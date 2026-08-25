<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('get_student_exams_details')) {
    function get_student_exams_details($con, int $studentId) {

        if ($studentId <= 0) {
            return [
                'debug'    => "Invalid student_id: $studentId",
                'summary'  => [],
                'subjects' => [],
                'exams'    => []
            ];
        }

        $checkSql = "SELECT COUNT(*) AS total, 
                            GROUP_CONCAT(DISTINCT status) AS statuses 
                     FROM exam_attempts 
                     WHERE student_id = ?";
        
        $checkStmt = mysqli_prepare($con, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "i", $studentId);
        mysqli_stmt_execute($checkStmt);
        $checkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        mysqli_stmt_close($checkStmt);

        $sql = "SELECT 
                    ea.id,
                    ea.exam_id,
                    ea.student_id,
                    ea.status,
                    ea.total_score,
                    ea.max_score,
                    ea.started_at,
                    ea.submitted_at,
                    ea.graded_at,

                    ce.subject_id       AS exam_subject_id,
                    ce.course_id        AS exam_course_id,
                    ce.unit_id          AS exam_unit_id,
                    ce.duration_minutes AS exam_duration_minutes,
                    ce.total_marks      AS exam_total_marks,
                    ce.pass_marks       AS exam_pass_marks

                FROM exam_attempts ea
                LEFT JOIN course_exams ce ON ce.id = ea.exam_id
                WHERE ea.student_id = ?
                ORDER BY ea.started_at DESC";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return [
                'debug' => [
                    'sql_error' => mysqli_error($con),
                    'db_check'  => $checkRow,
                ],
                'summary'  => [],
                'subjects' => [],
                'exams'    => []
            ];
        }

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $exams        = [];
        $totalScoreSum = 0;
        $totalMaxSum   = 0;
        $highest       = null;
        $lowest        = null;
        $passedCount   = 0;
        $failedCount   = 0;

        while ($row = mysqli_fetch_assoc($result)) {

            $totalScore = $row['total_score'] !== null ? (float)$row['total_score'] : 0;
            $maxScore   = $row['max_score']   !== null ? (float)$row['max_score']   : ((float)($row['exam_total_marks'] ?? 100));
            $passMarks  = $row['exam_pass_marks'] !== null ? (float)$row['exam_pass_marks'] : ($maxScore * 0.5);

            $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
            $isPassed   = $totalScore >= $passMarks;

            if ($isPassed) $passedCount++;
            else $failedCount++;

            if ($highest === null || $percentage > $highest) $highest = $percentage;
            if ($lowest  === null || $percentage < $lowest)  $lowest  = $percentage;

            $totalScoreSum += $totalScore;
            $totalMaxSum   += $maxScore;

            $exams[] = [
                'id'               => (int)$row['id'],
                'exam_id'          => (int)$row['exam_id'],
                'status'           => $row['status'],
                'total_score'      => $totalScore,
                'max_score'        => $maxScore,
                'percentage'       => $percentage,
                'is_passed'        => $isPassed,
                'result_label'     => $isPassed ? 'ناجح' : 'راسب',
                'duration_minutes' => $row['exam_duration_minutes'] ? (int)$row['exam_duration_minutes'] : null,
                'exam_date'        => $row['submitted_at'] ?: $row['started_at'],
            ];
        }

        mysqli_stmt_close($stmt);

        $totalExams = count($exams);
        $average    = $totalMaxSum > 0 ? round(($totalScoreSum / $totalMaxSum) * 100, 0) : 0;

        return [
            'debug' => [
                'student_id'  => $studentId,
                'db_check'    => $checkRow,
                'rows_found'  => $totalExams,
            ],
            'summary' => [
                'average'       => (int)$average,
                'max_score'     => 100,
                'total_exams'   => $totalExams,
                'highest_score' => $highest !== null ? (int)round($highest) : 0,
                'lowest_score'  => $lowest  !== null ? (int)round($lowest)  : 0,
                'passed_count'  => $passedCount,
                'failed_count'  => $failedCount,
            ],
            'exams' => $exams,
        ];
    }
}