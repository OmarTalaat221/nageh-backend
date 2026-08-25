SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `student_reports`
ADD COLUMN `sender_type` ENUM('teacher', 'admin') NOT NULL DEFAULT 'teacher' AFTER `id`,
ADD COLUMN `admin_id` BIGINT UNSIGNED NULL AFTER `sender_type`,
ADD COLUMN `subject_id` BIGINT UNSIGNED NULL AFTER `student_id`,
ADD COLUMN `report_type` ENUM('academic_performance', 'behavior_discipline', 'exam_result', 'attendance') NOT NULL DEFAULT 'academic_performance' AFTER `subject_id`,
ADD COLUMN `term` VARCHAR(100) NULL AFTER `report_type`,
ADD COLUMN `score` DECIMAL(5,2) NULL AFTER `term`,
ADD COLUMN `max_score` DECIMAL(5,2) NULL AFTER `score`,
ADD COLUMN `grade_label` VARCHAR(50) NULL AFTER `max_score`,
ADD COLUMN `percentage` DECIMAL(5,2) NULL AFTER `grade_label`,
ADD COLUMN `strengths` JSON NULL AFTER `description`,
ADD COLUMN `improvements` JSON NULL AFTER `strengths`,
ADD COLUMN `report_date` DATE NULL AFTER `improvements`,
MODIFY COLUMN `teacher_id` BIGINT UNSIGNED NULL;

ALTER TABLE `student_reports`
ADD KEY `idx_sr_admin` (`admin_id`),
ADD KEY `idx_sr_subject` (`subject_id`),
ADD KEY `idx_sr_report_type` (`report_type`),
ADD KEY `idx_sr_date` (`report_date`);

SET FOREIGN_KEY_CHECKS = 1;
