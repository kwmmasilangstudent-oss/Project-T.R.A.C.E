<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

scannerRequireAuth();

echo json_encode([
    'success' => true,
    'stats' => todayScanStats(scannerUserId()),
], JSON_UNESCAPED_UNICODE);
exit;
