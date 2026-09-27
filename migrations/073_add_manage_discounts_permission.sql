-- Migration 073: give discount codes a permission of their own.
--
-- The admin discount routes were guarded by `manage_teachers` because that was the closest code
-- that already existed. It puts two unrelated jobs behind one switch: whoever is trusted to enter
-- teacher data can also cut any price on the platform, while whoever actually runs promotions
-- cannot open the page at all unless someone hands them the teachers module too.
--
-- Nothing here changes what a super_admin can do - `hasPermission()` short-circuits for that role.
-- It only matters for the scoped admin accounts, which is exactly where it should matter.
--
-- After this runs, index.php asks for `manage_discounts` instead, so any scoped admin who was
-- using the discounts page through `manage_teachers` needs the new box ticked in Admins ->
-- Permissions. That direction is deliberate: a permission that has to be granted is recoverable,
-- one that is silently inherited is not.

-- The unique index on `code` makes the insert safe to re-run.
INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
VALUES (
    'manage_discounts',
    'Manage Discount Codes',
    'discounts',
    'Create, edit, and delete discount codes, and review who redeemed them'
)
ON DUPLICATE KEY UPDATE `code` = `code`;

-- The permissions list is served translated, so a permission with no translation row would reach
-- the screen in English while everything around it is Arabic. Both languages are looked up by
-- code rather than hardcoded id, since nothing guarantees ar = 1 on every database.
INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'إدارة أكواد الخصم', 'إنشاء وتعديل وحذف أكواد الخصم، ومتابعة من استخدمها'
FROM `permissions` p
JOIN `languages` l ON l.code = 'ar'
WHERE p.code = 'manage_discounts'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'Manage Discount Codes', 'Create, edit, and delete discount codes, and review who redeemed them'
FROM `permissions` p
JOIN `languages` l ON l.code = 'en'
WHERE p.code = 'manage_discounts'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- The new permission is deliberately granted to nobody.
--
-- Copying it onto everyone who holds `manage_teachers` would have kept today's behaviour intact,
-- but it would also have re-created the exact bundling this migration exists to undo - the
-- data-entry account would still be able to cut prices, only now with a checkbox that says it
-- was intended. Whoever should have it gets it ticked once, on purpose, in Admins -> Permissions.
--
-- The queries to see who is affected before running this (super_admins are unaffected either way):
--
--   SELECT a.id, a.name, a.email FROM admins a
--   JOIN admin_permissions ap ON ap.admin_id = a.id
--   JOIN permissions p ON p.id = ap.permission_id
--   WHERE p.code = 'manage_teachers' AND LOWER(a.role) <> 'super_admin';
--
--   SELECT r.id, r.name FROM roles r
--   JOIN role_permissions rp ON rp.role_id = r.id
--   JOIN permissions p ON p.id = rp.permission_id
--   WHERE p.code = 'manage_teachers';
