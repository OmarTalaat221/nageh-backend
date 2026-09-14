-- `session_reschedule_requests.new_start_time`/`new_end_time` were NOT NULL, but both
-- app/teacher/sessions/manage.php::teacherRequestReschedule and studentRequestReschedule have
-- always treated them as optional (a reschedule can just move the day, keeping the same time) —
-- causing "Column 'new_start_time' cannot be null" whenever a caller omits them.
ALTER TABLE `session_reschedule_requests`
    MODIFY COLUMN `new_start_time` TIME NULL,
    MODIFY COLUMN `new_end_time` TIME NULL;
