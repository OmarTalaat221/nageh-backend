<?php
require_once __DIR__ . '/../helpers.php';

if (!function_exists('get_book_details')) {
    function get_book_details($con, $bookId) {
        if (!$bookId) return null;
        ensure_content_tables_schema($con);
        $langInfo = get_language_info($con);
        $isAdmin = is_admin_request();

        $stmt = mysqli_prepare($con, "SELECT id, subject_id, course_id, unit_id, file_type, extension, link, file_path, order_num, is_active, created_by_type, created_by_id, created_at, updated_at FROM course_books WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $bookId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;
        $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
        $row['unit_id'] = $row['unit_id'] ? (int)$row['unit_id'] : null;
        $row['order_num'] = (int)$row['order_num'];
        $row['is_active'] = (int)$row['is_active'];
        $row['created_by_type'] = $row['created_by_type'] ?? 'admin';
        $row['created_by_id'] = isset($row['created_by_id']) && $row['created_by_id'] ? (int)$row['created_by_id'] : null;
        $row['created_by'] = get_course_created_by_details($con, $row['created_by_type'], $row['created_by_id']);

        $row['teachers'] = get_content_teachers_details($con, 'book', $bookId);
        $row['teacher_ids'] = array_values(array_map(function($t) { return (int)$t['id']; }, $row['teachers']));

        $tStmt = mysqli_prepare($con, "SELECT cbt.language_id, l.code AS language_code, cbt.title, cbt.description FROM course_books_translations cbt JOIN languages l ON cbt.language_id = l.id WHERE cbt.book_id = ?");
        $translations = [];
        $titleAr = null;
        $titleEn = null;
        $descAr = null;
        $descEn = null;
        if ($tStmt) {
            mysqli_stmt_bind_param($tStmt, "i", $bookId);
            mysqli_stmt_execute($tStmt);
            $tRes = mysqli_stmt_get_result($tStmt);
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
        $row['title'] = $transMap[$langInfo['code']] ?? ($titleAr ?? $titleEn ?? '');
        $row['description'] = $descMap[$langInfo['code']] ?? ($descAr ?? $descEn ?? '');

        if ($isAdmin) {
            $row['translations'] = $translations;
            $row['title_ar'] = $titleAr;
            $row['title_en'] = $titleEn;
            $row['description_ar'] = $descAr;
            $row['description_en'] = $descEn;
        }

        return format_response_images($row);
    }
}

if (!function_exists('adminListBooks')) {
    function adminListBooks($con) {
        ensure_content_tables_schema($con);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $unitIds = parse_int_list_param($_GET['unit_ids'] ?? $_GET['unit_id'] ?? $_GET['units'] ?? null);
        $courseIds = parse_int_list_param($_GET['course_ids'] ?? $_GET['course_id'] ?? $_GET['courses'] ?? null);
        $subjectIds = parse_int_list_param($_GET['subject_ids'] ?? $_GET['subject_id'] ?? $_GET['subjects'] ?? null);
        $teacherIds = parse_int_list_param($_GET['teacher_ids'] ?? $_GET['teacher_id'] ?? $_GET['teachers'] ?? null);
        $createdByIds = parse_int_list_param($_GET['created_by_ids'] ?? $_GET['created_by_id'] ?? null);
        $fileType = trim($_GET['file_type'] ?? '');
        $extension = trim($_GET['extension'] ?? '');
        $createdByType = strtolower(trim($_GET['created_by_type'] ?? ''));
        $activeFilter = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $sortBy = strtolower(trim($_GET['sort_by'] ?? $_GET['sort'] ?? 'order_num'));
        $sortDir = strtoupper(trim($_GET['dir'] ?? $_GET['direction'] ?? $_GET['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $allowedSort = [
            'id' => 'b.id',
            'order_num' => 'b.order_num',
            'file_type' => 'b.file_type',
            'extension' => 'b.extension',
            'created_at' => 'b.created_at',
            'title' => 'bt.title'
        ];
        $orderCol = $allowedSort[$sortBy] ?? 'b.order_num';

        $whereConditions = [];
        $params = [];
        $types = "";
        $joinBt = "";

        if (!empty($teacherIds)) {
            $joinBt = "LEFT JOIN book_teachers bt_piv ON b.id = bt_piv.book_id";
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $whereConditions[] = "((b.created_by_type = 'teacher' AND b.created_by_id IN ({$placeholders})) OR (bt_piv.teacher_id IN ({$placeholders}) AND bt_piv.is_active = 1) OR (b.created_by_type = 'admin' AND NOT EXISTS (SELECT 1 FROM book_teachers bt_chk WHERE bt_chk.book_id = b.id AND bt_chk.is_active = 1)))";
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
            foreach ($teacherIds as $tId) {
                $params[] = $tId;
                $types .= "i";
            }
        }

        if (!empty($unitIds)) {
            $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
            $whereConditions[] = "b.unit_id IN ({$placeholders})";
            foreach ($unitIds as $uId) {
                $params[] = $uId;
                $types .= "i";
            }
        }

        if (!empty($courseIds)) {
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $whereConditions[] = "b.course_id IN ({$placeholders})";
            foreach ($courseIds as $cId) {
                $params[] = $cId;
                $types .= "i";
            }
        }

        if (!empty($subjectIds)) {
            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
            $whereConditions[] = "b.subject_id IN ({$placeholders})";
            foreach ($subjectIds as $sId) {
                $params[] = $sId;
                $types .= "i";
            }
        }

        if (!empty($fileType)) {
            $whereConditions[] = "b.file_type = ?";
            $params[] = $fileType;
            $types .= "s";
        }

        if (!empty($extension)) {
            $whereConditions[] = "b.extension = ?";
            $params[] = $extension;
            $types .= "s";
        }

        if (in_array($createdByType, ['admin', 'teacher'])) {
            $whereConditions[] = "b.created_by_type = ?";
            $params[] = $createdByType;
            $types .= "s";
        }

        if (!empty($createdByIds)) {
            $placeholders = implode(',', array_fill(0, count($createdByIds), '?'));
            $whereConditions[] = "b.created_by_id IN ({$placeholders})";
            foreach ($createdByIds as $cbId) {
                $params[] = $cbId;
                $types .= "i";
            }
        }

        if ($activeFilter !== null) {
            $whereConditions[] = "b.is_active = ?";
            $params[] = $activeFilter;
            $types .= "i";
        }

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereConditions[] = "(b.link LIKE ? OR b.file_path LIKE ? OR bt.title LIKE ? OR bt.description LIKE ?)";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "ssss";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM course_books");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(DISTINCT b.id) AS total FROM course_books b {$joinBt} LEFT JOIN course_books_translations bt ON b.id = bt.book_id {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            if ($countStmt) {
                if (!empty($types)) {
                    mysqli_stmt_bind_param($countStmt, $types, ...$params);
                }
                mysqli_stmt_execute($countStmt);
                $countRes = mysqli_stmt_get_result($countStmt);
                $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
            }
        }

        $sql = "
            SELECT DISTINCT b.id
            FROM course_books b
            {$joinBt}
            LEFT JOIN course_books_translations bt ON b.id = bt.book_id
            {$whereClause}
            ORDER BY {$orderCol} {$sortDir}, b.id ASC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $books = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $bId = (int)$row['id'];
            $detail = get_book_details($con, $bId);
            if ($detail) {
                $books[] = $detail;
            }
        }

        return build_admin_paginated_response($books, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetBook')) {
    function adminGetBook($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Book ID is required'];
        }
        $book = get_book_details($con, $id);
        if (!$book) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }
        return ['status' => 'success', 'data' => $book];
    }
}

if (!function_exists('adminCreateBook')) {
    function adminCreateBook($con, $creatorData = null) {
        ensure_content_tables_schema($con);
        $body = read_json_body();
        $unitId = isset($body['unit_id']) && $body['unit_id'] > 0 ? (int)$body['unit_id'] : null;
        $courseId = isset($body['course_id']) && $body['course_id'] > 0 ? (int)$body['course_id'] : null;
        $subjectId = isset($body['subject_id']) && $body['subject_id'] > 0 ? (int)$body['subject_id'] : null;

        $err = validate_content_linkage($con, $unitId, $courseId, $subjectId);
        if ($err) return $err;

        $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
        $titleEn = trim($body['title_en'] ?? $titleAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);
        $fileType = trim($body['file_type'] ?? 'pdf');
        $extension = trim($body['extension'] ?? 'pdf');
        $link = trim($body['link'] ?? '');
        $filePathInput = $body['file_path'] ?? $body['file'] ?? null;

        $uploadedFile = $_FILES['file'] ?? $_FILES['book'] ?? null;
        $filePath = process_image_input($filePathInput, 'books', $uploadedFile);

        $orderNum = isset($body['order_num']) ? (int)$body['order_num'] : 1;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;

        $tId = 0;
        if (is_array($creatorData)) {
            $tId = (int)($creatorData['id'] ?? $creatorData['teacher_id'] ?? 0);
        } elseif (is_object($creatorData)) {
            $tId = (int)($creatorData->id ?? $creatorData->teacher_id ?? 0);
        }

        if ($tId > 0) {
            $createdByType = 'teacher';
            $createdById = $tId;
        } else {
            $createdByType = strtolower(trim($body['created_by_type'] ?? 'admin'));
            $createdById = isset($body['created_by_id']) && $body['created_by_id'] ? (int)$body['created_by_id'] : null;
        }

        if (empty($titleAr)) {
            return ['status' => 'error', 'message' => 'Book title (title_ar) is required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO course_books (subject_id, course_id, unit_id, file_type, extension, link, file_path, order_num, is_active, created_by_type, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare book insert: ' . mysqli_error($con)];
        }
        mysqli_stmt_bind_param($stmt, "iiissssissi", $subjectId, $courseId, $unitId, $fileType, $extension, $link, $filePath, $orderNum, $isActive, $createdByType, $createdById);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create book: ' . mysqli_error($con)];
        }

        $bookId = mysqli_insert_id($con);

        $tStmt = mysqli_prepare($con, "INSERT INTO course_books_translations (book_id, language_id, title, description) VALUES (?, 1, ?, ?), (?, 2, ?, ?)");
        mysqli_stmt_bind_param($tStmt, "ississ", $bookId, $titleAr, $descAr, $bookId, $titleEn, $descEn);
        mysqli_stmt_execute($tStmt);

        $teacherIds = $body['teacher_ids'] ?? [];
        if (!is_array($teacherIds)) {
            $teacherIds = is_numeric($teacherIds) ? [(int)$teacherIds] : [];
        }
        if ($createdByType === 'teacher' && $createdById > 0) {
            if (!in_array($createdById, $teacherIds)) {
                $teacherIds[] = $createdById;
            }
        }
        sync_content_teachers($con, 'book', $bookId, $teacherIds);

        return [
            'status' => 'success',
            'message' => 'Book created successfully',
            'data' => get_book_details($con, $bookId)
        ];
    }
}

if (!function_exists('adminUpdateBook')) {
    function adminUpdateBook($con, $creatorData = null) {
        ensure_content_tables_schema($con);
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Book ID is required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, subject_id, course_id, unit_id, file_path FROM course_books WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
        if (!$existing) {
            return ['status' => 'error', 'message' => 'Book not found'];
        }

        $unitId = isset($body['unit_id']) ? (int)$body['unit_id'] : (int)$existing['unit_id'];
        $courseId = isset($body['course_id']) ? (int)$body['course_id'] : (int)$existing['course_id'];
        $subjectId = isset($body['subject_id']) ? (int)$body['subject_id'] : (int)$existing['subject_id'];

        $err = validate_content_linkage($con, $unitId, $courseId, $subjectId);
        if ($err) return $err;

        $fileType = isset($body['file_type']) ? trim($body['file_type']) : null;
        $extension = isset($body['extension']) ? trim($body['extension']) : null;
        $link = isset($body['link']) ? trim($body['link']) : null;

        $filePathInput = $body['file_path'] ?? $body['file'] ?? null;
        $uploadedFile = $_FILES['file'] ?? $_FILES['book'] ?? null;
        $filePath = process_image_input($filePathInput, 'books', $uploadedFile);

        $orderNum = isset($body['order_num']) ? (int)$body['order_num'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;

        $updateFields = ["subject_id = ?", "course_id = ?", "unit_id = ?"];
        $params = [$subjectId, $courseId, $unitId];
        $types = "iii";

        if ($fileType !== null) {
            $updateFields[] = "file_type = ?";
            $params[] = $fileType;
            $types .= "s";
        }
        if ($extension !== null) {
            $updateFields[] = "extension = ?";
            $params[] = $extension;
            $types .= "s";
        }
        if ($link !== null) {
            $updateFields[] = "link = ?";
            $params[] = $link;
            $types .= "s";
        }
        if ($filePath !== null) {
            $updateFields[] = "file_path = ?";
            $params[] = $filePath;
            $types .= "s";
        }
        if ($orderNum !== null) {
            $updateFields[] = "order_num = ?";
            $params[] = $orderNum;
            $types .= "i";
        }
        if ($isActive !== null) {
            $updateFields[] = "is_active = ?";
            $params[] = $isActive;
            $types .= "i";
        }
        if (isset($body['created_by_type']) && in_array(strtolower($body['created_by_type']), ['admin', 'teacher'])) {
            $updateFields[] = "created_by_type = ?";
            $params[] = strtolower($body['created_by_type']);
            $types .= "s";
        }
        if (isset($body['created_by_id'])) {
            $updateFields[] = "created_by_id = ?";
            $params[] = (int)$body['created_by_id'];
            $types .= "i";
        }

        $params[] = $id;
        $types .= "i";

        $uSql = "UPDATE course_books SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $uStmt = mysqli_prepare($con, $uSql);
        mysqli_stmt_bind_param($uStmt, $types, ...$params);
        mysqli_stmt_execute($uStmt);

        if (isset($body['title_ar']) || isset($body['title_en']) || isset($body['title']) || isset($body['description_ar']) || isset($body['description_en'])) {
            $titleAr = trim($body['title_ar'] ?? $body['title'] ?? '');
            $titleEn = trim($body['title_en'] ?? $titleAr);
            $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
            $descEn = trim($body['description_en'] ?? $descAr);

            if (!empty($titleAr)) {
                $tStmt1 = mysqli_prepare($con, "INSERT INTO course_books_translations (book_id, language_id, title, description) VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
                mysqli_stmt_bind_param($tStmt1, "iss", $id, $titleAr, $descAr);
                mysqli_stmt_execute($tStmt1);
            }
            if (!empty($titleEn)) {
                $tStmt2 = mysqli_prepare($con, "INSERT INTO course_books_translations (book_id, language_id, title, description) VALUES (?, 2, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)");
                mysqli_stmt_bind_param($tStmt2, "iss", $id, $titleEn, $descEn);
                mysqli_stmt_execute($tStmt2);
            }
        }

        if (isset($body['teacher_ids'])) {
            sync_content_teachers($con, 'book', $id, $body['teacher_ids']);
        }

        return [
            'status' => 'success',
            'message' => 'Book updated successfully',
            'data' => get_book_details($con, $id)
        ];
    }
}

if (!function_exists('adminDeleteBook')) {
    function adminDeleteBook($con, $creatorData = null) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Book ID is required'];
        }
        $stmt = mysqli_prepare($con, "DELETE FROM course_books WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            @mysqli_query($con, "DELETE FROM course_books_translations WHERE book_id = {$id}");
            @mysqli_query($con, "DELETE FROM book_teachers WHERE book_id = {$id}");
            return ['status' => 'success', 'message' => 'Book deleted successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to delete book'];
    }
}
