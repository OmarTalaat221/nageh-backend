SET FOREIGN_KEY_CHECKS = 0;

-- Section 10: Attendance & Delay & Salaries & Deductions -------------------

-- Single-row global HR policy settings (all admin-configurable knobs).
CREATE TABLE IF NOT EXISTS `hr_policy_settings` (
    `id` TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    `teacher_max_absence_with_permission_per_month` INT UNSIGNED NOT NULL DEFAULT 2,
    `teacher_max_absence_without_permission` INT UNSIGNED NOT NULL DEFAULT 2,
    `teacher_max_reschedule_per_month` INT UNSIGNED NOT NULL DEFAULT 3,
    `teacher_grace_minutes` INT UNSIGNED NOT NULL DEFAULT 10,
    `teacher_late_fine_per_minute` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `teacher_absence_fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `student_max_absence_with_permission_per_month` INT UNSIGNED NOT NULL DEFAULT 2,
    `student_max_absence_without_permission` INT UNSIGNED NOT NULL DEFAULT 2,
    `student_max_reschedule_per_month` INT UNSIGNED NOT NULL DEFAULT 3,
    `student_grace_minutes` INT UNSIGNED NOT NULL DEFAULT 10,
    `default_pay_model` ENUM('hourly','per_session','per_minute') NOT NULL DEFAULT 'per_session',
    `default_pay_rate` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `chk_hr_policy_single_row` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hr_policy_settings` (`id`) VALUES (1)
    ON DUPLICATE KEY UPDATE `id` = `id`;

-- Per-teacher pay-rate override (absence of a row = use hr_policy_settings default).
CREATE TABLE IF NOT EXISTS `teacher_pay_rates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `pay_model` ENUM('hourly','per_session','per_minute') NOT NULL,
    `rate_amount` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tpr_teacher` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher's own attendance (check-in/out) per session occurrence date.
CREATE TABLE IF NOT EXISTS `teacher_session_attendance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `occurrence_date` DATE NOT NULL,
    `status` ENUM('present','late','absent') NOT NULL DEFAULT 'present',
    `check_in_time` DATETIME NULL,
    `check_out_time` DATETIME NULL,
    `minutes_late` INT NOT NULL DEFAULT 0,
    `is_excused` TINYINT(1) NOT NULL DEFAULT 0,
    `recorded_by` ENUM('teacher','system') NOT NULL DEFAULT 'teacher',
    `earning_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `wallet_transaction_id` BIGINT UNSIGNED NULL,
    `marked_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tsa_session_date` (`session_id`, `occurrence_date`),
    KEY `idx_tsa_teacher` (`teacher_id`),
    KEY `idx_tsa_date` (`occurrence_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only ledger of every teacher wallet movement.
CREATE TABLE IF NOT EXISTS `teacher_wallet_transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('earning','fine','refund','admin_adjustment') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL COMMENT 'positive = credit (earning/refund/positive adjustment), negative = debit (fine/negative adjustment)',
    `reference_type` ENUM('session','appeal','manual') NOT NULL DEFAULT 'manual',
    `reference_id` BIGINT UNSIGNED NULL,
    `note` VARCHAR(255) NULL,
    `created_by_type` ENUM('system','admin') NOT NULL DEFAULT 'system',
    `created_by_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_twt_teacher` (`teacher_id`),
    KEY `idx_twt_type` (`type`),
    KEY `idx_twt_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Absence-with-permission / late-notice requests, teacher or student, always admin-reviewed.
CREATE TABLE IF NOT EXISTS `attendance_exception_requests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `occurrence_date` DATE NOT NULL,
    `requested_by_type` ENUM('teacher','student') NOT NULL,
    `requested_by_id` BIGINT UNSIGNED NOT NULL,
    `request_type` ENUM('absence_permission','late_notice') NOT NULL,
    `reason` TEXT NOT NULL,
    `expected_minutes_late` INT UNSIGNED NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by_admin_id` BIGINT UNSIGNED NULL,
    `admin_note` TEXT NULL,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_aer_session_date` (`session_id`, `occurrence_date`),
    KEY `idx_aer_requester` (`requested_by_type`, `requested_by_id`),
    KEY `idx_aer_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher appeals against a fine/deduction wallet transaction.
CREATE TABLE IF NOT EXISTS `teacher_deduction_appeals` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `wallet_transaction_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note` TEXT NULL,
    `reviewed_by_admin_id` BIGINT UNSIGNED NULL,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_tda_teacher` (`teacher_id`),
    KEY `idx_tda_wallet_txn` (`wallet_transaction_id`),
    KEY `idx_tda_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extend existing student attendance ledger with the same excused/late/check-in concept
-- already anticipated by app/parent/attendance/student_activity.php's read side.
ALTER TABLE `session_attendance`
    MODIFY COLUMN `status` ENUM('present','absent','late') NOT NULL DEFAULT 'present',
    ADD COLUMN `check_in_time` DATETIME NULL AFTER `status`,
    ADD COLUMN `check_out_time` DATETIME NULL AFTER `check_in_time`,
    ADD COLUMN `minutes_late` INT NOT NULL DEFAULT 0 AFTER `check_out_time`,
    ADD COLUMN `is_excused` TINYINT(1) NOT NULL DEFAULT 0 AFTER `minutes_late`;

-- Cached wallet balance on the teacher row (source of truth stays teacher_wallet_transactions).
ALTER TABLE `teachers`
    ADD COLUMN `wallet_balance` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `is_active`;

SET FOREIGN_KEY_CHECKS = 1;
