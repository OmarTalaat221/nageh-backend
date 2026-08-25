<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../teacher/homework/manage.php';

if (!function_exists('get_student_subscribed_subject_ids')) {
    function get_student_subscribed_subject_ids($con, int $studentId): array {
        require_once __DIR__ . '/../../user/student/subscriptions/manage.php';
        $acc = get_student_accessible_entities($con, $studentId);
        return $acc['subject_ids'];
    }
}

if (!function_exists('get_student_subscribed_course_ids')) {
    function get_student_subscribed_course_ids($con, int $studentId): array {
        require_once __DIR__ . '/../../user/student/subscriptions/manage.php';
        $acc = get_student_accessible_entities($con, $studentId);
        return $acc['course_ids'];
    }
}

if (!function_exists('studentListHomework')) {
    function studentListHomework($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $subjectIds = get_student_subscribed_subject_ids($con, $studentId);
        if (empty($subjectIds)) {
            return build_paginated_response([], $page, $limit, 0);
        }
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $types = str_repeat('i', count($subjectIds));

        $countSql = "SELECT COUNT(*) AS total FROM homework WHERE is_active = 1 AND subject_id IN ({$placeholders})";
        $countStmt = mysqli_prepare($con, $countSql);
        mysqli_stmt_bind_param($countStmt, $types, ...$subjectIds);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT id FROM homework WHERE is_active = 1 AND subject_id IN ({$placeholders}) ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        $bindTypes = $types . "ii";
        $bindParams = array_merge($subjectIds, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $hw = get_homework_details($con, (int)$row['id']);
            $subStmt = mysqli_prepare($con, "SELECT id, status, file_path, feedback_text, feedback_rating, submitted_at FROM homework_submissions WHERE homework_id = ? AND student_id = ? LIMIT 1");
            mysqli_stmt_bind_param($subStmt, "ii", $row['id'], $studentId);
            mysqli_stmt_execute($subStmt);
            $sub = mysqli_fetch_assoc(mysqli_stmt_get_result($subStmt));
            $hw['my_submission'] = $sub ? format_response_images([
                'id' => (int)$sub['id'],
                'status' => $sub['status'],
                'file_path' => $sub['file_path'],
                'feedback_text' => $sub['feedback_text'],
                'feedback_rating' => $sub['feedback_rating'] !== null ? (int)$sub['feedback_rating'] : null,
                'submitted_at' => $sub['submitted_at'],
            ]) : null;
            $items[] = $hw;
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('studentSubmitHomework')) {
    function studentSubmitHomework($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $homeworkId = (int)($body['homework_id'] ?? 0);
        if ($homeworkId <= 0) {
            return ['status' => 'error', 'message' => 'homework_id is required'];
        }

        $subjectIds = get_student_subscribed_subject_ids($con, $studentId);
        $hwStmt = mysqli_prepare($con, "SELECT subject_id FROM homework WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($hwStmt, "i", $homeworkId);
        mysqli_stmt_execute($hwStmt);
        $hwRow = mysqli_fetch_assoc(mysqli_stmt_get_result($hwStmt));
        if (!$hwRow) {
            return ['status' => 'error', 'message' => 'Homework not found'];
        }
        if (!in_array((int)$hwRow['subject_id'], $subjectIds)) {
            return ['status' => 'error', 'message' => 'You are not subscribed to this homework\'s subject'];
        }

        $fileInput = $body['file'] ?? $body['file_path'] ?? null;
        $uploadedFile = $_FILES['file'] ?? $_FILES['pdf'] ?? null;
        $filePath = process_image_input($fileInput, 'homework_submissions', $uploadedFile);
        if (empty($filePath)) {
            return ['status' => 'error', 'message' => 'A solution file is required'];
        }

        $stmt = mysqli_prepare($con, "
            INSERT INTO homework_submissions (homework_id, student_id, file_path, status, submitted_at)
            VALUES (?, ?, ?, 'submitted', NOW())
            ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), status = 'submitted', submitted_at = NOW(), feedback_text = NULL, feedback_rating = NULL, reviewed_at = NULL
        ");
        mysqli_stmt_bind_param($stmt, "iis", $homeworkId, $studentId, $filePath);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to submit homework: ' . mysqli_error($con)];
        }

        $subId = mysqli_insert_id($con);
        if ($subId <= 0) {
            $fetchStmt = mysqli_prepare($con, "SELECT id FROM homework_submissions WHERE homework_id = ? AND student_id = ? LIMIT 1");
            mysqli_stmt_bind_param($fetchStmt, "ii", $homeworkId, $studentId);
            mysqli_stmt_execute($fetchStmt);
            $subId = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($fetchStmt))['id'] ?? 0);
        }

        return ['status' => 'success', 'message' => 'Homework submitted successfully', 'data' => get_submission_details($con, $subId)];
    }
}
