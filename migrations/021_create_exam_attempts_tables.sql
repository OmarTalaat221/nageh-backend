SET FOREIGN_KEY_CHECKS = 0;

-- NOTE: `questions.exam_id` still carries an old foreign key (`fk_questions_exam`) pointing at the
-- legacy `exams` table, but every exam CRUD endpoint (admin/teacher) actually reads and writes the
-- newer `course_exams` table (see migration 014). That mismatch means creating a question against a
-- real course_exams.id can fail (or silently misbehave) once IDs diverge between the two tables.
-- This repoints the constraint at the table that's actually in use. If the constraint name differs
-- on your server, check it first with:
--   SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
--   WHERE TABLE_NAME = 'questions' AND COLUMN_NAME = 'exam_id' AND REFERENCED_TABLE_NAME IS NOT NULL;
-- and adjust the DROP line below to match before running this file.
ALTER TABLE `questions` DROP FOREIGN KEY `fk_questions_exam`;
ALTER TABLE `questions` ADD CONSTRAINT `fk_questions_course_exam`
    FOREIGN KEY (`exam_id`) REFERENCES `course_exams` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS `exam_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('in_progress', 'submitted', 'graded') NOT NULL DEFAULT 'in_progress',
    `total_score` DECIMAL(8,2) NULL,
    `max_score` DECIMAL(8,2) NOT NULL DEFAULT 0,
    `started_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `submitted_at` DATETIME NULL,
    `graded_at` DATETIME NULL,
    `graded_by_type` ENUM('admin', 'teacher') NULL,
    `graded_by_id` BIGINT UNSIGNED NULL,
    UNIQUE KEY `uq_exam_attempt_student` (`exam_id`, `student_id`),
    KEY `idx_ea_exam` (`exam_id`),
    KEY `idx_ea_student` (`student_id`),
    KEY `idx_ea_status` (`status`),
    CONSTRAINT `fk_exam_attempts_exam`
        FOREIGN KEY (`exam_id`) REFERENCES `course_exams` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_exam_attempts_student`
        FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_attempt_answers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `mcq_option_id` BIGINT UNSIGNED NULL,
    `true_false_answer` TINYINT(1) NULL,
    `written_answer_text` TEXT NULL,
    `is_correct` TINYINT(1) NULL,
    `score_awarded` DECIMAL(8,2) NULL,
    `teacher_note` TEXT NULL,
    `answered_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_attempt_question` (`attempt_id`, `question_id`),
    KEY `idx_eaa_attempt` (`attempt_id`),
    KEY `idx_eaa_question` (`question_id`),
    CONSTRAINT `fk_exam_attempt_answers_attempt`
        FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_exam_attempt_answers_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
