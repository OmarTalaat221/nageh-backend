<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/JWT.php';

if (!function_exists('listPublicBanners')) {
    function listPublicBanners($con) {
        $langInfo = get_language_info($con);
        $langCode = strtolower($langInfo['code'] ?? 'ar');

        $sql = "
            SELECT b.id, b.title_ar, b.title_en, b.image_ar, b.image_en, b.link, b.type, b.created_at,
                   (SELECT COUNT(*) FROM banner_views bv WHERE bv.banner_id = b.id) AS views_count
            FROM banners b
            WHERE b.is_active = 1
            ORDER BY b.id DESC
        ";

        $res = mysqli_query($con, $sql);
        $banners = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $bId = (int)$row['id'];
            $viewsCount = (int)$row['views_count'];

            if ($langCode === 'en' && !empty($row['title_en'])) {
                $title = $row['title_en'];
            } else {
                $title = $row['title_ar'] ?: ($row['title_en'] ?? '');
            }

            if ($langCode === 'en' && !empty($row['image_en'])) {
                $image = $row['image_en'];
            } else {
                $image = $row['image_ar'];
            }

            $banners[] = [
                'id' => $bId,
                'title' => $title,
                'image' => format_response_images($image),
                'link' => $row['link'],
                'type' => $row['type'],
                'views_count' => $viewsCount,
                'created_at' => $row['created_at']
            ];
        }

        return [
            'status' => 'success',
            'data' => $banners
        ];
    }
}

if (!function_exists('trackBannerView')) {
    function trackBannerView($con) {
        $body = read_json_body();
        $bannerId = (int)($body['banner_id'] ?? 0);

        if ($bannerId <= 0) {
            return ['status' => 'error', 'message' => 'banner_id is required'];
        }

        // Check if banner exists
        $checkStmt = mysqli_prepare($con, "SELECT id FROM banners WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $bannerId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'Banner not found'];
        }

        $userId = isset($body['user_id']) ? (int)$body['user_id'] : null;
        $userType = isset($body['user_type']) ? strtolower(trim($body['user_type'])) : null;

        // Auto-detect user from Authorization JWT token if not provided
        if ($userId === null || empty($userType)) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
                $token = $matches[1];
                $decoded = verifyStudentAccessToken($token) 
                        ?: verifyTeacherAccessToken($token) 
                        ?: verifyParentAccessToken($token) 
                        ?: verifyAdminAccessToken($token);

                if (is_array($decoded)) {
                    if ($userId === null) {
                        $userId = (int)($decoded['id'] ?? 0) ?: null;
                    }
                    if (empty($userType)) {
                        $userType = strtolower($decoded['role'] ?? $decoded['admin_role'] ?? 'user');
                    }
                }
            }
        }

        if (empty($userType)) {
            $userType = 'guest';
        }

        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ipAddress && str_contains($ipAddress, ',')) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt = mysqli_prepare($con, "
            INSERT INTO banner_views (banner_id, user_id, user_type, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare view tracking: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "iisss", $bannerId, $userId, $userType, $ipAddress, $userAgent);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to record banner view: ' . mysqli_error($con)];
        }

        $viewId = mysqli_insert_id($con);

        return [
            'status' => 'success',
            'message' => 'Banner view recorded successfully',
            'data' => [
                'view_id' => (int)$viewId,
                'banner_id' => $bannerId,
                'user_id' => $userId,
                'user_type' => $userType
            ]
        ];
    }
}
