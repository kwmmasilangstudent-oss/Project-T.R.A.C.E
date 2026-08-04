<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['resident']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

$userId = getSessionUserId();

$currentUser = null;
try {
    $user = $pdo->prepare('SELECT full_name, email, security_question FROM users WHERE id = ? LIMIT 1');
    $user->execute([$userId]);
    $currentUser = $user->fetch();
} catch (Throwable $e) {
    $currentUser = null;
}

$securityQuestions = [
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
    'What city were you born in?',
    'What was the name of your first school?',
    'What is your favorite food?',
    'What was the make of your first car?',
    'What is the name of your childhood best friend?',
    'What street did you grow up on?'
];
$currentQuestion = $currentUser['security_question'] ?? '';

$settings = [];
try {
    $rows = $pdo->query('SELECT key_name, key_value FROM settings')->fetchAll();
    foreach ($rows as $row) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (Throwable $e) {
    $settings = [];
}

$emailNotif = $settings['email_notifications'] ?? '0';
$smsNotif = $settings['sms_notifications'] ?? '0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
        http_response_code(400);
        die('<html><body style="font-family:sans-serif;padding:2rem;text-align:center"><h2>Security Error</h2><p>Invalid or expired form submission. <a href="javascript:history.back()">Go back</a> and try again.</p></body></html>');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'update_preferences') {
        $emailNotifications = isset($_POST['email_notifications']) ? '1' : '0';
        $smsNotifications = isset($_POST['sms_notifications']) ? '1' : '0';

        $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['email_notifications', $emailNotifications]);
        $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['sms_notifications', $smsNotifications]);
        logAudit('update_settings', 'Resident updated preferences');
        $_SESSION['_flash_success'] = 'Settings updated successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
        $emailNotif = $emailNotifications;
        $smsNotif = $smsNotifications;
    }

    if ($action === 'update_security_question') {
        $sqQuestion = $_POST['security_question'] ?? '';
        $sqAnswer = trim($_POST['security_answer'] ?? '');
        $sqCurrentPassword = $_POST['sq_current_password'] ?? '';

        if ($sqQuestion === '' || !in_array($sqQuestion, $securityQuestions)) {
            $_SESSION['_flash_error'] = 'Please select a valid security question.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        if ($sqAnswer === '') {
            $_SESSION['_flash_error'] = 'Please provide an answer to your security question.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        if ($sqCurrentPassword === '') {
            $_SESSION['_flash_error'] = 'Please enter your current password to save changes.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        $pwStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $pwStmt->execute([$userId]);
        $pwRow = $pwStmt->fetch();
        if (!$pwRow || !password_verify($sqCurrentPassword, $pwRow['password_hash'])) {
            $_SESSION['_flash_error'] = 'Incorrect current password. Security question not updated.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        $updateStmt = $pdo->prepare('UPDATE users SET security_question = ?, security_answer = ? WHERE id = ?');
        $updateStmt->execute([$sqQuestion, password_hash($sqAnswer, PASSWORD_DEFAULT), $userId]);
        logAudit('update_security_question', 'Resident updated security question');
        $_SESSION['_flash_success'] = 'Security question updated successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'delete_account') {
        $password = $_POST['delete_password'] ?? '';
        $userStmt = $pdo->prepare('SELECT password_hash, full_name FROM users WHERE id = ? LIMIT 1');
        $userStmt->execute([$userId]);
        $userData = $userStmt->fetch();

        if (!$userData || !password_verify($password, $userData['password_hash'])) {
            $_SESSION['_flash_error'] = 'Incorrect password. Account deletion cancelled.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $residentStmt = $pdo->prepare('SELECT id FROM residents WHERE user_id = ? LIMIT 1');
            $residentStmt->execute([$userId]);
            $resident = $residentStmt->fetch();

            if ($resident) {
                $residentId = (int) $resident['id'];
                $pdo->prepare('DELETE FROM personal_information WHERE resident_id = ?')->execute([$residentId]);
                $pdo->prepare('DELETE FROM residents WHERE id = ?')->execute([$residentId]);
            }

            $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);

            logAudit('delete_account', 'Resident deleted own account');
            session_destroy();
            header('Location: ' . BASE_URL . '/index.php');
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
   CUSTOM PROPERTIES
   ═══════════════════════════════ */
:root {
    --st-accent: #10b981;
    --st-accent-dark: #059669;
    --st-violet: #8b5cf6;
    --st-sky: #0ea5e9;
    --st-amber: #f59e0b;
    --st-teal: #14b8a6;
    --st-red: #ef4444;
    --st-rose: #f43f5e;
    --st-bg: #0f172a;
    --st-card: rgba(255,255,255,0.03);
    --st-text: #f1f5f9;
    --st-text-sec: #94a3b8;
    --st-text-muted: #64748b;
    --st-text-dim: #475569;
    --st-border: rgba(255,255,255,0.08);
    --st-border-lt: rgba(255,255,255,0.12);
    --st-rad: 12px;
    --st-rad-lg: 16px;
    --st-rad-xl: 20px;
}

html.light {
    --st-accent: #059669;
    --st-accent-dark: #047857;
    --st-violet: #7c3aed;
    --st-sky: #0284c7;
    --st-amber: #d97706;
    --st-teal: #0f766e;
    --st-red: #dc2626;
    --st-rose: #e11d48;
    --st-bg: #ffffff;
    --st-card: rgba(0,0,0,0.02);
    --st-surface: #f1f5f9;
    --st-surface-hover: #e2e8f0;
    --st-text: #1e293b;
    --st-text-sec: #475569;
    --st-text-muted: #64748b;
    --st-text-dim: #94a3b8;
    --st-border: rgba(0,0,0,0.08);
    --st-border-lt: rgba(0,0,0,0.12);
    --st-input-bg: #ffffff;
    --st-input-text: #1e293b;
    --st-input-placeholder: #94a3b8;
    --st-input-border: rgba(0,0,0,0.12);
    --st-input-focus-bg: #ffffff;
    --st-toggle-bg: rgba(0,0,0,0.10);
    --st-toggle-border: rgba(0,0,0,0.18);
    --st-toggle-circle: #64748b;
    --st-option-bg: #ffffff;
    --st-option-text: #1e293b;
    --st-section-border: rgba(0,0,0,0.06);
    --st-actions-border: rgba(0,0,0,0.06);
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--st-bg) !important;
    color: var(--st-text);
}

.navbar, footer, .main-navbar { display: none !important; }

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.st-layout { display: flex; min-height: 100vh; }

.st-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--st-border);
    background: var(--st-card);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.st-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.st-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.st-head-left { flex: 1; min-width: 260px; }

.st-badge {
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
    width: fit-content;
}

.st-dot {
    width: 7px; height: 7px;
    background: var(--st-accent);
    border-radius: 50%;
    animation: stPulse 2s ease-in-out infinite;
}

@keyframes stPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.st-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: var(--st-text);
    line-height: 1.2;
    margin-bottom: 8px;
}

.st-title span {
    background: linear-gradient(135deg, var(--st-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.st-desc {
    font-size: 0.92rem;
    color: var(--st-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* ═══════════════════════════════
   ALERTS
   ═══════════════════════════════ */
.st-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: var(--st-rad);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: stSlide 0.4s ease;
}
.st-alert i { font-size: 1.15rem; flex-shrink: 0; }

@keyframes stSlide {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.st-alert-success {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.st-alert-success i { color: var(--st-accent); }

.st-alert-error {
    background: rgba(239,68,68,0.10);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.st-alert-error i { color: var(--st-red); }

/* ═══════════════════════════════
   CARD
   ═══════════════════════════════ */
.st-card {
    background: var(--st-card);
    border: 1px solid var(--st-border);
    border-radius: var(--st-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.st-card:hover { border-color: var(--st-border-lt); }

.st-card-hd {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.st-card-ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.st-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--st-text);
    margin: 0;
    line-height: 1.3;
}

.st-card-st {
    font-size: 0.82rem;
    color: var(--st-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* Section dividers */
.st-section {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 32px 0 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--st-section-border);
}
.st-section:first-child { margin-top: 0; }

.st-section i {
    font-size: 0.95rem;
    color: var(--st-accent);
}

.st-section span {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--st-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

/* ═══════════════════════════════
   FORMS
   ═══════════════════════════════ */
.st-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--st-text-sec);
    margin-bottom: 8px;
}
.st-label i { font-size: 0.82rem; color: var(--st-text-muted); }

.st-input,
.st-select {
    width: 100%;
    padding: 12px 16px;
    background: var(--st-input-bg);
    border: 1px solid var(--st-input-border);
    border-radius: var(--st-rad);
    font-size: 0.88rem;
    color: var(--st-input-text);
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}

.st-input::placeholder { color: var(--st-input-placeholder); }

.st-input:focus,
.st-select:focus {
    border-color: var(--st-accent);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    background: var(--st-input-focus-bg);
}

.st-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}
.st-select option { background: var(--st-option-bg); color: var(--st-option-text); }

.st-hint {
    font-size: 0.75rem;
    color: var(--st-text-dim);
    margin-top: 6px;
}

/* Form grid */
.st-fg {
    display: grid;
    gap: 18px;
}
.st-fg-3 { grid-template-columns: 1fr 1fr 1fr; }
.st-fg-2 { grid-template-columns: 1fr 1fr; }

@media (max-width: 991px) {
    .st-fg-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .st-fg-3,
    .st-fg-2 { grid-template-columns: 1fr; }
}

/* Custom toggle switch */
.st-toggle-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    border-radius: var(--st-rad);
    transition: all 0.25s ease;
}

.st-toggle-wrap:hover {
    background: var(--st-surface-hover);
    border-color: var(--st-border-lt);
}

.st-toggle {
    appearance: none;
    width: 44px;
    height: 24px;
    border-radius: 100px;
    background: var(--st-toggle-bg);
    border: 2px solid var(--st-toggle-border);
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.st-toggle::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--st-toggle-circle);
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.st-toggle:checked {
    background: rgba(16,185,129,0.25);
    border-color: var(--st-accent);
}

.st-toggle:checked::after {
    left: calc(100% - 18px);
    background: var(--st-accent);
}

.st-toggle:focus-visible {
    box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
}

.st-toggle-text {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--st-text-sec);
}

.st-toggle-icon {
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
.st-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 28px;
    border: none;
    border-radius: var(--st-rad);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}
.st-btn i { transition: transform 0.2s ease; }

.st-btn-primary {
    background: linear-gradient(135deg, var(--st-accent), var(--st-accent-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
}
.st-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(16,185,129,0.35);
    color: #ffffff;
}
.st-btn-primary:active { transform: translateY(0); }
.st-btn-primary:hover i { transform: translateX(3px); }

.st-btn-danger {
    background: linear-gradient(135deg, var(--st-red), #dc2626);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(239,68,68,0.20);
}
.st-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(239,68,68,0.30);
    color: #ffffff;
}
.st-btn-danger:active { transform: translateY(0); }

.st-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--st-actions-border);
}

/* ═══════════════════════════════
   DANGER ZONE
   ═══════════════════════════════ */
.st-danger-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.st-danger-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.20);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--st-red);
}

.st-danger-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--st-red);
    margin: 0;
}

.st-danger-warning {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 14px 18px;
    background: rgba(239,68,68,0.06);
    border: 1px solid rgba(239,68,68,0.12);
    border-radius: var(--st-rad);
    margin-bottom: 24px;
    margin-top: 16px;
}

.st-danger-warning i {
    color: var(--st-amber);
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 1px;
}

.st-danger-warning p {
    font-size: 0.82rem;
    color: var(--st-text-muted);
    line-height: 1.6;
    margin: 0;
}

.st-danger-form-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 16px;
    align-items: end;
}

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.st-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.st-reveal.st-vis { opacity: 1; transform: translateY(0); }

.st-d1 { transition-delay: 0.05s; }
.st-d2 { transition-delay: 0.12s; }
.st-d3 { transition-delay: 0.20s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 991.98px) {
    .st-layout { flex-direction: column; }
    .st-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--st-border);
    }
    .st-main { padding: 28px 24px; }
    .st-head { flex-direction: column; gap: 16px; }
}

@media (max-width: 767.98px) {
    .st-main { padding: 24px 18px; }
    .st-card { padding: 24px 20px; }
    .st-title { font-size: 1.4rem; }
    .st-card-tt { font-size: 1.15rem; }
}

@media (max-width: 480px) {
    .st-main { padding: 20px 14px; }
    .st-card { padding: 20px 16px; }
    .st-title { font-size: 1.25rem; }
    .st-toggle-wrap { padding: 12px 14px; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="st-layout">
    <!-- Sidebar -->
    <div class="st-sidebar">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <!-- Main -->
    <div class="st-main">

        <!-- Header -->
        <div class="st-head st-reveal st-d1">
            <div class="st-head-left">
                <div class="st-badge">
                    <span class="st-dot"></span>
                    Account
                </div>
                <h1 class="st-title">My <span>Settings</span></h1>
                <p class="st-desc">Manage your profile, security preferences, and account settings.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success)) : ?>
            <div class="st-alert st-alert-success st-reveal st-d2">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo e($success); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)) : ?>
            <div class="st-alert st-alert-error st-reveal st-d2">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Card -->
        <div class="st-card st-reveal st-d2">
            <div class="st-card-hd">
                <div class="st-card-ico" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                    <i class="bi bi-person-gear"></i>
                </div>
                <div>
                    <h5 class="st-card-tt">Profile &amp; Preferences</h5>
                    <p class="st-card-st">Update your personal details, password, and notification preferences.</p>
                </div>
            </div>

                <form method="post" id="settingsForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_preferences">

                    <!-- Personal Details -->
                    <div class="st-section">
                        <i class="bi bi-person-badge"></i>
                        <span>Personal Details</span>
                    </div>

                <div class="st-fg st-fg-2">
                    <div>
                        <label class="st-label"><i class="bi bi-person"></i> Full Name</label>
                        <input type="text" name="full_name" class="st-input" placeholder="Juan Dela Cruz" required value="<?php echo e($currentUser['full_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="st-label"><i class="bi bi-envelope"></i> Email Address</label>
                        <input type="email" class="st-input" value="<?php echo e($currentUser['email'] ?? ''); ?>" disabled>
                        <span class="st-hint">Contact admin to change your email address.</span>
                    </div>
                </div>

                <!-- Password -->
                <div class="st-section">
                    <i class="bi bi-shield-lock"></i>
                    <span>Change Password</span>
                </div>

                <div class="st-fg st-fg-3">
                    <div>
                        <label class="st-label"><i class="bi bi-key"></i> Current Password</label>
                        <div style="position:relative;">
                            <input type="password" name="current_password" class="st-input" placeholder="Enter current password" autocomplete="current-password" style="padding-right:40px;">
                            <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div>
                        <label class="st-label"><i class="bi bi-lock"></i> New Password</label>
                        <div style="position:relative;">
                            <input type="password" name="new_password" class="st-input" placeholder="Min 6 characters" autocomplete="new-password" style="padding-right:40px;">
                            <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div>
                        <label class="st-label"><i class="bi bi-check2-square"></i> Confirm Password</label>
                        <div style="position:relative;">
                            <input type="password" name="confirm_password" class="st-input" placeholder="Repeat new password" autocomplete="new-password" style="padding-right:40px;">
                            <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Security Questions -->
                <div class="st-section">
                    <i class="bi bi-shield-check"></i>
                    <span>Security Question</span>
                </div>
                <p style="font-size:0.82rem;color:var(--st-text-muted);margin-bottom:16px;">Used to verify your identity if you forget your password.</p>

                <form method="post" id="sqForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_security_question">
                    <div class="st-fg st-fg-2">
                        <div>
                            <label class="st-label"><i class="bi bi-question-circle"></i> Question</label>
                            <select name="security_question" class="st-select" required>
                                <option value="">-- Select a question --</option>
                                <?php foreach ($securityQuestions as $q): ?>
                                    <option value="<?php echo e($q); ?>" <?php echo ($currentQuestion === $q) ? 'selected' : ''; ?>><?php echo e($q); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="st-label"><i class="bi bi-pencil"></i> Answer</label>
                            <input type="text" name="security_answer" class="st-input" placeholder="Your answer" required>
                        </div>
                    </div>
                    <div class="st-fg" style="margin-top:16px;">
                        <div>
                            <label class="st-label"><i class="bi bi-key"></i> Current Password (to confirm changes)</label>
                            <div style="position:relative;">
                                <input type="password" name="sq_current_password" class="st-input" placeholder="Enter current password" required style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="st-actions">
                        <button type="submit" class="st-btn st-btn-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>Update Security Question</span>
                        </button>
                    </div>
                </form>

                <!-- Preferences -->
                <div class="st-section">
                    <i class="bi bi-sliders"></i>
                    <span>Preferences</span>
                </div>

                <div class="st-fg st-fg-3">
                    <div>
                        <label class="st-label" style="margin-bottom:12px;"><i class="bi bi-bell"></i> Notifications</label>
                        <div class="st-toggle-wrap">
                            <div class="st-toggle-icon" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <input type="checkbox" name="email_notifications" class="st-toggle" id="stEmailNotif" <?php echo $emailNotif === '1' ? 'checked' : ''; ?>>
                            <label class="st-toggle-text" for="stEmailNotif">Email Alerts</label>
                        </div>
                    </div>
                    <div>
                        <label class="st-label" style="margin-bottom:12px;"><i class="bi bi-phone"></i> Mobile</label>
                        <div class="st-toggle-wrap">
                            <div class="st-toggle-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <input type="checkbox" name="sms_notifications" class="st-toggle" id="stSmsNotif" <?php echo $smsNotif === '1' ? 'checked' : ''; ?>>
                            <label class="st-toggle-text" for="stSmsNotif">SMS Alerts</label>
                        </div>
                    </div>
                </div>

                <!-- Save -->
                <div class="st-actions">
                    <button type="submit" class="st-btn st-btn-primary">
                        <i class="bi bi-check-lg"></i>
                        <span>Save Settings</span>
                    </button>
                </div>
            </form>

            <!-- Danger Zone -->
            <div class="st-section">
                <i class="bi bi-exclamation-octagon"></i>
                <span>Danger Zone</span>
            </div>

            <div class="st-card st-reveal st-d3">
                <div class="st-danger-warning">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                </div>

                <form method="post" id="deleteForm" onsubmit="return confirm('This will permanently delete your account and all associated data. This cannot be undone. Are you absolutely sure?');">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="delete_account">

                    <div class="st-fg st-fg-2" style="margin-bottom:0;">
                        <div>
                            <label class="st-label"><i class="bi bi-key"></i> Confirm with Password</label>
                            <div style="position:relative;">
                                <input type="password" name="delete_password" class="st-input" placeholder="Enter your current password" required style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button type="submit" class="st-btn st-btn-danger" style="width:100%;">
                                <i class="bi bi-trash3"></i>
                                <span>Delete Account</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.st-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('st-vis');
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
