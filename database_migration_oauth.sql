-- OAuth Authentication Database Migration for PUNKtionary
-- Run this on your MySQL database (prod_punk)

-- 1. Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oauth_provider ENUM('google', 'facebook', 'apple') NOT NULL,
    oauth_provider_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    profile_picture_url VARCHAR(512),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,

    UNIQUE KEY unique_provider_user (oauth_provider, oauth_provider_id),
    INDEX idx_email (email),
    INDEX idx_provider (oauth_provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create oauth_states table for CSRF protection
CREATE TABLE IF NOT EXISTS oauth_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_token VARCHAR(64) NOT NULL,
    provider ENUM('google', 'facebook', 'apple') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,

    UNIQUE KEY unique_state (state_token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add user attribution columns to bands table
ALTER TABLE bands
ADD COLUMN submitted_by INT NULL AFTER id,
ADD COLUMN edited_by INT NULL,
ADD FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL;

-- 4. Add user attribution columns to venues table
ALTER TABLE venues
ADD COLUMN submitted_by INT NULL AFTER id,
ADD COLUMN edited_by INT NULL,
ADD FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL;

-- 5. Add user attribution columns to resources table
ALTER TABLE resources
ADD COLUMN submitted_by INT NULL AFTER id,
ADD COLUMN edited_by INT NULL,
ADD FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL;

-- Note: Existing submissions will have submitted_by = NULL (anonymous/legacy content)
-- This is intentional - we cannot retroactively attribute submissions
