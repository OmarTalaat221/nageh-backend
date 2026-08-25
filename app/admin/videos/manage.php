<?php

require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';


/*
|--------------------------------------------------------------------------
| Get Video Details
|--------------------------------------------------------------------------
*/

if (!function_exists('adminGetVideoDetails')) {

    function adminGetVideoDetails($con, $videoId)
    {
        if (!$videoId) {
            return null;
        }

        $langInfo = get_language_info($con);

        /*
        |--------------------------------------------------------------------------
        | Get Video
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            SELECT
                id,
                unit_id,
                code,
                duration_seconds,
                publish_status,
                is_active,
                created_at,
                updated_at
            FROM videos
            WHERE id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $videoId);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        if (!$row) {
            return null;
        }

        $row['id'] = (int)$row['id'];
        $row['unit_id'] = (int)$row['unit_id'];
        $row['duration_seconds'] = (int)($row['duration_seconds'] ?? 0);
        $row['is_active'] = (int)$row['is_active'];


        /*
        |--------------------------------------------------------------------------
        | Get Translations
        |--------------------------------------------------------------------------
        */

        $tStmt = mysqli_prepare(
            $con,
            "
            SELECT
                vt.language_id,
                l.code AS language_code,
                vt.name,
                vt.description,
                vt.is_active
            FROM videos_translations vt
            JOIN languages l
                ON vt.language_id = l.id
            WHERE vt.video_id = ?
            ORDER BY vt.language_id ASC
            "
        );

        $translations = [];

        $nameAr = null;
        $nameEn = null;

        $descAr = null;
        $descEn = null;

        if ($tStmt) {

            mysqli_stmt_bind_param(
                $tStmt,
                "i",
                $videoId
            );

            mysqli_stmt_execute($tStmt);

            $tRes = mysqli_stmt_get_result($tStmt);

            while ($tRow = mysqli_fetch_assoc($tRes)) {

                $tRow['language_id'] = (int)$tRow['language_id'];
                $tRow['is_active'] = (int)$tRow['is_active'];

                $translations[] = $tRow;

                if ($tRow['language_code'] === 'ar') {

                    $nameAr = $tRow['name'];
                    $descAr = $tRow['description'];
                }

                if ($tRow['language_code'] === 'en') {

                    $nameEn = $tRow['name'];
                    $descEn = $tRow['description'];
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Current Language Data
        |--------------------------------------------------------------------------
        */

        $transMap = array_column(
            $translations,
            'name',
            'language_code'
        );

        $descMap = array_column(
            $translations,
            'description',
            'language_code'
        );

        $currentLanguageCode =
            $langInfo['code'] ?? 'en';


        $row['name'] =
            $transMap[$currentLanguageCode]
            ?? $nameAr
            ?? $nameEn
            ?? $row['code'];


        $row['description'] =
            $descMap[$currentLanguageCode]
            ?? $descAr
            ?? $descEn
            ?? '';


        /*
        |--------------------------------------------------------------------------
        | Arabic / English
        |--------------------------------------------------------------------------
        */

        $row['name_ar'] = $nameAr;
        $row['name_en'] = $nameEn;

        $row['description_ar'] = $descAr;
        $row['description_en'] = $descEn;

        $row['translations'] = $translations;


        return format_response_images($row);
    }
}


/*
|--------------------------------------------------------------------------
| List Videos
|--------------------------------------------------------------------------
*/

if (!function_exists('adminListVideos')) {

    function adminListVideos($con)
    {
        $page = max(
            1,
            (int)($_GET['page'] ?? 1)
        );

        $limit = max(
            1,
            min(
                100,
                (int)($_GET['limit'] ?? 10)
            )
        );

        $offset = ($page - 1) * $limit;


        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $unitIds = parse_int_list_param(
            $_GET['unit_ids']
            ?? $_GET['unit_id']
            ?? $_GET['units']
            ?? null
        );

        $activeFilter = isset($_GET['is_active'])
            ? (int)$_GET['is_active']
            : null;

        $search = trim(
            $_GET['search']
            ?? $_GET['q']
            ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $sortBy = strtolower(
            trim(
                $_GET['sort_by']
                ?? $_GET['sort']
                ?? 'id'
            )
        );

        $sortDir =
            strtoupper(
                trim(
                    $_GET['dir']
                    ?? $_GET['direction']
                    ?? $_GET['order']
                    ?? 'DESC'
                )
            ) === 'ASC'
                ? 'ASC'
                : 'DESC';


        $orderCol =
            ($sortBy === 'code')
                ? 'v.code'
                : 'v.id';


        /*
        |--------------------------------------------------------------------------
        | WHERE Conditions
        |--------------------------------------------------------------------------
        */

        $whereConditions = [];

        $params = [];

        $types = "";


        /*
        |--------------------------------------------------------------------------
        | Unit Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($unitIds)) {

            $placeholders = implode(
                ',',
                array_fill(
                    0,
                    count($unitIds),
                    '?'
                )
            );

            $whereConditions[] =
                "v.unit_id IN ({$placeholders})";

            foreach ($unitIds as $uId) {

                $params[] = (int)$uId;
                $types .= "i";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Active Filter
        |--------------------------------------------------------------------------
        */

        if ($activeFilter !== null) {

            $whereConditions[] =
                "v.is_active = ?";

            $params[] = $activeFilter;

            $types .= "i";
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($search)) {

            $searchPattern =
                '%' . $search . '%';

            $whereConditions[] = "
                (
                    v.code LIKE ?
                    OR vt.name LIKE ?
                    OR vt.description LIKE ?
                )
            ";

            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;

            $types .= "sss";
        }


        $whereClause =
            !empty($whereConditions)
                ? "WHERE " . implode(
                    " AND ",
                    $whereConditions
                )
                : "";


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $statRes = mysqli_query(
            $con,
            "
            SELECT
                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN is_active = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS active_count,

                SUM(
                    CASE
                        WHEN is_active = 0
                        THEN 1
                        ELSE 0
                    END
                ) AS inactive_count

            FROM videos
            "
        );

        $statRow = mysqli_fetch_assoc($statRes);

        $statistics = [
            'total_count' =>
                (int)($statRow['total'] ?? 0),

            'active_count' =>
                (int)($statRow['active_count'] ?? 0),

            'inactive_count' =>
                (int)($statRow['inactive_count'] ?? 0)
        ];


        /*
        |--------------------------------------------------------------------------
        | Count
        |--------------------------------------------------------------------------
        */

        $totalRecords =
            $statistics['total_count'];


        if (!empty($whereClause)) {

            $countSql = "
                SELECT
                    COUNT(DISTINCT v.id) AS total

                FROM videos v

                LEFT JOIN videos_translations vt
                    ON v.id = vt.video_id

                {$whereClause}
            ";

            $countStmt =
                mysqli_prepare(
                    $con,
                    $countSql
                );

            if ($countStmt) {

                if (!empty($types)) {

                    mysqli_stmt_bind_param(
                        $countStmt,
                        $types,
                        ...$params
                    );
                }

                mysqli_stmt_execute(
                    $countStmt
                );

                $countRes =
                    mysqli_stmt_get_result(
                        $countStmt
                    );

                $countRow =
                    mysqli_fetch_assoc(
                        $countRes
                    );

                $totalRecords =
                    (int)(
                        $countRow['total'] ?? 0
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Get Video IDs
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT DISTINCT
                v.id

            FROM videos v

            LEFT JOIN videos_translations vt
                ON v.id = vt.video_id

            {$whereClause}

            ORDER BY
                {$orderCol} {$sortDir},
                v.id ASC

            LIMIT ?
            OFFSET ?
        ";


        $params[] = $limit;
        $params[] = $offset;

        $types .= "ii";


        $stmt = mysqli_prepare(
            $con,
            $sql
        );


        if (!$stmt) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to prepare videos query: '
                    . mysqli_error($con)
            ];
        }


        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );

        mysqli_stmt_execute($stmt);

        $res =
            mysqli_stmt_get_result($stmt);


        /*
        |--------------------------------------------------------------------------
        | Build Response
        |--------------------------------------------------------------------------
        */

        $videos = [];


        while (
            $row =
            mysqli_fetch_assoc($res)
        ) {

            $videoId =
                (int)$row['id'];


            $detail =
                adminGetVideoDetails(
                    $con,
                    $videoId
                );


            if ($detail) {

                $videos[] = $detail;
            }
        }


        return build_admin_paginated_response(
            $videos,
            $page,
            $limit,
            $totalRecords,
            $statistics
        );
    }
}


/*
|--------------------------------------------------------------------------
| Get Single Video
|--------------------------------------------------------------------------
*/

if (!function_exists('adminGetVideo')) {

    function adminGetVideo($con)
    {
        $body = read_json_body();

        $id = (int)(
            $_GET['id']
            ?? ($body['id'] ?? 0)
        );


        if ($id <= 0) {

            return [
                'status' => 'error',
                'message' =>
                    'Video ID is required'
            ];
        }


        $video =
            adminGetVideoDetails(
                $con,
                $id
            );


        if (!$video) {

            return [
                'status' => 'error',
                'message' =>
                    'Video not found'
            ];
        }


        return [
            'status' => 'success',
            'data' => $video
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Create Video
|--------------------------------------------------------------------------
*/

if (!function_exists('adminCreateVideo')) {

    function adminCreateVideo(
        $con,
        $creatorData = null
    ) {

        $body = read_json_body();


        /*
        |--------------------------------------------------------------------------
        | Unit
        |--------------------------------------------------------------------------
        */

        $unitId = (int)(
            $body['unit_id'] ?? 0
        );


        if ($unitId <= 0) {

            return [
                'status' => 'error',
                'message' =>
                    'unit_id is required'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Arabic Translation
        |--------------------------------------------------------------------------
        */

        $nameAr = trim(
            $body['name_ar']
            ?? $body['name']
            ?? ''
        );

        $descAr = trim(
            $body['description_ar']
            ?? $body['description']
            ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | English Translation
        |--------------------------------------------------------------------------
        */

        $nameEn = trim(
            $body['name_en']
            ?? $nameAr
        );

        $descEn = trim(
            $body['description_en']
            ?? $descAr
        );


        /*
        |--------------------------------------------------------------------------
        | Video Data
        |--------------------------------------------------------------------------
        */

        $code = trim(
            $body['code']
            ?? $body['video_code']
            ?? ('video-' . uniqid())
        );


        $durationSeconds = (int)(
            $body['duration_seconds']
            ?? $body['duration']
            ?? 0
        );


        /*
        |--------------------------------------------------------------------------
        | Publish Status
        |--------------------------------------------------------------------------
        */

        $publishStatus =
            strtolower(
                trim(
                    $body['publish_status']
                    ?? 'published'
                )
            );


        if (!in_array(
            $publishStatus,
            ['draft', 'published'],
            true
        )) {

            return [
                'status' => 'error',
                'message' =>
                    'publish_status must be draft or published'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Video Active
        |--------------------------------------------------------------------------
        */

        $isActive = isset(
            $body['is_active']
        )
            ? (
                (int)$body['is_active'] === 1
                    ? 1
                    : 0
            )
            : 1;


        if (empty($code)) {

            return [
                'status' => 'error',
                'message' =>
                    'Video code is required'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Insert Video
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $con,
            "
            INSERT INTO videos
            (
                unit_id,
                code,
                duration_seconds,
                publish_status,
                is_active
            )
            VALUES (?, ?, ?, ?, ?)
            "
        );


        if (!$stmt) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to prepare video insert: '
                    . mysqli_error($con)
            ];
        }


        mysqli_stmt_bind_param(
            $stmt,
            "isisi",
            $unitId,
            $code,
            $durationSeconds,
            $publishStatus,
            $isActive
        );


        if (!mysqli_stmt_execute($stmt)) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to create video: '
                    . mysqli_error($con)
            ];
        }


        $videoId =
            mysqli_insert_id($con);


        /*
        |--------------------------------------------------------------------------
        | Arabic Translation
        |--------------------------------------------------------------------------
        */

        if (
            !empty($nameAr)
            || !empty($descAr)
        ) {

            $translationStmt =
                mysqli_prepare(
                    $con,
                    "
                    INSERT INTO videos_translations
                    (
                        video_id,
                        language_id,
                        name,
                        description,
                        is_active
                    )
                    VALUES (?, 1, ?, ?, 1)

                    ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        description = VALUES(description)
                    "
                );


            if ($translationStmt) {

                mysqli_stmt_bind_param(
                    $translationStmt,
                    "iss",
                    $videoId,
                    $nameAr,
                    $descAr
                );

                mysqli_stmt_execute(
                    $translationStmt
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | English Translation
        |--------------------------------------------------------------------------
        */

        if (
            !empty($nameEn)
            || !empty($descEn)
        ) {

            $translationStmt =
                mysqli_prepare(
                    $con,
                    "
                    INSERT INTO videos_translations
                    (
                        video_id,
                        language_id,
                        name,
                        description,
                        is_active
                    )
                    VALUES (?, 2, ?, ?, 1)

                    ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        description = VALUES(description)
                    "
                );


            if ($translationStmt) {

                mysqli_stmt_bind_param(
                    $translationStmt,
                    "iss",
                    $videoId,
                    $nameEn,
                    $descEn
                );

                mysqli_stmt_execute(
                    $translationStmt
                );
            }
        }


        return [
            'status' => 'success',
            'message' =>
                'Video created successfully',
            'data' =>
                adminGetVideoDetails(
                    $con,
                    $videoId
                )
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Update Video
|--------------------------------------------------------------------------
*/

if (!function_exists('adminUpdateVideo')) {

    function adminUpdateVideo(
        $con,
        $creatorData = null
    ) {

        $body = read_json_body();


        $id = (int)(
            $body['id']
            ?? $_GET['id']
            ?? 0
        );


        if ($id <= 0) {

            return [
                'status' => 'error',
                'message' =>
                    'Video ID is required'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Check Existing Video
        |--------------------------------------------------------------------------
        */

        $checkStmt =
            mysqli_prepare(
                $con,
                "
                SELECT
                    id,
                    unit_id,
                    code,
                    duration_seconds,
                    publish_status,
                    is_active
                FROM videos
                WHERE id = ?
                LIMIT 1
                "
            );


        if (!$checkStmt) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to check video'
            ];
        }


        mysqli_stmt_bind_param(
            $checkStmt,
            "i",
            $id
        );

        mysqli_stmt_execute(
            $checkStmt
        );


        $existing =
            mysqli_fetch_assoc(
                mysqli_stmt_get_result(
                    $checkStmt
                )
            );


        if (!$existing) {

            return [
                'status' => 'error',
                'message' =>
                    'Video not found'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Video Fields
        |--------------------------------------------------------------------------
        */

        $unitId = isset(
            $body['unit_id']
        )
            ? (int)$body['unit_id']
            : (int)$existing['unit_id'];


        $code = isset(
            $body['code']
        )
            ? trim($body['code'])
            : null;


        $durationSeconds = null;


        if (
            isset(
                $body['duration_seconds']
            )
        ) {

            $durationSeconds =
                (int)$body['duration_seconds'];

        } elseif (
            isset($body['duration'])
        ) {

            $durationSeconds =
                (int)$body['duration'];
        }


        /*
        |--------------------------------------------------------------------------
        | Publish Status
        |--------------------------------------------------------------------------
        */

        $publishStatus = null;


        if (
            isset($body['publish_status'])
        ) {

            $publishStatus =
                strtolower(
                    trim(
                        $body['publish_status']
                    )
                );


            if (!in_array(
                $publishStatus,
                ['draft', 'published'],
                true
            )) {

                return [
                    'status' => 'error',
                    'message' =>
                        'publish_status must be draft or published'
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Video Active
        |--------------------------------------------------------------------------
        */

        $isActive = null;


        if (
            isset($body['is_active'])
        ) {

            $isActive =
                (int)$body['is_active'] === 1
                    ? 1
                    : 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Build Update
        |--------------------------------------------------------------------------
        */

        $updateFields = [
            "unit_id = ?"
        ];

        $params = [
            $unitId
        ];

        $types = "i";


        if ($code !== null) {

            if ($code === '') {

                return [
                    'status' => 'error',
                    'message' =>
                        'Video code cannot be empty'
                ];
            }


            $updateFields[] =
                "code = ?";

            $params[] =
                $code;

            $types .= "s";
        }


        if (
            $durationSeconds !== null
        ) {

            $updateFields[] =
                "duration_seconds = ?";

            $params[] =
                $durationSeconds;

            $types .= "i";
        }


        if (
            $publishStatus !== null
        ) {

            $updateFields[] =
                "publish_status = ?";

            $params[] =
                $publishStatus;

            $types .= "s";
        }


        if (
            $isActive !== null
        ) {

            $updateFields[] =
                "is_active = ?";

            $params[] =
                $isActive;

            $types .= "i";
        }


        $params[] = $id;
        $types .= "i";


        $uSql = "
            UPDATE videos
            SET " .
                implode(
                    ", ",
                    $updateFields
                ) . "
            WHERE id = ?
        ";


        $uStmt =
            mysqli_prepare(
                $con,
                $uSql
            );


        if (!$uStmt) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to prepare video update: '
                    . mysqli_error($con)
            ];
        }


        mysqli_stmt_bind_param(
            $uStmt,
            $types,
            ...$params
        );


        if (!mysqli_stmt_execute($uStmt)) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to update video: '
                    . mysqli_error($con)
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Arabic Translation Update
        |--------------------------------------------------------------------------
        */

        if (
            isset($body['name_ar'])
            || isset($body['name'])
            || isset($body['description_ar'])
            || isset($body['description'])
        ) {

            $nameAr = trim(
                $body['name_ar']
                ?? $body['name']
                ?? ''
            );


            $descAr = trim(
                $body['description_ar']
                ?? $body['description']
                ?? ''
            );


            $translationStmt =
                mysqli_prepare(
                    $con,
                    "
                    INSERT INTO videos_translations
                    (
                        video_id,
                        language_id,
                        name,
                        description,
                        is_active
                    )
                    VALUES (?, 1, ?, ?, 1)

                    ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        description = VALUES(description)
                    "
                );


            if ($translationStmt) {

                mysqli_stmt_bind_param(
                    $translationStmt,
                    "iss",
                    $id,
                    $nameAr,
                    $descAr
                );

                mysqli_stmt_execute(
                    $translationStmt
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | English Translation Update
        |--------------------------------------------------------------------------
        */

        if (
            isset($body['name_en'])
            || isset($body['description_en'])
        ) {

            $nameEn = trim(
                $body['name_en']
                ?? ''
            );


            $descEn = trim(
                $body['description_en']
                ?? ''
            );


            $translationStmt =
                mysqli_prepare(
                    $con,
                    "
                    INSERT INTO videos_translations
                    (
                        video_id,
                        language_id,
                        name,
                        description,
                        is_active
                    )
                    VALUES (?, 2, ?, ?, 1)

                    ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        description = VALUES(description)
                    "
                );


            if ($translationStmt) {

                mysqli_stmt_bind_param(
                    $translationStmt,
                    "iss",
                    $id,
                    $nameEn,
                    $descEn
                );

                mysqli_stmt_execute(
                    $translationStmt
                );
            }
        }


        return [
            'status' => 'success',
            'message' =>
                'Video updated successfully',
            'data' =>
                adminGetVideoDetails(
                    $con,
                    $id
                )
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Delete Video
|--------------------------------------------------------------------------
*/

if (!function_exists('adminDeleteVideo')) {

    function adminDeleteVideo(
        $con,
        $creatorData = null
    ) {

        $body = read_json_body();


        $id = (int)(
            $_GET['id']
            ?? ($body['id'] ?? 0)
        );


        if ($id <= 0) {

            return [
                'status' => 'error',
                'message' =>
                    'Video ID is required'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Check Video
        |--------------------------------------------------------------------------
        */

        $checkStmt =
            mysqli_prepare(
                $con,
                "
                SELECT id
                FROM videos
                WHERE id = ?
                LIMIT 1
                "
            );


        if (!$checkStmt) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to check video'
            ];
        }


        mysqli_stmt_bind_param(
            $checkStmt,
            "i",
            $id
        );

        mysqli_stmt_execute(
            $checkStmt
        );


        $exists =
            mysqli_fetch_assoc(
                mysqli_stmt_get_result(
                    $checkStmt
                )
            );


        if (!$exists) {

            return [
                'status' => 'error',
                'message' =>
                    'Video not found'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Delete Translations
        |--------------------------------------------------------------------------
        */

        $translationStmt =
            mysqli_prepare(
                $con,
                "
                DELETE FROM videos_translations
                WHERE video_id = ?
                "
            );


        if ($translationStmt) {

            mysqli_stmt_bind_param(
                $translationStmt,
                "i",
                $id
            );

            mysqli_stmt_execute(
                $translationStmt
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Delete Video
        |--------------------------------------------------------------------------
        */

        $stmt =
            mysqli_prepare(
                $con,
                "
                DELETE FROM videos
                WHERE id = ?
                "
            );


        if (!$stmt) {

            return [
                'status' => 'error',
                'message' =>
                    'Failed to prepare video delete: '
                    . mysqli_error($con)
            ];
        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );


        if (mysqli_stmt_execute($stmt)) {

            return [
                'status' => 'success',
                'message' =>
                    'Video deleted successfully'
            ];
        }


        return [
            'status' => 'error',
            'message' =>
                'Failed to delete video: '
                . mysqli_error($con)
        ];
    }
}