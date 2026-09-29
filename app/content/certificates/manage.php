<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/certificate_types.php';
require_once __DIR__ . '/../helpers.php';

if (!function_exists('get_certificate_details')) {
    function get_certificate_details($con, $certificateId) {
        if (!$certificateId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, academic_year_id, subject_id, course_id, code, score, certificate_type, image, is_active, created_by_type, created_by_id, created_at, updated_at FROM certificates WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $certificateId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['academic_year_id'] = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
        $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
        $row['score'] = (float)$row['score'];
        $row['is_active'] = (int)$row['is_active'];
        $row['created_by_type'] = $row['created_by_type'] ?? 'admin';
        $row['created_by_id'] = isset($row['created_by_id']) && $row['created_by_id'] ? (int)$row['created_by_id'] : null;
        $row['created_by'] = function_exists('get_course_created_by_details') ? get_course_created_by_details($con, $row['created_by_type'], $row['created_by_id']) : null;

        $teachers = [];
        $tRes = mysqli_query($con, "SELECT ct.teacher_id, t.name AS teacher_name, t.email AS teacher_email FROM certificate_teachers ct JOIN teachers t ON ct.teacher_id = t.id WHERE ct.certificate_id = {$certificateId} AND ct.is_active = 1");
        if ($tRes) {
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $teachers[] = [
                    'id' => (int)$tRow['teacher_id'],
                    'name' => $tRow['teacher_name'],
                    'email' => $tRow['teacher_email'],
                ];
            }
        }
        $row['teachers'] = $teachers;
        $row['teacher_ids'] = array_values(array_map(function ($t) { return (int)$t['id']; }, $teachers));

        $tStmt = mysqli_prepare($con, "SELECT ct.language_id, l.code AS language_code, ct.name, ct.description FROM certificates_translations ct JOIN languages l ON ct.language_id = l.id WHERE ct.certificate_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        $descAr = null;
        $descEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $certificateId);
            mysqli_stmt_execute($tStmt);
            $tRes2 = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes2)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') {
                    $nameAr = $tRow['name'];
                    $descAr = $tRow['description'];
                }
                if ($tRow['language_code'] === 'en') {
                    $nameEn = $tRow['name'];
                    $descEn = $tRow['description'];
                }
            }
        }

        $transMap = array_column($translations, 'name', 'language_code');
        $descMap = array_column($translations, 'description', 'language_code');
        $row['name'] = $transMap[$langInfo['code']] ?? ($nameAr ?? $nameEn ?? $row['code']);
        $row['title'] = $row['name'];
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
            $row['title_ar'] = $nameAr;
            $row['title_en'] = $nameEn;
            $row['description_ar'] = $descAr;
            $row['description_en'] = $descEn;
        }

        $issuedRes = mysqli_query($con, "SELECT COUNT(*) AS total FROM student_certificates WHERE certificate_id = {$certificateId}");
        $row['issued_count'] = $issuedRes ? (int)(mysqli_fetch_assoc($issuedRes)['total'] ?? 0) : 0;

        return format_response_images($row);
    }
}

if (!function_exists('adminListCertificates')) {
    function adminListCertificates($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $academicYearIds = function_exists('parse_int_list_param') ? parse_int_list_param($_GET['academic_year_ids'] ?? $_GET['academic_year_id'] ?? null) : array_filter([(int)($_GET['academic_year_id'] ?? 0)]);
        $subjectIds = function_exists('parse_int_list_param') ? parse_int_list_param($_GET['subject_ids'] ?? $_GET['subject_id'] ?? null) : array_filter([(int)($_GET['subject_id'] ?? 0)]);
        $courseIds = function_exists('parse_int_list_param') ? parse_int_list_param($_GET['course_ids'] ?? $_GET['course_id'] ?? null) : array_filter([(int)($_GET['course_id'] ?? 0)]);
        $teacherIds = function_exists('parse_int_list_param') ? parse_int_list_param($_GET['teacher_ids'] ?? $_GET['teacher_id'] ?? null) : array_filter([(int)($_GET['teacher_id'] ?? 0)]);
        $certificateType = trim($_GET['certificate_type'] ?? '');
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $code = trim($_GET['code'] ?? '');
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $sortBy = strtolower(trim($_GET['sort_by'] ?? $_GET['sort'] ?? 'id'));
        $sortDir = strtoupper(trim($_GET['dir'] ?? $_GET['direction'] ?? $_GET['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSort = [
            'id' => 'c.id',
            'code' => 'c.code',
            'score' => 'c.score',
            'created_at' => 'c.created_at',
            'name' => 'ct.name',
        ];
        $orderCol = $allowedSort[$sortBy] ?? 'c.id';

        $whereConditions = [];
        $params = [];
        $types = "";
        $joinCt = "";

        if (!empty($teacherIds)) {
            $joinCt = "LEFT JOIN certificate_teachers ct_piv ON c.id = ct_piv.certificate_id";
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $whereConditions[] = "((c.created_by_type = 'teacher' AND c.created_by_id IN ({$placeholders})) OR (ct_piv.teacher_id IN ({$placeholders}) AND ct_piv.is_active = 1) OR (c.created_by_type = 'admin' AND NOT EXISTS (SELECT 1 FROM certificate_teachers ct_chk WHERE ct_chk.certificate_id = c.id AND ct_chk.is_active = 1)))";
            foreach ($teacherIds as $tId) { $params[] = $tId; $types .= "i"; }
            foreach ($teacherIds as $tId) { $params[] = $tId; $types .= "i"; }
        }

        if (!empty($academicYearIds)) {
            $placeholders = implode(',', array_fill(0, count($academicYearIds), '?'));
            $whereConditions[] = "c.academic_year_id IN ({$placeholders})";
            foreach ($academicYearIds as $v) { $params[] = $v; $types .= "i"; }
        }
        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereConditions[] = "c.subject_id IN ({$placeholders})";
            foreach ($subjectIds as $v) { $params[] = $v; $types .= "i"; }
        }
        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $whereConditions[] = "c.course_id IN ({$placeholders})";
            foreach ($courseIds as $v) { $params[] = $v; $types .= "i"; }
        }
        if (!empty($certificateType)) {
            $whereConditions[] = "c.certificate_type = ?";
            $params[] = $certificateType;
            $types .= "s";
        }
        if ($activeFilter !== null) {
            $whereConditions[] = "c.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }
        if (!empty($code)) {
            $whereConditions[] = "c.code LIKE ?";
            $params[] = '%' . $code . '%';
            $types .= "s";
        }
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(c.code LIKE ? OR ct.name LIKE ? OR ct.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM certificates");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0),
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT c.id) AS total FROM certificates c {$joinCt} LEFT JOIN certificates_translations ct ON c.id = ct.certificate_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            if ($countStmt) {
                if (!empty($types)) {
                    mysqli_stmt_bind_param($countStmt, $types, ...$params);
                }
                mysqli_stmt_execute($countStmt);
                $countRes = mysqli_stmt_get_result($countStmt);
                $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
            }
        }

        $sql = "
            SELECT DISTINCT c.id
            FROM certificates c
            {$joinCt}
            LEFT JOIN certificates_translations ct ON c.id = ct.certificate_id
            {$whereClause}
            ORDER BY {$orderCol} {$sortDir}
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $requestingTeacherId = (count($teacherIds) === 1) ? (int)$teacherIds[0] : null;

        $certificates = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $cId = (int)$row['id'];
            $detail = get_certificate_details($con, $cId);
            if ($detail) {
                if ($requestingTeacherId !== null) {
                    $detail['can_edit'] = ($detail['created_by_type'] ?? 'admin') !== 'admin';
                }
                $certificates[] = $detail;
            }
        }

        return build_admin_paginated_response($certificates, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetCertificate')) {
    function adminGetCertificate($con) {
        $id = (int)($_GET['id'] ?? $_GET['certificate_id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Certificate ID is required'];
        }
        $certificate = get_certificate_details($con, $id);
        if (!$certificate) {
            return ['status' => 'error', 'message' => 'Certificate not found'];
        }
        return ['status' => 'success', 'data' => $certificate];
    }
}

if (!function_exists('adminCreateCertificate')) {
    function adminCreateCertificate($con, $creatorData = null) {
        $body = read_json_body();
        $academicYearId = isset($body['academic_year_id']) && $body['academic_year_id'] !== '' ? (int)$body['academic_year_id'] : null;
        $subjectId = isset($body['subject_id']) && $body['subject_id'] !== '' ? (int)$body['subject_id'] : null;
        $courseId = isset($body['course_id']) && $body['course_id'] !== '' ? (int)$body['course_id'] : null;
        $code = trim($body['code'] ?? '');
        $score = isset($body['score']) ? (float)$body['score'] : 0.00;
        $certificateType = trim($body['certificate_type'] ?? 'general');
        $image = trim($body['image'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        $nameAr = trim($body['name_ar'] ?? $body['title_ar'] ?? $body['name'] ?? $body['title'] ?? '');
        $nameEn = trim($body['name_en'] ?? $body['title_en'] ?? $nameAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);

        $tId = 0;
        if (is_array($creatorData)) {
            $tId = (int)($creatorData['id'] ?? $creatorData['teacher_id'] ?? 0);
        } elseif (is_object($creatorData)) {
            $tId = (int)($creatorData->id ?? $creatorData->teacher_id ?? 0);
        }

        if ($tId > 0) {
            $createdByType = 'teacher';
            $createdById = $tId;
        } else {
            $createdByType = strtolower(trim($body['created_by_type'] ?? 'admin'));
            $createdById = isset($body['created_by_id']) && $body['created_by_id'] ? (int)$body['created_by_id'] : null;
        }

        if (empty($nameAr)) {
            return ['status' => 'error', 'message' => 'Certificate name/title is required'];
        }
        if (empty($code)) {
            $code = 'CERT-' . strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $nameEn ?: $nameAr), 0, 10)) . '-' . substr(uniqid(), -5);
        }

        $stmt = mysqli_prepare($con, "INSERT INTO certificates (academic_year_id, subject_id, course_id, code, score, certificate_type, image, is_active, created_by_type, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare certificate insert: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "iiisdssisi", $academicYearId, $subjectId, $courseId, $code, $score, $certificateType, $image, $isActive, $createdByType, $createdById);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create certificate: ' . mysqli_error($con)];
        }

        $certificateId = mysqli_insert_id($con);

        $tStmt = mysqli_prepare($con, "INSERT INTO certificates_translations (certificate_id, language_id, name, description) VALUES (?, 1, ?, ?), (?, 2, ?, ?)");
        mysqli_stmt_bind_param($tStmt, "ississ", $certificateId, $nameAr, $descAr, $certificateId, $nameEn, $descEn);
        mysqli_stmt_execute($tStmt);

        $teacherIds = $body['teacher_ids'] ?? [];
        if (!is_array($teacherIds)) {
            $teacherIds = is_numeric($teacherIds) ? [(int)$teacherIds] : [];
        }
        if ($createdByType === 'teacher' && $createdById > 0 && !in_array($createdById, $teacherIds)) {
            $teacherIds[] = $createdById;
        }
        foreach (array_unique(array_map('intval', $teacherIds)) as $tid) {
            if ($tid <= 0) continue;
            $linkStmt = mysqli_prepare($con, "INSERT INTO certificate_teachers (certificate_id, teacher_id, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_active = 1");
            mysqli_stmt_bind_param($linkStmt, "ii", $certificateId, $tid);
            mysqli_stmt_execute($linkStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Certificate created successfully',
            'data' => get_certificate_details($con, $certificateId),
        ];
    }
}

if (!function_exists('adminUpdateCertificate')) {
    function adminUpdateCertificate($con, $creatorData = null) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Certificate ID is required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, academic_year_id, subject_id, course_id, created_by_type, created_by_id FROM certificates WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$existing) {
            return ['status' => 'error', 'message' => 'Certificate not found'];
        }

        $updateFields = [];
        $params = [];
        $types = "";

        if (array_key_exists('academic_year_id', $body)) {
            $updateFields[] = "academic_year_id = ?";
            $params[] = $body['academic_year_id'] !== '' ? (int)$body['academic_year_id'] : null;
            $types .= "i";
        }
        if (array_key_exists('subject_id', $body)) {
            $updateFields[] = "subject_id = ?";
            $params[] = $body['subject_id'] !== '' ? (int)$body['subject_id'] : null;
            $types .= "i";
        }
        if (array_key_exists('course_id', $body)) {
            $updateFields[] = "course_id = ?";
            $params[] = $body['course_id'] !== '' ? (int)$body['course_id'] : null;
            $types .= "i";
        }
        if (isset($body['code']) && !empty(trim($body['code']))) {
            $updateFields[] = "code = ?";
            $params[] = trim($body['code']);
            $types .= "s";
        }
        if (isset($body['score'])) {
            $updateFields[] = "score = ?";
            $params[] = (float)$body['score'];
            $types .= "d";
        }
        if (isset($body['certificate_type']) && !empty(trim($body['certificate_type']))) {
            $updateFields[] = "certificate_type = ?";
            $params[] = trim($body['certificate_type']);
            $types .= "s";
        }
        if (isset($body['image'])) {
            $updateFields[] = "image = ?";
            $params[] = trim($body['image']);
            $types .= "s";
        }
        if (isset($body['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = (int)$body['is_active'];
            $types .= "i";
        }
        if (isset($body['created_by_type']) && in_array(strtolower($body['created_by_type']), ['admin', 'teacher'])) {
            $updateFields[] = "created_by_type = ?";
            $params[] = strtolower($body['created_by_type']);
            $types .= "s";
        }
        if (isset($body['created_by_id'])) {
            $updateFields[] = "created_by_id = ?";
            $params[] = (int)$body['created_by_id'];
            $types .= "i";
        }

        if (!empty($updateFields)) {
            $params[] = $id;
            $types .= "i";
            $uSql = "UPDATE certificates SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $uStmt = mysqli_prepare($con, $uSql);
            mysqli_stmt_bind_param($uStmt, $types, ...$params);
            mysqli_stmt_execute($uStmt);
        }

        if (isset($body['name_ar']) || isset($body['title_ar']) || isset($body['name_en']) || isset($body['title_en']) || isset($body['name']) || isset($body['title']) || isset($body['description_ar']) || isset($body['description_en'])) {
            $nameAr = trim($body['name_ar'] ?? $body['title_ar'] ?? $body['name'] ?? $body['title'] ?? '');
            $nameEn = trim($body['name_en'] ?? $body['title_en'] ?? $nameAr);
            $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
            $descEn = trim($body['description_en'] ?? $descAr);

            if (!empty($nameAr)) {
                $tStmt1 = mysqli_prepare($con, "INSERT INTO certificates_translations (certificate_id, language_id, name, description) VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
                mysqli_stmt_bind_param($tStmt1, "iss", $id, $nameAr, $descAr);
                mysqli_stmt_execute($tStmt1);
            }
            if (!empty($nameEn)) {
                $tStmt2 = mysqli_prepare($con, "INSERT INTO certificates_translations (certificate_id, language_id, name, description) VALUES (?, 2, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
                mysqli_stmt_bind_param($tStmt2, "iss", $id, $nameEn, $descEn);
                mysqli_stmt_execute($tStmt2);
            }
        }

        if (isset($body['teacher_ids']) && is_array($body['teacher_ids'])) {
            mysqli_query($con, "UPDATE certificate_teachers SET is_active = 0 WHERE certificate_id = {$id}");
            foreach (array_unique(array_map('intval', $body['teacher_ids'])) as $tid) {
                if ($tid <= 0) continue;
                $linkStmt = mysqli_prepare($con, "INSERT INTO certificate_teachers (certificate_id, teacher_id, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_active = 1");
                mysqli_stmt_bind_param($linkStmt, "ii", $id, $tid);
                mysqli_stmt_execute($linkStmt);
            }
        }

        return [
            'status' => 'success',
            'message' => 'Certificate updated successfully',
            'data' => get_certificate_details($con, $id),
        ];
    }
}

if (!function_exists('adminDeleteCertificate')) {
    function adminDeleteCertificate($con, $creatorData = null) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Certificate ID is required'];
        }
        $stmt = mysqli_prepare($con, "DELETE FROM certificates WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            @mysqli_query($con, "DELETE FROM certificates_translations WHERE certificate_id = {$id}");
            @mysqli_query($con, "DELETE FROM certificate_teachers WHERE certificate_id = {$id}");
            return ['status' => 'success', 'message' => 'Certificate deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete certificate'];
    }
}



if (!function_exists('adminAddStudentCertificate')) {
    function adminAddStudentCertificate($con, $creatorData = null) {
        $body = read_json_body();

        $certificateId = (int)($body['certificate_id'] ?? 0);

        // Awarding by type: the admin picks تميز / إجتياز / تفوق / عامة and nothing else. The
        // course-less template for that type is found, or created the first time it is needed, so
        // the caller never has to hunt for a template built around some particular course.
        if ($certificateId <= 0 && isset($body['certificate_type'])) {
            $requestedType = certificate_type_normalize($body['certificate_type']);
            if ($requestedType === '') {
                return [
                    'status' => 'error',
                    'message' => 'Unknown certificate type'
                ];
            }

            $creatorType = 'admin';
            $creatorId = 1;
            if (is_array($creatorData)) {
                $creatorType = !empty($creatorData['teacher_id']) || ($creatorData['user_type'] ?? '') === 'teacher' ? 'teacher' : 'admin';
                $creatorId = (int)($creatorData['id'] ?? $creatorData['teacher_id'] ?? 1);
            }

            $certificateId = certificate_general_template_id($con, $requestedType, $creatorType, $creatorId);
            if ($certificateId <= 0) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to resolve a certificate for this type'
                ];
            }
        }

        $studentIds = $body['student_ids'] ?? [];

        // Also support single student_id
        if (empty($studentIds) && !empty($body['student_id'])) {
            $studentIds = [(int)$body['student_id']];
        }

        if (!is_array($studentIds)) {
            $studentIds = [(int)$studentIds];
        }

        $studentIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $studentIds),
                    function ($id) {
                        return $id > 0;
                    }
                )
            )
        );

        $examId = isset($body['exam_id']) && $body['exam_id'] !== ''
            ? (int)$body['exam_id']
            : null;

        $scoreAchieved = isset($body['score_achieved']) && $body['score_achieved'] !== ''
            ? (float)$body['score_achieved']
            : null;

        $gradeObtained = isset($body['grade_obtained']) && trim((string)$body['grade_obtained']) !== ''
            ? trim($body['grade_obtained'])
            : null;

        $pdfUrl = isset($body['pdf_url']) && trim($body['pdf_url']) !== ''
            ? trim($body['pdf_url'])
            : null;

        $issueDate = isset($body['issue_date']) && trim($body['issue_date']) !== ''
            ? trim($body['issue_date'])
            : date('Y-m-d');

        if ($certificateId <= 0) {
            return [
                'status' => 'error',
                'message' => 'certificate_id or certificate_type is required'
            ];
        }

        if (empty($studentIds)) {
            return [
                'status' => 'error',
                'message' => 'At least one student ID is required'
            ];
        }

        /*
         * Get certificate.
         */
        $certificateStmt = mysqli_prepare(
            $con,
            "
            SELECT
                id,
                academic_year_id,
                subject_id,
                course_id,
                code,
                score,
                certificate_type,
                image,
                is_active,
                created_by_type,
                created_by_id,
                created_at,
                updated_at
            FROM certificates
            WHERE id = ?
            LIMIT 1
            "
        );

        if (!$certificateStmt) {
            return [
                'status' => 'error',
                'message' => 'Failed to prepare certificate query: ' . mysqli_error($con)
            ];
        }

        mysqli_stmt_bind_param($certificateStmt, "i", $certificateId);
        mysqli_stmt_execute($certificateStmt);

        $certificateResult = mysqli_stmt_get_result($certificateStmt);
        $certificate = mysqli_fetch_assoc($certificateResult);

        if (!$certificate) {
            return [
                'status' => 'error',
                'message' => 'Certificate not found'
            ];
        }

        if ((int)$certificate['is_active'] !== 1) {
            return [
                'status' => 'error',
                'message' => 'Certificate is not active'
            ];
        }

        $courseId = !empty($certificate['course_id'])
            ? (int)$certificate['course_id']
            : null;

        /*
         * Optional exam validation.
         */
        if ($examId !== null && $examId > 0) {
            $examStmt = mysqli_prepare(
                $con,
                // `exams` is the legacy table the exam endpoints stopped writing to; every exam
                // lives in `course_exams` (see migration 021), so validating against `exams` here
                // rejected every real exam id.
                "
                SELECT id
                FROM course_exams
                WHERE id = ?
                LIMIT 1
                "
            );

            if (!$examStmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare exam query: ' . mysqli_error($con)
                ];
            }

            mysqli_stmt_bind_param($examStmt, "i", $examId);
            mysqli_stmt_execute($examStmt);

            $examResult = mysqli_stmt_get_result($examStmt);

            if (!mysqli_fetch_assoc($examResult)) {
                return [
                    'status' => 'error',
                    'message' => 'Exam not found'
                ];
            }
        }

        /*
         * Prepare reusable statements.
         */
        $studentStmt = mysqli_prepare(
            $con,
            "
            SELECT *
            FROM students
            WHERE id = ?
            LIMIT 1
            "
        );

        $duplicateStmt = mysqli_prepare(
            $con,
            "
            SELECT id
            FROM student_certificates
            WHERE certificate_id = ?
              AND student_id = ?
            LIMIT 1
            "
        );

        $insertStmt = mysqli_prepare(
            $con,
            "
            INSERT INTO student_certificates (
                certificate_id,
                student_id,
                course_id,
                exam_id,
                certificate_code,
                pdf_url,
                score_achieved,
                grade_obtained,
                issue_date
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            "
        );

        if (!$studentStmt || !$duplicateStmt || !$insertStmt) {
            return [
                'status' => 'error',
                'message' => 'Failed to prepare database statements: ' . mysqli_error($con)
            ];
        }

        $issued = [];
        $skipped = [];
        $failed = [];

        foreach ($studentIds as $studentId) {

            /*
             * Check student.
             */
            mysqli_stmt_bind_param($studentStmt, "i", $studentId);
            mysqli_stmt_execute($studentStmt);

            $studentResult = mysqli_stmt_get_result($studentStmt);
            $student = mysqli_fetch_assoc($studentResult);

            if (!$student) {
                $failed[] = [
                    'student_id' => $studentId,
                    'reason' => 'Student not found'
                ];

                continue;
            }

            /*
             * Check duplicate.
             */
            mysqli_stmt_bind_param(
                $duplicateStmt,
                "ii",
                $certificateId,
                $studentId
            );

            mysqli_stmt_execute($duplicateStmt);

            $duplicateResult = mysqli_stmt_get_result($duplicateStmt);
            $duplicate = mysqli_fetch_assoc($duplicateResult);

            if ($duplicate) {
                $skipped[] = [
                    'student_id' => $studentId,
                    'student_certificate_id' => (int)$duplicate['id'],
                    'reason' => 'Certificate already issued to this student'
                ];

                continue;
            }

            /*
             * Unique certificate code.
             */
            $baseCode = trim($certificate['code'] ?? 'CERT');

            $certificateCode = strtoupper(
                $baseCode .
                '-S' . $studentId .
                '-' . date('Ymd') .
                '-' . substr(strtoupper(uniqid()), -6)
            );

         
            mysqli_stmt_bind_param(
                $insertStmt,
                "iiiissdss",
                $certificateId,
                $studentId,
                $courseId,
                $examId,
                $certificateCode,
                $pdfUrl,
                $scoreAchieved,
                $gradeObtained,
                $issueDate
            );

            if (!mysqli_stmt_execute($insertStmt)) {
                $failed[] = [
                    'student_id' => $studentId,
                    'reason' => mysqli_error($con)
                ];

                continue;
            }

            $studentCertificateId = mysqli_insert_id($con);

            $issued[] = [
                'id' => (int)$studentCertificateId,
                'certificate_id' => $certificateId,
                'student_id' => $studentId,
                'course_id' => $courseId,
                'exam_id' => $examId,
                'certificate_code' => $certificateCode,
                'pdf_url' => $pdfUrl,
                'score_achieved' => $scoreAchieved,
                'grade_obtained' => $gradeObtained,
                'issue_date' => $issueDate,
                'student' => format_response_images($student)
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Student certificates processed successfully',
            'data' => [
                'certificate' => get_certificate_details(
                    $con,
                    $certificateId
                ),

                'summary' => [
                    'requested_count' => count($studentIds),
                    'issued_count' => count($issued),
                    'skipped_count' => count($skipped),
                    'failed_count' => count($failed)
                ],

                'issued' => $issued,
                'skipped' => $skipped,
                'failed' => $failed
            ]
        ];
    }
}

if (!function_exists('get_course_students')) {
    function get_course_students($con, $courseId) {
        $courseId = (int)$courseId;

        if ($courseId <= 0) {
            return [];
        }

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT DISTINCT
                s.*
            FROM student_subscription_courses ssc
            INNER JOIN student_subscriptions ssb
                ON ssc.student_subscription_id = ssb.id
            INNER JOIN students s
                ON ssb.student_id = s.id
            WHERE ssc.course_id = ?
              AND NOT EXISTS (
                    SELECT 1
                    FROM student_certificates sc
                    WHERE sc.course_id = ssc.course_id
                      AND sc.student_id = s.id
              )
            ORDER BY s.id DESC
            "
        );

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $courseId
        );

        if (!mysqli_stmt_execute($stmt)) {
            return [];
        }

        $result = mysqli_stmt_get_result($stmt);

        $students = [];

        while ($row = mysqli_fetch_assoc($result)) {
            if (isset($row['id'])) {
                $row['id'] = (int)$row['id'];
            }

            if (isset($row['is_active'])) {
                $row['is_active'] = (int)$row['is_active'];
            }

            if (isset($row['academic_year_id']) && $row['academic_year_id'] !== null) {
                $row['academic_year_id'] = (int)$row['academic_year_id'];
            }

            if (isset($row['created_by_id']) && $row['created_by_id'] !== null) {
                $row['created_by_id'] = (int)$row['created_by_id'];
            }

            $students[] = function_exists('format_response_images')
                ? format_response_images($row)
                : $row;
        }

        mysqli_stmt_close($stmt);

        return ['status' => 'success', 'message' => $students];
    }
}
/**
 * Certificates already issued to students. `student_certificates.course_id` is nullable, so a
 * certificate granted outside any course shows up here with a null course rather than being
 * impossible to record.
 */
if (!function_exists('adminListStudentCertificates')) {
    function adminListStudentCertificates($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = [];
        $params = [];
        $types = '';

        if (!empty($_GET['student_id'])) {
            $where[] = 'sc.student_id = ?';
            $params[] = (int)$_GET['student_id'];
            $types .= 'i';
        }
        if (!empty($_GET['certificate_id'])) {
            $where[] = 'sc.certificate_id = ?';
            $params[] = (int)$_GET['certificate_id'];
            $types .= 'i';
        }
        // Same ownership rule the certificates list uses: a teacher sees certificates they created,
        // ones explicitly assigned to them, and admin-made ones that no teacher owns.
        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        if ($teacherId > 0) {
            $where[] = "sc.certificate_id IN (
                SELECT c.id FROM certificates c
                LEFT JOIN certificate_teachers ct ON ct.certificate_id = c.id
                WHERE (c.created_by_type = 'teacher' AND c.created_by_id = ?)
                   OR (ct.teacher_id = ? AND ct.is_active = 1)
                   OR (c.created_by_type = 'admin' AND NOT EXISTS (
                        SELECT 1 FROM certificate_teachers ct_chk
                        WHERE ct_chk.certificate_id = c.id AND ct_chk.is_active = 1
                   ))
            )";
            $params[] = $teacherId;
            $params[] = $teacherId;
            $types .= 'ii';
        }

        if (isset($_GET['course_id']) && $_GET['course_id'] !== '') {
            if ((string)$_GET['course_id'] === 'none') {
                $where[] = 'sc.course_id IS NULL';
            } else {
                $where[] = 'sc.course_id = ?';
                $params[] = (int)$_GET['course_id'];
                $types .= 'i';
            }
        }
        if ($search !== '') {
            $where[] = '(st.name LIKE ? OR st.email LIKE ? OR sc.certificate_code LIKE ?)';
            $pattern = '%' . $search . '%';
            array_push($params, $pattern, $pattern, $pattern);
            $types .= 'sss';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS total FROM student_certificates sc LEFT JOIN students st ON st.id = sc.student_id {$whereSql}";
        if ($params) {
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);
        } else {
            $countRes = mysqli_query($con, $countSql);
            $total = $countRes ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;
        }

        $sql = "
            SELECT sc.id, sc.certificate_id, sc.student_id, sc.course_id, sc.exam_id,
                   sc.certificate_code, sc.pdf_url, sc.score_achieved, sc.grade_obtained,
                   sc.issue_date, sc.created_at,
                   st.name AS student_name, st.email AS student_email, st.image AS student_image
            FROM student_certificates sc
            LEFT JOIN students st ON st.id = sc.student_id
            {$whereSql}
            ORDER BY sc.issue_date DESC, sc.id DESC
            LIMIT ? OFFSET ?
        ";
        $listParams = $params;
        $listTypes = $types . 'ii';
        $listParams[] = $limit;
        $listParams[] = $offset;

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $listTypes, ...$listParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['certificate_id'] = (int)$row['certificate_id'];
            $row['student_id'] = (int)$row['student_id'];
            $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
            $row['exam_id'] = $row['exam_id'] ? (int)$row['exam_id'] : null;
            $row['score_achieved'] = $row['score_achieved'] !== null ? (float)$row['score_achieved'] : null;
            $row['certificate'] = get_certificate_details($con, $row['certificate_id']);
            $row['course'] = $row['course_id'] ? get_course_details($con, $row['course_id']) : null;
            $rows[] = $row;
        }

        return build_paginated_response(format_response_images($rows), $page, $limit, $total);
    }
}

if (!function_exists('adminRevokeStudentCertificate')) {
    function adminRevokeStudentCertificate($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $body['student_certificate_id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Student certificate id is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM student_certificates WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to revoke certificate: ' . mysqli_error($con)];
        }
        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return ['status' => 'error', 'message' => 'Student certificate not found'];
        }

        return ['status' => 'success', 'message' => 'تم سحب الشهادة من الطالب'];
    }
}

/**
 * Teacher-side wrappers. The teacher id comes from the token, never the request, so a teacher can
 * only ever list or revoke certificates within their own scope.
 */
if (!function_exists('teacher_certificate_scope_id')) {
    function teacher_certificate_scope_id($teacherData): int {
        if (is_array($teacherData)) {
            return (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        }
        if (is_object($teacherData)) {
            return (int)($teacherData->id ?? $teacherData->teacher_id ?? 0);
        }
        return 0;
    }
}

if (!function_exists('teacherListStudentCertificates')) {
    function teacherListStudentCertificates($con, $teacherData = null) {
        $teacherId = teacher_certificate_scope_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication is required'];
        }
        $_GET['teacher_id'] = $teacherId;
        return adminListStudentCertificates($con);
    }
}

if (!function_exists('teacherRevokeStudentCertificate')) {
    function teacherRevokeStudentCertificate($con, $teacherData = null) {
        $teacherId = teacher_certificate_scope_id($teacherData);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Teacher authentication is required'];
        }

        $body = read_json_body();
        $id = (int)($body['id'] ?? $body['student_certificate_id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Student certificate id is required'];
        }

        // Revoking is destructive, so ownership is checked here rather than trusted from the client.
        $stmt = mysqli_prepare($con, "
            SELECT sc.id
            FROM student_certificates sc
            JOIN certificates c ON c.id = sc.certificate_id
            LEFT JOIN certificate_teachers ct ON ct.certificate_id = c.id
            WHERE sc.id = ?
              AND ((c.created_by_type = 'teacher' AND c.created_by_id = ?)
                   OR (ct.teacher_id = ? AND ct.is_active = 1))
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "iii", $id, $teacherId, $teacherId);
        mysqli_stmt_execute($stmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            return ['status' => 'error', 'message' => 'This certificate was not granted from one of your certificates'];
        }

        return adminRevokeStudentCertificate($con);
    }
}
