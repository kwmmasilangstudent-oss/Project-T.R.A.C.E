<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$debugLogPath = __DIR__ . '/../logs/cctv_request.log';
@file_put_contents($debugLogPath, date('Y-m-d H:i:s') . ' REQUEST_START ' . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . ' ' . ($_SERVER['REQUEST_URI'] ?? '') . "\n", FILE_APPEND | LOCK_EX);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function ($e) {
    http_response_code(500);
    @file_put_contents(__DIR__ . '/../logs/cctv_request.log', date('Y-m-d H:i:s') . ' EXCEPTION ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
    echo '<pre style="white-space: pre-wrap; color: #b91c1c;">' . htmlspecialchars((string) $e) . '</pre>';
    exit;
});

require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
requireCsrf();

// includes/auth.php loads includes/functions.php, which sets a global exception handler.
// Restore our debug handler so we can see the real failure during development.
set_exception_handler(function ($e) {
    http_response_code(500);
    echo '<pre style="white-space: pre-wrap; color: #b91c1c;">' . htmlspecialchars((string) $e) . '</pre>';
    exit;
});

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

function debugCctv(string $message): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/cctv_debug.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
}

function ensureCctvSchema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS cctv_cameras (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        location VARCHAR(255) DEFAULT '',
        snapshot_url TEXT NOT NULL,
        camera_type VARCHAR(20) NOT NULL DEFAULT 'snapshot',
        username VARCHAR(255) DEFAULT NULL,
        password VARCHAR(255) DEFAULT NULL,
        is_enabled TINYINT(1) NOT NULL DEFAULT 1,
        last_status VARCHAR(20) NOT NULL DEFAULT 'unknown',
        last_checked_at DATETIME DEFAULT NULL,
        last_error TEXT DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = $pdo->query('SHOW COLUMNS FROM cctv_cameras')->fetchAll(PDO::FETCH_COLUMN);
    $columnDefinitions = [
        'camera_type' => "VARCHAR(20) NOT NULL DEFAULT 'snapshot'",
        'username' => 'VARCHAR(255) DEFAULT NULL',
        'password' => 'VARCHAR(255) DEFAULT NULL',
        'is_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'last_status' => "VARCHAR(20) NOT NULL DEFAULT 'unknown'",
        'last_checked_at' => 'DATETIME DEFAULT NULL',
        'last_error' => 'TEXT DEFAULT NULL',
    ];

    foreach ($columnDefinitions as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE cctv_cameras ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
}

try {
    ensureCctvSchema($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre style="white-space: pre-wrap; color: #b91c1c;">Schema initialization failed:\n' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugCctv('POST start');
    debugCctv('REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '')); 
    debugCctv('POST keys=' . implode(',', array_keys($_POST)));
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $snapshotUrl = trim($_POST['snapshot_url'] ?? '');
    $cameraType = trim($_POST['camera_type'] ?? 'snapshot');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
    $id = $_POST['id'] ?? null;

    debugCctv('name=' . $name . ' snapshotUrl=' . $snapshotUrl . ' cameraType=' . $cameraType . ' id=' . ($id ?? 'null'));

    if ($name && $snapshotUrl) {
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE cctv_cameras SET name = ?, location = ?, snapshot_url = ?, camera_type = ?, username = ?, password = ?, is_enabled = ? WHERE id = ?');
                $stmt->execute([$name, $location, $snapshotUrl, $cameraType, $username !== '' ? $username : null, $password !== '' ? $password : null, $isEnabled, $id]);
                $_SESSION['_flash_success'] = 'Camera updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO cctv_cameras (name, location, snapshot_url, camera_type, username, password, is_enabled) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $location, $snapshotUrl, $cameraType, $username !== '' ? $username : null, $password !== '' ? $password : null, $isEnabled]);
                $_SESSION['_flash_success'] = 'Camera added successfully.';
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo '<pre style="white-space: pre-wrap; color: #b91c1c;">Unable to save camera:\n' . htmlspecialchars($e->getMessage()) . '</pre>';
            exit;
        }
    } elseif ($name === '' || $snapshotUrl === '') {
        $_SESSION['_flash_error'] = 'Camera name and snapshot URL are required.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM cctv_cameras WHERE id = ?');
    $stmt->execute([(int) $_GET['delete']]);
    $_SESSION['_flash_success'] = 'Camera removed.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$cameras = [];
try {
    $cameras = $pdo->query('SELECT * FROM cctv_cameras ORDER BY sort_order ASC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $cameras = [];
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
                    <h2 class="mb-0">CCTV Live Monitoring</h2>
                    <p class="text-muted mb-0">Secure snapshot feeds with proxy handling and health status.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCameraModal">
                    <i class="bi bi-plus-lg"></i> Add Camera
                </button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if (empty($cameras)): ?>
                <div class="text-center py-5">
                    <div class="display-1 text-muted mb-3"><i class="bi bi-camera-video-off"></i></div>
                    <h4 class="text-muted">No Cameras Configured</h4>
                    <p class="text-muted">Add your first camera to start live monitoring.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-3">
                    <strong>Testing tip:</strong> open the camera card image or visit <code>/admin/cctv_proxy.php?id=CAMERA_ID</code> to verify whether your configured endpoint is reachable.
                </div>
                <div class="row g-3" id="cctvGrid">
                    <?php foreach ($cameras as $cam): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="glass-card h-100 position-relative">
                                <div class="position-relative bg-dark rounded overflow-hidden" style="aspect-ratio: 16/9;">
                                    <img src="<?php echo BASE_URL; ?>/admin/cctv_proxy.php?id=<?php echo (int) $cam['id']; ?>"
                                         class="cctv-snapshot w-100 h-100 object-fit-cover"
                                         alt="<?php echo e($cam['name']); ?>"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 9%22><rect fill=%22%23111%22 width=%2216%22 height=%229%22/><text fill=%22%23666%22 x=%228%22 y=%225%22 text-anchor=%22middle%22 font-size=%221%22>Offline</text></svg>';">
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge <?php echo (($cam['last_status'] ?? 'unknown') === 'online') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                            <i class="bi bi-circle-fill" style="font-size:6px;"></i> <?php echo e(ucfirst((string) ($cam['last_status'] ?? 'unknown'))); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo e($cam['name']); ?></h6>
                                        <small class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo e($cam['location'] ?: 'Unknown location'); ?></small>
                                        <div class="mt-1"><small class="text-muted cctv-timestamp" data-id="<?php echo (int) $cam['id']; ?>"><?php echo e($cam['last_checked_at'] ? 'Last check: ' . date('H:i:s', strtotime($cam['last_checked_at'])) : 'Waiting for first check'); ?></small></div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="?edit=<?php echo (int) $cam['id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        <a href="?delete=<?php echo (int) $cam['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this camera?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addCameraModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo isset($_GET['edit']) ? 'Edit' : 'Add'; ?> Camera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if (isset($_GET['edit'])): ?>
                        <?php
                        $editCam = null;
                        foreach ($cameras as $c) { if ($c['id'] == $_GET['edit']) { $editCam = $c; break; } }
                        ?>
                        <input type="hidden" name="id" value="<?php echo e($editCam['id'] ?? ''); ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Camera Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo e($editCam['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?php echo e($editCam['location'] ?? ''); ?>">
                    </div>
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Snapshot URL</label>
                        <input type="url" name="snapshot_url" class="form-control" required value="<?php echo e($editCam['snapshot_url'] ?? ''); ?>">
                        <div class="form-text">Example: http://192.168.1.100:80/snap.jpg or http://nvr/snapshot/1</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camera Type</label>
                        <select name="camera_type" class="form-select">
                            <option value="snapshot" <?php echo (($editCam['camera_type'] ?? 'snapshot') === 'snapshot') ? 'selected' : ''; ?>>Snapshot</option>
                            <option value="mjpeg" <?php echo (($editCam['camera_type'] ?? 'snapshot') === 'mjpeg') ? 'selected' : ''; ?>>MJPEG</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo e($editCam['username'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" value="<?php echo e($editCam['password'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" <?php echo !empty($editCam['is_enabled'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Enable camera</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Camera</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setInterval(function() {
        document.querySelectorAll('.cctv-snapshot').forEach(function(img) {
            var url = img.getAttribute('src');
            if (!url) return;
            img.src = url.split('?')[0] + '?t=' + Date.now();
        });
    }, 3000);

    setInterval(function() {
        document.querySelectorAll('.cctv-timestamp').forEach(function(el) {
            var cameraId = el.getAttribute('data-id');
            if (!cameraId) return;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo BASE_URL; ?>/admin/cctv_proxy.php?id=' + cameraId + '&t=' + Date.now(), true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    el.textContent = 'Updated ' + new Date().toLocaleTimeString();
                    el.classList.remove('text-danger');
                    el.classList.add('text-success');
                } else {
                    el.textContent = 'Connection failed';
                    el.classList.add('text-danger');
                }
            };
            xhr.onerror = function() {
                el.textContent = 'Offline';
                el.classList.add('text-danger');
            };
            xhr.send();
        });
    }, 5000);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
