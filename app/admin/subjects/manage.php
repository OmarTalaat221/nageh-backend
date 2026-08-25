<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/functions.php';

if (!function_exists('sync_subject_academic_years')) {
    function sync_subject_academic_years($con, int $subjectId, $academicYearIds): void {
        if ($subjectId <= 0) return;

        if (!is_array($academicYearIds)) {
            if (is_numeric($academicYearIds)) {
                $academicYearIds = [(int)$academicYearIds];
            } elseif (is_string($academicYearIds) && str_contains($academicYearIds, ',')) {
                $academicYearIds = array_map('intval', explode(',', $academicYearIds));
            } else {
                $academicYearIds = [];
            }
        }

        // Remove existing linkages for this subject
        $delStmt = mysqli_prepare($con, "DELETE FROM subject_academic_years WHERE subject_id = ?");
        if ($delStmt) {
            mysqli_stmt_bind_param($delStmt, "i", $subjectId);
            mysqli_stmt_execute($delStmt);
        }

        // Insert new unique valid academic year IDs
        $insertStmt = mysqli_prepare($con, "INSERT INTO subject_academic_years (subject_id, academic_year_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        $uniqueAyIds = array_unique(array_filter(array_map('intval', $academicYearIds)));

        foreach ($uniqueAyIds as $ayId) {
            if ($ayId > 0 && $insertStmt) {
                mysqli_stmt_bind_param($insertStmt, "ii", $subjectId, $ayId);
                mysqli_stmt_execute($insertStmt);
            }
        }
    }
}

if (!function_exists('adminListSubjects')) {
    function adminListSubjects($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
        $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;

        $whereConditions = [];
        $params = [];
        $types = "";

        $joinSay = "";
        if ($academicYearId > 0) {
            $joinSay = "JOIN subject_academic_years say ON sub.id = say.subject_id";
            $whereConditions[] = "say.academic_year_id = ? AND say.is_active = 1";
            $params[] = $academicYearId;
            $types .= "i";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(sub.code LIKE ? OR subt.name LIKE ? OR subt.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM subjects");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT sub.id) AS total FROM subjects sub {$joinSay} LEFT JOIN subjects_translations subt ON sub.id = subt.subject_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            if (!empty($types)) {
                mysqli_stmt_bind_param($countStmt, $types, ...$params);
            }
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT DISTINCT sub.id, sub.code, sub.is_active, sub.created_at, sub.updated_at FROM subjects sub {$joinSay} LEFT JOIN subjects_translations subt ON sub.id = subt.subject_id {$whereClause} ORDER BY sub.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $subjects = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $subId = (int)$row['id'];
            $row['id'] = $subId;
            $row['is_active'] = (int)$row['is_active'];
            $row['translations'] = get_subject_translations_all($con, $subId);

            foreach ($row['translations'] as $t) {
                if ($t['language_code'] === 'ar') {
                    $row['name_ar'] = $t['name'];
                    $row['description_ar'] = $t['description'];
                }
                if ($t['language_code'] === 'en') {
                    $row['name_en'] = $t['name'];
                    $row['description_en'] = $t['description'];
                }
            }

            $row['academic_years'] = get_subject_academic_years_details($con, $subId);
            $row['academic_year_ids'] = array_values(array_map(function($y) { return (int)$y['id']; }, $row['academic_years']));

            $subjects[] = $row;
        }

        return build_admin_paginated_response($subjects, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetSubject')) {
    function adminGetSubject($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Subject ID is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, code, is_active, created_at, updated_at FROM subjects WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return ['status' => 'error', 'message' => 'Subject not found'];
        }

        $subId = (int)$row['id'];
        $row['id'] = $subId;
        $row['is_active'] = (int)$row['is_active'];
        $row['translations'] = get_subject_translations_all($con, $subId);

        foreach ($row['translations'] as $t) {
            if ($t['language_code'] === 'ar') {
                $row['name_ar'] = $t['name'];
                $row['description_ar'] = $t['description'];
            }
            if ($t['language_code'] === 'en') {
                $row['name_en'] = $t['name'];
                $row['description_en'] = $t['description'];
            }
        }

        $row['academic_years'] = get_subject_academic_years_details($con, $subId);
        $row['academic_year_ids'] = array_values(array_map(function($y) { return (int)$y['id']; }, $row['academic_years']));

        return ['status' => 'success', 'data' => $row];
    }
}

if (!function_exists('adminCreateSubject')) {
    function adminCreateSubject($con) {
        $body = read_json_body();
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $descAr = trim($body['description_ar'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $academicYearIds = $body['academic_year_ids'] ?? $body['academic_years'] ?? $body['academic_year_id'] ?? null;

        if (empty($code) || empty($nameAr)) {
            return ['status' => 'error', 'message' => 'Subject code and name_ar are required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO subjects (code, is_active) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $code, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create subject: ' . mysqli_error($con)];
        }

        $subId = mysqli_insert_id($con);

        $transStmt = mysqli_prepare($con, "INSERT INTO subjects_translations (subject_id, language_id, name, description) VALUES (?, 1, ?, ?), (?, 2, ?, ?)");
        mysqli_stmt_bind_param($transStmt, "ississ", $subId, $nameAr, $descAr, $subId, $nameEn, $descEn);
        mysqli_stmt_execute($transStmt);

        if ($academicYearIds !== null) {
            sync_subject_academic_years($con, $subId, $academicYearIds);
        }

        $_GET['id'] = $subId;
        return [
            'status' => 'success',
            'message' => 'Subject created successfully',
            'data' => adminGetSubject($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateSubject')) {
    function adminUpdateSubject($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        $code = strtolower(trim($body['code'] ?? ''));
        $nameAr = trim($body['name_ar'] ?? '');
        $nameEn = trim($body['name_en'] ?? '');
        $descAr = trim($body['description_ar'] ?? '');
        $descEn = trim($body['description_en'] ?? '');
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;
        $academicYearIds = $body['academic_year_ids'] ?? $body['academic_years'] ?? $body['academic_year_id'] ?? null;

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Subject ID is required'];
        }

        if (!empty($code)) {
            $stmt = mysqli_prepare($con, "UPDATE subjects SET code = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $code, $id);
            mysqli_stmt_execute($stmt);
        }

        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE subjects SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($nameAr) || !empty($descAr)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO subjects_translations (subject_id, language_id, name, description) VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
            mysqli_stmt_bind_param($transStmt, "iss", $id, $nameAr, $descAr);
            mysqli_stmt_execute($transStmt);
        }

        if (!empty($nameEn) || !empty($descEn)) {
            $transStmt = mysqli_prepare($con, "INSERT INTO subjects_translations (subject_id, language_id, name, description) VALUES (?, 2, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)");
            mysqli_stmt_bind_param($transStmt, "iss", $id, $nameEn, $descEn);
            mysqli_stmt_execute($transStmt);
        }

        if ($academicYearIds !== null) {
            sync_subject_academic_years($con, $id, $academicYearIds);
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => 'Subject updated successfully',
            'data' => adminGetSubject($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteSubject')) {
    function adminDeleteSubject($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Subject ID is required'];
        }

        // 1. Delete links from subject_academic_years pivot table
        $sayStmt = mysqli_prepare($con, "DELETE FROM subject_academic_years WHERE subject_id = ?");
        if ($sayStmt) {
            mysqli_stmt_bind_param($sayStmt, "i", $id);
            mysqli_stmt_execute($sayStmt);
        }

        // 2. Unlink primary subject_id from teachers
        $uStmt = mysqli_prepare($con, "UPDATE teachers SET subject_id = NULL WHERE subject_id = ?");
        if ($uStmt) {
            mysqli_stmt_bind_param($uStmt, "i", $id);
            mysqli_stmt_execute($uStmt);
        }

        // 3. Remove subject from teacher_subjects pivot table
        $tsStmt = mysqli_prepare($con, "DELETE FROM teacher_subjects WHERE subject_id = ?");
        if ($tsStmt) {
            mysqli_stmt_bind_param($tsStmt, "i", $id);
            mysqli_stmt_execute($tsStmt);
        }

        // 4. Remove translations
        $trStmt = mysqli_prepare($con, "DELETE FROM subjects_translations WHERE subject_id = ?");
        if ($trStmt) {
            mysqli_stmt_bind_param($trStmt, "i", $id);
            mysqli_stmt_execute($trStmt);
        }

        // 5. Delete the subject itself
        $stmt = mysqli_prepare($con, "DELETE FROM subjects WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare delete query: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete subject: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => trans('subject_deleted_success')];
    }
}

if (!function_exists('adminLinkSubjectToAcademicYear')) {
    function adminLinkSubjectToAcademicYear($con) {
        $body = read_json_body();
        $subjectId = (int)($body['subject_id'] ?? 0);
        $academicYearIds = $body['academic_year_ids'] ?? $body['academic_years'] ?? $body['academic_year_id'] ?? 0;

        if ($subjectId <= 0) {
            return ['status' => 'error', 'message' => 'Valid subject_id is required'];
        }

        if (!is_array($academicYearIds)) {
            if (is_numeric($academicYearIds)) {
                $academicYearIds = [(int)$academicYearIds];
            } elseif (is_string($academicYearIds) && str_contains($academicYearIds, ',')) {
                $academicYearIds = array_map('intval', explode(',', $academicYearIds));
            } else {
                $academicYearIds = [];
            }
        }

        $academicYearIds = array_unique(array_filter(array_map('intval', $academicYearIds)));
        if (empty($academicYearIds)) {
            return ['status' => 'error', 'message' => 'Valid academic_year_id or academic_year_ids array is required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO subject_academic_years (subject_id, academic_year_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        foreach ($academicYearIds as $ayId) {
            if ($ayId > 0 && $stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $subjectId, $ayId);
                mysqli_stmt_execute($stmt);
            }
        }

        $_GET['id'] = $subjectId;
        return [
            'status' => 'success',
            'message' => 'Subject linked to academic year(s) successfully',
            'data' => adminGetSubject($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUnlinkSubjectFromAcademicYear')) {
    function adminUnlinkSubjectFromAcademicYear($con) {
        $body = read_json_body();
        $subjectId = (int)($body['subject_id'] ?? 0);
        $academicYearIds = $body['academic_year_ids'] ?? $body['academic_years'] ?? $body['academic_year_id'] ?? 0;

        if ($subjectId <= 0) {
            return ['status' => 'error', 'message' => 'Valid subject_id is required'];
        }

        if (!is_array($academicYearIds)) {
            if (is_numeric($academicYearIds)) {
                $academicYearIds = [(int)$academicYearIds];
            } elseif (is_string($academicYearIds) && str_contains($academicYearIds, ',')) {
                $academicYearIds = array_map('intval', explode(',', $academicYearIds));
            } else {
                $academicYearIds = [];
            }
        }

        $academicYearIds = array_unique(array_filter(array_map('intval', $academicYearIds)));
        if (empty($academicYearIds)) {
            return ['status' => 'error', 'message' => 'Valid academic_year_id or academic_year_ids array is required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM subject_academic_years WHERE subject_id = ? AND academic_year_id = ?");
        foreach ($academicYearIds as $ayId) {
            if ($ayId > 0 && $stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $subjectId, $ayId);
                mysqli_stmt_execute($stmt);
            }
        }

        $_GET['id'] = $subjectId;
        return [
            'status' => 'success',
            'message' => 'Subject unlinked from academic year(s) successfully',
            'data' => adminGetSubject($con)['data'] ?? null
        ];
    }
}
