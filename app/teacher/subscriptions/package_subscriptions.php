<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../admin/subscriptions/manage.php';

if (!function_exists('teacher_owns_course')) {
    /**
     * A teacher owns a course when they are its teacher, created it, or sit on its
     * course_teachers list. The same three ways the rest of the teacher dashboard decides it.
     */
    function teacher_owns_course($con, int $teacherId, int $courseId): bool {
        if ($teacherId <= 0 || $courseId <= 0) {
            return false;
        }
        $sql = "
            SELECT 1
            FROM courses c
            LEFT JOIN course_teachers ct ON ct.course_id = c.id AND ct.teacher_id = ? AND ct.is_active = 1
            WHERE c.id = ?
              AND (c.teacher_id = ? OR (c.created_by_type = 'teacher' AND c.created_by_id = ?) OR ct.teacher_id IS NOT NULL)
            LIMIT 1
        ";
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "iiii", $teacherId, $courseId, $teacherId, $teacherId);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }
        $res = mysqli_stmt_get_result($stmt);
        return (bool)($res && mysqli_fetch_row($res));
    }
}

if (!function_exists('teacherListPackageSubscriptions')) {
    /**
     * The subscribers of one package, with the subscription details the admin screen shows:
     * status, period, price, how they subscribed.
     *
     * The teacher gets the admin's whole filter set rather than a reduced copy of it, because
     * the filtering is already written once in adminListStudentSubscriptions and reading
     * $_GET is how it takes its arguments. What a teacher does NOT get is the admin's reach:
     * course_id is required here and has to be a course this teacher owns, so the shared
     * listing can only ever answer about their own students. Read-only - pausing, cancelling
     * and editing a subscription stay with the admin.
     */
    function teacherListPackageSubscriptions($con, $teacherData = null) {
        $tId = 0;
        if (is_array($teacherData)) {
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        } elseif (is_object($teacherData)) {
            $tId = (int)($teacherData->id ?? $teacherData->teacher_id ?? 0);
        }
        if ($tId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication is required'];
        }

        $courseId = (int)($_GET['course_id'] ?? $_GET['course_ids'] ?? 0);
        if ($courseId <= 0) {
            return ['status' => 'error', 'message' => 'course_id is required'];
        }
        if (!teacher_owns_course($con, $tId, $courseId)) {
            return ['status' => 'error', 'message' => 'This package does not belong to you'];
        }

        // Pinned to the one course that was just checked, so no other course_id spelling in the
        // query string can widen the listing past it.
        $_GET['course_id'] = $courseId;
        unset($_GET['course_ids'], $_GET['student_id']);

        return adminListStudentSubscriptions($con);
    }
}
