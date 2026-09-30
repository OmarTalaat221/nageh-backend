<?php
/**
 * Referral points: what a referral earns, and what a point is worth.
 *
 * Two questions the platform could not answer. How many points does a referral code earn? - an
 * admin typed a number into the approval box, per request, so two identical purchases could pay
 * different rewards. And what is a point worth? - nothing was recorded anywhere, so "80 points"
 * corresponded to no amount of money and redeeming meant an admin deducting points by hand.
 *
 * The value is held PER CURRENCY (migration 084). This platform has no exchange-rate table by
 * design - every price is a number a human typed for a country - so a single global rate would
 * mean one point is worth 1 unit whether the student is quoted in pounds or riyals. A currency
 * with no value set simply cannot be paid with points, and the student is told so.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/pricing.php';

if (!function_exists('points_setting')) {
    function points_setting($con, string $key, string $default = ''): string {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            $res = mysqli_query($con, "SELECT setting_key, setting_value FROM site_settings
                WHERE setting_key IN ('referral_commission_type','referral_commission_value','points_redeem_enabled','points_min_redeem')");
            if ($res) while ($row = mysqli_fetch_assoc($res)) $cache[$row['setting_key']] = (string)$row['setting_value'];
        }
        return array_key_exists($key, $cache) ? $cache[$key] : $default;
    }
}

if (!function_exists('points_rules')) {
    /**
     * The platform-wide earning rule and the redemption switches.
     */
    function points_rules($con): array {
        $type = strtolower(trim(points_setting($con, 'referral_commission_type', 'percent')));
        if (!in_array($type, ['percent', 'fixed'], true)) $type = 'percent';

        return [
            'commission_type' => $type,
            'commission_value' => max(0.0, (float)points_setting($con, 'referral_commission_value', '10')),
            'redeem_enabled' => points_setting($con, 'points_redeem_enabled', '1') === '1',
            'min_redeem' => max(0.0, (float)points_setting($con, 'points_min_redeem', '0')),
        ];
    }
}

if (!function_exists('points_value_for_currency')) {
    /**
     * What one point is worth in a currency, or null when points cannot be spent in it.
     *
     * Null rather than a fallback of 1.0 on purpose: a missing rate is not "one to one", it is
     * "nobody has said", and guessing it would hand a student a discount nobody priced.
     */
    function points_value_for_currency($con, string $currencyCode): ?float {
        $code = strtoupper(trim($currencyCode));
        if ($code === '') return null;

        $safe = mysqli_real_escape_string($con, $code);
        $row = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT point_value FROM point_currency_values WHERE currency_code = '{$safe}' AND is_active = 1 LIMIT 1"));
        if (!$row) return null;

        $value = (float)$row['point_value'];
        return $value > 0 ? $value : null;
    }
}

if (!function_exists('points_earned_for_purchase')) {
    /**
     * How many points a referral earns on a purchase.
     *
     * The code's own rule wins; the platform default applies when the code does not set one. A
     * code created before this existed carries ('fixed', 0.00) - which is indistinguishable from
     * somebody deliberately setting it to zero - so a zero fixed value is treated as "not set" and
     * falls through to the default. Deliberately awarding nothing is done by setting the platform
     * default to 0, or by deactivating the code.
     */
    function points_earned_for_purchase($con, ?array $affiliate, float $purchaseAmount): float {
        $rules = points_rules($con);

        $type = strtolower(trim((string)($affiliate['commission_type'] ?? '')));
        $value = (float)($affiliate['commission_value'] ?? 0);

        if (!in_array($type, ['percent', 'fixed'], true) || $value <= 0) {
            $type = $rules['commission_type'];
            $value = $rules['commission_value'];
        }

        $points = $type === 'percent'
            ? $purchaseAmount * ($value / 100)
            : $value;

        return round(max(0.0, $points), 2);
    }
}

if (!function_exists('points_balance_of')) {
    function points_balance_of($con, int $studentId): float {
        if ($studentId <= 0) return 0.0;
        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT points_balance FROM students WHERE id = " . (int)$studentId . " LIMIT 1"));
        return $row ? (float)$row['points_balance'] : 0.0;
    }
}

if (!function_exists('points_redeem_quote')) {
    /**
     * Works out what spending points would take off a bill, without spending them.
     *
     * Returns ['points' => spent, 'discount' => currency amount, 'message' => why less than asked].
     * Points are never spent beyond the bill: asking to spend 500 points against a 100-pound bill
     * spends only what covers the 100, because the remainder would vanish rather than carry over.
     */
    function points_redeem_quote($con, int $studentId, float $requestedPoints, array $currency, float $amountDue): array {
        $none = ['points' => 0.0, 'discount' => 0.0, 'message' => ''];

        if ($requestedPoints <= 0 || $amountDue <= 0) return $none;

        $rules = points_rules($con);
        if (!$rules['redeem_enabled']) {
            return array_merge($none, ['message' => app_points_text($con, 'استبدال النقاط موقوف حاليًا.', 'Redeeming points is switched off right now.')]);
        }

        $code = (string)($currency['code'] ?? '');
        $pointValue = points_value_for_currency($con, $code);
        if ($pointValue === null) {
            return array_merge($none, ['message' => app_points_text(
                $con,
                "النقاط مش متسعّرة بعملة {$code}، فمش هينفع تستخدمها هنا.",
                "Points have no value set in {$code}, so they cannot be spent on this purchase."
            )]);
        }

        $balance = points_balance_of($con, $studentId);
        if ($balance <= 0) {
            return array_merge($none, ['message' => app_points_text($con, 'رصيد نقاطك فاضي.', 'Your points balance is empty.')]);
        }

        $points = min($requestedPoints, $balance);

        if ($rules['min_redeem'] > 0 && $points < $rules['min_redeem']) {
            return array_merge($none, ['message' => app_points_text(
                $con,
                "أقل عدد نقاط تقدر تستبدله هو {$rules['min_redeem']} نقطة.",
                "The smallest number of points you can redeem is {$rules['min_redeem']}."
            )]);
        }

        $digits = (int)($currency['decimal_digits'] ?? 2);
        $discount = round($points * $pointValue, $digits);

        $message = '';
        if ($discount > $amountDue) {
            // Cap at the bill and hand back the points that were not needed, rather than burning
            // them on a discount larger than the thing being bought.
            $discount = round($amountDue, $digits);
            $points = round($discount / $pointValue, 2);
            $message = app_points_text(
                $con,
                'استخدمنا النقاط اللي تغطي المبلغ بس، والباقي فضل في رصيدك.',
                'Only the points needed to cover the amount were used; the rest stayed in your balance.'
            );
        } elseif ($points < $requestedPoints) {
            $message = app_points_text($con, 'رصيدك أقل من العدد المطلوب، فاستخدمنا المتاح.', 'Your balance is smaller than you asked for, so what you have was used.');
        }

        return ['points' => round($points, 2), 'discount' => $discount, 'message' => $message];
    }
}

if (!function_exists('points_spend')) {
    /**
     * Actually takes the points, and writes the ledger row.
     *
     * The balance is decremented with a guarded UPDATE rather than a read-then-write, so two
     * requests filed at the same moment cannot both pass the balance check and spend it twice.
     * Returns false when the balance moved underneath us.
     */
    function points_spend($con, int $studentId, float $points, string $description, string $referenceType = '', ?int $referenceId = null): bool {
        if ($studentId <= 0 || $points <= 0) return false;

        $stmt = mysqli_prepare($con, "UPDATE students SET points_balance = points_balance - ? WHERE id = ? AND points_balance >= ?");
        mysqli_stmt_bind_param($stmt, "did", $points, $studentId, $points);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) === 0) return false;

        $negative = -1 * $points;
        // `reference_type` is an ENUM('course','book','affiliate_commission','other'); anything
        // else would be stored as an empty string rather than rejected.
        $refType = in_array($referenceType, ['course', 'book', 'affiliate_commission', 'other'], true) ? $referenceType : 'other';
        $ins = mysqli_prepare($con, "INSERT INTO student_point_transactions (student_id, points, type, reference_type, reference_id, description) VALUES (?, ?, 'redeem', ?, ?, ?)");
        if ($ins) {
            mysqli_stmt_bind_param($ins, "idsis", $studentId, $negative, $refType, $referenceId, $description);
            mysqli_stmt_execute($ins);
        }

        return true;
    }
}

if (!function_exists('points_refund')) {
    /**
     * Gives points back - when a payment request they were spent on is rejected or cancelled.
     */
    function points_refund($con, int $studentId, float $points, string $description, string $referenceType = '', ?int $referenceId = null): void {
        if ($studentId <= 0 || $points <= 0) return;

        $stmt = mysqli_prepare($con, "UPDATE students SET points_balance = points_balance + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "di", $points, $studentId);
        mysqli_stmt_execute($stmt);

        $refType = in_array($referenceType, ['course', 'book', 'affiliate_commission', 'other'], true) ? $referenceType : 'other';
        $ins = mysqli_prepare($con, "INSERT INTO student_point_transactions (student_id, points, type, reference_type, reference_id, description) VALUES (?, ?, 'refund', ?, ?, ?)");
        if ($ins) {
            mysqli_stmt_bind_param($ins, "idsis", $studentId, $points, $refType, $referenceId, $description);
            mysqli_stmt_execute($ins);
        }
    }
}

if (!function_exists('points_currency_rank')) {
    /**
     * The order currencies appear in on the points screen.
     *
     * Alphabetical put Afghanis and Armenian drams above the Egyptian pound, so the two currencies
     * this platform actually sells in were on page 2 of 15. The dollar leads because it is the base
     * currency, then the Arab currencies, then the ones the rest of the world is priced in, then
     * everything else alphabetically.
     */
    function points_currency_rank(string $code): int {
        static $order = null;
        if ($order === null) {
            $order = [];
            $rank = 0;
            foreach ([
                // The base currency, first because every other figure is reasoned against it.
                'USD',
                // Arab currencies.
                'EGP', 'SAR', 'AED', 'KWD', 'QAR', 'BHD', 'OMR', 'JOD', 'LBP', 'SYP',
                'IQD', 'YER', 'LYD', 'TND', 'DZD', 'MAD', 'SDG', 'MRU', 'SOS', 'DJF', 'KMF',
                // The rest of the world's major ones.
                'EUR', 'GBP', 'TRY', 'CHF', 'CNY', 'JPY', 'CAD', 'AUD', 'INR', 'RUB', 'PKR',
            ] as $c) {
                $order[$c] = $rank++;
            }
        }

        // Everything unlisted sorts after the lot, and alphabetically among itself.
        return $order[strtoupper($code)] ?? 9999;
    }
}

if (!function_exists('points_sort_currencies')) {
    function points_sort_currencies(array $rows, string $key = 'currency_code'): array {
        usort($rows, function ($a, $b) use ($key) {
            $ra = points_currency_rank((string)($a[$key] ?? ''));
            $rb = points_currency_rank((string)($b[$key] ?? ''));
            if ($ra !== $rb) return $ra <=> $rb;
            return strcmp((string)($a[$key] ?? ''), (string)($b[$key] ?? ''));
        });
        return $rows;
    }
}

if (!function_exists('app_points_text')) {
    function app_points_text($con, string $ar, string $en): string {
        return strtolower(get_request_lang($con)) === 'en' ? $en : $ar;
    }
}

if (!function_exists('student_points_value_summary')) {
    /**
     * What a balance is worth, for the student's own screen.
     *
     * Their own country's currency leads, because that is the number that means something to
     * them; the others follow for a student who buys in more than one. `redeem_enabled` and
     * `min_redeem` come along so the page can say what is possible rather than only what is held.
     */
    function student_points_value_summary($con, float $balance, int $studentId = 0): array {
        $rules = points_rules($con);

        $preferred = '';
        if (function_exists('pricing_context')) {
            // Resolved for wherever the student is, the same way a course price is. The context
            // carries the currency as an array, not a bare code.
            $context = @pricing_context($con, $studentId);
            $preferred = strtoupper((string)($context['currency']['code'] ?? ''));
        }
        if ($preferred === '') $preferred = strtoupper(pricing_base_currency($con));

        $rows = [];
        $res = mysqli_query($con, "
            SELECT p.currency_code, p.point_value, c.symbol_ar, c.symbol_en, c.decimal_digits
            FROM point_currency_values p
            JOIN currencies c ON c.code = p.currency_code AND c.is_active = 1
            WHERE p.is_active = 1 AND p.point_value > 0");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $digits = (int)$row['decimal_digits'];
                $rows[] = [
                    'currency_code' => $row['currency_code'],
                    'symbol' => $row['symbol_ar'] ?: $row['symbol_en'],
                    'point_value' => (float)$row['point_value'],
                    'balance_worth' => round($balance * (float)$row['point_value'], $digits),
                ];
            }
        }

        $rows = points_sort_currencies($rows);

        // The student's own currency first, whatever the global order says.
        usort($rows, function ($a, $b) use ($preferred) {
            $pa = $a['currency_code'] === $preferred ? 0 : 1;
            $pb = $b['currency_code'] === $preferred ? 0 : 1;
            if ($pa !== $pb) return $pa <=> $pb;
            return points_currency_rank($a['currency_code']) <=> points_currency_rank($b['currency_code']);
        });

        return [
            'redeem_enabled' => $rules['redeem_enabled'] ? 1 : 0,
            'min_redeem' => $rules['min_redeem'],
            // Null when points are worth nothing anywhere - which is different from being worth 0.
            'primary' => $rows[0] ?? null,
            'all' => $rows,
        ];
    }
}
