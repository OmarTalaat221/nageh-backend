<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('adminListLanguages')) {
    function adminListLanguages($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (l.code LIKE ? OR lt.name LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM languages");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT l.id) AS total FROM languages l LEFT JOIN languages_translations lt ON l.id = lt.translated_language_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT DISTINCT l.id, l.code, l.direction, l.is_default, l.sort_order, l.is_active, l.created_at FROM languages l LEFT JOIN languages_translations lt ON l.id = lt.translated_language_id {$whereClause} ORDER BY l.sort_order ASC, l.id ASC LIMIT ? OFFSET ?";
        
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
            $row['sort_order'] = (int)$row['sort_order'];
            $row['is_active'] = (int)$row['is_active'];
            $languages[] = $row;
        }

        return build_admin_paginated_response($languages, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetLanguage')) {
    function adminGetLanguage($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Language ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, direction, is_default, sort_order, is_active, created_at FROM languages WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'Language not found'];
        }

        $row['id'] = (int)$row['id'];
        $row['is_default'] = (int)$row['is_default'];
        $row['sort_order'] = (int)$row['sort_order'];
        $row['is_active'] = (int)$row['is_active'];

        return ['status' => 'success', 'data' => $row];
    }
}

if (!function_exists('adminCreateLanguage')) {
    function adminCreateLanguage($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $direction = strtolower(trim($body['direction'] ?? 'ltr'));
        $isDefault = isset($body['is_default']) ? (int)$body['is_default'] : 0;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $name = trim($body['name'] ?? $code);

        if (empty($code)) {
            return ['status' => 'error', 'message' => 'Language code is required'];
        }

        if (!in_array($direction, ['rtl', 'ltr'])) {
            $direction = 'ltr';
        }

        if ($isDefault === 1) {
            mysqli_query($con, "UPDATE languages SET is_default = 0");
        }

        $stmt = mysqli_prepare($con, "INSERT INTO languages (code, direction, is_default, is_active) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssii", $code, $direction, $isDefault, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create language: ' . mysqli_error($con)];
        }

        $langId = mysqli_insert_id($con);

        $transStmt = mysqli_prepare($con, "INSERT INTO languages_translations (translated_language_id, language_id, name) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($transStmt, "iis", $langId, $langId, $name);
        mysqli_stmt_execute($transStmt);

        return [
            'status' => 'success',
            'message' => 'Language created successfully',
            'data' => adminGetLanguage($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateLanguage')) {
    function adminUpdateLanguage($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $code = strtolower(trim($body['code'] ?? ''));
        $direction = strtolower(trim($body['direction'] ?? ''));
        $isDefault = isset($body['is_default']) ? (int)$body['is_default'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Language ID is required'];
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE languages SET code = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if (in_array($direction, ['rtl', 'ltr'])) {
            $stmt = mysqli_prepare($con, "UPDATE languages SET direction = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $direction, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isDefault === 1) {
            mysqli_query($con, "UPDATE languages SET is_default = 0");
            $stmt = mysqli_prepare($con, "UPDATE languages SET is_default = 1, is_active = 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
        } elseif ($isDefault === 0) {
            $stmt = mysqli_prepare($con, "UPDATE languages SET is_default = 0 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isActive !== null && $isDefault !== 1) {
            $stmt = mysqli_prepare($con, "UPDATE languages SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        return [
            'status' => 'success',
            'message' => 'Language updated successfully',
            'data' => adminGetLanguage($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteLanguage')) {
    function adminDeleteLanguage($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Language ID is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM languages WHERE id = ? AND is_default = 0");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete language or language is default: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Language deleted successfully'];
    }
}
