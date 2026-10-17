-- ============================================================
-- Migration: 099_platform_owner_slots.sql
-- The platform's owner gives consultations himself, and he is not a teacher:
-- he has no row in `teachers`, does not sign in as one, and should not appear
-- in any teacher listing.
--
-- So a slot may now belong to nobody in `teachers`: teacher_id NULL means "the
-- platform owner". The name students see for those slots is a setting rather
-- than a constant, so it can be changed without touching code.
--
-- Nothing points at these tables with a foreign key, and NULL is simply absent
-- from an index, so existing rows and queries are unaffected.
-- ============================================================

ALTER TABLE `consultation_slots`
  MODIFY `teacher_id` BIGINT(20) UNSIGNED NULL
  COMMENT 'NULL = the platform owner, who is not a teacher';

ALTER TABLE `teacher_availability_slots`
  MODIFY `teacher_id` BIGINT(20) UNSIGNED NULL
  COMMENT 'NULL = the platform owner, who is not a teacher';

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
SELECT 'platform_owner_name', 'الأستاذ إبراهيم العبيد'
WHERE NOT EXISTS (
  SELECT 1 FROM (SELECT `setting_key` FROM `site_settings` WHERE `setting_key` = 'platform_owner_name' LIMIT 1) AS existing
);
