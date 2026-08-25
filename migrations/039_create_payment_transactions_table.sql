SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `item_type` ENUM('course', 'book') NOT NULL,
    `item_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'EGP',
    `status` ENUM('pending', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `easykash_ref` VARCHAR(100) NULL,
    `payment_method` VARCHAR(100) NULL,
    `granted_ref_id` BIGINT UNSIGNED NULL,
    `raw_callback` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    KEY `idx_pt_student` (`student_id`),
    KEY `idx_pt_status` (`status`),
    KEY `idx_pt_ref` (`easykash_ref`),
    KEY `idx_pt_item` (`item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
