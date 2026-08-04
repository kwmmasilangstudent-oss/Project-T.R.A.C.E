-- ============================================================
--  Project T.R.A.C.E. — Event & Attendance Schema Migration
--  Run this after the existing trace.sql schema.
--  Safe to re-run (uses IF NOT EXISTS / conditional ADD COLUMN).
-- ============================================================

SET @db = DATABASE();

-- ── Extend agenda table with event/calendar fields ──
ALTER TABLE agenda
    ADD COLUMN IF NOT EXISTS event_type VARCHAR(50) DEFAULT 'meeting',
    ADD COLUMN IF NOT EXISTS is_scannable TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS qr_session_token VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS scan_mode ENUM('open','closed','invited') DEFAULT 'open',
    ADD COLUMN IF NOT EXISTS expected_attendees INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS checkin_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill event_type based on meeting_type for existing rows
UPDATE agenda
SET event_type = LOWER(meeting_type)
WHERE event_type IS NULL OR event_type = ''
  AND meeting_type IS NOT NULL AND meeting_type != '';

-- ── Dedicated scan logs per agenda/event ──
CREATE TABLE IF NOT EXISTS agenda_scan_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agenda_id INT NOT NULL,
    resident_id INT NULL,
    scan_result ENUM('success','not_found','inactive','expired','error') NOT NULL,
    scanned_by_user_id INT NOT NULL,
    scanned_by_name VARCHAR(255) NULL,
    remarks TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agenda_scans_agenda_id (agenda_id),
    INDEX idx_agenda_scans_scanned_at (scanned_at),
    INDEX idx_agenda_scans_resident_id (resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Event invitees list (for closed/invited mode) ──
CREATE TABLE IF NOT EXISTS agenda_invitees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agenda_id INT NOT NULL,
    resident_id INT NOT NULL,
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_agenda_resident (agenda_id, resident_id),
    INDEX idx_agenda_invitees_resident (resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
