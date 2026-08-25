<?php
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/email_verification.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('teacherRegister')) {
    function teacherRegister($con) {
        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $whatsapp = trim($body['whatsapp'] ?? '');
        $specialization = trim($body['specialization'] ?? '');
        $image = trim($body['image'] ?? '');

        $subjectIds = [];
        if (isset($body['subject_ids']) && is_array($body['subject_ids'])) {
            foreach ($body['subject_ids'] as $sid) {
                if ((int)$sid > 0) $subjectIds[] = (int)$sid;
            }
        } elseif (isset($body['subject_id']) && (int)$body['subject_id'] > 0) {
            $subjectIds[] = (int)$body['subject_id'];
        }

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('name_email_password_required')];
        }

        if (empty($subjectIds)) {
            return ['status' => 'error', 'message' => trans('subject_id_required')];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => trans('invalid_email')];
        }

        if (strlen($password) < 6) {
            return ['status' => 'error', 'message' => trans('short_password')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($res)) {
            return ['status' => 'error', 'message' => trans('email_already_registered')];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $primarySubjectId = $subjectIds[0];

        $stmt = mysqli_prepare($con, "INSERT INTO teachers (name, email, password, subject_id, city_id, whatsapp, specialization, image, is_email_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");
        mysqli_stmt_bind_param($stmt, "sssiisss", $name, $email, $hashedPassword, $primarySubjectId, $cityId, $whatsapp, $specialization, $image);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create teacher account: ' . mysqli_error($con)];
        }

        $teacherId = mysqli_insert_id($con);

        $pivotStmt = mysqli_prepare($con, "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        foreach ($subjectIds as $subId) {
            mysqli_stmt_bind_param($pivotStmt, "ii", $teacherId, $subId);
            mysqli_stmt_execute($pivotStmt);
        }

        $otpInfo = generateAndSendOtp($con, 'teacher', $teacherId, $email);

        $expandedSubjects = get_teacher_subjects_details($con, $teacherId);
        $expandedCity = get_city_details($con, $cityId);

        return [
            'status' => 'success',
            'message' => trans('teacher_register_success'),
            'data' => [
                'teacher' => format_response_images([
                    'id' => (int)$teacherId,
                    'name' => $name,
                    'email' => $email,
                    'whatsapp' => $whatsapp,
                    'specialization' => $specialization,
                    'image' => $image,
                    'is_email_verified' => 0,
                    'is_active' => 0,
                    'subjects' => $expandedSubjects,
                    'city' => $expandedCity
                ]),
                'verification' => [
                    'otp_code' => $otpInfo['code'],
                    'expires_at' => $otpInfo['expires_at']
                ]
            ]
        ];
    }
}
