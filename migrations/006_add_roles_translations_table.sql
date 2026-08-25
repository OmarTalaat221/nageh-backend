-- Migration 006: Add Roles Translations Table

CREATE TABLE IF NOT EXISTS `roles_translations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `language_id` SMALLINT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_roles_translations` (`role_id`, `language_id`),
    KEY `idx_roles_translations_language` (`language_id`),
    CONSTRAINT `fk_roles_translations_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_roles_translations_language`
        FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default translations for system roles
INSERT INTO `roles_translations` (`role_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'مدير النظام الفائق', 'صلاحية كاملة لمشرف النظام الأعلى بدون قيود'),
(1, 2, 'Super Administrator', 'Full system access for super administrator without restrictions'),
(2, 1, 'مدير عام', 'صلاحيات الإدارة العامة للنظام'),
(2, 2, 'General Administrator', 'General administration access'),
(3, 1, 'مشرف محتوى', 'صلاحيات الإشراف ومراجعة المحتوى والمستخدمين'),
(3, 2, 'Content Moderator', 'Content moderation and user review access')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
