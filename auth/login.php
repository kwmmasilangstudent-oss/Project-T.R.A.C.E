<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $attempts = &$_SESSION['_login_attempts'];
    if (!isset($attempts)) {
        $attempts = ['count' => 0, 'first' => 0];
    }
    if ($attempts['count'] >= 5 && (time() - $attempts['first']) < 300) {
        $error = 'Too many login attempts. Please try again in 5 minutes.';
    } else {
        if ($attempts['count'] >= 5 || (time() - $attempts['first']) > 300) {
            $attempts = ['count' => 0, 'first' => 0];
        }
        if ($attempts['count'] === 0) {
            $attempts['first'] = time();
        }
        $attempts['count']++;

        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (Throwable $e) {
            $user = null;
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['_login_attempts']);
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        $error = 'Invalid email or password.';
    }
}

$error = $error ?? '';

$barangayName = 'Barangay Tumalaytay';
try {
    $pdo = getDbConnection();
    $n = $pdo->prepare("SELECT key_value FROM settings WHERE key_name = 'barangay_name'");
    $n->execute();
    $v = $n->fetchColumn();
    if ($v) $barangayName = $v;
} catch (Throwable $e) {}

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
    --lg-primary: #1a56db;
    --lg-primary-dark: #1042a3;
    --lg-primary-light: #e8effc;
    --lg-accent: #10b981;
    --lg-accent-dark: #059669;
    --lg-teal: #14b8a6;
    --lg-amber: #f59e0b;
    --lg-red: #ef4444;
    --lg-violet: #8b5cf6;
    --lg-bg: #f0f4f8;
    --lg-card: #ffffff;
    --lg-hero-bg: #0f172a;
    --lg-text: #0f172a;
    --lg-muted: #64748b;
    --lg-light: #94a3b8;
    --lg-border: #e2e8f0;
    --lg-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --lg-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
    --lg-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
    --lg-shadow-xl: 0 20px 60px rgba(0,0,0,0.15);
    --lg-radius: 12px;
    --lg-radius-lg: 20px;
    --lg-radius-xl: 28px;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--lg-text);
    overflow-x: hidden;
}

/* ═══════════════════════════════
   FULL-PAGE AUTH LAYOUT
   ═══════════════════════════════ */
.lg-auth-wrapper {
    min-height: 100vh;
    display: flex;
    background: var(--lg-hero-bg);
    position: relative;
    overflow: hidden;
}

/* Noise */
.lg-auth-wrapper::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

/* Grid */
.lg-grid-overlay {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

/* Orbs */
.lg-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    z-index: 0;
    animation: lgFloat 20s ease-in-out infinite;
}

.lg-orb.o1 { width: 400px; height: 400px; background: rgba(16,185,129,0.08); top: -10%; left: -8%; }
.lg-orb.o2 { width: 300px; height: 300px; background: rgba(139,92,246,0.06); bottom: -10%; right: -6%; animation-delay: -10s; }
.lg-orb.o3 { width: 220px; height: 220px; background: rgba(14,165,233,0.06); top: 40%; right: 30%; animation-delay: -5s; animation-duration: 25s; }

@keyframes lgFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LEFT PANEL (branding)
   ═══════════════════════════════ */
.lg-left-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px 80px;
    position: relative;
    z-index: 2;
}

.lg-left-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: 100px;
    color: #6ee7b7;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 24px;
    width: fit-content;
}

.lg-left-badge .lg-dot {
    width: 8px; height: 8px;
    background: var(--lg-accent);
    border-radius: 50%;
    animation: lgPulse 2s ease-in-out infinite;
}

@keyframes lgPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.lg-left-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 900;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 16px;
}

.lg-left-title span {
    background: linear-gradient(135deg, var(--lg-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lg-left-desc {
    font-size: 1.05rem;
    color: #94a3b8;
    max-width: 480px;
    line-height: 1.7;
    margin-bottom: 40px;
}

/* Features */
.lg-features {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.lg-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.lg-feature-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.lg-feature-text h6 {
    font-weight: 700;
    font-size: 0.9rem;
    color: #e2e8f0;
    margin-bottom: 3px;
}

.lg-feature-text p {
    font-size: 0.82rem;
    color: #64748b;
    line-height: 1.5;
    margin: 0;
}

/* Divider line */
.lg-left-divider {
    width: 60px;
    height: 3px;
    background: linear-gradient(135deg, var(--lg-accent), #34d399);
    border-radius: 2px;
    margin: 32px 0 24px;
}

/* Stats row */
.lg-left-stats {
    display: flex;
    gap: 32px;
}

.lg-left-stat-val {
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 4px;
}

.lg-left-stat-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

/* ═══════════════════════════════
   RIGHT PANEL (form)
   ═══════════════════════════════ */
.lg-right-panel {
    width: 520px;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    position: relative;
    z-index: 2;
    background: rgba(255,255,255,0.02);
    border-left: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(40px);
}

.lg-form-container {
    width: 100%;
    max-width: 380px;
}

/* Form header */
.lg-form-header {
    margin-bottom: 36px;
}

.lg-form-lock {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--lg-accent);
    margin-bottom: 22px;
}

.lg-form-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 8px;
}

.lg-form-subtitle {
    font-size: 0.92rem;
    color: #94a3b8;
    line-height: 1.6;
}

/* Error */
.lg-error {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    background: rgba(239,68,68,0.10);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: var(--lg-radius);
    margin-bottom: 24px;
    color: #fca5a5;
    font-size: 0.88rem;
    font-weight: 500;
    animation: lgShake 0.4s ease;
}

.lg-error i {
    font-size: 1.1rem;
    color: #ef4444;
    flex-shrink: 0;
}

@keyframes lgShake {
    0%, 100% { transform: translateX(0); }
    20%      { transform: translateX(-6px); }
    40%      { transform: translateX(6px); }
    60%      { transform: translateX(-4px); }
    80%      { transform: translateX(4px); }
}

/* Form groups */
.lg-form-group {
    margin-bottom: 20px;
}

.lg-form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}

.lg-form-label i {
    font-size: 0.85rem;
    color: var(--lg-light);
}

.lg-input-wrap {
    position: relative;
}

.lg-input {
    width: 100%;
    padding: 14px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--lg-radius);
    font-size: 0.92rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}

.lg-input::placeholder { color: #475569; }

.lg-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    background: rgba(255,255,255,0.07);
}

.lg-input.has-error {
    border-color: var(--lg-red);
    box-shadow: 0 0 0 3px rgba(239,68,68,0.12);
}

.lg-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #475569;
    font-size: 1rem;
    pointer-events: none;
    transition: color 0.25s ease;
}

.lg-input:focus ~ .lg-input-icon { color: var(--lg-accent); }

.lg-input.has-icon { padding-left: 42px; }

/* Password toggle */
.lg-pw-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: transparent;
    border: none;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.lg-pw-toggle:hover {
    color: #94a3b8;
    background: rgba(255,255,255,0.05);
}

/* Submit button */
.lg-submit {
    width: 100%;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--lg-accent), var(--lg-accent-dark));
    border: none;
    border-radius: var(--lg-radius);
    color: #ffffff;
    font-size: 0.95rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 28px;
}

.lg-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.35);
}

.lg-submit:active { transform: translateY(0); }

.lg-submit i { transition: transform 0.2s ease; }
.lg-submit:hover i { transform: translateX(3px); }

/* Links row */
.lg-links {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 28px;
}

.lg-link {
    font-size: 0.85rem;
    font-weight: 500;
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.lg-link:hover { color: #e2e8f0; }
.lg-link i { font-size: 0.85rem; }

/* Security note */
.lg-security {
    margin-top: 36px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: var(--lg-radius);
}

.lg-security i {
    font-size: 0.9rem;
    color: var(--lg-accent);
    flex-shrink: 0;
}

.lg-security p {
    font-size: 0.75rem;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}

/* ═══════════════════════════════
   SCROLL REVEAL (used on left panel)
   ═══════════════════════════════ */
.lg-reveal {
    opacity: 0;
    transform: translateY(25px);
    transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.lg-reveal.lg-visible {
    opacity: 1;
    transform: translateY(0);
}

.lg-d1 { transition-delay: 0.1s; }
.lg-d2 { transition-delay: 0.2s; }
.lg-d3 { transition-delay: 0.3s; }
.lg-d4 { transition-delay: 0.4s; }
.lg-d5 { transition-delay: 0.5s; }
.lg-d6 { transition-delay: 0.6s; }
.lg-d7 { transition-delay: 0.7s; }
.lg-d8 { transition-delay: 0.8s; }

/* Form reveal */
.lg-form-reveal {
    opacity: 0;
    transform: translateX(30px);
    transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    transition-delay: 0.3s;
}

.lg-form-reveal.lg-visible {
    opacity: 1;
    transform: translateX(0);
}

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .lg-left-panel { padding: 60px 50px; }
    .lg-right-panel { width: 460px; }
}

@media (max-width: 991.98px) {
    .lg-auth-wrapper {
        flex-direction: column;
        min-height: auto;
    }

    .lg-left-panel {
        padding: 50px 32px 40px;
        text-align: center;
        align-items: center;
    }

    .lg-left-badge { margin-left: auto; margin-right: auto; }
    .lg-left-desc { margin-left: auto; margin-right: auto; }
    .lg-left-divider { margin-left: auto; margin-right: auto; }
    .lg-features { align-items: center; }
    .lg-feature-item { max-width: 340px; }
    .lg-left-stats { justify-content: center; }

    .lg-right-panel {
        width: 100%;
        min-height: auto;
        padding: 40px 32px 60px;
        border-left: none;
        border-top: 1px solid rgba(255,255,255,0.06);
    }

    .lg-form-container { max-width: 400px; }
}

@media (max-width: 767.98px) {
    .lg-left-panel { padding: 40px 20px 32px; }
    .lg-right-panel { padding: 32px 20px 50px; }
    .lg-left-title { font-size: 1.6rem; }
    .lg-left-desc { font-size: 0.95rem; }
    .lg-form-title { font-size: 1.5rem; }
    .lg-features { gap: 14px; }
    .lg-feature-icon { width: 36px; height: 36px; font-size: 0.9rem; }
    .lg-left-stats { gap: 24px; }
}

@media (max-width: 480px) {
    .lg-left-panel { padding: 32px 16px 28px; }
    .lg-right-panel { padding: 28px 16px 40px; }
    .lg-left-stats { flex-direction: column; gap: 16px; align-items: center; }
    .lg-links { flex-direction: column; align-items: center; }
    .lg-form-lock { width: 48px; height: 48px; border-radius: 14px; font-size: 1.2rem; }
}


</style>

<!-- Hide default page chrome for full-screen auth -->
<style>
    .navbar, footer, .main-navbar { display: none !important; }
    body { background: #0f172a !important; }
</style>

<!-- ═══════════════════════════════════════
     AUTH LAYOUT
     ═══════════════════════════════════════ -->
<div class="lg-auth-wrapper">
    <div class="lg-grid-overlay"></div>
    <div class="lg-orb o1"></div>
    <div class="lg-orb o2"></div>
    <div class="lg-orb o3"></div>

    <!-- ── LEFT: Branding ── -->
    <div class="lg-left-panel">
        <div class="lg-left-badge lg-reveal lg-d1">
            <span class="lg-dot"></span>
            Secure Portal
        </div>

        <h1 class="lg-left-title lg-reveal lg-d2">
            Welcome to <span><?php echo e($barangayName); ?></span>
        </h1>

        <p class="lg-left-desc lg-reveal lg-d3">
            Sign in to access barangay services, track your requests, manage documents, and stay connected with your community.
        </p>

        <div class="lg-features">
            <div class="lg-feature-item lg-reveal lg-d4">
                <div class="lg-feature-icon" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="lg-feature-text">
                    <h6>Certificate Requests</h6>
                    <p>Apply for barangay clearance, residency, and other documents online.</p>
                </div>
            </div>
           
            <div class="lg-feature-item lg-reveal lg-d6">
                <div class="lg-feature-icon" style="background:rgba(139,92,246,0.12); color:#8b5cf6;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div class="lg-feature-text">
                    <h6>Secure & Private</h6>
                    <p>Your data is encrypted and protected under government privacy standards.</p>
                </div>
            </div>
        </div>

        <div class="lg-left-divider lg-reveal lg-d7"></div>

        
    </div>

    <!-- ── RIGHT: Login Form ── -->
    <div class="lg-right-panel">
        <div class="lg-form-container lg-form-reveal">
            <div class="lg-form-header">
                <div class="lg-form-lock">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h2 class="lg-form-title">Sign In</h2>
                <p class="lg-form-subtitle">Enter your credentials to access the portal.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="lg-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="on">
                <?php echo csrfField(); ?>
                <div class="lg-form-group">
                    <label class="lg-form-label">
                        <i class="bi bi-envelope"></i> Email Address
                    </label>
                    <div class="lg-input-wrap">
                        <input
                            type="email"
                            name="email"
                            class="lg-input has-icon <?php echo !empty($error) ? 'has-error' : ''; ?>"
                            placeholder="your@email.com"
                            required
                            autocomplete="email"
                            autofocus
                            value="<?php echo e($_POST['email'] ?? ''); ?>"
                        >
                        <i class="lg-input-icon bi bi-envelope"></i>
                    </div>
                </div>

                <div class="lg-form-group">
                    <label class="lg-form-label">
                        <i class="bi bi-key"></i> Password
                    </label>
                    <div class="lg-input-wrap">
                        <input
                            type="password"
                            name="password"
                            id="lgPassword"
                            class="lg-input has-icon <?php echo !empty($error) ? 'has-error' : ''; ?>"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <i class="lg-input-icon bi bi-lock"></i>
                        <button type="button" class="lg-pw-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="lgPwIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="lg-submit">
                    <span>Sign In</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="lg-links">
                <a href="forgot_password.php" class="lg-link">
                    <i class="bi bi-key"></i> Forgot password?
                </a>
                <a href="<?php echo BASE_URL; ?>/index.php" class="lg-link">
                    <i class="bi bi-house"></i> Back to Home
                </a>
            </div>

            <div class="lg-security">
                <i class="bi bi-shield-fill-check"></i>
                <p>Your login is protected with industry-standard encryption. Never share your password with anyone.</p>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
      SCRIPTS
      ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ── Reveal animations ── */
    var reveals = document.querySelectorAll('.lg-reveal, .lg-form-reveal');
    /* Trigger immediately with slight delay for cinematic entrance */
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('lg-visible');
        });
    }, 100);
});

/* ── Password Toggle ── */
function togglePassword() {
    var input = document.getElementById('lgPassword');
    var icon = document.getElementById('lgPwIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
