-- Run these migrations in phpMyAdmin for the edit system
-- Migration 005: Create pending_edits table
CREATE TABLE IF NOT EXISTS pending_edits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('band', 'venue', 'resource') NOT NULL,
  entity_id INT NOT NULL,
  submitted_by INT NOT NULL,
  field_changes JSON NOT NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  admin_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  reviewed_by INT NULL,
  FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_status (status),
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_submitted_by (submitted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 006: Add is_admin field to users table
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER email;

-- Set yourself as admin (update with your actual user ID)
-- UPDATE users SET is_admin = 1 WHERE id = YOUR_USER_ID;
