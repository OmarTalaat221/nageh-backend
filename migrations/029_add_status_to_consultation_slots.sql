SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `consultation_slots`
ADD COLUMN `status` ENUM('available', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'available' AFTER `booked_at`;

ALTER TABLE `consultation_slots`
ADD KEY `idx_cs_status` (`status`);

SET FOREIGN_KEY_CHECKS = 1;
