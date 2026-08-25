<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../teacher/reports/manage.php';

if (!function_exists('studentListMyReports')) {
    function studentListMyReports($con, $studentData = null) {
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
            return ['status' => 'success', 'data' => []];
        }

        $reportType = trim($_GET['report_type'] ?? '');
        $where = ["r.student_id = ?"];
        $params = [$studentId];
        $types = "i";

        if (!empty($reportType)) {
            $where[] = "r.report_type = ?";
            $params[] = $reportType;
            $types .= "s";
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

if (!function_exists('studentGetMyReport')) {
    function studentGetMyReport($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('report_not_found')];

        $report = get_student_report_details($con, $id);
        if (!$report || (int)$report['student_id'] !== $studentId) {
            return ['status' => 'error', 'message' => trans('report_not_found')];
        }

        return ['status' => 'success', 'data' => $report];
    }
}
