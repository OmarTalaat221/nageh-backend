<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/notifications.php';
require_once __DIR__ . '/../../../helpers/admin_logs.php';
require_once __DIR__ . '/../../../helpers/points.php';

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
                         a.student_id AS referrer_student_id, a.commission_type, a.commission_value,
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

                // What the rule would award, so the approval box opens on a number instead of on
                // a zero the admin has to fill in from memory - and so overriding it is visibly an
                // override rather than the only way to enter anything at all.
                $row['suggested_amount'] = points_earned_for_purchase($con, [
                    'commission_type' => $row['commission_type'] ?? null,
                    'commission_value' => $row['commission_value'] ?? null,
                ], $row['purchase_amount']);

                // Which rule produced it, so the screen can say "10% of 500" rather than just 50.
                $platformRules = points_rules($con);
                $codeType = strtolower(trim((string)($row['commission_type'] ?? '')));
                $codeValue = (float)($row['commission_value'] ?? 0);
                $usesOwnRule = in_array($codeType, ['percent', 'fixed'], true) && $codeValue > 0;
                $row['applied_rule'] = [
                    'source' => $usesOwnRule ? 'code' : 'platform',
                    'type' => $usesOwnRule ? $codeType : $platformRules['commission_type'],
                    'value' => $usesOwnRule ? $codeValue : $platformRules['commission_value'],
                ];

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
        // An amount sent explicitly still wins - an admin overruling the rule for one request is a
        // real need. Omitting it now means "use the rule" rather than "award zero", which is what
        // an empty box used to mean.
        $amountWasGiven = isset($body['amount']) || isset($body['reward_amount']) || isset($body['points']);
        $amount = (float)($body['amount'] ?? $body['reward_amount'] ?? $body['points'] ?? 0.00);
        $notes = trim($body['notes'] ?? '');

        if ($commissionId <= 0) {
            return ['status' => 'error', 'message' => 'Valid commission_id is required'];
        }

        if ($amountWasGiven && $amount < 0) {
            return ['status' => 'error', 'message' => 'Amount/points cannot be negative'];
        }

        // Fetch request
        $stmt = mysqli_prepare($con, "SELECT ac.id, ac.affiliate_id, ac.referred_student_id, ac.item_type, ac.item_id, ac.purchase_amount, ac.status, a.student_id AS referrer_student_id, a.commission_type, a.commission_value, st_target.name AS referred_name
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

        // No amount sent: work it out from the code's own rule, falling back to the platform
        // default. Before this the box was simply left empty and the referral earned nothing.
        if (!$amountWasGiven) {
            $amount = points_earned_for_purchase($con, [
                'commission_type' => $req['commission_type'] ?? null,
                'commission_value' => $req['commission_value'] ?? null,
            ], (float)$req['purchase_amount']);
        }

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


if (!function_exists('adminGetPointsSettings')) {
    /**
     * Everything that decides what a referral earns and what a point buys, in one payload.
     */
    function adminGetPointsSettings($con) {
        $rules = points_rules($con);

        $values = [];
        $res = mysqli_query($con, "
            SELECT c.code, c.name_ar, c.name_en, c.symbol_ar, c.symbol_en, c.decimal_digits,
                   p.point_value, p.is_active
            FROM currencies c
            LEFT JOIN point_currency_values p ON p.currency_code = c.code
            WHERE c.is_active = 1
            ORDER BY c.sort_order ASC, c.code ASC");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $values[] = [
                    'currency_code' => $row['code'],
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'symbol' => $row['symbol_ar'] ?: $row['symbol_en'],
                    'decimal_digits' => (int)$row['decimal_digits'],
                    // Null means nobody has priced points in this currency, which is different
                    // from pricing them at zero - the client shows an empty box, not a 0.
                    'point_value' => $row['point_value'] !== null ? (float)$row['point_value'] : null,
                    'is_active' => $row['is_active'] !== null ? (int)$row['is_active'] : 0,
                ];
            }
        }

        return [
            'status' => 'success',
            'data' => [
                'commission_type' => $rules['commission_type'],
                'commission_value' => $rules['commission_value'],
                'redeem_enabled' => $rules['redeem_enabled'] ? 1 : 0,
                'min_redeem' => $rules['min_redeem'],
                'base_currency' => pricing_base_currency($con),
                'currency_values' => $values,
            ],
        ];
    }
}

if (!function_exists('adminUpdatePointsSettings')) {
    function adminUpdatePointsSettings($con, $adminData = null) {
        $body = read_json_body();

        $pairs = [];

        if (isset($body['commission_type'])) {
            $type = strtolower(trim((string)$body['commission_type']));
            if (!in_array($type, ['percent', 'fixed'], true)) {
                return ['status' => 'error', 'message' => 'commission_type must be percent or fixed'];
            }
            $pairs['referral_commission_type'] = $type;
        }

        if (isset($body['commission_value'])) {
            $value = (float)$body['commission_value'];
            if ($value < 0) {
                return ['status' => 'error', 'message' => 'commission_value cannot be negative'];
            }
            // A percentage over 100 would pay a referrer more than the purchase was worth.
            $type = $pairs['referral_commission_type'] ?? points_rules($con)['commission_type'];
            if ($type === 'percent' && $value > 100) {
                return ['status' => 'error', 'message' => 'A percentage commission cannot exceed 100'];
            }
            $pairs['referral_commission_value'] = (string)$value;
        }

        if (isset($body['redeem_enabled'])) {
            $pairs['points_redeem_enabled'] = ((int)$body['redeem_enabled'] === 1 || $body['redeem_enabled'] === true) ? '1' : '0';
        }

        if (isset($body['min_redeem'])) {
            $min = (float)$body['min_redeem'];
            if ($min < 0) {
                return ['status' => 'error', 'message' => 'min_redeem cannot be negative'];
            }
            $pairs['points_min_redeem'] = (string)$min;
        }

        foreach ($pairs as $key => $value) {
            $stmt = mysqli_prepare($con, "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            mysqli_stmt_bind_param($stmt, "ss", $key, $value);
            mysqli_stmt_execute($stmt);
        }

        // Per-currency point values. Sent as a list so several can move at once - a rate change is
        // usually a repricing across currencies, not one number.
        $currencyValues = $body['currency_values'] ?? null;
        $touched = 0;
        if (is_array($currencyValues)) {
            foreach ($currencyValues as $entry) {
                $code = strtoupper(trim((string)($entry['currency_code'] ?? '')));
                if ($code === '') continue;

                $exists = mysqli_fetch_assoc(mysqli_query($con,
                    "SELECT id FROM currencies WHERE code = '" . mysqli_real_escape_string($con, $code) . "' LIMIT 1"));
                if (!$exists) continue;

                $value = isset($entry['point_value']) && $entry['point_value'] !== '' ? (float)$entry['point_value'] : null;
                if ($value !== null && $value < 0) {
                    return ['status' => 'error', 'message' => "point_value for {$code} cannot be negative"];
                }
                $isActive = array_key_exists('is_active', $entry) ? ((int)$entry['is_active'] === 1 ? 1 : 0) : 1;

                if ($value === null) {
                    // Cleared: points stop being spendable in this currency, and the row is
                    // deactivated rather than deleted so the number that was there is recoverable.
                    mysqli_query($con, "UPDATE point_currency_values SET is_active = 0
                        WHERE currency_code = '" . mysqli_real_escape_string($con, $code) . "'");
                    $touched++;
                    continue;
                }

                $stmt = mysqli_prepare($con, "INSERT INTO point_currency_values (currency_code, point_value, is_active)
                    VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE point_value = VALUES(point_value), is_active = VALUES(is_active)");
                mysqli_stmt_bind_param($stmt, "sdi", $code, $value, $isActive);
                mysqli_stmt_execute($stmt);
                $touched++;
            }
        }

        $admId = is_array($adminData) ? (int)($adminData['id'] ?? 0) : 0;
        if ($admId > 0) {
            log_admin_action($con, $admId, 'UPDATE_POINTS_SETTINGS',
                'Updated referral/point settings: ' . implode(', ', array_keys($pairs)) . "; currencies touched: {$touched}");
        }

        return adminGetPointsSettings($con);
    }
}

if (!function_exists('adminSetAffiliateRule')) {
    /**
     * Gives one referral code its own earning rule, overriding the platform default.
     *
     * The columns have existed since `affiliates` was created and were never once written after
     * the row was inserted, so every code has been on ('fixed', 0.00) - which reads as "no rule".
     * Sending an empty `commission_value` puts a code back on the platform default.
     */
    function adminSetAffiliateRule($con, $adminData = null) {
        $body = read_json_body();
        $affiliateId = (int)($body['affiliate_id'] ?? $body['id'] ?? 0);

        if ($affiliateId <= 0) {
            return ['status' => 'error', 'message' => 'affiliate_id is required'];
        }

        $affiliate = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT id, affiliate_code FROM affiliates WHERE id = {$affiliateId} LIMIT 1"));
        if (!$affiliate) {
            return ['status' => 'error', 'message' => 'Affiliate code not found'];
        }

        // Cleared: back to the platform rule. 0.00 is what an unset code already looks like, so
        // this is the same state a code has always started in, not a new one.
        $clear = !isset($body['commission_value']) || $body['commission_value'] === '' || $body['commission_value'] === null;

        if ($clear) {
            mysqli_query($con, "UPDATE affiliates SET commission_type = 'fixed', commission_value = 0.00 WHERE id = {$affiliateId}");
        } else {
            $type = strtolower(trim((string)($body['commission_type'] ?? 'percent')));
            if (!in_array($type, ['percent', 'fixed'], true)) {
                return ['status' => 'error', 'message' => 'commission_type must be percent or fixed'];
            }

            $value = (float)$body['commission_value'];
            if ($value < 0) {
                return ['status' => 'error', 'message' => 'commission_value cannot be negative'];
            }
            if ($type === 'percent' && $value > 100) {
                return ['status' => 'error', 'message' => 'A percentage commission cannot exceed 100'];
            }

            $stmt = mysqli_prepare($con, "UPDATE affiliates SET commission_type = ?, commission_value = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sdi", $type, $value, $affiliateId);
            mysqli_stmt_execute($stmt);
        }

        $admId = is_array($adminData) ? (int)($adminData['id'] ?? 0) : 0;
        if ($admId > 0) {
            log_admin_action($con, $admId, 'SET_AFFILIATE_RULE',
                "Affiliate #{$affiliateId} ({$affiliate['affiliate_code']}) rule " . ($clear ? 'cleared to the platform default' : 'set'));
        }

        $row = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT id, affiliate_code, commission_type, commission_value FROM affiliates WHERE id = {$affiliateId} LIMIT 1"));
        $row['commission_value'] = (float)$row['commission_value'];
        $row['uses_platform_default'] = $row['commission_value'] <= 0;

        return ['status' => 'success', 'message' => trans('saved_successfully') ?: 'Saved', 'data' => $row];
    }
}
