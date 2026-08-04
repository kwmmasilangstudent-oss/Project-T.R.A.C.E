<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

scannerRequireAuth();

$action = $_GET['action'] ?? '';
$isExport = $action === 'export_csv';

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$resultFilter = trim($_GET['result'] ?? '');
$officialFilter = trim($_GET['official'] ?? '');
$search = trim($_GET['search'] ?? '');

function buildWhere(array &$params): string {
    global $dateFrom, $dateTo, $resultFilter, $officialFilter, $search;
    $where = [];
    if ($dateFrom !== '') { $where[] = 'DATE(sl.scanned_at) >= ?'; $params[] = $dateFrom; }
    if ($dateTo !== '') { $where[] = 'DATE(sl.scanned_at) <= ?'; $params[] = $dateTo; }
    $allowed = ['success', 'not_found', 'inactive', 'expired', 'error'];
    if ($resultFilter !== '' && in_array($resultFilter, $allowed, true)) {
        $where[] = 'sl.scan_result = ?'; $params[] = $resultFilter;
    }
    if ($officialFilter !== '') { $where[] = 'sl.scanned_by_name LIKE ?'; $params[] = '%' . $officialFilter . '%'; }
    if ($search !== '') {
        $where[] = '(r.full_name LIKE ? OR sl.qr_code_scanned LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    return $where ? ('WHERE ' . implode(' AND ', $where)) : '';
}

function fetchRows(bool $limited): array {
    global $perPage, $page;
    $params = [];
    $where = buildWhere($params);
    $pdo = getDbConnection();
    $limitSql = '';
    if ($limited) {
        $offset = ($page - 1) * $perPage;
        $limitSql = "LIMIT $perPage OFFSET $offset";
    }
    $stmt = $pdo->prepare(
        "SELECT sl.*, r.full_name AS resident_name
         FROM scan_logs sl
         LEFT JOIN residents r ON r.id = sl.resident_id
         $where
         ORDER BY sl.scanned_at DESC
         $limitSql"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchTotal(): int {
    $params = [];
    $where = buildWhere($params);
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM scan_logs sl
         LEFT JOIN residents r ON r.id = sl.resident_id
         $where"
    );
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function fetchSummary(): array {
    $params = [];
    $where = buildWhere($params);
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN scan_result = 'success' THEN 1 ELSE 0 END) AS success,
            SUM(CASE WHEN scan_result = 'not_found' THEN 1 ELSE 0 END) AS not_found,
            SUM(CASE WHEN scan_result IN ('inactive','expired') THEN 1 ELSE 0 END) AS inactive,
            SUM(CASE WHEN scan_result = 'error' THEN 1 ELSE 0 END) AS error
         FROM scan_logs sl
         LEFT JOIN residents r ON r.id = sl.resident_id
         $where"
    );
    $stmt->execute($params);
    $row = $stmt->fetch();
    return [
        'total' => (int) ($row['total'] ?? 0),
        'success' => (int) ($row['success'] ?? 0),
        'not_found' => (int) ($row['not_found'] ?? 0),
        'inactive' => (int) ($row['inactive'] ?? 0),
        'error' => (int) ($row['error'] ?? 0),
    ];
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;

if ($isExport) {
    $rows = fetchRows(false);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="scan_logs_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date/Time', 'Resident Name', 'QR Code', 'Result', 'Scanned By', 'Remarks', 'IP Address']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['scanned_at'],
            $r['resident_name'] ?? '—',
            $r['qr_code_scanned'],
            strtoupper($r['scan_result']),
            $r['scanned_by_name'] ?? '',
            $r['remarks'] ?? '',
            $r['ip_address'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

$rows = fetchRows(true);
$total = fetchTotal();
$totalPages = (int) ceil($total / $perPage);
$summary = fetchSummary();

$base = BASE_URL;
$cssSrc = $base . '/scanner/assets/css/scanner.css';
$jsSrc = $base . '/scanner/assets/js/logs.js';
$scannerUrl = $base . '/scanner/index.php';

function qs(array $overrides): string {
    global $dateFrom, $dateTo, $resultFilter, $officialFilter, $search;
    $p = [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'result' => $resultFilter,
        'official' => $officialFilter,
        'search' => $search,
    ];
    foreach ($overrides as $k => $v) { $p[$k] = $v; }
    return '?' . http_build_query(array_filter($p, function ($v) { return $v !== '' && $v !== null; }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Logs — <?php echo e(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e($cssSrc); ?>">
    <style>
        .lg-wrap { max-width: 1100px; margin: 0 auto; padding: 20px 16px 60px; }
        .lg-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
        .lg-title { font-size: 28px; font-weight: 800; margin:0; color:#fff; }
        .lg-back { display:inline-flex; align-items:center; gap:8px; height:48px; padding:0 18px; border-radius:12px;
            background: var(--sc-accent); color:#fff; font-weight:700; text-decoration:none; font-size:16px; }
        .lg-back:hover { background: var(--sc-accent-dark); color:#fff; }
        .lg-summary { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
        .lg-sum-card { flex:1 1 130px; background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);
            border-radius:14px; padding:16px; text-align:center; }
        .lg-sum-num { font-size:30px; font-weight:800; color:#fff; line-height:1; }
        .lg-sum-lbl { font-size:14px; color:#94a3b8; margin-top:6px; }
        .lg-sum-card.ok .lg-sum-num { color:#4ade80; }
        .lg-sum-card.bad .lg-sum-num { color:#f87171; }
        .lg-sum-card.warn .lg-sum-num { color:#fbbf24; }
        .lg-filters { background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);
            border-radius:14px; padding:16px; margin-bottom:18px; }
        .lg-filter-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:12px; }
        .lg-field label { display:block; font-size:14px; color:#cbd5e1; margin-bottom:6px; font-weight:600; }
        .lg-input, .lg-select { width:100%; min-height:48px; padding:10px 14px; font-size:16px; border-radius:10px;
            border:1px solid var(--sc-border); background:#fff; color:var(--sc-text); font-family:var(--sc-font); }
        .lg-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
        .lg-table-wrap { overflow-x:auto; border-radius:14px; border:1px solid rgba(255,255,255,0.08); }
        table.lg-table { width:100%; border-collapse:collapse; min-width:720px; background:#fff; color:var(--sc-text); }
        table.lg-table th, table.lg-table td { padding:14px 16px; text-align:left; font-size:15px; border-bottom:1px solid var(--sc-border-soft); }
        table.lg-table th { background:#f1f5f9; font-weight:700; position:sticky; top:0; }
        table.lg-table tr:nth-child(even) td { background:#f8fafc; }
        .lg-tag { display:inline-block; padding:3px 12px; border-radius:100px; font-size:13px; font-weight:700; }
        .lg-tag.success { background: var(--sc-green-soft); color:#166534; }
        .lg-tag.not_found, .lg-tag.error { background: var(--sc-red-soft); color:#991b1b; }
        .lg-tag.inactive, .lg-tag.expired { background: var(--sc-orange-soft); color:#9a3412; }
        .lg-pager { display:flex; align-items:center; justify-content:center; gap:8px; margin-top:18px; flex-wrap:wrap; }
        .lg-pager a, .lg-pager span { min-width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center;
            padding:0 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.14); background:rgba(255,255,255,0.06);
            color:#e2e8f0; text-decoration:none; font-weight:600; font-size:15px; }
        .lg-pager a:hover { background:rgba(255,255,255,0.14); color:#fff; }
        .lg-pager .current { background: var(--sc-accent); border-color:var(--sc-accent); color:#fff; }
        .lg-empty { text-align:center; padding:40px; color:#94a3b8; font-size:17px; }
        .lg-resident { font-weight:700; }
        .lg-print-only { display:none; }
        @media print {
            body { background:#fff; }
            .sc-topbar, .lg-filters, .lg-actions, .lg-pager, .lg-back, .no-print { display:none !important; }
            .lg-wrap { max-width:100%; padding:0; }
            table.lg-table { color:#000; }
            table.lg-table th { background:#eee; }
            .lg-sum-card { background:#fff; border:1px solid #ccc; }
            .lg-sum-num { color:#000 !important; }
            .lg-sum-lbl { color:#333 !important; }
        }
    </style>
</head>
<body>

    <div class="sc-topbar no-print">
        <div class="sc-topbar-brand"><i class="bi bi-clock-history"></i><span>Scan Logs</span></div>
        <div class="sc-topbar-actions">
            <?php $dashboardUrl = ($_SESSION['role'] ?? '') === 'secretary' ? e($base) . '/secretary/dashboard.php' : e($base) . '/admin/dashboard.php'; ?>
            <a class="sc-history-link" href="<?php echo $dashboardUrl; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="sc-history-link" href="<?php echo e($scannerUrl); ?>"><i class="bi bi-upc-scan"></i> Scanner</a>
        </div>
    </div>

    <div class="lg-wrap">
        <div class="lg-head no-print">
            <h1 class="lg-title">Scan History &amp; Logs</h1>
            <a class="lg-back" href="<?php echo e($scannerUrl); ?>"><i class="bi bi-arrow-left"></i> Back to Scanner</a>
        </div>

        <div class="lg-summary">
            <div class="lg-sum-card"><div class="lg-sum-num"><?php echo $summary['total']; ?></div><div class="lg-sum-lbl">Total Scans</div></div>
            <div class="lg-sum-card ok"><div class="lg-sum-num"><?php echo $summary['success']; ?></div><div class="lg-sum-lbl">Verified</div></div>
            <div class="lg-sum-card bad"><div class="lg-sum-num"><?php echo $summary['not_found']; ?></div><div class="lg-sum-lbl">Not Found</div></div>
            <div class="lg-sum-card warn"><div class="lg-sum-num"><?php echo $summary['inactive']; ?></div><div class="lg-sum-lbl">Inactive / Expired</div></div>
            <div class="lg-sum-card"><div class="lg-sum-num"><?php echo $summary['error']; ?></div><div class="lg-sum-lbl">Errors</div></div>
        </div>

        <form class="lg-filters no-print" method="get">
            <div class="lg-filter-grid">
                <div class="lg-field">
                    <label for="fSearch">Search (name or QR)</label>
                    <input type="text" id="fSearch" name="search" class="lg-input" value="<?php echo e($search); ?>" placeholder="Name or code">
                </div>
                <div class="lg-field">
                    <label for="fFrom">Date From</label>
                    <input type="date" id="fFrom" name="date_from" class="lg-input" value="<?php echo e($dateFrom); ?>">
                </div>
                <div class="lg-field">
                    <label for="fTo">Date To</label>
                    <input type="date" id="fTo" name="date_to" class="lg-input" value="<?php echo e($dateTo); ?>">
                </div>
                <div class="lg-field">
                    <label for="fResult">Result</label>
                    <select id="fResult" name="result" class="lg-select">
                        <option value="">All results</option>
                        <option value="success" <?php echo $resultFilter === 'success' ? 'selected' : ''; ?>>Verified</option>
                        <option value="not_found" <?php echo $resultFilter === 'not_found' ? 'selected' : ''; ?>>Not Found</option>
                        <option value="inactive" <?php echo $resultFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="expired" <?php echo $resultFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="error" <?php echo $resultFilter === 'error' ? 'selected' : ''; ?>>Error</option>
                    </select>
                </div>
                <div class="lg-field">
                    <label for="fOfficial">Official Name</label>
                    <input type="text" id="fOfficial" name="official" class="lg-input" value="<?php echo e($officialFilter); ?>" placeholder="Scanned by">
                </div>
            </div>
            <div class="lg-actions">
                <button type="submit" class="sc-btn sc-btn-primary" style="flex:0 0 auto;"><i class="bi bi-funnel"></i> Apply Filters</button>
                <a href="<?php echo e(qs(['page' => 1, 'action' => 'export_csv'])); ?>" class="sc-btn sc-btn-ghost" style="flex:0 0 auto;"><i class="bi bi-download"></i> Export CSV</a>
                <button type="button" class="sc-btn sc-btn-ghost" style="flex:0 0 auto;" onclick="window.print()"><i class="bi bi-printer"></i> Print Summary</button>
                <a href="<?php echo e(BASE_URL . '/scanner/logs.php'); ?>" class="sc-btn sc-btn-ghost" style="flex:0 0 auto;"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </form>

        <div class="lg-table-wrap">
            <table class="lg-table">
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
                        <tr><td colspan="6" class="lg-empty">No scan records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo e(date('M d, Y h:i A', strtotime($r['scanned_at']))); ?></td>
                                <td class="lg-resident"><?php echo e($r['resident_name'] ?? '—'); ?></td>
                                <td><?php echo e($r['qr_code_scanned']); ?></td>
                                <td><span class="lg-tag <?php echo e($r['scan_result']); ?>"><?php echo e(strtoupper($r['scan_result'])); ?></span></td>
                                <td><?php echo e($r['scanned_by_name'] ?? '—'); ?></td>
                                <td><?php echo e($r['remarks'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="lg-pager no-print">
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
    </div>

</body>
</html>
