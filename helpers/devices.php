<?php
require_once __DIR__ . '/functions.php';

/**
 * Device limiting.
 *
 * One set of credentials shared around a class is the cheapest way to give a paid course away.
 * Each sign-in registers the device it came from; once an account is at its ceiling, the next new
 * device is refused and told to contact support rather than quietly handed a second session.
 *
 * Two things make "1 device" mean one device rather than one login:
 *   - a returning device updates its own row instead of taking another slot, and
 *   - every authenticated request re-checks that the token's device is still active, so an admin
 *     reset takes effect immediately instead of waiting for the token to expire.
 */

if (!function_exists('device_settings')) {
    function device_settings($con): array {
        static $cached = null;
        if ($cached !== null) return $cached;

        $enabled = true;
        $maxDevices = 1;

        $res = mysqli_query($con, "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('max_devices_per_student', 'student_device_limit_enabled')");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                if ($row['setting_key'] === 'student_device_limit_enabled') {
                    $enabled = (string)$row['setting_value'] === '1';
                } elseif ($row['setting_key'] === 'max_devices_per_student') {
                    $maxDevices = max(1, (int)$row['setting_value']);
                }
            }
        }

        $cached = ['enabled' => $enabled, 'max_devices' => $maxDevices];
        return $cached;
    }
}

/**
 * The ceiling that applies to one student: their own override when set, otherwise the global one.
 */
if (!function_exists('device_limit_for_student')) {
    function device_limit_for_student($con, int $studentId): int {
        $settings = device_settings($con);
        $limit = $settings['max_devices'];

        $stmt = mysqli_prepare($con, "SELECT max_devices FROM students WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $studentId);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($row && $row['max_devices'] !== null && (int)$row['max_devices'] > 0) {
                $limit = (int)$row['max_devices'];
            }
        }

        return max(1, $limit);
    }
}

if (!function_exists('device_request_context')) {
    function device_request_context(array $body): array {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $headerDevice = $headers['X-Device-Id'] ?? $headers['x-device-id'] ?? $_SERVER['HTTP_X_DEVICE_ID'] ?? '';

        return [
            'device_id' => substr(trim((string)($body['device_id'] ?? $headerDevice)), 0, 191),
            'device_name' => substr(trim((string)($body['device_name'] ?? '')), 0, 255) ?: null,
            'platform' => substr(trim((string)($body['platform'] ?? '')), 0, 100) ?: null,
            'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000) ?: null,
            'ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
        ];
    }
}

/**
 * Decides whether this sign-in may proceed, and registers the device when it may.
 *
 * Returns ['allowed' => bool, 'code' => string, 'message' => string, 'device_id' => string|null].
 * `code` is what a client should branch on - the Arabic message is for display only:
 *   - `device_id_required`  the client sent no device id while enforcement is on
 *   - `device_limit_reached` this account is already at its ceiling
 */
if (!function_exists('device_register_login')) {
    function device_register_login($con, int $studentId, array $body, string $userType = 'student'): array {
        $settings = device_settings($con);
        $ctx = device_request_context($body);

        if (!$settings['enabled']) {
            // Still record what we can see, so turning the limit on later starts from a real
            // picture of who is signed in where rather than an empty table.
            if ($ctx['device_id'] !== '') {
                device_touch($con, $userType, $studentId, $ctx);
            }
            return ['allowed' => true, 'code' => 'ok', 'message' => '', 'device_id' => $ctx['device_id'] ?: null];
        }

        if ($ctx['device_id'] === '') {
            // Letting an unidentified client through would make the whole limit decorative: anyone
            // could drop the field and get unlimited sessions.
            return [
                'allowed' => false,
                'code' => 'device_id_required',
                'message' => 'هذا الإصدار من التطبيق لا يدعم تسجيل الأجهزة. يرجى تحديث التطبيق أو التواصل مع الدعم الفني.',
                'device_id' => null,
            ];
        }

        $existing = device_find($con, $userType, $studentId, $ctx['device_id']);

        if ($existing && (int)$existing['is_active'] === 1) {
            device_touch($con, $userType, $studentId, $ctx);
            return ['allowed' => true, 'code' => 'ok', 'message' => '', 'device_id' => $ctx['device_id']];
        }

        // A revoked device coming back is treated as a brand new one: the admin took its slot
        // away, so it has to fit inside the ceiling again to get one.
        $limit = device_limit_for_student($con, $studentId);
        $active = device_active_count($con, $userType, $studentId);

        if ($active >= $limit) {
            return [
                'allowed' => false,
                'code' => 'device_limit_reached',
                'message' => $limit === 1
                    ? 'حسابك مفعّل على جهاز آخر بالفعل. للدخول من هذا الجهاز يرجى التواصل مع الدعم الفني.'
                    : "حسابك وصل للحد الأقصى ({$limit}) من الأجهزة المسموح بها. للدخول من هذا الجهاز يرجى التواصل مع الدعم الفني.",
                'device_id' => $ctx['device_id'],
                'max_devices' => $limit,
                'active_devices' => $active,
            ];
        }

        device_touch($con, $userType, $studentId, $ctx);
        return ['allowed' => true, 'code' => 'ok', 'message' => '', 'device_id' => $ctx['device_id']];
    }
}

if (!function_exists('device_find')) {
    function device_find($con, string $userType, int $userId, string $deviceId): ?array {
        $stmt = mysqli_prepare($con, "SELECT * FROM user_devices WHERE user_type = ? AND user_id = ? AND device_id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "sis", $userType, $userId, $deviceId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }
}

if (!function_exists('device_active_count')) {
    function device_active_count($con, string $userType, int $userId): int {
        $stmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM user_devices WHERE user_type = ? AND user_id = ? AND is_active = 1");
        if (!$stmt) return 0;
        mysqli_stmt_bind_param($stmt, "si", $userType, $userId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return (int)($row['total'] ?? 0);
    }
}

/**
 * Inserts the device or refreshes what we know about it. Re-activates a revoked row, which is
 * correct only because every caller has already checked the ceiling.
 */
if (!function_exists('device_touch')) {
    function device_touch($con, string $userType, int $userId, array $ctx): void {
        $stmt = mysqli_prepare($con, "
            INSERT INTO user_devices (user_type, user_id, device_id, device_name, platform, user_agent, ip_address, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                device_name = COALESCE(VALUES(device_name), device_name),
                platform = COALESCE(VALUES(platform), platform),
                user_agent = VALUES(user_agent),
                ip_address = VALUES(ip_address),
                is_active = 1,
                revoked_at = NULL,
                revoked_by_admin_id = NULL,
                last_seen_at = CURRENT_TIMESTAMP
        ");
        if (!$stmt) return;
        mysqli_stmt_bind_param(
            $stmt, "sisssss",
            $userType, $userId, $ctx['device_id'], $ctx['device_name'],
            $ctx['platform'], $ctx['user_agent'], $ctx['ip_address']
        );
        mysqli_stmt_execute($stmt);
    }
}

/**
 * Called on every authenticated request that carries a device id in its token. Returns false when
 * that device has been revoked, which is what makes an admin reset log the student out at once
 * instead of at token expiry.
 */
if (!function_exists('device_is_authorized')) {
    function device_is_authorized($con, string $userType, int $userId, string $deviceId): bool {
        if ($deviceId === '') return true;

        $settings = device_settings($con);
        if (!$settings['enabled']) return true;

        $stmt = mysqli_prepare($con, "SELECT is_active FROM user_devices WHERE user_type = ? AND user_id = ? AND device_id = ? LIMIT 1");
        if (!$stmt) return true;
        mysqli_stmt_bind_param($stmt, "sis", $userType, $userId, $deviceId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        // A token whose device row is gone entirely belongs to a session from before the device
        // was registered - it is revoked as far as this check is concerned.
        return $row !== null && (int)$row['is_active'] === 1;
    }
}

if (!function_exists('device_list')) {
    function device_list($con, string $userType, int $userId, bool $activeOnly = false): array {
        $sql = "SELECT * FROM user_devices WHERE user_type = ? AND user_id = ?";
        if ($activeOnly) $sql .= " AND is_active = 1";
        $sql .= " ORDER BY is_active DESC, last_seen_at DESC";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "si", $userType, $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['user_id'] = (int)$row['user_id'];
            $row['is_active'] = (int)$row['is_active'];
            $row['revoked_by_admin_id'] = $row['revoked_by_admin_id'] ? (int)$row['revoked_by_admin_id'] : null;
            $rows[] = $row;
        }
        return $rows;
    }
}

/**
 * Revokes devices. Rows are kept rather than deleted - who was signed in from where is exactly
 * the history someone will want when a shared-account complaint comes back a second time.
 */
if (!function_exists('device_revoke')) {
    function device_revoke($con, string $userType, int $userId, ?string $deviceId, ?int $adminId): int {
        $sql = "UPDATE user_devices SET is_active = 0, revoked_at = NOW(), revoked_by_admin_id = ? WHERE user_type = ? AND user_id = ? AND is_active = 1";
        if ($deviceId !== null) $sql .= " AND device_id = ?";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return 0;

        if ($deviceId !== null) {
            mysqli_stmt_bind_param($stmt, "isis", $adminId, $userType, $userId, $deviceId);
        } else {
            mysqli_stmt_bind_param($stmt, "isi", $adminId, $userType, $userId);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt);
    }
}
