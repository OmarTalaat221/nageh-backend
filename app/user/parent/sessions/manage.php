<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../teacher/sessions/manage.php';
require_once __DIR__ . '/../../../teacher/sessions/files.php';
require_once __DIR__ . '/../../student/subscriptions/manage.php';

if (!function_exists('parent_get_linked_student_ids')) {
    function parent_get_linked_student_ids($con, int $parentId): array {
        $stmt = mysqli_prepare($con, "SELECT student_id FROM parent_students WHERE parent_id = ? AND is_active = 1");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $parentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $ids = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ids[] = (int)$row['student_id'];
        }
        return $ids;
    }
}

/**
 * Resolves & authorizes which child (student_id) this request is about, given the parent's
 * linked children. Returns null (with an error already built by the caller) if unauthorized.
 */
if (!function_exists('parent_resolve_target_student_id')) {
    function parent_resolve_target_student_id($con, int $parentId, int $requestedStudentId): ?int {
        $linkedIds = parent_get_linked_student_ids($con, $parentId);
        if (empty($linkedIds)) return null;
        if ($requestedStudentId <= 0) {
            return count($linkedIds) === 1 ? $linkedIds[0] : null;
        }
        return in_array($requestedStudentId, $linkedIds, true) ? $requestedStudentId : null;
    }
}

if (!function_exists('parentGetChildSessions')) {
    function parentGetChildSessions($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $requestedStudentId = (int)($_GET['student_id'] ?? 0);
        $studentId = parent_resolve_target_student_id($con, $parentId, $requestedStudentId);
        if ($studentId === null) {
            return ['status' => 'error', 'message' => trans('student_not_found')];
        }

        $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $inputDate = trim($_GET['date'] ?? $_GET['session_date'] ?? '');
        $requestedDay = trim($_GET['day'] ?? $_GET['day_of_week'] ?? '');

        $day = null;
        $overrideDate = null;
        if (!empty($inputDate) && strtotime($inputDate) !== false) {
            $overrideDate = date('Y-m-d', strtotime($inputDate));
            $day = date('l', strtotime($inputDate));
        } elseif (!empty($requestedDay) && in_array($requestedDay, $allowedDays, true)) {
            $day = $requestedDay;
        }

        $subjectIds = get_student_subscribed_subject_ids($con, $studentId);
        $courseIds = get_student_subscribed_course_ids($con, $studentId);

        if (empty($subjectIds) && empty($courseIds)) {
            return ['status' => 'success', 'data' => ['student_id' => $studentId, 'day' => $day, 'date' => $overrideDate, 'sessions' => []]];
        }

        $whereParts = [];
        $params = [];
        $types = "";

        if ($day !== null) {
            $whereParts[] = "day_of_week = ?";
            $params[] = $day;
            $types .= "s";
        }

        $entityWhereParts = [];
        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $entityWhereParts[] = "subject_id IN ({$placeholders})";
            foreach ($subjectIds as $sId) { $params[] = $sId; $types .= "i"; }
        }
        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $entityWhereParts[] = "course_id IN ({$placeholders})";
            foreach ($courseIds as $cId) { $params[] = $cId; $types .= "i"; }
        }
        $whereParts[] = "(" . implode(" OR ", $entityWhereParts) . ")";

        $sql = "SELECT id FROM class_sessions WHERE is_active = 1 AND " . implode(" AND ", $whereParts) . " ORDER BY FIELD(day_of_week,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time ASC";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_class_session_details($con, (int)$row['id'], $overrideDate);
        }

        return ['status' => 'success', 'data' => ['student_id' => $studentId, 'day' => $day, 'date' => $overrideDate ?: date('Y-m-d'), 'sessions' => $items]];
    }
}

if (!function_exists('parentGetChildSessionFiles')) {
    function parentGetChildSessionFiles($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $requestedStudentId = (int)($_GET['student_id'] ?? 0);
        $studentId = parent_resolve_target_student_id($con, $parentId, $requestedStudentId);
        if ($studentId === null) {
            return ['status' => 'error', 'message' => trans('student_not_found')];
        }

        $sessionId = (int)($_GET['session_id'] ?? $_GET['id'] ?? 0);
        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $session = get_class_session_details($con, $sessionId);
        if (!$session) {
            return ['status' => 'error', 'message' => trans('session_not_found')];
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $hasAccess = $session['course_id']
            ? in_array($session['course_id'], $accessible['course_ids'])
            : in_array($session['subject_id'], $accessible['subject_ids']);
        if (!$hasAccess) {
            return ['status' => 'error', 'message' => trans('not_subscribed_to_session')];
        }

        $fileType = strtolower(trim($_GET['file_type'] ?? $_GET['type'] ?? ''));
        $allowedTypes = get_allowed_session_file_types();
        if (!empty($fileType) && !in_array($fileType, $allowedTypes)) {
            $fileType = null;
        }

        $files = get_session_files_list($con, $sessionId, $fileType ?: null);

        foreach ($files as &$file) {
            $subStmt = mysqli_prepare($con, "SELECT id, file_path, original_name, note, status, grade, teacher_feedback, graded_at, created_at FROM session_file_submissions WHERE session_file_id = ? AND student_id = ? LIMIT 1");
            mysqli_stmt_bind_param($subStmt, "ii", $file['id'], $studentId);
            mysqli_stmt_execute($subStmt);
            $sub = mysqli_fetch_assoc(mysqli_stmt_get_result($subStmt));

            if ($sub) {
                $sub['id'] = (int)$sub['id'];
                $sub['grade'] = $sub['grade'] !== null ? (float)$sub['grade'] : null;
                $file['child_submission'] = format_response_images($sub);
            } else {
                $file['child_submission'] = null;
            }
        }
        unset($file);

        return ['status' => 'success', 'data' => ['student_id' => $studentId, 'session_id' => $sessionId, 'files' => $files, 'total' => count($files)]];
    }
}
