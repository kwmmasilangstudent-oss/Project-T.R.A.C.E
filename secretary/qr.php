<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/qr.php';

$pdo = getDbConnection();
$residents = [];
try {
    $residents = $pdo->query('SELECT id, full_name FROM residents ORDER BY full_name')->fetchAll();
} catch (Throwable $e) {
    $residents = [];
}

$message = $_SESSION['_flash_message'] ?? '';
$messageType = $_SESSION['_flash_message_type'] ?? 'info';
unset($_SESSION['_flash_message'], $_SESSION['_flash_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $residentId = (int) ($_POST['resident_id'] ?? 0);
    $action = $_POST['action'] ?? 'generate';

    if ($residentId) {
        $residentStmt = $pdo->prepare('SELECT full_name FROM residents WHERE id = ? LIMIT 1');
        $residentStmt->execute([$residentId]);
        $resident = $residentStmt->fetch();
        $value = 'resident:' . $residentId . ':' . ($resident['full_name'] ?? 'unknown');

        if ($action === 'verify') {
            $_SESSION['_flash_message'] = 'Verification ready for resident ID ' . $residentId . '.';
            $_SESSION['_flash_message_type'] = 'info';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $insertStmt = $pdo->prepare('INSERT INTO qr_codes (resident_id, qr_value) VALUES (?, ?)');
            $insertStmt->execute([$residentId, $value]);
            $_SESSION['_flash_message'] = 'QR code generated for ' . ($resident['full_name'] ?? 'resident') . '.';
            $_SESSION['_flash_message_type'] = 'success';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } else {
        $_SESSION['_flash_message'] = 'Please choose a resident.';
        $_SESSION['_flash_message_type'] = 'warning';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
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
    --qr-accent: #10b981;
    --qr-accent-dark: #059669;
    --qr-violet: #8b5cf6;
    --qr-violet-dark: #7c3aed;
    --qr-sky: #0ea5e9;
    --qr-amber: #f59e0b;
    --qr-red: #ef4444;
    --qr-bg: #0f172a;
    --qr-card: rgba(255,255,255,0.03);
    --qr-text: #f0f4f8;
    --qr-text-sec: #94a3b8;
    --qr-text-muted: #64748b;
    --qr-text-dim: #475569;
    --qr-border: rgba(255,255,255,0.08);
    --qr-border-lt: rgba(255,255,255,0.12);
    --qr-rad: 12px;
    --qr-rad-lg: 16px;
    --qr-rad-xl: 20px;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--qr-bg) !important;
    color: var(--qr-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.qr-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.qr-page::after {
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

.qr-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: qrFloat 22s ease-in-out infinite;
}
.qr-orb.o1 { width: 420px; height: 420px; background: rgba(139,92,246,0.07); top: -10%; right: -6%; }
.qr-orb.o2 { width: 300px; height: 300px; background: rgba(16,185,129,0.06); bottom: -8%; left: -5%; animation-delay: -10s; }
.qr-orb.o3 { width: 220px; height: 220px; background: rgba(14,165,233,0.05); top: 50%; left: 35%; animation-delay: -5s; animation-duration: 26s; }

@keyframes qrFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.qr-page { min-height: 100vh; position: relative; z-index: 1; }

.qr-layout { display: flex; min-height: 100vh; }

.qr-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--qr-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.qr-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.qr-head {
    margin-bottom: 36px;
}

.qr-badge {
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

.qr-badge .qr-dot {
    width: 7px; height: 7px;
    background: var(--qr-violet);
    border-radius: 50%;
    animation: qrPulse 2s ease-in-out infinite;
}

@keyframes qrPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.qr-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.qr-title span {
    background: linear-gradient(135deg, var(--qr-violet), #a78bfa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.qr-desc {
    font-size: 0.92rem;
    color: var(--qr-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* ═══════════════════════════════
   ALERTS
   ═══════════════════════════════ */
.qr-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: var(--qr-rad);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: qrSlide 0.4s ease;
}

.qr-alert i { font-size: 1.15rem; flex-shrink: 0; }

@keyframes qrSlide {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.qr-alert-success {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.qr-alert-success i { color: var(--qr-accent); }

.qr-alert-info {
    background: rgba(14,165,233,0.10);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.qr-alert-info i { color: var(--qr-sky); }

.qr-alert-warning {
    background: rgba(245,158,11,0.10);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.qr-alert-warning i { color: var(--qr-amber); }

/* ═══════════════════════════════
   CENTERED CARD
   ═══════════════════════════════ */
.qr-center-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 0;
}

.qr-feature-card {
    width: 100%;
    max-width: 560px;
    background: var(--qr-card);
    border: 1px solid var(--qr-border);
    border-radius: var(--qr-rad-xl);
    backdrop-filter: blur(40px);
    padding: 40px 36px;
    transition: border-color 0.3s ease;
    position: relative;
    overflow: hidden;
}
.qr-feature-card:hover { border-color: rgba(255,255,255,0.12); }

/* Decorative corner accents */
.qr-feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--qr-violet), #a78bfa);
    border-radius: 0 0 3px 0;
}

.qr-feature-card::after {
    content: '';
    position: absolute;
    bottom: 0;
    right: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #a78bfa, var(--qr-violet));
    border-radius: 3px 0 0 0;
}

/* QR visual icon area */
.qr-visual {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
}

.qr-visual-ring {
    width: 88px;
    height: 88px;
    border-radius: 24px;
    background: rgba(139,92,246,0.08);
    border: 2px solid rgba(139,92,246,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.qr-visual-ring i {
    font-size: 2rem;
    color: var(--qr-violet);
}

/* Animated scan line */
.qr-visual-ring::after {
    content: '';
    position: absolute;
    top: 12px;
    left: 15%;
    right: 15%;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(139,92,246,0.6), transparent);
    border-radius: 2px;
    animation: qrScan 2.5s ease-in-out infinite;
}

@keyframes qrScan {
    0%, 100% { top: 12px; opacity: 0.3; }
    50%      { top: 60px; opacity: 1; }
}

.qr-card-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
    text-align: center;
    margin-bottom: 6px;
}

.qr-card-sub {
    font-size: 0.85rem;
    color: var(--qr-text-muted);
    text-align: center;
    margin-bottom: 32px;
    line-height: 1.5;
}

/* ═══════════════════════════════
   FORM
   ═══════════════════════════════ */
.qr-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.qr-label i { font-size: 0.82rem; color: var(--qr-text-muted); }

.qr-select {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--qr-rad);
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
.qr-select option { background: #1e293b; color: #e2e8f0; }

.qr-select:focus {
    border-color: var(--qr-violet);
    box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
    background-color: rgba(255,255,255,0.07);
}

.qr-form-group {
    margin-bottom: 18px;
}

/* Action radio cards */
.qr-action-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.qr-action-card {
    position: relative;
}

.qr-action-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.qr-action-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 18px 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: var(--qr-rad-lg);
    cursor: pointer;
    transition: all 0.25s ease;
    text-align: center;
}

.qr-action-label:hover {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.15);
}

.qr-action-card input[type="radio"]:checked + .qr-action-label {
    background: rgba(139,92,246,0.10);
    border-color: rgba(139,92,246,0.35);
}

.qr-action-label .qr-action-ico {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.25s ease;
}

.qr-action-card input[type="radio"]:checked + .qr-action-label .qr-action-ico {
    transform: scale(1.08);
}

.qr-action-label .qr-action-text {
    font-size: 0.82rem;
    font-weight: 600;
    color: #cbd5e1;
}

.qr-action-label .qr-action-desc {
    font-size: 0.72rem;
    color: var(--qr-text-dim);
    line-height: 1.4;
}

.qr-action-card input[type="radio"]:checked + .qr-action-label .qr-action-text {
    color: #e9d5ff;
}

/* Submit */
.qr-btn {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border: none;
    border-radius: var(--qr-rad);
    font-size: 0.9rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    margin-top: 8px;
}
.qr-btn i { transition: transform 0.2s ease; }

.qr-btn-violet {
    background: linear-gradient(135deg, var(--qr-violet), var(--qr-violet-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(139,92,246,0.25);
}
.qr-btn-violet:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(139,92,246,0.35);
    color: #ffffff;
}
.qr-btn-violet:active { transform: translateY(0); }
.qr-btn-violet:hover i { transform: translateX(3px); }

/* Divider */
.qr-divider {
    display: flex;
    align-items: center;
    gap: 14px;
    margin: 28px 0;
}
.qr-divider::before,
.qr-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.06);
}
.qr-divider span {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--qr-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

/* Info row */
.qr-info-row {
    display: flex;
    justify-content: center;
    gap: 28px;
}

.qr-info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    color: var(--qr-text-dim);
}

.qr-info-item i {
    font-size: 0.85rem;
    color: var(--qr-violet);
}

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.qr-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.qr-reveal.qr-vis { opacity: 1; transform: translateY(0); }

.qr-d1 { transition-delay: 0.05s; }
.qr-d2 { transition-delay: 0.1s; }
.qr-d3 { transition-delay: 0.2s; }
.qr-d4 { transition-delay: 0.3s; }
.qr-d5 { transition-delay: 0.4s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .qr-main { padding: 32px 36px; }
}
@media (max-width: 991.98px) {
    .qr-layout { flex-direction: column; }
    .qr-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--qr-border);
    }
    .qr-main { padding: 28px 24px; }
    .qr-center-wrap { padding: 10px 0; }
}
@media (max-width: 767.98px) {
    .qr-main { padding: 24px 16px; }
    .qr-feature-card { padding: 32px 24px; }
    .qr-title { font-size: 1.4rem; }
    .qr-info-row { flex-direction: column; align-items: center; gap: 10px; }
}
@media (max-width: 480px) {
    .qr-main { padding: 20px 14px; }
    .qr-feature-card { padding: 28px 18px; border-radius: 16px; }
    .qr-action-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="qr-page">
    <div class="qr-orb o1"></div>
    <div class="qr-orb o2"></div>
    <div class="qr-orb o3"></div>

    <div class="qr-layout">
        <!-- Sidebar -->
       
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
       

        <!-- Main -->
        <div class="qr-main">

            <!-- Header -->
            <div class="qr-head qr-reveal qr-d1">
                <div class="qr-badge">
                    <span class="qr-dot"></span>
                    Verification
                </div>
                <h1 class="qr-title">QR <span>Identification</span></h1>
                <p class="qr-desc">Generate and verify resident QR codes for fast community validation and secure identification.</p>
            </div>

            <?php if ($message): ?>
                <div class="qr-alert qr-alert-<?php echo e($messageType); ?> qr-reveal qr-d2">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'warning' ? 'exclamation-triangle-fill' : 'info-circle-fill'); ?>"></i>
                    <span><?php echo e($message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Centered Feature Card -->
            <div class="qr-center-wrap">
                <div class="qr-feature-card qr-reveal qr-d3">

                    <!-- QR Icon Visual -->
                    <div class="qr-visual">
                        <div class="qr-visual-ring">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                    </div>

                    <h2 class="qr-card-title">Generate or Verify</h2>
                    <p class="qr-card-sub">Select a resident and choose whether to generate a new QR code or verify an existing one.</p>

                    <form method="post">
                        <?php echo csrfField(); ?>
                        <!-- Resident Select -->
                        <div class="qr-form-group">
                            <label class="qr-label"><i class="bi bi-person"></i> Resident</label>
                            <select name="resident_id" class="qr-select" required>
                                <option value="">Select a resident...</option>
                                <?php foreach ($residents as $resident): ?>
                                    <option value="<?php echo (int) $resident['id']; ?>"><?php echo e($resident['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Action Selection -->
                        <div class="qr-form-group">
                            <label class="qr-label" style="margin-bottom: 10px;"><i class="bi bi-lightning-charge"></i> Action</label>
                            <div class="qr-action-grid">
                                <div class="qr-action-card">
                                    <input type="radio" name="action" value="generate" id="actGenerate" checked>
                                    <label for="actGenerate" class="qr-action-label">
                                        <div class="qr-action-ico" style="background:rgba(139,92,246,0.12); color:#8b5cf6;">
                                            <i class="bi bi-qr-code"></i>
                                        </div>
                                        <span class="qr-action-text">Generate QR</span>
                                        <span class="qr-action-desc">Create a new QR code for this resident</span>
                                    </label>
                                </div>
                                <div class="qr-action-card">
                                    <input type="radio" name="action" value="verify" id="actVerify">
                                    <label for="actVerify" class="qr-action-label">
                                        <div class="qr-action-ico" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <span class="qr-action-text">Verify QR</span>
                                        <span class="qr-action-desc">Validate an existing resident QR code</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="qr-btn qr-btn-violet">
                            <i class="bi bi-arrow-right-circle"></i>
                            <span>Submit</span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="qr-divider">
                        <span>Security Info</span>
                    </div>

                    <!-- Info Row -->
                    <div class="qr-info-row">
                        <div class="qr-info-item">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Encrypted payload</span>
                        </div>
                        <div class="qr-info-item">
                            <i class="bi bi-fingerprint"></i>
                            <span>Unique per resident</span>
                        </div>
                        <div class="qr-info-item">
                            <i class="bi bi-clock-history"></i>
                            <span>Timestamped</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.qr-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('qr-vis');
        });
    }, 60);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>