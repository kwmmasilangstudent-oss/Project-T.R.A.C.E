<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['secretary', 'admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$currentRole = getCurrentRole();

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_document'])) {
    requireCsrf();
    $residentId = (int) ($_POST['resident_id'] ?? 0);
    $documentType = trim($_POST['document_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $templateId = (int) ($_POST['template_id'] ?? 0);

    $documentNumber = strtoupper($documentType) . '-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $controlNumber = strtoupper($documentType) . '-' . date('ym') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

    $qrPayload = 'document:' . $documentNumber . ':' . time();
    $qrPath = null;
    try {
        $qrDir = __DIR__ . '/../assets/uploads/qr';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }
        $qrFileName = 'doc_' . uniqid() . '.png';
        $qrPath = 'assets/uploads/qr/' . $qrFileName;
        require_once __DIR__ . '/../includes/qr.php';
        $qrImage = generateQrImage($qrPayload);
        file_put_contents($qrDir . '/' . $qrFileName, $qrImage);
    } catch (Throwable $e) {
        $qrPath = null;
    }

    $stmt = $pdo->prepare('INSERT INTO documents (resident_id, document_type, document_number, control_number, purpose, status, qr_code_path, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$residentId, $documentType, $documentNumber, $controlNumber, $purpose, 'issued', $qrPath, $_SESSION['user_id'] ?? null]);
    $_SESSION['_flash_success'] = 'Document generated successfully. Document Number: ' . $documentNumber;
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_document'])) {
    requireCsrf();
    $documentId = (int) ($_POST['document_id'] ?? 0);
    $stmt = $pdo->prepare('UPDATE documents SET status = ? WHERE id = ?');
    $stmt->execute(['archived', $documentId]);
    $_SESSION['_flash_success'] = 'Document archived.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    requireCsrf();
    $documentId = (int) ($_POST['document_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $stmt = $pdo->prepare('UPDATE documents SET status = ? WHERE id = ?');
    $stmt->execute([$status, $documentId]);
    $_SESSION['_flash_success'] = 'Document status updated.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$archived = isset($_GET['archived']);

$residents = $pdo->query('SELECT id, full_name FROM residents ORDER BY full_name')->fetchAll();
$templates = $pdo->query('SELECT * FROM application_templates ORDER BY name')->fetchAll();

$where = '';
$params = [];
if ($search) {
    $where .= ' AND (d.document_number LIKE ? OR d.control_number LIKE ? OR r.full_name LIKE ? OR d.document_type LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($archived) {
    $where .= ' AND d.status = ?';
    $params[] = 'archived';
} else {
    $where .= ' AND d.status != ?';
    $params[] = 'archived';
}

$paginator = paginate(
    'SELECT COUNT(*) FROM documents d LEFT JOIN residents r ON r.id = d.resident_id WHERE 1=1' . $where,
    $params,
    'SELECT d.*, r.full_name FROM documents d LEFT JOIN residents r ON r.id = d.resident_id WHERE 1=1' . $where . ' ORDER BY d.created_at DESC',
    $params
);
$documents = $paginator['data'];

$statsQuery = 'SELECT
    COUNT(*) as total,
    SUM(status = "draft") as draft,
    SUM(status = "issued") as issued,
    SUM(status = "archived") as archived
    FROM documents';
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
   PROPERTIES
   ═══════════════════════════════ */
:root {
    --dc-accent: #10b981;
    --dc-accent-dark: #059669;
    --dc-sky: #0ea5e9;
    --dc-amber: #f59e0b;
    --dc-red: #ef4444;
    --dc-violet: #8b5cf6;
    --dc-teal: #14b8a6;
    --dc-bg: #0f172a;
    --dc-card: rgba(255,255,255,0.03);
    --dc-text: #f0f4f8;
    --dc-text-sec: #94a3b8;
    --dc-text-muted: #64748b;
    --dc-text-dim: #475569;
    --dc-border: rgba(255,255,255,0.08);
    --dc-border-lt: rgba(255,255,255,0.12);
    --dc-rad: 12px;
    --dc-rad-lg: 16px;
    --dc-rad-xl: 20px;
}

/* ═══════════════════════════════
   ATMOSPHERE
   ═══════════════════════════════ */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--dc-bg) !important;
    color: var(--dc-text);
}

.navbar, footer, .main-navbar { display: none !important; }

.dc-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}

.dc-page::after {
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

.dc-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    z-index: 0;
    animation: dcFloat 22s ease-in-out infinite;
}
.dc-orb.o1 { width: 460px; height: 460px; background: rgba(14,165,233,0.06); top: -14%; right: -8%; }
.dc-orb.o2 { width: 340px; height: 340px; background: rgba(16,185,129,0.06); bottom: -10%; left: -6%; animation-delay: -10s; }
.dc-orb.o3 { width: 260px; height: 260px; background: rgba(139,92,246,0.05); top: 55%; left: 30%; animation-delay: -6s; animation-duration: 26s; }

@keyframes dcFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%      { transform: translate(25px, -15px) scale(1.05); }
    66%      { transform: translate(-15px, 10px) scale(0.95); }
}

/* ═══════════════════════════════
   LAYOUT
   ═══════════════════════════════ */
.dc-page { min-height: 100vh; position: relative; z-index: 1; }

.dc-layout { display: flex; min-height: 100vh; }

.dc-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--dc-border);
    background: rgba(255,255,255,0.015);
    backdrop-filter: blur(30px);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.dc-main {
    flex: 1;
    min-width: 0;
    padding: 40px 48px;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════
   PAGE HEADER
   ═══════════════════════════════ */
.dc-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 36px;
    gap: 24px;
    flex-wrap: wrap;
}

.dc-head-left { flex: 1; min-width: 260px; }

.dc-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    border-radius: 100px;
    color: #7dd3fc;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 16px;
    width: fit-content;
}

.dc-badge .dc-dot {
    width: 7px; height: 7px;
    background: var(--dc-sky);
    border-radius: 50%;
    animation: dcPulse 2s ease-in-out infinite;
}

@keyframes dcPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.4); }
}

.dc-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    margin-bottom: 8px;
}

.dc-title span {
    background: linear-gradient(135deg, var(--dc-sky), #38bdf8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dc-desc {
    font-size: 0.92rem;
    color: var(--dc-text-sec);
    line-height: 1.6;
    max-width: 520px;
}

/* Stat pills row */
.dc-stats-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: flex-start;
}

.dc-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 600;
    white-space: nowrap;
}
.dc-stat-pill i { font-size: 0.88rem; }

.dc-stat-issued {
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.dc-stat-draft {
    background: rgba(245,158,11,0.10);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.dc-stat-archived {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}

/* ═══════════════════════════════
   ALERT
   ═══════════════════════════════ */
.dc-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: rgba(16,185,129,0.10);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: var(--dc-rad);
    color: #6ee7b7;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 28px;
    animation: dcSlide 0.4s ease;
}
.dc-alert i { font-size: 1.15rem; color: var(--dc-accent); flex-shrink: 0; }

@keyframes dcSlide {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════
   CARDS
   ═══════════════════════════════ */
.dc-card {
    background: var(--dc-card);
    border: 1px solid var(--dc-border);
    border-radius: var(--dc-rad-xl);
    backdrop-filter: blur(40px);
    padding: 32px;
    margin-bottom: 28px;
    transition: border-color 0.3s ease;
}
.dc-card:hover { border-color: rgba(255,255,255,0.12); }

.dc-card-hd {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
}

.dc-card-ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.dc-card-tt {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
}

.dc-card-st {
    font-size: 0.82rem;
    color: var(--dc-text-muted);
    margin: 0;
    line-height: 1.4;
}

/* ═══════════════════════════════
   FORMS
   ═══════════════════════════════ */
.dc-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.dc-label i { font-size: 0.82rem; color: var(--dc-text-muted); }

.dc-input,
.dc-select {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--dc-rad);
    font-size: 0.88rem;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    transition: all 0.25s ease;
    outline: none;
}
.dc-input::placeholder { color: #475569; }

.dc-input:focus,
.dc-select:focus {
    border-color: var(--dc-accent);
    box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    background: rgba(255,255,255,0.07);
}

.dc-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}
.dc-select option { background: #1e293b; color: #e2e8f0; }

/* ═══════════════════════════════
   FORM GRID
   ═══════════════════════════════ */
.dc-fg {
    display: grid;
    gap: 18px;
    align-items: end;
}
.dc-fg-4 { grid-template-columns: 1fr 1fr 1fr auto; }
.dc-fg-3 { grid-template-columns: 1fr auto auto; }

@media (max-width: 991px) {
    .dc-fg-4 { grid-template-columns: 1fr 1fr; }
    .dc-fg-3 { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .dc-fg-4 { grid-template-columns: 1fr; }
}

/* ═══════════════════════════════
   BUTTONS
   ═══════════════════════════════ */
.dc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--dc-rad);
    font-size: 0.88rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-decoration: none;
    white-space: nowrap;
}
.dc-btn i { transition: transform 0.2s ease; }

.dc-btn-sky {
    background: linear-gradient(135deg, var(--dc-sky), #0284c7);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(14,165,233,0.25);
}
.dc-btn-sky:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(14,165,233,0.35);
    color: #ffffff;
}
.dc-btn-sky:active { transform: translateY(0); }
.dc-btn-sky:hover i { transform: translateX(3px); }

.dc-btn-ghost {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: #e2e8f0;
}
.dc-btn-ghost:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    color: #ffffff;
}

.dc-btn-toggle {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.10);
    color: var(--dc-text-sec);
    padding: 12px 20px;
}
.dc-btn-toggle:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.18);
    color: #e2e8f0;
}
.dc-btn-toggle.active {
    background: rgba(245,158,11,0.10);
    border-color: rgba(245,158,11,0.25);
    color: #fcd34d;
}

/* Small action buttons */
.dc-btn-sm {
    padding: 6px 13px;
    font-size: 0.76rem;
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
    background: transparent;
}

.dc-btn-archive {
    background: rgba(100,116,139,0.12);
    border-color: rgba(100,116,139,0.25);
    color: #94a3b8;
}
.dc-btn-archive:hover { background: rgba(100,116,139,0.2); color: #cbd5e1; }

.dc-btn-qr {
    background: rgba(14,165,233,0.10);
    border-color: rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.dc-btn-qr:hover { background: rgba(14,165,233,0.18); color: #bae6fd; }

/* ═══════════════════════════════
   TABLE
   ═══════════════════════════════ */
.dc-table-wrap {
    overflow-x: auto;
    border-radius: var(--dc-rad-lg);
    border: 1px solid rgba(255,255,255,0.04);
}

.dc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    min-width: 800px;
}

.dc-table thead th {
    padding: 14px 16px;
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: var(--dc-text-sec);
    font-weight: 600;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    white-space: nowrap;
}

.dc-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    color: #e2e8f0;
    vertical-align: middle;
}

.dc-table tbody tr:last-child td { border-bottom: none; }
.dc-table tbody tr { transition: background 0.2s ease; }
.dc-table tbody tr:hover { background: rgba(255,255,255,0.03); }

.dc-table .dc-doc-num {
    font-weight: 700;
    font-size: 0.84rem;
    color: #f1f5f9;
    font-variant-numeric: tabular-nums;
}

.dc-table .dc-ctrl-num {
    font-size: 0.8rem;
    color: var(--dc-text-muted);
    font-variant-numeric: tabular-nums;
}

.dc-table .dc-resident {
    font-weight: 600;
    color: #e2e8f0;
}

.dc-table .dc-doc-type {
    color: var(--dc-text-sec);
}

.dc-table .dc-purpose {
    color: var(--dc-text-dim);
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dc-table .dc-date {
    font-size: 0.8rem;
    color: var(--dc-text-dim);
    white-space: nowrap;
}

.dc-table .dc-actions {
    display: flex;
    gap: 6px;
    white-space: nowrap;
}

/* Status badges */
.dc-st {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.73rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.dc-st-issued {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.dc-st-draft {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
}
.dc-st-archived {
    background: rgba(100,116,139,0.12);
    border: 1px solid rgba(100,116,139,0.25);
    color: #94a3b8;
}
.dc-st-default {
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}

/* Empty */
.dc-empty {
    text-align: center;
    padding: 48px 20px;
}
.dc-empty-ico {
    width: 60px; height: 60px;
    border-radius: 17px;
    background: rgba(100,116,139,0.08);
    border: 1px solid rgba(100,116,139,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--dc-text-dim);
    margin: 0 auto 14px;
}
.dc-empty-txt { font-size: 0.9rem; color: var(--dc-text-muted); margin: 0; }

/* ═══════════════════════════════
   ANIMATIONS
   ═══════════════════════════════ */
.dc-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.dc-reveal.dc-vis { opacity: 1; transform: translateY(0); }

.dc-d1 { transition-delay: 0.05s; }
.dc-d2 { transition-delay: 0.1s; }
.dc-d3 { transition-delay: 0.15s; }
.dc-d4 { transition-delay: 0.2s; }
.dc-d5 { transition-delay: 0.25s; }
.dc-d6 { transition-delay: 0.3s; }

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 1199.98px) {
    .dc-main { padding: 32px 36px; }
}
@media (max-width: 991.98px) {
    .dc-layout { flex-direction: column; }
    .dc-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--dc-border);
    }
    .dc-main { padding: 28px 24px; }
    .dc-head { flex-direction: column; gap: 16px; }
}
@media (max-width: 767.98px) {
    .dc-main { padding: 24px 16px; }
    .dc-card { padding: 24px 20px; }
    .dc-title { font-size: 1.4rem; }
    .dc-stats-row { flex-direction: column; }
    .dc-stat-pill { width: fit-content; }
}
@media (max-width: 480px) {
    .dc-main { padding: 20px 14px; }
    .dc-card { padding: 20px 16px; border-radius: 16px; }
}
</style>

<!-- ═══════════════════════════════════════
     PAGE
     ═══════════════════════════════════════ -->
<div class="dc-page">
    <div class="dc-orb o1"></div>
    <div class="dc-orb o2"></div>
    <div class="dc-orb o3"></div>

    <div class="dc-layout">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main -->
        <div class="dc-main">

            <!-- Header -->
            <div class="dc-head dc-reveal dc-d1">
                <div class="dc-head-left">
                    <div class="dc-badge">
                        <span class="dc-dot"></span>
                        Documents
                    </div>
                    <h1 class="dc-title">Document <span>Management</span></h1>
                    <p class="dc-desc">Generate barangay certificates and clearance documents with QR verification codes.</p>
                </div>
                <div class="dc-stats-row dc-reveal dc-d2">
                    <div class="dc-stat-pill dc-stat-issued">
                        <i class="bi bi-check-circle-fill"></i>
                        Issued: <?php echo e($stats['issued'] ?? 0); ?>
                    </div>
                    <div class="dc-stat-pill dc-stat-draft">
                        <i class="bi bi-pencil-square"></i>
                        Draft: <?php echo e($stats['draft'] ?? 0); ?>
                    </div>
                    <div class="dc-stat-pill dc-stat-archived">
                        <i class="bi bi-archive-fill"></i>
                        Archived: <?php echo e($stats['archived'] ?? 0); ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="dc-alert dc-reveal dc-d2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Quick Generate -->
            <div class="dc-card dc-reveal dc-d3">
                <div class="dc-card-hd">
                    <div class="dc-card-ico" style="background:rgba(14,165,233,0.12); color:#0ea5e9;">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <div>
                        <h5 class="dc-card-tt">Quick Generate</h5>
                        <p class="dc-card-st">Select a resident and document type to instantly generate a certificate.</p>
                    </div>
                </div>

                <form method="post">
                    <?php echo csrfField(); ?>
                    <div class="dc-fg dc-fg-4">
                        <div>
                            <label class="dc-label"><i class="bi bi-person"></i> Resident</label>
                            <select name="resident_id" class="dc-select" required>
                                <option value="">Select resident</option>
                                <?php foreach ($residents as $resident): ?>
                                    <option value="<?php echo (int) $resident['id']; ?>"><?php echo e($resident['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="dc-label"><i class="bi bi-file-earmark-text"></i> Document Type</label>
                            <select name="document_type" class="dc-select" required>
                                <option value="">Select type</option>
                                <option value="Barangay Clearance">Barangay Clearance</option>
                                <option value="Certificate of Residency">Certificate of Residency</option>
                                <option value="Certificate of Indigency">Certificate of Indigency</option>
                                <option value="Business Clearance">Business Clearance</option>
                                <option value="First Time Job Seeker">First Time Job Seeker</option>
                                <option value="Good Moral">Good Moral</option>
                                <option value="Solo Parent Certificate">Solo Parent Certificate</option>
                                <option value="Low Income Certificate">Low Income Certificate</option>
                                <option value="Certification">Certification</option>
                                <option value="Custom Certificate">Custom Certificate</option>
                            </select>
                        </div>
                        <div>
                            <label class="dc-label"><i class="bi bi-bullseye"></i> Purpose</label>
                            <input type="text" name="purpose" class="dc-input" placeholder="e.g. Employment, School">
                        </div>
                        <div>
                            <button type="submit" name="generate_document" class="dc-btn dc-btn-sky">
                                <i class="bi bi-file-earmark-plus"></i>
                                <span>Generate</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Search & Toggle -->
            <div class="dc-card dc-reveal dc-d4">
                <div class="dc-card-hd">
                    <div class="dc-card-ico" style="background:rgba(139,92,246,0.12); color:#8b5cf6;">
                        <i class="bi bi-search"></i>
                    </div>
                    <div>
                        <h5 class="dc-card-tt">Search &amp; Filter</h5>
                        <p class="dc-card-st">Find documents by number, resident name, or type.</p>
                    </div>
                </div>

                <form method="get">
                    <div class="dc-fg dc-fg-3">
                        <div>
                            <label class="dc-label"><i class="bi bi-search"></i> Search</label>
                            <input type="text" name="search" class="dc-input" placeholder="Search by number, resident, type..." value="<?php echo e($search); ?>">
                        </div>
                        <div>
                            <button type="submit" class="dc-btn dc-btn-ghost">
                                <i class="bi bi-search"></i>
                                <span>Search</span>
                            </button>
                        </div>
                        <div>
                            <a href="?<?php echo $archived ? '' : 'archived=1'; ?>" class="dc-btn dc-btn-toggle <?php echo $archived ? 'active' : ''; ?>">
                                <i class="bi bi-archive"></i>
                                <span><?php echo $archived ? 'Show Active' : 'Show Archived'; ?></span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Documents Table -->
            <div class="dc-card dc-reveal dc-d5">
                <div class="dc-card-hd">
                    <div class="dc-card-ico" style="background:rgba(16,185,129,0.12); color:#10b981;">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <h5 class="dc-card-tt">
                            <?php echo $archived ? 'Archived Documents' : 'Active Documents'; ?>
                        </h5>
                        <p class="dc-card-st"><?php echo count($documents); ?> document<?php echo count($documents) !== 1 ? 's' : ''; ?> found.</p>
                    </div>
                </div>

                <?php if (!empty($documents)): ?>
                    <div class="dc-table-wrap">
                        <table class="dc-table">
                            <thead>
                                <tr>
                                    <th>Document #</th>
                                    <th>Control #</th>
                                    <th>Resident</th>
                                    <th>Type</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $document): ?>
                                    <tr>
                                        <td><span class="dc-doc-num"><?php echo e($document['document_number']); ?></span></td>
                                        <td><span class="dc-ctrl-num"><?php echo e($document['control_number']); ?></span></td>
                                        <td><span class="dc-resident"><?php echo e($document['full_name'] ?? 'Unknown'); ?></span></td>
                                        <td><span class="dc-doc-type"><?php echo e($document['document_type']); ?></span></td>
                                        <td><span class="dc-purpose" title="<?php echo e($document['purpose'] ?? ''); ?>"><?php echo e($document['purpose'] ?? '-'); ?></span></td>
                                        <td>
                                            <?php
                                                $stClass = match($document['status'] ?? 'issued') {
                                                    'issued'   => 'dc-st-issued',
                                                    'draft'    => 'dc-st-draft',
                                                    'archived' => 'dc-st-archived',
                                                    default    => 'dc-st-default'
                                                };
                                                $stIcon = match($document['status'] ?? 'issued') {
                                                    'issued'   => 'bi-check-circle-fill',
                                                    'draft'    => 'bi-pencil-fill',
                                                    'archived' => 'bi-archive-fill',
                                                    default    => 'bi-circle'
                                                };
                                            ?>
                                            <span class="dc-st <?php echo $stClass; ?>">
                                                <i class="bi <?php echo $stIcon; ?>"></i>
                                                <?php echo e($document['status'] ?? 'issued'); ?>
                                            </span>
                                        </td>
                                        <td><span class="dc-date"><?php echo date('M d, Y', strtotime($document['created_at'])); ?></span></td>
                                        <td>
                                            <div class="dc-actions">
                                                <form method="post" class="d-inline" onsubmit="return confirm('Archive this document?')">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="document_id" value="<?php echo (int) $document['id']; ?>">
                                                    <button type="submit" name="archive_document" class="dc-btn-sm dc-btn-archive">
                                                        <i class="bi bi-archive"></i> Archive
                                                    </button>
                                                </form>
                                                <a href="<?php echo BASE_URL; ?>/includes/qr.php?type=document&id=<?php echo (int) $document['id']; ?>" target="_blank" class="dc-btn-sm dc-btn-qr">
                                                    <i class="bi bi-qr-code"></i> QR
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($documents)): ?>
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($paginator); ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="dc-empty">
                        <div class="dc-empty-ico"><i class="bi bi-inbox"></i></div>
                        <p class="dc-empty-txt">
                            <?php echo $archived
                                ? 'No archived documents found.'
                                : 'No documents found. Generate one above or adjust your search.'; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reveals = document.querySelectorAll('.dc-reveal');
    setTimeout(function() {
        reveals.forEach(function(el) {
            el.classList.add('dc-vis');
        });
    }, 60);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>