-- Migration 027: FCM tokens per user type + notifications log + session notification dedupe

ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `fcm_token` VARCHAR(255) NULL AFTER `image`;
ALTER TABLE `teachers` ADD COLUMN IF NOT EXISTS `fcm_token` VARCHAR(255) NULL AFTER `image`;
ALTER TABLE `parents` ADD COLUMN IF NOT EXISTS `fcm_token` VARCHAR(255) NULL AFTER `image`;
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `fcm_token` VARCHAR(255) NULL AFTER `image`;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sender_type` ENUM('admin', 'teacher', 'system') NOT NULL DEFAULT 'admin',
    `sender_id` BIGINT UNSIGNED NULL,
    `target_type` ENUM('student', 'teacher') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `data` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification_recipients` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `notification_id` BIGINT UNSIGNED NOT NULL,
    `user_type` ENUM('student', 'teacher') NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `is_sent` TINYINT(1) NOT NULL DEFAULT 0,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `error` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_nr_notification` (`notification_id`),
    KEY `idx_nr_user` (`user_type`, `user_id`),
    CONSTRAINT `fk_nr_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `class_session_notification_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `notify_type` ENUM('created', 'started') NOT NULL,
    `session_date` DATE NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_session_notify` (`session_id`, `notify_type`, `session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
