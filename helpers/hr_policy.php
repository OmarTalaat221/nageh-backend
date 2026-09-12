<?php
// Section 10: single-row global HR policy (attendance/lateness/reschedule limits,
// fine amounts, and default teacher pay model) plus per-teacher pay overrides.
require_once __DIR__ . '/functions.php';

if (!function_exists('get_hr_policy_settings')) {
    function get_hr_policy_settings($con): array {
        static $cached = null;
        if ($cached !== null) return $cached;

        $res = mysqli_query($con, "SELECT * FROM hr_policy_settings WHERE id = 1 LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;

        if (!$row) {
            mysqli_query($con, "INSERT INTO hr_policy_settings (id) VALUES (1)");
            $res = mysqli_query($con, "SELECT * FROM hr_policy_settings WHERE id = 1 LIMIT 1");
            $row = mysqli_fetch_assoc($res);
        }

        $row['teacher_max_absence_with_permission_per_month'] = (int)$row['teacher_max_absence_with_permission_per_month'];
        $row['teacher_max_absence_without_permission'] = (int)$row['teacher_max_absence_without_permission'];
        $row['teacher_max_reschedule_per_month'] = (int)$row['teacher_max_reschedule_per_month'];
        $row['teacher_grace_minutes'] = (int)$row['teacher_grace_minutes'];
        $row['teacher_late_fine_per_minute'] = (float)$row['teacher_late_fine_per_minute'];
        $row['teacher_absence_fine_amount'] = (float)$row['teacher_absence_fine_amount'];
        $row['student_max_absence_with_permission_per_month'] = (int)$row['student_max_absence_with_permission_per_month'];
        $row['student_max_absence_without_permission'] = (int)$row['student_max_absence_without_permission'];
        $row['student_max_reschedule_per_month'] = (int)$row['student_max_reschedule_per_month'];
        $row['student_grace_minutes'] = (int)$row['student_grace_minutes'];
        $row['default_pay_rate'] = (float)$row['default_pay_rate'];

        $cached = $row;
        return $cached;
    }
}

if (!function_exists('get_teacher_pay_rate')) {
    /** Returns ['pay_model' => ..., 'rate_amount' => ...] — the teacher's own override if set, else the global default. */
    function get_teacher_pay_rate($con, int $teacherId): array {
        $stmt = mysqli_prepare($con, "SELECT pay_model, rate_amount FROM teacher_pay_rates WHERE teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($row) {
            return ['pay_model' => $row['pay_model'], 'rate_amount' => (float)$row['rate_amount']];
        }

        $policy = get_hr_policy_settings($con);
        return ['pay_model' => $policy['default_pay_model'], 'rate_amount' => $policy['default_pay_rate']];
    }
}

if (!function_exists('compute_session_earning')) {
    function compute_session_earning(string $payModel, float $rateAmount, int $durationMinutes): float {
        $durationMinutes = max(0, $durationMinutes);
        switch ($payModel) {
            case 'hourly':
                return round($rateAmount * ($durationMinutes / 60), 2);
            case 'per_minute':
                return round($rateAmount * $durationMinutes, 2);
            case 'per_session':
            default:
                return round($rateAmount, 2);
        }
    }
}

if (!function_exists('adjust_teacher_wallet')) {
    /**
     * Appends one immutable ledger row and updates the cached teachers.wallet_balance in the
     * same call. $amount is signed: positive = credit (earning/refund/positive adjustment),
     * negative = debit (fine/negative adjustment). Returns the new wallet_transactions.id.
     */
    function adjust_teacher_wallet(
        $con,
        int $teacherId,
        string $type,
        float $amount,
        string $referenceType = 'manual',
        ?int $referenceId = null,
        ?string $note = null,
        string $createdByType = 'system',
        ?int $createdByAdminId = null
    ): int {
        $stmt = mysqli_prepare($con, "INSERT INTO teacher_wallet_transactions (teacher_id, type, amount, reference_type, reference_id, note, created_by_type, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isdsissi", $teacherId, $type, $amount, $referenceType, $referenceId, $note, $createdByType, $createdByAdminId);
        mysqli_stmt_execute($stmt);
        $txnId = (int)mysqli_insert_id($con);

        $bStmt = mysqli_prepare($con, "UPDATE teachers SET wallet_balance = wallet_balance + ? WHERE id = ?");
        mysqli_stmt_bind_param($bStmt, "di", $amount, $teacherId);
        mysqli_stmt_execute($bStmt);

        return $txnId;
    }
}

if (!function_exists('count_unexcused_absences_this_month')) {
    function count_unexcused_absences_this_month($con, string $table, string $idColumn, int $id): int {
        $monthStart = get_request_now()->format('Y-m-01');
        $dateColumn = $table === 'teacher_session_attendance' ? 'occurrence_date' : 'session_date';
        $stmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM {$table} WHERE {$idColumn} = ? AND status = 'absent' AND is_excused = 0 AND {$dateColumn} >= ?");
        mysqli_stmt_bind_param($stmt, "is", $id, $monthStart);
        mysqli_stmt_execute($stmt);
        return (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
    }
}

if (!function_exists('count_reschedule_requests_this_month')) {
    function count_reschedule_requests_this_month($con, string $requestedByType, int $requestedById): int {
        $monthStart = get_request_now()->format('Y-m-01 00:00:00');
        $stmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM session_reschedule_requests WHERE requested_by_type = ? AND requested_by_id = ? AND created_at >= ?");
        mysqli_stmt_bind_param($stmt, "sis", $requestedByType, $requestedById, $monthStart);
        mysqli_stmt_execute($stmt);
        return (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
    }
}

if (!function_exists('has_approved_attendance_exception')) {
    function has_approved_attendance_exception($con, int $sessionId, string $occurrenceDate, string $requestedByType, int $requestedById, string $requestType): ?array {
        $stmt = mysqli_prepare($con, "SELECT * FROM attendance_exception_requests WHERE session_id = ? AND occurrence_date = ? AND requested_by_type = ? AND requested_by_id = ? AND request_type = ? AND status = 'approved' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "issis", $sessionId, $occurrenceDate, $requestedByType, $requestedById, $requestType);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }
}
