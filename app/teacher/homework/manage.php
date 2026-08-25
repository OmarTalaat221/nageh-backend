<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('get_homework_details')) {
    function get_homework_details($con, int $homeworkId) {
        if ($homeworkId <= 0) return null;

        $stmt = mysqli_prepare($con, "SELECT id, teacher_id, subject_id, title, description, due_date, file_path, is_active, created_at FROM homework WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $homeworkId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['teacher_id'] = (int)$row['teacher_id'];
        $row['subject_id'] = (int)$row['subject_id'];
        $row['is_active'] = (int)$row['is_active'];
        $row['subject'] = get_subject_details($con, $row['subject_id']);

        $cStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM homework_submissions WHERE homework_id = ?");
        mysqli_stmt_bind_param($cStmt, "i", $homeworkId);
        mysqli_stmt_execute($cStmt);
        $row['submissions_count'] = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt))['total'] ?? 0);

        return format_response_images($row);
    }
}

if (!function_exists('teacherListHomework')) {
    function teacherListHomework($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total FROM homework WHERE teacher_id = {$teacherId}");
        $totalRecords = (int)(mysqli_fetch_assoc($statRes)['total'] ?? 0);

        $stmt = mysqli_prepare($con, "SELECT id FROM homework WHERE teacher_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($stmt, "iii", $teacherId, $limit, $offset);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_homework_details($con, (int)$row['id']);
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('teacherCreateHomework')) {
    function teacherCreateHomework($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();

        $subjectId = (int)($body['subject_id'] ?? 0);
        $title = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $dueDate = !empty($body['due_date']) ? date('Y-m-d', strtotime($body['due_date'])) : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        if ($subjectId <= 0 || empty($title)) {
            return ['status' => 'error', 'message' => 'subject_id and title are required'];
        }

        $fileInput = $body['file'] ?? $body['file_path'] ?? null;
        $uploadedFile = $_FILES['file'] ?? $_FILES['pdf'] ?? null;
        $filePath = process_image_input($fileInput, 'homework', $uploadedFile);

        $stmt = mysqli_prepare($con, "INSERT INTO homework (teacher_id, subject_id, title, description, due_date, file_path, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iissssi", $teacherId, $subjectId, $title, $description, $dueDate, $filePath, $isActive);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create homework: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => 'Homework created successfully',
            'data' => get_homework_details($con, mysqli_insert_id($con))
        ];
    }
}

if (!function_exists('teacherUpdateHomework')) {
    function teacherUpdateHomework($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Homework ID is required'];

        $checkStmt = mysqli_prepare($con, "SELECT id FROM homework WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $id, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'Homework not found or not owned by you'];
        }

        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($body['title'])) { $updateFields[] = "title = ?"; $params[] = trim($body['title']); $types .= "s"; }
        if (isset($body['description'])) { $updateFields[] = "description = ?"; $params[] = trim($body['description']); $types .= "s"; }
        if (isset($body['subject_id'])) { $updateFields[] = "subject_id = ?"; $params[] = (int)$body['subject_id']; $types .= "i"; }
        if (isset($body['due_date'])) { $updateFields[] = "due_date = ?"; $params[] = !empty($body['due_date']) ? date('Y-m-d', strtotime($body['due_date'])) : null; $types .= "s"; }
        if (isset($body['is_active'])) { $updateFields[] = "is_active = ?"; $params[] = (int)$body['is_active']; $types .= "i"; }

        $fileInput = $body['file'] ?? $body['file_path'] ?? null;
        $uploadedFile = $_FILES['file'] ?? $_FILES['pdf'] ?? null;
        $filePath = process_image_input($fileInput, 'homework', $uploadedFile);
        if ($filePath !== null) { $updateFields[] = "file_path = ?"; $params[] = $filePath; $types .= "s"; }

        if (!empty($updateFields)) {
            $params[] = $id;
            $types .= "i";
            $uStmt = mysqli_prepare($con, "UPDATE homework SET " . implode(", ", $updateFields) . " WHERE id = ?");
            mysqli_stmt_bind_param($uStmt, $types, ...$params);
            mysqli_stmt_execute($uStmt);
        }

        return ['status' => 'success', 'message' => 'Homework updated successfully', 'data' => get_homework_details($con, $id)];
    }
}

if (!function_exists('teacherDeleteHomework')) {
    function teacherDeleteHomework($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Homework ID is required'];

        $stmt = mysqli_prepare($con, "DELETE FROM homework WHERE id = ? AND teacher_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $teacherId);
        if (mysqli_stmt_execute($stmt)) {
            @mysqli_query($con, "DELETE FROM homework_submissions WHERE homework_id = {$id}");
            return ['status' => 'success', 'message' => 'Homework deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete homework'];
    }
}

if (!function_exists('get_submission_details')) {
    function get_submission_details($con, int $subId) {
        $stmt = mysqli_prepare($con, "SELECT hs.id, hs.homework_id, hs.student_id, hs.file_path, hs.status, hs.feedback_text, hs.feedback_rating, hs.submitted_at, hs.reviewed_at, s.name AS student_name, s.email AS student_email FROM homework_submissions hs JOIN students s ON hs.student_id = s.id WHERE hs.id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $subId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['homework_id'] = (int)$row['homework_id'];
        $row['student_id'] = (int)$row['student_id'];
        $row['feedback_rating'] = $row['feedback_rating'] !== null ? (int)$row['feedback_rating'] : null;
        return format_response_images($row);
    }
}

if (!function_exists('teacherListHomeworkSubmissions')) {
    function teacherListHomeworkSubmissions($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $homeworkId = (int)($_GET['homework_id'] ?? 0);
        if ($homeworkId <= 0) return ['status' => 'error', 'message' => 'homework_id is required'];

        $checkStmt = mysqli_prepare($con, "SELECT id FROM homework WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $homeworkId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'Homework not found or not owned by you'];
        }

        $stmt = mysqli_prepare($con, "SELECT id FROM homework_submissions WHERE homework_id = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $homeworkId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_submission_details($con, (int)$row['id']);
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('teacherGradeSubmission')) {
    function teacherGradeSubmission($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $submissionId = (int)($body['submission_id'] ?? $body['id'] ?? 0);
        $feedbackText = trim($body['feedback_text'] ?? $body['feedback'] ?? '');
        $rating = isset($body['rating']) ? (int)$body['rating'] : null;

        if ($submissionId <= 0 || empty($feedbackText)) {
            return ['status' => 'error', 'message' => 'submission_id and feedback_text are required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT hs.id FROM homework_submissions hs JOIN homework h ON hs.homework_id = h.id WHERE hs.id = ? AND h.teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $submissionId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'Submission not found or not owned by you'];
        }

        $stmt = mysqli_prepare($con, "UPDATE homework_submissions SET status = 'reviewed', feedback_text = ?, feedback_rating = ?, reviewed_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $feedbackText, $rating, $submissionId);
        mysqli_stmt_execute($stmt);

        return ['status' => 'success', 'message' => 'Feedback submitted successfully', 'data' => get_submission_details($con, $submissionId)];
    }
}
