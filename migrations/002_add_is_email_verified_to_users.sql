-- Migration 002: Add is_email_verified column to user tables

ALTER TABLE `students` ADD COLUMN `is_email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`;
ALTER TABLE `parents` ADD COLUMN `is_email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`;
ALTER TABLE `teachers` ADD COLUMN `is_email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`;
ALTER TABLE `admins` ADD COLUMN `is_email_verified` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`;
