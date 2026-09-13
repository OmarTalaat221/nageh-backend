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
                // Unlike other roles, a suspended teacher (is_active = 0) is intentionally still
                // allowed to authenticate here: the frontend keeps them logged in but restricts
                // the UI to a single "contact support" chat entry showing the suspension reason,
                // instead of forcing a hard logout.
                return $decoded;
            }
        }

        json_response([
            'status' => 'error',
            'message' => trans('unauthorized')
        ], 401);
    }
}
