<?php
require_once __DIR__ . '/JWT.php';

if (!function_exists('generateJWT')) {
    function generateJWT($payload) {
        return generateAccessToken($payload, 'admin');
    }
}

if (!function_exists('verifyJWT')) {
    function verifyJWT($token) {
        return verifyAdminAccessToken($token);
    }
}