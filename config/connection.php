<?php

$con = mysqli_connect(
    "localhost",
    "campcod1_najeh",
    "campcod1_najeh",
    "campcod1_najeh"
);

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$con->set_charset("utf8mb4");

if (!function_exists('cairo_is_dst')) {
    // Egypt reintroduced DST in 2023: clocks move forward the last Friday of April and back the
    // last Thursday of October (both at local midnight). The system's bundled Africa/Cairo tzdata
    // was never updated to reflect this, so it always reports plain standard time (UTC+2) — this
    // computes the real rule directly instead of trusting that (stale) timezone database.
    function cairo_is_dst(?DateTime $utcNow = null): bool {
        $utcNow = $utcNow ?? new DateTime('now', new DateTimeZone('UTC'));
        $year = (int)$utcNow->format('Y');
        $dstStart = new DateTime("{$year}-04-01", new DateTimeZone('UTC'));
        $dstStart->modify('last Friday of this month');
        $dstEnd = new DateTime("{$year}-10-01", new DateTimeZone('UTC'));
        $dstEnd->modify('last Thursday of this month');
        return $utcNow >= $dstStart && $utcNow < $dstEnd;
    }
}

if (!function_exists('cairo_utc_offset_seconds')) {
    function cairo_utc_offset_seconds(): int {
        return cairo_is_dst() ? 3 * 3600 : 2 * 3600;
    }
}

$timezone = trim($_GET["_timezone"] ?? "");

if ($timezone === "") {
    $timezone = "Africa/Cairo";
}

try {
    $timezoneObject = new DateTimeZone($timezone);
} catch (Throwable $e) {
    $timezone = "Africa/Cairo";
    $timezoneObject = new DateTimeZone($timezone);
}

date_default_timezone_set($timezone);

$currentDate = new DateTime(
    "now",
    $timezoneObject
);

$offsetSeconds = $timezoneObject->getOffset($currentDate);

if ($timezone === "Africa/Cairo") {
    // Replace whatever (stale, DST-blind) offset the system database gave us with the correct one.
    $correctOffsetSeconds = cairo_utc_offset_seconds();
    $currentDate->modify(sprintf('%+d seconds', $correctOffsetSeconds - $offsetSeconds));

    // PHP's own clock has to move with it. Correcting only MySQL below left the two an hour apart
    // for the whole DST season: anything PHP wrote with date() landed an hour in the past, and
    // anything compared against NOW() read an hour ahead. An email verification code was expired
    // 45 minutes before it was created, so every correct code was rejected as expired.
    //
    // Set as a fixed offset rather than a name, because the name is precisely what this server's
    // tzdata gets wrong. PHP rejects "+03:00" as an identifier; Etc/GMT-N is the spelling it takes,
    // and its sign is inverted by POSIX convention - Etc/GMT-3 means UTC+3.
    if ($correctOffsetSeconds !== $offsetSeconds) {
        $etcZone = 'Etc/GMT' . ($correctOffsetSeconds >= 0 ? '-' : '+') . intdiv(abs($correctOffsetSeconds), 3600);
        if (@date_default_timezone_set($etcZone) === false) {
            date_default_timezone_set($timezone);
        }
    }

    $offsetSeconds = $correctOffsetSeconds;
}

$sign = $offsetSeconds >= 0 ? "+" : "-";
$absoluteOffset = abs($offsetSeconds);
$offsetHours = intdiv($absoluteOffset, 3600);
$offsetMinutes = intdiv($absoluteOffset % 3600, 60);

$mysqlOffset = sprintf(
    "%s%02d:%02d",
    $sign,
    $offsetHours,
    $offsetMinutes
);

$escapedOffset = mysqli_real_escape_string(
    $con,
    $mysqlOffset
);

if (!mysqli_query(
    $con,
    "SET time_zone = '{$escapedOffset}'"
)) {
    die(
        "Failed to set database timezone: "
        . mysqli_error($con)
    );
}

$GLOBALS["CLIENT_TIMEZONE"] = $timezone;
$GLOBALS["CURRENT_TIME"] = $currentDate->format(
    "Y-m-d H:i:s"
);