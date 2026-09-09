ALTER TABLE courses ADD COLUMN kind ENUM('normal','package') NOT NULL DEFAULT 'normal' AFTER type;
ALTER TABLE courses ADD INDEX idx_courses_kind (kind);
ALTER TABLE courses ADD INDEX idx_courses_type (type);
