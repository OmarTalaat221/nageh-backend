<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/notifications.php';
require_once __DIR__ . '/../../../helpers/admin_logs.php';

require_once __DIR__ . '/../../user/student/affiliate/manage.php';

if (!function_exists('log_admin_action')) {
    function log_admin_action($con, $adminId, string $action, string $notes = ''): bool {
        if (function_exists('writeAdminLog')) {
            $adminData = is_array($adminId) ? $adminId : ['id' => (int)$adminId];
            return (bool)writeAdminLog($con, $adminData, $action, 'affiliate', null, null, null, $notes);
        }
        return false;
    }
}

if (!function_exists('adminListAffiliateRequests')) {
    function adminListAffiliateRequests($con, $adminData = null) {
        if (function_exists('ensure_affiliate_commissions_extended_columns')) {
            ensure_affiliate_commissions_extended_columns($con);
        }
        $admId = 0;
        if (is_array($adminData)) {
            $admId = (int)($adminData['id'] ?? $adminData['admin_id'] ?? 0);
        } elseif (is_object($adminData)) {
            $admId = (int)($adminData->id ?? $adminData->admin_id ?? 0);
        }

        if ($admId <= 0) {
            return ['status' => 'error', 'message' => trans('auth_required') ?? 'Admin authentication required'];
        }

        $statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
        $whereClause = "";
        $params = [];
        $types = "";

        if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $whereClause = "WHERE ac.status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }

        $query = "SELECT ac.id, ac.affiliate_id, ac.referred_student_id, ac.item_type, ac.item_id, ac.purchase_amount, ac.amount AS reward_amount, ac.status, ac.notes, ac.commission_date, ac.created_at, ac.updated_at,
                         a.student_id AS referrer_student_id,
                         st_ref.name AS referrer_name, st_ref.email AS referrer_email, st_ref.phone AS referrer_phone, st_ref.points_balance AS referrer_points_balance,
                         st_target.name AS referred_name, st_target.email AS referred_email, st_target.phone AS referred_phone,
                         adm.name AS approved_by_admin_name
                  FROM affiliate_commissions ac
                  JOIN affiliates a ON ac.affiliate_id = a.id
                  LEFT JOIN students st_ref ON a.student_id = st_ref.id
                  LEFT JOIN students st_target ON ac.referred_student_id = st_target.id
                  LEFT JOIN admins adm ON ac.approved_by_admin_id = adm.id
                  {$whereClause}
                  ORDER BY ac.id DESC";

        $stmt = mysqli_prepare($con, $query);
        if ($stmt && !empty($types)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $list = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['affiliate_id'] = (int)$row['affiliate_id'];
                $row['referrer_student_id'] = (int)$row['referrer_student_id'];
                $row['referred_student_id'] = $row['referred_student_id'] ? (int)$row['referred_student_id'] : null;
                $row['item_id'] = $row['item_id'] ? (int)$row['item_id'] : null;
                $row['purchase_amount'] = (float)$row['purchase_amount'];
                $row['reward_amount'] = (float)$row['reward_amount'];
                $row['referrer_points_balance'] = (float)($row['referrer_points_balance'] ?? 0);

                // Get Item Title & Included Details
                $formattedDetails = format_affiliate_item_details($con, $row['item_type'] ?? '', (int)($row['item_id'] ?? 0));
                $row['item_title'] = $formattedDetails['item_title'];
                $row['code_details'] = $formattedDetails['code_details'];
                $row['included_courses'] = $formattedDetails['included_courses'];

                $list[] = $row;
            }

            return [
                'status' => 'success',
                'data' => $list
            ];
        }

        return ['status' => 'error', 'message' => 'Failed to retrieve affiliate requests: ' . mysqli_error($con)];
    }
}

if (!function_exists('adminApproveAffiliateRequest')) {
    function adminApproveAffiliateRequest($con, $adminData = null) {
        $admId = 0;
        if (is_array($adminData)) {
            $admId = (int)($adminData['id'] ?? $adminData['admin_id'] ?? 0);
        } elseif (is_object($adminData)) {
            $admId = (int)($adminData->id ?? $adminData->admin_id ?? 0);
        }

        if ($admId <= 0) {
            return ['status' => 'error', 'message' => trans('auth_required') ?? 'Admin authentication required'];
        }

        $body = read_json_body();
        $commissionId = (int)($body['commission_id'] ?? $body['id'] ?? 0);
        $amount = (float)($body['amount'] ?? $body['reward_amount'] ?? $body['points'] ?? 0.00);
        $notes = trim($body['notes'] ?? '');

        if ($commissionId <= 0) {
            return ['status' => 'error', 'message' => 'Valid commission_id is required'];
        }

        if ($amount < 0) {
            return ['status' => 'error', 'message' => 'Amount/points cannot be negative'];
        }

        // Fetch request
        $stmt = mysqli_prepare($con, "SELECT ac.id, ac.affiliate_id, ac.referred_student_id, ac.item_type, ac.item_id, ac.purchase_amount, ac.status, a.student_id AS referrer_student_id, st_target.name AS referred_name
            FROM affiliate_commissions ac
            JOIN affiliates a ON ac.affiliate_id = a.id
            LEFT JOIN students st_target ON ac.referred_student_id = st_target.id
            WHERE ac.id = ? LIMIT 1");

        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database query preparation failed'];
        }

        mysqli_stmt_bind_param($stmt, "i", $commissionId);
        mysqli_stmt_execute($stmt);
        $req = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$req) {
            return ['status' => 'error', 'message' => 'Affiliate commission request not found'];
        }

        if ($req['status'] === 'approved') {
            return ['status' => 'error', 'message' => 'This request is already approved'];
        }

        $referrerStudentId = (int)$req['referrer_student_id'];
        $referredName = $req['referred_name'] ?? 'طالب';

        // Update commission record
        $upd = mysqli_prepare($con, "UPDATE affiliate_commissions SET amount = ?, status = 'approved', notes = ?, approved_by_admin_id = ?, updated_at = NOW() WHERE id = ?");
        if (!$upd) {
            return ['status' => 'error', 'message' => 'Failed to update commission record'];
        }

        mysqli_stmt_bind_param($upd, "dsii", $amount, $notes, $admId, $commissionId);
        if (!mysqli_stmt_execute($upd)) {
            return ['status' => 'error', 'message' => 'Failed to execute commission update: ' . mysqli_error($con)];
        }

        // Credit referrer student balance
        $updBal = mysqli_prepare($con, "UPDATE students SET points_balance = points_balance + ? WHERE id = ?");
        if ($updBal) {
            mysqli_stmt_bind_param($updBal, "di", $amount, $referrerStudentId);
            mysqli_stmt_execute($updBal);
        }

        // Log point transaction
        $desc = "مكافأة إحالة عن شراء الطالب ({$referredName})";
        $insTx = mysqli_prepare($con, "INSERT INTO student_point_transactions (student_id, points, type, reference_type, reference_id, referred_student_id, description) VALUES (?, ?, 'earn_referral_purchase', 'affiliate_commission', ?, ?, ?)");
        if ($insTx) {
            $refStudentId = (int)$req['referred_student_id'];
            mysqli_stmt_bind_param($insTx, "ddiis", $referrerStudentId, $amount, $commissionId, $refStudentId, $desc);
            mysqli_stmt_execute($insTx);
        }

        // Send notification to referring student
        if (function_exists('createNotification')) {
            $notifTitle = "مكافأة إحالة جديدة!";
            $notifBody = "تم إضافة {$amount} إلى رصيدك من قبل الإدارة بعد شراء الطالب ({$referredName}) لكورس/كتاب!";
            createNotification($con, 'student', $referrerStudentId, $notifTitle, $notifBody, [
                'type' => 'affiliate_reward',
                'amount' => $amount,
                'commission_id' => $commissionId
            ]);
        }

        if (function_exists('log_admin_action')) {
            log_admin_action($con, $admId, 'APPROVE_AFFILIATE_COMMISSION', "Approved affiliate commission #{$commissionId} with reward amount {$amount} for student #{$referrerStudentId}");
        }

        return [
            'status' => 'success',
            'message' => trans('affiliate_reward_approved'),
            'data' => [
                'commission_id' => $commissionId,
                'reward_amount' => $amount,
                'referrer_student_id' => $referrerStudentId
            ]
        ];
    }
}

if (!function_exists('adminRejectAffiliateRequest')) {
    function adminRejectAffiliateRequest($con, $adminData = null) {
        $admId = 0;
        if (is_array($adminData)) {
            $admId = (int)($adminData['id'] ?? $adminData['admin_id'] ?? 0);
        } elseif (is_object($adminData)) {
            $admId = (int)($adminData->id ?? $adminData->admin_id ?? 0);
        }

        if ($admId <= 0) {
            return ['status' => 'error', 'message' => trans('auth_required') ?? 'Admin authentication required'];
        }

        $body = read_json_body();
        $commissionId = (int)($body['commission_id'] ?? $body['id'] ?? 0);
        $notes = trim($body['notes'] ?? '');

        if ($commissionId <= 0) {
            return ['status' => 'error', 'message' => 'Valid commission_id is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, status FROM affiliate_commissions WHERE id = ? LIMIT 1");
        if (!$stmt) return ['status' => 'error', 'message' => 'Query preparation error'];

        mysqli_stmt_bind_param($stmt, "i", $commissionId);
        mysqli_stmt_execute($stmt);
        $req = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$req) {
            return ['status' => 'error', 'message' => 'Affiliate commission request not found'];
        }

        $upd = mysqli_prepare($con, "UPDATE affiliate_commissions SET status = 'rejected', notes = ?, approved_by_admin_id = ?, updated_at = NOW() WHERE id = ?");
        if (!$upd) {
            return ['status' => 'error', 'message' => 'Failed to prepare reject statement'];
        }

        mysqli_stmt_bind_param($upd, "sii", $notes, $admId, $commissionId);
        if (!mysqli_stmt_execute($upd)) {
            return ['status' => 'error', 'message' => 'Failed to execute reject: ' . mysqli_error($con)];
        }

        if (function_exists('log_admin_action')) {
            log_admin_action($con, $admId, 'REJECT_AFFILIATE_COMMISSION', "Rejected affiliate commission #{$commissionId}");
        }

        return [
            'status' => 'success',
            'message' => trans('affiliate_request_rejected')
        ];
    }
}

if (!function_exists('adminDeductStudentPoints')) {
    function adminDeductStudentPoints($con, $adminData = null) {
        $admId = 0;
        if (is_array($adminData)) {
            $admId = (int)($adminData['id'] ?? $adminData['admin_id'] ?? 0);
        } elseif (is_object($adminData)) {
            $admId = (int)($adminData->id ?? $adminData->admin_id ?? 0);
        }

        if ($admId <= 0) {
            return ['status' => 'error', 'message' => trans('auth_required') ?? 'Admin authentication required'];
        }

        $body = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);
        $points = (float)($body['points'] ?? $body['amount'] ?? 0.00);
        $notes = trim($body['notes'] ?? $body['description'] ?? $body['reason'] ?? 'خصم رصيد/نقاط بواسطة الأدمن');

        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Valid student_id is required'];
        }

        if ($points <= 0) {
            return ['status' => 'error', 'message' => 'Points to deduct must be greater than zero'];
        }

        // Fetch student current points balance
        $stmt = mysqli_prepare($con, "SELECT id, name, points_balance FROM students WHERE id = ? LIMIT 1");
        if (!$stmt) return ['status' => 'error', 'message' => 'Database query preparation error'];

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$student) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }

        $currentBalance = (float)$student['points_balance'];
        if ($currentBalance < $points) {
            return [
                'status' => 'error',
                'message' => "رصيد نقاط الطالب غير كافٍ. الرصيد الحالي: {$currentBalance}"
            ];
        }

        $newBalance = $currentBalance - $points;

        // Deduct points
        $upd = mysqli_prepare($con, "UPDATE students SET points_balance = ? WHERE id = ?");
        if (!$upd) return ['status' => 'error', 'message' => 'Failed to prepare balance deduction'];

        mysqli_stmt_bind_param($upd, "di", $newBalance, $studentId);
        if (!mysqli_stmt_execute($upd)) {
            return ['status' => 'error', 'message' => 'Failed to deduct student points: ' . mysqli_error($con)];
        }

        // Log transaction
        $negPoints = -1.0 * $points;
        $insTx = mysqli_prepare($con, "INSERT INTO student_point_transactions (student_id, points, type, description) VALUES (?, ?, 'redeem', ?)");
        if ($insTx) {
            mysqli_stmt_bind_param($insTx, "dds", $studentId, $negPoints, $notes);
            mysqli_stmt_execute($insTx);
        }

        // Send notification to student
        if (function_exists('createNotification')) {
            $notifTitle = "خصم نقاط من حسابك";
            $notifBody = "تم خصم {$points} نقطة/جنيه من رصيدك بواسطة الإدارة. السبب: {$notes}. الرصيد الحالي: {$newBalance}";
            createNotification($con, 'student', $studentId, $notifTitle, $notifBody, [
                'type' => 'points_deducted',
                'deducted_amount' => $points,
                'new_balance' => $newBalance
            ]);
        }

        if (function_exists('log_admin_action')) {
            log_admin_action($con, $admId, 'DEDUCT_STUDENT_POINTS', "Deducted {$points} points from student #{$studentId}. Notes: {$notes}");
        }

        return [
            'status' => 'success',
            'message' => trans('points_deducted_success'),
            'data' => [
                'student_id' => $studentId,
                'deducted_points' => $points,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance
            ]
        ];
    }
}

