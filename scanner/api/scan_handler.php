<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

scannerRequireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method.', 405);
}

$rawInput = file_get_contents('php://input');
$payload = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (empty($payload)) {
    $payload = $_POST;
}

$csrfToken = $payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!isset($_SESSION['csrf_token']) || !is_string($csrfToken) || !hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
    jsonError('Security token missing or invalid. Please refresh the page.', 403);
}

$rawCode = sanitizeQrInput($payload['qr_code'] ?? '');
$remarks = isset($payload['remarks']) ? mb_substr(trim((string) $payload['remarks']), 0, 500, 'UTF-8') : null;
$agendaId = isset($payload['agenda_id']) ? (int) $payload['agenda_id'] : 0;
if ($agendaId <= 0) {
    $agendaId = null;
}

if ($rawCode === '') {
    jsonError('No QR code received.', 400);
}

if (strlen($rawCode) < 3) {
    jsonError('QR code is too short to be valid.', 400);
}

$userId = scannerUserId();
$userName = scannerUserName();
$ip = getClientIp();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (!rateLimitScans($userId, 15, 60)) {
    logScan([
        'resident_id' => null,
        'qr_code_scanned' => $rawCode,
        'scan_result' => 'error',
        'scanned_by_user_id' => $userId,
        'scanned_by_name' => $userName,
        'remarks' => 'Rate limit exceeded',
        'agenda_id' => $agendaId,
        'ip_address' => $ip,
        'user_agent' => $userAgent,
    ]);
    jsonError('Too many scans. Please wait a moment before scanning again.', 429);
}

$identifier = extractQrIdentifier($rawCode);

$resident = getResidentByQr($identifier);

if (!$resident) {
    logScan([
        'resident_id' => null,
        'qr_code_scanned' => $rawCode,
        'scan_result' => 'not_found',
        'scanned_by_user_id' => $userId,
        'scanned_by_name' => $userName,
        'remarks' => $remarks,
        'agenda_id' => $agendaId,
        'ip_address' => $ip,
        'user_agent' => $userAgent,
    ]);
    jsonError('QR Code not recognized. This card may not be registered. Please verify manually or register this resident.', 404, [
        'status' => 'not_found',
        'qr_code' => $rawCode,
        'agenda_id' => $agendaId,
        'resident' => null,
        'scanned_at' => date('Y-m-d H:i:s'),
        'scanned_by' => $userName,
    ]);
}

$status = $resident['status'] ?? 'active';

if ($status === 'inactive' || $status === 'expired') {
    logScan([
        'resident_id' => (int) $resident['id'],
        'qr_code_scanned' => $rawCode,
        'scan_result' => $status,
        'scanned_by_user_id' => $userId,
        'scanned_by_name' => $userName,
        'remarks' => $remarks,
        'agenda_id' => $agendaId,
        'ip_address' => $ip,
        'user_agent' => $userAgent,
    ]);
    jsonSuccess([
        'status' => $status,
        'qr_code' => $rawCode,
        'agenda_id' => $agendaId,
        'resident' => normalizeResidentRow($resident),
        'message' => $status === 'expired'
            ? 'This resident record has EXPIRED. Please renew their registration.'
            : 'This resident record is INACTIVE. Please verify with the barangay office.',
        'scanned_at' => date('Y-m-d H:i:s'),
        'scanned_by' => $userName,
    ]);
}

logScan([
    'resident_id' => (int) $resident['id'],
    'qr_code_scanned' => $rawCode,
    'scan_result' => 'success',
    'scanned_by_user_id' => $userId,
    'scanned_by_name' => $userName,
    'remarks' => $remarks,
    'agenda_id' => $agendaId,
    'ip_address' => $ip,
    'user_agent' => $userAgent,
]);

$badgeLabel = 'VERIFIED RESIDENT';
jsonSuccess([
    'status' => 'active',
    'qr_code' => $rawCode,
    'agenda_id' => $agendaId,
    'resident' => normalizeResidentRow($resident),
    'message' => $badgeLabel,
    'scanned_at' => date('Y-m-d H:i:s'),
    'scanned_by' => $userName,
]);
