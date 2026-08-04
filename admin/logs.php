<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$search = trim($_GET['search'] ?? '');
$actionFilter = trim($_GET['action'] ?? '');
$userFilter = (int) ($_GET['user'] ?? 0);

$query = 'SELECT aul.*, u.full_name, u.email FROM audit_logs aul LEFT JOIN users u ON u.id = aul.user_id WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (aul.action LIKE ? OR aul.details LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($actionFilter) {
    $query .= ' AND aul.action LIKE ?';
    $params[] = $actionFilter;
}
if ($userFilter) {
    $query .= ' AND aul.user_id = ?';
    $params[] = $userFilter;
}
$orderBy = ' ORDER BY aul.created_at DESC';

$whereClause = substr($query, strpos($query, 'WHERE 1=1') + 9, strpos($query, ' ORDER BY') - (strpos($query, 'WHERE 1=1') + 9));

$paginator = paginate(
    "SELECT COUNT(*) FROM audit_logs aul LEFT JOIN users u ON u.id = aul.user_id WHERE 1=1" . $whereClause,
    $params,
    "SELECT aul.*, u.full_name, u.email FROM audit_logs aul LEFT JOIN users u ON u.id = aul.user_id WHERE 1=1" . $whereClause . $orderBy,
    $params,
    50
);
$logs = $paginator['data'];

try {
    $users = $pdo->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();
    $uniqueActions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll();
} catch (Throwable $e) {
    $logs = [];
    $users = [];
    $uniqueActions = [];
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
                    <h3>Audit Logs</h3>
                    <p class="text-muted">Track user actions, system changes, and security events.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/reports.php?report=audit&export=csv" class="btn btn-sm btn-primary">Export CSV</a>
            </div>

            <div class="glass-card p-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search logs..." value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Action</label>
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <?php foreach ($uniqueActions as $actionRow): ?>
                                <option value="<?php echo e($actionRow['action']); ?>" <?php echo $actionFilter === $actionRow['action'] ? 'selected' : ''; ?>><?php echo e($actionRow['action']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">User</label>
                        <select name="user" class="form-select">
                            <option value="0">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo (int) $user['id']; ?>" <?php echo $userFilter === (int) $user['id'] ? 'selected' : ''; ?>><?php echo e($user['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4">
                <h5 class="mb-3">Recent Activity</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($logs as $log): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong><?php echo e($log['action']); ?></strong>
                                    <span class="badge bg-secondary-subtle text-secondary ms-2"><?php echo e($log['full_name'] ?? 'System'); ?></span>
                                    <p class="mb-0 text-muted"><?php echo e($log['details'] ?? '-'); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                        <?php if ($log['ip_address']): ?> • IP: <?php echo e($log['ip_address']); ?> <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($logs)) : ?>
                        <li class="list-group-item text-center text-muted py-4">No audit logs found.</li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($logs)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
