-- Migration: Create carousel_items table
-- This table stores featured content for the homepage carousel

CREATE TABLE IF NOT EXISTS carousel_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500) NOT NULL,
    link_url VARCHAR(500),
    display_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    publish_date DATE,
    expire_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active (active),
    INDEX idx_display_order (display_order),
    INDEX idx_publish_date (publish_date),
    INDEX idx_expire_date (expire_date)
);
