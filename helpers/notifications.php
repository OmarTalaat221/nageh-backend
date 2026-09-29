<?php
require_once __DIR__ . '/firebase.php';
require_once __DIR__ . '/mailer.php';

if (!defined('FIREBASE_SERVICE_ACCOUNT_PATH')) {
    define('FIREBASE_SERVICE_ACCOUNT_PATH', __DIR__ . '/../config/najeh-e3ef7-firebase-adminsdk-fbsvc-fd9759abc6.json');
}

/**
 * Cached per-request so sending to many users only does one OAuth token exchange.
 */
if (!function_exists('get_cached_firebase_access')) {
    function get_cached_firebase_access() {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $result = getFirebaseAccessToken(FIREBASE_SERVICE_ACCOUNT_PATH);
        $cached = $result;
        return $cached;
    }
}

if (!function_exists('notification_user_table')) {
    function notification_user_table(string $userType): ?string {
        $map = ['student' => 'students', 'teacher' => 'teachers', 'parent' => 'parents', 'admin' => 'admins'];
        return $map[$userType] ?? null;
    }
}

if (!function_exists('save_user_fcm_token')) {
    function save_user_fcm_token($con, string $userType, int $userId, string $token): array {
        $table = notification_user_table($userType);
        if (!$table || $userId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid user'];
        }
        $token = trim($token);
        if ($token === '') {
            return ['status' => 'error', 'message' => 'fcm_token is required'];
        }

        $stmt = mysqli_prepare($con, "UPDATE {$table} SET fcm_token = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $token, $userId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to save token: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'FCM token saved successfully'];
    }
}

if (!function_exists('get_user_fcm_token')) {
    function get_user_fcm_token($con, string $userType, int $userId): ?string {
        $table = notification_user_table($userType);
        if (!$table) return null;

        $stmt = mysqli_prepare($con, "SELECT fcm_token FROM {$table} WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return !empty($row['fcm_token']) ? $row['fcm_token'] : null;
    }
}

if (!function_exists('get_user_contact')) {
    function get_user_contact($con, string $userType, int $userId): ?array {
        $table = notification_user_table($userType);
        if (!$table) return null;

        $stmt = mysqli_prepare($con, "SELECT name, email FROM {$table} WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }
}

/**
 * Sends a push notification to a list of user ids of the same type, and logs the notification
 * plus a per-recipient row (used for admin history and could back an in-app inbox later).
 */
if (!function_exists('send_notification_to_users')) {
    function send_notification_to_users($con, string $userType, array $userIds, string $title, string $body, array $data = [], string $senderType = 'admin', ?int $senderId = null, array $channels = ['push']): array {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($id) => $id > 0)));
        if (empty($userIds)) {
            return ['status' => 'error', 'message' => 'No recipients found'];
        }

        $dataJson = !empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
        return _send_notification_to_users_impl($con, $userType, $userIds, $title, $body, $data, $senderType, $senderId, $dataJson, $channels);
    }
}

if (!function_exists('notification_try')) {
    /**
     * Run a statement whose failure is expected and harmless.
     *
     * These used to be written `@mysqli_query(...)`, which is safe only while mysqli reports
     * errors as warnings. From PHP 8.1 mysqli throws by default, `@` does not suppress an
     * exception, and every one of these ALTERs throws the moment the column it adds already
     * exists - that is, on the second request onwards. The whole request then dies, so the
     * notification is never written AND the endpoint that was sending it returns nothing at all.
     */
    function notification_try($con, string $sql): bool {
        try {
            return (bool)mysqli_query($con, $sql);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Adds a column only when it is actually missing, so the common path runs no failing DDL.
     */
    function notification_add_column($con, string $table, string $column, string $sql): void {
        try {
            $res = mysqli_query($con, "SELECT COUNT(*) AS n FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '" . mysqli_real_escape_string($con, $table) . "'
                  AND COLUMN_NAME = '" . mysqli_real_escape_string($con, $column) . "'");
            $exists = $res && (int)(mysqli_fetch_assoc($res)['n'] ?? 0) > 0;
            if ($exists) return;
        } catch (\Throwable $e) {
            // Could not look it up; fall through and let the guarded ALTER decide.
        }

        notification_try($con, $sql);
    }
}

if (!function_exists('ensure_notifications_tables')) {
    function ensure_notifications_tables($con): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS `notifications` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `sender_type` VARCHAR(50) NOT NULL DEFAULT 'admin',
            `sender_id` BIGINT UNSIGNED NULL,
            `target_type` VARCHAR(50) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `body` TEXT NOT NULL,
            `data` TEXT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        notification_try($con, "ALTER TABLE `notifications` MODIFY COLUMN `target_type` VARCHAR(50) NOT NULL");
        notification_try($con, "ALTER TABLE `notifications` MODIFY COLUMN `sender_type` VARCHAR(50) NOT NULL DEFAULT 'admin'");
        notification_add_column($con, 'notifications', 'channels', "ALTER TABLE `notifications` ADD COLUMN `channels` VARCHAR(50) NOT NULL DEFAULT 'push' AFTER `target_type`");

        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS `notification_recipients` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `notification_id` BIGINT UNSIGNED NOT NULL,
            `user_type` VARCHAR(50) NOT NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `is_sent` TINYINT(1) NOT NULL DEFAULT 0,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `error` VARCHAR(500) NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_nr_notification` (`notification_id`),
            KEY `idx_nr_user` (`user_type`, `user_id`),
            CONSTRAINT `fk_nr_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        notification_try($con, "ALTER TABLE `notification_recipients` MODIFY COLUMN `user_type` VARCHAR(50) NOT NULL");
        notification_add_column($con, 'notification_recipients', 'is_email_sent', "ALTER TABLE `notification_recipients` ADD COLUMN `is_email_sent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_sent`");
        notification_add_column($con, 'notification_recipients', 'email_error', "ALTER TABLE `notification_recipients` ADD COLUMN `email_error` VARCHAR(500) NULL AFTER `error`");
    }
}

if (!function_exists('get_notification_click_link')) {
    function get_notification_click_link(string $userType): string {
        $links = [
            'student' => 'https://nageh-dash-student.vercel.app/notifications',
            'parent'  => 'https://nageh-dash-student.vercel.app/parent/dashboard',
            'teacher' => 'https://najeh-teacher.vercel.app/notifications',
        ];
        return $links[$userType] ?? '';
    }
}

if (!function_exists('_send_notification_to_users_impl')) {
    function _send_notification_to_users_impl($con, string $userType, array $userIds, string $title, string $body, array $data, string $senderType, ?int $senderId, ?string $dataJson, array $channels = ['push']): array {
        ensure_notifications_tables($con);

        $wantsPush = in_array('push', $channels, true);
        $wantsEmail = in_array('email', $channels, true);
        $channelsLabel = implode(',', array_values(array_unique($channels))) ?: 'push';

        $data['channels'] = $channelsLabel;
        $data['target_type'] = $userType;

        $stmt = mysqli_prepare($con, "INSERT INTO notifications (sender_type, sender_id, target_type, channels, title, body, data) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sisssss", $senderType, $senderId, $userType, $channelsLabel, $title, $body, $dataJson);
        mysqli_stmt_execute($stmt);
        $notificationId = mysqli_insert_id($con);

        $access = $wantsPush ? get_cached_firebase_access() : null;
        $canPush = $wantsPush && ($access['status'] ?? 'error') === 'success';

        $sentCount = 0;
        $failedCount = 0;
        $noTokenCount = 0;
        $emailSentCount = 0;
        $emailFailedCount = 0;
        $emailSkippedCount = 0;

        $recStmt = mysqli_prepare($con, "INSERT INTO notification_recipients (notification_id, user_type, user_id, is_sent, error, is_email_sent, email_error) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($userIds as $userId) {
            $isSent = 0;
            $error = null;

            if ($wantsPush) {
                $token = get_user_fcm_token($con, $userType, $userId);

                if (empty($token)) {
                    $noTokenCount++;
                    $error = 'No FCM token registered';
                } elseif (!$canPush) {
                    $failedCount++;
                    $error = $access['message'] ?? 'Failed to obtain Firebase access token';
                } else {
                    $result = sendFirebaseNotification($access['access_token'], $access['project_id'], $token, $title, $body, $data, "", get_notification_click_link($userType));
                    if (($result['status'] ?? 'error') === 'success') {
                        $isSent = 1;
                        $sentCount++;
                    } else {
                        $failedCount++;
                        $error = $result['message'] ?? 'Failed to send';
                    }
                }
            }

            $isEmailSent = 0;
            $emailError = null;

            if ($wantsEmail) {
                $contact = get_user_contact($con, $userType, $userId);
                $email = $contact['email'] ?? null;

                if (empty($email)) {
                    $emailSkippedCount++;
                    $emailError = 'No email on file';
                } else {
                    $htmlBody = '<div style="font-family:Tahoma,Arial,sans-serif;line-height:1.8;direction:rtl;text-align:right">'
                        . '<h2 style="margin:0 0 12px">' . htmlspecialchars($title) . '</h2>'
                        . '<p style="margin:0;white-space:pre-wrap">' . nl2br(htmlspecialchars($body)) . '</p>'
                        . '</div>';

                    if (send_smtp_email($email, $title, $htmlBody)) {
                        $isEmailSent = 1;
                        $emailSentCount++;
                    } else {
                        $emailFailedCount++;
                        $emailError = 'Failed to send email';
                    }
                }
            }

            mysqli_stmt_bind_param($recStmt, "isiisis", $notificationId, $userType, $userId, $isSent, $error, $isEmailSent, $emailError);
            mysqli_stmt_execute($recStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Notification processed',
            'data' => [
                'notification_id' => (int)$notificationId,
                'total_recipients' => count($userIds),
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'no_token_count' => $noTokenCount,
                'email_sent_count' => $emailSentCount,
                'email_failed_count' => $emailFailedCount,
                'email_skipped_count' => $emailSkippedCount
            ]
        ];
    }
}

if (!function_exists('send_notification_to_user')) {
    function send_notification_to_user($con, string $userType, int $userId, string $title, string $body, array $data = [], string $senderType = 'system', ?int $senderId = null, array $channels = ['push']): array {
        return send_notification_to_users($con, $userType, [$userId], $title, $body, $data, $senderType, $senderId, $channels);
    }
}

/**
 * Active student ids enrolled (via active, non-expired subscriptions) in the given course
 * and/or subject and/or teacher - used to notify everyone attached to a class session or
 * a teacher's consultation slot. $teacherId also covers students who only hold a
 * teacher-wide subscription (student_subscription_teachers) with no course/subject rows.
 */
if (!function_exists('get_active_student_ids_for_session')) {
    function get_active_student_ids_for_session($con, ?int $courseId, ?int $subjectId, ?int $teacherId = null): array {
        $ids = [];
        
        if (!empty($courseId)) {
            $res = mysqli_query($con, "
                SELECT DISTINCT ss.student_id FROM student_subscription_courses ssc
                JOIN student_subscriptions ss ON ssc.student_subscription_id = ss.id
                WHERE ssc.course_id = {$courseId} AND ss.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= NOW())
            ");
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $ids[] = (int)$row['student_id'];
                }
            }
        }
        if (!empty($subjectId)) {
            $res = mysqli_query($con, "
                SELECT DISTINCT ss.student_id FROM student_subscription_subjects sss
                JOIN student_subscriptions ss ON sss.student_subscription_id = ss.id
                WHERE sss.subject_id = {$subjectId} AND ss.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= NOW())
            ");
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $ids[] = (int)$row['student_id'];
                }
            }
        }
        if (!empty($teacherId)) {
            $res = mysqli_query($con, "
                SELECT DISTINCT ss.student_id FROM student_subscription_teachers sst
                JOIN student_subscriptions ss ON sst.student_subscription_id = ss.id
                WHERE sst.teacher_id = {$teacherId} AND ss.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= NOW())
            ");
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $ids[] = (int)$row['student_id'];
                }
            }
        }
        return array_values(array_unique($ids));
    }
}

if (!function_exists('get_admin_ids')) {
    function get_admin_ids($con): array {
        $ids = [];
        $res = mysqli_query($con, "SELECT id FROM admins WHERE is_active = 1");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $ids[] = (int)$row['id'];
            }
        }
        return $ids;
    }
}

if (!function_exists('ensure_class_session_notification_log_table')) {
    function ensure_class_session_notification_log_table($con): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS `class_session_notification_log` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `session_id` BIGINT UNSIGNED NOT NULL,
            `notify_type` VARCHAR(50) NOT NULL,
            `session_date` DATE NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_session_notify` (`session_id`, `notify_type`, `session_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        @mysqli_query($con, "ALTER TABLE `class_session_notification_log` MODIFY COLUMN `notify_type` VARCHAR(50) NOT NULL");
    }
}

if (!function_exists('mark_session_notification_once')) {
    function mark_session_notification_once($con, int $sessionId, string $notifyType, string $sessionDate): bool {
        ensure_class_session_notification_log_table($con);
        $stmt = @mysqli_prepare($con, "INSERT IGNORE INTO class_session_notification_log (session_id, notify_type, session_date) VALUES (?, ?, ?)");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "iss", $sessionId, $notifyType, $sessionDate);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt) > 0;
    }
}
