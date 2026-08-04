<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Contact Us - ' . APP_NAME;
$pageDescription = 'Get in touch with Barangay Tumalaytay. Office hours, contact information, and location details.';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = getDbConnection();

$contactContent = getLandingContent('contact', 'Office: Barangay Hall, Tumalaytay. Contact the barangay office for verification and support.');
$footerContent = getLandingContent('footer', 'Thank you for partnering with the barangay in building a stronger community.');

/* ── Fetch officials for directory ── */
$contactOfficials = [];
try {
    $contactOfficials = $pdo->query("
        SELECT official_name, position_title, position_label, contact_number, email, tier
        FROM landing_officials
        WHERE is_active = 1 AND (contact_number != '' OR email != '')
        ORDER BY FIELD(tier, 'captain','executive','kagawad','sk'), sort_order ASC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $contactOfficials = [];
}

/* ── Fetch settings ── */
$barangayName = 'Barangay Tumalaytay';
$officeAddress = '';
$officePhone = '';
$officeEmail = '';
$fbLink = '#';
$mapEmbed = '';

try {
    $keys = ['barangay_name', 'office_address', 'office_phone', 'office_email', 'facebook_url', 'map_embed'];
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $s = $pdo->prepare("SELECT key_name, key_value FROM settings WHERE key_name IN ($placeholders)");
    $s->execute($keys);
    while ($row = $s->fetch()) {
        if ($row['key_name'] === 'barangay_name') $barangayName = $row['key_value'];
        if ($row['key_name'] === 'office_address') $officeAddress = $row['key_value'];
        if ($row['key_name'] === 'office_phone') $officePhone = $row['key_value'];
        if ($row['key_name'] === 'office_email') $officeEmail = $row['key_value'];
        if ($row['key_name'] === 'facebook_url') $fbLink = $row['key_value'];
        if ($row['key_name'] === 'map_embed') $mapEmbed = $row['key_value'];
    }
} catch (Throwable $e) {}
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

/* Hero Quick Contact */
.ct-hero-contact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
}

.ct-hero-contact-item {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 22px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 14px;
    backdrop-filter: blur(12px);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    cursor: default;
}

.ct-hero-contact-item:hover {
    background: rgba(255,255,255,0.09);
    border-color: rgba(255,255,255,0.18);
    transform: translateY(-2px);
}

.ct-hero-contact-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}

.ct-hero-contact-label {
    font-size: 0.72rem;
    color: var(--ct-light);
    font-weight: 500;
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.ct-hero-contact-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: #ffffff;
}

a.ct-hero-contact-item .ct-hero-contact-value { transition: color 0.2s; }
a.ct-hero-contact-item:hover .ct-hero-contact-value { color: #7dd3fc; }

/* ═══════════════════════════════
   SECTIONS
   ═══════════════════════════════ */
.ct-section { padding: 80px 0; }
.ct-section-alt { background: #ffffff; }

.ct-section-header { margin-bottom: 40px; }

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

/* ═══════════════════════════════
   INFO CARDS
   ═══════════════════════════════ */
.ct-info-card {
    background: var(--ct-card);
    border: 1px solid var(--ct-border);
    border-radius: var(--ct-radius-lg);
    box-shadow: var(--ct-shadow-sm);
    height: 100%;
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.ct-info-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ct-shadow-md);
}

.ct-info-card-header {
    padding: 24px 26px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.ct-info-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.ct-info-card-header h5 {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--ct-text);
    margin-bottom: 2px;
}

.ct-info-card-header small {
    font-size: 0.78rem;
    color: var(--ct-muted);
}

.ct-info-card-body {
    padding: 0 26px 26px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

/* ── Office Info ── */
.ct-office-text {
    font-size: 0.92rem;
    color: var(--ct-muted);
    line-height: 1.7;
    margin-bottom: 24px;
}

/* Schedule */
.ct-schedule {
    margin-top: auto;
}

.ct-schedule-title {
    font-weight: 700;
    font-size: 0.82rem;
    color: var(--ct-text);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ct-schedule-title i { color: var(--ct-accent); font-size: 0.9rem; }

.ct-schedule-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--ct-border);
    font-size: 0.88rem;
}

.ct-schedule-row:last-child { border-bottom: none; }

.ct-schedule-day {
    font-weight: 600;
    color: var(--ct-text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.ct-schedule-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.ct-schedule-time {
    font-weight: 500;
    color: var(--ct-muted);
}

.ct-schedule-time.closed {
    color: var(--ct-red);
    font-weight: 600;
}

/* ── Emergency Contacts ── */
.ct-emergency-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: var(--ct-radius);
    background: rgba(0,0,0,0.015);
    border: 1px solid var(--ct-border);
    margin-bottom: 10px;
    transition: all 0.25s ease;
}

.ct-emergency-item:last-child { margin-bottom: 0; }

.ct-emergency-item:hover {
    background: rgba(0,0,0,0.03);
    border-color: rgba(0,0,0,0.08);
}

.ct-emergency-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.ct-emergency-info { flex-grow: 1; }

.ct-emergency-label {
    font-size: 0.75rem;
    color: var(--ct-muted);
    font-weight: 500;
    margin-bottom: 1px;
}

.ct-emergency-value {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--ct-text);
}

.ct-emergency-value a {
    color: var(--ct-primary);
    text-decoration: none;
    transition: opacity 0.2s;
}

.ct-emergency-value a:hover { opacity: 0.7; text-decoration: underline; }

/* ── Social Links ── */
.ct-social-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--ct-border);
}

.ct-social-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
    border: 1px solid var(--ct-border);
    background: transparent;
    color: var(--ct-text);
    cursor: pointer;
}

.ct-social-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--ct-shadow-sm);
}

.ct-social-btn i { font-size: 1rem; }

/* ═══════════════════════════════
   DIRECTORY CARDS
   ═══════════════════════════════ */
.ct-dir-card {
    background: var(--ct-card);
    border: 1px solid var(--ct-border);
    border-radius: var(--ct-radius-lg);
    box-shadow: var(--ct-shadow-sm);
    padding: 22px 20px;
    height: 100%;
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    position: relative;
    overflow: hidden;
}

.ct-dir-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.ct-dir-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ct-shadow-md);
}

.ct-dir-card:hover::before { opacity: 1; }

.ct-dir-name {
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--ct-text);
    margin-bottom: 2px;
}

.ct-dir-position {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 12px;
}

.ct-dir-contact-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: var(--ct-muted);
    padding: 5px 0;
}

.ct-dir-contact-row i {
    font-size: 0.82rem;
    width: 16px;
    text-align: center;
    flex-shrink: 0;
}

.ct-dir-contact-row a {
    color: var(--ct-primary);
    text-decoration: none;
}

.ct-dir-contact-row a:hover { text-decoration: underline; }

/* ═══════════════════════════════
   MAP SECTION
   ═══════════════════════════════ */
.ct-map-card {
    background: var(--ct-card);
    border: 1px solid var(--ct-border);
    border-radius: var(--ct-radius-lg);
    box-shadow: var(--ct-shadow-md);
    overflow: hidden;
}

.ct-map-wrapper {
    width: 100%;
    height: 380px;
    background: linear-gradient(135deg, #1e293b, #0f172a);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 14px;
    position: relative;
    overflow: hidden;
}

.ct-map-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 30% 40%, rgba(14,165,233,0.06) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 60%, rgba(20,184,166,0.04) 0%, transparent 40%);
    pointer-events: none;
}

.ct-map-placeholder-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: rgba(255,255,255,0.3);
}

.ct-map-placeholder-text {
    font-size: 0.92rem;
    font-weight: 600;
    color: rgba(255,255,255,0.35);
    text-align: center;
    padding: 0 20px;
}

.ct-map-wrapper iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* ═══════════════════════════════
   CTA FOOTER
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
    margin: 0 auto 24px;
    line-height: 1.7;
}

.ct-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 0.92rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    cursor: pointer;
    border: none;
    color: #fff;
    background: linear-gradient(135deg, var(--ct-accent), var(--ct-accent-dark));
    box-shadow: 0 4px 16px rgba(14,165,233,0.3);
}

.ct-cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(14,165,233,0.4);
    color: #fff;
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
.ct-d7 { transition-delay: 0.35s; }
.ct-d8 { transition-delay: 0.4s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .ct-hero-contact-row { gap: 10px; }
}

@media (max-width: 991.98px) {
    .ct-hero { padding: 80px 0 60px; }
    .ct-section { padding: 60px 0; }
    .ct-cta-section { padding: 60px 0; }
    .ct-map-wrapper { height: 300px; }
}

@media (max-width: 767.98px) {
    .ct-hero { padding: 70px 0 50px; }
    .ct-section { padding: 50px 0; }
    .ct-cta-section { padding: 50px 0; }
    .ct-cta-card { padding: 40px 24px; }
    .ct-info-card-header { padding: 20px 20px 14px; }
    .ct-info-card-body { padding: 0 20px 20px; }
    .ct-hero-contact-item { padding: 12px 16px; }
    .ct-hero-contact-icon { width: 36px; height: 36px; font-size: 0.9rem; }
    .ct-hero-contact-value { font-size: 0.82rem; }
    .ct-section-header { margin-bottom: 28px; }
    .ct-map-wrapper { height: 260px; }
}

@media (max-width: 480px) {
    .ct-hero-contact-row { flex-direction: column; }
    .ct-hero-contact-item { width: 100%; }
    .ct-cta-card { padding: 32px 18px; }
    .ct-schedule-row { font-size: 0.82rem; }
}
</style>

<!-- ═══════════════════════════════════════
     HERO
     ═══════════════════════════════════════ -->
<section class="ct-hero">
    <div class="ct-hero-grid"></div>
    <div class="ct-floating-orb o1"></div>
    <div class="ct-floating-orb o2"></div>
    <div class="ct-floating-orb o3"></div>

    <div class="container ct-hero-content">
        <div class="ct-breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house"></i> Home</a>
            <span class="ct-sep">/</span>
            <span class="ct-current">Contact Us</span>
        </div>

        <div class="ct-hero-badge">
            <span class="ct-dot"></span>
            Get in Touch
        </div>

        <h1 class="ct-hero-title">Contact <span><?php echo e($barangayName); ?></span></h1>
        <p class="ct-hero-desc">We're here to help. Reach out to our barangay office for services, inquiries, emergency assistance, or community concerns.</p>

        <div class="ct-hero-contact-row">
            <?php if (!empty($officePhone)): ?>
            <a href="tel:<?php echo e($officePhone); ?>" class="ct-hero-contact-item ct-reveal ct-d1">
                <div class="ct-hero-contact-icon" style="background:rgba(14,165,233,0.15); color:#0ea5e9;">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <div>
                    <div class="ct-hero-contact-label">Call Us</div>
                    <div class="ct-hero-contact-value"><?php echo e($officePhone); ?></div>
                </div>
            </a>
            <?php endif; ?>
            <?php if (!empty($officeEmail)): ?>
            <a href="mailto:<?php echo e($officeEmail); ?>" class="ct-hero-contact-item ct-reveal ct-d2">
                <div class="ct-hero-contact-icon" style="background:rgba(20,184,166,0.15); color:#14b8a6;">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <div class="ct-hero-contact-label">Email</div>
                    <div class="ct-hero-contact-value"><?php echo e($officeEmail); ?></div>
                </div>
            </a>
            <?php endif; ?>
            <div class="ct-hero-contact-item ct-reveal ct-d3">
                <div class="ct-hero-contact-icon" style="background:rgba(239,68,68,0.15); color:#ef4444;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <div class="ct-hero-contact-label">Emergency</div>
                    <div class="ct-hero-contact-value">911</div>
                </div>
            </div>
            <?php if (!empty($officeAddress)): ?>
            <div class="ct-hero-contact-item ct-reveal ct-d4">
                <div class="ct-hero-contact-icon" style="background:rgba(245,158,11,0.15); color:#f59e0b;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <div class="ct-hero-contact-label">Location</div>
                    <div class="ct-hero-contact-value"><?php echo e($officeAddress); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     OFFICE & EMERGENCY INFO
     ═══════════════════════════════════════ -->
<section class="ct-section">
    <div class="container">
        <div class="ct-section-header ct-reveal">
            <div class="ct-section-tag"><i class="bi bi-building"></i> Information</div>
            <h2 class="ct-section-title">Office Details</h2>
            <p class="ct-section-subtitle">Everything you need to reach us — office hours, contact details, and emergency hotlines.</p>
        </div>

        <div class="row g-4">
            <!-- Office Info + Schedule -->
            <div class="col-lg-6">
                <div class="ct-info-card ct-reveal ct-d1">
                    <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(135deg,#0ea5e9,#38bdf8);"></div>
                    <div class="ct-info-card-header">
                        <div class="ct-info-card-icon" style="background:rgba(14,165,233,0.10); color:#0ea5e9;">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <h5>Barangay Hall</h5>
                            <small>Main Office</small>
                        </div>
                    </div>
                    <div class="ct-info-card-body">
                        <div class="ct-office-text"><?php echo nl2br(e($contactContent)); ?></div>

                        <div class="ct-schedule">
                            <div class="ct-schedule-title"><i class="bi bi-clock"></i> Office Hours</div>
                            <div class="ct-schedule-row">
                                <span class="ct-schedule-day"><span class="ct-schedule-dot" style="background:var(--ct-green);"></span> Monday</span>
                                <span class="ct-schedule-time">8:00 AM — 5:00 PM</span>
                            </div>
                            <div class="ct-schedule-row">
                                <span class="ct-schedule-day"><span class="ct-schedule-dot" style="background:var(--ct-green);"></span> Tuesday</span>
                                <span class="ct-schedule-time">8:00 AM — 5:00 PM</span>
                            </div>
                            <div class="ct-schedule-row">
                                <span class="ct-schedule-day"><span class="ct-schedule-dot" style="background:var(--ct-green);"></span> Wednesday</span>
                                <span class="ct-schedule-time">8:00 AM — 5:00 PM</span>
                            </div>
                            <div class="ct-schedule-row">
                                <span class="ct-schedule-day"><span class="ct-schedule-dot" style="background:var(--ct-green);"></span> Thursday</span>
                                <span class="ct-schedule-time">8:00 AM — 5:00 PM</span>
                            </div>
                            <div class="ct-schedule-row">
                                <span class="ct-schedule-day"><span class="ct-schedule-dot" style="background:var(--ct-green);"></span> Friday</span>
                                <span class="ct-schedule-time">8:00 AM — 5:00 PM</span>
                            </div>
                            <div class="ct-schedule-row">
                                <span class="ct-schedule-day"><span class="ct-schedule-dot" style="background:var(--ct-amber);"></span> Saturday</span>
                                <span class="ct-schedule-time">8:00 AM — 12:00 PM</span>
                            </div>
                            <div class="ct-schedule-row">
                                <span class="ct-schedule-day"><span class="ct-schedule-dot" style="background:var(--ct-red);"></span> Sunday</span>
                                <span class="ct-schedule-time closed">Closed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency + Social -->
            <div class="col-lg-6">
                <div class="ct-info-card ct-reveal ct-d2">
                    <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(135deg,#ef4444,#f87171);"></div>
                    <div class="ct-info-card-header">
                        <div class="ct-info-card-icon" style="background:rgba(239,68,68,0.10); color:#ef4444;">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                        <div>
                            <h5>Emergency Contacts</h5>
                            <small>Hotlines & direct lines</small>
                        </div>
                    </div>
                    <div class="ct-info-card-body">
                        <div class="ct-emergency-item">
                            <div class="ct-emergency-icon" style="background:rgba(239,68,68,0.10); color:#ef4444;">
                                <i class="bi bi-exclamation-octagon-fill"></i>
                            </div>
                            <div class="ct-emergency-info">
                                <div class="ct-emergency-label">Emergency Hotline</div>
                                <div class="ct-emergency-value"><a href="tel:911">911</a></div>
                            </div>
                        </div>

                        <div class="ct-emergency-item">
                            <div class="ct-emergency-icon" style="background:rgba(14,165,233,0.10); color:#0ea5e9;">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="ct-emergency-info">
                                <div class="ct-emergency-label">Barangay Hall</div>
                                <div class="ct-emergency-value">
                                    <?php if (!empty($officePhone)): ?>
                                        <a href="tel:<?php echo e($officePhone); ?>"><?php echo e($officePhone); ?></a>
                                    <?php else: ?>
                                        <a href="tel:(02) 123-4567">(02) 123-4567</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="ct-emergency-item">
                            <div class="ct-emergency-icon" style="background:rgba(20,184,166,0.10); color:#14b8a6;">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div class="ct-emergency-info">
                                <div class="ct-emergency-label">Official Email</div>
                                <div class="ct-emergency-value">
                                    <?php if (!empty($officeEmail)): ?>
                                        <a href="mailto:<?php echo e($officeEmail); ?>"><?php echo e($officeEmail); ?></a>
                                    <?php else: ?>
                                        <a href="mailto:barangay@tumalaytay.gov.ph">barangay@tumalaytay.gov.ph</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="ct-emergency-item">
                            <div class="ct-emergency-icon" style="background:rgba(139,92,246,0.10); color:#8b5cf6;">
                                <i class="bi bi-shield-fill-check"></i>
                            </div>
                            <div class="ct-emergency-info">
                                <div class="ct-emergency-label">BFP / Fire Station</div>
                                <div class="ct-emergency-value"><a href="tel:(02) 123-8888">(02) 123-8888</a></div>
                            </div>
                        </div>

                        <div class="ct-emergency-item">
                            <div class="ct-emergency-icon" style="background:rgba(245,158,11,0.10); color:#f59e0b;">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="ct-emergency-info">
                                <div class="ct-emergency-label">MDRRMO / Rescue</div>
                                <div class="ct-emergency-value"><a href="tel:(02) 123-9999">(02) 123-9999</a></div>
                            </div>
                        </div>

                        <div class="ct-social-row">
                            <a href="<?php echo e($fbLink); ?>" target="_blank" class="ct-social-btn" style="color:#1877f2;" rel="noopener">
                                <i class="bi bi-facebook"></i> Facebook
                            </a>
                            <a href="#" class="ct-social-btn" style="color:#1da1f2;">
                                <i class="bi bi-twitter-x"></i> Twitter / X
                            </a>
                            <a href="mailto:<?php echo e($officeEmail ?: 'barangay@tumalaytay.gov.ph'); ?>" class="ct-social-btn" style="color:#ea4335;">
                                <i class="bi bi-envelope"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     OFFICIALS DIRECTORY (compact)
     ═══════════════════════════════════════ -->
<?php if (!empty($contactOfficials)): ?>
<section class="ct-section ct-section-alt">
    <div class="container">
        <div class="ct-section-header ct-reveal">
            <div class="ct-section-tag"><i class="bi bi-people"></i> Directory</div>
            <h2 class="ct-section-title">Key Officials</h2>
            <p class="ct-section-subtitle">Direct contact information for barangay officials you can reach out to.</p>
        </div>

        <div class="row g-4">
            <?php
            $dirColors = [
                'captain'   => ['color' => '#f2b544', 'bg' => 'rgba(242,181,68,0.10)', 'gradient' => 'linear-gradient(135deg,#f2b544,#fbbf24)'],
                'executive' => ['color' => '#2f7bff', 'bg' => 'rgba(47,123,255,0.10)', 'gradient' => 'linear-gradient(135deg,#2f7bff,#60a5fa)'],
                'kagawad'   => ['color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.10)', 'gradient' => 'linear-gradient(135deg,#8b5cf6,#a78bfa)'],
                'sk'        => ['color' => '#35d18f', 'bg' => 'rgba(53,209,143,0.10)', 'gradient' => 'linear-gradient(135deg,#35d18f,#22c55e)'],
            ];
            foreach ($contactOfficials as $i => $off):
                $tier = $off['tier'] ?? 'kagawad';
                $dc = $dirColors[$tier] ?? $dirColors['kagawad'];
                $delay = 'ct-d' . min($i + 1, 6);
            ?>
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="ct-dir-card ct-reveal <?php echo $delay; ?>">
                        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?php echo $dc['gradient']; ?>;"></div>
                        <div class="ct-dir-name"><?php echo e($off['official_name']); ?></div>
                        <div class="ct-dir-position" style="color:<?php echo $dc['color']; ?>;"><?php echo e($off['position_title']); ?></div>
                        <?php if (!empty($off['contact_number'])): ?>
                            <div class="ct-dir-contact-row">
                                <i class="bi bi-telephone" style="color:var(--ct-green);"></i>
                                <a href="tel:<?php echo e($off['contact_number']); ?>"><?php echo e($off['contact_number']); ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($off['email'])): ?>
                            <div class="ct-dir-contact-row">
                                <i class="bi bi-envelope" style="color:var(--ct-primary);"></i>
                                <a href="mailto:<?php echo e($off['email']); ?>"><?php echo e($off['email']); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════
     MAP
     ═══════════════════════════════════════ -->
<section class="ct-section">
    <div class="container">
        <div class="ct-section-header ct-reveal">
            <div class="ct-section-tag"><i class="bi bi-geo-alt"></i> Location</div>
            <h2 class="ct-section-title">Find Us</h2>
            <p class="ct-section-subtitle">Visit the barangay hall for in-person inquiries and document processing.</p>
        </div>

        <div class="ct-map-card ct-reveal ct-d1">
            <?php if (!empty($mapEmbed)): ?>
                <div class="ct-map-wrapper" style="height:400px;padding:0;">
                    <?php echo $mapEmbed; ?>
                </div>
            <?php else: ?>
                <div class="ct-map-wrapper">
                    <div class="ct-map-placeholder-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="ct-map-placeholder-text"><?php echo e($officeAddress ?: 'Barangay Hall, ' . $barangayName); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     CTA
     ═══════════════════════════════════════ -->
<section class="ct-cta-section">
    <div class="container">
        <div class="ct-cta-card ct-reveal">
            <div class="ct-cta-content">
                <div class="ct-cta-icon"><i class="bi bi-heart-pulse"></i></div>
                <h3><?php echo e($barangayName); ?></h3>
                <p><?php echo e($footerContent); ?></p>
                <a href="<?php echo BASE_URL; ?>/index.php" class="ct-cta-btn">
                    <i class="bi bi-house"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.ct-reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('ct-visible');
            }
        });
    }, { threshold: 0.06, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function(el) { obs.observe(el); });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>