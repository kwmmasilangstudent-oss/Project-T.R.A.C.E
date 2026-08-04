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
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $meetingType = trim($_POST['meeting_type'] ?? '');
        $agendaDate = trim($_POST['agenda_date'] ?? '');
        $timeFrom = trim($_POST['time_from'] ?? '');
        $timeTo = trim($_POST['time_to'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $attendees = trim($_POST['attendees'] ?? '');
        $minutes = trim($_POST['minutes'] ?? '');
        $actionItems = trim($_POST['action_items'] ?? '');
        $status = trim($_POST['status'] ?? 'scheduled');
        $eventType = trim($_POST['event_type'] ?? 'meeting');
        $isScannable = !empty($_POST['is_scannable']) ? 1 : 0;
        $scanMode = trim($_POST['scan_mode'] ?? 'open');
        $expectedAttendees = (int) ($_POST['expected_attendees'] ?? 0);

        if (!$title) {
            $_SESSION['_flash_error'] = 'Title is required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO agenda (title, description, meeting_type, agenda_date, time_from, time_to, location, attendees, minutes, action_items, status, event_type, is_scannable, scan_mode, expected_attendees) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $description, $meetingType, $agendaDate ?: null, $timeFrom ?: null, $timeTo ?: null, $location, $attendees, $minutes, $actionItems, $status, $eventType, $isScannable, $scanMode, $expectedAttendees]);
            logAudit('create_agenda', 'Created agenda: ' . $title);
            $_SESSION['_flash_success'] = 'Agenda item created successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'update') {
        $agendaId = (int) ($_POST['agenda_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $meetingType = trim($_POST['meeting_type'] ?? '');
        $agendaDate = trim($_POST['agenda_date'] ?? '');
        $timeFrom = trim($_POST['time_from'] ?? '');
        $timeTo = trim($_POST['time_to'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $attendees = trim($_POST['attendees'] ?? '');
        $minutes = trim($_POST['minutes'] ?? '');
        $actionItems = trim($_POST['action_items'] ?? '');
        $status = trim($_POST['status'] ?? 'scheduled');
        $eventType = trim($_POST['event_type'] ?? 'meeting');
        $isScannable = !empty($_POST['is_scannable']) ? 1 : 0;
        $scanMode = trim($_POST['scan_mode'] ?? 'open');
        $expectedAttendees = (int) ($_POST['expected_attendees'] ?? 0);

        if ($agendaId <= 0 || !$title) {
            $_SESSION['_flash_error'] = 'Invalid agenda data.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('UPDATE agenda SET title = ?, description = ?, meeting_type = ?, agenda_date = ?, time_from = ?, time_to = ?, location = ?, attendees = ?, minutes = ?, action_items = ?, status = ?, event_type = ?, is_scannable = ?, scan_mode = ?, expected_attendees = ? WHERE id = ?');
            $stmt->execute([$title, $description, $meetingType, $agendaDate ?: null, $timeFrom ?: null, $timeTo ?: null, $location, $attendees, $minutes, $actionItems, $status, $eventType, $isScannable, $scanMode, $expectedAttendees, $agendaId]);
            logAudit('update_agenda', 'Updated agenda ID: ' . $agendaId);
            $_SESSION['_flash_success'] = 'Agenda updated successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete') {
        $agendaId = (int) ($_POST['agenda_id'] ?? 0);

        if ($agendaId <= 0) {
            $_SESSION['_flash_error'] = 'Invalid agenda item.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('DELETE FROM agenda WHERE id = ?');
            $stmt->execute([$agendaId]);
            logAudit('delete_agenda', 'Deleted agenda ID: ' . $agendaId);
            $_SESSION['_flash_success'] = 'Agenda deleted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'update_status') {
        $agendaId = (int) ($_POST['agenda_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($agendaId > 0 && $status) {
            $stmt = $pdo->prepare('UPDATE agenda SET status = ? WHERE id = ?');
            $stmt->execute([$status, $agendaId]);
            logAudit('update_agenda_status', 'Updated agenda ID: ' . $agendaId . ' to ' . $status);
            $_SESSION['_flash_success'] = 'Status updated.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['meeting_type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = '';
$params = [];

if ($search) {
    $where .= ' AND (title LIKE ? OR description LIKE ? OR location LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($typeFilter) {
    $where .= ' AND meeting_type = ?';
    $params[] = $typeFilter;
}
if ($statusFilter) {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
}

$paginator = paginate(
    'SELECT COUNT(*) FROM agenda WHERE 1=1' . $where,
    $params,
    'SELECT * FROM agenda WHERE 1=1' . $where . ' ORDER BY agenda_date DESC, created_at DESC',
    $params
);
$agendaItems = $paginator['data'];

$statsQuery = 'SELECT 
    COUNT(*) as total,
    SUM(status = "scheduled") as scheduled,
    SUM(status = "ongoing") as ongoing,
    SUM(status = "completed") as completed,
    SUM(status = "cancelled") as cancelled
    FROM agenda';
$stats = $pdo->query($statsQuery)->fetch();

$meetingTypes = $pdo->query('SELECT DISTINCT meeting_type FROM agenda WHERE meeting_type IS NOT NULL AND meeting_type != "" ORDER BY meeting_type')->fetchAll();

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
                    <h3>Agenda Management</h3>
                    <p class="text-muted">Plan meetings, schedules, agenda items, meeting minutes, and action items.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary">Total: <?php echo (int) ($stats['total'] ?? 0); ?></span>
                    <span class="badge bg-info-subtle text-info">Scheduled: <?php echo (int) ($stats['scheduled'] ?? 0); ?></span>
                    <span class="badge bg-warning-subtle text-warning">Ongoing: <?php echo (int) ($stats['ongoing'] ?? 0); ?></span>
                    <span class="badge bg-success-subtle text-success">Completed: <?php echo (int) ($stats['completed'] ?? 0); ?></span>
                </div>
            </div>
            <?php if (!empty($success)) : ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <?php if (!empty($error)) : ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

            <div class="glass-card p-4 mb-4">
                <h5 class="mb-3">Create Agenda Item</h5>
                <form method="post" class="row g-3">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-4">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Meeting Type</label>
                        <select name="meeting_type" class="form-select">
                            <option value="Regular">Regular</option>
                            <option value="Special">Special</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Committee">Committee</option>
                            <option value="Assembly">Assembly</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="scheduled">Scheduled</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="agenda_date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time From</label>
                        <input type="time" name="time_from" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time To</label>
                        <input type="time" name="time_to" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Event Type</label>
                        <select name="event_type" class="form-select">
                            <option value="meeting">Meeting</option>
                            <option value="assembly">Assembly</option>
                            <option value="health">Health Mission</option>
                            <option value="community">Community</option>
                            <option value="education">Education</option>
                            <option value="sports">Sports</option>
                            <option value="livelihood">Livelihood</option>
                            <option value="celebration">Celebration</option>
                            <option value="emergency">Emergency</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">QR Check-in</label>
                        <select name="is_scannable" class="form-select">
                            <option value="0">Disabled</option>
                            <option value="1">Enabled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Scan Mode</label>
                        <select name="scan_mode" class="form-select">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="invited">Invited Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expected Attendees</label>
                        <input type="number" name="expected_attendees" class="form-control" placeholder="0" min="0" value="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Attendees</label>
                        <textarea name="attendees" class="form-control" rows="2" placeholder="List of attendees"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Action Items</label>
                        <textarea name="action_items" class="form-control" rows="2" placeholder="Tasks and deadlines"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Minutes</label>
                        <textarea name="minutes" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Save Agenda</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search agenda..." value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Meeting Type</label>
                        <select name="meeting_type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($meetingTypes as $type): ?>
                                <option value="<?php echo e($type['meeting_type']); ?>" <?php echo $typeFilter === $type['meeting_type'] ? 'selected' : ''; ?>><?php echo e($type['meeting_type']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="ongoing" <?php echo $statusFilter === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4">
                <h5 class="mb-3">Agenda Items</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($agendaItems as $item): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <strong><?php echo e($item['title']); ?></strong>
                                        <span class="badge bg-secondary-subtle text-secondary"><?php echo e($item['meeting_type'] ?? 'Meeting'); ?></span>
                                        <span class="badge bg-<?php echo match($item['status']) { 'scheduled' => 'primary', 'ongoing' => 'info', 'completed' => 'success', 'cancelled' => 'danger', default => 'secondary' }; ?>-subtle text-<?php echo match($item['status']) { 'scheduled' => 'primary', 'ongoing' => 'info', 'completed' => 'success', 'cancelled' => 'danger', default => 'secondary' }; ?>"><?php echo e($item['status']); ?></span>
                                    </div>
                                    <p class="mb-1 text-muted"><?php echo e($item['description']); ?></p>
                                    <?php if ($item['location']): ?>
                                        <small class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo e($item['location']); ?></small><br>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?php if ($item['agenda_date']): ?>
                                            <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($item['agenda_date'])); ?>
                                            <?php echo $item['time_from'] ? '• ' . date('g:i A', strtotime($item['time_from'])) : ''; ?>
                                            <?php echo $item['time_to'] ? ' - ' . date('g:i A', strtotime($item['time_to'])) : ''; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item['event_type']) && $item['event_type'] !== 'meeting'): ?>
                                            &nbsp;<span class="badge bg-secondary-subtle text-secondary"><?php echo e(ucfirst($item['event_type'])); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['is_scannable'])): ?>
                                            &nbsp;<span class="badge bg-info-subtle text-info"><i class="bi bi-upc-scan"></i> QR</span>
                                        <?php endif; ?>
                                        <?php if ((int) ($item['expected_attendees'] ?? 0) > 0): ?>
                                            &nbsp;<span class="badge bg-primary-subtle text-primary"><i class="bi bi-people"></i> <?php echo (int) $item['expected_attendees']; ?> expected</span>
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($item['attendees']): ?>
                                        <br><small class="text-info"><strong>Attendees:</strong> <?php echo nl2br(e($item['attendees'])); ?></small>
                                    <?php endif; ?>
                                    <?php if ($item['action_items']): ?>
                                        <br><small class="text-warning"><strong>Action Items:</strong> <?php echo nl2br(e($item['action_items'])); ?></small>
                                    <?php endif; ?>
                                    <?php if ($item['minutes']): ?>
                                        <br><small class="text-success"><strong>Minutes:</strong> <?php echo nl2br(e($item['minutes'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex flex-column gap-1 ms-3">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo (int) $item['id']; ?>">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger" data-agenda-id="<?php echo (int) $item['id']; ?>" data-agenda-title="<?php echo e($item['title']); ?>" id="deleteBtn<?php echo (int) $item['id']; ?>">Delete</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($agendaItems)) : ?>
                        <li class="list-group-item text-center text-muted py-4">No agenda items found.</li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($agendaItems)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php foreach ($agendaItems as $item): ?>
    <div class="modal fade" id="editModal<?php echo (int) $item['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Agenda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <?php echo csrfField(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="agenda_id" value="<?php echo (int) $item['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo e($item['title']); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Meeting Type</label>
                                <select name="meeting_type" class="form-select">
                                    <?php foreach (['Regular', 'Special', 'Emergency', 'Committee', 'Assembly'] as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo $item['meeting_type'] === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['scheduled', 'ongoing', 'completed', 'cancelled'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $item['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="agenda_date" class="form-control" value="<?php echo e($item['agenda_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Time From</label>
                                <input type="time" name="time_from" class="form-control" value="<?php echo e($item['time_from'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Time To</label>
                                <input type="time" name="time_to" class="form-control" value="<?php echo e($item['time_to'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" value="<?php echo e($item['location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Event Type</label>
                                <select name="event_type" class="form-select">
                                    <?php foreach (['meeting', 'assembly', 'health', 'community', 'education', 'sports', 'livelihood', 'celebration', 'emergency', 'general'] as $et): ?>
                                        <option value="<?php echo $et; ?>" <?php echo ($item['event_type'] ?? 'meeting') === $et ? 'selected' : ''; ?>><?php echo ucfirst($et); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">QR Check-in</label>
                                <select name="is_scannable" class="form-select">
                                    <option value="0" <?php echo empty($item['is_scannable']) ? 'selected' : ''; ?>>Disabled</option>
                                    <option value="1" <?php echo !empty($item['is_scannable']) ? 'selected' : ''; ?>>Enabled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Scan Mode</label>
                                <select name="scan_mode" class="form-select">
                                    <?php foreach (['open', 'closed', 'invited'] as $sm): ?>
                                        <option value="<?php echo $sm; ?>" <?php echo ($item['scan_mode'] ?? 'open') === $sm ? 'selected' : ''; ?>><?php echo ucfirst($sm); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Expected Attendees</label>
                                <input type="number" name="expected_attendees" class="form-control" value="<?php echo (int) ($item['expected_attendees'] ?? 0); ?>" min="0">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"><?php echo e($item['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Attendees</label>
                                <textarea name="attendees" class="form-control" rows="2"><?php echo e($item['attendees'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Action Items</label>
                                <textarea name="action_items" class="form-control" rows="2"><?php echo e($item['action_items'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Minutes</label>
                                <textarea name="minutes" class="form-control" rows="3"><?php echo e($item['minutes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
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
                <h3 class="delete-toast-title">Delete Agenda Item</h3>
            </div>
            <div class="delete-toast-message">
                <p>Are you sure you want to delete <span id="deleteToastTitle"></span>? This action cannot be undone.</p>
            </div>
            <div class="delete-toast-buttons">
                <button id="deleteToastCancel" class="btn btn-outline-secondary">Cancel</button>
                <button id="deleteToastConfirm" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toastOverlay = document.getElementById('deleteToastOverlay');
        const toastCancel = document.getElementById('deleteToastCancel');
        const toastConfirm = document.getElementById('deleteToastConfirm');
        const toastTitle = document.getElementById('deleteToastTitle');
        
        let pendingDeleteId = null;
        
        // Open delete toast when delete button is clicked
        document.querySelectorAll('[data-agenda-id]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const agendaId = this.getAttribute('data-agenda-id');
                const agendaTitle = this.getAttribute('data-agenda-title');
                
                pendingDeleteId = agendaId;
                toastTitle.textContent = agendaTitle;
                toastOverlay.classList.add('active');
            });
        });
        
        // Close toast when clicking cancel or overlay
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
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && toastOverlay.classList.contains('active')) {
                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
            }
        });
        
        // Handle confirm button click
        toastConfirm.addEventListener('click', function() {
            if (pendingDeleteId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'agenda_id';
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

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[type="time"]').forEach(function(input) {
            if (input.dataset.ampmEnhanced) return;
            input.dataset.ampmEnhanced = '1';
            var select = document.createElement('select');
            select.className = 'form-select';
            select.style.cssText = 'width:auto;display:inline-block;padding:.375rem 1.75rem .375rem .75rem;font-size:.9375rem;line-height:1.5;border-radius:.375rem;margin-left:4px;vertical-align:middle;';
            select.innerHTML = '<option value="AM">AM</option><option value="PM">PM</option>';
            input.parentNode.insertBefore(select, input.nextSibling);
            function syncAmPmFromInput() {
                var val = input.value;
                if (!val) { select.value = 'AM'; return; }
                var hour = parseInt(val.split(':')[0], 10);
                if (isNaN(hour)) { select.value = 'AM'; return; }
                select.value = hour >= 12 ? 'PM' : 'AM';
            }
            function syncInputFromAmPm() {
                var val = input.value;
                if (!val) return;
                var parts = val.split(':');
                var hour = parseInt(parts[0], 10);
                if (isNaN(hour)) return;
                var isPM = select.value === 'PM';
                if (isPM && hour < 12) hour += 12;
                if (!isPM && hour === 12) hour = 0;
                var newVal = hour.toString().padStart(2, '0') + ':' + parts[1];
                if (newVal !== input.value) { input.value = newVal; }
            }
            input.addEventListener('change', syncAmPmFromInput);
            select.addEventListener('change', syncInputFromAmPm);
            syncAmPmFromInput();
        });
    });
</script>
