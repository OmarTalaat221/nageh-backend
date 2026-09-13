<?php
require_once __DIR__ . "/../helpers/JWT.php";
require_once __DIR__ . "/../helpers/language.php";

if (!function_exists('parentAuthMiddleware')) {
    function parentAuthMiddleware() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = verifyParentAccessToken($token);

            if ($decoded) {
                global $con;
                $parentId = is_array($decoded) ? ($decoded['id'] ?? null) : null;
                if ($parentId && $con) {
                    $stmt = mysqli_prepare($con, "SELECT is_active FROM parents WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt, "i", $parentId);
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
