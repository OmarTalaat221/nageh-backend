<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../student/homework/manage.php'; // get_student_subscribed_subject_ids / course_ids
require_once __DIR__ . '/../../../helpers/auto_reports.php';

if (!function_exists('get_exam_summary')) {
    function get_exam_summary($con, int $examId) {
        $stmt = mysqli_prepare($con, "SELECT ce.id, ce.subject_id, ce.course_id, ce.unit_id, ce.duration_minutes, ce.total_marks, ce.pass_marks, ce.is_active, cet.title, cet.description FROM course_exams ce LEFT JOIN course_exams_translations cet ON ce.id = cet.exam_id AND cet.language_id = 1 WHERE ce.id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $examId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
        $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
        $row['unit_id'] = $row['unit_id'] ? (int)$row['unit_id'] : null;
        $row['duration_minutes'] = (int)$row['duration_minutes'];
        // 0 minutes means the exam is open-ended - no countdown, and it is never auto-submitted
        // when time runs out, because there is no time. The flag spares every client from having
        // to know that 0 is special.
        $row['has_time_limit'] = $row['duration_minutes'] > 0 ? 1 : 0;
        // An exam attached to no course, no unit and no subject stands on its own and is open to
        // every student, whether or not they are subscribed to anything.
        $row['is_public'] = (!$row['course_id'] && !$row['unit_id'] && !$row['subject_id']) ? 1 : 0;
        $row['is_active'] = (int)$row['is_active'];
        // Same "show exam to students" toggle as the admin/teacher side (adminGetExamDetails) -
        // always 1 here in practice, since this function is only ever reached for exams that
        // already passed the `is_active = 1` filter in studentListAvailableExams().
        $row['show_to_students'] = $row['is_active'];
        $row['questions_count'] = (int)(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM questions WHERE exam_id = {$examId}"))['total'] ?? 0);

        // Embed lightweight {id, name} relations so a client can label/group exams (e.g. by unit)
        // without a second lookup per row - a unit-scoped exam always carries its parent course_id
        // too (set by the admin/teacher UI when the exam is created), so filtering /student/exams
        // by course_id already returns both whole-course exams AND every unit exam under it mixed
        // together; `unit` is what lets the client tell them apart and group them back by unit.
        $row['unit'] = null;
        if (!empty($row['unit_id'])) {
            $uRow = mysqli_fetch_assoc(mysqli_query($con, "
                SELECT u.id, COALESCE(ut.name, u.code) AS name
                FROM units u
                LEFT JOIN units_translations ut ON u.id = ut.unit_id AND ut.language_id = 1
                WHERE u.id = {$row['unit_id']} LIMIT 1
            "));
            $row['unit'] = $uRow ? ['id' => (int)$uRow['id'], 'name' => $uRow['name']] : null;
        }

        $row['course'] = !empty($row['course_id']) ? get_course_details($con, $row['course_id']) : null;
        $row['subject'] = !empty($row['subject_id']) ? get_subject_details($con, $row['subject_id']) : null;

        return $row;
    }
}

if (!function_exists('get_exam_attempt_details')) {
    function get_exam_attempt_details($con, int $attemptId, bool $revealAnswers = false) {
        $stmt = mysqli_prepare($con, "SELECT id, exam_id, student_id, status, total_score, max_score, started_at, submitted_at, graded_at, graded_by_type, graded_by_id FROM exam_attempts WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $attemptId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['exam_id'] = (int)$row['exam_id'];
        $row['student_id'] = (int)$row['student_id'];
        $row['total_score'] = $row['total_score'] !== null ? (float)$row['total_score'] : null;
        $row['max_score'] = (float)$row['max_score'];
        $row['exam'] = get_exam_summary($con, $row['exam_id']);

        $sStmt = mysqli_prepare($con, "SELECT id, name, email FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($sStmt, "i", $row['student_id']);
        mysqli_stmt_execute($sStmt);
        $sRow = mysqli_fetch_assoc(mysqli_stmt_get_result($sStmt));
        if ($sRow) { $sRow['id'] = (int)$sRow['id']; $row['student'] = $sRow; }

        $aStmt = mysqli_prepare($con, "
            SELECT eaa.id, eaa.question_id, eaa.mcq_option_id, eaa.true_false_answer, eaa.written_answer_text,
                   eaa.is_correct, eaa.score_awarded, eaa.teacher_note, q.question_type, q.score AS max_question_score,
                   qt.question_text
            FROM exam_attempt_answers eaa
            JOIN questions q ON eaa.question_id = q.id
            LEFT JOIN questions_translations qt ON q.id = qt.question_id AND qt.language_id = 1
            WHERE eaa.attempt_id = ?
        ");
        mysqli_stmt_bind_param($aStmt, "i", $attemptId);
        mysqli_stmt_execute($aStmt);
        $aRes = mysqli_stmt_get_result($aStmt);

        $answers = [];
        while ($aRow = mysqli_fetch_assoc($aRes)) {
            $aRow['id'] = (int)$aRow['id'];
            $aRow['question_id'] = (int)$aRow['question_id'];
            $aRow['mcq_option_id'] = $aRow['mcq_option_id'] ? (int)$aRow['mcq_option_id'] : null;
            $aRow['true_false_answer'] = $aRow['true_false_answer'] !== null ? (int)$aRow['true_false_answer'] : null;
            $aRow['is_correct'] = $aRow['is_correct'] !== null ? (int)$aRow['is_correct'] : null;
            $aRow['score_awarded'] = $aRow['score_awarded'] !== null ? (float)$aRow['score_awarded'] : null;
            $aRow['max_question_score'] = (float)$aRow['max_question_score'];

            if ($revealAnswers && $aRow['question_type'] === 'mcq') {
                $optRes = mysqli_query($con, "SELECT mo.id, mo.is_correct, mot.option_text FROM mcq_options mo LEFT JOIN mcq_options_translations mot ON mo.id = mot.mcq_option_id AND mot.language_id = 1 WHERE mo.question_id = {$aRow['question_id']}");
                $options = [];
                while ($o = mysqli_fetch_assoc($optRes)) {
                    $options[] = ['id' => (int)$o['id'], 'is_correct' => (int)$o['is_correct'], 'option_text' => $o['option_text']];
                }
                $aRow['options'] = $options;
            }
            if ($revealAnswers && $aRow['question_type'] === 'true_false') {
                $tf = mysqli_fetch_assoc(mysqli_query($con, "SELECT correct_answer FROM true_false_answers WHERE question_id = {$aRow['question_id']} LIMIT 1"));
                $aRow['correct_answer'] = $tf ? (int)$tf['correct_answer'] : null;
            }

            $answers[] = $aRow;
        }
        $row['answers'] = $answers;

        return $row;
    }
}

if (!function_exists('studentListAvailableExams')) {
    function studentListAvailableExams($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $subjectIds = get_student_subscribed_subject_ids($con, $studentId);
        $courseIds = get_student_subscribed_course_ids($con, $studentId);

        $unitId = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
        $reqCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $reqSubjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        // Every exam always carries a course_id (set at creation time, whether it's a whole-course
        // exam or a unit exam - a unit exam just also has unit_id set on top of it). So course_id
        // alone must default to WHOLE-COURSE exams only (unit_id IS NULL) - otherwise every request
        // for "this course's exams" would silently also return every one of its unit exams too.
        // `include_unit_exams` (alias `with_unit_exams`) then has 3 states, checked as a string
        // rather than a strict boolean so "only" is a real third option, not just another truthy value:
        //   unset / "0" / "false" (default) -> whole-course exams only (unit_id IS NULL)
        //   "1" / "true"                    -> combined: whole-course exam(s) + every unit exam
        //   "only"                          -> unit exams only, excluding the whole-course exam(s)
        $unitExamsParam = strtolower(trim((string)($_GET['include_unit_exams'] ?? $_GET['with_unit_exams'] ?? '0')));
        $onlyUnitExams = in_array($unitExamsParam, ['only', 'unit_only', 'units_only'], true);
        $includeUnitExams = $onlyUnitExams || in_array($unitExamsParam, ['1', 'true', 'yes'], true);

        // Sorting: by order_num by default - the sequence the teacher/admin actually intended (and
        // already numbers into the exam titles themselves, e.g. "... - 07"), not when each exam
        // happened to be created. `sort` (aliases: `sort_by`, `order`) still accepts "newest"/
        // "oldest" to explicitly fall back to created_at-based sorting when a caller wants that.
        $sortByProvided = isset($_GET['sort']) || isset($_GET['sort_by']) || isset($_GET['order']);
        $sortParam = strtolower(trim((string)($_GET['sort'] ?? $_GET['sort_by'] ?? $_GET['order'] ?? '')));
        $sortDir = in_array($sortParam, ['oldest', 'asc', 'created_at_asc', 'id_asc'], true) ? 'ASC' : 'DESC';
        $orderBySql = $sortByProvided
            ? "created_at {$sortDir}, id {$sortDir}"
            : "order_num ASC, id ASC";

        // A standalone exam - no course, no unit, no subject - belongs to nobody in particular and
        // is offered to every student. It is listed unconditionally, which is also why the old
        // early return for a student with no subscriptions at all is gone: that student now still
        // has the public exams to sit.
        $whereParts = ["(course_id IS NULL AND unit_id IS NULL AND subject_id IS NULL)"];
        $params = [];
        $types = "";
        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereParts[] = "subject_id IN ({$placeholders})";
            foreach ($subjectIds as $sId) { $params[] = $sId; $types .= "i"; }
        }
        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $whereParts[] = "course_id IN ({$placeholders})";
            foreach ($courseIds as $cId) { $params[] = $cId; $types .= "i"; }
        }

        $extraConditions = [];
        if ($unitId > 0) {
            $extraConditions[] = "unit_id = ?";
            $params[] = $unitId;
            $types .= "i";
        }
        if ($reqCourseId > 0) {
            $extraConditions[] = "course_id = ?";
            $params[] = $reqCourseId;
            $types .= "i";

            // unit_id in the request already scopes to one specific unit below, so none of this
            // applies then; otherwise pick one of the 3 states described above.
            if ($unitId <= 0) {
                if ($onlyUnitExams) {
                    $extraConditions[] = "unit_id IS NOT NULL";
                } elseif (!$includeUnitExams) {
                    $extraConditions[] = "unit_id IS NULL";
                }
                // else ($includeUnitExams && !$onlyUnitExams): combined - no extra restriction.
            }
        }
        if ($reqSubjectId > 0) {
            $extraConditions[] = "subject_id = ?";
            $params[] = $reqSubjectId;
            $types .= "i";
        }

        // `public=1` narrows the list to the standalone exams alone, so a client can show them as
        // their own section instead of mixed in with the ones that came with a subscription.
        $publicParam = strtolower(trim((string)($_GET['public'] ?? $_GET['only_public'] ?? '')));
        if (in_array($publicParam, ['1', 'true', 'yes', 'only'], true)) {
            $extraConditions[] = "course_id IS NULL AND unit_id IS NULL AND subject_id IS NULL";
        }

        $extraSql = !empty($extraConditions) ? " AND " . implode(" AND ", $extraConditions) : "";

        $sql = "SELECT id FROM course_exams WHERE is_active = 1 AND (" . implode(" OR ", $whereParts) . ")" . $extraSql . " ORDER BY {$orderBySql}";
        $stmt = mysqli_prepare($con, $sql);
        // A student with no subscriptions leaves nothing to bind - only the public-exam condition
        // is left, and it carries no placeholder. bind_param with an empty type string is an error.
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $examId = (int)$row['id'];
            $exam = get_exam_summary($con, $examId);

            $attStmt = mysqli_prepare($con, "SELECT id, status, total_score, max_score FROM exam_attempts WHERE exam_id = ? AND student_id = ? LIMIT 1");
            mysqli_stmt_bind_param($attStmt, "ii", $examId, $studentId);
            mysqli_stmt_execute($attStmt);
            $att = mysqli_fetch_assoc(mysqli_stmt_get_result($attStmt));
            $exam['my_attempt'] = $att ? [
                'id' => (int)$att['id'],
                'status' => $att['status'],
                'total_score' => $att['total_score'] !== null ? (float)$att['total_score'] : null,
                'max_score' => (float)$att['max_score'],
            ] : null;

            $items[] = $exam;
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('studentStartExamAttempt')) {
    function studentStartExamAttempt($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $examId = (int)($body['exam_id'] ?? $_GET['exam_id'] ?? $_GET['id'] ?? 0);
        if ($examId <= 0) return ['status' => 'error', 'message' => 'exam_id is required'];

        $exam = get_exam_summary($con, $examId);
        if (!$exam || !$exam['is_active']) return ['status' => 'error', 'message' => 'Exam not found or inactive'];

        $subjectIds = get_student_subscribed_subject_ids($con, $studentId);
        $courseIds = get_student_subscribed_course_ids($con, $studentId);
        // A standalone exam is open to every student; there is no subscription to check it against.
        $allowed = (int)($exam['is_public'] ?? 0) === 1
            || ($exam['course_id'] && in_array((int)$exam['course_id'], $courseIds))
            || ($exam['subject_id'] && in_array((int)$exam['subject_id'], $subjectIds));
        if (!$allowed) {
            return ['status' => 'error', 'message' => 'You are not subscribed to this exam\'s subject/course'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, status FROM exam_attempts WHERE exam_id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $examId, $studentId);
        mysqli_stmt_execute($checkStmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));

        if (!$existing) {
            $maxScoreRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COALESCE(SUM(score),0) AS total FROM questions WHERE exam_id = {$examId} AND is_active = 1"));
            $maxScore = (float)($maxScoreRow['total'] ?? 0);
            $iStmt = mysqli_prepare($con, "INSERT INTO exam_attempts (exam_id, student_id, status, max_score, started_at) VALUES (?, ?, 'in_progress', ?, NOW())");
            mysqli_stmt_bind_param($iStmt, "iid", $examId, $studentId, $maxScore);
            mysqli_stmt_execute($iStmt);
            $attemptId = mysqli_insert_id($con);
        } else {
            $attemptId = (int)$existing['id'];
            if ($existing['status'] !== 'in_progress') {
                $maxScoreRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COALESCE(SUM(score),0) AS total FROM questions WHERE exam_id = {$examId} AND is_active = 1"));
                $maxScore = (float)($maxScoreRow['total'] ?? 0);
                mysqli_query($con, "DELETE FROM exam_attempt_answers WHERE attempt_id = {$attemptId}");
                $uReset = mysqli_prepare($con, "UPDATE exam_attempts SET status = 'in_progress', total_score = NULL, max_score = ?, started_at = NOW(), submitted_at = NULL, graded_at = NULL, graded_by_type = NULL, graded_by_id = NULL WHERE id = ?");
                mysqli_stmt_bind_param($uReset, "di", $maxScore, $attemptId);
                mysqli_stmt_execute($uReset);
            }
        }

        $qStmt = mysqli_prepare($con, "SELECT q.id, q.question_type, q.score, qt.question_text FROM questions q LEFT JOIN questions_translations qt ON q.id = qt.question_id AND qt.language_id = 1 WHERE q.exam_id = ? AND q.is_active = 1 ORDER BY q.id ASC");
        mysqli_stmt_bind_param($qStmt, "i", $examId);
        mysqli_stmt_execute($qStmt);
        $qRes = mysqli_stmt_get_result($qStmt);

        $questions = [];
        while ($q = mysqli_fetch_assoc($qRes)) {
            $q['id'] = (int)$q['id'];
            $q['score'] = (float)$q['score'];
            if ($q['question_type'] === 'mcq') {
                $optRes = mysqli_query($con, "SELECT mo.id, mot.option_text FROM mcq_options mo LEFT JOIN mcq_options_translations mot ON mo.id = mot.mcq_option_id AND mot.language_id = 1 WHERE mo.question_id = {$q['id']} AND mo.is_active = 1 ORDER BY mo.id ASC");
                $options = [];
                while ($o = mysqli_fetch_assoc($optRes)) {
                    $options[] = ['id' => (int)$o['id'], 'option_text' => $o['option_text']];
                }
                $q['options'] = $options;
            }
            $questions[] = $q;
        }

        return [
            'status' => 'success',
            'message' => 'Exam attempt started',
            'data' => ['attempt_id' => $attemptId, 'exam' => $exam, 'questions' => $questions]
        ];
    }
}

if (!function_exists('studentSubmitExamAttempt')) {
    function studentSubmitExamAttempt($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $attemptId = (int)($body['attempt_id'] ?? 0);
        $answers = $body['answers'] ?? [];

        if ($attemptId <= 0 || !is_array($answers) || empty($answers)) {
            return ['status' => 'error', 'message' => 'attempt_id and a non-empty answers array are required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, status FROM exam_attempts WHERE id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $attemptId, $studentId);
        mysqli_stmt_execute($checkStmt);
        $attempt = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$attempt) return ['status' => 'error', 'message' => 'Attempt not found'];
        if ($attempt['status'] !== 'in_progress') return ['status' => 'error', 'message' => 'This attempt was already submitted'];

        $hasWritten = false;
        $totalScore = 0.0;

        foreach ($answers as $ans) {
            $questionId = (int)($ans['question_id'] ?? 0);
            if ($questionId <= 0) continue;

            $qRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT question_type, score FROM questions WHERE id = {$questionId} LIMIT 1"));
            if (!$qRow) continue;

            $mcqOptionId = null;
            $trueFalseAnswer = null;
            $writtenText = null;
            $isCorrect = null;
            $scoreAwarded = null;

            if ($qRow['question_type'] === 'mcq') {
                $mcqOptionId = isset($ans['mcq_option_id']) ? (int)$ans['mcq_option_id'] : null;
                if ($mcqOptionId) {
                    $optRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT is_correct FROM mcq_options WHERE id = {$mcqOptionId} AND question_id = {$questionId} LIMIT 1"));
                    $isCorrect = $optRow ? (int)$optRow['is_correct'] : 0;
                    $scoreAwarded = $isCorrect ? (float)$qRow['score'] : 0.0;
                }
            } elseif ($qRow['question_type'] === 'true_false') {
                $trueFalseAnswer = isset($ans['true_false_answer']) ? (int)$ans['true_false_answer'] : null;
                $correctRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT correct_answer FROM true_false_answers WHERE question_id = {$questionId} LIMIT 1"));
                if ($correctRow !== null && $trueFalseAnswer !== null) {
                    $isCorrect = ((int)$correctRow['correct_answer'] === $trueFalseAnswer) ? 1 : 0;
                    $scoreAwarded = $isCorrect ? (float)$qRow['score'] : 0.0;
                }
            } else { // written
                $writtenText = trim($ans['written_answer_text'] ?? $ans['answer_text'] ?? '');
                $hasWritten = true; // needs manual grading regardless
            }

            if ($scoreAwarded !== null) {
                $totalScore += $scoreAwarded;
            }

            $stmt = mysqli_prepare($con, "
                INSERT INTO exam_attempt_answers (attempt_id, question_id, mcq_option_id, true_false_answer, written_answer_text, is_correct, score_awarded)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE mcq_option_id = VALUES(mcq_option_id), true_false_answer = VALUES(true_false_answer),
                    written_answer_text = VALUES(written_answer_text), is_correct = VALUES(is_correct), score_awarded = VALUES(score_awarded)
            ");
            mysqli_stmt_bind_param($stmt, "iiiisid", $attemptId, $questionId, $mcqOptionId, $trueFalseAnswer, $writtenText, $isCorrect, $scoreAwarded);
            mysqli_stmt_execute($stmt);
        }

        $newStatus = $hasWritten ? 'submitted' : 'graded';
        $uStmt = mysqli_prepare($con, "UPDATE exam_attempts SET status = ?, total_score = ?, submitted_at = NOW()" . ($hasWritten ? "" : ", graded_at = NOW()") . " WHERE id = ?");
        mysqli_stmt_bind_param($uStmt, "sdi", $newStatus, $totalScore, $attemptId);
        mysqli_stmt_execute($uStmt);

        if ($newStatus === 'graded') {
            auto_generate_exam_result_report($con, $attemptId);
        }

        return ['status' => 'success', 'message' => 'Exam submitted successfully', 'data' => get_exam_attempt_details($con, $attemptId, true)];
    }
}

if (!function_exists('studentListExamResults')) {
    function studentListExamResults($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($studentId <= 0) {
            $studentId = (int)($_GET['student_id'] ?? 0);
        }

        if ($studentId <= 0) {
            return ['status' => 'success', 'data' => []];
        }

        $stmt = mysqli_prepare($con, "SELECT id FROM exam_attempts WHERE student_id = ? AND status != 'in_progress' ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_exam_attempt_details($con, (int)$row['id'], true);
        }

        return ['status' => 'success', 'data' => $items];
    }
}

if (!function_exists('studentGetExamResultDetail')) {
    function studentGetExamResultDetail($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $id = (int)($_GET['id'] ?? $_GET['attempt_id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'attempt id is required'];

        $detail = get_exam_attempt_details($con, $id, true);
        if (!$detail || $detail['student_id'] !== $studentId) {
            return ['status' => 'error', 'message' => 'Attempt not found'];
        }

        return ['status' => 'success', 'data' => $detail];
    }
}
