<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('ensure_courses_schema')) {
    function ensure_courses_schema($con): void {
    }
}

if (!function_exists('sync_course_teachers')) {
    function sync_course_teachers($con, int $courseId, $teacherIds): void {
        ensure_courses_schema($con);
        if ($courseId <= 0) return;

        if (!is_array($teacherIds)) {
            if (is_numeric($teacherIds)) {
                $teacherIds = [(int)$teacherIds];
            } elseif (is_string($teacherIds) && str_contains($teacherIds, ',')) {
                $teacherIds = array_map('intval', explode(',', $teacherIds));
            } else {
                $teacherIds = [];
            }
        }

        $uniqueTeacherIds = array_unique(array_filter(array_map('intval', $teacherIds)));

        $delStmt = mysqli_prepare($con, "DELETE FROM course_teachers WHERE course_id = ?");
        if ($delStmt) {
            mysqli_stmt_bind_param($delStmt, "i", $courseId);
            mysqli_stmt_execute($delStmt);
        }

        $insertStmt = mysqli_prepare($con, "INSERT INTO course_teachers (course_id, teacher_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        foreach ($uniqueTeacherIds as $tId) {
            if ($tId > 0 && $insertStmt) {
                mysqli_stmt_bind_param($insertStmt, "ii", $courseId, $tId);
                mysqli_stmt_execute($insertStmt);
            }
        }

        if (!empty($uniqueTeacherIds)) {
            $primaryTeacherId = reset($uniqueTeacherIds);
            $uStmt = mysqli_prepare($con, "UPDATE courses SET teacher_id = ? WHERE id = ?");
            if ($uStmt) {
                mysqli_stmt_bind_param($uStmt, "ii", $primaryTeacherId, $courseId);
                mysqli_stmt_execute($uStmt);
            }
        }
    }
}

if (!function_exists('parse_int_list_param')) {
    function parse_int_list_param($input): array {
        if (empty($input)) return [];
        if (is_array($input)) {
            return array_values(array_filter(array_map('intval', $input)));
        }
        if (is_numeric($input)) {
            $val = (int)$input;
            return $val > 0 ? [$val] : [];
        }
        if (is_string($input) && str_contains($input, ',')) {
            return array_values(array_filter(array_map('intval', explode(',', $input))));
        }
        $val = (int)$input;
        return $val > 0 ? [$val] : [];
    }
}

if (!function_exists('adminListCourses')) {
    function adminListCourses($con) {
        ensure_courses_schema($con);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $whereConditions = [];
        $params = [];
        $types = "";
        $joinCt = "";

        $subjectIds = parse_int_list_param($_GET['subject_ids'] ?? $_GET['subject_id'] ?? $_GET['subjects'] ?? null);
        if (!empty($subjectIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereConditions[] = "c.subject_id IN ({$inPlaceholders})";
            foreach ($subjectIds as $sId) {
                $params[] = $sId;
                $types .= "i";
            }
        }

        $filterCourseIds = parse_int_list_param($_GET['course_ids'] ?? null);
        if (!empty($filterCourseIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($filterCourseIds), '?'));
            $whereConditions[] = "c.id IN ({$inPlaceholders})";
            foreach ($filterCourseIds as $cIdFilter) {
                $params[] = $cIdFilter;
                $types .= "i";
            }
        }

        $ayIds = parse_int_list_param($_GET['academic_year_ids'] ?? $_GET['academic_year_id'] ?? $_GET['academic_years'] ?? null);
        if (!empty($ayIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($ayIds), '?'));
            $whereConditions[] = "c.academic_year_id IN ({$inPlaceholders})";
            foreach ($ayIds as $ayId) {
                $params[] = $ayId;
                $types .= "i";
            }
        }

        $catIds = parse_int_list_param($_GET['category_ids'] ?? $_GET['category_id'] ?? $_GET['categories'] ?? null);
        if (!empty($catIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($catIds), '?'));
            $whereConditions[] = "c.category_id IN ({$inPlaceholders})";
            foreach ($catIds as $cId) {
                $params[] = $cId;
                $types .= "i";
            }
        }

        $teacherIds = parse_int_list_param($_GET['teacher_ids'] ?? $_GET['teacher_id'] ?? $_GET['teachers'] ?? null);
        if (!empty($teacherIds)) {
            $joinCt = "LEFT JOIN course_teachers ct ON c.id = ct.course_id";
            $inPlaceholders = implode(',', array_fill(0, count($teacherIds), '?'));
             $whereConditions[] = "(c.teacher_id IN ({$inPlaceholders}) OR (ct.teacher_id IN ({$inPlaceholders}) AND ct.is_active = 1) OR (c.created_by_type = 'teacher' AND c.created_by_id IN ({$inPlaceholders})) OR (c.teacher_id IS NULL AND NOT EXISTS (SELECT 1 FROM course_teachers ct_chk WHERE ct_chk.course_id = c.id AND ct_chk.is_active = 1) AND c.subject_id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id IN ({$inPlaceholders}))))";
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
        }

        $createdByType = strtolower(trim($_GET['created_by_type'] ?? $_GET['creator_type'] ?? ''));
        if (in_array($createdByType, ['admin', 'teacher'])) {
            $whereConditions[] = "c.created_by_type = ?";
            $params[] = $createdByType;
            $types .= "s";
        }

        $createdById = isset($_GET['created_by_id']) ? (int)$_GET['created_by_id'] : (isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : 0);
        if ($createdById > 0) {
            $whereConditions[] = "c.created_by_id = ?";
            $params[] = $createdById;
            $types .= "i";
        }

        if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
            $whereConditions[] = "c.is_active = ?";
            $params[] = (int)$_GET['is_active'];
            $types .= "i";
        }

        if (isset($_GET['is_free']) && $_GET['is_free'] !== '') {
            $isFree = (int)$_GET['is_free'];
            if ($isFree === 1) {
                $whereConditions[] = "c.price = 0";
            } else {
                $whereConditions[] = "c.price > 0";
            }
        }

        if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
            $whereConditions[] = "c.price >= ?";
            $params[] = (float)$_GET['min_price'];
            $types .= "d";
        }

        if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
            $whereConditions[] = "c.price <= ?";
            $params[] = (float)$_GET['max_price'];
            $types .= "d";
        }

        $minLessons = isset($_GET['min_lessons']) ? (int)$_GET['min_lessons'] : (isset($_GET['min_lessons_count']) ? (int)$_GET['min_lessons_count'] : null);
        if ($minLessons !== null && $minLessons >= 0) {
            $whereConditions[] = "c.lessons_count >= ?";
            $params[] = $minLessons;
            $types .= "i";
        }

        $maxLessons = isset($_GET['max_lessons']) ? (int)$_GET['max_lessons'] : (isset($_GET['max_lessons_count']) ? (int)$_GET['max_lessons_count'] : null);
        if ($maxLessons !== null && $maxLessons >= 0) {
            $whereConditions[] = "c.lessons_count <= ?";
            $params[] = $maxLessons;
            $types .= "i";
        }

        $slugFilter = trim($_GET['slug'] ?? '');
        if (!empty($slugFilter)) {
            $whereConditions[] = "c.slug = ?";
            $params[] = $slugFilter;
            $types .= "s";
        }

        $body = read_json_body();
        $typeFilter = strtolower(trim($_GET['type'] ?? $_GET['course_type'] ?? $_GET['type_id'] ?? $body['type'] ?? $body['course_type'] ?? ''));
        if (!empty($typeFilter)) {
            $whereConditions[] = "c.type = ?";
            $params[] = $typeFilter;
            $types .= "s";
        }

        $kindFilter = strtolower(trim($_GET['kind'] ?? $body['kind'] ?? ''));
        if (in_array($kindFilter, ['normal', 'package'], true)) {
            $whereConditions[] = "c.kind = ?";
            $params[] = $kindFilter;
            $types .= "s";
        }

        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? $_GET['name'] ?? '');
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(c.slug LIKE ? OR ct_trans.name LIKE ? OR ct_trans.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $sortBy = strtolower(trim($_GET['sort_by'] ?? $_GET['order_by'] ?? $_GET['sort'] ?? 'id'));
        $sortDir = strtoupper(trim($_GET['dir'] ?? $_GET['order'] ?? $_GET['sort_dir'] ?? 'DESC'));
        if (!in_array($sortDir, ['ASC', 'DESC'])) {
            $sortDir = 'DESC';
        }

        switch ($sortBy) {
            case 'price_asc':
            case 'price_low':
                $orderBy = "c.price ASC, c.id DESC";
                break;
            case 'price_desc':
            case 'price_high':
                $orderBy = "c.price DESC, c.id DESC";
                break;
            case 'name':
            case 'title':
                $orderBy = "ct_trans.name {$sortDir}, c.id DESC";
                break;
            case 'lessons':
            case 'lessons_count':
                $orderBy = "c.lessons_count {$sortDir}, c.id DESC";
                break;
            case 'oldest':
            case 'created_at_asc':
                $orderBy = "c.id ASC";
                break;
            case 'newest':
            case 'created_at_desc':
            case 'id':
            default:
                $orderBy = "c.id {$sortDir}";
                break;
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM courses");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT c.id) AS total FROM courses c {$joinCt} LEFT JOIN courses_translations ct_trans ON c.id = ct_trans.course_id {$whereClause}";
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
            SELECT DISTINCT c.id, c.subject_id, c.category_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.kind, c.price, c.image, c.lessons_count, c.is_active, c.created_by_type, c.created_by_id, c.created_at, c.updated_at, c.video_preview_url
            FROM courses c
            {$joinCt}
            LEFT JOIN courses_translations ct_trans ON c.id = ct_trans.course_id
            {$whereClause}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            $sqlFallback = "
                SELECT DISTINCT c.id, c.category_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.kind, c.price, c.image, c.lessons_count, c.is_active, c.created_at, c.updated_at
                FROM courses c
                {$joinCt}
                LEFT JOIN courses_translations ct_trans ON c.id = ct_trans.course_id
                {$whereClause}
                ORDER BY c.id DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = mysqli_prepare($con, $sqlFallback);
        }

        $res = null; 
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
        }

        $courses = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $cId = (int)$row['id'];
                $sId = isset($row['subject_id']) && $row['subject_id'] ? (int)$row['subject_id'] : null;
                $catId = $row['category_id'] ? (int)$row['category_id'] : null;
                $ayId = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;

                $row['id'] = $cId;
                if (count($teacherIds) === 1) {
                    $row['can_edit'] = ($row['created_by_type'] ?? 'admin') !== 'admin';
                }
                $row['type'] = $row['type'] ?? 'english';
                $row['kind'] = $row['kind'] ?? 'normal';
                $row['intro_video'] = $row['video_preview_url'] ?? null;
                $row['subject_id'] = $sId;
                $row['category_id'] = $catId;
                $row['academic_year_id'] = $ayId;
                $row['price'] = (float)$row['price'];
                $row['lessons_count'] = (int)$row['lessons_count'];
                $row['is_active'] = (int)$row['is_active'];
                $row['created_by_type'] = $row['created_by_type'] ?? 'admin';
                $row['created_by_id'] = isset($row['created_by_id']) && $row['created_by_id'] ? (int)$row['created_by_id'] : null;
                $row['created_by'] = get_course_created_by_details($con, $row['created_by_type'], $row['created_by_id']);

                $row['subject'] = get_subject_details($con, $sId);
                $row['category'] = get_school_type_details($con, $catId);
                $row['academic_year'] = get_academic_year_details($con, $ayId) ?: (get_subject_academic_years_details($con, $sId)[0] ?? null);

                $transStmt = mysqli_prepare($con, "SELECT ct.language_id, l.code AS language_code, ct.name, ct.description FROM courses_translations ct JOIN languages l ON ct.language_id = l.id WHERE ct.course_id = ?");
                if ($transStmt) {
                    mysqli_stmt_bind_param($transStmt, "i", $cId);
                    mysqli_stmt_execute($transStmt);
                    $tRes = mysqli_stmt_get_result($transStmt);
                    $row['translations'] = [];
                    while ($t = mysqli_fetch_assoc($tRes)) {
                        $t['language_id'] = (int)$t['language_id'];
                        $row['translations'][] = $t;
                        if ($t['language_code'] === 'ar') {
                            $row['name_ar'] = $t['name'];
                            $row['description_ar'] = $t['description'];
                        }
                        if ($t['language_code'] === 'en') {
                            $row['name_en'] = $t['name'];
                            $row['description_en'] = $t['description'];
                        }
                    }
                }

                $row['teachers'] = get_course_teachers_details($con, $cId);
                $row['teacher_ids'] = array_values(array_map(function($t) { return (int)$t['id']; }, $row['teachers']));

                $courses[] = format_response_images($row);
            }
        }

        return build_admin_paginated_response($courses, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetCourse')) {
    function adminGetCourse($con) {
        ensure_courses_schema($con);

        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Course ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, subject_id, category_id, academic_year_id, teacher_id, slug, type, kind, price, image, lessons_count, is_active, created_by_type, created_by_id, created_at, updated_at FROM courses WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $stmt = mysqli_prepare($con, "SELECT id, category_id, academic_year_id, teacher_id, slug, type, kind, price, image, lessons_count, is_active, created_at, updated_at FROM courses WHERE id = ? LIMIT 1");
        }

        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare course statement'];
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'Course not found'];
        }

        $cId = (int)$row['id'];
        $sId = isset($row['subject_id']) && $row['subject_id'] ? (int)$row['subject_id'] : null;
        $catId = $row['category_id'] ? (int)$row['category_id'] : null;
        $ayId = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;

        $row['id'] = $cId;
        $row['type'] = $row['type'] ?? 'recorded';
        $row['kind'] = $row['kind'] ?? 'normal';
        $row['subject_id'] = $sId;
        $row['category_id'] = $catId;
        $row['academic_year_id'] = $ayId;
        $row['price'] = (float)$row['price'];
        $row['lessons_count'] = (int)$row['lessons_count'];
        $row['is_active'] = (int)$row['is_active'];
        $row['created_by_type'] = $row['created_by_type'] ?? 'admin';
        $row['created_by_id'] = isset($row['created_by_id']) && $row['created_by_id'] ? (int)$row['created_by_id'] : null;
        $row['created_by'] = get_course_created_by_details($con, $row['created_by_type'], $row['created_by_id']);

        $row['subject'] = get_subject_details($con, $sId);
        $row['category'] = get_school_type_details($con, $catId);
        $row['academic_year'] = get_academic_year_details($con, $ayId) ?: (get_subject_academic_years_details($con, $sId)[0] ?? null);

        $transStmt = mysqli_prepare($con, "SELECT ct.language_id, l.code AS language_code, ct.name, ct.description FROM courses_translations ct JOIN languages l ON ct.language_id = l.id WHERE ct.course_id = ?");
        if ($transStmt) {
            mysqli_stmt_bind_param($transStmt, "i", $cId);
            mysqli_stmt_execute($transStmt);
            $tRes = mysqli_stmt_get_result($transStmt);
            $row['translations'] = [];
            while ($t = mysqli_fetch_assoc($tRes)) {
                $t['language_id'] = (int)$t['language_id'];
                $row['translations'][] = $t;
                if ($t['language_code'] === 'ar') {
                    $row['name_ar'] = $t['name'];
                    $row['description_ar'] = $t['description'];
                }
                if ($t['language_code'] === 'en') {
                    $row['name_en'] = $t['name'];
                    $row['description_en'] = $t['description'];
                }
            }
        }

        $row['teachers'] = get_course_teachers_details($con, $cId);
        $row['teacher_ids'] = array_values(array_map(function($t) { return (int)$t['id']; }, $row['teachers']));

        return ['status' => 'success', 'data' => format_response_images($row)];
    }
}

if (!function_exists('adminCreateCourse')) {
    function adminCreateCourse($con, $adminData = null) {
        ensure_courses_schema($con);
        $body = read_json_body();
        $adminId = (int)($adminData['id'] ?? 0);
        $nameAr = trim($body['name_ar'] ?? $body['name'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);
        $subjectId = isset($body['subject_id']) ? (int)$body['subject_id'] : null;
        $categoryId = isset($body['category_id']) ? (int)$body['category_id'] : null;
        $academicYearId = isset($body['academic_year_id']) ? (int)$body['academic_year_id'] : null;
        $teacherIds = $body['teacher_ids'] ?? $body['teachers'] ?? $body['teacher_id'] ?? null;
        $price = isset($body['price']) ? (float)$body['price'] : 0.00;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $slug = strtolower(trim($body['slug'] ?? ''));
        $type = isset($body['type']) && trim($body['type']) !== '' ? strtolower(trim($body['type'])) : 'recorded';
        $kind = isset($body['kind']) && strtolower(trim($body['kind'])) === 'package' ? 'package' : 'normal';

        if (empty($nameAr)) {
            return ['status' => 'error', 'message' => 'Course name (name_ar) is required'];
        }

        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($nameEn)) ?: ('course-' . uniqid());
        }

        $imageInput = $body['image'] ?? null;
        $fileImage = $_FILES['image'] ?? $_FILES['file'] ?? null;
        $image = process_image_input($imageInput, 'courses', $fileImage);

        $primaryTeacherId = null;
        if (!empty($teacherIds)) {
            if (is_array($teacherIds)) {
                $primaryTeacherId = (int)reset($teacherIds);
            } elseif (is_numeric($teacherIds)) {
                $primaryTeacherId = (int)$teacherIds;
            }
        }

        $createdByAdminId = $adminId > 0 ? $adminId : null;

        $stmt = mysqli_prepare($con, "
            INSERT INTO courses (subject_id, category_id, academic_year_id, teacher_id, slug, type, kind, price, image, is_active, created_by_type, created_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin', ?)
        ");
        if (!$stmt) {
            $stmt = mysqli_prepare($con, "
                INSERT INTO courses (category_id, academic_year_id, teacher_id, slug, type, price, image, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iiissdsi", $categoryId, $academicYearId, $primaryTeacherId, $slug, $type, $price, $image, $isActive);
            }
        } else {
            mysqli_stmt_bind_param($stmt, "iiiisssdsii", $subjectId, $categoryId, $academicYearId, $primaryTeacherId, $slug, $type, $kind, $price, $image, $isActive, $createdByAdminId);
        }

        if (!$stmt || !mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create course: ' . mysqli_error($con)];
        }

        $courseId = mysqli_insert_id($con);

        // Insert translations
        $tStmt = mysqli_prepare($con, "INSERT INTO courses_translations (course_id, language_id, name, description) VALUES (?, 1, ?, ?), (?, 2, ?, ?)");
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "ississ", $courseId, $nameAr, $descAr, $courseId, $nameEn, $descEn);
            mysqli_stmt_execute($tStmt);
        }

        // Sync teachers
        if ($teacherIds !== null) {
            sync_course_teachers($con, $courseId, $teacherIds);
        }

        $whatYouWillLearn = $body['what_you_will_learn'] ?? null;
        if ($whatYouWillLearn !== null) {
            $wStr = is_array($whatYouWillLearn) ? json_encode($whatYouWillLearn, JSON_UNESCAPED_UNICODE) : trim((string)$whatYouWillLearn);
            @mysqli_query($con, "UPDATE courses SET what_you_will_learn = '" . mysqli_real_escape_string($con, $wStr) . "' WHERE id = {$courseId}");
            @mysqli_query($con, "UPDATE courses_translations SET what_you_will_learn = '" . mysqli_real_escape_string($con, $wStr) . "' WHERE course_id = {$courseId}");
        }

        $videoInput = $body['video_preview_url'] ?? $body['intro_video'] ?? $body['preview_video'] ?? null;
        $fileVideo = $_FILES['video_preview_url'] ?? $_FILES['intro_video'] ?? $_FILES['preview_video'] ?? null;
        $videoPreviewUrl = null;
        if ($fileVideo && isset($fileVideo['tmp_name']) && file_exists($fileVideo['tmp_name'])) {
            $videoPreviewUrl = upload_video_file($fileVideo, 'videos');
        } elseif (is_string($videoInput) && !empty($videoInput)) {
            $videoPreviewUrl = trim($videoInput);
        }

        if ($videoPreviewUrl !== null) {
            @mysqli_query($con, "UPDATE courses SET video_preview_url = '" . mysqli_real_escape_string($con, $videoPreviewUrl) . "' WHERE id = {$courseId}");
        }

        $_GET['id'] = $courseId;
        return [
            'status' => 'success',
            'message' => 'Course created successfully',
            'data' => adminGetCourse($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateCourse')) {
    function adminUpdateCourse($con) {
        ensure_courses_schema($con);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Course ID is required'];
        }

        $nameAr = isset($body['name_ar']) ? trim($body['name_ar']) : null;
        $nameEn = isset($body['name_en']) ? trim($body['name_en']) : null;
        $descAr = isset($body['description_ar']) ? trim($body['description_ar']) : null;
        $descEn = isset($body['description_en']) ? trim($body['description_en']) : null;
        $subjectId = isset($body['subject_id']) ? (int)$body['subject_id'] : null;
        $categoryId = isset($body['category_id']) ? (int)$body['category_id'] : null;
        $academicYearId = isset($body['academic_year_id']) ? (int)$body['academic_year_id'] : null;
        $teacherIds = $body['teacher_ids'] ?? $body['teachers'] ?? $body['teacher_id'] ?? null;
        $price = isset($body['price']) ? (float)$body['price'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;
        $slug = isset($body['slug']) ? strtolower(trim($body['slug'])) : null;
        $type = isset($body['type']) && trim($body['type']) !== '' ? strtolower(trim($body['type'])) : null;
        $kind = isset($body['kind']) && in_array(strtolower(trim($body['kind'])), ['normal', 'package'], true) ? strtolower(trim($body['kind'])) : null;

        $imageInput = $body['image'] ?? null;
        $fileImage = $_FILES['image'] ?? $_FILES['file'] ?? null;
        $image = process_image_input($imageInput, 'courses', $fileImage);

        if ($subjectId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET subject_id = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $subjectId, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($categoryId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET category_id = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $categoryId, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($academicYearId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET academic_year_id = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $academicYearId, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($type !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET type = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $type, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($kind !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET kind = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $kind, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($price !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET price = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "di", $price, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET is_active = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($image !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET image = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $image, $id);
                mysqli_stmt_execute($stmt);
            }
        }
        if ($slug !== null) {
            $stmt = mysqli_prepare($con, "UPDATE courses SET slug = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $slug, $id);
                mysqli_stmt_execute($stmt);
            }
        }

        $whatYouWillLearn = $body['what_you_will_learn'] ?? null;
        if ($whatYouWillLearn !== null) {
            $wStr = is_array($whatYouWillLearn) ? json_encode($whatYouWillLearn, JSON_UNESCAPED_UNICODE) : trim((string)$whatYouWillLearn);
            @mysqli_query($con, "UPDATE courses SET what_you_will_learn = '" . mysqli_real_escape_string($con, $wStr) . "' WHERE id = {$id}");
            @mysqli_query($con, "UPDATE courses_translations SET what_you_will_learn = '" . mysqli_real_escape_string($con, $wStr) . "' WHERE course_id = {$id}");
        }

        $videoInput = $body['video_preview_url'] ?? $body['intro_video'] ?? $body['preview_video'] ?? null;
        $fileVideo = $_FILES['video_preview_url'] ?? $_FILES['intro_video'] ?? $_FILES['preview_video'] ?? null;
        $videoPreviewUrl = null;
        if ($fileVideo && isset($fileVideo['tmp_name']) && file_exists($fileVideo['tmp_name'])) {
            $videoPreviewUrl = upload_video_file($fileVideo, 'videos');
        } elseif (is_string($videoInput) && !empty($videoInput)) {
            $videoPreviewUrl = trim($videoInput);
        }

        if ($videoPreviewUrl !== null) {
            @mysqli_query($con, "UPDATE courses SET video_preview_url = '" . mysqli_real_escape_string($con, $videoPreviewUrl) . "' WHERE id = {$id}");
        }

        if ($nameAr !== null || $descAr !== null) {
            $tStmt = mysqli_prepare($con, "INSERT INTO courses_translations (course_id, language_id, name, description) VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
            if ($tStmt) {
                mysqli_stmt_bind_param($tStmt, "iss", $id, $nameAr, $descAr);
                mysqli_stmt_execute($tStmt);
            }
        }

        if ($nameEn !== null || $descEn !== null) {
            $tStmt = mysqli_prepare($con, "INSERT INTO courses_translations (course_id, language_id, name, description) VALUES (?, 2, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
            if ($tStmt) {
                mysqli_stmt_bind_param($tStmt, "iss", $id, $nameEn, $descEn);
                mysqli_stmt_execute($tStmt);
            }
        }

        if ($teacherIds !== null) {
            sync_course_teachers($con, $id, $teacherIds);
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => 'Course updated successfully',
            'data' => adminGetCourse($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteCourse')) {
    function adminDeleteCourse($con) {
        ensure_courses_schema($con);
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Course ID is required'];
        }

        // Delete teachers link
        $ctStmt = mysqli_prepare($con, "DELETE FROM course_teachers WHERE course_id = ?");
        if ($ctStmt) {
            mysqli_stmt_bind_param($ctStmt, "i", $id);
            mysqli_stmt_execute($ctStmt);
        }

        // Delete translations
        $trStmt = mysqli_prepare($con, "DELETE FROM courses_translations WHERE course_id = ?");
        if ($trStmt) {
            mysqli_stmt_bind_param($trStmt, "i", $id);
            mysqli_stmt_execute($trStmt);
        }

        $stmt = mysqli_prepare($con, "DELETE FROM courses WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare delete query: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete course: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Course deleted successfully'];
    }
}

if (!function_exists('adminLinkTeacherToCourse')) {
    function adminLinkTeacherToCourse($con) {
        ensure_courses_schema($con);
        $body = read_json_body();
        $courseId = (int)($body['course_id'] ?? 0);
        $teacherIds = $body['teacher_ids'] ?? $body['teachers'] ?? $body['teacher_id'] ?? 0;

        if ($courseId <= 0) {
            return ['status' => 'error', 'message' => 'Valid course_id is required'];
        }

        if (!is_array($teacherIds)) {
            if (is_numeric($teacherIds)) {
                $teacherIds = [(int)$teacherIds];
            } elseif (is_string($teacherIds) && str_contains($teacherIds, ',')) {
                $teacherIds = array_map('intval', explode(',', $teacherIds));
            } else {
                $teacherIds = [];
            }
        }

        $teacherIds = array_unique(array_filter(array_map('intval', $teacherIds)));
        if (empty($teacherIds)) {
            return ['status' => 'error', 'message' => 'Valid teacher_id or teacher_ids array is required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO course_teachers (course_id, teacher_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        foreach ($teacherIds as $tId) {
            if ($tId > 0 && $stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $courseId, $tId);
                mysqli_stmt_execute($stmt);
            }
        }

        $_GET['id'] = $courseId;
        return [
            'status' => 'success',
            'message' => 'Teacher(s) linked to course successfully',
            'data' => adminGetCourse($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUnlinkTeacherFromCourse')) {
    function adminUnlinkTeacherFromCourse($con) {
        ensure_courses_schema($con);
        $body = read_json_body();
        $courseId = (int)($body['course_id'] ?? 0);
        $teacherIds = $body['teacher_ids'] ?? $body['teachers'] ?? $body['teacher_id'] ?? 0;

        if ($courseId <= 0) {
            return ['status' => 'error', 'message' => 'Valid course_id is required'];
        }

        if (!is_array($teacherIds)) {
            if (is_numeric($teacherIds)) {
                $teacherIds = [(int)$teacherIds];
            } elseif (is_string($teacherIds) && str_contains($teacherIds, ',')) {
                $teacherIds = array_map('intval', explode(',', $teacherIds));
            } else {
                $teacherIds = [];
            }
        }

        $teacherIds = array_unique(array_filter(array_map('intval', $teacherIds)));
        if (empty($teacherIds)) {
            return ['status' => 'error', 'message' => 'Valid teacher_id or teacher_ids array is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM course_teachers WHERE course_id = ? AND teacher_id = ?");
        foreach ($teacherIds as $tId) {
            if ($tId > 0 && $stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $courseId, $tId);
                mysqli_stmt_execute($stmt);
            }
        }

        $_GET['id'] = $courseId;
        return [
            'status' => 'success',
            'message' => 'Teacher(s) unlinked from course successfully',
            'data' => adminGetCourse($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminToggleCourseWebsiteVisibility')) {
    function adminToggleCourseWebsiteVisibility($con) {
        ensure_courses_schema($con);
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        $showOnWebsite = isset($body['show_on_website']) ? (int)$body['show_on_website'] : (isset($_GET['show_on_website']) ? (int)$_GET['show_on_website'] : null);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Course ID is required'];
        }

        if ($showOnWebsite !== null) {
            $val = $showOnWebsite ? 1 : 0;
            $stmt = mysqli_prepare($con, "UPDATE courses SET show_on_website = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $val, $id);
                mysqli_stmt_execute($stmt);
            }
        } else {
            @mysqli_query($con, "UPDATE courses SET show_on_website = IF(show_on_website = 1, 0, 1) WHERE id = {$id}");
        }

        return ['status' => 'success', 'message' => 'Course website visibility updated successfully'];
    }
}
