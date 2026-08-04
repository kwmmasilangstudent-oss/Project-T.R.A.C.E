<?php
// Set BASE_URL: Empty string for Railway/Production, '/FinalTrace' for local XAMPP
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('RAILWAY_ENVIRONMENT') ? '' : '/FinalTrace');
}

// Application constants
const APP_NAME = 'Project T.R.A.C.E.';
const APP_VERSION = '1.0.0';

// Database constants (Fallback to local XAMPP if Railway ENV variables aren't present)
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'trace_db');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
?>