-- Migration 056: Grade-aware subject assignment for teachers and class sessions.
--
-- Today `teacher_subjects` and `class_sessions` only carry a subject_id with no grade/stage
-- information, so a teacher's subject assignment (and any subject-scoped session they create)
-- is not isolated per academic year: any student subscribed to that subject sees the session
-- regardless of grade. `academic_year_id` is nullable on both tables so existing rows keep their
-- current "all grades" behavior; only new grade-specific assignments/sessions opt in.

ALTER TABLE `teacher_subjects`
  ADD COLUMN `academic_year_id` BIGINT UNSIGNED NULL AFTER `subject_id`,
  ADD CONSTRAINT `fk_teacher_subjects_academic_year`
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `teacher_subjects`
  DROP INDEX `uq_teacher_subjects`,
  ADD UNIQUE KEY `uq_teacher_subjects` (`teacher_id`, `subject_id`, `academic_year_id`);

ALTER TABLE `class_sessions`
  ADD COLUMN `academic_year_id` BIGINT UNSIGNED NULL AFTER `grade`,
  ADD CONSTRAINT `fk_class_sessions_academic_year`
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL;
