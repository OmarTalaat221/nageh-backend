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

        $adminStmt = mysqli_prepare($con, "SELECT role FROM admins WHERE id = ? LIMIT 1");
        if ($adminStmt) {
            mysqli_stmt_bind_param($adminStmt, "i", $adminId);
            mysqli_stmt_execute($adminStmt);
            $adminRow = mysqli_fetch_assoc(mysqli_stmt_get_result($adminStmt));
            if ($adminRow && strtolower($adminRow['role'] ?? '') === 'super_admin') {
                return true;
            }
        }

        $stmt = mysqli_prepare($con, "
            SELECT p.id
            FROM permissions p
            JOIN admin_permissions ap ON p.id = ap.permission_id
            WHERE ap.admin_id = ? AND p.code = ? LIMIT 1
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

if (!function_exists('isSuperAdmin')) {
    /**
     * Whether this admin is a super admin.
     *
     * Not the same question as hasPermission(): that one answers true for a super admin AND for
     * anyone granted the permission, which is the right rule for almost everything. A few actions
     * are not grantable at all - editing what a paid subscription contains is one, because it
     * moves what a student has already paid for - and those need to ask specifically.
     *
     * The token is trusted only as a hint. The role is read from the database, so that revoking
     * somebody's super admin does not wait for their token to expire.
     */
    function isSuperAdmin($con, array $adminData): bool {
        $adminId = (int)($adminData['id'] ?? 0);
        if ($adminId <= 0) return false;

        $stmt = mysqli_prepare($con, "SELECT role FROM admins WHERE id = ? AND is_active = 1 LIMIT 1");
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "i", $adminId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        return $row && strtolower($row['role'] ?? '') === 'super_admin';
    }
}

if (!function_exists('requireSuperAdmin')) {
    function requireSuperAdmin($con, array $adminData): void {
        if (!isSuperAdmin($con, $adminData)) {
            json_response([
                'status' => 'error',
                'message' => trans('super_admin_only'),
                'error_code' => 'super_admin_only',
            ], 403);
        }
    }
}
