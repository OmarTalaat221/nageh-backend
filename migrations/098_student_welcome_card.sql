-- ============================================================
-- Migration: 098_student_welcome_card.sql
-- The welcome card a student sees once, the first time they are signed in.
-- NULL means "not shown yet"; the timestamp is written the moment the student
-- dismisses it, so the card follows the account rather than the browser.
-- ============================================================

ALTER TABLE `students`
  ADD COLUMN IF NOT EXISTS `welcome_seen_at` DATETIME NULL
  COMMENT 'When the student dismissed the one-time welcome card. NULL = not shown yet';

-- Everyone who already has an account is marked as having seen it: the card greets new
-- sign-ups, and should not appear one day to students who have been using the platform for
-- months. Only rows created after this migration stay NULL.
UPDATE `students` SET `welcome_seen_at` = NOW() WHERE `welcome_seen_at` IS NULL;
