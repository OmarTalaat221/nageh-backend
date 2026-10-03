<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../content/units.php';

if (!function_exists('ensure_books_schema')) {
    function ensure_books_schema($con) {
    }
}

if (!function_exists('adminGetBookDetails')) {
    function adminGetBookDetails($con, $bookId) {
        if (!$bookId) return null;
        $langInfo = get_language_info($con);

        $res = mysqli_query($con, "SELECT id, subject_id, course_id, unit_id, file_type, category, extension, link, file_path, cover_image, price, allow_download, order_num, is_active, created_by_type, created_by_id, created_at, updated_at FROM course_books WHERE id = {$bookId} LIMIT 1");
        if (!$res || !($row = mysqli_fetch_assoc($res))) {
            return null;
        }

        $row['id'] = (int)$row['id'];
        $row['unit_id'] = !empty($row['unit_id']) ? (int)$row['unit_id'] : null;
        $row['subject_id'] = !empty($row['subject_id']) ? (int)$row['subject_id'] : null;
        $row['course_id'] = !empty($row['course_id']) ? (int)$row['course_id'] : null;
        $row['category'] = $row['category'] ?: 'summary';
        $row['price'] = (float)($row['price'] ?? 0.00);
        $row['allow_download'] = (int)($row['allow_download'] ?? 1);
        $row['is_active'] = (int)$row['is_active'];
        $rawLink = $row['link'];
        $row['cover_image'] = format_image_url(!empty($row['cover_image']) ? $row['cover_image'] : $rawLink);

        // Admin/teacher open this file the same way a student does: a direct link when it is
        // downloadable, or - when allow_download was turned off specifically to keep it from
        // being extracted and shared - only through the authenticated view-only stream, never a
        // bare public URL. The flag is for the audience it was set for, not just for students.
        if ($row['allow_download'] === 1) {
            $row['link'] = format_image_url($row['link']);
            $row['file_path'] = format_image_url($row['file_path'] ?: $rawLink);
            $row['view_url'] = $row['file_path'];
        } else {
            $row['link'] = null;
            $row['file_path'] = null;
            $streamBase = is_teacher_request() ? 'teacher' : 'admin';
            $row['view_url'] = 'https://camp-coding.online/najeh/' . $streamBase . '/books/stream?book_id=' . $row['id'];
        }

        $translations = [];
        $titleAr = null;
        $titleEn = null;
        $descAr = null;
        $descEn = null;

        $tRes = mysqli_query($con, "SELECT cbt.language_id, l.code AS language_code, cbt.title, cbt.description FROM course_books_translations cbt JOIN languages l ON cbt.language_id = l.id WHERE cbt.book_id = {$bookId}");
        if ($tRes) {
            while ($tRow = mysqli_fetch_assoc($tRes)) {
                $tRow['language_id'] = (int)$tRow['language_id'];
                $translations[] = $tRow;
                if ($tRow['language_code'] === 'ar') {
                    $titleAr = $tRow['title'];
                    $descAr = $tRow['description'];
                }
                if ($tRow['language_code'] === 'en') {
                    $titleEn = $tRow['title'];
                    $descEn = $tRow['description'];
                }
            }
        }

        $transMap = array_column($translations, 'title', 'language_code');
        $descMap = array_column($translations, 'description', 'language_code');
        $row['title'] = $transMap[$langInfo['code']] ?? ($titleAr ?? $titleEn ?? ('Book #' . $row['id']));
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        $row['translations'] = $translations;
        $row['title_ar'] = $titleAr;
        $row['title_en'] = $titleEn;
        $row['description_ar'] = $descAr;
        $row['description_en'] = $descEn;

        $row['unit'] = $row['unit_id'] ? get_unit_details($con, $row['unit_id']) : null;
        $courseIdForLookup = $row['course_id'] ?: (int)($row['unit']['course_id'] ?? 0);
        $row['course'] = $courseIdForLookup ? get_course_details($con, $courseIdForLookup) : null;
        $row['subject'] = $row['subject_id'] ? get_subject_details($con, $row['subject_id']) : null;

        return format_response_images($row);
    }
}

if (!function_exists('adminListBooks')) {
    function adminListBooks($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $unitId = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
        $category = in_array(trim($_GET['category'] ?? ''), ['summary', 'exam'], true) ? trim($_GET['category']) : '';

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $cbWhere = ["1=1"];
        if ($unitId > 0) $cbWhere[] = "cb.unit_id = {$unitId}";
        if ($category !== '') $cbWhere[] = "cb.category = '{$category}'";
        if ($courseId > 0) $cbWhere[] = "(cb.course_id = {$courseId} OR cb.unit_id IN (SELECT id FROM units WHERE course_id = {$courseId}))";
        if ($subjectId > 0) $cbWhere[] = "cb.subject_id = {$subjectId}";
        if ($teacherId > 0) {
            $cbWhere[] = "(
                (cb.created_by_type = 'teacher' AND cb.created_by_id = {$teacherId})
                OR (cb.created_by_id = {$teacherId})
                OR cb.id IN (SELECT book_id FROM book_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR cb.course_id IN (SELECT id FROM courses WHERE teacher_id = {$teacherId} OR (created_by_type = 'teacher' AND created_by_id = {$teacherId}))
                OR cb.course_id IN (SELECT course_id FROM course_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR cb.unit_id IN (SELECT id FROM units WHERE (created_by_type = 'teacher' AND created_by_id = {$teacherId}))
                OR cb.unit_id IN (SELECT unit_id FROM unit_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR cb.subject_id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id = {$teacherId})
                OR (cb.created_by_type = 'admin' AND NOT EXISTS (SELECT 1 FROM book_teachers bt_chk WHERE bt_chk.book_id = cb.id AND bt_chk.is_active = 1))
            )";
        }
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $cbWhere[] = "(cbt.title LIKE '{$sp}' OR cbt.description LIKE '{$sp}')";
        }
        $cbWhereSql = "WHERE " . implode(" AND ", $cbWhere);

        $countRes = mysqli_query($con, "
            SELECT COUNT(DISTINCT cb.id) AS total
            FROM course_books cb
            LEFT JOIN course_books_translations cbt ON cb.id = cbt.book_id AND cbt.language_id = {$langId}
            {$cbWhereSql}
        ");
        $totalRecords = $countRes ? (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0) : 0;

        $sql = "
            SELECT DISTINCT cb.id
            FROM course_books cb
            LEFT JOIN course_books_translations cbt ON cb.id = cbt.book_id AND cbt.language_id = {$langId}
            {$cbWhereSql}
            ORDER BY cb.order_num ASC, cb.id ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        $books = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $bId = (int)$row['id'];
                $detail = adminGetBookDetails($con, $bId);
                if ($detail) {
                    if ($teacherId > 0) {
                        $detail['can_edit'] = ($detail['created_by_type'] ?? 'admin') !== 'admin';
                    }
                    $books[] = $detail;
                }
            }
        }

        return build_admin_paginated_response($books, $page, $limit, $totalRecords);
    }
}

if (!function_exists('adminGetBook')) {
    function adminGetBook($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Book ID is required'];
        }
        $book = adminGetBookDetails($con, $id);
        if (!$book) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }
        return ['status' => 'success', 'data' => $book];
    }
}

if (!function_exists('adminCreateBook')) {
    function adminCreateBook($con, $creatorData = null) {
        $body = read_json_body();

        $creatorType = 'admin';
        $creatorId = 1;
        if (is_array($creatorData)) {
            $creatorType = (!empty($creatorData['teacher_id']) || isset($creatorData['specialization']) || ($creatorData['role'] ?? '') === 'teacher' || ($creatorData['user_type'] ?? '') === 'teacher') ? 'teacher' : 'admin';
            $creatorId = (int)($creatorData['id'] ?? $creatorData['teacher_id'] ?? 1);
        } elseif (is_object($creatorData)) {
            $creatorType = (!empty($creatorData->teacher_id) || isset($creatorData->specialization) || ($creatorData->role ?? '') === 'teacher' || ($creatorData->user_type ?? '') === 'teacher') ? 'teacher' : 'admin';
            $creatorId = (int)($creatorData->id ?? $creatorData->teacher_id ?? 1);
        }

        $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
        $titleEn = trim($body['title_en'] ?? $titleAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);

        // read_json_body() already uploads any raw $_FILES entry itself and rewrites $body[key] into
        // a hosted URL (the generic handler further up in helpers/functions.php) - 'cover_image'/
        // 'image' already relied on that. The PDF upload never worked because nothing here ever read
        // the uploaded 'file' key the same way, only 'file_path' (which the form never sends).
        $filePath = trim($body['file_path'] ?? $body['file'] ?? '');
        $link = trim($body['link'] ?? '');
        $coverImage = trim($body['cover_image'] ?? $body['image'] ?? '');

        $fileType = trim($body['file_type'] ?? 'pdf');
        $category = in_array(trim($body['category'] ?? ''), ['summary', 'exam'], true) ? trim($body['category']) : 'summary';
        $extension = trim($body['extension'] ?? 'pdf');
        $courseId = isset($body['course_id']) && (int)$body['course_id'] > 0 ? (int)$body['course_id'] : null;
        $unitId = isset($body['unit_id']) && (int)$body['unit_id'] > 0 ? (int)$body['unit_id'] : null;
        $subjectId = isset($body['subject_id']) && (int)$body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        // A unit-scoped book only ever got unit_id from the form - course_id/subject_id were left
        // NULL on the row forever, resolvable only at read time by whichever endpoint bothered to
        // join units. Storing the real values here keeps every consumer consistent.
        if ($unitId !== null && ($courseId === null || $subjectId === null)) {
            $uRes = mysqli_query($con, "SELECT course_id, subject_id FROM units WHERE id = {$unitId} LIMIT 1");
            if ($uRes && $uRow = mysqli_fetch_assoc($uRes)) {
                if ($courseId === null && !empty($uRow['course_id'])) {
                    $courseId = (int)$uRow['course_id'];
                }
                if ($subjectId === null && !empty($uRow['subject_id'])) {
                    $subjectId = (int)$uRow['subject_id'];
                }
            }
        }

        // Books tied to a course/unit are bundled with the course purchase and
        // must never carry their own separate price — only standalone
        // (external) books, with no course_id/unit_id, are individually priced.
        $isCourseLinked = ($courseId !== null || $unitId !== null);
        $price = $isCourseLinked ? 0.00 : (float)($body['price'] ?? 0.00);
        $allowDownload = isset($body['allow_download']) ? (int)(bool)$body['allow_download'] : 1;

        $cbSub = $subjectId ? $subjectId : 'NULL';
        $cbCourse = $courseId ? $courseId : 'NULL';
        $cbUnit = $unitId ? $unitId : 'NULL';

        $cbSql = "INSERT INTO course_books (subject_id, course_id, unit_id, file_type, category, extension, link, file_path, cover_image, price, allow_download, is_active, created_by_type, created_by_id) VALUES ({$cbSub}, {$cbCourse}, {$cbUnit}, '" . mysqli_real_escape_string($con, $fileType) . "', '" . mysqli_real_escape_string($con, $category) . "', '" . mysqli_real_escape_string($con, $extension) . "', '" . mysqli_real_escape_string($con, $link) . "', '" . mysqli_real_escape_string($con, $filePath) . "', '" . mysqli_real_escape_string($con, $coverImage) . "', {$price}, {$allowDownload}, {$isActive}, '{$creatorType}', {$creatorId})";
        if (!mysqli_query($con, $cbSql)) {
            return ['status' => 'error', 'message' => 'Failed to create book: ' . mysqli_error($con)];
        }
        $bookId = mysqli_insert_id($con);
        if ($creatorType === 'teacher' && $creatorId > 0) {
            @mysqli_query($con, "INSERT INTO book_teachers (book_id, teacher_id, is_active) VALUES ({$bookId}, {$creatorId}, 1) ON DUPLICATE KEY UPDATE is_active = 1");
        }

        if (!empty($titleAr) || !empty($descAr)) {
            @mysqli_query($con, "INSERT INTO course_books_translations (book_id, language_id, title, description) VALUES ({$bookId}, 1, '" . mysqli_real_escape_string($con, $titleAr) . "', '" . mysqli_real_escape_string($con, $descAr) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
        }
        if (!empty($titleEn) || !empty($descEn)) {
            @mysqli_query($con, "INSERT INTO course_books_translations (book_id, language_id, title, description) VALUES ({$bookId}, 2, '" . mysqli_real_escape_string($con, $titleEn) . "', '" . mysqli_real_escape_string($con, $descEn) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
        }

        return [
            'status' => 'success',
            'message' => 'Book created successfully',
            'data' => adminGetBookDetails($con, $bookId)
        ];
    }
}

if (!function_exists('adminUpdateBook')) {
    function adminUpdateBook($con, $creatorData = null) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Book ID is required'];
        }

        $existingRes = mysqli_query($con, "SELECT course_id, unit_id FROM course_books WHERE id = {$id} LIMIT 1");
        $existingRow = $existingRes ? mysqli_fetch_assoc($existingRes) : null;
        if (!$existingRow) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }

        $resolvedCourseId = isset($body['course_id']) ? (int)$body['course_id'] : (int)($existingRow['course_id'] ?? 0);
        $resolvedUnitId = isset($body['unit_id']) ? (int)$body['unit_id'] : (int)($existingRow['unit_id'] ?? 0);
        // Books tied to a course/unit are bundled with the course purchase —
        // force their price to 0 regardless of what was submitted.
        $isCourseLinked = ($resolvedCourseId > 0 || $resolvedUnitId > 0);

        $updateFields = [];

        // Same backfill as creation: a unit-scoped book must carry its unit's real course_id/
        // subject_id, not leave them NULL forever just because the form only ever sent unit_id.
        if ($resolvedUnitId > 0 && (!isset($body['course_id']) || !isset($body['subject_id']))) {
            $uRes2 = mysqli_query($con, "SELECT course_id, subject_id FROM units WHERE id = {$resolvedUnitId} LIMIT 1");
            if ($uRes2 && $uRow2 = mysqli_fetch_assoc($uRes2)) {
                if (!isset($body['course_id']) && !empty($uRow2['course_id'])) {
                    $updateFields[] = "course_id = " . (int)$uRow2['course_id'];
                }
                if (!isset($body['subject_id']) && !empty($uRow2['subject_id'])) {
                    $updateFields[] = "subject_id = " . (int)$uRow2['subject_id'];
                }
            }
        }

        if ($isCourseLinked) {
            $updateFields[] = "price = 0";
        } elseif (isset($body['price'])) {
            $price = (float)$body['price'];
            $updateFields[] = "price = {$price}";
        }
        if (isset($body['allow_download'])) {
            $allowDownload = (int)(bool)$body['allow_download'];
            $updateFields[] = "allow_download = {$allowDownload}";
        }
        // read_json_body() already uploads a raw $_FILES entry itself and rewrites $body[key] into a
        // hosted URL - 'cover_image'/'image' already relied on that below. The PDF upload never
        // worked because nothing here ever read the uploaded 'file' key, only 'file_path' (which the
        // edit form never sends).
        if (isset($body['file_path']) || isset($body['file'])) {
            $fpEsc = mysqli_real_escape_string($con, trim($body['file_path'] ?? $body['file']));
            $updateFields[] = "file_path = '{$fpEsc}'";
        }
        if (isset($body['link'])) {
            $linkEsc = mysqli_real_escape_string($con, trim($body['link']));
            $updateFields[] = "link = '{$linkEsc}'";
        }
        if (isset($body['cover_image']) || isset($body['image'])) {
            $imgEsc = mysqli_real_escape_string($con, trim($body['cover_image'] ?? $body['image']));
            $updateFields[] = "cover_image = '{$imgEsc}'";
        }
        if (isset($body['file_type'])) {
            $ftEsc = mysqli_real_escape_string($con, trim($body['file_type']));
            $updateFields[] = "file_type = '{$ftEsc}'";
            $updateFields[] = "extension = '{$ftEsc}'";
        }
        if (in_array(trim($body['category'] ?? ''), ['summary', 'exam'], true)) {
            $updateFields[] = "category = '" . trim($body['category']) . "'";
        }
        if (isset($body['is_active'])) {
            $act = (int)$body['is_active'];
            $updateFields[] = "is_active = {$act}";
        }
        if (isset($body['subject_id'])) {
            $subVal = (int)$body['subject_id'] > 0 ? (int)$body['subject_id'] : 'NULL';
            $updateFields[] = "subject_id = {$subVal}";
        }
        if (isset($body['course_id'])) {
            $crsVal = (int)$body['course_id'] > 0 ? (int)$body['course_id'] : 'NULL';
            $updateFields[] = "course_id = {$crsVal}";
        }
        if (isset($body['unit_id'])) {
            $unitVal = (int)$body['unit_id'] > 0 ? (int)$body['unit_id'] : 'NULL';
            $updateFields[] = "unit_id = {$unitVal}";
        }

        if (!empty($updateFields)) {
            mysqli_query($con, "UPDATE course_books SET " . implode(", ", $updateFields) . " WHERE id = {$id}");
        }

        if (isset($body['title_ar']) || isset($body['title_en']) || isset($body['title']) || isset($body['description_ar']) || isset($body['description_en'])) {
            $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
            $titleEn = trim($body['title_en'] ?? $titleAr);
            $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
            $descEn = trim($body['description_en'] ?? $descAr);

            if (!empty($titleAr)) {
                @mysqli_query($con, "INSERT INTO course_books_translations (book_id, language_id, title, description) VALUES ({$id}, 1, '" . mysqli_real_escape_string($con, $titleAr) . "', '" . mysqli_real_escape_string($con, $descAr) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
            }
            if (!empty($titleEn)) {
                @mysqli_query($con, "INSERT INTO course_books_translations (book_id, language_id, title, description) VALUES ({$id}, 2, '" . mysqli_real_escape_string($con, $titleEn) . "', '" . mysqli_real_escape_string($con, $descEn) . "') ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
            }
        }

        return [
            'status' => 'success',
            'message' => 'Book updated successfully',
            'data' => adminGetBookDetails($con, $id)
        ];
    }
}

if (!function_exists('adminDeleteBook')) {
    function adminDeleteBook($con, $creatorData = null) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Book ID is required'];
        }
        if (mysqli_query($con, "DELETE FROM course_books WHERE id = {$id}")) {
            @mysqli_query($con, "DELETE FROM course_books_translations WHERE book_id = {$id}");
            return ['status' => 'success', 'message' => 'Book deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete book'];
    }
}

if (!function_exists('adminReorderBooks')) {
    // Persists a manually drag-reordered list of a unit's books/exam PDFs. Scoped to one
    // unit + category at a time, same as the Units reorder - the two categories are always
    // browsed as separate lists, so their order_num sequences are independent of each other.
    // Body: { unit_id, category, ordered_ids: [book_id, book_id, ...] } - the new order, top to bottom.
    function adminReorderBooks($con) {
        $body = read_json_body();
        $unitId = (int)($body['unit_id'] ?? 0);
        $category = in_array(trim($body['category'] ?? ''), ['summary', 'exam'], true) ? trim($body['category']) : null;
        $orderedIds = $body['ordered_ids'] ?? $body['order'] ?? [];

        if ($unitId <= 0) {
            return ['status' => 'error', 'message' => 'unit_id is required'];
        }
        if (!is_array($orderedIds) || empty($orderedIds)) {
            return ['status' => 'error', 'message' => 'ordered_ids is required'];
        }

        $categorySql = $category !== null ? "AND category = '{$category}'" : '';
        $position = 1;
        foreach ($orderedIds as $bookId) {
            $bookId = (int)$bookId;
            if ($bookId <= 0) continue;
            mysqli_query($con, "UPDATE course_books SET order_num = {$position} WHERE id = {$bookId} AND unit_id = {$unitId} {$categorySql}");
            $position++;
        }

        return ['status' => 'success', 'message' => 'Books reordered successfully'];
    }
}

if (!function_exists('adminStreamBookFile')) {
    // Admin/teacher's own "Open" action, kept consistent with the student's authenticated
    // view-only stream: when allow_download = 0, adminGetBookDetails() hands out this URL
    // instead of the raw file_path/link, specifically so the file never ends up as a bare,
    // copyable, permanent public link - not even in the hands of the people managing it.
    // No per-book ownership check: being an authenticated admin or teacher (enforced by
    // whichever middleware wraps the route) is itself the permission.
    function adminStreamBookFile($con) {
        $bookId = (int)($_GET['book_id'] ?? $_GET['id'] ?? 0);
        if ($bookId <= 0) {
            return ['status' => 'error', 'message' => 'book_id is required'];
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

        $localPath = realpath(__DIR__ . '/../../../' . ltrim($source, '/'));
        $uploadsRoot = realpath(__DIR__ . '/../../../');
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

if (!function_exists('adminToggleBookWebsiteVisibility')) {
    function adminToggleBookWebsiteVisibility($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        $showOnWebsite = isset($body['show_on_website']) ? (int)$body['show_on_website'] : (isset($_GET['show_on_website']) ? (int)$_GET['show_on_website'] : null);

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Book ID is required'];
        }

        if ($showOnWebsite !== null) {
            $val = $showOnWebsite ? 1 : 0;
            @mysqli_query($con, "UPDATE course_books SET show_on_website = {$val} WHERE id = {$id}");
        } else {
            @mysqli_query($con, "UPDATE course_books SET show_on_website = IF(show_on_website = 1, 0, 1) WHERE id = {$id}");
        }

        return ['status' => 'success', 'message' => 'Book website visibility updated successfully'];
    }
}

if (!function_exists('adminGrantBookAccessToStudent')) {
    function adminGrantBookAccessToStudent($con) {
        $body = read_json_body();

        $bookId = (int)($body['book_id'] ?? $body['id'] ?? $_GET['book_id'] ?? 0);
        $studentIds = [];
        if (!empty($body['student_ids']) && is_array($body['student_ids'])) {
            $studentIds = array_map('intval', $body['student_ids']);
        } elseif (!empty($body['student_id'])) {
            $studentIds = [(int)$body['student_id']];
        } elseif (!empty($_GET['student_id'])) {
            $studentIds = [(int)$_GET['student_id']];
        }

        if ($bookId <= 0 || empty($studentIds)) {
            return ['status' => 'error', 'message' => 'book_id and student_id (or student_ids) are required'];
        }

        $bRes = mysqli_query($con, "SELECT id, price FROM course_books WHERE id = {$bookId} LIMIT 1");
        $bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
        if (!$bRow) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }

        $price = (float)($bRow['price'] ?? 0.00);
        $grantedCount = 0;

        foreach ($studentIds as $stId) {
            if ($stId <= 0) continue;

            $chk = mysqli_query($con, "SELECT id FROM student_book_subscriptions WHERE student_id = {$stId} AND book_id = {$bookId} LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) {
                mysqli_query($con, "UPDATE student_book_subscriptions SET is_active = 1 WHERE student_id = {$stId} AND book_id = {$bookId}");
            } else {
                mysqli_query($con, "INSERT INTO student_book_subscriptions (student_id, book_id, granted_by_type, granted_by_id, price, is_active) VALUES ({$stId}, {$bookId}, 'admin', 1, {$price}, 1)");
            }

            $subStmt = mysqli_prepare($con, "INSERT INTO student_subscriptions (student_id, subscription_type, is_active) VALUES (?, 'custom', 1)");
            if ($subStmt) {
                mysqli_stmt_bind_param($subStmt, "i", $stId);
                mysqli_stmt_execute($subStmt);
                $subId = mysqli_insert_id($con);
                if ($subId > 0) {
                    mysqli_query($con, "INSERT INTO student_subscription_books (student_subscription_id, book_id) VALUES ({$subId}, {$bookId})");
                }
            }

            $grantedCount++;
        }

        return [
            'status' => 'success',
            'message' => "Book access granted successfully to {$grantedCount} student(s)",
            'data' => [
                'book_id' => $bookId,
                'granted_students_count' => $grantedCount
            ]
        ];
    }
}

// teacherGrantBookAccessToStudent moved to app/teacher/books/manage.php

if (!function_exists('adminListBookPurchaseRequests')) {
    function adminListBookPurchaseRequests($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, (int)($_GET['limit'] ?? 20));
        $offset = ($page - 1) * $limit;

        $studentId = (int)($_GET['student_id'] ?? 0);
        $bookId = (int)($_GET['book_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? $_GET['category_id'] ?? 0);
        $status = trim($_GET['status'] ?? '');
        $reviewedByType = trim($_GET['reviewed_by_type'] ?? '');
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $whereClause = "WHERE 1=1";

       if ($studentId > 0) {
    $whereClause .= " AND sbpr.student_id = " . (int)$studentId;
}

        if ($bookId > 0) {
            $whereClause .= " AND sbpr.book_id = {$bookId}";
        }

        if ($subjectId > 0) {
            $whereClause .= " AND cb.subject_id = {$subjectId}";
        }

        if ($status !== '') {
            $statusEsc = mysqli_real_escape_string($con, $status);
            $whereClause .= " AND sbpr.status = '{$statusEsc}'";
        }

        if ($reviewedByType !== '') {
            $reviewedByTypeEsc = mysqli_real_escape_string($con, $reviewedByType);
            $whereClause .= " AND sbpr.reviewed_by_type = '{$reviewedByTypeEsc}'";
        }

        if ($search !== '') {
            $searchEsc = mysqli_real_escape_string($con, $search);
            $searchLike = "%{$searchEsc}%";

            $whereClause .= "
                AND (
                    st.name LIKE '{$searchLike}'
                    OR st.email LIKE '{$searchLike}'
                    OR st.phone LIKE '{$searchLike}'
                    OR cbt.title LIKE '{$searchLike}'
                    OR sbpr.status LIKE '{$searchLike}'
                    OR sbpr.notes LIKE '{$searchLike}'
                    OR subt.name LIKE '{$searchLike}'
                )
            ";
        }

        $countSql = "
            SELECT COUNT(DISTINCT sbpr.id) AS total
            FROM student_book_purchase_requests sbpr
            JOIN students st
                ON sbpr.student_id = st.id
            LEFT JOIN course_books cb
                ON sbpr.book_id = cb.id
            LEFT JOIN course_books_translations cbt
                ON cb.id = cbt.book_id
                AND cbt.language_id = {$langId}
            LEFT JOIN subjects sub
                ON cb.subject_id = sub.id
            LEFT JOIN subjects_translations subt
                ON sub.id = subt.subject_id
                AND subt.language_id = {$langId}
            {$whereClause}
        ";

        $countRes = mysqli_query($con, $countSql);

        $totalRecords = 0;

        if ($countRes) {
            $countRow = mysqli_fetch_assoc($countRes);
            $totalRecords = (int)($countRow['total'] ?? 0);
        }

        $sql = "
            SELECT
                sbpr.id,
                sbpr.student_id,
                sbpr.book_id,
                sbpr.status,
                sbpr.reviewed_by_type,
                sbpr.reviewed_by_id,
                sbpr.notes,
                sbpr.created_at,
                sbpr.updated_at,

                st.name AS student_name,
                st.email AS student_email,
                st.phone AS student_phone,
                st.image AS student_image,

                COALESCE(
                    cbt.title,
                    cb.file_path
                ) AS book_title,

                cbt.description AS book_description,
                cb.file_path AS book_file_path,
                cb.link AS book_cover_image,
                cb.price AS book_price,
                cb.allow_download AS book_allow_download,
                cb.subject_id AS subject_id,
                subt.name AS subject_name

            FROM student_book_purchase_requests sbpr

            JOIN students st
                ON sbpr.student_id = st.id

            LEFT JOIN course_books cb
                ON sbpr.book_id = cb.id

            LEFT JOIN course_books_translations cbt
                ON cb.id = cbt.book_id
                AND cbt.language_id = {$langId}

            LEFT JOIN subjects sub
                ON cb.subject_id = sub.id

            LEFT JOIN subjects_translations subt
                ON sub.id = subt.subject_id
                AND subt.language_id = {$langId}

            {$whereClause}

            ORDER BY sbpr.id DESC

            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $res = mysqli_query($con, $sql);

        $items = [];

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['student_id'] = (int)$row['student_id'];
                $row['book_id'] = (int)$row['book_id'];

                $row['reviewed_by_id'] = !empty($row['reviewed_by_id'])
                    ? (int)$row['reviewed_by_id']
                    : null;

                $row['student_image'] = !empty($row['student_image'])
                    ? format_image_url($row['student_image'])
                    : null;

                $row['book_cover_image'] = !empty($row['book_cover_image'])
                    ? format_image_url($row['book_cover_image'])
                    : null;

                $row['book_file_path'] = !empty($row['book_file_path'])
                    ? format_image_url($row['book_file_path'])
                    : null;

                $row['book_price'] = (float)($row['book_price'] ?? 0.00);
                $row['book_allow_download'] = (int)($row['book_allow_download'] ?? 1);
                $row['subject_id'] = !empty($row['subject_id']) ? (int)$row['subject_id'] : null;
                $row['subject_name'] = $row['subject_name'] ?? null;

                $items[] = $row;
            }
        }

return build_admin_paginated_response(
    $items,
    $page,
    $limit,
    $totalRecords,
    [
        'total_count' => $totalRecords
    ]
);
    }
}

// teacherListBookPurchaseRequests moved to app/teacher/books/manage.php

if (!function_exists('adminReviewBookRequest')) {
    function adminReviewBookRequest($con, $adminData = null) {
        $adminId = 0;
        if (is_array($adminData)) {
            $adminId = (int)($adminData['id'] ?? $adminData['admin_id'] ?? 0);
        } elseif (is_object($adminData)) {
            $adminId = (int)($adminData->id ?? $adminData->admin_id ?? 0);
        }

        $body = read_json_body();
        $requestId = (int)($body['request_id'] ?? $_GET['request_id'] ?? 0);
        $newStatus = trim($body['status'] ?? '');
        $notes = trim($body['notes'] ?? '');

        if ($requestId <= 0) {
            return ['status' => 'error', 'message' => 'request_id is required'];
        }
        if (!in_array($newStatus, ['approved', 'rejected'])) {
            return ['status' => 'error', 'message' => 'status must be approved or rejected'];
        }

        $rRes = mysqli_query($con, "SELECT id, student_id, book_id, status FROM student_book_purchase_requests WHERE id = {$requestId} LIMIT 1");
        $rRow = $rRes ? mysqli_fetch_assoc($rRes) : null;
        if (!$rRow) {
            return ['status' => 'error', 'message' => 'Purchase request not found'];
        }
        if ($rRow['status'] !== 'pending') {
            return ['status' => 'error', 'message' => 'This request has already been reviewed'];
        }

        $stId = (int)$rRow['student_id'];
        $bookId = (int)$rRow['book_id'];
        $notesEsc = mysqli_real_escape_string($con, $notes);

        mysqli_query($con, "UPDATE student_book_purchase_requests SET status = '{$newStatus}', reviewed_by_type = 'admin', reviewed_by_id = {$adminId}, notes = '{$notesEsc}' WHERE id = {$requestId}");

        if ($newStatus === 'approved') {
            $bRes = mysqli_query($con, "SELECT price FROM course_books WHERE id = {$bookId} LIMIT 1");
            $bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
            $price = $bRow ? (float)$bRow['price'] : 0.00;

            $chk = mysqli_query($con, "SELECT id FROM student_book_subscriptions WHERE student_id = {$stId} AND book_id = {$bookId} LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) {
                mysqli_query($con, "UPDATE student_book_subscriptions SET is_active = 1, granted_by_type = 'admin', granted_by_id = {$adminId} WHERE student_id = {$stId} AND book_id = {$bookId}");
            } else {
                mysqli_query($con, "INSERT INTO student_book_subscriptions (student_id, book_id, granted_by_type, granted_by_id, price, is_active) VALUES ({$stId}, {$bookId}, 'admin', {$adminId}, {$price}, 1)");
            }
        }

        return [
            'status' => 'success',
            'message' => $newStatus === 'approved' ? 'Request approved and book access granted' : 'Request rejected',
            'data' => ['request_id' => $requestId, 'status' => $newStatus]
        ];
    }
}

