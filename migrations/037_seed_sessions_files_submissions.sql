SET FOREIGN_KEY_CHECKS = 0;

INSERT IGNORE INTO `class_sessions`
    (`id`, `teacher_id`, `subject_id`, `course_id`, `code`, `day_of_week`, `start_time`, `end_time`, `room`, `is_online`, `meeting_link`, `grade`, `is_active`)
VALUES
    (1,  1, 1, 1,  'SESS-CHEM-001',  'Sunday',    '09:00:00', '10:30:00', 'قاعة 101',  0, NULL,                                    'الثانوية العامة', 1),
    (2,  1, 1, 1,  'SESS-CHEM-002',  'Tuesday',   '09:00:00', '10:30:00', 'قاعة 101',  0, NULL,                                    'الثانوية العامة', 1),
    (3,  1, 1, 1,  'SESS-CHEM-003',  'Thursday',  '09:00:00', '10:30:00', 'قاعة 101',  0, NULL,                                    'الثانوية العامة', 1),
    (4,  2, 2, 2,  'SESS-PHYS-001',  'Sunday',    '11:00:00', '12:30:00', 'قاعة 202',  0, NULL,                                    'الثانوية العامة', 1),
    (5,  2, 2, 2,  'SESS-PHYS-002',  'Wednesday', '11:00:00', '12:30:00', 'قاعة 202',  0, NULL,                                    'الثانوية العامة', 1),
    (6,  3, 3, 3,  'SESS-MATH-001',  'Monday',    '10:00:00', '11:30:00', NULL,         1, 'https://meet.google.com/abc-defg-hij', 'الثانوية العامة', 1),
    (7,  3, 3, 3,  'SESS-MATH-002',  'Thursday',  '10:00:00', '11:30:00', NULL,         1, 'https://meet.google.com/abc-defg-hij', 'الثانوية العامة', 1),
    (8,  4, 4, 4,  'SESS-BIO-001',   'Tuesday',   '13:00:00', '14:30:00', 'معمل الأحياء', 0, NULL,                                 'الثانوية العامة', 1),
    (9,  5, 5, 5,  'SESS-ARAB-001',  'Monday',    '08:00:00', '09:30:00', 'قاعة 305',  0, NULL,                                    'الثانوية العامة', 1),
    (10, 6, 6, 6,  'SESS-ENG-001',   'Wednesday', '14:00:00', '15:30:00', NULL,         1, 'https://zoom.us/j/123456789',          'الثانوية العامة', 1),
    (11, 1, 1, NULL, 'SESS-CHEM-FREE', 'Saturday',  '16:00:00', '17:00:00', 'قاعة 101',  0, NULL,                                  'الثانوية العامة', 1),
    (12, 2, 2, NULL, 'SESS-PHYS-FREE', 'Friday',    '16:00:00', '17:00:00', NULL,         1, 'https://meet.google.com/xyz-abcd-efg', 'الثانوية العامة', 1)
ON DUPLICATE KEY UPDATE `code` = VALUES(`code`);

INSERT IGNORE INTO `session_files`
    (`id`, `session_id`, `uploaded_by_type`, `uploaded_by_id`, `file_type`, `title`, `description`, `file_path`, `original_name`, `is_active`)
VALUES
    (1,  1, 'teacher', 1, 'explanation', 'ملف شرح - الكيمياء العضوية الجزء الأول',    'ملف PDF يحتوي على شرح مفصل للمركبات العضوية',      'uploads/session-files/chem_explanation_01.pdf', 'chem_explanation_01.pdf', 1),
    (2,  1, 'teacher', 1, 'homework',    'واجب الكيمياء - الأسبوع الأول',             'حل التمارين من 1 إلى 15 من الكتاب المدرسي',        'uploads/session-files/chem_homework_01.pdf',    'chem_homework_01.pdf',    1),
    (3,  2, 'teacher', 1, 'explanation', 'ملف شرح - التفاعلات الكيميائية',           'ملخص شامل لأنواع التفاعلات الكيميائية',             'uploads/session-files/chem_reactions.pdf',      'chem_reactions.pdf',      1),
    (4,  2, 'teacher', 1, 'exam',        'امتحان تجريبي - الكيمياء العضوية',          'امتحان شامل على الوحدة الأولى بالكامل',            'uploads/session-files/chem_exam_01.pdf',        'chem_exam_01.pdf',        1),
    (5,  3, 'teacher', 1, 'task',        'مهمة بحثية - المركبات الكربونية',           'اكتب ملخصاً عن استخدامات المركبات الكربونية في الحياة اليومية', 'uploads/session-files/chem_task_01.pdf', 'chem_task_01.pdf', 1),
    (6,  4, 'teacher', 2, 'explanation', 'ملف شرح - قوانين نيوتن',                   'شرح مفصل لقوانين الحركة الثلاثة',                  'uploads/session-files/phys_newton.pdf',         'phys_newton.pdf',         1),
    (7,  4, 'teacher', 2, 'homework',    'واجب الفيزياء - الأسبوع الأول',            'حل مسائل الحركة المستقيمة المنتظمة',               'uploads/session-files/phys_homework_01.pdf',    'phys_homework_01.pdf',    1),
    (8,  5, 'teacher', 2, 'exam',        'اختبار قصير - قوانين نيوتن',               'اختبار 10 أسئلة على قوانين الحركة',               'uploads/session-files/phys_quiz_01.pdf',        'phys_quiz_01.pdf',        1),
    (9,  6, 'teacher', 3, 'explanation', 'ملف شرح - التفاضل والتكامل',              'مقدمة في التفاضل مع أمثلة محلولة',                'uploads/session-files/math_calculus.pdf',       'math_calculus.pdf',       1),
    (10, 6, 'teacher', 3, 'homework',    'واجب الرياضيات - الأسبوع الأول',           'احسب المشتقات للدوال التالية',                     'uploads/session-files/math_homework_01.pdf',    'math_homework_01.pdf',    1),
    (11, 7, 'admin',   1, 'task',        'مهمة جماعية - تطبيقات التفاضل',            'مهمة جماعية لحل مسائل تطبيقية على التفاضل',       'uploads/session-files/math_group_task.pdf',     'math_group_task.pdf',     1),
    (12, 8, 'teacher', 4, 'explanation', 'ملف شرح - الخلية الحيوانية',               'صور ورسوم توضيحية لأجزاء الخلية الحيوانية',       'uploads/session-files/bio_cell.pdf',            'bio_cell.pdf',            1),
    (13, 9, 'teacher', 5, 'homework',    'واجب اللغة العربية - الأسبوع الأول',       'تحليل نص أدبي من اختيارك بالأساليب البلاغية',    'uploads/session-files/arabic_homework_01.pdf',  'arabic_homework_01.pdf',  1),
    (14, 10,'teacher', 6, 'explanation', 'Grammar Notes - Present Perfect Tense',    'Detailed notes on present perfect with examples',  'uploads/session-files/eng_grammar_01.pdf',      'eng_grammar_01.pdf',      1),
    (15, 10,'teacher', 6, 'exam',        'Grammar Quiz - Present Perfect',           'Multiple choice quiz on present perfect tense',    'uploads/session-files/eng_quiz_01.pdf',         'eng_quiz_01.pdf',         1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT IGNORE INTO `session_file_submissions`
    (`id`, `session_file_id`, `student_id`, `file_path`, `original_name`, `note`, `status`, `grade`, `teacher_feedback`, `graded_by_id`, `graded_at`)
VALUES
    (1,  2,  1, 'uploads/session-submissions/student1_chem_hw1.pdf',   'student1_chem_hw1.pdf',   'حليت كل التمارين إلا رقم 12 لأنني لم أفهمه جيداً',              'graded',  85.00, 'أداء جيد جداً، مراجعة التمرين 12 مطلوبة', 1, NOW()),
    (2,  2,  2, 'uploads/session-submissions/student2_chem_hw1.pdf',   'student2_chem_hw1.pdf',   'حليت جميع التمارين',                                              'graded',  95.00, 'ممتاز! حل مثالي وخطوات واضحة',           1, NOW()),
    (3,  2,  3, 'uploads/session-submissions/student3_chem_hw1.pdf',   'student3_chem_hw1.pdf',   NULL,                                                              'pending', NULL,  NULL,                                       NULL, NULL),
    (4,  4,  1, 'uploads/session-submissions/student1_chem_exam1.pdf', 'student1_chem_exam1.pdf', 'راجعت الكتاب جيداً قبل الحل',                                    'graded',  78.00, 'جيد، تحتاج مراجعة الجزء الثالث',          1, NOW()),
    (5,  4,  2, 'uploads/session-submissions/student2_chem_exam1.pdf', 'student2_chem_exam1.pdf', NULL,                                                              'pending', NULL,  NULL,                                       NULL, NULL),
    (6,  7,  1, 'uploads/session-submissions/student1_phys_hw1.pdf',   'student1_phys_hw1.pdf',   'صعبت علي مسألة رقم 5',                                           'graded',  70.00, 'تحتاج تدريب أكثر على قانون نيوتن الثاني', 2, NOW()),
    (7,  7,  2, 'uploads/session-submissions/student2_phys_hw1.pdf',   'student2_phys_hw1.pdf',   'حليت كل الأسئلة وأضفت حلاً بديلاً للمسألة الأخيرة',            'graded',  100.00,'ممتاز! أفضل حل في الفصل',                2, NOW()),
    (8,  10, 1, 'uploads/session-submissions/student1_math_hw1.pdf',   'student1_math_hw1.pdf',   NULL,                                                              'pending', NULL,  NULL,                                       NULL, NULL),
    (9,  13, 3, 'uploads/session-submissions/student3_arabic_hw1.pdf', 'student3_arabic_hw1.pdf', 'اخترت قصيدة المتنبي لتحليلها',                                  'pending', NULL,  NULL,                                       NULL, NULL),
    (10, 15, 2, 'uploads/session-submissions/student2_eng_quiz1.pdf',  'student2_eng_quiz1.pdf',  'I studied all the examples from the notes',                      'graded',  88.00, 'Very good work! Minor errors in question 7', 6, NOW())
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

SET FOREIGN_KEY_CHECKS = 1;
