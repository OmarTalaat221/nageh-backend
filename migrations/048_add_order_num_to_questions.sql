ALTER TABLE `questions`
  ADD COLUMN `order_num` INT NOT NULL DEFAULT 0 AFTER `question_type`;

-- Backfill: number existing questions sequentially per exam, ordered by id.
SET @rn2 := 0, @prev2 := 0;
UPDATE `questions` q
JOIN (
  SELECT id, exam_id,
    (@rn2 := IF(@prev2 = exam_id, @rn2 + 1, 1)) AS rn2,
    (@prev2 := exam_id) AS dummy2
  FROM `questions`
  ORDER BY exam_id, id
) t ON t.id = q.id
SET q.order_num = t.rn2;
