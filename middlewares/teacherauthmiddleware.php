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
                global $con;
                $teacherId = is_array($decoded) ? ($decoded['id'] ?? null) : ($decoded->id ?? null);
                if ($teacherId && $con) {
                    $stmt = mysqli_prepare($con, "SELECT is_active FROM teachers WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt, "i", $teacherId);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $row = $result ? mysqli_fetch_assoc($result) : null;

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
