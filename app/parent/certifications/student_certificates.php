<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('get_student_certificates')) {
    function get_student_certificates($con, int $studentId, string $filter = 'all') {

        if ($studentId <= 0) {
            return [
                'summary'      => [
                    'total_certificates'   => 0,
                    'excellent_count'      => 0,
                    'very_good_count'      => 0,
                    'good_count'           => 0,
                    'pass_count'           => 0,
                    'average_score'        => 0,
                    'certificate_types'    => [],
                ],
                'certificates' => []
            ];
        }

        $sql = "SELECT 
                    sc.id                    AS student_certificate_id,
                    sc.certificate_id        AS certificate_id,
                    sc.student_id            AS student_id,
                    sc.course_id             AS sc_course_id,
                    sc.exam_id               AS exam_id,
                    sc.certificate_code      AS certificate_code,
                    sc.pdf_url               AS pdf_url,
                    sc.score_achieved        AS score_achieved,
                    sc.grade_obtained        AS grade_obtained,
                    sc.issue_date            AS issue_date,
                    sc.created_at            AS created_at,

                    cert.code                AS cert_code,
                    cert.score               AS cert_max_score,
                    cert.certificate_type    AS certificate_type,
                    cert.image               AS cert_image,
                    cert.is_active           AS cert_is_active,
                    cert.academic_year_id    AS academic_year_id,
                    cert.subject_id          AS cert_subject_id,
                    cert.course_id           AS cert_course_id,
                    cert.created_by_type     AS created_by_type,
                    cert.created_by_id       AS created_by_id,

                    c.id                     AS course_id,
                    c.slug                   AS course_slug,
                    c.type                   AS course_type,
                    c.image                  AS course_image,
                    c.lessons_count          AS course_lessons_count,
                    c.category_id            AS course_category_id,
                    c.subject_id             AS course_subject_id,
                    c.teacher_id             AS course_teacher_id,

                    ce.id                    AS exam_id_ref,
                    ce.subject_id            AS exam_subject_id,
                    ce.course_id             AS exam_course_id,
                    ce.unit_id               AS exam_unit_id,
                    ce.duration_minutes      AS exam_duration_minutes,
                    ce.total_marks           AS exam_total_marks,
                    ce.pass_marks            AS exam_pass_marks
                FROM student_certificates sc
                INNER JOIN certificates cert ON cert.id = sc.certificate_id
                LEFT  JOIN courses      c    ON c.id    = sc.course_id
                LEFT  JOIN course_exams ce   ON ce.id   = sc.exam_id
                WHERE sc.student_id = ?
                  AND cert.is_active = 1
                ORDER BY sc.issue_date DESC, sc.created_at DESC";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return [
                'summary'      => [],
                'certificates' => [],
                'error'        => mysqli_error($con)
            ];
        }

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $certificates      = [];
        $totalScoreSum     = 0;
        $totalMaxSum       = 0;
        $excellentCount    = 0;
        $veryGoodCount     = 0;
        $goodCount         = 0;
        $passCount         = 0;
        $typesCounter      = [];

        while ($row = mysqli_fetch_assoc($result)) {

            $scoreAchieved = $row['score_achieved'] !== null ? (float)$row['score_achieved'] : 0;
            $maxScore      = $row['cert_max_score']  !== null ? (float)$row['cert_max_score']  : 100;
            if ($maxScore <= 0) { $maxScore = 100; }

            $percent = round(($scoreAchieved / $maxScore) * 100, 2);

            // ---- grade label (Arabic) ----
            if ($percent >= 90) {
                $gradeLabel = 'ممتاز';
                $gradeKey   = 'excellent';
                $excellentCount++;
            } elseif ($percent >= 80) {
                $gradeLabel = 'جيد جدًا';
                $gradeKey   = 'very_good';
                $veryGoodCount++;
            } elseif ($percent >= 65) {
                $gradeLabel = 'جيد';
                $gradeKey   = 'good';
                $goodCount++;
            } elseif ($percent >= 50) {
                $gradeLabel = 'مقبول';
                $gradeKey   = 'pass';
                $passCount++;
            } else {
                $gradeLabel = 'راسب';
                $gradeKey   = 'fail';
            }

            // ---- certificate type label ----
            $type = $row['certificate_type'];
            if ($type === 'academic') {
                $typeLabel = 'تفوق دراسي';
            } elseif ($type === 'training' || $type === 'course') {
                $typeLabel = 'دورة تدريبية';
            } elseif ($type === 'attendance') {
                $typeLabel = 'حضور وانضباط';
            } elseif ($type === 'behavior') {
                $typeLabel = 'سلوك وانضباط';
            } elseif ($type === 'achievement') {
                $typeLabel = 'إنجاز';
            } elseif ($type === 'participation') {
                $typeLabel = 'مشاركة';
            } else {
                $typeLabel = $type ?: 'شهادة';
            }

            // ---- type counter ----
            if (!isset($typesCounter[$type])) {
                $typesCounter[$type] = [
                    'type'  => $type,
                    'label' => $typeLabel,
                    'count' => 0,
                ];
            }
            $typesCounter[$type]['count']++;

            // ---- accumulate for average ----
            $totalScoreSum += $scoreAchieved;
            $totalMaxSum   += $maxScore;

            $certificates[] = [
                'id'                     => (int)$row['student_certificate_id'],
                'certificate_id'         => (int)$row['certificate_id'],
                'certificate_code'       => $row['certificate_code'] ?: $row['cert_code'],
                'title'                  => $row['course_slug'] ?: $row['cert_code'],
                'image'                  => $row['cert_image'],
                'pdf_url'                => $row['pdf_url'],
                'certificate_type'       => $type,
                'certificate_type_label' => $typeLabel,

                'score_achieved'         => $scoreAchieved,
                'max_score'              => $maxScore,
                'percent'                => $percent,
                'grade_obtained'         => $row['grade_obtained'] ?: $gradeLabel,
                'grade_key'              => $gradeKey,
                'grade_label'            => $gradeLabel,

                'issue_date'             => $row['issue_date'],
                'created_at'             => $row['created_at'],
                'academic_year_id'       => $row['academic_year_id'] ? (int)$row['academic_year_id'] : null,

                'created_by_type'        => $row['created_by_type'],
                'created_by_id'          => $row['created_by_id'] ? (int)$row['created_by_id'] : null,

                'course' => $row['course_id'] ? [
                    'id'            => (int)$row['course_id'],
                    'slug'          => $row['course_slug'],
                    'type'          => $row['course_type'],
                    'image'         => $row['course_image'],
                    'lessons_count' => (int)($row['course_lessons_count'] ?? 0),
                    'category_id'   => $row['course_category_id'] ? (int)$row['course_category_id'] : null,
                    'subject_id'    => $row['course_subject_id']  ? (int)$row['course_subject_id']  : null,
                    'teacher_id'    => $row['course_teacher_id']  ? (int)$row['course_teacher_id']  : null,
                ] : null,

                'exam' => $row['exam_id'] ? [
                    'id'               => (int)$row['exam_id'],
                    'subject_id'       => $row['exam_subject_id'] ? (int)$row['exam_subject_id'] : null,
                    'course_id'        => $row['exam_course_id']  ? (int)$row['exam_course_id']  : null,
                    'unit_id'          => $row['exam_unit_id']    ? (int)$row['exam_unit_id']    : null,
                    'total_marks'      => $row['exam_total_marks'] !== null ? (float)$row['exam_total_marks'] : null,
                    'pass_marks'       => $row['exam_pass_marks']  !== null ? (float)$row['exam_pass_marks']  : null,
                    'duration_minutes' => $row['exam_duration_minutes'] !== null ? (int)$row['exam_duration_minutes'] : null,
                ] : null,
            ];
        }

        mysqli_stmt_close($stmt);

        // ---- apply filter (all / type key / grade key) ----
        $filter = strtolower(trim($filter));
        if ($filter !== '' && $filter !== 'all') {
            $certificates = array_values(array_filter($certificates, function ($c) use ($filter) {
                return $c['certificate_type'] === $filter
                    || $c['grade_key']        === $filter;
            }));
        }

        // ---- average score ----
        $averageScore = 0;
        if ($totalMaxSum > 0) {
            $averageScore = round(($totalScoreSum / $totalMaxSum) * 100, 2);
        }

        return [
            'summary' => [
                'total_certificates' => count($certificates),
                'excellent_count'    => $excellentCount,
                'very_good_count'    => $veryGoodCount,
                'good_count'         => $goodCount,
                'pass_count'         => $passCount,
                'average_score'      => $averageScore,
                'certificate_types'  => array_values($typesCounter),
            ],
            'certificates' => format_response_images($certificates),
        ];
    }
}