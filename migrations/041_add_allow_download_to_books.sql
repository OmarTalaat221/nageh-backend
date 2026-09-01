-- Adds device-download control to books: when allow_download = 0, the book
-- is view-only on the platform (served through an authenticated streaming
-- endpoint) instead of exposing a raw, permanently downloadable file URL.
ALTER TABLE `course_books`
  ADD COLUMN `allow_download` TINYINT(1) NOT NULL DEFAULT 1 AFTER `price`;
