<?php
require_once __DIR__ . '/../../../helpers/discounts.php';

/**
 * Reads the course/package ids out of a student request, whichever shape they arrived in:
 * `course_ids` as an array or a comma-separated string, or a single `course_id`.
 */
if (!function_exists('student_discount_request_course_ids')) {
    function student_discount_request_course_ids(array $body): array {
        $courseIds = $body['course_ids'] ?? $_GET['course_ids'] ?? [];
        if (!is_array($courseIds)) {
            $courseIds = array_filter(array_map('trim', explode(',', (string)$courseIds)));
        }
        $singleCourse = (int)($body['course_id'] ?? $_GET['course_id'] ?? 0);
        if ($singleCourse > 0) {
            $courseIds[] = $singleCourse;
        }
        return $courseIds;
    }
}

if (!function_exists('student_discount_id')) {
    function student_discount_id($studentData): int {
        if (is_array($studentData)) {
            return (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        }
        if (is_object($studentData)) {
            return (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        return 0;
    }
}

/**
 * Lets a student see what a discount code would take off a course or package before they commit.
 * Read-only by design: checking never consumes a use, so refreshing the page costs nothing.
 */
if (!function_exists('studentCheckDiscountCode')) {
    function studentCheckDiscountCode($con, $studentData = null) {
        $studentId = student_discount_id($studentData);

        $body = read_json_body();
        $code = (string)($body['code'] ?? $_GET['code'] ?? '');
        $courseIds = student_discount_request_course_ids($body);

        if (!$courseIds) {
            return ['status' => 'error', 'message' => 'course_id is required'];
        }

        $result = discount_validate_code($con, $code, $studentId ?: null, $courseIds);

        return [
            'status' => !empty($result['valid']) ? 'success' : 'error',
            'message' => $result['message'] ?? '',
            'data' => $result,
        ];
    }
}

/**
 * The student actually USING the code. There is no payment gateway here, so using a code cannot
 * mean paying with it - it means pinning it to the course the student wants. The claim is stored
 * as `pending`, which costs the code nothing, and the admin's grant is what turns it into a real
 * discount. Requires a logged-in student: an anonymous visitor has nothing to pin a code to.
 */
if (!function_exists('studentApplyDiscountCode')) {
    function studentApplyDiscountCode($con, $studentData = null) {
        $studentId = student_discount_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'يجب تسجيل الدخول لاستخدام كود الخصم'];
        }

        $body = read_json_body();
        $code = (string)($body['code'] ?? '');
        $courseIds = student_discount_request_course_ids($body);

        if (!$courseIds) {
            return ['status' => 'error', 'message' => 'course_id is required'];
        }

        $result = discount_claim_code($con, $code, $studentId, $courseIds);

        return [
            'status' => !empty($result['valid']) ? 'success' : 'error',
            'message' => $result['message'] ?? '',
            'data' => $result,
        ];
    }
}

/**
 * Every code this student is currently holding. `is_still_valid` is false when the code stopped
 * working after the claim was made (deactivated, expired, or fully used up by other students),
 * so the UI can grey the claim out instead of promising a discount that will be refused.
 */
if (!function_exists('studentListDiscountClaims')) {
    function studentListDiscountClaims($con, $studentData = null) {
        $studentId = student_discount_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'يجب تسجيل الدخول'];
        }

        $body = read_json_body_raw_safe();
        $courseIds = student_discount_request_course_ids($body);

        return [
            'status' => 'success',
            'data' => discount_list_pending_claims($con, $studentId, $courseIds),
        ];
    }
}

if (!function_exists('studentCancelDiscountClaim')) {
    function studentCancelDiscountClaim($con, $studentData = null) {
        $studentId = student_discount_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'يجب تسجيل الدخول'];
        }

        $body = read_json_body();
        $claimId = (int)($body['id'] ?? $body['claim_id'] ?? $_GET['id'] ?? 0);
        if ($claimId <= 0) {
            return ['status' => 'error', 'message' => 'claim id is required'];
        }

        if (!discount_cancel_claim($con, $studentId, $claimId)) {
            return ['status' => 'error', 'message' => 'كود الخصم ده مش موجود في طلباتك'];
        }

        return ['status' => 'success', 'message' => 'تم إلغاء كود الخصم من طلبك'];
    }
}
