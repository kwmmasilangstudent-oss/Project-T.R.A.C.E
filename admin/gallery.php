<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (isset($_POST['create_gallery_item'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imagePath = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'gallery_' . uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = 'assets/uploads/' . $fileName;
            }
        }

        if (!$title) {
            $_SESSION['_flash_error'] = 'Title is required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        $pdo->prepare('INSERT INTO gallery (title, image_path, description) VALUES (?, ?, ?)')->execute([$title, $imagePath, $description]);
        logAudit('create_gallery_item', 'Created gallery item: ' . $title);
        $_SESSION['_flash_success'] = 'Gallery item added successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['bulk_delete_gallery'])) {
        $ids = $_POST['gallery_ids'] ?? [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $idList = array_map('intval', $ids);
            $pdo->prepare('DELETE FROM gallery WHERE id IN (' . $placeholders . ')')->execute($idList);
            logAudit('bulk_delete_gallery', 'Deleted gallery items IDs: ' . implode(',', $idList));
            $_SESSION['_flash_success'] = count($idList) . ' gallery item(s) deleted.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM gallery WHERE id = ?')->execute([$deleteId]);
    logAudit('delete_gallery_item', 'Deleted gallery item ID: ' . $deleteId);
    $_SESSION['_flash_success'] = 'Gallery item deleted.';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$gallery = $pdo->query('SELECT * FROM gallery ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="col-md-9 py-4 px-3 px-md-4">
            <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Gallery Management</h3>
                    <p class="text-muted-glass mb-0">Manage images displayed on the landing gallery page.</p>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createGalleryModal">
                    <i class="bi bi-plus-lg"></i> Add Image
                </button>
            </div>

            <?php if (!empty($success)) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter:invert(1) grayscale(100%) brightness(200%)"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo e($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter:invert(1) grayscale(100%) brightness(200%)"></button>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Gallery Images</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span id="bulkDeleteCount" class="text-muted small" style="display:none;"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn" style="display:none;">
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>
                <form method="post" id="bulkDeleteForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="bulk_delete_gallery" value="1">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                    <th>Preview</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Added</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gallery as $item): ?>
                                    <tr>
                                        <td><input type="checkbox" name="gallery_ids[]" value="<?php echo (int) $item['id']; ?>" class="form-check-input gallery-checkbox"></td>
                                        <td>
                                            <?php if (!empty($item['image_path'])): ?>
                                                <img src="<?php echo asset($item['image_path']); ?>" alt="<?php echo e($item['title']); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                                            <?php else: ?>
                                                <div class="rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;background:var(--accent);color:var(--bg);font-size:1.2rem;font-weight:700;">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo e($item['title']); ?></strong></td>
                                        <td><?php echo e($item['description'] ?? ''); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                        <td>
                                            <div class="table-actions justify-content-end">
                                                <a href="<?php echo BASE_URL; ?>/admin/gallery.php?delete=<?php echo (int) $item['id']; ?>" class="btn btn-sm btn-outline-danger" data-gallery-id="<?php echo (int) $item['id']; ?>" data-gallery-title="<?php echo e($item['title']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($gallery)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No gallery items found. Add one below.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Gallery Modal -->
    <div class="modal fade" id="createGalleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="create_gallery_item" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-images me-2"></i>Add Gallery Image</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required autofocus>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter a description for this image..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> Upload Image
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div id="bulkDeleteOverlay" class="delete-toast-overlay">
        <div class="delete-toast-container">
            <div class="delete-toast-card glass-card">
                <div class="delete-toast-header">
                    <div class="delete-toast-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="delete-toast-title">Delete Gallery Items</h3>
                </div>
                <div class="delete-toast-message">
                    <p>Are you sure you want to delete <span id="bulkDeleteCountText"></span> gallery item(s)? This action cannot be undone.</p>
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
                    <h3 class="delete-toast-title">Delete Gallery Item</h3>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.gallery-checkbox');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const bulkDeleteCount = document.getElementById('bulkDeleteCount');
            const bulkDeleteForm = document.getElementById('bulkDeleteForm');
            const bulkDeleteOverlay = document.getElementById('bulkDeleteOverlay');
            const bulkDeleteCancel = document.getElementById('bulkDeleteCancel');
            const bulkDeleteConfirm = document.getElementById('bulkDeleteConfirm');
            const bulkDeleteCountText = document.getElementById('bulkDeleteCountText');

            function updateBulkDeleteButton() {
                const checked = document.querySelectorAll('.gallery-checkbox:checked');
                if (checked.length > 0) {
                    bulkDeleteBtn.style.display = 'inline-flex';
                    bulkDeleteCount.style.display = 'inline';
                    bulkDeleteCount.textContent = checked.length + ' selected';
                } else {
                    bulkDeleteBtn.style.display = 'none';
                    bulkDeleteCount.style.display = 'none';
                }
            }

            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateBulkDeleteButton);
            });

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(function(cb) {
                        cb.checked = selectAll.checked;
                    });
                    updateBulkDeleteButton();
                });
            }

            bulkDeleteBtn.addEventListener('click', function() {
                const checked = document.querySelectorAll('.gallery-checkbox:checked');
                if (checked.length > 0) {
                    bulkDeleteCountText.textContent = checked.length;
                    bulkDeleteOverlay.classList.add('active');
                }
            });

            bulkDeleteCancel.addEventListener('click', function() {
                bulkDeleteOverlay.classList.remove('active');
            });

            bulkDeleteOverlay.addEventListener('click', function(e) {
                if (e.target === bulkDeleteOverlay) {
                    bulkDeleteOverlay.classList.remove('active');
                }
            });

            bulkDeleteConfirm.addEventListener('click', function() {
                bulkDeleteForm.submit();
                bulkDeleteOverlay.classList.remove('active');
            });

            const deleteToastOverlay = document.getElementById('deleteToastOverlay');
            const deleteToastCancel = document.getElementById('deleteToastCancel');
            const deleteToastConfirm = document.getElementById('deleteToastConfirm');
            const deleteToastTitle = document.getElementById('deleteToastTitle');
            let pendingDeleteHref = null;

            document.querySelectorAll('[data-gallery-id]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    pendingDeleteHref = this.getAttribute('href');
                    deleteToastTitle.textContent = this.getAttribute('data-gallery-title') || 'this item';
                    deleteToastOverlay.classList.add('active');
                });
            });

            deleteToastCancel.addEventListener('click', function() {
                deleteToastOverlay.classList.remove('active');
                pendingDeleteHref = null;
            });

            deleteToastOverlay.addEventListener('click', function(e) {
                if (e.target === deleteToastOverlay) {
                    deleteToastOverlay.classList.remove('active');
                    pendingDeleteHref = null;
                }
            });

            deleteToastConfirm.addEventListener('click', function() {
                if (pendingDeleteHref) {
                    window.location.href = pendingDeleteHref;
                }
                deleteToastOverlay.classList.remove('active');
                pendingDeleteHref = null;
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (deleteToastOverlay.classList.contains('active')) {
                        deleteToastOverlay.classList.remove('active');
                        pendingDeleteHref = null;
                    }
                    if (bulkDeleteOverlay.classList.contains('active')) {
                        bulkDeleteOverlay.classList.remove('active');
                    }
                }
            });
        });
    </script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
