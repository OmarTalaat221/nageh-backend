<?php
require_once __DIR__ . "/../helpers/JWT.php";
require_once __DIR__ . "/../helpers/language.php";
require_once __DIR__ . "/../helpers/devices.php";

if (!function_exists('studentAuthMiddleware')) {
    function studentAuthMiddleware() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = verifyStudentAccessToken($token);

            if ($decoded) {
                global $con;
                $studentId = is_array($decoded) ? ($decoded['id'] ?? null) : null;
                if ($studentId && $con) {
                    $stmt = mysqli_prepare($con, "SELECT is_active FROM students WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt, "i", $studentId);
                    mysqli_stmt_execute($stmt);
                    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                    if (!$row || (int)$row['is_active'] === 0) {
                        json_response([
                            'status' => 'error',
                            'message' => trans('account_inactive')
                        ], 401);
                    }

                    // Checked on every request, not just at login: an admin resetting a student's
                    // devices has to end the sessions already open on them, otherwise the reset
                    // does nothing until the tokens happen to expire.
                    //
                    // Tokens minted before this feature carry no `did` and are left alone, so
                    // turning the limit on does not sign out everyone who is already logged in.
                    $deviceId = (string)($decoded['did'] ?? '');
                    if ($deviceId !== '' && !device_is_authorized($con, 'student', (int)$studentId, $deviceId)) {
                        json_response([
                            'status' => 'error',
                            'message' => 'تم إلغاء تفعيل هذا الجهاز. يرجى تسجيل الدخول مرة أخرى أو التواصل مع الدعم الفني.',
                            'error_code' => 'device_revoked'
                        ], 401);
                    }

                    // The admin's "sign out everywhere". Checked against `iat` rather than the
                    // device, so it also catches tokens issued before devices existed or while
                    // the limit was switched off - the ones device revocation cannot see.
                    if (session_is_revoked($con, (int)$studentId, $decoded['iat'] ?? 0)) {
                        json_response([
                            'status' => 'error',
                            'message' => 'تم تسجيل خروجك من كل الأجهزة. يرجى تسجيل الدخول مرة أخرى.',
                            'error_code' => 'session_revoked'
                        ], 401);
                    }
                }

                return $decoded;
            }
        }

        json_response([
            'status' => 'error',
            'message' => trans('unauthorized')
        ], 401);
    }
}

/**
 * Same as studentAuthMiddleware() but never rejects the request: if a valid `Authorization:
 * Bearer <token>` is present it returns the decoded student payload (so the response can be
 * personalized - e.g. `own`/`is_subscribed` flags), otherwise it returns null and the endpoint
 * falls back to its public/unauthenticated behavior.
 */
if (!function_exists('studentAuthMiddlewareOptional')) {
    function studentAuthMiddlewareOptional() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $decoded = verifyStudentAccessToken($matches[1]);
            if ($decoded) {
                return $decoded;
            }
        }

        return null;
    }
}
