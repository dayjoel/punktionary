-- Migration 007: Change is_admin to account_type with three levels
-- 0 = user, 1 = admin, 2 = god

-- Add new account_type column
ALTER TABLE users ADD COLUMN account_type TINYINT(1) DEFAULT 0 AFTER email;

-- Copy existing is_admin values to account_type
UPDATE users SET account_type = is_admin WHERE is_admin IS NOT NULL;

-- Drop old is_admin column
ALTER TABLE users DROP COLUMN is_admin;

-- Set yourself as god (update with your actual user ID)
-- UPDATE users SET account_type = 2 WHERE id = YOUR_USER_ID;
