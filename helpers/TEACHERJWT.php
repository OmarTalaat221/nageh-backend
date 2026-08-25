<?php
require_once __DIR__ . '/JWT.php';

if (!function_exists('generateTeacherJWT')) {
    function generateTeacherJWT($payload) {
        return generateAccessToken($payload, 'teacher');
    }
}

if (!function_exists('verifyTeacherJWT')) {
    function verifyTeacherJWT($token) {
        return verifyTeacherAccessToken($token);
    }
}
