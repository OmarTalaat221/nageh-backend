-- Migration 078: give a video its own URL, and rescue the 153 URLs stranded in a second table.
--
-- Today the platform keeps videos in two unconnected places:
--
--   `videos`         157 rows. This is what the admin and teacher dashboards create and edit.
--                    It has no column for a URL at all.
--   `course_videos`  153 rows, every one of them with a URL. No endpoint anywhere in the codebase
--                    writes to it. Three student read paths join to it and nothing else touches it.
--
-- The two are joined on `v.id = cv.id`, and only 9 ids exist in both tables. So of the 157 videos
-- on the platform, 9 can be played. The other 148 were created through the dashboard, which sends
-- `video_url` in its payload, and the backend drops it on the floor because there is nowhere to
-- put it. Nobody was told. The field simply does nothing, and has never done anything.
--
-- This migration gives `videos` the column it always needed and moves the 153 URLs onto it.
--
-- =================================================================================================
-- How the 153 URLs are matched to their videos
-- =================================================================================================
--
-- Within a unit, the Nth row of `course_videos` (by its own `order_num`) is the Nth row of
-- `videos` (by id). That holds because the two tables are the same lessons entered twice: unit
-- 1007 has `course_videos` 3013 "فيديو 1" and 3014 "فيديو 2" against `videos` 2013 "الدرس 1"
-- (code V-203-1-1) and 2014 "الدرس 2" (V-203-1-2).
--
-- Checked against the production data before writing this, three ways:
--
--   * all 75 units named by `course_videos` also exist in `videos`, and every one of the 153 rows
--     sits in a unit where both tables hold the same number of rows - so no unit is ambiguous;
--   * pairing by rank produces exactly 153 pairs, one per URL, no video receiving two;
--   * a completely independent rule - the ids happen to run at a fixed offset, cv.id - 1000 for
--     the 144 legacy rows and cv.id for the 9 newer ones - picks the same partner in all 153
--     cases, with the unit agreeing every time. Zero disagreements between the two methods.
--
-- The rank rule is the one used below, because it says something true about the data ("the third
-- lesson is the third lesson") rather than relying on an arithmetic coincidence. The offset was
-- only ever a check on it.
--
-- =================================================================================================
-- What this does NOT do
-- =================================================================================================
--
-- `course_videos` is left exactly as it is. It is not dropped, not emptied, not modified. If the
-- matching above is wrong about any row, the original is still sitting there to be re-read. Drop
-- it in a later migration, once the videos have been watched and not before.
--
-- Nothing here is destructive: one nullable column is added, and it is filled only where it is
-- currently NULL. Re-running the file is safe and changes nothing the second time.

-- =================================================================================================
-- 1. The column
-- =================================================================================================
--
-- TEXT, matching `course_videos`.`video_url`, because a signed Bunny URL with a token and an
-- expiry on it is comfortably longer than 255 characters and truncating one produces a link that
-- is broken in a way nobody can see by looking at it.

SET @has_video_url := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'videos' AND COLUMN_NAME = 'video_url');
SET @sql := IF(@has_video_url = 0,
    'ALTER TABLE `videos` ADD COLUMN `video_url` TEXT NULL COMMENT ''Where the video plays from. Bunny, YouTube, or a direct file.'' AFTER `code`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 2. The 153 URLs, moved onto it
-- =================================================================================================
--
-- `WHERE v.video_url IS NULL OR v.video_url = ''` is what makes this safe to re-run and safe to
-- run late: once somebody has typed a Bunny link into a video through the dashboard, this will not
-- overwrite it with the old YouTube one.

UPDATE `videos` v
JOIN (
    SELECT
        vr.id AS video_id,
        cvr.video_url AS video_url
    FROM (
        SELECT id, unit_id, video_url,
               ROW_NUMBER() OVER (PARTITION BY unit_id ORDER BY order_num, id) AS rk
        FROM `course_videos`
        WHERE video_url IS NOT NULL AND video_url <> ''
    ) cvr
    JOIN (
        SELECT id, unit_id,
               ROW_NUMBER() OVER (PARTITION BY unit_id ORDER BY id) AS rk
        FROM `videos`
    ) vr
      ON vr.unit_id = cvr.unit_id
     AND vr.rk = cvr.rk
) src
  ON src.video_id = v.id
SET v.video_url = src.video_url
WHERE v.video_url IS NULL OR v.video_url = '';

-- =================================================================================================
-- 3. Finding the ones still to be moved to Bunny
-- =================================================================================================
--
-- The whole library is being moved onto Bunny, which means somebody has to work through 144
-- YouTube links by hand and know which are done. This index is what makes
-- "WHERE video_url IS NULL OR video_url NOT LIKE '%mediadelivery%'" cheap enough to put on a
-- dashboard screen, on a prefix of the column because TEXT cannot be indexed whole.

SET @has_video_url_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'videos' AND INDEX_NAME = 'idx_videos_video_url');
SET @sql := IF(@has_video_url_idx = 0,
    'ALTER TABLE `videos` ADD KEY `idx_videos_video_url` (`video_url`(64))',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
