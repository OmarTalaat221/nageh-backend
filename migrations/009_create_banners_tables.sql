-- 1. Create banners table
CREATE TABLE IF NOT EXISTS `banners` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title_ar` VARCHAR(255) NULL,
    `title_en` VARCHAR(255) NULL,
    `image_ar` VARCHAR(500) NOT NULL,
    `image_en` VARCHAR(500) NULL,
    `link` VARCHAR(500) NULL,
    `type` ENUM('image', 'video') NOT NULL DEFAULT 'image',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_banners_is_active` (`is_active`),
    KEY `idx_banners_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create banner_views table
CREATE TABLE IF NOT EXISTS `banner_views` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `banner_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `user_type` VARCHAR(50) NULL DEFAULT 'guest',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_banner_views_banner_id` (`banner_id`),
    KEY `idx_banner_views_user_id` (`user_id`),
    KEY `idx_banner_views_user_type` (`user_type`),
    CONSTRAINT `fk_banner_views_banner`
        FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add permission for banners management
INSERT INTO `permissions` (`code`, `name`, `module`, `description`) VALUES
('manage_banners', 'Manage Banners & Analytics', 'banners', 'Manage system banners and view analytics')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `module` = VALUES(`module`);

-- Add permission translations
INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, 1, 'إدارة البانرات والإحصائيات', 'إدارة البانرات الإعلانية ومتابعة المشاهدات'
FROM `permissions` p WHERE p.code = 'manage_banners'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, 2, 'Manage Banners & Analytics', 'Manage system banners and track views'
FROM `permissions` p WHERE p.code = 'manage_banners'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Grant permission to Super Admin role (role_id = 1)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, p.id FROM `permissions` p WHERE p.code = 'manage_banners'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);
