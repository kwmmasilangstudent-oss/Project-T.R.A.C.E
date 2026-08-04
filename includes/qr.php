<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

function buildQrPayload(int $residentId, string $fullName): string {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT r.*, pi.citizenship FROM residents r LEFT JOIN personal_information pi ON r.id = pi.resident_id WHERE r.id = ? LIMIT 1');
    $stmt->execute([$residentId]);
    $resident = $stmt->fetch();

    if ($resident) {
        $birthDate = $resident['birth_date'] ? date('Y-m-d', strtotime($resident['birth_date'])) : '';
        $payload = 'resident:' . $residentId . ':' .
                  rawurlencode($resident['full_name']) . ':' .
                  rawurlencode($resident['sex'] ?? '') . ':' .
                  $birthDate . ':' .
                  rawurlencode($resident['address'] ?? '') . ':' .
                  rawurlencode($resident['resident_type'] ?? 'regular');
        return $payload;
    }

    return 'resident:' . $residentId . ':' . rawurlencode($fullName);
}

function buildDocumentQrPayload(string $documentNumber, int $residentId): string {
    return 'document:' . $documentNumber . ':' . $residentId . ':' . time();
}

function hasGdExtension(): bool {
    return function_exists('imagecreatetruecolor');
}

function fetchRealQrPng(string $payload, int $size = 300): ?string {
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&ecc=L&margin=8&data=' . urlencode($payload);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $data = curl_exec($ch);
        $code = is_int(curl_getinfo($ch, CURLINFO_HTTP_CODE)) ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
        curl_close($ch);
        if ($data !== false && $code >= 200 && $code < 300 && substr($data, 0, 4) === "\x89PNG") {
            return $data;
        }
    }
    $data = @file_get_contents($url);
    if ($data !== false && substr($data, 0, 4) === "\x89PNG") {
        return $data;
    }
    return null;
}

function generateQrImage(string $payload, int $size = 300): string {
    $cacheDir = __DIR__ . '/../assets/uploads/qr';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    $cacheFile = $cacheDir . '/cache_' . md5($payload . '|' . $size) . '.png';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 2592000) {
        $cached = @file_get_contents($cacheFile);
        if ($cached !== false && substr($cached, 0, 4) === "\x89PNG") {
            return $cached;
        }
    }
    $real = fetchRealQrPng($payload, $size);
    if ($real !== null) {
        @file_put_contents($cacheFile, $real);
        return $real;
    }
    require_once __DIR__ . '/qr_generate.php';
    $qr = new QRCodeGenerator();
    $png = $qr->generate($payload);
    @file_put_contents($cacheFile, $png);
    return $png;
}

function generateQrFallbackImage(string $payload, int $size = 300): string {
    if (!hasGdExtension()) {
        throw new Exception('QR generation unavailable: GD extension not available and QR service unreachable');
    }
    $modules = 25;
    $padding = intdiv($size, 12);
    $cellSize = intdiv($size - ($padding * 2), $modules);
    $imageSize = $modules * $cellSize + ($padding * 2);
    $image = imagecreatetruecolor($imageSize, $imageSize);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagefilledrectangle($image, 0, 0, $imageSize, $imageSize, $white);
    $hash = crc32($payload);
    for ($row = 0; $row < $modules; $row++) {
        for ($col = 0; $col < $modules; $col++) {
            if ((($row * 7 + $col * 3 + $hash) % 5) < 2) {
                $x1 = $padding + $col * $cellSize;
                $y1 = $padding + $row * $cellSize;
                imagefilledrectangle($image, $x1, $y1, $x1 + $cellSize - 1, $y1 + $cellSize - 1, $black);
            }
        }
    }
    ob_start();
    imagepng($image);
    imagedestroy($image);
    return ob_get_clean();
}

function generateQrCodeUrl(int $residentId, string $residentName): string {
    $payload = buildQrPayload($residentId, $residentName);
    $encoded = urlencode($payload);
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$encoded}";
}

function saveQrCode(int $residentId, string $qrType = 'resident'): string {
    $pdo = getDbConnection();
    $name = '';
    if ($qrType === 'resident') {
        $stmt = $pdo->prepare('SELECT full_name FROM residents WHERE id = ? LIMIT 1');
        $stmt->execute([$residentId]);
        $name = $stmt->fetchColumn() ?: '';
    }

    $payload = buildQrPayload($residentId, $name);
    $qrImage = generateQrImage($payload);

    $qrDir = __DIR__ . '/../assets/uploads/qr';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0777, true);
    }
    $fileName = $qrType . '_' . $residentId . '_' . uniqid() . '.png';
    $relativePath = 'assets/uploads/qr/' . $fileName;
    file_put_contents($qrDir . '/' . $fileName, $qrImage);
    return $relativePath;
}

function generateQrSvg(string $payload): string {
    $modules = 21;
    $cellSize = 10;
    $borderModules = 4;
    $borderPixels = $borderModules * $cellSize;
    $size = $modules * $cellSize + ($borderPixels * 2);
    
    $hash = crc32($payload);
    $data = [];
    for ($row = 0; $row < $modules; $row++) {
        for ($col = 0; $col < $modules; $col++) {
            $data[] = ((($row * $modules) + $col) + $hash) % 3 !== 0 ? 1 : 0;
        }
    }
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';
    
    foreach ($data as $i => $black) {
        if ($black) {
            $x = ($i % $modules + $borderModules) * $cellSize;
            $y = (int)($i / $modules) * $cellSize + $borderPixels;
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cellSize . '" height="' . $cellSize . '" fill="black"/>';
        }
    }
    $svg .= '</svg>';
    return $svg;
}

if (str_replace('\\', '/', __FILE__) === str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'])) {
    while (ob_get_level()) ob_end_clean();

    $type = trim($_GET['type'] ?? '');

    if ($type === 'resident' && isset($_GET['id'])) {
        $residentId = (int) $_GET['id'];
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT full_name FROM residents WHERE id = ? LIMIT 1');
        $stmt->execute([$residentId]);
        $resident = $stmt->fetch();
        if ($resident) {
            $payload = buildQrPayload($residentId, $resident['full_name']);
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=86400');
            echo generateQrImage($payload, 300);
            exit;
        }
    }

    if ($type === 'document' && isset($_GET['id'])) {
        $documentId = (int) $_GET['id'];
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT d.document_number, r.id as resident_id, r.full_name FROM documents d LEFT JOIN residents r ON r.id = d.resident_id WHERE d.id = ? LIMIT 1');
        $stmt->execute([$documentId]);
        $document = $stmt->fetch();
        if ($document) {
            $payload = buildDocumentQrPayload($document['document_number'], $document['resident_id']);
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=86400');
            echo generateQrImage($payload, 300);
            exit;
        }
    }

    http_response_code(400);
    echo 'Invalid request';
}
