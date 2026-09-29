<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/email_verification.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../../helpers/admin_notifications.php';
require_once __DIR__ . '/../../../../helpers/app_settings.php';
require_once __DIR__ . '/../../../../helpers/phone.php';

if (!function_exists('studentRegister')) {
    function studentRegister($con) {
        // Checked before anything is read or written, so a closed platform never half-creates an
        // account and never sends a verification email for one that will not exist.
        $blocked = registration_blocked_response($con);
        if ($blocked !== null) return $blocked;

        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $phone = !empty(trim($body['phone'] ?? '')) ? trim($body['phone']) : null;
        $age = (isset($body['age']) && is_numeric($body['age']) && (int)$body['age'] > 0) ? (int)$body['age'] : null;
        $schoolId = (isset($body['school_id']) && is_numeric($body['school_id']) && (int)$body['school_id'] > 0) ? (int)$body['school_id'] : null;
        $cityId = (isset($body['city_id']) && is_numeric($body['city_id']) && (int)$body['city_id'] > 0) ? (int)$body['city_id'] : null;
        $academicYearId = (isset($body['academic_year_id']) && is_numeric($body['academic_year_id']) && (int)$body['academic_year_id'] > 0) ? (int)$body['academic_year_id'] : null;
        $image = !empty(trim($body['image'] ?? '')) ? trim($body['image']) : null;
        $allowedLanguages = ['arabic', 'english', 'french'];
        $studyLanguage = strtolower(trim($body['study_language'] ?? 'arabic'));
        if (!in_array($studyLanguage, $allowedLanguages)) $studyLanguage = 'arabic';

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('name_email_password_required')];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => trans('invalid_email')];
        }

        if (strlen($password) < 6) {
            return ['status' => 'error', 'message' => trans('short_password')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM students WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($res)) {
            return ['status' => 'error', 'message' => trans('email_already_registered')];
        }

        // A phone number is now something you can log in with, so it has to identify one account.
        // Two numbers were already on two accounts each when this check was added; those are left
        // alone and handled at login. This is what stops a third pair ever being created.
        if ($phone !== null && phone_find_students($con, $phone)) {
            return [
                'status' => 'error',
                'message' => trans('phone_already_registered'),
                'error_code' => 'phone_already_registered',
            ];
        }

        $referralCode = trim($body['referral_code'] ?? $body['affiliate_code'] ?? $body['ref'] ?? '');
        $referredByStudentId = null;
        if (!empty($referralCode)) {
            $refCheck = mysqli_prepare($con, "SELECT id FROM students WHERE affiliate_code = ? LIMIT 1");
            if ($refCheck) {
                mysqli_stmt_bind_param($refCheck, "s", $referralCode);
                mysqli_stmt_execute($refCheck);
                $refRes = mysqli_fetch_assoc(mysqli_stmt_get_result($refCheck));
                if ($refRes) {
                    $referredByStudentId = (int)$refRes['id'];
                }
            }
            if (!$referredByStudentId) {
                $affCheck = mysqli_prepare($con, "SELECT student_id FROM affiliates WHERE affiliate_code = ? AND is_active = 1 LIMIT 1");
                if ($affCheck) {
                    mysqli_stmt_bind_param($affCheck, "s", $referralCode);
                    mysqli_stmt_execute($affCheck);
                    $affRes = mysqli_fetch_assoc(mysqli_stmt_get_result($affCheck));
                    if ($affRes && !empty($affRes['student_id'])) {
                        $referredByStudentId = (int)$affRes['student_id'];
                    }
                }
            }
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($con, "INSERT INTO students (name, email, password, image, phone, age, school_id, city_id, academic_year_id, study_language, is_email_verified, referred_by_student_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)");
        mysqli_stmt_bind_param($stmt, "sssssiiiisi", $name, $email, $hashedPassword, $image, $phone, $age, $schoolId, $cityId, $academicYearId, $studyLanguage, $referredByStudentId);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create student account: ' . mysqli_error($con)];
        }

        $studentId = mysqli_insert_id($con);

        // Auto-generate affiliate code for new student
        $affiliateCode = '';
        require_once __DIR__ . '/../affiliate/manage.php';
        if (function_exists('generateStudentAffiliateCode')) {
            $affiliateCode = generateStudentAffiliateCode($con, (int)$studentId);
        }

        // Record student referral tracking
        if ($referredByStudentId > 0) {
            $refIns = mysqli_prepare($con, "INSERT INTO student_referrals (referrer_student_id, referred_student_id, affiliate_code) VALUES (?, ?, ?)");
            if ($refIns) {
                mysqli_stmt_bind_param($refIns, "iis", $referredByStudentId, $studentId, $referralCode);
                mysqli_stmt_execute($refIns);
            }
        }
        
        notify_of_new_student_registration($con, (int)$studentId, $name, $email, $referredByStudentId);

        $otpInfo = generateAndSendOtp($con, 'student', $studentId, $email);

        $payload = [
            'id' => (int)$studentId,
            'name' => $name,
            'email' => $email,
            'role' => 'student'
        ];

        $tokens = generateStudentTokens($payload);

        return [
            'status' => 'success',
            'message' => trans('student_register_success'),
            'data' => [
                'student' => format_response_images([
                    'id' => (int)$studentId,
                    'name' => $name,
                    'email' => $email,
                    'image' => $image,
                    'phone' => $phone,
                    'age' => $age,
                    'study_language' => $studyLanguage,
                    'affiliate_code' => $affiliateCode,
                    'referral_code' => $affiliateCode,
                    'share_link' => 'https://najeh-app.com/register?ref=' . urlencode($affiliateCode),
                    'points_balance' => 0.00,
                    'is_email_verified' => 0,
                    'city' => get_city_details($con, $cityId),
                    'school' => get_school_details($con, $schoolId)
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
