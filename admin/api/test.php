<?php
ob_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$response = [
    'status' => 'API test',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

try {
    $response['checks']['session_active'] = session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO';
    $response['checks']['user_id'] = $_SESSION['user_id'] ?? 'NOT_SET';
    $response['checks']['request_method'] = $_SERVER['REQUEST_METHOD'];
    
    if (function_exists('csrfToken')) {
        $token = csrfToken();
        $response['checks']['csrf_token'] = substr($token, 0, 10) . '... (present)';
    } else {
        $response['checks']['csrf_token'] = 'FUNCTION_NOT_FOUND';
    }
    
    if (function_exists('validateCsrfToken')) {
        $response['checks']['validateCsrfToken'] = 'FUNCTION_EXISTS';
    } else {
        $response['checks']['validateCsrfToken'] = 'FUNCTION_NOT_FOUND';
    }
    
    if (function_exists('createNotification')) {
        $response['checks']['createNotification'] = 'FUNCTION_EXISTS';
    } else {
        $response['checks']['createNotification'] = 'FUNCTION_NOT_FOUND';
    }
    
    if (function_exists('logAudit')) {
        $response['checks']['logAudit'] = 'FUNCTION_EXISTS';
    } else {
        $response['checks']['logAudit'] = 'FUNCTION_NOT_FOUND';
    }
    
    try {
        $pdo = getDbConnection();
        $response['checks']['database'] = 'CONNECTED';
    } catch (Exception $e) {
        $response['checks']['database'] = 'ERROR: ' . $e->getMessage();
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

ob_clean();
echo json_encode($response);
exit;
?>
