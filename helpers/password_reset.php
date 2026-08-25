<?php
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/mailer.php';

if (!function_exists('sendPasswordResetOtp')) {
    function sendPasswordResetOtp($con, string $userType, string $email): array {
        $email = strtolower(trim($email));
        $userType = strtolower(trim($userType));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => trans('invalid_email')];
        }

        $tableMap = [
            'student' => 'students',
            'teacher' => 'teachers',
            'parent' => 'parents',
            'admin' => 'admins'
        ];

        $tableName = $tableMap[$userType] ?? 'students';

        $checkStmt = mysqli_prepare($con, "SELECT id, name FROM {$tableName} WHERE email = ? LIMIT 1");
        if (!$checkStmt) {
            return ['status' => 'error', 'message' => trans('profile_not_found')];
        }

        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $userRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));

        if (!$userRow) {
            return ['status' => 'error', 'message' => trans('profile_not_found')];
        }

        $userId = (int)$userRow['id'];
        $userName = $userRow['name'] ?? 'المستخدم';

        $code = sprintf('%06d', mt_rand(100000, 999999));
        $resetToken = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60));

        $invStmt = mysqli_prepare($con, "UPDATE password_reset_codes SET is_used = 1 WHERE email = ? AND user_type = ? AND is_used = 0");
        if ($invStmt) {
            mysqli_stmt_bind_param($invStmt, "ss", $email, $userType);
            mysqli_stmt_execute($invStmt);
        }

        $stmt = mysqli_prepare($con, "INSERT INTO password_reset_codes (user_type, user_id, email, code, reset_token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sissss", $userType, $userId, $email, $code, $resetToken, $expiresAt);
            mysqli_stmt_execute($stmt);
        }

        $subject = "إعادة تعيين كلمة المرور - منصة ناجح التعليمية";
        $htmlBody = "
            <div dir='rtl' style='font-family: Arial, sans-serif; padding: 20px; color: #333; background-color: #f9f9f9; border-radius: 8px;'>
                <h2 style='color: #1976D2;'>مرحباً {$userName} 🔐</h2>
                <p>تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في <strong>منصة ناجح التعليمية</strong>.</p>
                <p>رمز إعادة التعيين الخاص بك هو:</p>
                <div style='background: #e3f2fd; padding: 15px; text-align: center; border-radius: 6px; font-size: 26px; font-weight: bold; letter-spacing: 5px; color: #0D47A1;'>
                    {$code}
                </div>
                <p style='color: #777; margin-top: 15px;'>هذا الرمز صالِح لمدة 15 دقيقة فقط. إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد.</p>
            </div>
        ";

        sendMailMessage($email, $subject, $htmlBody);

        return [
            'status' => 'success',
            'message' => trans('password_reset_code_sent'),
            'data' => [
                'email' => $email,
                'user_type' => $userType,
                'reset_code' => $code,
                'reset_token' => $resetToken,
                'expires_at' => $expiresAt
            ]
        ];
    }
}

if (!function_exists('verifyPasswordResetOtp')) {
    function verifyPasswordResetOtp($con, string $userType, string $email, string $code): array {
        $email = strtolower(trim($email));
        $code = trim($code);
        $userType = strtolower(trim($userType));

        if (empty($email) || empty($code)) {
            return ['status' => 'error', 'message' => trans('email_password_required')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, reset_token FROM password_reset_codes WHERE email = ? AND code = ? AND user_type = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
        if (!$stmt) return ['status' => 'error', 'message' => trans('invalid_otp_code')];

        mysqli_stmt_bind_param($stmt, "sss", $email, $code, $userType);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => trans('invalid_otp_code')];
        }

        return [
            'status' => 'success',
            'message' => trans('code_verified_success'),
            'data' => [
                'email' => $email,
                'user_type' => $userType,
                'reset_token' => $row['reset_token']
            ]
        ];
    }
}

if (!function_exists('resetPasswordWithCodeOrToken')) {
    function resetPasswordWithCodeOrToken($con, string $userType, string $email, string $codeOrToken, string $newPassword): array {
        $email = strtolower(trim($email));
        $codeOrToken = trim($codeOrToken);
        $newPassword = trim($newPassword);
        $userType = strtolower(trim($userType));

        if (empty($email) || empty($codeOrToken) || empty($newPassword)) {
            return ['status' => 'error', 'message' => trans('email_password_required')];
        }

        if (strlen($newPassword) < 6) {
            return ['status' => 'error', 'message' => trans('short_password')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, user_id FROM password_reset_codes WHERE email = ? AND (code = ? OR reset_token = ?) AND user_type = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
        if (!$stmt) return ['status' => 'error', 'message' => trans('invalid_otp_code')];

        mysqli_stmt_bind_param($stmt, "ssss", $email, $codeOrToken, $codeOrToken, $userType);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => trans('invalid_otp_code')];
        }

        $codeId = (int)$row['id'];
        $userId = (int)$row['user_id'];

        $tableMap = [
            'student' => 'students',
            'teacher' => 'teachers',
            'parent' => 'parents',
            'admin' => 'admins'
        ];

        $tableName = $tableMap[$userType] ?? 'students';
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $upStmt = mysqli_prepare($con, "UPDATE {$tableName} SET password = ? WHERE id = ?");
        if ($upStmt) {
            mysqli_stmt_bind_param($upStmt, "si", $hashedPassword, $userId);
            mysqli_stmt_execute($upStmt);
        }

        $useStmt = mysqli_prepare($con, "UPDATE password_reset_codes SET is_used = 1 WHERE id = ?");
        if ($useStmt) {
            mysqli_stmt_bind_param($useStmt, "i", $codeId);
            mysqli_stmt_execute($useStmt);
        }

        return [
            'status' => 'success',
            'message' => trans('password_reset_success')
        ];
    }
}
