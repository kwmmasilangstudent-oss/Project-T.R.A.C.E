<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

scannerRequireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$q = trim($_GET['q'] ?? '');

if ($q === '' || mb_strlen($q) < 1) {
    echo json_encode(['success' => true, 'residents' => []]);
    exit;
}

try {
    $pdo = getDbConnection();
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare(
        'SELECT id, full_name, senior_citizen_id, qr_code_identifier
         FROM residents
         WHERE full_name LIKE ? OR senior_citizen_id LIKE ? OR osca_id LIKE ?
         ORDER BY full_name ASC
         LIMIT 15'
    );
    $stmt->execute([$like, $like, $like]);
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'id' => (int) $row['id'],
            'full_name' => $row['full_name'],
            'senior_citizen_id' => $row['senior_citizen_id'] ?? '',
            'qr_code_identifier' => $row['qr_code_identifier'] ?? '',
        ];
    }

    echo json_encode(['success' => true, 'residents' => $result]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed. Please try again.']);
}