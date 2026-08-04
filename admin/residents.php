<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin', 'secretary']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

if (!defined('BASE_URL')) {
    define('BASE_URL', '/FinalTrace');
}

require_once __DIR__ . '/../migrations/run.php';

$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');
        $sex = trim($_POST['sex'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $householdNumber = trim($_POST['household_number'] ?? '');
        $civilStatus = trim($_POST['civil_status'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $residentType = trim($_POST['resident_type'] ?? 'regular');
        $userId = null;

        if (!$fullName) {
            $_SESSION['_flash_error'] = 'Full name is required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $pdo->prepare('INSERT INTO residents (full_name, birth_date, sex, address, contact_number, household_number, civil_status, occupation, education, emergency_contact, resident_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$fullName, $birthDate ?: null, $sex, $address, $contactNumber, $householdNumber, $civilStatus, $occupation, $education, $emergencyContact, $residentType]);
            $residentId = (int) $pdo->lastInsertId();

            $pdo->prepare('INSERT INTO personal_information (resident_id, civil_status, occupation, education) VALUES (?, ?, ?, ?)')
                ->execute([$residentId, $civilStatus, $occupation, $education]);

            if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/photos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $residentId . '.' . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);
                    $pdo->prepare('UPDATE residents SET photo_url = ? WHERE id = ?')
                        ->execute(['assets/uploads/photos/' . $filename, $residentId]);
                }
            }

            require_once __DIR__ . '/../includes/qr.php';
            $qrPath = saveQrCode($residentId, 'resident');
            $pdo->prepare('UPDATE residents SET qr_code_path = ? WHERE id = ?')->execute([$qrPath, $residentId]);

            logAudit('create_resident', 'Created resident: ' . $fullName);
            $_SESSION['_flash_success'] = 'Resident created successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'update') {
        $residentId = (int) ($_POST['resident_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');
        $sex = trim($_POST['sex'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $householdNumber = trim($_POST['household_number'] ?? '');
        $civilStatus = trim($_POST['civil_status'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $residentType = trim($_POST['resident_type'] ?? 'regular');

        if ($residentId <= 0 || !$fullName) {
            $_SESSION['_flash_error'] = 'Invalid resident data.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('UPDATE residents SET full_name = ?, birth_date = ?, sex = ?, address = ?, contact_number = ?, household_number = ?, civil_status = ?, occupation = ?, education = ?, emergency_contact = ?, resident_type = ? WHERE id = ?');
            $stmt->execute([$fullName, $birthDate ?: null, $sex, $address, $contactNumber, $householdNumber, $civilStatus, $occupation, $education, $emergencyContact, $residentType, $residentId]);

            require_once __DIR__ . '/../includes/qr.php';
            $qrPath = saveQrCode($residentId, 'resident');
            $pdo->prepare('UPDATE residents SET qr_code_path = ? WHERE id = ?')->execute([$qrPath, $residentId]);

            $check = $pdo->prepare('SELECT id FROM personal_information WHERE resident_id = ? LIMIT 1');
            $check->execute([$residentId]);
            if ($check->fetch()) {
                $pdo->prepare('UPDATE personal_information SET civil_status = ?, occupation = ?, education = ? WHERE resident_id = ?')->execute([$civilStatus, $occupation, $education, $residentId]);
            } else {
                $pdo->prepare('INSERT INTO personal_information (resident_id, civil_status, occupation, education) VALUES (?, ?, ?, ?)')->execute([$residentId, $civilStatus, $occupation, $education]);
            }

            logAudit('update_resident', 'Updated resident ID: ' . $residentId);
            $_SESSION['_flash_success'] = 'Resident updated successfully.';

            if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/photos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                foreach (glob($uploadDir . $residentId . '.*') as $old) { unlink($old); }
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $residentId . '.' . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);
                    $pdo->prepare('UPDATE residents SET photo_url = ? WHERE id = ?')
                        ->execute(['assets/uploads/photos/' . $filename, $residentId]);
                }
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete') {
        $residentId = (int) ($_POST['resident_id'] ?? 0);

        if ($residentId <= 0) {
            $_SESSION['_flash_error'] = 'Invalid resident.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('SELECT full_name FROM residents WHERE id = ? LIMIT 1');
            $stmt->execute([$residentId]);
            $residentName = $stmt->fetchColumn();
            
            $pdo->prepare('DELETE FROM personal_information WHERE resident_id = ?')->execute([$residentId]);
            foreach (glob(__DIR__ . '/../assets/uploads/photos/' . $residentId . '.*') as $old) { unlink($old); }
            $pdo->prepare('DELETE FROM residents WHERE id = ?')->execute([$residentId]);
            
            logAudit('delete_resident', 'Deleted resident ID: ' . $residentId);
            $_SESSION['_flash_success'] = 'Resident deleted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete_bulk') {
        $ids = $_POST['resident_ids'] ?? '';
        $idArray = array_filter(array_map('intval', explode(',', $ids)), fn($id) => $id > 0);

        if (empty($idArray)) {
            $_SESSION['_flash_error'] = 'No residents selected.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $placeholders = implode(',', array_fill(0, count($idArray), '?'));
            $pdo->prepare("DELETE FROM personal_information WHERE resident_id IN ($placeholders)")->execute($idArray);
            foreach ($idArray as $delId) {
                foreach (glob(__DIR__ . '/../assets/uploads/photos/' . $delId . '.*') as $old) { unlink($old); }
            }
            $pdo->prepare("DELETE FROM residents WHERE id IN ($placeholders)")->execute($idArray);
            logAudit('delete_residents_bulk', 'Deleted ' . count($idArray) . ' residents');
            $_SESSION['_flash_success'] = count($idArray) . ' residents deleted successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$sexFilter = $_GET['sex'] ?? '';
$civilStatusFilter = $_GET['civil_status'] ?? '';
$residentTypeFilter = $_GET['resident_type'] ?? '';

// Get statistics for filter badges
$stats = [];
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN resident_type = 'senior_citizen' THEN 1 ELSE 0 END) as senior_citizens,
    SUM(CASE WHEN resident_type = 'pwd' THEN 1 ELSE 0 END) as pwd_count,
    SUM(CASE WHEN resident_type = '4ps' THEN 1 ELSE 0 END) as _4ps_count,
    SUM(CASE WHEN civil_status = 'Single' THEN 1 ELSE 0 END) as single
FROM residents WHERE 1=1";

$params = [];
$types = '';

if ($search) {
    $statsQuery .= " AND (full_name LIKE ? OR address LIKE ? OR contact_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

if ($sexFilter) {
    $statsQuery .= " AND sex = ?";
    $params[] = $sexFilter;
    $types .= "s";
}

if ($civilStatusFilter) {
    $statsQuery .= " AND civil_status = ?";
    $params[] = $civilStatusFilter;
    $types .= "s";
}

if ($residentTypeFilter && $residentTypeFilter !== 'all') {
    $statsQuery .= " AND resident_type = ?";
    $params[] = $residentTypeFilter;
    $types .= "s";
}

if ($params) {
    $stmt = $pdo->prepare($statsQuery);
    $stmt->execute($params);
    $stats = $stmt->fetch();
} else {
    $stats = $pdo->query($statsQuery)->fetch();
}

// Get filtered residents
$residentsQuery = "SELECT r.*, pi.citizenship FROM residents r LEFT JOIN personal_information pi ON r.id = pi.resident_id WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $residentsQuery .= " AND (r.full_name LIKE ? OR r.address LIKE ? OR r.contact_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

if ($sexFilter) {
    $residentsQuery .= " AND r.sex = ?";
    $params[] = $sexFilter;
    $types .= "s";
}

if ($civilStatusFilter) {
    $residentsQuery .= " AND r.civil_status = ?";
    $params[] = $civilStatusFilter;
    $types .= "s";
}

if ($residentTypeFilter && $residentTypeFilter !== 'all') {
    $residentsQuery .= " AND r.resident_type = ?";
    $params[] = $residentTypeFilter;
    $types .= "s";
}

$residentsQuery .= " ORDER BY r.created_at DESC";

$whereClause = substr($residentsQuery, strpos($residentsQuery, 'WHERE 1=1') + 9, strpos($residentsQuery, ' ORDER BY') - (strpos($residentsQuery, 'WHERE 1=1') + 9));

$paginator = paginate(
    "SELECT COUNT(*) FROM residents r LEFT JOIN personal_information pi ON r.id = pi.resident_id WHERE 1=1" . $whereClause,
    $params,
    "SELECT r.*, pi.citizenship FROM residents r LEFT JOIN personal_information pi ON r.id = pi.resident_id WHERE 1=1" . $whereClause . " ORDER BY r.created_at DESC",
    $params
);
$residents = $paginator['data'];
?>
<?php
$pageTitle = 'Residents - Barangay Management System';
require_once __DIR__ . '/../includes/header.php';
?>
<style id="printStyle">
        .id-card-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            background: #f4f6f9;
            min-height: 350px;
        }
        .id-card {
            width: 3.375in;
            height: 2.125in;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12), 0 1px 3px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', Arial, sans-serif;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .id-card-watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0.04;
            font-size: 3in;
            font-weight: 900;
            color: #1e293b;
            line-height: 1;
            user-select: none;
        }
        .id-card-top {
            display: flex;
            align-items: center;
            padding: 0.12in 0.18in 0.08in;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8e 100%);
            color: #fff;
            gap: 0.12in;
        }
        .id-card-seal {
            width: 0.38in;
            height: 0.38in;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.16in;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .id-card-top-text {
            line-height: 1.2;
        }
        .id-card-top-text .municipality {
            font-size: 0.08in;
            font-weight: 400;
            opacity: 0.85;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .id-card-top-text .barangay-name {
            font-size: 0.16in;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .id-card-top-text .card-type {
            font-size: 0.07in;
            font-weight: 500;
            opacity: 0.75;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .id-card-body {
            flex: 1;
            display: flex;
            padding: 0.12in 0.18in;
            gap: 0.14in;
            align-items: center;
            z-index: 1;
        }
        .id-card-photo {
            width: 0.75in;
            height: 0.9in;
            position: relative;
            background: #fff;
            border: 2px solid rgba(16,185,129,0.30);
            border-radius: 8px;
            padding: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: visible;
        }
        .id-card-photo::before,
        .id-card-photo::after {
            content: '';
            position: absolute;
            width: 10px;
            height: 10px;
            border: 2px solid #10b981;
            pointer-events: none;
        }
        .id-card-photo::before {
            top: 3px; left: 3px;
            border-right: none; border-bottom: none;
            border-radius: 3px 0 0 0;
        }
        .id-card-photo::after {
            bottom: 3px; right: 3px;
            border-left: none; border-top: none;
            border-radius: 0 0 3px 0;
        }
        .id-card-photo .qr-corner-bl,
        .id-card-photo .qr-corner-tr {
            position: absolute;
            width: 10px;
            height: 10px;
            border: 2px solid #10b981;
            pointer-events: none;
        }
        .id-card-photo .qr-corner-bl {
            bottom: 3px; left: 3px;
            border-right: none; border-top: none;
            border-radius: 0 0 0 3px;
        }
        .id-card-photo .qr-corner-tr {
            top: 3px; right: 3px;
            border-left: none; border-bottom: none;
            border-radius: 0 3px 0 0;
        }
        .id-card-photo .qr-img {
            width: 0.58in;
            height: 0.58in;
            display: block;
            image-rendering: pixelated;
        }
        .id-card-photo .qr-label {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            font-size: 0.045in;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 1.5px 6px;
            border-radius: 8px;
            margin-top: 4px;
            white-space: nowrap;
            line-height: 1.3;
        }
        .id-card-info {
            flex: 1;
            min-width: 0;
        }
        .id-card-name {
            font-size: 0.14in;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.15;
            margin-bottom: 0.04in;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .id-card-detail {
            font-size: 0.085in;
            color: #475569;
            line-height: 1.4;
            display: flex;
            gap: 0.04in;
        }
        .id-card-detail .label {
            color: #94a3b8;
            min-width: 0.4in;
            flex-shrink: 0;
        }
        .id-card-detail .value {
            color: #1e293b;
            font-weight: 500;
        }
        .id-card-type-badge {
            display: inline-block;
            padding: 0.01in 0.06in;
            border-radius: 3px;
            font-size: 0.075in;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .id-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.06in 0.18in;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            z-index: 1;
        }
        .id-card-footer .issued {
            font-size: 0.07in;
            color: #94a3b8;
        }
        .id-card-footer .qr {
            position: relative;
            background: #fff;
            border: 1.5px solid rgba(16,185,129,0.30);
            border-radius: 6px;
            padding: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .id-card-footer .qr::before,
        .id-card-footer .qr::after {
            content: '';
            position: absolute;
            width: 7px;
            height: 7px;
            border: 2px solid #10b981;
            pointer-events: none;
        }
        .id-card-footer .qr::before {
            top: 2px; left: 2px;
            border-right: none; border-bottom: none;
            border-radius: 2px 0 0 0;
        }
        .id-card-footer .qr::after {
            bottom: 2px; right: 2px;
            border-left: none; border-top: none;
            border-radius: 0 0 2px 0;
        }
        .id-card-footer .qr .qr-corner-bl,
        .id-card-footer .qr .qr-corner-tr {
            position: absolute;
            width: 7px;
            height: 7px;
            border: 2px solid #10b981;
            pointer-events: none;
        }
        .id-card-footer .qr .qr-corner-bl {
            bottom: 2px; left: 2px;
            border-right: none; border-top: none;
            border-radius: 0 0 0 2px;
        }
        .id-card-footer .qr .qr-corner-tr {
            top: 2px; right: 2px;
            border-left: none; border-bottom: none;
            border-radius: 0 2px 0 0;
        }
        .id-card-footer .qr img {
            width: 0.5in;
            height: 0.5in;
            display: block;
            image-rendering: pixelated;
        }
        .id-card-footer .qr-label {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            font-size: 0.035in;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 1px 4px;
            border-radius: 6px;
            margin-top: 2px;
            white-space: nowrap;
            line-height: 1.3;
        }
        .id-card-footer .id-number {
            font-size: 0.07in;
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        @media print {
            @page {
                size: 3.375in 2.125in;
                margin: 0;
            }
            body * { visibility: hidden; }
            .id-card, .id-card * { visibility: visible; }
            .id-card {
                position: fixed;
                top: 0;
                left: 0;
                width: 3.375in;
                height: 2.125in;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
                border-radius: 0;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Residents</h1>
                    <div>
                        <!-- Search and Filter -->
                        <form method="get" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search name, address, contact..." value="<?php echo e($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select name="sex" class="form-select">
                                    <option value="">All Genders</option>
                                    <option value="Male" <?php echo $sexFilter === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $sexFilter === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="civil_status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <?php foreach (['Single', 'Married', 'Widowed', 'Separated'] as $status): ?>
                                    <option value="<?php echo e($status); ?>" <?php echo $civilStatusFilter === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="resident_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="regular" <?php echo $residentTypeFilter === 'regular' ? 'selected' : ''; ?>>Regular</option>
                                    <option value="senior_citizen" <?php echo $residentTypeFilter === 'senior_citizen' ? 'selected' : ''; ?>>Senior Citizen</option>
                                    <option value="pwd" <?php echo $residentTypeFilter === 'pwd' ? 'selected' : ''; ?>>PWD</option>
                                    <option value="4ps" <?php echo $residentTypeFilter === '4ps' ? 'selected' : ''; ?>>4Ps Beneficiary</option>
                                    <option value="indigent" <?php echo $residentTypeFilter === 'indigent' ? 'selected' : ''; ?>>Indigent</option>
                                    <option value="fisherfolk" <?php echo $residentTypeFilter === 'fisherfolk' ? 'selected' : ''; ?>>Fisherfolk</option>
                                    <option value="solo_parent" <?php echo $residentTypeFilter === 'solo_parent' ? 'selected' : ''; ?>>Solo Parent</option>
                                    <option value="farmer" <?php echo $residentTypeFilter === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                                    <option value="student" <?php echo $residentTypeFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="faculty" <?php echo $residentTypeFilter === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="glass-card p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Total Residents</h6>
                                    <p class="mb-0 fw-bold fs-4"><?php echo e($stats['total'] ?? 0); ?></p>
                                </div>
                                <div class="bg-primary rounded-circle p-2 text-white">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Senior Citizens</h6>
                                    <p class="mb-0 fw-bold fs-4"><?php echo e($stats['senior_citizens'] ?? 0); ?></p>
                                </div>
                                <div class="bg-warning rounded-circle p-2 text-dark">
                                    <i class="bi bi-person-lines-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">PWD</h6>
                                    <p class="mb-0 fw-bold fs-4"><?php echo e($stats['pwd_count'] ?? 0); ?></p>
                                </div>
                                <div class="bg-info rounded-circle p-2 text-white">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">4Ps Beneficiaries</h6>
                                    <p class="mb-0 fw-bold fs-4"><?php echo e($stats['_4ps_count'] ?? 0); ?></p>
                                </div>
                                <div class="bg-success rounded-circle p-2 text-white">
                                    <i class="bi bi-gift"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alerts -->
                <?php if (!empty($success)) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if (!empty($error)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo e($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Residents Table -->
                    <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Residents List</h5>
                        <div id="bulkActionBar" class="d-none">
                            <span id="selectedCount" class="me-2 text-muted">0 selected</span>
                            <button id="deleteSelectedBtn" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete Selected</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                    <th>Name</th>
                                    <th>Resident Type</th>
                                    <th>Birth Date</th>
                                    <th>Sex</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($residents)): ?>
                                    <?php foreach ($residents as $resident): ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input resident-check" value="<?php echo (int) $resident['id']; ?>"></td>
                                        <td><strong><?php echo e($resident['full_name']); ?></strong></td>
                                        <td>
                                            <?php 
                                                $bgColors = [
                                                    'senior_citizen' => 'rgba(255, 193, 7, 0.15)',
                                                    'pwd' => 'rgba(13, 202, 240, 0.15)',
                                                    '4ps' => 'rgba(40, 167, 69, 0.15)',
                                                    'indigent' => 'rgba(220, 53, 69, 0.15)',
                                                    'fisherfolk' => 'rgba(6, 182, 212, 0.15)',
                                                    'solo_parent' => 'rgba(168, 85, 247, 0.15)',
                                                    'farmer' => 'rgba(34, 197, 94, 0.15)',
                                                    'student' => 'rgba(59, 130, 246, 0.15)',
                                                    'faculty' => 'rgba(249, 115, 22, 0.15)',
                                                    'regular' => 'rgba(108, 117, 125, 0.15)'
                                                ];
                                                $fgColors = [
                                                    'senior_citizen' => '#ffc107',
                                                    'pwd' => '#0dcaf0',
                                                    '4ps' => '#28a745',
                                                    'indigent' => '#dc3545',
                                                    'fisherfolk' => '#06b6d4',
                                                    'solo_parent' => '#a855f7',
                                                    'farmer' => '#22c55e',
                                                    'student' => '#3b82f6',
                                                    'faculty' => '#f97316',
                                                    'regular' => '#6c757d'
                                                ];
                                                $labels = [
                                                    'senior_citizen' => 'Senior Citizen',
                                                    'pwd' => 'PWD',
                                                    '4ps' => '4Ps Beneficiary',
                                                    'indigent' => 'Indigent',
                                                    'fisherfolk' => 'Fisherfolk',
                                                    'solo_parent' => 'Solo Parent',
                                                    'farmer' => 'Farmer',
                                                    'student' => 'Student',
                                                    'faculty' => 'Faculty',
                                                    'regular' => 'Regular'
                                                ];
                                                $rtRaw = trim($resident['resident_type'] ?? 'regular');
                                                $rtTypes = array_filter(array_map('trim', explode(',', $rtRaw)));
                                                if (empty($rtTypes)) $rtTypes = ['regular'];
                                                foreach ($rtTypes as $rtVal):
                                            ?>
                                            <span class="badge" style="background: <?php echo $bgColors[$rtVal] ?? 'rgba(108, 117, 125, 0.15)'; ?>; color: <?php echo $fgColors[$rtVal] ?? '#6c757d'; ?>; margin-right: 4px;">
                                                <?php echo $labels[$rtVal] ?? $rtVal; ?>
                                            </span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><?php echo $resident['birth_date'] ? date('M d, Y', strtotime($resident['birth_date'])) : '-'; ?></td>
                                        <td><?php echo e($resident['sex'] ?? '-'); ?></td>
                                        <td><?php echo e($resident['address'] ?? '-'); ?></td>
                                        <td><?php echo e($resident['contact_number'] ?? '-'); ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button class="btn btn-sm btn-outline-primary me-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editModal<?php echo (int) $resident['id']; ?>">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-info me-1" title="View ID Card" data-bs-toggle="modal" data-bs-target="#idModal<?php echo (int) $resident['id']; ?>">
                                                    <i class="bi bi-card-id"></i> ID
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        data-resident-id="<?php echo (int) $resident['id']; ?>"
                                                        data-resident-name="<?php echo e($resident['full_name']); ?>"
                                                        id="deleteBtn<?php echo (int) $resident['id']; ?>">
                                                    <i class="bi bi-trash3"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="bi bi-people" style="font-size:2rem;color:var(--text-low);display:block;margin-bottom:0.5rem;"></i>
                                            <span style="color:var(--text-low);">No residents found matching your criteria.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($residents)): ?>
                    <div class="mt-3 d-flex justify-content-center">
                        <?php echo renderPagination($paginator); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Resident Modal -->
    <div class="modal fade" id="createResidentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Resident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control"
                                       placeholder="e.g. Juan Dela Cruz" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sex</label>
                                <select name="sex" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control"
                                       placeholder="e.g. Purok 1, Brgy. Sample">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                       placeholder="e.g. 09123456789">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Household #</label>
                                <input type="text" name="household_number" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Civil Status</label>
                                <select name="civil_status" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Education</label>
                                <input type="text" name="education" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Resident Type</label>
                                <select name="resident_type" class="form-select">
                                    <option value="regular">Regular Resident</option>
                                    <option value="senior_citizen">Senior Citizen</option>
                                    <option value="pwd">PWD</option>
                                    <option value="4ps">4Ps Beneficiary</option>
                                    <option value="indigent">Indigent</option>
                                    <option value="fisherfolk">Fisherfolk</option>
                                    <option value="solo_parent">Solo Parent</option>
                                    <option value="farmer">Farmer</option>
                                    <option value="student">Student</option>
                                    <option value="faculty">Faculty</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Save Resident
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Resident Modals -->
    <?php foreach ($residents as $resident): ?>
    <div class="modal fade" id="editModal<?php echo (int) $resident['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Resident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="resident_id" value="<?php echo (int) $resident['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?php echo e($resident['full_name']); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" class="form-control"
                                       value="<?php echo e($resident['birth_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                <label class="form-label">Sex</label>
                                <select name="sex" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo $resident['sex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $resident['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control"
                                       value="<?php echo e($resident['address'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                       value="<?php echo e($resident['contact_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Household #</label>
                                <input type="text" name="household_number" class="form-control"
                                       value="<?php echo e($resident['household_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Civil Status</label>
                                <select name="civil_status" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Single" <?php echo $resident['civil_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo $resident['civil_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Widowed" <?php echo $resident['civil_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="Separated" <?php echo $resident['civil_status'] === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" class="form-control"
                                       value="<?php echo e($resident['occupation'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Education</label>
                                <input type="text" name="education" class="form-control"
                                       value="<?php echo e($resident['education'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Resident Type</label>
                                <select name="resident_type" class="form-select">
                                    <option value="">Select</option>
                                    <option value="regular" <?php echo ($resident['resident_type'] ?? '') === 'regular' ? 'selected' : ''; ?>>Regular Resident</option>
                                    <option value="senior_citizen" <?php echo ($resident['resident_type'] ?? '') === 'senior_citizen' ? 'selected' : ''; ?>>Senior Citizen</option>
                                    <option value="pwd" <?php echo ($resident['resident_type'] ?? '') === 'pwd' ? 'selected' : ''; ?>>PWD</option>
                                    <option value="4ps" <?php echo ($resident['resident_type'] ?? '') === '4ps' ? 'selected' : ''; ?>>4Ps Beneficiary</option>
                                    <option value="indigent" <?php echo ($resident['resident_type'] ?? '') === 'indigent' ? 'selected' : ''; ?>>Indigent</option>
                                    <option value="fisherfolk" <?php echo ($resident['resident_type'] ?? '') === 'fisherfolk' ? 'selected' : ''; ?>>Fisherfolk</option>
                                    <option value="solo_parent" <?php echo ($resident['resident_type'] ?? '') === 'solo_parent' ? 'selected' : ''; ?>>Solo Parent</option>
                                    <option value="farmer" <?php echo ($resident['resident_type'] ?? '') === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                                    <option value="student" <?php echo ($resident['resident_type'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="faculty" <?php echo ($resident['resident_type'] ?? '') === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control"
                                       value="<?php echo e($resident['emergency_contact'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                <?php if (!empty($resident['photo_url'])): ?>
                                <small class="text-muted">Current: <a href="<?php echo BASE_URL . '/' . e($resident['photo_url']); ?>" target="_blank">view photo</a></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <!-- QR Code Modals -->
    <?php foreach ($residents as $resident): ?>
    <?php if (!empty($resident['qr_code_path'])): ?>
    <div class="modal fade" id="qrModal<?php echo (int) $resident['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-qr-code me-2"></i>QR Code - <?php echo htmlspecialchars($resident['full_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                <img src="<?php echo BASE_URL; ?>/includes/qr.php?type=resident&id=<?php echo (int) $resident['id']; ?>" 
                     alt="QR Code" 
                     style="width: 300px; height: 300px;">
                    <p class="mt-3 text-muted"><?php echo htmlspecialchars($resident['full_name']); ?> (ID: <?php echo (int) $resident['id']; ?>)</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="<?php echo BASE_URL; ?>/includes/qr.php?type=resident&id=<?php echo (int) $resident['id']; ?>" target="_blank" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Download QR
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    
    <!-- ID Card Modals -->
    <?php foreach ($residents as $resident): ?>
    <?php
        $rt = $resident['resident_type'] ?? 'regular';
        $typeLabels = ['regular'=>'Regular','senior_citizen'=>'Senior','pwd'=>'PWD','4ps'=>'4Ps','indigent'=>'Indigent','fisherfolk'=>'Fisherfolk','solo_parent'=>'Solo Parent','farmer'=>'Farmer','student'=>'Student','faculty'=>'Faculty'];
        $typeLabel = $typeLabels[$rt] ?? 'Regular';
        $birthDate = $resident['birth_date'] ?? '';
        $age = $birthDate ? (new DateTime())->diff(new DateTime($birthDate))->y : '-';
    ?>
    <div class="modal fade" id="idModal<?php echo (int) $resident['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-card-id me-2"></i>ID Card</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="id-card-wrap">
                        <div class="id-card" id="printCard<?php echo (int) $resident['id']; ?>">
                            <div class="id-card-watermark">BMS</div>
                            <div class="id-card-top">
                                <div class="id-card-seal">BMS</div>
                                <div class="id-card-top-text">
                                    <div class="municipality">Municipality of</div>
                                    <div class="barangay-name"><?php echo e(getSetting('barangay_name', 'Barangay')); ?></div>
                                    <div class="card-type">Resident ID</div>
                                </div>
                            </div>
                            <div class="id-card-body">
                                <div class="id-card-photo">
                                    <span class="qr-corner-bl"></span>
                                    <span class="qr-corner-tr"></span>
                                    <img class="qr-img" src="<?php echo BASE_URL; ?>/includes/qr.php?type=resident&id=<?php echo (int) $resident['id']; ?>" alt="QR">
                                    <div class="qr-label"><i class="bi bi-shield-check" style="font-size:0.04in;margin-right:1px;"></i>Verified</div>
                                </div>
                                <div class="id-card-info">
                                    <div class="id-card-name"><?php echo htmlspecialchars($resident['full_name']); ?></div>
                                    <div class="id-card-detail"><span class="label">Address</span><span class="value"><?php echo htmlspecialchars($resident['address'] ?? '-'); ?></span></div>
                                    <div class="id-card-detail"><span class="label">Age/Sex</span><span class="value"><?php echo $age; ?> / <?php echo htmlspecialchars($resident['sex'] ?? '-'); ?></span></div>
                                    <div class="id-card-detail" style="margin-top:0.03in;"><span class="id-card-type-badge"><?php echo $typeLabel; ?></span></div>
                                </div>
                            </div>
                            <div class="id-card-footer">
                                <div style="text-align:center;width:100%;">
                                    <div class="id-number">ID <?php echo str_pad($resident['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <div class="issued">Issued <?php echo date('M d, Y'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printID(<?php echo (int) $resident['id']; ?>)"><i class="bi bi-printer me-1"></i> Print</button>
                </div>
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
                    <h3 class="delete-toast-title">Delete Resident</h3>
                </div>
                <div class="delete-toast-message">
                    <p>Are you sure you want to delete <span id="deleteToastName"></span>? This action cannot be undone.</p>
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
            const toastName = document.getElementById('deleteToastName');
            const selectAll = document.getElementById('selectAll');
            const bulkActionBar = document.getElementById('bulkActionBar');
            const selectedCount = document.getElementById('selectedCount');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

            let pendingDeleteId = null;
            let pendingDeleteIds = [];

            function updateBulkBar() {
                const checked = document.querySelectorAll('.resident-check:checked');
                const count = checked.length;
                if (count > 0) {
                    bulkActionBar.classList.remove('d-none');
                    selectedCount.textContent = count + ' selected';
                } else {
                    bulkActionBar.classList.add('d-none');
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.resident-check').forEach(cb => cb.checked = this.checked);
                    updateBulkBar();
                });
            }

            document.querySelectorAll('.resident-check').forEach(cb => {
                cb.addEventListener('change', updateBulkBar);
            });

            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', function() {
                    const checked = document.querySelectorAll('.resident-check:checked');
                    pendingDeleteIds = Array.from(checked).map(cb => cb.value);
                    if (pendingDeleteIds.length === 0) return;
                    toastName.textContent = pendingDeleteIds.length + ' residents';
                    toastOverlay.classList.add('active');
                });
            }

            document.querySelectorAll('[data-resident-id]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    pendingDeleteId = this.getAttribute('data-resident-id');
                    pendingDeleteIds = [];
                    toastName.textContent = this.getAttribute('data-resident-name');
                    toastOverlay.classList.add('active');
                });
            });

            toastCancel.addEventListener('click', function() {
                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
                pendingDeleteIds = [];
            });

            toastOverlay.addEventListener('click', function(e) {
                if (e.target === toastOverlay) {
                    toastOverlay.classList.remove('active');
                    pendingDeleteId = null;
                    pendingDeleteIds = [];
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && toastOverlay.classList.contains('active')) {
                    toastOverlay.classList.remove('active');
                    pendingDeleteId = null;
                    pendingDeleteIds = [];
                }
            });

            toastConfirm.addEventListener('click', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                if (pendingDeleteIds.length > 0) {
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_bulk';

                    const idsInput = document.createElement('input');
                    idsInput.type = 'hidden';
                    idsInput.name = 'resident_ids';
                    idsInput.value = pendingDeleteIds.join(',');

                    form.appendChild(actionInput);
                    form.appendChild(idsInput);
                } else if (pendingDeleteId) {
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'resident_id';
                    idInput.value = pendingDeleteId;

                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                }

                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_csrf_token';
                csrfInput.value = '<?php echo e($_SESSION["_csrf_token"] ?? ""); ?>';
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();

                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
                pendingDeleteIds = [];
            });
        });

        function printID(id) {
            var card = document.getElementById('printCard' + id);
            if (!card) return;
            var win = window.open('', '_blank');
            win.document.write('<!DOCTYPE html><html><head><style>');
            win.document.write('@page{size:3.375in 2.125in;margin:0;}');
            win.document.write('*{margin:0;padding:0;box-sizing:border-box;}');
            win.document.write('body{display:flex;align-items:center;justify-content:center;width:3.375in;height:2.125in;}');
            win.document.write(document.querySelector('#printStyle').textContent);
            win.document.write('</style></head><body>');
            win.document.write(card.outerHTML);
            win.document.write('</body></html>');
            win.document.close();
            win.focus();
            var imgs = win.document.querySelectorAll('img');
            var loaded = 0;
            var total = imgs.length;
            if (total === 0) { setTimeout(function() { win.print(); win.close(); }, 200); return; }
            imgs.forEach(function(img) {
                if (img.complete) { loaded++; } else {
                    img.onload = function() { loaded++; if (loaded >= total) { win.print(); win.close(); } };
                    img.onerror = function() { loaded++; if (loaded >= total) { win.print(); win.close(); } };
                }
            });
            setTimeout(function() { win.print(); win.close(); }, 5000);
        }
    </script>