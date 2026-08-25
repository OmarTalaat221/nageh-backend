<?php
require_once __DIR__ . '/functions.php';

if (!function_exists('hasPermission')) {
    function hasPermission($con, array $adminData, string $permissionCode): bool {
        $role = strtolower($adminData['admin_role'] ?? $adminData['role'] ?? '');
        if ($role === 'super_admin') {
            return true;
        }

        $adminId = (int)($adminData['id'] ?? 0);
        if ($adminId <= 0) return false;

        $adminStmt = mysqli_prepare($con, "
            SELECT a.role, a.role_id, r.code AS role_code 
            FROM admins a 
            LEFT JOIN roles r ON a.role_id = r.id 
            WHERE a.id = ? LIMIT 1
        ");
        if ($adminStmt) {
            mysqli_stmt_bind_param($adminStmt, "i", $adminId);
            mysqli_stmt_execute($adminStmt);
            $adminRes = mysqli_stmt_get_result($adminStmt);
            $adminRow = mysqli_fetch_assoc($adminRes);
            if ($adminRow) {
                if (strtolower($adminRow['role'] ?? '') === 'super_admin' || strtolower($adminRow['role_code'] ?? '') === 'super_admin') {
                    return true;
                }
            }
        }

        $stmt = mysqli_prepare($con, "
            SELECT p.id 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN admins a ON a.role_id = rp.role_id
            WHERE a.id = ? AND p.code = ? LIMIT 1
        ");
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "is", $adminId, $permissionCode);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        return (bool)mysqli_fetch_assoc($res);
    }
}

if (!function_exists('requirePermission')) {
    function requirePermission($con, array $adminData, string $permissionCode): void {
        if (!hasPermission($con, $adminData, $permissionCode)) {
            json_response([
                'status' => 'error',
                'message' => 'Permission denied: You do not have access to perform this action (' . $permissionCode . ')'
            ], 403);
        }
    }
}
