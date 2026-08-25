-- Migration 005: Add Profile Images and Roles & Permissions System (RBAC)

ALTER TABLE `students` ADD COLUMN `image` VARCHAR(500) NULL AFTER `password`;
ALTER TABLE `parents` ADD COLUMN `image` VARCHAR(500) NULL AFTER `password`;
ALTER TABLE `admins` ADD COLUMN `image` VARCHAR(500) NULL AFTER `password`;

-- Roles Table
CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions Table
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_permissions_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions Pivot Table
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_role_permissions` (`role_id`, `permission_id`),
    CONSTRAINT `fk_role_permissions_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add role_id to admins table
ALTER TABLE `admins` ADD COLUMN `role_id` BIGINT UNSIGNED NULL AFTER `role`;
ALTER TABLE `admins` ADD CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- Seed Default Roles
INSERT INTO `roles` (`code`, `name`, `description`, `is_system`) VALUES
('super_admin', 'Super Administrator', 'Full system access with no permission restrictions', 1),
('admin', 'General Administrator', 'Standard administrative access', 1),
('moderator', 'Content Moderator', 'Moderation and user management access', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Seed System Permissions
INSERT INTO `permissions` (`code`, `name`, `module`, `description`) VALUES
('view_dashboard', 'View Dashboard & Statistics', 'dashboard', 'Access dashboard and statistics'),
('manage_cities', 'Manage Cities', 'cities', 'Full CRUD access for cities'),
('manage_subjects', 'Manage Subjects', 'subjects', 'Full CRUD access for subjects'),
('manage_school_types', 'Manage School Types', 'school_types', 'Full CRUD access for school types'),
('manage_schools', 'Manage Schools', 'schools', 'Full CRUD access for schools'),
('manage_academic_years', 'Manage Academic Years', 'academic_years', 'Full CRUD access for academic years'),
('manage_course_categories', 'Manage Course Categories', 'course_categories', 'Full CRUD access for course categories'),
('manage_languages', 'Manage Languages', 'languages', 'Full CRUD access for languages'),
('manage_teachers', 'Manage Teachers', 'teachers', 'Add, edit, approve, and delete teachers'),
('manage_students', 'Manage Students', 'students', 'Add, edit, and delete students'),
('manage_parents', 'Manage Parents & Student Linkage', 'parents', 'Add, edit parents and link students'),
('manage_roles', 'Manage Roles & Permissions', 'roles', 'Full CRUD access for roles and permissions'),
('manage_admins', 'Manage Admins', 'admins', 'Add, edit, and delete admin users')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
