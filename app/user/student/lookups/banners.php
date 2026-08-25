<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/upload.php';

if (!function_exists('studentListBanners')) {
    function studentListBanners($con) {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $type = strtolower(trim($_GET['type'] ?? 'all'));

        $whereConditions = ["b.is_active = 1"];
        if ($type !== 'all' && !empty($type)) {
            $spType = mysqli_real_escape_string($con, $type);
            $whereConditions[] = "b.type = '{$spType}'";
        }
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $sql = "
            SELECT b.id, b.title_ar, b.title_en, b.type, b.image_ar, b.image_en, b.link, b.created_at,
                   (SELECT COUNT(*) FROM banner_views bv WHERE bv.banner_id = b.id) AS views_count
            FROM banners b
            {$whereClause}
            ORDER BY b.id DESC
        ";
        $res = mysqli_query($con, $sql);
        $banners = [];

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['views_count'] = (int)$row['views_count'];
                $row['title'] = ($langInfo['code'] === 'en' && !empty($row['title_en'])) ? $row['title_en'] : $row['title_ar'];
                $row['image'] = ($langInfo['code'] === 'en' && !empty($row['image_en'])) ? $row['image_en'] : $row['image_ar'];
                $row['url'] = $row['link'];
                $banners[] = format_response_images($row);
            }
        }

        return [
            'status' => 'success',
            'data' => $banners
        ];
    }
}

if (!function_exists('studentTrackBannerView')) {
    function studentTrackBannerView($con) {
        $body = read_json_body();
        $id = (int)($body['banner_id'] ?? $body['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'banner_id is required'];
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        $userType = strtolower(trim($body['user_type'] ?? 'guest'));
        if (!in_array($userType, ['student', 'teacher', 'parent', 'guest'])) {
            $userType = 'guest';
        }

        $stmt = mysqli_prepare($con, "INSERT INTO banner_views (banner_id, user_type, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare view tracking statement: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "isss", $id, $userType, $ipAddress, $userAgent);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to track banner view: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Banner view tracked successfully'];
    }
}
