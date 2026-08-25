-- Migration 026: Add columns expected by the certificates read code
-- (grade_obtained, pdf_url) that were missing from migration 016, and seed
-- sample certificates data for testing.

ALTER TABLE `student_certificates` ADD COLUMN IF NOT EXISTS `grade_obtained` DECIMAL(8,2) NULL AFTER `score_achieved`;
ALTER TABLE `student_certificates` ADD COLUMN IF NOT EXISTS `pdf_url` VARCHAR(500) NULL AFTER `certificate_code`;

INSERT IGNORE INTO `certificates` (`id`, `academic_year_id`, `subject_id`, `course_id`, `code`, `score`, `certificate_type`, `image`, `is_active`, `created_by_type`, `created_by_id`) VALUES
(1, 1, 1, 1, 'CERT-CHEM-001', 90.00, 'course_completion', 'uploads/certificates/chem-completion.png', 1, 'admin', 1),
(2, 1, 4, 4, 'CERT-MATH-001', 85.00, 'course_completion', 'uploads/certificates/math-completion.png', 1, 'admin', 1),
(3, 1, 6, 6, 'CERT-PHYS-001', 95.00, 'excellence', 'uploads/certificates/phys-excellence.png', 1, 'admin', 1),
(4, NULL, NULL, NULL, 'CERT-GEN-2026', 0.00, 'general', 'uploads/certificates/general.png', 1, 'admin', 1);

INSERT IGNORE INTO `certificates_translations` (`certificate_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'شهادة إتمام كورس الكيمياء العضوية', 'تُمنح هذه الشهادة للطلاب الذين أكملوا كورس الكيمياء العضوية بنجاح'),
(1, 2, 'Organic Chemistry Course Completion Certificate', 'Awarded to students who successfully completed the Organic Chemistry course'),
(2, 1, 'شهادة إتمام كورس الرياضيات', 'تُمنح هذه الشهادة للطلاب الذين أكملوا كورس الرياضيات بنجاح'),
(2, 2, 'Math Course Completion Certificate', 'Awarded to students who successfully completed the Math course'),
(3, 1, 'شهادة التميز في الفيزياء', 'تُمنح هذه الشهادة للطلاب المتميزين في كورس الفيزياء'),
(3, 2, 'Physics Excellence Certificate', 'Awarded to top-performing students in the Physics course'),
(4, 1, 'شهادة تقدير عامة', 'شهادة تقدير عامة لمنصة ناجح'),
(4, 2, 'General Appreciation Certificate', 'General appreciation certificate from Najeh platform');

INSERT IGNORE INTO `certificate_teachers` (`certificate_id`, `teacher_id`, `is_active`) VALUES
(1, 4, 1),
(2, 3, 1),
(3, 2, 1);

INSERT IGNORE INTO `student_certificates` (`certificate_id`, `student_id`, `course_id`, `exam_id`, `certificate_code`, `pdf_url`, `score_achieved`, `grade_obtained`, `issue_date`) VALUES
(1, 1, 1, NULL, 'SC-2026-0001', 'uploads/certificates/issued/sc-2026-0001.pdf', 92.50, 92.50, '2026-01-15'),
(1, 2, 1, NULL, 'SC-2026-0002', 'uploads/certificates/issued/sc-2026-0002.pdf', 88.00, 88.00, '2026-01-15'),
(2, 3, 4, NULL, 'SC-2026-0003', 'uploads/certificates/issued/sc-2026-0003.pdf', 85.00, 85.00, '2026-02-01'),
(3, 1, 6, NULL, 'SC-2026-0004', 'uploads/certificates/issued/sc-2026-0004.pdf', 97.00, 97.00, '2026-03-10');
