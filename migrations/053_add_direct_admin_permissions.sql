-- Switches admin permissions from role-based (admins.role_id -> role_permissions) to direct
-- per-admin assignment: a new `admin_permissions` table lets an admin be granted individual
-- permissions via checkboxes, without needing a role in between.
--
-- The `roles` / `role_permissions` tables and `admins.role`/`admins.role_id` columns are left
-- untouched (not dropped) so nothing else that reads them breaks and existing data is not lost.
--
-- Backfill: every existing admin keeps exactly the permissions their current role already grants,
-- so nobody loses access the moment this ships. `super_admin` accounts are skipped (they already
-- bypass all permission checks in code and don't need explicit rows).

CREATE TABLE IF NOT EXISTS `admin_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_permission` (`admin_id`, `permission_id`),
  KEY `idx_admin_permissions_admin` (`admin_id`),
  KEY `idx_admin_permissions_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `admin_permissions` (`admin_id`, `permission_id`)
SELECT a.id, rp.permission_id
FROM `admins` a
JOIN `role_permissions` rp ON rp.role_id = a.role_id
WHERE a.role_id IS NOT NULL
  AND a.role <> 'super_admin';
