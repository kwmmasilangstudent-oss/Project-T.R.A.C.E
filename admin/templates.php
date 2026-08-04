<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin', 'secretary']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $documentType = trim($_POST['document_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $headerContent = trim($_POST['header_content'] ?? '');
        $bodyContent = trim($_POST['body_content'] ?? '');
        $footerContent = trim($_POST['footer_content'] ?? '');
        $watermarkText = trim($_POST['watermark_text'] ?? '');
        $signatureLine1 = trim($_POST['signature_line_1'] ?? '');
        $signatureLine2 = trim($_POST['signature_line_2'] ?? '');
        $backgroundPath = null;

        if (isset($_FILES['background']) && $_FILES['background']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'template_' . uniqid() . '_' . basename($_FILES['background']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['background']['tmp_name'], $targetPath)) {
                $backgroundPath = 'assets/uploads/' . $fileName;
            }
        }

        if (!$name || !$documentType) {
            $_SESSION['_flash_error'] = 'Template name and document type are required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO application_templates (name, document_type, description, header_content, body_content, footer_content, background_path, watermark_text, signature_line_1, signature_line_2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $documentType, $description, $headerContent, $bodyContent, $footerContent, $backgroundPath, $watermarkText, $signatureLine1, $signatureLine2]);
            logAudit('create_template', 'Created template: ' . $name);
            $_SESSION['_flash_success'] = 'Template saved successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'update') {
        $templateId = (int) ($_POST['template_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $documentType = trim($_POST['document_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $headerContent = trim($_POST['header_content'] ?? '');
        $bodyContent = trim($_POST['body_content'] ?? '');
        $footerContent = trim($_POST['footer_content'] ?? '');
        $watermarkText = trim($_POST['watermark_text'] ?? '');
        $signatureLine1 = trim($_POST['signature_line_1'] ?? '');
        $signatureLine2 = trim($_POST['signature_line_2'] ?? '');
        $backgroundPath = null;

        if (isset($_FILES['background']) && $_FILES['background']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'template_' . uniqid() . '_' . basename($_FILES['background']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['background']['tmp_name'], $targetPath)) {
                $backgroundPath = 'assets/uploads/' . $fileName;
            }
        }

        if ($templateId <= 0 || !$name || !$documentType) {
            $_SESSION['_flash_error'] = 'Invalid template data.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            if ($backgroundPath) {
                $stmt = $pdo->prepare('UPDATE application_templates SET name = ?, document_type = ?, description = ?, header_content = ?, body_content = ?, footer_content = ?, background_path = ?, watermark_text = ?, signature_line_1 = ?, signature_line_2 = ? WHERE id = ?');
                $stmt->execute([$name, $documentType, $description, $headerContent, $bodyContent, $footerContent, $backgroundPath, $watermarkText, $signatureLine1, $signatureLine2, $templateId]);
            } else {
                $stmt = $pdo->prepare('UPDATE application_templates SET name = ?, document_type = ?, description = ?, header_content = ?, body_content = ?, footer_content = ?, watermark_text = ?, signature_line_1 = ?, signature_line_2 = ? WHERE id = ?');
                $stmt->execute([$name, $documentType, $description, $headerContent, $bodyContent, $footerContent, $watermarkText, $signatureLine1, $signatureLine2, $templateId]);
            }
            logAudit('update_template', 'Updated template ID: ' . $templateId);
            $_SESSION['_flash_success'] = 'Template updated successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete') {
        $templateId = (int) ($_POST['template_id'] ?? 0);

        if ($templateId <= 0) {
            $_SESSION['_flash_error'] = 'Invalid template.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('DELETE FROM application_templates WHERE id = ?');
            $stmt->execute([$templateId]);
            logAudit('delete_template', 'Deleted template ID: ' . $templateId);
            $_SESSION['_flash_success'] = 'Template deleted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['document_type'] ?? '');

$where = '';
$params = [];

if ($search) {
    $where .= ' AND (name LIKE ? OR description LIKE ? OR document_type LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($typeFilter) {
    $where .= ' AND document_type = ?';
    $params[] = $typeFilter;
}

$paginator = paginate(
    'SELECT COUNT(*) FROM application_templates WHERE 1=1' . $where,
    $params,
    'SELECT * FROM application_templates WHERE 1=1' . $where . ' ORDER BY created_at DESC',
    $params
);
$templates = $paginator['data'];

$documentTypes = $pdo->query('SELECT DISTINCT document_type FROM application_templates ORDER BY document_type')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <div class="col-md-9 py-4 px-3 px-md-4">
            <!-- Page Header -->
            <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Document Templates</h3>
                    <p class="text-muted-glass mb-0">Create reusable document templates for barangay certificates and clearances.</p>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                    <i class="bi bi-plus-lg"></i> New Template
                </button>
            </div>

            <!-- Stats -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="stat-chip stat-total">
                    <span class="stat-dot"></span>
                    Total: <?php echo count($templates); ?>
                </span>
                <span class="stat-chip stat-admins">
                    <span class="stat-dot"></span>
                    Types: <?php echo count($documentTypes); ?>
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
                               placeholder="Search templates..."
                               value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Document Type</label>
                        <select name="document_type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($documentTypes as $type): ?>
                                <option value="<?php echo e($type['document_type']); ?>"
                                    <?php echo $typeFilter === $type['document_type'] ? 'selected' : ''; ?>>
                                    <?php echo e($type['document_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Templates Table -->
            <div class="glass-card p-3 p-md-4">
                <h5 class="mb-3" style="font-family:var(--font-display);font-weight:700;">All Templates</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Document Type</th>
                                <th>Description</th>
                                <th>Background</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><strong><?php echo e($template['name']); ?></strong></td>
                                    <td><?php echo e($template['document_type']); ?></td>
                                    <td style="max-width:200px;"><?php echo e($template['description'] ?? '-'); ?></td>
                                    <td>
                                        <?php if (!empty($template['background_path'])): ?>
                                            <img src="<?php echo asset($template['background_path']); ?>"
                                                 alt="Background"
                                                 style="height:40px;width:40px;object-fit:cover;border-radius:6px;
                                                        border:1px solid var(--surface);">
                                        <?php else: ?>
                                            <span style="color:var(--text-low);">Default</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($template['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions justify-content-end">
                                            <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal<?php echo (int) $template['id']; ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    data-template-id="<?php echo (int) $template['id']; ?>"
                                                    data-template-name="<?php echo e($template['name']); ?>">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($templates)) : ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-file-earmark-richtext"
                                           style="font-size:2rem;color:var(--text-low);display:block;margin-bottom:0.5rem;"></i>
                                        <span style="color:var(--text-low);">No templates found matching your criteria.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($templates)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!--  Create Template Modal                                              -->
<!-- ================================================================== -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>Create New Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" class="form-control"
                                   placeholder="e.g. Standard Clearance" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Document Type</label>
                            <input type="text" name="document_type" class="form-control"
                                   placeholder="Select or type a type" required list="typeListCreate">
                            <datalist id="typeListCreate">
                                <option value="Barangay Clearance">
                                <option value="Certificate of Residency">
                                <option value="Certificate of Indigency">
                                <option value="Business Clearance">
                                <option value="First Time Job Seeker">
                                <option value="Good Moral">
                                <option value="Solo Parent Certificate">
                                <option value="Low Income Certificate">
                                <option value="Certification">
                            </datalist>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control"
                                   placeholder="Brief description of this template">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Header Content</label>
                            <textarea name="header_content" class="form-control" rows="2"
                                      placeholder="Header HTML/text for document"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Body Content</label>
                            <textarea name="body_content" class="form-control" rows="3"
                                      placeholder="Body HTML/text for document"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Footer Content</label>
                            <textarea name="footer_content" class="form-control" rows="2"
                                      placeholder="Footer HTML/text for document"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Watermark Text</label>
                            <input type="text" name="watermark_text" class="form-control"
                                   placeholder="e.g. OFFICIAL COPY">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Signature Line 1</label>
                            <input type="text" name="signature_line_1" class="form-control"
                                   placeholder="e.g. Barangay Captain">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Signature Line 2</label>
                            <input type="text" name="signature_line_2" class="form-control"
                                   placeholder="e.g. Secretary">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Background Image</label>
                            <input type="file" name="background" class="form-control" accept="image/*">
                            <small class="text-muted">Optional. Used as the document background.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!--  Edit Template Modals                                               -->
<!-- ================================================================== -->
<?php foreach ($templates as $template): ?>
    <div class="modal fade" id="editModal<?php echo (int) $template['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Template</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="template_id" value="<?php echo (int) $template['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="name" class="form-control"
                                       value="<?php echo e($template['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Document Type</label>
                                <input type="text" name="document_type" class="form-control"
                                       value="<?php echo e($template['document_type']); ?>" required
                                       list="typeListEdit<?php echo (int) $template['id']; ?>">
                                <datalist id="typeListEdit<?php echo (int) $template['id']; ?>">
                                    <option value="Barangay Clearance">
                                    <option value="Certificate of Residency">
                                    <option value="Certificate of Indigency">
                                    <option value="Business Clearance">
                                    <option value="First Time Job Seeker">
                                    <option value="Good Moral">
                                    <option value="Solo Parent Certificate">
                                    <option value="Low Income Certificate">
                                    <option value="Certification">
                                </datalist>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control"
                                       value="<?php echo e($template['description'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Header Content</label>
                                <textarea name="header_content" class="form-control" rows="2"><?php echo e($template['header_content'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Body Content</label>
                                <textarea name="body_content" class="form-control" rows="3"><?php echo e($template['body_content'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Footer Content</label>
                                <textarea name="footer_content" class="form-control" rows="2"><?php echo e($template['footer_content'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Watermark Text</label>
                                <input type="text" name="watermark_text" class="form-control"
                                       value="<?php echo e($template['watermark_text'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Signature Line 1</label>
                                <input type="text" name="signature_line_1" class="form-control"
                                       value="<?php echo e($template['signature_line_1'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Signature Line 2</label>
                                <input type="text" name="signature_line_2" class="form-control"
                                       value="<?php echo e($template['signature_line_2'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Background Image</label>
                                <?php if (!empty($template['background_path'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo asset($template['background_path']); ?>"
                                             alt="Current background"
                                             style="height:60px;width:60px;object-fit:cover;border-radius:8px;
                                                    border:1px solid var(--surface);padding:4px;background:var(--surface);">
                                        <small class="text-muted d-block mt-1">Current background</small>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="background" class="form-control" accept="image/*">
                                <small class="text-muted">Leave blank to keep current background.</small>
                            </div>
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
                    <h3 class="delete-toast-title">Delete Template</h3>
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
            
            document.querySelectorAll('[data-template-id]').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    pendingDeleteId = this.getAttribute('data-template-id');
                    toastName.textContent = this.getAttribute('data-template-name');
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
                    idInput.name = 'template_id';
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