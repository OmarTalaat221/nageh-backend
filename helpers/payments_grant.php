<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin_notifications.php';

/**
 * Granting paid access, independent of how the money arrived.
 *
 * These three lived inside the gateway handler, which made anything that wanted to grant access
 * after a payment drag a payment gateway in with it. Nothing here knows or cares whether the
 * money came through a gateway, a bank transfer or an admin deciding it did - it resolves what is
 * being bought, checks the student does not already own it, and opens the access.
 */
if (!function_exists('payments_resolve_purchasable_item')) {
    function payments_resolve_purchasable_item($con, string $itemType, int $itemId) {
        if ($itemType === 'course') {
            $stmt = mysqli_prepare($con, "SELECT id, price, is_active FROM courses WHERE id = ? LIMIT 1");
        } elseif ($itemType === 'book') {
            $stmt = mysqli_prepare($con, "SELECT id, price, is_active FROM course_books WHERE id = ? LIMIT 1");
        } else {
            return null;
        }
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $itemId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['price'] = (float)$row['price'];
        $row['is_active'] = (int)$row['is_active'];
        return $row;
    }
}

if (!function_exists('payments_student_already_has_access')) {
    function payments_student_already_has_access($con, int $studentId, string $itemType, int $itemId): bool {
        if ($itemType === 'course') {
            $stmt = mysqli_prepare($con, "SELECT ss.id FROM student_subscription_courses ssc
                JOIN student_subscriptions ss ON ss.id = ssc.student_subscription_id
                WHERE ss.student_id = ? AND ssc.course_id = ? AND ss.is_active = 1
                AND (ss.end_date IS NULL OR ss.end_date >= NOW()) LIMIT 1");
        } else {
            $stmt = mysqli_prepare($con, "SELECT id FROM student_book_subscriptions
                WHERE student_id = ? AND book_id = ? AND is_active = 1 LIMIT 1");
        }
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $itemId);
        mysqli_stmt_execute($stmt);
        return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

if (!function_exists('payments_grant_access')) {
    function payments_grant_access($con, int $studentId, string $itemType, int $itemId, float $price): ?int {
        if ($itemType === 'course') {
            // A package sells a fixed period (courses.duration_months), so the access it grants
            // has to expire with it; an ordinary course stays open ended as before.
            $kindStmt = mysqli_prepare($con, "SELECT kind, duration_months FROM courses WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($kindStmt, "i", $itemId);
            mysqli_stmt_execute($kindStmt);
            $courseRow = mysqli_fetch_assoc(mysqli_stmt_get_result($kindStmt));

            $subscriptionType = ($courseRow['kind'] ?? 'normal') === 'package' ? 'package' : 'course';
            $months = (int)($courseRow['duration_months'] ?? 0);
            $endDate = $months > 0 ? date('Y-m-d H:i:s', strtotime("+{$months} months")) : null;

            $stmt = mysqli_prepare($con, "INSERT INTO student_subscriptions (student_id, subscription_type, start_date, end_date, is_active) VALUES (?, ?, NOW(), ?, 1)");
            mysqli_stmt_bind_param($stmt, "iss", $studentId, $subscriptionType, $endDate);
            if (!mysqli_stmt_execute($stmt)) return null;
            $subId = mysqli_insert_id($con);

            $linkStmt = mysqli_prepare($con, "INSERT INTO student_subscription_courses (student_subscription_id, course_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE course_id = course_id");
            mysqli_stmt_bind_param($linkStmt, "ii", $subId, $itemId);
            mysqli_stmt_execute($linkStmt);

            notify_of_course_subscription($con, $studentId, [$itemId], 'شراء مباشر');

            return $subId;
        }

        $stmt = mysqli_prepare($con, "INSERT INTO student_book_subscriptions (student_id, book_id, granted_by_type, granted_by_id, price, is_active) VALUES (?, ?, 'student', ?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "iiid", $studentId, $itemId, $studentId, $price);
        if (!mysqli_stmt_execute($stmt)) return null;
        return mysqli_insert_id($con);
    }
}
