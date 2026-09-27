<?php
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/app_settings.php';

if (!function_exists('parentLogin')) {
    function parentLogin($con) {

        $body     = read_json_body();
        $email    = strtolower(trim($body['email']    ?? ''));
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            return [
                'status'  => 'error',
                'message' => trans('email_password_required')
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status'  => 'error',
                'message' => trans('invalid_email_format')
            ];
        }

        // Counted as 'student' rather than 'parent' because that is whose credentials this
        // endpoint actually checks - the table below is `students`. Sharing the counter with the
        // student login means a lockout cannot be sidestepped by switching to this path.
        $locked = login_attempt_guard($con, $email, 'student');
        if ($locked !== null) return $locked;

        $sql = "SELECT
                    id, name, email, password, image, phone, age,
                    school_id, city_id, is_email_verified, is_active
                FROM students
                WHERE email = ?
                LIMIT 1";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return [
                'status'  => 'error',
                'message' => 'Database query preparation failed: ' . mysqli_error($con)
            ];
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result  = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$student || !password_verify($password, $student['password'])) {
            login_attempt_fail($con, $email, 'student');
            return [
                'status'  => 'error',
                'message' => trans('invalid_credentials')
            ];
        }

        login_attempt_reset($con, $email, 'student');

        if ((int)$student['is_email_verified'] !== 1) {
            return [
                'status'                      => 'error',
                'message'                     => trans('email_not_verified'),
                'requires_email_verification' => true,
                'email'                       => $student['email']
            ];
        }

        if ((int)$student['is_active'] !== 1) {
            return [
                'status'  => 'error',
                'message' => trans('account_inactive')
            ];
        }

        $studentId = (int)$student['id'];

        $payload = [
            'id'    => $studentId,
            'name'  => $student['name'],
            'email' => $student['email'],
            'role'  => 'student'
        ];

        $tokens = generateStudentTokens($payload);

        unset($student['password']);

        $student['id']         = $studentId;
        $student['school_id']  = $student['school_id'] ? (int)$student['school_id'] : null;
        $student['city_id']    = $student['city_id']   ? (int)$student['city_id']   : null;
        $student['type']       = "parent";
        $student['parents']    = get_student_parents_details($con, $studentId);


        return [
            'status'  => 'success',
            'message' => trans('student_login_success'),
            'data'    => [
                'student' => format_response_images($student),
                'tokens'  => $tokens
            ]
        ];
    }
}