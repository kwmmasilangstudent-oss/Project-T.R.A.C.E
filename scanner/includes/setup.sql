-- ============================================================
--  Project T.R.A.C.E. — QR Scanner Module Migration
--  Run this after the existing trace.sql schema.
--  Safe to re-run (uses IF NOT EXISTS / conditional ADD COLUMN).
-- ============================================================

-- ── Extend residents table with scanner-relevant fields ──
SET @db = DATABASE();

ALTER TABLE residents
    ADD COLUMN IF NOT EXISTS qr_code_identifier VARCHAR(255) NULL UNIQUE,
    ADD COLUMN IF NOT EXISTS senior_citizen_id VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS osca_id VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS medical_conditions TEXT NULL,
    ADD COLUMN IF NOT EXISTS blood_type VARCHAR(10) NULL,
    ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS status ENUM('active','inactive','expired') NOT NULL DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill qr_code_identifier for existing residents that do not have one yet
UPDATE residents
SET qr_code_identifier = CONCAT('RES-', YEAR(created_at), '-', LPAD(id, 5, '0'))
WHERE qr_code_identifier IS NULL OR qr_code_identifier = '';

-- Ensure uniqueness after backfill (ignore rows that collide)
-- (collisions are extremely unlikely given id padding)

-- ── Scan logs table ──
CREATE TABLE IF NOT EXISTS scan_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NULL,
    qr_code_scanned VARCHAR(255) NOT NULL,
    scan_result ENUM('success','not_found','inactive','expired','error') NOT NULL,
    scanned_by_user_id INT NOT NULL,
    scanned_by_name VARCHAR(255) NULL,
    remarks TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scan_logs_scanned_at (scanned_at),
    INDEX idx_scan_logs_resident_id (resident_id),
    INDEX idx_scan_logs_result (scan_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ensure the "official" and "encoder" roles are usable ──
-- The users table role column already exists; no schema change required.
-- This comment documents that scanner access is granted to:
--   admin, secretary, encoder, official
