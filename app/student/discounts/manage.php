<?php
require_once __DIR__ . '/../../../helpers/discounts.php';

/**
 * Lets a student see what a discount code would take off a course or package before they commit.
 * Read-only by design: checking never consumes a use, so refreshing the page costs nothing.
 */
if (!function_exists('studentCheckDiscountCode')) {
    function studentCheckDiscountCode($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }

        $body = read_json_body();
        $code = (string)($body['code'] ?? $_GET['code'] ?? '');

        $courseIds = $body['course_ids'] ?? $_GET['course_ids'] ?? [];
        if (!is_array($courseIds)) {
            $courseIds = array_filter(array_map('trim', explode(',', (string)$courseIds)));
        }
        $singleCourse = (int)($body['course_id'] ?? $_GET['course_id'] ?? 0);
        if ($singleCourse > 0) {
            $courseIds[] = $singleCourse;
        }

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
