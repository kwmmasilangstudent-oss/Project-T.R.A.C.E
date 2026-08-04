<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$message = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

$step = $_GET['step'] ?? 'email';
$email = $_SESSION['_reset_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'lookup_email') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $_SESSION['_flash_error'] = 'Please enter your email address.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT id, full_name, security_question FROM users WHERE email = ? AND status = "active" AND security_question IS NOT NULL LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['_reset_email'] = $email;
            $_SESSION['_reset_user_id'] = $user['id'];
            $_SESSION['_reset_question'] = $user['security_question'];
            header('Location: forgot_password.php?step=answer');
            exit;
        } else {
            $_SESSION['_flash_error'] = 'No account found with that email, or no security question set.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if ($action === 'verify_answer') {
        $answer = trim($_POST['answer'] ?? '');
        if ($answer === '') {
            $_SESSION['_flash_error'] = 'Please provide your answer.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        $userId = $_SESSION['_reset_user_id'] ?? 0;
        if (!$userId) {
            header('Location: forgot_password.php');
            exit;
        }
        $stmt = $pdo->prepare('SELECT security_answer FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row && password_verify($answer, $row['security_answer'])) {
            $_SESSION['_reset_verified'] = true;
            header('Location: reset_password.php');
            exit;
        } else {
            $_SESSION['_flash_error'] = 'Incorrect answer. Please try again.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
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
.lp-field input, .lp-field select { width:100%; padding:12px 16px; border:1px solid #e2e8f0; border-radius:10px; font-size:0.9rem; outline:none; transition:border-color .2s; box-sizing:border-box; background:#fff; }
.lp-field input:focus, .lp-field select:focus { border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.1); }
.lp-btn { width:100%; padding:12px; background:#8b5cf6; color:#fff; border:none; border-radius:10px; font-size:0.9rem; font-weight:700; cursor:pointer; transition:background .2s; }
.lp-btn:hover { background:#7c3aed; }
.lp-back { display:block; text-align:center; margin-top:16px; font-size:0.85rem; color:#64748b; text-decoration:none; }
.lp-back:hover { color:#475569; }
.lp-alert { padding:12px 16px; border-radius:10px; font-size:0.85rem; margin-bottom:20px; }
.lp-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.lp-alert-error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.lp-question { font-size:0.95rem; font-weight:600; color:#1e293b; text-align:center; margin-bottom:20px; padding:16px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; }
.navbar, footer, .main-navbar { display:none !important; }
</style>
<div class="lp-wrap">
    <div class="lp-card">
        <div class="lp-logo"><?php echo e(APP_NAME); ?></div>
        <?php if ($step === 'email'): ?>
            <p class="lp-sub">Enter your email to verify your identity.</p>
            <?php if ($message): ?>
                <div class="lp-alert lp-alert-success"><?php echo e($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="lp-alert lp-alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="lookup_email">
                <div class="lp-field">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <button type="submit" class="lp-btn">Continue</button>
            </form>
        <?php elseif ($step === 'answer'): ?>
            <p class="lp-sub">Answer your security question to reset your password.</p>
            <?php if ($error): ?>
                <div class="lp-alert lp-alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <div class="lp-question"><?php echo e($_SESSION['_reset_question'] ?? ''); ?></div>
            <form method="post">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="verify_answer">
                <div class="lp-field">
                    <label>Your Answer</label>
                    <input type="text" name="answer" placeholder="Type your answer" required>
                </div>
                <button type="submit" class="lp-btn">Verify Answer</button>
            </form>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="lp-back">&larr; Back to Login</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
