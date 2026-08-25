<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';

if (!function_exists('studentListSchools')) {
    function studentListSchools($con) {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
        $cityId = isset($_GET['city_id']) ? (int)$_GET['city_id'] : 0;
        $schoolTypeId = isset($_GET['school_type_id']) ? (int)$_GET['school_type_id'] : 0;

        $whereConditions = ["s.is_active = 1"];
        $params = [$langId];
        $types = "i";

        if ($cityId > 0) {
            $whereConditions[] = "s.city_id = ?";
            $params[] = $cityId;
            $types .= "i";
        }

        if ($schoolTypeId > 0) {
            $whereConditions[] = "s.school_type_id = ?";
            $params[] = $schoolTypeId;
            $types .= "i";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(s.code LIKE ? OR st.name LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ss";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $countSql = "
            SELECT COUNT(*) AS total
            FROM schools s
            LEFT JOIN schools_translations st ON s.id = st.school_id AND st.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT s.id, s.city_id, s.school_type_id, s.code, COALESCE(st.name, s.code) AS name, s.is_active, s.created_at
            FROM schools s
            LEFT JOIN schools_translations st ON s.id = st.school_id AND st.language_id = ?
            {$whereClause}
            ORDER BY st.name ASC, s.id ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $schools = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['city_id'] = $row['city_id'] ? (int)$row['city_id'] : null;
            $row['school_type_id'] = $row['school_type_id'] ? (int)$row['school_type_id'] : null;
            $row['is_active'] = (int)$row['is_active'];
            $schools[] = $row;
        }

        return build_paginated_response($schools, $page, $limit, $totalRecords);
    }
}
