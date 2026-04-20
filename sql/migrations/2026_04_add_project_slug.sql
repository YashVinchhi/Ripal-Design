-- Migration: add slug column to projects
ALTER TABLE `projects`
  ADD COLUMN `slug` VARCHAR(255) NULL UNIQUE AFTER `name`;

-- Optional: create index for faster lookup
CREATE UNIQUE INDEX IF NOT EXISTS `idx_projects_slug` ON `projects` (`slug`);
