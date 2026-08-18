<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$allowedRoles = $allowedRoles ?? ['admin'];
requireAuth($allowedRoles);

$pdo = getDbConnection();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    requireCsrf();
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId) {
        try {
            $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
        } catch (Throwable $e) {}
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['read_all']) && $_GET['read_all'] === '1') {
    try {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
    } catch (Throwable $e) {}
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss'])) {
    requireCsrf();
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId) {
        try {
            $stmt = $pdo->prepare('SELECT created_by FROM notifications WHERE id = ? AND user_id = ?');
            $stmt->execute([$notificationId, $userId]);
            $createdBy = $stmt->fetchColumn();
            if ($createdBy && (int) $createdBy !== $userId) {
                $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
            }
        } catch (Throwable $e) {}
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$paginator = [];
$notifications = [];
$unreadCount = 0;
try {
    $paginator = paginate(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ?',
        [$userId],
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC',
        [$userId]
    );
    $notifications = $paginator['data'];

    $unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([$userId]);
    $unreadCount = (int) $unreadStmt->fetchColumn();
} catch (Throwable $e) {
    $paginator = [];
    $notifications = [];
    $unreadCount = 0;
}

$totalCount = count($notifications);
$readCount = $totalCount - $unreadCount;

function detectNotifType($msg) {
    $msg = strtolower($msg);
    if (str_contains($msg, 'approved') || str_contains($msg, 'completed') || str_contains($msg, 'ready')) {
        return 'success';
    }
    if (str_contains($msg, 'rejected') || str_contains($msg, 'denied') || str_contains($msg, 'failed')) {
        return 'error';
    }
    if (str_contains($msg, 'urgent') || str_contains($msg, 'emergency')) {
        return 'urgent';
    }
    if (str_contains($msg, 'review') || str_contains($msg, 'pending') || str_contains($msg, 'processing')) {
        return 'info';
    }
    if (str_contains($msg, 'appointment') || str_contains($msg, 'schedule') || str_contains($msg, 'booked')) {
        return 'schedule';
    }
    return 'general';
}

$typeStyles = [
    'success'  => ['bg' => 'rgba(16,185,129,0.10)',  'color' => '#6ee7b7', 'icon' => 'bi-check-circle-fill',        'border' => 'rgba(16,185,129,0.20)'],
    'error'    => ['bg' => 'rgba(239,68,68,0.10)',    'color' => '#fca5a5', 'icon' => 'bi-x-circle-fill',            'border' => 'rgba(239,68,68,0.20)'],
    'urgent'   => ['bg' => 'rgba(239,68,68,0.10)',    'color' => '#fca5a5', 'icon' => 'bi-exclamation-octagon-fill',  'border' => 'rgba(239,68,68,0.20)'],
    'info'     => ['bg' => 'rgba(14,165,233,0.10)',   'color' => '#7dd3fc', 'icon' => 'bi-info-circle-fill',          'border' => 'rgba(14,165,233,0.20)'],
    'schedule' => ['bg' => 'rgba(245,158,11,0.10)',   'color' => '#fcd34d', 'icon' => 'bi-calendar-check',            'border' => 'rgba(245,158,11,0.20)'],
    'general'  => ['bg' => 'rgba(139,92,246,0.10)',   'color' => '#c4b5fd', 'icon' => 'bi-bell-fill',                 'border' => 'rgba(139,92,246,0.20)'],
];

$pageTitle = $pageTitle ?? 'Notifications';
$pageDescription = $pageDescription ?? 'Alerts and updates from the barangay regarding requests, appointments, and announcements.';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><?php echo e($pageTitle); ?></h2>
                    <p class="text-muted mb-0"><?php echo e($pageDescription); ?></p>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <a href="?read_all=1" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-check-all"></i> Mark All Read
                    </a>
                <?php endif; ?>
            </div>

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-bell-fill text-primary fs-3"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-0">Unread</h6>
                                    <h3 class="mb-0 fw-bold"><?php echo $unreadCount; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-check2-all text-success fs-3"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-0">Read</h6>
                                    <h3 class="mb-0 fw-bold"><?php echo $readCount; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-inbox text-info fs-3"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-0">Total</h6>
                                    <h3 class="mb-0 fw-bold"><?php echo $totalCount; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <?php if (!empty($notifications)): ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notif):
                        $isUnread = empty($notif['is_read']);
                        $detected = detectNotifType($notif['message'] ?? '');
                        $ts = $typeStyles[$detected];
                        $hasLink = !empty($notif['link']);
                    ?>
                        <div class="list-group-item border-0 shadow-sm mb-2 <?php echo $isUnread ? 'border-start border-4 border-primary' : ''; ?>">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="p-2 rounded" style="background:<?php echo $ts['bg']; ?>; color:<?php echo $ts['color']; ?>; border:1px solid <?php echo $ts['border']; ?>;">
                                        <i class="bi <?php echo $ts['icon']; ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="<?php echo $isUnread ? 'fw-semibold' : 'text-muted'; ?>">
                                        <?php echo nl2br(e($notif['message'])); ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i>
                                        <?php echo date('M d, Y \a\t h:i A', strtotime($notif['created_at'])); ?>
                                    </small>
                                    <div class="mt-2">
                                        <?php if ($hasLink): ?>
                                            <a href="<?php echo e($notif['link']); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-arrow-right-short"></i> View Details
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php if ($isUnread): ?>
                                        <form method="post" class="d-inline">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="notification_id" value="<?php echo (int) $notif['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-lg"></i> Mark Read
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-check-circle-fill"></i> Read
                                        </span>
                                    <?php endif; ?>
                                    <?php if (empty($notif['created_by']) || (int) $notif['created_by'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                        <form method="post" class="d-inline ms-1" onsubmit="return confirm('Dismiss this notification permanently?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="notification_id" value="<?php echo (int) $notif['id']; ?>">
                                            <button type="submit" name="dismiss" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i> Dismiss
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($paginator)) echo renderPagination($paginator); ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-bell-slash text-muted fs-1"></i>
                    </div>
                    <h5 class="text-muted">No Notifications Yet</h5>
                    <p class="text-muted">You don't have any notifications at the moment. We'll alert you when there are updates on requests or important system announcements.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
