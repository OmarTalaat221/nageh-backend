<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/manage.php'; // get_class_session_details()
require_once __DIR__ . '/../../../helpers/auto_reports.php';

if (!function_exists('session_attendance_table_exists')) {
    function session_attendance_table_exists($con): bool {
        $chk = @mysqli_query($con, "SHOW TABLES LIKE 'session_attendance'");
        return $chk && mysqli_num_rows($chk) > 0;
    }
}

if (!function_exists('get_session_expected_students')) {
    function get_session_expected_students($con, array $session): array {
        $sql = $session['course_id']
            ? "SELECT DISTINCT s.id, s.name, s.email, s.image FROM student_subscription_courses ssc
                JOIN student_subscriptions ss ON ssc.student_subscription_id = ss.id
                JOIN students s ON ss.student_id = s.id
                WHERE ssc.course_id = {$session['course_id']} AND ss.is_active = 1"
            : "SELECT DISTINCT s.id, s.name, s.email, s.image FROM student_subscription_subjects sss
                JOIN student_subscriptions ss ON sss.student_subscription_id = ss.id
                JOIN students s ON ss.student_id = s.id
                WHERE sss.subject_id = {$session['subject_id']} AND ss.is_active = 1";
        $res = mysqli_query($con, $sql);
        $students = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $students[] = format_response_images($row);
            }
        }
        return $students;
    }
}

if (!function_exists('teacherGetSessionAttendanceRoster')) {
    function teacherGetSessionAttendanceRoster($con, $teacherData, ?int $targetSessionId = null, ?string $targetDate = null) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($targetSessionId ?: $body['session_id'] ?? $body['id'] ?? $_GET['session_id'] ?? $_GET['id'] ?? $_POST['session_id'] ?? $_POST['id'] ?? $_REQUEST['session_id'] ?? $_REQUEST['id'] ?? 0);
        $date = trim($targetDate ?: $_GET['date'] ?? $body['date'] ?? $_POST['date'] ?? '') ?: date('Y-m-d');
        if ($sessionId <= 0) return ['status' => 'error', 'message' => trans('session_id_required')];

        $session = get_class_session_details($con, $sessionId);
        if (!$session || $session['teacher_id'] !== $teacherId) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }
        if (!session_attendance_table_exists($con)) {
            return ['status' => 'error', 'message' => trans('attendance_table_missing')];
        }

        $students = get_session_expected_students($con, $session);

        $presentIds = [];
        $dStmt = mysqli_prepare($con, "SELECT student_id FROM session_attendance WHERE session_id = ? AND session_date = ? AND status = 'present'");
        mysqli_stmt_bind_param($dStmt, "is", $sessionId, $date);
        mysqli_stmt_execute($dStmt);
        $dRes = mysqli_stmt_get_result($dStmt);
        while ($row = mysqli_fetch_assoc($dRes)) {
            $presentIds[] = (int)$row['student_id'];
        }

        foreach ($students as &$st) {
            $st['is_present'] = in_array($st['id'], $presentIds, true);
        }

        return ['status' => 'success', 'data' => ['session_id' => $sessionId, 'date' => $date, 'students' => $students]];
    }
}

if (!function_exists('teacherMarkSessionAttendance')) {
    function teacherMarkSessionAttendance($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($body['session_id'] ?? $body['id'] ?? $_GET['session_id'] ?? $_GET['id'] ?? $_POST['session_id'] ?? $_POST['id'] ?? $_REQUEST['session_id'] ?? $_REQUEST['id'] ?? 0);
        $date = trim($_GET['date'] ?? $body['date'] ?? $_POST['date'] ?? '') ?: date('Y-m-d');

        $presentInput = $body['present_student_ids'] ?? $body['student_ids'] ?? $body['present_ids'] ?? $body['students'] ?? $_POST['present_student_ids'] ?? $_POST['student_ids'] ?? [];
        if (is_string($presentInput)) {
            $presentInput = explode(',', $presentInput);
        }
        if (!is_array($presentInput)) {
            $presentInput = [];
        }
        $presentIds = array_values(array_unique(array_map('intval', $presentInput)));

        if ($sessionId <= 0) return ['status' => 'error', 'message' => trans('session_id_required')];

        $session = get_class_session_details($con, $sessionId);
        if (!$session || $session['teacher_id'] !== $teacherId) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }
        if (!session_attendance_table_exists($con)) {
            return ['status' => 'error', 'message' => trans('attendance_table_missing')];
        }

        $delStmt = mysqli_prepare($con, "DELETE FROM session_attendance WHERE session_id = ? AND session_date = ?");
        mysqli_stmt_bind_param($delStmt, "is", $sessionId, $date);
        mysqli_stmt_execute($delStmt);

        $insStmt = mysqli_prepare($con, "INSERT INTO session_attendance (session_id, student_id, session_date, status, marked_by_teacher_id) VALUES (?, ?, ?, 'present', ?)");
        foreach ($presentIds as $stId) {
            if ($stId > 0) {
                mysqli_stmt_bind_param($insStmt, "iisi", $sessionId, $stId, $date, $teacherId);
                mysqli_stmt_execute($insStmt);
            }
        }

        auto_generate_attendance_reports($con, $session, $date, get_session_expected_students($con, $session), $presentIds);

        return teacherGetSessionAttendanceRoster($con, $teacherData, $sessionId, $date);
    }
}

if (!function_exists('teacherGetSessionAttendanceStats')) {
    function teacherGetSessionAttendanceStats($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $sessionId = (int)($_GET['session_id'] ?? $_GET['id'] ?? 0);
        if ($sessionId <= 0) return ['status' => 'error', 'message' => trans('session_id_required')];

        $session = get_class_session_details($con, $sessionId);
        if (!$session || $session['teacher_id'] !== $teacherId) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }
        if (!session_attendance_table_exists($con)) {
            return ['status' => 'error', 'message' => trans('attendance_table_missing')];
        }

        $totalDatesRes = mysqli_query($con, "SELECT COUNT(DISTINCT session_date) AS total FROM session_attendance WHERE session_id = {$sessionId}");
        $totalOccurrences = (int)(mysqli_fetch_assoc($totalDatesRes)['total'] ?? 0);

        $students = get_session_expected_students($con, $session);
        $perStudent = [];
        foreach ($students as $st) {
            $cStmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM session_attendance WHERE session_id = ? AND student_id = ? AND status = 'present'");
            mysqli_stmt_bind_param($cStmt, "ii", $sessionId, $st['id']);
            mysqli_stmt_execute($cStmt);
            $presentCount = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt))['c'] ?? 0);
            $perStudent[] = [
                'student_id' => $st['id'],
                'name' => $st['name'],
                'email' => $st['email'],
                'present_count' => $presentCount,
                'attendance_rate' => $totalOccurrences > 0 ? round(($presentCount / $totalOccurrences) * 100, 1) : 0,
            ];
        }
        usort($perStudent, fn($a, $b) => $b['present_count'] <=> $a['present_count']);

        return [
            'status' => 'success',
            'data' => [
                'session_id' => $sessionId,
                'total_occurrences_marked' => $totalOccurrences,
                'expected_students_count' => count($students),
                'students' => $perStudent,
            ],
        ];
    }
}
