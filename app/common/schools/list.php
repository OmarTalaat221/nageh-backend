<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('listSchools')) {
    function listSchools($con) {
        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
        $cityId = isset($_GET['city_id']) ? (int)$_GET['city_id'] : 0;
        $typeId = isset($_GET['school_type_id']) ? (int)$_GET['school_type_id'] : 0;

        $whereClause = "WHERE s.is_active = 1";
        $params = [$langId];
        $types = "i";

        if ($cityId > 0) {
            $whereClause .= " AND s.city_id = ?";
            $params[] = $cityId;
            $types .= "i";
        }
        if ($typeId > 0) {
            $whereClause .= " AND s.school_type_id = ?";
            $params[] = $typeId;
            $types .= "i";
        }
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause .= " AND (s.code LIKE ? OR st.name LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ss";
        }

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
            SELECT s.id, s.code, COALESCE(st.name, s.code) AS name, s.school_type_id, s.city_id, s.is_active
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
            $cId = $row['city_id'] ? (int)$row['city_id'] : null;
            $stId = $row['school_type_id'] ? (int)$row['school_type_id'] : null;
            unset($row['city_id'], $row['school_type_id']);

            $row['id'] = (int)$row['id'];
            $row['is_active'] = (int)$row['is_active'];
            $row['city'] = get_city_details($con, $cId);
            $row['school_type'] = get_school_type_details($con, $stId);

            $schools[] = $row;
        }

        return build_paginated_response($schools, $page, $limit, $totalRecords);
    }
}
