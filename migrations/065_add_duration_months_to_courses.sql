-- Migration 065: give a package a subscription length.
--
-- A "package" is a row in `courses` with kind = 'package'. Until now nothing on it said how long
-- a subscription to it lasts, so a package subscription created by an admin was stored with
-- end_date = NULL (open ended) unless the caller happened to pass duration_days by hand. This
-- column is the package's own answer: 1, 3, 6 or 12 months. NULL means "not set" and is what
-- every ordinary (non-package) course keeps.

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'courses'
      AND COLUMN_NAME = 'duration_months'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `courses` ADD COLUMN `duration_months` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `kind`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
