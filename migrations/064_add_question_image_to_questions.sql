-- Migration 064: formalize a schema change that was applied directly on production
-- (camp-coding.online) without a migration file, on/around 2026-09.
--
-- `questions.question_image` (TEXT, NULL) already exists live on production - confirmed via
-- `DESCRIBE questions;` on the server. This migration exists only so the tracked schema matches
-- what is actually running, and so a fresh environment (or another developer's local DB) can be
-- brought up to the same state. Guarded with a existence check so it is a no-op if the column
-- is already there (e.g. on production itself).

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'questions'
      AND COLUMN_NAME = 'question_image'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `questions` ADD COLUMN `question_image` TEXT NULL AFTER `score`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
