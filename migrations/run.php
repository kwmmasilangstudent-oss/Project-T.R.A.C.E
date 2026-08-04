<?php
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_version (
    version INT PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $pdo->query('SELECT MAX(version) FROM schema_version');
$applied = (int) $stmt->fetchColumn();

if ($applied >= 1) return;

try {
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS resident_type VARCHAR(50) NULL DEFAULT 'regular'");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS qr_code_path VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS photo_url VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS philsys_pcn VARCHAR(19) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS suffix VARCHAR(10) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS birthplace VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS citizenship VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS religion VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS ethnicity VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS household_members INT DEFAULT 1");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS house_number VARCHAR(50) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS street_name VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS purok_sitio_id INT NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS housing_material VARCHAR(50) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS tenure_status VARCHAR(20) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS drinking_water_source VARCHAR(50) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS toilet_facility_type VARCHAR(50) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS educational_attainment VARCHAR(50) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS primary_occupation VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS employment_status VARCHAR(20) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS monthly_household_income DECIMAL(12,2) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_senior_citizen TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_pwd TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS pwd_disability_type VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_solo_parent TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_ofw TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE residents ADD COLUMN IF NOT EXISTS is_indigent TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE landing_officials ADD COLUMN IF NOT EXISTS committee VARCHAR(255) DEFAULT ''");
    $pdo->exec("ALTER TABLE landing_content ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL");
    $pdo->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL");
    $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'general'");
    $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS priority VARCHAR(20) DEFAULT 'normal'");
    $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");
    $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS event_time TIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS end_date DATE DEFAULT NULL");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS end_time TIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS location VARCHAR(500) DEFAULT ''");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS event_type VARCHAR(50) DEFAULT 'general'");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");
    $pdo->exec("INSERT INTO schema_version (version) VALUES (1)");
} catch (Throwable $e) {
    // DDL auto-commits in MySQL, so transactions are not used here.
    // ADD COLUMN IF NOT EXISTS is idempotent, so partial failures are safe.
}
