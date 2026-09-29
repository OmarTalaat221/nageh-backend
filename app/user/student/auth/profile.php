<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../subscriptions/manage.php';
require_once __DIR__ . '/../../../../helpers/notifications.php';
require_once __DIR__ . '/../../../../helpers/phone.php';

if (!function_exists('studentProfile')) {
    function studentProfile($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($studentId <= 0) {
            $studentId = (int)($_GET['student_id'] ?? 0);
        }

        if ($studentId <= 0) {
            return [
                'status' => 'success',
                'message' => 'Guest mode',
                'data' => null
            ];
        }

        require_once __DIR__ . '/../affiliate/manage.php';
        if (function_exists('ensure_affiliate_commissions_extended_columns')) {
            ensure_affiliate_commissions_extended_columns($con);
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, image, phone, age, school_id, city_id, academic_year_id, study_language, affiliate_code, points_balance, is_email_verified, is_active, created_at FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($res);

        if (!$student) {
            return ['status' => 'error', 'message' => trans('profile_not_found')];
        }

        require_once __DIR__ . '/../affiliate/manage.php';
        $affCode = $student['affiliate_code'] ?? '';
        if (empty($affCode) && function_exists('generateStudentAffiliateCode')) {
            $affCode = generateStudentAffiliateCode($con, $studentId);
        }

        $cityId = $student['city_id'] ? (int)$student['city_id'] : null;
        $schoolId = $student['school_id'] ? (int)$student['school_id'] : null;

        $student['id'] = (int)$student['id'];
        $student['city_id'] = $cityId;
        $student['school_id'] = $schoolId;
        $student['academic_year_id'] = !empty($student['academic_year_id']) ? (int)$student['academic_year_id'] : null;
        $student['age'] = $student['age'] ? (int)$student['age'] : null;
        $student['affiliate_code'] = $affCode;
        // $student['referral_code'] = $affCode;
        $student['share_link'] = "https://najeh-app.com/register?ref=" . urlencode($affCode);
        $student['points_balance'] = (float)($student['points_balance'] ?? 0.00);
        $student['is_email_verified'] = (int)$student['is_email_verified'];
        $student['is_active'] = (int)$student['is_active'];
        $student['city'] = get_city_details($con, $cityId);
        $student['school'] = get_school_details($con, $schoolId);
        $student['parents'] = get_student_parents_details($con, $studentId);
        $student['parent_ids'] = array_values(array_map(function($p) { return (int)$p['id']; }, $student['parents']));

        $accessible = get_student_accessible_entities($con, $studentId);
        $student['is_subscribed'] = !empty($accessible['course_ids']);

        return [
            'status' => 'success',
            'message' => trans('profile_fetched_success'),
            'data' => format_response_images($student)
        ];
    }
}

if (!function_exists('studentUpdateProfile')) {
    function studentUpdateProfile($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $body = read_json_body();

        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($body['name']) && trim($body['name']) !== '') {
            $updateFields[] = "name = ?";
            $params[] = trim($body['name']);
            $types .= "s";
        }

        if (isset($body['email']) && trim($body['email']) !== '') {
            $newEmail = strtolower(trim($body['email']));
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                return ['status' => 'error', 'message' => trans('invalid_email')];
            }
            $chkEmail = mysqli_prepare($con, "SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
            if ($chkEmail) {
                mysqli_stmt_bind_param($chkEmail, "si", $newEmail, $studentId);
                mysqli_stmt_execute($chkEmail);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($chkEmail))) {
                    return ['status' => 'error', 'message' => trans('email_in_use')];
                }
            }
            $updateFields[] = "email = ?";
            $params[] = $newEmail;
            $types .= "s";
        }

        if (isset($body['phone'])) {
            $phoneVal = trim($body['phone']);

            // The same rule registration applies. Without it, a student could take a number that
            // is already someone else's and lock both of them out of phone login - registration
            // would be guarded and the back door left open.
            if ($phoneVal !== '' && phone_find_students($con, $phoneVal, $studentId)) {
                return [
                    'status' => 'error',
                    'message' => trans('phone_already_registered'),
                    'error_code' => 'phone_already_registered',
                ];
            }

            $updateFields[] = "phone = ?";
            $params[] = $phoneVal !== '' ? $phoneVal : null;
            $types .= "s";
        }

        if (array_key_exists('age', $body)) {
            $updateFields[] = "age = ?";
            $params[] = ($body['age'] !== null && is_numeric($body['age']) && (int)$body['age'] > 0) ? (int)$body['age'] : null;
            $types .= "i";
        }

        if (array_key_exists('city_id', $body)) {
            $updateFields[] = "city_id = ?";
            $params[] = ($body['city_id'] !== null && is_numeric($body['city_id']) && (int)$body['city_id'] > 0) ? (int)$body['city_id'] : null;
            $types .= "i";
        }

        if (array_key_exists('school_id', $body)) {
            $updateFields[] = "school_id = ?";
            $params[] = ($body['school_id'] !== null && is_numeric($body['school_id']) && (int)$body['school_id'] > 0) ? (int)$body['school_id'] : null;
            $types .= "i";
        }

        if (array_key_exists('academic_year_id', $body)) {
            $updateFields[] = "academic_year_id = ?";
            $params[] = ($body['academic_year_id'] !== null && is_numeric($body['academic_year_id']) && (int)$body['academic_year_id'] > 0) ? (int)$body['academic_year_id'] : null;
            $types .= "i";
        }

        if (isset($body['study_language']) && trim($body['study_language']) !== '') {
            $allowedLanguages = ['arabic', 'english', 'french'];
            $studyLang = strtolower(trim($body['study_language']));
            if (!in_array($studyLang, $allowedLanguages, true)) {
                return ['status' => 'error', 'message' => trans('invalid_study_language')];
            }
            $updateFields[] = "study_language = ?";
            $params[] = $studyLang;
            $types .= "s";
        }

        // Image / Avatar upload processing
        $imageInput = $body['image'] ?? $body['avatar'] ?? $body['photo'] ?? $body['profile_image'] ?? $body['picture'] ?? $body['file'] ?? null;
        $fileInput = $_FILES['image'] ?? $_FILES['avatar'] ?? $_FILES['photo'] ?? $_FILES['profile_image'] ?? $_FILES['picture'] ?? $_FILES['file'] ?? null;

        if ($imageInput !== null || ($fileInput && !empty($fileInput['tmp_name']))) {
            require_once __DIR__ . '/../../../../helpers/upload.php';
            $uploadedImg = process_image_input($imageInput, 'avatars', $fileInput);
            if ($uploadedImg) {
                $updateFields[] = "image = ?";
                $params[] = $uploadedImg;
                $types .= "s";
            }
        }

        if (isset($body['password']) && strlen(trim($body['password'])) > 0) {
            if (strlen(trim($body['password'])) < 6) {
                return ['status' => 'error', 'message' => trans('short_password')];
            }
            $updateFields[] = "password = ?";
            $params[] = password_hash(trim($body['password']), PASSWORD_BCRYPT);
            $types .= "s";
        }

        if (empty($updateFields)) {
            return ['status' => 'error', 'message' => trans('no_fields_to_update')];
        }

        $params[] = $studentId;
        $types .= "i";
        $stmt = mysqli_prepare($con, "UPDATE students SET " . implode(", ", $updateFields) . " WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare update query'];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update profile: ' . mysqli_error($con)];
        }

        $profileRes = studentProfile($con, $studentData);
        if (is_array($profileRes) && ($profileRes['status'] ?? '') === 'success') {
            $profileRes['message'] = trans('profile_updated_success');
        }
        return $profileRes;
    }
}

if (!function_exists('studentUpdateFcmToken')) {
    function studentUpdateFcmToken($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        return save_user_fcm_token($con, 'student', $studentId, trim($body['fcm_token'] ?? ''));
    }
}
