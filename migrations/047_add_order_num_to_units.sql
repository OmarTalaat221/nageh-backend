ALTER TABLE `units`
  ADD COLUMN `order_num` INT NOT NULL DEFAULT 0 AFTER `code`;

-- Backfill: number existing units sequentially per course, ordered by id.
SET @rn := 0, @prev := 0;
UPDATE `units` u
JOIN (
  SELECT id, course_id,
    (@rn := IF(@prev = course_id, @rn + 1, 1)) AS rn,
    (@prev := course_id) AS dummy
  FROM `units`
  ORDER BY course_id, id
) t ON t.id = u.id
SET u.order_num = t.rn;
