<?php
require_once __DIR__ . '/../helpers.php';

if (!function_exists('get_unit_details')) {
    function get_unit_details($con, $unitId) {
        if (!$unitId) return null;
        ensure_content_tables_schema($con);
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, course_id, subject_id, code, order_num, videos_drip_enabled, is_active, created_by_type, created_by_id, created_at, updated_at FROM units WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $unitId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['course_id'] = (int)$row['course_id'];
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
        $row['order_num'] = (int)($row['order_num'] ?? 0);
        $row['videos_drip_enabled'] = (int)($row['videos_drip_enabled'] ?? 0);
        $row['is_active'] = (int)$row['is_active'];
        $row['created_by_type'] = $row['created_by_type'] ?? 'admin';
        $row['created_by_id'] = isset($row['created_by_id']) && $row['created_by_id'] ? (int)$row['created_by_id'] : null;
        $row['created_by'] = get_course_created_by_details($con, $row['created_by_type'], $row['created_by_id']);

        $row['teachers'] = get_content_teachers_details($con, 'unit', $unitId);
        $row['teacher_ids'] = array_values(array_map(function($t) { return (int)$t['id']; }, $row['teachers']));

        $tStmt = mysqli_prepare($con, "SELECT ut.language_id, l.code AS language_code, ut.name, ut.description FROM units_translations ut JOIN languages l ON ut.language_id = l.id WHERE ut.unit_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        $descAr = null;
        $descEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $unitId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') {
                    $nameAr = $tRow['name'];
                    $descAr = $tRow['description'];
                }
                if ($tRow['language_code'] === 'en') {
                    $nameEn = $tRow['name'];
                    $descEn = $tRow['description'];
                }
            }
        }

        $transMap = array_column($translations, 'name', 'language_code');
        $descMap = array_column($translations, 'description', 'language_code');
        $row['name'] = $transMap[$langInfo['code']] ?? ($nameAr ?? $nameEn ?? $row['code']);
        $row['title'] = $row['name'];
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
            $row['title_ar'] = $nameAr;
            $row['title_en'] = $nameEn;
            $row['description_ar'] = $descAr;
            $row['description_en'] = $descEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('adminListUnits')) {
    function adminListUnits($con) {
        ensure_content_tables_schema($con);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $courseIds = parse_int_list_param($_GET['course_ids'] ?? $_GET['course_id'] ?? $_GET['courses'] ?? null);
        $subjectIds = parse_int_list_param($_GET['subject_ids'] ?? $_GET['subject_id'] ?? $_GET['subjects'] ?? null);
        $teacherIds = parse_int_list_param($_GET['teacher_ids'] ?? $_GET['teacher_id'] ?? $_GET['teachers'] ?? null);
        $createdByIds = parse_int_list_param($_GET['created_by_ids'] ?? $_GET['created_by_id'] ?? null);
        $createdByType = strtolower(trim($_GET['created_by_type'] ?? ''));
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $code = trim($_GET['code'] ?? '');
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $sortBy = strtolower(trim($_GET['sort_by'] ?? $_GET['sort'] ?? 'order_num'));
        $sortDir = strtoupper(trim($_GET['dir'] ?? $_GET['direction'] ?? $_GET['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $allowedSort = [
            'id' => 'u.id',
            'code' => 'u.code',
            'created_at' => 'u.created_at',
            'name' => 'ut.name',
            'order_num' => 'u.order_num'
        ];
        $orderCol = $allowedSort[$sortBy] ?? 'u.order_num';

        $whereConditions = [];
        $params = [];
        $types = "";
        $joinUt = "";

        if (!empty($teacherIds)) {
            $joinUt = "LEFT JOIN unit_teachers ut_piv ON u.id = ut_piv.unit_id";
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $whereConditions[] = "((u.created_by_type = 'teacher' AND u.created_by_id IN ({$placeholders})) OR (ut_piv.teacher_id IN ({$placeholders}) AND ut_piv.is_active = 1) OR (u.created_by_type = 'admin' AND NOT EXISTS (SELECT 1 FROM unit_teachers ut_chk WHERE ut_chk.unit_id = u.id AND ut_chk.is_active = 1)))";
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
        }

        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $whereConditions[] = "u.course_id IN ({$placeholders})";
            foreach ($courseIds as $cId) {
                $params[] = $cId;
                $types .= "i";
            }
        }

        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereConditions[] = "u.subject_id IN ({$placeholders})";
            foreach ($subjectIds as $sId) {
                $params[] = $sId;
                $types .= "i";
            }
        }

        if (in_array($createdByType, ['admin', 'teacher'])) {
            $whereConditions[] = "u.created_by_type = ?";
            $params[] = $createdByType;
            $types .= "s";
        }

        if (!empty($createdByIds)) {
            $placeholders = implode(',', array_fill(0, count($createdByIds), '?'));
            $whereConditions[] = "u.created_by_id IN ({$placeholders})";
            foreach ($createdByIds as $cbId) {
                $params[] = $cbId;
                $types .= "i";
            }
        }

        if ($activeFilter !== null) {
            $whereConditions[] = "u.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }

        if (!empty($code)) {
            $whereConditions[] = "u.code LIKE ?";
            $params[] = '%' . $code . '%';
            $types .= "s";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(u.code LIKE ? OR ut.name LIKE ? OR ut.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM units");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT u.id) AS total FROM units u {$joinUt} LEFT JOIN units_translations ut ON u.id = ut.unit_id {$whereClause}";
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
            SELECT DISTINCT u.id
            FROM units u
            {$joinUt}
            LEFT JOIN units_translations ut ON u.id = ut.unit_id
            {$whereClause}
            ORDER BY {$orderCol} {$sortDir}, u.id ASC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $units = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $uId = (int)$row['id'];
            $detail = get_unit_details($con, $uId);
            if ($detail) {
                $units[] = $detail;
            }
        }

        return build_admin_paginated_response($units, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetUnit')) {
    function adminGetUnit($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Unit ID is required'];
        }
        $unit = get_unit_details($con, $id);
        if (!$unit) {
            return ['status' => 'error', 'message' => 'Unit not found'];
        }
        return ['status' => 'success', 'data' => $unit];
    }
}

if (!function_exists('adminCreateUnit')) {
    function adminCreateUnit($con, $creatorData = null) {
        ensure_content_tables_schema($con);
        $body = read_json_body();
        $courseId = (int)($body['course_id'] ?? 0);
        $subjectId = (int)($body['subject_id'] ?? 0);
        $nameAr = trim($body['name_ar'] ?? $body['title_ar'] ?? $body['name'] ?? $body['title'] ?? '');
        $nameEn = trim($body['name_en'] ?? $body['title_en'] ?? $nameAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);
        $code = strtolower(trim($body['code'] ?? ''));
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        $tId = 0;
        if (is_array($creatorData)) {
            $tId = (int)($creatorData['id'] ?? $creatorData['teacher_id'] ?? 0);
        } elseif (is_object($creatorData)) {
            $tId = (int)($creatorData->id ?? $creatorData->teacher_id ?? 0);
        }

        if ($tId > 0) {
            $createdByType = 'teacher';
            $createdById = $tId;
        } else {
            $createdByType = strtolower(trim($body['created_by_type'] ?? 'admin'));
            $createdById = isset($body['created_by_id']) && $body['created_by_id'] ? (int)$body['created_by_id'] : null;
        }

        if ($courseId <= 0 || $subjectId <= 0) {
            return ['status' => 'error', 'message' => 'Both course_id and subject_id are required for creating a unit'];
        }
        if (empty($nameAr)) {
            return ['status' => 'error', 'message' => 'Unit name/title is required'];
        }
        if (empty($code)) {
            $code = preg_replace('/[^a-z0-9]+/i', '-', strtolower($nameEn)) ?: ('unit-' . uniqid());
        }

        $nextOrderRes = mysqli_query($con, "SELECT COALESCE(MAX(order_num), 0) + 1 AS next_order FROM units WHERE course_id = {$courseId}");
        $orderNum = $nextOrderRes ? (int)(mysqli_fetch_assoc($nextOrderRes)['next_order'] ?? 1) : 1;
        $dripEnabled = isset($body['videos_drip_enabled']) ? (int)(bool)$body['videos_drip_enabled'] : 0;

        $stmt = mysqli_prepare($con, "INSERT INTO units (course_id, subject_id, code, order_num, videos_drip_enabled, is_active, created_by_type, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare unit insert: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "iisiiisi", $courseId, $subjectId, $code, $orderNum, $dripEnabled, $isActive, $createdByType, $createdById);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create unit: ' . mysqli_error($con)];
        }

        $unitId = mysqli_insert_id($con);

        $tStmt = mysqli_prepare($con, "INSERT INTO units_translations (unit_id, language_id, name, description) VALUES (?, 1, ?, ?), (?, 2, ?, ?)");
        mysqli_stmt_bind_param($tStmt, "ississ", $unitId, $nameAr, $descAr, $unitId, $nameEn, $descEn);
        mysqli_stmt_execute($tStmt);

        $teacherIds = $body['teacher_ids'] ?? [];
        if (!is_array($teacherIds)) {
            $teacherIds = is_numeric($teacherIds) ? [(int)$teacherIds] : [];
        }
        if ($createdByType === 'teacher' && $createdById > 0) {
            if (!in_array($createdById, $teacherIds)) {
                $teacherIds[] = $createdById;
            }
        }
        sync_content_teachers($con, 'unit', $unitId, $teacherIds);

        return [
            'status' => 'success',
            'message' => 'Unit created successfully',
            'data' => get_unit_details($con, $unitId)
        ];
    }
}

if (!function_exists('adminUpdateUnit')) {
    function adminUpdateUnit($con, $creatorData = null) {
        ensure_content_tables_schema($con);
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Unit ID is required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, course_id, subject_id, created_by_type, created_by_id FROM units WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$existing) {
            return ['status' => 'error', 'message' => 'Unit not found'];
        }

        $courseId = isset($body['course_id']) ? (int)$body['course_id'] : (int)$existing['course_id'];
        $subjectId = isset($body['subject_id']) ? (int)$body['subject_id'] : (int)$existing['subject_id'];
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        $updateFields = ["course_id = ?", "subject_id = ?"];
        $params = [$courseId, $subjectId];
        $types = "ii";

        if (isset($body['code']) && !empty(trim($body['code']))) {
            $updateFields[] = "code = ?";
            $params[] = strtolower(trim($body['code']));
            $types .= "s";
        }
        if ($isActive !== null) {
            $updateFields[] = "is_active = ?";
            $params[] = $isActive;
            $types .= "i";
        }
        if (isset($body['videos_drip_enabled'])) {
            $updateFields[] = "videos_drip_enabled = ?";
            $params[] = (int)(bool)$body['videos_drip_enabled'];
            $types .= "i";
        }
        if (isset($body['created_by_type']) && in_array(strtolower($body['created_by_type']), ['admin', 'teacher'])) {
            $updateFields[] = "created_by_type = ?";
            $params[] = strtolower($body['created_by_type']);
            $types .= "s";
        }
        if (isset($body['created_by_id'])) {
            $updateFields[] = "created_by_id = ?";
            $params[] = (int)$body['created_by_id'];
            $types .= "i";
        }

        $params[] = $id;
        $types .= "i";

        $uSql = "UPDATE units SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $uStmt = mysqli_prepare($con, $uSql);
        mysqli_stmt_bind_param($uStmt, $types, ...$params);
        mysqli_stmt_execute($uStmt);

        if (isset($body['name_ar']) || isset($body['title_ar']) || isset($body['name_en']) || isset($body['title_en']) || isset($body['name']) || isset($body['title']) || isset($body['description_ar']) || isset($body['description_en'])) {
            $nameAr = trim($body['name_ar'] ?? $body['title_ar'] ?? $body['name'] ?? $body['title'] ?? '');
            $nameEn = trim($body['name_en'] ?? $body['title_en'] ?? $nameAr);
            $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
            $descEn = trim($body['description_en'] ?? $descAr);

            if (!empty($nameAr)) {
                $tStmt1 = mysqli_prepare($con, "INSERT INTO units_translations (unit_id, language_id, name, description) VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
                mysqli_stmt_bind_param($tStmt1, "iss", $id, $nameAr, $descAr);
                mysqli_stmt_execute($tStmt1);
            }
            if (!empty($nameEn)) {
                $tStmt2 = mysqli_prepare($con, "INSERT INTO units_translations (unit_id, language_id, name, description) VALUES (?, 2, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
                mysqli_stmt_bind_param($tStmt2, "iss", $id, $nameEn, $descEn);
                mysqli_stmt_execute($tStmt2);
            }
        }

        if (isset($body['teacher_ids'])) {
            sync_content_teachers($con, 'unit', $id, $body['teacher_ids']);
        }

        return [
            'status' => 'success',
            'message' => 'Unit updated successfully',
            'data' => get_unit_details($con, $id)
        ];
    }
}

if (!function_exists('adminDeleteUnit')) {
    function adminDeleteUnit($con, $creatorData = null) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Unit ID is required'];
        }
        $stmt = mysqli_prepare($con, "DELETE FROM units WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            @mysqli_query($con, "DELETE FROM units_translations WHERE unit_id = {$id}");
            @mysqli_query($con, "DELETE FROM unit_teachers WHERE unit_id = {$id}");
            return ['status' => 'success', 'message' => 'Unit deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete unit'];
    }
}

if (!function_exists('adminReorderUnits')) {
    // Persists a manually drag-reordered list of units within one course.
    // Body: { course_id, ordered_ids: [unit_id, unit_id, ...] } — the new order,
    // top to bottom.
    function adminReorderUnits($con, $creatorData = null) {
        $body = read_json_body();
        $courseId = (int)($body['course_id'] ?? 0);
        $orderedIds = $body['ordered_ids'] ?? $body['order'] ?? [];

        if ($courseId <= 0) {
            return ['status' => 'error', 'message' => 'course_id is required'];
        }
        if (!is_array($orderedIds) || empty($orderedIds)) {
            return ['status' => 'error', 'message' => 'ordered_ids is required'];
        }

        $position = 1;
        foreach ($orderedIds as $unitId) {
            $unitId = (int)$unitId;
            if ($unitId <= 0) continue;
            mysqli_query($con, "UPDATE units SET order_num = {$position} WHERE id = {$unitId} AND course_id = {$courseId}");
            $position++;
        }

        return ['status' => 'success', 'message' => 'Units reordered successfully'];
    }
}
