-- Migration: Tours, scenes and panorama hotspot links

CREATE TABLE IF NOT EXISTS `project_tours` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_tours_project` (`project_id`),
  KEY `idx_project_tours_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tour_scenes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id` BIGINT UNSIGNED NOT NULL,
  `project_file_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `initial_yaw` DECIMAL(10,6) NOT NULL DEFAULT 0,
  `initial_pitch` DECIMAL(10,6) NOT NULL DEFAULT 0,
  `initial_hfov` DECIMAL(10,6) NOT NULL DEFAULT 100,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tour_scenes_tour` (`tour_id`),
  KEY `idx_tour_scenes_file` (`project_file_id`),
  KEY `idx_tour_scenes_order` (`tour_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tour_hotspots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` BIGINT UNSIGNED NOT NULL,
  `hotspot_type` VARCHAR(32) NOT NULL DEFAULT 'link',
  `yaw` DECIMAL(10,6) NOT NULL,
  `pitch` DECIMAL(10,6) NOT NULL,
  `target_scene_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NULL,
  `content_html` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tour_hotspots_scene` (`scene_id`),
  KEY `idx_tour_hotspots_target` (`target_scene_id`),
  KEY `idx_tour_hotspots_type` (`hotspot_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
