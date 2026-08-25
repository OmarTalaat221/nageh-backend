<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../teacher/reports/manage.php';

if (!function_exists('adminListStudentReports')) {
    function adminListStudentReports($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        $reportType = trim($_GET['report_type'] ?? '');
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = [];
        $params = [];
        $types = "";

        if ($studentId > 0) {
            $where[] = "r.student_id = ?";
            $params[] = $studentId;
            $types .= "i";
        }
        if ($teacherId > 0) {
            $where[] = "r.teacher_id = ?";
            $params[] = $teacherId;
            $types .= "i";
        }
        if ($subjectId > 0) {
            $where[] = "r.subject_id = ?";
            $params[] = $subjectId;
            $types .= "i";
        }
        if (!empty($reportType)) {
            $where[] = "r.report_type = ?";
            $params[] = $reportType;
            $types .= "s";
        }
        if (!empty($search)) {
            $pattern = '%' . $search . '%';
            $where[] = "(r.title LIKE ? OR r.description LIKE ? OR s.name LIKE ?)";
            $params[] = $pattern;
            $params[] = $pattern;
            $params[] = $pattern;
            $types .= "sss";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $countSql = "SELECT COUNT(*) AS total FROM student_reports r LEFT JOIN students s ON r.student_id = s.id {$whereSql}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($where)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "SELECT r.id FROM student_reports r LEFT JOIN students s ON r.student_id = s.id {$whereSql} ORDER BY r.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $report = get_student_report_details($con, (int)$row['id']);
            if ($report) $items[] = $report;
        }

        return build_admin_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminGetStudentReport')) {
    function adminGetStudentReport($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('report_not_found')];

        $report = get_student_report_details($con, $id);
        if (!$report) return ['status' => 'error', 'message' => trans('report_not_found')];

        return ['status' => 'success', 'data' => $report];
    }
}

if (!function_exists('adminCreateStudentReport')) {
    function adminCreateStudentReport($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();

        $studentId = (int)($body['student_id'] ?? 0);
        $teacherId = isset($body['teacher_id']) && $body['teacher_id'] !== '' ? (int)$body['teacher_id'] : null;
        $subjectId = isset($body['subject_id']) && $body['subject_id'] !== '' ? (int)$body['subject_id'] : null;
        $title = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $reportType = trim($body['report_type'] ?? 'academic_performance');
        $term = trim($body['term'] ?? 'الفصل الدراسي الأول');
        $score = isset($body['score']) && $body['score'] !== '' ? (float)$body['score'] : null;
        $maxScore = isset($body['max_score']) && $body['max_score'] !== '' ? (float)$body['max_score'] : null;
        $gradeLabel = trim($body['grade_label'] ?? '');
        $reportDate = trim($body['report_date'] ?? date('Y-m-d'));

        $strengths = is_array($body['strengths'] ?? null) ? json_encode($body['strengths'], JSON_UNESCAPED_UNICODE) : null;
        $improvements = is_array($body['improvements'] ?? null) ? json_encode($body['improvements'], JSON_UNESCAPED_UNICODE) : null;

        if ($studentId <= 0 || empty($title)) {
            return ['status' => 'error', 'message' => 'student_id and title are required'];
        }

        $percentage = null;
        if ($score !== null && $maxScore !== null && $maxScore > 0) {
            $percentage = round(($score / $maxScore) * 100, 2);
        }

        $senderType = $teacherId ? 'teacher' : 'admin';

        $stmt = mysqli_prepare($con, "INSERT INTO student_reports (sender_type, admin_id, teacher_id, student_id, subject_id, report_type, title, term, score, max_score, grade_label, percentage, description, strengths, improvements, report_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siiiissssdddssss", $senderType, $adminId, $teacherId, $studentId, $subjectId, $reportType, $title, $term, $score, $maxScore, $gradeLabel, $percentage, $description, $strengths, $improvements, $reportDate);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create report: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Report added successfully', 'data' => get_student_report_details($con, mysqli_insert_id($con))];
    }
}

if (!function_exists('adminUpdateStudentReport')) {
    function adminUpdateStudentReport($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('report_not_found')];

        $studentId = isset($body['student_id']) ? (int)$body['student_id'] : null;
        $teacherId = array_key_exists('teacher_id', $body) ? ($body['teacher_id'] !== null && $body['teacher_id'] !== '' ? (int)$body['teacher_id'] : null) : null;
        $subjectId = array_key_exists('subject_id', $body) ? ($body['subject_id'] !== null && $body['subject_id'] !== '' ? (int)$body['subject_id'] : null) : null;
        $title = isset($body['title']) ? trim($body['title']) : null;
        $description = isset($body['description']) ? trim($body['description']) : null;
        $reportType = isset($body['report_type']) ? trim($body['report_type']) : null;
        $term = isset($body['term']) ? trim($body['term']) : null;
        $score = isset($body['score']) && $body['score'] !== '' ? (float)$body['score'] : null;
        $maxScore = isset($body['max_score']) && $body['max_score'] !== '' ? (float)$body['max_score'] : null;
        $gradeLabel = isset($body['grade_label']) ? trim($body['grade_label']) : null;
        $reportDate = isset($body['report_date']) ? trim($body['report_date']) : null;

        $strengths = is_array($body['strengths'] ?? null) ? json_encode($body['strengths'], JSON_UNESCAPED_UNICODE) : null;
        $improvements = is_array($body['improvements'] ?? null) ? json_encode($body['improvements'], JSON_UNESCAPED_UNICODE) : null;

        if ($studentId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET student_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $studentId, $id);
            mysqli_stmt_execute($stmt);
        }
        if (array_key_exists('teacher_id', $body)) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET teacher_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $teacherId, $id);
            mysqli_stmt_execute($stmt);
        }
        if (array_key_exists('subject_id', $body)) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET subject_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $subjectId, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($title !== null && !empty($title)) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET title = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $title, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($description !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET description = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $description, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($reportType !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET report_type = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $reportType, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($term !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET term = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $term, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($score !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET score = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "di", $score, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($maxScore !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET max_score = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "di", $maxScore, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($gradeLabel !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET grade_label = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $gradeLabel, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($reportDate !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET report_date = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $reportDate, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($strengths !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET strengths = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $strengths, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($improvements !== null) {
            $stmt = mysqli_prepare($con, "UPDATE student_reports SET improvements = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $improvements, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($score !== null || $maxScore !== null) {
            $curStmt = mysqli_prepare($con, "SELECT score, max_score FROM student_reports WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($curStmt, "i", $id);
            mysqli_stmt_execute($curStmt);
            $curRow = mysqli_fetch_assoc(mysqli_stmt_get_result($curStmt));
            if ($curRow && $curRow['score'] !== null && $curRow['max_score'] !== null && $curRow['max_score'] > 0) {
                $pct = round(($curRow['score'] / $curRow['max_score']) * 100, 2);
                $pctStmt = mysqli_prepare($con, "UPDATE student_reports SET percentage = ? WHERE id = ?");
                mysqli_stmt_bind_param($pctStmt, "di", $pct, $id);
                mysqli_stmt_execute($pctStmt);
            }
        }

        return ['status' => 'success', 'message' => 'Report updated successfully', 'data' => get_student_report_details($con, $id)];
    }
}

if (!function_exists('adminDeleteStudentReport')) {
    function adminDeleteStudentReport($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Report ID is required'];

        $stmt = mysqli_prepare($con, "DELETE FROM student_reports WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            return ['status' => 'success', 'message' => 'Report deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete report'];
    }
}
