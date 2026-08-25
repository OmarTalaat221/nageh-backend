<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('adminListSchools')) {
    function adminListSchools($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (s.code LIKE ? OR st.name LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM schools");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT s.id) AS total FROM schools s LEFT JOIN schools_translations st ON s.id = st.school_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT DISTINCT s.id, s.code, s.school_type_id, s.city_id, s.is_active, s.created_at FROM schools s LEFT JOIN schools_translations st ON s.id = st.school_id {$whereClause} ORDER BY s.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $schools = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $schoolId = (int)$row['id'];
            $cId = $row['city_id'] ? (int)$row['city_id'] : null;
            $stId = $row['school_type_id'] ? (int)$row['school_type_id'] : null;

            $row['id'] = $schoolId;
            $row['is_active'] = (int)$row['is_active'];
            $row['city'] = get_city_details($con, $cId);
            $row['school_type'] = get_school_type_details($con, $stId);

            $transStmt = mysqli_prepare($con, "SELECT st.language_id, l.code AS language_code, st.name FROM schools_translations st JOIN languages l ON st.language_id = l.id WHERE st.school_id = ?");
            mysqli_stmt_bind_param($transStmt, "i", $schoolId);
            mysqli_stmt_execute($transStmt);
            $transRes = mysqli_stmt_get_result($transStmt);

            $translations = [];
            while ($t = mysqli_fetch_assoc($transRes)) {
                $t['language_id'] = (int)$t['language_id'];
                if ($t['language_code'] === 'ar') $row['name_ar'] = $t['name'];
                if ($t['language_code'] === 'en') $row['name_en'] = $t['name'];
                $translations[] = $t;
            }
            $row['translations'] = $translations;

            unset($row['city_id'], $row['school_type_id']);
            $schools[] = $row;
        }

        return build_admin_paginated_response($schools, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetSchool')) {
    function adminGetSchool($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'School ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, school_type_id, city_id, is_active, created_at FROM schools WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'School not found'];
        }

        $schoolId = (int)$row['id'];
        $cId = $row['city_id'] ? (int)$row['city_id'] : null;
        $stId = $row['school_type_id'] ? (int)$row['school_type_id'] : null;

        $row['id'] = $schoolId;
        $row['is_active'] = (int)$row['is_active'];
        $row['city'] = get_city_details($con, $cId);
        $row['school_type'] = get_school_type_details($con, $stId);

        $transStmt = mysqli_prepare($con, "SELECT st.language_id, l.code AS language_code, st.name FROM schools_translations st JOIN languages l ON st.language_id = l.id WHERE st.school_id = ?");
        mysqli_stmt_bind_param($transStmt, "i", $schoolId);
        mysqli_stmt_execute($transStmt);
        $transRes = mysqli_stmt_get_result($transStmt);

        $translations = [];
        while ($t = mysqli_fetch_assoc($transRes)) {
            $t['language_id'] = (int)$t['language_id'];
            if ($t['language_code'] === 'ar') $row['name_ar'] = $t['name'];
            if ($t['language_code'] === 'en') $row['name_en'] = $t['name'];
            $translations[] = $t;
        }
        $row['translations'] = $translations;

        unset($row['city_id'], $row['school_type_id']);
        return ['status' => 'success', 'data' => $row];
    }
}

if (!function_exists('adminCreateSchool')) {
    function adminCreateSchool($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $schoolTypeId = (int)($body['school_type_id'] ?? 1);
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($code) || empty($nameAr)) {
            return ['status' => 'error', 'message' => 'School code and name_ar are required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO schools (school_type_id, city_id, code, is_active) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisi", $schoolTypeId, $cityId, $code, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create school: ' . mysqli_error($con)];
        }

        $schoolId = mysqli_insert_id($con);

        $transStmt = mysqli_prepare($con, "INSERT INTO schools_translations (school_id, language_id, name) VALUES (?, 1, ?), (?, 2, ?)");
        mysqli_stmt_bind_param($transStmt, "isis", $schoolId, $nameAr, $schoolId, $nameEn);
        mysqli_stmt_execute($transStmt);

        return [
            'status' => 'success',
            'message' => 'School created successfully',
            'data' => adminGetSchool($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateSchool')) {
    function adminUpdateSchool($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? '');
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $schoolTypeId = isset($body['school_type_id']) ? (int)$body['school_type_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'School ID is required'];
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE schools SET code = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($cityId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE schools SET city_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $cityId, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($schoolTypeId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE schools SET school_type_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $schoolTypeId, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE schools SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($nameAr)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO schools_translations (school_id, language_id, name) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameAr);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($nameEn)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO schools_translations (school_id, language_id, name) VALUES (?, 2, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameEn);
            mysqli_stmt_execute($transStmt);
        }

        return [
            'status' => 'success',
            'message' => 'School updated successfully',
            'data' => adminGetSchool($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteSchool')) {
    function adminDeleteSchool($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'School ID is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM schools WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete school: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'School deleted successfully'];
    }
}
