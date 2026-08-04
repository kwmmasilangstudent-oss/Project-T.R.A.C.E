<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

scannerRequireAuth();

$eventId = max(0, (int) ($_GET['event_id'] ?? 0));
$action = $_GET['action'] ?? '';

function loadEvents(): array {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        "SELECT id, title, agenda_date, time_from, meeting_type, expected_attendees, checkin_count, status
         FROM agenda
         WHERE status IN ('scheduled','ongoing','completed')
         ORDER BY agenda_date DESC, time_from DESC
         LIMIT 200"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

$base = BASE_URL;
$cssSrc = $base . '/scanner/assets/css/scanner.css';
$allEvents = loadEvents();

function loadEvent(int $id): ?array {
    if ($id <= 0) return null;
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT id, title, description, agenda_date, time_from, time_to, location,
                meeting_type, expected_attendees, checkin_count
         FROM agenda
         WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function fetchAttendees(int $eventId): array {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        "SELECT asl.scanned_at,
                r.full_name,
                r.senior_citizen_id,
                r.address,
                r.contact_number,
                r.sex,
                r.birth_date,
                r.civil_status,
                r.occupation,
                asl.scanned_by_name,
                asl.remarks,
                asl.ip_address
         FROM agenda_scan_logs asl
         LEFT JOIN residents r ON r.id = asl.resident_id
         WHERE asl.agenda_id = ?
           AND asl.scan_result = 'success'
           AND asl.resident_id IS NOT NULL
         ORDER BY asl.scanned_at ASC"
    );
    $stmt->execute([$eventId]);
    return $stmt->fetchAll();
}

function fetchEventStats(int $eventId): array {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_scans,
            COUNT(DISTINCT resident_id) AS unique_attendees,
            SUM(CASE WHEN scan_result = 'success' THEN 1 ELSE 0 END) AS success_count,
            SUM(CASE WHEN scan_result IN ('inactive','expired') THEN 1 ELSE 0 END) AS inactive_count,
            SUM(CASE WHEN scan_result = 'not_found' THEN 1 ELSE 0 END) AS not_found_count,
            SUM(CASE WHEN scan_result = 'error' THEN 1 ELSE 0 END) AS error_count
         FROM agenda_scan_logs
         WHERE agenda_id = ?"
    );
    $stmt->execute([$eventId]);
    $row = $stmt->fetch();
    return [
        'total_scans' => (int) ($row['total_scans'] ?? 0),
        'unique_attendees' => (int) ($row['unique_attendees'] ?? 0),
        'success_count' => (int) ($row['success_count'] ?? 0),
        'inactive_count' => (int) ($row['inactive_count'] ?? 0),
        'not_found_count' => (int) ($row['not_found_count'] ?? 0),
        'error_count' => (int) ($row['error_count'] ?? 0),
    ];
}

function generateDocumentId(int $eventId): string {
    $date = date('Y-m-d');
    $num = str_pad((string) $eventId, 4, '0', STR_PAD_LEFT);
    return "ATT-{$date}-{$num}";
}

function formatTime(?string $t): string {
    if (!$t) return '—';
    $dt = DateTime::createFromFormat('H:i:s', $t);
    if (!$dt) $dt = DateTime::createFromFormat('H:i', $t);
    return $dt ? $dt->format('g:i A') : $t;
}

function formatDate(?string $d): string {
    if (!$d) return '—';
    try {
        return DateTime::createFromFormat('Y-m-d', $d)->format('F j, Y');
    } catch (Throwable $e) {
        return $d;
    }
}

$event = $eventId > 0 ? loadEvent($eventId) : null;
$attendees = $eventId > 0 ? fetchAttendees($eventId) : [];
$stats = $eventId > 0 ? fetchEventStats($eventId) : ['total_scans' => 0, 'unique_attendees' => 0, 'success_count' => 0, 'inactive_count' => 0, 'not_found_count' => 0, 'error_count' => 0];
$docId = $eventId > 0 ? generateDocumentId($eventId) : 'ATT-0000';
$generatedAt = date('F j, Y \a\t g:i A');
$generatedBy = htmlspecialchars(scannerUserName(), ENT_QUOTES, 'UTF-8');

$pageTitle = $event ? ('Attendance — ' . $event['title']) : 'Attendance Sheet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> — <?php echo e(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --accent: #1a56db;
            --accent-dark: #1042a3;
            --text: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --success-bg: #f0fdf4;
            --success-text: #166534;
            --warn-bg: #fffbeb;
            --warn-text: #92400e;
        }

        * { box-sizing: border-box; }
        body {
            font-family: var(--font);
            background: #f1f5f9;
            color: var(--text);
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Toolbar (hidden on print) ── */
        .as-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 16px 24px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .as-toolbar-title { font-weight: 700; font-size: 18px; color: var(--text); }
        .as-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .as-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: var(--font);
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .as-btn-primary {
            background: var(--accent);
            color: #ffffff;
            box-shadow: 0 1px 3px rgba(26, 86, 219, 0.2);
        }
        .as-btn-primary:hover { background: var(--accent-dark); color: #ffffff; }
        .as-btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-strong);
        }
        .as-btn-ghost:hover { background: var(--surface-alt); color: var(--text); }

        /* ── Sheet container ── */
        .as-sheet-wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 16px 48px;
        }

        /* ── Attendance Sheet ── */
        .as-sheet {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        /* ── Header ── */
        .as-header {
            background: linear-gradient(135deg, #1e40af 0%, #1a56db 100%);
            color: #ffffff;
            padding: 28px 32px 24px;
            position: relative;
            overflow: hidden;
        }
        .as-header::before {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
            pointer-events: none;
        }
        .as-header-content { position: relative; z-index: 1; }
        .as-doc-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 6px;
        }
        .as-meeting-title {
            font-size: 26px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0 0 16px;
        }
        .as-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .as-meta-item { display: flex; flex-direction: column; gap: 3px; }
        .as-meta-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.75;
        }
        .as-meta-value { font-size: 15px; font-weight: 600; }
        .as-meta-value.mono { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 13px; letter-spacing: 0.02em; }

        /* ── Stats bar ── */
        .as-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 16px 32px;
            background: var(--surface-alt);
            border-bottom: 1px solid var(--border);
        }
        .as-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }
        .as-stat-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .as-stat-dot.blue { background: var(--accent); }
        .as-stat-dot.green { background: #22c55e; }
        .as-stat-dot.amber { background: #f59e0b; }
        .as-stat-dot.red { background: #ef4444; }

        /* ── Table ── */
        .as-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table.as-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 700px;
        }
        table.as-table thead th {
            background: #f1f5f9;
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 2px solid var(--border-strong);
            white-space: nowrap;
        }
        table.as-table tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            color: var(--text);
        }
        table.as-table tbody tr:nth-child(even) td {
            background: var(--surface-alt);
        }
        table.as-table tbody tr:last-child td {
            border-bottom: none;
        }
        .as-name { font-weight: 700; }
        .as-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .as-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .as-badge.success { background: var(--success-bg); color: var(--success-text); }
        .as-badge.warn { background: var(--warn-bg); color: var(--warn-text); }
        .as-time { font-variant-numeric: tabular-nums; font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12px; }
        .as-empty {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }
        .as-empty i { font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.4; }
        .as-empty p { font-size: 15px; margin: 0; }

        /* ── Footer / Signatures ── */
        .as-footer {
            padding: 24px 32px 32px;
            border-top: 2px solid var(--border-strong);
            background: var(--surface);
        }
        .as-footer-note {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px dashed var(--border);
        }
        .as-sig-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }
        .as-sig-block { display: flex; flex-direction: column; gap: 6px; }
        .as-sig-line {
            border-bottom: 1px solid var(--text);
            height: 48px;
            margin-bottom: 6px;
        }
        .as-sig-label { font-size: 13px; font-weight: 700; color: var(--text); }
        .as-sig-sublabel { font-size: 11px; color: var(--text-muted); }

        /* ── Sidebar layout ── */
        .as-layout {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        .as-sidebar {
            width: 280px;
            flex-shrink: 0;
            background: #0f172a;
            border-right: 1px solid rgba(255,255,255,0.08);
            padding: 16px 0;
            overflow-y: auto;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .as-sidebar-title {
            padding: 0 16px 12px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .as-sidebar-list { display: flex; flex-direction: column; gap: 2px; }
        .as-sidebar-item {
            display: block;
            padding: 12px 16px;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .as-sidebar-item:hover {
            background: rgba(255,255,255,0.06);
            border-left-color: rgba(255,255,255,0.2);
        }
        .as-sidebar-item.active {
            background: rgba(13,148,136,0.12);
            border-left-color: var(--sc-accent);
        }
        .as-sidebar-item .asi-title {
            font-size: 14px;
            font-weight: 700;
            color: #e2e8f0;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .as-sidebar-item .asi-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 12px;
            color: #64748b;
        }
        .as-sidebar-item .asi-meta i { font-size: 13px; }
        .as-sidebar-item .asi-count {
            margin-left: auto;
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
            background: rgba(255,255,255,0.06);
            padding: 2px 8px;
            border-radius: 100px;
        }
        .as-sidebar-item.active .asi-count {
            background: rgba(13,148,136,0.2);
            color: #5eead4;
        }
        .as-sidebar-item .asi-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .as-sidebar-item .asi-dot.scheduled { background: #3b82f6; }
        .as-sidebar-item .asi-dot.ongoing { background: #22c55e; }
        .as-sidebar-item .asi-dot.completed { background: #64748b; }
        .as-sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: #64748b;
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
        }
        .as-sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 199;
            background: rgba(0,0,0,0.5);
        }
        .as-content {
            flex: 1;
            min-width: 0;
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .as-sidebar {
                position: fixed;
                left: -300px;
                top: 0;
                z-index: 200;
                height: 100vh;
                transition: left 0.3s ease;
            }
            .as-sidebar.open { left: 0; }
            .as-sidebar-overlay.show { display: block; }
            .as-sidebar-toggle { display: inline-flex; align-items: center; }
        }
        @media (max-width: 768px) {
            .as-header { padding: 20px 20px 18px; }
            .as-meeting-title { font-size: 20px; }
            .as-meta-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .as-stats { padding: 12px 20px; }
            .as-footer { padding: 20px 20px 28px; }
            .as-sig-grid { grid-template-columns: 1fr; gap: 16px; }
        }

        /* ── Print styles ── */
        @media print {
            @page {
                size: A4;
                margin: 14mm 14mm 18mm 14mm;
            }
            body {
                background: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .as-toolbar { display: none !important; }
            .as-sheet-wrap { max-width: 100%; padding: 0; }
            .as-sheet {
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
            }
            .as-header {
                background: #1a56db !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 18px 24px 16px !important;
            }
            .as-header::before { display: none; }
            .as-stats {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 10px 24px !important;
            }
            table.as-table { font-size: 11px; }
            table.as-table thead th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 8px 10px !important;
                font-size: 10px;
            }
            table.as-table tbody td { padding: 7px 10px !important; }
            .as-footer { padding: 18px 24px 24px !important; }
            .as-sig-line { border-bottom: 1px solid #000 !important; }
            .as-sig-label { color: #000 !important; }
            .as-sig-sublabel { color: #444 !important; }
            .as-footer-note { color: #555 !important; }
            .as-time { font-size: 11px; }
            .as-badge {
                border: 1px solid currentColor;
            }
            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>

    <!-- ── Toolbar (not printed) ── -->
    <div class="as-toolbar no-print">
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="as-sidebar-toggle" id="sidebarToggle" title="Browse events"><i class="bi bi-list"></i></button>
            <div class="as-toolbar-title"><i class="bi bi-file-earmark-text"></i> Attendance Sheet</div>
        </div>
        <div class="as-toolbar-actions">
            <?php $backUrl = ($_SESSION['role'] ?? '') === 'secretary' ? e($base . '/secretary/dashboard.php') : e($base . '/admin/dashboard.php'); ?>
            <a href="<?php echo $backUrl; ?>" class="as-btn as-btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
            <a href="<?php echo e($base . '/scanner/index.php'); ?>" class="as-btn as-btn-ghost"><i class="bi bi-upc-scan"></i> Scanner</a>
            <button class="as-btn as-btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print / Save as PDF</button>
        </div>
    </div>

    <div class="as-layout">
        <div class="as-sidebar-overlay" id="sidebarOverlay"></div>
        <nav class="as-sidebar" id="sidebar">
            <div class="as-sidebar-title">Events</div>
            <div class="as-sidebar-list">
                <?php foreach ($allEvents as $ev): ?>
                <?php $isActive = (int) $ev['id'] === $eventId; ?>
                <a class="as-sidebar-item<?php echo $isActive ? ' active' : ''; ?>" href="?event_id=<?php echo (int) $ev['id']; ?>">
                    <div class="asi-title"><?php echo e($ev['title']); ?></div>
                    <div class="asi-meta">
                        <span class="asi-dot <?php echo e($ev['status'] ?? 'completed'); ?>"></span>
                        <span><?php echo $ev['agenda_date'] ? e($ev['agenda_date']) : '—'; ?></span>
                        <?php if ((int) $ev['checkin_count'] > 0): ?>
                        <span class="asi-count"><?php echo (int) $ev['checkin_count']; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (empty($allEvents)): ?>
            <div style="padding:20px 16px;text-align:center;color:#64748b;font-size:14px;">No events available.</div>
            <?php endif; ?>
        </nav>

        <div class="as-content">
            <div class="as-sheet-wrap">
        <div class="as-sheet">

            <!-- ── Header ── -->
            <div class="as-header">
                <div class="as-header-content">
                    <div class="as-doc-title"><i class="bi bi-shield-check"></i> Official Attendance Record</div>
                    <h1 class="as-meeting-title"><?php echo $event ? e($event['title']) : 'Select an Event'; ?></h1>
                    <div class="as-meta-grid">
                        <?php if ($event): ?>
                            <div class="as-meta-item">
                                <span class="as-meta-label">Date</span>
                                <span class="as-meta-value"><?php echo formatDate($event['agenda_date']); ?></span>
                            </div>
                            <div class="as-meta-item">
                                <span class="as-meta-label">Time</span>
                                <span class="as-meta-value"><?php echo formatTime($event['time_from']); ?> – <?php echo formatTime($event['time_to']); ?></span>
                            </div>
                            <div class="as-meta-item">
                                <span class="as-meta-label">Location</span>
                                <span class="as-meta-value"><?php echo $event['location'] ? e($event['location']) : '—'; ?></span>
                            </div>
                            <div class="as-meta-item">
                                <span class="as-meta-label">Meeting Type</span>
                                <span class="as-meta-value" style="text-transform:capitalize;"><?php echo e($event['meeting_type'] ?? $event['event_type'] ?? '—'); ?></span>
                            </div>
                            <div class="as-meta-item">
                                <span class="as-meta-label">Document ID</span>
                                <span class="as-meta-value mono"><?php echo e($docId); ?></span>
                            </div>
                            <div class="as-meta-item">
                                <span class="as-meta-label">Generated</span>
                                <span class="as-meta-value" style="font-size:13px;"><?php echo $generatedAt; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="as-meta-item">
                                <span class="as-meta-label">Status</span>
                                <span class="as-meta-value">No event selected</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Stats ── -->
            <?php if ($event): ?>
                <div class="as-stats">
                    <div class="as-stat"><span class="as-stat-dot blue"></span> Total Scans: <?php echo $stats['total_scans']; ?></div>
                    <div class="as-stat"><span class="as-stat-dot green"></span> Verified Attendees: <?php echo $stats['unique_attendees']; ?></div>
                    <div class="as-stat"><span class="as-stat-dot amber"></span> Inactive / Expired: <?php echo $stats['inactive_count']; ?></div>
                    <div class="as-stat"><span class="as-stat-dot red"></span> Not Found / Errors: <?php echo $stats['not_found_count'] + $stats['error_count']; ?></div>
                </div>
            <?php endif; ?>

            <!-- ── Table ── -->
            <div class="as-table-wrap">
                <table class="as-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Full Name</th>
                            <th>Senior Citizen ID</th>
                            <th>Sex</th>
                            <th>Address</th>
                            <th>Contact Number</th>
                            <th>Date of Birth</th>
                            <th>Time In</th>
                            <th>Scanned By</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$event): ?>
                            <tr><td colspan="10" class="as-empty"><i class="bi bi-calendar-x"></i><p>No event selected. Go back and choose an event to generate the attendance sheet.</p></td></tr>
                        <?php elseif (empty($attendees)): ?>
                            <tr><td colspan="10" class="as-empty"><i class="bi bi-person-check"></i><p>No verified attendees recorded for this event yet.</p></td></tr>
                        <?php else: ?>
                            <?php foreach ($attendees as $i => $r): ?>
                                <tr>
                                    <td style="text-align:center;font-weight:700;color:var(--text-muted);"><?php echo $i + 1; ?></td>
                                    <td>
                                        <div class="as-name"><?php echo e($r['full_name'] ?? '—'); ?></div>
                                        <?php if ($r['civil_status'] || $r['occupation']): ?>
                                            <div class="as-sub"><?php echo e(trim(($r['civil_status'] ?? '') . ($r['civil_status'] && $r['occupation'] ? ' · ' : '') . ($r['occupation'] ?? ''))); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-variant-numeric:tabular-nums;font-family:'SF Mono','Fira Code',monospace;font-size:12px;"><?php echo e($r['senior_citizen_id'] ?? '—'); ?></td>
                                    <td><?php echo e($r['sex'] ?? '—'); ?></td>
                                    <td style="max-width:200px;"><?php echo e($r['address'] ?? '—'); ?></td>
                                    <td style="font-variant-numeric:tabular-nums;"><?php echo e($r['contact_number'] ?? '—'); ?></td>
                                    <td style="font-variant-numeric:tabular-nums;font-size:12px;"><?php echo e($r['birth_date'] ?? '—'); ?></td>
                                    <td class="as-time"><?php echo e(date('M d, Y g:i A', strtotime($r['scanned_at']))); ?></td>
                                    <td><?php echo e($r['scanned_by_name'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($r['remarks']): ?>
                                            <span class="as-badge warn"><?php echo e($r['remarks']); ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ── Footer / Signatures ── -->
            <?php if ($event && !empty($attendees)): ?>
                <div class="as-footer">
                    <div class="as-footer-note">
                        <i class="bi bi-info-circle"></i>
                        Document ID: <strong><?php echo e($docId); ?></strong>
                        &nbsp;|&nbsp; Generated: <?php echo $generatedAt; ?>
                        &nbsp;|&nbsp; Generated by: <?php echo $generatedBy; ?>
                        &nbsp;|&nbsp; Source: trace_db.agenda_scan_logs
                        &nbsp;|&nbsp; Total verified attendees: <strong><?php echo count($attendees); ?></strong>
                    </div>
                    <div class="as-sig-grid">
                        <div class="as-sig-block">
                            <div class="as-sig-line"></div>
                            <div class="as-sig-label">Prepared by</div>
                            <div class="as-sig-sublabel">Barangay Secretary / Encoder</div>
                        </div>
                        <div class="as-sig-block">
                            <div class="as-sig-line"></div>
                            <div class="as-sig-label">Certified by</div>
                            <div class="as-sig-sublabel">Barangay Captain / Chairman</div>
                        </div>
                        <div class="as-sig-block">
                            <div class="as-sig-line"></div>
                            <div class="as-sig-label">Received by</div>
                            <div class="as-sig-sublabel">Official / Facilitator</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            </div>
        </div>
    </div>
    </div>

    <script>
    (function(){
        var toggle = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        if (toggle && sidebar && overlay) {
            function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); }
            toggle.addEventListener('click', function(e) { e.stopPropagation(); sidebar.classList.toggle('open'); overlay.classList.toggle('show'); });
            overlay.addEventListener('click', closeSidebar);
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeSidebar(); });
        }
    })();
    </script>
</body>
</html>
