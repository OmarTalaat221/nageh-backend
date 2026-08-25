<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/upload.php';

if (!function_exists('parentLogin')) {
    function parentLogin($con) {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('email_password_required')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, password, image, city_id, is_email_verified, is_active FROM parents WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database query preparation failed: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $parent = mysqli_fetch_assoc($result);

        if (!$parent) {
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if (!password_verify($password, $parent['password'])) {
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if ((int)$parent['is_email_verified'] !== 1) {
            return [
                'status' => 'error',
                'message' => trans('email_not_verified'),
                'requires_email_verification' => true,
                'email' => $parent['email']
            ];
        }

        if ((int)$parent['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('account_inactive')];
        }

        $payload = [
            'id' => (int)$parent['id'],
            'name' => $parent['name'],
            'email' => $parent['email'],
            'role' => 'parent'
        ];

        $tokens = generateParentTokens($payload);

        $pId = (int)$parent['id'];
        $cityId = $parent['city_id'] ? (int)$parent['city_id'] : null;

        unset($parent['password']);

        $parent['id'] = $pId;
        $parent['city_id'] = $cityId;
        $parent['is_email_verified'] = (int)$parent['is_email_verified'];
        $parent['is_active'] = (int)$parent['is_active'];
        $parent['city'] = get_city_details($con, $cityId);

        $studentsStmt = mysqli_prepare($con, "SELECT s.id, s.name, s.email, s.image, s.phone, s.age, s.city_id, s.school_id FROM students s JOIN parent_students ps ON s.id = ps.student_id WHERE ps.parent_id = ? AND ps.is_active = 1");
        mysqli_stmt_bind_param($studentsStmt, "i", $pId);
        mysqli_stmt_execute($studentsStmt);
        $studentsRes = mysqli_stmt_get_result($studentsStmt);

        $students = [];
        while ($sRow = mysqli_fetch_assoc($studentsRes)) {
            $scId = $sRow['city_id'] ? (int)$sRow['city_id'] : null;
            $schId = $sRow['school_id'] ? (int)$sRow['school_id'] : null;

            $sRow['id'] = (int)$sRow['id'];
            $sRow['city_id'] = $scId;
            $sRow['school_id'] = $schId;
            $sRow['age'] = $sRow['age'] ? (int)$sRow['age'] : null;
            $sRow['city'] = get_city_details($con, $scId);
            $sRow['school'] = get_school_details($con, $schId);
            $students[] = $sRow;
        }
        $parent['students'] = $students;
        $parent['student_ids'] = array_values(array_map(function($s) { return (int)$s['id']; }, $students));

        return [
            'status' => 'success',
            'message' => trans('parent_login_success'),
            'data' => [
                'parent' => format_response_images($parent),
                'tokens' => $tokens
            ]
        ];
    }
}
