<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('adminListParents')) {
    function adminListParents($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? $_GET['query'] ?? '');

        $whereClause = "";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $searchPattern = '%' . $search . '%';
            $whereClause = "WHERE (p.name LIKE ? OR p.email LIKE ?)";
            $params = [$searchPattern, $searchPattern];
            $types = "ss";
        }

        $statRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count FROM parents");
        $statRow = mysqli_fetch_assoc($statRes);
        $totalRecords = (int)($statRow['total'] ?? 0);
        $statistics = [
            'total_count' => $totalRecords,
            'active_count' => (int)($statRow['active_count'] ?? 0),
            'inactive_count' => (int)($statRow['inactive_count'] ?? 0)
        ];

        if (!empty($whereClause)) {
            $countSql = "SELECT COUNT(*) AS total FROM parents p {$whereClause}";
            $countStmt = mysqli_prepare($con, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countRes = mysqli_stmt_get_result($countStmt);
            $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
        }

        $sql = "SELECT p.id, p.name, p.email, p.image, p.city_id, p.is_email_verified, p.is_active, p.created_at FROM parents p {$whereClause} ORDER BY p.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $parents = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $pId = (int)$row['id'];
            $cId = $row['city_id'] ? (int)$row['city_id'] : null;

            $row['id'] = $pId;
            $row['city_id'] = $cId;
            $row['is_email_verified'] = (int)$row['is_email_verified'];
            $row['is_active'] = (int)$row['is_active'];
            $row['city'] = get_city_details($con, $cId);

            // Fetch linked students
            $studentsStmt = mysqli_prepare($con, "SELECT s.id, s.name, s.email, s.image, s.phone, s.age, s.city_id, s.school_id FROM students s JOIN parent_students ps ON s.id = ps.student_id WHERE ps.parent_id = ? AND ps.is_active = 1");
            mysqli_stmt_bind_param($studentsStmt, "i", $pId);
            mysqli_stmt_execute($studentsStmt);
            $studentsRes = mysqli_stmt_get_result($studentsStmt);

            $students = [];
            while ($sRow = mysqli_fetch_assoc($studentsRes)) {
                $scId = $sRow['city_id'] ? (int)$sRow['city_id'] : null;
                $schId = $sRow['school_id'] ? (int)$sRow['school_id'] : null;

                $sRow['id'] = (int)$sRow['id'];
                $sRow['city_id'] = $scId;
                $sRow['school_id'] = $schId;
                $sRow['age'] = $sRow['age'] ? (int)$sRow['age'] : null;
                $sRow['city'] = get_city_details($con, $scId);
                $sRow['school'] = get_school_details($con, $schId);
                $students[] = $sRow;
            }
            $row['students'] = $students;
            $row['student_ids'] = array_values(array_map(function($s) { return (int)$s['id']; }, $students));

            $parents[] = $row;
        }

        return build_admin_paginated_response(format_response_images($parents), $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('adminGetParent')) {
    function adminGetParent($con) {
        $id = (int)($_GET['id'] ?? read_json_body()['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Parent ID is required'];

        $stmt = mysqli_prepare($con, "SELECT id, name, email, image, city_id, is_email_verified, is_active, created_at FROM parents WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) return ['status' => 'error', 'message' => 'Parent not found'];

        $pId = (int)$row['id'];
        $cId = $row['city_id'] ? (int)$row['city_id'] : null;

        $row['id'] = $pId;
        $row['city_id'] = $cId;
        $row['is_email_verified'] = (int)$row['is_email_verified'];
        $row['is_active'] = (int)$row['is_active'];
        $row['city'] = get_city_details($con, $cId);

        $studentsStmt = mysqli_prepare($con, "SELECT s.id, s.name, s.email, s.image, s.phone, s.age, s.city_id, s.school_id FROM students s JOIN parent_students ps ON s.id = ps.student_id WHERE ps.parent_id = ? AND ps.is_active = 1");
        mysqli_stmt_bind_param($studentsStmt, "i", $pId);
        mysqli_stmt_execute($studentsStmt);
        $studentsRes = mysqli_stmt_get_result($studentsStmt);

        $students = [];
        while ($sRow = mysqli_fetch_assoc($studentsRes)) {
            $scId = $sRow['city_id'] ? (int)$sRow['city_id'] : null;
            $schId = $sRow['school_id'] ? (int)$sRow['school_id'] : null;

            $sRow['id'] = (int)$sRow['id'];
            $sRow['city_id'] = $scId;
            $sRow['school_id'] = $schId;
            $sRow['age'] = $sRow['age'] ? (int)$sRow['age'] : null;
            $sRow['city'] = get_city_details($con, $scId);
            $sRow['school'] = get_school_details($con, $schId);
            $students[] = $sRow;
        }
        $row['students'] = $students;
        $row['student_ids'] = array_values(array_map(function($s) { return (int)$s['id']; }, $students));

        return ['status' => 'success', 'data' => format_response_images($row)];
    }
}

if (!function_exists('adminCreateParent')) {
    function adminCreateParent($con) {
        $body = read_json_body();
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $image = trim($body['image'] ?? '');
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $isVerified = isset($body['is_email_verified']) ? (int)$body['is_email_verified'] : 1;

        if (empty($name) || empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => 'Name, email, and password are required'];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'Email already registered'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($con, "INSERT INTO parents (name, email, password, image, city_id, is_email_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssiii", $name, $email, $hashedPassword, $image, $cityId, $isVerified, $isActive);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create parent: ' . mysqli_error($con)];
        }

        $parentId = mysqli_insert_id($con);

        // Optionally link student(s) if provided upon creation
        if (isset($body['student_ids']) && is_array($body['student_ids'])) {
            $linkStmt = mysqli_prepare($con, "INSERT INTO parent_students (parent_id, student_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
            foreach ($body['student_ids'] as $sid) {
                $stId = (int)$sid;
                if ($stId > 0) {
                    mysqli_stmt_bind_param($linkStmt, "ii", $parentId, $stId);
                    mysqli_stmt_execute($linkStmt);
                }
            }
        }

        $_GET['id'] = $parentId;
        return [
            'status' => 'success',
            'message' => 'Parent created successfully',
            'data' => adminGetParent($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUpdateParent')) {
    function adminUpdateParent($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Parent ID is required'];

        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = trim($body['password'] ?? '');
        $image = isset($body['image']) ? trim($body['image']) : null;
        $cityId = isset($body['city_id']) ? (int)$body['city_id'] : null;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : null;
        $isVerified = isset($body['is_email_verified']) ? (int)$body['is_email_verified'] : null;

        if (!empty($name)) {
            $stmt = mysqli_prepare($con, "UPDATE parents SET name = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $name, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($email)) {
            $stmt = mysqli_prepare($con, "UPDATE parents SET email = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
        }
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($con, "UPDATE parents SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($image !== null) {
            $stmt = mysqli_prepare($con, "UPDATE parents SET image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $image, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($cityId !== null) {
            $stmt = mysqli_prepare($con, "UPDATE parents SET city_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $cityId, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isActive !== null) {
            $stmt = mysqli_prepare($con, "UPDATE parents SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);
            mysqli_stmt_execute($stmt);
        }
        if ($isVerified !== null) {
            $stmt = mysqli_prepare($con, "UPDATE parents SET is_email_verified = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $isVerified, $id);
            mysqli_stmt_execute($stmt);
        }

        $_GET['id'] = $id;
        return [
            'status' => 'success',
            'message' => 'Parent updated successfully',
            'data' => adminGetParent($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminDeleteParent')) {
    function adminDeleteParent($con) {
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => 'Parent ID is required'];

        $stmt = mysqli_prepare($con, "DELETE FROM parents WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete parent: ' . mysqli_error($con)];
        }
        return ['status' => 'success', 'message' => 'Parent deleted successfully'];
    }
}

if (!function_exists('adminLinkStudentToParent')) {
    function adminLinkStudentToParent($con) {
        $body = read_json_body();
        $parentId = (int)($body['parent_id'] ?? 0);
        $parentEmail = strtolower(trim($body['parent_email'] ?? ''));
        $studentId = (int)($body['student_id'] ?? 0);
        $studentEmail = strtolower(trim($body['student_email'] ?? ''));

        if ($parentId <= 0 && !empty($parentEmail)) {
            $pStmt = mysqli_prepare($con, "SELECT id FROM parents WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($pStmt, "s", $parentEmail);
            mysqli_stmt_execute($pStmt);
            $pRes = mysqli_stmt_get_result($pStmt);
            if ($pRow = mysqli_fetch_assoc($pRes)) {
                $parentId = (int)$pRow['id'];
            }
        }

        if ($studentId <= 0 && !empty($studentEmail)) {
            $sStmt = mysqli_prepare($con, "SELECT id FROM students WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($sStmt, "s", $studentEmail);
            mysqli_stmt_execute($sStmt);
            $sRes = mysqli_stmt_get_result($sStmt);
            if ($sRow = mysqli_fetch_assoc($sRes)) {
                $studentId = (int)$sRow['id'];
            }
        }

        if ($parentId <= 0 || $studentId <= 0) {
            return ['status' => 'error', 'message' => 'Valid parent_id (or parent_email) and student_id (or student_email) are required'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO parent_students (parent_id, student_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        mysqli_stmt_bind_param($stmt, "ii", $parentId, $studentId);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to link student to parent: ' . mysqli_error($con)];
        }

        $_GET['id'] = $parentId;
        return [
            'status' => 'success',
            'message' => 'Student linked to parent successfully',
            'data' => adminGetParent($con)['data'] ?? null
        ];
    }
}

if (!function_exists('adminUnlinkStudentFromParent')) {
    function adminUnlinkStudentFromParent($con) {
        $body = read_json_body();
        $parentId = (int)($body['parent_id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);

        if ($parentId <= 0 || $studentId <= 0) {
            return ['status' => 'error', 'message' => 'parent_id and student_id are required'];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM parent_students WHERE parent_id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $parentId, $studentId);
        mysqli_stmt_execute($stmt);

        return ['status' => 'success', 'message' => 'Student unlinked from parent successfully'];
    }
}
