-- Migration 007: Add Permissions Translations Table

CREATE TABLE IF NOT EXISTS `permissions_translations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `language_id` SMALLINT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_permissions_translations` (`permission_id`, `language_id`),
    KEY `idx_permissions_translations_language` (`language_id`),
    CONSTRAINT `fk_permissions_translations_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_permissions_translations_language`
        FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default translations for system permissions
INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'عرض لوحة التحكم والإحصائيات', 'الاطلاع على إحصائيات لوحة التحكم والتحليلات الحية'),
(1, 2, 'View Dashboard & Statistics', 'Access dashboard analytics and live statistics'),

(2, 1, 'إدارة المدن', 'إضافة وتعديل وحذف وقراءة المدن'),
(2, 2, 'Manage Cities', 'Full CRUD access for cities'),

(3, 1, 'إدارة المواد الدراسية', 'إضافة وتعديل وحذف وقراءة المواد الدراسية'),
(3, 2, 'Manage Subjects', 'Full CRUD access for subjects'),

(4, 1, 'إدارة أنواع المدارس', 'إضافة وتعديل وحذف وقراءة أنواع المدارس'),
(4, 2, 'Manage School Types', 'Full CRUD access for school types'),

(5, 1, 'إدارة المدارس', 'إضافة وتعديل وحذف وقراءة المدارس'),
(5, 2, 'Manage Schools', 'Full CRUD access for schools'),

(6, 1, 'إدارة السنوات الدراسية', 'إضافة وتعديل وحذف وقراءة السنوات الدراسية'),
(6, 2, 'Manage Academic Years', 'Full CRUD access for academic years'),

(7, 1, 'إدارة أقسام الكورسات', 'إضافة وتعديل وحذف وقراءة أقسام الكورسات'),
(7, 2, 'Manage Course Categories', 'Full CRUD access for course categories'),

(8, 1, 'إدارة اللغات', 'إضافة وتعديل وحذف وقراءة لغات النظام'),
(8, 2, 'Manage Languages', 'Full CRUD access for languages'),

(9, 1, 'إدارة المعلمين وتفعيلهم', 'إضافة وتعديل وتفعيل وحذف المعلمين'),
(9, 2, 'Manage Teachers', 'Add, edit, approve, and delete teachers'),

(10, 1, 'إدارة الطلاب', 'إضافة وتعديل وحذف الطلاب'),
(10, 2, 'Manage Students', 'Add, edit, and delete students'),

(11, 1, 'إدارة أولياء الأمور والربط', 'إضافة وتعديل أولياء الأمور وربط الطلاب بهم'),
(11, 2, 'Manage Parents & Student Linkage', 'Add, edit parents and link students'),

(12, 1, 'إدارة الأدوار والصلاحيات', 'إدارة الرولز وتخصيص الصلاحيات في النظام'),
(12, 2, 'Manage Roles & Permissions', 'Full CRUD access for roles and permissions'),

(13, 1, 'إدارة المسؤولين', 'إضافة وتعديل وحذف حسابات مسؤولين النظام'),
(13, 2, 'Manage Admins', 'Add, edit, and delete admin users')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
