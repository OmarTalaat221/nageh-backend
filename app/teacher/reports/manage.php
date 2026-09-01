<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('get_student_report_details')) {
    function get_student_report_details($con, int $reportId) {
        $sql = "SELECT r.*, 
                       t.name AS teacher_name, t.specialization AS teacher_specialization,
                       a.name AS admin_name,
                       s.name AS student_name, s.email AS student_email, s.image AS student_image,
                       sub.code AS subject_code,
                       (SELECT name FROM subjects_translations st WHERE st.subject_id = sub.id AND st.language_id = 1 LIMIT 1) AS subject_name_ar,
                       (SELECT name FROM subjects_translations st WHERE st.subject_id = sub.id AND st.language_id = 2 LIMIT 1) AS subject_name_en
                FROM student_reports r
                LEFT JOIN teachers t ON r.teacher_id = t.id
                LEFT JOIN admins a ON r.admin_id = a.id
                LEFT JOIN students s ON r.student_id = s.id
                LEFT JOIN subjects sub ON r.subject_id = sub.id
                WHERE r.id = ? LIMIT 1";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $reportId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['admin_id'] = $row['admin_id'] ? (int)$row['admin_id'] : null;
        $row['teacher_id'] = $row['teacher_id'] ? (int)$row['teacher_id'] : null;
        $row['student_id'] = (int)$row['student_id'];
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;

        $row['score'] = $row['score'] !== null ? (float)$row['score'] : null;
        $row['max_score'] = $row['max_score'] !== null ? (float)$row['max_score'] : null;

        if ($row['percentage'] !== null) {
            $row['percentage'] = (float)$row['percentage'];
        } elseif ($row['score'] !== null && $row['max_score'] !== null && $row['max_score'] > 0) {
            $row['percentage'] = round(($row['score'] / $row['max_score']) * 100, 2);
        } else {
            $row['percentage'] = null;
        }

        if (is_string($row['strengths'] ?? null)) {
            $row['strengths'] = json_decode($row['strengths'], true) ?: [];
        } elseif (!is_array($row['strengths'] ?? null)) {
            $row['strengths'] = [];
        }

        if (is_string($row['improvements'] ?? null)) {
            $row['improvements'] = json_decode($row['improvements'], true) ?: [];
        } elseif (!is_array($row['improvements'] ?? null)) {
            $row['improvements'] = [];
        }

        if ($row['sender_type'] === 'system' && !empty($row['teacher_id'])) {
            $senderName = $row['teacher_name'] ?: '-';
            $senderRole = 'معلم (تلقائي)';
        } elseif ($row['sender_type'] === 'system' && !empty($row['admin_id'])) {
            $senderName = $row['admin_name'] ?: 'إدارة المدرسة';
            $senderRole = 'إدارة (تلقائي)';
        } elseif ($row['sender_type'] === 'system') {
            $senderName = 'منصة نجاح';
            $senderRole = 'تقرير تلقائي';
        } elseif ($row['sender_type'] === 'admin') {
            $senderName = $row['admin_name'] ?: 'إدارة المدرسة';
            $senderRole = 'إدارة';
        } else {
            $senderName = $row['teacher_name'] ?: '-';
            $senderRole = 'معلم';
        }

        $row['sender'] = [
            'type' => $row['sender_type'],
            'name' => $senderName,
            'role' => $senderRole,
            'specialization' => $row['teacher_specialization'] ?? null
        ];

        $row['subject_name'] = $row['subject_name_ar'] ?: ($row['subject_name_en'] ?: ($row['subject_code'] ?: null));

        $row['student_image'] = format_image_url($row['student_image']);

        return $row;
    }
}

if (!function_exists('teacherListStudentReports')) {
    function teacherListStudentReports($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $studentId = (int)($_GET['student_id'] ?? 0);

        $where = ["r.teacher_id = ?"];
        $params = [$teacherId];
        $types = "i";

        if ($studentId > 0) {
            $where[] = "r.student_id = ?";
            $params[] = $studentId;
            $types .= "i";
        }

        $whereSql = implode(" AND ", $where);
        $stmt = mysqli_prepare($con, "SELECT r.id FROM student_reports r WHERE {$whereSql} ORDER BY r.id DESC");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $report = get_student_report_details($con, (int)$row['id']);
            if ($report) $items[] = $report;
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('teacherGetStudentReport')) {
    function teacherGetStudentReport($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('report_not_found')];

        $report = get_student_report_details($con, $id);
        if (!$report || (int)$report['teacher_id'] !== $teacherId) {
            return ['status' => 'error', 'message' => trans('report_not_found')];
        }

        return ['status' => 'success', 'data' => $report];
    }
}

if (!function_exists('teacherCreateStudentReport')) {
    function teacherCreateStudentReport($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();

        $studentId = (int)($body['student_id'] ?? 0);
        $subjectId = isset($body['subject_id']) ? (int)$body['subject_id'] : null;
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

        $stmt = mysqli_prepare($con, "INSERT INTO student_reports (sender_type, teacher_id, student_id, subject_id, report_type, title, term, score, max_score, grade_label, percentage, description, strengths, improvements, report_date) VALUES ('teacher', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiissssdddssss", $teacherId, $studentId, $subjectId, $reportType, $title, $term, $score, $maxScore, $gradeLabel, $percentage, $description, $strengths, $improvements, $reportDate);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create report: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => 'Report added successfully', 'data' => get_student_report_details($con, mysqli_insert_id($con))];
    }
}

if (!function_exists('teacherUpdateStudentReport')) {
    function teacherUpdateStudentReport($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('report_not_found')];

        $checkStmt = mysqli_prepare($con, "SELECT id FROM student_reports WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $id, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('report_not_found')];
        }

        $studentId = isset($body['student_id']) ? (int)$body['student_id'] : null;
        $subjectId = isset($body['subject_id']) ? (int)$body['subject_id'] : null;
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
        if ($subjectId !== null) {
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

if (!function_exists('teacherDeleteStudentReport')) {
    function teacherDeleteStudentReport($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Report ID is required'];

        $stmt = mysqli_prepare($con, "DELETE FROM student_reports WHERE id = ? AND teacher_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $teacherId);
        if (mysqli_stmt_execute($stmt)) {
            return ['status' => 'success', 'message' => 'Report deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete report'];
    }
}
