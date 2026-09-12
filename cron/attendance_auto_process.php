<?php
// Section 10: runs every minute (same crontab entry style as session_start_notifications.php).
// For every class_sessions occurrence whose grace period has just elapsed, auto-detects an
// unexcused no-show (teacher or student) and applies the configured consequence, unless an
// approved absence_permission request already covers that exact occurrence.

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/notifications.php';
require_once __DIR__ . '/../helpers/hr_policy.php';
require_once __DIR__ . '/../helpers/attendance_core.php';

if (!function_exists('cron_get_active_student_subscription_pairs')) {
    function cron_get_active_student_subscription_pairs($con, ?int $courseId, ?int $subjectId, ?int $teacherId): array {
        $pairs = [];

        if (!empty($courseId)) {
            $res = mysqli_query($con, "
                SELECT DISTINCT ss.student_id, ss.id AS subscription_id FROM student_subscription_courses ssc
                JOIN student_subscriptions ss ON ssc.student_subscription_id = ss.id
                WHERE ssc.course_id = {$courseId} AND ss.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= NOW())
            ");
            if ($res) while ($row = mysqli_fetch_assoc($res)) $pairs[(int)$row['student_id']] = (int)$row['subscription_id'];
        }
        if (!empty($subjectId)) {
            $res = mysqli_query($con, "
                SELECT DISTINCT ss.student_id, ss.id AS subscription_id FROM student_subscription_subjects sss
                JOIN student_subscriptions ss ON sss.student_subscription_id = ss.id
                WHERE sss.subject_id = {$subjectId} AND ss.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= NOW())
            ");
            if ($res) while ($row = mysqli_fetch_assoc($res)) $pairs[(int)$row['student_id']] = (int)$row['subscription_id'];
        }
        if (!empty($teacherId)) {
            $res = mysqli_query($con, "
                SELECT DISTINCT ss.student_id, ss.id AS subscription_id FROM student_subscription_teachers sst
                JOIN student_subscriptions ss ON sst.student_subscription_id = ss.id
                WHERE sst.teacher_id = {$teacherId} AND ss.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= NOW())
            ");
            if ($res) while ($row = mysqli_fetch_assoc($res)) $pairs[(int)$row['student_id']] = (int)$row['subscription_id'];
        }

        return $pairs;
    }
}

if (!function_exists('cron_record_excused_teacher_absence')) {
    function cron_record_excused_teacher_absence($con, int $sessionId, int $teacherId, string $occurrenceDate): void {
        $stmt = mysqli_prepare($con, "INSERT IGNORE INTO teacher_session_attendance (session_id, teacher_id, occurrence_date, status, is_excused, recorded_by) VALUES (?, ?, ?, 'absent', 1, 'system')");
        mysqli_stmt_bind_param($stmt, "iis", $sessionId, $teacherId, $occurrenceDate);
        mysqli_stmt_execute($stmt);
    }
}

if (!function_exists('cron_record_excused_student_absence')) {
    function cron_record_excused_student_absence($con, int $sessionId, int $studentId, string $occurrenceDate): void {
        $stmt = mysqli_prepare($con, "INSERT IGNORE INTO session_attendance (session_id, student_id, session_date, status, is_excused) VALUES (?, ?, ?, 'absent', 1)");
        mysqli_stmt_bind_param($stmt, "iis", $sessionId, $studentId, $occurrenceDate);
        mysqli_stmt_execute($stmt);
    }
}

$now = get_request_now();
$today = $now->format('l');
$todayDate = $now->format('Y-m-d');
$policy = get_hr_policy_settings($con);

$teacherCheckTime = (clone $now)->modify("-{$policy['teacher_grace_minutes']} minutes")->format('H:i:00');
$studentCheckTime = (clone $now)->modify("-{$policy['student_grace_minutes']} minutes")->format('H:i:00');

$teacherAbsences = 0;
$studentAbsences = 0;

// --- Teacher side: one no-show check per session whose teacher-grace window just elapsed. ---
$teacherRes = mysqli_query($con, "
    SELECT id, teacher_id, course_id, subject_id FROM class_sessions
    WHERE is_active = 1 AND day_of_week = '{$today}' AND start_time = '{$teacherCheckTime}'
");
if ($teacherRes) {
    while ($session = mysqli_fetch_assoc($teacherRes)) {
        $sessionId = (int)$session['id'];
        $teacherId = (int)$session['teacher_id'];

        $alreadyCheckedIn = mysqli_fetch_assoc(mysqli_query($con, "SELECT id FROM teacher_session_attendance WHERE session_id = {$sessionId} AND occurrence_date = '{$todayDate}' LIMIT 1"));
        if ($alreadyCheckedIn) continue;

        $excused = has_approved_attendance_exception($con, $sessionId, $todayDate, 'teacher', $teacherId, 'absence_permission');
        if ($excused) {
            cron_record_excused_teacher_absence($con, $sessionId, $teacherId, $todayDate);
            continue;
        }

        record_teacher_auto_absence($con, $sessionId, $teacherId, $todayDate);
        $teacherAbsences++;
    }
}

// --- Student side: one no-show check per subscribed student whose student-grace window just elapsed. ---
$studentRes = mysqli_query($con, "
    SELECT id, teacher_id, course_id, subject_id FROM class_sessions
    WHERE is_active = 1 AND day_of_week = '{$today}' AND start_time = '{$studentCheckTime}'
");
if ($studentRes) {
    while ($session = mysqli_fetch_assoc($studentRes)) {
        $sessionId = (int)$session['id'];
        $courseId = $session['course_id'] ? (int)$session['course_id'] : null;
        $subjectId = $session['subject_id'] ? (int)$session['subject_id'] : null;
        $teacherId = (int)$session['teacher_id'];

        $pairs = cron_get_active_student_subscription_pairs($con, $courseId, $subjectId, $teacherId);
        foreach ($pairs as $studentId => $subscriptionId) {
            $alreadyCheckedIn = mysqli_fetch_assoc(mysqli_query($con, "SELECT id FROM session_attendance WHERE session_id = {$sessionId} AND student_id = {$studentId} AND session_date = '{$todayDate}' LIMIT 1"));
            if ($alreadyCheckedIn) continue;

            $excused = has_approved_attendance_exception($con, $sessionId, $todayDate, 'student', $studentId, 'absence_permission');
            if ($excused) {
                cron_record_excused_student_absence($con, $sessionId, $studentId, $todayDate);
                continue;
            }

            record_student_auto_absence($con, $sessionId, $studentId, $todayDate, $subscriptionId);
            $studentAbsences++;
        }
    }
}

echo "[" . $now->format('Y-m-d H:i:s') . "] Auto-processed attendance - {$teacherAbsences} teacher absence(s), {$studentAbsences} student absence(s)." . PHP_EOL;
