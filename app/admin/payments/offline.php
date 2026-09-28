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
        $statistics = offline_payment_statistics($con);

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
        // Only bank transfer exists, so leaving it out means that. It is still checked when sent,
        // so a client that invents a method is told rather than silently having it stored.
        if (array_key_exists('method', $body)) {
            $method = strtolower(trim((string)$body['method']));
            if (!in_array($method, offline_payment_methods(), true)) {
                return app_offline_text($con, 'وسيلة الدفع دي مش معروفة.', 'That payment method is not recognised.');
            }
        }

        // SWIFT/BIC is 8 or 11 characters by the ISO standard. It is what a bank abroad routes on,
        // so a mistyped one fails the transfer at the sending bank with no useful explanation.
        if (array_key_exists('swift_code', $body)) {
            $swift = strtoupper(trim((string)$body['swift_code']));
            if ($swift !== '' && !preg_match('/^[A-Z0-9]{8}(?:[A-Z0-9]{3})?$/', $swift)) {
                return app_offline_text(
                    $con,
                    'كود السويفت لازم يكون 8 أو 11 حرف/رقم (مثال: NBEGEGCX).',
                    'A SWIFT/BIC code is 8 or 11 letters and digits (e.g. NBEGEGCX).'
                );
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

        // Which currency the account receives. Checked against the currencies table rather than
        // just the shape, because "the account takes SAR" is what decides whether a student
        // quoted in riyals is ever shown it - a typo here hides the account from everyone.
        if (array_key_exists('currency_code', $body)) {
            $code = strtoupper(trim((string)$body['currency_code']));
            if ($code !== '') {
                $currency = currency_info($con, $code);
                if (!$currency['is_active']) {
                    return app_offline_text($con, 'العملة دي مش موجودة أو مش مفعّلة.', 'That currency does not exist or is not active.');
                }
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
            (method, label_ar, label_en, bank_name, account_name, account_number, swift_code, currency_code, instructions_ar, instructions_en, is_active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];

        // Only bank transfers exist today, so an omitted method is that rather than a validation
        // error - there is nothing else it could have meant.
        $method = strtolower(trim((string)($body['method'] ?? 'bank'))) ?: 'bank';
        $labelAr = trim((string)$body['label_ar']);
        $labelEn = trim((string)$body['label_en']);
        $bankName = trim((string)($body['bank_name'] ?? '')) ?: null;
        $accountName = trim((string)($body['account_name'] ?? '')) ?: null;
        $accountNumber = trim((string)$body['account_number']);
        $swift = strtoupper(trim((string)($body['swift_code'] ?? ''))) ?: null;
        // An omitted currency means the base currency, which is what every account created before
        // this column existed was already implicitly receiving.
        $currencyCode = strtoupper(trim((string)($body['currency_code'] ?? ''))) ?: pricing_base_currency($con);
        $insAr = trim((string)($body['instructions_ar'] ?? '')) ?: null;
        $insEn = trim((string)($body['instructions_en'] ?? '')) ?: null;
        $isActive = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;
        $sortOrder = (int)($body['sort_order'] ?? 0);

        mysqli_stmt_bind_param($stmt, "ssssssssssii", $method, $labelAr, $labelEn, $bankName, $accountName, $accountNumber, $swift, $currencyCode, $insAr, $insEn, $isActive, $sortOrder);
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
            'method' => 's', 'label_ar' => 's', 'label_en' => 's', 'bank_name' => 's',
            'account_name' => 's', 'account_number' => 's', 'swift_code' => 's',
            'currency_code' => 's', 'instructions_ar' => 's', 'instructions_en' => 's',
            'is_active' => 'i', 'sort_order' => 'i',
        ];

        // Fields that are genuinely optional: clearing one stores NULL rather than an empty
        // string, so "no SWIFT code" and "a SWIFT code of nothing" are not two different things.
        $nullable = ['bank_name', 'account_name', 'swift_code', 'instructions_ar', 'instructions_en'];

        foreach ($map as $key => $type) {
            if (!array_key_exists($key, $body)) continue;
            $fields[] = "`{$key}` = ?";
            $value = $body[$key];
            if ($type === 'i') {
                $params[] = (int)$value;
            } else {
                $trimmed = trim((string)$value);
                if ($key === 'swift_code' || $key === 'currency_code') $trimmed = strtoupper($trimmed);
                // `currency_code` is NOT NULL, so an edit that clears it means "back to the base
                // currency" rather than a constraint violation.
                if ($key === 'currency_code' && $trimmed === '') $trimmed = pricing_base_currency($con);
                $params[] = ($trimmed === '' && in_array($key, $nullable, true)) ? null : $trimmed;
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
