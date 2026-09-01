-- Adds a 'system' sender_type for automatically generated reports (exam results, attendance,
-- course completion), plus nullable link columns used to trace/guard-against-duplicate
-- auto-generated reports. Existing manual (teacher/admin) reports are unaffected.

ALTER TABLE `student_reports`
  MODIFY COLUMN `sender_type` ENUM('teacher','admin','system') NOT NULL DEFAULT 'teacher',
  ADD COLUMN `course_id` BIGINT UNSIGNED NULL AFTER `subject_id`,
  ADD COLUMN `session_id` BIGINT UNSIGNED NULL AFTER `course_id`,
  ADD COLUMN `exam_attempt_id` BIGINT UNSIGNED NULL AFTER `session_id`;
