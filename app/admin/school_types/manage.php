<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('adminListSchoolTypes')) {
    function adminListSchoolTypes($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (st.code LIKE ? OR stt.name LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM school_types");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT st.id) AS total FROM school_types st LEFT JOIN school_types_translations stt ON st.id = stt.school_type_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT DISTINCT st.id, st.code, st.is_active, st.created_at, st.updated_at FROM school_types st LEFT JOIN school_types_translations stt ON st.id = stt.school_type_id {$whereClause} ORDER BY st.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $typesList = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $typeId = (int)$row['id'];
            $row['id'] = $typeId;
            $row['is_active'] = (int)$row['is_active'];

            $transStmt = mysqli_prepare($con, "SELECT stt.language_id, l.code AS language_code, stt.name FROM school_types_translations stt JOIN languages l ON stt.language_id = l.id WHERE stt.school_type_id = ?");
            mysqli_stmt_bind_param($transStmt, "i", $typeId);
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

            $typesList[] = $row;
        }

        return build_admin_paginated_response($typesList, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetSchoolType')) {
    function adminGetSchoolType($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'School type ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, is_active, created_at, updated_at FROM school_types WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'School type not found'];
        }

        $typeId = (int)$row['id'];
        $row['id'] = $typeId;
        $row['is_active'] = (int)$row['is_active'];

        $transStmt = mysqli_prepare($con, "SELECT stt.language_id, l.code AS language_code, stt.name FROM school_types_translations stt JOIN languages l ON stt.language_id = l.id WHERE stt.school_type_id = ?");
        mysqli_stmt_bind_param($transStmt, "i", $typeId);
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

        return ['status' => 'success', 'data' => $row];
    }
}

if (!function_exists('adminCreateSchoolType')) {
    function adminCreateSchoolType($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($code) || empty($nameAr)) {
            return ['status' => 'error', 'message' => 'School type code and name_ar are required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO school_types (code, is_active) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $code, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create school type: ' . mysqli_error($con)];
        }

        $typeId = mysqli_insert_id($con);

        $transStmt = mysqli_prepare($con, "INSERT INTO school_types_translations (school_type_id, language_id, name) VALUES (?, 1, ?), (?, 2, ?)");
        mysqli_stmt_bind_param($transStmt, "isis", $typeId, $nameAr, $typeId, $nameEn);
        mysqli_stmt_execute($transStmt);

        return [
            'status' => 'success',
            'message' => 'School type created successfully',
            'data' => adminGetSchoolType($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateSchoolType')) {
    function adminUpdateSchoolType($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'School type ID is required'];
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE school_types SET code = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE school_types SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($nameAr)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO school_types_translations (school_type_id, language_id, name) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameAr);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($nameEn)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO school_types_translations (school_type_id, language_id, name) VALUES (?, 2, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameEn);
            mysqli_stmt_execute($transStmt);
        }

        return [
            'status' => 'success',
            'message' => 'School type updated successfully',
            'data' => adminGetSchoolType($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteSchoolType')) {
    function adminDeleteSchoolType($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'School type ID is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM school_types WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete school type: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'School type deleted successfully'];
    }
}
