<?php
error_reporting(E_ALL);
ini_set("display_errors", "0");
ini_set("log_errors", "1");
require_once __DIR__ . "/config/constants.php";
require_once __DIR__ . "/config/database.php";
echo "STEP1: includes done\n";
$type = trim($_GET["type"] ?? "");
$id = (int)($_GET["id"] ?? 0);
echo "STEP2: type=$type id=$id\n";
echo "FILE: " . __FILE__ . "\n";
echo "SCRIPT: " . $_SERVER["SCRIPT_FILENAME"] . "\n";
echo "MATCH: " . ((__FILE__ === $_SERVER["SCRIPT_FILENAME"]) ? "YES" : "NO") . "\n";
if ($type === "resident" && $id > 0) {
    echo "STEP3: entering resident block\n";
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT full_name FROM residents WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $resident = $stmt->fetch();
        echo "STEP4: resident=" . json_encode($resident) . "\n";
        if ($resident) {
            $payload = buildQrPayload($id, $resident["full_name"]);
            echo "STEP5: payload=$payload\n";
            require_once __DIR__ . "/includes/qr_generate.php";
            echo "STEP6: qr_generate loaded\n";
            $qr = new QRCodeGenerator();
            echo "STEP7: QRCodeGenerator created\n";
            $result = $qr->generate($payload);
            echo "STEP8: result length=" . strlen($result) . "\n";
            header("Content-Type: image/png");
            echo $result;
        }
    } catch (Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
}

