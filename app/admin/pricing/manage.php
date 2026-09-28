<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/pricing.php';

/**
 * The admin side of pricing: which countries and currencies are offered, and what each item costs
 * in each of them.
 *
 * The screen this serves is the only place on the platform where a wrong number costs real money,
 * so two things are done here that are not done elsewhere:
 *
 *   - a price is always stored in the currency of the country it was entered for, taken from the
 *     country row rather than from the request, so a form cannot say "Egypt" beside a figure the
 *     client labelled as dollars;
 *   - a bulk save is all-or-nothing on validation. Every row is checked before any row is
 *     written, because half a price list is worse than none - the half that saved looks correct.
 */

if (!function_exists('pricing_actor')) {
    function pricing_actor($data): int {
        if (is_array($data)) return (int)($data['id'] ?? 0);
        if (is_object($data)) return (int)($data->id ?? 0);
        return 0;
    }
}

if (!function_exists('pricing_item_exists')) {
    /** The item, or null. Returns the base price with it, so the screen can show what it falls back to. */
    function pricing_item_exists($con, string $itemType, int $itemId): ?array {
        if (!in_array($itemType, ['course', 'book'], true) || $itemId <= 0) return null;

        $table = $itemType === 'course' ? 'courses' : 'course_books';
        $stmt = mysqli_prepare($con, "SELECT id, price FROM {$table} WHERE id = ? LIMIT 1");
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, "i", $itemId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        return ['id' => (int)$row['id'], 'price' => (float)$row['price']];
    }
}

// =================================================================================================
// Countries and currencies
// =================================================================================================

if (!function_exists('adminListCountries')) {
    function adminListCountries($con) {
        $activeOnly = isset($_GET['active_only']) && in_array((string)$_GET['active_only'], ['1', 'true'], true);
        return ['status' => 'success', 'data' => countries_list($con, $activeOnly, true)];
    }
}

if (!function_exists('adminListCurrencies')) {
    function adminListCurrencies($con) {
        $activeOnly = isset($_GET['active_only']) && in_array((string)$_GET['active_only'], ['1', 'true'], true);
        return ['status' => 'success', 'data' => currencies_list($con, $activeOnly, true)];
    }
}

/**
 * Turning a country on or off, moving it up the list, or changing which currency its prices are
 * entered in.
 *
 * The names are not editable. They come from ICU and are the same spellings the rest of the
 * user's software uses; an admin retyping "Egypt" as "egypt" would only ever make the list worse.
 */
if (!function_exists('adminUpdateCountry')) {
    function adminUpdateCountry($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) return ['status' => 'error', 'message' => app_text($con, 'حدد الدولة.', 'Specify the country.')];

        $country = country_find($con, $id, false);
        if (!$country) return ['status' => 'error', 'message' => app_text($con, 'الدولة دي مش موجودة.', 'That country does not exist.')];

        $fields = [];
        $params = [];
        $types = '';

        if (array_key_exists('is_active', $body)) {
            $fields[] = '`is_active` = ?';
            $params[] = (int)(bool)$body['is_active'];
            $types .= 'i';
        }
        if (array_key_exists('sort_order', $body)) {
            $fields[] = '`sort_order` = ?';
            $params[] = (int)$body['sort_order'];
            $types .= 'i';
        }
        if (array_key_exists('currency_code', $body)) {
            $code = strtoupper(trim((string)$body['currency_code']));
            if (!currency_info($con, $code)['is_active']) {
                return ['status' => 'error', 'message' => app_text($con, 'العملة دي مش موجودة أو مش مفعّلة.', 'That currency does not exist or is not active.')];
            }
            $fields[] = '`currency_code` = ?';
            $params[] = $code;
            $types .= 's';
        }

        if (!$fields) return ['status' => 'error', 'message' => app_text($con, 'مفيش حاجة تتغير.', 'Nothing to change.')];

        $params[] = $id;
        $types .= 'i';

        $stmt = mysqli_prepare($con, "UPDATE countries SET " . implode(', ', $fields) . " WHERE id = ?");
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        }

        // Prices already entered for this country keep the currency they were entered in - the
        // column on `item_country_prices` is a copy for exactly this reason. Changing a country's
        // currency decides what the NEXT price entered for it is denominated in, and says so.
        $note = array_key_exists('currency_code', $body)
            ? app_text(
                $con,
                'تم التحديث. الأسعار المتسجلة قبل كده بتفضل بعملتها القديمة لحد ما تتكتب من جديد.',
                'Updated. Prices already entered keep the currency they were entered in until they are re-entered.'
            )
            : app_text($con, 'تم التحديث.', 'Updated.');

        return ['status' => 'success', 'message' => $note];
    }
}

if (!function_exists('adminUpdateCurrency')) {
    function adminUpdateCurrency($con) {
        $body = read_json_body();
        $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
        $code = strtoupper(trim((string)($body['code'] ?? '')));

        if ($id <= 0 && $code === '') {
            return ['status' => 'error', 'message' => app_text($con, 'حدد العملة.', 'Specify the currency.')];
        }

        $map = ['is_active' => 'i', 'sort_order' => 'i', 'decimal_digits' => 'i',
                'symbol_ar' => 's', 'symbol_en' => 's'];

        $fields = [];
        $params = [];
        $types = '';

        foreach ($map as $key => $type) {
            if (!array_key_exists($key, $body)) continue;

            if ($key === 'decimal_digits') {
                $digits = (int)$body[$key];
                if ($digits < 0 || $digits > 4) {
                    return ['status' => 'error', 'message' => app_text(
                        $con,
                        'عدد الخانات العشرية لازم يكون بين 0 و 4.',
                        'The number of decimal places must be between 0 and 4.'
                    )];
                }
                $params[] = $digits;
            } elseif ($type === 'i') {
                $params[] = $key === 'is_active' ? (int)(bool)$body[$key] : (int)$body[$key];
            } else {
                $symbol = trim((string)$body[$key]);
                if ($symbol === '') {
                    return ['status' => 'error', 'message' => app_text($con, 'رمز العملة ما ينفعش يكون فاضي.', 'A currency symbol cannot be empty.')];
                }
                $params[] = mb_substr($symbol, 0, 20);
            }

            $fields[] = "`{$key}` = ?";
            $types .= $type;
        }

        if (!$fields) return ['status' => 'error', 'message' => app_text($con, 'مفيش حاجة تتغير.', 'Nothing to change.')];

        // Deactivating the base currency would leave every fallback price on the platform labelled
        // with a currency the system itself says is not in use.
        if (array_key_exists('is_active', $body) && !(bool)$body['is_active']) {
            $target = $code !== '' ? $code : pricing_currency_code_by_id($con, $id);
            if ($target !== '' && $target === pricing_base_currency($con)) {
                return ['status' => 'error', 'message' => app_text(
                    $con,
                    'دي العملة الأساسية. غيّر العملة الأساسية الأول من إعدادات التطبيق.',
                    'This is the base currency. Change the base currency in the application settings first.'
                )];
            }
        }

        $where = $id > 0 ? '`id` = ?' : '`code` = ?';
        $params[] = $id > 0 ? $id : $code;
        $types .= $id > 0 ? 'i' : 's';

        $stmt = mysqli_prepare($con, "UPDATE currencies SET " . implode(', ', $fields) . " WHERE {$where}");
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        }

        return ['status' => 'success', 'message' => app_text($con, 'تم التحديث.', 'Updated.')];
    }
}

if (!function_exists('pricing_currency_code_by_id')) {
    function pricing_currency_code_by_id($con, int $id): string {
        $stmt = mysqli_prepare($con, "SELECT code FROM currencies WHERE id = ? LIMIT 1");
        if (!$stmt) return '';
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ? (string)$row['code'] : '';
    }
}

// =================================================================================================
// An item's country prices
// =================================================================================================

if (!function_exists('pricing_item_prices_payload')) {
    /**
     * Shared by the admin and teacher screens: the entered prices, plus the base price they all
     * fall back to and the countries still available to add.
     */
    function pricing_item_prices_payload($con, string $itemType, int $itemId, array $item): array {
        $prices = pricing_item_prices_list($con, $itemType, $itemId);
        $taken = array_flip(array_map(function ($row) { return $row['country_id']; }, $prices));

        $available = array_values(array_filter(countries_list($con, true, false), function ($country) use ($taken) {
            return !isset($taken[$country['id']]);
        }));

        $baseCurrency = currency_info($con, pricing_base_currency($con));

        return [
            'item_type' => $itemType,
            'item_id' => $itemId,
            // What a student in a country with no entry of its own is quoted.
            'base_price' => $item['price'],
            'base_currency' => $baseCurrency,
            'base_price_formatted' => pricing_format_amount($con, $item['price'], $baseCurrency),
            'prices' => $prices,
            'available_countries' => $available,
        ];
    }
}

if (!function_exists('adminListItemPrices')) {
    function adminListItemPrices($con) {
        $itemType = strtolower(trim((string)($_GET['item_type'] ?? 'course')));
        $itemId = (int)($_GET['item_id'] ?? 0);

        $item = pricing_item_exists($con, $itemType, $itemId);
        if (!$item) {
            return ['status' => 'error', 'message' => app_text($con, 'العنصر ده مش موجود.', 'That item does not exist.')];
        }

        return ['status' => 'success', 'data' => pricing_item_prices_payload($con, $itemType, $itemId, $item)];
    }
}

/**
 * Saving a whole price list at once.
 *
 * Takes `prices: [{country_id, price}, ...]`, which is what the screen holds - a row per country
 * the admin added. Everything is validated first and written second, so a bad figure in the fifth
 * row does not leave the first four saved and the rest lost.
 */
if (!function_exists('pricing_save_item_prices')) {
    function pricing_save_item_prices($con, string $itemType, int $itemId, array $rows, string $byType, int $byId): array {
        if (!$rows) {
            return ['ok' => false, 'message' => app_text($con, 'مفيش أسعار مبعوتة.', 'No prices were sent.')];
        }

        $validated = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) continue;

            $countryId = (int)($row['country_id'] ?? 0);
            $country = country_find($con, $countryId, false);
            if (!$country) {
                return ['ok' => false, 'message' => app_text($con, 'واحدة من الدول المبعوتة مش موجودة.', 'One of the countries sent does not exist.')];
            }

            // The same country twice in one payload is a screen bug, and silently letting the
            // last one win would make which price you got depend on row order.
            if (isset($seen[$countryId])) {
                $name = get_request_lang($con) === 'en' ? $country['name_en'] : $country['name_ar'];
                return ['ok' => false, 'message' => app_text(
                    $con,
                    "{$name} مكررة في القايمة.",
                    "{$name} appears twice in the list."
                )];
            }
            $seen[$countryId] = true;

            $currency = currency_info($con, $country['currency_code']);
            $error = null;
            $amount = pricing_validate_amount($con, $row['price'] ?? null, $currency, $error);
            if ($amount === null) {
                $name = get_request_lang($con) === 'en' ? $country['name_en'] : $country['name_ar'];
                return ['ok' => false, 'message' => "{$name}: {$error}"];
            }

            $validated[] = ['country_id' => $countryId, 'price' => $amount];
        }

        $saved = 0;
        foreach ($validated as $row) {
            $result = pricing_upsert_country_price($con, $itemType, $itemId, $row['country_id'], $row['price'], $byType, $byId);
            if (empty($result['ok'])) return ['ok' => false, 'message' => $result['message']];
            $saved += 1;
        }

        return ['ok' => true, 'saved' => $saved];
    }
}

if (!function_exists('adminSaveItemPrices')) {
    function adminSaveItemPrices($con, $adminData = null) {
        $body = read_json_body();
        $itemType = strtolower(trim((string)($body['item_type'] ?? 'course')));
        $itemId = (int)($body['item_id'] ?? 0);

        $item = pricing_item_exists($con, $itemType, $itemId);
        if (!$item) {
            return ['status' => 'error', 'message' => app_text($con, 'العنصر ده مش موجود.', 'That item does not exist.')];
        }

        $rows = $body['prices'] ?? [];
        if (!is_array($rows)) $rows = [];

        // A single {country_id, price} is accepted too, for the "add one country" case.
        if (isset($body['country_id'])) {
            $rows[] = ['country_id' => $body['country_id'], 'price' => $body['price'] ?? null];
        }

        $result = pricing_save_item_prices($con, $itemType, $itemId, $rows, 'admin', pricing_actor($adminData));
        if (empty($result['ok'])) return ['status' => 'error', 'message' => $result['message']];

        return [
            'status' => 'success',
            'message' => app_text($con, 'تم حفظ الأسعار.', 'The prices were saved.'),
            'data' => pricing_item_prices_payload($con, $itemType, $itemId, $item),
        ];
    }
}

if (!function_exists('pricing_delete_item_price')) {
    function pricing_delete_item_price($con, string $itemType, int $itemId, int $countryId, int $priceId): bool {
        if ($priceId > 0) {
            $stmt = mysqli_prepare($con, "DELETE FROM item_country_prices WHERE id = ? AND item_type = ? AND item_id = ?");
            if (!$stmt) return false;
            mysqli_stmt_bind_param($stmt, "isi", $priceId, $itemType, $itemId);
        } else {
            $stmt = mysqli_prepare($con, "DELETE FROM item_country_prices WHERE item_type = ? AND item_id = ? AND country_id = ?");
            if (!$stmt) return false;
            mysqli_stmt_bind_param($stmt, "sii", $itemType, $itemId, $countryId);
        }

        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt) > 0;
    }
}

if (!function_exists('adminDeleteItemPrice')) {
    function adminDeleteItemPrice($con) {
        $body = read_json_body();
        $itemType = strtolower(trim((string)($body['item_type'] ?? $_GET['item_type'] ?? 'course')));
        $itemId = (int)($body['item_id'] ?? $_GET['item_id'] ?? 0);
        $countryId = (int)($body['country_id'] ?? $_GET['country_id'] ?? 0);
        $priceId = (int)($body['id'] ?? $_GET['id'] ?? 0);

        $item = pricing_item_exists($con, $itemType, $itemId);
        if (!$item) {
            return ['status' => 'error', 'message' => app_text($con, 'العنصر ده مش موجود.', 'That item does not exist.')];
        }
        if ($countryId <= 0 && $priceId <= 0) {
            return ['status' => 'error', 'message' => app_text($con, 'حدد الدولة.', 'Specify the country.')];
        }

        if (!pricing_delete_item_price($con, $itemType, $itemId, $countryId, $priceId)) {
            return ['status' => 'error', 'message' => app_text($con, 'السعر ده مش موجود أصلًا.', 'That price is not there.')];
        }

        return [
            'status' => 'success',
            // Removing a price is not the same as setting it to zero, and the difference matters
            // enough to say out loud: this country goes back to the base price.
            'message' => app_text(
                $con,
                'تم حذف السعر. الدولة دي هترجع تشتغل بالسعر الأساسي.',
                'The price was removed. That country goes back to the base price.'
            ),
            'data' => pricing_item_prices_payload($con, $itemType, $itemId, $item),
        ];
    }
}

// =================================================================================================
// The overview
// =================================================================================================

/**
 * Every item that has at least one country price, so the pricing screen is usable on its own
 * rather than only reachable one course at a time from the course list.
 */
if (!function_exists('adminListPricedItems')) {
    function adminListPricedItems($con) {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $sql = "
            SELECT p.item_type, p.item_id, COUNT(*) AS countries_count, MAX(p.updated_at) AS updated_at,
                   CASE p.item_type
                       WHEN 'course' THEN COALESCE(
                           (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = p.item_id AND ct.language_id = {$langId} LIMIT 1),
                           (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = p.item_id LIMIT 1))
                       ELSE COALESCE(
                           (SELECT bt.title FROM course_books_translations bt WHERE bt.book_id = p.item_id AND bt.language_id = {$langId} LIMIT 1),
                           (SELECT bt.title FROM course_books_translations bt WHERE bt.book_id = p.item_id LIMIT 1))
                   END AS item_name,
                   CASE p.item_type
                       WHEN 'course' THEN (SELECT c.price FROM courses c WHERE c.id = p.item_id)
                       ELSE (SELECT b.price FROM course_books b WHERE b.id = p.item_id)
                   END AS base_price
            FROM item_country_prices p
            GROUP BY p.item_type, p.item_id
            ORDER BY MAX(p.updated_at) DESC
            LIMIT 300
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
