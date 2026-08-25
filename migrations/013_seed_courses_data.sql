-- Migration 013: Ensure schema columns and seed courses data
ALTER TABLE `courses` ADD COLUMN IF NOT EXISTS `subject_id` BIGINT UNSIGNED NULL AFTER `category_id`;
ALTER TABLE `courses` ADD COLUMN IF NOT EXISTS `created_by_type` ENUM('admin', 'teacher') NOT NULL DEFAULT 'admin' AFTER `is_active`;
ALTER TABLE `courses` ADD COLUMN IF NOT EXISTS `created_by_id` BIGINT UNSIGNED NULL AFTER `created_by_type`;

CREATE TABLE IF NOT EXISTS `course_teachers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_course_teachers` (`course_id`, `teacher_id`),
    KEY `idx_ct_course` (`course_id`),
    KEY `idx_ct_teacher` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing sample courses with subject_id, created_by_type, created_by_id
UPDATE `courses` SET `subject_id` = 1, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 1;
UPDATE `courses` SET `subject_id` = 2, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 2;
UPDATE `courses` SET `subject_id` = 3, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 3;
UPDATE `courses` SET `subject_id` = 4, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 4;
UPDATE `courses` SET `subject_id` = 5, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 5;
UPDATE `courses` SET `subject_id` = 6, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 6;
UPDATE `courses` SET `subject_id` = 1, `created_by_type` = 'teacher', `created_by_id` = 4 WHERE `id` = 7;
UPDATE `courses` SET `subject_id` = 4, `created_by_type` = 'teacher', `created_by_id` = 3 WHERE `id` = 8;
UPDATE `courses` SET `subject_id` = 6, `created_by_type` = 'teacher', `created_by_id` = 2 WHERE `id` = 9;
UPDATE `courses` SET `subject_id` = 4, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 10;
UPDATE `courses` SET `subject_id` = 1, `created_by_type` = 'teacher', `created_by_id` = 4 WHERE `id` = 11;
UPDATE `courses` SET `subject_id` = 5, `created_by_type` = 'admin', `created_by_id` = 1 WHERE `id` = 12;

-- Populate course_teachers pivot table
INSERT IGNORE INTO `course_teachers` (`course_id`, `teacher_id`) VALUES
(1, 4), (1, 1),
(2, 5), (2, 2),
(3, 6), (3, 3),
(4, 3), (4, 4),
(5, 1),
(6, 2),
(7, 4), (7, 5),
(8, 3),
(9, 2),
(10, 3),
(11, 4),
(12, 1);
