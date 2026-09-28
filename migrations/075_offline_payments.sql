-- Migration 075: offline payments - the student transfers the money, an admin confirms it.
--
-- There is no payment gateway on this platform and none is planned for now. What actually happens
-- is that a student sends the money by InstaPay, a wallet or a bank transfer, and then has to
-- reach someone on WhatsApp with a screenshot and hope it gets matched to their account by hand.
-- Nothing about that exists in the system: no record of who paid, no receipt, no queue, and no
-- way to tell a student what is happening with their money.
--
-- This gives that flow a place to live. The student picks what they are buying, sees where to
-- send the money, sends it, and files the receipt. It lands in a queue with the amount and the
-- reference. Approving it grants the access and records who approved it and when.
--
-- Nothing here changes any existing behaviour. The tables start empty, and a platform with no
-- payment accounts configured simply shows the student nothing new.

-- ---------------------------------------------------------------------------------------------
-- 1. Where the money is sent.
-- ---------------------------------------------------------------------------------------------
--
-- A row per account the platform can receive on. Kept as data rather than hardcoded because these
-- change - a wallet number is replaced, a bank account is closed - and each change would otherwise
-- be a deploy.
--
-- Deactivating beats deleting: a request already filed points at the account it was paid into, and
-- that trail is the whole point when someone asks three months later where their money went.
CREATE TABLE IF NOT EXISTS `payment_accounts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `method` VARCHAR(40) NOT NULL DEFAULT 'other' COMMENT 'instapay / vodafone_cash / etihad_cash / orange_cash / we_pay / bank / other',
    `label_ar` VARCHAR(150) NOT NULL,
    `label_en` VARCHAR(150) NOT NULL,
    `account_name` VARCHAR(191) NULL COMMENT 'The name the transfer should be addressed to',
    `account_number` VARCHAR(191) NOT NULL COMMENT 'Wallet number, IBAN, InstaPay address',
    `instructions_ar` TEXT NULL,
    `instructions_en` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_pa_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------------------------
-- 2. The requests themselves.
-- ---------------------------------------------------------------------------------------------
--
-- `amount` is what the student says they sent, and it is stored next to `original_amount` and the
-- discount rather than instead of them. A reviewer needs to see all three at once: the price, what
-- was taken off it, and what actually arrived. Recomputing the expected figure at review time
-- would silently follow a later price change and make an old receipt look wrong.
--
-- The item columns mirror `payment_transactions` (course or book) so the same two things can be
-- bought whichever way the money arrives.
--
-- A rejected request keeps its row and its reason. "This is the third receipt this month that did
-- not match" is exactly what someone needs to know, and deleting the evidence destroys it.
CREATE TABLE IF NOT EXISTS `offline_payment_requests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `item_type` ENUM('course', 'book') NOT NULL DEFAULT 'course',
    `item_id` BIGINT UNSIGNED NOT NULL,

    `payment_account_id` BIGINT UNSIGNED NULL COMMENT 'Which of our accounts they say they paid into',
    `original_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'The listed price when the request was filed',
    `discount_code_id` BIGINT UNSIGNED NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'What the student says they transferred',
    `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',

    `sender_name` VARCHAR(191) NULL COMMENT 'The name on the sending account, when it is not the student',
    `sender_reference` VARCHAR(191) NULL COMMENT 'Transaction / reference number from the transfer',
    `transferred_at` DATETIME NULL COMMENT 'When the student says they sent it',
    `receipt_image` VARCHAR(500) NULL,
    `student_note` TEXT NULL,

    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `reviewed_by_type` ENUM('admin', 'teacher') NULL,
    `reviewed_by_id` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `review_note` TEXT NULL COMMENT 'Why it was rejected, or anything the reviewer wants on the record',
    `granted_ref_id` BIGINT UNSIGNED NULL COMMENT 'The subscription row this created, once approved',

    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY `idx_opr_student` (`student_id`, `status`),
    KEY `idx_opr_status` (`status`, `created_at`),
    KEY `idx_opr_item` (`item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------------------------
-- 3. Who may approve money.
-- ---------------------------------------------------------------------------------------------
--
-- Its own permission, granted to nobody, for the same reason as migrations 073 and 074: approving
-- a payment hands out paid access, and that is not something an account should inherit because it
-- happened to be given an unrelated module.
INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
VALUES (
    'manage_payments',
    'Manage Offline Payments',
    'payments',
    'Review offline payment requests and manage the accounts students transfer to'
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `module` = VALUES(`module`),
    `description` = VALUES(`description`);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'إدارة المدفوعات اليدوية', 'مراجعة طلبات الدفع اليدوي وإدارة الحسابات اللي الطلبة بيحوّلوا عليها'
FROM `permissions` p
JOIN `languages` l ON l.code = 'ar'
WHERE p.code = 'manage_payments'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'Manage Offline Payments', 'Review offline payment requests and manage the accounts students transfer to'
FROM `permissions` p
JOIN `languages` l ON l.code = 'en'
WHERE p.code = 'manage_payments'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);
