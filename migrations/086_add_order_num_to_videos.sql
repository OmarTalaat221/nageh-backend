-- The admin/teacher video form has sent `order_num` on every create/update for a while, and the
-- backend has silently dropped it every time because this column never existed - manual ordering
-- of a unit's videos never actually worked. Added now so the same drag-to-reorder treatment units
-- and books already have can be given to videos too.
ALTER TABLE `videos`
    ADD COLUMN `order_num` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `unit_id`;
