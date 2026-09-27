<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "./config/serverConfig.php";
require_once "./helpers/functions.php";
require_once "./helpers/language.php";
require_once "./helpers/JWT.php";
require_once "./helpers/entities.php";
require_once "./helpers/email_verification.php";
require_once "./helpers/password_reset.php";
require_once "./helpers/rbac.php";
require_once "./config/connection.php";

if (!function_exists('handleRoute')) {
    function handleRoute($route, $currentMethod, $currentPath, $con)
    {
        $allowedMethods = is_array($route["method"] ?? null) ? $route["method"] : [$route["method"] ?? ""];
        if (!in_array($currentMethod, $allowedMethods, true)) {
            return;
        }

        if (isset($route["path"]) && $route["path"] === $currentPath) {
            $result = $route["handler"]($con);
            json_response($result);
            exit();
        }

        if (!empty($route["regex"])) {
            if (preg_match($route["regex"], $currentPath, $matches)) {
                $result = $route["handler"]($con, $matches);
                json_response($result);
                exit();
            }
        }
    }
}

if (!function_exists('handleTokenRefresh')) {
    function handleTokenRefresh(string $defaultRole = 'student') {
        $body = read_json_body();
        $refreshToken = trim($body['refresh_token'] ?? '');
        $role = strtolower(trim($body['role'] ?? $defaultRole));

        if (empty($refreshToken)) {
            json_response(['status' => 'error', 'message' => trans('refresh_token_required')], 400);
        }

        $decoded = verifyRefreshToken($refreshToken, $role);
        if (!$decoded) {
            json_response(['status' => 'error', 'message' => trans('invalid_refresh_token')], 401);
        }

        $tablesByRole = [
            'teacher' => 'teachers',
            'student' => 'students',
            'parent' => 'parents',
            'admin' => 'admins',
        ];
        $table = $tablesByRole[$role] ?? null;
        if ($table && !empty($decoded['id'])) {
            global $con;
            $stmt = mysqli_prepare($con, "SELECT is_active FROM {$table} WHERE id = ? LIMIT 1");
            $entityId = (int)$decoded['id'];
            mysqli_stmt_bind_param($stmt, "i", $entityId);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$row || (int)$row['is_active'] === 0) {
                json_response(['status' => 'error', 'message' => trans('account_inactive')], 403);
            }

            // Gated here too, or a revoked device could mint itself fresh access tokens forever
            // and an admin reset would never actually end that session.
            if ($role === 'student') {
                require_once __DIR__ . '/helpers/devices.php';

                if (!empty($decoded['did']) && !device_is_authorized($con, 'student', $entityId, (string)$decoded['did'])) {
                    json_response([
                        'status' => 'error',
                        'message' => 'تم إلغاء تفعيل هذا الجهاز. يرجى تسجيل الدخول مرة أخرى أو التواصل مع الدعم الفني.',
                        'error_code' => 'device_revoked'
                    ], 403);
                }

                // A refresh token outlives an access token by a month, so this is the one that
                // matters most: without it, "sign out everywhere" would last an hour.
                if (session_is_revoked($con, $entityId, $decoded['iat'] ?? 0)) {
                    json_response([
                        'status' => 'error',
                        'message' => 'تم تسجيل خروجك من كل الأجهزة. يرجى تسجيل الدخول مرة أخرى.',
                        'error_code' => 'session_revoked'
                    ], 403);
                }
            }
        }

        unset($decoded['token_type'], $decoded['iat'], $decoded['exp'], $decoded['role']);

        $newTokens = generateTokens($decoded, $role);
        return [
            'status' => 'success',
            'message' => trans('tokens_refreshed_success'),
            'data' => $newTokens
        ];
    }
}

if (!function_exists('handleVerifyEmailOtp')) {
    function handleVerifyEmailOtp($con, string $defaultRole = 'student') {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $code = trim($body['code'] ?? $body['otp'] ?? $body['verification_code'] ?? '');
        $role = strtolower(trim($body['role'] ?? $body['user_type'] ?? $defaultRole));

        if (empty($email) || empty($code)) {
            json_response(['status' => 'error', 'message' => 'Email and verification OTP code (code or otp) are required'], 400);
        }

        $verified = verifyEmailWithOtp($con, $role, $email, $code);
        if (!$verified) {
            json_response(['status' => 'error', 'message' => trans('invalid_otp_code')], 400);
        }

        $tableMap = ['student' => 'students', 'teacher' => 'teachers', 'parent' => 'parents', 'admin' => 'admins'];
        $tableName = $tableMap[$role] ?? 'students';
        $stmt = mysqli_prepare($con, "SELECT id, name, email FROM {$tableName} WHERE email = ? LIMIT 1");
        $tokens = null;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $uRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($uRow) {
                $payload = [
                    'id' => (int)$uRow['id'],
                    'name' => $uRow['name'],
                    'email' => $uRow['email'],
                    'role' => $role
                ];
                $tokens = generateStudentTokens($payload);
            }
        }

        return [
            'status' => 'success',
            'message' => trans('email_verified_success'),
            'data' => [
                'is_email_verified' => true,
                'tokens' => $tokens
            ]
        ];
    }
}

if (!function_exists('handleResendOtp')) {
    function handleResendOtp($con, string $defaultRole = 'student') {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $role = strtolower(trim($body['role'] ?? $body['user_type'] ?? $defaultRole));

        if (empty($email)) {
            json_response(['status' => 'error', 'message' => trans('invalid_email')], 400);
        }

        $tableMap = ['student' => 'students', 'teacher' => 'teachers', 'parent' => 'parents', 'admin' => 'admins'];
        $tableName = $tableMap[$role] ?? 'students';

        $stmt = mysqli_prepare($con, "SELECT id FROM {$tableName} WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            json_response(['status' => 'error', 'message' => trans('profile_not_found')], 404);
        }

        $userId = (int)$row['id'];
        $otpInfo = generateAndSendOtp($con, $role, $userId, $email);

        return [
            'status' => 'success',
            'message' => trans('otp_sent_success'),
            'data' => [
                'otp_code' => $otpInfo['code'],
                'expires_at' => $otpInfo['expires_at']
            ]
        ];
    }
}

if (!function_exists('handleForgotPassword')) {
    function handleForgotPassword($con, string $defaultRole = 'student') {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $role = strtolower(trim($body['role'] ?? $body['user_type'] ?? $defaultRole));

        return sendPasswordResetOtp($con, $role, $email);
    }
}

if (!function_exists('handleVerifyResetCode')) {
    function handleVerifyResetCode($con, string $defaultRole = 'student') {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $code = trim($body['code'] ?? '');
        $role = strtolower(trim($body['role'] ?? $body['user_type'] ?? $defaultRole));

        return verifyPasswordResetOtp($con, $role, $email, $code);
    }
}

if (!function_exists('handleResetPassword')) {
    function handleResetPassword($con, string $defaultRole = 'student') {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $codeOrToken = trim($body['code'] ?? $body['reset_token'] ?? '');
        $newPassword = trim($body['new_password'] ?? $body['password'] ?? '');
        $role = strtolower(trim($body['role'] ?? $body['user_type'] ?? $defaultRole));

        return resetPasswordWithCodeOrToken($con, $role, $email, $codeOrToken, $newPassword);
    }
}

$method = strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");
if ($method === 'POST') {
    $override = $_POST['_method'] ?? $_GET['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
    if ($override) {
        $method = strtoupper($override);
    }
}
$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
if ($path !== "/" && substr($path, -1) === "/") {
    $path = rtrim($path, "/");
}

// Maintenance mode, checked once for the whole API rather than per handler - a switch that only
// covered the endpoints someone remembered to annotate would not be a maintenance mode.
//
// Admin routes are exempt because the switch is turned back OFF through one of them; without that
// exemption this would be a door that locks from the inside. OPTIONS is exempt so the browser's
// CORS preflight still succeeds and the client sees the real 503 instead of a CORS error.
require_once "./helpers/app_settings.php";
if ($method !== 'OPTIONS' && maintenance_blocks_request($con, $path)) {
    json_response([
        "status" => "error",
        "error_code" => "maintenance_mode",
        "message" => app_setting_message($con, 'maintenance_message', 'المنصة تحت الصيانة حاليًا، برجاء المحاولة بعد قليل.'),
    ], 503);
}

$routes = [
    [
        "method" => "GET",
        "path" => "/najeh",
        "handler" => function ($con) {
            return [
                "status" => "ok",
                "message" => "Welcome to Najeh Platform API",
                "language" => get_request_lang($con)
            ];
        },
    ],

    // Global Verification, Token & Password Reset Endpoints ---
    [
        "method" => "POST",
        "path" => "/najeh/auth/refresh",
        "handler" => function () {
            return handleTokenRefresh('student');
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/auth/verify-email",
        "handler" => function ($con) {
            return handleVerifyEmailOtp($con, 'student');
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/auth/resend-otp",
        "handler" => function ($con) {
            return handleResendOtp($con, 'student');
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/auth/forgot-password",
        "handler" => function ($con) {
            return handleForgotPassword($con, 'student');
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/auth/verify-reset-code",
        "handler" => function ($con) {
            return handleVerifyResetCode($con, 'student');
        },
    ],
           [
            "method" => "GET",
            "path" => "/najeh/student/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentListNotifications($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentListNotifications($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/notifications/unread-count",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetNotificationUnreadCount($con, $studentData);
            },
        ],
         [
            "method" => "GET",
            "path" => "/najeh/teacher/home",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/content/home.php";
                $teacherData = teacherAuthMiddleware();
                return teacherHome($con, $teacherData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/teacher/content/home",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/content/home.php";
                $teacherData = teacherAuthMiddleware();
                return teacherHome($con, $teacherData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/teacher/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/notifications/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherListNotifications($con, $teacherData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/teacher/notifications/unread-count",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/notifications/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherGetNotificationUnreadCount($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/notifications/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherMarkNotificationRead($con, $teacherData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/teacher/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/notifications/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherMarkNotificationRead($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/notifications/mark-read",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/notifications/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherMarkNotificationRead($con, $teacherData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/teacher/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/notifications/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherDeleteNotification($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/notifications/delete",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/notifications/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherDeleteNotification($con, $teacherData);
            },
        ],
    

 
        // Student Auth
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/login",
            "handler" => function ($con) {
                include_once "./app/user/student/auth/login.php";
                return studentLogin($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/register",
            "handler" => function ($con) {
                include_once "./app/user/student/auth/register.php";
                return studentRegister($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/verify-email",
            "handler" => function ($con) {
                return handleVerifyEmailOtp($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/resend-otp",
            "handler" => function ($con) {
                return handleResendOtp($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/forgot-password",
            "handler" => function ($con) {
                return handleForgotPassword($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/verify-reset-code",
            "handler" => function ($con) {
                return handleVerifyResetCode($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/reset-password",
            "handler" => function ($con) {
                return handleResetPassword($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/refresh",
            "handler" => function () {
                return handleTokenRefresh('student');
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/profile.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentProfile($con, $studentData);
            },
        ],
        [
            "method" => ["PUT", "POST", "PATCH"],
            "path" => "/najeh/user/student/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/profile.php";
                $studentData = studentAuthMiddleware();
                return studentUpdateProfile($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/fcm-token",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/profile.php";
                $studentData = studentAuthMiddleware();
                return studentUpdateFcmToken($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentListNotifications($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentListNotifications($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/notifications/unread-count",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetNotificationUnreadCount($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/student/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/notifications/mark-read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/student/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentDeleteNotification($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/notifications/delete",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentDeleteNotification($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/student/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/notifications/mark-read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/student/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentDeleteNotification($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/notifications/delete",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentDeleteNotification($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/notifications/unread-count",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetNotificationUnreadCount($con, $studentData);
            },
        ],
        [
            "method" => ["POST", "PUT"],
            "path" => "/najeh/user/student/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/notifications/mark-read",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMarkNotificationRead($con, $studentData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/user/student/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentDeleteNotification($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/notifications/delete",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/notifications/manage.php";
                $studentData = studentAuthMiddleware();
                return studentDeleteNotification($con, $studentData);
            },
        ],
    [
        "method" => "POST",
        "path" => "/najeh/auth/reset-password",
        "handler" => function ($con) {
            return handleResetPassword($con, 'student');
        },
    ],

    // Public Essential Lookup Routes ----------------------
    [
        "method" => "GET",
        "path" => "/najeh/cities",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/cities.php";
            return studentListCities($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/school-types",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/school_types.php";
            return studentListSchoolTypes($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/schools",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/schools.php";
            return studentListSchools($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/subjects",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/subjects.php";
            return studentListSubjects($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/languages",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/languages.php";
            return studentListLanguages($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/academic-years",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/academic_years.php";
            return studentListAcademicYears($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/course-categories",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/course_categories.php";
            return studentListCourseCategories($con);
        },
    ],

    // Banners Public Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/banners",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/banners.php";
            return studentListBanners($con);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/banners/view",
        "handler" => function ($con) {
            include_once "./app/user/student/lookups/banners.php";
            return studentTrackBannerView($con);
        },
    ],

    // Courses Public Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/courses",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/courses.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetAllCoursesWithOwn($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/courses/show",
        "handler" => function ($con) {
            include_once "./app/user/student/content/courses.php";
            return studentGetCourseDetails($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/courses/units",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/units.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetCourseUnitsOnly($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/units",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/units.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetCourseUnitsOnly($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/units/videos",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/units.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetUnitVideosOnly($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/unit/videos",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/units.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetUnitVideosOnly($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/units/questions",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/units.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetUnitQuestionsOnly($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/unit/questions",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/units.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetUnitQuestionsOnly($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/questions/answer",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/questions.php";
            $studentData = studentAuthMiddleware();
            return studentSubmitQuestionAnswer($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/questions/submit-answer",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/questions.php";
            $studentData = studentAuthMiddleware();
            return studentSubmitQuestionAnswer($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/videos/show",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/videos.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetVideoDetails($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/videos/details",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/videos.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetVideoDetails($con, $studentData);
        },
    ],

    // Content Public Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/units",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminListUnits($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/units/show",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminGetUnit($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/videos",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminListVideos($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/videos/show",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminGetVideo($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/books",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/user/student/content/books.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetAllBooksWithOwn($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/books/show",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminGetBook($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/exams",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminListExams($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/exams/show",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminGetExam($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/questions",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminListQuestions($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/questions/show",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminGetQuestion($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/certificates",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminListCertificates($con);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/certificates/show",
        "handler" => function ($con) {
            include_once "./app/admin/courses/content.php";
            return adminGetCertificate($con);
        },
    ],

    // Teacher Content & Courses Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/units",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
            if ($tId > 0 && !isset($_GET['teacher_id'])) {
                $_GET['teacher_id'] = $tId;
            }
            return adminListUnits($con);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/units",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminCreateUnit($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/units",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminUpdateUnit($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/units",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminDeleteUnit($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/units/reorder",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminReorderUnits($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/videos",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
            if ($tId > 0 && !isset($_GET['teacher_id'])) {
                $_GET['teacher_id'] = $tId;
            }
            return adminListVideos($con);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/videos",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminCreateVideo($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/videos",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminUpdateVideo($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/videos",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminDeleteVideo($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/books",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
            if ($tId > 0 && !isset($_GET['teacher_id'])) {
                $_GET['teacher_id'] = $tId;
            }
            return adminListBooks($con);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/books",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminCreateBook($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/books",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminUpdateBook($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/books",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminDeleteBook($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/books/grant-access",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            $bookManagePath = __DIR__ . "/app/teacher/books/manage.php";
            if (!file_exists($bookManagePath)) {
                return ['status' => 'error', 'message' => 'DIAGNOSTIC: file does not exist on server at ' . $bookManagePath];
            }
            include_once $bookManagePath;
            if (!function_exists('teacherGrantBookAccessToStudent')) {
                return ['status' => 'error', 'message' => 'DIAGNOSTIC: file exists (size ' . filesize($bookManagePath) . ' bytes) but function teacherGrantBookAccessToStudent is not defined after include'];
            }
            $teacherData = teacherAuthMiddleware();
            return teacherGrantBookAccessToStudent($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/books/purchase-requests",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            $bookManagePath = __DIR__ . "/app/teacher/books/manage.php";
            if (!file_exists($bookManagePath)) {
                return ['status' => 'error', 'message' => 'DIAGNOSTIC: file does not exist on server at ' . $bookManagePath];
            }
            include_once $bookManagePath;
            if (!function_exists('teacherListBookPurchaseRequests')) {
                return ['status' => 'error', 'message' => 'DIAGNOSTIC: file exists (size ' . filesize($bookManagePath) . ' bytes) but function teacherListBookPurchaseRequests is not defined after include'];
            }
            $teacherData = teacherAuthMiddleware();
            return teacherListBookPurchaseRequests($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/books/purchase-requests/review",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            $bookManagePath = __DIR__ . "/app/teacher/books/manage.php";
            if (!file_exists($bookManagePath)) {
                return ['status' => 'error', 'message' => 'DIAGNOSTIC: file does not exist on server at ' . $bookManagePath];
            }
            include_once $bookManagePath;
            if (!function_exists('teacherReviewBookRequest')) {
                return ['status' => 'error', 'message' => 'DIAGNOSTIC: file exists (size ' . filesize($bookManagePath) . ' bytes) but function teacherReviewBookRequest is not defined after include - check for a PHP parse error in that file (see server error log)'];
            }
            $teacherData = teacherAuthMiddleware();
            return teacherReviewBookRequest($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/exams",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
            if ($tId > 0 && !isset($_GET['teacher_id'])) {
                $_GET['teacher_id'] = $tId;
            }
            return adminListExams($con);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/exams",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminCreateExam($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/exams",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminUpdateExam($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/exams",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminDeleteExam($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/questions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
            if ($tId > 0 && !isset($_GET['teacher_id'])) {
                $_GET['teacher_id'] = $tId;
            }
            return adminListQuestions($con);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/questions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminCreateQuestion($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/questions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminUpdateQuestion($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/questions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminDeleteQuestion($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/questions/reorder",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminReorderQuestions($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/certificates",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
            if ($tId > 0 && !isset($_GET['teacher_id'])) {
                $_GET['teacher_id'] = $tId;
            }
            return adminListCertificates($con);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/certificates",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminCreateCertificate($con, $teacherData);
        },
    ],
        [
        "method" => "POST",
        "path" => "/najeh/teacher/student-certificates",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminAddStudentCertificate($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/student-certificates",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/content/certificates/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListStudentCertificates($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/student-certificates",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/content/certificates/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherRevokeStudentCertificate($con, $teacherData);
        },
    ],
        [
        "method" => "POST",
        "path" => "/najeh/teacher/course-student",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return get_course_students($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/certificates",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminUpdateCertificate($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/certificates",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/content/manage.php";
            $teacherData = teacherAuthMiddleware();
            return adminDeleteCertificate($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/subscribed-students",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/subscriptions/students.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGetSubscribedStudents($con, $teacherData);
        },
    ],

    // Teacher Homework Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/homework",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/homework/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListHomework($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/homework",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/homework/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCreateHomework($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/homework",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/homework/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherUpdateHomework($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/homework",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/homework/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherDeleteHomework($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/homework/submissions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/homework/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListHomeworkSubmissions($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/homework/feedback",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/homework/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGradeSubmission($con, $teacherData);
        },
    ],

    // Student Homework Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/student/homework",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/homework/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentListHomework($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/homework/submit",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/homework/manage.php";
            $studentData = studentAuthMiddleware();
            return studentSubmitHomework($con, $studentData);
        },
    ],

    // Teacher Student Reports Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/reports",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/reports/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListStudentReports($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/reports/show",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/reports/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGetStudentReport($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/reports",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/reports/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCreateStudentReport($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/reports",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/reports/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherUpdateStudentReport($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/reports",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/reports/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherDeleteStudentReport($con, $teacherData);
        },
    ],

    // Teacher Consultation Slots Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/consultations",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListConsultationSlots($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/consultations/bookings",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListBookedConsultations($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/consultations/respond",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherRespondToConsultationBooking($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/consultations/respond",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherRespondToConsultationBooking($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/consultations",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCreateConsultationSlot($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/consultations/recurring",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCreateRecurringConsultationSlots($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/consultations",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherDeleteConsultationSlot($con, $teacherData);
        },
    ],

    // Student Consultation Slots Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/student/consultations",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentListAvailableConsultationTeachers($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/consultations/slots",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentListAvailableConsultationSlots($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/consultations/my-bookings",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentListMyBookedConsultations($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/consultations/book",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $studentData = studentAuthMiddleware();
            return studentBookConsultationSlot($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/consultations/cancel",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $studentData = studentAuthMiddleware();
            return studentCancelConsultationBooking($con, $studentData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/student/consultations/cancel",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $studentData = studentAuthMiddleware();
            return studentCancelConsultationBooking($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/consultations/cancel",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCancelConsultationBooking($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/consultations/cancel",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/consultations/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCancelConsultationBooking($con, $teacherData);
        },
    ],

    // Teacher Class Sessions Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/sessions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListSessions($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/sessions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCreateSession($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/sessions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherUpdateSession($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/sessions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherDeleteSession($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/sessions/start",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherStartSession($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/sessions/end",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherEndSession($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/sessions/reschedule-request",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherRequestReschedule($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/sessions/reschedule-requests",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListRescheduleRequests($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/sessions/reschedule-requests/incoming",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListIncomingRescheduleRequests($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/sessions/reschedule-requests/review",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherReviewRescheduleRequest($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/sessions/attendance/roster",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/attendance.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGetSessionAttendanceRoster($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/sessions/attendance/mark",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/attendance.php";
            $teacherData = teacherAuthMiddleware();
            return teacherMarkSessionAttendance($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/sessions/attendance/stats",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/attendance.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGetSessionAttendanceStats($con, $teacherData);
        },
    ],

    // Teacher Attendance Exceptions & Wallet Routes (Section 10) --------------------------------
    [
        "method" => "POST",
        "path" => "/najeh/teacher/attendance/exceptions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/attendance/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherRequestAttendanceException($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/attendance/exceptions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/attendance/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListMyAttendanceExceptionRequests($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/wallet",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/attendance/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGetWallet($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/wallet/transactions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/attendance/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListWalletTransactions($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/wallet/appeals",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/attendance/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCreateDeductionAppeal($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/wallet/appeals",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/attendance/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListMyAppeals($con, $teacherData);
        },
    ],

    // Teacher Session Files Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/sessions/files",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/files.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListSessionFiles($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/sessions/files",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/files.php";
            $teacherData = teacherAuthMiddleware();
            return teacherAddSessionFile($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/sessions/files",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/files.php";
            $teacherData = teacherAuthMiddleware();
            return teacherUpdateSessionFile($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/sessions/files",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/files.php";
            $teacherData = teacherAuthMiddleware();
            return teacherDeleteSessionFile($con, $teacherData);
        },
    ],

    // Student Sessions Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/student/sessions/today",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/sessions/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentTodaySessions($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/sessions/reschedule-request",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $studentData = studentAuthMiddleware();
            return studentRequestReschedule($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/sessions/reschedule-requests",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/teacher/sessions/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentListMyRescheduleRequests($con, $studentData);
        },
    ],

    // Student Attendance Routes (Section 10) --------------------------------
    [
        "method" => "POST",
        "path" => "/najeh/student/attendance/check-in",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/attendance/manage.php";
            $studentData = studentAuthMiddleware();
            return studentCheckInSession($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/attendance/exceptions",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/attendance/manage.php";
            $studentData = studentAuthMiddleware();
            return studentRequestAttendanceException($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/attendance/exceptions",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/attendance/manage.php";
            $studentData = studentAuthMiddleware();
            return studentListMyAttendanceExceptionRequests($con, $studentData);
        },
    ],

    // Student Session Files & Submissions Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/student/sessions/show",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/sessions/files.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetSessionDetails($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/sessions/files",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/sessions/files.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetSessionFiles($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/sessions/files/submit",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/sessions/files.php";
            $studentData = studentAuthMiddleware();
            return studentSubmitSessionFile($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/sessions/submissions",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/sessions/files.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetMySubmissions($con, $studentData);
        },
    ],

    // Teacher Session Submissions & Grading Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/sessions/files/submissions",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/files.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListSubmissions($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/sessions/files/grade",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/sessions/files.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGradeSubmission($con, $teacherData);
        },
    ],

    // Student Exams Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/student/exams",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/exams/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentListAvailableExams($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/exams/start",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/exams/manage.php";
            $studentData = studentAuthMiddleware();
            return studentStartExamAttempt($con, $studentData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/student/exams/submit",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/exams/manage.php";
            $studentData = studentAuthMiddleware();
            return studentSubmitExamAttempt($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/exams/results",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/exams/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentListExamResults($con, $studentData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/student/exams/results/show",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/student/exams/manage.php";
            $studentData = studentAuthMiddlewareOptional();
            return studentGetExamResultDetail($con, $studentData);
        },
    ],

    // Teacher Exam Attempts (Grading) Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/exams/attempts",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/exams/attempts.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListExamAttempts($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/exams/attempts/show",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/exams/attempts.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGetExamAttemptDetail($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/exams/attempts",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/exams/attempts.php";
            $teacherData = teacherAuthMiddleware();
            return teacherGradeExamAttempt($con, $teacherData);
        }, 
    ],

    // Teacher Courses Routes --------------------------------
    [
        "method" => "GET",
        "path" => "/najeh/teacher/courses",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/courses/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListMyCourses($con, $teacherData);
        },
    ],
    [
        "method" => "POST",
        "path" => "/najeh/teacher/courses",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/courses/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherCreateCourse($con, $teacherData);
        },
    ],
    [
        "method" => "PUT",
        "path" => "/najeh/teacher/courses",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/courses/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherUpdateMyCourse($con, $teacherData);
        },
    ],
    [
        "method" => "DELETE",
        "path" => "/najeh/teacher/courses",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/courses/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherDeleteMyCourse($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/subjects",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/lookups/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListMySubjects($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/academic-years",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/lookups/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListMyAcademicYears($con, $teacherData);
        },
    ],
    [
        "method" => "GET",
        "path" => "/najeh/teacher/academic_years",
        "handler" => function ($con) {
            include_once "./middlewares/teacherauthmiddleware.php";
            include_once "./app/teacher/lookups/manage.php";
            $teacherData = teacherAuthMiddleware();
            return teacherListMyAcademicYears($con, $teacherData);
        },
    ],

    // Admin Routes ----------------------------------------
    
        [
            "method" => "POST",
            "path" => "/najeh/admin/auth/login",
            "handler" => function ($con) {
                include_once "./app/admin/auth/login.php";
                return adminLogin($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/auth/refresh",
            "handler" => function () {
                return handleTokenRefresh('admin');
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/auth/profile.php";
                $adminData = adminAuthMiddleware();
                return adminProfile($con, $adminData);
            },
        ],
        [
            "method" => ["PUT", "POST", "PATCH"],
            "path" => "/najeh/admin/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/auth/profile.php";
                $adminData = adminAuthMiddleware();
                return adminUpdateProfile($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/fcm-token",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/auth/profile.php";
                $adminData = adminAuthMiddleware();
                return adminUpdateFcmToken($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/onboard/start",
            "handler" => function ($con) {
                include_once "./app/user/student/onboard/manage.php";
                return studentOnboardStartWithCode($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/onboard/complete",
            "handler" => function ($con) {
                include_once "./app/user/student/onboard/manage.php";
                return studentOnboardComplete($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/parent/onboard/start",
            "handler" => function ($con) {
                include_once "./app/user/parent/onboard/manage.php";
                return parentOnboardStartWithCode($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/parent/onboard/complete",
            "handler" => function ($con) {
                include_once "./app/user/parent/onboard/manage.php";
                return parentOnboardComplete($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/notifications/send",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/notifications/manage.php";
                $adminData = adminAuthMiddleware();
                return adminSendNotification($con, $adminData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/notifications/manage.php";
                adminAuthMiddleware();
                return adminListNotifications($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/notifications/inbox",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/notifications/inbox.php";
                $adminData = adminAuthMiddleware();
                return adminListMyNotifications($con, $adminData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/notifications/inbox/unread-count",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/notifications/inbox.php";
                $adminData = adminAuthMiddleware();
                return adminGetNotificationUnreadCount($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/notifications/inbox/read",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/notifications/inbox.php";
                $adminData = adminAuthMiddleware();
                return adminMarkNotificationRead($con, $adminData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/notifications/inbox",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/notifications/inbox.php";
                $adminData = adminAuthMiddleware();
                return adminDeleteMyNotification($con, $adminData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/dashboard/statistics",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/dashboard/statistics.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'view_dashboard');
                return adminDashboardStatistics($con);
            },
        ],

        // 1. Admin Users Management (manage_admins)
        [
            "method" => "GET",
            "path" => "/najeh/admin/admins",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/admins/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_admins');
                return adminListAdmins($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/admins/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/admins/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_admins');
                return adminGetAdmin($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/admins",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/admins/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_admins');
                return adminCreateAdminUser($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/admins",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/admins/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_admins');
                return adminUpdateAdminUser($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/admins",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/admins/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_admins');
                return adminDeleteAdminUser($con, $adminData);
            },
        ],

        // Admin Student Reports Routes --------------------------------
        [
            "method" => "GET",
            "path" => "/najeh/admin/reports",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/reports/manage.php";
                $adminData = adminAuthMiddleware();
                return adminListStudentReports($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/reports/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/reports/manage.php";
                $adminData = adminAuthMiddleware();
                return adminGetStudentReport($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/reports",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/reports/manage.php";
                $adminData = adminAuthMiddleware();
                return adminCreateStudentReport($con, $adminData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/reports",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/reports/manage.php";
                $adminData = adminAuthMiddleware();
                return adminUpdateStudentReport($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/reports",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/reports/manage.php";
                $adminData = adminAuthMiddleware();
                return adminDeleteStudentReport($con);
            },
        ],

        // 2. Roles & Permissions Management (manage_roles)
        [
            "method" => "GET",
            "path" => "/najeh/admin/roles",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/roles/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_roles');
                return adminListRoles($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/roles/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/roles/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_roles');
                return adminGetRole($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/roles",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/roles/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_roles');
                return adminCreateRole($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/roles",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/roles/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_roles');
                return adminUpdateRole($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/roles",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/roles/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_roles');
                return adminDeleteRole($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/permissions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/roles/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_roles');
                return adminListPermissions($con);
            },
        ],

        // 3. Students Management (manage_students)
        [
            "method" => "GET",
            "path" => "/najeh/admin/students",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/students.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminListStudents($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/students/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/students.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminGetStudent($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/students",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/students.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminCreateStudent($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/students",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/students.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminUpdateStudent($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/students",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/students.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminDeleteStudent($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/students/export",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/export/export.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                adminExportStudents($con);
            },
        ],

        // Student Devices — how many devices an account may sign in from (manage_students)
        [
            "method" => "GET",
            "path" => "/najeh/admin/students/devices",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/devices/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminListStudentDevices($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/students/devices/reset",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/devices/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminResetStudentDevices($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/students/logout-all",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/devices/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminLogoutStudentEverywhere($con, $adminData);
            },
        ],
        [
            "method" => ["DELETE", "POST"],
            "path" => "/najeh/admin/students/devices/revoke",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/devices/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminRevokeStudentDevice($con, $adminData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/students/device-limit",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/devices/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_students');
                return adminSetStudentDeviceLimit($con);
            },
        ],

        // 4. Teachers Management (manage_teachers)
        [
            "method" => "GET",
            "path" => "/najeh/admin/teachers",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/teachers.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListTeachers($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/teachers/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/teachers.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetTeacher($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/teachers",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/teachers.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateTeacher($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/teachers",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/teachers.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateTeacher($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/teachers",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/teachers.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteTeacher($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/teachers/export",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/export/export.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                adminExportTeachers($con);
            },
        ],

        // 5. Parents & Linkage Management (manage_parents)
        [
            "method" => "GET",
            "path" => "/najeh/admin/parents",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/parents.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                return adminListParents($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/parents/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/parents.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                return adminGetParent($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/parents",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/parents.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                return adminCreateParent($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/parents",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/parents.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                return adminUpdateParent($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/parents",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/parents.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                return adminDeleteParent($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/parents/export",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/export/export.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                adminExportParents($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/parents/link-student",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/parents.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                return adminLinkStudentToParent($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/parents/unlink-student",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/users/parents.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_parents');
                return adminUnlinkStudentFromParent($con);
            },
        ],

        // Banners CRUD & View Analytics (Protected by RBAC)
        [
            "method" => "GET",
            "path" => "/najeh/admin/banners",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/banners/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_banners');
                return adminListBanners($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/banners/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/banners/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_banners');
                return adminGetBanner($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/banners",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/banners/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_banners');
                return adminCreateBanner($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/banners",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/banners/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_banners');
                return adminUpdateBanner($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/banners",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/banners/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_banners');
                return adminDeleteBanner($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/banners/views",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/banners/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_banners');
                return adminGetBannerViews($con);
            },
        ],

        // 6. Lookups CRUD (Protected by RBAC)
        // Languages CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/languages",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/languages/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_languages');
                return adminListLanguages($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/languages/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/languages/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_languages');
                return adminGetLanguage($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/languages",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/languages/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_languages');
                return adminCreateLanguage($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/languages",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/languages/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_languages');
                return adminUpdateLanguage($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/languages",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/languages/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_languages');
                return adminDeleteLanguage($con);
            },
        ],

        // Cities CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/cities",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/cities/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_cities');
                return adminListCities($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/cities/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/cities/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_cities');
                return adminGetCity($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/cities",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/cities/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_cities');
                return adminCreateCity($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/cities",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/cities/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_cities');
                return adminUpdateCity($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/cities",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/cities/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_cities');
                return adminDeleteCity($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/cities/export",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/export/export.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_cities');
                adminExportCities($con);
            },
        ],

        // School Types CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/school-types",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/school_types/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_school_types');
                return adminListSchoolTypes($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/school-types/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/school_types/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_school_types');
                return adminGetSchoolType($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/school-types",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/school_types/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_school_types');
                return adminCreateSchoolType($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/school-types",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/school_types/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_school_types');
                return adminUpdateSchoolType($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/school-types",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/school_types/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_school_types');
                return adminDeleteSchoolType($con);
            },
        ],

        // Schools CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/schools",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/schools/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_schools');
                return adminListSchools($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/schools/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/schools/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_schools');
                return adminGetSchool($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/schools",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/schools/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_schools');
                return adminCreateSchool($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/schools",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/schools/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_schools');
                return adminUpdateSchool($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/schools",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/schools/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_schools');
                return adminDeleteSchool($con);
            },
        ],

        // Subjects CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/subjects",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subjects/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                return adminListSubjects($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/subjects/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subjects/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                return adminGetSubject($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/subjects",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subjects/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                return adminCreateSubject($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/subjects",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subjects/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                return adminUpdateSubject($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/subjects",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subjects/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                return adminDeleteSubject($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/subjects/export",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/export/export.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                adminExportSubjects($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/subjects/link-academic-year",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subjects/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                return adminLinkSubjectToAcademicYear($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/subjects/unlink-academic-year",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subjects/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_subjects');
                return adminUnlinkSubjectFromAcademicYear($con);
            },
        ],

        // Courses CRUD (Protected by RBAC)
        [
            "method" => "GET",
            "path" => "/najeh/admin/courses",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListCourses($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/courses/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetCourse($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/courses",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateCourse($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/courses",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateCourse($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/courses",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteCourse($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/courses/link-teacher",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminLinkTeacherToCourse($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/courses/unlink-teacher",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUnlinkTeacherFromCourse($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/courses/seed",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminSeedCourses($con);
            },
        ],

        // Units CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/units",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListUnits($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/units/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetUnit($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/units",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateUnit($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/units",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateUnit($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/units",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteUnit($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/units/reorder",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminReorderUnits($con);
            },
        ],

        // Videos CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/videos",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListVideos($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/videos/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetVideo($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/videos",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateVideo($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/videos",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateVideo($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/videos",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteVideo($con);
            },
        ],

        // Books CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/books",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListBooks($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/books/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetBook($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/books",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateBook($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/books",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateBook($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/books",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteBook($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/books/grant-access",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGrantBookAccessToStudent($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/books/purchase-requests",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListBookPurchaseRequests($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/books/purchase-requests/review",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                return adminReviewBookRequest($con, $adminData);
            },
        ],

        // Exams CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/exams",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListExams($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/exams/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetExam($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/exams",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateExam($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/exams",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateExam($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/exams",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteExam($con);
            },
        ],

        // Questions CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/questions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListQuestions($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/questions/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetQuestion($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/questions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateQuestion($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/questions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateQuestion($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/questions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteQuestion($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/questions/reorder",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminReorderQuestions($con);
            },
        ],

        // Certificates CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/certificates",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListCertificates($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/certificates/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetCertificate($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/certificates",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateCertificate($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/certificates",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateCertificate($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/certificates",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/content.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteCertificate($con);
            },
        ],

        // Admin: certificates issued to students (not tied to any course) ----
        [
            "method" => "GET",
            "path" => "/najeh/admin/student-certificates",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/content/certificates/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListStudentCertificates($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/student-certificates",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/content/certificates/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminAddStudentCertificate($con, $adminData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/student-certificates",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/content/certificates/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminRevokeStudentCertificate($con);
            },
        ],

        // Discount codes (reduce a price; they never grant access) ----------
        [
            "method" => "GET",
            "path" => "/najeh/admin/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/discounts/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_discounts');
                return adminListDiscountCodes($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/discount-codes/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/discounts/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_discounts');
                return adminGetDiscountCode($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/discounts/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_discounts');
                return adminCreateDiscountCode($con, $adminData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/discounts/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_discounts');
                return adminUpdateDiscountCode($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/discounts/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_discounts');
                return adminDeleteDiscountCode($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/discount-codes/check",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/discounts/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_discounts');
                return adminCheckDiscountCode($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/discount-codes/redemptions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/discounts/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_discounts');
                return adminListDiscountRedemptions($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/discount-codes/check",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/discounts/manage.php";
                $studentData = studentAuthMiddleware();
                return studentCheckDiscountCode($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/discount-codes/apply",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/discounts/manage.php";
                $studentData = studentAuthMiddleware();
                return studentApplyDiscountCode($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/discount-codes/my-codes",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/discounts/manage.php";
                $studentData = studentAuthMiddleware();
                return studentListDiscountClaims($con, $studentData);
            },
        ],
        [
            "method" => ["DELETE", "POST"],
            "path" => "/najeh/student/discount-codes/cancel",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/discounts/manage.php";
                $studentData = studentAuthMiddleware();
                return studentCancelDiscountClaim($con, $studentData);
            },
        ],

        // Discount Codes CRUD (Teacher - own courses only) --------------------------------
        [
            "method" => "GET",
            "path" => "/najeh/teacher/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/discounts/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherListDiscountCodes($con, $teacherData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/teacher/discount-codes/show",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/discounts/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherGetDiscountCode($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/discounts/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherCreateDiscountCode($con, $teacherData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/teacher/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/discounts/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherUpdateDiscountCode($con, $teacherData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/teacher/discount-codes",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/discounts/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherDeleteDiscountCode($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/discount-codes/check",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/discounts/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherCheckDiscountCode($con, $teacherData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/teacher/discount-codes/redemptions",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/discounts/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherListDiscountRedemptions($con, $teacherData);
            },
        ],

        // Subscriptions & Subscription Codes CRUD (Admin)
        [
            "method" => "GET",
            "path" => "/najeh/admin/subscription-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subscriptions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListSubscriptionCodes($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/subscription-codes/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subscriptions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetSubscriptionCode($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/subscription-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subscriptions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateSubscriptionCode($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/subscription-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subscriptions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateSubscriptionCode($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/subscription-codes",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subscriptions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteSubscriptionCode($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/subscriptions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subscriptions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListStudentSubscriptions($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/subscriptions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/subscriptions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateStudentSubscription($con, $adminData);
            },
        ],

        // Admin: Session Reschedule Requests
        [
            "method" => "GET",
            "path" => "/najeh/admin/reschedule-requests",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListRescheduleRequests($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/reschedule-requests",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminReviewRescheduleRequest($con);
            },
        ],

        // Admin: HR Policy Settings, Attendance Exceptions, Teacher Wallets & Appeals (Section 10)
        [
            "method" => "GET",
            "path" => "/najeh/admin/hr-settings",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/hr_settings/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetHrPolicySettings($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/hr-settings",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/hr_settings/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateHrPolicySettings($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/teachers/pay-rate",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/hr_settings/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminSetTeacherPayRate($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/teachers/pay-rate",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/hr_settings/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminClearTeacherPayRate($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/attendance/exceptions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListAttendanceExceptionRequests($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/attendance/exceptions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminReviewAttendanceExceptionRequest($con, $adminData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/teacher-wallets",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListTeacherWallets($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/teacher-wallets/ledger",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetTeacherWalletLedger($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/teacher-wallets/adjust",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminAdjustTeacherWallet($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/teacher-wallets/lift-suspension",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminLiftTeacherAttendanceSuspension($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/deduction-appeals",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListDeductionAppeals($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/deduction-appeals",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/attendance/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminReviewDeductionAppeal($con, $adminData);
            },
        ],

        [
            "method" => "GET",
            "path" => "/najeh/admin/consultations",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/consultations/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListConsultations($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/consultations",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/consultations/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateConsultationSlot($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/consultations",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/consultations/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteConsultationSlot($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/consultations/recurring",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/consultations/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateRecurringConsultationSlots($con);
            },
        ],

        [
            "method" => "GET",
            "path" => "/najeh/admin/sessions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/sessions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListSessions($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/sessions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/sessions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateSession($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/sessions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/sessions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateSession($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/sessions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/sessions/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteSession($con);
            },
        ],

        // Admin / Teacher / Student: dated availability slots -------------
        // Real-calendar appointments, a parallel layer to the weekly class_sessions pattern.
        [
            "method" => "GET",
            "path" => "/najeh/admin/availability",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListAvailabilitySlots($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/availability",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCreateAvailabilitySlot($con, $adminData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/availability",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminUpdateAvailabilitySlot($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/availability",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminDeleteAvailabilitySlot($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/availability/generate",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGenerateAvailabilitySlots($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/availability/book",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminBookStudentIntoSlot($con, $adminData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/availability/book",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminCancelStudentBooking($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/availability/schedule-student",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/availability/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminScheduleStudent($con, $adminData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/teacher/availability",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/availability/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherListAvailabilitySlots($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/availability",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/availability/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherCreateAvailabilitySlot($con, $teacherData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/teacher/availability",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/availability/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherUpdateAvailabilitySlot($con, $teacherData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/teacher/availability",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/availability/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherDeleteAvailabilitySlot($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/availability/generate",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/availability/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherGenerateAvailabilitySlots($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/availability/book",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/availability/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherBookStudentIntoSlot($con, $teacherData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/teacher/availability/book",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/availability/manage.php";
                $teacherData = teacherAuthMiddleware();
                return teacherCancelStudentBooking($con, $teacherData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/availability",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/availability/manage.php";
                $studentData = studentAuthMiddleware();
                return studentListAvailableSlots($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/availability/my-schedule",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/availability/manage.php";
                $studentData = studentAuthMiddleware();
                return studentMyScheduleSlots($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/availability/book",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/availability/manage.php";
                $studentData = studentAuthMiddleware();
                return studentBookAvailabilitySlot($con, $studentData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/student/availability/book",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/student/availability/manage.php";
                $studentData = studentAuthMiddleware();
                return studentCancelAvailabilityBooking($con, $studentData);
            },
        ],

        // Admin: Session Files --------------------------------
        [
            "method" => "GET",
            "path" => "/najeh/admin/sessions/files",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/files.php";
                $adminData = adminAuthMiddleware();
                return adminListSessionFiles($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/sessions/files",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/files.php";
                $adminData = adminAuthMiddleware();
                return adminAddSessionFile($con, $adminData);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/sessions/files",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/files.php";
                $adminData = adminAuthMiddleware();
                return adminUpdateSessionFile($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/sessions/files",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/files.php";
                $adminData = adminAuthMiddleware();
                return adminDeleteSessionFile($con);
            },
        ],

        // Admin: Session File Submissions & Grading --------------------------------
        [
            "method" => "GET",
            "path" => "/najeh/admin/sessions/files/submissions",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/files.php";
                $adminData = adminAuthMiddleware();
                return adminListSubmissions($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/sessions/files/grade",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/sessions/files.php";
                $adminData = adminAuthMiddleware();
                return adminGradeSubmission($con);
            },
        ],

        // Admin: Exam Attempts (Results & Grading)
        [
            "method" => "GET",
            "path" => "/najeh/admin/exams/attempts",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/exams/attempts.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminListExamAttempts($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/exams/attempts/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/exams/attempts.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGetExamAttemptDetail($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/exams/attempts",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/teacher/exams/attempts.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_teachers');
                return adminGradeExamAttempt($con, $adminData);
            },
        ],

        // Academic Years CRUD
        [
            "method" => "GET",
            "path" => "/najeh/landing",
            "handler" => function ($con) {
                header("Content-Type: text/html; charset=UTF-8");
                readfile(__DIR__ . "/website/index.html");
                exit();
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/home",
            "handler" => function ($con) {
                include_once "./app/admin/site_settings/manage.php";
                return getPublicLandingConfig($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/site-settings",
            "handler" => function ($con) {
                include_once "./app/admin/site_settings/manage.php";
                return getPublicLandingConfig($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/about-us",
            "handler" => function ($con) {
                include_once "./app/admin/site_settings/manage.php";
                return getPublicAboutUsConfig($con);
            },
        ],
        // The operational settings a client needs before anyone signs in: the support number, the
        // social links, and whether the platform is open. Separate from /site-settings, which is
        // the WEBSITE's landing payload - banners, courses and books - and is far too heavy to be
        // fetched for a phone number.
        [
            "method" => "GET",
            "path" => "/najeh/app-config",
            "handler" => function ($con) {
                return getPublicAppConfig($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/site-settings",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/site_settings/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_settings');
                return adminGetSiteSettings($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/site-settings",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/site_settings/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_settings');
                return adminUpdateSiteSettings($con);
            },
        ],
        // Clears every active lockout at once. The escape hatch for a limit set too low, so that
        // fixing it never requires phpMyAdmin.
        [
            "method" => "POST",
            "path" => "/najeh/admin/login-attempts/clear",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_settings');
                $cleared = login_attempt_clear_all($con);
                return [
                    'status' => 'success',
                    'message' => "تم إلغاء {$cleared} إيقاف مؤقت.",
                    'data' => ['cleared' => $cleared],
                ];
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/courses/toggle-website-visibility",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/courses/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_courses');
                return adminToggleCourseWebsiteVisibility($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/books/toggle-website-visibility",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/books/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_books');
                return adminToggleBookWebsiteVisibility($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/academic-years",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/academic_years/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_academic_years');
                return adminListAcademicYears($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/academic-years/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/academic_years/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_academic_years');
                return adminGetAcademicYear($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/academic-years",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/academic_years/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_academic_years');
                return adminCreateAcademicYear($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/academic-years",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/academic_years/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_academic_years');
                return adminUpdateAcademicYear($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/academic-years",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/academic_years/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_academic_years');
                return adminDeleteAcademicYear($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/academic-years/export",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/export/export.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_academic_years');
                adminExportAcademicYears($con);
            },
        ],

        // Course Categories CRUD
        [
            "method" => "GET",
            "path" => "/najeh/admin/course-categories",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/course_categories/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_course_categories');
                return adminListCourseCategories($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/admin/course-categories/show",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/course_categories/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_course_categories');
                return adminGetCourseCategory($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/course-categories",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/course_categories/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_course_categories');
                return adminCreateCourseCategory($con);
            },
        ],
        [
            "method" => "PUT",
            "path" => "/najeh/admin/course-categories",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/course_categories/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_course_categories');
                return adminUpdateCourseCategory($con);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/admin/course-categories",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/course_categories/manage.php";
                $adminData = adminAuthMiddleware();
                requirePermission($con, $adminData, 'manage_course_categories');
                return adminDeleteCourseCategory($con);
            },
        ],
    

    
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/login",
            "handler" => function ($con) {
                include_once "./app/teacher/auth/login.php";
                return teacherLogin($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/register",
            "handler" => function ($con) {
                include_once "./app/teacher/auth/register.php";
                return teacherRegister($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/verify-email",
            "handler" => function ($con) {
                return handleVerifyEmailOtp($con, 'teacher');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/resend-otp",
            "handler" => function ($con) {
                return handleResendOtp($con, 'teacher');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/forgot-password",
            "handler" => function ($con) {
                return handleForgotPassword($con, 'teacher');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/verify-reset-code",
            "handler" => function ($con) {
                return handleVerifyResetCode($con, 'teacher');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/reset-password",
            "handler" => function ($con) {
                return handleResetPassword($con, 'teacher');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/auth/refresh",
            "handler" => function () {
                return handleTokenRefresh('teacher');
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/teacher/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/auth/profile.php";
                $teacherData = teacherAuthMiddleware();
                return teacherProfile($con, $teacherData);
            },
        ],
        [
            "method" => ["PUT", "POST", "PATCH"],
            "path" => "/najeh/teacher/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/auth/profile.php";
                $teacherData = teacherAuthMiddleware();
                return teacherUpdateProfile($con, $teacherData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/teacher/fcm-token",
            "handler" => function ($con) {
                include_once "./middlewares/teacherauthmiddleware.php";
                include_once "./app/teacher/auth/profile.php";
                $teacherData = teacherAuthMiddleware();
                return teacherUpdateFcmToken($con, $teacherData);
            },
        ],
    
    
    
    [
        "method"  => "POST",
        "path"    => "/najeh/parent/auth/login",
        "handler" => function ($con) {
            include_once "./app/parent/auth/login.php";
            return parentLogin($con);
        },
    ],
    [
        "method"  => "POST",
        "path"    => "/najeh/parent/exams/exam_results",
        "handler" => function ($con) {
            include_once "./middlewares/studentauthmiddleware.php";
            include_once "./app/parent/exams/exam_results.php";
            $studentData = studentAuthMiddleware();

            $body      = read_json_body();
            $studentId = (int)($body['student_id'] ?? 0);

            if ($studentId <= 0) {
                return [
                    'status'  => 'error',
                    'message' => trans('student_id_required')
                ];
            }
            if ($studentId !== (int)($studentData['id'] ?? 0)) {
                return [
                    'status'  => 'error',
                    'message' => trans('unauthorized')
                ];
            }

            return [
                'status'  => 'success',
                'message' => trans('exams_fetched_successfully'),
                'data'    => get_student_exams_details($con, $studentId)
            ];
        },
    ],
    [
    "method"  => "POST",
    "path"    => "/najeh/parent/courses/courses_progress",
    "handler" => function ($con) {
        include_once "./middlewares/studentauthmiddleware.php";
        include_once "./app/parent/courses/courses_progress.php";
        $studentData = studentAuthMiddleware();

        $body      = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);

        if ($studentId <= 0) {
            return [
                'status'  => 'error',
                'message' => trans('student_id_required')
            ];
        }
        if ($studentId !== (int)($studentData['id'] ?? 0)) {
            return [
                'status'  => 'error',
                'message' => trans('unauthorized')
            ];
        }

        return [
            'status'  => 'success',
            'message' => trans('courses_progress_fetched_successfully'),
            'data'    => get_student_courses_progress($con, $studentId)
        ];
    },
],
    [
    "method"  => "POST",
    "path"    => "/najeh/parent/attendance/student_activity",
    "handler" => function ($con) {
        include_once "./middlewares/studentauthmiddleware.php";
        include_once "./app/parent/attendance/student_activity.php";
        $studentData = studentAuthMiddleware();

        $body      = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);

        if ($studentId <= 0) {
            return [
                'status'  => 'error',
                'message' => trans('student_id_required')
            ];
        }
        if ($studentId !== (int)($studentData['id'] ?? 0)) {
            return [
                'status'  => 'error',
                'message' => trans('unauthorized')
            ];
        }

        return [
            'status'  => 'success',
            'message' => trans('student_attendance_successfully'),
            'data'    => get_student_attendance($con, $studentId)
        ];
    },
],
    [
    "method"  => "POST",
    "path"    => "/najeh/parent/subscription/student_subscriptions",
    "handler" => function ($con) {
        include_once "./middlewares/studentauthmiddleware.php";
        include_once "./app/parent/subscription/student_subscriptions.php";
        $studentData = studentAuthMiddleware();

        $body      = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);

        if ($studentId <= 0) {
            return [
                'status'  => 'error',
                'message' => trans('student_id_required')
            ];
        }
        if ($studentId !== (int)($studentData['id'] ?? 0)) {
            return [
                'status'  => 'error',
                'message' => trans('unauthorized')
            ];
        }

        return [
            'status'  => 'success',
            'message' => trans('student_subscriptions_successfully'),
            'data'    => get_student_subscriptions($con, $studentId)
        ];
    },
],
    [
    "method"  => "POST",
    "path"    => "/najeh/parent/certifications/student_certificates",
    "handler" => function ($con) {
        include_once "./middlewares/studentauthmiddleware.php";
        include_once "./app/parent/certifications/student_certificates.php";
        $studentData = studentAuthMiddleware();

        $body      = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);

        if ($studentId <= 0) {
            return [
                'status'  => 'error',
                'message' => trans('student_id_required')
            ];
        }
        if ($studentId !== (int)($studentData['id'] ?? 0)) {
            return [
                'status'  => 'error',
                'message' => trans('unauthorized')
            ];
        }

        return [
            'status'  => 'success',
            'message' => trans('student_certificates_successfully'),
            'data'    => get_student_certificates($con, $studentId)
        ];
    },
],



   
        // Student Auth
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/login",
            "handler" => function ($con) {
                include_once "./app/user/student/auth/login.php";
                return studentLogin($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/register",
            "handler" => function ($con) {
                include_once "./app/user/student/auth/register.php";
                return studentRegister($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/verify-email",
            "handler" => function ($con) {
                return handleVerifyEmailOtp($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/resend-otp",
            "handler" => function ($con) {
                return handleResendOtp($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/forgot-password",
            "handler" => function ($con) {
                return handleForgotPassword($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/verify-reset-code",
            "handler" => function ($con) {
                return handleVerifyResetCode($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/reset-password",
            "handler" => function ($con) {
                return handleResetPassword($con, 'student');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/auth/refresh",
            "handler" => function () {
                return handleTokenRefresh('student');
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/profile.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentProfile($con, $studentData);
            },
        ],
        [
            "method" => ["PUT", "POST", "PATCH"],
            "path" => "/najeh/user/student/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/profile.php";
                $studentData = studentAuthMiddleware();
                return studentUpdateProfile($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/student/fcm-token",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/profile.php";
                $studentData = studentAuthMiddleware();
                return studentUpdateFcmToken($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/devices",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/devices.php";
                $studentData = studentAuthMiddleware();
                return studentListMyDevices($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/devices",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/devices.php";
                $studentData = studentAuthMiddleware();
                return studentListMyDevices($con, $studentData);
            },
        ],
        [
            "method" => ["PUT", "POST", "PATCH"],
            "path" => "/najeh/user/student/profile",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/auth/profile.php";
                $studentData = studentAuthMiddleware();
                return studentUpdateProfile($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/subscriptions/redeem-code",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/subscriptions/manage.php";
                $studentData = studentAuthMiddleware();
                return studentRedeemSubscriptionCode($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/payments/checkout",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/payments/manage.php";
                $studentData = studentAuthMiddleware();
                return studentInitiatePayment($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/payments/status",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/payments/manage.php";
                $studentData = studentAuthMiddleware();
                $transactionId = (int)($_GET['transaction_id'] ?? 0);
                return studentGetPaymentStatus($con, $studentData, $transactionId);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/payments/confirm",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/payments/manage.php";
                $studentData = studentAuthMiddleware();
                return studentConfirmPayment($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/payments/easykash/callback",
            "handler" => function ($con) {
                include_once "./helpers/easykash.php";
                include_once "./app/user/student/payments/manage.php";
                return paymentsEasykashCallback($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/payments/easykash/callback",
            "handler" => function ($con) {
                include_once "./helpers/easykash.php";
                include_once "./app/user/student/payments/manage.php";
                return paymentsEasykashCallback($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/subscriptions/my-subscriptions",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/subscriptions/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentListMySubscriptions($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/my-subscribed-courses",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/subscriptions/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMySubscribedCourses($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/my-subscribed-subjects",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/subscriptions/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMySubscribedSubjects($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/my-subscribed-teachers",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/subscriptions/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMySubscribedTeachers($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/my-courses-progress",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMySubscribedCoursesProgress($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/my-subscribed-courses-progress",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMySubscribedCoursesProgress($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/my-subscribed-books",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMySubscribedBooks($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/my-certificates",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMyCertificates($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/videos/track-progress",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddleware();
                return studentUpdateVideoProgress($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/books/track-open",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddleware();
                return studentTrackBookOpen($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/books/subscribe",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/books.php";
                $studentData = studentAuthMiddleware();
                return studentSubscribeBook($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/books/buy",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/books.php";
                $studentData = studentAuthMiddleware();
                return studentSubscribeBook($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/books/purchase-requests",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/books.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMyBookRequests($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/home",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentHome($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/courses-and-subjects",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                return studentGetCoursesAndSubjects($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/all-courses-and-subjects",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                return studentGetCoursesAndSubjects($con);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/courses",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetAllCoursesWithOwn($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/courses/show",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetCourseDetails($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/teacher-details",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetTeacherProfileDetails($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/teachers/show",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetTeacherProfileDetails($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/course-details",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetCourseDetails($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/courses/rate",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddleware();
                return studentRateCourse($con, $studentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/student/teachers/rate",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/manage.php";
                $studentData = studentAuthMiddleware();
                return studentRateTeacher($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/books",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/books.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetAllBooksWithOwn($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/books/stream",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/books.php";
                $studentData = studentAuthMiddleware();
                studentStreamBookFile($con, $studentData);
            },
        ],

        // Parent Auth
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/login",
            "handler" => function ($con) {
                include_once "./app/user/parent/auth/login.php";
                return parentLogin($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/register",
            "handler" => function ($con) {
                include_once "./app/user/parent/auth/register.php";
                return parentRegister($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/verify-email",
            "handler" => function ($con) {
                return handleVerifyEmailOtp($con, 'parent');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/resend-otp",
            "handler" => function ($con) {
                return handleResendOtp($con, 'parent');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/forgot-password",
            "handler" => function ($con) {
                return handleForgotPassword($con, 'parent');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/verify-reset-code",
            "handler" => function ($con) {
                return handleVerifyResetCode($con, 'parent');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/reset-password",
            "handler" => function ($con) {
                return handleResetPassword($con, 'parent');
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/auth/refresh",
            "handler" => function () {
                return handleTokenRefresh('parent');
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/parent/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/auth/profile.php";
                $parentData = parentAuthMiddleware();
                return parentProfile($con, $parentData);
            },
        ],
        [
            "method" => ["PUT", "POST", "PATCH"],
            "path" => "/najeh/user/parent/auth/profile",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/auth/profile.php";
                $parentData = parentAuthMiddleware();
                return parentUpdateProfile($con, $parentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/user/parent/fcm-token",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/auth/profile.php";
                $parentData = parentAuthMiddleware();
                return parentUpdateFcmToken($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/parent/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/notifications/manage.php";
                $parentData = parentAuthMiddleware();
                return parentListNotifications($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/parent/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/notifications/manage.php";
                $parentData = parentAuthMiddleware();
                return parentListNotifications($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/parent/notifications/unread-count",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/notifications/manage.php";
                $parentData = parentAuthMiddleware();
                return parentGetNotificationUnreadCount($con, $parentData);
            },
        ],
        [
            "method" => ["POST", "PUT"],
            "path" => "/najeh/parent/notifications/read",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/notifications/manage.php";
                $parentData = parentAuthMiddleware();
                return parentMarkNotificationRead($con, $parentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/parent/notifications/mark-read",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/notifications/manage.php";
                $parentData = parentAuthMiddleware();
                return parentMarkNotificationRead($con, $parentData);
            },
        ],
        [
            "method" => "DELETE",
            "path" => "/najeh/parent/notifications",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/notifications/manage.php";
                $parentData = parentAuthMiddleware();
                return parentDeleteNotification($con, $parentData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/parent/notifications/delete",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/notifications/manage.php";
                $parentData = parentAuthMiddleware();
                return parentDeleteNotification($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/reports",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/reports.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentListMyReports($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/student/reports/show",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/reports.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMyReport($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/reports",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/reports.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentListMyReports($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/reports/show",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/content/reports.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetMyReport($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/parent/reports",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/reports/manage.php";
                $parentData = parentAuthMiddleware();
                return parentListChildrenReports($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/user/parent/reports/show",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/reports/manage.php";
                $parentData = parentAuthMiddleware();
                return parentGetChildReport($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/parent/reports",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/reports/manage.php";
                $parentData = parentAuthMiddleware();
                return parentListChildrenReports($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/parent/reports/show",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/reports/manage.php";
                $parentData = parentAuthMiddleware();
                return parentGetChildReport($con, $parentData);
            },
        ],

        // Parent: Children's Sessions --------------------------------
        [
            "method" => "GET",
            "path" => "/najeh/parent/sessions",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/sessions/manage.php";
                $parentData = parentAuthMiddleware();
                return parentGetChildSessions($con, $parentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/parent/sessions/files",
            "handler" => function ($con) {
                include_once "./middlewares/parentauthmiddleware.php";
                include_once "./app/user/parent/sessions/manage.php";
                $parentData = parentAuthMiddleware();
                return parentGetChildSessionFiles($con, $parentData);
            },
        ],
        // Student Affiliate Routes --------------------------------
        [
            "method" => "GET",
            "path" => "/najeh/user/student/affiliate/info",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/affiliate/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetAffiliateInfo($con, $studentData);
            },
        ],
        [
            "method" => "GET",
            "path" => "/najeh/student/affiliate/info",
            "handler" => function ($con) {
                include_once "./middlewares/studentauthmiddleware.php";
                include_once "./app/user/student/affiliate/manage.php";
                $studentData = studentAuthMiddlewareOptional();
                return studentGetAffiliateInfo($con, $studentData);
            },
        ],

        // Admin Affiliate Routes --------------------------------
        [
            "method" => "GET",
            "path" => "/najeh/admin/affiliates/requests",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/affiliates/manage.php";
                $adminData = adminAuthMiddleware();
                return adminListAffiliateRequests($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/affiliates/approve",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/affiliates/manage.php";
                $adminData = adminAuthMiddleware();
                return adminApproveAffiliateRequest($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/affiliates/reject",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/affiliates/manage.php";
                $adminData = adminAuthMiddleware();
                return adminRejectAffiliateRequest($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/affiliates/deduct-points",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/affiliates/manage.php";
                $adminData = adminAuthMiddleware();
                return adminDeductStudentPoints($con, $adminData);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/admin/students/deduct-points",
            "handler" => function ($con) {
                include_once "./middlewares/adminauthmiddleware.php";
                include_once "./app/admin/affiliates/manage.php";
                $adminData = adminAuthMiddleware();
                return adminDeductStudentPoints($con, $adminData);
            },
        ],

        // Audio & File Upload Endpoints --------------------------------
        [
            "method" => "POST",
            "path" => "/najeh/common/upload/audio",
            "handler" => function ($con) {
                include_once "./middlewares/anyauthmiddleware.php";
                anyAuthMiddleware();
                include_once "./app/common/upload/manage.php";
                return handleAudioFileUpload($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/upload/audio",
            "handler" => function ($con) {
                include_once "./middlewares/anyauthmiddleware.php";
                anyAuthMiddleware();
                include_once "./app/common/upload/manage.php";
                return handleAudioFileUpload($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/common/upload",
            "handler" => function ($con) {
                include_once "./app/common/upload/manage.php";
                return handleGeneralFileUpload($con);
            },
        ],
        [
            "method" => "POST",
            "path" => "/najeh/upload",
            "handler" => function ($con) {
                include_once "./app/common/upload/manage.php";
                return handleGeneralFileUpload($con);
            },
        ],
    
];

foreach ($routes as $key => $route) {
    if (is_array($route) && !isset($route["method"])) {
        foreach ($route as $subRoute) {
            handleRoute($subRoute, $method, $path, $con);
        }
    } else {
        handleRoute($route, $method, $path, $con);
    }
}

json_response(
    [
        "status" => "error",
        "message" => trans("route_not_found"),
        "method" => $method,
        "path" => $path,
    ],
    404
);
