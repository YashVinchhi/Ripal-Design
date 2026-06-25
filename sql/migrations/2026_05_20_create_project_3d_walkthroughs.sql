-- Migration: GLB model walkthroughs

CREATE TABLE IF NOT EXISTS `project_3d_models` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `uploaded_by` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `stored_path` TEXT NOT NULL,
  `mime` VARCHAR(120) NULL,
  `size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `camera_position` VARCHAR(120) NULL,
  `camera_target` VARCHAR(120) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_3d_models_project_slug` (`project_id`, `slug`),
  KEY `idx_project_3d_models_project` (`project_id`),
  KEY `idx_project_3d_models_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_3d_hotspots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `position_x` DECIMAL(12,6) NOT NULL,
  `position_y` DECIMAL(12,6) NOT NULL,
  `position_z` DECIMAL(12,6) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_3d_hotspots_model` (`model_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_3d_camera_points` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `position_x` DECIMAL(12,6) NOT NULL,
  `position_y` DECIMAL(12,6) NOT NULL,
  `position_z` DECIMAL(12,6) NOT NULL,
  `target_x` DECIMAL(12,6) NOT NULL,
  `target_y` DECIMAL(12,6) NOT NULL,
  `target_z` DECIMAL(12,6) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_project_3d_camera_points_model` (`model_id`, `sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
