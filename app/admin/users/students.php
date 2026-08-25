<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('adminListStudents')) {
    function adminListStudents($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
            $params = [$searchPattern, $searchPattern, $searchPattern];
            $types = "sss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM students");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(*) AS total FROM students s {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT s.id, s.name, s.email, s.image, s.phone, s.age, s.city_id, s.school_id, s.is_email_verified, s.is_active, s.created_at FROM students s {$whereClause} ORDER BY s.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $students = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $stId = (int)$row['id'];
            $cId = $row['city_id'] ? (int)$row['city_id'] : null;
            $sId = $row['school_id'] ? (int)$row['school_id'] : null;

            $row['id'] = $stId;
            $row['city_id'] = $cId;
            $row['school_id'] = $sId;
            $row['age'] = $row['age'] ? (int)$row['age'] : null;
            $row['is_email_verified'] = (int)$row['is_email_verified'];
            $row['is_active'] = (int)$row['is_active'];
            $row['city'] = get_city_details($con, $cId);
            $row['school'] = get_school_details($con, $sId);
            $row['parents'] = get_student_parents_details($con, $stId);
            $row['parent_ids'] = array_values(array_map(function($p) { return (int)$p['id']; }, $row['parents']));

            $students[] = $row;
        }

        return build_admin_paginated_response(format_response_images($students), $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetStudent')) {
    function adminGetStudent($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Student ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, image, phone, age, city_id, school_id, study_language, is_email_verified, is_active, created_at FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }

        $stId = (int)$row['id'];
        $cId = $row['city_id'] ? (int)$row['city_id'] : null;
        $sId = $row['school_id'] ? (int)$row['school_id'] : null;

        $row['id'] = $stId;
        $row['city_id'] = $cId;
        $row['school_id'] = $sId;
        $row['age'] = $row['age'] ? (int)$row['age'] : null;
        $row['is_email_verified'] = (int)$row['is_email_verified'];
        $row['is_active'] = (int)$row['is_active'];
        $row['city'] = get_city_details($con, $cId);
        $row['school'] = get_school_details($con, $sId);
        $row['parents'] = get_student_parents_details($con, $stId);
        $row['parent_ids'] = array_values(array_map(function($p) { return (int)$p['id']; }, $row['parents']));

        return ['status' => 'success', 'data' => format_response_images($row)];
    }
}

if (!function_exists('adminCreateStudent')) {
    function adminCreateStudent($con) {
        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $phone = trim($body['phone'] ?? '');
        $image = trim($body['image'] ?? '');
        $age = isset($body['age']) ? (int)$body['age'] : null;
        $schoolId = isset($body['school_id']) ? (int)$body['school_id'] : null;
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $isVerified = isset($body['is_email_verified']) ? (int)$body['is_email_verified'] : 1;
        $allowedLanguages = ['arabic', 'english', 'french'];
        $studyLanguage = strtolower(trim($body['study_language'] ?? 'arabic'));
        if (!in_array($studyLanguage, $allowedLanguages)) $studyLanguage = 'arabic';

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => 'Name, email, and password are required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM students WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'Email already registered'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($con, "INSERT INTO students (name, email, password, image, phone, age, school_id, city_id, study_language, is_email_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssiiisii", $name, $email, $hashedPassword, $image, $phone, $age, $schoolId, $cityId, $studyLanguage, $isVerified, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create student: ' . mysqli_error($con)];
        }

        $studentId = mysqli_insert_id($con);
        $_GET['id'] = $studentId;
        return [
            'status' => 'success',
            'message' => 'Student created successfully',
            'data' => adminGetStudent($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateStudent')) {
    function adminUpdateStudent($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Student ID is required'];

        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $phone = trim($body['phone'] ?? '');
        $image = isset($body['image']) ? trim($body['image']) : null;
        $age = isset($body['age']) ? (int)$body['age'] : null;
        $schoolId = isset($body['school_id']) ? (int)$body['school_id'] : null;
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;
        $isVerified = isset($body['is_email_verified']) ? (int)$body['is_email_verified'] : null;

        if (!empty($name)) {
            $stmt = mysqli_prepare($con, "UPDATE students SET name = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $name, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($email)) {
            $stmt = mysqli_prepare($con, "UPDATE students SET email = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($con, "UPDATE students SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($image !== null) {
            $stmt = mysqli_prepare($con, "UPDATE students SET image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $image, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($phone)) {
            $stmt = mysqli_prepare($con, "UPDATE students SET phone = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $phone, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($age !== null) {
            $stmt = mysqli_prepare($con, "UPDATE students SET age = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $age, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($schoolId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE students SET school_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $schoolId, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($cityId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE students SET city_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $cityId, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE students SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isVerified !== null) {
            $stmt = mysqli_prepare($con, "UPDATE students SET is_email_verified = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isVerified, $id);
            mysqli_stmt_execute($stmt);
        }
        $studyLanguageUpdate = isset($body['study_language']) ? strtolower(trim($body['study_language'])) : null;
        $allowedLanguages = ['arabic', 'english', 'french'];
        if ($studyLanguageUpdate !== null && in_array($studyLanguageUpdate, $allowedLanguages)) {
            $stmt = mysqli_prepare($con, "UPDATE students SET study_language = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $studyLanguageUpdate, $id);
            mysqli_stmt_execute($stmt);
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => adminGetStudent($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteStudent')) {
    function adminDeleteStudent($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Student ID is required'];

        $stmt = mysqli_prepare($con, "DELETE FROM students WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete student: ' . mysqli_error($con)];
        }
        return ['status' => 'success', 'message' => 'Student deleted successfully'];
    }
}
