<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google_oauth.php';

if (GOOGLE_CLIENT_ID === '') {
    header('Location: login.php?error=google_not_configured');
    exit;
}

$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    header('Location: login.php?error=google_auth_denied');
    exit;
}

if ($code === '') {
    header('Location: login.php?error=google_no_code');
    exit;
}

$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
];

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: login.php?error=google_token_exchange_failed');
    exit;
}

$token = json_decode($response, true);
$accessToken = $token['access_token'] ?? '';

if ($accessToken === '') {
    header('Location: login.php?error=google_no_access_token');
    exit;
}

$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);
$userInfo = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: login.php?error=google_userinfo_failed');
    exit;
}

$googleUser = json_decode($userInfo, true);
$googleId = $googleUser['id'] ?? '';
$email = $googleUser['email'] ?? '';
$name = $googleUser['name'] ?? '';

if ($email === '') {
    header('Location: login.php?error=google_no_email');
    exit;
}

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ? LIMIT 1');
$stmt->execute([$googleId]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?');
        $stmt->execute([$googleId, $user['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, google_id, role, status) VALUES (?, ?, NULL, ?, ?, "active")');
        $stmt->execute([$name, $email, $googleId, 'resident']);
        $userId = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO residents (user_id, full_name) VALUES (?, ?)')->execute([$userId, $name]);
        $user = [
            'id' => $userId,
            'full_name' => $name,
            'email' => $email,
            'role' => 'resident',
            'status' => 'active',
        ];
    }
}

if ($user['status'] !== 'active') {
    header('Location: login.php?error=account_inactive');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['full_name'];

header('Location: ' . BASE_URL . '/index.php');
exit;
