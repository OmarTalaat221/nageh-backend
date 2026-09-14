<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../courses/manage.php';
require_once __DIR__ . '/../../user/student/subscriptions/manage.php';
require_once __DIR__ . '/../../../helpers/notifications.php';
require_once __DIR__ . '/../../../helpers/admin_notifications.php';
require_once __DIR__ . '/../../../helpers/attendance_core.php';
require_once __DIR__ . '/files.php';

if (!function_exists('is_teacher_request')) {
    function is_teacher_request(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($uri, '/teacher/');
    }
}

if (!function_exists('is_admin_or_teacher_request')) {
    function is_admin_or_teacher_request(): bool {
        if (function_exists('is_admin_request') && is_admin_request()) {
            return true;
        }
        if (is_teacher_request()) {
            return true;
        }
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            if (function_exists('verifyTeacherAccessToken') && verifyTeacherAccessToken($token)) {
                return true;
            }
            if (function_exists('verifyAdminAccessToken') && verifyAdminAccessToken($token)) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Whether this teacher is allowed to schedule a session for the given course: either they're
 * directly assigned to it (courses.teacher_id or an active course_teachers row), or the course
 * has no teacher assigned at all - in which case any teacher of its subject may use it.
 */
if (!function_exists('teacher_can_use_course_for_session')) {
    function teacher_can_use_course_for_session($con, int $teacherId, int $courseId): bool {
        $stmt = mysqli_prepare($con, "SELECT c.id, c.teacher_id, c.subject_id FROM courses c WHERE c.id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $courseId);
        mysqli_stmt_execute($stmt);
        $course = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$course) return false;

        if ((int)$course['teacher_id'] === $teacherId) return true;

        $ctStmt = mysqli_prepare($con, "SELECT id FROM course_teachers WHERE course_id = ? AND teacher_id = ? AND is_active = 1 LIMIT 1");
        mysqli_stmt_bind_param($ctStmt, "ii", $courseId, $teacherId);
        mysqli_stmt_execute($ctStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($ctStmt))) return true;

        if ($course['teacher_id'] === null) {
            $anyCtStmt = mysqli_prepare($con, "SELECT id FROM course_teachers WHERE course_id = ? AND is_active = 1 LIMIT 1");
            mysqli_stmt_bind_param($anyCtStmt, "i", $courseId);
            mysqli_stmt_execute($anyCtStmt);
            $hasAnyTeacher = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($anyCtStmt));
            if (!$hasAnyTeacher) {
                $courseSubjectId = (int)($course['subject_id'] ?? 0);
                // update-1: a course/package with NEITHER an assigned teacher NOR a subject is
                // fully open - any teacher can add a session to it (e.g. a content-free package
                // where a different teacher runs each weekly slot). Once it has a subject, the
                // existing subject-assignment check still applies as before.
                if ($courseSubjectId <= 0) {
                    return true;
                }
                return is_subject_assigned_to_teacher($con, $teacherId, $courseSubjectId);
            }
        }

        return false;
    }
}

/**
 * Whether a session is happening right now, using the request's effective timezone (the client's
 * `_time_zone` if it sent one, otherwise Africa/Cairo - see get_request_timezone()).
 */
if (!function_exists('class_session_is_available_now')) {
    function class_session_is_available_now(array $session): bool {
        $now = get_request_now();
        $today = $now->format('l'); // "Sunday".."Saturday"
        $nowTime = $now->format('H:i:s');
        return $session['day_of_week'] === $today
            && $nowTime >= $session['start_time']
            && $nowTime <= $session['end_time'];
    }
}

if (!function_exists('get_session_calculated_date')) {
    function get_session_calculated_date($dayOfWeek) {
        if (empty($dayOfWeek)) return date('Y-m-d');
        $todayName = date('l');
        if (strcasecmp($todayName, $dayOfWeek) === 0) {
            return date('Y-m-d');
        }
        return date('Y-m-d', strtotime("next " . $dayOfWeek));
    }
}

if (!function_exists('is_session_started_today')) {
    function is_session_started_today($con, int $sessionId, string $sessionDate): bool {
        ensure_class_session_notification_log_table($con);
        $stmt = @mysqli_prepare($con, "SELECT id FROM class_session_notification_log WHERE session_id = ? AND notify_type = 'started' AND session_date = ? LIMIT 1");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "is", $sessionId, $sessionDate);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        return $res ? (bool)mysqli_fetch_assoc($res) : false;
    }
}

if (!function_exists('is_session_ended_today')) {
    function is_session_ended_today($con, int $sessionId, string $sessionDate): bool {
        ensure_class_session_notification_log_table($con);
        $stmt = @mysqli_prepare($con, "SELECT id FROM class_session_notification_log WHERE session_id = ? AND notify_type = 'ended' AND session_date = ? LIMIT 1");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "is", $sessionId, $sessionDate);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        return $res ? (bool)mysqli_fetch_assoc($res) : false;
    }
}

if (!function_exists('get_class_session_details')) {
    function get_class_session_details($con, int $sessionId, ?string $overrideDate = null) {
        $stmt = mysqli_prepare($con, "SELECT id, teacher_id, subject_id, course_id, code, day_of_week, start_time, end_time, room, is_online, meeting_link, grade, academic_year_id, is_active, created_at FROM class_sessions WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $sessionId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['teacher_id'] = (int)$row['teacher_id'];
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
        $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
        $row['academic_year_id'] = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
        $row['is_active'] = (int)$row['is_active'];
        $row['is_online'] = (int)$row['is_online'];
        $row['scope'] = $row['course_id'] ? 'course' : 'subject';
        $row['academic_year'] = $row['academic_year_id'] ? get_academic_year_details($con, $row['academic_year_id']) : null;

        $targetDate = $overrideDate;
        if (empty($targetDate)) {
            $inputDate = trim($_GET['date'] ?? $_GET['session_date'] ?? '');
            if (!empty($inputDate) && strtotime($inputDate) !== false) {
                $targetDate = date('Y-m-d', strtotime($inputDate));
            }
        }
        $sessionDate = $targetDate ?: get_session_calculated_date($row['day_of_week']);
        $row['date'] = $sessionDate;
        $row['session_date'] = $sessionDate;

        $row['subject'] = $row['subject_id'] ? get_subject_details($con, $row['subject_id']) : null;
        $row['teacher'] = $row['teacher_id'] ? get_teacher_details($con, $row['teacher_id']) : null;
        $row['course'] = $row['course_id'] ? get_course_details($con, $row['course_id']) : null;
        $now = get_request_now();
        $todayDate = $now->format('Y-m-d');
        $nowTime = $now->format('H:i:s');

        $isToday = ($sessionDate === $todayDate);
        $isStartedToday = is_session_started_today($con, $row['id'], $todayDate);
        $isEndedToday = is_session_ended_today($con, $row['id'], $todayDate);
        // A session the teacher explicitly started is joinable even if the clock has drifted
        // outside the scheduled start/end window (e.g. started a few minutes early) — the manual
        // start/end action is the source of truth once it's been used, not the fixed schedule.
        $isAvailableNow = $isToday && (
            class_session_is_available_now($row) ||
            ($isStartedToday && !$isEndedToday)
        );

        $status = 'not_started';
        $isStarted = 0;
        $isEnded = 0;

        if ($isEndedToday) {
            $status = 'ended';
            $isStarted = 1;
            $isEnded = 1;
        } elseif ($isStartedToday) {
            $status = 'started';
            $isStarted = 1;
            $isEnded = 0;
        } elseif ($sessionDate < $todayDate) {
            $status = 'ended';
            $isStarted = 1;
            $isEnded = 1;
        } elseif ($sessionDate > $todayDate) {
            $status = 'not_started';
            $isStarted = 0;
            $isEnded = 0;
        } else {
            if ($nowTime > $row['end_time']) {
                $status = 'ended';
                $isStarted = 1;
                $isEnded = 1;
            } elseif ($nowTime >= $row['start_time'] && $nowTime <= $row['end_time']) {
                $status = 'started';
                $isStarted = 1;
                $isEnded = 0;
            } else {
                $status = 'not_started';
                $isStarted = 0;
                $isEnded = 0;
            }
        }

        $row['is_available_now'] = $isAvailableNow ? 1 : 0;
        $row['status'] = $status;
        $row['is_started'] = $isStarted;
        $row['is_ended'] = $isEnded;

        if (!is_admin_or_teacher_request()) {
            if ($status !== 'started') {
                $row['meeting_link'] = null;
            }
        }

        $studentsCountSql = $row['course_id']
            ? "SELECT COUNT(DISTINCT ss.student_id) AS total FROM student_subscription_courses ssc JOIN student_subscriptions ss ON ssc.student_subscription_id = ss.id WHERE ssc.course_id = {$row['course_id']} AND ss.is_active = 1"
            : "SELECT COUNT(DISTINCT ss.student_id) AS total FROM student_subscription_subjects sss JOIN student_subscriptions ss ON sss.student_subscription_id = ss.id WHERE sss.subject_id = {$row['subject_id']} AND ss.is_active = 1";
        $row['students_count'] = (int)(mysqli_fetch_assoc(mysqli_query($con, $studentsCountSql))['total'] ?? 0);

        $row['files'] = get_session_files_list($con, $row['id']);

        return format_response_images($row);
    }
}

if (!function_exists('validate_session_linkage')) {
    function validate_session_linkage(?int $subjectId, ?int $courseId): ?array {
        if (empty($subjectId) && empty($courseId)) {
            return ['status' => 'error', 'message' => 'Either subject_id or course_id is required'];
        }
        return null;
    }
}

if (!function_exists('teacherListSessions')) {
    function teacherListSessions($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $inputDate = trim($_GET['date'] ?? $_GET['session_date'] ?? '');
        $requestedDay = trim($_GET['day'] ?? $_GET['day_of_week'] ?? '');
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 20;
        $offset = ($page - 1) * $limit;

        $day = null;
        $overrideDate = null;
        if (!empty($inputDate) && strtotime($inputDate) !== false) {
            $overrideDate = date('Y-m-d', strtotime($inputDate));
            $day = date('l', strtotime($inputDate));
        } elseif (!empty($requestedDay)) {
            $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            if (in_array($requestedDay, $allowedDays, true)) {
                $day = $requestedDay;
            }
        }

        $courseId = (int)($_GET['course_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? 0);

        $where = ["teacher_id = ?"];
        $params = [$teacherId];
        $types = "i";

        if ($day !== null) {
            $where[] = "day_of_week = ?";
            $params[] = $day;
            $types .= "s";
        }
        if ($courseId > 0) {
            $where[] = "course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }
        if ($subjectId > 0) {
            $where[] = "subject_id = ?";
            $params[] = $subjectId;
            $types .= "i";
        }

        $whereSql = implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) AS total FROM class_sessions WHERE " . $whereSql;
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT id FROM class_sessions WHERE " . $whereSql . " ORDER BY FIELD(day_of_week,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time ASC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        $bindTypes = $types . "ii";
        $bindParams = array_merge($params, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_class_session_details($con, (int)$row['id'], $overrideDate);
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('teacherCreateSession')) {
    function teacherCreateSession($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();

        $subjectId = isset($body['subject_id']) && $body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        $courseId = isset($body['course_id']) && $body['course_id'] > 0 ? (int)$body['course_id'] : null;
        $academicYearId = isset($body['academic_year_id']) && $body['academic_year_id'] > 0 ? (int)$body['academic_year_id'] : null;

        $err = validate_session_linkage($subjectId, $courseId);
        if ($err) return $err;

        if ($courseId && !teacher_can_use_course_for_session($con, $teacherId, $courseId)) {
            return ['status' => 'error', 'message' => 'You are not assigned to this course and it already has another assigned teacher'];
        }
        if (!$courseId && $subjectId && !is_subject_assigned_to_teacher($con, $teacherId, $subjectId, $academicYearId)) {
            return ['status' => 'error', 'message' => 'You can only schedule sessions for subjects and grades assigned to you'];
        }

        $code = trim($body['code'] ?? '');
        $day = trim($body['day_of_week'] ?? $body['day'] ?? '');
        $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $days = isset($body['days']) && is_array($body['days']) ? array_values(array_unique(array_map('trim', $body['days']))) : [$day];
        $days = array_filter($days, fn($d) => in_array($d, $allowedDays, true));
        if (empty($days)) {
            return ['status' => 'error', 'message' => 'day_of_week must be one of: ' . implode(', ', $allowedDays)];
        }
        $startTime = !empty($body['start_time']) ? date('H:i:s', strtotime($body['start_time'])) : null;
        $endTime = !empty($body['end_time']) ? date('H:i:s', strtotime($body['end_time'])) : null;
        $room = trim($body['room'] ?? '');
        $isOnline = isset($body['is_online']) ? (int)$body['is_online'] : 0;
        $meetingLink = $isOnline ? trim($body['meeting_link'] ?? '') : null;
        $grade = trim($body['grade'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($startTime) || empty($endTime)) {
            return ['status' => 'error', 'message' => 'start_time and end_time are required'];
        }
        if ($isOnline && empty($meetingLink)) {
            return ['status' => 'error', 'message' => 'meeting_link is required when is_online is true'];
        }

        $created = [];
        foreach ($days as $day) {
            $stmt = mysqli_prepare($con, "INSERT INTO class_sessions (teacher_id, subject_id, course_id, code, day_of_week, start_time, end_time, room, is_online, meeting_link, grade, academic_year_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiisssssissii", $teacherId, $subjectId, $courseId, $code, $day, $startTime, $endTime, $room, $isOnline, $meetingLink, $grade, $academicYearId, $isActive);
            if (!mysqli_stmt_execute($stmt)) {
                return ['status' => 'error', 'message' => 'Failed to create session: ' . mysqli_error($con)];
            }

            $newSessionId = mysqli_insert_id($con);

            if ($day === get_request_now()->format('l')) {
                $today = get_request_now()->format('Y-m-d');
                if (mark_session_notification_once($con, $newSessionId, 'created', $today)) {
                    $studentIds = get_active_student_ids_for_session($con, $courseId, $subjectId, $teacherId);
                    if (!empty($studentIds)) {
                        send_notification_to_users(
                            $con,
                            'student',
                            $studentIds,
                            'حصة جديدة اليوم',
                            "تمت إضافة حصة اليوم الساعة {$startTime}" . (!empty($grade) ? " - {$grade}" : ''),
                            ['type' => 'session_created', 'session_id' => $newSessionId],
                            'teacher',
                            $teacherId
                        );
                    }
                }
            }

            $created[] = get_class_session_details($con, $newSessionId);
        }

        return [
            'status' => 'success',
            'message' => 'Session created successfully',
            'data' => count($created) === 1 ? $created[0] : $created
        ];
    }
}

if (!function_exists('teacherStartSession')) {
    function teacherStartSession($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($_GET['id'] ?? $_GET['session_id'] ?? $body['id'] ?? $body['session_id'] ?? 0);
        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, course_id, subject_id, start_time FROM class_sessions WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $sessionId, $teacherId);
        mysqli_stmt_execute($stmt);
        $session = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$session) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }

        $now = get_request_now();
        $minutesUntilStart = (int)floor((strtotime($session['start_time']) - strtotime($now->format('H:i:s'))) / 60);
        if ($minutesUntilStart > 5) {
            return ['status' => 'error', 'message' => trans('session_start_too_early')];
        }

        $today = $now->format('Y-m-d');
        $alreadyNotified = !mark_session_notification_once($con, $sessionId, 'started', $today);

        if (!$alreadyNotified) {
            $courseId = $session['course_id'] ? (int)$session['course_id'] : null;
            $subjectId = $session['subject_id'] ? (int)$session['subject_id'] : null;
            $studentIds = get_active_student_ids_for_session($con, $courseId, $subjectId, $teacherId);
            if (!empty($studentIds)) {
                send_notification_to_users(
                    $con,
                    'student',
                    $studentIds,
                    'الحصة بدأت الآن',
                    'ادخل الآن للانضمام إلى الحصة',
                    ['type' => 'session_started', 'session_id' => $sessionId],
                    'teacher',
                    $teacherId
                );
            }

            notify_admins_of_session_started($con, $teacherId, $courseId, $subjectId);

            // Section 10: this is also the teacher's own check-in — record attendance for this
            // occurrence date exactly once (guarded by the same idempotent notification-log flag).
            record_teacher_session_checkin($con, $sessionId, $teacherId, $today, $session['start_time'], $courseId, $subjectId);
        }

        return [
            'status' => 'success',
            'message' => 'Session Started',
            'data' => get_class_session_details($con, $sessionId)
        ];
    }
}

if (!function_exists('teacherEndSession')) {
    function teacherEndSession($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($_GET['id'] ?? $_GET['session_id'] ?? $body['id'] ?? $body['session_id'] ?? 0);
        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, course_id, subject_id, start_time, end_time FROM class_sessions WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $sessionId, $teacherId);
        mysqli_stmt_execute($stmt);
        $session = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$session) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }

        $today = get_request_now()->format('Y-m-d');
        $wasFirstEnd = mark_session_notification_once($con, $sessionId, 'ended', $today);

        if ($wasFirstEnd) {
            // Section 10: the teacher's own check-out + salary calculation for this occurrence.
            record_teacher_session_checkout($con, $sessionId, $teacherId, $today, $session['start_time'], $session['end_time']);
        }

        return [
            'status' => 'success',
            'message' => 'Session Ended',
            'data' => get_class_session_details($con, $sessionId)
        ];
    }
}

if (!function_exists('teacherUpdateSession')) {
    function teacherUpdateSession($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('session_id_required')];

        $checkStmt = mysqli_prepare($con, "SELECT id, day_of_week, start_time, end_time FROM class_sessions WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $id, $teacherId);
        mysqli_stmt_execute($checkStmt);
        $currentSession = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$currentSession) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }

        $requestedDay = trim($body['day_of_week'] ?? $body['day'] ?? '');
        $requestedStart = !empty($body['start_time']) ? date('H:i:s', strtotime($body['start_time'])) : null;
        $requestedEnd = !empty($body['end_time']) ? date('H:i:s', strtotime($body['end_time'])) : null;
        $dayChanged = (isset($body['day_of_week']) || isset($body['day'])) && $requestedDay !== $currentSession['day_of_week'];
        $startChanged = isset($body['start_time']) && $requestedStart !== $currentSession['start_time'];
        $endChanged = isset($body['end_time']) && $requestedEnd !== $currentSession['end_time'];
        if ($dayChanged || $startChanged || $endChanged) {
            return ['status' => 'error', 'message' => 'Changing the session day/time directly is not allowed. Use the reschedule-request flow (POST /teacher/sessions/reschedule-request) instead, which requires admin approval.'];
        }

        $updateFields = [];
        $params = [];
        $types = "";

        $bodyAcademicYearId = isset($body['academic_year_id']) && $body['academic_year_id'] > 0 ? (int)$body['academic_year_id'] : null;

        if (isset($body['course_id']) && $body['course_id'] && !teacher_can_use_course_for_session($con, $teacherId, (int)$body['course_id'])) {
            return ['status' => 'error', 'message' => 'You are not assigned to this course and it already has another assigned teacher'];
        }
        if (isset($body['subject_id']) && $body['subject_id'] && empty($body['course_id']) && !is_subject_assigned_to_teacher($con, $teacherId, (int)$body['subject_id'], $bodyAcademicYearId)) {
            return ['status' => 'error', 'message' => 'You can only schedule sessions for subjects and grades assigned to you'];
        }

        if (isset($body['subject_id'])) { $updateFields[] = "subject_id = ?"; $params[] = $body['subject_id'] ? (int)$body['subject_id'] : null; $types .= "i"; }
        if (isset($body['course_id'])) { $updateFields[] = "course_id = ?"; $params[] = $body['course_id'] ? (int)$body['course_id'] : null; $types .= "i"; }
        if (isset($body['code'])) { $updateFields[] = "code = ?"; $params[] = trim($body['code']); $types .= "s"; }
        if (isset($body['room'])) { $updateFields[] = "room = ?"; $params[] = trim($body['room']); $types .= "s"; }
        if (isset($body['is_online'])) { $updateFields[] = "is_online = ?"; $params[] = (int)$body['is_online']; $types .= "i"; }
        if (isset($body['meeting_link'])) { $updateFields[] = "meeting_link = ?"; $params[] = trim($body['meeting_link']); $types .= "s"; }
        if (isset($body['grade'])) { $updateFields[] = "grade = ?"; $params[] = trim($body['grade']); $types .= "s"; }
        if (isset($body['academic_year_id'])) { $updateFields[] = "academic_year_id = ?"; $params[] = $bodyAcademicYearId; $types .= "i"; }
        if (isset($body['is_active'])) { $updateFields[] = "is_active = ?"; $params[] = (int)$body['is_active']; $types .= "i"; }

        if (!empty($updateFields)) {
            $params[] = $id;
            $types .= "i";
            $uStmt = mysqli_prepare($con, "UPDATE class_sessions SET " . implode(", ", $updateFields) . " WHERE id = ?");
            mysqli_stmt_bind_param($uStmt, $types, ...$params);
            mysqli_stmt_execute($uStmt);
        }

        return ['status' => 'success', 'message' => 'Session updated successfully', 'data' => get_class_session_details($con, $id)];
    }
}

if (!function_exists('teacherDeleteSession')) {
    function teacherDeleteSession($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('session_id_required')];

        $stmt = mysqli_prepare($con, "DELETE FROM class_sessions WHERE id = ? AND teacher_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $teacherId);
        if (mysqli_stmt_execute($stmt)) {
            return ['status' => 'success', 'message' => 'Session deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete session'];
    }
}

if (!function_exists('get_reschedule_request_details')) {
    function get_reschedule_request_details($con, int $reqId) {
        $stmt = mysqli_prepare($con, "SELECT srr.id, srr.session_id, srr.teacher_id, srr.requested_by_type, srr.requested_by_id, srr.new_day, srr.new_start_time, srr.new_end_time, srr.reason, srr.status, srr.admin_note, srr.reviewed_by_type, srr.reviewed_by_id, srr.created_at, srr.reviewed_at FROM session_reschedule_requests srr WHERE srr.id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $reqId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['session_id'] = (int)$row['session_id'];
        $row['teacher_id'] = (int)$row['teacher_id'];
        $row['requested_by_id'] = $row['requested_by_id'] ? (int)$row['requested_by_id'] : null;
        $row['reviewed_by_id'] = $row['reviewed_by_id'] ? (int)$row['reviewed_by_id'] : null;
        $row['session'] = get_class_session_details($con, $row['session_id']);

        $requester = null;
        if ($row['requested_by_type'] === 'student' && $row['requested_by_id']) {
            $sStmt = mysqli_prepare($con, "SELECT id, name, email FROM students WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($sStmt, "i", $row['requested_by_id']);
            mysqli_stmt_execute($sStmt);
            $requester = mysqli_fetch_assoc(mysqli_stmt_get_result($sStmt)) ?: null;
        } elseif ($row['requested_by_type'] === 'teacher' && $row['requested_by_id']) {
            $tStmt = mysqli_prepare($con, "SELECT id, name, email FROM teachers WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($tStmt, "i", $row['requested_by_id']);
            mysqli_stmt_execute($tStmt);
            $requester = mysqli_fetch_assoc(mysqli_stmt_get_result($tStmt)) ?: null;
        }
        $row['requester'] = $requester;

        return $row;
    }
}

if (!function_exists('teacherRequestReschedule')) {
    function teacherRequestReschedule($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($body['session_id'] ?? 0);
        $newDay = trim($body['new_day'] ?? $body['day'] ?? '');
        $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $reason = trim($body['reason'] ?? '');

        if ($sessionId <= 0 || !in_array($newDay, $allowedDays) || empty($reason)) {
            return ['status' => 'error', 'message' => 'session_id, a valid new_day, and reason are required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM class_sessions WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $sessionId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }

        $policy = get_hr_policy_settings($con);
        if (count_reschedule_requests_this_month($con, 'teacher', $teacherId) >= $policy['teacher_max_reschedule_per_month']) {
            return ['status' => 'error', 'message' => trans('max_reschedule_requests_reached')];
        }

        $newStart = !empty($body['new_start_time']) ? date('H:i:s', strtotime($body['new_start_time'])) : null;
        $newEnd = !empty($body['new_end_time']) ? date('H:i:s', strtotime($body['new_end_time'])) : null;

        $stmt = mysqli_prepare($con, "INSERT INTO session_reschedule_requests (session_id, teacher_id, requested_by_type, requested_by_id, new_day, new_start_time, new_end_time, reason) VALUES (?, ?, 'teacher', ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiissss", $sessionId, $teacherId, $teacherId, $newDay, $newStart, $newEnd, $reason);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to send request: ' . mysqli_error($con)];
        }

        $requestId = (int)mysqli_insert_id($con);
        $teacherName = attendance_notify_get_teacher_name($con, $teacherId);
        notify_admins($con, 'طلب تأجيل حصة جديد', "المعلم {$teacherName} أرسل طلب تأجيل لإحدى حصصه.", ['type' => 'reschedule_request_created', 'request_id' => $requestId]);

        return ['status' => 'success', 'message' => 'Reschedule request sent to admin', 'data' => get_reschedule_request_details($con, $requestId)];
    }
}

if (!function_exists('teacherListRescheduleRequests')) {
    function teacherListRescheduleRequests($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $stmt = mysqli_prepare($con, "SELECT id FROM session_reschedule_requests WHERE teacher_id = ? AND requested_by_type = 'teacher' ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_reschedule_request_details($con, (int)$row['id']);
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('studentRequestReschedule')) {
    function studentRequestReschedule($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($body['session_id'] ?? 0);
        $newDay = trim($body['new_day'] ?? $body['day'] ?? '');
        $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $reason = trim($body['reason'] ?? '');

        if ($sessionId <= 0 || !in_array($newDay, $allowedDays) || empty($reason)) {
            return ['status' => 'error', 'message' => 'session_id, a valid new_day, and reason are required'];
        }

        $session = get_class_session_details($con, $sessionId);
        if (!$session) {
            return ['status' => 'error', 'message' => trans('session_not_found')];
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $sessionCourseId = (int)($session['course_id'] ?? 0);
        $sessionSubjectId = (int)($session['subject_id'] ?? 0);
        $sessionTeacherId = (int)($session['teacher_id'] ?? 0);
        $hasAccess =
            ($sessionCourseId > 0 && in_array($sessionCourseId, $accessible['course_ids'] ?? [])) ||
            ($sessionSubjectId > 0 && in_array($sessionSubjectId, $accessible['subject_ids'] ?? [])) ||
            ($sessionTeacherId > 0 && in_array($sessionTeacherId, $accessible['teacher_ids'] ?? []));
        if (!$hasAccess) {
            return ['status' => 'error', 'message' => trans('not_subscribed_to_session')];
        }

        $policy = get_hr_policy_settings($con);
        if (count_reschedule_requests_this_month($con, 'student', $studentId) >= $policy['student_max_reschedule_per_month']) {
            return ['status' => 'error', 'message' => trans('max_reschedule_requests_reached')];
        }

        $newStart = !empty($body['new_start_time']) ? date('H:i:s', strtotime($body['new_start_time'])) : null;
        $newEnd = !empty($body['new_end_time']) ? date('H:i:s', strtotime($body['new_end_time'])) : null;

        $stmt = mysqli_prepare($con, "INSERT INTO session_reschedule_requests (session_id, teacher_id, requested_by_type, requested_by_id, new_day, new_start_time, new_end_time, reason) VALUES (?, ?, 'student', ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiissss", $sessionId, $session['teacher_id'], $studentId, $newDay, $newStart, $newEnd, $reason);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to send request: ' . mysqli_error($con)];
        }

        $requestId = (int)mysqli_insert_id($con);
        $studentName = admin_notify_get_student_name($con, $studentId);
        send_notification_to_users($con, 'teacher', [(int)$session['teacher_id']], 'طلب تأجيل حصة من طالب', "الطالب {$studentName} أرسل طلب تأجيل لحصة يحضرها.", ['type' => 'reschedule_request_created', 'request_id' => $requestId], 'system', null, ['push']);

        return ['status' => 'success', 'message' => 'Reschedule request sent to the teacher', 'data' => get_reschedule_request_details($con, $requestId)];
    }
}

if (!function_exists('studentListMyRescheduleRequests')) {
    function studentListMyRescheduleRequests($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($studentId <= 0) {
            $studentId = (int)($_GET['student_id'] ?? 0);
        }
        if ($studentId <= 0) {
            return ['status' => 'success', 'data' => []];
        }

        $stmt = mysqli_prepare($con, "SELECT id FROM session_reschedule_requests WHERE requested_by_type = 'student' AND requested_by_id = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_reschedule_request_details($con, (int)$row['id']);
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('teacherListIncomingRescheduleRequests')) {
    function teacherListIncomingRescheduleRequests($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $stmt = mysqli_prepare($con, "SELECT id FROM session_reschedule_requests WHERE teacher_id = ? AND requested_by_type = 'student' ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_reschedule_request_details($con, (int)$row['id']);
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('teacherReviewRescheduleRequest')) {
    function teacherReviewRescheduleRequest($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $decision = strtolower(trim($body['decision'] ?? $body['status'] ?? ''));
        $note = trim($body['note'] ?? $body['teacher_note'] ?? '');

        if ($id <= 0 || !in_array($decision, ['approved', 'rejected'])) {
            return ['status' => 'error', 'message' => 'id and a valid decision (approved/rejected) are required'];
        }

        $reqStmt = mysqli_prepare($con, "SELECT session_id, new_day, new_start_time, new_end_time FROM session_reschedule_requests WHERE id = ? AND teacher_id = ? AND requested_by_type = 'student' AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($reqStmt, "ii", $id, $teacherId);
        mysqli_stmt_execute($reqStmt);
        $reqRow = mysqli_fetch_assoc(mysqli_stmt_get_result($reqStmt));
        if (!$reqRow) {
            return ['status' => 'error', 'message' => 'Request not found, already reviewed, or not addressed to you'];
        }

        $uStmt = mysqli_prepare($con, "UPDATE session_reschedule_requests SET status = ?, admin_note = ?, reviewed_by_type = 'teacher', reviewed_by_id = ?, reviewed_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, "ssii", $decision, $note, $teacherId, $id);
        mysqli_stmt_execute($uStmt);

        if ($decision === 'approved') {
            $sStmt = mysqli_prepare($con, "UPDATE class_sessions SET day_of_week = ?, start_time = COALESCE(?, start_time), end_time = COALESCE(?, end_time) WHERE id = ?");
            mysqli_stmt_bind_param($sStmt, "sssi", $reqRow['new_day'], $reqRow['new_start_time'], $reqRow['new_end_time'], $reqRow['session_id']);
            mysqli_stmt_execute($sStmt);
        }

        $requesterId = (int)($reqRow['requested_by_id'] ?? 0);
        if ($requesterId > 0) {
            $decisionLabel = $decision === 'approved' ? 'تمت الموافقة عليه' : 'تم رفضه';
            $requesterName = admin_notify_get_student_name($con, $requesterId);
            send_notification_to_users($con, 'student', [$requesterId], 'الرد على طلب تأجيل الحصة', "طلب تأجيل الحصة الذي أرسله {$requesterName} {$decisionLabel}.", ['type' => 'reschedule_request_reviewed', 'request_id' => $id, 'decision' => $decision], 'teacher', $teacherId, ['push']);
        }

        return ['status' => 'success', 'message' => 'Reschedule request ' . $decision, 'data' => get_reschedule_request_details($con, $id)];
    }
}

if (!function_exists('adminListRescheduleRequests')) {
    function adminListRescheduleRequests($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = strtolower(trim($_GET['status'] ?? ''));

        $where = "WHERE requested_by_type = 'teacher'";
        $params = [];
        $types = "";
        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $where .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($params)) {
            $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM session_reschedule_requests {$where}");
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);
        } else {
            $totalRecords = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM session_reschedule_requests {$where}"))['total'] ?? 0);
        }

        $sql = "SELECT id FROM session_reschedule_requests {$where} ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        $bindTypes = $types . "ii";
        $bindParams = array_merge($params, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_reschedule_request_details($con, (int)$row['id']);
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminReviewRescheduleRequest')) {
    function adminReviewRescheduleRequest($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $decision = strtolower(trim($body['decision'] ?? $body['status'] ?? ''));
        $adminNote = trim($body['admin_note'] ?? '');

        if ($id <= 0 || !in_array($decision, ['approved', 'rejected'])) {
            return ['status' => 'error', 'message' => 'id and a valid decision (approved/rejected) are required'];
        }

        $reqStmt = mysqli_prepare($con, "SELECT session_id, requested_by_id, new_day, new_start_time, new_end_time FROM session_reschedule_requests WHERE id = ? AND requested_by_type = 'teacher' AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($reqStmt, "i", $id);
        mysqli_stmt_execute($reqStmt);
        $reqRow = mysqli_fetch_assoc(mysqli_stmt_get_result($reqStmt));
        if (!$reqRow) {
            return ['status' => 'error', 'message' => 'Request not found, already reviewed, or not a teacher-initiated request'];
        }

        if ($decision === 'approved') {
            $sessionId = (int)$reqRow['session_id'];
            $keepOriginalSchedule = !empty($body['keep_original_schedule']);
            $newTeacherId = isset($body['new_teacher_id']) ? (int)$body['new_teacher_id'] : 0;

            if ($keepOriginalSchedule && $newTeacherId <= 0) {
                return ['status' => 'error', 'message' => 'new_teacher_id is required when keeping the original schedule'];
            }

            if ($newTeacherId > 0) {
                $sessStmt = mysqli_prepare($con, "SELECT subject_id, course_id, academic_year_id FROM class_sessions WHERE id = ? LIMIT 1");
                mysqli_stmt_bind_param($sessStmt, "i", $sessionId);
                mysqli_stmt_execute($sessStmt);
                $sessRow = mysqli_fetch_assoc(mysqli_stmt_get_result($sessStmt));
                if (!$sessRow) {
                    return ['status' => 'error', 'message' => trans('session_not_found')];
                }

                $courseId = $sessRow['course_id'] ? (int)$sessRow['course_id'] : null;
                $subjectId = $sessRow['subject_id'] ? (int)$sessRow['subject_id'] : null;
                $sessionAcademicYearId = $sessRow['academic_year_id'] ? (int)$sessRow['academic_year_id'] : null;

                if ($courseId && !teacher_can_use_course_for_session($con, $newTeacherId, $courseId)) {
                    return ['status' => 'error', 'message' => 'This teacher is not assigned to the selected course and it already has another assigned teacher'];
                }
                if (!$courseId && $subjectId && !is_subject_assigned_to_teacher($con, $newTeacherId, $subjectId, $sessionAcademicYearId)) {
                    return ['status' => 'error', 'message' => 'This teacher is not assigned to the selected subject and grade'];
                }
            }

            if ($keepOriginalSchedule) {
                $sStmt = mysqli_prepare($con, "UPDATE class_sessions SET teacher_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($sStmt, "ii", $newTeacherId, $sessionId);
                mysqli_stmt_execute($sStmt);
            } else {
                $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                $overrideDay = trim($body['new_day'] ?? '');
                $finalDay = in_array($overrideDay, $allowedDays, true) ? $overrideDay : $reqRow['new_day'];
                $finalStart = !empty($body['new_start_time']) ? date('H:i:s', strtotime($body['new_start_time'])) : $reqRow['new_start_time'];
                $finalEnd = !empty($body['new_end_time']) ? date('H:i:s', strtotime($body['new_end_time'])) : $reqRow['new_end_time'];

                $sStmt = mysqli_prepare($con, "UPDATE class_sessions SET teacher_id = COALESCE(NULLIF(?, 0), teacher_id), day_of_week = ?, start_time = COALESCE(?, start_time), end_time = COALESCE(?, end_time) WHERE id = ?");
                mysqli_stmt_bind_param($sStmt, "isssi", $newTeacherId, $finalDay, $finalStart, $finalEnd, $sessionId);
                mysqli_stmt_execute($sStmt);
            }
        }

        $uStmt = mysqli_prepare($con, "UPDATE session_reschedule_requests SET status = ?, admin_note = ?, reviewed_by_type = 'admin', reviewed_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, "ssi", $decision, $adminNote, $id);
        mysqli_stmt_execute($uStmt);

        $requesterId = (int)($reqRow['requested_by_id'] ?? 0);
        if ($requesterId > 0) {
            $decisionLabel = $decision === 'approved' ? 'تمت الموافقة عليه' : 'تم رفضه';
            $requesterName = attendance_notify_get_teacher_name($con, $requesterId);
            send_notification_to_users($con, 'teacher', [$requesterId], 'الرد على طلب تأجيل الحصة', "طلب تأجيل الحصة الذي أرسله {$requesterName} {$decisionLabel}.", ['type' => 'reschedule_request_reviewed', 'request_id' => $id, 'decision' => $decision], 'system', null, ['push']);
        }

        return ['status' => 'success', 'message' => 'Reschedule request ' . $decision, 'data' => get_reschedule_request_details($con, $id)];
    }
}
