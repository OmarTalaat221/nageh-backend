<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/hr_policy.php';

if (!function_exists('adminGetHrPolicySettings')) {
    function adminGetHrPolicySettings($con) {
        return ['status' => 'success', 'data' => get_hr_policy_settings($con)];
    }
}

if (!function_exists('adminUpdateHrPolicySettings')) {
    /**
     * Partial update — any field omitted from the body keeps its current value. Only affects
     * sessions/requests evaluated from now on (the cron and request handlers always read this
     * table fresh at the moment they act), never retroactively.
     */
    function adminUpdateHrPolicySettings($con) {
        $body = read_json_body();

        $intFields = [
            'teacher_max_absence_with_permission_per_month',
            'teacher_max_absence_without_permission',
            'teacher_max_reschedule_per_month',
            'teacher_grace_minutes',
            'student_max_absence_with_permission_per_month',
            'student_max_absence_without_permission',
            'student_max_reschedule_per_month',
            'student_grace_minutes',
        ];
        $decimalFields = [
            'teacher_late_fine_per_minute',
            'teacher_absence_fine_amount',
            'default_pay_rate',
        ];

        $updateFields = [];
        $params = [];
        $types = "";

        foreach ($intFields as $field) {
            if (isset($body[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = max(0, (int)$body[$field]);
                $types .= "i";
            }
        }
        foreach ($decimalFields as $field) {
            if (isset($body[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = max(0, (float)$body[$field]);
                $types .= "d";
            }
        }
        if (isset($body['default_pay_model']) && in_array($body['default_pay_model'], ['hourly', 'per_session', 'per_minute'], true)) {
            $updateFields[] = "default_pay_model = ?";
            $params[] = $body['default_pay_model'];
            $types .= "s";
        }

        if (empty($updateFields)) {
            return ['status' => 'error', 'message' => trans('no_fields_to_update')];
        }

        $sql = "UPDATE hr_policy_settings SET " . implode(", ", $updateFields) . " WHERE id = 1";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update settings: ' . mysqli_error($con)];
        }

        // Settings are cached per-request in get_hr_policy_settings(); this request's own
        // response should reflect the fresh values, so re-query directly rather than reuse cache.
        $res = mysqli_query($con, "SELECT * FROM hr_policy_settings WHERE id = 1 LIMIT 1");
        $row = mysqli_fetch_assoc($res);

        return ['status' => 'success', 'message' => trans('settings_updated_success'), 'data' => $row];
    }
}

if (!function_exists('adminSetTeacherPayRate')) {
    function adminSetTeacherPayRate($con) {
        $body = read_json_body();
        $teacherId = (int)($body['teacher_id'] ?? 0);
        $payModel = trim($body['pay_model'] ?? '');
        $rateAmount = isset($body['rate_amount']) ? (float)$body['rate_amount'] : null;

        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_id_required')];
        }
        if (!in_array($payModel, ['hourly', 'per_session', 'per_minute'], true)) {
            return ['status' => 'error', 'message' => trans('pay_model_required')];
        }
        if ($rateAmount === null || $rateAmount < 0) {
            return ['status' => 'error', 'message' => trans('rate_amount_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('teacher_not_found')];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO teacher_pay_rates (teacher_id, pay_model, rate_amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE pay_model = VALUES(pay_model), rate_amount = VALUES(rate_amount)");
        mysqli_stmt_bind_param($stmt, "isd", $teacherId, $payModel, $rateAmount);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to save pay rate: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => trans('pay_rate_saved_success'), 'data' => get_teacher_pay_rate($con, $teacherId)];
    }
}

if (!function_exists('adminClearTeacherPayRate')) {
    function adminClearTeacherPayRate($con) {
        $teacherId = (int)($_GET['teacher_id'] ?? read_json_body()['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_id_required')];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM teacher_pay_rates WHERE teacher_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);

        return ['status' => 'success', 'message' => trans('pay_rate_reset_success'), 'data' => get_teacher_pay_rate($con, $teacherId)];
    }
}
