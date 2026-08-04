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

    if ($action === 'backup') {
        try {
            $backupDir = __DIR__ . '/../assets/uploads/backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }

            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $sql = '';
            foreach ($tables as $table) {
                $create = $pdo->query('SHOW CREATE TABLE ' . $table)->fetch();
                $sql .= "\n\n" . $create['Create Table'] . ";\n\n";

                $rows = $pdo->query('SELECT * FROM ' . $table)->fetchAll();
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        if ($value === null) return 'NULL';
                        return $pdo->quote($value);
                    }, $row);
                    $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
            }

            $fileName = 'trace_db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            file_put_contents($backupDir . $fileName, $sql);

            $pdo->prepare('INSERT INTO system_logs (message) VALUES (?)')->execute(['Database backup created: ' . $fileName]);
            logAudit('backup', 'Database backup created: ' . $fileName);

            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . strlen($sql));
            echo $sql;
            exit;
        } catch (Throwable $e) {
            $_SESSION['_flash_error'] = 'Backup failed: ' . $e->getMessage();
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if ($action === 'restore' && isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $fileContent = file_get_contents($_FILES['restore_file']['tmp_name']);

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            $statements = array_filter(array_map('trim', explode(';', $fileContent)));
            foreach ($statements as $statement) {
                if ($statement) {
                    $pdo->exec($statement);
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            $pdo->prepare('INSERT INTO system_logs (message) VALUES (?)')->execute(['Database restored from file']);
            logAudit('restore', 'Database restored from file');

            $_SESSION['_flash_success'] = 'Database restored successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Throwable $e) {
            $_SESSION['_flash_error'] = 'Restore failed: ' . $e->getMessage();
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if ($action === 'delete_backup') {
        $file = basename($_POST['file'] ?? '');
        $backupDir = __DIR__ . '/../assets/uploads/backups/';
        $path = $backupDir . $file;
        if ($file && str_ends_with($file, '.sql') && file_exists($path)) {
            @unlink($path);
            logAudit('delete_backup', 'Deleted backup: ' . $file);
            $_SESSION['_flash_success'] = 'Backup deleted.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['_flash_error'] = 'File not found.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if ($action === 'generate_cron_key') {
        $key = bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES ('cron_key', ?) ON DUPLICATE KEY UPDATE key_value = ?")
            ->execute([$key, $key]);
        $_SESSION['_flash_success'] = 'Cron key generated. Script URL: ' . BASE_URL . '/cron_backup.php?key=' . $key;
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
    }

    if ($action === 'run_scheduled') {
        $result = runScheduledBackup();
        if ($result) {
            $_SESSION['_flash_success'] = 'Scheduled backup created: ' . $result;
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['_flash_error'] = 'Backup not needed yet (once per day) or failed.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$backups = [];
$backupDir = __DIR__ . '/../assets/uploads/backups/';
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = $file;
        }
    }
    rsort($backups);
}

$lastBackup = getSetting('last_scheduled_backup', '0');
$cronKey = getSetting('cron_key', '');
$cronUrl = $cronKey ? BASE_URL . '/cron_backup.php?key=' . $cronKey : '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 py-4">
            <h3>Backup & Restore</h3>
            <p class="text-muted">Create, download, and manage database backups.</p>

            <?php if ($success) : ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

            <div class="glass-card p-4 mb-4">
                <h5 class="mb-3">Create Backup</h5>
                <form method="post" class="row g-3">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="backup">
                    <div class="col-md-8">
                        <p class="text-muted">Download a full SQL backup of the database including all tables and data.</p>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Download Backup</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4 mb-4">
                <h5 class="mb-3">Scheduled Backups</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <?php if ($lastBackup !== '0'): ?>
                            <p class="mb-1"><strong>Last scheduled backup:</strong> <?php echo date('M d, Y h:i A', (int)$lastBackup); ?></p>
                        <?php else: ?>
                            <p class="mb-1"><strong>Last scheduled backup:</strong> Never</p>
                        <?php endif; ?>
                        <p class="text-muted">Automated backups run once per day. The last 10 are retained.</p>
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <form method="post" style="display:inline">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="run_scheduled">
                            <button type="submit" class="btn btn-outline-primary">Run Now</button>
                        </form>
                        <form method="post" style="display:inline">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="generate_cron_key">
                            <button type="submit" class="btn btn-outline-secondary"><?php echo $cronKey ? 'Regenerate Cron Key' : 'Generate Cron Key'; ?></button>
                        </form>
                    </div>
                    <?php if ($cronUrl): ?>
                    <div class="col-12">
                        <label class="form-label">Cron Command</label>
                        <code style="display:block;padding:10px;background:var(--card-bg, #f8f9fa);border-radius:6px;word-break:break-all;font-size:0.85rem;">
                            php <?php echo e(__DIR__ . '/../cron_backup.php'); ?><br>
                            <?php echo e($cronUrl); ?>
                        </code>
                        <small class="text-muted">Add to crontab (Linux) or Task Scheduler (Windows) to run daily.</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-card p-4 mb-4">
                <h5 class="mb-3">Restore Database</h5>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="restore">
                    <div class="col-md-6">
                        <label class="form-label">Select Backup File (.sql)</label>
                        <input type="file" name="restore_file" accept=".sql" class="form-control" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('This will replace all current data. Are you sure?')">Restore Database</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4">
                <h5 class="mb-3">Available Backups</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($backups as $backup): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo e($backup); ?></span>
                            <div class="d-flex align-items-center gap-3">
                                <small class="text-muted"><?php echo date('M d, Y h:i A', filemtime($backupDir . $backup)); ?></small>
                                <a href="<?php echo BASE_URL; ?>/assets/uploads/backups/<?php echo e($backup); ?>" class="btn btn-sm btn-outline-primary" download>Download</a>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this backup?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete_backup">
                                    <input type="hidden" name="file" value="<?php echo e($backup); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($backups)) : ?>
                        <li class="list-group-item text-center text-muted">No backups available.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
