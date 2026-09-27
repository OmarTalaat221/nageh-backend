-- Migration 070: let a student apply a discount code themselves, before an admin grants anything.
--
-- Until now `discount_code_redemptions` only ever held rows an admin wrote at the moment of a
-- grant, so a student could look at a code but never actually use one. There is no payment
-- gateway in play, so "using" a code cannot mean paying with it: it means the student pins the
-- code to a course they want, and that pin is what the admin's grant later consumes.
--
-- `status` is what separates the two: a `pending` row is a student's claim and costs the code
-- nothing (`discount_codes.used_count` is untouched), while an `applied` row is a discount that
-- really was given. Existing rows are all admin grants, so the default is 'applied' and no
-- backfill is needed.

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'discount_code_redemptions'
      AND COLUMN_NAME = 'status'
);

SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `discount_code_redemptions` ADD COLUMN `status` ENUM('pending', 'applied') NOT NULL DEFAULT 'applied' COMMENT 'pending = student claim, costs the code nothing; applied = discount actually given' AFTER `final_amount`",
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- The student's own screen and the admin's grant both look a claim up by student, so index it.
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'discount_code_redemptions'
      AND INDEX_NAME = 'idx_dcr_student_status'
);

SET @sql2 := IF(
    @idx_exists = 0,
    'ALTER TABLE `discount_code_redemptions` ADD INDEX `idx_dcr_student_status` (`student_id`, `status`)',
    'SELECT 1'
);

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
