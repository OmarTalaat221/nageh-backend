-- Migration 083: a certificate template per type that belongs to no course.
--
-- Awarding a certificate to a student meant picking a template, and every template on the platform
-- was built around a course. So the only way to tell a student "you did well" was to attach that
-- praise to a course they happened to be enrolled in, and a student with no course could be given
-- nothing at all.
--
-- The four types the platform actually awards - تميز, إجتياز, تفوق, عامة - now each have a general
-- template with no course, no subject and no academic year. Granting by type resolves to the row
-- seeded here.
--
-- The backend creates any of these on first use if it is missing (certificate_general_template_id
-- in helpers/certificate_types.php), so the feature works before this file is applied. Applying it
-- means the rows exist with these exact codes and names rather than being created ad hoc.
--
-- Nothing is renamed or deleted. The existing course certificates keep their rows, their types and
-- everyone already holding them; the labels the admin screen shows for the four types changed in
-- the same commit, but the stored codes did not, so no existing row means anything different than
-- it did before.

INSERT INTO `certificates`
    (`academic_year_id`, `subject_id`, `course_id`, `code`, `score`, `certificate_type`, `is_active`, `created_by_type`, `created_by_id`)
SELECT * FROM (
    SELECT NULL AS academic_year_id, NULL AS subject_id, NULL AS course_id,
           'CERT-GEN-EXCELLENCE' AS code, 0.00 AS score, 'excellence' AS certificate_type,
           1 AS is_active, 'admin' AS created_by_type, 1 AS created_by_id UNION ALL
    SELECT NULL, NULL, NULL, 'CERT-GEN-PASS',        0.00, 'course_completion', 1, 'admin', 1 UNION ALL
    SELECT NULL, NULL, NULL, 'CERT-GEN-DISTINCTION', 0.00, 'achievement',       1, 'admin', 1 UNION ALL
    SELECT NULL, NULL, NULL, 'CERT-GEN-GENERAL',     0.00, 'general',           1, 'admin', 1
) AS incoming
WHERE NOT EXISTS (
    SELECT 1 FROM `certificates` c WHERE c.code = incoming.code
);

INSERT INTO `certificates_translations` (`certificate_id`, `language_id`, `name`, `description`)
SELECT c.id, 1, t.name_ar, t.desc_ar
FROM `certificates` c
JOIN (
    SELECT 'CERT-GEN-EXCELLENCE' AS code, 'شهادة تميز'  AS name_ar, 'تُمنح للطالب المتميز'          AS desc_ar UNION ALL
    SELECT 'CERT-GEN-PASS',              'شهادة إجتياز', 'تُمنح للطالب الذي اجتاز بنجاح' UNION ALL
    SELECT 'CERT-GEN-DISTINCTION',       'شهادة تفوق',   'تُمنح للطالب المتفوق' UNION ALL
    SELECT 'CERT-GEN-GENERAL',           'شهادة عامة',   'شهادة تقدير عامة من منصة ناجح'
) t ON t.code = c.code
WHERE NOT EXISTS (
    SELECT 1 FROM `certificates_translations` x WHERE x.certificate_id = c.id AND x.language_id = 1
);

INSERT INTO `certificates_translations` (`certificate_id`, `language_id`, `name`, `description`)
SELECT c.id, 2, t.name_en, t.desc_en
FROM `certificates` c
JOIN (
    SELECT 'CERT-GEN-EXCELLENCE' AS code, 'Excellence Certificate'  AS name_en, 'Awarded to an outstanding student' AS desc_en UNION ALL
    SELECT 'CERT-GEN-PASS',              'Pass Certificate',        'Awarded to a student who passed' UNION ALL
    SELECT 'CERT-GEN-DISTINCTION',       'Distinction Certificate', 'Awarded to a student with distinction' UNION ALL
    SELECT 'CERT-GEN-GENERAL',           'General Certificate',     'A general certificate of appreciation from Najeh'
) t ON t.code = c.code
WHERE NOT EXISTS (
    SELECT 1 FROM `certificates_translations` x WHERE x.certificate_id = c.id AND x.language_id = 2
);
