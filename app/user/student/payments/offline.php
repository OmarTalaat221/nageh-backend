<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../../helpers/offline_payments.php';

/**
 * The student half of offline payments: where to send the money, what it should come to, and
 * what happened to the receipt once it was sent.
 */

if (!function_exists('offline_student_id')) {
    function offline_student_id($studentData): int {
        if (is_array($studentData)) return (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        if (is_object($studentData)) return (int)($studentData->id ?? $studentData->student_id ?? 0);
        return 0;
    }
}

if (!function_exists('studentListPaymentAccounts')) {
    function studentListPaymentAccounts($con) {
        return [
            'status' => 'success',
            'data' => payment_accounts_list($con, true),
        ];
    }
}

/**
 * The price before the student commits to anything. Read-only, and it spends nothing - a student
 * can check a code as often as they like.
 */
if (!function_exists('studentQuoteOfflinePayment')) {
    function studentQuoteOfflinePayment($con, $studentData = null) {
        $studentId = offline_student_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('student_auth_required')];
        }

        $body = read_json_body();
        $itemType = strtolower(trim($body['item_type'] ?? $_GET['item_type'] ?? 'course'));
        $itemId = (int)($body['item_id'] ?? $_GET['item_id'] ?? 0);
        $code = trim($body['discount_code'] ?? $_GET['discount_code'] ?? '');

        if (!in_array($itemType, ['course', 'book'], true) || $itemId <= 0) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'اختر الكورس أو الكتاب الأول.', 'Choose the course or book first.')];
        }

        if (payments_student_already_has_access($con, $studentId, $itemType, $itemId)) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'انت مشترك في ده بالفعل.', 'You already have access to this.')];
        }

        $quote = offline_payment_quote($con, $studentId, $itemType, $itemId, $code);
        if (empty($quote['ok'])) {
            return ['status' => 'error', 'message' => $quote['message']];
        }

        unset($quote['ok']);
        $quote['accounts'] = payment_accounts_list($con, true);

        return ['status' => 'success', 'data' => $quote];
    }
}

/**
 * Filing the request. Grants nothing - it joins a queue.
 */
if (!function_exists('studentCreateOfflinePayment')) {
    function studentCreateOfflinePayment($con, $studentData = null) {
        $studentId = offline_student_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('student_auth_required')];
        }

        $body = read_json_body();
        $itemType = strtolower(trim($body['item_type'] ?? 'course'));
        $itemId = (int)($body['item_id'] ?? 0);

        if (!in_array($itemType, ['course', 'book'], true) || $itemId <= 0) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'اختر الكورس أو الكتاب الأول.', 'Choose the course or book first.')];
        }

        if (payments_student_already_has_access($con, $studentId, $itemType, $itemId)) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'انت مشترك في ده بالفعل.', 'You already have access to this.')];
        }

        // One open request per item. Without this a student who does not see their first request
        // files three more, and someone ends up approving the same payment twice.
        $dupe = mysqli_prepare($con, "SELECT id FROM offline_payment_requests
            WHERE student_id = ? AND item_type = ? AND item_id = ? AND status = 'pending' LIMIT 1");
        if ($dupe) {
            mysqli_stmt_bind_param($dupe, "isi", $studentId, $itemType, $itemId);
            mysqli_stmt_execute($dupe);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($dupe))) {
                return ['status' => 'error', 'message' => app_offline_text(
                    $con,
                    'عندك طلب دفع لنفس الحاجة لسه تحت المراجعة.',
                    'You already have a payment request for this under review.'
                )];
            }
        }

        $quote = offline_payment_quote($con, $studentId, $itemType, $itemId, trim($body['discount_code'] ?? ''));
        if (empty($quote['ok'])) {
            return ['status' => 'error', 'message' => $quote['message']];
        }

        $receipt = process_image_input($body['receipt_image'] ?? null, 'receipts');
        if (empty($receipt)) {
            return ['status' => 'error', 'message' => app_offline_text(
                $con,
                'ارفق صورة إيصال التحويل — من غيرها مفيش حاجة تتراجع.',
                'Attach a picture of the transfer receipt - there is nothing to review without it.'
            )];
        }

        $amount = (float)($body['amount'] ?? $quote['amount_due']);
        if ($amount <= 0) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'اكتب المبلغ اللي حوّلته.', 'Enter the amount you transferred.')];
        }

        $accountId = (int)($body['payment_account_id'] ?? 0);
        if ($accountId > 0) {
            $account = payment_account_find($con, $accountId);
            if (!$account || (int)$account['is_active'] !== 1) {
                return ['status' => 'error', 'message' => app_offline_text($con, 'وسيلة الدفع دي مش متاحة.', 'That payment method is not available.')];
            }
        } else {
            $accountId = null;
        }

        $senderName = substr(trim((string)($body['sender_name'] ?? '')), 0, 191) ?: null;
        $reference = substr(trim((string)($body['sender_reference'] ?? '')), 0, 191) ?: null;
        $note = trim((string)($body['student_note'] ?? '')) ?: null;

        // Accepted as a plain date or datetime; anything unparseable is dropped rather than
        // stored as a zero date that would read as 1970 on the reviewer's screen.
        $transferredAt = null;
        $rawDate = trim((string)($body['transferred_at'] ?? ''));
        if ($rawDate !== '') {
            $ts = strtotime($rawDate);
            if ($ts !== false) $transferredAt = date('Y-m-d H:i:s', $ts);
        }

        $stmt = mysqli_prepare($con, "INSERT INTO offline_payment_requests
            (student_id, item_type, item_id, payment_account_id, original_amount, discount_code_id,
             discount_amount, amount, sender_name, sender_reference, transferred_at, receipt_image, student_note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        }

        $original = (float)$quote['original_amount'];
        $discountAmount = (float)$quote['discount_amount'];
        $discountCodeId = $quote['discount_code_id'];

        mysqli_stmt_bind_param(
            $stmt, "isiididdsssss",
            $studentId, $itemType, $itemId, $accountId, $original, $discountCodeId,
            $discountAmount, $amount, $senderName, $reference, $transferredAt, $receipt, $note
        );

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        }

        $id = mysqli_insert_id($con);

        if (function_exists('notify_admins')) {
            notify_admins($con, 'طلب دفع يدوي جديد', "طالب رفع إيصال تحويل بمبلغ {$amount} وبيستنى المراجعة.");
        }

        return [
            'status' => 'success',
            'message' => app_offline_text(
                $con,
                'تم استلام طلبك. هيتراجع وهيتفتحلك الوصول بعد التأكيد.',
                'We have your request. It will be reviewed and your access opened once it is confirmed.'
            ),
            'data' => ['id' => $id, 'status' => 'pending'],
        ];
    }
}

if (!function_exists('studentListMyOfflinePayments')) {
    function studentListMyOfflinePayments($con, $studentData = null) {
        $studentId = offline_student_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('student_auth_required')];
        }

        $sql = offline_payment_base_query($con) . " WHERE r.student_id = ? ORDER BY r.id DESC LIMIT 100";
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return ['status' => 'success', 'data' => []];

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = offline_payment_format($con, $row, true);
        }

        return ['status' => 'success', 'data' => $rows];
    }
}

/**
 * Withdrawing a request the student filed by mistake. Only ever their own, and only while it is
 * still pending - a reviewed request is a record, not a draft.
 */
if (!function_exists('studentCancelOfflinePayment')) {
    function studentCancelOfflinePayment($con, $studentData = null) {
        $studentId = offline_student_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('student_auth_required')];
        }

        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'حدد الطلب.', 'Specify the request.')];
        }

        $stmt = mysqli_prepare($con, "UPDATE offline_payment_requests SET status = 'cancelled'
            WHERE id = ? AND student_id = ? AND status = 'pending'");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database error'];
        }
        mysqli_stmt_bind_param($stmt, "ii", $id, $studentId);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return ['status' => 'error', 'message' => app_offline_text(
                $con,
                'الطلب ده مش موجود أو اتراجع عليه بالفعل.',
                'That request does not exist or has already been reviewed.'
            )];
        }

        return ['status' => 'success', 'message' => app_offline_text($con, 'تم إلغاء الطلب.', 'The request was cancelled.')];
    }
}
