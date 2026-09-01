<?php
require_once __DIR__ . '/../../../helpers/notifications.php';
require_once __DIR__ . '/../admins/manage.php';

if (!function_exists('adminProfile')) {
    function adminProfile($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);

        $stmt = mysqli_prepare($con, "SELECT id, name, email, role, is_active, created_at FROM admins WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $adminId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($res);

        if (!$admin) {
            return ['status' => 'error', 'message' => trans('profile_not_found')];
        }

        if (function_exists('get_admin_permissions')) {
            $admin['permissions'] = get_admin_permissions($con, $adminId);
            $admin['permission_codes'] = array_map(fn($p) => $p['code'], $admin['permissions']);
        }

        return [
            'status' => 'success',
            'message' => trans('profile_fetched_success'),
            'data' => $admin
        ];
    }
}

if (!function_exists('adminUpdateProfile')) {
    function adminUpdateProfile($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();

        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($body['name']) && trim($body['name']) !== '') { $updateFields[] = "name = ?"; $params[] = trim($body['name']); $types .= "s"; }

        if (isset($body['password']) && strlen(trim($body['password'])) > 0) {
            if (strlen(trim($body['password'])) < 6) {
                return ['status' => 'error', 'message' => trans('short_password')];
            }
            $updateFields[] = "password = ?";
            $params[] = password_hash(trim($body['password']), PASSWORD_BCRYPT);
            $types .= "s";
        }

        if (empty($updateFields)) {
            return ['status' => 'error', 'message' => 'No fields to update'];
        }

        $params[] = $adminId;
        $types .= "i";
        $stmt = mysqli_prepare($con, "UPDATE admins SET " . implode(", ", $updateFields) . " WHERE id = ?");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update profile: ' . mysqli_error($con)];
        }

        return adminProfile($con, $adminData);
    }
}

if (!function_exists('adminUpdateFcmToken')) {
    function adminUpdateFcmToken($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();
        return save_user_fcm_token($con, 'admin', $adminId, trim($body['fcm_token'] ?? ''));
    }
}
