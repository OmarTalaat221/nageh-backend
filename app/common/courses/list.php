<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../admin/courses/manage.php';

if (!function_exists('listPublicCourses')) {
    function listPublicCourses($con) {
        ensure_courses_schema($con);
        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $body = read_json_body();
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
        $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $typeFilter = strtolower(trim($_GET['type'] ?? $_GET['course_type'] ?? $_GET['type_id'] ?? $body['type'] ?? $body['course_type'] ?? ''));

        $whereConditions = ["c.is_active = 1"];
        $params = [$langId];
        $types = "i";

        $joinCt = "";
        if ($teacherId > 0) {
            $joinCt = "LEFT JOIN course_teachers ct ON c.id = ct.course_id";
            $whereConditions[] = "(c.teacher_id = ? OR (ct.teacher_id = ? AND ct.is_active = 1) OR (c.created_by_type = 'teacher' AND c.created_by_id = ?) OR (c.created_by_type = 'admin' AND c.teacher_id IS NULL AND NOT EXISTS (SELECT 1 FROM course_teachers ct_chk WHERE ct_chk.course_id = c.id AND ct_chk.is_active = 1)))";
            $params[] = $teacherId;
            $params[] = $teacherId;
            $params[] = $teacherId;
            $types .= "iii";
        }

        if ($subjectId > 0) {
            $whereConditions[] = "c.subject_id = ?";
            $params[] = $subjectId;
            $types .= "i";
        }

        if ($academicYearId > 0) {
            $whereConditions[] = "c.academic_year_id = ?";
            $params[] = $academicYearId;
            $types .= "i";
        }

        if ($categoryId > 0) {
            $whereConditions[] = "c.category_id = ?";
            $params[] = $categoryId;
            $types .= "i";
        }

        if (!empty($typeFilter)) {
            $whereConditions[] = "c.type = ?";
            $params[] = $typeFilter;
            $types .= "s";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(c.slug LIKE ? OR ct_trans.name LIKE ? OR ct_trans.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $countSql = "
            SELECT COUNT(DISTINCT c.id) AS total
            FROM courses c
            {$joinCt}
            LEFT JOIN courses_translations ct_trans ON c.id = ct_trans.course_id AND ct_trans.language_id = ?
            {$whereClause}
        ";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT DISTINCT c.id, c.subject_id, c.category_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.price, c.image, c.lessons_count, c.is_active, c.created_by_type, c.created_by_id, c.created_at,
                   COALESCE(ct_trans.name, c.slug) AS name, ct_trans.description
            FROM courses c
            {$joinCt}
            LEFT JOIN courses_translations ct_trans ON c.id = ct_trans.course_id AND ct_trans.language_id = ?
            {$whereClause}
            ORDER BY c.id DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $courses = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $cId = (int)$row['id'];
            $sId = $row['subject_id'] ? (int)$row['subject_id'] : null;
            $catId = $row['category_id'] ? (int)$row['category_id'] : null;
            $ayId = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;

            $row['id'] = $cId;
            $row['subject_id'] = $sId;
            $row['category_id'] = $catId;
            $row['academic_year_id'] = $ayId;
            $row['type'] = $row['type'] ?? 'recorded';
            $row['price'] = (float)$row['price'];
            $row['lessons_count'] = (int)$row['lessons_count'];
            $row['created_by_type'] = $row['created_by_type'] ?: 'admin';
            $row['created_by_id'] = $row['created_by_id'] ? (int)$row['created_by_id'] : null;
            $row['created_by'] = get_course_created_by_details($con, $row['created_by_type'], $row['created_by_id']);

            $row['subject'] = get_subject_details($con, $sId);
            $row['category'] = get_school_type_details($con, $catId);
            $row['academic_year'] = get_academic_year_details($con, $ayId) ?: (get_subject_academic_years_details($con, $sId)[0] ?? null);

            $row['teachers'] = get_course_teachers_details($con, $cId);
            $row['teacher_ids'] = array_values(array_map(function($t) { return (int)$t['id']; }, $row['teachers']));

            $courses[] = $row;
        }

        return build_admin_paginated_response(format_response_images($courses), $page, $limit, $totalRecords);
    }
}

if (!function_exists('getPublicCourseDetail')) {
    function getPublicCourseDetail($con) {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $id = (int)($_GET['id'] ?? $_GET['course_id'] ?? 0);
        $slug = trim($_GET['slug'] ?? '');

        if ($id <= 0 && empty($slug)) {
            return ['status' => 'error', 'message' => 'Course id or slug is required'];
        }

        $whereCond = $id > 0 ? "c.id = {$id}" : "c.slug = '" . mysqli_real_escape_string($con, $slug) . "'";

        $sql = "
            SELECT c.id, c.subject_id, c.category_id, c.academic_year_id, c.teacher_id, c.slug, c.price, c.image, c.video_preview_url, c.is_active, c.created_at, c.what_you_will_learn AS c_learn,
                   COALESCE(ct_trans.name, c.slug) AS name, ct_trans.description, ct_trans.what_you_will_learn AS ct_learn,
                   t.id AS t_id, t.name AS teacher_name, t.specialization AS teacher_specialization, t.image AS teacher_image, t.rating AS teacher_rating, t.experience AS teacher_experience,
                   sub_t.name AS subject_name, ay_t.name AS academic_year_name
            FROM courses c
            LEFT JOIN courses_translations ct_trans ON c.id = ct_trans.course_id AND ct_trans.language_id = {$langId}
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN subjects_translations sub_t ON s.id = sub_t.subject_id AND sub_t.language_id = {$langId}
            LEFT JOIN academic_years ay ON c.academic_year_id = ay.id
            LEFT JOIN academic_years_translations ay_t ON ay.id = ay_t.academic_year_id AND ay_t.language_id = {$langId}
            WHERE {$whereCond} AND c.is_active = 1 LIMIT 1
        ";

        $res = mysqli_query($con, $sql);
        $row = mysqli_fetch_assoc($res);
        if (!$row) {
            return ['status' => 'error', 'message' => 'Course not found'];
        }

        $cId = (int)$row['id'];
        $price = (float)$row['price'];

        $uRes = mysqli_query($con, "SELECT u.id, u.code, u.is_active FROM units u WHERE u.course_id = {$cId} AND u.is_active = 1 ORDER BY u.id ASC");
        $curriculum = [];
        $totalLessons = 0;
        $totalSecondsSum = 0;

        if ($uRes) {
            while ($uRow = mysqli_fetch_assoc($uRes)) {
                $unitId = (int)$uRow['id'];

                $uName = '';
                $uDesc = '';
                $utRes = @mysqli_query($con, "SELECT * FROM units_translations WHERE unit_id = {$unitId} AND language_id = {$langId} LIMIT 1");
                if ($utRes && $utRow = mysqli_fetch_assoc($utRes)) {
                    $uName = $utRow['name'] ?? $utRow['title'] ?? '';
                    $uDesc = $utRow['description'] ?? '';
                }
                if (empty($uName)) {
                    $uName = $uRow['code'] ?? ('الوحدة ' . $unitId);
                }

                $vRes = mysqli_query($con, "SELECT * FROM videos WHERE unit_id = {$unitId} AND is_active = 1 ORDER BY id ASC");
                $lessons = [];

                if ($vRes && mysqli_num_rows($vRes) > 0) {
                    while ($vRow = mysqli_fetch_assoc($vRes)) {
                        $vId = (int)$vRow['id'];
                        $vCode = !empty($vRow['code']) ? $vRow['code'] : ('video-' . $vId);
                        $durSec = (int)($vRow['duration_seconds'] ?? $vRow['duration'] ?? 0);
                        $totalSecondsSum += $durSec;
                        $totalLessons++;

                        $minutes = floor($durSec / 60);
                        $seconds = $durSec % 60;
                        $formattedDuration = sprintf("%02d:%02d", $minutes, $seconds);

                        $vTitle = '';
                        $vDesc = '';
                        $vtRes = @mysqli_query($con, "SELECT * FROM videos_translations WHERE video_id = {$vId} AND language_id = {$langId} LIMIT 1");
                        if ($vtRes && $vtRow = mysqli_fetch_assoc($vtRes)) {
                            $vTitle = $vtRow['title'] ?? $vtRow['name'] ?? '';
                            $vDesc = $vtRow['description'] ?? '';
                        }
                        if (empty($vTitle)) {
                            $vTitle = $vCode;
                        }

                        $lessons[] = [
                            'id' => $vId,
                            'code' => $vCode,
                            'title' => $vTitle,
                            'description' => $vDesc,
                            'duration' => $formattedDuration,
                            'duration_seconds' => $durSec,
                            'publish_status' => $vRow['publish_status'] ?? 'published'
                        ];
                    }
                }

                $curriculum[] = [
                    'id' => $unitId,
                    'name' => $uName,
                    'description' => $uDesc,
                    'lessons_count' => count($lessons),
                    'lessons' => $lessons
                ];
            }
        }

        $totalHours = round($totalSecondsSum / 3600, 1);

        $previewVideoUrl = null;
        if (!empty($row['video_preview_url'])) {
            $previewVideoUrl = format_response_images(['url' => $row['video_preview_url']])['url'];
        }

        $responseData = [
            'id' => $cId,
            'slug' => $row['slug'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'price' => $price,
            'image' => format_response_images(['img' => $row['image']])['img'],
            'video_preview_url' => $previewVideoUrl,
            'lessons_count' => $totalLessons,
            'total_duration_hours' => $totalHours,
            'curriculum' => $curriculum
        ];

        return [
            'status' => 'success',
            'data' => format_response_images($responseData)
        ];
    }
}
