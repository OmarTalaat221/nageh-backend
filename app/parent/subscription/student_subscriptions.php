<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('get_student_subscriptions')) {
    function get_student_subscriptions($con, int $studentId, string $filter = 'all') {

        if ($studentId <= 0) {
            return [
                'summary'       => [
                    'total_remaining'   => 0,
                    'total_paid'        => 0,
                    'active_courses'    => 0,
                    'active_books'      => 0,
                    'total_subscriptions' => 0,
                ],
                'subscriptions' => []
            ];
        }

    
    
        $sql = "SELECT 
                    ss.id                       AS subscription_id,
                    ss.student_id               AS student_id,
                    ss.subscription_code_id     AS subscription_code_id,
                    ss.subscription_type        AS subscription_type,
                    ss.start_date               AS start_date,
                    ss.end_date                 AS end_date,
                    ss.is_active                AS is_active,
                    ss.created_at               AS created_at,
                    ss.updated_at               AS updated_at,

                    sc.code                     AS code,
                    sc.title                    AS code_title,
                    sc.description              AS code_description,
                    sc.price                    AS code_price,
                    sc.duration_days            AS duration_days,
                    sc.valid_from               AS valid_from,
                    sc.valid_to                 AS valid_to
                FROM student_subscriptions ss
                LEFT JOIN subscription_codes sc ON sc.id = ss.subscription_code_id
                WHERE ss.student_id = ?
                ORDER BY ss.created_at DESC";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return [
                'summary'       => [],
                'subscriptions' => [],
                'error'         => mysqli_error($con)
            ];
        }

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $subscriptions = [];
        $subIds        = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $subId              = (int)$row['subscription_id'];
            $subIds[]           = $subId;

            $startDate          = $row['start_date'];
            $endDate            = $row['end_date'];
            $isActiveFlag       = (int)$row['is_active'] === 1;
            $price              = $row['code_price'] !== null ? (float)$row['code_price'] : 0;

            // ---- remaining days & progress ----
            $today          = time();
            $endTs          = $endDate   ? strtotime($endDate)   : null;
            $startTs        = $startDate ? strtotime($startDate) : null;

            $remainingDays  = 0;
            $totalDays      = (int)($row['duration_days'] ?? 0);
            $elapsedDays    = 0;
            $progressPct    = 0;

            if ($endTs && $endTs >= $today) {
                $remainingDays = (int) ceil(($endTs - $today) / 86400);
            }

            if ($startTs && $endTs && $endTs > $startTs) {
                $totalRange   = $endTs - $startTs;
                $elapsedRange = min(max($today - $startTs, 0), $totalRange);
                $progressPct  = round(($elapsedRange / $totalRange) * 100, 2);
                $elapsedDays  = (int) floor($elapsedRange / 86400);
                if ($totalDays <= 0) {
                    $totalDays = (int) ceil($totalRange / 86400);
                }
            }

            // ---- status ----
            $isExpired = ($endTs && $endTs < $today);
            if (!$isActiveFlag || $isExpired) {
                $status      = 'expired';
                $statusLabel = 'منتهي';
            } elseif ($remainingDays > 0) {
                $status      = 'active';
                $statusLabel = 'نشط';
            } else {
                $status      = 'expired';
                $statusLabel = 'منتهي';
            }

            $subscriptions[$subId] = [
                'id'                   => $subId,
                'code'                 => $row['code'],
                'title'                => $row['code_title'],
                'description'          => $row['code_description'],
                'subscription_type'    => $row['subscription_type'],
                'price'                => $price,
                'start_date'           => $startDate,
                'end_date'             => $endDate,
                'duration_days'        => $totalDays,
                'elapsed_days'         => $elapsedDays,
                'remaining_days'       => $remainingDays,
                'progress_percent'     => $progressPct,
                'is_active'            => $isActiveFlag,
                'status'               => $status,
                'status_label'         => $statusLabel,
                'created_at'           => $row['created_at'],
                'updated_at'           => $row['updated_at'],
                'courses'              => [],
                'books'                => [],
                'teachers'             => [],
            ];
        }
        mysqli_stmt_close($stmt);

        // ============================================================
        //  2) Fetch courses / books / teachers per subscription
        // ============================================================
        if (!empty($subIds)) {
            $ids = implode(',', array_map('intval', $subIds));

            // --- Courses ---
            $cSql = "SELECT ssc.student_subscription_id, ssc.course_id,
                            c.slug, c.type, c.image, c.price, c.lessons_count,
                            c.category_id, c.subject_id, c.teacher_id
                     FROM student_subscription_courses ssc
                     INNER JOIN courses c ON c.id = ssc.course_id
                     WHERE ssc.student_subscription_id IN ($ids)";
            $cRes = mysqli_query($con, $cSql);
            if ($cRes) {
                while ($r = mysqli_fetch_assoc($cRes)) {
                    $sid = (int)$r['student_subscription_id'];
                    if (isset($subscriptions[$sid])) {
                        $subscriptions[$sid]['courses'][] = [
                            'id'            => (int)$r['course_id'],
                            'slug'          => $r['slug'],
                            'type'          => $r['type'],
                            'image'         => $r['image'],
                            'price'         => $r['price'] !== null ? (float)$r['price'] : 0,
                            'lessons_count' => (int)($r['lessons_count'] ?? 0),
                            'category_id'   => $r['category_id'] ? (int)$r['category_id'] : null,
                            'subject_id'    => $r['subject_id']  ? (int)$r['subject_id']  : null,
                            'teacher_id'    => $r['teacher_id']  ? (int)$r['teacher_id']  : null,
                        ];
                    }
                }
            }

            // --- Books ---
            $bSql = "SELECT ssb.student_subscription_id, ssb.book_id,
                            b.title AS book_title, b.image AS book_image,
                            b.price AS book_price
                     FROM student_subscription_books ssb
                     INNER JOIN books b ON b.id = ssb.book_id
                     WHERE ssb.student_subscription_id IN ($ids)";
            $bRes = mysqli_query($con, $bSql);
            if ($bRes) {
                while ($r = mysqli_fetch_assoc($bRes)) {
                    $sid = (int)$r['student_subscription_id'];
                    if (isset($subscriptions[$sid])) {
                        $subscriptions[$sid]['books'][] = [
                            'id'    => (int)$r['book_id'],
                            'title' => $r['book_title'],
                            'image' => $r['book_image'],
                            'price' => $r['book_price'] !== null ? (float)$r['book_price'] : 0,
                        ];
                    }
                }
            }

            // --- Teachers ---
            $tSql = "SELECT sst.student_subscription_id, sst.teacher_id,
                            t.name AS teacher_name, t.image AS teacher_image
                     FROM student_subscription_teachers sst
                     INNER JOIN teachers t ON t.id = sst.teacher_id
                     WHERE sst.student_subscription_id IN ($ids)";
            $tRes = mysqli_query($con, $tSql);
            if ($tRes) {
                while ($r = mysqli_fetch_assoc($tRes)) {
                    $sid = (int)$r['student_subscription_id'];
                    if (isset($subscriptions[$sid])) {
                        $subscriptions[$sid]['teachers'][] = [
                            'id'    => (int)$r['teacher_id'],
                            'name'  => $r['teacher_name'],
                            'image' => $r['teacher_image'],
                        ];
                    }
                }
            }
        }

        // ============================================================
        //  3) Also fetch standalone book subscriptions (student_book_subscriptions)
        // ============================================================
        $sqlBooks = "SELECT sbs.id                AS subscription_id,
                            sbs.student_id        AS student_id,
                            sbs.book_id           AS book_id,
                            sbs.granted_by_type   AS granted_by_type,
                            sbs.granted_by_id     AS granted_by_id,
                            sbs.price             AS price,
                            sbs.is_active         AS is_active,
                            sbs.created_at        AS created_at,
                            b.title               AS book_title,
                            b.image               AS book_image
                     FROM student_book_subscriptions sbs
                     INNER JOIN books b ON b.id = sbs.book_id
                     WHERE sbs.student_id = ?
                     ORDER BY sbs.created_at DESC";

        $stmt2 = mysqli_prepare($con, $sqlBooks);
        if ($stmt2) {
            mysqli_stmt_bind_param($stmt2, "i", $studentId);
            mysqli_stmt_execute($stmt2);
            $bookRes = mysqli_stmt_get_result($stmt2);

            while ($row = mysqli_fetch_assoc($bookRes)) {
                $isActiveFlag = (int)$row['is_active'] === 1;
                $status       = $isActiveFlag ? 'active'  : 'expired';
                $statusLabel  = $isActiveFlag ? 'نشط'    : 'منتهي';

                // Give it a unique key so it doesn't collide with main subscriptions
                $key = 'book_' . (int)$row['subscription_id'];

                $subscriptions[$key] = [
                    'id'                => (int)$row['subscription_id'],
                    'code'              => null,
                    'title'             => $row['book_title'],
                    'description'       => null,
                    'subscription_type' => 'book',
                    'price'             => $row['price'] !== null ? (float)$row['price'] : 0,
                    'start_date'        => $row['created_at'],
                    'end_date'          => null,
                    'duration_days'     => 0,
                    'elapsed_days'      => 0,
                    'remaining_days'    => 0,
                    'progress_percent'  => 0,
                    'is_active'         => $isActiveFlag,
                    'status'            => $status,
                    'status_label'      => $statusLabel,
                    'created_at'        => $row['created_at'],
                    'updated_at'        => null,
                    'granted_by_type'   => $row['granted_by_type'],
                    'granted_by_id'     => $row['granted_by_id'] ? (int)$row['granted_by_id'] : null,
                    'courses'           => [],
                    'teachers'          => [],
                    'books'             => [[
                        'id'    => (int)$row['book_id'],
                        'title' => $row['book_title'],
                        'image' => $row['book_image'],
                        'price' => $row['price'] !== null ? (float)$row['price'] : 0,
                    ]],
                ];
            }
            mysqli_stmt_close($stmt2);
        }

        // ============================================================
        //  4) Filter (all / active / expired)
        // ============================================================
        $filter = strtolower(trim($filter));
        if (in_array($filter, ['active', 'expired'], true)) {
            $subscriptions = array_filter($subscriptions, function ($s) use ($filter) {
                return $s['status'] === $filter;
            });
        }

        // ============================================================
        //  5) Build summary
        // ============================================================
        $totalPaid       = 0;
        $totalRemaining  = 0;
        $activeCourses   = 0;
        $activeBooks     = 0;

        foreach ($subscriptions as $sub) {
            $totalPaid += (float)$sub['price'];

            if ($sub['status'] === 'active') {
                // "remaining" = pro-rated unused portion of active subscription price
                if ($sub['duration_days'] > 0 && $sub['remaining_days'] > 0) {
                    $totalRemaining += round(
                        ((float)$sub['price'] / $sub['duration_days']) * $sub['remaining_days'],
                        2
                    );
                }
                $activeCourses += count($sub['courses']);
                $activeBooks   += count($sub['books']);
            }
        }

        $subscriptionsList = array_values($subscriptions);

        return [
            'summary' => [
                'total_subscriptions' => count($subscriptionsList),
                'total_paid'          => round($totalPaid, 2),
                'total_remaining'     => round($totalRemaining, 2),
                'active_courses'      => $activeCourses,
                'active_books'        => $activeBooks,
            ],
            'subscriptions' => format_response_images($subscriptionsList),
        ];
    }
}