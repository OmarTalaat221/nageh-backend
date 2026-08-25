<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../student/exams/manage.php'; // get_exam_summary / get_exam_attempt_details

if (!function_exists('teacher_owns_exam')) {
    function teacher_owns_exam($con, int $examId, int $teacherId): bool {
        $stmt = mysqli_prepare($con, "
            SELECT ce.id FROM course_exams ce
            LEFT JOIN exam_teachers et ON ce.id = et.exam_id
            WHERE ce.id = ? AND ((ce.created_by_type = 'teacher' AND ce.created_by_id = ?) OR (et.teacher_id = ? AND et.is_active = 1))
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "iii", $examId, $teacherId, $teacherId);
        mysqli_stmt_execute($stmt);
        return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

if (!function_exists('teacherListExamAttempts')) {
    function teacherListExamAttempts($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $examId = (int)($_GET['exam_id'] ?? 0);
        if ($examId <= 0) return ['status' => 'error', 'message' => 'exam_id is required'];
        if (!teacher_owns_exam($con, $examId, $teacherId)) {
            return ['status' => 'error', 'message' => 'This exam is not owned by or assigned to you'];
        }
        return adminListExamAttempts($con, $examId);
    }
}

if (!function_exists('adminListExamAttempts')) {
    function adminListExamAttempts($con, ?int $forcedExamId = null) {
        $examId = $forcedExamId ?? (int)($_GET['exam_id'] ?? 0);
        $status = strtolower(trim($_GET['status'] ?? ''));

        $where = [];
        $params = [];
        $types = "";
        if ($examId > 0) { $where[] = "exam_id = ?"; $params[] = $examId; $types .= "i"; }
        if (in_array($status, ['in_progress', 'submitted', 'graded'])) { $where[] = "status = ?"; $params[] = $status; $types .= "s"; }
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT id FROM exam_attempts {$whereClause} ORDER BY id DESC";
        $stmt = mysqli_prepare($con, $sql);
        if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_exam_attempt_details($con, (int)$row['id'], true);
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('teacherGetExamAttemptDetail')) {
    function teacherGetExamAttemptDetail($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $id = (int)($_GET['id'] ?? $_GET['attempt_id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'attempt id is required'];

        $detail = get_exam_attempt_details($con, $id, true);
        if (!$detail) return ['status' => 'error', 'message' => 'Attempt not found'];
        if (!teacher_owns_exam($con, $detail['exam_id'], $teacherId)) {
            return ['status' => 'error', 'message' => 'This exam is not owned by or assigned to you'];
        }

        return ['status' => 'success', 'data' => $detail];
    }
}

if (!function_exists('adminGetExamAttemptDetail')) {
    function adminGetExamAttemptDetail($con) {
        $id = (int)($_GET['id'] ?? $_GET['attempt_id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'attempt id is required'];

        $detail = get_exam_attempt_details($con, $id, true);
        if (!$detail) return ['status' => 'error', 'message' => 'Attempt not found'];

        return ['status' => 'success', 'data' => $detail];
    }
}

/**
 * Shared grading routine used by both the teacher and admin endpoints. Lets the grader override
 * any question's score_awarded/teacher_note (typically written questions the auto-corrector
 * couldn't score, but nothing stops correcting an mcq/true_false score too), then recomputes the
 * attempt's total_score and marks it 'graded'.
 */
if (!function_exists('grade_exam_attempt')) {
    function grade_exam_attempt($con, int $attemptId, array $answerGrades, string $graderType, int $graderId) {
        foreach ($answerGrades as $g) {
            $questionId = (int)($g['question_id'] ?? 0);
            $scoreAwarded = isset($g['score_awarded']) ? (float)$g['score_awarded'] : null;
            $teacherNote = isset($g['teacher_note']) ? trim($g['teacher_note']) : null;
            if ($questionId <= 0 || $scoreAwarded === null) continue;

            $updateFields = ["score_awarded = ?", "is_correct = ?"];
            $qScore = mysqli_fetch_assoc(mysqli_query($con, "SELECT score FROM questions WHERE id = {$questionId} LIMIT 1"));
            $maxQScore = $qScore ? (float)$qScore['score'] : $scoreAwarded;
            $isCorrect = $scoreAwarded >= $maxQScore ? 1 : ($scoreAwarded <= 0 ? 0 : null);

            if ($teacherNote !== null) {
                $stmt = mysqli_prepare($con, "UPDATE exam_attempt_answers SET score_awarded = ?, is_correct = ?, teacher_note = ? WHERE attempt_id = ? AND question_id = ?");
                mysqli_stmt_bind_param($stmt, "disii", $scoreAwarded, $isCorrect, $teacherNote, $attemptId, $questionId);
            } else {
                $stmt = mysqli_prepare($con, "UPDATE exam_attempt_answers SET score_awarded = ?, is_correct = ? WHERE attempt_id = ? AND question_id = ?");
                mysqli_stmt_bind_param($stmt, "diii", $scoreAwarded, $isCorrect, $attemptId, $questionId);
            }
            mysqli_stmt_execute($stmt);
        }

        $totalRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COALESCE(SUM(score_awarded),0) AS total FROM exam_attempt_answers WHERE attempt_id = {$attemptId}"));
        $total = (float)($totalRow['total'] ?? 0);

        $uStmt = mysqli_prepare($con, "UPDATE exam_attempts SET status = 'graded', total_score = ?, graded_at = NOW(), graded_by_type = ?, graded_by_id = ? WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, "dsii", $total, $graderType, $graderId, $attemptId);
        mysqli_stmt_execute($uStmt);
    }
}

if (!function_exists('teacherGradeExamAttempt')) {
    function teacherGradeExamAttempt($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $attemptId = (int)($body['attempt_id'] ?? $body['id'] ?? 0);
        $answers = $body['answers'] ?? [];

        if ($attemptId <= 0 || !is_array($answers)) {
            return ['status' => 'error', 'message' => 'attempt_id and answers array are required'];
        }

        $detail = get_exam_attempt_details($con, $attemptId);
        if (!$detail) return ['status' => 'error', 'message' => 'Attempt not found'];
        if (!teacher_owns_exam($con, $detail['exam_id'], $teacherId)) {
            return ['status' => 'error', 'message' => 'This exam is not owned by or assigned to you'];
        }

        grade_exam_attempt($con, $attemptId, $answers, 'teacher', $teacherId);

        return ['status' => 'success', 'message' => 'Attempt graded successfully', 'data' => get_exam_attempt_details($con, $attemptId, true)];
    }
}

if (!function_exists('adminGradeExamAttempt')) {
    function adminGradeExamAttempt($con, $adminData) {
        $adminId = (int)($adminData['id'] ?? 0);
        $body = read_json_body();
        $attemptId = (int)($body['attempt_id'] ?? $body['id'] ?? 0);
        $answers = $body['answers'] ?? [];

        if ($attemptId <= 0 || !is_array($answers)) {
            return ['status' => 'error', 'message' => 'attempt_id and answers array are required'];
        }

        $detail = get_exam_attempt_details($con, $attemptId);
        if (!$detail) return ['status' => 'error', 'message' => 'Attempt not found'];

        grade_exam_attempt($con, $attemptId, $answers, 'admin', $adminId);

        return ['status' => 'success', 'message' => 'Attempt graded successfully', 'data' => get_exam_attempt_details($con, $attemptId, true)];
    }
}
