<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../content/helpers.php';
require_once __DIR__ . '/../subscriptions/manage.php';
require_once __DIR__ . '/../../../admin/content/units.php';
require_once __DIR__ . '/questions.php';

if (!function_exists('studentTableExists')) {
    function studentTableExists($con, $tableName) {
        static $cache = [];
        $cacheKey = spl_object_id($con) . ':' . $tableName;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }
        $safeTableName = mysqli_real_escape_string($con, $tableName);
        $result = mysqli_query($con, "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$safeTableName}' LIMIT 1");
        $cache[$cacheKey] = $result && mysqli_num_rows($result) > 0;
        return $cache[$cacheKey];
    }
}

if (!function_exists('studentListUnits')) {
    function studentListUnits($con) {
        $_GET['is_active'] = 1;
        return adminListUnits($con);
    }
}

if (!function_exists('studentGetUnit')) {
    function studentGetUnit($con) {
        return adminGetUnit($con);
    }
}

if (!function_exists('studentGetCourseUnitsOnly')) {
    function studentGetCourseUnitsOnly($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $body = read_json_body();
        $courseId = (int)($_GET['course_id'] ?? $_GET['id'] ?? $body['course_id'] ?? $body['id'] ?? 0);
        if ($courseId <= 0) {
            return ['status' => 'error', 'message' => 'course_id is required'];
        }

        $langInfo = get_student_request_lang_info($con);
        $langId = (int)($langInfo['id'] ?? 2);

        $hasVideoProgress = studentTableExists($con, 'student_video_progress');

        $uSql = "
            SELECT u.id, u.course_id, u.subject_id, u.code, u.is_active, u.created_by_type, u.created_by_id, u.created_at,
                   COALESCE(ut.name, u.code) AS name, COALESCE(ut.description, '') AS description
            FROM units u
            LEFT JOIN units_translations ut ON u.id = ut.unit_id AND ut.language_id = {$langId}
            WHERE u.course_id = {$courseId} AND u.is_active = 1
            ORDER BY u.order_num ASC, u.id ASC
        ";
        $uRes = mysqli_query($con, $uSql);

        $units = [];
        $overallTotalUnits = 0;
        $overallTotalVideos = 0;
        $overallTotalCompletedVideos = 0;
        $overallTotalBooks = 0;
        $overallTotalQuestions = 0;
        $overallTotalAnsweredQuestions = 0;
        $overallTotalAnswersCount = 0;
        $overallTotalCorrectCount = 0;
        $overallTotalWrongCount = 0;

        if ($uRes) {
            while ($uRow = mysqli_fetch_assoc($uRes)) {
                $uId = (int)$uRow['id'];

                if ($uRow['created_by_type'] === 'teacher' && (int)$uRow['created_by_id'] > 0) {
                    $tRes = mysqli_query($con, "SELECT is_active FROM teachers WHERE id = " . (int)$uRow['created_by_id'] . " LIMIT 1");
                    if ($tRes && $tRow = mysqli_fetch_assoc($tRes)) {
                        if ((int)$tRow['is_active'] !== 1) {
                            continue;
                        }
                    }
                }
                $utCheck = mysqli_query($con, "
                    SELECT ut.id FROM unit_teachers ut
                    JOIN teachers t ON ut.teacher_id = t.id
                    WHERE ut.unit_id = {$uId} AND ut.is_active = 1 AND t.is_active = 1
                    LIMIT 1
                ");
                $hasUtRecords = mysqli_num_rows(mysqli_query($con, "SELECT id FROM unit_teachers WHERE unit_id = {$uId}")) > 0;
                if ($hasUtRecords && mysqli_num_rows($utCheck) === 0) {
                    continue;
                }

                $overallTotalUnits++;

                $vCountRes = mysqli_query($con, "SELECT COUNT(id) AS total FROM videos WHERE unit_id = {$uId} AND is_active = 1");
                $unitVideosCount = (int)(mysqli_fetch_assoc($vCountRes)['total'] ?? 0);
                if ($unitVideosCount === 0 && studentTableExists($con, 'course_videos')) {
                    $cvCountRes = mysqli_query($con, "SELECT COUNT(id) AS total FROM course_videos WHERE unit_id = {$uId} AND is_active = 1");
                    $unitVideosCount = (int)(mysqli_fetch_assoc($cvCountRes)['total'] ?? 0);
                }
                $overallTotalVideos += $unitVideosCount;

                $unitCompletedVideosCount = 0;
                if ($stId > 0 && $hasVideoProgress && $unitVideosCount > 0) {
                    $cvRes = mysqli_query($con, "
                        SELECT COUNT(DISTINCT svp.video_id) AS completed
                        FROM student_video_progress svp
                        JOIN videos v ON svp.video_id = v.id
                        WHERE svp.student_id = {$stId} AND v.unit_id = {$uId} AND v.is_active = 1 AND svp.is_completed = 1
                    ");
                    if ($cvRes && $cvRow = mysqli_fetch_assoc($cvRes)) {
                        $unitCompletedVideosCount = (int)($cvRow['completed'] ?? 0);
                    }
                }
                $overallTotalCompletedVideos += $unitCompletedVideosCount;

                $bCountRes = mysqli_query($con, "SELECT COUNT(id) AS total FROM course_books WHERE unit_id = {$uId} AND is_active = 1");
                $unitBooksCount = (int)(mysqli_fetch_assoc($bCountRes)['total'] ?? 0);
                $overallTotalBooks += $unitBooksCount;

                $qCountRes = mysqli_query($con, "SELECT COUNT(id) AS total FROM questions WHERE unit_id = {$uId} AND is_active = 1");
                $unitQuestionsCount = (int)(mysqli_fetch_assoc($qCountRes)['total'] ?? 0);
                $overallTotalQuestions += $unitQuestionsCount;

                $unitAnsweredQuestionsCount = 0;
                $unitTotalAnswers = 0;
                $unitCorrectAnswers = 0;
                $unitWrongAnswers = 0;

                if ($stId > 0 && function_exists('ensure_student_question_answers_table')) {
                    ensure_student_question_answers_table($con);
                    $sqRes = mysqli_query($con, "
                        SELECT 
                            COUNT(*) AS total_answers,
                            COUNT(DISTINCT sqa.question_id) AS distinct_questions,
                            COALESCE(SUM(CASE WHEN sqa.is_correct = 1 THEN 1 ELSE 0 END), 0) AS correct_answers,
                            COALESCE(SUM(CASE WHEN sqa.is_correct = 0 THEN 1 ELSE 0 END), 0) AS wrong_answers
                        FROM student_question_answers sqa
                        JOIN questions q ON sqa.question_id = q.id
                        WHERE sqa.student_id = {$stId} AND q.unit_id = {$uId} AND q.is_active = 1
                    ");
                    if ($sqRes && $sqRow = mysqli_fetch_assoc($sqRes)) {
                        $unitTotalAnswers = (int)($sqRow['total_answers'] ?? 0);
                        $unitAnsweredQuestionsCount = (int)($sqRow['distinct_questions'] ?? 0);
                        $unitCorrectAnswers = (int)($sqRow['correct_answers'] ?? 0);
                        $unitWrongAnswers = (int)($sqRow['wrong_answers'] ?? 0);
                    }
                }

                $overallTotalAnsweredQuestions += $unitAnsweredQuestionsCount;
                $overallTotalAnswersCount += $unitTotalAnswers;
                $overallTotalCorrectCount += $unitCorrectAnswers;
                $overallTotalWrongCount += $unitWrongAnswers;

                $unitProgressPct = $unitVideosCount > 0 ? round(($unitCompletedVideosCount / $unitVideosCount) * 100, 2) : 0.00;
                $unitQuestionAvgPct = $unitTotalAnswers > 0 ? round(($unitCorrectAnswers / $unitTotalAnswers) * 100, 2) : 0.00;

                $units[] = [
                    'id' => $uId,
                    'course_id' => (int)$uRow['course_id'],
                    'code' => $uRow['code'],
                    'name' => $uRow['name'],
                    'description' => $uRow['description'],
                    'progress' => [
                        'videos_count' => $unitVideosCount,
                        'completed_videos_count' => $unitCompletedVideosCount,
                        'progress_percentage' => $unitProgressPct
                    ],
                    'statistics' => [
                        'videos_count' => $unitVideosCount,
                        'completed_videos_count' => $unitCompletedVideosCount,
                        'books_count' => $unitBooksCount,
                        'questions_count' => $unitQuestionsCount,
                        'answered_questions_count' => $unitAnsweredQuestionsCount,
                        'unit_total_answers' => $unitTotalAnswers,
                        'unit_correct_answers' => $unitCorrectAnswers,
                        'unit_wrong_answers' => $unitWrongAnswers,
                        'unit_average_percentage' => $unitQuestionAvgPct
                    ]
                ];
            }
        }

        $overallAvgPct = $overallTotalAnswersCount > 0 ? round(($overallTotalCorrectCount / $overallTotalAnswersCount) * 100, 2) : 0.00;

        return [
            'status' => 'success',
            'data' => [
                'language' => $langInfo['code'],
                'units' => $units,
                'statistics' => [
                    'total_units_count' => $overallTotalUnits,
                    'total_videos_count' => $overallTotalVideos,
                    'total_completed_videos_count' => $overallTotalCompletedVideos,
                    'total_books_count' => $overallTotalBooks,
                    'total_questions_count' => $overallTotalQuestions,
                    'total_answered_questions_count' => $overallTotalAnsweredQuestions,
                    'total_answers_count' => $overallTotalAnswersCount,
                    'total_correct_count' => $overallTotalCorrectCount,
                    'total_wrong_count' => $overallTotalWrongCount,
                    'average_percentage' => $overallAvgPct
                ]
            ]
        ];
    }
}

if (!function_exists('studentGetUnitVideosOnly')) {
    function studentGetUnitVideosOnly($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $body = read_json_body();
        $unitId = (int)($_GET['unit_id'] ?? $_GET['id'] ?? $body['unit_id'] ?? $body['id'] ?? 0);
        if ($unitId <= 0) {
            return ['status' => 'error', 'message' => 'unit_id is required'];
        }

        $langInfo = get_student_request_lang_info($con);
        $langId = (int)($langInfo['id'] ?? 2);

        $uResTmp = mysqli_query($con, "
            SELECT u.id, u.course_id, u.subject_id, u.code, u.videos_drip_enabled, COALESCE(ut.name, u.code) AS name
            FROM units u
            LEFT JOIN units_translations ut ON u.id = ut.unit_id AND ut.language_id = {$langId}
            WHERE u.id = {$unitId} AND u.is_active = 1 LIMIT 1
        ");
        $uRow = mysqli_fetch_assoc($uResTmp);
        if (!$uRow) {
            return ['status' => 'error', 'message' => 'Unit not found or inactive'];
        }
    $cId = (int)$uRow['course_id'];
        $sId = (int)($uRow['subject_id'] ?? 0);
        $isSubscribed = false;
        if ($stId > 0 && function_exists('get_student_accessible_entities')) {
            $accessible = get_student_accessible_entities($con, $stId);
            $isSubscribed = (
                in_array($cId, $accessible['course_ids']) ||
                ($sId > 0 && in_array($sId, $accessible['subject_ids']))
            );
        }

        $hasVideoProgress = studentTableExists($con, 'student_video_progress');
        $dripEnabled = (int)($uRow['videos_drip_enabled'] ?? 0) === 1;
        $previousVideoCompleted = true;

        $vSql = "
            SELECT v.id, v.unit_id, v.code, v.duration_seconds, v.publish_status, v.is_active,
                   COALESCE(cv.video_url, '') AS video_url,
                   COALESCE(vt.name, cvt.title, v.code) AS title,
                   COALESCE(vt.description, cvt.description, '') AS description
            FROM videos v
            LEFT JOIN course_videos cv ON v.id = cv.id
            LEFT JOIN videos_translations vt ON v.id = vt.video_id AND vt.language_id = {$langId}
            LEFT JOIN course_videos_translations cvt ON cv.id = cvt.video_id AND cvt.language_id = {$langId}
            WHERE v.unit_id = {$unitId} AND v.is_active = 1
            ORDER BY v.id ASC
        ";
        $vRes = mysqli_query($con, $vSql);

        $videos = [];
        $totalVideos = 0;
        $completedVideos = 0;
        $totalDurationSec = 0;
        $totalWatchedSec = 0;
        $vIndex = 1;

        if ($vRes) {
            while ($vRow = mysqli_fetch_assoc($vRes)) {
                $vId = (int)$vRow['id'];

                $vtCheck2 = mysqli_query($con, "
                    SELECT vt.id FROM video_teachers vt
                    JOIN teachers t ON vt.teacher_id = t.id
                    WHERE vt.video_id = {$vId} AND vt.is_active = 1 AND t.is_active = 1
                    LIMIT 1
                ");
                $hasVtRecords = mysqli_num_rows(mysqli_query($con, "SELECT id FROM video_teachers WHERE video_id = {$vId}")) > 0;
                if ($hasVtRecords && mysqli_num_rows($vtCheck2) === 0) {
                    continue;
                }

                $totalVideos++;

                $isFreePreview = false;
                $isDripLocked = $dripEnabled && $isSubscribed && !$previousVideoCompleted;
                $isLocked = !$isSubscribed || $isDripLocked;
                $lockReason = !$isSubscribed ? 'not_subscribed' : ($isDripLocked ? 'sequential_drip' : null);

                $durSec = (int)($vRow['duration_seconds'] ?? 0);
                $totalDurationSec += $durSec;

                $isCompleted = 0;
                $watchedSec = 0;
                $lastPos = 0;

                if ($stId > 0 && $hasVideoProgress) {
                    $vpRes = mysqli_query($con, "SELECT is_completed, watched_seconds, total_seconds, last_position_seconds FROM student_video_progress WHERE student_id = {$stId} AND video_id = {$vId} LIMIT 1");
                    if ($vpRes && $vpRow = mysqli_fetch_assoc($vpRes)) {
                        $isCompleted = (int)($vpRow['is_completed'] ?? 0);
                        $watchedSec = (int)($vpRow['watched_seconds'] ?? 0);
                        $lastPos = (int)($vpRow['last_position_seconds'] ?? 0);
                    }
                }

                $totalWatchedSec += $watchedSec;
                if ($isCompleted === 1) {
                    $completedVideos++;
                }
                // Next video's drip lock depends on THIS video's completion.
                $previousVideoCompleted = ($isCompleted === 1);

                $videos[] = [
                    'id' => $vId,
                    'unit_id' => $unitId,
                    'code' => $vRow['code'],
                    'title' => $vRow['title'],
                    'description' => $vRow['description'],
                    'video_url' => $isLocked ? null : format_image_url($vRow['video_url']),
                    'duration_seconds' => $durSec,
                    'publish_status' => $vRow['publish_status'],
                    'is_free_preview' => false,
                    'is_locked' => $isLocked,
                    'is_open' => !$isLocked,
                    'lock_reason' => $lockReason,
                    'progress' => [
                        'is_completed' => $isCompleted,
                        'watched_seconds' => $watchedSec,
                        'total_seconds' => $durSec,
                        'last_position_seconds' => $lastPos
                    ]
                ];
                $vIndex++;
            }
        }

        $completionPct = $totalVideos > 0 ? round(($completedVideos / $totalVideos) * 100, 2) : 0.00;

        return [
            'status' => 'success',
            'data' => [
                'language' => $langInfo['code'],
                'unit' => [
                    'id' => (int)$uRow['id'],
                    'course_id' => (int)$uRow['course_id'],
                    'code' => $uRow['code'],
                    'name' => $uRow['name'],
                    'videos_drip_enabled' => $dripEnabled ? 1 : 0
                ],
                'videos' => $videos,
                'statistics' => [
                    'total_videos_count' => $totalVideos,
                    'completed_videos_count' => $completedVideos,
                    'total_duration_seconds' => $totalDurationSec,
                    'total_watched_seconds' => $totalWatchedSec,
                    'completion_percentage' => $completionPct
                ]
            ]
        ];
    }
}

if (!function_exists('studentGetUnitQuestionsOnly')) {
    function studentGetUnitQuestionsOnly($con, $studentData = null) {
        if (function_exists('ensure_student_question_answers_table')) {
            ensure_student_question_answers_table($con);
        }

        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $body = read_json_body();
        $unitId = (int)($_GET['unit_id'] ?? $_GET['id'] ?? $body['unit_id'] ?? $body['id'] ?? 0);
        $courseId = (int)($_GET['course_id'] ?? $body['course_id'] ?? 0);

        if ($unitId <= 0 && $courseId <= 0) {
            return ['status' => 'error', 'message' => 'unit_id or course_id is required'];
        }

        $langInfo = get_student_request_lang_info($con);
        $langId = (int)($langInfo['id'] ?? 2);

        $unitData = null;
        if ($unitId > 0) {
            $uResTmp = mysqli_query($con, "
                SELECT u.id, u.course_id, u.code, COALESCE(ut.name, u.code) AS name
                FROM units u
                LEFT JOIN units_translations ut ON u.id = ut.unit_id AND ut.language_id = {$langId}
                WHERE u.id = {$unitId} AND u.is_active = 1 LIMIT 1
            ");
            if ($uResTmp && $uRow = mysqli_fetch_assoc($uResTmp)) {
                $unitData = [
                    'id' => (int)$uRow['id'],
                    'course_id' => (int)$uRow['course_id'],
                    'code' => $uRow['code'],
                    'name' => $uRow['name']
                ];
            }
        }

        $qWhere = ["q.is_active = 1"];
        if ($unitId > 0) {
            $qWhere[] = "q.unit_id = {$unitId}";
        }
        if ($courseId > 0) {
            $qWhere[] = "q.course_id = {$courseId}";
        }
        $qWhereStr = implode(" AND ", $qWhere);

        $qSql = "
            SELECT q.id, q.unit_id, q.course_id, q.question_type, q.score, q.is_active, q.created_by_type, q.created_by_id,
                   COALESCE(qt.question_text, '') AS question_text
            FROM questions q
            LEFT JOIN questions_translations qt ON q.id = qt.question_id AND qt.language_id = {$langId}
            WHERE {$qWhereStr}
            ORDER BY q.order_num ASC, q.id ASC
        ";
        $qRes = mysqli_query($con, $qSql);

        $questions = [];
        $totalQuestions = 0;
        $answeredQuestions = 0;
        $totalAnswersCount = 0;
        $totalCorrectCount = 0;
        $totalWrongCount = 0;

        if ($qRes) {
            while ($qRow = mysqli_fetch_assoc($qRes)) {
                $qId = (int)$qRow['id'];

                if ($qRow['created_by_type'] === 'teacher' && (int)$qRow['created_by_id'] > 0) {
                    $qtCheck1 = mysqli_query($con, "SELECT is_active FROM teachers WHERE id = " . (int)$qRow['created_by_id'] . " LIMIT 1");
                    if ($qtCheck1 && $qtRow1 = mysqli_fetch_assoc($qtCheck1)) {
                        if ((int)$qtRow1['is_active'] !== 1) {
                            continue;
                        }
                    }
                }

                $totalQuestions++;

                $options = [];
                if ($qRow['question_type'] === 'mcq') {
                    $optSql = "
                        SELECT mo.id, mo.is_correct,
                               COALESCE(mot.option_text, '') AS option_text
                        FROM mcq_options mo
                        LEFT JOIN mcq_options_translations mot ON mo.id = mot.mcq_option_id AND mot.language_id = {$langId}
                        WHERE mo.question_id = {$qId} AND mo.is_active = 1
                        ORDER BY mo.id ASC
                    ";
                    $optRes = mysqli_query($con, $optSql);
                    if ($optRes) {
                        while ($oRow = mysqli_fetch_assoc($optRes)) {
                            $options[] = [
                                'id' => (int)$oRow['id'],
                                'option_text' => $oRow['option_text'],
                                'is_correct' => (int)$oRow['is_correct']
                            ];
                        }
                    }
                }

                $tfAnswer = null;
                if ($qRow['question_type'] === 'true_false') {
                    $tfRes = mysqli_query($con, "SELECT correct_answer FROM true_false_answers WHERE question_id = {$qId} LIMIT 1");
                    if ($tfRes && $tfRow = mysqli_fetch_assoc($tfRes)) {
                        $tfAnswer = (int)$tfRow['correct_answer'];
                    }
                }

                $qStats = ($stId > 0 && function_exists('get_student_question_statistics')) ? get_student_question_statistics($con, $stId, $qId) : [
                    'times_answered' => 0,
                    'correct_answers_count' => 0,
                    'wrong_answers_count' => 0,
                    'correct_percentage' => 0.00,
                    'last_attempt' => null
                ];

                $ansCnt = $qStats['times_answered'];
                $corrCnt = $qStats['correct_answers_count'];
                $wrngCnt = $qStats['wrong_answers_count'];

                if ($ansCnt > 0) {
                    $answeredQuestions++;
                }

                $totalAnswersCount += $ansCnt;
                $totalCorrectCount += $corrCnt;
                $totalWrongCount += $wrngCnt;

                $questions[] = [
                    'id' => $qId,
                    'unit_id' => (int)$qRow['unit_id'],
                    'question_type' => $qRow['question_type'],
                    'score' => (float)$qRow['score'],
                    'question_text' => $qRow['question_text'],
                    'options' => $options,
                    'true_false_answer' => $tfAnswer,
                    'statistics' => $qStats
                ];
            }
        }

        $overallAvgPct = $totalAnswersCount > 0 ? round(($totalCorrectCount / $totalAnswersCount) * 100, 2) : 0.00;

        return [
            'status' => 'success',
            'data' => [
                'language' => $langInfo['code'],
                'unit' => $unitData,
                'questions' => $questions,
                'statistics' => [
                    'total_questions_count' => $totalQuestions,
                    'answered_questions_count' => $answeredQuestions,
                    'total_answers_count' => $totalAnswersCount,
                    'total_correct_count' => $totalCorrectCount,
                    'total_wrong_count' => $totalWrongCount,
                    'average_percentage' => $overallAvgPct
                ]
            ]
        ];
    }
}

if (!function_exists('studentGetCourseUnitsContent')) {
    function studentGetCourseUnitsContent($con, $studentData = null) {
        return studentGetCourseUnitsOnly($con, $studentData);
    }
}
