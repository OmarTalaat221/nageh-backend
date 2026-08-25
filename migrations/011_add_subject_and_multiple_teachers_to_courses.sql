-- Migration 011: Add subject_id to courses and create course_teachers pivot table for multiple teachers
ALTER TABLE `courses` MODIFY COLUMN `category_id` BIGINT UNSIGNED NULL;
ALTER TABLE `courses` MODIFY COLUMN `teacher_id` BIGINT UNSIGNED NULL;

-- Add subject_id if not exists
SET @dbname = DATABASE();
SET @tablename = "courses";
SET @columnname = "subject_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `courses` ADD COLUMN `subject_id` BIGINT UNSIGNED NULL AFTER `category_id`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Create course_teachers pivot table
CREATE TABLE IF NOT EXISTS `course_teachers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_course_teachers` (`course_id`, `teacher_id`),
    KEY `idx_ct_course` (`course_id`),
    KEY `idx_ct_teacher` (`teacher_id`),
    CONSTRAINT `fk_ct_course`
        FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_ct_teacher`
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync existing teacher_id into course_teachers
INSERT IGNORE INTO `course_teachers` (`course_id`, `teacher_id`)
SELECT `id`, `teacher_id` FROM `courses` WHERE `teacher_id` IS NOT NULL AND `teacher_id` > 0;
