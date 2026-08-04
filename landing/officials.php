<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Officials - ' . APP_NAME;
$pageDescription = 'Meet the elected officials and staff of Barangay Tumalaytay. View their roles, contact information, and responsibilities.';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = getDbConnection();

require_once __DIR__ . '/../migrations/run.php';

/* ═══════════════════════════════════════════════
   AUTO-CREATE REQUIRED TABLES
   ═══════════════════════════════════════════════ */

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
    contact_number VARCHAR(50) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    committee VARCHAR(255) DEFAULT '',
    bio TEXT DEFAULT NULL,
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

/* ── Seed officials if empty ── */
try {
    $officialCount = $pdo->query("SELECT COUNT(*) FROM landing_officials")->fetchColumn();
} catch (Throwable $e) {
    $officialCount = 0;
}
if ($officialCount == 0) {
    $defaults = [
        ['captain', 1, 'Hon. Juan Dela Cruz', 'Barangay Captain', 'Punong Barangay', '', '0917-123-4567', 'captain@barangay.gov.ph', 'Overall Leadership', 'Serving the community for over 10 years with dedication to transparent governance and public service.'],
        ['executive', 1, 'Maria Santos', 'Secretary', 'Kalihim', '', '0918-234-5678', 'secretary@barangay.gov.ph', 'Records & Documentation', 'Ensures all barangay records, minutes, and official documents are properly maintained and accessible.'],
        ['executive', 2, 'Roberto Garcia', 'Treasurer', 'Ingat-Yaman', '', '0919-345-6789', 'treasurer@barangay.gov.ph', 'Finance & Budget', 'Manages barangay funds with full transparency and ensures proper allocation for community programs.'],
        ['kagawad', 1, 'Ana Reyes', 'Kagawad', 'Committee on Health', '', '0920-111-2222', '', 'Health & Sanitation', 'Champions public health programs including vaccination drives, sanitation campaigns, and health education.'],
        ['kagawad', 2, 'Carlos Mendoza', 'Kagawad', 'Committee on Education', '', '0920-222-3333', '', 'Education & Scholarships', 'Advocates for educational programs, scholarship opportunities, and youth development initiatives.'],
        ['kagawad', 3, 'Elena Villanueva', 'Kagawad', 'Committee on Peace & Order', '', '0920-333-4444', '', 'Peace & Order', 'Works closely with tanod and police force to maintain peace, safety, and security in the barangay.'],
        ['kagawad', 4, 'Pedro Lim', 'Kagawad', 'Committee on Infrastructure', '', '0920-444-5555', '', 'Infrastructure & Roads', 'Oversees road projects, public facility maintenance, and barangay infrastructure development.'],
        ['kagawad', 5, 'Rosa Bautista', 'Kagawad', 'Committee on Women', '', '0920-555-6666', '', 'Women & Family Welfare', 'Leads programs for women empowerment, gender equality, and family welfare in the community.'],
        ['kagawad', 6, 'Miguel Torres', 'Kagawad', 'Committee on Agriculture', '', '0920-666-7777', '', 'Agriculture & Livelihood', 'Supports local farmers, promotes sustainable agriculture, and develops livelihood programs.'],
        ['kagawad', 7, 'Luz Fernandez', 'Kagawad', 'Committee on Budget & Appropriations', '', '0920-777-8888', '', 'Budget & Appropriations', 'Ensures responsible budget allocation and financial oversight for all barangay programs and projects.'],
        ['sk', 1, 'Andrei Cruz', 'SK Chairperson', 'Sangguniang Kabataan', '', '0921-111-1111', 'sk@barangay.gov.ph', 'Youth Development', 'Leads the youth council in implementing programs for sports, education, and youth empowerment.'],
        ['sk', 2, 'Dr. Carmen Ramos', 'BHW Coordinator', 'Health & Wellness', '', '0921-222-2222', '', 'Barangay Health Workers', 'Coordinates health worker activities, medical missions, and community health programs.'],
        ['sk', 3, 'Ricardo Santos', 'Tanod Chief', 'Peace & Order', '', '0921-333-3333', '', 'Barangay Tanod Force', 'Commands the barangay tanod force responsible for patrol, security, and emergency response.'],
    ];
    try {
        $seed = $pdo->prepare("INSERT INTO landing_officials (tier, sort_order, official_name, position_title, position_label, photo_path, contact_number, email, committee, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($defaults as $d) {
            $seed->execute($d);
        }
    } catch (Throwable $e) {}
}

/* ── Fetch officials by tier ── */
$tierOrder = ['captain', 'executive', 'kagawad', 'sk'];

$tierConfig = [
    'captain' => [
        'label'     => 'Barangay Captain',
        'sublabel'  => 'Punong Barangay',
        'color'     => '#f2b544',
        'colorBg'   => 'rgba(242,181,68,0.1)',
        'colorBdr'  => 'rgba(242,181,68,0.25)',
        'icon'      => 'bi-award-fill',
        'gradient'  => 'linear-gradient(135deg, #f2b544, #fbbf24)',
    ],
    'executive' => [
        'label'     => 'Executive Officers',
        'sublabel'  => 'Secretary & Treasurer',
        'color'     => '#2f7bff',
        'colorBg'   => 'rgba(47,123,255,0.1)',
        'colorBdr'  => 'rgba(47,123,255,0.25)',
        'icon'      => 'bi-person-workspace',
        'gradient'  => 'linear-gradient(135deg, #2f7bff, #60a5fa)',
    ],
    'kagawad' => [
        'label'     => 'Sangguniang Barangay',
        'sublabel'  => 'Kagawads',
        'color'     => '#8b5cf6',
        'colorBg'   => 'rgba(139,92,246,0.1)',
        'colorBdr'  => 'rgba(139,92,246,0.25)',
        'icon'      => 'bi-people-fill',
        'gradient'  => 'linear-gradient(135deg, #8b5cf6, #a78bfa)',
    ],
    'sk' => [
        'label'     => 'Appointed & Youth Officials',
        'sublabel'  => 'SK, BHW & Tanod',
        'color'     => '#35d18f',
        'colorBg'   => 'rgba(53,209,143,0.1)',
        'colorBdr'  => 'rgba(53,209,143,0.25)',
        'icon'      => 'bi-shield-fill',
        'gradient'  => 'linear-gradient(135deg, #35d18f, #22c55e)',
    ],
];

$officialsByTier = [];
$totalOfficials = 0;
foreach ($tierOrder as $tier) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM landing_officials WHERE tier = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmt->execute([$tier]);
        $officialsByTier[$tier] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $officialsByTier[$tier] = [];
    }
    $totalOfficials += count($officialsByTier[$tier]);
}

$barangayName = getSetting('barangay_name', 'Barangay Tumalaytay');
$heroBg = getSetting('hero_background', '');
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --of-primary: #1a56db;
        --of-primary-dark: #1042a3;
        --of-primary-light: #e8effc;
        --of-accent: #f59e0b;
        --of-accent-dark: #d97706;
        --of-green: #10b981;
        --of-red: #ef4444;
        --of-violet: #8b5cf6;
        --of-bg: #f0f4f8;
        --of-card: #ffffff;
        --of-hero: #0f172a;
        --of-text: #0f172a;
        --of-muted: #64748b;
        --of-light: #94a3b8;
        --of-border: #e2e8f0;
        --of-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --of-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        --of-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
        --of-shadow-xl: 0 20px 60px rgba(0,0,0,0.15);
        --of-radius: 12px;
        --of-radius-lg: 20px;
        --of-radius-xl: 28px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--of-bg);
        color: var(--of-text);
        overflow-x: hidden;
    }

    /* ═══════════════════════════════════
       HERO
       ═══════════════════════════════════ */
    .of-hero {
        position: relative;
        padding: 100px 0 80px;
        background: var(--of-hero);
        overflow: hidden;
    }

    <?php if (!empty($heroBg)): ?>
    .of-hero {
        background: linear-gradient(160deg, rgba(15,23,42,0.93), rgba(15,23,42,0.87)),
                    url('<?php echo asset($heroBg); ?>') center/cover no-repeat;
    }
    <?php endif; ?>

    .of-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 20% 50%, rgba(139,92,246,0.12) 0%, transparent 50%),
            radial-gradient(ellipse at 80% 20%, rgba(245,158,11,0.09) 0%, transparent 40%);
        pointer-events: none;
    }

    .of-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        pointer-events: none;
    }

    .of-hero-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
    }

    .of-floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
        animation: ofFloat 20s ease-in-out infinite;
    }

    .of-floating-orb.o1 { width: 380px; height: 380px; background: rgba(139,92,246,0.1); top: -15%; left: -5%; }
    .of-floating-orb.o2 { width: 280px; height: 280px; background: rgba(245,158,11,0.07); bottom: -15%; right: -5%; animation-delay: -10s; }

    @keyframes ofFloat {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33%      { transform: translate(25px, -15px) scale(1.05); }
        66%      { transform: translate(-15px, 10px) scale(0.95); }
    }

    .of-hero-content { position: relative; z-index: 2; }

    .of-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 28px;
        font-size: 0.82rem;
    }

    .of-breadcrumb a { color: var(--of-light); text-decoration: none; transition: color 0.2s ease; }
    .of-breadcrumb a:hover { color: #fff; }
    .of-breadcrumb .of-sep { color: rgba(255,255,255,0.2); }
    .of-breadcrumb .of-current { color: #c4b5fd; font-weight: 600; }

    .of-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        background: rgba(139,92,246,0.12);
        border: 1px solid rgba(139,92,246,0.25);
        border-radius: 100px;
        color: #c4b5fd;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .of-hero-badge .of-dot {
        width: 8px; height: 8px;
        background: var(--of-violet);
        border-radius: 50%;
        animation: ofPulse 2s ease-in-out infinite;
    }

    @keyframes ofPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.4); }
    }

    .of-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.2rem, 5vw, 3.8rem);
        font-weight: 900;
        color: #ffffff;
        line-height: 1.1;
        margin-bottom: 16px;
    }

    .of-hero-title span {
        background: linear-gradient(135deg, var(--of-accent), #fbbf24);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .of-hero-desc {
        font-size: 1.1rem;
        color: #94a3b8;
        max-width: 600px;
        line-height: 1.7;
        margin-bottom: 28px;
    }

    /* Hero mini pyramid preview */
    .of-hero-pyramid {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: var(--of-radius-lg);
        padding: 28px;
        backdrop-filter: blur(20px);
        text-align: center;
    }

    .of-hero-pyramid h5 {
        color: #e2e8f0;
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .of-hero-pyramid h5 i { color: var(--of-accent); }

    .of-pyramid-mini {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .of-pyramid-mini-tier {
        display: flex;
        justify-content: center;
        gap: 6px;
    }

    .of-pyramid-mini-dot {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        color: #fff;
        transition: transform 0.2s ease;
    }

    .of-pyramid-mini-dot:hover { transform: scale(1.2); }

    .of-pyramid-mini-line {
        width: 2px;
        height: 10px;
        background: rgba(255,255,255,0.12);
    }

    .of-pyramid-mini-connector {
        height: 2px;
        background: rgba(255,255,255,0.08);
        border-radius: 1px;
    }

    .of-hero-tier-count {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
        justify-content: center;
    }

    .of-tier-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 100px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .of-tier-pill i { font-size: 0.75rem; }

    /* ═══════════════════════════════════
       ORG CHART SECTION
       ═══════════════════════════════════ */
    .of-org-section {
        padding: 100px 0 40px;
    }

    .of-section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .of-section-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: var(--of-primary-light);
        color: var(--of-primary);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        border-radius: 100px;
        margin-bottom: 16px;
    }

    .of-section-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 800;
        color: var(--of-text);
        margin-bottom: 12px;
        line-height: 1.15;
    }

    .of-section-subtitle {
        font-size: 1rem;
        color: var(--of-muted);
        max-width: 560px;
        margin: 0 auto;
        line-height: 1.7;
    }

    /* Pyramid */
    .of-pyramid {
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
    }

    .of-pyramid-tier {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 20px;
        width: 100%;
    }

    .of-connector {
        display: flex;
        justify-content: center;
        width: 100%;
        height: 32px;
        position: relative;
    }

    .of-connector::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        width: 2px;
        height: 100%;
        background: var(--of-border);
    }

    .of-connector::after {
        content: '';
        position: absolute;
        bottom: 0;
        height: 2px;
        background: var(--of-border);
    }

    .of-connector.cn-1::after { left: 30%; right: 30%; }
    .of-connector.cn-2::after { left: 12%; right: 12%; }
    .of-connector.cn-3::after { left: 28%; right: 28%; }

    /* ── Tier Header (above cards) ── */
    .of-tier-label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--of-border);
        width: 100%;
        max-width: 1100px;
    }

    .of-tier-label-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .of-tier-label h6 {
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--of-text);
        margin: 0;
    }

    .of-tier-label small {
        font-size: 0.72rem;
        color: var(--of-muted);
    }

    /* ── Official Card ── */
    .of-card {
        background: var(--of-card);
        border: 1px solid var(--of-border);
        border-radius: var(--of-radius-lg);
        box-shadow: var(--of-shadow-sm);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .of-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        transition: opacity 0.3s ease;
        opacity: 0;
    }

    .of-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--of-shadow-lg);
    }

    .of-card:hover::before { opacity: 1; }

    .of-card-top {
        padding: 28px 22px 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .of-card-photo {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        overflow: hidden;
        margin-bottom: 14px;
        border: 3px solid var(--of-border);
        background: var(--of-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .of-card:hover .of-card-photo {
        border-color: var(--of-primary);
        box-shadow: 0 0 0 4px rgba(26,86,219,0.1);
    }

    .of-card-photo img { width: 100%; height: 100%; object-fit: cover; }

    .of-card-photo .of-photo-fallback {
        font-size: 2rem;
        color: var(--of-light);
    }

    .of-card-name {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--of-text);
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .of-card-position {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 2px;
    }

    .of-card-sublabel {
        font-size: 0.7rem;
        color: var(--of-muted);
        margin-bottom: 0;
    }

    .of-card-body {
        padding: 16px 22px;
    }

    .of-card-detail {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 8px 0;
        font-size: 0.82rem;
        color: var(--of-muted);
        line-height: 1.5;
    }

    .of-card-detail i {
        font-size: 0.85rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .of-card-detail a {
        color: var(--of-primary);
        text-decoration: none;
    }

    .of-card-detail a:hover { text-decoration: underline; }

    .of-card-expand {
        padding: 0 22px 22px;
        display: none;
    }

    .of-card-expand.show { display: block; }

    .of-card-bio {
        font-size: 0.85rem;
        color: var(--of-muted);
        line-height: 1.65;
        padding: 12px;
        background: rgba(0,0,0,0.02);
        border-radius: var(--of-radius);
        border: 1px solid var(--of-border);
    }

    .of-card-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-top: 1px solid var(--of-border);
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--of-muted);
        cursor: pointer;
        transition: all 0.2s ease;
        gap: 6px;
        background: transparent;
        border-left: none;
        border-right: none;
        border-bottom: none;
        width: 100%;
        font-family: 'Inter', sans-serif;
    }

    .of-card-toggle:hover {
        color: var(--of-primary);
        background: rgba(26,86,219,0.04);
    }

    .of-card-toggle i {
        font-size: 0.85rem;
        transition: transform 0.3s ease;
    }

    .of-card-toggle.expanded i { transform: rotate(180deg); }

    /* Captain Card Override */
    .of-tier-captain .of-card {
        border-color: rgba(242,181,68,0.25);
    }

    .of-tier-captain .of-card::before {
        background: linear-gradient(135deg, var(--of-accent), #fbbf24);
        opacity: 1;
    }

    .of-tier-captain .of-card:hover {
        border-color: var(--of-accent);
        box-shadow: 0 14px 44px rgba(245,158,11,0.12);
    }

    .of-tier-captain .of-card-top { padding: 32px 28px 0; }
    .of-tier-captain .of-card-photo { width: 110px; height: 110px; border-color: var(--of-accent); }
    .of-tier-captain .of-card-name { font-size: 1.15rem; }
    .of-tier-captain .of-card-position { font-size: 0.85rem; color: var(--of-accent-dark); }
    .of-tier-captain .of-card-body { padding: 16px 28px; }

    .of-crown {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 32px;
        height: 32px;
        background: var(--of-accent);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.85rem;
        box-shadow: 0 2px 10px rgba(245,158,11,0.4);
        z-index: 2;
    }

    /* Executive Override */
    .of-tier-executive .of-card { min-width: 240px; max-width: 280px; }
    .of-tier-executive .of-card-photo { width: 90px; height: 90px; }

    /* Kagawad Override */
    .of-tier-kagawad .of-card {
        min-width: 0;
        flex: 1;
        max-width: 160px;
    }

    .of-tier-kagawad .of-card-top { padding: 22px 14px 0; }
    .of-tier-kagawad .of-card-photo { width: 70px; height: 70px; margin-bottom: 10px; }
    .of-tier-kagawad .of-card-name { font-size: 0.82rem; }
    .of-tier-kagawad .of-card-position { font-size: 0.65rem; }
    .of-tier-kagawad .of-card-body { padding: 10px 14px; }
    .of-tier-kagawad .of-card-detail { font-size: 0.75rem; padding: 5px 0; }

    /* SK Override */
    .of-tier-sk .of-card { min-width: 200px; max-width: 240px; }
    .of-tier-sk .of-card-photo { width: 78px; height: 78px; }

    /* ═══════════════════════════════════
       DIRECTORY TABLE
       ═══════════════════════════════════ */
    .of-directory-section {
        padding: 60px 0 100px;
        background: linear-gradient(180deg, var(--of-bg) 0%, #e8edf4 100%);
    }

    .of-directory-card {
        background: var(--of-card);
        border: 1px solid var(--of-border);
        border-radius: var(--of-radius-lg);
        box-shadow: var(--of-shadow-md);
        overflow: hidden;
    }

    .of-directory-header {
        padding: 24px 28px;
        border-bottom: 1px solid var(--of-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .of-directory-header h5 {
        font-weight: 700;
        font-size: 1rem;
        color: var(--of-text);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .of-directory-header h5 i { color: var(--of-primary); }

    .of-directory-search {
        position: relative;
        width: 260px;
    }

    .of-directory-search input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--of-border);
        border-radius: 10px;
        font-size: 0.85rem;
        color: var(--of-text);
        font-family: 'Inter', sans-serif;
        transition: all 0.2s ease;
    }

    .of-directory-search input::placeholder { color: var(--of-light); }

    .of-directory-search input:focus {
        outline: none;
        border-color: var(--of-primary);
        box-shadow: 0 0 0 3px rgba(26,86,219,0.1);
        background: #fff;
    }

    .of-directory-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--of-light);
        font-size: 0.85rem;
    }

    .of-directory-table {
        width: 100%;
        border-collapse: collapse;
    }

    .of-directory-table thead th {
        padding: 14px 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--of-muted);
        background: rgba(0,0,0,0.02);
        border-bottom: 1px solid var(--of-border);
        text-align: left;
    }

    .of-directory-table tbody tr {
        transition: background 0.2s ease;
    }

    .of-directory-table tbody tr:hover {
        background: rgba(26,86,219,0.03);
    }

    .of-directory-table tbody td {
        padding: 14px 20px;
        font-size: 0.88rem;
        color: var(--of-text);
        border-bottom: 1px solid var(--of-border);
        vertical-align: middle;
    }

    .of-directory-table tbody tr:last-child td { border-bottom: none; }

    .of-dir-person {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .of-dir-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        overflow: hidden;
        background: var(--of-bg);
        border: 2px solid var(--of-border);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .of-dir-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .of-dir-avatar i { font-size: 1rem; color: var(--of-light); }

    .of-dir-name { font-weight: 600; font-size: 0.88rem; }
    .of-dir-sub { font-size: 0.75rem; color: var(--of-muted); }

    .of-dir-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 100px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .of-dir-badge i { font-size: 0.7rem; }

    .of-dir-contact {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        color: var(--of-muted);
    }

    .of-dir-contact i { color: var(--of-light); }

    /* ═══════════════════════════════════
       SCROLL REVEAL
       ═══════════════════════════════════ */
    .of-reveal {
        opacity: 0;
        transform: translateY(35px);
        transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .of-reveal.of-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .of-d1 { transition-delay: 0.05s; }
    .of-d2 { transition-delay: 0.1s; }
    .of-d3 { transition-delay: 0.15s; }
    .of-d4 { transition-delay: 0.2s; }
    .of-d5 { transition-delay: 0.25s; }
    .of-d6 { transition-delay: 0.3s; }
    .of-d7 { transition-delay: 0.35s; }
    .of-d8 { transition-delay: 0.4s; }

    /* ═══════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════ */
    @media (max-width: 1199.98px) {
        .of-tier-kagawad .of-card { max-width: 140px; }
        .of-tier-kagawad .of-card-photo { width: 60px; height: 60px; }
        .of-tier-kagawad .of-card-name { font-size: 0.78rem; }
    }

    @media (max-width: 991.98px) {
        .of-hero { padding: 80px 0 60px; }
        .of-pyramid-tier { flex-wrap: wrap; gap: 14px; }
        .of-tier-kagawad { gap: 10px !important; }
        .of-tier-kagawad .of-card { max-width: calc(25% - 10px); flex: 1 1 calc(25% - 10px); min-width: 110px; }
        .of-org-section { padding: 60px 0 20px; }
        .of-directory-search { width: 200px; }
    }

    @media (max-width: 767.98px) {
        .of-hero { padding: 70px 0 50px; }
        .of-hero-pyramid { margin-top: 24px; }
        .of-org-section { padding: 50px 0; }

        .of-pyramid-tier {
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .of-pyramid-tier.of-tier-executive {
            flex-direction: row;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .of-pyramid-tier.of-tier-kagawad {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            max-width: 340px;
            width: 100%;
        }

        .of-tier-kagawad .of-card { max-width: 100%; min-width: 0; width: 100%; }
        .of-tier-kagawad .of-card-photo { width: 60px; height: 60px; }

        .of-tier-captain .of-card-photo { width: 90px; height: 90px; }
        .of-tier-captain .of-card-top { padding: 26px 20px 0; }
        .of-card-top { padding: 22px 16px 0; }
        .of-card-photo { width: 78px; height: 78px; }

        .of-connector { display: none; }
        .of-tier-label { margin-bottom: 14px; }
        .of-section-header { margin-bottom: 40px; }

        /* Directory table scroll */
        .of-directory-card { overflow-x: auto; }
        .of-directory-table { min-width: 600px; }
        .of-directory-header { flex-direction: column; align-items: stretch; }
        .of-directory-search { width: 100%; }
    }

    @media (max-width: 480px) {
        .of-tier-executive { flex-direction: column !important; align-items: center; }
        .of-tier-executive .of-card { min-width: 220px; max-width: 100%; }
        .of-tier-sk { flex-direction: column !important; align-items: center; }
        .of-tier-sk .of-card { min-width: 200px; max-width: 100%; }
    }
</style>

<!-- ═══════════════════════════════════
     HERO
     ═══════════════════════════════════ -->
<section class="of-hero">
    <div class="of-hero-grid"></div>
    <div class="of-floating-orb o1"></div>
    <div class="of-floating-orb o2"></div>

    <div class="container of-hero-content">
        <div class="of-breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house"></i> Home</a>
            <span class="of-sep">/</span>
            <span class="of-current">Officials</span>
        </div>

        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="of-hero-badge">
                    <span class="of-dot"></span>
                    Barangay Leadership
                </div>
                <h1 class="of-hero-title">Your <span>Officials</span></h1>
                <p class="of-hero-desc">
                    Meet the dedicated public servants of <?php echo e($barangayName); ?> — committed to transparent governance, community service, and building a better barangay for every family.
                </p>

                <div class="of-hero-tier-count">
                    <?php foreach ($tierOrder as $tier):
                        $count = count($officialsByTier[$tier]);
                        if ($count === 0) continue;
                        $tc = $tierConfig[$tier];
                    ?>
                        <div class="of-tier-pill" style="background:<?php echo $tc['colorBg']; ?>; color:<?php echo $tc['color']; ?>; border:1px solid <?php echo $tc['colorBdr']; ?>;">
                            <i class="bi <?php echo $tc['icon']; ?>"></i>
                            <?php echo $count; ?> <?php echo e($tc['sublabel']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="of-hero-pyramid">
                    <h5><i class="bi bi-diagram-3-fill"></i> Organizational Structure</h5>
                    <div class="of-pyramid-mini">
                        <!-- Captain -->
                        <div class="of-pyramid-mini-tier">
                            <div class="of-pyramid-mini-dot" style="background:<?php echo $tierConfig['captain']['gradient']; ?>; border-color:<?php echo $tierConfig['captain']['color']; ?>;">1</div>
                        </div>
                        <div class="of-pyramid-mini-line"></div>
                        <!-- Executive -->
                        <div class="of-pyramid-mini-tier">
                            <?php for ($i = 0; $i < count($officialsByTier['executive']); $i++): ?>
                                <div class="of-pyramid-mini-dot" style="background:<?php echo $tierConfig['executive']['gradient']; ?>; border-color:<?php echo $tierConfig['executive']['color']; ?>;">E</div>
                            <?php endfor; ?>
                        </div>
                        <div class="of-pyramid-mini-line"></div>
                        <!-- Kagawads -->
                        <div class="of-pyramid-mini-tier">
                            <?php for ($i = 0; $i < count($officialsByTier['kagawad']); $i++): ?>
                                <div class="of-pyramid-mini-dot" style="background:<?php echo $tierConfig['kagawad']['gradient']; ?>; border-color:<?php echo $tierConfig['kagawad']['color']; ?>;">K</div>
                            <?php endfor; ?>
                        </div>
                        <div class="of-pyramid-mini-line"></div>
                        <!-- SK -->
                        <div class="of-pyramid-mini-tier">
                            <?php for ($i = 0; $i < count($officialsByTier['sk']); $i++): ?>
                                <div class="of-pyramid-mini-dot" style="background:<?php echo $tierConfig['sk']['gradient']; ?>; border-color:<?php echo $tierConfig['sk']['color']; ?>;">S</div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div style="margin-top:16px; font-size:0.78rem; color:var(--of-light);">
                        <?php echo $totalOfficials; ?> Total Officials
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     ORG CHART PYRAMID
     ═══════════════════════════════════ -->
<section class="of-org-section" id="pyramid">
    <div class="container">
        <div class="of-section-header of-reveal">
            <div class="of-section-tag"><i class="bi bi-diagram-3"></i> Organization</div>
            <h2 class="of-section-title">Barangay Leadership Pyramid</h2>
            <p class="of-section-subtitle">
                The hierarchical structure of our barangay officials — from the Captain down to appointed officers.
            </p>
        </div>

        <div class="of-pyramid">

            <?php foreach ($tierOrder as $tierIdx => $tier):
                $officials = $officialsByTier[$tier];
                if (empty($officials)) continue;
                $tc = $tierConfig[$tier];
                $delayClass = 'of-d' . min($tierIdx + 1, 4);
                $isCaptain = ($tier === 'captain');
                $isKagawad = ($tier === 'kagawad');
                $tierClass = 'of-tier-' . $tier;
            ?>

                <!-- Tier Label -->
                <div class="of-tier-label of-reveal <?php echo $delayClass; ?>">
                    <div class="of-tier-label-icon" style="background:<?php echo $tc['colorBg']; ?>; color:<?php echo $tc['color']; ?>;">
                        <i class="bi <?php echo $tc['icon']; ?>"></i>
                    </div>
                    <div>
                        <h6><?php echo e($tc['label']); ?></h6>
                        <small><?php echo count($officials); ?> official<?php echo count($officials) !== 1 ? 's' : ''; ?> &bull; <?php echo e($tc['sublabel']); ?></small>
                    </div>
                </div>

                <!-- Cards -->
                <div class="of-pyramid-tier <?php echo $tierClass; ?> of-reveal <?php echo $delayClass; ?>">
                    <?php foreach ($officials as $offIdx => $off):
                        $cardDelay = 'of-d' . min($offIdx + 1, 8);
                    ?>
                        <div class="of-card of-reveal <?php echo $cardDelay; ?>" data-official-id="<?php echo $off['id']; ?>">
                            <div style="position:absolute; top:0; left:0; right:0; height:4px; background: <?php echo $tc['gradient']; ?>; opacity:0;"></div>

                            <?php if ($isCaptain): ?>
                                <div class="of-crown"><i class="bi bi-award-fill"></i></div>
                            <?php endif; ?>

                            <div class="of-card-top">
                                <div class="of-card-photo">
                                    <?php if (!empty($off['photo_path'])): ?>
                                        <img class="of-photo-img" src="<?php echo asset($off['photo_path']); ?>" alt="<?php echo e($off['official_name']); ?>">
                                        <div class="of-photo-fallback" style="display:none;"><i class="bi bi-person-circle"></i></div>
                                    <?php else: ?>
                                        <div class="of-photo-fallback"><i class="bi bi-person-circle"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="of-card-name"><?php echo e($off['official_name']); ?></div>
                                <div class="of-card-position" style="color:<?php echo $tc['color']; ?>;"><?php echo e($off['position_title']); ?></div>
                                <?php if (!empty($off['position_label'])): ?>
                                    <div class="of-card-sublabel"><?php echo e($off['position_label']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="of-card-body">
                                <?php if (!empty($off['committee'] ?? '')): ?>
                                    <div class="of-card-detail">
                                        <i class="bi bi-people" style="color:<?php echo $tc['color']; ?>;"></i>
                                        <span><?php echo e($off['committee']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($off['contact_number'])): ?>
                                    <div class="of-card-detail">
                                        <i class="bi bi-telephone" style="color:var(--of-green);"></i>
                                        <span><?php echo e($off['contact_number']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($off['email'])): ?>
                                    <div class="of-card-detail">
                                        <i class="bi bi-envelope" style="color:var(--of-primary);"></i>
                                        <a href="mailto:<?php echo e($off['email']); ?>"><?php echo e($off['email']); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($off['bio'])): ?>
                                <div class="of-card-expand" id="bio-<?php echo $off['id']; ?>">
                                    <div class="of-card-bio">
                                        <i class="bi bi-quote" style="color:<?php echo $tc['color']; ?>; margin-right:4px;"></i>
                                        <?php echo e($off['bio']); ?>
                                    </div>
                                </div>
                                <button class="of-card-toggle" onclick="toggleBio(this, <?php echo $off['id']; ?>)">
                                    <span>View Bio</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($tierIdx < count($tierOrder) - 1 && count($officialsByTier[$tierOrder[$tierIdx + 1]]) > 0): ?>
                    <div class="of-connector of-reveal <?php echo $delayClass; ?>"></div>
                <?php endif; ?>

            <?php endforeach; ?>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     DIRECTORY TABLE
     ═══════════════════════════════════ -->
<section class="of-directory-section" id="directory">
    <div class="container">
        <div class="of-section-header of-reveal">
            <div class="of-section-tag"><i class="bi bi-journal-text"></i> Directory</div>
            <h2 class="of-section-title">Officials Directory</h2>
            <p class="of-section-subtitle">
                Complete contact list of all barangay officials for quick reference.
            </p>
        </div>

        <div class="of-directory-card of-reveal of-d1">
            <div class="of-directory-header">
                <h5><i class="bi bi-list-columns"></i> All Officials</h5>
                <div class="of-directory-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="ofDirSearch" placeholder="Search officials…">
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="of-directory-table" id="ofDirTable">
                    <thead>
                        <tr>
                            <th>Official</th>
                            <th>Position</th>
                            <th>Tier</th>
                            <th>Committee</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tierOrder as $tier):
                            $tc = $tierConfig[$tier];
                            foreach ($officialsByTier[$tier] as $off):
                        ?>
                            <tr>
                                <td>
                                    <div class="of-dir-person">
                                        <div class="of-dir-avatar">
                                            <?php if (!empty($off['photo_path'])): ?>
                                                <img src="<?php echo asset($off['photo_path']); ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-person"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="of-dir-name"><?php echo e($off['official_name']); ?></div>
                                            <?php if (!empty($off['email'])): ?>
                                                <div class="of-dir-sub"><?php echo e($off['email']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:600;"><?php echo e($off['position_title']); ?></span>
                                    <?php if (!empty($off['position_label'])): ?>
                                        <br><span style="font-size:0.75rem; color:var(--of-muted);"><?php echo e($off['position_label']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="of-dir-badge" style="background:<?php echo $tc['colorBg']; ?>; color:<?php echo $tc['color']; ?>; border:1px solid <?php echo $tc['colorBdr']; ?>;">
                                        <i class="bi <?php echo $tc['icon']; ?>"></i>
                                        <?php echo e($tc['label']); ?>
                                    </span>
                                </td>
                                <td style="color:var(--of-muted); font-size:0.85rem;">
                                    <?php echo e($off['committee'] ?? '' ?: '—'); ?>
                                </td>
                                <td>
                                    <?php if (!empty($off['contact_number'])): ?>
                                        <div class="of-dir-contact">
                                            <i class="bi bi-telephone-fill"></i>
                                            <?php echo e($off['contact_number']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--of-light); font-size:0.82rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endforeach; ?>
                    </tbody>
                </table>
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
    var reveals = document.querySelectorAll('.of-reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('of-visible');
            }
        });
    }, { threshold: 0.06, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function(el) { obs.observe(el); });

    /* ── Image error fallback ── */
    document.addEventListener('error', function(e) {
        var t = e.target;
        if (t.tagName === 'IMG' && t.classList.contains('of-photo-img')) {
            t.style.display = 'none';
            var fb = t.nextElementSibling;
            if (fb && fb.classList.contains('of-photo-fallback')) fb.style.display = 'flex';
        }
    }, true);

    /* ── Directory Search ── */
    var searchInput = document.getElementById('ofDirSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#ofDirTable tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(query) !== -1 ? '' : 'none';
            });
        });
    }
});

/* ── Toggle Bio ── */
function toggleBio(btn, id) {
    var bioEl = document.getElementById('bio-' + id);
    if (!bioEl) return;

    var isShown = bioEl.classList.contains('show');
    bioEl.classList.toggle('show');
    btn.classList.toggle('expanded');

    var span = btn.querySelector('span');
    span.textContent = isShown ? 'View Bio' : 'Hide Bio';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>