-- Migration 074: make the platform's operational settings real, and editable.
--
-- `site_settings` already held keys like `maintenance_mode`, `registration_enabled` and
-- `max_login_attempts`. Not one of them was read by a single line of PHP, and nothing in any
-- dashboard could edit them - they were rows that looked like controls and did nothing. An admin
-- who found them and switched maintenance on would have been told it worked and seen no change.
--
-- This migration makes them mean something: it seeds the keys that were missing, adds the table
-- the login limit needs to count against, and creates the permission that guards the new screen.
--
-- Nothing here turns anything ON. Every switch this adds is seeded to its inert value, for the
-- same reason the device limit was: a migration that changes how the platform behaves the moment
-- it runs gives nobody a chance to look at it first.

-- ---------------------------------------------------------------------------------------------
-- 1. The permission for the new Application Settings screen.
-- ---------------------------------------------------------------------------------------------
--
-- The site-settings endpoints had NO permission check at all - any admin account, however
-- narrowly scoped, could rewrite every key in the table including maintenance mode. Now they are
-- gated, and this is what opens them.
--
-- As in migration 073, it is granted to nobody: a permission that has to be handed over is a
-- decision someone made, and `manage_settings` is the one permission where that matters most.
INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
VALUES (
    'manage_settings',
    'Manage Application Settings',
    'app_settings',
    'Edit contact details, social links, maintenance mode, registration and login protection'
)
ON DUPLICATE KEY UPDATE `code` = `code`;

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'إدارة إعدادات التطبيق', 'تعديل بيانات التواصل وروابط التواصل الاجتماعي ووضع الصيانة والتسجيل وحماية تسجيل الدخول'
FROM `permissions` p
JOIN `languages` l ON l.code = 'ar'
WHERE p.code = 'manage_settings'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'Manage Application Settings', 'Edit contact details, social links, maintenance mode, registration and login protection'
FROM `permissions` p
JOIN `languages` l ON l.code = 'en'
WHERE p.code = 'manage_settings'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ---------------------------------------------------------------------------------------------
-- 2. The keys the new screen edits.
-- ---------------------------------------------------------------------------------------------
--
-- `ON DUPLICATE KEY UPDATE setting_key = setting_key` is a deliberate no-op: whatever is already
-- in the table wins. This migration must never overwrite a real support number with a placeholder.
--
-- `login_lockout_enabled` is new and seeded OFF even though `max_login_attempts` has held the
-- value 5 for months. That number was never enforced, so switching enforcement on together with
-- this migration would start locking real people out of their accounts the moment it ran, on the
-- strength of a number nobody chose deliberately.
INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES
    -- Contact and support. These are the ones the student app actually reads.
    ('whatsapp_number', ''),
    ('support_email', ''),
    ('support_phone', ''),
    ('facebook_link', ''),
    ('instagram_link', ''),
    ('youtube_link', ''),
    ('tiktok_link', ''),
    ('telegram_link', ''),

    -- Platform controls.
    ('maintenance_mode', '0'),
    ('maintenance_message_ar', 'المنصة تحت الصيانة حاليًا، برجاء المحاولة بعد قليل.'),
    ('maintenance_message_en', 'The platform is under maintenance. Please try again shortly.'),
    ('registration_enabled', '1'),
    ('registration_disabled_message_ar', 'تسجيل الحسابات الجديدة متوقف حاليًا. برجاء التواصل مع الدعم الفني.'),
    ('registration_disabled_message_en', 'New account registration is currently closed. Please contact support.'),

    -- Login protection. Off until someone turns it on.
    ('login_lockout_enabled', '0'),
    ('max_login_attempts', '5'),
    ('login_lockout_minutes', '15')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- ---------------------------------------------------------------------------------------------
-- 3. What the login limit counts against.
-- ---------------------------------------------------------------------------------------------
--
-- Counted per (email, IP, role), not per email alone. Per-email counting turns the feature into a
-- weapon: anyone who knows the admin's address could lock the admin out of their own platform by
-- failing to log in five times. Tying the count to the IP it came from means an attacker can only
-- ever lock out themselves.
--
-- `locked_until` is the whole mechanism - the lock expires by itself. There is no state that an
-- admin has to remember to clear, and no way for a misconfiguration to lock someone out forever.
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(191) NOT NULL COMMENT 'The email that was tried, lowercased',
    `ip_address` VARCHAR(45) NOT NULL,
    `role` VARCHAR(20) NOT NULL COMMENT 'student / teacher / parent / admin',
    `failures` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL COMMENT 'UTC. Null = not locked. Expires on its own.',
    `last_failure_at` DATETIME NULL COMMENT 'UTC',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_login_attempt` (`identifier`, `ip_address`, `role`),
    KEY `idx_la_locked_until` (`locked_until`),
    KEY `idx_la_last_failure` (`last_failure_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
