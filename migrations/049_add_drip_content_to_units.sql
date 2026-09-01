-- Per-unit toggle: 0 = all videos open immediately (current/default behavior),
-- 1 = sequential drip — a video stays locked until the student has completed
-- the previous video in the same unit (ordered by video id).
ALTER TABLE `units`
  ADD COLUMN `videos_drip_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `order_num`;
