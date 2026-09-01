<?php
require_once __DIR__ . '/../../../libs/SimpleXLSXGen.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';

use Shuchkin\SimpleXLSXGen;

if (!function_exists('exportYesNo')) {
    function exportYesNo($value, $lang) {
        if ($lang === 'ar') {
            return $value ? 'نعم' : 'لا';
        }
        return $value ? 'Yes' : 'No';
    }
}

if (!function_exists('sendExcelExport')) {
    function sendExcelExport(array $header, array $rows, string $sheetName, string $lang, string $filenamePrefix) {
        $data = array_merge([$header], $rows);
        $xlsx = SimpleXLSXGen::fromArray($data, $sheetName);
        if ($lang === 'ar') {
            $xlsx->rightToLeft(true);
        }
        $filename = $filenamePrefix . '_' . gmdate('Ymd_His') . '.xlsx';
        $xlsx->downloadAs($filename);
        exit();
    }
}

if (!function_exists('adminExportStudents')) {
    function adminExportStudents($con) {
        $lang = get_request_lang($con);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
            $params = [$searchPattern, $searchPattern, $searchPattern];
            $types = "sss";
        }

        $sql = "SELECT s.id, s.name, s.email, s.phone, s.age, s.city_id, s.school_id, s.is_email_verified, s.is_active, s.created_at FROM students s {$whereClause} ORDER BY s.id DESC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'الاسم', 'البريد الإلكتروني', 'الهاتف', 'العمر', 'المدينة', 'المدرسة', 'مفعل البريد', 'نشط', 'تاريخ التسجيل']
            : ['ID', 'Name', 'Email', 'Phone', 'Age', 'City', 'School', 'Email Verified', 'Active', 'Registered At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $city = get_city_details($con, $row['city_id'] ? (int)$row['city_id'] : null);
            $school = get_school_details($con, $row['school_id'] ? (int)$row['school_id'] : null);
            $rows[] = [
                (int)$row['id'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['age'] ? (int)$row['age'] : '',
                $city['name'] ?? '',
                $school['name'] ?? '',
                exportYesNo((int)$row['is_email_verified'], $lang),
                exportYesNo((int)$row['is_active'], $lang),
                $row['created_at'],
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'الطلاب' : 'Students', $lang, 'students_export');
    }
}

if (!function_exists('adminExportTeachers')) {
    function adminExportTeachers($con) {
        $lang = get_request_lang($con);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (t.name LIKE ? OR t.email LIKE ? OR t.whatsapp LIKE ? OR t.specialization LIKE ?)";
            $params = [$searchPattern, $searchPattern, $searchPattern, $searchPattern];
            $types = "ssss";
        }

        $sql = "SELECT t.id, t.name, t.email, t.whatsapp, t.specialization, t.experience, t.rating, t.city_id, t.is_email_verified, t.is_active, t.created_at FROM teachers t {$whereClause} ORDER BY t.id DESC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'الاسم', 'البريد الإلكتروني', 'واتساب', 'التخصص', 'الخبرة', 'التقييم', 'المدينة', 'مفعل البريد', 'نشط', 'تاريخ التسجيل']
            : ['ID', 'Name', 'Email', 'WhatsApp', 'Specialization', 'Experience', 'Rating', 'City', 'Email Verified', 'Active', 'Registered At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $city = get_city_details($con, $row['city_id'] ? (int)$row['city_id'] : null);
            $rows[] = [
                (int)$row['id'],
                $row['name'],
                $row['email'],
                $row['whatsapp'],
                $row['specialization'],
                $row['experience'],
                $row['rating'],
                $city['name'] ?? '',
                exportYesNo((int)$row['is_email_verified'], $lang),
                exportYesNo((int)$row['is_active'], $lang),
                $row['created_at'],
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'المعلمون' : 'Teachers', $lang, 'teachers_export');
    }
}

if (!function_exists('adminExportAcademicYears')) {
    function adminExportAcademicYears($con) {
        $lang = get_request_lang($con);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (ay.code LIKE ? OR ayt.name LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $sql = "SELECT DISTINCT ay.id, ay.code, ay.number, ay.is_active, ay.created_at FROM academic_years ay LEFT JOIN academic_years_translations ayt ON ay.id = ayt.academic_year_id {$whereClause} ORDER BY ay.number ASC, ay.id ASC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'الكود', 'الاسم', 'الترتيب', 'نشط', 'تاريخ الإنشاء']
            : ['ID', 'Code', 'Name', 'Order', 'Active', 'Created At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ayId = (int)$row['id'];
            $nameAr = null;
            $nameEn = null;
            $transStmt = mysqli_prepare($con, "SELECT l.code AS language_code, ayt.name FROM academic_years_translations ayt JOIN languages l ON ayt.language_id = l.id WHERE ayt.academic_year_id = ?");
            mysqli_stmt_bind_param($transStmt, "i", $ayId);
            mysqli_stmt_execute($transStmt);
            $transRes = mysqli_stmt_get_result($transStmt);
            while ($t = mysqli_fetch_assoc($transRes)) {
                if ($t['language_code'] === 'ar') $nameAr = $t['name'];
                if ($t['language_code'] === 'en') $nameEn = $t['name'];
            }

            $rows[] = [
                $ayId,
                $row['code'],
                $lang === 'ar' ? ($nameAr ?? $nameEn ?? '') : ($nameEn ?? $nameAr ?? ''),
                $row['number'] ? (int)$row['number'] : '',
                exportYesNo((int)$row['is_active'], $lang),
                $row['created_at'],
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'الصفوف الدراسية' : 'Grades', $lang, 'grades_export');
    }
}

if (!function_exists('adminExportSubjects')) {
    function adminExportSubjects($con) {
        $lang = get_request_lang($con);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (sub.code LIKE ? OR subt.name LIKE ? OR subt.description LIKE ?)";
            $params = [$searchPattern, $searchPattern, $searchPattern];
            $types = "sss";
        }

        $sql = "SELECT DISTINCT sub.id, sub.code, sub.is_active, sub.created_at FROM subjects sub LEFT JOIN subjects_translations subt ON sub.id = subt.subject_id {$whereClause} ORDER BY sub.id DESC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'الكود', 'الاسم', 'نشط', 'تاريخ الإنشاء']
            : ['ID', 'Code', 'Name', 'Active', 'Created At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $subId = (int)$row['id'];
            $translations = get_subject_translations_all($con, $subId);
            $nameAr = null;
            $nameEn = null;
            foreach ($translations as $t) {
                if ($t['language_code'] === 'ar') $nameAr = $t['name'];
                if ($t['language_code'] === 'en') $nameEn = $t['name'];
            }

            $rows[] = [
                $subId,
                $row['code'],
                $lang === 'ar' ? ($nameAr ?? $nameEn ?? '') : ($nameEn ?? $nameAr ?? ''),
                exportYesNo((int)$row['is_active'], $lang),
                $row['created_at'],
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'المواد الدراسية' : 'Subjects', $lang, 'subjects_export');
    }
}

if (!function_exists('adminExportCities')) {
    function adminExportCities($con) {
        $lang = get_request_lang($con);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (c.code LIKE ? OR ct.name LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $sql = "SELECT DISTINCT c.id, c.code, c.is_active, c.created_at FROM cities c LEFT JOIN cities_translations ct ON c.id = ct.city_id {$whereClause} ORDER BY c.id DESC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'الكود', 'الاسم', 'نشط', 'تاريخ الإنشاء']
            : ['ID', 'Code', 'Name', 'Active', 'Created At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $city = get_city_details($con, (int)$row['id']);
            $rows[] = [
                (int)$row['id'],
                $row['code'],
                $city['name'] ?? '',
                exportYesNo((int)$row['is_active'], $lang),
                $row['created_at'],
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'المناطق' : 'Regions', $lang, 'regions_export');
    }
}

if (!function_exists('adminExportParents')) {
    function adminExportParents($con) {
        $lang = get_request_lang($con);
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (p.name LIKE ? OR p.email LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $sql = "SELECT p.id, p.name, p.email, p.city_id, p.is_email_verified, p.is_active, p.created_at FROM parents p {$whereClause} ORDER BY p.id DESC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $header = $lang === 'ar'
            ? ['المعرف', 'الاسم', 'البريد الإلكتروني', 'المدينة', 'مفعل البريد', 'نشط', 'تاريخ التسجيل']
            : ['ID', 'Name', 'Email', 'City', 'Email Verified', 'Active', 'Registered At'];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $city = get_city_details($con, $row['city_id'] ? (int)$row['city_id'] : null);
            $rows[] = [
                (int)$row['id'],
                $row['name'],
                $row['email'],
                $city['name'] ?? '',
                exportYesNo((int)$row['is_email_verified'], $lang),
                exportYesNo((int)$row['is_active'], $lang),
                $row['created_at'],
            ];
        }

        sendExcelExport($header, $rows, $lang === 'ar' ? 'أولياء الأمور' : 'Parents', $lang, 'parents_export');
    }
}
