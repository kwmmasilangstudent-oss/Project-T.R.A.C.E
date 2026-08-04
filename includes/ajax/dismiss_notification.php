<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$notificationId = (int) ($_POST['notification_id'] ?? 0);

if ($notificationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT created_by FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $userId]);
    $createdBy = $stmt->fetchColumn();

    if ($createdBy && (int) $createdBy === $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot dismiss this notification']);
        exit;
    }

    $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
