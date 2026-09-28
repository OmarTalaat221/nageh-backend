<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/pricing.php';
require_once __DIR__ . '/../../../helpers/discounts.php';
require_once __DIR__ . '/../../../app/admin/pricing/manage.php';

/**
 * A teacher setting country prices for their own work.
 *
 * Teachers already set the base price of the courses they create, so the country prices belong in
 * the same hands - an admin having to enter a Saudi price for every teacher's course would mean
 * they never get entered.
 *
 * The ownership test is the same one the discount codes and the offline payment queue use, and it
 * is applied before anything is read as well as before anything is written. A teacher asking about
 * a course that is not theirs is told it does not exist rather than that they are not allowed to
 * see it: which courses exist on this platform is not their business either.
 *
 * Books are in scope here, unlike the payment queue - a teacher's book has a price they set, and
 * the reason books were left out of that queue (two review paths granting the same access twice)
 * does not apply to a price.
 */

if (!function_exists('teacher_pricing_owns_item')) {
    function teacher_pricing_owns_item($con, int $teacherId, string $itemType, int $itemId): bool {
        if ($teacherId <= 0 || $itemId <= 0) return false;

        if ($itemType === 'course') {
            return in_array($itemId, discount_teacher_course_ids($con, $teacherId), true);
        }

        if ($itemType !== 'book') return false;

        // A book is the teacher's if they created it, if they are linked to it, or if it belongs
        // to one of their courses - the same three ways a course is theirs.
        $stmt = mysqli_prepare($con, "SELECT b.id FROM course_books b
            LEFT JOIN book_teachers bt ON bt.book_id = b.id AND bt.teacher_id = ?
            WHERE b.id = ?
              AND (
                    (b.created_by_type = 'teacher' AND b.created_by_id = ?)
                 OR bt.id IS NOT NULL
                 OR b.course_id IN (SELECT c.id FROM courses c WHERE c.teacher_id = ?)
              )
            LIMIT 1");
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "iiii", $teacherId, $itemId, $teacherId, $teacherId);
        mysqli_stmt_execute($stmt);
        return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

if (!function_exists('teacher_pricing_resolve')) {
    /**
     * Returns [$itemType, $itemId, $item] or an error response. One place, because the ownership
     * check being skipped on one of four endpoints is exactly how a teacher ends up repricing
     * somebody else's course.
     */
    function teacher_pricing_resolve($con, int $teacherId, array $source) {
        $itemType = strtolower(trim((string)($source['item_type'] ?? 'course')));
        $itemId = (int)($source['item_id'] ?? 0);

        $notFound = ['status' => 'error', 'message' => app_text($con, 'العنصر ده مش موجود.', 'That item does not exist.')];

        $item = pricing_item_exists($con, $itemType, $itemId);
        if (!$item) return $notFound;
        if (!teacher_pricing_owns_item($con, $teacherId, $itemType, $itemId)) return $notFound;

        return [$itemType, $itemId, $item];
    }
}

if (!function_exists('teacherListItemPrices')) {
    function teacherListItemPrices($con, $teacherData = null) {
        $teacherId = pricing_actor($teacherData);
        if ($teacherId <= 0) return ['status' => 'error', 'message' => trans('teacher_auth_required')];

        $resolved = teacher_pricing_resolve($con, $teacherId, $_GET);
        if (isset($resolved['status'])) return $resolved;
        [$itemType, $itemId, $item] = $resolved;

        return ['status' => 'success', 'data' => pricing_item_prices_payload($con, $itemType, $itemId, $item)];
    }
}

if (!function_exists('teacherSaveItemPrices')) {
    function teacherSaveItemPrices($con, $teacherData = null) {
        $teacherId = pricing_actor($teacherData);
        if ($teacherId <= 0) return ['status' => 'error', 'message' => trans('teacher_auth_required')];

        $body = read_json_body();
        $resolved = teacher_pricing_resolve($con, $teacherId, $body);
        if (isset($resolved['status'])) return $resolved;
        [$itemType, $itemId, $item] = $resolved;

        $rows = $body['prices'] ?? [];
        if (!is_array($rows)) $rows = [];
        if (isset($body['country_id'])) {
            $rows[] = ['country_id' => $body['country_id'], 'price' => $body['price'] ?? null];
        }

        $result = pricing_save_item_prices($con, $itemType, $itemId, $rows, 'teacher', $teacherId);
        if (empty($result['ok'])) return ['status' => 'error', 'message' => $result['message']];

        return [
            'status' => 'success',
            'message' => app_text($con, 'تم حفظ الأسعار.', 'The prices were saved.'),
            'data' => pricing_item_prices_payload($con, $itemType, $itemId, $item),
        ];
    }
}

if (!function_exists('teacherDeleteItemPrice')) {
    function teacherDeleteItemPrice($con, $teacherData = null) {
        $teacherId = pricing_actor($teacherData);
        if ($teacherId <= 0) return ['status' => 'error', 'message' => trans('teacher_auth_required')];

        $body = read_json_body();
        $source = $body ?: $_GET;
        $resolved = teacher_pricing_resolve($con, $teacherId, $source);
        if (isset($resolved['status'])) return $resolved;
        [$itemType, $itemId, $item] = $resolved;

        $countryId = (int)($source['country_id'] ?? 0);
        $priceId = (int)($source['id'] ?? 0);
        if ($countryId <= 0 && $priceId <= 0) {
            return ['status' => 'error', 'message' => app_text($con, 'حدد الدولة.', 'Specify the country.')];
        }

        if (!pricing_delete_item_price($con, $itemType, $itemId, $countryId, $priceId)) {
            return ['status' => 'error', 'message' => app_text($con, 'السعر ده مش موجود أصلًا.', 'That price is not there.')];
        }

        return [
            'status' => 'success',
            'message' => app_text(
                $con,
                'تم حذف السعر. الدولة دي هترجع تشتغل بالسعر الأساسي.',
                'The price was removed. That country goes back to the base price.'
            ),
            'data' => pricing_item_prices_payload($con, $itemType, $itemId, $item),
        ];
    }
}

/**
 * The teacher's own priced items, for the list on their pricing screen.
 */
if (!function_exists('teacherListPricedItems')) {
    function teacherListPricedItems($con, $teacherData = null) {
        $teacherId = pricing_actor($teacherData);
        if ($teacherId <= 0) return ['status' => 'error', 'message' => trans('teacher_auth_required')];

        $courseIds = discount_teacher_course_ids($con, $teacherId);
        if (!$courseIds) return ['status' => 'success', 'data' => []];

        $ids = implode(',', array_map('intval', $courseIds));
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $sql = "
            SELECT p.item_type, p.item_id, COUNT(*) AS countries_count, MAX(p.updated_at) AS updated_at,
                   COALESCE(
                       (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = p.item_id AND ct.language_id = {$langId} LIMIT 1),
                       (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = p.item_id LIMIT 1)
                   ) AS item_name,
                   (SELECT c.price FROM courses c WHERE c.id = p.item_id) AS base_price
            FROM item_country_prices p
            WHERE p.item_type = 'course' AND p.item_id IN ({$ids})
            GROUP BY p.item_type, p.item_id
            ORDER BY MAX(p.updated_at) DESC
        ";

        $res = @mysqli_query($con, $sql);
        if (!$res) return ['status' => 'success', 'data' => []];

        $baseCurrency = currency_info($con, pricing_base_currency($con));
        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $basePrice = (float)$row['base_price'];
            $rows[] = [
                'item_type' => $row['item_type'],
                'item_id' => (int)$row['item_id'],
                'item_name' => $row['item_name'],
                'countries_count' => (int)$row['countries_count'],
                'base_price' => $basePrice,
                'base_price_formatted' => pricing_format_amount($con, $basePrice, $baseCurrency),
                'updated_at' => $row['updated_at'],
            ];
        }

        return ['status' => 'success', 'data' => $rows, 'base_currency' => $baseCurrency];
    }
}
