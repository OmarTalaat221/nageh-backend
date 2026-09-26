-- Migration 066: real calendar dates for teaching appointments.
--
-- `class_sessions` is a weekly recurring pattern (day_of_week + start_time) with no calendar date
-- anywhere on it, and attendance/reschedule/cron all depend on that shape, so it is left exactly
-- as it is. This migration adds a PARALLEL layer for appointments that happen on a specific date:
-- a teacher publishes concrete dated slots, and students are booked into them - by themselves,
-- by an admin, or by the teacher.
--
-- One slot = one date + one time range for one teacher. `capacity` decides whether it is a
-- private appointment (1, the default) or a group one (> 1); bookings always live in the second
-- table, so there is a single code path for both cases.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `teacher_availability_slots` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `course_id` BIGINT UNSIGNED NULL,
    `subject_id` BIGINT UNSIGNED NULL,
    `slot_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `capacity` INT UNSIGNED NOT NULL DEFAULT 1,
    `booked_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('available', 'full', 'cancelled', 'completed') NOT NULL DEFAULT 'available',
    `is_online` TINYINT(1) NOT NULL DEFAULT 1,
    `meeting_link` VARCHAR(500) NULL,
    `room` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_by_type` ENUM('admin', 'teacher') NOT NULL DEFAULT 'admin',
    `created_by_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tas_slot` (`teacher_id`, `slot_date`, `start_time`),
    KEY `idx_tas_teacher_date` (`teacher_id`, `slot_date`),
    KEY `idx_tas_date` (`slot_date`),
    KEY `idx_tas_course` (`course_id`),
    KEY `idx_tas_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `availability_slot_bookings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slot_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `student_subscription_id` BIGINT UNSIGNED NULL,
    `status` ENUM('booked', 'cancelled', 'attended', 'missed') NOT NULL DEFAULT 'booked',
    `booked_by_type` ENUM('student', 'admin', 'teacher') NOT NULL DEFAULT 'student',
    `booked_by_id` BIGINT UNSIGNED NULL,
    `notes` TEXT NULL,
    `cancelled_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_asb_slot_student` (`slot_id`, `student_id`),
    KEY `idx_asb_student` (`student_id`),
    KEY `idx_asb_slot` (`slot_id`),
    KEY `idx_asb_subscription` (`student_subscription_id`),
    KEY `idx_asb_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
