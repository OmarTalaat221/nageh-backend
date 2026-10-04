<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../../helpers/video_source.php';
require_once __DIR__ . '/../../../../helpers/pricing.php';
require_once __DIR__ . '/../../../content/helpers.php';
require_once __DIR__ . '/../subscriptions/manage.php';

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

if (!function_exists('studentGetAllCoursesWithOwn')) {
    function studentGetAllCoursesWithOwn($con, $studentData = null) {
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

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
        $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;

        $body = read_json_body();
        $typeFilter = strtolower(trim($_GET['type'] ?? $_GET['course_type'] ?? $_GET['type_id'] ?? $body['type'] ?? $body['course_type'] ?? ''));

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $whereConditions = ["c.is_active = 1"];
        if ($subjectId > 0) $whereConditions[] = "c.subject_id = {$subjectId}";
        if ($teacherId > 0) $whereConditions[] = "(c.teacher_id = {$teacherId} OR (c.created_by_type = 'teacher' AND c.created_by_id = {$teacherId}))";
        if ($academicYearId > 0) $whereConditions[] = "c.academic_year_id = {$academicYearId}";
        if (!empty($typeFilter)) {
            $tp = mysqli_real_escape_string($con, $typeFilter);
            $whereConditions[] = "c.type = '{$tp}'";
        }
        $kindFilter = strtolower(trim($_GET['kind'] ?? $body['kind'] ?? ''));
        if (in_array($kindFilter, ['normal', 'package'], true)) {
            $whereConditions[] = "c.kind = '{$kindFilter}'";
        }
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereConditions[] = "(c.slug LIKE '{$sp}' OR ct.name LIKE '{$sp}' OR ct.description LIKE '{$sp}')";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        $countSql = "SELECT COUNT(DISTINCT c.id) AS total FROM courses c LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId} {$whereClause}";
        $countRes = mysqli_query($con, $countSql);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT c.id, c.subject_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.kind, c.price, c.image, c.is_active, c.created_at,
                   COALESCE(ct.name, c.slug) AS name, ct.description
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            {$whereClause}
            ORDER BY c.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        $courses = [];

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $cId = (int)$row['id'];
                $row['id'] = $cId;
                $row['type'] = $row['type'] ?? 'recorded';
                $row['kind'] = $row['kind'] ?? 'normal';
                $row['price'] = (float)$row['price'];
                $isOwn = in_array($cId, $accessibleCourseIds);
                $row['own'] = $isOwn;
                $row['is_subscribed'] = $isOwn;

                $stats = get_student_course_progress_stats($con, $isOwn ? $stId : 0, $cId);
                $row['total_lessons_count'] = $stats['total_lessons_count'];
                $row['units_count'] = $stats['units_count'];
                $row['completed_lessons_count'] = $stats['completed_lessons_count'];
                $row['progress_percentage'] = $isOwn ? $stats['progress_percentage'] : 0.00;

                $courses[] = format_response_images($row);
            }
        }

        // The price a student in this country actually pays, in that country's currency.
        // One query for the whole page, and every row with no country price of its own keeps
        // the base price it already had.
        pricing_decorate_rows($con, $courses, 'course', $stId);

        return build_admin_paginated_response($courses, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('studentGetMySubscribedCoursesProgress')) {
    function studentGetMySubscribedCoursesProgress($con, $studentData = null) {
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
        $activeCourseIds = $accessible['course_ids'];
        // Same rule as studentGetMySubscribedCourses: a course whose only grant is a frozen
        // subscription must still be listed (flagged), not silently dropped as if never bought.
        require_once __DIR__ . '/../../../subscriptions/student_subscriptions.php';
        $frozenCourseIds = get_student_frozen_course_ids($con, $stId);
        $courseIds = array_values(array_unique(array_merge($activeCourseIds, $frozenCourseIds)));
        if (empty($courseIds)) {
            return build_admin_paginated_response([], 1, 10, 0, ['total_count' => 0]);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $body = read_json_body();
        $typeFilter = strtolower(trim($_GET['type'] ?? $_GET['course_type'] ?? $_GET['type_id'] ?? $body['type'] ?? $body['course_type'] ?? ''));

        $cListStr = implode(',', array_map('intval', $courseIds));
        $whereSearch = "";
        if (!empty($search)) {
            $sp = '%' . mysqli_real_escape_string($con, $search) . '%';
            $whereSearch = "AND (c.slug LIKE '{$sp}' OR ct.name LIKE '{$sp}')";
        }
        if (!empty($typeFilter)) {
            $tp = mysqli_real_escape_string($con, $typeFilter);
            $whereSearch .= " AND c.type = '{$tp}'";
        }
        $kindFilter = strtolower(trim($_GET['kind'] ?? $body['kind'] ?? ''));
        if (in_array($kindFilter, ['normal', 'package'], true)) {
            $whereSearch .= " AND c.kind = '{$kindFilter}'";
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $countSql = "SELECT COUNT(DISTINCT c.id) AS total FROM courses c LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId} WHERE c.id IN ({$cListStr}) AND c.is_active = 1 {$whereSearch}";
        $countRes = mysqli_query($con, $countSql);
        $totalRecords = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);

        $sql = "
            SELECT c.id, c.subject_id, c.academic_year_id, c.teacher_id, c.slug, c.type, c.kind, c.price, c.image, c.is_active, c.created_at,
                   COALESCE(ct.name, c.slug) AS name, ct.description
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            WHERE c.id IN ({$cListStr}) AND c.is_active = 1 {$whereSearch}
            ORDER BY c.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = mysqli_query($con, $sql);
        $courses = [];

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $cId = (int)$row['id'];
                $row['id'] = $cId;
                $row['type'] = $row['type'] ?? 'recorded';
                $row['kind'] = $row['kind'] ?? 'normal';
                $row['price'] = (float)$row['price'];
                $isFrozen = !in_array($cId, $activeCourseIds, true) && in_array($cId, $frozenCourseIds, true);
                $row['own'] = !$isFrozen;
                $row['is_subscribed'] = !$isFrozen;
                $row['is_frozen'] = $isFrozen;
                $row['freeze_reason'] = $isFrozen ? trans('student_subscription_frozen_reason') : null;
                $row['teachers'] = get_course_teachers_details($con, $cId);

                $stats = get_student_course_progress_stats($con, $stId, $cId);
                $row['total_lessons_count'] = $stats['total_lessons_count'];
                $row['units_count'] = $stats['units_count'];
                $row['completed_lessons_count'] = $stats['completed_lessons_count'];
                $row['progress_percentage'] = $stats['progress_percentage'];

                $courses[] = format_response_images($row);
            }
        }

        pricing_decorate_rows($con, $courses, 'course', $stId);

        return build_admin_paginated_response($courses, $page, $limit, $totalRecords, ['total_count' => $totalRecords]);
    }
}

if (!function_exists('studentGetCourseDetails')) {
    function studentGetCourseDetails($con, $studentData = null) {
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
        $courseId = (int)($_GET['id'] ?? $_GET['course_id'] ?? $body['id'] ?? $body['course_id'] ?? 0);
        $slug = trim($_GET['slug'] ?? $body['slug'] ?? '');

        if ($courseId <= 0 && empty($slug)) {
            return ['status' => 'error', 'message' => 'Course ID or slug is required'];
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $whereCond = $courseId > 0 ? "c.id = {$courseId}" : "c.slug = '" . mysqli_real_escape_string($con, $slug) . "'";

        $sql = "
            SELECT c.id, c.subject_id, c.academic_year_id, c.teacher_id, c.slug, c.kind, c.price, c.image, c.video_preview_url, c.is_active, c.created_at, c.what_you_will_learn AS c_learn,
                   COALESCE(ct.name, c.slug) AS name, ct.description, ct.what_you_will_learn AS ct_learn,
                   t.id AS t_id, t.name AS teacher_name, t.specialization AS teacher_specialization, t.image AS teacher_image, t.rating AS teacher_rating, t.experience AS teacher_experience,
                   sub_t.name AS subject_name, ay_t.name AS academic_year_name
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN subjects_translations sub_t ON s.id = sub_t.subject_id AND sub_t.language_id = {$langId}
            LEFT JOIN academic_years ay ON c.academic_year_id = ay.id
            LEFT JOIN academic_years_translations ay_t ON ay.id = ay_t.academic_year_id AND ay_t.language_id = {$langId}
            WHERE {$whereCond} LIMIT 1
        ";

        $res = mysqli_query($con, $sql);
        $cRow = mysqli_fetch_assoc($res);
        if (!$cRow) {
            return ['status' => 'error', 'message' => 'Course not found'];
        }

        $cId = (int)$cRow['id'];
        $accessible = get_student_accessible_entities($con, $stId);
        $isSubscribed = in_array($cId, $accessible['course_ids']);
        $isFrozen = !$isSubscribed && $stId > 0 && function_exists('get_student_frozen_course_ids')
            && in_array($cId, get_student_frozen_course_ids($con, $stId));

        // The figure below is what the student is quoted and what the payment flow charges,
        // so it is resolved once here rather than read straight off the course row.
        $pricing = pricing_for_item($con, 'course', $cId, (float)$cRow['price'], $stId);
        $price = $pricing['price'];
        $originalPrice = $price;
        $discountPct = 0;
        $savingsAmount = 0.00;

        $uRes = mysqli_query($con, "SELECT u.id, u.code, u.is_active FROM units u WHERE u.course_id = {$cId} AND u.is_active = 1 ORDER BY u.id ASC");

        $curriculum = [];
        $totalLessons = 0;
        $totalSecondsSum = 0;
        $completedLessons = 0;
        $hasVideoProgressTable = studentTableExists($con, 'student_video_progress');

        if ($uRes) {
            while ($uRow = mysqli_fetch_assoc($uRes)) {
                $unitId = (int)$uRow['id'];

                $uName = '';
                $uDesc = '';
                $utRes = @mysqli_query($con, "SELECT * FROM units_translations WHERE unit_id = {$unitId} AND language_id = {$langId} LIMIT 1");
                if ($utRes && $utRow = mysqli_fetch_assoc($utRes)) {
                    $uName = $utRow['name'] ?? $utRow['title'] ?? '';
                    $uDesc = $utRow['description'] ?? '';
                }
                if (empty($uName)) {
                    $uName = $uRow['code'] ?? ('الوحدة ' . $unitId);
                }

                $vRes = mysqli_query($con, "SELECT * FROM videos WHERE unit_id = {$unitId} AND is_active = 1 ORDER BY id ASC");
                $lessons = [];
                $lessonIndex = 1;

                if ($vRes) {
                    while ($vRow = mysqli_fetch_assoc($vRes)) {
                        $vId = (int)$vRow['id'];
                        $vCode = !empty($vRow['code']) ? $vRow['code'] : ('video-' . $vId);
                        $durSec = (int)($vRow['duration_seconds'] ?? $vRow['duration'] ?? 0);
                        $pubStatus = $vRow['publish_status'] ?? 'published';
                        $totalSecondsSum += $durSec;
                        $totalLessons++;

                        $isFreePreview = $lessonIndex === 1;
                        $isLocked = !$isSubscribed && !$isFreePreview;
                        $isLessonCompleted = false;

                        if ($stId > 0 && $isSubscribed && $hasVideoProgressTable) {
                            $progCheck = mysqli_query($con, "SELECT is_completed FROM student_video_progress WHERE student_id = {$stId} AND video_id = {$vId} LIMIT 1");
                            if ($progCheck && $pRow = mysqli_fetch_assoc($progCheck)) {
                                $isLessonCompleted = (int)$pRow['is_completed'] === 1;
                                if ($isLessonCompleted) {
                                    $completedLessons++;
                                }
                            }
                        }

                        $minutes = floor($durSec / 60);
                        $seconds = $durSec % 60;
                        $formattedDuration = sprintf('%02d:%02d', $minutes, $seconds);

                        $vTitle = '';
                        $vDesc = '';
                        $vtRes = @mysqli_query($con, "SELECT * FROM videos_translations WHERE video_id = {$vId} AND language_id = {$langId} LIMIT 1");
                        if ($vtRes && $vtRow = mysqli_fetch_assoc($vtRes)) {
                            $vTitle = $vtRow['title'] ?? $vtRow['name'] ?? '';
                            $vDesc = $vtRow['description'] ?? '';
                        }
                        if (empty($vTitle)) {
                            $vTitle = $vCode;
                        }

                        $lessons[] = [
                            'id' => $vId,
                            'code' => $vCode,
                            'title' => $vTitle,
                            'name' => $vTitle,
                            'description' => $vDesc,
                            'duration' => $formattedDuration,
                            'duration_seconds' => $durSec,
                            'publish_status' => $pubStatus,
                            'is_free_preview' => $isFreePreview,
                            'is_locked' => $isLocked,
                            'is_completed' => $isLessonCompleted
                        ];

                        $lessonIndex++;
                    }
                }

                $curriculum[] = [
                    'id' => $unitId,
                    'name' => $uName,
                    'description' => $uDesc,
                    'lessons_count' => count($lessons),
                    'videos_count' => count($lessons),
                    'lessons' => $lessons,
                    'videos' => $lessons
                ];
            }
        }

        $totalHours = round($totalSecondsSum / 3600, 1);
        $courseStats = get_student_course_progress_stats($con, $isSubscribed ? $stId : 0, $cId);
        $progressPct = $isSubscribed ? $courseStats['progress_percentage'] : 0.00;

        $tId = (int)($cRow['t_id'] ?? $cRow['teacher_id'] ?? 0);
        $studentsCount = 0;
        if ($tId > 0) {
            $tCountRes = mysqli_query($con, "SELECT COUNT(DISTINCT student_id) AS total FROM student_subscriptions ss JOIN student_subscription_teachers sst ON ss.id = sst.student_subscription_id WHERE sst.teacher_id = {$tId}");
            if ($tCountRes && $tRowC = mysqli_fetch_assoc($tCountRes)) {
                $studentsCount = (int)($tRowC['total'] ?? 0);
            }
        }

        $avgRating = 0.0;
        $totalReviews = 0;
        $starsDist = ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0];
        $reviews = [];

        $rStatRes = mysqli_query($con, "SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS avg_rate FROM course_ratings WHERE course_id = {$cId} AND is_approved = 1");
        if ($rStatRes && $rStatRow = mysqli_fetch_assoc($rStatRes)) {
            $totalReviews = (int)($rStatRow['total'] ?? 0);
            $avgRating = round((float)($rStatRow['avg_rate'] ?? 0), 1);
        }

        if ($totalReviews > 0) {
            $distRes = mysqli_query($con, "SELECT rating, COUNT(*) AS cnt FROM course_ratings WHERE course_id = {$cId} AND is_approved = 1 GROUP BY rating");
            if ($distRes) {
                while ($dRow = mysqli_fetch_assoc($distRes)) {
                    $rKey = (string)$dRow['rating'];
                    if (isset($starsDist[$rKey])) {
                        $starsDist[$rKey] = (int)$dRow['cnt'];
                    }
                }
            }

            $revRes = mysqli_query($con, "
                SELECT cr.id, cr.rating, cr.comment, cr.created_at, s.name AS student_name, s.image AS student_image
                FROM course_ratings cr
                LEFT JOIN students s ON cr.student_id = s.id
                WHERE cr.course_id = {$cId} AND cr.is_approved = 1
                ORDER BY cr.id DESC LIMIT 20
            ");
            if ($revRes) {
                while ($revRow = mysqli_fetch_assoc($revRes)) {
                    $reviews[] = format_response_images([
                        'id' => (int)$revRow['id'],
                        'student_name' => $revRow['student_name'] ?? '',
                        'student_image' => $revRow['student_image'] ?? null,
                        'rating' => (int)$revRow['rating'],
                        'comment' => $revRow['comment'] ?? '',
                        'date' => $revRow['created_at']
                    ]);
                }
            }
        }

        $ratingsSummary = [
            'average_rating' => $avgRating,
            'total_reviews' => $totalReviews,
            'stars_distribution' => $starsDist
        ];

        $rawLearn = $cRow['ct_learn'] ?: $cRow['c_learn'];
        $whatYouWillLearn = [];
        if (!empty($rawLearn)) {
            if (str_starts_with(trim($rawLearn), '[')) {
                $decoded = json_decode($rawLearn, true);
                if (is_array($decoded)) $whatYouWillLearn = array_values($decoded);
            }
            if (empty($whatYouWillLearn)) {
                $lines = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $rawLearn))));
                if (!empty($lines)) $whatYouWillLearn = array_values($lines);
            }
        }

        $categories = array_filter([
            $cRow['subject_name'] ?? null,
            $cRow['academic_year_name'] ?? null
        ]);

        $teacherData = null;
        if ($tId > 0) {
            $teacherRating = 0.0;
            $teacherRatingRes = mysqli_query($con, "SELECT COALESCE(AVG(rating), 0) AS avg_rate FROM teacher_ratings WHERE teacher_id = {$tId} AND is_approved = 1");
            if ($teacherRatingRes && $teacherRatingRow = mysqli_fetch_assoc($teacherRatingRes)) {
                $teacherRating = round((float)($teacherRatingRow['avg_rate'] ?? 0), 1);
            }

            $teacherData = format_response_images([
                'id' => $tId,
                'name' => $cRow['teacher_name'] ?? '',
                'specialization' => $cRow['teacher_specialization'] ?? '',
                'image' => $cRow['teacher_image'] ?? null,
                'rating' => $teacherRating,
                'experience' => $cRow['teacher_experience'] ?? '',
                'students_count' => $studentsCount,
                'bio' => $cRow['teacher_specialization'] ?? ''
            ]);
        }

        $previewVideoUrl = null;
        if (!empty($cRow['video_preview_url'])) {
            $previewVideoUrl = format_response_images(['url' => $cRow['video_preview_url']])['url'];
        }

        $responseData = [
            'id' => $cId,
            'slug' => $cRow['slug'],
            'kind' => $cRow['kind'] ?? 'normal',
            'name' => $cRow['name'],
            'description' => $cRow['description'] ?? '',
            'price' => $price,
            'original_price' => $originalPrice,
            'discount_percentage' => $discountPct,
            'savings_amount' => $savingsAmount,
            // What `price` above is denominated in, and what the same course costs in the base
            // currency. Sent on every response rather than only when a country price exists, so a
            // client never has to guess which currency it is holding.
            'base_price' => $pricing['base_price'],
            'currency' => $pricing['currency'],
            'price_formatted' => $pricing['price_formatted'],
            'is_country_price' => $pricing['is_country_price'],
            'image' => format_response_images(['img' => $cRow['image']])['img'],
            'video_preview_url' => $previewVideoUrl,
            'rating' => $avgRating,
            'reviews_count' => $totalReviews,
            'students_count' => $studentsCount,
            'lessons_count' => $totalLessons,
            // The curriculum is a unit per entry, so this is the number of units - sent because a
            // course can have units with no videos in them yet, and "0 lessons" on its own tells
            // a student nothing about a course that plainly has content being built.
            'units_count' => count($curriculum),
            'total_duration_hours' => $totalHours,
            'has_certificate' => true,
            'access_type' => 'وصول كامل',
            'own' => $isSubscribed,
            'is_subscribed' => $isSubscribed,
            'is_frozen' => $isFrozen,
            'freeze_reason' => $isFrozen ? trans('student_subscription_frozen_reason') : null,
            'progress_percentage' => $progressPct,
            'completed_lessons_count' => $completedLessons,
            'teacher' => $teacherData,
            'overview' => [
                'what_you_will_learn' => $whatYouWillLearn,
                'about_course' => $cRow['description'] ?? '',
                'categories' => array_values($categories)
            ],
            'curriculum' => $curriculum,
            'ratings_summary' => $ratingsSummary,
            'reviews' => $reviews
        ];

        return [
            'status' => 'success',
            'data' => format_response_images($responseData)
        ];
    }
}

if (!function_exists('studentRateCourse')) {
    function studentRateCourse($con, $studentData = null) {
        $body = read_json_body();
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($body['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

        $courseId = (int)($body['course_id'] ?? 0);
        $rating = (int)($body['rating'] ?? 0);
        $comment = trim($body['comment'] ?? '');

        if ($courseId <= 0) {
            return ['status' => 'error', 'message' => 'course_id is required'];
        }
        if ($rating < 1 || $rating > 5) {
            return ['status' => 'error', 'message' => 'rating must be an integer between 1 and 5'];
        }

        $stmt = mysqli_prepare($con, "
            INSERT INTO course_ratings (student_id, course_id, rating, comment, is_approved)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                comment = VALUES(comment),
                is_approved = 1
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare course rating statement'];
        }

        mysqli_stmt_bind_param($stmt, "iiis", $stId, $courseId, $rating, $comment);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to submit course rating: ' . mysqli_error($con)];
        }

        $avgRes = mysqli_query($con, "SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rate FROM course_ratings WHERE course_id = {$courseId} AND is_approved = 1");
        $avgRow = mysqli_fetch_assoc($avgRes);
        $avgRating = round((float)($avgRow['avg_rate'] ?? $rating), 2);
        $totalReviews = (int)($avgRow['cnt'] ?? 1);

        return [
            'status' => 'success',
            'message' => 'Course rating submitted successfully',
            'data' => [
                'course_id' => $courseId,
                'student_id' => $stId,
                'user_rating' => $rating,
                'comment' => $comment,
                'course_average_rating' => $avgRating,
                'total_reviews' => $totalReviews
            ]
        ];
    }
}

if (!function_exists('studentRateTeacher')) {
    function studentRateTeacher($con, $studentData = null) {
        $body = read_json_body();
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($body['student_id'] ?? 0);
        }
        if ($stId <= 0) {
            return ['status' => 'error', 'message' => 'Student authentication is required'];
        }

        $teacherId = (int)($body['teacher_id'] ?? 0);
        $rating = (int)($body['rating'] ?? 0);
        $comment = trim($body['comment'] ?? '');

        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'teacher_id is required'];
        }
        if ($rating < 1 || $rating > 5) {
            return ['status' => 'error', 'message' => 'rating must be an integer between 1 and 5'];
        }

        $stmt = mysqli_prepare($con, "
            INSERT INTO teacher_ratings (student_id, teacher_id, rating, comment, is_approved)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                comment = VALUES(comment),
                is_approved = 1
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare teacher rating statement'];
        }

        mysqli_stmt_bind_param($stmt, "iiis", $stId, $teacherId, $rating, $comment);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to submit teacher rating: ' . mysqli_error($con)];
        }

        $avgRes = mysqli_query($con, "SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rate FROM teacher_ratings WHERE teacher_id = {$teacherId} AND is_approved = 1");
        $avgRow = mysqli_fetch_assoc($avgRes);
        $avgRating = round((float)($avgRow['avg_rate'] ?? $rating), 2);
        $totalReviews = (int)($avgRow['cnt'] ?? 1);

        $upStmt = mysqli_prepare($con, "UPDATE teachers SET rating = ? WHERE id = ?");
        if ($upStmt) {
            mysqli_stmt_bind_param($upStmt, "di", $avgRating, $teacherId);
            mysqli_stmt_execute($upStmt);
        }

        return [
            'status' => 'success',
            'message' => 'Teacher rating submitted successfully',
            'data' => [
                'teacher_id' => $teacherId,
                'student_id' => $stId,
                'user_rating' => $rating,
                'comment' => $comment,
                'teacher_average_rating' => $avgRating,
                'total_reviews' => $totalReviews
            ]
        ];
    }
}

if (!function_exists('studentGetCoursesAndSubjects')) {
    function studentGetCoursesAndSubjects($con) {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $hasSubjImg = false;
        $chkCol = @mysqli_query($con, "SHOW COLUMNS FROM subjects LIKE 'image'");
        if ($chkCol && mysqli_num_rows($chkCol) > 0) {
            $hasSubjImg = true;
        }

        $subjImgSelect = $hasSubjImg ? "s.image" : "NULL AS image";

        $sRes = mysqli_query($con, "
            SELECT s.id, COALESCE(st.name, s.code) AS name, {$subjImgSelect}
            FROM subjects s
            LEFT JOIN subjects_translations st ON s.id = st.subject_id AND st.language_id = {$langId}
            WHERE s.is_active = 1
            ORDER BY st.name ASC, s.id ASC
        ");

        $subjects = [];
        if ($sRes) {
            while ($sRow = mysqli_fetch_assoc($sRes)) {
                $sRow['id'] = (int)$sRow['id'];
                $sRow['name'] = $sRow['name'] ?? '';
                $sRow['image'] = format_image_url($sRow['image']);
                $subjects[] = $sRow;
            }
        }

        $cRes = mysqli_query($con, "
            SELECT c.id, c.subject_id, COALESCE(ct.name, c.slug) AS name, c.image
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            WHERE c.is_active = 1
            ORDER BY c.id DESC
        ");

        $courses = [];
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $cRow['id'] = (int)$cRow['id'];
                $cRow['subject_id'] = $cRow['subject_id'] ? (int)$cRow['subject_id'] : null;
                $cRow['name'] = $cRow['name'] ?? '';
                $cRow['image'] = format_image_url($cRow['image']);
                $courses[] = $cRow;
            }
        }

        pricing_decorate_rows($con, $courses, 'course');

        return [
            'status' => 'success',
            'data' => format_response_images([
                'subjects' => $subjects,
                'courses' => $courses
            ])
        ];
    }
}

if (!function_exists('studentGetTeacherProfileDetails')) {
    function studentGetTeacherProfileDetails($con, $studentData = null) {
        ensure_teachers_schema($con);

        $teacherId = (int)($_GET['teacher_id'] ?? $_GET['id'] ?? read_json_body()['teacher_id'] ?? read_json_body()['id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => 'teacher_id is required'];
        }

        $stmt = mysqli_prepare($con, "
            SELECT id, name, email, whatsapp, specialization, experience, biography, description, qualifications, rating, video, image, is_active, can_view_profile, created_at
            FROM teachers
            WHERE id = ? LIMIT 1
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare statement'];
        }

        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $tRow = mysqli_fetch_assoc($res);

        if (!$tRow) {
            return ['status' => 'error', 'message' => 'Teacher not found'];
        }

        $canViewProfile = (int)($tRow['can_view_profile'] ?? 1);
        $tRow['id'] = (int)$tRow['id'];
        $tRow['rating'] = (float)$tRow['rating'];
        $tRow['is_active'] = (int)$tRow['is_active'];
        $tRow['can_view_profile'] = ($canViewProfile === 1);

        if ($canViewProfile !== 1) {
            return [
                'status' => 'error',
                'message' => 'Browsing this teacher profile is not allowed',
                'can_view_profile' => false,
                'data' => [
                    'id' => $tRow['id'],
                    'name' => $tRow['name'],
                    'can_view_profile' => false
                ]
            ];
        }

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $subjects = get_teacher_subjects_details($con, $teacherId);

        $typeFilter = strtolower(trim($_GET['type'] ?? $_GET['course_type'] ?? read_json_body()['type'] ?? read_json_body()['course_type'] ?? ''));
        $typeCondition = "";
        if (!empty($typeFilter)) {
            $tp = mysqli_real_escape_string($con, $typeFilter);
            $typeCondition = " AND c.type = '{$tp}'";
        }
        $kindFilter = strtolower(trim($_GET['kind'] ?? read_json_body()['kind'] ?? ''));
        if (in_array($kindFilter, ['normal', 'package'], true)) {
            $typeCondition .= " AND c.kind = '{$kindFilter}'";
        }

        $cRes = mysqli_query($con, "
            SELECT DISTINCT c.id, c.subject_id, c.type, c.kind, COALESCE(ct.name, c.slug) AS name, c.price, c.image, ct.description
            FROM courses c
            LEFT JOIN course_teachers cot ON c.id = cot.course_id
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            WHERE (c.teacher_id = {$teacherId} OR cot.teacher_id = {$teacherId}) AND c.is_active = 1 {$typeCondition}
            ORDER BY c.id DESC
        ");

        $courses = [];
        if ($cRes) {
            while ($cRow = mysqli_fetch_assoc($cRes)) {
                $cRow['id'] = (int)$cRow['id'];
                $cRow['subject_id'] = $cRow['subject_id'] ? (int)$cRow['subject_id'] : null;
                $cRow['type'] = $cRow['type'] ?? 'recorded';
                $cRow['kind'] = $cRow['kind'] ?? 'normal';
                $cRow['price'] = (float)$cRow['price'];
                $courses[] = $cRow;
            }
        }

        pricing_decorate_rows($con, $courses, 'course');

        $ratingsCount = 0;
        $averageRating = (float)$tRow['rating'];
        $rStatRes = mysqli_query($con, "SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS avg_rate FROM teacher_ratings WHERE teacher_id = {$teacherId} AND is_approved = 1");
        if ($rStatRes && $rStatRow = mysqli_fetch_assoc($rStatRes)) {
            $ratingsCount = (int)($rStatRow['total'] ?? 0);
            if ($ratingsCount > 0) {
                $averageRating = round((float)$rStatRow['avg_rate'], 2);
            }
        }

        $reviewsRes = mysqli_query($con, "
            SELECT tr.id, tr.rating, tr.comment, tr.created_at, s.name AS student_name, s.image AS student_image
            FROM teacher_ratings tr
            LEFT JOIN students s ON tr.student_id = s.id
            WHERE tr.teacher_id = {$teacherId} AND tr.is_approved = 1
            ORDER BY tr.id DESC
        ");

        $reviews = [];
        if ($reviewsRes) {
            while ($rRow = mysqli_fetch_assoc($reviewsRes)) {
                $rRow['id'] = (int)$rRow['id'];
                $rRow['rating'] = (int)$rRow['rating'];
                $reviews[] = $rRow;
            }
        }

        $tRow['rating'] = $averageRating;
        $tRow['average_rating'] = $averageRating;
        $tRow['total_reviews_count'] = $ratingsCount;
        $tRow['subjects'] = $subjects;
        $tRow['courses'] = $courses;
        $tRow['reviews'] = $reviews;
        $tRow['ratings'] = $reviews;

        return [
            'status' => 'success',
            'data' => format_response_images($tRow)
        ];
    }
}

if (!function_exists('studentGetCourseAllContent')) {
    function studentGetCourseAllContent($con, $studentData = null) {
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
        $courseId = (int)($_GET['id'] ?? $_GET['course_id'] ?? $body['id'] ?? $body['course_id'] ?? 0);
        $slug = trim($_GET['slug'] ?? $body['slug'] ?? '');

        if ($courseId <= 0 && empty($slug)) {
            return ['status' => 'error', 'message' => 'course_id or slug is required'];
        }

        $langInfo = function_exists('get_student_request_lang_info') ? get_student_request_lang_info($con) : get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 2);

        $whereCond = $courseId > 0 ? "c.id = {$courseId}" : "c.slug = '" . mysqli_real_escape_string($con, $slug) . "'";

        $sql = "
            SELECT c.id, c.subject_id, c.academic_year_id, c.teacher_id, c.slug, c.price, c.image, c.video_preview_url, c.is_active, c.created_at, c.what_you_will_learn AS c_learn,
                   COALESCE(ct.name, c.slug) AS name, ct.description, ct.what_you_will_learn AS ct_learn,
                   t.id AS t_id, t.name AS teacher_name, t.specialization AS teacher_specialization, t.image AS teacher_image, t.rating AS teacher_rating, t.experience AS teacher_experience,
                   s.id AS sub_id, COALESCE(sub_t.name, s.code) AS subject_name,
                   ay.id AS ay_id, COALESCE(ay_t.name, ay.code) AS academic_year_name
            FROM courses c
            LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = {$langId}
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN subjects_translations sub_t ON s.id = sub_t.subject_id AND sub_t.language_id = {$langId}
            LEFT JOIN academic_years ay ON c.academic_year_id = ay.id
            LEFT JOIN academic_years_translations ay_t ON ay.id = ay_t.academic_year_id AND ay_t.language_id = {$langId}
            WHERE {$whereCond} AND c.is_active = 1 LIMIT 1
        ";

        $res = mysqli_query($con, $sql);
        $cRow = mysqli_fetch_assoc($res);
        if (!$cRow) {
            return ['status' => 'error', 'message' => 'Course not found or inactive'];
        }

        $cId = (int)$cRow['id'];
        $isSubscribed = false;
        if ($stId > 0 && function_exists('get_student_accessible_entities')) {
            $accessible = get_student_accessible_entities($con, $stId);
            $isSubscribed = in_array($cId, $accessible['course_ids']);
        }

        $hasVideoProgress = studentTableExists($con, 'student_video_progress');
        $hasBookProgress = studentTableExists($con, 'student_book_progress');

        $uRes = mysqli_query($con, "
            SELECT u.id, u.course_id, u.subject_id, u.code, u.is_active, u.created_by_type, u.created_by_id,
                   COALESCE(ut.name, u.code) AS name, ut.description
            FROM units u
            LEFT JOIN units_translations ut ON u.id = ut.unit_id AND ut.language_id = {$langId}
            WHERE u.course_id = {$cId} AND u.is_active = 1
            ORDER BY u.id ASC
        ");

        $units = [];
        $overallTotalUnits = 0;
        $overallTotalVideos = 0;
        $overallTotalCompletedVideos = 0;
        $overallTotalBooks = 0;
        $overallTotalOpenedBooks = 0;
        $overallTotalExams = 0;
        $overallTotalAttemptedExams = 0;
        $overallTotalHomework = 0;
        $overallTotalSubmittedHomework = 0;
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

                // Five of the columns this used to name do not exist on `videos`: course_id,
                // duration, created_by_type and created_by_id belong to `course_videos`, and
                // videos_translations has `name`, not `title`. MySQL answers a SELECT naming an
                // unknown column with error 1054 and no rows, so this query has been returning
                // nothing since it was written - every course's curriculum, empty, always.
                $vUrlExpr = video_url_select_expr($con, 'v', 'cv');
                $vSql = "
                    SELECT v.id, v.unit_id, v.code, v.duration_seconds, v.publish_status, v.is_active,
                           {$vUrlExpr} AS video_url,
                           cv.created_by_type, cv.created_by_id,
                           COALESCE(vt.name, cvt.title, v.code) AS title,
                           COALESCE(vt.description, cvt.description, '') AS description
                    FROM videos v
                    LEFT JOIN course_videos cv ON v.id = cv.id
                    LEFT JOIN videos_translations vt ON v.id = vt.video_id AND vt.language_id = {$langId}
                    LEFT JOIN course_videos_translations cvt ON cv.id = cvt.video_id AND cvt.language_id = {$langId}
                    WHERE v.unit_id = {$uId} AND v.is_active = 1 AND (v.publish_status IS NULL OR v.publish_status = 'published')
                    ORDER BY v.order_num ASC, v.id ASC
                ";
                $vRes = mysqli_query($con, $vSql);
                $videos = [];
                $unitVideosCount = 0;
                $unitCompletedVideosCount = 0;
                $vIndex = 1;

                if ($vRes) {
                    while ($vRow = mysqli_fetch_assoc($vRes)) {
                        $vId = (int)$vRow['id'];

                        if ($vRow['created_by_type'] === 'teacher' && (int)$vRow['created_by_id'] > 0) {
                            $vtCheck1 = mysqli_query($con, "SELECT is_active FROM teachers WHERE id = " . (int)$vRow['created_by_id'] . " LIMIT 1");
                            if ($vtCheck1 && $vtRow1 = mysqli_fetch_assoc($vtCheck1)) {
                                if ((int)$vtRow1['is_active'] !== 1) {
                                    continue;
                                }
                            }
                        }
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

                        $unitVideosCount++;
                        $overallTotalVideos++;

                        $isFreePreview = false;
                        $isLocked = !$isSubscribed;
                        $isCompleted = 0;
                        $watchedSeconds = 0;
                        $totalSeconds = (int)($vRow['duration_seconds'] ?? 0);
                        $lastPos = 0;

                        if ($stId > 0 && $hasVideoProgress) {
                            $vpRes = mysqli_query($con, "SELECT is_completed, watched_seconds, total_seconds, last_position_seconds FROM student_video_progress WHERE student_id = {$stId} AND video_id = {$vId} LIMIT 1");
                            if ($vpRes && $vpRow = mysqli_fetch_assoc($vpRes)) {
                                $isCompleted = (int)($vpRow['is_completed'] ?? 0);
                                $watchedSeconds = (int)($vpRow['watched_seconds'] ?? 0);
                                if ($totalSeconds <= 0) {
                                    $totalSeconds = (int)($vpRow['total_seconds'] ?? 0);
                                }
                                $lastPos = (int)($vpRow['last_position_seconds'] ?? 0);
                            }
                        }

                        if ($isCompleted === 1) {
                            $unitCompletedVideosCount++;
                            $overallTotalCompletedVideos++;
                        }

                        $videos[] = [
                            'id' => $vId,
                            'unit_id' => $uId,
                            'code' => $vRow['code'],
                            'title' => $vRow['title'],
                            'description' => $vRow['description'],
                            'video_url' => $isLocked ? null : $vRow['video_url'],
                            'duration_seconds' => $totalSeconds,
                            'publish_status' => $vRow['publish_status'],
                            'is_free_preview' => false,
                            'is_locked' => $isLocked,
                            'is_open' => !$isLocked,
                            'statistics' => [
                                'is_completed' => $isCompleted,
                                'watched_seconds' => $watchedSeconds,
                                'total_seconds' => $totalSeconds,
                                'last_position_seconds' => $lastPos
                            ]
                        ];
                        $vIndex++;
                    }
                }

                $bSql = "
                    SELECT cb.id, cb.unit_id, cb.course_id, cb.file_type, cb.extension, cb.link, cb.file_path, cb.is_active,
                           COALESCE(cbt.title, cb.file_type) AS title, cbt.description
                    FROM course_books cb
                    LEFT JOIN course_books_translations cbt ON cb.id = cbt.book_id AND cbt.language_id = {$langId}
                    WHERE cb.unit_id = {$uId} AND cb.is_active = 1
                    ORDER BY cb.order_num ASC, cb.id ASC
                ";
                $bRes = mysqli_query($con, $bSql);
                $books = [];
                $unitBooksCount = 0;
                $unitOpenedBooksCount = 0;

                if ($bRes) {
                    while ($bRow = mysqli_fetch_assoc($bRes)) {
                        $bId = (int)$bRow['id'];
                        $unitBooksCount++;
                        $overallTotalBooks++;

                        $openCount = 0;
                        $lastPage = 1;
                        $totalPages = 0;

                        if ($stId > 0 && $hasBookProgress) {
                            $bpRes = mysqli_query($con, "SELECT open_count, last_page_number, total_pages FROM student_book_progress WHERE student_id = {$stId} AND book_id = {$bId} LIMIT 1");
                            if ($bpRes && $bpRow = mysqli_fetch_assoc($bpRes)) {
                                $openCount = (int)($bpRow['open_count'] ?? 0);
                                $lastPage = (int)($bpRow['last_page_number'] ?? 1);
                                $totalPages = (int)($bpRow['total_pages'] ?? 0);
                            }
                        }

                        if ($openCount > 0) {
                            $unitOpenedBooksCount++;
                            $overallTotalOpenedBooks++;
                        }

                        $books[] = [
                            'id' => $bId,
                            'unit_id' => $uId,
                            'title' => $bRow['title'],
                            'description' => $bRow['description'],
                            'file_type' => $bRow['file_type'],
                            'extension' => $bRow['extension'],
                            'file_path' => $isSubscribed ? format_image_url($bRow['file_path'] ?: $bRow['link']) : null,
                            'link' => $isSubscribed ? $bRow['link'] : null,
                            'is_locked' => !$isSubscribed,
                            'is_open' => $isSubscribed,
                            'is_subscribed' => $isSubscribed,
                            'own' => $isSubscribed,
                            'statistics' => [
                                'open_count' => $openCount,
                                'last_page_number' => $lastPage,
                                'total_pages' => $totalPages
                            ]
                        ];
                    }
                }

                $eSql = "
                    SELECT ce.id, ce.unit_id, ce.course_id, ce.duration_minutes, ce.total_marks, ce.pass_marks, ce.is_active,
                           COALESCE(cet.title, '') AS title, cet.description
                    FROM course_exams ce
                    LEFT JOIN course_exams_translations cet ON ce.id = cet.exam_id AND cet.language_id = {$langId}
                    WHERE ce.unit_id = {$uId} AND ce.is_active = 1
                    ORDER BY ce.order_num ASC, ce.id ASC
                ";
                $eRes = mysqli_query($con, $eSql);
                $exams = [];
                $unitExamsCount = 0;
                $unitAttemptedExamsCount = 0;

                if ($eRes) {
                    while ($eRow = mysqli_fetch_assoc($eRes)) {
                        $examId = (int)$eRow['id'];
                        $unitExamsCount++;
                        $overallTotalExams++;

                        $myAttempt = null;
                        if ($stId > 0) {
                            $eaRes = mysqli_query($con, "SELECT id, status, total_score, max_score, started_at, submitted_at FROM exam_attempts WHERE exam_id = {$examId} AND student_id = {$stId} ORDER BY id DESC LIMIT 1");
                            if ($eaRes && $eaRow = mysqli_fetch_assoc($eaRes)) {
                                $unitAttemptedExamsCount++;
                                $overallTotalAttemptedExams++;
                                $myAttempt = [
                                    'attempt_id' => (int)$eaRow['id'],
                                    'status' => $eaRow['status'],
                                    'total_score' => $eaRow['total_score'] !== null ? (float)$eaRow['total_score'] : null,
                                    'max_score' => (float)$eaRow['max_score'],
                                    'started_at' => $eaRow['started_at'],
                                    'submitted_at' => $eaRow['submitted_at']
                                ];
                            }
                        }

                        $exams[] = [
                            'id' => $examId,
                            'unit_id' => $uId,
                            'title' => $eRow['title'],
                            'description' => $eRow['description'],
                            'duration_minutes' => (int)$eRow['duration_minutes'],
                            'total_marks' => (float)$eRow['total_marks'],
                            'pass_marks' => (float)$eRow['pass_marks'],
                            'my_attempt' => $myAttempt
                        ];
                    }
                }

                $hSql = "
                    SELECT h.id, h.unit_id, h.course_id, h.title, h.description, h.due_date, h.max_marks, h.is_active
                    FROM homework h
                    WHERE h.unit_id = {$uId} AND h.is_active = 1
                    ORDER BY h.id ASC
                ";
                $hRes = @mysqli_query($con, $hSql);
                $homeworkList = [];
                $unitHomeworkCount = 0;
                $unitSubmittedHomeworkCount = 0;

                if ($hRes) {
                    while ($hRow = mysqli_fetch_assoc($hRes)) {
                        $hwId = (int)$hRow['id'];
                        $unitHomeworkCount++;
                        $overallTotalHomework++;

                        $mySubmission = null;
                        if ($stId > 0) {
                            $hsRes = mysqli_query($con, "SELECT id, status, score_awarded, feedback_note, submitted_at FROM homework_submissions WHERE homework_id = {$hwId} AND student_id = {$stId} ORDER BY id DESC LIMIT 1");
                            if ($hsRes && $hsRow = mysqli_fetch_assoc($hsRes)) {
                                $unitSubmittedHomeworkCount++;
                                $overallTotalSubmittedHomework++;
                                $mySubmission = [
                                    'submission_id' => (int)$hsRow['id'],
                                    'status' => $hsRow['status'],
                                    'score_awarded' => $hsRow['score_awarded'] !== null ? (float)$hsRow['score_awarded'] : null,
                                    'feedback_note' => $hsRow['feedback_note'],
                                    'submitted_at' => $hsRow['submitted_at']
                                ];
                            }
                        }

                        $homeworkList[] = [
                            'id' => $hwId,
                            'unit_id' => $uId,
                            'title' => $hRow['title'],
                            'description' => $hRow['description'],
                            'due_date' => $hRow['due_date'],
                            'max_marks' => (float)$hRow['max_marks'],
                            'my_submission' => $mySubmission
                        ];
                    }
                }

                $qSql = "
                    SELECT q.id, q.unit_id, q.course_id, q.question_type, q.score, q.is_active, q.created_by_type, q.created_by_id,
                           COALESCE(qt.question_text, '') AS question_text
                    FROM questions q
                    LEFT JOIN questions_translations qt ON q.id = qt.question_id AND qt.language_id = {$langId}
                    WHERE q.unit_id = {$uId} AND q.is_active = 1
                    ORDER BY q.id ASC
                ";
                $qRes = mysqli_query($con, $qSql);
                $questions = [];
                $unitQuestionsCount = 0;
                $unitAnsweredQuestionsCount = 0;
                $unitTotalAnswers = 0;
                $unitCorrectAnswers = 0;
                $unitWrongAnswers = 0;

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

                        $unitQuestionsCount++;
                        $overallTotalQuestions++;

                        $options = [];
                        if ($qRow['question_type'] === 'mcq') {
                            $optSql = "
                                SELECT mo.id, mo.is_correct, mo.order_num,
                                       COALESCE(mot.option_text, '') AS option_text
                                FROM mcq_options mo
                                LEFT JOIN mcq_options_translations mot ON mo.id = mot.mcq_option_id AND mot.language_id = {$langId}
                                WHERE mo.question_id = {$qId}
                                ORDER BY mo.order_num ASC, mo.id ASC
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

                        $qAnsCount = $qStats['times_answered'];
                        $qCorrCount = $qStats['correct_answers_count'];
                        $qWrngCount = $qStats['wrong_answers_count'];

                        if ($qAnsCount > 0) {
                            $unitAnsweredQuestionsCount++;
                            $overallTotalAnsweredQuestions++;
                        }

                        $unitTotalAnswers += $qAnsCount;
                        $unitCorrectAnswers += $qCorrCount;
                        $unitWrongAnswers += $qWrngCount;

                        $overallTotalAnswersCount += $qAnsCount;
                        $overallTotalCorrectCount += $qCorrCount;
                        $overallTotalWrongCount += $qWrngCount;

                        $questions[] = [
                            'id' => $qId,
                            'unit_id' => $uId,
                            'question_type' => $qRow['question_type'],
                            'score' => (float)$qRow['score'],
                            'question_text' => $qRow['question_text'],
                            'options' => $options,
                            'true_false_answer' => $tfAnswer,
                            'statistics' => $qStats
                        ];
                    }
                }

                $unitAvgPercentage = $unitTotalAnswers > 0 ? round(($unitCorrectAnswers / $unitTotalAnswers) * 100, 2) : 0.00;

                $units[] = [
                    'id' => $uId,
                    'course_id' => $cId,
                    'code' => $uRow['code'],
                    'name' => $uRow['name'],
                    'description' => $uRow['description'],
                    'videos' => $videos,
                    'books' => $books,
                    'exams' => $exams,
                    'homework' => $homeworkList,
                    'questions' => $questions,
                    'statistics' => [
                        'videos_count' => $unitVideosCount,
                        'completed_videos_count' => $unitCompletedVideosCount,
                        'books_count' => $unitBooksCount,
                        'opened_books_count' => $unitOpenedBooksCount,
                        'exams_count' => $unitExamsCount,
                        'attempted_exams_count' => $unitAttemptedExamsCount,
                        'homework_count' => $unitHomeworkCount,
                        'submitted_homework_count' => $unitSubmittedHomeworkCount,
                        'questions_count' => $unitQuestionsCount,
                        'answered_questions_count' => $unitAnsweredQuestionsCount,
                        'unit_total_answers' => $unitTotalAnswers,
                        'unit_correct_answers' => $unitCorrectAnswers,
                        'unit_wrong_answers' => $unitWrongAnswers,
                        'unit_average_percentage' => $unitAvgPercentage
                    ]
                ];
            }
        }

        $overallAvgPercentage = $overallTotalAnswersCount > 0 ? round(($overallTotalCorrectCount / $overallTotalAnswersCount) * 100, 2) : 0.00;

        $contentPricing = pricing_for_item($con, 'course', $cId, (float)$cRow['price'], $stId);

        $responseData = [
            'id' => $cId,
            'slug' => $cRow['slug'],
            'name' => $cRow['name'],
            'description' => $cRow['description'],
            'price' => $contentPricing['price'],
            'base_price' => $contentPricing['base_price'],
            'currency' => $contentPricing['currency'],
            'price_formatted' => $contentPricing['price_formatted'],
            'is_country_price' => $contentPricing['is_country_price'],
            'image' => $cRow['image'],
            'video_preview_url' => $cRow['video_preview_url'],
            'is_subscribed' => $isSubscribed,
            'teacher' => $cRow['t_id'] ? format_response_images([
                'id' => (int)$cRow['t_id'],
                'name' => $cRow['teacher_name'],
                'image' => $cRow['teacher_image'],
                'specialization' => $cRow['teacher_specialization']
            ]) : null,
            'subject' => $cRow['sub_id'] ? [
                'id' => (int)$cRow['sub_id'],
                'name' => $cRow['subject_name']
            ] : null,
            'academic_year' => $cRow['ay_id'] ? [
                'id' => (int)$cRow['ay_id'],
                'name' => $cRow['academic_year_name']
            ] : null,
            'units' => $units,
            'statistics' => [
                'total_units_count' => $overallTotalUnits,
                'total_videos_count' => $overallTotalVideos,
                'total_completed_videos_count' => $overallTotalCompletedVideos,
                'total_books_count' => $overallTotalBooks,
                'total_opened_books_count' => $overallTotalOpenedBooks,
                'total_exams_count' => $overallTotalExams,
                'total_attempted_exams_count' => $overallTotalAttemptedExams,
                'total_homework_count' => $overallTotalHomework,
                'total_submitted_homework_count' => $overallTotalSubmittedHomework,
                'total_questions_count' => $overallTotalQuestions,
                'total_answered_questions_count' => $overallTotalAnsweredQuestions,
                'total_answers_count' => $overallTotalAnswersCount,
                'total_correct_count' => $overallTotalCorrectCount,
                'total_wrong_count' => $overallTotalWrongCount,
                'average_percentage' => $overallAvgPercentage
            ]
        ];

        return [
            'status' => 'success',
            'data' => format_response_images($responseData)
        ];
    }
}