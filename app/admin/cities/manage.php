<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('adminListCities')) {
    function adminListCities($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM cities");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        $stmt = mysqli_prepare($con, "SELECT id, code, is_active, created_at, updated_at FROM cities ORDER BY id DESC LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $cities = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $cityId = (int)$row['id'];
            $row['id'] = $cityId;
            $row['is_active'] = (int)$row['is_active'];
            $row['translations'] = get_city_translations_all($con, $cityId);

            foreach ($row['translations'] as $t) {
                if ($t['language_code'] === 'ar') $row['name_ar'] = $t['name'];
                if ($t['language_code'] === 'en') $row['name_en'] = $t['name'];
            }

            $cities[] = $row;
        }

        return build_admin_paginated_response($cities, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetCity')) {
    function adminGetCity($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'City ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, is_active, created_at, updated_at FROM cities WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'City not found'];
        }

        $cityId = (int)$row['id'];
        $row['id'] = $cityId;
        $row['is_active'] = (int)$row['is_active'];
        $row['translations'] = get_city_translations_all($con, $cityId);

        foreach ($row['translations'] as $t) {
            if ($t['language_code'] === 'ar') $row['name_ar'] = $t['name'];
            if ($t['language_code'] === 'en') $row['name_en'] = $t['name'];
        }

        return ['status' => 'success', 'data' => $row];
    }
}

if (!function_exists('adminCreateCity')) {
    function adminCreateCity($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($code) || empty($nameAr)) {
            return ['status' => 'error', 'message' => 'City code and name_ar are required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO cities (code, is_active) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $code, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create city: ' . mysqli_error($con)];
        }

        $cityId = mysqli_insert_id($con);

        $transStmt = mysqli_prepare($con, "INSERT INTO cities_translations (city_id, language_id, name) VALUES (?, 1, ?), (?, 2, ?)");
        mysqli_stmt_bind_param($transStmt, "isis", $cityId, $nameAr, $cityId, $nameEn);
        mysqli_stmt_execute($transStmt);

        return [
            'status' => 'success',
            'message' => 'City created successfully',
            'data' => [
                'id' => (int)$cityId,
                'code' => $code,
                'is_active' => $isActive,
                'translations' => get_city_translations_all($con, $cityId)
            ]
        ];
    }
}

if (!function_exists('adminUpdateCity')) {
    function adminUpdateCity($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'City ID is required'];
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE cities SET code = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE cities SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($nameAr)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO cities_translations (city_id, language_id, name) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameAr);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($nameEn)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO cities_translations (city_id, language_id, name) VALUES (?, 2, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameEn);
            mysqli_stmt_execute($transStmt);
        }

        return [
            'status' => 'success',
            'message' => 'City updated successfully',
            'data' => adminGetCity($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteCity')) {
    function adminDeleteCity($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'City ID is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM cities WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete city: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'City deleted successfully'];
    }
}
