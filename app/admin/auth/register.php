<?php

if (!function_exists('adminRegister')) {
    function adminRegister($con) {
        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $role = trim($body['role'] ?? 'admin');

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('name_email_password_required')];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => trans('invalid_email')];
        }

        if (strlen($password) < 6) {
            return ['status' => 'error', 'message' => trans('short_password')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM admins WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($res)) {
            return ['status' => 'error', 'message' => trans('email_already_registered')];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($con, "INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashedPassword, $role);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create admin: ' . mysqli_error($con)];
        }

        $adminId = mysqli_insert_id($con);
        $payload = [
            'id' => (int)$adminId,
            'name' => $name,
            'email' => $email,
            'role' => 'admin',
            'admin_role' => $role
        ];

        $tokens = generateAdminTokens($payload);

        return [
            'status' => 'success',
            'message' => trans('admin_register_success'),
            'data' => [
                'user' => [
                    'id' => (int)$adminId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role
                ],
                'tokens' => $tokens
            ]
        ];
    }
}
