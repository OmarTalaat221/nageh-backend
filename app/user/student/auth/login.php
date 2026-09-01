<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../subscriptions/manage.php';

if (!function_exists('studentLogin')) {
    function studentLogin($con) {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('email_password_required')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, password, image, phone, age, school_id, city_id, is_email_verified, is_active FROM students WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database query preparation failed: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);

        if (!$student) {
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if (!password_verify($password, $student['password'])) {
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if ((int)$student['is_email_verified'] !== 1) {
            return [
                'status' => 'error',
                'message' => trans('email_not_verified'),
                'requires_email_verification' => true,
                'email' => $student['email']
            ];
        }

        if ((int)$student['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('account_inactive')];
        }

        $payload = [
            'id' => (int)$student['id'],
            'name' => $student['name'],
            'email' => $student['email'],
            'role' => 'student'
        ];

        $tokens = generateStudentTokens($payload);

        $stId = (int)$student['id'];
        $cityId = $student['city_id'] ? (int)$student['city_id'] : null;
        $schoolId = $student['school_id'] ? (int)$student['school_id'] : null;

        unset($student['password']);

        $student['id'] = $stId;
        $student['city_id'] = $cityId;
        $student['school_id'] = $schoolId;
        $student['age'] = $student['age'] ? (int)$student['age'] : null;
        $student['is_email_verified'] = (int)$student['is_email_verified'];
        $student['is_active'] = (int)$student['is_active'];
        $student['city'] = get_city_details($con, $cityId);
        $student['school'] = get_school_details($con, $schoolId);
        $student['parents'] = get_student_parents_details($con, $stId);
        $student['parent_ids'] = array_values(array_map(function($p) { return (int)$p['id']; }, $student['parents']));

        $accessible = get_student_accessible_entities($con, $stId);
        $student['is_subscribed'] = !empty($accessible['course_ids']);

        return [
            'status' => 'success',
            'message' => trans('student_login_success'),
            'data' => [
                'student' => format_response_images($student),
                'tokens' => $tokens
            ]
        ];
    }
}
