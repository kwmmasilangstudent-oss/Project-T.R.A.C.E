<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'About Us - ' . APP_NAME;
$pageDescription = 'Learn about the mission, vision, and history of Barangay Tumalaytay.';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = getDbConnection();

/* ── Content ── */
$mission    = getLandingContent('mission', 'To serve the community with integrity, transparency, and excellence — ensuring every resident has access to responsive governance and quality public services.');
$vision     = getLandingContent('vision', 'A progressive, self-reliant barangay built on transparency, accountability, and active public participation — where every family thrives in a safe and empowered community.');
$objectives = getLandingContent('objectives', "• Improve public service delivery and digital access for all residents\n• Strengthen peace and order through community-driven programs\n• Promote health, education, and livelihood opportunities\n• Ensure transparent management of barangay funds and resources\n• Foster inclusive governance with active youth and resident participation");
$history    = getLandingContent('history', 'Founded as one of the foundational communities in the municipality, our barangay has grown from a small rural settlement into a thriving, resilient community. Through decades of collective effort, inclusive leadership, and the unwavering spirit of its residents, the barangay continues to evolve — embracing modern governance while preserving its deep-rooted values of bayanihan and service.');

$barangayName = getSetting('barangay_name', 'Barangay Tumalaytay');

/* ── Officials ── */
$tierConfig = [
    'captain'   => ['label' => 'Barangay Captain', 'color' => '#f2b544'],
    'executive' => ['label' => 'Executive Officers', 'color' => '#2f7bff'],
    'kagawad'   => ['label' => 'Sangguniang Barangay', 'color' => '#8b5cf6'],
    'sk'        => ['label' => 'Appointed Officials', 'color' => '#35d18f'],
];

$officialsByTier = [];
foreach (['captain', 'executive', 'kagawad', 'sk'] as $tier) {
    $stmt = $pdo->prepare("SELECT * FROM landing_officials WHERE tier = ? AND is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute([$tier]);
    $officialsByTier[$tier] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$heroBg = getSetting('hero_background', '');
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --ab-primary: #1a56db;
        --ab-primary-dark: #1042a3;
        --ab-primary-light: #e8effc;
        --ab-accent: #f59e0b;
        --ab-accent-dark: #d97706;
        --ab-green: #10b981;
        --ab-violet: #8b5cf6;
        --ab-rose: #f43f5e;
        --ab-bg: #f0f4f8;
        --ab-card: #ffffff;
        --ab-hero: #0f172a;
        --ab-text: #0f172a;
        --ab-muted: #64748b;
        --ab-light: #94a3b8;
        --ab-border: #e2e8f0;
        --ab-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --ab-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        --ab-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
        --ab-radius: 12px;
        --ab-radius-lg: 20px;
        --ab-radius-xl: 28px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--ab-bg);
        color: var(--ab-text);
        overflow-x: hidden;
    }

    /* ═══════════════════════════════════
       PAGE HERO
       ═══════════════════════════════════ */
    .ab-hero {
        position: relative;
        padding: 100px 0 80px;
        background: var(--ab-hero);
        overflow: hidden;
    }

    <?php if (!empty($heroBg)): ?>
    .ab-hero {
        background: linear-gradient(160deg, rgba(15,23,42,0.93), rgba(15,23,42,0.87)),
                    url('<?php echo asset($heroBg); ?>') center/cover no-repeat;
    }
    <?php endif; ?>

    .ab-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 15% 50%, rgba(26,86,219,0.14) 0%, transparent 50%),
            radial-gradient(ellipse at 85% 20%, rgba(245,158,11,0.09) 0%, transparent 40%);
        pointer-events: none;
    }

    .ab-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        pointer-events: none;
    }

    .ab-hero-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
    }

    .ab-hero-content { position: relative; z-index: 2; }

    .ab-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        background: rgba(26, 86, 219, 0.15);
        border: 1px solid rgba(26, 86, 219, 0.25);
        border-radius: 100px;
        color: #93bbfc;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .ab-hero-badge .ab-dot {
        width: 8px; height: 8px;
        background: var(--ab-accent);
        border-radius: 50%;
        animation: abPulse 2s ease-in-out infinite;
    }

    @keyframes abPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.3); }
    }

    .ab-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.2rem, 5vw, 3.8rem);
        font-weight: 900;
        color: #ffffff;
        line-height: 1.1;
        margin-bottom: 16px;
    }

    .ab-hero-title span {
        background: linear-gradient(135deg, var(--ab-accent), #fbbf24);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .ab-hero-subtitle {
        font-size: 1.1rem;
        color: #94a3b8;
        max-width: 640px;
        line-height: 1.7;
    }

    .ab-floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
        animation: abFloat 20s ease-in-out infinite;
    }

    .ab-floating-orb.o1 { width: 350px; height: 350px; background: rgba(26,86,219,0.1); top: -15%; left: -5%; }
    .ab-floating-orb.o2 { width: 250px; height: 250px; background: rgba(245,158,11,0.07); bottom: -15%; right: -5%; animation-delay: -10s; }

    @keyframes abFloat {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33%      { transform: translate(25px, -15px) scale(1.05); }
        66%      { transform: translate(-15px, 10px) scale(0.95); }
    }

    /* Breadcrumb */
    .ab-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 28px;
        font-size: 0.82rem;
    }

    .ab-breadcrumb a {
        color: var(--ab-light);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .ab-breadcrumb a:hover { color: #fff; }
    .ab-breadcrumb .ab-sep { color: rgba(255,255,255,0.2); }
    .ab-breadcrumb .ab-current { color: #7db0ff; font-weight: 600; }

    /* ═══════════════════════════════════
       PILLARS (Mission & Vision Cards)
       ═══════════════════════════════════ */
    .ab-pillars {
        margin-top: -60px;
        position: relative;
        z-index: 10;
        padding-bottom: 40px;
    }

    .ab-pillar-card {
        background: var(--ab-card);
        border: 1px solid var(--ab-border);
        border-radius: var(--ab-radius-lg);
        box-shadow: var(--ab-shadow-lg);
        padding: 40px 32px;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .ab-pillar-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
    }

    .ab-pillar-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 48px rgba(0,0,0,0.12);
    }

    .ab-pillar-card.ab-mission::before {
        background: linear-gradient(90deg, var(--ab-primary), #60a5fa);
    }

    .ab-pillar-card.ab-vision::before {
        background: linear-gradient(90deg, var(--ab-accent), #fbbf24);
    }

    .ab-pillar-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--ab-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .ab-pillar-card:hover .ab-pillar-icon { transform: scale(1.1) rotate(-5deg); }

    .ab-pillar-icon.ic-mission {
        background: rgba(26, 86, 219, 0.1);
        color: var(--ab-primary);
    }

    .ab-pillar-icon.ic-vision {
        background: rgba(245, 158, 11, 0.1);
        color: var(--ab-accent-dark);
    }

    .ab-pillar-card h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--ab-text);
        margin-bottom: 14px;
    }

    .ab-pillar-card .ab-pillar-text {
        font-size: 1rem;
        color: var(--ab-muted);
        line-height: 1.75;
    }

    /* ═══════════════════════════════════
       OBJECTIVES & HISTORY SECTION
       ═══════════════════════════════════ */
    .ab-details {
        padding: 40px 0 80px;
    }

    .ab-detail-card {
        background: var(--ab-card);
        border: 1px solid var(--ab-border);
        border-radius: var(--ab-radius-lg);
        box-shadow: var(--ab-shadow-sm);
        padding: 40px 36px;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
    }

    .ab-detail-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
    }

    .ab-detail-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--ab-shadow-md);
    }

    .ab-detail-card.ab-objectives-card::before {
        background: linear-gradient(90deg, var(--ab-green), #34d399);
    }

    .ab-detail-card.ab-history-card::before {
        background: linear-gradient(90deg, var(--ab-violet), #a78bfa);
    }

    .ab-detail-icon {
        width: 52px;
        height: 52px;
        border-radius: var(--ab-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 18px;
        transition: transform 0.3s ease;
    }

    .ab-detail-card:hover .ab-detail-icon { transform: scale(1.1); }

    .ab-detail-icon.ic-green {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .ab-detail-icon.ic-violet {
        background: rgba(139, 92, 246, 0.1);
        color: #7c3aed;
    }

    .ab-detail-card h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--ab-text);
        margin-bottom: 14px;
    }

    .ab-detail-card .ab-detail-text {
        font-size: 0.95rem;
        color: var(--ab-muted);
        line-height: 1.75;
    }

    /* Objectives list styling */
    .ab-objectives-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .ab-objectives-list li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--ab-border);
        font-size: 0.95rem;
        color: var(--ab-muted);
        line-height: 1.6;
        transition: all 0.2s ease;
    }

    .ab-objectives-list li:last-child { border-bottom: none; }
    .ab-objectives-list li:hover { color: var(--ab-text); transform: translateX(4px); }

    .ab-objectives-list .ab-check {
        width: 26px;
        height: 26px;
        min-width: 26px;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #059669;
        font-size: 0.7rem;
        margin-top: 2px;
    }

    /* ═══════════════════════════════════
       OFFICIALS SECTION
       ═══════════════════════════════════ */
    .ab-officials-section {
        padding: 80px 0 100px;
        background: linear-gradient(180deg, var(--ab-bg) 0%, #e8edf4 100%);
    }

    .ab-section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .ab-section-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: var(--ab-primary-light);
        color: var(--ab-primary);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        border-radius: 100px;
        margin-bottom: 16px;
    }

    .ab-section-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 800;
        color: var(--ab-text);
        margin-bottom: 12px;
        line-height: 1.15;
    }

    .ab-section-subtitle {
        font-size: 1rem;
        color: var(--ab-muted);
        max-width: 560px;
        margin: 0 auto;
        line-height: 1.7;
    }

    /* Officials Pyramid */
    .ab-pyramid {
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
    }

    .ab-pyramid-tier {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 18px;
        width: 100%;
    }

    .ab-connector {
        display: flex;
        justify-content: center;
        width: 100%;
        height: 28px;
        position: relative;
    }

    .ab-connector::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        width: 2px;
        height: 100%;
        background: var(--ab-border);
    }

    .ab-connector::after {
        content: '';
        position: absolute;
        bottom: 0;
        height: 2px;
        background: var(--ab-border);
    }

    .ab-connector.cn-1::after { left: 30%; right: 30%; }
    .ab-connector.cn-2::after { left: 15%; right: 15%; }
    .ab-connector.cn-3::after { left: 30%; right: 30%; }

    /* Official Card */
    .ab-official-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 26px 18px 22px;
        background: var(--ab-card);
        border: 1px solid var(--ab-border);
        border-radius: var(--ab-radius-lg);
        box-shadow: var(--ab-shadow-sm);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        overflow: hidden;
    }

    .ab-official-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(135deg, var(--ab-primary), var(--ab-accent));
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .ab-official-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--ab-shadow-lg);
        border-color: rgba(26,86,219,0.15);
    }

    .ab-official-card:hover::before { opacity: 1; }

    .ab-official-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        margin-bottom: 12px;
        border: 3px solid var(--ab-border);
        background: var(--ab-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .ab-official-card:hover .ab-official-photo {
        border-color: var(--ab-primary);
        box-shadow: 0 0 0 3px rgba(26,86,219,0.1);
    }

    .ab-official-photo img { width: 100%; height: 100%; object-fit: cover; }

    .ab-official-photo .ab-fallback {
        font-size: 1.8rem;
        color: var(--ab-light);
    }

    .ab-official-name {
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--ab-text);
        margin-bottom: 3px;
        line-height: 1.3;
    }

    .ab-official-position {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1px;
    }

    .ab-official-sublabel {
        font-size: 0.68rem;
        color: var(--ab-muted);
    }

    /* Captain Override */
    .ab-tier-captain .ab-official-card {
        padding: 34px 38px 28px;
        border-color: rgba(242,181,68,0.3);
        background: linear-gradient(135deg, #fffbeb, #ffffff);
    }

    .ab-tier-captain .ab-official-photo {
        width: 100px;
        height: 100px;
        border-color: var(--ab-accent);
    }

    .ab-tier-captain .ab-official-card:hover {
        border-color: var(--ab-accent);
        box-shadow: 0 12px 40px rgba(245,158,11,0.12);
    }

    .ab-tier-captain .ab-official-card::before {
        background: linear-gradient(135deg, var(--ab-accent), #fbbf24);
        opacity: 1;
    }

    .ab-tier-captain .ab-official-name { font-size: 1.05rem; }
    .ab-tier-captain .ab-official-position { font-size: 0.8rem; color: var(--ab-accent-dark); }

    .ab-crown {
        position: absolute;
        top: -2px; right: -2px;
        width: 30px; height: 30px;
        background: var(--ab-accent);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.8rem;
        box-shadow: 0 2px 10px rgba(245,158,11,0.4);
    }

    /* Kagawad Override */
    .ab-tier-kagawad .ab-official-card {
        padding: 18px 12px;
        min-width: 0;
        flex: 1;
        max-width: 135px;
    }

    .ab-tier-kagawad .ab-official-photo { width: 65px; height: 65px; margin-bottom: 9px; }
    .ab-tier-kagawad .ab-official-name { font-size: 0.78rem; }
    .ab-tier-kagawad .ab-official-position { font-size: 0.62rem; }

    /* Executive Override */
    .ab-tier-executive .ab-official-card { min-width: 190px; }
    .ab-tier-executive .ab-official-photo { width: 80px; height: 80px; }

    /* SK Override */
    .ab-tier-sk .ab-official-card { min-width: 170px; }
    .ab-tier-sk .ab-official-photo { width: 72px; height: 72px; }

    /* ═══════════════════════════════════
       VALUES SECTION
       ═══════════════════════════════════ */
    .ab-values {
        padding: 80px 0;
    }

    .ab-value-card {
        background: var(--ab-card);
        border: 1px solid var(--ab-border);
        border-radius: var(--ab-radius-lg);
        padding: 32px 24px;
        text-align: center;
        height: 100%;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }

    .ab-value-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        transform: scaleX(0);
        transition: transform 0.4s ease;
    }

    .ab-value-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--ab-shadow-md);
    }

    .ab-value-card:hover::after { transform: scaleX(1); }

    .ab-value-card:nth-child(1)::after { background: var(--ab-primary); }
    .ab-value-card:nth-child(2)::after { background: var(--ab-accent); }
    .ab-value-card:nth-child(3)::after { background: var(--ab-green); }
    .ab-value-card:nth-child(4)::after { background: var(--ab-violet); }

    .ab-value-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin: 0 auto 16px;
        transition: transform 0.3s ease;
    }

    .ab-value-card:hover .ab-value-icon { transform: scale(1.15); }

    .ab-value-card h5 {
        font-weight: 700;
        font-size: 1rem;
        color: var(--ab-text);
        margin-bottom: 8px;
    }

    .ab-value-card p {
        font-size: 0.85rem;
        color: var(--ab-muted);
        line-height: 1.6;
        margin: 0;
    }

    /* ═══════════════════════════════════
       SCROLL REVEAL
       ═══════════════════════════════════ */
    .ab-reveal {
        opacity: 0;
        transform: translateY(35px);
        transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .ab-reveal.ab-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .ab-d1 { transition-delay: 0.1s; }
    .ab-d2 { transition-delay: 0.2s; }
    .ab-d3 { transition-delay: 0.3s; }
    .ab-d4 { transition-delay: 0.4s; }
    .ab-d5 { transition-delay: 0.5s; }
    .ab-d6 { transition-delay: 0.6s; }
    .ab-d7 { transition-delay: 0.7s; }

    /* ═══════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════ */
    @media (max-width: 1199.98px) {
        .ab-tier-kagawad .ab-official-card { max-width: 120px; padding: 14px 8px; }
        .ab-tier-kagawad .ab-official-photo { width: 55px; height: 55px; }
    }

    @media (max-width: 991.98px) {
        .ab-hero { padding: 80px 0 60px; }
        .ab-pillars { margin-top: -40px; }
        .ab-pillar-card { padding: 32px 24px; }

        .ab-pyramid-tier { flex-wrap: wrap; gap: 12px; }
        .ab-tier-kagawad { gap: 8px !important; }
        .ab-tier-kagawad .ab-official-card { max-width: calc(25% - 10px); flex: 1 1 calc(25% - 10px); min-width: 90px; }
        .ab-officials-section { padding: 50px 0 60px; }
    }

    @media (max-width: 767.98px) {
        .ab-hero { padding: 70px 0 50px; }
        .ab-pillars { margin-top: -30px; }
        .ab-pillar-card { padding: 28px 20px; }
        .ab-pillar-card h2 { font-size: 1.3rem; }

        .ab-detail-card { padding: 28px 20px; }
        .ab-detail-card h3 { font-size: 1.2rem; }

        /* Stack pyramid */
        .ab-pyramid-tier {
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .ab-pyramid-tier.ab-tier-executive {
            flex-direction: row;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ab-pyramid-tier.ab-tier-kagawad {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            max-width: 300px;
            width: 100%;
        }

        .ab-tier-kagawad .ab-official-card { max-width: 100%; min-width: 0; width: 100%; }
        .ab-tier-kagawad .ab-official-photo { width: 55px; height: 55px; }
        .ab-tier-captain .ab-official-photo { width: 85px; height: 85px; }
        .ab-tier-captain .ab-official-card { padding: 26px 22px 22px; }
        .ab-official-card { padding: 18px 14px; }

        .ab-connector { display: none; }
        .ab-section-header { margin-bottom: 35px; }
        .ab-officials-section { padding: 40px 0 50px; }
    }

    @media (max-width: 480px) {
        .ab-tier-executive { flex-direction: column !important; align-items: center; }
        .ab-tier-executive .ab-official-card { min-width: 170px; }
        .ab-tier-sk { flex-direction: column !important; align-items: center; }
        .ab-tier-sk .ab-official-card { min-width: 160px; }
    }
</style>

<!-- ═══════════════════════════════════
     PAGE HERO
     ═══════════════════════════════════ -->
<section class="ab-hero">
    <div class="ab-hero-grid"></div>
    <div class="ab-floating-orb o1"></div>
    <div class="ab-floating-orb o2"></div>

    <div class="container ab-hero-content">
        <div class="ab-breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house"></i> Home</a>
            <span class="ab-sep">/</span>
            <span class="ab-current">About</span>
        </div>
        <h1 class="ab-hero-title">About <span><?php echo e($barangayName); ?></span></h1>
        <p class="ab-hero-subtitle">
            Discover our mission, vision, dedicated leadership, and the values that drive our commitment to transparent and responsive community governance.
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════
     MISSION & VISION PILLARS
     ═══════════════════════════════════ -->
<section class="ab-pillars">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6 ab-reveal">
                <div class="ab-pillar-card ab-mission">
                    <div class="ab-pillar-icon ic-mission"><i class="bi bi-bullseye"></i></div>
                    <h2>Our Mission</h2>
                    <p class="ab-pillar-text"><?php echo nl2br(e($mission)); ?></p>
                </div>
            </div>
            <div class="col-md-6 ab-reveal ab-d1">
                <div class="ab-pillar-card ab-vision">
                    <div class="ab-pillar-icon ic-vision"><i class="bi bi-eye"></i></div>
                    <h2>Our Vision</h2>
                    <p class="ab-pillar-text"><?php echo nl2br(e($vision)); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     OBJECTIVES & HISTORY
     ═══════════════════════════════════ -->
<section class="ab-details">
    <div class="container">
        <div class="row g-4">
            <!-- Objectives -->
            <div class="col-lg-7 ab-reveal ab-d1">
                <div class="ab-detail-card ab-objectives-card">
                    <div class="ab-detail-icon ic-green"><i class="bi bi-list-check"></i></div>
                    <h3>Our Objectives</h3>
                    <?php
                        $objLines = array_filter(array_map('trim', explode("\n", $objectives)));
                        $objLines = array_map(function($l) {
                            return ltrim($l, '•-–* ');
                        }, $objLines);
                    ?>
                    <?php if (!empty($objLines)): ?>
                        <ul class="ab-objectives-list">
                            <?php foreach ($objLines as $line): ?>
                                <?php if (!empty($line)): ?>
                                    <li>
                                        <span class="ab-check"><i class="bi bi-check2"></i></span>
                                        <span><?php echo e($line); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="ab-detail-text"><?php echo e($objectives); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- History -->
            <div class="col-lg-5 ab-reveal ab-d2">
                <div class="ab-detail-card ab-history-card">
                    <div class="ab-detail-icon ic-violet"><i class="bi bi-clock-history"></i></div>
                    <h3>Our History</h3>
                    <p class="ab-detail-text"><?php echo nl2br(e($history)); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     CORE VALUES
     ═══════════════════════════════════ -->
<section class="ab-values">
    <div class="container">
        <div class="ab-section-header ab-reveal">
            <div class="ab-section-tag"><i class="bi bi-gem"></i> Core Values</div>
            <h2 class="ab-section-title">What We Stand For</h2>
            <p class="ab-section-subtitle">The principles that guide every decision, program, and interaction in our barangay.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6 ab-reveal ab-d1">
                <div class="ab-value-card">
                    <div class="ab-value-icon" style="background:rgba(26,86,219,0.1); color:var(--ab-primary);">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                    <h5>Transparency</h5>
                    <p>Open governance with accessible records, public budgets, and honest communication at every level.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 ab-reveal ab-d2">
                <div class="ab-value-card">
                    <div class="ab-value-icon" style="background:rgba(245,158,11,0.1); color:var(--ab-accent-dark);">
                        <i class="bi bi-shield-fill-check"></i>
                    </div>
                    <h5>Accountability</h5>
                    <p>Leaders and programs measured by results — responsible stewardship of public trust and resources.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 ab-reveal ab-d3">
                <div class="ab-value-card">
                    <div class="ab-value-icon" style="background:rgba(16,185,129,0.1); color:#059669;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h5>Community</h5>
                    <p>Every resident has a voice. Inclusive programs that uplift families, youth, and the most vulnerable.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 ab-reveal ab-d4">
                <div class="ab-value-card">
                    <div class="ab-value-icon" style="background:rgba(139,92,246,0.1); color:#7c3aed;">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <h5>Innovation</h5>
                    <p>Embracing digital tools and modern solutions to deliver faster, smarter, and more accessible services.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     OFFICIALS PYRAMID
     ═══════════════════════════════════ -->
<section class="ab-officials-section" id="officials">
    <div class="container">
        <div class="ab-section-header ab-reveal">
            <div class="ab-section-tag"><i class="bi bi-people-fill"></i> Barangay Officials</div>
            <h2 class="ab-section-title">Your Barangay Leadership</h2>
            <p class="ab-section-subtitle">
                Meet the dedicated public servants who lead and serve our community with integrity and commitment.
            </p>
        </div>

        <div class="ab-pyramid">

            <!-- TIER 1: Captain -->
            <?php if (!empty($officialsByTier['captain'])): ?>
                <div class="ab-pyramid-tier ab-tier-captain ab-reveal">
                    <?php foreach ($officialsByTier['captain'] as $cap): ?>
                        <div class="ab-official-card">
                            <div class="ab-crown"><i class="bi bi-award-fill"></i></div>
                            <div class="ab-official-photo">
                                <?php if (!empty($cap['photo_path'])): ?>
                                    <img src="<?php echo asset($cap['photo_path']); ?>" alt="<?php echo e($cap['official_name']); ?>">
                                <?php else: ?>
                                    <div class="ab-fallback"><i class="bi bi-person-circle"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="ab-official-name"><?php echo e($cap['official_name']); ?></div>
                            <div class="ab-official-position" style="color:<?php echo $tierConfig['captain']['color']; ?>;">
                                <?php echo e($cap['position_title']); ?>
                            </div>
                            <?php if (!empty($cap['position_label'])): ?>
                                <div class="ab-official-sublabel"><?php echo e($cap['position_label']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="ab-connector cn-1 ab-reveal"></div>
            <?php endif; ?>

            <!-- TIER 2: Executive -->
            <?php if (!empty($officialsByTier['executive'])): ?>
                <div class="ab-pyramid-tier ab-tier-executive ab-reveal ab-d1">
                    <?php foreach ($officialsByTier['executive'] as $exec): ?>
                        <div class="ab-official-card">
                            <div class="ab-official-photo">
                                <?php if (!empty($exec['photo_path'])): ?>
                                    <img src="<?php echo asset($exec['photo_path']); ?>" alt="<?php echo e($exec['official_name']); ?>">
                                <?php else: ?>
                                    <div class="ab-fallback"><i class="bi bi-person-circle"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="ab-official-name"><?php echo e($exec['official_name']); ?></div>
                            <div class="ab-official-position" style="color:<?php echo $tierConfig['executive']['color']; ?>;">
                                <?php echo e($exec['position_title']); ?>
                            </div>
                            <?php if (!empty($exec['position_label'])): ?>
                                <div class="ab-official-sublabel"><?php echo e($exec['position_label']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="ab-connector cn-2 ab-reveal"></div>
            <?php endif; ?>

            <!-- TIER 3: Kagawads -->
            <?php if (!empty($officialsByTier['kagawad'])): ?>
                <div class="ab-pyramid-tier ab-tier-kagawad ab-reveal ab-d2">
                    <?php foreach ($officialsByTier['kagawad'] as $kag): ?>
                        <div class="ab-official-card">
                            <div class="ab-official-photo">
                                <?php if (!empty($kag['photo_path'])): ?>
                                    <img src="<?php echo asset($kag['photo_path']); ?>" alt="<?php echo e($kag['official_name']); ?>">
                                <?php else: ?>
                                    <div class="ab-fallback"><i class="bi bi-person-circle"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="ab-official-name"><?php echo e($kag['official_name']); ?></div>
                            <div class="ab-official-position" style="color:<?php echo $tierConfig['kagawad']['color']; ?>;">
                                <?php echo e($kag['position_title']); ?>
                            </div>
                            <?php if (!empty($kag['position_label'])): ?>
                                <div class="ab-official-sublabel"><?php echo e($kag['position_label']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="ab-connector cn-3 ab-reveal"></div>
            <?php endif; ?>

            <!-- TIER 4: SK / BHW / Tanod -->
            <?php if (!empty($officialsByTier['sk'])): ?>
                <div class="ab-pyramid-tier ab-tier-sk ab-reveal ab-d3">
                    <?php foreach ($officialsByTier['sk'] as $sk): ?>
                        <div class="ab-official-card">
                            <div class="ab-official-photo">
                                <?php if (!empty($sk['photo_path'])): ?>
                                    <img src="<?php echo asset($sk['photo_path']); ?>" alt="<?php echo e($sk['official_name']); ?>">
                                <?php else: ?>
                                    <div class="ab-fallback"><i class="bi bi-person-circle"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="ab-official-name"><?php echo e($sk['official_name']); ?></div>
                            <div class="ab-official-position" style="color:<?php echo $tierConfig['sk']['color']; ?>;">
                                <?php echo e($sk['position_title']); ?>
                            </div>
                            <?php if (!empty($sk['position_label'])): ?>
                                <div class="ab-official-sublabel"><?php echo e($sk['position_label']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     SCROLL REVEAL SCRIPT
     ═══════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var els = document.querySelectorAll('.ab-reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('ab-visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function(el) { obs.observe(el); });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>