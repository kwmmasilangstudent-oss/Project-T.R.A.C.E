<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

function fmtBudgetShort($amount) {
    if ($amount === null || $amount == 0) return null;
    if ($amount >= 1000000) {
        $val = $amount / 1000000;
        return ($val == round($val) ? number_format($val, 0) : number_format($val, 1)) . 'M';
    }
    if ($amount >= 1000) {
        $val = $amount / 1000;
        return ($val == round($val) ? number_format($val, 0) : number_format($val, 1)) . 'K';
    }
    return number_format($amount, 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $progressPercent = (int) ($_POST['progress_percent'] ?? 0);
        $budgetAmount = (float) ($_POST['budget_amount'] ?? 0);
        $budgetSource = trim($_POST['budget_source'] ?? '');

        if (!$title) {
            $_SESSION['_flash_error'] = 'Project title is required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO projects (title, description, objectives, category, location, status, start_date, end_date, progress_percent, approved_by, approved_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$title, $description, $objectives, $category, $location, 'ongoing', $startDate ?: null, $endDate ?: null, $progressPercent, $_SESSION['user_id']]);
            $projectId = (int) $pdo->lastInsertId();

            if ($budgetAmount > 0) {
                $pdo->prepare('INSERT INTO project_budget (project_id, amount, source, type, description) VALUES (?, ?, ?, ?, ?)')->execute([$projectId, $budgetAmount, $budgetSource ?: 'Admin Allocation', 'allocation', 'Initial budget']);
            }

            logAudit('create_project', 'Created project: ' . $title);
            $_SESSION['_flash_success'] = 'Project created and approved successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'update') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $progressPercent = (int) ($_POST['progress_percent'] ?? 0);

        if ($projectId <= 0 || !$title) {
            $_SESSION['_flash_error'] = 'Invalid project data.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('UPDATE projects SET title = ?, description = ?, objectives = ?, category = ?, location = ?, status = ?, start_date = ?, end_date = ?, progress_percent = ? WHERE id = ?');
            $stmt->execute([$title, $description, $objectives, $category, $location, $status, $startDate ?: null, $endDate ?: null, $progressPercent, $projectId]);
            logAudit('update_project', 'Updated project ID: ' . $projectId);
            $_SESSION['_flash_success'] = 'Project updated successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete') {
        $projectId = (int) ($_POST['project_id'] ?? 0);

        if ($projectId <= 0) {
            $_SESSION['_flash_error'] = 'Invalid project.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $pdo->prepare('DELETE FROM expenses WHERE project_id = ?')->execute([$projectId]);
            $pdo->prepare('DELETE FROM project_budget WHERE project_id = ?')->execute([$projectId]);
            $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);
            logAudit('delete_project', 'Deleted project ID: ' . $projectId);
            $_SESSION['_flash_success'] = 'Project deleted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'approve_project' || $action === 'complete_project' || $action === 'reject_project') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $updates = ['approved_by' => $_SESSION['user_id'], 'approved_at' => date('Y-m-d H:i:s')];

        if ($action === 'approve_project') {
            $updates['status'] = 'ongoing';
        } elseif ($action === 'complete_project') {
            $updates['status'] = 'completed';
            $updates['progress_percent'] = 100;
        } elseif ($action === 'reject_project') {
            $updates['status'] = 'rejected';
        }

        $setParts = [];
        $params = [];
        foreach ($updates as $column => $value) {
            $setParts[] = $column . ' = ?';
            $params[] = $value;
        }
        $params[] = $projectId;

        $stmt = $pdo->prepare('UPDATE projects SET ' . implode(', ', $setParts) . ' WHERE id = ?');
        $stmt->execute($params);
        logAudit($action, 'Project ID: ' . $projectId . ' status changed to ' . ($updates['status'] ?? 'updated'));
        $_SESSION['_flash_success'] = 'Project updated.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = '';
$params = [];

if ($search) {
    $where .= ' AND (p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($categoryFilter) {
    $where .= ' AND p.category = ?';
    $params[] = $categoryFilter;
}
if ($statusFilter) {
    $where .= ' AND p.status = ?';
    $params[] = $statusFilter;
}

$baseSelect = 'SELECT p.*, u.full_name as approved_by_name,
    (SELECT SUM(amount) FROM project_budget WHERE project_id = p.id) as total_budget,
    (SELECT SUM(amount) FROM expenses WHERE project_id = p.id) as total_expenses
    FROM projects p LEFT JOIN users u ON u.id = p.approved_by WHERE 1=1';
$countBase = 'SELECT COUNT(*) FROM projects p LEFT JOIN users u ON u.id = p.approved_by WHERE 1=1';
$paginator = paginate($countBase . $where, $params, $baseSelect . $where . ' ORDER BY p.created_at DESC', $params);
$projects = $paginator['data'];

$projectStats = $pdo->query('SELECT
    COUNT(*) as total,
    SUM(status = "planned") as planned,
    SUM(status = "ongoing") as ongoing,
    SUM(status = "completed") as completed,
    SUM(status = "rejected") as rejected
    FROM projects')->fetch();

$categories = $pdo->query('SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != "" ORDER BY category')->fetchAll();

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
                    <h3 class="mb-1">Project Management</h3>
                    <p class="text-muted-glass mb-0">Create, approve, monitor, and review barangay projects.</p>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#createProjectModal">
                    <i class="bi bi-plus-lg"></i> Create Project
                </button>
            </div>

            <!-- Stats -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="stat-chip stat-total">
                    <span class="stat-dot"></span>
                    Total: <?php echo (int) ($projectStats['total'] ?? 0); ?>
                </span>
                <span class="stat-chip stat-secretaries">
                    <span class="stat-dot"></span>
                    Planned: <?php echo (int) ($projectStats['planned'] ?? 0); ?>
                </span>
                <span class="stat-chip stat-admins">
                    <span class="stat-dot"></span>
                    Ongoing: <?php echo (int) ($projectStats['ongoing'] ?? 0); ?>
                </span>
                <span class="stat-chip stat-active">
                    <span class="stat-dot"></span>
                    Completed: <?php echo (int) ($projectStats['completed'] ?? 0); ?>
                </span>
                <span class="stat-chip stat-total">
                    <span class="stat-dot"></span>
                    Rejected: <?php echo (int) ($projectStats['rejected'] ?? 0); ?>
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
                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Search projects..."
                               value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat['category']); ?>"
                                    <?php echo $categoryFilter === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo e($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="planned" <?php echo $statusFilter === 'planned' ? 'selected' : ''; ?>>Planned</option>
                            <option value="ongoing" <?php echo $statusFilter === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Projects Table -->
            <div class="glass-card p-3 p-md-4">
                <h5 class="mb-3" style="font-family:var(--font-display);font-weight:700;">All Projects</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Timeline</th>
                                <th>Budget</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <?php
                                    $remaining = (float) ($project['total_budget'] ?? 0) - (float) ($project['total_expenses'] ?? 0);
                                    $statusColor = match($project['status']) {
                                        'planned'   => 'secondary',
                                        'ongoing'   => 'info',
                                        'completed' => 'success',
                                        'rejected'  => 'danger',
                                        default     => 'primary'
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($project['title']); ?></strong><br>
                                        <small style="color:var(--text-low);">By <?php echo e($project['approved_by_name'] ?? 'System'); ?></small>
                                    </td>
                                    <td><?php echo e($project['category'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($project['start_date'] || $project['end_date']): ?>
                                            <?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : '...'; ?>
                                            &mdash;
                                            <?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '...'; ?>
                                        <?php else: ?>
                                            <span style="color:var(--text-low);">No timeline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($project['total_budget']): $short = fmtBudgetShort($project['total_budget']); $remShort = fmtBudgetShort($remaining); ?>
                                            <strong>&#8369;<?php echo $short; ?></strong><br>
                                            <small class="text-<?php echo $remaining >= 0 ? 'success' : 'danger'; ?>">
                                                Rem: &#8369;<?php echo $remShort; ?>
                                            </small>
                                        <?php else: ?>
                                            <span style="color:var(--text-low);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="min-width:120px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:8px;">
                                                <div class="progress-bar" style="width:<?php echo (int) $project['progress_percent']; ?>%;"></div>
                                            </div>
                                            <small style="color:var(--text-mid);white-space:nowrap;"><?php echo (int) $project['progress_percent']; ?>%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $statusColor; ?>-subtle text-<?php echo $statusColor; ?>">
                                            <?php echo e(ucfirst($project['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions justify-content-end">
                                            <?php if (in_array($project['status'], ['planned', 'rejected'])): ?>
                                                <form method="post" class="d-inline"
                                                      onsubmit="return confirm('Approve this project?')">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="project_id" value="<?php echo (int) $project['id']; ?>">
                                                    <button type="submit" name="action" value="approve_project"
                                                            class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-check-circle"></i> Approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($project['status'] === 'ongoing'): ?>
                                                <form method="post" class="d-inline"
                                                      onsubmit="return confirm('Mark this project as completed?')">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="project_id" value="<?php echo (int) $project['id']; ?>">
                                                    <button type="submit" name="action" value="complete_project"
                                                            class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-check2-circle"></i> Complete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal<?php echo (int) $project['id']; ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    data-project-id="<?php echo (int) $project['id']; ?>"
                                                    data-project-name="<?php echo e($project['title']); ?>">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($projects)) : ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-kanban" style="font-size:2rem;color:var(--text-low);display:block;margin-bottom:0.5rem;"></i>
                                        <span style="color:var(--text-low);">No projects found matching your criteria.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($projects)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!--  Create Project Modal                                               -->
<!-- ================================================================== -->
<div class="modal fade" id="createProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="e.g. Road Rehabilitation" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Health">Health</option>
                                <option value="Education">Education</option>
                                <option value="Environment">Environment</option>
                                <option value="Social">Social</option>
                                <option value="Economic">Economic</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Progress (%)</label>
                            <input type="number" name="progress_percent" class="form-control"
                                   min="0" max="100" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                   placeholder="e.g. Purok 3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Budget Amount</label>
                            <input type="number" step="0.01" name="budget_amount" class="form-control"
                                   placeholder="0.00" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Budget Source</label>
                            <input type="text" name="budget_source" class="form-control"
                                   placeholder="e.g. Barangay Fund">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Objectives</label>
                            <textarea name="objectives" class="form-control" rows="2"
                                      placeholder="Project objectives..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Project description..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!--  Edit Project Modals                                                -->
<!-- ================================================================== -->
<?php foreach ($projects as $project): ?>
    <div class="modal fade" id="editModal<?php echo (int) $project['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <?php echo csrfField(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="project_id" value="<?php echo (int) $project['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control"
                                       value="<?php echo e($project['title']); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <?php foreach (['Infrastructure', 'Health', 'Education', 'Environment', 'Social', 'Economic', 'Other'] as $cat): ?>
                                        <option value="<?php echo $cat; ?>"
                                            <?php echo $project['category'] === $cat ? 'selected' : ''; ?>>
                                            <?php echo $cat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="planned" <?php echo $project['status'] === 'planned' ? 'selected' : ''; ?>>Planned</option>
                                    <option value="ongoing" <?php echo $project['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="rejected" <?php echo $project['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control"
                                       value="<?php echo e($project['location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="<?php echo e($project['start_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="<?php echo e($project['end_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Progress (%)</label>
                                <input type="number" name="progress_percent" class="form-control"
                                       min="0" max="100"
                                       value="<?php echo (int) $project['progress_percent']; ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Objectives</label>
                                <textarea name="objectives" class="form-control" rows="2"><?php echo e($project['objectives'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"><?php echo e($project['description'] ?? ''); ?></textarea>
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
                    <h3 class="delete-toast-title">Delete Project</h3>
                </div>
                <div class="delete-toast-message">
                    <p>Are you sure you want to delete <span id="deleteToastName"></span>? This will also remove all budget and expense records.</p>
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
            
            document.querySelectorAll('[data-project-id]').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    pendingDeleteId = this.getAttribute('data-project-id');
                    toastName.textContent = this.getAttribute('data-project-name');
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
                    idInput.name = 'project_id';
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