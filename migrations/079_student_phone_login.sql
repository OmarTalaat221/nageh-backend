-- Migration 079: let a student log in with their phone number, not only their email.
--
-- The problem a phone login has to solve is that the same number gets typed five different ways:
--
--     01098476530          as the student registered it
--     +20 109 847 6530     with a country code and spaces
--     00201098476530       with the international prefix
--
-- All three are one person. Matching on the stored string finds none of them.
--
-- The rule used here is the last nine digits. A national significant number is nine or ten digits
-- almost everywhere, and the country code and trunk zero are exactly the parts that vary:
--
--     01098476530     -> 098476530
--     +20 109 847 6530 -> 098476530      (Egypt, same person)
--     0501234567      -> 501234567
--     +966 50 123 4567 -> 501234567      (Saudi Arabia, same person)
--
-- Nine rather than ten because an Egyptian number written internationally keeps its trunk zero
-- and a Saudi one does not; nine is the longest suffix both forms of both countries agree on.
--
-- =================================================================================================
-- Why the column computes itself
-- =================================================================================================
--
-- `phone_key` is a STORED generated column, not a plain one that PHP fills in. A phone number can
-- change from at least four places - registration, the student's own profile screen, the admin
-- dashboard, and the parent-linking flow - and a plain column would be correct only as long as
-- every one of them remembered to recompute it. One that forgot would leave a student unable to
-- log in with a number the screen says is theirs, and nothing would look wrong anywhere.
--
-- Being STORED, it is indexed, so the lookup is a normal index seek rather than a scan with a
-- function wrapped around every row.
--
-- =================================================================================================
-- Why the index is NOT unique
-- =================================================================================================
--
-- Two numbers in `students` are already on two accounts each:
--
--     01098476530   -> #1065 and #1066
--     01099998888   -> #1001 and #1025
--
-- A UNIQUE index would simply refuse to be created, and the migration would fail halfway on
-- phpMyAdmin. So the index is plain, the existing pairs are left alone, and the ambiguity is
-- handled where it actually matters: the login refuses to guess between two accounts and says to
-- use the email instead. Registration is what stops any NEW duplicate being created.
--
-- To find them at any time:
--
--     SELECT phone_key, COUNT(*) n, GROUP_CONCAT(id) ids, GROUP_CONCAT(email) emails
--     FROM students WHERE phone_key IS NOT NULL
--     GROUP BY phone_key HAVING n > 1;
--
-- Nothing here is destructive. One generated column and one index are added, no existing column
-- is read differently, and re-running the file changes nothing.

-- =================================================================================================
-- 1. The key
-- =================================================================================================
--
-- NULLIF(...,'') so that a student with no phone stores NULL rather than an empty string. An empty
-- string would make every phone-less account match every other one, which is the single worst
-- thing this column could do.

SET @has_phone_key := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'phone_key');
SET @sql := IF(@has_phone_key = 0,
    'ALTER TABLE `students` ADD COLUMN `phone_key` VARCHAR(16) AS (NULLIF(RIGHT(REGEXP_REPLACE(IFNULL(`phone`, ''''), ''[^0-9]'', ''''), 9), '''')) STORED COMMENT ''Last 9 digits of the phone, for logging in. Computed, never written.'' AFTER `phone`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =================================================================================================
-- 2. The index it is looked up by
-- =================================================================================================

SET @has_phone_key_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND INDEX_NAME = 'idx_students_phone_key');
SET @sql := IF(@has_phone_key_idx = 0,
    'ALTER TABLE `students` ADD KEY `idx_students_phone_key` (`phone_key`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
