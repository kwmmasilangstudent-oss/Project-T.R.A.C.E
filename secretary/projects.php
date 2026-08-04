<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary', 'admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$isAdmin = getCurrentRole() === 'admin';

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $objectives = trim($_POST['objectives'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status = trim($_POST['status'] ?? 'planned');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $progressPercent = (int) ($_POST['progress_percent'] ?? 0);
    $budgetAmount = (float) ($_POST['budget_amount'] ?? 0);
    $budgetSource = trim($_POST['budget_source'] ?? '');

    if ($title) {
        $stmt = $pdo->prepare('INSERT INTO projects (title, description, objectives, category, location, status, start_date, end_date, progress_percent, approved_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $description, $objectives, $category, $location, $status, $startDate ?: null, $endDate ?: null, $progressPercent, $isAdmin ? $_SESSION['user_id'] : null]);
        $projectId = (int) $pdo->lastInsertId();

        if ($budgetAmount > 0) {
            $budgetStmt = $pdo->prepare('INSERT INTO project_budget (project_id, amount, source, type, description) VALUES (?, ?, ?, ?, ?)');
            $budgetStmt->execute([$projectId, $budgetAmount, $budgetSource ?: 'Barangay Fund', 'allocation', 'Initial budget allocation']);
            if ($isAdmin) {
                $pdo->prepare('UPDATE projects SET approved_at = NOW() WHERE id = ?')->execute([$projectId]);
            }
        }
        $_SESSION['_flash_success'] = 'Project created successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$paginator = paginate(
    'SELECT COUNT(*) FROM projects p',
    [],
    'SELECT p.*,
        (SELECT amount FROM project_budget WHERE project_id = p.id ORDER BY created_at ASC LIMIT 1) as budget_amount,
        (SELECT SUM(amount) FROM project_budget WHERE project_id = p.id) as total_budget
        FROM projects p ORDER BY p.created_at DESC',
    []
);
$projects = $paginator['data'];

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
    --pj-accent: #10b981;
    --pj-accent-dark: #059669;
    --pj-teal: #14b8a6;
    --pj-sky: #0ea5e9;
    --pj-amber: #f59e0b;
    --pj-red: #ef4444;
    --pj-violet: #8b5cf6;
    --pj-rose: #f43f5e;
    --pj-bg: #0f172a;
    --pj-card: rgba(255,255,255,0.03);
    --pj-text: #f0f4f8;
    --pj-text-sec: #94a3b8;
    --pj-text-muted: #64748b;
    --pj-text-dim: #475569;
    --pj-border: rgba(255,255,255,0.08);
    --pj-border-lt: rgba(255,255,255,0.12);
    --pj-rad: 12px;
    --pj-rad-lg: 16px;
    --pj-rad-xl: 20px;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--pj-bg) !important;
    color: var(--pj-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.pj-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.pj-page::after {
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

.pj-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: pjFloat 22s ease-in-out infinite;
}
.pj-orb.o1 { width: 460px; height: 460px; background: rgba(20,184,166,0.06); top: -14%; left: -8%; }
.pj-orb.o2 { width: 340px; height: 340px; background: rgba(245,158,11,0.05); bottom: -10%; right: -6%; animation-delay: -11s; }
.pj-orb.o3 { width: 250px; height: 250px; background: rgba(139,92,246,0.05); top: 50%; right: 30%; animation-delay: -5s; animation-duration: 26s; }

@keyframes pjFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.pj-page { min-height: 100vh; position: relative; z-index: 1; }

.pj-layout { display: flex; min-height: 100vh; }

.pj-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--pj-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.pj-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.pj-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.pj-head-left { flex: 1; min-width: 260px; }

.pj-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(20,184,166,0.12);
    border: 1px solid rgba(20,184,166,0.25);
    border-radius: 100px;
    color: #5eead4;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
    width: fit-content;
}

.pj-badge .pj-dot {
    width: 7px; height: 7px;
    background: var(--pj-teal);
    border-radius: 50%;
    animation: pjPulse 2s ease-in-out infinite;
}

@keyframes pjPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.pj-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.pj-title span {
    background: linear-gradient(135deg, var(--pj-teal), #2dd4bf);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.pj-desc {
    font-size: 0.92rem;
    color: var(--pj-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

.pj-count-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(20,184,166,0.10);
    border: 1px solid rgba(20,184,166,0.25);
    border-radius: 100px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #5eead4;
    white-space: nowrap;
}
.pj-count-pill i { font-size: 0.9rem; }

/* ═══════════════════════════════
   ALERT
   ═══════════════════════════════ */
.pj-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: var(--pj-rad);
    color: #6ee7b7;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: pjSlide 0.4s ease;
}
.pj-alert i { font-size: 1.15rem; color: var(--pj-accent); flex-shrink: 0; }

@keyframes pjSlide {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.pj-card {
    background: var(--pj-card);
    border: 1px solid var(--pj-border);
    border-radius: var(--pj-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.pj-card:hover { border-color: rgba(255,255,255,0.12); }

.pj-card-hd {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.pj-card-ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.pj-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.pj-card-st {
    font-size: 0.82rem;
    color: var(--pj-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* ═══════════════════════════════
   FORMS
   ═══════════════════════════════ */
.pj-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.pj-label i { font-size: 0.82rem; color: var(--pj-text-muted); }

.pj-input,
.pj-select,
.pj-textarea {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--pj-rad);
    font-size: 0.88rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}

.pj-input::placeholder,
.pj-textarea::placeholder { color: #475569; }

.pj-input:focus,
.pj-select:focus,
.pj-textarea:focus {
    border-color: var(--pj-teal);
    box-shadow: 0 0 0 3px rgba(20,184,166,0.15);
    background: rgba(255,255,255,0.07);
}

.pj-input[type="date"] { color-scheme: dark; }

.pj-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}
.pj-select option { background: #1e293b; color: #e2e8f0; }

.pj-textarea { resize: vertical; min-height: 80px; }

/* ═══════════════════════════════
   FORM GRID
   ═══════════════════════════════ */
.pj-fg {
    display: grid;
    gap: 18px;
}
.pj-fg-3 { grid-template-columns: 1fr 1fr 1fr; }
.pj-fg-2 { grid-template-columns: 1fr 1fr; }
.pj-fg .pj-s2 { grid-column: span 2; }
.pj-fg .pj-s3 { grid-column: span 3; }

@media (max-width: 991px) {
    .pj-fg-3 { grid-template-columns: 1fr 1fr; }
    .pj-fg-3 .pj-s2,
    .pj-fg-3 .pj-s3 { grid-column: span 2; }
}
@media (max-width: 640px) {
    .pj-fg-3,
    .pj-fg-2 { grid-template-columns: 1fr; }
    .pj-fg-3 .pj-s2,
    .pj-fg-3 .pj-s3 { grid-column: span 1; }
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.pj-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--pj-rad);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}
.pj-btn i { transition: transform 0.2s ease; }

.pj-btn-teal {
    background: linear-gradient(135deg, var(--pj-teal), #0d9488);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(20,184,166,0.25);
}
.pj-btn-teal:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(20,184,166,0.35);
    color: #ffffff;
}
.pj-btn-teal:active { transform: translateY(0); }
.pj-btn-teal:hover i { transform: translateX(3px); }

.pj-btn-approve {
    padding: 5px 12px;
    font-size: 0.73rem;
    font-weight: 600;
    border-radius: 8px;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.pj-btn-approve:hover {
    background: rgba(16,185,129,0.2);
    color: #a7f3d0;
}

/* ═══════════════════════════════
   TABLE
   ═══════════════════════════════ */
.pj-table-wrap {
    overflow-x: auto;
    border-radius: var(--pj-rad-lg);
    border: 1px solid rgba(255,255,255,0.04);
}

.pj-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    min-width: 780px;
}

.pj-table thead th {
    padding: 14px 16px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: var(--pj-text-sec);
    font-weight: 600;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    white-space: nowrap;
}

.pj-table tbody td {
    padding: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    color: #e2e8f0;
    vertical-align: middle;
}

.pj-table tbody tr:last-child td { border-bottom: none; }
.pj-table tbody tr { transition: background 0.2s ease; }
.pj-table tbody tr:hover { background: rgba(255,255,255,0.03); }

.pj-table .pj-proj-title {
    font-weight: 700;
    font-size: 0.9rem;
    color: #f1f5f9;
}

.pj-table .pj-proj-loc {
    font-size: 0.78rem;
    color: var(--pj-text-dim);
    margin-top: 2px;
}

.pj-table .pj-cat {
    font-size: 0.82rem;
    color: var(--pj-text-sec);
}

.pj-table .pj-timeline {
    font-size: 0.82rem;
    color: var(--pj-text-muted);
    white-space: nowrap;
}

.pj-table .pj-timeline .pj-sep {
    margin: 0 5px;
    color: var(--pj-text-dim);
}

.pj-table .pj-budget-val {
    font-weight: 700;
    font-size: 0.88rem;
    color: #6ee7b7;
    font-variant-numeric: tabular-nums;
}

.pj-table .pj-budget-na {
    font-size: 0.82rem;
    color: var(--pj-text-dim);
}

/* Progress bar */
.pj-progress-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 120px;
}

.pj-progress-track {
    flex: 1;
    height: 7px;
    background: rgba(255,255,255,0.06);
    border-radius: 100px;
    overflow: hidden;
}

.pj-progress-fill {
    height: 100%;
    border-radius: 100px;
    transition: width 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    background: linear-gradient(90deg, var(--pj-teal), #2dd4bf);
}

.pj-progress-fill.complete {
    background: linear-gradient(90deg, var(--pj-accent), #34d399);
}

.pj-progress-pct {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--pj-text-sec);
    min-width: 32px;
    text-align: right;
    font-variant-numeric: tabular-nums;
}

/* Status badges */
.pj-st {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.73rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.pj-st-planned {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}
.pj-st-ongoing {
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.pj-st-completed {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.pj-st-default {
    background: rgba(139,92,246,0.12);
    border: 1px solid rgba(139,92,246,0.25);
    color: #c4b5fd;
}

/* Status + approve cell */
.pj-status-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Empty */
.pj-empty {
    text-align: center;
    padding: 48px 20px;
}
.pj-empty-ico {
    width: 60px; height: 60px;
    border-radius: 17px;
    background: rgba(100,116,139,0.08);
    border: 1px solid rgba(100,116,139,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--pj-text-dim);
    margin: 0 auto 14px;
}
.pj-empty-txt { font-size: 0.9rem; color: var(--pj-text-muted); margin: 0; }

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.pj-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.pj-reveal.pj-vis { opacity: 1; transform: translateY(0); }

.pj-d1 { transition-delay: 0.05s; }
.pj-d2 { transition-delay: 0.1s; }
.pj-d3 { transition-delay: 0.15s; }
.pj-d4 { transition-delay: 0.2s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .pj-main { padding: 32px 36px; }
}
@media (max-width: 991.98px) {
    .pj-layout { flex-direction: column; }
    .pj-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--pj-border);
    }
    .pj-main { padding: 28px 24px; }
    .pj-head { flex-direction: column; gap: 16px; }
}
@media (max-width: 767.98px) {
    .pj-main { padding: 24px 16px; }
    .pj-card { padding: 24px 20px; }
    .pj-title { font-size: 1.4rem; }
}
@media (max-width: 480px) {
    .pj-main { padding: 20px 14px; }
    .pj-card { padding: 20px 16px; border-radius: 16px; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="pj-page">
    <div class="pj-orb o1"></div>
    <div class="pj-orb o2"></div>
    <div class="pj-orb o3"></div>

    <div class="pj-layout">
        <!-- Sidebar -->
        <div class="pj-sidebar">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <div class="pj-main">

            <!-- Header -->
            <div class="pj-head pj-reveal pj-d1">
                <div class="pj-head-left">
                    <div class="pj-badge">
                        <span class="pj-dot"></span>
                        Projects
                    </div>
                    <h1 class="pj-title">Project <span>Management</span></h1>
                    <p class="pj-desc">Create and track barangay projects with timelines, budgets, objectives, and progress monitoring.</p>
                </div>
                <div class="pj-count-pill pj-reveal pj-d2">
                    <i class="bi bi-kanban-fill"></i>
                    <?php echo count($projects); ?> Project<?php echo count($projects) !== 1 ? 's' : ''; ?>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="pj-alert pj-reveal pj-d2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Create Project -->
            <div class="pj-card pj-reveal pj-d2">
                <div class="pj-card-hd">
                    <div class="pj-card-ico" style="background:rgba(20,184,166,0.12); color:#14b8a6;">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <div>
                        <h5 class="pj-card-tt">Create New Project</h5>
                        <p class="pj-card-st">Define the project scope, timeline, location, and initial budget allocation.</p>
                    </div>
                </div>

                <form method="post">
                    <?php echo csrfField(); ?>
                    <!-- Row 1: Title / Category / Status -->
                    <div class="pj-fg pj-fg-3">
                        <div>
                            <label class="pj-label"><i class="bi bi-type"></i> Project Title</label>
                            <input type="text" name="title" class="pj-input" placeholder="Enter project title..." required>
                        </div>
                        <div>
                            <label class="pj-label"><i class="bi bi-tag"></i> Category</label>
                            <select name="category" class="pj-select">
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Health">Health</option>
                                <option value="Education">Education</option>
                                <option value="Environment">Environment</option>
                                <option value="Social">Social</option>
                                <option value="Economic">Economic</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="pj-label"><i class="bi bi-flag"></i> Status</label>
                            <select name="status" class="pj-select">
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Start / End / Location -->
                    <div class="pj-fg pj-fg-3" style="margin-top: 18px;">
                        <div>
                            <label class="pj-label"><i class="bi bi-calendar-event"></i> Start Date</label>
                            <input type="date" name="start_date" class="pj-input">
                        </div>
                        <div>
                            <label class="pj-label"><i class="bi bi-calendar-check"></i> End Date</label>
                            <input type="date" name="end_date" class="pj-input">
                        </div>
                        <div>
                            <label class="pj-label"><i class="bi bi-geo-alt"></i> Location</label>
                            <input type="text" name="location" class="pj-input" placeholder="e.g. Barangay Hall">
                        </div>
                    </div>

                    <!-- Row 3: Objectives full-width -->
                    <div class="pj-fg" style="margin-top: 18px;">
                        <div>
                            <label class="pj-label"><i class="bi bi-bullseye"></i> Objectives</label>
                            <textarea name="objectives" class="pj-textarea" rows="2" placeholder="List the key objectives of this project..."></textarea>
                        </div>
                    </div>

                    <!-- Row 4: Description / Progress / Budget -->
                    <div class="pj-fg pj-fg-3" style="margin-top: 18px;">
                        <div>
                            <label class="pj-label"><i class="bi bi-text-left"></i> Description</label>
                            <textarea name="description" class="pj-textarea" rows="2" placeholder="Brief project description..."></textarea>
                        </div>
                        <div>
                            <label class="pj-label"><i class="bi bi-graph-up-arrow"></i> Progress (%)</label>
                            <input type="number" name="progress_percent" class="pj-input" min="0" max="100" value="0" placeholder="0">
                        </div>
                        <div>
                            <label class="pj-label"><i class="bi bi-cash-stack"></i> Budget Amount (₱)</label>
                            <input type="number" step="0.01" name="budget_amount" class="pj-input" placeholder="0.00">
                        </div>
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="submit" class="pj-btn pj-btn-teal">
                            <i class="bi bi-save"></i>
                            <span>Save Project</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Projects Table -->
            <div class="pj-card pj-reveal pj-d3">
                <div class="pj-card-hd">
                    <div class="pj-card-ico" style="background:rgba(245,158,11,0.12); color:#f59e0b;">
                        <i class="bi bi-kanban"></i>
                    </div>
                    <div>
                        <h5 class="pj-card-tt">All Projects</h5>
                        <p class="pj-card-st"><?php echo count($projects); ?> project<?php echo count($projects) !== 1 ? 's' : ''; ?> tracked.</p>
                    </div>
                </div>

                <?php if (!empty($projects)): ?>
                    <div class="pj-table-wrap">
                        <table class="pj-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Category</th>
                                    <th>Timeline</th>
                                    <th>Budget</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <div class="pj-proj-title"><?php echo e($project['title']); ?></div>
                                            <?php if ($project['location']): ?>
                                                <div class="pj-proj-loc">
                                                    <i class="bi bi-geo-alt" style="margin-right:3px;"></i><?php echo e($project['location']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="pj-cat"><?php echo e($project['category'] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($project['start_date'] || $project['end_date']): ?>
                                                <span class="pj-timeline">
                                                    <?php echo $project['start_date'] ? date('M d', strtotime($project['start_date'])) : '...'; ?>
                                                    <span class="pj-sep">&rarr;</span>
                                                    <?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '...'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="pj-timeline" style="color:var(--pj-text-dim);">No timeline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($project['total_budget']): ?>
                                                <span class="pj-budget-val">&<?php echo number_format($project['total_budget'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="pj-budget-na">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="pj-progress-wrap">
                                                <div class="pj-progress-track">
                                                    <div class="pj-progress-fill<?php echo (int) $project['progress_percent'] >= 100 ? ' complete' : ''; ?>" style="width: <?php echo min(100, max(0, (int) $project['progress_percent'])); ?>%;"></div>
                                                </div>
                                                <span class="pj-progress-pct"><?php echo (int) $project['progress_percent']; ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="pj-status-cell">
                                                <?php
                                                    $stClass = match($project['status'] ?? 'planned') {
                                                        'planned'   => 'pj-st-planned',
                                                        'ongoing'   => 'pj-st-ongoing',
                                                        'completed' => 'pj-st-completed',
                                                        default     => 'pj-st-default'
                                                    };
                                                    $stIcon = match($project['status'] ?? 'planned') {
                                                        'planned'   => 'bi-clock-fill',
                                                        'ongoing'   => 'bi-arrow-repeat',
                                                        'completed' => 'bi-check-circle-fill',
                                                        default     => 'bi-circle'
                                                    };
                                                ?>
                                                <span class="pj-st <?php echo $stClass; ?>">
                                                    <i class="bi <?php echo $stIcon; ?>"></i>
                                                    <?php echo e($project['status'] ?? 'planned'); ?>
                                                </span>
                                                <?php if ($isAdmin && !$project['approved_by']): ?>
                                                    <form method="post" class="d-inline">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="project_id" value="<?php echo (int) $project['id']; ?>">
                                                        <button type="submit" name="action" value="approve_project" class="pj-btn-approve">
                                                            <i class="bi bi-check-lg"></i> Approve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($projects)): ?>
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($paginator); ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="pj-empty">
                        <div class="pj-empty-ico"><i class="bi bi-inbox"></i></div>
                        <p class="pj-empty-txt">No projects found. Create your first project above.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.pj-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('pj-vis');
        });
    }, 60);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>