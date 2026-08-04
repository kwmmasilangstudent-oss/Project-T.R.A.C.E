<?php
require_once __DIR__ . '/../includes/auth.php';

scannerRequireAuth();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'csrf_token' => $_SESSION['csrf_token']]);
exit;
