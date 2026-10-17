-- ============================================================
-- Migration: 101_consultation_live_state.sql
-- "Start" has to mean something to the student, not just open a link for
-- whoever pressed it. The slot now records when it was started and when it
-- was ended, so the student's screen can say "it has started, go in" and
-- later "it is over" instead of leaving them guessing at the hour.
--
-- `status` gains two values it did not carry before: in_progress between the
-- two timestamps, completed after the second one.
-- ============================================================

ALTER TABLE `consultation_slots`
  ADD COLUMN IF NOT EXISTS `started_at` DATETIME NULL
  COMMENT 'When the admin or the teacher pressed Start',
  ADD COLUMN IF NOT EXISTS `ended_at` DATETIME NULL
  COMMENT 'When it was ended; the consultation reads as completed from here on';
