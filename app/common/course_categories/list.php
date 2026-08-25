<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('listCourseCategories')) {
    function listCourseCategories($con) {
        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "WHERE cc.is_active = 1";
        $params = [$langId];
        $types = "i";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause .= " AND (cc.code LIKE ? OR cct.name LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ss";
        }

        $countSql = "
            SELECT COUNT(*) AS total
            FROM course_categories cc
            LEFT JOIN course_categories_translations cct ON cc.id = cct.course_category_id AND cct.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT cc.id, cc.code, cc.image, COALESCE(cct.name, cc.code) AS name, cc.is_active
            FROM course_categories cc
            LEFT JOIN course_categories_translations cct ON cc.id = cct.course_category_id AND cct.language_id = ?
            {$whereClause}
            ORDER BY cct.name ASC, cc.id ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $categories = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['is_active'] = (int)$row['is_active'];
            $categories[] = $row;
        }

        return build_paginated_response($categories, $page, $limit, $totalRecords);
    }
}
