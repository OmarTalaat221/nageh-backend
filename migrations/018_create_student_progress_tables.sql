SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `student_video_progress` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `video_id` BIGINT UNSIGNED NOT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `watched_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_position_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_student_video` (`student_id`, `video_id`),
    KEY `idx_svp_student` (`student_id`),
    KEY `idx_svp_video` (`video_id`),
    KEY `idx_svp_completed` (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_book_progress` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `book_id` BIGINT UNSIGNED NOT NULL,
    `open_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `last_page_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `total_pages` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_opened_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_student_book` (`student_id`, `book_id`),
    KEY `idx_sbp_student` (`student_id`),
    KEY `idx_sbp_book` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
