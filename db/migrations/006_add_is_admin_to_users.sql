-- Add is_admin field to users table
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER email;
