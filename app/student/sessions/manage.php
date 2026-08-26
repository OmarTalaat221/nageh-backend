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

        // لازم نحدد upcoming/upcoming_hours الأول، قبل ما نبني فلتر اليوم،
        // عشان نقدر نقرر هل هنطبق فلتر day_of_week أصلاً أو لا.
        $upcoming = in_array(strtolower(trim((string)($_GET['upcoming'] ?? ''))), ['1', 'true', 'yes'], true);
        $upcomingHours = 24;
        if (isset($_GET['upcoming_hours']) && (int)$_GET['upcoming_hours'] > 0) {
            $upcomingHours = (int)$_GET['upcoming_hours'];
        }

        $day = null;
        $overrideDate = null;
        $wantsAllDays = false;
        $dayExplicit = false; // true بس لو المستخدم فعليًا بعت date أو day في الريكوست

        if (!empty($inputDate) && strtotime($inputDate) !== false) {
            $overrideDate = date('Y-m-d', strtotime($inputDate));
            $day = date('l', strtotime($inputDate));
            $dayExplicit = true;
        } elseif (!empty($requestedDay)) {
            if (strcasecmp($requestedDay, 'all') === 0) {
                $wantsAllDays = true;
                $overrideDate = get_request_now()->format('Y-m-d');
            } else {
                foreach ($allowedDays as $allowedDay) {
                    if (strcasecmp($requestedDay, $allowedDay) === 0) {
                        $day = $allowedDay;
                        $dayExplicit = true;
                        break;
                    }
                }
                if ($day === null) {
                    $requestNow = get_request_now();
                    $overrideDate = $requestNow->format('Y-m-d');
                    $day = $requestNow->format('l');
                } else {
                    $overrideDate = get_request_now()->format('Y-m-d');
                }
            }
        } else {
            $requestNow = get_request_now();
            $overrideDate = $requestNow->format('Y-m-d');
            $day = $requestNow->format('l');
            // $dayExplicit فاضلة false هنا عمدًا: ده default مش طلب فعلي من الفرونت
        }

        $accessible = get_student_accessible_entities($con, $studentId);
        $courseIds = $accessible['course_ids'] ?? [];
        $subjectIds = $accessible['subject_ids'] ?? [];
        $teacherIds = $accessible['teacher_ids'] ?? [];

        $where = ["is_active = 1"];
        $params = [];
        $types = "";

        // الفلتر ده يتطبق بس لو:
        // - الطلب مش upcoming، أو
        // - المستخدم حدد يوم/تاريخ فعليًا (مش default)
        $applyDayFilter = $day !== null && !$wantsAllDays && (!$upcoming || $dayExplicit);
        if ($applyDayFilter) {
            $where[] = "day_of_week = ?";
            $params[] = $day;
            $types .= "s";
        }

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

        if (empty($entityWhere)) {
            return build_paginated_response([], $page, $limit, 0);
        }

        $where[] = "(" . implode(" OR ", $entityWhere) . ")";
        $whereSql = implode(" AND ", $where);

        if ($upcoming) {
            $sql = "SELECT id, day_of_week, start_time FROM class_sessions WHERE {$whereSql}";
            $stmt = mysqli_prepare($con, $sql);
            $rows = [];
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $rows[] = $row;
                    }
                }
            }

            $now = get_request_now();
            $nowTs = $now->getTimestamp();
            $minTs = $nowTs + ($upcomingHours * 3600);
            $dayIndexMap = array_flip($allowedDays);
            $todayIndex = (int)$now->format('w');
            $todayDate = $now->format('Y-m-d');

            $occurrences = [];
            foreach ($rows as $row) {
                $dayName = (string)$row['day_of_week'];
                if (!isset($dayIndexMap[$dayName])) {
                    continue;
                }

                $diff = ($dayIndexMap[$dayName] - $todayIndex + 7) % 7;
                $occDate = date('Y-m-d', strtotime("{$todayDate} +{$diff} days"));
                $occTs = strtotime("{$occDate} " . (string)$row['start_time']);
                if ($occTs === false) {
                    continue;
                }
                // No +7-day rollover: only THIS week's occurrence counts.
                // If it already passed or is within the upcoming window, skip.
                if ($occTs < $minTs) {
                    continue;
                }

                $occurrences[] = ['occ' => $occTs, 'id' => (int)$row['id']];
            }

            usort($occurrences, function ($a, $b) {
                return $a['occ'] <=> $b['occ'];
            });

            $totalRecords = count($occurrences);
            $pageItems = array_slice($occurrences, $offset, $limit);

            $items = [];
            foreach ($pageItems as $entry) {
                $details = get_class_session_details(
                    $con,
                    $entry['id'],
                    date('Y-m-d', $entry['occ'])
                );
                if ($details) {
                    $details['next_session_date'] = date('Y-m-d', $entry['occ']);
                    $details['next_start_datetime'] = date('Y-m-d H:i:s', $entry['occ']);
                    $items[] = $details;
                }
            }

            return build_paginated_response($items, $page, $limit, $totalRecords);
        }

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