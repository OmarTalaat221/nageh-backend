<?php
// Automatic "system" notifications for admins (and, where relevant, the
// teacher involved) triggered by real platform events — new registrations,
// subscriptions, book purchase requests, session starts, and consultation
// slots. These are always sender_type='system', channels=['push'], and
// never block/fail the calling action if something goes wrong (best-effort).
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/entities.php';

if (!function_exists('notify_admins')) {
    function notify_admins($con, string $title, string $body, array $data = []): void {
        try {
            $adminIds = get_admin_ids($con);
            if (empty($adminIds)) return;
            $data['type'] = $data['type'] ?? 'admin_alert';
            send_notification_to_users($con, 'admin', $adminIds, $title, $body, $data, 'system', null, ['push']);
        } catch (Throwable $e) {
            // best-effort only — never break the calling action
        }
    }
}

if (!function_exists('admin_notify_get_student_name')) {
    function admin_notify_get_student_name($con, int $studentId): string {
        $res = mysqli_query($con, "SELECT name FROM students WHERE id = {$studentId} LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['name'] ?? "طالب #{$studentId}";
    }
}

if (!function_exists('admin_notify_get_book_title')) {
    function admin_notify_get_book_title($con, int $bookId): string {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $res = mysqli_query($con, "SELECT title FROM course_books_translations WHERE book_id = {$bookId} AND language_id = {$langId} LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['title'] ?? "كتاب #{$bookId}";
    }
}

if (!function_exists('notify_of_new_student_registration')) {
    /**
     * Fired once from studentRegister() right after the new student row is
     * inserted. If $referredByStudentId is set, looks up the referrer's name
     * so the admin sees exactly who brought the new student in.
     */
    function notify_of_new_student_registration($con, int $studentId, string $studentName, string $email, ?int $referredByStudentId = null): void {
        try {
            if ($referredByStudentId) {
                $referrerName = admin_notify_get_student_name($con, $referredByStudentId);
                $title = 'طالب جديد عن طريق الإحالة';
                $body = "{$studentName} ({$email}) سجّل في المنصة عن طريق إحالة من الطالب {$referrerName}.";
                $data = ['type' => 'new_student_referral', 'student_id' => $studentId, 'referred_by_student_id' => $referredByStudentId];
            } else {
                $title = 'طالب جديد سجّل في المنصة';
                $body = "{$studentName} ({$email}) سجّل حساب جديد في المنصة.";
                $data = ['type' => 'new_student_registration', 'student_id' => $studentId];
            }
            notify_admins($con, $title, $body, $data);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_book_purchase_request')) {
    function notify_of_book_purchase_request($con, int $studentId, int $bookId): void {
        try {
            $studentName = admin_notify_get_student_name($con, $studentId);
            $bookTitle = admin_notify_get_book_title($con, $bookId);
            $title = 'طلب شراء كتاب جديد';
            $body = "الطالب {$studentName} طلب شراء الكتاب \"{$bookTitle}\" وينتظر الموافقة.";
            notify_admins($con, $title, $body, ['type' => 'book_purchase_request', 'student_id' => $studentId, 'book_id' => $bookId]);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_course_subscription')) {
    /**
     * Fired whenever a student ends up with access to one or more courses,
     * regardless of the path (subscription code redemption, direct admin
     * grant, or a completed course purchase). Notifies admins (unless
     * $notifyAdmin is false, e.g. when an admin granted it themselves) and
     * each granted course's teacher(s).
     *
     * $source is a short human label for how the subscription happened,
     * e.g. 'كود اشتراك', 'شراء مباشر', 'منح إداري'.
     */
    function notify_of_course_subscription($con, int $studentId, array $courseIds, string $source, bool $notifyAdmin = true): void {
        try {
            $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds), fn($id) => $id > 0)));
            if (empty($courseIds)) return;

            $studentName = admin_notify_get_student_name($con, $studentId);

            foreach ($courseIds as $courseId) {
                $course = get_course_details($con, $courseId);
                $isPackage = ($course['kind'] ?? 'normal') === 'package';
                $entityLabel = $isPackage ? 'باقة' : 'كورس';
                $entityLabelPossessive = $isPackage ? 'باقتك' : 'كورسك';
                $courseName = $course['name'] ?? "{$entityLabel} #{$courseId}";

                if ($notifyAdmin) {
                    notify_admins(
                        $con,
                        "اشتراك جديد في {$entityLabel}",
                        "الطالب {$studentName} اشترك في {$entityLabel} \"{$courseName}\" ({$source}).",
                        ['type' => 'course_subscription', 'student_id' => $studentId, 'course_id' => $courseId]
                    );
                }

                $teachers = get_course_teachers_details($con, $courseId);
                $teacherIds = array_values(array_map(fn($t) => (int)$t['id'], $teachers));
                if (!empty($teacherIds)) {
                    send_notification_to_users(
                        $con,
                        'teacher',
                        $teacherIds,
                        "طالب جديد اشترك في {$entityLabelPossessive}",
                        "الطالب {$studentName} اشترك في {$entityLabel} \"{$courseName}\" ({$source}).",
                        ['type' => 'course_subscription', 'student_id' => $studentId, 'course_id' => $courseId],
                        'system',
                        null,
                        ['push']
                    );
                }
            }
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_admins_of_session_started')) {
    function notify_admins_of_session_started($con, int $teacherId, ?int $courseId, ?int $subjectId): void {
        try {
            $teacherRes = mysqli_query($con, "SELECT name FROM teachers WHERE id = {$teacherId} LIMIT 1");
            $teacherRow = $teacherRes ? mysqli_fetch_assoc($teacherRes) : null;
            $teacherName = $teacherRow['name'] ?? "معلم #{$teacherId}";

            $contextLabel = '';
            if (!empty($courseId)) {
                $course = get_course_details($con, $courseId);
                $contextLabel = ' - كورس "' . ($course['name'] ?? "#{$courseId}") . '"';
            }

            notify_admins(
                $con,
                'حصة بدأت الآن',
                "المعلم {$teacherName} بدأ حصة{$contextLabel}.",
                ['type' => 'session_started', 'teacher_id' => $teacherId, 'course_id' => $courseId, 'subject_id' => $subjectId]
            );
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('notify_of_consultation_slot_created')) {
    function notify_of_consultation_slot_created($con, int $teacherId, ?int $subjectId, string $slotDate, string $slotTime): void {
        try {
            // 0 is the platform owner's own slot: he has no row in `teachers`, and the wording
            // should not call him a teacher either.
            $isOwner = $teacherId <= 0;

            if ($isOwner) {
                $teacherName = function_exists('platform_owner_name')
                    ? platform_owner_name($con)
                    : 'صاحب المنصة';
            } else {
                $teacherRes = mysqli_query($con, "SELECT name FROM teachers WHERE id = {$teacherId} LIMIT 1");
                $teacherRow = $teacherRes ? mysqli_fetch_assoc($teacherRes) : null;
                $teacherName = $teacherRow['name'] ?? "معلم #{$teacherId}";
            }

            notify_admins(
                $con,
                $isOwner ? 'موعد استشارة جديد' : 'استشارة جديدة من معلم',
                ($isOwner ? "{$teacherName} أضاف" : "المعلم {$teacherName} أضاف") . " موعد استشارة جديد يوم {$slotDate} الساعة {$slotTime}.",
                ['type' => 'consultation_created', 'teacher_id' => $teacherId, 'subject_id' => $subjectId]
            );

            $studentIds = get_active_student_ids_for_session($con, null, $subjectId, $teacherId);
            if (!empty($studentIds)) {
                send_notification_to_users(
                    $con,
                    'student',
                    $studentIds,
                    'موعد استشارة جديد متاح',
                    ($isOwner ? "{$teacherName} أتاح" : "المعلم {$teacherName} أتاح") . " موعد استشارة جديد يوم {$slotDate} الساعة {$slotTime}.",
                    ['type' => 'consultation_created', 'teacher_id' => $teacherId, 'subject_id' => $subjectId],
                    'system',
                    null,
                    ['push']
                );
            }
        } catch (Throwable $e) {
        }
    }
}
