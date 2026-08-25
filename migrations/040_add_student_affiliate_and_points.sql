-- Migration: 040_add_student_affiliate_and_points.sql

ALTER TABLE `students`
  ADD COLUMN `referred_by_student_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `city_id`,
  ADD COLUMN `affiliate_code` varchar(50) DEFAULT NULL AFTER `referred_by_student_id`,
  ADD COLUMN `points_balance` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `affiliate_code`;

ALTER TABLE `students`
  ADD UNIQUE KEY `uq_students_affiliate_code` (`affiliate_code`),
  ADD KEY `idx_students_referred_by` (`referred_by_student_id`);

ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_referred_by` FOREIGN KEY (`referred_by_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Extend affiliate_commissions for admin reward management
ALTER TABLE `affiliate_commissions`
  ADD COLUMN `referred_student_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `affiliate_id`,
  ADD COLUMN `item_type` enum('course','book','other') DEFAULT NULL AFTER `referred_student_id`,
  ADD COLUMN `item_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `item_type`,
  ADD COLUMN `purchase_amount` decimal(10,2) DEFAULT 0.00 AFTER `item_id`,
  ADD COLUMN `notes` text DEFAULT NULL AFTER `status`,
  ADD COLUMN `approved_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `notes`;

ALTER TABLE `affiliate_commissions`
  ADD KEY `idx_affiliate_commissions_referred_student` (`referred_student_id`),
  ADD KEY `idx_affiliate_commissions_approved_by` (`approved_by_admin_id`);

-- Create student_referrals tracking table
CREATE TABLE IF NOT EXISTS `student_referrals` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_student_id` bigint(20) UNSIGNED NOT NULL,
  `referred_student_id` bigint(20) UNSIGNED NOT NULL,
  `affiliate_code` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_referrals_referrer` (`referrer_student_id`),
  KEY `idx_student_referrals_referred` (`referred_student_id`),
  CONSTRAINT `fk_student_referrals_referrer` FOREIGN KEY (`referrer_student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_referrals_referred` FOREIGN KEY (`referred_student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create student_point_transactions log table
CREATE TABLE IF NOT EXISTS `student_point_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `points` decimal(10,2) NOT NULL DEFAULT 0.00,
  `type` enum('earn_referral_purchase','redeem','admin_adjustment') NOT NULL DEFAULT 'earn_referral_purchase',
  `reference_type` enum('course','book','affiliate_commission','other') DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `referred_student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_point_tx_student` (`student_id`),
  CONSTRAINT `fk_student_point_tx_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-generate affiliate codes for all existing students in the database
UPDATE `students` 
SET `affiliate_code` = CONCAT('NAJEH-STU-', `id`) 
WHERE `affiliate_code` IS NULL OR `affiliate_code` = '';

-- Insert entries for all existing students into the affiliates table
INSERT INTO `affiliates` (`student_id`, `affiliate_code`, `commission_type`, `commission_value`, `is_active`)
SELECT s.`id`, s.`affiliate_code`, 'fixed', 0.00, 1 
FROM `students` s
LEFT JOIN `affiliates` a ON s.`id` = a.`student_id`
WHERE a.`id` IS NULL AND s.`affiliate_code` IS NOT NULL AND s.`affiliate_code` != '';

