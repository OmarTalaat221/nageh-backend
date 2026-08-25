<?php
require_once __DIR__ . '/JWT.php';

if (!function_exists('generateUserJWT')) {
    function generateUserJWT($payload) {
        return generateAccessToken($payload, 'user');
    }
}

if (!function_exists('verifyUserJWT')) {
    function verifyUserJWT($token) {
        return verifyStudentAccessToken($token);
    }
}
