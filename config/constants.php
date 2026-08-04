<?php
function getEnvVar(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    $value = $_ENV[$key] ?? null;
    if (is_string($value) && $value !== '') {
        return $value;
    }

    $value = $_SERVER[$key] ?? null;
    if (is_string($value) && $value !== '') {
        return $value;
    }

    return $default;
}

function getBaseUrl(): string {
    $configuredUrl = getEnvVar('APP_URL', '');
    if ($configuredUrl !== '') {
        return rtrim($configuredUrl, '/');
    }

    $railwayDomain = getEnvVar('RAILWAY_PUBLIC_DOMAIN', '');
    if ($railwayDomain !== '') {
        $scheme = strtolower(getEnvVar('FORWARDED_PROTO', getEnvVar('REQUEST_SCHEME', 'https')));
        $scheme = $scheme === 'http' ? 'http' : 'https';
        return $scheme . '://' . $railwayDomain;
    }

    $isRailway = !empty(getEnvVar('RAILWAY_ENVIRONMENT', '')) || !empty(getEnvVar('RAILWAY_STATIC_URL', ''));
    return $isRailway ? '' : '/FinalTrace';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', getBaseUrl());
}

// App configuration
const APP_NAME = 'Project T.R.A.C.E.';
const APP_VERSION = '1.0.0';

$databaseUrl = getEnvVar('DATABASE_URL', getEnvVar('DB_URL', getEnvVar('MYSQL_URL', '')));
if ($databaseUrl !== '') {
    $parsed = parse_url($databaseUrl);
    if (isset($parsed['host'])) {
        define('DB_HOST', $parsed['host']);
    } else {
        define('DB_HOST', getEnvVar('MYSQLHOST', getEnvVar('DB_HOST', '127.0.0.1')));
    }

    if (isset($parsed['port'])) {
        define('DB_PORT', (string) $parsed['port']);
    } else {
        define('DB_PORT', getEnvVar('MYSQLPORT', getEnvVar('DB_PORT', '3306')));
    }

    if (isset($parsed['path'])) {
        define('DB_NAME', ltrim($parsed['path'], '/'));
    } else {
        define('DB_NAME', getEnvVar('MYSQLDATABASE', getEnvVar('DB_NAME', 'trace_db')));
    }

    if (isset($parsed['user'])) {
        define('DB_USER', $parsed['user']);
    } else {
        define('DB_USER', getEnvVar('MYSQLUSER', getEnvVar('DB_USER', 'root')));
    }

    if (isset($parsed['pass'])) {
        define('DB_PASS', $parsed['pass']);
    } else {
        define('DB_PASS', getEnvVar('MYSQLPASSWORD', getEnvVar('DB_PASS', '')));
    }
} else {
    define('DB_HOST', getEnvVar('MYSQLHOST', getEnvVar('DB_HOST', '127.0.0.1')));
    define('DB_PORT', getEnvVar('MYSQLPORT', getEnvVar('DB_PORT', '3306')));
    define('DB_NAME', getEnvVar('MYSQLDATABASE', getEnvVar('DB_NAME', 'trace_db')));
    define('DB_USER', getEnvVar('MYSQLUSER', getEnvVar('DB_USER', 'root')));
    define('DB_PASS', getEnvVar('MYSQLPASSWORD', getEnvVar('DB_PASS', '')));
}
?>