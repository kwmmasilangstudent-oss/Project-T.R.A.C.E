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
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $photoPath = null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'official_' . uniqid() . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $photoPath = 'assets/uploads/' . $fileName;
            }
        }

        if (!$fullName || !$position) {
            $_SESSION['_flash_error'] = 'Full name and position are required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO officials (full_name, position, contact_number, photo_path) VALUES (?, ?, ?, ?)');
            $stmt->execute([$fullName, $position, $contactNumber, $photoPath]);
            logAudit('create_official', 'Created official: ' . $fullName . ' (' . $position . ')');
            $_SESSION['_flash_success'] = 'Official added successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'update') {
        $officialId = (int) ($_POST['official_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');

        if ($officialId <= 0) {
            $_SESSION['_flash_error'] = 'Invalid official.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (!$fullName || !$position) {
            $_SESSION['_flash_error'] = 'Full name and position are required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $photoPath = null;

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = 'official_' . uniqid() . '_' . basename($_FILES['photo']['name']);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $photoPath = 'assets/uploads/' . $fileName;
                }
            }

            if ($photoPath) {
                $stmt = $pdo->prepare('UPDATE officials SET full_name = ?, position = ?, contact_number = ?, photo_path = ? WHERE id = ?');
                $stmt->execute([$fullName, $position, $contactNumber, $photoPath, $officialId]);
            } else {
                $stmt = $pdo->prepare('UPDATE officials SET full_name = ?, position = ?, contact_number = ? WHERE id = ?');
                $stmt->execute([$fullName, $position, $contactNumber, $officialId]);
            }
            logAudit('update_official', 'Updated official ID: ' . $officialId);
            $_SESSION['_flash_success'] = 'Official updated successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete') {
        $officialId = (int) ($_POST['official_id'] ?? 0);

        if ($officialId <= 0) {
            $_SESSION['_flash_error'] = 'Invalid official.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('DELETE FROM officials WHERE id = ?');
            $stmt->execute([$officialId]);
            logAudit('delete_official', 'Deleted official ID: ' . $officialId);
            $_SESSION['_flash_success'] = 'Official deleted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$positionFilter = trim($_GET['position'] ?? '');

$query = 'SELECT * FROM officials WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (full_name LIKE ? OR position LIKE ? OR contact_number LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($positionFilter) {
    $query .= ' AND position = ?';
    $params[] = $positionFilter;
}
$query .= ' ORDER BY full_name ASC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$officials = $stmt->fetchAll();

$positions = $pdo->query('SELECT DISTINCT position FROM officials ORDER BY position')->fetchAll();

$statsQuery = 'SELECT
    COUNT(*) as total,
    COUNT(DISTINCT position) as unique_positions
    FROM officials';
$stats = $pdo->query($statsQuery)->fetch();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
             <!-- Sidebar -->
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 py-4 px-3 px-md-4">
            <!-- Page Header -->
            <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Officials Management</h3>
                    <p class="text-muted-glass mb-0">Maintain barangay official profiles, positions, photos, and contact details.</p>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#createOfficialModal">
                    <i class="bi bi-plus-lg"></i> Add Official
                </button>
            </div>

            <!-- Stats -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="stat-chip stat-total">
                    <span class="stat-dot"></span>
                    Total: <?php echo (int) ($stats['total'] ?? 0); ?>
                </span>
                <span class="stat-chip stat-admins">
                    <span class="stat-dot"></span>
                    Positions: <?php echo (int) ($stats['unique_positions'] ?? 0); ?>
                </span>
            </div>

            <!-- Alerts -->
            <?php if (!empty($success)) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
                            style="filter:invert(1) grayscale(100%) brightness(200%)"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo e($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
                            style="filter:invert(1) grayscale(100%) brightness(200%)"></button>
                </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <div class="glass-card p-3 p-md-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Search name, position, or contact..."
                               value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Position</label>
                        <select name="position" class="form-select">
                            <option value="">All Positions</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo e($pos['position']); ?>"
                                    <?php echo $positionFilter === $pos['position'] ? 'selected' : ''; ?>>
                                    <?php echo e($pos['position']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Officials Table -->
            <div class="glass-card p-3 p-md-4">
                <h5 class="mb-3" style="font-family:var(--font-display);font-weight:700;">Officials Directory</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Contact</th>
                                <th>Added</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($officials as $official): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($official['photo_path'])): ?>
                                            <img src="<?php echo asset($official['photo_path']); ?>"
                                                 alt="<?php echo e($official['full_name']); ?>"
                                                 style="width:50px;height:50px;object-fit:cover;border-radius:50%;">
                                        <?php else: ?>
                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                 style="width:50px;height:50px;font-size:1.2rem;font-weight:700;
                                                        background:var(--accent);color:var(--bg);">
                                                <?php echo e(strtoupper(mb_substr($official['full_name'], 0, 1))); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo e($official['full_name']); ?></strong></td>
                                    <td><?php echo e($official['position']); ?></td>
                                    <td><?php echo e($official['contact_number'] ?? '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($official['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions justify-content-end">
                                            <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal<?php echo (int) $official['id']; ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    data-official-id="<?php echo (int) $official['id']; ?>"
                                                    data-official-name="<?php echo e($official['full_name']); ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Official Modal -->
    <div class="modal fade" id="createOfficialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Create Official</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" required list="positionListCreate">
                            <datalist id="positionListCreate">
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo e($pos['position']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Create Official
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Official Modals -->
    <?php foreach ($officials as $official): ?>
        <div class="modal fade" id="editModal<?php echo (int) $official['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Official</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="official_id" value="<?php echo (int) $official['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?php echo e($official['full_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" name="position" class="form-control"
                                       value="<?php echo e($official['position']); ?>" required
                                       list="positionListEdit<?php echo (int) $official['id']; ?>">
                                <datalist id="positionListEdit<?php echo (int) $official['id']; ?>">
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo e($pos['position']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                       value="<?php echo e($official['contact_number']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Photo</label>
                                <?php if (!empty($official['photo_path'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo asset($official['photo_path']); ?>"
                                             alt="Current photo"
                                             style="height:80px;width:80px;object-fit:cover;border-radius:50%;">
                                        <small class="text-muted d-block mt-1">Current photo</small>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                <small class="text-muted">Leave blank to keep current photo.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Delete Confirmation Toast -->
    <div id="deleteToastOverlay" class="delete-toast-overlay">
        <div class="delete-toast-container">
            <div class="delete-toast-card glass-card">
                <div class="delete-toast-header">
                    <div class="delete-toast-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="delete-toast-title">Delete Official</h3>
                </div>
                <div class="delete-toast-message">
                    <p>Are you sure you want to delete <span id="deleteToastName"></span>? This action cannot be undone.</p>
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
            const toastOverlay = document.getElementById('deleteToastOverlay');
            const toastCancel = document.getElementById('deleteToastCancel');
            const toastConfirm = document.getElementById('deleteToastConfirm');
            const toastName = document.getElementById('deleteToastName');
            
            let pendingDeleteId = null;
            
            document.querySelectorAll('[data-official-id]').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    pendingDeleteId = this.getAttribute('data-official-id');
                    toastName.textContent = this.getAttribute('data-official-name');
                    toastOverlay.classList.add('active');
                });
            });
            
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
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && toastOverlay.classList.contains('active')) {
                    toastOverlay.classList.remove('active');
                    pendingDeleteId = null;
                }
            });
            
            toastConfirm.addEventListener('click', function() {
                if (pendingDeleteId) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    var actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete';
                    
                    var idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'official_id';
                    idInput.value = pendingDeleteId;
                    
                form.appendChild(actionInput);
                form.appendChild(idInput);
                
                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_csrf_token';
                csrfInput.value = '<?php echo e($_SESSION["_csrf_token"] ?? ""); ?>';
                form.appendChild(csrfInput);
                
                document.body.appendChild(form);
                form.submit();
                }
                
                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
            });
        });
    </script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>