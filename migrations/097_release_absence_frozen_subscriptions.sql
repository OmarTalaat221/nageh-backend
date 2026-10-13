-- ============================================================
-- Migration: 097_release_absence_frozen_subscriptions.sql
-- Najeh no longer freezes a subscription for absence (the automatic switch-off
-- was removed from record_student_auto_absence). This releases the students the
-- old rule had already locked out.
--
-- Only rows the attendance cron could have produced are touched: switched off,
-- but never paused or cancelled by an admin, and not past their end date. An
-- admin's own pause or cancellation sets paused_at / cancelled_at and is left
-- exactly as it is.
-- ============================================================

UPDATE `student_subscriptions`
SET `is_active` = 1
WHERE `is_active` = 0
  AND `paused_at` IS NULL
  AND `cancelled_at` IS NULL
  AND (
    `end_date` IS NULL
    OR `end_date` = '0000-00-00 00:00:00'
    OR `end_date` >= NOW()
  );
