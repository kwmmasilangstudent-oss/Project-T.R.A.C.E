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
    $settings = [
        'barangay_name' => trim($_POST['barangay_name'] ?? ''),
        'barangay_address' => trim($_POST['barangay_address'] ?? ''),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
    ];
    foreach ($settings as $key => $value) {
        $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute([$key, $value]);
    }

    if (isset($_FILES['barangay_logo']) && $_FILES['barangay_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'logo_' . uniqid() . '_' . basename($_FILES['barangay_logo']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['barangay_logo']['tmp_name'], $targetPath)) {
            $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['barangay_logo', 'assets/uploads/' . $fileName]);
        }
    }

    if (isset($_FILES['officials_signature']) && $_FILES['officials_signature']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'signature_' . uniqid() . '_' . basename($_FILES['officials_signature']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['officials_signature']['tmp_name'], $targetPath)) {
            $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['officials_signature', 'assets/uploads/' . $fileName]);
        }
    }

    logAudit('update_settings', 'Updated system settings');
    $_SESSION['_flash_success'] = 'Settings updated successfully.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$settings = [];
try {
    $rows = $pdo->query('SELECT key_name, key_value FROM settings')->fetchAll();
    foreach ($rows as $row) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (Throwable $e) {
    $settings = [];
}

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
                    <h3 class="mb-1">System Settings</h3>
                    <p class="text-muted-glass mb-0">Manage barangay information, appearance, notifications, and system preferences.</p>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($success)) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
                            style="filter:invert(1) grayscale(100%) brightness(200%)"></button>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <!-- Barangay Information -->
                <div class="glass-card p-3 p-md-4 mb-4">
                    <h5 class="mb-3" style="font-family:var(--font-display);font-weight:700;">
                        <i class="bi bi-building me-2"></i>Barangay Information
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Barangay Name</label>
                            <input type="text" name="barangay_name" class="form-control"
                                   placeholder="e.g. Barangay San Isidro"
                                   value="<?php echo e($settings['barangay_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="barangay_address" class="form-control"
                                   placeholder="e.g. San Isidro, Nueva Ecija"
                                   value="<?php echo e($settings['barangay_address'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barangay Logo</label>
                            <?php if (!empty($settings['barangay_logo'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo asset($settings['barangay_logo']); ?>"
                                         alt="Logo"
                                         style="height:60px;width:60px;object-fit:contain;border-radius:8px;
                                                border:1px solid var(--surface);padding:4px;background:var(--surface);">
                                    <small class="text-muted d-block mt-1">Current logo</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="barangay_logo" class="form-control" accept="image/*">
                            <small class="text-muted">Recommended: square image, at least 200x200px.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Officials Signature</label>
                            <?php if (!empty($settings['officials_signature'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo asset($settings['officials_signature']); ?>"
                                         alt="Signature"
                                         style="height:60px;object-fit:contain;border-radius:8px;
                                                border:1px solid var(--surface);padding:4px;background:var(--surface);">
                                    <small class="text-muted d-block mt-1">Current signature</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="officials_signature" class="form-control" accept="image/*">
                            <small class="text-muted">Used in official document generation.</small>
                        </div>
                    </div>
                </div>

                <!-- System Preferences -->
                <div class="glass-card p-3 p-md-4 mb-4">
                    <h5 class="mb-3" style="font-family:var(--font-display);font-weight:700;">
                        <i class="bi bi-gear me-2"></i>System Preferences
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                       id="maintenance_mode"
                                       <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="d-flex justify-content-end mb-4">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="bi bi-check-lg"></i> Save All Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>