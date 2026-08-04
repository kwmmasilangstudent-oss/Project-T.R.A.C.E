<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    switch (getCurrentRole()) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        case 'secretary':
            header('Location: ' . BASE_URL . '/secretary/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/resident/dashboard.php');
            break;
    }
    exit;
}

header('Location: ' . BASE_URL . '/landing/home.php');
exit;