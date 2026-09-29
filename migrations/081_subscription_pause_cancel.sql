-- Migration 081: an admin can pause or cancel a student's subscription, and it is recorded.
--
-- Until now the only things anyone could do to a subscription through the dashboard were list it
-- and create it. There was no way to stop one - not for a refund, not for a chargeback, not for a
-- student who asked to freeze while travelling - short of editing the row in phpMyAdmin.
--
-- =================================================================================================
-- Why access control is NOT moved into a new status column
-- =================================================================================================
--
-- Whether a student can open a course is decided in several places by the same two columns:
-- `is_active` = 1 and `end_date` in the future. get_student_accessible_entities() is the main one,
-- and the student app, the teacher dashboard and the certificate logic all lean on it.
--
-- Introducing a `status` column that those places did not know to read would mean a subscription
-- that says "cancelled" on the admin screen while the student is still watching the videos. So
-- the existing two columns stay the source of truth and keep their exact meaning:
--
--     pause    is_active = 0                       - the student loses access, the dates stand
--     resume   is_active = 1                       - exactly as it was
--     cancel   is_active = 0 AND end_date = now    - over, and the end date says when
--
-- Every existing query keeps working with no change at all. What this migration adds is the part
-- those two columns cannot express: WHICH of the two happened, WHEN, WHO did it and WHY. Without
-- that, a paused subscription and a cancelled one are the same row, and neither says who to ask.
--
-- Nothing is destructive: four nullable columns, no existing column touched, no data rewritten.
-- Re-running changes nothing.

-- =================================================================================================
-- 1. Paused
-- =================================================================================================

SET @has_paused := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_subscriptions' AND COLUMN_NAME = 'paused_at');
SET @sql := IF(@has_paused = 0,
    'ALTER TABLE `student_subscriptions` ADD COLUMN `paused_at` DATETIME NULL COMMENT ''When an admin paused it. NULL once resumed.'' AFTER `is_active`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 2. Cancelled
-- =================================================================================================
--
-- Separate from `end_date` reaching the past on its own. A subscription that simply ran its course
-- and one an admin ended early look identical in `end_date`, and only one of them needs explaining
-- to the student who calls about it.

SET @has_cancelled := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_subscriptions' AND COLUMN_NAME = 'cancelled_at');
SET @sql := IF(@has_cancelled = 0,
    'ALTER TABLE `student_subscriptions` ADD COLUMN `cancelled_at` DATETIME NULL COMMENT ''When an admin cancelled it outright'' AFTER `paused_at`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 3. Why, and by whom
-- =================================================================================================

SET @has_reason := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_subscriptions' AND COLUMN_NAME = 'status_reason');
SET @sql := IF(@has_reason = 0,
    'ALTER TABLE `student_subscriptions` ADD COLUMN `status_reason` VARCHAR(500) NULL COMMENT ''What the admin typed when pausing or cancelling'' AFTER `cancelled_at`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_by := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_subscriptions' AND COLUMN_NAME = 'status_changed_by');
SET @sql := IF(@has_by = 0,
    'ALTER TABLE `student_subscriptions` ADD COLUMN `status_changed_by` BIGINT UNSIGNED NULL COMMENT ''The admin who paused or cancelled it'' AFTER `status_reason`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
