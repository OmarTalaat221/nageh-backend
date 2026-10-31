<?php
require_once __DIR__ . '/../../../helpers/availability.php';

/**
 * Teacher-facing calendar of dated appointments. A teacher only ever sees and edits their own
 * slots - the teacher id is taken from the token, never from the request - and can book one of
 * their own students into a slot on that student's behalf.
 */

if (!function_exists('teacher_availability_id')) {
    function teacher_availability_id($teacherData): int {
        if (is_array($teacherData)) {
            return (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        }
        if (is_object($teacherData)) {
            return (int)($teacherData->id ?? $teacherData->teacher_id ?? 0);
        }
        return 0;
    }
}

if (!function_exists('teacherListAvailabilitySlots')) {
    function teacherListAvailabilitySlots($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication required'];
        }

        return availability_list_slots($con, [
            'teacher_id' => $teacherId,
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

if (!function_exists('teacherCreateAvailabilitySlot')) {
    function teacherCreateAvailabilitySlot($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication required'];
        }
        return availability_create_slot($con, $teacherId, read_json_body(), 'teacher', $teacherId);
    }
}

if (!function_exists('teacherBulkDeleteAvailabilitySlots')) {
    function teacherBulkDeleteAvailabilitySlots($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication required'];
        }
        $body = read_json_body();
        // The teacher id is passed as the restriction rather than as a filter, so no body
        // field can widen this past the caller's own calendar.
        return availability_bulk_delete_slots($con, [
            'course_id' => $body['course_id'] ?? null,
            'student_id' => $body['student_id'] ?? null,
            'date_from' => $body['date_from'] ?? $body['from'] ?? null,
            'date_to' => $body['date_to'] ?? $body['to'] ?? null,
            'include_started' => $body['include_started'] ?? false,
        ], $teacherId);
    }
}

if (!function_exists('teacherGenerateAvailabilitySlots')) {
    function teacherGenerateAvailabilitySlots($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication required'];
        }
        return availability_generate_slots($con, $teacherId, read_json_body(), 'teacher', $teacherId);
    }
}

if (!function_exists('teacherUpdateAvailabilitySlot')) {
    function teacherUpdateAvailabilitySlot($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        $body = read_json_body();
        $slotId = (int)($body['id'] ?? $body['slot_id'] ?? $_GET['id'] ?? 0);
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => 'Slot id is required'];
        }
        return availability_update_slot($con, $slotId, $body, $teacherId);
    }
}

if (!function_exists('teacherDeleteAvailabilitySlot')) {
    function teacherDeleteAvailabilitySlot($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        $body = read_json_body();
        $slotId = (int)($body['id'] ?? $body['slot_id'] ?? $_GET['id'] ?? 0);
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => 'Slot id is required'];
        }
        return availability_delete_slot($con, $slotId, $teacherId);
    }
}

if (!function_exists('teacherBookStudentIntoSlot')) {
    function teacherBookStudentIntoSlot($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);
        if ($slotId <= 0 || $studentId <= 0) {
            return ['status' => 'error', 'message' => 'slot_id and student_id are required'];
        }

        $slot = get_availability_slot_details($con, $slotId, false);
        if (!$slot) {
            return ['status' => 'error', 'message' => 'Slot not found'];
        }
        if ((int)$slot['teacher_id'] !== $teacherId) {
            return ['status' => 'error', 'message' => 'This slot belongs to another teacher'];
        }

        return availability_book_slot($con, $slotId, $studentId, 'teacher', $teacherId, false);
    }
}

if (!function_exists('teacherCancelStudentBooking')) {
    function teacherCancelStudentBooking($con, $teacherData) {
        $teacherId = teacher_availability_id($teacherData);
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? $_GET['slot_id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? $_GET['student_id'] ?? 0);
        if ($slotId <= 0 || $studentId <= 0) {
            return ['status' => 'error', 'message' => 'slot_id and student_id are required'];
        }

        $slot = get_availability_slot_details($con, $slotId, false);
        if (!$slot) {
            return ['status' => 'error', 'message' => 'Slot not found'];
        }
        if ((int)$slot['teacher_id'] !== $teacherId) {
            return ['status' => 'error', 'message' => 'This slot belongs to another teacher'];
        }

        return availability_cancel_booking($con, $slotId, $studentId, 'teacher', $teacherId);
    }
}

if (!function_exists('teacherStartAvailabilitySlot')) {
    /**
     * Same as the admin pair, scoped to this teacher's own slots.
     */
    function teacherStartAvailabilitySlot($con, $teacherData) {
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? $_GET['id'] ?? 0);

        return availability_set_live_state($con, $slotId, true, (int)($teacherData['id'] ?? 0));
    }
}

if (!function_exists('teacherEndAvailabilitySlot')) {
    function teacherEndAvailabilitySlot($con, $teacherData) {
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? $_GET['id'] ?? 0);

        return availability_set_live_state($con, $slotId, false, (int)($teacherData['id'] ?? 0));
    }
}
