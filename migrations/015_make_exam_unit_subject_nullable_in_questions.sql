SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `questions` MODIFY COLUMN `exam_id` BIGINT UNSIGNED NULL;
ALTER TABLE `questions` MODIFY COLUMN `course_id` BIGINT UNSIGNED NULL;
ALTER TABLE `questions` MODIFY COLUMN `unit_id` BIGINT UNSIGNED NULL;

ALTER TABLE `questions` ADD COLUMN `subject_id` BIGINT UNSIGNED NULL AFTER `course_id`;
ALTER TABLE `questions` ADD COLUMN `created_by_type` ENUM('admin', 'teacher') NOT NULL DEFAULT 'admin' AFTER `is_active`;
ALTER TABLE `questions` ADD COLUMN `created_by_id` BIGINT UNSIGNED NULL AFTER `created_by_type`;

CREATE TABLE IF NOT EXISTS `question_teachers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_question_teachers` (`question_id`, `teacher_id`),
    KEY `idx_qt_question` (`question_id`),
    KEY `idx_qt_teacher` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
