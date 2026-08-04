<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

scannerRequireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method.', 405);
}

$input = $_GET;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $decoded = $raw ? json_decode($raw, true) : [];
    if (is_array($decoded)) {
        $input = array_merge($_GET, $decoded);
    }
    $input = array_merge($input, $_POST);
}

$page = max(1, (int) ($input['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($input['date_from'])) {
    $where[] = 'DATE(sl.scanned_at) >= ?';
    $params[] = $input['date_from'];
}
if (!empty($input['date_to'])) {
    $where[] = 'DATE(sl.scanned_at) <= ?';
    $params[] = $input['date_to'];
}
if (!empty($input['result'])) {
    $allowed = ['success', 'not_found', 'inactive', 'expired', 'error'];
    if (in_array($input['result'], $allowed, true)) {
        $where[] = 'sl.scan_result = ?';
        $params[] = $input['result'];
    }
}
if (!empty($input['official'])) {
    $where[] = 'sl.scanned_by_name LIKE ?';
    $params[] = '%' . $input['official'] . '%';
}
$search = trim($input['search'] ?? '');
if ($search !== '') {
    $where[] = '(r.full_name LIKE ? OR sl.qr_code_scanned LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $pdo = getDbConnection();
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM scan_logs sl
         LEFT JOIN residents r ON r.id = sl.resident_id
         $whereSql"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $dataStmt = $pdo->prepare(
        "SELECT sl.*, r.full_name AS resident_name
         FROM scan_logs sl
         LEFT JOIN residents r ON r.id = sl.resident_id
         $whereSql
         ORDER BY sl.scanned_at DESC
         LIMIT $perPage OFFSET $offset"
    );
    $dataStmt->execute($params);
    $rows = $dataStmt->fetchAll();

    $totalPages = (int) ceil($total / $perPage);

    jsonSuccess([
        'rows' => $rows,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
    ]);
} catch (Throwable $e) {
    jsonError('Could not load scan history.', 500);
}
