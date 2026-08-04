<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Events - ' . APP_NAME;
$pageDescription = 'Upcoming events, community programs, and activities in Barangay Tumalaytay.';

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

$pdo->exec("CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL DEFAULT '',
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    location VARCHAR(500) DEFAULT '',
    event_type VARCHAR(50) DEFAULT 'general',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Seed events if empty ── */
try {
    $eventCount = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    if ($eventCount == 0) {
        $seed = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, event_type) VALUES (?, ?, ?, ?, ?, ?)");
        $evts = [
            ['Monthly Barangay Assembly', 'All residents are invited to attend the monthly assembly. Agenda includes Q3 budget review, infrastructure updates, and community program announcements.', '2025-07-15', '15:00', 'Barangay Hall', 'meeting'],
            ['Free Medical Mission', 'Partnership with the Municipal Health Office — free check-ups, blood pressure monitoring, blood sugar testing, and medicines. Bring PhilHealth or valid ID.', '2025-07-20', '08:00', 'Barangay Health Center', 'health'],
            ['Community Clean-Up Drive', 'Monthly clean-up drive. Assembly at Barangay Plaza. Gloves and garbage bags will be provided. Let\'s work together for a cleaner barangay!', '2025-07-05', '06:00', 'Barangay Plaza', 'community'],
            ['Scholarship Application Deadline', 'Last day to submit applications for the SK Educational Assistance Program for SY 2025-2026. Submit requirements at the Barangay Hall.', '2025-08-01', '17:00', 'Barangay Hall', 'education'],
            ['Basketball League Opening', 'Inter-purok basketball league opening ceremony and first games. All purok teams are expected to register by July 25.', '2025-08-05', '16:00', 'Barangay Covered Court', 'sports'],
            ['Livelihood Skills Training', 'Free livelihood skills training on food processing and handicraft making. Open to all residents, priority given to indigent families.', '2025-07-25', '09:00', 'Barangay Training Center', 'livelihood'],
            ['Feast Day Celebration', 'Annual barangay fiesta celebration with parade, cultural shows, and community feast. All residents and visitors are welcome.', '2025-08-15', '07:00', 'Barangay Plaza & Main Roads', 'celebration'],
            ['Disaster Preparedness Seminar', 'Earthquake and typhoon preparedness seminar by BDRRMC. Learn evacuation routes, emergency kits, and family disaster plans.', '2025-07-10', '14:00', 'Barangay Hall', 'emergency'],
        ];
        foreach ($evts as $e) { $seed->execute($e); }
    }
} catch (Throwable $e) {}

/* ── Seed announcements if empty ── */
try {
    $annCount = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
    if ($annCount == 0) {
        $seed = $pdo->prepare("INSERT INTO announcements (title, content, type, priority, is_pinned, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $anns = [
            ['Road Rehabilitation – Purok 3', 'Road work begins July 10, expected 2 weeks. Use alternate routes during 7AM–5PM.', 'infrastructure', 'normal', 0, '2025-06-25 09:00:00'],
            ['Typhoon Season Advisory', 'Prepare emergency kits, secure documents, identify evacuation routes. Emergency hotlines posted at the bulletin board.', 'emergency', 'high', 1, '2025-06-20 07:00:00'],
            ['Free Vaccination for Senior Citizens', 'Pneumonia and flu vaccines available for residents aged 60+. Bring valid ID and vaccination card.', 'health', 'normal', 0, '2025-06-28 10:00:00'],
            ['SK Youth Leadership Summit', 'A 2-day youth leadership training for ages 15–30. Register at the SK Hall on or before July 30.', 'event', 'normal', 0, '2025-06-22 14:00:00'],
            ['Water Interruption Notice', 'Scheduled water service interruption on July 8, 2025 from 10PM to 5AM for pipeline maintenance.', 'infrastructure', 'high', 0, '2025-07-01 08:00:00'],
        ];
        foreach ($anns as $a) { $seed->execute($a); }
    }
} catch (Throwable $e) {}

/* ── Fetch data ── */
$today = date('Y-m-d');

$upcomingEvents = [];
$pastEvents = [];
$recentAnnouncements = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_date >= ? AND is_active = 1 ORDER BY event_date ASC, event_time ASC LIMIT 10");
    $stmt->execute([$today]);
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_date < ? AND is_active = 1 ORDER BY event_date DESC LIMIT 5");
    $stmt->execute([$today]);
    $pastEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recentAnnouncements = $pdo->query("SELECT * FROM announcements WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY is_pinned DESC, created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $upcomingEvents = [];
    $pastEvents = [];
    $recentAnnouncements = [];
}

$barangayName = getSetting('barangay_name', 'Barangay Tumalaytay');
$heroBg = getSetting('hero_background', '');

/* ── Event type config ── */
$eventTypeConfig = [
    'meeting'      => ['label' => 'Meeting',      'icon' => 'bi-people-fill',            'color' => '#2f7bff', 'bg' => 'rgba(47,123,255,0.1)',  'border' => 'rgba(47,123,255,0.25)'],
    'health'       => ['label' => 'Health',        'icon' => 'bi-heart-pulse',            'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)', 'border' => 'rgba(16,185,129,0.25)'],
    'community'    => ['label' => 'Community',     'icon' => 'bi-tree-fill',              'color' => '#14b8a6', 'bg' => 'rgba(20,184,166,0.1)', 'border' => 'rgba(20,184,166,0.25)'],
    'education'    => ['label' => 'Education',     'icon' => 'bi-mortarboard-fill',       'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.1)', 'border' => 'rgba(139,92,246,0.25)'],
    'sports'       => ['label' => 'Sports',        'icon' => 'bi-trophy-fill',            'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)', 'border' => 'rgba(245,158,11,0.25)'],
    'livelihood'   => ['label' => 'Livelihood',    'icon' => 'bi-briefcase-fill',         'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.1)', 'border' => 'rgba(99,102,241,0.25)'],
    'celebration'  => ['label' => 'Celebration',   'icon' => 'bi-balloon-fill',           'color' => '#f43f5e', 'bg' => 'rgba(244,63,94,0.1)',  'border' => 'rgba(244,63,94,0.25)'],
    'emergency'    => ['label' => 'Emergency',     'icon' => 'bi-exclamation-triangle-fill','color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)', 'border' => 'rgba(239,68,68,0.25)'],
    'general'      => ['label' => 'General',       'icon' => 'bi-calendar-event',         'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)','border' => 'rgba(100,116,139,0.25)'],
];

$annTypeConfig = [
    'general'        => ['label' => 'General',        'icon' => 'bi-megaphone',          'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.12)', 'border' => 'rgba(99,102,241,0.25)'],
    'event'          => ['label' => 'Event',          'icon' => 'bi-calendar-event',     'color' => '#2f7bff', 'bg' => 'rgba(26,86,219,0.12)', 'border' => 'rgba(26,86,219,0.25)'],
    'health'         => ['label' => 'Health',         'icon' => 'bi-heart-pulse',        'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)', 'border' => 'rgba(16,185,129,0.25)'],
    'emergency'      => ['label' => 'Emergency',      'icon' => 'bi-exclamation-triangle','color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.12)', 'border' => 'rgba(239,68,68,0.25)'],
    'infrastructure' => ['label' => 'Infrastructure', 'icon' => 'bi-building',           'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)', 'border' => 'rgba(245,158,11,0.25)'],
    'program'        => ['label' => 'Program',        'icon' => 'bi-gift',               'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.12)', 'border' => 'rgba(139,92,246,0.25)'],
];

function getEvtInfo($type) {
    global $eventTypeConfig;
    return $eventTypeConfig[$type] ?? $eventTypeConfig['general'];
}

function getAnnInfo($type) {
    global $annTypeConfig;
    return $annTypeConfig[$type] ?? $annTypeConfig['general'];
}

function relativeTime($datetime) {
    $now = time();
    $ts = strtotime($datetime);
    $diff = $now - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    return date('M d, Y', $ts);
}

function daysUntil($date) {
    $now = new DateTime('today');
    $target = new DateTime($date);
    $diff = $now->diff($target)->days;
    $isPast = $target < $now;
    if ($diff === 0) return ['text' => 'Today', 'class' => 'today'];
    if ($diff === 1 && !$isPast) return ['text' => 'Tomorrow', 'class' => 'soon'];
    if ($diff <= 7 && !$isPast) return ['text' => 'In ' . $diff . ' days', 'class' => 'soon'];
    if (!$isPast) return ['text' => 'In ' . $diff . ' days', 'class' => 'future'];
    return ['text' => $diff . ' day' . ($diff > 1 ? 's' : '') . ' ago', 'class' => 'past'];
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --ev-primary: #1a56db;
        --ev-primary-dark: #1042a3;
        --ev-primary-light: #e8effc;
        --ev-accent: #f59e0b;
        --ev-accent-dark: #d97706;
        --ev-green: #10b981;
        --ev-red: #ef4444;
        --ev-violet: #8b5cf6;
        --ev-bg: #f0f4f8;
        --ev-card: #ffffff;
        --ev-hero: #0f172a;
        --ev-text: #0f172a;
        --ev-muted: #64748b;
        --ev-light: #94a3b8;
        --ev-border: #e2e8f0;
        --ev-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --ev-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        --ev-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
        --ev-shadow-xl: 0 20px 60px rgba(0,0,0,0.15);
        --ev-radius: 12px;
        --ev-radius-lg: 20px;
        --ev-radius-xl: 28px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--ev-bg);
        color: var(--ev-text);
        overflow-x: hidden;
    }

    /* ═══════════════════════════════════
       HERO
       ═══════════════════════════════════ */
    .ev-hero {
        position: relative;
        padding: 100px 0 80px;
        background: var(--ev-hero);
        overflow: hidden;
    }

    <?php if (!empty($heroBg)): ?>
    .ev-hero {
        background: linear-gradient(160deg, rgba(15,23,42,0.93), rgba(15,23,42,0.87)),
                    url('<?php echo asset($heroBg); ?>') center/cover no-repeat;
    }
    <?php endif; ?>

    .ev-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 25% 50%, rgba(245,158,11,0.12) 0%, transparent 50%),
            radial-gradient(ellipse at 75% 20%, rgba(26,86,219,0.09) 0%, transparent 40%);
        pointer-events: none;
    }

    .ev-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        pointer-events: none;
    }

    .ev-hero-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
    }

    .ev-floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
        animation: evFloat 20s ease-in-out infinite;
    }

    .ev-floating-orb.o1 { width: 380px; height: 380px; background: rgba(245,158,11,0.1); top: -15%; left: -5%; }
    .ev-floating-orb.o2 { width: 280px; height: 280px; background: rgba(26,86,219,0.07); bottom: -15%; right: -5%; animation-delay: -10s; }
    .ev-floating-orb.o3 { width: 180px; height: 180px; background: rgba(244,63,94,0.06); top: 40%; left: 55%; animation-delay: -5s; }

    @keyframes evFloat {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33%      { transform: translate(25px, -15px) scale(1.05); }
        66%      { transform: translate(-15px, 10px) scale(0.95); }
    }

    .ev-hero-content { position: relative; z-index: 2; }

    .ev-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 28px;
        font-size: 0.82rem;
    }

    .ev-breadcrumb a { color: var(--ev-light); text-decoration: none; transition: color 0.2s ease; }
    .ev-breadcrumb a:hover { color: #fff; }
    .ev-breadcrumb .ev-sep { color: rgba(255,255,255,0.2); }
    .ev-breadcrumb .ev-current { color: #fcd34d; font-weight: 600; }

    .ev-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.25);
        border-radius: 100px;
        color: #fcd34d;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .ev-hero-badge .ev-dot {
        width: 8px; height: 8px;
        background: var(--ev-accent);
        border-radius: 50%;
        animation: evPulse 2s ease-in-out infinite;
    }

    @keyframes evPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.4); }
    }

    .ev-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.2rem, 5vw, 3.8rem);
        font-weight: 900;
        color: #ffffff;
        line-height: 1.1;
        margin-bottom: 16px;
    }

    .ev-hero-title span {
        background: linear-gradient(135deg, var(--ev-accent), #fbbf24);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .ev-hero-desc {
        font-size: 1.1rem;
        color: #94a3b8;
        max-width: 580px;
        line-height: 1.7;
        margin-bottom: 28px;
    }

    /* Hero stats */
    .ev-hero-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .ev-hero-pill {
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

    .ev-hero-pill:hover {
        background: rgba(255,255,255,0.08);
        border-color: rgba(255,255,255,0.15);
    }

    .ev-hero-pill .ev-pill-num {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.2rem;
        color: #fff;
    }

    .ev-hero-pill .ev-pill-lbl {
        font-size: 0.78rem;
        color: var(--ev-light);
        font-weight: 500;
    }

    /* Hero mini calendar */
    .ev-hero-calendar {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: var(--ev-radius-lg);
        padding: 28px;
        backdrop-filter: blur(20px);
    }

    .ev-hero-calendar h5 {
        color: #e2e8f0;
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ev-hero-calendar h5 i { color: var(--ev-accent); }

    .ev-cal-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        transition: all 0.2s ease;
    }

    .ev-cal-item:last-child { border-bottom: none; }
    .ev-cal-item:hover { transform: translateX(4px); }

    .ev-cal-date-box {
        width: 44px;
        min-width: 44px;
        text-align: center;
        border-radius: 10px;
        padding: 6px 4px;
        background: rgba(245,158,11,0.12);
        border: 1px solid rgba(245,158,11,0.2);
    }

    .ev-cal-date-box .ev-cal-month {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ev-accent);
    }

    .ev-cal-date-box .ev-cal-day {
        font-family: 'Playfair Display', serif;
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.2;
    }

    .ev-cal-info h6 {
        color: #e2e8f0;
        font-weight: 600;
        font-size: 0.82rem;
        margin-bottom: 2px;
        line-height: 1.3;
    }

    .ev-cal-info p {
        color: var(--ev-light);
        font-size: 0.72rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* ═══════════════════════════════════
       SECTION HEADERS
       ═══════════════════════════════════ */
    .ev-section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .ev-section-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: var(--ev-primary-light);
        color: var(--ev-primary);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        border-radius: 100px;
        margin-bottom: 16px;
    }

    .ev-section-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 800;
        color: var(--ev-text);
        margin-bottom: 12px;
        line-height: 1.15;
    }

    .ev-section-subtitle {
        font-size: 1rem;
        color: var(--ev-muted);
        max-width: 560px;
        margin: 0 auto;
        line-height: 1.7;
    }

    /* ═══════════════════════════════════
       EVENTS TIMELINE
       ═══════════════════════════════════ */
    .ev-events-section {
        padding: 100px 0 40px;
    }

    .ev-timeline {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
    }

    .ev-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 28px;
        width: 2px;
        background: var(--ev-border);
    }

    .ev-timeline-item {
        position: relative;
        padding-left: 72px;
        padding-bottom: 32px;
    }

    .ev-timeline-item:last-child { padding-bottom: 0; }

    .ev-timeline-dot {
        position: absolute;
        left: 18px;
        top: 4px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 3px solid;
        background: var(--ev-card);
        z-index: 2;
        transition: all 0.3s ease;
    }

    .ev-timeline-item:hover .ev-timeline-dot {
        transform: scale(1.3);
    }

    .ev-timeline-date {
        position: absolute;
        left: -72px;
        top: 0;
        width: 56px;
        text-align: center;
    }

    .ev-tl-month {
        font-size: 0.62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ev-light);
    }

    .ev-tl-day {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--ev-text);
        line-height: 1.1;
    }

    .ev-tl-weekday {
        font-size: 0.65rem;
        font-weight: 600;
        color: var(--ev-muted);
        text-transform: uppercase;
    }

    /* Timeline card */
    .ev-timeline-card {
        background: var(--ev-card);
        border: 1px solid var(--ev-border);
        border-radius: var(--ev-radius-lg);
        box-shadow: var(--ev-shadow-sm);
        padding: 0;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        overflow: hidden;
        position: relative;
    }

    .ev-timeline-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        opacity: 0.85;
    }

    .ev-timeline-card:hover {
        transform: translateX(6px);
        box-shadow: var(--ev-shadow-lg);
    }

    .ev-tl-header {
        padding: 22px 24px 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .ev-tl-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .ev-tl-type-badge i { font-size: 0.75rem; }

    .ev-tl-countdown {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .ev-tl-countdown.today {
        background: rgba(16,185,129,0.12);
        color: #059669;
        border: 1px solid rgba(16,185,129,0.25);
    }

    .ev-tl-countdown.soon {
        background: rgba(245,158,11,0.12);
        color: var(--ev-accent-dark);
        border: 1px solid rgba(245,158,11,0.25);
    }

    .ev-tl-countdown.future {
        background: rgba(47,123,255,0.08);
        color: var(--ev-primary);
        border: 1px solid rgba(47,123,255,0.15);
    }

    .ev-tl-countdown i { font-size: 0.7rem; }

    .ev-tl-body {
        padding: 14px 24px 0;
    }

    .ev-tl-body h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--ev-text);
        margin-bottom: 8px;
        line-height: 1.35;
    }

    .ev-tl-body p {
        font-size: 0.9rem;
        color: var(--ev-muted);
        line-height: 1.65;
        margin: 0;
    }

    .ev-tl-footer {
        padding: 14px 24px;
        margin-top: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        border-top: 1px solid var(--ev-border);
    }

    .ev-tl-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: var(--ev-muted);
    }

    .ev-tl-meta i { font-size: 0.85rem; color: var(--ev-light); }

    /* ═══════════════════════════════════
       PAST EVENTS
       ═══════════════════════════════════ */
    .ev-past-section {
        padding: 20px 0 40px;
    }

    .ev-past-card {
        background: rgba(0,0,0,0.02);
        border: 1px solid var(--ev-border);
        border-radius: var(--ev-radius);
        padding: 18px 20px;
        transition: all 0.3s ease;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .ev-past-card:hover {
        background: rgba(26,86,219,0.03);
        transform: translateX(4px);
    }

    .ev-past-date {
        text-align: center;
        min-width: 46px;
        padding: 6px 4px;
        border-radius: 8px;
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--ev-border);
    }

    .ev-past-date .ev-pd-month {
        font-size: 0.58rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ev-light);
    }

    .ev-past-date .ev-pd-day {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--ev-muted);
        line-height: 1.1;
    }

    .ev-past-info h5 {
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--ev-text);
        margin-bottom: 4px;
    }

    .ev-past-info p {
        font-size: 0.82rem;
        color: var(--ev-muted);
        margin: 0;
        line-height: 1.5;
    }

    /* ═══════════════════════════════════
       ANNOUNCEMENTS
       ═══════════════════════════════════ */
    .ev-announcements-section {
        padding: 60px 0 100px;
        background: linear-gradient(180deg, var(--ev-bg) 0%, #e8edf4 100%);
    }

    .ev-ann-card {
        background: var(--ev-card);
        border: 1px solid var(--ev-border);
        border-radius: var(--ev-radius-lg);
        box-shadow: var(--ev-shadow-sm);
        padding: 0;
        height: 100%;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .ev-ann-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        opacity: 0.85;
    }

    .ev-ann-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--ev-shadow-lg);
    }

    .ev-ann-card.pinned {
        border-color: rgba(245,158,11,0.2);
    }

    .ev-ann-card.pinned::before {
        background: linear-gradient(90deg, var(--ev-accent), #fbbf24);
        opacity: 1;
    }

    .ev-ann-header {
        padding: 20px 22px 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .ev-ann-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 100px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .ev-ann-type-badge i { font-size: 0.7rem; }

    .ev-ann-pinned {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        background: rgba(245,158,11,0.1);
        border: 1px solid rgba(245,158,11,0.25);
        border-radius: 100px;
        color: var(--ev-accent-dark);
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .ev-ann-pinned i { font-size: 0.6rem; }

    .ev-ann-body {
        padding: 12px 22px 0;
        flex: 1;
    }

    .ev-ann-body h4 {
        font-weight: 700;
        font-size: 0.98rem;
        color: var(--ev-text);
        margin-bottom: 8px;
        line-height: 1.35;
    }

    .ev-ann-body p {
        font-size: 0.85rem;
        color: var(--ev-muted);
        line-height: 1.65;
        margin: 0;
    }

    .ev-ann-footer {
        padding: 14px 22px;
        margin-top: auto;
        border-top: 1px solid var(--ev-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ev-ann-date {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.78rem;
        color: var(--ev-light);
    }

    .ev-ann-date i { font-size: 0.82rem; }

    .ev-ann-ago {
        font-size: 0.75rem;
        color: var(--ev-light);
        font-weight: 500;
    }

    /* Priority dot */
    .ev-priority-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }

    .ev-priority-dot.high {
        background: var(--ev-red);
        animation: evPriorityPulse 1.5s ease-in-out infinite;
    }

    .ev-priority-dot.normal { background: var(--ev-green); }

    @keyframes evPriorityPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50% { box-shadow: 0 0 0 5px rgba(239,68,68,0); }
    }

    /* ═══════════════════════════════════
       CTA
       ═══════════════════════════════════ */
    .ev-cta-section {
        padding: 0 0 100px;
    }

    .ev-cta-card {
        background: linear-gradient(135deg, var(--ev-hero) 0%, #1e293b 100%);
        border-radius: var(--ev-radius-xl);
        padding: 60px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .ev-cta-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse at 30% 50%, rgba(245,158,11,0.12) 0%, transparent 50%),
            radial-gradient(ellipse at 70% 30%, rgba(26,86,219,0.08) 0%, transparent 40%);
        pointer-events: none;
    }

    .ev-cta-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
        background-size: 50px 50px;
        pointer-events: none;
    }

    .ev-cta-inner { position: relative; z-index: 2; }

    .ev-cta-inner h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.6rem, 3vw, 2.4rem);
        font-weight: 800;
        color: #fff;
        margin-bottom: 14px;
    }

    .ev-cta-inner p {
        font-size: 1rem;
        color: #94a3b8;
        max-width: 480px;
        margin: 0 auto 28px;
        line-height: 1.7;
    }

    .ev-cta-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 14px;
    }

    .ev-btn-cta-primary {
        padding: 15px 36px;
        background: linear-gradient(135deg, var(--ev-accent), #fbbf24);
        color: var(--ev-hero);
        font-weight: 700;
        font-size: 0.95rem;
        border: none;
        border-radius: var(--ev-radius);
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(245,158,11,0.3);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .ev-btn-cta-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(245,158,11,0.45);
        color: var(--ev-hero);
    }

    .ev-btn-cta-secondary {
        padding: 15px 36px;
        background: transparent;
        color: #e2e8f0;
        font-weight: 600;
        font-size: 0.95rem;
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: var(--ev-radius);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .ev-btn-cta-secondary:hover {
        background: rgba(255,255,255,0.06);
        border-color: rgba(255,255,255,0.3);
        color: #fff;
        transform: translateY(-3px);
    }

    /* ═══════════════════════════════════
       EMPTY STATE
       ═══════════════════════════════════ */
    .ev-empty {
        text-align: center;
        padding: 50px 20px;
    }

    .ev-empty-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: rgba(26,86,219,0.08);
        border: 1px solid rgba(26,86,219,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 1.8rem;
        color: var(--ev-primary);
        opacity: 0.6;
    }

    .ev-empty h4 {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        font-size: 1.15rem;
        color: var(--ev-text);
        margin-bottom: 6px;
    }

    .ev-empty p {
        font-size: 0.9rem;
        color: var(--ev-muted);
        max-width: 360px;
        margin: 0 auto;
    }

    /* ═══════════════════════════════════
       SCROLL REVEAL
       ═══════════════════════════════════ */
    .ev-reveal {
        opacity: 0;
        transform: translateY(35px);
        transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .ev-reveal.ev-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .ev-d1 { transition-delay: 0.05s; }
    .ev-d2 { transition-delay: 0.1s; }
    .ev-d3 { transition-delay: 0.15s; }
    .ev-d4 { transition-delay: 0.2s; }
    .ev-d5 { transition-delay: 0.25s; }
    .ev-d6 { transition-delay: 0.3s; }
    .ev-d7 { transition-delay: 0.35s; }
    .ev-d8 { transition-delay: 0.4s; }

    /* ═══════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════ */
    @media (max-width: 991.98px) {
        .ev-hero { padding: 80px 0 60px; }
        .ev-hero-calendar { margin-top: 24px; }
        .ev-events-section { padding: 60px 0 20px; }
    }

    @media (max-width: 767.98px) {
        .ev-hero { padding: 70px 0 50px; }
        .ev-hero-pills { gap: 8px; }
        .ev-hero-pill { padding: 8px 14px; }
        .ev-hero-pill .ev-pill-num { font-size: 1rem; }

        /* Timeline mobile */
        .ev-timeline::before { left: 16px; }
        .ev-timeline-item { padding-left: 50px; }
        .ev-timeline-dot { left: 6px; width: 20px; height: 20px; }

        .ev-timeline-date {
            position: relative;
            left: 0;
            width: auto;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .ev-tl-month { font-size: 0.7rem; }
        .ev-tl-day { font-size: 1.1rem; }

        .ev-tl-header { padding: 18px 18px 0; flex-wrap: wrap; }
        .ev-tl-body { padding: 12px 18px 0; }
        .ev-tl-body h3 { font-size: 1.05rem; }
        .ev-tl-body p { font-size: 0.85rem; }
        .ev-tl-footer { padding: 12px 18px; gap: 10px; }

        .ev-ann-header { padding: 16px 16px 0; }
        .ev-ann-body { padding: 10px 16px 0; }
        .ev-ann-body h4 { font-size: 0.92rem; }
        .ev-ann-body p { font-size: 0.82rem; }
        .ev-ann-footer { padding: 12px 16px; }

        .ev-section-header { margin-bottom: 35px; }
        .ev-announcements-section { padding: 40px 0 60px; }

        .ev-cta-card { padding: 44px 22px; }
        .ev-cta-actions { flex-direction: column; align-items: center; }
        .ev-btn-cta-primary, .ev-btn-cta-secondary { width: 100%; max-width: 280px; text-align: center; }

        .ev-past-card { flex-direction: column; gap: 10px; }
    }
</style>

<!-- ═══════════════════════════════════
     HERO
     ═══════════════════════════════════ -->
<section class="ev-hero">
    <div class="ev-hero-grid"></div>
    <div class="ev-floating-orb o1"></div>
    <div class="ev-floating-orb o2"></div>
    <div class="ev-floating-orb o3"></div>

    <div class="container ev-hero-content">
        <div class="ev-breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house"></i> Home</a>
            <span class="ev-sep">/</span>
            <span class="ev-current">Events & Updates</span>
        </div>

        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="ev-hero-badge">
                    <span class="ev-dot"></span>
                    What's Happening
                </div>
                <h1 class="ev-hero-title">Events & <span>Updates</span></h1>
                <p class="ev-hero-desc">
                    Stay informed with upcoming events, community programs, and important announcements from <?php echo e($barangayName); ?>.
                </p>
                <div class="ev-hero-pills">
                    <div class="ev-hero-pill">
                        <span class="ev-pill-num"><?php echo count($upcomingEvents); ?></span>
                        <span class="ev-pill-lbl">Upcoming Events</span>
                    </div>
                    <div class="ev-hero-pill">
                        <span class="ev-pill-num"><?php echo count($recentAnnouncements); ?></span>
                        <span class="ev-pill-lbl">Active Notices</span>
                    </div>
                    <?php
                        $todayEvents = 0;
                        foreach ($upcomingEvents as $ev) {
                            if ($ev['event_date'] === $today) $todayEvents++;
                        }
                    ?>
                    <?php if ($todayEvents > 0): ?>
                    <div class="ev-hero-pill" style="border-color:rgba(16,185,129,0.25);">
                        <span class="ev-pill-num" style="color:#6ee7b7;"><?php echo $todayEvents; ?></span>
                        <span class="ev-pill-lbl">Today</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="ev-hero-calendar">
                    <h5><i class="bi bi-calendar-week"></i> Next Up</h5>
                    <?php if (!empty($upcomingEvents)): ?>
                        <?php foreach (array_slice($upcomingEvents, 0, 4) as $ev):
                            $evtInfo = getEvtInfo($ev['event_type']);
                        ?>
                            <div class="ev-cal-item">
                                <div class="ev-cal-date-box">
                                    <div class="ev-cal-month"><?php echo date('M', strtotime($ev['event_date'])); ?></div>
                                    <div class="ev-cal-day"><?php echo date('d', strtotime($ev['event_date'])); ?></div>
                                </div>
                                <div class="ev-cal-info">
                                    <h6><?php echo e($ev['title']); ?></h6>
                                    <p>
                                        <i class="bi <?php echo $evtInfo['icon']; ?>" style="color:<?php echo $evtInfo['color']; ?>;"></i>
                                        <?php echo $evtInfo['label']; ?>
                                        <?php if (!empty($ev['event_time'])): ?>
                                            &bull; <?php echo date('g:i A', strtotime($ev['event_time'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--ev-light); font-size:0.85rem;">No upcoming events scheduled.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════
     UPCOMING EVENTS TIMELINE
     ═══════════════════════════════════ -->
<section class="ev-events-section" id="events">
    <div class="container">
        <div class="ev-section-header ev-reveal">
            <div class="ev-section-tag"><i class="bi bi-calendar-event"></i> Upcoming Events</div>
            <h2 class="ev-section-title">Mark Your Calendar</h2>
            <p class="ev-section-subtitle">
                Community events, health missions, training programs, and more — all in one timeline.
            </p>
        </div>

        <?php if (!empty($upcomingEvents)): ?>
            <div class="ev-timeline">
                <?php foreach ($upcomingEvents as $idx => $ev):
                    $evtInfo = getEvtInfo($ev['event_type']);
                    $countdown = daysUntil($ev['event_date']);
                    $delayClass = 'ev-d' . min($idx + 1, 8);
                ?>
                    <div class="ev-timeline-item ev-reveal <?php echo $delayClass; ?>">
                        <div class="ev-timeline-dot" style="border-color:<?php echo $evtInfo['color']; ?>;"></div>

                        <div class="ev-timeline-card" style="--tc-color:<?php echo $evtInfo['color']; ?>;">
                            <div style="position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, <?php echo $evtInfo['color']; ?>, <?php echo $evtInfo['color']; ?>88);"></div>

                            <div class="ev-tl-header">
                                <span class="ev-tl-type-badge" style="background:<?php echo $evtInfo['bg']; ?>; color:<?php echo $evtInfo['color']; ?>; border:1px solid <?php echo $evtInfo['border']; ?>;">
                                    <i class="bi <?php echo $evtInfo['icon']; ?>"></i>
                                    <?php echo e($evtInfo['label']); ?>
                                </span>
                                <span class="ev-tl-countdown <?php echo $countdown['class']; ?>">
                                    <i class="bi bi-clock"></i>
                                    <?php echo e($countdown['text']); ?>
                                </span>
                            </div>

                            <div class="ev-tl-body">
                                <h3><?php echo e($ev['title']); ?></h3>
                                <?php if (!empty($ev['description'])): ?>
                                    <p><?php echo e($ev['description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="ev-tl-footer">
                                <span class="ev-tl-meta">
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo date('l, M d, Y', strtotime($ev['event_date'])); ?>
                                </span>
                                <?php if (!empty($ev['event_time'])): ?>
                                    <span class="ev-tl-meta">
                                        <i class="bi bi-clock"></i>
                                        <?php echo date('g:i A', strtotime($ev['event_time'])); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($ev['location'])): ?>
                                    <span class="ev-tl-meta">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php echo e($ev['location']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ev-empty ev-reveal">
                <div class="ev-empty-icon"><i class="bi bi-calendar-x"></i></div>
                <h4>No Upcoming Events</h4>
                <p>There are no scheduled events at this time. Check back soon for community activities and programs.</p>
            </div>
        <?php endif; ?>

        <!-- Past Events -->
        <?php if (!empty($pastEvents)): ?>
            <div class="ev-past-section ev-reveal" style="margin-top:48px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                    <div style="width:32px; height:32px; border-radius:8px; background:rgba(0,0,0,0.04); border:1px solid var(--ev-border); display:flex; align-items:center; justify-content:center; color:var(--ev-light); font-size:0.85rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 style="font-weight:700; font-size:0.95rem; color:var(--ev-text); margin:0;">Recent Past Events</h5>
                </div>
                <div class="row g-3">
                    <?php foreach ($pastEvents as $pev):
                        $pevInfo = getEvtInfo($pev['event_type']);
                    ?>
                        <div class="col-md-6">
                            <div class="ev-past-card">
                                <div class="ev-past-date">
                                    <div class="ev-pd-month"><?php echo date('M', strtotime($pev['event_date'])); ?></div>
                                    <div class="ev-pd-day"><?php echo date('d', strtotime($pev['event_date'])); ?></div>
                                </div>
                                <div class="ev-past-info">
                                    <h5><?php echo e($pev['title']); ?></h5>
                                    <p>
                                        <span style="color:<?php echo $pevInfo['color']; ?>; font-weight:600;"><?php echo e($pevInfo['label']); ?></span>
                                        <?php if (!empty($pev['location'])): ?>
                                            &bull; <?php echo e($pev['location']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════
     ANNOUNCEMENTS
     ═══════════════════════════════════ -->
<section class="ev-announcements-section" id="announcements">
    <div class="container">
        <div class="ev-section-header ev-reveal">
            <div class="ev-section-tag"><i class="bi bi-megaphone-fill"></i> Announcements</div>
            <h2 class="ev-section-title">Latest Notices & Advisories</h2>
            <p class="ev-section-subtitle">
                Important updates, advisories, and community notices from the barangay.
            </p>
        </div>

        <?php if (!empty($recentAnnouncements)): ?>
            <div class="row g-4">
                <?php foreach ($recentAnnouncements as $idx => $ann):
                    $annInfo = getAnnInfo($ann['type']);
                    $isPinned = !empty($ann['is_pinned']);
                    $priority = $ann['priority'] ?? 'normal';
                    $delayClass = 'ev-d' . min($idx + 1, 8);
                ?>
                    <div class="col-lg-6 ev-reveal <?php echo $delayClass; ?>">
                        <div class="ev-ann-card <?php echo $isPinned ? 'pinned' : ''; ?>">
                            <?php if (!$isPinned): ?>
                                <div style="position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, <?php echo $annInfo['color']; ?>, <?php echo $annInfo['color']; ?>88);"></div>
                            <?php endif; ?>

                            <div class="ev-ann-header">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="ev-ann-type-badge" style="background:<?php echo $annInfo['bg']; ?>; color:<?php echo $annInfo['color']; ?>; border:1px solid <?php echo $annInfo['border']; ?>;">
                                        <i class="bi <?php echo $annInfo['icon']; ?>"></i>
                                        <?php echo e($annInfo['label']); ?>
                                    </span>
                                    <?php if ($priority === 'high'): ?>
                                        <span class="ev-priority-dot high" title="High priority"></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isPinned): ?>
                                    <span class="ev-ann-pinned"><i class="bi bi-pin-fill"></i>Pinned</span>
                                <?php endif; ?>
                            </div>

                            <div class="ev-ann-body">
                                <h4><?php echo e($ann['title']); ?></h4>
                                <p><?php echo e($ann['content']); ?></p>
                            </div>

                            <div class="ev-ann-footer">
                                <span class="ev-ann-date">
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                                </span>
                                <span class="ev-ann-ago"><?php echo relativeTime($ann['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4 ev-reveal">
                <a href="<?php echo BASE_URL; ?>/landing/announcements.php" style="display:inline-flex; align-items:center; gap:8px; color:var(--ev-primary); font-weight:600; font-size:0.9rem; text-decoration:none; padding:10px 20px; border:1px solid rgba(26,86,219,0.2); border-radius:100px; transition:all 0.2s ease;">
                    <i class="bi bi-arrow-right-circle"></i> View All Announcements
                </a>
            </div>
        <?php else: ?>
            <div class="ev-empty ev-reveal">
                <div class="ev-empty-icon"><i class="bi bi-megaphone"></i></div>
                <h4>No Active Announcements</h4>
                <p>There are no current announcements. Check back soon for community updates.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════
     CTA
     ═══════════════════════════════════ -->
<section class="ev-cta-section">
    <div class="container">
        <div class="ev-cta-card ev-reveal">
            <div class="ev-cta-inner">
                <h2>Never Miss an Event Again</h2>
                <p>
                    Register for an account to receive notifications about upcoming events, announcements, and community programs.
                </p>
                <div class="ev-cta-actions">
                    <a class="ev-btn-cta-primary" href="<?php echo BASE_URL; ?>/auth/register.php">
                        <i class="bi bi-person-plus"></i> Create Account
                    </a>
                    <a class="ev-btn-cta-secondary" href="<?php echo BASE_URL; ?>/landing/announcements.php">
                        <i class="bi bi-megaphone"></i> All Announcements
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
    var reveals = document.querySelectorAll('.ev-reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('ev-visible');
            }
        });
    }, { threshold: 0.06, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function(el) { obs.observe(el); });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>