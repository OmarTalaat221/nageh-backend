<?php
require_once __DIR__ . '/../../helpers/language.php';
require_once __DIR__ . '/../../helpers/entities.php';
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/upload.php';

if (!function_exists('parse_int_list_param')) {
    function parse_int_list_param($input): array {
        if (is_array($input)) {
            return array_unique(array_filter(array_map('intval', $input)));
        }
        if (is_numeric($input)) {
            $val = (int)$input;
            return $val > 0 ? [$val] : [];
        }
        if (is_string($input) && trim($input) !== '') {
            $parts = explode(',', $input);
            return array_unique(array_filter(array_map('intval', $parts)));
        }
        return [];
    }
}

if (!function_exists('ensure_content_tables_schema')) {
    function ensure_content_tables_schema($con): void {
        return;
    }
}

if (!function_exists('sync_content_teachers')) {
    function sync_content_teachers($con, string $itemType, int $itemId, $teacherIds): void {
        ensure_content_tables_schema($con);
        if ($itemId <= 0) return;

        $tableMap = [
            'unit' => ['table' => 'unit_teachers', 'col' => 'unit_id'],
            'video' => ['table' => 'video_teachers', 'col' => 'video_id'],
            'book' => ['table' => 'book_teachers', 'col' => 'book_id'],
            'exam' => ['table' => 'exam_teachers', 'col' => 'exam_id'],
            'question' => ['table' => 'question_teachers', 'col' => 'question_id'],
            'certificate' => ['table' => 'certificate_teachers', 'col' => 'certificate_id'],
        ];

        $info = $tableMap[$itemType] ?? null;
        if (!$info) return;

        $table = $info['table'];
        $col = $info['col'];

        if (!is_array($teacherIds)) {
            if (is_numeric($teacherIds)) {
                $teacherIds = [(int)$teacherIds];
            } elseif (is_string($teacherIds) && str_contains($teacherIds, ',')) {
                $teacherIds = array_map('intval', explode(',', $teacherIds));
            } else {
                $teacherIds = [];
            }
        }

        $uniqueTeacherIds = array_unique(array_filter(array_map('intval', $teacherIds)));

        $delStmt = mysqli_prepare($con, "DELETE FROM {$table} WHERE {$col} = ?");
        if ($delStmt) {
            mysqli_stmt_bind_param($delStmt, "i", $itemId);
            mysqli_stmt_execute($delStmt);
        }

        $insertStmt = mysqli_prepare($con, "INSERT INTO {$table} ({$col}, teacher_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        foreach ($uniqueTeacherIds as $tId) {
            if ($tId > 0 && $insertStmt) {
                mysqli_stmt_bind_param($insertStmt, "ii", $itemId, $tId);
                mysqli_stmt_execute($insertStmt);
            }
        }
    }
}

if (!function_exists('get_content_teachers_details')) {
    function get_content_teachers_details($con, string $itemType, int $itemId): array {
        if ($itemId <= 0) return [];

        $tableMap = [
            'unit' => ['table' => 'unit_teachers', 'col' => 'unit_id'],
            'video' => ['table' => 'video_teachers', 'col' => 'video_id'],
            'book' => ['table' => 'book_teachers', 'col' => 'book_id'],
            'exam' => ['table' => 'exam_teachers', 'col' => 'exam_id'],
            'question' => ['table' => 'question_teachers', 'col' => 'question_id'],
            'certificate' => ['table' => 'certificate_teachers', 'col' => 'certificate_id'],
        ];

        $info = $tableMap[$itemType] ?? null;
        if (!$info) return [];

        $table = $info['table'];
        $col = $info['col'];

        $stmt = mysqli_prepare($con, "
            SELECT t.id, t.name, t.email, t.whatsapp, t.specialization, t.experience, t.image, t.rating
            FROM {$table} pt
            JOIN teachers t ON pt.teacher_id = t.id
            WHERE pt.{$col} = ? AND pt.is_active = 1 AND t.is_active = 1
            ORDER BY t.id ASC
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $itemId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $teachers = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['rating'] = (float)$row['rating'];
            $teachers[] = format_response_images($row);
        }
        return $teachers;
    }
}

if (!function_exists('validate_content_linkage')) {
    function validate_content_linkage($con, ?int $unitId, ?int &$courseId, ?int &$subjectId): ?array {
        if ($unitId !== null && $unitId > 0) {
            $stmt = mysqli_prepare($con, "SELECT id, course_id, subject_id FROM units WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $unitId);
                mysqli_stmt_execute($stmt);
                $unitRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if (!$unitRow) {
                    return ['status' => 'error', 'message' => 'Unit ID provided does not exist'];
                }

                $uCourseId = (int)$unitRow['course_id'];
                $uSubjectId = isset($unitRow['subject_id']) && $unitRow['subject_id'] ? (int)$unitRow['subject_id'] : null;

                if ($courseId !== null && $courseId > 0 && $courseId !== $uCourseId) {
                    return ['status' => 'error', 'message' => 'Course ID provided does not match the course of the selected unit'];
                }
                if ($subjectId !== null && $subjectId > 0 && $uSubjectId !== null && $subjectId !== $uSubjectId) {
                    return ['status' => 'error', 'message' => 'Subject ID provided does not match the subject of the selected unit'];
                }

                $courseId = $uCourseId;
                if ($uSubjectId) {
                    $subjectId = $uSubjectId;
                }
            }
        } else {
            if (($courseId === null || $courseId <= 0) && ($subjectId === null || $subjectId <= 0)) {
                return ['status' => 'error', 'message' => 'Item must be linked to at least a subject_id or a course_id when no unit_id is provided'];
            }
        }
        return null;
    }
}
