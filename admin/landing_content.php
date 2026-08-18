<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin', 'secretary']);
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
    'footer' => 'Footer',
    

];

$sectionIcons = [
    'hero' => 'bi-image',
    'mission' => 'bi-bullseye',
    'vision' => 'bi-eye',
    'objectives' => 'bi-list-check',
    'history' => 'bi-clock-history',
    'services' => 'bi-grid-1x2',
    'contact' => 'bi-telephone',
    'footer' => 'bi-layout-text-window-reverse',
    
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_text') {
        $updatedSections = [];
        foreach ($sections as $key => $label) {
            $content = trim($_POST[$key] ?? '');
            $stmt = $pdo->prepare('INSERT INTO landing_content (section_name, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = VALUES(content)');
            $stmt->execute([$key, $content]);
            $updatedSections[] = $key;
        }
        logAudit('update_landing_content', 'Updated landing page sections: ' . implode(', ', $updatedSections));
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
    } elseif ($action === 'upload_achievements_image') {
        if (isset($_FILES['achievements_image']) && $_FILES['achievements_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'achievements_' . uniqid() . '_' . basename($_FILES['achievements_image']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['achievements_image']['tmp_name'], $targetPath)) {
                $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['achievements_image', 'assets/uploads/' . $fileName]);
                $description = trim($_POST['achievements_description'] ?? '');
                if ($description !== '') {
                    $pdo->prepare('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute(['achievements_description', $description]);
                }
                logAudit('upload_achievements_image', 'Uploaded achievements image with description');
                $_SESSION['_flash_success'] = 'Achievements image uploaded successfully.';
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
    } elseif ($action === 'remove_achievements_image') {
        $stmt = $pdo->prepare('SELECT key_value FROM settings WHERE key_name = ? LIMIT 1');
        $stmt->execute(['achievements_image']);
        $row = $stmt->fetch();
        if ($row && !empty($row['key_value'])) {
            $path = __DIR__ . '/../' . $row['key_value'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $pdo->prepare('UPDATE settings SET key_value = "" WHERE key_name = ?')->execute(['achievements_image']);
        $pdo->prepare('UPDATE settings SET key_value = "" WHERE key_name = ?')->execute(['achievements_description']);
        logAudit('remove_achievements_image', 'Removed achievements image and description');
        $_SESSION['_flash_success'] = 'Achievements image removed.';
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
$achievementsImage = getSetting('achievements_image', '');

$landingOfficials = [];
try {
    $stmt = $pdo->query('SELECT * FROM landing_officials ORDER BY FIELD(tier, "captain", "executive", "kagawad", "sk"), sort_order ASC');
    $landingOfficials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $landingOfficials = [];
}

$tierConfig = [
    'captain' => ['label' => 'Barangay Captain', 'color' => '#f2b544', 'colorBg' => 'rgba(242,181,68,0.12)', 'colorBdr' => 'rgba(242,181,68,0.3)', 'icon' => 'bi-award-fill'],
    'executive' => ['label' => 'Executive Officers', 'color' => '#2f7bff', 'colorBg' => 'rgba(47,123,255,0.12)', 'colorBdr' => 'rgba(47,123,255,0.3)', 'icon' => 'bi-person-workspace'],
    'kagawad' => ['label' => 'Sangguniang Barangay', 'color' => '#8b5cf6', 'colorBg' => 'rgba(139,92,246,0.12)', 'colorBdr' => 'rgba(139,92,246,0.3)', 'icon' => 'bi-people-fill'],
    'sk' => ['label' => 'Appointed & Youth Officials', 'color' => '#35d18f', 'colorBg' => 'rgba(53,209,143,0.12)', 'colorBdr' => 'rgba(53,209,143,0.3)', 'icon' => 'bi-shield-fill'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_official'])) {
    requireCsrf();
    $officialId = (int) ($_POST['official_id'] ?? 0);
    $officialName = trim($_POST['official_name'] ?? '');
    $positionTitle = trim($_POST['position_title'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $tier = trim($_POST['tier'] ?? 'kagawad');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $positionLabel = trim($_POST['position_label'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $committee = trim($_POST['committee'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($officialId <= 0 || !$officialName || !$positionTitle) {
        $_SESSION['_flash_error'] = 'Official name and position are required.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $tierLimits = ['captain' => 1, 'executive' => 2, 'kagawad' => 7, 'sk' => 3];
    if (isset($tierLimits[$tier])) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM landing_officials WHERE tier = ? AND id != ? AND is_active = 1');
        $countStmt->execute([$tier, $officialId]);
        $currentCount = (int) $countStmt->fetchColumn();
        if ($currentCount >= $tierLimits[$tier]) {
            $_SESSION['_flash_error'] = 'Maximum ' . $tierLimits[$tier] . ' active ' . $tier . '(s) allowed.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    $oldOfficial = $pdo->prepare('SELECT * FROM landing_officials WHERE id = ?');
    $oldOfficial->execute([$officialId]);
    $oldData = $oldOfficial->fetch(PDO::FETCH_ASSOC);

    $photoPath = $oldData['photo_path'] ?? null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'official_' . uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photoPath = 'assets/uploads/' . $fileName;
        }
    }

    $pdo->prepare('UPDATE landing_officials SET official_name = ?, position_title = ?, contact_number = ?, photo_path = ?, tier = ?, sort_order = ?, position_label = ?, email = ?, committee = ?, bio = ?, is_active = ? WHERE id = ?')->execute([$officialName, $positionTitle, $contactNumber, $photoPath, $tier, $sortOrder, $positionLabel, $email, $committee, $bio, $isActive, $officialId]);

    $newData = $pdo->prepare('SELECT * FROM landing_officials WHERE id = ?')->fetch(PDO::FETCH_ASSOC);
    $pdo->prepare('INSERT INTO landing_officials_history (official_id, action, old_values, new_values, changed_by) VALUES (?, ?, ?, ?, ?)')->execute([$officialId, 'update', json_encode($oldData), json_encode($newData), $_SESSION['user_id'] ?? 0]);

    logAudit('update_landing_official', 'Updated landing official ID: ' . $officialId);
    $_SESSION['_flash_success'] = 'Official updated successfully.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_official'])) {
    requireCsrf();
    $officialName = trim($_POST['official_name'] ?? '');
    $positionTitle = trim($_POST['position_title'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $tier = trim($_POST['tier'] ?? 'kagawad');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $positionLabel = trim($_POST['position_label'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $committee = trim($_POST['committee'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (!$officialName || !$positionTitle) {
        $_SESSION['_flash_error'] = 'Official name and position are required.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $tierLimits = ['captain' => 1, 'executive' => 2, 'kagawad' => 7, 'sk' => 3];
    if (isset($tierLimits[$tier])) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM landing_officials WHERE tier = ? AND is_active = 1');
        $countStmt->execute([$tier]);
        $currentCount = (int) $countStmt->fetchColumn();
        if ($currentCount >= $tierLimits[$tier]) {
            $_SESSION['_flash_error'] = 'Maximum ' . $tierLimits[$tier] . ' active ' . $tier . '(s) allowed.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'official_' . uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photoPath = 'assets/uploads/' . $fileName;
        }
    }

    $pdo->prepare('INSERT INTO landing_officials (official_name, position_title, contact_number, photo_path, tier, sort_order, position_label, email, committee, bio, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$officialName, $positionTitle, $contactNumber, $photoPath, $tier, $sortOrder, $positionLabel, $email, $committee, $bio, 1]);

    $newId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO landing_officials_history (official_id, action, old_values, new_values, changed_by) VALUES (?, ?, NULL, ?, ?)')->execute([$newId, 'create', json_encode(['id' => $newId, 'official_name' => $officialName, 'position_title' => $positionTitle]), $_SESSION['user_id'] ?? 0]);

    logAudit('create_landing_official', 'Created landing official: ' . $officialName);
    $_SESSION['_flash_success'] = 'Official added successfully.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if (isset($_GET['delete_official'])) {
    $officialId = (int) ($_GET['delete_official'] ?? 0);
    if ($officialId > 0) {
        $oldOfficial = $pdo->prepare('SELECT * FROM landing_officials WHERE id = ?');
        $oldOfficial->execute([$officialId]);
        $oldData = $oldOfficial->fetch(PDO::FETCH_ASSOC);

        $pdo->prepare('UPDATE landing_officials SET is_active = 0 WHERE id = ?')->execute([$officialId]);
        $pdo->prepare('INSERT INTO landing_officials_history (official_id, action, old_values, new_values, changed_by) VALUES (?, ?, ?, NULL, ?)')->execute([$officialId, 'soft_delete', json_encode($oldData), $_SESSION['user_id'] ?? 0]);

        logAudit('soft_delete_landing_official', 'Soft deleted landing official ID: ' . $officialId);
        $_SESSION['_flash_success'] = 'Official deactivated successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

if (isset($_GET['restore_official'])) {
    $officialId = (int) ($_GET['restore_official'] ?? 0);
    if ($officialId > 0) {
        $oldOfficial = $pdo->prepare('SELECT * FROM landing_officials WHERE id = ?');
        $oldOfficial->execute([$officialId]);
        $oldData = $oldOfficial->fetch(PDO::FETCH_ASSOC);

        $pdo->prepare('UPDATE landing_officials SET is_active = 1 WHERE id = ?')->execute([$officialId]);
        $pdo->prepare('INSERT INTO landing_officials_history (official_id, action, old_values, new_values, changed_by) VALUES (?, ?, NULL, ?, ?)')->execute([$officialId, 'restore', json_encode($oldData), $_SESSION['user_id'] ?? 0]);

        logAudit('restore_landing_official', 'Restored landing official ID: ' . $officialId);
        $_SESSION['_flash_success'] = 'Official restored successfully.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_officials'])) {
    requireCsrf();
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['_flash_error'] = 'Please select a CSV file to import.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $file = fopen($_FILES['import_file']['tmp_name'], 'r');
    if ($file === false) {
        $_SESSION['_flash_error'] = 'Failed to open uploaded file.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $imported = 0;
    $skipped = 0;
    $tierLimits = ['captain' => 1, 'executive' => 2, 'kagawad' => 7, 'sk' => 3];

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) < 7) {
            $skipped++;
            continue;
        }

        list($officialName, $positionTitle, $contactNumber, $photoPath, $tier, $sortOrder, $positionLabel, $email, $committee, $bio) = array_pad($row, 10, '');
        $officialName = trim($officialName);
        $positionTitle = trim($positionTitle);
        $tier = strtolower(trim($tier));
        $sortOrder = (int) $sortOrder;

        if (!in_array($tier, ['captain', 'executive', 'kagawad', 'sk'], true)) {
            $tier = 'kagawad';
        }

        if (!$officialName || !$positionTitle) {
            $skipped++;
            continue;
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM landing_officials WHERE tier = ? AND is_active = 1');
        $countStmt->execute([$tier]);
        $currentCount = (int) $countStmt->fetchColumn();
        if ($currentCount >= ($tierLimits[$tier] ?? 999)) {
            $skipped++;
            continue;
        }

        $pdo->prepare('INSERT INTO landing_officials (official_name, position_title, contact_number, photo_path, tier, sort_order, position_label, email, committee, bio, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$officialName, $positionTitle, $contactNumber, $photoPath, $tier, $sortOrder, $positionLabel, $email, $committee, $bio, 1]);
        $newId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO landing_officials_history (official_id, action, old_values, new_values, changed_by) VALUES (?, ?, NULL, ?, ?)')->execute([$newId, 'import', json_encode(['id' => $newId, 'official_name' => $officialName, 'position_title' => $positionTitle]), $_SESSION['user_id'] ?? 0]);
        $imported++;
    }

    fclose($file);
    logAudit('import_landing_officials', 'Imported ' . $imported . ' officials, skipped ' . $skipped);
    $_SESSION['_flash_success'] = 'Import completed. ' . $imported . ' officials imported, ' . $skipped . ' skipped.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if (isset($_GET['export_officials'])) {
    $exportOfficials = $pdo->query('SELECT * FROM landing_officials ORDER BY FIELD(tier, "captain", "executive", "kagawad", "sk"), sort_order ASC')->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="landing_officials_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['official_name', 'position_title', 'contact_number', 'photo_path', 'tier', 'sort_order', 'position_label', 'email', 'committee', 'bio', 'is_active']);

    foreach ($exportOfficials as $off) {
        fputcsv($output, [
            $off['official_name'],
            $off['position_title'],
            $off['contact_number'],
            $off['photo_path'],
            $off['tier'],
            $off['sort_order'],
            $off['position_label'],
            $off['email'],
            $off['committee'],
            $off['bio'],
            $off['is_active']
        ]);
    }

    fclose($output);
    exit;
}

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
                <h5 class="lc-card-title"><i class="bi bi-trophy"></i>Achievements Image</h5>
                <div class="row g-4 align-items-stretch">
                    <div class="col-md-4">
                        <div class="lc-divider-label">Current Image</div>
                        <?php if (!empty($achievementsImage)): ?>
                            <img src="<?php echo asset($achievementsImage); ?>" alt="Achievements image" class="lc-hero-preview">
                        <?php else: ?>
                            <div class="lc-hero-placeholder">
                                <i class="bi bi-trophy"></i>
                                <span>No achievements image uploaded</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="lc-divider-label">Upload New</div>
                        <form method="post" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="upload_achievements_image">
                            <div class="lc-upload-dropzone mb-3">
                                <label class="lc-field-label mb-2"><i class="bi bi-cloud-arrow-up me-1"></i>Choose an image file</label>
                                <input type="file" name="achievements_image" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="lc-field-label mb-2"><i class="bi bi-card-text me-1"></i>Description</label>
                                <textarea name="achievements_description" class="form-control" rows="3" placeholder="Enter a description for this achievements image..."><?php echo e(getSetting('achievements_description', '')); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Upload</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="lc-divider-label">Status</div>
                        <form method="post" onsubmit="return confirm('Remove current achievements image?')" class="d-flex flex-column h-100">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="remove_achievements_image">
                            <div class="mb-3">
                                <?php if (!empty($achievementsImage)): ?>
                                    <span class="lc-status-pill lc-status-active"><i class="bi bi-circle-fill"></i>Image active</span>
                                <?php else: ?>
                                    <span class="lc-status-pill lc-status-inactive"><i class="bi bi-circle-fill"></i>No image set</span>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100 mt-auto" <?php echo empty($achievementsImage) ? 'disabled' : ''; ?>>
                                <i class="bi bi-trash3 me-1"></i>Remove Image
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

            <div class="glass-card p-4 mb-4">
                <h5 class="lc-card-title"><i class="bi bi-people"></i>Officials Management</h5>
                <p style="color: var(--lc-text-soft); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Manage the officials displayed on the landing page. Changes are reflected immediately on the public site.
                </p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="?export_officials=1" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </a>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importOfficialModal">
                        <i class="bi bi-upload me-1"></i>Import CSV
                    </button>
                </div>

                <?php if (!empty($landingOfficials)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Tier</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($landingOfficials as $off): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($off['photo_path'])): ?>
                                                <img src="<?php echo asset($off['photo_path']); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                            <?php else: ?>
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-size:0.9rem;font-weight:700;background:var(--lc-primary);color:#fff;">
                                                    <?php echo e(strtoupper(mb_substr($off['official_name'], 0, 1))); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo e($off['official_name']); ?></strong></td>
                                        <td><?php echo e($off['position_title']); ?></td>
                                        <td>
                                            <?php $tc = $tierConfig[$off['tier']] ?? $tierConfig['kagawad']; ?>
                                            <span style="background:<?php echo $tc['colorBg']; ?>; color:<?php echo $tc['color']; ?>; border:1px solid <?php echo $tc['colorBdr']; ?>; padding:4px 10px; border-radius:999px; font-size:0.72rem; font-weight:600;">
                                                <i class="bi <?php echo $tc['icon']; ?>"></i> <?php echo e($tc['label']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo e($off['contact_number'] ?? '-'); ?></td>
                                        <td>
                                            <span class="lc-status-pill <?php echo $off['is_active'] ? 'lc-status-active' : 'lc-status-inactive'; ?>">
                                                <i class="bi bi-circle-fill"></i> <?php echo $off['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editOfficialModal<?php echo (int) $off['id']; ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <?php if ($off['is_active']): ?>
                                                    <a href="?delete_official=<?php echo (int) $off['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deactivate this official?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?restore_official=<?php echo (int) $off['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Restore this official?')">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4" style="color: var(--lc-text-soft);">
                        <i class="bi bi-person-x fs-1 mb-2 d-block"></i>
                        <p>No officials found. Add one below.</p>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfficialModal">
                        <i class="bi bi-plus-lg me-1"></i>Add Official
                    </button>
                </div>
            </div>

            <!-- Import CSV Modal -->
            <div class="modal fade" id="importOfficialModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Officials</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="import_officials" value="1">
                                <div class="mb-3">
                                    <label class="form-label">CSV File</label>
                                    <input type="file" name="import_file" class="form-control" accept=".csv" required>
                                    <small class="text-muted">Expected columns: official_name, position_title, contact_number, photo_path, tier, sort_order, position_label, email, committee, bio, is_active</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Official Modal -->
            <div class="modal fade" id="addOfficialModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Official</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="add_official" value="1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="official_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Position Title</label>
                                        <input type="text" name="position_title" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" name="contact_number" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tier</label>
                                        <select name="tier" class="form-control">
                                            <option value="captain">Barangay Captain</option>
                                            <option value="executive">Executive Officer</option>
                                            <option value="kagawad">Kagawad</option>
                                            <option value="sk">SK / Appointed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Position Label</label>
                                        <input type="text" name="position_label" class="form-control" placeholder="e.g. Punong Barangay">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Committee</label>
                                        <input type="text" name="committee" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Photo</label>
                                        <input type="file" name="photo" class="form-control" accept="image/*">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Bio</label>
                                        <textarea name="bio" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Add Official</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Official Modals -->
            <?php foreach ($landingOfficials as $off): ?>
                <div class="modal fade" id="editOfficialModal<?php echo (int) $off['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form method="post" enctype="multipart/form-data">
                                <?php echo csrfField(); ?>
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Official</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="update_official" value="1">
                                    <input type="hidden" name="official_id" value="<?php echo (int) $off['id']; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" name="official_name" class="form-control" value="<?php echo e($off['official_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Position Title</label>
                                            <input type="text" name="position_title" class="form-control" value="<?php echo e($off['position_title']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Contact Number</label>
                                            <input type="text" name="contact_number" class="form-control" value="<?php echo e($off['contact_number'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tier</label>
                                            <select name="tier" class="form-control">
                                                <option value="captain" <?php echo $off['tier'] === 'captain' ? 'selected' : ''; ?>>Barangay Captain</option>
                                                <option value="executive" <?php echo $off['tier'] === 'executive' ? 'selected' : ''; ?>>Executive Officer</option>
                                                <option value="kagawad" <?php echo $off['tier'] === 'kagawad' ? 'selected' : ''; ?>>Kagawad</option>
                                                <option value="sk" <?php echo $off['tier'] === 'sk' ? 'selected' : ''; ?>>SK / Appointed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Sort Order</label>
                                            <input type="number" name="sort_order" class="form-control" value="<?php echo (int) ($off['sort_order'] ?? 0); ?>" min="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Position Label</label>
                                            <input type="text" name="position_label" class="form-control" value="<?php echo e($off['position_label'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo e($off['email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Committee</label>
                                            <input type="text" name="committee" class="form-control" value="<?php echo e($off['committee'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Photo</label>
                                            <?php if (!empty($off['photo_path'])): ?>
                                                <div class="mb-2">
                                                    <img src="<?php echo asset($off['photo_path']); ?>" alt="Current photo" style="height:60px;width:60px;object-fit:cover;border-radius:50%;">
                                                    <small class="text-muted d-block mt-1">Current photo</small>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="photo" class="form-control" accept="image/*">
                                            <small class="text-muted">Leave blank to keep current photo.</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Bio</label>
                                            <textarea name="bio" class="form-control" rows="3"><?php echo e($off['bio'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive<?php echo (int) $off['id']; ?>" <?php echo $off['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="isActive<?php echo (int) $off['id']; ?>">Active on landing page</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

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
                                        <?php if (in_array($key, ['achievements', 'documentation'], true)): ?>
                                            <p class="mb-0"><?php echo renderLandingMarkdown($value); ?></p>
                                        <?php else: ?>
                                            <p class="mb-0"><?php echo nl2br(e($value)); ?></p>
                                        <?php endif; ?>
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