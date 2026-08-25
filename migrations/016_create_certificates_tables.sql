SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `academic_year_id` BIGINT UNSIGNED NULL,
    `subject_id` BIGINT UNSIGNED NULL,
    `course_id` BIGINT UNSIGNED NULL,
    `code` VARCHAR(100) NOT NULL UNIQUE,
    `score` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `certificate_type` VARCHAR(50) NOT NULL DEFAULT 'general',
    `image` VARCHAR(500) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by_type` ENUM('admin', 'teacher') NOT NULL DEFAULT 'admin',
    `created_by_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_cert_academic_year` (`academic_year_id`),
    KEY `idx_cert_subject` (`subject_id`),
    KEY `idx_cert_course` (`course_id`),
    KEY `idx_cert_type` (`certificate_type`),
    KEY `idx_cert_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `certificates_translations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_id` BIGINT UNSIGNED NOT NULL,
    `language_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    UNIQUE KEY `uq_certificates_lang` (`certificate_id`, `language_id`),
    KEY `idx_cert_trans_certificate` (`certificate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `certificate_teachers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_certificate_teachers` (`certificate_id`, `teacher_id`),
    KEY `idx_ct_cert` (`certificate_id`),
    KEY `idx_ct_teacher` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `course_id` BIGINT UNSIGNED NULL,
    `exam_id` BIGINT UNSIGNED NULL,
    `certificate_code` VARCHAR(100) NOT NULL UNIQUE,
    `score_achieved` DECIMAL(8,2) NULL,
    `issue_date` DATE NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sc_cert` (`certificate_id`),
    KEY `idx_sc_student` (`student_id`),
    KEY `idx_sc_course` (`course_id`),
    KEY `idx_sc_exam` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
