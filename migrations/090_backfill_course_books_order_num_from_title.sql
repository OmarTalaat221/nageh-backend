-- Same root cause as migrations/089 (course_exams): course_books.order_num defaulted to creation
-- order on insert, which does not match the sequence already encoded in the title itself (e.g.
-- "Dossier 7 - ... - 07"). Content was not always uploaded in title order (a later "L'épreuve du
-- Brevet" proficiency book numbered 09/10 can easily have been uploaded before earlier lessons were
-- finished), so sorting by the existing order_num shows them out of sequence for both the admin
-- drag-reorder list and the student-facing list. Recover the real order from the trailing number in
-- the Arabic title, same technique as 089.

UPDATE course_books cb
JOIN course_books_translations cbt ON cbt.book_id = cb.id AND cbt.language_id = 1
SET cb.order_num = CAST(REGEXP_SUBSTR(cbt.title, '[0-9]+$') AS UNSIGNED)
WHERE REGEXP_SUBSTR(cbt.title, '[0-9]+$') IS NOT NULL
  AND CAST(REGEXP_SUBSTR(cbt.title, '[0-9]+$') AS UNSIGNED) > 0;
