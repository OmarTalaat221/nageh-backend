<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';

if (!function_exists('ensure_student_question_answers_table')) {
    function ensure_student_question_answers_table($con) {
    }
}

if (!function_exists('get_student_request_lang_info')) {
    function get_student_request_lang_info($con) {
        $langCode = has_request_lang_header() ? get_request_lang($con) : 'en';
        $stmt = mysqli_prepare($con, "SELECT id, code FROM languages WHERE code = ? AND is_active = 1 LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $langCode);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                return ['id' => (int)$row['id'], 'code' => $row['code']];
            }
        }
        if ($langCode === 'en') {
            return ['id' => 2, 'code' => 'en'];
        }
        return ['id' => 1, 'code' => 'ar'];
    }
}

if (!function_exists('get_student_question_statistics')) {
    function get_student_question_statistics($con, int $studentId, int $questionId) {
        ensure_student_question_answers_table($con);

        $res = mysqli_query($con, "
            SELECT 
                COUNT(*) AS times_answered,
                COALESCE(SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END), 0) AS correct_answers_count,
                COALESCE(SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END), 0) AS wrong_answers_count
            FROM student_question_answers
            WHERE student_id = {$studentId} AND question_id = {$questionId}
        ");
        $row = $res ? mysqli_fetch_assoc($res) : null;

        $timesAnswered = (int)($row['times_answered'] ?? 0);
        $correctCount = (int)($row['correct_answers_count'] ?? 0);
        $wrongCount = (int)($row['wrong_answers_count'] ?? 0);
        $percentage = $timesAnswered > 0 ? round(($correctCount / $timesAnswered) * 100, 2) : 0.00;

        $lastAttempt = null;
        $lRes = mysqli_query($con, "
            SELECT id, mcq_option_id, true_false_answer, written_answer_text, is_correct, score_awarded, created_at
            FROM student_question_answers
            WHERE student_id = {$studentId} AND question_id = {$questionId}
            ORDER BY id DESC LIMIT 1
        ");
        if ($lRes && $lRow = mysqli_fetch_assoc($lRes)) {
            $correctOptionId = null;
            $correctAnswer = null;

            $qTypeRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT question_type FROM questions WHERE id = {$questionId} LIMIT 1"));
            $qType = $qTypeRow['question_type'] ?? null;

            if ($qType === 'mcq') {
                $correctOptRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT id FROM mcq_options WHERE question_id = {$questionId} AND is_correct = 1 LIMIT 1"));
                $correctOptionId = $correctOptRow ? (int)$correctOptRow['id'] : null;
            } elseif ($qType === 'true_false') {
                $tfRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT correct_answer FROM true_false_answers WHERE question_id = {$questionId} LIMIT 1"));
                $correctAnswer = $tfRow ? (int)$tfRow['correct_answer'] : null;
            }

            $lastAttempt = [
                'id' => (int)$lRow['id'],
                'mcq_option_id' => $lRow['mcq_option_id'] ? (int)$lRow['mcq_option_id'] : null,
                'true_false_answer' => $lRow['true_false_answer'] !== null ? (int)$lRow['true_false_answer'] : null,
                'written_answer_text' => $lRow['written_answer_text'],
                'is_correct' => (int)$lRow['is_correct'],
                'score_awarded' => (float)$lRow['score_awarded'],
                'created_at' => $lRow['created_at'],
                'correct_option_id' => $correctOptionId,
                'correct_answer' => $correctAnswer
            ];
        }

        return [
            'times_answered' => $timesAnswered,
            'correct_answers_count' => $correctCount,
            'wrong_answers_count' => $wrongCount,
            'correct_percentage' => $percentage,
            'last_attempt' => $lastAttempt
        ];
    }
}

if (!function_exists('get_student_overall_statistics')) {
    function get_student_overall_statistics($con, int $studentId, int $courseId = 0, int $unitId = 0) {
        ensure_student_question_answers_table($con);

        $whereConds = ["sqa.student_id = {$studentId}"];
        if ($courseId > 0) {
            $whereConds[] = "sqa.course_id = {$courseId}";
        }
        if ($unitId > 0) {
            $whereConds[] = "sqa.unit_id = {$unitId}";
        }
        $whereClause = "WHERE " . implode(" AND ", $whereConds);

        $sql = "
            SELECT 
                COUNT(*) AS total_answers_count,
                COALESCE(SUM(CASE WHEN sqa.is_correct = 1 THEN 1 ELSE 0 END), 0) AS total_correct_count,
                COALESCE(SUM(CASE WHEN sqa.is_correct = 0 THEN 1 ELSE 0 END), 0) AS total_wrong_count,
                COUNT(DISTINCT sqa.question_id) AS distinct_questions_answered
            FROM student_question_answers sqa
            {$whereClause}
        ";
        $res = mysqli_query($con, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;

        $totalAnswers = (int)($row['total_answers_count'] ?? 0);
        $totalCorrect = (int)($row['total_correct_count'] ?? 0);
        $totalWrong = (int)($row['total_wrong_count'] ?? 0);
        $distinctQuestions = (int)($row['distinct_questions_answered'] ?? 0);
        $avgPercentage = $totalAnswers > 0 ? round(($totalCorrect / $totalAnswers) * 100, 2) : 0.00;

        return [
            'total_answers_count' => $totalAnswers,
            'total_correct_count' => $totalCorrect,
            'total_wrong_count' => $totalWrong,
            'distinct_questions_answered' => $distinctQuestions,
            'average_percentage' => $avgPercentage
        ];
    }
}

if (!function_exists('studentSubmitQuestionAnswer')) {
    function studentSubmitQuestionAnswer($con, $studentData) {
        ensure_student_question_answers_table($con);

        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $body = read_json_body();
        $questionId = (int)($_POST['question_id'] ?? $body['question_id'] ?? $_GET['question_id'] ?? 0);
        if ($questionId <= 0) {
            return ['status' => 'error', 'message' => 'question_id is required'];
        }

        $qRes = mysqli_query($con, "
            SELECT q.id, q.course_id, q.unit_id, q.question_type, q.score, q.is_active, q.created_by_type, q.created_by_id
            FROM questions q
            WHERE q.id = {$questionId} AND q.is_active = 1
            LIMIT 1
        ");
        $qRow = $qRes ? mysqli_fetch_assoc($qRes) : null;
        if (!$qRow) {
            return ['status' => 'error', 'message' => 'Question not found or inactive'];
        }

        if ($qRow['created_by_type'] === 'teacher' && (int)$qRow['created_by_id'] > 0) {
            $tCheck = mysqli_query($con, "SELECT is_active FROM teachers WHERE id = " . (int)$qRow['created_by_id'] . " LIMIT 1");
            if ($tCheck && $tRow = mysqli_fetch_assoc($tCheck)) {
                if ((int)$tRow['is_active'] !== 1) {
                    return ['status' => 'error', 'message' => 'Teacher account associated with this question is inactive'];
                }
            }
        }

        $cId = $qRow['course_id'] ? (int)$qRow['course_id'] : null;
        $uId = $qRow['unit_id'] ? (int)$qRow['unit_id'] : null;
        $qType = $qRow['question_type'];
        $maxScore = (float)$qRow['score'];

        $mcqOptionId = isset($body['mcq_option_id']) ? (int)$body['mcq_option_id'] : (isset($_POST['mcq_option_id']) ? (int)$_POST['mcq_option_id'] : null);
        $trueFalseAnswer = isset($body['true_false_answer']) ? (int)$body['true_false_answer'] : (isset($_POST['true_false_answer']) ? (int)$_POST['true_false_answer'] : null);
        $writtenText = trim($body['written_answer_text'] ?? $_POST['written_answer_text'] ?? $body['answer_text'] ?? $_POST['answer_text'] ?? '');

        $isCorrect = 0;
        $scoreAwarded = 0.00;
        $correctOptionId = null;
        $correctAnswer = null;

        if ($qType === 'mcq') {
            $correctOptRes = mysqli_query($con, "SELECT id FROM mcq_options WHERE question_id = {$questionId} AND is_correct = 1 LIMIT 1");
            if ($correctOptRes && $correctOptRow = mysqli_fetch_assoc($correctOptRes)) {
                $correctOptionId = (int)$correctOptRow['id'];
            }

            if ($mcqOptionId && $mcqOptionId > 0) {
                $isCorrect = ($correctOptionId !== null && $correctOptionId === $mcqOptionId) ? 1 : 0;
                $scoreAwarded = $isCorrect === 1 ? $maxScore : 0.00;
            }
        } elseif ($qType === 'true_false') {
            $tfRes = mysqli_query($con, "SELECT correct_answer FROM true_false_answers WHERE question_id = {$questionId} LIMIT 1");
            if ($tfRes && $tfRow = mysqli_fetch_assoc($tfRes)) {
                $correctAnswer = (int)$tfRow['correct_answer'];
            }

            if ($trueFalseAnswer !== null && $correctAnswer !== null) {
                $isCorrect = ($correctAnswer === (int)$trueFalseAnswer) ? 1 : 0;
                $scoreAwarded = $isCorrect === 1 ? $maxScore : 0.00;
            }
        } else {
            $isCorrect = 0;
            $scoreAwarded = 0.00;
        }

        $uIdVal = ($uId !== null && $uId > 0) ? (int)$uId : "NULL";
        $cIdVal = ($cId !== null && $cId > 0) ? (int)$cId : "NULL";
        $mcqOptVal = ($mcqOptionId !== null && $mcqOptionId > 0) ? (int)$mcqOptionId : "NULL";
        $tfAnsVal = ($trueFalseAnswer !== null) ? (int)$trueFalseAnswer : "NULL";
        $writtenEsc = "'" . mysqli_real_escape_string($con, $writtenText) . "'";

        $insSql = "
            INSERT INTO student_question_answers 
            (student_id, question_id, unit_id, course_id, mcq_option_id, true_false_answer, written_answer_text, is_correct, score_awarded, created_at)
            VALUES 
            ({$stId}, {$questionId}, {$uIdVal}, {$cIdVal}, {$mcqOptVal}, {$tfAnsVal}, {$writtenEsc}, {$isCorrect}, {$scoreAwarded}, NOW())
        ";
        $insRes = mysqli_query($con, $insSql);
        if (!$insRes) {
            return ['status' => 'error', 'message' => 'Failed to save question answer: ' . mysqli_error($con)];
        }
        $answerId = mysqli_insert_id($con);

        $qStats = get_student_question_statistics($con, $stId, $questionId);
        $overallStats = get_student_overall_statistics($con, $stId, $cId ? $cId : 0, $uId ? $uId : 0);

        return [
            'status' => 'success',
            'message' => 'Answer submitted successfully',
            'data' => [
                'submitted_answer' => [
                    'id' => $answerId,
                    'question_id' => $questionId,
                    'is_correct' => $isCorrect,
                    'score_awarded' => $scoreAwarded,
                    'max_score' => $maxScore,
                    'correct_option_id' => $correctOptionId,
                    'correct_answer' => $correctAnswer
                ],
                'question_statistics' => $qStats,
                'overall_statistics' => $overallStats
            ]
        ];
    }
}
