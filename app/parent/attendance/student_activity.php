<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('get_student_attendance')) {
    function get_student_attendance($con, int $studentId) {

        if ($studentId <= 0) {
            return [
                'summary' => [
                    'total_courses'           => 0,
                    'total_sessions'          => 0,
                    'present_count'           => 0,
                    'absent_count'            => 0,
                    'late_count'              => 0,
                    'excused_count'           => 0,
                    'overall_attendance_rate' => 0,
                ],
                'courses' => []
            ];
        }

        $sql = "SELECT 
                    sa.id                       AS attendance_id,
                    sa.session_id               AS session_id,
                    sa.student_id               AS student_id,
                    sa.session_date             AS session_date,
                    sa.status                   AS attendance_status,
                    sa.marked_by_teacher_id     AS marked_by_teacher_id,
                    sa.marked_at                AS marked_at,

                    cs.code                     AS session_code,
                    cs.day_of_week              AS session_day_of_week,
                    cs.start_time               AS session_start_time,
                    cs.end_time                 AS session_end_time,
                    cs.room                     AS session_room,
                    cs.is_online                AS session_is_online,
                    cs.meeting_link             AS session_meeting_link,
                    cs.grade                    AS session_grade,
                    cs.is_active                AS session_is_active,
                    cs.course_id                AS session_course_id,
                    cs.subject_id               AS session_subject_id,

                    c.id                        AS course_id,
                    c.slug                      AS course_slug,
                    c.type                      AS course_type,
                    c.image                     AS course_image,
                    c.lessons_count             AS course_lessons_count,
                    c.category_id               AS course_category_id,
                    c.subject_id                AS course_subject_id,
                    c.teacher_id                AS course_teacher_id,

                    t.id                        AS teacher_id,
                    t.name                      AS teacher_name,
                    t.image                     AS teacher_image

                FROM session_attendance sa
                INNER JOIN class_sessions cs ON cs.id = sa.session_id
                INNER JOIN courses        c  ON c.id  = cs.course_id
                LEFT  JOIN teachers       t  ON t.id  = sa.marked_by_teacher_id
                WHERE sa.student_id = ?
                  AND cs.is_active  = 1
                  AND c.is_active   = 1
                ORDER BY c.id ASC, sa.session_date ASC, sa.session_id ASC";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return [
                'summary'  => [],
                'courses'  => [],
                'error'    => mysqli_error($con)
            ];
        }

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $coursesMap   = [];
        $totalPresent = 0;
        $totalAbsent  = 0;
        $totalLate    = 0;
        $totalExcused = 0;

        while ($row = mysqli_fetch_assoc($result)) {

            $courseId     = (int)$row['course_id'];
            $sessionId    = (int)$row['session_id'];
            $attendanceId = (int)$row['attendance_id'];
            $status       = $row['attendance_status'];

            // ---- global counters ----
            if ($status === 'present') {
                $totalPresent++;
            } elseif ($status === 'absent') {
                $totalAbsent++;
            } elseif ($status === 'late') {
                $totalLate++;
            } elseif ($status === 'excused') {
                $totalExcused++;
            }

            // ---- status label (Arabic) ----
            if ($status === 'present') {
                $statusLabel = 'حاضر';
            } elseif ($status === 'absent') {
                $statusLabel = 'غائب';
            } elseif ($status === 'late') {
                $statusLabel = 'متأخر';
            } elseif ($status === 'excused') {
                $statusLabel = 'معذور';
            } else {
                $statusLabel = 'غير محدد';
            }

            // ---- build course node ----
            if (!isset($coursesMap[$courseId])) {
                $coursesMap[$courseId] = [
                    'id'              => $courseId,
                    'slug'            => $row['course_slug'],
                    'type'            => $row['course_type'],
                    'image'           => $row['course_image'],
                    'category_id'     => $row['course_category_id'] ? (int)$row['course_category_id'] : null,
                    'subject_id'      => $row['course_subject_id']  ? (int)$row['course_subject_id']  : null,
                    'teacher_id'      => $row['course_teacher_id']  ? (int)$row['course_teacher_id']  : null,
                    'lessons_count'   => (int)($row['course_lessons_count'] ?? 0),
                    'total_sessions'  => 0,
                    'present_count'   => 0,
                    'absent_count'    => 0,
                    'late_count'      => 0,
                    'excused_count'   => 0,
                    'attendance_rate' => 0,
                    'last_activity'   => null,
                    'sessions'        => [],
                ];
            }

            // ---- session record ----
            $sessionRecord = [
                'attendance_id'        => $attendanceId,
                'session_id'           => $sessionId,
                'session_code'         => $row['session_code'],
                'session_day_of_week'  => $row['session_day_of_week'],
                'session_date'         => $row['session_date'],
                'session_start_time'   => $row['session_start_time'],
                'session_end_time'     => $row['session_end_time'],
                'session_room'         => $row['session_room'],
                'session_is_online'    => (int)$row['session_is_online'] === 1,
                'session_meeting_link' => $row['session_meeting_link'],
                'session_grade'        => $row['session_grade'],
                'status'               => $status,
                'status_label'         => $statusLabel,
                'marked_by_teacher_id' => $row['marked_by_teacher_id'] ? (int)$row['marked_by_teacher_id'] : null,
                'teacher_name'         => $row['teacher_name'],
                'teacher_image'        => $row['teacher_image'],
                'marked_at'            => $row['marked_at'],
            ];

            $coursesMap[$courseId]['sessions'][] = $sessionRecord;

            // ---- course counters ----
            $coursesMap[$courseId]['total_sessions']++;
            if ($status === 'present') {
                $coursesMap[$courseId]['present_count']++;
            } elseif ($status === 'absent') {
                $coursesMap[$courseId]['absent_count']++;
            } elseif ($status === 'late') {
                $coursesMap[$courseId]['late_count']++;
            } elseif ($status === 'excused') {
                $coursesMap[$courseId]['excused_count']++;
            }

            // ---- last activity ----
            if ($row['marked_at']) {
                if (
                    $coursesMap[$courseId]['last_activity'] === null ||
                    strtotime($row['marked_at']) > strtotime($coursesMap[$courseId]['last_activity'])
                ) {
                    $coursesMap[$courseId]['last_activity'] = $row['marked_at'];
                }
            }
        }

        mysqli_stmt_close($stmt);

        // ---- post-process: rates & flatten ----
        $totalSessionsAll = 0;

        foreach ($coursesMap as $cid => &$course) {

            // course attendance rate (present + late = attended)
            $course['attendance_rate'] = $course['total_sessions'] > 0
                ? round(($course['present_count'] + $course['late_count']) / $course['total_sessions'] * 100, 2)
                : 0;

            $totalSessionsAll += $course['total_sessions'];
        }
        unset($course);

        $courses      = array_values($coursesMap);
        $totalCourses = count($courses);


        $overallAttendanceRate = 0;
        if ($totalCourses > 0) {
            $sumRates = array_sum(array_column($courses, 'attendance_rate'));
            $overallAttendanceRate = round($sumRates / $totalCourses, 2);
        }

        return [
            'summary' => [
                'total_courses'           => $totalCourses,
                'total_sessions'          => $totalSessionsAll,
                'present_count'           => $totalPresent,
                'absent_count'            => $totalAbsent,
                'late_count'              => $totalLate,
                'excused_count'           => $totalExcused,
                'overall_attendance_rate' => $overallAttendanceRate,
            ],
            'courses' => format_response_images($courses),
        ];
    }
}