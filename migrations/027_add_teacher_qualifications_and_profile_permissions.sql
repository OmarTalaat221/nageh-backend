DELIMITER //

CREATE PROCEDURE add_teacher_extra_columns()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teachers' AND COLUMN_NAME = 'qualifications'
    ) THEN
        ALTER TABLE `teachers` ADD COLUMN `qualifications` TEXT NULL AFTER `biography`;
    END IF;

    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teachers' AND COLUMN_NAME = 'can_view_profile'
    ) THEN
        ALTER TABLE `teachers` ADD COLUMN `can_view_profile` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`;
    END IF;
END //

DELIMITER ;

CALL add_teacher_extra_columns();
DROP PROCEDURE IF EXISTS add_teacher_extra_columns;
