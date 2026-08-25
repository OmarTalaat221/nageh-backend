-- Lets a STUDENT also request a session time change (reviewed by the session's teacher),
-- alongside the existing teacher-initiated requests (reviewed by admin).
ALTER TABLE `session_reschedule_requests`
    ADD COLUMN `requested_by_type` ENUM('teacher','student') NOT NULL DEFAULT 'teacher' AFTER `teacher_id`,
    ADD COLUMN `requested_by_id` BIGINT UNSIGNED NULL AFTER `requested_by_type`,
    ADD COLUMN `reviewed_by_type` ENUM('admin','teacher') NULL AFTER `admin_note`,
    ADD COLUMN `reviewed_by_id` BIGINT UNSIGNED NULL AFTER `reviewed_by_type`;

-- Backfill existing rows: they were all teacher-initiated requests reviewed by admin.
UPDATE `session_reschedule_requests` SET `requested_by_type` = 'teacher', `requested_by_id` = `teacher_id` WHERE `requested_by_id` IS NULL;
UPDATE `session_reschedule_requests` SET `reviewed_by_type` = 'admin' WHERE `status` IN ('approved','rejected') AND `reviewed_by_type` IS NULL;

ALTER TABLE `session_reschedule_requests`
    ADD KEY `idx_srr_requested_by` (`requested_by_type`, `requested_by_id`);
