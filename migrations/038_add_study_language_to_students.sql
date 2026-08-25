ALTER TABLE `students`
    ADD COLUMN IF NOT EXISTS `study_language` ENUM('arabic','english','french') NOT NULL DEFAULT 'arabic' AFTER `city_id`;
