-- Notifications enhancement migration
-- Adds metadata columns and indexes for robust role-based notifications.

SET @db_name = DATABASE();

SET @has_actor_user_id = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'actor_user_id'
);
SET @sql = IF(@has_actor_user_id = 0,
  'ALTER TABLE notifications ADD COLUMN actor_user_id INT NULL AFTER body',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_project_id = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'project_id'
);
SET @sql = IF(@has_project_id = 0,
  'ALTER TABLE notifications ADD COLUMN project_id INT NULL AFTER actor_user_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_entity_type = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'entity_type'
);
SET @sql = IF(@has_entity_type = 0,
  'ALTER TABLE notifications ADD COLUMN entity_type VARCHAR(50) NULL AFTER project_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_entity_id = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'entity_id'
);
SET @sql = IF(@has_entity_id = 0,
  'ALTER TABLE notifications ADD COLUMN entity_id INT NULL AFTER entity_type',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_action_key = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'action_key'
);
SET @sql = IF(@has_action_key = 0,
  'ALTER TABLE notifications ADD COLUMN action_key VARCHAR(100) NULL AFTER entity_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_deep_link = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'deep_link'
);
SET @sql = IF(@has_deep_link = 0,
  'ALTER TABLE notifications ADD COLUMN deep_link VARCHAR(500) NULL AFTER action_key',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_metadata_json = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'metadata_json'
);
SET @sql = IF(@has_metadata_json = 0,
  'ALTER TABLE notifications ADD COLUMN metadata_json JSON NULL AFTER deep_link',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_read_at = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'read_at'
);
SET @sql = IF(@has_read_at = 0,
  'ALTER TABLE notifications ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL AFTER is_read',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk_actor = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_notifications_actor'
);
SET @sql = IF(@has_fk_actor = 0,
  'ALTER TABLE notifications ADD CONSTRAINT fk_notifications_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk_project = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_notifications_project'
);
SET @sql = IF(@has_fk_project = 0,
  'ALTER TABLE notifications ADD CONSTRAINT fk_notifications_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_user_read_created = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_user_read_created'
);
SET @sql = IF(@has_idx_user_read_created = 0,
  'CREATE INDEX idx_notifications_user_read_created ON notifications (user_id, is_read, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_project_created = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_project_created'
);
SET @sql = IF(@has_idx_project_created = 0,
  'CREATE INDEX idx_notifications_project_created ON notifications (project_id, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_action_created = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_action_created'
);
SET @sql = IF(@has_idx_action_created = 0,
  'CREATE INDEX idx_notifications_action_created ON notifications (action_key, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
