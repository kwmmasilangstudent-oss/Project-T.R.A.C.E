<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Services - ' . APP_NAME;
$pageDescription = 'Explore barangay services including document requests, community programs, and resident resources.';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = getDbConnection();

/* ═══════════════════════════════════════════════
   AUTO-CREATE REQUIRED TABLES
   ═══════════════════════════════════════════════ */

$pdo->exec("CREATE TABLE IF NOT EXISTS landing_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL UNIQUE,
    content TEXT,
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

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    key_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

$pdo->exec("CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT DEFAULT NULL,
    document_type VARCHAR(100) NOT NULL DEFAULT '',
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Content ── */
$services   = getLandingContent('services', 'Barangay services, document requests, and public updates are now centralized for your convenience.');
$objectives = getLandingContent('objectives', 'To improve public service delivery and digital access for all residents.');
$contact    = getLandingContent('contact', '(02) 8123-4567');
$mission    = getLandingContent('mission', 'To serve the community with integrity and excellence.');

$barangayName = getSetting('barangay_name', 'Barangay Tumalaytay');
$heroBg = getSetting('hero_background', '');

/* ── Stats ── */
$stats = [];
try {
    $stats = $pdo->query("SELECT * FROM landing_stats WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $stats = [];
}

/* ── Fallback live stats ── */
$liveStats = [];
$tables = ['residents', 'officials', 'documents'];
foreach ($tables as $tbl) {
    try {
        $liveStats[$tbl] = (int) $pdo->query("SELECT COUNT(*) FROM {$tbl}")->fetchColumn();
    } catch (Exception $e) {
        $liveStats[$tbl] = 0;
    }
}
try {
    $liveStats['issued'] = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'issued'")->fetchColumn();
} catch (Exception $e) {
    $liveStats['issued'] = 0;
}

/* ── Service definitions ── */
$serviceList = [
    [
        'title'       => 'Barangay Clearance',
        'desc'        => 'Official clearance certificate for employment, business registration, travel, legal, and other personal purposes.',
        'icon'        => 'bi-file-earmark-check',
        'color'       => '#2f7bff',
        'bg'          => 'rgba(47,123,255,0.1)',
        'border'      => 'rgba(47,123,255,0.2)',
        'requirement' => 'Valid ID, Cedula',
        'duration'    => '1–2 working days',
    ],
    [
        'title'       => 'Certificate of Residency',
        'desc'        => 'Certified proof of residence within the barangay — required for school enrollment, employment, and government transactions.',
        'icon'        => 'bi-house-door',
        'color'       => '#10b981',
        'bg'          => 'rgba(16,185,129,0.1)',
        'border'      => 'rgba(16,185,129,0.2)',
        'requirement' => 'Valid ID, Utility Bill',
        'duration'    => '1 working day',
    ],
    [
        'title'       => 'Certificate of Indigency',
        'desc'        => 'For low-income families seeking medical, educational, burial, or financial assistance from government and NGOs.',
        'icon'        => 'bi-heart',
        'color'       => '#f43f5e',
        'bg'          => 'rgba(244,63,94,0.1)',
        'border'      => 'rgba(244,63,94,0.2)',
        'requirement' => 'Valid ID, Barangay Certification',
        'duration'    => '1–2 working days',
    ],
    [
        'title'       => 'Business Clearance',
        'desc'        => 'Permit clearance for business operations, renewal, or new establishment registration within the barangay jurisdiction.',
        'icon'        => 'bi-shop',
        'color'       => '#f59e0b',
        'bg'          => 'rgba(245,158,11,0.1)',
        'border'      => 'rgba(245,158,11,0.2)',
        'requirement' => 'DTI/SEC Registration, Valid ID',
        'duration'    => '2–3 working days',
    ],
    [
        'title'       => 'First Time Job Seeker',
        'desc'        => 'Certificate for first-time job seekers under RA 11261 — waives government fees for IDs, clearances, and documents.',
        'icon'        => 'bi-person-workspace',
        'color'       => '#8b5cf6',
        'bg'          => 'rgba(139,92,246,0.1)',
        'border'      => 'rgba(139,92,246,0.2)',
        'requirement' => 'Valid ID, Oath of Undertaking',
        'duration'    => 'Same day',
    ],
    [
        'title'       => 'Good Moral Character',
        'desc'        => 'Certification of good moral character and standing within the community — for school, employment, and legal purposes.',
        'icon'        => 'bi-award',
        'color'       => '#14b8a6',
        'bg'          => 'rgba(20,184,166,0.1)',
        'border'      => 'rgba(20,184,166,0.2)',
        'requirement' => 'Valid ID, No Blotter Record',
        'duration'    => '1–2 working days',
    ],
    [
        'title'       => 'Barangay ID',
        'desc'        => 'Official barangay identification card for residents — used for identification, transactions, and access to barangay services.',
        'icon'        => 'bi-person-badge',
        'color'       => '#6366f1',
        'bg'          => 'rgba(99,102,241,0.1)',
        'border'      => 'rgba(99,102,241,0.2)',
        'requirement' => '1x1 Photo, Valid ID, Cedula',
        'duration'    => '3–5 working days',
    ],
    [
        'title'       => 'Blotter / Incident Report',
        'desc'        => 'File a formal incident report or blotter for disputes, disturbances, or any peace and order concerns in the community.',
        'icon'        => 'bi-shield-exclamation',
        'color'       => '#ef4444',
        'bg'          => 'rgba(239,68,68,0.1)',
        'border'      => 'rgba(239,68,68,0.2)',
        'requirement' => 'Valid ID, Written Statement',
        'duration'    => 'Same day',
    ],
];
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --sv-primary: #1a56db;
        --sv-primary-dark: #1042a3;
        --sv-primary-light: #e8effc;
        --sv-accent: #f59e0b;
        --sv-accent-dark: #d97706;
        --sv-green: #10b981;
        --sv-red: #ef4444;
        --sv-violet: #8b5cf6;
        --sv-bg: #f0f4f8;
        --sv-card: #ffffff;
        --sv-hero: #0f172a;
        --sv-text: #0f172a;
        --sv-muted: #64748b;
        --sv-light: #94a3b8;
        --sv-border: #e2e8f0;
        --sv-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --sv-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        --sv-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
        --sv-shadow-xl: 0 20px 60px rgba(0,0,0,0.15);
        --sv-radius: 12px;
        --sv-radius-lg: 20px;
        --sv-radius-xl: 28px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--sv-bg);
        color: var(--sv-text);
        overflow-x: hidden;
    }

    /* ═══════════════════════════════════
       HERO
       ═══════════════════════════════════ */
    .sv-hero {
        position: relative;
        padding: 100px 0 80px;
        background: var(--sv-hero);
        overflow: hidden;
    }

    <?php if (!empty($heroBg)): ?>
    .sv-hero {
        background: linear-gradient(160deg, rgba(15,23,42,0.93), rgba(15,23,42,0.87)),
                    url('<?php echo asset($heroBg); ?>') center/cover no-repeat;
    }
    <?php endif; ?>

    .sv-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 20% 50%, rgba(26,86,219,0.14) 0%, transparent 50%),
            radial-gradient(ellipse at 80% 20%, rgba(245,158,11,0.09) 0%, transparent 40%);
        pointer-events: none;
    }

    .sv-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        pointer-events: none;
    }

    .sv-hero-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
    }

    .sv-floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
        animation: svFloat 20s ease-in-out infinite;
    }

    .sv-floating-orb.o1 { width: 380px; height: 380px; background: rgba(26,86,219,0.12); top: -15%; left: -5%; }
    .sv-floating-orb.o2 { width: 280px; height: 280px; background: rgba(245,158,11,0.07); bottom: -15%; right: -5%; animation-delay: -10s; }
    .sv-floating-orb.o3 { width: 180px; height: 180px; background: rgba(16,185,129,0.06); top: 40%; left: 60%; animation-delay: -5s; }

    @keyframes svFloat {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33%      { transform: translate(25px, -15px) scale(1.05); }
        66%      { transform: translate(-15px, 10px) scale(0.95); }
    }

    .sv-hero-content { position: relative; z-index: 2; }

    .sv-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 28px;
        font-size: 0.82rem;
    }

    .sv-breadcrumb a { color: var(--sv-light); text-decoration: none; transition: color 0.2s ease; }
    .sv-breadcrumb a:hover { color: #fff; }
    .sv-breadcrumb .sv-sep { color: rgba(255,255,255,0.2); }
    .sv-breadcrumb .sv-current { color: #7db0ff; font-weight: 600; }

    .sv-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.25);
        border-radius: 100px;
        color: #6ee7b7;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .sv-hero-badge .sv-dot {
        width: 8px; height: 8px;
        background: var(--sv-green);
        border-radius: 50%;
        animation: svPulse 2s ease-in-out infinite;
    }

    @keyframes svPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.4); }
    }

    .sv-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.2rem, 5vw, 3.8rem);
        font-weight: 900;
        color: #ffffff;
        line-height: 1.1;
        margin-bottom: 16px;
    }

    .sv-hero-title span {
        background: linear-gradient(135deg, var(--sv-accent), #fbbf24);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .sv-hero-desc {
        font-size: 1.1rem;
        color: #94a3b8;
        max-width: 580px;
        line-height: 1.7;
        margin-bottom: 28px;
    }

    .sv-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
    }

    .sv-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 15px 30px;
        background: linear-gradient(135deg, var(--sv-primary), var(--sv-primary-dark));
        color: #fff;
        font-size: 0.95rem;
        font-weight: 600;
        border: none;
        border-radius: var(--sv-radius);
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(26,86,219,0.35);
    }

    .sv-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(26,86,219,0.5);
        color: #fff;
    }

    .sv-btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 15px 30px;
        background: rgba(255,255,255,0.05);
        color: #e2e8f0;
        font-size: 0.95rem;
        font-weight: 600;
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: var(--sv-radius);
        text-decoration: none;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .sv-btn-secondary:hover {
        background: rgba(255,255,255,0.1);
        border-color: rgba(255,255,255,0.25);
        color: #fff;
        transform: translateY(-2px);
    }

    /* Hero side panel */
    .sv-hero-panel {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: var(--sv-radius-lg);
        padding: 30px;
        backdrop-filter: blur(20px);
    }

    .sv-hero-panel h4 {
        color: #e2e8f0;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sv-hero-panel h4 i { color: var(--sv-accent); }

    .sv-panel-stat {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .sv-panel-stat:last-child { border-bottom: none; }

    .sv-panel-stat-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .sv-panel-stat-num {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.4rem;
        color: #fff;
        line-height: 1;
    }

    .sv-panel-stat-label {
        font-size: 0.78rem;
        color: var(--sv-light);
        font-weight: 500;
    }

    /* ═══════════════════════════════════
       LIVE STATS BAR
       ═══════════════════════════════════ */
    .sv-stats-bar {
        margin-top: -50px;
        position: relative;
        z-index: 10;
        margin-bottom: 50px;
    }

    .sv-stats-grid {
        display: grid;
        grid-template-columns: repeat(<?php echo max(count($stats), 4); ?>, 1fr);
        gap: 0;
        background: var(--sv-card);
        border-radius: var(--sv-radius-lg);
        box-shadow: var(--sv-shadow-xl);
        overflow: hidden;
    }

    .sv-stat-item {
        padding: 32px 24px;
        text-align: center;
        border-right: 1px solid var(--sv-border);
        transition: all 0.3s ease;
    }

    .sv-stat-item:last-child { border-right: none; }
    .sv-stat-item:hover { background: var(--sv-primary-light); }

    .sv-stat-num {
        font-family: 'Playfair Display', serif;
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--sv-primary);
        line-height: 1;
        margin-bottom: 6px;
    }

    .sv-stat-lbl {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--sv-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* ═══════════════════════════════════
       SECTION HEADERS
       ═══════════════════════════════════ */
    .sv-section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .sv-section-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: var(--sv-primary-light);
        color: var(--sv-primary);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        border-radius: 100px;
        margin-bottom: 16px;
    }

    .sv-section-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 800;
        color: var(--sv-text);
        margin-bottom: 12px;
        line-height: 1.15;
    }

    .sv-section-subtitle {
        font-size: 1rem;
        color: var(--sv-muted);
        max-width: 560px;
        margin: 0 auto;
        line-height: 1.7;
    }

    /* ═══════════════════════════════════
       SERVICE CARDS
       ═══════════════════════════════════ */
    .sv-services-section {
        padding-bottom: 40px;
    }

    .sv-svc-card {
        background: var(--sv-card);
        border: 1px solid var(--sv-border);
        border-radius: var(--sv-radius-lg);
        box-shadow: var(--sv-shadow-sm);
        padding: 0;
        height: 100%;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .sv-svc-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        transition: opacity 0.3s ease;
        opacity: 0.85;
    }

    .sv-svc-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--sv-shadow-lg);
    }

    .sv-svc-card:hover::before { opacity: 1; }

    .sv-svc-card-body {
        padding: 28px 24px 0;
        flex: 1;
    }

    .sv-svc-icon {
        width: 54px;
        height: 54px;
        border-radius: var(--sv-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 18px;
        transition: transform 0.3s ease;
    }

    .sv-svc-card:hover .sv-svc-icon { transform: scale(1.1) rotate(-5deg); }

    .sv-svc-card-body h3 {
        font-weight: 700;
        font-size: 1.05rem;
        color: var(--sv-text);
        margin-bottom: 10px;
    }

    .sv-svc-card-body p {
        font-size: 0.88rem;
        color: var(--sv-muted);
        line-height: 1.65;
        margin: 0;
    }

    .sv-svc-meta {
        padding: 16px 24px;
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid var(--sv-border);
        gap: 8px;
        flex-wrap: wrap;
    }

    .sv-svc-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 100px;
        background: rgba(0,0,0,0.03);
        color: var(--sv-muted);
    }

    .sv-svc-meta-item i { font-size: 0.78rem; }

    /* ═══════════════════════════════════
       OBJECTIVES SECTION
       ═══════════════════════════════════ */
    .sv-objectives-section {
        padding: 60px 0 100px;
        background: linear-gradient(180deg, var(--sv-bg) 0%, #e8edf4 100%);
    }

    .sv-obj-card {
        background: var(--sv-card);
        border: 1px solid var(--sv-border);
        border-radius: var(--sv-radius-lg);
        box-shadow: var(--sv-shadow-md);
        padding: 40px;
        position: relative;
        overflow: hidden;
    }

    .sv-obj-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--sv-green), #34d399);
    }

    .sv-obj-icon {
        width: 56px;
        height: 56px;
        border-radius: var(--sv-radius);
        background: rgba(16,185,129,0.1);
        color: #059669;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 20px;
    }

    .sv-obj-card h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--sv-text);
        margin-bottom: 16px;
    }

    .sv-obj-text {
        font-size: 1rem;
        color: var(--sv-muted);
        line-height: 1.8;
    }

    /* Objectives as checklist */
    .sv-obj-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sv-obj-list li {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid var(--sv-border);
        font-size: 0.95rem;
        color: var(--sv-muted);
        line-height: 1.6;
        transition: all 0.2s ease;
    }

    .sv-obj-list li:last-child { border-bottom: none; }
    .sv-obj-list li:hover { color: var(--sv-text); transform: translateX(4px); }

    .sv-obj-check {
        width: 28px;
        height: 28px;
        min-width: 28px;
        background: rgba(16,185,129,0.1);
        border: 1px solid rgba(16,185,129,0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #059669;
        font-size: 0.75rem;
        margin-top: 2px;
    }

    /* ═══════════════════════════════════
       HOW IT WORKS
       ═══════════════════════════════════ */
    .sv-process-section {
        padding: 80px 0;
    }

    .sv-process-card {
        background: var(--sv-card);
        border: 1px solid var(--sv-border);
        border-radius: var(--sv-radius-lg);
        padding: 32px 24px;
        text-align: center;
        height: 100%;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }

    .sv-process-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        transform: scaleX(0);
        transition: transform 0.4s ease;
    }

    .sv-process-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--sv-shadow-md);
    }

    .sv-process-card:hover::after { transform: scaleX(1); }

    .sv-process-card:nth-child(1)::after { background: var(--sv-primary); }
    .sv-process-card:nth-child(2)::after { background: var(--sv-accent); }
    .sv-process-card:nth-child(3)::after { background: var(--sv-green); }
    .sv-process-card:nth-child(4)::after { background: var(--sv-violet); }

    .sv-process-num {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.2rem;
    }

    .sv-process-card h5 {
        font-weight: 700;
        font-size: 1rem;
        color: var(--sv-text);
        margin-bottom: 8px;
    }

    .sv-process-card p {
        font-size: 0.85rem;
        color: var(--sv-muted);
        line-height: 1.6;
        margin: 0;
    }

    /* Connector arrows between steps */
    .sv-process-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--sv-light);
        font-size: 1.2rem;
        padding: 0;
    }

    /* ═══════════════════════════════════
       CTA
       ═══════════════════════════════════ */
    .sv-cta-section {
        padding: 0 0 100px;
    }

    .sv-cta-card {
        background: linear-gradient(135deg, var(--sv-hero) 0%, #1e293b 100%);
        border-radius: var(--sv-radius-xl);
        padding: 60px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .sv-cta-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 30% 50%, rgba(26,86,219,0.12) 0%, transparent 50%),
            radial-gradient(ellipse at 70% 30%, rgba(245,158,11,0.08) 0%, transparent 40%);
        pointer-events: none;
    }

    .sv-cta-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
        background-size: 50px 50px;
        pointer-events: none;
    }

    .sv-cta-inner { position: relative; z-index: 2; }

    .sv-cta-inner h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.6rem, 3vw, 2.4rem);
        font-weight: 800;
        color: #fff;
        margin-bottom: 14px;
    }

    .sv-cta-inner p {
        font-size: 1rem;
        color: #94a3b8;
        max-width: 480px;
        margin: 0 auto 28px;
        line-height: 1.7;
    }

    .sv-cta-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 14px;
    }

    .sv-btn-cta-primary {
        padding: 15px 36px;
        background: linear-gradient(135deg, var(--sv-accent), #fbbf24);
        color: var(--sv-hero);
        font-weight: 700;
        font-size: 0.95rem;
        border: none;
        border-radius: var(--sv-radius);
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(245,158,11,0.3);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .sv-btn-cta-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(245,158,11,0.45);
        color: var(--sv-hero);
    }

    .sv-btn-cta-secondary {
        padding: 15px 36px;
        background: transparent;
        color: #e2e8f0;
        font-weight: 600;
        font-size: 0.95rem;
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: var(--sv-radius);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .sv-btn-cta-secondary:hover {
        background: rgba(255,255,255,0.06);
        border-color: rgba(255,255,255,0.3);
        color: #fff;
        transform: translateY(-3px);
    }

    /* ═══════════════════════════════════
       SCROLL REVEAL
       ═══════════════════════════════════ */
    .sv-reveal {
        opacity: 0;
        transform: translateY(35px);
        transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .sv-reveal.sv-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .sv-d1 { transition-delay: 0.05s; }
    .sv-d2 { transition-delay: 0.1s; }
    .sv-d3 { transition-delay: 0.15s; }
    .sv-d4 { transition-delay: 0.2s; }
    .sv-d5 { transition-delay: 0.25s; }
    .sv-d6 { transition-delay: 0.3s; }
    .sv-d7 { transition-delay: 0.35s; }
    .sv-d8 { transition-delay: 0.4s; }

    /* ═══════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════ */
    @media (max-width: 991.98px) {
        .sv-hero { padding: 80px 0 60px; }
        .sv-stats-bar { margin-top: -35px; margin-bottom: 40px; }
        .sv-stats-grid { grid-template-columns: repeat(2, 1fr); }
        .sv-stat-item:nth-child(2) { border-right: none; }
        .sv-stat-item:nth-child(1),
        .sv-stat-item:nth-child(2) { border-bottom: 1px solid var(--sv-border); }
    }

    @media (max-width: 767.98px) {
        .sv-hero { padding: 70px 0 50px; }
        .sv-hero-panel { margin-top: 30px; }
        .sv-hero-actions { flex-direction: column; }
        .sv-btn-primary, .sv-btn-secondary { width: 100%; justify-content: center; }

        .sv-stats-bar { margin-top: -25px; margin-bottom: 32px; }
        .sv-stats-grid { grid-template-columns: repeat(2, 1fr); border-radius: var(--sv-radius); }
        .sv-stat-item { padding: 22px 14px; }
        .sv-stat-num { font-size: 1.8rem; }

        .sv-svc-card-body { padding: 22px 18px 0; }
        .sv-svc-meta { padding: 14px 18px; }
        .sv-obj-card { padding: 28px 20px; }
        .sv-process-card { padding: 24px 18px; }
        .sv-process-arrow { display: none; }

        .sv-section-header { margin-bottom: 35px; }
        .sv-services-section { padding-bottom: 20px; }

        .sv-cta-card { padding: 44px 22px; }
        .sv-cta-actions { flex-direction: column; align-items: center; }
        .sv-btn-cta-primary, .sv-btn-cta-secondary { width: 100%; max-width: 280px; text-align: center; }
    }
</style>

<!-- ═══════════════════════════════════
     HERO
     ═══════════════════════════════════ -->
<section class="sv-hero">
    <div class="sv-hero-grid"></div>
    <div class="sv-floating-orb o1"></div>
    <div class="sv-floating-orb o2"></div>
    <div class="sv-floating-orb o3"></div>

    <div class="container sv-hero-content">
        <div class="sv-breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house"></i> Home</a>
            <span class="sv-sep">/</span>
            <span class="sv-current">Services</span>
        </div>

        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="sv-hero-badge">
                    <span class="sv-dot"></span>
                    Barangay Services
                </div>
                <h1 class="sv-hero-title">Community <span>Services</span></h1>
                <p class="sv-hero-desc">
                    <?php echo e($services); ?>
                </p>
                <div class="sv-hero-actions">
                    <a class="sv-btn-primary" href="<?php echo BASE_URL; ?>/auth/register.php">
                        <i class="bi bi-person-plus"></i> Request a Document
                    </a>
                    <a class="sv-btn-secondary" href="<?php echo BASE_URL; ?>/landing/officials.php">
                        <i class="bi bi-people"></i> View Officials
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="sv-hero-panel">
                    <h4><i class="bi bi-lightning-charge-fill"></i> Live Barangay Data</h4>
                    <div class="sv-panel-stat">
                        <div class="sv-panel-stat-icon" style="background:rgba(47,123,255,0.12); color:#7db0ff;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <div class="sv-panel-stat-num"><?php echo number_format($liveStats['residents']); ?></div>
                            <div class="sv-panel-stat-label">Registered Residents</div>
                        </div>
                    </div>
                    <div class="sv-panel-stat">
                        <div class="sv-panel-stat-icon" style="background:rgba(245,158,11,0.12); color:#fbbf24;">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div>
                            <div class="sv-panel-stat-num"><?php echo number_format($liveStats['officials']); ?></div>
                            <div class="sv-panel-stat-label">Barangay Officials</div>
                        </div>
                    </div>
                    <div class="sv-panel-stat">
                        <div class="sv-panel-stat-icon" style="background:rgba(16,185,129,0.12); color:#6ee7b7;">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div>
                            <div class="sv-panel-stat-num"><?php echo number_format($liveStats['issued']); ?></div>
                            <div class="sv-panel-stat-label">Certificates Issued</div>
                        </div>
                    </div>
                    <div class="sv-panel-stat">
                        <div class="sv-panel-stat-icon" style="background:rgba(139,92,246,0.12); color:#a78bfa;">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <div class="sv-panel-stat-num"><?php echo e($contact ?: 'N/A'); ?></div>
                            <div class="sv-panel-stat-label">Contact Hotline</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     DYNAMIC STATS BAR
     ═══════════════════════════════════ -->
<?php if (!empty($stats)): ?>
<section class="sv-stats-bar">
    <div class="container">
        <div class="sv-stats-grid sv-reveal">
            <?php foreach ($stats as $stat): ?>
                <div class="sv-stat-item">
                    <div class="sv-stat-num" data-target="<?php echo intval($stat['stat_value']); ?>" data-suffix="<?php echo e($stat['stat_suffix']); ?>">
                        0<?php echo e($stat['stat_suffix']); ?>
                    </div>
                    <div class="sv-stat-lbl"><?php echo e($stat['stat_label']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════
     SERVICES GRID
     ═══════════════════════════════════ -->
<section class="sv-services-section" id="services">
    <div class="container">
        <div class="sv-section-header sv-reveal">
            <div class="sv-section-tag"><i class="bi bi-grid-3x3-gap-fill"></i> Available Services</div>
            <h2 class="sv-section-title">Documents & Clearances</h2>
            <p class="sv-section-subtitle">
                Request official barangay documents online or visit the barangay hall. All services are available to registered residents.
            </p>
        </div>

        <div class="row g-4">
            <?php foreach ($serviceList as $idx => $svc):
                $delayClass = 'sv-d' . min($idx + 1, 8);
            ?>
                <div class="col-lg-3 col-md-6 sv-reveal <?php echo $delayClass; ?>">
                    <div class="sv-svc-card" style="--svc-color: <?php echo $svc['color']; ?>;">
                        <div style="position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, <?php echo $svc['color']; ?>, <?php echo $svc['color']; ?>88);"></div>

                        <div class="sv-svc-card-body">
                            <div class="sv-svc-icon" style="background:<?php echo $svc['bg']; ?>; color:<?php echo $svc['color']; ?>;">
                                <i class="bi <?php echo $svc['icon']; ?>"></i>
                            </div>
                            <h3><?php echo e($svc['title']); ?></h3>
                            <p><?php echo e($svc['desc']); ?></p>
                        </div>

                        <div class="sv-svc-meta">
                            <span class="sv-svc-meta-item">
                                <i class="bi bi-file-text"></i>
                                <?php echo e($svc['requirement']); ?>
                            </span>
                            <span class="sv-svc-meta-item">
                                <i class="bi bi-clock"></i>
                                <?php echo e($svc['duration']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     HOW IT WORKS
     ═══════════════════════════════════ -->
<section class="sv-process-section">
    <div class="container">
        <div class="sv-section-header sv-reveal">
            <div class="sv-section-tag"><i class="bi bi-diagram-3"></i> Process</div>
            <h2 class="sv-section-title">How to Request</h2>
            <p class="sv-section-subtitle">
                A simple 4-step process to request any barangay document or clearance.
            </p>
        </div>

        <div class="row g-4 align-items-center">
            <div class="col-lg-3 col-md-6 sv-reveal sv-d1">
                <div class="sv-process-card">
                    <div class="sv-process-num" style="background:rgba(47,123,255,0.1); color:var(--sv-primary);">1</div>
                    <h5>Register Account</h5>
                    <p>Create your resident account with valid personal information and upload a valid ID.</p>
                </div>
            </div>
            <div class="col-auto d-none d-lg-block sv-process-arrow"><i class="bi bi-arrow-right"></i></div>
            <div class="col-lg-3 col-md-6 sv-reveal sv-d2">
                <div class="sv-process-card">
                    <div class="sv-process-num" style="background:rgba(245,158,11,0.1); color:var(--sv-accent-dark);">2</div>
                    <h5>Submit Request</h5>
                    <p>Choose the document type, fill out the online form, and attach required requirements.</p>
                </div>
            </div>
            <div class="col-auto d-none d-lg-block sv-process-arrow"><i class="bi bi-arrow-right"></i></div>
            <div class="col-lg-3 col-md-6 sv-reveal sv-d3">
                <div class="sv-process-card">
                    <div class="sv-process-num" style="background:rgba(16,185,129,0.1); color:#059669;">3</div>
                    <h5>Processing</h5>
                    <p>Barangay staff reviews and processes your request. Track status in real-time from your dashboard.</p>
                </div>
            </div>
            <div class="col-auto d-none d-lg-block sv-process-arrow"><i class="bi bi-arrow-right"></i></div>
            <div class="col-lg-3 col-md-6 sv-reveal sv-d4">
                <div class="sv-process-card">
                    <div class="sv-process-num" style="background:rgba(139,92,246,0.1); color:#7c3aed;">4</div>
                    <h5>Claim Document</h5>
                    <p>Receive notification when ready. Visit the barangay hall with your QR code to claim.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     OBJECTIVES
     ═══════════════════════════════════ -->
<section class="sv-objectives-section">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-7 sv-reveal">
                <div class="sv-obj-card">
                    <div class="sv-obj-icon"><i class="bi bi-list-check"></i></div>
                    <h2>Barangay Objectives</h2>
                    <?php
                        $objLines = array_filter(array_map('trim', explode("\n", $objectives)));
                        $objLines = array_map(function($l) { return ltrim($l, '•-–* '); }, $objLines);
                    ?>
                    <?php if (count(array_filter($objLines)) > 1): ?>
                        <ul class="sv-obj-list">
                            <?php foreach ($objLines as $line): ?>
                                <?php if (!empty($line)): ?>
                                    <li>
                                        <span class="sv-obj-check"><i class="bi bi-check2"></i></span>
                                        <span><?php echo e($line); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="sv-obj-text"><?php echo nl2br(e($objectives)); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5 sv-reveal sv-d1">
                <div class="sv-obj-card" style="background: var(--sv-hero); border-color: rgba(255,255,255,0.08);">
                    <div style="position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, var(--sv-primary), var(--sv-accent));"></div>
                    <div class="sv-obj-icon" style="background:rgba(47,123,255,0.12); color:#7db0ff;">
                        <i class="bi bi-bullseye"></i>
                    </div>
                    <h2 style="color:#e2e8f0;">Our Mission</h2>
                    <p class="sv-obj-text" style="color:var(--sv-light);"><?php echo nl2br(e($mission)); ?></p>

                    <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.08);">
                        <a href="<?php echo BASE_URL; ?>/landing/about.php" style="display:inline-flex; align-items:center; gap:8px; color:#7db0ff; font-weight:600; font-size:0.9rem; text-decoration:none; transition: all 0.2s ease;">
                            Learn more about us <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     CTA
     ═══════════════════════════════════ -->
<section class="sv-cta-section">
    <div class="container">
        <div class="sv-cta-card sv-reveal">
            <div class="sv-cta-inner">
                <h2>Need a Document? Get Started Today</h2>
                <p>
                    Register for a free account to request barangay documents online, track your requests, and access all community services.
                </p>
                <div class="sv-cta-actions">
                    <a class="sv-btn-cta-primary" href="<?php echo BASE_URL; ?>/auth/register.php">
                        <i class="bi bi-person-plus"></i> Create Free Account
                    </a>
                    <a class="sv-btn-cta-secondary" href="<?php echo BASE_URL; ?>/auth/login.php">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ── Scroll Reveal ── */
    var reveals = document.querySelectorAll('.sv-reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('sv-visible');
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function(el) { obs.observe(el); });

    /* ── Animated Counters ── */
    var statEls = document.querySelectorAll('.sv-stat-num[data-target]');
    var counterObs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                animateCounter(e.target);
                counterObs.unobserve(e.target);
            }
        });
    }, { threshold: 0.5 });
    statEls.forEach(function(el) { counterObs.observe(el); });

    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-target'), 10);
        var suffix = el.getAttribute('data-suffix') || '';
        var duration = 2000;
        var start = performance.now();

        function update(now) {
            var elapsed = now - start;
            var progress = Math.min(elapsed / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(eased * target);
            el.textContent = current.toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>