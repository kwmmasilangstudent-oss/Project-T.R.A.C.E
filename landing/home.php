<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

/* ── Fetch SEO-critical data early (used in header before inclusion) ── */
$barangayName = 'Barangay Tumalaytay';
try {
    $n = $pdo->prepare("SELECT key_value FROM settings WHERE key_name = 'barangay_name'");
    $n->execute();
    $v = $n->fetchColumn();
    if ($v) $barangayName = $v;
} catch (Throwable $e) {}

$hero = getLandingContent('hero', 'A transparent and resilient barangay management system for every resident.');

$pageTitle = $barangayName . ' - ' . APP_NAME;
$pageDescription = $hero;
$pageOgType = 'website';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

/* ═══════════════════════════════════════════════
   DATA FETCHING
   ═══════════════════════════════════════════════ */

$services = getLandingContent('services', 'Barangay services, document requests, and public updates are now centralized.');

$heroBg = getSetting('hero_background', '');

$latestAnnouncements = [];
try {
    $latestAnnouncements = $pdo->query("
        SELECT id, title, content, type, priority, is_pinned, created_at
        FROM announcements
        WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= NOW())
        ORDER BY is_pinned DESC, created_at DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $latestAnnouncements = [];
}

$myRecentRequests = [];
if (isLoggedIn() && getCurrentRole() === 'resident') {
    try {
        $residentStmt = $pdo->prepare('SELECT id FROM residents WHERE user_id = ? LIMIT 1');
        $residentStmt->execute([$_SESSION['user_id']]);
        $resident = $residentStmt->fetch();
        if ($resident) {
            $stmt = $pdo->prepare('SELECT * FROM applications WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5');
            $stmt->execute([$resident['id']]);
            $myRecentRequests = $stmt->fetchAll();
        }
    } catch (Throwable $e) {
        $myRecentRequests = [];
    }
}

/* ── Hero Stats ── */
$statAnnouncements = count($latestAnnouncements);
$statOfficials = 0;
try {
    $statOfficials = (int) $pdo->query("SELECT COUNT(*) FROM landing_officials WHERE is_active = 1")->fetchColumn();
} catch (Throwable $e) {}
$statPending = 0;
if (!empty($myRecentRequests)) {
    $statPending = count(array_filter($myRecentRequests, fn($r) => in_array($r['status'] ?? '', ['submitted', 'pending', 'under_review'])));
}

/* ── Announcement Type Config ── */
$typeConfig = [
    'general'        => ['label' => 'General',          'icon' => 'bi-megaphone',            'bg' => 'rgba(99,102,241,0.10)',  'color' => '#6366f1'],
    'event'          => ['label' => 'Event',            'icon' => 'bi-calendar-event',       'bg' => 'rgba(14,165,233,0.10)',  'color' => '#0ea5e9'],
    'health'         => ['label' => 'Health',           'icon' => 'bi-heart-pulse',          'bg' => 'rgba(16,185,129,0.10)',  'color' => '#10b981'],
    'emergency'      => ['label' => 'Emergency',        'icon' => 'bi-exclamation-triangle', 'bg' => 'rgba(239,68,68,0.10)',   'color' => '#ef4444'],
    'infrastructure' => ['label' => 'Infrastructure',   'icon' => 'bi-building',             'bg' => 'rgba(245,158,11,0.10)',  'color' => '#f59e0b'],
    'education'      => ['label' => 'Education',        'icon' => 'bi-mortarboard',          'bg' => 'rgba(139,92,246,0.10)',  'color' => '#8b5cf6'],
    'news'           => ['label' => 'News',             'icon' => 'bi-newspaper',            'bg' => 'rgba(14,165,233,0.10)',  'color' => '#0ea5e9'],
    'program'        => ['label' => 'Program',          'icon' => 'bi-clipboard-data',       'bg' => 'rgba(16,185,129,0.10)',  'color' => '#10b981'],
    'meeting'        => ['label' => 'Meeting',          'icon' => 'bi-calendar-check',       'bg' => 'rgba(20,184,166,0.10)',  'color' => '#14b8a6'],
    'maintenance'    => ['label' => 'Maintenance',      'icon' => 'bi-tools',                'bg' => 'rgba(100,116,139,0.10)', 'color' => '#64748b'],
];

function getLandingTypeInfo($type) {
    global $typeConfig;
    return $typeConfig[$type] ?? $typeConfig['general'];
}
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
    --ct-primary: #1a56db;
    --ct-primary-dark: #1042a3;
    --ct-primary-light: #e8effc;
    --ct-accent: #0ea5e9;
    --ct-accent-dark: #0284c7;
    --ct-teal: #14b8a6;
    --ct-teal-dark: #0d9488;
    --ct-green: #10b981;
    --ct-amber: #f59e0b;
    --ct-red: #ef4444;
    --ct-violet: #8b5cf6;
    --ct-bg: #f0f4f8;
    --ct-card: #ffffff;
    --ct-hero-bg: #0f172a;
    --ct-text: #0f172a;
    --ct-muted: #64748b;
    --ct-light: #94a3b8;
    --ct-border: #e2e8f0;
    --ct-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --ct-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
    --ct-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
    --ct-shadow-xl: 0 20px 60px rgba(0,0,0,0.15);
    --ct-radius: 12px;
    --ct-radius-lg: 20px;
    --ct-radius-xl: 28px;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--ct-bg);
    color: var(--ct-text);
    overflow-x: hidden;
}

.navbar, footer, .main-navbar { display: none !important; }

.ct-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 32px;
}

/* ═══════════════════════════════
   HERO
   ═══════════════════════════════ */
    .ct-hero {
        position: relative;
        padding: 100px 0 80px;
        background: var(--ct-hero-bg);
        overflow: hidden;
    }

    <?php if (!empty($heroBg)): ?>
    .ct-hero {
        background: linear-gradient(160deg, rgba(15,23,42,0.93), rgba(15,23,42,0.87)),
                    url('<?php echo asset($heroBg); ?>') center/cover no-repeat;
    }
    <?php endif; ?>

.ct-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 20% 40%, rgba(14,165,233,0.10) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 70%, rgba(20,184,166,0.08) 0%, transparent 45%),
        radial-gradient(ellipse at 50% 10%, rgba(139,92,246,0.06) 0%, transparent 40%);
    pointer-events: none;
}

.ct-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
}

.ct-hero-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
}

.ct-floating-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    animation: ctFloat 20s ease-in-out infinite;
}

.ct-floating-orb.o1 { width: 360px; height: 360px; background: rgba(14,165,233,0.09); top: -18%; left: -6%; }
.ct-floating-orb.o2 { width: 260px; height: 260px; background: rgba(20,184,166,0.07); bottom: -12%; right: -5%; animation-delay: -10s; }
.ct-floating-orb.o3 { width: 180px; height: 180px; background: rgba(139,92,246,0.06); top: 25%; right: 18%; animation-delay: -5s; animation-duration: 25s; }

@keyframes ctFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

.ct-hero-content { position: relative; z-index: 2; }

/* Breadcrumb */
.ct-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 28px;
    font-size: 0.82rem;
}

.ct-breadcrumb a { color: var(--ct-light); text-decoration: none; transition: color 0.2s; }
.ct-breadcrumb a:hover { color: #fff; }
.ct-breadcrumb .ct-sep { color: rgba(255,255,255,0.2); }
.ct-breadcrumb .ct-current { color: #7dd3fc; font-weight: 600; }

/* Badge */
.ct-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    border-radius: 100px;
    color: #7dd3fc;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.ct-hero-badge .ct-dot {
    width: 8px; height: 8px;
    background: var(--ct-accent);
    border-radius: 50%;
    animation: ctPulse 2s ease-in-out infinite;
}

@keyframes ctPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

/* Title */
.ct-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 900;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 16px;
}

.ct-hero-title span {
    background: linear-gradient(135deg, var(--ct-accent), #38bdf8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ct-hero-desc {
    font-size: 1.1rem;
    color: #94a3b8;
    max-width: 600px;
    line-height: 1.7;
    margin-bottom: 32px;
}

/* Hero Actions */
.ct-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 44px;
}

.ct-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 26px;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    cursor: pointer;
    border: none;
    white-space: nowrap;
}

.ct-btn i { transition: transform 0.2s ease; }

.ct-btn-primary {
    background: linear-gradient(135deg, var(--ct-accent), var(--ct-accent-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(14,165,233,0.3);
}
.ct-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(14,165,233,0.4);
    color: #ffffff;
}
.ct-btn-primary:hover i { transform: translateX(3px); }

.ct-btn-green {
    background: linear-gradient(135deg, var(--ct-green), #059669);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.3);
}
.ct-btn-green:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
    color: #ffffff;
}
.ct-btn-green:hover i { transform: translateX(3px); }

.ct-btn-ghost {
    background: rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.7);
    border: 1px solid rgba(255,255,255,0.12);
}
.ct-btn-ghost:hover {
    background: rgba(255,255,255,0.12);
    color: #ffffff;
    border-color: rgba(255,255,255,0.22);
}

/* Hero Stats */
.ct-hero-stats {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.ct-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 22px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: var(--ct-radius-lg);
    backdrop-filter: blur(16px);
    min-width: 170px;
    transition: all 0.3s ease;
}

.ct-stat-card:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.14);
}

.ct-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.ct-stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.ct-stat-label {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.4);
    margin-top: 2px;
}

/* ═══════════════════════════════
   SECTIONS
   ═══════════════════════════════ */
.ct-section { padding: 80px 0; }
.ct-section-alt { background: #ffffff; }

.ct-section-header { margin-bottom: 40px; }

.ct-section-header.ct-center { text-align: center; }
.ct-section-header.ct-center .ct-section-subtitle { margin: 0 auto; }

.ct-section-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: var(--ct-primary-light);
    color: var(--ct-primary);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    border-radius: 100px;
    margin-bottom: 16px;
}

.ct-section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: var(--ct-text);
    margin-bottom: 10px;
    line-height: 1.15;
}

.ct-section-subtitle {
    font-size: 1rem;
    color: var(--ct-muted);
    max-width: 560px;
    line-height: 1.7;
}

/* Section header row with View All */
.ct-section-head-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 40px;
    gap: 24px;
    flex-wrap: wrap;
}

.ct-view-all {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--ct-accent);
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
    padding-bottom: 2px;
    border-bottom: 1px solid rgba(14,165,233,0.3);
}

.ct-view-all:hover {
    color: var(--ct-accent-dark);
    border-color: var(--ct-accent);
    gap: 10px;
}

/* ═══════════════════════════════
   SERVICE CARDS
   ═══════════════════════════════ */
.ct-service-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.ct-service-card {
    display: flex;
    flex-direction: column;
    background: var(--ct-card);
    border: 1px solid var(--ct-border);
    border-radius: var(--ct-radius-lg);
    box-shadow: var(--ct-shadow-sm);
    padding: 30px 24px 26px;
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.ct-service-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--ct-shadow-lg);
    border-color: transparent;
    color: inherit;
}

.ct-svc-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 18px;
    transition: transform 0.3s ease;
}

.ct-service-card:hover .ct-svc-icon { transform: scale(1.08) rotate(-4deg); }

.ct-service-card h5 {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--ct-text);
    margin-bottom: 8px;
}

.ct-service-card p {
    font-size: 0.85rem;
    color: var(--ct-muted);
    line-height: 1.65;
    flex: 1;
    margin-bottom: 18px;
}

.ct-svc-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    transition: gap 0.2s ease;
}

.ct-service-card:hover .ct-svc-link { gap: 10px; }

/* ═══════════════════════════════
   ANNOUNCEMENT CARDS
   ═══════════════════════════════ */
.ct-announce-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.ct-announce-card {
    display: flex;
    flex-direction: column;
    background: var(--ct-card);
    border: 1px solid var(--ct-border);
    border-radius: var(--ct-radius-lg);
    box-shadow: var(--ct-shadow-sm);
    overflow: hidden;
    position: relative;
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.ct-announce-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ct-shadow-md);
    border-color: rgba(0,0,0,0.08);
}

.ct-announce-body {
    padding: 26px 22px 18px;
    flex: 1;
}

.ct-announce-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.ct-announce-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.ct-pin-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 11px;
    border-radius: 6px;
    background: rgba(245,158,11,0.10);
    color: var(--ct-amber);
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    border: 1px solid rgba(245,158,11,0.2);
}

.ct-announce-card h5 {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--ct-text);
    margin-bottom: 10px;
    line-height: 1.35;
}

.ct-announce-text {
    font-size: 0.85rem;
    color: var(--ct-muted);
    line-height: 1.65;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ct-announce-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 22px;
    border-top: 1px solid var(--ct-border);
    font-size: 0.78rem;
    color: var(--ct-light);
}

.ct-announce-footer a {
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: gap 0.2s ease;
}

.ct-announce-footer a:hover { gap: 8px; }

/* ═══════════════════════════════
   REQUESTS TABLE
   ═══════════════════════════════ */
.ct-table-card {
    background: var(--ct-card);
    border: 1px solid var(--ct-border);
    border-radius: var(--ct-radius-lg);
    box-shadow: var(--ct-shadow-sm);
    overflow: hidden;
}

.ct-table-wrap { overflow-x: auto; }

.ct-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.ct-table thead { background: rgba(0,0,0,0.02); }

.ct-table th {
    padding: 14px 20px;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ct-muted);
    border-bottom: 1px solid var(--ct-border);
    white-space: nowrap;
}

.ct-table td {
    padding: 16px 20px;
    color: #334155;
    border-bottom: 1px solid var(--ct-border);
    vertical-align: middle;
}

.ct-table tbody tr { transition: background 0.2s ease; }
.ct-table tbody tr:hover { background: rgba(14,165,233,0.03); }
.ct-table tbody tr:last-child td { border-bottom: none; }

.ct-table .ct-ref {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-weight: 600;
    color: var(--ct-primary);
    font-size: 0.82rem;
}

.ct-table .ct-type {
    font-weight: 700;
    color: var(--ct-text);
}

.ct-table .ct-date {
    font-size: 0.8rem;
    color: var(--ct-light);
}

.ct-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

/* ═══════════════════════════════
   CTA SECTION
   ═══════════════════════════════ */
.ct-cta-section {
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}

.ct-cta-card {
    background: var(--ct-hero-bg);
    border-radius: var(--ct-radius-xl);
    padding: 60px 48px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.ct-cta-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 30% 50%, rgba(14,165,233,0.10) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 30%, rgba(20,184,166,0.08) 0%, transparent 45%);
    pointer-events: none;
}

.ct-cta-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
}

.ct-cta-content { position: relative; z-index: 2; }

.ct-cta-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: rgba(14,165,233,0.15);
    border: 1px solid rgba(14,165,233,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: var(--ct-accent);
    margin: 0 auto 20px;
}

.ct-cta-card h3 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.4rem, 2.5vw, 1.8rem);
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 12px;
}

.ct-cta-card p {
    font-size: 1rem;
    color: #94a3b8;
    max-width: 520px;
    margin: 0 auto 28px;
    line-height: 1.7;
}

.ct-cta-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* ═══════════════════════════════
   SCROLL REVEAL
   ═══════════════════════════════ */
.ct-reveal {
    opacity: 0;
    transform: translateY(35px);
    transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.ct-reveal.ct-visible {
    opacity: 1;
    transform: translateY(0);
}

.ct-d1 { transition-delay: 0.05s; }
.ct-d2 { transition-delay: 0.1s; }
.ct-d3 { transition-delay: 0.15s; }
.ct-d4 { transition-delay: 0.2s; }
.ct-d5 { transition-delay: 0.25s; }
.ct-d6 { transition-delay: 0.3s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .ct-service-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 991.98px) {
    .ct-hero { padding: 80px 0 60px; }
    .ct-section { padding: 60px 0; }
    .ct-cta-section { padding: 60px 0; }
    .ct-announce-grid { grid-template-columns: repeat(2, 1fr); }
    .ct-hero-stats { gap: 10px; }
    .ct-stat-card { min-width: 150px; padding: 14px 18px; }
    .ct-stat-value { font-size: 1.3rem; }
}

@media (max-width: 767.98px) {
    .ct-container { padding: 0 20px; }
    .ct-hero { padding: 70px 0 48px; }
    .ct-hero-title { font-size: 2rem; }
    .ct-section { padding: 50px 0; }
    .ct-cta-section { padding: 50px 0; }
    .ct-service-grid { grid-template-columns: 1fr; }
    .ct-announce-grid { grid-template-columns: 1fr; }
    .ct-service-card { padding: 24px 20px 22px; }
    .ct-section-head-row { flex-direction: column; align-items: flex-start; gap: 12px; }
    .ct-cta-card { padding: 40px 24px; }
    .ct-table th, .ct-table td { padding: 12px 14px; }
}

@media (max-width: 480px) {
    .ct-hero-title { font-size: 1.7rem; }
    .ct-hero-stats { flex-direction: column; }
    .ct-stat-card { min-width: 100%; }
    .ct-hero-actions { flex-direction: column; }
    .ct-btn { width: 100%; justify-content: center; }
    .ct-cta-section { padding: 40px 0; }
    .ct-cta-card { padding: 32px 18px; }
}

/* ═══════════════════════════════
   LOADING SCREEN
   ═══════════════════════════════ */
.lp-screen {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0a0f1e;
    transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.8s;
}

.lp-screen.lp-done {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.lp-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.012) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.012) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
}

.lp-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    opacity: 0;
    animation: lpOrbIn 2s ease forwards, lpOrbDrift 20s ease-in-out infinite;
}

.lp-orb.a { width: 600px; height: 600px; background: rgba(16,185,129,0.07); top: -15%; left: -10%; animation-delay: 0s, 0s; }
.lp-orb.b { width: 400px; height: 400px; background: rgba(139,92,246,0.06); bottom: -10%; right: -5%; animation-delay: 0.3s, -10s; }
.lp-orb.c { width: 300px; height: 300px; background: rgba(14,165,233,0.05); top: 40%; left: 50%; animation-delay: 0.6s, -5s; }

@keyframes lpOrbIn { from { opacity: 0; transform: scale(0.6); } to { opacity: 1; transform: scale(1); } }
@keyframes lpOrbDrift { 0%, 100% { transform: translate(0,0) scale(1); } 33% { transform: translate(30px,-20px) scale(1.03); } 66% { transform: translate(-20px,15px) scale(0.97); } }

.lp-particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }

.lp-particle {
    position: absolute;
    width: 2px;
    height: 2px;
    background: #10b981;
    border-radius: 50%;
    opacity: 0;
    animation: lpFloat linear infinite;
}

@keyframes lpFloat {
    0%   { opacity: 0; transform: translateY(0) scale(0); }
    15%  { opacity: 0.6; transform: scale(1); }
    85%  { opacity: 0.3; }
    100% { opacity: 0; transform: translateY(-100vh) scale(0); }
}

.lp-center {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 40px;
    padding: 0 24px;
    text-align: center;
}

.lp-shield-wrap {
    position: relative;
    width: 120px;
    height: 120px;
    opacity: 0;
    animation: lpShieldIn 1s cubic-bezier(0.16,1,0.3,1) 0.2s forwards;
}

.lp-shield-ring {
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    border: 2px solid transparent;
    border-top-color: #10b981;
    border-right-color: rgba(16,185,129,0.3);
    animation: lpSpin 2.2s linear infinite;
}

.lp-shield-ring.b { inset: -18px; border-top-color: rgba(139,92,246,0.4); border-right-color: rgba(139,92,246,0.1); animation-duration: 3.4s; animation-direction: reverse; }
.lp-shield-ring.c { inset: -28px; border-top-color: rgba(14,165,233,0.2); border-right-color: transparent; animation-duration: 5s; border-width: 1px; }

.lp-shield {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: linear-gradient(145deg, rgba(16,185,129,0.12), rgba(16,185,129,0.03));
    border: 2px solid rgba(16,185,129,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(12px);
    box-shadow: 0 0 40px rgba(16,185,129,0.08), inset 0 0 30px rgba(16,185,129,0.04);
}

.lp-shield i {
    font-size: 2.8rem;
    color: #10b981;
    filter: drop-shadow(0 0 12px rgba(16,185,129,0.3));
    animation: lpPulse 3s ease-in-out infinite;
}

@keyframes lpSpin { to { transform: rotate(360deg); } }
@keyframes lpShieldIn { from { opacity: 0; transform: scale(0.5) rotate(-20deg); } to { opacity: 1; transform: scale(1) rotate(0deg); } }
@keyframes lpPulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.06); opacity: 0.85; } }

.lp-text-block { opacity: 0; animation: lpTextIn 0.8s ease 0.5s forwards; }

.lp-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 4vw, 2.4rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 10px;
    letter-spacing: -0.01em;
}

.lp-title span {
    background: linear-gradient(135deg, #10b981, #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lp-subtitle { font-size: 0.95rem; color: #64748b; font-weight: 400; letter-spacing: 0.02em; }

.lp-divider { width: 48px; height: 3px; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 2px; margin: 16px auto 0; }

@keyframes lpTextIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.lp-progress-section { width: 280px; opacity: 0; animation: lpTextIn 0.8s ease 0.8s forwards; }

.lp-progress-track { width: 100%; height: 3px; background: rgba(255,255,255,0.06); border-radius: 10px; overflow: hidden; position: relative; }

.lp-progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7); border-radius: 10px; transition: width 0.3s ease; position: relative; }

.lp-progress-bar::after { content: ''; position: absolute; right: 0; top: -3px; width: 9px; height: 9px; background: #6ee7b7; border-radius: 50%; box-shadow: 0 0 14px rgba(16,185,129,0.5), 0 0 4px rgba(16,185,129,0.8); }

.lp-progress-info { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; }

.lp-progress-label { font-size: 0.73rem; font-weight: 600; color: #64748b; letter-spacing: 0.06em; text-transform: uppercase; }

.lp-progress-pct { font-size: 0.73rem; font-weight: 700; color: #34d399; font-variant-numeric: tabular-nums; }

.lp-status { opacity: 0; animation: lpTextIn 0.6s ease 1.1s forwards; display: flex; align-items: center; gap: 8px; }

.lp-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px rgba(16,185,129,0.15); animation: lpDotPulse 1.4s ease-in-out infinite; }

.lp-status-text { font-size: 0.76rem; color: #64748b; font-weight: 500; font-variant-numeric: tabular-nums; }

@keyframes lpDotPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(0.8); } }

.lp-corner { position: absolute; width: 60px; height: 60px; pointer-events: none; opacity: 0; animation: lpTextIn 1s ease 0.3s forwards; }

.lp-corner::before, .lp-corner::after { content: ''; position: absolute; background: linear-gradient(90deg, #10b981, transparent); border-radius: 2px; }

.lp-corner.tl { top: 32px; left: 32px; }
.lp-corner.tl::before { top: 0; left: 0; width: 40px; height: 1px; }
.lp-corner.tl::after { top: 0; left: 0; width: 1px; height: 40px; background: linear-gradient(180deg, #10b981, transparent); }

.lp-corner.tr { top: 32px; right: 32px; }
.lp-corner.tr::before { top: 0; right: 0; width: 40px; height: 1px; background: linear-gradient(270deg, #10b981, transparent); }
.lp-corner.tr::after { top: 0; right: 0; width: 1px; height: 40px; background: linear-gradient(180deg, #10b981, transparent); }

.lp-corner.bl { bottom: 32px; left: 32px; }
.lp-corner.bl::before { bottom: 0; left: 0; width: 40px; height: 1px; }
.lp-corner.bl::after { bottom: 0; left: 0; width: 1px; height: 40px; background: linear-gradient(0deg, #10b981, transparent); }

.lp-corner.br { bottom: 32px; right: 32px; }
.lp-corner.br::before { bottom: 0; right: 0; width: 40px; height: 1px; background: linear-gradient(270deg, #10b981, transparent); }
.lp-corner.br::after { bottom: 0; right: 0; width: 1px; height: 40px; background: linear-gradient(0deg, #10b981, transparent); }

.lp-footer { position: absolute; bottom: 32px; left: 50%; transform: translateX(-50%); font-size: 0.68rem; color: rgba(255,255,255,0.15); letter-spacing: 0.08em; text-transform: uppercase; font-weight: 600; white-space: nowrap; opacity: 0; animation: lpTextIn 0.8s ease 1.3s forwards; }

@media (max-width: 480px) {
    .lp-shield-wrap { width: 96px; height: 96px; }
    .lp-shield i { font-size: 2.2rem; }
    .lp-center { gap: 30px; }
    .lp-progress-section { width: 220px; }
    .lp-corner { display: none; }
}
</style>

<!-- ═══════════════════════════════════════
     LOADING SCREEN
     ═══════════════════════════════════════ -->
<div class="lp-screen" id="lpScreen">
    <div class="lp-grid"></div>
    <div class="lp-orb a"></div>
    <div class="lp-orb b"></div>
    <div class="lp-orb c"></div>
    <div class="lp-particles" id="lpParticles"></div>
    <div class="lp-corner tl"></div>
    <div class="lp-corner tr"></div>
    <div class="lp-corner bl"></div>
    <div class="lp-corner br"></div>

    <div class="lp-center">
        <div class="lp-shield-wrap">
            <div class="lp-shield-ring"></div>
            <div class="lp-shield-ring b"></div>
            <div class="lp-shield-ring c"></div>
            <div class="lp-shield">
                <i class="bi bi-shield-fill-check"></i>
            </div>
        </div>

        <div class="lp-text-block">
            <h1 class="lp-title">Barangay <span>Management</span></h1>
            <p class="lp-subtitle">Community Services Portal</p>
            <div class="lp-divider"></div>
        </div>

        <div class="lp-progress-section">
            <div class="lp-progress-track">
                <div class="lp-progress-bar" id="lpBar"></div>
            </div>
            <div class="lp-progress-info">
                <span class="lp-progress-label">Initializing</span>
                <span class="lp-progress-pct" id="lpPct">0%</span>
            </div>
        </div>

        <div class="lp-status">
            <div class="lp-status-dot"></div>
            <span class="lp-status-text" id="lpStatus">Connecting to server...</span>
        </div>
    </div>

    <div class="lp-footer">Secure &bull; Encrypted &bull; Trusted</div>
</div>

<!-- ═══════════════════════════════════════
     HERO
     ═══════════════════════════════════════ -->
<section class="ct-hero">
    <div class="ct-hero-grid"></div>
    <div class="ct-floating-orb o1"></div>
    <div class="ct-floating-orb o2"></div>
    <div class="ct-floating-orb o3"></div>

    <div class="ct-container ct-hero-content">
        <div class="ct-breadcrumb">
            <span class="ct-current"><i class="bi bi-house"></i> Home</span>
        </div>

        <div class="ct-hero-badge">
            <span class="ct-dot"></span>
            Community Portal
        </div>

        <h1 class="ct-hero-title">Welcome to <span><?php echo e($barangayName); ?></span></h1>
        <p class="ct-hero-desc"><?php echo e($hero); ?></p>

        <div class="ct-hero-actions">
            <?php if (isLoggedIn() && getCurrentRole() === 'resident'): ?>
                <a href="<?php echo BASE_URL; ?>/resident/requests.php" class="ct-btn ct-btn-green">
                    <i class="bi bi-file-earmark-text"></i> Request Certificate
                </a>
                <a href="<?php echo BASE_URL; ?>/resident/notifications.php" class="ct-btn ct-btn-ghost">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <a href="<?php echo BASE_URL; ?>/landing/officials.php" class="ct-btn ct-btn-ghost">
                    <i class="bi bi-diagram-3"></i> Officials
                </a>
            <?php elseif (isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="ct-btn ct-btn-primary">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>/landing/officials.php" class="ct-btn ct-btn-ghost">
                    <i class="bi bi-diagram-3"></i> Officials
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/auth/login.php" class="ct-btn ct-btn-primary">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
                <a href="<?php echo BASE_URL; ?>/landing/officials.php" class="ct-btn ct-btn-ghost">
                    <i class="bi bi-diagram-3"></i> View Officials
                </a>
                <a href="<?php echo BASE_URL; ?>/landing/announcements.php" class="ct-btn ct-btn-ghost">
                    <i class="bi bi-megaphone"></i> Announcements
                </a>
            <?php endif; ?>
        </div>

        <div class="ct-hero-stats">
            <div class="ct-stat-card ct-reveal ct-d1">
                <div class="ct-stat-icon" style="background:rgba(14,165,233,0.15); color:#0ea5e9;">
                    <i class="bi bi-megaphone"></i>
                </div>
                <div>
                    <div class="ct-stat-value"><?php echo $statAnnouncements; ?></div>
                    <div class="ct-stat-label">Active News</div>
                </div>
            </div>
            <div class="ct-stat-card ct-reveal ct-d2">
                <div class="ct-stat-icon" style="background:rgba(139,92,246,0.15); color:#8b5cf6;">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="ct-stat-value"><?php echo $statOfficials; ?></div>
                    <div class="ct-stat-label">Officials</div>
                </div>
            </div>
            <div class="ct-stat-card ct-reveal ct-d3">
                <div class="ct-stat-icon" style="background:rgba(20,184,166,0.15); color:#14b8a6;">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
                <div>
                    <div class="ct-stat-value">4</div>
                    <div class="ct-stat-label">Services</div>
                </div>
            </div>
            <?php if (isLoggedIn() && getCurrentRole() === 'resident' && $statPending > 0): ?>
            <div class="ct-stat-card ct-reveal ct-d4">
                <div class="ct-stat-icon" style="background:rgba(245,158,11,0.15); color:#f59e0b;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="ct-stat-value"><?php echo $statPending; ?></div>
                    <div class="ct-stat-label">Pending</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     SERVICES
     ═══════════════════════════════════════ -->
<section class="ct-section">
    <div class="ct-container">
        <div class="ct-section-header ct-center ct-reveal">
            <div class="ct-section-tag"><i class="bi bi-grid-3x3-gap"></i> Services</div>
            <h2 class="ct-section-title">What We Offer</h2>
            <p class="ct-section-subtitle"><?php echo e($services); ?></p>
        </div>

        <div class="ct-service-grid">
            <a href="<?php echo BASE_URL; ?>/<?php echo (isLoggedIn() && getCurrentRole() === 'resident') ? 'resident/requests.php' : 'auth/login.php'; ?>" class="ct-service-card ct-reveal ct-d1">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ct-accent),#38bdf8);"></div>
                <div class="ct-svc-icon" style="background:rgba(14,165,233,0.10);color:var(--ct-accent);">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h5>Document Requests</h5>
                <p>Request barangay clearance, residency certificates, and other official documents online.</p>
                <span class="ct-svc-link" style="color:var(--ct-accent);">
                    <?php echo isLoggedIn() ? 'Request Now' : 'Sign In to Request'; ?> <i class="bi bi-arrow-right"></i>
                </span>
            </a>

            <a href="<?php echo BASE_URL; ?>/landing/announcements.php" class="ct-service-card ct-reveal ct-d2">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ct-violet),#a78bfa);"></div>
                <div class="ct-svc-icon" style="background:rgba(139,92,246,0.10);color:var(--ct-violet);">
                    <i class="bi bi-megaphone"></i>
                </div>
                <h5>Community Updates</h5>
                <p>Stay informed with the latest announcements, events, and community programs.</p>
                <span class="ct-svc-link" style="color:var(--ct-violet);">
                    View Updates <i class="bi bi-arrow-right"></i>
                </span>
            </a>

            <a href="<?php echo BASE_URL; ?>/landing/officials.php" class="ct-service-card ct-reveal ct-d3">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ct-amber),#fbbf24);"></div>
                <div class="ct-svc-icon" style="background:rgba(245,158,11,0.10);color:var(--ct-amber);">
                    <i class="bi bi-people"></i>
                </div>
                <h5>Officials Directory</h5>
                <p>Find contact information and details for all barangay officials and staff.</p>
                <span class="ct-svc-link" style="color:var(--ct-amber);">
                    View Directory <i class="bi bi-arrow-right"></i>
                </span>
            </a>

            <a href="<?php echo BASE_URL; ?>/<?php echo (isLoggedIn() && getCurrentRole() === 'resident') ? 'resident/profile.php' : 'auth/login.php'; ?>" class="ct-service-card ct-reveal ct-d4">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ct-green),#34d399);"></div>
                <div class="ct-svc-icon" style="background:rgba(16,185,129,0.10);color:var(--ct-green);">
                    <i class="bi bi-person-check"></i>
                </div>
                <h5>Resident Portal</h5>
                <p>Manage your profile, track applications, and access personalized resident services.</p>
                <span class="ct-svc-link" style="color:var(--ct-green);">
                    <?php echo isLoggedIn() ? 'My Profile' : 'Get Started'; ?> <i class="bi bi-arrow-right"></i>
                </span>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     ANNOUNCEMENTS
     ═══════════════════════════════════════ -->
<?php if (!empty($latestAnnouncements)): ?>
<section class="ct-section ct-section-alt">
    <div class="ct-container">
        <div class="ct-section-head-row ct-reveal">
            <div>
                <div class="ct-section-tag"><i class="bi bi-megaphone"></i> Latest Updates</div>
                <h2 class="ct-section-title" style="margin-bottom:6px;">Announcements</h2>
                <p class="ct-section-subtitle" style="margin:0;">Recent notices and community updates from your barangay.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/landing/announcements.php" class="ct-view-all">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="ct-announce-grid">
            <?php foreach ($latestAnnouncements as $i => $announcement):
                $info = getLandingTypeInfo($announcement['type'] ?? 'general');
                $created = strtotime($announcement['created_at']);
                $delay = 'ct-d' . min($i + 1, 6);
            ?>
                <div class="ct-announce-card ct-reveal <?php echo $delay; ?>">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?php echo $info['color']; ?>;"></div>
                    <div class="ct-announce-body">
                        <div class="ct-announce-badges">
                            <span class="ct-announce-badge" style="background:<?php echo $info['bg']; ?>;color:<?php echo $info['color']; ?>;">
                                <i class="bi <?php echo $info['icon']; ?>"></i>
                                <?php echo e($info['label']); ?>
                            </span>
                            <?php if (!empty($announcement['is_pinned'])): ?>
                                <span class="ct-pin-badge"><i class="bi bi-pin-angle"></i> Pinned</span>
                            <?php endif; ?>
                        </div>
                        <h5><?php echo e($announcement['title']); ?></h5>
                        <div class="ct-announce-text"><?php echo nl2br(e($announcement['content'])); ?></div>
                    </div>
                    <div class="ct-announce-footer">
                        <span><i class="bi bi-calendar3" style="margin-right:5px;"></i><?php echo date('M d, Y', $created); ?></span>
                        <a href="<?php echo BASE_URL; ?>/landing/announcements.php" style="color:<?php echo $info['color']; ?>;">
                            Read more <i class="bi bi-arrow-right-short"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════
     MY RECENT REQUESTS
     ═══════════════════════════════════════ -->
<?php if (!empty($myRecentRequests)): ?>
<section class="ct-section">
    <div class="ct-container">
        <div class="ct-section-head-row ct-reveal">
            <div>
                <div class="ct-section-tag"><i class="bi bi-clock-history"></i> My Requests</div>
                <h2 class="ct-section-title" style="margin-bottom:6px;">Recent Applications</h2>
                <p class="ct-section-subtitle" style="margin:0;">Your latest service requests and their current status.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/resident/requests.php" class="ct-view-all">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="ct-table-card ct-reveal ct-d1">
            <div class="ct-table-wrap">
                <table class="ct-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myRecentRequests as $app):
                            $statusCfg = match($app['status'] ?? '') {
                                'submitted'        => ['bg' => 'rgba(14,165,233,0.08)', 'color' => '#0ea5e9', 'icon' => 'bi-send'],
                                'pending'          => ['bg' => 'rgba(245,158,11,0.08)', 'color' => '#f59e0b', 'icon' => 'bi-clock'],
                                'under_review'     => ['bg' => 'rgba(20,184,166,0.08)', 'color' => '#14b8a6', 'icon' => 'bi-eye'],
                                'approved'         => ['bg' => 'rgba(16,185,129,0.08)', 'color' => '#10b981', 'icon' => 'bi-check-circle'],
                                'ready_for_pickup' => ['bg' => 'rgba(16,185,129,0.08)', 'color' => '#10b981', 'icon' => 'bi-bag-check'],
                                'completed'        => ['bg' => 'rgba(16,185,129,0.08)', 'color' => '#10b981', 'icon' => 'bi-check-all'],
                                'rejected'         => ['bg' => 'rgba(239,68,68,0.08)', 'color' => '#ef4444', 'icon' => 'bi-x-circle'],
                                default            => ['bg' => 'rgba(100,116,139,0.08)', 'color' => '#64748b', 'icon' => 'bi-question-circle'],
                            };
                            $priorityCfg = match($app['priority'] ?? 'normal') {
                                'urgent' => ['bg' => 'rgba(239,68,68,0.08)', 'color' => '#ef4444'],
                                'high'   => ['bg' => 'rgba(245,158,11,0.08)', 'color' => '#f59e0b'],
                                default  => ['bg' => 'rgba(100,116,139,0.06)', 'color' => '#64748b'],
                            };
                        ?>
                            <tr>
                                <td><span class="ct-ref">#<?php echo (int)$app['id']; ?></span></td>
                                <td class="ct-type"><?php echo e($app['application_type']); ?></td>
                                <td style="max-width:200px;"><?php echo e($app['purpose'] ?? '&mdash;'); ?></td>
                                <td>
                                    <span class="ct-status-badge" style="background:<?php echo $priorityCfg['bg']; ?>;color:<?php echo $priorityCfg['color']; ?>;">
                                        <?php echo e(ucfirst($app['priority'] ?? 'normal')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="ct-status-badge" style="background:<?php echo $statusCfg['bg']; ?>;color:<?php echo $statusCfg['color']; ?>;">
                                        <i class="bi <?php echo $statusCfg['icon']; ?>" style="font-size:0.65rem;"></i>
                                        <?php echo e(ucwords(str_replace('_', ' ', $app['status'] ?? 'unknown'))); ?>
                                    </span>
                                </td>
                                <td class="ct-date"><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════
     CTA
     ═══════════════════════════════════════ -->
<section class="ct-cta-section">
    <div class="ct-container">
        <div class="ct-cta-card ct-reveal">
            <div class="ct-cta-content">
                <div class="ct-cta-icon"><i class="bi bi-heart-pulse"></i></div>
                <h3><?php echo e($barangayName); ?></h3>
                <p><?php echo isLoggedIn()
                    ? 'Explore our services, check the latest updates, or connect with your barangay officials.'
                    : 'Create an account to request documents, track your applications, and stay connected with your community.';
                ?></p>
                <div class="ct-cta-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>/landing/officials.php" class="ct-btn ct-btn-primary">
                            <i class="bi bi-diagram-3"></i> Meet the Officials
                        </a>
                        <a href="<?php echo BASE_URL; ?>/landing/announcements.php" class="ct-btn ct-btn-ghost">
                            <i class="bi bi-megaphone"></i> View Announcements
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="ct-btn ct-btn-primary">
                            <i class="bi bi-person-plus"></i> Register Now
                        </a>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="ct-btn ct-btn-ghost">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
(function() {
    'use strict';

    var lpContainer = document.getElementById('lpParticles');
    var lpCount = window.innerWidth < 600 ? 18 : 35;

    for (var i = 0; i < lpCount; i++) {
        var p = document.createElement('div');
        p.className = 'lp-particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.bottom = '-4px';
        p.style.animationDuration = (6 + Math.random() * 10) + 's';
        p.style.animationDelay = (Math.random() * 8) + 's';
        p.style.width = (1.5 + Math.random() * 2) + 'px';
        p.style.height = p.style.width;
        if (Math.random() > 0.7) {
            p.style.background = 'rgba(139,92,246,0.7)';
        } else if (Math.random() > 0.5) {
            p.style.background = 'rgba(14,165,233,0.6)';
        }
        lpContainer.appendChild(p);
    }

    var lpBar = document.getElementById('lpBar');
    var lpPct = document.getElementById('lpPct');
    var lpStatus = document.getElementById('lpStatus');
    var lpScreen = document.getElementById('lpScreen');

    var lpMessages = [
        { at: 0,   text: 'Connecting to server...' },
        { at: 12,  text: 'Authenticating credentials...' },
        { at: 28,  text: 'Loading user profile...' },
        { at: 45,  text: 'Fetching announcements...' },
        { at: 60,  text: 'Preparing dashboard...' },
        { at: 78,  text: 'Loading documents & requests...' },
        { at: 90,  text: 'Almost ready...' },
        { at: 100, text: 'Welcome!' }
    ];

    var lpProgress = 0;
    var lpMsgIndex = 0;
    var lpDuration = 3000;
    var lpInterval = 30;
    var lpStep = 100 / (lpDuration / lpInterval);

    var lpTimer = setInterval(function() {
        lpProgress += lpStep + (Math.random() * lpStep * 0.5);
        if (lpProgress >= 100) lpProgress = 100;

        var rounded = Math.round(lpProgress);
        lpBar.style.width = rounded + '%';
        lpPct.textContent = rounded + '%';

        for (var j = lpMessages.length - 1; j >= 0; j--) {
            if (rounded >= lpMessages[j].at && lpMsgIndex < j) {
                lpMsgIndex = j;
                lpStatus.style.transition = 'opacity 0.2s ease';
                lpStatus.style.opacity = '0';
                (function(msg) {
                    setTimeout(function() {
                        lpStatus.textContent = msg;
                        lpStatus.style.opacity = '1';
                    }, 200);
                })(lpMessages[j].text);
                break;
            }
        }

        if (lpProgress >= 100) {
            clearInterval(lpTimer);
            setTimeout(function() {
                lpScreen.classList.add('lp-done');
                setTimeout(function() {
                    var reveals = document.querySelectorAll('.ct-reveal');
                    reveals.forEach(function(el) {
                        el.classList.add('ct-visible');
                    });
                }, 400);
                setTimeout(function() {
                    if (lpScreen.parentNode) lpScreen.parentNode.removeChild(lpScreen);
                }, 900);
            }, 600);
        }
    }, lpInterval);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>