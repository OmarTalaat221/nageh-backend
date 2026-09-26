<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('get_json_input')) {
    function get_json_input(): array {
        $json = read_json_body();
        if (!empty($json)) {
            return $json;
        }
        if (!empty($_POST)) {
            return $_POST;
        }
        return [];
    }
}

if (!function_exists('json_error')) {
    function json_error(string $message, int $code = 400): array {
        return ['status' => 'error', 'message' => $message];
    }
}

if (!function_exists('handle_question_image_upload')) {
    function handle_question_image_upload(array $input, ?string $existingImage = null): string {
        // 1. Check $_FILES for multipart/form-data upload
        $fileKey = null;
        if (isset($_FILES['question_image']) && !empty($_FILES['question_image']['name']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
            $fileKey = 'question_image';
        } elseif (isset($_FILES['image']) && !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileKey = 'image';
        }

        if ($fileKey !== null) {
            if (function_exists('handle_file_upload')) {
                $uploaded = handle_file_upload($fileKey, 'questions');
                if ($uploaded) return $uploaded;
            } elseif (function_exists('upload_file')) {
                $uploaded = upload_file($_FILES[$fileKey], 'questions');
                if ($uploaded) return $uploaded;
            } elseif (function_exists('upload_image')) {
                $uploaded = upload_image($_FILES[$fileKey], 'questions');
                if ($uploaded) return $uploaded;
            } else {
                // Fallback manual upload if helper functions are named differently
                $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                $filename = 'question_' . uniqid('', true) . '.' . $ext;
                $targetDir = __DIR__ . '/../../../uploads/questions/';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetDir . $filename)) {
                    return 'uploads/questions/' . $filename;
                }
            }
        }

        // 2. Check if a Base64 string was passed in JSON or POST
        $rawImage = $input['question_image'] ?? $input['image'] ?? null;
        if (!empty($rawImage) && is_string($rawImage)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $rawImage, $type)) {
                if (function_exists('upload_image_base64')) {
                    $uploaded = upload_image_base64($rawImage, 'questions');
                    if ($uploaded) return $uploaded;
                }
                $data = substr($rawImage, strpos($rawImage, ',') + 1);
                $data = base64_decode($data);
                if ($data !== false) {
                    $ext = strtolower($type[1]);
                    if ($ext === 'jpeg') $ext = 'jpg';
                    $filename = 'question_' . uniqid('', true) . '.' . $ext;
                    $targetDir = __DIR__ . '/../../../uploads/questions/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0777, true);
                    }
                    if (file_put_contents($targetDir . $filename, $data)) {
                        return 'uploads/questions/' . $filename;
                    }
                }
            } else {
                // Already a URL or relative path
                return trim($rawImage);
            }
        }

        // 3. Explicit removal if empty string was passed
        if ($existingImage !== null && (array_key_exists('question_image', $input) || array_key_exists('image', $input))) {
            $val = $input['question_image'] ?? $input['image'] ?? null;
            if ($val === '' || $val === null) {
                return '';
            }
        }

        return $existingImage ?? '';
    }
}

if (!function_exists('save_written_model_answer')) {
    function save_written_model_answer($con, int $questionId, string $modelAnswerAr, string $modelAnswerEn, array $languages): void {
        mysqli_query($con, "DELETE FROM written_model_answers WHERE question_id = {$questionId}");

        if ($modelAnswerAr === '' && $modelAnswerEn === '') {
            return;
        }

        mysqli_query($con, "INSERT INTO written_model_answers (question_id, is_active) VALUES ({$questionId}, 1)");
        $waId = (int)mysqli_insert_id($con);

        foreach ($languages as $lang) {
            $lId = (int)$lang['id'];
            $lCode = strtolower($lang['code']);
            $val = ($lCode === 'en') ? ($modelAnswerEn ?: $modelAnswerAr) : ($modelAnswerAr ?: $modelAnswerEn);

            if ($val !== '') {
                $stmt = mysqli_prepare($con, "INSERT INTO written_model_answers_translations (written_model_answer_id, language_id, model_answer) VALUES (?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "iis", $waId, $lId, $val);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

if (!function_exists('adminGetQuestionDetails')) {
    function adminGetQuestionDetails($con, $questionId) {
        if (!$questionId) return null;
        $langInfo = get_language_info($con);

        $stmt = mysqli_prepare($con, "SELECT id, exam_id, course_id, unit_id, subject_id, question_type, question_image, order_num, score, is_active, created_by_type, created_by_id, created_at, updated_at FROM questions WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $questionId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['exam_id'] = (int)$row['exam_id'];
        $row['course_id'] = (int)$row['course_id'];
        $row['unit_id'] = $row['unit_id'] ? (int)$row['unit_id'] : null;
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
        $row['question_image'] = $row['question_image'] ?? null;
        $row['order_num'] = (int)($row['order_num'] ?? 0);
        $row['score'] = (float)$row['score'];
        $row['is_active'] = (int)$row['is_active'];

        $tStmt = mysqli_prepare($con, "SELECT qt.language_id, l.code AS language_code, qt.question_text FROM questions_translations qt JOIN languages l ON qt.language_id = l.id WHERE qt.question_id = ?");
        $translations = [];
        $textAr = null;
        $textEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $questionId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') $textAr = $tRow['question_text'];
                if ($tRow['language_code'] === 'en') $textEn = $tRow['question_text'];
            }
        }
        $row['translations'] = $translations;
        $row['question_text_ar'] = $textAr;
        $row['question_text_en'] = $textEn;
        $row['question_text'] = strtolower($langInfo['code'] ?? 'ar') === 'en' ? ($textEn ?: $textAr) : ($textAr ?: $textEn);

        if ($row['question_type'] === 'mcq') {
            $optStmt = mysqli_prepare($con, "SELECT id, question_id, option_key, option_image, is_correct, is_active FROM mcq_options WHERE question_id = ? ORDER BY id ASC");
            $options = [];
            if ($optStmt) {
                mysqli_stmt_bind_param($optStmt, "i", $questionId);
                mysqli_stmt_execute($optStmt);
                $optRes = mysqli_stmt_get_result($optStmt);
                while ($optRow = mysqli_fetch_assoc($optRes)) {
                    $optId = (int)$optRow['id'];
                    $optRow['id'] = $optId;
                    $optRow['question_id'] = (int)$optRow['question_id'];
                    $optRow['is_correct'] = (int)$optRow['is_correct'];
                    $optRow['is_active'] = (int)$optRow['is_active'];

                    $optTStmt = mysqli_prepare($con, "SELECT mot.language_id, l.code AS language_code, mot.option_text FROM mcq_options_translations mot JOIN languages l ON mot.language_id = l.id WHERE mot.mcq_option_id = ?");
                    $optTextAr = null;
                    $optTextEn = null;
                    if ($optTStmt) {
                        mysqli_stmt_bind_param($optTStmt, "i", $optId);
                        mysqli_stmt_execute($optTStmt);
                        $optTRes = mysqli_stmt_get_result($optTStmt);
                        while ($optTRow = mysqli_fetch_assoc($optTRes)) {
                            if ($optTRow['language_code'] === 'ar') $optTextAr = $optTRow['option_text'];
                            if ($optTRow['language_code'] === 'en') $optTextEn = $optTRow['option_text'];
                        }
                    }
                    $optRow['option_text_ar'] = $optTextAr;
                    $optRow['option_text_en'] = $optTextEn;
                    $optRow['option_text'] = strtolower($langInfo['code'] ?? 'ar') === 'en' ? ($optTextEn ?: $optTextAr) : ($optTextAr ?: $optTextEn);
                    $options[] = format_response_images($optRow);
                }
            }
            $row['options'] = $options;
        } elseif ($row['question_type'] === 'true_false') {
            $tfStmt = mysqli_prepare($con, "SELECT correct_answer FROM true_false_answers WHERE question_id = ? LIMIT 1");
            if ($tfStmt) {
                mysqli_stmt_bind_param($tfStmt, "i", $questionId);
                mysqli_stmt_execute($tfStmt);
                $tfRes = mysqli_stmt_get_result($tfStmt);
                if ($tfRow = mysqli_fetch_assoc($tfRes)) {
                    $row['correct_answer'] = (int)$tfRow['correct_answer'];
                } else {
                    $row['correct_answer'] = 1;
                }
            }
        } elseif ($row['question_type'] === 'written') {
            $waRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT id FROM written_model_answers WHERE question_id = {$questionId} LIMIT 1"));
            $modelAr = null;
            $modelEn = null;
            if ($waRow) {
                $waId = (int)$waRow['id'];
                $wtRes = mysqli_query($con, "SELECT wmat.language_id, l.code AS language_code, wmat.model_answer FROM written_model_answers_translations wmat JOIN languages l ON wmat.language_id = l.id WHERE wmat.written_model_answer_id = {$waId}");
                if ($wtRes) {
                    while ($wtRow = mysqli_fetch_assoc($wtRes)) {
                        if ($wtRow['language_code'] === 'ar') $modelAr = $wtRow['model_answer'];
                        if ($wtRow['language_code'] === 'en') $modelEn = $wtRow['model_answer'];
                    }
                }
            }
            $row['model_answer_ar'] = $modelAr;
            $row['model_answer_en'] = $modelEn;
            $row['model_answer'] = strtolower($langInfo['code'] ?? 'ar') === 'en' ? ($modelEn ?: $modelAr) : ($modelAr ?: $modelEn);
        }

        return format_response_images($row);
    }
}

if (!function_exists('adminListQuestions')) {
    function adminListQuestions($con) {
        $langInfo = get_language_info($con);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $examId = isset($_GET['exam_id']) && $_GET['exam_id'] !== '' ? (int)$_GET['exam_id'] : 0;
        $courseId = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : 0;
        $unitId = isset($_GET['unit_id']) && $_GET['unit_id'] !== '' ? (int)$_GET['unit_id'] : 0;
        $teacherId = isset($_GET['teacher_id']) && $_GET['teacher_id'] !== '' ? (int)$_GET['teacher_id'] : 0;

        $where = ["1=1"];
        if ($examId > 0) $where[] = "q.exam_id = {$examId}";
        if ($courseId > 0) $where[] = "q.course_id = {$courseId}";
        if ($unitId > 0) $where[] = "q.unit_id = {$unitId}";
        if ($teacherId > 0) $where[] = "((q.created_by_type = 'teacher' AND q.created_by_id = {$teacherId}) OR q.created_by_type = 'admin')";
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $where[] = "EXISTS (SELECT 1 FROM questions_translations qt WHERE qt.question_id = q.id AND qt.question_text LIKE '{$sp}')";
        }
        $whereSql = implode(" AND ", $where);

        $countRes = mysqli_query($con, "SELECT COUNT(*) AS total FROM questions q WHERE {$whereSql}");
        $totalRecords = $countRes ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;

        $sql = "SELECT q.id FROM questions q WHERE {$whereSql} ORDER BY q.order_num ASC, q.id ASC LIMIT {$limit} OFFSET {$offset}";
        $res = mysqli_query($con, $sql);

        $items = [];
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $q = adminGetQuestionDetails($con, (int)$r['id']);
                if ($q) {
                    if ($teacherId > 0) {
                        $q['can_edit'] = ($q['created_by_type'] ?? 'admin') !== 'admin';
                    }
                    $items[] = $q;
                }
            }
        }

        return build_admin_paginated_response($items, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('adminGetQuestion')) {
    function adminGetQuestion($con) {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) return json_error('Question ID is required', 400);

        $data = adminGetQuestionDetails($con, $id);
        if (!$data) return json_error('Question not found', 404);

        return [
            'status' => 'success',
            'data' => $data
        ];
    }
}

if (!function_exists('adminCreateQuestion')) {
    function adminCreateQuestion($con, $creatorData = null) {
        $input = get_json_input();

        $examId = isset($input['exam_id']) && $input['exam_id'] !== '' ? (int)$input['exam_id'] : 0;
        $courseId = isset($input['course_id']) && $input['course_id'] !== '' ? (int)$input['course_id'] : 0;
        $unitId = isset($input['unit_id']) && $input['unit_id'] !== '' ? (int)$input['unit_id'] : null;
        $subjectId = isset($input['subject_id']) && $input['subject_id'] !== '' ? (int)$input['subject_id'] : null;

        if ($examId > 0 && ($courseId <= 0 || $unitId === null || $subjectId === null)) {
            $examRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT course_id, unit_id, subject_id FROM course_exams WHERE id = {$examId} LIMIT 1"));
            if ($examRow) {
                if ($unitId === null && !empty($examRow['unit_id'])) {
                    $unitId = (int)$examRow['unit_id'];
                }
                if ($subjectId === null && !empty($examRow['subject_id'])) {
                    $subjectId = (int)$examRow['subject_id'];
                }
                if ($courseId <= 0) {
                    $courseId = (int)($examRow['course_id'] ?? 0);
                    if ($courseId <= 0 && $unitId) {
                        $uRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT course_id FROM units WHERE id = {$unitId} LIMIT 1"));
                        $courseId = (int)($uRow['course_id'] ?? 0);
                    }
                }
            }
        }

        if ($courseId <= 0) {
            return json_error('Unable to resolve the course for this question', 422);
        }

        $qType = trim($input['question_type'] ?? 'mcq');
        $questionImage = handle_question_image_upload($input);
        $score = isset($input['score']) ? (float)$input['score'] : 1.00;
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        $textAr = trim($input['question_text_ar'] ?? $input['question_text'] ?? '');
        $textEn = trim($input['question_text_en'] ?? $input['question_text'] ?? '');

        $createdByType = 'admin';
        $createdById = 1;
        if ($creatorData) {
            if (isset($creatorData['teacher_id']) || (isset($creatorData['role_id']) && $creatorData['role_id'] === 'teacher')) {
                $createdByType = 'teacher';
                $createdById = (int)($creatorData['id'] ?? $creatorData['teacher_id'] ?? 0);
            } else {
                $createdById = (int)($creatorData['id'] ?? 1);
            }
        }

        $nextOrderRes = mysqli_query($con, "SELECT COALESCE(MAX(order_num), 0) + 1 AS next_order FROM questions WHERE exam_id = {$examId}");
        $orderNum = $nextOrderRes ? (int)(mysqli_fetch_assoc($nextOrderRes)['next_order'] ?? 1) : 1;

        $stmt = mysqli_prepare($con, "INSERT INTO questions (exam_id, course_id, unit_id, subject_id, question_type, question_image, order_num, score, is_active, created_by_type, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return json_error('Database error: ' . mysqli_error($con), 500);
        }

        mysqli_stmt_bind_param($stmt, "iiiissidisi", $examId, $courseId, $unitId, $subjectId, $qType, $questionImage, $orderNum, $score, $isActive, $createdByType, $createdById);

        if (!mysqli_stmt_execute($stmt)) {
            return json_error('Failed to create question: ' . mysqli_stmt_error($stmt), 500);
        }

        $questionId = (int)mysqli_insert_id($con);
        mysqli_stmt_close($stmt);

        $languages = get_active_languages($con);
        foreach ($languages as $lang) {
            $lId = (int)$lang['id'];
            $lCode = strtolower($lang['code']);
            $textToSave = ($lCode === 'en') ? ($textEn ?: $textAr) : ($textAr ?: $textEn);

            if ($textToSave) {
                $tStmt = mysqli_prepare($con, "INSERT INTO questions_translations (question_id, language_id, question_text) VALUES (?, ?, ?)");
                if ($tStmt) {
                    mysqli_stmt_bind_param($tStmt, "iis", $questionId, $lId, $textToSave);
                    mysqli_stmt_execute($tStmt);
                    mysqli_stmt_close($tStmt);
                }
            }
        }

        $mcqOptionsInput = $input['mcq_options'] ?? $input['options'] ?? null;
        if (is_string($mcqOptionsInput)) {
            $decoded = json_decode($mcqOptionsInput, true);
            if (is_array($decoded)) {
                $mcqOptionsInput = $decoded;
            }
        }

        if ($qType === 'mcq' && !empty($mcqOptionsInput) && is_array($mcqOptionsInput)) {
            foreach ($mcqOptionsInput as $idx => $opt) {
                $optKey = trim($opt['option_key'] ?? chr(65 + $idx));
                $isCorr = !empty($opt['is_correct']) ? 1 : 0;
                $optImage = trim($opt['option_image'] ?? '');

                $optStmt = mysqli_prepare($con, "INSERT INTO mcq_options (question_id, option_key, option_image, is_correct, is_active) VALUES (?, ?, ?, ?, 1)");
                if ($optStmt) {
                    mysqli_stmt_bind_param($optStmt, "issi", $questionId, $optKey, $optImage, $isCorr);
                    mysqli_stmt_execute($optStmt);
                    $optId = (int)mysqli_insert_id($con);
                    mysqli_stmt_close($optStmt);

                    $optTextAr = trim($opt['option_text_ar'] ?? $opt['option_text'] ?? '');
                    $optTextEn = trim($opt['option_text_en'] ?? $opt['option_text'] ?? '');

                    foreach ($languages as $lang) {
                        $lId = (int)$lang['id'];
                        $lCode = strtolower($lang['code']);
                        $optVal = ($lCode === 'en') ? ($optTextEn ?: $optTextAr) : ($optTextAr ?: $optTextEn);
                        if ($optVal) {
                            $optTStmt = mysqli_prepare($con, "INSERT INTO mcq_options_translations (mcq_option_id, language_id, option_text) VALUES (?, ?, ?)");
                            if ($optTStmt) {
                                mysqli_stmt_bind_param($optTStmt, "iis", $optId, $lId, $optVal);
                                mysqli_stmt_execute($optTStmt);
                                mysqli_stmt_close($optTStmt);
                            }
                        }
                    }
                }
            }
        } elseif ($qType === 'true_false') {
            $corrAns = !empty($input['correct_answer']) ? 1 : 0;
            $tfStmt = mysqli_prepare($con, "INSERT INTO true_false_answers (question_id, correct_answer, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE correct_answer = VALUES(correct_answer)");
            if ($tfStmt) {
                mysqli_stmt_bind_param($tfStmt, "ii", $questionId, $corrAns);
                mysqli_stmt_execute($tfStmt);
                mysqli_stmt_close($tfStmt);
            }
        } elseif ($qType === 'written') {
            $modelAnswerAr = trim($input['model_answer_ar'] ?? $input['model_answer'] ?? '');
            $modelAnswerEn = trim($input['model_answer_en'] ?? $input['model_answer'] ?? '');
            save_written_model_answer($con, $questionId, $modelAnswerAr, $modelAnswerEn, $languages);
        }

        $result = adminGetQuestionDetails($con, $questionId);
        return [
            'status' => 'success',
            'message' => 'Question created successfully',
            'data' => $result
        ];
    }
}

if (!function_exists('adminUpdateQuestion')) {
    function adminUpdateQuestion($con) {
        $input = get_json_input();
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if (!$id) return json_error('Question ID is required', 400);

        $existing = adminGetQuestionDetails($con, $id);
        if (!$existing) return json_error('Question not found', 404);

        $examId = isset($input['exam_id']) ? (int)$input['exam_id'] : $existing['exam_id'];
        $courseId = isset($input['course_id']) ? (int)$input['course_id'] : $existing['course_id'];
        $unitId = isset($input['unit_id']) ? (int)$input['unit_id'] : $existing['unit_id'];
        $subjectId = isset($input['subject_id']) ? (int)$input['subject_id'] : $existing['subject_id'];
        $qType = trim($input['question_type'] ?? $existing['question_type']);
        $questionImage = handle_question_image_upload($input, $existing['question_image'] ?? '');
        $orderNum = isset($input['order_num']) ? (int)$input['order_num'] : $existing['order_num'];
        $score = isset($input['score']) ? (float)$input['score'] : $existing['score'];
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : $existing['is_active'];

        $stmt = mysqli_prepare($con, "UPDATE questions SET exam_id = ?, course_id = ?, unit_id = ?, subject_id = ?, question_type = ?, question_image = ?, order_num = ?, score = ?, is_active = ? WHERE id = ?");
        if (!$stmt) return json_error('Database error: ' . mysqli_error($con), 500);

        mysqli_stmt_bind_param($stmt, "iiiissidii", $examId, $courseId, $unitId, $subjectId, $qType, $questionImage, $orderNum, $score, $isActive, $id);

        if (!mysqli_stmt_execute($stmt)) {
            return json_error('Failed to update question: ' . mysqli_stmt_error($stmt), 500);
        }
        mysqli_stmt_close($stmt);

        $textAr = isset($input['question_text_ar']) ? trim($input['question_text_ar']) : (isset($input['question_text']) ? trim($input['question_text']) : null);
        $textEn = isset($input['question_text_en']) ? trim($input['question_text_en']) : (isset($input['question_text']) ? trim($input['question_text']) : null);

        $languages = get_active_languages($con);
        foreach ($languages as $lang) {
            $lId = (int)$lang['id'];
            $lCode = strtolower($lang['code']);
            $textToSave = ($lCode === 'en') ? ($textEn ?? $textAr) : ($textAr ?? $textEn);

            if ($textToSave !== null) {
                $tStmt = mysqli_prepare($con, "INSERT INTO questions_translations (question_id, language_id, question_text) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE question_text = VALUES(question_text)");
                if ($tStmt) {
                    mysqli_stmt_bind_param($tStmt, "iis", $id, $lId, $textToSave);
                    mysqli_stmt_execute($tStmt);
                    mysqli_stmt_close($tStmt);
                }
            }
        }

        if ($qType === 'mcq') {
            mysqli_query($con, "DELETE FROM true_false_answers WHERE question_id = {$id}");
            mysqli_query($con, "DELETE FROM written_model_answers WHERE question_id = {$id}");

            $mcqOptionsInput = $input['mcq_options'] ?? $input['options'] ?? null;
            if (is_string($mcqOptionsInput)) {
                $decoded = json_decode($mcqOptionsInput, true);
                if (is_array($decoded)) {
                    $mcqOptionsInput = $decoded;
                }
            }

            if (!empty($mcqOptionsInput) && is_array($mcqOptionsInput)) {
                mysqli_query($con, "DELETE FROM mcq_options WHERE question_id = {$id}");
                foreach ($mcqOptionsInput as $idx => $opt) {
                    $optKey = trim($opt['option_key'] ?? chr(65 + $idx));
                    $isCorr = !empty($opt['is_correct']) ? 1 : 0;
                    $optImage = trim($opt['option_image'] ?? '');

                    $optStmt = mysqli_prepare($con, "INSERT INTO mcq_options (question_id, option_key, option_image, is_correct, is_active) VALUES (?, ?, ?, ?, 1)");
                    if ($optStmt) {
                        mysqli_stmt_bind_param($optStmt, "issi", $id, $optKey, $optImage, $isCorr);
                        mysqli_stmt_execute($optStmt);
                        $optId = (int)mysqli_insert_id($con);
                        mysqli_stmt_close($optStmt);

                        $optTextAr = trim($opt['option_text_ar'] ?? $opt['option_text'] ?? '');
                        $optTextEn = trim($opt['option_text_en'] ?? $opt['option_text'] ?? '');

                        foreach ($languages as $lang) {
                            $lId = (int)$lang['id'];
                            $lCode = strtolower($lang['code']);
                            $optVal = ($lCode === 'en') ? ($optTextEn ?: $optTextAr) : ($optTextAr ?: $optTextEn);
                            if ($optVal) {
                                $optTStmt = mysqli_prepare($con, "INSERT INTO mcq_options_translations (mcq_option_id, language_id, option_text) VALUES (?, ?, ?)");
                                if ($optTStmt) {
                                    mysqli_stmt_bind_param($optTStmt, "iis", $optId, $lId, $optVal);
                                    mysqli_stmt_execute($optTStmt);
                                    mysqli_stmt_close($optTStmt);
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($qType === 'true_false') {
            mysqli_query($con, "DELETE FROM mcq_options WHERE question_id = {$id}");
            mysqli_query($con, "DELETE FROM written_model_answers WHERE question_id = {$id}");

            if (isset($input['correct_answer'])) {
                $corrAns = !empty($input['correct_answer']) ? 1 : 0;
                $tfStmt = mysqli_prepare($con, "INSERT INTO true_false_answers (question_id, correct_answer, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE correct_answer = VALUES(correct_answer)");
                if ($tfStmt) {
                    mysqli_stmt_bind_param($tfStmt, "ii", $id, $corrAns);
                    mysqli_stmt_execute($tfStmt);
                    mysqli_stmt_close($tfStmt);
                }
            }
        } elseif ($qType === 'written') {
            mysqli_query($con, "DELETE FROM mcq_options WHERE question_id = {$id}");
            mysqli_query($con, "DELETE FROM true_false_answers WHERE question_id = {$id}");

            if (isset($input['model_answer_ar']) || isset($input['model_answer_en']) || isset($input['model_answer'])) {
                $modelAnswerAr = trim($input['model_answer_ar'] ?? $input['model_answer'] ?? '');
                $modelAnswerEn = trim($input['model_answer_en'] ?? $input['model_answer'] ?? '');
                save_written_model_answer($con, $id, $modelAnswerAr, $modelAnswerEn, $languages);
            }
        }

        $result = adminGetQuestionDetails($con, $id);
        return [
            'status' => 'success',
            'message' => 'Question updated successfully',
            'data' => $result
        ];
    }
}

if (!function_exists('adminDeleteQuestion')) {
    function adminDeleteQuestion($con) {
        $input = get_json_input();
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if (!$id) return json_error('Question ID is required', 400);

        $stmt = mysqli_prepare($con, "DELETE FROM questions WHERE id = ?");
        if (!$stmt) return json_error('Database error: ' . mysqli_error($con), 500);

        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return json_error('Failed to delete question: ' . mysqli_stmt_error($stmt), 500);
        }
        mysqli_stmt_close($stmt);

        return [
            'status' => 'success',
            'message' => 'Question deleted successfully'
        ];
    }
}

if (!function_exists('adminReorderQuestions')) {
    function adminReorderQuestions($con, $creatorData = null) {
        $input = get_json_input();
        $examId = isset($input['exam_id']) ? (int)$input['exam_id'] : 0;
        $orderedIds = $input['ordered_ids'] ?? $input['order'] ?? [];

        if ($examId <= 0) return json_error('exam_id is required', 400);
        if (!is_array($orderedIds) || empty($orderedIds)) return json_error('ordered_ids is required', 400);

        $position = 1;
        foreach ($orderedIds as $qId) {
            $qId = (int)$qId;
            if ($qId <= 0) continue;
            mysqli_query($con, "UPDATE questions SET order_num = {$position} WHERE id = {$qId} AND exam_id = {$examId}");
            $position++;
        }

        return [
            'status' => 'success',
            'message' => 'Questions reordered successfully'
        ];
    }
}