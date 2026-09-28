<?php
require_once __DIR__ . '/../../../helpers/discounts.php';

/**
 * Teacher-owned discount codes.
 *
 * Same table as the admin's codes - what separates them is `created_by_type` / `created_by_id`.
 * A teacher only ever sees, edits and deletes their own rows, and a teacher's code can only ever
 * reduce the price of a course that teacher actually teaches: `discount_validate_code()` enforces
 * that at redemption time, and the create/update handlers below refuse to even store a course
 * that is not theirs. Both halves are needed - the write-side check is the friendly error, the
 * redemption-side check is the one that cannot be bypassed by a code created before a teacher
 * was removed from a course.
 */

if (!function_exists('teacher_discount_id')) {
    function teacher_discount_id($teacherData): int {
        if (is_array($teacherData)) {
            return (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        }
        if (is_object($teacherData)) {
            return (int)($teacherData->id ?? $teacherData->teacher_id ?? 0);
        }
        return 0;
    }
}

if (!function_exists('teacher_owns_discount_code')) {
    function teacher_owns_discount_code($con, int $codeId, int $teacherId): bool {
        $stmt = mysqli_prepare($con, "SELECT id FROM discount_codes WHERE id = ? AND created_by_type = 'teacher' AND created_by_id = ? LIMIT 1");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "ii", $codeId, $teacherId);
        mysqli_stmt_execute($stmt);
        return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

/**
 * Returns the course ids to store, or a string error message if any of them is not this
 * teacher's. An empty list is legal and means "all my courses".
 */
if (!function_exists('teacher_filter_discount_course_ids')) {
    function teacher_filter_discount_course_ids($con, int $teacherId, $courseIds) {
        if (!is_array($courseIds)) {
            $courseIds = $courseIds !== null && $courseIds !== '' ? [$courseIds] : [];
        }
        $requested = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
        if (!$requested) return [];

        $owned = discount_teacher_course_ids($con, $teacherId);
        $foreign = array_values(array_diff($requested, $owned));
        if ($foreign) {
            return 'لا يمكنك عمل خصم على كورسات مش بتدرسها (' . implode(', ', $foreign) . ')';
        }
        return $requested;
    }
}

if (!function_exists('teacherListDiscountCodes')) {
    function teacherListDiscountCodes($con, $teacherData = null) {
        $teacherId = teacher_discount_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = ["created_by_type = 'teacher'", 'created_by_id = ?'];
        $params = [$teacherId];
        $types = 'i';

        if ($search !== '') {
            $where[] = '(code LIKE ? OR title LIKE ?)';
            $pattern = '%' . $search . '%';
            array_push($params, $pattern, $pattern);
            $types .= 'ss';
        }
        if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
            $where[] = 'is_active = ?';
            $params[] = (int)$_GET['is_active'];
            $types .= 'i';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM discount_codes {$whereSql}");
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $statStmt = mysqli_prepare($con, "
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count,
                   SUM(used_count) AS total_redemptions
            FROM discount_codes
            WHERE created_by_type = 'teacher' AND created_by_id = ?
        ");
        mysqli_stmt_bind_param($statStmt, "i", $teacherId);
        mysqli_stmt_execute($statStmt);
        $statRow = mysqli_fetch_assoc(mysqli_stmt_get_result($statStmt)) ?: [];
        $statistics = [
            'total_count' => (int)($statRow['total'] ?? 0),
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'total_redemptions' => (int)($statRow['total_redemptions'] ?? 0),
        ];

        $listParams = $params;
        $listTypes = $types . 'ii';
        $listParams[] = $limit;
        $listParams[] = $offset;

        $stmt = mysqli_prepare($con, "SELECT * FROM discount_codes {$whereSql} ORDER BY id DESC LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($stmt, $listTypes, ...$listParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = discount_format_row($con, $row);
        }

        return build_paginated_response($rows, $page, $limit, $total, $statistics);
    }
}

if (!function_exists('teacherGetDiscountCode')) {
    function teacherGetDiscountCode($con, $teacherData = null) {
        $teacherId = teacher_discount_id($teacherData);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Discount code id is required'];
        }
        if (!teacher_owns_discount_code($con, $id, $teacherId)) {
            return ['status' => 'error', 'message' => 'Discount code not found'];
        }
        return ['status' => 'success', 'data' => get_discount_code_details($con, $id)];
    }
}

if (!function_exists('teacherCreateDiscountCode')) {
    function teacherCreateDiscountCode($con, $teacherData = null) {
        $teacherId = teacher_discount_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $body = read_json_body();

        $title = trim($body['title'] ?? '');
        if ($title === '') {
            return ['status' => 'error', 'message' => 'Discount code title is required'];
        }

        $discountType = strtolower(trim($body['discount_type'] ?? 'percentage'));
        if (!in_array($discountType, ['percentage', 'fixed'], true)) {
            return ['status' => 'error', 'message' => 'discount_type must be percentage or fixed'];
        }

        $discountValue = (float)($body['discount_value'] ?? 0);
        if ($discountValue <= 0) {
            return ['status' => 'error', 'message' => 'discount_value must be greater than zero'];
        }
        if ($discountType === 'percentage' && $discountValue > 100) {
            return ['status' => 'error', 'message' => 'A percentage discount cannot exceed 100'];
        }

        $courseIds = teacher_filter_discount_course_ids($con, $teacherId, $body['course_ids'] ?? []);
        if (is_string($courseIds)) {
            return ['status' => 'error', 'message' => $courseIds];
        }

        $code = strtoupper(trim($body['code'] ?? ''));
        if ($code === '') {
            $code = 'DISC-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        }

        $dupStmt = mysqli_prepare($con, "SELECT id FROM discount_codes WHERE UPPER(code) = ? LIMIT 1");
        mysqli_stmt_bind_param($dupStmt, "s", $code);
        mysqli_stmt_execute($dupStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($dupStmt))) {
            return ['status' => 'error', 'message' => 'Discount code already exists: ' . $code];
        }

        $description = trim($body['description'] ?? '') ?: null;
        $maxDiscount = isset($body['max_discount_amount']) && $body['max_discount_amount'] !== '' && $body['max_discount_amount'] !== null
            ? (float)$body['max_discount_amount'] : null;
        $appliesTo = !empty($courseIds) ? 'selected' : 'all';
        $maxUses = isset($body['max_uses']) && $body['max_uses'] !== '' && $body['max_uses'] !== null
            ? (int)$body['max_uses'] : null;
        $oncePerStudent = isset($body['once_per_student']) ? (int)(bool)$body['once_per_student'] : 1;
        $validFrom = !empty($body['valid_from']) ? date('Y-m-d H:i:s', strtotime($body['valid_from'])) : null;
        $validTo = !empty($body['valid_to']) ? date('Y-m-d H:i:s', strtotime($body['valid_to'])) : null;
        $isActive = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;

        // Which currency a fixed amount is in; ignored for a percentage code.
        $currencyCode = strtoupper(trim((string)($body['currency_code'] ?? ''))) ?: pricing_base_currency($con);
        if (!currency_info($con, $currencyCode)['is_active']) {
            return ['status' => 'error', 'message' => app_text($con, 'العملة دي مش موجودة أو مش مفعّلة.', 'That currency does not exist or is not active.')];
        }

        $stmt = mysqli_prepare($con, "
            INSERT INTO discount_codes
                (code, title, description, discount_type, discount_value, currency_code, max_discount_amount, applies_to,
                 max_uses, once_per_student, valid_from, valid_to, is_active, created_by_type, created_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'teacher', ?)
        ");
        mysqli_stmt_bind_param(
            $stmt, "ssssdsdsiissii",
            $code, $title, $description, $discountType, $discountValue, $currencyCode, $maxDiscount, $appliesTo,
            $maxUses, $oncePerStudent, $validFrom, $validTo, $isActive, $teacherId
        );
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create discount code: ' . mysqli_error($con)];
        }

        $newId = mysqli_insert_id($con);
        discount_sync_courses($con, $newId, $courseIds);

        return [
            'status' => 'success',
            'message' => 'تم إنشاء كود الخصم بنجاح',
            'data' => get_discount_code_details($con, $newId),
        ];
    }
}

if (!function_exists('teacherUpdateDiscountCode')) {
    function teacherUpdateDiscountCode($con, $teacherData = null) {
        $teacherId = teacher_discount_id($teacherData);
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Discount code id is required'];
        }
        if (!teacher_owns_discount_code($con, $id, $teacherId)) {
            return ['status' => 'error', 'message' => 'Discount code not found'];
        }

        $fields = [];
        $params = [];
        $types = '';

        if (array_key_exists('title', $body)) {
            $fields[] = 'title = ?';
            $params[] = trim($body['title']);
            $types .= 's';
        }
        if (array_key_exists('description', $body)) {
            $fields[] = 'description = ?';
            $params[] = trim((string)$body['description']) ?: null;
            $types .= 's';
        }
        if (array_key_exists('discount_type', $body)) {
            $discountType = strtolower(trim($body['discount_type']));
            if (!in_array($discountType, ['percentage', 'fixed'], true)) {
                return ['status' => 'error', 'message' => 'discount_type must be percentage or fixed'];
            }
            $fields[] = 'discount_type = ?';
            $params[] = $discountType;
            $types .= 's';
        }
        if (array_key_exists('discount_value', $body)) {
            $fields[] = 'discount_value = ?';
            $params[] = (float)$body['discount_value'];
            $types .= 'd';
        }
        if (array_key_exists('currency_code', $body)) {
            $currencyCode = strtoupper(trim((string)$body['currency_code'])) ?: pricing_base_currency($con);
            if (!currency_info($con, $currencyCode)['is_active']) {
                return ['status' => 'error', 'message' => app_text($con, 'العملة دي مش موجودة أو مش مفعّلة.', 'That currency does not exist or is not active.')];
            }
            $fields[] = 'currency_code = ?';
            $params[] = $currencyCode;
            $types .= 's';
        }
        if (array_key_exists('max_discount_amount', $body)) {
            $fields[] = 'max_discount_amount = ?';
            $params[] = $body['max_discount_amount'] === '' || $body['max_discount_amount'] === null
                ? null : (float)$body['max_discount_amount'];
            $types .= 'd';
        }
        if (array_key_exists('max_uses', $body)) {
            $fields[] = 'max_uses = ?';
            $params[] = $body['max_uses'] === '' || $body['max_uses'] === null ? null : (int)$body['max_uses'];
            $types .= 'i';
        }
        if (array_key_exists('once_per_student', $body)) {
            $fields[] = 'once_per_student = ?';
            $params[] = (int)(bool)$body['once_per_student'];
            $types .= 'i';
        }
        foreach (['valid_from', 'valid_to'] as $dateField) {
            if (array_key_exists($dateField, $body)) {
                $fields[] = "{$dateField} = ?";
                $params[] = !empty($body[$dateField]) ? date('Y-m-d H:i:s', strtotime($body[$dateField])) : null;
                $types .= 's';
            }
        }
        if (array_key_exists('is_active', $body)) {
            $fields[] = 'is_active = ?';
            $params[] = (int)(bool)$body['is_active'];
            $types .= 'i';
        }

        // The scope follows the course list, same as the admin handler - except a teacher may
        // only ever name their own courses.
        if (array_key_exists('course_ids', $body)) {
            $courseIds = teacher_filter_discount_course_ids($con, $teacherId, $body['course_ids']);
            if (is_string($courseIds)) {
                return ['status' => 'error', 'message' => $courseIds];
            }
            discount_sync_courses($con, $id, $courseIds);
            $fields[] = 'applies_to = ?';
            $params[] = !empty($courseIds) ? 'selected' : 'all';
            $types .= 's';
        }

        if ($fields) {
            $params[] = $id;
            $types .= 'i';
            $stmt = mysqli_prepare($con, "UPDATE discount_codes SET " . implode(', ', $fields) . " WHERE id = ?");
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                return ['status' => 'error', 'message' => 'Failed to update discount code: ' . mysqli_error($con)];
            }
        }

        return [
            'status' => 'success',
            'message' => 'تم تعديل كود الخصم بنجاح',
            'data' => get_discount_code_details($con, $id),
        ];
    }
}

if (!function_exists('teacherDeleteDiscountCode')) {
    function teacherDeleteDiscountCode($con, $teacherData = null) {
        $teacherId = teacher_discount_id($teacherData);
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Discount code id is required'];
        }
        if (!teacher_owns_discount_code($con, $id, $teacherId)) {
            return ['status' => 'error', 'message' => 'Discount code not found'];
        }

        mysqli_query($con, "DELETE FROM discount_code_courses WHERE discount_code_id = {$id}");

        $stmt = mysqli_prepare($con, "DELETE FROM discount_codes WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete discount code: ' . mysqli_error($con)];
        }

        // Redemptions are left behind on purpose: they are the record of discounts already given.
        return ['status' => 'success', 'message' => 'تم حذف كود الخصم'];
    }
}

if (!function_exists('teacherCheckDiscountCode')) {
    function teacherCheckDiscountCode($con, $teacherData = null) {
        $body = read_json_body();
        $code = (string)($body['code'] ?? $_GET['code'] ?? '');
        $studentId = (int)($body['student_id'] ?? $_GET['student_id'] ?? 0);
        $courseIds = $body['course_ids'] ?? $_GET['course_ids'] ?? [];
        if (!is_array($courseIds)) {
            $courseIds = array_filter(array_map('trim', explode(',', (string)$courseIds)));
        }

        $result = discount_validate_code($con, $code, $studentId ?: null, $courseIds);

        return [
            'status' => !empty($result['valid']) ? 'success' : 'error',
            'message' => $result['message'] ?? '',
            'data' => $result,
        ];
    }
}

/**
 * Discounts actually given on this teacher's codes. Pending student claims are included with
 * their `status`, because "three students are holding my code" is exactly what a teacher wants
 * to see before deciding whether to keep it running.
 */
if (!function_exists('teacherListDiscountRedemptions')) {
    function teacherListDiscountRedemptions($con, $teacherData = null) {
        $teacherId = teacher_discount_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = ["d.created_by_type = 'teacher'", 'd.created_by_id = ?'];
        $params = [$teacherId];
        $types = 'i';

        if (!empty($_GET['discount_code_id'])) {
            $where[] = 'r.discount_code_id = ?';
            $params[] = (int)$_GET['discount_code_id'];
            $types .= 'i';
        }
        if (!empty($_GET['status']) && in_array($_GET['status'], ['pending', 'applied'], true)) {
            $where[] = 'r.status = ?';
            $params[] = $_GET['status'];
            $types .= 's';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countStmt = mysqli_prepare($con, "
            SELECT COUNT(*) AS total
            FROM discount_code_redemptions r
            JOIN discount_codes d ON d.id = r.discount_code_id
            {$whereSql}
        ");
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $listParams = $params;
        $listTypes = $types . 'ii';
        $listParams[] = $limit;
        $listParams[] = $offset;

        $stmt = mysqli_prepare($con, "
            SELECT r.*, d.code, d.title, s.name AS student_name
            FROM discount_code_redemptions r
            JOIN discount_codes d ON d.id = r.discount_code_id
            LEFT JOIN students s ON s.id = r.student_id
            {$whereSql}
            ORDER BY r.id DESC
            LIMIT ? OFFSET ?
        ");
        mysqli_stmt_bind_param($stmt, $listTypes, ...$listParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['student_id'] = (int)$row['student_id'];
            $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
            $row['original_amount'] = (float)$row['original_amount'];
            $row['discount_amount'] = (float)$row['discount_amount'];
            $row['final_amount'] = (float)$row['final_amount'];
            $row['course'] = $row['course_id'] ? get_course_details($con, $row['course_id']) : null;
            $rows[] = $row;
        }

        return build_paginated_response(format_response_images($rows), $page, $limit, $total);
    }
}
