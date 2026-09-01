<?php
require_once __DIR__ . '/../../../../helpers/JWT.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../auth/register.php';
require_once __DIR__ . '/../../student/onboard/manage.php';

if (!function_exists('parentOnboardStartWithCode')) {
    function parentOnboardStartWithCode($con) {
        $body = read_json_body();
        $codeStr = strtoupper(trim($body['code'] ?? ''));
        if (empty($codeStr)) {
            return ['status' => 'error', 'message' => 'Subscription code is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, title, is_active, claimed_by_student_id FROM subscription_codes WHERE code = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $codeStr);
        mysqli_stmt_execute($stmt);
        $codeRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$codeRow) {
            return ['status' => 'error', 'message' => 'Invalid subscription code'];
        }
        if ((int)$codeRow['is_active'] !== 1) {
            return ['status' => 'error', 'message' => 'Subscription code is inactive'];
        }

        $claimedStudentId = $codeRow['claimed_by_student_id'] ? (int)$codeRow['claimed_by_student_id'] : 0;
        if ($claimedStudentId <= 0) {
            return ['status' => 'error', 'message' => 'This code has not been activated by a student yet. Ask your child to activate it first.'];
        }

        $stStmt = mysqli_prepare($con, "SELECT id, name FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stStmt, "i", $claimedStudentId);
        mysqli_stmt_execute($stStmt);
        $studentRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));
        if (!$studentRow) {
            return ['status' => 'error', 'message' => 'Linked student not found'];
        }

        $onboardingToken = generateAccessToken([
            'purpose' => 'parent_onboarding',
            'code' => $codeStr,
            'student_id' => $claimedStudentId,
        ], 'parent', 1800);

        return [
            'status' => 'success',
            'message' => 'Code verified, complete your profile to finish signing up',
            'data' => [
                'onboarding_token' => $onboardingToken,
                'expires_in' => 1800,
                'student' => [
                    'id' => (int)$studentRow['id'],
                    'name' => $studentRow['name'],
                ],
            ]
        ];
    }
}

if (!function_exists('parentOnboardComplete')) {
    function parentOnboardComplete($con) {
        $token = get_bearer_token_from_request();
        if (!$token) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $decoded = verifyAccessToken($token, 'parent');
        if (!$decoded || ($decoded['purpose'] ?? '') !== 'parent_onboarding') {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $studentId = (int)($decoded['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Linked student not found'];
        }

        $stCheck = mysqli_prepare($con, "SELECT id FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stCheck, "i", $studentId);
        mysqli_stmt_execute($stCheck);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stCheck))) {
            return ['status' => 'error', 'message' => 'Linked student not found'];
        }

        $registerResult = parentRegister($con);
        if (($registerResult['status'] ?? 'error') !== 'success') {
            return $registerResult;
        }

        $newParentId = (int)($registerResult['data']['parent']['id'] ?? 0);
        if ($newParentId > 0) {
            $linkStmt = mysqli_prepare($con, "INSERT INTO parent_students (parent_id, student_id) VALUES (?, ?)");
            if ($linkStmt) {
                mysqli_stmt_bind_param($linkStmt, "ii", $newParentId, $studentId);
                mysqli_stmt_execute($linkStmt);
            }
            $registerResult['data']['linked_student_id'] = $studentId;
        }

        return $registerResult;
    }
}
