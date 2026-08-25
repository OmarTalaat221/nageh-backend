<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('listAcademicYears')) {
    function listAcademicYears($con) {
        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "WHERE ay.is_active = 1";
        $params = [$langId];
        $types = "i";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause .= " AND (ay.code LIKE ? OR ayt.name LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ss";
        }

        $countSql = "
            SELECT COUNT(*) AS total
            FROM academic_years ay
            LEFT JOIN academic_years_translations ayt ON ay.id = ayt.academic_year_id AND ayt.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT ay.id, ay.code, ay.number, COALESCE(ayt.name, ay.code) AS name, ay.is_active
            FROM academic_years ay
            LEFT JOIN academic_years_translations ayt ON ay.id = ayt.academic_year_id AND ayt.language_id = ?
            {$whereClause}
            ORDER BY ay.number ASC, ay.id ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $years = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['number'] = $row['number'] ? (int)$row['number'] : null;
            $row['is_active'] = (int)$row['is_active'];
            $years[] = $row;
        }

        return build_paginated_response($years, $page, $limit, $totalRecords);
    }
}
