<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('adminListCourseCategories')) {
    function adminListCourseCategories($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (cc.code LIKE ? OR cct.name LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM course_categories");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT cc.id) AS total FROM course_categories cc LEFT JOIN course_categories_translations cct ON cc.id = cct.course_category_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT DISTINCT cc.id, cc.code, cc.image, cc.is_active, cc.created_at FROM course_categories cc LEFT JOIN course_categories_translations cct ON cc.id = cct.course_category_id {$whereClause} ORDER BY cc.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $categories = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $catId = (int)$row['id'];
            $row['id'] = $catId;
            $row['is_active'] = (int)$row['is_active'];

            $transStmt = mysqli_prepare($con, "SELECT cct.language_id, l.code AS language_code, cct.name FROM course_categories_translations cct JOIN languages l ON cct.language_id = l.id WHERE cct.course_category_id = ?");
            mysqli_stmt_bind_param($transStmt, "i", $catId);
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

            $categories[] = $row;
        }

        return build_admin_paginated_response($categories, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetCourseCategory')) {
    function adminGetCourseCategory($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Course category ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, image, is_active, created_at FROM course_categories WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'Course category not found'];
        }

        $catId = (int)$row['id'];
        $row['id'] = $catId;
        $row['is_active'] = (int)$row['is_active'];

        $transStmt = mysqli_prepare($con, "SELECT cct.language_id, l.code AS language_code, cct.name FROM course_categories_translations cct JOIN languages l ON cct.language_id = l.id WHERE cct.course_category_id = ?");
        mysqli_stmt_bind_param($transStmt, "i", $catId);
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

if (!function_exists('adminCreateCourseCategory')) {
    function adminCreateCourseCategory($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $image = trim($body['image'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($code) || empty($nameAr)) {
            return ['status' => 'error', 'message' => 'Course category code and name_ar are required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO course_categories (code, image, is_active) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssi", $code, $image, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create course category: ' . mysqli_error($con)];
        }

        $catId = mysqli_insert_id($con);

        $transStmt = mysqli_prepare($con, "INSERT INTO course_categories_translations (course_category_id, language_id, name) VALUES (?, 1, ?), (?, 2, ?)");
        mysqli_stmt_bind_param($transStmt, "isis", $catId, $nameAr, $catId, $nameEn);
        mysqli_stmt_execute($transStmt);

        return [
            'status' => 'success',
            'message' => 'Course category created successfully',
            'data' => adminGetCourseCategory($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateCourseCategory')) {
    function adminUpdateCourseCategory($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? '');
        $image = trim($body['image'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Course category ID is required'];
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE course_categories SET code = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($image)) {
            $stmt = mysqli_prepare($con, "UPDATE course_categories SET image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $image, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE course_categories SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($nameAr)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO course_categories_translations (course_category_id, language_id, name) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameAr);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($nameEn)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO course_categories_translations (course_category_id, language_id, name) VALUES (?, 2, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            mysqli_stmt_bind_param($transStmt, "is", $id, $nameEn);
            mysqli_stmt_execute($transStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Course category updated successfully',
            'data' => adminGetCourseCategory($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteCourseCategory')) {
    function adminDeleteCourseCategory($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Course category ID is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM course_categories WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete course category: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Course category deleted successfully'];
    }
}
