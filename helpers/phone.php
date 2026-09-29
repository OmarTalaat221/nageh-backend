<?php
/**
 * Turning what somebody typed into their phone box into something that can be looked up.
 *
 * The same number reaches this platform written at least three ways - 01098476530, +20 109 847
 * 6530, 00201098476530 - and they are one person. The key is the last nine digits, which is the
 * part that does not change: the country code and the trunk zero are exactly what varies.
 *
 * This must produce the same answer as the `students`.`phone_key` generated column added in
 * migration 079, or a student types a number that is genuinely theirs and is told it is wrong.
 * The SQL is: NULLIF(RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 9), '')
 */

if (!function_exists('phone_key')) {

    /**
     * The lookup key for a phone number, or null when there is nothing to look up.
     */
    function phone_key(?string $raw): ?string {
        $digits = preg_replace('/[^0-9]/', '', (string)$raw);

        if ($digits === '' || $digits === null) {
            return null;
        }

        // Shorter than nine digits is not a phone number anybody can be found by. Returning it
        // anyway would let "0", typed by accident, match a stored "0".
        if (strlen($digits) < 9) {
            return null;
        }

        return substr($digits, -9);
    }
}

if (!function_exists('looks_like_phone')) {

    /**
     * Whether what was typed into a combined "email or phone" box is meant to be a phone number.
     *
     * Deliberately not "is it a valid phone" - only "is it clearly not an email". An address must
     * contain an @, so anything without one that is mostly digits is a phone attempt, and telling
     * the student "no account with this number" beats "invalid email format".
     */
    function looks_like_phone(string $value): bool {
        $value = trim($value);

        if ($value === '' || strpos($value, '@') !== false) {
            return false;
        }

        return preg_match('/^[0-9+\s().-]{6,}$/', $value) === 1;
    }
}

if (!function_exists('phone_default_dial_code')) {

    /**
     * The dial code assumed for a number typed without one. A setting rather than a constant
     * because the platform sells in more than one country and the answer changes with the market.
     */
    function phone_default_dial_code($con): string {
        static $code = null;

        if ($code === null) {
            $res = @mysqli_query($con, "SELECT setting_value FROM site_settings WHERE setting_key = 'default_phone_dial_code' LIMIT 1");
            $row = $res ? mysqli_fetch_assoc($res) : null;
            $code = preg_replace('/[^0-9]/', '', (string)($row['setting_value'] ?? '')) ?: '20';
        }

        return $code;
    }
}

if (!function_exists('phone_dial_code_exists')) {

    /** Whether a dial code is one of the countries the platform knows about. */
    function phone_dial_code_exists($con, string $dial): bool {
        $dial = preg_replace('/[^0-9]/', '', $dial);

        if ($dial === '') {
            return false;
        }

        $stmt = @mysqli_prepare($con, "SELECT 1 FROM countries WHERE dial_code = ? LIMIT 1");

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $dial);
        mysqli_stmt_execute($stmt);

        return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

if (!function_exists('phone_to_international')) {

    /**
     * The number as it should be stored: '+' followed by country code and national number.
     *
     * $dialCode is what a country picker on the form selected. When the number already carries a
     * country code of its own - it starts with + or 00 - that wins, because somebody who typed
     * +966... meant Saudi Arabia whatever the dropdown happened to be showing.
     *
     * The national trunk zero is dropped. '+20' followed by '01098476530' is not a number anybody
     * can dial, and storing it would put a digit in front of the nine that identify the student.
     *
     * Returns [international, dial_code] or [null, null] when there is nothing usable.
     */
    function phone_to_international($con, ?string $raw, ?string $dialCode = null): array {
        $raw = trim((string)$raw);

        if ($raw === '') {
            return [null, null];
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);

        if ($digits === '') {
            return [null, null];
        }

        $carriesOwnCode = str_starts_with($raw, '+') || str_starts_with($digits, '00');

        if ($carriesOwnCode) {
            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            }

            return ['+' . $digits, phone_dial_code_of($con, $digits)];
        }

        $dial = preg_replace('/[^0-9]/', '', (string)$dialCode);

        if ($dial === '' || !phone_dial_code_exists($con, $dial)) {
            $dial = phone_default_dial_code($con);
        }

        return ['+' . $dial . ltrim($digits, '0'), $dial];
    }
}

if (!function_exists('phone_dial_code_of')) {

    /**
     * Which country a set of international digits belongs to.
     *
     * Longest match wins: 1 and 1876 both prefix a Jamaican number and only the longer is right.
     */
    function phone_dial_code_of($con, string $digits): ?string {
        $digits = preg_replace('/[^0-9]/', '', $digits);

        if ($digits === '') {
            return null;
        }

        $stmt = @mysqli_prepare($con, "
            SELECT dial_code FROM countries
            WHERE dial_code <> '' AND ? LIKE CONCAT(dial_code, '%')
            ORDER BY LENGTH(dial_code) DESC LIMIT 1
        ");

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "s", $digits);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        return $row['dial_code'] ?? null;
    }
}

if (!function_exists('phone_country_code_column_exists')) {

    /** Whether migration 080 has been applied. Same reasoning as the 079 guard below. */
    function phone_country_code_column_exists($con): bool {
        static $exists = null;

        if ($exists === null) {
            $res = mysqli_query($con, "
                SELECT COUNT(*) AS n FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'students'
                  AND COLUMN_NAME = 'phone_country_code'
            ");
            $exists = $res && (int)(mysqli_fetch_assoc($res)['n'] ?? 0) > 0;
        }

        return $exists;
    }
}

if (!function_exists('phone_key_column_exists')) {

    /**
     * Whether migration 079 has been applied yet.
     *
     * Code reaches the server on a `git pull` and migrations are run by hand afterwards. During
     * that window the column does not exist, and naming it would turn every student login on the
     * platform into a fatal SQL error. Asked once per request.
     */
    function phone_key_column_exists($con): bool {
        static $exists = null;

        if ($exists === null) {
            $res = mysqli_query($con, "
                SELECT COUNT(*) AS n FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'students'
                  AND COLUMN_NAME = 'phone_key'
            ");
            $exists = $res && (int)(mysqli_fetch_assoc($res)['n'] ?? 0) > 0;
        }

        return $exists;
    }
}

if (!function_exists('phone_find_students')) {

    /**
     * Every student whose phone number matches, as ids.
     *
     * Returns more than one when a number is on more than one account, which is real: two such
     * pairs already exist. The caller decides what to do about it - the login refuses to guess,
     * registration refuses to add another.
     */
    function phone_find_students($con, ?string $rawPhone, int $excludeId = 0): array {
        if (!phone_key_column_exists($con)) {
            return [];
        }

        $key = phone_key($rawPhone);

        if ($key === null) {
            return [];
        }

        $stmt = mysqli_prepare($con, "SELECT id FROM students WHERE phone_key = ? AND id <> ?");

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, "si", $key, $excludeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $ids = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ids[] = (int)$row['id'];
        }

        return $ids;
    }
}
