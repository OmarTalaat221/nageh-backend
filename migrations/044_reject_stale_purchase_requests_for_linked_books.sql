-- Course/unit-linked books can no longer be purchased separately (access
-- comes free with the course). This does NOT touch the whole table — it only
-- auto-rejects the specific pending requests that were submitted for books
-- that are now known to be course/unit-linked, leaving every other request
-- (including requests for standalone external books) untouched.

-- Preview what will be affected:
-- SELECT sbpr.* FROM student_book_purchase_requests sbpr
-- JOIN course_books cb ON sbpr.book_id = cb.id
-- WHERE sbpr.status = 'pending' AND (cb.course_id IS NOT NULL OR cb.unit_id IS NOT NULL);

UPDATE `student_book_purchase_requests` sbpr
JOIN `course_books` cb ON sbpr.book_id = cb.id
SET
  sbpr.status = 'rejected',
  sbpr.reviewed_by_type = 'admin',
  sbpr.notes = 'Auto-rejected: this book is bundled with its course/unit and does not require a separate purchase.'
WHERE sbpr.status = 'pending'
  AND (cb.course_id IS NOT NULL OR cb.unit_id IS NOT NULL);
