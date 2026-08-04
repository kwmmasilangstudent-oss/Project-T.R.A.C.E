<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

function fmtBudgetShort($amount) {
    if ($amount === null || $amount == 0) return '0';
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

$stats = getDashboardStats();

$currentMonthStart = date('Y-m-01');
$lastMonthStart = date('Y-m-01', strtotime('-1 month'));
$moM = [];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE created_at >= ?");
    $stmt->execute([$currentMonthStart]);
    $cur = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE created_at >= ? AND created_at < ?");
    $stmt->execute([$lastMonthStart, $currentMonthStart]);
    $prev = (int) $stmt->fetchColumn();
    $moM['requests_pct'] = $prev > 0 ? round((($cur - $prev) / $prev) * 100) : ($cur > 0 ? 100 : 0);
    $moM['requests_dir'] = $moM['requests_pct'] >= 0 ? 'up' : 'down';
} catch (Throwable $e) {
    $moM = ['requests_pct' => 0, 'requests_dir' => 'up'];
}

try {
    $curRes = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE created_at >= '$currentMonthStart'")->fetchColumn();
    $prevRes = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE created_at >= '$lastMonthStart' AND created_at < '$currentMonthStart'")->fetchColumn();
    $moM['residents_pct'] = $prevRes > 0 ? round((($curRes - $prevRes) / $prevRes) * 100) : ($curRes > 0 ? 100 : 0);
    $moM['residents_dir'] = $moM['residents_pct'] >= 0 ? 'up' : 'down';
} catch (Throwable $e) {
    $moM['residents_pct'] = 0;
    $moM['residents_dir'] = 'up';
}
?>
<div class="col-md-3">
    <a href="<?php echo BASE_URL; ?>/admin/resident_profiling.php" class="text-decoration-none">
        <div class="glass-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-muted mb-1">Total Residents</h6>
                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['total_residents'] ?? 0); ?></p>
                </div>
                <div class="rounded-circle bg-primary-subtle p-2"><i class="bi bi-people text-primary"></i></div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <small class="text-muted">Registered in system</small>
                <small class="text-<?php echo $moM['residents_dir'] === 'up' ? 'success' : 'danger'; ?>">
                    <i class="bi bi-arrow-<?php echo $moM['residents_dir']; ?>"></i>
                    <?php echo abs($moM['residents_pct']); ?>% this month
                </small>
            </div>
        </div>
    </a>
</div>
<div class="col-md-3">
    <a href="<?php echo BASE_URL; ?>/secretary/requests.php" class="text-decoration-none">
        <div class="glass-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-muted mb-1">Pending Requests</h6>
                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['pending_requests'] ?? 0); ?></p>
                </div>
                <div class="rounded-circle bg-warning-subtle p-2"><i class="bi bi-hourglass-split text-warning"></i></div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <small class="text-muted">Awaiting review</small>
                <small class="text-<?php echo $moM['requests_dir'] === 'up' ? 'danger' : 'success'; ?>">
                    <i class="bi bi-arrow-<?php echo $moM['requests_dir']; ?>"></i>
                    <?php echo abs($moM['requests_pct']); ?>% MoM
                </small>
            </div>
        </div>
    </a>
</div>
<div class="col-md-3">
    <a href="<?php echo BASE_URL; ?>/secretary/requests.php?status=approved" class="text-decoration-none">
        <div class="glass-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-muted mb-1">Approved Requests</h6>
                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['approved_requests'] ?? 0); ?></p>
                </div>
                <div class="rounded-circle bg-success-subtle p-2"><i class="bi bi-check-circle text-success"></i></div>
            </div>
            <small class="text-muted d-block mt-2">Processed successfully</small>
        </div>
    </a>
</div>
<div class="col-md-3">
    <a href="<?php echo BASE_URL; ?>/secretary/requests.php?status=rejected" class="text-decoration-none">
        <div class="glass-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-muted mb-1">Rejected Requests</h6>
                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['rejected_requests'] ?? 0); ?></p>
                </div>
                <div class="rounded-circle bg-danger-subtle p-2"><i class="bi bi-x-circle text-danger"></i></div>
            </div>
            <small class="text-muted d-block mt-2">Declined applications</small>
        </div>
    </a>
</div>