-- Fix foreign key constraint on teachers table for subject_id
ALTER TABLE `teachers` DROP FOREIGN KEY `fk_teachers_subject`;
ALTER TABLE `teachers` ADD CONSTRAINT `fk_teachers_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;
