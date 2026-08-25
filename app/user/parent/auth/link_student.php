<?php

if (!function_exists('parentLinkStudent')) {
    function parentLinkStudent($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? 0);
        $body = read_json_body();
        $studentEmail = strtolower(trim($body['student_email'] ?? ''));
        $studentId = isset($body['student_id']) ? (int)$body['student_id'] : 0;

        if (empty($studentEmail) && $studentId <= 0) {
            return ['status' => 'error', 'message' => trans('student_not_found')];
        }

        if (!empty($studentEmail)) {
            $stmt = mysqli_prepare($con, "SELECT id, name, email FROM students WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $studentEmail);
        } else {
            $stmt = mysqli_prepare($con, "SELECT id, name, email FROM students WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "i", $studentId);
        }

        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($res);

        if (!$student) {
            return ['status' => 'error', 'message' => trans('student_not_found')];
        }

        $foundStudentId = (int)$student['id'];

        $checkStmt = mysqli_prepare($con, "SELECT id FROM parent_students WHERE parent_id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $parentId, $foundStudentId);
        mysqli_stmt_execute($checkStmt);
        $checkRes = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkRes)) {
            return ['status' => 'error', 'message' => trans('student_already_linked')];
        }

        $linkStmt = mysqli_prepare($con, "INSERT INTO parent_students (parent_id, student_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($linkStmt, "ii", $parentId, $foundStudentId);

        if (!mysqli_stmt_execute($linkStmt)) {
            return ['status' => 'error', 'message' => 'Failed to link student: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => trans('student_linked_success'),
            'data' => [
                'parent_id' => $parentId,
                'student' => $student
            ]
        ];
    }
}

if (!function_exists('parentUnlinkStudent')) {
    function parentUnlinkStudent($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? 0);
        $body = read_json_body();
        $studentId = isset($body['student_id']) ? (int)$body['student_id'] : 0;
        $studentEmail = strtolower(trim($body['student_email'] ?? ''));

        if ($studentId <= 0 && !empty($studentEmail)) {
            $stmt = mysqli_prepare($con, "SELECT id FROM students WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $studentEmail);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $studentId = (int)$row['id'];
            }
        }

        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('student_not_found')];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM parent_students WHERE parent_id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $parentId, $studentId);
        mysqli_stmt_execute($stmt);

        return [
            'status' => 'success',
            'message' => trans('student_unlinked_success')
        ];
    }
}
