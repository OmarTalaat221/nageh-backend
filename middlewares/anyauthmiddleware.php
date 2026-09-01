<?php
require_once __DIR__ . "/../helpers/JWT.php";
require_once __DIR__ . "/../helpers/language.php";

if (!function_exists('verifyAnyRoleAccessToken')) {
    function verifyAnyRoleAccessToken(string $token) {
        foreach (['admin', 'teacher', 'student', 'parent'] as $role) {
            $decoded = verifyAccessToken($token, $role);
            if ($decoded) {
                $decoded['role'] = $decoded['role'] ?? $role;
                return $decoded;
            }
        }
        return false;
    }
}

if (!function_exists('anyAuthMiddleware')) {
    function anyAuthMiddleware() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = verifyAnyRoleAccessToken($token);

            if ($decoded) {
                return $decoded;
            }
        }

        json_response([
            'status' => 'error',
            'message' => trans('unauthorized')
        ], 401);
    }
}
