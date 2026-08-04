<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

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
$securityQuestion = '';
$securityAnswer = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $securityQuestion = $_POST['security_question'] ?? '';
    $securityAnswer = trim($_POST['security_answer'] ?? '');
    $role = 'resident';

    $pwError = validatePasswordStrength($password);
    if ($pwError) {
        $error = $pwError;
    } elseif ($securityQuestion === '' || !in_array($securityQuestion, $securityQuestions)) {
        $error = 'Please select a valid security question.';
    } elseif ($securityAnswer === '') {
        $error = 'Please provide an answer to your security question.';
    } else {
        try {
            $pdo = getDbConnection();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Email already exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role, 'active', $securityQuestion, password_hash($securityAnswer, PASSWORD_DEFAULT)]);
                $userId = (int) $pdo->lastInsertId();

                $residentStmt = $pdo->prepare('INSERT INTO residents (user_id, full_name) VALUES (?, ?)');
                $residentStmt->execute([$userId, $fullName]);

                $success = 'Account created successfully. Please login.';
            }
        } catch (Throwable $e) {
            $error = 'Registration is temporarily unavailable. Please try again later.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-card p-4">
                <h3 class="mb-3">Register</h3>
                <p class="text-muted">Create a resident account and complete your profile later.</p>
                <?php if (!empty($error)) : ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
                <?php if (!empty($success)) : ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
                <form method="post">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div style="position:relative;">
                            <input type="password" name="password" class="form-control" required style="padding-right:40px;">
                            <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6c757d;padding:0;"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <hr class="my-3">
                    <p class="text-muted small mb-2">Security questions are used to verify your identity if you forget your password.</p>
                    <div class="mb-3">
                        <label class="form-label">Security Question</label>
                        <select name="security_question" class="form-select" required>
                            <option value="">-- Select a question --</option>
                            <?php foreach ($securityQuestions as $q): ?>
                                <option value="<?php echo e($q); ?>" <?php echo ($securityQuestion === $q) ? 'selected' : ''; ?>><?php echo e($q); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Security Answer</label>
                        <input type="text" name="security_answer" class="form-control" placeholder="Your answer" required value="<?php echo e($securityAnswer); ?>">
                    </div>
                    <button class="btn btn-primary w-100">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function togglePw(btn){
    var inp=btn.previousElementSibling;
    var ic=btn.querySelector('i');
    if(inp.type==='password'){inp.type='text';ic.className='bi bi-eye-slash';}
    else{inp.type='password';ic.className='bi bi-eye';}
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
