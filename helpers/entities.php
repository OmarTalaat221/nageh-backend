<?php
require_once __DIR__ . '/language.php';

if (!function_exists('get_city_details')) {
    function get_city_details($con, $cityId) {
        if (!$cityId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code FROM cities WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $cityId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];

        $tStmt = mysqli_prepare($con, "SELECT ct.language_id, l.code AS language_code, ct.name FROM cities_translations ct JOIN languages l ON ct.language_id = l.id WHERE ct.city_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $cityId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') $nameAr = $tRow['name'];
                if ($tRow['language_code'] === 'en') $nameEn = $tRow['name'];
            }
        }

        $transMap = array_column($translations, 'name', 'language_code');
        $row['name'] = $transMap[$langInfo['code']] ?? ($nameAr ?? $nameEn ?? $row['code']);

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('get_school_type_details')) {
    function get_school_type_details($con, $schoolTypeId) {
        if (!$schoolTypeId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code FROM school_types WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $schoolTypeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];

        $tStmt = mysqli_prepare($con, "SELECT stt.language_id, l.code AS language_code, stt.name FROM school_types_translations stt JOIN languages l ON stt.language_id = l.id WHERE stt.school_type_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $schoolTypeId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') $nameAr = $tRow['name'];
                if ($tRow['language_code'] === 'en') $nameEn = $tRow['name'];
            }
        }

        $transMap = array_column($translations, 'name', 'language_code');
        $row['name'] = $transMap[$langInfo['code']] ?? ($nameAr ?? $nameEn ?? $row['code']);

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('get_school_details')) {
    function get_school_details($con, $schoolId) {
        if (!$schoolId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code, school_type_id, city_id FROM schools WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $schoolId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $cId = isset($row['city_id']) && $row['city_id'] ? (int)$row['city_id'] : null;
        $stId = isset($row['school_type_id']) && $row['school_type_id'] ? (int)$row['school_type_id'] : null;
        unset($row['city_id'], $row['school_type_id']);

        $tStmt = mysqli_prepare($con, "SELECT st.language_id, l.code AS language_code, st.name FROM schools_translations st JOIN languages l ON st.language_id = l.id WHERE st.school_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $schoolId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') $nameAr = $tRow['name'];
                if ($tRow['language_code'] === 'en') $nameEn = $tRow['name'];
            }
        }

        $transMap = array_column($translations, 'name', 'language_code');
        $row['name'] = $transMap[$langInfo['code']] ?? ($nameAr ?? $nameEn ?? $row['code']);

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
        }

        $row['city'] = get_city_details($con, $cId);
        $row['school_type'] = get_school_type_details($con, $stId);

        return format_response_images($row);
    }
}

if (!function_exists('get_subject_details')) {
    function get_subject_details($con, $subjectId) {
        if (!$subjectId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code FROM subjects WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $subjectId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];

        $tStmt = mysqli_prepare($con, "SELECT subt.language_id, l.code AS language_code, subt.name, subt.description FROM subjects_translations subt JOIN languages l ON subt.language_id = l.id WHERE subt.subject_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        $descAr = null;
        $descEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $subjectId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
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
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
            $row['description_ar'] = $descAr;
            $row['description_en'] = $descEn;
        }

        $row['academic_years'] = get_subject_academic_years_details($con, $subjectId);
        $row['academic_year_ids'] = array_values(array_map(function($ay) { return (int)$ay['id']; }, $row['academic_years']));

        return format_response_images($row);
    }
}

if (!function_exists('get_academic_year_details')) {
    function get_academic_year_details($con, $academicYearId) {
        if (!$academicYearId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code, number FROM academic_years WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $academicYearId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['number'] = $row['number'] ? (int)$row['number'] : null;

        $tStmt = mysqli_prepare($con, "SELECT ayt.language_id, l.code AS language_code, ayt.name FROM academic_years_translations ayt JOIN languages l ON ayt.language_id = l.id WHERE ayt.academic_year_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $academicYearId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') $nameAr = $tRow['name'];
                if ($tRow['language_code'] === 'en') $nameEn = $tRow['name'];
            }
        }

        $transMap = array_column($translations, 'name', 'language_code');
        $row['name'] = $transMap[$langInfo['code']] ?? ($nameAr ?? $nameEn ?? $row['code']);

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('get_course_category_details')) {
    function get_course_category_details($con, $categoryId) {
        if (!$categoryId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code, image FROM course_categories WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $categoryId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];

        $tStmt = mysqli_prepare($con, "SELECT cct.language_id, l.code AS language_code, cct.name FROM course_categories_translations cct JOIN languages l ON cct.language_id = l.id WHERE cct.course_category_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $categoryId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') $nameAr = $tRow['name'];
                if ($tRow['language_code'] === 'en') $nameEn = $tRow['name'];
            }
        }

        $transMap = array_column($translations, 'name', 'language_code');
        $row['name'] = $transMap[$langInfo['code']] ?? ($nameAr ?? $nameEn ?? $row['code']);

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('get_subject_academic_years_details')) {
    function get_subject_academic_years_details($con, $subjectId) {
        if (!$subjectId) return [];

        $stmt = mysqli_prepare($con, "
            SELECT ay.id
            FROM subject_academic_years say
            JOIN academic_years ay ON say.academic_year_id = ay.id
            WHERE say.subject_id = ? AND say.is_active = 1 AND ay.is_active = 1
            ORDER BY ay.number ASC, ay.id ASC
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $subjectId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $years = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ayId = (int)$row['id'];
            $detail = get_academic_year_details($con, $ayId);
            if ($detail) {
                $years[] = $detail;
            }
        }
        return $years;
    }
}

if (!function_exists('get_academic_year_subjects_details')) {
    function get_academic_year_subjects_details($con, $academicYearId) {
        if (!$academicYearId) return [];

        $stmt = mysqli_prepare($con, "
            SELECT sub.id
            FROM subject_academic_years say
            JOIN subjects sub ON say.subject_id = sub.id
            WHERE say.academic_year_id = ? AND say.is_active = 1 AND sub.is_active = 1
            ORDER BY sub.id DESC
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $academicYearId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $subjects = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $subId = (int)$row['id'];
            $detail = get_subject_details($con, $subId);
            if ($detail) {
                $subjects[] = $detail;
            }
        }
        return $subjects;
    }
}

if (!function_exists('ensure_teachers_schema')) {
    function ensure_teachers_schema($con) {
        static $checked = false;
        if ($checked) return;
        $chk1 = @mysqli_query($con, "SHOW COLUMNS FROM teachers LIKE 'qualifications'");
        if ($chk1 && mysqli_num_rows($chk1) == 0) {
            @mysqli_query($con, "ALTER TABLE teachers ADD COLUMN qualifications TEXT NULL AFTER biography");
        }
        $chk2 = @mysqli_query($con, "SHOW COLUMNS FROM teachers LIKE 'can_view_profile'");
        if ($chk2 && mysqli_num_rows($chk2) == 0) {
            @mysqli_query($con, "ALTER TABLE teachers ADD COLUMN can_view_profile TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active");
        }
        $checked = true;
    }
}

if (!function_exists('get_teacher_subjects_details')) {
    function get_teacher_subjects_details($con, $teacherId) {
        if (!$teacherId) return [];

        $stmt = mysqli_prepare($con, "
            SELECT DISTINCT s_id FROM (
                SELECT ts.subject_id AS s_id
                FROM teacher_subjects ts
                JOIN subjects sub ON ts.subject_id = sub.id
                WHERE ts.teacher_id = ? AND ts.is_active = 1 AND sub.is_active = 1

                UNION

                SELECT t.subject_id AS s_id
                FROM teachers t
                JOIN subjects sub ON t.subject_id = sub.id
                WHERE t.id = ? AND t.subject_id IS NOT NULL AND sub.is_active = 1

                UNION

                SELECT c.subject_id AS s_id
                FROM courses c
                JOIN subjects sub ON c.subject_id = sub.id
                WHERE (c.teacher_id = ? OR (c.created_by_type = 'teacher' AND c.created_by_id = ?) OR c.id IN (SELECT course_id FROM course_teachers WHERE teacher_id = ? AND is_active = 1))
                  AND c.subject_id IS NOT NULL AND sub.is_active = 1
            ) combined_subjects
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "iiiii", $teacherId, $teacherId, $teacherId, $teacherId, $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $subjects = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $subId = (int)$row['s_id'];
            $detail = get_subject_details($con, $subId);
            if ($detail) {
                $subjects[] = $detail;
            }
        }
        return $subjects;
    }
}

if (!function_exists('get_teacher_academic_years_details')) {
    function get_teacher_academic_years_details($con, $teacherId) {
        if (!$teacherId) return [];

        $stmt = mysqli_prepare($con, "
            SELECT DISTINCT ay_id FROM (
                SELECT say.academic_year_id AS ay_id
                FROM subject_academic_years say
                JOIN subjects sub ON say.subject_id = sub.id
                JOIN teacher_subjects ts ON sub.id = ts.subject_id
                JOIN academic_years ay ON say.academic_year_id = ay.id
                WHERE ts.teacher_id = ? AND ts.is_active = 1 AND say.is_active = 1 AND sub.is_active = 1 AND ay.is_active = 1

                UNION

                SELECT say.academic_year_id AS ay_id
                FROM subject_academic_years say
                JOIN subjects sub ON say.subject_id = sub.id
                JOIN teachers t ON sub.id = t.subject_id
                JOIN academic_years ay ON say.academic_year_id = ay.id
                WHERE t.id = ? AND say.is_active = 1 AND sub.is_active = 1 AND ay.is_active = 1

                UNION

                SELECT c.academic_year_id AS ay_id
                FROM courses c
                JOIN academic_years ay ON c.academic_year_id = ay.id
                WHERE (c.teacher_id = ? OR (c.created_by_type = 'teacher' AND c.created_by_id = ?) OR c.id IN (SELECT course_id FROM course_teachers WHERE teacher_id = ? AND is_active = 1))
                  AND c.academic_year_id IS NOT NULL AND ay.is_active = 1
            ) combined_ays
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "iiiii", $teacherId, $teacherId, $teacherId, $teacherId, $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $academicYears = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ayId = (int)$row['ay_id'];
            $detail = get_academic_year_details($con, $ayId);
            if ($detail) {
                $academicYears[] = $detail;
            }
        }
        return $academicYears;
    }
}

if (!function_exists('get_teacher_academic_years_for_subject')) {
    // Grades this teacher may actually create a SUBJECT-scoped session for, given the specific
    // (subject, grade) assignments made on their profile - unlike get_teacher_academic_years_details()
    // above, which returns every grade the teacher touches across ALL subjects/courses.
    function get_teacher_academic_years_for_subject($con, int $teacherId, int $subjectId): array {
        if (!$teacherId || !$subjectId) return [];

        $stmt = mysqli_prepare($con, "SELECT academic_year_id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND is_active = 1");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "ii", $teacherId, $subjectId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $specificIds = [];
        $hasBlanketAssignment = false;
        $hasAnyAssignment = false;
        while ($row = mysqli_fetch_assoc($res)) {
            $hasAnyAssignment = true;
            if ($row['academic_year_id']) {
                $specificIds[] = (int)$row['academic_year_id'];
            } else {
                $hasBlanketAssignment = true;
            }
        }

        // Legacy single-subject teachers (teachers.subject_id, no teacher_subjects row) are a
        // blanket assignment too - same as before this feature existed.
        if (!$hasAnyAssignment) {
            $legacyStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE id = ? AND subject_id = ? LIMIT 1");
            mysqli_stmt_bind_param($legacyStmt, "ii", $teacherId, $subjectId);
            mysqli_stmt_execute($legacyStmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($legacyStmt))) {
                $hasBlanketAssignment = true;
            }
        }

        $academicYearIds = $specificIds;
        if ($hasBlanketAssignment) {
            $sayStmt = mysqli_prepare($con, "SELECT academic_year_id FROM subject_academic_years WHERE subject_id = ? AND is_active = 1");
            mysqli_stmt_bind_param($sayStmt, "i", $subjectId);
            mysqli_stmt_execute($sayStmt);
            $sayRes = mysqli_stmt_get_result($sayStmt);
            while ($sayRow = mysqli_fetch_assoc($sayRes)) {
                $academicYearIds[] = (int)$sayRow['academic_year_id'];
            }
        }

        $academicYearIds = array_values(array_unique($academicYearIds));

        $academicYears = [];
        foreach ($academicYearIds as $ayId) {
            $detail = get_academic_year_details($con, $ayId);
            if ($detail) $academicYears[] = $detail;
        }
        return $academicYears;
    }
}

if (!function_exists('get_student_parents_details')) {
    function get_student_parents_details($con, $studentId) {
        if (!$studentId) return [];

        $stmt = mysqli_prepare($con, "
            SELECT p.id, p.name, p.email, p.image, p.city_id, p.is_email_verified, p.is_active, p.created_at
            FROM parents p
            JOIN parent_students ps ON p.id = ps.parent_id
            WHERE ps.student_id = ? AND ps.is_active = 1
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $parents = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $cId = $row['city_id'] ? (int)$row['city_id'] : null;
            $row['city_id'] = $cId;
            $row['is_email_verified'] = (int)$row['is_email_verified'];
            $row['is_active'] = (int)$row['is_active'];
            $row['city'] = get_city_details($con, $cId);
            $parents[] = format_response_images($row);
        }
        return $parents;
    }
}

if (!function_exists('get_role_details')) {
    function get_role_details($con, $roleId) {
        if (!$roleId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code, is_system FROM roles WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $roleId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['is_system'] = (int)$row['is_system'];

        $tStmt = mysqli_prepare($con, "SELECT rt.language_id, l.code AS language_code, rt.name, rt.description FROM roles_translations rt JOIN languages l ON rt.language_id = l.id WHERE rt.role_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        $descAr = null;
        $descEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $roleId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
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
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
            $row['description_ar'] = $descAr;
            $row['description_en'] = $descEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('get_permission_details')) {
    function get_permission_details($con, $permissionId) {
        if (!$permissionId) return null;
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, code, module FROM permissions WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $permissionId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];

        $tStmt = mysqli_prepare($con, "SELECT pt.language_id, l.code AS language_code, pt.name, pt.description FROM permissions_translations pt JOIN languages l ON pt.language_id = l.id WHERE pt.permission_id = ?");
        $translations = [];
        $nameAr = null;
        $nameEn = null;
        $descAr = null;
        $descEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $permissionId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
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
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['name_ar'] = $nameAr;
            $row['name_en'] = $nameEn;
            $row['description_ar'] = $descAr;
            $row['description_en'] = $descEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('get_city_translations_all')) {
    function get_city_translations_all($con, $cityId) {
        $stmt = mysqli_prepare($con, "
            SELECT ct.language_id, l.code AS language_code, ct.name
            FROM cities_translations ct
            JOIN languages l ON ct.language_id = l.id
            WHERE ct.city_id = ?
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $cityId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $translations = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['language_id'] = (int)$row['language_id'];
            $translations[] = $row;
        }
        return $translations;
    }
}

if (!function_exists('get_subject_translations_all')) {
    function get_subject_translations_all($con, $subjectId) {
        $stmt = mysqli_prepare($con, "
            SELECT st.language_id, l.code AS language_code, st.name, st.description
            FROM subjects_translations st
            JOIN languages l ON st.language_id = l.id
            WHERE st.subject_id = ?
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $subjectId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $translations = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['language_id'] = (int)$row['language_id'];
            $translations[] = $row;
        }
        return $translations;
    }
}

if (!function_exists('get_role_translations_all')) {
    function get_role_translations_all($con, $roleId) {
        $stmt = mysqli_prepare($con, "
            SELECT rt.language_id, l.code AS language_code, rt.name, rt.description
            FROM roles_translations rt
            JOIN languages l ON rt.language_id = l.id
            WHERE rt.role_id = ?
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $roleId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $translations = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['language_id'] = (int)$row['language_id'];
            $translations[] = $row;
        }
        return $translations;
    }
}

if (!function_exists('get_permission_translations_all')) {
    function get_permission_translations_all($con, $permissionId) {
        $stmt = mysqli_prepare($con, "
            SELECT pt.language_id, l.code AS language_code, pt.name, pt.description
            FROM permissions_translations pt
            JOIN languages l ON pt.language_id = l.id
            WHERE pt.permission_id = ?
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $permissionId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $translations = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['language_id'] = (int)$row['language_id'];
            $translations[] = $row;
        }
        return $translations;
    }
}

if (!function_exists('get_course_teachers_details')) {
    function get_course_teachers_details($con, $courseId) {
        if (!$courseId) return [];

        $stmt = mysqli_prepare($con, "
            SELECT t.id, t.name, t.email, t.whatsapp, t.specialization, t.experience, t.image, t.rating
            FROM course_teachers ct
            JOIN teachers t ON ct.teacher_id = t.id
            WHERE ct.course_id = ? AND ct.is_active = 1 AND t.is_active = 1
            ORDER BY t.id ASC
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $courseId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $teachers = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['rating'] = (float)$row['rating'];
            $teachers[] = format_response_images($row);
        }
        return $teachers;
    }
}

if (!function_exists('get_course_created_by_details')) {
    function get_course_created_by_details($con, ?string $type, ?int $id) {
        $type = strtolower($type ?: 'admin');
        if (!$id || $id <= 0) {
            return [
                'type' => $type,
                'id' => null,
                'name' => ($type === 'teacher') ? 'Teacher' : 'Admin'
            ];
        }

        if ($type === 'teacher') {
            $stmt = mysqli_prepare($con, "SELECT id, name, email, image FROM teachers WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($row) {
                    $row['id'] = (int)$row['id'];
                    $row['type'] = 'teacher';
                    return format_response_images($row);
                }
            }
        } else {
            $stmt = mysqli_prepare($con, "SELECT id, name, email, image FROM admins WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($row) {
                    $row['id'] = (int)$row['id'];
                    $row['type'] = 'admin';
                    return format_response_images($row);
                }
            }
        }

        return [
            'type' => $type,
            'id' => (int)$id,
            'name' => ($type === 'teacher') ? 'Teacher' : 'Admin'
        ];
    }
}

if (!function_exists('get_teacher_details')) {
    function get_teacher_details($con, $teacherId) {
        if (!$teacherId) return null;
        $stmt = mysqli_prepare($con, "SELECT id, name, email, whatsapp, experience, specialization, biography, qualifications, rating, image, description, is_active FROM teachers WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['rating'] = (float)$row['rating'];
        $row['is_active'] = (int)$row['is_active'];
        return format_response_images($row);
    }
}

if (!function_exists('get_student_details')) {
    function get_student_details($con, $studentId) {
        if (!$studentId) return null;
        $stmt = mysqli_prepare($con, "SELECT id, school_id, city_id, name, age, phone, email, image, is_email_verified, is_active FROM students WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['school_id'] = $row['school_id'] ? (int)$row['school_id'] : null;
        $row['city_id'] = $row['city_id'] ? (int)$row['city_id'] : null;
        $row['age'] = $row['age'] ? (int)$row['age'] : null;
        $row['is_active'] = (int)$row['is_active'];
        return format_response_images($row);
    }
}

if (!function_exists('get_student_academic_year_id')) {
    function get_student_academic_year_id($con, int $studentId): ?int {
        if ($studentId <= 0) return null;
        $stmt = mysqli_prepare($con, "SELECT academic_year_id FROM students WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return ($row && $row['academic_year_id']) ? (int)$row['academic_year_id'] : null;
    }
}

if (!function_exists('get_teacher_subject_assignments')) {
    // Raw teacher_subjects pivot rows (one per subject+grade pairing), unlike
    // get_teacher_subjects_details() which merges everything down to distinct subject ids.
    function get_teacher_subject_assignments($con, $teacherId) {
        if (!$teacherId) return [];
        $stmt = mysqli_prepare($con, "SELECT id, subject_id, academic_year_id FROM teacher_subjects WHERE teacher_id = ? AND is_active = 1 ORDER BY subject_id ASC");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $assignments = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $subjectId = (int)$row['subject_id'];
            $academicYearId = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
            $assignments[] = [
                'subject_id' => $subjectId,
                'subject' => get_subject_details($con, $subjectId),
                'academic_year_id' => $academicYearId,
                'academic_year' => $academicYearId ? get_academic_year_details($con, $academicYearId) : null,
            ];
        }
        return $assignments;
    }
}

if (!function_exists('get_course_details')) {
    function get_course_details($con, $courseId) {
        if (!$courseId) return null;
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $stmt = mysqli_prepare($con, "
            SELECT c.id, c.subject_id, c.category_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.kind, c.duration_months, c.price, c.image, c.is_active,
                   COALESCE(ct.name, c.slug) AS name, ct.description
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            WHERE c.id = ? LIMIT 1
        ");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $courseId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['type'] = $row['type'] ?? 'recorded';
        $row['kind'] = $row['kind'] ?? 'normal';
        $row['duration_months'] = isset($row['duration_months']) && $row['duration_months'] !== null ? (int)$row['duration_months'] : null;
        $row['price'] = (float)$row['price'];
        $row['is_active'] = (int)$row['is_active'];
        return format_response_images($row);
    }
}
