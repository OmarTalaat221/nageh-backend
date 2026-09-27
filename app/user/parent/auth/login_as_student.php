<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/JWT.php';

if (!function_exists('parentLoginAsStudent')) {
    function parentLoginAsStudent($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $body = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);

        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'student_id is required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT ps.id FROM parent_students ps WHERE ps.parent_id = ? AND ps.student_id = ? AND ps.is_active = 1 LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $parentId, $studentId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'This student is not linked to your account'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, image, phone, age, city_id, school_id, study_language, is_email_verified, is_active FROM students WHERE id = ? AND is_active = 1 LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$student) {
            return ['status' => 'error', 'message' => 'Student account not found or inactive'];
        }

        // No `did` claim here on purpose: a parent looking in on their child is not the child
        // signing in, so it must not eat one of the student's device slots, and the student's
        // device limit must not lock the parent out. The student auth middleware only enforces
        // the device check on tokens that carry a `did`, so this passes through untouched.
        $payload = [
            'id' => (int)$student['id'],
            'name' => $student['name'],
            'email' => $student['email'],
            'role' => 'student',
            'logged_in_by_parent' => $parentId
        ];

        $tokens = generateStudentTokens($payload);

        $cId = $student['city_id'] ? (int)$student['city_id'] : null;
        $sId = $student['school_id'] ? (int)$student['school_id'] : null;

        $student['id'] = (int)$student['id'];
        $student['age'] = $student['age'] ? (int)$student['age'] : null;
        $student['city_id'] = $cId;
        $student['school_id'] = $sId;
        $student['is_email_verified'] = (int)$student['is_email_verified'];
        $student['is_active'] = (int)$student['is_active'];
        $student['city'] = get_city_details($con, $cId);
        $student['school'] = get_school_details($con, $sId);

        return [
            'status' => 'success',
            'message' => 'Logged in as student successfully',
            'data' => [
                'student' => format_response_images($student),
                'logged_in_by_parent_id' => $parentId,
                'tokens' => $tokens
            ]
        ];
    }
}
