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
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($appointmentId > 0 && $action) {
        $status = null;

        if ($action === 'approve') {
            $status = 'approved';
        } elseif ($action === 'reject') {
            $status = 'rejected';
        } elseif ($action === 'complete') {
            $status = 'completed';
        } elseif ($action === 'pending') {
            $status = 'pending';
        } elseif ($action === 'cancel') {
            $status = 'cancelled';
        }

        if ($status) {
            $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?')->execute([$status, $appointmentId]);

            $apptRow = $pdo->prepare('SELECT a.*, r.full_name, r.user_id FROM appointments a LEFT JOIN residents r ON r.id = a.resident_id WHERE a.id = ? LIMIT 1');
            $apptRow->execute([$appointmentId]);
            $apptData = $apptRow->fetch();

            if ($apptData && !empty($apptData['user_id'])) {
                $statusLabel = ucfirst($status);
                createNotification(
                    (int) $apptData['user_id'],
                    'Your appointment on ' . date('M d, Y', strtotime($apptData['appointment_date'])) . ' has been ' . $statusLabel . '.',
                    defined('BASE_URL') ? BASE_URL . '/resident/appointments.php' : '/resident/appointments.php',
                    (int) ($_SESSION['user_id'] ?? 0)
                );
            }

            logAudit('update_appointment', 'Appointment #' . $appointmentId . ' status: ' . $status);
            $_SESSION['_flash_success'] = 'Appointment updated successfully.';
        } else {
            $_SESSION['_flash_error'] = 'Invalid action.';
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = '';
$params = [];
if ($statusFilter) {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
}
if ($search) {
    $where .= ' AND (r.full_name LIKE ? OR a.purpose LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$orderBy = ' ORDER BY a.appointment_date DESC, a.created_at DESC';

$paginator = paginate(
    'SELECT COUNT(*) FROM appointments a LEFT JOIN residents r ON r.id = a.resident_id WHERE 1=1' . $where,
    $params,
    'SELECT a.*, r.full_name FROM appointments a LEFT JOIN residents r ON r.id = a.resident_id WHERE 1=1' . $where . $orderBy,
    $params
);
$appointments = $paginator['data'];

$stats = $pdo->query('SELECT
    COUNT(*) as total,
    SUM(status = "pending") as pending,
    SUM(status = "approved") as approved,
    SUM(status = "completed") as completed,
    SUM(status = "rejected") as rejected,
    SUM(status = "cancelled") as cancelled
    FROM appointments')->fetch();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
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

.rq-stats-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: flex-start;
    max-width: 500px;
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
.rq-stat-approved {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.rq-stat-completed {
    background: rgba(139,92,246,0.10);
    border: 1px solid rgba(139,92,246,0.25);
    color: #c4b5fd;
}

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

.rq-input {
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
}
.rq-input::placeholder { color: var(--rq-text-dim); }
.rq-input:focus {
    border-color: var(--rq-amber);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
    background-color: rgba(255,255,255,0.07);
}

.rq-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

@media (max-width: 767px) {
    .rq-filter-grid { grid-template-columns: 1fr; }
}

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

.rq-act-cancel {
    background: rgba(148,163,184,0.10);
    border-color: rgba(148,163,184,0.25);
    color: #94a3b8;
}
.rq-act-cancel:hover { background: rgba(148,163,184,0.18); color: #cbd5e1; }

.rq-table-wrap {
    overflow-x: auto;
    border-radius: var(--rq-rad-lg);
    border: 1px solid rgba(255,255,255,0.04);
}

.rq-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.84rem;
    min-width: 780px;
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

.rq-cell-id {
    font-weight: 700;
    font-size: 0.82rem;
    color: #f1f5f9;
    font-variant-numeric: tabular-nums;
}

.rq-cell-name {
    font-weight: 600;
    color: #e2e8f0;
}

.rq-cell-date {
    font-size: 0.8rem;
    color: var(--rq-text-dim);
    white-space: nowrap;
}

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

.rq-st-pending {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.rq-st-approved {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.rq-st-completed {
    background: rgba(139,92,246,0.12);
    border: 1px solid rgba(139,92,246,0.25);
    color: #c4b5fd;
}
.rq-st-rejected {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.rq-st-cancelled {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}
.rq-st-default {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}

.rq-actions-cell {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

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

<div class="rq-page">
    <div class="rq-orb o1"></div>
    <div class="rq-orb o2"></div>
    <div class="rq-orb o3"></div>

    <div class="rq-layout">
        <div class="rq-sidebar">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <div class="rq-main">

            <div class="rq-head rq-reveal rq-d1">
                <div class="rq-head-left">
                    <div class="rq-badge">
                        <span class="rq-dot"></span>
                        Scheduling
                    </div>
                    <h1 class="rq-title">Appointment <span>Requests</span></h1>
                    <p class="rq-desc">Review and manage resident appointment requests for barangay hall visits.</p>
                </div>
                <div class="rq-stats-row rq-reveal rq-d2">
                    <div class="rq-stat-pill rq-stat-total">
                        <i class="bi bi-collection"></i>
                        Total: <?php echo (int) ($stats['total'] ?? 0); ?>
                    </div>
                    <div class="rq-stat-pill rq-stat-pending">
                        <i class="bi bi-hourglass-split"></i>
                        Pending: <?php echo (int) ($stats['pending'] ?? 0); ?>
                    </div>
                    <div class="rq-stat-pill rq-stat-approved">
                        <i class="bi bi-check-circle"></i>
                        Approved: <?php echo (int) ($stats['approved'] ?? 0); ?>
                    </div>
                    <div class="rq-stat-pill rq-stat-completed">
                        <i class="bi bi-check-all"></i>
                        Completed: <?php echo (int) ($stats['completed'] ?? 0); ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="rq-alert rq-reveal rq-d2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="rq-alert rq-reveal rq-d2" style="background:rgba(239,68,68,0.10); border-color:rgba(239,68,68,0.25); color:#fca5a5;">
                    <i class="bi bi-exclamation-triangle-fill" style="color:var(--rq-red);"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            <?php endif; ?>

            <div class="rq-card rq-reveal rq-d2">
                <div class="rq-card-hd">
                    <div class="rq-card-ico" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <div>
                        <h5 class="rq-card-tt">Filter Appointments</h5>
                        <p class="rq-card-st">Narrow down results by status or search by resident name.</p>
                    </div>
                </div>

                <form method="get">
                    <div class="rq-filter-grid">
                        <div>
                            <label class="rq-label"><i class="bi bi-flag"></i> Status</label>
                            <select name="status" class="rq-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="rq-label"><i class="bi bi-search"></i> Search</label>
                            <input type="text" name="search" class="rq-input" placeholder="Search resident or purpose..." value="<?php echo e($search); ?>">
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

            <div class="rq-card rq-reveal rq-d3">
                <div class="rq-card-hd">
                    <div class="rq-card-ico" style="background:rgba(245,158,11,0.12); color:#f59e0b;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <h5 class="rq-card-tt">Appointments</h5>
                        <p class="rq-card-st"><?php echo count($appointments); ?> appointment<?php echo count($appointments) !== 1 ? 's' : ''; ?> found. Sorted by date.</p>
                    </div>
                </div>

                <?php if (!empty($appointments)): ?>
                    <div class="rq-table-wrap">
                        <table class="rq-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Resident</th>
                                    <th>Date</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Booked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appt):
                                    $stClass = match($appt['status'] ?? '') {
                                        'pending'   => 'rq-st-pending',
                                        'approved'  => 'rq-st-approved',
                                        'completed' => 'rq-st-completed',
                                        'rejected'  => 'rq-st-rejected',
                                        'cancelled' => 'rq-st-cancelled',
                                        default     => 'rq-st-default'
                                    };
                                    $stIcon = match($appt['status'] ?? '') {
                                        'pending'   => 'bi-clock-fill',
                                        'approved'  => 'bi-check-circle-fill',
                                        'completed' => 'bi-check-all',
                                        'rejected'  => 'bi-x-circle-fill',
                                        'cancelled' => 'bi-dash-circle-fill',
                                        default     => 'bi-circle'
                                    };
                                ?>
                                    <tr>
                                        <td><span class="rq-cell-id">#<?php echo (int) $appt['id']; ?></span></td>
                                        <td><span class="rq-cell-name"><?php echo e($appt['full_name'] ?? 'Unknown'); ?></span></td>
                                        <td><span class="rq-cell-date"><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></span></td>
                                        <td><?php echo e($appt['purpose']); ?></td>
                                        <td>
                                            <span class="rq-st <?php echo $stClass; ?>">
                                                <i class="bi <?php echo $stIcon; ?>"></i>
                                                <?php echo e(ucwords($appt['status'])); ?>
                                            </span>
                                        </td>
                                        <td><span class="rq-cell-date"><?php echo date('M d, Y', strtotime($appt['created_at'])); ?></span></td>
                                        <td>
                                            <form method="post" class="rq-actions-cell">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="appointment_id" value="<?php echo (int) $appt['id']; ?>">
                                                <?php if ($appt['status'] === 'pending'): ?>
                                                    <button type="submit" name="action" value="approve" class="rq-act rq-act-approve">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="rq-act rq-act-reject">
                                                        <i class="bi bi-x-lg"></i> Reject
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($appt['status'] === 'approved'): ?>
                                                    <button type="submit" name="action" value="complete" class="rq-act rq-act-complete">
                                                        <i class="bi bi-check-all"></i> Complete
                                                    </button>
                                                    <button type="submit" name="action" value="cancel" class="rq-act rq-act-cancel">
                                                        <i class="bi bi-x-lg"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($appt['status'] === 'rejected'): ?>
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
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($paginator); ?>
                    </div>
                <?php else: ?>
                    <div class="rq-empty">
                        <div class="rq-empty-ico"><i class="bi bi-calendar-x"></i></div>
                        <p class="rq-empty-txt">No appointments match your current filters.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.rq-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('rq-vis');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
