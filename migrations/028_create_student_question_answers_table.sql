SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `student_question_answers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `unit_id` BIGINT UNSIGNED NULL,
    `course_id` BIGINT UNSIGNED NULL,
    `mcq_option_id` BIGINT UNSIGNED NULL,
    `true_false_answer` TINYINT(1) NULL,
    `written_answer_text` TEXT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    `score_awarded` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sqa_student` (`student_id`),
    KEY `idx_sqa_question` (`question_id`),
    KEY `idx_sqa_unit` (`unit_id`),
    KEY `idx_sqa_course` (`course_id`),
    KEY `idx_sqa_student_question` (`student_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
