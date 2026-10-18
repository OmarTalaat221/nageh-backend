<?php
require_once __DIR__ . '/../../../helpers/availability.php';

/**
 * Student-facing view of dated appointments: the teacher's open slots, and the student's own
 * booked ones. A student may only ever act on themselves - the student id comes from the token.
 */

if (!function_exists('student_availability_id')) {
    function student_availability_id($studentData): int {
        if (is_array($studentData)) {
            return (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        }
        if (is_object($studentData)) {
            return (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        return 0;
    }
}

if (!function_exists('studentListAvailableSlots')) {
    function studentListAvailableSlots($con, $studentData) {
        $studentId = student_availability_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication required'];
        }

        // Neither filter is required. Demanding a teacher or a course before answering left the
        // student staring at an empty calendar with nothing to pick from and no way to discover
        // what to ask for.
        //
        // teacher_id=0 means the platform owner specifically, so what matters is whether the
        // parameter was sent at all, not whether it is truthy.
        $teacherSent = isset($_GET['teacher_id']) && trim((string)$_GET['teacher_id']) !== '';
        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        $courseId = (int)($_GET['course_id'] ?? 0);

        // Without a course named, the answer is the packages this student actually has - not
        // every package on the platform. Booking already refuses a slot the student has no
        // subscription for, so listing them was offering a calendar full of dates that each
        // turned into an error on the way back.
        $courseIds = $courseId > 0
            ? null
            : availability_student_course_ids($con, $studentId);

        return availability_list_slots($con, [
            'teacher_id' => $teacherSent ? $teacherId : null,
            'course_id' => $courseId ?: null,
            'course_ids' => $courseIds,
            'subject_id' => $_GET['subject_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? $_GET['from'] ?? null,
            'date_to' => $_GET['date_to'] ?? $_GET['to'] ?? null,
            'only_bookable' => true,
            'page' => $_GET['page'] ?? 1,
            'limit' => $_GET['limit'] ?? 200,
        ]);
    }
}

if (!function_exists('studentMyScheduleSlots')) {
    function studentMyScheduleSlots($con, $studentData) {
        $studentId = student_availability_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication required'];
        }

        return availability_list_slots($con, [
            'student_id' => $studentId,
            'teacher_id' => $_GET['teacher_id'] ?? null,
            'course_id' => $_GET['course_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? $_GET['from'] ?? null,
            'date_to' => $_GET['date_to'] ?? $_GET['to'] ?? null,
            'page' => $_GET['page'] ?? 1,
            'limit' => $_GET['limit'] ?? 200,
        ]);
    }
}

if (!function_exists('studentBookAvailabilitySlot')) {
    function studentBookAvailabilitySlot($con, $studentData) {
        $studentId = student_availability_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication required'];
        }

        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? 0);
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => 'slot_id is required'];
        }

        // A student, unlike an admin or teacher, must hold a live subscription to book.
        return availability_book_slot($con, $slotId, $studentId, 'student', $studentId, true);
    }
}

if (!function_exists('studentCancelAvailabilityBooking')) {
    function studentCancelAvailabilityBooking($con, $studentData) {
        $studentId = student_availability_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication required'];
        }

        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? $_GET['slot_id'] ?? 0);
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => 'slot_id is required'];
        }

        return availability_cancel_booking($con, $slotId, $studentId, 'student', $studentId);
    }
}
