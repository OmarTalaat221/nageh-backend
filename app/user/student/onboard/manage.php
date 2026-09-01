<?php
require_once __DIR__ . '/../../../../helpers/JWT.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../auth/register.php';
require_once __DIR__ . '/../subscriptions/manage.php';

if (!function_exists('get_bearer_token_from_request')) {
    function get_bearer_token_from_request(): ?string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

if (!function_exists('validate_subscription_code_for_onboarding')) {
    function validate_subscription_code_for_onboarding($con, string $codeStr) {
        $stmt = mysqli_prepare($con, "SELECT id, code, title, max_uses, used_count, valid_from, valid_to, is_active, claimed_by_student_id FROM subscription_codes WHERE code = ? LIMIT 1");
        if (!$stmt) return ['status' => 'error', 'message' => 'Query preparation error'];
        mysqli_stmt_bind_param($stmt, "s", $codeStr);
        mysqli_stmt_execute($stmt);
        $codeRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$codeRow) {
            return ['status' => 'error', 'message' => 'Invalid subscription code'];
        }
        if ((int)$codeRow['is_active'] !== 1) {
            return ['status' => 'error', 'message' => 'Subscription code is inactive'];
        }

        $now = date('Y-m-d H:i:s');
        if ($codeRow['valid_from'] && $codeRow['valid_from'] > $now) {
            return ['status' => 'error', 'message' => 'Subscription code is not active yet'];
        }
        if ($codeRow['valid_to'] && $codeRow['valid_to'] < $now) {
            return ['status' => 'error', 'message' => 'Subscription code has expired'];
        }
        if ((int)$codeRow['max_uses'] > 0 && (int)$codeRow['used_count'] >= (int)$codeRow['max_uses']) {
            return ['status' => 'error', 'message' => 'Subscription code has already reached its maximum uses'];
        }

        return ['status' => 'success', 'data' => $codeRow];
    }
}

if (!function_exists('studentOnboardStartWithCode')) {
    function studentOnboardStartWithCode($con) {
        $body = read_json_body();
        $codeStr = strtoupper(trim($body['code'] ?? ''));
        if (empty($codeStr)) {
            return ['status' => 'error', 'message' => 'Subscription code is required'];
        }

        $check = validate_subscription_code_for_onboarding($con, $codeStr);
        if ($check['status'] !== 'success') {
            return $check;
        }

        $onboardingToken = generateAccessToken([
            'purpose' => 'student_onboarding',
            'code' => $codeStr,
        ], 'student', 1800);

        return [
            'status' => 'success',
            'message' => 'Code verified, complete your profile to finish signing up',
            'data' => [
                'onboarding_token' => $onboardingToken,
                'expires_in' => 1800,
                'code_title' => $check['data']['title'] ?? null,
            ]
        ];
    }
}

if (!function_exists('studentOnboardComplete')) {
    function studentOnboardComplete($con) {
        $token = get_bearer_token_from_request();
        if (!$token) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $decoded = verifyAccessToken($token, 'student');
        if (!$decoded || ($decoded['purpose'] ?? '') !== 'student_onboarding') {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $codeStr = strtoupper(trim($decoded['code'] ?? ''));
        $check = validate_subscription_code_for_onboarding($con, $codeStr);
        if ($check['status'] !== 'success') {
            return $check;
        }

        $registerResult = studentRegister($con);
        if (($registerResult['status'] ?? 'error') !== 'success') {
            return $registerResult;
        }

        $newStudentId = (int)($registerResult['data']['student']['id'] ?? 0);
        if ($newStudentId > 0) {
            $redeemResult = studentRedeemSubscriptionCode($con, ['id' => $newStudentId], $codeStr);
            $registerResult['data']['subscription'] = $redeemResult['data'] ?? null;
            $registerResult['data']['subscription_status'] = $redeemResult['status'] ?? 'error';
        }

        return $registerResult;
    }
}
