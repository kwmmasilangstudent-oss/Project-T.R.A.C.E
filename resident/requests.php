<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['resident']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

$residentId = null;
$resident = null;
try {
    $residentStmt = $pdo->prepare('SELECT id FROM residents WHERE user_id = ? LIMIT 1');
    $residentStmt->execute([$_SESSION['user_id']]);
    $resident = $residentStmt->fetch();
} catch (Throwable $e) {
    $resident = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resident) {
    requireCsrf();
    $applicationType = trim($_POST['application_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $priority = trim($_POST['priority'] ?? 'normal');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($applicationType) {
        try {
            $stmt = $pdo->prepare('INSERT INTO applications (resident_id, application_type, purpose, priority, status, remarks) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$resident['id'], $applicationType, $purpose, $priority, 'submitted', $remarks]);
            notifyApplicationSubmitted((int) $pdo->lastInsertId());
            $_SESSION['_flash_success'] = 'Your request has been submitted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Throwable $e) {
            $_SESSION['_flash_error'] = 'Failed to submit request. Please try again later.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$paginator = [];
$applications = [];
if ($resident) {
    try {
        $paginator = paginate(
            'SELECT COUNT(*) FROM applications WHERE resident_id = ?',
            [$resident['id']],
            'SELECT * FROM applications WHERE resident_id = ? ORDER BY created_at DESC',
            [$resident['id']]
        );
        $applications = $paginator['data'];
    } catch (Throwable $e) {
        $paginator = [];
        $applications = [];
    }
}

$requestTypes = [
    'Barangay Clearance',
    'Certificate of Residency',
    'Certificate of Indigency',
    'Business Clearance',
    'First Time Job Seeker',
    'Appointment',
    'Certification',
    'Complaint',
    'Permit',
    'Others'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
/* ═══════════════════════════════
   CUSTOM PROPERTIES
   ═══════════════════════════════ */
:root {
    --sr-primary: #1a56db;
    --sr-accent: #10b981;
    --sr-accent-dark: #059669;
    --sr-accent-glow: rgba(16,185,129,0.15);
    --sr-amber: #f59e0b;
    --sr-red: #ef4444;
    --sr-violet: #8b5cf6;
    --sr-sky: #0ea5e9;
    --sr-bg: #0f172a;
    --sr-surface: rgba(255,255,255,0.04);
    --sr-surface-hover: rgba(255,255,255,0.07);
    --sr-border: rgba(255,255,255,0.08);
    --sr-text: #f1f5f9;
    --sr-text-secondary: #94a3b8;
    --sr-text-muted: #64748b;
    --sr-radius: 12px;
    --sr-radius-lg: 18px;
}

/* ═══════════════════════════════
   GLOBAL
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--sr-bg) !important;
    color: var(--sr-text);
    min-height: 100vh;
}

.navbar, footer, .main-navbar { display: none !important; }

body::after {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
.sr-grid-overlay {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

.sr-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: srFloat 22s ease-in-out infinite;
}

.sr-orb.o1 { width: 500px; height: 500px; background: rgba(16,185,129,0.06); top: -12%; left: -10%; }
.sr-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -8%; right: -5%; animation-delay: -12s; }
.sr-orb.o3 { width: 260px; height: 260px; background: rgba(14,165,233,0.05); top: 45%; left: 50%; animation-delay: -6s; animation-duration: 28s; }

@keyframes srFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(30px, -20px) scale(1.04); }
    66%      { transform: translate(-20px, 15px) scale(0.96); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.sr-page-wrapper {
    position: relative;
    z-index: 1;
    min-height: 100vh;
}

.sr-page-wrapper .container-fluid { padding: 0; }
.sr-page-wrapper .row { margin: 0; min-height: 100vh; }

/* ═══════════════════════════════
   SIDEBAR
   ═══════════════════════════════ */
.sr-sidebar-col {
    background: rgba(15,23,42,0.60);
    backdrop-filter: blur(30px);
    border-right: 1px solid var(--sr-border);
    padding: 0 !important;
    min-height: 100vh;
}

.sr-sidebar-col .sidebar,
.sr-sidebar-col .sidebar-menu,
.sr-sidebar-col .sidebar-header,
.sr-sidebar-col .sidebar-nav,
.sr-sidebar-col ul,
.sr-sidebar-col li,
.sr-sidebar-col a {
    background: transparent !important;
    color: var(--sr-text-secondary) !important;
}

.sr-sidebar-col a:hover,
.sr-sidebar-col .active a,
.sr-sidebar-col .active {
    background: var(--sr-surface-hover) !important;
    color: var(--sr-text) !important;
}

.sr-sidebar-col .sidebar-header h4,
.sr-sidebar-col .sidebar-header h5,
.sr-sidebar-col .sidebar-header h3 {
    color: var(--sr-text) !important;
}

/* ═══════════════════════════════
   MAIN CONTENT
   ═══════════════════════════════ */
.sr-main-col {
    padding: 40px 48px 60px !important;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.sr-page-header { margin-bottom: 32px; }

.sr-page-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.20);
    border-radius: 100px;
    color: #6ee7b7;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
}

.sr-page-badge i { font-size: 0.8rem; }

.sr-page-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.sr-page-title span {
    background: linear-gradient(135deg, var(--sr-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.sr-page-desc {
    font-size: 0.95rem;
    color: var(--sr-text-muted);
    line-height: 1.6;
    max-width: 600px;
}

.sr-page-divider {
    width: 48px;
    height: 3px;
    background: linear-gradient(135deg, var(--sr-accent), #34d399);
    border-radius: 2px;
    margin-top: 20px;
}

/* ═══════════════════════════════
   ALERT
   ═══════════════════════════════ */
.sr-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-radius: var(--sr-radius);
    margin-bottom: 24px;
    font-size: 0.88rem;
    font-weight: 500;
    animation: srSlideIn 0.4s ease;
}

@keyframes srSlideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.sr-alert i { font-size: 1.15rem; flex-shrink: 0; }

.sr-alert-success {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}

.sr-alert-success i { color: var(--sr-accent); }

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.sr-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--sr-border);
    border-radius: var(--sr-radius-lg);
    padding: 32px;
    backdrop-filter: blur(20px);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.sr-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
}

.sr-card:hover {
    border-color: rgba(255,255,255,0.12);
    box-shadow: 0 8px 40px rgba(0,0,0,0.20);
}

.sr-card + .sr-card { margin-top: 24px; }

/* Card header */
.sr-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 18px;
}

.sr-card-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 6px;
}

.sr-card-subtitle {
    font-size: 0.88rem;
    color: var(--sr-text-muted);
    line-height: 1.6;
    margin-bottom: 28px;
}

/* ═══════════════════════════════
   FORM CONTROLS
   ═══════════════════════════════ */
.sr-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}

.sr-label i { font-size: 0.85rem; color: var(--sr-text-muted); }

.sr-input,
.sr-select,
.sr-textarea {
    width: 100%;
    padding: 13px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--sr-radius);
    font-size: 0.92rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
}

.sr-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
    cursor: pointer;
}

.sr-select option {
    background: #1e293b;
    color: #e2e8f0;
}

.sr-textarea {
    resize: vertical;
    min-height: 72px;
}

.sr-input::placeholder,
.sr-textarea::placeholder { color: #475569; }

.sr-input:focus,
.sr-select:focus,
.sr-textarea:focus {
    border-color: var(--sr-accent);
    box-shadow: 0 0 0 3px var(--sr-accent-glow);
    background: rgba(255,255,255,0.07);
}

.sr-form-row {
    display: grid;
    gap: 20px;
    margin-bottom: 20px;
}

.sr-form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.sr-form-row.cols-1 { grid-template-columns: 1fr; }

.sr-form-group {
    display: flex;
    flex-direction: column;
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.sr-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px 28px;
    border: none;
    border-radius: var(--sr-radius);
    font-size: 0.92rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    line-height: 1.4;
}

.sr-btn i { transition: transform 0.2s ease; }

.sr-btn-primary {
    background: linear-gradient(135deg, var(--sr-accent), var(--sr-accent-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
}

.sr-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(16,185,129,0.35);
    color: #ffffff;
}

.sr-btn-primary:active { transform: translateY(0); }
.sr-btn-primary:hover i { transform: translateX(3px); }

/* ═══════════════════════════════
   STATUS BADGES
   ═══════════════════════════════ */
.sr-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 100px;
    font-size: 0.76rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.sr-badge i { font-size: 0.7rem; }

/* Priority badges */
.sr-badge-urgent {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}

.sr-badge-high {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}

.sr-badge-normal {
    background: rgba(148,163,184,0.10);
    border: 1px solid rgba(148,163,184,0.20);
    color: #94a3b8;
}

/* Status badges */
.sr-status {
    background: rgba(148,163,184,0.10);
    border: 1px solid rgba(148,163,184,0.20);
    color: #94a3b8;
}

.sr-status-submitted {
    background: rgba(26,86,219,0.12);
    border: 1px solid rgba(26,86,219,0.25);
    color: #93c5fd;
}

.sr-status-pending {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}

.sr-status-under_review {
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}

.sr-status-approved,
.sr-status-ready_for_pickup,
.sr-status-completed {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}

.sr-status-rejected {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}

/* ═══════════════════════════════
   TABLE
   ═══════════════════════════════ */
.sr-table-wrap {
    overflow-x: auto;
    border-radius: var(--sr-radius);
    border: 1px solid var(--sr-border);
}

.sr-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.sr-table thead th {
    padding: 14px 18px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid var(--sr-border);
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--sr-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
    text-align: left;
}

.sr-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.2s ease;
}

.sr-table tbody tr:last-child { border-bottom: none; }

.sr-table tbody tr:hover {
    background: rgba(255,255,255,0.03);
}

.sr-table tbody td {
    padding: 14px 18px;
    color: #cbd5e1;
    vertical-align: middle;
    white-space: nowrap;
}

.sr-table .col-ref {
    font-weight: 700;
    color: #e2e8f0;
    font-variant-numeric: tabular-nums;
}

.sr-table .col-ref span {
    color: var(--sr-text-muted);
    font-weight: 400;
    margin-right: 2px;
}

.sr-table .col-date {
    font-size: 0.82rem;
    color: var(--sr-text-muted);
}

.sr-table .col-type {
    font-weight: 600;
    color: #e2e8f0;
}

/* Empty state */
.sr-empty {
    text-align: center;
    padding: 48px 24px;
}

.sr-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--sr-accent);
    margin: 0 auto 18px;
}

.sr-empty h6 {
    font-family: 'Playfair Display', serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #e2e8f0;
    margin-bottom: 6px;
}

.sr-empty p {
    font-size: 0.85rem;
    color: var(--sr-text-muted);
    margin: 0;
}

/* ═══════════════════════════════
   REVEAL ANIMATIONS
   ═══════════════════════════════ */
.sr-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.sr-reveal.sr-visible {
    opacity: 1;
    transform: translateY(0);
}

.sr-d1 { transition-delay: 0.05s; }
.sr-d2 { transition-delay: 0.10s; }
.sr-d3 { transition-delay: 0.15s; }
.sr-d4 { transition-delay: 0.20s; }
.sr-d5 { transition-delay: 0.25s; }

/* ═══════════════════════════════
   REQUEST COUNT BADGE
   ═══════════════════════════════ */
.sr-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 24px;
    padding: 0 8px;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.20);
    border-radius: 100px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #6ee7b7;
    margin-left: 10px;
    vertical-align: middle;
}

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 991.98px) {
    .sr-sidebar-col {
        min-height: auto !important;
        border-right: none !important;
        border-bottom: 1px solid var(--sr-border) !important;
    }
    .sr-main-col { padding: 32px 24px 50px !important; }
}

@media (max-width: 767.98px) {
    .sr-main-col { padding: 24px 18px 40px !important; }
    .sr-card { padding: 24px 20px; }
    .sr-form-row.cols-3 { grid-template-columns: 1fr; }
    .sr-page-title { font-size: 1.4rem; }
    .sr-card-title { font-size: 1.15rem; }
    .sr-table thead th { padding: 12px 14px; font-size: 0.72rem; }
    .sr-table tbody td { padding: 12px 14px; font-size: 0.82rem; }
}

@media (max-width: 480px) {
    .sr-main-col { padding: 20px 14px 36px !important; }
    .sr-card { padding: 20px 16px; border-radius: var(--sr-radius); }
    .sr-page-title { font-size: 1.25rem; }
}
</style>

<!-- ═══════════════════════════════════════
     ATMOSPHERIC ELEMENTS
     ═══════════════════════════════════════ -->
<div class="sr-grid-overlay"></div>
<div class="sr-orb o1"></div>
<div class="sr-orb o2"></div>
<div class="sr-orb o3"></div>

<!-- ═══════════════════════════════════════
     PAGE LAYOUT
     ═══════════════════════════════════════ -->
<div class="sr-page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 sr-main-col">

                <!-- ── Page Header ── -->
                <div class="sr-page-header sr-reveal sr-d1">
                    <div class="sr-page-badge">
                        <i class="bi bi-clipboard2-data-fill"></i>
                        Services
                    </div>
                    <h1 class="sr-page-title">
                        Service <span>Requests</span>
                    </h1>
                    <p class="sr-page-desc">
                        Submit barangay service requests and track their progress through the approval workflow.
                    </p>
                    <div class="sr-page-divider"></div>
                </div>

                <!-- ── Alert ── -->
                <?php if (!empty($success)) : ?>
                    <div class="sr-alert sr-alert-success sr-reveal sr-d2">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo e($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- ── Submit Request Card ── -->
                <div class="sr-card sr-reveal sr-d2">
                    <div class="sr-card-icon" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <h3 class="sr-card-title">New Request</h3>
                    <p class="sr-card-subtitle">Fill in the details below to submit a new service request to the barangay.</p>

                    <form method="post">
                        <?php echo csrfField(); ?>
                        <div class="sr-form-row cols-3">
                            <div class="sr-form-group">
                                <label class="sr-label">
                                    <i class="bi bi-folder2-open"></i> Request Type
                                </label>
                                <select name="application_type" class="sr-select" required>
                                    <option value="">Select request type</option>
                                    <?php foreach ($requestTypes as $type): ?>
                                        <option value="<?php echo e($type); ?>"><?php echo e($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="sr-form-group">
                                <label class="sr-label">
                                    <i class="bi bi-bullseye"></i> Purpose
                                </label>
                                <input type="text" name="purpose" class="sr-input" placeholder="e.g. Employment, School">
                            </div>
                            <div class="sr-form-group">
                                <label class="sr-label">
                                    <i class="bi bi-flag"></i> Priority
                                </label>
                                <select name="priority" class="sr-select">
                                    <option value="normal">Normal</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="sr-form-row cols-1" style="margin-bottom:28px;">
                            <div class="sr-form-group">
                                <label class="sr-label">
                                    <i class="bi bi-chat-left-text"></i> Remarks
                                </label>
                                <textarea name="remarks" class="sr-textarea" rows="2" placeholder="Optional — add any additional details or special instructions..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="sr-btn sr-btn-primary">
                            <span>Submit Request</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <!-- ── My Requests Card ── -->
                <div class="sr-card sr-reveal sr-d3">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:24px;">
                        <div>
                            <div style="display:flex; align-items:center;">
                                <h3 class="sr-card-title" style="margin-bottom:0;">My Requests</h3>
                                <?php if (!empty($applications)): ?>
                                    <span class="sr-count-badge"><?php echo count($applications); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="sr-card-subtitle" style="margin-bottom:0; margin-top:4px;">
                                All your submitted service requests and their current status.
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($applications)): ?>
                        <div class="sr-table-wrap">
                            <table class="sr-table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Type</th>
                                        <th>Purpose</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td class="col-ref">
                                                <span>#</span><?php echo (int) $application['id']; ?>
                                            </td>
                                            <td class="col-type">
                                                <?php echo e($application['application_type']); ?>
                                            </td>
                                            <td>
                                                <?php echo e($application['purpose'] ?? '—'); ?>
                                            </td>
                                            <td>
                                                <?php if ($application['priority'] === 'urgent'): ?>
                                                    <span class="sr-badge sr-badge-urgent">
                                                        <i class="bi bi-exclamation-triangle-fill"></i> Urgent
                                                    </span>
                                                <?php elseif ($application['priority'] === 'high'): ?>
                                                    <span class="sr-badge sr-badge-high">
                                                        <i class="bi bi-chevron-double-up"></i> High
                                                    </span>
                                                <?php else: ?>
                                                    <span class="sr-badge sr-badge-normal">
                                                        <i class="bi bi-dash-lg"></i> Normal
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $statusClass = match($application['status']) {
                                                        'submitted' => 'sr-status-submitted',
                                                        'pending' => 'sr-status-pending',
                                                        'under_review' => 'sr-status-under_review',
                                                        'approved' => 'sr-status-approved',
                                                        'ready_for_pickup' => 'sr-status-ready_for_pickup',
                                                        'completed' => 'sr-status-completed',
                                                        'rejected' => 'sr-status-rejected',
                                                        default => 'sr-status'
                                                    };
                                                    $statusIcon = match($application['status']) {
                                                        'submitted' => 'bi-send-fill',
                                                        'pending' => 'bi-clock-fill',
                                                        'under_review' => 'bi-search',
                                                        'approved' => 'bi-check-circle-fill',
                                                        'ready_for_pickup' => 'bi-bag-check-fill',
                                                        'completed' => 'bi-trophy-fill',
                                                        'rejected' => 'bi-x-circle-fill',
                                                        default => 'bi-circle'
                                                    };
                                                ?>
                                                <span class="sr-badge <?php echo $statusClass; ?>">
                                                    <i class="bi <?php echo $statusIcon; ?>"></i>
                                                    <?php echo e(str_replace('_', ' ', ucwords($application['status']))); ?>
                                                </span>
                                            </td>
                                            <td class="col-date">
                                                <?php echo date('M d, Y', strtotime($application['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($paginator)) echo renderPagination($paginator); ?>
                    <?php else: ?>
                        <div class="sr-empty">
                            <div class="sr-empty-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <h6>No Requests Yet</h6>
                            <p>Submit a new service request using the form above to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.sr-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('sr-visible');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>