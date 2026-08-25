<?php
require_once __DIR__ . '/../helpers.php';

if (!function_exists('get_video_details')) {
    function get_video_details($con, $videoId) {
        if (!$videoId) return null;
        ensure_content_tables_schema($con);
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, unit_id, code, duration_seconds, publish_status, is_active, created_at, updated_at FROM videos WHERE id = ? LIMIT 1");
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

        $row['teachers'] = get_content_teachers_details($con, 'video', $videoId);
        $row['teacher_ids'] = array_values(array_map(function($t) { return (int)$t['id']; }, $row['teachers']));

        $tStmt = mysqli_prepare($con, "SELECT vt.language_id, l.code AS language_code, vt.title, vt.description FROM videos_translations vt JOIN languages l ON vt.language_id = l.id WHERE vt.video_id = ?");
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

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['title_ar'] = $titleAr;
            $row['title_en'] = $titleEn;
            $row['description_ar'] = $descAr;
            $row['description_en'] = $descEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('adminListVideos')) {
    function adminListVideos($con) {
        ensure_content_tables_schema($con);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $unitIds = parse_int_list_param($_GET['unit_ids'] ?? $_GET['unit_id'] ?? $_GET['units'] ?? null);
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $sortBy = strtolower(trim($_GET['sort_by'] ?? $_GET['sort'] ?? 'id'));
        $sortDir = strtoupper(trim($_GET['dir'] ?? $_GET['direction'] ?? $_GET['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $orderCol = ($sortBy === 'code') ? 'v.code' : 'v.id';

        $whereConditions = [];
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

        if ($activeFilter !== null) {
            $whereConditions[] = "v.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(v.code LIKE ? OR vt.title LIKE ? OR vt.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM videos");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT v.id) AS total FROM videos v LEFT JOIN videos_translations vt ON v.id = vt.video_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            if ($countStmt) {
                if (!empty($types)) {
                    mysqli_stmt_bind_param($countStmt, $types, ...$params);
                }
                mysqli_stmt_execute($countStmt);
                $countRes = mysqli_stmt_get_result($countStmt);
                $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
            }
        }

        $sql = "
            SELECT DISTINCT v.id
            FROM videos v
            LEFT JOIN videos_translations vt ON v.id = vt.video_id
            {$whereClause}
            ORDER BY {$orderCol} {$sortDir}, v.id ASC
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
            $detail = get_video_details($con, $vId);
            if ($detail) {
                $videos[] = $detail;
            }
        }

        return build_admin_paginated_response($videos, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetVideo')) {
    function adminGetVideo($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Video ID is required'];
        }
        $video = get_video_details($con, $id);
        if (!$video) {
            return ['status' => 'error', 'message' => 'Video not found'];
        }
        return ['status' => 'success', 'data' => $video];
    }
}

if (!function_exists('adminCreateVideo')) {
    function adminCreateVideo($con, $creatorData = null) {
        ensure_content_tables_schema($con);
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
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($code)) {
            return ['status' => 'error', 'message' => 'Video code is required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO videos (unit_id, code, duration_seconds, publish_status, is_active) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare video insert: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "isisi", $unitId, $code, $durationSeconds, $publishStatus, $isActive);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create video: ' . mysqli_error($con)];
        }

        $videoId = mysqli_insert_id($con);

        if (!empty($titleAr) || !empty($descAr)) {
            @mysqli_query($con, "INSERT INTO videos_translations (video_id, language_id, title, description) VALUES ({$videoId}, 1, '" . mysqli_real_escape_string($con, $titleAr) . "', '" . mysqli_real_escape_string($con, $descAr) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
        }
        if (!empty($titleEn) || !empty($descEn)) {
            @mysqli_query($con, "INSERT INTO videos_translations (video_id, language_id, title, description) VALUES ({$videoId}, 2, '" . mysqli_real_escape_string($con, $titleEn) . "', '" . mysqli_real_escape_string($con, $descEn) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
        }

        $teacherIds = $body['teacher_ids'] ?? [];
        if (!is_array($teacherIds)) {
            $teacherIds = is_numeric($teacherIds) ? [(int)$teacherIds] : [];
        }
        sync_content_teachers($con, 'video', $videoId, $teacherIds);

        return [
            'status' => 'success',
            'message' => 'Video created successfully',
            'data' => get_video_details($con, $videoId)
        ];
    }
}

if (!function_exists('adminUpdateVideo')) {
    function adminUpdateVideo($con, $creatorData = null) {
        ensure_content_tables_schema($con);
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Video ID is required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, unit_id FROM videos WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$existing) {
            return ['status' => 'error', 'message' => 'Video not found'];
        }

        $unitId = isset($body['unit_id']) ? (int)$body['unit_id'] : (int)$existing['unit_id'];
        $code = isset($body['code']) ? trim($body['code']) : null;
        $durationSeconds = isset($body['duration_seconds']) ? (int)$body['duration_seconds'] : (isset($body['duration']) ? (int)$body['duration'] : null);
        $publishStatus = isset($body['publish_status']) ? strtolower(trim($body['publish_status'])) : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        $updateFields = ["unit_id = ?"];
        $params = [$unitId];
        $types = "i";

        if ($code !== null) {
            $updateFields[] = "code = ?";
            $params[] = $code;
            $types .= "s";
        }
        if ($durationSeconds !== null) {
            $updateFields[] = "duration_seconds = ?";
            $params[] = $durationSeconds;
            $types .= "i";
        }
        if ($publishStatus !== null) {
            $updateFields[] = "publish_status = ?";
            $params[] = $publishStatus;
            $types .= "s";
        }
        if ($isActive !== null) {
            $updateFields[] = "is_active = ?";
            $params[] = $isActive;
            $types .= "i";
        }

        $params[] = $id;
        $types .= "i";

        $uSql = "UPDATE videos SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $uStmt = mysqli_prepare($con, $uSql);
        mysqli_stmt_bind_param($uStmt, $types, ...$params);
        mysqli_stmt_execute($uStmt);

        if (isset($body['title_ar']) || isset($body['title_en']) || isset($body['title']) || isset($body['description_ar']) || isset($body['description_en'])) {
            $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
            $titleEn = trim($body['title_en'] ?? $titleAr);
            $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
            $descEn = trim($body['description_en'] ?? $descAr);

            if (!empty($titleAr)) {
                @mysqli_query($con, "INSERT INTO videos_translations (video_id, language_id, title, description) VALUES ({$id}, 1, '" . mysqli_real_escape_string($con, $titleAr) . "', '" . mysqli_real_escape_string($con, $descAr) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
            }
            if (!empty($titleEn)) {
                @mysqli_query($con, "INSERT INTO videos_translations (video_id, language_id, title, description) VALUES ({$id}, 2, '" . mysqli_real_escape_string($con, $titleEn) . "', '" . mysqli_real_escape_string($con, $descEn) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
            }
        }

        if (isset($body['teacher_ids'])) {
            sync_content_teachers($con, 'video', $id, $body['teacher_ids']);
        }

        return [
            'status' => 'success',
            'message' => 'Video updated successfully',
            'data' => get_video_details($con, $id)
        ];
    }
}

if (!function_exists('adminDeleteVideo')) {
    function adminDeleteVideo($con, $creatorData = null) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Video ID is required'];
        }
        $stmt = mysqli_prepare($con, "DELETE FROM videos WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            @mysqli_query($con, "DELETE FROM videos_translations WHERE video_id = {$id}");
            @mysqli_query($con, "DELETE FROM video_teachers WHERE video_id = {$id}");
            return ['status' => 'success', 'message' => 'Video deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete video'];
    }
}
