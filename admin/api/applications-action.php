<?php
ob_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

$response = [
    'success' => false,
    'message' => '',
    'status' => null,
    'notificationId' => null
];

try {
    requireAuth(['admin', 'secretary']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $csrfToken = $_POST['csrf_token'] ?? $_POST['_csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        throw new Exception('CSRF token validation failed');
    }

    $pdo = getDbConnection();

    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($applicationId <= 0) {
        throw new Exception('Invalid application ID');
    }

    if (!in_array($action, ['review', 'approve', 'reject', 'ready', 'complete', 'pending'])) {
        throw new Exception('Invalid action');
    }

    $statusMap = [
        'review' => 'under_review',
        'approve' => 'approved',
        'reject' => 'rejected',
        'ready' => 'ready_for_pickup',
        'complete' => 'completed',
        'pending' => 'pending'
    ];

    $status = $statusMap[$action];

    $updates = ['status' => $status, 'reviewed_by' => (int) ($_SESSION['user_id'] ?? 0), 'reviewed_at' => date('Y-m-d H:i:s')];
    $remarks = trim($_POST['remarks'] ?? '');
    if ($remarks) {
        $updates['remarks'] = $remarks;
    }

    $setParts = [];
    $params = [];
    foreach ($updates as $column => $value) {
        $setParts[] = $column . ' = ?';
        $params[] = $value;
    }
    $params[] = $applicationId;

    $stmt = $pdo->prepare('UPDATE applications SET ' . implode(', ', $setParts) . ' WHERE id = ?');
    if (!$stmt->execute($params)) {
        throw new Exception('Failed to update application');
    }

    $appRow = $pdo->prepare('SELECT a.*, r.full_name, r.user_id, a.application_type FROM applications a LEFT JOIN residents r ON r.id = a.resident_id WHERE a.id = ? LIMIT 1');
    $appRow->execute([$applicationId]);
    $appData = $appRow->fetch();

    if (!$appData) {
        throw new Exception('Application not found');
    }

    if (!empty($appData['user_id'])) {
        $type = $appData['application_type'] ?: 'request';
        $label = ucwords(str_replace('_', ' ', $status));
        $link = defined('BASE_URL') ? BASE_URL . '/resident/requests.php' : '/resident/requests.php';
        
        createNotification(
            (int) $appData['user_id'],
            'Your ' . $type . ' request #' . $applicationId . ' status is now ' . $label . '.',
            $link,
            (int) ($_SESSION['user_id'] ?? 0)
        );
        
        $response['notificationId'] = $pdo->lastInsertId();
    }

    logAudit('update_application', 'Application #' . $applicationId . ' status: ' . $status);

    $response['success'] = true;
    $response['status'] = $status;
    $response['message'] = 'Application updated successfully.';

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Application action error: ' . $e->getMessage());
} catch (Throwable $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Application action error: ' . $e->getMessage());
}

ob_clean();
echo json_encode($response);
exit;
