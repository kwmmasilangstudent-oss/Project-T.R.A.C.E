<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth(['admin']);

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

function fetchCameraSnapshot(string $url, ?string $username, ?string $password): array {
    $result = [
        'ok' => false,
        'data' => '',
        'contentType' => 'image/jpeg',
        'message' => 'Unable to fetch snapshot',
    ];

    if ($url === '') {
        $result['message'] = 'No snapshot URL configured';
        return $result;
    }

    $headers = [];
    if ($username !== '' && $password !== '') {
        $headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
            'ignore_errors' => true,
            'header' => $headers,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $data = @file_get_contents($url, false, $context);
    if ($data === false || $data === '') {
        return $result;
    }

    $result['ok'] = true;
    $result['data'] = $data;
    $result['contentType'] = 'image/jpeg';

    $headers = $http_response_header ?? [];
    foreach ($headers as $header) {
        if (stripos($header, 'content-type:') === 0) {
            $result['contentType'] = trim(substr($header, 13));
            break;
        }
    }

    return $result;
}

function writeCameraStatus(PDO $pdo, int $cameraId, string $status, ?string $error): void {
    $stmt = $pdo->prepare('UPDATE cctv_cameras SET last_status = ?, last_checked_at = NOW(), last_error = ? WHERE id = ?');
    $stmt->execute([$status, $error, $cameraId]);
}

$pdo = getDbConnection();
ensureCctvSchema($pdo);

$cameraId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($cameraId <= 0) {
    http_response_code(400);
    echo 'Invalid camera id';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM cctv_cameras WHERE id = ? LIMIT 1');
$stmt->execute([$cameraId]);
$camera = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$camera) {
    http_response_code(404);
    echo 'Camera not found';
    exit;
}

if (empty($camera['is_enabled'])) {
    http_response_code(403);
    echo 'Camera disabled';
    exit;
}

$result = fetchCameraSnapshot($camera['snapshot_url'], $camera['username'], $camera['password']);
if ($result['ok']) {
    writeCameraStatus($pdo, $cameraId, 'online', null);
    header('Content-Type: ' . $result['contentType']);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $result['data'];
    exit;
}

writeCameraStatus($pdo, $cameraId, 'offline', $result['message']);
header('Content-Type: image/svg+xml');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo '<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="720" viewBox="0 0 1280 720">
  <rect width="1280" height="720" fill="#111827"/>
  <rect x="40" y="40" width="1200" height="640" rx="24" fill="#1f2937" stroke="#374151" stroke-width="4"/>
  <circle cx="640" cy="330" r="120" fill="#ef4444" opacity="0.2"/>
  <path d="M640 240 L640 420" stroke="#ef4444" stroke-width="24" stroke-linecap="round"/>
  <path d="M640 450 L640 470" stroke="#ef4444" stroke-width="24" stroke-linecap="round"/>
  <text x="640" y="590" text-anchor="middle" font-family="Arial, sans-serif" font-size="44" fill="#f9fafb">Camera offline</text>
  <text x="640" y="640" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#9ca3af">Unable to fetch snapshot from the configured endpoint</text>
</svg>';
