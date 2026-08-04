<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary', 'admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$agendaId = isset($_GET['agenda_id']) ? (int) $_GET['agenda_id'] : 0;
if ($agendaId <= 0) {
    header('Location: ' . BASE_URL . '/secretary/agenda.php');
    exit;
}

$agenda = null;
try {
    $stmt = $pdo->prepare('SELECT * FROM agenda WHERE id = ? LIMIT 1');
    $stmt->execute([$agendaId]);
    $agenda = $stmt->fetch();
} catch (Throwable $e) {
    $agenda = null;
}

if (!$agenda) {
    header('Location: ' . BASE_URL . '/secretary/agenda.php');
    exit;
}

$action = $_GET['action'] ?? '';
$isExport = $action === 'export_csv';

$where = ['sl.agenda_id = ?'];
$params = [$agendaId];

if (!empty($_GET['result'])) {
    $allowed = ['success', 'not_found', 'inactive', 'expired', 'error'];
    $result = strtolower(trim($_GET['result']));
    if (in_array($result, $allowed, true)) {
        $where[] = 'sl.scan_result = ?';
        $params[] = $result;
    }
}

if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $where[] = '(r.full_name LIKE ? OR sl.qr_code_scanned LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

if ($isExport) {
    $stmt = $pdo->prepare(
        "SELECT sl.scanned_at, r.full_name, sl.qr_code_scanned, sl.scan_result, sl.scanned_by_name, sl.remarks
         FROM agenda_scan_logs sl
         LEFT JOIN residents r ON r.id = sl.resident_id
         $whereSql
         ORDER BY sl.scanned_at ASC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="event_attendance_' . $agendaId . '_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date/Time', 'Resident Name', 'QR Code', 'Result', 'Scanned By', 'Remarks']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['scanned_at'],
            $r['full_name'] ?? '—',
            $r['qr_code_scanned'],
            strtoupper($r['scan_result']),
            $r['scanned_by_name'] ?? '',
            $r['remarks'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM agenda_scan_logs sl
     LEFT JOIN residents r ON r.id = sl.resident_id
     $whereSql"
);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$dataStmt = $pdo->prepare(
    "SELECT sl.*, r.full_name AS resident_name
     FROM agenda_scan_logs sl
     LEFT JOIN residents r ON r.id = sl.resident_id
     $whereSql
     ORDER BY sl.scanned_at DESC
     LIMIT $perPage OFFSET $offset"
);
$dataStmt->execute($params);
$rows = $dataStmt->fetchAll();

$totalPages = (int) ceil($total / $perPage);

$summaryStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN scan_result = 'success' THEN 1 ELSE 0 END) AS success,
        SUM(CASE WHEN scan_result = 'not_found' THEN 1 ELSE 0 END) AS not_found,
        SUM(CASE WHEN scan_result IN ('inactive','expired') THEN 1 ELSE 0 END) AS inactive,
        SUM(CASE WHEN scan_result = 'error' THEN 1 ELSE 0 END) AS error
     FROM agenda_scan_logs
     WHERE agenda_id = ?"
);
$summaryStmt->execute([$agendaId]);
$summary = $summaryStmt->fetch();

$base = BASE_URL;
$calendarUrl = $base . '/secretary/agenda.php';
$scannerUrl = $base . '/scanner/index.php';

function qs(array $overrides): string {
    global $agendaId;
    $p = ['agenda_id' => $agendaId];
    foreach ($_GET as $k => $v) { $p[$k] = $v; }
    foreach ($overrides as $k => $v) { $p[$k] = $v; }
    return '?' . http_build_query(array_filter($p, function ($v) { return $v !== '' && $v !== null; }));
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
:root {
    --at-bg: #0f172a;
    --at-card: rgba(255,255,255,0.035);
    --at-border: rgba(255,255,255,0.07);
    --at-text: #f0f4f8;
    --at-text-secondary: #94a3b8;
    --at-muted: #475569;
    --at-accent: #10b981;
    --at-accent-dark: #059669;
    --at-radius: 12px;
    --at-radius-lg: 20px;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--at-bg) !important;
    color: var(--at-text);
    overflow-x: hidden;
}

.navbar, footer, .main-navbar { display: none !important; }

.at-page {
    min-height: 100vh;
    display: flex;
    position: relative;
    overflow: hidden;
    background: var(--at-bg);
}

.at-grid {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

.at-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(90px);
    pointer-events: none;
    z-index: 0;
    animation: atFloat 22s ease-in-out infinite;
}
.at-orb.o1 { width: 450px; height: 450px; background: rgba(16,185,129,0.06); top: -12%; left: -6%; }
.at-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -15%; right: -8%; animation-delay: -12s; }

@keyframes atFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(20px, -12px) scale(1.04); }
    66% { transform: translate(-12px, 8px) scale(0.96); }
}

.at-main {
    flex: 1;
    padding: 40px 48px;
    position: relative;
    z-index: 5;
    overflow-y: auto;
    min-height: 100vh;
    max-width: 1100px;
    margin: 0 auto;
}

.at-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 28px;
}

.at-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 900;
    color: #ffffff;
    line-height: 1.15;
}

.at-header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.at-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--at-radius);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    white-space: nowrap;
}

.at-btn-primary {
    background: linear-gradient(135deg, var(--at-accent), var(--at-accent-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
}

.at-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.35);
    color: #ffffff;
}

.at-btn-ghost {
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--at-border);
    color: var(--at-text-secondary);
}

.at-btn-ghost:hover {
    background: rgba(255,255,255,0.08);
    color: #e2e8f0;
    border-color: rgba(255,255,255,0.15);
}

.at-card {
    background: var(--at-card);
    border: 1px solid var(--at-border);
    border-radius: var(--at-radius-lg);
    backdrop-filter: blur(30px);
    padding: 24px;
    margin-bottom: 24px;
}

.at-event-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.at-event-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.at-event-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
}

.at-event-meta {
    font-size: 0.85rem;
    color: var(--at-text-secondary);
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.at-event-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.at-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.at-sum-card {
    flex: 1 1 130px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 16px;
    text-align: center;
}

.at-sum-num { font-size: 30px; font-weight: 800; color: #fff; line-height: 1; }
.at-sum-lbl { font-size: 14px; color: var(--at-text-secondary); margin-top: 6px; }
.at-sum-card.ok .at-sum-num { color: #4ade80; }
.at-sum-card.bad .at-sum-num { color: #f87171; }
.at-sum-card.warn .at-sum-num { color: #fbbf24; }

.at-filters {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 18px;
}

.at-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.at-field label {
    display: block;
    font-size: 14px;
    color: #cbd5e1;
    margin-bottom: 6px;
    font-weight: 600;
}

.at-input, .at-select {
    width: 100%;
    min-height: 48px;
    padding: 10px 14px;
    font-size: 16px;
    border-radius: 10px;
    border: 1px solid var(--at-border);
    background: #fff;
    color: var(--at-text);
    font-family: var(--at-font, 'Inter', sans-serif);
}

.at-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
}

.at-table-wrap {
    overflow-x: auto;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
}

table.at-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
    background: #fff;
    color: var(--at-text);
}

table.at-table th, table.at-table td {
    padding: 14px 16px;
    text-align: left;
    font-size: 15px;
    border-bottom: 1px solid var(--at-border);
}

table.at-table th {
    background: #f1f5f9;
    font-weight: 700;
    position: sticky;
    top: 0;
}

table.at-table tr:nth-child(even) td { background: #f8fafc; }

.at-tag {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 700;
}

.at-tag.success { background: #dcfce7; color: #166534; }
.at-tag.not_found, .at-tag.error { background: #fee2e2; color: #991b1b; }
.at-tag.inactive, .at-tag.expired { background: #ffedd5; color: #9a3412; }

.at-pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 18px;
    flex-wrap: wrap;
}

.at-pager a, .at-pager span {
    min-width: 44px; height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 14px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.14);
    background: rgba(255,255,255,0.06);
    color: #e2e8f0;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
}

.at-pager a:hover { background: rgba(255,255,255,0.14); color: #fff; }
.at-pager .current { background: var(--at-accent); border-color: var(--at-accent); color: #fff; }

.at-empty {
    text-align: center;
    padding: 40px;
    color: var(--at-text-secondary);
    font-size: 17px;
}

@media print {
    body { background: #fff; }
    .at-header, .at-filters, .at-actions, .at-pager, .no-print { display: none !important; }
    .at-main { max-width: 100%; padding: 0; }
    table.at-table { color: #000; }
    table.at-table th { background: #eee; }
    .at-sum-card { background: #fff; border: 1px solid #ccc; }
    .at-sum-num { color: #000 !important; }
    .at-sum-lbl { color: #333 !important; }
}

@media (max-width: 991.98px) {
    .at-main { padding: 28px 24px; }
}

@media (max-width: 767.98px) {
    .at-main { padding: 24px 16px; }
}
</style>

<div class="at-page">
    <div class="at-grid"></div>
    <div class="at-orb o1"></div>
    <div class="at-orb o2"></div>

    <main class="at-main">
        <div class="at-header no-print">
            <h1 class="at-title">Event Attendance</h1>
            <div class="at-header-actions">
                <a href="<?php echo e($calendarUrl); ?>" class="at-btn at-btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back to Agenda
                </a>
                <a href="<?php echo e(qs(['action' => 'export_csv'])); ?>" class="at-btn at-btn-primary">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="at-card no-print">
            <div class="at-event-header">
                <div class="at-event-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div class="at-event-title"><?php echo e($agenda['title']); ?></div>
                    <div class="at-event-meta">
                        <?php if ($agenda['agenda_date']): ?>
                            <span><i class="bi bi-calendar3"></i> <?php echo date('l, M d, Y', strtotime($agenda['agenda_date'])); ?></span>
                        <?php endif; ?>
                        <?php if ($agenda['time_from']): ?>
                            <span><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($agenda['time_from'])); ?><?php echo $agenda['time_to'] ? ' - ' . date('g:i A', strtotime($agenda['time_to'])) : ''; ?></span>
                        <?php endif; ?>
                        <?php if ($agenda['location']): ?>
                            <span><i class="bi bi-geo-alt"></i> <?php echo e($agenda['location']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="at-summary">
                <div class="at-sum-card">
                    <div class="at-sum-num"><?php echo (int) ($summary['total'] ?? 0); ?></div>
                    <div class="at-sum-lbl">Total Scans</div>
                </div>
                <div class="at-sum-card ok">
                    <div class="at-sum-num"><?php echo (int) ($summary['success'] ?? 0); ?></div>
                    <div class="at-sum-lbl">Verified</div>
                </div>
                <div class="at-sum-card bad">
                    <div class="at-sum-num"><?php echo (int) ($summary['not_found'] ?? 0); ?></div>
                    <div class="at-sum-lbl">Not Found</div>
                </div>
                <div class="at-sum-card warn">
                    <div class="at-sum-num"><?php echo (int) ($summary['inactive'] ?? 0); ?></div>
                    <div class="at-sum-lbl">Inactive / Expired</div>
                </div>
                <div class="at-sum-card">
                    <div class="at-sum-num"><?php echo (int) ($summary['error'] ?? 0); ?></div>
                    <div class="at-sum-lbl">Errors</div>
                </div>
            </div>

            <form class="at-filters" method="get">
                <input type="hidden" name="agenda_id" value="<?php echo (int) $agendaId; ?>">
                <div class="at-filter-grid">
                    <div class="at-field">
                        <label for="fSearch">Search</label>
                        <input type="text" id="fSearch" name="search" class="at-input" value="<?php echo e($_GET['search'] ?? ''); ?>" placeholder="Name or QR code">
                    </div>
                    <div class="at-field">
                        <label for="fResult">Result</label>
                        <select id="fResult" name="result" class="at-select">
                            <option value="">All results</option>
                            <option value="success" <?php echo ($_GET['result'] ?? '') === 'success' ? 'selected' : ''; ?>>Verified</option>
                            <option value="not_found" <?php echo ($_GET['result'] ?? '') === 'not_found' ? 'selected' : ''; ?>>Not Found</option>
                            <option value="inactive" <?php echo ($_GET['result'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="expired" <?php echo ($_GET['result'] ?? '') === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="error" <?php echo ($_GET['result'] ?? '') === 'error' ? 'selected' : ''; ?>>Error</option>
                        </select>
                    </div>
                </div>
                <div class="at-actions">
                    <button type="submit" class="at-btn at-btn-primary">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    <a href="<?php echo e($calendarUrl); ?>" class="at-btn at-btn-ghost">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                    <button type="button" class="at-btn at-btn-ghost" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </form>
        </div>

        <div class="at-table-wrap">
            <table class="at-table">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Resident Name</th>
                        <th>QR Code</th>
                        <th>Result</th>
                        <th>Scanned By</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6" class="at-empty">No scan records found for this event.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo e(date('M d, Y h:i A', strtotime($r['scanned_at']))); ?></td>
                                <td class="at-resident"><?php echo e($r['resident_name'] ?? '—'); ?></td>
                                <td><?php echo e($r['qr_code_scanned']); ?></td>
                                <td><span class="at-tag <?php echo e($r['scan_result']); ?>"><?php echo e(strtoupper($r['scan_result'])); ?></span></td>
                                <td><?php echo e($r['scanned_by_name'] ?? '—'); ?></td>
                                <td><?php echo e($r['remarks'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="at-pager no-print">
                <?php if ($page > 1): ?>
                    <a href="<?php echo e(qs(['page' => $page - 1])); ?>"><i class="bi bi-chevron-left"></i></a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($p = $start; $p <= $end; $p++):
                ?>
                    <?php if ($p === $page): ?>
                        <span class="current"><?php echo $p; ?></span>
                    <?php else: ?>
                        <a href="<?php echo e(qs(['page' => $p])); ?>"><?php echo $p; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo e(qs(['page' => $page + 1])); ?>"><i class="bi bi-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
