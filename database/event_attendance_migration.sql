-- ============================================================
--  Project T.R.A.C.E. — Event Attendance Schema Additions
--  Enables automatic, de-duplicated logging of scanned
--  senior residents per event (agenda).
--  Safe to re-run (uses IF NOT EXISTS / conditional ADD COLUMN).
-- ============================================================

-- 1) Mark residents as senior citizens so attendance pages can
--    reliably filter "senior residents only".
ALTER TABLE residents
    ADD COLUMN IF NOT EXISTS is_senior TINYINT(1) NOT NULL DEFAULT 0;

-- 2) Ensure each (event, resident) pair is logged at most once so
--    re-scans do not double-count attendance. Existing duplicates
--    are kept but the unique key prevents new ones.
ALTER TABLE agenda_scan_logs
    ADD UNIQUE KEY IF NOT EXISTS unique_agenda_resident_scan (agenda_id, resident_id);

-- 3) Backfill is_senior for residents who already have a Senior
--    Citizen / OSCA id (best-effort; safe if none match).
UPDATE residents
SET is_senior = 1
WHERE is_senior = 0
  AND (senior_citizen_id IS NOT NULL AND senior_citizen_id != '');
