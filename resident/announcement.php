<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['resident']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$announcementId = (int) ($_GET['id'] ?? 0);
$announcement = null;

if ($announcementId > 0) {
    try {
        $residentStmt = $pdo->prepare('SELECT id FROM residents WHERE user_id = ? LIMIT 1');
        $residentStmt->execute([$_SESSION['user_id']]);
        $resident = $residentStmt->fetch();

        if ($resident) {
            $stmt = $pdo->prepare('SELECT a.* FROM announcements a JOIN announcement_reads ar ON ar.announcement_id = a.id WHERE a.id = ? AND a.is_active = 1 AND ar.resident_id = ? LIMIT 1');
            $stmt->execute([$announcementId, $resident['id']]);
            $announcement = $stmt->fetch();
        }
    } catch (Throwable $e) {
        $announcement = null;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<?php if (!$announcement): ?>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
            <div class="col-md-9 py-4">
                <div class="db-card db-reveal db-d4">
                    <div class="db-card-body text-center py-5">
                        <div class="display-1 text-muted mb-3"><i class="bi bi-megaphone"></i></div>
                        <h4 class="text-muted">Announcement not found</h4>
                        <p class="text-muted">The announcement you are looking for does not exist or is no longer available.</p>
                        <a href="<?php echo BASE_URL; ?>/resident/dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 p-0"><?php require_once __DIR__ . '/../includes/sidebar.php'; ?></div>
            <div class="col-md-9 py-4">
                <div class="db-card db-reveal db-d4" style="margin-bottom:24px;">
                    <div class="db-card-header">
                        <h5><i class="bi bi-megaphone" style="color:#fcd34d;"></i> <?php echo e($announcement['title']); ?></h5>
                    </div>
                    <div class="db-card-body">
                        <div class="db-ann-item">
                            <div class="db-ann-content">
                                <div class="db-ann-title"><?php echo e($announcement['title']); ?></div>
                                <div class="db-ann-meta">
                                    <span class="db-ann-badge" style="background:rgba(245,158,11,0.12); color:#fcd34d;">
                                        <?php echo e(ucfirst($announcement['type'] ?? 'general')); ?>
                                    </span>
                                    <span class="db-ann-date"><?php echo date('M d, Y \a\t h:i A', strtotime($announcement['created_at'])); ?></span>
                                </div>
                                <div style="margin-top:20px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.08);">
                                    <p style="color:#94a3b8; line-height:1.8; white-space:pre-wrap; font-size:0.92rem;"><?php echo nl2br(e($announcement['content'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
