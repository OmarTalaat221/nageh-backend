<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../units/manage.php';

if (!function_exists('adminGetExamDetails')) {
    function adminGetExamDetails($con, $examId) {
        if (!$examId) return null;
        $langInfo = get_language_info($con);

        $stmt = mysqli_prepare($con, "SELECT id, subject_id, course_id, unit_id, order_num, duration_minutes, total_marks, pass_marks, is_active, created_by_type, created_by_id, created_at, updated_at FROM course_exams WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $examId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['order_num'] = (int)($row['order_num'] ?? 1);
        $row['unit_id'] = !empty($row['unit_id']) ? (int)$row['unit_id'] : null;
        $row['subject_id'] = !empty($row['subject_id']) ? (int)$row['subject_id'] : null;
        $row['course_id'] = !empty($row['course_id']) ? (int)$row['course_id'] : null;
        $row['duration_minutes'] = (int)$row['duration_minutes'];
        // A duration of 0 means the exam is open-ended: no countdown is shown and it is never
        // auto-submitted when the clock runs out, because there is no clock. Sent as its own flag
        // so no client has to know that 0 carries that meaning.
        $row['has_time_limit'] = $row['duration_minutes'] > 0 ? 1 : 0;
        // An exam attached to no course, no unit and no subject stands on its own: it is offered
        // to every student, subscribed or not.
        $row['is_public'] = (!$row['course_id'] && !$row['unit_id'] && !$row['subject_id']) ? 1 : 0;
        $row['total_marks'] = (int)$row['total_marks'];
        $row['pass_marks'] = (int)$row['pass_marks'];
        $row['is_active'] = (int)$row['is_active'];
        // Explicit alias for `is_active` on this specific table: this IS the "show exam to
        // students" toggle - GET /student/exams filters on is_active = 1, so flipping it off
        // here hides the exam from every student immediately, without touching anything else.
        $row['show_to_students'] = $row['is_active'];
        $row['created_by_id'] = $row['created_by_id'] ? (int)$row['created_by_id'] : null;

        $titleAr = null;
        $titleEn = null;
        $descAr = null;
        $descEn = null;

        $tStmt = mysqli_prepare($con, "SELECT cet.language_id, l.code AS language_code, cet.title, cet.description FROM course_exams_translations cet JOIN languages l ON cet.language_id = l.id WHERE cet.exam_id = ?");
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $examId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                if ($tRow['language_code'] === 'ar') {
                    $titleAr = $tRow['title'];
                    $descAr = $tRow['description'];
                }
                if ($tRow['language_code'] === 'en') {
                    $titleEn = $tRow['title'];
                    $descEn = $tRow['description'];
                }
            }
        }

        $isEn = strtolower($langInfo['code'] ?? 'ar') === 'en';
        $row['title_ar'] = $titleAr;
        $row['title_en'] = $titleEn;
        $row['description_ar'] = $descAr;
        $row['description_en'] = $descEn;
        $row['title'] = $isEn ? ($titleEn ?: $titleAr ?: ('Exam #' . $row['id'])) : ($titleAr ?: $titleEn ?: ('Exam #' . $row['id']));
        $row['description'] = $isEn ? ($descEn ?: $descAr ?: '') : ($descAr ?: $descEn ?: '');

        $row['unit'] = $row['unit_id'] ? get_unit_details($con, $row['unit_id']) : null;
        $courseIdForLookup = $row['course_id'] ?: (int)($row['unit']['course_id'] ?? 0);
        $row['course'] = $courseIdForLookup ? get_course_details($con, $courseIdForLookup) : null;
        $subjectIdForLookup = $row['subject_id'] ?: (int)($row['unit']['subject_id'] ?? 0);
        $row['subject'] = $subjectIdForLookup ? get_subject_details($con, $subjectIdForLookup) : null;

        $row['teachers'] = get_content_teachers_details($con, 'exam', $examId);
        $row['created_by'] = get_course_created_by_details($con, $row['created_by_type'], $row['created_by_id']);

        return format_response_images($row);
    }
}

if (!function_exists('adminListExams')) {
    function adminListExams($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $unitId = isset($_GET['unit_id']) && $_GET['unit_id'] !== '' ? (int)$_GET['unit_id'] : 0;
        $courseId = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : 0;
        $subjectId = isset($_GET['subject_id']) && $_GET['subject_id'] !== '' ? (int)$_GET['subject_id'] : 0;
        $teacherId = isset($_GET['teacher_id']) && $_GET['teacher_id'] !== '' ? (int)$_GET['teacher_id'] : 0;
        // The tabs on the admin exams library page - "unit" = tied to a lesson, "course" = a
        // whole-course exam, "standalone" = no lesson/course/subject at all. Previously done by
        // fetching every single exam in the table and filtering in the browser.
        $scope = in_array(trim($_GET['scope'] ?? ''), ['unit', 'course', 'standalone'], true) ? trim($_GET['scope']) : '';

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $where = ["1=1"];
        if ($scope === 'unit') $where[] = "COALESCE(ce.unit_id, 0) > 0";
        if ($scope === 'course') $where[] = "COALESCE(ce.unit_id, 0) = 0 AND COALESCE(ce.course_id, 0) > 0";
        if ($scope === 'standalone') $where[] = "COALESCE(ce.unit_id, 0) = 0 AND COALESCE(ce.course_id, 0) = 0 AND COALESCE(ce.subject_id, 0) = 0";
        if (isset($_GET['is_active']) && $_GET['is_active'] !== '') $where[] = "ce.is_active = " . ((int)$_GET['is_active'] === 1 ? 1 : 0);
        if ($unitId > 0) $where[] = "ce.unit_id = {$unitId}";
        if ($courseId > 0) $where[] = "(ce.course_id = {$courseId} OR ce.unit_id IN (SELECT id FROM units WHERE course_id = {$courseId}))";
        if ($subjectId > 0) $where[] = "ce.subject_id = {$subjectId}";
        if ($teacherId > 0) {
            $where[] = "(
                (ce.created_by_type = 'teacher' AND ce.created_by_id = {$teacherId})
                OR ce.id IN (SELECT exam_id FROM exam_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR ce.course_id IN (SELECT id FROM courses WHERE teacher_id = {$teacherId} OR (created_by_type = 'teacher' AND created_by_id = {$teacherId}))
                OR ce.course_id IN (SELECT course_id FROM course_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR ce.unit_id IN (SELECT id FROM units WHERE (created_by_type = 'teacher' AND created_by_id = {$teacherId}))
                OR ce.unit_id IN (SELECT unit_id FROM unit_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR ce.subject_id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id = {$teacherId})
                OR (ce.created_by_type = 'admin' AND NOT EXISTS (SELECT 1 FROM exam_teachers et_chk WHERE et_chk.exam_id = ce.id AND et_chk.is_active = 1))
            )";
        }
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $where[] = "EXISTS (SELECT 1 FROM course_exams_translations cet WHERE cet.exam_id = ce.id AND (cet.title LIKE '{$sp}' OR cet.description LIKE '{$sp}'))";
        }
        $whereSql = "WHERE " . implode(" AND ", $where);

        $countRes = mysqli_query($con, "SELECT COUNT(DISTINCT ce.id) AS total FROM course_exams ce {$whereSql}");
        $totalRecords = $countRes ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;

        $sql = "SELECT DISTINCT ce.id FROM course_exams ce {$whereSql} ORDER BY ce.order_num ASC, ce.id ASC LIMIT {$limit} OFFSET {$offset}";
        $res = mysqli_query($con, $sql);

        $exams = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $detail = adminGetExamDetails($con, (int)$row['id']);
                if ($detail) {
                    if ($teacherId > 0) {
                        $detail['can_edit'] = ($detail['created_by_type'] ?? 'admin') !== 'admin';
                    }
                    $exams[] = $detail;
                }
            }
        }

        // Cheap aggregate counts for the stat cards - scoped to the unit/course being viewed but not
        // to search/tab, so the admin exams pages no longer have to fetch every single exam just
        // to total them up in the browser.
        $statWhere = ["1=1"];
        if ($unitId > 0) $statWhere[] = "unit_id = {$unitId}";
        if ($courseId > 0) $statWhere[] = "(course_id = {$courseId} OR unit_id IN (SELECT id FROM units WHERE course_id = {$courseId}))";
        if ($subjectId > 0) $statWhere[] = "subject_id = {$subjectId}";
        $statsRes = mysqli_query($con, "
            SELECT
                COUNT(*) AS total_count,
                SUM(is_active = 1) AS active_count,
                SUM(is_active = 0) AS inactive_count,
                COALESCE(MAX(total_marks), 0) AS max_total_marks
            FROM course_exams
            WHERE " . implode(" AND ", $statWhere));
        $statsRow = $statsRes ? (mysqli_fetch_assoc($statsRes) ?: []) : [];
        $statistics = [
            'total_count' => (int)($statsRow['total_count'] ?? 0),
            'active_count' => (int)($statsRow['active_count'] ?? 0),
            'inactive_count' => (int)($statsRow['inactive_count'] ?? 0),
            'max_total_marks' => (int)($statsRow['max_total_marks'] ?? 0),
        ];

        return build_admin_paginated_response($exams, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetExam')) {
    function adminGetExam($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Exam ID is required'];
        }
        $exam = adminGetExamDetails($con, $id);
        if (!$exam) {
            return ['status' => 'error', 'message' => 'Exam not found'];
        }
        return ['status' => 'success', 'data' => $exam];
    }
}

if (!function_exists('adminCreateExam')) {
    function adminCreateExam($con, $creatorData = null) {
        $body = read_json_body();

        $creatorType = 'admin';
        $creatorId = 1;
        if (is_array($creatorData)) {
            $creatorType = (!empty($creatorData['teacher_id']) || isset($creatorData['specialization']) || ($creatorData['role'] ?? '') === 'teacher' || ($creatorData['user_type'] ?? '') === 'teacher') ? 'teacher' : 'admin';
            $creatorId = (int)($creatorData['id'] ?? $creatorData['teacher_id'] ?? 1);
        } elseif (is_object($creatorData)) {
            $creatorType = (!empty($creatorData->teacher_id) || isset($creatorData->specialization) || ($creatorData->role ?? '') === 'teacher' || ($creatorData->user_type ?? '') === 'teacher') ? 'teacher' : 'admin';
            $creatorId = (int)($creatorData->id ?? $creatorData->teacher_id ?? 1);
        }

        $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
        $titleEn = trim($body['title_en'] ?? $titleAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);

        if ($titleAr === '' && $titleEn === '') {
            return ['status' => 'error', 'message' => 'Exam title is required'];
        }

        $courseId = isset($body['course_id']) && (int)$body['course_id'] > 0 ? (int)$body['course_id'] : null;
        $unitId = isset($body['unit_id']) && (int)$body['unit_id'] > 0 ? (int)$body['unit_id'] : null;
        $subjectId = isset($body['subject_id']) && (int)$body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        // 0 = no time limit. Clamped rather than rejected so a negative number cannot be stored as
        // a duration that no client knows how to render.
        $durationMinutes = isset($body['duration_minutes']) ? max(0, (int)$body['duration_minutes']) : 30;
        $totalMarks = isset($body['total_marks']) ? (int)$body['total_marks'] : 100;
        $passMarks = isset($body['pass_marks']) ? (int)$body['pass_marks'] : 50;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $orderNum = isset($body['order_num']) && (int)$body['order_num'] > 0 ? (int)$body['order_num'] : 1;

        $ceSub = $subjectId ? $subjectId : 'NULL';
        $ceCourse = $courseId ? $courseId : 'NULL';
        $ceUnit = $unitId ? $unitId : 'NULL';

        $sql = "INSERT INTO course_exams (subject_id, course_id, unit_id, order_num, duration_minutes, total_marks, pass_marks, is_active, created_by_type, created_by_id) VALUES ({$ceSub}, {$ceCourse}, {$ceUnit}, {$orderNum}, {$durationMinutes}, {$totalMarks}, {$passMarks}, {$isActive}, '{$creatorType}', {$creatorId})";
        if (!mysqli_query($con, $sql)) {
            return ['status' => 'error', 'message' => 'Failed to create exam: ' . mysqli_error($con)];
        }
        $examId = mysqli_insert_id($con);

        if ($creatorType === 'teacher' && $creatorId > 0) {
            @mysqli_query($con, "INSERT INTO exam_teachers (exam_id, teacher_id, is_active) VALUES ({$examId}, {$creatorId}, 1) ON DUPLICATE KEY UPDATE is_active = 1");
        }

        if ($titleAr !== '' || $descAr !== '') {
            @mysqli_query($con, "INSERT INTO course_exams_translations (exam_id, language_id, title, description) VALUES ({$examId}, 1, '" . mysqli_real_escape_string($con, $titleAr) . "', '" . mysqli_real_escape_string($con, $descAr) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
        }
        if ($titleEn !== '' || $descEn !== '') {
            @mysqli_query($con, "INSERT INTO course_exams_translations (exam_id, language_id, title, description) VALUES ({$examId}, 2, '" . mysqli_real_escape_string($con, $titleEn) . "', '" . mysqli_real_escape_string($con, $descEn) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
        }

        return [
            'status' => 'success',
            'message' => 'Exam created successfully',
            'data' => adminGetExamDetails($con, $examId)
        ];
    }
}

if (!function_exists('adminUpdateExam')) {
    function adminUpdateExam($con, $creatorData = null) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Exam ID is required'];
        }

        $checkRes = mysqli_query($con, "SELECT id FROM course_exams WHERE id = {$id} LIMIT 1");
        if (!$checkRes || !mysqli_fetch_assoc($checkRes)) {
            return ['status' => 'error', 'message' => 'Exam not found'];
        }

        $updateFields = [];
        if (isset($body['duration_minutes'])) {
            $updateFields[] = "duration_minutes = " . max(0, (int)$body['duration_minutes']);
        }
        if (isset($body['total_marks'])) {
            $updateFields[] = "total_marks = " . (int)$body['total_marks'];
        }
        if (isset($body['pass_marks'])) {
            $updateFields[] = "pass_marks = " . (int)$body['pass_marks'];
        }
        if (isset($body['is_active'])) {
            $updateFields[] = "is_active = " . (int)$body['is_active'];
        }
        if (isset($body['order_num'])) {
            $orderVal = (int)$body['order_num'] > 0 ? (int)$body['order_num'] : 1;
            $updateFields[] = "order_num = {$orderVal}";
        }
        if (isset($body['subject_id'])) {
            $subVal = (int)$body['subject_id'] > 0 ? (int)$body['subject_id'] : 'NULL';
            $updateFields[] = "subject_id = {$subVal}";
        }
        if (isset($body['course_id'])) {
            $crsVal = (int)$body['course_id'] > 0 ? (int)$body['course_id'] : 'NULL';
            $updateFields[] = "course_id = {$crsVal}";
        }
        if (array_key_exists('unit_id', $body)) {
            $unitVal = (int)($body['unit_id'] ?? 0) > 0 ? (int)$body['unit_id'] : 'NULL';
            $updateFields[] = "unit_id = {$unitVal}";
        }

        if (!empty($updateFields)) {
            mysqli_query($con, "UPDATE course_exams SET " . implode(", ", $updateFields) . " WHERE id = {$id}");
        }

        if (isset($body['title_ar']) || isset($body['title_en']) || isset($body['title']) || isset($body['description_ar']) || isset($body['description_en'])) {
            $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
            $titleEn = trim($body['title_en'] ?? $titleAr);
            $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
            $descEn = trim($body['description_en'] ?? $descAr);

            if ($titleAr !== '') {
                @mysqli_query($con, "INSERT INTO course_exams_translations (exam_id, language_id, title, description) VALUES ({$id}, 1, '" . mysqli_real_escape_string($con, $titleAr) . "', '" . mysqli_real_escape_string($con, $descAr) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
            }
            if ($titleEn !== '') {
                @mysqli_query($con, "INSERT INTO course_exams_translations (exam_id, language_id, title, description) VALUES ({$id}, 2, '" . mysqli_real_escape_string($con, $titleEn) . "', '" . mysqli_real_escape_string($con, $descEn) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
            }
        }

        return [
            'status' => 'success',
            'message' => 'Exam updated successfully',
            'data' => adminGetExamDetails($con, $id)
        ];
    }
}

if (!function_exists('adminDeleteExam')) {
    function adminDeleteExam($con, $creatorData = null) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Exam ID is required'];
        }
        if (mysqli_query($con, "DELETE FROM course_exams WHERE id = {$id}")) {
            @mysqli_query($con, "DELETE FROM course_exams_translations WHERE exam_id = {$id}");
            @mysqli_query($con, "DELETE FROM exam_teachers WHERE exam_id = {$id}");
            return ['status' => 'success', 'message' => 'Exam deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete exam'];
    }
}
