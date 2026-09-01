<?php
// Automatically generates student_reports rows (sender_type = 'system') the moment an
// exam is graded, attendance is marked, or a course is fully completed. Manual report
// creation (teacher/admin) is untouched and lives in app/*/reports/manage.php.
require_once __DIR__ . '/entities.php';

if (!function_exists('auto_report_grade_label')) {
    function auto_report_grade_label(float $percentage): string {
        if ($percentage >= 90) return 'ممتاز';
        if ($percentage >= 80) return 'جيد جدًا';
        if ($percentage >= 65) return 'جيد';
        if ($percentage >= 50) return 'مقبول';
        return 'راسب';
    }
}

if (!function_exists('auto_generate_exam_result_report')) {
    function auto_generate_exam_result_report($con, int $attemptId): void {
        $stmt = mysqli_prepare($con, "
            SELECT ea.student_id, ea.total_score, ea.max_score, ea.graded_by_type, ea.graded_by_id,
                   ce.subject_id, ce.course_id, COALESCE(cet.title, '') AS exam_title
            FROM exam_attempts ea
            JOIN course_exams ce ON ea.exam_id = ce.id
            LEFT JOIN course_exams_translations cet ON ce.id = cet.exam_id AND cet.language_id = 1
            WHERE ea.id = ? LIMIT 1
        ");
        if (!$stmt) return;
        mysqli_stmt_bind_param($stmt, "i", $attemptId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return;

        $studentId = (int)$row['student_id'];
        $subjectId = $row['subject_id'] !== null ? (int)$row['subject_id'] : null;
        $courseId = $row['course_id'] !== null ? (int)$row['course_id'] : null;
        $teacherId = $row['graded_by_type'] === 'teacher' && $row['graded_by_id'] ? (int)$row['graded_by_id'] : null;
        $adminId = $row['graded_by_type'] === 'admin' && $row['graded_by_id'] ? (int)$row['graded_by_id'] : null;
        $score = $row['total_score'] !== null ? (float)$row['total_score'] : 0.0;
        $maxScore = (float)$row['max_score'];
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.0;
        $gradeLabel = auto_report_grade_label($percentage);
        $examTitle = $row['exam_title'] !== '' ? $row['exam_title'] : 'الامتحان';
        $title = 'نتيجة امتحان: ' . $examTitle;
        $description = 'حصل الطالب على ' . $score . ' من ' . $maxScore . ' (' . $percentage . '%) في "' . $examTitle . '".';

        $existingRes = mysqli_query($con, "SELECT id FROM student_reports WHERE exam_attempt_id = " . (int)$attemptId . " LIMIT 1");
        $existing = $existingRes ? mysqli_fetch_assoc($existingRes) : null;

        if ($existing) {
            $uStmt = mysqli_prepare($con, "UPDATE student_reports SET score = ?, max_score = ?, percentage = ?, grade_label = ?, title = ?, description = ?, subject_id = ?, course_id = ?, teacher_id = ?, admin_id = ?, report_date = CURDATE() WHERE id = ?");
            if (!$uStmt) return;
            $existingId = (int)$existing['id'];
            mysqli_stmt_bind_param($uStmt, "dddsssiiiii", $score, $maxScore, $percentage, $gradeLabel, $title, $description, $subjectId, $courseId, $teacherId, $adminId, $existingId);
            mysqli_stmt_execute($uStmt);
            return;
        }

        $iStmt = mysqli_prepare($con, "
            INSERT INTO student_reports
                (sender_type, student_id, subject_id, course_id, teacher_id, admin_id, exam_attempt_id, report_type, score, max_score, percentage, grade_label, title, description, report_date)
            VALUES ('system', ?, ?, ?, ?, ?, ?, 'exam_result', ?, ?, ?, ?, ?, ?, CURDATE())
        ");
        if (!$iStmt) return;
        mysqli_stmt_bind_param($iStmt, "iiiiiidddsss", $studentId, $subjectId, $courseId, $teacherId, $adminId, $attemptId, $score, $maxScore, $percentage, $gradeLabel, $title, $description);
        mysqli_stmt_execute($iStmt);
    }
}

if (!function_exists('auto_generate_attendance_reports')) {
    function auto_generate_attendance_reports($con, array $session, string $date, array $students, array $presentIds): void {
        $sessionId = (int)($session['id'] ?? 0);
        if ($sessionId <= 0) return;
        $subjectId = !empty($session['subject_id']) ? (int)$session['subject_id'] : null;
        $courseId = !empty($session['course_id']) ? (int)$session['course_id'] : null;
        $teacherId = !empty($session['teacher_id']) ? (int)$session['teacher_id'] : null;

        foreach ($students as $student) {
            $studentId = (int)($student['id'] ?? 0);
            if ($studentId <= 0) continue;

            $isPresent = in_array($studentId, $presentIds, true);
            $statusLabel = $isPresent ? 'حاضر' : 'غائب';
            $score = $isPresent ? 100.0 : 0.0;
            $maxScore = 100.0;
            $title = 'الحضور بتاريخ ' . $date;
            $description = 'حالة الطالب في الحصة بتاريخ ' . $date . ': ' . $statusLabel . '.';

            $eStmt = mysqli_prepare($con, "SELECT id FROM student_reports WHERE session_id = ? AND student_id = ? AND report_date = ? LIMIT 1");
            if (!$eStmt) continue;
            mysqli_stmt_bind_param($eStmt, "iis", $sessionId, $studentId, $date);
            mysqli_stmt_execute($eStmt);
            $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($eStmt));

            if ($existing) {
                $uStmt = mysqli_prepare($con, "UPDATE student_reports SET title = ?, description = ?, grade_label = ?, score = ?, max_score = ?, percentage = ?, teacher_id = ? WHERE id = ?");
                if (!$uStmt) continue;
                $existingId = (int)$existing['id'];
                mysqli_stmt_bind_param($uStmt, "sssdddii", $title, $description, $statusLabel, $score, $maxScore, $score, $teacherId, $existingId);
                mysqli_stmt_execute($uStmt);
                continue;
            }

            $iStmt = mysqli_prepare($con, "
                INSERT INTO student_reports
                    (sender_type, student_id, subject_id, course_id, session_id, teacher_id, report_type, score, max_score, percentage, grade_label, title, description, report_date)
                VALUES ('system', ?, ?, ?, ?, ?, 'attendance', ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$iStmt) continue;
            mysqli_stmt_bind_param($iStmt, "iiiiidddssss", $studentId, $subjectId, $courseId, $sessionId, $teacherId, $score, $maxScore, $score, $statusLabel, $title, $description, $date);
            mysqli_stmt_execute($iStmt);
        }
    }
}

if (!function_exists('auto_generate_course_completion_report')) {
    function auto_generate_course_completion_report($con, int $studentId, int $courseId): void {
        if ($studentId <= 0 || $courseId <= 0) return;

        $stats = get_student_course_progress_stats($con, $studentId, $courseId);
        if ((int)($stats['total_lessons_count'] ?? 0) <= 0) return;
        if ((float)($stats['progress_percentage'] ?? 0) < 100.0) return;

        $eStmt = mysqli_prepare($con, "SELECT id FROM student_reports WHERE student_id = ? AND course_id = ? AND report_type = 'academic_performance' LIMIT 1");
        if (!$eStmt) return;
        mysqli_stmt_bind_param($eStmt, "ii", $studentId, $courseId);
        mysqli_stmt_execute($eStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($eStmt))) return;

        $course = get_course_details($con, $courseId);
        $courseName = $course['name'] ?? ('كورس #' . $courseId);
        $subjectId = !empty($course['subject_id']) ? (int)$course['subject_id'] : null;
        $title = 'إكمال الكورس: ' . $courseName;
        $description = 'أكمل الطالب جميع دروس كورس "' . $courseName . '" بنسبة 100%.';

        $iStmt = mysqli_prepare($con, "
            INSERT INTO student_reports
                (sender_type, student_id, subject_id, course_id, report_type, percentage, grade_label, title, description, report_date)
            VALUES ('system', ?, ?, ?, 'academic_performance', 100.00, 'مكتمل', ?, ?, CURDATE())
        ");
        if (!$iStmt) return;
        mysqli_stmt_bind_param($iStmt, "iiiss", $studentId, $subjectId, $courseId, $title, $description);
        mysqli_stmt_execute($iStmt);
    }
}
