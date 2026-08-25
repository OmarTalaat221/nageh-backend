<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('teacherGetBookPurchaseRequests')) {
    function teacherGetBookPurchaseRequests($con, $teacherData = null) {
        $teacherId = 0;
        if (is_array($teacherData)) {
            $teacherId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        } elseif (is_object($teacherData)) {
            $teacherId = (int)($teacherData->id ?? $teacherData->teacher_id ?? 0);
        }
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication is required'];
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $statusFilter = trim($_GET['status'] ?? '');
        $whereExtra = '';
        $allowed = ['pending', 'approved', 'rejected'];
        if (in_array($statusFilter, $allowed)) {
            $sf = mysqli_real_escape_string($con, $statusFilter);
            $whereExtra = " AND r.status = '{$sf}'";
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $teacherBooksSql = "
            SELECT DISTINCT cb.id FROM course_books cb
            LEFT JOIN courses c ON cb.course_id = c.id
            WHERE cb.created_by_type = 'teacher' AND cb.created_by_id = {$teacherId}
               OR c.teacher_id = {$teacherId}
               OR cb.id IN (SELECT book_id FROM book_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
        ";

        $countRes = mysqli_query($con, "SELECT COUNT(*) AS total FROM student_book_purchase_requests r WHERE r.book_id IN ({$teacherBooksSql}){$whereExtra}");
        $total = $countRes ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;

        $sql = "
            SELECT r.id, r.student_id, r.book_id, r.status, r.notes, r.reviewed_by_type, r.reviewed_by_id, r.created_at, r.updated_at,
                   st.name AS student_name, st.phone AS student_phone,
                   COALESCE(bt.title, cb.file_path) AS book_title,
                   cb.link AS book_cover, cb.price AS book_price
            FROM student_book_purchase_requests r
            LEFT JOIN students st ON r.student_id = st.id
            LEFT JOIN course_books cb ON r.book_id = cb.id
            LEFT JOIN course_books_translations bt ON cb.id = bt.book_id AND bt.language_id = {$langId}
            WHERE r.book_id IN ({$teacherBooksSql}){$whereExtra}
            ORDER BY r.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $res = mysqli_query($con, $sql);
        $items = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['student_id'] = (int)$row['student_id'];
                $row['book_id'] = (int)$row['book_id'];
                $row['reviewed_by_id'] = $row['reviewed_by_id'] ? (int)$row['reviewed_by_id'] : null;
                $row['book_price'] = $row['book_price'] !== null ? (float)$row['book_price'] : null;
                $row['book_cover'] = format_image_url($row['book_cover']);
                $items[] = $row;
            }
        }

        return build_admin_paginated_response($items, $page, $limit, $total, ['total_count' => $total]);
    }
}

if (!function_exists('teacherReviewBookRequest')) {
    function teacherReviewBookRequest($con, $teacherData = null) {
        $teacherId = 0;
        if (is_array($teacherData)) {
            $teacherId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        } elseif (is_object($teacherData)) {
            $teacherId = (int)($teacherData->id ?? $teacherData->teacher_id ?? 0);
        }
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication is required'];
        }

        $body = read_json_body();
        $requestId = (int)($body['request_id'] ?? $_GET['request_id'] ?? 0);
        $newStatus = trim($body['status'] ?? '');
        $notes = trim($body['notes'] ?? '');

        if ($requestId <= 0) {
            return ['status' => 'error', 'message' => 'request_id is required'];
        }
        if (!in_array($newStatus, ['approved', 'rejected'])) {
            return ['status' => 'error', 'message' => 'status must be approved or rejected'];
        }

        $rRes = mysqli_query($con, "SELECT id, student_id, book_id, status FROM student_book_purchase_requests WHERE id = {$requestId} LIMIT 1");
        $rRow = $rRes ? mysqli_fetch_assoc($rRes) : null;
        if (!$rRow) {
            return ['status' => 'error', 'message' => 'Purchase request not found'];
        }
        if ($rRow['status'] !== 'pending') {
            return ['status' => 'error', 'message' => 'This request has already been reviewed'];
        }

        $bookId = (int)$rRow['book_id'];
        $ownerCheck = mysqli_query($con, "
            SELECT cb.id FROM course_books cb
            LEFT JOIN courses c ON cb.course_id = c.id
            WHERE cb.id = {$bookId} AND (
                (cb.created_by_type = 'teacher' AND cb.created_by_id = {$teacherId})
                OR c.teacher_id = {$teacherId}
                OR cb.id IN (SELECT book_id FROM book_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
            ) LIMIT 1
        ");
        if (!$ownerCheck || mysqli_num_rows($ownerCheck) === 0) {
            return ['status' => 'error', 'message' => 'You are not authorized to review this book request'];
        }

        $stId = (int)$rRow['student_id'];
        $notesEsc = mysqli_real_escape_string($con, $notes);

        mysqli_query($con, "UPDATE student_book_purchase_requests SET status = '{$newStatus}', reviewed_by_type = 'teacher', reviewed_by_id = {$teacherId}, notes = '{$notesEsc}' WHERE id = {$requestId}");

        if ($newStatus === 'approved') {
            $bRes = mysqli_query($con, "SELECT price FROM course_books WHERE id = {$bookId} LIMIT 1");
            $bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
            $price = $bRow ? (float)$bRow['price'] : 0.00;

            $chk = mysqli_query($con, "SELECT id FROM student_book_subscriptions WHERE student_id = {$stId} AND book_id = {$bookId} LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) {
                mysqli_query($con, "UPDATE student_book_subscriptions SET is_active = 1, granted_by_type = 'teacher', granted_by_id = {$teacherId} WHERE student_id = {$stId} AND book_id = {$bookId}");
            } else {
                mysqli_query($con, "INSERT INTO student_book_subscriptions (student_id, book_id, granted_by_type, granted_by_id, price, is_active) VALUES ({$stId}, {$bookId}, 'teacher', {$teacherId}, {$price}, 1)");
            }
        }

        return [
            'status' => 'success',
            'message' => $newStatus === 'approved' ? 'Request approved and book access granted' : 'Request rejected',
            'data' => ['request_id' => $requestId, 'status' => $newStatus]
        ];
    }
}
