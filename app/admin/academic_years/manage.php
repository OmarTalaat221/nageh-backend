<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('adminListAcademicYears')) {
    function adminListAcademicYears($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (ay.code LIKE ? OR ayt.name LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM academic_years");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT ay.id) AS total FROM academic_years ay LEFT JOIN academic_years_translations ayt ON ay.id = ayt.academic_year_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT DISTINCT ay.id, ay.code, ay.number, ay.is_active, ay.created_at FROM academic_years ay LEFT JOIN academic_years_translations ayt ON ay.id = ayt.academic_year_id {$whereClause} ORDER BY ay.number ASC, ay.id ASC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $years = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ayId = (int)$row['id'];
            $row['id'] = $ayId;
            $row['number'] = $row['number'] ? (int)$row['number'] : null;
            $row['is_active'] = (int)$row['is_active'];

            $transStmt = mysqli_prepare($con, "SELECT ayt.language_id, l.code AS language_code, ayt.name FROM academic_years_translations ayt JOIN languages l ON ayt.language_id = l.id WHERE ayt.academic_year_id = ?");
            mysqli_stmt_bind_param($transStmt, "i", $ayId);
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

            $years[] = $row;
        }

        return build_admin_paginated_response($years, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetAcademicYear')) {
    function adminGetAcademicYear($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Academic year ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, number, is_active, created_at FROM academic_years WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'Academic year not found'];
        }

        $ayId = (int)$row['id'];
        $row['id'] = $ayId;
        $row['number'] = $row['number'] ? (int)$row['number'] : null;
        $row['is_active'] = (int)$row['is_active'];

        $transStmt = mysqli_prepare($con, "SELECT ayt.language_id, l.code AS language_code, ayt.name FROM academic_years_translations ayt JOIN languages l ON ayt.language_id = l.id WHERE ayt.academic_year_id = ?");
        mysqli_stmt_bind_param($transStmt, "i", $ayId);
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

if (!function_exists('adminCreateAcademicYear')) {
    function adminCreateAcademicYear($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        // The English name falls back to the Arabic one rather than being demanded. A grade named
        // only in Arabic is a real grade, and blocking it over a second label helps nobody.
        $nameEn = trim($body['name_en'] ?? '') !== '' ? trim($body['name_en']) : $nameAr;
        $number = isset($body['number']) && $body['number'] !== '' ? (int)$body['number'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($nameAr)) {
            return ['status' => 'error', 'message' => trans('academic_year_name_required')];
        }

        // `code` is NOT NULL UNIQUE, so it cannot simply be left empty now that the form no
        // longer asks for it - it is derived instead. Telling an admin "code is required" about
        // a box that is not on the screen is the worst of both.
        if ($code === '') {
            $code = generate_entity_code($con, 'academic_years', $nameEn, 'grade');
        }

        $stmt = mysqli_prepare($con, "INSERT INTO academic_years (code, number, is_active) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sii", $code, $number, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create academic year: ' . mysqli_error($con)];
        }

        $ayId = mysqli_insert_id($con);

        $transStmt = mysqli_prepare($con, "INSERT INTO academic_years_translations (academic_year_id, language_id, name) VALUES (?, 1, ?), (?, 2, ?)");
        mysqli_stmt_bind_param($transStmt, "isis", $ayId, $nameAr, $ayId, $nameEn);
        mysqli_stmt_execute($transStmt);

        return [
            'status' => 'success',
            'message' => 'Academic year created successfully',
            'data' => adminGetAcademicYear($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateAcademicYear')) {
    function adminUpdateAcademicYear($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? '');
        $number = isset($body['number']) ? (int)$body['number'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Academic year ID is required'];
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE academic_years SET code = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($number !== null) {
            $stmt = mysqli_prepare($con, "UPDATE academic_years SET number = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $number, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE academic_years SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($nameAr)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO academic_years_translations (academic_year_id, language_id, name) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameAr);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($nameEn)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO academic_years_translations (academic_year_id, language_id, name) VALUES (?, 2, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameEn);
            mysqli_stmt_execute($transStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Academic year updated successfully',
            'data' => adminGetAcademicYear($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteAcademicYear')) {
    function adminDeleteAcademicYear($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Academic year ID is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM academic_years WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete academic year: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Academic year deleted successfully'];
    }
}
