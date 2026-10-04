-- Courses had no manual ordering at all - the admin list and every student-facing list were sorted
-- by id, so the only way to move a course up was to re-create it. This adds the same order_num
-- convention already used by units, videos, course_books and course_exams.
--
-- The seed runs newest-first (id DESC) on purpose: every one of those lists was `id DESC` until
-- now, so seeding that way means the first load after this migration looks exactly like the last
-- load before it. It also makes the DEFAULT 0 on new rows do the right thing - a freshly created
-- course sorts above everything until someone drags it, which is the behaviour the dashboards had
-- all along.

ALTER TABLE `courses`
    ADD COLUMN `order_num` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `lessons_count`;

SET @row = 0;

UPDATE `courses`
SET `order_num` = (@row := @row + 1)
ORDER BY `id` DESC;

CREATE INDEX `idx_courses_order_num` ON `courses` (`order_num`);
