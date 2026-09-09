<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../content/helpers.php';
require_once __DIR__ . '/../../../../helpers/admin_notifications.php';

if (!function_exists('student_video_progress_table_exists')) {
    function student_video_progress_table_exists($con): bool {
        static $exists = null;
        if ($exists === null) {
            $chk = @mysqli_query($con, "SHOW TABLES LIKE 'student_video_progress'");
            $exists = $chk && mysqli_num_rows($chk) > 0;
        }
        return $exists;
    }
}

if (!function_exists('get_student_course_progress_stats')) {
    function get_student_course_progress_stats($con, $stId, $cId): array {
        $cId = (int)$cId;
        $stId = (int)$stId;

        $totalVidRes = mysqli_query($con, "
            SELECT 
                COUNT(v.id) AS total_lessons,
                COALESCE(SUM(v.duration_seconds), 0) AS total_duration
            FROM videos v 
            JOIN units u ON v.unit_id = u.id 
            WHERE u.course_id = {$cId} AND u.is_active = 1 AND v.is_active = 1
        ");

        $totalLessons = 0;
        $totalDuration = 0;
        if ($totalVidRes && $row = mysqli_fetch_assoc($totalVidRes)) {
            $totalLessons = (int)($row['total_lessons'] ?? 0);
            $totalDuration = (int)($row['total_duration'] ?? 0);
        }

        $completedLessons = 0;
        $watchedDuration = 0;

        if ($stId > 0 && student_video_progress_table_exists($con)) {
            $progRes = mysqli_query($con, "
                SELECT 
                    COUNT(DISTINCT CASE WHEN svp.is_completed = 1 THEN svp.video_id END) AS completed_lessons,
                    COALESCE(SUM(
                        CASE 
                            WHEN svp.is_completed = 1 THEN GREATEST(LEAST(svp.watched_seconds, v.duration_seconds), v.duration_seconds)
                            ELSE LEAST(svp.watched_seconds, v.duration_seconds)
                        END
                    ), 0) AS watched_duration
                FROM student_video_progress svp 
                JOIN videos v ON svp.video_id = v.id 
                JOIN units u ON v.unit_id = u.id 
                WHERE svp.student_id = {$stId} AND u.course_id = {$cId} AND u.is_active = 1 AND v.is_active = 1
            ");
            if ($progRes && $pRow = mysqli_fetch_assoc($progRes)) {
                $completedLessons = (int)($pRow['completed_lessons'] ?? 0);
                $watchedDuration = (int)($pRow['watched_duration'] ?? 0);
            }
        }

        $progressPct = 0.00;
        if ($totalDuration > 0) {
            $progressPct = min(100.00, round(($watchedDuration / $totalDuration) * 100, 2));
        } elseif ($totalLessons > 0) {
            $progressPct = round(($completedLessons / $totalLessons) * 100, 2);
        }

        return [
            'total_lessons_count' => $totalLessons,
            'completed_lessons_count' => $completedLessons,
            'total_duration_seconds' => $totalDuration,
            'watched_duration_seconds' => $watchedDuration,
            'progress_percentage' => $progressPct
        ];
    }
}

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

if (!function_exists('studentRedeemSubscriptionCode')) {
    function studentRedeemSubscriptionCode($con, $studentData = null, ?string $codeOverride = null) {
        $body = read_json_body();
        $codeStr = strtoupper(trim($codeOverride ?? ($body['code'] ?? '')));
        if (empty($codeStr)) {
            return ['status' => 'error', 'message' => trans('Subscription activation code is required')];
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
            return ['status' => 'error', 'message' => trans('Student authentication is required to redeem code')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, title, price, max_uses, used_count, valid_from, valid_to, duration_days, is_active FROM subscription_codes WHERE code = ? LIMIT 1");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Query preparation error'];
        }
        mysqli_stmt_bind_param($stmt, "s", $codeStr);
        mysqli_stmt_execute($stmt);
        $codeRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$codeRow) {
            return ['status' => 'error', 'message' => trans('Invalid subscription code')];
        }
        if ((int)$codeRow['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('Subscription code is inactive')];
        }

        $now = date('Y-m-d H:i:s');
        if ($codeRow['valid_from'] && $codeRow['valid_from'] > $now) {
            return ['status' => 'error', 'message' => trans('Subscription code is not active yet')];
        }
        if ($codeRow['valid_to'] && $codeRow['valid_to'] < $now) {
            return ['status' => 'error', 'message' => trans('Subscription code has expired')];
        }
        if ((int)$codeRow['max_uses'] > 0 && (int)$codeRow['used_count'] >= (int)$codeRow['max_uses']) {
            return ['status' => 'error', 'message' => trans('Subscription code has already been used and cannot be used again (Single-use code)')];
        }

        $codeId = (int)$codeRow['id'];
        $alreadyRedeemedStmt = mysqli_prepare($con, "SELECT id FROM code_redemptions_log WHERE subscription_code_id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($alreadyRedeemedStmt, "ii", $codeId, $stId);
        mysqli_stmt_execute($alreadyRedeemedStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($alreadyRedeemedStmt))) {
            return ['status' => 'error', 'message' => trans('You have already redeemed this subscription code')];
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
            return ['status' => 'error', 'message' => trans('This subscription code has no linked content or targets')];
        }

        $subType = 'custom';
        if (!empty($courses) && empty($subjects) && empty($teachers)) $subType = 'course';
        if (!empty($subjects) && empty($courses) && empty($teachers)) $subType = 'subject';
        if (!empty($teachers) && empty($courses) && empty($subjects)) $subType = 'teacher';

        $durationDays = $codeRow['duration_days'] ? (int)$codeRow['duration_days'] : null;
        $startDate = date('Y-m-d H:i:s');
        $endDate = null;
        if ($durationDays && $durationDays > 0) {
            $endDate = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
        } elseif (!empty($codeRow['valid_to'])) {
            $endDate = date('Y-m-d H:i:s', strtotime($codeRow['valid_to']));
        }

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

        mysqli_query($con, "UPDATE subscription_codes SET used_count = used_count + 1, claimed_by_student_id = COALESCE(claimed_by_student_id, {$stId}) WHERE id = {$codeId}");

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $logStmt = mysqli_prepare($con, "INSERT INTO code_redemptions_log (subscription_code_id, student_id, student_subscription_id, ip_address) VALUES (?, ?, ?, ?)");
        if ($logStmt) {
            mysqli_stmt_bind_param($logStmt, "iiis", $codeId, $stId, $subId, $ipAddress);
            mysqli_stmt_execute($logStmt);
        }

        // If referral_code / ref is passed in body, auto-link referrer if student is not yet linked
        $passedRefCode = trim($body['referral_code'] ?? $body['ref'] ?? $body['affiliate_code'] ?? '');
        if (!empty($passedRefCode)) {
            $checkSt = mysqli_prepare($con, "SELECT referred_by_student_id FROM students WHERE id = ? LIMIT 1");
            if ($checkSt) {
                mysqli_stmt_bind_param($checkSt, "i", $stId);
                mysqli_stmt_execute($checkSt);
                $curStRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkSt));
                if (empty($curStRow['referred_by_student_id'])) {
                    $refStStmt = mysqli_prepare($con, "SELECT id FROM students WHERE affiliate_code = ? LIMIT 1");
                    if ($refStStmt) {
                        mysqli_stmt_bind_param($refStStmt, "s", $passedRefCode);
                        mysqli_stmt_execute($refStStmt);
                        $refStRow = mysqli_fetch_assoc(mysqli_stmt_get_result($refStStmt));
                        if ($refStRow && (int)$refStRow['id'] !== $stId) {
                            $refId = (int)$refStRow['id'];
                            mysqli_query($con, "UPDATE students SET referred_by_student_id = {$refId} WHERE id = {$stId}");
                        }
                    }
                }
            }
        }

        // Trigger ONE single affiliate commission request for referrer student upon code redemption
        require_once __DIR__ . '/../affiliate/manage.php';
        if (function_exists('createPendingAffiliateCommission')) {
            $codePrice = (float)($codeRow['price'] ?? 0.00);

            // If code price is 0, sum the prices of linked courses
            if ($codePrice <= 0 && !empty($courses)) {
                $cIdsClean = array_map('intval', $courses);
                $cIdsStr = implode(',', $cIdsClean);
                if (!empty($cIdsStr)) {
                    $sumRes = mysqli_query($con, "SELECT SUM(price) AS total_price FROM courses WHERE id IN ({$cIdsStr})");
                    if ($sumRes && $sumRow = mysqli_fetch_assoc($sumRes)) {
                        $codePrice = (float)($sumRow['total_price'] ?? 0.00);
                    }
                }
            }

            createPendingAffiliateCommission($con, $stId, 'subscription_code', (int)$codeId, $codePrice);
        }

        if (!empty($courses)) {
            notify_of_course_subscription($con, $stId, $courses, 'كود اشتراك');
        }

        return [
            'status' => 'success',
            'message' => trans('Subscription code redeemed and activated successfully'),
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

if (!function_exists('fix_null_subscription_end_dates')) {
    function fix_null_subscription_end_dates($con) {
        static $fixed = false;
        if ($fixed) return;
        $sql = "
            UPDATE student_subscriptions ss
            JOIN subscription_codes sc ON ss.subscription_code_id = sc.id
            SET ss.end_date = CASE 
                WHEN sc.duration_days IS NOT NULL AND sc.duration_days > 0 
                    THEN DATE_ADD(ss.start_date, INTERVAL sc.duration_days DAY)
                WHEN sc.valid_to IS NOT NULL 
                    THEN sc.valid_to
                ELSE ss.end_date
            END
            WHERE ss.end_date IS NULL AND ss.subscription_code_id IS NOT NULL
        ";
        @mysqli_query($con, $sql);
        $fixed = true;
    }
}

if (!function_exists('get_student_accessible_entities')) {
    function get_student_accessible_entities($con, int $studentId): array {
        if ($studentId <= 0) {
            return ['course_ids' => [], 'subject_ids' => [], 'teacher_ids' => []];
        }
        fix_null_subscription_end_dates($con);
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
        $derivedCourseIds = $directCourseIds;
        $derivedTeacherIds = $directTeacherIds;

        $studentGradeId = 0;
        $stGradeRes = mysqli_query($con, "SELECT academic_year_id FROM students WHERE id = {$studentId} LIMIT 1");
        if ($stGradeRes && ($stGradeRow = mysqli_fetch_assoc($stGradeRes))) {
            $studentGradeId = (int)($stGradeRow['academic_year_id'] ?? 0);
        }
        $gradeFilterSql = $studentGradeId > 0
            ? " AND (c.academic_year_id IS NULL OR c.academic_year_id = {$studentGradeId})"
            : "";
        $gradeFilterSqlNoAlias = $studentGradeId > 0
            ? " AND (academic_year_id IS NULL OR academic_year_id = {$studentGradeId})"
            : "";

        if (!empty($directCourseIds)) {
            $dcListStr = implode(',', array_unique($directCourseIds));
            $csRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM courses WHERE id IN ({$dcListStr}) AND subject_id IS NOT NULL");
            if ($csRes) {
                while ($row = mysqli_fetch_assoc($csRes)) {
                    $derivedSubjectIds[] = (int)$row['subject_id'];
                }
            }
        }

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
        $derivedSubjectIds = array_values(array_unique(array_filter($derivedSubjectIds)));

        if (!empty($directSubjectIds)) {
            $dsListStr = implode(',', array_unique($directSubjectIds));

            $scRes = mysqli_query($con, "SELECT id FROM courses WHERE subject_id IN ({$dsListStr}) AND is_active = 1{$gradeFilterSqlNoAlias}");
            if ($scRes) {
                while ($row = mysqli_fetch_assoc($scRes)) {
                    $derivedCourseIds[] = (int)$row['id'];
                }
            }
            $stRes = mysqli_query($con, "SELECT DISTINCT teacher_id FROM teacher_subjects WHERE subject_id IN ({$dsListStr})");
            if ($stRes) {
                while ($row = mysqli_fetch_assoc($stRes)) {
                    $derivedTeacherIds[] = (int)$row['teacher_id'];
                }
            }
        }

        if (!empty($directTeacherIds)) {
            $dtListStr = implode(',', $directTeacherIds);
            $tcrRes = mysqli_query($con, "SELECT DISTINCT c.id FROM courses c LEFT JOIN course_teachers ct ON c.id = ct.course_id WHERE (c.teacher_id IN ({$dtListStr}) OR (c.created_by_type = 'teacher' AND c.created_by_id IN ({$dtListStr})) OR (ct.teacher_id IN ({$dtListStr}) AND ct.is_active = 1)) AND c.is_active = 1{$gradeFilterSql}");
            if ($tcrRes) {
                while ($row = mysqli_fetch_assoc($tcrRes)) {
                    $derivedCourseIds[] = (int)$row['id'];
                }
            }
        }
        $derivedCourseIds = array_values(array_unique(array_filter($derivedCourseIds)));
        $derivedTeacherIds = array_values(array_unique(array_filter($derivedTeacherIds)));

        return [
            'course_ids' => $derivedCourseIds,
            'subject_ids' => $derivedSubjectIds,
            'teacher_ids' => $derivedTeacherIds
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
        $_GET['is_active'] = 1;
        require_once __DIR__ . '/../../../admin/courses/manage.php';
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

if (!function_exists('studentGetMySubscribedCoursesProgress')) {
    function studentGetMySubscribedCoursesProgress($con, $studentData = null) {
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

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $body = read_json_body();
        $typeFilter = strtolower(trim($_GET['type'] ?? $_GET['course_type'] ?? $_GET['type_id'] ?? $body['type'] ?? $body['course_type'] ?? ''));
        $kindFilter = strtolower(trim($_GET['kind'] ?? $body['kind'] ?? ''));

        $cListStr = implode(',', array_map('intval', $courseIds));
        $whereSearch = "";
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereSearch = "AND (c.slug LIKE '{$sp}' OR ct.name LIKE '{$sp}')";
        }
        if (!empty($typeFilter)) {
            $tp = mysqli_real_escape_string($con, $typeFilter);
            $whereSearch .= " AND c.type = '{$tp}'";
        }
        if (in_array($kindFilter, ['normal', 'package'], true)) {
            $whereSearch .= " AND c.kind = '{$kindFilter}'";
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $countSql = "SELECT COUNT(DISTINCT c.id) AS total FROM courses c LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId} WHERE c.id IN ({$cListStr}) AND c.is_active = 1 {$whereSearch}";
        $countRes = mysqli_query($con, $countSql);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT c.id, c.subject_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.kind, c.price, c.image, c.is_active, c.created_at,
                   COALESCE(ct.name, c.slug) AS name, ct.description
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            WHERE c.id IN ({$cListStr}) AND c.is_active = 1 {$whereSearch}
            ORDER BY c.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        $courses = [];

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $cId = (int)$row['id'];
                $row['id'] = $cId;
                $row['type'] = $row['type'] ?? 'recorded';
                $row['kind'] = $row['kind'] ?? 'normal';
                $row['price'] = (float)$row['price'];
                $row['is_subscribed'] = true;

                $stats = get_student_course_progress_stats($con, $stId, $cId);
                $row['total_lessons_count'] = $stats['total_lessons_count'];
                $row['completed_lessons_count'] = $stats['completed_lessons_count'];
                $row['progress_percentage'] = $stats['progress_percentage'];

                $courses[] = format_response_images($row);
            }
        }

        return build_admin_paginated_response($courses, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('studentGetMySubscribedBooks')) {
    function studentGetMySubscribedBooks($con, $studentData = null) {
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
        $subjectIds = $accessible['subject_ids'];
        $teacherIds = $accessible['teacher_ids'];

        $directBookIds = [];
        $dbRes = mysqli_query($con, "SELECT DISTINCT book_id FROM student_book_subscriptions WHERE student_id = {$stId} AND is_active = 1");
        if ($dbRes) {
            while ($row = mysqli_fetch_assoc($dbRes)) {
                $directBookIds[] = (int)$row['book_id'];
            }
        }

        if (empty($courseIds) && empty($subjectIds) && empty($teacherIds) && empty($directBookIds)) {
            return build_admin_paginated_response([], 1, 10, 0, ['total_count' => 0]);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $body = read_json_body();
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $body['search'] ?? $body['q'] ?? '');
        $unitId = (int)($_GET['unit_id'] ?? $body['unit_id'] ?? $_REQUEST['unit_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? $body['subject_id'] ?? $_REQUEST['subject_id'] ?? 0);
        $courseId = (int)($_GET['course_id'] ?? $body['course_id'] ?? $_REQUEST['course_id'] ?? 0);

        $orConditions = [];
        if (!empty($courseIds)) {
            $cList = implode(',', array_map('intval', $courseIds));
            $orConditions[] = "b.course_id IN ({$cList})";
        }
        if (!empty($subjectIds)) {
            $sList = implode(',', array_map('intval', $subjectIds));
            $orConditions[] = "b.subject_id IN ({$sList})";
        }
        if (!empty($directBookIds)) {
            $bList = implode(',', array_map('intval', $directBookIds));
            $orConditions[] = "b.id IN ({$bList})";
        }
        if (!empty($teacherIds)) {
            $tList = implode(',', array_map('intval', $teacherIds));
            $orConditions[] = "(b.created_by_type = 'teacher' AND b.created_by_id IN ({$tList}))";
            $orConditions[] = "b.id IN (SELECT book_id FROM book_teachers WHERE teacher_id IN ({$tList}) AND is_active = 1)";
        }

        $subClause = "(" . implode(" OR ", $orConditions) . ")";
        $whereSearch = "";
        if ($unitId > 0) {
            $whereSearch .= " AND b.unit_id = {$unitId}";
        }
        if ($courseId > 0) {
            $whereSearch .= " AND (b.course_id = {$courseId} OR b.unit_id IN (SELECT id FROM units WHERE course_id = {$courseId}))";
        }
        if ($subjectId > 0) {
            $whereSearch .= " AND (b.subject_id = {$subjectId} OR b.course_id IN (SELECT id FROM courses WHERE subject_id = {$subjectId}) OR b.unit_id IN (SELECT u.id FROM units u JOIN courses c ON u.course_id = c.id WHERE c.subject_id = {$subjectId}))";
        }
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereSearch .= " AND (bt.title LIKE '{$sp}' OR bt.description LIKE '{$sp}')";
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $countSql = "
            SELECT COUNT(DISTINCT b.id) AS total
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            WHERE b.is_active = 1 AND {$subClause} {$whereSearch}
        ";
        $countRes = mysqli_query($con, $countSql);
        if ($countRes === false) {
            return ['status' => 'error', 'message' => 'Books count query failed: ' . mysqli_error($con)];
        }
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT DISTINCT b.id, b.unit_id, b.subject_id, b.course_id, b.file_type, b.extension, b.link, b.file_path, b.is_active, b.created_at,
                   COALESCE(bt.title, b.file_path) AS title, bt.description
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            WHERE b.is_active = 1 AND {$subClause} {$whereSearch}
            ORDER BY b.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        if ($res === false) {
            return ['status' => 'error', 'message' => 'Books query failed: ' . mysqli_error($con)];
        }
        $books = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['unit_id'] = $row['unit_id'] ? (int)$row['unit_id'] : null;
            $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
            $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
            $row['is_active'] = (int)$row['is_active'];
            $row['file_path'] = format_image_url($row['file_path'] ?: $row['link']);
            $row['own'] = true;
            $row['is_subscribed'] = true;
            $books[] = function_exists('enrich_student_book_details') ? enrich_student_book_details($con, $row) : format_response_images($row);
        }

        return build_admin_paginated_response($books, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('studentGetMyCertificates')) {
    function studentGetMyCertificates($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $academicYearId = null;
        $issuedCerts = [];

        if ($stId > 0) {
            $chkAy = @mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'academic_year_id'");
            if ($chkAy && mysqli_num_rows($chkAy) === 0) {
                @mysqli_query($con, "ALTER TABLE students ADD COLUMN `academic_year_id` bigint(20) UNSIGNED DEFAULT NULL");
            }
            $stStmt = mysqli_prepare($con, "SELECT academic_year_id FROM students WHERE id = ? LIMIT 1");
            if ($stStmt) {
                mysqli_stmt_bind_param($stStmt, "i", $stId);
                mysqli_stmt_execute($stStmt);
                $stRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));
                $academicYearId = $stRow['academic_year_id'] ? (int)$stRow['academic_year_id'] : null;
            }

            $issuedRes = mysqli_query($con, "
                SELECT sc.id AS student_certificate_id, sc.certificate_code, sc.issue_date, sc.grade_obtained, sc.pdf_url,
                       c.id AS certificate_id, c.academic_year_id, c.score, c.certificate_type, c.code, c.image,
                       COALESCE(ct.name, c.code) AS name, ct.description
                FROM student_certificates sc
                JOIN certificates c ON sc.certificate_id = c.id
                LEFT JOIN certificates_translations ct ON c.id = ct.certificate_id AND ct.language_id = 1
                WHERE sc.student_id = {$stId}
                ORDER BY sc.id DESC
            ");
            if ($issuedRes) {
                while ($row = mysqli_fetch_assoc($issuedRes)) {
                    $row['student_certificate_id'] = (int)$row['student_certificate_id'];
                    $row['certificate_id'] = (int)$row['certificate_id'];
                    $row['academic_year_id'] = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
                    $row['score'] = (float)$row['score'];
                    $row['grade_obtained'] = $row['grade_obtained'] ? (float)$row['grade_obtained'] : null;
                    $row['is_issued'] = true;
                    $issuedCerts[] = format_response_images($row);
                }
            }
        }

        $ayClause = "c.academic_year_id IS NULL";
        if ($academicYearId !== null) {
            $ayClause = "(c.academic_year_id IS NULL OR c.academic_year_id = {$academicYearId})";
        }

        $availableCerts = [];
        $availRes = mysqli_query($con, "
            SELECT c.id, c.academic_year_id, c.score, c.certificate_type, c.code, c.image, c.is_active, c.created_at,
                   COALESCE(ct.name, c.code) AS name, ct.description
            FROM certificates c
            LEFT JOIN certificates_translations ct ON c.id = ct.certificate_id AND ct.language_id = 1
            WHERE c.is_active = 1 AND {$ayClause}
            ORDER BY c.id DESC
        ");
        if ($availRes) {
            while ($row = mysqli_fetch_assoc($availRes)) {
                $row['id'] = (int)$row['id'];
                $row['academic_year_id'] = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
                $row['score'] = (float)$row['score'];
                $row['is_active'] = (int)$row['is_active'];
                $row['is_issued'] = false;
                $availableCerts[] = format_response_images($row);
            }
        }

        return [
            'status' => 'success',
            'data' => [
                'issued_certificates' => $issuedCerts,
                'available_certificates' => $availableCerts
            ]
        ];
    }
}

if (!function_exists('studentUpdateVideoProgress')) {
    function studentUpdateVideoProgress($con, $studentData = null) {
        $body = read_json_body();
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
            return ['status' => 'error', 'message' => trans('Student authentication is required')];
        }

        $videoId = (int)($body['video_id'] ?? 0);
        if ($videoId <= 0) {
            return ['status' => 'error', 'message' => trans('video_id is required')];
        }

        $vStmt = mysqli_prepare($con, "SELECT id, duration_seconds AS duration, unit_id FROM videos WHERE id = ? LIMIT 1");
        if (!$vStmt) {
            return ['status' => 'error', 'message' => 'Query preparation error: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($vStmt, "i", $videoId);
        mysqli_stmt_execute($vStmt);
        $vRow = mysqli_fetch_assoc(mysqli_stmt_get_result($vStmt));
        if (!$vRow) {
            return ['status' => 'error', 'message' => trans('Video not found')];
        }

        $unitId = (int)$vRow['unit_id'];
        $uStmt = mysqli_prepare($con, "SELECT course_id FROM units WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($uStmt, "i", $unitId);
        mysqli_stmt_execute($uStmt);
        $uRow = mysqli_fetch_assoc(mysqli_stmt_get_result($uStmt));
        $courseId = $uRow ? (int)$uRow['course_id'] : 0;

        $accessible = get_student_accessible_entities($con, $stId);
        if (!in_array($courseId, $accessible['course_ids'])) {
            return ['status' => 'error', 'message' => trans('You are not subscribed to the course containing this video')];
        }

        $watchedSeconds = max(0, (int)($body['watched_seconds'] ?? 0));
        $totalSeconds = max(0, (int)($body['total_seconds'] ?? $vRow['duration'] ?? 0));
        $lastPosSeconds = max(0, (int)($body['last_position_seconds'] ?? $watchedSeconds));
        $isCompleted = isset($body['is_completed']) ? (int)$body['is_completed'] : 0;

        if ($totalSeconds > 0 && $watchedSeconds >= ($totalSeconds * 0.9)) {
            $isCompleted = 1;
        }

        $completedAt = $isCompleted === 1 ? date('Y-m-d H:i:s') : null;

        $stmt = mysqli_prepare($con, "
            INSERT INTO student_video_progress (student_id, video_id, is_completed, watched_seconds, total_seconds, last_position_seconds, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                is_completed = IF(is_completed = 1, 1, VALUES(is_completed)),
                watched_seconds = GREATEST(watched_seconds, VALUES(watched_seconds)),
                total_seconds = VALUES(total_seconds),
                last_position_seconds = VALUES(last_position_seconds),
                completed_at = IF(is_completed = 1 AND completed_at IS NOT NULL, completed_at, VALUES(completed_at))
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare progress update statement: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "iiiiiis", $stId, $videoId, $isCompleted, $watchedSeconds, $totalSeconds, $lastPosSeconds, $completedAt);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update video progress: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => trans('Video progress updated successfully'),
            'data' => [
                'student_id' => $stId,
                'video_id' => $videoId,
                'is_completed' => (bool)$isCompleted,
                'watched_seconds' => $watchedSeconds,
                'total_seconds' => $totalSeconds,
                'last_position_seconds' => $lastPosSeconds
            ]
        ];
    }
}

if (!function_exists('studentTrackBookOpen')) {
    function studentTrackBookOpen($con, $studentData = null) {
        $body = read_json_body();
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
            return ['status' => 'error', 'message' => trans('Student authentication is required')];
        }

        $bookId = (int)($body['book_id'] ?? 0);
        if ($bookId <= 0) {
            return ['status' => 'error', 'message' => trans('book_id is required')];
        }

        $bStmt = mysqli_prepare($con, "SELECT id, unit_id, subject_id, course_id, created_by_type, created_by_id FROM course_books WHERE id = ? LIMIT 1");
        if (!$bStmt) {
            return ['status' => 'error', 'message' => 'Query preparation error: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($bStmt, "i", $bookId);
        mysqli_stmt_execute($bStmt);
        $bRow = mysqli_fetch_assoc(mysqli_stmt_get_result($bStmt));
        if (!$bRow) {
            return ['status' => 'error', 'message' => trans('Book not found')];
        }

        $courseId = (int)($bRow['course_id'] ?? 0);
        $subjectId = (int)($bRow['subject_id'] ?? 0);

        $accessible = get_student_accessible_entities($con, $stId);
        $isSubscribed = (
            in_array($courseId, $accessible['course_ids']) ||
            in_array($subjectId, $accessible['subject_ids']) ||
            ($bRow['created_by_type'] === 'teacher' && in_array((int)$bRow['created_by_id'], $accessible['teacher_ids']))
        );

        if (!$isSubscribed) {
            return ['status' => 'error', 'message' => trans('You are not subscribed to the content containing this book')];
        }

        $lastPageNumber = max(1, (int)($body['last_page_number'] ?? $body['page'] ?? 1));
        $totalPages = max(0, (int)($body['total_pages'] ?? 0));
        $now = date('Y-m-d H:i:s');

        $chk = @mysqli_query($con, "SHOW TABLES LIKE 'student_book_progress'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            return ['status' => 'error', 'message' => "The 'student_book_progress' table does not exist on this database - book-open tracking is not set up yet"];
        }

        $stmt = mysqli_prepare($con, "
            INSERT INTO student_book_progress (student_id, book_id, open_count, last_page_number, total_pages, last_opened_at)
            VALUES (?, ?, 1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                open_count = open_count + 1,
                last_page_number = VALUES(last_page_number),
                total_pages = GREATEST(total_pages, VALUES(total_pages)),
                last_opened_at = VALUES(last_opened_at)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare book tracking statement: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "iiiis", $stId, $bookId, $lastPageNumber, $totalPages, $now);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to log book opening: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => trans('Book opening tracked successfully'),
            'data' => [
                'student_id' => $stId,
                'book_id' => $bookId,
                'last_page_number' => $lastPageNumber,
                'total_pages' => $totalPages,
                'last_opened_at' => $now
            ]
        ];
    }
}



if (!function_exists('studentGetAllCoursesWithOwn')) {
    function studentGetAllCoursesWithOwn($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $accessibleCourseIds = $accessible['course_ids'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
        $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
        $body = read_json_body();
        $typeFilter = strtolower(trim($_GET['type'] ?? $_GET['course_type'] ?? $_GET['type_id'] ?? $body['type'] ?? $body['course_type'] ?? ''));
        $kindFilter = strtolower(trim($_GET['kind'] ?? $body['kind'] ?? ''));

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $whereConditions = ["c.is_active = 1"];
        if ($subjectId > 0) $whereConditions[] = "c.subject_id = {$subjectId}";
        if ($teacherId > 0) $whereConditions[] = "(c.teacher_id = {$teacherId} OR (c.created_by_type = 'teacher' AND c.created_by_id = {$teacherId}))";
        if ($academicYearId > 0) $whereConditions[] = "c.academic_year_id = {$academicYearId}";
        if (!empty($typeFilter)) {
            $tp = mysqli_real_escape_string($con, $typeFilter);
            $whereConditions[] = "c.type = '{$tp}'";
        }
        if (in_array($kindFilter, ['normal', 'package'], true)) {
            $whereConditions[] = "c.kind = '{$kindFilter}'";
        }
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereConditions[] = "(c.slug LIKE '{$sp}' OR ct.name LIKE '{$sp}' OR ct.description LIKE '{$sp}')";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $countSql = "SELECT COUNT(DISTINCT c.id) AS total FROM courses c LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId} {$whereClause}";
        $countRes = mysqli_query($con, $countSql);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT c.id, c.subject_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.kind, c.price, c.image, c.is_active, c.created_at,
                   COALESCE(ct.name, c.slug) AS name, ct.description
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            {$whereClause}
            ORDER BY c.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        $courses = [];

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $cId = (int)$row['id'];
                $row['id'] = $cId;
                $row['type'] = $row['type'] ?? 'recorded';
                $row['kind'] = $row['kind'] ?? 'normal';
                $row['price'] = (float)$row['price'];
                $isOwn = in_array($cId, $accessibleCourseIds);
                $row['own'] = $isOwn;
                $row['is_subscribed'] = $isOwn;

                if ($stId > 0 && $isOwn) {
                    $stats = get_student_course_progress_stats($con, $stId, $cId);
                    $row['total_lessons_count'] = $stats['total_lessons_count'];
                    $row['completed_lessons_count'] = $stats['completed_lessons_count'];
                    $row['progress_percentage'] = $stats['progress_percentage'];
                } else {
                    $stats = get_student_course_progress_stats($con, 0, $cId);
                    $row['total_lessons_count'] = $stats['total_lessons_count'];
                    $row['completed_lessons_count'] = 0;
                    $row['progress_percentage'] = 0.00;
                }

                $courses[] = format_response_images($row);
            }
        }

        return build_admin_paginated_response($courses, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('studentGetAllBooksWithOwn')) {
    function studentGetAllBooksWithOwn($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $accessibleCourseIds = $accessible['course_ids'];
        $accessibleSubjectIds = $accessible['subject_ids'];
        $accessibleTeacherIds = $accessible['teacher_ids'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $unitId = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $whereConditions = ["b.is_active = 1"];
        if ($unitId > 0) $whereConditions[] = "b.unit_id = {$unitId}";
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereConditions[] = "(bt.title LIKE '{$sp}' OR bt.description LIKE '{$sp}')";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $countSql = "
            SELECT COUNT(DISTINCT b.id) AS total
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            {$whereClause}
        ";
        $countRes = mysqli_query($con, $countSql);
        if ($countRes === false) {
            return ['status' => 'error', 'message' => 'Books count query failed: ' . mysqli_error($con)];
        }
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT b.id, b.unit_id, b.subject_id, b.course_id, b.file_type, b.extension, b.link, b.file_path,b.*,
                   b.created_by_type, b.created_by_id, b.is_active, b.created_at,
                   COALESCE(bt.title, b.file_path) AS title, bt.description
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            {$whereClause}
            ORDER BY b.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        if ($res === false) {
            return ['status' => 'error', 'message' => 'Books query failed: ' . mysqli_error($con)];
        }
        $books = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $bId = (int)$row['id'];
            $cId = $row['course_id'] ? (int)$row['course_id'] : 0;
            $sId = $row['subject_id'] ? (int)$row['subject_id'] : 0;
            $createdById = (int)$row['created_by_id'];

            $row['id'] = $bId;
            $row['unit_id'] = $row['unit_id'] ? (int)$row['unit_id'] : null;
            $row['subject_id'] = $sId ?: null;
            $row['course_id'] = $cId ?: null;
            $row['is_active'] = (int)$row['is_active'];

            $isOwn = (
                in_array($cId, $accessibleCourseIds) ||
                in_array($sId, $accessibleSubjectIds) ||
                ($row['created_by_type'] === 'teacher' && in_array($createdById, $accessibleTeacherIds))
            );

            $row['own'] = $isOwn;
            $row['is_subscribed'] = $isOwn;

            if ($isOwn) {
                $row['file_path'] = format_image_url($row['file_path'] ?: $row['link']);
            } else {
                $row['file_path'] = null;
                $row['link'] = null;
            }

            $books[] = format_response_images($row);
        }

        return build_admin_paginated_response($books, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}
