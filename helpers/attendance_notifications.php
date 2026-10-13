<?php
// Section 10 notifications: attendance exception requests, auto-recorded
// absences/lateness, wallet deductions, suspensions/freezes, and appeals.
// Same best-effort, never-break-the-caller convention as admin_notifications.php.
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/admin_notifications.php';

if (!function_exists('attendance_notify_get_teacher_name')) {
    function attendance_notify_get_teacher_name($con, int $teacherId): string {
        $res = mysqli_query($con, "SELECT name FROM teachers WHERE id = {$teacherId} LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['name'] ?? "المعلم #{$teacherId}";
    }
}

if (!function_exists('notify_of_attendance_exception_request_created')) {
    function notify_of_attendance_exception_request_created($con, string $requestedByType, int $requestedById, string $requestType, int $requestId): void {
        try {
            $requesterName = $requestedByType === 'teacher'
                ? attendance_notify_get_teacher_name($con, $requestedById)
                : admin_notify_get_student_name($con, $requestedById);
            $typeLabel = $requestType === 'late_notice' ? 'إبلاغ عن تأخير' : 'طلب غياب بإذن';
            $roleLabel = $requestedByType === 'teacher' ? 'المعلم' : 'الطالب';

            notify_admins(
                $con,
                'طلب جديد بشأن الحصص',
                "{$roleLabel} {$requesterName} أرسل {$typeLabel} بخصوص إحدى حصصه.",
                ['type' => 'attendance_exception_request_created', 'request_id' => $requestId]
            );
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_attendance_exception_request_reviewed')) {
    function notify_of_attendance_exception_request_reviewed($con, string $requestedByType, int $requestedById, string $requestType, string $decision, int $requestId): void {
        try {
            // The message is addressed to the requester directly ("your request..."), but the same
            // row also appears in the admin's global notifications log — where "your" is meaningless
            // without a name. Name the requester explicitly so that view is self-explanatory too.
            $requesterName = $requestedByType === 'teacher'
                ? attendance_notify_get_teacher_name($con, $requestedById)
                : admin_notify_get_student_name($con, $requestedById);
            $typeLabel = $requestType === 'late_notice' ? 'إبلاغ التأخير' : 'طلب الغياب بإذن';
            $decisionLabel = $decision === 'approved' ? 'تمت الموافقة عليه' : 'تم رفضه';
            $title = 'الرد على ' . $typeLabel;
            $body = "{$typeLabel} الخاص بـ {$requesterName} {$decisionLabel} من قبل الإدارة.";
            send_notification_to_users(
                $con,
                $requestedByType,
                [$requestedById],
                $title,
                $body,
                ['type' => 'attendance_exception_request_reviewed', 'request_id' => $requestId, 'decision' => $decision],
                'system',
                null,
                ['push']
            );
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_unexcused_absence')) {
    function notify_of_unexcused_absence($con, string $userType, int $userId, int $sessionId): void {
        try {
            $title = 'تسجيل غياب بدون إذن';
            $body = $userType === 'teacher'
                ? 'تم تسجيل غيابك عن إحدى حصصك تلقائيًا لعدم الدخول في الموعد المحدد، وتم تطبيق الغرامة المقررة.'
                : 'تم تسجيل غيابك عن إحدى حصصك تلقائيًا لعدم الدخول في الموعد المحدد.';
            send_notification_to_users(
                $con,
                $userType,
                [$userId],
                $title,
                $body,
                ['type' => 'unexcused_absence_recorded', 'session_id' => $sessionId],
                'system',
                null,
                ['push']
            );

            $roleLabel = $userType === 'teacher' ? 'المعلم' : 'الطالب';
            $name = $userType === 'teacher' ? attendance_notify_get_teacher_name($con, $userId) : admin_notify_get_student_name($con, $userId);
            notify_admins($con, 'غياب بدون إذن', "{$roleLabel} {$name} تغيّب عن حصة بدون إذن مسبق.", ['type' => 'unexcused_absence_recorded', 'user_type' => $userType, 'user_id' => $userId, 'session_id' => $sessionId]);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_teacher_deduction')) {
    function notify_of_teacher_deduction($con, int $teacherId, float $amount, string $reason): void {
        try {
            $absAmount = number_format(abs($amount), 2);
            $teacherName = attendance_notify_get_teacher_name($con, $teacherId);
            send_notification_to_users(
                $con,
                'teacher',
                [$teacherId],
                'تم تطبيق خصم على محفظتك',
                "تم خصم {$absAmount} من رصيد {$teacherName} بسبب: {$reason}.",
                ['type' => 'wallet_deduction', 'amount' => $amount],
                'system',
                null,
                ['push']
            );
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_teacher_suspended')) {
    function notify_of_teacher_suspended($con, int $teacherId): void {
        try {
            send_notification_to_users(
                $con,
                'teacher',
                [$teacherId],
                'تم إيقاف حسابك مؤقتًا',
                'تم إيقاف حسابك تلقائيًا لتجاوزك الحد المسموح به من الغياب بدون إذن. تواصل مع الإدارة لمزيد من التفاصيل.',
                ['type' => 'teacher_suspended'],
                'system',
                null,
                ['push']
            );
            $name = attendance_notify_get_teacher_name($con, $teacherId);
            notify_admins($con, 'تم إيقاف معلم تلقائيًا', "تم إيقاف المعلم {$name} تلقائيًا لتجاوزه الحد المسموح به من الغياب بدون إذن.", ['type' => 'teacher_suspended', 'teacher_id' => $teacherId]);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_deduction_appeal_reviewed')) {
    function notify_of_deduction_appeal_reviewed($con, int $teacherId, string $decision): void {
        try {
            $decisionLabel = $decision === 'approved' ? 'تمت الموافقة عليه وتم إرجاع المبلغ إلى محفظتك' : 'تم رفضه';
            $teacherName = attendance_notify_get_teacher_name($con, $teacherId);
            send_notification_to_users(
                $con,
                'teacher',
                [$teacherId],
                'الرد على اعتراضك',
                "اعتراض {$teacherName} على الخصم {$decisionLabel}.",
                ['type' => 'deduction_appeal_reviewed', 'decision' => $decision],
                'system',
                null,
                ['push']
            );
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_other_party_of_checkin')) {
    /**
     * Fired the first time either side checks in to a session occurrence, nudging the other
     * party not to be late (spec: "تنبيه الطرف الآخر لعدم التأخير"). Best-effort, admin-notified
     * only implicitly through the normal notification log.
     */
    function notify_other_party_of_checkin($con, int $sessionId, ?int $courseId, ?int $subjectId, int $teacherId, string $checkedInBy): void {
        try {
            if ($checkedInBy === 'teacher') {
                $studentIds = get_active_student_ids_for_session($con, $courseId, $subjectId, $teacherId);
                if (!empty($studentIds)) {
                    send_notification_to_users($con, 'student', $studentIds, 'المعلم دخل الحصة', 'المعلم دخل الحصة الآن، برجاء عدم التأخير في الانضمام.', ['type' => 'other_party_checked_in', 'session_id' => $sessionId], 'system', null, ['push']);
                }
            } else {
                send_notification_to_users($con, 'teacher', [$teacherId], 'طالب دخل الحصة', 'أحد الطلاب دخل الحصة بالفعل، برجاء عدم التأخير.', ['type' => 'other_party_checked_in', 'session_id' => $sessionId], 'system', null, ['push']);
            }
        } catch (Throwable $e) {
        }
    }
}
