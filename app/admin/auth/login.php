<?php
require_once __DIR__ . '/../../../helpers/app_settings.php';

if (!function_exists('adminLogin')) {
    function adminLogin($con) {
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('email_password_required')];
        }

        // The admin account is the one worth guessing at, so it is counted like any other. The
        // count is per (email, IP), so an attacker locks out their own address and never the
        // admin's, and the lock expires on its own - there is no state to unstick.
        $locked = login_attempt_guard($con, $email, 'admin');
        if ($locked !== null) return $locked;

        $stmt = mysqli_prepare($con, "SELECT id, name, email, password, role, is_active FROM admins WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database query preparation failed: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        if (!$admin) {
            login_attempt_fail($con, $email, 'admin');
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if ((int)$admin['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('account_inactive')];
        }

        if (!password_verify($password, $admin['password'])) {
            login_attempt_fail($con, $email, 'admin');
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        login_attempt_reset($con, $email, 'admin');

        $payload = [
            'id' => (int)$admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role' => 'admin',
            'admin_role' => $admin['role']
        ];

        $tokens = generateAdminTokens($payload);

        return [
            'status' => 'success',
            'message' => trans('admin_login_success'),
            'data' => [
                'user' => [
                    'id' => (int)$admin['id'],
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'role' => $admin['role']
                ],
                'tokens' => $tokens
            ]
        ];
    }
}
