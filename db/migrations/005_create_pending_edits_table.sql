-- Create pending_edits table for storing suggested changes
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
