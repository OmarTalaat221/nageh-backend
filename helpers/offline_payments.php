<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/payments_grant.php';
require_once __DIR__ . '/discounts.php';
require_once __DIR__ . '/pricing.php';
require_once __DIR__ . '/points.php';
require_once __DIR__ . '/admin_notifications.php';

/**
 * Offline payments: the student sends the money themselves, and someone here confirms it.
 *
 * There is no gateway on this platform. Until now that meant a student transferred the money and
 * then tried to reach a human on WhatsApp with a screenshot - nothing recorded who had paid, what
 * they had paid for, or whether anyone had looked at it yet. This gives that a queue.
 *
 * The rule the whole file is built around: filing a request grants nothing. Access is opened only
 * when a reviewer approves, and the amount the student typed is never trusted as proof of
 * anything - it is shown next to the price so a person can compare them.
 */

/**
 * Bank transfer only. The Egyptian wallets this started with do not exist outside Egypt, and the
 * platform can be run from anywhere - a bank account is the one instrument that travels.
 *
 * Still a list rather than a constant so adding another instrument later is one line here and a
 * label in the dashboards, with no migration.
 */
if (!function_exists('offline_payment_methods')) {
    function offline_payment_methods(): array {
        return ['bank'];
    }
}

// =================================================================================================
// The accounts students transfer to
// =================================================================================================

if (!function_exists('payment_account_format')) {
    function payment_account_format($con, array $row, bool $forStudent = false): array {
        $isEnglish = get_request_lang($con) === 'en';

        $out = [
            'id' => (int)$row['id'],
            'method' => $row['method'],
            'label' => $isEnglish ? ($row['label_en'] ?: $row['label_ar']) : ($row['label_ar'] ?: $row['label_en']),
            'bank_name' => $row['bank_name'] ?? null,
            'account_name' => $row['account_name'],
            'account_number' => $row['account_number'],
            'swift_code' => $row['swift_code'] ?? null,
            'currency_code' => $row['currency_code'] ?? pricing_base_currency($con),
            'currency' => currency_info($con, (string)($row['currency_code'] ?? pricing_base_currency($con))),
            'instructions' => $isEnglish
                ? ($row['instructions_en'] ?: $row['instructions_ar'])
                : ($row['instructions_ar'] ?: $row['instructions_en']),
        ];

        // The admin screen edits both languages, so it needs the raw columns as well.
        if (!$forStudent) {
            $out += [
                'label_ar' => $row['label_ar'],
                'label_en' => $row['label_en'],
                'instructions_ar' => $row['instructions_ar'],
                'instructions_en' => $row['instructions_en'],
                'is_active' => (int)$row['is_active'],
                'sort_order' => (int)$row['sort_order'],
                'created_at' => $row['created_at'],
            ];
        }

        return $out;
    }
}

if (!function_exists('payment_accounts_list')) {
    function payment_accounts_list($con, bool $activeOnly = false, string $preferCurrency = ''): array {
        $sql = "SELECT * FROM payment_accounts";
        if ($activeOnly) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY sort_order ASC, id ASC";

        $res = @mysqli_query($con, $sql);
        if (!$res) return [];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = payment_account_format($con, $row, $activeOnly);
        }

        // A student quoted in riyals is sent to the account that takes riyals. If there is no such
        // account the full list is returned rather than an empty one: somewhere to send the money
        // and a currency to sort out with support beats a screen that offers nothing at all.
        $preferCurrency = strtoupper(trim($preferCurrency));
        if ($preferCurrency !== '') {
            $matching = array_values(array_filter($rows, function ($row) use ($preferCurrency) {
                return strtoupper((string)$row['currency_code']) === $preferCurrency;
            }));
            if ($matching) return $matching;
        }

        return $rows;
    }
}

if (!function_exists('payment_account_find')) {
    function payment_account_find($con, int $id): ?array {
        $stmt = mysqli_prepare($con, "SELECT * FROM payment_accounts WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }
}

// =================================================================================================
// Pricing a request
// =================================================================================================

/**
 * What this student should be paying for this item right now, including a discount code they are
 * already holding.
 *
 * The discount is only previewed here - it is not spent. A code is consumed when access is
 * actually granted, so a request that is never approved costs the student nothing.
 */
if (!function_exists('offline_payment_quote')) {
    function offline_payment_quote($con, int $studentId, string $itemType, int $itemId, string $code = '', float $usePoints = 0.0): array {
        $item = payments_resolve_purchasable_item($con, $itemType, $itemId);
        if (!$item) {
            return ['ok' => false, 'message' => app_offline_text($con, 'العنصر ده مش موجود.', 'That item does not exist.')];
        }
        if ((int)$item['is_active'] !== 1) {
            return ['ok' => false, 'message' => app_offline_text($con, 'العنصر ده مش متاح حاليًا.', 'That item is not available right now.')];
        }

        // Priced for the student's country, so the number quoted here is the number they were
        // shown on the course page - and the currency is pinned to the request from this point on.
        $priced = pricing_for_item($con, $itemType, $itemId, (float)$item['price'], $studentId);
        $context = pricing_context($con, $studentId);
        $currency = $priced['currency'];

        $price = (float)$priced['price'];
        if ($price <= 0) {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'العنصر ده مالوش سعر، مفيش حاجة تتدفع.',
                'This item has no price, so there is nothing to pay.'
            )];
        }

        $discountAmount = 0.00;
        $discountCodeId = null;
        $discountMessage = '';

        // Discount codes only ever reduce a course price - a book is not something they apply to.
        $code = trim($code);
        if ($code !== '' && $itemType === 'course') {
            // It reads the price off the course itself, so the figure a reviewer later compares
            // against is the one the discount engine actually used.
            $check = discount_validate_code($con, $code, $studentId, [$itemId]);
            if (!empty($check['valid'])) {
                $discountAmount = (float)($check['discount_amount'] ?? 0);
                $discountCodeId = (int)($check['code']['id'] ?? 0) ?: null;
                $price = (float)($check['original_amount'] ?? $price);
            } else {
                $discountMessage = (string)($check['message'] ?? '');
            }
        }

        $digits = (int)$currency['decimal_digits'];
        $afterDiscount = max(0.0, round($price - $discountAmount, $digits));

        // Points come off AFTER the discount code, against what is actually left to pay. Applying
        // them to the pre-discount price would let a student spend points on money they were never
        // going to be charged.
        $pointsSpent = 0.00;
        $pointsDiscount = 0.00;
        $pointsMessage = '';
        $pointsBalance = points_balance_of($con, $studentId);

        if ($usePoints > 0) {
            $redeem = points_redeem_quote($con, $studentId, $usePoints, $currency, $afterDiscount);
            $pointsSpent = (float)$redeem['points'];
            $pointsDiscount = (float)$redeem['discount'];
            $pointsMessage = (string)$redeem['message'];
        }

        $final = max(0.0, round($afterDiscount - $pointsDiscount, $digits));

        $pointValue = points_value_for_currency($con, (string)$currency['code']);

        return [
            'ok' => true,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'original_amount' => round($price, $digits),
            'discount_amount' => round($discountAmount, $digits),
            'discount_code_id' => $discountCodeId,
            'discount_message' => $discountMessage,
            // What the student has, what one is worth here, and what spending them did to this
            // bill. `point_value` of null means points cannot be spent in this currency at all.
            'points_balance' => round($pointsBalance, 2),
            'point_value' => $pointValue,
            'points_spent' => round($pointsSpent, 2),
            'points_discount' => round($pointsDiscount, $digits),
            'points_message' => $pointsMessage,
            'max_points_usable' => $pointValue !== null && $pointValue > 0
                ? round(min($pointsBalance, $afterDiscount / $pointValue), 2)
                : 0.0,
            'amount_due' => $final,
            // Everything a reviewer and a student need to agree on what was quoted: which
            // currency, which country it was quoted for, and whether it was a country price at
            // all or the base price falling through.
            'currency' => $currency,
            'currency_code' => $currency['code'],
            'country_id' => $context['country_id'],
            'country_iso2' => $context['country_iso2'],
            'country_name' => $context['country_name'],
            'is_country_price' => $priced['is_country_price'],
            'amount_due_formatted' => pricing_format_amount($con, $final, $currency),
            'original_amount_formatted' => pricing_format_amount($con, $price, $currency),
        ];
    }
}

if (!function_exists('app_offline_text')) {
    function app_offline_text($con, string $ar, string $en): string {
        return get_request_lang($con) === 'en' ? $en : $ar;
    }
}

// =================================================================================================
// Reading requests
// =================================================================================================

if (!function_exists('offline_payment_format')) {
    function offline_payment_format($con, array $row, bool $forStudent = false): array {
        $out = [
            'id' => (int)$row['id'],
            'student_id' => (int)$row['student_id'],
            'item_type' => $row['item_type'],
            'item_id' => (int)$row['item_id'],
            'item_name' => $row['item_name'] ?? null,
            'original_amount' => (float)$row['original_amount'],
            'discount_amount' => (float)$row['discount_amount'],
            'amount' => (float)$row['amount'],
            'currency' => $row['currency'],
            // The currency is on the row already; this is what it is called and what sign to put
            // in front of it, so a reviewer reading a queue of requests in four currencies can
            // tell them apart at a glance instead of decoding three-letter codes.
            'currency_info' => currency_info($con, (string)$row['currency']),
            'amount_formatted' => pricing_format_amount($con, (float)$row['amount'], currency_info($con, (string)$row['currency'])),
            'original_amount_formatted' => pricing_format_amount($con, (float)$row['original_amount'], currency_info($con, (string)$row['currency'])),
            'country_id' => $row['country_id'] ? (int)$row['country_id'] : null,
            'country_name' => $row['country_name'] ?? null,
            'country_iso2' => $row['country_iso2'] ?? null,
            'sender_name' => $row['sender_name'],
            'sender_reference' => $row['sender_reference'],
            'transferred_at' => $row['transferred_at'],
            'receipt_image' => $row['receipt_image'],
            'student_note' => $row['student_note'],
            'status' => $row['status'],
            'review_note' => $row['review_note'],
            'reviewed_at' => $row['reviewed_at'],
            'created_at' => $row['created_at'],
            'payment_account_id' => $row['payment_account_id'] ? (int)$row['payment_account_id'] : null,
            'payment_account_label' => $row['payment_account_label'] ?? null,
        ];

        // A student has no business knowing which staff member looked at their receipt.
        if (!$forStudent) {
            $out += [
                'student_name' => $row['student_name'] ?? null,
                'student_email' => $row['student_email'] ?? null,
                'student_phone' => $row['student_phone'] ?? null,
                'reviewed_by_type' => $row['reviewed_by_type'],
                'reviewed_by_id' => $row['reviewed_by_id'] ? (int)$row['reviewed_by_id'] : null,
                'granted_ref_id' => $row['granted_ref_id'] ? (int)$row['granted_ref_id'] : null,
            ];
        }

        return format_response_images($out);
    }
}

/**
 * The shared SELECT. `item_name` is resolved per row type so a reviewer sees what was bought
 * rather than an id, and the amounts come back beside it so the comparison is on one line.
 */
if (!function_exists('offline_payment_base_query')) {
    function offline_payment_base_query($con): string {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        $countryNameColumn = get_request_lang($con) === 'en' ? 'co.name_en' : 'co.name_ar';

        return "
            SELECT r.*,
                   s.name AS student_name, s.email AS student_email, s.phone AS student_phone,
                   pa.label_ar AS payment_account_label,
                   co.iso2 AS country_iso2, {$countryNameColumn} AS country_name,
                   CASE r.item_type
                       WHEN 'course' THEN COALESCE(
                           (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = r.item_id AND ct.language_id = {$langId} LIMIT 1),
                           (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = r.item_id LIMIT 1))
                       ELSE COALESCE(
                           (SELECT bt.title FROM course_books_translations bt WHERE bt.book_id = r.item_id AND bt.language_id = {$langId} LIMIT 1),
                           (SELECT bt.title FROM course_books_translations bt WHERE bt.book_id = r.item_id LIMIT 1))
                   END AS item_name
            FROM offline_payment_requests r
            LEFT JOIN students s ON s.id = r.student_id
            LEFT JOIN payment_accounts pa ON pa.id = r.payment_account_id
            LEFT JOIN countries co ON co.id = r.country_id
        ";
    }
}

/**
 * The counts, and the pending money broken out by currency.
 *
 * A single `SUM(amount)` over this table used to be the whole answer. Once a course can sell for
 * 1500 Egyptian pounds in Cairo and 200 riyals in Riyadh, that sum adds pounds to riyals and
 * prints the result as one number - a figure that is not the total of anything.
 *
 * So `pending_amount` now means only what it can honestly mean: the pending total in the base
 * currency. Everything else is in `pending_amounts`, one entry per currency, for a screen to list.
 *
 * `$scopeSql` is the teacher's ownership condition, or '' for the whole platform.
 */
if (!function_exists('offline_payment_statistics')) {
    function offline_payment_statistics($con, string $scopeSql = ''): array {
        $where = $scopeSql !== '' ? " WHERE {$scopeSql}" : '';
        $baseCurrency = pricing_base_currency($con);

        $statistics = [
            'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0,
            'pending_amount' => 0.0,
            'pending_amount_currency' => $baseCurrency,
            'pending_amounts' => [],
        ];

        $res = @mysqli_query($con, "SELECT r.status, r.currency, COUNT(*) AS c, SUM(r.amount) AS total
            FROM offline_payment_requests r{$where} GROUP BY r.status, r.currency");
        if (!$res) return $statistics;

        $byCurrency = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $status = (string)$row['status'];
            if (isset($statistics[$status])) $statistics[$status] += (int)$row['c'];

            if ($status !== 'pending') continue;

            $code = strtoupper((string)$row['currency']) ?: $baseCurrency;
            $byCurrency[$code] = ($byCurrency[$code] ?? 0.0) + (float)$row['total'];
        }

        foreach ($byCurrency as $code => $total) {
            $currency = currency_info($con, $code);
            $statistics['pending_amounts'][] = [
                'currency_code' => $code,
                'currency' => $currency,
                'total' => round($total, (int)$currency['decimal_digits']),
                'total_formatted' => pricing_format_amount($con, $total, $currency),
            ];
        }

        $statistics['pending_amount'] = round($byCurrency[$baseCurrency] ?? 0.0, 2);

        return $statistics;
    }
}

// =================================================================================================
// Approving and rejecting
// =================================================================================================

/**
 * Approves a request: opens the access, spends the discount code if one was quoted, and records
 * who did it.
 *
 * Re-checks the status inside the UPDATE rather than trusting the row it just read. Two reviewers
 * with the same queue open is not a hypothetical, and without that check both would grant the
 * same access and the student would end up with two subscriptions to one course.
 */
if (!function_exists('offline_payment_approve')) {
    function offline_payment_approve($con, array $request, string $reviewerType, int $reviewerId, string $note = ''): array {
        $id = (int)$request['id'];

        $claim = mysqli_prepare($con, "UPDATE offline_payment_requests
            SET status = 'approved', reviewed_by_type = ?, reviewed_by_id = ?, reviewed_at = NOW(), review_note = ?
            WHERE id = ? AND status = 'pending'");
        if (!$claim) {
            return ['ok' => false, 'message' => app_offline_text($con, 'تعذر تنفيذ العملية.', 'The operation could not be completed.')];
        }
        mysqli_stmt_bind_param($claim, "sisi", $reviewerType, $reviewerId, $note, $id);
        mysqli_stmt_execute($claim);

        if (mysqli_stmt_affected_rows($claim) === 0) {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'الطلب ده اتراجع عليه بالفعل.',
                'This request has already been reviewed.'
            )];
        }

        $studentId = (int)$request['student_id'];
        $itemType = $request['item_type'];
        $itemId = (int)$request['item_id'];

        // Already owning it is not a failure - the money was still received, and the request
        // should close as approved rather than trapping a student between two states.
        if (payments_student_already_has_access($con, $studentId, $itemType, $itemId)) {
            return [
                'ok' => true,
                'already_had_access' => true,
                'message' => app_offline_text(
                    $con,
                    'الطلب اتقبل. الطالب كان معاه الوصول بالفعل.',
                    'Approved. The student already had access.'
                ),
            ];
        }

        $grantedId = payments_grant_access($con, $studentId, $itemType, $itemId, (float)$request['amount']);
        if ($grantedId === null) {
            // Put it back rather than leaving a request marked approved that granted nothing.
            mysqli_query($con, "UPDATE offline_payment_requests SET status = 'pending', reviewed_by_type = NULL, reviewed_by_id = NULL, reviewed_at = NULL WHERE id = {$id}");
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'تعذر فتح الوصول للطالب، والطلب رجع لحالته.',
                'Access could not be opened for the student, so the request was put back.'
            )];
        }

        $upd = mysqli_prepare($con, "UPDATE offline_payment_requests SET granted_ref_id = ? WHERE id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, "ii", $grantedId, $id);
            mysqli_stmt_execute($upd);
        }

        // The code is spent here, not when the request was filed: a request that is never
        // approved must not cost the student one of their uses.
        $codeId = $request['discount_code_id'] ? (int)$request['discount_code_id'] : 0;
        if ($codeId > 0 && $itemType === 'course' && function_exists('discount_apply_code')) {
            $codeRow = get_discount_code_details($con, $codeId);
            if ($codeRow) {
                discount_apply_code(
                    $con,
                    (string)$codeRow['code'],
                    $studentId,
                    [$itemId],
                    $reviewerType,
                    $reviewerId,
                    $grantedId
                );
            }
        }

        return [
            'ok' => true,
            'granted_ref_id' => $grantedId,
            'message' => app_offline_text($con, 'تم قبول الطلب وفتح الوصول للطالب.', 'Approved, and access has been opened for the student.'),
        ];
    }
}

if (!function_exists('offline_payment_reject')) {
    function offline_payment_reject($con, array $request, string $reviewerType, int $reviewerId, string $note): array {
        $note = trim($note);
        if ($note === '') {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'اكتب سبب الرفض — الطالب هيقراه.',
                'A reason is required - the student is shown it.'
            )];
        }

        $id = (int)$request['id'];
        $stmt = mysqli_prepare($con, "UPDATE offline_payment_requests
            SET status = 'rejected', reviewed_by_type = ?, reviewed_by_id = ?, reviewed_at = NOW(), review_note = ?
            WHERE id = ? AND status = 'pending'");
        if (!$stmt) {
            return ['ok' => false, 'message' => app_offline_text($con, 'تعذر تنفيذ العملية.', 'The operation could not be completed.')];
        }
        mysqli_stmt_bind_param($stmt, "sisi", $reviewerType, $reviewerId, $note, $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'الطلب ده اتراجع عليه بالفعل.',
                'This request has already been reviewed.'
            )];
        }

        // Points spent on a request that is refused have to come back. Done only after the UPDATE
        // reported a row, so a second reviewer racing the first cannot refund the same points
        // twice - the second UPDATE matches nothing and returns above.
        $pointsSpent = (float)($request['points_spent'] ?? 0);
        if ($pointsSpent > 0) {
            $itemType = (string)($request['item_type'] ?? '');
            points_refund(
                $con, (int)$request['student_id'], $pointsSpent,
                'استرجاع نقاط بعد رفض طلب الدفع',
                in_array($itemType, ['course', 'book'], true) ? $itemType : 'other',
                (int)($request['item_id'] ?? 0)
            );
        }

        return ['ok' => true, 'message' => app_offline_text($con, 'تم رفض الطلب.', 'The request was rejected.')];
    }
}

/**
 * The course ids a teacher may review payments for - the same ownership test the discount codes
 * use, so a teacher never sees money for work that is not theirs.
 */
if (!function_exists('offline_payment_teacher_course_ids')) {
    function offline_payment_teacher_course_ids($con, int $teacherId): array {
        return discount_teacher_course_ids($con, $teacherId);
    }
}
