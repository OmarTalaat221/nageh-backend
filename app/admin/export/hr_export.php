<?php
/**
 * Excel exports for the money-and-attendance side of the dashboard.
 *
 * The platform already exported students, teachers, parents, cities, subjects and grades - the
 * lists of who exists. None of the numbers could be exported: not a teacher's balance, not a
 * single deduction, not who was absent, not one report. Those are exactly the figures somebody has
 * to reconcile against a payroll run or hand to an accountant, and the only way to get them out
 * was to read them off the screen.
 *
 * Every function here reuses the same filters as the screen it belongs to, so what downloads is
 * what the admin is looking at - not the whole table regardless of what they filtered to.
 */

require_once __DIR__ . '/export.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/hr_policy.php';

if (!function_exists('hr_export_txn_type_label')) {
    function hr_export_txn_type_label(?string $type, string $lang): string {
        $labels = [
            'ar' => ['earning' => 'أرباح', 'fine' => 'خصم', 'refund' => 'استرداد', 'adjustment' => 'تسوية'],
            'en' => ['earning' => 'Earning', 'fine' => 'Deduction', 'refund' => 'Refund', 'adjustment' => 'Adjustment'],
        ];
        return $labels[$lang][$type] ?? ($type ?? '');
    }
}

if (!function_exists('hr_export_status_label')) {
    function hr_export_status_label(?string $status, string $lang): string {
        $labels = [
            'ar' => ['pending' => 'قيد المراجعة', 'approved' => 'تمت الموافقة', 'rejected' => 'مرفوض'],
            'en' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'],
        ];
        return $labels[$lang][$status] ?? ($status ?? '');
    }
}

if (!function_exists('adminExportTeacherWallets')) {
    /**
     * One row per teacher: what they have earned, what has been deducted, and where that leaves
     * them. The earnings and deductions columns are summed from the ledger rather than read off
     * `teachers.wallet_balance`, so the sheet shows how the balance was arrived at and not just
     * the cached figure.
     */
    function adminExportTeacherWallets($con) {
        $lang = get_request_lang($con);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = "WHERE 1=1";
        $params = [];
        $types = "";
        if ($search !== '') {
            $where .= " AND (t.name LIKE ? OR t.email LIKE ?)";
            $pattern = '%' . $search . '%';
            $params[] = $pattern; $params[] = $pattern; $types .= "ss";
        }

        $sql = "SELECT t.id, t.name, t.email, t.is_active, t.is_attendance_suspended, t.wallet_balance,
                   COALESCE(SUM(CASE WHEN w.amount > 0 THEN w.amount END), 0) AS credited,
                   COALESCE(SUM(CASE WHEN w.amount < 0 THEN -w.amount END), 0) AS debited,
                   COALESCE(SUM(CASE WHEN w.type = 'fine' THEN 1 END), 0) AS fine_count
            FROM teachers t
            LEFT JOIN teacher_wallet_transactions w ON w.teacher_id = t.id
            {$where}
            GROUP BY t.id, t.name, t.email, t.is_active, t.is_attendance_suspended, t.wallet_balance
            ORDER BY t.wallet_balance DESC";

        $stmt = mysqli_prepare($con, $sql);
        if (!empty($types)) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'المدرس', 'البريد الإلكتروني', 'الرصيد الحالي', 'إجمالي المستحق', 'إجمالي المخصوم', 'عدد الخصومات', 'نوع الأجر', 'قيمة الأجر', 'نشط', 'موقوف عن الحضور']
            : ['ID', 'Teacher', 'Email', 'Current Balance', 'Total Credited', 'Total Deducted', 'Deductions', 'Pay Model', 'Pay Rate', 'Active', 'Attendance Suspended'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $pay = get_teacher_pay_rate($con, (int)$row['id']);
            $rows[] = [
                (int)$row['id'],
                $row['name'],
                $row['email'],
                round((float)$row['wallet_balance'], 2),
                round((float)$row['credited'], 2),
                round((float)$row['debited'], 2),
                (int)$row['fine_count'],
                $pay['pay_model'] ?? '',
                isset($pay['pay_rate']) ? round((float)$pay['pay_rate'], 2) : '',
                exportYesNo((int)$row['is_active'], $lang),
                exportYesNo((int)$row['is_attendance_suspended'], $lang),
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'محافظ المدرسين' : 'Teacher Wallets', $lang, 'teacher_wallets');
    }
}

if (!function_exists('adminExportWalletTransactions')) {
    /**
     * Every wallet movement, one row each - this is where the automatic lateness and absence
     * deductions actually live. `teacher_id` narrows it to one teacher, `type` to one kind
     * (fine / earning / refund / adjustment), and `from` / `to` to a pay period.
     */
    function adminExportWalletTransactions($con) {
        $lang = get_request_lang($con);
        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        $type = strtolower(trim($_GET['type'] ?? ''));
        $from = trim($_GET['from'] ?? $_GET['date_from'] ?? '');
        $to = trim($_GET['to'] ?? $_GET['date_to'] ?? '');

        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($teacherId > 0) { $where[] = "w.teacher_id = ?"; $params[] = $teacherId; $types .= "i"; }
        if (in_array($type, ['earning', 'fine', 'refund', 'adjustment'], true)) {
            $where[] = "w.type = ?"; $params[] = $type; $types .= "s";
        }
        if ($from !== '') { $where[] = "DATE(w.created_at) >= ?"; $params[] = date('Y-m-d', strtotime($from)); $types .= "s"; }
        if ($to !== '')   { $where[] = "DATE(w.created_at) <= ?"; $params[] = date('Y-m-d', strtotime($to));   $types .= "s"; }

        $whereClause = "WHERE " . implode(" AND ", $where);

        $stmt = mysqli_prepare($con, "
            SELECT w.id, w.teacher_id, t.name AS teacher_name, w.type, w.amount, w.reference_type,
                   w.reference_id, w.note, w.created_by_type, w.created_at
            FROM teacher_wallet_transactions w
            LEFT JOIN teachers t ON t.id = w.teacher_id
            {$whereClause}
            ORDER BY w.id DESC");
        if (!empty($types)) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'المدرس', 'النوع', 'المبلغ', 'السبب', 'مرتبط بـ', 'رقم المرجع', 'بواسطة', 'التاريخ']
            : ['ID', 'Teacher', 'Type', 'Amount', 'Reason', 'Related To', 'Reference', 'By', 'Date'];

        // `created_by_type` is 'system' for the automatic ones, which is how a reader tells a
        // deduction the platform applied itself from one an admin typed in by hand.
        $byLabels = [
            'ar' => ['system' => 'تلقائي', 'admin' => 'الإدارة', 'teacher' => 'المدرس'],
            'en' => ['system' => 'Automatic', 'admin' => 'Admin', 'teacher' => 'Teacher'],
        ];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                (int)$row['id'],
                $row['teacher_name'] ?? ('#' . (int)$row['teacher_id']),
                hr_export_txn_type_label($row['type'], $lang),
                round((float)$row['amount'], 2),
                $row['note'] ?? '',
                $row['reference_type'] ?? '',
                $row['reference_id'] ? (int)$row['reference_id'] : '',
                $byLabels[$lang][$row['created_by_type']] ?? ($row['created_by_type'] ?? ''),
                $row['created_at'] ?? '',
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'حركات المحافظ' : 'Wallet Transactions', $lang, 'wallet_transactions');
    }
}

if (!function_exists('adminExportDeductionAppeals')) {
    function adminExportDeductionAppeals($con) {
        $lang = get_request_lang($con);
        $status = strtolower(trim($_GET['status'] ?? ''));

        $where = "WHERE 1=1";
        $params = [];
        $types = "";
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where .= " AND a.status = ?"; $params[] = $status; $types .= "s";
        }

        $stmt = mysqli_prepare($con, "
            SELECT a.id, a.teacher_id, t.name AS teacher_name, a.status, a.reason, a.admin_note,
                   a.created_at, a.reviewed_at, w.amount, w.note AS deduction_note, w.created_at AS deducted_at
            FROM teacher_deduction_appeals a
            LEFT JOIN teachers t ON t.id = a.teacher_id
            LEFT JOIN teacher_wallet_transactions w ON w.id = a.wallet_transaction_id
            {$where}
            ORDER BY a.id DESC");
        if (!empty($types)) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'المدرس', 'قيمة الخصم', 'سبب الخصم', 'تاريخ الخصم', 'سبب الاعتراض', 'الحالة', 'رد الإدارة', 'تاريخ الاعتراض', 'تاريخ المراجعة']
            : ['ID', 'Teacher', 'Deducted Amount', 'Deduction Reason', 'Deducted At', 'Appeal Reason', 'Status', 'Admin Note', 'Appealed At', 'Reviewed At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                (int)$row['id'],
                $row['teacher_name'] ?? ('#' . (int)$row['teacher_id']),
                $row['amount'] !== null ? round((float)$row['amount'], 2) : '',
                $row['deduction_note'] ?? '',
                $row['deducted_at'] ?? '',
                $row['reason'] ?? '',
                hr_export_status_label($row['status'], $lang),
                $row['admin_note'] ?? '',
                $row['created_at'] ?? '',
                $row['reviewed_at'] ?? '',
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'اعتراضات الخصومات' : 'Deduction Appeals', $lang, 'deduction_appeals');
    }
}

if (!function_exists('adminExportAttendanceExceptions')) {
    function adminExportAttendanceExceptions($con) {
        $lang = get_request_lang($con);
        $status = strtolower(trim($_GET['status'] ?? ''));
        $requestedByType = strtolower(trim($_GET['requested_by_type'] ?? ''));
        $requestType = strtolower(trim($_GET['request_type'] ?? ''));

        $where = ["1=1"];
        $params = [];
        $types = "";
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) { $where[] = "r.status = ?"; $params[] = $status; $types .= "s"; }
        if (in_array($requestedByType, ['teacher', 'student'], true)) { $where[] = "r.requested_by_type = ?"; $params[] = $requestedByType; $types .= "s"; }
        if (in_array($requestType, ['absence_permission', 'late_notice'], true)) { $where[] = "r.request_type = ?"; $params[] = $requestType; $types .= "s"; }
        $whereClause = "WHERE " . implode(" AND ", $where);

        // The requester is a teacher or a student depending on the row, so the name is resolved
        // with two joins rather than one - a single join would silently blank half the sheet.
        $stmt = mysqli_prepare($con, "
            SELECT r.id, r.session_id, r.requested_by_type, r.requested_by_id, r.request_type,
                   r.occurrence_date, r.expected_minutes_late, r.reason, r.status, r.admin_note,
                   r.created_at, r.reviewed_at,
                   COALESCE(t.name, s.name) AS requester_name,
                   COALESCE(t.email, s.email) AS requester_email
            FROM attendance_exception_requests r
            LEFT JOIN teachers t ON r.requested_by_type = 'teacher' AND t.id = r.requested_by_id
            LEFT JOIN students s ON r.requested_by_type = 'student' AND s.id = r.requested_by_id
            {$whereClause}
            ORDER BY r.id DESC");
        if (!empty($types)) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $whoLabels = ['ar' => ['teacher' => 'مدرس', 'student' => 'طالب'], 'en' => ['teacher' => 'Teacher', 'student' => 'Student']];
        $kindLabels = [
            'ar' => ['absence_permission' => 'غياب بإذن', 'late_notice' => 'إبلاغ عن تأخير'],
            'en' => ['absence_permission' => 'Absence permission', 'late_notice' => 'Late notice'],
        ];

        $header = $lang === 'ar'
            ? ['المعرف', 'مقدم الطلب', 'النوع', 'نوع الطلب', 'رقم الحصة', 'تاريخ الحصة', 'دقائق التأخير المتوقعة', 'السبب', 'الحالة', 'رد الإدارة', 'تاريخ الطلب', 'تاريخ المراجعة']
            : ['ID', 'Requested By', 'Who', 'Request Type', 'Session', 'Session Date', 'Expected Minutes Late', 'Reason', 'Status', 'Admin Note', 'Requested At', 'Reviewed At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                (int)$row['id'],
                $row['requester_name'] ?: ('#' . (int)$row['requested_by_id']),
                $whoLabels[$lang][$row['requested_by_type']] ?? $row['requested_by_type'],
                $kindLabels[$lang][$row['request_type']] ?? $row['request_type'],
                (int)$row['session_id'],
                $row['occurrence_date'] ?? '',
                $row['expected_minutes_late'] !== null ? (int)$row['expected_minutes_late'] : '',
                $row['reason'] ?? '',
                hr_export_status_label($row['status'], $lang),
                $row['admin_note'] ?? '',
                $row['created_at'] ?? '',
                $row['reviewed_at'] ?? '',
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'طلبات الغياب والتأخير' : 'Attendance Requests', $lang, 'attendance_requests');
    }
}

if (!function_exists('adminExportReports')) {
    function adminExportReports($con) {
        $lang = get_request_lang($con);
        $reportType = trim($_GET['report_type'] ?? '');
        $studentId = (int)($_GET['student_id'] ?? 0);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = ["1=1"];
        $params = [];
        $types = "";
        if ($reportType !== '') { $where[] = "r.report_type = ?"; $params[] = $reportType; $types .= "s"; }
        if ($studentId > 0) { $where[] = "r.student_id = ?"; $params[] = $studentId; $types .= "i"; }
        if ($search !== '') {
            $where[] = "(s.name LIKE ? OR r.title LIKE ?)";
            $pattern = '%' . $search . '%';
            $params[] = $pattern; $params[] = $pattern; $types .= "ss";
        }
        $whereClause = "WHERE " . implode(" AND ", $where);

        $stmt = mysqli_prepare($con, "
            SELECT r.id, r.student_id, s.name AS student_name, r.report_type, r.title, r.description,
                   r.score, r.max_score, r.percentage, r.grade_label, r.rating, r.term,
                   r.report_date, r.sender_type, r.created_at
            FROM student_reports r
            LEFT JOIN students s ON s.id = r.student_id
            {$whereClause}
            ORDER BY r.id DESC");
        if (!empty($types)) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $typeLabels = [
            'ar' => ['academic_performance' => 'الأداء الأكاديمي', 'behavior_discipline' => 'السلوك والانضباط', 'attendance' => 'الحضور والغياب', 'exam_result' => 'نتيجة امتحان'],
            'en' => ['academic_performance' => 'Academic performance', 'behavior_discipline' => 'Behaviour & discipline', 'attendance' => 'Attendance', 'exam_result' => 'Exam result'],
        ];

        $header = $lang === 'ar'
            ? ['المعرف', 'الطالب', 'نوع التقرير', 'العنوان', 'التفاصيل', 'الفصل', 'الدرجة', 'الدرجة الكلية', 'النسبة', 'التقدير', 'التقييم', 'بواسطة', 'تاريخ التقرير']
            : ['ID', 'Student', 'Report Type', 'Title', 'Details', 'Term', 'Score', 'Max Score', 'Percentage', 'Grade', 'Rating', 'By', 'Report Date'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $score = $row['score'] !== null ? (float)$row['score'] : null;
            $max = $row['max_score'] !== null ? (float)$row['max_score'] : null;
            // `percentage` is stored, but it is null on older rows - fall back to working it out.
            $pct = $row['percentage'] !== null
                ? round((float)$row['percentage'], 1)
                : (($score !== null && $max) ? round($score / $max * 100, 1) : null);

            $rows[] = [
                (int)$row['id'],
                $row['student_name'] ?: ('#' . (int)$row['student_id']),
                $typeLabels[$lang][$row['report_type']] ?? $row['report_type'],
                $row['title'] ?? '',
                $row['description'] ?? '',
                $row['term'] ?? '',
                $score !== null ? round($score, 2) : '',
                $max !== null ? round($max, 2) : '',
                $pct !== null ? $pct . '%' : '',
                $row['grade_label'] ?? '',
                $row['rating'] !== null ? $row['rating'] : '',
                $row['sender_type'] ?? '',
                $row['report_date'] ?: ($row['created_at'] ?? ''),
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'تقارير الطلاب' : 'Student Reports', $lang, 'student_reports');
    }
}
