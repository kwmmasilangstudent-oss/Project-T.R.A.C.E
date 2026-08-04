<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$message = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
$verified = !empty($_SESSION['_reset_verified']);
$email = $_SESSION['_reset_email'] ?? '';

if (!$verified || $email === '') {
    header('Location: ' . BASE_URL . '/auth/forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $pwError = validatePasswordStrength($password);
    if ($pwError) {
        $_SESSION['_flash_error'] = $pwError;
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif ($password !== $confirm) {
        $_SESSION['_flash_error'] = 'Passwords do not match.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([$hash, $email]);
        unset($_SESSION['_reset_verified'], $_SESSION['_reset_email'], $_SESSION['_reset_user_id'], $_SESSION['_reset_question']);
        $_SESSION['_flash_success'] = 'Password has been reset successfully. You can now login.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<style>
body { background: #f8fafc !important; }
.lp-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
.lp-card { width:100%; max-width:440px; background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.06); padding:40px; }
.lp-logo { font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:800; color:#0f172a; text-align:center; margin-bottom:6px; }
.lp-sub { font-size:0.9rem; color:#64748b; text-align:center; margin-bottom:28px; }
.lp-field { margin-bottom:20px; }
.lp-field label { display:block; font-size:0.82rem; font-weight:600; color:#475569; margin-bottom:6px; }
.lp-field input { width:100%; padding:12px 16px; border:1px solid #e2e8f0; border-radius:10px; font-size:0.9rem; outline:none; transition:border-color .2s; box-sizing:border-box; }
.lp-field input:focus { border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.1); }
.lp-field { position:relative; }
.lp-pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:0; font-size:1rem; }
.lp-pw-toggle:hover { color:#64748b; }
.lp-btn { width:100%; padding:12px; background:#8b5cf6; color:#fff; border:none; border-radius:10px; font-size:0.9rem; font-weight:700; cursor:pointer; transition:background .2s; }
.lp-btn:hover { background:#7c3aed; }
.lp-back { display:block; text-align:center; margin-top:16px; font-size:0.85rem; color:#64748b; text-decoration:none; }
.lp-back:hover { color:#475569; }
.lp-alert { padding:12px 16px; border-radius:10px; font-size:0.85rem; margin-bottom:20px; }
.lp-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.lp-alert-error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.navbar, footer, .main-navbar { display:none !important; }
</style>
<div class="lp-wrap">
    <div class="lp-card">
        <div class="lp-logo"><?php echo e(APP_NAME); ?></div>
        <?php if ($message): ?>
            <p class="lp-sub"><?php echo e($message); ?></p>
            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="lp-btn" style="display:block;text-align:center;text-decoration:none;">Go to Login</a>
        <?php else: ?>
            <p class="lp-sub">Enter your new password.</p>
            <?php if ($error): ?>
                <div class="lp-alert lp-alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <?php echo csrfField(); ?>
                <div class="lp-field">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="At least 8 characters" required style="padding-right:40px;">
                    <button type="button" class="lp-pw-toggle" onclick="togglePw(this)"><i class="bi bi-eye"></i></button>
                </div>
                <div class="lp-field">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required style="padding-right:40px;">
                    <button type="button" class="lp-pw-toggle" onclick="togglePw(this)"><i class="bi bi-eye"></i></button>
                </div>
                <button type="submit" class="lp-btn">Reset Password</button>
            </form>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="lp-back">&larr; Back to Login</a>
    </div>
</div>
<script>
function togglePw(btn){
    var inp=btn.previousElementSibling;
    var ic=btn.querySelector('i');
    if(inp.type==='password'){inp.type='text';ic.className='bi bi-eye-slash';}
    else{inp.type='password';ic.className='bi bi-eye';}
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
