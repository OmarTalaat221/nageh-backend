<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('teacherListMySubjects')) {
    function teacherListMySubjects($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized teacher token'];
        }

        $subjects = get_teacher_subjects_details($con, $teacherId);
        return [
            'status' => 'success',
            'data' => $subjects
        ];
    }
}

if (!function_exists('teacherListMyAcademicYears')) {
    function teacherListMyAcademicYears($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? $teacherData->id ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'Unauthorized teacher token'];
        }

        // Passing subject_id narrows the list to the grades this teacher is actually assigned
        // for THAT subject (used by the session-creation form); without it, every grade the
        // teacher touches across all subjects/courses is returned (used e.g. by certificates).
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        $academicYears = $subjectId > 0
            ? get_teacher_academic_years_for_subject($con, $teacherId, $subjectId)
            : get_teacher_academic_years_details($con, $teacherId);

        return [
            'status' => 'success',
            'data' => $academicYears
        ];
    }
}
