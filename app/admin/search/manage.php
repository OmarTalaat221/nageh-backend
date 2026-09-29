<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/rbac.php';

/**
 * The search box in the dashboard header.
 *
 * It has been an <input> with no state and no handler since the dashboard was built - typing in
 * it did nothing at all. This is what it calls.
 *
 * Two rules shape the whole file:
 *
 *   1. It returns only what THIS admin is allowed to open. Search is a common way to leak the
 *      existence of things: an admin with no student permission typing a name and seeing "Ahmed
 *      Mahmoud - student" has learned something the platform meant to keep from them, even
 *      without opening the row. Every group is gated on the same permission as its screen.
 *
 *   2. Every result carries the link to the page that shows it. A result you cannot click is a
 *      riddle, and the caller should not have to know how each section builds its URLs.
 */

if (!function_exists('admin_search_escape_like')) {
    /**
     * `%` and `_` are wildcards inside LIKE. A student searching for "100%" would otherwise match
     * everything, and a search for "a_b" would match "axb".
     */
    function admin_search_escape_like($con, string $term): string {
        return mysqli_real_escape_string($con, str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term));
    }
}

if (!function_exists('adminGlobalSearch')) {
    function adminGlobalSearch($con, $adminData = null) {
        $raw = trim((string)($_GET['q'] ?? $_GET['query'] ?? $_GET['search'] ?? ''));

        // Two characters matches most of the database and helps nobody.
        if (mb_strlen($raw) < 2) {
            return [
                'status' => 'success',
                'query' => $raw,
                'total' => 0,
                'data' => [],
                'message' => mb_strlen($raw) > 0 ? trans('search_too_short') : null,
            ];
        }

        $perGroup = max(1, min(10, (int)($_GET['limit'] ?? 5)));
        $like = '%' . admin_search_escape_like($con, $raw) . '%';


        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $admin = is_array($adminData) ? $adminData : (array)$adminData;
        $can = fn(string $code) => hasPermission($con, $admin, $code);

        $groups = [];

        $collect = function (string $type, string $permission, string $sql, string $labelAr, string $labelEn)
            use ($con, &$groups, $can, $langId) {
            if (!$can($permission)) return;

            $res = @mysqli_query($con, $sql);
            if (!$res) return;

            $items = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];

                // The link carries the row's OWN title, not the term that was typed: searching
                // "geo" and landing on a page filtered by "geo" just shows every match again,
                // while filtering by the title shows the one that was actually clicked. Encoded
                // here rather than in SQL because an Arabic title cannot go into a URL raw.
                if (!empty($row['link']) && strpos($row['link'], '?q=') !== false) {
                    [$path, $q] = explode('?q=', $row['link'], 2);
                    $row['link'] = $q === '' ? $path : $path . '?q=' . rawurlencode($q);
                }

                $items[] = format_response_images($row);
            }

            if ($items) {
                $groups[] = [
                    'type' => $type,
                    'label' => $langId === 2 ? $labelEn : $labelAr,
                    'items' => $items,
                ];
            }
        };

        // ---- people ---------------------------------------------------------------------------

        $collect('students', 'manage_students', "
            SELECT s.id, s.name AS title,
                   CONCAT_WS(' · ', NULLIF(s.email,''), NULLIF(s.phone,'')) AS subtitle,
                   s.image, CONCAT('/users?q=', s.name) AS link, s.is_active
            FROM students s
            WHERE s.name LIKE '{$like}' OR s.email LIKE '{$like}' OR s.phone LIKE '{$like}'
            ORDER BY s.id DESC LIMIT {$perGroup}
        ", 'الطلاب', 'Students');

        $collect('teachers', 'manage_teachers', "
            SELECT t.id, t.name AS title,
                   CONCAT_WS(' · ', NULLIF(t.email,''), NULLIF(t.specialization,'')) AS subtitle,
                   t.image, CONCAT('/teachers?q=', t.name) AS link, t.is_active
            FROM teachers t
            WHERE t.name LIKE '{$like}' OR t.email LIKE '{$like}' OR t.specialization LIKE '{$like}'
            ORDER BY t.id DESC LIMIT {$perGroup}
        ", 'المدرسون', 'Teachers');

        $collect('parents', 'manage_parents', "
            SELECT p.id, p.name AS title, p.email AS subtitle, p.image,
                   CONCAT('/parents?q=', p.name) AS link, p.is_active
            FROM parents p
            WHERE p.name LIKE '{$like}' OR p.email LIKE '{$like}'
            ORDER BY p.id DESC LIMIT {$perGroup}
        ", 'أولياء الأمور', 'Parents');

        // ---- academic ---------------------------------------------------------------------------
        //
        // Courses and packages are the same table split on `kind`, and they have separate screens,
        // so a package must not be returned as a course the admin then cannot find under Courses.

        $collect('courses', 'manage_courses', "
            SELECT c.id, COALESCE(ct.name, c.slug) AS title,
                   CONCAT_WS(' · ', NULLIF(COALESCE(st.name, ''), ''), NULLIF(COALESCE(ayt.name, ''), '')) AS subtitle,
                   c.image, CONCAT('/courses/', c.id, '/units') AS link, c.is_active
            FROM courses c
            LEFT JOIN courses_translations ct ON ct.course_id = c.id AND ct.language_id = {$langId}
            LEFT JOIN subjects_translations st ON st.subject_id = c.subject_id AND st.language_id = {$langId}
            LEFT JOIN academic_years_translations ayt ON ayt.academic_year_id = c.academic_year_id AND ayt.language_id = {$langId}
            WHERE (c.kind IS NULL OR c.kind <> 'package')
              AND (ct.name LIKE '{$like}' OR c.slug LIKE '{$like}')
            ORDER BY c.id DESC LIMIT {$perGroup}
        ", 'الكورسات', 'Courses');

        $collect('packages', 'manage_packages', "
            SELECT c.id, COALESCE(ct.name, c.slug) AS title,
                   COALESCE(ayt.name, '') AS subtitle,
                   c.image, CONCAT('/packages?q=', COALESCE(ct.name, c.slug)) AS link, c.is_active
            FROM courses c
            LEFT JOIN courses_translations ct ON ct.course_id = c.id AND ct.language_id = {$langId}
            LEFT JOIN academic_years_translations ayt ON ayt.academic_year_id = c.academic_year_id AND ayt.language_id = {$langId}
            WHERE c.kind = 'package' AND (ct.name LIKE '{$like}' OR c.slug LIKE '{$like}')
            ORDER BY c.id DESC LIMIT {$perGroup}
        ", 'الباقات', 'Packages');

        $collect('books', 'manage_books', "
            SELECT b.id, COALESCE(bt.title, CONCAT('#', b.id)) AS title,
                   COALESCE(bt.description, '') AS subtitle,
                   b.cover_image AS image, CONCAT('/books?q=', COALESCE(bt.title, '')) AS link, b.is_active
            FROM course_books b
            LEFT JOIN course_books_translations bt ON bt.book_id = b.id AND bt.language_id = {$langId}
            WHERE bt.title LIKE '{$like}' OR bt.description LIKE '{$like}'
            ORDER BY b.id DESC LIMIT {$perGroup}
        ", 'الكتب', 'Books');

        $collect('subjects', 'manage_subjects', "
            SELECT s.id, COALESCE(st.name, s.code) AS title, s.code AS subtitle,
                   NULL AS image, CONCAT('/subjects?q=', COALESCE(st.name, s.code)) AS link, s.is_active
            FROM subjects s
            LEFT JOIN subjects_translations st ON st.subject_id = s.id AND st.language_id = {$langId}
            WHERE st.name LIKE '{$like}' OR s.code LIKE '{$like}'
            ORDER BY s.id DESC LIMIT {$perGroup}
        ", 'المواد الدراسية', 'Subjects');

        $collect('grades', 'manage_academic_years', "
            SELECT a.id, COALESCE(at2.name, a.code) AS title, a.code AS subtitle,
                   NULL AS image, CONCAT('/grades?q=', COALESCE(at2.name, a.code)) AS link, a.is_active
            FROM academic_years a
            LEFT JOIN academic_years_translations at2 ON at2.academic_year_id = a.id AND at2.language_id = {$langId}
            WHERE at2.name LIKE '{$like}' OR a.code LIKE '{$like}'
            ORDER BY a.id DESC LIMIT {$perGroup}
        ", 'الصفوف الدراسية', 'Grades');

        $collect('exams', 'manage_exams', "
            SELECT e.id, COALESCE(et.title, CONCAT('#', e.id)) AS title,
                   COALESCE(et.description, '') AS subtitle,
                   NULL AS image, CONCAT('/exams?q=', COALESCE(et.title, '')) AS link, e.is_active
            FROM course_exams e
            LEFT JOIN course_exams_translations et ON et.exam_id = e.id AND et.language_id = {$langId}
            WHERE et.title LIKE '{$like}' OR et.description LIKE '{$like}'
            ORDER BY e.id DESC LIMIT {$perGroup}
        ", 'الامتحانات', 'Exams');

        $total = array_sum(array_map(fn($g) => count($g['items']), $groups));

        return [
            'status' => 'success',
            'query' => $raw,
            'total' => $total,
            'data' => $groups,
        ];
    }
}
