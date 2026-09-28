<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/pricing.php';

/**
 * The public side of pricing: the country and currency lists every dashboard fills its selects
 * from, and the answer to "where does this request think I am".
 *
 * Open to everyone. A country list is not a secret, and the price a visitor is shown depends on
 * it - a logged-out person looking at the course catalogue needs the same answer a logged-in one
 * gets, or the price changes when they sign in for no reason they can see.
 */

if (!function_exists('pricing_lookup_student_id')) {
    function pricing_lookup_student_id($studentData): int {
        if (is_array($studentData)) return (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        if (is_object($studentData)) return (int)($studentData->id ?? $studentData->student_id ?? 0);
        return 0;
    }
}

if (!function_exists('publicListCountries')) {
    function publicListCountries($con) {
        return [
            'status' => 'success',
            'data' => countries_list($con, true, false),
        ];
    }
}

if (!function_exists('publicListCurrencies')) {
    function publicListCurrencies($con) {
        return [
            'status' => 'success',
            'data' => currencies_list($con, true, false),
        ];
    }
}

/**
 * Where this request is being priced for, and why.
 *
 * `source` is included deliberately. When a student says "it is showing me dollars", the first
 * thing anyone needs to know is whether that came from their profile, their address, or from
 * falling all the way through to the base currency - and without this they would have to guess.
 */
if (!function_exists('publicPricingContext')) {
    function publicPricingContext($con, $studentData = null) {
        $context = pricing_context($con, pricing_lookup_student_id($studentData));

        return [
            'status' => 'success',
            'data' => [
                'country' => $context['country_id'] ? [
                    'id' => $context['country_id'],
                    'iso2' => $context['country_iso2'],
                    'name' => $context['country_name'],
                ] : null,
                'currency' => $context['currency'],
                'base_currency' => $context['base_currency'],
                'source' => $context['source'],
                'country_pricing_enabled' => pricing_enabled($con),
            ],
        ];
    }
}

/**
 * A student choosing which country they are in - the currency switcher, made permanent.
 *
 * Stored on their record so it survives the session and outranks their address on every later
 * request. Sending nothing clears it and hands them back to automatic detection.
 */
if (!function_exists('studentSetPricingCountry')) {
    function studentSetPricingCountry($con, $studentData = null) {
        $studentId = pricing_lookup_student_id($studentData);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('student_auth_required')];
        }

        $body = read_json_body();
        $countryId = (int)($body['country_id'] ?? 0);
        $iso2 = strtoupper(trim((string)($body['country_code'] ?? $body['iso2'] ?? '')));

        if ($countryId <= 0 && $iso2 === '') {
            $stmt = mysqli_prepare($con, "UPDATE students SET country_id = NULL WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $studentId);
                mysqli_stmt_execute($stmt);
            }
            return [
                'status' => 'success',
                'message' => app_text($con, 'رجعنا لتحديد الدولة تلقائيًا.', 'Back to detecting your country automatically.'),
            ];
        }

        $country = $countryId > 0 ? country_find($con, $countryId) : country_find_by_iso2($con, $iso2);
        if (!$country) {
            return ['status' => 'error', 'message' => app_text($con, 'الدولة دي مش متاحة.', 'That country is not available.')];
        }

        $stmt = mysqli_prepare($con, "UPDATE students SET country_id = ? WHERE id = ?");
        if (!$stmt) return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];

        $newCountryId = (int)$country['id'];
        mysqli_stmt_bind_param($stmt, "ii", $newCountryId, $studentId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)];
        }

        $currency = currency_info($con, $country['currency_code']);

        return [
            'status' => 'success',
            'message' => app_text($con, 'تم تحديث الدولة.', 'Your country has been updated.'),
            'data' => [
                'country' => country_format($con, $country),
                'currency' => $currency,
            ],
        ];
    }
}
