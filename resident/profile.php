<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['resident']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$resident = null;
try {
    $residentStmt = $pdo->prepare('SELECT r.*, p.civil_status, p.occupation, p.education FROM residents r LEFT JOIN personal_information p ON p.resident_id = r.id WHERE r.user_id = ? LIMIT 1');
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
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = sanitizeString($_POST['full_name'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');
        $sex = trim($_POST['sex'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $civilStatus = trim($_POST['civil_status'] ?? '');
        $occupation = sanitizeString($_POST['occupation'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $emergencyContact = sanitizeString($_POST['emergency_contact'] ?? '');

        if (!$fullName) {
            $_SESSION['_flash_error'] = 'Full name is required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif ($birthDate && !validateDate($birthDate)) {
            $_SESSION['_flash_error'] = 'Invalid birth date format.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $pdo->prepare('UPDATE residents SET full_name = ?, birth_date = ?, sex = ?, address = ?, contact_number = ?, emergency_contact = ? WHERE id = ?')->execute([$fullName, $birthDate ?: null, $sex, $address, $contactNumber, $emergencyContact, $resident['id']]);

            $check = $pdo->prepare('SELECT id FROM personal_information WHERE resident_id = ? LIMIT 1');
            $check->execute([$resident['id']]);
            if ($check->fetch()) {
                $pdo->prepare('UPDATE personal_information SET civil_status = ?, occupation = ?, education = ? WHERE resident_id = ?')->execute([$civilStatus, $occupation, $education, $resident['id']]);
            } else {
                $pdo->prepare('INSERT INTO personal_information (resident_id, civil_status, occupation, education) VALUES (?, ?, ?, ?)')->execute([$resident['id'], $civilStatus, $occupation, $education]);
            }

            logAudit('update_profile', 'Resident updated profile');
            $_SESSION['_flash_success'] = 'Profile updated successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
            $residentStmt->execute([$_SESSION['user_id']]);
            $resident = $residentStmt->fetch();
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        $userStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $_SESSION['_flash_error'] = 'Current password is incorrect.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (strlen($newPassword) < 6) {
            $_SESSION['_flash_error'] = 'New password must be at least 6 characters.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            logAudit('change_password', 'Resident changed password');
            $_SESSION['_flash_success'] = 'Password changed successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$myDocuments = [];
if ($resident) {
    $docStmt = $pdo->prepare('SELECT * FROM documents WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5');
    $docStmt->execute([$resident['id']]);
    $myDocuments = $docStmt->fetchAll();
}

$recentAnnouncements = [];
if ($resident) {
    $stmt = $pdo->prepare('SELECT a.title, a.type, a.priority, a.created_at, ar.is_read FROM announcements a JOIN announcement_reads ar ON ar.announcement_id = a.id WHERE a.is_active = 1 AND ar.resident_id = ? ORDER BY a.created_at DESC LIMIT 5');
    $stmt->execute([$resident['id']]);
    $recentAnnouncements = $stmt->fetchAll();
}

$myRequests = [];
if ($resident) {
    $stmt = $pdo->prepare('SELECT * FROM applications WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->execute([$resident['id']]);
    $myRequests = $stmt->fetchAll();
}

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
    --pf-accent: #10b981;
    --pf-accent-dark: #059669;
    --pf-accent-glow: rgba(16,185,129,0.15);
    --pf-sky: #0ea5e9;
    --pf-violet: #8b5cf6;
    --pf-amber: #f59e0b;
    --pf-red: #ef4444;
    --pf-bg: #0f172a;
    --pf-surface: rgba(255,255,255,0.04);
    --pf-surface-hover: rgba(255,255,255,0.07);
    --pf-border: rgba(255,255,255,0.08);
    --pf-text: #f1f5f9;
    --pf-text-secondary: #94a3b8;
    --pf-text-muted: #64748b;
    --pf-radius: 12px;
    --pf-radius-lg: 18px;
}

/* ═══════════════════════════════
   GLOBAL
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--pf-bg) !important;
    color: var(--pf-text);
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
.pf-grid-overlay {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

.pf-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: pfFloat 22s ease-in-out infinite;
}

.pf-orb.o1 { width: 500px; height: 500px; background: rgba(16,185,129,0.06); top: -12%; left: -10%; }
.pf-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -8%; right: -5%; animation-delay: -12s; }
.pf-orb.o3 { width: 260px; height: 260px; background: rgba(14,165,233,0.05); top: 50%; left: 45%; animation-delay: -6s; animation-duration: 28s; }

@keyframes pfFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(30px, -20px) scale(1.04); }
    66%      { transform: translate(-20px, 15px) scale(0.96); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.pf-page-wrapper {
    position: relative;
    z-index: 1;
    min-height: 100vh;
}

.pf-page-wrapper .container-fluid { padding: 0; }
.pf-page-wrapper .row { margin: 0; min-height: 100vh; }

/* ═══════════════════════════════
   SIDEBAR
   ═══════════════════════════════ */
.pf-sidebar-col {
    background: rgba(15,23,42,0.60);
    backdrop-filter: blur(30px);
    border-right: 1px solid var(--pf-border);
    padding: 0 !important;
    min-height: 100vh;
}

.pf-sidebar-col .sidebar,
.pf-sidebar-col .sidebar-menu,
.pf-sidebar-col .sidebar-header,
.pf-sidebar-col .sidebar-nav,
.pf-sidebar-col ul,
.pf-sidebar-col li,
.pf-sidebar-col a {
    background: transparent !important;
    color: var(--pf-text-secondary) !important;
}

.pf-sidebar-col a:hover,
.pf-sidebar-col .active a,
.pf-sidebar-col .active {
    background: var(--pf-surface-hover) !important;
    color: var(--pf-text) !important;
}

.pf-sidebar-col .sidebar-header h4,
.pf-sidebar-col .sidebar-header h5,
.pf-sidebar-col .sidebar-header h3 {
    color: var(--pf-text) !important;
}

/* ═══════════════════════════════
   MAIN CONTENT
   ═══════════════════════════════ */
.pf-main-col {
    padding: 40px 48px 60px !important;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.pf-page-header {
    margin-bottom: 32px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.pf-page-header-text { flex: 1; min-width: 200px; }

.pf-page-badge {
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

.pf-page-badge i { font-size: 0.8rem; }

.pf-page-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.pf-page-title span {
    background: linear-gradient(135deg, var(--pf-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.pf-page-desc {
    font-size: 0.95rem;
    color: var(--pf-text-muted);
    line-height: 1.6;
}

.pf-page-divider {
    width: 48px;
    height: 3px;
    background: linear-gradient(135deg, var(--pf-accent), #34d399);
    border-radius: 2px;
    margin-top: 20px;
}

/* ═══════════════════════════════
   ALERTS
   ═══════════════════════════════ */
.pf-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-radius: var(--pf-radius);
    margin-bottom: 24px;
    font-size: 0.88rem;
    font-weight: 500;
    animation: pfSlideIn 0.4s ease;
}

@keyframes pfSlideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.pf-alert i { font-size: 1.15rem; flex-shrink: 0; }

.pf-alert-success {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.pf-alert-success i { color: var(--pf-accent); }

.pf-alert-danger {
    background: rgba(239,68,68,0.10);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.pf-alert-danger i { color: var(--pf-red); }

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.pf-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--pf-border);
    border-radius: var(--pf-radius-lg);
    padding: 28px;
    backdrop-filter: blur(20px);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.pf-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
}

.pf-card:hover {
    border-color: rgba(255,255,255,0.12);
    box-shadow: 0 8px 40px rgba(0,0,0,0.20);
}

.pf-card + .pf-card { margin-top: 0; }

.pf-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.pf-card-header-left { display: flex; align-items: center; gap: 12px; }

.pf-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}

.pf-card-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 2px;
}

.pf-card-subtitle {
    font-size: 0.82rem;
    color: var(--pf-text-muted);
    margin: 0;
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.pf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--pf-radius);
    font-size: 0.88rem;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    line-height: 1.4;
}

.pf-btn i { transition: transform 0.2s ease; }

.pf-btn-primary {
    background: linear-gradient(135deg, var(--pf-accent), var(--pf-accent-dark));
    color: #fff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
}

.pf-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(16,185,129,0.35);
    color: #fff;
}

.pf-btn-primary:active { transform: translateY(0); }
.pf-btn-primary:hover i { transform: translateX(3px); }

.pf-btn-ghost {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.12);
    color: var(--pf-text-secondary);
    padding: 8px 16px;
    font-size: 0.82rem;
}

.pf-btn-ghost:hover {
    border-color: var(--pf-accent);
    color: var(--pf-accent);
    background: rgba(16,185,129,0.06);
}

.pf-btn-sm {
    padding: 8px 16px;
    font-size: 0.82rem;
}

/* ═══════════════════════════════
   PROFILE HERO CARD
   ═══════════════════════════════ */
.pf-profile-hero {
    display: flex;
    align-items: center;
    gap: 24px;
}

.pf-avatar {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: rgba(16,185,129,0.12);
    border: 2px solid rgba(16,185,129,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--pf-accent);
    flex-shrink: 0;
}

.pf-profile-info h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 4px;
}

.pf-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.20);
    border-radius: 100px;
    font-size: 0.76rem;
    font-weight: 600;
    color: #6ee7b7;
    text-transform: capitalize;
}

/* ═══════════════════════════════
   INFO GRID
   ═══════════════════════════════ */
.pf-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.pf-info-item {
    padding: 14px 16px;
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: var(--pf-radius);
    transition: border-color 0.25s ease;
}

.pf-info-item:hover {
    border-color: rgba(255,255,255,0.12);
}

.pf-info-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--pf-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pf-info-label i { font-size: 0.72rem; }

.pf-info-value {
    font-size: 0.92rem;
    font-weight: 600;
    color: #e2e8f0;
    word-break: break-word;
}

/* ═══════════════════════════════
   DOCUMENTS LIST
   ═══════════════════════════════ */
.pf-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pf-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    gap: 12px;
}

.pf-list-item:last-child { border-bottom: none; }

.pf-list-item-info strong {
    font-size: 0.88rem;
    color: #e2e8f0;
    display: block;
    margin-bottom: 2px;
}

.pf-list-item-info small {
    font-size: 0.78rem;
    color: var(--pf-text-muted);
}

/* ═══════════════════════════════
   BADGES
   ═══════════════════════════════ */
.pf-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.74rem;
    font-weight: 600;
    white-space: nowrap;
    text-transform: capitalize;
}

.pf-badge-draft {
    background: rgba(148,163,184,0.10);
    border: 1px solid rgba(148,163,184,0.20);
    color: #94a3b8;
}

.pf-badge-issued {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.20);
    color: #6ee7b7;
}

.pf-badge-archived {
    background: rgba(245,158,11,0.10);
    border: 1px solid rgba(245,158,11,0.20);
    color: #fcd34d;
}

.pf-badge-default {
    background: rgba(14,165,233,0.10);
    border: 1px solid rgba(14,165,233,0.20);
    color: #7dd3fc;
}

/* Status badges for requests */
.pf-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.74rem;
    font-weight: 600;
    white-space: nowrap;
}

.pf-status-submitted { background: rgba(26,86,219,0.12); border: 1px solid rgba(26,86,219,0.25); color: #93c5fd; }
.pf-status-pending { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25); color: #fcd34d; }
.pf-status-under_review { background: rgba(14,165,233,0.12); border: 1px solid rgba(14,165,233,0.25); color: #7dd3fc; }
.pf-status-approved,
.pf-status-ready_for_pickup,
.pf-status-completed { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }
.pf-status-rejected { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }
.pf-status-default { background: rgba(148,163,184,0.10); border: 1px solid rgba(148,163,184,0.20); color: #94a3b8; }

/* ═══════════════════════════════
   ANNOUNCEMENT LIST
   ═══════════════════════════════ */
.pf-announcement-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.pf-announcement-item:last-child { border-bottom: none; }

.pf-announcement-item.unread {
    padding-left: 14px;
    border-left: 3px solid var(--pf-accent);
}

.pf-announcement-title {
    font-size: 0.88rem;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 3px;
}

.pf-announcement-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.pf-type-badge {
    padding: 2px 8px;
    border-radius: 100px;
    font-size: 0.68rem;
    font-weight: 600;
    background: rgba(148,163,184,0.10);
    border: 1px solid rgba(148,163,184,0.15);
    color: #94a3b8;
    text-transform: capitalize;
}

.pf-new-badge {
    padding: 2px 8px;
    border-radius: 100px;
    font-size: 0.68rem;
    font-weight: 700;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.20);
    color: #6ee7b7;
}

.pf-date-text {
    font-size: 0.75rem;
    color: var(--pf-text-muted);
}

/* ═══════════════════════════════
   TABLE (requests)
   ═══════════════════════════════ */
.pf-table-wrap {
    overflow-x: auto;
    border-radius: var(--pf-radius);
    border: 1px solid rgba(255,255,255,0.05);
}

.pf-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.pf-table thead th {
    padding: 12px 16px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid var(--pf-border);
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--pf-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    text-align: left;
    white-space: nowrap;
}

.pf-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.2s ease;
}

.pf-table tbody tr:last-child { border-bottom: none; }
.pf-table tbody tr:hover { background: rgba(255,255,255,0.03); }

.pf-table tbody td {
    padding: 12px 16px;
    color: #cbd5e1;
    vertical-align: middle;
    white-space: nowrap;
}

.pf-table .col-ref {
    font-weight: 700;
    color: #e2e8f0;
}

.pf-table .col-ref span {
    color: var(--pf-text-muted);
    font-weight: 400;
    margin-right: 2px;
}

/* ═══════════════════════════════
   FORM CONTROLS
   ═══════════════════════════════ */
.pf-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}

.pf-input,
.pf-select {
    width: 100%;
    padding: 13px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--pf-radius);
    font-size: 0.92rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
}

.pf-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
    cursor: pointer;
}

.pf-select option {
    background: #1e293b;
    color: #e2e8f0;
}

.pf-input::placeholder { color: #475569; }

.pf-input:focus,
.pf-select:focus {
    border-color: var(--pf-accent);
    box-shadow: 0 0 0 3px var(--pf-accent-glow);
    background: rgba(255,255,255,0.07);
}

.pf-form-grid {
    display: grid;
    gap: 18px;
}

.pf-form-grid.cols-2 { grid-template-columns: 1fr 1fr; }
.pf-form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.pf-form-grid.cols-2-1 { grid-template-columns: 2fr 1fr 1fr; }

.pf-form-group {
    display: flex;
    flex-direction: column;
}

/* ═══════════════════════════════
   EMPTY STATE
   ═══════════════════════════════ */
.pf-empty {
    text-align: center;
    padding: 28px 16px;
}

.pf-empty-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--pf-accent);
    margin: 0 auto 12px;
}

.pf-empty p {
    font-size: 0.85rem;
    color: var(--pf-text-muted);
    margin: 0;
}

/* ═══════════════════════════════
   NOT LINKED STATE
   ═══════════════════════════════ */
.pf-not-linked {
    text-align: center;
    padding: 60px 40px;
}

.pf-not-linked-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: var(--pf-accent);
    margin: 0 auto 18px;
}

.pf-not-linked h4 {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.2rem;
    color: #ffffff;
    margin-bottom: 8px;
}

.pf-not-linked p {
    font-size: 0.92rem;
    color: var(--pf-text-muted);
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.6;
}

/* ═══════════════════════════════
   MODAL — DARK THEME OVERRIDE
   ═══════════════════════════════ */
.pf-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.60);
    backdrop-filter: blur(6px);
    z-index: 1050;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.pf-modal-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}

.pf-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.95);
    width: 90%;
    max-width: 720px;
    max-height: 90vh;
    background: #131c31;
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: var(--pf-radius-lg);
    z-index: 1060;
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    pointer-events: none;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.pf-modal.active {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
    pointer-events: auto;
}

.pf-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 28px 0;
}

.pf-modal-header h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
}

.pf-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--pf-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pf-modal-close:hover {
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.25);
    color: #fca5a5;
}

.pf-modal-body {
    padding: 24px 28px;
    overflow-y: auto;
    flex: 1;
}

.pf-modal-footer {
    padding: 0 28px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.pf-btn-cancel {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.10);
    color: var(--pf-text-secondary);
}

.pf-btn-cancel:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.18);
    color: #e2e8f0;
}

/* ═══════════════════════════════
   REVEAL ANIMATIONS
   ═══════════════════════════════ */
.pf-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.pf-reveal.pf-visible {
    opacity: 1;
    transform: translateY(0);
}

.pf-d1 { transition-delay: 0.05s; }
.pf-d2 { transition-delay: 0.10s; }
.pf-d3 { transition-delay: 0.15s; }
.pf-d4 { transition-delay: 0.20s; }
.pf-d5 { transition-delay: 0.25s; }
.pf-d6 { transition-delay: 0.30s; }

/* ═══════════════════════════════
   GRID HELPERS
   ═══════════════════════════════ */
.pf-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.pf-grid-2 > * { min-width: 0; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 991.98px) {
    .pf-sidebar-col {
        min-height: auto !important;
        border-right: none !important;
        border-bottom: 1px solid var(--pf-border) !important;
    }
    .pf-main-col { padding: 32px 24px 50px !important; }
    .pf-grid-2 { grid-template-columns: 1fr; }
}

@media (max-width: 767.98px) {
    .pf-main-col { padding: 24px 18px 40px !important; }
    .pf-card { padding: 22px 18px; }
    .pf-page-title { font-size: 1.4rem; }
    .pf-profile-hero { flex-direction: column; text-align: center; }
    .pf-info-grid { grid-template-columns: 1fr 1fr; }
    .pf-form-grid.cols-3,
    .pf-form-grid.cols-2-1 { grid-template-columns: 1fr; }
    .pf-modal { width: 95%; }
    .pf-modal-header,
    .pf-modal-body,
    .pf-modal-footer { padding-left: 20px; padding-right: 20px; }
}

@media (max-width: 480px) {
    .pf-main-col { padding: 20px 14px 36px !important; }
    .pf-card { padding: 18px 14px; border-radius: var(--pf-radius); }
    .pf-page-title { font-size: 1.25rem; }
    .pf-info-grid { grid-template-columns: 1fr; }
    .pf-page-header { flex-direction: column; align-items: flex-start; }
}
</style>

<!-- ═══════════════════════════════════════
     ATMOSPHERIC ELEMENTS
     ═══════════════════════════════════════ -->
<div class="pf-grid-overlay"></div>
<div class="pf-orb o1"></div>
<div class="pf-orb o2"></div>
<div class="pf-orb o3"></div>

<!-- ═══════════════════════════════════════
     PAGE LAYOUT
     ═══════════════════════════════════════ -->
<div class="pf-page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 p-0 pf-sidebar-col">
                <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 pf-main-col">

                <?php if (!empty($success)) : ?>
                    <div class="pf-alert pf-alert-success pf-reveal pf-d1">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo e($success); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)) : ?>
                    <div class="pf-alert pf-alert-danger pf-reveal pf-d1">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?php echo e($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($resident): ?>

                    <!-- ── Page Header ── -->
                    <div class="pf-page-header pf-reveal pf-d1">
                        <div class="pf-page-header-text">
                            <div class="pf-page-badge">
                                <i class="bi bi-person-circle"></i>
                                Dashboard
                            </div>
                            <h1 class="pf-page-title">My <span>Profile</span></h1>
                            <p class="pf-page-desc">View your information, manage documents, and update your account.</p>
                            <div class="pf-page-divider"></div>
                        </div>
                        <button class="pf-btn pf-btn-primary" id="pfEditBtn">
                            <i class="bi bi-pencil"></i>
                            <span>Edit Profile</span>
                        </button>
                    </div>

                    <!-- ── Profile Hero + Personal Info ── -->
                    <div class="pf-grid-2" style="margin-bottom:20px;">
                        <!-- Profile Card -->
                        <div class="pf-card pf-reveal pf-d2">
                            <div class="pf-profile-hero">
                                <div class="pf-avatar">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div class="pf-profile-info">
                                    <h2><?php echo e($resident['full_name']); ?></h2>
                                    <span class="pf-role-badge">
                                        <i class="bi bi-shield-check"></i> Resident
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats Card -->
                        <div class="pf-card pf-reveal pf-d3">
                            <div class="pf-card-header">
                                <div class="pf-card-header-left">
                                    <div class="pf-card-icon" style="background:rgba(14,165,233,0.10); color:#7dd3fc;">
                                        <i class="bi bi-clipboard-data"></i>
                                    </div>
                                    <div>
                                        <h5 class="pf-card-title">Activity</h5>
                                        <p class="pf-card-subtitle">Your account at a glance</p>
                                    </div>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                                <div style="text-align:center; padding:12px 8px; background:rgba(16,185,129,0.06); border-radius:var(--pf-radius); border:1px solid rgba(16,185,129,0.12);">
                                    <div style="font-size:1.3rem; font-weight:800; color:#6ee7b7; line-height:1; margin-bottom:4px;"><?php echo count($myRequests); ?></div>
                                    <div style="font-size:0.72rem; color:var(--pf-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.04em;">Requests</div>
                                </div>
                                <div style="text-align:center; padding:12px 8px; background:rgba(14,165,233,0.06); border-radius:var(--pf-radius); border:1px solid rgba(14,165,233,0.12);">
                                    <div style="font-size:1.3rem; font-weight:800; color:#7dd3fc; line-height:1; margin-bottom:4px;"><?php echo count($myDocuments); ?></div>
                                    <div style="font-size:0.72rem; color:var(--pf-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.04em;">Documents</div>
                                </div>
                                <div style="text-align:center; padding:12px 8px; background:rgba(139,92,246,0.06); border-radius:var(--pf-radius); border:1px solid rgba(139,92,246,0.12);">
                                    <div style="font-size:1.3rem; font-weight:800; color:#a78bfa; line-height:1; margin-bottom:4px;"><?php echo count($recentAnnouncements); ?></div>
                                    <div style="font-size:0.72rem; color:var(--pf-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.04em;">Updates</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Personal Information ── -->
                    <div class="pf-card pf-reveal pf-d3" style="margin-bottom:20px;">
                        <div class="pf-card-header">
                            <div class="pf-card-header-left">
                                <div class="pf-card-icon" style="background:rgba(16,185,129,0.10); color:#6ee7b7;">
                                    <i class="bi bi-person-vcard"></i>
                                </div>
                                <div>
                                    <h5 class="pf-card-title">Personal Information</h5>
                                    <p class="pf-card-subtitle">Your registered details in the barangay system</p>
                                </div>
                            </div>
                        </div>
                        <div class="pf-info-grid">
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-person"></i> Full Name</div>
                                <div class="pf-info-value"><?php echo e($resident['full_name']); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-calendar3"></i> Birth Date</div>
                                <div class="pf-info-value"><?php echo e($resident['birth_date'] ?? '—'); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-gender-ambiguous"></i> Sex</div>
                                <div class="pf-info-value"><?php echo e($resident['sex'] ?? '—'); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-heart"></i> Civil Status</div>
                                <div class="pf-info-value"><?php echo e($resident['civil_status'] ?? $resident['p_civil_status'] ?? '—'); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-geo-alt"></i> Address</div>
                                <div class="pf-info-value"><?php echo e($resident['address'] ?? '—'); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-telephone"></i> Contact</div>
                                <div class="pf-info-value"><?php echo e($resident['contact_number'] ?? '—'); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-briefcase"></i> Occupation</div>
                                <div class="pf-info-value"><?php echo e($resident['occupation'] ?? $resident['p_occupation'] ?? '—'); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-mortarboard"></i> Education</div>
                                <div class="pf-info-value"><?php echo e($resident['education'] ?? $resident['p_education'] ?? '—'); ?></div>
                            </div>
                            <div class="pf-info-item">
                                <div class="pf-info-label"><i class="bi bi-exclamation-triangle"></i> Emergency Contact</div>
                                <div class="pf-info-value"><?php echo e($resident['emergency_contact'] ?? '—'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Documents + Change Password ── -->
                    <div class="pf-grid-2" style="margin-bottom:20px;">
                        <!-- Documents -->
                        <div class="pf-card pf-reveal pf-d4">
                            <div class="pf-card-header">
                                <div class="pf-card-header-left">
                                    <div class="pf-card-icon" style="background:rgba(245,158,11,0.10); color:#fcd34d;">
                                        <i class="bi bi-folder2-open"></i>
                                    </div>
                                    <div>
                                        <h5 class="pf-card-title">My Documents</h5>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($myDocuments)): ?>
                                <ul class="pf-list">
                                    <?php foreach ($myDocuments as $doc): ?>
                                        <?php
                                            $docBadge = match($doc['status']) {
                                                'draft' => 'pf-badge-draft',
                                                'issued' => 'pf-badge-issued',
                                                'archived' => 'pf-badge-archived',
                                                default => 'pf-badge-default'
                                            };
                                        ?>
                                        <li class="pf-list-item">
                                            <div class="pf-list-item-info">
                                                <strong><?php echo e($doc['document_type']); ?></strong>
                                                <small><?php echo e($doc['document_number']); ?> &bull; <?php echo date('M d, Y', strtotime($doc['created_at'])); ?></small>
                                            </div>
                                            <span class="pf-badge <?php echo $docBadge; ?>"><?php echo e($doc['status']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="pf-empty">
                                    <div class="pf-empty-icon"><i class="bi bi-inbox"></i></div>
                                    <p>No documents yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Change Password -->
                        <div class="pf-card pf-reveal pf-d4">
                            <div class="pf-card-header">
                                <div class="pf-card-header-left">
                                    <div class="pf-card-icon" style="background:rgba(239,68,68,0.10); color:#fca5a5;">
                                        <i class="bi bi-shield-lock"></i>
                                    </div>
                                    <div>
                                        <h5 class="pf-card-title">Change Password</h5>
                                        <p class="pf-card-subtitle">Update your account password</p>
                                    </div>
                                </div>
                            </div>
                            <form method="post">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="change_password">
                                <div class="pf-form-grid cols-1" style="margin-bottom:20px;">
                                    <div class="pf-form-group">
                                        <label class="pf-label">
                                            <i class="bi bi-key"></i> Current Password
                                        </label>
                                        <div style="position:relative;">
                                            <input type="password" name="current_password" class="pf-input" placeholder="Enter current password" required style="padding-right:40px;">
                                            <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="pf-form-group">
                                        <label class="pf-label">
                                            <i class="bi bi-lock"></i> New Password
                                        </label>
                                        <div style="position:relative;">
                                            <input type="password" name="new_password" class="pf-input" placeholder="Minimum 6 characters" required minlength="6" style="padding-right:40px;">
                                            <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="pf-btn pf-btn-primary pf-btn-sm">
                                    <span>Change Password</span>
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ── Announcements + Requests ── -->
                    <div class="pf-grid-2">
                        <!-- Announcements -->
                        <div class="pf-card pf-reveal pf-d5">
                            <div class="pf-card-header">
                                <div class="pf-card-header-left">
                                    <div class="pf-card-icon" style="background:rgba(139,92,246,0.10); color:#a78bfa;">
                                        <i class="bi bi-megaphone"></i>
                                    </div>
                                    <div>
                                        <h5 class="pf-card-title">Recent Announcements</h5>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($recentAnnouncements)): ?>
                                <div>
                                    <?php foreach ($recentAnnouncements as $announcement): ?>
                                        <a href="<?php echo BASE_URL; ?>/resident/announcement.php?id=<?php echo (int) $announcement['id']; ?>" style="display:block; text-decoration:none; color:inherit;">
                                            <div class="pf-announcement-item <?php echo !$announcement['is_read'] ? 'unread' : ''; ?>">
                                                <div>
                                                    <div class="pf-announcement-title">
                                                        <?php echo e($announcement['title']); ?>
                                                    </div>
                                                    <div class="pf-announcement-meta">
                                                        <span class="pf-type-badge"><?php echo e($announcement['type'] ?? 'announcement'); ?></span>
                                                        <?php if (!$announcement['is_read']): ?>
                                                            <span class="pf-new-badge">New</span>
                                                        <?php endif; ?>
                                                        <span class="pf-date-text"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="pf-empty">
                                    <div class="pf-empty-icon"><i class="bi bi-megaphone"></i></div>
                                    <p>No announcements yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Recent Requests -->
                        <div class="pf-card pf-reveal pf-d5">
                            <div class="pf-card-header">
                                <div class="pf-card-header-left">
                                    <div class="pf-card-icon" style="background:rgba(14,165,233,0.10); color:#7dd3fc;">
                                        <i class="bi bi-clipboard2-data"></i>
                                    </div>
                                    <div>
                                        <h5 class="pf-card-title">Recent Requests</h5>
                                    </div>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/resident/requests.php" class="pf-btn pf-btn-ghost pf-btn-sm">
                                    View All <i class="bi bi-arrow-right" style="font-size:0.75rem;"></i>
                                </a>
                            </div>
                            <?php if (!empty($myRequests)): ?>
                                <div class="pf-table-wrap">
                                    <table class="pf-table">
                                        <thead>
                                            <tr>
                                                <th>Ref</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($myRequests as $request): ?>
                                                <?php
                                                    $statusClass = match($request['status']) {
                                                        'submitted' => 'pf-status-submitted',
                                                        'pending' => 'pf-status-pending',
                                                        'under_review' => 'pf-status-under_review',
                                                        'approved' => 'pf-status-approved',
                                                        'ready_for_pickup' => 'pf-status-ready_for_pickup',
                                                        'completed' => 'pf-status-completed',
                                                        'rejected' => 'pf-status-rejected',
                                                        default => 'pf-status-default'
                                                    };
                                                ?>
                                                <tr>
                                                    <td class="col-ref"><span>#</span><?php echo (int) $request['id']; ?></td>
                                                    <td style="color:#e2e8f0; font-weight:600;"><?php echo e($request['application_type']); ?></td>
                                                    <td><span class="pf-status <?php echo $statusClass; ?>"><?php echo e(ucwords(str_replace('_', ' ', $request['status']))); ?></span></td>
                                                    <td style="color:var(--pf-text-muted); font-size:0.82rem;"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="pf-empty">
                                    <div class="pf-empty-icon"><i class="bi bi-inbox"></i></div>
                                    <p>No requests yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- ── Not Linked State ── -->
                    <div class="pf-card pf-reveal pf-d1">
                        <div class="pf-not-linked">
                            <div class="pf-not-linked-icon">
                                <i class="bi bi-person-x"></i>
                            </div>
                            <h4>Profile Not Linked</h4>
                            <p>Your account is not yet linked to the resident database. Please contact the barangay secretary to complete your registration.</p>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     EDIT PROFILE MODAL (CUSTOM DARK)
     ═══════════════════════════════════════ -->
<?php if ($resident): ?>
    <div class="pf-modal-backdrop" id="pfBackdrop"></div>
    <div class="pf-modal" id="pfModal">
        <div class="pf-modal-header">
            <h3>Edit Profile</h3>
            <button class="pf-modal-close" id="pfModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="post" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="pf-modal-body">
                <div class="pf-form-grid cols-2-1" style="margin-bottom:0;">
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-person"></i> Full Name</label>
                        <input type="text" name="full_name" class="pf-input" value="<?php echo e($resident['full_name']); ?>" required>
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-calendar3"></i> Birth Date</label>
                        <input type="date" name="birth_date" class="pf-input" value="<?php echo e($resident['birth_date'] ?? ''); ?>">
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-gender-ambiguous"></i> Sex</label>
                        <select name="sex" class="pf-select">
                            <option value="">Select</option>
                            <option value="Male" <?php echo $resident['sex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $resident['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                </div>
                <div class="pf-form-grid cols-3" style="margin-top:18px;">
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-geo-alt"></i> Address</label>
                        <input type="text" name="address" class="pf-input" value="<?php echo e($resident['address'] ?? ''); ?>">
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-telephone"></i> Contact Number</label>
                        <input type="text" name="contact_number" class="pf-input" value="<?php echo e($resident['contact_number'] ?? ''); ?>">
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-heart"></i> Civil Status</label>
                        <select name="civil_status" class="pf-select">
                            <option value="">Select</option>
                            <option value="Single" <?php echo ($resident['civil_status'] ?? $resident['p_civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo ($resident['civil_status'] ?? $resident['p_civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                            <option value="Widowed" <?php echo ($resident['civil_status'] ?? $resident['p_civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                            <option value="Separated" <?php echo ($resident['civil_status'] ?? $resident['p_civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                        </select>
                    </div>
                </div>
                <div class="pf-form-grid cols-3" style="margin-top:18px;">
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-briefcase"></i> Occupation</label>
                        <input type="text" name="occupation" class="pf-input" value="<?php echo e($resident['occupation'] ?? $resident['p_occupation'] ?? ''); ?>">
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-mortarboard"></i> Education</label>
                        <input type="text" name="education" class="pf-input" value="<?php echo e($resident['education'] ?? $resident['p_education'] ?? ''); ?>">
                    </div>
                    <div class="pf-form-group">
                        <label class="pf-label"><i class="bi bi-exclamation-triangle"></i> Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="pf-input" value="<?php echo e($resident['emergency_contact'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            <div class="pf-modal-footer">
                <button type="button" class="pf-btn pf-btn-cancel" id="pfCancelBtn">Cancel</button>
                <button type="submit" class="pf-btn pf-btn-primary">
                    <span>Save Changes</span>
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    /* ── Staggered reveal ── */
    var reveals = document.querySelectorAll('.pf-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('pf-visible');
        });
    }, 80);

    /* ── Custom Modal ── */
    var modal = document.getElementById('pfModal');
    var backdrop = document.getElementById('pfBackdrop');
    var editBtn = document.getElementById('pfEditBtn');
    var closeBtn = document.getElementById('pfModalClose');
    var cancelBtn = document.getElementById('pfCancelBtn');

    function openModal() {
        if (modal && backdrop) {
            modal.classList.add('active');
            backdrop.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (modal && backdrop) {
            modal.classList.remove('active');
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (editBtn) editBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
});

function togglePw(btn){
    var inp=btn.previousElementSibling;
    var ic=btn.querySelector('i');
    if(inp.type==='password'){inp.type='text';ic.className='bi bi-eye-slash';}
    else{inp.type='password';ic.className='bi bi-eye';}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>