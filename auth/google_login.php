<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/google_oauth.php';

if (GOOGLE_CLIENT_ID === '') {
    header('Location: login.php?error=google_not_configured');
    exit;
}

$params = http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
