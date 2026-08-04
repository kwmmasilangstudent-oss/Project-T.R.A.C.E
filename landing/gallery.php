<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Gallery - ' . APP_NAME;
$pageDescription = 'Browse photos and images showcasing Barangay Tumalaytay events, programs, and community life.';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = getDbConnection();
$gallery = [];
try {
    $gallery = $pdo->query('SELECT * FROM gallery ORDER BY created_at DESC')->fetchAll();
} catch (Throwable $e) {
    $gallery = [];
}

/* ── Detect available columns ── */
$galleryCols = [];
if (!empty($gallery)) {
    $galleryCols = array_keys($gallery[0]);
}

/* ── Extract unique categories if column exists ── */
$categories = ['all'];
$hasCategory = in_array('category', $galleryCols);
if ($hasCategory) {
    foreach ($gallery as $item) {
        $cat = trim($item['category'] ?? '');
        if ($cat !== '' && !in_array($cat, $categories)) {
            $categories[] = $cat;
        }
    }
}

$barangayName = 'Barangay Tumalaytay';
try {
    $n = $pdo->prepare("SELECT key_value FROM settings WHERE key_name = 'barangay_name'");
    $n->execute();
    $v = $n->fetchColumn();
    if ($v) $barangayName = $v;
} catch (Throwable $e) {}

$heroBg = getSetting('hero_background', '');

$totalItems = count($gallery);
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
    --gl-primary: #1a56db;
    --gl-primary-dark: #1042a3;
    --gl-primary-light: #e8effc;
    --gl-accent: #f59e0b;
    --gl-accent-dark: #d97706;
    --gl-green: #10b981;
    --gl-red: #ef4444;
    --gl-violet: #8b5cf6;
    --gl-rose: #f43f5e;
    --gl-bg: #f0f4f8;
    --gl-card: #ffffff;
    --gl-hero-bg: #0f172a;
    --gl-text: #0f172a;
    --gl-muted: #64748b;
    --gl-light: #94a3b8;
    --gl-border: #e2e8f0;
    --gl-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --gl-shadow-md: 0 4px 20px rgba(0,0,0,0.08);
    --gl-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
    --gl-shadow-xl: 0 20px 60px rgba(0,0,0,0.15);
    --gl-radius: 12px;
    --gl-radius-lg: 20px;
    --gl-radius-xl: 28px;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--gl-bg);
    color: var(--gl-text);
    overflow-x: hidden;
}

/* ═══════════════════════════════
   HERO
   ═══════════════════════════════ */
    .gl-hero {
        position: relative;
        padding: 100px 0 80px;
        background: var(--gl-hero-bg);
        overflow: hidden;
    }

    <?php if (!empty($heroBg)): ?>
    .gl-hero {
        background: linear-gradient(160deg, rgba(15,23,42,0.93), rgba(15,23,42,0.87)),
                    url('<?php echo asset($heroBg); ?>') center/cover no-repeat;
    }
    <?php endif; ?>

.gl-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 25% 40%, rgba(244,63,94,0.10) 0%, transparent 50%),
        radial-gradient(ellipse at 75% 60%, rgba(245,158,11,0.08) 0%, transparent 45%),
        radial-gradient(ellipse at 50% 10%, rgba(139,92,246,0.07) 0%, transparent 40%);
    pointer-events: none;
}

.gl-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
}

.gl-hero-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
}

.gl-floating-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    animation: glFloat 20s ease-in-out infinite;
}

.gl-floating-orb.o1 { width: 350px; height: 350px; background: rgba(244,63,94,0.09); top: -18%; left: -5%; }
.gl-floating-orb.o2 { width: 280px; height: 280px; background: rgba(245,158,11,0.07); bottom: -12%; right: -5%; animation-delay: -10s; }
.gl-floating-orb.o3 { width: 200px; height: 200px; background: rgba(139,92,246,0.06); top: 20%; right: 20%; animation-delay: -5s; animation-duration: 25s; }

@keyframes glFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

.gl-hero-content { position: relative; z-index: 2; }

/* Breadcrumb */
.gl-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 28px;
    font-size: 0.82rem;
}

.gl-breadcrumb a { color: var(--gl-light); text-decoration: none; transition: color 0.2s; }
.gl-breadcrumb a:hover { color: #fff; }
.gl-breadcrumb .gl-sep { color: rgba(255,255,255,0.2); }
.gl-breadcrumb .gl-current { color: #fda4af; font-weight: 600; }

/* Badge */
.gl-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    background: rgba(244,63,94,0.12);
    border: 1px solid rgba(244,63,94,0.25);
    border-radius: 100px;
    color: #fda4af;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.gl-hero-badge .gl-dot {
    width: 8px; height: 8px;
    background: var(--gl-rose);
    border-radius: 50%;
    animation: glPulse 2s ease-in-out infinite;
}

@keyframes glPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

/* Title */
.gl-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 900;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 16px;
}

.gl-hero-title span {
    background: linear-gradient(135deg, var(--gl-rose), #fb7185);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.gl-hero-desc {
    font-size: 1.1rem;
    color: #94a3b8;
    max-width: 600px;
    line-height: 1.7;
    margin-bottom: 32px;
}

/* Hero Stats */
.gl-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
}

.gl-meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    backdrop-filter: blur(12px);
    transition: all 0.3s ease;
}

.gl-meta-pill:hover {
    background: rgba(255,255,255,0.09);
    border-color: rgba(255,255,255,0.18);
}

.gl-meta-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.gl-meta-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 1px;
}

.gl-meta-label {
    font-size: 0.72rem;
    color: var(--gl-light);
    font-weight: 500;
}

/* ═══════════════════════════════
   FILTER BAR
   ═══════════════════════════════ */
.gl-filter-bar {
    padding: 28px 0;
    position: relative;
    z-index: 3;
    margin-top: -30px;
}

.gl-filter-inner {
    background: var(--gl-card);
    border: 1px solid var(--gl-border);
    border-radius: var(--gl-radius-lg);
    box-shadow: var(--gl-shadow-md);
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}

.gl-filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.gl-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 100px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--gl-muted);
    background: transparent;
    border: 1px solid var(--gl-border);
    cursor: pointer;
    transition: all 0.25s ease;
    font-family: 'Inter', sans-serif;
    white-space: nowrap;
}

.gl-filter-pill:hover {
    color: var(--gl-primary);
    border-color: var(--gl-primary);
    background: var(--gl-primary-light);
}

.gl-filter-pill.active {
    color: #fff;
    background: var(--gl-primary);
    border-color: var(--gl-primary);
    box-shadow: 0 2px 10px rgba(26,86,219,0.25);
}

.gl-filter-count {
    font-size: 0.82rem;
    color: var(--gl-light);
    font-weight: 500;
    white-space: nowrap;
}

/* ═══════════════════════════════
   GALLERY GRID
   ═══════════════════════════════ */
.gl-gallery-section {
    padding: 0 0 100px;
}

.gl-gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* ── Gallery Item ── */
.gl-item {
    position: relative;
    border-radius: var(--gl-radius-lg);
    overflow: hidden;
    background: var(--gl-card);
    border: 1px solid var(--gl-border);
    box-shadow: var(--gl-shadow-sm);
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    cursor: pointer;
}

.gl-item:hover {
    transform: translateY(-6px);
    box-shadow: var(--gl-shadow-xl);
    border-color: transparent;
}

.gl-item:nth-child(4n+1) {
    grid-column: span 1;
}

/* Make first item and every 5th item larger */
.gl-item.gl-featured {
    grid-column: span 2;
    grid-row: span 2;
}

.gl-item-img-wrap {
    position: relative;
    width: 100%;
    overflow: hidden;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
}

.gl-item:not(.gl-featured) .gl-item-img-wrap {
    height: 240px;
}

.gl-featured .gl-item-img-wrap {
    height: 100%;
    min-height: 400px;
}

.gl-item-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.gl-item:hover .gl-item-img-wrap img {
    transform: scale(1.06);
}

/* Placeholder for items without images */
.gl-item-placeholder {
    width: 100%;
    height: 100%;
    min-height: 240px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    overflow: hidden;
}

.gl-item-placeholder::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 30% 40%, rgba(244,63,94,0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 60%, rgba(139,92,246,0.06) 0%, transparent 40%);
    pointer-events: none;
}

.gl-featured .gl-item-placeholder { min-height: 400px; }

.gl-placeholder-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    color: rgba(255,255,255,0.3);
}

.gl-placeholder-title {
    font-size: 0.88rem;
    font-weight: 600;
    color: rgba(255,255,255,0.4);
    text-align: center;
    padding: 0 16px;
}

/* Overlay on hover */
.gl-item-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15,23,42,0.90) 0%, rgba(15,23,42,0.3) 40%, transparent 70%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 24px;
    opacity: 0;
    transition: opacity 0.35s ease;
}

.gl-item:hover .gl-item-overlay { opacity: 1; }

/* Always show info at bottom for non-featured */
.gl-item-info {
    padding: 18px 20px;
    background: var(--gl-card);
    border-top: 1px solid var(--gl-border);
}

.gl-item-title {
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--gl-text);
    margin-bottom: 4px;
    line-height: 1.3;
}

.gl-item-meta {
    font-size: 0.78rem;
    color: var(--gl-light);
    display: flex;
    align-items: center;
    gap: 12px;
}

.gl-item-meta i { font-size: 0.75rem; }

/* Featured overlay info */
.gl-featured .gl-item-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(15,23,42,0.95), transparent);
    border-top: none;
    padding: 40px 28px 24px;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.4s ease;
}

.gl-item.gl-featured:hover .gl-item-info {
    opacity: 1;
    transform: translateY(0);
}

.gl-featured .gl-item-title {
    color: #ffffff;
    font-size: 1.15rem;
}

.gl-featured .gl-item-meta { color: #94a3b8; }

/* Category badge on item */
.gl-item-cat {
    position: absolute;
    top: 14px;
    left: 14px;
    z-index: 3;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: rgba(15,23,42,0.70);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 100px;
    font-size: 0.68rem;
    font-weight: 700;
    color: #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0;
    transform: translateY(-6px);
    transition: all 0.3s ease;
}

.gl-item:hover .gl-item-cat {
    opacity: 1;
    transform: translateY(0);
}

/* Zoom icon */
.gl-item-zoom {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 3;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(15,23,42,0.70);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.10);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e2e8f0;
    font-size: 0.85rem;
    opacity: 0;
    transform: translateY(-6px);
    transition: all 0.3s ease 0.05s;
}

.gl-item:hover .gl-item-zoom {
    opacity: 1;
    transform: translateY(0);
}

/* ═══════════════════════════════
   LIGHTBOX
   ═══════════════════════════════ */
.gl-lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.92);
    backdrop-filter: blur(20px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.35s ease;
}

.gl-lightbox.active {
    opacity: 1;
    visibility: visible;
}

.gl-lightbox-inner {
    position: relative;
    max-width: 90vw;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    transform: scale(0.92);
    transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.gl-lightbox.active .gl-lightbox-inner {
    transform: scale(1);
}

.gl-lightbox-img {
    max-width: 100%;
    max-height: 75vh;
    border-radius: var(--gl-radius);
    object-fit: contain;
    box-shadow: 0 20px 80px rgba(0,0,0,0.5);
}

.gl-lightbox-placeholder {
    width: 500px;
    max-width: 90vw;
    height: 350px;
    border-radius: var(--gl-radius);
    background: linear-gradient(135deg, #1e293b, #0f172a);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    box-shadow: 0 20px 80px rgba(0,0,0,0.5);
}

.gl-lightbox-placeholder i {
    font-size: 3rem;
    color: rgba(255,255,255,0.2);
}

.gl-lightbox-placeholder span {
    font-size: 1rem;
    color: rgba(255,255,255,0.35);
    font-weight: 600;
}

.gl-lightbox-caption {
    margin-top: 18px;
    text-align: center;
    color: #e2e8f0;
}

.gl-lightbox-caption h4 {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.gl-lightbox-caption p {
    font-size: 0.85rem;
    color: #94a3b8;
}

.gl-lightbox-close {
    position: absolute;
    top: -50px;
    right: 0;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.gl-lightbox-close:hover {
    background: rgba(239,68,68,0.2);
    border-color: rgba(239,68,68,0.4);
}

.gl-lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.gl-lightbox-nav:hover {
    background: rgba(255,255,255,0.15);
}

.gl-lightbox-prev { left: -60px; }
.gl-lightbox-next { right: -60px; }

.gl-lightbox-counter {
    margin-top: 14px;
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 500;
}

/* ═══════════════════════════════
   EMPTY STATE
   ═══════════════════════════════ */
.gl-empty {
    background: var(--gl-card);
    border: 1px solid var(--gl-border);
    border-radius: var(--gl-radius-xl);
    box-shadow: var(--gl-shadow-md);
    padding: 80px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.gl-empty::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 30% 30%, rgba(244,63,94,0.04) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 70%, rgba(139,92,246,0.03) 0%, transparent 40%);
    pointer-events: none;
}

.gl-empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: rgba(244,63,94,0.08);
    border: 1px solid rgba(244,63,94,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--gl-rose);
    margin: 0 auto 20px;
    position: relative;
}

.gl-empty-icon::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 24px;
    border: 2px dashed rgba(244,63,94,0.12);
}

.gl-empty h4 {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.4rem;
    color: var(--gl-text);
    margin-bottom: 10px;
}

.gl-empty p {
    font-size: 0.95rem;
    color: var(--gl-muted);
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.6;
}

/* ═══════════════════════════════
   SCROLL REVEAL
   ═══════════════════════════════ */
.gl-reveal {
    opacity: 0;
    transform: translateY(35px);
    transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.gl-reveal.gl-visible {
    opacity: 1;
    transform: translateY(0);
}

.gl-d1 { transition-delay: 0.05s; }
.gl-d2 { transition-delay: 0.1s; }
.gl-d3 { transition-delay: 0.15s; }
.gl-d4 { transition-delay: 0.2s; }
.gl-d5 { transition-delay: 0.25s; }
.gl-d6 { transition-delay: 0.3s; }
.gl-d7 { transition-delay: 0.35s; }
.gl-d8 { transition-delay: 0.4s; }
.gl-d9 { transition-delay: 0.45s; }

/* Hidden by filter */
.gl-item.gl-hidden {
    display: none;
}

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .gl-featured .gl-item-img-wrap { min-height: 320px; }
    .gl-featured .gl-item-placeholder { min-height: 320px; }
}

@media (max-width: 991.98px) {
    .gl-hero { padding: 80px 0 60px; }
    .gl-gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .gl-item.gl-featured { grid-column: span 2; grid-row: span 1; }
    .gl-featured .gl-item-img-wrap { min-height: 280px; }
    .gl-featured .gl-item-placeholder { min-height: 280px; }
    .gl-item:not(.gl-featured) .gl-item-img-wrap { height: 200px; }
    .gl-lightbox-prev { left: 10px; }
    .gl-lightbox-next { right: 10px; }
}

@media (max-width: 767.98px) {
    .gl-hero { padding: 70px 0 50px; }
    .gl-gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .gl-item.gl-featured { grid-column: span 1; }
    .gl-featured .gl-item-img-wrap,
    .gl-featured .gl-item-placeholder { min-height: 200px; }
    .gl-featured .gl-item-info {
        opacity: 1;
        transform: none;
        position: relative;
        background: var(--gl-card);
        padding: 14px 16px;
        border-top: 1px solid var(--gl-border);
    }
    .gl-featured .gl-item-title { color: var(--gl-text); font-size: 0.9rem; }
    .gl-featured .gl-item-meta { color: var(--gl-light); }
    .gl-item-cat { opacity: 1; transform: none; }
    .gl-item-zoom { opacity: 1; transform: none; }
    .gl-item-overlay { opacity: 1; }
    .gl-filter-inner { padding: 14px 18px; }
    .gl-filter-pill { padding: 6px 12px; font-size: 0.75rem; }
    .gl-lightbox-nav { display: none; }
    .gl-empty { padding: 50px 24px; }
}

@media (max-width: 480px) {
    .gl-gallery-grid { grid-template-columns: 1fr; }
    .gl-item.gl-featured { grid-column: span 1; }
    .gl-hero-meta { gap: 10px; }
    .gl-meta-pill { padding: 8px 14px; }
}
</style>

<!-- ═══════════════════════════════════════
     HERO
     ═══════════════════════════════════════ -->
<section class="gl-hero">
    <div class="gl-hero-grid"></div>
    <div class="gl-floating-orb o1"></div>
    <div class="gl-floating-orb o2"></div>
    <div class="gl-floating-orb o3"></div>

    <div class="container gl-hero-content">
        <div class="gl-breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house"></i> Home</a>
            <span class="gl-sep">/</span>
            <span class="gl-current">Gallery</span>
        </div>

        <div class="gl-hero-badge">
            <span class="gl-dot"></span>
            Photo Gallery
        </div>

        <h1 class="gl-hero-title">Community <span>Gallery</span></h1>
        <p class="gl-hero-desc">Moments and memories from <?php echo e($barangayName); ?> events, activities, and community programs.</p>

        <div class="gl-hero-meta">
            <div class="gl-meta-pill gl-reveal gl-d1">
                <div class="gl-meta-icon" style="background:rgba(244,63,94,0.15); color:#f43f5e;">
                    <i class="bi bi-images"></i>
                </div>
                <div>
                    <div class="gl-meta-value"><?php echo $totalItems; ?></div>
                    <div class="gl-meta-label">Photos</div>
                </div>
            </div>
            <?php if ($hasCategory && count($categories) > 1): ?>
            <div class="gl-meta-pill gl-reveal gl-d2">
                <div class="gl-meta-icon" style="background:rgba(139,92,246,0.15); color:#8b5cf6;">
                    <i class="bi bi-bookmark"></i>
                </div>
                <div>
                    <div class="gl-meta-value"><?php echo count($categories) - 1; ?></div>
                    <div class="gl-meta-label">Categories</div>
                </div>
            </div>
            <?php endif; ?>
            <div class="gl-meta-pill gl-reveal gl-d3">
                <div class="gl-meta-icon" style="background:rgba(245,158,11,0.15); color:#f59e0b;">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="gl-meta-value"><?php echo e($barangayName); ?></div>
                    <div class="gl-meta-label">Community</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($gallery)): ?>

<!-- ═══════════════════════════════════════
     FILTER BAR
     ═══════════════════════════════════════ -->
<?php if ($hasCategory && count($categories) > 1): ?>
<div class="gl-filter-bar">
    <div class="container">
        <div class="gl-filter-inner gl-reveal">
            <div class="gl-filter-pills">
                <?php foreach ($categories as $cat):
                    $label = ($cat === 'all') ? 'All Photos' : e(ucfirst($cat));
                ?>
                    <button class="gl-filter-pill<?php echo ($cat === 'all') ? ' active' : ''; ?>" data-filter="<?php echo e($cat); ?>">
                        <?php if ($cat === 'all'): ?>
                            <i class="bi bi-grid-3x3-gap"></i>
                        <?php endif; ?>
                        <?php echo $label; ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="gl-filter-count"><?php echo $totalItems; ?> photo<?php echo $totalItems !== 1 ? 's' : ''; ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════
     GALLERY GRID
     ═══════════════════════════════════════ -->
<section class="gl-gallery-section">
    <div class="container">
        <div class="gl-gallery-grid" id="glGrid">
            <?php foreach ($gallery as $i => $item):
                $title = $item['title'] ?? 'Untitled';
                $imagePath = $item['image_path'] ?? $item['image'] ?? $item['photo'] ?? $item['file_path'] ?? '';
                $itemCategory = $hasCategory ? ($item['category'] ?? '') : '';
                $itemDate = $item['created_at'] ?? '';
                $isFeatured = ($i === 0 && $totalItems > 4);
                $delay = 'gl-d' . min(($i % 9) + 1, 9);
                $hasImage = !empty($imagePath);
            ?>
                <div class="gl-item gl-reveal <?php echo $delay; ?> <?php echo $isFeatured ? 'gl-featured' : ''; ?>"
                     data-category="<?php echo e($itemCategory); ?>"
                     data-index="<?php echo $i; ?>"
                     onclick="openLightbox(<?php echo $i; ?>)">

                    <?php if ($hasCategory && !empty($itemCategory)): ?>
                        <div class="gl-item-cat"><?php echo e(ucfirst($itemCategory)); ?></div>
                    <?php endif; ?>

                    <div class="gl-item-zoom"><i class="bi bi-zoom-in"></i></div>

                    <div class="gl-item-img-wrap">
                        <?php if ($hasImage): ?>
                            <img class="gl-item-img" src="<?php echo asset($imagePath); ?>"
                                 alt="<?php echo e($title); ?>"
                                 loading="lazy">
                            <div class="gl-item-placeholder" style="display:none;">
                                <div class="gl-placeholder-icon"><i class="bi bi-image"></i></div>
                                <div class="gl-placeholder-title"><?php echo e($title); ?></div>
                            </div>
                        <?php else: ?>
                            <div class="gl-item-placeholder">
                                <div class="gl-placeholder-icon"><i class="bi bi-image"></i></div>
                                <div class="gl-placeholder-title"><?php echo e($title); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isFeatured): ?>
                        <div class="gl-item-info">
                            <div class="gl-item-title"><?php echo e($title); ?></div>
                            <div class="gl-item-meta">
                                <?php if (!empty($itemDate)): ?>
                                    <span><i class="bi bi-calendar3"></i> <?php echo date('M d, Y', strtotime($itemDate)); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="gl-item-info">
                            <div class="gl-item-title"><?php echo e($title); ?></div>
                            <div class="gl-item-meta">
                                <?php if (!empty($itemDate)): ?>
                                    <span><i class="bi bi-calendar3"></i> <?php echo date('M d, Y', strtotime($itemDate)); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     LIGHTBOX
     ═══════════════════════════════════════ -->
<div class="gl-lightbox" id="glLightbox">
    <div class="gl-lightbox-inner">
        <button class="gl-lightbox-close" onclick="closeLightbox()"><i class="bi bi-x-lg"></i></button>
        <button class="gl-lightbox-nav gl-lightbox-prev" onclick="navLightbox(-1)"><i class="bi bi-chevron-left"></i></button>
        <button class="gl-lightbox-nav gl-lightbox-next" onclick="navLightbox(1)"><i class="bi bi-chevron-right"></i></button>
        <div id="glLightboxContent"></div>
        <div class="gl-lightbox-caption" id="glLightboxCaption"></div>
        <div class="gl-lightbox-counter" id="glLightboxCounter"></div>
    </div>
</div>

<?php else: ?>

<!-- ═══════════════════════════════════════
     EMPTY STATE
     ═══════════════════════════════════════ -->
<section class="gl-gallery-section" style="padding-top:60px;">
    <div class="container">
        <div class="gl-empty gl-reveal">
            <div class="gl-empty-icon">
                <i class="bi bi-images"></i>
            </div>
            <h4>Gallery Coming Soon</h4>
            <p>No photos have been uploaded yet. Check back soon for moments and memories from <?php echo e($barangayName); ?> events and activities.</p>
        </div>
    </div>
</section>

<?php endif; ?>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ── Scroll Reveal ── */
    var reveals = document.querySelectorAll('.gl-reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('gl-visible');
            }
        });
    }, { threshold: 0.06, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function(el) { obs.observe(el); });

    /* ── Image error fallback ── */
    document.addEventListener('error', function(e) {
        var t = e.target;
        if (t.tagName === 'IMG' && t.classList.contains('gl-item-img')) {
            t.style.display = 'none';
            var fb = t.nextElementSibling;
            if (fb && fb.classList.contains('gl-item-placeholder')) fb.style.display = 'flex';
        }
    }, true);

    /* ── Filter Pills ── */
    var pills = document.querySelectorAll('.gl-filter-pill');
    var items = document.querySelectorAll('.gl-item');

    pills.forEach(function(pill) {
        pill.addEventListener('click', function() {
            pills.forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');

            var filter = this.getAttribute('data-filter');
            var visibleCount = 0;

            items.forEach(function(item) {
                var cat = item.getAttribute('data-category') || '';
                var show = (filter === 'all') || (cat.toLowerCase() === filter.toLowerCase());
                item.classList.toggle('gl-hidden', !show);
                if (show) visibleCount++;
            });

            var countEl = document.querySelector('.gl-filter-count');
            if (countEl) {
                countEl.textContent = visibleCount + ' photo' + (visibleCount !== 1 ? 's' : '');
            }
        });
    });
});

/* ═══════════════════════════════
   LIGHTBOX
   ═══════════════════════════════ */
var glItems = <?php echo json_encode(array_map(function($item) use ($galleryCols) {
    $imagePath = $item['image_path'] ?? $item['image'] ?? $item['photo'] ?? $item['file_path'] ?? '';
    return [
        'title' => $item['title'] ?? 'Untitled',
        'image' => $imagePath,
        'date'  => isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : '',
    ];
}, $gallery)); ?>;

var glCurrentIndex = 0;
var glLightbox = document.getElementById('glLightbox');
var glContent = document.getElementById('glLightboxContent');
var glCaption = document.getElementById('glLightboxCaption');
var glCounter = document.getElementById('glLightboxCounter');

function openLightbox(index) {
    glCurrentIndex = index;
    renderLightbox();
    glLightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    glLightbox.classList.remove('active');
    document.body.style.overflow = '';
}

function navLightbox(dir) {
    glCurrentIndex = (glCurrentIndex + dir + glItems.length) % glItems.length;
    renderLightbox();
}

function renderLightbox() {
    var item = glItems[glCurrentIndex];

    if (item.image && item.image.trim() !== '') {
        var img = document.createElement('img');
        img.className = 'gl-lightbox-img';
        img.src = '<?php echo BASE_URL; ?>/' + item.image;
        img.alt = item.title;
        img.addEventListener('error', function() {
            this.outerHTML = '<div class="gl-lightbox-placeholder"><i class="bi bi-image"></i><span>' + escapeHtml(item.title) + '</span></div>';
        });
        glContent.innerHTML = '';
        glContent.appendChild(img);
    } else {
        glContent.innerHTML = '<div class="gl-lightbox-placeholder"><i class="bi bi-image"></i><span>' + escapeHtml(item.title) + '</span></div>';
    }

    glCaption.innerHTML = '<h4>' + escapeHtml(item.title) + '</h4>' + (item.date ? '<p>' + escapeHtml(item.date) + '</p>' : '');
    glCounter.textContent = (glCurrentIndex + 1) + ' / ' + glItems.length;
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text || ''));
    return div.innerHTML;
}

/* ── Keyboard nav ── */
document.addEventListener('keydown', function(e) {
    if (!glLightbox.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') navLightbox(-1);
    if (e.key === 'ArrowRight') navLightbox(1);
});

/* ── Click outside to close ── */
glLightbox.addEventListener('click', function(e) {
    if (e.target === glLightbox) closeLightbox();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>