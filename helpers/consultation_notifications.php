<?php
/**
 * Telling people about a consultation.
 *
 * Nothing in the consultation flow notified anybody. A student could book a slot and the teacher
 * would only find out by opening the bookings screen; a teacher could approve or reject one and
 * the student would only find out by checking back. These are the four moments that matter, in one
 * place so every caller says the same thing.
 *
 * Every function here is best-effort: a consultation must still be booked even if the notification
 * service is unreachable, so nothing raises and nothing returns a value the caller has to handle.
 */

require_once __DIR__ . '/notifications.php';

if (!function_exists('consultation_when_label')) {
    function consultation_when_label(?array $slot): string {
        if (!$slot) return '';
        $date = trim((string)($slot['slot_date'] ?? ''));
        $time = trim((string)($slot['slot_time'] ?? ''));
        if ($date === '' && $time === '') return '';
        // HH:MM is enough - the seconds are noise in a push notification.
        if ($time !== '') $time = substr($time, 0, 5);
        return trim($date . ' ' . $time);
    }
}

if (!function_exists('consultation_person_name')) {
    function consultation_person_name($con, string $table, ?int $id): string {
        // A consultation with no teacher is the platform owner's own. He has no row to read a
        // name from, so without this the notification would name nobody at all.
        if ($table === 'teachers' && !$id && function_exists('platform_owner_name')) {
            return platform_owner_name($con);
        }
        if (!$id || !in_array($table, ['students', 'teachers'], true)) return '';
        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT name FROM {$table} WHERE id = " . (int)$id . " LIMIT 1"));
        return trim((string)($row['name'] ?? ''));
    }
}

if (!function_exists('notify_consultation_booked')) {
    /**
     * A slot has just been booked for a student. Both sides are told, and who did the booking
     * changes what each of them is told - "you booked" reads wrong when an admin did it for you.
     */
    function notify_consultation_booked($con, int $slotId, int $studentId, int $teacherId, string $bookedBy = 'student', ?int $actorId = null): void {
        $slot = mysqli_fetch_assoc(mysqli_query($con, "SELECT slot_date, slot_time, meeting_type FROM consultation_slots WHERE id = " . (int)$slotId . " LIMIT 1"));
        $when = consultation_when_label($slot);
        $studentName = consultation_person_name($con, 'students', $studentId);
        $teacherName = consultation_person_name($con, 'teachers', $teacherId);

        $data = ['type' => 'consultation_booked', 'slot_id' => $slotId, 'booked_by' => $bookedBy];
        $senderType = $bookedBy === 'admin' ? 'admin' : 'system';

        if ($studentId > 0) {
            $body = $bookedBy === 'admin'
                ? "تم حجز موعد استشارة لك مع {$teacherName}" . ($when !== '' ? " يوم {$when}" : '') . "."
                : "تم تسجيل حجزك لاستشارة مع {$teacherName}" . ($when !== '' ? " يوم {$when}" : '') . ".";

            @send_notification_to_users($con, 'student', [$studentId], 'موعد استشارة', $body, $data, $senderType, $actorId, ['push']);
        }

        if ($teacherId > 0) {
            $body = $bookedBy === 'admin'
                ? "حجزت الإدارة موعد استشارة لك مع الطالب {$studentName}" . ($when !== '' ? " يوم {$when}" : '') . "."
                : "حجز الطالب {$studentName} موعد استشارة معك" . ($when !== '' ? " يوم {$when}" : '') . ".";

            @send_notification_to_users($con, 'teacher', [$teacherId], 'حجز استشارة جديد', $body, $data, $senderType, $actorId, ['push']);
        }
    }
}

if (!function_exists('notify_consultation_series_booked')) {
    /**
     * A whole run of repeating slots was booked for one student in a single go.
     *
     * One summary notification per side rather than one per date: a weekly slot booked for three
     * months is a dozen rows, and a dozen near-identical alerts is not news, it is noise.
     */
    function notify_consultation_series_booked($con, int $studentId, int $teacherId, int $count, string $firstDate, string $lastDate, ?int $actorId = null): void {
        if ($count <= 0) return;

        $studentName = consultation_person_name($con, 'students', $studentId);
        $teacherName = consultation_person_name($con, 'teachers', $teacherId);
        $range = $firstDate === $lastDate ? $firstDate : "{$firstDate} إلى {$lastDate}";
        $data = ['type' => 'consultation_booked', 'booked_by' => 'admin', 'count' => $count];

        if ($studentId > 0) {
            @send_notification_to_users($con, 'student', [$studentId], 'مواعيد استشارة',
                "تم حجز {$count} موعد استشارة لك مع {$teacherName} ({$range}).",
                $data, 'admin', $actorId, ['push']);
        }

        if ($teacherId > 0) {
            @send_notification_to_users($con, 'teacher', [$teacherId], 'حجز استشارات جديد',
                "حجزت الإدارة {$count} موعد استشارة لك مع الطالب {$studentName} ({$range}).",
                $data, 'admin', $actorId, ['push']);
        }
    }
}

if (!function_exists('notify_consultation_reviewed')) {
    /**
     * The teacher accepted or refused a booking. Only the student needs telling - the teacher just
     * did it.
     */
    function notify_consultation_reviewed($con, int $slotId, int $studentId, int $teacherId, bool $approved): void {
        if ($studentId <= 0) return;

        $slot = mysqli_fetch_assoc(mysqli_query($con, "SELECT slot_date, slot_time FROM consultation_slots WHERE id = " . (int)$slotId . " LIMIT 1"));
        $when = consultation_when_label($slot);
        $teacherName = consultation_person_name($con, 'teachers', $teacherId);

        $body = $approved
            ? "وافق {$teacherName} على موعد الاستشارة" . ($when !== '' ? " يوم {$when}" : '') . "."
            : "اعتذر {$teacherName} عن موعد الاستشارة" . ($when !== '' ? " يوم {$when}" : '') . "، ويمكنك اختيار موعد آخر.";

        @send_notification_to_users(
            $con, 'student', [$studentId],
            $approved ? 'تم تأكيد الاستشارة' : 'تم إلغاء الاستشارة',
            $body,
            ['type' => 'consultation_reviewed', 'slot_id' => $slotId, 'approved' => $approved ? 1 : 0],
            'teacher', $teacherId, ['push']
        );
    }
}

if (!function_exists('notify_consultation_cancelled')) {
    /**
     * A booking was undone. Whoever did not do it is the one who needs telling.
     */
    function notify_consultation_cancelled($con, int $slotId, int $studentId, int $teacherId, string $cancelledBy, ?int $actorId = null): void {
        $slot = mysqli_fetch_assoc(mysqli_query($con, "SELECT slot_date, slot_time FROM consultation_slots WHERE id = " . (int)$slotId . " LIMIT 1"));
        $when = consultation_when_label($slot);
        $studentName = consultation_person_name($con, 'students', $studentId);
        $teacherName = consultation_person_name($con, 'teachers', $teacherId);
        $data = ['type' => 'consultation_cancelled', 'slot_id' => $slotId, 'cancelled_by' => $cancelledBy];

        if ($cancelledBy !== 'student' && $studentId > 0) {
            $by = $cancelledBy === 'admin' ? 'الإدارة' : $teacherName;
            @send_notification_to_users($con, 'student', [$studentId], 'إلغاء موعد استشارة',
                "ألغى {$by} موعد الاستشارة" . ($when !== '' ? " يوم {$when}" : '') . ".",
                $data, $cancelledBy === 'admin' ? 'admin' : 'teacher', $actorId, ['push']);
        }

        if ($cancelledBy !== 'teacher' && $teacherId > 0) {
            $by = $cancelledBy === 'admin' ? 'الإدارة' : "الطالب {$studentName}";
            @send_notification_to_users($con, 'teacher', [$teacherId], 'إلغاء موعد استشارة',
                "ألغى {$by} موعد الاستشارة" . ($when !== '' ? " يوم {$when}" : '') . ".",
                $data, $cancelledBy === 'admin' ? 'admin' : 'system', $actorId, ['push']);
        }
    }
}
