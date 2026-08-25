<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../content/helpers.php';
require_once __DIR__ . '/../subscriptions/manage.php';
require_once __DIR__ . '/questions.php';

if (!function_exists('studentTableExists')) {
    function studentTableExists($con, $tableName) {
        static $cache = [];
        $cacheKey = spl_object_id($con) . ':' . $tableName;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }
        $safeTableName = mysqli_real_escape_string($con, $tableName);
        $result = mysqli_query($con, "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$safeTableName}' LIMIT 1");
        $cache[$cacheKey] = $result && mysqli_num_rows($result) > 0;
        return $cache[$cacheKey];
    }
}

if (!function_exists('studentGetVideoDetails')) {
    function studentGetVideoDetails($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $body = read_json_body();
        $videoId = (int)($_GET['id'] ?? $_GET['video_id'] ?? $body['id'] ?? $body['video_id'] ?? 0);
        if ($videoId <= 0) {
            return ['status' => 'error', 'message' => 'video_id or id is required'];
        }

        $langInfo = get_student_request_lang_info($con);
        $langId = (int)($langInfo['id'] ?? 2);

        $sql = "
            SELECT 
                v.id, v.unit_id, v.code, v.duration_seconds, v.publish_status, v.is_active, v.created_at,
                COALESCE(cv.video_url, '') AS video_url, COALESCE(cv.course_id, u.course_id) AS course_id, COALESCE(cv.subject_id, u.subject_id) AS subject_id,
                COALESCE(vt.name, cvt.title, v.code) AS title, COALESCE(vt.description, cvt.description, '') AS description,
                u.code AS unit_code, COALESCE(ut.name, u.code) AS unit_name, u.is_active AS unit_is_active,
                c.slug AS course_slug, COALESCE(ct.name, c.slug) AS course_name, c.is_active AS course_is_active, c.teacher_id AS course_teacher_id,
                t.id AS teacher_id, t.name AS teacher_name, t.image AS teacher_image, t.specialization AS teacher_specialization, t.is_active AS teacher_is_active,
                s.id AS sub_id, COALESCE(st_tr.name, s.code) AS subject_name
            FROM videos v
            LEFT JOIN course_videos cv ON v.id = cv.id
            LEFT JOIN videos_translations vt ON v.id = vt.video_id AND vt.language_id = {$langId}
            LEFT JOIN course_videos_translations cvt ON cv.id = cvt.video_id AND cvt.language_id = {$langId}
            LEFT JOIN units u ON v.unit_id = u.id
            LEFT JOIN units_translations ut ON u.id = ut.unit_id AND ut.language_id = {$langId}
            LEFT JOIN courses c ON u.course_id = c.id
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN subjects s ON u.subject_id = s.id
            LEFT JOIN subjects_translations st_tr ON s.id = st_tr.subject_id AND st_tr.language_id = {$langId}
            WHERE v.id = {$videoId} AND v.is_active = 1
            LIMIT 1
        ";
        $res = mysqli_query($con, $sql);
        $vRow = mysqli_fetch_assoc($res);
        if (!$vRow) {
            return ['status' => 'error', 'message' => 'Video not found or inactive'];
        }

        if ((int)($vRow['unit_is_active'] ?? 1) !== 1) {
            return ['status' => 'error', 'message' => 'Unit associated with this video is inactive'];
        }
        if ((int)($vRow['course_is_active'] ?? 1) !== 1) {
            return ['status' => 'error', 'message' => 'Course associated with this video is inactive'];
        }

        $cId = (int)$vRow['course_id'];
        $isSubscribed = false;
        if ($stId > 0 && function_exists('get_student_accessible_entities')) {
            $accessible = get_student_accessible_entities($con, $stId);
            $isSubscribed = in_array($cId, $accessible['course_ids']);
        }

        $firstVideoId = 0;
        $uId = (int)$vRow['unit_id'];
        if ($uId > 0) {
            $firstVRes = mysqli_query($con, "SELECT id FROM videos WHERE unit_id = {$uId} AND is_active = 1 ORDER BY id ASC LIMIT 1");
            if ($firstVRes && $fvRow = mysqli_fetch_assoc($firstVRes)) {
                $firstVideoId = (int)$fvRow['id'];
            }
        }
        $isFreePreview = false;
        $isLocked = !$isSubscribed;

        $progress = [
            'is_completed' => 0,
            'watched_seconds' => 0,
            'total_seconds' => (int)($vRow['duration_seconds'] ?? 0),
            'last_position_seconds' => 0,
            'completed_at' => null
        ];

        if ($stId > 0 && studentTableExists($con, 'student_video_progress')) {
            $vpRes = mysqli_query($con, "SELECT is_completed, watched_seconds, total_seconds, last_position_seconds, completed_at FROM student_video_progress WHERE student_id = {$stId} AND video_id = {$videoId} LIMIT 1");
            if ($vpRes && $vpRow = mysqli_fetch_assoc($vpRes)) {
                $progress = [
                    'is_completed' => (int)($vpRow['is_completed'] ?? 0),
                    'watched_seconds' => (int)($vpRow['watched_seconds'] ?? 0),
                    'total_seconds' => (int)($vpRow['total_seconds'] ?? $progress['total_seconds']),
                    'last_position_seconds' => (int)($vpRow['last_position_seconds'] ?? 0),
                    'completed_at' => $vpRow['completed_at']
                ];
            }
        }

        $data = [
            'id' => (int)$vRow['id'],
            'code' => $vRow['code'],
            'title' => $vRow['title'],
            'description' => $vRow['description'],
            'video_url' => $isLocked ? null : $vRow['video_url'],
            'duration_seconds' => (int)($vRow['duration_seconds'] ?? 0),
            'publish_status' => $vRow['publish_status'],
            'is_free_preview' => false,
            'is_locked' => $isLocked,
            'is_open' => !$isLocked,
            'is_subscribed' => $isSubscribed,
            'unit' => [
                'id' => (int)$vRow['unit_id'],
                'code' => $vRow['unit_code'],
                'name' => $vRow['unit_name']
            ],
            'course' => [
                'id' => $cId,
                'slug' => $vRow['course_slug'],
                'name' => $vRow['course_name']
            ],
            'teacher' => $vRow['teacher_id'] ? format_response_images([
                'id' => (int)$vRow['teacher_id'],
                'name' => $vRow['teacher_name'],
                'image' => $vRow['teacher_image'],
                'specialization' => $vRow['teacher_specialization']
            ]) : null,
            'subject' => $vRow['sub_id'] ? [
                'id' => (int)$vRow['sub_id'],
                'name' => $vRow['subject_name']
            ] : null,
            'progress' => $progress
        ];

        return [
            'status' => 'success',
            'data' => format_response_images($data)
        ];
    }
}

if (!function_exists('studentUpdateVideoProgress')) {
    function studentUpdateVideoProgress($con, $studentData = null) {
        $body = read_json_body();
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($body['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

        $videoId = (int)($body['video_id'] ?? 0);
        if ($videoId <= 0) {
            return ['status' => 'error', 'message' => 'video_id is required'];
        }

        $vStmt = mysqli_prepare($con, "SELECT id, duration_seconds AS duration, unit_id FROM videos WHERE id = ? LIMIT 1");
        if (!$vStmt) {
            return ['status' => 'error', 'message' => 'Query preparation error'];
        }
        mysqli_stmt_bind_param($vStmt, "i", $videoId);
        mysqli_stmt_execute($vStmt);
        $vRow = mysqli_fetch_assoc(mysqli_stmt_get_result($vStmt));
        if (!$vRow) {
            return ['status' => 'error', 'message' => 'Video not found'];
        }

        $unitId = (int)$vRow['unit_id'];
        $uStmt = mysqli_prepare($con, "SELECT course_id FROM units WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($uStmt, "i", $unitId);
        mysqli_stmt_execute($uStmt);
        $uRow = mysqli_fetch_assoc(mysqli_stmt_get_result($uStmt));
        $courseId = $uRow ? (int)$uRow['course_id'] : 0;

        $accessible = get_student_accessible_entities($con, $stId);
        if (!in_array($courseId, $accessible['course_ids'])) {
            return ['status' => 'error', 'message' => 'You are not subscribed to the course containing this video'];
        }

        $watchedSeconds = max(0, (int)($body['watched_seconds'] ?? 0));
        $totalSeconds = max(0, (int)($body['total_seconds'] ?? $vRow['duration'] ?? 0));
        $lastPosSeconds = max(0, (int)($body['last_position_seconds'] ?? $watchedSeconds));
        $isCompleted = isset($body['is_completed']) ? (int)$body['is_completed'] : 0;

        if ($totalSeconds > 0 && $watchedSeconds >= ($totalSeconds * 0.9)) {
            $isCompleted = 1;
        }

        $completedAt = $isCompleted === 1 ? date('Y-m-d H:i:s') : null;

        $stmt = mysqli_prepare($con, "
            INSERT INTO student_video_progress (student_id, video_id, is_completed, watched_seconds, total_seconds, last_position_seconds, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                is_completed = IF(is_completed = 1, 1, VALUES(is_completed)),
                watched_seconds = GREATEST(watched_seconds, VALUES(watched_seconds)),
                total_seconds = VALUES(total_seconds),
                last_position_seconds = VALUES(last_position_seconds),
                completed_at = IF(is_completed = 1 AND completed_at IS NOT NULL, completed_at, VALUES(completed_at))
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare progress update statement: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "iiiiiis", $stId, $videoId, $isCompleted, $watchedSeconds, $totalSeconds, $lastPosSeconds, $completedAt);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update video progress: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => 'Video progress updated successfully',
            'data' => [
                'student_id' => $stId,
                'video_id' => $videoId,
                'is_completed' => (bool)$isCompleted,
                'watched_seconds' => $watchedSeconds,
                'total_seconds' => $totalSeconds,
                'last_position_seconds' => $lastPosSeconds
            ]
        ];
    }
}
