-- Every unit-scoped book (course_books.unit_id set) only ever had unit_id written on create/update;
-- course_id/subject_id were left NULL on the row forever and only ever resolved at read time by
-- whichever endpoint bothered to join units. That made per-book ownership/listing queries that key
-- off course_books.course_id directly (instead of resolving through the unit) silently miss these
-- rows. Backfill the stored values from the unit they already point to.

UPDATE course_books cb
JOIN units u ON cb.unit_id = u.id
SET cb.course_id = u.course_id
WHERE cb.unit_id IS NOT NULL
  AND cb.course_id IS NULL
  AND u.course_id IS NOT NULL;

UPDATE course_books cb
JOIN units u ON cb.unit_id = u.id
SET cb.subject_id = u.subject_id
WHERE cb.unit_id IS NOT NULL
  AND cb.subject_id IS NULL
  AND u.subject_id IS NOT NULL;
