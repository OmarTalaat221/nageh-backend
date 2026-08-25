CREATE TABLE IF NOT EXISTS `session_attendance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `session_date` DATE NOT NULL,
    `status` ENUM('present','absent') NOT NULL DEFAULT 'present',
    `marked_by_teacher_id` BIGINT UNSIGNED NULL,
    `marked_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_sess_att` (`session_id`, `student_id`, `session_date`),
    KEY `idx_sa_session` (`session_id`),
    KEY `idx_sa_student` (`student_id`),
    KEY `idx_sa_date` (`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
