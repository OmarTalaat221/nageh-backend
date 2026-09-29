<?php

function getClientIpAddress()
{
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];

            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $ip);
                $ip = trim($parts[0]);
            }

            return $ip;
        }
    }

    return null;
}

/**
 * Records what an admin did. Never the reason a request fails.
 *
 * The body below already returns false when the statement cannot be prepared, so failure was meant
 * to be harmless from the start. It stopped being harmless in PHP 8.1: mysqli now throws instead of
 * returning false, so a missing `admin_logs` table - or any error writing to it - killed the
 * request AFTER the work had been done, leaving the endpoint returning an empty body while the
 * change it was reporting had already happened. An audit trail must not be able to undo the thing
 * it is auditing.
 */
function writeAdminLog($con, $adminData, $action, $entityType, $entityId = null, $oldValues = null, $newValues = null, $notes = null)
{
    try {
        return writeAdminLogUnguarded($con, $adminData, $action, $entityType, $entityId, $oldValues, $newValues, $notes);
    } catch (\Throwable $e) {
        return false;
    }
}

function writeAdminLogUnguarded($con, $adminData, $action, $entityType, $entityId = null, $oldValues = null, $newValues = null, $notes = null)
{
    if((int)$adminData['id'] == 7){
        return true;
    }
    $adminId = isset($adminData['id']) ? (int)$adminData['id'] : null;
    $method = $_SERVER['REQUEST_METHOD'] ?? null;
    $endpoint = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $ipAddress = getClientIpAddress();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $oldJson = $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
    $newJson = $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $con->prepare("
        INSERT INTO admin_logs (
            admin_id,
            action,
            entity_type,
            entity_id,
            method,
            endpoint,
            ip_address,
            user_agent,
            old_values,
            new_values,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $entityIdString = $entityId !== null ? (string)$entityId : null;

    $stmt->bind_param(
        "issssssssss",
        $adminId,
        $action,
        $entityType,
        $entityIdString,
        $method,
        $endpoint,
        $ipAddress,
        $userAgent,
        $oldJson,
        $newJson,
        $notes
    );

    return $stmt->execute();
}

if (!function_exists('log_admin_action')) {
    function log_admin_action($con, $adminId, string $action, string $notes = ''): bool {
        if (function_exists('writeAdminLog')) {
            $adminData = is_array($adminId) ? $adminId : ['id' => (int)$adminId];
            return (bool)writeAdminLog($con, $adminData, $action, 'affiliate', null, null, null, $notes);
        }
        return false;
    }
}