-- Migration 080: every student phone number carries its country code.
--
-- Migration 079 made a phone number something you can log in with, by matching on its last nine
-- digits. That works, but it leaves the stored number ambiguous: 01098476530 is Egyptian only
-- because almost everyone on the platform is, and the moment the platform sells in Saudi Arabia
-- and the Gulf - which migration 077 was built for - a bare national number stops identifying
-- anybody on its own.
--
-- After this, `phone` is stored in international form and `phone_country_code` says which country
-- it belongs to, so a registration form can show the right flag and a support agent can dial it.
--
--     01098476530             ->  +201098476530     phone_country_code = 20
--     +966 50 123 4567        ->  +966501234567     phone_country_code = 966
--
-- =================================================================================================
-- The one assumption, stated out loud
-- =================================================================================================
--
-- A number with no country code has to be given one, and the only honest source is "where are
-- these students". 64 of the 65 stored numbers are eleven digits starting 01, which is an Egyptian
-- mobile and nothing else; the 65th already carries +20. So bare numbers are treated as Egyptian.
--
-- If that is wrong for some future import, `site_settings`.`default_phone_dial_code` is what this
-- reads, and re-running the file will not touch a number that already has a country code.
--
-- =================================================================================================
-- What this does NOT change
-- =================================================================================================
--
-- `phone_key` from migration 079 is the last nine digits and is UNAFFECTED by this rewrite, which
-- is the whole reason the number nine was chosen:
--
--     01098476530     -> last 9 = 098476530
--     +201098476530   -> last 9 = 098476530     the same student, before and after
--
-- So nobody who could log in yesterday stops being able to today, and the two known duplicate
-- pairs stay exactly as duplicated as they were. The column is generated, so it recomputes itself
-- as the numbers below are rewritten - nothing here has to remember to update it.
--
-- Nothing is destructive: one nullable column is added and `phone` is rewritten only where it does
-- not already start with '+'. Re-running changes nothing the second time.

-- =================================================================================================
-- 1. The default, so it is a setting rather than a number buried in an UPDATE
-- =================================================================================================

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
SELECT 'default_phone_dial_code', '20'
WHERE NOT EXISTS (SELECT 1 FROM `site_settings` WHERE `setting_key` = 'default_phone_dial_code');

-- =================================================================================================
-- 2. The column
-- =================================================================================================

SET @has_pcc := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'phone_country_code');
SET @sql := IF(@has_pcc = 0,
    'ALTER TABLE `students` ADD COLUMN `phone_country_code` VARCHAR(8) NULL COMMENT ''Dial code of the phone number, without the +'' AFTER `phone`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 3. Numbers written with 00 or + already carry their country code
-- =================================================================================================
--
-- Reduced to a single canonical spelling first, so that step 4 does not have to distinguish
-- '+20 109 847 6530' from '00201098476530'.

UPDATE `students`
SET `phone` = CONCAT('+', SUBSTRING(REGEXP_REPLACE(`phone`, '[^0-9]', ''), 3))
WHERE `phone` IS NOT NULL
  AND `phone` NOT LIKE '+%'
  AND REGEXP_REPLACE(`phone`, '[^0-9]', '') LIKE '00%';

UPDATE `students`
SET `phone` = CONCAT('+', REGEXP_REPLACE(`phone`, '[^0-9]', ''))
WHERE `phone` IS NOT NULL
  AND `phone` LIKE '+%';

-- =================================================================================================
-- 4. Bare national numbers get the default country code
-- =================================================================================================
--
-- The leading zero is a trunk prefix used only when dialling inside the country. It is dropped,
-- never kept alongside the country code - +20 01098476530 is not a number anyone can call.

SET @default_dial := (SELECT COALESCE(
    (SELECT `setting_value` FROM `site_settings` WHERE `setting_key` = 'default_phone_dial_code' LIMIT 1),
    '20'));

UPDATE `students`
SET `phone` = CONCAT('+', @default_dial,
        TRIM(LEADING '0' FROM REGEXP_REPLACE(`phone`, '[^0-9]', '')))
WHERE `phone` IS NOT NULL
  AND `phone` <> ''
  AND `phone` NOT LIKE '+%';

-- =================================================================================================
-- 5. Which country each number belongs to
-- =================================================================================================
--
-- Longest dial code wins: 1 (North America) and 1876 (Jamaica) both prefix a Jamaican number, and
-- only the longer one is right. Guarded on `countries` existing so this file does not fail on a
-- database where migration 076 has not been applied.

SET @has_countries := (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'countries');
SET @sql := IF(@has_countries > 0,
    'UPDATE `students` s
     SET s.`phone_country_code` = (
         SELECT c.`dial_code` FROM `countries` c
         WHERE c.`dial_code` <> ''''
           AND SUBSTRING(s.`phone`, 2) LIKE CONCAT(c.`dial_code`, ''%'')
         ORDER BY LENGTH(c.`dial_code`) DESC
         LIMIT 1
     )
     WHERE s.`phone` LIKE ''+%'' AND s.`phone_country_code` IS NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
