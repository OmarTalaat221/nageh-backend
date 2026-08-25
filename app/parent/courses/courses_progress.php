<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('get_student_courses_progress')) {
    function get_student_courses_progress($con, int $studentId) {

        if ($studentId <= 0) {
            return [
                'summary' => [
                    'total_courses'      => 0,
                    'completed_courses'  => 0,
                    'in_progress_courses'=> 0,
                    'not_started_courses'=> 0,
                    'overall_progress'   => 0,
                    'total_watch_hours'  => 0,
                ],
                'courses' => []
            ];
        }

        $sql = "SELECT 
                    v.id                     AS video_id,
                    v.unit_id,
                    v.code                   AS video_code,
                    v.duration_seconds       AS video_duration,
                    v.publish_status         AS video_publish_status,
                    v.is_active              AS video_is_active,

                    u.id                     AS unit_id_ref,
                    u.course_id              AS course_id,
                    u.subject_id             AS unit_subject_id,
                    u.code                   AS unit_code,
                    u.duration               AS unit_duration,
                    u.duration_unit          AS unit_duration_unit,
                    u.lessons_count          AS unit_lessons_count,

                    c.id                     AS course_id_ref,
                    c.slug                   AS course_slug,
                    c.type                   AS course_type,
                    c.price                  AS course_price,
                    c.image                  AS course_image,
                    c.lessons_count          AS course_lessons_count,
                    c.category_id            AS course_category_id,
                    c.subject_id             AS course_subject_id,
                    c.teacher_id             AS course_teacher_id,
                    c.what_you_will_learn    AS course_what_you_will_learn,
                    c.video_preview_url      AS course_video_preview_url,

                    svp.id                   AS progress_id,
                    svp.is_completed         AS video_is_completed,
                    svp.watched_seconds      AS video_watched_seconds,
                    svp.total_seconds        AS video_total_seconds,
                    svp.last_position_seconds AS video_last_position,
                    svp.completed_at         AS video_completed_at,
                    svp.updated_at           AS progress_updated_at

                FROM videos v
                INNER JOIN units   u ON u.id = v.unit_id
                INNER JOIN courses c ON c.id = u.course_id
                LEFT JOIN student_video_progress svp 
                       ON svp.video_id = v.id AND svp.student_id = ?
                WHERE v.is_active = 1
                  AND c.is_active = 1
                ORDER BY c.id ASC, u.id ASC, v.id ASC";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return [
                'summary' => [],
                'courses' => [],
                'error'   => mysqli_error($con)
            ];
        }

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $coursesMap        = [];
        $totalWatchSeconds = 0;

        while ($row = mysqli_fetch_assoc($result)) {

            $courseId  = (int)$row['course_id'];
            $unitId    = (int)$row['unit_id'];
            $videoId   = (int)$row['video_id'];

            $videoDuration    = (int)($row['video_duration'] ?? 0);
            $watchedSeconds   = (int)($row['video_watched_seconds'] ?? 0);
            $isVideoCompleted = (int)($row['video_is_completed'] ?? 0) === 1;
            $lastPosition     = (int)($row['video_last_position'] ?? 0);

            $totalWatchSeconds += $watchedSeconds;

            if (!isset($coursesMap[$courseId])) {
                $coursesMap[$courseId] = [
                    'id'                  => $courseId,
                    'slug'                => $row['course_slug'],
                    'type'                => $row['course_type'],
                    'price'               => $row['course_price'] !== null ? (float)$row['course_price'] : 0,
                    'image'               => $row['course_image'],
                    'category_id'         => $row['course_category_id'] ? (int)$row['course_category_id'] : null,
                    'subject_id'          => $row['course_subject_id']  ? (int)$row['course_subject_id']  : null,
                    'teacher_id'          => $row['course_teacher_id']  ? (int)$row['course_teacher_id']  : null,
                    'video_preview_url'   => $row['course_video_preview_url'],
                    'what_you_will_learn' => $row['course_what_you_will_learn'],
                    'lessons_count'       => (int)($row['course_lessons_count'] ?? 0),
                    'total_videos'        => 0,
                    'completed_videos'    => 0,
                    'total_duration'      => 0,
                    'watched_duration'    => 0,
                    'progress_percent'    => 0,
                    'status'              => 'not_started',
                    'status_label'        => 'لم يبدأ',
                    'last_activity'       => null,
                    'units'               => [],
                ];
            }

            if (!isset($coursesMap[$courseId]['units'][$unitId])) {
                $coursesMap[$courseId]['units'][$unitId] = [
                    'id'               => $unitId,
                    'code'             => $row['unit_code'],
                    'subject_id'       => $row['unit_subject_id'] ? (int)$row['unit_subject_id'] : null,
                    'duration'         => $row['unit_duration']   ? (int)$row['unit_duration']   : null,
                    'duration_unit'    => $row['unit_duration_unit'],
                    'lessons_count'    => (int)($row['unit_lessons_count'] ?? 0),
                    'total_videos'     => 0,
                    'completed_videos' => 0,
                    'total_duration'   => 0,
                    'watched_duration' => 0,
                    'progress_percent' => 0,
                    'videos'           => [],
                ];
            }

            $videoProgress = $videoDuration > 0
                ? round(($watchedSeconds / $videoDuration) * 100, 2)
                : 0;

            $coursesMap[$courseId]['units'][$unitId]['videos'][] = [
                'id'                    => $videoId,
                'code'                  => $row['video_code'],
                'duration_seconds'      => $videoDuration,
                'watched_seconds'       => $watchedSeconds,
                'last_position_seconds' => $lastPosition,
                'is_completed'          => $isVideoCompleted,
                'progress_percent'      => $videoProgress,
                'completed_at'          => $row['video_completed_at'],
                'publish_status'        => $row['video_publish_status'],
            ];

            $coursesMap[$courseId]['units'][$unitId]['total_videos']++;
            $coursesMap[$courseId]['units'][$unitId]['total_duration']   += $videoDuration;
            $coursesMap[$courseId]['units'][$unitId]['watched_duration'] += $watchedSeconds;
            if ($isVideoCompleted) {
                $coursesMap[$courseId]['units'][$unitId]['completed_videos']++;
            }

            $coursesMap[$courseId]['total_videos']++;
            $coursesMap[$courseId]['total_duration']   += $videoDuration;
            $coursesMap[$courseId]['watched_duration'] += $watchedSeconds;
            if ($isVideoCompleted) {
                $coursesMap[$courseId]['completed_videos']++;
            }

            if ($row['progress_updated_at']) {
                if (
                    $coursesMap[$courseId]['last_activity'] === null ||
                    strtotime($row['progress_updated_at']) > strtotime($coursesMap[$courseId]['last_activity'])
                ) {
                    $coursesMap[$courseId]['last_activity'] = $row['progress_updated_at'];
                }
            }
        }

        mysqli_stmt_close($stmt);

        $completedCourses  = 0;
        $inProgressCourses = 0;
        $notStartedCourses = 0;

        foreach ($coursesMap as $cid => &$course) {

            foreach ($course['units'] as &$unit) {
                $unit['progress_percent'] = $unit['total_duration'] > 0
                    ? round(($unit['watched_duration'] / $unit['total_duration']) * 100, 2)
                    : 0;
            }
            unset($unit);

            $course['progress_percent'] = $course['total_duration'] > 0
                ? round(($course['watched_duration'] / $course['total_duration']) * 100, 2)
                : 0;

            if ($course['progress_percent'] >= 100) {
                $course['status']       = 'completed';
                $course['status_label'] = 'مكتمل';
                $completedCourses++;
            } elseif ($course['progress_percent'] > 0) {
                $course['status']       = 'in_progress';
                $course['status_label'] = 'قيد التقدم';
                $inProgressCourses++;
            } else {
                $course['status']       = 'not_started';
                $course['status_label'] = 'لم يبدأ';
                $notStartedCourses++;
            }

            $course['units'] = array_values($course['units']);
        }
        unset($course);

        $courses      = array_values($coursesMap);
        $totalCourses = count($courses);

        $overallProgress = 0;
        if ($totalCourses > 0) {
            $sumProgress = array_sum(array_column($courses, 'progress_percent'));
            $overallProgress = round($sumProgress / $totalCourses, 2);
        }

        $totalWatchHours = round($totalWatchSeconds / 3600, 2);

        return [
            'summary' => [
                'total_courses'        => $totalCourses,
                'completed_courses'    => $completedCourses,
                'in_progress_courses'  => $inProgressCourses,
                'not_started_courses'  => $notStartedCourses,
                'overall_progress'     => $overallProgress,
                'total_watch_seconds'  => $totalWatchSeconds,
                'total_watch_hours'    => $totalWatchHours,
            ],
            'courses' => format_response_images($courses),
        ];
    }
}