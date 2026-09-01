<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/notifications.php';

if (!function_exists('admin_notification_active_ids')) {
    function admin_notification_active_ids($con, string $table): array {
        $ids = [];
        $res = mysqli_query($con, "SELECT id FROM {$table} WHERE is_active = 1");
        while ($row = mysqli_fetch_assoc($res)) { $ids[] = (int)$row['id']; }
        return $ids;
    }
}

if (!function_exists('adminSendNotification')) {
    function adminSendNotification($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();

        $targetType = strtolower(trim($body['target_type'] ?? ''));
        $title = trim($body['title'] ?? '');
        $message = trim($body['body'] ?? $body['message'] ?? '');
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        $rawChannels = $body['channels'] ?? ['push'];
        if (!is_array($rawChannels)) { $rawChannels = [$rawChannels]; }
        $channels = array_values(array_intersect(array_map(fn($c) => strtolower(trim($c)), $rawChannels), ['push', 'email']));
        if (empty($channels)) { $channels = ['push']; }

        $allowedTargets = ['all_users', 'all_students', 'all_teachers', 'all_parents', 'students', 'teachers', 'parents'];
        if (!in_array($targetType, $allowedTargets, true)) {
            return ['status' => 'error', 'message' => 'target_type must be one of: ' . implode(', ', $allowedTargets)];
        }
        if (empty($title) || empty($message)) {
            return ['status' => 'error', 'message' => 'title and body are required'];
        }

        if ($targetType === 'all_users') {
            $targets = [
                'student' => admin_notification_active_ids($con, 'students'),
                'teacher' => admin_notification_active_ids($con, 'teachers'),
                'parent' => admin_notification_active_ids($con, 'parents'),
            ];
        } else {
            $userType = in_array($targetType, ['all_students', 'students']) ? 'student'
                : (in_array($targetType, ['all_teachers', 'teachers']) ? 'teacher' : 'parent');

            if ($targetType === 'all_students') {
                $ids = admin_notification_active_ids($con, 'students');
            } elseif ($targetType === 'all_teachers') {
                $ids = admin_notification_active_ids($con, 'teachers');
            } elseif ($targetType === 'all_parents') {
                $ids = admin_notification_active_ids($con, 'parents');
            } else {
                $rawIds = $body['student_ids'] ?? $body['teacher_ids'] ?? $body['parent_ids'] ?? $body['ids'] ?? [];
                if (!is_array($rawIds) || empty($rawIds)) {
                    return ['status' => 'error', 'message' => "{$userType}_ids array is required for this target_type"];
                }
                $ids = array_map('intval', $rawIds);
            }

            if (empty($ids)) {
                return ['status' => 'error', 'message' => 'No recipients found for this target'];
            }

            $targets = [$userType => $ids];
        }

        $summary = [
            'notification_ids' => [],
            'total_recipients' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'no_token_count' => 0,
            'email_sent_count' => 0,
            'email_failed_count' => 0,
            'email_skipped_count' => 0,
        ];

        $anySent = false;

        foreach ($targets as $userType => $ids) {
            if (empty($ids)) { continue; }

            $result = send_notification_to_users($con, $userType, $ids, $title, $message, $data, 'admin', $adminId, $channels);
            if (($result['status'] ?? 'error') !== 'success') {
                continue;
            }

            $anySent = true;
            $resultData = $result['data'] ?? [];
            $summary['notification_ids'][] = $resultData['notification_id'] ?? null;
            $summary['total_recipients'] += (int)($resultData['total_recipients'] ?? 0);
            $summary['sent_count'] += (int)($resultData['sent_count'] ?? 0);
            $summary['failed_count'] += (int)($resultData['failed_count'] ?? 0);
            $summary['no_token_count'] += (int)($resultData['no_token_count'] ?? 0);
            $summary['email_sent_count'] += (int)($resultData['email_sent_count'] ?? 0);
            $summary['email_failed_count'] += (int)($resultData['email_failed_count'] ?? 0);
            $summary['email_skipped_count'] += (int)($resultData['email_skipped_count'] ?? 0);
        }

        if (!$anySent) {
            return ['status' => 'error', 'message' => 'No recipients found for this target'];
        }

        return ['status' => 'success', 'message' => 'Notification processed', 'data' => $summary];
    }
}

if (!function_exists('adminListNotifications')) {
    function adminListNotifications($con) {
        ensure_notifications_tables($con);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM notifications"))['total'] ?? 0);

        $sql = "SELECT id, sender_type, sender_id, target_type, channels, title, body, data, created_at FROM notifications ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['sender_id'] = $row['sender_id'] ? (int)$row['sender_id'] : null;
            $row['data'] = $row['data'] ? json_decode($row['data'], true) : null;

            $statsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(is_sent) AS sent, SUM(is_email_sent) AS email_sent FROM notification_recipients WHERE notification_id = {$row['id']}");
            $statsRow = mysqli_fetch_assoc($statsRes);
            $row['total_recipients'] = (int)($statsRow['total'] ?? 0);
            $row['sent_count'] = (int)($statsRow['sent'] ?? 0);
            $row['email_sent_count'] = (int)($statsRow['email_sent'] ?? 0);

            $items[] = $row;
        }

        return build_admin_paginated_response($items, $page, $limit, $totalRecords);
    }
}
