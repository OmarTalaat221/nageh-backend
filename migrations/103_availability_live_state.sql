-- ============================================================
-- Migration: 103_availability_live_state.sql
-- A package session needs the same thing a consultation needed: the meeting
-- link cannot sit on the student's screen from the moment they book. It is
-- an invitation to walk into an empty room, hours or days early.
--
-- Start is a stored state. `started_at` and `ended_at` are what the
-- student's screen reads to decide whether to show the link at all.
--
-- `status` becomes a VARCHAR for the same reason it did on
-- consultation_slots in migration 102: it was an enum, and MySQL stores a
-- value an enum has not been told about as the empty string without
-- complaining, so adding `in_progress` to an enum column that four other
-- places write to is a trap waiting to be stepped in.
-- ============================================================

ALTER TABLE `teacher_availability_slots`
  ADD COLUMN IF NOT EXISTS `started_at` DATETIME NULL
  COMMENT 'When the admin or the teacher pressed Start',
  ADD COLUMN IF NOT EXISTS `ended_at` DATETIME NULL
  COMMENT 'When it was ended; the session reads as completed from here on';

ALTER TABLE `teacher_availability_slots`
  MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'available';
