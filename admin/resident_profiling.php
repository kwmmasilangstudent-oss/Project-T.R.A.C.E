<?php require_once __DIR__ . "/../includes/auth.php"; ?>
<?php requireAuth(["admin"]); ?>
<?php require_once __DIR__ . "/../config/database.php"; ?>
<?php require_once __DIR__ . "/../includes/header.php"; ?>
<?php require_once __DIR__ . "/../includes/navbar.php";

$pdo = getDbConnection();
$success = $_SESSION['_flash_success'] ?? "";
$error = $_SESSION['_flash_error'] ?? "";
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

require_once __DIR__ . "/../migrations/run.php";

if (isset($_GET["download"]) && $_GET["download"] === "template") {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=resident_profiling_template.csv");
    $cols = [
        "PCN (PhilSys ID)","First Name","Middle Name","Last Name","Suffix",
        "Birth Date","Birth Place","Gender","Civil Status","Citizenship","Religion","Ethnicity",
        "Resident Type (regular,senior_citizen,pwd,4ps,indigent,fisherfolk,solo_parent,farmer,student,faculty)",
        "House Number","Street Name","Purok/Sitio ID (1-5)",
        "Housing Material (concrete,wood,semi-concrete,light)",
        "Tenure Status (owner,renter,free)",
        "Drinking Water Source (piped,protected_well,unprotected_well,spring,bottled)",
        "Toilet Facility Type (sanitary,unsanitary,none)",
        "Household Members","Educational Attainment",
        "Primary Occupation","Employment Status (employed,unemployed,self-employed,student,retired)",
        "Monthly Household Income",
        "Senior Citizen (yes/no)","PWD (yes/no)","PWD Disability Type",
        "Solo Parent (yes/no)","OFW (yes/no)","Indigent (yes/no)"
    ];
    $out = fopen("php://output", "w");
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, $cols);
    fclose($out);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireCsrf();
    $action = $_POST["action"] ?? "";
    if ($action === "create" || $action === "update") {
        $fn = sanitizeString($_POST["first_name"] ?? "");
        $ln = sanitizeString($_POST["last_name"] ?? "");
        $rid = $action === "update" ? (int)($_POST["resident_id"] ?? 0) : 0;
        if (!$fn || !$ln) {
            $_SESSION['_flash_error'] = "First name and last name are required.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif ($action === "update" && $rid <= 0) {
            $_SESSION['_flash_error'] = "Invalid resident.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (($_POST["birthdate"] ?? "") && !validateDate($_POST["birthdate"])) {
            $_SESSION['_flash_error'] = "Invalid birth date format.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } elseif (($_POST["monthly_household_income"] ?? "") && !is_numeric(str_replace(",", "", $_POST["monthly_household_income"]))) {
            $_SESSION['_flash_error'] = "Monthly household income must be a valid number.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $pcn = trim($_POST["philsys_pcn"] ?? "");
            $mn = trim($_POST["middle_name"] ?? "");
            $suf = trim($_POST["suffix"] ?? "");
            $full = trim("$fn $mn $ln" . ($suf ? " $suf" : ""));
            $bd = trim($_POST["birthdate"] ?? "");
            $bp = trim($_POST["birthplace"] ?? "");
            $sx = trim($_POST["gender"] ?? "");
            $cs = trim($_POST["civil_status"] ?? "");
            $ct = trim($_POST["citizenship"] ?? "");
            $rl = trim($_POST["religion"] ?? "");
            $et = trim($_POST["ethnicity"] ?? "");
            $rtArr = $_POST["resident_type"] ?? [];
            $rt = is_array($rtArr) && count($rtArr) > 0 ? implode(",", $rtArr) : "regular";
            $hn = trim($_POST["house_number"] ?? "");
            $sn = trim($_POST["street_name"] ?? "");
            $addr = trim("$hn $sn");
            $psid = $_POST["purok_sitio_id"] ? (int)$_POST["purok_sitio_id"] : null;
            $hm = trim($_POST["housing_material"] ?? "");
            $ts = trim($_POST["tenure_status"] ?? "");
            $dws = trim($_POST["drinking_water_source"] ?? "");
            $tft = trim($_POST["toilet_facility_type"] ?? "");
            $hhm = $_POST["household_members"] ? (int)$_POST["household_members"] : 1;
            $ea = trim($_POST["educational_attainment"] ?? "");
            $po = trim($_POST["primary_occupation"] ?? "");
            $es = trim($_POST["employment_status"] ?? "");
            $mhi = $_POST["monthly_household_income"] ? (float)str_replace(",", "", $_POST["monthly_household_income"]) : null;
            $isc = isset($_POST["is_senior_citizen"]) ? 1 : 0;
            $ip = isset($_POST["is_pwd"]) ? 1 : 0;
            $pdt = trim($_POST["pwd_disability_type"] ?? "");
            $isp = isset($_POST["is_solo_parent"]) ? 1 : 0;
            $ofw = isset($_POST["is_ofw"]) ? 1 : 0;
            $ind = isset($_POST["is_indigent"]) ? 1 : 0;

            if ($action === "create") {
                $stmt = $pdo->prepare("INSERT INTO residents (philsys_pcn,first_name,middle_name,last_name,suffix,full_name,birth_date,birthplace,sex,civil_status,citizenship,religion,ethnicity,resident_type,address,house_number,street_name,purok_sitio_id,housing_material,tenure_status,drinking_water_source,toilet_facility_type,household_members,educational_attainment,primary_occupation,employment_status,monthly_household_income,is_senior_citizen,is_pwd,pwd_disability_type,is_solo_parent,is_ofw,is_indigent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$pcn?:null,$fn,$mn?:null,$ln,$suf?:null,$full,$bd?:null,$bp?:null,$sx?:null,$cs?:null,$ct?:null,$rl?:null,$et?:null,$rt,$addr?:null,$hn?:null,$sn?:null,$psid,$hm?:null,$ts?:null,$dws?:null,$tft?:null,$hhm,$ea?:null,$po?:null,$es?:null,$mhi,$isc,$ip,$pdt?:null,$isp,$ofw,$ind]);
                logAudit("create_resident_profile","Created resident profile: $full");
                $_SESSION['_flash_success'] = "Resident profile created successfully.";
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $stmt = $pdo->prepare("UPDATE residents SET philsys_pcn=?,first_name=?,middle_name=?,last_name=?,suffix=?,full_name=?,birth_date=?,birthplace=?,sex=?,civil_status=?,citizenship=?,religion=?,ethnicity=?,resident_type=?,address=?,house_number=?,street_name=?,purok_sitio_id=?,housing_material=?,tenure_status=?,drinking_water_source=?,toilet_facility_type=?,household_members=?,educational_attainment=?,primary_occupation=?,employment_status=?,monthly_household_income=?,is_senior_citizen=?,is_pwd=?,pwd_disability_type=?,is_solo_parent=?,is_ofw=?,is_indigent=? WHERE id=?");
                $stmt->execute([$pcn?:null,$fn,$mn?:null,$ln,$suf?:null,$full,$bd?:null,$bp?:null,$sx?:null,$cs?:null,$ct?:null,$rl?:null,$et?:null,$rt,$addr?:null,$hn?:null,$sn?:null,$psid,$hm?:null,$ts?:null,$dws?:null,$tft?:null,$hhm,$ea?:null,$po?:null,$es?:null,$mhi,$isc,$ip,$pdt?:null,$isp,$ofw,$ind,$rid]);
                logAudit("update_resident_profile","Updated resident profile ID: $rid");
                $_SESSION['_flash_success'] = "Resident profile updated successfully.";
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    } elseif ($action === "delete") {
        $rid = (int)($_POST["resident_id"] ?? 0);
        if ($rid > 0) {
            $pdo->prepare("DELETE FROM residents WHERE id = ?")->execute([$rid]);
            logAudit("delete_resident_profile","Deleted resident profile ID: $rid");
            $_SESSION['_flash_success'] = "Resident profile deleted successfully.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['_flash_error'] = "Invalid resident.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === "bulk_import") {
        $inserted = 0;
        $skipped = 0;
        $raw = "";
        if (!empty($_POST["paste_data"])) {
            $raw = $_POST["paste_data"];
        } elseif (!empty($_FILES["csv_file"]["tmp_name"])) {
            $raw = file_get_contents($_FILES["csv_file"]["tmp_name"]);
            if (str_starts_with($raw, "\xEF\xBB\xBF")) $raw = substr($raw, 3);
        }
        if (trim($raw) === "") {
            $_SESSION['_flash_error'] = "No data provided. Upload a CSV file or paste tab-separated data.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $lines = explode("\n", str_replace("\r\n", "\n", $raw));
            $header = null;
            $mode = "csv";
            if (str_contains($raw, "\t") && !str_contains(substr($raw, 0, 500), ",")) {
                $mode = "tsv";
            }
            foreach ($lines as $i => $line) {
                $line = trim($line);
                if ($line === "") continue;
                if ($i === 0) {
                    $header = $mode === "tsv" ? explode("\t", $line) : str_getcsv($line);
                    continue;
                }
                $vals = $mode === "tsv" ? explode("\t", $line) : str_getcsv($line);
                $map = array_combine($header, $vals);
                if (!$map || empty(trim($map["First Name"] ?? "")) || empty(trim($map["Last Name"] ?? ""))) {
                    $skipped++;
                    continue;
                }
                $fn = trim($map["First Name"] ?? "");
                $ln = trim($map["Last Name"] ?? "");
                $mn = trim($map["Middle Name"] ?? "");
                $suf = trim($map["Suffix"] ?? "");
                $full = trim("$fn $mn $ln" . ($suf ? " $suf" : ""));
                $pcn = trim($map["PCN (PhilSys ID)"] ?? "");
                $bd = trim($map["Birth Date"] ?? "");
                $bp = trim($map["Birth Place"] ?? "");
                $sx = trim($map["Gender"] ?? "");
                $cs = trim($map["Civil Status"] ?? "");
                $ct = trim($map["Citizenship"] ?? "");
                $rl = trim($map["Religion"] ?? "");
                $et = trim($map["Ethnicity"] ?? "");
                $rtRaw = trim($map["Resident Type (regular,senior_citizen,pwd,4ps,indigent)"] ?? "regular");
                $rt = $rtRaw !== "" ? $rtRaw : "regular";
                $hn = trim($map["House Number"] ?? "");
                $sn = trim($map["Street Name"] ?? "");
                $addr = trim("$hn $sn");
                $psidRaw = trim($map["Purok/Sitio ID (1-5)"] ?? "");
                $psid = $psidRaw !== "" ? (int)$psidRaw : null;
                $hm = trim($map["Housing Material (concrete,wood,semi-concrete,light)"] ?? "");
                $ts = trim($map["Tenure Status (owner,renter,free)"] ?? "");
                $dws = trim($map["Drinking Water Source (piped,protected_well,unprotected_well,spring,bottled)"] ?? "");
                $tft = trim($map["Toilet Facility Type (sanitary,unsanitary,none)"] ?? "");
                $hhmRaw = trim($map["Household Members"] ?? "");
                $hhm = $hhmRaw !== "" ? (int)$hhmRaw : 1;
                $ea = trim($map["Educational Attainment"] ?? "");
                $po = trim($map["Primary Occupation"] ?? "");
                $es = trim($map["Employment Status (employed,unemployed,self-employed,student,retired)"] ?? "");
                $mhiRaw = trim($map["Monthly Household Income"] ?? "");
                $mhi = $mhiRaw !== "" ? (float)$mhiRaw : null;
                $isc = in_array(strtolower(trim($map["Senior Citizen (yes/no)"] ?? "")), ["yes","y","1"]) ? 1 : 0;
                $ip = in_array(strtolower(trim($map["PWD (yes/no)"] ?? "")), ["yes","y","1"]) ? 1 : 0;
                $pdt = trim($map["PWD Disability Type"] ?? "");
                $isp = in_array(strtolower(trim($map["Solo Parent (yes/no)"] ?? "")), ["yes","y","1"]) ? 1 : 0;
                $ofw = in_array(strtolower(trim($map["OFW (yes/no)"] ?? "")), ["yes","y","1"]) ? 1 : 0;
                $ind = in_array(strtolower(trim($map["Indigent (yes/no)"] ?? "")), ["yes","y","1"]) ? 1 : 0;
                try {
                    $stmt = $pdo->prepare("INSERT INTO residents (philsys_pcn,first_name,middle_name,last_name,suffix,full_name,birth_date,birthplace,sex,civil_status,citizenship,religion,ethnicity,resident_type,address,house_number,street_name,purok_sitio_id,housing_material,tenure_status,drinking_water_source,toilet_facility_type,household_members,educational_attainment,primary_occupation,employment_status,monthly_household_income,is_senior_citizen,is_pwd,pwd_disability_type,is_solo_parent,is_ofw,is_indigent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$pcn?:null,$fn,$mn?:null,$ln,$suf?:null,$full,$bd?:null,$bp?:null,$sx?:null,$cs?:null,$ct?:null,$rl?:null,$et?:null,$rt,$addr?:null,$hn?:null,$sn?:null,$psid,$hm?:null,$ts?:null,$dws?:null,$tft?:null,$hhm,$ea?:null,$po?:null,$es?:null,$mhi,$isc,$ip,$pdt?:null,$isp,$ofw,$ind]);
                    $inserted++;
                } catch (Throwable $e) {
                    $skipped++;
                }
            }
            logAudit("bulk_import_residents","Bulk imported $inserted residents ($skipped skipped)");
            if ($inserted > 0) {
                $_SESSION['_flash_success'] = "Successfully imported $inserted resident" . ($inserted !== 1 ? "s" : "") . "." . ($skipped > 0 ? " $skipped row" . ($skipped !== 1 ? "s" : "") . " skipped due to errors." : "");
            } else {
                $_SESSION['_flash_error'] = "No residents were imported. Check your file format and try again.";
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$search = $_GET["search"] ?? "";
$countWhere = "";
$dataWhere = "";
$params = [];
if ($search) {
    $like = "%$search%";
    $countWhere = " WHERE first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR full_name LIKE ? OR philsys_pcn LIKE ? OR address LIKE ?";
    $dataWhere = " WHERE first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR full_name LIKE ? OR philsys_pcn LIKE ? OR address LIKE ?";
    $params = [$like,$like,$like,$like,$like,$like];
}
$paginator = paginate(
    "SELECT COUNT(*) FROM residents$countWhere",
    $params,
    "SELECT * FROM residents$dataWhere ORDER BY created_at DESC",
    $params
);
$profiles = $paginator['data'];
$total = $paginator['total'];
$sc = $pdo->query("SELECT COUNT(*) FROM residents WHERE is_senior_citizen=1")->fetchColumn();
$pwd = $pdo->query("SELECT COUNT(*) FROM residents WHERE is_pwd=1")->fetchColumn();
$sol = $pdo->query("SELECT COUNT(*) FROM residents WHERE is_solo_parent=1")->fetchColumn();
$ofwC = $pdo->query("SELECT COUNT(*) FROM residents WHERE is_ofw=1")->fetchColumn();
$indC = $pdo->query("SELECT COUNT(*) FROM residents WHERE is_indigent=1")->fetchColumn();

function getAge($bd) {
    if (!$bd) return null;
    return (new DateTime())->diff(new DateTime($bd))->y;
}

function fmtMoney($v) {
    if ($v === null) return "-";
    return "&#x20B1;" . number_format((float)$v, 2);
}

function fmtPcn($v) {
    return $v ?: "-";
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>
        </div>
        <div class="col-md-9 py-4 px-3 px-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Resident Profiling</h3>
                    <p class="text-muted-glass mb-0">Comprehensive resident profiles with dynamic fields and validation</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#profileModal" id="addResidentBtn">
                        <i class="bi bi-plus-lg"></i> Add Resident
                    </button>
                    <button class="btn btn-outline-info d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                        <i class="bi bi-upload"></i> Bulk Import
                    </button>
                </div>
            </div>
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="stat-chip stat-total"><span class="stat-dot"></span>Total Profiles: <?php echo (int)$total; ?></span>
                <span class="stat-chip" style="background:rgba(255,193,7,0.12);border-color:rgba(255,193,7,0.25);color:#fbbf24;">
                    <span class="stat-dot" style="background:#fbbf24;"></span>Senior: <?php echo (int)$sc; ?>
                </span>
                <span class="stat-chip" style="background:rgba(13,202,240,0.12);border-color:rgba(13,202,240,0.25);color:#22d3ee;">
                    <span class="stat-dot" style="background:#22d3ee;"></span>PWD: <?php echo (int)$pwd; ?>
                </span>
                <span class="stat-chip" style="background:rgba(16,185,129,0.12);border-color:rgba(16,185,129,0.25);color:#34d399;">
                    <span class="stat-dot" style="background:#34d399;"></span>Solo Parent: <?php echo (int)$sol; ?>
                </span>
                <span class="stat-chip" style="background:rgba(99,102,241,0.12);border-color:rgba(99,102,241,0.25);color:#818cf8;">
                    <span class="stat-dot" style="background:#818cf8;"></span>OFW: <?php echo (int)$ofwC; ?>
                </span>
                <span class="stat-chip" style="background:rgba(239,68,68,0.12);border-color:rgba(239,68,68,0.25);color:#f87171;">
                    <span class="stat-dot" style="background:#f87171;"></span>Indigent: <?php echo (int)$indC; ?>
                </span>
            </div>
            <div class="glass-card p-3 p-md-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-8">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, PCN, or address..." value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
                    </div>
                </form>
            </div>
            <div class="glass-card p-3 p-md-4">
                <h5 class="mb-3" style="font-family:var(--font-display);font-weight:700;">Resident Profiles</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>PCN</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Type</th>
                                <th>Address</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($profiles)): ?>
                                <?php foreach ($profiles as $r): ?>
                                <?php $age = getAge($r["birth_date"] ?? null); ?>
                                <tr>
                                    <td><code style="font-size:0.8rem;"><?php echo fmtPcn($r["philsys_pcn"] ?? ""); ?></code></td>
                                    <td><strong><?php echo e(!empty($r["full_name"]) ? $r["full_name"] : trim(($r["first_name"] ?? "") . " " . ($r["last_name"] ?? ""))); ?></strong></td>
                                    <td><?php echo $age !== null ? (int)$age : "-"; ?></td>
                                    <td>
                                        <?php
                                        $rtRaw = $r["resident_type"] ?? "regular";
                                        $rtList = explode(",", $rtRaw);
                                        $labels = ["regular"=>"Regular","senior_citizen"=>"Senior","pwd"=>"PWD","4ps"=>"4Ps","indigent"=>"Indigent","fisherfolk"=>"Fisherfolk","solo_parent"=>"Solo Parent","farmer"=>"Farmer","student"=>"Student","faculty"=>"Faculty"];
                                        $colors = ["regular"=>"#6c757d","senior_citizen"=>"#fbbf24","pwd"=>"#22d3ee","4ps"=>"#34d399","indigent"=>"#f87171","fisherfolk"=>"#06b6d4","solo_parent"=>"#a855f7","farmer"=>"#22c55e","student"=>"#3b82f6","faculty"=>"#f97316"];
                                        $bg = ["regular"=>"rgba(108,117,125,0.15)","senior_citizen"=>"rgba(255,193,7,0.15)","pwd"=>"rgba(13,202,240,0.15)","4ps"=>"rgba(16,185,129,0.15)","indigent"=>"rgba(239,68,68,0.15)","fisherfolk"=>"rgba(6,182,212,0.15)","solo_parent"=>"rgba(168,85,247,0.15)","farmer"=>"rgba(34,197,94,0.15)","student"=>"rgba(59,130,246,0.15)","faculty"=>"rgba(249,115,22,0.15)"];
                                        ?>
                                        <?php foreach ($rtList as $rt): $rt = trim($rt); ?>
                                        <span class="badge me-1" style="background:<?php echo $bg[$rt]??$bg["regular"]; ?>;color:<?php echo $colors[$rt]??$colors["regular"]; ?>;"><?php echo $labels[$rt]??"Regular"; ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><?php echo e($r["address"] ?? "-"); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editProfile(<?php echo (int)$r["id"]; ?>)" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-person-vcard" style="font-size:2rem;color:var(--text-low);display:block;margin-bottom:0.5rem;"></i>
                                        <span style="color:var(--text-low);">No resident profiles found<?php echo $search ? " matching your search." : "."; ?></span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($profiles)): ?>
                <div class="mt-3 d-flex justify-content-center">
                    <?php echo renderPagination($paginator); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Bulk Import Residents</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_import">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="csvTab-tab" data-bs-toggle="tab" data-bs-target="#csvTab" type="button" role="tab">Upload CSV</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pasteTab-tab" data-bs-toggle="tab" data-bs-target="#pasteTab" type="button" role="tab">Paste Data</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="csvTab" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">CSV File</label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv,.tsv,.txt">
                                <small class="text-muted-glass" style="font-size:0.75rem;">
                                    Upload a CSV file exported from Excel. 
                                    <a href="?download=template" class="text-decoration-underline">Download template</a>.
                                </small>
                            </div>
                            <div id="csvPreview" class="mb-3" style="display:none;">
                                <div class="alert alert-info py-2 px-3 mb-0">
                                    <strong id="csvRowCount">0</strong> rows detected. First row must be the header.
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pasteTab" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Paste Data (tab-separated)</label>
                                <textarea name="paste_data" class="form-control" rows="10" placeholder="Copy rows from Excel/sheets and paste here. First row must be the header matching the template columns." style="font-family:monospace;font-size:0.8rem;"></textarea>
                                <small class="text-muted-glass" style="font-size:0.75rem;">
                                    Copy from Excel and paste directly. 
                                    <a href="?download=template" class="text-decoration-underline">Download template</a> for column format.
                                </small>
                            </div>
                            <div id="pastePreview" class="mb-3" style="display:none;">
                                <div class="alert alert-info py-2 px-3 mb-0">
                                    <strong id="pasteRowCount">0</strong> data rows detected.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bulkImportBtn"><i class="bi bi-upload me-1"></i> Import Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="residentProfileForm" method="post">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i><span id="modalFormTitle">Resident Profile</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="resident_id" id="residentId" value="">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab">Identification</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab">Location & Housing</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab">Socio-Economic</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab4-tab" data-bs-toggle="tab" data-bs-target="#tab4" type="button" role="tab">Special Sectors</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">PCN (PhilSys ID)</label>
                                    <input type="text" name="philsys_pcn" class="form-control" id="pcnInput" placeholder="0000-0000-0000-0000" maxlength="19">
                                    <small class="text-muted-glass" style="font-size:0.75rem;">Format: 0000-0000-0000-0000</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" id="firstNameInput" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" id="lastNameInput" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Suffix</label>
                                    <select name="suffix" class="form-select">
                                        <option value="">Select</option>
                                        <option value="Jr.">Jr.</option>
                                        <option value="Sr.">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Birth Date</label>
                                    <input type="date" name="birthdate" class="form-control" id="birthdateInput">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Age</label>
                                    <input type="text" class="form-control" id="ageDisplay" readonly placeholder="Auto-calculated">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Birth Place</label>
                                    <input type="text" name="birthplace" class="form-control" placeholder="City/Municipality, Province">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
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
                                <div class="col-md-4">
                                    <label class="form-label">Citizenship</label>
                                    <input type="text" name="citizenship" class="form-control" placeholder="Filipino">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Religion</label>
                                    <input type="text" name="religion" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ethnicity</label>
                                    <input type="text" name="ethnicity" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Resident Type</label>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;padding:8px 0;">
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="regular" id="rt_regular">
                                            <label class="form-check-label" for="rt_regular" style="font-size:0.9rem;">Regular</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="senior_citizen" id="rt_senior_citizen">
                                            <label class="form-check-label" for="rt_senior_citizen" style="font-size:0.9rem;">Senior Citizen</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="pwd" id="rt_pwd">
                                            <label class="form-check-label" for="rt_pwd" style="font-size:0.9rem;">PWD</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="4ps" id="rt_4ps">
                                            <label class="form-check-label" for="rt_4ps" style="font-size:0.9rem;">4Ps Beneficiary</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="indigent" id="rt_indigent">
                                            <label class="form-check-label" for="rt_indigent" style="font-size:0.9rem;">Indigent</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="fisherfolk" id="rt_fisherfolk">
                                            <label class="form-check-label" for="rt_fisherfolk" style="font-size:0.9rem;">Fisherfolk</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="solo_parent" id="rt_solo_parent">
                                            <label class="form-check-label" for="rt_solo_parent" style="font-size:0.9rem;">Solo Parent</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="farmer" id="rt_farmer">
                                            <label class="form-check-label" for="rt_farmer" style="font-size:0.9rem;">Farmer</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="student" id="rt_student">
                                            <label class="form-check-label" for="rt_student" style="font-size:0.9rem;">Student</label>
                                        </div>
                                        <div class="form-check" style="min-width:130px;">
                                            <input class="form-check-input" type="checkbox" name="resident_type[]" value="faculty" id="rt_faculty">
                                            <label class="form-check-label" for="rt_faculty" style="font-size:0.9rem;">Faculty</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">House Number / Unit</label>
                                    <input type="text" name="house_number" class="form-control" placeholder="e.g., 123">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Street / Purok Name</label>
                                    <input type="text" name="street_name" class="form-control" placeholder="e.g., Purok 1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Purok/Sitio</label>
                                    <select name="purok_sitio_id" class="form-select">
                                        <option value="">Select</option>
                                        <option value="1">Purok 1</option>
                                        <option value="2">Purok 2</option>
                                        <option value="3">Purok 3</option>
                                        <option value="4">Purok 4</option>
                                        <option value="5">Purok 5</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Housing Material</label>
                                    <select name="housing_material" class="form-select">
                                        <option value="">Select</option>
                                        <option value="concrete">Concrete</option>
                                        <option value="wood">Wood</option>
                                        <option value="semi-concrete">Semi-concrete</option>
                                        <option value="light">Light/Makeshift</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tenure Status</label>
                                    <select name="tenure_status" class="form-select">
                                        <option value="">Select</option>
                                        <option value="owner">Owner</option>
                                        <option value="renter">Renter</option>
                                        <option value="free">Free/Shared</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Household Size</label>
                                    <input type="number" name="household_members" class="form-control" min="1" value="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Drinking Water Source</label>
                                    <select name="drinking_water_source" class="form-select">
                                        <option value="">Select</option>
                                        <option value="piped">Piped/Faucet</option>
                                        <option value="protected_well">Protected Well</option>
                                        <option value="unprotected_well">Unprotected Well</option>
                                        <option value="spring">Spring</option>
                                        <option value="bottled">Bottled</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Toilet Facility</label>
                                    <select name="toilet_facility_type" class="form-select">
                                        <option value="">Select</option>
                                        <option value="sanitary">Sanitary/Flush</option>
                                        <option value="unsanitary">Unsanitary/Open Pit</option>
                                        <option value="none">None/Shared</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab3" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Educational Attainment</label>
                                    <select name="educational_attainment" class="form-select">
                                        <option value="">Select</option>
                                        <option value="no_formal">No Formal Education</option>
                                        <option value="elementary_undergrad">Elementary Undergraduate</option>
                                        <option value="elementary_graduate">Elementary Graduate</option>
                                        <option value="high_school_undergrad">High School Undergraduate</option>
                                        <option value="high_school_graduate">High School Graduate</option>
                                        <option value="vocational">Vocational</option>
                                        <option value="college_undergrad">College Undergraduate</option>
                                        <option value="college_graduate">College Graduate</option>
                                        <option value="post_graduate">Post-Graduate</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Primary Occupation</label>
                                    <input type="text" name="primary_occupation" class="form-control" placeholder="e.g., Farmer, Teacher">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Employment Status</label>
                                    <select name="employment_status" class="form-select">
                                        <option value="">Select</option>
                                        <option value="employed">Employed</option>
                                        <option value="unemployed">Unemployed</option>
                                        <option value="self-employed">Self-Employed</option>
                                        <option value="student">Student</option>
                                        <option value="retired">Retired</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Monthly Household Income (&#x20B1;)</label>
                                    <input type="text" name="monthly_household_income" class="form-control" id="incomeInput" placeholder="0.00">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <span id="incomeDisplay" style="font-size:0.9rem;color:var(--text-mid);padding-bottom:0.5rem;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab4" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_senior_citizen" name="is_senior_citizen">
                                        <label class="form-check-label" for="is_senior_citizen">Senior Citizen (60+ years)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_pwd" name="is_pwd">
                                        <label class="form-check-label" for="is_pwd">Person with Disability (PWD)</label>
                                    </div>
                                </div>
                                <div class="col-md-6" id="pwd_disability_container" style="display:none;">
                                    <label class="form-label">Disability Type</label>
                                    <input type="text" name="pwd_disability_type" class="form-control" placeholder="e.g., Visual Impairment, Physical Disability">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_solo_parent" name="is_solo_parent">
                                        <label class="form-check-label" for="is_solo_parent">Solo Parent</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_ofw" name="is_ofw">
                                        <label class="form-check-label" for="is_ofw">Overseas Filipino Worker (OFW)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_indigent" name="is_indigent">
                                        <label class="form-check-label" for="is_indigent">Indigent Family</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> <span id="saveBtnText">Save Profile</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="delete-toast-overlay" id="deleteOverlay" style="display:none;">
    <div class="delete-toast-container">
        <div class="delete-toast-card glass-card">
            <div class="delete-toast-header">
                <div class="delete-toast-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <h3 class="delete-toast-title">Delete Resident Profile</h3>
            </div>
            <div class="delete-toast-message">
                <p>Are you sure you want to delete <strong id="deleteName"></strong>? This action cannot be undone.</p>
            </div>
            <div class="delete-toast-buttons">
                <button class="btn btn-outline-secondary" onclick="closeDelete()">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var pcnInput = document.getElementById("pcnInput");
    if (pcnInput) {
        pcnInput.addEventListener("input", function() {
            var v = this.value.replace(/\D/g, "").substring(0, 16);
            var parts = [];
            for (var i = 0; i < v.length; i += 4) {
                parts.push(v.substring(i, i + 4));
            }
            this.value = parts.join("-");
        });
    }

    var birthInput = document.getElementById("birthdateInput");
    var ageDisplay = document.getElementById("ageDisplay");
    if (birthInput && ageDisplay) {
        birthInput.addEventListener("change", function() {
            if (!this.value) { ageDisplay.value = ""; return; }
            var bd = new Date(this.value);
            var today = new Date();
            var age = today.getFullYear() - bd.getFullYear();
            var m = today.getMonth() - bd.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < bd.getDate())) age--;
            ageDisplay.value = age >= 0 ? age + " years old" : "Invalid date";
        });
    }

    var pwdCheck = document.getElementById("is_pwd");
    var pwdCont = document.getElementById("pwd_disability_container");
    if (pwdCheck && pwdCont) {
        pwdCheck.addEventListener("change", function() {
            pwdCont.style.display = this.checked ? "block" : "none";
            if (!this.checked) {
                var inp = pwdCont.querySelector("input");
                if (inp) inp.value = "";
            }
        });
    }

    var incInput = document.getElementById("incomeInput");
    var incDisp = document.getElementById("incomeDisplay");
    if (incInput && incDisp) {
        incInput.addEventListener("input", function() {
            var v = this.value.replace(/,/g, "").replace(/[^\d.]/g, "");
            var parts = v.split(".");
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            this.value = parts.join(".");
            if (v && parseFloat(v) > 0) {
                incDisp.textContent = "= ₱" + parseFloat(v).toLocaleString("en-PH", {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                incDisp.textContent = "";
            }
        });
    }
})();

(function() {
    var csvInput = document.querySelector("input[name='csv_file']");
    var csvPreview = document.getElementById("csvPreview");
    var csvRowCount = document.getElementById("csvRowCount");
    if (csvInput && csvPreview && csvRowCount) {
        csvInput.addEventListener("change", function() {
            var file = this.files[0];
            if (!file) { csvPreview.style.display = "none"; return; }
            var reader = new FileReader();
            reader.onload = function(e) {
                var text = e.target.result;
                var lines = text.split(/\r?\n/).filter(function(l) { return l.trim() !== ""; });
                var count = lines.length > 0 ? lines.length - 1 : 0;
                if (count > 0) {
                    csvRowCount.textContent = count;
                    csvPreview.style.display = "block";
                } else {
                    csvPreview.style.display = "none";
                }
            };
            reader.readAsText(file);
        });
    }

    var pasteInput = document.querySelector("textarea[name='paste_data']");
    var pastePreview = document.getElementById("pastePreview");
    var pasteRowCount = document.getElementById("pasteRowCount");
    if (pasteInput && pastePreview && pasteRowCount) {
        pasteInput.addEventListener("input", function() {
            var text = this.value;
            var lines = text.split(/\r?\n/).filter(function(l) { return l.trim() !== ""; });
            var count = lines.length > 0 ? lines.length - 1 : 0;
            if (count > 0) {
                pasteRowCount.textContent = count;
                pastePreview.style.display = "block";
            } else {
                pastePreview.style.display = "none";
            }
        });
    }

    var bulkImportBtn = document.getElementById("bulkImportBtn");
    var importForm = bulkImportBtn && bulkImportBtn.closest("form");
    if (importForm) {
        importForm.addEventListener("submit", function(e) {
            var csvFile = this.querySelector("input[name='csv_file']");
            var pasteData = this.querySelector("textarea[name='paste_data']");
            var csvTab = document.getElementById("csvTab");
            var pasteTab = document.getElementById("pasteTab");
            var csvActive = csvTab && csvTab.classList.contains("show") && csvTab.classList.contains("active");
            var pasteActive = pasteTab && pasteTab.classList.contains("show") && pasteTab.classList.contains("active");
            if (csvActive && (!csvFile || !csvFile.files || !csvFile.files[0])) {
                e.preventDefault();
                alert("Please select a CSV file to upload.");
                return;
            }
            if (pasteActive && (!pasteData || pasteData.value.trim() === "")) {
                e.preventDefault();
                alert("Please paste data before importing.");
                return;
            }
            if (!confirm("Are you sure you want to import " + (csvActive ? (csvRowCount ? csvRowCount.textContent : "the") : (pasteRowCount ? pasteRowCount.textContent : "the")) + " residents?")) {
                e.preventDefault();
                return;
            }
            bulkImportBtn.disabled = true;
            bulkImportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing...';
        });
    }
})();

var editData = {};
function editProfile(id) {
    var all = <?php echo json_encode($profiles); ?>;
    var r = all.find(function(p) { return parseInt(p.id) === id; });
    if (!r) return;
    editData = r;
    document.getElementById("formAction").value = "update";
    document.getElementById("residentId").value = r.id;
    document.getElementById("modalFormTitle").textContent = "Edit Resident Profile";
    document.getElementById("saveBtnText").textContent = "Update Profile";
    setField("philsys_pcn", r.philsys_pcn);
    setField("first_name", r.first_name);
    setField("middle_name", r.middle_name);
    setField("last_name", r.last_name);
    setField("suffix", r.suffix);
    setField("birthdate", r.birth_date);
    setField("birthplace", r.birthplace);
    setField("gender", r.sex);
    setField("civil_status", r.civil_status);
    setField("citizenship", r.citizenship);
    setField("religion", r.religion);
    setField("ethnicity", r.ethnicity);
    setResidentTypes(r.resident_type);
    setField("house_number", r.house_number);
    setField("street_name", r.street_name);
    setField("purok_sitio_id", r.purok_sitio_id);
    setField("housing_material", r.housing_material);
    setField("tenure_status", r.tenure_status);
    setField("drinking_water_source", r.drinking_water_source);
    setField("toilet_facility_type", r.toilet_facility_type);
    setField("household_members", r.household_members);
    setField("educational_attainment", r.educational_attainment);
    setField("primary_occupation", r.primary_occupation);
    setField("employment_status", r.employment_status);
    setField("monthly_household_income", r.monthly_household_income);
    setCheck("is_senior_citizen", r.is_senior_citizen);
    setCheck("is_pwd", r.is_pwd);
    setField("pwd_disability_type", r.pwd_disability_type);
    setCheck("is_solo_parent", r.is_solo_parent);
    setCheck("is_ofw", r.is_ofw);
    setCheck("is_indigent", r.is_indigent);
    if (r.birth_date) {
        var evt = new Event("change");
        document.getElementById("birthdateInput").dispatchEvent(evt);
    }
    if (parseInt(r.is_pwd) === 1) {
        document.getElementById("pwd_disability_container").style.display = "block";
    }
    if (r.monthly_household_income && parseFloat(r.monthly_household_income) > 0) {
        document.getElementById("incomeDisplay").textContent = "= ₱" + parseFloat(r.monthly_household_income).toLocaleString("en-PH", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    var modal = new bootstrap.Modal(document.getElementById("profileModal"));
    modal.show();
}

function setField(name, val) {
    var el = document.querySelector("[name='" + name + "']");
    if (el) el.value = val || "";
}

function setCheck(name, val) {
    var el = document.getElementById(name);
    if (el) el.checked = parseInt(val) === 1;
}

function setResidentTypes(val) {
    var types = (val || "regular").split(",");
    document.querySelectorAll("[name='resident_type[]']").forEach(function(cb) {
        cb.checked = types.indexOf(cb.value) !== -1;
    });
}

function resetForm() {
    document.getElementById("formAction").value = "create";
    document.getElementById("residentId").value = "";
    document.getElementById("modalFormTitle").textContent = "Resident Profile";
    document.getElementById("saveBtnText").textContent = "Save Profile";
    document.getElementById("residentProfileForm").reset();
    document.getElementById("ageDisplay").value = "";
    document.getElementById("incomeDisplay").textContent = "";
    document.getElementById("pwd_disability_container").style.display = "none";
    editData = {};
}

document.addEventListener("DOMContentLoaded", function() {
    var addBtn = document.getElementById("addResidentBtn");
    if (addBtn) {
        addBtn.addEventListener("click", resetForm);
    }
    var modalEl = document.getElementById("profileModal");
    if (modalEl) {
        modalEl.addEventListener("hidden.bs.modal", function() {
            if (document.getElementById("formAction").value === "create") resetForm();
        });
    }
});

var deleteId = null;
function confirmDelete(id, name) {
    deleteId = id;
    document.getElementById("deleteName").textContent = name;
    document.getElementById("deleteOverlay").style.display = "flex";
    setTimeout(function() {
        document.getElementById("deleteOverlay").classList.add("active");
    }, 10);
}

function closeDelete() {
    document.getElementById("deleteOverlay").classList.remove("active");
    setTimeout(function() {
        document.getElementById("deleteOverlay").style.display = "none";
        deleteId = null;
    }, 300);
}

document.addEventListener("DOMContentLoaded", function() {
    var confirmBtn = document.getElementById("confirmDeleteBtn");
    if (confirmBtn) {
        confirmBtn.addEventListener("click", function() {
            if (deleteId) {
                var form = document.createElement("form");
                form.method = "POST";
                var a = document.createElement("input");
                a.type = "hidden"; a.name = "action"; a.value = "delete";
                var i = document.createElement("input");
                i.type = "hidden"; i.name = "resident_id"; i.value = deleteId;
                form.appendChild(a);
                form.appendChild(i);
                
                var csrfInput = document.createElement("input");
                csrfInput.type = "hidden";
                csrfInput.name = "_csrf_token";
                csrfInput.value = '<?php echo e($_SESSION["_csrf_token"] ?? ""); ?>';
                form.appendChild(csrfInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    var overlay = document.getElementById("deleteOverlay");
    if (overlay) {
        overlay.addEventListener("click", function(e) {
            if (e.target === overlay) closeDelete();
        });
    }
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") closeDelete();
    });
});
</script>
<style>
.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 600;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    color: var(--text-hi);
}
.stat-chip .stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--bgn-gold);
    flex-shrink: 0;
}
.stat-chip.stat-total {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.15);
}
.light .stat-chip {
    background: rgba(0,0,0,0.03);
    border-color: rgba(0,0,0,0.08);
    color: #1e293b;
}
.light .stat-chip.stat-total {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.12);
}
.light .stat-chip .stat-dot {
    background: #caa23a;
}
.form-check-input:checked {
    background-color: var(--bgn-gold);
    border-color: var(--bgn-gold);
}
.light .form-check-input:checked {
    background-color: #caa23a;
    border-color: #caa23a;
}
</style>
<script>
(function() {
    var sbBody = document.querySelector('.sb-body');
    var sbDesktop = document.getElementById('sbDesktop');
    if (!sbBody || !sbDesktop) return;
    var key = 'sbScrollTop_' + (window.location.pathname || 'default');
    if (sessionStorage) {
        var saved = sessionStorage.getItem(key);
        if (saved !== null) {
            sbBody.scrollTop = parseInt(saved, 10) || 0;
        }
        sbBody.addEventListener('scroll', function() {
            sessionStorage.setItem(key, String(sbBody.scrollTop));
        });
    }
    var links = sbDesktop.querySelectorAll('.sb-link');
    links.forEach(function(link) {
        link.addEventListener('click', function() {
            if (sessionStorage && sbBody) {
                sessionStorage.setItem(key, String(sbBody.scrollTop));
            }
        });
    });
})();
</script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

