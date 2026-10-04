-- Student sign-up asks for less and verifies nothing.
--
-- 1) `students.email` becomes nullable. It was NOT NULL with a UNIQUE key, which is why the
--    registration form could not simply stop requiring it - an empty string would have been
--    stored and the second student without an email would have collided with the first. MySQL
--    allows any number of NULLs in a UNIQUE index, so NULL is the right empty here, not ''.
--    A student with no email logs in with their phone number instead.
--
-- 2) The platform sells in Lebanon, so a number typed without a country code is Lebanese. The
--    setting already existed (helpers/phone.php reads `default_phone_dial_code`); it had simply
--    never been given a value, and the hard-coded fallback behind it was Egypt's 20.

ALTER TABLE `students` MODIFY COLUMN `email` VARCHAR(190) NULL;

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES ('default_phone_dial_code', '961')
ON DUPLICATE KEY UPDATE `setting_value` = '961';
