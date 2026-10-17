-- ============================================================
-- Migration: 100_consultation_meeting_link.sql
-- A consultation needs somewhere to actually happen. The slot carried a
-- meeting type but never the meeting: whoever held it had to find the link
-- elsewhere and send it by hand.
-- ============================================================

ALTER TABLE `consultation_slots`
  ADD COLUMN IF NOT EXISTS `meeting_link` VARCHAR(500) NULL
  COMMENT 'Where the consultation is held - the link the Start button opens';
