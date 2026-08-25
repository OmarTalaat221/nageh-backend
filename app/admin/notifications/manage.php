<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/notifications.php';

if (!function_exists('adminSendNotification')) {
    function adminSendNotification($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();

        $targetType = strtolower(trim($body['target_type'] ?? ''));
        $title = trim($body['title'] ?? '');
        $message = trim($body['body'] ?? $body['message'] ?? '');
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        $allowedTargets = ['all_students', 'all_teachers', 'students', 'teachers'];
        if (!in_array($targetType, $allowedTargets, true)) {
            return ['status' => 'error', 'message' => 'target_type must be one of: ' . implode(', ', $allowedTargets)];
        }
        if (empty($title) || empty($message)) {
            return ['status' => 'error', 'message' => 'title and body are required'];
        }

        $userType = in_array($targetType, ['all_students', 'students']) ? 'student' : 'teacher';

        if ($targetType === 'all_students') {
            $ids = [];
            $res = mysqli_query($con, "SELECT id FROM students WHERE is_active = 1");
            while ($row = mysqli_fetch_assoc($res)) { $ids[] = (int)$row['id']; }
        } elseif ($targetType === 'all_teachers') {
            $ids = [];
            $res = mysqli_query($con, "SELECT id FROM teachers WHERE is_active = 1");
            while ($row = mysqli_fetch_assoc($res)) { $ids[] = (int)$row['id']; }
        } else {
            $rawIds = $body['student_ids'] ?? $body['teacher_ids'] ?? $body['ids'] ?? [];
            if (!is_array($rawIds) || empty($rawIds)) {
                return ['status' => 'error', 'message' => ($userType === 'student' ? 'student_ids' : 'teacher_ids') . ' array is required for this target_type'];
            }
            $ids = array_map('intval', $rawIds);
        }

        if (empty($ids)) {
            return ['status' => 'error', 'message' => 'No recipients found for this target'];
        }

        return send_notification_to_users($con, $userType, $ids, $title, $message, $data, 'admin', $adminId);
    }
}

if (!function_exists('adminListNotifications')) {
    function adminListNotifications($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM notifications"))['total'] ?? 0);

        $sql = "SELECT id, sender_type, sender_id, target_type, title, body, data, created_at FROM notifications ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['sender_id'] = $row['sender_id'] ? (int)$row['sender_id'] : null;
            $row['data'] = $row['data'] ? json_decode($row['data'], true) : null;

            $statsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(is_sent) AS sent FROM notification_recipients WHERE notification_id = {$row['id']}");
            $statsRow = mysqli_fetch_assoc($statsRes);
            $row['total_recipients'] = (int)($statsRow['total'] ?? 0);
            $row['sent_count'] = (int)($statsRow['sent'] ?? 0);

            $items[] = $row;
        }

        return build_admin_paginated_response($items, $page, $limit, $totalRecords);
    }
}
