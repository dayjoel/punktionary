-- Migration: Create pending_carousel_news table
-- This table stores user-submitted news items pending admin review

CREATE TABLE IF NOT EXISTS pending_carousel_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submitted_url VARCHAR(500) NOT NULL,
    scraped_title VARCHAR(255),
    scraped_description TEXT,
    scraped_image_url VARCHAR(500),
    submitted_by INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
