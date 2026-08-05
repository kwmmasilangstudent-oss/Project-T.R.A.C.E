<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

function asset(string $path): string {
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'assets/')) {
        return BASE_URL . '/' . $path;
    }
    return BASE_URL . '/assets/' . $path;
}

function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function enforceSessionTimeout(): void {
    $limit = 30 * 60;
    $now = time();
    if (!empty($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > $limit) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function requireAuth(array $roles = []): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    enforceSessionTimeout();

    if ($roles && (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true))) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function getCurrentRole(): string {
    return $_SESSION['role'] ?? 'guest';
}

function getLandingContent(string $section, string $default = ''): string {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT content FROM landing_content WHERE section_name = ? LIMIT 1');
        $stmt->execute([$section]);
        $row = $stmt->fetch();
        return $row['content'] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function getRoleLabel(string $role): string {
    return match ($role) {
        'admin' => 'Administrator',
        'secretary' => 'Barangay Secretary',
        'resident' => 'Resident',
        default => 'Guest',
    };
}

function getUnreadNotificationCount(int $userId): int {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function getUnreadAnnouncementCount(int $residentId): int {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM announcement_reads WHERE resident_id = ? AND is_read = 0');
        $stmt->execute([$residentId]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function getDashboardStats(): array {
    try {
        $pdo = getDbConnection();
        $stats = [];

        $stats['total_residents'] = (int) $pdo->query('SELECT COUNT(*) FROM residents')->fetchColumn();
        $stats['total_officials'] = (int) $pdo->query('SELECT COUNT(*) FROM officials')->fetchColumn();
        $stats['total_users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        $male = (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE sex = "Male"')->fetchColumn();
        $female = (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE sex = "Female"')->fetchColumn();
        $stats['male_count'] = $male;
        $stats['female_count'] = $female;

        $ageStats = $pdo->query('SELECT TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) as age FROM residents WHERE birth_date IS NOT NULL')->fetchAll();
        $ageDistribution = [
            '0-17' => 0,
            '18-35' => 0,
            '36-50' => 0,
            '51-65' => 0,
            '65+' => 0
        ];
        foreach ($ageStats as $row) {
            $age = (int) $row['age'];
            if ($age <= 17) $ageDistribution['0-17']++;
            elseif ($age <= 35) $ageDistribution['18-35']++;
            elseif ($age <= 50) $ageDistribution['36-50']++;
            elseif ($age <= 65) $ageDistribution['51-65']++;
            else $ageDistribution['65+']++;
        }
        $stats['age_distribution'] = $ageDistribution;

        $stats['pending_requests'] = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE status IN ("submitted", "pending")')->fetchColumn();
        $stats['approved_requests'] = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE status = "approved"')->fetchColumn();
        $stats['rejected_requests'] = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE status = "rejected"')->fetchColumn();

        $stats['total_projects'] = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
        $stats['ongoing_projects'] = (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE status = "ongoing"')->fetchColumn();
        $stats['completed_projects'] = (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE status = "completed"')->fetchColumn();

        $stats['total_budget'] = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM project_budget')->fetchColumn();
        $stats['total_expenses'] = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM expenses')->fetchColumn();

        $stats['documents_issued'] = (int) $pdo->query('SELECT COUNT(*) FROM documents WHERE status = "issued"')->fetchColumn();
        $stats['total_announcements'] = (int) $pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn();
        $stats['total_appointments'] = (int) $pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn();

        $stats['submitted'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'submitted'")->fetchColumn();
        $stats['pending'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
        $stats['under_review'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'under_review'")->fetchColumn();
        $stats['approved'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'")->fetchColumn();
        $stats['ready_for_pickup'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'ready_for_pickup'")->fetchColumn();
        $stats['completed'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'")->fetchColumn();
        $stats['rejected'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'")->fetchColumn();

        $stats['planned'] = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'planned'")->fetchColumn();
        $stats['ongoing'] = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'ongoing'")->fetchColumn();
        $stats['projects_completed'] = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();

        $stats['qr_scans'] = (int) $pdo->query('SELECT COUNT(*) FROM verification_logs')->fetchColumn();

        $monthlyStats = $pdo->query('SELECT DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count FROM applications GROUP BY month ORDER BY month DESC LIMIT 12')->fetchAll();
        $stats['monthly_applications'] = $monthlyStats;

        $dailyStats = $pdo->query('SELECT DATE(created_at) as day, COUNT(*) as count FROM applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY day ORDER BY day')->fetchAll();
        $stats['daily_applications'] = $dailyStats;

        $yearlyStats = $pdo->query('SELECT YEAR(created_at) as year, COUNT(*) as count FROM applications GROUP BY year ORDER BY year DESC LIMIT 5')->fetchAll();
        $stats['yearly_applications'] = $yearlyStats;

        return $stats;
    } catch (Throwable $e) {
        return [];
    }
}

function getResidentStats(int $userId): array {
    try {
        $pdo = getDbConnection();
        $stats = [];

        $residentStmt = $pdo->prepare('SELECT id FROM residents WHERE user_id = ? LIMIT 1');
        $residentStmt->execute([$userId]);
        $resident = $residentStmt->fetch();

        if ($resident) {
            $residentId = (int) $resident['id'];
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE resident_id = ?');
            $stmt->execute([$residentId]);
            $stats['my_requests'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM documents WHERE resident_id = ?');
            $stmt->execute([$residentId]);
            $stats['my_documents'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE resident_id = ?');
            $stmt->execute([$residentId]);
            $stats['my_appointments'] = (int) $stmt->fetchColumn();
            $stats['unread_announcements'] = getUnreadAnnouncementCount($residentId);
            $stats['unread_notifications'] = getUnreadNotificationCount($userId);
        } else {
            $stats['my_requests'] = 0;
            $stats['my_documents'] = 0;
            $stats['my_appointments'] = 0;
            $stats['unread_announcements'] = 0;
            $stats['unread_notifications'] = 0;
        }

        return $stats;
    } catch (Throwable $e) {
        return [
            'my_requests' => 0,
            'my_documents' => 0,
            'my_appointments' => 0,
            'unread_announcements' => 0,
            'unread_notifications' => 0
        ];
    }
}

function getSecretaryStats(): array {
    try {
        $pdo = getDbConnection();
        $stats = [];

        $stats['total_residents'] = (int) $pdo->query('SELECT COUNT(*) FROM residents')->fetchColumn();
        $stats['pending_applications'] = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE status IN ("submitted", "pending", "under_review")')->fetchColumn();
        $stats['documents_issued'] = (int) $pdo->query('SELECT COUNT(*) FROM documents WHERE status = "issued"')->fetchColumn();
        $stats['total_announcements'] = (int) $pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn();
        $stats['total_projects'] = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
        $stats['upcoming_agenda'] = (int) $pdo->query('SELECT COUNT(*) FROM agenda WHERE agenda_date >= CURDATE() AND status = "scheduled"')->fetchColumn();

        return $stats;
    } catch (Throwable $e) {
        return [];
    }
}

function logAudit(string $action, ?string $details = null): void {
    try {
        if (!isLoggedIn()) return;

        $pdo = getDbConnection();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $action, $details, $ipAddress, $userAgent]);
    } catch (Throwable $e) {
        // Silent fail for logging
    }
}

function publishAnnouncement(
    PDO $pdo,
    array $data
): ?int {
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');
    if ($title === '' || $content === '') {
        return null;
    }

    $audience = trim($data['audience'] ?? 'all');
    $type = trim($data['type'] ?? 'general');
    $priority = trim($data['priority'] ?? 'normal');
    $isPinned = !empty($data['is_pinned']) ? 1 : 0;
    $expiresAt = $data['expires_at'] ?? null;
    if ($expiresAt) {
        $expiresAt = date('Y-m-d H:i:s', strtotime($expiresAt));
    } else {
        $expiresAt = null;
    }
    $attachment = $data['attachment_path'] ?? null;
    $createdBy = (int) ($_SESSION['user_id'] ?? null);

    $stmt = $pdo->prepare('INSERT INTO announcements (title, content, audience, type, priority, is_pinned, expires_at, attachment_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $content, $audience, $type, $priority, $isPinned, $expiresAt, $attachment, $createdBy]);

    $announcementId = (int) $pdo->lastInsertId();

    $link = defined('BASE_URL') ? BASE_URL . '/resident/announcement.php?id=' . $announcementId : '/resident/announcement.php?id=' . $announcementId;

    if ($audience === 'all') {
        $residents = $pdo->query('SELECT id, user_id FROM residents')->fetchAll();
        $readStmt = $pdo->prepare('INSERT IGNORE INTO announcement_reads (announcement_id, resident_id, is_read) VALUES (?, ?, 0)');
        foreach ($residents as $resident) {
            $readStmt->execute([$announcementId, $resident['id']]);
            if (!empty($resident['user_id'])) {
                createNotification((int) $resident['user_id'], 'New announcement: ' . $title, $link, $createdBy);
            }
        }
    } elseif ($audience === 'secretary') {
        $users = $pdo->query('SELECT id FROM users WHERE role = "secretary" AND status = "active"')->fetchAll();
        $secLink = defined('BASE_URL') ? BASE_URL . '/secretary/announcements.php' : '/secretary/announcements.php';
        foreach ($users as $user) {
            createNotification((int) $user['id'], 'New announcement for secretaries: ' . $title, $secLink, $createdBy);
        }
    } elseif ($audience === 'admin') {
        $users = $pdo->query('SELECT id FROM users WHERE role = "admin" AND status = "active"')->fetchAll();
        $admLink = defined('BASE_URL') ? BASE_URL . '/admin/announcements.php' : '/admin/announcements.php';
        foreach ($users as $user) {
            createNotification((int) $user['id'], 'New announcement for admins: ' . $title, $admLink, $createdBy);
        }
    }

    if ($createdBy) {
        $role = getCurrentRole();
        $ownLink = defined('BASE_URL') ? BASE_URL . '/admin/announcements.php' : '/admin/announcements.php';
        $ownMsg = 'You published an announcement: ' . $title;
        if ($role === 'secretary') {
            $ownLink = defined('BASE_URL') ? BASE_URL . '/secretary/announcements.php' : '/secretary/announcements.php';
        }
        createNotification($createdBy, $ownMsg, $ownLink, $createdBy);
    }

    return $announcementId;
}

function ensureResidentAnnouncementReads(PDO $pdo, int $residentId): void {
    $pdo->prepare('INSERT IGNORE INTO announcement_reads (announcement_id, resident_id, is_read)
        SELECT a.id, ?, 0
        FROM announcements a
        WHERE a.audience = "all" AND a.is_active = 1
          AND (a.expires_at IS NULL OR a.expires_at >= NOW())
          AND NOT EXISTS (
            SELECT 1 FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.resident_id = ?
          )')
        ->execute([$residentId, $residentId]);
}

function createNotification(int $userId, string $message, ?string $link = null, ?int $createdBy = null): void {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message, link, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $message, $link, $createdBy]);
    } catch (Throwable $e) {
        // Silent fail
    }
}

function notifyApplicationSubmitted(int $applicationId): void {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT a.application_type, r.user_id FROM applications a LEFT JOIN residents r ON r.id = a.resident_id WHERE a.id = ? LIMIT 1');
        $stmt->execute([$applicationId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['user_id'])) {
            $type = $row['application_type'] ?: 'request';
            $link = defined('BASE_URL') ? BASE_URL . '/resident/requests.php' : '/resident/requests.php';
            $createdBy = (int) $row['user_id'];
            createNotification((int) $row['user_id'], 'Your ' . $type . ' request #' . $applicationId . ' has been submitted. We will notify you of updates.', $link, $createdBy);
        }
        $adminStmt = $pdo->query('SELECT id FROM users WHERE role IN ("secretary", "admin")');
        $adminUsers = $adminStmt->fetchAll();
        foreach ($adminUsers as $adminUser) {
            createNotification((int) $adminUser['id'], 'New ' . ($row['application_type'] ?? 'request') . ' request #' . $applicationId . ' submitted by a resident.', $link, (int) $row['user_id']);
        }
    } catch (Throwable $e) {
        // Silent fail
    }
}

function notifyApplicationStatus(int $applicationId, string $status, ?int $createdBy = null): void {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT a.application_type, r.user_id FROM applications a LEFT JOIN residents r ON r.id = a.resident_id WHERE a.id = ? LIMIT 1');
        $stmt->execute([$applicationId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['user_id'])) {
            $type = $row['application_type'] ?: 'request';
            $label = ucwords(str_replace('_', ' ', $status));
            $link = defined('BASE_URL') ? BASE_URL . '/resident/requests.php' : '/resident/requests.php';
            createNotification((int) $row['user_id'], 'Your ' . $type . ' request #' . $applicationId . ' status is now ' . $label . '.', $link, $createdBy);
        }
    } catch (Throwable $e) {
        // Silent fail
    }
}

function formatCurrency(float $amount): string {
    return '₱' . number_format($amount, 2);
}

function getSetting(string $key, string $default = ''): string {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT key_value FROM settings WHERE key_name = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row['key_value'] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function getMaintenanceMode(): bool {
    return getSetting('maintenance_mode', '0') === '1';
}

function isMaintenanceModeActive(): bool {
    if (getMaintenanceMode() && (!isLoggedIn() || getCurrentRole() !== 'admin')) {
        return true;
    }
    return false;
}

function getAgeFromDate(?string $birthDate): ?int {
    if (!$birthDate) return null;
    $birth = new DateTime($birthDate);
    $now = new DateTime();
    return (int) $now->diff($birth)->y;
}

function getSexDistribution(): array {
    try {
        $pdo = getDbConnection();
        $male = (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE sex = "Male"')->fetchColumn();
        $female = (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE sex = "Female"')->fetchColumn();
        return ['male' => $male, 'female' => $female];
    } catch (Throwable $e) {
        return ['male' => 0, 'female' => 0];
    }
}

function getSessionUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getSessionUserName(): string {
    return $_SESSION['name'] ?? 'Guest';
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function getThemeForCurrentRole(string $default = 'light'): string {
    $role = getCurrentRole();
    $key = match ($role) {
        'admin' => 'theme_admin',
        'secretary' => 'theme_secretary',
        'resident' => 'theme_resident',
        default => 'theme',
    };
    return getSetting($key, $default);
}

function csrfField(): string {
    $token = $_SESSION['_csrf_token'] ?? '';
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

function validateCsrfToken(?string $token): bool {
    if (empty($token)) return false;
    $stored = $_SESSION['_csrf_token'] ?? '';
    if ($stored === '') return false;
    return hash_equals($stored, $token);
}

function regenerateCsrfToken(): void {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['_csrf_token_time'] = time();
}

function validatePasswordStrength(string $password): ?string {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    return null;
}

function requireCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['_csrf_token'] ?? '';
    if (empty($token) || !validateCsrfToken($token)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Security Validation Failed</title>';
        echo '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8f9fa;}.card{max-width:420px;padding:32px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);text-align:center;}h1{font-size:1.2rem;color:#dc2626;margin-bottom:8px;}p{color:#64748b;font-size:0.9rem;}</style>';
        echo '</head><body><div class="card"><h1>Security Validation Failed</h1>';
        echo '<p>Your session may have expired. Please refresh the page and try again.</p>';
        echo '<a href="javascript:location.reload()" style="display:inline-block;margin-top:16px;padding:10px 24px;background:#6366f1;color:#fff;text-decoration:none;border-radius:8px;">Refresh Page</a>';
        echo '</div></body></html>';
        exit;
    }
}

function isDebugMode(): bool {
    return true;
}

function logServiceUnavailable(?Throwable $exception = null): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $errorMessage = '[503] ' . date('Y-m-d H:i:s') . ' ';
    if ($exception !== null) {
        $errorMessage .= $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();
    }
    $lastError = error_get_last();
    if ($lastError !== null) {
        $errorMessage .= ' | last_error: ' . $lastError['message'] . ' in ' . $lastError['file'] . ' on line ' . $lastError['line'];
    }
    $result = @file_put_contents($logDir . '/service_unavailable.log', $errorMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    if ($result === false) {
        @file_put_contents($logDir . '/service_unavailable_failure.log', '[WRITE FAIL] ' . $errorMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function renderServiceUnavailable(?Throwable $exception = null): void {
    logServiceUnavailable($exception);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(503);
    $base = defined('BASE_URL') ? BASE_URL : '/';
    $showDetails = isDebugMode();
    header('X-Debug-Service-Unavailable: 1');
    if ($exception !== null) {
        header('X-Debug-Exception: ' . substr($exception->getMessage(), 0, 200));
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Service Unavailable</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>body{background:#f8f9fa;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.card{max-width:640px;border:none;box-shadow:0 4px 24px rgba(0,0,0,.08)}</style>';
    echo '</head><body><div class="card p-5 text-center"><h1 class="display-4 text-muted mb-3">503</h1>';
    echo '<p class="lead mb-4">Service is temporarily unavailable. Please try again later.</p>';
    if ($showDetails) {
        echo '<div class="text-start bg-white p-3 rounded shadow-sm mb-3"><h5 class="mb-2">Debug details</h5>';
        if ($exception !== null) {
            echo '<pre style="white-space: pre-wrap; color: #b91c1c;">' . htmlspecialchars((string) $exception) . '</pre>';
        }
        $lastError = error_get_last();
        if ($lastError !== null) {
            echo '<pre style="white-space: pre-wrap; color: #6b7280;">Last error: ' . htmlspecialchars($lastError['message'] . ' in ' . $lastError['file'] . ' on line ' . $lastError['line']) . '</pre>';
        }
        echo '</div>';
    }
    echo '<a href="' . $base . '" class="btn btn-primary">Go Home</a></div></body></html>';
    exit;
}

function sendEmail(string $to, string $subject, string $body, string $altBody = ''): bool {
    require_once __DIR__ . '/phpmailer/autoload.php';
    require_once __DIR__ . '/../config/email.php';
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Email send failed: ' . $e->getMessage());
        return false;
    }
}

set_exception_handler('renderServiceUnavailable');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        renderServiceUnavailable(null);
    }
});

function logError(string $message, ?Throwable $exception = null): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $trace = $exception ? "\n" . $exception->getTraceAsString() : '';
    $line = "[$timestamp] $message$trace" . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function paginate(string $countQuery, array $countParams, string $dataQuery, array $dataParams, int $perPage = 20, string $pageParam = 'page'): array {
    $page = max(1, (int) ($_GET[$pageParam] ?? 1));
    try {
        $pdo = getDbConnection();
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetchColumn();
    } catch (Throwable $e) {
        logError('Pagination count query failed', $e);
        $total = 0;
    }
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    try {
        $pdo = getDbConnection();
        $dataStmt = $pdo->prepare($dataQuery . " LIMIT $perPage OFFSET $offset");
        $dataStmt->execute($dataParams);
        $data = $dataStmt->fetchAll();
    } catch (Throwable $e) {
        logError('Pagination data query failed', $e);
        $data = [];
    }
    return [
        'data' => $data,
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'pageParam' => $pageParam,
    ];
}

function renderPagination(array $p): string {
    if ($p['totalPages'] <= 1) return '';
    $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-center mb-0" style="gap:2px;">';
    $params = $_GET;
    $pageParam = $p['pageParam'] ?? 'page';
    if ($p['page'] > 1) {
        $params[$pageParam] = $p['page'] - 1;
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query($params) . '"><i class="bi bi-chevron-left"></i></a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>';
    }
    $start = max(1, $p['page'] - 2);
    $end = min($p['totalPages'], $p['page'] + 2);
    if ($start > 1) {
        $params[$pageParam] = 1;
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query($params) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $params[$pageParam] = $i;
        if ($i === $p['page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query($params) . '">' . $i . '</a></li>';
        }
    }
    if ($end < $p['totalPages']) {
        if ($end < $p['totalPages'] - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $params[$pageParam] = $p['totalPages'];
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query($params) . '">' . $p['totalPages'] . '</a></li>';
    }
    if ($p['page'] < $p['totalPages']) {
        $params[$pageParam] = $p['page'] + 1;
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query($params) . '"><i class="bi bi-chevron-right"></i></a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

function cacheGet(string $key): mixed {
    static $cache = [];
    return $cache[$key] ?? null;
}

function cacheSet(string $key, mixed $value): void {
    static $cache = [];
    $cache[$key] = $value;
}

function cacheClear(): void {
    static $cache = [];
    $cache = [];
}

function cachedQuery(string $sql, array $params = [], int $ttl = 60): mixed {
    $key = $sql . '|' . serialize($params);
    $cached = cacheGet($key);
    if ($cached !== null && (time() - $cached['time']) < $ttl) {
        return $cached['data'];
    }
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        cacheSet($key, ['data' => $result, 'time' => time()]);
        return $result;
    } catch (Throwable $e) {
        logError('Cached query failed: ' . $sql, $e);
        return [];
    }
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone(string $phone): bool {
    return preg_match('/^(?:\+63|0)[0-9]{10}$/', preg_replace('/[\s\-\(\)]/', '', $phone)) === 1;
}

function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateEnum(string $value, array $allowed): bool {
    return in_array($value, $allowed, true);
}

function sanitizeString(string $value): string {
    return trim(preg_replace('/[<>]/', '', $value));
}

function sanitizeFilename(string $filename): string {
    return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
}

function runScheduledBackup(): ?string {
    try {
        $pdo = getDbConnection();
        $lastBackup = getSetting('last_scheduled_backup', '0');
        $interval = 86400;
        if ($lastBackup !== '0' && (time() - (int)$lastBackup) < $interval) {
            return null;
        }
        $backupDir = __DIR__ . '/../assets/uploads/backups/';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0777, true);
        }
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $sql = '';
        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE ' . $table)->fetch();
            $sql .= "\n\n" . $create['Create Table'] . ";\n\n";
            $rows = $pdo->query('SELECT * FROM ' . $table)->fetchAll();
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, $row);
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
        }
        $fileName = 'auto_backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($backupDir . $fileName, $sql);
        $allBackups = glob($backupDir . 'auto_backup_*.sql');
        rsort($allBackups);
        $keep = 10;
        foreach (array_slice($allBackups, $keep) as $old) {
            @unlink($old);
        }
        $pdo->prepare('INSERT INTO system_logs (message) VALUES (?)')->execute(['Scheduled backup created: ' . $fileName]);
        logAudit('scheduled_backup', 'Automatic backup created: ' . $fileName);
        $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES ('last_scheduled_backup', ?) ON DUPLICATE KEY UPDATE key_value = ?")
            ->execute([(string)time(), (string)time()]);
        return $fileName;
    } catch (Throwable $e) {
        logError('Scheduled backup failed', $e);
        return null;
    }
}

?>
