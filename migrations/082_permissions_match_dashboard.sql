-- Migration 082: one permission per section of the dashboard, and nothing else in the list.
--
-- The permission checkboxes an admin is assigned from do not describe the dashboard:
--
--   * `manage_teachers` alone gates 92 backend routes and SEVENTEEN sidebar sections - teachers,
--     books, book purchase requests, courses, packages, exams, certificates, subscriptions,
--     subscription codes, live sessions, availability, consultations, reschedule requests,
--     attendance exceptions, teacher wallets, deduction appeals and HR settings. Ticking "Manage
--     Teachers" hands over the entire academic and financial side of the platform, and there is
--     no way to grant any one of those on its own. Packages and Certificates have no checkbox at
--     all because they have no permission of their own to tick.
--
--   * `manage_schools` and `manage_school_types` are offered as checkboxes for screens that do
--     not exist in the dashboard.
--
--   * Twelve permissions from an earlier design - `courses.manage`, `students.manage` and the
--     rest of the dotted set - are enforced by no route anywhere, and are nonetheless granted to
--     real admins, who gain nothing from them.
--
-- =================================================================================================
-- Nobody loses access
-- =================================================================================================
--
-- Splitting a permission normally takes access away from whoever held the old one. So every admin
-- currently holding `manage_teachers` is granted every permission split out of it, below. Their
-- access after this migration is identical to their access before it; what changes is that the
-- next admin can be given packages without also being given teacher wallets.
--
-- The routes are re-gated in the same commit. Until they are, the new permissions grant nothing
-- and `manage_teachers` still works exactly as it did, so applying this file early is harmless.
--
-- =================================================================================================
-- What is hidden rather than deleted
-- =================================================================================================
--
-- `is_visible` hides a permission from the assignment screen without revoking it from anyone who
-- holds it or breaking a route that still checks it. Deleting the rows would cascade into
-- `admin_permissions` and silently change what existing admins can do, which is exactly the kind
-- of change that is noticed weeks later by someone who cannot open a page any more.

-- =================================================================================================
-- 1. is_visible
-- =================================================================================================

SET @has_visible := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions' AND COLUMN_NAME = 'is_visible');
SET @sql := IF(@has_visible = 0,
    'ALTER TABLE `permissions` ADD COLUMN `is_visible` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''Whether it appears on the admin permission screen'' AFTER `module`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 2. The sections that had no permission of their own
-- =================================================================================================

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
SELECT * FROM (
    SELECT 'manage_packages' AS code, 'Manage Packages' AS name, 'packages' AS module, 'Create and edit subscription packages' AS description UNION ALL
    SELECT 'manage_exams', 'Manage Exams', 'exams', 'Create and edit exams, questions and review attempts' UNION ALL
    SELECT 'manage_certificates', 'Manage Certificates', 'certificates', 'Issue, revoke and review student certificates' UNION ALL
    SELECT 'manage_subscriptions', 'Manage Student Subscriptions', 'subscriptions', 'Grant, pause and cancel subscriptions, and manage subscription codes' UNION ALL
    SELECT 'manage_sessions', 'Manage Live Sessions', 'sessions', 'Live sessions, teacher availability and reschedule requests' UNION ALL
    SELECT 'manage_consultations', 'Manage Consultations', 'consultations', 'Review and answer student consultations' UNION ALL
    SELECT 'manage_attendance', 'Manage Attendance', 'attendance', 'Attendance exceptions and absence records' UNION ALL
    SELECT 'manage_wallets', 'Manage Teacher Wallets', 'wallets', 'Teacher wallets, payouts and deduction appeals' UNION ALL
    SELECT 'manage_hr', 'Manage HR Settings', 'hr', 'Deduction rules and attendance policy'
) AS incoming
WHERE NOT EXISTS (SELECT 1 FROM `permissions` p WHERE p.code = incoming.code);

-- =================================================================================================
-- 3. Their Arabic and English names
-- =================================================================================================
--
-- Matching the sidebar labels word for word. A checkbox called something the sidebar does not say
-- is a checkbox nobody can map to a screen.

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`)
SELECT p.id, 1, t.name_ar FROM `permissions` p JOIN (
    SELECT 'manage_packages' AS code, 'إدارة الباقات' AS name_ar UNION ALL
    SELECT 'manage_exams', 'إدارة الامتحانات' UNION ALL
    SELECT 'manage_certificates', 'إدارة الشهادات' UNION ALL
    SELECT 'manage_subscriptions', 'إدارة اشتراكات الطلاب وأكواد الاشتراك' UNION ALL
    SELECT 'manage_sessions', 'إدارة الحصص المباشرة والمواعيد' UNION ALL
    SELECT 'manage_consultations', 'إدارة الاستشارات' UNION ALL
    SELECT 'manage_attendance', 'إدارة الحضور والاستثناءات' UNION ALL
    SELECT 'manage_wallets', 'إدارة محافظ المدرسين والتظلمات' UNION ALL
    SELECT 'manage_hr', 'إدارة إعدادات الموارد البشرية'
) t ON t.code = p.code
WHERE NOT EXISTS (SELECT 1 FROM `permissions_translations` x WHERE x.permission_id = p.id AND x.language_id = 1);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`)
SELECT p.id, 2, p.name FROM `permissions` p
WHERE p.code IN ('manage_packages','manage_exams','manage_certificates','manage_subscriptions',
                 'manage_sessions','manage_consultations','manage_attendance','manage_wallets','manage_hr')
  AND NOT EXISTS (SELECT 1 FROM `permissions_translations` x WHERE x.permission_id = p.id AND x.language_id = 2);

-- `manage_teachers` now means only the teachers screen, so its label must stop promising the rest.
UPDATE `permissions_translations` pt
JOIN `permissions` p ON p.id = pt.permission_id
SET pt.name = 'إدارة المدرسين'
WHERE p.code = 'manage_teachers' AND pt.language_id = 1;

-- =================================================================================================
-- 4. Nobody loses what they already had
-- =================================================================================================

INSERT INTO `admin_permissions` (`admin_id`, `permission_id`)
SELECT ap.admin_id, p.id
FROM `admin_permissions` ap
JOIN `permissions` old ON old.id = ap.permission_id AND old.code = 'manage_teachers'
JOIN `permissions` p ON p.code IN (
    'manage_packages','manage_exams','manage_certificates','manage_subscriptions',
    'manage_sessions','manage_consultations','manage_attendance','manage_wallets','manage_hr',
    'manage_courses','manage_books'
)
WHERE NOT EXISTS (
    SELECT 1 FROM `admin_permissions` x WHERE x.admin_id = ap.admin_id AND x.permission_id = p.id
);

-- =================================================================================================
-- 5. Hide what the dashboard has no screen for
-- =================================================================================================
--
-- The dotted codes are the earlier design and are checked by no route in the codebase; the two
-- school ones guard API routes that no dashboard page reaches. Hidden, not deleted, so that
-- nobody's existing grants change.
--
-- `manage_roles` stays visible: it has no sidebar entry of its own, but the "Admins and
-- Permissions" screen calls /admin/roles, so hiding it would make a permission that screen needs
-- impossible to grant.

UPDATE `permissions` SET `is_visible` = 0
WHERE `code` LIKE '%.%' OR `code` IN ('manage_school_types', 'manage_schools');
