<?php
require_once __DIR__ . '/constants.php';

function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Displays the actual connection issue instead of throwing a 503
            die("Database Connection Error: " . $e->getMessage() . " | Host: " . DB_HOST . " | DB: " . DB_NAME);
        }
    }

    return $pdo;
}
?>