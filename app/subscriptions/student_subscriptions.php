<?php
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/language.php';
require_once __DIR__ . '/../../helpers/upload.php';
require_once __DIR__ . '/../content/helpers.php';
require_once __DIR__ . '/codes.php';

if (!function_exists('get_student_subscription_details')) {
    function get_student_subscription_details($con, int $subId) {
        if ($subId <= 0) return null;

        $stmt = mysqli_prepare($con, "SELECT ss.id, ss.student_id, ss.subscription_code_id, ss.subscription_type, ss.start_date, ss.end_date, ss.is_active, ss.created_at, sc.code AS code_string FROM student_subscriptions ss LEFT JOIN subscription_codes sc ON ss.subscription_code_id = sc.id WHERE ss.id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $subId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['student_id'] = (int)$row['student_id'];
        $row['subscription_code_id'] = $row['subscription_code_id'] ? (int)$row['subscription_code_id'] : null;
        $row['is_active'] = (int)$row['is_active'];

        $now = date('Y-m-d H:i:s');
        $row['is_expired'] = ($row['end_date'] !== null && $row['end_date'] < $now);
        $row['is_currently_valid'] = ($row['is_active'] === 1 && !$row['is_expired']);

        $stStmt = mysqli_prepare($con, "SELECT id, name, email, phone, is_active FROM students WHERE id = ? LIMIT 1");
        if ($stStmt) {
            mysqli_stmt_bind_param($stStmt, "i", $row['student_id']);
            mysqli_stmt_execute($stStmt);
            $stRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));
            if ($stRow) {
                $stRow['id'] = (int)$stRow['id'];
                $stRow['is_active'] = (int)$stRow['is_active'];
                $row['student'] = $stRow;
            }
        }

        $subjects = [];
        $sRes = mysqli_query($con, "SELECT s.id, st.name, s.code FROM student_subscription_subjects sss JOIN subjects s ON sss.subject_id = s.id LEFT JOIN subjects_translations st ON s.id = st.subject_id AND st.language_id = 1 WHERE sss.student_subscription_id = {$subId}");
        if ($sRes) {
            while ($sRow = mysqli_fetch_assoc($sRes)) {
                $sRow['id'] = (int)$sRow['id'];
                $subjects[] = $sRow;
            }
        }
        $row['subjects'] = $subjects;

        $teachers = [];
        $tRes = mysqli_query($con, "SELECT t.id, t.name, t.email, t.specialization, t.image FROM student_subscription_teachers sst JOIN teachers t ON sst.teacher_id = t.id WHERE sst.student_subscription_id = {$subId}");
        if ($tRes) {
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['id'] = (int)$tRow['id'];
                $teachers[] = format_response_images($tRow);
            }
        }
        $row['teachers'] = $teachers;

        $courses = [];
        $cRes = mysqli_query($con, "SELECT c.id, c.slug, c.price, c.image, ct.name FROM student_subscription_courses ssc JOIN courses c ON ssc.course_id = c.id LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = 1 WHERE ssc.student_subscription_id = {$subId}");
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $cRow['id'] = (int)$cRow['id'];
                $cRow['price'] = (float)$cRow['price'];
                $courses[] = format_response_images($cRow);
            }
        }
        $row['courses'] = $courses;

        return format_response_images($row);
    }
}

if (!function_exists('adminListStudentSubscriptions')) {
    function adminListStudentSubscriptions($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        $subjectIds = parse_int_list_param($_GET['subject_ids'] ?? $_GET['subject_id'] ?? null);
        $teacherIds = parse_int_list_param($_GET['teacher_ids'] ?? $_GET['teacher_id'] ?? null);
        $courseIds = parse_int_list_param($_GET['course_ids'] ?? $_GET['course_id'] ?? null);
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $whereConditions = [];
        $params = [];
        $types = "";

        if ($studentId > 0) {
            $whereConditions[] = "ss.student_id = ?";
            $params[] = $studentId;
            $types .= "i";
        }

        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_subjects WHERE subject_id IN ({$placeholders}))";
            foreach ($subjectIds as $sId) {
                $params[] = $sId;
                $types .= "i";
            }
        }

        if (!empty($teacherIds)) {
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_teachers WHERE teacher_id IN ({$placeholders}))";
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
        }

        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_courses WHERE course_id IN ({$placeholders}))";
            foreach ($courseIds as $cId) {
                $params[] = $cId;
                $types .= "i";
            }
        }

        if ($activeFilter !== null) {
            $whereConditions[] = "ss.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(st.name LIKE ? OR st.email LIKE ? OR st.phone LIKE ? OR sc.code LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ssss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $countSql = "SELECT COUNT(DISTINCT ss.id) AS total FROM student_subscriptions ss JOIN students st ON ss.student_id = st.id LEFT JOIN subscription_codes sc ON ss.subscription_code_id = sc.id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        $totalRecords = 0;
        if ($countStmt) {
            if (!empty($types)) {
                mysqli_stmt_bind_param($countStmt, $types, ...$params);
            }
            mysqli_stmt_execute($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);
        }

        $sql = "
            SELECT DISTINCT ss.id
            FROM student_subscriptions ss
            JOIN students st ON ss.student_id = st.id
            LEFT JOIN subscription_codes sc ON ss.subscription_code_id = sc.id
            {$whereClause}
            ORDER BY ss.id DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $subscriptions = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $subId = (int)$row['id'];
            $detail = get_student_subscription_details($con, $subId);
            if ($detail) {
                $subscriptions[] = $detail;
            }
        }

        return build_admin_paginated_response($subscriptions, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('adminCreateStudentSubscription')) {
    function adminCreateStudentSubscription($con) {
        $body = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'Student ID is required'];
        }

        $stCheck = mysqli_prepare($con, "SELECT id FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stCheck, "i", $studentId);
        mysqli_stmt_execute($stCheck);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stCheck))) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }

        $subjectIds = parse_int_list_param($body['subject_ids'] ?? $body['subject_id'] ?? null);
        $teacherIds = parse_int_list_param($body['teacher_ids'] ?? $body['teacher_id'] ?? null);
        $courseIds = parse_int_list_param($body['course_ids'] ?? $body['course_id'] ?? null);

        if (empty($subjectIds) && empty($teacherIds) && empty($courseIds)) {
            return ['status' => 'error', 'message' => 'At least one subject, teacher, or course must be selected for subscription'];
        }

        $subType = 'custom';
        if (!empty($courseIds) && empty($subjectIds) && empty($teacherIds)) $subType = 'course';
        if (!empty($subjectIds) && empty($courseIds) && empty($teacherIds)) $subType = 'subject';
        if (!empty($teacherIds) && empty($courseIds) && empty($subjectIds)) $subType = 'teacher';

        $durationDays = isset($body['duration_days']) && $body['duration_days'] > 0 ? (int)$body['duration_days'] : null;
        $startDate = !empty($body['start_date']) ? date('Y-m-d H:i:s', strtotime($body['start_date'])) : date('Y-m-d H:i:s');
        $endDate = !empty($body['end_date']) ? date('Y-m-d H:i:s', strtotime($body['end_date'])) : ($durationDays ? date('Y-m-d H:i:s', strtotime("+{$durationDays} days")) : null);

        $stmt = mysqli_prepare($con, "INSERT INTO student_subscriptions (student_id, subscription_type, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare subscription insert: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "isss", $studentId, $subType, $startDate, $endDate);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create student subscription: ' . mysqli_error($con)];
        }

        $subId = mysqli_insert_id($con);

        foreach ($subjectIds as $sId) {
            $isStmt = mysqli_prepare($con, "INSERT INTO student_subscription_subjects (student_subscription_id, subject_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE subject_id=subject_id");
            mysqli_stmt_bind_param($isStmt, "ii", $subId, $sId);
            mysqli_stmt_execute($isStmt);
        }

        foreach ($teacherIds as $tId) {
            $itStmt = mysqli_prepare($con, "INSERT INTO student_subscription_teachers (student_subscription_id, teacher_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE teacher_id=teacher_id");
            mysqli_stmt_bind_param($itStmt, "ii", $subId, $tId);
            mysqli_stmt_execute($itStmt);
        }

        foreach ($courseIds as $cId) {
            $icStmt = mysqli_prepare($con, "INSERT INTO student_subscription_courses (student_subscription_id, course_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE course_id=course_id");
            mysqli_stmt_bind_param($icStmt, "ii", $subId, $cId);
            mysqli_stmt_execute($icStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Student subscription created successfully',
            'data' => get_student_subscription_details($con, $subId)
        ];
    }
}

if (!function_exists('studentRedeemSubscriptionCode')) {
    function studentRedeemSubscriptionCode($con, $studentData = null) {
        $body = read_json_body();
        $codeStr = strtoupper(trim($body['code'] ?? ''));
        if (empty($codeStr)) {
            return ['status' => 'error', 'message' => 'Subscription activation code is required'];
        }

        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($body['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication is required to redeem code'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, title, max_uses, used_count, valid_from, valid_to, duration_days, is_active FROM subscription_codes WHERE code = ? LIMIT 1");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Query preparation error'];
        }
        mysqli_stmt_bind_param($stmt, "s", $codeStr);
        mysqli_stmt_execute($stmt);
        $codeRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$codeRow) {
            return ['status' => 'error', 'message' => 'Invalid subscription code'];
        }
        if ((int)$codeRow['is_active'] !== 1) {
            return ['status' => 'error', 'message' => 'Subscription code is inactive'];
        }

        $now = date('Y-m-d H:i:s');
        if ($codeRow['valid_from'] && $codeRow['valid_from'] > $now) {
            return ['status' => 'error', 'message' => 'Subscription code is not active yet'];
        }
        if ($codeRow['valid_to'] && $codeRow['valid_to'] < $now) {
            return ['status' => 'error', 'message' => 'Subscription code has expired'];
        }
        if ((int)$codeRow['max_uses'] > 0 && (int)$codeRow['used_count'] >= (int)$codeRow['max_uses']) {
            return ['status' => 'error', 'message' => 'Subscription code has already been used and cannot be used again (Single-use code)'];
        }

        $codeId = (int)$codeRow['id'];
        $alreadyRedeemedStmt = mysqli_prepare($con, "SELECT id FROM code_redemptions_log WHERE subscription_code_id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($alreadyRedeemedStmt, "ii", $codeId, $stId);
        mysqli_stmt_execute($alreadyRedeemedStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($alreadyRedeemedStmt))) {
            return ['status' => 'error', 'message' => 'You have already redeemed this subscription code'];
        }

        $subjects = [];
        $sRes = mysqli_query($con, "SELECT subject_id FROM subscription_code_subjects WHERE subscription_code_id = {$codeId}");
        if ($sRes) {
            while ($sRow = mysqli_fetch_assoc($sRes)) {
                $subjects[] = (int)$sRow['subject_id'];
            }
        }

        $teachers = [];
        $tRes = mysqli_query($con, "SELECT teacher_id FROM subscription_code_teachers WHERE subscription_code_id = {$codeId}");
        if ($tRes) {
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $teachers[] = (int)$tRow['teacher_id'];
            }
        }

        $courses = [];
        $cRes = mysqli_query($con, "SELECT course_id FROM subscription_code_courses WHERE subscription_code_id = {$codeId}");
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $courses[] = (int)$cRow['course_id'];
            }
        }

        if (empty($subjects) && empty($teachers) && empty($courses)) {
            return ['status' => 'error', 'message' => 'This subscription code has no linked content or targets'];
        }

        $subType = 'custom';
        if (!empty($courses) && empty($subjects) && empty($teachers)) $subType = 'course';
        if (!empty($subjects) && empty($courses) && empty($teachers)) $subType = 'subject';
        if (!empty($teachers) && empty($courses) && empty($subjects)) $subType = 'teacher';

        $durationDays = $codeRow['duration_days'] ? (int)$codeRow['duration_days'] : null;
        $startDate = date('Y-m-d H:i:s');
        $endDate = $durationDays ? date('Y-m-d H:i:s', strtotime("+{$durationDays} days")) : null;

        $subStmt = mysqli_prepare($con, "INSERT INTO student_subscriptions (student_id, subscription_code_id, subscription_type, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        mysqli_stmt_bind_param($subStmt, "iisss", $stId, $codeId, $subType, $startDate, $endDate);
        if (!mysqli_stmt_execute($subStmt)) {
            return ['status' => 'error', 'message' => 'Failed to activate subscription: ' . mysqli_error($con)];
        }

        $subId = mysqli_insert_id($con);

        foreach ($subjects as $sId) {
            $isStmt = mysqli_prepare($con, "INSERT INTO student_subscription_subjects (student_subscription_id, subject_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE subject_id=subject_id");
            mysqli_stmt_bind_param($isStmt, "ii", $subId, $sId);
            mysqli_stmt_execute($isStmt);
        }

        foreach ($teachers as $tId) {
            $itStmt = mysqli_prepare($con, "INSERT INTO student_subscription_teachers (student_subscription_id, teacher_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE teacher_id=teacher_id");
            mysqli_stmt_bind_param($itStmt, "ii", $subId, $tId);
            mysqli_stmt_execute($itStmt);
        }

        foreach ($courses as $cId) {
            $icStmt = mysqli_prepare($con, "INSERT INTO student_subscription_courses (student_subscription_id, course_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE course_id=course_id");
            mysqli_stmt_bind_param($icStmt, "ii", $subId, $cId);
            mysqli_stmt_execute($icStmt);
        }

        mysqli_query($con, "UPDATE subscription_codes SET used_count = used_count + 1 WHERE id = {$codeId}");

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $logStmt = mysqli_prepare($con, "INSERT INTO code_redemptions_log (subscription_code_id, student_id, student_subscription_id, ip_address) VALUES (?, ?, ?, ?)");
        if ($logStmt) {
            mysqli_stmt_bind_param($logStmt, "iiis", $codeId, $stId, $subId, $ipAddress);
            mysqli_stmt_execute($logStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Subscription code redeemed and activated successfully',
            'data' => get_student_subscription_details($con, $subId)
        ];
    }
}

if (!function_exists('studentListMySubscriptions')) {
    function studentListMySubscriptions($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return [
                'status' => 'success',
                'data' => []
            ];
        }

        $res = mysqli_query($con, "SELECT id FROM student_subscriptions WHERE student_id = {$stId} ORDER BY id DESC");
        $subscriptions = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $subDetail = get_student_subscription_details($con, (int)$row['id']);
                if ($subDetail) {
                    $subscriptions[] = $subDetail;
                }
            }
        }

        return [
            'status' => 'success',
            'data' => $subscriptions
        ];
    }
}

if (!function_exists('get_student_accessible_entities')) {
    function get_student_accessible_entities($con, int $studentId): array {
        if ($studentId <= 0) {
            return ['course_ids' => [], 'subject_ids' => [], 'teacher_ids' => []];
        }
        $now = date('Y-m-d H:i:s');

        $activeSubIds = [];
        $activeCodeIds = [];

        $subRes = mysqli_query($con, "
            SELECT id, subscription_code_id FROM student_subscriptions
            WHERE student_id = {$studentId} AND (is_active = 1 OR is_active IS NULL)
            AND (end_date IS NULL OR end_date = '0000-00-00 00:00:00' OR end_date = '0000-00-00' OR end_date >= '{$now}')
        ");
        if ($subRes) {
            while ($row = mysqli_fetch_assoc($subRes)) {
                $activeSubIds[] = (int)$row['id'];
                if (!empty($row['subscription_code_id'])) {
                    $activeCodeIds[] = (int)$row['subscription_code_id'];
                }
            }
        }

        $logRes = @mysqli_query($con, "SELECT DISTINCT subscription_code_id FROM code_redemptions_log WHERE student_id = {$studentId}");
        if ($logRes) {
            while ($row = mysqli_fetch_assoc($logRes)) {
                if (!empty($row['subscription_code_id'])) {
                    $activeCodeIds[] = (int)$row['subscription_code_id'];
                }
            }
        }

        $activeCodeIds = array_values(array_unique(array_filter($activeCodeIds)));

        if (empty($activeSubIds) && empty($activeCodeIds)) {
            return ['course_ids' => [], 'subject_ids' => [], 'teacher_ids' => []];
        }

        $directCourseIds = [];
        $directSubjectIds = [];
        $directTeacherIds = [];

        if (!empty($activeSubIds)) {
            $subListStr = implode(',', $activeSubIds);

            $cRes = mysqli_query($con, "SELECT DISTINCT course_id FROM student_subscription_courses WHERE student_subscription_id IN ({$subListStr})");
            if ($cRes) {
                while ($row = mysqli_fetch_assoc($cRes)) {
                    $directCourseIds[] = (int)$row['course_id'];
                }
            }

            $sRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM student_subscription_subjects WHERE student_subscription_id IN ({$subListStr})");
            if ($sRes) {
                while ($row = mysqli_fetch_assoc($sRes)) {
                    $directSubjectIds[] = (int)$row['subject_id'];
                }
            }

            $tRes = mysqli_query($con, "SELECT DISTINCT teacher_id FROM student_subscription_teachers WHERE student_subscription_id IN ({$subListStr})");
            if ($tRes) {
                while ($row = mysqli_fetch_assoc($tRes)) {
                    $directTeacherIds[] = (int)$row['teacher_id'];
                }
            }
        }

        if (!empty($activeCodeIds)) {
            $codeListStr = implode(',', $activeCodeIds);

            $sccRes = mysqli_query($con, "SELECT DISTINCT course_id FROM subscription_code_courses WHERE subscription_code_id IN ({$codeListStr})");
            if ($sccRes) {
                while ($row = mysqli_fetch_assoc($sccRes)) {
                    $directCourseIds[] = (int)$row['course_id'];
                }
            }

            $scsRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM subscription_code_subjects WHERE subscription_code_id IN ({$codeListStr})");
            if ($scsRes) {
                while ($row = mysqli_fetch_assoc($scsRes)) {
                    $directSubjectIds[] = (int)$row['subject_id'];
                }
            }

            $sctRes = mysqli_query($con, "SELECT DISTINCT teacher_id FROM subscription_code_teachers WHERE subscription_code_id IN ({$codeListStr})");
            if ($sctRes) {
                while ($row = mysqli_fetch_assoc($sctRes)) {
                    $directTeacherIds[] = (int)$row['teacher_id'];
                }
            }
        }

        $derivedSubjectIds = $directSubjectIds;
        if (!empty($directTeacherIds)) {
            $tListStr = implode(',', $directTeacherIds);
            $tsRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM teacher_subjects WHERE teacher_id IN ({$tListStr})");
            if ($tsRes) {
                while ($row = mysqli_fetch_assoc($tsRes)) {
                    $derivedSubjectIds[] = (int)$row['subject_id'];
                }
            }
            $tcRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM courses WHERE (teacher_id IN ({$tListStr}) OR (created_by_type = 'teacher' AND created_by_id IN ({$tListStr}))) AND subject_id IS NOT NULL");
            if ($tcRes) {
                while ($row = mysqli_fetch_assoc($tcRes)) {
                    $derivedSubjectIds[] = (int)$row['subject_id'];
                }
            }
        }
        $derivedSubjectIds = array_unique(array_filter($derivedSubjectIds));

        $derivedCourseIds = $directCourseIds;
        if (!empty($derivedSubjectIds)) {
            $dsListStr = implode(',', $derivedSubjectIds);
            $scRes = mysqli_query($con, "SELECT id FROM courses WHERE subject_id IN ({$dsListStr}) AND is_active = 1");
            if ($scRes) {
                while ($row = mysqli_fetch_assoc($scRes)) {
                    $derivedCourseIds[] = (int)$row['id'];
                }
            }
        }
        if (!empty($directTeacherIds)) {
            $dtListStr = implode(',', $directTeacherIds);
            $tcrRes = mysqli_query($con, "SELECT DISTINCT c.id FROM courses c LEFT JOIN course_teachers ct ON c.id = ct.course_id WHERE (c.teacher_id IN ({$dtListStr}) OR (c.created_by_type = 'teacher' AND c.created_by_id IN ({$dtListStr})) OR (ct.teacher_id IN ({$dtListStr}) AND ct.is_active = 1)) AND c.is_active = 1");
            if ($tcrRes) {
                while ($row = mysqli_fetch_assoc($tcrRes)) {
                    $derivedCourseIds[] = (int)$row['id'];
                }
            }
        }
        $derivedCourseIds = array_unique(array_filter($derivedCourseIds));

        return [
            'course_ids' => array_values($derivedCourseIds),
            'subject_ids' => array_values($derivedSubjectIds),
            'teacher_ids' => array_values(array_unique(array_filter($directTeacherIds)))
        ];
    }
}

if (!function_exists('studentGetMySubscribedCourses')) {
    function studentGetMySubscribedCourses($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return build_admin_paginated_response([], 1, 10, 0, ['total_count' => 0]);
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $courseIds = $accessible['course_ids'];
        if (empty($courseIds)) {
            return build_admin_paginated_response([], 1, 10, 0, ['total_count' => 0]);
        }

        $_GET['course_ids'] = $courseIds;
        require_once __DIR__ . '/../admin/courses/manage.php';
        return adminListCourses($con);
    }
}

if (!function_exists('studentGetMySubscribedSubjects')) {
    function studentGetMySubscribedSubjects($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'success', 'data' => []];
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $subjectIds = $accessible['subject_ids'];
        if (empty($subjectIds)) {
            return ['status' => 'success', 'data' => []];
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $sListStr = implode(',', $subjectIds);

        $sql = "
            SELECT s.id, s.code, s.description, s.icon, s.is_active, s.created_at,
                   COALESCE(st.name, s.code) AS name
            FROM subjects s
            LEFT JOIN subjects_translations st ON s.id = st.subject_id AND st.language_id = {$langId}
            WHERE s.id IN ({$sListStr}) AND s.is_active = 1
            ORDER BY s.id ASC
        ";
        $res = mysqli_query($con, $sql);
        $subjects = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['is_active'] = (int)$row['is_active'];
                $subjects[] = format_response_images($row);
            }
        }

        return ['status' => 'success', 'data' => $subjects];
    }
}

if (!function_exists('studentGetMySubscribedTeachers')) {
    function studentGetMySubscribedTeachers($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'success', 'data' => []];
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $teacherIds = $accessible['teacher_ids'];
        if (empty($teacherIds)) {
            return ['status' => 'success', 'data' => []];
        }

        $tListStr = implode(',', $teacherIds);
        $sql = "
            SELECT t.id, t.name, t.email, t.whatsapp, t.specialization, t.experience, t.image, t.rating
            FROM teachers t
            WHERE t.id IN ({$tListStr}) AND t.is_active = 1
            ORDER BY t.id ASC
        ";
        $res = mysqli_query($con, $sql);
        $teachers = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['rating'] = (float)$row['rating'];
                $teachers[] = format_response_images($row);
            }
        }

        return ['status' => 'success', 'data' => $teachers];
    }
}

if (!function_exists('teacherGetSubscribedStudents')) {
    function teacherGetSubscribedStudents($con, $teacherData = null) {
        $tId = 0;
        if (is_array($teacherData)) {
            $tId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        } elseif (is_object($teacherData)) {
            $tId = (int)($teacherData->id ?? $teacherData->teacher_id ?? 0);
        }
        if ($tId <= 0) {
            $tId = (int)($_GET['teacher_id'] ?? 0);
        }
        if ($tId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication is required'];
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $now = date('Y-m-d H:i:s');

        $tCoursesRes = mysqli_query($con, "SELECT DISTINCT c.id FROM courses c LEFT JOIN course_teachers ct ON c.id = ct.course_id WHERE (c.teacher_id = {$tId} OR (c.created_by_type = 'teacher' AND c.created_by_id = {$tId}) OR (ct.teacher_id = {$tId} AND ct.is_active = 1))");
        $tCourseIds = [];
        if ($tCoursesRes) {
            while ($r = mysqli_fetch_assoc($tCoursesRes)) {
                $tCourseIds[] = (int)$r['id'];
            }
        }

        $tSubjRes = mysqli_query($con, "SELECT subject_id FROM teacher_subjects WHERE teacher_id = {$tId}");
        $tSubjectIds = [];
        if ($tSubjRes) {
            while ($r = mysqli_fetch_assoc($tSubjRes)) {
                $tSubjectIds[] = (int)$r['subject_id'];
            }
        }

        $whereConditions = [
            "ss.is_active = 1",
            "(ss.end_date IS NULL OR ss.end_date >= '{$now}')"
        ];

        $subQueryParts = ["(sst.teacher_id = {$tId})"];
        if (!empty($tCourseIds)) {
            $tcList = implode(',', $tCourseIds);
            $subQueryParts[] = "(ssc.course_id IN ({$tcList}))";
        }
        if (!empty($tSubjectIds)) {
            $tsList = implode(',', $tSubjectIds);
            $subQueryParts[] = "(sss.subject_id IN ({$tsList}))";
        }

        $whereConditions[] = "(" . implode(" OR ", $subQueryParts) . ")";

        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereConditions[] = "(st.name LIKE '{$sp}' OR st.email LIKE '{$sp}' OR st.phone LIKE '{$sp}')";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $countSql = "
            SELECT COUNT(DISTINCT st.id) AS total
            FROM student_subscriptions ss
            JOIN students st ON ss.student_id = st.id
            LEFT JOIN student_subscription_teachers sst ON ss.id = sst.student_subscription_id
            LEFT JOIN student_subscription_courses ssc ON ss.id = ssc.student_subscription_id
            LEFT JOIN student_subscription_subjects sss ON ss.id = sss.student_subscription_id
            {$whereClause}
        ";
        $countRes = mysqli_query($con, $countSql);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT DISTINCT st.id, st.name, st.email, st.phone, st.image, st.academic_year_id, st.is_active, st.created_at
            FROM student_subscriptions ss
            JOIN students st ON ss.student_id = st.id
            LEFT JOIN student_subscription_teachers sst ON ss.id = sst.student_subscription_id
            LEFT JOIN student_subscription_courses ssc ON ss.id = ssc.student_subscription_id
            LEFT JOIN student_subscription_subjects sss ON ss.id = sss.student_subscription_id
            {$whereClause}
            ORDER BY st.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        $students = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['academic_year_id'] = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
                $row['is_active'] = (int)$row['is_active'];
                $students[] = format_response_images($row);
            }
        }

        return build_admin_paginated_response($students, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}
