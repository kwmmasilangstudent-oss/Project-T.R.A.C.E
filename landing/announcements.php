<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Announcements - ' . APP_NAME;
$pageDescription = 'Stay informed with the latest barangay announcements, community updates, and official notices.';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = getDbConnection();

/* ═══════════════════════════════════════════════
   AUTO-CREATE ALL REQUIRED TABLES (no errors)
   ═══════════════════════════════════════════════ */

$pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL DEFAULT '',
    content TEXT,
    type VARCHAR(50) DEFAULT 'general',
    priority VARCHAR(20) DEFAULT 'normal',
    is_pinned TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    expires_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS landing_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL UNIQUE,
    content TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS landing_officials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier VARCHAR(50) NOT NULL,
    sort_order INT DEFAULT 0,
    official_name VARCHAR(255) NOT NULL DEFAULT '',
    position_title VARCHAR(255) NOT NULL DEFAULT '',
    position_label VARCHAR(255) DEFAULT '',
    photo_path VARCHAR(500) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS landing_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_label VARCHAR(100) NOT NULL DEFAULT '',
    stat_value VARCHAR(50) NOT NULL DEFAULT '0',
    stat_suffix VARCHAR(10) DEFAULT '',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    key_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Fetch announcements ── */
$announcements = [];
try {
    $announcements = $pdo->query("
        SELECT a.id, a.title, a.content, a.type, a.priority, a.is_pinned, a.created_at, a.expires_at,
               u.full_name, u.role AS author_role
        FROM announcements a
        LEFT JOIN users u ON u.id = a.created_by
        WHERE a.is_active = 1
          AND a.audience = 'all'
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($announcements as &$a) {
        $a['is_expired'] = !empty($a['expires_at']) && strtotime($a['expires_at']) < time();
    }
} catch (Throwable $e) {
    $announcements = [];
}

$barangayName = getSetting('barangay_name', 'Barangay Tumalaytay');
$heroBg = getSetting('hero_background', '');

/* ── Type config ── */
$typeConfig = [
    'general'        => ['label' => 'General',          'icon' => 'bi-megaphone',      'bg' => 'rgba(99,102,241,0.12)',  'color' => '#6366f1', 'border' => 'rgba(99,102,241,0.25)'],
    'event'          => ['label' => 'Event',            'icon' => 'bi-calendar-event',  'bg' => 'rgba(26,86,219,0.12)',   'color' => '#2f7bff', 'border' => 'rgba(26,86,219,0.25)'],
    'health'         => ['label' => 'Health',           'icon' => 'bi-heart-pulse',     'bg' => 'rgba(16,185,129,0.12)',  'color' => '#10b981', 'border' => 'rgba(16,185,129,0.25)'],
    'emergency'      => ['label' => 'Emergency',        'icon' => 'bi-exclamation-triangle', 'bg' => 'rgba(239,68,68,0.12)', 'color' => '#ef4444', 'border' => 'rgba(239,68,68,0.25)'],
    'infrastructure' => ['label' => 'Infrastructure',   'icon' => 'bi-building',        'bg' => 'rgba(245,158,11,0.12)',  'color' => '#f59e0b', 'border' => 'rgba(245,158,11,0.25)'],
    'education'      => ['label' => 'Education',        'icon' => 'bi-mortarboard',     'bg' => 'rgba(139,92,246,0.12)',  'color' => '#8b5cf6', 'border' => 'rgba(139,92,246,0.25)'],
    'news'           => ['label' => 'News',             'icon' => 'bi-newspaper',       'bg' => 'rgba(99,102,241,0.12)',  'color' => '#6366f1', 'border' => 'rgba(99,102,241,0.25)'],
    'program'        => ['label' => 'Program',          'icon' => 'bi-clipboard-data',    'bg' => 'rgba(20,184,166,0.12)',  'color' => '#0d9488', 'border' => 'rgba(20,184,166,0.25)'],
    'meeting'        => ['label' => 'Meeting',          'icon' => 'bi-calendar-check',    'bg' => 'rgba(6,182,212,0.12)',   'color' => '#06b6d4', 'border' => 'rgba(6,182,212,0.25)'],
    'maintenance'    => ['label' => 'Maintenance',      'icon' => 'bi-tools',             'bg' => 'rgba(107,114,128,0.12)', 'color' => '#6b7280', 'border' => 'rgba(107,114,128,0.25)'],
];

function getTypeInfo($type) {
    global $typeConfig;
    return $typeConfig[$type] ?? $typeConfig['general'];
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --an-primary: #1a56db;
        --an-primary-dark: #1042a3;
        --an-primary-light: #e8effc;
        --an-accent: #f59e0b;
        --an-accent-dark: #d97706;
        --an-green: #10b981;
        --an-red: #ef4444;
        --an-violet: #8b5cf6;
        --an-bg: #f0f4f8;
        --an-card: #ffffff;
        --an-hero: #0f172a;
        --an-text: #0f172a;
        --an-muted: #64748b;
        --an-light: #94a3b8;
        --an-border: #e2e8f0;
        --an-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --an-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        --an-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
        --an-radius: 12px;
        --an-radius-lg: 20px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--an-bg);
        color: var(--an-text);
        overflow-x: hidden;
    }

    /* ═══════════════════════════════════
       HERO
       ═══════════════════════════════════ */
    .an-hero {
        position: relative;
        padding: 100px 0 80px;
        background: var(--an-hero);
        overflow: hidden;
    }

    <?php if (!empty($heroBg)): ?>
    .an-hero {
        background: linear-gradient(160deg, rgba(15,23,42,0.93), rgba(15,23,42,0.87)),
                    url('<?php echo asset($heroBg); ?>') center/cover no-repeat;
    }
    <?php endif; ?>

    .an-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 15% 50%, rgba(239,68,68,0.1) 0%, transparent 50%),
            radial-gradient(ellipse at 85% 20%, rgba(245,158,11,0.08) 0%, transparent 40%);
        pointer-events: none;
    }

    .an-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        pointer-events: none;
    }

    .an-hero-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
    }

    .an-floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
        animation: anFloat 20s ease-in-out infinite;
    }

    .an-floating-orb.o1 { width: 350px; height: 350px; background: rgba(239,68,68,0.08); top: -15%; left: -5%; }
    .an-floating-orb.o2 { width: 250px; height: 250px; background: rgba(245,158,11,0.06); bottom: -15%; right: -5%; animation-delay: -10s; }

    @keyframes anFloat {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33%      { transform: translate(25px, -15px) scale(1.05); }
        66%      { transform: translate(-15px, 10px) scale(0.95); }
    }

    .an-hero-content { position: relative; z-index: 2; }

    .an-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 28px;
        font-size: 0.82rem;
    }

    .an-breadcrumb a {
        color: var(--an-light);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .an-breadcrumb a:hover { color: #fff; }
    .an-breadcrumb .an-sep { color: rgba(255,255,255,0.2); }
    .an-breadcrumb .an-current { color: #7db0ff; font-weight: 600; }

    .an-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.25);
        border-radius: 100px;
        color: #fca5a5;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .an-hero-badge .an-dot {
        width: 8px; height: 8px;
        background: var(--an-red);
        border-radius: 50%;
        animation: anPulse 2s ease-in-out infinite;
    }

    @keyframes anPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.4); }
    }

    .an-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.2rem, 5vw, 3.8rem);
        font-weight: 900;
        color: #ffffff;
        line-height: 1.1;
        margin-bottom: 16px;
    }

    .an-hero-title span {
        background: linear-gradient(135deg, var(--an-accent), #fbbf24);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .an-hero-subtitle {
        font-size: 1.1rem;
        color: #94a3b8;
        max-width: 580px;
        line-height: 1.7;
        margin-bottom: 0;
    }

    /* Hero stat pills */
    .an-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 28px;
    }

    .an-hero-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 100px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .an-hero-stat-pill:hover {
        background: rgba(255,255,255,0.08);
        border-color: rgba(255,255,255,0.15);
    }

    .an-hero-stat-pill .an-pill-num {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.2rem;
        color: #fff;
    }

    .an-hero-stat-pill .an-pill-label {
        font-size: 0.78rem;
        color: var(--an-light);
        font-weight: 500;
    }

    /* ═══════════════════════════════════
       FILTER TABS
       ═══════════════════════════════════ */
    .an-filter-bar {
        margin-top: -30px;
        position: relative;
        z-index: 10;
        margin-bottom: 36px;
    }

    .an-filter-inner {
        background: var(--an-card);
        border: 1px solid var(--an-border);
        border-radius: var(--an-radius-lg);
        box-shadow: var(--an-shadow-md);
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .an-filter-inner::-webkit-scrollbar { height: 3px; }
    .an-filter-inner::-webkit-scrollbar-track { background: transparent; }
    .an-filter-inner::-webkit-scrollbar-thumb { background: var(--an-border); border-radius: 99px; }

    .an-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 100px;
        color: var(--an-muted);
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        font-family: 'Inter', sans-serif;
    }

    .an-filter-btn:hover {
        background: rgba(26,86,219,0.06);
        color: var(--an-text);
    }

    .an-filter-btn.active {
        background: var(--an-primary-light);
        color: var(--an-primary);
        border-color: rgba(26,86,219,0.2);
    }

    .an-filter-btn i { font-size: 0.9rem; }

    .an-filter-count {
        background: rgba(0,0,0,0.06);
        padding: 1px 7px;
        border-radius: 99px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .an-filter-btn.active .an-filter-count {
        background: rgba(26,86,219,0.15);
        color: var(--an-primary);
    }

    /* ═══════════════════════════════════
       ANNOUNCEMENT CARDS
       ═══════════════════════════════════ */
    .an-cards-section {
        padding-bottom: 100px;
    }

    .an-card {
        background: var(--an-card);
        border: 1px solid var(--an-border);
        border-radius: var(--an-radius-lg);
        box-shadow: var(--an-shadow-sm);
        padding: 0;
        height: 100%;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .an-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        transition: opacity 0.3s ease;
    }

    .an-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--an-shadow-lg);
    }

    .an-card-header {
        padding: 22px 24px 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .an-card-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        flex-shrink: 0;
    }

    .an-card-type-badge i { font-size: 0.75rem; }

    .an-card-pinned {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: rgba(245,158,11,0.1);
        border: 1px solid rgba(245,158,11,0.25);
        border-radius: 100px;
        color: var(--an-accent-dark);
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        flex-shrink: 0;
    }

    .an-card-pinned i { font-size: 0.65rem; }

    .an-card-body {
        padding: 14px 24px 0;
        flex: 1;
    }

    .an-card-body h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--an-text);
        margin-bottom: 10px;
        line-height: 1.35;
    }

    .an-card-body .an-card-content {
        font-size: 0.9rem;
        color: var(--an-muted);
        line-height: 1.7;
        margin: 0;
    }

    .an-card-footer {
        padding: 16px 24px;
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        border-top: 1px solid var(--an-border);
    }

    .an-card-date {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: var(--an-light);
    }

    .an-card-date i { font-size: 0.85rem; }

    .an-card-ago {
        font-size: 0.78rem;
        color: var(--an-light);
        font-weight: 500;
    }

    .an-card-author {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--an-primary);
    }

    /* Pinned card accent */
    .an-card.pinned {
        border-color: rgba(245,158,11,0.2);
    }

    .an-card.pinned::before {
        background: linear-gradient(90deg, var(--an-accent), #fbbf24);
        opacity: 1;
    }

    .an-card:not(.pinned)::before {
        opacity: 0.85;
    }

    .an-card.expired {
        opacity: 0.75;
        filter: grayscale(0.25);
    }

    .an-card.expired:hover {
        opacity: 1;
        filter: grayscale(0);
    }

    .an-card-expired {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: rgba(100,116,139,0.10);
        border: 1px solid rgba(100,116,139,0.25);
        border-radius: 100px;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .an-card-expired i { font-size: 0.65rem; }

    /* High priority pulse */
    .an-priority-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 4px;
    }

    .an-priority-dot.high {
        background: var(--an-red);
        animation: anPriorityPulse 1.5s ease-in-out infinite;
    }

    .an-priority-dot.normal { background: var(--an-green); }

    @keyframes anPriorityPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50% { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
    }

    /* ═══════════════════════════════════
       EMPTY STATE
       ═══════════════════════════════════ */
    .an-empty {
        text-align: center;
        padding: 80px 20px;
    }

    .an-empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(26,86,219,0.08);
        border: 1px solid rgba(26,86,219,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        color: var(--an-primary);
        opacity: 0.6;
    }

    .an-empty h3 {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.3rem;
        color: var(--an-text);
        margin-bottom: 8px;
    }

    .an-empty p {
        font-size: 0.95rem;
        color: var(--an-muted);
        max-width: 400px;
        margin: 0 auto;
        line-height: 1.6;
    }

    /* ═══════════════════════════════════
       SCROLL REVEAL
       ═══════════════════════════════════ */
    .an-reveal {
        opacity: 0;
        transform: translateY(35px);
        transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .an-reveal.an-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .an-d1 { transition-delay: 0.05s; }
    .an-d2 { transition-delay: 0.1s; }
    .an-d3 { transition-delay: 0.15s; }
    .an-d4 { transition-delay: 0.2s; }
    .an-d5 { transition-delay: 0.25s; }
    .an-d6 { transition-delay: 0.3s; }
    .an-d7 { transition-delay: 0.35s; }
    .an-d8 { transition-delay: 0.4s; }

    /* ═══════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════ */
    @media (max-width: 991.98px) {
        .an-hero { padding: 80px 0 60px; }
        .an-filter-bar { margin-top: -20px; }
    }

    @media (max-width: 767.98px) {
        .an-hero { padding: 70px 0 50px; }
        .an-filter-bar { margin-top: -18px; margin-bottom: 28px; }
        .an-filter-inner { padding: 10px 12px; }
        .an-filter-btn { padding: 6px 12px; font-size: 0.76rem; }

        .an-card-header { padding: 18px 18px 0; flex-wrap: wrap; }
        .an-card-body { padding: 12px 18px 0; }
        .an-card-body h3 { font-size: 1.05rem; }
        .an-card-body .an-card-content { font-size: 0.85rem; }
        .an-card-footer { padding: 14px 18px; flex-wrap: wrap; gap: 8px; }

        .an-hero-stats { gap: 8px; }
        .an-hero-stat-pill { padding: 8px 14px; }
        .an-hero-stat-pill .an-pill-num { font-size: 1rem; }
    }
</style>

<!-- ═══════════════════════════════════
     HERO
     ═══════════════════════════════════ -->
<section class="an-hero">
    <div class="an-hero-grid"></div>
    <div class="an-floating-orb o1"></div>
    <div class="an-floating-orb o2"></div>

    <div class="container an-hero-content">
        <div class="an-breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house"></i> Home</a>
            <span class="an-sep">/</span>
            <span class="an-current">Announcements</span>
        </div>

        <div class="an-hero-badge">
            <span class="an-dot"></span>
            Community Updates
        </div>

        <h1 class="an-hero-title">Public <span>Announcements</span></h1>
        <p class="an-hero-subtitle">
            Stay informed with the latest notices, events, advisories, and important updates from <?php echo e($barangayName); ?>.
        </p>

        <div class="an-hero-stats">
            <div class="an-hero-stat-pill">
                <span class="an-pill-num"><?php echo count($announcements); ?></span>
                <span class="an-pill-label">Active Notices</span>
            </div>
            <?php
                $pinnedCount = 0;
                $emergencyCount = 0;
                foreach ($announcements as $a) {
                    if ($a['is_pinned']) $pinnedCount++;
                    if ($a['type'] === 'emergency') $emergencyCount++;
                }
            ?>
            <?php if ($pinnedCount > 0): ?>
            <div class="an-hero-stat-pill">
                <span class="an-pill-num"><?php echo $pinnedCount; ?></span>
                <span class="an-pill-label">Pinned</span>
            </div>
            <?php endif; ?>
            <?php if ($emergencyCount > 0): ?>
            <div class="an-hero-stat-pill" style="border-color: rgba(239,68,68,0.2);">
                <span class="an-pill-num" style="color: #fca5a5;"><?php echo $emergencyCount; ?></span>
                <span class="an-pill-label">Urgent</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     FILTER BAR
     ═══════════════════════════════════ -->
<?php if (!empty($announcements)): ?>
<div class="container an-filter-bar an-reveal">
    <div class="an-filter-inner">
        <button class="an-filter-btn active" data-filter="all">
            <i class="bi bi-grid-3x3-gap-fill"></i> All
            <span class="an-filter-count"><?php echo count($announcements); ?></span>
        </button>
        <?php
            $typeCounts = [];
            foreach ($announcements as $a) {
                $t = $a['type'] ?? 'general';
                $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
            }
            foreach ($typeCounts as $type => $cnt):
                $info = getTypeInfo($type);
        ?>
            <button class="an-filter-btn" data-filter="<?php echo e($type); ?>">
                <i class="bi <?php echo e($info['icon']); ?>"></i>
                <?php echo e($info['label']); ?>
                <span class="an-filter-count"><?php echo $cnt; ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════
     ANNOUNCEMENT CARDS
     ═══════════════════════════════════ -->
<section class="an-cards-section">
    <div class="container">
        <?php if (!empty($announcements)): ?>
            <div class="row g-4" id="anCardsGrid">
                <?php foreach ($announcements as $idx => $announcement):
                    $type = $announcement['type'] ?? 'general';
                    $info = getTypeInfo($type);
                    $isPinned = !empty($announcement['is_pinned']);
                    $priority = $announcement['priority'] ?? 'normal';
                    $delayClass = 'an-d' . min($idx + 1, 8);

                    $created = strtotime($announcement['created_at']);
                    $now = time();
                    $diff = $now - $created;
                    if ($diff < 3600) {
                        $ago = max(1, floor($diff / 60)) . ' min ago';
                    } elseif ($diff < 86400) {
                        $ago = floor($diff / 3600) . ' hr ago';
                    } elseif ($diff < 604800) {
                        $ago = floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
                    } else {
                        $ago = date('M d, Y', $created);
                    }
                ?>
                    <div class="col-lg-6 an-reveal <?php echo $delayClass; ?>" data-type="<?php echo e($type); ?>">
                        <div class="an-card <?php echo $isPinned ? 'pinned' : ''; ?> <?php echo !empty($announcement['is_expired']) ? 'expired' : ''; ?>" style="--card-color: <?php echo $info['color']; ?>;">
                            <div style="position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, <?php echo $info['color']; ?>, <?php echo $info['color']; ?>88); <?php echo $isPinned ? '' : 'opacity:0.85;'; ?>"></div>

                            <div class="an-card-header">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="an-card-type-badge" style="background:<?php echo $info['bg']; ?>; color:<?php echo $info['color']; ?>; border:1px solid <?php echo $info['border']; ?>;">
                                        <i class="bi <?php echo e($info['icon']); ?>"></i>
                                        <?php echo e($info['label']); ?>
                                    </span>
                                    <?php if ($priority === 'high'): ?>
                                        <span class="an-priority-dot high" title="High priority"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <?php if ($isPinned): ?>
                                        <span class="an-card-pinned"><i class="bi bi-pin-fill"></i>Pinned</span>
                                    <?php endif; ?>
                                    <?php if (!empty($announcement['is_expired'])): ?>
                                        <span class="an-card-expired"><i class="bi bi-clock-history"></i>Expired</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="an-card-body">
                                <h3><?php echo e($announcement['title']); ?></h3>
                                <p class="an-card-content"><?php echo nl2br(e($announcement['content'])); ?></p>
                            </div>

                            <div class="an-card-footer">
                                <span class="an-card-date">
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo date('M d, Y \a\t h:i A', $created); ?>
                                </span>
                                <span class="an-card-ago"><?php echo $ago; ?></span>
                                <?php if (!empty($announcement['author_role'])): ?>
                                <span class="an-card-author">
                                    <i class="bi bi-person-badge"></i>
                                    <?php echo e(ucfirst($announcement['author_role'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="an-empty an-reveal">
                <div class="an-empty-icon"><i class="bi bi-megaphone"></i></div>
                <h3>No Announcements Yet</h3>
                <p>There are no active announcements at this time. Check back soon for community updates, events, and advisories.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ── Scroll Reveal ── */
    var reveals = document.querySelectorAll('.an-reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('an-visible');
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function(el) { obs.observe(el); });

    /* ── Filter Tabs ── */
    var filterBtns = document.querySelectorAll('.an-filter-btn');
    var cards = document.querySelectorAll('#anCardsGrid [data-type]');

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var filter = this.getAttribute('data-filter');

            filterBtns.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            cards.forEach(function(card) {
                var cardType = card.getAttribute('data-type');
                if (filter === 'all' || cardType === filter) {
                    card.style.display = '';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(15px)';
                    setTimeout(function() {
                        card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    card.style.transition = 'opacity 0.2s ease';
                    card.style.opacity = '0';
                    setTimeout(function() {
                        card.style.display = 'none';
                    }, 200);
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>