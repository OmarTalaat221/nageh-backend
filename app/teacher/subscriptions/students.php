<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../content/helpers.php';
require_once __DIR__ . '/../../user/student/subscriptions/manage.php';

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

        fix_null_subscription_end_dates($con);

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

        $tSubjRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM teacher_subjects WHERE teacher_id = {$tId}");
        $tSubjectIds = [];
        if ($tSubjRes) {
            while ($r = mysqli_fetch_assoc($tSubjRes)) {
                if ($r['subject_id']) $tSubjectIds[] = (int)$r['subject_id'];
            }
        }
        $tCourseSubjRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM courses WHERE (teacher_id = {$tId} OR (created_by_type = 'teacher' AND created_by_id = {$tId})) AND subject_id IS NOT NULL");
        if ($tCourseSubjRes) {
            while ($r = mysqli_fetch_assoc($tCourseSubjRes)) {
                if ($r['subject_id']) $tSubjectIds[] = (int)$r['subject_id'];
            }
        }
        $tSubjectIds = array_values(array_unique($tSubjectIds));

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

        // Narrows the list to the students of one specific course or package, which is what the
        // "set this student's dates" screen needs - without it a teacher can only see all of
        // their students at once.
        $filterCourseId = (int)($_GET['course_id'] ?? 0);
        if ($filterCourseId > 0) {
            $whereConditions[] = "ss.id IN (SELECT student_subscription_id FROM student_subscription_courses WHERE course_id = {$filterCourseId})";
        }

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

        $hasAcademicYear = false;
        $colCheck = mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'academic_year_id'");
        if ($colCheck && mysqli_num_rows($colCheck) > 0) {
            $hasAcademicYear = true;
        }
        $yearColSql = $hasAcademicYear ? "st.academic_year_id" : "NULL AS academic_year_id";

        $sql = "
            SELECT DISTINCT st.id, st.name, st.email, st.phone, st.image, {$yearColSql}, st.is_active, st.created_at
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

                // The package this student and this teacher actually share, if any - so the
                // teacher's dashboard can offer "schedule this student's sessions" without first
                // making them re-pick a course they were already looking at.
                $studentId = $row['id'];
                $pkgRes = mysqli_query($con, "
                    SELECT c.id, COALESCE(ct.name, c.slug) AS name
                    FROM student_subscriptions ss2
                    JOIN student_subscription_courses ssc2 ON ssc2.student_subscription_id = ss2.id
                    JOIN courses c ON c.id = ssc2.course_id
                    LEFT JOIN courses_translations ct ON ct.course_id = c.id AND ct.language_id = 1
                    WHERE ss2.student_id = {$studentId} AND ss2.is_active = 1
                      AND c.kind = 'package'
                      AND (c.teacher_id = {$tId} OR EXISTS (
                          SELECT 1 FROM course_teachers ctt
                          WHERE ctt.course_id = c.id AND ctt.teacher_id = {$tId} AND ctt.is_active = 1
                      ))
                    LIMIT 1
                ");
                $pkgRow = $pkgRes ? mysqli_fetch_assoc($pkgRes) : null;
                $row['package_course_id'] = $pkgRow ? (int)$pkgRow['id'] : null;
                $row['package_course_name'] = $pkgRow['name'] ?? null;

                $students[] = format_response_images($row);
            }
        }

        return build_admin_paginated_response($students, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}
