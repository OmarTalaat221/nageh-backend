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

$timezone = trim($_GET["_timezone"] ?? "");

if ($timezone === "") {
    $timezone = "UTC";
}

try {
    $timezoneObject = new DateTimeZone($timezone);
} catch (Throwable $e) {
    $timezone = "UTC";
    $timezoneObject = new DateTimeZone("UTC");
}

date_default_timezone_set($timezone);

$currentDate = new DateTime(
    "now",
    $timezoneObject
);

$offsetSeconds = $timezoneObject->getOffset($currentDate);

if ($timezone === "Africa/Cairo") {
    $currentDate->modify("+1 hour");
    $offsetSeconds += 3600;
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