<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../content/helpers.php';
require_once __DIR__ . '/../../../helpers/admin_notifications.php';

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
            mysqli_stmt_bind_param($stmt, "sssdissii", $codeStr, $title, $description, $price, $maxUses, $validFrom, $validTo, $durationDays, $isActive);
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

if (!function_exists('get_student_subscription_details')) {
    function get_student_subscription_details($con, int $subId) {
        if ($subId <= 0) return null;

        $stmt = mysqli_prepare($con, "SELECT ss.id, ss.student_id, ss.subscription_code_id, ss.subscription_type, ss.start_date, ss.end_date, ss.is_active, ss.created_at, sc.code AS code_string FROM student_subscriptions ss LEFT JOIN subscription_codes sc ON ss.subscription_code_id = sc.id WHERE ss.id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $subId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['student_id'] = (int)$row['student_id'];
        $row['subscription_code_id'] = $row['subscription_code_id'] ? (int)$row['subscription_code_id'] : null;
        $row['is_active'] = (int)$row['is_active'];

        $now = date('Y-m-d H:i:s');
        $row['is_expired'] = ($row['end_date'] !== null && $row['end_date'] < $now);
        $row['is_currently_valid'] = ($row['is_active'] === 1 && !$row['is_expired']);

        $stStmt = mysqli_prepare($con, "SELECT id, name, email, phone, is_active FROM students WHERE id = ? LIMIT 1");
        if ($stStmt) {
            mysqli_stmt_bind_param($stStmt, "i", $row['student_id']);
            mysqli_stmt_execute($stStmt);
            $stRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));
            if ($stRow) {
                $stRow['id'] = (int)$stRow['id'];
                $stRow['is_active'] = (int)$stRow['is_active'];
                $row['student'] = $stRow;
            }
        }

        $subjects = [];
        $sRes = mysqli_query($con, "SELECT s.id, st.name, s.code FROM student_subscription_subjects sss JOIN subjects s ON sss.subject_id = s.id LEFT JOIN subjects_translations st ON s.id = st.subject_id AND st.language_id = 1 WHERE sss.student_subscription_id = {$subId}");
        if ($sRes) {
            while ($sRow = mysqli_fetch_assoc($sRes)) {
                $sRow['id'] = (int)$sRow['id'];
                $subjects[] = $sRow;
            }
        }
        $row['subjects'] = $subjects;

        $teachers = [];
        $tRes = mysqli_query($con, "SELECT t.id, t.name, t.email, t.specialization, t.image FROM student_subscription_teachers sst JOIN teachers t ON sst.teacher_id = t.id WHERE sst.student_subscription_id = {$subId}");
        if ($tRes) {
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['id'] = (int)$tRow['id'];
                $teachers[] = format_response_images($tRow);
            }
        }
        $row['teachers'] = $teachers;

        $courses = [];
        $cRes = mysqli_query($con, "SELECT c.id, c.slug, c.price, c.image, ct.name FROM student_subscription_courses ssc JOIN courses c ON ssc.course_id = c.id LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = 1 WHERE ssc.student_subscription_id = {$subId}");
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $cRow['id'] = (int)$cRow['id'];
                $cRow['price'] = (float)$cRow['price'];
                $courses[] = format_response_images($cRow);
            }
        }
        $row['courses'] = $courses;

        return format_response_images($row);
    }
}

if (!function_exists('adminListStudentSubscriptions')) {
    function adminListStudentSubscriptions($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        $subjectIds = parse_int_list_param($_GET['subject_ids'] ?? $_GET['subject_id'] ?? null);
        $teacherIds = parse_int_list_param($_GET['teacher_ids'] ?? $_GET['teacher_id'] ?? null);
        $courseIds = parse_int_list_param($_GET['course_ids'] ?? $_GET['course_id'] ?? null);
        $bookIds = parse_int_list_param($_GET['book_ids'] ?? $_GET['book_id'] ?? null);
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $subType = trim($_GET['subscription_type'] ?? $_GET['type'] ?? '');
        $statusFilter = strtolower(trim($_GET['status'] ?? ''));
        $fromDate = trim($_GET['from_date'] ?? $_GET['start_date'] ?? '');
        $toDate = trim($_GET['to_date'] ?? $_GET['end_date'] ?? '');
        $codeFilter = trim($_GET['code'] ?? '');
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $whereConditions = [];
        $params = [];
        $types = "";

        if ($studentId > 0) {
            $whereConditions[] = "ss.student_id = ?";
            $params[] = $studentId;
            $types .= "i";
        }

        if (!empty($subType)) {
            $whereConditions[] = "ss.subscription_type = ?";
            $params[] = $subType;
            $types .= "s";
        }

        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_subjects WHERE subject_id IN ({$placeholders}))";
            foreach ($subjectIds as $sId) {
                $params[] = $sId;
                $types .= "i";
            }
        }

        if (!empty($teacherIds)) {
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_teachers WHERE teacher_id IN ({$placeholders}))";
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
        }

        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_courses WHERE course_id IN ({$placeholders}))";
            foreach ($courseIds as $cId) {
                $params[] = $cId;
                $types .= "i";
            }
        }

        if (!empty($bookIds)) {
            $bTableCheck = mysqli_query($con, "SHOW TABLES LIKE 'student_subscription_books'");
            if ($bTableCheck && mysqli_num_rows($bTableCheck) > 0) {
                $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
                $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_books WHERE book_id IN ({$placeholders}))";
                foreach ($bookIds as $bId) {
                    $params[] = $bId;
                    $types .= "i";
                }
            }
        }

        if ($activeFilter !== null) {
            $whereConditions[] = "ss.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }

        if ($statusFilter === 'active' || $statusFilter === 'valid') {
            $now = date('Y-m-d H:i:s');
            $whereConditions[] = "ss.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= ?)";
            $params[] = $now;
            $types .= "s";
        } elseif ($statusFilter === 'expired') {
            $now = date('Y-m-d H:i:s');
            $whereConditions[] = "ss.end_date IS NOT NULL AND ss.end_date < ?";
            $params[] = $now;
            $types .= "s";
        } elseif ($statusFilter === 'inactive') {
            $whereConditions[] = "ss.is_active = 0";
        }

        if (!empty($fromDate)) {
            $whereConditions[] = "ss.start_date >= ?";
            $params[] = date('Y-m-d 00:00:00', strtotime($fromDate));
            $types .= "s";
        }

        if (!empty($toDate)) {
            $whereConditions[] = "ss.start_date <= ?";
            $params[] = date('Y-m-d 23:59:59', strtotime($toDate));
            $types .= "s";
        }

        if (!empty($codeFilter)) {
            $whereConditions[] = "sc.code LIKE ?";
            $params[] = '%' . $codeFilter . '%';
            $types .= "s";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(st.name LIKE ? OR st.email LIKE ? OR st.phone LIKE ? OR sc.code LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ssss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $countSql = "SELECT COUNT(DISTINCT ss.id) AS total FROM student_subscriptions ss JOIN students st ON ss.student_id = st.id LEFT JOIN subscription_codes sc ON ss.subscription_code_id = sc.id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        $totalRecords = 0;
        if ($countStmt) {
            if (!empty($types)) {
                mysqli_stmt_bind_param($countStmt, $types, ...$params);
            }
            if (mysqli_stmt_execute($countStmt)) {
                $cRes = mysqli_stmt_get_result($countStmt);
                if ($cRes) {
                    $cRow = mysqli_fetch_assoc($cRes);
                    $totalRecords = (int)($cRow['total'] ?? 0);
                }
            }
        }

        $sql = "
            SELECT DISTINCT ss.id
            FROM student_subscriptions ss
            JOIN students st ON ss.student_id = st.id
            LEFT JOIN subscription_codes sc ON ss.subscription_code_id = sc.id
            {$whereClause}
            ORDER BY ss.id DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $subscriptions = [];
        $stmt = mysqli_prepare($con, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $subId = (int)$row['id'];
                        $detail = get_student_subscription_details($con, $subId);
                        if ($detail) {
                            $subscriptions[] = $detail;
                        }
                    }
                }
            }
        }

        return build_admin_paginated_response($subscriptions, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('adminCreateStudentSubscription')) {
    function adminCreateStudentSubscription($con) {
        $body = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Student ID is required'];
        }

        $stCheck = mysqli_prepare($con, "SELECT id FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stCheck, "i", $studentId);
        mysqli_stmt_execute($stCheck);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stCheck))) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }

        $subjectIds = parse_int_list_param($body['subject_ids'] ?? $body['subject_id'] ?? null);
        $teacherIds = parse_int_list_param($body['teacher_ids'] ?? $body['teacher_id'] ?? null);
        $courseIds = parse_int_list_param($body['course_ids'] ?? $body['course_id'] ?? null);

        if (empty($subjectIds) && empty($teacherIds) && empty($courseIds)) {
            return ['status' => 'error', 'message' => 'At least one subject, teacher, or course must be selected for subscription'];
        }

        $coursesAllPackages = false;
        if (!empty($courseIds)) {
            $courseListStr = implode(',', array_map('intval', $courseIds));
            $kindCheckRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN kind = 'package' THEN 1 ELSE 0 END) AS package_count FROM courses WHERE id IN ({$courseListStr})");
            if ($kindCheckRes && $kindCheckRow = mysqli_fetch_assoc($kindCheckRes)) {
                $coursesAllPackages = (int)($kindCheckRow['total'] ?? 0) > 0
                    && (int)($kindCheckRow['total'] ?? 0) === (int)($kindCheckRow['package_count'] ?? 0);
            }
        }

        $subType = 'custom';
        if (!empty($courseIds) && empty($subjectIds) && empty($teacherIds)) $subType = $coursesAllPackages ? 'package' : 'course';
        if (!empty($subjectIds) && empty($courseIds) && empty($teacherIds)) $subType = 'subject';
        if (!empty($teacherIds) && empty($courseIds) && empty($subjectIds)) $subType = 'teacher';

        $durationDays = isset($body['duration_days']) && $body['duration_days'] > 0 ? (int)$body['duration_days'] : null;
        $startDate = !empty($body['start_date']) ? date('Y-m-d H:i:s', strtotime($body['start_date'])) : date('Y-m-d H:i:s');
        $endDate = !empty($body['end_date']) ? date('Y-m-d H:i:s', strtotime($body['end_date'])) : ($durationDays ? date('Y-m-d H:i:s', strtotime("+{$durationDays} days")) : null);

        $stmt = mysqli_prepare($con, "INSERT INTO student_subscriptions (student_id, subscription_type, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare subscription insert: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "isss", $studentId, $subType, $startDate, $endDate);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create student subscription: ' . mysqli_error($con)];
        }

        $subId = mysqli_insert_id($con);

        foreach ($subjectIds as $sId) {
            $isStmt = mysqli_prepare($con, "INSERT INTO student_subscription_subjects (student_subscription_id, subject_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE subject_id=subject_id");
            mysqli_stmt_bind_param($isStmt, "ii", $subId, $sId);
            mysqli_stmt_execute($isStmt);
        }

        foreach ($teacherIds as $tId) {
            $itStmt = mysqli_prepare($con, "INSERT INTO student_subscription_teachers (student_subscription_id, teacher_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE teacher_id=teacher_id");
            mysqli_stmt_bind_param($itStmt, "ii", $subId, $tId);
            mysqli_stmt_execute($itStmt);
        }

        foreach ($courseIds as $cId) {
            $icStmt = mysqli_prepare($con, "INSERT INTO student_subscription_courses (student_subscription_id, course_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE course_id=course_id");
            mysqli_stmt_bind_param($icStmt, "ii", $subId, $cId);
            mysqli_stmt_execute($icStmt);
        }

        if (!empty($courseIds)) {
            // Admin granted this themselves, so only the course's teacher(s) need telling — not the admin.
            notify_of_course_subscription($con, $studentId, $courseIds, 'منح إداري', false);
        }

        return [
            'status' => 'success',
            'message' => 'Student subscription created successfully',
            'data' => get_student_subscription_details($con, $subId)
        ];
    }
}
