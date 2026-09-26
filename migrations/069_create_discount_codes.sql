-- Migration 069: discount codes for course and package subscriptions.
--
-- These are NOT `subscription_codes`. A subscription code opens access outright and is free; a
-- discount code only reduces a price and grants nothing by itself. The two are deliberately
-- separate tables so neither flow can accidentally hand out access or money.
--
-- `discount_code_redemptions` records an actual application (an admin granting a subscription
-- with the code). Merely checking a code from the student's screen is a read: it must not consume
-- a use, or a student refreshing a page would burn the code's quota.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `discount_codes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `max_discount_amount` DECIMAL(10,2) NULL COMMENT 'Caps a percentage discount; ignored for fixed',
    `applies_to` ENUM('all', 'selected') NOT NULL DEFAULT 'all',
    `max_uses` INT UNSIGNED NULL COMMENT 'NULL = unlimited',
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `once_per_student` TINYINT(1) NOT NULL DEFAULT 1,
    `valid_from` DATETIME NULL,
    `valid_to` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by_type` ENUM('admin', 'teacher') NOT NULL DEFAULT 'admin',
    `created_by_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_dc_code` (`code`),
    KEY `idx_dc_active` (`is_active`),
    KEY `idx_dc_valid_to` (`valid_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only used when applies_to = 'selected'. A package is a course row, so one table covers both.
CREATE TABLE IF NOT EXISTS `discount_code_courses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `discount_code_id` BIGINT UNSIGNED NOT NULL,
    `course_id` BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY `uq_dcc` (`discount_code_id`, `course_id`),
    KEY `idx_dcc_code` (`discount_code_id`),
    KEY `idx_dcc_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `discount_code_redemptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `discount_code_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `course_id` BIGINT UNSIGNED NULL,
    `student_subscription_id` BIGINT UNSIGNED NULL,
    `original_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `final_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `applied_by_type` ENUM('admin', 'teacher', 'student', 'system') NOT NULL DEFAULT 'admin',
    `applied_by_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_dcr_code` (`discount_code_id`),
    KEY `idx_dcr_student` (`student_id`),
    KEY `idx_dcr_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
