<?php
require_once __DIR__ . '/functions.php';

/**
 * The platform's operational settings: who to contact, whether the doors are open, and how hard
 * it is to guess a password.
 *
 * These keys lived in `site_settings` for months without a single line reading them. The point of
 * this file is that a switch in the admin screen now does what it says - and that when a setting
 * is missing or unreadable, the platform keeps working rather than locking everyone out.
 */

if (!function_exists('app_settings')) {
    /**
     * Every operational key, with defaults chosen so that an empty or broken table means
     * "nothing is being enforced". A settings read must never be the reason the site goes down.
     */
    function app_settings($con): array {
        static $cached = null;
        if ($cached !== null) return $cached;

        $defaults = [
            'whatsapp_number' => '',
            'support_email' => '',
            'support_phone' => '',
            'facebook_link' => '',
            'instagram_link' => '',
            'youtube_link' => '',
            'tiktok_link' => '',
            'telegram_link' => '',
            'site_name_ar' => '',
            'site_name_en' => '',
            'maintenance_mode' => '0',
            'maintenance_message_ar' => 'المنصة تحت الصيانة حاليًا، برجاء المحاولة بعد قليل.',
            'maintenance_message_en' => 'The platform is under maintenance. Please try again shortly.',
            'registration_enabled' => '1',
            'registration_disabled_message_ar' => 'تسجيل الحسابات الجديدة متوقف حاليًا. برجاء التواصل مع الدعم الفني.',
            'registration_disabled_message_en' => 'New account registration is currently closed. Please contact support.',
            'login_lockout_enabled' => '0',
            'max_login_attempts' => '5',
            'login_lockout_minutes' => '15',
        ];

        // A blank contact field is a real answer - "no support number has been entered yet". A
        // blank switch or message is not; it is a row someone cleared by accident, and honouring
        // it would read as 0 and silently disable the thing the default exists to describe.
        $blankIsMeaningful = array_flip([
            'whatsapp_number', 'support_email', 'support_phone', 'facebook_link',
            'instagram_link', 'youtube_link', 'tiktok_link', 'telegram_link',
            'site_name_ar', 'site_name_en',
        ]);

        $inList = "'" . implode("','", array_map(function ($k) use ($con) {
            return mysqli_real_escape_string($con, $k);
        }, array_keys($defaults))) . "'";

        $res = @mysqli_query($con, "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ({$inList})");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $key = $row['setting_key'];
                $val = $row['setting_value'];

                if ($val === null) continue;
                if ($val === '' && !isset($blankIsMeaningful[$key])) continue;

                $defaults[$key] = (string)$val;
            }
        }

        $cached = $defaults;
        return $cached;
    }
}

if (!function_exists('app_setting_flag')) {
    function app_setting_flag($con, string $key, bool $default = false): bool {
        $settings = app_settings($con);
        if (!isset($settings[$key]) || $settings[$key] === '') return $default;
        return in_array(strtolower((string)$settings[$key]), ['1', 'true', 'yes', 'on'], true);
    }
}

/**
 * The localized message for a setting that has an `_ar` / `_en` pair.
 */
if (!function_exists('app_setting_message')) {
    function app_setting_message($con, string $baseKey, string $fallbackAr): string {
        $settings = app_settings($con);
        $lang = strtolower(substr((string)($_GET['lang'] ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'ar'), 0, 2));
        $key = $lang === 'en' ? $baseKey . '_en' : $baseKey . '_ar';
        $msg = trim((string)($settings[$key] ?? ''));
        if ($msg !== '') return $msg;
        return trim((string)($settings[$baseKey . '_ar'] ?? '')) ?: $fallbackAr;
    }
}

// =================================================================================================
// Maintenance mode
// =================================================================================================

/**
 * Whether this request should be turned away because the platform is in maintenance.
 *
 * Admin routes are never blocked. That is not a convenience - maintenance mode is switched off
 * through an admin route, so blocking those would make the setting a one-way door with no way
 * back except phpMyAdmin.
 *
 * The public config endpoint is also allowed through, so a client can read the maintenance
 * message it is about to display instead of guessing at a bare 503.
 */
if (!function_exists('maintenance_blocks_request')) {
    function maintenance_blocks_request($con, string $path): bool {
        if (!app_setting_flag($con, 'maintenance_mode', false)) return false;

        $allowedPrefixes = [
            '/najeh/admin/',          // the way back out
            '/najeh/app-config',      // so clients can read the message
            '/najeh/site-settings',   // the public website's own config
            '/najeh/about-us',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) return false;
        }

        return true;
    }
}

// =================================================================================================
// Registration
// =================================================================================================

/**
 * Returns an error array when new sign-ups are closed, or null when they are open.
 *
 * Deliberately not applied to teacher registration: a teacher account does not become usable
 * until an admin approves it, so it is already gated by a human and closing it here would only
 * be a second lock on the same door.
 */
if (!function_exists('registration_blocked_response')) {
    function registration_blocked_response($con): ?array {
        if (app_setting_flag($con, 'registration_enabled', true)) return null;

        return [
            'status' => 'error',
            'error_code' => 'registration_disabled',
            'message' => app_setting_message(
                $con,
                'registration_disabled_message',
                'تسجيل الحسابات الجديدة متوقف حاليًا. برجاء التواصل مع الدعم الفني.'
            ),
        ];
    }
}

// =================================================================================================
// Login lockout
// =================================================================================================

if (!function_exists('login_attempt_ip')) {
    function login_attempt_ip(): string {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45) ?: '0.0.0.0';
    }
}

/**
 * Called before the password is checked. Returns an error array when this (email, IP, role) is
 * currently locked out, or null to let the login proceed.
 *
 * The message names the number of minutes left rather than a timestamp, because a timestamp would
 * be in whatever timezone the request happened to carry and would be read wrong half the year.
 */
if (!function_exists('login_attempt_guard')) {
    function login_attempt_guard($con, string $email, string $role): ?array {
        if (!app_setting_flag($con, 'login_lockout_enabled', false)) return null;
        if ($email === '') return null;

        $ip = login_attempt_ip();
        $stmt = @mysqli_prepare($con, "SELECT locked_until FROM login_attempts WHERE identifier = ? AND ip_address = ? AND role = ? LIMIT 1");
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, "sss", $email, $ip, $role);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$row || empty($row['locked_until'])) return null;

        // Stored as UTC, compared as UTC - connection.php sets the session timezone from the
        // request, so anything else would drift by hours between one client and the next.
        $until = strtotime($row['locked_until'] . ' UTC');
        $now = time();
        if ($until === false || $until <= $now) return null;

        $minutes = max(1, (int)ceil(($until - $now) / 60));

        return [
            'status' => 'error',
            'error_code' => 'login_locked',
            'message' => "تم إيقاف المحاولات مؤقتًا بسبب محاولات دخول خاطئة متكررة. حاول مرة أخرى بعد {$minutes} دقيقة.",
            'retry_after_minutes' => $minutes,
        ];
    }
}

/**
 * Called after a password is rejected. Locks the (email, IP, role) once the ceiling is reached.
 */
if (!function_exists('login_attempt_fail')) {
    function login_attempt_fail($con, string $email, string $role): void {
        if (!app_setting_flag($con, 'login_lockout_enabled', false)) return;
        if ($email === '') return;

        $settings = app_settings($con);
        $max = max(1, (int)$settings['max_login_attempts']);
        $minutes = max(1, (int)$settings['login_lockout_minutes']);
        $ip = login_attempt_ip();

        // One statement so two simultaneous wrong passwords cannot both read "4" and both write
        // "5". `failures` is incremented in place, and the lock is set the moment it reaches the
        // ceiling, evaluated by the database against the value it just wrote.
        $stmt = @mysqli_prepare($con, "
            INSERT INTO login_attempts (identifier, ip_address, role, failures, last_failure_at)
            VALUES (?, ?, ?, 1, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
                failures = IF(locked_until IS NOT NULL AND locked_until <= UTC_TIMESTAMP(), 1, failures + 1),
                last_failure_at = UTC_TIMESTAMP(),
                locked_until = IF(
                    IF(locked_until IS NOT NULL AND locked_until <= UTC_TIMESTAMP(), 1, failures + 1) >= ?,
                    DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE),
                    NULL
                )
        ");
        if (!$stmt) return;

        mysqli_stmt_bind_param($stmt, "sssii", $email, $ip, $role, $max, $minutes);
        mysqli_stmt_execute($stmt);
    }
}

/**
 * Called after a successful login: the counter for this (email, IP, role) goes away entirely.
 */
if (!function_exists('login_attempt_reset')) {
    function login_attempt_reset($con, string $email, string $role): void {
        if ($email === '') return;

        $ip = login_attempt_ip();
        $stmt = @mysqli_prepare($con, "DELETE FROM login_attempts WHERE identifier = ? AND ip_address = ? AND role = ?");
        if (!$stmt) return;

        mysqli_stmt_bind_param($stmt, "sss", $email, $ip, $role);
        mysqli_stmt_execute($stmt);
    }
}

/**
 * Clears every lockout. The escape hatch for "I locked myself out testing this".
 */
if (!function_exists('login_attempt_clear_all')) {
    function login_attempt_clear_all($con): int {
        if (!@mysqli_query($con, "DELETE FROM login_attempts WHERE locked_until IS NOT NULL")) return 0;
        return mysqli_affected_rows($con);
    }
}

// =================================================================================================
// The public payload
// =================================================================================================

/**
 * What an unauthenticated client needs to know about the platform: who to contact, and whether
 * anything is closed right now.
 *
 * Flat and small on purpose. The student app used to read this from the landing-page endpoint,
 * which ships banners, ten courses and ten books - roughly 24KB to find one phone number - and
 * nested the settings one level deeper than the client looked, so it never found it at all.
 */
if (!function_exists('getPublicAppConfig')) {
    function getPublicAppConfig($con) {
        $s = app_settings($con);

        return [
            'status' => 'success',
            'data' => [
                'site_name_ar' => $s['site_name_ar'],
                'site_name_en' => $s['site_name_en'],
                'whatsapp_number' => preg_replace('/\D/', '', $s['whatsapp_number']),
                'support_email' => $s['support_email'],
                'support_phone' => $s['support_phone'],
                'social' => [
                    'facebook' => $s['facebook_link'],
                    'instagram' => $s['instagram_link'],
                    'youtube' => $s['youtube_link'],
                    'tiktok' => $s['tiktok_link'],
                    'telegram' => $s['telegram_link'],
                ],
                'maintenance_mode' => app_setting_flag($con, 'maintenance_mode', false),
                'maintenance_message' => app_setting_message($con, 'maintenance_message', 'المنصة تحت الصيانة حاليًا، برجاء المحاولة بعد قليل.'),
                'registration_enabled' => app_setting_flag($con, 'registration_enabled', true),
                'registration_disabled_message' => app_setting_message($con, 'registration_disabled_message', 'تسجيل الحسابات الجديدة متوقف حاليًا.'),
            ],
        ];
    }
}
