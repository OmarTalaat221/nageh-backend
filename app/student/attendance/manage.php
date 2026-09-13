<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/hr_policy.php';
require_once __DIR__ . '/../../../helpers/attendance_notifications.php';
require_once __DIR__ . '/../../teacher/sessions/manage.php';
require_once __DIR__ . '/../../admin/attendance/manage.php';
require_once __DIR__ . '/../../user/student/subscriptions/manage.php';

/**
 * Student check-in when opening a session's meeting link. Best-effort by design (the frontend
 * should still open the link even if this call fails) — records status/lateness for the day,
 * mirroring teacherStartSession's role on the teacher side (no equivalent existed before).
 */
if (!function_exists('studentCheckInSession')) {
    function studentCheckInSession($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($body['session_id'] ?? 0);
        if ($sessionId <= 0 || $studentId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $session = get_class_session_details($con, $sessionId);
        if (!$session) {
            return ['status' => 'error', 'message' => trans('session_not_found')];
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $hasAccess =
            (!empty($session['course_id']) && in_array((int)$session['course_id'], $accessible['course_ids'] ?? [])) ||
            (!empty($session['subject_id']) && in_array((int)$session['subject_id'], $accessible['subject_ids'] ?? [])) ||
            (!empty($session['teacher_id']) && in_array((int)$session['teacher_id'], $accessible['teacher_ids'] ?? []));
        if (!$hasAccess) {
            return ['status' => 'error', 'message' => trans('not_subscribed_to_session')];
        }

        $now = get_request_now();
        $occurrenceDate = $now->format('Y-m-d');
        $policy = get_hr_policy_settings($con);
        $minutesLate = max(0, (int)floor((strtotime($now->format('H:i:s')) - strtotime($session['start_time'])) / 60));
        $isExcused = 0;
        $lateNotice = has_approved_attendance_exception($con, $sessionId, $occurrenceDate, 'student', $studentId, 'late_notice');
        if ($lateNotice && $minutesLate > 0) {
            $isExcused = 1;
        }
        $status = $minutesLate > $policy['student_grace_minutes'] ? 'late' : 'present';
        // Use the app's timezone-adjusted clock, not SQL NOW() — see the same fix in
        // record_teacher_session_checkin (helpers/attendance_core.php) for why mixing the two
        // silently corrupts any later duration/lateness calculation based on this timestamp.
        $checkInTime = $now->format('Y-m-d H:i:s');

        $stmt = mysqli_prepare($con, "
            INSERT INTO session_attendance (session_id, student_id, session_date, status, check_in_time, minutes_late, is_excused, marked_by_teacher_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
            ON DUPLICATE KEY UPDATE
                check_in_time = COALESCE(check_in_time, VALUES(check_in_time)),
                status = VALUES(status),
                minutes_late = VALUES(minutes_late),
                is_excused = VALUES(is_excused)
        ");
        mysqli_stmt_bind_param($stmt, "iisssii", $sessionId, $studentId, $occurrenceDate, $status, $checkInTime, $minutesLate, $isExcused);
        mysqli_stmt_execute($stmt);

        $wasFirstCheckIn = mysqli_stmt_affected_rows($stmt) === 1; // 1 = fresh insert, 2 = update-on-duplicate
        if ($wasFirstCheckIn) {
            notify_other_party_of_checkin($con, $sessionId, $session['course_id'], $session['subject_id'], (int)$session['teacher_id'], 'student');
        }

        return ['status' => 'success', 'message' => trans('check_in_recorded_success'), 'data' => ['status' => $status, 'minutes_late' => $minutesLate]];
    }
}

/**
 * Student requests an absence-with-permission or a late-arrival notice for one occurrence of a
 * session they're subscribed to. Always reviewed by the admin.
 */
if (!function_exists('studentRequestAttendanceException')) {
    function studentRequestAttendanceException($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($body['session_id'] ?? 0);
        $occurrenceDate = trim($body['occurrence_date'] ?? $body['date'] ?? '');
        $requestType = trim($body['request_type'] ?? '');
        $reason = trim($body['reason'] ?? '');
        $expectedMinutesLate = isset($body['expected_minutes_late']) ? (int)$body['expected_minutes_late'] : null;

        if ($sessionId <= 0 || empty($occurrenceDate) || strtotime($occurrenceDate) === false) {
            return ['status' => 'error', 'message' => trans('session_id_and_date_required')];
        }
        if (!in_array($requestType, ['absence_permission', 'late_notice'], true)) {
            return ['status' => 'error', 'message' => trans('request_type_required')];
        }
        if (empty($reason)) {
            return ['status' => 'error', 'message' => trans('reason_required')];
        }
        if ($requestType === 'late_notice' && (!$expectedMinutesLate || $expectedMinutesLate <= 0)) {
            return ['status' => 'error', 'message' => trans('expected_minutes_late_required')];
        }

        $occurrenceDate = date('Y-m-d', strtotime($occurrenceDate));

        $session = get_class_session_details($con, $sessionId);
        if (!$session) {
            return ['status' => 'error', 'message' => trans('session_not_found')];
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $hasAccess =
            (!empty($session['course_id']) && in_array((int)$session['course_id'], $accessible['course_ids'] ?? [])) ||
            (!empty($session['subject_id']) && in_array((int)$session['subject_id'], $accessible['subject_ids'] ?? [])) ||
            (!empty($session['teacher_id']) && in_array((int)$session['teacher_id'], $accessible['teacher_ids'] ?? []));
        if (!$hasAccess) {
            return ['status' => 'error', 'message' => trans('not_subscribed_to_session')];
        }

        $dupStmt = mysqli_prepare($con, "SELECT id FROM attendance_exception_requests WHERE session_id = ? AND occurrence_date = ? AND requested_by_type = 'student' AND requested_by_id = ? AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($dupStmt, "isi", $sessionId, $occurrenceDate, $studentId);
        mysqli_stmt_execute($dupStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($dupStmt))) {
            return ['status' => 'error', 'message' => trans('request_already_pending')];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO attendance_exception_requests (session_id, occurrence_date, requested_by_type, requested_by_id, request_type, reason, expected_minutes_late) VALUES (?, ?, 'student', ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isissi", $sessionId, $occurrenceDate, $studentId, $requestType, $reason, $expectedMinutesLate);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to send request: ' . mysqli_error($con)];
        }

        $requestId = (int)mysqli_insert_id($con);
        notify_of_attendance_exception_request_created($con, 'student', $studentId, $requestType, $requestId);

        return ['status' => 'success', 'message' => trans('request_sent_success'), 'data' => get_attendance_exception_request_details($con, $requestId)];
    }
}

if (!function_exists('studentListMyAttendanceExceptionRequests')) {
    function studentListMyAttendanceExceptionRequests($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $stmt = mysqli_prepare($con, "SELECT id FROM attendance_exception_requests WHERE requested_by_type = 'student' AND requested_by_id = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_attendance_exception_request_details($con, (int)$row['id']);
        }

        return ['status' => 'success', 'data' => $items];
    }
}
