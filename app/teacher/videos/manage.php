<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/video_source.php';

if (!function_exists('teacherGetVideoDetails')) {
    function teacherGetVideoDetails($con, $videoId) {
        if (!$videoId) return null;
        $langInfo = get_language_info($con);

        $urlCol = video_url_column_exists($con) ? 'video_url' : "'' AS video_url";
        $stmt = mysqli_prepare($con, "SELECT id, unit_id, code, {$urlCol}, duration_seconds, publish_status, is_active, created_at, updated_at FROM videos WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $videoId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['unit_id'] = (int)$row['unit_id'];
        $row['duration_seconds'] = (int)($row['duration_seconds'] ?? 0);
        $row['is_active'] = (int)$row['is_active'];
        $row['video_url'] = ($row['video_url'] ?? '') !== '' ? $row['video_url'] : null;
        $row = video_decorate_source($row);

        // `videos_translations` has `name`. `title` is the column on course_videos_translations,
        // the other table, and naming it here returned nothing at all.
        $tStmt = mysqli_prepare($con, "SELECT vt.language_id, l.code AS language_code, vt.name AS title, vt.description FROM videos_translations vt JOIN languages l ON vt.language_id = l.id WHERE vt.video_id = ?");
        $translations = [];
        $titleAr = null;
        $titleEn = null;
        $descAr = null;
        $descEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $videoId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') {
                    $titleAr = $tRow['title'];
                    $descAr = $tRow['description'];
                }
                if ($tRow['language_code'] === 'en') {
                    $titleEn = $tRow['title'];
                    $descEn = $tRow['description'];
                }
            }
        }

        $transMap = array_column($translations, 'title', 'language_code');
        $descMap = array_column($translations, 'description', 'language_code');
        $row['title'] = $transMap[$langInfo['code']] ?? ($titleAr ?? $titleEn ?? $row['code']);
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        return format_response_images($row);
    }
}

if (!function_exists('teacherListVideos')) {
    function teacherListVideos($con, $teacherData = null) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $unitIds = parse_int_list_param($_GET['unit_ids'] ?? $_GET['unit_id'] ?? $_GET['units'] ?? null);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $whereConditions = ["v.is_active = 1"];
        $params = [];
        $types = "";

        if (!empty($unitIds)) {
            $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
            $whereConditions[] = "v.unit_id IN ({$placeholders})";
            foreach ($unitIds as $uId) {
                $params[] = $uId;
                $types .= "i";
            }
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(v.code LIKE ? OR vt.title LIKE ? OR vt.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $countSql = "SELECT COUNT(DISTINCT v.id) AS total FROM videos v LEFT JOIN videos_translations vt ON v.id = vt.video_id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if ($countStmt && !empty($types)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        if ($countStmt) {
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        } else {
            $totalRecords = 0;
        }

        $sql = "
            SELECT DISTINCT v.id
            FROM videos v
            LEFT JOIN videos_translations vt ON v.id = vt.video_id
            {$whereClause}
            ORDER BY v.id DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $videos = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $vId = (int)$row['id'];
            $detail = teacherGetVideoDetails($con, $vId);
            if ($detail) {
                $videos[] = $detail;
            }
        }

        return build_admin_paginated_response($videos, $page, $limit, $totalRecords);
    }
}

if (!function_exists('teacherGetVideo')) {
    function teacherGetVideo($con, $teacherData = null) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Video ID is required'];
        }
        $video = teacherGetVideoDetails($con, $id);
        if (!$video) {
            return ['status' => 'error', 'message' => 'Video not found'];
        }
        return ['status' => 'success', 'data' => $video];
    }
}

if (!function_exists('teacherCreateVideo')) {
    function teacherCreateVideo($con, $teacherData = null) {
        $body = read_json_body();
        $unitId = (int)($body['unit_id'] ?? 0);

        if ($unitId <= 0) {
            return ['status' => 'error', 'message' => 'unit_id is required'];
        }

        $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
        $titleEn = trim($body['title_en'] ?? $titleAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);

        $code = trim($body['code'] ?? $body['video_code'] ?? ('video-' . uniqid()));
        $durationSeconds = (int)($body['duration_seconds'] ?? $body['duration'] ?? 0);
        $publishStatus = in_array(strtolower($body['publish_status'] ?? ''), ['draft', 'published']) ? strtolower($body['publish_status']) : 'published';
        $isActive = 1;
        $videoUrl = video_normalize_url($body['video_url'] ?? $body['url'] ?? null);

        if (video_url_column_exists($con)) {
            $stmt = mysqli_prepare($con, "INSERT INTO videos (unit_id, code, video_url, duration_seconds, publish_status, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO videos (unit_id, code, duration_seconds, publish_status, is_active) VALUES (?, ?, ?, ?, ?)");
        }
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare video insert: ' . mysqli_error($con)];
        }
        if (video_url_column_exists($con)) {
            mysqli_stmt_bind_param($stmt, "issisi", $unitId, $code, $videoUrl, $durationSeconds, $publishStatus, $isActive);
        } else {
            mysqli_stmt_bind_param($stmt, "isisi", $unitId, $code, $durationSeconds, $publishStatus, $isActive);
        }
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create video: ' . mysqli_error($con)];
        }

        $videoId = mysqli_insert_id($con);

        if (!empty($titleAr) || !empty($descAr)) {
            @mysqli_query($con, "INSERT INTO videos_translations (video_id, language_id, name, description) VALUES ({$videoId}, 1, '" . mysqli_real_escape_string($con, $titleAr) . "', '" . mysqli_real_escape_string($con, $descAr) . "') ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
        }
        if (!empty($titleEn) || !empty($descEn)) {
            @mysqli_query($con, "INSERT INTO videos_translations (video_id, language_id, name, description) VALUES ({$videoId}, 2, '" . mysqli_real_escape_string($con, $titleEn) . "', '" . mysqli_real_escape_string($con, $descEn) . "') ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
        }

        return [
            'status' => 'success',
            'message' => 'Video created successfully',
            'data' => teacherGetVideoDetails($con, $videoId)
        ];
    }
}
