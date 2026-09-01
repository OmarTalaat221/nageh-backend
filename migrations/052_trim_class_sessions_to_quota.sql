-- Reduces the seeded `class_sessions` demo data down to a manageable number per weekday, so the
-- admin schedule calendar isn't cluttered with dozens of test sessions per day.
--
-- Unlike migration 051 (which only removed junk sessions with zero usage history), this one
-- deletes sessions + ALL their related data unconditionally: session_attendance, session_files
-- (and session_file_submissions pointing at those files), session_reschedule_requests,
-- class_session_notification_log, and student_reports.session_id references. Run this only if
-- you're OK losing attendance/report/file history tied to the trimmed sessions (this was
-- confirmed for this dev/demo dataset).
--
-- Step A: remove the same 3 "broken" categories as migration 051, but this time unconditionally
--         (no usage-history guard) — duplicates, missing teacher_id, no subject_id and no course_id.
-- Step B: whatever is left, keep only the first @KEEP_PER_DAY sessions (by id) per day_of_week,
--         delete the rest.
--
-- Adjust @KEEP_PER_DAY below to change how many sessions remain per weekday.

SET @KEEP_PER_DAY = 3;

-- ============================================================
-- STEP A — drop broken sessions (duplicates / missing teacher / no subject+course), no matter
-- what history they carry.
-- ============================================================
DROP TEMPORARY TABLE IF EXISTS tmp_broken_sessions;
CREATE TEMPORARY TABLE tmp_broken_sessions AS
SELECT cs.id
FROM class_sessions cs
LEFT JOIN teachers t ON cs.teacher_id = t.id
LEFT JOIN class_sessions dup
    ON dup.teacher_id = cs.teacher_id
   AND dup.day_of_week = cs.day_of_week
   AND dup.start_time = cs.start_time
   AND dup.end_time = cs.end_time
   AND dup.subject_id <=> cs.subject_id
   AND dup.course_id <=> cs.course_id
   AND dup.id < cs.id
WHERE dup.id IS NOT NULL OR t.id IS NULL OR (cs.subject_id IS NULL AND cs.course_id IS NULL);

DELETE sfs FROM session_file_submissions sfs
JOIN session_files sf ON sfs.session_file_id = sf.id
JOIN tmp_broken_sessions b ON sf.session_id = b.id;
DELETE sf FROM session_files sf JOIN tmp_broken_sessions b ON sf.session_id = b.id;
DELETE sa FROM session_attendance sa JOIN tmp_broken_sessions b ON sa.session_id = b.id;
DELETE srr FROM session_reschedule_requests srr JOIN tmp_broken_sessions b ON srr.session_id = b.id;
DELETE csnl FROM class_session_notification_log csnl JOIN tmp_broken_sessions b ON csnl.session_id = b.id;
DELETE sr FROM student_reports sr JOIN tmp_broken_sessions b ON sr.session_id = b.id;
DELETE cs FROM class_sessions cs JOIN tmp_broken_sessions b ON cs.id = b.id;

DROP TEMPORARY TABLE tmp_broken_sessions;

-- ============================================================
-- STEP B — trim remaining sessions down to @KEEP_PER_DAY per weekday.
-- ============================================================
DROP TEMPORARY TABLE IF EXISTS tmp_sessions_to_trim;
CREATE TEMPORARY TABLE tmp_sessions_to_trim AS
SELECT cs.id
FROM class_sessions cs
WHERE (
    SELECT COUNT(*) FROM class_sessions cs2
    WHERE cs2.day_of_week = cs.day_of_week AND cs2.id <= cs.id
) > @KEEP_PER_DAY;

DELETE sfs FROM session_file_submissions sfs
JOIN session_files sf ON sfs.session_file_id = sf.id
JOIN tmp_sessions_to_trim t ON sf.session_id = t.id;
DELETE sf FROM session_files sf JOIN tmp_sessions_to_trim t ON sf.session_id = t.id;
DELETE sa FROM session_attendance sa JOIN tmp_sessions_to_trim t ON sa.session_id = t.id;
DELETE srr FROM session_reschedule_requests srr JOIN tmp_sessions_to_trim t ON srr.session_id = t.id;
DELETE csnl FROM class_session_notification_log csnl JOIN tmp_sessions_to_trim t ON csnl.session_id = t.id;
DELETE sr FROM student_reports sr JOIN tmp_sessions_to_trim t ON sr.session_id = t.id;
DELETE cs FROM class_sessions cs JOIN tmp_sessions_to_trim t ON cs.id = t.id;

DROP TEMPORARY TABLE tmp_sessions_to_trim;
