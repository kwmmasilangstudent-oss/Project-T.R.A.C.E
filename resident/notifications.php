<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['resident']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    requireCsrf();
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId) {
        try {
            $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
        } catch (Throwable $e) {}
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['read_all']) && $_GET['read_all'] === '1') {
    try {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
    } catch (Throwable $e) {}
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss'])) {
    requireCsrf();
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId) {
        try {
            $stmt = $pdo->prepare('SELECT created_by FROM notifications WHERE id = ? AND user_id = ?');
            $stmt->execute([$notificationId, $userId]);
            $createdBy = $stmt->fetchColumn();
            if ($createdBy && (int) $createdBy !== $userId) {
                $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
            }
        } catch (Throwable $e) {}
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$paginator = [];
$notifications = [];
$unreadCount = 0;
try {
    $paginator = paginate(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ?',
        [$userId],
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC',
        [$userId]
    );
    $notifications = $paginator['data'];

    $unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([$userId]);
    $unreadCount = (int) $unreadStmt->fetchColumn();
} catch (Throwable $e) {
    $paginator = [];
    $notifications = [];
    $unreadCount = 0;
}

$totalCount = count($notifications);
$readCount = $totalCount - $unreadCount;

/* ── Auto-detect notification type from message content ── */
function detectNotifType($msg) {
    $msg = strtolower($msg);
    if (str_contains($msg, 'approved') || str_contains($msg, 'completed') || str_contains($msg, 'ready')) {
        return 'success';
    }
    if (str_contains($msg, 'rejected') || str_contains($msg, 'denied') || str_contains($msg, 'failed')) {
        return 'error';
    }
    if (str_contains($msg, 'urgent') || str_contains($msg, 'emergency')) {
        return 'urgent';
    }
    if (str_contains($msg, 'review') || str_contains($msg, 'pending') || str_contains($msg, 'processing')) {
        return 'info';
    }
    if (str_contains($msg, 'appointment') || str_contains($msg, 'schedule') || str_contains($msg, 'booked')) {
        return 'schedule';
    }
    return 'general';
}

$typeStyles = [
    'success'  => ['bg' => 'rgba(16,185,129,0.10)',  'color' => '#6ee7b7', 'icon' => 'bi-check-circle-fill',        'border' => 'rgba(16,185,129,0.20)'],
    'error'    => ['bg' => 'rgba(239,68,68,0.10)',    'color' => '#fca5a5', 'icon' => 'bi-x-circle-fill',            'border' => 'rgba(239,68,68,0.20)'],
    'urgent'   => ['bg' => 'rgba(239,68,68,0.10)',    'color' => '#fca5a5', 'icon' => 'bi-exclamation-octagon-fill',  'border' => 'rgba(239,68,68,0.20)'],
    'info'     => ['bg' => 'rgba(14,165,233,0.10)',   'color' => '#7dd3fc', 'icon' => 'bi-info-circle-fill',          'border' => 'rgba(14,165,233,0.20)'],
    'schedule' => ['bg' => 'rgba(245,158,11,0.10)',   'color' => '#fcd34d', 'icon' => 'bi-calendar-check',            'border' => 'rgba(245,158,11,0.20)'],
    'general'  => ['bg' => 'rgba(139,92,246,0.10)',   'color' => '#c4b5fd', 'icon' => 'bi-bell-fill',                 'border' => 'rgba(139,92,246,0.20)'],
];

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
    --nf-accent: #10b981;
    --nf-accent-dark: #059669;
    --nf-accent-glow: rgba(16,185,129,0.15);
    --nf-violet: #8b5cf6;
    --nf-sky: #0ea5e9;
    --nf-amber: #f59e0b;
    --nf-red: #ef4444;
    --nf-bg: #0f172a;
    --nf-surface: rgba(255,255,255,0.04);
    --nf-surface-hover: rgba(255,255,255,0.07);
    --nf-border: rgba(255,255,255,0.08);
    --nf-text: #f1f5f9;
    --nf-text-secondary: #94a3b8;
    --nf-text-muted: #64748b;
    --nf-radius: 12px;
    --nf-radius-lg: 18px;
}

/* ═══════════════════════════════
   GLOBAL
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--nf-bg) !important;
    color: var(--nf-text);
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
.nf-grid-overlay {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

.nf-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: nfFloat 22s ease-in-out infinite;
}

.nf-orb.o1 { width: 500px; height: 500px; background: rgba(16,185,129,0.06); top: -12%; left: -10%; }
.nf-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -8%; right: -5%; animation-delay: -12s; }
.nf-orb.o3 { width: 260px; height: 260px; background: rgba(14,165,233,0.05); top: 50%; left: 45%; animation-delay: -6s; animation-duration: 28s; }

@keyframes nfFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(30px, -20px) scale(1.04); }
    66%      { transform: translate(-20px, 15px) scale(0.96); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.nf-page-wrapper {
    position: relative;
    z-index: 1;
    min-height: 100vh;
}

.nf-page-wrapper .container-fluid { padding: 0; }
.nf-page-wrapper .row { margin: 0; min-height: 100vh; }

/* ═══════════════════════════════
   SIDEBAR
   ═══════════════════════════════ */
.nf-sidebar-col {
    background: rgba(15,23,42,0.60);
    backdrop-filter: blur(30px);
    border-right: 1px solid var(--nf-border);
    padding: 0 !important;
    min-height: 100vh;
}

.nf-sidebar-col .sidebar,
.nf-sidebar-col .sidebar-menu,
.nf-sidebar-col .sidebar-header,
.nf-sidebar-col .sidebar-nav,
.nf-sidebar-col ul,
.nf-sidebar-col li,
.nf-sidebar-col a {
    background: transparent !important;
    color: var(--nf-text-secondary) !important;
}

.nf-sidebar-col a:hover,
.nf-sidebar-col .active a,
.nf-sidebar-col .active {
    background: var(--nf-surface-hover) !important;
    color: var(--nf-text) !important;
}

.nf-sidebar-col .sidebar-header h4,
.nf-sidebar-col .sidebar-header h5,
.nf-sidebar-col .sidebar-header h3 {
    color: var(--nf-text) !important;
}

/* ═══════════════════════════════
   MAIN CONTENT
   ═══════════════════════════════ */
.nf-main-col {
    padding: 40px 48px 60px !important;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.nf-page-header {
    margin-bottom: 32px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.nf-page-header-text { flex: 1; min-width: 200px; }

.nf-page-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.20);
    border-radius: 100px;
    color: #6ee7b7;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
}

.nf-page-badge i { font-size: 0.8rem; }

.nf-page-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.nf-page-title span {
    background: linear-gradient(135deg, var(--nf-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nf-page-desc {
    font-size: 0.95rem;
    color: var(--nf-text-muted);
    line-height: 1.6;
    max-width: 600px;
}

.nf-page-divider {
    width: 48px;
    height: 3px;
    background: linear-gradient(135deg, var(--nf-accent), #34d399);
    border-radius: 2px;
    margin-top: 20px;
}

/* ═══════════════════════════════
   STAT PILLS + ACTIONS
   ═══════════════════════════════ */
.nf-stats-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
}

.nf-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--nf-border);
    border-radius: var(--nf-radius);
    backdrop-filter: blur(12px);
    transition: all 0.3s ease;
}

.nf-stat-pill:hover {
    border-color: rgba(255,255,255,0.14);
    background: rgba(255,255,255,0.05);
}

.nf-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.nf-stat-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 1px;
}

.nf-stat-label {
    font-size: 0.72rem;
    color: var(--nf-text-muted);
    font-weight: 500;
}

.nf-mark-all-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.20);
    border-radius: var(--nf-radius);
    color: #6ee7b7;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
    white-space: nowrap;
}

.nf-mark-all-btn:hover {
    background: rgba(16,185,129,0.18);
    border-color: rgba(16,185,129,0.35);
    color: #a7f3d0;
    transform: translateY(-2px);
}

/* ═══════════════════════════════
   NOTIFICATION ITEMS
   ═══════════════════════════════ */
.nf-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.nf-item {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--nf-border);
    border-radius: var(--nf-radius-lg);
    overflow: hidden;
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    position: relative;
    backdrop-filter: blur(10px);
}

.nf-item::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
}

.nf-item:hover {
    border-color: rgba(255,255,255,0.14);
    box-shadow: 0 8px 40px rgba(0,0,0,0.20);
}

/* Unread left accent */
.nf-item.unread {
    border-left: 4px solid var(--nf-accent);
    background: linear-gradient(135deg, rgba(16,185,129,0.03), rgba(255,255,255,0.02));
}

.nf-item-inner {
    padding: 18px 22px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

/* Type icon */
.nf-item-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

/* Content */
.nf-item-content { flex-grow: 1; min-width: 0; }

.nf-item-message {
    font-size: 0.9rem;
    color: #cbd5e1;
    line-height: 1.6;
    margin-bottom: 8px;
}

.nf-item.unread .nf-item-message {
    font-weight: 600;
    color: #e2e8f0;
}

.nf-item.read .nf-item-message {
    color: var(--nf-text-muted);
}

.nf-item-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.nf-item-time {
    font-size: 0.78rem;
    color: var(--nf-text-muted);
    display: flex;
    align-items: center;
    gap: 5px;
}

.nf-item-time i { font-size: 0.72rem; }

.nf-unread-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    background: rgba(16,185,129,0.10);
    color: #6ee7b7;
    border-radius: 100px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.nf-unread-tag i { font-size: 0.4rem; }

.nf-item-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: rgba(16,185,129,0.06);
    border: 1px solid rgba(16,185,129,0.12);
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6ee7b7;
    text-decoration: none;
    transition: all 0.2s ease;
}

.nf-item-link:hover {
    background: rgba(16,185,129,0.12);
    border-color: rgba(16,185,129,0.25);
    color: #a7f3d0;
}

.nf-item-link i { font-size: 0.78rem; }

/* Actions */
.nf-item-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    margin-top: 2px;
}

.nf-mark-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.18);
    border-radius: 10px;
    color: #6ee7b7;
    font-size: 0.76rem;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
}

.nf-mark-btn:hover {
    background: rgba(16,185,129,0.15);
    border-color: rgba(16,185,129,0.30);
    transform: translateY(-1px);
}

.nf-mark-btn i { font-size: 0.75rem; }

.nf-dismiss-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    background: rgba(239,68,68,0.06);
    border: 1px solid rgba(239,68,68,0.15);
    border-radius: 10px;
    color: #fca5a5;
    font-size: 0.76rem;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
}

.nf-dismiss-btn:hover {
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.25);
    transform: translateY(-1px);
}

.nf-dismiss-btn i { font-size: 0.75rem; }

.nf-read-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    background: rgba(16,185,129,0.06);
    border: 1px solid rgba(16,185,129,0.10);
    border-radius: 100px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6ee7b7;
}

.nf-read-badge i { font-size: 0.6rem; }

/* ═══════════════════════════════
   EMPTY STATE
   ═══════════════════════════════ */
.nf-empty {
    text-align: center;
    padding: 60px 40px;
    position: relative;
}

.nf-empty::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 30% 30%, rgba(16,185,129,0.04) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 70%, rgba(139,92,246,0.03) 0%, transparent 40%);
    pointer-events: none;
    border-radius: inherit;
}

.nf-empty-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: var(--nf-accent);
    margin: 0 auto 18px;
    position: relative;
}

.nf-empty-icon::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 26px;
    border: 2px dashed rgba(16,185,129,0.12);
}

.nf-empty h4 {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.3rem;
    color: #ffffff;
    margin-bottom: 8px;
    position: relative;
}

.nf-empty p {
    font-size: 0.92rem;
    color: var(--nf-text-muted);
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.6;
    position: relative;
}

/* ═══════════════════════════════
   GLASS CARD (for empty wrapper)
   ═══════════════════════════════ */
.nf-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--nf-border);
    border-radius: var(--nf-radius-lg);
    backdrop-filter: blur(20px);
    position: relative;
    overflow: hidden;
}

.nf-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
}

/* ═══════════════════════════════
   REVEAL ANIMATIONS
   ═══════════════════════════════ */
.nf-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.nf-reveal.nf-visible {
    opacity: 1;
    transform: translateY(0);
}

.nf-d1 { transition-delay: 0.05s; }
.nf-d2 { transition-delay: 0.10s; }
.nf-d3 { transition-delay: 0.15s; }
.nf-d4 { transition-delay: 0.20s; }
.nf-d5 { transition-delay: 0.25s; }
.nf-d6 { transition-delay: 0.30s; }
.nf-d7 { transition-delay: 0.35s; }
.nf-d8 { transition-delay: 0.40s; }
.nf-d9 { transition-delay: 0.45s; }
.nf-d10 { transition-delay: 0.50s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 991.98px) {
    .nf-sidebar-col {
        min-height: auto !important;
        border-right: none !important;
        border-bottom: 1px solid var(--nf-border) !important;
    }
    .nf-main-col { padding: 32px 24px 50px !important; }
}

@media (max-width: 767.98px) {
    .nf-main-col { padding: 24px 18px 40px !important; }
    .nf-page-title { font-size: 1.4rem; }
    .nf-page-header { flex-direction: column; align-items: flex-start; }
    .nf-stats-row { gap: 8px; }
    .nf-stat-pill { flex: 1; min-width: 0; }
    .nf-mark-all-btn { flex: 1; justify-content: center; }
    .nf-item-inner { padding: 14px 16px; gap: 12px; }
    .nf-item-icon { width: 36px; height: 36px; font-size: 0.9rem; border-radius: 10px; }
    .nf-item-message { font-size: 0.85rem; }
    .nf-empty { padding: 40px 20px; }
}

@media (max-width: 480px) {
    .nf-main-col { padding: 20px 14px 36px !important; }
    .nf-page-title { font-size: 1.25rem; }
    .nf-stats-row { flex-direction: column; }
    .nf-stat-pill { width: 100%; }
    .nf-item-inner { flex-direction: column; gap: 10px; }
    .nf-item-actions { margin-left: 0; }
}
</style>

<!-- ═══════════════════════════════════════
     ATMOSPHERIC ELEMENTS
     ═══════════════════════════════════════ -->
<div class="nf-grid-overlay"></div>
<div class="nf-orb o1"></div>
<div class="nf-orb o2"></div>
<div class="nf-orb o3"></div>

<!-- ═══════════════════════════════════════
     PAGE LAYOUT
     ═══════════════════════════════════════ -->
<div class="nf-page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 p-0 nf-sidebar-col">
                <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 nf-main-col">

                <!-- ── Page Header ── -->
                <div class="nf-page-header nf-reveal nf-d1">
                    <div class="nf-page-header-text">
                        <div class="nf-page-badge">
                            <i class="bi bi-bell-fill"></i>
                            Alerts Center
                        </div>
                        <h1 class="nf-page-title">
                            My <span>Notifications</span>
                        </h1>
                        <p class="nf-page-desc">
                            Alerts and updates from the barangay regarding your requests, appointments, and announcements.
                        </p>
                        <div class="nf-page-divider"></div>
                    </div>
                </div>

                <!-- ── Stats + Actions Row ── -->
                <div class="nf-stats-row nf-reveal nf-d2">
                    <div class="nf-stat-pill">
                        <div class="nf-stat-icon" style="background:rgba(139,92,246,0.10); color:#c4b5fd;">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div>
                            <div class="nf-stat-value"><?php echo $unreadCount; ?></div>
                            <div class="nf-stat-label">Unread</div>
                        </div>
                    </div>
                    <div class="nf-stat-pill">
                        <div class="nf-stat-icon" style="background:rgba(16,185,129,0.10); color:#6ee7b7;">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div>
                            <div class="nf-stat-value"><?php echo $readCount; ?></div>
                            <div class="nf-stat-label">Read</div>
                        </div>
                    </div>
                    <div class="nf-stat-pill">
                        <div class="nf-stat-icon" style="background:rgba(14,165,233,0.10); color:#7dd3fc;">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <div>
                            <div class="nf-stat-value"><?php echo $totalCount; ?></div>
                            <div class="nf-stat-label">Total</div>
                        </div>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <a href="?read_all=1" class="nf-mark-all-btn">
                            <i class="bi bi-check-all"></i> Mark All Read
                        </a>
                    <?php endif; ?>
                </div>

                <!-- ── Notifications List ── -->
                <?php if (!empty($notifications)): ?>

                    <div class="nf-list">
                        <?php foreach ($notifications as $i => $notif):
                            $isUnread = empty($notif['is_read']);
                            $detected = detectNotifType($notif['message'] ?? '');
                            $ts = $typeStyles[$detected];
                            $delay = 'nf-d' . min($i + 2, 10);
                            $hasLink = !empty($notif['link']);
                        ?>
                            <div class="nf-item nf-reveal <?php echo $delay; ?> <?php echo $isUnread ? 'unread' : 'read'; ?>">
                                <div class="nf-item-inner">
                                    <div class="nf-item-icon" style="background:<?php echo $ts['bg']; ?>; color:<?php echo $ts['color']; ?>; border:1px solid <?php echo $ts['border']; ?>;">
                                        <i class="bi <?php echo $ts['icon']; ?>"></i>
                                    </div>

                                    <div class="nf-item-content">
                                        <div class="nf-item-message"><?php echo nl2br(e($notif['message'])); ?></div>
                                        <div class="nf-item-meta">
                                            <span class="nf-item-time">
                                                <i class="bi bi-clock"></i>
                                                <?php echo date('M d, Y \a\t h:i A', strtotime($notif['created_at'])); ?>
                                            </span>
                                            <?php if ($isUnread): ?>
                                                <span class="nf-unread-tag"><i class="bi bi-circle-fill"></i> New</span>
                                            <?php endif; ?>
                                            <?php if ($hasLink): ?>
                                                <a href="<?php echo e($notif['link']); ?>" class="nf-item-link">
                                                    <i class="bi bi-arrow-right-short"></i> View Details
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="nf-item-actions">
                                        <?php if ($isUnread): ?>
                                            <form method="post" class="d-inline">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="notification_id" value="<?php echo (int) $notif['id']; ?>">
                                                <button type="submit" name="mark_read" class="nf-mark-btn">
                                                    <i class="bi bi-check-lg"></i> Mark Read
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="nf-read-badge">
                                                <i class="bi bi-check-circle-fill"></i> Read
                                            </span>
                                        <?php endif; ?>
                                        <?php if (empty($notif['created_by']) || (int) $notif['created_by'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Dismiss this notification permanently?');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="notification_id" value="<?php echo (int) $notif['id']; ?>">
                                                <button type="submit" name="dismiss" class="nf-dismiss-btn">
                                                    <i class="bi bi-x-circle"></i> Dismiss
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($paginator)) echo renderPagination($paginator); ?>

                <?php else: ?>

                    <!-- ── Empty State ── -->
                    <div class="nf-card nf-reveal nf-d2">
                        <div class="nf-empty">
                            <div class="nf-empty-icon">
                                <i class="bi bi-bell-slash"></i>
                            </div>
                            <h4>No Notifications Yet</h4>
                            <p>You don't have any notifications at the moment. We'll alert you when there are updates on your requests or important community announcements.</p>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.nf-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('nf-visible');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>