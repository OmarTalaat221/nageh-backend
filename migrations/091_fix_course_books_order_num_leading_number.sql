-- Migration 090 assumed the sequence number sits at the END of the title ("Dossier 7 - ... - 07"),
-- matching course_exams' title format. course_books titles actually carry it at the START instead
-- ("01 - Dossier 1 - La science-fiction : raconter"), so 090's trailing-number regex matched
-- nothing for these rows and left their order_num exactly as scrambled as before. This corrects it
-- using the leading number instead.

UPDATE course_books cb
JOIN course_books_translations cbt ON cbt.book_id = cb.id AND cbt.language_id = 1
SET cb.order_num = CAST(REGEXP_SUBSTR(cbt.title, '^[0-9]+') AS UNSIGNED)
WHERE cbt.title REGEXP '^[0-9]+'
  AND CAST(REGEXP_SUBSTR(cbt.title, '^[0-9]+') AS UNSIGNED) > 0;
