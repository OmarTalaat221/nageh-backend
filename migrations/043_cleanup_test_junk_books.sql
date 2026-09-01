-- Removes leftover test/junk book rows created while testing the Create Book
-- endpoint (example.com placeholder links, empty file paths, one pointing at
-- a unit_id that does not exist in `units`). Review the SELECT below before
-- running the DELETEs — these ids are specific to this database snapshot.

-- Preview what will be deleted:
-- SELECT * FROM course_books WHERE id IN (425,426,427,428,429,430,431);

DELETE FROM `course_books_translations` WHERE `book_id` IN (425,426,427,428,429,430,431);
DELETE FROM `student_book_purchase_requests` WHERE `book_id` IN (425,426,427,428,429,430,431);
DELETE FROM `course_books` WHERE `id` IN (425,426,427,428,429,430,431);
