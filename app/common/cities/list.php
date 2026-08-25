<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('listCities')) {
    function listCities($con) {
        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "WHERE c.is_active = 1";
        $params = [$langId];
        $types = "i";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause .= " AND (c.code LIKE ? OR ct.name LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ss";
        }

        // Count total
        $countSql = "
            SELECT COUNT(*) AS total
            FROM cities c
            LEFT JOIN cities_translations ct ON c.id = ct.city_id AND ct.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        // Fetch paginated
        $sql = "
            SELECT c.id, c.code, COALESCE(ct.name, c.code) AS name, c.is_active, c.created_at
            FROM cities c
            LEFT JOIN cities_translations ct ON c.id = ct.city_id AND ct.language_id = ?
            {$whereClause}
            ORDER BY ct.name ASC, c.id ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $cities = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['is_active'] = (int)$row['is_active'];
            $cities[] = $row;
        }

        return build_paginated_response($cities, $page, $limit, $totalRecords);
    }
}
