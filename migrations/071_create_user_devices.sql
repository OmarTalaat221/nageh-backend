-- Migration 071: limit how many devices one account may be signed in from.
--
-- A single set of credentials shared around a class is the cheapest way to give a paid course
-- away, and nothing in the platform noticed it before. Each sign-in now registers the device it
-- came from, and a student who is already at their limit is refused rather than quietly handed a
-- second session.
--
-- The limit is deliberately in two places. `site_settings.max_devices_per_student` is the default
-- for everyone; `students.max_devices` overrides it for one student, because the answer to "my
-- son shares my tablet" is to raise that student's ceiling, not the whole platform's.
--
-- `user_type` is here so the same table can hold teacher and parent devices later without a
-- second table. Only students are enforced today.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `user_devices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_type` ENUM('student', 'teacher', 'parent') NOT NULL DEFAULT 'student',
    `user_id` BIGINT UNSIGNED NOT NULL,
    `device_id` VARCHAR(191) NOT NULL COMMENT 'Opaque id the client generates once and keeps',
    `device_name` VARCHAR(255) NULL COMMENT 'Human label for the admin screen, e.g. Chrome on Windows',
    `platform` VARCHAR(100) NULL,
    `user_agent` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = revoked by an admin; the row is kept as history',
    `revoked_at` DATETIME NULL,
    `revoked_by_admin_id` BIGINT UNSIGNED NULL,
    `first_seen_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- One row per device per account: a returning device updates its row instead of taking
    -- another slot, which is what makes "1 device" mean one device rather than one login.
    UNIQUE KEY `uq_user_device` (`user_type`, `user_id`, `device_id`),
    KEY `idx_ud_owner` (`user_type`, `user_id`, `is_active`),
    KEY `idx_ud_last_seen` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Per-student override of the global ceiling. NULL means "use the global setting".
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'students'
      AND COLUMN_NAME = 'max_devices'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `students` ADD COLUMN `max_devices` INT UNSIGNED NULL COMMENT ''NULL = use site_settings.max_devices_per_student''',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Defaults: one device, and enforcement ON.
--
-- Enforcement being on means a client that does NOT send a device id on login is refused, because
-- letting it through unregistered would be a hole big enough to make the whole feature decorative.
-- If a client that cannot be updated yet has to keep working, set student_device_limit_enabled to
-- 0 until it can.
INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES
    ('max_devices_per_student', '1'),
    ('student_device_limit_enabled', '1')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
