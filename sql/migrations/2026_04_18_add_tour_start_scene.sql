-- Migration: Add start scene reference on project_tours
ALTER TABLE `project_tours`
  ADD COLUMN `start_scene_id` BIGINT UNSIGNED NULL AFTER `description`;

ALTER TABLE `project_tours`
  ADD KEY `idx_project_tours_start_scene` (`start_scene_id`);
