<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';

if (!function_exists('studentListSubjects')) {
    function studentListSubjects($con) {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
        $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;

        $whereConditions = ["s.is_active = 1"];
        $params = [$langId];
        $types = "i";

        if ($academicYearId > 0) {
            $whereConditions[] = "EXISTS (SELECT 1 FROM subject_academic_years say WHERE say.subject_id = s.id AND say.academic_year_id = ?)";
            $params[] = $academicYearId;
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
            FROM subjects s
            LEFT JOIN subjects_translations st ON s.id = st.subject_id AND st.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT s.id, s.code, COALESCE(st.name, s.code) AS name, s.is_active, s.created_at
            FROM subjects s
            LEFT JOIN subjects_translations st ON s.id = st.subject_id AND st.language_id = ?
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

        $subjects = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['is_active'] = (int)$row['is_active'];
            $subjects[] = $row;
        }

        return build_paginated_response($subjects, $page, $limit, $totalRecords);
    }
}
