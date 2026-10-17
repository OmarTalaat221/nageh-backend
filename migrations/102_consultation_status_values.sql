-- ============================================================
-- Migration: 102_consultation_status_values.sql
-- `status` was an enum of four values: available, pending, approved,
-- rejected. Migration 101 introduced two more states, in_progress and
-- completed, and MySQL stored both as '' - an invalid enum value is
-- silently written as the empty string rather than refused. Reading it back
-- fell through to "pending", so a consultation that had been started and
-- ended showed up as still waiting for approval, with its start and end
-- times sitting right underneath.
--
-- The column becomes a VARCHAR. The application already decides which
-- values are legal, and an enum that quietly eats the ones it has not been
-- told about is the trap that caused this.
--
-- Run it after 101. The repair statements then read the real state back out
-- of the timestamps, which did land.
-- ============================================================

ALTER TABLE `consultation_slots`
  MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'available';

-- Rows the enum emptied. Order matters: ended before started, or a finished
-- consultation would be repaired as a running one.
UPDATE `consultation_slots`
   SET `status` = 'completed'
 WHERE `status` = '' AND `ended_at` IS NOT NULL;

UPDATE `consultation_slots`
   SET `status` = 'in_progress'
 WHERE `status` = '' AND `started_at` IS NOT NULL AND `ended_at` IS NULL;

-- Nothing else should be able to produce an empty status, but a row with no
-- timestamps to read is put back to whatever its booking says.
UPDATE `consultation_slots`
   SET `status` = CASE WHEN `is_booked` = 1 THEN 'approved' ELSE 'available' END
 WHERE `status` = '';
