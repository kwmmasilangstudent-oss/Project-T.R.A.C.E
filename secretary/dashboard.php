<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary']);

require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$stats = getSecretaryStats();

$pendingApplications = [];
$recentDocuments = [];
try {
    $stmt = $pdo->prepare('SELECT a.*, r.full_name FROM applications a LEFT JOIN residents r ON a.resident_id = r.id WHERE a.status IN ("submitted", "pending", "under_review") ORDER BY a.created_at DESC LIMIT 5');
    $stmt->execute();
    $pendingApplications = $stmt->fetchAll();

    $recentDocuments = $pdo->query('SELECT d.*, r.full_name FROM documents d LEFT JOIN residents r ON d.resident_id = r.id ORDER BY d.created_at DESC LIMIT 5')->fetchAll();
} catch (Throwable $e) {
    $pendingApplications = [];
    $recentDocuments = [];
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
    --ds-accent: #10b981;
    --ds-accent-dark: #059669;
    --ds-sky: #0ea5e9;
    --ds-amber: #f59e0b;
    --ds-red: #ef4444;
    --ds-violet: #8b5cf6;
    --ds-rose: #f43f5e;
    --ds-teal: #14b8a6;
    --ds-bg: #0f172a;
    --ds-card: rgba(255,255,255,0.03);
    --ds-text: #f0f4f8;
    --ds-text-sec: #94a3b8;
    --ds-text-muted: #64748b;
    --ds-text-dim: #475569;
    --ds-border: rgba(255,255,255,0.08);
    --ds-border-lt: rgba(255,255,255,0.12);
    --ds-rad: 12px;
    --ds-rad-lg: 16px;
    --ds-rad-xl: 20px;
}

html.light {
    --ds-bg: #f4f6f9;
    --ds-card: rgba(255,255,255,0.8);
    --ds-text: #1e293b;
    --ds-text-sec: #475569;
    --ds-text-muted: #64748b;
    --ds-text-dim: #94a3b8;
    --ds-border: rgba(0,0,0,0.08);
    --ds-border-lt: rgba(0,0,0,0.12);
}

html.light .ds-title,
html.light .ds-stat-value,
html.light .ds-card-tt { color: #1e293b; }

html.light .ds-title span {
    background: linear-gradient(135deg, #059669, #10b981);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

html.light .ds-text-sec { color: #475569; }

html.light .ds-greeting strong { color: #1e293b; }

html.light .ds-card {
    background: rgba(255,255,255,0.85);
    border-color: rgba(0,0,0,0.08);
}

html.light .ds-card:hover { border-color: rgba(0,0,0,0.14); }

html.light .ds-stat-card {
    background: rgba(255,255,255,0.85);
    border-color: rgba(0,0,0,0.08);
}

html.light .ds-stat-card:hover {
    border-color: rgba(0,0,0,0.14);
}

html.light .ds-badge {
    background: rgba(16,185,129,0.1);
    border-color: rgba(16,185,129,0.2);
    color: #059669;
}

html.light .ds-card-link {
    color: #059669;
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.18);
}

html.light .ds-card-link:hover {
    background: rgba(16,185,129,0.14);
    color: #047857;
}

html.light .ds-list-item { border-bottom-color: rgba(0,0,0,0.06); }

html.light .ds-list-name { color: #1e293b; }

html.light .ds-st-submitted {
    background: rgba(14,165,233,0.1);
    border-color: rgba(14,165,233,0.2);
    color: #0284c7;
}

html.light .ds-st-pending {
    background: rgba(245,158,11,0.1);
    border-color: rgba(245,158,11,0.2);
    color: #d97706;
}

html.light .ds-st-under_review {
    background: rgba(139,92,246,0.1);
    border-color: rgba(139,92,246,0.2);
    color: #7c3aed;
}

html.light .ds-st-success {
    background: rgba(16,185,129,0.1);
    border-color: rgba(16,185,129,0.2);
    color: #059669;
}

html.light .ds-st-default {
    background: rgba(100,116,139,0.1);
    border-color: rgba(100,116,139,0.2);
    color: #475569;
}

html.light .ds-quick-btn {
    background: rgba(255,255,255,0.7);
    border-color: rgba(0,0,0,0.08);
    color: #475569;
}

html.light .ds-quick-btn:hover {
    background: rgba(255,255,255,0.9);
    border-color: rgba(0,0,0,0.14);
    color: #1e293b;
}

html.light .ds-empty-ico {
    background: rgba(100,116,139,0.08);
    border-color: rgba(100,116,139,0.12);
    color: #94a3b8;
}

html.light .ds-sidebar {
    background: rgba(255,255,255,0.7);
    border-right-color: rgba(0,0,0,0.08);
}

html.light .ds-page::before { opacity: 0; }

html.light .ds-page::after { opacity: 0; }

html.light .ds-orb { opacity: 0.4; }

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--ds-bg) !important;
    color: var(--ds-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.ds-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.ds-page::after {
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

.ds-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: dsFloat 22s ease-in-out infinite;
}
.ds-orb.o1 { width: 480px; height: 480px; background: rgba(16,185,129,0.06); top: -12%; left: -8%; }
.ds-orb.o2 { width: 360px; height: 360px; background: rgba(139,92,246,0.05); bottom: -8%; right: -6%; animation-delay: -11s; }
.ds-orb.o3 { width: 240px; height: 240px; background: rgba(14,165,233,0.05); top: 45%; right: 25%; animation-delay: -5s; animation-duration: 26s; }

@keyframes dsFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.ds-page { min-height: 100vh; position: relative; z-index: 1; }

.ds-layout { display: flex; min-height: 100vh; }

.ds-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--ds-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.ds-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.ds-head {
    margin-bottom: 36px;
}

.ds-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: 100px;
    color: #6ee7b7;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
    width: fit-content;
}

.ds-badge .ds-dot {
    width: 7px; height: 7px;
    background: var(--ds-accent);
    border-radius: 50%;
    animation: dsPulse 2s ease-in-out infinite;
}

@keyframes dsPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.ds-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.ds-title span {
    background: linear-gradient(135deg, var(--ds-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ds-desc {
    font-size: 0.92rem;
    color: var(--ds-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* Greeting */
.ds-greeting {
    font-size: 0.88rem;
    color: var(--ds-text-muted);
    margin-bottom: 6px;
    font-weight: 500;
}

.ds-greeting strong {
    color: #e2e8f0;
    font-weight: 700;
}

/* ═══════════════════════════════
   STAT CARDS
   ═══════════════════════════════ */
.ds-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}

.ds-stat-card {
    background: var(--ds-card);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-rad-xl);
    backdrop-filter: blur(40px);
    padding: 24px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: relative;
    overflow: hidden;
}

.ds-stat-card:hover {
    border-color: rgba(255,255,255,0.16);
    transform: translateY(-3px);
    color: inherit;
    text-decoration: none;
}

.ds-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    border-radius: 2px 2px 0 0;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.ds-stat-card:hover::before { opacity: 1; }

.ds-stat-card[data-accent="green"]::before  { background: linear-gradient(90deg, var(--ds-accent), #34d399); }
.ds-stat-card[data-accent="amber"]::before  { background: linear-gradient(90deg, var(--ds-amber), #fbbf24); }
.ds-stat-card[data-accent="sky"]::before    { background: linear-gradient(90deg, var(--ds-sky), #38bdf8); }
.ds-stat-card[data-accent="violet"]::before { background: linear-gradient(90deg, var(--ds-violet), #a78bfa); }
.ds-stat-card[data-accent="rose"]::before   { background: linear-gradient(90deg, var(--ds-rose), #fb7185); }
.ds-stat-card[data-accent="teal"]::before   { background: linear-gradient(90deg, var(--ds-teal), #2dd4bf); }

.ds-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.ds-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.ds-stat-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ds-text-muted);
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.ds-stat-value {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.ds-stat-sub {
    font-size: 0.78rem;
    color: var(--ds-text-dim);
    font-weight: 500;
}

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.ds-card {
    background: var(--ds-card);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.ds-card:hover { border-color: rgba(255,255,255,0.12); }

.ds-card-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.ds-card-hd-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.ds-card-ico {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.ds-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.ds-card-st {
    font-size: 0.8rem;
    color: var(--ds-text-muted);
    margin: 0;
    line-height: 1.4;
}

.ds-card-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6ee7b7;
    text-decoration: none;
    transition: all 0.2s ease;
    padding: 6px 14px;
    border-radius: 100px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.2);
}

.ds-card-link:hover {
    background: rgba(16,185,129,0.15);
    color: #a7f3d0;
}
.ds-card-link i { font-size: 0.78rem; transition: transform 0.2s ease; }
.ds-card-link:hover i { transform: translateX(2px); }

/* ═══════════════════════════════
   LIST ITEMS
   ═══════════════════════════════ */
.ds-list { display: flex; flex-direction: column; }

.ds-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.ds-list-item:last-child { border-bottom: none; }

.ds-list-info { flex: 1; min-width: 0; }

.ds-list-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: #f1f5f9;
    margin-bottom: 3px;
}

.ds-list-meta {
    font-size: 0.8rem;
    color: var(--ds-text-muted);
    line-height: 1.4;
}

/* Status badges */
.ds-st-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.ds-st-submitted {
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.ds-st-pending {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.ds-st-under_review {
    background: rgba(139,92,246,0.12);
    border: 1px solid rgba(139,92,246,0.25);
    color: #c4b5fd;
}
.ds-st-success {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.ds-st-default {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}

/* Empty state */
.ds-empty {
    text-align: center;
    padding: 40px 20px;
}

.ds-empty-ico {
    width: 52px;
    height: 52px;
    border-radius: 15px;
    background: rgba(100,116,139,0.08);
    border: 1px solid rgba(100,116,139,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--ds-text-dim);
    margin: 0 auto 12px;
}

.ds-empty-txt {
    font-size: 0.88rem;
    color: var(--ds-text-muted);
    margin: 0;
}

/* ═══════════════════════════════
   TWO-COL LAYOUT
   ═══════════════════════════════ */
.ds-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

/* ═══════════════════════════════
   SECTION HEADER
   ═══════════════════════════════ */
.ds-section-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--ds-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 18px;
    padding-left: 2px;
}

/* ═══════════════════════════════
   QUICK ACTIONS ROW
   ═══════════════════════════════ */
.ds-quick-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.ds-quick-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--ds-text-sec);
    text-decoration: none;
    transition: all 0.2s ease;
}

.ds-quick-btn:hover {
    background: rgba(255,255,255,0.07);
    border-color: rgba(255,255,255,0.15);
    color: #e2e8f0;
}

.ds-quick-btn i {
    font-size: 0.9rem;
    color: var(--ds-text-dim);
    transition: color 0.2s ease;
}

.ds-quick-btn:hover i { color: var(--ds-accent); }

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.ds-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.ds-reveal.ds-vis { opacity: 1; transform: translateY(0); }

.ds-d1 { transition-delay: 0.05s; }
.ds-d2 { transition-delay: 0.1s; }
.ds-d3 { transition-delay: 0.15s; }
.ds-d4 { transition-delay: 0.2s; }
.ds-d5 { transition-delay: 0.25s; }
.ds-d6 { transition-delay: 0.3s; }
.ds-d7 { transition-delay: 0.35s; }
.ds-d8 { transition-delay: 0.4s; }
.ds-d9 { transition-delay: 0.45s; }

/* Stat card stagger (child-level) */
.ds-stats-grid .ds-stat-card { opacity: 0; transform: translateY(20px); transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.ds-stats-grid .ds-stat-card.ds-vis { opacity: 1; transform: translateY(0); }
.ds-stats-grid .ds-stat-card:nth-child(1) { transition-delay: 0.1s; }
.ds-stats-grid .ds-stat-card:nth-child(2) { transition-delay: 0.16s; }
.ds-stats-grid .ds-stat-card:nth-child(3) { transition-delay: 0.22s; }
.ds-stats-grid .ds-stat-card:nth-child(4) { transition-delay: 0.28s; }

.ds-stats-grid-2 .ds-stat-card:nth-child(1) { transition-delay: 0.34s; }
.ds-stats-grid-2 .ds-stat-card:nth-child(2) { transition-delay: 0.4s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .ds-main { padding: 32px 36px; }
    .ds-stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 991.98px) {
    .ds-layout { flex-direction: column; }
    .ds-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--ds-border);
    }
    .ds-main { padding: 28px 24px; }
    .ds-two-col { grid-template-columns: 1fr; }
    .ds-stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 767.98px) {
    .ds-main { padding: 24px 16px; }
    .ds-card { padding: 24px 20px; }
    .ds-title { font-size: 1.4rem; }
    .ds-stat-value { font-size: 1.6rem; }
}

@media (max-width: 575.98px) {
    .ds-stats-grid { grid-template-columns: 1fr; }
    .ds-main { padding: 20px 14px; }
    .ds-card { padding: 20px 16px; border-radius: 16px; }
    .ds-list-item { flex-direction: column; align-items: flex-start; gap: 8px; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="ds-page">
    <div class="ds-orb o1"></div>
    <div class="ds-orb o2"></div>
    <div class="ds-orb o3"></div>

    <div class="ds-layout">
        <!-- Sidebar -->
        <div class="ds-sidebar">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <div class="ds-main">

            <!-- Header -->
            <div class="ds-head ds-reveal ds-d1">
                <div class="ds-badge">
                    <span class="ds-dot"></span>
                    Dashboard
                </div>
                <p class="ds-greeting">Welcome back, <strong><?php echo e($_SESSION['name'] ?? 'Secretary'); ?></strong></p>
                <h1 class="ds-title">Secretary <span>Dashboard</span></h1>
                <p class="ds-desc">Overview of residents, applications, documents, projects, and community activity at a glance.</p>
            </div>

            <!-- Quick Links -->
            <div class="ds-quick-row ds-reveal ds-d2">
                <a href="<?php echo BASE_URL; ?>/secretary/residents.php" class="ds-quick-btn">
                    <i class="bi bi-people"></i> Residents
                </a>
                <a href="<?php echo BASE_URL; ?>/secretary/requests.php" class="ds-quick-btn">
                    <i class="bi bi-file-earmark-text"></i> Applications
                </a>
                <a href="<?php echo BASE_URL; ?>/secretary/documents.php" class="ds-quick-btn">
                    <i class="bi bi-folder2-open"></i> Documents
                </a>
                <a href="<?php echo BASE_URL; ?>/secretary/announcements.php" class="ds-quick-btn">
                    <i class="bi bi-megaphone"></i> Announcements
                </a>
                <a href="<?php echo BASE_URL; ?>/secretary/projects.php" class="ds-quick-btn">
                    <i class="bi bi-kanban"></i> Projects
                </a>
                <a href="<?php echo BASE_URL; ?>/secretary/agenda.php" class="ds-quick-btn">
                    <i class="bi bi-calendar-event"></i> Agenda
                </a>
            </div>

            <!-- Primary Stats -->
            <div class="ds-section-label ds-reveal ds-d2">Core Metrics</div>
            <div class="ds-stats-grid">
                <a href="<?php echo BASE_URL; ?>/secretary/residents.php" class="ds-stat-card" data-accent="green">
                    <div class="ds-stat-top">
                        <span class="ds-stat-label">Residents</span>
                        <div class="ds-stat-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="ds-stat-value"><?php echo e($stats['total_residents'] ?? 0); ?></div>
                    <span class="ds-stat-sub">Registered in the barangay</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/secretary/requests.php" class="ds-stat-card" data-accent="amber">
                    <div class="ds-stat-top">
                        <span class="ds-stat-label">Pending</span>
                        <div class="ds-stat-icon" style="background:rgba(245,158,11,0.12); color:#f59e0b;">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="ds-stat-value"><?php echo e($stats['pending_applications'] ?? 0); ?></div>
                    <span class="ds-stat-sub">Applications awaiting review</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/secretary/documents.php" class="ds-stat-card" data-accent="sky">
                    <div class="ds-stat-top">
                        <span class="ds-stat-label">Documents</span>
                        <div class="ds-stat-icon" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                            <i class="bi bi-file-earmark-check-fill"></i>
                        </div>
                    </div>
                    <div class="ds-stat-value"><?php echo e($stats['documents_issued'] ?? 0); ?></div>
                    <span class="ds-stat-sub">Certificates &amp; clearances issued</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/secretary/announcements.php" class="ds-stat-card" data-accent="violet">
                    <div class="ds-stat-top">
                        <span class="ds-stat-label">Updates</span>
                        <div class="ds-stat-icon" style="background:rgba(139,92,246,0.12); color:#8b5cf6;">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                    </div>
                    <div class="ds-stat-value"><?php echo e($stats['total_announcements'] ?? 0); ?></div>
                    <span class="ds-stat-sub">Published announcements</span>
                </a>
            </div>

            <!-- Secondary Stats -->
            <div class="ds-section-label ds-reveal ds-d5">Activity</div>
            <div class="ds-stats-grid ds-stats-grid-2" style="grid-template-columns: repeat(2, 1fr); max-width: calc(50% + 9px);">
                <a href="<?php echo BASE_URL; ?>/secretary/projects.php" class="ds-stat-card" data-accent="teal">
                    <div class="ds-stat-top">
                        <span class="ds-stat-label">Projects</span>
                        <div class="ds-stat-icon" style="background:rgba(20,184,166,0.12); color:#14b8a6;">
                            <i class="bi bi-kanban-fill"></i>
                        </div>
                    </div>
                    <div class="ds-stat-value"><?php echo e($stats['total_projects'] ?? 0); ?></div>
                    <span class="ds-stat-sub">Active barangay projects</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/secretary/agenda.php" class="ds-stat-card" data-accent="rose">
                    <div class="ds-stat-top">
                        <span class="ds-stat-label">Agenda</span>
                        <div class="ds-stat-icon" style="background:rgba(244,63,94,0.12); color:#f43f5e;">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                    </div>
                    <div class="ds-stat-value"><?php echo e($stats['upcoming_agenda'] ?? 0); ?></div>
                    <span class="ds-stat-sub">Upcoming meetings &amp; events</span>
                </a>
            </div>

            <!-- Two Column: Applications + Documents -->
            <div class="ds-two-col ds-reveal ds-d7">
                <!-- Pending Applications -->
                <div class="ds-card">
                    <div class="ds-card-hd">
                        <div class="ds-card-hd-left">
                            <div class="ds-card-ico" style="background:rgba(245,158,11,0.12); color:#f59e0b;">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div>
                                <h5 class="ds-card-tt">Pending Applications</h5>
                                <p class="ds-card-st">Recently submitted requests</p>
                            </div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/secretary/requests.php" class="ds-card-link">
                            View all <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <?php if (!empty($pendingApplications)): ?>
                        <div class="ds-list">
                            <?php foreach ($pendingApplications as $app): ?>
                                <div class="ds-list-item">
                                    <div class="ds-list-info">
                                        <div class="ds-list-name"><?php echo e($app['full_name'] ?? 'Unknown'); ?></div>
                                        <div class="ds-list-meta"><?php echo e($app['application_type']); ?></div>
                                    </div>
                                    <span class="ds-st-badge ds-st-<?php echo e($app['status']); ?>">
                                        <?php if ($app['status'] === 'submitted'): ?>
                                            <i class="bi bi-send-fill"></i>
                                        <?php elseif ($app['status'] === 'pending'): ?>
                                            <i class="bi bi-clock-fill"></i>
                                        <?php elseif ($app['status'] === 'under_review'): ?>
                                            <i class="bi bi-eye-fill"></i>
                                        <?php endif; ?>
                                        <?php echo e(str_replace('_', ' ', $app['status'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="ds-empty">
                            <div class="ds-empty-ico"><i class="bi bi-inbox"></i></div>
                            <p class="ds-empty-txt">No pending applications at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Documents -->
                <div class="ds-card">
                    <div class="ds-card-hd">
                        <div class="ds-card-hd-left">
                            <div class="ds-card-ico" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                                <i class="bi bi-file-earmark-check"></i>
                            </div>
                            <div>
                                <h5 class="ds-card-tt">Recent Documents</h5>
                                <p class="ds-card-st">Latest issued certificates</p>
                            </div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/secretary/documents.php" class="ds-card-link">
                            View all <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <?php if (!empty($recentDocuments)): ?>
                        <div class="ds-list">
                            <?php foreach ($recentDocuments as $doc): ?>
                                <div class="ds-list-item">
                                    <div class="ds-list-info">
                                        <div class="ds-list-name"><?php echo e($doc['document_type']); ?></div>
                                        <div class="ds-list-meta"><?php echo e($doc['full_name'] ?? 'Unknown'); ?></div>
                                    </div>
                                    <span class="ds-st-badge ds-st-success">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <?php echo e($doc['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="ds-empty">
                            <div class="ds-empty-ico"><i class="bi bi-inbox"></i></div>
                            <p class="ds-empty-txt">No documents issued yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    /* Staggered reveal for non-stat elements */
    var reveals = document.querySelectorAll('.ds-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('ds-vis');
        });
    }, 60);

    /* Staggered reveal for stat cards */
    var statCards = document.querySelectorAll('.ds-stat-card');
    setTimeout(function() {
        statCards.forEach(function(el) {
            el.classList.add('ds-vis');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>