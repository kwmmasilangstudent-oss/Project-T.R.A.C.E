<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0);

$forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'] ?? ''));
$isSecureRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']);
if ($isSecureRequest) {
    ini_set('session.cookie_secure', 1);
}

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['_session_regenerated']) || (time() - $_SESSION['_session_regenerated']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['_session_regenerated'] = time();
}

if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['_csrf_token_time'] = time();
}
