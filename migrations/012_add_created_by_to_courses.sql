-- Migration 012: Add created_by_type and created_by_id to courses table
SET @dbname = DATABASE();
SET @tablename = "courses";
SET @columnname = "created_by_type";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `courses` ADD COLUMN `created_by_type` ENUM('admin', 'teacher') NOT NULL DEFAULT 'admin' AFTER `is_active`, ADD COLUMN `created_by_id` BIGINT UNSIGNED NULL AFTER `created_by_type`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
