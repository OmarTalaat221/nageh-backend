ALTER TABLE `subscription_codes`
  ADD COLUMN `claimed_by_student_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `used_count`;
