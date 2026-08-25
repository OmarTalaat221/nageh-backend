CREATE TABLE IF NOT EXISTS `session_files` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` INT UNSIGNED NOT NULL,
    `uploaded_by_type` ENUM('admin','teacher') NOT NULL DEFAULT 'teacher',
    `uploaded_by_id` INT UNSIGNED NOT NULL,
    `file_type` ENUM('exam','homework','task','explanation','other') NOT NULL DEFAULT 'other',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL DEFAULT '',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_files_session_id` (`session_id`),
    KEY `idx_session_files_file_type` (`file_type`),
    KEY `idx_session_files_uploader` (`uploaded_by_type`, `uploaded_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
