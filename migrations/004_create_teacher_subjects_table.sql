-- Migration 004: Make teachers.subject_id nullable and create teacher_subjects pivot table

ALTER TABLE `teachers` MODIFY COLUMN `subject_id` BIGINT UNSIGNED NULL;
ALTER TABLE `teachers` MODIFY COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `teacher_subjects` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_teacher_subjects` (`teacher_id`, `subject_id`),
    KEY `idx_teacher_subjects_subject` (`subject_id`),
    CONSTRAINT `fk_teacher_subjects_teacher`
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_teacher_subjects_subject`
        FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
