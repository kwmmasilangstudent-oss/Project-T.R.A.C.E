<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['resident']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$stats = getResidentStats($_SESSION['user_id']);

$resident = null;
try {
    $residentStmt = $pdo->prepare('SELECT id FROM residents WHERE user_id = ? LIMIT 1');
    $residentStmt->execute([$_SESSION['user_id']]);
    $resident = $residentStmt->fetch();
} catch (Throwable $e) {
    $resident = null;
}

$recentAnnouncements = [];
if ($resident) {
    try {
        $stmt = $pdo->prepare('SELECT a.id, a.title, a.type, a.priority, a.created_at, ar.is_read FROM announcements a JOIN announcement_reads ar ON ar.announcement_id = a.id WHERE a.is_active = 1 AND ar.resident_id = ? ORDER BY a.created_at DESC LIMIT 5');
        $stmt->execute([$resident['id']]);
        $recentAnnouncements = $stmt->fetchAll();
    } catch (Throwable $e) {
        $recentAnnouncements = [];
    }
}
$myRequests = [];
if ($resident) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM applications WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5');
        $stmt->execute([$resident['id']]);
        $myRequests = $stmt->fetchAll();
    } catch (Throwable $e) {
        $myRequests = [];
    }
}

$requestCount = count($myRequests);
$unreadAnn = 0;
foreach ($recentAnnouncements as $a) { if (empty($a['is_read'])) $unreadAnn++; }

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
    --db-accent: #10b981;
    --db-accent-dark: #059669;
    --db-accent-glow: rgba(16,185,129,0.15);
    --db-violet: #8b5cf6;
    --db-sky: #0ea5e9;
    --db-amber: #f59e0b;
    --db-red: #ef4444;
    --db-bg: #0f172a;
    --db-surface: rgba(255,255,255,0.04);
    --db-surface-hover: rgba(255,255,255,0.07);
    --db-border: rgba(255,255,255,0.08);
    --db-text: #f1f5f9;
    --db-text-secondary: #94a3b8;
    --db-text-muted: #64748b;
    --db-radius: 12px;
    --db-radius-lg: 18px;
}

/* ═══════════════════════════════
   GLOBAL
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--db-bg) !important;
    color: var(--db-text);
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
.db-grid-overlay {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

.db-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: dbFloat 22s ease-in-out infinite;
}

.db-orb.o1 { width: 500px; height: 500px; background: rgba(16,185,129,0.06); top: -12%; left: -10%; }
.db-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -8%; right: -5%; animation-delay: -12s; }
.db-orb.o3 { width: 260px; height: 260px; background: rgba(14,165,233,0.05); top: 50%; left: 45%; animation-delay: -6s; animation-duration: 28s; }

@keyframes dbFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(30px, -20px) scale(1.04); }
    66%      { transform: translate(-20px, 15px) scale(0.96); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.db-page-wrapper {
    position: relative;
    z-index: 1;
    min-height: 100vh;
}

.db-page-wrapper .container-fluid { padding: 0; }
.db-page-wrapper > .container-fluid > .row { margin: 0; min-height: 100vh; }

/* ═══════════════════════════════
   SIDEBAR
   ═══════════════════════════════ */
.db-sidebar-col {
    background: rgba(15,23,42,0.60);
    backdrop-filter: blur(30px);
    border-right: 1px solid var(--db-border);
    padding: 0 !important;
    min-height: 100vh;
}

.db-sidebar-col .sidebar,
.db-sidebar-col .sidebar-menu,
.db-sidebar-col .sidebar-header,
.db-sidebar-col .sidebar-nav,
.db-sidebar-col ul,
.db-sidebar-col li,
.db-sidebar-col a {
    background: transparent !important;
    color: var(--db-text-secondary) !important;
}

.db-sidebar-col a:hover,
.db-sidebar-col .active a,
.db-sidebar-col .active {
    background: var(--db-surface-hover) !important;
    color: var(--db-text) !important;
}

.db-sidebar-col .sidebar-header h4,
.db-sidebar-col .sidebar-header h5,
.db-sidebar-col .sidebar-header h3 {
    color: var(--db-text) !important;
}

/* ═══════════════════════════════
   MAIN CONTENT
   ═══════════════════════════════ */
.db-main-col {
    padding: 40px 48px 60px !important;
}

/* ═══════════════════════════════
   PAGE HEADER / GREETING
   ═══════════════════════════════ */
.db-page-header {
    margin-bottom: 32px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.db-page-header-text { flex: 1; min-width: 240px; }

.db-greeting {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.db-avatar {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: rgba(16,185,129,0.12);
    border: 2px solid rgba(16,185,129,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: var(--db-accent);
    flex-shrink: 0;
}

.db-greeting-text h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.4rem, 3vw, 2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 2px;
}

.db-greeting-text h1 span {
    background: linear-gradient(135deg, var(--db-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.db-greeting-text p {
    font-size: 0.95rem;
    color: var(--db-text-muted);
    margin: 0;
}

.db-page-divider {
    width: 48px;
    height: 3px;
    background: linear-gradient(135deg, var(--db-accent), #34d399);
    border-radius: 2px;
    margin-top: 20px;
}

/* ═══════════════════════════════
   STAT PILLS
   ═══════════════════════════════ */
.db-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 32px;
}

.db-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius);
    backdrop-filter: blur(12px);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.db-stat-pill:hover {
    border-color: rgba(255,255,255,0.14);
    background: rgba(255,255,255,0.06);
    transform: translateY(-2px);
    color: inherit;
}

.db-pill-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.db-pill-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 1px;
}

.db-pill-label {
    font-size: 0.72rem;
    color: var(--db-text-muted);
    font-weight: 500;
}

/* ═══════════════════════════════
   SECTION TITLES
   ═══════════════════════════════ */
.db-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.db-section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.1rem, 2vw, 1.35rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 4px;
}

.db-section-sub {
    font-size: 0.85rem;
    color: var(--db-text-muted);
    margin: 0;
}

.db-view-all {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    background: transparent;
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6ee7b7;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.db-view-all:hover {
    background: rgba(16,185,129,0.08);
    border-color: rgba(16,185,129,0.25);
    color: #a7f3d0;
}

.db-view-all i { transition: transform 0.2s ease; font-size: 0.78rem; }
.db-view-all:hover i { transform: translateX(3px); }

/* ═══════════════════════════════
   QUICK ACTION CARDS
   ═══════════════════════════════ */
.db-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 14px;
    margin-bottom: 36px;
}

.db-action-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius-lg);
    padding: 22px 20px;
    text-decoration: none;
    color: inherit;
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    backdrop-filter: blur(10px);
}

.db-action-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
}

.db-action-card:hover {
    transform: translateY(-5px);
    border-color: rgba(255,255,255,0.16);
    box-shadow: 0 12px 48px rgba(0,0,0,0.25);
    color: inherit;
}

.db-action-accent {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.db-action-card:hover .db-action-accent { opacity: 1; }

.db-action-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 14px;
    transition: transform 0.3s ease;
}

.db-action-card:hover .db-action-icon { transform: scale(1.08); }

.db-action-title {
    font-weight: 700;
    font-size: 0.9rem;
    color: #e2e8f0;
    margin-bottom: 4px;
}

.db-action-sub {
    font-size: 0.78rem;
    color: var(--db-text-muted);
    margin: 0;
}

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.db-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius-lg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(20px);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.db-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
    z-index: 1;
}

.db-card:hover {
    border-color: rgba(255,255,255,0.12);
    box-shadow: 0 8px 40px rgba(0,0,0,0.20);
}

.db-card-header {
    padding: 18px 22px;
    border-bottom: 1px solid var(--db-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.db-card-header h5 {
    font-weight: 700;
    font-size: 0.95rem;
    color: #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.db-card-header h5 i { font-size: 0.95rem; }

.db-card-body {
    padding: 0;
    flex-grow: 1;
}

/* ═══════════════════════════════
   ANNOUNCEMENT LIST
   ═══════════════════════════════ */
.db-ann-item {
    padding: 16px 22px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: flex-start;
    gap: 14px;
    transition: background 0.2s ease;
}

.db-ann-item:last-child { border-bottom: none; }
.db-ann-item:hover { background: rgba(255,255,255,0.03); }

.db-ann-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 6px;
    flex-shrink: 0;
    border: 2px solid;
}

.db-ann-dot.unread {
    background: var(--db-accent);
    border-color: var(--db-accent);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.20);
}

.db-ann-dot.read {
    background: transparent;
    border-color: rgba(255,255,255,0.15);
}

.db-ann-content { flex-grow: 1; min-width: 0; }

.db-ann-title {
    font-weight: 600;
    font-size: 0.88rem;
    color: #e2e8f0;
    margin-bottom: 4px;
    line-height: 1.3;
}

.db-ann-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.db-ann-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 100px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.db-ann-new {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 8px;
    background: rgba(16,185,129,0.10);
    color: #6ee7b7;
    border-radius: 100px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.db-ann-date {
    font-size: 0.75rem;
    color: var(--db-text-muted);
}

/* ═══════════════════════════════
   REQUESTS TABLE
   ═══════════════════════════════ */
.db-table-wrap {
    overflow-x: auto;
}

.db-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.db-table thead th {
    padding: 12px 20px;
    font-size: 0.73rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--db-text-muted);
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid var(--db-border);
    text-align: left;
    white-space: nowrap;
}

.db-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.2s ease;
}

.db-table tbody tr:last-child { border-bottom: none; }
.db-table tbody tr:hover { background: rgba(255,255,255,0.03); }

.db-table tbody td {
    padding: 13px 20px;
    color: #cbd5e1;
    border-bottom: none;
    vertical-align: middle;
    white-space: nowrap;
}

.db-table .col-ref {
    font-weight: 700;
    color: #6ee7b7;
}

.db-table .col-type {
    font-weight: 600;
    color: #e2e8f0;
}

.db-table .col-date {
    font-size: 0.82rem;
    color: var(--db-text-muted);
}

.db-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

.db-ann-link {
    text-decoration: none;
    color: inherit;
    display: block;
}
.db-empty {
    padding: 40px 24px;
    text-align: center;
}

.db-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 14px;
}

.db-empty h6 {
    font-weight: 700;
    font-size: 0.9rem;
    color: #e2e8f0;
    margin-bottom: 4px;
}

.db-empty p {
    font-size: 0.82rem;
    color: var(--db-text-muted);
    margin: 0;
}

/* ═══════════════════════════════
   REVEAL ANIMATIONS
   ═══════════════════════════════ */
.db-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.db-reveal.db-visible {
    opacity: 1;
    transform: translateY(0);
}

.db-d1 { transition-delay: 0.05s; }
.db-d2 { transition-delay: 0.10s; }
.db-d3 { transition-delay: 0.15s; }
.db-d4 { transition-delay: 0.20s; }
.db-d5 { transition-delay: 0.25s; }
.db-d6 { transition-delay: 0.30s; }
.db-d7 { transition-delay: 0.35s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .db-action-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 991.98px) {
    .db-sidebar-col {
        min-height: auto !important;
        border-right: none !important;
        border-bottom: 1px solid var(--db-border) !important;
    }
    .db-main-col { padding: 32px 24px 50px !important; }
    .db-action-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 767.98px) {
    .db-main-col { padding: 24px 18px 40px !important; }
    .db-greeting { gap: 12px; }
    .db-avatar { width: 44px; height: 44px; border-radius: 12px; font-size: 1.1rem; }
    .db-greeting-text h1 { font-size: 1.4rem; }
    .db-stats-row { gap: 8px; }
    .db-stat-pill { flex: 1; min-width: 0; padding: 10px 14px; }
    .db-pill-icon { width: 32px; height: 32px; font-size: 0.85rem; }
    .db-pill-value { font-size: 1rem; }
    .db-action-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .db-card-header { padding: 14px 16px; }
    .db-ann-item { padding: 12px 16px; }
    .db-table thead th { padding: 10px 14px; }
    .db-table tbody td { padding: 10px 14px; font-size: 0.82rem; }
}

@media (max-width: 480px) {
    .db-main-col { padding: 20px 14px 36px !important; }
    .db-greeting-text h1 { font-size: 1.25rem; }
    .db-action-grid { grid-template-columns: 1fr 1fr; }
    .db-action-card { padding: 18px 16px; }
    .db-action-icon { width: 40px; height: 40px; font-size: 1rem; }
    .db-page-header { flex-direction: column; align-items: flex-start; }
}
</style>

<!-- ═══════════════════════════════════════
     ATMOSPHERIC ELEMENTS
     ═══════════════════════════════════════ -->
<div class="db-grid-overlay"></div>
<div class="db-orb o1"></div>
<div class="db-orb o2"></div>
<div class="db-orb o3"></div>

<!-- ═══════════════════════════════════════
     PAGE LAYOUT
     ═══════════════════════════════════════ -->
<div class="db-page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            
                <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        

            <!-- Main Content -->
            <div class="col-md-9 db-main-col">

                <!-- ── Page Header / Greeting ── -->
                <div class="db-page-header db-reveal db-d1">
                    <div class="db-page-header-text">
                        <div class="db-greeting">
                            <div class="db-avatar"><i class="bi bi-person-fill"></i></div>
                            <div class="db-greeting-text">
                                <h1>Welcome, <span><?php echo e($_SESSION['name'] ?? 'Resident'); ?></span></h1>
                                <p>Here's what's happening in your barangay today.</p>
                            </div>
                        </div>
                        <div class="db-page-divider"></div>
                    </div>
                </div>

                <!-- ── Stat Pills ── -->
                <div class="db-stats-row db-reveal db-d2">
                    <a href="<?php echo BASE_URL; ?>/resident/requests.php" class="db-stat-pill">
                        <div class="db-pill-icon" style="background:rgba(14,165,233,0.10); color:#7dd3fc;">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <div class="db-pill-value"><?php echo (int)($stats['my_requests'] ?? 0); ?></div>
                            <div class="db-pill-label">Requests</div>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/resident/profile.php" class="db-stat-pill">
                        <div class="db-pill-icon" style="background:rgba(16,185,129,0.10); color:#6ee7b7;">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div>
                            <div class="db-pill-value"><?php echo (int)($stats['my_documents'] ?? 0); ?></div>
                            <div class="db-pill-label">Documents</div>
                        </div>
                    </a>
                </div>

                <!-- ── Quick Actions ── -->
                <div class="db-reveal db-d2">
                    <div class="db-section-header">
                        <div>
                            <h3 class="db-section-title">Quick Actions</h3>
                            <p class="db-section-sub">Frequently used services at your fingertips.</p>
                        </div>
                    </div>
                </div>

                <div class="db-action-grid">
                    <a href="<?php echo BASE_URL; ?>/resident/requests.php" class="db-action-card db-reveal db-d2">
                        <div class="db-action-accent" style="background:linear-gradient(135deg,#0ea5e9,#38bdf8);"></div>
                        <div class="db-action-icon" style="background:rgba(14,165,233,0.10); color:#7dd3fc;">
                            <i class="bi bi-file-earmark-plus"></i>
                        </div>
                        <div class="db-action-title">Request Certificate</div>
                        <p class="db-action-sub">Apply for clearance & documents</p>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/resident/appointments.php" class="db-action-card db-reveal db-d3">
                        <div class="db-action-accent" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"></div>
                        <div class="db-action-icon" style="background:rgba(245,158,11,0.10); color:#fcd34d;">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="db-action-title">Appointments</div>
                        <p class="db-action-sub">Schedule or view bookings</p>
</a>

                                    <a href="<?php echo BASE_URL; ?>/resident/profile.php" class="db-action-card db-reveal db-d5">
                        <div class="db-action-accent" style="background:linear-gradient(135deg,#14b8a6,#2dd4bf);"></div>
                        <div class="db-action-icon" style="background:rgba(20,184,166,0.10); color:#5eead4;">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <div class="db-action-title">My Profile</div>
                        <p class="db-action-sub">Personal info & documents</p>
                    </a>
                </div>

                <!-- ── Official Announcements Notification ── -->
                <?php if (!empty($officialAnnouncements)): ?>
                <div class="db-reveal db-d3" style="margin-bottom:24px;">
                    <?php foreach ($officialAnnouncements as $ann): ?>
                    <a href="<?php echo BASE_URL; ?>/landing/announcements.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:14px; padding:14px 20px; background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.18); border-radius:var(--db-radius); transition:all 0.2s ease;">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(245,158,11,0.12); color:#fcd34d; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0;">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:0.78rem; font-weight:700; color:#fcd34d; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:3px;">Official Announcement</div>
                            <div style="font-size:0.9rem; font-weight:600; color:var(--db-text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($ann['title']); ?></div>
                        </div>
                        <i class="bi bi-chevron-right" style="color:var(--db-text-muted); font-size:0.85rem; flex-shrink:0;"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ── Two-Column: Announcements + Requests ── -->
                <div class="row g-4 align-items-start" style="margin-bottom:24px;">
                    <!-- Announcements -->
                    <div class="col-lg-6">
                        <div class="db-card db-reveal db-d4">
                            <div class="db-card-header">
                                <h5><i class="bi bi-megaphone" style="color:#fcd34d;"></i> Recent Announcements</h5>
                            </div>
                            <div class="db-card-body" style="max-height:320px; overflow-y:auto;">
                                <?php if (!empty($recentAnnouncements)): ?>
                                    <?php foreach ($recentAnnouncements as $ann):
                                        $typeInfo = match($ann['type'] ?? 'general') {
                                            'event'          => ['bg' => 'rgba(14,165,233,0.10)', 'color' => '#7dd3fc'],
                                            'health'         => ['bg' => 'rgba(16,185,129,0.10)', 'color' => '#6ee7b7'],
                                            'emergency'      => ['bg' => 'rgba(239,68,68,0.10)',  'color' => '#fca5a5'],
                                            'infrastructure' => ['bg' => 'rgba(245,158,11,0.10)', 'color' => '#fcd34d'],
                                            'education'      => ['bg' => 'rgba(139,92,246,0.10)', 'color' => '#c4b5fd'],
                                            'program'        => ['bg' => 'rgba(20,184,166,0.10)', 'color' => '#5eead4'],
                                            'meeting'        => ['bg' => 'rgba(6,182,212,0.10)',  'color' => '#67e8f9'],
                                            default          => ['bg' => 'rgba(99,102,241,0.10)', 'color' => '#a5b4fc'],
                                        };
                                        $isUnread = empty($ann['is_read']);
                                    ?>
                                        <a href="<?php echo BASE_URL; ?>/resident/announcement.php?id=<?php echo (int) $ann['id']; ?>" class="db-ann-link">
                                            <div class="db-ann-item">
                                                <div class="db-ann-dot <?php echo $isUnread ? 'unread' : 'read'; ?>"></div>
                                                <div class="db-ann-content">    
                                                    <div class="db-ann-title"><?php echo e($ann['title']); ?></div>
                                                    <div class="db-ann-meta">
                                                        <span class="db-ann-badge" style="background:<?php echo $typeInfo['bg']; ?>; color:<?php echo $typeInfo['color']; ?>;">
                                                            <?php echo e(ucfirst($ann['type'] ?? 'general')); ?>
                                                        </span>
                                                        <?php if ($isUnread): ?>
                                                            <span class="db-ann-new"><i class="bi bi-circle-fill" style="font-size:0.4rem;"></i> New</span>
                                                        <?php endif; ?>
                                                        <span class="db-ann-date"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="db-empty">
                                        <div class="db-empty-icon" style="background:rgba(245,158,11,0.08); color:#fcd34d;">
                                            <i class="bi bi-megaphone"></i>
                                        </div>
                                        <h6>No Announcements</h6>
                                        <p>You're all caught up. Check back later for updates.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Requests -->
                    <div class="col-lg-6">
                        <div class="db-card db-reveal db-d5">
                            <div class="db-card-header">
                                <h5><i class="bi bi-file-earmark-text" style="color:#7dd3fc;"></i> My Recent Requests</h5>
                                <a href="<?php echo BASE_URL; ?>/resident/requests.php" class="db-view-all">View All <i class="bi bi-arrow-right"></i></a>
                            </div>
                            <div class="db-card-body" style="max-height:320px; overflow-y:auto;">
                                <?php if (!empty($myRequests)): ?>
                                    <div class="db-table-wrap">
                                        <table class="db-table">
                                            <thead>
                                                <tr>
                                                    <th>Ref</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($myRequests as $req):
                                                    $statusCfg = match($req['status'] ?? '') {
                                                        'submitted'        => ['bg' => 'rgba(14,165,233,0.12)', 'color' => '#7dd3fc',  'icon' => 'bi-send'],
                                                        'pending'          => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#fcd34d',  'icon' => 'bi-clock'],
                                                        'under_review'     => ['bg' => 'rgba(6,182,212,0.12)',  'color' => '#67e8f9',  'icon' => 'bi-eye'],
                                                        'approved'         => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#6ee7b7',  'icon' => 'bi-check-circle'],
                                                        'ready_for_pickup' => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#6ee7b7',  'icon' => 'bi-bag-check'],
                                                        'completed'        => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#6ee7b7',  'icon' => 'bi-check-all'],
                                                        'rejected'         => ['bg' => 'rgba(239,68,68,0.12)',  'color' => '#fca5a5',  'icon' => 'bi-x-circle'],
                                                        default            => ['bg' => 'rgba(148,163,184,0.10)','color' => '#94a3b8',  'icon' => 'bi-question-circle'],
                                                    };
                                                ?>
                                                    <tr>
                                                        <td class="col-ref">#<?php echo (int)$req['id']; ?></td>
                                                        <td class="col-type"><?php echo e($req['application_type']); ?></td>
                                                        <td>
                                                            <span class="db-status-badge" style="background:<?php echo $statusCfg['bg']; ?>;color:<?php echo $statusCfg['color']; ?>;">
                                                                <i class="bi <?php echo $statusCfg['icon']; ?>" style="font-size:0.6rem;"></i>
                                                                <?php echo e(ucwords(str_replace('_', ' ', $req['status'] ?? ''))); ?>
                                                            </span>
                                                        </td>
                                                        <td class="col-date"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="db-empty">
                                        <div class="db-empty-icon" style="background:rgba(14,165,233,0.08); color:#7dd3fc;">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <h6>No Requests Yet</h6>
                                        <p>Start by requesting a certificate or document.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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
    var reveals = document.querySelectorAll('.db-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('db-visible');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>