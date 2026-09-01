<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('parse_teacher_subject_assignments')) {
    // Accepts either the richer `subjects: [{subject_id, academic_year_id}]` shape, or the
    // legacy flat `subject_ids: [1,2,3]` array (each treated as "all grades" / academic_year_id null).
    function parse_teacher_subject_assignments(array $body): array {
        $assignments = [];

        if (isset($body['subjects']) && is_array($body['subjects'])) {
            foreach ($body['subjects'] as $entry) {
                if (!is_array($entry)) continue;
                $subjectId = (int)($entry['subject_id'] ?? 0);
                if ($subjectId <= 0) continue;
                $academicYearId = isset($entry['academic_year_id']) && $entry['academic_year_id'] ? (int)$entry['academic_year_id'] : null;
                $assignments[] = ['subject_id' => $subjectId, 'academic_year_id' => $academicYearId];
            }
            return $assignments;
        }

        if (isset($body['subject_ids']) && is_array($body['subject_ids'])) {
            foreach ($body['subject_ids'] as $sid) {
                if ((int)$sid > 0) {
                    $assignments[] = ['subject_id' => (int)$sid, 'academic_year_id' => null];
                }
            }
        } elseif (isset($body['subject_id']) && (int)$body['subject_id'] > 0) {
            $assignments[] = ['subject_id' => (int)$body['subject_id'], 'academic_year_id' => null];
        }

        return $assignments;
    }
}

if (!function_exists('adminListTeachers')) {
    function adminListTeachers($con) {
        ensure_teachers_schema($con);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (t.name LIKE ? OR t.email LIKE ? OR t.whatsapp LIKE ? OR t.specialization LIKE ?)";
            $params = [$searchPattern, $searchPattern, $searchPattern, $searchPattern];
            $types = "ssss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS pending_count FROM teachers");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'pending_count' => (int)($statRow['pending_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(*) AS total FROM teachers t {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT t.id, t.subject_id, t.name, t.email, t.whatsapp, t.specialization, t.experience, t.biography, t.description, t.qualifications, t.can_view_profile, t.video, t.image, t.rating, t.city_id, t.is_email_verified, t.is_active, t.created_at FROM teachers t {$whereClause} ORDER BY t.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $teachers = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $tId = (int)$row['id'];
            $cId = $row['city_id'] ? (int)$row['city_id'] : null;
            $subId = $row['subject_id'] ? (int)$row['subject_id'] : null;

            $row['id'] = $tId;
            $row['city_id'] = $cId;
            $row['subject_id'] = $subId;
            $row['rating'] = (float)$row['rating'];
            $row['can_view_profile'] = (int)($row['can_view_profile'] ?? 1);
            $row['is_email_verified'] = (int)$row['is_email_verified'];
            $row['is_active'] = (int)$row['is_active'];
            $row['subjects'] = get_teacher_subjects_details($con, $tId);
            $row['subject_ids'] = array_values(array_map(function($s) { return (int)$s['id']; }, $row['subjects']));
            $row['subject_assignments'] = get_teacher_subject_assignments($con, $tId);
            $row['city'] = get_city_details($con, $cId);

            $teachers[] = $row;
        }

        return build_admin_paginated_response(format_response_images($teachers), $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetTeacher')) {
    function adminGetTeacher($con) {
        ensure_teachers_schema($con);
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Teacher ID is required'];

        $stmt = mysqli_prepare($con, "SELECT id, subject_id, name, email, whatsapp, specialization, experience, biography, rating, video, image, description, qualifications, can_view_profile, city_id, is_email_verified, is_active, created_at FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) return ['status' => 'error', 'message' => 'Teacher not found'];

        $tId = (int)$row['id'];
        $cId = $row['city_id'] ? (int)$row['city_id'] : null;
        $subId = $row['subject_id'] ? (int)$row['subject_id'] : null;

        $row['id'] = $tId;
        $row['city_id'] = $cId;
        $row['subject_id'] = $subId;
        $row['rating'] = (float)$row['rating'];
        $row['can_view_profile'] = (int)($row['can_view_profile'] ?? 1);
        $row['is_email_verified'] = (int)$row['is_email_verified'];
        $row['is_active'] = (int)$row['is_active'];
        $row['subjects'] = get_teacher_subjects_details($con, $tId);
        $row['subject_ids'] = array_values(array_map(function($s) { return (int)$s['id']; }, $row['subjects']));
        $row['subject_assignments'] = get_teacher_subject_assignments($con, $tId);
        $row['city'] = get_city_details($con, $cId);

        return ['status' => 'success', 'data' => format_response_images($row)];
    }
}

if (!function_exists('adminCreateTeacher')) {
    function adminCreateTeacher($con) {
        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $whatsapp = trim($body['whatsapp'] ?? '');
        $specialization = trim($body['specialization'] ?? '');
        $image = trim($body['image'] ?? '');
        $video = trim($body['video'] ?? '');
        $experience = trim($body['experience'] ?? '');
        $biography = trim($body['biography'] ?? '');
        $description = trim($body['description'] ?? '');
        $qualifications = trim($body['qualifications'] ?? '');
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $isVerified = isset($body['is_email_verified']) ? (int)$body['is_email_verified'] : 1;
        $canViewProfile = isset($body['can_view_profile']) ? (int)$body['can_view_profile'] : 1;

        $subjectAssignments = parse_teacher_subject_assignments($body);
        $subjectIds = array_values(array_unique(array_column($subjectAssignments, 'subject_id')));

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => 'Name, email, and password are required'];
        }

        if (empty($subjectAssignments)) {
            return ['status' => 'error', 'message' => 'At least one subject_id is required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'Email already registered'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $primarySubjectId = $subjectIds[0];

        $stmt = mysqli_prepare($con, "INSERT INTO teachers (name, email, password, subject_id, city_id, whatsapp, specialization, image, video, experience, biography, description, qualifications, is_email_verified, is_active, can_view_profile) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssiissssssssiii", $name, $email, $hashedPassword, $primarySubjectId, $cityId, $whatsapp, $specialization, $image, $video, $experience, $biography, $description, $qualifications, $isVerified, $isActive, $canViewProfile);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create teacher: ' . mysqli_error($con)];
        }

        $teacherId = mysqli_insert_id($con);

        $pivotStmt = mysqli_prepare($con, "INSERT INTO teacher_subjects (teacher_id, subject_id, academic_year_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        foreach ($subjectAssignments as $assignment) {
            mysqli_stmt_bind_param($pivotStmt, "iii", $teacherId, $assignment['subject_id'], $assignment['academic_year_id']);
            mysqli_stmt_execute($pivotStmt);
        }

        $_GET['id'] = $teacherId;
        return [
            'status' => 'success',
            'message' => 'Teacher created successfully',
            'data' => adminGetTeacher($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateTeacher')) {
    function adminUpdateTeacher($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Teacher ID is required'];

        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $whatsapp = trim($body['whatsapp'] ?? '');
        $specialization = trim($body['specialization'] ?? '');
        $image = isset($body['image']) ? trim($body['image']) : null;
        $video = isset($body['video']) ? trim($body['video']) : null;
        $experience = isset($body['experience']) ? trim($body['experience']) : null;
        $biography = isset($body['biography']) ? trim($body['biography']) : null;
        $description = isset($body['description']) ? trim($body['description']) : null;
        $qualifications = isset($body['qualifications']) ? trim($body['qualifications']) : null;
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;
        $isVerified = isset($body['is_email_verified']) ? (int)$body['is_email_verified'] : null;
        $canViewProfile = isset($body['can_view_profile']) ? (int)$body['can_view_profile'] : null;

        if (!empty($name)) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET name = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $name, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($email)) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET email = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($con, "UPDATE teachers SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($whatsapp)) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET whatsapp = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $whatsapp, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($specialization)) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET specialization = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $specialization, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($image !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $image, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($video !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET video = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $video, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($experience !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET experience = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $experience, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($biography !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET biography = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $biography, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($description !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET description = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $description, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($qualifications !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET qualifications = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $qualifications, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($cityId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET city_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $cityId, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isVerified !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET is_email_verified = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isVerified, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($canViewProfile !== null) {
            $stmt = mysqli_prepare($con, "UPDATE teachers SET can_view_profile = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $canViewProfile, $id);
            mysqli_stmt_execute($stmt);
        }

        if (isset($body['subjects']) || isset($body['subject_ids'])) {
            $subjectAssignments = parse_teacher_subject_assignments($body);
            mysqli_query($con, "DELETE FROM teacher_subjects WHERE teacher_id = {$id}");
            $pivotStmt = mysqli_prepare($con, "INSERT INTO teacher_subjects (teacher_id, subject_id, academic_year_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
            foreach ($subjectAssignments as $assignment) {
                mysqli_stmt_bind_param($pivotStmt, "iii", $id, $assignment['subject_id'], $assignment['academic_year_id']);
                mysqli_stmt_execute($pivotStmt);
            }
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => 'Teacher updated successfully',
            'data' => adminGetTeacher($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteTeacher')) {
    function adminDeleteTeacher($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Teacher ID is required'];

        $stmt = mysqli_prepare($con, "DELETE FROM teachers WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete teacher: ' . mysqli_error($con)];
        }
        return ['status' => 'success', 'message' => 'Teacher deleted successfully'];
    }
}
