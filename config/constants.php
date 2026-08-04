<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', (isset($_ENV['RAILWAY_ENVIRONMENT']) || isset($_SERVER['RAILWAY_ENVIRONMENT'])) ? '' : '/FinalTrace');
}

// Application constants
const APP_NAME = 'Project T.R.A.C.E.';
const APP_VERSION = '1.0.0';

// Helper function to read environment variables robustly
function getEnvVar(string $key, string $default = ''): string {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

// Database constants
define('DB_HOST', getEnvVar('MYSQLHOST', '127.0.0.1'));
define('DB_PORT', getEnvVar('MYSQLPORT', '3306'));
define('DB_NAME', getEnvVar('MYSQLDATABASE', 'trace_db'));
define('DB_USER', getEnvVar('MYSQLUSER', 'root'));
define('DB_PASS', getEnvVar('MYSQLPASSWORD', ''));
?>