<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('get_admin_permissions')) {
    function get_admin_permissions($con, int $adminId): array {
        $stmt = mysqli_prepare($con, "SELECT p.id, p.code, p.name, p.module FROM permissions p JOIN admin_permissions ap ON p.id = ap.permission_id WHERE ap.admin_id = ? ORDER BY p.id ASC");
        mysqli_stmt_bind_param($stmt, "i", $adminId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $permissions = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $permissions[] = $row;
        }
        return $permissions;
    }
}

if (!function_exists('set_admin_permissions')) {
    function set_admin_permissions($con, int $adminId, array $permissionIds): void {
        $delStmt = mysqli_prepare($con, "DELETE FROM admin_permissions WHERE admin_id = ?");
        mysqli_stmt_bind_param($delStmt, "i", $adminId);
        mysqli_stmt_execute($delStmt);

        $permissionIds = array_values(array_unique(array_filter(array_map('intval', $permissionIds), fn($id) => $id > 0)));
        if (empty($permissionIds)) return;

        $insertStmt = mysqli_prepare($con, "INSERT IGNORE INTO admin_permissions (admin_id, permission_id) VALUES (?, ?)");
        foreach ($permissionIds as $permissionId) {
            mysqli_stmt_bind_param($insertStmt, "ii", $adminId, $permissionId);
            mysqli_stmt_execute($insertStmt);
        }
    }
}

if (!function_exists('adminListAdmins')) {
    function adminListAdmins($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (a.name LIKE ? OR a.email LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $countSql = "SELECT COUNT(*) AS total FROM admins a {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($whereClause)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "SELECT a.id, a.name, a.email, a.role, a.role_id, a.image, a.is_active, a.created_at, r.name AS role_name, r.code AS role_code FROM admins a LEFT JOIN roles r ON a.role_id = r.id {$whereClause} ORDER BY a.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $admins = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['role_id'] = $row['role_id'] ? (int)$row['role_id'] : null;
            $row['is_active'] = (int)$row['is_active'];
            $row['permissions'] = get_admin_permissions($con, $row['id']);
            $row['permission_ids'] = array_map(fn($p) => $p['id'], $row['permissions']);
            $admins[] = $row;
        }

        return build_admin_paginated_response(format_response_images($admins), $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminGetAdmin')) {
    function adminGetAdmin($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('admin_not_found')];

        $stmt = mysqli_prepare($con, "SELECT a.id, a.name, a.email, a.role, a.role_id, a.image, a.is_active, a.created_at, r.name AS role_name, r.code AS role_code FROM admins a LEFT JOIN roles r ON a.role_id = r.id WHERE a.id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) return ['status' => 'error', 'message' => trans('admin_not_found')];

        $row['id'] = (int)$row['id'];
        $row['role_id'] = $row['role_id'] ? (int)$row['role_id'] : null;
        $row['is_active'] = (int)$row['is_active'];
        $row['permissions'] = get_admin_permissions($con, $row['id']);
        $row['permission_ids'] = array_map(fn($p) => $p['id'], $row['permissions']);

        return ['status' => 'success', 'data' => format_response_images($row)];
    }
}

if (!function_exists('adminCreateAdminUser')) {
    function adminCreateAdminUser($con) {
        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $role = strtolower(trim($body['role'] ?? 'admin'));
        $roleId = isset($body['role_id']) ? (int)$body['role_id'] : null;
        $image = trim($body['image'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => trans('name_email_password_required')];
        }

        if (!in_array($role, ['super_admin', 'admin', 'moderator'])) {
            $role = 'admin';
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM admins WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('email_already_registered')];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($con, "INSERT INTO admins (name, email, password, role, role_id, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssisi", $name, $email, $hashedPassword, $role, $roleId, $image, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create admin user: ' . mysqli_error($con)];
        }

        $adminId = mysqli_insert_id($con);

        if (isset($body['permission_ids']) && is_array($body['permission_ids'])) {
            set_admin_permissions($con, $adminId, $body['permission_ids']);
        }

        $_GET['id'] = $adminId;
        return [
            'status' => 'success',
            'message' => trans('admin_user_created_success'),
            'data' => adminGetAdmin($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateAdminUser')) {
    function adminUpdateAdminUser($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('admin_not_found')];

        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $role = strtolower(trim($body['role'] ?? ''));
        $roleId = (array_key_exists('role_id', $body) && $body['role_id'] !== null && $body['role_id'] !== '') ? (int)$body['role_id'] : null;
        $image = isset($body['image']) ? trim($body['image']) : null;

        $isActive = null;
        if (array_key_exists('is_active', $body) && $body['is_active'] !== null && $body['is_active'] !== '') {
            $isActive = (int)$body['is_active'];
        } elseif (array_key_exists('status', $body) && $body['status'] !== null && $body['status'] !== '') {
            $isActive = (int)$body['status'];
        } elseif (array_key_exists('active', $body) && $body['active'] !== null && $body['active'] !== '') {
            $isActive = (int)$body['active'];
        }

        if (!empty($name)) {
            $stmt = mysqli_prepare($con, "UPDATE admins SET name = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $name, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($email)) {
            $stmt = mysqli_prepare($con, "UPDATE admins SET email = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($con, "UPDATE admins SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($role) && in_array($role, ['super_admin', 'admin', 'moderator'])) {
            $stmt = mysqli_prepare($con, "UPDATE admins SET role = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $role, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($roleId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE admins SET role_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $roleId, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($image !== null) {
            $stmt = mysqli_prepare($con, "UPDATE admins SET image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $image, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE admins SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }
        if (isset($body['permission_ids']) && is_array($body['permission_ids'])) {
            set_admin_permissions($con, $id, $body['permission_ids']);
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => trans('admin_user_updated_success'),
            'data' => adminGetAdmin($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteAdminUser')) {
    function adminDeleteAdminUser($con, $currentAdmin) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('admin_not_found')];

        if ($id === (int)($currentAdmin['id'] ?? 0)) {
            return ['status' => 'error', 'message' => trans('cannot_delete_self')];
        }

        $delPermStmt = mysqli_prepare($con, "DELETE FROM admin_permissions WHERE admin_id = ?");
        mysqli_stmt_bind_param($delPermStmt, "i", $id);
        mysqli_stmt_execute($delPermStmt);

        $stmt = mysqli_prepare($con, "DELETE FROM admins WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete admin user: ' . mysqli_error($con)];
        }
        return ['status' => 'success', 'message' => trans('admin_user_deleted_success')];
    }
}
