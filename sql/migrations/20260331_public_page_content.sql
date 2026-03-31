-- Migration: Public page content CMS table
-- Date: 2026-03-31

CREATE TABLE IF NOT EXISTS public_page_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_slug VARCHAR(100) NOT NULL,
    section_key VARCHAR(150) NOT NULL,
    content_value MEDIUMTEXT DEFAULT NULL,
    content_format ENUM('plain', 'html') NOT NULL DEFAULT 'plain',
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_public_page_content (page_slug, section_key),
    INDEX idx_public_page_slug (page_slug),
    INDEX idx_public_page_updated_by (updated_by),
    CONSTRAINT fk_public_page_content_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
