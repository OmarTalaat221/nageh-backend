<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../teacher/sessions/files.php';
require_once __DIR__ . '/../../teacher/sessions/manage.php'; // get_class_session_details()
require_once __DIR__ . '/../../user/student/subscriptions/manage.php';

if (!function_exists('get_submission_details')) {
    function get_submission_details($con, int $submissionId): ?array {
        $stmt = mysqli_prepare($con, "SELECT s.id, s.session_file_id, s.student_id, s.file_path, s.original_name, s.note, s.status, s.grade, s.teacher_feedback, s.graded_by_id, s.graded_at, s.created_at, s.updated_at, st.name AS student_name, st.image AS student_image FROM session_file_submissions s LEFT JOIN students st ON s.student_id = st.id WHERE s.id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $submissionId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['session_file_id'] = (int)$row['session_file_id'];
        $row['student_id'] = (int)$row['student_id'];
        $row['grade'] = $row['grade'] !== null ? (float)$row['grade'] : null;
        $row['graded_by_id'] = $row['graded_by_id'] ? (int)$row['graded_by_id'] : null;
        $row['file'] = format_image_url($row['file_path']);
        unset($row['file_path']);

        return format_response_images($row);
    }
}

if (!function_exists('studentGetSessionDetails')) {
    function studentGetSessionDetails($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $sessionId = (int)($_GET['id'] ?? $_GET['session_id'] ?? $body['id'] ?? $body['session_id'] ?? 0);

        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $session = get_class_session_details($con, $sessionId);
        if (!$session) {
            return ['status' => 'error', 'message' => trans('session_not_found')];
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $hasAccess = false;
        if ($session['course_id']) {
            $hasAccess = in_array($session['course_id'], $accessible['course_ids']);
        } elseif ($session['subject_id']) {
            $hasAccess = in_array($session['subject_id'], $accessible['subject_ids']);
        }

        $session['has_access'] = $hasAccess;
        $session['is_subscribed'] = $hasAccess;
        $session['is_locked'] = !$hasAccess;
        $session['is_open'] = $hasAccess;
        if (!$hasAccess) {
            $session['meeting_link'] = null;
        }

        if (!empty($session['files'])) {
            foreach ($session['files'] as &$file) {
                $subStmt = mysqli_prepare($con, "SELECT id, file_path, original_name, note, status, grade, teacher_feedback, graded_at, created_at FROM session_file_submissions WHERE session_file_id = ? AND student_id = ? LIMIT 1");
                mysqli_stmt_bind_param($subStmt, "ii", $file['id'], $studentId);
                mysqli_stmt_execute($subStmt);
                $sub = mysqli_fetch_assoc(mysqli_stmt_get_result($subStmt));

                if ($sub) {
                    $sub['id'] = (int)$sub['id'];
                    $sub['grade'] = $sub['grade'] !== null ? (float)$sub['grade'] : null;
                    $file['my_submission'] = format_response_images($sub);
                } else {
                    $file['my_submission'] = null;
                }
            }
            unset($file);
        }

        return ['status' => 'success', 'data' => $session];
    }
}

if (!function_exists('studentGetSessionFiles')) {
    function studentGetSessionFiles($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $sessionId = (int)($_GET['session_id'] ?? $_GET['id'] ?? 0);

        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $fileType = strtolower(trim($_GET['file_type'] ?? $_GET['type'] ?? ''));
        $allowedTypes = get_allowed_session_file_types();
        if (!empty($fileType) && !in_array($fileType, $allowedTypes)) {
            $fileType = null;
        }

        $files = get_session_files_list($con, $sessionId, $fileType ?: null);

        foreach ($files as &$file) {
            $subStmt = mysqli_prepare($con, "SELECT id, file_path, original_name, note, status, grade, teacher_feedback, graded_at, created_at FROM session_file_submissions WHERE session_file_id = ? AND student_id = ? LIMIT 1");
            mysqli_stmt_bind_param($subStmt, "ii", $file['id'], $studentId);
            mysqli_stmt_execute($subStmt);
            $sub = mysqli_fetch_assoc(mysqli_stmt_get_result($subStmt));

            if ($sub) {
                $sub['id'] = (int)$sub['id'];
                $sub['grade'] = $sub['grade'] !== null ? (float)$sub['grade'] : null;
                $file['my_submission'] = format_response_images($sub);
            } else {
                $file['my_submission'] = null;
            }
        }
        unset($file);

        return ['status' => 'success', 'data' => $files, 'total' => count($files)];
    }
}

if (!function_exists('studentSubmitSessionFile')) {
    function studentSubmitSessionFile($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

        $body = read_json_body();
        $sessionFileId = (int)($body['session_file_id'] ?? $_POST['session_file_id'] ?? 0);
        $note = trim($body['note'] ?? $_POST['note'] ?? '');

        if ($sessionFileId <= 0) {
            return ['status' => 'error', 'message' => trans('session_file_required')];
        }

        $fileCheck = mysqli_prepare($con, "SELECT sf.id, sf.session_id, sf.file_type, cs.course_id, cs.subject_id FROM session_files sf JOIN class_sessions cs ON sf.session_id = cs.id WHERE sf.id = ? AND sf.is_active = 1 LIMIT 1");
        mysqli_stmt_bind_param($fileCheck, "i", $sessionFileId);
        mysqli_stmt_execute($fileCheck);
        $fileRow = mysqli_fetch_assoc(mysqli_stmt_get_result($fileCheck));

        if (!$fileRow) {
            return ['status' => 'error', 'message' => trans('session_file_not_found')];
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $hasAccess = false;
        if ($fileRow['course_id']) {
            $hasAccess = in_array((int)$fileRow['course_id'], $accessible['course_ids']);
        } elseif ($fileRow['subject_id']) {
            $hasAccess = in_array((int)$fileRow['subject_id'], $accessible['subject_ids']);
        }

        if (!$hasAccess) {
            return ['status' => 'error', 'message' => trans('not_subscribed_to_session')];
        }

        $fileInput = $_FILES['file'] ?? $_FILES['solution'] ?? $_FILES['file_path'] ?? null;
        $fileUrlInput = $body['file'] ?? $body['file_path'] ?? $body['file_url'] ?? null;

        $filePath = null;
        $originalName = '';

        if ($fileInput && isset($fileInput['tmp_name']) && !empty($fileInput['tmp_name']) && file_exists($fileInput['tmp_name'])) {
            $originalName = $fileInput['name'] ?? '';
            $filePath = upload_document_file($fileInput, 'session-submissions');
            if (!$filePath) $filePath = upload_image_file($fileInput, 'session-submissions');
            if (!$filePath) $filePath = upload_video_file($fileInput, 'session-submissions');
        }

        if (empty($filePath) && !empty($fileUrlInput) && is_string($fileUrlInput)) {
            $filePath = trim($fileUrlInput);
            $originalName = basename($filePath);
        }

        if (empty($filePath)) {
            return ['status' => 'error', 'message' => trans('solution_file_required')];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO session_file_submissions (session_file_id, student_id, file_path, original_name, note, status) VALUES (?, ?, ?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), original_name = VALUES(original_name), note = VALUES(note), status = 'pending', updated_at = NOW()");
        mysqli_stmt_bind_param($stmt, "iisss", $sessionFileId, $studentId, $filePath, $originalName, $note);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to submit solution: ' . mysqli_error($con)];
        }

        $newId = mysqli_insert_id($con);
        if (!$newId) {
            $findStmt = mysqli_prepare($con, "SELECT id FROM session_file_submissions WHERE session_file_id = ? AND student_id = ? LIMIT 1");
            mysqli_stmt_bind_param($findStmt, "ii", $sessionFileId, $studentId);
            mysqli_stmt_execute($findStmt);
            $found = mysqli_fetch_assoc(mysqli_stmt_get_result($findStmt));
            $newId = (int)($found['id'] ?? 0);
        }

        return [
            'status' => 'success',
            'message' => 'Solution submitted successfully',
            'data' => get_submission_details($con, $newId)
        ];
    }
}

if (!function_exists('studentGetMySubmissions')) {
    function studentGetMySubmissions($con, $studentData = null) {
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
            return ['status' => 'success', 'data' => [], 'total' => 0];
        }

        $sessionId = (int)($_GET['session_id'] ?? 0);
        $status = strtolower(trim($_GET['status'] ?? ''));

        $sql = "SELECT sfs.id FROM session_file_submissions sfs JOIN session_files sf ON sfs.session_file_id = sf.id WHERE sfs.student_id = ?";
        $params = [$studentId];
        $types = "i";

        if ($sessionId > 0) {
            $sql .= " AND sf.session_id = ?";
            $params[] = $sessionId;
            $types .= "i";
        }
        if (in_array($status, ['pending', 'graded'])) {
            $sql .= " AND sfs.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $sql .= " ORDER BY sfs.id DESC";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $detail = get_submission_details($con, (int)$row['id']);
            if ($detail) $items[] = $detail;
        }

        return ['status' => 'success', 'data' => $items, 'total' => count($items)];
    }
}
