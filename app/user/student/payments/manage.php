<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/easykash.php';

if (!function_exists('payments_resolve_purchasable_item')) {
    function payments_resolve_purchasable_item($con, string $itemType, int $itemId) {
        if ($itemType === 'course') {
            $stmt = mysqli_prepare($con, "SELECT id, price, is_active FROM courses WHERE id = ? LIMIT 1");
        } elseif ($itemType === 'book') {
            $stmt = mysqli_prepare($con, "SELECT id, price, is_active FROM course_books WHERE id = ? LIMIT 1");
        } else {
            return null;
        }
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $itemId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['price'] = (float)$row['price'];
        $row['is_active'] = (int)$row['is_active'];
        return $row;
    }
}

if (!function_exists('payments_student_already_has_access')) {
    function payments_student_already_has_access($con, int $studentId, string $itemType, int $itemId): bool {
        if ($itemType === 'course') {
            $stmt = mysqli_prepare($con, "SELECT ss.id FROM student_subscription_courses ssc
                JOIN student_subscriptions ss ON ss.id = ssc.student_subscription_id
                WHERE ss.student_id = ? AND ssc.course_id = ? AND ss.is_active = 1
                AND (ss.end_date IS NULL OR ss.end_date >= NOW()) LIMIT 1");
        } else {
            $stmt = mysqli_prepare($con, "SELECT id FROM student_book_subscriptions
                WHERE student_id = ? AND book_id = ? AND is_active = 1 LIMIT 1");
        }
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $itemId);
        mysqli_stmt_execute($stmt);
        return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

if (!function_exists('payments_grant_access')) {
    function payments_grant_access($con, int $studentId, string $itemType, int $itemId, float $price): ?int {
        if ($itemType === 'course') {
            $stmt = mysqli_prepare($con, "INSERT INTO student_subscriptions (student_id, subscription_type, start_date, is_active) VALUES (?, 'course', NOW(), 1)");
            mysqli_stmt_bind_param($stmt, "i", $studentId);
            if (!mysqli_stmt_execute($stmt)) return null;
            $subId = mysqli_insert_id($con);

            $linkStmt = mysqli_prepare($con, "INSERT INTO student_subscription_courses (student_subscription_id, course_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE course_id = course_id");
            mysqli_stmt_bind_param($linkStmt, "ii", $subId, $itemId);
            mysqli_stmt_execute($linkStmt);

            return $subId;
        }

        $stmt = mysqli_prepare($con, "INSERT INTO student_book_subscriptions (student_id, book_id, granted_by_type, granted_by_id, price, is_active) VALUES (?, ?, 'student', ?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "iiid", $studentId, $itemId, $studentId, $price);
        if (!mysqli_stmt_execute($stmt)) return null;
        return mysqli_insert_id($con);
    }
}

if (!function_exists('studentInitiatePayment')) {
    function studentInitiatePayment($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => trans('student_auth_required')];
        }

        $body = read_json_body();
        $itemType = strtolower(trim($body['item_type'] ?? ''));
        $itemId = (int)($body['item_id'] ?? 0);

        if (!in_array($itemType, ['course', 'book'], true) || $itemId <= 0) {
            return ['status' => 'error', 'message' => trans('invalid_request') ?? 'A valid item_type (course|book) and item_id are required'];
        }

        $item = payments_resolve_purchasable_item($con, $itemType, $itemId);
        if (!$item || (int)$item['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('item_not_found_or_unavailable')];
        }
        if ($item['price'] <= 0) {
            return ['status' => 'error', 'message' => trans('item_is_free')];
        }
        if (payments_student_already_has_access($con, $stId, $itemType, $itemId)) {
            return ['status' => 'error', 'message' => trans('already_have_access')];
        }

        $stStmt = mysqli_prepare($con, "SELECT name, email, phone FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stStmt, "i", $stId);
        mysqli_stmt_execute($stStmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));
        if (!$student) {
            return ['status' => 'error', 'message' => trans('student_not_found')];
        }

        $config = easykash_config();
        $redirectUrl = trim($body['redirect_url'] ?? '') ?: $config['default_redirect_url'];

        $insStmt = mysqli_prepare($con, "INSERT INTO payment_transactions (student_id, item_type, item_id, amount, currency, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $currency = $config['currency'];
        mysqli_stmt_bind_param($insStmt, "isids", $stId, $itemType, $itemId, $item['price'], $currency);
        if (!mysqli_stmt_execute($insStmt)) {
            return ['status' => 'error', 'message' => 'Failed to create payment transaction: ' . mysqli_error($con)];
        }
        $transactionId = mysqli_insert_id($con);

        $result = easykash_create_payment([
            'amount' => $item['price'],
            'currency' => $currency,
            'name' => $student['name'],
            'email' => $student['email'],
            'mobile' => $student['phone'] ?? '',
            'redirectUrl' => $redirectUrl,
            'customerReference' => $transactionId,
        ]);

        if ($result['status'] !== 'success') {
            mysqli_query($con, "UPDATE payment_transactions SET status = 'failed' WHERE id = {$transactionId}");
            return ['status' => 'error', 'message' => 'Failed to initiate payment with EasyKash', 'details' => $result['message'] ?? null];
        }

        return [
            'status' => 'success',
            'message' => trans('payment_initiated'),
            'data' => [
                'transaction_id' => $transactionId,
                'amount' => $item['price'],
                'currency' => $currency,
                'payment_url' => $result['redirectUrl'],
            ],
        ];
    }
}

if (!function_exists('payments_process_easykash_result')) {
    function payments_process_easykash_result($con, array $data, int $requireStudentId = 0, bool $requireSignature = true): array {
        $rawCallback = json_encode($data, JSON_UNESCAPED_UNICODE);
        $transactionId = (int)easykash_ci_get($data, 'customerReference');

        if ($requireSignature && !easykash_verify_callback_signature($data)) {
            if ($transactionId > 0) {
                $updStmt = mysqli_prepare($con, "UPDATE payment_transactions SET raw_callback = ? WHERE id = ?");
                mysqli_stmt_bind_param($updStmt, "si", $rawCallback, $transactionId);
                mysqli_stmt_execute($updStmt);
            }
            return ['status' => 'error', 'message' => 'Invalid signature'];
        }

        if ($transactionId <= 0) {
            return ['status' => 'error', 'message' => 'Missing customerReference'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, student_id, item_type, item_id, amount, status FROM payment_transactions WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $transactionId);
        mysqli_stmt_execute($stmt);
        $tx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$tx) {
            return ['status' => 'error', 'message' => 'Unknown transaction'];
        }

        if ($requireStudentId > 0 && (int)$tx['student_id'] !== $requireStudentId) {
            return ['status' => 'error', 'message' => 'This transaction does not belong to you'];
        }

        if ($tx['status'] === 'paid') {
            return [
                'status' => 'success',
                'message' => 'Transaction already processed',
                'data' => ['transaction_id' => $transactionId, 'payment_status' => 'paid'],
            ];
        }

        $paidStatus = strtoupper(trim(easykash_ci_get($data, 'status')));
        $easykashRef = easykash_ci_get($data, 'easykashRef') ?: (easykash_ci_get($data, 'providerRefNum') ?: null);
        $paymentMethod = easykash_ci_get($data, 'PaymentMethod') ?: null;

        if ($paidStatus !== 'PAID') {
            $newStatus = ($paidStatus === 'CANCELLED') ? 'cancelled' : 'failed';
            $updStmt = mysqli_prepare($con, "UPDATE payment_transactions SET status = ?, easykash_ref = ?, payment_method = ?, raw_callback = ? WHERE id = ?");
            mysqli_stmt_bind_param($updStmt, "ssssi", $newStatus, $easykashRef, $paymentMethod, $rawCallback, $transactionId);
            mysqli_stmt_execute($updStmt);
            return [
                'status' => 'success',
                'message' => 'Callback recorded',
                'data' => ['transaction_id' => $transactionId, 'payment_status' => $newStatus],
            ];
        }

        $grantedRefId = payments_grant_access($con, (int)$tx['student_id'], $tx['item_type'], (int)$tx['item_id'], (float)$tx['amount']);

        require_once __DIR__ . '/../affiliate/manage.php';
        if (function_exists('createPendingAffiliateCommission')) {
            createPendingAffiliateCommission($con, (int)$tx['student_id'], $tx['item_type'], (int)$tx['item_id'], (float)$tx['amount']);
        }

        $updStmt = mysqli_prepare($con, "UPDATE payment_transactions SET status = 'paid', easykash_ref = ?, payment_method = ?, granted_ref_id = ?, raw_callback = ?, paid_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($updStmt, "ssisi", $easykashRef, $paymentMethod, $grantedRefId, $rawCallback, $transactionId);
        mysqli_stmt_execute($updStmt);

        return [
            'status' => 'success',
            'message' => trans('payment_confirmed_access_granted'),
            'data' => ['transaction_id' => $transactionId, 'payment_status' => 'paid'],
        ];
    }
}

if (!function_exists('paymentsEasykashCallback')) {
    function paymentsEasykashCallback($con) {
        // Merge GET and POST: EasyKash's own docs are inconsistent about whether the
        // callback arrives as query params or a POST body, so accept either.
        $data = array_merge($_GET, $_POST);

        // Always log the raw callback attempt, even if signature verification fails below,
        // so a bad/rejected callback can be diagnosed instead of silently vanishing.
        easykash_log('Callback received: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        return payments_process_easykash_result($con, $data);
    }
}

if (!function_exists('studentConfirmPayment')) {
    function studentConfirmPayment($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

      $data = array_merge($_GET, read_json_body());
        easykash_log('Frontend confirm received (student ' . $stId . '): ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        return payments_process_easykash_result($con, $data, $stId, false);
    }
}

if (!function_exists('studentGetPaymentStatus')) {
    function studentGetPaymentStatus($con, $studentData = null, int $transactionId = 0) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0 || $transactionId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid request'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, item_type, item_id, amount, currency, status, created_at, paid_at FROM payment_transactions WHERE id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $transactionId, $stId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$row) {
            return ['status' => 'error', 'message' => 'Transaction not found'];
        }

        $row['id'] = (int)$row['id'];
        $row['item_id'] = (int)$row['item_id'];
        $row['amount'] = (float)$row['amount'];

        return ['status' => 'success', 'data' => $row];
    }
}
