<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('listSubjects')) {
    function listSubjects($con) {
        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
        $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;

        $whereClause = "WHERE sub.is_active = 1";
        $params = [$langId];
        $types = "i";

        $joinSay = "";
        if ($academicYearId > 0) {
            $joinSay = "JOIN subject_academic_years say ON sub.id = say.subject_id";
            $whereClause .= " AND say.academic_year_id = ? AND say.is_active = 1";
            $params[] = $academicYearId;
            $types .= "i";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause .= " AND (sub.code LIKE ? OR subt.name LIKE ? OR subt.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $countSql = "
            SELECT COUNT(DISTINCT sub.id) AS total
            FROM subjects sub
            {$joinSay}
            LEFT JOIN subjects_translations subt ON sub.id = subt.subject_id AND subt.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT DISTINCT sub.id, sub.code, COALESCE(subt.name, sub.code) AS name, subt.description, sub.is_active
            FROM subjects sub
            {$joinSay}
            LEFT JOIN subjects_translations subt ON sub.id = subt.subject_id AND subt.language_id = ?
            {$whereClause}
            ORDER BY subt.name ASC, sub.id ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $subjects = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $subId = (int)$row['id'];
            $row['id'] = $subId;
            $row['is_active'] = (int)$row['is_active'];
            $row['academic_years'] = get_subject_academic_years_details($con, $subId);
            $row['academic_year_ids'] = array_values(array_map(function($y) { return (int)$y['id']; }, $row['academic_years']));
            $subjects[] = $row;
        }

        return build_admin_paginated_response($subjects, $page, $limit, $totalRecords);
    }
}
