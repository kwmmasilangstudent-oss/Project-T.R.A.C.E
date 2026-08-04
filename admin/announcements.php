<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

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
    $stmt = $pdo->prepare('DELETE FROM announcements WHERE id = ?');
    $stmt->execute([$deleteId]);
    $pdo->prepare('DELETE FROM announcement_reads WHERE announcement_id = ?')->execute([$deleteId]);
    logAudit('delete_announcement', 'Deleted announcement ID: ' . $deleteId);
    $_SESSION['_flash_success'] = 'Announcement deleted.';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete_announcements'])) {
    requireCsrf();
    $ids = $_POST['announcement_ids'] ?? [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $idList = array_map('intval', $ids);
        $pdo->prepare('DELETE FROM announcements WHERE id IN (' . $placeholders . ')')->execute($idList);
        $pdo->prepare('DELETE FROM announcement_reads WHERE announcement_id IN (' . $placeholders . ')')->execute($idList);
        logAudit('bulk_delete_announcements', 'Deleted announcements IDs: ' . implode(',', $idList));
        $_SESSION['_flash_success'] = count($idList) . ' announcement(s) deleted.';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
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
    $where .= ' AND (title LIKE ? OR content LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($typeFilter) {
    $where .= ' AND type = ?';
    $params[] = $typeFilter;
}
if ($priorityFilter) {
    $where .= ' AND priority = ?';
    $params[] = $priorityFilter;
}
if ($pinnedFilter !== '') {
    $where .= ' AND is_pinned = ?';
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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3>Announcement Management</h3>
                    <p class="text-muted">Create and manage barangay announcements, news, events, and emergency alerts.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary">Total: <?php echo (int) ($stats['total'] ?? 0); ?></span>
                    <span class="badge bg-danger-subtle text-danger">Emergency: <?php echo (int) ($stats['emergency'] ?? 0); ?></span>
                    <span class="badge bg-info-subtle text-info">Events: <?php echo (int) ($stats['event'] ?? 0); ?></span>
                    <span class="badge bg-warning-subtle text-warning">Pinned: <?php echo (int) ($stats['pinned'] ?? 0); ?></span>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal">
                        <i class="bi bi-plus-lg"></i> New Announcement
                    </button>
                </div>
            </div>
            <?php if (!empty($success)) : ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>

            <div class="modal fade" id="createAnnouncementModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="post" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="modal-header">
                                <h5 class="modal-title">Create Announcement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-control" required autofocus>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Type</label>
                                        <select name="type" class="form-select">
                                            <?php foreach ($announcementTypes as $value => $label): ?>
                                                <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Priority</label>
                                        <select name="priority" class="form-select">
                                            <option value="normal">Normal</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Pinned</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_pinned" value="1" id="modalPinCheck">
                                            <label class="form-check-label" for="modalPinCheck">Pin to top</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Expires At</label>
                                        <input type="datetime-local" name="expires_at" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Audience</label>
                                        <select name="audience" class="form-select">
                                            <option value="all">All Residents</option>
                                            <option value="secretary">Secretary Only</option>
                                            <option value="admin">Admin Only</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Attachment</label>
                                        <input type="file" name="attachment" class="form-control">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Content</label>
                                        <textarea name="content" class="form-control" rows="4" required></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary">Publish Announcement</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search announcements..." value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($announcementTypes as $value => $label): ?>
                                <option value="<?php echo e($value); ?>" <?php echo $typeFilter === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="normal" <?php echo $priorityFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pinned</label>
                        <select name="pinned" class="form-select">
                            <option value="">All</option>
                            <option value="1" <?php echo $pinnedFilter === '1' ? 'selected' : ''; ?>>Pinned Only</option>
                            <option value="0" <?php echo $pinnedFilter === '0' ? 'selected' : ''; ?>>Not Pinned</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Announcements</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span id="bulkDeleteCount" class="text-muted small" style="display:none;"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn" style="display:none;">
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>
                <form method="post" id="bulkDeleteForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="bulk_delete_announcements" value="1">
                    <ul class="list-group list-group-flush" id="announcementList">
                        <?php foreach ($announcements as $announcement): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-start gap-2 flex-grow-1">
                                        <input type="checkbox" name="announcement_ids[]" value="<?php echo (int) $announcement['id']; ?>" class="form-check-input mt-1 announcement-checkbox" style="width:18px;height:18px;">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong><?php echo e($announcement['title']); ?></strong>
                                                <span class="badge bg-secondary-subtle text-secondary text-capitalize"><?php echo e($announcement['type'] ?? 'announcement'); ?></span>
                                                <span class="badge bg-<?php echo match($announcement['priority']) { 'urgent' => 'danger', 'high' => 'warning', default => 'primary' }; ?>-subtle text-<?php echo match($announcement['priority']) { 'urgent' => 'danger', 'high' => 'warning', default => 'primary' }; ?>"><?php echo e($announcement['priority'] ?? 'normal'); ?></span>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-0 text-muted"><?php echo e($announcement['content']); ?></p>
                                            <small class="text-muted">Reads: <?php echo (int) ($announcement['total_reads'] ?? 0); ?> | Unread: <?php echo (int) ($announcement['total_unreads'] ?? 0); ?>
                                                <?php if (!empty($announcement['is_pinned'])): ?>
                                                    <span class="badge bg-warning-subtle text-warning ms-2">Pinned</span>
                                                <?php endif; ?>
                                            </small>
                                            <?php if ($announcement['attachment_path']): ?>
                                                <br><a href="<?php echo asset($announcement['attachment_path']); ?>" target="_blank" class="small">View Attachment</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 ms-3">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Archive this announcement?')">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="archive_id" value="<?php echo (int) $announcement['id']; ?>">
                                            <button type="submit" name="archive_announcement" class="btn btn-sm btn-outline-secondary">Archive</button>
                                        </form>
                                        <a href="#" class="btn btn-sm btn-outline-danger" data-announcement-id="<?php echo (int) $announcement['id']; ?>" data-announcement-title="<?php echo e($announcement['title'] ?? 'Untitled'); ?>" id="deleteBtn<?php echo (int) $announcement['id']; ?>">Delete</a>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($announcements)): ?>
                            <li class="list-group-item text-center text-muted py-4">No announcements found.</li>
                        <?php endif; ?>
                    </ul>
                </form>
                <?php if (!empty($announcements)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
    
    <!-- Bulk Delete Confirmation Modal -->
    <div id="bulkDeleteOverlay" class="delete-toast-overlay">
        <div class="delete-toast-container">
            <div class="delete-toast-card glass-card">
                <div class="delete-toast-header">
                    <div class="delete-toast-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="delete-toast-title">Delete Announcements</h3>
                </div>
                <div class="delete-toast-message">
                    <p>Are you sure you want to delete <span id="bulkDeleteCountText"></span> announcement(s)? This action cannot be undone.</p>
                </div>
                <div class="delete-toast-buttons">
                    <button id="bulkDeleteCancel" class="btn btn-outline-secondary">Cancel</button>
                    <button id="bulkDeleteConfirm" class="btn btn-danger">Delete All</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Toast -->
    <div id="deleteToastOverlay" class="delete-toast-overlay">
        <div class="delete-toast-container">
            <div class="delete-toast-card glass-card">
                <div class="delete-toast-header">
                    <div class="delete-toast-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="delete-toast-title">Delete Announcement</h3>
                </div>
                <div class="delete-toast-message">
                    <p>Are you sure you want to delete <span id="deleteToastTitle"></span>? This action cannot be undone.</p>
                </div>
                <div class="delete-toast-buttons">
                    <button id="deleteToastCancel" class="btn btn-outline-secondary">Cancel</button>
                    <button id="deleteToastConfirm" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toastOverlay = document.getElementById('deleteToastOverlay');
            const toastCancel = document.getElementById('deleteToastCancel');
            const toastConfirm = document.getElementById('deleteToastConfirm');
            const toastTitle = document.getElementById('deleteToastTitle');

            const bulkDeleteOverlay = document.getElementById('bulkDeleteOverlay');
            const bulkDeleteCancel = document.getElementById('bulkDeleteCancel');
            const bulkDeleteConfirm = document.getElementById('bulkDeleteConfirm');
            const bulkDeleteCountText = document.getElementById('bulkDeleteCountText');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const bulkDeleteCount = document.getElementById('bulkDeleteCount');
            const announcementCheckboxes = document.querySelectorAll('.announcement-checkbox');
            const announcementList = document.getElementById('announcementList');

            let pendingDeleteId = null;
            let pendingBulkDeleteIds = [];

            // Select all checkbox functionality
            function updateBulkDeleteUI() {
                const checkedBoxes = document.querySelectorAll('.announcement-checkbox:checked');
                const count = checkedBoxes.length;
                if (count > 0) {
                    bulkDeleteBtn.style.display = 'inline-flex';
                    bulkDeleteCount.style.display = 'inline';
                    bulkDeleteCount.textContent = count + ' selected';
                    pendingBulkDeleteIds = Array.from(checkedBoxes).map(function(cb) { return cb.value; });
                } else {
                    bulkDeleteBtn.style.display = 'none';
                    bulkDeleteCount.style.display = 'none';
                    pendingBulkDeleteIds = [];
                }
            }

            // Add event listeners to all checkboxes
            announcementCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateBulkDeleteUI);
            });

            // Bulk delete button click
            bulkDeleteBtn.addEventListener('click', function() {
                const count = pendingBulkDeleteIds.length;
                if (count === 0) return;
                bulkDeleteCountText.textContent = count;
                bulkDeleteOverlay.classList.add('active');
            });

            // Close bulk delete modal when clicking cancel
            bulkDeleteCancel.addEventListener('click', function() {
                bulkDeleteOverlay.classList.remove('active');
                pendingBulkDeleteIds = [];
            });

            // Close bulk delete modal when clicking overlay
            bulkDeleteOverlay.addEventListener('click', function(e) {
                if (e.target === bulkDeleteOverlay) {
                    bulkDeleteOverlay.classList.remove('active');
                    pendingBulkDeleteIds = [];
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (bulkDeleteOverlay.classList.contains('active')) {
                        bulkDeleteOverlay.classList.remove('active');
                        pendingBulkDeleteIds = [];
                    }
                    if (toastOverlay.classList.contains('active')) {
                        toastOverlay.classList.remove('active');
                        pendingDeleteId = null;
                    }
                }
            });

            // Handle bulk delete confirm
            bulkDeleteConfirm.addEventListener('click', function() {
                if (pendingBulkDeleteIds.length > 0) {
                    var form = document.getElementById('bulkDeleteForm');
                    form.submit();
                }
                bulkDeleteOverlay.classList.remove('active');
                pendingBulkDeleteIds = [];
            });

            // Open delete toast when delete button is clicked
            document.querySelectorAll('[data-announcement-id]').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const announcementId = this.getAttribute('data-announcement-id');
                    const announcementTitle = this.getAttribute('data-announcement-title') || 'this announcement';

                    pendingDeleteId = announcementId;
                    toastTitle.textContent = announcementTitle;
                    toastOverlay.classList.add('active');
                });
            });

            // Close toast when clicking cancel or overlay
            toastCancel.addEventListener('click', function() {
                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
            });

            toastOverlay.addEventListener('click', function(e) {
                if (e.target === toastOverlay) {
                    toastOverlay.classList.remove('active');
                    pendingDeleteId = null;
                }
            });

            // Handle confirm button click
            toastConfirm.addEventListener('click', function() {
                if (pendingDeleteId) {
                    window.location.href = '?delete=' + pendingDeleteId;
                }

                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
            });
        });
    </script>
