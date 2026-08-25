<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../../helpers/notifications.php';

if (!function_exists('parentProfile')) {
    function parentProfile($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? 0);

        $stmt = mysqli_prepare($con, "SELECT id, name, email, image, city_id, is_email_verified, is_active, created_at FROM parents WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $parentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $parent = mysqli_fetch_assoc($res);

        if (!$parent) {
            return ['status' => 'error', 'message' => trans('profile_not_found')];
        }

        $cityId = $parent['city_id'] ? (int)$parent['city_id'] : null;

        $parent['id'] = (int)$parent['id'];
        $parent['city_id'] = $cityId;
        $parent['is_email_verified'] = (int)$parent['is_email_verified'];
        $parent['is_active'] = (int)$parent['is_active'];
        $parent['city'] = get_city_details($con, $cityId);

        $studentsStmt = mysqli_prepare($con, "SELECT s.id, s.name, s.email, s.image, s.phone, s.age, s.city_id, s.school_id FROM students s JOIN parent_students ps ON s.id = ps.student_id WHERE ps.parent_id = ? AND ps.is_active = 1");
        mysqli_stmt_bind_param($studentsStmt, "i", $parentId);
        mysqli_stmt_execute($studentsStmt);
        $studentsRes = mysqli_stmt_get_result($studentsStmt);

        $students = [];
        while ($row = mysqli_fetch_assoc($studentsRes)) {
            $sCityId = $row['city_id'] ? (int)$row['city_id'] : null;
            $sSchoolId = $row['school_id'] ? (int)$row['school_id'] : null;

            $row['id'] = (int)$row['id'];
            $row['city_id'] = $sCityId;
            $row['school_id'] = $sSchoolId;
            $row['age'] = $row['age'] ? (int)$row['age'] : null;
            $row['city'] = get_city_details($con, $sCityId);
            $row['school'] = get_school_details($con, $sSchoolId);

            $students[] = $row;
        }

        $parent['students'] = $students;
        $parent['student_ids'] = array_values(array_map(function($s) { return (int)$s['id']; }, $students));

        return [
            'status' => 'success',
            'message' => trans('profile_fetched_success'),
            'data' => format_response_images($parent)
        ];
    }
}

if (!function_exists('parentUpdateProfile')) {
    function parentUpdateProfile($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? 0);
        if ($parentId <= 0) {
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
            $chkEmail = mysqli_prepare($con, "SELECT id FROM parents WHERE email = ? AND id != ? LIMIT 1");
            if ($chkEmail) {
                mysqli_stmt_bind_param($chkEmail, "si", $newEmail, $parentId);
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
            $updateFields[] = "phone = ?";
            $params[] = $phoneVal !== '' ? $phoneVal : null;
            $types .= "s";
        }

        if (array_key_exists('city_id', $body)) {
            $updateFields[] = "city_id = ?";
            $params[] = ($body['city_id'] !== null && is_numeric($body['city_id']) && (int)$body['city_id'] > 0) ? (int)$body['city_id'] : null;
            $types .= "i";
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

        $params[] = $parentId;
        $types .= "i";
        $stmt = mysqli_prepare($con, "UPDATE parents SET " . implode(", ", $updateFields) . " WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare parent update query'];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update parent profile: ' . mysqli_error($con)];
        }

        $profileRes = parentProfile($con, $parentData);
        if (is_array($profileRes) && ($profileRes['status'] ?? '') === 'success') {
            $profileRes['message'] = trans('profile_updated_success');
        }
        return $profileRes;
    }
}

if (!function_exists('parentUpdateFcmToken')) {
    function parentUpdateFcmToken($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? 0);
        $body = read_json_body();
        return save_user_fcm_token($con, 'parent', $parentId, trim($body['fcm_token'] ?? ''));
    }
}
