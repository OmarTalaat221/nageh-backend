<?php
/**
 * The four kinds of certificate the platform awards, in one place.
 *
 * A certificate used to be reachable only through a template that carried a course, so awarding
 * one to a student meant first finding a template built for the right course. Most of what the
 * platform actually hands out is not about a course at all - it is a mark of how the student did -
 * so the four types below are what the admin picks from, and each one has a general template that
 * belongs to no course.
 *
 * The stored codes are the ones already in the database; only the labels changed. `pass` and
 * `distinction` are accepted as aliases because they read better than the codes they map to, and
 * the Arabic words are accepted too, so a client may send whichever it holds.
 */

if (!function_exists('certificate_type_catalogue')) {
    function certificate_type_catalogue() {
        return [
            'excellence'        => ['ar' => 'تميز',   'en' => 'Excellence',  'code' => 'CERT-GEN-EXCELLENCE'],
            'course_completion' => ['ar' => 'إجتياز', 'en' => 'Pass',        'code' => 'CERT-GEN-PASS'],
            'achievement'       => ['ar' => 'تفوق',   'en' => 'Distinction', 'code' => 'CERT-GEN-DISTINCTION'],
            'general'           => ['ar' => 'عامة',   'en' => 'General',     'code' => 'CERT-GEN-GENERAL'],
        ];
    }
}

if (!function_exists('certificate_type_normalize')) {
    /**
     * Turns whatever a client sent into one of the four stored codes, or '' if it is none of them.
     */
    function certificate_type_normalize($value) {
        $value = trim((string)$value);
        if ($value === '') return '';

        $catalogue = certificate_type_catalogue();
        if (isset($catalogue[$value])) return $value;

        $aliases = [
            'pass'        => 'course_completion',
            'completion'  => 'course_completion',
            'distinction' => 'achievement',
            'إجتياز'      => 'course_completion',
            'اجتياز'      => 'course_completion',
            'تميز'        => 'excellence',
            'تفوق'        => 'achievement',
            'عامة'        => 'general',
            'عام'         => 'general',
        ];

        $lower = mb_strtolower($value, 'UTF-8');
        if (isset($aliases[$lower])) return $aliases[$lower];
        if (isset($aliases[$value])) return $aliases[$value];

        return '';
    }
}

if (!function_exists('certificate_general_template_id')) {
    /**
     * The id of the general (course-less) certificate template for a type, created on first use.
     *
     * Created on demand rather than only by migration so that awarding a certificate by type works
     * on a database where migration 083 has not been applied yet, instead of failing with an error
     * that says nothing about why.
     */
    function certificate_general_template_id($con, $type, $creatorType = 'admin', $creatorId = 1) {
        $type = certificate_type_normalize($type);
        if ($type === '') return 0;

        $catalogue = certificate_type_catalogue();
        $entry = $catalogue[$type];

        $safeType = mysqli_real_escape_string($con, $type);
        $row = mysqli_fetch_assoc(mysqli_query($con, "
            SELECT id FROM certificates
            WHERE certificate_type = '{$safeType}'
              AND course_id IS NULL AND subject_id IS NULL AND is_active = 1
            ORDER BY id ASC LIMIT 1
        "));
        if ($row) return (int)$row['id'];

        // A code collision here means a row with this code exists but did not match above - it was
        // deactivated, or given a course. Fall back to a suffixed code rather than failing the
        // insert on the UNIQUE key.
        $code = $entry['code'];
        $exists = mysqli_fetch_assoc(mysqli_query($con, "SELECT id FROM certificates WHERE code = '" . mysqli_real_escape_string($con, $code) . "' LIMIT 1"));
        if ($exists) {
            $code = $entry['code'] . '-' . time();
        }

        $creatorType = $creatorType === 'teacher' ? 'teacher' : 'admin';
        $creatorId = (int)$creatorId > 0 ? (int)$creatorId : 1;
        $safeCode = mysqli_real_escape_string($con, $code);

        if (!mysqli_query($con, "
            INSERT INTO certificates (academic_year_id, subject_id, course_id, code, score, certificate_type, is_active, created_by_type, created_by_id)
            VALUES (NULL, NULL, NULL, '{$safeCode}', 0.00, '{$safeType}', 1, '{$creatorType}', {$creatorId})
        ")) {
            return 0;
        }

        $newId = (int)mysqli_insert_id($con);

        $nameAr = mysqli_real_escape_string($con, 'شهادة ' . $entry['ar']);
        $nameEn = mysqli_real_escape_string($con, $entry['en'] . ' Certificate');
        @mysqli_query($con, "INSERT INTO certificates_translations (certificate_id, language_id, name, description) VALUES ({$newId}, 1, '{$nameAr}', '') ON DUPLICATE KEY UPDATE name = VALUES(name)");
        @mysqli_query($con, "INSERT INTO certificates_translations (certificate_id, language_id, name, description) VALUES ({$newId}, 2, '{$nameEn}', '') ON DUPLICATE KEY UPDATE name = VALUES(name)");

        return $newId;
    }
}
