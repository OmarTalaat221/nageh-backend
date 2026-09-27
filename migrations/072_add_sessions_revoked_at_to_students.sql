-- Migration 072: let an admin sign a student out of every session at once.
--
-- Revoking devices (migration 071) already ends the sessions opened on them, but only those: a
-- token issued before the device feature existed, or while the limit was switched off, carries no
-- device id and slips through. Losing a phone is exactly the moment that gap matters.
--
-- `sessions_revoked_at` is a cutoff, not a list. Every JWT already carries `iat`, so one timestamp
-- invalidates every token minted before it - with a device id or without one, access or refresh,
-- on any client, without having to track individual tokens anywhere.
--
-- It is deliberately separate from device revocation. Signing someone out is not the same as
-- taking their device slot away: "log my son out, he left his tablet at school" should not also
-- cost him the slot he will want back in an hour.

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'students'
      AND COLUMN_NAME = 'sessions_revoked_at'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `students` ADD COLUMN `sessions_revoked_at` DATETIME NULL COMMENT ''Tokens issued before this moment are rejected''',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
