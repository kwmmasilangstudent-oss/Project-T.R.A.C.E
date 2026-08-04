<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$reportType = trim($_GET['report'] ?? 'residents');

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = $reportType . '_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    switch ($reportType) {
        case 'residents':
            fputcsv($output, ['ID', 'Full Name', 'Birth Date', 'Sex', 'Address', 'Contact', 'Household', 'Civil Status', 'Occupation', 'Education', 'Created']);
            $residents = $pdo->query('SELECT * FROM residents ORDER BY full_name')->fetchAll();
            foreach ($residents as $resident) {
                fputcsv($output, [
                    $resident['id'],
                    $resident['full_name'],
                    $resident['birth_date'],
                    $resident['sex'],
                    $resident['address'],
                    $resident['contact_number'],
                    $resident['household_number'],
                    $resident['civil_status'],
                    $resident['occupation'],
                    $resident['education'],
                    $resident['created_at']
                ]);
            }
            break;

        case 'projects':
            fputcsv($output, ['ID', 'Title', 'Category', 'Status', 'Start Date', 'End Date', 'Progress', 'Created']);
            $projects = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll();
            foreach ($projects as $project) {
                fputcsv($output, [
                    $project['id'],
                    $project['title'],
                    $project['category'],
                    $project['status'],
                    $project['start_date'],
                    $project['end_date'],
                    $project['progress_percent'],
                    $project['created_at']
                ]);
            }
            break;

        case 'financial':
            fputcsv($output, ['Project', 'Type', 'Amount', 'Source', 'Description', 'Date']);
            $stmt = $pdo->query('SELECT p.title, pb.type, pb.amount, pb.source, pb.description, pb.created_at FROM project_budget pb LEFT JOIN projects p ON p.id = pb.project_id ORDER BY pb.created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['title'], $row['type'], $row['amount'], $row['source'], $row['description'], $row['created_at']]);
            }
            break;

        case 'certificates':
            fputcsv($output, ['ID', 'Resident', 'Document Number', 'Control Number', 'Type', 'Purpose', 'Status', 'Date']);
            $stmt = $pdo->query('SELECT d.*, r.full_name FROM documents d LEFT JOIN residents r ON r.id = d.resident_id ORDER BY d.created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['full_name'], $row['document_number'], $row['control_number'], $row['document_type'], $row['purpose'], $row['status'], $row['created_at']]);
            }
            break;

        case 'applications':
            fputcsv($output, ['ID', 'Resident', 'Type', 'Priority', 'Status', 'Remarks', 'Date']);
            $stmt = $pdo->query('SELECT a.*, r.full_name FROM applications a LEFT JOIN residents r ON r.id = a.resident_id ORDER BY a.created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['full_name'], $row['application_type'], $row['priority'], $row['status'], $row['remarks'], $row['created_at']]);
            }
            break;

        case 'attendance':
            fputcsv($output, ['ID', 'Title', 'Type', 'Date', 'Time From', 'Time To', 'Location', 'Attendees', 'Status']);
            $stmt = $pdo->query('SELECT * FROM agenda ORDER BY agenda_date DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['title'], $row['meeting_type'], $row['agenda_date'], $row['time_from'], $row['time_to'], $row['location'], $row['attendees'], $row['status']]);
            }
            break;

        case 'announcements':
            fputcsv($output, ['ID', 'Title', 'Type', 'Priority', 'Audience', 'Created']);
            $stmt = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['title'], $row['type'], $row['priority'], $row['audience'], $row['created_at']]);
            }
            break;

        case 'activity':
            fputcsv($output, ['ID', 'User', 'Action', 'Details', 'Date']);
            $stmt = $pdo->query('SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['full_name'], $row['action'], $row['details'], $row['created_at']]);
            }
            break;

        case 'audit':
            fputcsv($output, ['ID', 'User', 'Action', 'Details', 'IP Address', 'User Agent', 'Date']);
            $stmt = $pdo->query('SELECT aul.*, u.full_name FROM audit_logs aul LEFT JOIN users u ON u.id = aul.user_id ORDER BY aul.created_at DESC');
            while ($row = $stmt->fetch()) {
                fputcsv($output, [$row['id'], $row['full_name'], $row['action'], $row['details'], $row['ip_address'], $row['user_agent'], $row['created_at']]);
            }
            break;
    }

    fclose($output);
    logAudit('export_report', 'Exported ' . $reportType . ' report');
    exit;
}

$reportMeta = [
    'residents'     => ['label' => 'Resident Reports',      'icon' => 'bi-people',           'desc' => 'Complete listing of all registered barangay residents.'],
    'projects'      => ['label' => 'Project Reports',        'icon' => 'bi-kanban',           'desc' => 'Overview of all barangay projects and their current statuses.'],
    'financial'     => ['label' => 'Financial Reports',      'icon' => 'bi-cash-stack',       'desc' => 'Budget allocations and expense records across all projects.'],
    'certificates'  => ['label' => 'Certificate Reports',    'icon' => 'bi-file-earmark-text','desc' => 'Issued documents and certificates inventory.'],
    'applications'  => ['label' => 'Application Reports',    'icon' => 'bi-inbox',            'desc' => 'All resident applications and their processing status.'],
    'attendance'    => ['label' => 'Attendance Reports',     'icon' => 'bi-calendar-check',   'desc' => 'Agenda, meetings, and attendance records.'],
    'announcements' => ['label' => 'Announcement Reports',   'icon' => 'bi-megaphone',        'desc' => 'Published barangay announcements and bulletins.'],
    'activity'      => ['label' => 'Activity Reports',       'icon' => 'bi-activity',         'desc' => 'User activity log across the entire system.'],
    'audit'         => ['label' => 'Audit Reports',          'icon' => 'bi-shield-lock',      'desc' => 'System audit trail with IP and user agent details.'],
];

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
                    <h3 class="mb-1">Reports</h3>
                    <p class="text-muted-glass mb-0">Generate and export system reports for record-keeping and analysis.</p>
                </div>
                <a href="?report=<?php echo e($reportType); ?>&export=csv"
                   class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>

            <!-- Report Type Selector -->
            <div class="glass-card p-3 p-md-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Report Type</label>
                        <select name="report" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($reportMeta as $key => $meta): ?>
                                <option value="<?php echo e($key); ?>"
                                    <?php echo $reportType === $key ? 'selected' : ''; ?>>
                                    <?php echo e($meta['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <p class="mb-0" style="color:var(--text-mid);font-size:0.9rem;line-height:2.4;">
                            <i class="bi <?php echo $reportMeta[$reportType]['icon'] ?? 'bi-file-bar-graph'; ?> me-1"></i>
                            <?php echo e($reportMeta[$reportType]['desc'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Load</button>
                    </div>
                </form>

                <!-- Quick Nav Pills -->
                <div class="d-flex flex-wrap gap-2 mt-3 pt-3" style="border-top:1px solid var(--surface);">
                    <?php foreach ($reportMeta as $key => $meta): ?>
                        <a href="?report=<?php echo e($key); ?>"
                           class="btn btn-sm <?php echo $reportType === $key ? 'btn-primary' : 'btn-outline-secondary'; ?> d-flex align-items-center gap-1">
                            <i class="bi <?php echo $meta['icon']; ?>"></i>
                            <?php echo e(str_replace(' Reports', '', $meta['label'])); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Report Content -->
            <div class="glass-card p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h5 class="mb-0" style="font-family:var(--font-display);font-weight:700;">
                        <i class="bi <?php echo $reportMeta[$reportType]['icon'] ?? 'bi-file-bar-graph'; ?> me-2"></i>
                        <?php echo e($reportMeta[$reportType]['label'] ?? 'Report'); ?>
                    </h5>
                    <a href="?report=<?php echo e($reportType); ?>&export=csv"
                       class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                        <i class="bi bi-filetype-csv"></i> Download CSV
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <?php if ($reportType === 'residents'): ?>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Sex</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                    <th>Created</th>
                                <?php elseif ($reportType === 'projects'): ?>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Created</th>
                                <?php elseif ($reportType === 'financial'): ?>
                                    <th>Project</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Source</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                <?php elseif ($reportType === 'certificates'): ?>
                                    <th>ID</th>
                                    <th>Resident</th>
                                    <th>Document Number</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                <?php elseif ($reportType === 'applications'): ?>
                                    <th>ID</th>
                                    <th>Resident</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                <?php elseif ($reportType === 'attendance'): ?>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Location</th>
                                <?php elseif ($reportType === 'announcements'): ?>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Audience</th>
                                    <th>Date</th>
                                <?php elseif ($reportType === 'activity'): ?>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                <?php elseif ($reportType === 'audit'): ?>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $rowCount = 0;
                                switch ($reportType) {
                                    case 'residents':
                                        $rows = $pdo->query('SELECT * FROM residents ORDER BY full_name')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['full_name']); ?></strong></td>
                                                <td><?php echo e($row['sex'] ?? '-'); ?></td>
                                                <td><?php echo e($row['address'] ?? '-'); ?></td>
                                                <td><?php echo e($row['contact_number'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'projects':
                                        $rows = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['title']); ?></strong></td>
                                                <td><?php echo e($row['category'] ?? '-'); ?></td>
                                                <td><?php echo e(ucfirst($row['status'])); ?></td>
                                                <td><?php echo (int) $row['progress_percent']; ?>%</td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'financial':
                                        $rows = $pdo->query('SELECT p.title, pb.type, pb.amount, pb.source, pb.description, pb.created_at FROM project_budget pb LEFT JOIN projects p ON p.id = pb.project_id ORDER BY pb.created_at DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><strong><?php echo e($row['title'] ?? 'N/A'); ?></strong></td>
                                                <td><?php echo e(ucfirst($row['type'])); ?></td>
                                                <td>&#8369;<?php echo number_format($row['amount'], 2); ?></td>
                                                <td><?php echo e($row['source'] ?? '-'); ?></td>
                                                <td><?php echo e($row['description'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'certificates':
                                        $rows = $pdo->query('SELECT d.*, r.full_name FROM documents d LEFT JOIN residents r ON r.id = d.resident_id ORDER BY d.created_at DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['full_name'] ?? 'Unknown'); ?></strong></td>
                                                <td><?php echo e($row['document_number']); ?></td>
                                                <td><?php echo e($row['document_type']); ?></td>
                                                <td><?php echo e(ucfirst($row['status'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'applications':
                                        $rows = $pdo->query('SELECT a.*, r.full_name FROM applications a LEFT JOIN residents r ON r.id = a.resident_id ORDER BY a.created_at DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['full_name'] ?? 'Unknown'); ?></strong></td>
                                                <td><?php echo e($row['application_type']); ?></td>
                                                <td><?php echo e(ucfirst($row['priority'])); ?></td>
                                                <td><?php echo e(ucfirst($row['status'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'attendance':
                                        $rows = $pdo->query('SELECT * FROM agenda ORDER BY agenda_date DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['title']); ?></strong></td>
                                                <td><?php echo e($row['meeting_type'] ?? '-'); ?></td>
                                                <td><?php echo $row['agenda_date'] ? date('M d, Y', strtotime($row['agenda_date'])) : '-'; ?></td>
                                                <td><?php echo e($row['time_from'] ?? '-'); ?> &mdash; <?php echo e($row['time_to'] ?? '-'); ?></td>
                                                <td><?php echo e($row['location'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'announcements':
                                        $rows = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['title']); ?></strong></td>
                                                <td><?php echo e(ucfirst($row['type'] ?? '-')); ?></td>
                                                <td><?php echo e(ucfirst($row['priority'] ?? '-')); ?></td>
                                                <td><?php echo e(ucfirst($row['audience'] ?? '-')); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'activity':
                                        $rows = $pdo->query('SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['full_name'] ?? 'System'); ?></strong></td>
                                                <td><?php echo e($row['action']); ?></td>
                                                <td style="max-width:250px;"><?php echo e($row['details'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;

                                    case 'audit':
                                        $rows = $pdo->query('SELECT aul.*, u.full_name FROM audit_logs aul LEFT JOIN users u ON u.id = aul.user_id ORDER BY aul.created_at DESC')->fetchAll();
                                        $rowCount = count($rows);
                                        foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td><strong><?php echo e($row['full_name'] ?? 'System'); ?></strong></td>
                                                <td><?php echo e($row['action']); ?></td>
                                                <td style="max-width:250px;"><?php echo e($row['details'] ?? '-'); ?></td>
                                                <td><code style="font-size:0.8rem;"><?php echo e($row['ip_address'] ?? '-'); ?></code></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        break;
                                }
                            ?>

                            <?php if ($rowCount === 0) : ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-file-earmark-x"
                                           style="font-size:2rem;color:var(--text-low);display:block;margin-bottom:0.5rem;"></i>
                                        <span style="color:var(--text-low);">No records found for this report.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($rowCount > 0) : ?>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3"
                         style="border-top:1px solid var(--surface);">
                        <span style="color:var(--text-low);font-size:0.85rem;">
                            Showing <?php echo $rowCount; ?> record<?php echo $rowCount !== 1 ? 's' : ''; ?>
                        </span>
                        <a href="?report=<?php echo e($reportType); ?>&export=csv"
                           class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                            <i class="bi bi-download"></i> Export All as CSV
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>