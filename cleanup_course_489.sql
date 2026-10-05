-- =================================================================================================
-- تنظيف بيانات اختبار الكورس رقم 489
-- =================================================================================================
-- الغرض: حذف كل محتوى الكورس 489 (الدروس/الفيديوهات/الامتحانات/الكتب)، كل اشتراكات الطلاب فيه،
-- وكل القيود المالية/الإحصائية المرتبطة به - دون المساس بصف الكورس نفسه (courses.id = 489 يبقى كما هو).
--
-- طريقة الاستخدام:
--   1) نفّذ أولاً قسم "التحقق قبل الحذف" بمفرده وراجع الأرقام (هل هي منطقية؟ هل تخص فعلاً بياناتك
--      التجريبية؟). هذا القسم قراءة فقط ولا يغيّر شيئًا.
--   2) إذا كانت الأرقام صحيحة، نفّذ باقي السكربت كاملاً (من START TRANSACTION حتى COMMIT).
--   3) لو حبيت تتأكد قبل التثبيت النهائي، استبدل COMMIT في آخر سطر بـ ROLLBACK في أول تجربة،
--      وراجع قسم "التحقق بعد الحذف"، ثم أعد التنفيذ من الأول واستخدم COMMIT فعليًا.
-- =================================================================================================


-- -------------------------------------------------------------------------------------------
-- التحقق قبل الحذف (SELECT فقط - آمن، نفّذه أولًا)
-- -------------------------------------------------------------------------------------------
SELECT
    (SELECT COUNT(*) FROM units WHERE course_id = 489)                                   AS units_count,
    (SELECT COUNT(*) FROM videos WHERE unit_id IN (SELECT id FROM units WHERE course_id = 489)) AS videos_count,
    (SELECT COUNT(*) FROM course_exams WHERE course_id = 489
        OR unit_id IN (SELECT id FROM units WHERE course_id = 489))                       AS exams_count,
    (SELECT COUNT(*) FROM course_books WHERE course_id = 489
        OR unit_id IN (SELECT id FROM units WHERE course_id = 489))                       AS books_count,
    (SELECT COUNT(DISTINCT student_subscription_id) FROM student_subscription_courses
        WHERE course_id = 489)                                                           AS linked_subscriptions_count,
    (SELECT COUNT(*) FROM payment_transactions WHERE item_type = 'course' AND item_id = 489) AS payment_transactions_count,
    (SELECT COUNT(*) FROM offline_payment_requests WHERE item_type = 'course' AND item_id = 489) AS offline_payments_count,
    (SELECT COUNT(*) FROM transaction_items WHERE item_type = 'course' AND item_id = 489) AS transaction_items_count;


-- -------------------------------------------------------------------------------------------
-- بداية الحذف الفعلي
-- -------------------------------------------------------------------------------------------
START TRANSACTION;

-- تجميع كل المعرفات المتأثرة في جداول مؤقتة أولًا، حتى لا نعيد حساب نفس الاستعلامات المتداخلة
-- عشرات المرات، وحتى لا نفقد أي معرف بعد حذف صف أب يعتمد عليه استعلام لاحق.
DROP TEMPORARY TABLE IF EXISTS tmp_unit_ids;
CREATE TEMPORARY TABLE tmp_unit_ids AS
    SELECT id FROM units WHERE course_id = 489;

DROP TEMPORARY TABLE IF EXISTS tmp_video_ids;
CREATE TEMPORARY TABLE tmp_video_ids AS
    SELECT id FROM videos WHERE unit_id IN (SELECT id FROM tmp_unit_ids);

DROP TEMPORARY TABLE IF EXISTS tmp_exam_ids;
CREATE TEMPORARY TABLE tmp_exam_ids AS
    SELECT id FROM course_exams
    WHERE course_id = 489 OR unit_id IN (SELECT id FROM tmp_unit_ids);

DROP TEMPORARY TABLE IF EXISTS tmp_question_ids;
CREATE TEMPORARY TABLE tmp_question_ids AS
    SELECT id FROM questions
    WHERE exam_id IN (SELECT id FROM tmp_exam_ids)
       OR course_id = 489
       OR unit_id IN (SELECT id FROM tmp_unit_ids);

DROP TEMPORARY TABLE IF EXISTS tmp_attempt_ids;
CREATE TEMPORARY TABLE tmp_attempt_ids AS
    SELECT id FROM exam_attempts WHERE exam_id IN (SELECT id FROM tmp_exam_ids);

DROP TEMPORARY TABLE IF EXISTS tmp_book_ids;
CREATE TEMPORARY TABLE tmp_book_ids AS
    SELECT id FROM course_books
    WHERE course_id = 489 OR unit_id IN (SELECT id FROM tmp_unit_ids);

-- كل اشتراك طالب كان مربوطًا بالكورس 489 (قد يكون الاشتراك خاصًا بهذا الكورس فقط، أو باقة تضم
-- كورسات أخرى أيضًا - الفرق بينهما يُحسم لاحقًا قبل حذف صف الاشتراك نفسه).
DROP TEMPORARY TABLE IF EXISTS tmp_subscription_ids;
CREATE TEMPORARY TABLE tmp_subscription_ids AS
    SELECT DISTINCT student_subscription_id AS id
    FROM student_subscription_courses
    WHERE course_id = 489;


-- 1) محتوى الامتحانات (الأعمق فالأقرب)
DELETE FROM mcq_options            WHERE question_id IN (SELECT id FROM tmp_question_ids);
DELETE FROM true_false_answers     WHERE question_id IN (SELECT id FROM tmp_question_ids);
DELETE FROM written_model_answers  WHERE question_id IN (SELECT id FROM tmp_question_ids);
DELETE FROM exam_attempt_answers   WHERE attempt_id IN (SELECT id FROM tmp_attempt_ids)
                                      OR question_id IN (SELECT id FROM tmp_question_ids);
DELETE FROM student_question_answers WHERE question_id IN (SELECT id FROM tmp_question_ids);
DELETE FROM exam_attempts          WHERE id IN (SELECT id FROM tmp_attempt_ids);
DELETE FROM questions              WHERE id IN (SELECT id FROM tmp_question_ids);
DELETE FROM exam_teachers          WHERE exam_id IN (SELECT id FROM tmp_exam_ids);
DELETE FROM course_exams_translations WHERE exam_id IN (SELECT id FROM tmp_exam_ids);
DELETE FROM course_exams           WHERE id IN (SELECT id FROM tmp_exam_ids);

-- 2) الفيديوهات (بما فيها الجدول القديم المصاحب course_videos)
DELETE FROM student_video_progress WHERE video_id IN (SELECT id FROM tmp_video_ids);
DELETE FROM video_teachers         WHERE video_id IN (SELECT id FROM tmp_video_ids);
DELETE FROM videos_translations    WHERE video_id IN (SELECT id FROM tmp_video_ids);
DELETE FROM course_videos_translations WHERE video_id IN (SELECT id FROM tmp_video_ids);
DELETE FROM course_videos          WHERE id IN (SELECT id FROM tmp_video_ids);
DELETE FROM videos                 WHERE id IN (SELECT id FROM tmp_video_ids);

-- 3) الكتب/ملخصات PDF المرتبطة بالكورس أو بدروسه
DELETE FROM student_book_progress          WHERE book_id IN (SELECT id FROM tmp_book_ids);
DELETE FROM student_book_purchase_requests WHERE book_id IN (SELECT id FROM tmp_book_ids);
DELETE FROM student_subscription_books     WHERE book_id IN (SELECT id FROM tmp_book_ids);
DELETE FROM course_books_translations      WHERE book_id IN (SELECT id FROM tmp_book_ids);
DELETE FROM course_books                   WHERE id IN (SELECT id FROM tmp_book_ids);

-- 4) الدروس نفسها
DELETE FROM unit_teachers      WHERE unit_id IN (SELECT id FROM tmp_unit_ids);
DELETE FROM units_translations WHERE unit_id IN (SELECT id FROM tmp_unit_ids);
DELETE FROM units              WHERE id IN (SELECT id FROM tmp_unit_ids);

-- 5) اشتراكات الطلاب في الكورس + لوج استخدام الأكواد المرتبط بها
DELETE FROM code_redemptions_log WHERE student_subscription_id IN (SELECT id FROM tmp_subscription_ids);
DELETE FROM student_subscription_courses WHERE course_id = 489;

-- لا يُحذف صف الاشتراك نفسه إلا إذا لم يعد مرتبطًا بأي كورس/مادة/معلم/كتاب آخر - حماية
-- للاشتراكات الباقات التي تضم كورس 489 مع كورسات حقيقية أخرى.
DELETE FROM student_subscriptions
WHERE id IN (SELECT id FROM tmp_subscription_ids)
  AND id NOT IN (SELECT student_subscription_id FROM student_subscription_courses)
  AND id NOT IN (SELECT student_subscription_id FROM student_subscription_subjects)
  AND id NOT IN (SELECT student_subscription_id FROM student_subscription_teachers)
  AND id NOT IN (SELECT student_subscription_id FROM student_subscription_books);

-- 6) القيود المالية/الإحصائية الخاصة بالكورس 489 (هذه هي مصادر أرقام المبيعات في helpers/sales.php)
DELETE FROM payment_transactions     WHERE item_type = 'course' AND item_id = 489;
DELETE FROM offline_payment_requests WHERE item_type = 'course' AND item_id = 489;
DELETE FROM transaction_items        WHERE item_type = 'course' AND item_id = 489;

DROP TEMPORARY TABLE IF EXISTS tmp_unit_ids, tmp_video_ids, tmp_exam_ids, tmp_question_ids,
    tmp_attempt_ids, tmp_book_ids, tmp_subscription_ids;

COMMIT;


-- -------------------------------------------------------------------------------------------
-- التحقق بعد الحذف (يجب أن تكون كل الأرقام أصفارًا)
-- -------------------------------------------------------------------------------------------
SELECT
    (SELECT COUNT(*) FROM units WHERE course_id = 489)                                   AS units_count,
    (SELECT COUNT(*) FROM videos WHERE unit_id IN (SELECT id FROM units WHERE course_id = 489)) AS videos_count,
    (SELECT COUNT(*) FROM course_exams WHERE course_id = 489)                             AS exams_count,
    (SELECT COUNT(*) FROM course_books WHERE course_id = 489)                             AS books_count,
    (SELECT COUNT(*) FROM student_subscription_courses WHERE course_id = 489)             AS linked_subscriptions_count,
    (SELECT COUNT(*) FROM payment_transactions WHERE item_type = 'course' AND item_id = 489) AS payment_transactions_count,
    (SELECT COUNT(*) FROM offline_payment_requests WHERE item_type = 'course' AND item_id = 489) AS offline_payments_count,
    (SELECT COUNT(*) FROM transaction_items WHERE item_type = 'course' AND item_id = 489) AS transaction_items_count;
