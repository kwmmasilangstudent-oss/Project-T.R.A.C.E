<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

scannerRequireAuth();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$officialName = scannerUserName();
$base = BASE_URL;
$scanApi = $base . '/scanner/api/scan_handler.php';
$lookupApi = $base . '/scanner/api/resident_lookup.php';
$searchApi = $base . '/scanner/api/resident_search.php';
$logsUrl = $base . '/scanner/logs.php';
$sheetUrl = $base . '/scanner/attendance_sheet.php';
$beepSrc = $base . '/scanner/assets/audio/beep.wav';
$errorSrc = $base . '/scanner/assets/audio/error.wav';
$libSrc = $base . '/scanner/assets/js/vendor/html5-qrcode.min.js';
$cssSrc = $base . '/scanner/assets/css/scanner.css';
$jsSrc = $base . '/scanner/assets/js/scanner.js';
$eventsApi = $base . '/scanner/api/events.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f172a">
    <title>Resident QR Scanner — <?php echo e(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e($cssSrc); ?>">
</head>
<body>

    <div class="sc-topbar">
        <div class="sc-topbar-brand">
            <i class="bi bi-upc-scan"></i>
            <span>Resident QR Scanner</span>
        </div>
        <div class="sc-topbar-actions">
            <?php $dashboardUrl = ($_SESSION['role'] ?? '') === 'secretary' ? e(BASE_URL) . '/secretary/dashboard.php' : e(BASE_URL) . '/admin/dashboard.php'; ?>
            <a class="sc-icon-btn" href="<?php echo $dashboardUrl; ?>" title="Back to Dashboard"><i class="bi bi-arrow-left"></i></a>
            <a class="sc-icon-btn" href="<?php echo e($sheetUrl); ?>" title="Attendance sheet"><i class="bi bi-file-earmark-text"></i></a>
            <button class="sc-icon-btn" id="btnTorch" title="Flashlight" disabled><i class="bi bi-lightbulb"></i></button>
            <button class="sc-icon-btn" id="btnSwitch" title="Switch camera" disabled><i class="bi bi-camera-reverse"></i></button>
            <button class="sc-icon-btn" id="btnSens" title="Scan sensitivity"><i class="bi bi-sliders"></i><span style="display:none;">Normal</span></button>
            <button class="sc-icon-btn" id="btnBatch" title="Batch scan mode"><i class="bi bi-list-check"></i></button>
            <a class="sc-history-link" href="<?php echo e($logsUrl); ?>"><i class="bi bi-clock-history"></i> Logs</a>
        </div>
    </div>

    <div class="sc-stats">
        <div class="sc-stat"><div class="sc-stat-num" id="statTotal">0</div><div class="sc-stat-label">Scanned Today</div></div>
        <div class="sc-stat ok"><div class="sc-stat-num" id="statOk">0</div><div class="sc-stat-label">Verified</div></div>
        <div class="sc-stat bad"><div class="sc-stat-num" id="statNotFound">0</div><div class="sc-stat-label">Not Found</div></div>
        <div class="sc-stat warn"><div class="sc-stat-num" id="statInactive">0</div><div class="sc-stat-label">Inactive/Expired</div></div>
    </div>

    <div class="sc-event-bar" id="eventBar">
        <i class="bi bi-calendar-event"></i>
        <select class="sc-event-select" id="eventSelect" aria-label="Select event">
            <option value="">No event selected</option>
        </select>
        <span class="sc-event-count" id="eventCount" style="display:none;"></span>
    </div>

    <div class="sc-batch-banner" id="batchBanner">
        <i class="bi bi-list-check"></i> Batch mode ON — scans log automatically. Tap "Continue Scanning" to keep going.
    </div>

    <div class="sc-stage">
        <div id="reader"></div>

        <div class="sc-frame" id="scFrame">
            <div class="sc-frame-inner">
                <div class="sc-frame-corner-tr"></div>
                <div class="sc-frame-corner-bl"></div>
                <div class="sc-frame-text">Align the QR code within the frame</div>
            </div>
        </div>
    </div>

    <div class="sc-status ready" id="scStatus">
        <i class="bi bi-camera"></i> Loading scanner...
    </div>

    <div class="sc-permission" id="scPermission">
        <h3><i class="bi bi-camera-video-off"></i> Camera access needed</h3>
        <p>This scanner needs your camera to read printed QR codes. Please allow camera access:</p>
        <ol>
            <li>Tap the <strong>camera / lock icon</strong> in your browser address bar.</li>
            <li>Choose <strong>Allow</strong> for camera permission.</li>
            <li>On Android, also check <code>Settings → Apps → Browser → Permissions → Camera = Allow</code>.</li>
            <li>Return here and tap <strong>Start Scanner</strong>.</li>
        </ol>
        <p>If the camera still will not work, use <strong>"Find by Name"</strong> below to search for a resident.</p>
    </div>

    <div class="sc-actions">
        <button class="sc-btn sc-btn-primary" id="btnStart"><i class="bi bi-camera"></i> Start Scanner</button>
        <button class="sc-btn sc-btn-danger" id="btnStop" disabled><i class="bi bi-stop-circle"></i> Stop Scanner</button>
    </div>
    <div class="sc-panel">
        <div class="sc-collapsible sc-manual open" id="lookupPanel">
            <label for="lookupInput"><i class="bi bi-search"></i> Search resident by name or Senior Citizen ID</label>
            <div class="sc-autocomplete-wrap">
                <input type="text" id="lookupInput" class="sc-input" placeholder="Type a name to search..." autocomplete="off">
                <div class="sc-autocomplete-list" id="autocompleteList"></div>
            </div>
            <button class="sc-btn sc-btn-primary" id="btnLookupSubmit" style="margin-top:12px;width:100%;"><i class="bi bi-search"></i> Search</button>
            <div id="lookupResults" style="margin-top:12px;"></div>
        </div>

        <select class="sc-cam-select" id="camSelect" style="display:none;"></select>

        <div class="sc-sens" style="display:none;">
            <span>Sensitivity:</span>
            <button class="sc-toggle" id="sensToggle" type="button" aria-label="Toggle sensitivity"></button>
            <span id="sensLabel">Normal</span>
        </div>
    </div>

    <div class="sc-recent-backdrop" id="recentBackdrop"></div>
    <div class="sc-recent-drawer" id="recentDrawer">
        <div class="sc-recent-head">
            <span><i class="bi bi-clock-history"></i> Last 5 Scans</span>
            <button class="sc-recent-close" id="recentClose"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="sc-recent-list" id="recentList">
            <div class="sc-recent-empty">No scans yet.</div>
        </div>
    </div>

    <div class="sc-modal-backdrop" id="resultModal">
        <div class="sc-result-card" id="resultContent"></div>
    </div>

    <script>
        window.SCANNER_CFG = {
            csrf: <?php echo json_encode($csrf); ?>,
            scanApi: <?php echo json_encode($scanApi); ?>,
            lookupApi: <?php echo json_encode($lookupApi); ?>,
            searchApi: <?php echo json_encode($searchApi); ?>,
            eventsApi: <?php echo json_encode($eventsApi); ?>,
            officialName: <?php echo json_encode($officialName); ?>,
            beepSrc: <?php echo json_encode($beepSrc); ?>,
            errorSrc: <?php echo json_encode($errorSrc); ?>
        };
    </script>
    <script src="<?php echo e($libSrc); ?>"></script>
    <script src="<?php echo e($jsSrc); ?>"></script>
</body>
</html>