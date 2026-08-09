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

    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($appointmentId <= 0) {
        throw new Exception('Invalid appointment ID');
    }

    if (!in_array($action, ['approve', 'reject', 'complete', 'pending', 'cancel'])) {
        throw new Exception('Invalid action');
    }

    $statusMap = [
        'approve' => 'approved',
        'reject' => 'rejected',
        'complete' => 'completed',
        'pending' => 'pending',
        'cancel' => 'cancelled'
    ];

    $status = $statusMap[$action];

    $updateStmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
    if (!$updateStmt->execute([$status, $appointmentId])) {
        throw new Exception('Failed to update appointment');
    }

    $apptRow = $pdo->prepare('SELECT a.*, r.full_name, r.user_id FROM appointments a 
        LEFT JOIN residents r ON r.id = a.resident_id WHERE a.id = ? LIMIT 1');
    $apptRow->execute([$appointmentId]);
    $apptData = $apptRow->fetch();

    if (!$apptData) {
        throw new Exception('Appointment not found');
    }

    if ($apptData && !empty($apptData['user_id'])) {
        $statusLabel = ucfirst($status);
        $message = 'Your appointment on ' . date('M d, Y', strtotime($apptData['appointment_date'])) 
                 . ' has been ' . $statusLabel . '.';
        
        createNotification(
            (int) $apptData['user_id'],
            $message,
            defined('BASE_URL') ? BASE_URL . '/resident/appointments.php' : '/resident/appointments.php',
            (int) ($_SESSION['user_id'] ?? 0)
        );
        
        $response['notificationId'] = $pdo->lastInsertId();
    }

    logAudit('update_appointment', 'Appointment #' . $appointmentId . ' status: ' . $status);

    $response['success'] = true;
    $response['status'] = $status;
    $response['message'] = 'Appointment updated successfully.';

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Appointment action error: ' . $e->getMessage());
} catch (Throwable $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Appointment action error: ' . $e->getMessage());
}

ob_clean();
echo json_encode($response);
exit;
