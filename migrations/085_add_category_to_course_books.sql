-- A unit's PDFs have always been one undifferentiated pile ("ملخصات"/books). The client wants a
-- second, distinct kind in the same place: PDF exam papers ("اختبارات"), managed exactly like a
-- book (same upload flow, same CRUD) but shown and labelled separately from summaries everywhere -
-- admin, teacher and student. One column is enough to tell them apart; everything else about
-- course_books (translations, teachers, pricing, access) applies to both the same way.
ALTER TABLE `course_books`
    ADD COLUMN `category` ENUM('summary','exam') NOT NULL DEFAULT 'summary' AFTER `file_type`;
