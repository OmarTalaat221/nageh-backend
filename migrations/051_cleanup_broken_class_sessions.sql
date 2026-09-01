-- Cleans up junk rows in `class_sessions` in 3 categories: exact duplicates (same teacher/day/
-- time/subject/course), sessions pointing at a teacher_id that no longer exists, and sessions
-- with neither subject_id nor course_id set.
--
-- Safety: a session is only auto-deleted if it has ZERO usage history (no attendance marked, no
-- files, no reschedule requests, no student_reports pointing at it). Anything matching one of the
-- 3 categories but that DOES have history is left alone and only shown in the review queries below
-- (section 2) so an admin can look at it manually before deciding.
--
-- HOW TO RUN: execute section 1 first and read the results. Only run section 3 (the actual
-- DELETE) after you're satisfied with what section 1/2 show.

-- ============================================================
-- SECTION 1 — REVIEW: sessions that are safe to auto-delete (no usage history at all)
-- ============================================================
SELECT
    cs.id, cs.teacher_id, cs.subject_id, cs.course_id, cs.day_of_week, cs.start_time, cs.end_time,
    CASE
        WHEN dup.id IS NOT NULL THEN 'duplicate'
        WHEN t.id IS NULL THEN 'missing_teacher'
        WHEN cs.subject_id IS NULL AND cs.course_id IS NULL THEN 'no_subject_or_course'
    END AS reason
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
WHERE (dup.id IS NOT NULL OR t.id IS NULL OR (cs.subject_id IS NULL AND cs.course_id IS NULL))
  AND NOT EXISTS (SELECT 1 FROM session_attendance sa WHERE sa.session_id = cs.id)
  AND NOT EXISTS (SELECT 1 FROM session_files sf WHERE sf.session_id = cs.id)
  AND NOT EXISTS (SELECT 1 FROM session_reschedule_requests srr WHERE srr.session_id = cs.id)
  AND NOT EXISTS (SELECT 1 FROM student_reports sr WHERE sr.session_id = cs.id);

-- ============================================================
-- SECTION 2 — REVIEW: same 3 categories but WITH usage history — NOT touched by section 3,
-- listed here so you can decide manually what to do with each one.
-- ============================================================
SELECT
    cs.id, cs.teacher_id, cs.subject_id, cs.course_id, cs.day_of_week, cs.start_time, cs.end_time,
    CASE
        WHEN dup.id IS NOT NULL THEN 'duplicate'
        WHEN t.id IS NULL THEN 'missing_teacher'
        WHEN cs.subject_id IS NULL AND cs.course_id IS NULL THEN 'no_subject_or_course'
    END AS reason,
    (SELECT COUNT(*) FROM session_attendance sa WHERE sa.session_id = cs.id) AS attendance_rows,
    (SELECT COUNT(*) FROM session_files sf WHERE sf.session_id = cs.id) AS file_rows,
    (SELECT COUNT(*) FROM session_reschedule_requests srr WHERE srr.session_id = cs.id) AS reschedule_rows,
    (SELECT COUNT(*) FROM student_reports sr WHERE sr.session_id = cs.id) AS report_rows
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
WHERE (dup.id IS NOT NULL OR t.id IS NULL OR (cs.subject_id IS NULL AND cs.course_id IS NULL))
  AND (
        EXISTS (SELECT 1 FROM session_attendance sa WHERE sa.session_id = cs.id)
     OR EXISTS (SELECT 1 FROM session_files sf WHERE sf.session_id = cs.id)
     OR EXISTS (SELECT 1 FROM session_reschedule_requests srr WHERE srr.session_id = cs.id)
     OR EXISTS (SELECT 1 FROM student_reports sr WHERE sr.session_id = cs.id)
  );

-- ============================================================
-- SECTION 3 — DELETE: only run after reviewing section 1. Removes exactly the rows section 1
-- listed (recomputed the same way, guarded by the same "zero usage history" condition).
-- ============================================================
DELETE cs FROM class_sessions cs
LEFT JOIN teachers t ON cs.teacher_id = t.id
LEFT JOIN class_sessions dup
    ON dup.teacher_id = cs.teacher_id
   AND dup.day_of_week = cs.day_of_week
   AND dup.start_time = cs.start_time
   AND dup.end_time = cs.end_time
   AND dup.subject_id <=> cs.subject_id
   AND dup.course_id <=> cs.course_id
   AND dup.id < cs.id
WHERE (dup.id IS NOT NULL OR t.id IS NULL OR (cs.subject_id IS NULL AND cs.course_id IS NULL))
  AND NOT EXISTS (SELECT 1 FROM session_attendance sa WHERE sa.session_id = cs.id)
  AND NOT EXISTS (SELECT 1 FROM session_files sf WHERE sf.session_id = cs.id)
  AND NOT EXISTS (SELECT 1 FROM session_reschedule_requests srr WHERE srr.session_id = cs.id)
  AND NOT EXISTS (SELECT 1 FROM student_reports sr WHERE sr.session_id = cs.id);
