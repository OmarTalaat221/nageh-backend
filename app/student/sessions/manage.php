<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../user/student/subscriptions/manage.php';
require_once __DIR__ . '/../../teacher/sessions/manage.php';
require_once __DIR__ . '/../../student/homework/manage.php';

if (!function_exists('studentTodaySessions')) {
    function studentTodaySessions($con, $studentData) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($studentId <= 0) {
            $studentId = (int)($_GET['student_id'] ?? 0);
        }

        $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

        $inputDate = trim($_GET['date'] ?? $_GET['session_date'] ?? '');
        $requestedDay = trim($_GET['day'] ?? $_GET['day_of_week'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $day = null;
        $overrideDate = null;
        if (!empty($inputDate) && strtotime($inputDate) !== false) {
            $overrideDate = date('Y-m-d', strtotime($inputDate));
            $day = date('l', strtotime($inputDate));
        } elseif (!empty($requestedDay) && in_array($requestedDay, $allowedDays, true)) {
            $day = $requestedDay;
            $overrideDate = get_request_now()->format('Y-m-d');
        } else {
            $requestNow = get_request_now();
            $overrideDate = $requestNow->format('Y-m-d');
            $day = $requestNow->format('l');
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $courseIds = $accessible['course_ids'] ?? [];
        $subjectIds = $accessible['subject_ids'] ?? [];
        $teacherIds = $accessible['teacher_ids'] ?? [];

        $where = ["is_active = 1", "day_of_week = ?"];
        $params = [$day];
        $types = "s";

        $entityWhere = [];
        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $entityWhere[] = "subject_id IN ({$placeholders})";
            foreach ($subjectIds as $sId) { $params[] = (int)$sId; $types .= "i"; }
        }
        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $entityWhere[] = "course_id IN ({$placeholders})";
            foreach ($courseIds as $cId) { $params[] = (int)$cId; $types .= "i"; }
        }
        if (!empty($teacherIds)) {
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $entityWhere[] = "teacher_id IN ({$placeholders})";
            foreach ($teacherIds as $tId) { $params[] = (int)$tId; $types .= "i"; }
        }

        if (!empty($entityWhere)) {
            $where[] = "(" . implode(" OR ", $entityWhere) . ")";
        }

        $whereSql = implode(" AND ", $where);

        $totalRecords = 0;
        $countSql = "SELECT COUNT(*) AS total FROM class_sessions WHERE {$whereSql}";
        $countStmt = mysqli_prepare($con, $countSql);
        if ($countStmt) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $cRes = mysqli_stmt_get_result($countStmt);
            if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
                $totalRecords = (int)($cRow['total'] ?? 0);
            }
        }

        if ($totalRecords === 0) {
            $fallbackWhere = ["is_active = 1", "day_of_week = ?"];
            $fallbackParams = [$day];
            $fallbackTypes = "s";
            $fallbackWhereSql = implode(" AND ", $fallbackWhere);

            $countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM class_sessions WHERE {$fallbackWhereSql}");
            if ($countStmt) {
                mysqli_stmt_bind_param($countStmt, $fallbackTypes, ...$fallbackParams);
                mysqli_stmt_execute($countStmt);
                $cRes = mysqli_stmt_get_result($countStmt);
                if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
                    $totalRecords = (int)($cRow['total'] ?? 0);
                }
            }

            $sql = "SELECT id FROM class_sessions WHERE {$fallbackWhereSql} ORDER BY start_time ASC LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($con, $sql);
            if ($stmt) {
                $bindTypes = $fallbackTypes . "ii";
                $bindParams = array_merge($fallbackParams, [$limit, $offset]);
                mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $items = [];
                if ($res) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $items[] = get_class_session_details($con, (int)$row['id'], $overrideDate);
                    }
                }
                return build_paginated_response($items, $page, $limit, $totalRecords);
            }
        }

        $sql = "SELECT id FROM class_sessions WHERE {$whereSql} ORDER BY start_time ASC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        $items = [];
        if ($stmt) {
            $bindTypes = $types . "ii";
            $bindParams = array_merge($params, [$limit, $offset]);
            mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $items[] = get_class_session_details($con, (int)$row['id'], $overrideDate);
                }
            }
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}
