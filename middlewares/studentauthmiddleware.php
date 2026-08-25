<?php
require_once __DIR__ . "/../helpers/JWT.php";
require_once __DIR__ . "/../helpers/language.php";

if (!function_exists('studentAuthMiddleware')) {
    function studentAuthMiddleware() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = verifyStudentAccessToken($token);

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
