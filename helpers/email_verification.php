<?php
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/mailer.php';

if (!function_exists('generateAndSendOtp')) {
    function generateAndSendOtp($con, string $userType, int $userId, string $email): array {
        $code = sprintf('%06d', mt_rand(100000, 999999));
        $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60));

        $invStmt = mysqli_prepare($con, "UPDATE email_verification_codes SET is_used = 1 WHERE email = ? AND user_type = ? AND is_used = 0");
        if ($invStmt) {
            mysqli_stmt_bind_param($invStmt, "ss", $email, $userType);
            mysqli_stmt_execute($invStmt);
        }

        $stmt = mysqli_prepare($con, "INSERT INTO email_verification_codes (user_type, user_id, email, code, expires_at) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sisss", $userType, $userId, $email, $code, $expiresAt);
            mysqli_stmt_execute($stmt);
        }

        $subject = "رمز التحقق الخاص بك - منصة ناجح التعليمية";
        $htmlBody = "
            <div dir='rtl' style='font-family: Arial, sans-serif; padding: 20px; color: #333; background-color: #f9f9f9; border-radius: 8px;'>
                <h2 style='color: #4CAF50;'>مرحباً بك في منصة ناجح التعليمية 🎓</h2>
                <p>رمز التحقق الخاص بك لتأكيد البريد الإلكتروني هو:</p>
                <div style='background: #e8f5e9; padding: 15px; text-align: center; border-radius: 6px; font-size: 24px; font-weight: bold; letter-spacing: 4px; color: #2E7D32;'>
                    {$code}
                </div>
                <p style='color: #777; margin-top: 15px;'>هذا الرمز صالِح لمدة 15 دقيقة فقط.</p>
            </div>
        ";

        $mailResult = sendMailMessage($email, $subject, $htmlBody);

        return [
            'code' => $code,
            'expires_at' => $expiresAt,
            'mail_result' => $mailResult
        ];
    }
}

if (!function_exists('verifyEmailWithOtp')) {
    function verifyEmailWithOtp($con, string $userType, string $email, string $code): bool {
        $email = strtolower(trim($email));
        $code = trim($code);

        $stmt = mysqli_prepare($con, "SELECT id, user_id FROM email_verification_codes WHERE email = ? AND code = ? AND user_type = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "sss", $email, $code, $userType);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return false;
        }

        $codeId = (int)$row['id'];
        $userId = (int)$row['user_id'];

        $useStmt = mysqli_prepare($con, "UPDATE email_verification_codes SET is_used = 1 WHERE id = ?");
        if ($useStmt) {
            mysqli_stmt_bind_param($useStmt, "i", $codeId);
            mysqli_stmt_execute($useStmt);
        }

        $tableMap = [
            'student' => 'students',
            'teacher' => 'teachers',
            'parent' => 'parents',
            'admin' => 'admins'
        ];

        $tableName = $tableMap[$userType] ?? 'students';
        $updateStmt = mysqli_prepare($con, "UPDATE {$tableName} SET is_email_verified = 1 WHERE id = ? OR email = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "is", $userId, $email);
            mysqli_stmt_execute($updateStmt);
        }

        return true;
    }
}
