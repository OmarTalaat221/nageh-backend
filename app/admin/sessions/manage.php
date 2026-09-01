<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../teacher/sessions/manage.php';

/**
 * class_sessions only has a day_of_week (no calendar date of its own), so a "date range" filter
 * means: which weekday names fall inside [from, to]. Capped at 7 unique days - once every weekday
 * name has been seen the range can't add more, so it stops early even for long ranges.
 */
if (!function_exists('get_day_names_in_date_range')) {
    function get_day_names_in_date_range(string $from, string $to): array {
        $start = strtotime($from);
        $end = strtotime($to);
        if ($start === false || $end === false || $end < $start) return [];

        $days = [];
        $seen = [];
        $cursor = $start;
        while ($cursor <= $end && count($days) < 7) {
            $name = date('l', $cursor);
            if (!isset($seen[$name])) {
                $seen[$name] = true;
                $days[] = $name;
            }
            $cursor = strtotime('+1 day', $cursor);
        }
        return $days;
    }
}

if (!function_exists('adminListSessions')) {
    function adminListSessions($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        $courseId = (int)($_GET['course_id'] ?? 0);
        $requestedDay = trim($_GET['day'] ?? $_GET['day_of_week'] ?? '');
        $inputDate = trim($_GET['date'] ?? $_GET['session_date'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? $_GET['from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? $_GET['to'] ?? '');
        $isOnline = isset($_GET['is_online']) && $_GET['is_online'] !== '' ? (int)$_GET['is_online'] : null;
        $isActive = isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (int)$_GET['is_active'] : null;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $day = null;
        $days = [];
        $overrideDate = null;
        if (!empty($dateFrom) && !empty($dateTo)) {
            $days = get_day_names_in_date_range($dateFrom, $dateTo);
        } elseif (!empty($inputDate) && strtotime($inputDate) !== false) {
            $overrideDate = date('Y-m-d', strtotime($inputDate));
            $day = date('l', strtotime($inputDate));
        } elseif (!empty($requestedDay)) {
            $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            if (in_array($requestedDay, $allowedDays, true)) {
                $day = $requestedDay;
            }
        }

        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($teacherId > 0) {
            $where[] = "cs.teacher_id = ?";
            $params[] = $teacherId;
            $types .= "i";
        }
        if ($subjectId > 0) {
            $where[] = "cs.subject_id = ?";
            $params[] = $subjectId;
            $types .= "i";
        }
        if ($courseId > 0) {
            $where[] = "cs.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }
        if (!empty($days)) {
            $inPlaceholders = implode(',', array_fill(0, count($days), '?'));
            $where[] = "cs.day_of_week IN ({$inPlaceholders})";
            foreach ($days as $d) {
                $params[] = $d;
                $types .= "s";
            }
        } elseif ($day !== null) {
            $where[] = "cs.day_of_week = ?";
            $params[] = $day;
            $types .= "s";
        }
        if ($isOnline !== null) {
            $where[] = "cs.is_online = ?";
            $params[] = $isOnline;
            $types .= "i";
        }
        if ($isActive !== null) {
            $where[] = "cs.is_active = ?";
            $params[] = $isActive;
            $types .= "i";
        }
        if (!empty($search)) {
            $pattern = '%' . $search . '%';
            $where[] = "(t.name LIKE ? OR cs.grade LIKE ? OR cs.room LIKE ? OR cs.code LIKE ?)";
            $params[] = $pattern;
            $params[] = $pattern;
            $params[] = $pattern;
            $params[] = $pattern;
            $types .= "ssss";
        }

        $whereClause = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) AS total FROM class_sessions cs LEFT JOIN teachers t ON cs.teacher_id = t.id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT cs.id FROM class_sessions cs LEFT JOIN teachers t ON cs.teacher_id = t.id {$whereClause} ORDER BY FIELD(cs.day_of_week,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), cs.start_time ASC LIMIT ? OFFSET ?";
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

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total_sessions, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_sessions, SUM(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) AS online_sessions FROM class_sessions");
        $statRow = mysqli_fetch_assoc($statRes) ?: [];

        return build_admin_paginated_response($items, $page, $limit, $totalRecords, [
            'total_sessions' => (int)($statRow['total_sessions'] ?? 0),
            'active_sessions' => (int)($statRow['active_sessions'] ?? 0),
            'online_sessions' => (int)($statRow['online_sessions'] ?? 0)
        ]);
    }
}

if (!function_exists('adminCreateSession')) {
    function adminCreateSession($con) {
        $body = read_json_body();

        $teacherId = (int)($body['teacher_id'] ?? 0);
        $subjectId = isset($body['subject_id']) && $body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        $courseId = isset($body['course_id']) && $body['course_id'] > 0 ? (int)$body['course_id'] : null;
        $dayOfWeek = trim($body['day_of_week'] ?? '');
        $startTime = !empty($body['start_time']) ? date('H:i:s', strtotime($body['start_time'])) : null;
        $endTime = !empty($body['end_time']) ? date('H:i:s', strtotime($body['end_time'])) : null;
        $room = trim($body['room'] ?? '');
        $meetingLink = trim($body['meeting_link'] ?? '');
        $isOnline = isset($body['is_online']) ? (int)$body['is_online'] : 0;
        $grade = trim($body['grade'] ?? '');
        $academicYearId = isset($body['academic_year_id']) && $body['academic_year_id'] > 0 ? (int)$body['academic_year_id'] : null;

        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'teacher_id is required'];
        }

        $err = validate_session_linkage($subjectId, $courseId);
        if ($err) return $err;

        if ($courseId && !teacher_can_use_course_for_session($con, $teacherId, $courseId)) {
            return ['status' => 'error', 'message' => 'This teacher is not assigned to the selected course and it already has another assigned teacher'];
        }
        if (!$courseId && $subjectId && !is_subject_assigned_to_teacher($con, $teacherId, $subjectId, $academicYearId)) {
            return ['status' => 'error', 'message' => 'This teacher is not assigned to the selected subject and grade'];
        }

        $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        if (empty($dayOfWeek) || !in_array($dayOfWeek, $allowedDays, true)) {
            return ['status' => 'error', 'message' => 'Valid day_of_week is required'];
        }
        if (empty($startTime) || empty($endTime)) {
            return ['status' => 'error', 'message' => 'start_time and end_time are required'];
        }

        $code = uniqid('SESS-');

        $stmt = mysqli_prepare($con, "INSERT INTO class_sessions (teacher_id, subject_id, course_id, code, day_of_week, start_time, end_time, room, is_online, meeting_link, grade, academic_year_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "iiisssssissi", $teacherId, $subjectId, $courseId, $code, $dayOfWeek, $startTime, $endTime, $room, $isOnline, $meetingLink, $grade, $academicYearId);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create class session: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => 'Class session created successfully',
            'data' => get_class_session_details($con, mysqli_insert_id($con))
        ];
    }
}

if (!function_exists('adminUpdateSession')) {
    function adminUpdateSession($con) {
        $body = read_json_body();
        $sessionId = (int)($_GET['id'] ?? $body['id'] ?? $body['session_id'] ?? 0);

        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, teacher_id, subject_id, course_id, academic_year_id FROM class_sessions WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $sessionId);
        mysqli_stmt_execute($checkStmt);
        $currentSession = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$currentSession) {
            return ['status' => 'error', 'message' => 'Class session not found'];
        }

        $scopeFieldsTouched = array_key_exists('teacher_id', $body) || array_key_exists('subject_id', $body) || array_key_exists('course_id', $body) || array_key_exists('academic_year_id', $body);
        if ($scopeFieldsTouched) {
            $finalTeacherId = (array_key_exists('teacher_id', $body) && (int)$body['teacher_id'] > 0)
                ? (int)$body['teacher_id']
                : (int)$currentSession['teacher_id'];
            $finalSubjectId = array_key_exists('subject_id', $body)
                ? ($body['subject_id'] ? (int)$body['subject_id'] : null)
                : ($currentSession['subject_id'] !== null ? (int)$currentSession['subject_id'] : null);
            $finalCourseId = array_key_exists('course_id', $body)
                ? ($body['course_id'] ? (int)$body['course_id'] : null)
                : ($currentSession['course_id'] !== null ? (int)$currentSession['course_id'] : null);
            $finalAcademicYearId = array_key_exists('academic_year_id', $body)
                ? ($body['academic_year_id'] ? (int)$body['academic_year_id'] : null)
                : ($currentSession['academic_year_id'] !== null ? (int)$currentSession['academic_year_id'] : null);

            $err = validate_session_linkage($finalSubjectId, $finalCourseId);
            if ($err) return $err;

            if ($finalCourseId && !teacher_can_use_course_for_session($con, $finalTeacherId, $finalCourseId)) {
                return ['status' => 'error', 'message' => 'This teacher is not assigned to the selected course and it already has another assigned teacher'];
            }
            if (!$finalCourseId && $finalSubjectId && !is_subject_assigned_to_teacher($con, $finalTeacherId, $finalSubjectId, $finalAcademicYearId)) {
                return ['status' => 'error', 'message' => 'This teacher is not assigned to the selected subject and grade'];
            }
        }

        $updateFields = [];
        $params = [];
        $types = "";

        if (array_key_exists('teacher_id', $body) && (int)$body['teacher_id'] > 0) {
            $updateFields[] = "teacher_id = ?";
            $params[] = (int)$body['teacher_id'];
            $types .= "i";
        }
        if (array_key_exists('subject_id', $body)) {
            $updateFields[] = "subject_id = ?";
            $params[] = $body['subject_id'] ? (int)$body['subject_id'] : null;
            $types .= "i";
        }
        if (array_key_exists('course_id', $body)) {
            $updateFields[] = "course_id = ?";
            $params[] = $body['course_id'] ? (int)$body['course_id'] : null;
            $types .= "i";
        }
        if (array_key_exists('academic_year_id', $body)) {
            $updateFields[] = "academic_year_id = ?";
            $params[] = $body['academic_year_id'] ? (int)$body['academic_year_id'] : null;
            $types .= "i";
        }
        if (!empty($body['day_of_week'])) {
            $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            $day = trim($body['day_of_week']);
            if (in_array($day, $allowedDays, true)) {
                $updateFields[] = "day_of_week = ?";
                $params[] = $day;
                $types .= "s";
            }
        }
        if (!empty($body['start_time'])) {
            $updateFields[] = "start_time = ?";
            $params[] = date('H:i:s', strtotime($body['start_time']));
            $types .= "s";
        }
        if (!empty($body['end_time'])) {
            $updateFields[] = "end_time = ?";
            $params[] = date('H:i:s', strtotime($body['end_time']));
            $types .= "s";
        }
        if (array_key_exists('room', $body)) {
            $updateFields[] = "room = ?";
            $params[] = trim($body['room']);
            $types .= "s";
        }
        if (array_key_exists('meeting_link', $body)) {
            $updateFields[] = "meeting_link = ?";
            $params[] = trim($body['meeting_link']);
            $types .= "s";
        }
        if (array_key_exists('is_online', $body)) {
            $updateFields[] = "is_online = ?";
            $params[] = (int)$body['is_online'];
            $types .= "i";
        }
        if (array_key_exists('grade', $body)) {
            $updateFields[] = "grade = ?";
            $params[] = trim($body['grade']);
            $types .= "s";
        }
        if (array_key_exists('is_active', $body)) {
            $updateFields[] = "is_active = ?";
            $params[] = (int)$body['is_active'];
            $types .= "i";
        }

        if (!empty($updateFields)) {
            $sql = "UPDATE class_sessions SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $params[] = $sessionId;
            $types .= "i";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
        }

        return [
            'status' => 'success',
            'message' => 'Class session schedule updated successfully',
            'data' => get_class_session_details($con, $sessionId)
        ];
    }
}

if (!function_exists('adminDeleteSession')) {
    function adminDeleteSession($con) {
        $body = read_json_body();
        $sessionId = (int)($_GET['id'] ?? $body['id'] ?? $body['session_id'] ?? 0);
        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM class_sessions WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $sessionId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete class session'];
        }

        return ['status' => 'success', 'message' => 'Class session deleted successfully'];
    }
}
