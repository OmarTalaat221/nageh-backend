<?php
require_once __DIR__ . '/../../../helpers/availability.php';

/**
 * Admin-facing calendar of dated appointments. Same engine as the teacher routes, except the
 * admin always names the teacher explicitly and is not restricted to one.
 */

if (!function_exists('adminListAvailabilitySlots')) {
    function adminListAvailabilitySlots($con) {
        return availability_list_slots($con, [
            'teacher_id' => $_GET['teacher_id'] ?? null,
            'course_id' => $_GET['course_id'] ?? null,
            'subject_id' => $_GET['subject_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? $_GET['from'] ?? null,
            'date_to' => $_GET['date_to'] ?? $_GET['to'] ?? null,
            'status' => $_GET['status'] ?? null,
            'student_id' => $_GET['student_id'] ?? null,
            'page' => $_GET['page'] ?? 1,
            'limit' => $_GET['limit'] ?? 200,
        ]);
    }
}

if (!function_exists('adminCreateAvailabilitySlot')) {
    function adminCreateAvailabilitySlot($con, $adminData) {
        $body = read_json_body();
        // 0 is the platform owner, who opens slots of his own and is not a teacher.
        $teacherId = max(0, (int)($body['teacher_id'] ?? 0));
        $adminId = (int)($adminData['id'] ?? 0);
        return availability_create_slot($con, $teacherId, $body, 'admin', $adminId ?: null);
    }
}

if (!function_exists('adminGenerateAvailabilitySlots')) {
    function adminGenerateAvailabilitySlots($con, $adminData) {
        $body = read_json_body();
        // 0 is the platform owner, who opens slots of his own and is not a teacher.
        $teacherId = max(0, (int)($body['teacher_id'] ?? 0));
        $adminId = (int)($adminData['id'] ?? 0);
        return availability_generate_slots($con, $teacherId, $body, 'admin', $adminId ?: null);
    }
}

if (!function_exists('adminUpdateAvailabilitySlot')) {
    function adminUpdateAvailabilitySlot($con) {
        $body = read_json_body();
        $slotId = (int)($body['id'] ?? $body['slot_id'] ?? $_GET['id'] ?? 0);
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => 'Slot id is required'];
        }
        return availability_update_slot($con, $slotId, $body, null);
    }
}

if (!function_exists('adminDeleteAvailabilitySlot')) {
    function adminDeleteAvailabilitySlot($con) {
        $body = read_json_body();
        $slotId = (int)($body['id'] ?? $body['slot_id'] ?? $_GET['id'] ?? 0);
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => 'Slot id is required'];
        }
        return availability_delete_slot($con, $slotId, null);
    }
}

if (!function_exists('adminBookStudentIntoSlot')) {
    function adminBookStudentIntoSlot($con, $adminData) {
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);
        if ($slotId <= 0 || $studentId <= 0) {
            return ['status' => 'error', 'message' => 'slot_id and student_id are required'];
        }
        $adminId = (int)($adminData['id'] ?? 0);
        return availability_book_slot($con, $slotId, $studentId, 'admin', $adminId ?: null, false);
    }
}

if (!function_exists('adminCancelStudentBooking')) {
    function adminCancelStudentBooking($con, $adminData) {
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? $_GET['slot_id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? $_GET['student_id'] ?? 0);
        if ($slotId <= 0 || $studentId <= 0) {
            return ['status' => 'error', 'message' => 'slot_id and student_id are required'];
        }
        $adminId = (int)($adminData['id'] ?? 0);
        return availability_cancel_booking($con, $slotId, $studentId, 'admin', $adminId ?: null);
    }
}

/**
 * Builds a whole schedule for one student in one call: generate the dates from a pattern, then
 * book that student into every one of them. This is the "admin opens a subscribed student and
 * sets their dates" flow - the slots and the bookings are created together so a half-made
 * schedule (dates with nobody in them) cannot be left behind by a mistyped second request.
 */
if (!function_exists('adminScheduleStudent')) {
    function adminScheduleStudent($con, $adminData) {
        $body = read_json_body();
        $teacherId = (int)($body['teacher_id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);
        // teacher_id 0 is the platform owner; only the student is genuinely required here.
        $teacherId = max(0, $teacherId);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'student_id is required'];
        }

        $student = get_student_details($con, $studentId);
        if (!$student) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }

        $courseId = isset($body['course_id']) && (int)$body['course_id'] > 0 ? (int)$body['course_id'] : null;

        // Default the range to the student's own subscription window, which is what the package
        // duration now decides - so "schedule this student" means "for as long as they paid for".
        $subscription = availability_find_student_subscription($con, $studentId, $courseId);
        if (empty($body['start_date']) && $subscription) {
            $body['start_date'] = date('Y-m-d', strtotime($subscription['start_date']));
        }
        if (empty($body['end_date']) && $subscription && !empty($subscription['end_date'])) {
            $body['end_date'] = date('Y-m-d', strtotime($subscription['end_date']));
        }
        if (empty($body['start_date']) || empty($body['end_date'])) {
            return ['status' => 'error', 'message' => 'start_date and end_date are required (the student has no dated subscription to infer them from)'];
        }

        $adminId = (int)($adminData['id'] ?? 0) ?: null;
        $generated = availability_generate_slots($con, $teacherId, $body, 'admin', $adminId);
        if (($generated['status'] ?? '') !== 'success') {
            return $generated;
        }

        $dates = $generated['data']['dates'] ?? [];
        $startTime = date('H:i:s', strtotime($body['start_time']));

        $booked = 0;
        $failures = [];
        foreach ($dates as $date) {
            $teacherMatch = $teacherId > 0 ? "teacher_id = ?" : "teacher_id IS NULL";
            $stmt = mysqli_prepare($con, "SELECT id FROM teacher_availability_slots WHERE {$teacherMatch} AND slot_date = ? AND start_time = ? LIMIT 1");
            if ($teacherId > 0) {
                mysqli_stmt_bind_param($stmt, "iss", $teacherId, $date, $startTime);
            } else {
                mysqli_stmt_bind_param($stmt, "ss", $date, $startTime);
            }
            mysqli_stmt_execute($stmt);
            $slotRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$slotRow) continue;

            $result = availability_book_slot($con, (int)$slotRow['id'], $studentId, 'admin', $adminId, false);
            if (($result['status'] ?? '') === 'success') {
                $booked++;
            } else {
                $failures[] = ['date' => $date, 'message' => $result['message'] ?? 'failed'];
            }
        }

        return [
            'status' => 'success',
            'message' => "تم تحديد {$booked} موعدًا للطالب",
            'data' => [
                'student_id' => $studentId,
                'teacher_id' => $teacherId,
                'created_slots' => $generated['data']['created_count'] ?? 0,
                'reused_slots' => $generated['data']['skipped_count'] ?? 0,
                'booked_count' => $booked,
                'dates' => $dates,
                'failures' => $failures,
            ],
        ];
    }
}
