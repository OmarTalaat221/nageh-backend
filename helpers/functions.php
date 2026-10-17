<?php
require_once __DIR__ . '/language.php';

// Polyfills for PHP < 8.0 compatibility
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

require_once __DIR__ . '/upload.php';


if (!function_exists('get_request_timezone')) {
    function get_request_timezone(): string {
        $tz = trim($_GET['_time_zone'] ?? $_POST['_time_zone'] ?? '');
        if (empty($tz)) {
            $body = read_json_body_raw_safe();
            $tz = trim($body['_time_zone'] ?? '');
        }
        if (!empty($tz) && in_array($tz, timezone_identifiers_list())) {
            return $tz;
        }
        return 'Africa/Cairo';
    }
}


if (!function_exists('get_raw_request_body')) {
    function get_raw_request_body(): string {
        static $raw = null;
        if ($raw === null || $raw === '') {
            $input = file_get_contents('php://input');
            if ($input !== false && $input !== '') {
                $raw = $input;
            } else {
                $raw = $GLOBALS['HTTP_RAW_POST_DATA'] ?? '';
            }
            if (is_string($raw) && str_starts_with($raw, "\xEF\xBB\xBF")) {
                $raw = substr($raw, 3);
            }
            $raw = is_string($raw) ? trim($raw) : '';
        }
        return $raw;
    }
}

if (!function_exists('read_json_body_raw_safe')) {
    function read_json_body_raw_safe(): array {
        static $cached = null;
        if ($cached !== null) return $cached;
        $decoded = json_decode(get_raw_request_body(), true);
        $cached = is_array($decoded) ? $decoded : [];
        return $cached;
    }
}

if (!function_exists('get_request_now')) {
    function get_request_now(): DateTime {
        try {
            $tz = get_request_timezone();
            if ($tz === 'Africa/Cairo' && function_exists('cairo_utc_offset_seconds')) {
                // The server's bundled Africa/Cairo tzdata does not know about Egypt's reintroduced
                // DST, so PHP's own timezone database silently stays on standard time (UTC+2)
                // year-round. cairo_utc_offset_seconds() (config/connection.php) computes the
                // correct offset from the actual DST calendar instead of trusting that database —
                // use the same source of truth here so app-level "now" and the DB connection's
                // clock (set from the same function) never drift apart again.
                $utcNow = new DateTime('now', new DateTimeZone('UTC'));
                $utcNow->modify(sprintf('+%d seconds', cairo_utc_offset_seconds()));
                return $utcNow;
            }
            return new DateTime('now', new DateTimeZone($tz));
        } catch (Exception $e) {
            return new DateTime('now', new DateTimeZone('Africa/Cairo'));
        }
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        $data = format_response_images($data);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('parse_raw_multipart_data')) {
    function parse_raw_multipart_data(string $rawInput, string $contentType): array {
        $postData = [];
        $filesData = [];

        if (!preg_match('/boundary=(.*)$/i', $contentType, $matches)) {
            return ['post' => $postData, 'files' => $filesData];
        }

        $boundary = trim($matches[1], '"\' ');
        $blocks = explode("--" . $boundary, $rawInput);

        foreach ($blocks as $block) {
            if (empty($block) || $block === "--\r\n" || $block === "--\n") {
                continue;
            }

            if (strpos($block, "\r\n\r\n") !== false) {
                list($headerStr, $content) = explode("\r\n\r\n", $block, 2);
            } elseif (strpos($block, "\n\n") !== false) {
                list($headerStr, $content) = explode("\n\n", $block, 2);
            } else {
                continue;
            }

            $content = rtrim($content, "\r\n");

            $headers = [];
            foreach (explode("\n", str_replace("\r", "", $headerStr)) as $headerLine) {
                if (strpos($headerLine, ':') !== false) {
                    list($hName, $hVal) = explode(':', $headerLine, 2);
                    $headers[strtolower(trim($hName))] = trim($hVal);
                }
            }

            $cd = $headers['content-disposition'] ?? '';
            if (!empty($cd) && preg_match('/name="([^"]+)"/i', $cd, $nameMatch)) {
                $fieldName = $nameMatch[1];

                if (preg_match('/filename="([^"]+)"/i', $cd, $filenameMatch)) {
                    $fileName = $filenameMatch[1];
                    $fileType = $headers['content-type'] ?? 'application/octet-stream';

                    if (!empty($fileName)) {
                        $tmpFilePath = tempnam(sys_get_temp_dir(), 'php_put_file_');
                        file_put_contents($tmpFilePath, $content);

                        $filesData[$fieldName] = [
                            'name' => $fileName,
                            'type' => $fileType,
                            'tmp_name' => $tmpFilePath,
                            'error' => UPLOAD_ERR_OK,
                            'size' => strlen($content)
                        ];
                    }
                } else {
                    if (str_ends_with($fieldName, '[]')) {
                        $realName = substr($fieldName, 0, -2);
                        if (!isset($postData[$realName]) || !is_array($postData[$realName])) {
                            $postData[$realName] = [];
                        }
                        $postData[$realName][] = $content;
                    } else {
                        $postData[$fieldName] = $content;
                    }
                }
            }
        }

        return ['post' => $postData, 'files' => $filesData];
    }
}

if (!function_exists('read_json_body')) {
    function read_json_body(): array {
        static $cachedBody = null;
        if ($cachedBody !== null) {
            return $cachedBody;
        }

        $raw = get_raw_request_body();
        if (empty($raw)) {
            $raw = file_get_contents('php://input') ?: '';
            if (is_string($raw) && str_starts_with($raw, "\xEF\xBB\xBF")) {
                $raw = substr($raw, 3);
            }
            $raw = is_string($raw) ? trim($raw) : '';
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        $body = json_decode($raw, true);
        if (!is_array($body) && !empty($raw)) {
            $cleanRaw = preg_replace('/^[\x00-\x1F\x7F\xEF\xBB\xBF]+/u', '', $raw);
            $body = json_decode($cleanRaw, true);
        }

        if (!is_array($body)) {
            $body = [];
        }

        $parsedPost = $_POST ?: [];
        $parsedFiles = $_FILES ?: [];

        if (empty($body) && !empty($raw)) {
            if (str_contains(strtolower($contentType), 'multipart/form-data')) {
                $parsedData = parse_raw_multipart_data($raw, $contentType);
                $parsedPost = array_merge($parsedPost, $parsedData['post']);
                $parsedFiles = array_merge($parsedFiles, $parsedData['files']);
            } elseif (str_contains(strtolower($contentType), 'application/x-www-form-urlencoded')) {
                parse_str($raw, $postVars);
                if (is_array($postVars)) {
                    $parsedPost = array_merge($parsedPost, $postVars);
                }
            }
        }

        if (!empty($parsedFiles)) {
            foreach ($parsedFiles as $fk => $fv) {
                if (!isset($_FILES[$fk])) {
                    $_FILES[$fk] = $fv;
                }
            }
        }

        if (!empty($parsedPost)) {
            $body = array_merge($body, $parsedPost);
        }

        if (!empty($_GET)) {
            foreach ($_GET as $k => $v) {
                if (!isset($body[$k])) {
                    $body[$k] = $v;
                }
            }
        }

        if (!empty($_REQUEST)) {
            foreach ($_REQUEST as $k => $v) {
                if (!isset($body[$k])) {
                    $body[$k] = $v;
                }
            }
        }

        if (!empty($parsedFiles)) {
            foreach ($parsedFiles as $key => $fileInfo) {
                if (isset($fileInfo['tmp_name']) && !empty($fileInfo['tmp_name']) && file_exists($fileInfo['tmp_name'])) {
                    $keyLower = strtolower($key);
                    $subfolder = 'avatars';
                    if (str_contains($keyLower, 'banner')) {
                        $subfolder = 'banners';
                    } elseif (str_contains($keyLower, 'video')) {
                        $subfolder = 'videos';
                    } elseif (str_contains($keyLower, 'doc') || str_contains($keyLower, 'pdf') || str_contains($keyLower, 'homework') || str_contains($keyLower, 'book') || $keyLower === 'file') {
                        $subfolder = 'documents';
                    } elseif (str_contains($keyLower, 'audio')) {
                        $subfolder = 'audio';
                    }

                    $uploadedPath = upload_image_file($fileInfo, $subfolder)
                        ?? upload_video_file($fileInfo, $subfolder)
                        ?? upload_document_file($fileInfo, $subfolder)
                        ?? upload_audio_file($fileInfo, $subfolder);

                    if ($uploadedPath) {
                        $body[$key] = $uploadedPath;
                    }
                }
            }
        }

        if (!empty($body)) {
            foreach ($body as $key => $val) {
                if (is_string($val) && (str_starts_with($val, 'data:image/') || str_starts_with($val, 'data:video/') || str_starts_with($val, 'data:audio/'))) {
                    $subfolder = str_starts_with($val, 'data:video/') ? 'videos' : (str_starts_with($val, 'data:audio/') ? 'audio' : 'avatars');
                    $processed = process_image_input($val, $subfolder);
                    if ($processed) {
                        $body[$key] = $processed;
                    }
                }
            }
        }

        $cachedBody = $body ?: [];
        return $cachedBody;
    }
}

if (!function_exists('build_paginated_response')) {
    function build_paginated_response(array $items, int $page, int $limit, int $totalRecords, array $statistics = []): array {
        $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

        $formattedItems = format_response_images($items);

        $res = [
            'status' => 'success',
            'data' => $formattedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_prev_page' => $page > 1
            ]
        ];

        if (!empty($statistics)) {
            $res['statistics'] = format_response_images($statistics);
        }

        return $res;
    }
}

if (!function_exists('build_admin_paginated_response')) {
    function build_admin_paginated_response(array $items, int $page, int $limit, int $totalRecords, array $statistics = []): array {
        return build_paginated_response($items, $page, $limit, $totalRecords, $statistics);
    }
}

if (!function_exists('ensure_ratings_and_learnings_tables')) {
    function ensure_ratings_and_learnings_tables($con): void {
        return;
    }
}

if (!function_exists('get_allowed_session_file_types')) {
    function get_allowed_session_file_types(): array {
        return ['exam', 'homework', 'task', 'explanation', 'other'];
    }
}
if (!function_exists('generate_entity_code')) {
    /**
     * A unique `code` for a row whose form no longer asks for one.
     *
     * Grades and subjects both have `code varchar(100) NOT NULL UNIQUE`, so making the field
     * optional on the screen without filling it here would simply fail the insert - and the
     * admin would be told "code is required" about a box that is no longer on the form.
     *
     * Built from the English name when there is one, since a code is an internal handle that
     * people type into URLs and exports. An Arabic-only name leaves nothing usable after
     * stripping non-ASCII, so those fall back to the prefix and a number.
     */
    function generate_entity_code($con, string $table, string $from, string $prefix): string {
        $base = strtolower(trim($from));
        $base = preg_replace('/[^a-z0-9]+/', '-', $base);
        $base = trim((string)$base, '-');
        $base = substr((string)$base, 0, 60);

        if ($base === '') {
            $base = $prefix;
        }

        $safeTable = preg_replace('/[^a-z_]/', '', $table);
        $candidate = $base;
        $suffix = 1;

        // Bounded: after a hundred collisions something else is wrong, and a random tail is a
        // better answer than a loop that does not end.
        while ($suffix <= 100) {
            $escaped = mysqli_real_escape_string($con, $candidate);
            $res = mysqli_query($con, "SELECT id FROM `{$safeTable}` WHERE code = '{$escaped}' LIMIT 1");

            if (!$res || !mysqli_fetch_assoc($res)) {
                return $candidate;
            }

            $suffix++;
            $candidate = $base . '-' . $suffix;
        }

        return $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
    }
}

// A demo student account: sees and opens everything in the catalogue without paying for any of
// it, and is kept out of every admin-facing student list and count. It is flagged by a column
// rather than a hardcoded email so the flag can be moved or revoked from the database alone.
//
// Access is granted here, in code, rather than by writing subscription rows. That matters twice
// over: new content is covered the moment it is created, with nothing to re-run, and because the
// account owns no subscription, it never surfaces in the lists that are built from subscriptions
// - teacher rosters, subscription screens, revenue reports - which is exactly where it should
// not appear.
if (!function_exists('student_is_demo_account')) {
    function student_is_demo_account($con, int $studentId): bool {
        static $cache = [];

        if ($studentId <= 0) {
            return false;
        }

        if (array_key_exists($studentId, $cache)) {
            return $cache[$studentId];
        }

        // Older databases predate the column; treat its absence as "no demo accounts exist"
        // rather than letting every student query fail.
        $hasColumn = @mysqli_query($con, "SHOW COLUMNS FROM `students` LIKE 'is_demo_account'");

        if (!$hasColumn || mysqli_num_rows($hasColumn) === 0) {
            return $cache[$studentId] = false;
        }

        $stmt = mysqli_prepare($con, "SELECT is_demo_account FROM `students` WHERE id = ? LIMIT 1");

        if (!$stmt) {
            return $cache[$studentId] = false;
        }

        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        return $cache[$studentId] = ((int)($row['is_demo_account'] ?? 0) === 1);
    }
}

if (!function_exists('demo_account_accessible_entities')) {
    function demo_account_accessible_entities($con): array {
        $collect = function (string $sql) use ($con): array {
            $ids = [];
            $res = @mysqli_query($con, $sql);

            if ($res) {
                while ($row = mysqli_fetch_row($res)) {
                    $ids[] = (int)$row[0];
                }
            }

            return $ids;
        };

        return [
            'course_ids' => $collect("SELECT id FROM `courses` WHERE is_active = 1"),
            'subject_ids' => $collect("SELECT id FROM `subjects` WHERE is_active = 1"),
            'teacher_ids' => $collect("SELECT id FROM `teachers` WHERE is_active = 1"),
        ];
    }
}

// SQL condition that keeps demo accounts out of a student query. Returns a harmless `1=1` on a
// database where the column has not been added yet, so deploying this code before running the
// migration degrades to "no demo accounts" instead of breaking every admin student screen.
if (!function_exists('demo_account_condition')) {
    function demo_account_condition($con, string $alias = ''): string {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $res = @mysqli_query($con, "SHOW COLUMNS FROM `students` LIKE 'is_demo_account'");
            $hasColumn = (bool)($res && mysqli_num_rows($res) > 0);
        }

        if (!$hasColumn) {
            return '1=1';
        }

        $prefix = $alias !== '' ? "`{$alias}`." : '';

        return "{$prefix}is_demo_account = 0";
    }
}

// Whether the students table carries a given column. Lets a query opt into a column that a
// pending migration has not added yet instead of failing outright on an older database.
if (!function_exists('students_table_has_column')) {
    function students_table_has_column($con, string $column): bool {
        static $cache = [];

        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        $safe = preg_replace('/[^A-Za-z0-9_]/', '', $column);
        $res = @mysqli_query($con, "SHOW COLUMNS FROM `students` LIKE '{$safe}'");

        return $cache[$column] = (bool)($res && mysqli_num_rows($res) > 0);
    }
}

// The platform's owner gives consultations himself and is not a teacher: he has no row in
// `teachers`, and should not appear in any teacher listing. A slot with teacher_id NULL is his.
//
// These two helpers are the only place that knows what to call him, so every screen - admin,
// teacher, student - says the same thing, and the name is a setting rather than a constant.
if (!function_exists('platform_owner_name')) {
    function platform_owner_name($con): string {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $res = @mysqli_query($con, "SELECT setting_value FROM site_settings WHERE setting_key = 'platform_owner_name' LIMIT 1");
        $value = $res ? trim((string)(mysqli_fetch_assoc($res)['setting_value'] ?? '')) : '';

        return $cached = ($value !== '' ? $value : 'الأستاذ إبراهيم العبيد');
    }
}

if (!function_exists('platform_owner_as_teacher')) {
    /**
     * The owner shaped like a teacher, for responses that carry a `teacher` object.
     *
     * id is 0, never a real teacher's id: a client can tell the two apart, and sending 0 back as
     * a teacher_id filter asks for the owner's slots.
     */
    function platform_owner_as_teacher($con): array {
        return [
            'id' => 0,
            'name' => platform_owner_name($con),
            'email' => null,
            'image' => null,
            'specialization' => null,
            'rating' => null,
            'is_platform_owner' => true,
        ];
    }
}
