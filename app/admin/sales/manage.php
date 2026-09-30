<?php
require_once __DIR__ . '/../../../helpers/sales.php';
require_once __DIR__ . '/../dashboard/statistics.php';

/**
 * The dedicated Sales screen: one overview, one filterable/searchable list per kind (courses,
 * packages, books), and the same monthly trend the home dashboard widget shows. Everything here
 * reads through helpers/sales.php and statistics.php's dashboard_* functions - never a new query
 * duplicating what "counts as revenue" a third time.
 */

if (!function_exists('sales_date_params')) {
    function sales_date_params(): array {
        $dateFrom = trim($_GET['date_from'] ?? '') ?: null;
        $dateTo = trim($_GET['date_to'] ?? '') ?: null;
        return [$dateFrom, $dateTo];
    }
}

if (!function_exists('adminSalesOverview')) {
    function adminSalesOverview($con) {
        [$dateFrom, $dateTo] = sales_date_params();
        return [
            'status' => 'success',
            'data' => sales_overview($con, $dateFrom, $dateTo),
        ];
    }
}

if (!function_exists('adminSalesItems')) {
    function adminSalesItems($con) {
        [$dateFrom, $dateTo] = sales_date_params();
        $kind = strtolower(trim($_GET['kind'] ?? 'course'));
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));

        if ($kind === 'book') {
            $rows = sales_aggregate_books($con, $dateFrom, $dateTo);
        } elseif ($kind === 'package') {
            $rows = sales_aggregate_courses($con, 'package', $dateFrom, $dateTo);
        } else {
            $kind = 'course';
            $rows = sales_aggregate_courses($con, 'normal', $dateFrom, $dateTo);
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, function ($row) use ($needle) {
                return mb_strpos(mb_strtolower($row['name'] ?? ''), $needle) !== false;
            }));
        }

        $totals = [
            'items_count' => count($rows),
            'sales_count' => array_sum(array_column($rows, 'sales_count')),
            'revenue' => round(array_sum(array_column($rows, 'revenue')), 2),
        ];

        $response = sales_paginate($rows, $page, $limit);
        $response['statistics'] = $totals;
        $response['kind'] = $kind;
        return $response;
    }
}

/**
 * The individual purchases behind one course/package/book's number on the Sales list - who
 * bought it, through which channel, and when. This is the drill-down a row on that list was
 * missing: clicking it used to open the course's own content instead of its sales.
 */
if (!function_exists('adminSalesItemDetail')) {
    function adminSalesItemDetail($con) {
        [$dateFrom, $dateTo] = sales_date_params();
        $kind = strtolower(trim($_GET['kind'] ?? 'course'));
        $itemId = (int)($_GET['id'] ?? ($kind === 'book' ? ($_GET['book_id'] ?? 0) : ($_GET['course_id'] ?? 0)));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));

        if ($itemId <= 0) {
            return ['status' => 'error', 'message' => 'id is required'];
        }

        if ($kind === 'book') {
            $langInfo = get_language_info($con);
            $langId = (int)($langInfo['id'] ?? 1);
            $bookRow = dashboard_fetch_row($con, "
                SELECT cb.id, cb.cover_image, COALESCE(cbt.title, CONCAT('#', cb.id)) AS name
                FROM course_books cb
                LEFT JOIN course_books_translations cbt ON cbt.book_id = cb.id AND cbt.language_id = {$langId}
                WHERE cb.id = {$itemId}
                LIMIT 1
            ");
            $itemInfo = format_response_images([
                'book_id' => $itemId,
                'name' => $bookRow['name'] ?? ('#' . $itemId),
                'image' => $bookRow['cover_image'] ?? null,
            ]);
            $rows = sales_book_transactions($con, $itemId, $dateFrom, $dateTo);
        } else {
            $kind = $kind === 'package' ? 'package' : 'course';
            $item = get_course_details($con, $itemId);
            $itemInfo = $item ? [
                'course_id' => $itemId,
                'name' => $item['name'] ?? ('#' . $itemId),
                'image' => $item['image'] ?? null,
                'kind' => $item['kind'] ?? 'normal',
            ] : ['course_id' => $itemId, 'name' => '#' . $itemId, 'image' => null, 'kind' => $kind];
            $rows = sales_course_transactions($con, $itemId, $dateFrom, $dateTo);
        }

        $totals = [
            'sales_count' => count($rows),
            'revenue' => round(array_sum(array_column($rows, 'amount')), 2),
        ];

        $response = sales_paginate($rows, $page, $limit);
        $response['item'] = format_response_images($itemInfo);
        $response['statistics'] = $totals;
        $response['kind'] = $kind;
        return $response;
    }
}

if (!function_exists('adminSalesTrend')) {
    function adminSalesTrend($con) {
        $year = (int)($_GET['year'] ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }
        return [
            'status' => 'success',
            'data' => [
                'year' => $year,
                'sales_by_month' => dashboard_sales_by_month($con, $year),
            ],
        ];
    }
}
