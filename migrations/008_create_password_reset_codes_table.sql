-- Migration 008: Create password_reset_codes table

CREATE TABLE IF NOT EXISTS `password_reset_codes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_type` ENUM('student', 'teacher', 'parent', 'admin') NOT NULL DEFAULT 'student',
    `user_id` BIGINT UNSIGNED NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `reset_token` VARCHAR(100) NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `is_used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_password_reset_lookup` (`email`, `code`, `user_type`, `is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
