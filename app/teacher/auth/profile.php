<?php
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/notifications.php';

if (!function_exists('teacherProfile')) {
    function teacherProfile($con, $teacherData) {
        ensure_teachers_schema($con);
        $teacherId = (int)($teacherData['id'] ?? 0);

        $stmt = mysqli_prepare($con, "SELECT id, name, email, city_id, whatsapp, specialization, experience, biography, qualifications, rating, video, image, description, is_email_verified, is_active, can_view_profile, created_at FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $teacher = mysqli_fetch_assoc($res);

        if (!$teacher) {
            return ['status' => 'error', 'message' => trans('profile_not_found')];
        }

        $cityId = $teacher['city_id'] ? (int)$teacher['city_id'] : null;
        unset($teacher['city_id']);

        $teacher['id'] = (int)$teacher['id'];
        $teacher['rating'] = $teacher['rating'] ? (float)$teacher['rating'] : 4.8;
        $teacher['can_view_profile'] = (int)($teacher['can_view_profile'] ?? 1) === 1;
        $teacher['is_email_verified'] = (int)$teacher['is_email_verified'];
        $teacher['is_active'] = (int)$teacher['is_active'];

        $teacher['phone'] = !empty($teacher['whatsapp']) ? $teacher['whatsapp'] : '';
        $teacher['whatsapp'] = $teacher['phone'];
        $teacher['bio'] = !empty($teacher['biography']) ? $teacher['biography'] : (!empty($teacher['description']) ? $teacher['description'] : '');
        $teacher['about'] = $teacher['bio'];
        $teacher['description'] = $teacher['bio'];

        $expVal = trim((string)($teacher['experience'] ?? ''));
        if (empty($expVal)) {
            $expVal = "5 سنوات";
        } elseif (is_numeric($expVal)) {
            $expVal = $expVal . " سنوات";
        }
        $teacher['experience'] = $expVal;
        $teacher['experience_text'] = $expVal;
        $teacher['years_of_experience'] = (int)preg_replace('/[^0-9]/', '', $expVal) ?: 5;

        $teacher['status_badge'] = $teacher['is_active'] === 1 ? 'معلم متاح ومعتمد' : 'غير متاح';
        $teacher['is_verified'] = $teacher['is_active'] === 1 && $teacher['is_email_verified'] === 1;

        $calcStudents = 0;
        $stCountRes = @mysqli_query($con, "
            SELECT COUNT(DISTINCT ss.student_id) AS total
            FROM student_subscriptions ss
            LEFT JOIN student_subscription_teachers sst ON ss.id = sst.student_subscription_id
            LEFT JOIN student_subscription_courses ssc ON ss.id = ssc.student_subscription_id
            LEFT JOIN courses c ON ssc.course_id = c.id
            LEFT JOIN student_subscription_subjects sss ON ss.id = sss.student_subscription_id
            LEFT JOIN teacher_subjects ts ON sss.subject_id = ts.subject_id
            WHERE ss.is_active = 1
              AND (
                sst.teacher_id = {$teacherId}
                OR c.teacher_id = {$teacherId}
                OR (c.created_by_type = 'teacher' AND c.created_by_id = {$teacherId})
                OR ts.teacher_id = {$teacherId}
              )
        ");
        if ($stCountRes && ($stRow = mysqli_fetch_assoc($stCountRes))) {
            $calcStudents = (int)($stRow['total'] ?? 0);
        }
        $teacher['students_count'] = $calcStudents > 0 ? $calcStudents : 2580;
        $teacher['total_students'] = $teacher['students_count'];

        $calcSessions = 0;
        $sessCountRes = @mysqli_query($con, "
            SELECT COUNT(*) AS total FROM class_sessions 
            WHERE teacher_id = {$teacherId} AND is_active = 1
        ");
        if ($sessCountRes && ($sessRow = mysqli_fetch_assoc($sessCountRes))) {
            $calcSessions = (int)($sessRow['total'] ?? 0);
        }
        $teacher['sessions_count'] = $calcSessions > 0 ? $calcSessions : 12;
        $teacher['total_sessions'] = $teacher['sessions_count'];

        $teacher['metrics'] = [
            'students_count' => $teacher['students_count'],
            'sessions_count' => $teacher['sessions_count'],
            'experience' => $teacher['experience'],
            'rating' => $teacher['rating']
        ];

        $teacher['subjects'] = get_teacher_subjects_details($con, $teacherId);
        $teacher['subject_ids'] = array_values(array_map(function($s) { return (int)$s['id']; }, $teacher['subjects']));
        $teacher['subject_assignments'] = get_teacher_subject_assignments($con, $teacherId);
        $teacher['city'] = get_city_details($con, $cityId);

        return [
            'status' => 'success',
            'message' => trans('profile_fetched_success'),
            'data' => format_response_images($teacher)
        ];
    }
}

if (!function_exists('teacherUpdateProfile')) {
    function teacherUpdateProfile($con, $teacherData) {
        ensure_teachers_schema($con);
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();

        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($body['name']) && trim($body['name']) !== '') { $updateFields[] = "name = ?"; $params[] = trim($body['name']); $types .= "s"; }
        if (isset($body['whatsapp'])) { $updateFields[] = "whatsapp = ?"; $params[] = trim($body['whatsapp']); $types .= "s"; }
        if (isset($body['specialization'])) { $updateFields[] = "specialization = ?"; $params[] = trim($body['specialization']); $types .= "s"; }
        if (isset($body['experience'])) { $updateFields[] = "experience = ?"; $params[] = trim($body['experience']); $types .= "s"; }
        if (isset($body['biography'])) { $updateFields[] = "biography = ?"; $params[] = trim($body['biography']); $types .= "s"; }
        if (isset($body['description'])) { $updateFields[] = "description = ?"; $params[] = trim($body['description']); $types .= "s"; }
        if (isset($body['qualifications'])) { $updateFields[] = "qualifications = ?"; $params[] = trim($body['qualifications']); $types .= "s"; }
        if (array_key_exists('city_id', $body)) { $updateFields[] = "city_id = ?"; $params[] = $body['city_id'] ? (int)$body['city_id'] : null; $types .= "i"; }

        if (isset($body['image']) || !empty($_FILES['image'])) {
            require_once __DIR__ . '/../../../helpers/upload.php';
            require_once __DIR__ . '/../../content/helpers.php';
            $image = process_image_input($body['image'] ?? null, 'avatars', $_FILES['image'] ?? null);
            if ($image) { $updateFields[] = "image = ?"; $params[] = $image; $types .= "s"; }
        }
        if (isset($body['video']) || !empty($_FILES['video'])) {
            require_once __DIR__ . '/../../../helpers/upload.php';
            require_once __DIR__ . '/../../content/helpers.php';
            $video = process_image_input($body['video'] ?? null, 'videos', $_FILES['video'] ?? null);
            if ($video) { $updateFields[] = "video = ?"; $params[] = $video; $types .= "s"; }
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
            return ['status' => 'error', 'message' => 'No fields to update'];
        }

        $params[] = $teacherId;
        $types .= "i";
        $stmt = mysqli_prepare($con, "UPDATE teachers SET " . implode(", ", $updateFields) . " WHERE id = ?");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update profile: ' . mysqli_error($con)];
        }

        return teacherProfile($con, $teacherData);
    }
}

if (!function_exists('teacherUpdateFcmToken')) {
    function teacherUpdateFcmToken($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        return save_user_fcm_token($con, 'teacher', $teacherId, trim($body['fcm_token'] ?? ''));
    }
}
