<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary', 'admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$reportType = trim($_GET['report'] ?? 'requests');

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = $reportType . '_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    switch ($reportType) {
        case 'requests':
            fputcsv($output, ['ID', 'Resident', 'Type', 'Priority', 'Status', 'Remarks', 'Date']);
            $stmt = $pdo->query('SELECT a.*, r.full_name FROM applications a LEFT JOIN residents r ON r.id = a.resident_id ORDER BY a.created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['full_name'], $row['application_type'], $row['priority'], $row['status'], $row['remarks'], $row['created_at']]);
            }
            break;

        case 'documents':
            fputcsv($output, ['ID', 'Resident', 'Document Number', 'Control Number', 'Type', 'Purpose', 'Status', 'Date']);
            $stmt = $pdo->query('SELECT d.*, r.full_name FROM documents d LEFT JOIN residents r ON r.id = d.resident_id ORDER BY d.created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['full_name'], $row['document_number'], $row['control_number'], $row['document_type'], $row['purpose'], $row['status'], $row['created_at']]);
            }
            break;

        case 'residents':
            fputcsv($output, ['ID', 'Full Name', 'Birth Date', 'Sex', 'Address', 'Contact', 'Household', 'Civil Status', 'Occupation', 'Education', 'Created']);
            $stmt = $pdo->query('SELECT * FROM residents ORDER BY full_name');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['full_name'], $row['birth_date'], $row['sex'], $row['address'], $row['contact_number'], $row['household_number'], $row['civil_status'], $row['occupation'], $row['education'], $row['created_at']]);
            }
            break;

        case 'announcements':
            fputcsv($output, ['ID', 'Title', 'Type', 'Priority', 'Audience', 'Created']);
            $stmt = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['title'], $row['type'], $row['priority'], $row['audience'], $row['created_at']]);
            }
            break;
    }

    fclose($output);
    logAudit('export_report', 'Exported ' . $reportType . ' report');
    exit;
}

$reportTypes = [
    'requests'      => ['label' => 'Applications',  'icon' => 'bi-file-earmark-text'],
    'documents'     => ['label' => 'Documents',      'icon' => 'bi-folder2-open'],
    'residents'     => ['label' => 'Residents',      'icon' => 'bi-people'],
    'announcements' => ['label' => 'Announcements',  'icon' => 'bi-megaphone'],
];

$currentReport = in_array($reportType, array_keys($reportTypes)) ? $reportType : 'requests';

$rows = [];
switch ($currentReport) {
    case 'requests':
        $stmt = $pdo->query('SELECT a.*, r.full_name FROM applications a LEFT JOIN residents r ON r.id = a.resident_id ORDER BY a.created_at DESC');
        $rows = $stmt->fetchAll();
        break;
    case 'documents':
        $stmt = $pdo->query('SELECT d.*, r.full_name FROM documents d LEFT JOIN residents r ON r.id = d.resident_id ORDER BY d.created_at DESC');
        $rows = $stmt->fetchAll();
        break;
    case 'residents':
        $stmt = $pdo->query('SELECT * FROM residents ORDER BY full_name');
        $rows = $stmt->fetchAll();
        break;
    case 'announcements':
        $stmt = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();
        break;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
/* ═══════════════════════════════
   PROPERTIES
   ═══════════════════════════════ */
:root {
    --rp-accent: #10b981;
    --rp-accent-dark: #059669;
    --rp-rose: #f43f5e;
    --rp-rose-dark: #e11d48;
    --rp-sky: #0ea5e9;
    --rp-amber: #f59e0b;
    --rp-violet: #8b5cf6;
    --rp-teal: #14b8a6;
    --rp-red: #ef4444;
    --rp-bg: #0f172a;
    --rp-card: rgba(255,255,255,0.03);
    --rp-text: #f0f4f8;
    --rp-text-sec: #94a3b8;
    --rp-text-muted: #64748b;
    --rp-text-dim: #475569;
    --rp-border: rgba(255,255,255,0.08);
    --rp-border-lt: rgba(255,255,255,0.12);
    --rp-rad: 12px;
    --rp-rad-lg: 16px;
    --rp-rad-xl: 20px;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--rp-bg) !important;
    color: var(--rp-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.rp-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.rp-page::after {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

.rp-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: rpFloat 22s ease-in-out infinite;
}
.rp-orb.o1 { width: 440px; height: 440px; background: rgba(244,63,94,0.06); top: -12%; left: -8%; }
.rp-orb.o2 { width: 320px; height: 320px; background: rgba(16,185,129,0.06); bottom: -10%; right: -6%; animation-delay: -11s; }
.rp-orb.o3 { width: 240px; height: 240px; background: rgba(14,165,233,0.05); top: 50%; right: 25%; animation-delay: -5s; animation-duration: 26s; }

@keyframes rpFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.rp-page { min-height: 100vh; position: relative; z-index: 1; }

.rp-layout { display: flex; min-height: 100vh; }

.rp-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--rp-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.rp-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.rp-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.rp-head-left { flex: 1; min-width: 260px; }

.rp-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(244,63,94,0.12);
    border: 1px solid rgba(244,63,94,0.25);
    border-radius: 100px;
    color: #fda4af;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
    width: fit-content;
}

.rp-badge .rp-dot {
    width: 7px; height: 7px;
    background: var(--rp-rose);
    border-radius: 50%;
    animation: rpPulse 2s ease-in-out infinite;
}

@keyframes rpPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.rp-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.rp-title span {
    background: linear-gradient(135deg, var(--rp-rose), #fb7185);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.rp-desc {
    font-size: 0.92rem;
    color: var(--rp-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* Record count */
.rp-count-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(244,63,94,0.10);
    border: 1px solid rgba(244,63,94,0.25);
    border-radius: 100px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #fda4af;
    white-space: nowrap;
}
.rp-count-pill i { font-size: 0.9rem; }

/* ═══════════════════════════════
   REPORT TYPE TABS
   ═══════════════════════════════ */
.rp-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.rp-tab {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--rp-text-sec);
    text-decoration: none;
    transition: all 0.25s ease;
    white-space: nowrap;
}

.rp-tab:hover {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.14);
    color: #e2e8f0;
}

.rp-tab.active {
    background: rgba(244,63,94,0.10);
    border-color: rgba(244,63,94,0.30);
    color: #fda4af;
}

.rp-tab i {
    font-size: 0.92rem;
    color: var(--rp-text-dim);
    transition: color 0.2s ease;
}

.rp-tab:hover i { color: var(--rp-text-sec); }
.rp-tab.active i { color: var(--rp-rose); }

/* ═══════════════════════════════
   GLASS CARD
   ═══════════════════════════════ */
.rp-card {
    background: var(--rp-card);
    border: 1px solid var(--rp-border);
    border-radius: var(--rp-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.rp-card:hover { border-color: rgba(255,255,255,0.12); }

.rp-card-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    gap: 16px;
    flex-wrap: wrap;
}

.rp-card-hd-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.rp-card-ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.rp-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.rp-card-st {
    font-size: 0.82rem;
    color: var(--rp-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.rp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: var(--rp-rad);
    font-size: 0.84rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}
.rp-btn i { transition: transform 0.2s ease; }

.rp-btn-rose {
    background: linear-gradient(135deg, var(--rp-rose), var(--rp-rose-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(244,63,94,0.25);
}
.rp-btn-rose:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(244,63,94,0.35);
    color: #ffffff;
}
.rp-btn-rose:active { transform: translateY(0); }
.rp-btn-rose:hover i { transform: translateY(2px); }

/* ═══════════════════════════════
   TABLE
   ═══════════════════════════════ */
.rp-table-wrap {
    overflow-x: auto;
    border-radius: var(--rp-rad-lg);
    border: 1px solid rgba(255,255,255,0.04);
}

.rp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.84rem;
}

.rp-table thead th {
    padding: 14px 16px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: var(--rp-text-sec);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    white-space: nowrap;
}

.rp-table tbody td {
    padding: 13px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    color: #e2e8f0;
    vertical-align: middle;
}

.rp-table tbody tr:last-child td { border-bottom: none; }
.rp-table tbody tr { transition: background 0.2s ease; }
.rp-table tbody tr:hover { background: rgba(255,255,255,0.03); }

.rp-table .rp-cell-id {
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--rp-text-dim);
    font-variant-numeric: tabular-nums;
}

.rp-table .rp-cell-bold {
    font-weight: 700;
    color: #f1f5f9;
}

.rp-table .rp-cell-text {
    color: var(--rp-text-sec);
}

.rp-table .rp-cell-muted {
    color: var(--rp-text-dim);
    font-size: 0.8rem;
}

.rp-table .rp-cell-date {
    font-size: 0.8rem;
    color: var(--rp-text-dim);
    white-space: nowrap;
}

/* Status badges */
.rp-st {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.rp-st-urgent {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.rp-st-high {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.rp-st-normal {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}
.rp-st-issued {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.rp-st-default {
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}

/* Empty */
.rp-empty {
    text-align: center;
    padding: 48px 20px;
}
.rp-empty-ico {
    width: 60px; height: 60px;
    border-radius: 17px;
    background: rgba(100,116,139,0.08);
    border: 1px solid rgba(100,116,139,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--rp-text-dim);
    margin: 0 auto 14px;
}
.rp-empty-txt { font-size: 0.9rem; color: var(--rp-text-muted); margin: 0; }

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.rp-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.rp-reveal.rp-vis { opacity: 1; transform: translateY(0); }

.rp-d1 { transition-delay: 0.05s; }
.rp-d2 { transition-delay: 0.1s; }
.rp-d3 { transition-delay: 0.15s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .rp-main { padding: 32px 36px; }
}
@media (max-width: 991.98px) {
    .rp-layout { flex-direction: column; }
    .rp-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--rp-border);
    }
    .rp-main { padding: 28px 24px; }
    .rp-head { flex-direction: column; gap: 16px; }
}
@media (max-width: 767.98px) {
    .rp-main { padding: 24px 16px; }
    .rp-card { padding: 24px 20px; }
    .rp-title { font-size: 1.4rem; }
    .rp-card-hd { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 575.98px) {
    .rp-main { padding: 20px 14px; }
    .rp-card { padding: 20px 16px; border-radius: 16px; }
    .rp-tabs { gap: 4px; }
    .rp-tab { padding: 8px 14px; font-size: 0.78rem; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="rp-page">
    <div class="rp-orb o1"></div>
    <div class="rp-orb o2"></div>
    <div class="rp-orb o3"></div>

    <div class="rp-layout">
        <!-- Sidebar -->
        <div class="rp-sidebar">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <div class="rp-main">

            <!-- Header -->
            <div class="rp-head rp-reveal rp-d1">
                <div class="rp-head-left">
                    <div class="rp-badge">
                        <span class="rp-dot"></span>
                        Analytics
                    </div>
                    <h1 class="rp-title">Barangay <span>Reports</span></h1>
                    <p class="rp-desc">Generate and export detailed reports for applications, documents, residents, and announcements.</p>
                </div>
                <div class="rp-count-pill rp-reveal rp-d2">
                    <i class="bi bi-database-fill"></i>
                    <?php echo count($rows); ?> Record<?php echo count($rows) !== 1 ? 's' : ''; ?>
                </div>
            </div>

            <!-- Report Type Tabs -->
            <div class="rp-tabs rp-reveal rp-d2">
                <?php foreach ($reportTypes as $key => $meta): ?>
                    <a href="?report=<?php echo e($key); ?>" class="rp-tab <?php echo $currentReport === $key ? 'active' : ''; ?>">
                        <i class="bi <?php echo e($meta['icon']); ?>"></i>
                        <?php echo e($meta['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Report Card -->
            <div class="rp-card rp-reveal rp-d3">
                <div class="rp-card-hd">
                    <div class="rp-card-hd-left">
                        <?php
                            $cardIcons = [
                                'requests'      => ['icon' => 'bi-file-earmark-text', 'bg' => 'rgba(14,165,233,0.12)', 'color' => '#0ea5e9'],
                                'documents'     => ['icon' => 'bi-folder2-open',      'bg' => 'rgba(16,185,129,0.12)', 'color' => '#10b981'],
                                'residents'     => ['icon' => 'bi-people',            'bg' => 'rgba(139,92,246,0.12)', 'color' => '#8b5cf6'],
                                'announcements' => ['icon' => 'bi-megaphone',         'bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b'],
                            ];
                            $ci = $cardIcons[$currentReport] ?? $cardIcons['requests'];
                        ?>
                        <div class="rp-card-ico" style="background:<?php echo $ci['bg']; ?>; color:<?php echo $ci['color']; ?>;">
                            <i class="bi <?php echo $ci['icon']; ?>"></i>
                        </div>
                        <div>
                            <h5 class="rp-card-tt"><?php echo e($reportTypes[$currentReport]['label']); ?></h5>
                            <p class="rp-card-st"><?php echo count($rows); ?> total record<?php echo count($rows) !== 1 ? 's' : ''; ?> in this report.</p>
                        </div>
                    </div>
                    <a href="?report=<?php echo e($currentReport); ?>&export=1" class="rp-btn rp-btn-rose">
                        <i class="bi bi-download"></i>
                        <span>Export CSV</span>
                    </a>
                </div>

                <?php if (!empty($rows)): ?>
                    <div class="rp-table-wrap">
                        <table class="rp-table">
                            <thead>
                                <tr>
                                    <?php if ($currentReport === 'requests'): ?>
                                        <th>ID</th>
                                        <th>Resident</th>
                                        <th>Type</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Date</th>
                                    <?php elseif ($currentReport === 'documents'): ?>
                                        <th>ID</th>
                                        <th>Resident</th>
                                        <th>Document #</th>
                                        <th>Control #</th>
                                        <th>Type</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    <?php elseif ($currentReport === 'residents'): ?>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Sex</th>
                                        <th>Address</th>
                                        <th>Contact</th>
                                        <th>Created</th>
                                    <?php elseif ($currentReport === 'announcements'): ?>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Priority</th>
                                        <th>Audience</th>
                                        <th>Date</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($currentReport === 'requests'): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><span class="rp-cell-id">#<?php echo (int) $row['id']; ?></span></td>
                                            <td><span class="rp-cell-bold"><?php echo e($row['full_name'] ?? 'Unknown'); ?></span></td>
                                            <td><span class="rp-cell-text"><?php echo e($row['application_type']); ?></span></td>
                                            <td>
                                                <?php
                                                    $prClass = match($row['priority'] ?? 'normal') {
                                                        'urgent' => 'rp-st-urgent',
                                                        'high'   => 'rp-st-high',
                                                        default  => 'rp-st-normal'
                                                    };
                                                    $prIcon = match($row['priority'] ?? 'normal') {
                                                        'urgent' => 'bi-exclamation-triangle-fill',
                                                        'high'   => 'bi-chevron-double-up',
                                                        default  => 'bi-dash'
                                                    };
                                                ?>
                                                <span class="rp-st <?php echo $prClass; ?>">
                                                    <i class="bi <?php echo $prIcon; ?>"></i>
                                                    <?php echo e($row['priority'] ?? 'normal'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="rp-st rp-st-default">
                                                    <?php echo e(str_replace('_', ' ', $row['status'])); ?>
                                                </span>
                                            </td>
                                            <td><span class="rp-cell-muted"><?php echo e($row['remarks'] ?? '-'); ?></span></td>
                                            <td><span class="rp-cell-date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php elseif ($currentReport === 'documents'): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><span class="rp-cell-id">#<?php echo (int) $row['id']; ?></span></td>
                                            <td><span class="rp-cell-bold"><?php echo e($row['full_name'] ?? 'Unknown'); ?></span></td>
                                            <td><span class="rp-cell-bold" style="font-variant-numeric:tabular-nums; font-size:0.82rem;"><?php echo e($row['document_number']); ?></span></td>
                                            <td><span class="rp-cell-muted" style="font-variant-numeric:tabular-nums;"><?php echo e($row['control_number']); ?></span></td>
                                            <td><span class="rp-cell-text"><?php echo e($row['document_type']); ?></span></td>
                                            <td><span class="rp-cell-muted"><?php echo e($row['purpose'] ?? '-'); ?></span></td>
                                            <td>
                                                <span class="rp-st rp-st-issued">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <?php echo e($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><span class="rp-cell-date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php elseif ($currentReport === 'residents'): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><span class="rp-cell-id">#<?php echo (int) $row['id']; ?></span></td>
                                            <td><span class="rp-cell-bold"><?php echo e($row['full_name']); ?></span></td>
                                            <td><span class="rp-cell-text"><?php echo e($row['sex'] ?? '-'); ?></span></td>
                                            <td><span class="rp-cell-muted"><?php echo e($row['address'] ?? '-'); ?></span></td>
                                            <td><span class="rp-cell-text"><?php echo e($row['contact_number'] ?? '-'); ?></span></td>
                                            <td><span class="rp-cell-date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php elseif ($currentReport === 'announcements'): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><span class="rp-cell-id">#<?php echo (int) $row['id']; ?></span></td>
                                            <td><span class="rp-cell-bold"><?php echo e($row['title']); ?></span></td>
                                            <td><span class="rp-cell-text"><?php echo e($row['type'] ?? '-'); ?></span></td>
                                            <td>
                                                <?php
                                                    $aqClass = match($row['priority'] ?? 'normal') {
                                                        'urgent' => 'rp-st-urgent',
                                                        'high'   => 'rp-st-high',
                                                        default  => 'rp-st-normal'
                                                    };
                                                ?>
                                                <span class="rp-st <?php echo $aqClass; ?>">
                                                    <?php echo e($row['priority'] ?? 'normal'); ?>
                                                </span>
                                            </td>
                                            <td><span class="rp-cell-text"><?php echo e($row['audience'] ?? '-'); ?></span></td>
                                            <td><span class="rp-cell-date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="rp-empty">
                        <div class="rp-empty-ico"><i class="bi bi-inbox"></i></div>
                        <p class="rp-empty-txt">No records found for this report type.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.rp-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('rp-vis');
        });
    }, 60);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>