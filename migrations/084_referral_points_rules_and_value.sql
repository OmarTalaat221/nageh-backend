-- Migration 084: referral points become a rule, and a point becomes worth something.
--
-- =================================================================================================
-- What was there
-- =================================================================================================
--
-- `affiliates` has carried `commission_type` and `commission_value` since it was created. Both are
-- written once, as 'fixed' and 0.00, and then read by nothing:
--
--     NAJEH-STU-1062   fixed   0.00
--     NAJEH-STU-1001   fixed   0.00
--
-- So no referral code had a rule. When a referred student bought something, an admin opened the
-- request and typed a number into the approval box, per request, from memory. Two identical
-- purchases could earn different rewards depending on who approved them and when.
--
-- And a point was worth nothing in particular. `students.points_balance` counted them and
-- `student_point_transactions` recorded them, but there was no rate anywhere in the database, so
-- "80 points" did not correspond to any amount of money. Redeeming meant an admin deducting points
-- by hand through /admin/students/deduct-points, with the actual discount arranged off-platform.
--
-- =================================================================================================
-- Why the value is per currency
-- =================================================================================================
--
-- The pricing design on this platform is deliberate: every price is a number a human typed for a
-- country, and there is NO exchange-rate table. A single `point_value` column would quietly break
-- that - it would mean one point is worth 1 unit whether the student is being quoted in pounds or
-- riyals, which is the exact mistake the discount engine already refuses to make when it declines
-- to subtract a dollar amount from a pound price.
--
-- So a point's value is set per currency, the same way an item's price is. A currency with no row
-- here simply cannot be paid for with points, and the student is told that rather than being
-- quietly charged the wrong number.

-- =================================================================================================
-- 1. The earning rule, and the redemption switches
-- =================================================================================================
--
-- `referral_commission_type` is 'percent' (of the purchase amount) or 'fixed' (a flat number of
-- points per referred purchase). A code may override both on its own row in `affiliates`; these
-- are the platform defaults used when it does not.

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
SELECT * FROM (
    SELECT 'referral_commission_type' AS setting_key, 'percent' AS setting_value UNION ALL
    SELECT 'referral_commission_value', '10' UNION ALL
    SELECT 'points_redeem_enabled', '1' UNION ALL
    SELECT 'points_min_redeem', '50'
) AS incoming
WHERE NOT EXISTS (
    SELECT 1 FROM `site_settings` s WHERE s.setting_key = incoming.setting_key
);

-- =================================================================================================
-- 2. What one point is worth, per currency
-- =================================================================================================

CREATE TABLE IF NOT EXISTS `point_currency_values` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `currency_code` VARCHAR(10) NOT NULL,
    `point_value` DECIMAL(12,4) NOT NULL DEFAULT 1.0000 COMMENT 'How much one point is worth in this currency',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_point_currency` (`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeded at 1.00 for every active currency, which is the one rate that cannot surprise anybody:
-- one point buys one unit. Change them on the Points screen; a rate of 0 or a deactivated row
-- means points cannot be spent in that currency at all.
INSERT INTO `point_currency_values` (`currency_code`, `point_value`, `is_active`)
SELECT c.code, 1.0000, 1
FROM `currencies` c
WHERE c.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM `point_currency_values` p WHERE p.currency_code = c.code);

-- =================================================================================================
-- 3. Points can now come back
-- =================================================================================================
--
-- `student_point_transactions.type` was ENUM('earn_referral_purchase','redeem','admin_adjustment').
-- Nothing ever gave points back, so there was no value for it. A payment request that is rejected
-- after points were spent on it has to return them, and recording that as 'admin_adjustment' would
-- make a refund indistinguishable from a manual correction in the student's own history.
--
-- MODIFY, not a rebuild: adding a value to the end of an ENUM does not renumber the existing ones,
-- so every stored row keeps the value it already had.

ALTER TABLE `student_point_transactions`
    MODIFY COLUMN `type` ENUM('earn_referral_purchase','redeem','refund','admin_adjustment')
    NOT NULL DEFAULT 'earn_referral_purchase';

-- =================================================================================================
-- 4. What a payment request paid with points
-- =================================================================================================
--
-- Recorded on the request itself rather than worked out later from the points ledger: a reviewer
-- comparing a transfer against what was owed needs the number that was actually quoted, and the
-- refund path when a request is rejected needs to know exactly how many points to give back.

SET @has_points := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offline_payment_requests' AND COLUMN_NAME = 'points_spent');
SET @sql := IF(@has_points = 0,
    'ALTER TABLE `offline_payment_requests` ADD COLUMN `points_spent` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT ''Points the student spent on this request''',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_pd := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offline_payment_requests' AND COLUMN_NAME = 'points_discount');
SET @sql := IF(@has_pd = 0,
    'ALTER TABLE `offline_payment_requests` ADD COLUMN `points_discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT ''What those points were worth, in the request currency'' AFTER `points_spent`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
