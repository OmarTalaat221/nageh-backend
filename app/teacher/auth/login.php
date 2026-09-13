<?php
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('teacherLogin')) {
    function teacherLogin($con) {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('email_password_required')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, password, city_id, whatsapp, specialization, image, is_email_verified, is_active, is_attendance_suspended FROM teachers WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database query preparation failed: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $teacher = mysqli_fetch_assoc($result);

        if (!$teacher) {
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if (!password_verify($password, $teacher['password'])) {
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if ((int)$teacher['is_email_verified'] !== 1) {
            return [
                'status' => 'error',
                'message' => trans('email_not_verified'),
                'requires_email_verification' => true,
                'email' => $teacher['email']
            ];
        }

        if ((int)$teacher['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('account_inactive')];
        }

        $payload = [
            'id' => (int)$teacher['id'],
            'name' => $teacher['name'],
            'email' => $teacher['email'],
            'role' => 'teacher'
        ];

        $tokens = generateTeacherTokens($payload);

        $teacherId = (int)$teacher['id'];
        $cityId = $teacher['city_id'] ? (int)$teacher['city_id'] : null;

        unset($teacher['password'], $teacher['city_id'], $teacher['subject_id']);

        $teacher['id'] = $teacherId;
        $teacher['is_email_verified'] = (int)$teacher['is_email_verified'];
        $teacher['is_active'] = (int)$teacher['is_active'];
        $teacher['is_attendance_suspended'] = (int)$teacher['is_attendance_suspended'];
        if ($teacher['is_attendance_suspended'] === 1) {
            $teacher['suspension_reason'] = trans('teacher_suspension_reason');
        }
        $teacher['subjects'] = get_teacher_subjects_details($con, $teacherId);
        $teacher['city'] = get_city_details($con, $cityId);

        return [
            'status' => 'success',
            'message' => trans('teacher_login_success'),
            'data' => [
                'teacher' => format_response_images($teacher),
                'tokens' => $tokens
            ]
        ];
    }
}
