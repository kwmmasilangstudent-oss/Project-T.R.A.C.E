-- Migration: Seed per-role theme defaults
-- Run this once to populate role-specific theme settings.
-- The old global `theme` key is left intact for backward compatibility.

INSERT INTO settings (key_name, key_value, created_at, updated_at)
VALUES
    ('theme_admin', 'light', NOW(), NOW()),
    ('theme_secretary', 'light', NOW(), NOW()),
    ('theme_resident', 'light', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    key_value = VALUES(key_value),
    updated_at = NOW();
