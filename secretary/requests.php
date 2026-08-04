<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary', 'admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$currentRole = getCurrentRole();

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($applicationId > 0 && $action) {
        $updates = ['reviewed_by' => $_SESSION['user_id'], 'reviewed_at' => date('Y-m-d H:i:s')];
        $status = null;

        if ($action === 'approve') {
            $status = 'approved';
        } elseif ($action === 'reject') {
            $status = 'rejected';
        } elseif ($action === 'ready') {
            $status = 'ready_for_pickup';
        } elseif ($action === 'complete') {
            $status = 'completed';
        } elseif ($action === 'review') {
            $status = 'under_review';
        } elseif ($action === 'pending') {
            $status = 'pending';
        }

        if ($status) {
            $updates['status'] = $status;
        }
        $remarks = trim($_POST['remarks'] ?? '');
        if ($remarks) {
            $updates['remarks'] = $remarks;
        }

        $setParts = [];
        $params = [];
        foreach ($updates as $column => $value) {
            $setParts[] = $column . ' = ?';
            $params[] = $value;
        }
        $params[] = $applicationId;

        $stmt = $pdo->prepare('UPDATE applications SET ' . implode(', ', $setParts) . ' WHERE id = ?');
        $stmt->execute($params);
        if ($status) {
            notifyApplicationStatus($applicationId, $status, (int) ($_SESSION['user_id'] ?? 0));
        }
        $_SESSION['_flash_success'] = 'Application updated successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$statusFilter = trim($_GET['status'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');

$where = '';
$params = [];
if ($statusFilter) {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
}
if ($priorityFilter) {
    $where .= ' AND a.priority = ?';
    $params[] = $priorityFilter;
}

$orderBy = ' ORDER BY
    CASE
        WHEN a.priority = "urgent" THEN 1
        WHEN a.priority = "high" THEN 2
        ELSE 3
    END,
    a.created_at DESC';

$paginator = paginate(
    'SELECT COUNT(*) FROM applications a LEFT JOIN residents r ON r.id = a.resident_id WHERE 1=1' . $where,
    $params,
    'SELECT a.*, r.full_name, r.address FROM applications a LEFT JOIN residents r ON r.id = a.resident_id WHERE 1=1' . $where . $orderBy,
    $params
);
$applications = $paginator['data'];

$statsQuery = 'SELECT
    COUNT(*) as total,
    COALESCE(SUM(status = "submitted"), 0) as submitted,
    COALESCE(SUM(status = "pending"), 0) as pending,
    COALESCE(SUM(status = "under_review"), 0) as under_review,
    COALESCE(SUM(status = "approved"), 0) as approved,
    COALESCE(SUM(status = "ready_for_pickup"), 0) as ready_for_pickup,
    COALESCE(SUM(status = "completed"), 0) as completed,
    COALESCE(SUM(status = "rejected"), 0) as rejected
    FROM applications';
$stats = $pdo->query($statsQuery)->fetch();

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
    --rq-accent: #10b981;
    --rq-accent-dark: #059669;
    --rq-amber: #f59e0b;
    --rq-amber-dark: #d97706;
    --rq-sky: #0ea5e9;
    --rq-red: #ef4444;
    --rq-violet: #8b5cf6;
    --rq-teal: #14b8a6;
    --rq-rose: #f43f5e;
    --rq-bg: #0f172a;
    --rq-card: rgba(255,255,255,0.03);
    --rq-text: #f0f4f8;
    --rq-text-sec: #94a3b8;
    --rq-text-muted: #64748b;
    --rq-text-dim: #475569;
    --rq-border: rgba(255,255,255,0.08);
    --rq-border-lt: rgba(255,255,255,0.12);
    --rq-rad: 12px;
    --rq-rad-lg: 16px;
    --rq-rad-xl: 20px;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--rq-bg) !important;
    color: var(--rq-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.rq-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.rq-page::after {
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

.rq-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: rqFloat 22s ease-in-out infinite;
}
.rq-orb.o1 { width: 460px; height: 460px; background: rgba(245,158,11,0.06); top: -12%; right: -8%; }
.rq-orb.o2 { width: 320px; height: 320px; background: rgba(16,185,129,0.06); bottom: -10%; left: -6%; animation-delay: -11s; }
.rq-orb.o3 { width: 240px; height: 240px; background: rgba(14,165,233,0.05); top: 48%; left: 30%; animation-delay: -5s; animation-duration: 26s; }

@keyframes rqFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.rq-page { min-height: 100vh; position: relative; z-index: 1; }

.rq-layout { display: flex; min-height: 100vh; }

.rq-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--rq-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.rq-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.rq-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.rq-head-left { flex: 1; min-width: 260px; }

.rq-badge {
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

.rq-badge .rq-dot {
    width: 7px; height: 7px;
    background: var(--rq-amber);
    border-radius: 50%;
    animation: rqPulse 2s ease-in-out infinite;
}

@keyframes rqPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.rq-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.rq-title span {
    background: linear-gradient(135deg, var(--rq-amber), #fbbf24);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.rq-desc {
    font-size: 0.92rem;
    color: var(--rq-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* Stat pills */
.rq-stats-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: flex-start;
    max-width: 400px;
}

.rq-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 100px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}
.rq-stat-pill i { font-size: 0.82rem; }

.rq-stat-total {
    background: rgba(14,165,233,0.10);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.rq-stat-pending {
    background: rgba(245,158,11,0.10);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.rq-stat-review {
    background: rgba(139,92,246,0.10);
    border: 1px solid rgba(139,92,246,0.25);
    color: #c4b5fd;
}
.rq-stat-approved {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}

/* ═══════════════════════════════
   ALERT
   ═══════════════════════════════ */
.rq-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: var(--rq-rad);
    color: #6ee7b7;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: rqSlide 0.4s ease;
}
.rq-alert i { font-size: 1.15rem; color: var(--rq-accent); flex-shrink: 0; }

@keyframes rqSlide {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.rq-card {
    background: var(--rq-card);
    border: 1px solid var(--rq-border);
    border-radius: var(--rq-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.rq-card:hover { border-color: rgba(255,255,255,0.12); }

.rq-card-hd {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.rq-card-ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.rq-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.rq-card-st {
    font-size: 0.82rem;
    color: var(--rq-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* ═══════════════════════════════
   FORMS
   ═══════════════════════════════ */
.rq-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.rq-label i { font-size: 0.82rem; color: var(--rq-text-muted); }

.rq-select {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--rq-rad);
    font-size: 0.88rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}
.rq-select option { background: #1e293b; color: #e2e8f0; }

.rq-select:focus {
    border-color: var(--rq-amber);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
    background-color: rgba(255,255,255,0.07);
}

/* Filter grid */
.rq-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

@media (max-width: 767px) {
    .rq-filter-grid { grid-template-columns: 1fr; }
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.rq-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--rq-rad);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}
.rq-btn i { transition: transform 0.2s ease; }

.rq-btn-amber {
    background: linear-gradient(135deg, var(--rq-amber), var(--rq-amber-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(245,158,11,0.25);
}
.rq-btn-amber:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(245,158,11,0.35);
    color: #ffffff;
}
.rq-btn-amber:active { transform: translateY(0); }
.rq-btn-amber:hover i { transform: translateX(3px); }

/* Workflow action buttons */
.rq-act {
    padding: 5px 12px;
    font-size: 0.73rem;
    font-weight: 600;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: transparent;
    white-space: nowrap;
}

.rq-act-review {
    background: rgba(14,165,233,0.10);
    border-color: rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.rq-act-review:hover { background: rgba(14,165,233,0.18); color: #bae6fd; }

.rq-act-approve {
    background: rgba(16,185,129,0.10);
    border-color: rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.rq-act-approve:hover { background: rgba(16,185,129,0.18); color: #a7f3d0; }

.rq-act-reject {
    background: rgba(239,68,68,0.10);
    border-color: rgba(239,68,68,0.2);
    color: #fca5a5;
}
.rq-act-reject:hover { background: rgba(239,68,68,0.18); color: #fecaca; }

.rq-act-ready {
    background: rgba(16,185,129,0.10);
    border-color: rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.rq-act-ready:hover { background: rgba(16,185,129,0.18); color: #a7f3d0; }

.rq-act-complete {
    background: rgba(139,92,246,0.10);
    border-color: rgba(139,92,246,0.25);
    color: #c4b5fd;
}
.rq-act-complete:hover { background: rgba(139,92,246,0.18); color: #ddd6fe; }

.rq-act-pending {
    background: rgba(245,158,11,0.10);
    border-color: rgba(245,158,11,0.25);
    color: #fcd34d;
}
.rq-act-pending:hover { background: rgba(245,158,11,0.18); color: #fde68a; }

/* ═══════════════════════════════
   TABLE
   ═══════════════════════════════ */
.rq-table-wrap {
    overflow-x: auto;
    border-radius: var(--rq-rad-lg);
    border: 1px solid rgba(255,255,255,0.04);
}

.rq-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.84rem;
    min-width: 820px;
}

.rq-table thead th {
    padding: 14px 16px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: var(--rq-text-sec);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    white-space: nowrap;
}

.rq-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    color: #e2e8f0;
    vertical-align: middle;
}

.rq-table tbody tr:last-child td { border-bottom: none; }
.rq-table tbody tr { transition: background 0.2s ease; }
.rq-table tbody tr:hover { background: rgba(255,255,255,0.03); }

.rq-table .rq-cell-id {
    font-weight: 700;
    font-size: 0.82rem;
    color: #f1f5f9;
    font-variant-numeric: tabular-nums;
}

.rq-table .rq-cell-name {
    font-weight: 600;
    color: #e2e8f0;
}

.rq-table .rq-cell-type {
    color: var(--rq-text-sec);
}

.rq-table .rq-cell-date {
    font-size: 0.8rem;
    color: var(--rq-text-dim);
    white-space: nowrap;
}

/* Status badges */
.rq-st {
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

.rq-st-submitted {
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.rq-st-pending {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.rq-st-under_review {
    background: rgba(139,92,246,0.12);
    border: 1px solid rgba(139,92,246,0.25);
    color: #c4b5fd;
}
.rq-st-approved {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.rq-st-ready_for_pickup {
    background: rgba(20,184,166,0.12);
    border: 1px solid rgba(20,184,166,0.25);
    color: #5eead4;
}
.rq-st-completed {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.rq-st-rejected {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.rq-st-default {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}

/* Priority badges */
.rq-pr {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.rq-pr-urgent {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.rq-pr-high {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.rq-pr-normal {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}

/* Actions cell */
.rq-actions-cell {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

/* Empty */
.rq-empty {
    text-align: center;
    padding: 48px 20px;
}
.rq-empty-ico {
    width: 60px; height: 60px;
    border-radius: 17px;
    background: rgba(100,116,139,0.08);
    border: 1px solid rgba(100,116,139,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--rq-text-dim);
    margin: 0 auto 14px;
}
.rq-empty-txt { font-size: 0.9rem; color: var(--rq-text-muted); margin: 0; }

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.rq-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.rq-reveal.rq-vis { opacity: 1; transform: translateY(0); }

.rq-d1 { transition-delay: 0.05s; }
.rq-d2 { transition-delay: 0.1s; }
.rq-d3 { transition-delay: 0.15s; }
.rq-d4 { transition-delay: 0.2s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .rq-main { padding: 32px 36px; }
}
@media (max-width: 991.98px) {
    .rq-layout { flex-direction: column; }
    .rq-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--rq-border);
    }
    .rq-main { padding: 28px 24px; }
    .rq-head { flex-direction: column; gap: 16px; }
    .rq-stats-row { max-width: 100%; }
}
@media (max-width: 767.98px) {
    .rq-main { padding: 24px 16px; }
    .rq-card { padding: 24px 20px; }
    .rq-title { font-size: 1.4rem; }
}
@media (max-width: 480px) {
    .rq-main { padding: 20px 14px; }
    .rq-card { padding: 20px 16px; border-radius: 16px; }
    .rq-stats-row { flex-direction: column; }
    .rq-stat-pill { width: fit-content; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="rq-page">
    <div class="rq-orb o1"></div>
    <div class="rq-orb o2"></div>
    <div class="rq-orb o3"></div>

    <div class="rq-layout">
        <!-- Sidebar -->
        <div class="rq-sidebar">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <div class="rq-main">

            <!-- Header -->
            <div class="rq-head rq-reveal rq-d1">
                <div class="rq-head-left">
                    <div class="rq-badge">
                        <span class="rq-dot"></span>
                        Workflow
                    </div>
                    <h1 class="rq-title">Application <span>Requests</span></h1>
                    <p class="rq-desc">Review and route resident applications through the official barangay workflow.</p>
                </div>
                <div class="rq-stats-row rq-reveal rq-d2">
                    <div class="rq-stat-pill rq-stat-total">
                        <i class="bi bi-collection"></i>
                        Total: <?php echo (int) ($stats['total'] ?? 0); ?>
                    </div>
                    <div class="rq-stat-pill rq-stat-pending">
                        <i class="bi bi-hourglass-split"></i>
                        Pending: <?php echo (int) (($stats['submitted'] ?? 0) + ($stats['pending'] ?? 0)); ?>
                    </div>
                    <div class="rq-stat-pill rq-stat-review">
                        <i class="bi bi-eye"></i>
                        Review: <?php echo (int) ($stats['under_review'] ?? 0); ?>
                    </div>
                    <div class="rq-stat-pill rq-stat-approved">
                        <i class="bi bi-check-circle"></i>
                        Approved: <?php echo (int) ($stats['approved'] ?? 0); ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="rq-alert rq-reveal rq-d2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="rq-card rq-reveal rq-d2">
                <div class="rq-card-hd">
                    <div class="rq-card-ico" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <div>
                        <h5 class="rq-card-tt">Filter Applications</h5>
                        <p class="rq-card-st">Narrow down results by status or priority level.</p>
                    </div>
                </div>

                <form method="get">
                    <div class="rq-filter-grid">
                        <div>
                            <label class="rq-label"><i class="bi bi-flag"></i> Status</label>
                            <select name="status" class="rq-select">
                                <option value="">All Statuses</option>
                                <option value="submitted" <?php echo $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="under_review" <?php echo $statusFilter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="ready_for_pickup" <?php echo $statusFilter === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="rq-label"><i class="bi bi-exclamation-triangle"></i> Priority</label>
                            <select name="priority" class="rq-select">
                                <option value="">All Priorities</option>
                                <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="normal" <?php echo $priorityFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="rq-btn rq-btn-amber">
                                <i class="bi bi-filter"></i>
                                <span>Filter</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Applications Table -->
            <div class="rq-card rq-reveal rq-d3">
                <div class="rq-card-hd">
                    <div class="rq-card-ico" style="background:rgba(245,158,11,0.12); color:#f59e0b;">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                    <div>
                        <h5 class="rq-card-tt">Applications</h5>
                        <p class="rq-card-st"><?php echo count($applications); ?> application<?php echo count($applications) !== 1 ? 's' : ''; ?> found. Sorted by priority then date.</p>
                    </div>
                </div>

                <?php if (!empty($applications)): ?>
                    <div class="rq-table-wrap">
                        <table class="rq-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Resident</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <?php
                                        $stClass = match($app['status'] ?? '') {
                                            'submitted'        => 'rq-st-submitted',
                                            'pending'          => 'rq-st-pending',
                                            'under_review'     => 'rq-st-under_review',
                                            'approved'         => 'rq-st-approved',
                                            'ready_for_pickup' => 'rq-st-ready_for_pickup',
                                            'completed'        => 'rq-st-completed',
                                            'rejected'         => 'rq-st-rejected',
                                            default            => 'rq-st-default'
                                        };
                                        $stIcon = match($app['status'] ?? '') {
                                            'submitted'        => 'bi-send-fill',
                                            'pending'          => 'bi-clock-fill',
                                            'under_review'     => 'bi-eye-fill',
                                            'approved'         => 'bi-check-circle-fill',
                                            'ready_for_pickup' => 'bi-bag-check-fill',
                                            'completed'        => 'bi-check-all',
                                            'rejected'         => 'bi-x-circle-fill',
                                            default            => 'bi-circle'
                                        };
                                        $prClass = match($app['priority'] ?? 'normal') {
                                            'urgent' => 'rq-pr-urgent',
                                            'high'   => 'rq-pr-high',
                                            default  => 'rq-pr-normal'
                                        };
                                        $prIcon = match($app['priority'] ?? 'normal') {
                                            'urgent' => 'bi-exclamation-triangle-fill',
                                            'high'   => 'bi-chevron-double-up',
                                            default  => 'bi-dash'
                                        };
                                    ?>
                                    <tr>
                                        <td><span class="rq-cell-id">#<?php echo (int) $app['id']; ?></span></td>
                                        <td><span class="rq-cell-name"><?php echo e($app['full_name'] ?? 'Unknown'); ?></span></td>
                                        <td><span class="rq-cell-type"><?php echo e($app['application_type']); ?></span></td>
                                        <td>
                                            <span class="rq-pr <?php echo $prClass; ?>">
                                                <i class="bi <?php echo $prIcon; ?>"></i>
                                                <?php echo e($app['priority'] ?? 'normal'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="rq-st <?php echo $stClass; ?>">
                                                <i class="bi <?php echo $stIcon; ?>"></i>
                                                <?php echo e(str_replace('_', ' ', $app['status'])); ?>
                                            </span>
                                        </td>
                                        <td><span class="rq-cell-date"><?php echo date('M d, Y', strtotime($app['created_at'])); ?></span></td>
                                        <td>
                                            <form method="post" class="rq-actions-cell">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="application_id" value="<?php echo (int) $app['id']; ?>">
                                                <?php if (in_array($app['status'], ['submitted', 'pending'])): ?>
                                                    <button type="submit" name="action" value="review" class="rq-act rq-act-review">
                                                        <i class="bi bi-eye"></i> Review
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($app['status'] === 'under_review'): ?>
                                                    <button type="submit" name="action" value="approve" class="rq-act rq-act-approve">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="rq-act rq-act-reject">
                                                        <i class="bi bi-x-lg"></i> Reject
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($app['status'] === 'approved'): ?>
                                                    <button type="submit" name="action" value="ready" class="rq-act rq-act-ready">
                                                        <i class="bi bi-bag-check"></i> Ready
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                    <button type="submit" name="action" value="complete" class="rq-act rq-act-complete">
                                                        <i class="bi bi-check-all"></i> Complete
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($app['status'] === 'rejected'): ?>
                                                    <button type="submit" name="action" value="pending" class="rq-act rq-act-pending">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Pending
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($applications)): ?>
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($paginator); ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="rq-empty">
                        <div class="rq-empty-ico"><i class="bi bi-inbox"></i></div>
                        <p class="rq-empty-txt">No applications match your current filters.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.rq-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('rq-vis');
        });
    }, 60);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>