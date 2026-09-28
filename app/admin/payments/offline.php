<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/offline_payments.php';

/**
 * The review queue, and the accounts students transfer into.
 */

if (!function_exists('offline_reviewer_id')) {
    function offline_reviewer_id($data): int {
        if (is_array($data)) return (int)($data['id'] ?? 0);
        if (is_object($data)) return (int)($data->id ?? 0);
        return 0;
    }
}

if (!function_exists('offline_find_request')) {
    function offline_find_request($con, int $id): ?array {
        $stmt = mysqli_prepare($con, "SELECT * FROM offline_payment_requests WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }
}

// =================================================================================================
// The queue
// =================================================================================================

if (!function_exists('adminListOfflinePayments')) {
    function adminListOfflinePayments($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $conditions = [];
        $params = [];
        $types = '';

        $status = trim($_GET['status'] ?? '');
        if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $conditions[] = 'r.status = ?';
            $params[] = $status;
            $types .= 's';
        }

        $studentId = (int)($_GET['student_id'] ?? 0);
        if ($studentId > 0) {
            $conditions[] = 'r.student_id = ?';
            $params[] = $studentId;
            $types .= 'i';
        }

        $itemType = trim($_GET['item_type'] ?? '');
        if (in_array($itemType, ['course', 'book'], true)) {
            $conditions[] = 'r.item_type = ?';
            $params[] = $itemType;
            $types .= 's';
        }

        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        if ($search !== '') {
            $conditions[] = '(s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR r.sender_reference LIKE ? OR r.sender_name LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
            $types .= 'sssss';
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        // The counts are the point of this screen - "how many are waiting" is the first thing
        // anyone opening it wants, and it must not depend on the filter currently applied.
        $statistics = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'pending_amount' => 0.0];
        $statRes = @mysqli_query($con, "SELECT status, COUNT(*) AS c, SUM(amount) AS total FROM offline_payment_requests GROUP BY status");
        if ($statRes) {
            while ($row = mysqli_fetch_assoc($statRes)) {
                $statistics[$row['status']] = (int)$row['c'];
                if ($row['status'] === 'pending') $statistics['pending_amount'] = (float)$row['total'];
            }
        }

        $countSql = "SELECT COUNT(*) AS total FROM offline_payment_requests r LEFT JOIN students s ON s.id = r.student_id" . $where;
        $total = 0;
        $countStmt = mysqli_prepare($con, $countSql);
        if ($countStmt) {
            if ($types) mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);
        }

        $sql = offline_payment_base_query($con) . $where . " ORDER BY FIELD(r.status, 'pending') DESC, r.id DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = offline_payment_format($con, $row);
        }

        return [
            'status' => 'success',
            'data' => $rows,
            'statistics' => $statistics,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $total,
                'total_pages' => (int)ceil($total / $limit),
                'has_next_page' => ($offset + $limit) < $total,
                'has_prev_page' => $page > 1,
            ],
        ];
    }
}

if (!function_exists('adminReviewOfflinePayment')) {
    function adminReviewOfflinePayment($con, $adminData = null) {
        $adminId = offline_reviewer_id($adminData);
        $body = read_json_body();

        $id = (int)($body['id'] ?? 0);
        $action = strtolower(trim($body['action'] ?? ''));
        $note = trim((string)($body['note'] ?? $body['review_note'] ?? ''));

        if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'حدد الطلب والإجراء (approve أو reject).', 'Specify the request and the action (approve or reject).')];
        }

        $request = offline_find_request($con, $id);
        if (!$request) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'الطلب مش موجود.', 'That request does not exist.')];
        }
        if ($request['status'] !== 'pending') {
            return ['status' => 'error', 'message' => app_offline_text($con, 'الطلب ده اتراجع عليه بالفعل.', 'This request has already been reviewed.')];
        }

        $result = $action === 'approve'
            ? offline_payment_approve($con, $request, 'admin', $adminId, $note)
            : offline_payment_reject($con, $request, 'admin', $adminId, $note);

        if (empty($result['ok'])) {
            return ['status' => 'error', 'message' => $result['message']];
        }

        return ['status' => 'success', 'message' => $result['message'], 'data' => ['id' => $id, 'action' => $action]];
    }
}

// =================================================================================================
// The accounts
// =================================================================================================

if (!function_exists('adminListPaymentAccounts')) {
    function adminListPaymentAccounts($con) {
        return ['status' => 'success', 'data' => payment_accounts_list($con, false)];
    }
}

if (!function_exists('payment_account_validate')) {
    function payment_account_validate($con, array $body, bool $isCreate): ?string {
        $method = strtolower(trim((string)($body['method'] ?? '')));
        if ($isCreate || array_key_exists('method', $body)) {
            if (!in_array($method, offline_payment_methods(), true)) {
                return app_offline_text($con, 'وسيلة الدفع دي مش معروفة.', 'That payment method is not recognised.');
            }
        }

        if ($isCreate || array_key_exists('label_ar', $body)) {
            if (trim((string)($body['label_ar'] ?? '')) === '') {
                return app_offline_text($con, 'اكتب اسم الوسيلة بالعربي.', 'The Arabic label is required.');
            }
        }
        if ($isCreate || array_key_exists('label_en', $body)) {
            if (trim((string)($body['label_en'] ?? '')) === '') {
                return app_offline_text($con, 'اكتب اسم الوسيلة بالإنجليزي.', 'The English label is required.');
            }
        }

        // The number IS the feature. An account row without one tells a student to transfer
        // money to nowhere.
        if ($isCreate || array_key_exists('account_number', $body)) {
            if (trim((string)($body['account_number'] ?? '')) === '') {
                return app_offline_text($con, 'اكتب رقم الحساب أو المحفظة.', 'The account or wallet number is required.');
            }
        }

        return null;
    }
}

if (!function_exists('adminCreatePaymentAccount')) {
    function adminCreatePaymentAccount($con) {
        $body = read_json_body();

        $error = payment_account_validate($con, $body, true);
        if ($error !== null) return ['status' => 'error', 'message' => $error];

        $stmt = mysqli_prepare($con, "INSERT INTO payment_accounts
            (method, label_ar, label_en, account_name, account_number, instructions_ar, instructions_en, is_active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];

        $method = strtolower(trim((string)$body['method']));
        $labelAr = trim((string)$body['label_ar']);
        $labelEn = trim((string)$body['label_en']);
        $accountName = trim((string)($body['account_name'] ?? '')) ?: null;
        $accountNumber = trim((string)$body['account_number']);
        $insAr = trim((string)($body['instructions_ar'] ?? '')) ?: null;
        $insEn = trim((string)($body['instructions_en'] ?? '')) ?: null;
        $isActive = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;
        $sortOrder = (int)($body['sort_order'] ?? 0);

        mysqli_stmt_bind_param($stmt, "sssssssii", $method, $labelAr, $labelEn, $accountName, $accountNumber, $insAr, $insEn, $isActive, $sortOrder);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        }

        // Read before anything else touches the connection. The message below resolves the
        // request language, which runs its own query, and that resets insert_id to 0 - PHP
        // evaluates the array in order, so the id would be read after it had already been lost.
        $newId = mysqli_insert_id($con);

        return [
            'status' => 'success',
            'message' => app_offline_text($con, 'تمت إضافة وسيلة الدفع.', 'The payment method was added.'),
            'data' => ['id' => $newId],
        ];
    }
}

if (!function_exists('adminUpdatePaymentAccount')) {
    function adminUpdatePaymentAccount($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => app_offline_text($con, 'حدد الوسيلة.', 'Specify the account.')];

        if (!payment_account_find($con, $id)) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'الوسيلة مش موجودة.', 'That account does not exist.')];
        }

        $error = payment_account_validate($con, $body, false);
        if ($error !== null) return ['status' => 'error', 'message' => $error];

        // A partial update: only the fields actually sent are touched, so an edit of the label
        // does not blank out the instructions.
        $fields = [];
        $params = [];
        $types = '';

        $map = [
            'method' => 's', 'label_ar' => 's', 'label_en' => 's', 'account_name' => 's',
            'account_number' => 's', 'instructions_ar' => 's', 'instructions_en' => 's',
            'is_active' => 'i', 'sort_order' => 'i',
        ];

        foreach ($map as $key => $type) {
            if (!array_key_exists($key, $body)) continue;
            $fields[] = "`{$key}` = ?";
            $value = $body[$key];
            if ($type === 'i') {
                $params[] = (int)$value;
            } else {
                $trimmed = trim((string)$value);
                $params[] = ($trimmed === '' && in_array($key, ['account_name', 'instructions_ar', 'instructions_en'], true)) ? null : $trimmed;
            }
            $types .= $type;
        }

        if (!$fields) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'مفيش حاجة تتغير.', 'Nothing to change.')];
        }

        $params[] = $id;
        $types .= 'i';

        $stmt = mysqli_prepare($con, "UPDATE payment_accounts SET " . implode(', ', $fields) . " WHERE id = ?");
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        return ['status' => 'success', 'message' => app_offline_text($con, 'تم الحفظ.', 'Saved.')];
    }
}

/**
 * Deactivates rather than deletes when the account has ever been paid into: a filed request
 * points at the account it named, and that trail is what answers "where did my money go".
 */
if (!function_exists('adminDeletePaymentAccount')) {
    function adminDeletePaymentAccount($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => app_offline_text($con, 'حدد الوسيلة.', 'Specify the account.')];

        $used = mysqli_prepare($con, "SELECT id FROM offline_payment_requests WHERE payment_account_id = ? LIMIT 1");
        $isUsed = false;
        if ($used) {
            mysqli_stmt_bind_param($used, "i", $id);
            mysqli_stmt_execute($used);
            $isUsed = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($used));
        }

        if ($isUsed) {
            $stmt = mysqli_prepare($con, "UPDATE payment_accounts SET is_active = 0 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            return [
                'status' => 'success',
                'message' => app_offline_text(
                    $con,
                    'الوسيلة دي عليها تحويلات قديمة، فاتوقفت بدل ما تتمسح عشان السجل يفضل.',
                    'This account has transfers against it, so it was deactivated rather than deleted to keep the record.'
                ),
                'data' => ['deactivated' => true],
            ];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM payment_accounts WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        return ['status' => 'success', 'message' => app_offline_text($con, 'تم الحذف.', 'Deleted.'), 'data' => ['deactivated' => false]];
    }
}
