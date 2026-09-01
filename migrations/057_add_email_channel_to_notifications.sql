ALTER TABLE `notifications`
  ADD COLUMN `channels` VARCHAR(50) NOT NULL DEFAULT 'push' AFTER `target_type`;

ALTER TABLE `notification_recipients`
  ADD COLUMN `is_email_sent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_sent`,
  ADD COLUMN `email_error` VARCHAR(500) NULL AFTER `error`;
