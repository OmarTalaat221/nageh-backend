<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/notifications.php';

if (!function_exists('studentListNotifications')) {
    function studentListNotifications($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($studentId <= 0) {
            $studentId = (int)($_GET['student_id'] ?? 0);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        if ($studentId <= 0) {
            $response = build_paginated_response([], $page, $limit, 0);
            $response['unread_count'] = 0;
            return $response;
        }

        ensure_notifications_tables($con);

        $where = ["nr.user_type = 'student'", "nr.user_id = ?"];
        $params = [$studentId];
        $types = "i";

        if (isset($_GET['is_read']) && $_GET['is_read'] !== '') {
            $where[] = "nr.is_read = ?";
            $params[] = (int)$_GET['is_read'];
            $types .= "i";
        }

        $whereSql = implode(" AND ", $where);

        $totalRecords = 0;
        $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM notification_recipients nr WHERE {$whereSql}");
        if ($countStmt) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $cRes = mysqli_stmt_get_result($countStmt);
            if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
                $totalRecords = (int)($cRow['total'] ?? 0);
            }
        }

        $unreadCount = 0;
        $unreadStmt = mysqli_prepare($con, "SELECT COUNT(*) AS unread FROM notification_recipients WHERE user_type = 'student' AND user_id = ? AND is_read = 0");
        if ($unreadStmt) {
            mysqli_stmt_bind_param($unreadStmt, "i", $studentId);
            mysqli_stmt_execute($unreadStmt);
            $uRes = mysqli_stmt_get_result($unreadStmt);
            if ($uRes && ($uRow = mysqli_fetch_assoc($uRes))) {
                $unreadCount = (int)($uRow['unread'] ?? 0);
            }
        }

        $sql = "SELECT nr.id AS recipient_id, nr.notification_id, nr.is_read, nr.created_at AS received_at, n.sender_type, n.sender_id, n.title, n.body, n.data, n.created_at FROM notification_recipients nr JOIN notifications n ON nr.notification_id = n.id WHERE {$whereSql} ORDER BY nr.id DESC LIMIT ? OFFSET ?";

        $selectParams = $params;
        $selectParams[] = $limit;
        $selectParams[] = $offset;
        $selectTypes = $types . "ii";

        $items = [];
        $stmt = mysqli_prepare($con, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $selectTypes, ...$selectParams);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $row['id'] = (int)$row['recipient_id'];
                    $row['notification_id'] = (int)$row['notification_id'];
                    $row['sender_id'] = $row['sender_id'] ? (int)$row['sender_id'] : null;
                    $row['is_read'] = (int)$row['is_read'];
                    $row['data'] = !empty($row['data']) ? json_decode($row['data'], true) : null;
                    $items[] = $row;
                }
            }
        }

        $response = build_paginated_response($items, $page, $limit, $totalRecords);
        $response['unread_count'] = $unreadCount;
        return $response;
    }
}

if (!function_exists('studentMarkNotificationRead')) {
    function studentMarkNotificationRead($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $body = read_json_body();
        $notificationId = (int)($body['notification_id'] ?? $body['id'] ?? $_GET['id'] ?? 0);
        $markAll = !empty($body['all']) || !empty($body['mark_all']) || (isset($_GET['all']) && $_GET['all'] == '1');

        if ($markAll) {
            $stmt = mysqli_prepare($con, "UPDATE notification_recipients SET is_read = 1 WHERE user_type = 'student' AND user_id = ? AND is_read = 0");
            mysqli_stmt_bind_param($stmt, "i", $studentId);
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

        $stmt = mysqli_prepare($con, "UPDATE notification_recipients SET is_read = 1 WHERE user_type = 'student' AND user_id = ? AND (id = ? OR notification_id = ?)");
        mysqli_stmt_bind_param($stmt, "iii", $studentId, $notificationId, $notificationId);
        mysqli_stmt_execute($stmt);

        return [
            'status' => 'success',
            'message' => trans('notification_marked_read')
        ];
    }
}

if (!function_exists('studentGetNotificationUnreadCount')) {
    function studentGetNotificationUnreadCount($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($studentId <= 0) {
            $studentId = (int)($_GET['student_id'] ?? 0);
        }
        if ($studentId <= 0) {
            return [
                'status' => 'success',
                'data' => [
                    'unread_count' => 0
                ]
            ];
        }

        ensure_notifications_tables($con);

        $unreadCount = 0;
        $stmt = mysqli_prepare($con, "SELECT COUNT(*) AS unread FROM notification_recipients WHERE user_type = 'student' AND user_id = ? AND is_read = 0");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $studentId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                $unreadCount = (int)($row['unread'] ?? 0);
            }
        }

        return [
            'status' => 'success',
            'data' => [
                'unread_count' => $unreadCount
            ]
        ];
    }
}

if (!function_exists('studentDeleteNotification')) {
    function studentDeleteNotification($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        ensure_notifications_tables($con);

        $body = read_json_body();
        $id = (int)($body['id'] ?? $body['notification_id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'notification id is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM notification_recipients WHERE user_type = 'student' AND user_id = ? AND (id = ? OR notification_id = ?)");
        mysqli_stmt_bind_param($stmt, "iii", $studentId, $id, $id);
        mysqli_stmt_execute($stmt);

        return [
            'status' => 'success',
            'message' => trans('notification_deleted_success')
        ];
    }
}
