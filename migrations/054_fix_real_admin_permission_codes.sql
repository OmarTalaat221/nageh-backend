-- Fixes a real bug: every `requirePermission($con, $adminData, '...')` call across the admin
-- routes in index.php checks permission codes like `manage_students`, `manage_teachers`,
-- `view_dashboard`, etc. (the underscore-style codes from migration 005). But the `permissions`
-- table that actually ships in the base dump only ever seeded 12 OLDER, unrelated dot-style codes
-- (`students.manage`, `dashboard.view`, ...) that no route checks anywhere. Migration 005 tried to
-- add the real codes via `INSERT ... ON DUPLICATE KEY UPDATE`, but relies on a UNIQUE KEY on
-- `permissions.code` that only gets created if `CREATE TABLE IF NOT EXISTS` actually creates the
-- table - which it won't, since the table already exists from the base dump. If 005 was never run
-- (or ran against a table with no unique key), the real codes never made it in.
--
-- Net effect: no non-super_admin could ever pass ANY of these permission checks, no matter what
-- was ticked in the admin permissions UI, because the codes the checkbox list showed
-- (the 12 legacy ones) don't match the codes the routes actually check.
--
-- This migration is idempotent (safe to re-run): it inserts each of the 16 real codes only if
-- missing, and adds their Arabic/English translations only if missing - using NOT EXISTS guards
-- instead of relying on a unique key that may or may not be there.
--
-- It does NOT touch or remove the old 12 legacy permissions (dashboard.view, students.manage, ...)
-- - they're left alone in the DB so nothing else referencing them breaks. The admin UI has been
-- updated separately to only show the 16 real, enforced codes.

-- ============================================================
-- STEP 1 — insert the 16 permission codes actually enforced by index.php, if missing.
-- ============================================================
INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT
    'view_dashboard' AS code, 'View Dashboard & Statistics' AS name, 'dashboard' AS module, 'Access dashboard and statistics' AS description
) t WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'view_dashboard');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_admins', 'Manage Admins', 'admins', 'Add, edit, and delete admin users') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_admins');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_roles', 'Manage Roles & Permissions', 'roles', 'Full CRUD access for roles and permissions') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_roles');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_students', 'Manage Students', 'students', 'Add, edit, and delete students') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_students');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_teachers', 'Manage Teachers', 'teachers', 'Add, edit, approve, and delete teachers, and manage their content (units, videos, books, exams, certificates)') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_teachers');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_parents', 'Manage Parents & Student Linkage', 'parents', 'Add, edit parents and link students') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_parents');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_banners', 'Manage Banners', 'banners', 'Full CRUD access for banners and view analytics') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_banners');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_languages', 'Manage Languages', 'languages', 'Full CRUD access for languages') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_languages');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_cities', 'Manage Regions', 'cities', 'Full CRUD access for regions') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_cities');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_school_types', 'Manage School Types', 'school_types', 'Full CRUD access for school types') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_school_types');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_schools', 'Manage Schools', 'schools', 'Full CRUD access for schools') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_schools');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_subjects', 'Manage Subjects', 'subjects', 'Full CRUD access for subjects') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_subjects');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_courses', 'Manage Courses', 'courses', 'Full CRUD access for courses, units, videos, exams, questions, certificates and books') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_courses');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_books', 'Manage Books', 'books', 'Full CRUD access for books and purchase requests') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_books');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_academic_years', 'Manage Grades (Academic Years)', 'academic_years', 'Full CRUD access for grades / academic years') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_academic_years');

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (SELECT 'manage_course_categories', 'Manage Course Categories', 'course_categories', 'Full CRUD access for course categories') t
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `code` = 'manage_course_categories');

-- ============================================================
-- STEP 2 — add Arabic (language_id=1) and English (language_id=2) translations for every
-- permission inserted above, only where a translation row doesn't already exist.
-- ============================================================
INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, 1, tr.name_ar, tr.description_ar
FROM `permissions` p
JOIN (
    SELECT 'view_dashboard' AS code, 'عرض لوحة التحكم' AS name_ar, 'الوصول للوحة التحكم والإحصائيات' AS description_ar
    UNION ALL SELECT 'manage_admins', 'إدارة المشرفين والصلاحيات', 'إضافة وتعديل وحذف حسابات المشرفين وصلاحياتهم'
    UNION ALL SELECT 'manage_roles', 'إدارة الأدوار والصلاحيات (قديم)', 'إدارة كاملة للأدوار والصلاحيات (نظام قديم غير مستخدم حاليًا)'
    UNION ALL SELECT 'manage_students', 'إدارة الطلاب', 'إضافة وتعديل وحذف الطلاب'
    UNION ALL SELECT 'manage_teachers', 'إدارة المدرسين والمحتوى التعليمي', 'إضافة وتعديل وحذف المدرسين، وإدارة محتواهم من كورسات ووحدات وفيديوهات وامتحانات وشهادات'
    UNION ALL SELECT 'manage_parents', 'إدارة أولياء الأمور', 'إضافة وتعديل أولياء الأمور وربطهم بالطلاب'
    UNION ALL SELECT 'manage_banners', 'إدارة البانرات', 'إدارة كاملة للبانرات وعرض إحصائيات المشاهدة'
    UNION ALL SELECT 'manage_languages', 'إدارة اللغات', 'إدارة كاملة للغات المتاحة في المنصة'
    UNION ALL SELECT 'manage_cities', 'إدارة المناطق', 'إدارة كاملة للمناطق الجغرافية'
    UNION ALL SELECT 'manage_school_types', 'إدارة أنواع المدارس', 'إدارة كاملة لأنواع المدارس'
    UNION ALL SELECT 'manage_schools', 'إدارة المدارس', 'إدارة كاملة للمدارس'
    UNION ALL SELECT 'manage_subjects', 'إدارة المواد الدراسية', 'إدارة كاملة للمواد الدراسية'
    UNION ALL SELECT 'manage_courses', 'إدارة الكورسات', 'إدارة كاملة للكورسات والوحدات والفيديوهات والامتحانات والأسئلة والشهادات والكتب'
    UNION ALL SELECT 'manage_books', 'إدارة الكتب', 'إدارة كاملة للكتب وطلبات الشراء'
    UNION ALL SELECT 'manage_academic_years', 'إدارة الصفوف الدراسية', 'إدارة كاملة للصفوف الدراسية'
    UNION ALL SELECT 'manage_course_categories', 'إدارة تصنيفات الكورسات', 'إدارة كاملة لتصنيفات الكورسات'
) tr ON tr.code = p.code
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions_translations` pt WHERE pt.permission_id = p.id AND pt.language_id = 1
);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, 2, p.name, p.description
FROM `permissions` p
WHERE p.code IN (
    'view_dashboard','manage_admins','manage_roles','manage_students','manage_teachers','manage_parents',
    'manage_banners','manage_languages','manage_cities','manage_school_types','manage_schools','manage_subjects',
    'manage_courses','manage_books','manage_academic_years','manage_course_categories'
)
AND NOT EXISTS (
    SELECT 1 FROM `permissions_translations` pt WHERE pt.permission_id = p.id AND pt.language_id = 2
);
