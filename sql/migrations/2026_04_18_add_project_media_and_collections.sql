-- Migration: Add project publish flags, extend project_files, and create collections/likes
-- Run this file with your DB migration tool or `mysql < file` after review.

-- 1) Add publish flags to projects
ALTER TABLE `projects`
  ADD COLUMN `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `published_at` DATETIME NULL DEFAULT NULL;

-- 2) Extend project_files to include media type, metadata, ordering and privacy
ALTER TABLE `project_files`
  ADD COLUMN `media_type` VARCHAR(32) NOT NULL DEFAULT 'IMAGE',
  ADD COLUMN `meta` JSON NULL,
  ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0,
  ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 1;

-- 3) Create project_likes table for appreciates
CREATE TABLE IF NOT EXISTS `project_likes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_project_user` (`project_id`,`user_id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Create collections and collection_items for user inspiration collections
CREATE TABLE IF NOT EXISTS `collections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `visibility` ENUM('private','shared','public') NOT NULL DEFAULT 'private',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `collection_id` BIGINT UNSIGNED NOT NULL,
  `project_file_id` BIGINT UNSIGNED NOT NULL,
  `added_by` BIGINT UNSIGNED NOT NULL,
  `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_collection` (`collection_id`),
  KEY `idx_project_file` (`project_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
