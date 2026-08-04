<?php
/**
 * Migration: Add security questions to users table
 * Run this once: php migrations/add_security_questions.php
 */
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDbConnection();
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'security_question'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN security_question VARCHAR(255) NULL AFTER status");
        $pdo->exec("ALTER TABLE users ADD COLUMN security_answer VARCHAR(255) NULL AFTER security_question");
        echo "Migration complete: security_question and security_answer columns added.\n";
    } else {
        echo "Migration skipped: columns already exist.\n";
    }
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
