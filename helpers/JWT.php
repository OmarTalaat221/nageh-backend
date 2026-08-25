<?php

if (!defined('NAJEH_ADMIN_JWT_SECRET')) {
    define('NAJEH_ADMIN_JWT_SECRET', 'Najeh_Admin_Super_Secret_Key_2026!@#');
}
if (!defined('NAJEH_TEACHER_JWT_SECRET')) {
    define('NAJEH_TEACHER_JWT_SECRET', 'Najeh_Teacher_Super_Secret_Key_2026!$%');
}
if (!defined('NAJEH_STUDENT_JWT_SECRET')) {
    define('NAJEH_STUDENT_JWT_SECRET', 'Najeh_Student_Super_Secret_Key_2026!^&');
}
if (!defined('NAJEH_PARENT_JWT_SECRET')) {
    define('NAJEH_PARENT_JWT_SECRET', 'Najeh_Parent_Super_Secret_Key_2026!*()');
}

if (!function_exists('base64UrlEncode')) {
    function base64UrlEncode(string $text): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }
}

if (!function_exists('base64UrlDecode')) {
    function base64UrlDecode(string $text): string {
        return base_decode_safe($text);
    }
}

if (!function_exists('base_decode_safe')) {
    function base_decode_safe(string $text): string {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $text));
    }
}

if (!function_exists('getJwtSecretByRole')) {
    function getJwtSecretByRole(string $role): string {
        switch (strtolower($role)) {
            case 'admin':
                return NAJEH_ADMIN_JWT_SECRET;
            case 'teacher':
                return NAJEH_TEACHER_JWT_SECRET;
            case 'parent':
                return NAJEH_PARENT_JWT_SECRET;
            case 'student':
            case 'user':
            default:
                return NAJEH_STUDENT_JWT_SECRET;
        }
    }
}

if (!function_exists('generateAccessToken')) {
    function generateAccessToken(array $payload, string $role = 'student', int $ttlSeconds = 3600): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload['token_type'] = 'access';
        $payload['role'] = strtolower($role);
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;

        $base64UrlHeader = base64UrlEncode($header);
        $base64UrlPayload = base64UrlEncode(json_encode($payload));
        
        $secret = getJwtSecretByRole($role);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}

if (!function_exists('generateRefreshToken')) {
    function generateRefreshToken(array $payload, string $role = 'student', int $ttlSeconds = 2592000): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload['token_type'] = 'refresh';
        $payload['role'] = strtolower($role);
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;

        $base64UrlHeader = base64UrlEncode($header);
        $base64UrlPayload = base64UrlEncode(json_encode($payload));
        
        $secret = getJwtSecretByRole($role);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}

if (!function_exists('generateTokens')) {
    function generateTokens(array $payload, string $role = 'student', int $accessTtl = 3600, int $refreshTtl = 2592000): array {
        return [
            'access_token' => generateAccessToken($payload, $role, $accessTtl),
            'refresh_token' => generateRefreshToken($payload, $role, $refreshTtl),
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl
        ];
    }
}

if (!function_exists('verifyToken')) {
    function verifyToken(string $token, string $role = 'student', ?string $expectedTokenType = null) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        $secret = getJwtSecretByRole($role);
        
        $validSignature = base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, $secret, true));
        
        if (!hash_equals($validSignature, $signature)) {
            return false;
        }
        
        $decodedPayload = json_decode(base64UrlDecode($payload), true);
        if (!is_array($decodedPayload)) {
            return false;
        }
        
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }

        if ($expectedTokenType !== null && ($decodedPayload['token_type'] ?? '') !== $expectedTokenType) {
            return false;
        }
        
        return $decodedPayload;
    }
}

if (!function_exists('verifyAccessToken')) {
    function verifyAccessToken(string $token, string $role = 'student') {
        return verifyToken($token, $role, 'access');
    }
}

if (!function_exists('verifyRefreshToken')) {
    function verifyRefreshToken(string $token, string $role = 'student') {
        return verifyToken($token, $role, 'refresh');
    }
}

if (!function_exists('generateAdminTokens')) {
    function generateAdminTokens(array $payload, int $accessTtl = 3600, int $refreshTtl = 2592000): array {
        return generateTokens($payload, 'admin', $accessTtl, $refreshTtl);
    }
}
if (!function_exists('verifyAdminAccessToken')) {
    function verifyAdminAccessToken(string $token) {
        return verifyAccessToken($token, 'admin');
    }
}
if (!function_exists('verifyAdminRefreshToken')) {
    function verifyAdminRefreshToken(string $token) {
        return verifyRefreshToken($token, 'admin');
    }
}

if (!function_exists('generateTeacherTokens')) {
    function generateTeacherTokens(array $payload, int $accessTtl = 3600, int $refreshTtl = 2592000): array {
        return generateTokens($payload, 'teacher', $accessTtl, $refreshTtl);
    }
}
if (!function_exists('verifyTeacherAccessToken')) {
    function verifyTeacherAccessToken(string $token) {
        return verifyAccessToken($token, 'teacher');
    }
}
if (!function_exists('verifyTeacherRefreshToken')) {
    function verifyTeacherRefreshToken(string $token) {
        return verifyRefreshToken($token, 'teacher');
    }
}

if (!function_exists('generateStudentTokens')) {
    function generateStudentTokens(array $payload, int $accessTtl = 3600, int $refreshTtl = 2592000): array {
        return generateTokens($payload, 'student', $accessTtl, $refreshTtl);
    }
}
if (!function_exists('verifyStudentAccessToken')) {
    function verifyStudentAccessToken(string $token) {
        return verifyAccessToken($token, 'student');
    }
}
if (!function_exists('verifyStudentRefreshToken')) {
    function verifyStudentRefreshToken(string $token) {
        return verifyRefreshToken($token, 'student');
    }
}

if (!function_exists('generateParentTokens')) {
    function generateParentTokens(array $payload, int $accessTtl = 3600, int $refreshTtl = 2592000): array {
        return generateTokens($payload, 'parent', $accessTtl, $refreshTtl);
    }
}
if (!function_exists('verifyParentAccessToken')) {
    function verifyParentAccessToken(string $token) {
        return verifyAccessToken($token, 'parent');
    }
}
if (!function_exists('verifyParentRefreshToken')) {
    function verifyParentRefreshToken(string $token) {
        return verifyRefreshToken($token, 'parent');
    }
}
