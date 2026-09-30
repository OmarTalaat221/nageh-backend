<?php
require_once __DIR__ . '/entities.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/upload.php';

/**
 * The admin dashboard's "top 10 courses" widget (app/admin/dashboard/statistics.php) already
 * aggregates course revenue, but as a fixed top-10 with no date range, no search and no way to
 * separate a package from an ordinary course. The dedicated Sales page needs all four, so this
 * file re-implements the same revenue sources with those added, rather than bending the widget's
 * function to a shape it was never meant to have.
 *
 * The anti-double-counting rules are identical to dashboard_revenue_sources() in statistics.php -
 * see that function's own comment for why each guard exists. Both files must be read together
 * when a new payment path is added, or the two pictures of "how much was this course worth" will
 * quietly drift apart.
 */

if (!function_exists('sales_date_filter')) {
    function sales_date_filter(string $column, ?string $dateFrom, ?string $dateTo): string {
        $clauses = [];
        if (!empty($dateFrom)) {
            $clauses[] = "{$column} >= '" . date('Y-m-d 00:00:00', strtotime($dateFrom)) . "'";
        }
        if (!empty($dateTo)) {
            $clauses[] = "{$column} <= '" . date('Y-m-d 23:59:59', strtotime($dateTo)) . "'";
        }
        return $clauses ? ' AND ' . implode(' AND ', $clauses) : '';
    }
}

if (!function_exists('sales_aggregate_courses')) {
    /**
     * @param string|null $kind 'normal' | 'package' | null (both)
     */
    function sales_aggregate_courses($con, ?string $kind, ?string $dateFrom, ?string $dateTo): array {
        $agg = [];
        $add = function (int $courseId, int $count, float $revenue) use (&$agg) {
            if ($courseId <= 0) return;
            if (!isset($agg[$courseId])) {
                $agg[$courseId] = ['sales_count' => 0, 'revenue' => 0.0];
            }
            $agg[$courseId]['sales_count'] += $count;
            $agg[$courseId]['revenue'] += $revenue;
        };

        $onlineDate = sales_date_filter('COALESCE(pt.paid_at, pt.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT pt.item_id AS course_id, COUNT(*) AS sales_count, SUM(pt.amount) AS revenue
            FROM payment_transactions pt
            WHERE pt.status = 'paid' AND pt.item_type = 'course' {$onlineDate}
            GROUP BY pt.item_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        $codeDate = sales_date_filter('r.redeemed_at', $dateFrom, $dateTo);
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
            WHERE 1 = 1 {$codeDate}
            GROUP BY scc.course_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        $legacyDate = sales_date_filter('COALESCE(tr.paid_at, tr.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT ti.item_id AS course_id, COUNT(*) AS sales_count, SUM(ti.total_amount) AS revenue
            FROM transaction_items ti
            JOIN transactions tr ON tr.id = ti.transaction_id
            WHERE tr.direction = 'incoming' AND tr.status = 'paid' AND ti.item_type = 'course' {$legacyDate}
            GROUP BY ti.item_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        $offlineDate = sales_date_filter('COALESCE(opr.reviewed_at, opr.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT opr.item_id AS course_id, COUNT(*) AS sales_count, SUM(opr.amount) AS revenue
            FROM offline_payment_requests opr
            WHERE opr.status = 'approved' AND opr.item_type = 'course' {$offlineDate}
            GROUP BY opr.item_id
        ") as $row) {
            $add((int)$row['course_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        if (!$agg) return [];

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $ids = implode(',', array_map('intval', array_keys($agg)));
        $kindWhere = $kind === 'package' ? "AND c.kind = 'package'" : ($kind === 'normal' ? "AND c.kind <> 'package'" : '');

        $courses = [];
        foreach (dashboard_fetch_all($con, "
            SELECT c.id, c.kind, c.price, c.image, c.is_active, COALESCE(ct.name, c.slug) AS name
            FROM courses c
            LEFT JOIN courses_translations ct ON ct.course_id = c.id AND ct.language_id = {$langId}
            WHERE c.id IN ({$ids}) {$kindWhere}
        ") as $row) {
            $courses[(int)$row['id']] = $row;
        }

        $out = [];
        foreach ($agg as $courseId => $stats) {
            $course = $courses[$courseId] ?? null;
            if (!$course) continue; // filtered out by $kind, or the course no longer exists
            $out[] = [
                'course_id' => $courseId,
                'name' => $course['name'] ?? ('#' . $courseId),
                'kind' => $course['kind'] ?? 'normal',
                'image' => $course['image'] ?? null,
                'price' => isset($course['price']) ? (float)$course['price'] : null,
                'is_active' => isset($course['is_active']) ? (int)$course['is_active'] : null,
                'sales_count' => $stats['sales_count'],
                'revenue' => round($stats['revenue'], 2),
                'link' => '/courses/' . $courseId . '/units',
            ];
        }

        usort($out, function ($a, $b) {
            if ($a['revenue'] == $b['revenue']) return $b['sales_count'] <=> $a['sales_count'];
            return $b['revenue'] <=> $a['revenue'];
        });

        return format_response_images($out);
    }
}

if (!function_exists('sales_aggregate_books')) {
    function sales_aggregate_books($con, ?string $dateFrom, ?string $dateTo): array {
        $agg = [];
        $add = function (int $bookId, int $count, float $revenue) use (&$agg) {
            if ($bookId <= 0) return;
            if (!isset($agg[$bookId])) {
                $agg[$bookId] = ['sales_count' => 0, 'revenue' => 0.0];
            }
            $agg[$bookId]['sales_count'] += $count;
            $agg[$bookId]['revenue'] += $revenue;
        };

        $onlineDate = sales_date_filter('COALESCE(pt.paid_at, pt.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT pt.item_id AS book_id, COUNT(*) AS sales_count, SUM(pt.amount) AS revenue
            FROM payment_transactions pt
            WHERE pt.status = 'paid' AND pt.item_type = 'book' {$onlineDate}
            GROUP BY pt.item_id
        ") as $row) {
            $add((int)$row['book_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        // Manual admin/teacher grants AND approved offline (bank transfer) payments both land
        // here with no payment_transactions row of their own - see helpers/sales.php's own
        // top-of-file note and dashboard_revenue_sources()'s offline_book_grants entry.
        $manualDate = sales_date_filter('sbs.created_at', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT sbs.book_id AS book_id, COUNT(*) AS sales_count, SUM(sbs.price) AS revenue
            FROM student_book_subscriptions sbs
            WHERE sbs.price > 0 {$manualDate} AND NOT EXISTS (
                SELECT 1 FROM payment_transactions pt2
                WHERE pt2.status = 'paid' AND pt2.item_type = 'book' AND pt2.granted_ref_id = sbs.id
            )
            GROUP BY sbs.book_id
        ") as $row) {
            $add((int)$row['book_id'], (int)$row['sales_count'], (float)$row['revenue']);
        }

        if (!$agg) return [];

        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);
        $ids = implode(',', array_map('intval', array_keys($agg)));

        $books = [];
        foreach (dashboard_fetch_all($con, "
            SELECT cb.id, cb.price, cb.cover_image, cb.is_active,
                   COALESCE(cbt.title, CONCAT('#', cb.id)) AS name
            FROM course_books cb
            LEFT JOIN course_books_translations cbt ON cbt.book_id = cb.id AND cbt.language_id = {$langId}
            WHERE cb.id IN ({$ids})
        ") as $row) {
            $books[(int)$row['id']] = $row;
        }

        $out = [];
        foreach ($agg as $bookId => $stats) {
            $book = $books[$bookId] ?? null;
            $out[] = [
                'book_id' => $bookId,
                'name' => $book['name'] ?? ('#' . $bookId),
                'image' => $book['cover_image'] ?? null,
                'price' => isset($book['price']) ? (float)$book['price'] : null,
                'is_active' => isset($book['is_active']) ? (int)$book['is_active'] : null,
                'sales_count' => $stats['sales_count'],
                'revenue' => round($stats['revenue'], 2),
                'link' => '/books',
            ];
        }

        usort($out, function ($a, $b) {
            if ($a['revenue'] == $b['revenue']) return $b['sales_count'] <=> $a['sales_count'];
            return $b['revenue'] <=> $a['revenue'];
        });

        return format_response_images($out);
    }
}

/**
 * The individual sales behind one course/package's aggregate number - who bought it, through
 * which channel, when, and for how much. Same four channels as sales_aggregate_courses(), just
 * kept as separate rows instead of summed into one.
 */
if (!function_exists('sales_course_transactions')) {
    function sales_course_transactions($con, int $courseId, ?string $dateFrom, ?string $dateTo): array {
        $rows = [];

        $onlineDate = sales_date_filter('COALESCE(pt.paid_at, pt.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT pt.id, pt.student_id, pt.amount, COALESCE(pt.paid_at, pt.created_at) AS occurred_at,
                   pt.easykash_ref AS reference, pt.payment_method
            FROM payment_transactions pt
            WHERE pt.status = 'paid' AND pt.item_type = 'course' AND pt.item_id = {$courseId} {$onlineDate}
            ORDER BY occurred_at DESC
        ") as $row) {
            $rows[] = [
                'source' => 'online_payment',
                'source_label_ar' => 'دفع إلكتروني',
                'source_label_en' => 'Online payment',
                'student_id' => (int)$row['student_id'],
                'amount' => (float)$row['amount'],
                'occurred_at' => $row['occurred_at'],
                'reference' => $row['reference'] ?: ($row['payment_method'] ?? null),
            ];
        }

        $codeDate = sales_date_filter('r.redeemed_at', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT r.id, r.student_id, r.redeemed_at AS occurred_at, sc.code AS reference,
                   sc.price / GREATEST((SELECT COUNT(*) FROM subscription_code_courses WHERE subscription_code_id = sc.id), 1) AS amount
            FROM code_redemptions_log r
            JOIN subscription_codes sc ON sc.id = r.subscription_code_id
            JOIN subscription_code_courses scc ON scc.subscription_code_id = sc.id AND scc.course_id = {$courseId}
            WHERE 1 = 1 {$codeDate}
            ORDER BY occurred_at DESC
        ") as $row) {
            $rows[] = [
                'source' => 'subscription_code',
                'source_label_ar' => 'كود اشتراك',
                'source_label_en' => 'Subscription code',
                'student_id' => (int)$row['student_id'],
                'amount' => (float)$row['amount'],
                'occurred_at' => $row['occurred_at'],
                'reference' => $row['reference'],
            ];
        }

        $offlineDate = sales_date_filter('COALESCE(opr.reviewed_at, opr.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT opr.id, opr.student_id, opr.amount, COALESCE(opr.reviewed_at, opr.created_at) AS occurred_at,
                   opr.sender_reference AS reference
            FROM offline_payment_requests opr
            WHERE opr.status = 'approved' AND opr.item_type = 'course' AND opr.item_id = {$courseId} {$offlineDate}
            ORDER BY occurred_at DESC
        ") as $row) {
            $rows[] = [
                'source' => 'offline_payment',
                'source_label_ar' => 'دفع يدوي (تحويل بنكي)',
                'source_label_en' => 'Offline (bank transfer)',
                'student_id' => (int)$row['student_id'],
                'amount' => (float)$row['amount'],
                'occurred_at' => $row['occurred_at'],
                'reference' => $row['reference'],
            ];
        }

        $legacyDate = sales_date_filter('COALESCE(tr.paid_at, tr.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT ti.id, tr.student_id, ti.total_amount AS amount, COALESCE(tr.paid_at, tr.created_at) AS occurred_at,
                   tr.transaction_code AS reference
            FROM transaction_items ti
            JOIN transactions tr ON tr.id = ti.transaction_id
            WHERE tr.direction = 'incoming' AND tr.status = 'paid' AND ti.item_type = 'course' AND ti.item_id = {$courseId} {$legacyDate}
            ORDER BY occurred_at DESC
        ") as $row) {
            $rows[] = [
                'source' => 'legacy_transaction',
                'source_label_ar' => 'معاملة قديمة',
                'source_label_en' => 'Legacy transaction',
                'student_id' => $row['student_id'] ? (int)$row['student_id'] : 0,
                'amount' => (float)$row['amount'],
                'occurred_at' => $row['occurred_at'],
                'reference' => $row['reference'],
            ];
        }

        usort($rows, fn($a, $b) => strcmp((string)$b['occurred_at'], (string)$a['occurred_at']));

        foreach ($rows as &$row) {
            $student = $row['student_id'] > 0 ? get_student_details($con, $row['student_id']) : null;
            $row['student_name'] = $student['name'] ?? null;
            $row['student_image'] = $student['image'] ?? null;
            $row['amount'] = round($row['amount'], 2);
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('sales_book_transactions')) {
    function sales_book_transactions($con, int $bookId, ?string $dateFrom, ?string $dateTo): array {
        $rows = [];

        $onlineDate = sales_date_filter('COALESCE(pt.paid_at, pt.created_at)', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT pt.id, pt.student_id, pt.amount, COALESCE(pt.paid_at, pt.created_at) AS occurred_at,
                   pt.easykash_ref AS reference, pt.payment_method
            FROM payment_transactions pt
            WHERE pt.status = 'paid' AND pt.item_type = 'book' AND pt.item_id = {$bookId} {$onlineDate}
            ORDER BY occurred_at DESC
        ") as $row) {
            $rows[] = [
                'source' => 'online_payment',
                'source_label_ar' => 'دفع إلكتروني',
                'source_label_en' => 'Online payment',
                'student_id' => (int)$row['student_id'],
                'amount' => (float)$row['amount'],
                'occurred_at' => $row['occurred_at'],
                'reference' => $row['reference'] ?: ($row['payment_method'] ?? null),
            ];
        }

        $manualDate = sales_date_filter('sbs.created_at', $dateFrom, $dateTo);
        foreach (dashboard_fetch_all($con, "
            SELECT sbs.id, sbs.student_id, sbs.price AS amount, sbs.created_at AS occurred_at,
                   sbs.granted_by_type AS reference
            FROM student_book_subscriptions sbs
            WHERE sbs.price > 0 AND sbs.book_id = {$bookId} {$manualDate} AND NOT EXISTS (
                SELECT 1 FROM payment_transactions pt2
                WHERE pt2.status = 'paid' AND pt2.item_type = 'book' AND pt2.granted_ref_id = sbs.id
            )
            ORDER BY occurred_at DESC
        ") as $row) {
            $rows[] = [
                'source' => 'offline_payment',
                'source_label_ar' => 'منح يدوي / دفع يدوي',
                'source_label_en' => 'Manual grant / offline payment',
                'student_id' => (int)$row['student_id'],
                'amount' => (float)$row['amount'],
                'occurred_at' => $row['occurred_at'],
                'reference' => $row['reference'],
            ];
        }

        usort($rows, fn($a, $b) => strcmp((string)$b['occurred_at'], (string)$a['occurred_at']));

        foreach ($rows as &$row) {
            $student = $row['student_id'] > 0 ? get_student_details($con, $row['student_id']) : null;
            $row['student_name'] = $student['name'] ?? null;
            $row['student_image'] = $student['image'] ?? null;
            $row['amount'] = round($row['amount'], 2);
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('sales_paginate')) {
    function sales_paginate(array $rows, int $page, int $limit): array {
        $total = count($rows);
        $offset = ($page - 1) * $limit;
        $page_rows = array_slice($rows, $offset, $limit);
        return build_paginated_response($page_rows, $page, $limit, $total);
    }
}

/**
 * The overview cards + monthly trend, with an optional date range applied to the totals (today /
 * this month / this year stay fixed reference points regardless of the filter, since collapsing
 * them to match a custom range would make the cards answer a different question every time).
 */
if (!function_exists('sales_overview')) {
    function sales_overview($con, ?string $dateFrom, ?string $dateTo): array {
        $summary = dashboard_sales_summary($con);

        if (!empty($dateFrom) || !empty($dateTo)) {
            $rangeTotal = 0.0;
            $rangeOrders = 0;
            foreach (dashboard_revenue_sources() as $src) {
                $dateExpr = sales_date_filter($src['date'], $dateFrom, $dateTo);
                $row = dashboard_fetch_row($con, "
                    SELECT COALESCE(SUM({$src['amount']}), 0) AS total, COUNT(*) AS orders
                    FROM {$src['from']}
                    WHERE {$src['where']} {$dateExpr}
                ");
                $rangeTotal += (float)($row['total'] ?? 0);
                $rangeOrders += (int)($row['orders'] ?? 0);
            }
            $summary['range'] = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total' => round($rangeTotal, 2),
                'orders' => $rangeOrders,
            ];
        }

        return $summary;
    }
}
