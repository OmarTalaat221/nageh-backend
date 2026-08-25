-- Adds online-meeting support to teacher class sessions (room field alone wasn't enough for online sessions).
ALTER TABLE `class_sessions`
    ADD COLUMN `is_online` TINYINT(1) NOT NULL DEFAULT 0 AFTER `room`,
    ADD COLUMN `meeting_link` VARCHAR(500) NULL AFTER `is_online`;
