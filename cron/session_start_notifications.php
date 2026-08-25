<?php


require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/notifications.php';

$now = get_request_now();
$today = $now->format('l');
$currentMinute = $now->format('H:i:00');
$todayDate = $now->format('Y-m-d');

$res = mysqli_query($con, "
    SELECT id, course_id, subject_id, teacher_id
    FROM class_sessions
    WHERE is_active = 1
      AND day_of_week = '{$today}'
      AND start_time = '{$currentMinute}'
");

if (!$res) {
    fwrite(STDERR, "Query failed: " . mysqli_error($con) . PHP_EOL);
    exit(1);
}

$notifiedSessions = 0;

while ($session = mysqli_fetch_assoc($res)) {
    $sessionId = (int)$session['id'];
    $courseId = $session['course_id'] ? (int)$session['course_id'] : null;
    $subjectId = $session['subject_id'] ? (int)$session['subject_id'] : null;
    $teacherId = (int)$session['teacher_id'];

    if (!mark_session_notification_once($con, $sessionId, 'started', $todayDate)) {
        continue; 
    }

    $studentIds = get_active_student_ids_for_session($con, $courseId, $subjectId);
    if (empty($studentIds)) {
        continue;
    }

    send_notification_to_users(
        $con,
        'student',
        $studentIds,
        'الحصة بدأت الآن',
        'ادخل الآن للانضمام إلى الحصة',
        ['type' => 'session_started', 'session_id' => $sessionId],
        'system',
        $teacherId
    );

    $notifiedSessions++;
}

echo "[" . $now->format('Y-m-d H:i:s') . "] Checked sessions starting at {$currentMinute} on {$today} - notified {$notifiedSessions} session(s)." . PHP_EOL;
