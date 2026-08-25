-- Migration 010: Create subject_academic_years pivot table for many-to-many relationship
CREATE TABLE IF NOT EXISTS `subject_academic_years` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `academic_year_id` BIGINT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_subject_academic_year` (`subject_id`, `academic_year_id`),
    KEY `idx_say_subject` (`subject_id`),
    KEY `idx_say_academic_year` (`academic_year_id`),
    CONSTRAINT `fk_say_subject`
        FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_say_academic_year`
        FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
