<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/notifications.php';

if (!function_exists('parentListNotifications')) {
    function parentListNotifications($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $cRes = mysqli_query($con, "SELECT student_id FROM parent_students WHERE parent_id = {$parentId} AND is_active = 1");
        $childrenIds = [];
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $childrenIds[] = (int)$cRow['student_id'];
            }
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $userConds = ["(nr.user_type = 'parent' AND nr.user_id = {$parentId})"];
        if (!empty($childrenIds)) {
            $cListStr = implode(',', $childrenIds);
            $userConds[] = "(nr.user_type = 'student' AND nr.user_id IN ({$cListStr}))";
        }
        $userSql = "(" . implode(" OR ", $userConds) . ")";

        $where = [$userSql];
        if (isset($_GET['is_read']) && $_GET['is_read'] !== '') {
            $isRead = (int)$_GET['is_read'];
            $where[] = "nr.is_read = {$isRead}";
        }

        $whereSql = implode(" AND ", $where);

        $totalRecords = 0;
        $tRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM notification_recipients nr WHERE {$whereSql}");
        if ($tRes && ($tRow = mysqli_fetch_assoc($tRes))) {
            $totalRecords = (int)($tRow['total'] ?? 0);
        }

        $unreadCount = 0;
        $uRes = @mysqli_query($con, "SELECT COUNT(*) AS unread FROM notification_recipients nr WHERE {$userSql} AND nr.is_read = 0");
        if ($uRes && ($uRow = mysqli_fetch_assoc($uRes))) {
            $unreadCount = (int)($uRow['unread'] ?? 0);
        }

        $sql = "SELECT nr.id AS recipient_id, nr.notification_id, nr.user_type, nr.user_id, nr.is_read, nr.created_at AS received_at, n.sender_type, n.sender_id, n.title, n.body, n.data, n.created_at FROM notification_recipients nr JOIN notifications n ON nr.notification_id = n.id WHERE {$whereSql} ORDER BY nr.id DESC LIMIT {$limit} OFFSET {$offset}";

        $res = mysqli_query($con, $sql);
        $items = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['recipient_id'];
                $row['notification_id'] = (int)$row['notification_id'];
                $row['sender_id'] = $row['sender_id'] ? (int)$row['sender_id'] : null;
                $row['user_id'] = (int)$row['user_id'];
                $row['is_read'] = (int)$row['is_read'];
                $row['is_for_child'] = $row['user_type'] === 'student';
                $row['data'] = !empty($row['data']) ? json_decode($row['data'], true) : null;
                $items[] = $row;
            }
        }

        $response = build_paginated_response($items, $page, $limit, $totalRecords);
        $response['unread_count'] = $unreadCount;
        return $response;
    }
}

if (!function_exists('parentMarkNotificationRead')) {
    function parentMarkNotificationRead($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $cRes = mysqli_query($con, "SELECT student_id FROM parent_students WHERE parent_id = {$parentId} AND is_active = 1");
        $childrenIds = [];
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $childrenIds[] = (int)$cRow['student_id'];
            }
        }

        $userConds = ["(user_type = 'parent' AND user_id = {$parentId})"];
        if (!empty($childrenIds)) {
            $cListStr = implode(',', $childrenIds);
            $userConds[] = "(user_type = 'student' AND user_id IN ({$cListStr}))";
        }
        $userSql = "(" . implode(" OR ", $userConds) . ")";

        $body = read_json_body();
        $notificationId = (int)($body['notification_id'] ?? $body['id'] ?? $_GET['id'] ?? 0);
        $markAll = !empty($body['all']) || !empty($body['mark_all']) || (isset($_GET['all']) && $_GET['all'] == '1');

        if ($markAll) {
            mysqli_query($con, "UPDATE notification_recipients SET is_read = 1 WHERE {$userSql} AND is_read = 0");
            $affected = mysqli_affected_rows($con);
            return [
                'status' => 'success',
                'message' => trans('all_notifications_marked_read'),
                'updated_count' => max(0, $affected)
            ];
        }

        if ($notificationId <= 0) {
            return ['status' => 'error', 'message' => 'notification_id is required'];
        }

        mysqli_query($con, "UPDATE notification_recipients SET is_read = 1 WHERE {$userSql} AND (id = {$notificationId} OR notification_id = {$notificationId})");

        return [
            'status' => 'success',
            'message' => trans('notification_marked_read')
        ];
    }
}

if (!function_exists('parentGetNotificationUnreadCount')) {
    function parentGetNotificationUnreadCount($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $cRes = mysqli_query($con, "SELECT student_id FROM parent_students WHERE parent_id = {$parentId} AND is_active = 1");
        $childrenIds = [];
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $childrenIds[] = (int)$cRow['student_id'];
            }
        }

        $userConds = ["(user_type = 'parent' AND user_id = {$parentId})"];
        if (!empty($childrenIds)) {
            $cListStr = implode(',', $childrenIds);
            $userConds[] = "(user_type = 'student' AND user_id IN ({$cListStr}))";
        }
        $userSql = "(" . implode(" OR ", $userConds) . ")";

        $unreadCount = 0;
        $uRes = @mysqli_query($con, "SELECT COUNT(*) AS unread FROM notification_recipients WHERE {$userSql} AND is_read = 0");
        if ($uRes && ($uRow = mysqli_fetch_assoc($uRes))) {
            $unreadCount = (int)($uRow['unread'] ?? 0);
        }

        return [
            'status' => 'success',
            'data' => [
                'unread_count' => $unreadCount
            ]
        ];
    }
}

if (!function_exists('parentDeleteNotification')) {
    function parentDeleteNotification($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $cRes = mysqli_query($con, "SELECT student_id FROM parent_students WHERE parent_id = {$parentId} AND is_active = 1");
        $childrenIds = [];
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $childrenIds[] = (int)$cRow['student_id'];
            }
        }

        $userConds = ["(user_type = 'parent' AND user_id = {$parentId})"];
        if (!empty($childrenIds)) {
            $cListStr = implode(',', $childrenIds);
            $userConds[] = "(user_type = 'student' AND user_id IN ({$cListStr}))";
        }
        $userSql = "(" . implode(" OR ", $userConds) . ")";

        $body = read_json_body();
        $id = (int)($body['id'] ?? $body['notification_id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'notification id is required'];
        }

        mysqli_query($con, "DELETE FROM notification_recipients WHERE {$userSql} AND (id = {$id} OR notification_id = {$id})");

        return [
            'status' => 'success',
            'message' => trans('notification_deleted_success')
        ];
    }
}
