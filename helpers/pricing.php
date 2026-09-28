<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/app_settings.php';

/**
 * What a given student, in a given country, pays for a given thing.
 *
 * The platform had one price per item and one currency label that nothing ever chose. A student in
 * Cairo and a student in Riyadh saw the same number with the same sign in front of it. This file
 * is the whole of the answer to "which number, and which sign".
 *
 * Two rules it is built on, and neither is negotiable:
 *
 *   1. Every price shown is a number a human typed. There is no exchange-rate table here and no
 *      conversion anywhere. A country with no entered price falls back to the base price in the
 *      base currency - the same thing every student sees today - rather than to a figure this
 *      code invented.
 *
 *   2. Nothing here may ever be the reason a page fails. A missing country, an unreachable geo
 *      provider, a currency somebody deactivated: every one of them falls back to the base
 *      currency and the base price. Pricing is a display concern until the moment money is
 *      quoted, and by then the country is pinned to the request.
 */

// =================================================================================================
// Reference data
// =================================================================================================

if (!function_exists('pricing_enabled')) {
    function pricing_enabled($con): bool {
        return app_setting_flag($con, 'country_pricing_enabled', true);
    }
}

if (!function_exists('pricing_base_currency')) {
    /**
     * The currency a price with no country entry is quoted in.
     *
     * Falls back to 'USD' rather than to an empty string: migration 067 already relabelled every
     * transaction on this platform to dollars, so an unreadable setting should land on what the
     * data already says rather than on nothing.
     */
    function pricing_base_currency($con): string {
        $settings = app_settings($con);
        $code = strtoupper(trim((string)($settings['base_currency'] ?? '')));
        return preg_match('/^[A-Z]{3}$/', $code) ? $code : 'USD';
    }
}

if (!function_exists('currency_info')) {
    /**
     * A currency row, in the language of the request. Cached for the request because a listing of
     * twenty courses would otherwise ask for the same currency twenty times.
     *
     * A code with no row still returns something usable - the code standing in for its own name
     * and symbol - because a price is more useful labelled "1500 XAF" than not shown at all.
     */
    function currency_info($con, string $code): array {
        static $cache = [];

        $code = strtoupper(trim($code));
        if ($code === '') $code = 'USD';
        if (isset($cache[$code])) return $cache[$code];

        $isEnglish = get_request_lang($con) === 'en';

        $stmt = @mysqli_prepare($con, "SELECT code, name_ar, name_en, symbol_ar, symbol_en, decimal_digits, is_active FROM currencies WHERE code = ? LIMIT 1");
        $row = null;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $code);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        }

        $cache[$code] = $row ? [
            'code' => $row['code'],
            'name' => $isEnglish ? $row['name_en'] : $row['name_ar'],
            'symbol' => $isEnglish ? $row['symbol_en'] : $row['symbol_ar'],
            'decimal_digits' => (int)$row['decimal_digits'],
            'is_active' => (int)$row['is_active'] === 1,
        ] : [
            'code' => $code,
            'name' => $code,
            'symbol' => $code,
            'decimal_digits' => 2,
            'is_active' => false,
        ];

        return $cache[$code];
    }
}

if (!function_exists('pricing_format_amount')) {
    /**
     * "1,500.00 ج.م" in Arabic, "E£ 1,500.00" in English.
     *
     * The decimal places come from the currency rather than being fixed at two: a Kuwaiti dinar
     * has three and a Japanese yen has none, and printing "¥1,500.00" is wrong in Japan in the
     * same way "1500.00 KWD" is wrong in Kuwait.
     */
    function pricing_format_amount($con, float $amount, array $currency): string {
        $number = number_format($amount, (int)$currency['decimal_digits'], '.', ',');
        return get_request_lang($con) === 'en'
            ? $currency['symbol'] . ' ' . $number
            : $number . ' ' . $currency['symbol'];
    }
}

if (!function_exists('country_format')) {
    function country_format($con, array $row, bool $forAdmin = false): array {
        $isEnglish = get_request_lang($con) === 'en';

        $out = [
            'id' => (int)$row['id'],
            'iso2' => $row['iso2'],
            'name' => $isEnglish ? $row['name_en'] : $row['name_ar'],
            'dial_code' => $row['dial_code'],
            'currency_code' => $row['currency_code'],
        ];

        if ($forAdmin) {
            $out += [
                'name_ar' => $row['name_ar'],
                'name_en' => $row['name_en'],
                'is_active' => (int)$row['is_active'],
                'sort_order' => (int)$row['sort_order'],
            ];
        }

        return $out;
    }
}

if (!function_exists('countries_list')) {
    /**
     * Every country, ordered by the priority the migration seeded and then by name in the
     * language being asked for - so an Arabic client gets an Arabic alphabetical list rather than
     * one sorted by its English spelling.
     */
    function countries_list($con, bool $activeOnly = true, bool $forAdmin = false): array {
        $nameColumn = get_request_lang($con) === 'en' ? 'name_en' : 'name_ar';
        $sql = "SELECT * FROM countries";
        if ($activeOnly) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY sort_order ASC, {$nameColumn} ASC";

        $res = @mysqli_query($con, $sql);
        if (!$res) return [];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = country_format($con, $row, $forAdmin);
        }
        return $rows;
    }
}

if (!function_exists('currencies_list')) {
    function currencies_list($con, bool $activeOnly = true, bool $forAdmin = false): array {
        $nameColumn = get_request_lang($con) === 'en' ? 'name_en' : 'name_ar';
        $sql = "SELECT * FROM currencies";
        if ($activeOnly) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY sort_order ASC, {$nameColumn} ASC";

        $res = @mysqli_query($con, $sql);
        if (!$res) return [];

        $isEnglish = get_request_lang($con) === 'en';
        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $item = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $isEnglish ? $row['name_en'] : $row['name_ar'],
                'symbol' => $isEnglish ? $row['symbol_en'] : $row['symbol_ar'],
                'decimal_digits' => (int)$row['decimal_digits'],
            ];
            if ($forAdmin) {
                $item += [
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'symbol_ar' => $row['symbol_ar'],
                    'symbol_en' => $row['symbol_en'],
                    'is_active' => (int)$row['is_active'],
                    'sort_order' => (int)$row['sort_order'],
                ];
            }
            $rows[] = $item;
        }
        return $rows;
    }
}

if (!function_exists('country_find_by_iso2')) {
    function country_find_by_iso2($con, string $iso2, bool $activeOnly = true): ?array {
        $iso2 = strtoupper(trim($iso2));
        if (!preg_match('/^[A-Z]{2}$/', $iso2)) return null;

        $sql = "SELECT * FROM countries WHERE iso2 = ?" . ($activeOnly ? " AND is_active = 1" : "") . " LIMIT 1";
        $stmt = @mysqli_prepare($con, $sql);
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, "s", $iso2);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }
}

if (!function_exists('country_find')) {
    function country_find($con, int $id, bool $activeOnly = true): ?array {
        if ($id <= 0) return null;

        $sql = "SELECT * FROM countries WHERE id = ?" . ($activeOnly ? " AND is_active = 1" : "") . " LIMIT 1";
        $stmt = @mysqli_prepare($con, $sql);
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }
}

// =================================================================================================
// Working out where the student is
// =================================================================================================

if (!function_exists('pricing_client_ip')) {
    /**
     * The visitor's address as best we can tell.
     *
     * Proxy headers are trusted here, and deliberately not anywhere else. Forging one of these
     * changes which price a person is shown - which they can already do by passing `?country=`,
     * because choosing your own country is a feature. The login lockout counts against
     * REMOTE_ADDR through its own function and is not touched by this one; there, a forgeable
     * address would let an attacker dodge the counter.
     */
    function pricing_client_ip(): string {
        $candidates = [];

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Left-most is the original client; the rest are the proxies it passed through.
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $part) $candidates[] = $part;
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) $candidates[] = $_SERVER['HTTP_X_REAL_IP'];
        $candidates[] = $_SERVER['REMOTE_ADDR'] ?? '';

        foreach ($candidates as $candidate) {
            $ip = trim((string)$candidate);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }

        return '';
    }
}

if (!function_exists('pricing_is_public_ip')) {
    function pricing_is_public_ip(string $ip): bool {
        return $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}

if (!function_exists('pricing_requested_country_code')) {
    /**
     * A country the client asked for outright: the currency switcher.
     *
     * Read from the query string, the body and a header, because the same switch has to work on a
     * GET listing, a POST quote and a client that would rather set it once for every request.
     */
    function pricing_requested_country_code(): string {
        foreach (['country', 'country_code', 'pricing_country'] as $key) {
            if (!empty($_GET[$key])) return strtoupper(trim((string)$_GET[$key]));
            if (!empty($_POST[$key])) return strtoupper(trim((string)$_POST[$key]));
        }

        // The project's own reader, not a second one: it caches the raw body statically, so
        // reading it here cannot leave the handler downstream with an empty `php://input`.
        $body = function_exists('read_json_body') ? read_json_body() : [];
        foreach (['country', 'country_code', 'pricing_country'] as $key) {
            if (!empty($body[$key])) return strtoupper(trim((string)$body[$key]));
        }

        if (!empty($_SERVER['HTTP_X_COUNTRY'])) return strtoupper(trim((string)$_SERVER['HTTP_X_COUNTRY']));

        return '';
    }
}

if (!function_exists('pricing_edge_country_code')) {
    /**
     * The country the CDN in front of us already worked out.
     *
     * Free and exact when it is there: Cloudflare, Vercel, App Engine and Fastly all resolve the
     * address at the edge and pass the answer along. Checking these before paying for a lookup is
     * the difference between an outbound HTTP request per visitor and none at all.
     */
    function pricing_edge_country_code(): string {
        $headers = [
            'HTTP_CF_IPCOUNTRY',          // Cloudflare
            'HTTP_X_VERCEL_IP_COUNTRY',   // Vercel
            'HTTP_X_APPENGINE_COUNTRY',   // Google App Engine
            'HTTP_X_GEO_COUNTRY',         // Fastly and friends, when configured
            'HTTP_X_COUNTRY_CODE',
        ];

        foreach ($headers as $header) {
            $value = strtoupper(trim((string)($_SERVER[$header] ?? '')));
            // Cloudflare sends XX for an address it cannot place and T1 for Tor.
            if (preg_match('/^[A-Z]{2}$/', $value) && $value !== 'XX' && $value !== 'T1') return $value;
        }

        return '';
    }
}

if (!function_exists('pricing_geoip_lookup')) {
    /**
     * Ask a geo provider which country an address is in, and remember the answer.
     *
     * Everything here exists to make sure this is never the slow part of a page:
     *
     *   - private and reserved addresses are never looked up at all;
     *   - a hit is cached for 30 days, a miss for 6 hours;
     *   - one lookup per request, at most;
     *   - a 2 second ceiling on the whole exchange;
     *   - and if the provider fails five times in a row, nothing asks it again for an hour.
     *
     * That last one matters more than it looks. Without it, a host that blocks outbound HTTP turns
     * every first visit from every new address into a two second wait, forever.
     */
    function pricing_geoip_lookup($con, string $ip): string {
        static $doneThisRequest = false;

        if (!pricing_is_public_ip($ip)) return '';
        if (!app_setting_flag($con, 'geoip_lookup_enabled', true)) return '';

        // A hit is good for 30 days, a miss for 6 hours - an address that moved country is rare,
        // a provider that was briefly down is not.
        $cached = pricing_geoip_cache_read($con, $ip);
        if ($cached !== null) {
            if ($cached['iso2'] !== null) return $cached['iso2'];
            if ($cached['age_seconds'] < 6 * 3600) return '';
        }

        if ($doneThisRequest) return '';
        $doneThisRequest = true;

        if (pricing_geoip_circuit_open($con)) return '';

        $settings = app_settings($con);
        $template = trim((string)($settings['geoip_provider_url'] ?? ''));
        if ($template === '' || strpos($template, '{ip}') === false) $template = 'https://ipwho.is/{ip}';

        $url = str_replace('{ip}', rawurlencode($ip), $template);
        $body = pricing_http_get($url, 2);

        if ($body === null) {
            pricing_geoip_record_failure($con);
            pricing_geoip_cache_write($con, $ip, null);
            return '';
        }

        pricing_geoip_record_success($con);

        $iso2 = pricing_extract_country_code($body);
        pricing_geoip_cache_write($con, $ip, $iso2 !== '' ? $iso2 : null);
        return $iso2;
    }
}

if (!function_exists('pricing_extract_country_code')) {
    /**
     * Digs a two-letter country code out of whatever the provider sent back.
     *
     * The provider is a setting, so this cannot assume one response shape. It looks under the key
     * names the common services use, and accepts a bare two-letter body for the services whose
     * "just the country" endpoint returns exactly that.
     */
    function pricing_extract_country_code(string $body): string {
        $body = trim($body);
        if ($body === '') return '';

        if (preg_match('/^[A-Za-z]{2}$/', $body)) return strtoupper($body);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) return '';

        // A provider that reports failure in the body rather than the status code.
        if (isset($decoded['success']) && $decoded['success'] === false) return '';
        if (isset($decoded['status']) && $decoded['status'] === 'fail') return '';
        if (!empty($decoded['error'])) return '';

        foreach (['country_code', 'countryCode', 'country_code2', 'country_a2', 'country'] as $key) {
            $value = $decoded[$key] ?? null;
            if (is_string($value) && preg_match('/^[A-Za-z]{2}$/', trim($value))) return strtoupper(trim($value));
        }

        return '';
    }
}

if (!function_exists('pricing_http_get')) {
    /**
     * A GET with a hard ceiling on how long it may take. Returns null on any failure at all -
     * a timeout, a DNS failure, a disabled extension, a non-200.
     */
    function pricing_http_get(string $url, int $timeoutSeconds): ?string {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 2,
                CURLOPT_USERAGENT => 'najeh-pricing/1.0',
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($body !== false && $code >= 200 && $code < 300) ? (string)$body : null;
        }

        // No cURL. allow_url_fopen may also be off, in which case this returns false and the
        // caller treats it as a failed lookup like any other.
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'header' => "User-Agent: najeh-pricing/1.0\r\n",
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $context);
        return $body === false ? null : (string)$body;
    }
}

if (!function_exists('pricing_geoip_cache_read')) {
    function pricing_geoip_cache_read($con, string $ip): ?array {
        $stmt = @mysqli_prepare($con, "SELECT iso2, TIMESTAMPDIFF(SECOND, looked_up_at, UTC_TIMESTAMP()) AS age_seconds
            FROM ip_country_cache WHERE ip_address = ? LIMIT 1");
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, "s", $ip);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;

        $age = (int)$row['age_seconds'];
        // A hit older than 30 days is treated as if it were not there, so it gets asked again.
        if ($row['iso2'] !== null && $age > 30 * 24 * 3600) return null;

        return ['iso2' => $row['iso2'], 'age_seconds' => $age];
    }
}

if (!function_exists('pricing_geoip_cache_write')) {
    function pricing_geoip_cache_write($con, string $ip, ?string $iso2): void {
        $stmt = @mysqli_prepare($con, "INSERT INTO ip_country_cache (ip_address, iso2, looked_up_at)
            VALUES (?, ?, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE iso2 = VALUES(iso2), looked_up_at = UTC_TIMESTAMP()");
        if (!$stmt) return;

        mysqli_stmt_bind_param($stmt, "ss", $ip, $iso2);
        @mysqli_stmt_execute($stmt);
    }
}

/**
 * The circuit breaker, kept as one reserved row in the same cache table.
 *
 * `__provider__` is not a valid IP address, so it can never collide with a real cached lookup.
 */
if (!function_exists('pricing_geoip_circuit_open')) {
    function pricing_geoip_circuit_open($con): bool {
        $res = @mysqli_query($con, "SELECT failures, TIMESTAMPDIFF(SECOND, looked_up_at, UTC_TIMESTAMP()) AS age_seconds
            FROM ip_country_cache WHERE ip_address = '__provider__' LIMIT 1");
        if (!$res) return false;

        $row = mysqli_fetch_assoc($res);
        if (!$row) return false;

        return (int)$row['failures'] >= 5 && (int)$row['age_seconds'] < 3600;
    }
}

if (!function_exists('pricing_geoip_record_failure')) {
    function pricing_geoip_record_failure($con): void {
        @mysqli_query($con, "INSERT INTO ip_country_cache (ip_address, iso2, failures, looked_up_at)
            VALUES ('__provider__', NULL, 1, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE failures = failures + 1, looked_up_at = UTC_TIMESTAMP()");
    }
}

if (!function_exists('pricing_geoip_record_success')) {
    function pricing_geoip_record_success($con): void {
        @mysqli_query($con, "UPDATE ip_country_cache SET failures = 0 WHERE ip_address = '__provider__' AND failures > 0");
    }
}

// =================================================================================================
// The request's pricing context
// =================================================================================================

if (!function_exists('pricing_context')) {
    /**
     * Which country this request is being priced for, and in which currency.
     *
     * The order is deliberate, most authoritative first:
     *
     *   1. a country the client asked for outright - the currency switcher;
     *   2. the country on the student's own record;
     *   3. the country the CDN in front of us already resolved;
     *   4. a geo-IP lookup of the address;
     *   5. nothing, which means the base currency.
     *
     * A student who has told us where they live outranks their address, because a Saudi student
     * on holiday in Cairo is still a Saudi student and should not have their prices re-denominated
     * because of where they opened the app.
     */
    function pricing_context($con, int $studentId = 0): array {
        static $cache = [];
        if (isset($cache[$studentId])) return $cache[$studentId];

        $baseCode = pricing_base_currency($con);
        $fallback = [
            'country_id' => null,
            'country_iso2' => null,
            'country_name' => null,
            'currency' => currency_info($con, $baseCode),
            'base_currency' => $baseCode,
            'source' => 'base',
        ];

        if (!pricing_enabled($con)) {
            $fallback['source'] = 'disabled';
            $cache[$studentId] = $fallback;
            return $fallback;
        }

        $country = null;
        $source = '';

        $requested = pricing_requested_country_code();
        if ($requested !== '') {
            $country = country_find_by_iso2($con, $requested);
            if ($country) $source = 'client';
        }

        if (!$country && $studentId > 0) {
            $saved = pricing_student_country($con, $studentId);
            if ($saved) { $country = $saved; $source = 'student'; }
        }

        if (!$country) {
            $edge = pricing_edge_country_code();
            if ($edge !== '') {
                $country = country_find_by_iso2($con, $edge);
                if ($country) $source = 'edge';
            }
        }

        if (!$country) {
            $iso2 = pricing_geoip_lookup($con, pricing_client_ip());
            if ($iso2 !== '') {
                $country = country_find_by_iso2($con, $iso2);
                if ($country) $source = 'geoip';
            }
        }

        if (!$country) {
            $cache[$studentId] = $fallback;
            return $fallback;
        }

        // A student whose record does not say where they are, and whose address just told us:
        // remember it, so every later request skips the lookup entirely. Only ever fills a blank -
        // it never overwrites a country the student chose.
        if ($studentId > 0 && ($source === 'edge' || $source === 'geoip')) {
            pricing_remember_student_country($con, $studentId, (int)$country['id']);
        }

        $currency = currency_info($con, $country['currency_code']);

        // A currency somebody deactivated is not one to quote in. The country stays resolved -
        // it is still where the student is - but the money goes back to the base currency, which
        // also means the country's prices are skipped, since they are in the dead currency.
        if (!$currency['is_active']) {
            $currency = currency_info($con, $baseCode);
            $source .= '_currency_inactive';
        }

        $cache[$studentId] = [
            'country_id' => (int)$country['id'],
            'country_iso2' => $country['iso2'],
            'country_name' => get_request_lang($con) === 'en' ? $country['name_en'] : $country['name_ar'],
            'currency' => $currency,
            'base_currency' => $baseCode,
            'source' => $source,
        ];

        return $cache[$studentId];
    }
}

if (!function_exists('pricing_student_country')) {
    function pricing_student_country($con, int $studentId): ?array {
        $stmt = @mysqli_prepare($con, "SELECT c.* FROM students s JOIN countries c ON c.id = s.country_id
            WHERE s.id = ? AND c.is_active = 1 LIMIT 1");
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    }
}

if (!function_exists('pricing_remember_student_country')) {
    function pricing_remember_student_country($con, int $studentId, int $countryId): void {
        $stmt = @mysqli_prepare($con, "UPDATE students SET country_id = ? WHERE id = ? AND country_id IS NULL");
        if (!$stmt) return;

        mysqli_stmt_bind_param($stmt, "ii", $countryId, $studentId);
        @mysqli_stmt_execute($stmt);
    }
}

// =================================================================================================
// Reading prices
// =================================================================================================

if (!function_exists('pricing_country_prices')) {
    /**
     * The entered prices for a set of items in one country, as [item_id => row]. One query for the
     * whole listing rather than one per course.
     */
    function pricing_country_prices($con, string $itemType, array $itemIds, int $countryId): array {
        if ($countryId <= 0 || !$itemIds) return [];
        if (!in_array($itemType, ['course', 'book'], true)) return [];

        $ids = array_values(array_unique(array_filter(array_map('intval', $itemIds), function ($id) { return $id > 0; })));
        if (!$ids) return [];

        $idList = implode(',', $ids);
        $stmt = @mysqli_prepare($con, "SELECT item_id, price, currency_code FROM item_country_prices
            WHERE item_type = ? AND country_id = ? AND is_active = 1 AND item_id IN ({$idList})");
        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, "si", $itemType, $countryId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $out = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $out[(int)$row['item_id']] = [
                'price' => (float)$row['price'],
                'currency_code' => $row['currency_code'],
            ];
        }
        return $out;
    }
}

if (!function_exists('pricing_apply')) {
    /**
     * Builds the pricing block for one item: the number to show, the currency it is in, and what
     * it would have been in the base currency.
     *
     * `price` stays a plain number under its existing key on purpose. Every client already reads
     * it, and they keep working - what changes is which number it holds. `base_price` is kept
     * beside it so a dashboard can still show what the item costs in the base currency.
     */
    function pricing_apply($con, array $context, float $basePrice, ?array $countryPrice): array {
        $baseCode = $context['base_currency'];
        $currency = $context['currency'];
        $price = $basePrice;
        $isCountryPrice = false;

        if ($countryPrice !== null) {
            $rowCurrency = currency_info($con, $countryPrice['currency_code']);
            // A price entered in a currency that has since been deactivated is not shown. Falling
            // back to the base price is the honest answer; quoting a dead currency is not.
            if ($rowCurrency['is_active']) {
                $price = (float)$countryPrice['price'];
                $currency = $rowCurrency;
                $isCountryPrice = true;
            }
        }

        // Without a country price the number on the row is the base price, so it has to be shown
        // in the base currency - not in the currency of wherever the student happens to be.
        if (!$isCountryPrice) {
            $currency = currency_info($con, $baseCode);
        }

        return [
            'price' => round($price, (int)$currency['decimal_digits']),
            'base_price' => round($basePrice, 2),
            'base_currency' => $baseCode,
            'currency' => $currency,
            'price_formatted' => pricing_format_amount($con, $price, $currency),
            'is_country_price' => $isCountryPrice,
            'price_country_id' => $isCountryPrice ? $context['country_id'] : null,
        ];
    }
}

if (!function_exists('pricing_decorate_rows')) {
    /**
     * Rewrites `price` on every row of a listing to the price for this request's country, and
     * attaches the currency beside it.
     *
     * Takes the rows by reference and does one query for the lot. Called from the listing
     * endpoints as a single line, so adding a country price cannot leave one screen showing the
     * new number and another showing the old one.
     */
    function pricing_decorate_rows($con, array &$rows, string $itemType, int $studentId = 0, string $priceKey = 'price', string $idKey = 'id'): void {
        if (!$rows) return;

        $context = pricing_context($con, $studentId);
        $ids = [];
        foreach ($rows as $row) {
            if (isset($row[$idKey])) $ids[] = (int)$row[$idKey];
        }

        $countryPrices = pricing_country_prices($con, $itemType, $ids, (int)($context['country_id'] ?? 0));

        foreach ($rows as &$row) {
            if (!isset($row[$idKey])) continue;
            // Already priced. Two of these listings hand their rows to a per-row enricher that
            // prices them and then get decorated again on the way out; running twice would take
            // the country price as the base price and report the base as 1500 alongside it.
            if (array_key_exists('is_country_price', $row)) continue;
            $basePrice = isset($row[$priceKey]) ? (float)$row[$priceKey] : 0.0;
            $block = pricing_apply($con, $context, $basePrice, $countryPrices[(int)$row[$idKey]] ?? null);

            $row[$priceKey] = $block['price'];
            $row['base_price'] = $block['base_price'];
            $row['currency'] = $block['currency'];
            $row['price_formatted'] = $block['price_formatted'];
            $row['is_country_price'] = $block['is_country_price'];
        }
        unset($row);
    }
}

if (!function_exists('pricing_decorate_row')) {
    /** The single-row form, for a details endpoint. */
    function pricing_decorate_row($con, array &$row, string $itemType, int $studentId = 0, string $priceKey = 'price', string $idKey = 'id'): void {
        $rows = [$row];
        pricing_decorate_rows($con, $rows, $itemType, $studentId, $priceKey, $idKey);
        $row = $rows[0];
    }
}

if (!function_exists('pricing_for_item')) {
    /**
     * The price of one item, for the money paths - the offline payment quote and anything else
     * that has to agree with what the student was shown.
     */
    function pricing_for_item($con, string $itemType, int $itemId, float $basePrice, int $studentId = 0): array {
        $context = pricing_context($con, $studentId);
        $countryPrices = pricing_country_prices($con, $itemType, [$itemId], (int)($context['country_id'] ?? 0));
        return pricing_apply($con, $context, $basePrice, $countryPrices[$itemId] ?? null);
    }
}

// =================================================================================================
// Writing prices
// =================================================================================================

if (!function_exists('pricing_validate_amount')) {
    /**
     * Returns the amount, or null with a reason.
     *
     * Zero is allowed and means free in that country - a real thing an admin may want, and
     * distinct from having no entry at all, which means "fall back to the base price".
     */
    function pricing_validate_amount($con, $raw, array $currency, ?string &$error): ?float {
        $error = null;

        if ($raw === null || $raw === '' || !is_numeric($raw)) {
            $error = app_text($con, 'اكتب سعرًا صحيحًا.', 'Enter a valid price.');
            return null;
        }

        $amount = (float)$raw;
        if ($amount < 0) {
            $error = app_text($con, 'السعر ما ينفعش يكون بالسالب.', 'A price cannot be negative.');
            return null;
        }
        if ($amount > 99999999) {
            $error = app_text($con, 'السعر ده كبير أكتر من اللازم.', 'That price is too large.');
            return null;
        }

        return round($amount, (int)$currency['decimal_digits']);
    }
}

if (!function_exists('pricing_upsert_country_price')) {
    /**
     * Sets one country's price for one item.
     *
     * The currency is taken from the country rather than from the caller: a price for Egypt is in
     * Egyptian pounds, and letting a dashboard send both invites a form that says "Egypt" next to
     * a figure in dollars.
     */
    function pricing_upsert_country_price($con, string $itemType, int $itemId, int $countryId, float $price, string $byType, int $byId): array {
        $country = country_find($con, $countryId, false);
        if (!$country) {
            return ['ok' => false, 'message' => app_text($con, 'الدولة دي مش موجودة.', 'That country does not exist.')];
        }

        $currencyCode = $country['currency_code'];
        $stmt = @mysqli_prepare($con, "INSERT INTO item_country_prices
                (item_type, item_id, country_id, currency_code, price, is_active, created_by_type, created_by_id)
            VALUES (?, ?, ?, ?, ?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE price = VALUES(price), currency_code = VALUES(currency_code), is_active = 1");
        if (!$stmt) {
            return ['ok' => false, 'message' => app_text($con, 'تعذر حفظ السعر.', 'The price could not be saved.')];
        }

        mysqli_stmt_bind_param($stmt, "siisdsi", $itemType, $itemId, $countryId, $currencyCode, $price, $byType, $byId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['ok' => false, 'message' => app_text($con, 'تعذر حفظ السعر.', 'The price could not be saved.')];
        }

        return ['ok' => true, 'currency_code' => $currencyCode];
    }
}

if (!function_exists('pricing_item_prices_list')) {
    /**
     * Every country price entered for one item, for the screen that edits them.
     */
    function pricing_item_prices_list($con, string $itemType, int $itemId): array {
        $isEnglish = get_request_lang($con) === 'en';
        $nameColumn = $isEnglish ? 'c.name_en' : 'c.name_ar';

        $stmt = @mysqli_prepare($con, "SELECT p.id, p.country_id, p.currency_code, p.price, p.is_active, p.updated_at,
                   c.iso2, c.name_ar, c.name_en
            FROM item_country_prices p
            JOIN countries c ON c.id = p.country_id
            WHERE p.item_type = ? AND p.item_id = ?
            ORDER BY c.sort_order ASC, {$nameColumn} ASC");
        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, "si", $itemType, $itemId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $currency = currency_info($con, $row['currency_code']);
            $rows[] = [
                'id' => (int)$row['id'],
                'country_id' => (int)$row['country_id'],
                'country_iso2' => $row['iso2'],
                'country_name' => $isEnglish ? $row['name_en'] : $row['name_ar'],
                'currency_code' => $row['currency_code'],
                'currency' => $currency,
                'price' => (float)$row['price'],
                'price_formatted' => pricing_format_amount($con, (float)$row['price'], $currency),
                'is_active' => (int)$row['is_active'],
                'updated_at' => $row['updated_at'],
            ];
        }
        return $rows;
    }
}
