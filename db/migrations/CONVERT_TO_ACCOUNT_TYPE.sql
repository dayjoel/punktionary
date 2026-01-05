-- Run this in phpMyAdmin to convert from is_admin to account_type
-- This adds a hierarchical permission system:
-- 0 = user (regular user)
-- 1 = admin (can review edits, manage users)
-- 2 = god (full privileges, cannot be demoted by admins)

-- Add new account_type column
ALTER TABLE users ADD COLUMN account_type TINYINT(1) DEFAULT 0 AFTER email;

-- Copy existing is_admin values to account_type (0 or 1)
UPDATE users SET account_type = is_admin WHERE is_admin IS NOT NULL;

-- Drop old is_admin column
ALTER TABLE users DROP COLUMN is_admin;

-- IMPORTANT: Set yourself as god (update with your actual user ID)
-- UPDATE users SET account_type = 2 WHERE id = YOUR_USER_ID;
