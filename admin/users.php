<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentRole = getCurrentRole();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? 'secretary');
        $status = trim($_POST['status'] ?? 'active');

        if (!$fullName || !$email || !$password) {
            $_SESSION['_flash_error'] = 'Full name, email, and password are required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (!validateEmail($email)) {
            $_SESSION['_flash_error'] = 'Invalid email format.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (!in_array($role, ['admin', 'secretary'], true)) {
            $_SESSION['_flash_error'] = 'Invalid role selected.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $_SESSION['_flash_error'] = 'Email already exists.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
                logAudit('create_user', 'Created user: ' . $fullName . ' (' . $role . ')');
                $_SESSION['_flash_success'] = 'User created successfully.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    } elseif ($action === 'update') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $status = trim($_POST['status'] ?? '');

        if ($userId <= 0 || $userId === $currentUserId) {
            $_SESSION['_flash_error'] = 'You cannot modify your own account here.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (!$fullName || !$email) {
            $_SESSION['_flash_error'] = 'Full name and email are required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (!validateEmail($email)) {
            $_SESSION['_flash_error'] = 'Invalid email format.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $check->execute([$email, $userId]);
            if ($check->fetch()) {
                $_SESSION['_flash_error'] = 'Email already in use by another account.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?');
                $stmt->execute([$fullName, $email, $role, $status, $userId]);
                logAudit('update_user', 'Updated user ID: ' . $userId);
                $_SESSION['_flash_success'] = 'User updated successfully.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    } elseif ($action === 'reset_password') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($userId !== $currentUserId) {
            $_SESSION['_flash_error'] = 'You can only change your own password.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif ($userId <= 0) {
            $_SESSION['_flash_error'] = 'Invalid user.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (strlen($newPassword) < 6) {
            $_SESSION['_flash_error'] = 'New password must be at least 6 characters.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['_flash_error'] = 'New password and confirmation do not match.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify($currentPassword, $hash)) {
                $_SESSION['_flash_error'] = 'Current password is incorrect.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                logAudit('change_own_password', 'User changed own password ID: ' . $userId);
                $_SESSION['_flash_success'] = 'Password changed successfully.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0 || $userId === $currentUserId) {
            $_SESSION['_flash_error'] = 'You cannot delete your own account.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            logAudit('delete_user', 'Deleted user ID: ' . $userId);
            $_SESSION['_flash_success'] = 'User deleted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$countWhere = 'WHERE 1=1';
$dataWhere = 'WHERE 1=1';
$params = [];

if ($search) {
    $countWhere .= ' AND (full_name LIKE ? OR email LIKE ?)';
    $dataWhere .= ' AND (full_name LIKE ? OR email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($roleFilter) {
    $countWhere .= ' AND role = ?';
    $dataWhere .= ' AND role = ?';
    $params[] = $roleFilter;
}
if ($statusFilter) {
    $countWhere .= ' AND status = ?';
    $dataWhere .= ' AND status = ?';
    $params[] = $statusFilter;
}
$paginator = paginate(
    "SELECT COUNT(*) FROM users $countWhere",
    $params,
    "SELECT id, full_name, email, role, status, created_at FROM users $dataWhere ORDER BY created_at DESC",
    $params
);
$users = $paginator['data'];

$statsQuery = 'SELECT
    COUNT(*) as total,
    SUM(role = "admin") as admins,
    SUM(role = "secretary") as secretaries,
    SUM(status = "active") as active,
    SUM(status = "inactive") as inactive
    FROM users';
$stats = $pdo->query($statsQuery)->fetch();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
    border: 1px solid transparent;
}
.stat-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    opacity: 0.9;
    text-decoration: none;
}
.stat-chip:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
    text-decoration: none;
}
.stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.stat-total { background: rgba(59,130,246,0.10); color: #1e40af; border-color: rgba(59,130,246,0.20); }
.stat-total .stat-dot { background: #3b82f6; }
.stat-admins { background: rgba(244,63,94,0.10); color: #9f1239; border-color: rgba(244,63,94,0.20); }
.stat-admins .stat-dot { background: #f43f5e; }
.stat-secretaries { background: rgba(245,158,11,0.10); color: #92400e; border-color: rgba(245,158,11,0.20); }
.stat-secretaries .stat-dot { background: #f59e0b; }
.stat-active { background: rgba(34,197,94,0.10); color: #166534; border-color: rgba(34,197,94,0.20); }
.stat-active .stat-dot { background: #22c55e; }
</style>

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
                    <h3 class="mb-1">Users Management</h3>
                    <p class="text-muted-glass mb-0">Manage administrator, secretary, and resident accounts.</p>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-plus-lg"></i> Create User
                </button>
                <button class="btn btn-outline-secondary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#resetModal<?php echo $currentUserId; ?>">
                    <i class="bi bi-key"></i> Change My Password
                </button>
            </div>

            <!-- Stats -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a class="stat-chip stat-total" href="/admin/users.php">
                    <span class="stat-dot"></span>
                    Total: <?php echo (int) ($stats['total'] ?? 0); ?>
                </a>
                <a class="stat-chip stat-admins" href="/admin/users.php?role=admin">
                    <span class="stat-dot"></span>
                    Admins: <?php echo (int) ($stats['admins'] ?? 0); ?>
                </a>
                <a class="stat-chip stat-secretaries" href="/admin/users.php?role=secretary">
                    <span class="stat-dot"></span>
                    Secretaries: <?php echo (int) ($stats['secretaries'] ?? 0); ?>
                </a>
                <a class="stat-chip stat-active" href="/admin/users.php?status=active">
                    <span class="stat-dot"></span>
                    Active: <?php echo (int) ($stats['active'] ?? 0); ?>
                </a>
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
                               placeholder="Search name or email..."
                               value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="secretary" <?php echo $roleFilter === 'secretary' ? 'selected' : ''; ?>>Secretary</option>
                            <option value="resident" <?php echo $roleFilter === 'resident' ? 'selected' : ''; ?>>Resident</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="glass-card p-3 p-md-4">
                <h5 class="mb-3" style="font-family:var(--font-display);font-weight:700;">All Users</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                    $isSelf = ((int) $user['id']) === $currentUserId;
                                    $roleBadge = match($user['role']) {
                                        'admin'     => 'badge-glass-admin',
                                        'secretary' => 'badge-glass-secretary',
                                        'resident'  => 'badge-glass-resident',
                                        default     => 'badge-glass-resident'
                                    };
                                    $statusBadge = match($user['status']) {
                                        'active'   => 'badge-glass-active',
                                        'inactive' => 'badge-glass-inactive',
                                        default    => 'badge-glass-inactive'
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($user['full_name']); ?></strong>
                                        <?php if ($isSelf): ?>
                                            <span class="badge badge-glass-you ms-1">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><span class="badge <?php echo $roleBadge; ?>"><?php echo e(ucfirst($user['role'])); ?></span></td>
                                    <td><span class="badge <?php echo $statusBadge; ?>"><?php echo e(ucfirst($user['status'])); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions justify-content-end">
                                            <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal<?php echo (int) $user['id']; ?>"
                                                    <?php echo $isSelf ? 'disabled' : ''; ?>>
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#resetModal<?php echo (int) $user['id']; ?>"
                                                    <?php echo $isSelf ? '' : 'disabled'; ?>>
                                                <i class="bi bi-key"></i> Reset
                                            </button>
                                            <?php if (!$isSelf): ?>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    data-user-id="<?php echo (int) $user['id']; ?>"
                                                    data-user-name="<?php echo e($user['full_name']); ?>">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($users)) : ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-people" style="font-size:2rem;color:var(--text-low);display:block;margin-bottom:0.5rem;"></i>
                                        <span style="color:var(--text-low);">No users found matching your criteria.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($users)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!--  Create User Modal                                                  -->
<!-- ================================================================== -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                   placeholder="e.g. Juan Dela Cruz" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="e.g. juan@email.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div style="position:relative;">
                                <input type="password" name="password" class="form-control"
                                       placeholder="Minimum 6 characters" required minlength="6" style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6c757d;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="secretary" selected>Secretary</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!--  Edit User Modals                                                   -->
<!-- ================================================================== -->
<?php foreach ($users as $user): ?>
    <div class="modal fade" id="editModal<?php echo (int) $user['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <?php echo csrfField(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?php echo e($user['full_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="secretary" <?php echo $user['role'] === 'secretary' ? 'selected' : ''; ?>>Secretary</option>
                                    <option value="resident" <?php echo $user['role'] === 'resident' ? 'selected' : ''; ?>>Resident</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
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

<!-- ================================================================== -->
<!--  Reset Password Modals                                              -->
<!-- ================================================================== -->
<?php foreach ($users as $user): ?>
    <div class="modal fade" id="resetModal<?php echo (int) $user['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post"
                      onsubmit="return confirm('Reset password for <?php echo e($user['full_name']); ?>?')">
                    <?php echo csrfField(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                        <p style="color:var(--text-mid);font-size:0.9rem;margin-bottom:1rem;">
                            Change your password for
                            <strong style="color:var(--text-hi);"><?php echo e($user['full_name']); ?></strong>
                            <span style="color:var(--text-low);">(<?php echo e($user['email']); ?>)</span>
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div style="position:relative;">
                                <input type="password" name="current_password" class="form-control"
                                       placeholder="Enter your current password" required style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6c757d;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div style="position:relative;">
                                <input type="password" name="new_password" class="form-control"
                                       placeholder="Minimum 6 characters" required minlength="6" style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6c757d;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div style="position:relative;">
                                <input type="password" name="confirm_password" class="form-control"
                                       placeholder="Re-enter new password" required minlength="6" style="padding-right:40px;">
                                <button type="button" onclick="togglePw(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6c757d;padding:0;"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-repeat me-1"></i> Reset Password
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
                <h3 class="delete-toast-title">Delete User</h3>
            </div>
            <div class="delete-toast-message">
                <p>Are you sure you want to delete user <span id="deleteToastName"></span>? This action cannot be undone.</p>
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
        
        document.querySelectorAll('[data-user-id]').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                pendingDeleteId = this.getAttribute('data-user-id');
                toastName.textContent = this.getAttribute('data-user-name');
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
                idInput.name = 'user_id';
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

    function togglePw(btn){
        var inp=btn.previousElementSibling;
        var ic=btn.querySelector('i');
        if(inp.type==='password'){inp.type='text';ic.className='bi bi-eye-slash';}
        else{inp.type='password';ic.className='bi bi-eye';}
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>