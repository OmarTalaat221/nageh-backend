<?php
/**
 * What a video's URL is, and what it should be stored as.
 *
 * The platform is moving its library onto Bunny Stream. Bunny hands out more than one URL for the
 * same video and the one the dashboard's copy button gives - "Copy Video Play Url" - is the share
 * page, not the embed. Both carry the only two things that identify a video, the library id and
 * the guid, so rather than telling whoever is entering 157 videos to hand-edit each link, this
 * accepts any of Bunny's forms and stores the embed form.
 *
 * YouTube, Vimeo and direct files are still recognised and passed through untouched. Nothing that
 * plays today stops playing because of this file.
 */

if (!function_exists('video_parse_source')) {

    /**
     * Break a URL into { provider, url, library_id, video_id }.
     *
     * `url` is always what should be stored and played. For Bunny that is the embed URL, rebuilt
     * from the two ids rather than passed through, so a link copied with a `?autoplay=true` or an
     * expired token stuck on the end is cleaned rather than saved as-is.
     *
     * provider is one of: bunny, youtube, vimeo, file, external, none.
     */
    function video_parse_source(?string $rawUrl): array {

        $url = trim((string)$rawUrl);

        if ($url === '') {
            return [
                'provider'   => 'none',
                'url'        => null,
                'library_id' => null,
                'video_id'   => null,
            ];
        }

        // ---- Bunny Stream --------------------------------------------------------------------
        //
        // player.mediadelivery.net/play/{library}/{guid}   <- "Copy Video Play Url", the share page
        // iframe.mediadelivery.net/embed/{library}/{guid}  <- what the embed dialog gives
        // video.bunnycdn.com/play/{library}/{guid}         <- the older hostname
        //
        // Three hostnames for the same video. The copy button gives the first, the player needs
        // the second, and they differ by more than the path - which is why this normalises from
        // the two ids rather than rewriting the string.
        //
        // The guid is a plain UUID. Matching it strictly is what stops a mangled paste being
        // stored as a Bunny link that then fails silently in an iframe.

        if (preg_match(
            '~(?:player|iframe)\.mediadelivery\.net|video\.bunnycdn\.com~i', $url)
            && preg_match(
            '~/(?:play|embed)/(\d+)/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})~i',
            $url,
            $m
        )) {
            return [
                'provider'   => 'bunny',
                'url'        => 'https://iframe.mediadelivery.net/embed/' . $m[1] . '/' . strtolower($m[2]),
                'library_id' => $m[1],
                'video_id'   => strtolower($m[2]),
            ];
        }

        // The direct CDN playlist, vz-xxxx.b-cdn.net/{guid}/playlist.m3u8. It carries the guid but
        // not the library id, so it cannot be turned into an embed URL. Kept as it is; the player
        // can play an HLS playlist directly.

        if (preg_match(
            '~//([\w-]+)\.b-cdn\.net/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/~i',
            $url,
            $m
        )) {
            return [
                'provider'   => 'bunny',
                'url'        => $url,
                'library_id' => null,
                'video_id'   => strtolower($m[2]),
            ];
        }

        // ---- YouTube -------------------------------------------------------------------------

        if (preg_match(
            '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([\w-]{6,20})~i',
            $url,
            $m
        )) {
            return [
                'provider'   => 'youtube',
                'url'        => $url,
                'library_id' => null,
                'video_id'   => $m[1],
            ];
        }

        // ---- Vimeo ---------------------------------------------------------------------------

        if (preg_match('~vimeo\.com/(?:video/)?(\d{5,15})~i', $url, $m)) {
            return [
                'provider'   => 'vimeo',
                'url'        => $url,
                'library_id' => null,
                'video_id'   => $m[1],
            ];
        }

        // ---- A file ---------------------------------------------------------------------------

        if (preg_match('~\.(mp4|webm|ogg|ogv|mov|m4v|m3u8)(\?.*)?$~i', $url) || stripos($url, '/uploads/') !== false) {
            return [
                'provider'   => 'file',
                'url'        => $url,
                'library_id' => null,
                'video_id'   => null,
            ];
        }

        return [
            'provider'   => 'external',
            'url'        => $url,
            'library_id' => null,
            'video_id'   => null,
        ];
    }
}

if (!function_exists('video_normalize_url')) {

    /**
     * The URL as it should be written to the database. NULL for an empty input, so a blank field
     * clears rather than storing an empty string that then has to be checked for everywhere.
     */
    function video_normalize_url(?string $rawUrl): ?string {
        $parsed = video_parse_source($rawUrl);
        return $parsed['url'];
    }
}

if (!function_exists('video_url_column_exists')) {

    /**
     * Whether migration 078 has been run on this database yet.
     *
     * Code reaches the server on a `git pull` and migrations are applied by hand afterwards, so
     * there is always a window where the new code is live and the new column is not. Referencing
     * `videos`.`video_url` during that window is not a degraded experience, it is a fatal SQL
     * error on every lesson listing on the platform.
     *
     * Asked once per request and remembered.
     */
    function video_url_column_exists($con): bool {
        static $exists = null;

        if ($exists === null) {
            $res = mysqli_query($con, "
                SELECT COUNT(*) AS n FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'videos'
                  AND COLUMN_NAME = 'video_url'
            ");
            $exists = $res && (int)(mysqli_fetch_assoc($res)['n'] ?? 0) > 0;
        }

        return $exists;
    }
}

if (!function_exists('video_url_select_expr')) {

    /**
     * The SQL that reads a video's URL, whichever half of the migration this database is in.
     *
     * After 078 the column on `videos` wins and `course_videos` is the fallback, so a row the
     * backfill could not pair still plays. Before 078 only the old table is named, which is
     * exactly today's behaviour.
     *
     * $cvAlias may be null where the caller has no join to `course_videos`.
     */
    function video_url_select_expr($con, string $videoAlias = 'v', ?string $cvAlias = 'cv'): string {
        $parts = [];

        if (video_url_column_exists($con)) {
            $parts[] = "`{$videoAlias}`.`video_url`";
        }

        if ($cvAlias !== null) {
            $parts[] = "`{$cvAlias}`.`video_url`";
        }

        if (empty($parts)) {
            return "''";
        }

        return 'COALESCE(' . implode(', ', $parts) . ", '')";
    }
}

if (!function_exists('video_decorate_source')) {

    /**
     * Add `video_provider` beside an existing `video_url` on a row being returned to a dashboard,
     * so the dashboard can say "this one is on Bunny" without re-implementing the patterns above
     * in three JavaScript codebases.
     */
    function video_decorate_source(array $row, string $key = 'video_url'): array {
        $parsed = video_parse_source($row[$key] ?? null);
        $row['video_provider'] = $parsed['provider'];
        return $row;
    }
}
