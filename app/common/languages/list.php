<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('listLanguages')) {
    function listLanguages($con) {
        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "WHERE l.is_active = 1";
        $params = [$langId];
        $types = "i";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause .= " AND (l.code LIKE ? OR lt.name LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ss";
        }

        $countSql = "
            SELECT COUNT(*) AS total
            FROM languages l
            LEFT JOIN languages_translations lt ON l.id = lt.translated_language_id AND lt.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT l.id, l.code, l.direction, l.is_default, COALESCE(lt.name, l.code) AS name, l.is_active
            FROM languages l
            LEFT JOIN languages_translations lt ON l.id = lt.translated_language_id AND lt.language_id = ?
            {$whereClause}
            ORDER BY l.sort_order ASC, l.id ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $languages = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['is_default'] = (int)$row['is_default'];
            $row['is_active'] = (int)$row['is_active'];
            $languages[] = $row;
        }

        return build_paginated_response($languages, $page, $limit, $totalRecords);
    }
}
