<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['resident']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$resident = null;
try {
    $residentStmt = $pdo->prepare('SELECT id FROM residents WHERE user_id = ? LIMIT 1');
    $residentStmt->execute([$_SESSION['user_id']]);
    $resident = $residentStmt->fetch();
} catch (Throwable $e) {
    $resident = null;
}

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resident) {
    requireCsrf();
    $appointmentDate = trim($_POST['appointment_date'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if (!$appointmentDate || !$purpose) {
        $_SESSION['_flash_error'] = 'Date and purpose are required.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif (strtotime($appointmentDate) < strtotime('today')) {
        $_SESSION['_flash_error'] = 'Appointment date cannot be in the past.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $stmt = $pdo->prepare('INSERT INTO appointments (resident_id, appointment_date, purpose, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$resident['id'], $appointmentDate, $purpose, 'pending']);
        logAudit('create_appointment', 'Resident booked appointment for ' . $appointmentDate);

        $adminUsers = $pdo->query('SELECT id FROM users WHERE role = "admin" AND status = "active"')->fetchAll();
        $secretaryUsers = $pdo->query('SELECT id FROM users WHERE role = "secretary" AND status = "active"')->fetchAll();
        $residentStmt2 = $pdo->prepare('SELECT full_name FROM residents WHERE id = ? LIMIT 1');
        $residentStmt2->execute([$resident['id']]);
        $residentName = $residentStmt2->fetchColumn() ?? 'A resident';
        $appointmentLink = defined('BASE_URL') ? BASE_URL . '/admin/appointments.php' : '/admin/appointments.php';
        foreach ($adminUsers as $adminUser) {
            createNotification((int) $adminUser['id'], 'New appointment booked by ' . $residentName . ' on ' . $appointmentDate, $appointmentLink, (int) ($_SESSION['user_id'] ?? 0));
        }
        foreach ($secretaryUsers as $secUser) {
            createNotification((int) $secUser['id'], 'New appointment booked by ' . $residentName . ' on ' . $appointmentDate, $appointmentLink, (int) ($_SESSION['user_id'] ?? 0));
        }

        $_SESSION['_flash_success'] = 'Appointment booked successfully. Waiting for approval.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$paginator = [];
$appointments = [];
if ($resident) {
    $paginator = paginate(
        'SELECT COUNT(*) FROM appointments WHERE resident_id = ?',
        [$resident['id']],
        'SELECT * FROM appointments WHERE resident_id = ? ORDER BY appointment_date DESC',
        [$resident['id']]
    );
    $appointments = $paginator['data'];
}

/* ── Stats ── */
$statTotal = count($appointments);
$statPending = 0;
$statApproved = 0;
$statCompleted = 0;
foreach ($appointments as $a) {
    if ($a['status'] === 'pending') $statPending++;
    if ($a['status'] === 'approved') $statApproved++;
    if ($a['status'] === 'completed') $statCompleted++;
}

/* ── Next upcoming ── */
$nextAppointment = null;
foreach ($appointments as $a) {
    if (in_array($a['status'], ['pending', 'approved']) && strtotime($a['appointment_date']) >= strtotime('today')) {
        if (!$nextAppointment || strtotime($a['appointment_date']) < strtotime($nextAppointment['appointment_date'])) {
            $nextAppointment = $a;
        }
    }
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
    --ap-accent: #10b981;
    --ap-accent-dark: #059669;
    --ap-accent-glow: rgba(16,185,129,0.15);
    --ap-teal: #14b8a6;
    --ap-sky: #0ea5e9;
    --ap-amber: #f59e0b;
    --ap-red: #ef4444;
    --ap-violet: #8b5cf6;
    --ap-bg: #0f172a;
    --ap-surface: rgba(255,255,255,0.04);
    --ap-surface-hover: rgba(255,255,255,0.07);
    --ap-border: rgba(255,255,255,0.08);
    --ap-text: #f1f5f9;
    --ap-text-secondary: #94a3b8;
    --ap-text-muted: #64748b;
    --ap-radius: 12px;
    --ap-radius-lg: 18px;
}

/* ═══════════════════════════════
   GLOBAL
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--ap-bg) !important;
    color: var(--ap-text);
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
.ap-grid-overlay {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

.ap-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: apFloat 22s ease-in-out infinite;
}

.ap-orb.o1 { width: 500px; height: 500px; background: rgba(16,185,129,0.06); top: -12%; left: -10%; }
.ap-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -8%; right: -5%; animation-delay: -12s; }
.ap-orb.o3 { width: 260px; height: 260px; background: rgba(14,165,233,0.05); top: 50%; left: 45%; animation-delay: -6s; animation-duration: 28s; }

@keyframes apFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(30px, -20px) scale(1.04); }
    66%      { transform: translate(-20px, 15px) scale(0.96); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.ap-page-wrapper {
    position: relative;
    z-index: 1;
    min-height: 100vh;
}

.ap-page-wrapper .container-fluid { padding: 0; }
.ap-page-wrapper .row { margin: 0; min-height: 100vh; }

/* ═══════════════════════════════
   SIDEBAR
   ═══════════════════════════════ */
.ap-sidebar-col {
    background: rgba(15,23,42,0.60);
    backdrop-filter: blur(30px);
    border-right: 1px solid var(--ap-border);
    padding: 0 !important;
    min-height: 100vh;
}

.ap-sidebar-col .sidebar,
.ap-sidebar-col .sidebar-menu,
.ap-sidebar-col .sidebar-header,
.ap-sidebar-col .sidebar-nav,
.ap-sidebar-col ul,
.ap-sidebar-col li,
.ap-sidebar-col a {
    background: transparent !important;
    color: var(--ap-text-secondary) !important;
}

.ap-sidebar-col a:hover,
.ap-sidebar-col .active a,
.ap-sidebar-col .active {
    background: var(--ap-surface-hover) !important;
    color: var(--ap-text) !important;
}

.ap-sidebar-col .sidebar-header h4,
.ap-sidebar-col .sidebar-header h5,
.ap-sidebar-col .sidebar-header h3 {
    color: var(--ap-text) !important;
}

/* ═══════════════════════════════
   MAIN CONTENT
   ═══════════════════════════════ */
.ap-main-col {
    padding: 40px 48px 60px !important;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.ap-page-header {
    margin-bottom: 32px;
}

.ap-page-badge {
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

.ap-page-badge i { font-size: 0.8rem; }

.ap-page-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.ap-page-title span {
    background: linear-gradient(135deg, var(--ap-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ap-page-desc {
    font-size: 0.95rem;
    color: var(--ap-text-muted);
    line-height: 1.6;
    max-width: 600px;
}

.ap-page-divider {
    width: 48px;
    height: 3px;
    background: linear-gradient(135deg, var(--ap-accent), #34d399);
    border-radius: 2px;
    margin-top: 20px;
}

/* ═══════════════════════════════
   STAT PILLS
   ═══════════════════════════════ */
.ap-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
}

.ap-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--ap-border);
    border-radius: var(--ap-radius);
    backdrop-filter: blur(12px);
    transition: all 0.3s ease;
}

.ap-stat-pill:hover {
    border-color: rgba(255,255,255,0.14);
    background: rgba(255,255,255,0.06);
    transform: translateY(-2px);
}

.ap-pill-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.ap-pill-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 1px;
}

.ap-pill-label {
    font-size: 0.72rem;
    color: var(--ap-text-muted);
    font-weight: 500;
}

/* ═══════════════════════════════
   ALERTS
   ═══════════════════════════════ */
.ap-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-radius: var(--ap-radius);
    margin-bottom: 24px;
    font-size: 0.88rem;
    font-weight: 500;
    animation: apSlideIn 0.4s ease;
}

@keyframes apSlideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.ap-alert i { font-size: 1.15rem; flex-shrink: 0; }

.ap-alert-success {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.ap-alert-success i { color: var(--ap-accent); }

.ap-alert-error {
    background: rgba(239,68,68,0.10);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.ap-alert-error i { color: var(--ap-red); }

.ap-alert-close {
    margin-left: auto;
    background: transparent;
    border: none;
    color: inherit;
    opacity: 0.5;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 4px;
    transition: opacity 0.2s ease;
}
.ap-alert-close:hover { opacity: 1; }

/* ═══════════════════════════════
   NEXT APPOINTMENT BANNER
   ═══════════════════════════════ */
.ap-next-banner {
    background: rgba(16,185,129,0.04);
    border: 1px solid rgba(16,185,129,0.15);
    border-radius: var(--ap-radius-lg);
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.ap-next-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(135deg, var(--ap-accent), #34d399);
}

.ap-next-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.20);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--ap-accent);
    flex-shrink: 0;
}

.ap-next-info { flex-grow: 1; }

.ap-next-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6ee7b7;
    margin-bottom: 2px;
}

.ap-next-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #e2e8f0;
    margin-bottom: 2px;
}

.ap-next-meta {
    font-size: 0.82rem;
    color: var(--ap-text-muted);
    display: flex;
    align-items: center;
    gap: 12px;
}

.ap-next-meta i { font-size: 0.75rem; }

.ap-next-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    flex-shrink: 0;
}

/* ═══════════════════════════════
   FORM CARD
   ═══════════════════════════════ */
.ap-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--ap-border);
    border-radius: var(--ap-radius-lg);
    backdrop-filter: blur(20px);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.ap-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
}

.ap-card:hover {
    border-color: rgba(255,255,255,0.12);
    box-shadow: 0 8px 40px rgba(0,0,0,0.20);
}

.ap-card-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.20);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--ap-accent);
    margin-bottom: 18px;
}

.ap-card-title {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.3rem;
    color: #ffffff;
    margin-bottom: 6px;
}

.ap-card-subtitle {
    font-size: 0.88rem;
    color: var(--ap-text-muted);
    line-height: 1.5;
    margin: 0 0 24px;
}

/* ═══════════════════════════════
   FORM CONTROLS
   ═══════════════════════════════ */
.ap-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}

.ap-label i { font-size: 0.85rem; color: var(--ap-text-muted); }

.ap-input {
    width: 100%;
    padding: 13px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--ap-radius);
    font-size: 0.92rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}

.ap-input::placeholder { color: #475569; }

.ap-input:focus {
    border-color: var(--ap-accent);
    box-shadow: 0 0 0 3px var(--ap-accent-glow);
    background: rgba(255,255,255,0.07);
}

.ap-input-hint {
    font-size: 0.75rem;
    color: var(--ap-text-muted);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ap-input-hint i { font-size: 0.72rem; }

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.ap-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px 28px;
    border: none;
    border-radius: var(--ap-radius);
    font-size: 0.92rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    line-height: 1.4;
}

.ap-btn i { transition: transform 0.2s ease; }

.ap-btn-primary {
    background: linear-gradient(135deg, var(--ap-accent), var(--ap-accent-dark));
    color: #fff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
}

.ap-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(16,185,129,0.35);
    color: #fff;
}

.ap-btn-primary:active { transform: translateY(0); }
.ap-btn-primary:hover i { transform: translateX(3px); }

/* ═══════════════════════════════
   SECTION HEADER
   ═══════════════════════════════ */
.ap-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.ap-section-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ap-section-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}

.ap-section-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 2px;
}

.ap-section-count {
    font-size: 0.78rem;
    color: var(--ap-text-muted);
}

/* ═══════════════════════════════
   APPOINTMENTS TABLE
   ═══════════════════════════════ */
.ap-table-wrap {
    overflow-x: auto;
    border-radius: var(--ap-radius);
    border: 1px solid rgba(255,255,255,0.05);
}

.ap-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    min-width: 600px;
}

.ap-table thead th {
    padding: 13px 22px;
    font-size: 0.73rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--ap-text-muted);
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid var(--ap-border);
    text-align: left;
    white-space: nowrap;
}

.ap-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.2s ease;
}

.ap-table tbody tr:last-child { border-bottom: none; }
.ap-table tbody tr:hover { background: rgba(255,255,255,0.03); }

.ap-table tbody td {
    padding: 14px 22px;
    color: #cbd5e1;
    vertical-align: middle;
    white-space: nowrap;
}

/* Date cell */
.ap-date-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ap-date-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    line-height: 1;
}

.ap-date-month {
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 1px;
}

.ap-date-day {
    font-size: 0.85rem;
    font-weight: 800;
}

.ap-date-text {
    font-weight: 600;
    font-size: 0.88rem;
    color: #e2e8f0;
}

.ap-date-dayname {
    font-size: 0.73rem;
    color: var(--ap-text-muted);
}

/* Status badge */
.ap-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

.ap-status-badge i { font-size: 0.65rem; }

.ap-col-purpose {
    font-weight: 600;
    color: #e2e8f0;
}

.ap-col-requested {
    font-size: 0.82rem;
    color: var(--ap-text-muted);
}

/* ═══════════════════════════════
   EMPTY STATE
   ═══════════════════════════════ */
.ap-empty {
    padding: 48px 24px;
    text-align: center;
}

.ap-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
    position: relative;
}

.ap-empty-icon::after {
    content: '';
    position: absolute;
    inset: -7px;
    border-radius: 24px;
    border: 2px dashed rgba(16,185,129,0.15);
}

.ap-empty h5 {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.05rem;
    color: #e2e8f0;
    margin-bottom: 6px;
}

.ap-empty p {
    font-size: 0.88rem;
    color: var(--ap-text-muted);
    margin: 0 auto;
    max-width: 360px;
    line-height: 1.5;
}

/* ═══════════════════════════════
   NOT LINKED STATE
   ═══════════════════════════════ */
.ap-not-linked {
    text-align: center;
    padding: 60px 40px;
}

.ap-not-linked-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: var(--ap-accent);
    margin: 0 auto 18px;
}

.ap-not-linked h4 {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.2rem;
    color: #ffffff;
    margin-bottom: 8px;
}

.ap-not-linked p {
    font-size: 0.92rem;
    color: var(--ap-text-muted);
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.6;
}

/* ═══════════════════════════════
   REVEAL ANIMATIONS
   ═══════════════════════════════ */
.ap-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.ap-reveal.ap-visible {
    opacity: 1;
    transform: translateY(0);
}

.ap-d1 { transition-delay: 0.05s; }
.ap-d2 { transition-delay: 0.10s; }
.ap-d3 { transition-delay: 0.15s; }
.ap-d4 { transition-delay: 0.20s; }
.ap-d5 { transition-delay: 0.25s; }
.ap-d6 { transition-delay: 0.30s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 991.98px) {
    .ap-sidebar-col {
        min-height: auto !important;
        border-right: none !important;
        border-bottom: 1px solid var(--ap-border) !important;
    }
    .ap-main-col { padding: 32px 24px 50px !important; }
}

@media (max-width: 767.98px) {
    .ap-main-col { padding: 24px 18px 40px !important; }
    .ap-page-title { font-size: 1.4rem; }
    .ap-stats-row { gap: 8px; }
    .ap-stat-pill { flex: 1; min-width: 0; padding: 10px 14px; }
    .ap-pill-icon { width: 32px; height: 32px; font-size: 0.85rem; }
    .ap-pill-value { font-size: 1rem; }
    .ap-next-banner { flex-direction: column; align-items: flex-start; gap: 12px; }
    .ap-table thead th { padding: 11px 16px; }
    .ap-table tbody td { padding: 12px 16px; font-size: 0.84rem; }
    .ap-empty { padding: 36px 20px; }
}

@media (max-width: 480px) {
    .ap-main-col { padding: 20px 14px 36px !important; }
    .ap-page-title { font-size: 1.25rem; }
    .ap-page-header { }
    .ap-stat-pill { width: 100%; }
    .ap-date-cell { gap: 8px; }
    .ap-date-icon { width: 34px; height: 34px; }
}
</style>

<!-- ═══════════════════════════════════════
     ATMOSPHERIC ELEMENTS
     ═══════════════════════════════════════ -->
<div class="ap-grid-overlay"></div>
<div class="ap-orb o1"></div>
<div class="ap-orb o2"></div>
<div class="ap-orb o3"></div>

<!-- ═══════════════════════════════════════
     PAGE LAYOUT
     ═══════════════════════════════════════ -->
<div class="ap-page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 p-0 ap-sidebar-col">
                <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ap-main-col">

                <!-- ── Page Header ── -->
                <div class="ap-page-header ap-reveal ap-d1">
                    <div class="ap-page-badge">
                        <i class="bi bi-calendar-check"></i>
                        Scheduling
                    </div>
                    <h1 class="ap-page-title">
                        My <span>Appointments</span>
                    </h1>
                    <p class="ap-page-desc">
                        Schedule visits to the barangay hall and track your upcoming appointments.
                    </p>
                    <div class="ap-page-divider"></div>
                </div>

                <!-- ── Stats Row ── -->
                <div class="ap-stats-row ap-reveal ap-d2">
                    <div class="ap-stat-pill">
                        <div class="ap-pill-icon" style="background:rgba(16,185,129,0.10); color:#6ee7b7;">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div>
                            <div class="ap-pill-value"><?php echo $statTotal; ?></div>
                            <div class="ap-pill-label">Total</div>
                        </div>
                    </div>
                    <div class="ap-stat-pill">
                        <div class="ap-pill-icon" style="background:rgba(245,158,11,0.10); color:#fcd34d;">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div>
                            <div class="ap-pill-value"><?php echo $statPending; ?></div>
                            <div class="ap-pill-label">Pending</div>
                        </div>
                    </div>
                    <div class="ap-stat-pill">
                        <div class="ap-pill-icon" style="background:rgba(14,165,233,0.10); color:#7dd3fc;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="ap-pill-value"><?php echo $statApproved; ?></div>
                            <div class="ap-pill-label">Approved</div>
                        </div>
                    </div>
                    <div class="ap-stat-pill">
                        <div class="ap-pill-icon" style="background:rgba(139,92,246,0.10); color:#c4b5fd;">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div>
                            <div class="ap-pill-value"><?php echo $statCompleted; ?></div>
                            <div class="ap-pill-label">Completed</div>
                        </div>
                    </div>
                </div>

                <!-- ── Alerts ── -->
                <?php if (!empty($success)): ?>
                    <div class="ap-alert ap-alert-success ap-reveal ap-d2">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo e($success); ?></span>
                        <button class="ap-alert-close" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="ap-alert ap-alert-error ap-reveal ap-d2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?php echo e($error); ?></span>
                        <button class="ap-alert-close" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>
                    </div>
                <?php endif; ?>

                <?php if ($resident): ?>

                    <!-- ── Next Appointment Banner ── -->
                    <?php if ($nextAppointment):
                        $nextStatus = match($nextAppointment['status']) {
                            'pending'  => ['bg' => 'rgba(245,158,11,0.10)', 'color' => '#fcd34d', 'icon' => 'bi-clock', 'label' => 'Pending Approval'],
                            'approved' => ['bg' => 'rgba(16,185,129,0.10)', 'color' => '#6ee7b7', 'icon' => 'bi-check-circle', 'label' => 'Confirmed'],
                            default    => ['bg' => 'rgba(148,163,184,0.10)', 'color' => '#94a3b8', 'icon' => 'bi-question-circle', 'label' => ucfirst($nextAppointment['status'])],
                        };
                        $nextDate = strtotime($nextAppointment['appointment_date']);
                        $isToday = date('Y-m-d', $nextDate) === date('Y-m-d');
                        $isTomorrow = date('Y-m-d', $nextDate) === date('Y-m-d', strtotime('+1 day'));
                        $dateLabel = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : date('l, M d', $nextDate));
                    ?>
                        <div class="ap-next-banner ap-reveal ap-d2">
                            <div class="ap-next-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="ap-next-info">
                                <div class="ap-next-label"><?php echo $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : 'Upcoming'); ?> Appointment</div>
                                <div class="ap-next-title"><?php echo e($nextAppointment['purpose']); ?></div>
                                <div class="ap-next-meta">
                                    <span><i class="bi bi-calendar3"></i> <?php echo e($dateLabel); ?></span>
                                    <span><i class="bi bi-clock"></i> <?php echo date('h:i A', $nextDate); ?></span>
                                </div>
                            </div>
                            <span class="ap-next-status" style="background:<?php echo $nextStatus['bg']; ?>; color:<?php echo $nextStatus['color']; ?>;">
                                <i class="bi <?php echo $nextStatus['icon']; ?>"></i>
                                <?php echo e($nextStatus['label']); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- ── Booking Form ── -->
                    <div class="ap-card ap-reveal ap-d3" style="margin-bottom:24px;">
                        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(135deg,var(--ap-accent),#34d399);z-index:1;"></div>
                        <div style="padding:28px;">
                            <div class="ap-card-header-icon">
                                <i class="bi bi-calendar-plus"></i>
                            </div>
                            <h3 class="ap-card-title">Book an Appointment</h3>
                            <p class="ap-card-subtitle">Select your preferred date and describe the purpose of your visit. Appointments are subject to approval by the barangay staff.</p>

                            <form method="post">
                                <?php echo csrfField(); ?>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="ap-label">
                                            <i class="bi bi-calendar3"></i> Preferred Date
                                        </label>
                                        <input type="date" name="appointment_date" class="ap-input" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($_POST['appointment_date'] ?? ''); ?>">
                                        <div class="ap-input-hint"><i class="bi bi-info-circle"></i> Select a date from today onwards</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="ap-label">
                                            <i class="bi bi-chat-left-text"></i> Purpose of Visit
                                        </label>
                                        <input type="text" name="purpose" class="ap-input" placeholder="e.g. Document request, Barangay clearance, Inquiry" required value="<?php echo e($_POST['purpose'] ?? ''); ?>">
                                        <div class="ap-input-hint"><i class="bi bi-info-circle"></i> Briefly describe what you need assistance with</div>
                                    </div>
                                </div>
                                <button type="submit" class="ap-btn ap-btn-primary" style="margin-top:20px;">
                                    <span>Book Appointment</span>
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ── Appointments Table ── -->
                    <div class="ap-card ap-reveal ap-d4">
                        <div class="ap-section-header" style="padding:18px 22px; border-bottom:1px solid var(--ap-border);">
                            <div class="ap-section-header-left">
                                <div class="ap-section-icon" style="background:rgba(16,185,129,0.10); color:#6ee7b7;">
                                    <i class="bi bi-calendar3"></i>
                                </div>
                                <div>
                                    <h5 class="ap-section-title">Appointment History</h5>
                                    <span class="ap-section-count"><?php echo $statTotal; ?> total</span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($appointments)): ?>
                            <div class="ap-table-wrap" style="border:none; border-radius:0;">
                                <table class="ap-table">
                                    <thead>
                                        <tr>
                                            <th>Appointment Date</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Requested</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appt):
                                            $apptDate = strtotime($appt['appointment_date']);
                                            $statusCfg = match($appt['status'] ?? '') {
                                                'pending'   => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#fcd34d', 'icon' => 'bi-clock'],
                                                'approved'  => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#6ee7b7', 'icon' => 'bi-check-circle-fill'],
                                                'rejected'  => ['bg' => 'rgba(239,68,68,0.12)',  'color' => '#fca5a5', 'icon' => 'bi-x-circle-fill'],
                                                'completed' => ['bg' => 'rgba(14,165,233,0.12)', 'color' => '#7dd3fc', 'icon' => 'bi-check2-all'],
                                                'cancelled' => ['bg' => 'rgba(148,163,184,0.10)','color' => '#94a3b8', 'icon' => 'bi-dash-circle'],
                                                default     => ['bg' => 'rgba(148,163,184,0.10)','color' => '#94a3b8', 'icon' => 'bi-question-circle'],
                                            };
                                            $isPast = $apptDate < strtotime('today');
                                            $monthAbbr = strtoupper(date('M', $apptDate));
                                            $dayNum = date('d', $apptDate);
                                            $dateColor = $isPast ? 'var(--ap-text-muted)' : 'var(--ap-accent)';
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="ap-date-cell">
                                                        <div class="ap-date-icon" style="background:rgba(16,185,129,0.06); color:<?php echo $dateColor; ?>;">
                                                            <span class="ap-date-month"><?php echo $monthAbbr; ?></span>
                                                            <span class="ap-date-day"><?php echo $dayNum; ?></span>
                                                        </div>
                                                        <div>
                                                            <div class="ap-date-text"><?php echo date('M d, Y', $apptDate); ?></div>
                                                            <div class="ap-date-dayname"><?php echo date('l', $apptDate); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="ap-col-purpose"><?php echo e($appt['purpose']); ?></td>
                                                <td>
                                                    <span class="ap-status-badge" style="background:<?php echo $statusCfg['bg']; ?>; color:<?php echo $statusCfg['color']; ?>;">
                                                        <i class="bi <?php echo $statusCfg['icon']; ?>"></i>
                                                        <?php echo e(ucwords($appt['status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="ap-col-requested">
                                                    <?php echo date('M d, Y', strtotime($appt['created_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($paginator)) echo renderPagination($paginator); ?>
                        <?php else: ?>
                            <div class="ap-empty">
                                <div class="ap-empty-icon" style="background:rgba(16,185,129,0.08); color:#6ee7b7;">
                                    <i class="bi bi-calendar2-week"></i>
                                </div>
                                <h5>No Appointments Yet</h5>
                                <p>You haven't booked any appointments. Use the form above to schedule your first visit.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <!-- ── Not Linked State ── -->
                    <div class="ap-card ap-reveal ap-d2">
                        <div class="ap-not-linked">
                            <div class="ap-not-linked-icon">
                                <i class="bi bi-person-x"></i>
                            </div>
                            <h4>Profile Not Linked</h4>
                            <p>Your account is not yet linked to the resident database. Please visit the barangay hall or contact the secretary to complete your registration.</p>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.ap-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('ap-visible');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>