<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary', 'admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

$projects = [];
try {
    $projects = $pdo->query('SELECT id, title FROM projects ORDER BY title')->fetchAll();
} catch (Throwable $e) {
    $projects = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add_budget') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        $type = trim($_POST['type'] ?? 'allocation');
        $description = trim($_POST['description'] ?? '');
        $stmt = $pdo->prepare('INSERT INTO project_budget (project_id, amount, source, type, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$projectId, $amount, $source, $type, $description]);
        $_SESSION['_flash_success'] = 'Budget entry added.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif ($action === 'add_expense') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $stmt = $pdo->prepare('INSERT INTO expenses (project_id, amount, description) VALUES (?, ?, ?)');
        $stmt->execute([$projectId, $amount, $description]);
        $_SESSION['_flash_success'] = 'Expense recorded.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$filterProject = (int) ($_GET['project_id'] ?? 0);

$budgetWhere = '';
$expenseWhere = '';
$budgetParams = [];
$expenseParams = [];

if ($filterProject) {
    $budgetWhere .= ' AND pb.project_id = ?';
    $expenseWhere .= ' AND e.project_id = ?';
    $budgetParams[] = $filterProject;
    $expenseParams[] = $filterProject;
}

$budgetPaginator = paginate(
    'SELECT COUNT(*) FROM project_budget pb LEFT JOIN projects p ON p.id = pb.project_id WHERE 1=1' . $budgetWhere,
    $budgetParams,
    'SELECT pb.*, p.title FROM project_budget pb LEFT JOIN projects p ON p.id = pb.project_id WHERE 1=1' . $budgetWhere . ' ORDER BY pb.created_at DESC',
    $budgetParams
);
$budgetRows = $budgetPaginator['data'];

$expensePaginator = paginate(
    'SELECT COUNT(*) FROM expenses e LEFT JOIN projects p ON p.id = e.project_id WHERE 1=1' . $expenseWhere,
    $expenseParams,
    'SELECT e.*, p.title FROM expenses e LEFT JOIN projects p ON p.id = e.project_id WHERE 1=1' . $expenseWhere . ' ORDER BY e.created_at DESC',
    $expenseParams
);
$expenseRows = $expensePaginator['data'];

$reportQuery = 'SELECT p.id, p.title,
    COALESCE((SELECT SUM(amount) FROM project_budget WHERE project_id = p.id), 0) as total_budget,
    COALESCE((SELECT SUM(amount) FROM expenses WHERE project_id = p.id), 0) as total_expenses
    FROM projects p ORDER BY p.title';
$reportRows = $pdo->query($reportQuery)->fetchAll();

$totalAllocation = 0;
$totalExpensesVal = 0;
foreach ($reportRows as $row) {
    $totalAllocation += (float) $row['total_budget'];
    $totalExpensesVal += (float) $row['total_expenses'];
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
   CUSTOM PROPERTIES
   ═══════════════════════════════ */
:root {
    --bg-primary: #1a56db;
    --bg-accent: #10b981;
    --bg-accent-dark: #059669;
    --bg-teal: #14b8a6;
    --bg-amber: #f59e0b;
    --bg-red: #ef4444;
    --bg-violet: #8b5cf6;
    --bg-sky: #0ea5e9;
    --bg-base: #0f172a;
    --bg-surface: rgba(255,255,255,0.04);
    --bg-card: rgba(255,255,255,0.03);
    --bg-text: #f0f4f8;
    --bg-text-sec: #94a3b8;
    --bg-text-muted: #64748b;
    --bg-text-dim: #475569;
    --bg-border: rgba(255,255,255,0.08);
    --bg-border-lt: rgba(255,255,255,0.12);
    --bg-rad: 12px;
    --bg-rad-lg: 16px;
    --bg-rad-xl: 20px;
}

/* ═══════════════════════════════
   PAGE ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg-base) !important;
    color: var(--bg-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.bg-pg::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.bg-pg::after {
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

.bg-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: bgFloat 22s ease-in-out infinite;
}
.bg-orb.o1 { width: 480px; height: 480px; background: rgba(245,158,11,0.06); top: -12%; right: -8%; }
.bg-orb.o2 { width: 360px; height: 360px; background: rgba(16,185,129,0.06); bottom: -10%; left: -6%; animation-delay: -10s; }
.bg-orb.o3 { width: 260px; height: 260px; background: rgba(139,92,246,0.05); top: 45%; left: 35%; animation-delay: -5s; animation-duration: 28s; }

@keyframes bgFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.bg-pg { min-height: 100vh; position: relative; z-index: 1; }

.bg-layout {
    display: flex;
    min-height: 100vh;
}

.bg-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--bg-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.bg-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.bg-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.bg-head-left { flex: 1; min-width: 280px; }

.bg-page-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    border-radius: 100px;
    color: #fcd34d;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
    width: fit-content;
}

.bg-page-badge .bg-dot {
    width: 7px; height: 7px;
    background: var(--bg-amber);
    border-radius: 50%;
    animation: bgPulse 2s ease-in-out infinite;
}

@keyframes bgPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.bg-page-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.bg-page-title span {
    background: linear-gradient(135deg, var(--bg-amber), #fbbf24);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.bg-page-desc {
    font-size: 0.92rem;
    color: var(--bg-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* Stat pills */
.bg-stats-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: flex-start;
}

.bg-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 600;
    white-space: nowrap;
}

.bg-stat-pill i { font-size: 0.9rem; }

.bg-stat-allocated {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}

.bg-stat-expenses {
    background: rgba(239,68,68,0.10);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}

.bg-stat-remaining {
    background: rgba(14,165,233,0.10);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}

/* ═══════════════════════════════
   SUCCESS ALERT
   ═══════════════════════════════ */
.bg-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: var(--bg-rad);
    color: #6ee7b7;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: bgSlide 0.4s ease;
}

.bg-alert i { font-size: 1.15rem; color: var(--bg-accent); flex-shrink: 0; }

@keyframes bgSlide {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.bg-card {
    background: var(--bg-card);
    border: 1px solid var(--bg-border);
    border-radius: var(--bg-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}

.bg-card:hover { border-color: rgba(255,255,255,0.12); }

.bg-card-hd {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.bg-card-ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.bg-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.bg-card-st {
    font-size: 0.82rem;
    color: var(--bg-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* ═══════════════════════════════
   FORM ELEMENTS
   ═══════════════════════════════ */
.bg-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}

.bg-label i { font-size: 0.82rem; color: var(--bg-text-muted); }

.bg-input,
.bg-select,
.bg-textarea {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--bg-rad);
    font-size: 0.88rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}

.bg-input::placeholder,
.bg-textarea::placeholder { color: #475569; }

.bg-input:focus,
.bg-select:focus,
.bg-textarea:focus {
    border-color: var(--bg-accent);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    background: rgba(255,255,255,0.07);
}

.bg-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}

.bg-select option { background: #1e293b; color: #e2e8f0; }

.bg-textarea { resize: vertical; min-height: 80px; }

/* ═══════════════════════════════
   FORM GRID
   ═══════════════════════════════ */
.bg-fg {
    display: grid;
    gap: 18px;
}

.bg-fg-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
.bg-fg-3 { grid-template-columns: 1fr 1fr 1fr; }
.bg-fg-2 { grid-template-columns: 1fr 1fr; }

.bg-fg .bg-s2 { grid-column: span 2; }
.bg-fg .bg-s3 { grid-column: span 3; }
.bg-fg .bg-s4 { grid-column: span 4; }

@media (max-width: 991px) {
    .bg-fg-4 { grid-template-columns: 1fr 1fr; }
    .bg-fg-4 .bg-s3,
    .bg-fg-4 .bg-s4 { grid-column: span 2; }
    .bg-fg-3 { grid-template-columns: 1fr 1fr; }
    .bg-fg-3 .bg-s2 { grid-column: span 2; }
}
@media (max-width: 640px) {
    .bg-fg-4,
    .bg-fg-3,
    .bg-fg-2 { grid-template-columns: 1fr; }
    .bg-fg-4 .bg-s2,
    .bg-fg-4 .bg-s3,
    .bg-fg-4 .bg-s4,
    .bg-fg-3 .bg-s2 { grid-column: span 1; }
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.bg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--bg-rad);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}

.bg-btn i { transition: transform 0.2s ease; }

.bg-btn-accent {
    background: linear-gradient(135deg, var(--bg-accent), var(--bg-accent-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
}
.bg-btn-accent:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.35);
    color: #ffffff;
}
.bg-btn-accent:active { transform: translateY(0); }
.bg-btn-accent:hover i { transform: translateX(3px); }

.bg-btn-amber {
    background: linear-gradient(135deg, var(--bg-amber), #d97706);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(245,158,11,0.25);
}
.bg-btn-amber:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(245,158,11,0.35);
    color: #ffffff;
}
.bg-btn-amber:active { transform: translateY(0); }
.bg-btn-amber:hover i { transform: translateX(3px); }

.bg-btn-ghost {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: #e2e8f0;
}
.bg-btn-ghost:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    color: #ffffff;
}

/* ═══════════════════════════════
   FILTER BAR
   ═══════════════════════════════ */
.bg-filter-bar {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.bg-filter-grp {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.bg-filter-grp.grow { flex: 1; min-width: 180px; }

/* ═══════════════════════════════
   SUMMARY TABLE
   ═══════════════════════════════ */
.bg-table-wrap {
    overflow-x: auto;
    border-radius: var(--bg-rad-lg);
    border: 1px solid rgba(255,255,255,0.05);
}

.bg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.bg-table thead th {
    padding: 14px 18px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: var(--bg-text-sec);
    font-weight: 600;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    white-space: nowrap;
}

.bg-table tbody td {
    padding: 16px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    color: #e2e8f0;
    vertical-align: middle;
}

.bg-table tbody tr:last-child td { border-bottom: none; }

.bg-table tbody tr {
    transition: background 0.2s ease;
}

.bg-table tbody tr:hover {
    background: rgba(255,255,255,0.03);
}

.bg-table .bg-proj-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: #f1f5f9;
}

.bg-table .bg-money {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.bg-table .bg-money-pos { color: #6ee7b7; }
.bg-table .bg-money-neg { color: #fca5a5; }
.bg-table .bg-money-neutral { color: var(--bg-text-sec); }

/* Progress bar */
.bg-progress-track {
    height: 7px;
    min-width: 120px;
    background: rgba(255,255,255,0.06);
    border-radius: 100px;
    overflow: hidden;
}

.bg-progress-fill {
    height: 100%;
    border-radius: 100px;
    transition: width 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.bg-progress-fill.ok { background: linear-gradient(90deg, var(--bg-accent), #34d399); }
.bg-progress-fill.over { background: linear-gradient(90deg, var(--bg-red), #f87171); }

/* Remaining badge */
.bg-remaining-badge {
    display: inline-flex;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}

.bg-remaining-badge.ok {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}

.bg-remaining-badge.over {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}

/* ═══════════════════════════════
   LIST ITEMS (budget & expenses)
   ═══════════════════════════════ */
.bg-list { display: flex; flex-direction: column; }

.bg-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.bg-list-item:last-child { border-bottom: none; }

.bg-list-info { flex: 1; min-width: 0; }

.bg-list-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: #f1f5f9;
    margin-bottom: 4px;
}

.bg-list-meta {
    font-size: 0.8rem;
    color: var(--bg-text-muted);
    line-height: 1.5;
}

.bg-list-meta .bg-sep {
    margin: 0 6px;
    color: var(--bg-text-dim);
}

.bg-list-amount {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.bg-list-amount.pos {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}

.bg-list-amount.neg {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}

/* Empty state */
.bg-empty {
    text-align: center;
    padding: 40px 20px;
}

.bg-empty-ico {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(100,116,139,0.1);
    border: 1px solid rgba(100,116,139,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: var(--bg-text-dim);
    margin: 0 auto 14px;
}

.bg-empty-txt {
    font-size: 0.9rem;
    color: var(--bg-text-muted);
    margin: 0;
}

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.bg-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.bg-reveal.bg-vis { opacity: 1; transform: translateY(0); }

.bg-d1 { transition-delay: 0.05s; }
.bg-d2 { transition-delay: 0.1s; }
.bg-d3 { transition-delay: 0.15s; }
.bg-d4 { transition-delay: 0.2s; }
.bg-d5 { transition-delay: 0.25s; }
.bg-d6 { transition-delay: 0.3s; }
.bg-d7 { transition-delay: 0.35s; }
.bg-d8 { transition-delay: 0.4s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .bg-main { padding: 32px 36px; }
}

@media (max-width: 991.98px) {
    .bg-layout { flex-direction: column; }
    .bg-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--bg-border);
    }
    .bg-main { padding: 28px 24px; }
    .bg-head { flex-direction: column; gap: 16px; }
}

@media (max-width: 767.98px) {
    .bg-main { padding: 24px 16px; }
    .bg-card { padding: 24px 20px; }
    .bg-page-title { font-size: 1.4rem; }
    .bg-stats-row { flex-direction: column; }
    .bg-stat-pill { width: fit-content; }
}

@media (max-width: 480px) {
    .bg-main { padding: 20px 14px; }
    .bg-card { padding: 20px 16px; border-radius: 16px; }
    .bg-filter-bar { flex-direction: column; }
    .bg-filter-grp.grow { min-width: 100%; }
    .bg-list-item { flex-direction: column; align-items: flex-start; gap: 10px; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="bg-pg">
    <div class="bg-orb o1"></div>
    <div class="bg-orb o2"></div>
    <div class="bg-orb o3"></div>

    <div class="bg-layout">
        <!-- Sidebar -->
        <div class="bg-sidebar">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <div class="bg-main">

            <!-- Header -->
            <div class="bg-head bg-reveal bg-d1">
                <div class="bg-head-left">
                    <div class="bg-page-badge">
                        <span class="bg-dot"></span>
                        Finance
                    </div>
                    <h1 class="bg-page-title">Budget <span>Management</span></h1>
                    <p class="bg-page-desc">Track project budgets, allocations, expenses, and remaining balances across all barangay projects.</p>
                </div>
                <div class="bg-stats-row">
                    <div class="bg-stat-pill bg-stat-allocated">
                        <i class="bi bi-piggy-bank"></i>
                        Allocated: ₱<?php echo number_format($totalAllocation, 2); ?>
                    </div>
                    <div class="bg-stat-pill bg-stat-expenses">
                        <i class="bi bi-receipt"></i>
                        Expenses: ₱<?php echo number_format($totalExpensesVal, 2); ?>
                    </div>
                    <div class="bg-stat-pill bg-stat-remaining">
                        <i class="bi bi-wallet2"></i>
                        Remaining: ₱<?php echo number_format($totalAllocation - $totalExpensesVal, 2); ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="bg-alert bg-reveal bg-d2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Budget Allocation Form -->
            <div class="bg-card bg-reveal bg-d2">
                <div class="bg-card-hd">
                    <div class="bg-card-ico" style="background:rgba(16,185,129,0.12); color:#10b981;">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <div>
                        <h5 class="bg-card-tt">New Budget Allocation</h5>
                        <p class="bg-card-st">Record a new fund allocation, donation, or grant to a project.</p>
                    </div>
                </div>

                <form method="post">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add_budget">
                    <div class="bg-fg bg-fg-4">
                        <div>
                            <label class="bg-label"><i class="bi bi-folder"></i> Project</label>
                            <select name="project_id" class="bg-select" required>
                                <option value="">Select project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo (int) $project['id']; ?>"><?php echo e($project['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="bg-label"><i class="bi bi-cash-stack"></i> Amount (₱)</label>
                            <input type="number" step="0.01" name="amount" class="bg-input" placeholder="0.00" required>
                        </div>
                        <div>
                            <label class="bg-label"><i class="bi bi-building"></i> Source</label>
                            <input type="text" name="source" class="bg-input" placeholder="e.g. Barangay Fund">
                        </div>
                        <div>
                            <label class="bg-label"><i class="bi bi-tag"></i> Type</label>
                            <select name="type" class="bg-select">
                                <option value="allocation">Allocation</option>
                                <option value="donation">Donation</option>
                                <option value="grant">Grant</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="bg-s4">
                            <label class="bg-label"><i class="bi bi-text-left"></i> Description</label>
                            <input type="text" name="description" class="bg-input" placeholder="Brief description of this budget entry...">
                        </div>
                    </div>
                    <div style="margin-top: 24px;">
                        <button type="submit" class="bg-btn bg-btn-accent">
                            <i class="bi bi-save"></i>
                            <span>Save Budget Entry</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Expense Form -->
            <div class="bg-card bg-reveal bg-d3">
                <div class="bg-card-hd">
                    <div class="bg-card-ico" style="background:rgba(239,68,68,0.12); color:#ef4444;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <h5 class="bg-card-tt">Record Expense</h5>
                        <p class="bg-card-st">Log a new expense against a project's budget.</p>
                    </div>
                </div>

                <form method="post">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add_expense">
                    <div class="bg-fg bg-fg-3">
                        <div>
                            <label class="bg-label"><i class="bi bi-folder"></i> Project</label>
                            <select name="project_id" class="bg-select" required>
                                <option value="">Select project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo (int) $project['id']; ?>"><?php echo e($project['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="bg-label"><i class="bi bi-cash-stack"></i> Amount (₱)</label>
                            <input type="number" step="0.01" name="amount" class="bg-input" placeholder="0.00" required>
                        </div>
                        <div>
                            <label class="bg-label"><i class="bi bi-text-left"></i> Description</label>
                            <input type="text" name="description" class="bg-input" placeholder="Expense details">
                        </div>
                    </div>
                    <div style="margin-top: 24px;">
                        <button type="submit" class="bg-btn bg-btn-amber">
                            <i class="bi bi-plus-lg"></i>
                            <span>Record Expense</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Financial Summary -->
            <div class="bg-card bg-reveal bg-d4">
                <div class="bg-card-hd">
                    <div class="bg-card-ico" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>
                    <div>
                        <h5 class="bg-card-tt">Financial Summary</h5>
                        <p class="bg-card-st">Overview of allocated budgets versus actual expenses per project.</p>
                    </div>
                </div>

                <?php if (!empty($reportRows)): ?>
                    <div class="bg-table-wrap">
                        <table class="bg-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Allocated</th>
                                    <th>Expenses</th>
                                    <th>Remaining</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportRows as $row): ?>
                                    <?php
                                        $remaining = (float) $row['total_budget'] - (float) $row['total_expenses'];
                                        $pct = $row['total_budget'] > 0 ? min(100, max(0, ((float)$row['total_expenses'] / (float)$row['total_budget']) * 100)) : 0;
                                        $isOver = $remaining < 0;
                                    ?>
                                    <tr>
                                        <td><span class="bg-proj-name"><?php echo e($row['title']); ?></span></td>
                                        <td><span class="bg-money bg-money-pos">₱<?php echo number_format($row['total_budget'], 2); ?></span></td>
                                        <td><span class="bg-money bg-money-neg">₱<?php echo number_format($row['total_expenses'], 2); ?></span></td>
                                        <td>
                                            <span class="bg-remaining-badge <?php echo $isOver ? 'over' : 'ok'; ?>">
                                                ₱<?php echo number_format($remaining, 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="bg-progress-track">
                                                <div class="bg-progress-fill <?php echo $isOver ? 'over' : 'ok'; ?>" style="width: <?php echo $pct; ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-empty">
                        <div class="bg-empty-ico"><i class="bi bi-inbox"></i></div>
                        <p class="bg-empty-txt">No projects found. Create a project first to track budgets.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Filter -->
            <div class="bg-card bg-reveal bg-d5">
                <div class="bg-card-hd">
                    <div class="bg-card-ico" style="background:rgba(139,92,246,0.12); color:#8b5cf6;">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <div>
                        <h5 class="bg-card-tt">Filter & Export</h5>
                        <p class="bg-card-st">Narrow down entries by project or export data to CSV.</p>
                    </div>
                </div>

                <form method="get">
                    <div class="bg-filter-bar">
                        <div class="bg-filter-grp grow">
                            <label class="bg-label"><i class="bi bi-folder"></i> Project</label>
                            <select name="project_id" class="bg-select">
                                <option value="0">All Projects</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo (int) $project['id']; ?>" <?php echo $filterProject === (int) $project['id'] ? 'selected' : ''; ?>><?php echo e($project['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="bg-filter-grp">
                            <button type="submit" class="bg-btn bg-btn-ghost">
                                <i class="bi bi-funnel"></i>
                                <span>Apply Filter</span>
                            </button>
                        </div>
                        <div class="bg-filter-grp">
                            <a href="<?php echo BASE_URL; ?>/secretary/budget.php?export=1" class="bg-btn bg-btn-ghost">
                                <i class="bi bi-download"></i>
                                <span>Export CSV</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Budget Entries List -->
            <div class="bg-card bg-reveal bg-d6">
                <div class="bg-card-hd">
                    <div class="bg-card-ico" style="background:rgba(16,185,129,0.12); color:#10b981;">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                    <div>
                        <h5 class="bg-card-tt">Budget Entries</h5>
                        <p class="bg-card-st"><?php echo count($budgetRows); ?> recorded allocation<?php echo count($budgetRows) !== 1 ? 's' : ''; ?>.</p>
                    </div>
                </div>

                <?php if (!empty($budgetRows)): ?>
                    <div class="bg-list">
                        <?php foreach ($budgetRows as $budget): ?>
                            <div class="bg-list-item">
                                <div class="bg-list-info">
                                    <div class="bg-list-name"><?php echo e($budget['title'] ?? 'Untitled Project'); ?></div>
                                    <div class="bg-list-meta">
                                        <?php echo e($budget['source'] ?: 'No source'); ?>
                                        <span class="bg-sep">&bull;</span>
                                        <?php echo e(ucwords(str_replace('_', ' ', $budget['type'] ?? 'allocation'))); ?>
                                        <?php if ($budget['description']): ?>
                                            <span class="bg-sep">&bull;</span>
                                            <?php echo e($budget['description']); ?>
                                        <?php endif; ?>
                                        <span class="bg-sep">&bull;</span>
                                        <?php echo date('M d, Y', strtotime($budget['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="bg-list-amount pos">
                                    <i class="bi bi-plus-lg"></i>
                                    ₱<?php echo number_format($budget['amount'], 2); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($budgetRows)): ?>
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($budgetPaginator); ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-empty">
                        <div class="bg-empty-ico"><i class="bi bi-inbox"></i></div>
                        <p class="bg-empty-txt">No budget entries yet. Add one above to get started.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Expenses List -->
            <div class="bg-card bg-reveal bg-d7">
                <div class="bg-card-hd">
                    <div class="bg-card-ico" style="background:rgba(239,68,68,0.12); color:#ef4444;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <h5 class="bg-card-tt">Expenses</h5>
                        <p class="bg-card-st"><?php echo count($expenseRows); ?> recorded expense<?php echo count($expenseRows) !== 1 ? 's' : ''; ?>.</p>
                    </div>
                </div>

                <?php if (!empty($expenseRows)): ?>
                    <div class="bg-list">
                        <?php foreach ($expenseRows as $expense): ?>
                            <div class="bg-list-item">
                                <div class="bg-list-info">
                                    <div class="bg-list-name"><?php echo e($expense['title'] ?? 'Untitled Project'); ?></div>
                                    <div class="bg-list-meta">
                                        <?php echo e($expense['description'] ?: 'No description'); ?>
                                        <span class="bg-sep">&bull;</span>
                                        <?php echo date('M d, Y', strtotime($expense['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="bg-list-amount neg">
                                    <i class="bi bi-dash-lg"></i>
                                    ₱<?php echo number_format($expense['amount'], 2); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($expenseRows)): ?>
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($expensePaginator); ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-empty">
                        <div class="bg-empty-ico"><i class="bi bi-inbox"></i></div>
                        <p class="bg-empty-txt">No expenses recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.bg-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('bg-vis');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>