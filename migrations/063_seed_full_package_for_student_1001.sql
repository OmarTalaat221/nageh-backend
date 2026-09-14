-- Migration 063: seed one COMPLETE package (kind = 'package') and subscribe student 1001 to it.
--
-- Purpose: the only package that existed (id 229) had no image, no teacher, no units, no lessons,
-- no video URLs and no live sessions, so the student-facing package page and
-- /student/my-courses-progress had nothing real to render. This seeds a fully populated package:
-- course + ar/en translations, cover image, preview video, what_you_will_learn, an assigned teacher,
-- 3 units, 9 recorded lessons WITH playable video_url values, 2 weekly live sessions,
-- an ACTIVE 90-day subscription for student 1001, partial watch progress, and 2 approved reviews.
--
-- Safe to re-run: every insert is guarded by a NOT EXISTS check keyed on a stable `code`/slug,
-- so running it twice does not duplicate anything.

-- Explicit COLLATE matters here: every table below is utf8mb4_unicode_ci, but a bare
-- `SET NAMES utf8mb4` defaults the session (and therefore every @variable's collation) to
-- utf8mb4_general_ci, which then collides with the column collation on every `= @var`
-- comparison ("Illegal mix of collations ... for operation '='"). Pin it explicitly instead.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION sql_mode = '';
START TRANSACTION;

-- ---------------------------------------------------------------------------
-- Parameters
-- ---------------------------------------------------------------------------
SET @slug         = 'package-geography-g3-2026';
SET @student_id   = 1001;
SET @subject_id   = 8;   -- الجغرافيا
SET @ay_id        = 12;  -- الصف الثالث الثانوي
SET @teacher_id   = 208; -- أ. نادين سمير (active, already teaches Geography)
SET @lang_ar      = 1;
SET @lang_en      = 2;

-- ---------------------------------------------------------------------------
-- 1) The package course itself
-- ---------------------------------------------------------------------------
INSERT INTO `courses`
    (`category_id`, `subject_id`, `academic_year_id`, `teacher_id`, `slug`, `type`, `kind`,
     `price`, `image`, `lessons_count`, `is_active`, `show_on_website`,
     `created_by_type`, `created_by_id`, `what_you_will_learn`, `video_preview_url`)
SELECT NULL, @subject_id, @ay_id, @teacher_id, @slug, 'recorded', 'package',
       1200.00, 'https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=1200&q=80', 9, 1, 1,
       'admin', 1, 'إتقان منهج الجغرافيا للصف الثالث الثانوي كاملًا
قراءة الخرائط الكنتورية والاقتصادية وتحليلها
ربط الظواهر الطبيعية بالأنشطة البشرية والاقتصادية
حل نماذج امتحانات الأعوام السابقة خطوة بخطوة
كتابة إجابة نموذجية كاملة الدرجات في الأسئلة المقالية
متابعة أسبوعية مباشرة مع المعلم للإجابة عن استفساراتك', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/VolkswagenGTIReview.mp4'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `courses` c WHERE c.`slug` = @slug);

SET @course_id = (SELECT `id` FROM `courses` WHERE `slug` = @slug LIMIT 1);

-- Arabic + English translations
INSERT INTO `courses_translations` (`course_id`, `language_id`, `name`, `description`, `is_active`, `what_you_will_learn`)
SELECT @course_id, @lang_ar, 'الباقة المتكاملة - الجغرافيا للصف الثالث الثانوي', 'باقة متكاملة تجمع بين المحتوى المسجَّل والحصص المباشرة الأسبوعية لمنهج الجغرافيا للصف الثالث الثانوي. تشمل ثلاث وحدات كاملة بتسعة دروس مصوَّرة، وحصتين مباشرتين كل أسبوع للمتابعة وحل الأسئلة، ومراجعة نهائية على نماذج امتحانات الأعوام السابقة.', 1, 'إتقان منهج الجغرافيا للصف الثالث الثانوي كاملًا
قراءة الخرائط الكنتورية والاقتصادية وتحليلها
ربط الظواهر الطبيعية بالأنشطة البشرية والاقتصادية
حل نماذج امتحانات الأعوام السابقة خطوة بخطوة
كتابة إجابة نموذجية كاملة الدرجات في الأسئلة المقالية
متابعة أسبوعية مباشرة مع المعلم للإجابة عن استفساراتك'
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM `courses_translations` ct WHERE ct.`course_id` = @course_id AND ct.`language_id` = @lang_ar
);

INSERT INTO `courses_translations` (`course_id`, `language_id`, `name`, `description`, `is_active`, `what_you_will_learn`)
SELECT @course_id, @lang_en, 'The Complete Package - Geography, Grade 12', 'An all-in-one package combining recorded content with weekly live sessions for the Grade 12 Geography syllabus. It covers three full units across nine recorded lessons, two live follow-up sessions every week, and a final revision built on past exam papers.', 1, 'Master the full Grade 12 Geography syllabus
Read and analyse contour and economic maps
Connect physical phenomena to human and economic activity
Work through past exam papers step by step
Write full-mark model answers for essay questions
Get weekly live follow-up with the teacher'
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM `courses_translations` ct WHERE ct.`course_id` = @course_id AND ct.`language_id` = @lang_en
);

-- Link the teacher to the course (this is what fills the `teachers` array in the API responses)
INSERT INTO `course_teachers` (`course_id`, `teacher_id`, `is_active`)
SELECT @course_id, @teacher_id, 1 FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `course_teachers` ct WHERE ct.`course_id` = @course_id AND ct.`teacher_id` = @teacher_id
);

-- ---------------------------------------------------------------------------
-- 2) Units, and 3 recorded lessons per unit
--    A lesson needs THREE rows: `videos` (metadata), `course_videos` sharing the SAME id
--    (this is where video_url lives - the student endpoints join them on v.id = cv.id),
--    and `videos_translations` for the title/description.
-- ---------------------------------------------------------------------------

-- === Unit 1: الوحدة الأولى: أسس الجغرافيا الطبيعية ===
INSERT INTO `units` (`course_id`, `subject_id`, `code`, `order_num`, `videos_drip_enabled`,
                     `duration`, `duration_unit`, `lessons_count`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @course_id, @subject_id, 'PKG-G3-U1', 1, 0, 3, 'week', 3, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units` u WHERE u.`course_id` = @course_id AND u.`code` = 'PKG-G3-U1');
SET @unit_1 = (SELECT `id` FROM `units` WHERE `course_id` = @course_id AND `code` = 'PKG-G3-U1' LIMIT 1);

INSERT INTO `units_translations` (`unit_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @unit_1, @lang_ar, 'الوحدة الأولى: أسس الجغرافيا الطبيعية', 'تأسيس كامل لمفاهيم الجغرافيا الطبيعية: التضاريس والمناخ والموارد المائية، مع تدريبات تطبيقية على الخرائط.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units_translations` t WHERE t.`unit_id` = @unit_1 AND t.`language_id` = @lang_ar);
INSERT INTO `units_translations` (`unit_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @unit_1, @lang_en, 'Unit 1: Foundations of Physical Geography', 'A complete grounding in physical geography: landforms, climate and water resources, with hands-on map practice.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units_translations` t WHERE t.`unit_id` = @unit_1 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_1, 'PKG-G3-U1-L1', 1500, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_1 AND v.`code` = 'PKG-G3-U1-L1');
SET @v_1_1 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_1 AND `code` = 'PKG-G3-U1-L1' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_1_1, @subject_id, @course_id, @unit_1, '1500', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 1, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_1_1);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_1_1, @lang_ar, 'الدرس الأول: مدخل إلى الجغرافيا الطبيعية', 'تعريف علم الجغرافيا الطبيعية وفروعه وأهميته في فهم البيئة المحيطة.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_1_1 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_1_1, @lang_en, 'Lesson 1: Introduction to Physical Geography', 'What physical geography studies, its branches, and why it matters.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_1_1 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_1, 'PKG-G3-U1-L2', 1740, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_1 AND v.`code` = 'PKG-G3-U1-L2');
SET @v_1_2 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_1 AND `code` = 'PKG-G3-U1-L2' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_1_2, @subject_id, @course_id, @unit_1, '1740', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', 2, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_1_2);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_1_2, @lang_ar, 'الدرس الثاني: التضاريس وأشكال سطح الأرض', 'أنواع التضاريس وطرق تمثيلها على الخرائط الكنتورية مع أمثلة محلية.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_1_2 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_1_2, @lang_en, 'Lesson 2: Landforms and Relief', 'Types of landforms and how to read them on contour maps, with local examples.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_1_2 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_1, 'PKG-G3-U1-L3', 1320, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_1 AND v.`code` = 'PKG-G3-U1-L3');
SET @v_1_3 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_1 AND `code` = 'PKG-G3-U1-L3' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_1_3, @subject_id, @course_id, @unit_1, '1320', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 3, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_1_3);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_1_3, @lang_ar, 'الدرس الثالث: المناخ والأقاليم المناخية', 'عناصر المناخ وعوامل التحكم فيه وتوزيع الأقاليم المناخية الكبرى.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_1_3 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_1_3, @lang_en, 'Lesson 3: Climate and Climate Regions', 'Climate elements, controlling factors, and the distribution of major climate regions.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_1_3 AND t.`language_id` = @lang_en);


-- === Unit 2: الوحدة الثانية: الجغرافيا البشرية والاقتصادية ===
INSERT INTO `units` (`course_id`, `subject_id`, `code`, `order_num`, `videos_drip_enabled`,
                     `duration`, `duration_unit`, `lessons_count`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @course_id, @subject_id, 'PKG-G3-U2', 2, 0, 4, 'week', 3, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units` u WHERE u.`course_id` = @course_id AND u.`code` = 'PKG-G3-U2');
SET @unit_2 = (SELECT `id` FROM `units` WHERE `course_id` = @course_id AND `code` = 'PKG-G3-U2' LIMIT 1);

INSERT INTO `units_translations` (`unit_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @unit_2, @lang_ar, 'الوحدة الثانية: الجغرافيا البشرية والاقتصادية', 'دراسة السكان والعمران والأنشطة الاقتصادية، وربطها بالخريطة الاقتصادية لمصر والوطن العربي.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units_translations` t WHERE t.`unit_id` = @unit_2 AND t.`language_id` = @lang_ar);
INSERT INTO `units_translations` (`unit_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @unit_2, @lang_en, 'Unit 2: Human and Economic Geography', 'Population, settlement and economic activity, tied to the economic map of Egypt and the Arab world.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units_translations` t WHERE t.`unit_id` = @unit_2 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_2, 'PKG-G3-U2-L1', 1620, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_2 AND v.`code` = 'PKG-G3-U2-L1');
SET @v_2_1 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_2 AND `code` = 'PKG-G3-U2-L1' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_2_1, @subject_id, @course_id, @unit_2, '1620', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 1, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_2_1);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_2_1, @lang_ar, 'الدرس الأول: السكان والتركيب السكاني', 'نمو السكان وتوزيعهم وتركيبهم النوعي والعمري وأثر ذلك على التنمية.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_2_1 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_2_1, @lang_en, 'Lesson 1: Population and Population Structure', 'Population growth, distribution, age and sex structure, and the effect on development.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_2_1 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_2, 'PKG-G3-U2-L2', 1800, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_2 AND v.`code` = 'PKG-G3-U2-L2');
SET @v_2_2 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_2 AND `code` = 'PKG-G3-U2-L2' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_2_2, @subject_id, @course_id, @unit_2, '1800', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4', 2, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_2_2);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_2_2, @lang_ar, 'الدرس الثاني: الأنشطة الاقتصادية', 'الزراعة والصناعة والتجارة والخدمات، ومقومات كل نشاط ومشكلاته.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_2_2 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_2_2, @lang_en, 'Lesson 2: Economic Activities', 'Agriculture, industry, trade and services: the assets and problems of each.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_2_2 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_2, 'PKG-G3-U2-L3', 1440, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_2 AND v.`code` = 'PKG-G3-U2-L3');
SET @v_2_3 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_2 AND `code` = 'PKG-G3-U2-L3' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_2_3, @subject_id, @course_id, @unit_2, '1440', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4', 3, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_2_3);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_2_3, @lang_ar, 'الدرس الثالث: النقل والتجارة الدولية', 'شبكات النقل وطرق التجارة العالمية وأثر موقع مصر عليها.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_2_3 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_2_3, @lang_en, 'Lesson 3: Transport and International Trade', 'Transport networks, world trade routes, and the impact of Egypt''s location.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_2_3 AND t.`language_id` = @lang_en);


-- === Unit 3: الوحدة الثالثة: المراجعة النهائية وحل الامتحانات ===
INSERT INTO `units` (`course_id`, `subject_id`, `code`, `order_num`, `videos_drip_enabled`,
                     `duration`, `duration_unit`, `lessons_count`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @course_id, @subject_id, 'PKG-G3-U3', 3, 0, 2, 'week', 3, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units` u WHERE u.`course_id` = @course_id AND u.`code` = 'PKG-G3-U3');
SET @unit_3 = (SELECT `id` FROM `units` WHERE `course_id` = @course_id AND `code` = 'PKG-G3-U3' LIMIT 1);

INSERT INTO `units_translations` (`unit_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @unit_3, @lang_ar, 'الوحدة الثالثة: المراجعة النهائية وحل الامتحانات', 'مراجعة مركّزة على المنهج كاملًا مع حل نماذج امتحانات سابقة وتدريب على أسلوب الإجابة النموذجية.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units_translations` t WHERE t.`unit_id` = @unit_3 AND t.`language_id` = @lang_ar);
INSERT INTO `units_translations` (`unit_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @unit_3, @lang_en, 'Unit 3: Final Revision and Exam Practice', 'A focused revision of the whole syllabus with past-paper practice and model-answer technique.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `units_translations` t WHERE t.`unit_id` = @unit_3 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_3, 'PKG-G3-U3-L1', 2100, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_3 AND v.`code` = 'PKG-G3-U3-L1');
SET @v_3_1 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_3 AND `code` = 'PKG-G3-U3-L1' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_3_1, @subject_id, @course_id, @unit_3, '2100', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4', 1, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_3_1);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_3_1, @lang_ar, 'الدرس الأول: مراجعة شاملة للوحدة الأولى والثانية', 'تلخيص شامل لأهم النقاط مع خرائط ذهنية تسهّل المذاكرة.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_3_1 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_3_1, @lang_en, 'Lesson 1: Full Revision of Units 1 and 2', 'A comprehensive summary with mind maps that make revision easier.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_3_1 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_3, 'PKG-G3-U3-L2', 1980, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_3 AND v.`code` = 'PKG-G3-U3-L2');
SET @v_3_2 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_3 AND `code` = 'PKG-G3-U3-L2' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_3_2, @subject_id, @course_id, @unit_3, '1980', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4', 2, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_3_2);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_3_2, @lang_ar, 'الدرس الثاني: حل نماذج امتحانات الأعوام السابقة', 'حل تفصيلي لنماذج امتحانات حقيقية مع توضيح أسباب الأخطاء الشائعة.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_3_2 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_3_2, @lang_en, 'Lesson 2: Solving Past Exam Papers', 'Detailed solutions to real past papers, explaining the most common mistakes.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_3_2 AND t.`language_id` = @lang_en);

INSERT INTO `videos` (`unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`)
SELECT @unit_3, 'PKG-G3-U3-L3', 1680, 'published', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos` v WHERE v.`unit_id` = @unit_3 AND v.`code` = 'PKG-G3-U3-L3');
SET @v_3_3 = (SELECT `id` FROM `videos` WHERE `unit_id` = @unit_3 AND `code` = 'PKG-G3-U3-L3' LIMIT 1);
-- same id on purpose: course_videos.id === videos.id
INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`,
                             `order_num`, `is_active`, `created_by_type`, `created_by_id`)
SELECT @v_3_3, @subject_id, @course_id, @unit_3, '1680', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4', 3, 1, 'admin', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `course_videos` cv WHERE cv.`id` = @v_3_3);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_3_3, @lang_ar, 'الدرس الثالث: التدريب على أسلوب الإجابة النموذجية', 'كيف تكتب إجابة كاملة الدرجات في الأسئلة المقالية وأسئلة الخرائط.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_3_3 AND t.`language_id` = @lang_ar);
INSERT INTO `videos_translations` (`video_id`, `language_id`, `name`, `description`, `is_active`)
SELECT @v_3_3, @lang_en, 'Lesson 3: Model-Answer Technique', 'How to write a full-mark answer for essay questions and map questions.', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `videos_translations` t WHERE t.`video_id` = @v_3_3 AND t.`language_id` = @lang_en);


-- ---------------------------------------------------------------------------
-- 3) The weekly live schedule (what makes a 'package' a package)
-- ---------------------------------------------------------------------------
INSERT INTO `class_sessions` (`teacher_id`, `subject_id`, `course_id`, `code`, `day_of_week`,
                              `start_time`, `end_time`, `room`, `is_online`, `meeting_link`,
                              `grade`, `academic_year_id`, `is_active`)
SELECT @teacher_id, @subject_id, @course_id, 'PKG-G3-S1', 'Saturday', '18:00:00', '19:30:00', NULL, 1, 'https://meet.google.com/najeh-pkg-g3-geo',
       'الصف الثالث الثانوي', @ay_id, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `class_sessions` s WHERE s.`course_id` = @course_id AND s.`code` = 'PKG-G3-S1');

INSERT INTO `class_sessions` (`teacher_id`, `subject_id`, `course_id`, `code`, `day_of_week`,
                              `start_time`, `end_time`, `room`, `is_online`, `meeting_link`,
                              `grade`, `academic_year_id`, `is_active`)
SELECT @teacher_id, @subject_id, @course_id, 'PKG-G3-S2', 'Tuesday', '18:00:00', '19:30:00', NULL, 1, 'https://meet.google.com/najeh-pkg-g3-geo',
       'الصف الثالث الثانوي', @ay_id, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `class_sessions` s WHERE s.`course_id` = @course_id AND s.`code` = 'PKG-G3-S2');

-- ---------------------------------------------------------------------------
-- 4) Give student 1001 an ACTIVE 90-day subscription to this package
--    (his existing package subscription, id 1083, is frozen - this is a separate, working one)
-- ---------------------------------------------------------------------------
INSERT INTO `student_subscriptions` (`student_id`, `subscription_code_id`, `subscription_type`,
                                     `start_date`, `end_date`, `is_active`)
SELECT @student_id, NULL, 'package', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1 FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `student_subscriptions` ss
    JOIN `student_subscription_courses` ssc ON ssc.`student_subscription_id` = ss.`id`
    WHERE ss.`student_id` = @student_id AND ssc.`course_id` = @course_id
);

SET @subscription_id = (
    SELECT ss.`id` FROM `student_subscriptions` ss
    LEFT JOIN `student_subscription_courses` ssc ON ssc.`student_subscription_id` = ss.`id`
    WHERE ss.`student_id` = @student_id
      AND (ssc.`course_id` = @course_id OR ssc.`course_id` IS NULL)
      AND ss.`subscription_type` = 'package' AND ss.`is_active` = 1
    ORDER BY ss.`id` DESC LIMIT 1
);

INSERT INTO `student_subscription_courses` (`student_subscription_id`, `course_id`)
SELECT @subscription_id, @course_id FROM DUAL
WHERE @subscription_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM `student_subscription_courses` x
    WHERE x.`student_subscription_id` = @subscription_id AND x.`course_id` = @course_id
);

-- ---------------------------------------------------------------------------
-- 5) Some real watch progress, so the progress page is not a flat 0%
--    3 lessons completed + 1 half-watched  =>  3/9  =  33.33%
-- ---------------------------------------------------------------------------
INSERT INTO `student_video_progress` (`student_id`, `video_id`, `is_completed`, `watched_seconds`,
                                      `total_seconds`, `last_position_seconds`, `completed_at`)
SELECT @student_id, @v_1_1, 1, 1500, 1500, 1500, NOW() FROM DUAL
WHERE @v_1_1 IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM `student_video_progress` p WHERE p.`student_id` = @student_id AND p.`video_id` = @v_1_1
);

INSERT INTO `student_video_progress` (`student_id`, `video_id`, `is_completed`, `watched_seconds`,
                                      `total_seconds`, `last_position_seconds`, `completed_at`)
SELECT @student_id, @v_1_2, 1, 1740, 1740, 1740, NOW() FROM DUAL
WHERE @v_1_2 IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM `student_video_progress` p WHERE p.`student_id` = @student_id AND p.`video_id` = @v_1_2
);

INSERT INTO `student_video_progress` (`student_id`, `video_id`, `is_completed`, `watched_seconds`,
                                      `total_seconds`, `last_position_seconds`, `completed_at`)
SELECT @student_id, @v_1_3, 1, 1320, 1320, 1320, NOW() FROM DUAL
WHERE @v_1_3 IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM `student_video_progress` p WHERE p.`student_id` = @student_id AND p.`video_id` = @v_1_3
);

INSERT INTO `student_video_progress` (`student_id`, `video_id`, `is_completed`, `watched_seconds`,
                                      `total_seconds`, `last_position_seconds`, `completed_at`)
SELECT @student_id, @v_2_1, 0, 810, 1620, 810, NULL FROM DUAL
WHERE @v_2_1 IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM `student_video_progress` p WHERE p.`student_id` = @student_id AND p.`video_id` = @v_2_1
);

-- ---------------------------------------------------------------------------
-- 6) Two approved reviews from OTHER students, so rating / reviews_count are not zero
--    (student 1001 is skipped on purpose, so he can still leave his own review from the app)
-- ---------------------------------------------------------------------------
SET @rater_1 = (SELECT `id` FROM `students` WHERE `id` <> @student_id AND `is_active` = 1 ORDER BY `id` LIMIT 1);
SET @rater_2 = (SELECT `id` FROM `students` WHERE `id` <> @student_id AND `is_active` = 1 ORDER BY `id` LIMIT 1 OFFSET 1);

INSERT INTO `course_ratings` (`student_id`, `course_id`, `rating`, `comment`, `is_approved`)
SELECT @rater_1, @course_id, 5, 'شرح ممتاز ومنظَّم، والحصص المباشرة الأسبوعية فرَّقت معي كثيرًا في المراجعة.', 1 FROM DUAL
WHERE @rater_1 IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM `course_ratings` r WHERE r.`student_id` = @rater_1 AND r.`course_id` = @course_id
);

INSERT INTO `course_ratings` (`student_id`, `course_id`, `rating`, `comment`, `is_approved`)
SELECT @rater_2, @course_id, 4, 'المحتوى المسجَّل واضح والأمثلة على الخرائط عملية جدًا. أنصح بها.', 1 FROM DUAL
WHERE @rater_2 IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM `course_ratings` r WHERE r.`student_id` = @rater_2 AND r.`course_id` = @course_id
);

-- ---------------------------------------------------------------------------
-- 7) Keep the cached counters in sync with what was just inserted
-- ---------------------------------------------------------------------------
UPDATE `units` u
SET u.`lessons_count` = (SELECT COUNT(*) FROM `videos` v WHERE v.`unit_id` = u.`id` AND v.`is_active` = 1)
WHERE u.`course_id` = @course_id;

UPDATE `courses` c
SET c.`lessons_count` = (
    SELECT COUNT(*) FROM `videos` v JOIN `units` u ON v.`unit_id` = u.`id`
    WHERE u.`course_id` = c.`id` AND u.`is_active` = 1 AND v.`is_active` = 1
)
WHERE c.`id` = @course_id;

COMMIT;

-- ---------------------------------------------------------------------------
-- Verification (read-only) - run this after COMMIT to confirm the seed landed
-- ---------------------------------------------------------------------------
SELECT @course_id AS course_id, @subscription_id AS subscription_id;

SELECT c.`id`, c.`slug`, c.`kind`, c.`price`, ct.`name`,
       (SELECT COUNT(*) FROM `units` u WHERE u.`course_id` = c.`id`) AS units_count,
       (SELECT COUNT(*) FROM `videos` v JOIN `units` u ON v.`unit_id` = u.`id` WHERE u.`course_id` = c.`id`) AS lessons_count,
       (SELECT COUNT(*) FROM `course_videos` cv WHERE cv.`course_id` = c.`id` AND cv.`video_url` IS NOT NULL) AS lessons_with_video_url,
       (SELECT COUNT(*) FROM `class_sessions` s WHERE s.`course_id` = c.`id`) AS live_sessions,
       (SELECT COUNT(*) FROM `course_ratings` r WHERE r.`course_id` = c.`id`) AS reviews
FROM `courses` c
LEFT JOIN `courses_translations` ct ON ct.`course_id` = c.`id` AND ct.`language_id` = 1
WHERE c.`id` = @course_id;
