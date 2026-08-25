<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';

if (!function_exists('ensure_site_settings_schema')) {
    function ensure_site_settings_schema($con): void {
    }
}

if (!function_exists('adminGetSiteSettings')) {
    function adminGetSiteSettings($con) {
        $res = mysqli_query($con, "SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return ['status' => 'success', 'data' => $settings];
    }
}

if (!function_exists('adminUpdateSiteSettings')) {
    function adminUpdateSiteSettings($con) {
        $body = read_json_body();
        $inputData = !empty($body) ? $body : $_POST;

        if (empty($inputData) || !is_array($inputData)) {
            return ['status' => 'error', 'message' => 'No settings data provided'];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database error preparing settings update'];
        }

        foreach ($inputData as $key => $val) {
            if (!is_string($key)) continue;
            $strVal = is_array($val) || is_object($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val;
            mysqli_stmt_bind_param($stmt, "ss", $key, $strVal);
            mysqli_stmt_execute($stmt);
        }

        return adminGetSiteSettings($con);
    }
}

if (!function_exists('getPublicAboutUsConfig')) {
    function getPublicAboutUsConfig($con) {
        $langInfo = get_language_info($con);
        $langCode = strtolower($langInfo['code'] ?? 'ar');

        $res = mysqli_query($con, "SELECT setting_key, setting_value FROM site_settings");
        $raw = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $raw[$row['setting_key']] = $row['setting_value'];
            }
        }

        $pick = function($arKey, $enKey, $default = '') use ($raw, $langCode) {
            if ($langCode === 'en') {
                return $raw[$enKey] ?? $raw[$arKey] ?? $default;
            }
            return $raw[$arKey] ?? $raw[$enKey] ?? $default;
        };

        return [
            'status' => 'success',
            'data' => [
                'language' => $langCode,
                'header' => [
                    'title' => $pick('about_title_ar', 'about_title_en', 'من نحن'),
                    'description' => $pick('about_description_ar', 'about_description_en'),
                ],
                'stats' => [
                    'courses' => $raw['about_stats_courses'] ?? '150+',
                    'books' => $raw['about_stats_books'] ?? '40+',
                    'students' => $raw['about_stats_students'] ?? '20,000+',
                ],
                'mission' => [
                    'title' => $pick('about_mission_title_ar', 'about_mission_title_en', 'رسالتنا'),
                    'text' => $pick('about_mission_text_ar', 'about_mission_text_en'),
                ],
                'offerings' => [
                    [
                        'title' => $pick('about_offering1_title_ar', 'about_offering1_title_en', 'كورسات مباشرة'),
                        'description' => $pick('about_offering1_desc_ar', 'about_offering1_desc_en'),
                    ],
                    [
                        'title' => $pick('about_offering2_title_ar', 'about_offering2_title_en', 'كورسات مسجلة'),
                        'description' => $pick('about_offering2_desc_ar', 'about_offering2_desc_en'),
                    ],
                    [
                        'title' => $pick('about_offering3_title_ar', 'about_offering3_title_en', 'كتب تعليمية'),
                        'description' => $pick('about_offering3_desc_ar', 'about_offering3_desc_en'),
                    ]
                ],
                'values' => [
                    [
                        'title' => $pick('about_val1_title_ar', 'about_val1_title_en', 'الجودة أولاً'),
                        'description' => $pick('about_val1_desc_ar', 'about_val1_desc_en'),
                    ],
                    [
                        'title' => $pick('about_val2_title_ar', 'about_val2_title_en', 'مرونة كاملة'),
                        'description' => $pick('about_val2_desc_ar', 'about_val2_desc_en'),
                    ],
                    [
                        'title' => $pick('about_val3_title_ar', 'about_val3_title_en', 'مجتمع داعم'),
                        'description' => $pick('about_val3_desc_ar', 'about_val3_desc_en'),
                    ],
                    [
                        'title' => $pick('about_val4_title_ar', 'about_val4_title_en', 'شهادات معتمدة'),
                        'description' => $pick('about_val4_desc_ar', 'about_val4_desc_en'),
                    ],
                ],
                'cta' => [
                    'title' => $pick('about_cta_title_ar', 'about_cta_title_en'),
                    'subtitle' => $pick('about_cta_subtitle_ar', 'about_cta_subtitle_en'),
                ],
                'footer' => [
                    'description' => $pick('footer_description_ar', 'footer_description_en'),
                    'facebook' => $raw['facebook_link'] ?? '',
                    'instagram' => $raw['instagram_link'] ?? '',
                    'youtube' => $raw['youtube_link'] ?? '',
                ]
            ]
        ];
    }
}

if (!function_exists('getPublicLandingConfig')) {
    function getPublicLandingConfig($con) {
        $langInfo = get_language_info($con);
        $langCode = strtolower($langInfo['code'] ?? 'ar');
        $langId = (int)($langInfo['id'] ?? 1);

        $res = mysqli_query($con, "SELECT setting_key, setting_value FROM site_settings");
        $rawSettings = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $rawSettings[$row['setting_key']] = $row['setting_value'];
            }
        }

        $settings = $rawSettings;
        $settings['lang'] = $langCode;
        $settings['hero_badge_text'] = $langCode === 'en' ? ($rawSettings['hero_badge_text_en'] ?? $rawSettings['hero_badge_text_ar'] ?? '') : ($rawSettings['hero_badge_text_ar'] ?? $rawSettings['hero_badge_text_en'] ?? '');
        $settings['hero_title'] = $langCode === 'en' ? ($rawSettings['hero_title_en'] ?? $rawSettings['hero_title_ar'] ?? '') : ($rawSettings['hero_title_ar'] ?? $rawSettings['hero_title_en'] ?? '');
        $settings['hero_subtitle'] = $langCode === 'en' ? ($rawSettings['hero_subtitle_en'] ?? $rawSettings['hero_subtitle_ar'] ?? '') : ($rawSettings['hero_subtitle_ar'] ?? $rawSettings['hero_subtitle_en'] ?? '');
        $settings['hero_primary_btn_text'] = $langCode === 'en' ? ($rawSettings['hero_primary_btn_text_en'] ?? $rawSettings['hero_primary_btn_text_ar'] ?? '') : ($rawSettings['hero_primary_btn_text_ar'] ?? $rawSettings['hero_primary_btn_text_en'] ?? '');
        $settings['hero_secondary_btn_text'] = $langCode === 'en' ? ($rawSettings['hero_secondary_btn_text_en'] ?? $rawSettings['hero_secondary_btn_text_ar'] ?? '') : ($rawSettings['hero_secondary_btn_text_ar'] ?? $rawSettings['hero_secondary_btn_text_en'] ?? '');
        $settings['stats_courses_label'] = $langCode === 'en' ? ($rawSettings['stats_courses_label_en'] ?? 'Educational Courses') : ($rawSettings['stats_courses_label_ar'] ?? 'كورس تعليمي');
        $settings['stats_books_label'] = $langCode === 'en' ? ($rawSettings['stats_books_label_en'] ?? 'Books & Notes') : ($rawSettings['stats_books_label_ar'] ?? 'كتاب ومذكرة');
        $settings['stats_teachers_label'] = $langCode === 'en' ? ($rawSettings['stats_teachers_label_en'] ?? 'Expert Teachers') : ($rawSettings['stats_teachers_label_ar'] ?? 'مدرّس متخصص');
        $settings['stats_students_label'] = $langCode === 'en' ? ($rawSettings['stats_students_label_en'] ?? 'Enrolled Students') : ($rawSettings['stats_students_label_ar'] ?? 'طالب مسجل');
        $settings['footer_description'] = $langCode === 'en' ? ($rawSettings['footer_description_en'] ?? $rawSettings['footer_description_ar'] ?? '') : ($rawSettings['footer_description_ar'] ?? $rawSettings['footer_description_en'] ?? '');

        $bannersRes = mysqli_query($con, "SELECT id, title_ar, title_en, image_ar, image_en, link, type FROM banners WHERE is_active = 1 ORDER BY id DESC");
        $banners = [];
        if ($bannersRes) {
            while ($bRow = mysqli_fetch_assoc($bannersRes)) {
                $bRow['title'] = $langCode === 'en' ? ($bRow['title_en'] ?: $bRow['title_ar']) : ($bRow['title_ar'] ?: $bRow['title_en']);
                $bRow['image'] = $langCode === 'en' ? ($bRow['image_en'] ?: $bRow['image_ar']) : ($bRow['image_ar'] ?: $bRow['image_en']);
                $banners[] = format_response_images($bRow);
            }
        }

        $coursesRes = mysqli_query($con, "
            SELECT c.id, c.price, c.image, c.teacher_id, c.show_on_website,
                   COALESCE((SELECT name FROM courses_translations ct WHERE ct.course_id = c.id AND ct.language_id = {$langId} LIMIT 1),
                            (SELECT name FROM courses_translations ct WHERE ct.course_id = c.id LIMIT 1), c.slug) AS name,
                   COALESCE((SELECT description FROM courses_translations ct WHERE ct.course_id = c.id AND ct.language_id = {$langId} LIMIT 1),
                            (SELECT description FROM courses_translations ct WHERE ct.course_id = c.id LIMIT 1)) AS description,
                   (SELECT name FROM teachers t WHERE t.id = c.teacher_id LIMIT 1) AS teacher_name,
                   (SELECT rating FROM teachers t WHERE t.id = c.teacher_id LIMIT 1) AS teacher_rating
            FROM courses c
            WHERE c.is_active = 1 AND c.show_on_website = 1
            ORDER BY c.id DESC
            LIMIT 10
        ");
        $websiteCourses = [];
        if ($coursesRes) {
            while ($cRow = mysqli_fetch_assoc($coursesRes)) {
                $cRow['id'] = (int)$cRow['id'];
                $cRow['price'] = (float)$cRow['price'];
                $cRow['teacher_rating'] = $cRow['teacher_rating'] !== null ? (float)$cRow['teacher_rating'] : 5.0;
                $websiteCourses[] = format_response_images($cRow);
            }
        }

        $booksRes = mysqli_query($con, "
            SELECT b.id, b.unit_id, b.subject_id, b.course_id, b.file_path, b.image AS cover_image, b.price, b.show_on_website,
                   COALESCE((SELECT title FROM course_books_translations bt WHERE bt.book_id = b.id AND bt.language_id = {$langId} LIMIT 1),
                            (SELECT title FROM course_books_translations bt WHERE bt.book_id = b.id LIMIT 1), b.file_path) AS title,
                   COALESCE((SELECT description FROM course_books_translations bt WHERE bt.book_id = b.id AND bt.language_id = {$langId} LIMIT 1),
                            (SELECT description FROM course_books_translations bt WHERE bt.book_id = b.id LIMIT 1)) AS description
            FROM course_books b
            WHERE b.is_active = 1
            ORDER BY b.id DESC
            LIMIT 10
        ");
        $websiteBooks = [];
        if ($booksRes) {
            while ($bRow = mysqli_fetch_assoc($booksRes)) {
                $bRow['id'] = (int)$bRow['id'];
                $bRow['price'] = (float)($bRow['price'] ?? 0.00);
                $websiteBooks[] = format_response_images($bRow);
            }
        }

        $aboutUsData = getPublicAboutUsConfig($con)['data'] ?? null;

        return [
            'status' => 'success',
            'data' => [
                'language' => $langCode,
                'settings' => $settings,
                'banners' => $banners,
                'courses' => $websiteCourses,
                'books' => $websiteBooks,
                'about_us' => $aboutUsData,
            ]
        ];
    }
}
