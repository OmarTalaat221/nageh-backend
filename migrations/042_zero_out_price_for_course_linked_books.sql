-- Course/unit-linked books are bundled with the course purchase and must
-- never carry their own separate price (backend now enforces this on
-- create/update). This backfills any pre-existing rows that were linked to
-- a course/unit but still had a leftover nonzero price.
UPDATE `course_books`
SET `price` = 0
WHERE (`course_id` IS NOT NULL OR `unit_id` IS NOT NULL)
  AND `price` <> 0;
