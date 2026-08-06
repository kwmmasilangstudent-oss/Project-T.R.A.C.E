    <?php
    require_once __DIR__ . '/../includes/auth.php';
    requireAuth(['resident']);
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../config/database.php';

    $pdo = getDbConnection();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header("Content-Security-Policy: default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self'; frame-src 'none';");

    if (!defined('BASE_URL')) {
        define('BASE_URL', '/');
    }

    $resident = null;
    try {
        $residentStmt = $pdo->prepare('SELECT id, full_name, address, sex, birth_date, resident_type, photo_url FROM residents WHERE user_id = ? LIMIT 1');
        $residentStmt->execute([$_SESSION['user_id']]);
        $resident = $residentStmt->fetch();
    } catch (Throwable $e) {
        error_log('qr.php: Resident fetch failed - ' . $e->getMessage());
        $resident = null;
    }

    $qrImage = null;
    $qrValue = '';
    $age = '-';
    $typeLabel = 'Regular';
    if ($resident) {
        $qrValue = 'resident:' . $resident['id'] . ':' . rawurlencode($resident['full_name']);
        require_once __DIR__ . '/../includes/qr.php';
        try {
            $qrImage = generateQrImage($qrValue);
        } catch (Throwable $e) {
            error_log('qr.php: QR generation failed - ' . $e->getMessage());
            $qrImage = null;
        }
        $birthDate = $resident['birth_date'] ?? '';
        try {
            $age = getAgeFromDate($birthDate) ?? '-';
        } catch (Throwable $e) {
            $age = '-';
        }
        $typeLabels = ['regular'=>'Regular','senior_citizen'=>'Senior','pwd'=>'PWD','4ps'=>'4Ps','indigent'=>'Indigent'];
        $typeLabel = $typeLabels[$resident['resident_type'] ?? 'regular'] ?? 'Regular';
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
        --qr-accent: #10b981;
        --qr-accent-dark: #059669;
        --qr-accent-glow: rgba(16,185,129,0.15);
        --qr-sky: #0ea5e9;
        --qr-violet: #8b5cf6;
        --qr-amber: #f59e0b;
        --qr-red: #ef4444;
        --qr-bg: #0f172a;
        --qr-surface: rgba(255,255,255,0.04);
        --qr-surface-hover: rgba(255,255,255,0.07);
        --qr-border: rgba(255,255,255,0.08);
        --qr-text: #f1f5f9;
        --qr-text-secondary: #94a3b8;
        --qr-text-muted: #64748b;
        --qr-radius: 12px;
        --qr-radius-lg: 18px;
    }

    /* ═══════════════════════════════
    GLOBAL
    ═══════════════════════════════ */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--qr-bg) !important;
        color: var(--qr-text);
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
    .qr-grid-overlay {
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

    .qr-orb.o1 { width: 500px; height: 500px; background: rgba(16,185,129,0.06); top: -12%; left: -10%; }
    .qr-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -8%; right: -5%; animation-delay: -12s; }
    .qr-orb.o3 { width: 260px; height: 260px; background: rgba(14,165,233,0.05); top: 50%; left: 45%; animation-delay: -6s; animation-duration: 28s; }

    @keyframes qrFloat {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33%      { transform: translate(30px, -20px) scale(1.04); }
        66%      { transform: translate(-20px, 15px) scale(0.96); }
    }

    /* ═══════════════════════════════
    LAYOUT
    ═══════════════════════════════ */
    .qr-page-wrapper {
        position: relative;
        z-index: 1;
        min-height: 100vh;
    }

    .qr-page-wrapper .container-fluid { padding: 0; }
    .qr-page-wrapper .row { margin: 0; min-height: 100vh; }

    /* ═══════════════════════════════
    SIDEBAR
    ═══════════════════════════════ */
    .qr-sidebar-col {
        background: rgba(15,23,42,0.60);
        backdrop-filter: blur(30px);
        border-right: 1px solid var(--qr-border);
        padding: 0 !important;
        min-height: 100vh;
    }

    .qr-sidebar-col .sidebar,
    .qr-sidebar-col .sidebar-menu,
    .qr-sidebar-col .sidebar-header,
    .qr-sidebar-col .sidebar-nav,
    .qr-sidebar-col ul,
    .qr-sidebar-col li,
    .qr-sidebar-col a {
        background: transparent !important;
        color: var(--qr-text-secondary) !important;
    }

    .qr-sidebar-col a:hover,
    .qr-sidebar-col .active a,
    .qr-sidebar-col .active {
        background: var(--qr-surface-hover) !important;
        color: var(--qr-text) !important;
    }

    .qr-sidebar-col .sidebar-header h4,
    .qr-sidebar-col .sidebar-header h5,
    .qr-sidebar-col .sidebar-header h3 {
        color: var(--qr-text) !important;
    }

    /* ═══════════════════════════════
    MAIN CONTENT
    ═══════════════════════════════ */
    .qr-main-col {
        padding: 40px 48px 60px !important;
    }

    /* ═══════════════════════════════
    PAGE HEADER
    ═══════════════════════════════ */
    .qr-page-header { margin-bottom: 32px; }

    .qr-page-badge {
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

    .qr-page-badge i { font-size: 0.8rem; }

    .qr-page-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        font-weight: 800;
        color: #ffffff;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .qr-page-title span {
        background: linear-gradient(135deg, var(--qr-accent), #34d399);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .qr-page-desc {
        font-size: 0.95rem;
        color: var(--qr-text-muted);
        line-height: 1.6;
        max-width: 600px;
    }

    .qr-page-divider {
        width: 48px;
        height: 3px;
        background: linear-gradient(135deg, var(--qr-accent), #34d399);
        border-radius: 2px;
        margin-top: 20px;
    }

    /* ═══════════════════════════════
    GLASS CARDS
    ═══════════════════════════════ */
    .qr-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--qr-border);
        border-radius: var(--qr-radius-lg);
        padding: 32px;
        backdrop-filter: blur(20px);
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .qr-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
    }

    .qr-card:hover {
        border-color: rgba(255,255,255,0.12);
        box-shadow: 0 8px 40px rgba(0,0,0,0.20);
    }

    /* ═══════════════════════════════
    QR CODE FRAME
    ═══════════════════════════════ */
    .qr-display-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .qr-code-frame {
        position: relative;
        display: inline-block;
        padding: 24px;
        background: #ffffff;
        border-radius: var(--qr-radius-lg);
        border: 2px solid rgba(16,185,129,0.25);
        box-shadow: 0 12px 48px rgba(0,0,0,0.30);
        margin-bottom: 28px;
        transition: all 0.4s ease;
    }

    .qr-code-frame:hover {
        transform: scale(1.02);
        box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        border-color: rgba(16,185,129,0.45);
    }

    /* Corner accents */
    .qr-code-frame::before,
    .qr-code-frame::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border: 3px solid var(--qr-accent);
        pointer-events: none;
    }

    .qr-code-frame::before {
        top: 8px; left: 8px;
        border-right: none; border-bottom: none;
        border-radius: 6px 0 0 0;
    }

    .qr-code-frame::after {
        bottom: 8px; right: 8px;
        border-left: none; border-top: none;
        border-radius: 0 0 6px 0;
    }

    .qr-corner-bl,
    .qr-corner-tr {
        position: absolute;
        width: 20px;
        height: 20px;
        border: 3px solid var(--qr-accent);
        pointer-events: none;
    }

    .qr-corner-bl {
        bottom: 8px; left: 8px;
        border-right: none; border-top: none;
        border-radius: 0 0 0 6px;
    }

    .qr-corner-tr {
        top: 8px; right: 8px;
        border-left: none; border-bottom: none;
        border-radius: 0 6px 0 0;
    }

    .qr-code-frame img {
        width: 220px;
        height: 220px;
        image-rendering: pixelated;
        display: block;
    }

    .qr-code-label {
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, var(--qr-accent), var(--qr-accent-dark));
        color: #fff;
        padding: 4px 14px;
        border-radius: 100px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        white-space: nowrap;
        box-shadow: 0 2px 12px rgba(16,185,129,0.30);
    }

    /* Resident info below QR */
    .qr-display-name {
        font-family: 'Playfair Display', serif;
        font-size: 1.4rem;
        font-weight: 800;
        color: #ffffff;
        margin-bottom: 4px;
    }

    .qr-display-id {
        font-size: 0.85rem;
        color: var(--qr-text-muted);
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .qr-display-id i { font-size: 0.82rem; color: var(--qr-text-secondary); }

    /* ═══════════════════════════════
    ACTION BUTTONS
    ═══════════════════════════════ */
    .qr-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-bottom: 24px;
    }

    .qr-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: var(--qr-radius);
        font-size: 0.88rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        cursor: pointer;
        border: none;
    }

    .qr-action-btn:hover { transform: translateY(-2px); }
    .qr-action-btn i { transition: transform 0.2s ease; }
    .qr-action-btn:hover i { transform: translateY(-1px); }

    .qr-btn-download {
        background: linear-gradient(135deg, var(--qr-accent), var(--qr-accent-dark));
        color: #fff;
        box-shadow: 0 4px 16px rgba(16,185,129,0.25);
    }

    .qr-btn-download:hover {
        box-shadow: 0 8px 28px rgba(16,185,129,0.35);
        color: #fff;
    }

    .qr-btn-print {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.12);
        color: var(--qr-text);
    }

    .qr-btn-print:hover {
        border-color: var(--qr-accent);
        background: rgba(16,185,129,0.06);
    }

    /* QR Value code */
    .qr-value-code {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--qr-border);
        border-radius: 10px;
        font-size: 0.78rem;
        color: var(--qr-text-muted);
        font-family: 'Courier New', monospace;
        word-break: break-all;
    }

    .qr-value-code i {
        font-size: 0.82rem;
        color: var(--qr-text-secondary);
        flex-shrink: 0;
    }

    /* ═══════════════════════════════
    INFO CARDS GRID
    ═══════════════════════════════ */
    .qr-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 24px;
    }

    .qr-info-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--qr-border);
        border-radius: var(--qr-radius-lg);
        padding: 22px 20px;
        text-align: center;
        transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
    }

    .qr-info-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
    }

    .qr-info-card:hover {
        transform: translateY(-4px);
        border-color: rgba(255,255,255,0.14);
        box-shadow: 0 8px 40px rgba(0,0,0,0.20);
    }

    .qr-info-accent {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
    }

    .qr-info-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin: 0 auto 12px;
    }

    .qr-info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--qr-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 4px;
    }

    .qr-info-value {
        font-weight: 700;
        font-size: 1rem;
        color: #e2e8f0;
    }

    /* ═══════════════════════════════
    HOW IT WORKS CARD
    ═══════════════════════════════ */
    .qr-steps-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--qr-border);
        border-radius: var(--qr-radius-lg);
        padding: 28px;
        margin-top: 24px;
        backdrop-filter: blur(20px);
        position: relative;
        overflow: hidden;
    }

    .qr-steps-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
    }

    .qr-steps-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .qr-steps-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(16,185,129,0.10);
        border: 1px solid rgba(16,185,129,0.20);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        color: var(--qr-accent);
        flex-shrink: 0;
    }

    .qr-steps-title {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.1rem;
        color: #ffffff;
        margin-bottom: 2px;
    }

    .qr-steps-sub {
        font-size: 0.82rem;
        color: var(--qr-text-muted);
        margin: 0;
    }

    .qr-steps-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .qr-step {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 16px;
        border-radius: var(--qr-radius);
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        transition: all 0.25s ease;
    }

    .qr-step:hover {
        background: rgba(16,185,129,0.04);
        border-color: rgba(16,185,129,0.15);
    }

    .qr-step-num {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--qr-accent), #34d399);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        font-weight: 800;
        flex-shrink: 0;
    }

    .qr-step-text h6 {
        font-weight: 700;
        font-size: 0.85rem;
        color: #e2e8f0;
        margin-bottom: 3px;
    }

    .qr-step-text p {
        font-size: 0.78rem;
        color: var(--qr-text-muted);
        line-height: 1.5;
        margin: 0;
    }

    /* ═══════════════════════════════
    NOT LINKED STATE
    ═══════════════════════════════ */
    .qr-not-linked {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--qr-border);
        border-radius: var(--qr-radius-lg);
        padding: 60px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(20px);
    }

    .qr-not-linked::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 30% 30%, rgba(16,185,129,0.04) 0%, transparent 50%),
            radial-gradient(ellipse at 70% 70%, rgba(139,92,246,0.03) 0%, transparent 40%);
        pointer-events: none;
    }

    .qr-not-linked-icon {
        width: 80px;
        height: 80px;
        border-radius: 22px;
        background: rgba(16,185,129,0.08);
        border: 1px solid rgba(16,185,129,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--qr-accent);
        margin: 0 auto 20px;
        position: relative;
    }

    .qr-not-linked-icon::after {
        content: '';
        position: absolute;
        inset: -8px;
        border-radius: 28px;
        border: 2px dashed rgba(16,185,129,0.12);
    }

    .qr-not-linked h4 {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.3rem;
        color: #ffffff;
        margin-bottom: 8px;
        position: relative;
    }

    .qr-not-linked p {
        font-size: 0.92rem;
        color: var(--qr-text-muted);
        max-width: 420px;
        margin: 0 auto 24px;
        line-height: 1.6;
        position: relative;
    }

    .qr-not-linked-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: linear-gradient(135deg, var(--qr-accent), var(--qr-accent-dark));
        border: none;
        border-radius: var(--qr-radius);
        color: #fff;
        font-size: 0.88rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(16,185,129,0.25);
        position: relative;
    }

    .qr-not-linked-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 28px rgba(16,185,129,0.35);
        color: #fff;
    }

    /* ═══════════════════════════════
    PRINT STYLES
    ═══════════════════════════════ */
    @media print {
        .qr-grid-overlay, .qr-orb,
        .qr-sidebar-col, .qr-page-header, .qr-steps-card,
        .qr-info-grid, .qr-actions, .qr-value-code, .qr-display-id,
        .navbar, footer, .main-navbar { display: none !important; }

        body { background: #fff !important; }
        body::after { display: none !important; }
        .qr-page-wrapper .row { display: block !important; min-height: auto !important; }
        .qr-main-col { padding: 20px !important; }
        .qr-card { box-shadow: none !important; border: 2px solid #000 !important; background: #fff !important; backdrop-filter: none !important; }
        .qr-card::before { display: none !important; }
        .qr-display-name { color: #000 !important; }
        .qr-code-frame { border-color: #000 !important; box-shadow: none !important; }
        .qr-code-frame::before, .qr-code-frame::after,
        .qr-corner-bl, .qr-corner-tr { border-color: #000 !important; }
    }

    /* ═══════════════════════════════
    REVEAL ANIMATIONS
    ═══════════════════════════════ */
    .qr-reveal {
        opacity: 0;
        transform: translateY(24px);
        transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .qr-reveal.qr-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .qr-d1 { transition-delay: 0.05s; }
    .qr-d2 { transition-delay: 0.10s; }
    .qr-d3 { transition-delay: 0.15s; }
    .qr-d4 { transition-delay: 0.20s; }
    .qr-d5 { transition-delay: 0.25s; }
    .qr-d6 { transition-delay: 0.30s; }

    /* ═══════════════════════════════
    RESPONSIVE
    ═══════════════════════════════ */
    @media (max-width: 991.98px) {
        .qr-sidebar-col {
            min-height: auto !important;
            border-right: none !important;
            border-bottom: 1px solid var(--qr-border) !important;
        }
        .qr-main-col { padding: 32px 24px 50px !important; }
        .qr-steps-grid { grid-template-columns: 1fr; }
        .qr-info-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 767.98px) {
        .qr-main-col { padding: 24px 18px 40px !important; }
        .qr-card { padding: 24px 20px; }
        .qr-page-title { font-size: 1.4rem; }
        .qr-code-frame img { width: 180px; height: 180px; }
        .qr-info-grid { grid-template-columns: 1fr; }
        .qr-actions { flex-direction: column; align-items: stretch; }
        .qr-action-btn { justify-content: center; }
        .qr-not-linked { padding: 40px 20px; }
        .qr-steps-card { padding: 20px; }
        .qr-step { flex-direction: column; gap: 10px; }
    }

    @media (max-width: 480px) {
        .qr-main-col { padding: 20px 14px 36px !important; }
        .qr-card { padding: 20px 16px; border-radius: var(--qr-radius); }
        .qr-page-title { font-size: 1.25rem; }
        .qr-display-name { font-size: 1.1rem; }
        .qr-code-frame img { width: 160px; height: 160px; }
    }
    </style>

    <!-- ═══════════════════════════════════════
        ATMOSPHERIC ELEMENTS
        ═══════════════════════════════════════ -->
    <div class="qr-grid-overlay"></div>
    <div class="qr-orb o1"></div>
    <div class="qr-orb o2"></div>
    <div class="qr-orb o3"></div>

    <!-- ═══════════════════════════════════════
        PAGE LAYOUT
        ═══════════════════════════════════════ -->
    <div class="qr-page-wrapper qr-page">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
        
                    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
              

                <!-- Main Content -->
                <div class="col-md-9 qr-main-col">

                    <!-- ── Page Header ── -->
                    <div class="qr-page-header qr-reveal qr-d1">
                        <div class="qr-page-badge">
                            <i class="bi bi-qr-code-scan"></i>
                            Verification
                        </div>
                        <h1 class="qr-page-title">
                            My <span>QR Code</span>
                        </h1>
                        <p class="qr-page-desc">
                            Your personal verification QR code. Present this at the barangay hall for quick identity verification and document pickup.
                        </p>
                        <div class="qr-page-divider"></div>
                    </div>

                    <?php if ($resident): ?>

                        <!-- ── QR Display Card ── -->
                        <div class="qr-card qr-reveal qr-d2">
                            <div class="qr-display-wrap">

                                <div class="qr-code-frame">
                                    <span class="qr-corner-bl"></span>
                                    <span class="qr-corner-tr"></span>
                                    <img src="<?php echo $qrImage ? 'data:image/png;base64,' . base64_encode($qrImage) : ''; ?>" alt="QR Code for <?php echo e($resident['full_name']); ?>"<?php echo $qrImage ? '' : ' style="display:none;"'; ?>>
                                    <div class="qr-code-label">
                                        <i class="bi bi-shield-check" style="margin-right:4px;font-size:0.6rem;"></i> Verified Resident
                                    </div>
                                </div>

                                <div class="qr-display-name"><?php echo e($resident['full_name']); ?></div>
                                <div class="qr-display-id">
                                    <i class="bi bi-person-badge"></i>
                                    Resident ID: #<?php echo (int) $resident['id']; ?>
                                </div>

                                <div class="qr-actions">
                                    <button onclick="printQR()" class="qr-action-btn qr-btn-print">
                                        <i class="bi bi-printer"></i> Print QR
                                    </button>
                                </div>

                                <div class="qr-value-code">
                                    <i class="bi bi-upc-scan"></i>
                                    <?php echo e($qrValue); ?>
                                </div>

                            </div>
                        </div>

                        <!-- ── Info Cards ── -->
                        <div class="qr-info-grid">
                            <div class="qr-info-card qr-reveal qr-d3">
                                <div class="qr-info-accent" style="background:linear-gradient(135deg,var(--qr-accent),#34d399);"></div>
                                <div class="qr-info-icon" style="background:rgba(16,185,129,0.10); color:#6ee7b7;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="qr-info-label">Resident</div>
                                <div class="qr-info-value"><?php echo e($resident['full_name']); ?></div>
                            </div>
                            <div class="qr-info-card qr-reveal qr-d4">
                                <div class="qr-info-accent" style="background:linear-gradient(135deg,var(--qr-sky),#38bdf8);"></div>
                                <div class="qr-info-icon" style="background:rgba(14,165,233,0.10); color:#7dd3fc;">
                                    <i class="bi bi-hash"></i>
                                </div>
                                <div class="qr-info-label">ID Number</div>
                                <div class="qr-info-value">#<?php echo (int) $resident['id']; ?></div>
                            </div>
                            <div class="qr-info-card qr-reveal qr-d5">
                                <div class="qr-info-accent" style="background:linear-gradient(135deg,var(--qr-violet),#a78bfa);"></div>
                                <div class="qr-info-icon" style="background:rgba(139,92,246,0.10); color:#a78bfa;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="qr-info-label">Status</div>
                                <div class="qr-info-value" style="color:#6ee7b7;">Verified</div>
                            </div>
                        </div>

                        <!-- ── How It Works ── -->
                        <div class="qr-steps-card qr-reveal qr-d6">
                            <div class="qr-steps-header">
                                <div class="qr-steps-icon"><i class="bi bi-info-circle"></i></div>
                                <div>
                                    <h5 class="qr-steps-title">How It Works</h5>
                                    <p class="qr-steps-sub">Using your QR code for verification is simple and secure.</p>
                                </div>
                            </div>
                            <div class="qr-steps-grid">
                                <div class="qr-step">
                                    <div class="qr-step-num">1</div>
                                    <div class="qr-step-text">
                                        <h6>Save or Print</h6>
                                        <p>Download the QR code to your phone or print a physical copy to keep in your wallet.</p>
                                    </div>
                                </div>
                                <div class="qr-step">
                                    <div class="qr-step-num">2</div>
                                    <div class="qr-step-text">
                                        <h6>Present at Hall</h6>
                                        <p>Show your QR code to the barangay staff when visiting for document pickup or verification.</p>
                                    </div>
                                </div>
                                <div class="qr-step">
                                    <div class="qr-step-num">3</div>
                                    <div class="qr-step-text">
                                        <h6>Instant Verify</h6>
                                        <p>Staff will scan the code to instantly pull up your resident record — no manual lookup needed.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>

                        <!-- ── Not Linked State ── -->
                        <div class="qr-not-linked qr-reveal qr-d2">
                            <div class="qr-not-linked-icon">
                                <i class="bi bi-qr-code-scan"></i>
                            </div>
                            <h4>QR Code Not Available</h4>
                            <p>Your account is not yet linked to the resident database. Please visit the barangay hall or contact the secretary to complete your registration and generate your personal QR code.</p>
                            <a href="<?php echo BASE_URL; ?>/landing/contact.php" class="qr-not-linked-btn">
                                <i class="bi bi-envelope"></i> Contact Barangay
                            </a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
        SCRIPTS
        ═══════════════════════════════════════ -->
    <?php if ($resident): ?>
    <div id="printQRCard" style="display:none;">
        <div class="id-card" style="width:3.375in;height:2.125in;background:linear-gradient(135deg,#fff 0%,#f8fafc 100%);border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.12);overflow:hidden;display:flex;flex-direction:column;font-family:Arial,sans-serif;border:1px solid #e2e8f0;flex-shrink:0;">
            <div style="display:flex;align-items:center;padding:0.12in 0.18in 0.08in;background:linear-gradient(135deg,#1e3a5f 0%,#2d5a8e 100%);color:#fff;gap:0.12in;">
                <div style="width:0.38in;height:0.38in;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.16in;font-weight:800;flex-shrink:0;border:2px solid rgba(255,255,255,0.3);">BMS</div>
                <div style="line-height:1.2;">
                    <div style="font-size:0.08in;font-weight:400;opacity:0.85;letter-spacing:0.3px;text-transform:uppercase;">Municipality of</div>
                    <div style="font-size:0.16in;font-weight:700;letter-spacing:0.5px;"><?php echo e(getSetting('barangay_name', 'Barangay')); ?></div>
                    <div style="font-size:0.07in;font-weight:500;opacity:0.75;letter-spacing:1px;text-transform:uppercase;">Resident ID</div>
                </div>
            </div>
            <div style="flex:1;display:flex;padding:0.12in 0.18in;gap:0.14in;align-items:center;">
                <?php
                    $photoUrl = '';
                    if (!empty($resident['photo_url'])) {
                        $photoUrl = $resident['photo_url'];
                        if (strpos($photoUrl, '://') !== false || strpos($photoUrl, '..') !== false) {
                            $photoUrl = '';
                        }
                    }
                    ?>
                    <div style="width:0.75in;height:0.9in;border-radius:6px;border:2px solid #e2e8f0;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:0.45in;flex-shrink:0;overflow:hidden;">
                        <?php if ($photoUrl): ?>
                        <img src="<?php echo e($photoUrl); ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                        <i class="bi bi-person-fill"></i>
                        <?php endif; ?>
                    </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.14in;font-weight:700;color:#0f172a;line-height:1.15;margin-bottom:0.04in;text-transform:uppercase;letter-spacing:0.3px;"><?php echo e($resident['full_name']); ?></div>
                    <div style="font-size:0.085in;color:#475569;line-height:1.4;display:flex;gap:0.04in;"><span style="color:#94a3b8;min-width:0.4in;flex-shrink:0;">Address</span><span style="color:#1e293b;font-weight:500;"><?php echo e($resident['address'] ?? '-'); ?></span></div>
                    <div style="font-size:0.085in;color:#475569;line-height:1.4;display:flex;gap:0.04in;"><span style="color:#94a3b8;min-width:0.4in;flex-shrink:0;">Age/Sex</span><span style="color:#1e293b;font-weight:500;"><?php echo $age; ?> / <?php echo e($resident['sex'] ?? '-'); ?></span></div>
                    <div style="margin-top:0.03in;"><span style="display:inline-block;padding:0.01in 0.06in;border-radius:3px;font-size:0.075in;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;background:#dbeafe;color:#1d4ed8;"><?php echo $typeLabel; ?></span></div>
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.06in 0.18in;border-top:1px solid #e2e8f0;background:#f8fafc;">
                <div>
                    <div style="font-size:0.07in;color:#64748b;font-weight:500;letter-spacing:0.5px;">ID <?php echo str_pad($resident['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    <div style="font-size:0.07in;color:#94a3b8;">Issued <?php echo date('M d, Y'); ?></div>
                </div>
                <div><img src="<?php echo BASE_URL; ?>/includes/qr.php?type=resident&id=<?php echo (int) $resident['id']; ?>" alt="QR" style="width:0.6in;height:0.6in;display:block;"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var reveals = document.querySelectorAll('.qr-reveal');
        setTimeout(function() {
            reveals.forEach(function(el) {
                el.classList.add('qr-visible');
            });
        }, 80);
    });

    function printQR() {
        var card = document.getElementById('printQRCard');
        if (!card) return;
        var html = card.innerHTML;
        var win = window.open('', '_blank');
        win.document.write('<!DOCTYPE html><html><head><style>');
        win.document.write('@page{size:3.375in 2.125in;margin:0;}');
        win.document.write('*{margin:0;padding:0;box-sizing:border-box;}');
        win.document.write('body{display:flex;align-items:center;justify-content:center;width:3.375in;height:2.125in;background:#fff;}');
        win.document.write('.id-card{width:3.375in;height:2.125in;background:linear-gradient(135deg,#fff 0%,#f8fafc 100%);border-radius:10px;overflow:hidden;display:flex;flex-direction:column;font-family:Arial,sans-serif;border:1px solid #e2e8f0;}');
        win.document.write('</style></head><body>');
        win.document.write(html);
        win.document.write('</body></html>');
        win.document.close();
        win.focus();
        setTimeout(function() { win.print(); win.close(); }, 300);
    }
    </script>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>