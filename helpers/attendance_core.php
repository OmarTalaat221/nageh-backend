<?php
// Section 10: shared attendance-recording logic used by teacherStartSession/teacherEndSession
// (app/teacher/sessions/manage.php), the student check-in endpoint (app/student/attendance/manage.php),
// and the auto-absence cron (cron/attendance_auto_process.php) — kept in one place so all three
// entry points compute lateness/fines/earnings the exact same way.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/hr_policy.php';
require_once __DIR__ . '/attendance_notifications.php';

if (!function_exists('record_teacher_session_checkin')) {
    function record_teacher_session_checkin($con, int $sessionId, int $teacherId, string $occurrenceDate, string $startTime, ?int $courseId, ?int $subjectId): void {
        $now = get_request_now();
        $policy = get_hr_policy_settings($con);
        $minutesLate = max(0, (int)floor((strtotime($now->format('H:i:s')) - strtotime($startTime)) / 60));

        $isExcused = 0;
        if ($minutesLate > 0 && has_approved_attendance_exception($con, $sessionId, $occurrenceDate, 'teacher', $teacherId, 'late_notice')) {
            $isExcused = 1;
        }
        $status = $minutesLate > $policy['teacher_grace_minutes'] ? 'late' : 'present';
        // Must use the app's timezone-adjusted clock here, not SQL NOW() — mixing the two
        // (e.g. NOW() on a UTC DB server vs get_request_now() on Africa/Cairo) silently inflates
        // the duration computed at checkout by the timezone offset between them.
        $checkInTime = $now->format('Y-m-d H:i:s');

        $stmt = mysqli_prepare($con, "
            INSERT INTO teacher_session_attendance (session_id, teacher_id, occurrence_date, status, check_in_time, minutes_late, is_excused, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'teacher')
            ON DUPLICATE KEY UPDATE
                check_in_time = COALESCE(check_in_time, VALUES(check_in_time)),
                status = VALUES(status),
                minutes_late = VALUES(minutes_late),
                is_excused = VALUES(is_excused)
        ");
        mysqli_stmt_bind_param($stmt, "iisssii", $sessionId, $teacherId, $occurrenceDate, $status, $checkInTime, $minutesLate, $isExcused);
        mysqli_stmt_execute($stmt);

        if ($status === 'late' && $isExcused === 0 && $policy['teacher_late_fine_per_minute'] > 0) {
            $fineAmount = round($policy['teacher_late_fine_per_minute'] * $minutesLate, 2);
            $txnId = adjust_teacher_wallet($con, $teacherId, 'fine', -$fineAmount, 'session', $sessionId, "خصم تأخير {$minutesLate} دقيقة");
            $uStmt = mysqli_prepare($con, "UPDATE teacher_session_attendance SET wallet_transaction_id = ? WHERE session_id = ? AND occurrence_date = ?");
            mysqli_stmt_bind_param($uStmt, "iis", $txnId, $sessionId, $occurrenceDate);
            mysqli_stmt_execute($uStmt);
            notify_of_teacher_deduction($con, $teacherId, $fineAmount, "التأخر {$minutesLate} دقيقة عن موعد الحصة");
        }

        notify_other_party_of_checkin($con, $sessionId, $courseId, $subjectId, $teacherId, 'teacher');
    }
}

if (!function_exists('record_teacher_session_checkout')) {
    function record_teacher_session_checkout($con, int $sessionId, int $teacherId, string $occurrenceDate, string $startTime, string $endTime): void {
        $rowStmt = mysqli_prepare($con, "SELECT check_in_time, status FROM teacher_session_attendance WHERE session_id = ? AND occurrence_date = ? LIMIT 1");
        mysqli_stmt_bind_param($rowStmt, "is", $sessionId, $occurrenceDate);
        mysqli_stmt_execute($rowStmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($rowStmt));

        // No check-in row at all means the teacher ended the session without ever "starting" it
        // (or the auto-absence cron already ran first) — nothing to earn, nothing to check out.
        if (!$row || $row['status'] === 'absent') {
            return;
        }

        $checkInTime = $row['check_in_time'] ?: "{$occurrenceDate} {$startTime}";
        $checkOutTime = get_request_now()->format('Y-m-d H:i:s');
        $durationMinutes = max(1, (int)round((strtotime($checkOutTime) - strtotime($checkInTime)) / 60));

        $payRate = get_teacher_pay_rate($con, $teacherId);
        $earning = compute_session_earning($payRate['pay_model'], $payRate['rate_amount'], $durationMinutes);

        $txnId = adjust_teacher_wallet($con, $teacherId, 'earning', $earning, 'session', $sessionId, 'أجر حصة');

        $uStmt = mysqli_prepare($con, "UPDATE teacher_session_attendance SET check_out_time = ?, earning_amount = ?, wallet_transaction_id = ? WHERE session_id = ? AND occurrence_date = ?");
        mysqli_stmt_bind_param($uStmt, "sdiis", $checkOutTime, $earning, $txnId, $sessionId, $occurrenceDate);
        mysqli_stmt_execute($uStmt);
    }
}

if (!function_exists('record_teacher_auto_absence')) {
    function record_teacher_auto_absence($con, int $sessionId, int $teacherId, string $occurrenceDate): void {
        $policy = get_hr_policy_settings($con);
        $fineAmount = $policy['teacher_absence_fine_amount'];

        $stmt = mysqli_prepare($con, "INSERT IGNORE INTO teacher_session_attendance (session_id, teacher_id, occurrence_date, status, is_excused, recorded_by) VALUES (?, ?, ?, 'absent', 0, 'system')");
        mysqli_stmt_bind_param($stmt, "iis", $sessionId, $teacherId, $occurrenceDate);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return; // already recorded (e.g. by a previous cron tick) — never double-fine
        }

        if ($fineAmount > 0) {
            $txnId = adjust_teacher_wallet($con, $teacherId, 'fine', -$fineAmount, 'session', $sessionId, 'غرامة غياب بدون إذن');
            $uStmt = mysqli_prepare($con, "UPDATE teacher_session_attendance SET wallet_transaction_id = ? WHERE session_id = ? AND occurrence_date = ?");
            mysqli_stmt_bind_param($uStmt, "iis", $txnId, $sessionId, $occurrenceDate);
            mysqli_stmt_execute($uStmt);
        }

        notify_of_unexcused_absence($con, 'teacher', $teacherId, $sessionId);

        $absenceCount = count_unexcused_absences_this_month($con, 'teacher_session_attendance', 'teacher_id', $teacherId);
        if ($absenceCount >= $policy['teacher_max_absence_without_permission']) {
            // A dedicated flag, separate from `is_active` (the admin's own enable/disable switch):
            // this restriction only limits the teacher to the support chat, it never blocks login.
            $stmt2 = mysqli_prepare($con, "UPDATE teachers SET is_attendance_suspended = 1 WHERE id = ? AND is_attendance_suspended = 0");
            mysqli_stmt_bind_param($stmt2, "i", $teacherId);
            mysqli_stmt_execute($stmt2);
            if (mysqli_stmt_affected_rows($stmt2) > 0) {
                notify_of_teacher_suspended($con, $teacherId);
            }
        }
    }
}

if (!function_exists('record_student_auto_absence')) {
    function record_student_auto_absence($con, int $sessionId, int $studentId, string $occurrenceDate, ?int $subscriptionId): void {
        $policy = get_hr_policy_settings($con);

        $stmt = mysqli_prepare($con, "INSERT IGNORE INTO session_attendance (session_id, student_id, session_date, status, is_excused) VALUES (?, ?, ?, 'absent', 0)");
        mysqli_stmt_bind_param($stmt, "iis", $sessionId, $studentId, $occurrenceDate);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return; // already recorded
        }

        notify_of_unexcused_absence($con, 'student', $studentId, $sessionId);

        $absenceCount = count_unexcused_absences_this_month($con, 'session_attendance', 'student_id', $studentId);
        if ($absenceCount >= $policy['student_max_absence_without_permission'] && $subscriptionId) {
            $stmt2 = mysqli_prepare($con, "UPDATE student_subscriptions SET is_active = 0 WHERE id = ? AND is_active = 1");
            mysqli_stmt_bind_param($stmt2, "i", $subscriptionId);
            mysqli_stmt_execute($stmt2);
            if (mysqli_stmt_affected_rows($stmt2) > 0) {
                notify_of_student_subscription_frozen($con, $studentId, $subscriptionId);
            }
        }
    }
}
