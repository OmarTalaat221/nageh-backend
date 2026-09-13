<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/hr_policy.php';
require_once __DIR__ . '/../../../helpers/attendance_notifications.php';
require_once __DIR__ . '/../../teacher/sessions/manage.php';

if (!function_exists('get_attendance_exception_request_details')) {
    function get_attendance_exception_request_details($con, int $reqId) {
        $stmt = mysqli_prepare($con, "SELECT * FROM attendance_exception_requests WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $reqId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['session_id'] = (int)$row['session_id'];
        $row['requested_by_id'] = (int)$row['requested_by_id'];
        $row['expected_minutes_late'] = $row['expected_minutes_late'] !== null ? (int)$row['expected_minutes_late'] : null;
        $row['reviewed_by_admin_id'] = $row['reviewed_by_admin_id'] ? (int)$row['reviewed_by_admin_id'] : null;
        $row['session'] = get_class_session_details($con, $row['session_id']);

        if ($row['requested_by_type'] === 'student') {
            $sStmt = mysqli_prepare($con, "SELECT id, name, email, image FROM students WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($sStmt, "i", $row['requested_by_id']);
            mysqli_stmt_execute($sStmt);
            $row['requester'] = mysqli_fetch_assoc(mysqli_stmt_get_result($sStmt)) ?: null;
        } else {
            $tStmt = mysqli_prepare($con, "SELECT id, name, email, image FROM teachers WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($tStmt, "i", $row['requested_by_id']);
            mysqli_stmt_execute($tStmt);
            $row['requester'] = mysqli_fetch_assoc(mysqli_stmt_get_result($tStmt)) ?: null;
        }

        return $row;
    }
}

if (!function_exists('adminListAttendanceExceptionRequests')) {
    function adminListAttendanceExceptionRequests($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = strtolower(trim($_GET['status'] ?? ''));
        $requestedByType = strtolower(trim($_GET['requested_by_type'] ?? ''));
        $requestType = strtolower(trim($_GET['request_type'] ?? ''));

        $where = ["1=1"];
        $params = [];
        $types = "";
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where[] = "status = ?"; $params[] = $status; $types .= "s";
        }
        if (in_array($requestedByType, ['teacher', 'student'], true)) {
            $where[] = "requested_by_type = ?"; $params[] = $requestedByType; $types .= "s";
        }
        if (in_array($requestType, ['absence_permission', 'late_notice'], true)) {
            $where[] = "request_type = ?"; $params[] = $requestType; $types .= "s";
        }
        $whereClause = "WHERE " . implode(" AND ", $where);

        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM attendance_exception_requests {$whereClause}");
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $stmt = mysqli_prepare($con, "SELECT id FROM attendance_exception_requests {$whereClause} ORDER BY id DESC LIMIT ? OFFSET ?");
        $bindTypes = $types . "ii";
        $bindParams = array_merge($params, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_attendance_exception_request_details($con, (int)$row['id']);
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminReviewAttendanceExceptionRequest')) {
    function adminReviewAttendanceExceptionRequest($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $decision = strtolower(trim($body['decision'] ?? $body['status'] ?? ''));
        $note = trim($body['note'] ?? $body['admin_note'] ?? '');

        if ($id <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
            return ['status' => 'error', 'message' => trans('id_and_decision_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT * FROM attendance_exception_requests WHERE id = ? AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $reqRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$reqRow) {
            return ['status' => 'error', 'message' => trans('request_not_found_or_reviewed')];
        }

        $uStmt = mysqli_prepare($con, "UPDATE attendance_exception_requests SET status = ?, admin_note = ?, reviewed_by_admin_id = ?, reviewed_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, "ssii", $decision, $note, $adminId, $id);
        mysqli_stmt_execute($uStmt);

        notify_of_attendance_exception_request_reviewed(
            $con,
            $reqRow['requested_by_type'],
            (int)$reqRow['requested_by_id'],
            $reqRow['request_type'],
            $decision,
            $id
        );

        return ['status' => 'success', 'message' => trans('request_reviewed_success'), 'data' => get_attendance_exception_request_details($con, $id)];
    }
}

if (!function_exists('adminListTeacherWallets')) {
    function adminListTeacherWallets($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = "WHERE 1=1";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $where .= " AND (name LIKE ? OR email LIKE ?)";
            $pattern = '%' . $search . '%';
            $params[] = $pattern; $params[] = $pattern; $types .= "ss";
        }

        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM teachers {$where}");
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT id, name, email, image, is_active, is_attendance_suspended, wallet_balance FROM teachers {$where} ORDER BY wallet_balance DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        $bindTypes = $types . "ii";
        $bindParams = array_merge($params, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['is_active'] = (int)$row['is_active'];
            $row['is_attendance_suspended'] = (int)$row['is_attendance_suspended'];
            $row['wallet_balance'] = (float)$row['wallet_balance'];
            $row['pay_rate'] = get_teacher_pay_rate($con, $row['id']);
            $items[] = format_response_images($row);
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminGetTeacherWalletLedger')) {
    function adminGetTeacherWalletLedger($con) {
        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_id_required')];
        }

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
            $row['teacher_id'] = (int)$row['teacher_id'];
            $row['amount'] = (float)$row['amount'];
            $row['reference_id'] = $row['reference_id'] ? (int)$row['reference_id'] : null;
            $row['created_by_id'] = $row['created_by_id'] ? (int)$row['created_by_id'] : null;
            $items[] = $row;
        }

        $balanceRes = mysqli_query($con, "SELECT wallet_balance FROM teachers WHERE id = {$teacherId} LIMIT 1");
        $balance = (float)(mysqli_fetch_assoc($balanceRes)['wallet_balance'] ?? 0);

        $response = build_paginated_response($items, $page, $limit, $totalRecords);
        $response['wallet_balance'] = $balance;
        return $response;
    }
}

if (!function_exists('adminLiftTeacherAttendanceSuspension')) {
    function adminLiftTeacherAttendanceSuspension($con) {
        $body = read_json_body();
        $teacherId = (int)($body['teacher_id'] ?? 0);

        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('teacher_not_found')];
        }

        $stmt = mysqli_prepare($con, "UPDATE teachers SET is_attendance_suspended = 0 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);

        return ['status' => 'success', 'message' => trans('attendance_suspension_lifted_success')];
    }
}

if (!function_exists('adminAdjustTeacherWallet')) {
    function adminAdjustTeacherWallet($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();
        $teacherId = (int)($body['teacher_id'] ?? 0);
        $amount = isset($body['amount']) ? (float)$body['amount'] : null;
        $note = trim($body['note'] ?? '');

        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_id_required')];
        }
        if ($amount === null || $amount == 0) {
            return ['status' => 'error', 'message' => trans('amount_required')];
        }
        if (empty($note)) {
            return ['status' => 'error', 'message' => trans('note_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('teacher_not_found')];
        }

        adjust_teacher_wallet($con, $teacherId, 'admin_adjustment', $amount, 'manual', null, $note, 'admin', $adminId);

        $balanceRes = mysqli_query($con, "SELECT wallet_balance FROM teachers WHERE id = {$teacherId} LIMIT 1");
        $balance = (float)(mysqli_fetch_assoc($balanceRes)['wallet_balance'] ?? 0);

        return ['status' => 'success', 'message' => trans('wallet_adjusted_success'), 'data' => ['wallet_balance' => $balance]];
    }
}

if (!function_exists('get_deduction_appeal_details')) {
    function get_deduction_appeal_details($con, int $appealId) {
        $stmt = mysqli_prepare($con, "SELECT * FROM teacher_deduction_appeals WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $appealId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['wallet_transaction_id'] = (int)$row['wallet_transaction_id'];
        $row['teacher_id'] = (int)$row['teacher_id'];
        $row['reviewed_by_admin_id'] = $row['reviewed_by_admin_id'] ? (int)$row['reviewed_by_admin_id'] : null;

        $tStmt = mysqli_prepare($con, "SELECT id, name, email, image FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($tStmt, "i", $row['teacher_id']);
        mysqli_stmt_execute($tStmt);
        $row['teacher'] = mysqli_fetch_assoc(mysqli_stmt_get_result($tStmt)) ?: null;

        $txnStmt = mysqli_prepare($con, "SELECT * FROM teacher_wallet_transactions WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($txnStmt, "i", $row['wallet_transaction_id']);
        mysqli_stmt_execute($txnStmt);
        $txn = mysqli_fetch_assoc(mysqli_stmt_get_result($txnStmt));
        if ($txn) $txn['amount'] = (float)$txn['amount'];
        $row['wallet_transaction'] = $txn ?: null;

        return $row;
    }
}

if (!function_exists('adminListDeductionAppeals')) {
    function adminListDeductionAppeals($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = strtolower(trim($_GET['status'] ?? ''));

        $where = "WHERE 1=1";
        $params = [];
        $types = "";
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where .= " AND status = ?"; $params[] = $status; $types .= "s";
        }

        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM teacher_deduction_appeals {$where}");
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $stmt = mysqli_prepare($con, "SELECT id FROM teacher_deduction_appeals {$where} ORDER BY id DESC LIMIT ? OFFSET ?");
        $bindTypes = $types . "ii";
        $bindParams = array_merge($params, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_deduction_appeal_details($con, (int)$row['id']);
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminReviewDeductionAppeal')) {
    function adminReviewDeductionAppeal($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $decision = strtolower(trim($body['decision'] ?? $body['status'] ?? ''));
        $note = trim($body['note'] ?? $body['admin_note'] ?? '');

        if ($id <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
            return ['status' => 'error', 'message' => trans('id_and_decision_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT * FROM teacher_deduction_appeals WHERE id = ? AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $appeal = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$appeal) {
            return ['status' => 'error', 'message' => trans('appeal_not_found_or_reviewed')];
        }

        $uStmt = mysqli_prepare($con, "UPDATE teacher_deduction_appeals SET status = ?, admin_note = ?, reviewed_by_admin_id = ?, reviewed_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, "ssii", $decision, $note, $adminId, $id);
        mysqli_stmt_execute($uStmt);

        if ($decision === 'approved') {
            $txnStmt = mysqli_prepare($con, "SELECT teacher_id, amount FROM teacher_wallet_transactions WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($txnStmt, "i", $appeal['wallet_transaction_id']);
            mysqli_stmt_execute($txnStmt);
            $txn = mysqli_fetch_assoc(mysqli_stmt_get_result($txnStmt));
            if ($txn) {
                $refundAmount = abs((float)$txn['amount']);
                adjust_teacher_wallet($con, (int)$txn['teacher_id'], 'refund', $refundAmount, 'appeal', $id, 'استرجاع خصم بعد قبول الاعتراض', 'admin', $adminId);
            }
        }

        notify_of_deduction_appeal_reviewed($con, (int)$appeal['teacher_id'], $decision);

        return ['status' => 'success', 'message' => trans('appeal_reviewed_success'), 'data' => get_deduction_appeal_details($con, $id)];
    }
}
