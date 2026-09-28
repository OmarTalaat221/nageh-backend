-- Migration 077: a price per country, and the plumbing to decide which one a student is shown.
--
-- Migration 076 added the countries and currencies. This is what hangs a price off them.
--
-- The model, in one line: a course keeps its existing `courses.price`, which is now explicitly
-- "the price in the base currency"; on top of that an admin or the owning teacher may enter a
-- price for any number of individual countries, and a student in one of those countries is quoted
-- that price in that country's currency instead.
--
-- There are no exchange rates anywhere in this migration, on purpose. Every price in the new table
-- is a number a human typed for that country. A converted price would be wrong the day after it
-- was calculated, and nobody would know which of the two numbers on the screen was the real one.
-- A country with no entered price falls back to the base price in the base currency - which is
-- exactly what every student sees today, so running this changes nothing until someone enters a
-- price.
--
-- Nothing in here is destructive and nothing is switched on that was off. The one new table starts
-- empty, the new columns are all nullable or defaulted, and the two ALTERs that touch existing
-- columns only widen them.

-- =================================================================================================
-- 1. The prices themselves
-- =================================================================================================
--
-- One row per (item, country). `currency_code` is copied from the country when the price is
-- entered rather than read through a join at display time: if a country ever switches currency,
-- a price entered as 1500 Egyptian pounds must not silently start being read as 1500 of whatever
-- replaced them. The row keeps the currency it was entered in until someone re-enters it.
--
-- DECIMAL(14,3) rather than the (10,2) used for `courses.price`, because a Kuwaiti dinar has three
-- decimal places and a Japanese yen has none. Two places is an Egyptian and American assumption,
-- and this table exists precisely to stop making those.

CREATE TABLE IF NOT EXISTS `item_country_prices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_type` ENUM('course', 'book') NOT NULL DEFAULT 'course',
    `item_id` BIGINT UNSIGNED NOT NULL,
    `country_id` BIGINT UNSIGNED NOT NULL,
    `currency_code` CHAR(3) NOT NULL COMMENT 'Copied from the country when the price was entered',
    `price` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Off means fall back to the base price',
    `created_by_type` ENUM('admin', 'teacher') NULL,
    `created_by_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_item_country` (`item_type`, `item_id`, `country_id`),
    KEY `idx_item` (`item_type`, `item_id`),
    KEY `idx_country` (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================================================
-- 2. Where the student is
-- =================================================================================================
--
-- A student's own country, once it is known. It is the answer that outranks a geo-IP guess: a
-- Saudi student travelling through Cairo is still a Saudi student, and somebody who has told us
-- where they are should not be re-guessed on every request.

SET @has_student_country := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'country_id');
SET @sql := IF(@has_student_country = 0,
    'ALTER TABLE `students` ADD COLUMN `country_id` BIGINT UNSIGNED NULL COMMENT ''Set by the student, or from the first geo-IP lookup'' AFTER `city_id`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_student_country_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND INDEX_NAME = 'idx_students_country');
SET @sql := IF(@has_student_country_idx = 0,
    'ALTER TABLE `students` ADD KEY `idx_students_country` (`country_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The geo-IP answers we have already paid for.
--
-- Without this, every page view by a logged-out visitor is an outbound HTTP request to a third
-- party before a single price can be printed. With it, the second visit from an address is free.
--
-- `iso2` NULL means "we asked and got no answer", which is cached too - deliberately, and for a
-- much shorter time. A provider that is down must not be retried on every request for hours, and
-- an address that genuinely cannot be located must not be asked about forever.
--
-- `looked_up_at` is UTC, written with UTC_TIMESTAMP() and compared against it. Every timestamp on
-- this platform that is compared across requests has to be, because the connection's timezone is
-- set per request from the client.

CREATE TABLE IF NOT EXISTS `ip_country_cache` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `iso2` CHAR(2) NULL COMMENT 'NULL = looked up, no answer',
    `failures` INT NOT NULL DEFAULT 0 COMMENT 'Consecutive failed lookups, for the provider row',
    `looked_up_at` DATETIME NOT NULL COMMENT 'UTC',
    UNIQUE KEY `uniq_ip` (`ip_address`),
    KEY `idx_looked_up` (`looked_up_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================================================
-- 3. The money that already moves
-- =================================================================================================
--
-- An offline payment request records what was quoted. Now that a quote can be in any currency, the
-- request has to record which country it was quoted for, or a reviewer six weeks later cannot tell
-- whether "1500" was pounds or riyals and has no way to find out.
--
-- `currency` is already on this table (migration 075) and keeps its meaning.

SET @has_req_country := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offline_payment_requests' AND COLUMN_NAME = 'country_id');
SET @sql := IF(@has_req_country = 0,
    'ALTER TABLE `offline_payment_requests` ADD COLUMN `country_id` BIGINT UNSIGNED NULL COMMENT ''The country the price was quoted for'' AFTER `currency`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The amounts were DECIMAL(10,2). A three-decimal currency would have been rounded on the way in
-- without a word, so a student quoted 12.345 dinars would have filed a request for 12.35 and had
-- their receipt compared against the wrong number. Widening only - no existing value changes.
SET @amount_scale := (SELECT NUMERIC_SCALE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offline_payment_requests' AND COLUMN_NAME = 'amount');
SET @sql := IF(@amount_scale IS NOT NULL AND @amount_scale < 3,
    'ALTER TABLE `offline_payment_requests`
        MODIFY COLUMN `original_amount` DECIMAL(14,3) NOT NULL DEFAULT 0.000 COMMENT ''The listed price when the request was filed'',
        MODIFY COLUMN `discount_amount` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
        MODIFY COLUMN `amount` DECIMAL(14,3) NOT NULL DEFAULT 0.000 COMMENT ''What the student says they transferred''',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Which currency an account receives.
--
-- A student quoted in Egyptian pounds should be sent to an account that can take Egyptian pounds.
-- Existing rows get the base currency, because that is what they were implicitly receiving.
SET @has_acct_currency := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_accounts' AND COLUMN_NAME = 'currency_code');
SET @sql := IF(@has_acct_currency = 0,
    'ALTER TABLE `payment_accounts` ADD COLUMN `currency_code` CHAR(3) NOT NULL DEFAULT ''USD'' COMMENT ''The currency this account receives'' AFTER `swift_code`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 4. Discount codes have a currency too
-- =================================================================================================
--
-- A percentage code works in any currency - 20% off is 20% off. A fixed-amount code does not:
-- "50 off" is meaningless until you know 50 of what, and subtracting 50 US dollars from a price of
-- 1500 Egyptian pounds would hand out a discount of roughly 2400 pounds.
--
-- NULL means the base currency, which is what every existing fixed code was already denominated
-- in, so no existing code changes meaning. The pricing helper refuses to apply a fixed code to a
-- quote in a different currency and says so, rather than doing the subtraction anyway.

SET @has_code_currency := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'discount_codes' AND COLUMN_NAME = 'currency_code');
SET @sql := IF(@has_code_currency = 0,
    'ALTER TABLE `discount_codes` ADD COLUMN `currency_code` CHAR(3) NULL COMMENT ''Which currency a fixed amount is in. NULL = the base currency. Ignored for percentage codes.'' AFTER `discount_value`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 5. The settings
-- =================================================================================================
--
-- `base_currency` is the fallback the whole feature rests on: what a price with no country entry
-- is quoted in. It is seeded 'USD' because that is what migration 067 already relabelled every
-- transaction to, so seeding anything else would silently re-denominate the platform.
--
-- Unlike the device limit and the login lockout, the two switches here are seeded ON. Those two
-- could shut a real student out the moment they ran, so they had to be opted into. These cannot:
-- country pricing does nothing at all until a human enters a country price, and the geo lookup is
-- a read that falls back to the base currency whenever it fails. Seeding them off would have meant
-- an admin entering prices, seeing no change anywhere, and having no way to know why.

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
SELECT * FROM (SELECT 'base_currency' AS k, 'USD' AS v) AS t
WHERE NOT EXISTS (SELECT 1 FROM `site_settings` WHERE `setting_key` = 'base_currency');

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
SELECT * FROM (SELECT 'country_pricing_enabled' AS k, '1' AS v) AS t
WHERE NOT EXISTS (SELECT 1 FROM `site_settings` WHERE `setting_key` = 'country_pricing_enabled');

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
SELECT * FROM (SELECT 'geoip_lookup_enabled' AS k, '1' AS v) AS t
WHERE NOT EXISTS (SELECT 1 FROM `site_settings` WHERE `setting_key` = 'geoip_lookup_enabled');

-- The provider, as a URL with `{ip}` in it, so a host that blocks this one or a plan that runs out
-- can be swapped without a deploy. The response is scanned for a two-letter country code under any
-- of the usual key names, which is why an arbitrary provider works here at all.
INSERT INTO `site_settings` (`setting_key`, `setting_value`)
SELECT * FROM (SELECT 'geoip_provider_url' AS k, 'https://ipwho.is/{ip}' AS v) AS t
WHERE NOT EXISTS (SELECT 1 FROM `site_settings` WHERE `setting_key` = 'geoip_provider_url');

-- =================================================================================================
-- 6. The permission
-- =================================================================================================
--
-- Granted to nobody, as with `manage_discounts` (073), `manage_settings` and `manage_payments`
-- (074, 075). Prices are the one thing on this platform where a wrong click costs real money, so
-- who holds this is a decision someone makes rather than one a migration makes for them.
--
-- A super admin is unaffected: `hasPermission()` returns true for that role before it reads the
-- table at all.

INSERT INTO `permissions` (`code`, `name`, `module`, `description`)
VALUES (
    'manage_pricing',
    'Manage Pricing and Currencies',
    'pricing',
    'Set per-country prices, choose the base currency, and enable countries and currencies'
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `module` = VALUES(`module`),
    `description` = VALUES(`description`);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'إدارة الأسعار والعملات', 'تحديد أسعار الدول والعملة الأساسية وتفعيل الدول والعملات'
FROM `permissions` p
JOIN `languages` l ON l.code = 'ar'
WHERE p.code = 'manage_pricing'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `permissions_translations` (`permission_id`, `language_id`, `name`, `description`)
SELECT p.id, l.id, 'Manage Pricing and Currencies', 'Set per-country prices, choose the base currency, and enable countries and currencies'
FROM `permissions` p
JOIN `languages` l ON l.code = 'en'
WHERE p.code = 'manage_pricing'
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);
