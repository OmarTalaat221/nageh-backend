<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../teacher/reports/manage.php';

if (!function_exists('parentListChildrenReports')) {
    function parentListChildrenReports($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $filterStudentId = (int)($_GET['student_id'] ?? 0);
        $reportType = trim($_GET['report_type'] ?? '');

        $cStmt = mysqli_prepare($con, "SELECT student_id FROM parent_students WHERE parent_id = ? AND is_active = 1");
        mysqli_stmt_bind_param($cStmt, "i", $parentId);
        mysqli_stmt_execute($cStmt);
        $cRes = mysqli_stmt_get_result($cStmt);

        $allowedStudentIds = [];
        while ($cRow = mysqli_fetch_assoc($cRes)) {
            $allowedStudentIds[] = (int)$cRow['student_id'];
        }

        if (empty($allowedStudentIds)) {
            return ['status' => 'success', 'data' => []];
        }

        if ($filterStudentId > 0) {
            if (!in_array($filterStudentId, $allowedStudentIds, true)) {
                return ['status' => 'error', 'message' => trans('student_not_linked')];
            }
            $targetStudentIds = [$filterStudentId];
        } else {
            $targetStudentIds = $allowedStudentIds;
        }

        $inClause = implode(',', $targetStudentIds);
        $where = ["r.student_id IN ({$inClause})"];
        $params = [];
        $types = "";

        if (!empty($reportType)) {
            $where[] = "r.report_type = ?";
            $params[] = $reportType;
            $types .= "s";
        }

        $whereSql = implode(" AND ", $where);
        $sql = "SELECT r.id FROM student_reports r WHERE {$whereSql} ORDER BY r.id DESC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
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

if (!function_exists('parentGetChildReport')) {
    function parentGetChildReport($con, $parentData) {
        $parentId = (int)($parentData['id'] ?? $parentData['parent_id'] ?? 0);
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => trans('report_not_found')];

        $report = get_student_report_details($con, $id);
        if (!$report) return ['status' => 'error', 'message' => trans('report_not_found')];

        $studentId = (int)$report['student_id'];
        $checkStmt = mysqli_prepare($con, "SELECT id FROM parent_students WHERE parent_id = ? AND student_id = ? AND is_active = 1 LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $parentId, $studentId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('report_not_found')];
        }

        return ['status' => 'success', 'data' => $report];
    }
}
