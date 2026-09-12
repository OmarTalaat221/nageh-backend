<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/hr_policy.php';
require_once __DIR__ . '/../../../helpers/attendance_notifications.php';
require_once __DIR__ . '/../sessions/manage.php';
require_once __DIR__ . '/../../admin/attendance/manage.php';

/**
 * Teacher requests an absence-with-permission or a late-arrival notice for one occurrence of
 * one of their own sessions. Always reviewed by the admin (see adminReviewAttendanceExceptionRequest).
 */
if (!function_exists('teacherRequestAttendanceException')) {
    function teacherRequestAttendanceException($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($body['session_id'] ?? 0);
        $occurrenceDate = trim($body['occurrence_date'] ?? $body['date'] ?? '');
        $requestType = trim($body['request_type'] ?? '');
        $reason = trim($body['reason'] ?? '');
        $expectedMinutesLate = isset($body['expected_minutes_late']) ? (int)$body['expected_minutes_late'] : null;

        if ($sessionId <= 0 || empty($occurrenceDate) || strtotime($occurrenceDate) === false) {
            return ['status' => 'error', 'message' => trans('session_id_and_date_required')];
        }
        if (!in_array($requestType, ['absence_permission', 'late_notice'], true)) {
            return ['status' => 'error', 'message' => trans('request_type_required')];
        }
        if (empty($reason)) {
            return ['status' => 'error', 'message' => trans('reason_required')];
        }
        if ($requestType === 'late_notice' && (!$expectedMinutesLate || $expectedMinutesLate <= 0)) {
            return ['status' => 'error', 'message' => trans('expected_minutes_late_required')];
        }

        $occurrenceDate = date('Y-m-d', strtotime($occurrenceDate));

        $checkStmt = mysqli_prepare($con, "SELECT id FROM class_sessions WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $sessionId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }

        $dupStmt = mysqli_prepare($con, "SELECT id FROM attendance_exception_requests WHERE session_id = ? AND occurrence_date = ? AND requested_by_type = 'teacher' AND requested_by_id = ? AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($dupStmt, "isi", $sessionId, $occurrenceDate, $teacherId);
        mysqli_stmt_execute($dupStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($dupStmt))) {
            return ['status' => 'error', 'message' => trans('request_already_pending')];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO attendance_exception_requests (session_id, occurrence_date, requested_by_type, requested_by_id, request_type, reason, expected_minutes_late) VALUES (?, ?, 'teacher', ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isissi", $sessionId, $occurrenceDate, $teacherId, $requestType, $reason, $expectedMinutesLate);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to send request: ' . mysqli_error($con)];
        }

        $requestId = (int)mysqli_insert_id($con);
        notify_of_attendance_exception_request_created($con, 'teacher', $teacherId, $requestType, $requestId);

        return ['status' => 'success', 'message' => trans('request_sent_success'), 'data' => get_attendance_exception_request_details($con, $requestId)];
    }
}

if (!function_exists('teacherListMyAttendanceExceptionRequests')) {
    function teacherListMyAttendanceExceptionRequests($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $stmt = mysqli_prepare($con, "SELECT id FROM attendance_exception_requests WHERE requested_by_type = 'teacher' AND requested_by_id = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_attendance_exception_request_details($con, (int)$row['id']);
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('teacherGetWallet')) {
    function teacherGetWallet($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $balanceRes = mysqli_query($con, "SELECT wallet_balance FROM teachers WHERE id = {$teacherId} LIMIT 1");
        $balance = (float)(mysqli_fetch_assoc($balanceRes)['wallet_balance'] ?? 0);

        return [
            'status' => 'success',
            'data' => [
                'wallet_balance' => $balance,
                'pay_rate' => get_teacher_pay_rate($con, $teacherId),
            ],
        ];
    }
}

if (!function_exists('teacherListWalletTransactions')) {
    function teacherListWalletTransactions($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM teacher_wallet_transactions WHERE teacher_id = ?");
        mysqli_stmt_bind_param($countStmt, "i", $teacherId);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $stmt = mysqli_prepare($con, "SELECT * FROM teacher_wallet_transactions WHERE teacher_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($stmt, "iii", $teacherId, $limit, $offset);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['amount'] = (float)$row['amount'];
            $row['reference_id'] = $row['reference_id'] ? (int)$row['reference_id'] : null;
            $row['can_appeal'] = ($row['type'] === 'fine');
            $items[] = $row;
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('teacherCreateDeductionAppeal')) {
    function teacherCreateDeductionAppeal($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $walletTransactionId = (int)($body['wallet_transaction_id'] ?? 0);
        $reason = trim($body['reason'] ?? '');

        if ($walletTransactionId <= 0) {
            return ['status' => 'error', 'message' => trans('wallet_transaction_id_required')];
        }
        if (empty($reason)) {
            return ['status' => 'error', 'message' => trans('reason_required')];
        }

        $txnStmt = mysqli_prepare($con, "SELECT id, type FROM teacher_wallet_transactions WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($txnStmt, "ii", $walletTransactionId, $teacherId);
        mysqli_stmt_execute($txnStmt);
        $txn = mysqli_fetch_assoc(mysqli_stmt_get_result($txnStmt));
        if (!$txn) {
            return ['status' => 'error', 'message' => trans('wallet_transaction_not_found')];
        }
        if ($txn['type'] !== 'fine') {
            return ['status' => 'error', 'message' => trans('only_fines_can_be_appealed')];
        }

        $dupStmt = mysqli_prepare($con, "SELECT id FROM teacher_deduction_appeals WHERE wallet_transaction_id = ? LIMIT 1");
        mysqli_stmt_bind_param($dupStmt, "i", $walletTransactionId);
        mysqli_stmt_execute($dupStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($dupStmt))) {
            return ['status' => 'error', 'message' => trans('appeal_already_exists')];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO teacher_deduction_appeals (wallet_transaction_id, teacher_id, reason) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iis", $walletTransactionId, $teacherId, $reason);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to submit appeal: ' . mysqli_error($con)];
        }

        $appealId = (int)mysqli_insert_id($con);
        notify_admins($con, 'اعتراض جديد على خصم', "معلم قدّم اعتراضًا على خصم من محفظته.", ['type' => 'deduction_appeal_created', 'appeal_id' => $appealId]);

        return ['status' => 'success', 'message' => trans('appeal_submitted_success'), 'data' => get_deduction_appeal_details($con, $appealId)];
    }
}

if (!function_exists('teacherListMyAppeals')) {
    function teacherListMyAppeals($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $stmt = mysqli_prepare($con, "SELECT id FROM teacher_deduction_appeals WHERE teacher_id = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_deduction_appeal_details($con, (int)$row['id']);
        }

        return ['status' => 'success', 'data' => $items];
    }
}
