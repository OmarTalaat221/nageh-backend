<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('adminListBanners')) {
    function adminListBanners($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
        $typeFilter = strtolower(trim($_GET['type'] ?? ''));
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;

        $whereConditions = [];
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(b.title_ar LIKE ? OR b.title_en LIKE ? OR b.link LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        if (in_array($typeFilter, ['image', 'video'])) {
            $whereConditions[] = "b.type = ?";
            $params[] = $typeFilter;
            $types .= "s";
        }

        if ($activeFilter !== null) {
            $whereConditions[] = "b.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count,
                SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END) AS image_count,
                SUM(CASE WHEN type = 'video' THEN 1 ELSE 0 END) AS video_count
            FROM banners
        ");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);

        $viewsStatRes = mysqli_query($con, "SELECT COUNT(*) AS total_views FROM banner_views");
        $viewsStatRow = mysqli_fetch_assoc($viewsStatRes);
        $totalViews = (int)($viewsStatRow['total_views'] ?? 0);

        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0),
            'image_banners_count' => (int)($statRow['image_count'] ?? 0),
            'video_banners_count' => (int)($statRow['video_count'] ?? 0),
            'total_views_count' => $totalViews
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(*) AS total FROM banners b {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            if (!empty($types)) {
                mysqli_stmt_bind_param($countStmt, $types, ...$params);
            }
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "
            SELECT b.id, b.title_ar, b.title_en, b.image_ar, b.image_en, b.link, b.type, b.is_active, b.created_at, b.updated_at,
                   (SELECT COUNT(*) FROM banner_views bv WHERE bv.banner_id = b.id) AS views_count
            FROM banners b
            {$whereClause}
            ORDER BY b.id DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $banners = [];
        $hasLangHeader = has_request_lang_header();
        $langInfo = get_language_info($con);

        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['is_active'] = (int)$row['is_active'];
            $row['views_count'] = (int)$row['views_count'];
            $row['translations'] = [
                ['language_id' => 1, 'language_code' => 'ar', 'title' => $row['title_ar'], 'image' => format_response_images($row['image_ar'])],
                ['language_id' => 2, 'language_code' => 'en', 'title' => $row['title_en'], 'image' => format_response_images($row['image_en'])]
            ];
            if ($hasLangHeader) {
                if ($langInfo['code'] === 'ar') {
                    $row['title'] = $row['title_ar'];
                    $row['image'] = $row['image_ar'];
                } else {
                    $row['title'] = $row['title_en'];
                    $row['image'] = $row['image_en'];
                }
            }
            $banners[] = format_response_images($row);
        }

        return build_admin_paginated_response($banners, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetBanner')) {
    function adminGetBanner($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Banner ID is required'];
        }

        $stmt = mysqli_prepare($con, "
            SELECT b.id, b.title_ar, b.title_en, b.image_ar, b.image_en, b.link, b.type, b.is_active, b.created_at, b.updated_at,
                   (SELECT COUNT(*) FROM banner_views bv WHERE bv.banner_id = b.id) AS views_count
            FROM banners b
            WHERE b.id = ? LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $banner = mysqli_fetch_assoc($res);

        if (!$banner) {
            return ['status' => 'error', 'message' => 'Banner not found'];
        }

        $banner['id'] = (int)$banner['id'];
        $banner['is_active'] = (int)$banner['is_active'];
        $banner['views_count'] = (int)$banner['views_count'];
        $banner['translations'] = [
            ['language_id' => 1, 'language_code' => 'ar', 'title' => $banner['title_ar'], 'image' => format_response_images($banner['image_ar'])],
            ['language_id' => 2, 'language_code' => 'en', 'title' => $banner['title_en'], 'image' => format_response_images($banner['image_en'])]
        ];
        if (has_request_lang_header()) {
            $langInfo = get_language_info($con);
            if ($langInfo['code'] === 'ar') {
                $banner['title'] = $banner['title_ar'];
                $banner['image'] = $banner['image_ar'];
            } else {
                $banner['title'] = $banner['title_en'];
                $banner['image'] = $banner['image_en'];
            }
        }

        $breakdownStmt = mysqli_prepare($con, "
            SELECT user_type, COUNT(*) AS count
            FROM banner_views
            WHERE banner_id = ?
            GROUP BY user_type
        ");
        mysqli_stmt_bind_param($breakdownStmt, "i", $id);
        mysqli_stmt_execute($breakdownStmt);
        $bdRes = mysqli_stmt_get_result($breakdownStmt);

        $breakdown = [
            'student' => 0,
            'teacher' => 0,
            'parent' => 0,
            'guest' => 0
        ];
        while ($bdRow = mysqli_fetch_assoc($bdRes)) {
            $type = strtolower($bdRow['user_type'] ?? 'guest');
            $breakdown[$type] = (int)$bdRow['count'];
        }

        $banner['views_breakdown'] = $breakdown;

        return ['status' => 'success', 'data' => $banner];
    }
}

if (!function_exists('adminCreateBanner')) {
    function adminCreateBanner($con) {
        $body = read_json_body();

        $titleAr = trim($body['title_ar'] ?? '');
        $titleEn = trim($body['title_en'] ?? '');
        $imageArInput = $body['image_ar'] ?? $body['image'] ?? null;
        $imageEnInput = $body['image_en'] ?? null;
        $link = trim($body['link'] ?? '');
        $type = strtolower(trim($body['type'] ?? 'image'));
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (!in_array($type, ['image', 'video'])) {
            $type = 'image';
        }

        // Process image_ar
        $fileAr = $_FILES['image_ar'] ?? $_FILES['imagn_ar'] ?? $_FILES['image'] ?? $_FILES['file'] ?? $_FILES['image_ar_file'] ?? null;
        $inputAr = $body['image_ar'] ?? $body['imagn_ar'] ?? $body['image'] ?? $body['file'] ?? null;

        $imageAr = process_image_input($inputAr, 'banners', $fileAr);

        if (empty($imageAr)) {
            return ['status' => 'error', 'message' => 'Arabic banner image (image_ar) is required'];
        }

        // Process image_en
        $fileEn = $_FILES['image_en'] ?? $_FILES['imagn_en'] ?? $_FILES['image_english'] ?? $_FILES['image_en_file'] ?? null;
        $inputEn = $body['image_en'] ?? $body['imagn_en'] ?? $body['image_english'] ?? null;

        $imageEn = process_image_input($inputEn, 'banners', $fileEn);

        $stmt = mysqli_prepare($con, "
            INSERT INTO banners (title_ar, title_en, image_ar, image_en, link, type, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare banner insert: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "ssssssi", $titleAr, $titleEn, $imageAr, $imageEn, $link, $type, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create banner: ' . mysqli_error($con)];
        }

        $bannerId = mysqli_insert_id($con);
        $_GET['id'] = $bannerId;

        return [
            'status' => 'success',
            'message' => 'Banner created successfully',
            'data' => adminGetBanner($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateBanner')) {
    function adminUpdateBanner($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Banner ID is required'];
        }

        $titleAr = isset($body['title_ar']) ? trim($body['title_ar']) : null;
        $titleEn = isset($body['title_en']) ? trim($body['title_en']) : null;
        $link = isset($body['link']) ? trim($body['link']) : null;
        $type = isset($body['type']) ? strtolower(trim($body['type'])) : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        // Process image_ar update
        $fileAr = $_FILES['image_ar'] ?? $_FILES['imagn_ar'] ?? $_FILES['image'] ?? $_FILES['file'] ?? $_FILES['image_ar_file'] ?? null;
        $inputAr = $body['image_ar'] ?? $body['imagn_ar'] ?? $body['image'] ?? $body['file'] ?? null;
        $imageAr = process_image_input($inputAr, 'banners', $fileAr);

        // Process image_en update
        $fileEn = $_FILES['image_en'] ?? $_FILES['imagn_en'] ?? $_FILES['image_english'] ?? $_FILES['image_en_file'] ?? null;
        $inputEn = $body['image_en'] ?? $body['imagn_en'] ?? $body['image_english'] ?? null;
        $imageEn = process_image_input($inputEn, 'banners', $fileEn);

        if ($titleAr !== null) {
            $stmt = mysqli_prepare($con, "UPDATE banners SET title_ar = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $titleAr, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($titleEn !== null) {
            $stmt = mysqli_prepare($con, "UPDATE banners SET title_en = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $titleEn, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($imageAr !== null) {
            $stmt = mysqli_prepare($con, "UPDATE banners SET image_ar = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $imageAr, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($imageEn !== null) {
            $stmt = mysqli_prepare($con, "UPDATE banners SET image_en = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $imageEn, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($link !== null) {
            $stmt = mysqli_prepare($con, "UPDATE banners SET link = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $link, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($type !== null && in_array($type, ['image', 'video'])) {
            $stmt = mysqli_prepare($con, "UPDATE banners SET type = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $type, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE banners SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => 'Banner updated successfully',
            'data' => adminGetBanner($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteBanner')) {
    function adminDeleteBanner($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Banner ID is required'];
        }

        // Delete associated views
        $vStmt = mysqli_prepare($con, "DELETE FROM banner_views WHERE banner_id = ?");
        if ($vStmt) {
            mysqli_stmt_bind_param($vStmt, "i", $id);
            mysqli_stmt_execute($vStmt);
        }

        $stmt = mysqli_prepare($con, "DELETE FROM banners WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare delete query: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete banner: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Banner deleted successfully'];
    }
}

if (!function_exists('adminGetBannerViews')) {
    function adminGetBannerViews($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $bannerId = isset($_GET['banner_id']) ? (int)$_GET['banner_id'] : 0;
        $userType = strtolower(trim($_GET['user_type'] ?? ''));

        $whereConditions = [];
        $params = [];
        $types = "";

        if ($bannerId > 0) {
            $whereConditions[] = "bv.banner_id = ?";
            $params[] = $bannerId;
            $types .= "i";
        }

        if (!empty($userType)) {
            $whereConditions[] = "bv.user_type = ?";
            $params[] = $userType;
            $types .= "s";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $countSql = "SELECT COUNT(*) AS total FROM banner_views bv {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT bv.id, bv.banner_id, bv.user_id, bv.user_type, bv.ip_address, bv.user_agent, bv.created_at,
                   b.title_ar AS banner_title_ar, b.title_en AS banner_title_en, b.type AS banner_type
            FROM banner_views bv
            JOIN banners b ON bv.banner_id = b.id
            {$whereClause}
            ORDER BY bv.id DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $views = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['banner_id'] = (int)$row['banner_id'];
            $row['user_id'] = $row['user_id'] ? (int)$row['user_id'] : null;
            $views[] = $row;
        }

        return build_admin_paginated_response($views, $page, $limit, $totalRecords);
    }
}
