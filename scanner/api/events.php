<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

scannerRequireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Invalid request method.', 405);
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
         "SELECT id, title, agenda_date, time_from, is_scannable, scan_mode, expected_attendees, checkin_count
         FROM agenda
         WHERE status IN ('scheduled','ongoing','completed')
         ORDER BY agenda_date ASC, time_from ASC
         LIMIT 100"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $events = array_map(function ($r) {
        return [
            'id' => (int) $r['id'],
            'title' => $r['title'],
            'agenda_date' => $r['agenda_date'],
            'time_from' => $r['time_from'],
            'is_scannable' => (bool) $r['is_scannable'],
            'scan_mode' => $r['scan_mode'],
            'expected_attendees' => (int) $r['expected_attendees'],
            'checkin_count' => (int) $r['checkin_count'],
        ];
    }, $rows);

    jsonSuccess(['events' => $events]);
} catch (Throwable $e) {
    jsonError('Could not load events.', 500);
}
