-- Migration 068: let each course decide whether its videos can be downloaded.
--
-- `course_books.allow_download` already exists for book files; this is the same idea for a
-- course's videos. The default is 0 (watch inside the platform only) rather than 1, because the
-- protective choice is the one that must not depend on someone remembering to tick a box.

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'courses'
      AND COLUMN_NAME = 'allow_video_download'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `courses` ADD COLUMN `allow_video_download` TINYINT(1) NOT NULL DEFAULT 0 AFTER `duration_months`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
