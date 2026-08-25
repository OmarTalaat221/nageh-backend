<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/upload.php';

$ALLOWED_SESSION_FILE_TYPES = get_allowed_session_file_types();
$GLOBALS['ALLOWED_SESSION_FILE_TYPES'] = $ALLOWED_SESSION_FILE_TYPES;

if (!function_exists('get_session_file_details')) {
    function get_session_file_details($con, int $fileId): ?array {
        $stmt = mysqli_prepare($con, "SELECT id, session_id, uploaded_by_type, uploaded_by_id, file_type, title, description, file_path, original_name, is_active, created_at FROM session_files WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $fileId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['session_id'] = (int)$row['session_id'];
        $row['uploaded_by_id'] = (int)$row['uploaded_by_id'];
        $row['is_active'] = (int)$row['is_active'];
        $row['file'] = format_image_url($row['file_path']);
        unset($row['file_path']);

        return format_response_images($row);
    }
}

if (!function_exists('get_session_files_list')) {
    function get_session_files_list($con, int $sessionId, ?string $fileType = null): array {
        $sql = "SELECT id FROM session_files WHERE session_id = ? AND is_active = 1";
        $params = [$sessionId];
        $types = "i";

        if (!empty($fileType)) {
            $sql .= " AND file_type = ?";
            $params[] = $fileType;
            $types .= "s";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $files = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $detail = get_session_file_details($con, (int)$row['id']);
            if ($detail) $files[] = $detail;
        }

        return $files;
    }
}

if (!function_exists('sessionFileUploadHandler')) {
    function sessionFileUploadHandler($body, $uploaderType, $uploaderId): array {
        $allowedTypes = get_allowed_session_file_types();

        $sessionId = (int)($body['session_id'] ?? 0);
        $title = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $fileType = strtolower(trim($body['file_type'] ?? 'other'));

        if ($sessionId <= 0) {
            return ['error' => trans('session_id_required')];
        }
        if (!in_array($fileType, $allowedTypes)) {
            return ['error' => 'file_type must be one of: ' . implode(', ', $allowedTypes)];
        }

        $fileInput = $_FILES['file'] ?? $_FILES['attachment'] ?? $_FILES['file_path'] ?? null;
        $fileUrlInput = $body['file'] ?? $body['file_path'] ?? $body['file_url'] ?? $body['attachment'] ?? null;

        $filePath = null;
        $originalName = '';

        if ($fileInput && isset($fileInput['tmp_name']) && !empty($fileInput['tmp_name']) && file_exists($fileInput['tmp_name'])) {
            $originalName = $fileInput['name'] ?? '';
            $filePath = upload_document_file($fileInput, 'session-files');
            if (!$filePath) {
                $filePath = upload_image_file($fileInput, 'session-files');
            }
            if (!$filePath) {
                $filePath = upload_video_file($fileInput, 'session-files');
            }
        }

        if (empty($filePath) && !empty($fileUrlInput) && is_string($fileUrlInput)) {
            $filePath = trim($fileUrlInput);
            $originalName = basename($filePath);
        }

        if (empty($filePath)) {
            return ['error' => trans('file_upload_required')];
        }

        if (empty($title)) {
            $title = $originalName ?: $fileType . '_file';
        }

        return [
            'session_id' => $sessionId,
            'uploaded_by_type' => $uploaderType,
            'uploaded_by_id' => $uploaderId,
            'file_type' => $fileType,
            'title' => $title,
            'description' => $description,
            'file_path' => $filePath,
            'original_name' => $originalName,
        ];
    }
}

if (!function_exists('adminAddSessionFile')) {
    function adminAddSessionFile($con, $adminData = null) {
        $body = read_json_body();
        $adminId = (int)($adminData['id'] ?? 0);

        $data = sessionFileUploadHandler($body, 'admin', $adminId);
        if (isset($data['error'])) {
            return ['status' => 'error', 'message' => $data['error']];
        }

        $sessionId = $data['session_id'];
        $checkStmt = mysqli_prepare($con, "SELECT id FROM class_sessions WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $sessionId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('session_not_found')];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO session_files (session_id, uploaded_by_type, uploaded_by_id, file_type, title, description, file_path, original_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isisssss",
            $data['session_id'], $data['uploaded_by_type'], $data['uploaded_by_id'],
            $data['file_type'], $data['title'], $data['description'],
            $data['file_path'], $data['original_name']
        );

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to add session file: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => 'File added to session successfully',
            'data' => get_session_file_details($con, mysqli_insert_id($con))
        ];
    }
}

if (!function_exists('adminListSessionFiles')) {
    function adminListSessionFiles($con) {
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

        return ['status' => 'success', 'data' => $files, 'total' => count($files)];
    }
}

if (!function_exists('adminDeleteSessionFile')) {
    function adminDeleteSessionFile($con) {
        $body = read_json_body();
        $fileId = (int)($_GET['id'] ?? $body['id'] ?? $body['file_id'] ?? 0);
        if ($fileId <= 0) {
            return ['status' => 'error', 'message' => trans('file_id_required')];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM session_files WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $fileId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete session file'];
        }

        return ['status' => 'success', 'message' => 'Session file deleted successfully'];
    }
}

if (!function_exists('adminUpdateSessionFile')) {
    function adminUpdateSessionFile($con) {
        $body = read_json_body();
        $fileId = (int)($body['id'] ?? $body['file_id'] ?? 0);
        if ($fileId <= 0) {
            return ['status' => 'error', 'message' => trans('file_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM session_files WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $fileId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('file_not_found')];
        }

        $allowedTypes = get_allowed_session_file_types();
        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($body['title'])) {
            $updateFields[] = "title = ?";
            $params[] = trim($body['title']);
            $types .= "s";
        }
        if (isset($body['description'])) {
            $updateFields[] = "description = ?";
            $params[] = trim($body['description']);
            $types .= "s";
        }
        if (isset($body['file_type']) && in_array(strtolower($body['file_type']), $allowedTypes)) {
            $updateFields[] = "file_type = ?";
            $params[] = strtolower($body['file_type']);
            $types .= "s";
        }
        if (isset($body['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = (int)$body['is_active'];
            $types .= "i";
        }

        $fileInput = $_FILES['file'] ?? $_FILES['attachment'] ?? $_FILES['file_path'] ?? null;
        $fileUrlInput = $body['file'] ?? $body['file_path'] ?? $body['file_url'] ?? null;
        $newFilePath = null;
        $originalName = null;

        if ($fileInput && isset($fileInput['tmp_name']) && !empty($fileInput['tmp_name'])) {
            $originalName = $fileInput['name'] ?? '';
            $newFilePath = upload_document_file($fileInput, 'session-files');
            if (!$newFilePath) $newFilePath = upload_image_file($fileInput, 'session-files');
            if (!$newFilePath) $newFilePath = upload_video_file($fileInput, 'session-files');
        } elseif (!empty($fileUrlInput) && is_string($fileUrlInput)) {
            $newFilePath = trim($fileUrlInput);
            $originalName = basename($newFilePath);
        }

        if (!empty($newFilePath)) {
            $updateFields[] = "file_path = ?";
            $params[] = $newFilePath;
            $types .= "s";
            if (!empty($originalName)) {
                $updateFields[] = "original_name = ?";
                $params[] = $originalName;
                $types .= "s";
            }
        }

        if (!empty($updateFields)) {
            $params[] = $fileId;
            $types .= "i";
            $uStmt = mysqli_prepare($con, "UPDATE session_files SET " . implode(", ", $updateFields) . " WHERE id = ?");
            mysqli_stmt_bind_param($uStmt, $types, ...$params);
            mysqli_stmt_execute($uStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Session file updated successfully',
            'data' => get_session_file_details($con, $fileId)
        ];
    }
}

if (!function_exists('teacherAddSessionFile')) {
    function teacherAddSessionFile($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized teacher token'];
        }

        $body = read_json_body();
        $sessionId = (int)($body['session_id'] ?? 0);

        if ($sessionId > 0) {
            $checkStmt = mysqli_prepare($con, "SELECT id FROM class_sessions WHERE id = ? AND teacher_id = ? LIMIT 1");
            mysqli_stmt_bind_param($checkStmt, "ii", $sessionId, $teacherId);
            mysqli_stmt_execute($checkStmt);
            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
                return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
            }
        }

        $data = sessionFileUploadHandler($body, 'teacher', $teacherId);
        if (isset($data['error'])) {
            return ['status' => 'error', 'message' => $data['error']];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO session_files (session_id, uploaded_by_type, uploaded_by_id, file_type, title, description, file_path, original_name) VALUES (?, 'teacher', ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisssss",
            $data['session_id'], $teacherId, $data['file_type'],
            $data['title'], $data['description'], $data['file_path'], $data['original_name']
        );

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to add session file: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => 'File added to session successfully',
            'data' => get_session_file_details($con, mysqli_insert_id($con))
        ];
    }
}

if (!function_exists('teacherListSessionFiles')) {
    function teacherListSessionFiles($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $sessionId = (int)($_GET['session_id'] ?? $_GET['id'] ?? 0);
        if ($sessionId <= 0) {
            return ['status' => 'error', 'message' => trans('session_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM class_sessions WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $sessionId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('session_not_found_or_not_owned')];
        }

        $fileType = strtolower(trim($_GET['file_type'] ?? $_GET['type'] ?? ''));
        $allowedTypes = get_allowed_session_file_types();
        if (!empty($fileType) && !in_array($fileType, $allowedTypes)) {
            $fileType = null;
        }

        $files = get_session_files_list($con, $sessionId, $fileType ?: null);

        return ['status' => 'success', 'data' => $files, 'total' => count($files)];
    }
}

if (!function_exists('teacherDeleteSessionFile')) {
    function teacherDeleteSessionFile($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $fileId = (int)($_GET['id'] ?? $body['id'] ?? $body['file_id'] ?? 0);
        if ($fileId <= 0) {
            return ['status' => 'error', 'message' => trans('file_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT sf.id FROM session_files sf JOIN class_sessions cs ON sf.session_id = cs.id WHERE sf.id = ? AND cs.teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $fileId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('file_not_found_or_not_owned')];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM session_files WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $fileId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete session file'];
        }

        return ['status' => 'success', 'message' => 'Session file deleted successfully'];
    }
}

if (!function_exists('teacherUpdateSessionFile')) {
    function teacherUpdateSessionFile($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $fileId = (int)($body['id'] ?? $body['file_id'] ?? 0);
        if ($fileId <= 0) {
            return ['status' => 'error', 'message' => trans('file_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT sf.id FROM session_files sf JOIN class_sessions cs ON sf.session_id = cs.id WHERE sf.id = ? AND cs.teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $fileId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('file_not_found_or_not_owned')];
        }

        $allowedTypes = get_allowed_session_file_types();
        $updateFields = [];
        $params = [];
        $types = "";

        if (isset($body['title'])) {
            $updateFields[] = "title = ?";
            $params[] = trim($body['title']);
            $types .= "s";
        }
        if (isset($body['description'])) {
            $updateFields[] = "description = ?";
            $params[] = trim($body['description']);
            $types .= "s";
        }
        if (isset($body['file_type']) && in_array(strtolower($body['file_type']), $allowedTypes)) {
            $updateFields[] = "file_type = ?";
            $params[] = strtolower($body['file_type']);
            $types .= "s";
        }
        if (isset($body['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = (int)$body['is_active'];
            $types .= "i";
        }

        $fileInput = $_FILES['file'] ?? $_FILES['attachment'] ?? $_FILES['file_path'] ?? null;
        $fileUrlInput = $body['file'] ?? $body['file_path'] ?? $body['file_url'] ?? null;
        $newFilePath = null;
        $originalName = null;

        if ($fileInput && isset($fileInput['tmp_name']) && !empty($fileInput['tmp_name'])) {
            $originalName = $fileInput['name'] ?? '';
            $newFilePath = upload_document_file($fileInput, 'session-files');
            if (!$newFilePath) $newFilePath = upload_image_file($fileInput, 'session-files');
            if (!$newFilePath) $newFilePath = upload_video_file($fileInput, 'session-files');
        } elseif (!empty($fileUrlInput) && is_string($fileUrlInput)) {
            $newFilePath = trim($fileUrlInput);
            $originalName = basename($newFilePath);
        }

        if (!empty($newFilePath)) {
            $updateFields[] = "file_path = ?";
            $params[] = $newFilePath;
            $types .= "s";
            if (!empty($originalName)) {
                $updateFields[] = "original_name = ?";
                $params[] = $originalName;
                $types .= "s";
            }
        }

        if (!empty($updateFields)) {
            $params[] = $fileId;
            $types .= "i";
            $uStmt = mysqli_prepare($con, "UPDATE session_files SET " . implode(", ", $updateFields) . " WHERE id = ?");
            mysqli_stmt_bind_param($uStmt, $types, ...$params);
            mysqli_stmt_execute($uStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Session file updated successfully',
            'data' => get_session_file_details($con, $fileId)
        ];
    }
}

if (!function_exists('teacherListSubmissions')) {
    function teacherListSubmissions($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $sessionFileId = (int)($_GET['session_file_id'] ?? $_GET['file_id'] ?? $_GET['id'] ?? 0);

        if ($sessionFileId <= 0) {
            return ['status' => 'error', 'message' => trans('session_file_required')];
        }

        $ownerCheck = mysqli_prepare($con, "SELECT sf.id FROM session_files sf JOIN class_sessions cs ON sf.session_id = cs.id WHERE sf.id = ? AND cs.teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($ownerCheck, "ii", $sessionFileId, $teacherId);
        mysqli_stmt_execute($ownerCheck);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($ownerCheck))) {
            return ['status' => 'error', 'message' => trans('session_file_not_found_or_not_owned')];
        }

        $statusFilter = strtolower(trim($_GET['status'] ?? ''));

        $sql = "SELECT sfs.id, sfs.student_id, sfs.file_path, sfs.original_name, sfs.note, sfs.status, sfs.grade, sfs.teacher_feedback, sfs.graded_at, sfs.created_at, st.name AS student_name, st.image AS student_image FROM session_file_submissions sfs LEFT JOIN students st ON sfs.student_id = st.id WHERE sfs.session_file_id = ?";
        $params = [$sessionFileId];
        $types = "i";

        if (in_array($statusFilter, ['pending', 'graded'])) {
            $sql .= " AND sfs.status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }

        $sql .= " ORDER BY sfs.id DESC";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['student_id'] = (int)$row['student_id'];
            $row['grade'] = $row['grade'] !== null ? (float)$row['grade'] : null;
            $row['file'] = format_image_url($row['file_path']);
            unset($row['file_path']);
            $items[] = format_response_images($row);
        }

        $pendingCount = 0;
        $gradedCount = 0;
        foreach ($items as $item) {
            if ($item['status'] === 'pending') $pendingCount++;
            else $gradedCount++;
        }

        return [
            'status' => 'success',
            'data' => $items,
            'total' => count($items),
            'pending_count' => $pendingCount,
            'graded_count' => $gradedCount
        ];
    }
}

if (!function_exists('teacherGradeSubmission')) {
    function teacherGradeSubmission($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $submissionId = (int)($body['id'] ?? $body['submission_id'] ?? 0);

        if ($submissionId <= 0) {
            return ['status' => 'error', 'message' => trans('submission_id_required')];
        }

        $grade = isset($body['grade']) ? (float)$body['grade'] : null;
        $feedback = trim($body['feedback'] ?? $body['teacher_feedback'] ?? '');

        if ($grade === null && empty($feedback)) {
            return ['status' => 'error', 'message' => trans('grade_or_feedback_required')];
        }

        $ownerCheck = mysqli_prepare($con, "SELECT sfs.id FROM session_file_submissions sfs JOIN session_files sf ON sfs.session_file_id = sf.id JOIN class_sessions cs ON sf.session_id = cs.id WHERE sfs.id = ? AND cs.teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($ownerCheck, "ii", $submissionId, $teacherId);
        mysqli_stmt_execute($ownerCheck);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($ownerCheck))) {
            return ['status' => 'error', 'message' => trans('submission_not_found_or_not_yours')];
        }

        $updateFields = ["status = 'graded'", "graded_by_id = ?", "graded_at = NOW()"];
        $params = [$teacherId];
        $types = "i";

        if ($grade !== null) {
            $updateFields[] = "grade = ?";
            $params[] = $grade;
            $types .= "d";
        }
        if (!empty($feedback)) {
            $updateFields[] = "teacher_feedback = ?";
            $params[] = $feedback;
            $types .= "s";
        }

        $params[] = $submissionId;
        $types .= "i";

        $uStmt = mysqli_prepare($con, "UPDATE session_file_submissions SET " . implode(", ", $updateFields) . " WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, $types, ...$params);

        if (!mysqli_stmt_execute($uStmt)) {
            return ['status' => 'error', 'message' => 'Failed to grade submission: ' . mysqli_error($con)];
        }

        require_once __DIR__ . '/../../student/sessions/files.php';
        return [
            'status' => 'success',
            'message' => 'Submission graded successfully',
            'data' => get_submission_details($con, $submissionId)
        ];
    }
}

if (!function_exists('adminListSubmissions')) {
    function adminListSubmissions($con) {
        $sessionFileId = (int)($_GET['session_file_id'] ?? $_GET['file_id'] ?? $_GET['id'] ?? 0);
        if ($sessionFileId <= 0) {
            return ['status' => 'error', 'message' => trans('session_file_required')];
        }

        $statusFilter = strtolower(trim($_GET['status'] ?? ''));
        $sql = "SELECT sfs.id, sfs.student_id, sfs.file_path, sfs.original_name, sfs.note, sfs.status, sfs.grade, sfs.teacher_feedback, sfs.graded_at, sfs.created_at, st.name AS student_name, st.image AS student_image FROM session_file_submissions sfs LEFT JOIN students st ON sfs.student_id = st.id WHERE sfs.session_file_id = ?";
        $params = [$sessionFileId];
        $types = "i";

        if (in_array($statusFilter, ['pending', 'graded'])) {
            $sql .= " AND sfs.status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }

        $sql .= " ORDER BY sfs.id DESC";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['student_id'] = (int)$row['student_id'];
            $row['grade'] = $row['grade'] !== null ? (float)$row['grade'] : null;
            $row['file'] = format_image_url($row['file_path']);
            unset($row['file_path']);
            $items[] = format_response_images($row);
        }

        return ['status' => 'success', 'data' => $items, 'total' => count($items)];
    }
}

if (!function_exists('adminGradeSubmission')) {
    function adminGradeSubmission($con) {
        $body = read_json_body();
        $submissionId = (int)($body['id'] ?? $body['submission_id'] ?? 0);
        $grade = isset($body['grade']) ? (float)$body['grade'] : null;
        $feedback = trim($body['feedback'] ?? $body['teacher_feedback'] ?? '');

        if ($submissionId <= 0) {
            return ['status' => 'error', 'message' => trans('submission_id_required')];
        }
        if ($grade === null && empty($feedback)) {
            return ['status' => 'error', 'message' => trans('grade_or_feedback_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM session_file_submissions WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $submissionId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('submission_not_found')];
        }

        $updateFields = ["status = 'graded'", "graded_at = NOW()"];
        $params = [];
        $types = "";

        if ($grade !== null) {
            $updateFields[] = "grade = ?";
            $params[] = $grade;
            $types .= "d";
        }
        if (!empty($feedback)) {
            $updateFields[] = "teacher_feedback = ?";
            $params[] = $feedback;
            $types .= "s";
        }

        $params[] = $submissionId;
        $types .= "i";

        $uStmt = mysqli_prepare($con, "UPDATE session_file_submissions SET " . implode(", ", $updateFields) . " WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, $types, ...$params);

        if (!mysqli_stmt_execute($uStmt)) {
            return ['status' => 'error', 'message' => 'Failed to grade submission: ' . mysqli_error($con)];
        }

        require_once __DIR__ . '/../../student/sessions/files.php';
        return [
            'status' => 'success',
            'message' => 'Submission graded successfully',
            'data' => get_submission_details($con, $submissionId)
        ];
    }
}
