<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/offline_payments.php';
require_once __DIR__ . '/../../../app/admin/payments/offline.php';

/**
 * A teacher's view of offline payments - their own courses only.
 *
 * Teachers already review book purchase requests for their own books, so the same shape applies
 * here: they see the money coming in for work that is theirs, and can confirm it without waiting
 * for an admin. Ownership is the same test the discount codes use, so a teacher can never see or
 * touch a payment for somebody else's course.
 *
 * Books are deliberately out of scope: a book's purchase already has its own request flow with
 * its own teacher review, and putting it in two queues would mean two people granting it twice.
 */

if (!function_exists('teacher_offline_course_scope')) {
    /**
     * Returns the SQL condition limiting rows to this teacher's courses, or null when they own
     * none - in which case the caller must return an empty list rather than an open query.
     */
    function teacher_offline_course_scope($con, int $teacherId): ?string {
        $courseIds = offline_payment_teacher_course_ids($con, $teacherId);
        if (!$courseIds) return null;

        $ids = implode(',', array_map('intval', $courseIds));
        return "r.item_type = 'course' AND r.item_id IN ({$ids})";
    }
}

if (!function_exists('teacherListOfflinePayments')) {
    function teacherListOfflinePayments($con, $teacherData = null) {
        $teacherId = offline_reviewer_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_auth_required')];
        }

        $scope = teacher_offline_course_scope($con, $teacherId);
        if ($scope === null) {
            return [
                'status' => 'success',
                'data' => [],
                'statistics' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'pending_amount' => 0.0],
            ];
        }

        $conditions = [$scope];
        $params = [];
        $types = '';

        $status = trim($_GET['status'] ?? '');
        if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $conditions[] = 'r.status = ?';
            $params[] = $status;
            $types .= 's';
        }

        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        if ($search !== '') {
            $conditions[] = '(s.name LIKE ? OR s.email LIKE ? OR r.sender_reference LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
            $types .= 'sss';
        }

        $where = ' WHERE ' . implode(' AND ', $conditions);

        // Scoped to this teacher, so the totals are about their own courses and not the platform.
        $statistics = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'pending_amount' => 0.0];
        $statRes = @mysqli_query($con, "SELECT r.status, COUNT(*) AS c, SUM(r.amount) AS total
            FROM offline_payment_requests r WHERE {$scope} GROUP BY r.status");
        if ($statRes) {
            while ($row = mysqli_fetch_assoc($statRes)) {
                $statistics[$row['status']] = (int)$row['c'];
                if ($row['status'] === 'pending') $statistics['pending_amount'] = (float)$row['total'];
            }
        }

        $sql = offline_payment_base_query($con) . $where . " ORDER BY FIELD(r.status, 'pending') DESC, r.id DESC LIMIT 100";
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = offline_payment_format($con, $row);
        }

        return ['status' => 'success', 'data' => $rows, 'statistics' => $statistics];
    }
}

if (!function_exists('teacherReviewOfflinePayment')) {
    function teacherReviewOfflinePayment($con, $teacherData = null) {
        $teacherId = offline_reviewer_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_auth_required')];
        }

        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $action = strtolower(trim($body['action'] ?? ''));
        $note = trim((string)($body['note'] ?? $body['review_note'] ?? ''));

        if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'حدد الطلب والإجراء (approve أو reject).', 'Specify the request and the action (approve or reject).')];
        }

        $request = offline_find_request($con, $id);

        // A request for someone else's course reports "not found" rather than "forbidden", so the
        // endpoint cannot be used to discover that other teachers' payments exist.
        $courseIds = offline_payment_teacher_course_ids($con, $teacherId);
        $owns = $request
            && $request['item_type'] === 'course'
            && in_array((int)$request['item_id'], array_map('intval', $courseIds), true);

        if (!$owns) {
            return ['status' => 'error', 'message' => app_offline_text($con, 'الطلب مش موجود.', 'That request does not exist.')];
        }

        if ($request['status'] !== 'pending') {
            return ['status' => 'error', 'message' => app_offline_text($con, 'الطلب ده اتراجع عليه بالفعل.', 'This request has already been reviewed.')];
        }

        $result = $action === 'approve'
            ? offline_payment_approve($con, $request, 'teacher', $teacherId, $note)
            : offline_payment_reject($con, $request, 'teacher', $teacherId, $note);

        if (empty($result['ok'])) {
            return ['status' => 'error', 'message' => $result['message']];
        }

        return ['status' => 'success', 'message' => $result['message'], 'data' => ['id' => $id, 'action' => $action]];
    }
}
