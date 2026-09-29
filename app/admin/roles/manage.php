<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/permissions_registry.php';

if (!function_exists('adminListRoles')) {
    function adminListRoles($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (r.code LIKE ? OR r.name LIKE ? OR rt.name LIKE ?)";
            $params = [$searchPattern, $searchPattern, $searchPattern];
            $types = "sss";
        }

        $countSql = "SELECT COUNT(DISTINCT r.id) AS total FROM roles r LEFT JOIN roles_translations rt ON r.id = rt.role_id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($whereClause)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "SELECT DISTINCT r.id, r.code, r.name, r.description, r.is_system, r.created_at FROM roles r LEFT JOIN roles_translations rt ON r.id = rt.role_id {$whereClause} ORDER BY r.id ASC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $roles = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rId = (int)$row['id'];
            $row['id'] = $rId;
            $row['is_system'] = (int)$row['is_system'];
            $row['translations'] = get_role_translations_all($con, $rId);

            // Fetch role permissions
            $pStmt = mysqli_prepare($con, "SELECT p.id, p.code, p.name, p.module, p.description FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.role_id = ?");
            mysqli_stmt_bind_param($pStmt, "i", $rId);
            mysqli_stmt_execute($pStmt);
            $pRes = mysqli_stmt_get_result($pStmt);

            $permissions = [];
            while ($pRow = mysqli_fetch_assoc($pRes)) {
                $pId = (int)$pRow['id'];
                $pRow['id'] = $pId;
                $pRow['translations'] = get_permission_translations_all($con, $pId);
                $permissions[] = $pRow;
            }
            $row['permissions'] = $permissions;

            $roles[] = $row;
        }

        return build_admin_paginated_response($roles, $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminGetRole')) {
    function adminGetRole($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('role_not_found')];

        $stmt = mysqli_prepare($con, "SELECT id, code, name, description, is_system, created_at FROM roles WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) return ['status' => 'error', 'message' => trans('role_not_found')];

        $rId = (int)$row['id'];
        $row['id'] = $rId;
        $row['is_system'] = (int)$row['is_system'];
        $row['translations'] = get_role_translations_all($con, $rId);

        $pStmt = mysqli_prepare($con, "SELECT p.id, p.code, p.name, p.module, p.description FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.role_id = ?");
        mysqli_stmt_bind_param($pStmt, "i", $rId);
        mysqli_stmt_execute($pStmt);
        $pRes = mysqli_stmt_get_result($pStmt);

        $permissions = [];
        while ($pRow = mysqli_fetch_assoc($pRes)) {
            $pId = (int)$pRow['id'];
            $pRow['id'] = $pId;
            $pRow['translations'] = get_permission_translations_all($con, $pId);
            $permissions[] = $pRow;
        }
        $row['permissions'] = $permissions;

        return ['status' => 'success', 'data' => $row];
    }
}

if (!function_exists('adminCreateRole')) {
    function adminCreateRole($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? $body['name'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);
        $permissionIds = $body['permission_ids'] ?? [];

        if (empty($code) || empty($nameAr)) {
            return ['status' => 'error', 'message' => 'Role code and name_ar are required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO roles (code, name, description, is_system) VALUES (?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, "sss", $code, $nameAr, $descAr);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create role: ' . mysqli_error($con)];
        }

        $roleId = mysqli_insert_id($con);

        // Insert multi-language translations (ar = language_id 1, en = language_id 2)
        $transStmt = mysqli_prepare($con, "INSERT INTO roles_translations (role_id, language_id, name, description) VALUES (?, 1, ?, ?), (?, 2, ?, ?)");
        mysqli_stmt_bind_param($transStmt, "isssiss", $roleId, $nameAr, $descAr, $roleId, $nameEn, $descEn);
        mysqli_stmt_execute($transStmt);

        if (is_array($permissionIds) && !empty($permissionIds)) {
            $pStmt = mysqli_prepare($con, "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_id=VALUES(role_id)");
            foreach ($permissionIds as $pid) {
                $pId = (int)$pid;
                if ($pId > 0) {
                    mysqli_stmt_bind_param($pStmt, "ii", $roleId, $pId);
                    mysqli_stmt_execute($pStmt);
                }
            }
        }

        $_GET['id'] = $roleId;
        return [
            'status' => 'success',
            'message' => trans('role_created_success'),
            'data' => adminGetRole($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateRole')) {
    function adminUpdateRole($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('role_not_found')];

        $nameAr = trim($body['name_ar'] ?? $body['name'] ?? '');
        $nameEn = trim($body['name_en'] ?? '');
        $code = strtolower(trim($body['code'] ?? ''));
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? '');

        if (!empty($nameAr)) {
            $stmt = mysqli_prepare($con, "UPDATE roles SET name = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $nameAr, $id);
            mysqli_stmt_execute($stmt);

            $transStmt = mysqli_prepare($con, "INSERT INTO roles_translations (role_id, language_id, name, description) VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
            mysqli_stmt_bind_param($transStmt, "iss", $id, $nameAr, $descAr);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($nameEn)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO roles_translations (role_id, language_id, name, description) VALUES (?, 2, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
            mysqli_stmt_bind_param($transStmt, "iss", $id, $nameEn, $descEn);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE roles SET code = ? WHERE id = ? AND is_system = 0");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if (isset($body['permission_ids']) && is_array($body['permission_ids'])) {
            mysqli_query($con, "DELETE FROM role_permissions WHERE role_id = {$id}");
            $pStmt = mysqli_prepare($con, "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_id=VALUES(role_id)");
            foreach ($body['permission_ids'] as $pid) {
                $pId = (int)$pid;
                if ($pId > 0) {
                    mysqli_stmt_bind_param($pStmt, "ii", $id, $pId);
                    mysqli_stmt_execute($pStmt);
                }
            }
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => trans('role_updated_success'),
            'data' => adminGetRole($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteRole')) {
    function adminDeleteRole($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('role_not_found')];

        $stmt = mysqli_prepare($con, "DELETE FROM roles WHERE id = ? AND is_system = 0");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete role or role is a system role: ' . mysqli_error($con)];
        }
        return ['status' => 'success', 'message' => trans('role_deleted_success')];
    }
}

if (!function_exists('adminListPermissions')) {
    function adminListPermissions($con) {
        // The catalogue in helpers/permissions_registry.php is the source of truth; this makes the
        // table match it. Cheap when nothing has changed - one SELECT of a stored hash - and it
        // runs here because this is the screen where being out of date actually matters.
        permissions_sync($con);

        $langInfo = get_language_info($con);
        $langId = $langInfo['id'];

        // Only what the dashboard actually has a screen for. Permissions from an earlier design
        // and ones guarding API routes with no page were being offered as checkboxes, which made
        // the list describe something other than the platform. Guarded on migration 082 so the
        // endpoint works before it is applied.
        $hasVisible = mysqli_num_rows(mysqli_query($con, "
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions' AND COLUMN_NAME = 'is_visible'
        ")) > 0;

        $visibleWhere = $hasVisible ? "WHERE p.is_visible = 1" : "WHERE p.code NOT LIKE '%.%'";

        $res = mysqli_query($con, "
            SELECT p.id, p.code, COALESCE(pt.name, p.name) AS name, p.module, COALESCE(pt.description, p.description) AS description
            FROM permissions p
            LEFT JOIN permissions_translations pt ON p.id = pt.permission_id AND pt.language_id = {$langId}
            {$visibleWhere}
            ORDER BY p.module ASC, p.id ASC
        ");

        $permissions = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $pId = (int)$row['id'];
            $row['id'] = $pId;
            $row['translations'] = get_permission_translations_all($con, $pId);
            $permissions[] = $row;
        }

        return [
            'status' => 'success',
            'data' => $permissions
        ];
    }
}
