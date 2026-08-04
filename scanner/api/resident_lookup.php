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
        $input = array_merge($input, $decoded);
    }
    $input = array_merge($input, $_POST);
}

$term = trim($input['term'] ?? '');
$qr = trim($input['qr_code'] ?? '');

if ($term === '' && $qr === '') {
    jsonError('Please enter a name, senior citizen ID, or QR code.', 400);
}

try {
    $pdo = getDbConnection();
    if ($qr !== '') {
        $identifier = extractQrIdentifier(sanitizeQrInput($qr));
        $stmt = $pdo->prepare('SELECT * FROM residents WHERE qr_code_identifier = ? LIMIT 1');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch();
        if ($row) {
            jsonSuccess(['resident' => normalizeResidentRow($row)]);
        }
        jsonError('No resident found for that QR code.', 404);
    }

    $like = '%' . $term . '%';
    $stmt = $pdo->prepare(
        'SELECT * FROM residents
         WHERE full_name LIKE ? OR senior_citizen_id LIKE ? OR osca_id LIKE ? OR qr_code_identifier LIKE ?
         ORDER BY full_name ASC
         LIMIT 25'
    );
    $stmt->execute([$like, $like, $like, $like]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        jsonError('No residents found for "' . $term . '".', 404);
    }

    $result = array_map('normalizeResidentRow', $rows);
    jsonSuccess(['residents' => $result]);
} catch (Throwable $e) {
    jsonError('Lookup failed. Please try again.', 500);
}
