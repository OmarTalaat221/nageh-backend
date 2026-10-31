<?php
require_once __DIR__ . '/entities.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/notifications.php';

/**
 * Dated teaching appointments ("availability slots"), the real-calendar counterpart to the
 * weekly `class_sessions` pattern. One slot is one date + one time range for one teacher;
 * students are attached through `availability_slot_bookings`, so a private appointment
 * (capacity 1) and a group one (capacity > 1) run through exactly the same code.
 *
 * Shared by the admin, teacher and student routes so the three can never drift apart.
 */

if (!function_exists('availability_weekday_names')) {
    function availability_weekday_names(): array {
        return ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    }
}

if (!function_exists('availability_normalize_weekday')) {
    function availability_normalize_weekday($value): ?string {
        $normalized = ucfirst(strtolower(trim((string)$value)));
        return in_array($normalized, availability_weekday_names(), true) ? $normalized : null;
    }
}

if (!function_exists('availability_slot_status_labels')) {
    function availability_slot_status_labels(string $status): array {
        $map = [
            'available' => ['ar' => 'متاح', 'en' => 'Available'],
            'full' => ['ar' => 'مكتمل', 'en' => 'Full'],
            'cancelled' => ['ar' => 'ملغي', 'en' => 'Cancelled'],
            'completed' => ['ar' => 'منتهي', 'en' => 'Completed'],
        ];
        return $map[$status] ?? ['ar' => $status, 'en' => $status];
    }
}

/**
 * Turns a recurrence pattern into the concrete list of dates it covers.
 *
 * Patterns: `daily` (every day), `weekly` (the given weekday names), `monthly` (the given
 * days of the month - a month that is too short simply has no slot that month, rather than
 * silently sliding into the next one).
 *
 * Both caps below exist because this writes one row per date: without them a careless
 * "from 2020 to 2030, daily" would insert thousands of rows in a single request.
 */
if (!function_exists('availability_pattern_dates')) {
    function availability_pattern_dates(string $pattern, string $startDate, string $endDate, array $options = []): array {
        $maxDays = 400;
        $maxSlots = 300;

        $start = strtotime($startDate);
        $end = strtotime($endDate);
        if (!$start || !$end || $end < $start) {
            return ['error' => 'A valid start_date and end_date (end on or after start) are required'];
        }
        if ((($end - $start) / 86400) > $maxDays) {
            return ['error' => "The date range cannot be longer than {$maxDays} days"];
        }

        $dates = [];

        if ($pattern === 'daily') {
            for ($ts = $start; $ts <= $end; $ts = strtotime('+1 day', $ts)) {
                $dates[] = date('Y-m-d', $ts);
            }
        } elseif ($pattern === 'weekly') {
            $days = [];
            foreach ((array)($options['days'] ?? []) as $rawDay) {
                $day = availability_normalize_weekday($rawDay);
                if ($day && !in_array($day, $days, true)) {
                    $days[] = $day;
                }
            }
            if (!$days) {
                return ['error' => 'A weekly pattern needs at least one day in days[]'];
            }
            for ($ts = $start; $ts <= $end; $ts = strtotime('+1 day', $ts)) {
                if (in_array(date('l', $ts), $days, true)) {
                    $dates[] = date('Y-m-d', $ts);
                }
            }
        } elseif ($pattern === 'monthly') {
            $monthDays = [];
            foreach ((array)($options['days_of_month'] ?? []) as $rawDay) {
                $day = (int)$rawDay;
                if ($day >= 1 && $day <= 31 && !in_array($day, $monthDays, true)) {
                    $monthDays[] = $day;
                }
            }
            if (!$monthDays) {
                return ['error' => 'A monthly pattern needs at least one day in days_of_month[] (1-31)'];
            }
            $cursor = strtotime(date('Y-m-01', $start));
            while ($cursor <= $end) {
                $year = (int)date('Y', $cursor);
                $month = (int)date('n', $cursor);
                $daysInMonth = (int)date('t', $cursor);
                foreach ($monthDays as $day) {
                    if ($day > $daysInMonth) continue;
                    $ts = mktime(0, 0, 0, $month, $day, $year);
                    if ($ts >= $start && $ts <= $end) {
                        $dates[] = date('Y-m-d', $ts);
                    }
                }
                $cursor = strtotime('+1 month', $cursor);
            }
        } else {
            return ['error' => "Unknown pattern '{$pattern}' - use daily, weekly or monthly"];
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        if (count($dates) > $maxSlots) {
            return ['error' => "This pattern would create " . count($dates) . " slots; the limit is {$maxSlots} per request"];
        }
        if (!$dates) {
            return ['error' => 'This pattern does not match any date inside the given range'];
        }

        return ['dates' => $dates];
    }
}

if (!function_exists('availability_slot_bookings')) {
    function availability_slot_bookings($con, int $slotId): array {
        $stmt = mysqli_prepare($con, "
            SELECT b.id, b.student_id, b.student_subscription_id, b.status, b.booked_by_type, b.booked_by_id,
                   b.notes, b.created_at, s.name AS student_name, s.image AS student_image, s.phone AS student_phone
            FROM availability_slot_bookings b
            LEFT JOIN students s ON s.id = b.student_id
            WHERE b.slot_id = ? AND b.status <> 'cancelled'
            ORDER BY b.id ASC
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $slotId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['student_id'] = (int)$row['student_id'];
            $row['student_subscription_id'] = $row['student_subscription_id'] ? (int)$row['student_subscription_id'] : null;
            $row['booked_by_id'] = $row['booked_by_id'] ? (int)$row['booked_by_id'] : null;
            $rows[] = $row;
        }
        return $rows;
    }
}

if (!function_exists('availability_has_live_state')) {
    /**
     * Migration 103 adds `started_at` and `ended_at`. Until it has run a session simply never
     * reads as live, which is how it behaved before Start meant anything.
     */
    function availability_has_live_state($con): bool {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $res = @mysqli_query($con, "SHOW COLUMNS FROM `teacher_availability_slots` LIKE 'started_at'");

        return $cached = (bool)($res && mysqli_num_rows($res) > 0);
    }
}

if (!function_exists('get_availability_slot_details')) {
    function get_availability_slot_details($con, int $slotId, bool $withBookings = true): ?array {
        $stmt = mysqli_prepare($con, "SELECT * FROM teacher_availability_slots WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $slotId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        return availability_format_slot_row($con, $row, $withBookings);
    }
}

if (!function_exists('availability_format_slot_row')) {
    function availability_format_slot_row($con, array $row, bool $withBookings = true): array {
        $row['id'] = (int)$row['id'];
        // NULL means the slot belongs to the platform owner, who is not a teacher. Reported as
        // 0 so a client can tell it from a real teacher id.
        $isOwnerSlot = $row['teacher_id'] === null;
        $row['teacher_id'] = $isOwnerSlot ? 0 : (int)$row['teacher_id'];
        $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
        $row['capacity'] = (int)$row['capacity'];
        $row['booked_count'] = (int)$row['booked_count'];
        $row['is_online'] = (int)$row['is_online'];
        $row['created_by_id'] = $row['created_by_id'] ? (int)$row['created_by_id'] : null;
        $row['remaining_places'] = max(0, $row['capacity'] - $row['booked_count']);
        $row['is_bookable'] = $row['status'] === 'available' && $row['remaining_places'] > 0;

        $row['started_at'] = $row['started_at'] ?? null;
        $row['ended_at'] = $row['ended_at'] ?? null;

        // TWO GATES, AND EITHER ONE OPENS THE LINK.
        //
        // The clock is the rule: the session's own hour decides, so a teacher who forgets to
        // press Start does not lock out a room full of students who turned up on time. Ten
        // minutes early, because people arrive early.
        //
        // The human is the override: Start opens it ahead of the window when a session is
        // brought forward, and End closes it for good - which is why End is checked first and
        // beats everything. Without the clock a forgotten press locks everyone out; without
        // the human a forgotten End would leave the link open all night.
        $row['window_opens_at'] = date(
            'Y-m-d H:i:s',
            strtotime($row['slot_date'] . ' ' . $row['start_time']) - (10 * 60)
        );
        $row['window_closes_at'] = date(
            'Y-m-d H:i:s',
            strtotime($row['slot_date'] . ' ' . $row['end_time'])
        );

        $now = get_request_now()->format('Y-m-d H:i:s');
        $inWindow = $now >= $row['window_opens_at'] && $now <= $row['window_closes_at'];

        $row['is_live'] = empty($row['ended_at'])
            && (!empty($row['started_at']) || $inWindow);

        $labels = availability_slot_status_labels($row['status']);
        $row['status_label_ar'] = $labels['ar'];
        $row['status_label_en'] = $labels['en'];

        $row['start_datetime'] = $row['slot_date'] . ' ' . $row['start_time'];
        $row['end_datetime'] = $row['slot_date'] . ' ' . $row['end_time'];
        $row['day_of_week'] = date('l', strtotime($row['slot_date']));

        $row['teacher'] = $isOwnerSlot
            ? platform_owner_as_teacher($con)
            : get_teacher_details($con, $row['teacher_id']);
        $row['course'] = $row['course_id'] ? get_course_details($con, $row['course_id']) : null;
        $row['subject'] = $row['subject_id'] ? get_subject_details($con, $row['subject_id']) : null;

        if ($withBookings) {
            $row['bookings'] = availability_slot_bookings($con, $row['id']);
            $row['students'] = array_values(array_map(function ($b) {
                return ['id' => $b['student_id'], 'name' => $b['student_name'], 'image' => $b['student_image']];
            }, $row['bookings']));
        }

        return format_response_images($row);
    }
}

/**
 * `booked_count` and `status` are a cache of the bookings table, recomputed after every write so
 * the calendar screens can filter and colour slots without counting bookings per row. A slot the
 * admin cancelled outright stays cancelled - being emptied of bookings must not silently reopen it.
 */
if (!function_exists('availability_recompute_slot')) {
    function availability_recompute_slot($con, int $slotId): void {
        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM availability_slot_bookings WHERE slot_id = ? AND status <> 'cancelled'");
        mysqli_stmt_bind_param($countStmt, "i", $slotId);
        mysqli_stmt_execute($countStmt);
        $count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'] ?? 0);

        $slotStmt = mysqli_prepare($con, "SELECT capacity, status FROM teacher_availability_slots WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($slotStmt, "i", $slotId);
        mysqli_stmt_execute($slotStmt);
        $slot = mysqli_fetch_assoc(mysqli_stmt_get_result($slotStmt));
        if (!$slot) return;

        $status = $slot['status'];
        if (!in_array($status, ['cancelled', 'completed'], true)) {
            $status = $count >= (int)$slot['capacity'] ? 'full' : 'available';
        }

        $updStmt = mysqli_prepare($con, "UPDATE teacher_availability_slots SET booked_count = ?, status = ? WHERE id = ?");
        mysqli_stmt_bind_param($updStmt, "isi", $count, $status, $slotId);
        mysqli_stmt_execute($updStmt);
    }
}

if (!function_exists('availability_create_slot')) {
    function availability_create_slot($con, int $teacherId, array $body, string $byType, ?int $byId): array {
        // 0 is the platform owner: his slots are stored with no teacher at all.
        $teacherParam = $teacherId > 0 ? $teacherId : null;

        $slotDate = !empty($body['slot_date']) ? date('Y-m-d', strtotime($body['slot_date'])) : null;
        $startTime = !empty($body['start_time']) ? date('H:i:s', strtotime($body['start_time'])) : null;
        $endTime = !empty($body['end_time']) ? date('H:i:s', strtotime($body['end_time'])) : null;

        if (!$slotDate || !$startTime || !$endTime) {
            return ['status' => 'error', 'message' => 'slot_date, start_time and end_time are required'];
        }
        if ($endTime <= $startTime) {
            return ['status' => 'error', 'message' => 'end_time must be after start_time'];
        }

        $courseId = isset($body['course_id']) && (int)$body['course_id'] > 0 ? (int)$body['course_id'] : null;
        $subjectId = isset($body['subject_id']) && (int)$body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        $capacity = max(1, (int)($body['capacity'] ?? 1));
        $isOnline = isset($body['is_online']) ? (int)(bool)$body['is_online'] : 1;
        $meetingLink = trim((string)($body['meeting_link'] ?? '')) ?: null;
        $room = trim((string)($body['room'] ?? '')) ?: null;
        $notes = trim((string)($body['notes'] ?? '')) ?: null;

        $stmt = mysqli_prepare($con, "
            INSERT INTO teacher_availability_slots
                (teacher_id, course_id, subject_id, slot_date, start_time, end_time, capacity, is_online, meeting_link, room, notes, created_by_type, created_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt, "iiisssiissssi",
            $teacherParam, $courseId, $subjectId, $slotDate, $startTime, $endTime, $capacity, $isOnline, $meetingLink, $room, $notes, $byType, $byId
        );
        if (!mysqli_stmt_execute($stmt)) {
            if (mysqli_errno($con) === 1062) {
                return ['status' => 'error', 'message' => 'This teacher already has a slot at that date and time'];
            }
            return ['status' => 'error', 'message' => 'Failed to create slot: ' . mysqli_error($con)];
        }

        $slotId = (int)mysqli_insert_id($con);

        // Opened from one student's calendar, the slot is meant FOR that student - an empty
        // slot would be created and then hidden by the very filter that screen is showing,
        // which reads as "nothing was saved". Booking them here is what makes the date appear.
        $studentId = isset($body['student_id']) ? (int)$body['student_id'] : 0;
        $bookingWarning = null;
        if ($studentId > 0) {
            $booking = availability_book_slot($con, $slotId, $studentId, $byType, $byId);
            if (($booking['status'] ?? '') === 'error') {
                // The slot itself is real and the caller must be told it exists, so this is a
                // warning rather than a failure - deleting it here would throw away work the
                // admin can still use by booking the student by hand.
                $bookingWarning = $booking['message'] ?? 'Failed to book the student into the slot';
            }
        }

        $response = [
            'status' => 'success',
            'message' => $bookingWarning
                ? 'تم إنشاء الموعد، ولم يتم حجز الطالب: ' . $bookingWarning
                : 'تم إنشاء الموعد بنجاح',
            'data' => get_availability_slot_details($con, $slotId),
        ];
        if ($bookingWarning) {
            $response['booking_warning'] = $bookingWarning;
        }

        return $response;
    }
}

/**
 * Bulk-creates one slot per date produced by the pattern. Dates the teacher already has a slot
 * for at that exact time are skipped rather than failing the whole request, so re-running a
 * generator over an overlapping range is safe and the response says how many were skipped.
 */
if (!function_exists('availability_generate_slots')) {
    function availability_generate_slots($con, int $teacherId, array $body, string $byType, ?int $byId): array {
        // 0 is the platform owner: his slots are stored with no teacher at all.
        $teacherParam = $teacherId > 0 ? $teacherId : null;

        $pattern = strtolower(trim((string)($body['pattern'] ?? 'weekly')));
        $startDate = trim((string)($body['start_date'] ?? ''));
        $endDate = trim((string)($body['end_date'] ?? ''));
        $startTime = !empty($body['start_time']) ? date('H:i:s', strtotime($body['start_time'])) : null;
        $endTime = !empty($body['end_time']) ? date('H:i:s', strtotime($body['end_time'])) : null;

        if (!$startTime || !$endTime) {
            return ['status' => 'error', 'message' => 'start_time and end_time are required'];
        }
        if ($endTime <= $startTime) {
            return ['status' => 'error', 'message' => 'end_time must be after start_time'];
        }

        $result = availability_pattern_dates($pattern, $startDate, $endDate, [
            'days' => $body['days'] ?? [],
            'days_of_month' => $body['days_of_month'] ?? [],
        ]);
        if (isset($result['error'])) {
            return ['status' => 'error', 'message' => $result['error']];
        }

        $courseId = isset($body['course_id']) && (int)$body['course_id'] > 0 ? (int)$body['course_id'] : null;
        $subjectId = isset($body['subject_id']) && (int)$body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        $capacity = max(1, (int)($body['capacity'] ?? 1));
        $isOnline = isset($body['is_online']) ? (int)(bool)$body['is_online'] : 1;
        $meetingLink = trim((string)($body['meeting_link'] ?? '')) ?: null;
        $room = trim((string)($body['room'] ?? '')) ?: null;
        $notes = trim((string)($body['notes'] ?? '')) ?: null;

        $stmt = mysqli_prepare($con, "
            INSERT IGNORE INTO teacher_availability_slots
                (teacher_id, course_id, subject_id, slot_date, start_time, end_time, capacity, is_online, meeting_link, room, notes, created_by_type, created_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare slot insert: ' . mysqli_error($con)];
        }

        $createdIds = [];
        $skipped = 0;
        foreach ($result['dates'] as $slotDate) {
            mysqli_stmt_bind_param(
                $stmt, "iiisssiissssi",
                $teacherParam, $courseId, $subjectId, $slotDate, $startTime, $endTime, $capacity, $isOnline, $meetingLink, $room, $notes, $byType, $byId
            );
            if (!mysqli_stmt_execute($stmt)) {
                return ['status' => 'error', 'message' => 'Failed while creating slots: ' . mysqli_error($con)];
            }
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $createdIds[] = mysqli_insert_id($con);
            } else {
                $skipped++;
            }
        }

        return [
            'status' => 'success',
            'message' => 'تم توليد ' . count($createdIds) . ' موعدًا' . ($skipped ? " (تم تخطي {$skipped} موعدًا موجودًا بالفعل)" : ''),
            'data' => [
                'pattern' => $pattern,
                'created_count' => count($createdIds),
                'skipped_count' => $skipped,
                'dates' => $result['dates'],
                'created_ids' => $createdIds,
            ],
        ];
    }
}

if (!function_exists('availability_update_slot')) {
    function availability_update_slot($con, int $slotId, array $body, ?int $restrictToTeacherId = null): array {
        $slot = get_availability_slot_details($con, $slotId, false);
        if (!$slot) {
            return ['status' => 'error', 'message' => 'Slot not found'];
        }
        if ($restrictToTeacherId !== null && (int)$slot['teacher_id'] !== $restrictToTeacherId) {
            return ['status' => 'error', 'message' => 'This slot belongs to another teacher'];
        }

        $sets = [];
        $params = [];
        $types = '';

        if (!empty($body['slot_date'])) {
            $sets[] = 'slot_date = ?';
            $params[] = date('Y-m-d', strtotime($body['slot_date']));
            $types .= 's';
        }
        if (!empty($body['start_time'])) {
            $sets[] = 'start_time = ?';
            $params[] = date('H:i:s', strtotime($body['start_time']));
            $types .= 's';
        }
        if (!empty($body['end_time'])) {
            $sets[] = 'end_time = ?';
            $params[] = date('H:i:s', strtotime($body['end_time']));
            $types .= 's';
        }
        if (array_key_exists('capacity', $body)) {
            $newCapacity = max(1, (int)$body['capacity']);
            if ($newCapacity < (int)$slot['booked_count']) {
                return ['status' => 'error', 'message' => 'Capacity cannot be lower than the number of students already booked'];
            }
            $sets[] = 'capacity = ?';
            $params[] = $newCapacity;
            $types .= 'i';
        }
        if (array_key_exists('is_online', $body)) {
            $sets[] = 'is_online = ?';
            $params[] = (int)(bool)$body['is_online'];
            $types .= 'i';
        }
        foreach (['meeting_link', 'room', 'notes'] as $field) {
            if (array_key_exists($field, $body)) {
                $sets[] = "{$field} = ?";
                $params[] = trim((string)$body[$field]) ?: null;
                $types .= 's';
            }
        }
        foreach (['course_id', 'subject_id'] as $field) {
            if (array_key_exists($field, $body)) {
                $sets[] = "{$field} = ?";
                $params[] = (int)$body[$field] > 0 ? (int)$body[$field] : null;
                $types .= 'i';
            }
        }
        if (array_key_exists('status', $body)) {
            $status = strtolower(trim((string)$body['status']));
            if (!in_array($status, ['available', 'full', 'cancelled', 'completed'], true)) {
                return ['status' => 'error', 'message' => 'Invalid status'];
            }
            $sets[] = 'status = ?';
            $params[] = $status;
            $types .= 's';
        }

        if (!$sets) {
            return ['status' => 'error', 'message' => 'Nothing to update'];
        }

        $params[] = $slotId;
        $types .= 'i';
        $stmt = mysqli_prepare($con, "UPDATE teacher_availability_slots SET " . implode(', ', $sets) . " WHERE id = ?");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            if (mysqli_errno($con) === 1062) {
                return ['status' => 'error', 'message' => 'This teacher already has a slot at that date and time'];
            }
            return ['status' => 'error', 'message' => 'Failed to update slot: ' . mysqli_error($con)];
        }

        availability_recompute_slot($con, $slotId);

        return ['status' => 'success', 'message' => 'تم تعديل الموعد بنجاح', 'data' => get_availability_slot_details($con, $slotId)];
    }
}

if (!function_exists('availability_delete_slot')) {
    function availability_delete_slot($con, int $slotId, ?int $restrictToTeacherId = null): array {
        $slot = get_availability_slot_details($con, $slotId, false);
        if (!$slot) {
            return ['status' => 'error', 'message' => 'Slot not found'];
        }
        if ($restrictToTeacherId !== null && (int)$slot['teacher_id'] !== $restrictToTeacherId) {
            return ['status' => 'error', 'message' => 'This slot belongs to another teacher'];
        }

        $delBookings = mysqli_prepare($con, "DELETE FROM availability_slot_bookings WHERE slot_id = ?");
        mysqli_stmt_bind_param($delBookings, "i", $slotId);
        mysqli_stmt_execute($delBookings);

        $del = mysqli_prepare($con, "DELETE FROM teacher_availability_slots WHERE id = ?");
        mysqli_stmt_bind_param($del, "i", $slotId);
        if (!mysqli_stmt_execute($del)) {
            return ['status' => 'error', 'message' => 'Failed to delete slot: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'تم حذف الموعد بنجاح'];
    }
}

/**
 * The packages a student is actually in, as course ids.
 *
 * Read from the same two tables as availability_find_student_subscription rather than through
 * the subscriptions app file, so the calendar does not depend on that file being included.
 *
 * NULL means "do not filter at all": that is the answer for the demo account, which reaches
 * the whole catalogue without owning a subscription row. An EMPTY ARRAY is a real answer - a
 * student who is in no package - and the caller must tell the two apart.
 */
if (!function_exists('availability_student_course_ids')) {
    function availability_student_course_ids($con, int $studentId): ?array {
        if (function_exists('student_is_demo_account') && student_is_demo_account($con, $studentId)) {
            return null;
        }

        $stmt = mysqli_prepare($con, "
            SELECT DISTINCT ssc.course_id
            FROM student_subscriptions ss
            JOIN student_subscription_courses ssc ON ssc.student_subscription_id = ss.id
            WHERE ss.student_id = ? AND ss.is_active = 1
        ");
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $ids = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $courseId = (int)$row['course_id'];
            if ($courseId > 0) {
                $ids[] = $courseId;
            }
        }

        return $ids;
    }
}

/**
 * The student's live subscription is what a booking is charged against, and it is also what
 * bounds the booking in time: a slot after the subscription ends cannot be booked, which is the
 * whole point of giving a package a duration.
 */
if (!function_exists('availability_find_student_subscription')) {
    function availability_find_student_subscription($con, int $studentId, ?int $courseId): ?array {
        if ($courseId) {
            $stmt = mysqli_prepare($con, "
                SELECT ss.id, ss.start_date, ss.end_date
                FROM student_subscriptions ss
                JOIN student_subscription_courses ssc ON ssc.student_subscription_id = ss.id
                WHERE ss.student_id = ? AND ssc.course_id = ? AND ss.is_active = 1
                ORDER BY (ss.end_date IS NULL) DESC, ss.end_date DESC, ss.id DESC
                LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt, "ii", $studentId, $courseId);
        } else {
            $stmt = mysqli_prepare($con, "
                SELECT ss.id, ss.start_date, ss.end_date
                FROM student_subscriptions ss
                WHERE ss.student_id = ? AND ss.is_active = 1
                ORDER BY (ss.end_date IS NULL) DESC, ss.end_date DESC, ss.id DESC
                LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt, "i", $studentId);
        }
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }
}

if (!function_exists('availability_book_slot')) {
    function availability_book_slot($con, int $slotId, int $studentId, string $byType, ?int $byId, bool $requireSubscription = true): array {
        $slot = get_availability_slot_details($con, $slotId, false);
        if (!$slot) {
            return ['status' => 'error', 'message' => 'Slot not found'];
        }
        if (in_array($slot['status'], ['cancelled', 'completed'], true)) {
            return ['status' => 'error', 'message' => 'This slot is no longer open for booking'];
        }
        if ((int)$slot['booked_count'] >= (int)$slot['capacity']) {
            return ['status' => 'error', 'message' => 'This slot is already full'];
        }

        $student = get_student_details($con, $studentId);
        if (!$student) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }

        // A slot with no course belongs to no package, so there is no subscription that could
        // cover it. Without this, find_student_subscription(null) matches ANY active
        // subscription the student has and lets them into a session they bought nothing for -
        // the same hole the listing had. An admin booking (requireSubscription = false) is
        // still allowed to place somebody in one deliberately.
        if ($requireSubscription && empty($slot['course_id'])) {
            return ['status' => 'error', 'message' => 'This slot does not belong to any package'];
        }

        $subscription = availability_find_student_subscription($con, $studentId, $slot['course_id']);
        if ($requireSubscription && !$subscription) {
            return ['status' => 'error', 'message' => 'This student has no active subscription covering this slot'];
        }
        if ($subscription && !empty($subscription['end_date'])) {
            if (strtotime($slot['slot_date']) > strtotime(date('Y-m-d', strtotime($subscription['end_date'])))) {
                return ['status' => 'error', 'message' => 'This date is after the student\'s subscription ends'];
            }
        }

        $existing = mysqli_prepare($con, "SELECT id, status FROM availability_slot_bookings WHERE slot_id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($existing, "ii", $slotId, $studentId);
        mysqli_stmt_execute($existing);
        $existingRow = mysqli_fetch_assoc(mysqli_stmt_get_result($existing));

        $subscriptionId = $subscription ? (int)$subscription['id'] : null;

        if ($existingRow) {
            if ($existingRow['status'] !== 'cancelled') {
                return ['status' => 'error', 'message' => 'This student is already booked into this slot'];
            }
            $upd = mysqli_prepare($con, "
                UPDATE availability_slot_bookings
                SET status = 'booked', booked_by_type = ?, booked_by_id = ?, student_subscription_id = ?, cancelled_at = NULL
                WHERE id = ?
            ");
            $existingId = (int)$existingRow['id'];
            mysqli_stmt_bind_param($upd, "siii", $byType, $byId, $subscriptionId, $existingId);
            if (!mysqli_stmt_execute($upd)) {
                return ['status' => 'error', 'message' => 'Failed to book slot: ' . mysqli_error($con)];
            }
        } else {
            $ins = mysqli_prepare($con, "
                INSERT INTO availability_slot_bookings (slot_id, student_id, student_subscription_id, booked_by_type, booked_by_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($ins, "iiisi", $slotId, $studentId, $subscriptionId, $byType, $byId);
            if (!mysqli_stmt_execute($ins)) {
                return ['status' => 'error', 'message' => 'Failed to book slot: ' . mysqli_error($con)];
            }
        }

        availability_recompute_slot($con, $slotId);
        availability_notify_booking($con, $slotId, $studentId, $byType, true);

        return [
            'status' => 'success',
            'message' => 'تم حجز الموعد بنجاح',
            'data' => get_availability_slot_details($con, $slotId),
        ];
    }
}

if (!function_exists('availability_cancel_booking')) {
    function availability_cancel_booking($con, int $slotId, int $studentId, string $byType, ?int $byId): array {
        $stmt = mysqli_prepare($con, "SELECT id FROM availability_slot_bookings WHERE slot_id = ? AND student_id = ? AND status <> 'cancelled' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $slotId, $studentId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) {
            return ['status' => 'error', 'message' => 'No active booking found for this student in this slot'];
        }

        $upd = mysqli_prepare($con, "UPDATE availability_slot_bookings SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?");
        $bookingId = (int)$row['id'];
        mysqli_stmt_bind_param($upd, "i", $bookingId);
        if (!mysqli_stmt_execute($upd)) {
            return ['status' => 'error', 'message' => 'Failed to cancel booking: ' . mysqli_error($con)];
        }

        availability_recompute_slot($con, $slotId);
        availability_notify_booking($con, $slotId, $studentId, $byType, false);

        return ['status' => 'success', 'message' => 'تم إلغاء الحجز بنجاح', 'data' => get_availability_slot_details($con, $slotId)];
    }
}

/**
 * Whoever did not perform the action is the one who needs to hear about it: a student booking
 * notifies the teacher, an admin or teacher booking notifies the student.
 */
if (!function_exists('availability_notify_booking')) {
    function availability_notify_booking($con, int $slotId, int $studentId, string $byType, bool $isBooking): void {
        $slot = get_availability_slot_details($con, $slotId, false);
        if (!$slot) return;

        $when = $slot['slot_date'] . ' ' . substr($slot['start_time'], 0, 5);
        $title = $isBooking ? 'حجز موعد جديد' : 'إلغاء حجز موعد';

        if ($byType === 'student') {
            $student = get_student_details($con, $studentId);
            $name = $student['name'] ?? 'طالب';
            $body = $isBooking
                ? "قام {$name} بحجز موعد بتاريخ {$when}"
                : "قام {$name} بإلغاء حجز موعد بتاريخ {$when}";
            // An owner slot has no teacher to notify; he is the one acting from the admin panel.
            if ((int)$slot['teacher_id'] > 0) {
                send_notification_to_user($con, 'teacher', (int)$slot['teacher_id'], $title, $body, ['slot_id' => $slotId], 'system', null);
            }
            return;
        }

        $body = $isBooking
            ? "تم تحديد موعد لك بتاريخ {$when}"
            : "تم إلغاء موعدك بتاريخ {$when}";
        send_notification_to_user($con, 'student', $studentId, $title, $body, ['slot_id' => $slotId], 'system', null);
    }
}

/**
 * The one list query behind every calendar screen. Callers pass only the filters their role is
 * allowed to use; `only_bookable` is what the student screens use to hide full or cancelled slots.
 */
if (!function_exists('availability_list_slots')) {
    function availability_list_slots($con, array $filters): array {
        $where = [];
        $params = [];
        $types = '';

        if (isset($filters['teacher_id']) && trim((string)$filters['teacher_id']) !== '' && (int)$filters['teacher_id'] <= 0) {
            // Explicit 0: the platform owner's own slots.
            $where[] = 's.teacher_id IS NULL';
        } elseif (!empty($filters['teacher_id'])) {
            $where[] = 's.teacher_id = ?';
            $params[] = (int)$filters['teacher_id'];
            $types .= 'i';
        }
        if (!empty($filters['course_id'])) {
            $where[] = 's.course_id = ?';
            $params[] = (int)$filters['course_id'];
            $types .= 'i';
        }
        // The packages one student is allowed to see sessions of.
        //
        // A slot with no course belongs to no package, so it is NOT shown. Letting those
        // through was offering every student a "موعد عام" they had bought nothing to earn -
        // which is what a student who is in no package was being shown. Time with the platform
        // that belongs to no package is a consultation, and consultations are their own
        // feature with their own table.
        //
        // isset, not !empty: a student with no packages at all has an EMPTY list, and that is
        // an answer - skipping the filter there would show them every package on the platform.
        if (isset($filters['course_ids']) && is_array($filters['course_ids'])) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $filters['course_ids']))));
            if (!empty($ids)) {
                $where[] = 's.course_id IN (' . implode(',', $ids) . ')';
            } else {
                // In no package: there is nothing on this calendar for them.
                $where[] = '1 = 0';
            }
        }
        if (!empty($filters['subject_id'])) {
            $where[] = 's.subject_id = ?';
            $params[] = (int)$filters['subject_id'];
            $types .= 'i';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 's.slot_date >= ?';
            $params[] = date('Y-m-d', strtotime($filters['date_from']));
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 's.slot_date <= ?';
            $params[] = date('Y-m-d', strtotime($filters['date_to']));
            $types .= 's';
        }
        if (!empty($filters['status'])) {
            $where[] = 's.status = ?';
            $params[] = strtolower(trim($filters['status']));
            $types .= 's';
        }
        if (!empty($filters['only_bookable'])) {
            $where[] = "s.status = 'available' AND s.booked_count < s.capacity AND s.slot_date >= CURDATE()";
        }
        if (!empty($filters['student_id'])) {
            $where[] = "s.id IN (SELECT slot_id FROM availability_slot_bookings WHERE student_id = ? AND status <> 'cancelled')";
            $params[] = (int)$filters['student_id'];
            $types .= 'i';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(500, (int)($filters['limit'] ?? 200)));
        $offset = ($page - 1) * $limit;

        $countSql = "SELECT COUNT(*) AS total FROM teacher_availability_slots s {$whereSql}";
        if ($params) {
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);
        } else {
            $total = (int)(mysqli_fetch_assoc(mysqli_query($con, $countSql))['total'] ?? 0);
        }

        $sql = "SELECT s.* FROM teacher_availability_slots s {$whereSql} ORDER BY s.slot_date ASC, s.start_time ASC LIMIT ? OFFSET ?";
        $listParams = $params;
        $listTypes = $types . 'ii';
        $listParams[] = $limit;
        $listParams[] = $offset;

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $listTypes, ...$listParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = availability_format_slot_row($con, $row, true);
        }

        return build_paginated_response($rows, $page, $limit, $total);
    }
}

if (!function_exists('availability_set_live_state')) {
    /**
     * Start or end a package session.
     *
     * The same shape as consultations: a stored state, not a link somebody opens privately.
     * Until Start is pressed the students booked into the slot are shown no meeting link at
     * all, because a link handed out at booking time is an invitation to an empty room.
     *
     * $teacherScope limits the action to one teacher's own slots; null lets the admin act on
     * any of them, including the platform owner's, which belong to no teacher.
     */
    function availability_set_live_state($con, int $slotId, bool $start, ?int $teacherScope = null): array {
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => 'Slot id is required'];
        }

        if (!availability_has_live_state($con)) {
            return ['status' => 'error', 'message' => 'هذه الميزة تحتاج تحديث قاعدة البيانات (migration 103).'];
        }

        $scopeClause = $teacherScope !== null ? " AND teacher_id = " . (int)$teacherScope : "";
        $slot = mysqli_fetch_assoc(mysqli_query(
            $con,
            "SELECT id, teacher_id, slot_date, start_time, started_at, ended_at FROM teacher_availability_slots WHERE id = {$slotId}{$scopeClause} LIMIT 1"
        ));

        if (!$slot) {
            return ['status' => 'error', 'message' => 'Slot not found'];
        }

        if ($start) {
            // Starting again after an end re-opens it, so a session cut off by a dropped
            // connection is simply started once more.
            $sql = "UPDATE teacher_availability_slots SET started_at = NOW(), ended_at = NULL, status = 'in_progress' WHERE id = ?";
        } else {
            if (empty($slot['started_at'])) {
                return ['status' => 'error', 'message' => 'لم تبدأ هذه الحصة بعد.'];
            }
            $sql = "UPDATE teacher_availability_slots SET ended_at = NOW(), status = 'completed' WHERE id = ?";
        }

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $slotId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'تعذر تحديث حالة الحصة'];
        }

        availability_notify_live_state($con, $slotId, $slot, $start);

        return [
            'status' => 'success',
            'message' => $start ? 'بدأت الحصة، وتم إخطار الطلاب.' : 'انتهت الحصة، وتم إخطار الطلاب.',
            'data' => get_availability_slot_details($con, $slotId)
        ];
    }
}

if (!function_exists('availability_notify_live_state')) {
    /**
     * Everybody holding a live booking on this slot, told at once.
     *
     * A package session holds several students, unlike a consultation, so this is one send to
     * many rather than one each. Best-effort: the session must still start even if the
     * notification service is unreachable.
     */
    function availability_notify_live_state($con, int $slotId, array $slot, bool $start): void {
        $res = @mysqli_query($con, "SELECT student_id FROM availability_slot_bookings WHERE slot_id = {$slotId} AND status <> 'cancelled'");
        if (!$res) {
            return;
        }

        $studentIds = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $studentId = (int)$row['student_id'];
            if ($studentId > 0) {
                $studentIds[] = $studentId;
            }
        }

        if (empty($studentIds)) {
            return;
        }

        $when = trim(($slot['slot_date'] ?? '') . ' ' . substr((string)($slot['start_time'] ?? ''), 0, 5));

        if ($start) {
            $title = 'بدأت الحصة';
            $body = 'بدأت حصتك' . ($when !== '' ? " ({$when})" : '') . ' الآن، يمكنك الدخول.';
            $type = 'availability_session_started';
        } else {
            $title = 'انتهت الحصة';
            $body = 'انتهت حصتك' . ($when !== '' ? " ({$when})" : '') . '.';
            $type = 'availability_session_ended';
        }

        @send_notification_to_users(
            $con, 'student', $studentIds, $title, $body,
            ['type' => $type, 'slot_id' => $slotId],
            'system', null, ['push']
        );
    }
}
