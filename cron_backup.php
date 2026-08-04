<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (PHP_SAPI !== 'cli') {
    $key = $_GET['key'] ?? '';
    $stored = getSetting('cron_key', '');
    if ($key === '' || $stored === '' || !hash_equals($stored, $key)) {
        http_response_code(403);
        exit;
    }
}

$fileName = runScheduledBackup();
if ($fileName) {
    echo "Backup created: $fileName\n";
    logError('Scheduled backup completed: ' . $fileName);
} else {
    echo "No backup needed (within interval) or failed.\n";
}
