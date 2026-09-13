<?php
require_once __DIR__ . "/../helpers/JWT.php";
require_once __DIR__ . "/../helpers/language.php";

if (!function_exists('teacherAuthMiddleware')) {
    function teacherAuthMiddleware() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = verifyTeacherAccessToken($token);

            if ($decoded) {
                // Note: `is_attendance_suspended` (auto-set for excessive unauthorized absences)
                // is intentionally NOT checked here — that restriction only limits the teacher to
                // the support chat client-side, it must never block login or API access. Only a
                // genuine admin-disabled account (is_active = 0) is rejected below.
                global $con;
                $teacherId = is_array($decoded) ? ($decoded['id'] ?? null) : null;
                if ($teacherId && $con) {
                    $stmt = mysqli_prepare($con, "SELECT is_active FROM teachers WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt, "i", $teacherId);
                    mysqli_stmt_execute($stmt);
                    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                    if (!$row || (int)$row['is_active'] === 0) {
                        json_response([
                            'status' => 'error',
                            'message' => trans('account_inactive')
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
