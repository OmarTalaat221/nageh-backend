UPDATE courses SET
    video_preview_url = 'uploads/videos/chem_preview.mp4',
    what_you_will_learn = '["فهم المركبات العضوية والتفاعلات الأساسية","التفاعلات الكيميائية الرئيسية وتطبيقاتها","حل أسئلة الامتحانات بكفاءة عالية"]'
WHERE id = 1;

UPDATE courses_translations SET
    what_you_will_learn = '["فهم المركبات العضوية والتفاعلات الأساسية","التفاعلات الكيميائية الرئيسية وتطبيقاتها","حل أسئلة الامتحانات بكفاءة عالية"]'
WHERE course_id = 1;


INSERT IGNORE INTO course_ratings (student_id, course_id, rating, comment, is_approved) VALUES
(1, 1, 5, 'شرح ممتاز جداً ومبسط، استفدت كتير جداً في فهم التفاعلات!', 1),
(2, 1, 5, 'أفضل كورس شرحت منه الكيمياء العضوية، أسلوب ممتاز ومريح.', 1),
(3, 1, 4, 'محتوى ممتاز وشرح وافي، أتمنى إضافة المزيد من أسئلة الواجب.', 1)
ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment);

INSERT IGNORE INTO teacher_ratings (student_id, teacher_id, rating, comment, is_approved) VALUES
(1, 1, 5, 'أستاذ أحمد كمال أفضل مدرس كيمياء بلا منازع، ربي يبارك في حضرتك.', 1),
(2, 1, 5, 'شرح رائع وتبسيط لكل المفاهيم المعقدة.', 1)
ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment);
