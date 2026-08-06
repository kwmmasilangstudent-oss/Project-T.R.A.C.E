<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$userId = getSessionUserId();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

$currentUser = null;
$settings = [];
try {
    $user = $pdo->prepare('SELECT full_name, email, role FROM users WHERE id = ? LIMIT 1');
    $user->execute([$userId]);
    $currentUser = $user->fetch();

    $rows = $pdo->query('SELECT key_name, key_value FROM settings')->fetchAll();
    foreach ($rows as $row) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (Throwable $e) {
    $currentUser = null;
    $settings = [];
}

$emailNotif = $settings['email_notifications'] ?? '0';
$smsNotif = $settings['sms_notifications'] ?? '0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $emailNotifications = isset($_POST['email_notifications']) ? '1' : '0';
    $smsNotifications = isset($_POST['sms_notifications']) ? '1' : '0';

    if ($fullName === '') {
        $_SESSION['_flash_error'] = 'Full name is required.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?');
        $stmt->execute([$fullName, $userId]);

        if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
            $authStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $authStmt->execute([$userId]);
            $hash = $authStmt->fetchColumn();

            if (!password_verify($currentPassword, $hash)) {
                $_SESSION['_flash_error'] = 'Current password is incorrect.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } elseif ($newPassword !== $confirmPassword) {
                $_SESSION['_flash_error'] = 'New passwords do not match.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } elseif (strlen($newPassword) < 6) {
                $_SESSION['_flash_error'] = 'New password must be at least 6 characters.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([$newHash, $userId]);
            }
        }

        if (!$error) {
            $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['email_notifications', $emailNotifications]);
            $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['sms_notifications', $smsNotifications]);

            logAudit('update_settings', 'Secretary updated account settings');
            $_SESSION['_flash_success'] = 'Settings updated successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
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
   PROPERTIES
   ═══════════════════════════════ */
:root {
    --sg-accent: #10b981;
    --sg-accent-dark: #059669;
    --sg-violet: #8b5cf6;
    --sg-violet-dark: #7c3aed;
    --sg-sky: #0ea5e9;
    --sg-amber: #f59e0b;
    --sg-teal: #14b8a6;
    --sg-red: #ef4444;
    --sg-rose: #f43f5e;
    --sg-bg: #0f172a;
    --sg-card: rgba(255,255,255,0.03);
    --sg-text: #f0f4f8;
    --sg-text-sec: #94a3b8;
    --sg-text-muted: #64748b;
    --sg-text-dim: #475569;
    --sg-border: rgba(255,255,255,0.08);
    --sg-border-lt: rgba(255,255,255,0.12);
    --sg-rad: 12px;
    --sg-rad-lg: 16px;
    --sg-rad-xl: 20px;
}

html.light {
    --sg-accent: #059669;
    --sg-accent-dark: #047857;
    --sg-violet: #7c3aed;
    --sg-violet-dark: #6d28d9;
    --sg-sky: #0284c7;
    --sg-amber: #d97706;
    --sg-teal: #0f766e;
    --sg-red: #dc2626;
    --sg-rose: #e11d48;
    --sg-bg: #ffffff;
    --sg-card: rgba(0,0,0,0.02);
    --sg-text: #1e293b;
    --sg-text-sec: #475569;
    --sg-text-muted: #64748b;
    --sg-text-dim: #94a3b8;
    --sg-border: rgba(0,0,0,0.08);
    --sg-border-lt: rgba(0,0,0,0.12);
}

html.light .sg-title { color: #1e293b; }
html.light .sg-title span {
    background: linear-gradient(135deg, #7c3aed, #a78bfa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
html.light .sg-card-tt { color: #1e293b; }
html.light .sg-card-st { color: #64748b; }
html.light .sg-desc { color: #475569; }
html.light .sg-badge {
    background: rgba(139, 92, 246, 0.08);
    border-color: rgba(139, 92, 246, 0.2);
    color: #7c3aed;
}
html.light .sg-dot { background: #7c3aed; }
html.light .sg-label { color: #475569; }
html.light .sg-section { border-bottom-color: rgba(0,0,0,0.08); }
html.light .sg-section span { color: #64748b; }
html.light .sg-input,
html.light .sg-select {
    background: rgba(0,0,0,0.04);
    border-color: rgba(0,0,0,0.12);
    color: #1e293b;
}
html.light .sg-input::placeholder { color: rgba(0,0,0,0.4); }
html.light .sg-input:focus,
html.light .sg-select:focus {
    background: rgba(0,0,0,0.06);
    border-color: var(--sg-violet);
    box-shadow: 0 0 0 3px rgba(139,92,246,0.12);
}
html.light .sg-select option {
    background: #f8f9fa;
    color: #212529;
}
html.light .sg-hint { color: #94a3b8; }
html.light .sg-card {
    background: rgba(0,0,0,0.02);
    border-color: rgba(0,0,0,0.08);
}
html.light .sg-card:hover { border-color: rgba(0,0,0,0.14); }
html.light .sg-toggle-wrap {
    background: rgba(0,0,0,0.02);
    border-color: rgba(0,0,0,0.08);
}
html.light .sg-toggle-wrap:hover {
    background: rgba(0,0,0,0.04);
    border-color: rgba(0,0,0,0.14);
}
html.light .sg-toggle {
    background: rgba(0,0,0,0.06);
    border-color: rgba(0,0,0,0.15);
}
html.light .sg-toggle::after { background: #64748b; }
html.light .sg-toggle:checked { background: rgba(139,92,246,0.15); }
html.light .sg-toggle:checked::after { background: var(--sg-violet); }
html.light .sg-actions { border-top-color: rgba(0,0,0,0.08); }
html.light .sg-alert-success {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.2);
    color: #059669;
}
html.light .sg-alert-error {
    background: rgba(239,68,68,0.08);
    border-color: rgba(239,68,68,0.2);
    color: #dc2626;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--sg-bg) !important;
    color: var(--sg-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.sg-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.sg-page::after {
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

.sg-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: sgFloat 22s ease-in-out infinite;
}
.sg-orb.o1 { width: 440px; height: 440px; background: rgba(139,92,246,0.06); top: -12%; left: -8%; }
.sg-orb.o2 { width: 320px; height: 320px; background: rgba(16,185,129,0.06); bottom: -10%; right: -6%; animation-delay: -11s; }
.sg-orb.o3 { width: 240px; height: 240px; background: rgba(14,165,233,0.05); top: 50%; left: 35%; animation-delay: -5s; animation-duration: 26s; }

@keyframes sgFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.sg-page { min-height: 100vh; position: relative; z-index: 1; }

.sg-layout { display: flex; min-height: 100vh; }

.sg-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--sg-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.sg-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.sg-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.sg-head-left { flex: 1; min-width: 260px; }

.sg-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(139,92,246,0.12);
    border: 1px solid rgba(139,92,246,0.25);
    border-radius: 100px;
    color: #c4b5fd;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
    width: fit-content;
}

.sg-badge .sg-dot {
    width: 7px; height: 7px;
    background: var(--sg-violet);
    border-radius: 50%;
    animation: sgPulse 2s ease-in-out infinite;
}

@keyframes sgPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.sg-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.sg-title span {
    background: linear-gradient(135deg, var(--sg-violet), #a78bfa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.sg-desc {
    font-size: 0.92rem;
    color: var(--sg-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* ═══════════════════════════════
   ALERTS
   ═══════════════════════════════ */
.sg-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: var(--sg-rad);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: sgSlide 0.4s ease;
}
.sg-alert i { font-size: 1.15rem; flex-shrink: 0; }

@keyframes sgSlide {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.sg-alert-success {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.sg-alert-success i { color: var(--sg-accent); }

.sg-alert-error {
    background: rgba(239,68,68,0.10);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.sg-alert-error i { color: var(--sg-red); }

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.sg-card {
    background: var(--sg-card);
    border: 1px solid var(--sg-border);
    border-radius: var(--sg-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.sg-card:hover { border-color: rgba(255,255,255,0.12); }

.sg-card-hd {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.sg-card-ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.sg-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.sg-card-st {
    font-size: 0.82rem;
    color: var(--sg-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* Section dividers */
.sg-section {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 32px 0 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.sg-section:first-child { margin-top: 0; }

.sg-section i {
    font-size: 0.95rem;
    color: var(--sg-violet);
}

.sg-section span {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--sg-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

/* ═══════════════════════════════
   FORMS
   ═══════════════════════════════ */
.sg-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.sg-label i { font-size: 0.82rem; color: var(--sg-text-muted); }

.sg-input,
.sg-select {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--sg-rad);
    font-size: 0.88rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}

.sg-input::placeholder { color: #475569; }

.sg-input:focus,
.sg-select:focus {
    border-color: var(--sg-violet);
    box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
    background: rgba(255,255,255,0.07);
}

.sg-input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.sg-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}
.sg-select option { background: #1e293b; color: #e2e8f0; }

.sg-hint {
    font-size: 0.75rem;
    color: var(--sg-text-dim);
    margin-top: 6px;
}

/* Form grid */
.sg-fg {
    display: grid;
    gap: 18px;
}
.sg-fg-3 { grid-template-columns: 1fr 1fr 1fr; }
.sg-fg-2 { grid-template-columns: 1fr 1fr; }

@media (max-width: 991px) {
    .sg-fg-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .sg-fg-3,
    .sg-fg-2 { grid-template-columns: 1fr; }
}

/* Custom toggle switch */
.sg-toggle-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: rgba(255,255,255,0.025);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: var(--sg-rad);
    transition: all 0.25s ease;
}

.sg-toggle-wrap:hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.10);
}

.sg-toggle {
    appearance: none;
    width: 44px;
    height: 24px;
    border-radius: 100px;
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.15);
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.sg-toggle::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #94a3b8;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.sg-toggle:checked {
    background: rgba(139,92,246,0.2);
    border-color: var(--sg-violet);
}

.sg-toggle:checked::after {
    left: calc(100% - 18px);
    background: var(--sg-violet);
}

.sg-toggle:focus-visible {
    box-shadow: 0 0 0 3px rgba(139,92,246,0.2);
}

.sg-toggle-text {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--sg-text-sec);
}

.sg-toggle-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.sg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 28px;
    border: none;
    border-radius: var(--sg-rad);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}
.sg-btn i { transition: transform 0.2s ease; }

.sg-btn-violet {
    background: linear-gradient(135deg, var(--sg-violet), var(--sg-violet-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(139,92,246,0.25);
}
.sg-btn-violet:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(139,92,246,0.35);
    color: #ffffff;
}
.sg-btn-violet:active { transform: translateY(0); }
.sg-btn-violet:hover i { transform: translateX(3px); }

.sg-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,0.05);
}

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.sg-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.sg-reveal.sg-vis { opacity: 1; transform: translateY(0); }

.sg-d1 { transition-delay: 0.05s; }
.sg-d2 { transition-delay: 0.12s; }
.sg-d3 { transition-delay: 0.2s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .sg-main { padding: 32px 36px; }
}
@media (max-width: 991.98px) {
    .sg-layout { flex-direction: column; }
    .sg-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--sg-border);
    }
    .sg-main { padding: 28px 24px; }
    .sg-head { flex-direction: column; gap: 16px; }
}
@media (max-width: 767.98px) {
    .sg-main { padding: 24px 16px; }
    .sg-card { padding: 24px 20px; }
    .sg-title { font-size: 1.4rem; }
}
@media (max-width: 480px) {
    .sg-main { padding: 20px 14px; }
    .sg-card { padding: 20px 16px; border-radius: 16px; }
    .sg-toggle-wrap { padding: 12px 14px; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="sg-page">
    <div class="sg-orb o1"></div>
    <div class="sg-orb o2"></div>
    <div class="sg-orb o3"></div>

    <div class="sg-layout">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main -->
        <div class="sg-main">

            <!-- Header -->
            <div class="sg-head sg-reveal sg-d1">
                <div class="sg-head-left">
                    <div class="sg-badge">
                        <span class="sg-dot"></span>
                        Account
                    </div>
                    <h1 class="sg-title">My <span>Settings</span></h1>
                    <p class="sg-desc">Manage your profile and security settings.</p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="sg-alert sg-alert-success sg-reveal sg-d2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="sg-alert sg-alert-error sg-reveal sg-d2">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Profile Card -->
            <div class="sg-card sg-reveal sg-d2">
                <div class="sg-card-hd">
                    <div class="sg-card-ico" style="background:rgba(139,92,246,0.12); color:#8b5cf6;">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <div>
                        <h5 class="sg-card-tt">Profile &amp; Preferences</h5>
                        <p class="sg-card-st">Update your personal details, password, and notification preferences.</p>
                    </div>
                </div>

                <form method="post">
                    <?php echo csrfField(); ?>
                    <!-- Personal Details -->
                    <div class="sg-section">
                        <i class="bi bi-person-badge"></i>
                        <span>Personal Details</span>
                    </div>

                    <div class="sg-fg sg-fg-2">
                        <div>
                            <label class="sg-label"><i class="bi bi-person"></i> Full Name</label>
                            <input type="text" name="full_name" class="sg-input" placeholder="Juan Dela Cruz" required value="<?php echo e($currentUser['full_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="sg-label"><i class="bi bi-envelope"></i> Email Address</label>
                            <input type="email" class="sg-input" value="<?php echo e($currentUser['email'] ?? ''); ?>" disabled>
                            <span class="sg-hint">Contact admin to change your email address.</span>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="sg-section">
                        <i class="bi bi-shield-lock"></i>
                        <span>Change Password</span>
                    </div>

                    <div class="sg-fg sg-fg-3">
                        <div>
                            <label class="sg-label"><i class="bi bi-key"></i> Current Password</label>
                            <div style="position:relative;">
                                <input type="password" name="current_password" class="sg-input" placeholder="Enter current password" autocomplete="current-password" style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div>
                            <label class="sg-label"><i class="bi bi-lock"></i> New Password</label>
                            <div style="position:relative;">
                                <input type="password" name="new_password" class="sg-input" placeholder="Min 6 characters" autocomplete="new-password" style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div>
                            <label class="sg-label"><i class="bi bi-check2-square"></i> Confirm Password</label>
                            <div style="position:relative;">
                                <input type="password" name="confirm_password" class="sg-input" placeholder="Repeat new password" autocomplete="new-password" style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>

                   

                    <!-- Save -->
                    <div class="sg-actions">
                        <button type="submit" class="sg-btn sg-btn-violet">
                            <i class="bi bi-check-lg"></i>
                            <span>Save Settings</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.sg-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('sg-vis');
        });
    }, 60);
});

function togglePw(btn){
    var inp=btn.previousElementSibling;
    var ic=btn.querySelector('i');
    if(inp.type==='password'){inp.type='text';ic.className='bi bi-eye-slash';}
    else{inp.type='password';ic.className='bi bi-eye';}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>