<?php
require_once __DIR__ . '/db.php';

function sanitizeQrInput(mixed $value): string {
    if (!is_string($value)) {
        $value = (string) $value;
    }
    $value = trim($value);
    $value = str_replace(["\r", "\n", "\t"], '', $value);
    return mb_substr($value, 0, 255, 'UTF-8');
}

function getClientIp(): string {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return 'unknown';
}

function extractQrIdentifier(string $raw): string {
    $identifier = $raw;
    if (preg_match('/^resident:(\d+):/i', $raw, $m)) {
        $identifier = 'resident:' . $m[1];
    } elseif (str_contains($raw, ':')) {
        $parts = explode(':', $raw);
        $identifier = $parts[0] . ':' . ($parts[1] ?? '');
    }
    return $identifier;
}

function rateLimitScans(int $userId, int $max = 15, int $windowSeconds = 60): bool {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM scan_logs
             WHERE scanned_by_user_id = ? AND scanned_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)'
        );
        $stmt->execute([$userId, $windowSeconds]);
        $count = (int) $stmt->fetchColumn();
        return $count < $max;
    } catch (Throwable $e) {
        return true;
    }
}

function logScan(array $entry): void {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO scan_logs
             (resident_id, qr_code_scanned, scan_result, scanned_by_user_id, scanned_by_name, remarks, ip_address, user_agent, scanned_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $entry['resident_id'] ?? null,
            $entry['qr_code_scanned'] ?? '',
            $entry['scan_result'] ?? 'error',
            $entry['scanned_by_user_id'] ?? 0,
            $entry['scanned_by_name'] ?? null,
            $entry['remarks'] ?? null,
            $entry['ip_address'] ?? null,
            $entry['user_agent'] ?? null,
        ]);

        $agendaId = !empty($entry['agenda_id']) ? (int) $entry['agenda_id'] : null;
        if ($agendaId !== null) {
            $aStmt = $pdo->prepare(
                'INSERT INTO agenda_scan_logs
                 (agenda_id, resident_id, scan_result, scanned_by_user_id, scanned_by_name, remarks, ip_address, user_agent, scanned_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                     scan_result = VALUES(scan_result),
                     scanned_by_user_id = VALUES(scanned_by_user_id),
                     scanned_by_name = VALUES(scanned_by_name),
                     remarks = VALUES(remarks),
                     ip_address = VALUES(ip_address),
                     user_agent = VALUES(user_agent),
                     scanned_at = NOW()'
            );
            $aStmt->execute([
                $agendaId,
                $entry['resident_id'] ?? null,
                $entry['scan_result'] ?? 'error',
                $entry['scanned_by_user_id'] ?? 0,
                $entry['scanned_by_name'] ?? null,
                $entry['remarks'] ?? null,
                $entry['ip_address'] ?? null,
                $entry['user_agent'] ?? null,
            ]);

            // Only count the resident the first time they successfully check in.
            if (($entry['scan_result'] ?? '') === 'success') {
                $pdo->prepare(
                    'UPDATE agenda a
                     SET a.checkin_count = (
                         SELECT COUNT(DISTINCT resident_id)
                         FROM agenda_scan_logs
                         WHERE agenda_id = a.id AND scan_result = \'success\'
                     )
                     WHERE a.id = ?'
                )->execute([$agendaId]);
            }
        }
    } catch (Throwable $e) {
        // Logging failure must not break the scan response.
    }
}

function getResidentByQr(string $identifier): ?array {
    try {
        $pdo = getDbConnection();

        // The printed QR encodes "resident:<id>[:...]" (see includes/qr.php).
        // Resolve directly by primary key when the payload carries an id.
        if (preg_match('/^resident:(\d+)/i', $identifier, $m)) {
            $stmt = $pdo->prepare('SELECT * FROM residents WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $m[1]]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }

        // Fallback: match the stored identifier column (e.g. RES-2026-00035).
        $stmt = $pdo->prepare('SELECT * FROM residents WHERE qr_code_identifier = ? LIMIT 1');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        // Also try the raw QR value in case it was stored verbatim.
        $stmt = $pdo->prepare('SELECT * FROM residents WHERE qr_code_identifier = ? LIMIT 1');
        $stmt->execute([sanitizeQrInput($identifier)]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function computeAge(?string $birthDate): ?int {
    if (!$birthDate) {
        return null;
    }
    try {
        $b = new DateTime($birthDate);
        return (int) (new DateTime())->diff($b)->y;
    } catch (Throwable $e) {
        return null;
    }
}

function normalizeResidentRow(array $r): array {
    $dob = $r['birth_date'] ?? $r['date_of_birth'] ?? null;
    $phone = $r['contact_number'] ?? $r['phone'] ?? null;
    $cityId = $r['senior_citizen_id'] ?? ($r['osca_id'] ?? null);
    $emName = $r['emergency_contact'] ?? $r['emergency_contact_name'] ?? null;
    $emPhone = $r['emergency_contact_phone'] ?? null;
    $status = $r['status'] ?? 'active';
    $photo = $r['photo_path'] ?? null;

    $base = BASE_URL;
    $photoUrl = '';
    if (!empty($photo)) {
        $photoUrl = preg_match('#^https?://#i', $photo) ? $photo : $base . '/' . ltrim($photo, '/');
    }

    return [
        'id' => (int) $r['id'],
        'full_name' => $r['full_name'] ?? 'Unnamed Resident',
        'date_of_birth' => $dob,
        'age' => computeAge($dob),
        'gender' => $r['sex'] ?? $r['gender'] ?? null,
        'address' => $r['address'] ?? null,
        'photo_path' => $photoUrl,
        'senior_citizen_id' => $cityId,
        'contact_number' => $phone,
        'emergency_contact_name' => $emName,
        'emergency_contact_phone' => $emPhone,
        'medical_conditions' => $r['medical_conditions'] ?? null,
        'blood_type' => $r['blood_type'] ?? null,
        'qr_code_identifier' => $r['qr_code_identifier'] ?? null,
        'membership_status' => $status,
        'resident_type' => $r['resident_type'] ?? 'regular',
        'is_senior' => (bool) ($r['is_senior'] ?? 0),
    ];
}

function todayScanStats(int $userId): array {
    try {
        $pdo = getDbConnection();
        $sql = 'SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN scan_result = "success" THEN 1 ELSE 0 END) AS success,
            SUM(CASE WHEN scan_result = "not_found" THEN 1 ELSE 0 END) AS not_found,
            SUM(CASE WHEN scan_result IN ("inactive","expired") THEN 1 ELSE 0 END) AS inactive
            FROM scan_logs WHERE scanned_by_user_id = ? AND DATE(scanned_at) = CURDATE()';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return [
            'total' => (int) ($row['total'] ?? 0),
            'success' => (int) ($row['success'] ?? 0),
            'not_found' => (int) ($row['not_found'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
        ];
    } catch (Throwable $e) {
        return ['total' => 0, 'success' => 0, 'not_found' => 0, 'inactive' => 0];
    }
}
