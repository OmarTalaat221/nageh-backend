<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../content/helpers.php';
require_once __DIR__ . '/../subscriptions/manage.php';

if (!function_exists('enrich_student_book_details')) {
    function enrich_student_book_details($con, array $row) {
        $bookId = (int)$row['id'];
        $courseId = $row['course_id'] ? (int)$row['course_id'] : 0;
        $subjectId = $row['subject_id'] ? (int)$row['subject_id'] : 0;
        $unitId = $row['unit_id'] ? (int)$row['unit_id'] : 0;

        $row['unit'] = null;
        if ($unitId > 0) {
            $uStmt = mysqli_prepare($con, "SELECT u.id, u.course_id, u.subject_id, (SELECT name FROM units_translations ut WHERE ut.unit_id = u.id AND ut.language_id = 1 LIMIT 1) AS unit_name FROM units u WHERE u.id = ? LIMIT 1");
            if ($uStmt) {
                mysqli_stmt_bind_param($uStmt, "i", $unitId);
                mysqli_stmt_execute($uStmt);
                $uRow = mysqli_fetch_assoc(mysqli_stmt_get_result($uStmt));
                if ($uRow) {
                    $uRow['id'] = (int)$uRow['id'];
                    $row['unit'] = $uRow;
                    if ($courseId <= 0 && !empty($uRow['course_id'])) {
                        $courseId = (int)$uRow['course_id'];
                        $row['course_id'] = $courseId;
                    }
                    if ($subjectId <= 0 && !empty($uRow['subject_id'])) {
                        $subjectId = (int)$uRow['subject_id'];
                        $row['subject_id'] = $subjectId;
                    }
                }
            }
        }

        if ($subjectId <= 0 && $courseId > 0) {
            $cRes = mysqli_query($con, "SELECT subject_id FROM courses WHERE id = {$courseId} LIMIT 1");
            if ($cRes && $cRow = mysqli_fetch_assoc($cRes)) {
                if ($cRow['subject_id']) {
                    $subjectId = (int)$cRow['subject_id'];
                    $row['subject_id'] = $subjectId;
                }
            }
        }

        $row['subject'] = $subjectId > 0 ? get_subject_details($con, $subjectId) : null;

        $row['course'] = null;
        if ($courseId > 0) {
            $cStmt = mysqli_prepare($con, "SELECT c.id, c.price, c.image, c.teacher_id,
                                                  (SELECT name FROM courses_translations ct WHERE ct.course_id = c.id AND ct.language_id = 1 LIMIT 1) AS course_name
                                           FROM courses c WHERE c.id = ? LIMIT 1");
            if ($cStmt) {
                mysqli_stmt_bind_param($cStmt, "i", $courseId);
                mysqli_stmt_execute($cStmt);
                $cRow = mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt));
                if ($cRow) {
                    $cRow['id'] = (int)$cRow['id'];
                    $cRow['price'] = (float)$cRow['price'];
                    $cRow['image'] = format_image_url($cRow['image']);
                    $row['course'] = $cRow;
                }
            }
        }

        $teacherId = 0;
        if (($row['created_by_type'] ?? '') === 'teacher' && ($row['created_by_id'] ?? 0) > 0) {
            $teacherId = (int)$row['created_by_id'];
        } elseif (!empty($row['course']['teacher_id'])) {
            $teacherId = (int)$row['course']['teacher_id'];
        } else {
            $btRes = mysqli_query($con, "SELECT teacher_id FROM book_teachers WHERE book_id = {$bookId} LIMIT 1");
            if ($btRes && $btRow = mysqli_fetch_assoc($btRes)) {
                $teacherId = (int)$btRow['teacher_id'];
            }
        }

        $row['teacher'] = null;
        if ($teacherId > 0) {
            $tStmt = mysqli_prepare($con, "SELECT id, name, email, specialization, image, rating FROM teachers WHERE id = ? LIMIT 1");
            if ($tStmt) {
                mysqli_stmt_bind_param($tStmt, "i", $teacherId);
                mysqli_stmt_execute($tStmt);
                $tRow = mysqli_fetch_assoc(mysqli_stmt_get_result($tStmt));
                if ($tRow) {
                    $tRow['id'] = (int)$tRow['id'];
                    $tRow['rating'] = $tRow['rating'] !== null ? (float)$tRow['rating'] : 5.0;
                    $row['teacher'] = format_response_images($tRow);
                }
            }
        }

        $row['price'] = isset($row['price']) ? (float)$row['price'] : (!empty($row['course']['price']) ? (float)$row['course']['price'] : 0.00);
        $row['rating'] = isset($row['rating']) ? (float)$row['rating'] : (!empty($row['teacher']['rating']) ? (float)$row['teacher']['rating'] : 5.0);

        return format_response_images($row);
    }
}

if (!function_exists('apply_student_book_file_access')) {
    // Owned book: either expose the raw downloadable URL, or (when
    // allow_download = 0) hide it and expose only an authenticated
    // view-only streaming endpoint instead.
    function apply_student_book_file_access(array &$row, int $bookId): void {
        if ((int)($row['allow_download'] ?? 1) === 1) {
            $row['file_path'] = format_image_url($row['file_path'] ?: $row['link']);
            $row['view_url'] = $row['file_path'];
            return;
        }

        $row['file_path'] = null;
        $row['link'] = null;
        $row['view_url'] = 'https://camp-coding.online/najeh/student/books/stream?book_id=' . $bookId;
    }
}

if (!function_exists('studentGetAllBooksWithOwn')) {
    function studentGetAllBooksWithOwn($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $accessibleCourseIds = $accessible['course_ids'];
        $accessibleSubjectIds = $accessible['subject_ids'];
        $accessibleTeacherIds = $accessible['teacher_ids'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $body = read_json_body();
        $qsParams = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str(str_replace('?', '&', $_SERVER['QUERY_STRING']), $qsParams);
        }

        $search = trim($_GET['search'] ?? $_GET['q'] ?? $qsParams['search'] ?? $qsParams['q'] ?? $body['search'] ?? $body['q'] ?? '');
        $unitId = (int)($_GET['unit_id'] ?? $_GET['unitId'] ?? $qsParams['unit_id'] ?? $qsParams['unitId'] ?? $body['unit_id'] ?? $body['unitId'] ?? $_REQUEST['unit_id'] ?? $_REQUEST['unitId'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? $_GET['subjectId'] ?? $qsParams['subject_id'] ?? $qsParams['subjectId'] ?? $body['subject_id'] ?? $body['subjectId'] ?? $_REQUEST['subject_id'] ?? $_REQUEST['subjectId'] ?? 0);
        $courseId = (int)($_GET['course_id'] ?? $_GET['courseId'] ?? $qsParams['course_id'] ?? $qsParams['courseId'] ?? $body['course_id'] ?? $body['courseId'] ?? $_REQUEST['course_id'] ?? $_REQUEST['courseId'] ?? 0);

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $cbWhere = ["b.is_active = 1"];
        if ($unitId > 0) {
            $cbWhere[] = "b.unit_id = {$unitId}";
        }
        if ($courseId > 0) {
            $cbWhere[] = "(b.course_id = {$courseId} OR b.unit_id IN (SELECT id FROM units WHERE course_id = {$courseId}))";
        }
        if ($subjectId > 0) {
            $cbWhere[] = "((b.subject_id IS NOT NULL AND b.subject_id = {$subjectId}) OR (b.subject_id IS NULL AND (b.course_id IN (SELECT id FROM courses WHERE subject_id = {$subjectId}) OR b.unit_id IN (SELECT u.id FROM units u JOIN courses c ON u.course_id = c.id WHERE c.subject_id = {$subjectId}))))";
        }
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $cbWhere[] = "(bt.title LIKE '{$sp}' OR bt.description LIKE '{$sp}')";
        }
        $cbWhereSql = "WHERE " . implode(" AND ", $cbWhere);

        $countRes = mysqli_query($con, "
            SELECT COUNT(DISTINCT b.id) AS total
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            {$cbWhereSql}
        ");
        $totalRecords = $countRes ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;

        $finalSql = "
            SELECT b.id, b.unit_id, b.subject_id, b.course_id, b.file_type, b.extension, b.link, b.file_path, b.price, b.allow_download,
                   b.created_by_type, b.created_by_id, b.is_active, b.created_at,
                   COALESCE(bt.title, b.file_path) AS title, bt.description, 'course_books' AS src_table
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            {$cbWhereSql}
            ORDER BY b.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $res = mysqli_query($con, $finalSql);
        if ($res === false) {
            return ['status' => 'error', 'message' => 'Books query failed: ' . mysqli_error($con)];
        }
        $books = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $bId = (int)$row['id'];
            $cId = $row['course_id'] ? (int)$row['course_id'] : 0;
            $sId = $row['subject_id'] ? (int)$row['subject_id'] : 0;
            $uId = $row['unit_id'] ? (int)$row['unit_id'] : 0;
            $createdById = (int)$row['created_by_id'];

            if ($cId <= 0 && $uId > 0) {
                $uRes = mysqli_query($con, "SELECT course_id, subject_id FROM units WHERE id = {$uId} LIMIT 1");
                if ($uRes && $uRow = mysqli_fetch_assoc($uRes)) {
                    if ($uRow['course_id']) $cId = (int)$uRow['course_id'];
                    if ($uRow['subject_id']) $sId = (int)$uRow['subject_id'];
                }
            }
            if ($sId <= 0 && $cId > 0) {
                $cRes = mysqli_query($con, "SELECT subject_id FROM courses WHERE id = {$cId} LIMIT 1");
                if ($cRes && $cRow = mysqli_fetch_assoc($cRes)) {
                    if ($cRow['subject_id']) $sId = (int)$cRow['subject_id'];
                }
            }

            $row['id'] = $bId;
            $row['unit_id'] = $uId ?: null;
            $row['subject_id'] = $sId ?: null;
            $row['course_id'] = $cId ?: null;
            $row['is_active'] = (int)$row['is_active'];

            $isDirectSub = false;
            if ($stId > 0) {
                $subChk = @mysqli_query($con, "SELECT id FROM student_book_subscriptions WHERE student_id = {$stId} AND book_id = {$bId} AND is_active = 1 LIMIT 1");
                if ($subChk && mysqli_num_rows($subChk) > 0) {
                    $isDirectSub = true;
                } else {
                    $subChk2 = @mysqli_query($con, "SELECT ssb.id FROM student_subscription_books ssb JOIN student_subscriptions ss ON ssb.student_subscription_id = ss.id WHERE ss.student_id = {$stId} AND ssb.book_id = {$bId} AND ss.is_active = 1 LIMIT 1");
                    if ($subChk2 && mysqli_num_rows($subChk2) > 0) {
                        $isDirectSub = true;
                    }
                }
            }

            // Teacher-based auto-unlock only applies to books actually tied to a
            // course/subject (bundled books) — a standalone external book must
            // always be purchased separately, even if the student is otherwise
            // accessible to that same teacher.
            $isOwn = (
                $isDirectSub ||
                ($cId > 0 && in_array($cId, $accessibleCourseIds)) ||
                ($sId > 0 && in_array($sId, $accessibleSubjectIds)) ||
                (($cId > 0 || $sId > 0) && $row['created_by_type'] === 'teacher' && in_array($createdById, $accessibleTeacherIds))
            );

            $row['own'] = $isOwn;
            $row['is_subscribed'] = $isOwn;
            $row['is_locked'] = !$isOwn;
            $row['is_open'] = $isOwn;
            $row['allow_download'] = (int)($row['allow_download'] ?? 1);

            if ($isOwn) {
                apply_student_book_file_access($row, $bId);
            } else {
                $row['file_path'] = null;
                $row['link'] = null;
                $row['view_url'] = null;
            }

            $books[] = enrich_student_book_details($con, $row);
        }

        return build_admin_paginated_response($books, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('studentGetMySubscribedBooks')) {
    function studentGetMySubscribedBooks($con, $studentData = null) {
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
            return build_admin_paginated_response([], 1, 10, 0, ['total_count' => 0]);
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $courseIds = $accessible['course_ids'];
        $subjectIds = $accessible['subject_ids'];
        $teacherIds = $accessible['teacher_ids'];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $body = read_json_body();
        $qsParams = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str(str_replace('?', '&', $_SERVER['QUERY_STRING']), $qsParams);
        }

        $search = trim($_GET['search'] ?? $_GET['q'] ?? $qsParams['search'] ?? $qsParams['q'] ?? $body['search'] ?? $body['q'] ?? '');
        $unitId = (int)($_GET['unit_id'] ?? $_GET['unitId'] ?? $qsParams['unit_id'] ?? $qsParams['unitId'] ?? $body['unit_id'] ?? $body['unitId'] ?? $_REQUEST['unit_id'] ?? $_REQUEST['unitId'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? $_GET['subjectId'] ?? $qsParams['subject_id'] ?? $qsParams['subjectId'] ?? $body['subject_id'] ?? $body['subjectId'] ?? $_REQUEST['subject_id'] ?? $_REQUEST['subjectId'] ?? 0);
        $courseId = (int)($_GET['course_id'] ?? $_GET['courseId'] ?? $qsParams['course_id'] ?? $qsParams['courseId'] ?? $body['course_id'] ?? $body['courseId'] ?? $_REQUEST['course_id'] ?? $_REQUEST['courseId'] ?? 0);

        $orConditions = [];
        if (!empty($courseIds)) {
            $cList = implode(',', array_map('intval', $courseIds));
            $orConditions[] = "b.course_id IN ({$cList})";
        }
        if (!empty($subjectIds)) {
            $sList = implode(',', array_map('intval', $subjectIds));
            $orConditions[] = "b.subject_id IN ({$sList})";
        }
        if (!empty($teacherIds)) {
            $tList = implode(',', array_map('intval', $teacherIds));
            // Standalone external books (no course/unit/subject) are never
            // auto-unlocked just because the student can access the teacher.
            $orConditions[] = "(b.created_by_type = 'teacher' AND b.created_by_id IN ({$tList}) AND (b.course_id IS NOT NULL OR b.unit_id IS NOT NULL OR b.subject_id IS NOT NULL))";
            $orConditions[] = "(b.id IN (SELECT book_id FROM book_teachers WHERE teacher_id IN ({$tList}) AND is_active = 1) AND (b.course_id IS NOT NULL OR b.unit_id IS NOT NULL OR b.subject_id IS NOT NULL))";
        }

        $orConditions[] = "b.id IN (SELECT book_id FROM student_book_subscriptions WHERE student_id = {$stId} AND is_active = 1)";
        $orConditions[] = "b.id IN (SELECT ssb.book_id FROM student_subscription_books ssb JOIN student_subscriptions ss ON ssb.student_subscription_id = ss.id WHERE ss.student_id = {$stId} AND ss.is_active = 1)";

        $subClause = "(" . implode(" OR ", $orConditions) . ")";
        $whereSearch = "";
        if ($unitId > 0) {
            $whereSearch .= " AND b.unit_id = {$unitId}";
        }
        if ($courseId > 0) {
            $whereSearch .= " AND (b.course_id = {$courseId} OR b.unit_id IN (SELECT id FROM units WHERE course_id = {$courseId}))";
        }
        if ($subjectId > 0) {
            $whereSearch .= " AND (b.subject_id = {$subjectId} OR b.course_id IN (SELECT id FROM courses WHERE subject_id = {$subjectId}) OR b.unit_id IN (SELECT u.id FROM units u JOIN courses c ON u.course_id = c.id WHERE c.subject_id = {$subjectId}))";
        }
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereSearch .= " AND (bt.title LIKE '{$sp}' OR bt.description LIKE '{$sp}')";
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $countSql = "
            SELECT COUNT(DISTINCT b.id) AS total
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            WHERE b.is_active = 1 AND {$subClause} {$whereSearch}
        ";
        $countRes = mysqli_query($con, $countSql);
        if ($countRes === false) {
            return ['status' => 'error', 'message' => 'Books count query failed: ' . mysqli_error($con)];
        }
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT DISTINCT b.id, b.unit_id, b.subject_id, b.course_id, b.file_type, b.extension, b.link, b.file_path, b.allow_download, b.created_by_type, b.created_by_id, b.is_active, b.created_at,
                   COALESCE(bt.title, b.file_path) AS title, bt.description
            FROM course_books b
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id AND bt.language_id = {$langId}
            WHERE b.is_active = 1 AND {$subClause} {$whereSearch}
            ORDER BY b.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        if ($res === false) {
            return ['status' => 'error', 'message' => 'Books query failed: ' . mysqli_error($con)];
        }
        $books = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['unit_id'] = $row['unit_id'] ? (int)$row['unit_id'] : null;
            $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
            $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
            $row['is_active'] = (int)$row['is_active'];
            $row['allow_download'] = (int)($row['allow_download'] ?? 1);
            apply_student_book_file_access($row, $row['id']);
            $row['own'] = true;
            $row['is_subscribed'] = true;
            $books[] = enrich_student_book_details($con, $row);
        }

        return build_admin_paginated_response($books, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('studentTrackBookOpen')) {
    function studentTrackBookOpen($con, $studentData = null) {
        $body = read_json_body();
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
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

        $bookId = (int)($body['book_id'] ?? 0);
        if ($bookId <= 0) {
            return ['status' => 'error', 'message' => 'book_id is required'];
        }

        $bStmt = mysqli_prepare($con, "SELECT id, unit_id, subject_id, course_id, created_by_type, created_by_id FROM course_books WHERE id = ? LIMIT 1");
        if (!$bStmt) {
            return ['status' => 'error', 'message' => 'Query preparation error'];
        }
        mysqli_stmt_bind_param($bStmt, "i", $bookId);
        mysqli_stmt_execute($bStmt);
        $bRow = mysqli_fetch_assoc(mysqli_stmt_get_result($bStmt));
        if (!$bRow) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }

        $courseId = (int)($bRow['course_id'] ?? 0);
        $subjectId = (int)($bRow['subject_id'] ?? 0);
        $accessible = get_student_accessible_entities($con, $stId);
        $isOwn = (
            in_array($courseId, $accessible['course_ids']) ||
            in_array($subjectId, $accessible['subject_ids'])
        );

        if (!$isOwn) {
            return ['status' => 'error', 'message' => 'You must be subscribed to access this book'];
        }

        return ['status' => 'success', 'message' => 'Book access granted'];
    }
}

if (!function_exists('ensure_books_schema')) {
    function ensure_books_schema($con) {
    }
}

if (!function_exists('studentSubscribeBook')) {
    function studentSubscribeBook($con, $studentData = null) {
        ensure_books_schema($con);
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
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

        $body = read_json_body();
        $bookId = (int)($body['book_id'] ?? $body['id'] ?? $_GET['book_id'] ?? 0);
        if ($bookId <= 0) {
            return ['status' => 'error', 'message' => 'book_id is required'];
        }

        $bRes = mysqli_query($con, "SELECT id, course_id, unit_id FROM course_books WHERE id = {$bookId} LIMIT 1");
        $bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
        if (!$bRow) {
            $bRes = mysqli_query($con, "SELECT id FROM books WHERE id = {$bookId} LIMIT 1");
            $bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
        }
        if (!$bRow) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }

        // Course/unit-bundled books are never purchased on their own — access
        // comes automatically from owning the course. Only standalone
        // external books go through the purchase-request flow.
        if (!empty($bRow['course_id']) || !empty($bRow['unit_id'])) {
            return ['status' => 'error', 'message' => 'This book is bundled with its course and cannot be purchased separately. You already have access to it if you own the related course.'];
        }

        $activeChk = mysqli_query($con, "SELECT id FROM student_book_subscriptions WHERE student_id = {$stId} AND book_id = {$bookId} AND is_active = 1 LIMIT 1");
        if ($activeChk && mysqli_num_rows($activeChk) > 0) {
            return ['status' => 'error', 'message' => 'You are already subscribed to this book'];
        }

        $rChk = mysqli_query($con, "SELECT id, status FROM student_book_purchase_requests WHERE student_id = {$stId} AND book_id = {$bookId} ORDER BY id DESC LIMIT 1");
        if ($rChk && $rRow = mysqli_fetch_assoc($rChk)) {
            if ($rRow['status'] === 'pending') {
                return ['status' => 'error', 'message' => 'Your purchase request is already pending review'];
            }
            if ($rRow['status'] === 'approved') {
                return ['status' => 'error', 'message' => 'You are already subscribed to this book'];
            }
        }

        mysqli_query($con, "INSERT INTO student_book_purchase_requests (student_id, book_id, status) VALUES ({$stId}, {$bookId}, 'pending')");

        return [
            'status' => 'success',
            'message' => 'Purchase request submitted successfully, awaiting approval',
            'data' => [
                'book_id' => $bookId,
                'request_status' => 'pending'
            ]
        ];
    }
}

if (!function_exists('student_owns_book')) {
    function student_owns_book($con, int $stId, int $bookId): bool {
        if ($stId <= 0 || $bookId <= 0) return false;

        $subChk = @mysqli_query($con, "SELECT id FROM student_book_subscriptions WHERE student_id = {$stId} AND book_id = {$bookId} AND is_active = 1 LIMIT 1");
        if ($subChk && mysqli_num_rows($subChk) > 0) return true;

        $subChk2 = @mysqli_query($con, "SELECT ssb.id FROM student_subscription_books ssb JOIN student_subscriptions ss ON ssb.student_subscription_id = ss.id WHERE ss.student_id = {$stId} AND ssb.book_id = {$bookId} AND ss.is_active = 1 LIMIT 1");
        if ($subChk2 && mysqli_num_rows($subChk2) > 0) return true;

        $bRes = mysqli_query($con, "SELECT unit_id, subject_id, course_id, created_by_type, created_by_id FROM course_books WHERE id = {$bookId} LIMIT 1");
        $bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
        if (!$bRow) return false;

        $cId = (int)($bRow['course_id'] ?? 0);
        $sId = (int)($bRow['subject_id'] ?? 0);
        $uId = (int)($bRow['unit_id'] ?? 0);
        if ($cId <= 0 && $uId > 0) {
            $uRes = mysqli_query($con, "SELECT course_id, subject_id FROM units WHERE id = {$uId} LIMIT 1");
            if ($uRes && $uRow = mysqli_fetch_assoc($uRes)) {
                if ($uRow['course_id']) $cId = (int)$uRow['course_id'];
                if ($uRow['subject_id']) $sId = (int)$uRow['subject_id'];
            }
        }
        if ($sId <= 0 && $cId > 0) {
            $cRes = mysqli_query($con, "SELECT subject_id FROM courses WHERE id = {$cId} LIMIT 1");
            if ($cRes && $cRow = mysqli_fetch_assoc($cRes)) {
                if ($cRow['subject_id']) $sId = (int)$cRow['subject_id'];
            }
        }

        $accessible = get_student_accessible_entities($con, $stId);
        $createdById = (int)($bRow['created_by_id'] ?? 0);

        return (
            ($cId > 0 && in_array($cId, $accessible['course_ids'])) ||
            ($sId > 0 && in_array($sId, $accessible['subject_ids'])) ||
            (($cId > 0 || $sId > 0) && ($bRow['created_by_type'] ?? '') === 'teacher' && in_array($createdById, $accessible['teacher_ids']))
        );
    }
}

if (!function_exists('studentStreamBookFile')) {
    // Authenticated, ownership-checked file stream — used for books with
    // allow_download = 0 instead of exposing a raw permanent public URL.
    function studentStreamBookFile($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

        $bookId = (int)($_GET['book_id'] ?? $_GET['id'] ?? 0);
        if ($bookId <= 0) {
            return ['status' => 'error', 'message' => 'book_id is required'];
        }

        if (!student_owns_book($con, $stId, $bookId)) {
            return ['status' => 'error', 'message' => 'You must be subscribed to access this book'];
        }

        $bRes = mysqli_query($con, "SELECT file_path, link, file_type FROM course_books WHERE id = {$bookId} LIMIT 1");
        $bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
        if (!$bRow) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }

        $source = trim($bRow['file_path'] ?: $bRow['link'] ?: '');
        if ($source === '') {
            return ['status' => 'error', 'message' => 'Book file is not available'];
        }

        $mime = ((trim($bRow['file_type'] ?? 'pdf')) === 'pdf') ? 'application/pdf' : 'application/octet-stream';

        if (preg_match('/^https?:\/\//i', $source)) {
            $ch = curl_init($source);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $content = curl_exec($ch);
            $ok = ($content !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) < 400);
            curl_close($ch);

            if (!$ok) {
                return ['status' => 'error', 'message' => 'Failed to load book file'];
            }

            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="book-' . $bookId . '"');
            header('X-Content-Type-Options: nosniff');
            echo $content;
            exit();
        }

        $localPath = realpath(__DIR__ . '/../../../../' . ltrim($source, '/'));
        $uploadsRoot = realpath(__DIR__ . '/../../../../');
        if (!$localPath || !$uploadsRoot || !str_starts_with($localPath, $uploadsRoot) || !is_file($localPath)) {
            return ['status' => 'error', 'message' => 'Book file is not available'];
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($localPath) . '"');
        header('Content-Length: ' . filesize($localPath));
        header('X-Content-Type-Options: nosniff');
        readfile($localPath);
        exit();
    }
}

if (!function_exists('studentGetMyBookRequests')) {
    function studentGetMyBookRequests($con, $studentData = null) {
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
            return build_admin_paginated_response([], 1, 10, 0, ['total_count' => 0]);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $statusFilter = trim($_GET['status'] ?? '');
        $whereExtra = '';
        $allowed = ['pending', 'approved', 'rejected'];
        if (in_array($statusFilter, $allowed)) {
            $sf = mysqli_real_escape_string($con, $statusFilter);
            $whereExtra = " AND r.status = '{$sf}'";
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $countRes = mysqli_query($con, "SELECT COUNT(*) AS total FROM student_book_purchase_requests r WHERE r.student_id = {$stId}{$whereExtra}");
        $total = $countRes ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;

        $sql = "
            SELECT r.id, r.book_id, r.status, r.notes, r.reviewed_by_type, r.reviewed_by_id, r.created_at, r.updated_at,
                   COALESCE(bt.title, cb.file_path) AS book_title,
                   cb.link AS book_cover
            FROM student_book_purchase_requests r
            LEFT JOIN course_books cb ON r.book_id = cb.id
            LEFT JOIN course_books_translations bt ON cb.id = bt.book_id AND bt.language_id = {$langId}
            WHERE r.student_id = {$stId}{$whereExtra}
            ORDER BY r.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $res = mysqli_query($con, $sql);
        $items = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['book_id'] = (int)$row['book_id'];
                $row['reviewed_by_id'] = $row['reviewed_by_id'] ? (int)$row['reviewed_by_id'] : null;
                $row['book_cover'] = format_image_url($row['book_cover']);
                $items[] = $row;
            }
        }

        return build_admin_paginated_response($items, $page, $limit, $total, ['total_count' => $total]);
    }
}
