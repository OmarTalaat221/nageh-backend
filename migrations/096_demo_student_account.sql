-- ============================================================
-- Migration: 096_demo_student_account.sql
-- A student account for the mobile developer: it sees and opens the whole
-- catalogue without paying, and no admin-facing student list or count shows it.
--
-- Access itself is granted in code (get_student_accessible_entities checks this
-- flag), NOT by subscription rows - so new content is covered automatically, and
-- the account stays out of every list that is built from subscriptions.
-- ============================================================

ALTER TABLE `students`
  ADD COLUMN IF NOT EXISTS `is_demo_account` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT 'Full catalogue access without paying; hidden from admin student lists';

-- Password: 12345678
INSERT INTO `students`
  (`name`, `email`, `password`, `study_language`, `is_email_verified`, `is_active`, `is_demo_account`, `created_at`, `updated_at`)
SELECT
  'Super Student (Demo)',
  'superstudent@example.com',
  '$2y$10$x4RrI2i7Q1r6qDIYz/4h.eNpqPqidqyFRBsRRt3jVQdRMMhhKA0ne',
  'arabic',
  1,
  1,
  1,
  NOW(),
  NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM (SELECT `id` FROM `students` WHERE `email` = 'superstudent@example.com' LIMIT 1) AS existing
);
