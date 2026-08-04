<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

$sections = [
    'hero' => 'Hero Section',
    'mission' => 'Mission',
    'vision' => 'Vision',
    'objectives' => 'Objectives',
    'history' => 'History',
    'services' => 'Services',
    'contact' => 'Contact',
    'footer' => 'Footer'
];

$sectionIcons = [
    'hero' => 'bi-image',
    'mission' => 'bi-bullseye',
    'vision' => 'bi-eye',
    'objectives' => 'bi-list-check',
    'history' => 'bi-clock-history',
    'services' => 'bi-grid-1x2',
    'contact' => 'bi-telephone',
    'footer' => 'bi-layout-text-window-reverse'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_text') {
        foreach ($sections as $key => $label) {
            $content = trim($_POST[$key] ?? '');
            $stmt = $pdo->prepare('INSERT INTO landing_content (section_name, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = VALUES(content)');
            $stmt->execute([$key, $content]);
        }
        logAudit('update_landing_content', 'Updated landing page text content');
        $_SESSION['_flash_success'] = 'Landing content updated successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif ($action === 'upload_hero') {
        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'hero_' . uniqid() . '_' . basename($_FILES['hero_image']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $targetPath)) {
                $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['hero_background', 'assets/uploads/' . $fileName]);
                logAudit('upload_hero_image', 'Uploaded hero background image');
                $_SESSION['_flash_success'] = 'Hero background uploaded successfully.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $_SESSION['_flash_error'] = 'Failed to upload image.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        } else {
            $_SESSION['_flash_error'] = 'Please select an image file.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'remove_hero') {
        $stmt = $pdo->prepare('SELECT key_value FROM settings WHERE key_name = ? LIMIT 1');
        $stmt->execute(['hero_background']);
        $row = $stmt->fetch();
        if ($row && !empty($row['key_value'])) {
            $path = __DIR__ . '/../' . $row['key_value'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $pdo->prepare('UPDATE settings SET key_value = "" WHERE key_name = ?')->execute(['hero_background']);
        logAudit('remove_hero_image', 'Removed hero background image');
        $_SESSION['_flash_success'] = 'Hero background removed.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$contents = [];
foreach ($sections as $key => $label) {
    $stmt = $pdo->prepare('SELECT content FROM landing_content WHERE section_name = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    $contents[$key] = $row['content'] ?? '';
}

$heroBackground = getSetting('hero_background', '');
$barangayName = getSetting('barangay_name', 'Barangay Tumalaytay');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
    :root {
        --lc-primary: #2f7bff;
        --lc-primary-dark: #1e63e0;
        --lc-amber: #f2b544;
        --lc-red: #ef5a5a;
        --lc-green: #35d18f;
        --lc-text: #eef2f9;
        --lc-text-soft: #a9b4c7;
        --lc-border: rgba(255, 255, 255, 0.09);
        --lc-card-bg: linear-gradient(160deg, rgba(30, 45, 70, 0.75) 0%, rgba(18, 30, 50, 0.75) 100%);
        --lc-radius: 16px;
        --lc-shadow: 0 8px 28px rgba(0, 0, 0, 0.28);
    }

    .lc-page-header {
        background: transparent;
        padding: 0.25rem 0 1.5rem 0;
    }

    .lc-page-header h3 {
        font-weight: 700;
        letter-spacing: -0.01em;
        color: var(--lc-text);
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.6rem;
    }

    .lc-page-header p {
        margin-bottom: 0;
        font-size: 0.925rem;
        color: var(--lc-text-soft);
    }

    .lc-badge-cms {
        background: rgba(47, 123, 255, 0.15);
        color: #7db0ff;
        border: 1px solid rgba(47, 123, 255, 0.35);
        padding: 0.45rem 0.95rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .glass-card {
        background: var(--lc-card-bg);
        border: 1px solid var(--lc-border);
        border-radius: var(--lc-radius);
        box-shadow: var(--lc-shadow);
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    .glass-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--lc-primary), #7c5cff, var(--lc-green));
        opacity: 0.85;
    }

    .lc-card-title {
        font-weight: 700;
        color: var(--lc-text);
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin-bottom: 1.25rem !important;
        font-size: 1.05rem;
    }

    .lc-card-title i {
        color: var(--lc-primary);
        font-size: 1.15rem;
    }

    .lc-hero-preview {
        width: 100%;
        height: 170px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid var(--lc-border);
        box-shadow: var(--lc-shadow);
    }

    .lc-hero-placeholder {
        height: 170px;
        border: 1.5px dashed rgba(255, 255, 255, 0.18);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.03);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        color: var(--lc-text-soft);
    }

    .lc-hero-placeholder i {
        font-size: 1.75rem;
        opacity: 0.5;
    }

    .lc-hero-placeholder span {
        font-size: 0.85rem;
        font-weight: 500;
    }

    .lc-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
    }

    .lc-status-active {
        background: rgba(53, 209, 143, 0.15);
        color: var(--lc-green);
        border: 1px solid rgba(53, 209, 143, 0.3);
    }

    .lc-status-inactive {
        background: rgba(255, 255, 255, 0.06);
        color: var(--lc-text-soft);
        border: 1px solid var(--lc-border);
    }

    .lc-status-pill i {
        font-size: 0.7rem;
    }

    .lc-field-label {
        font-weight: 600;
        color: var(--lc-text);
        font-size: 0.88rem;
        margin-bottom: 0.4rem;
    }

    .lc-divider-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--lc-text-soft);
        margin-bottom: 0.5rem;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.04);
        border-radius: 10px;
        border: 1px solid var(--lc-border);
        padding: 0.6rem 0.85rem;
        font-size: 0.9rem;
        color: var(--lc-text);
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .form-control::placeholder {
        color: var(--lc-text-soft);
        opacity: 0.8;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.07);
        border-color: var(--lc-primary);
        color: var(--lc-text);
        box-shadow: 0 0 0 4px rgba(47, 123, 255, 0.15);
    }

    textarea.form-control {
        resize: vertical;
        line-height: 1.5;
    }

    .lc-section-field {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--lc-border);
        border-radius: 12px;
        padding: 1rem 1.1rem;
        transition: border-color 0.15s ease, background 0.15s ease;
        height: 100%;
    }

    .lc-section-field:focus-within {
        border-color: var(--lc-primary);
        background: rgba(47, 123, 255, 0.05);
    }

    .lc-section-field .lc-field-label {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--lc-text);
    }

    .lc-section-field .lc-field-label i {
        color: var(--lc-primary);
        font-size: 0.95rem;
    }

    .lc-upload-dropzone {
        border: 1.5px dashed rgba(47, 123, 255, 0.4);
        background: rgba(47, 123, 255, 0.06);
        border-radius: 12px;
        padding: 0.9rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--lc-primary) 0%, var(--lc-primary-dark) 100%);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 0.6rem 1.4rem;
        box-shadow: 0 4px 14px rgba(47, 123, 255, 0.35);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        color: #fff;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(47, 123, 255, 0.45);
        color: #fff;
    }

    .btn-outline-danger {
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        border-width: 1.5px;
        border-color: rgba(239, 90, 90, 0.55);
        color: var(--lc-red);
        background: rgba(239, 90, 90, 0.06);
    }

    .btn-outline-danger:not(:disabled):hover {
        transform: translateY(-1px);
        background: rgba(239, 90, 90, 0.15);
        border-color: var(--lc-red);
        color: var(--lc-red);
    }

    .btn-outline-danger:disabled {
        opacity: 0.35;
    }

    .lc-preview-shell {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--lc-border);
        border-radius: 14px;
        padding: 1.5rem;
    }

    .lc-preview-block {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--lc-border);
        border-radius: 12px;
        padding: 1rem 1.2rem;
        height: 100%;
    }

    .lc-preview-block h6 {
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #7db0ff;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.6rem;
    }

    .lc-preview-block p {
        font-size: 0.9rem;
        color: var(--lc-text-soft);
        line-height: 1.55;
    }

    .lc-preview-empty {
        color: rgba(169, 180, 199, 0.55) !important;
        font-style: italic;
    }

    .alert {
        border-radius: 12px;
        border: 1px solid transparent;
        font-weight: 500;
        padding: 0.9rem 1.2rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .alert-success {
        background: rgba(53, 209, 143, 0.12);
        color: var(--lc-green);
        border-color: rgba(53, 209, 143, 0.3);
    }

    .alert-danger {
        background: rgba(239, 90, 90, 0.12);
        color: var(--lc-red);
        border-color: rgba(239, 90, 90, 0.3);
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 py-4">

            <div class="lc-page-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <div>
                    <h3><i class="bi bi-layout-wtf"></i>Landing Content Management</h3>
                    <p>Manage the public-facing content, hero image, and settings for the barangay website.</p>
                </div>
                <span class="lc-badge-cms"><i class="bi bi-stars"></i>CMS</span>
            </div>

            <?php if (!empty($success)) : ?>
                <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="glass-card p-4 mb-4">
                <h5 class="lc-card-title"><i class="bi bi-image"></i>Hero Background</h5>
                <div class="row g-4 align-items-stretch">
                    <div class="col-md-4">
                        <div class="lc-divider-label">Current Image</div>
                        <?php if (!empty($heroBackground)): ?>
                            <img src="<?php echo asset($heroBackground); ?>" alt="Hero background" class="lc-hero-preview">
                        <?php else: ?>
                            <div class="lc-hero-placeholder">
                                <i class="bi bi-image"></i>
                                <span>No hero background uploaded</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="lc-divider-label">Upload New</div>
                        <form method="post" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="upload_hero">
                            <div class="lc-upload-dropzone mb-3">
                                <label class="lc-field-label mb-2"><i class="bi bi-cloud-arrow-up me-1"></i>Choose an image file</label>
                                <input type="file" name="hero_image" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Upload</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="lc-divider-label">Status</div>
                        <form method="post" onsubmit="return confirm('Remove current hero background?')" class="d-flex flex-column h-100">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="remove_hero">
                            <div class="mb-3">
                                <?php if (!empty($heroBackground)): ?>
                                    <span class="lc-status-pill lc-status-active"><i class="bi bi-circle-fill"></i>Background active</span>
                                <?php else: ?>
                                    <span class="lc-status-pill lc-status-inactive"><i class="bi bi-circle-fill"></i>No background set</span>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100 mt-auto" <?php echo empty($heroBackground) ? 'disabled' : ''; ?>>
                                <i class="bi bi-trash3 me-1"></i>Remove Background
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4 mb-4">
                <h5 class="lc-card-title"><i class="bi bi-pencil-square"></i>Page Content</h5>
                <form method="post">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_text">
                    <div class="row g-3">
                        <?php foreach ($sections as $key => $label): ?>
                            <div class="col-md-6">
                                <div class="lc-section-field">
                                    <label class="lc-field-label">
                                        <i class="bi <?php echo e($sectionIcons[$key] ?? 'bi-card-text'); ?>"></i>
                                        <?php echo e($label); ?>
                                    </label>
                                    <textarea name="<?php echo e($key); ?>" class="form-control" rows="4" placeholder="Enter <?php echo e(strtolower($label)); ?> content..."><?php echo e($contents[$key]); ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Save Content</button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4">
                <h5 class="lc-card-title"><i class="bi bi-eye"></i>Live Preview</h5>
                <div class="lc-preview-shell">
                    <div class="row g-3">
                        <?php foreach ($sections as $key => $label):
                            $isFullWidth = in_array($key, ['hero', 'objectives', 'history', 'services', 'contact', 'footer'], true);
                            $colClass = $isFullWidth ? 'col-12' : 'col-md-6';
                            $value = $contents[$key];
                        ?>
                            <div class="<?php echo $colClass; ?>">
                                <div class="lc-preview-block">
                                    <h6><i class="bi <?php echo e($sectionIcons[$key] ?? 'bi-card-text'); ?>"></i><?php echo e($label); ?></h6>
                                    <?php if (trim($value) !== ''): ?>
                                        <p class="mb-0"><?php echo nl2br(e($value)); ?></p>
                                    <?php else: ?>
                                        <p class="mb-0 lc-preview-empty">No content added yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>