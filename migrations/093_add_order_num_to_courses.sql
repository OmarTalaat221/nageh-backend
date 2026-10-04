-- Courses had no manual ordering at all - the admin list and every student-facing list were sorted
-- by id, so the only way to move a course up was to re-create it. This adds the same order_num
-- convention already used by units, videos, course_books and course_exams, and seeds it from the
-- current id order so nothing visibly jumps the first time the dashboard loads.

ALTER TABLE `courses`
    ADD COLUMN `order_num` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `lessons_count`;

SET @row = 0;

UPDATE `courses`
SET `order_num` = (@row := @row + 1)
ORDER BY `id` ASC;

CREATE INDEX `idx_courses_order_num` ON `courses` (`order_num`);
