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
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_remarks' && $applicationId > 0) {
        $remarks = trim($_POST['remarks'] ?? '');
        $pdo->prepare('UPDATE applications SET remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?')->execute([$remarks, $_SESSION['user_id'], $applicationId]);
        logAudit('update_remarks', 'Updated remarks for application ID: ' . $applicationId);
        $_SESSION['_flash_success'] = 'Remarks updated.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif ($applicationId > 0 && $action) {
        $updates = ['reviewed_by' => $_SESSION['user_id'], 'reviewed_at' => date('Y-m-d H:i:s')];
        $status = null;

        if ($action === 'approve') {
            $status = 'approved';
        } elseif ($action === 'reject') {
            $status = 'rejected';
        } elseif ($action === 'ready') {
            $status = 'ready_for_pickup';
        } elseif ($action === 'complete') {
            $status = 'completed';
        } elseif ($action === 'review') {
            $status = 'under_review';
        } elseif ($action === 'pending') {
            $status = 'pending';
        }

        if ($status) {
            $updates['status'] = $status;
        }
        $remarks = trim($_POST['remarks'] ?? '');
        if ($remarks) {
            $updates['remarks'] = $remarks;
        }

        $setParts = [];
        $params = [];
        foreach ($updates as $column => $value) {
            $setParts[] = $column . ' = ?';
            $params[] = $value;
        }
        $params[] = $applicationId;

        $stmt = $pdo->prepare('UPDATE applications SET ' . implode(', ', $setParts) . ' WHERE id = ?');
        $stmt->execute($params);
        logAudit('update_application', 'Updated application ID: ' . $applicationId . ' status: ' . ($status ?? 'no change'));
        if ($status) {
            notifyApplicationStatus($applicationId, $status, (int) ($_SESSION['user_id'] ?? 0));
        }
        $_SESSION['_flash_success'] = 'Application updated successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$statusFilter = trim($_GET['status'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = '';
$params = [];
if ($statusFilter) {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
}
if ($priorityFilter) {
    $where .= ' AND a.priority = ?';
    $params[] = $priorityFilter;
}
if ($search) {
    $where .= ' AND (r.full_name LIKE ? OR a.application_type LIKE ? OR a.purpose LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$orderBy = ' ORDER BY 
    CASE 
        WHEN a.priority = "urgent" THEN 1
        WHEN a.priority = "high" THEN 2
        ELSE 3
    END,
    a.created_at DESC';

$applications = [];
$stats = [];
try {
    $baseQuery = 'SELECT a.*, r.full_name, r.address FROM applications a LEFT JOIN residents r ON r.id = a.resident_id WHERE 1=1';
    $countBase = 'SELECT COUNT(*) FROM applications a LEFT JOIN residents r ON r.id = a.resident_id WHERE 1=1';
    $paginator = paginate($countBase . $where, $params, $baseQuery . $where . $orderBy, $params);
    $applications = $paginator['data'];

    $statsQuery = 'SELECT 
        COUNT(*) as total,
        SUM(status = "submitted") as submitted,
        SUM(status = "pending") as pending,
        SUM(status = "under_review") as under_review,
        SUM(status = "approved") as approved,
        SUM(status = "ready_for_pickup") as ready_for_pickup,
        SUM(status = "completed") as completed,
        SUM(status = "rejected") as rejected
        FROM applications';
    $stats = $pdo->query($statsQuery)->fetch();
} catch (Throwable $e) {
    $applications = [];
    $stats = [];
}

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
                    <h3>Application Oversight</h3>
                    <p class="text-muted">Monitor and manage all barangay applications and service requests.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary">Total: <?php echo (int) ($stats['total'] ?? 0); ?></span>
                    <span class="badge bg-warning-subtle text-warning">Pending: <?php echo (int) ($stats['pending'] ?? 0); ?></span>
                    <span class="badge bg-info-subtle text-info">Review: <?php echo (int) ($stats['under_review'] ?? 0); ?></span>
                    <span class="badge bg-success-subtle text-success">Approved: <?php echo (int) ($stats['approved'] ?? 0); ?></span>
                </div>
            </div>
            <?php if (!empty($success)) : ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <?php if (!empty($error)) : ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

            <div class="glass-card p-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="submitted" <?php echo $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?php echo $statusFilter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="ready_for_pickup" <?php echo $statusFilter === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="normal" <?php echo $priorityFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search name, type, or purpose..." value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4">
                <h5 class="mb-3">All Applications</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Resident</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <?php
                                    $statusBadge = match($app['status']) {
                                        'submitted' => 'bg-primary-subtle text-primary',
                                        'pending' => 'bg-warning-subtle text-warning',
                                        'under_review' => 'bg-info-subtle text-info',
                                        'approved' => 'bg-success-subtle text-success',
                                        'ready_for_pickup' => 'bg-success-subtle text-success',
                                        'completed' => 'bg-success-subtle text-success',
                                        'rejected' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary'
                                    };
                                ?>
                                <tr>
                                    <td><strong>#<?php echo (int) $app['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo e($app['full_name'] ?? 'Unknown'); ?></strong><br>
                                        <small class="text-muted"><?php echo e($app['address'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo e($app['application_type']); ?></td>
                                    <td>
                                        <?php if ($app['priority'] === 'urgent'): ?>
                                            <span class="badge bg-danger-subtle text-danger">Urgent</span>
                                        <?php elseif ($app['priority'] === 'high'): ?>
                                            <span class="badge bg-warning-subtle text-warning">High</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?php echo $statusBadge; ?>"><?php echo e(str_replace('_', ' ', ucwords($app['status']))); ?></span></td>
                                    <td><?php echo e($app['remarks'] ?? '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <form method="post" class="d-flex flex-wrap gap-1">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="application_id" value="<?php echo (int) $app['id']; ?>">
                                            <?php if (in_array($app['status'], ['submitted', 'pending'])): ?>
                                                <button type="submit" name="action" value="review" class="btn btn-sm btn-outline-info">Review</button>
                                            <?php endif; ?>
                                            <?php if ($app['status'] === 'under_review'): ?>
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-outline-success">Approve</button>
                                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm">Reject</button>
                                            <?php endif; ?>
                                            <?php if ($app['status'] === 'approved'): ?>
                                                <button type="submit" name="action" value="ready" class="btn btn-sm btn-outline-success">Ready</button>
                                            <?php endif; ?>
                                            <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                <button type="submit" name="action" value="complete" class="btn btn-sm btn-outline-primary">Complete</button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="post" class="d-flex mt-1">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="application_id" value="<?php echo (int) $app['id']; ?>">
                                            <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Add remarks..." value="<?php echo e($app['remarks'] ?? ''); ?>">
                                            <button type="submit" name="action" value="update_remarks" class="btn btn-sm btn-outline-secondary ms-1">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($applications)) : ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No applications found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($applications)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';