-- `teachers.is_active` is the admin's own enable/disable switch and must stay reserved for that.
-- The automatic attendance-policy suspension (exceeding unauthorized absences) was wrongly reusing
-- the same column, which made an auto-restricted teacher show up as "Inactive" in the admin list
-- exactly like a genuinely admin-disabled account, and (before this fix) blocked the teacher from
-- logging in at all. This adds a separate flag so the two states never collide again.
ALTER TABLE teachers
    ADD COLUMN is_attendance_suspended TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;
