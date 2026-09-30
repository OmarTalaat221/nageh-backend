<?php
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('dashboard_fetch_row')) {
    function dashboard_fetch_row($con, string $sql): array {
        $res = mysqli_query($con, $sql);
        if (!$res) return [];
        $row = mysqli_fetch_assoc($res);
        return is_array($row) ? $row : [];
    }
}

if (!function_exists('dashboard_fetch_all')) {
    function dashboard_fetch_all($con, string $sql): array {
        $res = mysqli_query($con, $sql);
        if (!$res) return [];
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
        return $rows;
    }
}

if (!function_exists('dashboard_month_labels')) {
    function dashboard_month_labels(): array {
        return [
            1  => ['ar' => 'يناير',  'en' => 'Jan'],
            2  => ['ar' => 'فبراير', 'en' => 'Feb'],
            3  => ['ar' => 'مارس',   'en' => 'Mar'],
            4  => ['ar' => 'أبريل',  'en' => 'Apr'],
            5  => ['ar' => 'مايو',   'en' => 'May'],
            6  => ['ar' => 'يونيو',  'en' => 'Jun'],
            7  => ['ar' => 'يوليو',  'en' => 'Jul'],
            8  => ['ar' => 'أغسطس',  'en' => 'Aug'],
            9  => ['ar' => 'سبتمبر', 'en' => 'Sep'],
            10 => ['ar' => 'أكتوبر', 'en' => 'Oct'],
            11 => ['ar' => 'نوفمبر', 'en' => 'Nov'],
            12 => ['ar' => 'ديسمبر', 'en' => 'Dec'],
        ];
    }
}

/**
 * Every way money has ever entered this platform, as one list, so a new revenue figure can never
 * silently miss a channel. Each entry says where the rows live, which column is the money, and
 * which column is the moment it was earned.
 *
 * Double counting is prevented at the source, not afterwards:
 * - a book bought online writes BOTH a payment_transactions row and a student_book_subscriptions
 *   row, linked by payment_transactions.granted_ref_id, so offline_book_grants excludes any
 *   student_book_subscriptions row that a paid payment_transactions row points at.
 * - an APPROVED offline_payment_requests row for a book is the same kind of manual grant as
 *   offline_book_grants above (offline_payments.php calls the same payments_grant_access() that
 *   writes student_book_subscriptions, and never touches payment_transactions), so it is already
 *   counted there and is deliberately not repeated in offline_payments below.
 * - legacy_transactions is the old, no-longer-written `transactions` ledger. Nothing in the
 *   current codebase inserts into it, so it cannot collide with the live channels, but any
 *   historical rows sitting in it still count as real money that was taken.
 */
if (!function_exists('dashboard_revenue_sources')) {
    function dashboard_revenue_sources(): array {
        return [
            'online_payments' => [
                'label_ar' => 'مدفوعات إلكترونية',
                'from' => "payment_transactions pt",
                'where' => "pt.status = 'paid'",
                'amount' => "pt.amount",
                'date' => "COALESCE(pt.paid_at, pt.created_at)",
            ],
            'subscription_codes' => [
                'label_ar' => 'أكواد الاشتراك',
                'from' => "code_redemptions_log r JOIN subscription_codes sc ON sc.id = r.subscription_code_id",
                'where' => "1 = 1",
                'amount' => "sc.price",
                'date' => "r.redeemed_at",
            ],
            'offline_book_grants' => [
                'label_ar' => 'كتب مباعة يدويًا',
                'from' => "student_book_subscriptions sbs",
                'where' => "sbs.price > 0 AND NOT EXISTS (
                    SELECT 1 FROM payment_transactions pt2
                    WHERE pt2.status = 'paid' AND pt2.item_type = 'book' AND pt2.granted_ref_id = sbs.id
                )",
                'amount' => "sbs.price",
                'date' => "sbs.created_at",
            ],
            // A course bought this way grants access through student_subscriptions, which has no
            // price column at all - unlike the book path, there was NOWHERE this money was ever
            // visible before. This is the only source of truth for it.
            'offline_payments' => [
                'label_ar' => 'دفع يدوي (تحويل بنكي)',
                'from' => "offline_payment_requests opr",
                'where' => "opr.status = 'approved' AND opr.item_type = 'course'",
                'amount' => "opr.amount",
                'date' => "COALESCE(opr.reviewed_at, opr.created_at)",
            ],
            'legacy_transactions' => [
                'label_ar' => 'معاملات قديمة',
                'from' => "transactions tr",
                'where' => "tr.direction = 'incoming' AND tr.status = 'paid'",
                'amount' => "tr.total_amount",
                'date' => "COALESCE(tr.paid_at, tr.created_at)",
            ],
        ];
    }
}

if (!function_exists('dashboard_sales_summary')) {
    function dashboard_sales_summary($con): array {
        $summary = [
            'currency' => 'USD',
            'total' => 0.0,
            'today' => 0.0,
            'this_month' => 0.0,
            'this_year' => 0.0,
            'orders_total' => 0,
            'orders_today' => 0,
            'sources' => [],
        ];

        foreach (dashboard_revenue_sources() as $key => $src) {
            $row = dashboard_fetch_row($con, "
                SELECT
                    COALESCE(SUM({$src['amount']}), 0) AS total,
                    COUNT(*) AS orders,
                    COALESCE(SUM(CASE WHEN DATE({$src['date']}) = CURDATE() THEN {$src['amount']} ELSE 0 END), 0) AS today,
                    COALESCE(SUM(CASE WHEN DATE({$src['date']}) = CURDATE() THEN 1 ELSE 0 END), 0) AS today_orders,
                    COALESCE(SUM(CASE WHEN YEAR({$src['date']}) = YEAR(CURDATE()) AND MONTH({$src['date']}) = MONTH(CURDATE()) THEN {$src['amount']} ELSE 0 END), 0) AS this_month,
                    COALESCE(SUM(CASE WHEN YEAR({$src['date']}) = YEAR(CURDATE()) THEN {$src['amount']} ELSE 0 END), 0) AS this_year
                FROM {$src['from']}
                WHERE {$src['where']}
            ");

            $summary['total'] += (float)($row['total'] ?? 0);
            $summary['today'] += (float)($row['today'] ?? 0);
            $summary['this_month'] += (float)($row['this_month'] ?? 0);
            $summary['this_year'] += (float)($row['this_year'] ?? 0);
            $summary['orders_total'] += (int)($row['orders'] ?? 0);
            $summary['orders_today'] += (int)($row['today_orders'] ?? 0);

            $summary['sources'][] = [
                'key' => $key,
                'label_ar' => $src['label_ar'],
                'total' => round((float)($row['total'] ?? 0), 2),
                'orders' => (int)($row['orders'] ?? 0),
                'today' => round((float)($row['today'] ?? 0), 2),
                'this_month' => round((float)($row['this_month'] ?? 0), 2),
            ];
        }

        $byItem = dashboard_fetch_row($con, "
            SELECT
                COALESCE(SUM(CASE WHEN item_type = 'course' THEN amount ELSE 0 END), 0) AS courses,
                COALESCE(SUM(CASE WHEN item_type = 'book' THEN amount ELSE 0 END), 0) AS books
            FROM payment_transactions WHERE status = 'paid'
        ");
        $summary['online_by_item'] = [
            'courses' => round((float)($byItem['courses'] ?? 0), 2),
            'books' => round((float)($byItem['books'] ?? 0), 2),
        ];

        // Access an admin handed out by hand carries no price anywhere in the schema, so it can
        // never appear in the money figures above. Surfacing the count keeps that gap visible
        // instead of looking like lost revenue.
        $manual = dashboard_fetch_row($con, "
            SELECT COUNT(*) AS c FROM student_subscriptions ss
            WHERE ss.subscription_code_id IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM payment_transactions pt
                WHERE pt.status = 'paid' AND pt.item_type = 'course' AND pt.granted_ref_id = ss.id
              )
        ");
        $summary['manual_grants_without_price'] = (int)($manual['c'] ?? 0);

        foreach (['total', 'today', 'this_month', 'this_year'] as $k) {
            $summary[$k] = round($summary[$k], 2);
        }

        return $summary;
    }
}

if (!function_exists('dashboard_sales_by_month')) {
    function dashboard_sales_by_month($con, int $year): array {
        $labels = dashboard_month_labels();
        $buckets = [];
        for ($m = 1; $m <= 12; $m++) {
            $buckets[$m] = [
                'month' => $m,
                'label_ar' => $labels[$m]['ar'],
                'label_en' => $labels[$m]['en'],
                'revenue' => 0.0,
                'orders' => 0,
            ];
        }

        foreach (dashboard_revenue_sources() as $src) {
            $rows = dashboard_fetch_all($con, "
                SELECT MONTH({$src['date']}) AS m, SUM({$src['amount']}) AS revenue, COUNT(*) AS orders
                FROM {$src['from']}
                WHERE {$src['where']} AND YEAR({$src['date']}) = {$year}
                GROUP BY MONTH({$src['date']})
            ");
            foreach ($rows as $row) {
                $m = (int)($row['m'] ?? 0);
                if ($m < 1 || $m > 12) continue;
                $buckets[$m]['revenue'] += (float)($row['revenue'] ?? 0);
                $buckets[$m]['orders'] += (int)($row['orders'] ?? 0);
            }
        }

        foreach ($buckets as $m => $b) {
            $buckets[$m]['revenue'] = round($b['revenue'], 2);
        }

        return array_values($buckets);
    }
}

if (!function_exists('dashboard_students_by_month')) {
    function dashboard_students_by_month($con, int $year): array {
        $labels = dashboard_month_labels();
        $buckets = [];
        for ($m = 1; $m <= 12; $m++) {
            $buckets[$m] = [
                'month' => $m,
                'label_ar' => $labels[$m]['ar'],
                'label_en' => $labels[$m]['en'],
                'count' => 0,
            ];
        }

        $rows = dashboard_fetch_all($con, "
            SELECT MONTH(created_at) AS m, COUNT(*) AS c
            FROM students
            WHERE YEAR(created_at) = {$year}
            GROUP BY MONTH(created_at)
        ");
        foreach ($rows as $row) {
            $m = (int)($row['m'] ?? 0);
            if ($m < 1 || $m > 12) continue;
            $buckets[$m]['count'] = (int)($row['c'] ?? 0);
        }

        return array_values($buckets);
    }
}

/**
 * A code that unlocks several courses at once has its price split evenly between them, so the
 * per-course revenue figures still add up to the platform total instead of counting the same
 * code price once per course it contains.
 */
if (!function_exists('dashboard_course_sales')) {
    function dashboard_course_sales($con, int $limit = 10): array {
        $agg = [];
        $add = function (int $courseId, int $count, float $revenue) use (&$agg) {
            if ($courseId <= 0) return;
            if (!isset($agg[$courseId])) {
                $agg[$courseId] = ['sales_count' => 0, 'revenue' => 0.0];
            }
            $agg[$courseId]['sales_count'] += $count;
            $agg[$courseId]['revenue'] += $revenue;
        };

        foreach (dashboard_fetch_all($con, "
            SELECT item_id AS course_id, COUNT(*) AS sales_count, SUM(amount) AS revenue
            FROM payment_transactions
            WHERE status = 'paid' AND item_type = 'course'
            GROUP BY item_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        foreach (dashboard_fetch_all($con, "
            SELECT scc.course_id AS course_id, COUNT(*) AS sales_count,
                   SUM(sc.price / GREATEST(cc.course_count, 1)) AS revenue
            FROM code_redemptions_log r
            JOIN subscription_codes sc ON sc.id = r.subscription_code_id
            JOIN subscription_code_courses scc ON scc.subscription_code_id = sc.id
            JOIN (
                SELECT subscription_code_id, COUNT(*) AS course_count
                FROM subscription_code_courses
                GROUP BY subscription_code_id
            ) cc ON cc.subscription_code_id = sc.id
            GROUP BY scc.course_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        foreach (dashboard_fetch_all($con, "
            SELECT ti.item_id AS course_id, COUNT(*) AS sales_count, SUM(ti.total_amount) AS revenue
            FROM transaction_items ti
            JOIN transactions tr ON tr.id = ti.transaction_id
            WHERE tr.direction = 'incoming' AND tr.status = 'paid' AND ti.item_type = 'course'
            GROUP BY ti.item_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        // Approved bank-transfer requests - see dashboard_revenue_sources() for why this is the
        // only place this money is recorded at all.
        foreach (dashboard_fetch_all($con, "
            SELECT item_id AS course_id, COUNT(*) AS sales_count, SUM(amount) AS revenue
            FROM offline_payment_requests
            WHERE status = 'approved' AND item_type = 'course'
            GROUP BY item_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        if (!$agg) return [];

        uasort($agg, function ($a, $b) {
            if ($a['revenue'] == $b['revenue']) return $b['sales_count'] <=> $a['sales_count'];
            return $b['revenue'] <=> $a['revenue'];
        });
        $top = array_slice($agg, 0, $limit, true);

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $ids = implode(',', array_map('intval', array_keys($top)));
        $courses = [];
        foreach (dashboard_fetch_all($con, "
            SELECT c.id, c.kind, c.price, c.image, c.is_active, COALESCE(ct.name, c.slug) AS name
            FROM courses c
            LEFT JOIN courses_translations ct ON ct.course_id = c.id AND ct.language_id = {$langId}
            WHERE c.id IN ({$ids})
        ") as $row) {
            $courses[(int)$row['id']] = $row;
        }

        $out = [];
        foreach ($top as $courseId => $stats) {
            $course = $courses[$courseId] ?? null;
            $out[] = [
                'course_id' => $courseId,
                'name' => $course['name'] ?? ('#' . $courseId),
                'kind' => $course['kind'] ?? 'normal',
                'image' => $course['image'] ?? null,
                'price' => isset($course['price']) ? (float)$course['price'] : null,
                'is_active' => isset($course['is_active']) ? (int)$course['is_active'] : null,
                'sales_count' => $stats['sales_count'],
                'revenue' => round($stats['revenue'], 2),
                // The course's units page: that is what "open this course" means everywhere else in the
                // dashboard. /courses/{id} on its own only redirects there.
                'link' => '/courses/' . $courseId . '/units',
            ];
        }
        return format_response_images($out);
    }
}

if (!function_exists('dashboard_status_labels')) {
    function dashboard_status_labels($status): array {
        $map = [
            'pending' => ['ar' => 'قيد المراجعة', 'en' => 'Pending'],
            'approved' => ['ar' => 'مقبول', 'en' => 'Approved'],
            'rejected' => ['ar' => 'مرفوض', 'en' => 'Rejected'],
            'cancelled' => ['ar' => 'ملغي', 'en' => 'Cancelled'],
            'completed' => ['ar' => 'مكتمل', 'en' => 'Completed'],
        ];
        $key = strtolower(trim((string)$status));
        if ($key === '') return ['ar' => '', 'en' => ''];
        return $map[$key] ?? ['ar' => $key, 'en' => $key];
    }
}

/**
 * There is no audit-log table in this project, so the feed is assembled on the fly from the
 * newest rows of the tables that represent real platform events. Each entry carries a `link`
 * that points at the admin page where that event can be acted on, and both an Arabic and an
 * English label so the dashboard can follow its own language instead of showing raw enum values.
 */
if (!function_exists('dashboard_recent_activity')) {
    function dashboard_recent_activity($con, int $limit = 15): array {
        $perSource = max(5, min($limit, 20));
        $items = [];

        $push = function (array $rows, string $type, callable $mapper) use (&$items) {
            foreach ($rows as $row) {
                $item = $mapper($row);
                if (!$item || empty($item['created_at'])) continue;
                $item['type'] = $type;
                $items[] = $item;
            }
        };

        $push(dashboard_fetch_all($con, "
            SELECT id, name, created_at FROM students ORDER BY created_at DESC, id DESC LIMIT {$perSource}
        "), 'student_registered', function ($r) {
            return [
                'title_ar' => 'طالب جديد سجّل في المنصة',
                'title_en' => 'New student registered',
                'description_ar' => $r['name'] ?? '',
                'description_en' => $r['name'] ?? '',
                'created_at' => $r['created_at'],
                'link' => '/users',
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT id, name, created_at FROM teachers ORDER BY created_at DESC, id DESC LIMIT {$perSource}
        "), 'teacher_registered', function ($r) {
            return [
                'title_ar' => 'معلم جديد انضم للمنصة',
                'title_en' => 'New teacher joined',
                'description_ar' => $r['name'] ?? '',
                'description_en' => $r['name'] ?? '',
                'created_at' => $r['created_at'],
                'link' => '/teachers',
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT id, name, created_at FROM parents ORDER BY created_at DESC, id DESC LIMIT {$perSource}
        "), 'parent_registered', function ($r) {
            return [
                'title_ar' => 'ولي أمر جديد سجّل في المنصة',
                'title_en' => 'New parent registered',
                'description_ar' => $r['name'] ?? '',
                'description_en' => $r['name'] ?? '',
                'created_at' => $r['created_at'],
                'link' => '/parents',
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT pt.id, pt.amount, pt.currency, pt.item_type, pt.item_id,
                   COALESCE(pt.paid_at, pt.created_at) AS created_at, s.name AS student_name
            FROM payment_transactions pt
            LEFT JOIN students s ON s.id = pt.student_id
            WHERE pt.status = 'paid'
            ORDER BY COALESCE(pt.paid_at, pt.created_at) DESC, pt.id DESC
            LIMIT {$perSource}
        "), 'payment', function ($r) {
            $isBook = ($r['item_type'] ?? '') === 'book';
            $money = (float)$r['amount'] . ' ' . ($r['currency'] ?? 'USD');
            $who = $r['student_name'] ?? '';
            return [
                'title_ar' => $isBook ? 'عملية شراء كتاب جديدة' : 'عملية شراء كورس جديدة',
                'title_en' => $isBook ? 'New book purchase' : 'New course purchase',
                'description_ar' => trim($who . ' - ' . $money),
                'description_en' => trim($who . ' - ' . $money),
                'amount' => (float)$r['amount'],
                'created_at' => $r['created_at'],
                'link' => $isBook ? '/books' : ('/courses/' . (int)$r['item_id'] . '/units'),
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT r.id, r.redeemed_at AS created_at, sc.code, sc.title, sc.price, s.name AS student_name
            FROM code_redemptions_log r
            JOIN subscription_codes sc ON sc.id = r.subscription_code_id
            LEFT JOIN students s ON s.id = r.student_id
            ORDER BY r.redeemed_at DESC, r.id DESC
            LIMIT {$perSource}
        "), 'code_redeemed', function ($r) {
            $what = trim(($r['student_name'] ?? '') . ' - ' . ($r['title'] ?? $r['code'] ?? ''));
            return [
                'title_ar' => 'تفعيل كود اشتراك',
                'title_en' => 'Subscription code redeemed',
                'description_ar' => $what,
                'description_en' => $what,
                'amount' => (float)($r['price'] ?? 0),
                'created_at' => $r['created_at'],
                'link' => '/codes',
                'entity_id' => (int)$r['id'],
            ];
        });

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $push(dashboard_fetch_all($con, "
            SELECT c.id, c.created_at, COALESCE(ct.name, c.slug) AS name
            FROM courses c
            LEFT JOIN courses_translations ct ON ct.course_id = c.id AND ct.language_id = {$langId}
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT {$perSource}
        "), 'course_created', function ($r) {
            return [
                'title_ar' => 'تم إنشاء كورس جديد',
                'title_en' => 'New course created',
                'description_ar' => $r['name'] ?? '',
                'description_en' => $r['name'] ?? '',
                'created_at' => $r['created_at'],
                'link' => '/courses/' . (int)$r['id'] . '/units',
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT br.id, br.status, br.created_at, s.name AS student_name
            FROM student_book_purchase_requests br
            LEFT JOIN students s ON s.id = br.student_id
            ORDER BY br.created_at DESC, br.id DESC
            LIMIT {$perSource}
        "), 'book_purchase_request', function ($r) {
            $status = dashboard_status_labels($r['status'] ?? '');
            $who = $r['student_name'] ?? '';
            return [
                'title_ar' => 'طلب شراء كتاب',
                'title_en' => 'Book purchase request',
                'description_ar' => trim($who . ' - ' . $status['ar']),
                'description_en' => trim($who . ' - ' . $status['en']),
                'status' => $r['status'] ?? null,
                'created_at' => $r['created_at'],
                'link' => '/book-purchase-requests',
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT rr.id, rr.status, rr.created_at
            FROM session_reschedule_requests rr
            ORDER BY rr.created_at DESC, rr.id DESC
            LIMIT {$perSource}
        "), 'reschedule_request', function ($r) {
            $status = dashboard_status_labels($r['status'] ?? '');
            return [
                'title_ar' => 'طلب تأجيل حصة',
                'title_en' => 'Session reschedule request',
                'description_ar' => $status['ar'],
                'description_en' => $status['en'],
                'status' => $r['status'] ?? null,
                'created_at' => $r['created_at'],
                'link' => '/reschedule-requests',
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT cs.id, cs.booked_at AS created_at, t.name AS teacher_name, s.name AS student_name
            FROM consultation_slots cs
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN students s ON s.id = cs.student_id
            WHERE cs.is_booked = 1 AND cs.booked_at IS NOT NULL
            ORDER BY cs.booked_at DESC, cs.id DESC
            LIMIT {$perSource}
        "), 'consultation_booked', function ($r) {
            $student = $r['student_name'] ?? '';
            $teacher = $r['teacher_name'] ?? '';
            return [
                'title_ar' => 'حجز استشارة',
                'title_en' => 'Consultation booked',
                'description_ar' => trim($student . ' مع ' . $teacher),
                'description_en' => trim($student . ' with ' . $teacher),
                'created_at' => $r['created_at'],
                'link' => '/consultations',
                'entity_id' => (int)$r['id'],
            ];
        });

        $push(dashboard_fetch_all($con, "
            SELECT er.id, er.request_type, er.status, er.created_at
            FROM attendance_exception_requests er
            ORDER BY er.created_at DESC, er.id DESC
            LIMIT {$perSource}
        "), 'attendance_exception_request', function ($r) {
            $isLate = ($r['request_type'] ?? '') === 'late_notice';
            $status = dashboard_status_labels($r['status'] ?? '');
            return [
                'title_ar' => $isLate ? 'إبلاغ تأخير' : 'طلب غياب بإذن',
                'title_en' => $isLate ? 'Late notice' : 'Excused absence request',
                'description_ar' => $status['ar'],
                'description_en' => $status['en'],
                'status' => $r['status'] ?? null,
                'created_at' => $r['created_at'],
                'link' => '/attendance-exceptions',
                'entity_id' => (int)$r['id'],
            ];
        });

        usort($items, function ($a, $b) {
            return strcmp((string)$b['created_at'], (string)$a['created_at']);
        });

        return array_slice($items, 0, $limit);
    }
}

if (!function_exists('adminDashboardStatistics')) {
    function adminDashboardStatistics($con) {
        $year = (int)($_GET['year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }
        $activityLimit = (int)($_GET['activity_limit'] ?? 15);
        if ($activityLimit < 1 || $activityLimit > 50) {
            $activityLimit = 15;
        }

        $adminsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM admins");
        $admins = mysqli_fetch_assoc($adminsRes);

        $teachersRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS pending FROM teachers");
        $teachers = mysqli_fetch_assoc($teachersRes);

        $studentsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN is_email_verified = 1 THEN 1 ELSE 0 END) AS verified FROM students");
        $students = mysqli_fetch_assoc($studentsRes);

        $parentsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM parents");
        $parents = mysqli_fetch_assoc($parentsRes);

        $schoolsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM schools");
        $schools = mysqli_fetch_assoc($schoolsRes);

        $subjectsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM subjects");
        $subjects = mysqli_fetch_assoc($subjectsRes);

        $citiesRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM cities");
        $cities = mysqli_fetch_assoc($citiesRes);

        $courses = dashboard_fetch_row($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM courses");

        return [
            'status' => 'success',
            'data' => [
                'statistics' => [
                    'admins' => [
                        'total' => (int)($admins['total'] ?? 0),
                        'active' => (int)($admins['active'] ?? 0)
                    ],
                    'teachers' => [
                        'total' => (int)($teachers['total'] ?? 0),
                        'active' => (int)($teachers['active'] ?? 0),
                        'pending' => (int)($teachers['pending'] ?? 0)
                    ],
                    'students' => [
                        'total' => (int)($students['total'] ?? 0),
                        'active' => (int)($students['active'] ?? 0),
                        'email_verified' => (int)($students['verified'] ?? 0)
                    ],
                    'parents' => [
                        'total' => (int)($parents['total'] ?? 0),
                        'active' => (int)($parents['active'] ?? 0)
                    ],
                    'schools' => [
                        'total' => (int)($schools['total'] ?? 0),
                        'active' => (int)($schools['active'] ?? 0)
                    ],
                    'subjects' => [
                        'total' => (int)($subjects['total'] ?? 0),
                        'active' => (int)($subjects['active'] ?? 0)
                    ],
                    'cities' => [
                        'total' => (int)($cities['total'] ?? 0),
                        'active' => (int)($cities['active'] ?? 0)
                    ],
                    'courses' => [
                        'total' => (int)($courses['total'] ?? 0),
                        'active' => (int)($courses['active'] ?? 0)
                    ]
                ],
                'sales' => dashboard_sales_summary($con),
                'top_courses' => dashboard_course_sales($con, 10),
                'charts' => [
                    'year' => $year,
                    'sales_by_month' => dashboard_sales_by_month($con, $year),
                    'students_by_month' => dashboard_students_by_month($con, $year)
                ],
                'recent_activity' => dashboard_recent_activity($con, $activityLimit)
            ]
        ];
    }
}
