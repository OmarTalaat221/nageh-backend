<?php
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/language.php';
require_once __DIR__ . '/../../helpers/upload.php';
require_once __DIR__ . '/../content/helpers.php';

if (!function_exists('generate_unique_subscription_code_string')) {
    function generate_unique_subscription_code_string($con, string $prefix = ''): string {
        do {
            $digits = '';
            for ($i = 0; $i < 14; $i++) {
                $digits .= mt_rand(0, 9);
            }
            $stmt = mysqli_prepare($con, "SELECT id FROM subscription_codes WHERE code = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $digits);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $exists = (bool)mysqli_fetch_assoc($res);
        } while ($exists);

        return $digits;
    }
}

if (!function_exists('sync_subscription_code_entities')) {
    function sync_subscription_code_entities($con, int $codeId, array $subjectIds = [], array $teacherIds = [], array $courseIds = []): void {
        if ($codeId <= 0) return;

        @mysqli_query($con, "DELETE FROM subscription_code_subjects WHERE subscription_code_id = {$codeId}");
        $cleanSubjectIds = array_unique(array_filter(array_map('intval', $subjectIds)));
        if (!empty($cleanSubjectIds)) {
            $stmt = mysqli_prepare($con, "INSERT INTO subscription_code_subjects (subscription_code_id, subject_id) VALUES (?, ?)");
            foreach ($cleanSubjectIds as $sId) {
                if ($sId > 0 && $stmt) {
                    mysqli_stmt_bind_param($stmt, "ii", $codeId, $sId);
                    mysqli_stmt_execute($stmt);
                }
            }
        }

        @mysqli_query($con, "DELETE FROM subscription_code_teachers WHERE subscription_code_id = {$codeId}");
        $cleanTeacherIds = array_unique(array_filter(array_map('intval', $teacherIds)));
        if (!empty($cleanTeacherIds)) {
            $stmt = mysqli_prepare($con, "INSERT INTO subscription_code_teachers (subscription_code_id, teacher_id) VALUES (?, ?)");
            foreach ($cleanTeacherIds as $tId) {
                if ($tId > 0 && $stmt) {
                    mysqli_stmt_bind_param($stmt, "ii", $codeId, $tId);
                    mysqli_stmt_execute($stmt);
                }
            }
        }

        @mysqli_query($con, "DELETE FROM subscription_code_courses WHERE subscription_code_id = {$codeId}");
        $cleanCourseIds = array_unique(array_filter(array_map('intval', $courseIds)));
        if (!empty($cleanCourseIds)) {
            $stmt = mysqli_prepare($con, "INSERT INTO subscription_code_courses (subscription_code_id, course_id) VALUES (?, ?)");
            foreach ($cleanCourseIds as $cId) {
                if ($cId > 0 && $stmt) {
                    mysqli_stmt_bind_param($stmt, "ii", $codeId, $cId);
                    mysqli_stmt_execute($stmt);
                }
            }
        }
    }
}

if (!function_exists('get_subscription_code_details')) {
    function get_subscription_code_details($con, int $codeId) {
        if ($codeId <= 0) return null;
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code, title, description, price, max_uses, used_count, valid_from, valid_to, duration_days, is_active, created_by_id, created_at, updated_at FROM subscription_codes WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $codeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['price'] = (float)$row['price'];
        $row['max_uses'] = (int)$row['max_uses'];
        $row['used_count'] = (int)$row['used_count'];
        $row['duration_days'] = $row['duration_days'] ? (int)$row['duration_days'] : null;
        $row['is_active'] = (int)$row['is_active'];

        $now = date('Y-m-d H:i:s');
        $isExpired = false;
        if ($row['valid_to'] && $row['valid_to'] < $now) $isExpired = true;
        if ($row['valid_from'] && $row['valid_from'] > $now) $isExpired = true;

        $isMaxReached = ($row['max_uses'] > 0 && $row['used_count'] >= $row['max_uses']);
        $row['is_valid'] = ($row['is_active'] === 1 && !$isExpired && !$isMaxReached);
        $row['remaining_uses'] = $row['max_uses'] === 0 ? 'unlimited' : max(0, $row['max_uses'] - $row['used_count']);

        $subjects = [];
        $sRes = mysqli_query($con, "SELECT s.id, st.name, s.code FROM subscription_code_subjects scs JOIN subjects s ON scs.subject_id = s.id LEFT JOIN subjects_translations st ON s.id = st.subject_id AND st.language_id = 1 WHERE scs.subscription_code_id = {$codeId}");
        if ($sRes) {
            while ($sRow = mysqli_fetch_assoc($sRes)) {
                $sRow['id'] = (int)$sRow['id'];
                $subjects[] = $sRow;
            }
        }
        $row['subjects'] = $subjects;
        $row['subject_ids'] = array_column($subjects, 'id');

        $teachers = [];
        $tRes = mysqli_query($con, "SELECT t.id, t.name, t.email, t.specialization, t.image FROM subscription_code_teachers sct JOIN teachers t ON sct.teacher_id = t.id WHERE sct.subscription_code_id = {$codeId}");
        if ($tRes) {
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['id'] = (int)$tRow['id'];
                $teachers[] = format_response_images($tRow);
            }
        }
        $row['teachers'] = $teachers;
        $row['teacher_ids'] = array_column($teachers, 'id');

        $courses = [];
        $cRes = mysqli_query($con, "SELECT c.id, c.slug, c.price, c.image, ct.name FROM subscription_code_courses scc JOIN courses c ON scc.course_id = c.id LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = 1 WHERE scc.subscription_code_id = {$codeId}");
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $cRow['id'] = (int)$cRow['id'];
                $cRow['price'] = (float)$cRow['price'];
                $courses[] = format_response_images($cRow);
            }
        }
        $row['courses'] = $courses;
        $row['course_ids'] = array_column($courses, 'id');

        if ($isAdmin) {
            $redemptions = [];
            $rRes = mysqli_query($con, "SELECT crl.id, crl.student_id, crl.redeemed_at, crl.ip_address, s.name AS student_name, s.email AS student_email FROM code_redemptions_log crl JOIN students s ON crl.student_id = s.id WHERE crl.subscription_code_id = {$codeId} ORDER BY crl.id DESC LIMIT 50");
            if ($rRes) {
                while ($rRow = mysqli_fetch_assoc($rRes)) {
                    $rRow['id'] = (int)$rRow['id'];
                    $rRow['student_id'] = (int)$rRow['student_id'];
                    $redemptions[] = $rRow;
                }
            }
            $row['redemptions'] = $redemptions;
        }

        return format_response_images($row);
    }
}

if (!function_exists('adminListSubscriptionCodes')) {
    function adminListSubscriptionCodes($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $subjectIds = parse_int_list_param($_GET['subject_ids'] ?? $_GET['subject_id'] ?? null);
        $teacherIds = parse_int_list_param($_GET['teacher_ids'] ?? $_GET['teacher_id'] ?? null);
        $courseIds = parse_int_list_param($_GET['course_ids'] ?? $_GET['course_id'] ?? null);

        $code = trim($_GET['code'] ?? '');
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $validOnly = isset($_GET['valid_only']) ? (int)$_GET['valid_only'] : null;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $sortBy = strtolower(trim($_GET['sort_by'] ?? $_GET['sort'] ?? 'id'));
        $sortDir = strtoupper(trim($_GET['dir'] ?? $_GET['direction'] ?? $_GET['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSort = [
            'id' => 'sc.id',
            'code' => 'sc.code',
            'title' => 'sc.title',
            'price' => 'sc.price',
            'used_count' => 'sc.used_count',
            'created_at' => 'sc.created_at'
        ];
        $orderCol = $allowedSort[$sortBy] ?? 'sc.id';

        $whereConditions = [];
        $params = [];
        $types = "";

        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereConditions[] = "sc.id IN (SELECT subscription_code_id FROM subscription_code_subjects WHERE subject_id IN ({$placeholders}))";
            foreach ($subjectIds as $sId) {
                $params[] = $sId;
                $types .= "i";
            }
        }

        if (!empty($teacherIds)) {
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $whereConditions[] = "sc.id IN (SELECT subscription_code_id FROM subscription_code_teachers WHERE teacher_id IN ({$placeholders}))";
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
        }

        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $whereConditions[] = "sc.id IN (SELECT subscription_code_id FROM subscription_code_courses WHERE course_id IN ({$placeholders}))";
            foreach ($courseIds as $cId) {
                $params[] = $cId;
                $types .= "i";
            }
        }

        if ($activeFilter !== null) {
            $whereConditions[] = "sc.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }

        if ($validOnly === 1) {
            $now = date('Y-m-d H:i:s');
            $whereConditions[] = "sc.is_active = 1 AND (sc.valid_from IS NULL OR sc.valid_from <= ?) AND (sc.valid_to IS NULL OR sc.valid_to >= ?) AND (sc.max_uses = 0 OR sc.used_count < sc.max_uses)";
            $params[] = $now;
            $params[] = $now;
            $types .= "ss";
        }

        if (!empty($code)) {
            $whereConditions[] = "sc.code LIKE ?";
            $params[] = '%' . $code . '%';
            $types .= "s";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(sc.code LIKE ? OR sc.title LIKE ? OR sc.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(used_count) AS total_redemptions FROM subscription_codes");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'total_redemptions' => (int)($statRow['total_redemptions'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT sc.id) AS total FROM subscription_codes sc {$whereClause}";
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
            SELECT DISTINCT sc.id
            FROM subscription_codes sc
            {$whereClause}
            ORDER BY {$orderCol} {$sortDir}
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $codes = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $scId = (int)$row['id'];
            $detail = get_subscription_code_details($con, $scId);
            if ($detail) {
                $codes[] = $detail;
            }
        }

        return build_admin_paginated_response($codes, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetSubscriptionCode')) {
    function adminGetSubscriptionCode($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Subscription Code ID is required'];
        }
        $code = get_subscription_code_details($con, $id);
        if (!$code) {
            return ['status' => 'error', 'message' => 'Subscription code not found'];
        }
        return ['status' => 'success', 'data' => $code];
    }
}

if (!function_exists('adminCreateSubscriptionCode')) {
    function adminCreateSubscriptionCode($con) {
        $body = read_json_body();
        $title = trim($body['title'] ?? $body['name'] ?? '');
        $description = trim($body['description'] ?? '');
        $price = isset($body['price']) ? (float)$body['price'] : 0.00;
        $maxUses = isset($body['max_uses']) ? (int)$body['max_uses'] : 1;
        $durationDays = isset($body['duration_days']) && $body['duration_days'] > 0 ? (int)$body['duration_days'] : null;
        $validFrom = !empty($body['valid_from']) ? date('Y-m-d H:i:s', strtotime($body['valid_from'])) : null;
        $validTo = !empty($body['valid_to']) ? date('Y-m-d H:i:s', strtotime($body['valid_to'])) : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        $subjectIds = $body['subject_ids'] ?? [];
        $teacherIds = $body['teacher_ids'] ?? [];
        $courseIds = $body['course_ids'] ?? [];

        $batchCount = max(1, min(100, (int)($body['batch_count'] ?? 1)));

        if (empty($title)) {
            return ['status' => 'error', 'message' => 'Subscription code title/name is required'];
        }

        $createdCodes = [];
        $prefix = trim($body['code_prefix'] ?? 'NAJEH');

        for ($i = 0; $i < $batchCount; $i++) {
            $codeStr = trim($body['code'] ?? '');
            if ($batchCount > 1 || empty($codeStr)) {
                $codeStr = generate_unique_subscription_code_string($con, $prefix);
            } else {
                $codeStr = strtoupper($codeStr);
                $checkStmt = mysqli_prepare($con, "SELECT id FROM subscription_codes WHERE code = ? LIMIT 1");
                mysqli_stmt_bind_param($checkStmt, "s", $codeStr);
                mysqli_stmt_execute($checkStmt);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
                    return ['status' => 'error', 'message' => 'Subscription code already exists: ' . $codeStr];
                }
            }

            $stmt = mysqli_prepare($con, "INSERT INTO subscription_codes (code, title, description, price, max_uses, valid_from, valid_to, duration_days, is_active, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            if (!$stmt) {
                return ['status' => 'error', 'message' => 'Failed to prepare code insert: ' . mysqli_error($con)];
            }
            mysqli_stmt_bind_param($stmt, "sssdissi", $codeStr, $title, $description, $price, $maxUses, $validFrom, $validTo, $durationDays, $isActive);
            if (!mysqli_stmt_execute($stmt)) {
                return ['status' => 'error', 'message' => 'Failed to create subscription code: ' . mysqli_error($con)];
            }

            $codeId = mysqli_insert_id($con);
            sync_subscription_code_entities($con, $codeId, $subjectIds, $teacherIds, $courseIds);
            $createdCodes[] = get_subscription_code_details($con, $codeId);
        }

        return [
            'status' => 'success',
            'message' => $batchCount > 1 ? "{$batchCount} subscription codes generated successfully" : 'Subscription code created successfully',
            'data' => $batchCount === 1 ? $createdCodes[0] : $createdCodes
        ];
    }
}

if (!function_exists('adminUpdateSubscriptionCode')) {
    function adminUpdateSubscriptionCode($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Subscription Code ID is required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, code FROM subscription_codes WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$existing) {
            return ['status' => 'error', 'message' => 'Subscription code not found'];
        }

        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($body['code']) && !empty(trim($body['code']))) {
            $newCode = strtoupper(trim($body['code']));
            if ($newCode !== $existing['code']) {
                $cCheck = mysqli_prepare($con, "SELECT id FROM subscription_codes WHERE code = ? AND id != ? LIMIT 1");
                mysqli_stmt_bind_param($cCheck, "si", $newCode, $id);
                mysqli_stmt_execute($cCheck);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($cCheck))) {
                    return ['status' => 'error', 'message' => 'Code already exists'];
                }
                $updateFields[] = "code = ?";
                $params[] = $newCode;
                $types .= "s";
            }
        }
        if (isset($body['title']) || isset($body['name'])) {
            $updateFields[] = "title = ?";
            $params[] = trim($body['title'] ?? $body['name']);
            $types .= "s";
        }
        if (isset($body['description'])) {
            $updateFields[] = "description = ?";
            $params[] = trim($body['description']);
            $types .= "s";
        }
        if (isset($body['price'])) {
            $updateFields[] = "price = ?";
            $params[] = (float)$body['price'];
            $types .= "d";
        }
        if (isset($body['max_uses'])) {
            $updateFields[] = "max_uses = ?";
            $params[] = (int)$body['max_uses'];
            $types .= "i";
        }
        if (array_key_exists('duration_days', $body)) {
            $updateFields[] = "duration_days = ?";
            $params[] = $body['duration_days'] ? (int)$body['duration_days'] : null;
            $types .= "i";
        }
        if (array_key_exists('valid_from', $body)) {
            $updateFields[] = "valid_from = ?";
            $params[] = !empty($body['valid_from']) ? date('Y-m-d H:i:s', strtotime($body['valid_from'])) : null;
            $types .= "s";
        }
        if (array_key_exists('valid_to', $body)) {
            $updateFields[] = "valid_to = ?";
            $params[] = !empty($body['valid_to']) ? date('Y-m-d H:i:s', strtotime($body['valid_to'])) : null;
            $types .= "s";
        }
        if (isset($body['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = (int)$body['is_active'];
            $types .= "i";
        }

        if (!empty($updateFields)) {
            $params[] = $id;
            $types .= "i";
            $uSql = "UPDATE subscription_codes SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $uStmt = mysqli_prepare($con, $uSql);
            mysqli_stmt_bind_param($uStmt, $types, ...$params);
            mysqli_stmt_execute($uStmt);
        }

        if (isset($body['subject_ids']) || isset($body['teacher_ids']) || isset($body['course_ids'])) {
            $existingDetail = get_subscription_code_details($con, $id);
            $subjectIds = $body['subject_ids'] ?? $existingDetail['subject_ids'];
            $teacherIds = $body['teacher_ids'] ?? $existingDetail['teacher_ids'];
            $courseIds = $body['course_ids'] ?? $existingDetail['course_ids'];
            sync_subscription_code_entities($con, $id, $subjectIds, $teacherIds, $courseIds);
        }

        return [
            'status' => 'success',
            'message' => 'Subscription code updated successfully',
            'data' => get_subscription_code_details($con, $id)
        ];
    }
}

if (!function_exists('adminDeleteSubscriptionCode')) {
    function adminDeleteSubscriptionCode($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Subscription Code ID is required'];
        }
        $stmt = mysqli_prepare($con, "DELETE FROM subscription_codes WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            @mysqli_query($con, "DELETE FROM subscription_code_subjects WHERE subscription_code_id = {$id}");
            @mysqli_query($con, "DELETE FROM subscription_code_teachers WHERE subscription_code_id = {$id}");
            @mysqli_query($con, "DELETE FROM subscription_code_courses WHERE subscription_code_id = {$id}");
            return ['status' => 'success', 'message' => 'Subscription code deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete subscription code'];
    }
}
