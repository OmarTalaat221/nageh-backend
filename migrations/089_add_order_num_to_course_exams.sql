-- course_exams had no manual-sort column at all, so every listing (admin and student) fell back to
-- id/created_at order - which does not track the sequence the content was actually built in. The
-- Arabic titles already carry the intended sequence as a trailing number (e.g. "... - 07"), so the
-- backfill below recovers it from there instead of guessing from id/created_at.

ALTER TABLE course_exams
    ADD COLUMN order_num INT UNSIGNED NOT NULL DEFAULT 1 AFTER unit_id;

UPDATE course_exams ce
JOIN course_exams_translations cet ON cet.exam_id = ce.id AND cet.language_id = 1
SET ce.order_num = CAST(REGEXP_SUBSTR(cet.title, '[0-9]+$') AS UNSIGNED)
WHERE REGEXP_SUBSTR(cet.title, '[0-9]+$') IS NOT NULL
  AND CAST(REGEXP_SUBSTR(cet.title, '[0-9]+$') AS UNSIGNED) > 0;
