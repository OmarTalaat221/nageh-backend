<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../admin/courses/manage.php';

if (!function_exists('is_subject_assigned_to_teacher')) {
    function is_subject_assigned_to_teacher($con, int $teacherId, int $subjectId, ?int $academicYearId = null): bool {
        if ($teacherId <= 0 || $subjectId <= 0) {
            return false;
        }

        if ($academicYearId) {
            $stmt1 = mysqli_prepare($con, "SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND (academic_year_id IS NULL OR academic_year_id = ?) LIMIT 1");
            if ($stmt1) {
                mysqli_stmt_bind_param($stmt1, "iii", $teacherId, $subjectId, $academicYearId);
                mysqli_stmt_execute($stmt1);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt1))) {
                    return true;
                }
            }
        } else {
            $stmt1 = mysqli_prepare($con, "SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? LIMIT 1");
            if ($stmt1) {
                mysqli_stmt_bind_param($stmt1, "ii", $teacherId, $subjectId);
                mysqli_stmt_execute($stmt1);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt1))) {
                    return true;
                }
            }
        }

        $stmt2 = mysqli_prepare($con, "SELECT id FROM teachers WHERE id = ? AND subject_id = ? LIMIT 1");
        if ($stmt2) {
            mysqli_stmt_bind_param($stmt2, "ii", $teacherId, $subjectId);
            mysqli_stmt_execute($stmt2);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2))) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('teacherCreateCourse')) {
    function teacherCreateCourse($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized teacher token'];
        }

        $body = read_json_body();
        $subjectId = (int)($body['subject_id'] ?? 0);
        $nameAr = trim($body['name_ar'] ?? $body['name'] ?? '');
        $nameEn = trim($body['name_en'] ?? $nameAr);
        $descAr = trim($body['description_ar'] ?? $body['description'] ?? '');
        $descEn = trim($body['description_en'] ?? $descAr);
        $categoryId = isset($body['category_id']) ? (int)$body['category_id'] : null;
        $academicYearId = isset($body['academic_year_id']) ? (int)$body['academic_year_id'] : null;
        $price = isset($body['price']) ? (float)$body['price'] : 0.00;
        $isActive = isset($body['is_active']) ? (int)$body['is_active'] : 1;
        $slug = strtolower(trim($body['slug'] ?? ''));
        $type = isset($body['type']) && trim($body['type']) !== '' ? strtolower(trim($body['type'])) : 'recorded';

        if ($subjectId <= 0) {
            return ['status' => 'error', 'message' => 'subject_id is required'];
        }

        if (empty($nameAr)) {
            return ['status' => 'error', 'message' => 'Course name (name_ar) is required'];
        }

        if (!is_subject_assigned_to_teacher($con, $teacherId, $subjectId)) {
            return [
                'status' => 'error',
                'message' => 'يمكنك إنشاء كورسات في المواد المسندة إليك فقط (You can only create courses for subjects assigned to you)'
            ];
        }

        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($nameEn)) ?: ('course-' . uniqid());
        }

        $imageInput = $body['image'] ?? null;
        $fileImage = $_FILES['image'] ?? $_FILES['file'] ?? null;
        $image = process_image_input($imageInput, 'courses', $fileImage);

        $stmt = mysqli_prepare($con, "
            INSERT INTO courses (subject_id, category_id, academic_year_id, teacher_id, slug, type, price, image, is_active, created_by_type, created_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'teacher', ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare course insert: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "iiiissdsii", $subjectId, $categoryId, $academicYearId, $teacherId, $slug, $type, $price, $image, $isActive, $teacherId);

        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create course: ' . mysqli_error($con)];
        }

        $courseId = mysqli_insert_id($con);

        $tStmt = mysqli_prepare($con, "INSERT INTO courses_translations (course_id, language_id, name, description) VALUES (?, 1, ?, ?), (?, 2, ?, ?)");
        mysqli_stmt_bind_param($tStmt, "ississ", $courseId, $nameAr, $descAr, $courseId, $nameEn, $descEn);
        mysqli_stmt_execute($tStmt);

        sync_course_teachers($con, $courseId, [$teacherId]);

        $_GET['id'] = $courseId;
        return [
            'status' => 'success',
            'message' => 'Course created successfully by teacher',
            'data' => adminGetCourse($con)['data'] ?? null
        ];
    }
}

if (!function_exists('teacherListMyCourses')) {
    function teacherListMyCourses($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized teacher token'];
        }

        $_GET['teacher_id'] = $teacherId;
        return adminListCourses($con);
    }
}

if (!function_exists('teacherUpdateMyCourse')) {
    function teacherUpdateMyCourse($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($body['id'] ?? 0);

        if ($teacherId <= 0 || $id <= 0) {
            return ['status' => 'error', 'message' => 'Course ID and valid teacher token are required'];
        }

        $checkStmt = mysqli_prepare($con, "
            SELECT c.id FROM courses c
            LEFT JOIN course_teachers ct ON c.id = ct.course_id
            WHERE c.id = ? AND (c.teacher_id = ? OR (ct.teacher_id = ? AND ct.is_active = 1))
            LIMIT 1
        ");
        mysqli_stmt_bind_param($checkStmt, "iii", $id, $teacherId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'You can only update courses owned by or assigned to you'];
        }

        if (isset($body['subject_id'])) {
            $newSubjectId = (int)$body['subject_id'];
            if ($newSubjectId > 0 && !is_subject_assigned_to_teacher($con, $teacherId, $newSubjectId)) {
                return [
                    'status' => 'error',
                    'message' => 'يمكنك نقل الكورس للمواد المسندة إليك فقط (You can only change course to subjects assigned to you)'
                ];
            }
        }

        return adminUpdateCourse($con);
    }
}

if (!function_exists('teacherDeleteMyCourse')) {
    function teacherDeleteMyCourse($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);

        if ($teacherId <= 0 || $id <= 0) {
            return ['status' => 'error', 'message' => 'Course ID and valid teacher token are required'];
        }

        $checkStmt = mysqli_prepare($con, "
            SELECT c.id FROM courses c
            LEFT JOIN course_teachers ct ON c.id = ct.course_id
            WHERE c.id = ? AND (c.teacher_id = ? OR (ct.teacher_id = ? AND ct.is_active = 1))
            LIMIT 1
        ");
        mysqli_stmt_bind_param($checkStmt, "iii", $id, $teacherId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => 'You can only delete courses owned by or assigned to you'];
        }

        return adminDeleteCourse($con);
    }
}
