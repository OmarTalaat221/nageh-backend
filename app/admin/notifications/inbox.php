<?php
// Admin's OWN notification inbox — the automatic system alerts admins get
// from admin_notifications.php (new student, course subscription, book
// purchase request, session started, consultation created), separate from
// manage.php which handles admin SENDING notifications to others.
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/notifications.php';

if (!function_exists('adminListMyNotifications')) {
    function adminListMyNotifications($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        if ($adminId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = ["nr.user_type = 'admin'", "nr.user_id = ?"];
        $params = [$adminId];
        $types = "i";

        if (isset($_GET['is_read']) && $_GET['is_read'] !== '') {
            $where[] = "nr.is_read = ?";
            $params[] = (int)$_GET['is_read'];
            $types .= "i";
        }

        $whereSql = implode(" AND ", $where);

        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM notification_recipients nr WHERE {$whereSql}");
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $unreadStmt = mysqli_prepare($con, "SELECT COUNT(*) AS unread FROM notification_recipients WHERE user_type = 'admin' AND user_id = ? AND is_read = 0");
        mysqli_stmt_bind_param($unreadStmt, "i", $adminId);
        mysqli_stmt_execute($unreadStmt);
        $unreadCount = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($unreadStmt))['unread'] ?? 0);

        $sql = "SELECT nr.id AS recipient_id, nr.notification_id, nr.is_sent, nr.is_read, nr.error, nr.created_at AS received_at, n.sender_type, n.sender_id, n.title, n.body, n.data, n.created_at FROM notification_recipients nr JOIN notifications n ON nr.notification_id = n.id WHERE {$whereSql} ORDER BY nr.id DESC LIMIT ? OFFSET ?";

        $selectParams = $params;
        $selectParams[] = $limit;
        $selectParams[] = $offset;
        $selectTypes = $types . "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $selectTypes, ...$selectParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['recipient_id'];
            $row['notification_id'] = (int)$row['notification_id'];
            $row['sender_id'] = $row['sender_id'] ? (int)$row['sender_id'] : null;
            $row['is_sent'] = (int)$row['is_sent'];
            $row['is_read'] = (int)$row['is_read'];
            $row['data'] = !empty($row['data']) ? json_decode($row['data'], true) : null;
            $items[] = $row;
        }

        $response = build_paginated_response($items, $page, $limit, $totalRecords);
        $response['unread_count'] = $unreadCount;
        return $response;
    }
}

if (!function_exists('adminMarkNotificationRead')) {
    function adminMarkNotificationRead($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        if ($adminId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $body = read_json_body();
        $notificationId = (int)($body['notification_id'] ?? $body['id'] ?? $_GET['id'] ?? 0);
        $markAll = !empty($body['all']) || !empty($body['mark_all']) || (isset($_GET['all']) && $_GET['all'] == '1');

        if ($markAll) {
            $stmt = mysqli_prepare($con, "UPDATE notification_recipients SET is_read = 1 WHERE user_type = 'admin' AND user_id = ? AND is_read = 0");
            mysqli_stmt_bind_param($stmt, "i", $adminId);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            return [
                'status' => 'success',
                'message' => trans('all_notifications_marked_read'),
                'updated_count' => max(0, $affected)
            ];
        }

        if ($notificationId <= 0) {
            return ['status' => 'error', 'message' => 'notification_id is required'];
        }

        $stmt = mysqli_prepare($con, "UPDATE notification_recipients SET is_read = 1 WHERE user_type = 'admin' AND user_id = ? AND (id = ? OR notification_id = ?)");
        mysqli_stmt_bind_param($stmt, "iii", $adminId, $notificationId, $notificationId);
        mysqli_stmt_execute($stmt);

        return [
            'status' => 'success',
            'message' => trans('notification_marked_read')
        ];
    }
}

if (!function_exists('adminGetNotificationUnreadCount')) {
    function adminGetNotificationUnreadCount($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        if ($adminId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $stmt = mysqli_prepare($con, "SELECT COUNT(*) AS unread FROM notification_recipients WHERE user_type = 'admin' AND user_id = ? AND is_read = 0");
        mysqli_stmt_bind_param($stmt, "i", $adminId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $unreadCount = (int)($row['unread'] ?? 0);

        return [
            'status' => 'success',
            'data' => [
                'unread_count' => $unreadCount
            ]
        ];
    }
}

if (!function_exists('adminDeleteMyNotification')) {
    function adminDeleteMyNotification($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        if ($adminId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $body = read_json_body();
        $id = (int)($body['id'] ?? $body['notification_id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'notification id is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM notification_recipients WHERE user_type = 'admin' AND user_id = ? AND (id = ? OR notification_id = ?)");
        mysqli_stmt_bind_param($stmt, "iii", $adminId, $id, $id);
        mysqli_stmt_execute($stmt);

        return [
            'status' => 'success',
            'message' => trans('notification_deleted_success')
        ];
    }
}
