<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary', 'admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$currentRole = getCurrentRole();

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

$announcementTypes = [
    'general' => 'General',
    'event' => 'Event',
    'health' => 'Health',
    'emergency' => 'Emergency',
    'infrastructure' => 'Infrastructure',
    'education' => 'Education',
    'news' => 'News',
    'program' => 'Program',
    'meeting' => 'Meeting',
    'maintenance' => 'Maintenance'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['archive_announcement'])) {
    requireCsrf();
    $attachment = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '_' . basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            $attachment = 'assets/uploads/' . $fileName;
        }
    }

    $title = trim($_POST['title'] ?? '');
    $announcementId = publishAnnouncement($pdo, array_merge($_POST, ['attachment_path' => $attachment]));

    if ($announcementId !== null) {
        logAudit('create_announcement', 'Created announcement: ' . $title);
        $_SESSION['_flash_success'] = 'Announcement published successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $pdo->prepare('UPDATE announcements SET is_active = 0 WHERE id = ?');
    $stmt->execute([$deleteId]);
    $pdo->prepare('DELETE FROM announcement_reads WHERE announcement_id = ?')->execute([$deleteId]);
    logAudit('delete_announcement', 'Deleted announcement ID: ' . $deleteId);
    $_SESSION['_flash_success'] = 'Announcement deleted.';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_announcement'])) {
    requireCsrf();
    $archiveId = (int) ($_POST['archive_id'] ?? 0);
    if ($archiveId) {
        $stmt = $pdo->prepare('UPDATE announcements SET is_active = 0 WHERE id = ?');
        $stmt->execute([$archiveId]);
        $pdo->prepare('DELETE FROM announcement_reads WHERE announcement_id = ?')->execute([$archiveId]);
        logAudit('archive_announcement', 'Archived announcement ID: ' . $archiveId);
        $_SESSION['_flash_success'] = 'Announcement archived.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$pinnedFilter = trim($_GET['pinned'] ?? '');

$where = '';
$params = [];

if ($search) {
    $where .= ' AND (a.title LIKE ? OR a.content LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($typeFilter) {
    $where .= ' AND a.type = ?';
    $params[] = $typeFilter;
}
if ($priorityFilter) {
    $where .= ' AND a.priority = ?';
    $params[] = $priorityFilter;
}
if ($pinnedFilter !== '') {
    $where .= ' AND a.is_pinned = ?';
    $params[] = $pinnedFilter;
}

$paginator = paginate(
    'SELECT COUNT(*) FROM (SELECT a.id FROM announcements a LEFT JOIN announcement_reads ar ON ar.announcement_id = a.id WHERE a.is_active = 1' . $where . ' GROUP BY a.id) as cnt',
    $params,
    'SELECT a.*, COUNT(ar.id) as total_reads, SUM(ar.is_read) as total_unreads FROM announcements a LEFT JOIN announcement_reads ar ON ar.announcement_id = a.id WHERE a.is_active = 1' . $where . ' GROUP BY a.id ORDER BY a.is_pinned DESC, a.created_at DESC',
    $params
);
$announcements = $paginator['data'];

$statsQuery = 'SELECT 
    COUNT(*) as total,
    SUM(type = "emergency") as emergency,
    SUM(type = "event") as event,
    SUM(type = "news") as news,
    SUM(is_pinned = 1) as pinned
    FROM announcements WHERE is_active = 1';
$stats = $pdo->query($statsQuery)->fetch();

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
    --an-primary: #1a56db;
    --an-primary-dark: #1042a3;
    --an-primary-light: #e8effc;
    --an-accent: #10b981;
    --an-accent-dark: #059669;
    --an-teal: #14b8a6;
    --an-amber: #f59e0b;
    --an-red: #ef4444;
    --an-violet: #8b5cf6;
    --an-sky: #0ea5e9;
    --an-bg: #0f172a;
    --an-surface: rgba(255,255,255,0.04);
    --an-surface-hover: rgba(255,255,255,0.07);
    --an-card-bg: rgba(255,255,255,0.03);
    --an-text: #f0f4f8;
    --an-text-secondary: #94a3b8;
    --an-text-muted: #64748b;
    --an-text-dim: #475569;
    --an-border: rgba(255,255,255,0.08);
    --an-border-light: rgba(255,255,255,0.12);
    --an-radius: 12px;
    --an-radius-lg: 16px;
    --an-radius-xl: 20px;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--an-bg) !important;
    color: var(--an-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.an-page-wrapper::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.an-page-wrapper::after {
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

.an-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: anFloat 22s ease-in-out infinite;
}
.an-orb.o1 { width: 500px; height: 500px; background: rgba(16,185,129,0.06); top: -15%; left: -10%; }
.an-orb.o2 { width: 350px; height: 350px; background: rgba(139,92,246,0.05); bottom: -10%; right: -8%; animation-delay: -12s; }
.an-orb.o3 { width: 280px; height: 280px; background: rgba(14,165,233,0.05); top: 50%; left: 40%; animation-delay: -6s; animation-duration: 28s; }

@keyframes anFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.an-page-wrapper { min-height: 100vh; position: relative; z-index: 1; }

.an-layout { display: flex; min-height: 100vh; }

.an-sidebar-col {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--an-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.an-main-col {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.an-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.an-page-header-left { flex: 1; min-width: 260px; }

.an-page-badge {
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

.an-page-badge .an-dot {
    width: 7px; height: 7px;
    background: var(--an-accent);
    border-radius: 50%;
    animation: anPulse 2s ease-in-out infinite;
}

@keyframes anPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.an-page-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.an-page-title span {
    background: linear-gradient(135deg, var(--an-accent), #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.an-page-desc {
    font-size: 0.92rem;
    color: var(--an-text-secondary);
    line-height: 1.6;
    max-width: 520px;
}

.an-count-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(14,165,233,0.10);
    border: 1px solid rgba(14,165,233,0.25);
    border-radius: 100px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #7dd3fc;
    white-space: nowrap;
}
.an-count-pill i { font-size: 0.9rem; }

/* ═══════════════════════════════
   SUCCESS ALERT
   ═══════════════════════════════ */
.an-alert-success {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: var(--an-radius);
    color: #6ee7b7;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: anSlideDown 0.4s ease;
}
.an-alert-success i { font-size: 1.15rem; color: var(--an-accent); flex-shrink: 0; }

@keyframes anSlideDown {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════
   GLASS CARDS
   ═══════════════════════════════ */
.an-card {
    background: var(--an-card-bg);
    border: 1px solid var(--an-border);
    border-radius: var(--an-radius-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.an-card:hover { border-color: rgba(255,255,255,0.12); }

.an-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.an-card-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.an-card-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.an-card-subtitle {
    font-size: 0.82rem;
    color: var(--an-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* ═══════════════════════════════
   FORM ELEMENTS
   ═══════════════════════════════ */
.an-form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.an-form-label i { font-size: 0.82rem; color: var(--an-text-muted); }

.an-form-input,
.an-form-select,
.an-form-textarea {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--an-radius);
    font-size: 0.88rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}

.an-form-input::placeholder,
.an-form-textarea::placeholder { color: #475569; }

.an-form-input:focus,
.an-form-select:focus,
.an-form-textarea:focus {
    border-color: var(--an-accent);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    background: rgba(255,255,255,0.07);
}

/* datetime-local color-scheme */
.an-form-input[type="datetime-local"] {
    color-scheme: dark;
}

.an-form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}
.an-form-select option { background: #1e293b; color: #e2e8f0; }

.an-form-textarea { resize: vertical; min-height: 80px; }

/* File input */
.an-form-file {
    width: 100%;
    padding: 10px 14px;
    background: rgba(255,255,255,0.05);
    border: 1px dashed rgba(255,255,255,0.15);
    border-radius: var(--an-radius);
    font-size: 0.85rem;
    color: var(--an-text-secondary);
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.25s ease;
}
.an-form-file:hover {
    border-color: rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.07);
}
.an-form-file::-webkit-file-upload-button {
    background: rgba(16,185,129,0.12);
    color: #6ee7b7;
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.8rem;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    margin-right: 12px;
    transition: all 0.2s ease;
}
.an-form-file::-webkit-file-upload-button:hover { background: rgba(16,185,129,0.2); }

/* Custom checkbox */
.an-check-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: var(--an-radius);
    cursor: pointer;
    transition: all 0.25s ease;
    user-select: none;
}
.an-check-wrap:hover {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.16);
}

.an-check-input {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 2px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.05);
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.an-check-input:checked {
    background: var(--an-accent);
    border-color: var(--an-accent);
}
.an-check-input:checked::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 6px;
    width: 5px;
    height: 10px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
.an-check-input:focus-visible {
    box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
}

.an-check-text {
    font-size: 0.88rem;
    font-weight: 500;
    color: #cbd5e1;
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.an-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--an-radius);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}
.an-btn i { transition: transform 0.2s ease; }

.an-btn-primary {
    background: linear-gradient(135deg, var(--an-accent), var(--an-accent-dark));
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(16,185,129,0.25);
}
.an-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.35);
    color: #ffffff;
}
.an-btn-primary:active { transform: translateY(0); }
.an-btn-primary:hover i { transform: translateX(3px); }

.an-btn-search {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: #e2e8f0;
}
.an-btn-search:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    color: #ffffff;
}

/* ═══════════════════════════════
   FILTER BAR
   ═══════════════════════════════ */
.an-filter-bar {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.an-filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.an-filter-group.grow { flex: 1; min-width: 180px; }

/* ═══════════════════════════════
   ANNOUNCEMENT LIST
   ═══════════════════════════════ */
.an-list { display: flex; flex-direction: column; gap: 2px; }

.an-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    padding: 20px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: background 0.2s ease;
}
.an-item:last-child { border-bottom: none; }

.an-item.pinned {
    background: rgba(245,158,11,0.04);
    margin: 0 -12px;
    padding: 20px 12px;
    border-radius: var(--an-radius);
    border-bottom: 1px solid rgba(245,158,11,0.1);
}
.an-item.pinned:last-child { border-bottom: none; }

.an-item-body { flex: 1; min-width: 0; }

.an-item-top {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

.an-item-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #f1f5f9;
}

.an-item-date {
    font-size: 0.78rem;
    color: var(--an-text-dim);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.an-item-expires {
    font-size: 0.75rem;
    color: var(--an-amber);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.an-item-content {
    font-size: 0.85rem;
    color: var(--an-text-muted);
    line-height: 1.6;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.an-item-attachment {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 8px;
    font-size: 0.8rem;
    color: #7dd3fc;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}
.an-item-attachment:hover { color: #bae6fd; }
.an-item-attachment i { font-size: 0.82rem; }

/* Badges */
.an-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: capitalize;
    white-space: nowrap;
}

.an-badge-type {
    background: rgba(100,116,139,0.15);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}
.an-badge-normal {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.an-badge-high {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.an-badge-urgent {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
}
.an-badge-pinned {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}

/* Actions */
.an-actions {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    flex-shrink: 0;
}
.an-btn-sm {
    padding: 7px 14px;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.an-btn-archive {
    background: rgba(100,116,139,0.12);
    border-color: rgba(100,116,139,0.25);
    color: #94a3b8;
}
.an-btn-archive:hover { background: rgba(100,116,139,0.2); color: #cbd5e1; }

.an-btn-delete {
    background: rgba(239,68,68,0.10);
    border-color: rgba(239,68,68,0.2);
    color: #fca5a5;
}
.an-btn-delete:hover { background: rgba(239,68,68,0.18); color: #fecaca; }

/* Empty */
.an-empty { text-align: center; padding: 48px 20px; }
.an-empty-icon {
    width: 64px; height: 64px;
    border-radius: 18px;
    background: rgba(100,116,139,0.1);
    border: 1px solid rgba(100,116,139,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--an-text-dim);
    margin: 0 auto 16px;
}
.an-empty-text { font-size: 0.92rem; color: var(--an-text-muted); margin: 0; }

/* ═══════════════════════════════
   FORM GRID
   ═══════════════════════════════ */
.an-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 18px;
}
.an-form-grid .an-span-2 { grid-column: span 2; }
.an-form-grid .an-span-3 { grid-column: span 3; }

@media (max-width: 991px) {
    .an-form-grid { grid-template-columns: 1fr 1fr; }
    .an-form-grid .an-span-2 { grid-column: span 2; }
    .an-form-grid .an-span-3 { grid-column: span 2; }
}
@media (max-width: 640px) {
    .an-form-grid { grid-template-columns: 1fr; }
    .an-form-grid .an-span-2,
    .an-form-grid .an-span-3 { grid-column: span 1; }
}

/* ═══════════════════════════════
   REVEALS
   ═══════════════════════════════ */
.an-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.an-reveal.an-visible { opacity: 1; transform: translateY(0); }

.an-d1 { transition-delay: 0.05s; }
.an-d2 { transition-delay: 0.1s; }
.an-d3 { transition-delay: 0.15s; }
.an-d4 { transition-delay: 0.2s; }
.an-d5 { transition-delay: 0.25s; }
.an-d6 { transition-delay: 0.3s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .an-main-col { padding: 32px 36px; }
}
@media (max-width: 991.98px) {
    .an-layout { flex-direction: column; }
    .an-sidebar-col {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--an-border);
    }
    .an-main-col { padding: 28px 24px; }
    .an-page-header { flex-direction: column; gap: 16px; }
    .an-item { flex-direction: column; gap: 12px; }
    .an-actions { align-self: flex-start; }
    .an-filter-bar { flex-direction: column; }
    .an-filter-group.grow { min-width: 100%; }
}
@media (max-width: 767.98px) {
    .an-main-col { padding: 24px 16px; }
    .an-card { padding: 24px 20px; }
    .an-page-title { font-size: 1.4rem; }
}
@media (max-width: 480px) {
    .an-main-col { padding: 20px 14px; }
    .an-card { padding: 20px 16px; border-radius: 16px; }
    .an-item-top { flex-direction: column; align-items: flex-start; gap: 6px; }
    .an-item.pinned { margin: 0 -8px; padding: 16px 8px; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="an-page-wrapper">
    <div class="an-orb o1"></div>
    <div class="an-orb o2"></div>
    <div class="an-orb o3"></div>

    <div class="an-layout">
        <!-- Sidebar -->
        <div class="an-sidebar-col">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <div class="an-main-col">

            <!-- Header -->
            <div class="an-page-header an-reveal an-d1">
                <div class="an-page-header-left">
                    <div class="an-page-badge">
                        <span class="an-dot"></span>
                        Management
                    </div>
                    <h1 class="an-page-title">Announcement <span>Management</span></h1>
                    <p class="an-page-desc">Publish barangay announcements, news, events, emergency alerts, and programs to keep your community informed.</p>
                </div>
                <div class="an-count-pill an-reveal an-d2">
                    <i class="bi bi-megaphone-fill"></i>
                    <?php echo count($announcements); ?> Active
                </div>
                <div class="d-flex gap-2 flex-wrap" style="margin-top:12px;">
                    <span class="badge" style="background:rgba(14,165,233,0.12); color:#7dd3fc; border:1px solid rgba(14,165,233,0.25);">Total: <?php echo (int) ($stats['total'] ?? 0); ?></span>
                    <span class="badge" style="background:rgba(239,68,68,0.12); color:#fca5a5; border:1px solid rgba(239,68,68,0.25);">Emergency: <?php echo (int) ($stats['emergency'] ?? 0); ?></span>
                    <span class="badge" style="background:rgba(20,184,166,0.12); color:#5eead4; border:1px solid rgba(20,184,166,0.25);">Events: <?php echo (int) ($stats['event'] ?? 0); ?></span>
                    <span class="badge" style="background:rgba(245,158,11,0.12); color:#fcd34d; border:1px solid rgba(245,158,11,0.25);">Pinned: <?php echo (int) ($stats['pinned'] ?? 0); ?></span>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="an-alert-success an-reveal an-d2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Create -->
            <div class="an-card an-reveal an-d3">
                <div class="an-card-header">
                    <div class="an-card-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <div>
                        <h5 class="an-card-title">Create Announcement</h5>
                        <p class="an-card-subtitle">Fill in the details below to publish a new announcement.</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <div class="an-form-grid">
                        <!-- Row 1: Title / Type / Priority -->
                        <div>
                            <label class="an-form-label"><i class="bi bi-type"></i> Title</label>
                            <input type="text" name="title" class="an-form-input" placeholder="Announcement title..." required>
                        </div>
                        <div>
                            <label class="an-form-label"><i class="bi bi-tag"></i> Type</label>
                            <select name="type" class="an-form-select">
                                <?php foreach ($announcementTypes as $value => $label): ?>
                                    <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="an-form-label"><i class="bi bi-flag"></i> Priority</label>
                            <select name="priority" class="an-form-select">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <!-- Row 2: Audience / Pinned / Expires -->
                        <div>
                            <label class="an-form-label"><i class="bi bi-people"></i> Audience</label>
                            <select name="audience" class="an-form-select">
                                <option value="all">All Residents</option>
                                <option value="secretary">Secretary Only</option>
                                <option value="admin">Admin Only</option>
                            </select>
                        </div>
                        <div>
                            <label class="an-form-label"><i class="bi bi-calendar3"></i> Expires At</label>
                            <input type="datetime-local" name="expires_at" class="an-form-input">
                        </div>
                        <div>
                            <label class="an-form-label"><i class="bi bi-paperclip"></i> Attachment</label>
                            <input type="file" name="attachment" class="an-form-file">
                        </div>

                        <!-- Row 3: Content full-width -->
                        <div class="an-span-3">
                            <label class="an-form-label"><i class="bi bi-text-left"></i> Content</label>
                            <textarea name="content" class="an-form-textarea" rows="3" placeholder="Write your announcement content here..." required></textarea>
                        </div>

                        <!-- Row 4: Pin checkbox full-width -->
                        <div class="an-span-3">
                            <label class="an-check-wrap">
                                <input type="checkbox" name="is_pinned" value="1" class="an-check-input">
                                <span class="an-check-text">
                                    <i class="bi bi-pushpin" style="margin-right:5px; color:var(--an-amber);"></i>
                                    Pin this announcement to the top of the list
                                </span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="submit" class="an-btn an-btn-primary">
                            <i class="bi bi-send-fill"></i>
                            <span>Publish Announcement</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filter -->
            <div class="an-card an-reveal an-d4">
                <div class="an-card-header">
                    <div class="an-card-icon" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <div>
                        <h5 class="an-card-title">Filter Announcements</h5>
                        <p class="an-card-subtitle">Search and narrow down results by type or priority.</p>
                    </div>
                </div>

                <form method="get">
                    <div class="an-filter-bar">
                        <div class="an-filter-group grow">
                            <label class="an-form-label"><i class="bi bi-search"></i> Search</label>
                            <input type="text" name="search" class="an-form-input" placeholder="Search announcements..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="an-filter-group">
                            <label class="an-form-label"><i class="bi bi-tag"></i> Type</label>
                            <select name="type" class="an-form-select">
                                <option value="">All Types</option>
                                <?php foreach ($announcementTypes as $value => $label): ?>
                                    <option value="<?php echo e($value); ?>" <?php echo $typeFilter === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="an-filter-group">
                            <label class="an-form-label"><i class="bi bi-flag"></i> Priority</label>
                            <select name="priority" class="an-form-select">
                                <option value="">All Priorities</option>
                                <option value="normal" <?php echo $priorityFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                        <div class="an-filter-group">
                            <label class="an-form-label"><i class="bi bi-pin-angle"></i> Pinned</label>
                            <select name="pinned" class="an-form-select">
                                <option value="">All</option>
                                <option value="1" <?php echo $pinnedFilter === '1' ? 'selected' : ''; ?>>Pinned Only</option>
                                <option value="0" <?php echo $pinnedFilter === '0' ? 'selected' : ''; ?>>Not Pinned</option>
                            </select>
                        </div>
                        <div class="an-filter-group">
                            <button type="submit" class="an-btn an-btn-search">
                                <i class="bi bi-search"></i>
                                <span>Filter</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Announcements List -->
            <div class="an-card an-reveal an-d5">
                <div class="an-card-header">
                    <div class="an-card-icon" style="background:rgba(139,92,246,0.12); color:#8b5cf6;">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    <div>
                        <h5 class="an-card-title">All Announcements</h5>
                        <p class="an-card-subtitle"><?php echo count($announcements); ?> active announcement<?php echo count($announcements) !== 1 ? 's' : ''; ?> found.</p>
                    </div>
                </div>

                <?php if (!empty($announcements)): ?>
                    <div class="an-list">
                        <?php foreach ($announcements as $index => $announcement): ?>
                            <?php $isPinned = !empty($announcement['is_pinned']); ?>
                            <div class="an-item<?php echo $isPinned ? ' pinned' : ''; ?>">
                                <div class="an-item-body">
                                    <div class="an-item-top">
                                        <span class="an-item-title">
                                            <?php if ($isPinned): ?>
                                                <i class="bi bi-pushpin-fill" style="color:var(--an-amber); margin-right:4px; font-size:0.8rem;"></i>
                                            <?php endif; ?>
                                            <?php echo e($announcement['title']); ?>
                                        </span>
                                        <span class="an-badge an-badge-type"><?php echo e($announcementTypes[$announcement['type']] ?? ucwords(str_replace('_', ' ', $announcement['type'] ?? 'general'))); ?></span>
                                        <span class="an-badge an-badge-<?php echo e($announcement['priority'] ?? 'normal'); ?>">
                                            <?php echo e($announcement['priority'] ?? 'normal'); ?>
                                        </span>
                                        <?php if ($isPinned): ?>
                                            <span class="an-badge an-badge-pinned">
                                                <i class="bi bi-pin-angle-fill"></i> Pinned
                                            </span>
                                        <?php endif; ?>
                                        <span class="an-item-date">
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                        </span>
                                        <?php if (!empty($announcement['expires_at'])): ?>
                                            <span class="an-item-expires">
                                                <i class="bi bi-clock-history"></i>
                                                Expires <?php echo date('M d, Y', strtotime($announcement['expires_at'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="an-item-content"><?php echo e($announcement['content']); ?></p>
                                    <small class="an-item-date" style="margin-top:6px;">
                                        Reads: <?php echo (int) ($announcement['total_reads'] ?? 0); ?> | Unread: <?php echo (int) ($announcement['total_unreads'] ?? 0); ?>
                                        <?php if (!empty($announcement['is_pinned'])): ?>
                                            <span class="an-badge an-badge-pinned" style="margin-left:6px;">
                                                <i class="bi bi-pin-angle-fill"></i> Pinned
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                    <?php if (!empty($announcement['attachment_path'])): ?>
                                        <a href="<?php echo asset($announcement['attachment_path']); ?>" target="_blank" class="an-item-attachment">
                                            <i class="bi bi-paperclip"></i> View Attachment
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="an-actions">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Archive this announcement?')">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="archive_id" value="<?php echo (int) $announcement['id']; ?>">
                                        <button type="submit" name="archive_announcement" class="an-btn-sm an-btn-archive">
                                            <i class="bi bi-archive"></i> Archive
                                        </button>
                                    </form>
                                    <a href="?delete=<?php echo (int) $announcement['id']; ?>" class="an-btn-sm an-btn-delete" onclick="return confirm('Delete permanently?')">
                                        <i class="bi bi-trash3"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($announcements)): ?>
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($paginator); ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="an-empty">
                        <div class="an-empty-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <p class="an-empty-text">No announcements found. Create one above or adjust your filters.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.an-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('an-visible');
        });
    }, 80);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>