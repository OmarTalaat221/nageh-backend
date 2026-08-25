<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/email_verification.php';
require_once __DIR__ . '/../../../../helpers/upload.php';

if (!function_exists('parentRegister')) {
    function parentRegister($con) {
        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $cityId = (isset($body['city_id']) && is_numeric($body['city_id']) && (int)$body['city_id'] > 0) ? (int)$body['city_id'] : null;
        $image = !empty(trim($body['image'] ?? '')) ? trim($body['image']) : null;

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('name_email_password_required')];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => trans('invalid_email')];
        }

        if (strlen($password) < 6) {
            return ['status' => 'error', 'message' => trans('short_password')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($res)) {
            return ['status' => 'error', 'message' => trans('email_already_registered')];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($con, "INSERT INTO parents (name, email, password, image, city_id, is_email_verified) VALUES (?, ?, ?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $hashedPassword, $image, $cityId);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create parent account: ' . mysqli_error($con)];
        }

        $parentId = mysqli_insert_id($con);
        
        $otpInfo = generateAndSendOtp($con, 'parent', $parentId, $email);

        $payload = [
            'id' => (int)$parentId,
            'name' => $name,
            'email' => $email,
            'role' => 'parent'
        ];

        $tokens = generateParentTokens($payload);

        return [
            'status' => 'success',
            'message' => trans('parent_register_success'),
            'data' => [
                'parent' => format_response_images([
                    'id' => (int)$parentId,
                    'name' => $name,
                    'email' => $email,
                    'image' => $image,
                    'is_email_verified' => 0,
                    'city' => get_city_details($con, $cityId)
                ]),
                'verification' => [
                    'otp_code' => $otpInfo['code'],
                    'expires_at' => $otpInfo['expires_at']
                ],
                'tokens' => $tokens
            ]
        ];
    }
}
