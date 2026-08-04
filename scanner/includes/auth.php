<?php
require_once __DIR__ . '/db.php';

function scannerRequireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        if (isApiRequest()) {
            jsonError('Authentication required.', 401);
        }
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    $role = $_SESSION['role'] ?? '';
    $allowed = ['admin', 'secretary', 'encoder', 'official'];
    if (!in_array($role, $allowed, true)) {
        if (isApiRequest()) {
            jsonError('You are not authorized to use the scanner.', 403);
        }
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    $limit = 30 * 60;
    $now = time();
    if (!empty($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > $limit) {
        session_unset();
        session_destroy();
        if (isApiRequest()) {
            jsonError('Session expired. Please sign in again.', 401);
        }
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function isApiRequest(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    if (str_contains($accept, 'application/json')) {
        return true;
    }
    if (str_contains($ct, 'application/json')) {
        return true;
    }
    return false;
}

function scannerUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function scannerUserName(): string {
    return $_SESSION['name'] ?? 'Unknown Official';
}

function jsonSuccess(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $code = 400, array $extra = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
