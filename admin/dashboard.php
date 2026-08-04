<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

if (!defined('BASE_URL')) { define('BASE_URL', '/'); }

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

$sexDistribution = getSexDistribution();
$ageDistribution = $stats['age_distribution'] ?? ['0-17' => 0, '18-35' => 0, '36-50' => 0, '51-65' => 0, '65+' => 0];

$monthlyData = $stats['monthly_applications'] ?? [];
$dailyData = $stats['daily_applications'] ?? [];
$yearlyData = $stats['yearly_applications'] ?? [];

$monthlyCounts = array_column($monthlyData, 'count');
$maxMonth = !empty($monthlyCounts) ? max($monthlyCounts) : 1;

$dailyCounts = array_column($dailyData, 'count');
$maxDay = !empty($dailyCounts) ? max($dailyCounts) : 1;

$monthlyLabels = array_reverse(array_column($monthlyData, 'month'));
$monthlyValues = array_reverse(array_column($monthlyData, 'count'));

$dailyLabels = array_map(fn($row) => date('M d', strtotime($row['day'])), $dailyData);
$dailyValues = array_column($dailyData, 'count');

$yearlyLabels = array_reverse(array_column($yearlyData, 'year'));
$yearlyValues = array_reverse(array_column($yearlyData, 'count'));

// Month-over-month change for requests
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

$recentActivities = [];
try {
    $stmt = $pdo->query('SELECT al.action, al.details, al.created_at, u.full_name FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 8');
    $recentActivities = $stmt->fetchAll();
} catch (Throwable $e) {
    $recentActivities = [];
}

$statusBreakdown = [
    'submitted' => (int) ($stats['submitted'] ?? 0),
    'pending' => (int) ($stats['pending'] ?? 0),
    'under_review' => (int) ($stats['under_review'] ?? 0),
    'approved' => (int) ($stats['approved'] ?? 0),
    'ready_for_pickup' => (int) ($stats['ready_for_pickup'] ?? 0),
    'completed' => (int) ($stats['completed'] ?? 0),
    'rejected' => (int) ($stats['rejected'] ?? 0),
];

$projectStatusBreakdown = [
    'planned' => (int) ($stats['planned'] ?? 0),
    'ongoing' => (int) ($stats['ongoing'] ?? 0),
    'completed' => (int) ($stats['projects_completed'] ?? 0),
];

// Resident profiling stats
$profileStats = [];
try {
    $profileStats['sr_citizen'] = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE is_senior_citizen=1")->fetchColumn();
    $profileStats['pwd'] = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE is_pwd=1")->fetchColumn();
    $profileStats['solo_parent'] = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE is_solo_parent=1")->fetchColumn();
    $profileStats['ofw'] = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE is_ofw=1")->fetchColumn();
    $profileStats['indigent'] = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE is_indigent=1")->fetchColumn();
} catch (Throwable $e) {
    $profileStats = ['sr_citizen' => 0, 'pwd' => 0, 'solo_parent' => 0, 'ofw' => 0, 'indigent' => 0];
}

// Real complete profiles: has first_name AND last_name AND address
try {
    $totalProfiled = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE first_name IS NOT NULL AND first_name != '' AND last_name IS NOT NULL AND last_name != ''")->fetchColumn();
    $completeProfiles = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE first_name IS NOT NULL AND first_name != '' AND last_name IS NOT NULL AND last_name != '' AND address IS NOT NULL AND address != ''")->fetchColumn();
} catch (Throwable $e) {
    $totalProfiled = $stats['total_residents'] ?? 0;
    $completeProfiles = 0;
}
$profileCompletionPct = $totalProfiled > 0 ? round(($completeProfiles / $totalProfiled) * 100) : 0;

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
                    <h2 class="mb-0">Administrator Dashboard</h2>
                    <p class="text-muted mb-0">Real-time barangay system analytics and insights.</p>
                </div>
                <span class="badge bg-success-subtle text-success"><i class="bi bi-circle-fill" style="font-size: 8px;"></i> Live</span>
            </div>

            <!-- Quick Actions -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="<?php echo BASE_URL; ?>/admin/resident_profiling.php" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"><i class="bi bi-person-plus"></i> Add Resident</a>
                <a href="<?php echo BASE_URL; ?>/admin/projects.php" class="btn btn-sm btn-outline-info d-flex align-items-center gap-1"><i class="bi bi-kanban"></i> New Project</a>
                <a href="<?php echo BASE_URL; ?>/admin/announcements.php" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1"><i class="bi bi-megaphone"></i> Announce</a>
                <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
                <a href="<?php echo BASE_URL; ?>/admin/cctv.php" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"><i class="bi bi-camera-video"></i> CCTV Live</a>
            </div>

            <div class="row g-3 mb-4" id="dashboardStats">
                <div class="col-md-3">
<a href="<?php echo BASE_URL; ?>/admin/residents.php" class="text-decoration-none">
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
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/projects.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Projects</h6>
                                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['total_projects'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-info-subtle p-2"><i class="bi bi-kanban text-info"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2"><?php echo e($stats['ongoing_projects'] ?? 0); ?> ongoing, <?php echo e($stats['completed_projects'] ?? 0); ?> completed</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/projects.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Total Budget</h6>
                                    <p class="display-6 mb-0 fw-bold">₱<?php echo fmtBudgetShort($stats['total_budget'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-success-subtle p-2"><i class="bi bi-cash-coin text-success"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2">Allocated funds</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/projects.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Expenses</h6>
                                    <p class="display-6 mb-0 fw-bold">₱<?php echo fmtBudgetShort($stats['total_expenses'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-danger-subtle p-2"><i class="bi bi-receipt text-danger"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2">Total spending</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Certificates</h6>
                                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['documents_issued'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-primary-subtle p-2"><i class="bi bi-file-earmark-text text-primary"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2">Documents released</small>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4" id="dashboardNotifications">
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/announcements.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Announcements</h6>
                                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['total_announcements'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-info-subtle p-2"><i class="bi bi-megaphone text-info"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2">Published updates</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/appointments.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Appointments</h6>
                                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['total_appointments'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-warning-subtle p-2"><i class="bi bi-calendar-check text-warning"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2">Scheduled bookings</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/scanner/attendance_sheet.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">QR Scans</h6>
                                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['qr_scans'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-success-subtle p-2"><i class="bi bi-qr-code-scan text-success"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2">Verification scans</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/officials.php" class="text-decoration-none">
                        <div class="glass-card p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Officials</h6>
                                    <p class="display-6 mb-0 fw-bold"><?php echo e($stats['total_officials'] ?? 0); ?></p>
                                </div>
                                <div class="rounded-circle bg-primary-subtle p-2"><i class="bi bi-person-badge text-primary"></i></div>
                            </div>
                            <small class="text-muted d-block mt-2">Active barangay officials</small>
                        </div>
                    </a>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="stat-chip" style="background:rgba(255,193,7,0.12);border-color:rgba(255,193,7,0.25);color:#fbbf24;">
                    <span class="stat-dot" style="background:#fbbf24;"></span>Senior: <?php echo (int)($profileStats['sr_citizen'] ?? 0); ?>
                </span>
                <span class="stat-chip" style="background:rgba(13,202,240,0.12);border-color:rgba(13,202,240,0.25);color:#22d3ee;">
                    <span class="stat-dot" style="background:#22d3ee;"></span>PWD: <?php echo (int)($profileStats['pwd'] ?? 0); ?>
                </span>
                <span class="stat-chip" style="background:rgba(16,185,129,0.12);border-color:rgba(16,185,129,0.25);color:#34d399;">
                    <span class="stat-dot" style="background:#34d399;"></span>Solo Parent: <?php echo (int)($profileStats['solo_parent'] ?? 0); ?>
                </span>
                <span class="stat-chip" style="background:rgba(99,102,241,0.12);border-color:rgba(99,102,241,0.25);color:#818cf8;">
                    <span class="stat-dot" style="background:#818cf8;"></span>OFW: <?php echo (int)($profileStats['ofw'] ?? 0); ?>
                </span>
                <span class="stat-chip" style="background:rgba(239,68,68,0.12);border-color:rgba(239,68,68,0.25);color:#f87171;">
                    <span class="stat-dot" style="background:#f87171;"></span>Indigent: <?php echo (int)($profileStats['indigent'] ?? 0); ?>
                </span>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Monthly Applications (Last 12 Months)</h6>
                        <div style="height: 280px;">
                            <canvas id="monthlyApplicationsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Daily Applications (Last 14 Days)</h6>
                        <div style="height: 280px;">
                            <canvas id="dailyApplicationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Sex Distribution</h6>
                        <div style="height: 280px; display: flex; align-items: center; justify-content: center;">
                            <div style="width: 220px; height: 220px; position: relative;">
                                <canvas id="sexDistributionChart"></canvas>
                                <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                                    <span style="font-size: 1.5rem; font-weight: 700;"><?php echo e($stats['total_residents'] ?? 0); ?></span>
                                    <span style="font-size: 0.75rem; color: var(--text-mid);">Total</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Age Distribution</h6>
                        <div style="height: 280px;">
                            <canvas id="ageDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Project Status Overview</h6>
                        <div style="height: 280px;">
                            <canvas id="projectStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Application Status Breakdown</h6>
                        <div style="height: 280px;">
                            <canvas id="applicationStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Budget vs Expenses</h6>
                        <div style="height: 280px;">
                            <canvas id="budgetExpenseChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Applications This Year</h6>
                        <div style="height: 280px;">
                            <canvas id="yearlyApplicationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">Recent Activity</h6>
                        <div style="max-height: 320px; overflow-y: auto;">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <li class="list-group-item" style="background: transparent; border-bottom-color: var(--glass-border);">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong style="color: var(--text-hi);"><?php echo e($activity['action']); ?></strong>
                                                <span style="color: var(--text-mid); font-size: 0.85rem;">by <?php echo e($activity['full_name'] ?? 'System'); ?></span>
                                                <p class="mb-0" style="color: var(--text-mid); font-size: 0.85rem;"><?php echo nl2br(e($activity['details'] ?? '-')); ?></p>
                                            </div>
                                            <small style="color: var(--text-low); white-space: nowrap;"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($recentActivities)) : ?>
                                    <li class="list-group-item text-center" style="color: var(--text-mid); background: transparent;">No recent activity.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="mb-3">System Health</h6>
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: var(--text-mid);">Profiles with complete info</span>
                                    <span style="color: var(--text-hi); font-weight: 600;"><?php echo $profileCompletionPct; ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                                    <div class="progress-bar bg-success" style="width: <?php echo max(5, $profileCompletionPct); ?>%;"></div>
                                </div>
                                <small style="color: var(--text-low); font-size: 0.7rem;"><?php echo $completeProfiles; ?>/<?php echo $totalProfiled; ?> have name + address</small>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: var(--text-mid);">Budget utilization</span>
                                    <span style="color: var(--text-hi); font-weight: 600;"><?php echo $stats['total_budget'] > 0 ? round(($stats['total_expenses'] / $stats['total_budget']) * 100) : 0; ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $stats['total_budget'] > 0 ? min(100, max(5, ($stats['total_expenses'] / $stats['total_budget']) * 100)) : 5; ?>%;"></div>
                                </div>
                                <small style="color: var(--text-low); font-size: 0.7rem;">₱<?php echo fmtBudgetShort($stats['total_expenses'] ?? 0); ?> / ₱<?php echo fmtBudgetShort($stats['total_budget'] ?? 0); ?></small>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: var(--text-mid);">Project completion rate</span>
                                    <span style="color: var(--text-hi); font-weight: 600;"><?php echo $stats['total_projects'] > 0 ? round(($stats['completed_projects'] / $stats['total_projects']) * 100) : 0; ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                                    <div class="progress-bar bg-info" style="width: <?php echo $stats['total_projects'] > 0 ? min(100, max(5, ($stats['completed_projects'] / $stats['total_projects']) * 100)) : 5; ?>%;"></div>
                                </div>
                                <small style="color: var(--text-low); font-size: 0.7rem;"><?php echo $stats['completed_projects'] ?? 0; ?>/<?php echo $stats['total_projects'] ?? 0; ?> complete</small>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: var(--text-mid);">Request approval rate</span>
                                    <span style="color: var(--text-hi); font-weight: 600;"><?php echo ($stats['approved_requests'] + $stats['rejected_requests']) > 0 ? round(($stats['approved_requests'] / ($stats['approved_requests'] + $stats['rejected_requests'])) * 100) : 0; ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                                    <div class="progress-bar bg-success" style="width: <?php echo ($stats['approved_requests'] + $stats['rejected_requests']) > 0 ? min(100, max(5, ($stats['approved_requests'] / ($stats['approved_requests'] + $stats['rejected_requests'])) * 100)) : 5; ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const gridColor = 'rgba(246, 248, 251, 0.08)';
    const textColor = '#c8d0dc';
    const months = <?php echo json_encode(array_map(fn($m) => date('M', strtotime($m . '-01')), $monthlyLabels)); ?>;
    const monthlyValues = <?php echo json_encode(array_map('intval', $monthlyValues)); ?>;
    const dailyLabels = <?php echo json_encode(array_slice($dailyLabels, -14)); ?>;
    const dailyValues = <?php echo json_encode(array_map('intval', array_slice($dailyValues, -14))); ?>;
    const yearlyLabels = <?php echo json_encode($yearlyLabels); ?>;
    const yearlyValues = <?php echo json_encode(array_map('intval', $yearlyValues)); ?>;
    const sexLabels = ['Male', 'Female'];
    const sexValues = [<?php echo (int) ($sexDistribution['male'] ?? 0); ?>, <?php echo (int) ($sexDistribution['female'] ?? 0); ?>];
    const ageLabels = <?php echo json_encode(array_keys($ageDistribution)); ?>;
    const ageValues = <?php echo json_encode(array_map('intval', array_values($ageDistribution))); ?>;
    const projectLabels = <?php echo json_encode(array_keys($projectStatusBreakdown)); ?>;
    const projectValues = <?php echo json_encode(array_map('intval', array_values($projectStatusBreakdown))); ?>;
    const appStatusLabels = ['Submitted', 'Pending', 'Under Review', 'Approved', 'Ready', 'Completed', 'Rejected'];
    const appStatusValues = [
        <?php echo (int) ($statusBreakdown['submitted'] ?? 0); ?>,
        <?php echo (int) ($statusBreakdown['pending'] ?? 0); ?>,
        <?php echo (int) ($statusBreakdown['under_review'] ?? 0); ?>,
        <?php echo (int) ($statusBreakdown['approved'] ?? 0); ?>,
        <?php echo (int) ($statusBreakdown['ready_for_pickup'] ?? 0); ?>,
        <?php echo (int) ($statusBreakdown['completed'] ?? 0); ?>,
        <?php echo (int) ($statusBreakdown['rejected'] ?? 0); ?>
    ];

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    new Chart(document.getElementById('monthlyApplicationsChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Applications',
                data: monthlyValues,
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 4,
                maxBarThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('dailyApplicationsChart'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Applications',
                data: dailyValues,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 20 } }
            }
        }
    });

    new Chart(document.getElementById('sexDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: sexLabels,
            datasets: [{
                data: sexValues,
                backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(6, 182, 212, 0.8)'],
                borderColor: ['#3b82f6', '#06b6d4'],
                borderWidth: 2,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } }
            }
        }
    });

    new Chart(document.getElementById('ageDistributionChart'), {
        type: 'bar',
        data: {
            labels: ageLabels,
            datasets: [{
                label: 'Residents',
                data: ageValues,
                backgroundColor: [
                    'rgba(148, 163, 184, 0.7)',
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(6, 182, 212, 0.7)',
                    'rgba(242, 183, 5, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ],
                borderRadius: 4,
                maxBarThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('projectStatusChart'), {
        type: 'bar',
        data: {
            labels: projectLabels,
            datasets: [{
                label: 'Projects',
                data: projectValues,
                backgroundColor: [
                    'rgba(107, 114, 128, 0.7)',
                    'rgba(6, 182, 212, 0.7)',
                    'rgba(34, 197, 94, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ],
                borderRadius: 4,
                maxBarThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('applicationStatusChart'), {
        type: 'bar',
        data: {
            labels: appStatusLabels,
            datasets: [{
                label: 'Applications',
                data: appStatusValues,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(242, 183, 5, 0.7)',
                    'rgba(6, 182, 212, 0.7)',
                    'rgba(34, 197, 94, 0.7)',
                    'rgba(34, 197, 94, 0.7)',
                    'rgba(34, 197, 94, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ],
                borderRadius: 4,
                maxBarThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 16 } }
            }
        }
    });

    function fmtShort(v) {
        if (v >= 1000000) return (v/1000000).toFixed(v%1000000===0?0:1) + 'M';
        if (v >= 1000) return (v/1000).toFixed(v%1000===0?0:1) + 'K';
        return v.toFixed(0);
    }

    new Chart(document.getElementById('budgetExpenseChart'), {
        type: 'bar',
        data: {
            labels: ['Budget', 'Expenses', 'Remaining'],
            datasets: [{
                label: 'Amount',
                data: [
                    <?php echo (float) ($stats['total_budget'] ?? 0); ?>,
                    <?php echo (float) ($stats['total_expenses'] ?? 0); ?>,
                    <?php echo max(0, (float) ($stats['total_budget'] ?? 0) - (float) ($stats['total_expenses'] ?? 0)); ?>
                ],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(59, 130, 246, 0.8)'
                ],
                borderRadius: 6,
                maxBarThickness: 64
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return '₱' + fmtShort(ctx.parsed.y); }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        callback: function(v) { return '₱' + fmtShort(v); }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('yearlyApplicationsChart'), {
        type: 'bar',
        data: {
            labels: yearlyLabels,
            datasets: [{
                label: 'Applications',
                data: yearlyValues,
                backgroundColor: 'rgba(242, 183, 5, 0.6)',
                borderColor: '#f2b705',
                borderWidth: 1,
                borderRadius: 4,
                maxBarThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor } },
                y: { grid: { display: false } }
            }
        }
    });
    
    setInterval(function() {
        fetch('<?php echo BASE_URL; ?>/admin/dashboard_stats.php')
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var el = document.getElementById('dashboardStats');
                if (el) el.innerHTML = html;
            })
            .catch(function() {});
    }, 30000);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
