-- Deleting a teacher failed with "Cannot delete or update a parent row: a foreign key constraint
-- fails (`courses`, CONSTRAINT `fk_courses_teacher`)". That constraint was created with an
-- ON UPDATE rule but no ON DELETE one, so it defaults to RESTRICT and any teacher who was ever set
-- as a course's main teacher could never be deleted.
--
-- Every other foreign key pointing at `teachers` already declares CASCADE or SET NULL
-- (fk_affiliates_teacher, fk_ct_teacher, fk_teacher_subjects_teacher, fk_transactions_teacher);
-- this one was simply missed. courses.teacher_id is nullable, and course_teachers is the real
-- source of a course's teachers, so SET NULL is the matching behaviour.

ALTER TABLE `courses` DROP FOREIGN KEY `fk_courses_teacher`;

ALTER TABLE `courses`
    ADD CONSTRAINT `fk_courses_teacher`
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;
