-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 13, 2026 at 01:36 AM
-- Server version: 10.11.18-MariaDB-cll-lve-log
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campcod1_najeh`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `number` smallint(5) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `code`, `number`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'grade-1', 1, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'grade-2', 2, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'grade-3', 3, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 'grade-4', 4, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 'grade-5', 5, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 'grade-6', 6, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 'grade-7', 7, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 'grade-8', 8, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 'grade-9', 9, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 'grade-10', 10, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 'grade-11', 11, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 'grade-12', 12, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `academic_years_translations`
--

CREATE TABLE `academic_years_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years_translations`
--

INSERT INTO `academic_years_translations` (`id`, `academic_year_id`, `language_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'الصف الأول الابتدائي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Primary Grade 1', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'الصف الثاني الابتدائي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'Primary Grade 2', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'الصف الثالث الابتدائي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Primary Grade 3', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 4, 1, 'الصف الرابع الابتدائي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 4, 2, 'Primary Grade 4', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 5, 1, 'الصف الخامس الابتدائي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 5, 2, 'Primary Grade 5', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 6, 1, 'الصف السادس الابتدائي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 6, 2, 'Primary Grade 6', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(13, 7, 1, 'الصف الأول الإعدادي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(14, 7, 2, 'Preparatory Grade 1', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(15, 8, 1, 'الصف الثاني الإعدادي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(16, 8, 2, 'Preparatory Grade 2', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(17, 9, 1, 'الصف الثالث الإعدادي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(18, 9, 2, 'Preparatory Grade 3', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(19, 10, 1, 'الصف الأول الثانوي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(20, 10, 2, 'Secondary Grade 1', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(21, 11, 1, 'الصف الثاني الثانوي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(22, 11, 2, 'Secondary Grade 2', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(23, 12, 1, 'الصف الثالث الثانوي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(24, 12, 2, 'Secondary Grade 3', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `role` enum('super_admin','admin','moderator') NOT NULL DEFAULT 'admin',
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `image`, `role`, `role_id`, `is_email_verified`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Najeh Super Admin', 'info@najeh.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=12', 'super_admin', 1, 1, 1, '2026-08-10 16:00:00', '2026-08-10 13:03:41'),
(2, 'Maha Content Manager', 'content@najeh-demo.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=32', 'admin', 2, 1, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'Omar Support Agent', 'support@najeh-demo.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=15', 'moderator', 3, 1, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `advertisements`
--

CREATE TABLE `advertisements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `link` varchar(1000) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `type` enum('banner','media','text') NOT NULL DEFAULT 'banner',
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `user_type` enum('student','teacher','parent') DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advertisements`
--

INSERT INTO `advertisements` (`id`, `link`, `position`, `type`, `status`, `user_type`, `user_id`, `is_active`, `created_at`, `updated_at`) VALUES
(101, 'https://www.khanacademy.org/', 'home_top', 'banner', 'published', NULL, NULL, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(102, 'https://www.khanacademy.org/', 'home_middle', 'banner', 'published', NULL, NULL, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(103, 'https://www.khanacademy.org/', 'student_dashboard', 'media', 'published', 'student', 1003, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(104, 'https://www.khanacademy.org/', 'home_bottom', 'banner', 'published', NULL, NULL, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(105, 'https://www.khanacademy.org/', 'announcement_bar', 'text', 'published', NULL, NULL, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(106, 'https://www.khanacademy.org/', 'student_dashboard', 'banner', 'published', 'student', 1006, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `advertisements_translations`
--

CREATE TABLE `advertisements_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `advertisement_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advertisements_translations`
--

INSERT INTO `advertisements_translations` (`id`, `advertisement_id`, `language_id`, `title`, `content`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
(1001, 101, 1, 'ذاكر بخطة واضحة', 'محتوى تعليمي منظم وروابط مباشرة لمصادر عامة.', 'https://loremflickr.com/1400/700/student,study,planner?lock=101', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1002, 101, 2, 'Study with a clear plan', 'Structured learning content with direct links to public resources.', 'https://loremflickr.com/1400/700/student,study,planner?lock=1101', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1003, 102, 1, 'اختبر نفسك أسبوعيًا', 'محتوى تعليمي منظم وروابط مباشرة لمصادر عامة.', 'https://loremflickr.com/1400/700/exam,quiz,study?lock=102', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1004, 102, 2, 'Test yourself weekly', 'Structured learning content with direct links to public resources.', 'https://loremflickr.com/1400/700/exam,quiz,study?lock=1102', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1005, 103, 1, 'محتوى علمي مفتوح', 'محتوى تعليمي منظم وروابط مباشرة لمصادر عامة.', 'https://loremflickr.com/1400/700/books,education,library?lock=103', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1006, 103, 2, 'Open learning resources', 'Structured learning content with direct links to public resources.', 'https://loremflickr.com/1400/700/books,education,library?lock=1103', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1007, 104, 1, 'راجع قبل الامتحان', 'محتوى تعليمي منظم وروابط مباشرة لمصادر عامة.', 'https://loremflickr.com/1400/700/exam,revision,study?lock=104', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1008, 104, 2, 'Review before the exam', 'Structured learning content with direct links to public resources.', 'https://loremflickr.com/1400/700/exam,revision,study?lock=1104', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1009, 105, 1, 'موعد المراجعة النهائية', 'محتوى تعليمي منظم وروابط مباشرة لمصادر عامة.', 'https://loremflickr.com/1400/700/exam,schedule,study?lock=105', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1010, 105, 2, 'Final review schedule', 'Structured learning content with direct links to public resources.', 'https://loremflickr.com/1400/700/exam,schedule,study?lock=1105', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1011, 106, 1, 'تابع تقدمك', 'محتوى تعليمي منظم وروابط مباشرة لمصادر عامة.', 'https://loremflickr.com/1400/700/progress,student,education?lock=106', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1012, 106, 2, 'Track your progress', 'Structured learning content with direct links to public resources.', 'https://loremflickr.com/1400/700/progress,student,education?lock=1106', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `affiliates`
--

CREATE TABLE `affiliates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `affiliate_code` varchar(100) NOT NULL,
  `commission_type` enum('percentage','fixed') NOT NULL,
  `commission_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `affiliates`
--

INSERT INTO `affiliates` (`id`, `student_id`, `teacher_id`, `affiliate_code`, `commission_type`, `commission_value`, `is_active`, `created_at`, `updated_at`) VALUES
(101, 1001, NULL, 'NAJEH-STU-1001', 'percentage', 8.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(102, 1002, NULL, 'NAJEH-STU-1002', 'percentage', 9.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(103, 1003, NULL, 'NAJEH-STU-1003', 'percentage', 10.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(104, 1004, NULL, 'NAJEH-STU-1004', 'percentage', 11.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(105, 1005, NULL, 'NAJEH-STU-1005', 'percentage', 12.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(106, 1006, NULL, 'NAJEH-STU-1006', 'percentage', 8.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(107, 1007, NULL, 'NAJEH-STU-1007', 'percentage', 9.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(108, 1008, NULL, 'NAJEH-STU-1008', 'percentage', 10.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(109, 1009, NULL, 'NAJEH-STU-1009', 'percentage', 11.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(110, 1010, NULL, 'NAJEH-STU-1010', 'percentage', 12.00, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_commissions`
--

CREATE TABLE `affiliate_commissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `affiliate_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_date` datetime NOT NULL,
  `status` enum('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `affiliate_commissions`
--

INSERT INTO `affiliate_commissions` (`id`, `affiliate_id`, `amount`, `commission_date`, `status`, `is_active`, `created_at`, `updated_at`) VALUES
(101, 101, 75.00, '2026-08-05 12:00:00', 'approved', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(102, 101, 40.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(103, 102, 90.00, '2026-08-05 12:00:00', 'paid', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(104, 102, 50.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(105, 103, 105.00, '2026-08-05 12:00:00', 'paid', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(106, 103, 60.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(107, 104, 120.00, '2026-08-05 12:00:00', 'approved', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(108, 104, 70.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(109, 105, 135.00, '2026-08-05 12:00:00', 'paid', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(110, 105, 80.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(111, 106, 150.00, '2026-08-05 12:00:00', 'paid', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(112, 106, 90.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(113, 107, 165.00, '2026-08-05 12:00:00', 'approved', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(114, 107, 100.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(115, 108, 180.00, '2026-08-05 12:00:00', 'paid', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(116, 108, 110.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(117, 109, 195.00, '2026-08-05 12:00:00', 'paid', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(118, 109, 120.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00'),
(119, 110, 210.00, '2026-08-05 12:00:00', 'approved', 1, '2026-08-05 16:00:00', '2026-08-10 16:00:00'),
(120, 110, 130.00, '2026-08-09 12:00:00', 'pending', 1, '2026-08-09 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title_ar` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `image_ar` varchar(500) NOT NULL,
  `image_en` varchar(500) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `type` enum('image','video') NOT NULL DEFAULT 'image',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title_ar`, `title_en`, `image_ar`, `image_en`, `link`, `type`, `is_active`, `created_at`, `updated_at`) VALUES
(101, 'ابدأ الترم بقوة', 'Start the semester strong', 'https://loremflickr.com/1600/600/students,school,education?lock=101', 'https://loremflickr.com/1600/600/students,school,education?lock=1101', 'https://www.khanacademy.org/', 'image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(102, 'مراجعة الثانوية العامة', 'Final secondary review', 'https://loremflickr.com/1600/600/exam,study,students?lock=102', 'https://loremflickr.com/1600/600/exam,study,students?lock=1102', 'https://www.khanacademy.org/', 'image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(103, 'كورسات العلوم', 'Science courses', 'https://loremflickr.com/1600/600/science,laboratory,students?lock=103', 'https://loremflickr.com/1600/600/science,laboratory,students?lock=1103', 'https://www.khanacademy.org/', 'image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(104, 'الرياضيات خطوة بخطوة', 'Math step by step', 'https://loremflickr.com/1600/600/mathematics,geometry,study?lock=104', 'https://loremflickr.com/1600/600/mathematics,geometry,study?lock=1104', 'https://www.khanacademy.org/', 'image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(105, 'تعلم البرمجة', 'Learn programming', 'https://loremflickr.com/1600/600/programming,computer,coding?lock=105', 'https://loremflickr.com/1600/600/programming,computer,coding?lock=1105', 'https://www.khanacademy.org/', 'image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(106, 'احجز استشارتك', 'Book your consultation', 'https://loremflickr.com/1600/600/teacher,student,consultation?lock=106', 'https://loremflickr.com/1600/600/teacher,student,consultation?lock=1106', 'https://www.khanacademy.org/', 'image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `banner_views`
--

CREATE TABLE `banner_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `banner_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_type` varchar(50) DEFAULT 'guest',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banner_views`
--

INSERT INTO `banner_views` (`id`, `banner_id`, `user_id`, `user_type`, `ip_address`, `user_agent`, `created_at`) VALUES
(1001, 101, 1001, 'student', '198.51.100.1', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 12:00:00'),
(1002, 102, 1002, 'student', '198.51.100.2', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 13:00:00'),
(1003, 103, 1003, 'student', '198.51.100.3', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 14:00:00'),
(1004, 104, 1004, 'student', '198.51.100.4', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 15:00:00'),
(1005, 105, 1005, 'student', '198.51.100.5', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 16:00:00'),
(1006, 106, 1006, 'student', '198.51.100.6', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 17:00:00'),
(1007, 101, 1007, 'student', '198.51.100.7', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 18:00:00'),
(1008, 102, 1008, 'student', '198.51.100.8', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 19:00:00'),
(1009, 103, 1009, 'student', '198.51.100.9', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 20:00:00'),
(1010, 104, 1010, 'student', '198.51.100.10', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 21:00:00'),
(1011, 105, 1011, 'student', '198.51.100.11', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 22:00:00'),
(1012, 106, 1012, 'student', '198.51.100.12', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-01 23:00:00'),
(1013, 101, 1013, 'student', '198.51.100.13', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 00:00:00'),
(1014, 102, 1014, 'student', '198.51.100.14', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 01:00:00'),
(1015, 103, 1015, 'student', '198.51.100.15', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 02:00:00'),
(1016, 104, 1016, 'student', '198.51.100.16', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 03:00:00'),
(1017, 105, 1017, 'student', '198.51.100.17', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 04:00:00'),
(1018, 106, 1018, 'student', '198.51.100.18', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 05:00:00'),
(1019, 101, 1019, 'student', '198.51.100.19', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 06:00:00'),
(1020, 102, 1020, 'student', '198.51.100.20', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 07:00:00'),
(1021, 103, 1021, 'student', '198.51.100.21', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 08:00:00'),
(1022, 104, 1022, 'student', '198.51.100.22', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 09:00:00'),
(1023, 105, 1023, 'student', '198.51.100.23', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 10:00:00'),
(1024, 106, 1024, 'student', '198.51.100.24', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 11:00:00'),
(1025, 101, 1025, 'student', '198.51.100.25', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 12:00:00'),
(1026, 102, 1026, 'student', '198.51.100.26', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 13:00:00'),
(1027, 103, 1027, 'student', '198.51.100.27', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 14:00:00'),
(1028, 104, 1028, 'student', '198.51.100.28', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 15:00:00'),
(1029, 105, 1029, 'student', '198.51.100.29', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 16:00:00'),
(1030, 106, 1030, 'student', '198.51.100.30', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 17:00:00'),
(1031, 101, 1031, 'student', '198.51.100.31', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 18:00:00'),
(1032, 102, 1032, 'student', '198.51.100.32', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 19:00:00'),
(1033, 103, 1033, 'student', '198.51.100.33', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 20:00:00'),
(1034, 104, 1034, 'student', '198.51.100.34', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 21:00:00'),
(1035, 105, 1035, 'student', '198.51.100.35', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 22:00:00'),
(1036, 106, 1036, 'student', '198.51.100.36', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-02 23:00:00'),
(1037, 101, 1037, 'student', '198.51.100.37', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 00:00:00'),
(1038, 102, 1038, 'student', '198.51.100.38', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 01:00:00'),
(1039, 103, 1039, 'student', '198.51.100.39', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 02:00:00'),
(1040, 104, 1040, 'student', '198.51.100.40', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 03:00:00'),
(1041, 105, 1041, 'student', '198.51.100.41', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 04:00:00'),
(1042, 106, 1042, 'student', '198.51.100.42', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 05:00:00'),
(1043, 101, 1043, 'student', '198.51.100.43', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 06:00:00'),
(1044, 102, 1044, 'student', '198.51.100.44', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 07:00:00'),
(1045, 103, 1045, 'student', '198.51.100.45', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 08:00:00'),
(1046, 104, 1046, 'student', '198.51.100.46', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 09:00:00'),
(1047, 105, 1047, 'student', '198.51.100.47', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 10:00:00'),
(1048, 106, 1048, 'student', '198.51.100.48', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 11:00:00'),
(1049, 101, 1049, 'student', '198.51.100.49', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 12:00:00'),
(1050, 102, 1050, 'student', '198.51.100.50', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 13:00:00'),
(1051, 103, 1051, 'student', '198.51.100.51', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 14:00:00'),
(1052, 104, 1052, 'student', '198.51.100.52', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 15:00:00'),
(1053, 105, 1053, 'student', '198.51.100.53', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 16:00:00'),
(1054, 106, 1054, 'student', '198.51.100.54', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 17:00:00'),
(1055, 101, 1055, 'student', '198.51.100.55', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 18:00:00'),
(1056, 102, 1056, 'student', '198.51.100.56', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 19:00:00'),
(1057, 103, 1057, 'student', '198.51.100.57', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 20:00:00'),
(1058, 104, 1058, 'student', '198.51.100.58', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 21:00:00'),
(1059, 105, 1059, 'student', '198.51.100.59', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 22:00:00'),
(1060, 106, 1060, 'student', '198.51.100.60', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-03 23:00:00'),
(1061, 101, 1001, 'student', '198.51.100.61', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 00:00:00'),
(1062, 102, 1002, 'student', '198.51.100.62', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 01:00:00'),
(1063, 103, 1003, 'student', '198.51.100.63', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 02:00:00'),
(1064, 104, 1004, 'student', '198.51.100.64', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 03:00:00'),
(1065, 105, 1005, 'student', '198.51.100.65', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 04:00:00'),
(1066, 106, 1006, 'student', '198.51.100.66', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 05:00:00'),
(1067, 101, 1007, 'student', '198.51.100.67', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 06:00:00'),
(1068, 102, 1008, 'student', '198.51.100.68', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 07:00:00'),
(1069, 103, 1009, 'student', '198.51.100.69', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 08:00:00'),
(1070, 104, 1010, 'student', '198.51.100.70', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 09:00:00'),
(1071, 105, 1011, 'student', '198.51.100.71', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 10:00:00'),
(1072, 106, 1012, 'student', '198.51.100.72', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 11:00:00'),
(1073, 101, 1013, 'student', '198.51.100.73', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 12:00:00'),
(1074, 102, 1014, 'student', '198.51.100.74', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 13:00:00'),
(1075, 103, 1015, 'student', '198.51.100.75', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 14:00:00'),
(1076, 104, 1016, 'student', '198.51.100.76', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 15:00:00'),
(1077, 105, 1017, 'student', '198.51.100.77', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 16:00:00'),
(1078, 106, 1018, 'student', '198.51.100.78', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 17:00:00'),
(1079, 101, 1019, 'student', '198.51.100.79', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 18:00:00'),
(1080, 102, 1020, 'student', '198.51.100.80', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 19:00:00'),
(1081, 103, 1021, 'student', '198.51.100.81', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 20:00:00'),
(1082, 104, 1022, 'student', '198.51.100.82', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 21:00:00'),
(1083, 105, 1023, 'student', '198.51.100.83', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 22:00:00'),
(1084, 106, 1024, 'student', '198.51.100.84', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-04 23:00:00'),
(1085, 101, 1025, 'student', '198.51.100.85', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 00:00:00'),
(1086, 102, 1026, 'student', '198.51.100.86', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 01:00:00'),
(1087, 103, 1027, 'student', '198.51.100.87', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 02:00:00'),
(1088, 104, 1028, 'student', '198.51.100.88', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 03:00:00'),
(1089, 105, 1029, 'student', '198.51.100.89', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 04:00:00'),
(1090, 106, 1030, 'student', '198.51.100.90', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 05:00:00'),
(1091, 101, 1031, 'student', '198.51.100.91', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 06:00:00'),
(1092, 102, 1032, 'student', '198.51.100.92', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 07:00:00'),
(1093, 103, 1033, 'student', '198.51.100.93', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 08:00:00'),
(1094, 104, 1034, 'student', '198.51.100.94', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 09:00:00'),
(1095, 105, 1035, 'student', '198.51.100.95', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 10:00:00'),
(1096, 106, 1036, 'student', '198.51.100.96', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 11:00:00'),
(1097, 101, 1037, 'student', '198.51.100.97', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 12:00:00'),
(1098, 102, 1038, 'student', '198.51.100.98', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 13:00:00'),
(1099, 103, 1039, 'student', '198.51.100.99', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 14:00:00'),
(1100, 104, 1040, 'student', '198.51.100.100', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 15:00:00'),
(1101, 105, 1041, 'student', '198.51.100.101', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 16:00:00'),
(1102, 106, 1042, 'student', '198.51.100.102', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 17:00:00'),
(1103, 101, 1043, 'student', '198.51.100.103', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 18:00:00'),
(1104, 102, 1044, 'student', '198.51.100.104', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 19:00:00'),
(1105, 103, 1045, 'student', '198.51.100.105', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 20:00:00'),
(1106, 104, 1046, 'student', '198.51.100.106', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 21:00:00'),
(1107, 105, 1047, 'student', '198.51.100.107', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 22:00:00'),
(1108, 106, 1048, 'student', '198.51.100.108', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-05 23:00:00'),
(1109, 101, 1049, 'student', '198.51.100.109', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 00:00:00'),
(1110, 102, 1050, 'student', '198.51.100.110', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 01:00:00'),
(1111, 103, 1051, 'student', '198.51.100.111', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 02:00:00'),
(1112, 104, 1052, 'student', '198.51.100.112', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 03:00:00'),
(1113, 105, 1053, 'student', '198.51.100.113', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 04:00:00'),
(1114, 106, 1054, 'student', '198.51.100.114', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 05:00:00'),
(1115, 101, 1055, 'student', '198.51.100.115', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 06:00:00'),
(1116, 102, 1056, 'student', '198.51.100.116', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 07:00:00'),
(1117, 103, 1057, 'student', '198.51.100.117', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 08:00:00'),
(1118, 104, 1058, 'student', '198.51.100.118', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 09:00:00'),
(1119, 105, 1059, 'student', '198.51.100.119', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 10:00:00'),
(1120, 106, 1060, 'student', '198.51.100.120', 'Mozilla/5.0 SeedBrowser/1.0', '2026-08-06 11:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `book_teachers`
--

CREATE TABLE `book_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_teachers`
--

INSERT INTO `book_teachers` (`id`, `book_id`, `teacher_id`, `is_active`, `created_at`) VALUES
(1001, 401, 201, 1, '2026-07-01 14:00:00'),
(1002, 402, 201, 1, '2026-07-01 14:00:00'),
(1003, 403, 202, 1, '2026-07-01 14:00:00'),
(1004, 404, 202, 1, '2026-07-01 14:00:00'),
(1005, 405, 203, 1, '2026-07-01 14:00:00'),
(1006, 406, 203, 1, '2026-07-01 14:00:00'),
(1007, 407, 204, 1, '2026-07-01 14:00:00'),
(1008, 408, 204, 1, '2026-07-01 14:00:00'),
(1009, 409, 205, 1, '2026-07-01 14:00:00'),
(1010, 410, 205, 1, '2026-07-01 14:00:00'),
(1011, 411, 206, 1, '2026-07-01 14:00:00'),
(1012, 412, 206, 1, '2026-07-01 14:00:00'),
(1013, 413, 207, 1, '2026-07-01 14:00:00'),
(1014, 414, 207, 1, '2026-07-01 14:00:00'),
(1015, 415, 208, 1, '2026-07-01 14:00:00'),
(1016, 416, 208, 1, '2026-07-01 14:00:00'),
(1017, 417, 209, 1, '2026-07-01 14:00:00'),
(1018, 418, 209, 1, '2026-07-01 14:00:00'),
(1019, 419, 210, 1, '2026-07-01 14:00:00'),
(1020, 420, 210, 1, '2026-07-01 14:00:00'),
(1021, 421, 211, 1, '2026-07-01 14:00:00'),
(1022, 422, 211, 1, '2026-07-01 14:00:00'),
(1023, 423, 212, 1, '2026-07-01 14:00:00'),
(1024, 424, 212, 1, '2026-07-01 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `score` decimal(8,2) NOT NULL DEFAULT 0.00,
  `certificate_type` varchar(50) NOT NULL DEFAULT 'general',
  `image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_type` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `created_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `academic_year_id`, `subject_id`, `course_id`, `code`, `score`, `certificate_type`, `image`, `is_active`, `created_by_type`, `created_by_id`, `created_at`, `updated_at`) VALUES
(101, 11, 1, 201, 'CERT-SEED-101', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=101', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(102, 12, 1, 202, 'CERT-SEED-102', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=102', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(103, 11, 2, 203, 'CERT-SEED-103', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=103', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(104, 12, 2, 204, 'CERT-SEED-104', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=104', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(105, 11, 3, 205, 'CERT-SEED-105', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=105', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(106, 12, 3, 206, 'CERT-SEED-106', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=106', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(107, 11, 4, 207, 'CERT-SEED-107', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=107', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(108, 12, 4, 208, 'CERT-SEED-108', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=108', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(109, 11, 5, 209, 'CERT-SEED-109', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=109', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(110, 12, 5, 210, 'CERT-SEED-110', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=110', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(111, 11, 6, 211, 'CERT-SEED-111', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=111', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(112, 12, 6, 212, 'CERT-SEED-112', 85.00, 'course_completion', 'https://loremflickr.com/1600/1100/certificate,diploma,education?lock=112', 1, 'admin', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `certificates_translations`
--

CREATE TABLE `certificates_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `certificate_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates_translations`
--

INSERT INTO `certificates_translations` (`id`, `certificate_id`, `language_id`, `name`, `description`) VALUES
(1001, 101, 1, 'شهادة إتمام اللغة العربية', 'تُمنح عند إتمام متطلبات كورس اللغة العربية.'),
(1002, 101, 2, 'Arabic Language Completion Certificate', 'Awarded after completing the Arabic Language course requirements.'),
(1003, 102, 1, 'شهادة إتمام اللغة العربية', 'تُمنح عند إتمام متطلبات كورس اللغة العربية.'),
(1004, 102, 2, 'Arabic Language Completion Certificate', 'Awarded after completing the Arabic Language course requirements.'),
(1005, 103, 1, 'شهادة إتمام اللغة الإنجليزية', 'تُمنح عند إتمام متطلبات كورس اللغة الإنجليزية.'),
(1006, 103, 2, 'English Language Completion Certificate', 'Awarded after completing the English Language course requirements.'),
(1007, 104, 1, 'شهادة إتمام اللغة الإنجليزية', 'تُمنح عند إتمام متطلبات كورس اللغة الإنجليزية.'),
(1008, 104, 2, 'English Language Completion Certificate', 'Awarded after completing the English Language course requirements.'),
(1009, 105, 1, 'شهادة إتمام الرياضيات', 'تُمنح عند إتمام متطلبات كورس الرياضيات.'),
(1010, 105, 2, 'Mathematics Completion Certificate', 'Awarded after completing the Mathematics course requirements.'),
(1011, 106, 1, 'شهادة إتمام الرياضيات', 'تُمنح عند إتمام متطلبات كورس الرياضيات.'),
(1012, 106, 2, 'Mathematics Completion Certificate', 'Awarded after completing the Mathematics course requirements.'),
(1013, 107, 1, 'شهادة إتمام الفيزياء', 'تُمنح عند إتمام متطلبات كورس الفيزياء.'),
(1014, 107, 2, 'Physics Completion Certificate', 'Awarded after completing the Physics course requirements.'),
(1015, 108, 1, 'شهادة إتمام الفيزياء', 'تُمنح عند إتمام متطلبات كورس الفيزياء.'),
(1016, 108, 2, 'Physics Completion Certificate', 'Awarded after completing the Physics course requirements.'),
(1017, 109, 1, 'شهادة إتمام الكيمياء', 'تُمنح عند إتمام متطلبات كورس الكيمياء.'),
(1018, 109, 2, 'Chemistry Completion Certificate', 'Awarded after completing the Chemistry course requirements.'),
(1019, 110, 1, 'شهادة إتمام الكيمياء', 'تُمنح عند إتمام متطلبات كورس الكيمياء.'),
(1020, 110, 2, 'Chemistry Completion Certificate', 'Awarded after completing the Chemistry course requirements.'),
(1021, 111, 1, 'شهادة إتمام الأحياء', 'تُمنح عند إتمام متطلبات كورس الأحياء.'),
(1022, 111, 2, 'Biology Completion Certificate', 'Awarded after completing the Biology course requirements.'),
(1023, 112, 1, 'شهادة إتمام الأحياء', 'تُمنح عند إتمام متطلبات كورس الأحياء.'),
(1024, 112, 2, 'Biology Completion Certificate', 'Awarded after completing the Biology course requirements.');

-- --------------------------------------------------------

--
-- Table structure for table `certificate_teachers`
--

CREATE TABLE `certificate_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `certificate_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificate_teachers`
--

INSERT INTO `certificate_teachers` (`id`, `certificate_id`, `teacher_id`, `is_active`, `created_at`) VALUES
(1001, 101, 201, 1, '2026-08-10 16:00:00'),
(1002, 102, 201, 1, '2026-08-10 16:00:00'),
(1003, 103, 202, 1, '2026-08-10 16:00:00'),
(1004, 104, 202, 1, '2026-08-10 16:00:00'),
(1005, 105, 203, 1, '2026-08-10 16:00:00'),
(1006, 106, 203, 1, '2026-08-10 16:00:00'),
(1007, 107, 204, 1, '2026-08-10 16:00:00'),
(1008, 108, 204, 1, '2026-08-10 16:00:00'),
(1009, 109, 205, 1, '2026-08-10 16:00:00'),
(1010, 110, 205, 1, '2026-08-10 16:00:00'),
(1011, 111, 206, 1, '2026-08-10 16:00:00'),
(1012, 112, 206, 1, '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'cairo', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'giza', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'alexandria', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 'qalyubia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 'dakahlia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 'sharqia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 'gharbia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 'monufia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 'beheira', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 'fayoum', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 'minya', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `cities_translations`
--

CREATE TABLE `cities_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `city_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities_translations`
--

INSERT INTO `cities_translations` (`id`, `city_id`, `language_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'القاهرة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Cairo', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'الجيزة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'Giza', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'الإسكندرية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Alexandria', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 4, 1, 'القليوبية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 4, 2, 'Qalyubia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 5, 1, 'الدقهلية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 5, 2, 'Dakahlia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 6, 1, 'الشرقية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 6, 2, 'Sharqia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(13, 7, 1, 'الغربية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(14, 7, 2, 'Gharbia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(15, 8, 1, 'المنوفية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(16, 8, 2, 'Monufia', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(17, 9, 1, 'البحيرة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(18, 9, 2, 'Beheira', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(19, 10, 1, 'الفيوم', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(20, 10, 2, 'Fayoum', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(21, 11, 1, 'المنيا', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(22, 11, 2, 'Minya', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `class_sessions`
--

CREATE TABLE `class_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `day_of_week` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `meeting_link` varchar(500) DEFAULT NULL,
  `grade` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_sessions`
--

INSERT INTO `class_sessions` (`id`, `teacher_id`, `subject_id`, `course_id`, `code`, `day_of_week`, `start_time`, `end_time`, `room`, `is_online`, `meeting_link`, `grade`, `is_active`, `created_at`) VALUES
(1, 1, 1, 1, 'SESS-CHEM-001', 'Sunday', '09:00:00', '10:30:00', 'قاعة 101', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(2, 1, 1, 1, 'SESS-CHEM-002', 'Tuesday', '09:00:00', '10:30:00', 'قاعة 101', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(3, 1, 1, 1, 'SESS-CHEM-003', 'Thursday', '09:00:00', '10:30:00', 'قاعة 101', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(4, 2, 2, 2, 'SESS-PHYS-001', 'Sunday', '11:00:00', '12:30:00', 'قاعة 202', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(5, 2, 2, 2, 'SESS-PHYS-002', 'Wednesday', '11:00:00', '12:30:00', 'قاعة 202', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(6, 3, 3, 3, 'SESS-MATH-001', 'Monday', '10:00:00', '11:30:00', NULL, 1, 'https://meet.google.com/abc-defg-hij', 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(7, 3, 3, 3, 'SESS-MATH-002', 'Thursday', '10:00:00', '11:30:00', NULL, 1, 'https://meet.google.com/abc-defg-hij', 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(8, 4, 4, 4, 'SESS-BIO-001', 'Tuesday', '13:00:00', '14:30:00', 'معمل الأحياء', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(9, 5, 5, 5, 'SESS-ARAB-001', 'Monday', '08:00:00', '09:30:00', 'قاعة 305', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(10, 6, 6, 6, 'SESS-ENG-001', 'Wednesday', '14:00:00', '15:30:00', NULL, 1, 'https://zoom.us/j/123456789', 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(11, 1, 1, NULL, 'SESS-CHEM-FREE', 'Saturday', '16:00:00', '17:00:00', 'قاعة 101', 0, NULL, 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(12, 2, 2, NULL, 'SESS-PHYS-FREE', 'Friday', '16:00:00', '17:00:00', NULL, 1, 'https://meet.google.com/xyz-abcd-efg', 'الثانوية العامة', 1, '2026-08-12 11:58:09'),
(101, 201, 1, 201, 'CLS-201', 'Wednesday', '12:00:00', '15:00:00', '', 1, 'https://meet.google.com/naj-eh000', 'Secondary', 1, '2026-08-01 14:00:00'),
(102, 201, 1, 202, 'CLS-202', 'Monday', '15:00:00', '16:00:00', 'Room 2', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(103, 202, 2, 203, 'CLS-203', 'Tuesday', '16:00:00', '17:00:00', NULL, 1, 'https://meet.google.com/naj-eh002', 'Secondary', 1, '2026-08-01 14:00:00'),
(104, 202, 2, 204, 'CLS-204', 'Wednesday', '17:00:00', '18:00:00', 'Room 4', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(105, 203, 3, 205, 'CLS-205', 'Thursday', '18:00:00', '19:00:00', NULL, 1, 'https://meet.google.com/naj-eh004', 'Secondary', 1, '2026-08-01 14:00:00'),
(106, 203, 3, 206, 'CLS-206', 'Saturday', '19:00:00', '20:00:00', 'Room 1', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(107, 204, 4, 207, 'CLS-207', 'Sunday', '14:00:00', '15:00:00', NULL, 1, 'https://meet.google.com/naj-eh006', 'Secondary', 1, '2026-08-01 14:00:00'),
(108, 204, 4, 208, 'CLS-208', 'Monday', '15:00:00', '16:00:00', 'Room 3', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(109, 205, 5, 209, 'CLS-209', 'Tuesday', '16:00:00', '17:00:00', NULL, 1, 'https://meet.google.com/naj-eh008', 'Secondary', 1, '2026-08-01 14:00:00'),
(110, 205, 5, 210, 'CLS-210', 'Wednesday', '17:00:00', '18:00:00', 'Room 5', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(111, 206, 6, 211, 'CLS-211', 'Thursday', '18:00:00', '19:00:00', NULL, 1, 'https://meet.google.com/naj-eh010', 'Secondary', 1, '2026-08-01 14:00:00'),
(112, 206, 6, 212, 'CLS-212', 'Saturday', '19:00:00', '20:00:00', 'Room 2', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(113, 207, 7, 213, 'CLS-213', 'Sunday', '14:00:00', '15:00:00', NULL, 1, 'https://meet.google.com/naj-eh012', 'Secondary', 1, '2026-08-01 14:00:00'),
(114, 207, 7, 214, 'CLS-214', 'Monday', '15:00:00', '16:00:00', 'Room 4', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(115, 208, 8, 215, 'CLS-215', 'Tuesday', '16:00:00', '17:00:00', NULL, 1, 'https://meet.google.com/naj-eh014', 'Secondary', 1, '2026-08-01 14:00:00'),
(116, 208, 8, 216, 'CLS-216', 'Wednesday', '17:00:00', '18:00:00', 'Room 1', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(117, 209, 9, 217, 'CLS-217', 'Thursday', '18:00:00', '19:00:00', NULL, 1, 'https://meet.google.com/naj-eh016', 'Secondary', 1, '2026-08-01 14:00:00'),
(118, 209, 9, 218, 'CLS-218', 'Saturday', '19:00:00', '20:00:00', 'Room 3', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(119, 210, 10, 219, 'CLS-219', 'Sunday', '14:00:00', '15:00:00', NULL, 1, 'https://meet.google.com/naj-eh018', 'Secondary', 1, '2026-08-01 14:00:00'),
(120, 210, 10, 220, 'CLS-220', 'Monday', '15:00:00', '16:00:00', 'Room 5', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(121, 211, 11, 221, 'CLS-221', 'Tuesday', '16:00:00', '17:00:00', NULL, 1, 'https://meet.google.com/naj-eh020', 'Secondary', 1, '2026-08-01 14:00:00'),
(122, 211, 11, 222, 'CLS-222', 'Wednesday', '17:00:00', '18:00:00', 'Room 2', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00'),
(123, 212, 15, 223, 'CLS-223', 'Thursday', '18:00:00', '19:00:00', NULL, 1, 'https://meet.google.com/naj-eh022', 'Secondary', 1, '2026-08-01 14:00:00'),
(124, 212, 15, 224, 'CLS-224', 'Saturday', '19:00:00', '20:00:00', 'Room 4', 0, NULL, 'Secondary', 1, '2026-08-01 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `code_redemptions_log`
--

CREATE TABLE `code_redemptions_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_code_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `student_subscription_id` bigint(20) UNSIGNED NOT NULL,
  `redeemed_at` timestamp NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `code_redemptions_log`
--

INSERT INTO `code_redemptions_log` (`id`, `subscription_code_id`, `student_id`, `student_subscription_id`, `redeemed_at`, `ip_address`) VALUES
(1001, 101, 1001, 1001, '2026-06-01 04:10:00', '197.51.100.1'),
(1002, 102, 1002, 1002, '2026-06-02 04:10:00', '197.51.100.2'),
(1003, 103, 1003, 1003, '2026-06-03 04:10:00', '197.51.100.3'),
(1004, 104, 1004, 1004, '2026-06-04 04:10:00', '197.51.100.4'),
(1005, 105, 1005, 1005, '2026-06-05 04:10:00', '197.51.100.5'),
(1006, 106, 1006, 1006, '2026-06-06 04:10:00', '197.51.100.6'),
(1007, 107, 1007, 1007, '2026-06-07 04:10:00', '197.51.100.7'),
(1008, 108, 1008, 1008, '2026-06-08 04:10:00', '197.51.100.8'),
(1009, 109, 1009, 1009, '2026-06-09 04:10:00', '197.51.100.9'),
(1010, 110, 1010, 1010, '2026-06-10 04:10:00', '197.51.100.10'),
(1011, 111, 1011, 1011, '2026-06-11 04:10:00', '197.51.100.11'),
(1012, 112, 1012, 1012, '2026-06-12 04:10:00', '197.51.100.12'),
(1013, 101, 1013, 1013, '2026-06-13 04:10:00', '197.51.100.13'),
(1014, 102, 1014, 1014, '2026-06-14 04:10:00', '197.51.100.14'),
(1015, 103, 1015, 1015, '2026-06-15 04:10:00', '197.51.100.15'),
(1016, 104, 1016, 1016, '2026-06-16 04:10:00', '197.51.100.16'),
(1017, 105, 1017, 1017, '2026-06-17 04:10:00', '197.51.100.17'),
(1018, 106, 1018, 1018, '2026-06-18 04:10:00', '197.51.100.18'),
(1019, 107, 1019, 1019, '2026-06-19 04:10:00', '197.51.100.19'),
(1020, 108, 1020, 1020, '2026-06-20 04:10:00', '197.51.100.20'),
(1021, 109, 1021, 1021, '2026-06-21 04:10:00', '197.51.100.21'),
(1022, 110, 1022, 1022, '2026-06-22 04:10:00', '197.51.100.22'),
(1023, 111, 1023, 1023, '2026-06-23 04:10:00', '197.51.100.23'),
(1024, 112, 1024, 1024, '2026-06-24 04:10:00', '197.51.100.24'),
(1025, 101, 1025, 1025, '2026-06-25 04:10:00', '197.51.100.25'),
(1026, 102, 1026, 1026, '2026-06-26 04:10:00', '197.51.100.26'),
(1027, 103, 1027, 1027, '2026-06-27 04:10:00', '197.51.100.27'),
(1028, 104, 1028, 1028, '2026-06-28 04:10:00', '197.51.100.28'),
(1029, 105, 1029, 1029, '2026-06-29 04:10:00', '197.51.100.29'),
(1030, 106, 1030, 1030, '2026-06-30 04:10:00', '197.51.100.30'),
(1031, 107, 1031, 1031, '2026-07-01 04:10:00', '197.51.100.31'),
(1032, 108, 1032, 1032, '2026-07-02 04:10:00', '197.51.100.32'),
(1033, 109, 1033, 1033, '2026-07-03 04:10:00', '197.51.100.33'),
(1034, 110, 1034, 1034, '2026-07-04 04:10:00', '197.51.100.34'),
(1035, 111, 1035, 1035, '2026-07-05 04:10:00', '197.51.100.35'),
(1036, 112, 1036, 1036, '2026-07-06 04:10:00', '197.51.100.36'),
(1037, 101, 1037, 1037, '2026-07-07 04:10:00', '197.51.100.37'),
(1038, 102, 1038, 1038, '2026-07-08 04:10:00', '197.51.100.38'),
(1039, 103, 1039, 1039, '2026-07-09 04:10:00', '197.51.100.39'),
(1040, 104, 1040, 1040, '2026-07-10 04:10:00', '197.51.100.40'),
(1041, 105, 1041, 1041, '2026-07-11 04:10:00', '197.51.100.41'),
(1042, 106, 1042, 1042, '2026-07-12 04:10:00', '197.51.100.42'),
(1043, 107, 1043, 1043, '2026-07-13 04:10:00', '197.51.100.43'),
(1044, 108, 1044, 1044, '2026-07-14 04:10:00', '197.51.100.44'),
(1045, 109, 1045, 1045, '2026-07-15 04:10:00', '197.51.100.45'),
(1046, 110, 1046, 1046, '2026-06-01 04:10:00', '197.51.100.46'),
(1047, 111, 1047, 1047, '2026-06-02 04:10:00', '197.51.100.47'),
(1048, 112, 1048, 1048, '2026-06-03 04:10:00', '197.51.100.48'),
(1049, 101, 1049, 1049, '2026-06-04 04:10:00', '197.51.100.49'),
(1050, 102, 1050, 1050, '2026-06-05 04:10:00', '197.51.100.50'),
(1051, 103, 1051, 1051, '2026-06-06 04:10:00', '197.51.100.51'),
(1052, 104, 1052, 1052, '2026-06-07 04:10:00', '197.51.100.52'),
(1053, 105, 1053, 1053, '2026-06-08 04:10:00', '197.51.100.53'),
(1054, 106, 1054, 1054, '2026-06-09 04:10:00', '197.51.100.54'),
(1055, 107, 1055, 1055, '2026-06-10 04:10:00', '197.51.100.55'),
(1056, 108, 1056, 1056, '2026-06-11 04:10:00', '197.51.100.56'),
(1057, 109, 1057, 1057, '2026-06-12 04:10:00', '197.51.100.57'),
(1058, 110, 1058, 1058, '2026-06-13 04:10:00', '197.51.100.58'),
(1059, 111, 1059, 1059, '2026-06-14 04:10:00', '197.51.100.59'),
(1060, 112, 1060, 1060, '2026-06-15 04:10:00', '197.51.100.60'),
(1061, 112, 1025, 1066, '2026-08-12 15:21:07', '156.221.84.221');

-- --------------------------------------------------------

--
-- Table structure for table `consultation_slots`
--

CREATE TABLE `consultation_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `meeting_type` enum('offline','video_call','chat','online') NOT NULL DEFAULT 'video_call',
  `slot_date` date NOT NULL,
  `slot_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booked_at` timestamp NULL DEFAULT NULL,
  `status` enum('available','pending','approved','rejected') NOT NULL DEFAULT 'available',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consultation_slots`
--

INSERT INTO `consultation_slots` (`id`, `teacher_id`, `subject_id`, `meeting_type`, `slot_date`, `slot_time`, `notes`, `is_booked`, `student_id`, `booked_at`, `status`, `is_active`, `created_at`) VALUES
(101, 201, 1, 'chat', '2026-08-12', '17:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1001, '2026-08-10 14:30:00', 'approved', 1, '2026-08-10 13:00:00'),
(102, 201, 1, 'video_call', '2026-08-13', '18:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1025, '2026-08-10 13:55:10', 'approved', 1, '2026-08-10 13:00:00'),
(103, 202, 2, 'video_call', '2026-08-14', '19:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1025, '2026-08-10 14:07:03', 'pending', 1, '2026-08-10 13:00:00'),
(104, 202, 2, 'chat', '2026-08-15', '20:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1025, '2026-08-12 14:50:17', 'pending', 1, '2026-08-10 13:00:00'),
(105, 203, 3, 'video_call', '2026-08-16', '17:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1005, '2026-08-10 14:30:00', 'approved', 1, '2026-08-10 13:00:00'),
(106, 203, 3, 'video_call', '2026-08-17', '18:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(107, 204, 4, 'chat', '2026-08-18', '19:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(108, 204, 4, 'video_call', '2026-08-19', '20:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(109, 205, 5, 'video_call', '2026-08-20', '17:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1009, '2026-08-10 14:30:00', 'approved', 1, '2026-08-10 13:00:00'),
(110, 205, 5, 'chat', '2026-08-21', '18:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(111, 206, 6, 'video_call', '2026-08-22', '19:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(112, 206, 6, 'video_call', '2026-08-23', '20:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(113, 207, 7, 'chat', '2026-08-12', '17:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1013, '2026-08-10 14:30:00', 'approved', 1, '2026-08-10 13:00:00'),
(114, 207, 7, 'video_call', '2026-08-13', '18:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(115, 208, 8, 'video_call', '2026-08-14', '19:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1025, '2026-08-10 14:07:27', 'pending', 1, '2026-08-10 13:00:00'),
(116, 208, 8, 'chat', '2026-08-15', '20:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1025, '2026-08-10 14:10:11', 'pending', 1, '2026-08-10 13:00:00'),
(117, 209, 9, 'video_call', '2026-08-16', '17:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1017, '2026-08-10 14:30:00', 'approved', 1, '2026-08-10 13:00:00'),
(118, 209, 9, 'video_call', '2026-08-17', '18:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(119, 210, 10, 'chat', '2026-08-18', '19:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(120, 210, 10, 'video_call', '2026-08-19', '20:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(121, 211, 11, 'video_call', '2026-08-20', '17:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 1, 1021, '2026-08-10 14:30:00', 'approved', 1, '2026-08-10 13:00:00'),
(122, 211, 11, 'chat', '2026-08-21', '18:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(123, 212, 15, 'video_call', '2026-08-22', '19:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00'),
(124, 212, 15, 'video_call', '2026-08-23', '20:30:00', 'مراجعة فردية لمدة 30 دقيقة لمناقشة نقاط الضعف.', 0, NULL, NULL, 'available', 1, '2026-08-10 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `academic_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `slug` varchar(190) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'recorded',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image` varchar(500) DEFAULT NULL,
  `lessons_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_website` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_type` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `created_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `what_you_will_learn` text DEFAULT NULL,
  `video_preview_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `category_id`, `subject_id`, `academic_year_id`, `teacher_id`, `slug`, `type`, `price`, `image`, `lessons_count`, `is_active`, `show_on_website`, `created_by_type`, `created_by_id`, `created_at`, `updated_at`, `what_you_will_learn`, `video_preview_url`) VALUES
(201, 3, 1, 11, 201, 'arabic-language-grade-11', 'english', 950.00, 'https://loremflickr.com/1200/800/arabic,books,language?lock=201', 21, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-12 11:39:09', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=VZZX2IXeet8'),
(202, 3, 1, 12, 201, 'arabic-language-grade-12', 'english', 700.00, 'https://loremflickr.com/1200/800/arabic,books,language?lock=202', 21, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-12 11:39:01', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=VZZX2IXeet8'),
(203, 3, 2, 11, 202, 'english-language-grade-11', 'recorded', 800.00, 'https://loremflickr.com/1200/800/english,language,books?lock=203', 24, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=O-6q-siuMik'),
(204, 3, 2, 12, 202, 'english-language-grade-12', 'recorded', 950.00, 'https://loremflickr.com/1200/800/english,language,books?lock=204', 24, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=O-6q-siuMik'),
(205, 2, 3, 11, 203, 'mathematics-grade-11', 'recorded', 650.00, 'https://loremflickr.com/1200/800/mathematics,geometry,education?lock=205', 27, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=u8im9mTcSlk'),
(206, 2, 3, 12, 203, 'mathematics-grade-12', 'recorded', 800.00, 'https://loremflickr.com/1200/800/mathematics,calculus,education?lock=206', 27, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=u8im9mTcSlk'),
(207, 1, 4, 11, 204, 'physics-grade-11', 'recorded', 900.00, 'https://loremflickr.com/1200/800/physics,laboratory,science?lock=207', 30, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=wHC245cVdHw'),
(208, 1, 4, 12, 204, 'physics-grade-12', 'recorded', 650.00, 'https://loremflickr.com/1200/800/physics,electricity,science?lock=208', 30, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=wHC245cVdHw'),
(209, 1, 5, 11, 205, 'chemistry-grade-11', 'recorded', 750.00, 'https://loremflickr.com/1200/800/chemistry,laboratory,science?lock=209', 18, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=KaxxANDh55k'),
(210, 1, 5, 12, 205, 'chemistry-grade-12', 'recorded', 900.00, 'https://loremflickr.com/1200/800/chemistry,molecules,science?lock=210', 18, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=KaxxANDh55k'),
(211, 1, 6, 11, 206, 'biology-grade-11', 'recorded', 1000.00, 'https://loremflickr.com/1200/800/biology,microscope,science?lock=211', 21, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=_wmwmMeF3pE'),
(212, 1, 6, 12, 206, 'biology-grade-12', 'recorded', 750.00, 'https://loremflickr.com/1200/800/biology,dna,science?lock=212', 21, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=_wmwmMeF3pE'),
(213, 4, 7, 11, 207, 'history-grade-11', 'recorded', 850.00, 'https://loremflickr.com/1200/800/history,books,education?lock=213', 24, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=5Zo0j7yj23E'),
(214, 4, 7, 12, 207, 'history-grade-12', 'recorded', 1000.00, 'https://loremflickr.com/1200/800/history,archive,education?lock=214', 24, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=5Zo0j7yj23E'),
(215, 4, 8, 11, 208, 'geography-grade-11', 'recorded', 700.00, 'https://loremflickr.com/1200/800/geography,map,education?lock=215', 27, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU'),
(216, 4, 8, 12, 208, 'geography-grade-12', 'recorded', 850.00, 'https://loremflickr.com/1200/800/geography,globe,education?lock=216', 27, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU'),
(217, 4, 9, 11, 209, 'philosophy-logic-grade-11', 'recorded', 950.00, 'https://loremflickr.com/1200/800/philosophy,books,thinking?lock=217', 30, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=O-6q-siuMik'),
(218, 4, 9, 12, 209, 'philosophy-logic-grade-12', 'recorded', 700.00, 'https://loremflickr.com/1200/800/philosophy,logic,books?lock=218', 30, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=O-6q-siuMik'),
(219, 5, 10, 11, 210, 'computer-science-grade-11', 'recorded', 800.00, 'https://loremflickr.com/1200/800/computer,programming,coding?lock=219', 18, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=rJCRGiEidZ4'),
(220, 5, 10, 12, 210, 'computer-science-grade-12', 'recorded', 950.00, 'https://loremflickr.com/1200/800/computer,code,technology?lock=220', 18, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=rJCRGiEidZ4'),
(221, 3, 11, 11, 211, 'french-language-grade-11', 'recorded', 650.00, 'https://loremflickr.com/1200/800/french,language,books?lock=221', 21, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=O-6q-siuMik'),
(222, 3, 11, 12, 211, 'french-language-grade-12', 'recorded', 800.00, 'https://loremflickr.com/1200/800/french,language,education?lock=222', 21, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=O-6q-siuMik'),
(223, 1, 15, 11, 212, 'integrated-science-grade-11', 'recorded', 850.00, 'https://loremflickr.com/1200/800/science,laboratory,education?lock=223', 18, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=_wmwmMeF3pE'),
(224, 1, 15, 12, 212, 'integrated-science-grade-12', 'recorded', 1000.00, 'uploads/avatars/img_6a79d3f05f3de6.44037406.png', 18, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 13:36:48', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]', 'https://www.youtube.com/watch?v=_wmwmMeF3pE');

-- --------------------------------------------------------

--
-- Table structure for table `courses_translations`
--

CREATE TABLE `courses_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `what_you_will_learn` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses_translations`
--

INSERT INTO `courses_translations` (`id`, `course_id`, `language_id`, `name`, `description`, `is_active`, `created_at`, `updated_at`, `what_you_will_learn`) VALUES
(1001, 201, 1, 'اللغة العربية - الصف الثاني الثانوي', 'كورس شامل في اللغة العربية للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1002, 201, 2, 'Arabic Language - Secondary Grade 2', 'A complete Arabic Language course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1003, 202, 1, 'اللغة العربية - الصف الثالث الثانوي', 'كورس شامل في اللغة العربية للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1004, 202, 2, 'Arabic Language - Secondary Grade 3', 'A complete Arabic Language course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1005, 203, 1, 'اللغة الإنجليزية - الصف الثاني الثانوي', 'كورس شامل في اللغة الإنجليزية للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1006, 203, 2, 'English Language - Secondary Grade 2', 'A complete English Language course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1007, 204, 1, 'اللغة الإنجليزية - الصف الثالث الثانوي', 'كورس شامل في اللغة الإنجليزية للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1008, 204, 2, 'English Language - Secondary Grade 3', 'A complete English Language course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1009, 205, 1, 'الرياضيات - الصف الثاني الثانوي', 'كورس شامل في الرياضيات للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1010, 205, 2, 'Mathematics - Secondary Grade 2', 'A complete Mathematics course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1011, 206, 1, 'الرياضيات - الصف الثالث الثانوي', 'كورس شامل في الرياضيات للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1012, 206, 2, 'Mathematics - Secondary Grade 3', 'A complete Mathematics course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1013, 207, 1, 'الفيزياء - الصف الثاني الثانوي', 'كورس شامل في الفيزياء للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1014, 207, 2, 'Physics - Secondary Grade 2', 'A complete Physics course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1015, 208, 1, 'الفيزياء - الصف الثالث الثانوي', 'كورس شامل في الفيزياء للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1016, 208, 2, 'Physics - Secondary Grade 3', 'A complete Physics course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1017, 209, 1, 'الكيمياء - الصف الثاني الثانوي', 'كورس شامل في الكيمياء للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1018, 209, 2, 'Chemistry - Secondary Grade 2', 'A complete Chemistry course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1019, 210, 1, 'الكيمياء - الصف الثالث الثانوي', 'كورس شامل في الكيمياء للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1020, 210, 2, 'Chemistry - Secondary Grade 3', 'A complete Chemistry course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1021, 211, 1, 'الأحياء - الصف الثاني الثانوي', 'كورس شامل في الأحياء للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1022, 211, 2, 'Biology - Secondary Grade 2', 'A complete Biology course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1023, 212, 1, 'الأحياء - الصف الثالث الثانوي', 'كورس شامل في الأحياء للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1024, 212, 2, 'Biology - Secondary Grade 3', 'A complete Biology course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1025, 213, 1, 'التاريخ - الصف الثاني الثانوي', 'كورس شامل في التاريخ للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1026, 213, 2, 'History - Secondary Grade 2', 'A complete History course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1027, 214, 1, 'التاريخ - الصف الثالث الثانوي', 'كورس شامل في التاريخ للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1028, 214, 2, 'History - Secondary Grade 3', 'A complete History course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1029, 215, 1, 'الجغرافيا - الصف الثاني الثانوي', 'كورس شامل في الجغرافيا للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1030, 215, 2, 'Geography - Secondary Grade 2', 'A complete Geography course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1031, 216, 1, 'الجغرافيا - الصف الثالث الثانوي', 'كورس شامل في الجغرافيا للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1032, 216, 2, 'Geography - Secondary Grade 3', 'A complete Geography course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1033, 217, 1, 'الفلسفة والمنطق - الصف الثاني الثانوي', 'كورس شامل في الفلسفة والمنطق للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1034, 217, 2, 'Philosophy & Logic - Secondary Grade 2', 'A complete Philosophy & Logic course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1035, 218, 1, 'الفلسفة والمنطق - الصف الثالث الثانوي', 'كورس شامل في الفلسفة والمنطق للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1036, 218, 2, 'Philosophy & Logic - Secondary Grade 3', 'A complete Philosophy & Logic course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1037, 219, 1, 'علوم الحاسب - الصف الثاني الثانوي', 'كورس شامل في علوم الحاسب للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1038, 219, 2, 'Computer Science - Secondary Grade 2', 'A complete Computer Science course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1039, 220, 1, 'علوم الحاسب - الصف الثالث الثانوي', 'كورس شامل في علوم الحاسب للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1040, 220, 2, 'Computer Science - Secondary Grade 3', 'A complete Computer Science course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1041, 221, 1, 'اللغة الفرنسية - الصف الثاني الثانوي', 'كورس شامل في اللغة الفرنسية للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1042, 221, 2, 'French Language - Secondary Grade 2', 'A complete French Language course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1043, 222, 1, 'اللغة الفرنسية - الصف الثالث الثانوي', 'كورس شامل في اللغة الفرنسية للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1044, 222, 2, 'French Language - Secondary Grade 3', 'A complete French Language course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1045, 223, 1, 'العلوم المتكاملة - الصف الثاني الثانوي', 'كورس شامل في العلوم المتكاملة للـالصف الثاني الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1046, 223, 2, 'Integrated Science - Secondary Grade 2', 'A complete Integrated Science course for Secondary Grade 2 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]'),
(1047, 224, 1, 'العلوم المتكاملة - الصف الثالث الثانوي', 'كورس شامل في العلوم المتكاملة للـالصف الثالث الثانوي، يتضمن شرحًا منظمًا وتدريبات وواجبات واختبارات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"فهم المفاهيم الأساسية بصورة مترابطة\", \"تطبيق الأفكار على أسئلة متدرجة\", \"التدرب على نماذج امتحانات ومراجعات دورية\"]'),
(1048, 224, 2, 'Integrated Science - Secondary Grade 3', 'A complete Integrated Science course for Secondary Grade 3 with structured lessons, practice, homework and assessments.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', '[\"Understand the core concepts in a connected way\", \"Apply concepts through graded practice\", \"Practice exam-style questions and periodic reviews\"]');

-- --------------------------------------------------------

--
-- Table structure for table `course_books`
--

CREATE TABLE `course_books` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `file_type` varchar(50) DEFAULT 'pdf',
  `extension` varchar(20) DEFAULT 'pdf',
  `link` text DEFAULT NULL,
  `file_path` text DEFAULT NULL,
  `order_num` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_website` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_type` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `created_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price` double DEFAULT 0,
  `cover_image` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_books`
--

INSERT INTO `course_books` (`id`, `subject_id`, `course_id`, `unit_id`, `file_type`, `extension`, `link`, `file_path`, `order_num`, `is_active`, `show_on_website`, `created_by_type`, `created_by_id`, `created_at`, `updated_at`, `price`, `cover_image`) VALUES
(401, 1, 201, 1001, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(402, 1, 202, 1004, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(403, 2, 203, 1007, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(404, 2, 204, 1010, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(405, 3, 205, 1013, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(406, 3, 206, 1016, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(407, 4, 207, 1019, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(408, 4, 208, 1022, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(409, 5, 209, 1025, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(410, 5, 210, 1028, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(411, 6, 211, 1031, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(412, 6, 212, 1034, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(413, 7, 213, 1037, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(414, 7, 214, 1040, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(415, 8, 215, 1043, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(416, 8, 216, 1046, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(417, 9, 217, 1049, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(418, 9, 218, 1052, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(419, 10, 219, 1055, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(420, 10, 220, 1058, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(421, 11, 221, 1061, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(422, 11, 222, 1064, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(423, 15, 223, 1067, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(424, 15, 224, 1070, 'pdf', 'pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 1, 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00', 0, ''),
(425, NULL, NULL, NULL, 'pdf', 'pdf', 'https://example.com/book.pdf', '', 1, 1, 1, 'admin', 1, '2026-08-11 11:51:00', '2026-08-11 11:51:00', 0, ''),
(426, NULL, NULL, NULL, 'pdf', 'pdf', 'https://example.com/book.png', '', 1, 1, 1, 'admin', 1, '2026-08-11 11:51:37', '2026-08-11 11:51:37', 0, ''),
(427, NULL, NULL, NULL, 'pdf', 'pdf', 'https://example.com/book.png', '', 1, 1, 1, 'admin', 1, '2026-08-11 11:57:19', '2026-08-11 11:57:19', 0, ''),
(428, NULL, NULL, NULL, 'pdf', 'pdf', '', '', 1, 1, 1, 'admin', 1, '2026-08-11 12:19:49', '2026-08-11 12:19:49', 0, 'https://example.com/book.png'),
(429, NULL, NULL, NULL, 'pdf', 'pdf', 'https://example.com/book.pdf', '', 1, 1, 1, 'admin', 1, '2026-08-11 12:24:43', '2026-08-11 12:24:43', 0, 'https://example.com/book.png'),
(430, NULL, NULL, NULL, 'pdf', 'pdf', 'uploads/avatars/img_6a7b14e18f76e7.12507093.html', '', 1, 1, 1, 'admin', 1, '2026-08-11 12:26:09', '2026-08-11 12:26:09', 0, 'uploads/avatars/img_6a7b14e18f4624.34926028.png'),
(431, NULL, NULL, 1, 'pdf', 'pdf', 'https://example.com/book.pdf', '', 1, 1, 1, 'admin', 201, '2026-08-12 12:20:34', '2026-08-12 12:20:34', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `course_books_translations`
--

CREATE TABLE `course_books_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_books_translations`
--

INSERT INTO `course_books_translations` (`id`, `book_id`, `language_id`, `title`, `description`) VALUES
(9001, 401, 1, 'مرجع إثرائي مفتوح: Algebra & Trigonometry Lecture Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى اللغة العربية.'),
(9002, 401, 2, 'Open supplementary reference: Algebra & Trigonometry Lecture Notes', 'A public PDF used as an additional reference alongside the Arabic Language course.'),
(9003, 402, 1, 'مرجع إثرائي مفتوح: AP Physics Lab Manual', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى اللغة العربية.'),
(9004, 402, 2, 'Open supplementary reference: AP Physics Lab Manual', 'A public PDF used as an additional reference alongside the Arabic Language course.'),
(9005, 403, 1, 'مرجع إثرائي مفتوح: Elementary Algebra 2e', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى اللغة الإنجليزية.'),
(9006, 403, 2, 'Open supplementary reference: Elementary Algebra 2e', 'A public PDF used as an additional reference alongside the English Language course.'),
(9007, 404, 1, 'مرجع إثرائي مفتوح: Introductory Statistics 2e', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى اللغة الإنجليزية.'),
(9008, 404, 2, 'Open supplementary reference: Introductory Statistics 2e', 'A public PDF used as an additional reference alongside the English Language course.'),
(9009, 405, 1, 'مرجع إثرائي مفتوح: Chemistry 2e Release Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الرياضيات.'),
(9010, 405, 2, 'Open supplementary reference: Chemistry 2e Release Notes', 'A public PDF used as an additional reference alongside the Mathematics course.'),
(9011, 406, 1, 'مرجع إثرائي مفتوح: Pharmacology for Nurses', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الرياضيات.'),
(9012, 406, 2, 'Open supplementary reference: Pharmacology for Nurses', 'A public PDF used as an additional reference alongside the Mathematics course.'),
(9013, 407, 1, 'مرجع إثرائي مفتوح: College Algebra', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الفيزياء.'),
(9014, 407, 2, 'Open supplementary reference: College Algebra', 'A public PDF used as an additional reference alongside the Physics course.'),
(9015, 408, 1, 'مرجع إثرائي مفتوح: Algebra & Trigonometry Lecture Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الفيزياء.'),
(9016, 408, 2, 'Open supplementary reference: Algebra & Trigonometry Lecture Notes', 'A public PDF used as an additional reference alongside the Physics course.'),
(9017, 409, 1, 'مرجع إثرائي مفتوح: Psychology 2e', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الكيمياء.'),
(9018, 409, 2, 'Open supplementary reference: Psychology 2e', 'A public PDF used as an additional reference alongside the Chemistry course.'),
(9019, 410, 1, 'مرجع إثرائي مفتوح: Elementary Algebra 2e', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الكيمياء.'),
(9020, 410, 2, 'Open supplementary reference: Elementary Algebra 2e', 'A public PDF used as an additional reference alongside the Chemistry course.'),
(9021, 411, 1, 'مرجع إثرائي مفتوح: Biology 2e Transition Guide', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الأحياء.'),
(9022, 411, 2, 'Open supplementary reference: Biology 2e Transition Guide', 'A public PDF used as an additional reference alongside the Biology course.'),
(9023, 412, 1, 'مرجع إثرائي مفتوح: Chemistry 2e Release Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الأحياء.'),
(9024, 412, 2, 'Open supplementary reference: Chemistry 2e Release Notes', 'A public PDF used as an additional reference alongside the Biology course.'),
(9025, 413, 1, 'مرجع إثرائي مفتوح: University Physics Volume 3 Release Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى التاريخ.'),
(9026, 413, 2, 'Open supplementary reference: University Physics Volume 3 Release Notes', 'A public PDF used as an additional reference alongside the History course.'),
(9027, 414, 1, 'مرجع إثرائي مفتوح: College Algebra', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى التاريخ.'),
(9028, 414, 2, 'Open supplementary reference: College Algebra', 'A public PDF used as an additional reference alongside the History course.'),
(9029, 415, 1, 'مرجع إثرائي مفتوح: AP Physics Lab Manual', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الجغرافيا.'),
(9030, 415, 2, 'Open supplementary reference: AP Physics Lab Manual', 'A public PDF used as an additional reference alongside the Geography course.'),
(9031, 416, 1, 'مرجع إثرائي مفتوح: Psychology 2e', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الجغرافيا.'),
(9032, 416, 2, 'Open supplementary reference: Psychology 2e', 'A public PDF used as an additional reference alongside the Geography course.'),
(9033, 417, 1, 'مرجع إثرائي مفتوح: Introductory Statistics 2e', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الفلسفة والمنطق.'),
(9034, 417, 2, 'Open supplementary reference: Introductory Statistics 2e', 'A public PDF used as an additional reference alongside the Philosophy & Logic course.'),
(9035, 418, 1, 'مرجع إثرائي مفتوح: Biology 2e Transition Guide', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى الفلسفة والمنطق.'),
(9036, 418, 2, 'Open supplementary reference: Biology 2e Transition Guide', 'A public PDF used as an additional reference alongside the Philosophy & Logic course.'),
(9037, 419, 1, 'مرجع إثرائي مفتوح: Pharmacology for Nurses', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى علوم الحاسب.'),
(9038, 419, 2, 'Open supplementary reference: Pharmacology for Nurses', 'A public PDF used as an additional reference alongside the Computer Science course.'),
(9039, 420, 1, 'مرجع إثرائي مفتوح: University Physics Volume 3 Release Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى علوم الحاسب.'),
(9040, 420, 2, 'Open supplementary reference: University Physics Volume 3 Release Notes', 'A public PDF used as an additional reference alongside the Computer Science course.'),
(9041, 421, 1, 'مرجع إثرائي مفتوح: Algebra & Trigonometry Lecture Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى اللغة الفرنسية.'),
(9042, 421, 2, 'Open supplementary reference: Algebra & Trigonometry Lecture Notes', 'A public PDF used as an additional reference alongside the French Language course.'),
(9043, 422, 1, 'مرجع إثرائي مفتوح: AP Physics Lab Manual', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى اللغة الفرنسية.'),
(9044, 422, 2, 'Open supplementary reference: AP Physics Lab Manual', 'A public PDF used as an additional reference alongside the French Language course.'),
(9045, 423, 1, 'مرجع إثرائي مفتوح: Chemistry 2e Release Notes', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى العلوم المتكاملة.'),
(9046, 423, 2, 'Open supplementary reference: Chemistry 2e Release Notes', 'A public PDF used as an additional reference alongside the Integrated Science course.'),
(9047, 424, 1, 'مرجع إثرائي مفتوح: Pharmacology for Nurses', 'ملف PDF عام ومفتوح للاستخدام كمرجع إضافي بجانب محتوى العلوم المتكاملة.'),
(9048, 424, 2, 'Open supplementary reference: Pharmacology for Nurses', 'A public PDF used as an additional reference alongside the Integrated Science course.'),
(9049, 425, 1, 'كتاب الوحدة الأولى', ''),
(9050, 425, 2, 'كتاب الوحدة الأولى', ''),
(9051, 426, 1, 'كتاب الوحدة الأولى', ''),
(9052, 426, 2, 'كتاب الوحدة الأولى', ''),
(9053, 427, 1, 'كتاب الوحدة الأولى', ''),
(9054, 427, 2, 'كتاب الوحدة الأولى', ''),
(9055, 428, 1, 'كتاب الوحدة الأولى', ''),
(9056, 428, 2, 'كتاب الوحدة الأولى', ''),
(9057, 429, 1, 'كتاب الوحدة الأولى', ''),
(9058, 429, 2, 'كتاب الوحدة الأولى', ''),
(9059, 430, 1, 'weiruoiewr', ''),
(9060, 430, 2, 'weiruoiewr', ''),
(9061, 431, 1, 'ملزمة الوحدة', ''),
(9062, 431, 2, 'ملزمة الوحدة', '');

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_categories`
--

INSERT INTO `course_categories` (`id`, `code`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'natural-sciences', 'https://loremflickr.com/1000/700/science,laboratory,education?lock=101', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'mathematics', 'https://loremflickr.com/1000/700/mathematics,geometry,education?lock=102', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'languages-literature', 'https://loremflickr.com/1000/700/language,books,education?lock=103', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 'humanities-philosophy', 'https://loremflickr.com/1000/700/history,philosophy,books?lock=104', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 'technology', 'https://loremflickr.com/1000/700/computer,technology,coding?lock=105', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 'exam-revision', 'https://loremflickr.com/1000/700/exam,study,education?lock=106', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 'ground-science', 'uploads/avatars/img_6a7ad6868d5bd2.16878557.webp', 1, '2026-08-11 08:00:06', '2026-08-11 08:00:06');

-- --------------------------------------------------------

--
-- Table structure for table `course_categories_translations`
--

CREATE TABLE `course_categories_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_category_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_categories_translations`
--

INSERT INTO `course_categories_translations` (`id`, `course_category_id`, `language_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'العلوم الطبيعية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Natural Sciences', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'الرياضيات', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'Mathematics', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'اللغات والآداب', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Languages & Literature', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 4, 1, 'العلوم الإنسانية والفلسفة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 4, 2, 'Humanities & Philosophy', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 5, 1, 'التكنولوجيا وعلوم الحاسب', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 5, 2, 'Technology & Computer Science', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 6, 1, 'المراجعات والامتحانات', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 6, 2, 'Exam Revision', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(13, 7, 1, 'علوم الأرض', 1, '2026-08-11 08:00:06', '2026-08-11 08:00:06'),
(14, 7, 2, 'Ground Science', 1, '2026-08-11 08:00:06', '2026-08-11 08:00:06');

-- --------------------------------------------------------

--
-- Table structure for table `course_exams`
--

CREATE TABLE `course_exams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `total_marks` int(11) NOT NULL DEFAULT 100,
  `pass_marks` int(11) NOT NULL DEFAULT 50,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_type` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `created_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_exams`
--

INSERT INTO `course_exams` (`id`, `subject_id`, `course_id`, `unit_id`, `duration_minutes`, `total_marks`, `pass_marks`, `is_active`, `created_by_type`, `created_by_id`, `created_at`, `updated_at`) VALUES
(601, 1, 201, 1003, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(602, 1, 202, 1006, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(603, 2, 203, 1009, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(604, 2, 204, 1012, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(605, 3, 205, 1015, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(606, 3, 206, 1018, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(607, 4, 207, 1021, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(608, 4, 208, 1024, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(609, 5, 209, 1027, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(610, 5, 210, 1030, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(611, 6, 211, 1033, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(612, 6, 212, 1036, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(613, 7, 213, 1039, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(614, 7, 214, 1042, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(615, 8, 215, 1045, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(616, 8, 216, 1048, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(617, 9, 217, 1051, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(618, 9, 218, 1054, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(619, 10, 219, 1057, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(620, 10, 220, 1060, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(621, 11, 221, 1063, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(622, 11, 222, 1066, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(623, 15, 223, 1069, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(624, 15, 224, 1072, 45, 10, 5, 1, 'admin', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `course_exams_translations`
--

CREATE TABLE `course_exams_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_exams_translations`
--

INSERT INTO `course_exams_translations` (`id`, `exam_id`, `language_id`, `title`, `description`) VALUES
(11001, 601, 1, 'اختبار اللغة العربية - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11002, 601, 2, 'Arabic Language Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11003, 602, 1, 'اختبار اللغة العربية - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11004, 602, 2, 'Arabic Language Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11005, 603, 1, 'اختبار اللغة الإنجليزية - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11006, 603, 2, 'English Language Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11007, 604, 1, 'اختبار اللغة الإنجليزية - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11008, 604, 2, 'English Language Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11009, 605, 1, 'اختبار الرياضيات - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11010, 605, 2, 'Mathematics Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11011, 606, 1, 'اختبار الرياضيات - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11012, 606, 2, 'Mathematics Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11013, 607, 1, 'اختبار الفيزياء - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11014, 607, 2, 'Physics Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11015, 608, 1, 'اختبار الفيزياء - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11016, 608, 2, 'Physics Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11017, 609, 1, 'اختبار الكيمياء - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11018, 609, 2, 'Chemistry Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11019, 610, 1, 'اختبار الكيمياء - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11020, 610, 2, 'Chemistry Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11021, 611, 1, 'اختبار الأحياء - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11022, 611, 2, 'Biology Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11023, 612, 1, 'اختبار الأحياء - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11024, 612, 2, 'Biology Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11025, 613, 1, 'اختبار التاريخ - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11026, 613, 2, 'History Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11027, 614, 1, 'اختبار التاريخ - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11028, 614, 2, 'History Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11029, 615, 1, 'اختبار الجغرافيا - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11030, 615, 2, 'Geography Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11031, 616, 1, 'اختبار الجغرافيا - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11032, 616, 2, 'Geography Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11033, 617, 1, 'اختبار الفلسفة والمنطق - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11034, 617, 2, 'Philosophy & Logic Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11035, 618, 1, 'اختبار الفلسفة والمنطق - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11036, 618, 2, 'Philosophy & Logic Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11037, 619, 1, 'اختبار علوم الحاسب - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11038, 619, 2, 'Computer Science Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11039, 620, 1, 'اختبار علوم الحاسب - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11040, 620, 2, 'Computer Science Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11041, 621, 1, 'اختبار اللغة الفرنسية - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11042, 621, 2, 'French Language Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11043, 622, 1, 'اختبار اللغة الفرنسية - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11044, 622, 2, 'French Language Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11045, 623, 1, 'اختبار العلوم المتكاملة - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11046, 623, 2, 'Integrated Science Final Unit Quiz', 'A five-question mixed-format assessment.'),
(11047, 624, 1, 'اختبار العلوم المتكاملة - الوحدة النهائية', 'اختبار شامل من 5 أسئلة متنوعة.'),
(11048, 624, 2, 'Integrated Science Final Unit Quiz', 'A five-question mixed-format assessment.');

-- --------------------------------------------------------

--
-- Table structure for table `course_ratings`
--

CREATE TABLE `course_ratings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_ratings`
--

INSERT INTO `course_ratings` (`id`, `student_id`, `course_id`, `rating`, `comment`, `is_approved`, `created_at`, `updated_at`) VALUES
(1001, 1001, 201, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1002, 1002, 202, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1003, 1003, 203, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1004, 1004, 204, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1005, 1005, 205, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1006, 1006, 206, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1007, 1007, 207, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1008, 1008, 208, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1009, 1009, 209, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1010, 1010, 210, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1011, 1011, 211, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1012, 1012, 212, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1013, 1013, 213, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1014, 1014, 214, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1015, 1015, 215, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1016, 1016, 216, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1017, 1017, 217, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1018, 1018, 218, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1019, 1019, 219, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1020, 1020, 220, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1021, 1021, 221, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1022, 1022, 222, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1023, 1023, 223, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1024, 1024, 224, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1025, 1025, 201, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1026, 1026, 202, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1027, 1027, 203, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1028, 1028, 204, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1029, 1029, 205, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1030, 1030, 206, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1031, 1031, 207, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1032, 1032, 208, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1033, 1033, 209, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1034, 1034, 210, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1035, 1035, 211, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1036, 1036, 212, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1037, 1037, 213, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1038, 1038, 214, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1039, 1039, 215, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1040, 1040, 216, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1041, 1041, 217, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1042, 1042, 218, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1043, 1043, 219, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1044, 1044, 220, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1045, 1045, 221, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1046, 1046, 222, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1047, 1047, 223, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1048, 1048, 224, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1049, 1049, 201, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1050, 1050, 202, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1051, 1051, 203, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1052, 1052, 204, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1053, 1053, 205, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1054, 1054, 206, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1055, 1055, 207, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1056, 1056, 208, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1057, 1057, 209, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1058, 1058, 210, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1059, 1059, 211, 4, 'شرح واضح ومحتوى منظم، والتدريبات مفيدة جدًا.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1060, 1060, 212, 5, 'محتوى جيد وسهل المتابعة مع مراجعات عملية.', 1, '2026-08-09 18:00:00', '2026-08-10 12:00:00'),
(1061, 1025, 1, 5, 'شرح رائع ومستفاد جداً', 1, '2026-08-11 11:17:17', '2026-08-11 11:17:17'),
(1062, 1025, 202, 5, 'Rh tj jyj', 1, '2026-08-11 11:28:48', '2026-08-12 07:56:15');

-- --------------------------------------------------------

--
-- Table structure for table `course_teachers`
--

CREATE TABLE `course_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_teachers`
--

INSERT INTO `course_teachers` (`id`, `course_id`, `teacher_id`, `is_active`, `created_at`) VALUES
(501, 201, 201, 1, '2026-07-01 14:00:00'),
(502, 202, 201, 1, '2026-07-01 14:00:00'),
(503, 203, 202, 1, '2026-07-01 14:00:00'),
(504, 204, 202, 1, '2026-07-01 14:00:00'),
(505, 205, 203, 1, '2026-07-01 14:00:00'),
(506, 206, 203, 1, '2026-07-01 14:00:00'),
(507, 207, 204, 1, '2026-07-01 14:00:00'),
(508, 208, 204, 1, '2026-07-01 14:00:00'),
(509, 209, 205, 1, '2026-07-01 14:00:00'),
(510, 210, 205, 1, '2026-07-01 14:00:00'),
(511, 211, 206, 1, '2026-07-01 14:00:00'),
(512, 212, 206, 1, '2026-07-01 14:00:00'),
(513, 213, 207, 1, '2026-07-01 14:00:00'),
(514, 214, 207, 1, '2026-07-01 14:00:00'),
(515, 215, 208, 1, '2026-07-01 14:00:00'),
(516, 216, 208, 1, '2026-07-01 14:00:00'),
(517, 217, 209, 1, '2026-07-01 14:00:00'),
(518, 218, 209, 1, '2026-07-01 14:00:00'),
(519, 219, 210, 1, '2026-07-01 14:00:00'),
(520, 220, 210, 1, '2026-07-01 14:00:00'),
(521, 221, 211, 1, '2026-07-01 14:00:00'),
(522, 222, 211, 1, '2026-07-01 14:00:00'),
(523, 223, 212, 1, '2026-07-01 14:00:00'),
(524, 224, 212, 1, '2026-07-01 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `course_videos`
--

CREATE TABLE `course_videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `video_url` text DEFAULT NULL,
  `order_num` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_type` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `created_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_videos`
--

INSERT INTO `course_videos` (`id`, `subject_id`, `course_id`, `unit_id`, `duration`, `video_url`, `order_num`, `is_active`, `created_by_type`, `created_by_id`, `created_at`, `updated_at`) VALUES
(3001, 1, 201, 1001, '23:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3002, 1, 201, 1001, '26:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3003, 1, 201, 1002, '26:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3004, 1, 201, 1002, '29:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3005, 1, 201, 1003, '29:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3006, 1, 201, 1003, '32:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3007, 1, 202, 1004, '26:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3008, 1, 202, 1004, '29:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3009, 1, 202, 1005, '29:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3010, 1, 202, 1005, '32:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3011, 1, 202, 1006, '32:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3012, 1, 202, 1006, '35:00', 'https://www.youtube.com/watch?v=VZZX2IXeet8', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3013, 2, 203, 1007, '29:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3014, 2, 203, 1007, '32:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3015, 2, 203, 1008, '32:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3016, 2, 203, 1008, '35:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3017, 2, 203, 1009, '35:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3018, 2, 203, 1009, '38:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3019, 2, 204, 1010, '32:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3020, 2, 204, 1010, '35:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3021, 2, 204, 1011, '35:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3022, 2, 204, 1011, '38:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3023, 2, 204, 1012, '38:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3024, 2, 204, 1012, '41:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3025, 3, 205, 1013, '35:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3026, 3, 205, 1013, '38:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3027, 3, 205, 1014, '38:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3028, 3, 205, 1014, '41:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3029, 3, 205, 1015, '41:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3030, 3, 205, 1015, '20:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3031, 3, 206, 1016, '38:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3032, 3, 206, 1016, '41:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3033, 3, 206, 1017, '41:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3034, 3, 206, 1017, '20:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3035, 3, 206, 1018, '20:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3036, 3, 206, 1018, '23:00', 'https://www.youtube.com/watch?v=u8im9mTcSlk', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3037, 4, 207, 1019, '41:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3038, 4, 207, 1019, '20:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3039, 4, 207, 1020, '20:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3040, 4, 207, 1020, '23:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3041, 4, 207, 1021, '23:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3042, 4, 207, 1021, '26:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3043, 4, 208, 1022, '20:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3044, 4, 208, 1022, '23:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3045, 4, 208, 1023, '23:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3046, 4, 208, 1023, '26:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3047, 4, 208, 1024, '26:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3048, 4, 208, 1024, '29:00', 'https://www.youtube.com/watch?v=wHC245cVdHw', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3049, 5, 209, 1025, '23:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3050, 5, 209, 1025, '26:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3051, 5, 209, 1026, '26:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3052, 5, 209, 1026, '29:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3053, 5, 209, 1027, '29:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3054, 5, 209, 1027, '32:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3055, 5, 210, 1028, '26:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3056, 5, 210, 1028, '29:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3057, 5, 210, 1029, '29:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3058, 5, 210, 1029, '32:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3059, 5, 210, 1030, '32:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3060, 5, 210, 1030, '35:00', 'https://www.youtube.com/watch?v=KaxxANDh55k', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3061, 6, 211, 1031, '29:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3062, 6, 211, 1031, '32:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3063, 6, 211, 1032, '32:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3064, 6, 211, 1032, '35:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3065, 6, 211, 1033, '35:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3066, 6, 211, 1033, '38:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3067, 6, 212, 1034, '32:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3068, 6, 212, 1034, '35:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3069, 6, 212, 1035, '35:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3070, 6, 212, 1035, '38:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3071, 6, 212, 1036, '38:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3072, 6, 212, 1036, '41:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3073, 7, 213, 1037, '35:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3074, 7, 213, 1037, '38:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3075, 7, 213, 1038, '38:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3076, 7, 213, 1038, '41:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3077, 7, 213, 1039, '41:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3078, 7, 213, 1039, '20:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3079, 7, 214, 1040, '38:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3080, 7, 214, 1040, '41:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3081, 7, 214, 1041, '41:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3082, 7, 214, 1041, '20:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3083, 7, 214, 1042, '20:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3084, 7, 214, 1042, '23:00', 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3085, 8, 215, 1043, '41:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3086, 8, 215, 1043, '20:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3087, 8, 215, 1044, '20:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3088, 8, 215, 1044, '23:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3089, 8, 215, 1045, '23:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3090, 8, 215, 1045, '26:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3091, 8, 216, 1046, '20:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3092, 8, 216, 1046, '23:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3093, 8, 216, 1047, '23:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3094, 8, 216, 1047, '26:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3095, 8, 216, 1048, '26:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3096, 8, 216, 1048, '29:00', 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3097, 9, 217, 1049, '23:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3098, 9, 217, 1049, '26:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3099, 9, 217, 1050, '26:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3100, 9, 217, 1050, '29:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3101, 9, 217, 1051, '29:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3102, 9, 217, 1051, '32:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3103, 9, 218, 1052, '26:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3104, 9, 218, 1052, '29:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3105, 9, 218, 1053, '29:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3106, 9, 218, 1053, '32:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3107, 9, 218, 1054, '32:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3108, 9, 218, 1054, '35:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3109, 10, 219, 1055, '29:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3110, 10, 219, 1055, '32:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3111, 10, 219, 1056, '32:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3112, 10, 219, 1056, '35:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3113, 10, 219, 1057, '35:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3114, 10, 219, 1057, '38:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3115, 10, 220, 1058, '32:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3116, 10, 220, 1058, '35:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3117, 10, 220, 1059, '35:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3118, 10, 220, 1059, '38:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3119, 10, 220, 1060, '38:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3120, 10, 220, 1060, '41:00', 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3121, 11, 221, 1061, '35:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3122, 11, 221, 1061, '38:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3123, 11, 221, 1062, '38:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3124, 11, 221, 1062, '41:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3125, 11, 221, 1063, '41:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3126, 11, 221, 1063, '20:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3127, 11, 222, 1064, '38:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3128, 11, 222, 1064, '41:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3129, 11, 222, 1065, '41:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3130, 11, 222, 1065, '20:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3131, 11, 222, 1066, '20:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3132, 11, 222, 1066, '23:00', 'https://www.youtube.com/watch?v=O-6q-siuMik', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3133, 15, 223, 1067, '41:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3134, 15, 223, 1067, '20:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3135, 15, 223, 1068, '20:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3136, 15, 223, 1068, '23:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3137, 15, 223, 1069, '23:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3138, 15, 223, 1069, '26:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3139, 15, 224, 1070, '20:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3140, 15, 224, 1070, '23:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3141, 15, 224, 1071, '23:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3142, 15, 224, 1071, '26:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3143, 15, 224, 1072, '26:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 1, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3144, 15, 224, 1072, '29:00', 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 2, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `course_videos_translations`
--

CREATE TABLE `course_videos_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_videos_translations`
--

INSERT INTO `course_videos_translations` (`id`, `video_id`, `language_id`, `title`, `description`) VALUES
(7001, 3001, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7002, 3001, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Arabic Language.'),
(7003, 3002, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7004, 3002, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Arabic Language.'),
(7005, 3003, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7006, 3003, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Arabic Language.'),
(7007, 3004, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7008, 3004, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Arabic Language.'),
(7009, 3005, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7010, 3005, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Arabic Language.'),
(7011, 3006, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7012, 3006, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Arabic Language.'),
(7013, 3007, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7014, 3007, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Arabic Language.'),
(7015, 3008, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7016, 3008, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Arabic Language.'),
(7017, 3009, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7018, 3009, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Arabic Language.'),
(7019, 3010, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7020, 3010, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Arabic Language.'),
(7021, 3011, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7022, 3011, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Arabic Language.'),
(7023, 3012, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة العربية.'),
(7024, 3012, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Arabic Language.'),
(7025, 3013, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7026, 3013, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to English Language.'),
(7027, 3014, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7028, 3014, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to English Language.'),
(7029, 3015, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7030, 3015, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to English Language.'),
(7031, 3016, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7032, 3016, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to English Language.'),
(7033, 3017, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7034, 3017, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to English Language.'),
(7035, 3018, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7036, 3018, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to English Language.'),
(7037, 3019, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7038, 3019, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to English Language.'),
(7039, 3020, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7040, 3020, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to English Language.'),
(7041, 3021, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7042, 3021, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to English Language.'),
(7043, 3022, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7044, 3022, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to English Language.'),
(7045, 3023, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7046, 3023, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to English Language.'),
(7047, 3024, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الإنجليزية.'),
(7048, 3024, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to English Language.'),
(7049, 3025, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7050, 3025, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Mathematics.'),
(7051, 3026, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7052, 3026, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Mathematics.'),
(7053, 3027, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7054, 3027, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Mathematics.'),
(7055, 3028, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7056, 3028, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Mathematics.'),
(7057, 3029, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7058, 3029, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Mathematics.'),
(7059, 3030, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7060, 3030, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Mathematics.'),
(7061, 3031, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7062, 3031, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Mathematics.'),
(7063, 3032, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7064, 3032, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Mathematics.'),
(7065, 3033, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7066, 3033, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Mathematics.'),
(7067, 3034, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7068, 3034, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Mathematics.'),
(7069, 3035, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7070, 3035, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Mathematics.'),
(7071, 3036, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الرياضيات.'),
(7072, 3036, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Mathematics.'),
(7073, 3037, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7074, 3037, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Physics.'),
(7075, 3038, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7076, 3038, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Physics.'),
(7077, 3039, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7078, 3039, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Physics.'),
(7079, 3040, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7080, 3040, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Physics.'),
(7081, 3041, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7082, 3041, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Physics.'),
(7083, 3042, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7084, 3042, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Physics.'),
(7085, 3043, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7086, 3043, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Physics.'),
(7087, 3044, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7088, 3044, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Physics.'),
(7089, 3045, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7090, 3045, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Physics.'),
(7091, 3046, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7092, 3046, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Physics.'),
(7093, 3047, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7094, 3047, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Physics.'),
(7095, 3048, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفيزياء.'),
(7096, 3048, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Physics.'),
(7097, 3049, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7098, 3049, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Chemistry.'),
(7099, 3050, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7100, 3050, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Chemistry.'),
(7101, 3051, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7102, 3051, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Chemistry.'),
(7103, 3052, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7104, 3052, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Chemistry.'),
(7105, 3053, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7106, 3053, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Chemistry.'),
(7107, 3054, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7108, 3054, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Chemistry.'),
(7109, 3055, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7110, 3055, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Chemistry.'),
(7111, 3056, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7112, 3056, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Chemistry.'),
(7113, 3057, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7114, 3057, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Chemistry.'),
(7115, 3058, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7116, 3058, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Chemistry.'),
(7117, 3059, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7118, 3059, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Chemistry.'),
(7119, 3060, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الكيمياء.'),
(7120, 3060, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Chemistry.'),
(7121, 3061, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7122, 3061, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Biology.'),
(7123, 3062, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7124, 3062, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Biology.'),
(7125, 3063, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7126, 3063, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Biology.'),
(7127, 3064, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7128, 3064, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Biology.'),
(7129, 3065, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7130, 3065, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Biology.'),
(7131, 3066, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7132, 3066, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Biology.'),
(7133, 3067, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7134, 3067, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Biology.'),
(7135, 3068, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7136, 3068, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Biology.'),
(7137, 3069, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7138, 3069, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Biology.'),
(7139, 3070, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7140, 3070, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Biology.'),
(7141, 3071, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7142, 3071, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Biology.'),
(7143, 3072, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الأحياء.'),
(7144, 3072, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Biology.'),
(7145, 3073, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7146, 3073, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to History.'),
(7147, 3074, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7148, 3074, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to History.'),
(7149, 3075, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7150, 3075, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to History.'),
(7151, 3076, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7152, 3076, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to History.'),
(7153, 3077, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7154, 3077, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to History.'),
(7155, 3078, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7156, 3078, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to History.'),
(7157, 3079, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7158, 3079, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to History.'),
(7159, 3080, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7160, 3080, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to History.'),
(7161, 3081, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7162, 3081, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to History.'),
(7163, 3082, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7164, 3082, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to History.'),
(7165, 3083, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7166, 3083, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to History.'),
(7167, 3084, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع التاريخ.'),
(7168, 3084, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to History.'),
(7169, 3085, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7170, 3085, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Geography.'),
(7171, 3086, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7172, 3086, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Geography.'),
(7173, 3087, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7174, 3087, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Geography.'),
(7175, 3088, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7176, 3088, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Geography.'),
(7177, 3089, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7178, 3089, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Geography.'),
(7179, 3090, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7180, 3090, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Geography.'),
(7181, 3091, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7182, 3091, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Geography.'),
(7183, 3092, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7184, 3092, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Geography.'),
(7185, 3093, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7186, 3093, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Geography.'),
(7187, 3094, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7188, 3094, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Geography.'),
(7189, 3095, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7190, 3095, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Geography.'),
(7191, 3096, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الجغرافيا.'),
(7192, 3096, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Geography.'),
(7193, 3097, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7194, 3097, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Philosophy & Logic.'),
(7195, 3098, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7196, 3098, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Philosophy & Logic.'),
(7197, 3099, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7198, 3099, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Philosophy & Logic.'),
(7199, 3100, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7200, 3100, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Philosophy & Logic.'),
(7201, 3101, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7202, 3101, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Philosophy & Logic.'),
(7203, 3102, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7204, 3102, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Philosophy & Logic.'),
(7205, 3103, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7206, 3103, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Philosophy & Logic.'),
(7207, 3104, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7208, 3104, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Philosophy & Logic.'),
(7209, 3105, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7210, 3105, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Philosophy & Logic.'),
(7211, 3106, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7212, 3106, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Philosophy & Logic.'),
(7213, 3107, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7214, 3107, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Philosophy & Logic.'),
(7215, 3108, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع الفلسفة والمنطق.'),
(7216, 3108, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Philosophy & Logic.'),
(7217, 3109, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7218, 3109, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Computer Science.'),
(7219, 3110, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7220, 3110, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Computer Science.'),
(7221, 3111, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7222, 3111, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Computer Science.'),
(7223, 3112, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7224, 3112, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Computer Science.'),
(7225, 3113, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7226, 3113, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Computer Science.'),
(7227, 3114, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7228, 3114, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Computer Science.'),
(7229, 3115, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7230, 3115, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Computer Science.'),
(7231, 3116, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7232, 3116, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Computer Science.'),
(7233, 3117, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7234, 3117, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Computer Science.'),
(7235, 3118, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7236, 3118, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Computer Science.'),
(7237, 3119, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7238, 3119, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Computer Science.'),
(7239, 3120, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع علوم الحاسب.'),
(7240, 3120, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Computer Science.'),
(7241, 3121, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7242, 3121, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to French Language.'),
(7243, 3122, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7244, 3122, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to French Language.'),
(7245, 3123, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7246, 3123, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to French Language.'),
(7247, 3124, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7248, 3124, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to French Language.'),
(7249, 3125, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7250, 3125, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to French Language.'),
(7251, 3126, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7252, 3126, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to French Language.'),
(7253, 3127, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7254, 3127, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to French Language.'),
(7255, 3128, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7256, 3128, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to French Language.'),
(7257, 3129, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7258, 3129, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to French Language.'),
(7259, 3130, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7260, 3130, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to French Language.'),
(7261, 3131, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7262, 3131, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to French Language.'),
(7263, 3132, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع اللغة الفرنسية.'),
(7264, 3132, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to French Language.'),
(7265, 3133, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7266, 3133, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Integrated Science.'),
(7267, 3134, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7268, 3134, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Integrated Science.'),
(7269, 3135, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7270, 3135, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Integrated Science.'),
(7271, 3136, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7272, 3136, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Integrated Science.'),
(7273, 3137, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7274, 3137, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Integrated Science.'),
(7275, 3138, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7276, 3138, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Integrated Science.'),
(7277, 3139, 1, 'فيديو 1 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7278, 3139, 2, 'Video 1 - Foundations and Core Concepts', 'Public educational video related to Integrated Science.'),
(7279, 3140, 1, 'فيديو 2 - الأساسيات وبناء المفاهيم', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7280, 3140, 2, 'Video 2 - Foundations and Core Concepts', 'Public educational video related to Integrated Science.'),
(7281, 3141, 1, 'فيديو 1 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7282, 3141, 2, 'Video 1 - Application and Problem Solving', 'Public educational video related to Integrated Science.'),
(7283, 3142, 1, 'فيديو 2 - التطبيق وحل المسائل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7284, 3142, 2, 'Video 2 - Application and Problem Solving', 'Public educational video related to Integrated Science.'),
(7285, 3143, 1, 'فيديو 1 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7286, 3143, 2, 'Video 1 - Revision and Comprehensive Practice', 'Public educational video related to Integrated Science.'),
(7287, 3144, 1, 'فيديو 2 - المراجعة والاختبار الشامل', 'درس فيديو فعلي مرتبط بموضوع العلوم المتكاملة.'),
(7288, 3144, 2, 'Video 2 - Revision and Comprehensive Practice', 'Public educational video related to Integrated Science.');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_codes`
--

CREATE TABLE `email_verification_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_type` enum('student','teacher','parent','admin') NOT NULL DEFAULT 'student',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `code` varchar(10) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_verification_codes`
--

INSERT INTO `email_verification_codes` (`id`, `user_type`, `user_id`, `email`, `code`, `expires_at`, `is_used`, `created_at`) VALUES
(1, 'student', 1001, 'student1001@example.com', '482731', '2026-08-11 16:00:00', 0, '2026-08-10 16:00:00'),
(2, 'teacher', 201, 'teacher201@example.com', '915204', '2026-08-11 16:00:00', 1, '2026-08-10 16:00:00'),
(3, 'parent', 301, 'parent301@example.com', '337821', '2026-08-11 16:00:00', 0, '2026-08-10 16:00:00'),
(4, 'admin', 2, 'content@najeh-demo.com', '660145', '2026-08-11 16:00:00', 1, '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempts`
--

CREATE TABLE `exam_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  `total_score` decimal(8,2) DEFAULT NULL,
  `max_score` decimal(8,2) NOT NULL DEFAULT 0.00,
  `started_at` timestamp NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `graded_by_type` enum('admin','teacher') DEFAULT NULL,
  `graded_by_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_attempts`
--

INSERT INTO `exam_attempts` (`id`, `exam_id`, `student_id`, `status`, `total_score`, `max_score`, `started_at`, `submitted_at`, `graded_at`, `graded_by_type`, `graded_by_id`) VALUES
(7001, 601, 1001, 'graded', 5.50, 10.00, '2026-08-01 13:00:00', '2026-08-01 09:32:00', '2026-08-01 12:00:00', 'teacher', 201),
(7002, 602, 1002, 'graded', 10.00, 10.00, '2026-08-02 14:00:00', '2026-08-02 10:32:00', '2026-08-02 13:00:00', 'teacher', 201),
(7003, 603, 1003, 'graded', 10.00, 10.00, '2026-08-03 15:00:00', '2026-08-03 11:32:00', '2026-08-03 14:00:00', 'teacher', 202),
(7004, 604, 1004, 'graded', 8.00, 10.00, '2026-08-04 16:00:00', '2026-08-04 12:32:00', '2026-08-04 15:00:00', 'teacher', 202),
(7005, 605, 1005, 'graded', 7.50, 10.00, '2026-08-05 17:00:00', '2026-08-05 13:32:00', '2026-08-05 16:00:00', 'teacher', 203),
(7006, 606, 1006, 'graded', 8.00, 10.00, '2026-08-06 13:00:00', '2026-08-06 09:32:00', '2026-08-06 12:00:00', 'teacher', 203),
(7007, 607, 1007, 'graded', 8.00, 10.00, '2026-08-07 14:00:00', '2026-08-07 10:32:00', '2026-08-07 13:00:00', 'teacher', 204),
(7008, 608, 1008, 'graded', 10.00, 10.00, '2026-08-08 15:00:00', '2026-08-08 11:32:00', '2026-08-08 14:00:00', 'teacher', 204),
(7009, 609, 1009, 'graded', 7.50, 10.00, '2026-08-09 16:00:00', '2026-08-09 12:32:00', '2026-08-09 15:00:00', 'teacher', 205),
(7010, 610, 1010, 'graded', 8.00, 10.00, '2026-08-01 17:00:00', '2026-08-01 13:32:00', '2026-08-01 16:00:00', 'teacher', 205),
(7011, 611, 1011, 'graded', 8.00, 10.00, '2026-08-02 13:00:00', '2026-08-02 09:32:00', '2026-08-02 12:00:00', 'teacher', 206),
(7012, 612, 1012, 'graded', 10.00, 10.00, '2026-08-03 14:00:00', '2026-08-03 10:32:00', '2026-08-03 13:00:00', 'teacher', 206),
(7013, 613, 1013, 'graded', 7.50, 10.00, '2026-08-04 15:00:00', '2026-08-04 11:32:00', '2026-08-04 14:00:00', 'teacher', 207),
(7014, 614, 1014, 'graded', 8.00, 10.00, '2026-08-05 16:00:00', '2026-08-05 12:32:00', '2026-08-05 15:00:00', 'teacher', 207),
(7015, 615, 1015, 'graded', 8.00, 10.00, '2026-08-06 17:00:00', '2026-08-06 13:32:00', '2026-08-06 16:00:00', 'teacher', 208),
(7016, 616, 1016, 'graded', 8.00, 10.00, '2026-08-07 13:00:00', '2026-08-07 09:32:00', '2026-08-07 12:00:00', 'teacher', 208),
(7017, 617, 1017, 'graded', 9.50, 10.00, '2026-08-08 14:00:00', '2026-08-08 10:32:00', '2026-08-08 13:00:00', 'teacher', 209),
(7018, 618, 1018, 'graded', 10.00, 10.00, '2026-08-09 15:00:00', '2026-08-09 11:32:00', '2026-08-09 14:00:00', 'teacher', 209),
(7019, 619, 1019, 'graded', 6.00, 10.00, '2026-08-01 16:00:00', '2026-08-01 12:32:00', '2026-08-01 15:00:00', 'teacher', 210),
(7020, 620, 1020, 'graded', 8.00, 10.00, '2026-08-02 17:00:00', '2026-08-02 13:32:00', '2026-08-02 16:00:00', 'teacher', 210),
(7021, 621, 1021, 'graded', 7.50, 10.00, '2026-08-03 13:00:00', '2026-08-03 09:32:00', '2026-08-03 12:00:00', 'teacher', 211),
(7022, 622, 1022, 'graded', 10.00, 10.00, '2026-08-04 14:00:00', '2026-08-04 10:32:00', '2026-08-04 13:00:00', 'teacher', 211),
(7023, 623, 1023, 'graded', 10.00, 10.00, '2026-08-05 15:00:00', '2026-08-05 11:32:00', '2026-08-05 14:00:00', 'teacher', 212),
(7024, 624, 1024, 'graded', 8.00, 10.00, '2026-08-06 16:00:00', '2026-08-06 12:32:00', '2026-08-06 15:00:00', 'teacher', 212),
(7025, 601, 1025, 'graded', 5.50, 10.00, '2026-08-07 17:00:00', '2026-08-07 13:32:00', '2026-08-07 16:00:00', 'teacher', 201),
(7026, 602, 1026, 'graded', 8.00, 10.00, '2026-08-08 13:00:00', '2026-08-08 09:32:00', '2026-08-08 12:00:00', 'teacher', 201),
(7027, 603, 1027, 'graded', 10.00, 10.00, '2026-08-09 14:00:00', '2026-08-09 10:32:00', '2026-08-09 13:00:00', 'teacher', 202),
(7028, 604, 1028, 'graded', 10.00, 10.00, '2026-08-01 15:00:00', '2026-08-01 11:32:00', '2026-08-01 14:00:00', 'teacher', 202),
(7029, 605, 1029, 'graded', 7.50, 10.00, '2026-08-02 16:00:00', '2026-08-02 12:32:00', '2026-08-02 15:00:00', 'teacher', 203),
(7030, 606, 1030, 'graded', 8.00, 10.00, '2026-08-03 17:00:00', '2026-08-03 13:32:00', '2026-08-03 16:00:00', 'teacher', 203),
(7031, 607, 1031, 'graded', 6.00, 10.00, '2026-08-04 13:00:00', '2026-08-04 09:32:00', '2026-08-04 12:00:00', 'teacher', 204),
(7032, 608, 1032, 'graded', 10.00, 10.00, '2026-08-05 14:00:00', '2026-08-05 10:32:00', '2026-08-05 13:00:00', 'teacher', 204),
(7033, 609, 1033, 'graded', 9.50, 10.00, '2026-08-06 15:00:00', '2026-08-06 11:32:00', '2026-08-06 14:00:00', 'teacher', 205),
(7034, 610, 1034, 'graded', 8.00, 10.00, '2026-08-07 16:00:00', '2026-08-07 12:32:00', '2026-08-07 15:00:00', 'teacher', 205),
(7035, 611, 1035, 'graded', 8.00, 10.00, '2026-08-08 17:00:00', '2026-08-08 13:32:00', '2026-08-08 16:00:00', 'teacher', 206),
(7036, 612, 1036, 'graded', 8.00, 10.00, '2026-08-09 13:00:00', '2026-08-09 09:32:00', '2026-08-09 12:00:00', 'teacher', 206),
(7037, 613, 1037, 'graded', 7.50, 10.00, '2026-08-01 14:00:00', '2026-08-01 10:32:00', '2026-08-01 13:00:00', 'teacher', 207),
(7038, 614, 1038, 'graded', 10.00, 10.00, '2026-08-02 15:00:00', '2026-08-02 11:32:00', '2026-08-02 14:00:00', 'teacher', 207),
(7039, 615, 1039, 'graded', 8.00, 10.00, '2026-08-03 16:00:00', '2026-08-03 12:32:00', '2026-08-03 15:00:00', 'teacher', 208),
(7040, 616, 1040, 'graded', 8.00, 10.00, '2026-08-04 17:00:00', '2026-08-04 13:32:00', '2026-08-04 16:00:00', 'teacher', 208),
(7041, 617, 1041, 'graded', 7.50, 10.00, '2026-08-05 13:00:00', '2026-08-05 09:32:00', '2026-08-05 12:00:00', 'teacher', 209),
(7042, 618, 1042, 'graded', 10.00, 10.00, '2026-08-06 14:00:00', '2026-08-06 10:32:00', '2026-08-06 13:00:00', 'teacher', 209),
(7043, 619, 1043, 'graded', 8.00, 10.00, '2026-08-07 15:00:00', '2026-08-07 11:32:00', '2026-08-07 14:00:00', 'teacher', 210),
(7044, 620, 1044, 'graded', 8.00, 10.00, '2026-08-08 16:00:00', '2026-08-08 12:32:00', '2026-08-08 15:00:00', 'teacher', 210),
(7045, 621, 1045, 'graded', 7.50, 10.00, '2026-08-09 17:00:00', '2026-08-09 13:32:00', '2026-08-09 16:00:00', 'teacher', 211),
(7046, 622, 1046, 'graded', 8.00, 10.00, '2026-08-01 13:00:00', '2026-08-01 09:32:00', '2026-08-01 12:00:00', 'teacher', 211),
(7047, 623, 1047, 'graded', 10.00, 10.00, '2026-08-02 14:00:00', '2026-08-02 10:32:00', '2026-08-02 13:00:00', 'teacher', 212),
(7048, 624, 1048, 'graded', 10.00, 10.00, '2026-08-03 15:00:00', '2026-08-03 11:32:00', '2026-08-03 14:00:00', 'teacher', 212),
(7049, 601, 1049, 'graded', 5.50, 10.00, '2026-08-04 16:00:00', '2026-08-04 12:32:00', '2026-08-04 15:00:00', 'teacher', 201),
(7050, 602, 1050, 'graded', 8.00, 10.00, '2026-08-05 17:00:00', '2026-08-05 13:32:00', '2026-08-05 16:00:00', 'teacher', 201),
(7051, 603, 1051, 'graded', 8.00, 10.00, '2026-08-06 13:00:00', '2026-08-06 09:32:00', '2026-08-06 12:00:00', 'teacher', 202),
(7052, 604, 1052, 'graded', 10.00, 10.00, '2026-08-07 14:00:00', '2026-08-07 10:32:00', '2026-08-07 13:00:00', 'teacher', 202),
(7053, 605, 1053, 'graded', 9.50, 10.00, '2026-08-08 15:00:00', '2026-08-08 11:32:00', '2026-08-08 14:00:00', 'teacher', 203),
(7054, 606, 1054, 'graded', 8.00, 10.00, '2026-08-09 16:00:00', '2026-08-09 12:32:00', '2026-08-09 15:00:00', 'teacher', 203),
(7055, 607, 1055, 'graded', 6.00, 10.00, '2026-08-01 17:00:00', '2026-08-01 13:32:00', '2026-08-01 16:00:00', 'teacher', 204),
(7056, 608, 1056, 'graded', 8.00, 10.00, '2026-08-02 13:00:00', '2026-08-02 09:32:00', '2026-08-02 12:00:00', 'teacher', 204),
(7057, 609, 1057, 'graded', 9.50, 10.00, '2026-08-03 14:00:00', '2026-08-03 10:32:00', '2026-08-03 13:00:00', 'teacher', 205),
(7058, 610, 1058, 'graded', 10.00, 10.00, '2026-08-04 15:00:00', '2026-08-04 11:32:00', '2026-08-04 14:00:00', 'teacher', 205),
(7059, 611, 1059, 'graded', 8.00, 10.00, '2026-08-05 16:00:00', '2026-08-05 12:32:00', '2026-08-05 15:00:00', 'teacher', 206),
(7060, 612, 1060, 'graded', 8.00, 10.00, '2026-08-06 17:00:00', '2026-08-06 13:32:00', '2026-08-06 16:00:00', 'teacher', 206),
(7061, 602, 1025, 'graded', 10.00, 10.00, '2026-08-10 13:45:05', '2026-08-10 13:45:36', '2026-08-12 13:03:05', 'teacher', 201),
(7062, 623, 1025, 'submitted', 2.00, 10.00, '2026-08-12 15:30:30', '2026-08-12 15:30:40', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempt_answers`
--

CREATE TABLE `exam_attempt_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attempt_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `mcq_option_id` bigint(20) UNSIGNED DEFAULT NULL,
  `true_false_answer` tinyint(1) DEFAULT NULL,
  `written_answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `score_awarded` decimal(8,2) DEFAULT NULL,
  `teacher_note` text DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_attempt_answers`
--

INSERT INTO `exam_attempt_answers` (`id`, `attempt_id`, `question_id`, `mcq_option_id`, `true_false_answer`, `written_answer_text`, `is_correct`, `score_awarded`, `teacher_note`, `answered_at`) VALUES
(15001, 7001, 5001, 10002, NULL, NULL, 0, 0.00, NULL, '2026-08-01 13:05:00'),
(15002, 7001, 5002, 10005, NULL, NULL, 1, 2.00, NULL, '2026-08-01 13:09:00'),
(15003, 7001, 5003, 10009, NULL, NULL, 1, 2.00, NULL, '2026-08-01 13:13:00'),
(15004, 7001, 5004, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-01 13:18:00'),
(15005, 7001, 5005, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-01 13:25:00'),
(15006, 7002, 5006, 10013, NULL, NULL, 1, 2.00, NULL, '2026-08-02 14:05:00'),
(15007, 7002, 5007, 10017, NULL, NULL, 1, 2.00, NULL, '2026-08-02 14:09:00'),
(15008, 7002, 5008, 10021, NULL, NULL, 1, 2.00, NULL, '2026-08-02 14:13:00'),
(15009, 7002, 5009, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-02 14:18:00'),
(15010, 7002, 5010, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-02 14:25:00'),
(15011, 7003, 5011, 10025, NULL, NULL, 1, 2.00, NULL, '2026-08-03 15:05:00'),
(15012, 7003, 5012, 10029, NULL, NULL, 1, 2.00, NULL, '2026-08-03 15:09:00'),
(15013, 7003, 5013, 10033, NULL, NULL, 1, 2.00, NULL, '2026-08-03 15:13:00'),
(15014, 7003, 5014, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-03 15:18:00'),
(15015, 7003, 5015, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-03 15:25:00'),
(15016, 7004, 5016, 10037, NULL, NULL, 1, 2.00, NULL, '2026-08-04 16:05:00'),
(15017, 7004, 5017, 10041, NULL, NULL, 1, 2.00, NULL, '2026-08-04 16:09:00'),
(15018, 7004, 5018, 10046, NULL, NULL, 0, 0.00, NULL, '2026-08-04 16:13:00'),
(15019, 7004, 5019, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-04 16:18:00'),
(15020, 7004, 5020, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-04 16:25:00'),
(15021, 7005, 5021, 10049, NULL, NULL, 1, 2.00, NULL, '2026-08-05 17:05:00'),
(15022, 7005, 5022, 10054, NULL, NULL, 0, 0.00, NULL, '2026-08-05 17:09:00'),
(15023, 7005, 5023, 10057, NULL, NULL, 1, 2.00, NULL, '2026-08-05 17:13:00'),
(15024, 7005, 5024, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-05 17:18:00'),
(15025, 7005, 5025, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-05 17:25:00'),
(15026, 7006, 5026, 10062, NULL, NULL, 0, 0.00, NULL, '2026-08-06 13:05:00'),
(15027, 7006, 5027, 10065, NULL, NULL, 1, 2.00, NULL, '2026-08-06 13:09:00'),
(15028, 7006, 5028, 10069, NULL, NULL, 1, 2.00, NULL, '2026-08-06 13:13:00'),
(15029, 7006, 5029, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-06 13:18:00'),
(15030, 7006, 5030, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-06 13:25:00'),
(15031, 7007, 5031, 10073, NULL, NULL, 1, 2.00, NULL, '2026-08-07 14:05:00'),
(15032, 7007, 5032, 10077, NULL, NULL, 1, 2.00, NULL, '2026-08-07 14:09:00'),
(15033, 7007, 5033, 10081, NULL, NULL, 1, 2.00, NULL, '2026-08-07 14:13:00'),
(15034, 7007, 5034, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-07 14:18:00'),
(15035, 7007, 5035, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-07 14:25:00'),
(15036, 7008, 5036, 10085, NULL, NULL, 1, 2.00, NULL, '2026-08-08 15:05:00'),
(15037, 7008, 5037, 10089, NULL, NULL, 1, 2.00, NULL, '2026-08-08 15:09:00'),
(15038, 7008, 5038, 10093, NULL, NULL, 1, 2.00, NULL, '2026-08-08 15:13:00'),
(15039, 7008, 5039, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-08 15:18:00'),
(15040, 7008, 5040, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-08 15:25:00'),
(15041, 7009, 5041, 10097, NULL, NULL, 1, 2.00, NULL, '2026-08-09 16:05:00'),
(15042, 7009, 5042, 10101, NULL, NULL, 1, 2.00, NULL, '2026-08-09 16:09:00'),
(15043, 7009, 5043, 10106, NULL, NULL, 0, 0.00, NULL, '2026-08-09 16:13:00'),
(15044, 7009, 5044, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-09 16:18:00'),
(15045, 7009, 5045, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-09 16:25:00'),
(15046, 7010, 5046, 10109, NULL, NULL, 1, 2.00, NULL, '2026-08-01 17:05:00'),
(15047, 7010, 5047, 10114, NULL, NULL, 0, 0.00, NULL, '2026-08-01 17:09:00'),
(15048, 7010, 5048, 10117, NULL, NULL, 1, 2.00, NULL, '2026-08-01 17:13:00'),
(15049, 7010, 5049, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-01 17:18:00'),
(15050, 7010, 5050, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-01 17:25:00'),
(15051, 7011, 5051, 10122, NULL, NULL, 0, 0.00, NULL, '2026-08-02 13:05:00'),
(15052, 7011, 5052, 10125, NULL, NULL, 1, 2.00, NULL, '2026-08-02 13:09:00'),
(15053, 7011, 5053, 10129, NULL, NULL, 1, 2.00, NULL, '2026-08-02 13:13:00'),
(15054, 7011, 5054, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-02 13:18:00'),
(15055, 7011, 5055, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-02 13:25:00'),
(15056, 7012, 5056, 10133, NULL, NULL, 1, 2.00, NULL, '2026-08-03 14:05:00'),
(15057, 7012, 5057, 10137, NULL, NULL, 1, 2.00, NULL, '2026-08-03 14:09:00'),
(15058, 7012, 5058, 10141, NULL, NULL, 1, 2.00, NULL, '2026-08-03 14:13:00'),
(15059, 7012, 5059, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-03 14:18:00'),
(15060, 7012, 5060, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-03 14:25:00'),
(15061, 7013, 5061, 10145, NULL, NULL, 1, 2.00, NULL, '2026-08-04 15:05:00'),
(15062, 7013, 5062, 10149, NULL, NULL, 1, 2.00, NULL, '2026-08-04 15:09:00'),
(15063, 7013, 5063, 10153, NULL, NULL, 1, 2.00, NULL, '2026-08-04 15:13:00'),
(15064, 7013, 5064, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-04 15:18:00'),
(15065, 7013, 5065, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-04 15:25:00'),
(15066, 7014, 5066, 10157, NULL, NULL, 1, 2.00, NULL, '2026-08-05 16:05:00'),
(15067, 7014, 5067, 10161, NULL, NULL, 1, 2.00, NULL, '2026-08-05 16:09:00'),
(15068, 7014, 5068, 10166, NULL, NULL, 0, 0.00, NULL, '2026-08-05 16:13:00'),
(15069, 7014, 5069, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-05 16:18:00'),
(15070, 7014, 5070, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-05 16:25:00'),
(15071, 7015, 5071, 10169, NULL, NULL, 1, 2.00, NULL, '2026-08-06 17:05:00'),
(15072, 7015, 5072, 10174, NULL, NULL, 0, 0.00, NULL, '2026-08-06 17:09:00'),
(15073, 7015, 5073, 10177, NULL, NULL, 1, 2.00, NULL, '2026-08-06 17:13:00'),
(15074, 7015, 5074, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-06 17:18:00'),
(15075, 7015, 5075, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-06 17:25:00'),
(15076, 7016, 5076, 10182, NULL, NULL, 0, 0.00, NULL, '2026-08-07 13:05:00'),
(15077, 7016, 5077, 10185, NULL, NULL, 1, 2.00, NULL, '2026-08-07 13:09:00'),
(15078, 7016, 5078, 10189, NULL, NULL, 1, 2.00, NULL, '2026-08-07 13:13:00'),
(15079, 7016, 5079, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-07 13:18:00'),
(15080, 7016, 5080, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-07 13:25:00'),
(15081, 7017, 5081, 10193, NULL, NULL, 1, 2.00, NULL, '2026-08-08 14:05:00'),
(15082, 7017, 5082, 10197, NULL, NULL, 1, 2.00, NULL, '2026-08-08 14:09:00'),
(15083, 7017, 5083, 10201, NULL, NULL, 1, 2.00, NULL, '2026-08-08 14:13:00'),
(15084, 7017, 5084, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-08 14:18:00'),
(15085, 7017, 5085, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-08 14:25:00'),
(15086, 7018, 5086, 10205, NULL, NULL, 1, 2.00, NULL, '2026-08-09 15:05:00'),
(15087, 7018, 5087, 10209, NULL, NULL, 1, 2.00, NULL, '2026-08-09 15:09:00'),
(15088, 7018, 5088, 10213, NULL, NULL, 1, 2.00, NULL, '2026-08-09 15:13:00'),
(15089, 7018, 5089, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-09 15:18:00'),
(15090, 7018, 5090, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-09 15:25:00'),
(15091, 7019, 5091, 10217, NULL, NULL, 1, 2.00, NULL, '2026-08-01 16:05:00'),
(15092, 7019, 5092, 10221, NULL, NULL, 1, 2.00, NULL, '2026-08-01 16:09:00'),
(15093, 7019, 5093, 10226, NULL, NULL, 0, 0.00, NULL, '2026-08-01 16:13:00'),
(15094, 7019, 5094, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-01 16:18:00'),
(15095, 7019, 5095, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-01 16:25:00'),
(15096, 7020, 5096, 10229, NULL, NULL, 1, 2.00, NULL, '2026-08-02 17:05:00'),
(15097, 7020, 5097, 10234, NULL, NULL, 0, 0.00, NULL, '2026-08-02 17:09:00'),
(15098, 7020, 5098, 10237, NULL, NULL, 1, 2.00, NULL, '2026-08-02 17:13:00'),
(15099, 7020, 5099, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-02 17:18:00'),
(15100, 7020, 5100, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-02 17:25:00'),
(15101, 7021, 5101, 10242, NULL, NULL, 0, 0.00, NULL, '2026-08-03 13:05:00'),
(15102, 7021, 5102, 10245, NULL, NULL, 1, 2.00, NULL, '2026-08-03 13:09:00'),
(15103, 7021, 5103, 10249, NULL, NULL, 1, 2.00, NULL, '2026-08-03 13:13:00'),
(15104, 7021, 5104, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-03 13:18:00'),
(15105, 7021, 5105, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-03 13:25:00'),
(15106, 7022, 5106, 10253, NULL, NULL, 1, 2.00, NULL, '2026-08-04 14:05:00'),
(15107, 7022, 5107, 10257, NULL, NULL, 1, 2.00, NULL, '2026-08-04 14:09:00'),
(15108, 7022, 5108, 10261, NULL, NULL, 1, 2.00, NULL, '2026-08-04 14:13:00'),
(15109, 7022, 5109, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-04 14:18:00'),
(15110, 7022, 5110, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-04 14:25:00'),
(15111, 7023, 5111, 10265, NULL, NULL, 1, 2.00, NULL, '2026-08-05 15:05:00'),
(15112, 7023, 5112, 10269, NULL, NULL, 1, 2.00, NULL, '2026-08-05 15:09:00'),
(15113, 7023, 5113, 10273, NULL, NULL, 1, 2.00, NULL, '2026-08-05 15:13:00'),
(15114, 7023, 5114, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-05 15:18:00'),
(15115, 7023, 5115, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-05 15:25:00'),
(15116, 7024, 5116, 10277, NULL, NULL, 1, 2.00, NULL, '2026-08-06 16:05:00'),
(15117, 7024, 5117, 10281, NULL, NULL, 1, 2.00, NULL, '2026-08-06 16:09:00'),
(15118, 7024, 5118, 10286, NULL, NULL, 0, 0.00, NULL, '2026-08-06 16:13:00'),
(15119, 7024, 5119, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-06 16:18:00'),
(15120, 7024, 5120, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-06 16:25:00'),
(15121, 7025, 5001, 10001, NULL, NULL, 1, 2.00, NULL, '2026-08-07 17:05:00'),
(15122, 7025, 5002, 10006, NULL, NULL, 0, 0.00, NULL, '2026-08-07 17:09:00'),
(15123, 7025, 5003, 10009, NULL, NULL, 1, 2.00, NULL, '2026-08-07 17:13:00'),
(15124, 7025, 5004, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-07 17:18:00'),
(15125, 7025, 5005, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-07 17:25:00'),
(15126, 7026, 5006, 10014, NULL, NULL, 0, 0.00, NULL, '2026-08-08 13:05:00'),
(15127, 7026, 5007, 10017, NULL, NULL, 1, 2.00, NULL, '2026-08-08 13:09:00'),
(15128, 7026, 5008, 10021, NULL, NULL, 1, 2.00, NULL, '2026-08-08 13:13:00'),
(15129, 7026, 5009, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-08 13:18:00'),
(15130, 7026, 5010, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-08 13:25:00'),
(15131, 7027, 5011, 10025, NULL, NULL, 1, 2.00, NULL, '2026-08-09 14:05:00'),
(15132, 7027, 5012, 10029, NULL, NULL, 1, 2.00, NULL, '2026-08-09 14:09:00'),
(15133, 7027, 5013, 10033, NULL, NULL, 1, 2.00, NULL, '2026-08-09 14:13:00'),
(15134, 7027, 5014, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-09 14:18:00'),
(15135, 7027, 5015, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-09 14:25:00'),
(15136, 7028, 5016, 10037, NULL, NULL, 1, 2.00, NULL, '2026-08-01 15:05:00'),
(15137, 7028, 5017, 10041, NULL, NULL, 1, 2.00, NULL, '2026-08-01 15:09:00'),
(15138, 7028, 5018, 10045, NULL, NULL, 1, 2.00, NULL, '2026-08-01 15:13:00'),
(15139, 7028, 5019, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-01 15:18:00'),
(15140, 7028, 5020, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-01 15:25:00'),
(15141, 7029, 5021, 10049, NULL, NULL, 1, 2.00, NULL, '2026-08-02 16:05:00'),
(15142, 7029, 5022, 10053, NULL, NULL, 1, 2.00, NULL, '2026-08-02 16:09:00'),
(15143, 7029, 5023, 10058, NULL, NULL, 0, 0.00, NULL, '2026-08-02 16:13:00'),
(15144, 7029, 5024, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-02 16:18:00'),
(15145, 7029, 5025, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-02 16:25:00'),
(15146, 7030, 5026, 10061, NULL, NULL, 1, 2.00, NULL, '2026-08-03 17:05:00'),
(15147, 7030, 5027, 10066, NULL, NULL, 0, 0.00, NULL, '2026-08-03 17:09:00'),
(15148, 7030, 5028, 10069, NULL, NULL, 1, 2.00, NULL, '2026-08-03 17:13:00'),
(15149, 7030, 5029, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-03 17:18:00'),
(15150, 7030, 5030, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-03 17:25:00'),
(15151, 7031, 5031, 10074, NULL, NULL, 0, 0.00, NULL, '2026-08-04 13:05:00'),
(15152, 7031, 5032, 10077, NULL, NULL, 1, 2.00, NULL, '2026-08-04 13:09:00'),
(15153, 7031, 5033, 10081, NULL, NULL, 1, 2.00, NULL, '2026-08-04 13:13:00'),
(15154, 7031, 5034, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-04 13:18:00'),
(15155, 7031, 5035, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-04 13:25:00'),
(15156, 7032, 5036, 10085, NULL, NULL, 1, 2.00, NULL, '2026-08-05 14:05:00'),
(15157, 7032, 5037, 10089, NULL, NULL, 1, 2.00, NULL, '2026-08-05 14:09:00'),
(15158, 7032, 5038, 10093, NULL, NULL, 1, 2.00, NULL, '2026-08-05 14:13:00'),
(15159, 7032, 5039, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-05 14:18:00'),
(15160, 7032, 5040, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-05 14:25:00'),
(15161, 7033, 5041, 10097, NULL, NULL, 1, 2.00, NULL, '2026-08-06 15:05:00'),
(15162, 7033, 5042, 10101, NULL, NULL, 1, 2.00, NULL, '2026-08-06 15:09:00'),
(15163, 7033, 5043, 10105, NULL, NULL, 1, 2.00, NULL, '2026-08-06 15:13:00'),
(15164, 7033, 5044, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-06 15:18:00'),
(15165, 7033, 5045, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-06 15:25:00'),
(15166, 7034, 5046, 10109, NULL, NULL, 1, 2.00, NULL, '2026-08-07 16:05:00'),
(15167, 7034, 5047, 10113, NULL, NULL, 1, 2.00, NULL, '2026-08-07 16:09:00'),
(15168, 7034, 5048, 10118, NULL, NULL, 0, 0.00, NULL, '2026-08-07 16:13:00'),
(15169, 7034, 5049, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-07 16:18:00'),
(15170, 7034, 5050, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-07 16:25:00'),
(15171, 7035, 5051, 10121, NULL, NULL, 1, 2.00, NULL, '2026-08-08 17:05:00'),
(15172, 7035, 5052, 10126, NULL, NULL, 0, 0.00, NULL, '2026-08-08 17:09:00'),
(15173, 7035, 5053, 10129, NULL, NULL, 1, 2.00, NULL, '2026-08-08 17:13:00'),
(15174, 7035, 5054, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-08 17:18:00'),
(15175, 7035, 5055, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-08 17:25:00'),
(15176, 7036, 5056, 10134, NULL, NULL, 0, 0.00, NULL, '2026-08-09 13:05:00'),
(15177, 7036, 5057, 10137, NULL, NULL, 1, 2.00, NULL, '2026-08-09 13:09:00'),
(15178, 7036, 5058, 10141, NULL, NULL, 1, 2.00, NULL, '2026-08-09 13:13:00'),
(15179, 7036, 5059, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-09 13:18:00'),
(15180, 7036, 5060, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-09 13:25:00'),
(15181, 7037, 5061, 10145, NULL, NULL, 1, 2.00, NULL, '2026-08-01 14:05:00'),
(15182, 7037, 5062, 10149, NULL, NULL, 1, 2.00, NULL, '2026-08-01 14:09:00'),
(15183, 7037, 5063, 10153, NULL, NULL, 1, 2.00, NULL, '2026-08-01 14:13:00'),
(15184, 7037, 5064, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-01 14:18:00'),
(15185, 7037, 5065, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-01 14:25:00'),
(15186, 7038, 5066, 10157, NULL, NULL, 1, 2.00, NULL, '2026-08-02 15:05:00'),
(15187, 7038, 5067, 10161, NULL, NULL, 1, 2.00, NULL, '2026-08-02 15:09:00'),
(15188, 7038, 5068, 10165, NULL, NULL, 1, 2.00, NULL, '2026-08-02 15:13:00'),
(15189, 7038, 5069, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-02 15:18:00'),
(15190, 7038, 5070, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-02 15:25:00'),
(15191, 7039, 5071, 10169, NULL, NULL, 1, 2.00, NULL, '2026-08-03 16:05:00'),
(15192, 7039, 5072, 10173, NULL, NULL, 1, 2.00, NULL, '2026-08-03 16:09:00'),
(15193, 7039, 5073, 10178, NULL, NULL, 0, 0.00, NULL, '2026-08-03 16:13:00'),
(15194, 7039, 5074, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-03 16:18:00'),
(15195, 7039, 5075, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-03 16:25:00'),
(15196, 7040, 5076, 10181, NULL, NULL, 1, 2.00, NULL, '2026-08-04 17:05:00'),
(15197, 7040, 5077, 10186, NULL, NULL, 0, 0.00, NULL, '2026-08-04 17:09:00'),
(15198, 7040, 5078, 10189, NULL, NULL, 1, 2.00, NULL, '2026-08-04 17:13:00'),
(15199, 7040, 5079, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-04 17:18:00'),
(15200, 7040, 5080, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-04 17:25:00'),
(15201, 7041, 5081, 10194, NULL, NULL, 0, 0.00, NULL, '2026-08-05 13:05:00'),
(15202, 7041, 5082, 10197, NULL, NULL, 1, 2.00, NULL, '2026-08-05 13:09:00'),
(15203, 7041, 5083, 10201, NULL, NULL, 1, 2.00, NULL, '2026-08-05 13:13:00'),
(15204, 7041, 5084, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-05 13:18:00'),
(15205, 7041, 5085, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-05 13:25:00'),
(15206, 7042, 5086, 10205, NULL, NULL, 1, 2.00, NULL, '2026-08-06 14:05:00'),
(15207, 7042, 5087, 10209, NULL, NULL, 1, 2.00, NULL, '2026-08-06 14:09:00'),
(15208, 7042, 5088, 10213, NULL, NULL, 1, 2.00, NULL, '2026-08-06 14:13:00'),
(15209, 7042, 5089, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-06 14:18:00'),
(15210, 7042, 5090, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-06 14:25:00'),
(15211, 7043, 5091, 10217, NULL, NULL, 1, 2.00, NULL, '2026-08-07 15:05:00'),
(15212, 7043, 5092, 10221, NULL, NULL, 1, 2.00, NULL, '2026-08-07 15:09:00'),
(15213, 7043, 5093, 10225, NULL, NULL, 1, 2.00, NULL, '2026-08-07 15:13:00'),
(15214, 7043, 5094, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-07 15:18:00'),
(15215, 7043, 5095, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-07 15:25:00'),
(15216, 7044, 5096, 10229, NULL, NULL, 1, 2.00, NULL, '2026-08-08 16:05:00'),
(15217, 7044, 5097, 10233, NULL, NULL, 1, 2.00, NULL, '2026-08-08 16:09:00'),
(15218, 7044, 5098, 10238, NULL, NULL, 0, 0.00, NULL, '2026-08-08 16:13:00'),
(15219, 7044, 5099, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-08 16:18:00'),
(15220, 7044, 5100, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-08 16:25:00'),
(15221, 7045, 5101, 10241, NULL, NULL, 1, 2.00, NULL, '2026-08-09 17:05:00'),
(15222, 7045, 5102, 10246, NULL, NULL, 0, 0.00, NULL, '2026-08-09 17:09:00'),
(15223, 7045, 5103, 10249, NULL, NULL, 1, 2.00, NULL, '2026-08-09 17:13:00'),
(15224, 7045, 5104, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-09 17:18:00'),
(15225, 7045, 5105, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-09 17:25:00'),
(15226, 7046, 5106, 10254, NULL, NULL, 0, 0.00, NULL, '2026-08-01 13:05:00'),
(15227, 7046, 5107, 10257, NULL, NULL, 1, 2.00, NULL, '2026-08-01 13:09:00'),
(15228, 7046, 5108, 10261, NULL, NULL, 1, 2.00, NULL, '2026-08-01 13:13:00'),
(15229, 7046, 5109, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-01 13:18:00'),
(15230, 7046, 5110, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-01 13:25:00'),
(15231, 7047, 5111, 10265, NULL, NULL, 1, 2.00, NULL, '2026-08-02 14:05:00'),
(15232, 7047, 5112, 10269, NULL, NULL, 1, 2.00, NULL, '2026-08-02 14:09:00'),
(15233, 7047, 5113, 10273, NULL, NULL, 1, 2.00, NULL, '2026-08-02 14:13:00'),
(15234, 7047, 5114, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-02 14:18:00'),
(15235, 7047, 5115, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-02 14:25:00'),
(15236, 7048, 5116, 10277, NULL, NULL, 1, 2.00, NULL, '2026-08-03 15:05:00'),
(15237, 7048, 5117, 10281, NULL, NULL, 1, 2.00, NULL, '2026-08-03 15:09:00'),
(15238, 7048, 5118, 10285, NULL, NULL, 1, 2.00, NULL, '2026-08-03 15:13:00'),
(15239, 7048, 5119, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-03 15:18:00'),
(15240, 7048, 5120, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-03 15:25:00'),
(15241, 7049, 5001, 10001, NULL, NULL, 1, 2.00, NULL, '2026-08-04 16:05:00'),
(15242, 7049, 5002, 10005, NULL, NULL, 1, 2.00, NULL, '2026-08-04 16:09:00'),
(15243, 7049, 5003, 10010, NULL, NULL, 0, 0.00, NULL, '2026-08-04 16:13:00'),
(15244, 7049, 5004, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-04 16:18:00'),
(15245, 7049, 5005, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-04 16:25:00'),
(15246, 7050, 5006, 10013, NULL, NULL, 1, 2.00, NULL, '2026-08-05 17:05:00'),
(15247, 7050, 5007, 10018, NULL, NULL, 0, 0.00, NULL, '2026-08-05 17:09:00'),
(15248, 7050, 5008, 10021, NULL, NULL, 1, 2.00, NULL, '2026-08-05 17:13:00'),
(15249, 7050, 5009, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-05 17:18:00'),
(15250, 7050, 5010, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-05 17:25:00'),
(15251, 7051, 5011, 10026, NULL, NULL, 0, 0.00, NULL, '2026-08-06 13:05:00'),
(15252, 7051, 5012, 10029, NULL, NULL, 1, 2.00, NULL, '2026-08-06 13:09:00'),
(15253, 7051, 5013, 10033, NULL, NULL, 1, 2.00, NULL, '2026-08-06 13:13:00'),
(15254, 7051, 5014, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-06 13:18:00'),
(15255, 7051, 5015, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-06 13:25:00'),
(15256, 7052, 5016, 10037, NULL, NULL, 1, 2.00, NULL, '2026-08-07 14:05:00'),
(15257, 7052, 5017, 10041, NULL, NULL, 1, 2.00, NULL, '2026-08-07 14:09:00'),
(15258, 7052, 5018, 10045, NULL, NULL, 1, 2.00, NULL, '2026-08-07 14:13:00'),
(15259, 7052, 5019, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-07 14:18:00'),
(15260, 7052, 5020, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-07 14:25:00'),
(15261, 7053, 5021, 10049, NULL, NULL, 1, 2.00, NULL, '2026-08-08 15:05:00'),
(15262, 7053, 5022, 10053, NULL, NULL, 1, 2.00, NULL, '2026-08-08 15:09:00'),
(15263, 7053, 5023, 10057, NULL, NULL, 1, 2.00, NULL, '2026-08-08 15:13:00'),
(15264, 7053, 5024, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-08 15:18:00'),
(15265, 7053, 5025, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-08 15:25:00'),
(15266, 7054, 5026, 10061, NULL, NULL, 1, 2.00, NULL, '2026-08-09 16:05:00'),
(15267, 7054, 5027, 10065, NULL, NULL, 1, 2.00, NULL, '2026-08-09 16:09:00'),
(15268, 7054, 5028, 10070, NULL, NULL, 0, 0.00, NULL, '2026-08-09 16:13:00'),
(15269, 7054, 5029, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-09 16:18:00'),
(15270, 7054, 5030, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-09 16:25:00'),
(15271, 7055, 5031, 10073, NULL, NULL, 1, 2.00, NULL, '2026-08-01 17:05:00'),
(15272, 7055, 5032, 10078, NULL, NULL, 0, 0.00, NULL, '2026-08-01 17:09:00'),
(15273, 7055, 5033, 10081, NULL, NULL, 1, 2.00, NULL, '2026-08-01 17:13:00'),
(15274, 7055, 5034, NULL, 0, NULL, 0, 0.00, NULL, '2026-08-01 17:18:00'),
(15275, 7055, 5035, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-01 17:25:00'),
(15276, 7056, 5036, 10086, NULL, NULL, 0, 0.00, NULL, '2026-08-02 13:05:00'),
(15277, 7056, 5037, 10089, NULL, NULL, 1, 2.00, NULL, '2026-08-02 13:09:00'),
(15278, 7056, 5038, 10093, NULL, NULL, 1, 2.00, NULL, '2026-08-02 13:13:00'),
(15279, 7056, 5039, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-02 13:18:00'),
(15280, 7056, 5040, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-02 13:25:00'),
(15281, 7057, 5041, 10097, NULL, NULL, 1, 2.00, NULL, '2026-08-03 14:05:00'),
(15282, 7057, 5042, 10101, NULL, NULL, 1, 2.00, NULL, '2026-08-03 14:09:00'),
(15283, 7057, 5043, 10105, NULL, NULL, 1, 2.00, NULL, '2026-08-03 14:13:00'),
(15284, 7057, 5044, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-03 14:18:00'),
(15285, 7057, 5045, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 1.50, 'إجابة جيدة؛ راجع التفاصيل الدقيقة.', '2026-08-03 14:25:00'),
(15286, 7058, 5046, 10109, NULL, NULL, 1, 2.00, NULL, '2026-08-04 15:05:00'),
(15287, 7058, 5047, 10113, NULL, NULL, 1, 2.00, NULL, '2026-08-04 15:09:00'),
(15288, 7058, 5048, 10117, NULL, NULL, 1, 2.00, NULL, '2026-08-04 15:13:00'),
(15289, 7058, 5049, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-04 15:18:00'),
(15290, 7058, 5050, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-04 15:25:00'),
(15291, 7059, 5051, 10121, NULL, NULL, 1, 2.00, NULL, '2026-08-05 16:05:00'),
(15292, 7059, 5052, 10125, NULL, NULL, 1, 2.00, NULL, '2026-08-05 16:09:00'),
(15293, 7059, 5053, 10130, NULL, NULL, 0, 0.00, NULL, '2026-08-05 16:13:00'),
(15294, 7059, 5054, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-05 16:18:00'),
(15295, 7059, 5055, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-05 16:25:00'),
(15296, 7060, 5056, 10133, NULL, NULL, 1, 2.00, NULL, '2026-08-06 17:05:00'),
(15297, 7060, 5057, 10138, NULL, NULL, 0, 0.00, NULL, '2026-08-06 17:09:00'),
(15298, 7060, 5058, 10141, NULL, NULL, 1, 2.00, NULL, '2026-08-06 17:13:00'),
(15299, 7060, 5059, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-06 17:18:00'),
(15300, 7060, 5060, NULL, NULL, 'إجابة منظمة تتضمن التعريف والفكرة الأساسية ومثالًا مختصرًا.', 1, 2.00, 'إجابة مكتملة وواضحة.', '2026-08-06 17:25:00'),
(15301, 7061, 5006, 10013, NULL, NULL, 1, 2.00, NULL, '2026-08-10 13:45:36'),
(15302, 7061, 5007, 10017, NULL, NULL, 1, 2.00, NULL, '2026-08-10 13:45:36'),
(15303, 7061, 5008, 10021, NULL, NULL, 1, 2.00, NULL, '2026-08-10 13:45:36'),
(15304, 7061, 5009, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-10 13:45:36'),
(15305, 7061, 5010, NULL, NULL, '', 1, 2.00, '', '2026-08-10 13:45:36'),
(15306, 7062, 5111, 10266, NULL, NULL, 0, 0.00, NULL, '2026-08-12 15:30:40'),
(15307, 7062, 5112, 10271, NULL, NULL, 0, 0.00, NULL, '2026-08-12 15:30:40'),
(15308, 7062, 5113, 10274, NULL, NULL, 0, 0.00, NULL, '2026-08-12 15:30:40'),
(15309, 7062, 5114, NULL, 1, NULL, 1, 2.00, NULL, '2026-08-12 15:30:40'),
(15310, 7062, 5115, NULL, NULL, '', NULL, NULL, NULL, '2026-08-12 15:30:40');

-- --------------------------------------------------------

--
-- Table structure for table `exam_teachers`
--

CREATE TABLE `exam_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_teachers`
--

INSERT INTO `exam_teachers` (`id`, `exam_id`, `teacher_id`, `is_active`, `created_at`) VALUES
(1001, 601, 201, 1, '2026-08-01 14:00:00'),
(1002, 602, 201, 1, '2026-08-01 14:00:00'),
(1003, 603, 202, 1, '2026-08-01 14:00:00'),
(1004, 604, 202, 1, '2026-08-01 14:00:00'),
(1005, 605, 203, 1, '2026-08-01 14:00:00'),
(1006, 606, 203, 1, '2026-08-01 14:00:00'),
(1007, 607, 204, 1, '2026-08-01 14:00:00'),
(1008, 608, 204, 1, '2026-08-01 14:00:00'),
(1009, 609, 205, 1, '2026-08-01 14:00:00'),
(1010, 610, 205, 1, '2026-08-01 14:00:00'),
(1011, 611, 206, 1, '2026-08-01 14:00:00'),
(1012, 612, 206, 1, '2026-08-01 14:00:00'),
(1013, 613, 207, 1, '2026-08-01 14:00:00'),
(1014, 614, 207, 1, '2026-08-01 14:00:00'),
(1015, 615, 208, 1, '2026-08-01 14:00:00'),
(1016, 616, 208, 1, '2026-08-01 14:00:00'),
(1017, 617, 209, 1, '2026-08-01 14:00:00'),
(1018, 618, 209, 1, '2026-08-01 14:00:00'),
(1019, 619, 210, 1, '2026-08-01 14:00:00'),
(1020, 620, 210, 1, '2026-08-01 14:00:00'),
(1021, 621, 211, 1, '2026-08-01 14:00:00'),
(1022, 622, 211, 1, '2026-08-01 14:00:00'),
(1023, 623, 212, 1, '2026-08-01 14:00:00'),
(1024, 624, 212, 1, '2026-08-01 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `homework`
--

CREATE TABLE `homework` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `homework`
--

INSERT INTO `homework` (`id`, `teacher_id`, `subject_id`, `title`, `description`, `due_date`, `file_path`, `is_active`, `created_at`, `updated_at`) VALUES
(101, 201, 1, 'واجب اللغة العربية الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس اللغة العربية ورفع الإجابة قبل موعد التسليم.', '2026-08-15', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(102, 201, 1, 'واجب اللغة العربية الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس اللغة العربية ورفع الإجابة قبل موعد التسليم.', '2026-08-16', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(103, 202, 2, 'واجب اللغة الإنجليزية الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس اللغة الإنجليزية ورفع الإجابة قبل موعد التسليم.', '2026-08-17', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(104, 202, 2, 'واجب اللغة الإنجليزية الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس اللغة الإنجليزية ورفع الإجابة قبل موعد التسليم.', '2026-08-18', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(105, 203, 3, 'واجب الرياضيات الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الرياضيات ورفع الإجابة قبل موعد التسليم.', '2026-08-19', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(106, 203, 3, 'واجب الرياضيات الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الرياضيات ورفع الإجابة قبل موعد التسليم.', '2026-08-20', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(107, 204, 4, 'واجب الفيزياء الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الفيزياء ورفع الإجابة قبل موعد التسليم.', '2026-08-21', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(108, 204, 4, 'واجب الفيزياء الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الفيزياء ورفع الإجابة قبل موعد التسليم.', '2026-08-22', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(109, 205, 5, 'واجب الكيمياء الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الكيمياء ورفع الإجابة قبل موعد التسليم.', '2026-08-23', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(110, 205, 5, 'واجب الكيمياء الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الكيمياء ورفع الإجابة قبل موعد التسليم.', '2026-08-24', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(111, 206, 6, 'واجب الأحياء الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الأحياء ورفع الإجابة قبل موعد التسليم.', '2026-08-15', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(112, 206, 6, 'واجب الأحياء الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الأحياء ورفع الإجابة قبل موعد التسليم.', '2026-08-16', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(113, 207, 7, 'واجب التاريخ الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس التاريخ ورفع الإجابة قبل موعد التسليم.', '2026-08-17', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(114, 207, 7, 'واجب التاريخ الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس التاريخ ورفع الإجابة قبل موعد التسليم.', '2026-08-18', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(115, 208, 8, 'واجب الجغرافيا الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الجغرافيا ورفع الإجابة قبل موعد التسليم.', '2026-08-19', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(116, 208, 8, 'واجب الجغرافيا الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الجغرافيا ورفع الإجابة قبل موعد التسليم.', '2026-08-20', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(117, 209, 9, 'واجب الفلسفة والمنطق الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الفلسفة والمنطق ورفع الإجابة قبل موعد التسليم.', '2026-08-21', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(118, 209, 9, 'واجب الفلسفة والمنطق الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس الفلسفة والمنطق ورفع الإجابة قبل موعد التسليم.', '2026-08-22', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(119, 210, 10, 'واجب علوم الحاسب الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس علوم الحاسب ورفع الإجابة قبل موعد التسليم.', '2026-08-23', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(120, 210, 10, 'واجب علوم الحاسب الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس علوم الحاسب ورفع الإجابة قبل موعد التسليم.', '2026-08-24', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(121, 211, 11, 'واجب اللغة الفرنسية الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس اللغة الفرنسية ورفع الإجابة قبل موعد التسليم.', '2026-08-15', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(122, 211, 11, 'واجب اللغة الفرنسية الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس اللغة الفرنسية ورفع الإجابة قبل موعد التسليم.', '2026-08-16', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(123, 212, 15, 'واجب العلوم المتكاملة الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس العلوم المتكاملة ورفع الإجابة قبل موعد التسليم.', '2026-08-17', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00'),
(124, 212, 15, 'واجب العلوم المتكاملة الأسبوعي', 'حل التدريبات المرتبطة بالوحدة الثانية من كورس العلوم المتكاملة ورفع الإجابة قبل موعد التسليم.', '2026-08-18', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 1, '2026-08-10 13:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `homework_submissions`
--

CREATE TABLE `homework_submissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `homework_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `status` enum('submitted','reviewed') NOT NULL DEFAULT 'submitted',
  `feedback_text` text DEFAULT NULL,
  `feedback_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `homework_submissions`
--

INSERT INTO `homework_submissions` (`id`, `homework_id`, `student_id`, `file_path`, `status`, `feedback_text`, `feedback_rating`, `submitted_at`, `reviewed_at`) VALUES
(101, 101, 1001, 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(102, 102, 1002, 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(103, 103, 1003, 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(104, 104, 1004, 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(105, 105, 1005, 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(106, 106, 1006, 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(107, 107, 1007, 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(108, 108, 1008, 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(109, 109, 1009, 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(110, 110, 1010, 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(111, 111, 1011, 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(112, 112, 1012, 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(113, 113, 1013, 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(114, 114, 1014, 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(115, 115, 1015, 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(116, 116, 1016, 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(117, 117, 1017, 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(118, 118, 1018, 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(119, 119, 1019, 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(120, 120, 1020, 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(121, 121, 1021, 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(122, 122, 1022, 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(123, 123, 1023, 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(124, 124, 1024, 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(125, 101, 1025, 'uploads/avatars/img_6a7b147ba6a738.13838434.pdf', 'submitted', NULL, NULL, '2026-08-11 12:24:27', NULL),
(126, 102, 1026, 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(127, 103, 1027, 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(128, 104, 1028, 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(129, 105, 1029, 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(130, 106, 1030, 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(131, 107, 1031, 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(132, 108, 1032, 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(133, 109, 1033, 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(134, 110, 1034, 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(135, 111, 1035, 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(136, 112, 1036, 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(137, 113, 1037, 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(138, 114, 1038, 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(139, 115, 1039, 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(140, 116, 1040, 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(141, 117, 1041, 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(142, 118, 1042, 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(143, 119, 1043, 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(144, 120, 1044, 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(145, 121, 1045, 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(146, 122, 1046, 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(147, 123, 1047, 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(148, 124, 1048, 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(149, 101, 1049, 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(150, 102, 1050, 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(151, 103, 1051, 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(152, 104, 1052, 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(153, 105, 1053, 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(154, 106, 1054, 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(155, 107, 1055, 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(156, 108, 1056, 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(157, 109, 1057, 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(158, 110, 1058, 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'submitted', NULL, NULL, '2026-08-10 14:00:00', NULL),
(159, 111, 1059, 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(160, 112, 1060, 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'reviewed', 'إجابة مرتبة. استمر مع مراجعة السؤال الأخير.', 5, '2026-08-10 14:00:00', '2026-08-10 15:30:00'),
(161, 102, 1025, 'uploads/avatars/img_6a7b101593d0c5.09652130.jpg', 'reviewed', 'حسن خطك شويه', 4, '2026-08-11 12:05:41', '2026-08-12 14:16:20');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `direction` enum('rtl','ltr') NOT NULL DEFAULT 'ltr',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `code`, `direction`, `is_default`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ar', 'rtl', 1, 1, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'en', 'ltr', 0, 2, 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `languages_translations`
--

CREATE TABLE `languages_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `translated_language_id` smallint(5) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `languages_translations`
--

INSERT INTO `languages_translations` (`id`, `translated_language_id`, `language_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'العربية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Arabic', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'الإنجليزية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'English', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `mcq_options`
--

CREATE TABLE `mcq_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `option_key` varchar(10) DEFAULT NULL,
  `option_image` varchar(500) DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mcq_options`
--

INSERT INTO `mcq_options` (`id`, `question_id`, `option_key`, `option_image`, `is_correct`, `is_active`, `created_at`, `updated_at`) VALUES
(10001, 5001, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10002, 5001, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10003, 5001, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10004, 5001, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10005, 5002, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10006, 5002, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10007, 5002, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10008, 5002, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10009, 5003, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10010, 5003, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10011, 5003, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10012, 5003, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10013, 5006, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10014, 5006, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10015, 5006, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10016, 5006, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10017, 5007, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10018, 5007, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10019, 5007, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10020, 5007, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10021, 5008, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10022, 5008, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10023, 5008, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10024, 5008, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10025, 5011, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10026, 5011, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10027, 5011, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10028, 5011, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10029, 5012, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10030, 5012, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10031, 5012, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10032, 5012, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10033, 5013, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10034, 5013, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10035, 5013, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10036, 5013, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10037, 5016, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10038, 5016, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10039, 5016, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10040, 5016, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10041, 5017, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10042, 5017, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10043, 5017, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10044, 5017, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10045, 5018, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10046, 5018, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10047, 5018, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10048, 5018, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10049, 5021, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10050, 5021, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10051, 5021, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10052, 5021, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10053, 5022, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10054, 5022, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10055, 5022, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10056, 5022, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10057, 5023, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10058, 5023, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10059, 5023, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10060, 5023, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10061, 5026, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10062, 5026, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10063, 5026, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10064, 5026, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10065, 5027, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10066, 5027, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10067, 5027, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10068, 5027, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10069, 5028, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10070, 5028, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10071, 5028, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10072, 5028, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10073, 5031, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10074, 5031, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10075, 5031, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10076, 5031, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10077, 5032, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10078, 5032, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10079, 5032, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10080, 5032, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10081, 5033, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10082, 5033, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10083, 5033, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10084, 5033, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10085, 5036, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10086, 5036, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10087, 5036, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10088, 5036, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10089, 5037, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10090, 5037, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10091, 5037, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10092, 5037, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10093, 5038, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10094, 5038, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10095, 5038, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10096, 5038, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10097, 5041, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10098, 5041, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10099, 5041, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10100, 5041, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10101, 5042, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10102, 5042, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10103, 5042, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10104, 5042, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10105, 5043, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10106, 5043, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10107, 5043, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10108, 5043, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10109, 5046, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10110, 5046, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10111, 5046, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10112, 5046, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10113, 5047, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10114, 5047, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10115, 5047, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10116, 5047, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10117, 5048, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10118, 5048, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10119, 5048, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10120, 5048, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10121, 5051, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10122, 5051, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10123, 5051, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10124, 5051, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10125, 5052, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10126, 5052, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10127, 5052, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10128, 5052, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10129, 5053, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10130, 5053, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10131, 5053, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10132, 5053, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10133, 5056, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10134, 5056, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10135, 5056, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10136, 5056, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10137, 5057, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10138, 5057, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10139, 5057, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10140, 5057, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10141, 5058, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10142, 5058, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10143, 5058, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10144, 5058, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10145, 5061, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10146, 5061, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10147, 5061, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10148, 5061, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10149, 5062, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10150, 5062, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10151, 5062, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10152, 5062, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10153, 5063, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10154, 5063, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10155, 5063, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10156, 5063, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10157, 5066, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10158, 5066, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10159, 5066, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10160, 5066, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10161, 5067, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10162, 5067, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10163, 5067, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10164, 5067, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10165, 5068, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10166, 5068, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10167, 5068, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10168, 5068, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10169, 5071, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10170, 5071, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10171, 5071, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10172, 5071, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10173, 5072, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10174, 5072, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10175, 5072, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10176, 5072, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10177, 5073, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10178, 5073, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10179, 5073, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10180, 5073, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10181, 5076, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10182, 5076, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10183, 5076, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10184, 5076, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10185, 5077, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10186, 5077, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10187, 5077, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10188, 5077, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10189, 5078, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10190, 5078, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10191, 5078, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10192, 5078, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10193, 5081, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10194, 5081, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10195, 5081, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10196, 5081, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10197, 5082, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10198, 5082, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10199, 5082, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10200, 5082, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10201, 5083, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10202, 5083, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10203, 5083, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10204, 5083, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10205, 5086, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10206, 5086, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10207, 5086, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10208, 5086, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10209, 5087, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10210, 5087, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10211, 5087, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10212, 5087, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10213, 5088, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10214, 5088, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10215, 5088, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10216, 5088, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10217, 5091, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10218, 5091, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10219, 5091, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10220, 5091, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10221, 5092, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10222, 5092, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10223, 5092, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10224, 5092, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10225, 5093, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10226, 5093, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10227, 5093, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10228, 5093, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10229, 5096, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10230, 5096, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10231, 5096, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10232, 5096, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10233, 5097, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10234, 5097, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10235, 5097, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10236, 5097, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10237, 5098, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10238, 5098, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10239, 5098, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10240, 5098, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10241, 5101, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10242, 5101, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10243, 5101, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10244, 5101, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10245, 5102, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10246, 5102, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10247, 5102, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10248, 5102, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10249, 5103, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10250, 5103, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10251, 5103, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10252, 5103, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10253, 5106, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10254, 5106, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10255, 5106, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10256, 5106, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10257, 5107, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10258, 5107, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10259, 5107, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10260, 5107, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10261, 5108, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10262, 5108, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10263, 5108, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10264, 5108, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10265, 5111, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10266, 5111, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10267, 5111, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10268, 5111, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10269, 5112, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10270, 5112, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10271, 5112, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10272, 5112, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10273, 5113, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10274, 5113, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10275, 5113, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10276, 5113, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10277, 5116, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10278, 5116, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10279, 5116, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10280, 5116, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10281, 5117, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10282, 5117, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10283, 5117, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10284, 5117, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10285, 5118, 'A', NULL, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10286, 5118, 'B', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10287, 5118, 'C', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(10288, 5118, 'D', NULL, 0, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `mcq_options_translations`
--

CREATE TABLE `mcq_options_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mcq_option_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `option_text` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mcq_options_translations`
--

INSERT INTO `mcq_options_translations` (`id`, `mcq_option_id`, `language_id`, `option_text`, `is_active`, `created_at`, `updated_at`) VALUES
(20001, 10001, 1, 'اسم', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20002, 10001, 2, 'Noun', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20003, 10002, 1, 'فعل', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20004, 10002, 2, 'Verb', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20005, 10003, 1, 'حرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20006, 10003, 2, 'Particle', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20007, 10004, 1, 'ظرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20008, 10004, 2, 'Adverb', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20009, 10005, 1, 'التشبيه', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20010, 10005, 2, 'Simile', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20011, 10006, 1, 'الجناس', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20012, 10006, 2, 'Paronomasia', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20013, 10007, 1, 'السجع', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20014, 10007, 2, 'Rhymed prose', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20015, 10008, 1, 'الطباق', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20016, 10008, 2, 'Antithesis', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20017, 10009, 1, 'الضمة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20018, 10009, 2, 'Damma', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20019, 10010, 1, 'الفتحة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20020, 10010, 2, 'Fatha', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20021, 10011, 1, 'الكسرة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20022, 10011, 2, 'Kasra', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20023, 10012, 1, 'السكون', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20024, 10012, 2, 'Sukun', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20025, 10013, 1, 'اسم', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20026, 10013, 2, 'Noun', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20027, 10014, 1, 'فعل', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20028, 10014, 2, 'Verb', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20029, 10015, 1, 'حرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20030, 10015, 2, 'Particle', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20031, 10016, 1, 'ظرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20032, 10016, 2, 'Adverb', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20033, 10017, 1, 'التشبيه', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20034, 10017, 2, 'Simile', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20035, 10018, 1, 'الجناس', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20036, 10018, 2, 'Paronomasia', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20037, 10019, 1, 'السجع', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20038, 10019, 2, 'Rhymed prose', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20039, 10020, 1, 'الطباق', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20040, 10020, 2, 'Antithesis', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20041, 10021, 1, 'الضمة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20042, 10021, 2, 'Damma', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20043, 10022, 1, 'الفتحة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20044, 10022, 2, 'Fatha', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20045, 10023, 1, 'الكسرة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20046, 10023, 2, 'Kasra', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20047, 10024, 1, 'السكون', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20048, 10024, 2, 'Sukun', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20049, 10025, 1, 'went', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20050, 10025, 2, 'went', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20051, 10026, 1, 'goed', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20052, 10026, 2, 'goed', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20053, 10027, 1, 'gone', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20054, 10027, 2, 'gone', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20055, 10028, 1, 'goes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20056, 10028, 2, 'goes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20057, 10029, 1, 'beautiful', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20058, 10029, 2, 'beautiful', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20059, 10030, 1, 'quickly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20060, 10030, 2, 'quickly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20061, 10031, 1, 'run', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20062, 10031, 2, 'run', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20063, 10032, 1, 'and', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20064, 10032, 2, 'and', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20065, 10033, 1, 'studies', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20066, 10033, 2, 'studies', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20067, 10034, 1, 'study', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20068, 10034, 2, 'study', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20069, 10035, 1, 'studying', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20070, 10035, 2, 'studying', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20071, 10036, 1, 'studied', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20072, 10036, 2, 'studied', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20073, 10037, 1, 'went', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20074, 10037, 2, 'went', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20075, 10038, 1, 'goed', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20076, 10038, 2, 'goed', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20077, 10039, 1, 'gone', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20078, 10039, 2, 'gone', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20079, 10040, 1, 'goes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20080, 10040, 2, 'goes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20081, 10041, 1, 'beautiful', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20082, 10041, 2, 'beautiful', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20083, 10042, 1, 'quickly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20084, 10042, 2, 'quickly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20085, 10043, 1, 'run', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20086, 10043, 2, 'run', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20087, 10044, 1, 'and', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20088, 10044, 2, 'and', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20089, 10045, 1, 'studies', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20090, 10045, 2, 'studies', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20091, 10046, 1, 'study', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20092, 10046, 2, 'study', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20093, 10047, 1, 'studying', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20094, 10047, 2, 'studying', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20095, 10048, 1, 'studied', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20096, 10048, 2, 'studied', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20097, 10049, 1, '4', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20098, 10049, 2, '4', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20099, 10050, 1, '3', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20100, 10050, 2, '3', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20101, 10051, 1, '5', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20102, 10051, 2, '5', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20103, 10052, 1, '7', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20104, 10052, 2, '7', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20105, 10053, 1, '0', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20106, 10053, 2, '0', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20107, 10054, 1, '1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20108, 10054, 2, '1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20109, 10055, 1, '-1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20110, 10055, 2, '-1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20111, 10056, 1, 'غير معرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20112, 10056, 2, 'غير معرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20113, 10057, 1, '180°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20114, 10057, 2, '180°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20115, 10058, 1, '90°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20116, 10058, 2, '90°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20117, 10059, 1, '270°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20118, 10059, 2, '270°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20119, 10060, 1, '360°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20120, 10060, 2, '360°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20121, 10061, 1, '4', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20122, 10061, 2, '4', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20123, 10062, 1, '3', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20124, 10062, 2, '3', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20125, 10063, 1, '5', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20126, 10063, 2, '5', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20127, 10064, 1, '7', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20128, 10064, 2, '7', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20129, 10065, 1, '0', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20130, 10065, 2, '0', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20131, 10066, 1, '1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20132, 10066, 2, '1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20133, 10067, 1, '-1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20134, 10067, 2, '-1', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20135, 10068, 1, 'غير معرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20136, 10068, 2, 'غير معرف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20137, 10069, 1, '180°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20138, 10069, 2, '180°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20139, 10070, 1, '90°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20140, 10070, 2, '90°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20141, 10071, 1, '270°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20142, 10071, 2, '270°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20143, 10072, 1, '360°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20144, 10072, 2, '360°', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20145, 10073, 1, 'نيوتن', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20146, 10073, 2, 'Newton', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20147, 10074, 1, 'جول', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20148, 10074, 2, 'Joule', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20149, 10075, 1, 'وات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20150, 10075, 2, 'Watt', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20151, 10076, 1, 'باسكال', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20152, 10076, 2, 'Pascal', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20153, 10077, 1, 'القوة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20154, 10077, 2, 'Force', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20155, 10078, 1, 'الطاقة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20156, 10078, 2, 'Energy', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20157, 10079, 1, 'القدرة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20158, 10079, 2, 'Power', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20159, 10080, 1, 'الضغط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20160, 10080, 2, 'Pressure', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20161, 10081, 1, '3×10^8 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20162, 10081, 2, '3×10^8 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20163, 10082, 1, '3×10^6 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20164, 10082, 2, '3×10^6 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20165, 10083, 1, '300 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20166, 10083, 2, '300 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20167, 10084, 1, '30 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20168, 10084, 2, '30 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20169, 10085, 1, 'نيوتن', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20170, 10085, 2, 'Newton', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20171, 10086, 1, 'جول', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20172, 10086, 2, 'Joule', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20173, 10087, 1, 'وات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20174, 10087, 2, 'Watt', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20175, 10088, 1, 'باسكال', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20176, 10088, 2, 'Pascal', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20177, 10089, 1, 'القوة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20178, 10089, 2, 'Force', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20179, 10090, 1, 'الطاقة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20180, 10090, 2, 'Energy', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20181, 10091, 1, 'القدرة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20182, 10091, 2, 'Power', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20183, 10092, 1, 'الضغط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20184, 10092, 2, 'Pressure', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20185, 10093, 1, '3×10^8 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20186, 10093, 2, '3×10^8 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20187, 10094, 1, '3×10^6 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20188, 10094, 2, '3×10^6 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20189, 10095, 1, '300 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20190, 10095, 2, '300 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20191, 10096, 1, '30 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20192, 10096, 2, '30 m/s', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20193, 10097, 1, 'البروتونات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20194, 10097, 2, 'Protons', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20195, 10098, 1, 'النيوترونات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20196, 10098, 2, 'Neutrons', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20197, 10099, 1, 'المدارات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20198, 10099, 2, 'Orbitals', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20199, 10100, 1, 'الجزيئات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20200, 10100, 2, 'Molecules', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20201, 10101, 1, 'حمضي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20202, 10101, 2, 'Acidic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20203, 10102, 1, 'قاعدي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20204, 10102, 2, 'Basic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20205, 10103, 1, 'متعادل', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20206, 10103, 2, 'Neutral', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20207, 10104, 1, 'ملحي فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20208, 10104, 2, 'Salt-only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20209, 10105, 1, 'تساهمية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20210, 10105, 2, 'Covalent', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20211, 10106, 1, 'أيونية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20212, 10106, 2, 'Ionic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20213, 10107, 1, 'فلزية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20214, 10107, 2, 'Metallic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20215, 10108, 1, 'هيدروجينية فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20216, 10108, 2, 'Hydrogen only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20217, 10109, 1, 'البروتونات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20218, 10109, 2, 'Protons', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20219, 10110, 1, 'النيوترونات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20220, 10110, 2, 'Neutrons', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20221, 10111, 1, 'المدارات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20222, 10111, 2, 'Orbitals', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20223, 10112, 1, 'الجزيئات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20224, 10112, 2, 'Molecules', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20225, 10113, 1, 'حمضي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20226, 10113, 2, 'Acidic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20227, 10114, 1, 'قاعدي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20228, 10114, 2, 'Basic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20229, 10115, 1, 'متعادل', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20230, 10115, 2, 'Neutral', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20231, 10116, 1, 'ملحي فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20232, 10116, 2, 'Salt-only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20233, 10117, 1, 'تساهمية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20234, 10117, 2, 'Covalent', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20235, 10118, 1, 'أيونية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20236, 10118, 2, 'Ionic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20237, 10119, 1, 'فلزية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20238, 10119, 2, 'Metallic', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20239, 10120, 1, 'هيدروجينية فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20240, 10120, 2, 'Hydrogen only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20241, 10121, 1, 'الخلية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20242, 10121, 2, 'Cell', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20243, 10122, 1, 'النسيج', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20244, 10122, 2, 'Tissue', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20245, 10123, 1, 'العضو', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20246, 10123, 2, 'Organ', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20247, 10124, 1, 'الجهاز', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20248, 10124, 2, 'System', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20249, 10125, 1, 'الميتوكوندريا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20250, 10125, 2, 'Mitochondria', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20251, 10126, 1, 'النواة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20252, 10126, 2, 'Nucleus', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20253, 10127, 1, 'الريبوسوم', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20254, 10127, 2, 'Ribosome', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20255, 10128, 1, 'جولجي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20256, 10128, 2, 'Golgi', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20257, 10129, 1, 'المعلومات الوراثية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20258, 10129, 2, 'Genetic information', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20259, 10130, 1, 'الأكسجين', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20260, 10130, 2, 'Oxygen', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20261, 10131, 1, 'الطاقة الحرارية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20262, 10131, 2, 'Thermal energy', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20263, 10132, 1, 'الماء', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20264, 10132, 2, 'Water', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20265, 10133, 1, 'الخلية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20266, 10133, 2, 'Cell', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20267, 10134, 1, 'النسيج', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20268, 10134, 2, 'Tissue', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20269, 10135, 1, 'العضو', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20270, 10135, 2, 'Organ', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20271, 10136, 1, 'الجهاز', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20272, 10136, 2, 'System', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20273, 10137, 1, 'الميتوكوندريا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20274, 10137, 2, 'Mitochondria', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20275, 10138, 1, 'النواة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20276, 10138, 2, 'Nucleus', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20277, 10139, 1, 'الريبوسوم', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20278, 10139, 2, 'Ribosome', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20279, 10140, 1, 'جولجي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20280, 10140, 2, 'Golgi', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20281, 10141, 1, 'المعلومات الوراثية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20282, 10141, 2, 'Genetic information', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20283, 10142, 1, 'الأكسجين', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20284, 10142, 2, 'Oxygen', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20285, 10143, 1, 'الطاقة الحرارية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20286, 10143, 2, 'Thermal energy', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20287, 10144, 1, 'الماء', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20288, 10144, 2, 'Water', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20289, 10145, 1, 'وثيقة معاصرة للحدث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20290, 10145, 2, 'وثيقة معاصرة للحدث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20291, 10146, 1, 'كتاب مدرسي حديث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20292, 10146, 2, 'كتاب مدرسي حديث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20293, 10147, 1, 'ملخص إلكتروني', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20294, 10147, 2, 'ملخص إلكتروني', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20295, 10148, 1, 'رأي متأخر', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20296, 10148, 2, 'رأي متأخر', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20297, 10149, 1, 'ترتيب الأحداث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20298, 10149, 2, 'ترتيب الأحداث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20299, 10150, 1, 'قياس المسافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20300, 10150, 2, 'قياس المسافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20301, 10151, 1, 'حساب الكثافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20302, 10151, 2, 'حساب الكثافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20303, 10152, 1, 'تحليل الجينات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20304, 10152, 2, 'تحليل الجينات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20305, 10153, 1, 'مصادر متعددة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20306, 10153, 2, 'مصادر متعددة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20307, 10154, 1, 'مصدر واحد فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20308, 10154, 2, 'مصدر واحد فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20309, 10155, 1, 'الشائعات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20310, 10155, 2, 'الشائعات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20311, 10156, 1, 'الصور دون سياق', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20312, 10156, 2, 'الصور دون سياق', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20313, 10157, 1, 'وثيقة معاصرة للحدث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20314, 10157, 2, 'وثيقة معاصرة للحدث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20315, 10158, 1, 'كتاب مدرسي حديث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20316, 10158, 2, 'كتاب مدرسي حديث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20317, 10159, 1, 'ملخص إلكتروني', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20318, 10159, 2, 'ملخص إلكتروني', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20319, 10160, 1, 'رأي متأخر', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20320, 10160, 2, 'رأي متأخر', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20321, 10161, 1, 'ترتيب الأحداث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20322, 10161, 2, 'ترتيب الأحداث', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20323, 10162, 1, 'قياس المسافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20324, 10162, 2, 'قياس المسافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20325, 10163, 1, 'حساب الكثافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20326, 10163, 2, 'حساب الكثافة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20327, 10164, 1, 'تحليل الجينات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20328, 10164, 2, 'تحليل الجينات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20329, 10165, 1, 'مصادر متعددة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20330, 10165, 2, 'مصادر متعددة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20331, 10166, 1, 'مصدر واحد فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20332, 10166, 2, 'مصدر واحد فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20333, 10167, 1, 'الشائعات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20334, 10167, 2, 'الشائعات', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20335, 10168, 1, 'الصور دون سياق', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20336, 10168, 2, 'الصور دون سياق', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20337, 10169, 1, 'خط الاستواء', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20338, 10169, 2, 'Equator', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20339, 10170, 1, 'جرينتش', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20340, 10170, 2, 'Greenwich', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20341, 10171, 1, 'القطب فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20342, 10171, 2, 'القطب فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20343, 10172, 1, 'مدار الأرض', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20344, 10172, 2, 'مدار الأرض', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20345, 10173, 1, 'جرينتش', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20346, 10173, 2, 'Greenwich', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20347, 10174, 1, 'القاهرة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20348, 10174, 2, 'Cairo', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20349, 10175, 1, 'روما', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20350, 10175, 2, 'Rome', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20351, 10176, 1, 'باريس', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20352, 10176, 2, 'Paris', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20353, 10177, 1, 'عدد السكان لكل وحدة مساحة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20354, 10177, 2, 'عدد السكان لكل وحدة مساحة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20355, 10178, 1, 'إجمالي مساحة الدولة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20356, 10178, 2, 'إجمالي مساحة الدولة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20357, 10179, 1, 'عدد المدن', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20358, 10179, 2, 'عدد المدن', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20359, 10180, 1, 'معدل الأمطار', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20360, 10180, 2, 'معدل الأمطار', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20361, 10181, 1, 'خط الاستواء', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20362, 10181, 2, 'Equator', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20363, 10182, 1, 'جرينتش', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20364, 10182, 2, 'Greenwich', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20365, 10183, 1, 'القطب فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20366, 10183, 2, 'القطب فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20367, 10184, 1, 'مدار الأرض', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20368, 10184, 2, 'مدار الأرض', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20369, 10185, 1, 'جرينتش', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20370, 10185, 2, 'Greenwich', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20371, 10186, 1, 'القاهرة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20372, 10186, 2, 'Cairo', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20373, 10187, 1, 'روما', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20374, 10187, 2, 'Rome', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20375, 10188, 1, 'باريس', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20376, 10188, 2, 'Paris', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20377, 10189, 1, 'عدد السكان لكل وحدة مساحة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20378, 10189, 2, 'عدد السكان لكل وحدة مساحة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20379, 10190, 1, 'إجمالي مساحة الدولة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20380, 10190, 2, 'إجمالي مساحة الدولة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20381, 10191, 1, 'عدد المدن', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20382, 10191, 2, 'عدد المدن', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20383, 10192, 1, 'معدل الأمطار', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20384, 10192, 2, 'معدل الأمطار', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20385, 10193, 1, 'العام إلى الخاص', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20386, 10193, 2, 'General to specific', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20387, 10194, 1, 'الخاص إلى العام', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20388, 10194, 2, 'Specific to general', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20389, 10195, 1, 'النتيجة إلى البيانات فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20390, 10195, 2, 'النتيجة إلى البيانات فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20391, 10196, 1, 'الرأي إلى الشعور', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20392, 10196, 2, 'الرأي إلى الشعور', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20393, 10197, 1, 'نتيجة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20394, 10197, 2, 'Conclusion', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20395, 10198, 1, 'صورة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20396, 10198, 2, 'Image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20397, 10199, 1, 'عنوان', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20398, 10199, 2, 'Title', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20399, 10200, 1, 'سؤال فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20400, 10200, 2, 'Question only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20401, 10201, 1, 'خطأ في الاستدلال', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20402, 10201, 2, 'Reasoning error', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20403, 10202, 1, 'حقيقة علمية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20404, 10202, 2, 'Scientific fact', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20405, 10203, 1, 'تعريف محكم', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20406, 10203, 2, 'Precise definition', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20407, 10204, 1, 'برهان صحيح', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20408, 10204, 2, 'Valid proof', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20409, 10205, 1, 'العام إلى الخاص', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20410, 10205, 2, 'General to specific', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20411, 10206, 1, 'الخاص إلى العام', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20412, 10206, 2, 'Specific to general', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20413, 10207, 1, 'النتيجة إلى البيانات فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20414, 10207, 2, 'النتيجة إلى البيانات فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20415, 10208, 1, 'الرأي إلى الشعور', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20416, 10208, 2, 'الرأي إلى الشعور', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20417, 10209, 1, 'نتيجة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20418, 10209, 2, 'Conclusion', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20419, 10210, 1, 'صورة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20420, 10210, 2, 'Image', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20421, 10211, 1, 'عنوان', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20422, 10211, 2, 'Title', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20423, 10212, 1, 'سؤال فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20424, 10212, 2, 'Question only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20425, 10213, 1, 'خطأ في الاستدلال', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20426, 10213, 2, 'Reasoning error', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20427, 10214, 1, 'حقيقة علمية', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20428, 10214, 2, 'Scientific fact', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20429, 10215, 1, 'تعريف محكم', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20430, 10215, 2, 'Precise definition', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20431, 10216, 1, 'برهان صحيح', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20432, 10216, 2, 'Valid proof', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20433, 10217, 1, 'خطوات محددة لحل مشكلة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20434, 10217, 2, 'Finite steps to solve a problem', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20435, 10218, 1, 'نوع ملف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20436, 10218, 2, 'A file type', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20437, 10219, 1, 'لغة برمجة فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20438, 10219, 2, 'Only a programming language', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20439, 10220, 1, 'جهاز تخزين', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20440, 10220, 2, 'Storage device', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20441, 10221, 1, 'Dictionary/Map', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20442, 10221, 2, 'Dictionary/Map', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20443, 10222, 1, 'Stack only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20444, 10222, 2, 'Stack only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20445, 10223, 1, 'Integer', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20446, 10223, 2, 'Integer', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20447, 10224, 1, 'Loop', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20448, 10224, 2, 'Loop', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20449, 10225, 1, 'خطيًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20450, 10225, 2, 'Linearly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20451, 10226, 1, 'ثابتًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20452, 10226, 2, 'Constantly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20453, 10227, 1, 'تربيعيًا دائمًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20454, 10227, 2, 'Always quadratically', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20455, 10228, 1, 'عشوائيًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20456, 10228, 2, 'Randomly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20457, 10229, 1, 'خطوات محددة لحل مشكلة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20458, 10229, 2, 'Finite steps to solve a problem', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20459, 10230, 1, 'نوع ملف', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20460, 10230, 2, 'A file type', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20461, 10231, 1, 'لغة برمجة فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20462, 10231, 2, 'Only a programming language', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20463, 10232, 1, 'جهاز تخزين', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20464, 10232, 2, 'Storage device', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20465, 10233, 1, 'Dictionary/Map', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20466, 10233, 2, 'Dictionary/Map', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20467, 10234, 1, 'Stack only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20468, 10234, 2, 'Stack only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20469, 10235, 1, 'Integer', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20470, 10235, 2, 'Integer', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20471, 10236, 1, 'Loop', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20472, 10236, 2, 'Loop', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20473, 10237, 1, 'خطيًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20474, 10237, 2, 'Linearly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20475, 10238, 1, 'ثابتًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20476, 10238, 2, 'Constantly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20477, 10239, 1, 'تربيعيًا دائمًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20478, 10239, 2, 'Always quadratically', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20479, 10240, 1, 'عشوائيًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20480, 10240, 2, 'Randomly', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20481, 10241, 1, 'مرحبًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20482, 10241, 2, 'Hello', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20483, 10242, 1, 'شكرًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20484, 10242, 2, 'Thank you', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20485, 10243, 1, 'وداعًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20486, 10243, 2, 'Goodbye', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20487, 10244, 1, 'من فضلك', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20488, 10244, 2, 'Please', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20489, 10245, 1, 'le', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20490, 10245, 2, 'le', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20491, 10246, 1, 'la', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20492, 10246, 2, 'la', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20493, 10247, 1, 'lese', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20494, 10247, 2, 'lese', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20495, 10248, 1, 'une', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20496, 10248, 2, 'une', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20497, 10249, 1, 'suis', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20498, 10249, 2, 'suis', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20499, 10250, 1, 'es', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20500, 10250, 2, 'es', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20501, 10251, 1, 'êtes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20502, 10251, 2, 'êtes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20503, 10252, 1, 'sommes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20504, 10252, 2, 'sommes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20505, 10253, 1, 'مرحبًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20506, 10253, 2, 'Hello', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20507, 10254, 1, 'شكرًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20508, 10254, 2, 'Thank you', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20509, 10255, 1, 'وداعًا', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20510, 10255, 2, 'Goodbye', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20511, 10256, 1, 'من فضلك', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20512, 10256, 2, 'Please', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20513, 10257, 1, 'le', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20514, 10257, 2, 'le', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20515, 10258, 1, 'la', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20516, 10258, 2, 'la', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20517, 10259, 1, 'lese', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20518, 10259, 2, 'lese', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20519, 10260, 1, 'une', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20520, 10260, 2, 'une', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20521, 10261, 1, 'suis', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20522, 10261, 2, 'suis', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20523, 10262, 1, 'es', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20524, 10262, 2, 'es', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20525, 10263, 1, 'êtes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20526, 10263, 2, 'êtes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20527, 10264, 1, 'sommes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20528, 10264, 2, 'sommes', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20529, 10265, 1, '100°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20530, 10265, 2, '100°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20531, 10266, 1, '0°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20532, 10266, 2, '0°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20533, 10267, 1, '50°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20534, 10267, 2, '50°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20535, 10268, 1, '200°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20536, 10268, 2, '200°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20537, 10269, 1, 'السائل', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20538, 10269, 2, 'Liquid', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20539, 10270, 1, 'الصلب', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20540, 10270, 2, 'Solid', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20541, 10271, 1, 'الغاز فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20542, 10271, 2, 'Gas only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20543, 10272, 1, 'البلازما فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20544, 10272, 2, 'Plasma only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20545, 10273, 1, 'الحركة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20546, 10273, 2, 'Motion', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20547, 10274, 1, 'اللون', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20548, 10274, 2, 'Color', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20549, 10275, 1, 'الكتلة فقط دون حركة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20550, 10275, 2, 'Mass without motion', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20551, 10276, 1, 'الاسم الكيميائي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20552, 10276, 2, 'Chemical name', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20553, 10277, 1, '100°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20554, 10277, 2, '100°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20555, 10278, 1, '0°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20556, 10278, 2, '0°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20557, 10279, 1, '50°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20558, 10279, 2, '50°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20559, 10280, 1, '200°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20560, 10280, 2, '200°C', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20561, 10281, 1, 'السائل', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20562, 10281, 2, 'Liquid', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20563, 10282, 1, 'الصلب', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20564, 10282, 2, 'Solid', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20565, 10283, 1, 'الغاز فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20566, 10283, 2, 'Gas only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20567, 10284, 1, 'البلازما فقط', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20568, 10284, 2, 'Plasma only', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20569, 10285, 1, 'الحركة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20570, 10285, 2, 'Motion', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20571, 10286, 1, 'اللون', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20572, 10286, 2, 'Color', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20573, 10287, 1, 'الكتلة فقط دون حركة', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20574, 10287, 2, 'Mass without motion', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20575, 10288, 1, 'الاسم الكيميائي', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(20576, 10288, 2, 'Chemical name', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` longtext DEFAULT NULL,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `city_id`, `name`, `email`, `password`, `image`, `is_email_verified`, `is_active`, `created_at`, `updated_at`) VALUES
(301, 1, 'عبد الرحمن وائل شريف', 'parent301@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=2', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(302, 2, 'جنى رضا مصطفى', 'parent302@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=3', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(303, 3, 'أحمد أحمد سعيد', 'parent303@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=4', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(304, 4, 'حبيبة عمرو السيد', 'parent304@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=5', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(305, 5, 'علي شريف كمال', 'parent305@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=6', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(306, 6, 'ملك عماد حسن', 'parent306@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=7', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(307, 7, 'كريم أحمد صبري', 'parent307@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=8', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(308, 8, 'فاطمة سمير ناصر', 'parent308@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=9', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(309, 9, 'زياد خالد علي', 'parent309@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=10', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(310, 10, 'بسملة إبراهيم عبد الله', 'parent310@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=11', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(311, 11, 'محمود وائل شريف', 'parent311@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=12', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(312, 1, 'ندى رضا مصطفى', 'parent312@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=13', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(313, 2, 'مصطفى أحمد سعيد', 'parent313@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=14', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(314, 3, 'روان عمرو السيد', 'parent314@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=15', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(315, 4, 'ياسين شريف كمال', 'parent315@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=16', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(316, 5, 'فرح عماد حسن', 'parent316@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=17', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(317, 6, 'إياد أحمد صبري', 'parent317@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=18', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(318, 7, 'شهد سمير ناصر', 'parent318@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=19', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(319, 8, 'حمزة خالد علي', 'parent319@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=20', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(320, 9, 'لارا إبراهيم عبد الله', 'parent320@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=21', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `parent_students`
--

CREATE TABLE `parent_students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parent_students`
--

INSERT INTO `parent_students` (`id`, `parent_id`, `student_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1001, 301, 1001, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1002, 301, 1002, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1003, 301, 1003, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1004, 302, 1004, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1005, 302, 1005, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1006, 302, 1006, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1007, 303, 1007, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1008, 303, 1008, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1009, 303, 1009, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1010, 304, 1010, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1011, 304, 1011, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1012, 304, 1012, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1013, 305, 1013, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1014, 305, 1014, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1015, 305, 1015, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1016, 306, 1016, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1017, 306, 1017, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1018, 306, 1018, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1019, 307, 1019, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1020, 307, 1020, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1021, 307, 1021, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1022, 308, 1022, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1023, 308, 1023, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1024, 308, 1024, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1025, 309, 1025, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1026, 309, 1026, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1027, 309, 1027, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1028, 310, 1028, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1029, 310, 1029, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1030, 310, 1030, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1031, 311, 1031, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1032, 311, 1032, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1033, 311, 1033, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1034, 312, 1034, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1035, 312, 1035, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1036, 312, 1036, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1037, 313, 1037, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1038, 313, 1038, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1039, 313, 1039, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1040, 314, 1040, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1041, 314, 1041, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1042, 314, 1042, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1043, 315, 1043, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1044, 315, 1044, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1045, 315, 1045, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1046, 316, 1046, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1047, 316, 1047, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1048, 316, 1048, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1049, 317, 1049, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1050, 317, 1050, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1051, 317, 1051, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1052, 318, 1052, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1053, 318, 1053, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1054, 318, 1054, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1055, 319, 1055, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1056, 319, 1056, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1057, 319, 1057, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1058, 320, 1058, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1059, 320, 1059, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1060, 320, 1060, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_codes`
--

CREATE TABLE `password_reset_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_type` enum('student','teacher','parent','admin') NOT NULL DEFAULT 'student',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `code` varchar(10) NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_codes`
--

INSERT INTO `password_reset_codes` (`id`, `user_type`, `user_id`, `email`, `code`, `reset_token`, `expires_at`, `is_used`, `created_at`) VALUES
(1, 'student', 1002, 'student1002@example.com', '773920', 'demo-reset-student-1002', '2026-08-11 16:00:00', 0, '2026-08-10 16:00:00'),
(2, 'teacher', 202, 'teacher202@example.com', '228411', 'demo-reset-teacher-202', '2026-08-11 16:00:00', 1, '2026-08-10 16:00:00'),
(3, 'parent', 302, 'parent302@example.com', '540218', 'demo-reset-parent-302', '2026-08-11 16:00:00', 0, '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `item_type` enum('course','book') NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'EGP',
  `status` enum('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `easykash_ref` varchar(100) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `granted_ref_id` bigint(20) UNSIGNED DEFAULT NULL,
  `raw_callback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`) VALUES
(1, 'dashboard.view', 'View Dashboard', 'dashboard', 'Access dashboard metrics', '2026-08-10 16:00:00'),
(2, 'students.manage', 'Manage Students', 'students', 'Create, edit and review student accounts', '2026-08-10 16:00:00'),
(3, 'teachers.manage', 'Manage Teachers', 'teachers', 'Create, edit and review teacher accounts', '2026-08-10 16:00:00'),
(4, 'courses.manage', 'Manage Courses', 'courses', 'Manage courses, units and videos', '2026-08-10 16:00:00'),
(5, 'exams.manage', 'Manage Exams', 'exams', 'Manage exams, questions and attempts', '2026-08-10 16:00:00'),
(6, 'subscriptions.manage', 'Manage Subscriptions', 'subscriptions', 'Manage subscriptions and activation codes', '2026-08-10 16:00:00'),
(7, 'transactions.manage', 'Manage Transactions', 'transactions', 'Review payments and transaction records', '2026-08-10 16:00:00'),
(8, 'content.manage', 'Manage Resources', 'content', 'Manage books, files, banners and advertisements', '2026-08-10 16:00:00'),
(9, 'reports.view', 'View Reports', 'reports', 'Access operational and student reports', '2026-08-10 16:00:00'),
(10, 'support.manage', 'Manage Support', 'support', 'Handle consultations and reschedule requests', '2026-08-10 16:00:00'),
(11, 'settings.manage', 'Manage Settings', 'settings', 'Manage site configuration', '2026-08-10 16:00:00'),
(12, 'roles.manage', 'Manage Roles', 'security', 'Manage roles and permissions', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `permissions_translations`
--

CREATE TABLE `permissions_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions_translations`
--

INSERT INTO `permissions_translations` (`id`, `permission_id`, `language_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'عرض لوحة التحكم', 'Access dashboard metrics', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'View Dashboard', 'Access dashboard metrics', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'إدارة الطلاب', 'Create, edit and review student accounts', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'Manage Students', 'Create, edit and review student accounts', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'إدارة المدرسين', 'Create, edit and review teacher accounts', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Manage Teachers', 'Create, edit and review teacher accounts', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 4, 1, 'إدارة الكورسات', 'Manage courses, units and videos', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 4, 2, 'Manage Courses', 'Manage courses, units and videos', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 5, 1, 'إدارة الامتحانات', 'Manage exams, questions and attempts', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 5, 2, 'Manage Exams', 'Manage exams, questions and attempts', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 6, 1, 'إدارة الاشتراكات', 'Manage subscriptions and activation codes', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 6, 2, 'Manage Subscriptions', 'Manage subscriptions and activation codes', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(13, 7, 1, 'إدارة المعاملات', 'Review payments and transaction records', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(14, 7, 2, 'Manage Transactions', 'Review payments and transaction records', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(15, 8, 1, 'إدارة المحتوى', 'Manage books, files, banners and advertisements', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(16, 8, 2, 'Manage Resources', 'Manage books, files, banners and advertisements', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(17, 9, 1, 'عرض التقارير', 'Access operational and student reports', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(18, 9, 2, 'View Reports', 'Access operational and student reports', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(19, 10, 1, 'إدارة الدعم', 'Handle consultations and reschedule requests', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(20, 10, 2, 'Manage Support', 'Handle consultations and reschedule requests', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(21, 11, 1, 'إدارة الإعدادات', 'Manage site configuration', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(22, 11, 2, 'Manage Settings', 'Manage site configuration', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(23, 12, 1, 'إدارة الأدوار والصلاحيات', 'Manage roles and permissions', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(24, 12, 2, 'Manage Roles', 'Manage roles and permissions', '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `question_type` enum('mcq','true_false','written') NOT NULL,
  `score` decimal(8,2) NOT NULL DEFAULT 1.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subject_id` int(11) DEFAULT NULL,
  `created_by_type` varchar(80) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `unit_id`, `question_type`, `score`, `is_active`, `created_at`, `updated_at`, `subject_id`, `created_by_type`, `created_by_id`) VALUES
(5001, 601, 201, 1003, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-12 14:28:02', 1, 'teacher', 201),
(5002, 601, 201, 1003, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-12 14:29:43', 1, 'teacher', 1),
(5003, 601, 201, 1003, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5004, 601, 201, 1003, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5005, 601, 201, 1003, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5006, 602, 202, 1006, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5007, 602, 202, 1006, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5008, 602, 202, 1006, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5009, 602, 202, 1006, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5010, 602, 202, 1006, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 1, 'admin', 1),
(5011, 603, 203, 1009, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5012, 603, 203, 1009, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5013, 603, 203, 1009, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5014, 603, 203, 1009, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5015, 603, 203, 1009, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5016, 604, 204, 1012, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5017, 604, 204, 1012, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5018, 604, 204, 1012, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5019, 604, 204, 1012, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5020, 604, 204, 1012, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 2, 'admin', 1),
(5021, 605, 205, 1015, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5022, 605, 205, 1015, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5023, 605, 205, 1015, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5024, 605, 205, 1015, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5025, 605, 205, 1015, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5026, 606, 206, 1018, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5027, 606, 206, 1018, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5028, 606, 206, 1018, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5029, 606, 206, 1018, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5030, 606, 206, 1018, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 3, 'admin', 1),
(5031, 607, 207, 1021, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5032, 607, 207, 1021, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5033, 607, 207, 1021, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5034, 607, 207, 1021, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5035, 607, 207, 1021, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5036, 608, 208, 1024, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5037, 608, 208, 1024, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5038, 608, 208, 1024, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5039, 608, 208, 1024, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5040, 608, 208, 1024, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 4, 'admin', 1),
(5041, 609, 209, 1027, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5042, 609, 209, 1027, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5043, 609, 209, 1027, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5044, 609, 209, 1027, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5045, 609, 209, 1027, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5046, 610, 210, 1030, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5047, 610, 210, 1030, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5048, 610, 210, 1030, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5049, 610, 210, 1030, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5050, 610, 210, 1030, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 5, 'admin', 1),
(5051, 611, 211, 1033, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5052, 611, 211, 1033, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5053, 611, 211, 1033, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5054, 611, 211, 1033, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5055, 611, 211, 1033, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5056, 612, 212, 1036, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5057, 612, 212, 1036, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5058, 612, 212, 1036, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5059, 612, 212, 1036, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5060, 612, 212, 1036, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 6, 'admin', 1),
(5061, 613, 213, 1039, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5062, 613, 213, 1039, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5063, 613, 213, 1039, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5064, 613, 213, 1039, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5065, 613, 213, 1039, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5066, 614, 214, 1042, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5067, 614, 214, 1042, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5068, 614, 214, 1042, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5069, 614, 214, 1042, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5070, 614, 214, 1042, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 7, 'admin', 1),
(5071, 615, 215, 1045, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5072, 615, 215, 1045, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5073, 615, 215, 1045, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5074, 615, 215, 1045, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5075, 615, 215, 1045, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5076, 616, 216, 1048, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5077, 616, 216, 1048, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5078, 616, 216, 1048, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5079, 616, 216, 1048, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5080, 616, 216, 1048, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 8, 'admin', 1),
(5081, 617, 217, 1051, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5082, 617, 217, 1051, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5083, 617, 217, 1051, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5084, 617, 217, 1051, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5085, 617, 217, 1051, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5086, 618, 218, 1054, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5087, 618, 218, 1054, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5088, 618, 218, 1054, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5089, 618, 218, 1054, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5090, 618, 218, 1054, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 9, 'admin', 1),
(5091, 619, 219, 1057, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5092, 619, 219, 1057, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5093, 619, 219, 1057, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5094, 619, 219, 1057, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5095, 619, 219, 1057, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5096, 620, 220, 1060, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5097, 620, 220, 1060, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5098, 620, 220, 1060, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5099, 620, 220, 1060, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5100, 620, 220, 1060, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 10, 'admin', 1),
(5101, 621, 221, 1063, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5102, 621, 221, 1063, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5103, 621, 221, 1063, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5104, 621, 221, 1063, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5105, 621, 221, 1063, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5106, 622, 222, 1066, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5107, 622, 222, 1066, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5108, 622, 222, 1066, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5109, 622, 222, 1066, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5110, 622, 222, 1066, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 11, 'admin', 1),
(5111, 623, 223, 1069, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5112, 623, 223, 1069, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5113, 623, 223, 1069, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5114, 623, 223, 1069, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5115, 623, 223, 1069, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5116, 624, 224, 1072, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5117, 624, 224, 1072, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5118, 624, 224, 1072, 'mcq', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5119, 624, 224, 1072, 'true_false', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1),
(5120, 624, 224, 1072, 'written', 2.00, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00', 15, 'admin', 1);

-- --------------------------------------------------------

--
-- Table structure for table `questions_translations`
--

CREATE TABLE `questions_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions_translations`
--

INSERT INTO `questions_translations` (`id`, `question_id`, `language_id`, `question_text`, `is_active`, `created_at`, `updated_at`) VALUES
(12001, 5001, 1, 'ما نوع كلمة «العلم» في الجملة؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12002, 5001, 2, 'What part of speech is “knowledge”?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12003, 5002, 1, 'أي أسلوب يعتمد على مقارنة شيئين؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12004, 5002, 2, 'Which device compares two things?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12005, 5003, 1, 'علامة رفع المبتدأ المفرد هي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12006, 5003, 2, 'The nominative mark of a singular Arabic subject is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12007, 5004, 1, 'الجملة الاسمية تبدأ باسم.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12008, 5004, 2, 'A nominal sentence starts with a noun.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12009, 5005, 1, 'اشرح الفرق بين الجملة الاسمية والجملة الفعلية.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12010, 5005, 2, 'Explain the difference between nominal and verbal sentences.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12011, 5006, 1, 'ما نوع كلمة «العلم» في الجملة؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12012, 5006, 2, 'What part of speech is “knowledge”?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12013, 5007, 1, 'أي أسلوب يعتمد على مقارنة شيئين؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12014, 5007, 2, 'Which device compares two things?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12015, 5008, 1, 'علامة رفع المبتدأ المفرد هي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12016, 5008, 2, 'The nominative mark of a singular Arabic subject is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12017, 5009, 1, 'الجملة الاسمية تبدأ باسم.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12018, 5009, 2, 'A nominal sentence starts with a noun.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12019, 5010, 1, 'اشرح الفرق بين الجملة الاسمية والجملة الفعلية.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12020, 5010, 2, 'Explain the difference between nominal and verbal sentences.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12021, 5011, 1, 'Choose the past tense of “go”.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12022, 5011, 2, 'Choose the past tense of “go”.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12023, 5012, 1, 'Which word is an adjective?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12024, 5012, 2, 'Which word is an adjective?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12025, 5013, 1, 'Choose the correct form: She ___ every day.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12026, 5013, 2, 'Choose the correct form: She ___ every day.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12027, 5014, 1, 'A complete sentence needs a subject and a predicate.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12028, 5014, 2, 'A complete sentence needs a subject and a predicate.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12029, 5015, 1, 'Write a short paragraph using a clear topic sentence.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12030, 5015, 2, 'Write a short paragraph using a clear topic sentence.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12031, 5016, 1, 'Choose the past tense of “go”.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12032, 5016, 2, 'Choose the past tense of “go”.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12033, 5017, 1, 'Which word is an adjective?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12034, 5017, 2, 'Which word is an adjective?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12035, 5018, 1, 'Choose the correct form: She ___ every day.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12036, 5018, 2, 'Choose the correct form: She ___ every day.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12037, 5019, 1, 'A complete sentence needs a subject and a predicate.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12038, 5019, 2, 'A complete sentence needs a subject and a predicate.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12039, 5020, 1, 'Write a short paragraph using a clear topic sentence.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12040, 5020, 2, 'Write a short paragraph using a clear topic sentence.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12041, 5021, 1, 'إذا كان 2x + 3 = 11 فما قيمة x؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12042, 5021, 2, 'If 2x + 3 = 11, what is x?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12043, 5022, 1, 'ميل الخط الأفقي يساوي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12044, 5022, 2, 'The slope of a horizontal line is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12045, 5023, 1, 'مجموع زوايا المثلث يساوي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12046, 5023, 2, 'The sum of a triangle’s angles is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12047, 5024, 1, 'مربع أي عدد حقيقي غير سالب.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12048, 5024, 2, 'The square of any real number is non-negative.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12049, 5025, 1, 'اشرح خطوات حل معادلة خطية من الدرجة الأولى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12050, 5025, 2, 'Explain the steps for solving a first-degree linear equation.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12051, 5026, 1, 'إذا كان 2x + 3 = 11 فما قيمة x؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12052, 5026, 2, 'If 2x + 3 = 11, what is x?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12053, 5027, 1, 'ميل الخط الأفقي يساوي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12054, 5027, 2, 'The slope of a horizontal line is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12055, 5028, 1, 'مجموع زوايا المثلث يساوي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12056, 5028, 2, 'The sum of a triangle’s angles is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12057, 5029, 1, 'مربع أي عدد حقيقي غير سالب.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12058, 5029, 2, 'The square of any real number is non-negative.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12059, 5030, 1, 'اشرح خطوات حل معادلة خطية من الدرجة الأولى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12060, 5030, 2, 'Explain the steps for solving a first-degree linear equation.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12061, 5031, 1, 'ما وحدة قياس القوة في النظام الدولي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12062, 5031, 2, 'What is the SI unit of force?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12063, 5032, 1, 'أي كمية تساوي الكتلة × التسارع؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12064, 5032, 2, 'Which quantity equals mass × acceleration?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12065, 5033, 1, 'سرعة الضوء تقريبًا؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12066, 5033, 2, 'Approximate speed of light?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12067, 5034, 1, 'الطاقة لا تفنى ولا تُستحدث من العدم.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12068, 5034, 2, 'Energy cannot be created or destroyed.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12069, 5035, 1, 'اشرح العلاقة بين القوة والكتلة والتسارع.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12070, 5035, 2, 'Explain the relationship between force, mass, and acceleration.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12071, 5036, 1, 'ما وحدة قياس القوة في النظام الدولي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12072, 5036, 2, 'What is the SI unit of force?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12073, 5037, 1, 'أي كمية تساوي الكتلة × التسارع؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12074, 5037, 2, 'Which quantity equals mass × acceleration?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12075, 5038, 1, 'سرعة الضوء تقريبًا؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12076, 5038, 2, 'Approximate speed of light?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12077, 5039, 1, 'الطاقة لا تفنى ولا تُستحدث من العدم.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12078, 5039, 2, 'Energy cannot be created or destroyed.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12079, 5040, 1, 'اشرح العلاقة بين القوة والكتلة والتسارع.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12080, 5040, 2, 'Explain the relationship between force, mass, and acceleration.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12081, 5041, 1, 'العدد الذري يساوي عدد؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12082, 5041, 2, 'Atomic number equals the number of?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12083, 5042, 1, 'pH أقل من 7 يدل غالبًا على محلول؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12084, 5042, 2, 'A pH below 7 usually indicates a?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12085, 5043, 1, 'الرابطة الناتجة عن مشاركة الإلكترونات هي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12086, 5043, 2, 'A bond formed by sharing electrons is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12087, 5044, 1, 'المادة تتكون من ذرات وجزيئات.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12088, 5044, 2, 'Matter is made of atoms and molecules.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12089, 5045, 1, 'اشرح باختصار الفرق بين الرابطة الأيونية والتساهمية.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12090, 5045, 2, 'Briefly explain the difference between ionic and covalent bonding.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12091, 5046, 1, 'العدد الذري يساوي عدد؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12092, 5046, 2, 'Atomic number equals the number of?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12093, 5047, 1, 'pH أقل من 7 يدل غالبًا على محلول؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12094, 5047, 2, 'A pH below 7 usually indicates a?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12095, 5048, 1, 'الرابطة الناتجة عن مشاركة الإلكترونات هي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12096, 5048, 2, 'A bond formed by sharing electrons is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12097, 5049, 1, 'المادة تتكون من ذرات وجزيئات.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12098, 5049, 2, 'Matter is made of atoms and molecules.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12099, 5050, 1, 'اشرح باختصار الفرق بين الرابطة الأيونية والتساهمية.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12100, 5050, 2, 'Briefly explain the difference between ionic and covalent bonding.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12101, 5051, 1, 'ما الوحدة الأساسية للحياة؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12102, 5051, 2, 'What is the basic unit of life?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12103, 5052, 1, 'أي عضية تنتج معظم ATP؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12104, 5052, 2, 'Which organelle produces most ATP?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12105, 5053, 1, 'DNA يحمل بشكل أساسي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12106, 5053, 2, 'DNA primarily carries?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12107, 5054, 1, 'الخلايا تنتج من خلايا سابقة.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12108, 5054, 2, 'Cells arise from pre-existing cells.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12109, 5055, 1, 'اشرح دور الغشاء الخلوي في الحفاظ على الاتزان الداخلي.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12110, 5055, 2, 'Explain the cell membrane’s role in maintaining homeostasis.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12111, 5056, 1, 'ما الوحدة الأساسية للحياة؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12112, 5056, 2, 'What is the basic unit of life?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12113, 5057, 1, 'أي عضية تنتج معظم ATP؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12114, 5057, 2, 'Which organelle produces most ATP?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12115, 5058, 1, 'DNA يحمل بشكل أساسي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12116, 5058, 2, 'DNA primarily carries?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12117, 5059, 1, 'الخلايا تنتج من خلايا سابقة.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12118, 5059, 2, 'Cells arise from pre-existing cells.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12119, 5060, 1, 'اشرح دور الغشاء الخلوي في الحفاظ على الاتزان الداخلي.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12120, 5060, 2, 'Explain the cell membrane’s role in maintaining homeostasis.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12121, 5061, 1, 'أي مما يلي يعد مصدرًا تاريخيًا أوليًا؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12122, 5061, 2, 'Which is a primary historical source?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12123, 5062, 1, 'التسلسل الزمني يساعد على؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12124, 5062, 2, 'Chronology helps to?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12125, 5063, 1, 'المؤرخ الجيد يقارن بين؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12126, 5063, 2, 'A good historian compares?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12127, 5064, 1, 'المصدر الأولي كُتب أو أُنتج قريبًا من زمن الحدث.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12128, 5064, 2, 'A primary source was created close to the event.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12129, 5065, 1, 'اشرح لماذا يجب مقارنة أكثر من مصدر عند دراسة حدث تاريخي.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12130, 5065, 2, 'Explain why multiple sources should be compared when studying a historical event.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12131, 5066, 1, 'أي مما يلي يعد مصدرًا تاريخيًا أوليًا؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12132, 5066, 2, 'Which is a primary historical source?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12133, 5067, 1, 'التسلسل الزمني يساعد على؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12134, 5067, 2, 'Chronology helps to?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12135, 5068, 1, 'المؤرخ الجيد يقارن بين؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12136, 5068, 2, 'A good historian compares?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12137, 5069, 1, 'المصدر الأولي كُتب أو أُنتج قريبًا من زمن الحدث.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12138, 5069, 2, 'A primary source was created close to the event.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12139, 5070, 1, 'اشرح لماذا يجب مقارنة أكثر من مصدر عند دراسة حدث تاريخي.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12140, 5070, 2, 'Explain why multiple sources should be compared when studying a historical event.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12141, 5071, 1, 'خطوط العرض تقيس الموقع بالنسبة إلى؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12142, 5071, 2, 'Latitude measures position relative to the?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12143, 5072, 1, 'خط الطول الرئيسي يمر عبر؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12144, 5072, 2, 'The prime meridian passes through?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12145, 5073, 1, 'الكثافة السكانية تعني؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12146, 5073, 2, 'Population density means?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12147, 5074, 1, 'المناخ يختلف عن الطقس لأنه يصف أنماطًا طويلة المدى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12148, 5074, 2, 'Climate differs from weather because it describes long-term patterns.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12149, 5075, 1, 'اشرح عاملين يؤثران في توزيع السكان.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12150, 5075, 2, 'Explain two factors that affect population distribution.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12151, 5076, 1, 'خطوط العرض تقيس الموقع بالنسبة إلى؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12152, 5076, 2, 'Latitude measures position relative to the?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12153, 5077, 1, 'خط الطول الرئيسي يمر عبر؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12154, 5077, 2, 'The prime meridian passes through?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12155, 5078, 1, 'الكثافة السكانية تعني؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12156, 5078, 2, 'Population density means?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12157, 5079, 1, 'المناخ يختلف عن الطقس لأنه يصف أنماطًا طويلة المدى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12158, 5079, 2, 'Climate differs from weather because it describes long-term patterns.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12159, 5080, 1, 'اشرح عاملين يؤثران في توزيع السكان.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12160, 5080, 2, 'Explain two factors that affect population distribution.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12161, 5081, 1, 'الاستدلال الاستنباطي يتحرك غالبًا من؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12162, 5081, 2, 'Deductive reasoning usually moves from?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12163, 5082, 1, 'الحجة المنطقية تتكون من مقدمات و؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12164, 5082, 2, 'A logical argument has premises and a?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12165, 5083, 1, 'المغالطة هي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12166, 5083, 2, 'A fallacy is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12167, 5084, 1, 'صلاحية الحجة لا تعني بالضرورة صدق مقدماتها.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12168, 5084, 2, 'Argument validity does not necessarily mean its premises are true.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12169, 5085, 1, 'اشرح الفرق بين صحة الحجة وصدق مقدماتها.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12170, 5085, 2, 'Explain the difference between argument validity and true premises.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12171, 5086, 1, 'الاستدلال الاستنباطي يتحرك غالبًا من؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12172, 5086, 2, 'Deductive reasoning usually moves from?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12173, 5087, 1, 'الحجة المنطقية تتكون من مقدمات و؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12174, 5087, 2, 'A logical argument has premises and a?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12175, 5088, 1, 'المغالطة هي؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12176, 5088, 2, 'A fallacy is?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12177, 5089, 1, 'صلاحية الحجة لا تعني بالضرورة صدق مقدماتها.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12178, 5089, 2, 'Argument validity does not necessarily mean its premises are true.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12179, 5090, 1, 'اشرح الفرق بين صحة الحجة وصدق مقدماتها.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12180, 5090, 2, 'Explain the difference between argument validity and true premises.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12181, 5091, 1, 'ما الخوارزمية؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12182, 5091, 2, 'What is an algorithm?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12183, 5092, 1, 'أي بنية تخزن أزواج مفتاح/قيمة؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12184, 5092, 2, 'Which structure stores key/value pairs?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12185, 5093, 1, 'التعقيد O(n) يعني أن الزمن ينمو تقريبًا؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12186, 5093, 2, 'O(n) means runtime grows roughly?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12187, 5094, 1, 'الاختبار جزء مهم من تطوير البرمجيات.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12188, 5094, 2, 'Testing is an important part of software development.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12189, 5095, 1, 'اشرح بمثال كيف تحول مشكلة بسيطة إلى خوارزمية.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12190, 5095, 2, 'Explain with an example how to turn a simple problem into an algorithm.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12191, 5096, 1, 'ما الخوارزمية؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12192, 5096, 2, 'What is an algorithm?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12193, 5097, 1, 'أي بنية تخزن أزواج مفتاح/قيمة؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12194, 5097, 2, 'Which structure stores key/value pairs?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12195, 5098, 1, 'التعقيد O(n) يعني أن الزمن ينمو تقريبًا؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12196, 5098, 2, 'O(n) means runtime grows roughly?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12197, 5099, 1, 'الاختبار جزء مهم من تطوير البرمجيات.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12198, 5099, 2, 'Testing is an important part of software development.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12199, 5100, 1, 'اشرح بمثال كيف تحول مشكلة بسيطة إلى خوارزمية.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12200, 5100, 2, 'Explain with an example how to turn a simple problem into an algorithm.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12201, 5101, 1, 'ماذا تعني Bonjour؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12202, 5101, 2, 'What does “Bonjour” mean?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12203, 5102, 1, 'اختر أداة التعريف الصحيحة: ___ livre', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12204, 5102, 2, 'Choose the correct article: ___ livre', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12205, 5103, 1, 'Je ___ étudiant.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12206, 5103, 2, 'Je ___ étudiant.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12207, 5104, 1, '“Merci” تعني شكرًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12208, 5104, 2, '“Merci” means thank you.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12209, 5105, 1, 'اكتب خمس جمل فرنسية قصيرة للتعريف بنفسك.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12210, 5105, 2, 'Write five short French sentences to introduce yourself.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12211, 5106, 1, 'ماذا تعني Bonjour؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12212, 5106, 2, 'What does “Bonjour” mean?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12213, 5107, 1, 'اختر أداة التعريف الصحيحة: ___ livre', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12214, 5107, 2, 'Choose the correct article: ___ livre', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12215, 5108, 1, 'Je ___ étudiant.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12216, 5108, 2, 'Je ___ étudiant.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12217, 5109, 1, '“Merci” تعني شكرًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12218, 5109, 2, '“Merci” means thank you.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12219, 5110, 1, 'اكتب خمس جمل فرنسية قصيرة للتعريف بنفسك.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12220, 5110, 2, 'Write five short French sentences to introduce yourself.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12221, 5111, 1, 'عند الضغط الجوي العادي يغلي الماء قرب؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12222, 5111, 2, 'At normal atmospheric pressure, water boils near?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12223, 5112, 1, 'أي حالة لها حجم ثابت وشكل غير ثابت؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12224, 5112, 2, 'Which state has fixed volume but variable shape?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12225, 5113, 1, 'الطاقة الحركية ترتبط بـ؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12226, 5113, 2, 'Kinetic energy is associated with?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12227, 5114, 1, 'المادة يمكن أن تتغير من حالة إلى أخرى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12228, 5114, 2, 'Matter can change from one state to another.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12229, 5115, 1, 'اشرح مثالًا يوميًا على تحول الطاقة من صورة لأخرى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12230, 5115, 2, 'Explain an everyday example of energy changing from one form to another.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12231, 5116, 1, 'عند الضغط الجوي العادي يغلي الماء قرب؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12232, 5116, 2, 'At normal atmospheric pressure, water boils near?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12233, 5117, 1, 'أي حالة لها حجم ثابت وشكل غير ثابت؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12234, 5117, 2, 'Which state has fixed volume but variable shape?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12235, 5118, 1, 'الطاقة الحركية ترتبط بـ؟', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12236, 5118, 2, 'Kinetic energy is associated with?', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12237, 5119, 1, 'المادة يمكن أن تتغير من حالة إلى أخرى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12238, 5119, 2, 'Matter can change from one state to another.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12239, 5120, 1, 'اشرح مثالًا يوميًا على تحول الطاقة من صورة لأخرى.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(12240, 5120, 2, 'Explain an everyday example of energy changing from one form to another.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resource_type` enum('book','file') NOT NULL,
  `code` varchar(120) NOT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `file_path` varchar(1000) DEFAULT NULL,
  `file_url` varchar(1000) DEFAULT NULL,
  `publish_status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `course_id`, `unit_id`, `resource_type`, `code`, `cover_image`, `file_path`, `file_url`, `publish_status`, `is_active`, `created_at`, `updated_at`) VALUES
(501, 201, 1001, 'book', 'RES-C201-1', 'https://loremflickr.com/800/1100/arabic,books,language,book?lock=501', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(502, 201, 1002, 'file', 'RES-C201-2', 'https://loremflickr.com/800/1100/arabic,books,language,study,notes?lock=502', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(503, 202, 1004, 'book', 'RES-C202-1', 'https://loremflickr.com/800/1100/arabic,books,language,book?lock=503', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(504, 202, 1005, 'file', 'RES-C202-2', 'https://loremflickr.com/800/1100/arabic,books,language,study,notes?lock=504', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(505, 203, 1007, 'book', 'RES-C203-1', 'https://loremflickr.com/800/1100/english,language,books,book?lock=505', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(506, 203, 1008, 'file', 'RES-C203-2', 'https://loremflickr.com/800/1100/english,language,books,study,notes?lock=506', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(507, 204, 1010, 'book', 'RES-C204-1', 'https://loremflickr.com/800/1100/english,language,books,book?lock=507', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(508, 204, 1011, 'file', 'RES-C204-2', 'https://loremflickr.com/800/1100/english,language,books,study,notes?lock=508', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(509, 205, 1013, 'book', 'RES-C205-1', 'https://loremflickr.com/800/1100/mathematics,geometry,education,book?lock=509', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(510, 205, 1014, 'file', 'RES-C205-2', 'https://loremflickr.com/800/1100/mathematics,geometry,education,study,notes?lock=510', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(511, 206, 1016, 'book', 'RES-C206-1', 'https://loremflickr.com/800/1100/mathematics,calculus,education,book?lock=511', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(512, 206, 1017, 'file', 'RES-C206-2', 'https://loremflickr.com/800/1100/mathematics,calculus,education,study,notes?lock=512', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(513, 207, 1019, 'book', 'RES-C207-1', 'https://loremflickr.com/800/1100/physics,laboratory,science,book?lock=513', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(514, 207, 1020, 'file', 'RES-C207-2', 'https://loremflickr.com/800/1100/physics,laboratory,science,study,notes?lock=514', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(515, 208, 1022, 'book', 'RES-C208-1', 'https://loremflickr.com/800/1100/physics,electricity,science,book?lock=515', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(516, 208, 1023, 'file', 'RES-C208-2', 'https://loremflickr.com/800/1100/physics,electricity,science,study,notes?lock=516', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(517, 209, 1025, 'book', 'RES-C209-1', 'https://loremflickr.com/800/1100/chemistry,laboratory,science,book?lock=517', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(518, 209, 1026, 'file', 'RES-C209-2', 'https://loremflickr.com/800/1100/chemistry,laboratory,science,study,notes?lock=518', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(519, 210, 1028, 'book', 'RES-C210-1', 'https://loremflickr.com/800/1100/chemistry,molecules,science,book?lock=519', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(520, 210, 1029, 'file', 'RES-C210-2', 'https://loremflickr.com/800/1100/chemistry,molecules,science,study,notes?lock=520', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(521, 211, 1031, 'book', 'RES-C211-1', 'https://loremflickr.com/800/1100/biology,microscope,science,book?lock=521', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(522, 211, 1032, 'file', 'RES-C211-2', 'https://loremflickr.com/800/1100/biology,microscope,science,study,notes?lock=522', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(523, 212, 1034, 'book', 'RES-C212-1', 'https://loremflickr.com/800/1100/biology,dna,science,book?lock=523', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(524, 212, 1035, 'file', 'RES-C212-2', 'https://loremflickr.com/800/1100/biology,dna,science,study,notes?lock=524', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(525, 213, 1037, 'book', 'RES-C213-1', 'https://loremflickr.com/800/1100/history,books,education,book?lock=525', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(526, 213, 1038, 'file', 'RES-C213-2', 'https://loremflickr.com/800/1100/history,books,education,study,notes?lock=526', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(527, 214, 1040, 'book', 'RES-C214-1', 'https://loremflickr.com/800/1100/history,archive,education,book?lock=527', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(528, 214, 1041, 'file', 'RES-C214-2', 'https://loremflickr.com/800/1100/history,archive,education,study,notes?lock=528', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(529, 215, 1043, 'book', 'RES-C215-1', 'https://loremflickr.com/800/1100/geography,map,education,book?lock=529', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(530, 215, 1044, 'file', 'RES-C215-2', 'https://loremflickr.com/800/1100/geography,map,education,study,notes?lock=530', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(531, 216, 1046, 'book', 'RES-C216-1', 'https://loremflickr.com/800/1100/geography,globe,education,book?lock=531', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(532, 216, 1047, 'file', 'RES-C216-2', 'https://loremflickr.com/800/1100/geography,globe,education,study,notes?lock=532', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(533, 217, 1049, 'book', 'RES-C217-1', 'https://loremflickr.com/800/1100/philosophy,books,thinking,book?lock=533', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(534, 217, 1050, 'file', 'RES-C217-2', 'https://loremflickr.com/800/1100/philosophy,books,thinking,study,notes?lock=534', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(535, 218, 1052, 'book', 'RES-C218-1', 'https://loremflickr.com/800/1100/philosophy,logic,books,book?lock=535', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(536, 218, 1053, 'file', 'RES-C218-2', 'https://loremflickr.com/800/1100/philosophy,logic,books,study,notes?lock=536', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(537, 219, 1055, 'book', 'RES-C219-1', 'https://loremflickr.com/800/1100/computer,programming,coding,book?lock=537', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(538, 219, 1056, 'file', 'RES-C219-2', 'https://loremflickr.com/800/1100/computer,programming,coding,study,notes?lock=538', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(539, 220, 1058, 'book', 'RES-C220-1', 'https://loremflickr.com/800/1100/computer,code,technology,book?lock=539', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(540, 220, 1059, 'file', 'RES-C220-2', 'https://loremflickr.com/800/1100/computer,code,technology,study,notes?lock=540', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(541, 221, 1061, 'book', 'RES-C221-1', 'https://loremflickr.com/800/1100/french,language,books,book?lock=541', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(542, 221, 1062, 'file', 'RES-C221-2', 'https://loremflickr.com/800/1100/french,language,books,study,notes?lock=542', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(543, 222, 1064, 'book', 'RES-C222-1', 'https://loremflickr.com/800/1100/french,language,education,book?lock=543', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(544, 222, 1065, 'file', 'RES-C222-2', 'https://loremflickr.com/800/1100/french,language,education,study,notes?lock=544', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(545, 223, 1067, 'book', 'RES-C223-1', 'https://loremflickr.com/800/1100/science,laboratory,education,book?lock=545', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(546, 223, 1068, 'file', 'RES-C223-2', 'https://loremflickr.com/800/1100/science,laboratory,education,study,notes?lock=546', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(547, 224, 1070, 'book', 'RES-C224-1', 'https://loremflickr.com/800/1100/science,experiment,education,book?lock=547', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(548, 224, 1071, 'file', 'RES-C224-2', 'https://loremflickr.com/800/1100/science,experiment,education,study,notes?lock=548', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `resources_translations`
--

CREATE TABLE `resources_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resource_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources_translations`
--

INSERT INTO `resources_translations` (`id`, `resource_id`, `language_id`, `name`, `description`, `author`, `is_active`, `created_at`, `updated_at`) VALUES
(10001, 501, 1, 'كتاب: AP Physics Lab Manual', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة العربية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10002, 501, 2, 'Book: AP Physics Lab Manual', 'Public open reference available through a direct URL to support the Arabic Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10003, 502, 1, 'ملف مراجعة: Psychology 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة العربية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10004, 502, 2, 'Review file: Psychology 2e', 'Public open reference available through a direct URL to support the Arabic Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10005, 503, 1, 'كتاب: Psychology 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة العربية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10006, 503, 2, 'Book: Psychology 2e', 'Public open reference available through a direct URL to support the Arabic Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10007, 504, 1, 'ملف مراجعة: Elementary Algebra 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة العربية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10008, 504, 2, 'Review file: Elementary Algebra 2e', 'Public open reference available through a direct URL to support the Arabic Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10009, 505, 1, 'كتاب: Elementary Algebra 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الإنجليزية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10010, 505, 2, 'Book: Elementary Algebra 2e', 'Public open reference available through a direct URL to support the English Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10011, 506, 1, 'ملف مراجعة: Introductory Statistics 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الإنجليزية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10012, 506, 2, 'Review file: Introductory Statistics 2e', 'Public open reference available through a direct URL to support the English Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10013, 507, 1, 'كتاب: Introductory Statistics 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الإنجليزية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10014, 507, 2, 'Book: Introductory Statistics 2e', 'Public open reference available through a direct URL to support the English Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10015, 508, 1, 'ملف مراجعة: Biology 2e Transition Guide', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الإنجليزية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10016, 508, 2, 'Review file: Biology 2e Transition Guide', 'Public open reference available through a direct URL to support the English Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10017, 509, 1, 'كتاب: Biology 2e Transition Guide', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الرياضيات.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10018, 509, 2, 'Book: Biology 2e Transition Guide', 'Public open reference available through a direct URL to support the Mathematics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10019, 510, 1, 'ملف مراجعة: Chemistry 2e Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الرياضيات.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10020, 510, 2, 'Review file: Chemistry 2e Release Notes', 'Public open reference available through a direct URL to support the Mathematics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10021, 511, 1, 'كتاب: Chemistry 2e Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الرياضيات.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10022, 511, 2, 'Book: Chemistry 2e Release Notes', 'Public open reference available through a direct URL to support the Mathematics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10023, 512, 1, 'ملف مراجعة: Pharmacology for Nurses', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الرياضيات.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10024, 512, 2, 'Review file: Pharmacology for Nurses', 'Public open reference available through a direct URL to support the Mathematics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10025, 513, 1, 'كتاب: Pharmacology for Nurses', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفيزياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10026, 513, 2, 'Book: Pharmacology for Nurses', 'Public open reference available through a direct URL to support the Physics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10027, 514, 1, 'ملف مراجعة: University Physics Volume 3 Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفيزياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10028, 514, 2, 'Review file: University Physics Volume 3 Release Notes', 'Public open reference available through a direct URL to support the Physics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10029, 515, 1, 'كتاب: University Physics Volume 3 Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفيزياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10030, 515, 2, 'Book: University Physics Volume 3 Release Notes', 'Public open reference available through a direct URL to support the Physics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10031, 516, 1, 'ملف مراجعة: College Algebra', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفيزياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10032, 516, 2, 'Review file: College Algebra', 'Public open reference available through a direct URL to support the Physics course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10033, 517, 1, 'كتاب: College Algebra', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الكيمياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10034, 517, 2, 'Book: College Algebra', 'Public open reference available through a direct URL to support the Chemistry course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10035, 518, 1, 'ملف مراجعة: Algebra & Trigonometry Lecture Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الكيمياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10036, 518, 2, 'Review file: Algebra & Trigonometry Lecture Notes', 'Public open reference available through a direct URL to support the Chemistry course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10037, 519, 1, 'كتاب: Algebra & Trigonometry Lecture Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الكيمياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10038, 519, 2, 'Book: Algebra & Trigonometry Lecture Notes', 'Public open reference available through a direct URL to support the Chemistry course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10039, 520, 1, 'ملف مراجعة: AP Physics Lab Manual', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الكيمياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10040, 520, 2, 'Review file: AP Physics Lab Manual', 'Public open reference available through a direct URL to support the Chemistry course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10041, 521, 1, 'كتاب: AP Physics Lab Manual', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الأحياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10042, 521, 2, 'Book: AP Physics Lab Manual', 'Public open reference available through a direct URL to support the Biology course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10043, 522, 1, 'ملف مراجعة: Psychology 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الأحياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10044, 522, 2, 'Review file: Psychology 2e', 'Public open reference available through a direct URL to support the Biology course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10045, 523, 1, 'كتاب: Psychology 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الأحياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10046, 523, 2, 'Book: Psychology 2e', 'Public open reference available through a direct URL to support the Biology course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10047, 524, 1, 'ملف مراجعة: Elementary Algebra 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الأحياء.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10048, 524, 2, 'Review file: Elementary Algebra 2e', 'Public open reference available through a direct URL to support the Biology course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10049, 525, 1, 'كتاب: Elementary Algebra 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في التاريخ.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10050, 525, 2, 'Book: Elementary Algebra 2e', 'Public open reference available through a direct URL to support the History course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10051, 526, 1, 'ملف مراجعة: Introductory Statistics 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في التاريخ.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10052, 526, 2, 'Review file: Introductory Statistics 2e', 'Public open reference available through a direct URL to support the History course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10053, 527, 1, 'كتاب: Introductory Statistics 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في التاريخ.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10054, 527, 2, 'Book: Introductory Statistics 2e', 'Public open reference available through a direct URL to support the History course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10055, 528, 1, 'ملف مراجعة: Biology 2e Transition Guide', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في التاريخ.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10056, 528, 2, 'Review file: Biology 2e Transition Guide', 'Public open reference available through a direct URL to support the History course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10057, 529, 1, 'كتاب: Biology 2e Transition Guide', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الجغرافيا.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10058, 529, 2, 'Book: Biology 2e Transition Guide', 'Public open reference available through a direct URL to support the Geography course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10059, 530, 1, 'ملف مراجعة: Chemistry 2e Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الجغرافيا.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10060, 530, 2, 'Review file: Chemistry 2e Release Notes', 'Public open reference available through a direct URL to support the Geography course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10061, 531, 1, 'كتاب: Chemistry 2e Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الجغرافيا.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10062, 531, 2, 'Book: Chemistry 2e Release Notes', 'Public open reference available through a direct URL to support the Geography course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10063, 532, 1, 'ملف مراجعة: Pharmacology for Nurses', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الجغرافيا.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10064, 532, 2, 'Review file: Pharmacology for Nurses', 'Public open reference available through a direct URL to support the Geography course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10065, 533, 1, 'كتاب: Pharmacology for Nurses', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفلسفة والمنطق.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10066, 533, 2, 'Book: Pharmacology for Nurses', 'Public open reference available through a direct URL to support the Philosophy & Logic course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10067, 534, 1, 'ملف مراجعة: University Physics Volume 3 Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفلسفة والمنطق.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10068, 534, 2, 'Review file: University Physics Volume 3 Release Notes', 'Public open reference available through a direct URL to support the Philosophy & Logic course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10069, 535, 1, 'كتاب: University Physics Volume 3 Release Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفلسفة والمنطق.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10070, 535, 2, 'Book: University Physics Volume 3 Release Notes', 'Public open reference available through a direct URL to support the Philosophy & Logic course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10071, 536, 1, 'ملف مراجعة: College Algebra', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في الفلسفة والمنطق.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10072, 536, 2, 'Review file: College Algebra', 'Public open reference available through a direct URL to support the Philosophy & Logic course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10073, 537, 1, 'كتاب: College Algebra', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في علوم الحاسب.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10074, 537, 2, 'Book: College Algebra', 'Public open reference available through a direct URL to support the Computer Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10075, 538, 1, 'ملف مراجعة: Algebra & Trigonometry Lecture Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في علوم الحاسب.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10076, 538, 2, 'Review file: Algebra & Trigonometry Lecture Notes', 'Public open reference available through a direct URL to support the Computer Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10077, 539, 1, 'كتاب: Algebra & Trigonometry Lecture Notes', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في علوم الحاسب.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10078, 539, 2, 'Book: Algebra & Trigonometry Lecture Notes', 'Public open reference available through a direct URL to support the Computer Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10079, 540, 1, 'ملف مراجعة: AP Physics Lab Manual', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في علوم الحاسب.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10080, 540, 2, 'Review file: AP Physics Lab Manual', 'Public open reference available through a direct URL to support the Computer Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10081, 541, 1, 'كتاب: AP Physics Lab Manual', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الفرنسية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10082, 541, 2, 'Book: AP Physics Lab Manual', 'Public open reference available through a direct URL to support the French Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10083, 542, 1, 'ملف مراجعة: Psychology 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الفرنسية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10084, 542, 2, 'Review file: Psychology 2e', 'Public open reference available through a direct URL to support the French Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10085, 543, 1, 'كتاب: Psychology 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الفرنسية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10086, 543, 2, 'Book: Psychology 2e', 'Public open reference available through a direct URL to support the French Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10087, 544, 1, 'ملف مراجعة: Elementary Algebra 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في اللغة الفرنسية.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10088, 544, 2, 'Review file: Elementary Algebra 2e', 'Public open reference available through a direct URL to support the French Language course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10089, 545, 1, 'كتاب: Elementary Algebra 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في العلوم المتكاملة.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10090, 545, 2, 'Book: Elementary Algebra 2e', 'Public open reference available through a direct URL to support the Integrated Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10091, 546, 1, 'ملف مراجعة: Introductory Statistics 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في العلوم المتكاملة.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10092, 546, 2, 'Review file: Introductory Statistics 2e', 'Public open reference available through a direct URL to support the Integrated Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10093, 547, 1, 'كتاب: Introductory Statistics 2e', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في العلوم المتكاملة.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10094, 547, 2, 'Book: Introductory Statistics 2e', 'Public open reference available through a direct URL to support the Integrated Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10095, 548, 1, 'ملف مراجعة: Biology 2e Transition Guide', 'مرجع خارجي مفتوح ومتاح عبر رابط مباشر لمساندة الطالب في العلوم المتكاملة.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(10096, 548, 2, 'Review file: Biology 2e Transition Guide', 'Public open reference available through a direct URL to support the Integrated Science course.', 'OpenStax / OER', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'super-admin', 'مدير النظام', 'Full platform administration', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'content-manager', 'مدير المحتوى', 'Manage learning content and catalog', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'support-agent', 'مسؤول الدعم', 'Student and parent support operations', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles_translations`
--

CREATE TABLE `roles_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles_translations`
--

INSERT INTO `roles_translations` (`id`, `role_id`, `language_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'مدير النظام', 'Full platform administration', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Full platform administration', 'Full platform administration', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'مدير المحتوى', 'Manage learning content and catalog', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'Manage learning content and catalog', 'Manage learning content and catalog', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'مسؤول الدعم', 'Student and parent support operations', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Student and parent support operations', 'Student and parent support operations', '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(1, 1, 1, '2026-08-10 16:00:00'),
(2, 1, 2, '2026-08-10 16:00:00'),
(3, 1, 3, '2026-08-10 16:00:00'),
(4, 1, 4, '2026-08-10 16:00:00'),
(5, 1, 5, '2026-08-10 16:00:00'),
(6, 1, 6, '2026-08-10 16:00:00'),
(7, 1, 7, '2026-08-10 16:00:00'),
(8, 1, 8, '2026-08-10 16:00:00'),
(9, 1, 9, '2026-08-10 16:00:00'),
(10, 1, 10, '2026-08-10 16:00:00'),
(11, 1, 11, '2026-08-10 16:00:00'),
(12, 1, 12, '2026-08-10 16:00:00'),
(13, 2, 1, '2026-08-10 16:00:00'),
(14, 2, 3, '2026-08-10 16:00:00'),
(15, 2, 4, '2026-08-10 16:00:00'),
(16, 2, 5, '2026-08-10 16:00:00'),
(17, 2, 8, '2026-08-10 16:00:00'),
(18, 2, 9, '2026-08-10 16:00:00'),
(19, 3, 1, '2026-08-10 16:00:00'),
(20, 3, 2, '2026-08-10 16:00:00'),
(21, 3, 6, '2026-08-10 16:00:00'),
(22, 3, 7, '2026-08-10 16:00:00'),
(23, 3, 9, '2026-08-10 16:00:00'),
(24, 3, 10, '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_type_id` bigint(20) UNSIGNED NOT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_type_id`, `city_id`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'cairo-future-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 3, 2, 'giza-language-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 3, 'alex-private-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 4, 1, 'cairo-intl-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 1, 4, 'qalyubia-public-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 5, 'mansoura-language-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 2, 6, 'sharqia-private-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 3, 7, 'tanta-language-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 1, 8, 'monufia-public-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 2, 9, 'beheira-private-01', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `schools_translations`
--

CREATE TABLE `schools_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools_translations`
--

INSERT INTO `schools_translations` (`id`, `school_id`, `language_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'مدرسة المستقبل بالقاهرة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Cairo Future School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'مدرسة الجيزة للغات', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'Giza Language School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'مدرسة الإسكندرية الحديثة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Alexandria Modern School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 4, 1, 'مدرسة القاهرة الدولية الحديثة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 4, 2, 'Modern Cairo International School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 5, 1, 'مدرسة القليوبية الجديدة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 5, 2, 'New Qalyubia School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 6, 1, 'مدرسة المنصورة للغات', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 6, 2, 'Mansoura Language School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(13, 7, 1, 'مدرسة الشرقية الخاصة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(14, 7, 2, 'Sharqia Private School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(15, 8, 1, 'مدرسة طنطا للغات', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(16, 8, 2, 'Tanta Language School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(17, 9, 1, 'مدرسة المنوفية النموذجية', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(18, 9, 2, 'Monufia Model School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(19, 10, 1, 'مدرسة البحيرة الحديثة', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(20, 10, 2, 'Beheira Modern School', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `school_types`
--

CREATE TABLE `school_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_types`
--

INSERT INTO `school_types` (`id`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'public', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'private', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'language', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 'international', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `school_types_translations`
--

CREATE TABLE `school_types_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_type_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_types_translations`
--

INSERT INTO `school_types_translations` (`id`, `school_type_id`, `language_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'حكومي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Public', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'خاص', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'Private', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'لغات', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Language', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 4, 1, 'دولي', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 4, 2, 'International', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `session_attendance`
--

CREATE TABLE `session_attendance` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `status` enum('present','absent') NOT NULL DEFAULT 'present',
  `marked_by_teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `marked_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `session_attendance`
--

INSERT INTO `session_attendance` (`id`, `session_id`, `student_id`, `session_date`, `status`, `marked_by_teacher_id`, `marked_at`) VALUES
(1001, 101, 1001, '2026-08-10', 'absent', 201, '2026-08-10 19:00:00'),
(1002, 101, 1002, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1003, 101, 1003, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1004, 101, 1004, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1005, 101, 1005, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1006, 101, 1006, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1007, 101, 1007, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1008, 101, 1008, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1009, 102, 1003, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1010, 102, 1004, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1011, 102, 1005, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1012, 102, 1006, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1013, 102, 1007, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1014, 102, 1008, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1015, 102, 1009, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1016, 102, 1010, '2026-08-10', 'present', 201, '2026-08-10 19:00:00'),
(1017, 103, 1005, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1018, 103, 1006, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1019, 103, 1007, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1020, 103, 1008, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1021, 103, 1009, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1022, 103, 1010, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1023, 103, 1011, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1024, 103, 1012, '2026-08-10', 'absent', 202, '2026-08-10 19:00:00'),
(1025, 104, 1007, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1026, 104, 1008, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1027, 104, 1009, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1028, 104, 1010, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1029, 104, 1011, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1030, 104, 1012, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1031, 104, 1013, '2026-08-10', 'absent', 202, '2026-08-10 19:00:00'),
(1032, 104, 1014, '2026-08-10', 'present', 202, '2026-08-10 19:00:00'),
(1033, 105, 1009, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1034, 105, 1010, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1035, 105, 1011, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1036, 105, 1012, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1037, 105, 1013, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1038, 105, 1014, '2026-08-10', 'absent', 203, '2026-08-10 19:00:00'),
(1039, 105, 1015, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1040, 105, 1016, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1041, 106, 1011, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1042, 106, 1012, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1043, 106, 1013, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1044, 106, 1014, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1045, 106, 1015, '2026-08-10', 'absent', 203, '2026-08-10 19:00:00'),
(1046, 106, 1016, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1047, 106, 1017, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1048, 106, 1018, '2026-08-10', 'present', 203, '2026-08-10 19:00:00'),
(1049, 107, 1013, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1050, 107, 1014, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1051, 107, 1015, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1052, 107, 1016, '2026-08-10', 'absent', 204, '2026-08-10 19:00:00'),
(1053, 107, 1017, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1054, 107, 1018, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1055, 107, 1019, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1056, 107, 1020, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1057, 108, 1015, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1058, 108, 1016, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1059, 108, 1017, '2026-08-10', 'absent', 204, '2026-08-10 19:00:00'),
(1060, 108, 1018, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1061, 108, 1019, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1062, 108, 1020, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1063, 108, 1021, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1064, 108, 1022, '2026-08-10', 'present', 204, '2026-08-10 19:00:00'),
(1065, 109, 1017, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1066, 109, 1018, '2026-08-10', 'absent', 205, '2026-08-10 19:00:00'),
(1067, 109, 1019, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1068, 109, 1020, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1069, 109, 1021, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1070, 109, 1022, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1071, 109, 1023, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1072, 109, 1024, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1073, 110, 1019, '2026-08-10', 'absent', 205, '2026-08-10 19:00:00'),
(1074, 110, 1020, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1075, 110, 1021, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1076, 110, 1022, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1077, 110, 1023, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1078, 110, 1024, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1079, 110, 1025, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1080, 110, 1026, '2026-08-10', 'present', 205, '2026-08-10 19:00:00'),
(1081, 111, 1021, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1082, 111, 1022, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1083, 111, 1023, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1084, 111, 1024, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1085, 111, 1025, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1086, 111, 1026, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1087, 111, 1027, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1088, 111, 1028, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1089, 112, 1023, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1090, 112, 1024, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1091, 112, 1025, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1092, 112, 1026, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1093, 112, 1027, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1094, 112, 1028, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1095, 112, 1029, '2026-08-10', 'present', 206, '2026-08-10 19:00:00'),
(1096, 112, 1030, '2026-08-10', 'absent', 206, '2026-08-10 19:00:00'),
(1097, 113, 1025, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1098, 113, 1026, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1099, 113, 1027, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1100, 113, 1028, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1101, 113, 1029, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1102, 113, 1030, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1103, 113, 1031, '2026-08-10', 'absent', 207, '2026-08-10 19:00:00'),
(1104, 113, 1032, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1105, 114, 1027, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1106, 114, 1028, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1107, 114, 1029, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1108, 114, 1030, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1109, 114, 1031, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1110, 114, 1032, '2026-08-10', 'absent', 207, '2026-08-10 19:00:00'),
(1111, 114, 1033, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1112, 114, 1034, '2026-08-10', 'present', 207, '2026-08-10 19:00:00'),
(1113, 115, 1029, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1114, 115, 1030, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1115, 115, 1031, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1116, 115, 1032, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1117, 115, 1033, '2026-08-10', 'absent', 208, '2026-08-10 19:00:00'),
(1118, 115, 1034, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1119, 115, 1035, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1120, 115, 1036, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1121, 116, 1031, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1122, 116, 1032, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1123, 116, 1033, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1124, 116, 1034, '2026-08-10', 'absent', 208, '2026-08-10 19:00:00'),
(1125, 116, 1035, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1126, 116, 1036, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1127, 116, 1037, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1128, 116, 1038, '2026-08-10', 'present', 208, '2026-08-10 19:00:00'),
(1129, 117, 1033, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1130, 117, 1034, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1131, 117, 1035, '2026-08-10', 'absent', 209, '2026-08-10 19:00:00'),
(1132, 117, 1036, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1133, 117, 1037, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1134, 117, 1038, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1135, 117, 1039, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1136, 117, 1040, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1137, 118, 1035, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1138, 118, 1036, '2026-08-10', 'absent', 209, '2026-08-10 19:00:00'),
(1139, 118, 1037, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1140, 118, 1038, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1141, 118, 1039, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1142, 118, 1040, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1143, 118, 1041, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1144, 118, 1042, '2026-08-10', 'present', 209, '2026-08-10 19:00:00'),
(1145, 119, 1037, '2026-08-10', 'absent', 210, '2026-08-10 19:00:00'),
(1146, 119, 1038, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1147, 119, 1039, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1148, 119, 1040, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1149, 119, 1041, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1150, 119, 1042, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1151, 119, 1043, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1152, 119, 1044, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1153, 120, 1039, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1154, 120, 1040, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1155, 120, 1041, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1156, 120, 1042, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1157, 120, 1043, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1158, 120, 1044, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1159, 120, 1045, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1160, 120, 1046, '2026-08-10', 'present', 210, '2026-08-10 19:00:00'),
(1161, 121, 1041, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1162, 121, 1042, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1163, 121, 1043, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1164, 121, 1044, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1165, 121, 1045, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1166, 121, 1046, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1167, 121, 1047, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1168, 121, 1048, '2026-08-10', 'absent', 211, '2026-08-10 19:00:00'),
(1169, 122, 1043, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1170, 122, 1044, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1171, 122, 1045, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1172, 122, 1046, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1173, 122, 1047, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1174, 122, 1048, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1175, 122, 1049, '2026-08-10', 'absent', 211, '2026-08-10 19:00:00'),
(1176, 122, 1050, '2026-08-10', 'present', 211, '2026-08-10 19:00:00'),
(1177, 123, 1045, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1178, 123, 1046, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1179, 123, 1047, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1180, 123, 1048, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1181, 123, 1049, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1182, 123, 1050, '2026-08-10', 'absent', 212, '2026-08-10 19:00:00'),
(1183, 123, 1051, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1184, 123, 1052, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1185, 124, 1047, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1186, 124, 1048, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1187, 124, 1049, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1188, 124, 1050, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1189, 124, 1051, '2026-08-10', 'absent', 212, '2026-08-10 19:00:00'),
(1190, 124, 1052, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1191, 124, 1053, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1192, 124, 1054, '2026-08-10', 'present', 212, '2026-08-10 19:00:00'),
(1193, 101, 1001, '2026-08-15', 'present', 201, '2026-08-12 22:46:02'),
(1194, 101, 1049, '2026-08-15', 'present', 201, '2026-08-12 22:46:02');

-- --------------------------------------------------------

--
-- Table structure for table `session_files`
--

CREATE TABLE `session_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED NOT NULL,
  `uploaded_by_type` enum('admin','teacher') NOT NULL DEFAULT 'teacher',
  `uploaded_by_id` int(10) UNSIGNED NOT NULL,
  `file_type` enum('exam','homework','task','explanation','other') NOT NULL DEFAULT 'other',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `session_files`
--

INSERT INTO `session_files` (`id`, `session_id`, `uploaded_by_type`, `uploaded_by_id`, `file_type`, `title`, `description`, `file_path`, `original_name`, `is_active`, `created_at`) VALUES
(1, 1, 'teacher', 1, 'explanation', 'ملف شرح - الكيمياء العضوية الجزء الأول', 'ملف PDF يحتوي على شرح مفصل للمركبات العضوية', 'uploads/session-files/chem_explanation_01.pdf', 'chem_explanation_01.pdf', 1, '2026-08-12 11:58:38'),
(2, 1, 'teacher', 1, 'homework', 'واجب الكيمياء - الأسبوع الأول', 'حل التمارين من 1 إلى 15 من الكتاب المدرسي', 'uploads/session-files/chem_homework_01.pdf', 'chem_homework_01.pdf', 1, '2026-08-12 11:58:38'),
(3, 2, 'teacher', 1, 'explanation', 'ملف شرح - التفاعلات الكيميائية', 'ملخص شامل لأنواع التفاعلات الكيميائية', 'uploads/session-files/chem_reactions.pdf', 'chem_reactions.pdf', 1, '2026-08-12 11:58:38'),
(4, 1, 'teacher', 1, 'exam', 'امتحان تجريبي - الكيمياء العضوية', 'امتحان شامل على الوحدة الأولى بالكامل', 'uploads/session-files/chem_exam_01.pdf', 'chem_exam_01.pdf', 1, '2026-08-12 11:58:38'),
(5, 1, 'teacher', 1, 'task', 'مهمة بحثية - المركبات الكربونية', 'اكتب ملخصاً عن استخدامات المركبات الكربونية في الحياة اليومية', 'uploads/session-files/chem_task_01.pdf', 'chem_task_01.pdf', 1, '2026-08-12 11:58:38'),
(6, 4, 'teacher', 2, 'explanation', 'ملف شرح - قوانين نيوتن', 'شرح مفصل لقوانين الحركة الثلاثة', 'uploads/session-files/phys_newton.pdf', 'phys_newton.pdf', 1, '2026-08-12 11:58:38'),
(7, 4, 'teacher', 2, 'homework', 'واجب الفيزياء - الأسبوع الأول', 'حل مسائل الحركة المستقيمة المنتظمة', 'uploads/session-files/phys_homework_01.pdf', 'phys_homework_01.pdf', 1, '2026-08-12 11:58:38'),
(8, 5, 'teacher', 2, 'exam', 'اختبار قصير - قوانين نيوتن', 'اختبار 10 أسئلة على قوانين الحركة', 'uploads/session-files/phys_quiz_01.pdf', 'phys_quiz_01.pdf', 1, '2026-08-12 11:58:38'),
(9, 6, 'teacher', 3, 'explanation', 'ملف شرح - التفاضل والتكامل', 'مقدمة في التفاضل مع أمثلة محلولة', 'uploads/session-files/math_calculus.pdf', 'math_calculus.pdf', 1, '2026-08-12 11:58:38'),
(10, 6, 'teacher', 3, 'homework', 'واجب الرياضيات - الأسبوع الأول', 'احسب المشتقات للدوال التالية', 'uploads/session-files/math_homework_01.pdf', 'math_homework_01.pdf', 1, '2026-08-12 11:58:38'),
(11, 7, 'admin', 1, 'task', 'مهمة جماعية - تطبيقات التفاضل', 'مهمة جماعية لحل مسائل تطبيقية على التفاضل', 'uploads/session-files/math_group_task.pdf', 'math_group_task.pdf', 1, '2026-08-12 11:58:38'),
(12, 8, 'teacher', 4, 'explanation', 'ملف شرح - الخلية الحيوانية', 'صور ورسوم توضيحية لأجزاء الخلية الحيوانية', 'uploads/session-files/bio_cell.pdf', 'bio_cell.pdf', 1, '2026-08-12 11:58:38'),
(13, 9, 'teacher', 5, 'homework', 'واجب اللغة العربية - الأسبوع الأول', 'تحليل نص أدبي من اختيارك بالأساليب البلاغية', 'uploads/session-files/arabic_homework_01.pdf', 'arabic_homework_01.pdf', 1, '2026-08-12 11:58:38'),
(14, 101, 'teacher', 6, 'explanation', 'Grammar Notes - Present Perfect Tense', 'Detailed notes on present perfect with examples', 'uploads/session-files/eng_grammar_01.pdf', 'eng_grammar_01.pdf', 1, '2026-08-12 11:58:38'),
(15, 101, 'teacher', 6, 'exam', 'Grammar Quiz - Present Perfect', 'Multiple choice quiz on present perfect tense', 'uploads/session-files/eng_quiz_01.pdf', 'eng_quiz_01.pdf', 1, '2026-08-12 11:58:38');

-- --------------------------------------------------------

--
-- Table structure for table `session_file_submissions`
--

CREATE TABLE `session_file_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_file_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `note` text DEFAULT NULL,
  `status` enum('pending','graded') NOT NULL DEFAULT 'pending',
  `grade` decimal(5,2) DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL,
  `graded_by_id` int(10) UNSIGNED DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `session_file_submissions`
--

INSERT INTO `session_file_submissions` (`id`, `session_file_id`, `student_id`, `file_path`, `original_name`, `note`, `status`, `grade`, `teacher_feedback`, `graded_by_id`, `graded_at`, `created_at`, `updated_at`) VALUES
(1, 2, 1025, 'uploads/session-submissions/student1_chem_hw1.pdf', 'student1_chem_hw1.pdf', 'حليت كل التمارين إلا رقم 12 لأنني لم أفهمه جيداً', 'graded', 85.00, 'أداء جيد جداً، مراجعة التمرين 12 مطلوبة', 1, '2026-08-12 11:58:38', '2026-08-12 11:58:38', '2026-08-12 12:05:59'),
(2, 2, 2, 'uploads/session-submissions/student2_chem_hw1.pdf', 'student2_chem_hw1.pdf', 'حليت جميع التمارين', 'graded', 95.00, 'ممتاز! حل مثالي وخطوات واضحة', 1, '2026-08-12 11:58:38', '2026-08-12 11:58:38', '2026-08-12 11:58:38'),
(3, 2, 3, 'uploads/session-submissions/student3_chem_hw1.pdf', 'student3_chem_hw1.pdf', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-08-12 11:58:38', '2026-08-12 11:58:38'),
(4, 4, 1025, 'student1025@example.com', 'student1025@example.com', '', 'pending', 78.00, 'جيد، تحتاج مراجعة الجزء الثالث', 1, '2026-08-12 11:58:38', '2026-08-12 11:58:38', '2026-08-12 13:01:18'),
(5, 4, 2, 'uploads/session-submissions/student2_chem_exam1.pdf', 'student2_chem_exam1.pdf', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-08-12 11:58:38', '2026-08-12 11:58:38'),
(6, 7, 1025, 'uploads/session-submissions/student1_phys_hw1.pdf', 'student1_phys_hw1.pdf', 'صعبت علي مسألة رقم 5', 'graded', 70.00, 'تحتاج تدريب أكثر على قانون نيوتن الثاني', 2, '2026-08-12 11:58:38', '2026-08-12 11:58:38', '2026-08-12 12:06:15'),
(7, 7, 2, 'uploads/session-submissions/student2_phys_hw1.pdf', 'student2_phys_hw1.pdf', 'حليت كل الأسئلة وأضفت حلاً بديلاً للمسألة الأخيرة', 'graded', 100.00, 'ممتاز! أفضل حل في الفصل', 2, '2026-08-12 11:58:38', '2026-08-12 11:58:38', '2026-08-12 11:58:38'),
(8, 10, 1025, 'uploads/session-submissions/student1_math_hw1.pdf', 'student1_math_hw1.pdf', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-08-12 11:58:38', '2026-08-12 12:06:17'),
(9, 13, 3, 'uploads/session-submissions/student3_arabic_hw1.pdf', 'student3_arabic_hw1.pdf', 'اخترت قصيدة المتنبي لتحليلها', 'pending', NULL, NULL, NULL, NULL, '2026-08-12 11:58:38', '2026-08-12 11:58:38'),
(10, 15, 2, 'uploads/session-submissions/student2_eng_quiz1.pdf', 'student2_eng_quiz1.pdf', 'I studied all the examples from the notes', 'graded', 88.00, 'Very good work! Minor errors in question 7', 6, '2026-08-12 11:58:38', '2026-08-12 11:58:38', '2026-08-12 11:58:38'),
(11, 15, 1025, 'https://example.com/my-solution.pdf', 'my-solution.pdf', 'هذا حل التمرين كامل', 'graded', 90.00, 'تيست', 201, '2026-08-12 23:10:49', '2026-08-12 12:58:42', '2026-08-12 23:10:49');

-- --------------------------------------------------------

--
-- Table structure for table `session_reschedule_requests`
--

CREATE TABLE `session_reschedule_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by_type` enum('teacher','student') NOT NULL DEFAULT 'teacher',
  `requested_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `new_day` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `new_start_time` time NOT NULL,
  `new_end_time` time NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `reviewed_by_type` enum('admin','teacher') DEFAULT NULL,
  `reviewed_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `session_reschedule_requests`
--

INSERT INTO `session_reschedule_requests` (`id`, `session_id`, `teacher_id`, `requested_by_type`, `requested_by_id`, `new_day`, `new_start_time`, `new_end_time`, `reason`, `status`, `admin_note`, `reviewed_by_type`, `reviewed_by_id`, `created_at`, `reviewed_at`) VALUES
(101, 101, 201, 'teacher', 201, 'Monday', '15:00:00', '16:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(102, 102, 201, 'teacher', 201, 'Tuesday', '16:00:00', '17:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(103, 103, 202, 'teacher', 202, 'Wednesday', '17:00:00', '18:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(104, 104, 202, 'teacher', 202, 'Thursday', '18:00:00', '19:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(105, 105, 203, 'teacher', 203, 'Saturday', '19:00:00', '20:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(106, 106, 203, 'teacher', 203, 'Sunday', '20:00:00', '21:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(107, 107, 204, 'teacher', 204, 'Monday', '15:00:00', '16:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(108, 108, 204, 'teacher', 204, 'Tuesday', '16:00:00', '17:00:00', 'تعارض مع موعد مراجعة جماعية للطلاب.', 'approved', 'تمت الموافقة وتحديث الموعد.', 'admin', 1, '2026-08-08 16:00:00', '2026-08-08 18:00:00'),
(109, 101, 201, 'student', 1025, 'Wednesday', '12:00:00', '15:00:00', '12344455', 'approved', 'تيست', 'teacher', 201, '2026-08-11 12:02:43', '2026-08-12 23:11:41');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name_ar', 'ناجح', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'site_name_en', 'Najeh', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'default_language', 'ar', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 'support_email', 'support@najeh-demo.com', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 'support_phone', '01000000000', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 'currency', 'EGP', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 'timezone', 'Africa/Cairo', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 'registration_enabled', '1', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 'maintenance_mode', '0', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 'max_login_attempts', '5', '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 'hero_badge_text_ar', 'أكثر من 40,000 طالب اختاروا Najeh', '2026-08-10 13:04:43', '2026-08-10 15:10:52'),
(12, 'hero_badge_text_en', 'Over 40,000 Students Chose Najeh', '2026-08-10 13:04:43', '2026-08-10 15:10:39'),
(13, 'hero_title_ar', 'مستقبلك الدراسي يبدأ من هنا، مع Najeh', '2026-08-10 13:04:43', '2026-08-10 15:11:00'),
(14, 'hero_title_en', 'Your Academic Future Starts Here, With Najeh', '2026-08-10 13:04:43', '2026-08-10 15:11:08'),
(15, 'hero_subtitle_ar', 'كورسات مسجلة، حصص مباشرة، وكتاب مذاكرة في مكان واحد — علشان توصل لأعلى تقدير وانت مطمئن على وقتك ومجهودك.', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(16, 'hero_subtitle_en', 'Recorded courses, live classes, and study notes all in one place — to achieve top grades effortlessly.', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(17, 'hero_primary_btn_text_ar', 'تصفح الكورسات', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(18, 'hero_primary_btn_text_en', 'Explore Courses', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(19, 'hero_primary_btn_link', '/courses', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(20, 'hero_secondary_btn_text_ar', 'خطط الاشتراك', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(21, 'hero_secondary_btn_text_en', 'Subscription Plans', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(22, 'hero_secondary_btn_link', '#plans', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(23, 'hero_success_rate', '94%', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(24, 'hero_live_sessions_count', '+1200', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(25, 'hero_rating', '4.9/5', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(26, 'stats_courses_count', '+300', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(27, 'stats_courses_label_ar', 'كورس تعليمي', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(28, 'stats_courses_label_en', 'Educational Courses', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(29, 'stats_books_count', '+120', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(30, 'stats_books_label_ar', 'كتاب ومذكرة', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(31, 'stats_books_label_en', 'Books & Notes', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(32, 'stats_teachers_count', '+85', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(33, 'stats_teachers_label_ar', 'مدرّس متخصص', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(34, 'stats_teachers_label_en', 'Expert Teachers', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(35, 'stats_students_count', '+40k', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(36, 'stats_students_label_ar', 'طالب مسجل', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(37, 'stats_students_label_en', 'Enrolled Students', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(38, 'about_title_ar', 'من نحن', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(39, 'about_title_en', 'About Us', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(40, 'about_description_ar', 'منصة Najeh التعليمية، وجهتك لتعلّم مستمر بلا حدود. نقدم كورسات مباشرة ومسجلة إلى جانب كتب تعليمية مختارة بعناية، لنكون شريكك من أول خطوة في التعلم وحتّى الإتقان.', '2026-08-10 13:04:43', '2026-08-10 15:12:06'),
(41, 'about_description_en', 'Najeh Educational Platform, your destination for continuous learning without limits. We provide live and recorded courses along with carefully selected educational books.', '2026-08-10 13:04:43', '2026-08-10 15:11:58'),
(42, 'about_stats_courses', '150+', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(43, 'about_stats_books', '40+', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(44, 'about_stats_students', '20,000+', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(45, 'about_mission_title_ar', 'رسالتنا', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(46, 'about_mission_title_en', 'Our Mission', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(47, 'about_mission_text_ar', 'نؤمن أن التعليم الجيد يجب أن يكون متاحاً للجميع، في أي وقت ومن أي مكان. لهذا أسسنا Najeh لنجمع بين مرونة التعلم عن بعد وجودة المحتوى الموثوق، سواء كنت تفضل حضور بث مباشر، أو مشاهدة كورس مسجل في وقتك الخاص، أو التعمق أكثر عبر كتاب مطبوع يصلك إلى باب بيتك.', '2026-08-10 13:04:43', '2026-08-10 15:12:16'),
(48, 'about_mission_text_en', 'We believe quality education should be accessible to everyone, anywhere, anytime. That is why we founded Najeh to blend distant learning flexibility with trusted content quality.', '2026-08-10 13:04:43', '2026-08-10 15:12:34'),
(49, 'about_offering1_title_ar', 'كورسات مباشرة', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(50, 'about_offering1_title_en', 'Live Courses', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(51, 'about_offering1_desc_ar', 'تفاعل مباشر مع المدرب واسأل أسئلتك لحظياً أثناء البث', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(52, 'about_offering1_desc_en', 'Direct interaction with instructors with real-time Q&A during streams', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(53, 'about_offering2_title_ar', 'كورسات مسجلة', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(54, 'about_offering2_title_en', 'Recorded Courses', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(55, 'about_offering2_desc_ar', 'تعلم في وقتك الخاص وبالسرعة التي تناسبك من أي جهاز', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(56, 'about_offering2_desc_en', 'Learn at your own pace from any device', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(57, 'about_offering3_title_ar', 'كتب تعليمية', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(58, 'about_offering3_title_en', 'Educational Books', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(59, 'about_offering3_desc_ar', 'مصادر مطبوعة ومختارة بعناية تدعم رحلتك التعليمية', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(60, 'about_offering3_desc_en', 'Carefully selected printed resources supporting your learning journey', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(61, 'about_val1_title_ar', 'الجودة أولاً', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(62, 'about_val1_title_en', 'Quality First', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(63, 'about_val1_desc_ar', 'محتوى مدروس بعناية من مدربين متخصصين في مجالاتهم', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(64, 'about_val1_desc_en', 'Carefully designed content by domain expert instructors', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(65, 'about_val2_title_ar', 'مرونة كاملة', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(66, 'about_val2_title_en', 'Full Flexibility', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(67, 'about_val2_desc_ar', 'تعلم في الوقت والمكان اللذين يناسبانك دون قيود', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(68, 'about_val2_desc_en', 'Learn anytime, anywhere without constraints', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(69, 'about_val3_title_ar', 'مجتمع داعم', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(70, 'about_val3_title_en', 'Supportive Community', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(71, 'about_val3_desc_ar', 'تفاعل مع زملائك ومدربيك في بيئة تعليمية محفزة', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(72, 'about_val3_desc_en', 'Interact with peers and teachers in an inspiring ecosystem', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(73, 'about_val4_title_ar', 'شهادات معتمدة', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(74, 'about_val4_title_en', 'Certified Credentials', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(75, 'about_val4_desc_ar', 'احصل على شهادة إتمام موثقة لكل كورس تنهيه بنجاح', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(76, 'about_val4_desc_en', 'Earn verified completion certificates for completed courses', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(77, 'about_cta_title_ar', 'ابدأ رحلتك التعليمية مع Najeh اليوم', '2026-08-10 13:04:43', '2026-08-10 15:12:59'),
(78, 'about_cta_title_en', 'Start Your Learning Journey With Najeh Today', '2026-08-10 13:04:43', '2026-08-10 15:13:07'),
(79, 'about_cta_subtitle_ar', 'انضم إلى آلاف المتعلمين الذين اختاروا Najeh لتطوير مهاراتهم', '2026-08-10 13:04:43', '2026-08-10 15:13:22'),
(80, 'about_cta_subtitle_en', 'Join thousands of learners who chose Najeh to upgrade their skills', '2026-08-10 13:04:43', '2026-08-10 15:13:30'),
(81, 'footer_description_ar', 'منصة تعليمية شاملة بتساعد الطالب يذاكر ويتابع تقدمه ويحقق أعلى الدرجات براحته.', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(82, 'footer_description_en', 'Comprehensive educational platform helping students study, track progress, and achieve top grades.', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(83, 'facebook_link', 'https://facebook.com', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(84, 'instagram_link', 'https://instagram.com', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(85, 'youtube_link', 'https://youtube.com', '2026-08-10 13:04:43', '2026-08-10 15:10:19'),
(86, 'whatsapp_number', '201000000000', '2026-08-10 13:04:43', '2026-08-10 15:10:19');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED DEFAULT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `study_language` enum('arabic','english','french') NOT NULL DEFAULT 'arabic',
  `name` varchar(200) NOT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `school_id`, `city_id`, `study_language`, `name`, `age`, `phone`, `email`, `password`, `image`, `is_email_verified`, `is_active`, `created_at`, `updated_at`) VALUES
(1001, 1, 1, 'arabic', 'يوسف أحمد محمد', 14, '01077001001', 'student1001@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=42', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1002, 2, 2, 'arabic', 'مريم إبراهيم عبد الله', 15, '01077001002', 'student1002@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=43', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1003, 3, 3, 'arabic', 'عمر طارق السيد', 16, '01077001003', 'student1003@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=44', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1004, 4, 4, 'arabic', 'سلمى أحمد سعيد', 17, '01077001004', 'student1004@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=45', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1005, 5, 5, 'arabic', 'آدم حسام محمد', 18, '01077001005', 'student1005@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=46', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1006, 6, 6, 'arabic', 'ليلى عماد حسن', 14, '01077001006', 'student1006@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=47', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1007, 7, 7, 'arabic', 'محمد جمال فتحي', 15, '01077001007', 'student1007@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=48', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1008, 8, 8, 'arabic', 'نور خالد علي', 16, '01077001008', 'student1008@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=49', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1009, 9, 9, 'arabic', 'عبد الرحمن سامح فؤاد', 17, '01077001009', 'student1009@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=50', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1010, 10, 10, 'arabic', 'جنى رضا مصطفى', 18, '01077001010', 'student1010@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=51', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1011, 1, 11, 'arabic', 'أحمد رامي محمود', 14, '01077001011', 'student1011@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=52', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1012, 2, 1, 'arabic', 'حبيبة شريف كمال', 15, '01077001012', 'student1012@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=53', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1013, 3, 2, 'arabic', 'علي أشرف عبد الحميد', 16, '01077001013', 'student1013@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=54', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1014, 4, 3, 'arabic', 'ملك سمير ناصر', 17, '01077001014', 'student1014@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=55', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1015, 5, 4, 'arabic', 'كريم محمود حسن', 18, '01077001015', 'student1015@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=56', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1016, 6, 5, 'arabic', 'فاطمة وائل شريف', 14, '01077001016', 'student1016@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=57', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1017, 7, 6, 'arabic', 'زياد حسام الدين', 15, '01077001017', 'student1017@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=58', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1018, 8, 7, 'arabic', 'بسملة عمرو السيد', 16, '01077001018', 'student1018@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=59', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1019, 9, 8, 'arabic', 'محمود محمد عادل', 17, '01077001019', 'student1019@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=60', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1020, 10, 9, 'arabic', 'ندى أحمد صبري', 18, '01077001020', 'student1020@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=1', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1021, 1, 10, 'arabic', 'مصطفى أحمد محمد', 14, '01077001021', 'student1021@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=2', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1022, 2, 11, 'arabic', 'روان إبراهيم عبد الله', 15, '01077001022', 'student1022@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=3', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1023, 3, 1, 'arabic', 'ياسين طارق السيد', 16, '01077001023', 'student1023@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=4', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1024, 4, 2, 'arabic', 'فرح أحمد سعيد', 17, '01077001024', 'student1024@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=5', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1025, 4, 5, 'arabic', 'إياد حسام محمد', 18, '01077001025', 'student1025@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=6', 1, 1, '2026-06-01 13:00:00', '2026-08-12 09:44:19'),
(1026, 6, 4, 'arabic', 'شهد عماد حسن', 14, '01077001026', 'student1026@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=7', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1027, 7, 5, 'arabic', 'حمزة جمال فتحي', 15, '01077001027', 'student1027@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=8', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1028, 8, 6, 'arabic', 'لارا خالد علي', 16, '01077001028', 'student1028@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=9', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1029, 9, 7, 'arabic', 'سيف سامح فؤاد', 17, '01077001029', 'student1029@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=10', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1030, 10, 8, 'arabic', 'منة رضا مصطفى', 18, '01077001030', 'student1030@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=11', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1031, 1, 9, 'arabic', 'يوسف رامي محمود', 14, '01077001031', 'student1031@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=12', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1032, 2, 10, 'arabic', 'مريم شريف كمال', 15, '01077001032', 'student1032@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=13', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1033, 3, 11, 'arabic', 'عمر أشرف عبد الحميد', 16, '01077001033', 'student1033@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=14', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1034, 4, 1, 'arabic', 'سلمى سمير ناصر', 17, '01077001034', 'student1034@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=15', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1035, 5, 2, 'arabic', 'آدم محمود حسن', 18, '01077001035', 'student1035@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=16', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1036, 6, 3, 'arabic', 'ليلى وائل شريف', 14, '01077001036', 'student1036@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=17', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1037, 7, 4, 'arabic', 'محمد حسام الدين', 15, '01077001037', 'student1037@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=18', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1038, 8, 5, 'arabic', 'نور عمرو السيد', 16, '01077001038', 'student1038@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=19', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1039, 9, 6, 'arabic', 'عبد الرحمن محمد عادل', 17, '01077001039', 'student1039@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=20', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1040, 10, 7, 'arabic', 'جنى أحمد صبري', 18, '01077001040', 'student1040@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=21', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1041, 1, 8, 'arabic', 'أحمد أحمد محمد', 14, '01077001041', 'student1041@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=22', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1042, 2, 9, 'arabic', 'حبيبة إبراهيم عبد الله', 15, '01077001042', 'student1042@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=23', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1043, 3, 10, 'arabic', 'علي طارق السيد', 16, '01077001043', 'student1043@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=24', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1044, 4, 11, 'arabic', 'ملك أحمد سعيد', 17, '01077001044', 'student1044@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=25', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1045, 5, 1, 'arabic', 'كريم حسام محمد', 18, '01077001045', 'student1045@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=26', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1046, 6, 2, 'arabic', 'فاطمة عماد حسن', 14, '01077001046', 'student1046@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=27', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1047, 7, 3, 'arabic', 'زياد جمال فتحي', 15, '01077001047', 'student1047@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=28', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1048, 8, 4, 'arabic', 'بسملة خالد علي', 16, '01077001048', 'student1048@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=29', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1049, 9, 5, 'arabic', 'محمود سامح فؤاد', 17, '01077001049', 'student1049@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=30', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1050, 10, 6, 'arabic', 'ندى رضا مصطفى', 18, '01077001050', 'student1050@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=31', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1051, 1, 7, 'arabic', 'مصطفى رامي محمود', 14, '01077001051', 'student1051@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=32', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1052, 2, 8, 'arabic', 'روان شريف كمال', 15, '01077001052', 'student1052@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=33', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1053, 3, 9, 'arabic', 'ياسين أشرف عبد الحميد', 16, '01077001053', 'student1053@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=34', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1054, 4, 10, 'arabic', 'فرح سمير ناصر', 17, '01077001054', 'student1054@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=35', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1055, 5, 11, 'arabic', 'إياد محمود حسن', 18, '01077001055', 'student1055@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=36', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1056, 6, 1, 'arabic', 'شهد وائل شريف', 14, '01077001056', 'student1056@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=37', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1057, 7, 2, 'arabic', 'حمزة حسام الدين', 15, '01077001057', 'student1057@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=38', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1058, 8, 3, 'arabic', 'لارا عمرو السيد', 16, '01077001058', 'student1058@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=39', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1059, 9, 4, 'arabic', 'سيف محمد عادل', 17, '01077001059', 'student1059@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'https://i.pravatar.cc/300?img=40', 1, 1, '2026-06-01 13:00:00', '2026-08-10 16:00:00'),
(1060, 10, 5, 'arabic', 'منة أحمد صبري', 18, '01077001060', 'student1060@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', 'uploads/avatars/img_6a79d43bec99a7.02642927.webp', 1, 1, '2026-06-01 13:00:00', '2026-08-10 13:38:03');

-- --------------------------------------------------------

--
-- Table structure for table `student_book_purchase_requests`
--

CREATE TABLE `student_book_purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_type` enum('admin','teacher') DEFAULT NULL,
  `reviewed_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_book_purchase_requests`
--

INSERT INTO `student_book_purchase_requests` (`id`, `student_id`, `book_id`, `status`, `reviewed_by_type`, `reviewed_by_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1025, 424, 'pending', NULL, NULL, NULL, '2026-08-11 13:05:48', '2026-08-11 13:05:48'),
(2, 1025, 403, 'pending', NULL, NULL, NULL, '2026-08-11 13:17:05', '2026-08-11 13:17:05'),
(3, 1025, 401, 'pending', NULL, NULL, NULL, '2026-08-11 13:18:24', '2026-08-12 14:12:41'),
(4, 1025, 405, 'pending', NULL, NULL, 'تيست', '2026-08-11 13:21:31', '2026-08-12 14:12:16'),
(6, 1001, 431, 'pending', NULL, NULL, NULL, '2026-08-13 00:09:08', '2026-08-13 00:09:08'),
(7, 1049, 415, 'pending', NULL, NULL, NULL, '2026-08-13 00:12:50', '2026-08-13 00:12:50');

-- --------------------------------------------------------

--
-- Table structure for table `student_book_subscriptions`
--

CREATE TABLE `student_book_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `granted_by_type` enum('admin','teacher','student') NOT NULL DEFAULT 'admin',
  `granted_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `price` double NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_book_subscriptions`
--

INSERT INTO `student_book_subscriptions` (`id`, `student_id`, `book_id`, `granted_by_type`, `granted_by_id`, `price`, `is_active`, `created_at`) VALUES
(1, 1025, 402, 'student', 1025, 0, 1, '2026-08-11 12:43:37'),
(2, 1, 402, 'admin', 1, 0, 1, '2026-08-11 13:19:52'),
(3, 1025, 405, 'admin', 1, 0, 1, '2026-08-11 13:36:06'),
(4, 1, 401, 'teacher', 201, 0, 1, '2026-08-12 12:33:32'),
(5, 1025, 401, 'teacher', 201, 0, 1, '2026-08-12 14:11:34');

-- 
--
--------------------------------------------------
--
--



CREATE TABLE `student_certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `certificate_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `exam_id` bigint(20) UNSIGNED DEFAULT NULL,
  `certificate_code` varchar(100) NOT NULL,
  `pdf_url` varchar(500) DEFAULT NULL,
  `score_achieved` decimal(8,2) DEFAULT NULL,
  `grade_obtained` decimal(8,2) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `student_certificates` (`id`, `certificate_id`, `student_id`, `course_id`, `exam_id`, `certificate_code`, `pdf_url`, `score_achieved`, `grade_obtained`, `issue_date`, `created_at`) VALUES
(101, 101, 1001, 201, 601, 'NAJEH-2026-S1001-C101', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 85.00, 85.00, '2026-08-01', '2026-08-10 16:00:00'),
(102, 102, 1002, 202, 602, 'NAJEH-2026-S1002-C102', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 86.00, 86.00, '2026-08-02', '2026-08-10 16:00:00'),
(103, 103, 1003, 203, 603, 'NAJEH-2026-S1003-C103', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 87.00, 87.00, '2026-08-03', '2026-08-10 16:00:00'),
(104, 104, 1004, 204, 604, 'NAJEH-2026-S1004-C104', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 88.00, 88.00, '2026-08-04', '2026-08-10 16:00:00'),
(105, 105, 1005, 205, 605, 'NAJEH-2026-S1005-C105', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 89.00, 89.00, '2026-08-05', '2026-08-10 16:00:00'),
(106, 106, 1006, 206, 606, 'NAJEH-2026-S1006-C106', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 90.00, 90.00, '2026-08-06', '2026-08-10 16:00:00'),
(107, 107, 1007, 207, 607, 'NAJEH-2026-S1007-C107', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 91.00, 91.00, '2026-08-07', '2026-08-10 16:00:00'),
(108, 108, 1008, 208, 608, 'NAJEH-2026-S1008-C108', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 92.00, 92.00, '2026-08-08', '2026-08-10 16:00:00'),
(109, 109, 1009, 209, 609, 'NAJEH-2026-S1009-C109', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 93.00, 93.00, '2026-08-09', '2026-08-10 16:00:00'),
(110, 110, 1010, 210, 610, 'NAJEH-2026-S1010-C110', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 94.00, 94.00, '2026-08-10', '2026-08-10 16:00:00'),
(111, 111, 1011, 211, 611, 'NAJEH-2026-S1011-C111', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 95.00, 95.00, '2026-08-01', '2026-08-10 16:00:00'),
(112, 112, 1012, 212, 612, 'NAJEH-2026-S1012-C112', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 96.00, 96.00, '2026-08-02', '2026-08-10 16:00:00'),
(113, 101, 1013, 201, 601, 'NAJEH-2026-S1013-C101', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 97.00, 97.00, '2026-08-03', '2026-08-10 16:00:00'),
(114, 102, 1014, 202, 602, 'NAJEH-2026-S1014-C102', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 98.00, 98.00, '2026-08-04', '2026-08-10 16:00:00'),
(115, 103, 1015, 203, 603, 'NAJEH-2026-S1015-C103', 'https://assets.openstax.org/oscms-prodcms/media/documents/introductory-statistics-2e_-_WEB.pdf', 99.00, 99.00, '2026-08-05', '2026-08-10 16:00:00'),
(116, 104, 1016, 204, 604, 'NAJEH-2026-S1016-C104', 'https://assets.openstax.org/oscms-prodcms/media/documents/Transition-Guide_iOtnrL1.pdf', 85.00, 85.00, '2026-08-06', '2026-08-10 16:00:00'),
(117, 105, 1017, 205, 605, 'NAJEH-2026-S1017-C105', 'https://assets.openstax.org/oscms-prodcms/media/documents/Chemistry2e_ReleaseNotes.pdf', 86.00, 86.00, '2026-08-07', '2026-08-10 16:00:00'),
(118, 106, 1018, 206, 606, 'NAJEH-2026-S1018-C106', 'https://assets.openstax.org/oscms-prodcms/media/documents/Pharmacology-WEB.pdf', 87.00, 87.00, '2026-08-08', '2026-08-10 16:00:00'),
(119, 107, 1019, 207, 607, 'NAJEH-2026-S1019-C107', 'https://assets.openstax.org/oscms-prodcms/media/documents/University_Physics_Volume_3_Errata_Release_Notes_2021.pdf', 88.00, 88.00, '2026-08-09', '2026-08-10 16:00:00'),
(120, 108, 1020, 208, 608, 'NAJEH-2026-S1020-C108', 'https://assets.openstax.org/oscms-prodcms/media/documents/CollegeAlgebra-OP.pdf', 89.00, 89.00, '2026-08-10', '2026-08-10 16:00:00'),
(121, 109, 1021, 209, 609, 'NAJEH-2026-S1021-C109', 'https://assets.openstax.org/oscms-prodcms/media/documents/OpenStax_Algebra__Trigonometry_Recorded_Lectures_and_Notes_for_Precalculus.pdf', 90.00, 90.00, '2026-08-01', '2026-08-10 16:00:00'),
(122, 110, 1022, 210, 610, 'NAJEH-2026-S1022-C110', 'https://assets.openstax.org/oscms-prodcms/media/documents/STUDENT_-_AP_Physics_Lab_Manual_Full_c1bLwwW.pdf', 91.00, 91.00, '2026-08-02', '2026-08-10 16:00:00'),
(123, 111, 1023, 211, 611, 'NAJEH-2026-S1023-C111', 'https://assets.openstax.org/oscms-prodcms/media/documents/Psychology2e_WEB.pdf', 92.00, 92.00, '2026-08-03', '2026-08-10 16:00:00'),
(124, 112, 1024, 212, 612, 'NAJEH-2026-S1024-C112', 'https://assets.openstax.org/oscms-prodcms/media/documents/ElementaryAlgebra2e-WEB_EjIP4sI.pdf', 93.00, 93.00, '2026-08-04', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_question_answers`
--

CREATE TABLE `student_question_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mcq_option_id` bigint(20) UNSIGNED DEFAULT NULL,
  `true_false_answer` tinyint(1) DEFAULT NULL,
  `written_answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `score_awarded` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_question_answers`
--

INSERT INTO `student_question_answers` (`id`, `student_id`, `question_id`, `unit_id`, `course_id`, `mcq_option_id`, `true_false_answer`, `written_answer_text`, `is_correct`, `score_awarded`, `created_at`) VALUES
(20001, 1001, 5001, 1003, 201, 10002, NULL, NULL, 0, 0.00, '2026-08-01 13:05:00'),
(20002, 1001, 5002, 1003, 201, 10005, NULL, NULL, 1, 2.00, '2026-08-01 13:09:00'),
(20003, 1002, 5006, 1006, 202, 10013, NULL, NULL, 1, 2.00, '2026-08-02 14:05:00'),
(20004, 1002, 5007, 1006, 202, 10017, NULL, NULL, 1, 2.00, '2026-08-02 14:09:00'),
(20005, 1003, 5011, 1009, 203, 10025, NULL, NULL, 1, 2.00, '2026-08-03 15:05:00'),
(20006, 1003, 5012, 1009, 203, 10029, NULL, NULL, 1, 2.00, '2026-08-03 15:09:00'),
(20007, 1004, 5016, 1012, 204, 10037, NULL, NULL, 1, 2.00, '2026-08-04 16:05:00'),
(20008, 1004, 5017, 1012, 204, 10041, NULL, NULL, 1, 2.00, '2026-08-04 16:09:00'),
(20009, 1005, 5021, 1015, 205, 10049, NULL, NULL, 1, 2.00, '2026-08-05 17:05:00'),
(20010, 1005, 5022, 1015, 205, 10054, NULL, NULL, 0, 0.00, '2026-08-05 17:09:00'),
(20011, 1006, 5026, 1018, 206, 10062, NULL, NULL, 0, 0.00, '2026-08-06 13:05:00'),
(20012, 1006, 5027, 1018, 206, 10065, NULL, NULL, 1, 2.00, '2026-08-06 13:09:00'),
(20013, 1007, 5031, 1021, 207, 10073, NULL, NULL, 1, 2.00, '2026-08-07 14:05:00'),
(20014, 1007, 5032, 1021, 207, 10077, NULL, NULL, 1, 2.00, '2026-08-07 14:09:00'),
(20015, 1008, 5036, 1024, 208, 10085, NULL, NULL, 1, 2.00, '2026-08-08 15:05:00'),
(20016, 1008, 5037, 1024, 208, 10089, NULL, NULL, 1, 2.00, '2026-08-08 15:09:00'),
(20017, 1009, 5041, 1027, 209, 10097, NULL, NULL, 1, 2.00, '2026-08-09 16:05:00'),
(20018, 1009, 5042, 1027, 209, 10101, NULL, NULL, 1, 2.00, '2026-08-09 16:09:00'),
(20019, 1010, 5046, 1030, 210, 10109, NULL, NULL, 1, 2.00, '2026-08-01 17:05:00'),
(20020, 1010, 5047, 1030, 210, 10114, NULL, NULL, 0, 0.00, '2026-08-01 17:09:00'),
(20021, 1011, 5051, 1033, 211, 10122, NULL, NULL, 0, 0.00, '2026-08-02 13:05:00'),
(20022, 1011, 5052, 1033, 211, 10125, NULL, NULL, 1, 2.00, '2026-08-02 13:09:00'),
(20023, 1012, 5056, 1036, 212, 10133, NULL, NULL, 1, 2.00, '2026-08-03 14:05:00'),
(20024, 1012, 5057, 1036, 212, 10137, NULL, NULL, 1, 2.00, '2026-08-03 14:09:00'),
(20025, 1013, 5061, 1039, 213, 10145, NULL, NULL, 1, 2.00, '2026-08-04 15:05:00'),
(20026, 1013, 5062, 1039, 213, 10149, NULL, NULL, 1, 2.00, '2026-08-04 15:09:00'),
(20027, 1014, 5066, 1042, 214, 10157, NULL, NULL, 1, 2.00, '2026-08-05 16:05:00'),
(20028, 1014, 5067, 1042, 214, 10161, NULL, NULL, 1, 2.00, '2026-08-05 16:09:00'),
(20029, 1015, 5071, 1045, 215, 10169, NULL, NULL, 1, 2.00, '2026-08-06 17:05:00'),
(20030, 1015, 5072, 1045, 215, 10174, NULL, NULL, 0, 0.00, '2026-08-06 17:09:00'),
(20031, 1016, 5076, 1048, 216, 10182, NULL, NULL, 0, 0.00, '2026-08-07 13:05:00'),
(20032, 1016, 5077, 1048, 216, 10185, NULL, NULL, 1, 2.00, '2026-08-07 13:09:00'),
(20033, 1017, 5081, 1051, 217, 10193, NULL, NULL, 1, 2.00, '2026-08-08 14:05:00'),
(20034, 1017, 5082, 1051, 217, 10197, NULL, NULL, 1, 2.00, '2026-08-08 14:09:00'),
(20035, 1018, 5086, 1054, 218, 10205, NULL, NULL, 1, 2.00, '2026-08-09 15:05:00'),
(20036, 1018, 5087, 1054, 218, 10209, NULL, NULL, 1, 2.00, '2026-08-09 15:09:00'),
(20037, 1019, 5091, 1057, 219, 10217, NULL, NULL, 1, 2.00, '2026-08-01 16:05:00'),
(20038, 1019, 5092, 1057, 219, 10221, NULL, NULL, 1, 2.00, '2026-08-01 16:09:00'),
(20039, 1020, 5096, 1060, 220, 10229, NULL, NULL, 1, 2.00, '2026-08-02 17:05:00'),
(20040, 1020, 5097, 1060, 220, 10234, NULL, NULL, 0, 0.00, '2026-08-02 17:09:00'),
(20041, 1021, 5101, 1063, 221, 10242, NULL, NULL, 0, 0.00, '2026-08-03 13:05:00'),
(20042, 1021, 5102, 1063, 221, 10245, NULL, NULL, 1, 2.00, '2026-08-03 13:09:00'),
(20043, 1022, 5106, 1066, 222, 10253, NULL, NULL, 1, 2.00, '2026-08-04 14:05:00'),
(20044, 1022, 5107, 1066, 222, 10257, NULL, NULL, 1, 2.00, '2026-08-04 14:09:00'),
(20045, 1023, 5111, 1069, 223, 10265, NULL, NULL, 1, 2.00, '2026-08-05 15:05:00'),
(20046, 1023, 5112, 1069, 223, 10269, NULL, NULL, 1, 2.00, '2026-08-05 15:09:00'),
(20047, 1024, 5116, 1072, 224, 10277, NULL, NULL, 1, 2.00, '2026-08-06 16:05:00'),
(20048, 1024, 5117, 1072, 224, 10281, NULL, NULL, 1, 2.00, '2026-08-06 16:09:00'),
(20049, 1025, 5001, 1003, 201, 10001, NULL, NULL, 1, 2.00, '2026-08-07 17:05:00'),
(20050, 1025, 5002, 1003, 201, 10006, NULL, NULL, 0, 0.00, '2026-08-07 17:09:00'),
(20051, 1026, 5006, 1006, 202, 10014, NULL, NULL, 0, 0.00, '2026-08-08 13:05:00'),
(20052, 1026, 5007, 1006, 202, 10017, NULL, NULL, 1, 2.00, '2026-08-08 13:09:00'),
(20053, 1027, 5011, 1009, 203, 10025, NULL, NULL, 1, 2.00, '2026-08-09 14:05:00'),
(20054, 1027, 5012, 1009, 203, 10029, NULL, NULL, 1, 2.00, '2026-08-09 14:09:00'),
(20055, 1028, 5016, 1012, 204, 10037, NULL, NULL, 1, 2.00, '2026-08-01 15:05:00'),
(20056, 1028, 5017, 1012, 204, 10041, NULL, NULL, 1, 2.00, '2026-08-01 15:09:00'),
(20057, 1029, 5021, 1015, 205, 10049, NULL, NULL, 1, 2.00, '2026-08-02 16:05:00'),
(20058, 1029, 5022, 1015, 205, 10053, NULL, NULL, 1, 2.00, '2026-08-02 16:09:00'),
(20059, 1030, 5026, 1018, 206, 10061, NULL, NULL, 1, 2.00, '2026-08-03 17:05:00'),
(20060, 1030, 5027, 1018, 206, 10066, NULL, NULL, 0, 0.00, '2026-08-03 17:09:00'),
(20061, 1031, 5031, 1021, 207, 10074, NULL, NULL, 0, 0.00, '2026-08-04 13:05:00'),
(20062, 1031, 5032, 1021, 207, 10077, NULL, NULL, 1, 2.00, '2026-08-04 13:09:00'),
(20063, 1032, 5036, 1024, 208, 10085, NULL, NULL, 1, 2.00, '2026-08-05 14:05:00'),
(20064, 1032, 5037, 1024, 208, 10089, NULL, NULL, 1, 2.00, '2026-08-05 14:09:00'),
(20065, 1033, 5041, 1027, 209, 10097, NULL, NULL, 1, 2.00, '2026-08-06 15:05:00'),
(20066, 1033, 5042, 1027, 209, 10101, NULL, NULL, 1, 2.00, '2026-08-06 15:09:00'),
(20067, 1034, 5046, 1030, 210, 10109, NULL, NULL, 1, 2.00, '2026-08-07 16:05:00'),
(20068, 1034, 5047, 1030, 210, 10113, NULL, NULL, 1, 2.00, '2026-08-07 16:09:00'),
(20069, 1035, 5051, 1033, 211, 10121, NULL, NULL, 1, 2.00, '2026-08-08 17:05:00'),
(20070, 1035, 5052, 1033, 211, 10126, NULL, NULL, 0, 0.00, '2026-08-08 17:09:00'),
(20071, 1036, 5056, 1036, 212, 10134, NULL, NULL, 0, 0.00, '2026-08-09 13:05:00'),
(20072, 1036, 5057, 1036, 212, 10137, NULL, NULL, 1, 2.00, '2026-08-09 13:09:00'),
(20073, 1037, 5061, 1039, 213, 10145, NULL, NULL, 1, 2.00, '2026-08-01 14:05:00'),
(20074, 1037, 5062, 1039, 213, 10149, NULL, NULL, 1, 2.00, '2026-08-01 14:09:00'),
(20075, 1038, 5066, 1042, 214, 10157, NULL, NULL, 1, 2.00, '2026-08-02 15:05:00'),
(20076, 1038, 5067, 1042, 214, 10161, NULL, NULL, 1, 2.00, '2026-08-02 15:09:00'),
(20077, 1039, 5071, 1045, 215, 10169, NULL, NULL, 1, 2.00, '2026-08-03 16:05:00'),
(20078, 1039, 5072, 1045, 215, 10173, NULL, NULL, 1, 2.00, '2026-08-03 16:09:00'),
(20079, 1040, 5076, 1048, 216, 10181, NULL, NULL, 1, 2.00, '2026-08-04 17:05:00'),
(20080, 1040, 5077, 1048, 216, 10186, NULL, NULL, 0, 0.00, '2026-08-04 17:09:00'),
(20081, 1041, 5081, 1051, 217, 10194, NULL, NULL, 0, 0.00, '2026-08-05 13:05:00'),
(20082, 1041, 5082, 1051, 217, 10197, NULL, NULL, 1, 2.00, '2026-08-05 13:09:00'),
(20083, 1042, 5086, 1054, 218, 10205, NULL, NULL, 1, 2.00, '2026-08-06 14:05:00'),
(20084, 1042, 5087, 1054, 218, 10209, NULL, NULL, 1, 2.00, '2026-08-06 14:09:00'),
(20085, 1043, 5091, 1057, 219, 10217, NULL, NULL, 1, 2.00, '2026-08-07 15:05:00'),
(20086, 1043, 5092, 1057, 219, 10221, NULL, NULL, 1, 2.00, '2026-08-07 15:09:00'),
(20087, 1044, 5096, 1060, 220, 10229, NULL, NULL, 1, 2.00, '2026-08-08 16:05:00'),
(20088, 1044, 5097, 1060, 220, 10233, NULL, NULL, 1, 2.00, '2026-08-08 16:09:00'),
(20089, 1045, 5101, 1063, 221, 10241, NULL, NULL, 1, 2.00, '2026-08-09 17:05:00'),
(20090, 1045, 5102, 1063, 221, 10246, NULL, NULL, 0, 0.00, '2026-08-09 17:09:00'),
(20091, 1046, 5106, 1066, 222, 10254, NULL, NULL, 0, 0.00, '2026-08-01 13:05:00'),
(20092, 1046, 5107, 1066, 222, 10257, NULL, NULL, 1, 2.00, '2026-08-01 13:09:00'),
(20093, 1047, 5111, 1069, 223, 10265, NULL, NULL, 1, 2.00, '2026-08-02 14:05:00'),
(20094, 1047, 5112, 1069, 223, 10269, NULL, NULL, 1, 2.00, '2026-08-02 14:09:00'),
(20095, 1048, 5116, 1072, 224, 10277, NULL, NULL, 1, 2.00, '2026-08-03 15:05:00'),
(20096, 1048, 5117, 1072, 224, 10281, NULL, NULL, 1, 2.00, '2026-08-03 15:09:00'),
(20097, 1049, 5001, 1003, 201, 10001, NULL, NULL, 1, 2.00, '2026-08-04 16:05:00'),
(20098, 1049, 5002, 1003, 201, 10005, NULL, NULL, 1, 2.00, '2026-08-04 16:09:00'),
(20099, 1050, 5006, 1006, 202, 10013, NULL, NULL, 1, 2.00, '2026-08-05 17:05:00'),
(20100, 1050, 5007, 1006, 202, 10018, NULL, NULL, 0, 0.00, '2026-08-05 17:09:00'),
(20101, 1051, 5011, 1009, 203, 10026, NULL, NULL, 0, 0.00, '2026-08-06 13:05:00'),
(20102, 1051, 5012, 1009, 203, 10029, NULL, NULL, 1, 2.00, '2026-08-06 13:09:00'),
(20103, 1052, 5016, 1012, 204, 10037, NULL, NULL, 1, 2.00, '2026-08-07 14:05:00'),
(20104, 1052, 5017, 1012, 204, 10041, NULL, NULL, 1, 2.00, '2026-08-07 14:09:00'),
(20105, 1053, 5021, 1015, 205, 10049, NULL, NULL, 1, 2.00, '2026-08-08 15:05:00'),
(20106, 1053, 5022, 1015, 205, 10053, NULL, NULL, 1, 2.00, '2026-08-08 15:09:00'),
(20107, 1054, 5026, 1018, 206, 10061, NULL, NULL, 1, 2.00, '2026-08-09 16:05:00'),
(20108, 1054, 5027, 1018, 206, 10065, NULL, NULL, 1, 2.00, '2026-08-09 16:09:00'),
(20109, 1055, 5031, 1021, 207, 10073, NULL, NULL, 1, 2.00, '2026-08-01 17:05:00'),
(20110, 1055, 5032, 1021, 207, 10078, NULL, NULL, 0, 0.00, '2026-08-01 17:09:00'),
(20111, 1056, 5036, 1024, 208, 10086, NULL, NULL, 0, 0.00, '2026-08-02 13:05:00'),
(20112, 1056, 5037, 1024, 208, 10089, NULL, NULL, 1, 2.00, '2026-08-02 13:09:00'),
(20113, 1057, 5041, 1027, 209, 10097, NULL, NULL, 1, 2.00, '2026-08-03 14:05:00'),
(20114, 1057, 5042, 1027, 209, 10101, NULL, NULL, 1, 2.00, '2026-08-03 14:09:00'),
(20115, 1058, 5046, 1030, 210, 10109, NULL, NULL, 1, 2.00, '2026-08-04 15:05:00'),
(20116, 1058, 5047, 1030, 210, 10113, NULL, NULL, 1, 2.00, '2026-08-04 15:09:00'),
(20117, 1059, 5051, 1033, 211, 10121, NULL, NULL, 1, 2.00, '2026-08-05 16:05:00'),
(20118, 1059, 5052, 1033, 211, 10125, NULL, NULL, 1, 2.00, '2026-08-05 16:09:00'),
(20119, 1060, 5056, 1036, 212, 10133, NULL, NULL, 1, 2.00, '2026-08-06 17:05:00'),
(20120, 1060, 5057, 1036, 212, 10138, NULL, NULL, 0, 0.00, '2026-08-06 17:09:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_reports`
--

CREATE TABLE `student_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` enum('teacher','admin') NOT NULL DEFAULT 'teacher',
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `report_type` enum('academic_performance','behavior_discipline','exam_result','attendance') NOT NULL DEFAULT 'academic_performance',
  `term` varchar(100) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `grade_label` varchar(50) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `strengths` longtext DEFAULT NULL CHECK (json_valid(`strengths`)),
  `improvements` longtext DEFAULT NULL CHECK (json_valid(`improvements`)),
  `report_date` date DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_reports`
--

INSERT INTO `student_reports` (`id`, `sender_type`, `admin_id`, `teacher_id`, `student_id`, `subject_id`, `report_type`, `term`, `score`, `max_score`, `grade_label`, `percentage`, `title`, `description`, `strengths`, `improvements`, `report_date`, `rating`, `created_at`) VALUES
(101, 'teacher', NULL, 201, 1001, 1, 'academic_performance', 'الفصل الدراسي الأول', 70.00, 100.00, 'جيد', 70.00, 'تقرير متابعة - اللغة العربية', 'أداء الطالب مستقر في اللغة العربية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(102, 'teacher', NULL, 201, 1002, 1, 'exam_result', 'الفصل الدراسي الأول', 77.00, 100.00, 'جيد جدًا', 77.00, 'تقرير متابعة - اللغة العربية', 'أداء الطالب مستقر في اللغة العربية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(103, 'teacher', NULL, 202, 1003, 2, 'attendance', 'الفصل الدراسي الأول', 84.00, 100.00, 'جيد جدًا', 84.00, 'تقرير متابعة - اللغة الإنجليزية', 'أداء الطالب مستقر في اللغة الإنجليزية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(104, 'teacher', NULL, 202, 1004, 2, 'behavior_discipline', 'الفصل الدراسي الأول', 91.00, 100.00, 'ممتاز', 91.00, 'تقرير متابعة - اللغة الإنجليزية', 'أداء الطالب مستقر في اللغة الإنجليزية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(105, 'teacher', NULL, 203, 1005, 3, 'academic_performance', 'الفصل الدراسي الأول', 98.00, 100.00, 'ممتاز', 98.00, 'تقرير متابعة - الرياضيات', 'أداء الطالب مستقر في الرياضيات مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(106, 'teacher', NULL, 203, 1006, 3, 'exam_result', 'الفصل الدراسي الأول', 74.00, 100.00, 'جيد', 74.00, 'تقرير متابعة - الرياضيات', 'أداء الطالب مستقر في الرياضيات مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(107, 'teacher', NULL, 204, 1007, 4, 'attendance', 'الفصل الدراسي الأول', 81.00, 100.00, 'جيد جدًا', 81.00, 'تقرير متابعة - الفيزياء', 'أداء الطالب مستقر في الفيزياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(108, 'teacher', NULL, 204, 1008, 4, 'behavior_discipline', 'الفصل الدراسي الأول', 88.00, 100.00, 'ممتاز', 88.00, 'تقرير متابعة - الفيزياء', 'أداء الطالب مستقر في الفيزياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(109, 'teacher', NULL, 205, 1009, 5, 'academic_performance', 'الفصل الدراسي الأول', 95.00, 100.00, 'ممتاز', 95.00, 'تقرير متابعة - الكيمياء', 'أداء الطالب مستقر في الكيمياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(110, 'teacher', NULL, 205, 1010, 5, 'exam_result', 'الفصل الدراسي الأول', 71.00, 100.00, 'جيد', 71.00, 'تقرير متابعة - الكيمياء', 'أداء الطالب مستقر في الكيمياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(111, 'teacher', NULL, 206, 1011, 6, 'attendance', 'الفصل الدراسي الأول', 78.00, 100.00, 'جيد جدًا', 78.00, 'تقرير متابعة - الأحياء', 'أداء الطالب مستقر في الأحياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(112, 'teacher', NULL, 206, 1012, 6, 'behavior_discipline', 'الفصل الدراسي الأول', 85.00, 100.00, 'ممتاز', 85.00, 'تقرير متابعة - الأحياء', 'أداء الطالب مستقر في الأحياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(113, 'teacher', NULL, 207, 1013, 7, 'academic_performance', 'الفصل الدراسي الأول', 92.00, 100.00, 'ممتاز', 92.00, 'تقرير متابعة - التاريخ', 'أداء الطالب مستقر في التاريخ مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(114, 'teacher', NULL, 207, 1014, 7, 'exam_result', 'الفصل الدراسي الأول', 99.00, 100.00, 'ممتاز', 99.00, 'تقرير متابعة - التاريخ', 'أداء الطالب مستقر في التاريخ مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(115, 'teacher', NULL, 208, 1015, 8, 'attendance', 'الفصل الدراسي الأول', 75.00, 100.00, 'جيد جدًا', 75.00, 'تقرير متابعة - الجغرافيا', 'أداء الطالب مستقر في الجغرافيا مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(116, 'teacher', NULL, 208, 1016, 8, 'behavior_discipline', 'الفصل الدراسي الأول', 82.00, 100.00, 'جيد جدًا', 82.00, 'تقرير متابعة - الجغرافيا', 'أداء الطالب مستقر في الجغرافيا مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(117, 'teacher', NULL, 209, 1017, 9, 'academic_performance', 'الفصل الدراسي الأول', 89.00, 100.00, 'ممتاز', 89.00, 'تقرير متابعة - الفلسفة والمنطق', 'أداء الطالب مستقر في الفلسفة والمنطق مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(118, 'teacher', NULL, 209, 1018, 9, 'exam_result', 'الفصل الدراسي الأول', 96.00, 100.00, 'ممتاز', 96.00, 'تقرير متابعة - الفلسفة والمنطق', 'أداء الطالب مستقر في الفلسفة والمنطق مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(119, 'teacher', NULL, 210, 1019, 10, 'attendance', 'الفصل الدراسي الأول', 72.00, 100.00, 'جيد', 72.00, 'تقرير متابعة - علوم الحاسب', 'أداء الطالب مستقر في علوم الحاسب مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(120, 'teacher', NULL, 210, 1020, 10, 'behavior_discipline', 'الفصل الدراسي الأول', 79.00, 100.00, 'جيد جدًا', 79.00, 'تقرير متابعة - علوم الحاسب', 'أداء الطالب مستقر في علوم الحاسب مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(121, 'teacher', NULL, 211, 1021, 11, 'academic_performance', 'الفصل الدراسي الأول', 86.00, 100.00, 'ممتاز', 86.00, 'تقرير متابعة - اللغة الفرنسية', 'أداء الطالب مستقر في اللغة الفرنسية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(122, 'teacher', NULL, 211, 1022, 11, 'exam_result', 'الفصل الدراسي الأول', 93.00, 100.00, 'ممتاز', 93.00, 'تقرير متابعة - اللغة الفرنسية', 'أداء الطالب مستقر في اللغة الفرنسية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(123, 'teacher', NULL, 212, 1023, 15, 'attendance', 'الفصل الدراسي الأول', 100.00, 100.00, 'ممتاز', 100.00, 'تقرير متابعة - العلوم المتكاملة', 'أداء الطالب مستقر في العلوم المتكاملة مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(124, 'teacher', NULL, 212, 1024, 15, 'behavior_discipline', 'الفصل الدراسي الأول', 76.00, 100.00, 'جيد جدًا', 76.00, 'تقرير متابعة - العلوم المتكاملة', 'أداء الطالب مستقر في العلوم المتكاملة مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(125, 'teacher', NULL, 201, 1025, 1, 'academic_performance', 'الفصل الدراسي الأول', 83.00, 100.00, 'جيد جدًا', 83.00, 'تقرير متابعة - اللغة العربية', 'أداء الطالب مستقر في اللغة العربية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(126, 'teacher', NULL, 201, 1026, 1, 'exam_result', 'الفصل الدراسي الأول', 90.00, 100.00, 'ممتاز', 90.00, 'تقرير متابعة - اللغة العربية', 'أداء الطالب مستقر في اللغة العربية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(127, 'teacher', NULL, 202, 1027, 2, 'attendance', 'الفصل الدراسي الأول', 97.00, 100.00, 'ممتاز', 97.00, 'تقرير متابعة - اللغة الإنجليزية', 'أداء الطالب مستقر في اللغة الإنجليزية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(128, 'teacher', NULL, 202, 1028, 2, 'behavior_discipline', 'الفصل الدراسي الأول', 73.00, 100.00, 'جيد', 73.00, 'تقرير متابعة - اللغة الإنجليزية', 'أداء الطالب مستقر في اللغة الإنجليزية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(129, 'teacher', NULL, 203, 1029, 3, 'academic_performance', 'الفصل الدراسي الأول', 80.00, 100.00, 'جيد جدًا', 80.00, 'تقرير متابعة - الرياضيات', 'أداء الطالب مستقر في الرياضيات مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(130, 'teacher', NULL, 203, 1030, 3, 'exam_result', 'الفصل الدراسي الأول', 87.00, 100.00, 'ممتاز', 87.00, 'تقرير متابعة - الرياضيات', 'أداء الطالب مستقر في الرياضيات مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(131, 'teacher', NULL, 204, 1031, 4, 'attendance', 'الفصل الدراسي الأول', 94.00, 100.00, 'ممتاز', 94.00, 'تقرير متابعة - الفيزياء', 'أداء الطالب مستقر في الفيزياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(132, 'teacher', NULL, 204, 1032, 4, 'behavior_discipline', 'الفصل الدراسي الأول', 70.00, 100.00, 'جيد', 70.00, 'تقرير متابعة - الفيزياء', 'أداء الطالب مستقر في الفيزياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(133, 'teacher', NULL, 205, 1033, 5, 'academic_performance', 'الفصل الدراسي الأول', 77.00, 100.00, 'جيد جدًا', 77.00, 'تقرير متابعة - الكيمياء', 'أداء الطالب مستقر في الكيمياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(134, 'teacher', NULL, 205, 1034, 5, 'exam_result', 'الفصل الدراسي الأول', 84.00, 100.00, 'جيد جدًا', 84.00, 'تقرير متابعة - الكيمياء', 'أداء الطالب مستقر في الكيمياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(135, 'teacher', NULL, 206, 1035, 6, 'attendance', 'الفصل الدراسي الأول', 91.00, 100.00, 'ممتاز', 91.00, 'تقرير متابعة - الأحياء', 'أداء الطالب مستقر في الأحياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(136, 'teacher', NULL, 206, 1036, 6, 'behavior_discipline', 'الفصل الدراسي الأول', 98.00, 100.00, 'ممتاز', 98.00, 'تقرير متابعة - الأحياء', 'أداء الطالب مستقر في الأحياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(137, 'teacher', NULL, 207, 1037, 7, 'academic_performance', 'الفصل الدراسي الأول', 74.00, 100.00, 'جيد', 74.00, 'تقرير متابعة - التاريخ', 'أداء الطالب مستقر في التاريخ مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(138, 'teacher', NULL, 207, 1038, 7, 'exam_result', 'الفصل الدراسي الأول', 81.00, 100.00, 'جيد جدًا', 81.00, 'تقرير متابعة - التاريخ', 'أداء الطالب مستقر في التاريخ مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(139, 'teacher', NULL, 208, 1039, 8, 'attendance', 'الفصل الدراسي الأول', 88.00, 100.00, 'ممتاز', 88.00, 'تقرير متابعة - الجغرافيا', 'أداء الطالب مستقر في الجغرافيا مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(140, 'teacher', NULL, 208, 1040, 8, 'behavior_discipline', 'الفصل الدراسي الأول', 95.00, 100.00, 'ممتاز', 95.00, 'تقرير متابعة - الجغرافيا', 'أداء الطالب مستقر في الجغرافيا مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(141, 'teacher', NULL, 209, 1041, 9, 'academic_performance', 'الفصل الدراسي الأول', 71.00, 100.00, 'جيد', 71.00, 'تقرير متابعة - الفلسفة والمنطق', 'أداء الطالب مستقر في الفلسفة والمنطق مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(142, 'teacher', NULL, 209, 1042, 9, 'exam_result', 'الفصل الدراسي الأول', 78.00, 100.00, 'جيد جدًا', 78.00, 'تقرير متابعة - الفلسفة والمنطق', 'أداء الطالب مستقر في الفلسفة والمنطق مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(143, 'teacher', NULL, 210, 1043, 10, 'attendance', 'الفصل الدراسي الأول', 85.00, 100.00, 'ممتاز', 85.00, 'تقرير متابعة - علوم الحاسب', 'أداء الطالب مستقر في علوم الحاسب مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(144, 'teacher', NULL, 210, 1044, 10, 'behavior_discipline', 'الفصل الدراسي الأول', 92.00, 100.00, 'ممتاز', 92.00, 'تقرير متابعة - علوم الحاسب', 'أداء الطالب مستقر في علوم الحاسب مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(145, 'teacher', NULL, 211, 1045, 11, 'academic_performance', 'الفصل الدراسي الأول', 99.00, 100.00, 'ممتاز', 99.00, 'تقرير متابعة - اللغة الفرنسية', 'أداء الطالب مستقر في اللغة الفرنسية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(146, 'teacher', NULL, 211, 1046, 11, 'exam_result', 'الفصل الدراسي الأول', 75.00, 100.00, 'جيد جدًا', 75.00, 'تقرير متابعة - اللغة الفرنسية', 'أداء الطالب مستقر في اللغة الفرنسية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(147, 'teacher', NULL, 212, 1047, 15, 'attendance', 'الفصل الدراسي الأول', 82.00, 100.00, 'جيد جدًا', 82.00, 'تقرير متابعة - العلوم المتكاملة', 'أداء الطالب مستقر في العلوم المتكاملة مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(148, 'teacher', NULL, 212, 1048, 15, 'behavior_discipline', 'الفصل الدراسي الأول', 89.00, 100.00, 'ممتاز', 89.00, 'تقرير متابعة - العلوم المتكاملة', 'أداء الطالب مستقر في العلوم المتكاملة مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(149, 'teacher', NULL, 201, 1049, 1, 'academic_performance', 'الفصل الدراسي الأول', 96.00, 100.00, 'ممتاز', 96.00, 'تقرير متابعة - اللغة العربية', 'أداء الطالب مستقر في اللغة العربية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(150, 'teacher', NULL, 201, 1050, 1, 'exam_result', 'الفصل الدراسي الأول', 72.00, 100.00, 'جيد', 72.00, 'تقرير متابعة - اللغة العربية', 'أداء الطالب مستقر في اللغة العربية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(151, 'teacher', NULL, 202, 1051, 2, 'attendance', 'الفصل الدراسي الأول', 79.00, 100.00, 'جيد جدًا', 79.00, 'تقرير متابعة - اللغة الإنجليزية', 'أداء الطالب مستقر في اللغة الإنجليزية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(152, 'teacher', NULL, 202, 1052, 2, 'behavior_discipline', 'الفصل الدراسي الأول', 86.00, 100.00, 'ممتاز', 86.00, 'تقرير متابعة - اللغة الإنجليزية', 'أداء الطالب مستقر في اللغة الإنجليزية مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(153, 'teacher', NULL, 203, 1053, 3, 'academic_performance', 'الفصل الدراسي الأول', 93.00, 100.00, 'ممتاز', 93.00, 'تقرير متابعة - الرياضيات', 'أداء الطالب مستقر في الرياضيات مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(154, 'teacher', NULL, 203, 1054, 3, 'exam_result', 'الفصل الدراسي الأول', 100.00, 100.00, 'ممتاز', 100.00, 'تقرير متابعة - الرياضيات', 'أداء الطالب مستقر في الرياضيات مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(155, 'teacher', NULL, 204, 1055, 4, 'attendance', 'الفصل الدراسي الأول', 76.00, 100.00, 'جيد جدًا', 76.00, 'تقرير متابعة - الفيزياء', 'أداء الطالب مستقر في الفيزياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(156, 'teacher', NULL, 204, 1056, 4, 'behavior_discipline', 'الفصل الدراسي الأول', 83.00, 100.00, 'جيد جدًا', 83.00, 'تقرير متابعة - الفيزياء', 'أداء الطالب مستقر في الفيزياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(157, 'teacher', NULL, 205, 1057, 5, 'academic_performance', 'الفصل الدراسي الأول', 90.00, 100.00, 'ممتاز', 90.00, 'تقرير متابعة - الكيمياء', 'أداء الطالب مستقر في الكيمياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(158, 'teacher', NULL, 205, 1058, 5, 'exam_result', 'الفصل الدراسي الأول', 97.00, 100.00, 'ممتاز', 97.00, 'تقرير متابعة - الكيمياء', 'أداء الطالب مستقر في الكيمياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 5, '2026-08-10 16:00:00'),
(159, 'teacher', NULL, 206, 1059, 6, 'attendance', 'الفصل الدراسي الأول', 73.00, 100.00, 'جيد', 73.00, 'تقرير متابعة - الأحياء', 'أداء الطالب مستقر في الأحياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00'),
(160, 'teacher', NULL, 206, 1060, 6, 'behavior_discipline', 'الفصل الدراسي الأول', 80.00, 100.00, 'جيد جدًا', 80.00, 'تقرير متابعة - الأحياء', 'أداء الطالب مستقر في الأحياء مع تحسن واضح في حل الأسئلة والتفاعل مع المحتوى.', '[\"الالتزام بالمحاضرات\", \"حل الواجبات\", \"تحسن سرعة الإجابة\"]', '[\"مراجعة الأخطاء أسبوعيًا\", \"زيادة التدريب على الأسئلة المركبة\"]', '2026-08-10', 4, '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_subscriptions`
--

CREATE TABLE `student_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_code_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subscription_type` enum('course','subject','teacher','package','custom','book') NOT NULL DEFAULT 'custom',
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subscriptions`
--

INSERT INTO `student_subscriptions` (`id`, `student_id`, `subscription_code_id`, `subscription_type`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1001, 1001, 101, 'course', '2026-06-01 00:00:00', '2026-11-28 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1002, 1002, 102, 'subject', '2026-06-02 00:00:00', '2026-11-29 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1003, 1003, 103, 'teacher', '2026-06-03 00:00:00', '2026-11-30 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1004, 1004, 104, 'package', '2026-06-04 00:00:00', '2026-12-01 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1005, 1005, 105, 'custom', '2026-06-05 00:00:00', '2026-12-02 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1006, 1006, 106, 'course', '2026-06-06 00:00:00', '2026-12-03 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1007, 1007, 107, 'subject', '2026-06-07 00:00:00', '2026-12-04 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1008, 1008, 108, 'teacher', '2026-06-08 00:00:00', '2026-12-05 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1009, 1009, 109, 'package', '2026-06-09 00:00:00', '2026-12-06 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1010, 1010, 110, 'custom', '2026-06-10 00:00:00', '2026-12-07 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1011, 1011, 111, 'course', '2026-06-11 00:00:00', '2026-12-08 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1012, 1012, 112, 'subject', '2026-06-12 00:00:00', '2026-12-09 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1013, 1013, 101, 'teacher', '2026-06-13 00:00:00', '2026-12-10 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1014, 1014, 102, 'package', '2026-06-14 00:00:00', '2026-12-11 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1015, 1015, 103, 'custom', '2026-06-15 00:00:00', '2026-12-12 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1016, 1016, 104, 'course', '2026-06-16 00:00:00', '2026-12-13 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1017, 1017, 105, 'subject', '2026-06-17 00:00:00', '2026-12-14 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1018, 1018, 106, 'teacher', '2026-06-18 00:00:00', '2026-12-15 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1019, 1019, 107, 'package', '2026-06-19 00:00:00', '2026-12-16 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1020, 1020, 108, 'custom', '2026-06-20 00:00:00', '2026-12-17 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1021, 1021, 109, 'course', '2026-06-21 00:00:00', '2026-12-18 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1022, 1022, 110, 'subject', '2026-06-22 00:00:00', '2026-12-19 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1023, 1023, 111, 'teacher', '2026-06-23 00:00:00', '2026-12-20 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1024, 1024, 112, 'package', '2026-06-24 00:00:00', '2026-12-21 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1025, 1025, 101, 'custom', '2026-06-25 00:00:00', '2026-12-22 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1026, 1026, 102, 'course', '2026-06-26 00:00:00', '2026-12-23 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1027, 1027, 103, 'subject', '2026-06-27 00:00:00', '2026-12-24 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1028, 1028, 104, 'teacher', '2026-06-28 00:00:00', '2026-12-25 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1029, 1029, 105, 'package', '2026-06-29 00:00:00', '2026-12-26 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1030, 1030, 106, 'custom', '2026-06-30 00:00:00', '2026-12-27 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1031, 1031, 107, 'course', '2026-07-01 00:00:00', '2026-12-28 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1032, 1032, 108, 'subject', '2026-07-02 00:00:00', '2026-12-29 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1033, 1033, 109, 'teacher', '2026-07-03 00:00:00', '2026-12-30 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1034, 1034, 110, 'package', '2026-07-04 00:00:00', '2026-12-31 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1035, 1035, 111, 'custom', '2026-07-05 00:00:00', '2027-01-01 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1036, 1036, 112, 'course', '2026-07-06 00:00:00', '2027-01-02 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1037, 1037, 101, 'subject', '2026-07-07 00:00:00', '2027-01-03 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1038, 1038, 102, 'teacher', '2026-07-08 00:00:00', '2027-01-04 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1039, 1039, 103, 'package', '2026-07-09 00:00:00', '2027-01-05 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1040, 1040, 104, 'custom', '2026-07-10 00:00:00', '2027-01-06 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1041, 1041, 105, 'course', '2026-07-11 00:00:00', '2027-01-07 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1042, 1042, 106, 'subject', '2026-07-12 00:00:00', '2027-01-08 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1043, 1043, 107, 'teacher', '2026-07-13 00:00:00', '2027-01-09 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1044, 1044, 108, 'package', '2026-07-14 00:00:00', '2027-01-10 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1045, 1045, 109, 'custom', '2026-07-15 00:00:00', '2027-01-11 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1046, 1046, 110, 'course', '2026-06-01 00:00:00', '2026-11-28 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1047, 1047, 111, 'subject', '2026-06-02 00:00:00', '2026-11-29 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1048, 1048, 112, 'teacher', '2026-06-03 00:00:00', '2026-11-30 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1049, 1049, 101, 'package', '2026-06-04 00:00:00', '2026-12-01 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1050, 1050, 102, 'custom', '2026-06-05 00:00:00', '2026-12-02 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1051, 1051, 103, 'course', '2026-06-06 00:00:00', '2026-12-03 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1052, 1052, 104, 'subject', '2026-06-07 00:00:00', '2026-12-04 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1053, 1053, 105, 'teacher', '2026-06-08 00:00:00', '2026-12-05 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1054, 1054, 106, 'package', '2026-06-09 00:00:00', '2026-12-06 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1055, 1055, 107, 'custom', '2026-06-10 00:00:00', '2026-12-07 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1056, 1056, 108, 'course', '2026-06-11 00:00:00', '2026-12-08 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1057, 1057, 109, 'subject', '2026-06-12 00:00:00', '2026-12-09 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1058, 1058, 110, 'teacher', '2026-06-13 00:00:00', '2026-12-10 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1059, 1059, 111, 'package', '2026-06-14 00:00:00', '2026-12-11 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1060, 1060, 112, 'custom', '2026-06-15 00:00:00', '2026-12-12 00:00:00', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(1061, 1001, NULL, 'subject', '2026-08-11 11:05:46', '2026-09-10 11:05:46', 1, '2026-08-11 11:05:46', '2026-08-11 11:05:46'),
(1062, 1025, NULL, 'custom', '2026-08-11 12:43:37', NULL, 1, '2026-08-11 12:43:37', '2026-08-11 12:43:37'),
(1063, 1025, NULL, 'custom', '2026-08-11 12:43:47', NULL, 1, '2026-08-11 12:43:47', '2026-08-11 12:43:47'),
(1064, 1, NULL, 'custom', '2026-08-11 13:19:52', NULL, 1, '2026-08-11 13:19:52', '2026-08-11 13:19:52'),
(1065, 1, NULL, 'custom', '2026-08-12 12:33:32', NULL, 1, '2026-08-12 12:33:32', '2026-08-12 12:33:32'),
(1066, 1025, 112, 'custom', '2026-08-12 15:21:07', '2027-02-08 15:21:07', 1, '2026-08-12 15:21:07', '2026-08-12 15:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `student_subscription_books`
--

CREATE TABLE `student_subscription_books` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_subscription_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subscription_books`
--

INSERT INTO `student_subscription_books` (`id`, `student_subscription_id`, `book_id`) VALUES
(1, 1062, 402),
(2, 1063, 402),
(3, 1064, 402),
(4, 1065, 401);

-- --------------------------------------------------------

--
-- Table structure for table `student_subscription_courses`
--

CREATE TABLE `student_subscription_courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_subscription_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subscription_courses`
--

INSERT INTO `student_subscription_courses` (`id`, `student_subscription_id`, `course_id`) VALUES
(1001, 1001, 201),
(1003, 1004, 207),
(1002, 1004, 208),
(1004, 1005, 209),
(1005, 1005, 210),
(1006, 1006, 212),
(1007, 1009, 217),
(1008, 1009, 218),
(1010, 1010, 219),
(1009, 1010, 220),
(1011, 1011, 221),
(1013, 1014, 203),
(1012, 1014, 204),
(1014, 1015, 205),
(1015, 1015, 206),
(1016, 1016, 208),
(1017, 1019, 213),
(1018, 1019, 214),
(1020, 1020, 215),
(1019, 1020, 216),
(1021, 1021, 217),
(1023, 1024, 223),
(1022, 1024, 224),
(1025, 1025, 1),
(1024, 1025, 201),
(1026, 1026, 204),
(1027, 1029, 209),
(1028, 1029, 210),
(1030, 1030, 211),
(1029, 1030, 212),
(1031, 1031, 213),
(1033, 1034, 219),
(1032, 1034, 220),
(1034, 1035, 221),
(1035, 1035, 222),
(1036, 1036, 224),
(1037, 1039, 205),
(1038, 1039, 206),
(1040, 1040, 207),
(1039, 1040, 208),
(1041, 1041, 209),
(1043, 1044, 215),
(1042, 1044, 216),
(1044, 1045, 217),
(1045, 1045, 218),
(1046, 1046, 220),
(1047, 1049, 201),
(1048, 1049, 202),
(1050, 1050, 203),
(1049, 1050, 204),
(1051, 1051, 205),
(1053, 1054, 211),
(1052, 1054, 212),
(1054, 1055, 213),
(1055, 1055, 214),
(1056, 1056, 216),
(1057, 1059, 221),
(1058, 1059, 222),
(1060, 1060, 223),
(1059, 1060, 224),
(1061, 1066, 223),
(1062, 1066, 224);

-- --------------------------------------------------------

--
-- Table structure for table `student_subscription_subjects`
--

CREATE TABLE `student_subscription_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_subscription_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subscription_subjects`
--

INSERT INTO `student_subscription_subjects` (`id`, `student_subscription_id`, `subject_id`) VALUES
(1001, 1002, 2),
(1002, 1004, 4),
(1003, 1005, 5),
(1004, 1007, 7),
(1005, 1009, 9),
(1006, 1010, 10),
(1007, 1012, 15),
(1008, 1014, 2),
(1009, 1015, 3),
(1010, 1017, 5),
(1011, 1019, 7),
(1012, 1020, 8),
(1013, 1022, 10),
(1014, 1024, 15),
(1015, 1025, 1),
(1016, 1027, 3),
(1017, 1029, 5),
(1018, 1030, 6),
(1019, 1032, 8),
(1020, 1034, 10),
(1021, 1035, 11),
(1022, 1037, 1),
(1023, 1039, 3),
(1024, 1040, 4),
(1025, 1042, 6),
(1026, 1044, 8),
(1027, 1045, 9),
(1028, 1047, 11),
(1029, 1049, 1),
(1030, 1050, 2),
(1031, 1052, 4),
(1032, 1054, 6),
(1033, 1055, 7),
(1034, 1057, 9),
(1035, 1059, 11),
(1036, 1060, 15),
(1038, 1061, 13),
(1037, 1061, 15),
(1039, 1066, 15);

-- --------------------------------------------------------

--
-- Table structure for table `student_subscription_teachers`
--

CREATE TABLE `student_subscription_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_subscription_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subscription_teachers`
--

INSERT INTO `student_subscription_teachers` (`id`, `student_subscription_id`, `teacher_id`) VALUES
(1001, 1003, 203),
(1002, 1004, 204),
(1003, 1005, 205),
(1004, 1008, 208),
(1005, 1009, 209),
(1006, 1010, 210),
(1007, 1013, 201),
(1008, 1014, 202),
(1009, 1015, 203),
(1010, 1018, 206),
(1011, 1019, 207),
(1012, 1020, 208),
(1013, 1023, 211),
(1014, 1024, 212),
(1015, 1025, 1),
(1016, 1028, 204),
(1017, 1029, 205),
(1018, 1030, 206),
(1019, 1033, 209),
(1020, 1034, 210),
(1021, 1035, 211),
(1022, 1038, 202),
(1023, 1039, 203),
(1024, 1040, 204),
(1025, 1043, 207),
(1026, 1044, 208),
(1027, 1045, 209),
(1028, 1048, 212),
(1029, 1049, 201),
(1030, 1050, 202),
(1031, 1053, 205),
(1032, 1054, 206),
(1033, 1055, 207),
(1034, 1058, 210),
(1035, 1059, 211),
(1036, 1060, 212),
(1037, 1066, 212);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'arabic', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 'english', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 'mathematics', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 'physics', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 'chemistry', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 'biology', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 'history', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 'geography', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 'philosophy-logic', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 'computer-science', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 'french', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 'german', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(13, 'economics', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(14, 'psychology', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(15, 'integrated-science', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `subjects_translations`
--

CREATE TABLE `subjects_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects_translations`
--

INSERT INTO `subjects_translations` (`id`, `subject_id`, `language_id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'اللغة العربية', 'النحو والبلاغة والقراءة والتعبير.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(2, 1, 2, 'Arabic Language', 'Arabic Language learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(3, 2, 1, 'اللغة الإنجليزية', 'Grammar, reading, writing and communication.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(4, 2, 2, 'English Language', 'English Language learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(5, 3, 1, 'الرياضيات', 'الجبر والهندسة والتفاضل والإحصاء.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(6, 3, 2, 'Mathematics', 'Mathematics learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(7, 4, 1, 'الفيزياء', 'الميكانيكا والكهرباء والموجات والفيزياء الحديثة.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(8, 4, 2, 'Physics', 'Physics learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(9, 5, 1, 'الكيمياء', 'الكيمياء العامة والعضوية والتحليلية.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(10, 5, 2, 'Chemistry', 'Chemistry learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(11, 6, 1, 'الأحياء', 'الخلية والوراثة ووظائف الأعضاء والبيئة.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(12, 6, 2, 'Biology', 'Biology learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(13, 7, 1, 'التاريخ', 'التاريخ المصري والعربي والعالمي وتحليل المصادر.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(14, 7, 2, 'History', 'History learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(15, 8, 1, 'الجغرافيا', 'الجغرافيا الطبيعية والبشرية والخرائط.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(16, 8, 2, 'Geography', 'Geography learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(17, 9, 1, 'الفلسفة والمنطق', 'التفكير النقدي والاستدلال والمنطق.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(18, 9, 2, 'Philosophy & Logic', 'Philosophy & Logic learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(19, 10, 1, 'علوم الحاسب', 'البرمجة والخوارزميات وقواعد البيانات.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(20, 10, 2, 'Computer Science', 'Computer Science learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(21, 11, 1, 'اللغة الفرنسية', 'Vocabulary, grammar and conversation.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(22, 11, 2, 'French Language', 'French Language learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(23, 12, 1, 'اللغة الألمانية', 'Vocabulary, grammar and communication.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(24, 12, 2, 'German Language', 'German Language learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(25, 13, 1, 'الاقتصاد', 'المفاهيم الاقتصادية والأسواق والقرارات المالية.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(26, 13, 2, 'Economics', 'Economics learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(27, 14, 1, 'علم النفس', 'السلوك والتعلم والدافعية والعمليات المعرفية.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(28, 14, 2, 'Psychology', 'Psychology learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(29, 15, 1, 'العلوم المتكاملة', 'المادة والطاقة والأحياء والأرض في إطار متكامل.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00'),
(30, 15, 2, 'Integrated Science', 'Integrated Science learning content with structured explanations, practice and assessment.', 1, '2026-08-10 16:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `subject_academic_years`
--

CREATE TABLE `subject_academic_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subject_academic_years`
--

INSERT INTO `subject_academic_years` (`id`, `subject_id`, `academic_year_id`, `is_active`, `created_at`) VALUES
(1, 1, 9, 1, '2026-08-10 16:00:00'),
(2, 1, 10, 1, '2026-08-10 16:00:00'),
(3, 1, 11, 1, '2026-08-10 16:00:00'),
(4, 1, 12, 1, '2026-08-10 16:00:00'),
(5, 2, 9, 1, '2026-08-10 16:00:00'),
(6, 2, 10, 1, '2026-08-10 16:00:00'),
(7, 2, 11, 1, '2026-08-10 16:00:00'),
(8, 2, 12, 1, '2026-08-10 16:00:00'),
(9, 3, 9, 1, '2026-08-10 16:00:00'),
(10, 3, 10, 1, '2026-08-10 16:00:00'),
(11, 3, 11, 1, '2026-08-10 16:00:00'),
(12, 3, 12, 1, '2026-08-10 16:00:00'),
(13, 4, 9, 1, '2026-08-10 16:00:00'),
(14, 4, 10, 1, '2026-08-10 16:00:00'),
(15, 4, 11, 1, '2026-08-10 16:00:00'),
(16, 4, 12, 1, '2026-08-10 16:00:00'),
(17, 5, 9, 1, '2026-08-10 16:00:00'),
(18, 5, 10, 1, '2026-08-10 16:00:00'),
(19, 5, 11, 1, '2026-08-10 16:00:00'),
(20, 5, 12, 1, '2026-08-10 16:00:00'),
(21, 6, 9, 1, '2026-08-10 16:00:00'),
(22, 6, 10, 1, '2026-08-10 16:00:00'),
(23, 6, 11, 1, '2026-08-10 16:00:00'),
(24, 6, 12, 1, '2026-08-10 16:00:00'),
(25, 7, 9, 1, '2026-08-10 16:00:00'),
(26, 7, 10, 1, '2026-08-10 16:00:00'),
(27, 7, 11, 1, '2026-08-10 16:00:00'),
(28, 7, 12, 1, '2026-08-10 16:00:00'),
(29, 8, 9, 1, '2026-08-10 16:00:00'),
(30, 8, 10, 1, '2026-08-10 16:00:00'),
(31, 8, 11, 1, '2026-08-10 16:00:00'),
(32, 8, 12, 1, '2026-08-10 16:00:00'),
(33, 9, 9, 1, '2026-08-10 16:00:00'),
(34, 9, 10, 1, '2026-08-10 16:00:00'),
(35, 9, 11, 1, '2026-08-10 16:00:00'),
(36, 9, 12, 1, '2026-08-10 16:00:00'),
(37, 10, 9, 1, '2026-08-10 16:00:00'),
(38, 10, 10, 1, '2026-08-10 16:00:00'),
(39, 10, 11, 1, '2026-08-10 16:00:00'),
(40, 10, 12, 1, '2026-08-10 16:00:00'),
(41, 11, 9, 1, '2026-08-10 16:00:00'),
(42, 11, 10, 1, '2026-08-10 16:00:00'),
(43, 11, 11, 1, '2026-08-10 16:00:00'),
(44, 11, 12, 1, '2026-08-10 16:00:00'),
(45, 15, 9, 1, '2026-08-10 16:00:00'),
(46, 15, 10, 1, '2026-08-10 16:00:00'),
(47, 15, 11, 1, '2026-08-10 16:00:00'),
(48, 15, 12, 1, '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_codes`
--

CREATE TABLE `subscription_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `duration_days` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_codes`
--

INSERT INTO `subscription_codes` (`id`, `code`, `title`, `description`, `price`, `max_uses`, `used_count`, `valid_from`, `valid_to`, `duration_days`, `is_active`, `created_by_id`, `created_at`, `updated_at`) VALUES
(101, '123456789102', 'Arabic Language 2026 Package', 'اشتراك تعليمي في اللغة العربية لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-12 15:20:08'),
(102, 'NAJEH-02-2026', 'English Language 2026 Package', 'اشتراك تعليمي في اللغة الإنجليزية لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(103, 'NAJEH-03-2026', 'Mathematics 2026 Package', 'اشتراك تعليمي في الرياضيات لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(104, 'NAJEH-04-2026', 'Physics 2026 Package', 'اشتراك تعليمي في الفيزياء لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(105, 'NAJEH-05-2026', 'Chemistry 2026 Package', 'اشتراك تعليمي في الكيمياء لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(106, 'NAJEH-06-2026', 'Biology 2026 Package', 'اشتراك تعليمي في الأحياء لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(107, 'NAJEH-07-2026', 'History 2026 Package', 'اشتراك تعليمي في التاريخ لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(108, 'NAJEH-08-2026', 'Geography 2026 Package', 'اشتراك تعليمي في الجغرافيا لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(109, 'NAJEH-09-2026', 'Philosophy & Logic 2026 Package', 'اشتراك تعليمي في الفلسفة والمنطق لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(110, 'NAJEH-10-2026', 'Computer Science 2026 Package', 'اشتراك تعليمي في علوم الحاسب لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(111, 'NAJEH-11-2026', 'French Language 2026 Package', 'اشتراك تعليمي في اللغة الفرنسية لمدة 180 يومًا.', 900.00, 100, 5, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-10 13:03:18'),
(112, '123456789103', 'Integrated Science 2026 Package', 'اشتراك تعليمي في العلوم المتكاملة لمدة 180 يومًا.', 900.00, 100, 6, '2026-08-01 00:00:00', '2027-06-30 23:59:59', 180, 1, 1, '2026-08-01 04:00:00', '2026-08-12 15:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_code_courses`
--

CREATE TABLE `subscription_code_courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_code_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_code_courses`
--

INSERT INTO `subscription_code_courses` (`id`, `subscription_code_id`, `course_id`) VALUES
(1001, 101, 201),
(1002, 101, 202),
(1003, 102, 203),
(1004, 102, 204),
(1005, 103, 205),
(1006, 103, 206),
(1007, 104, 207),
(1008, 104, 208),
(1009, 105, 209),
(1010, 105, 210),
(1011, 106, 211),
(1012, 106, 212),
(1013, 107, 213),
(1014, 107, 214),
(1015, 108, 215),
(1016, 108, 216),
(1017, 109, 217),
(1018, 109, 218),
(1019, 110, 219),
(1020, 110, 220),
(1021, 111, 221),
(1022, 111, 222),
(1023, 112, 223),
(1024, 112, 224);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_code_subjects`
--

CREATE TABLE `subscription_code_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_code_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_code_subjects`
--

INSERT INTO `subscription_code_subjects` (`id`, `subscription_code_id`, `subject_id`) VALUES
(1001, 101, 1),
(1002, 102, 2),
(1003, 103, 3),
(1004, 104, 4),
(1005, 105, 5),
(1006, 106, 6),
(1007, 107, 7),
(1008, 108, 8),
(1009, 109, 9),
(1010, 110, 10),
(1011, 111, 11),
(1012, 112, 15);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_code_teachers`
--

CREATE TABLE `subscription_code_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_code_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_code_teachers`
--

INSERT INTO `subscription_code_teachers` (`id`, `subscription_code_id`, `teacher_id`) VALUES
(1001, 101, 201),
(1002, 102, 202),
(1003, 103, 203),
(1004, 104, 204),
(1005, 105, 205),
(1006, 106, 206),
(1007, 107, 207),
(1008, 108, 208),
(1009, 109, 209),
(1010, 110, 210),
(1011, 111, 211),
(1012, 112, 212);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `video` longtext DEFAULT NULL,
  `image` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_profile` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `subject_id`, `city_id`, `name`, `email`, `password`, `whatsapp`, `experience`, `specialization`, `biography`, `qualifications`, `rating`, `video`, `image`, `description`, `is_email_verified`, `is_active`, `can_view_profile`, `created_at`, `updated_at`) VALUES
(201, 1, 1, 'د. أحمد منصور', 'teacher201@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000201', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في النحو والبلاغة وتحليل النصوص.', 'Arabic Language', 'مدرس متخصص في النحو والبلاغة وتحليل النصوص مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.76, 'https://www.youtube.com/watch?v=VZZX2IXeet8', 'https://i.pravatar.cc/400?img=22', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(202, 2, 2, 'أ. سارة حمدي', 'teacher202@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000202', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في Grammar, reading and academic writing.', 'English Language', 'مدرس متخصص في Grammar, reading and academic writing مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.67, 'https://www.youtube.com/watch?v=O-6q-siuMik', 'https://i.pravatar.cc/400?img=23', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(203, 3, 1, 'م. يوسف عادل', 'teacher203@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000203', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في الجبر والتفاضل وحل المشكلات.', 'Mathematics', 'مدرس متخصص في الجبر والتفاضل وحل المشكلات مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.65, 'https://www.youtube.com/watch?v=u8im9mTcSlk', 'https://i.pravatar.cc/400?img=24', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(204, 4, 5, 'د. خالد فؤاد', 'teacher204@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000204', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في الميكانيكا والكهرباء والفيزياء الحديثة.', 'Physics', 'مدرس متخصص في الميكانيكا والكهرباء والفيزياء الحديثة مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.67, 'https://www.youtube.com/watch?v=wHC245cVdHw', 'https://i.pravatar.cc/400?img=25', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(205, 5, 4, 'د. منى شريف', 'teacher205@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000205', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في الكيمياء العضوية والعامة.', 'Chemistry', 'مدرس متخصص في الكيمياء العضوية والعامة مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.85, 'https://www.youtube.com/watch?v=KaxxANDh55k', 'https://i.pravatar.cc/400?img=26', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(206, 6, 3, 'د. ريم وائل', 'teacher206@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000206', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في الوراثة والخلية ووظائف الأعضاء.', 'Biology', 'مدرس متخصص في الوراثة والخلية ووظائف الأعضاء مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.97, 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 'https://i.pravatar.cc/400?img=27', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(207, 7, 1, 'أ. حسام عبد الله', 'teacher207@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000207', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في التاريخ الحديث وتحليل المصادر.', 'History', 'مدرس متخصص في التاريخ الحديث وتحليل المصادر مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.65, 'https://www.youtube.com/watch?v=5Zo0j7yj23E', 'https://i.pravatar.cc/400?img=28', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(208, 8, 2, 'أ. نادين سمير', 'teacher208@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000208', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في الجغرافيا الطبيعية والبشرية والخرائط.', 'Geography', 'مدرس متخصص في الجغرافيا الطبيعية والبشرية والخرائط مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.69, 'https://www.youtube.com/watch?v=Gg6ju-lHCwU', 'https://i.pravatar.cc/400?img=29', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(209, 9, 1, 'د. عمر نبيل', 'teacher209@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000209', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في المنطق والتفكير النقدي.', 'Philosophy & Logic', 'مدرس متخصص في المنطق والتفكير النقدي مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.92, 'https://www.youtube.com/watch?v=O-6q-siuMik', 'https://i.pravatar.cc/400?img=30', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(210, 10, 1, 'م. مروان طارق', 'teacher210@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000210', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في Python والخوارزميات وقواعد البيانات.', 'Computer Science', 'مدرس متخصص في Python والخوارزميات وقواعد البيانات مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.91, 'https://www.youtube.com/watch?v=rJCRGiEidZ4', 'https://i.pravatar.cc/400?img=31', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(211, 11, 3, 'أ. ماريان عادل', 'teacher211@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000211', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في Grammaire, vocabulaire et conversation.', 'French Language', 'مدرس متخصص في Grammaire, vocabulaire et conversation مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.75, 'https://www.youtube.com/watch?v=O-6q-siuMik', 'https://i.pravatar.cc/400?img=32', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(212, 15, 9, 'أ. إيمان سامح', 'teacher212@example.com', '$2y$10$7qKfbH6uSIvZH1VWxyZP7.kCLP4hXLPE5eQAkQquX5cG21Hz/kBCu', '01088000212', 'خبرة عملية وتدريسية تتراوح بين 8 و15 عامًا في العلوم المتكاملة والتجارب المبسطة.', 'Integrated Science', 'مدرس متخصص في العلوم المتكاملة والتجارب المبسطة مع تركيز على الفهم والتطبيق وحل الأسئلة.', 'بكالوريوس متخصص ودورات تدريبية في التعليم الرقمي', 4.82, 'https://www.youtube.com/watch?v=_wmwmMeF3pE', 'https://i.pravatar.cc/400?img=33', 'شرح منظم، واجبات أسبوعية، اختبارات قصيرة ومتابعة تقدم الطالب.', 1, 1, 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_ratings`
--

CREATE TABLE `teacher_ratings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_ratings`
--

INSERT INTO `teacher_ratings` (`id`, `student_id`, `teacher_id`, `rating`, `comment`, `is_approved`, `created_at`, `updated_at`) VALUES
(1001, 1001, 201, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1002, 1002, 201, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1003, 1003, 202, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1004, 1004, 202, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1005, 1005, 203, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1006, 1006, 203, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1007, 1007, 204, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1008, 1008, 204, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1009, 1009, 205, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1010, 1010, 205, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1011, 1011, 206, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1012, 1012, 206, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1013, 1013, 207, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1014, 1014, 207, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1015, 1015, 208, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1016, 1016, 208, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1017, 1017, 209, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1018, 1018, 209, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1019, 1019, 210, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1020, 1020, 210, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1021, 1021, 211, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1022, 1022, 211, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1023, 1023, 212, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1024, 1024, 212, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1025, 1025, 201, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1026, 1026, 201, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1027, 1027, 202, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1028, 1028, 202, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1029, 1029, 203, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1030, 1030, 203, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1031, 1031, 204, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1032, 1032, 204, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1033, 1033, 205, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1034, 1034, 205, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1035, 1035, 206, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1036, 1036, 206, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1037, 1037, 207, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1038, 1038, 207, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1039, 1039, 208, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1040, 1040, 208, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1041, 1041, 209, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1042, 1042, 209, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1043, 1043, 210, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1044, 1044, 210, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1045, 1045, 211, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1046, 1046, 211, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1047, 1047, 212, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1048, 1048, 212, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1049, 1049, 201, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1050, 1050, 201, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1051, 1051, 202, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1052, 1052, 202, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1053, 1053, 203, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1054, 1054, 203, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1055, 1055, 204, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1056, 1056, 204, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1057, 1057, 205, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1058, 1058, 205, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1059, 1059, 206, 4, 'المدرس يشرح بهدوء ويربط المفاهيم بالأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00'),
(1060, 1060, 206, 5, 'متابعة جيدة ورد واضح على الأسئلة.', 1, '2026-08-09 18:30:00', '2026-08-10 12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`, `is_active`, `created_at`) VALUES
(500, 201, 1, 1, '2026-08-10 16:00:00'),
(501, 202, 2, 1, '2026-08-10 16:00:00'),
(502, 203, 3, 1, '2026-08-10 16:00:00'),
(503, 204, 4, 1, '2026-08-10 16:00:00'),
(504, 205, 5, 1, '2026-08-10 16:00:00'),
(505, 206, 6, 1, '2026-08-10 16:00:00'),
(506, 207, 7, 1, '2026-08-10 16:00:00'),
(507, 208, 8, 1, '2026-08-10 16:00:00'),
(508, 209, 9, 1, '2026-08-10 16:00:00'),
(509, 210, 10, 1, '2026-08-10 16:00:00'),
(510, 211, 11, 1, '2026-08-10 16:00:00'),
(511, 212, 15, 1, '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_code` varchar(80) NOT NULL,
  `transaction_type` enum('purchase','subscription','refund','payout','commission','adjustment') NOT NULL DEFAULT 'purchase',
  `direction` enum('incoming','outgoing') NOT NULL DEFAULT 'incoming',
  `payer_type` enum('student','parent','teacher','system','guest') NOT NULL DEFAULT 'student',
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `related_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fees_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'EGP',
  `payment_method` enum('cash','card','wallet','bank_transfer','fawry','instapay','free','other') NOT NULL DEFAULT 'other',
  `payment_provider` varchar(100) DEFAULT NULL,
  `provider_reference` varchar(190) DEFAULT NULL,
  `status` enum('pending','processing','paid','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `billing_name` varchar(200) DEFAULT NULL,
  `billing_email` varchar(190) DEFAULT NULL,
  `billing_phone` varchar(30) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_code`, `transaction_type`, `direction`, `payer_type`, `student_id`, `parent_id`, `teacher_id`, `related_transaction_id`, `subtotal`, `discount_amount`, `tax_amount`, `fees_amount`, `total_amount`, `currency`, `payment_method`, `payment_provider`, `provider_reference`, `status`, `paid_at`, `failed_at`, `cancelled_at`, `refunded_at`, `billing_name`, `billing_email`, `billing_phone`, `metadata`, `is_active`, `created_at`, `updated_at`) VALUES
(9001, 'SEED-2026-9001', 'purchase', 'incoming', 'student', 1001, NULL, NULL, NULL, 950.00, 50.00, 0.00, 10.00, 910.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9001', 'paid', '2026-06-15 12:00:00', NULL, NULL, NULL, 'يوسف أحمد محمد', 'student1001@example.com', '01077001001', '{\"source\": \"seed\", \"course_id\": 201, \"subscription_id\": 1001}', 1, '2026-06-15 16:00:00', '2026-08-10 16:00:00'),
(9002, 'SEED-2026-9002', 'purchase', 'incoming', 'student', 1002, NULL, NULL, NULL, 700.00, 0.00, 0.00, 0.00, 700.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9002', 'paid', '2026-06-16 13:00:00', NULL, NULL, NULL, 'مريم إبراهيم عبد الله', 'student1002@example.com', '01077001002', '{\"source\": \"seed\", \"course_id\": 202, \"subscription_id\": 1002}', 1, '2026-06-16 17:00:00', '2026-08-10 16:00:00'),
(9003, 'SEED-2026-9003', 'purchase', 'incoming', 'student', 1003, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9003', 'paid', '2026-06-17 14:00:00', NULL, NULL, NULL, 'عمر طارق السيد', 'student1003@example.com', '01077001003', '{\"source\": \"seed\", \"course_id\": 203, \"subscription_id\": 1003}', 1, '2026-06-17 18:00:00', '2026-08-10 16:00:00'),
(9004, 'SEED-2026-9004', 'purchase', 'incoming', 'student', 1004, NULL, NULL, NULL, 950.00, 0.00, 0.00, 0.00, 950.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9004', 'paid', '2026-06-18 15:00:00', NULL, NULL, NULL, 'سلمى أحمد سعيد', 'student1004@example.com', '01077001004', '{\"source\": \"seed\", \"course_id\": 204, \"subscription_id\": 1004}', 1, '2026-06-18 19:00:00', '2026-08-10 16:00:00'),
(9005, 'SEED-2026-9005', 'purchase', 'incoming', 'student', 1005, NULL, NULL, NULL, 650.00, 0.00, 0.00, 10.00, 660.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9005', 'paid', '2026-06-19 16:00:00', NULL, NULL, NULL, 'آدم حسام محمد', 'student1005@example.com', '01077001005', '{\"source\": \"seed\", \"course_id\": 205, \"subscription_id\": 1005}', 1, '2026-06-19 20:00:00', '2026-08-10 16:00:00'),
(9006, 'SEED-2026-9006', 'purchase', 'incoming', 'student', 1006, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9006', 'paid', '2026-06-20 17:00:00', NULL, NULL, NULL, 'ليلى عماد حسن', 'student1006@example.com', '01077001006', '{\"source\": \"seed\", \"course_id\": 206, \"subscription_id\": 1006}', 1, '2026-06-20 21:00:00', '2026-08-10 16:00:00'),
(9007, 'SEED-2026-9007', 'purchase', 'incoming', 'student', 1007, NULL, NULL, NULL, 900.00, 0.00, 0.00, 0.00, 900.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9007', 'paid', '2026-06-21 18:00:00', NULL, NULL, NULL, 'محمد جمال فتحي', 'student1007@example.com', '01077001007', '{\"source\": \"seed\", \"course_id\": 207, \"subscription_id\": 1007}', 1, '2026-06-21 22:00:00', '2026-08-10 16:00:00'),
(9008, 'SEED-2026-9008', 'purchase', 'incoming', 'student', 1008, NULL, NULL, NULL, 650.00, 50.00, 0.00, 0.00, 600.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9008', 'paid', '2026-06-22 19:00:00', NULL, NULL, NULL, 'نور خالد علي', 'student1008@example.com', '01077001008', '{\"source\": \"seed\", \"course_id\": 208, \"subscription_id\": 1008}', 1, '2026-06-22 23:00:00', '2026-08-10 16:00:00'),
(9009, 'SEED-2026-9009', 'purchase', 'incoming', 'student', 1009, NULL, NULL, NULL, 750.00, 0.00, 0.00, 10.00, 760.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9009', 'paid', '2026-06-23 12:00:00', NULL, NULL, NULL, 'عبد الرحمن سامح فؤاد', 'student1009@example.com', '01077001009', '{\"source\": \"seed\", \"course_id\": 209, \"subscription_id\": 1009}', 1, '2026-06-23 16:00:00', '2026-08-10 16:00:00'),
(9010, 'SEED-2026-9010', 'purchase', 'incoming', 'student', 1010, NULL, NULL, NULL, 900.00, 0.00, 0.00, 0.00, 900.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9010', 'paid', '2026-06-24 13:00:00', NULL, NULL, NULL, 'جنى رضا مصطفى', 'student1010@example.com', '01077001010', '{\"source\": \"seed\", \"course_id\": 210, \"subscription_id\": 1010}', 1, '2026-06-24 17:00:00', '2026-08-10 16:00:00'),
(9011, 'SEED-2026-9011', 'purchase', 'incoming', 'student', 1011, NULL, NULL, NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9011', 'paid', '2026-06-25 14:00:00', NULL, NULL, NULL, 'أحمد رامي محمود', 'student1011@example.com', '01077001011', '{\"source\": \"seed\", \"course_id\": 211, \"subscription_id\": 1011}', 1, '2026-06-25 18:00:00', '2026-08-10 16:00:00'),
(9012, 'SEED-2026-9012', 'purchase', 'incoming', 'student', 1012, NULL, NULL, NULL, 750.00, 0.00, 0.00, 0.00, 750.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9012', 'paid', '2026-06-26 15:00:00', NULL, NULL, NULL, 'حبيبة شريف كمال', 'student1012@example.com', '01077001012', '{\"source\": \"seed\", \"course_id\": 212, \"subscription_id\": 1012}', 1, '2026-06-26 19:00:00', '2026-08-10 16:00:00'),
(9013, 'SEED-2026-9013', 'purchase', 'incoming', 'student', 1013, NULL, NULL, NULL, 850.00, 0.00, 0.00, 10.00, 860.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9013', 'paid', '2026-06-27 16:00:00', NULL, NULL, NULL, 'علي أشرف عبد الحميد', 'student1013@example.com', '01077001013', '{\"source\": \"seed\", \"course_id\": 213, \"subscription_id\": 1013}', 1, '2026-06-27 20:00:00', '2026-08-10 16:00:00'),
(9014, 'SEED-2026-9014', 'purchase', 'incoming', 'student', 1014, NULL, NULL, NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9014', 'paid', '2026-06-28 17:00:00', NULL, NULL, NULL, 'ملك سمير ناصر', 'student1014@example.com', '01077001014', '{\"source\": \"seed\", \"course_id\": 214, \"subscription_id\": 1014}', 1, '2026-06-28 21:00:00', '2026-08-10 16:00:00'),
(9015, 'SEED-2026-9015', 'purchase', 'incoming', 'student', 1015, NULL, NULL, NULL, 700.00, 50.00, 0.00, 0.00, 650.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9015', 'paid', '2026-06-29 18:00:00', NULL, NULL, NULL, 'كريم محمود حسن', 'student1015@example.com', '01077001015', '{\"source\": \"seed\", \"course_id\": 215, \"subscription_id\": 1015}', 1, '2026-06-29 22:00:00', '2026-08-10 16:00:00'),
(9016, 'SEED-2026-9016', 'purchase', 'incoming', 'student', 1016, NULL, NULL, NULL, 850.00, 0.00, 0.00, 0.00, 850.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9016', 'paid', '2026-06-30 19:00:00', NULL, NULL, NULL, 'فاطمة وائل شريف', 'student1016@example.com', '01077001016', '{\"source\": \"seed\", \"course_id\": 216, \"subscription_id\": 1016}', 1, '2026-06-30 23:00:00', '2026-08-10 16:00:00'),
(9017, 'SEED-2026-9017', 'purchase', 'incoming', 'student', 1017, NULL, NULL, NULL, 950.00, 0.00, 0.00, 10.00, 960.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9017', 'paid', '2026-07-01 12:00:00', NULL, NULL, NULL, 'زياد حسام الدين', 'student1017@example.com', '01077001017', '{\"source\": \"seed\", \"course_id\": 217, \"subscription_id\": 1017}', 1, '2026-07-01 16:00:00', '2026-08-10 16:00:00'),
(9018, 'SEED-2026-9018', 'purchase', 'incoming', 'student', 1018, NULL, NULL, NULL, 700.00, 0.00, 0.00, 0.00, 700.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9018', 'paid', '2026-07-02 13:00:00', NULL, NULL, NULL, 'بسملة عمرو السيد', 'student1018@example.com', '01077001018', '{\"source\": \"seed\", \"course_id\": 218, \"subscription_id\": 1018}', 1, '2026-07-02 17:00:00', '2026-08-10 16:00:00'),
(9019, 'SEED-2026-9019', 'purchase', 'incoming', 'student', 1019, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9019', 'paid', '2026-07-03 14:00:00', NULL, NULL, NULL, 'محمود محمد عادل', 'student1019@example.com', '01077001019', '{\"source\": \"seed\", \"course_id\": 219, \"subscription_id\": 1019}', 1, '2026-07-03 18:00:00', '2026-08-10 16:00:00'),
(9020, 'SEED-2026-9020', 'purchase', 'incoming', 'student', 1020, NULL, NULL, NULL, 950.00, 0.00, 0.00, 0.00, 950.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9020', 'paid', '2026-07-04 15:00:00', NULL, NULL, NULL, 'ندى أحمد صبري', 'student1020@example.com', '01077001020', '{\"source\": \"seed\", \"course_id\": 220, \"subscription_id\": 1020}', 1, '2026-07-04 19:00:00', '2026-08-10 16:00:00'),
(9021, 'SEED-2026-9021', 'purchase', 'incoming', 'student', 1021, NULL, NULL, NULL, 650.00, 0.00, 0.00, 10.00, 660.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9021', 'paid', '2026-07-05 16:00:00', NULL, NULL, NULL, 'مصطفى أحمد محمد', 'student1021@example.com', '01077001021', '{\"source\": \"seed\", \"course_id\": 221, \"subscription_id\": 1021}', 1, '2026-07-05 20:00:00', '2026-08-10 16:00:00'),
(9022, 'SEED-2026-9022', 'purchase', 'incoming', 'student', 1022, NULL, NULL, NULL, 800.00, 50.00, 0.00, 0.00, 750.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9022', 'paid', '2026-07-06 17:00:00', NULL, NULL, NULL, 'روان إبراهيم عبد الله', 'student1022@example.com', '01077001022', '{\"source\": \"seed\", \"course_id\": 222, \"subscription_id\": 1022}', 1, '2026-07-06 21:00:00', '2026-08-10 16:00:00'),
(9023, 'SEED-2026-9023', 'purchase', 'incoming', 'student', 1023, NULL, NULL, NULL, 850.00, 0.00, 0.00, 0.00, 850.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9023', 'paid', '2026-07-07 18:00:00', NULL, NULL, NULL, 'ياسين طارق السيد', 'student1023@example.com', '01077001023', '{\"source\": \"seed\", \"course_id\": 223, \"subscription_id\": 1023}', 1, '2026-07-07 22:00:00', '2026-08-10 16:00:00'),
(9024, 'SEED-2026-9024', 'purchase', 'incoming', 'student', 1024, NULL, NULL, NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9024', 'paid', '2026-07-08 19:00:00', NULL, NULL, NULL, 'فرح أحمد سعيد', 'student1024@example.com', '01077001024', '{\"source\": \"seed\", \"course_id\": 224, \"subscription_id\": 1024}', 1, '2026-07-08 23:00:00', '2026-08-10 16:00:00'),
(9025, 'SEED-2026-9025', 'purchase', 'incoming', 'student', 1025, NULL, NULL, NULL, 950.00, 0.00, 0.00, 10.00, 960.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9025', 'paid', '2026-07-09 12:00:00', NULL, NULL, NULL, 'إياد حسام محمد', 'student1025@example.com', '01077001025', '{\"source\": \"seed\", \"course_id\": 201, \"subscription_id\": 1025}', 1, '2026-07-09 16:00:00', '2026-08-10 16:00:00'),
(9026, 'SEED-2026-9026', 'purchase', 'incoming', 'student', 1026, NULL, NULL, NULL, 700.00, 0.00, 0.00, 0.00, 700.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9026', 'paid', '2026-07-10 13:00:00', NULL, NULL, NULL, 'شهد عماد حسن', 'student1026@example.com', '01077001026', '{\"source\": \"seed\", \"course_id\": 202, \"subscription_id\": 1026}', 1, '2026-07-10 17:00:00', '2026-08-10 16:00:00'),
(9027, 'SEED-2026-9027', 'purchase', 'incoming', 'student', 1027, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9027', 'paid', '2026-07-11 14:00:00', NULL, NULL, NULL, 'حمزة جمال فتحي', 'student1027@example.com', '01077001027', '{\"source\": \"seed\", \"course_id\": 203, \"subscription_id\": 1027}', 1, '2026-07-11 18:00:00', '2026-08-10 16:00:00'),
(9028, 'SEED-2026-9028', 'purchase', 'incoming', 'student', 1028, NULL, NULL, NULL, 950.00, 0.00, 0.00, 0.00, 950.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9028', 'paid', '2026-07-12 15:00:00', NULL, NULL, NULL, 'لارا خالد علي', 'student1028@example.com', '01077001028', '{\"source\": \"seed\", \"course_id\": 204, \"subscription_id\": 1028}', 1, '2026-07-12 19:00:00', '2026-08-10 16:00:00'),
(9029, 'SEED-2026-9029', 'purchase', 'incoming', 'student', 1029, NULL, NULL, NULL, 650.00, 50.00, 0.00, 10.00, 610.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9029', 'paid', '2026-07-13 16:00:00', NULL, NULL, NULL, 'سيف سامح فؤاد', 'student1029@example.com', '01077001029', '{\"source\": \"seed\", \"course_id\": 205, \"subscription_id\": 1029}', 1, '2026-07-13 20:00:00', '2026-08-10 16:00:00'),
(9030, 'SEED-2026-9030', 'purchase', 'incoming', 'student', 1030, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9030', 'paid', '2026-07-14 17:00:00', NULL, NULL, NULL, 'منة رضا مصطفى', 'student1030@example.com', '01077001030', '{\"source\": \"seed\", \"course_id\": 206, \"subscription_id\": 1030}', 1, '2026-07-14 21:00:00', '2026-08-10 16:00:00'),
(9031, 'SEED-2026-9031', 'purchase', 'incoming', 'student', 1031, NULL, NULL, NULL, 900.00, 0.00, 0.00, 0.00, 900.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9031', 'paid', '2026-07-15 18:00:00', NULL, NULL, NULL, 'يوسف رامي محمود', 'student1031@example.com', '01077001031', '{\"source\": \"seed\", \"course_id\": 207, \"subscription_id\": 1031}', 1, '2026-07-15 22:00:00', '2026-08-10 16:00:00'),
(9032, 'SEED-2026-9032', 'purchase', 'incoming', 'student', 1032, NULL, NULL, NULL, 650.00, 0.00, 0.00, 0.00, 650.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9032', 'paid', '2026-07-16 19:00:00', NULL, NULL, NULL, 'مريم شريف كمال', 'student1032@example.com', '01077001032', '{\"source\": \"seed\", \"course_id\": 208, \"subscription_id\": 1032}', 1, '2026-07-16 23:00:00', '2026-08-10 16:00:00'),
(9033, 'SEED-2026-9033', 'purchase', 'incoming', 'student', 1033, NULL, NULL, NULL, 750.00, 0.00, 0.00, 10.00, 760.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9033', 'paid', '2026-07-17 12:00:00', NULL, NULL, NULL, 'عمر أشرف عبد الحميد', 'student1033@example.com', '01077001033', '{\"source\": \"seed\", \"course_id\": 209, \"subscription_id\": 1033}', 1, '2026-07-17 16:00:00', '2026-08-10 16:00:00'),
(9034, 'SEED-2026-9034', 'purchase', 'incoming', 'student', 1034, NULL, NULL, NULL, 900.00, 0.00, 0.00, 0.00, 900.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9034', 'paid', '2026-07-18 13:00:00', NULL, NULL, NULL, 'سلمى سمير ناصر', 'student1034@example.com', '01077001034', '{\"source\": \"seed\", \"course_id\": 210, \"subscription_id\": 1034}', 1, '2026-07-18 17:00:00', '2026-08-10 16:00:00'),
(9035, 'SEED-2026-9035', 'purchase', 'incoming', 'student', 1035, NULL, NULL, NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9035', 'paid', '2026-07-19 14:00:00', NULL, NULL, NULL, 'آدم محمود حسن', 'student1035@example.com', '01077001035', '{\"source\": \"seed\", \"course_id\": 211, \"subscription_id\": 1035}', 1, '2026-07-19 18:00:00', '2026-08-10 16:00:00'),
(9036, 'SEED-2026-9036', 'purchase', 'incoming', 'student', 1036, NULL, NULL, NULL, 750.00, 50.00, 0.00, 0.00, 700.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9036', 'paid', '2026-07-20 15:00:00', NULL, NULL, NULL, 'ليلى وائل شريف', 'student1036@example.com', '01077001036', '{\"source\": \"seed\", \"course_id\": 212, \"subscription_id\": 1036}', 1, '2026-07-20 19:00:00', '2026-08-10 16:00:00'),
(9037, 'SEED-2026-9037', 'purchase', 'incoming', 'student', 1037, NULL, NULL, NULL, 850.00, 0.00, 0.00, 10.00, 860.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9037', 'paid', '2026-07-21 16:00:00', NULL, NULL, NULL, 'محمد حسام الدين', 'student1037@example.com', '01077001037', '{\"source\": \"seed\", \"course_id\": 213, \"subscription_id\": 1037}', 1, '2026-07-21 20:00:00', '2026-08-10 16:00:00'),
(9038, 'SEED-2026-9038', 'purchase', 'incoming', 'student', 1038, NULL, NULL, NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9038', 'paid', '2026-07-22 17:00:00', NULL, NULL, NULL, 'نور عمرو السيد', 'student1038@example.com', '01077001038', '{\"source\": \"seed\", \"course_id\": 214, \"subscription_id\": 1038}', 1, '2026-07-22 21:00:00', '2026-08-10 16:00:00'),
(9039, 'SEED-2026-9039', 'purchase', 'incoming', 'student', 1039, NULL, NULL, NULL, 700.00, 0.00, 0.00, 0.00, 700.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9039', 'paid', '2026-07-23 18:00:00', NULL, NULL, NULL, 'عبد الرحمن محمد عادل', 'student1039@example.com', '01077001039', '{\"source\": \"seed\", \"course_id\": 215, \"subscription_id\": 1039}', 1, '2026-07-23 22:00:00', '2026-08-10 16:00:00'),
(9040, 'SEED-2026-9040', 'purchase', 'incoming', 'student', 1040, NULL, NULL, NULL, 850.00, 0.00, 0.00, 0.00, 850.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9040', 'paid', '2026-07-24 19:00:00', NULL, NULL, NULL, 'جنى أحمد صبري', 'student1040@example.com', '01077001040', '{\"source\": \"seed\", \"course_id\": 216, \"subscription_id\": 1040}', 1, '2026-07-24 23:00:00', '2026-08-10 16:00:00'),
(9041, 'SEED-2026-9041', 'purchase', 'incoming', 'student', 1041, NULL, NULL, NULL, 950.00, 0.00, 0.00, 10.00, 960.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9041', 'paid', '2026-07-25 12:00:00', NULL, NULL, NULL, 'أحمد أحمد محمد', 'student1041@example.com', '01077001041', '{\"source\": \"seed\", \"course_id\": 217, \"subscription_id\": 1041}', 1, '2026-07-25 16:00:00', '2026-08-10 16:00:00'),
(9042, 'SEED-2026-9042', 'purchase', 'incoming', 'student', 1042, NULL, NULL, NULL, 700.00, 0.00, 0.00, 0.00, 700.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9042', 'paid', '2026-07-26 13:00:00', NULL, NULL, NULL, 'حبيبة إبراهيم عبد الله', 'student1042@example.com', '01077001042', '{\"source\": \"seed\", \"course_id\": 218, \"subscription_id\": 1042}', 1, '2026-07-26 17:00:00', '2026-08-10 16:00:00'),
(9043, 'SEED-2026-9043', 'purchase', 'incoming', 'student', 1043, NULL, NULL, NULL, 800.00, 50.00, 0.00, 0.00, 750.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9043', 'paid', '2026-07-27 14:00:00', NULL, NULL, NULL, 'علي طارق السيد', 'student1043@example.com', '01077001043', '{\"source\": \"seed\", \"course_id\": 219, \"subscription_id\": 1043}', 1, '2026-07-27 18:00:00', '2026-08-10 16:00:00'),
(9044, 'SEED-2026-9044', 'purchase', 'incoming', 'student', 1044, NULL, NULL, NULL, 950.00, 0.00, 0.00, 0.00, 950.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9044', 'paid', '2026-07-28 15:00:00', NULL, NULL, NULL, 'ملك أحمد سعيد', 'student1044@example.com', '01077001044', '{\"source\": \"seed\", \"course_id\": 220, \"subscription_id\": 1044}', 1, '2026-07-28 19:00:00', '2026-08-10 16:00:00'),
(9045, 'SEED-2026-9045', 'purchase', 'incoming', 'student', 1045, NULL, NULL, NULL, 650.00, 0.00, 0.00, 10.00, 660.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9045', 'paid', '2026-07-29 16:00:00', NULL, NULL, NULL, 'كريم حسام محمد', 'student1045@example.com', '01077001045', '{\"source\": \"seed\", \"course_id\": 221, \"subscription_id\": 1045}', 1, '2026-07-29 20:00:00', '2026-08-10 16:00:00'),
(9046, 'SEED-2026-9046', 'purchase', 'incoming', 'student', 1046, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9046', 'paid', '2026-07-30 17:00:00', NULL, NULL, NULL, 'فاطمة عماد حسن', 'student1046@example.com', '01077001046', '{\"source\": \"seed\", \"course_id\": 222, \"subscription_id\": 1046}', 1, '2026-07-30 21:00:00', '2026-08-10 16:00:00'),
(9047, 'SEED-2026-9047', 'purchase', 'incoming', 'student', 1047, NULL, NULL, NULL, 850.00, 0.00, 0.00, 0.00, 850.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9047', 'paid', '2026-07-31 18:00:00', NULL, NULL, NULL, 'زياد جمال فتحي', 'student1047@example.com', '01077001047', '{\"source\": \"seed\", \"course_id\": 223, \"subscription_id\": 1047}', 1, '2026-07-31 22:00:00', '2026-08-10 16:00:00'),
(9048, 'SEED-2026-9048', 'purchase', 'incoming', 'student', 1048, NULL, NULL, NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9048', 'paid', '2026-08-01 19:00:00', NULL, NULL, NULL, 'بسملة خالد علي', 'student1048@example.com', '01077001048', '{\"source\": \"seed\", \"course_id\": 224, \"subscription_id\": 1048}', 1, '2026-08-01 23:00:00', '2026-08-10 16:00:00'),
(9049, 'SEED-2026-9049', 'purchase', 'incoming', 'student', 1049, NULL, NULL, NULL, 950.00, 0.00, 0.00, 10.00, 960.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9049', 'paid', '2026-08-02 12:00:00', NULL, NULL, NULL, 'محمود سامح فؤاد', 'student1049@example.com', '01077001049', '{\"source\": \"seed\", \"course_id\": 201, \"subscription_id\": 1049}', 1, '2026-08-02 16:00:00', '2026-08-10 16:00:00'),
(9050, 'SEED-2026-9050', 'purchase', 'incoming', 'student', 1050, NULL, NULL, NULL, 700.00, 50.00, 0.00, 0.00, 650.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9050', 'paid', '2026-08-03 13:00:00', NULL, NULL, NULL, 'ندى رضا مصطفى', 'student1050@example.com', '01077001050', '{\"source\": \"seed\", \"course_id\": 202, \"subscription_id\": 1050}', 1, '2026-08-03 17:00:00', '2026-08-10 16:00:00'),
(9051, 'SEED-2026-9051', 'purchase', 'incoming', 'student', 1051, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9051', 'paid', '2026-06-15 14:00:00', NULL, NULL, NULL, 'مصطفى رامي محمود', 'student1051@example.com', '01077001051', '{\"source\": \"seed\", \"course_id\": 203, \"subscription_id\": 1051}', 1, '2026-06-15 18:00:00', '2026-08-10 16:00:00'),
(9052, 'SEED-2026-9052', 'purchase', 'incoming', 'student', 1052, NULL, NULL, NULL, 950.00, 0.00, 0.00, 0.00, 950.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9052', 'paid', '2026-06-16 15:00:00', NULL, NULL, NULL, 'روان شريف كمال', 'student1052@example.com', '01077001052', '{\"source\": \"seed\", \"course_id\": 204, \"subscription_id\": 1052}', 1, '2026-06-16 19:00:00', '2026-08-10 16:00:00'),
(9053, 'SEED-2026-9053', 'purchase', 'incoming', 'student', 1053, NULL, NULL, NULL, 650.00, 0.00, 0.00, 10.00, 660.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9053', 'paid', '2026-06-17 16:00:00', NULL, NULL, NULL, 'ياسين أشرف عبد الحميد', 'student1053@example.com', '01077001053', '{\"source\": \"seed\", \"course_id\": 205, \"subscription_id\": 1053}', 1, '2026-06-17 20:00:00', '2026-08-10 16:00:00'),
(9054, 'SEED-2026-9054', 'purchase', 'incoming', 'student', 1054, NULL, NULL, NULL, 800.00, 0.00, 0.00, 0.00, 800.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9054', 'paid', '2026-06-18 17:00:00', NULL, NULL, NULL, 'فرح سمير ناصر', 'student1054@example.com', '01077001054', '{\"source\": \"seed\", \"course_id\": 206, \"subscription_id\": 1054}', 1, '2026-06-18 21:00:00', '2026-08-10 16:00:00'),
(9055, 'SEED-2026-9055', 'purchase', 'incoming', 'student', 1055, NULL, NULL, NULL, 900.00, 0.00, 0.00, 0.00, 900.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9055', 'paid', '2026-06-19 18:00:00', NULL, NULL, NULL, 'إياد محمود حسن', 'student1055@example.com', '01077001055', '{\"source\": \"seed\", \"course_id\": 207, \"subscription_id\": 1055}', 1, '2026-06-19 22:00:00', '2026-08-10 16:00:00'),
(9056, 'SEED-2026-9056', 'purchase', 'incoming', 'student', 1056, NULL, NULL, NULL, 650.00, 0.00, 0.00, 0.00, 650.00, 'EGP', 'card', 'Paymob', 'SEED-CARD-9056', 'paid', '2026-06-20 19:00:00', NULL, NULL, NULL, 'شهد وائل شريف', 'student1056@example.com', '01077001056', '{\"source\": \"seed\", \"course_id\": 208, \"subscription_id\": 1056}', 1, '2026-06-20 23:00:00', '2026-08-10 16:00:00'),
(9057, 'SEED-2026-9057', 'purchase', 'incoming', 'student', 1057, NULL, NULL, NULL, 750.00, 50.00, 0.00, 10.00, 710.00, 'EGP', 'wallet', 'Vodafone Cash', 'SEED-WALLET-9057', 'paid', '2026-06-21 12:00:00', NULL, NULL, NULL, 'حمزة حسام الدين', 'student1057@example.com', '01077001057', '{\"source\": \"seed\", \"course_id\": 209, \"subscription_id\": 1057}', 1, '2026-06-21 16:00:00', '2026-08-10 16:00:00'),
(9058, 'SEED-2026-9058', 'purchase', 'incoming', 'student', 1058, NULL, NULL, NULL, 900.00, 0.00, 0.00, 0.00, 900.00, 'EGP', 'instapay', 'InstaPay', 'SEED-INSTAPAY-9058', 'paid', '2026-06-22 13:00:00', NULL, NULL, NULL, 'لارا عمرو السيد', 'student1058@example.com', '01077001058', '{\"source\": \"seed\", \"course_id\": 210, \"subscription_id\": 1058}', 1, '2026-06-22 17:00:00', '2026-08-10 16:00:00'),
(9059, 'SEED-2026-9059', 'purchase', 'incoming', 'student', 1059, NULL, NULL, NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'EGP', 'fawry', 'Fawry', 'SEED-FAWRY-9059', 'paid', '2026-06-23 14:00:00', NULL, NULL, NULL, 'سيف محمد عادل', 'student1059@example.com', '01077001059', '{\"source\": \"seed\", \"course_id\": 211, \"subscription_id\": 1059}', 1, '2026-06-23 18:00:00', '2026-08-10 16:00:00'),
(9060, 'SEED-2026-9060', 'purchase', 'incoming', 'student', 1060, NULL, NULL, NULL, 750.00, 0.00, 0.00, 0.00, 750.00, 'EGP', 'bank_transfer', 'Banque Misr', 'SEED-BANK_TRANSFER-9060', 'paid', '2026-06-24 15:00:00', NULL, NULL, NULL, 'منة أحمد صبري', 'student1060@example.com', '01077001060', '{\"source\": \"seed\", \"course_id\": 212, \"subscription_id\": 1060}', 1, '2026-06-24 19:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `transactions_translations`
--

CREATE TABLE `transactions_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions_translations`
--

INSERT INTO `transactions_translations` (`id`, `transaction_id`, `language_id`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(14001, 9001, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-15 16:00:00', '2026-08-10 16:00:00'),
(14002, 9001, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-15 16:00:00', '2026-08-10 16:00:00'),
(14003, 9002, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-16 17:00:00', '2026-08-10 16:00:00'),
(14004, 9002, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-16 17:00:00', '2026-08-10 16:00:00'),
(14005, 9003, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-17 18:00:00', '2026-08-10 16:00:00'),
(14006, 9003, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-17 18:00:00', '2026-08-10 16:00:00'),
(14007, 9004, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-18 19:00:00', '2026-08-10 16:00:00'),
(14008, 9004, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-18 19:00:00', '2026-08-10 16:00:00'),
(14009, 9005, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-19 20:00:00', '2026-08-10 16:00:00'),
(14010, 9005, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-19 20:00:00', '2026-08-10 16:00:00'),
(14011, 9006, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-20 21:00:00', '2026-08-10 16:00:00'),
(14012, 9006, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-20 21:00:00', '2026-08-10 16:00:00'),
(14013, 9007, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-21 22:00:00', '2026-08-10 16:00:00'),
(14014, 9007, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-21 22:00:00', '2026-08-10 16:00:00'),
(14015, 9008, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-22 23:00:00', '2026-08-10 16:00:00'),
(14016, 9008, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-22 23:00:00', '2026-08-10 16:00:00'),
(14017, 9009, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-23 16:00:00', '2026-08-10 16:00:00'),
(14018, 9009, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-23 16:00:00', '2026-08-10 16:00:00'),
(14019, 9010, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-24 17:00:00', '2026-08-10 16:00:00'),
(14020, 9010, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-24 17:00:00', '2026-08-10 16:00:00'),
(14021, 9011, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-25 18:00:00', '2026-08-10 16:00:00'),
(14022, 9011, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-25 18:00:00', '2026-08-10 16:00:00'),
(14023, 9012, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-26 19:00:00', '2026-08-10 16:00:00'),
(14024, 9012, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-26 19:00:00', '2026-08-10 16:00:00'),
(14025, 9013, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-27 20:00:00', '2026-08-10 16:00:00'),
(14026, 9013, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-27 20:00:00', '2026-08-10 16:00:00'),
(14027, 9014, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-28 21:00:00', '2026-08-10 16:00:00'),
(14028, 9014, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-28 21:00:00', '2026-08-10 16:00:00'),
(14029, 9015, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-29 22:00:00', '2026-08-10 16:00:00'),
(14030, 9015, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-29 22:00:00', '2026-08-10 16:00:00'),
(14031, 9016, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-30 23:00:00', '2026-08-10 16:00:00'),
(14032, 9016, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-30 23:00:00', '2026-08-10 16:00:00'),
(14033, 9017, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-01 16:00:00', '2026-08-10 16:00:00'),
(14034, 9017, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-01 16:00:00', '2026-08-10 16:00:00'),
(14035, 9018, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-02 17:00:00', '2026-08-10 16:00:00'),
(14036, 9018, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-02 17:00:00', '2026-08-10 16:00:00'),
(14037, 9019, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-03 18:00:00', '2026-08-10 16:00:00'),
(14038, 9019, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-03 18:00:00', '2026-08-10 16:00:00'),
(14039, 9020, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-04 19:00:00', '2026-08-10 16:00:00'),
(14040, 9020, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-04 19:00:00', '2026-08-10 16:00:00'),
(14041, 9021, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-05 20:00:00', '2026-08-10 16:00:00'),
(14042, 9021, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-05 20:00:00', '2026-08-10 16:00:00'),
(14043, 9022, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-06 21:00:00', '2026-08-10 16:00:00'),
(14044, 9022, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-06 21:00:00', '2026-08-10 16:00:00'),
(14045, 9023, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-07 22:00:00', '2026-08-10 16:00:00'),
(14046, 9023, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-07 22:00:00', '2026-08-10 16:00:00'),
(14047, 9024, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-08 23:00:00', '2026-08-10 16:00:00'),
(14048, 9024, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-08 23:00:00', '2026-08-10 16:00:00'),
(14049, 9025, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-09 16:00:00', '2026-08-10 16:00:00'),
(14050, 9025, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-09 16:00:00', '2026-08-10 16:00:00'),
(14051, 9026, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-10 17:00:00', '2026-08-10 16:00:00'),
(14052, 9026, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-10 17:00:00', '2026-08-10 16:00:00'),
(14053, 9027, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-11 18:00:00', '2026-08-10 16:00:00'),
(14054, 9027, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-11 18:00:00', '2026-08-10 16:00:00'),
(14055, 9028, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-12 19:00:00', '2026-08-10 16:00:00'),
(14056, 9028, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-12 19:00:00', '2026-08-10 16:00:00'),
(14057, 9029, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-13 20:00:00', '2026-08-10 16:00:00'),
(14058, 9029, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-13 20:00:00', '2026-08-10 16:00:00'),
(14059, 9030, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-14 21:00:00', '2026-08-10 16:00:00'),
(14060, 9030, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-14 21:00:00', '2026-08-10 16:00:00'),
(14061, 9031, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-15 22:00:00', '2026-08-10 16:00:00'),
(14062, 9031, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-15 22:00:00', '2026-08-10 16:00:00'),
(14063, 9032, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-16 23:00:00', '2026-08-10 16:00:00'),
(14064, 9032, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-16 23:00:00', '2026-08-10 16:00:00'),
(14065, 9033, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-17 16:00:00', '2026-08-10 16:00:00'),
(14066, 9033, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-17 16:00:00', '2026-08-10 16:00:00'),
(14067, 9034, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-18 17:00:00', '2026-08-10 16:00:00'),
(14068, 9034, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-18 17:00:00', '2026-08-10 16:00:00'),
(14069, 9035, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-19 18:00:00', '2026-08-10 16:00:00'),
(14070, 9035, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-19 18:00:00', '2026-08-10 16:00:00'),
(14071, 9036, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-20 19:00:00', '2026-08-10 16:00:00'),
(14072, 9036, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-20 19:00:00', '2026-08-10 16:00:00'),
(14073, 9037, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-21 20:00:00', '2026-08-10 16:00:00'),
(14074, 9037, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-21 20:00:00', '2026-08-10 16:00:00'),
(14075, 9038, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-22 21:00:00', '2026-08-10 16:00:00'),
(14076, 9038, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-22 21:00:00', '2026-08-10 16:00:00'),
(14077, 9039, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-23 22:00:00', '2026-08-10 16:00:00'),
(14078, 9039, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-23 22:00:00', '2026-08-10 16:00:00'),
(14079, 9040, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-24 23:00:00', '2026-08-10 16:00:00'),
(14080, 9040, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-24 23:00:00', '2026-08-10 16:00:00'),
(14081, 9041, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-25 16:00:00', '2026-08-10 16:00:00'),
(14082, 9041, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-25 16:00:00', '2026-08-10 16:00:00'),
(14083, 9042, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-26 17:00:00', '2026-08-10 16:00:00'),
(14084, 9042, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-26 17:00:00', '2026-08-10 16:00:00'),
(14085, 9043, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-27 18:00:00', '2026-08-10 16:00:00'),
(14086, 9043, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-27 18:00:00', '2026-08-10 16:00:00'),
(14087, 9044, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-28 19:00:00', '2026-08-10 16:00:00'),
(14088, 9044, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-28 19:00:00', '2026-08-10 16:00:00'),
(14089, 9045, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-29 20:00:00', '2026-08-10 16:00:00'),
(14090, 9045, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-29 20:00:00', '2026-08-10 16:00:00'),
(14091, 9046, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-30 21:00:00', '2026-08-10 16:00:00'),
(14092, 9046, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-30 21:00:00', '2026-08-10 16:00:00'),
(14093, 9047, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-07-31 22:00:00', '2026-08-10 16:00:00'),
(14094, 9047, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-07-31 22:00:00', '2026-08-10 16:00:00'),
(14095, 9048, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-08-01 23:00:00', '2026-08-10 16:00:00'),
(14096, 9048, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-08-01 23:00:00', '2026-08-10 16:00:00'),
(14097, 9049, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-08-02 16:00:00', '2026-08-10 16:00:00'),
(14098, 9049, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-08-02 16:00:00', '2026-08-10 16:00:00'),
(14099, 9050, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-08-03 17:00:00', '2026-08-10 16:00:00'),
(14100, 9050, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-08-03 17:00:00', '2026-08-10 16:00:00'),
(14101, 9051, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-15 18:00:00', '2026-08-10 16:00:00'),
(14102, 9051, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-15 18:00:00', '2026-08-10 16:00:00'),
(14103, 9052, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-16 19:00:00', '2026-08-10 16:00:00'),
(14104, 9052, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-16 19:00:00', '2026-08-10 16:00:00'),
(14105, 9053, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-17 20:00:00', '2026-08-10 16:00:00'),
(14106, 9053, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-17 20:00:00', '2026-08-10 16:00:00'),
(14107, 9054, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-18 21:00:00', '2026-08-10 16:00:00'),
(14108, 9054, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-18 21:00:00', '2026-08-10 16:00:00'),
(14109, 9055, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-19 22:00:00', '2026-08-10 16:00:00'),
(14110, 9055, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-19 22:00:00', '2026-08-10 16:00:00'),
(14111, 9056, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-20 23:00:00', '2026-08-10 16:00:00'),
(14112, 9056, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-20 23:00:00', '2026-08-10 16:00:00'),
(14113, 9057, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-21 16:00:00', '2026-08-10 16:00:00'),
(14114, 9057, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-21 16:00:00', '2026-08-10 16:00:00'),
(14115, 9058, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-22 17:00:00', '2026-08-10 16:00:00'),
(14116, 9058, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-22 17:00:00', '2026-08-10 16:00:00'),
(14117, 9059, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-23 18:00:00', '2026-08-10 16:00:00'),
(14118, 9059, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-23 18:00:00', '2026-08-10 16:00:00'),
(14119, 9060, 1, 'عملية شراء مكتملة من بيانات الـSeed الواقعية.', 1, '2026-06-24 19:00:00', '2026-08-10 16:00:00'),
(14120, 9060, 2, 'Completed purchase transaction generated for the realistic seed dataset.', 1, '2026-06-24 19:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'EGP',
  `metadata` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction_items`
--

INSERT INTO `transaction_items` (`id`, `transaction_id`, `item_type`, `item_id`, `quantity`, `unit_price`, `discount_amount`, `tax_amount`, `total_amount`, `currency`, `metadata`, `is_active`, `created_at`, `updated_at`) VALUES
(10001, 9001, 'course', 201, 1, 950.00, 50.00, 0.00, 910.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-15 16:00:00', '2026-08-10 16:00:00'),
(10002, 9002, 'course', 202, 1, 700.00, 0.00, 0.00, 700.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-16 17:00:00', '2026-08-10 16:00:00'),
(10003, 9003, 'course', 203, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-17 18:00:00', '2026-08-10 16:00:00'),
(10004, 9004, 'course', 204, 1, 950.00, 0.00, 0.00, 950.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-18 19:00:00', '2026-08-10 16:00:00'),
(10005, 9005, 'course', 205, 1, 650.00, 0.00, 0.00, 660.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-19 20:00:00', '2026-08-10 16:00:00'),
(10006, 9006, 'course', 206, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-20 21:00:00', '2026-08-10 16:00:00'),
(10007, 9007, 'course', 207, 1, 900.00, 0.00, 0.00, 900.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-21 22:00:00', '2026-08-10 16:00:00'),
(10008, 9008, 'course', 208, 1, 650.00, 50.00, 0.00, 600.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-22 23:00:00', '2026-08-10 16:00:00'),
(10009, 9009, 'course', 209, 1, 750.00, 0.00, 0.00, 760.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-23 16:00:00', '2026-08-10 16:00:00'),
(10010, 9010, 'course', 210, 1, 900.00, 0.00, 0.00, 900.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-24 17:00:00', '2026-08-10 16:00:00'),
(10011, 9011, 'course', 211, 1, 1000.00, 0.00, 0.00, 1000.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-25 18:00:00', '2026-08-10 16:00:00'),
(10012, 9012, 'course', 212, 1, 750.00, 0.00, 0.00, 750.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-26 19:00:00', '2026-08-10 16:00:00'),
(10013, 9013, 'course', 213, 1, 850.00, 0.00, 0.00, 860.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-27 20:00:00', '2026-08-10 16:00:00'),
(10014, 9014, 'course', 214, 1, 1000.00, 0.00, 0.00, 1000.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-28 21:00:00', '2026-08-10 16:00:00'),
(10015, 9015, 'course', 215, 1, 700.00, 50.00, 0.00, 650.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-29 22:00:00', '2026-08-10 16:00:00'),
(10016, 9016, 'course', 216, 1, 850.00, 0.00, 0.00, 850.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-30 23:00:00', '2026-08-10 16:00:00'),
(10017, 9017, 'course', 217, 1, 950.00, 0.00, 0.00, 960.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-01 16:00:00', '2026-08-10 16:00:00'),
(10018, 9018, 'course', 218, 1, 700.00, 0.00, 0.00, 700.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-02 17:00:00', '2026-08-10 16:00:00'),
(10019, 9019, 'course', 219, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-03 18:00:00', '2026-08-10 16:00:00'),
(10020, 9020, 'course', 220, 1, 950.00, 0.00, 0.00, 950.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-04 19:00:00', '2026-08-10 16:00:00'),
(10021, 9021, 'course', 221, 1, 650.00, 0.00, 0.00, 660.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-05 20:00:00', '2026-08-10 16:00:00'),
(10022, 9022, 'course', 222, 1, 800.00, 50.00, 0.00, 750.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-06 21:00:00', '2026-08-10 16:00:00'),
(10023, 9023, 'course', 223, 1, 850.00, 0.00, 0.00, 850.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-07 22:00:00', '2026-08-10 16:00:00'),
(10024, 9024, 'course', 224, 1, 1000.00, 0.00, 0.00, 1000.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-08 23:00:00', '2026-08-10 16:00:00'),
(10025, 9025, 'course', 201, 1, 950.00, 0.00, 0.00, 960.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-09 16:00:00', '2026-08-10 16:00:00'),
(10026, 9026, 'course', 202, 1, 700.00, 0.00, 0.00, 700.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-10 17:00:00', '2026-08-10 16:00:00'),
(10027, 9027, 'course', 203, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-11 18:00:00', '2026-08-10 16:00:00'),
(10028, 9028, 'course', 204, 1, 950.00, 0.00, 0.00, 950.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-12 19:00:00', '2026-08-10 16:00:00'),
(10029, 9029, 'course', 205, 1, 650.00, 50.00, 0.00, 610.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-13 20:00:00', '2026-08-10 16:00:00'),
(10030, 9030, 'course', 206, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-14 21:00:00', '2026-08-10 16:00:00'),
(10031, 9031, 'course', 207, 1, 900.00, 0.00, 0.00, 900.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-15 22:00:00', '2026-08-10 16:00:00'),
(10032, 9032, 'course', 208, 1, 650.00, 0.00, 0.00, 650.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-16 23:00:00', '2026-08-10 16:00:00'),
(10033, 9033, 'course', 209, 1, 750.00, 0.00, 0.00, 760.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-17 16:00:00', '2026-08-10 16:00:00'),
(10034, 9034, 'course', 210, 1, 900.00, 0.00, 0.00, 900.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-18 17:00:00', '2026-08-10 16:00:00'),
(10035, 9035, 'course', 211, 1, 1000.00, 0.00, 0.00, 1000.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-19 18:00:00', '2026-08-10 16:00:00'),
(10036, 9036, 'course', 212, 1, 750.00, 50.00, 0.00, 700.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-20 19:00:00', '2026-08-10 16:00:00'),
(10037, 9037, 'course', 213, 1, 850.00, 0.00, 0.00, 860.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-21 20:00:00', '2026-08-10 16:00:00'),
(10038, 9038, 'course', 214, 1, 1000.00, 0.00, 0.00, 1000.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-22 21:00:00', '2026-08-10 16:00:00'),
(10039, 9039, 'course', 215, 1, 700.00, 0.00, 0.00, 700.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-23 22:00:00', '2026-08-10 16:00:00'),
(10040, 9040, 'course', 216, 1, 850.00, 0.00, 0.00, 850.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-24 23:00:00', '2026-08-10 16:00:00'),
(10041, 9041, 'course', 217, 1, 950.00, 0.00, 0.00, 960.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-25 16:00:00', '2026-08-10 16:00:00'),
(10042, 9042, 'course', 218, 1, 700.00, 0.00, 0.00, 700.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-26 17:00:00', '2026-08-10 16:00:00'),
(10043, 9043, 'course', 219, 1, 800.00, 50.00, 0.00, 750.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-27 18:00:00', '2026-08-10 16:00:00'),
(10044, 9044, 'course', 220, 1, 950.00, 0.00, 0.00, 950.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-28 19:00:00', '2026-08-10 16:00:00'),
(10045, 9045, 'course', 221, 1, 650.00, 0.00, 0.00, 660.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-29 20:00:00', '2026-08-10 16:00:00'),
(10046, 9046, 'course', 222, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-30 21:00:00', '2026-08-10 16:00:00'),
(10047, 9047, 'course', 223, 1, 850.00, 0.00, 0.00, 850.00, 'EGP', '{\"access_days\": 180}', 1, '2026-07-31 22:00:00', '2026-08-10 16:00:00'),
(10048, 9048, 'course', 224, 1, 1000.00, 0.00, 0.00, 1000.00, 'EGP', '{\"access_days\": 180}', 1, '2026-08-01 23:00:00', '2026-08-10 16:00:00'),
(10049, 9049, 'course', 201, 1, 950.00, 0.00, 0.00, 960.00, 'EGP', '{\"access_days\": 180}', 1, '2026-08-02 16:00:00', '2026-08-10 16:00:00'),
(10050, 9050, 'course', 202, 1, 700.00, 50.00, 0.00, 650.00, 'EGP', '{\"access_days\": 180}', 1, '2026-08-03 17:00:00', '2026-08-10 16:00:00'),
(10051, 9051, 'course', 203, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-15 18:00:00', '2026-08-10 16:00:00'),
(10052, 9052, 'course', 204, 1, 950.00, 0.00, 0.00, 950.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-16 19:00:00', '2026-08-10 16:00:00'),
(10053, 9053, 'course', 205, 1, 650.00, 0.00, 0.00, 660.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-17 20:00:00', '2026-08-10 16:00:00'),
(10054, 9054, 'course', 206, 1, 800.00, 0.00, 0.00, 800.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-18 21:00:00', '2026-08-10 16:00:00'),
(10055, 9055, 'course', 207, 1, 900.00, 0.00, 0.00, 900.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-19 22:00:00', '2026-08-10 16:00:00'),
(10056, 9056, 'course', 208, 1, 650.00, 0.00, 0.00, 650.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-20 23:00:00', '2026-08-10 16:00:00'),
(10057, 9057, 'course', 209, 1, 750.00, 50.00, 0.00, 710.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-21 16:00:00', '2026-08-10 16:00:00'),
(10058, 9058, 'course', 210, 1, 900.00, 0.00, 0.00, 900.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-22 17:00:00', '2026-08-10 16:00:00'),
(10059, 9059, 'course', 211, 1, 1000.00, 0.00, 0.00, 1000.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-23 18:00:00', '2026-08-10 16:00:00'),
(10060, 9060, 'course', 212, 1, 750.00, 0.00, 0.00, 750.00, 'EGP', '{\"access_days\": 180}', 1, '2026-06-24 19:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items_translations`
--

CREATE TABLE `transaction_items_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_item_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction_items_translations`
--

INSERT INTO `transaction_items_translations` (`id`, `transaction_item_id`, `language_id`, `item_name`, `is_active`, `created_at`, `updated_at`) VALUES
(15001, 10001, 1, 'اشتراك كورس اللغة العربية', 1, '2026-06-15 16:00:00', '2026-08-10 16:00:00'),
(15002, 10001, 2, 'Arabic Language course subscription', 1, '2026-06-15 16:00:00', '2026-08-10 16:00:00'),
(15003, 10002, 1, 'اشتراك كورس اللغة العربية', 1, '2026-06-16 17:00:00', '2026-08-10 16:00:00'),
(15004, 10002, 2, 'Arabic Language course subscription', 1, '2026-06-16 17:00:00', '2026-08-10 16:00:00'),
(15005, 10003, 1, 'اشتراك كورس اللغة الإنجليزية', 1, '2026-06-17 18:00:00', '2026-08-10 16:00:00'),
(15006, 10003, 2, 'English Language course subscription', 1, '2026-06-17 18:00:00', '2026-08-10 16:00:00'),
(15007, 10004, 1, 'اشتراك كورس اللغة الإنجليزية', 1, '2026-06-18 19:00:00', '2026-08-10 16:00:00'),
(15008, 10004, 2, 'English Language course subscription', 1, '2026-06-18 19:00:00', '2026-08-10 16:00:00'),
(15009, 10005, 1, 'اشتراك كورس الرياضيات', 1, '2026-06-19 20:00:00', '2026-08-10 16:00:00'),
(15010, 10005, 2, 'Mathematics course subscription', 1, '2026-06-19 20:00:00', '2026-08-10 16:00:00'),
(15011, 10006, 1, 'اشتراك كورس الرياضيات', 1, '2026-06-20 21:00:00', '2026-08-10 16:00:00'),
(15012, 10006, 2, 'Mathematics course subscription', 1, '2026-06-20 21:00:00', '2026-08-10 16:00:00'),
(15013, 10007, 1, 'اشتراك كورس الفيزياء', 1, '2026-06-21 22:00:00', '2026-08-10 16:00:00'),
(15014, 10007, 2, 'Physics course subscription', 1, '2026-06-21 22:00:00', '2026-08-10 16:00:00'),
(15015, 10008, 1, 'اشتراك كورس الفيزياء', 1, '2026-06-22 23:00:00', '2026-08-10 16:00:00'),
(15016, 10008, 2, 'Physics course subscription', 1, '2026-06-22 23:00:00', '2026-08-10 16:00:00'),
(15017, 10009, 1, 'اشتراك كورس الكيمياء', 1, '2026-06-23 16:00:00', '2026-08-10 16:00:00'),
(15018, 10009, 2, 'Chemistry course subscription', 1, '2026-06-23 16:00:00', '2026-08-10 16:00:00'),
(15019, 10010, 1, 'اشتراك كورس الكيمياء', 1, '2026-06-24 17:00:00', '2026-08-10 16:00:00'),
(15020, 10010, 2, 'Chemistry course subscription', 1, '2026-06-24 17:00:00', '2026-08-10 16:00:00'),
(15021, 10011, 1, 'اشتراك كورس الأحياء', 1, '2026-06-25 18:00:00', '2026-08-10 16:00:00'),
(15022, 10011, 2, 'Biology course subscription', 1, '2026-06-25 18:00:00', '2026-08-10 16:00:00'),
(15023, 10012, 1, 'اشتراك كورس الأحياء', 1, '2026-06-26 19:00:00', '2026-08-10 16:00:00'),
(15024, 10012, 2, 'Biology course subscription', 1, '2026-06-26 19:00:00', '2026-08-10 16:00:00'),
(15025, 10013, 1, 'اشتراك كورس التاريخ', 1, '2026-06-27 20:00:00', '2026-08-10 16:00:00'),
(15026, 10013, 2, 'History course subscription', 1, '2026-06-27 20:00:00', '2026-08-10 16:00:00'),
(15027, 10014, 1, 'اشتراك كورس التاريخ', 1, '2026-06-28 21:00:00', '2026-08-10 16:00:00'),
(15028, 10014, 2, 'History course subscription', 1, '2026-06-28 21:00:00', '2026-08-10 16:00:00'),
(15029, 10015, 1, 'اشتراك كورس الجغرافيا', 1, '2026-06-29 22:00:00', '2026-08-10 16:00:00'),
(15030, 10015, 2, 'Geography course subscription', 1, '2026-06-29 22:00:00', '2026-08-10 16:00:00'),
(15031, 10016, 1, 'اشتراك كورس الجغرافيا', 1, '2026-06-30 23:00:00', '2026-08-10 16:00:00'),
(15032, 10016, 2, 'Geography course subscription', 1, '2026-06-30 23:00:00', '2026-08-10 16:00:00'),
(15033, 10017, 1, 'اشتراك كورس الفلسفة والمنطق', 1, '2026-07-01 16:00:00', '2026-08-10 16:00:00'),
(15034, 10017, 2, 'Philosophy & Logic course subscription', 1, '2026-07-01 16:00:00', '2026-08-10 16:00:00'),
(15035, 10018, 1, 'اشتراك كورس الفلسفة والمنطق', 1, '2026-07-02 17:00:00', '2026-08-10 16:00:00'),
(15036, 10018, 2, 'Philosophy & Logic course subscription', 1, '2026-07-02 17:00:00', '2026-08-10 16:00:00'),
(15037, 10019, 1, 'اشتراك كورس علوم الحاسب', 1, '2026-07-03 18:00:00', '2026-08-10 16:00:00'),
(15038, 10019, 2, 'Computer Science course subscription', 1, '2026-07-03 18:00:00', '2026-08-10 16:00:00'),
(15039, 10020, 1, 'اشتراك كورس علوم الحاسب', 1, '2026-07-04 19:00:00', '2026-08-10 16:00:00'),
(15040, 10020, 2, 'Computer Science course subscription', 1, '2026-07-04 19:00:00', '2026-08-10 16:00:00'),
(15041, 10021, 1, 'اشتراك كورس اللغة الفرنسية', 1, '2026-07-05 20:00:00', '2026-08-10 16:00:00'),
(15042, 10021, 2, 'French Language course subscription', 1, '2026-07-05 20:00:00', '2026-08-10 16:00:00'),
(15043, 10022, 1, 'اشتراك كورس اللغة الفرنسية', 1, '2026-07-06 21:00:00', '2026-08-10 16:00:00'),
(15044, 10022, 2, 'French Language course subscription', 1, '2026-07-06 21:00:00', '2026-08-10 16:00:00'),
(15045, 10023, 1, 'اشتراك كورس العلوم المتكاملة', 1, '2026-07-07 22:00:00', '2026-08-10 16:00:00'),
(15046, 10023, 2, 'Integrated Science course subscription', 1, '2026-07-07 22:00:00', '2026-08-10 16:00:00'),
(15047, 10024, 1, 'اشتراك كورس العلوم المتكاملة', 1, '2026-07-08 23:00:00', '2026-08-10 16:00:00'),
(15048, 10024, 2, 'Integrated Science course subscription', 1, '2026-07-08 23:00:00', '2026-08-10 16:00:00'),
(15049, 10025, 1, 'اشتراك كورس اللغة العربية', 1, '2026-07-09 16:00:00', '2026-08-10 16:00:00'),
(15050, 10025, 2, 'Arabic Language course subscription', 1, '2026-07-09 16:00:00', '2026-08-10 16:00:00'),
(15051, 10026, 1, 'اشتراك كورس اللغة العربية', 1, '2026-07-10 17:00:00', '2026-08-10 16:00:00'),
(15052, 10026, 2, 'Arabic Language course subscription', 1, '2026-07-10 17:00:00', '2026-08-10 16:00:00'),
(15053, 10027, 1, 'اشتراك كورس اللغة الإنجليزية', 1, '2026-07-11 18:00:00', '2026-08-10 16:00:00'),
(15054, 10027, 2, 'English Language course subscription', 1, '2026-07-11 18:00:00', '2026-08-10 16:00:00'),
(15055, 10028, 1, 'اشتراك كورس اللغة الإنجليزية', 1, '2026-07-12 19:00:00', '2026-08-10 16:00:00'),
(15056, 10028, 2, 'English Language course subscription', 1, '2026-07-12 19:00:00', '2026-08-10 16:00:00'),
(15057, 10029, 1, 'اشتراك كورس الرياضيات', 1, '2026-07-13 20:00:00', '2026-08-10 16:00:00'),
(15058, 10029, 2, 'Mathematics course subscription', 1, '2026-07-13 20:00:00', '2026-08-10 16:00:00'),
(15059, 10030, 1, 'اشتراك كورس الرياضيات', 1, '2026-07-14 21:00:00', '2026-08-10 16:00:00'),
(15060, 10030, 2, 'Mathematics course subscription', 1, '2026-07-14 21:00:00', '2026-08-10 16:00:00'),
(15061, 10031, 1, 'اشتراك كورس الفيزياء', 1, '2026-07-15 22:00:00', '2026-08-10 16:00:00'),
(15062, 10031, 2, 'Physics course subscription', 1, '2026-07-15 22:00:00', '2026-08-10 16:00:00'),
(15063, 10032, 1, 'اشتراك كورس الفيزياء', 1, '2026-07-16 23:00:00', '2026-08-10 16:00:00'),
(15064, 10032, 2, 'Physics course subscription', 1, '2026-07-16 23:00:00', '2026-08-10 16:00:00'),
(15065, 10033, 1, 'اشتراك كورس الكيمياء', 1, '2026-07-17 16:00:00', '2026-08-10 16:00:00'),
(15066, 10033, 2, 'Chemistry course subscription', 1, '2026-07-17 16:00:00', '2026-08-10 16:00:00'),
(15067, 10034, 1, 'اشتراك كورس الكيمياء', 1, '2026-07-18 17:00:00', '2026-08-10 16:00:00'),
(15068, 10034, 2, 'Chemistry course subscription', 1, '2026-07-18 17:00:00', '2026-08-10 16:00:00'),
(15069, 10035, 1, 'اشتراك كورس الأحياء', 1, '2026-07-19 18:00:00', '2026-08-10 16:00:00'),
(15070, 10035, 2, 'Biology course subscription', 1, '2026-07-19 18:00:00', '2026-08-10 16:00:00'),
(15071, 10036, 1, 'اشتراك كورس الأحياء', 1, '2026-07-20 19:00:00', '2026-08-10 16:00:00'),
(15072, 10036, 2, 'Biology course subscription', 1, '2026-07-20 19:00:00', '2026-08-10 16:00:00'),
(15073, 10037, 1, 'اشتراك كورس التاريخ', 1, '2026-07-21 20:00:00', '2026-08-10 16:00:00'),
(15074, 10037, 2, 'History course subscription', 1, '2026-07-21 20:00:00', '2026-08-10 16:00:00'),
(15075, 10038, 1, 'اشتراك كورس التاريخ', 1, '2026-07-22 21:00:00', '2026-08-10 16:00:00'),
(15076, 10038, 2, 'History course subscription', 1, '2026-07-22 21:00:00', '2026-08-10 16:00:00'),
(15077, 10039, 1, 'اشتراك كورس الجغرافيا', 1, '2026-07-23 22:00:00', '2026-08-10 16:00:00'),
(15078, 10039, 2, 'Geography course subscription', 1, '2026-07-23 22:00:00', '2026-08-10 16:00:00'),
(15079, 10040, 1, 'اشتراك كورس الجغرافيا', 1, '2026-07-24 23:00:00', '2026-08-10 16:00:00'),
(15080, 10040, 2, 'Geography course subscription', 1, '2026-07-24 23:00:00', '2026-08-10 16:00:00'),
(15081, 10041, 1, 'اشتراك كورس الفلسفة والمنطق', 1, '2026-07-25 16:00:00', '2026-08-10 16:00:00'),
(15082, 10041, 2, 'Philosophy & Logic course subscription', 1, '2026-07-25 16:00:00', '2026-08-10 16:00:00'),
(15083, 10042, 1, 'اشتراك كورس الفلسفة والمنطق', 1, '2026-07-26 17:00:00', '2026-08-10 16:00:00'),
(15084, 10042, 2, 'Philosophy & Logic course subscription', 1, '2026-07-26 17:00:00', '2026-08-10 16:00:00'),
(15085, 10043, 1, 'اشتراك كورس علوم الحاسب', 1, '2026-07-27 18:00:00', '2026-08-10 16:00:00'),
(15086, 10043, 2, 'Computer Science course subscription', 1, '2026-07-27 18:00:00', '2026-08-10 16:00:00'),
(15087, 10044, 1, 'اشتراك كورس علوم الحاسب', 1, '2026-07-28 19:00:00', '2026-08-10 16:00:00'),
(15088, 10044, 2, 'Computer Science course subscription', 1, '2026-07-28 19:00:00', '2026-08-10 16:00:00'),
(15089, 10045, 1, 'اشتراك كورس اللغة الفرنسية', 1, '2026-07-29 20:00:00', '2026-08-10 16:00:00'),
(15090, 10045, 2, 'French Language course subscription', 1, '2026-07-29 20:00:00', '2026-08-10 16:00:00'),
(15091, 10046, 1, 'اشتراك كورس اللغة الفرنسية', 1, '2026-07-30 21:00:00', '2026-08-10 16:00:00'),
(15092, 10046, 2, 'French Language course subscription', 1, '2026-07-30 21:00:00', '2026-08-10 16:00:00'),
(15093, 10047, 1, 'اشتراك كورس العلوم المتكاملة', 1, '2026-07-31 22:00:00', '2026-08-10 16:00:00'),
(15094, 10047, 2, 'Integrated Science course subscription', 1, '2026-07-31 22:00:00', '2026-08-10 16:00:00'),
(15095, 10048, 1, 'اشتراك كورس العلوم المتكاملة', 1, '2026-08-01 23:00:00', '2026-08-10 16:00:00'),
(15096, 10048, 2, 'Integrated Science course subscription', 1, '2026-08-01 23:00:00', '2026-08-10 16:00:00'),
(15097, 10049, 1, 'اشتراك كورس اللغة العربية', 1, '2026-08-02 16:00:00', '2026-08-10 16:00:00'),
(15098, 10049, 2, 'Arabic Language course subscription', 1, '2026-08-02 16:00:00', '2026-08-10 16:00:00'),
(15099, 10050, 1, 'اشتراك كورس اللغة العربية', 1, '2026-08-03 17:00:00', '2026-08-10 16:00:00'),
(15100, 10050, 2, 'Arabic Language course subscription', 1, '2026-08-03 17:00:00', '2026-08-10 16:00:00'),
(15101, 10051, 1, 'اشتراك كورس اللغة الإنجليزية', 1, '2026-06-15 18:00:00', '2026-08-10 16:00:00'),
(15102, 10051, 2, 'English Language course subscription', 1, '2026-06-15 18:00:00', '2026-08-10 16:00:00'),
(15103, 10052, 1, 'اشتراك كورس اللغة الإنجليزية', 1, '2026-06-16 19:00:00', '2026-08-10 16:00:00'),
(15104, 10052, 2, 'English Language course subscription', 1, '2026-06-16 19:00:00', '2026-08-10 16:00:00'),
(15105, 10053, 1, 'اشتراك كورس الرياضيات', 1, '2026-06-17 20:00:00', '2026-08-10 16:00:00'),
(15106, 10053, 2, 'Mathematics course subscription', 1, '2026-06-17 20:00:00', '2026-08-10 16:00:00'),
(15107, 10054, 1, 'اشتراك كورس الرياضيات', 1, '2026-06-18 21:00:00', '2026-08-10 16:00:00'),
(15108, 10054, 2, 'Mathematics course subscription', 1, '2026-06-18 21:00:00', '2026-08-10 16:00:00'),
(15109, 10055, 1, 'اشتراك كورس الفيزياء', 1, '2026-06-19 22:00:00', '2026-08-10 16:00:00'),
(15110, 10055, 2, 'Physics course subscription', 1, '2026-06-19 22:00:00', '2026-08-10 16:00:00'),
(15111, 10056, 1, 'اشتراك كورس الفيزياء', 1, '2026-06-20 23:00:00', '2026-08-10 16:00:00'),
(15112, 10056, 2, 'Physics course subscription', 1, '2026-06-20 23:00:00', '2026-08-10 16:00:00'),
(15113, 10057, 1, 'اشتراك كورس الكيمياء', 1, '2026-06-21 16:00:00', '2026-08-10 16:00:00'),
(15114, 10057, 2, 'Chemistry course subscription', 1, '2026-06-21 16:00:00', '2026-08-10 16:00:00'),
(15115, 10058, 1, 'اشتراك كورس الكيمياء', 1, '2026-06-22 17:00:00', '2026-08-10 16:00:00'),
(15116, 10058, 2, 'Chemistry course subscription', 1, '2026-06-22 17:00:00', '2026-08-10 16:00:00'),
(15117, 10059, 1, 'اشتراك كورس الأحياء', 1, '2026-06-23 18:00:00', '2026-08-10 16:00:00'),
(15118, 10059, 2, 'Biology course subscription', 1, '2026-06-23 18:00:00', '2026-08-10 16:00:00'),
(15119, 10060, 1, 'اشتراك كورس الأحياء', 1, '2026-06-24 19:00:00', '2026-08-10 16:00:00'),
(15120, 10060, 2, 'Biology course subscription', 1, '2026-06-24 19:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `true_false_answers`
--

CREATE TABLE `true_false_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `correct_answer` tinyint(1) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `true_false_answers`
--

INSERT INTO `true_false_answers` (`id`, `question_id`, `correct_answer`, `is_active`, `created_at`, `updated_at`) VALUES
(1001, 5004, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1002, 5009, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1003, 5014, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1004, 5019, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1005, 5024, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1006, 5029, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1007, 5034, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1008, 5039, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1009, 5044, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1010, 5049, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1011, 5054, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1012, 5059, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1013, 5064, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1014, 5069, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1015, 5074, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1016, 5079, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1017, 5084, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1018, 5089, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1019, 5094, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1020, 5099, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1021, 5104, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1022, 5109, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1023, 5114, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1024, 5119, 1, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(120) NOT NULL,
  `duration` int(10) UNSIGNED DEFAULT NULL,
  `duration_unit` enum('day','week','month') DEFAULT NULL,
  `lessons_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_type` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `created_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `course_id`, `subject_id`, `code`, `duration`, `duration_unit`, `lessons_count`, `is_active`, `created_by_type`, `created_by_id`, `created_at`, `updated_at`) VALUES
(1001, 201, 1, 'C201-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1002, 201, 1, 'C201-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1003, 201, 1, 'C201-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1004, 202, 1, 'C202-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1005, 202, 1, 'C202-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1006, 202, 1, 'C202-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1007, 203, 2, 'C203-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1008, 203, 2, 'C203-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1009, 203, 2, 'C203-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1010, 204, 2, 'C204-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1011, 204, 2, 'C204-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1012, 204, 2, 'C204-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1013, 205, 3, 'C205-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1014, 205, 3, 'C205-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1015, 205, 3, 'C205-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1016, 206, 3, 'C206-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1017, 206, 3, 'C206-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1018, 206, 3, 'C206-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1019, 207, 4, 'C207-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1020, 207, 4, 'C207-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1021, 207, 4, 'C207-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1022, 208, 4, 'C208-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1023, 208, 4, 'C208-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1024, 208, 4, 'C208-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1025, 209, 5, 'C209-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1026, 209, 5, 'C209-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1027, 209, 5, 'C209-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1028, 210, 5, 'C210-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1029, 210, 5, 'C210-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1030, 210, 5, 'C210-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1031, 211, 6, 'C211-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1032, 211, 6, 'C211-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1033, 211, 6, 'C211-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1034, 212, 6, 'C212-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1035, 212, 6, 'C212-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1036, 212, 6, 'C212-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1037, 213, 7, 'C213-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1038, 213, 7, 'C213-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1039, 213, 7, 'C213-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1040, 214, 7, 'C214-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1041, 214, 7, 'C214-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1042, 214, 7, 'C214-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1043, 215, 8, 'C215-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1044, 215, 8, 'C215-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1045, 215, 8, 'C215-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1046, 216, 8, 'C216-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1047, 216, 8, 'C216-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1048, 216, 8, 'C216-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1049, 217, 9, 'C217-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1050, 217, 9, 'C217-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1051, 217, 9, 'C217-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1052, 218, 9, 'C218-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1053, 218, 9, 'C218-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1054, 218, 9, 'C218-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1055, 219, 10, 'C219-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1056, 219, 10, 'C219-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1057, 219, 10, 'C219-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1058, 220, 10, 'C220-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1059, 220, 10, 'C220-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1060, 220, 10, 'C220-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1061, 221, 11, 'C221-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1062, 221, 11, 'C221-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1063, 221, 11, 'C221-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1064, 222, 11, 'C222-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1065, 222, 11, 'C222-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1066, 222, 11, 'C222-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1067, 223, 15, 'C223-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1068, 223, 15, 'C223-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1069, 223, 15, 'C223-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1070, 224, 15, 'C224-U1', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1071, 224, 15, 'C224-U2', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(1072, 224, 15, 'C224-U3', 2, 'week', 6, 1, 'admin', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `units_translations`
--

CREATE TABLE `units_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units_translations`
--

INSERT INTO `units_translations` (`id`, `unit_id`, `language_id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(3001, 1001, 1, 'الأساسيات وبناء المفاهيم - اللغة العربية', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3002, 1001, 2, 'Foundations and Core Concepts - Arabic Language', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3003, 1002, 1, 'التطبيق وحل المسائل - اللغة العربية', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3004, 1002, 2, 'Application and Problem Solving - Arabic Language', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3005, 1003, 1, 'المراجعة والاختبار الشامل - اللغة العربية', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3006, 1003, 2, 'Revision and Comprehensive Practice - Arabic Language', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3007, 1004, 1, 'الأساسيات وبناء المفاهيم - اللغة العربية', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3008, 1004, 2, 'Foundations and Core Concepts - Arabic Language', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3009, 1005, 1, 'التطبيق وحل المسائل - اللغة العربية', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3010, 1005, 2, 'Application and Problem Solving - Arabic Language', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3011, 1006, 1, 'المراجعة والاختبار الشامل - اللغة العربية', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3012, 1006, 2, 'Revision and Comprehensive Practice - Arabic Language', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3013, 1007, 1, 'الأساسيات وبناء المفاهيم - اللغة الإنجليزية', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3014, 1007, 2, 'Foundations and Core Concepts - English Language', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3015, 1008, 1, 'التطبيق وحل المسائل - اللغة الإنجليزية', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3016, 1008, 2, 'Application and Problem Solving - English Language', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3017, 1009, 1, 'المراجعة والاختبار الشامل - اللغة الإنجليزية', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3018, 1009, 2, 'Revision and Comprehensive Practice - English Language', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3019, 1010, 1, 'الأساسيات وبناء المفاهيم - اللغة الإنجليزية', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3020, 1010, 2, 'Foundations and Core Concepts - English Language', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3021, 1011, 1, 'التطبيق وحل المسائل - اللغة الإنجليزية', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3022, 1011, 2, 'Application and Problem Solving - English Language', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3023, 1012, 1, 'المراجعة والاختبار الشامل - اللغة الإنجليزية', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3024, 1012, 2, 'Revision and Comprehensive Practice - English Language', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3025, 1013, 1, 'الأساسيات وبناء المفاهيم - الرياضيات', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3026, 1013, 2, 'Foundations and Core Concepts - Mathematics', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3027, 1014, 1, 'التطبيق وحل المسائل - الرياضيات', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3028, 1014, 2, 'Application and Problem Solving - Mathematics', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3029, 1015, 1, 'المراجعة والاختبار الشامل - الرياضيات', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3030, 1015, 2, 'Revision and Comprehensive Practice - Mathematics', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3031, 1016, 1, 'الأساسيات وبناء المفاهيم - الرياضيات', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3032, 1016, 2, 'Foundations and Core Concepts - Mathematics', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3033, 1017, 1, 'التطبيق وحل المسائل - الرياضيات', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3034, 1017, 2, 'Application and Problem Solving - Mathematics', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3035, 1018, 1, 'المراجعة والاختبار الشامل - الرياضيات', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3036, 1018, 2, 'Revision and Comprehensive Practice - Mathematics', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3037, 1019, 1, 'الأساسيات وبناء المفاهيم - الفيزياء', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3038, 1019, 2, 'Foundations and Core Concepts - Physics', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3039, 1020, 1, 'التطبيق وحل المسائل - الفيزياء', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3040, 1020, 2, 'Application and Problem Solving - Physics', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3041, 1021, 1, 'المراجعة والاختبار الشامل - الفيزياء', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3042, 1021, 2, 'Revision and Comprehensive Practice - Physics', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3043, 1022, 1, 'الأساسيات وبناء المفاهيم - الفيزياء', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3044, 1022, 2, 'Foundations and Core Concepts - Physics', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3045, 1023, 1, 'التطبيق وحل المسائل - الفيزياء', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3046, 1023, 2, 'Application and Problem Solving - Physics', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3047, 1024, 1, 'المراجعة والاختبار الشامل - الفيزياء', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3048, 1024, 2, 'Revision and Comprehensive Practice - Physics', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3049, 1025, 1, 'الأساسيات وبناء المفاهيم - الكيمياء', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3050, 1025, 2, 'Foundations and Core Concepts - Chemistry', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3051, 1026, 1, 'التطبيق وحل المسائل - الكيمياء', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3052, 1026, 2, 'Application and Problem Solving - Chemistry', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3053, 1027, 1, 'المراجعة والاختبار الشامل - الكيمياء', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3054, 1027, 2, 'Revision and Comprehensive Practice - Chemistry', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3055, 1028, 1, 'الأساسيات وبناء المفاهيم - الكيمياء', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3056, 1028, 2, 'Foundations and Core Concepts - Chemistry', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3057, 1029, 1, 'التطبيق وحل المسائل - الكيمياء', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3058, 1029, 2, 'Application and Problem Solving - Chemistry', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3059, 1030, 1, 'المراجعة والاختبار الشامل - الكيمياء', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3060, 1030, 2, 'Revision and Comprehensive Practice - Chemistry', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3061, 1031, 1, 'الأساسيات وبناء المفاهيم - الأحياء', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3062, 1031, 2, 'Foundations and Core Concepts - Biology', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3063, 1032, 1, 'التطبيق وحل المسائل - الأحياء', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3064, 1032, 2, 'Application and Problem Solving - Biology', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3065, 1033, 1, 'المراجعة والاختبار الشامل - الأحياء', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3066, 1033, 2, 'Revision and Comprehensive Practice - Biology', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3067, 1034, 1, 'الأساسيات وبناء المفاهيم - الأحياء', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3068, 1034, 2, 'Foundations and Core Concepts - Biology', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3069, 1035, 1, 'التطبيق وحل المسائل - الأحياء', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3070, 1035, 2, 'Application and Problem Solving - Biology', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3071, 1036, 1, 'المراجعة والاختبار الشامل - الأحياء', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3072, 1036, 2, 'Revision and Comprehensive Practice - Biology', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3073, 1037, 1, 'الأساسيات وبناء المفاهيم - التاريخ', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3074, 1037, 2, 'Foundations and Core Concepts - History', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3075, 1038, 1, 'التطبيق وحل المسائل - التاريخ', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3076, 1038, 2, 'Application and Problem Solving - History', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3077, 1039, 1, 'المراجعة والاختبار الشامل - التاريخ', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3078, 1039, 2, 'Revision and Comprehensive Practice - History', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3079, 1040, 1, 'الأساسيات وبناء المفاهيم - التاريخ', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3080, 1040, 2, 'Foundations and Core Concepts - History', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3081, 1041, 1, 'التطبيق وحل المسائل - التاريخ', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3082, 1041, 2, 'Application and Problem Solving - History', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3083, 1042, 1, 'المراجعة والاختبار الشامل - التاريخ', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3084, 1042, 2, 'Revision and Comprehensive Practice - History', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3085, 1043, 1, 'الأساسيات وبناء المفاهيم - الجغرافيا', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3086, 1043, 2, 'Foundations and Core Concepts - Geography', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3087, 1044, 1, 'التطبيق وحل المسائل - الجغرافيا', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3088, 1044, 2, 'Application and Problem Solving - Geography', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3089, 1045, 1, 'المراجعة والاختبار الشامل - الجغرافيا', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3090, 1045, 2, 'Revision and Comprehensive Practice - Geography', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3091, 1046, 1, 'الأساسيات وبناء المفاهيم - الجغرافيا', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3092, 1046, 2, 'Foundations and Core Concepts - Geography', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3093, 1047, 1, 'التطبيق وحل المسائل - الجغرافيا', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3094, 1047, 2, 'Application and Problem Solving - Geography', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3095, 1048, 1, 'المراجعة والاختبار الشامل - الجغرافيا', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3096, 1048, 2, 'Revision and Comprehensive Practice - Geography', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3097, 1049, 1, 'الأساسيات وبناء المفاهيم - الفلسفة والمنطق', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3098, 1049, 2, 'Foundations and Core Concepts - Philosophy & Logic', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3099, 1050, 1, 'التطبيق وحل المسائل - الفلسفة والمنطق', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3100, 1050, 2, 'Application and Problem Solving - Philosophy & Logic', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3101, 1051, 1, 'المراجعة والاختبار الشامل - الفلسفة والمنطق', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3102, 1051, 2, 'Revision and Comprehensive Practice - Philosophy & Logic', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3103, 1052, 1, 'الأساسيات وبناء المفاهيم - الفلسفة والمنطق', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3104, 1052, 2, 'Foundations and Core Concepts - Philosophy & Logic', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3105, 1053, 1, 'التطبيق وحل المسائل - الفلسفة والمنطق', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3106, 1053, 2, 'Application and Problem Solving - Philosophy & Logic', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3107, 1054, 1, 'المراجعة والاختبار الشامل - الفلسفة والمنطق', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3108, 1054, 2, 'Revision and Comprehensive Practice - Philosophy & Logic', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3109, 1055, 1, 'الأساسيات وبناء المفاهيم - علوم الحاسب', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3110, 1055, 2, 'Foundations and Core Concepts - Computer Science', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3111, 1056, 1, 'التطبيق وحل المسائل - علوم الحاسب', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3112, 1056, 2, 'Application and Problem Solving - Computer Science', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3113, 1057, 1, 'المراجعة والاختبار الشامل - علوم الحاسب', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3114, 1057, 2, 'Revision and Comprehensive Practice - Computer Science', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3115, 1058, 1, 'الأساسيات وبناء المفاهيم - علوم الحاسب', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3116, 1058, 2, 'Foundations and Core Concepts - Computer Science', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3117, 1059, 1, 'التطبيق وحل المسائل - علوم الحاسب', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3118, 1059, 2, 'Application and Problem Solving - Computer Science', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3119, 1060, 1, 'المراجعة والاختبار الشامل - علوم الحاسب', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3120, 1060, 2, 'Revision and Comprehensive Practice - Computer Science', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3121, 1061, 1, 'الأساسيات وبناء المفاهيم - اللغة الفرنسية', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3122, 1061, 2, 'Foundations and Core Concepts - French Language', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3123, 1062, 1, 'التطبيق وحل المسائل - اللغة الفرنسية', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3124, 1062, 2, 'Application and Problem Solving - French Language', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3125, 1063, 1, 'المراجعة والاختبار الشامل - اللغة الفرنسية', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3126, 1063, 2, 'Revision and Comprehensive Practice - French Language', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3127, 1064, 1, 'الأساسيات وبناء المفاهيم - اللغة الفرنسية', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3128, 1064, 2, 'Foundations and Core Concepts - French Language', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3129, 1065, 1, 'التطبيق وحل المسائل - اللغة الفرنسية', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3130, 1065, 2, 'Application and Problem Solving - French Language', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3131, 1066, 1, 'المراجعة والاختبار الشامل - اللغة الفرنسية', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3132, 1066, 2, 'Revision and Comprehensive Practice - French Language', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3133, 1067, 1, 'الأساسيات وبناء المفاهيم - العلوم المتكاملة', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3134, 1067, 2, 'Foundations and Core Concepts - Integrated Science', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3135, 1068, 1, 'التطبيق وحل المسائل - العلوم المتكاملة', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3136, 1068, 2, 'Application and Problem Solving - Integrated Science', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3137, 1069, 1, 'المراجعة والاختبار الشامل - العلوم المتكاملة', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3138, 1069, 2, 'Revision and Comprehensive Practice - Integrated Science', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3139, 1070, 1, 'الأساسيات وبناء المفاهيم - العلوم المتكاملة', 'وحدة تعليمية تغطي الأساسيات وبناء المفاهيم مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3140, 1070, 2, 'Foundations and Core Concepts - Integrated Science', 'Learning unit covering foundations and core concepts with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3141, 1071, 1, 'التطبيق وحل المسائل - العلوم المتكاملة', 'وحدة تعليمية تغطي التطبيق وحل المسائل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3142, 1071, 2, 'Application and Problem Solving - Integrated Science', 'Learning unit covering application and problem solving with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3143, 1072, 1, 'المراجعة والاختبار الشامل - العلوم المتكاملة', 'وحدة تعليمية تغطي المراجعة والاختبار الشامل مع أمثلة وأسئلة تطبيقية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(3144, 1072, 2, 'Revision and Comprehensive Practice - Integrated Science', 'Learning unit covering revision and comprehensive practice with examples and practice.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `unit_teachers`
--

CREATE TABLE `unit_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_teachers`
--

INSERT INTO `unit_teachers` (`id`, `unit_id`, `teacher_id`, `is_active`, `created_at`) VALUES
(1001, 1001, 201, 1, '2026-07-01 14:00:00'),
(1002, 1002, 201, 1, '2026-07-01 14:00:00'),
(1003, 1003, 201, 1, '2026-07-01 14:00:00'),
(1004, 1004, 201, 1, '2026-07-01 14:00:00'),
(1005, 1005, 201, 1, '2026-07-01 14:00:00'),
(1006, 1006, 201, 1, '2026-07-01 14:00:00'),
(1007, 1007, 202, 1, '2026-07-01 14:00:00'),
(1008, 1008, 202, 1, '2026-07-01 14:00:00'),
(1009, 1009, 202, 1, '2026-07-01 14:00:00'),
(1010, 1010, 202, 1, '2026-07-01 14:00:00'),
(1011, 1011, 202, 1, '2026-07-01 14:00:00'),
(1012, 1012, 202, 1, '2026-07-01 14:00:00'),
(1013, 1013, 203, 1, '2026-07-01 14:00:00'),
(1014, 1014, 203, 1, '2026-07-01 14:00:00'),
(1015, 1015, 203, 1, '2026-07-01 14:00:00'),
(1016, 1016, 203, 1, '2026-07-01 14:00:00'),
(1017, 1017, 203, 1, '2026-07-01 14:00:00'),
(1018, 1018, 203, 1, '2026-07-01 14:00:00'),
(1019, 1019, 204, 1, '2026-07-01 14:00:00'),
(1020, 1020, 204, 1, '2026-07-01 14:00:00'),
(1021, 1021, 204, 1, '2026-07-01 14:00:00'),
(1022, 1022, 204, 1, '2026-07-01 14:00:00'),
(1023, 1023, 204, 1, '2026-07-01 14:00:00'),
(1024, 1024, 204, 1, '2026-07-01 14:00:00'),
(1025, 1025, 205, 1, '2026-07-01 14:00:00'),
(1026, 1026, 205, 1, '2026-07-01 14:00:00'),
(1027, 1027, 205, 1, '2026-07-01 14:00:00'),
(1028, 1028, 205, 1, '2026-07-01 14:00:00'),
(1029, 1029, 205, 1, '2026-07-01 14:00:00'),
(1030, 1030, 205, 1, '2026-07-01 14:00:00'),
(1031, 1031, 206, 1, '2026-07-01 14:00:00'),
(1032, 1032, 206, 1, '2026-07-01 14:00:00'),
(1033, 1033, 206, 1, '2026-07-01 14:00:00'),
(1034, 1034, 206, 1, '2026-07-01 14:00:00'),
(1035, 1035, 206, 1, '2026-07-01 14:00:00'),
(1036, 1036, 206, 1, '2026-07-01 14:00:00'),
(1037, 1037, 207, 1, '2026-07-01 14:00:00'),
(1038, 1038, 207, 1, '2026-07-01 14:00:00'),
(1039, 1039, 207, 1, '2026-07-01 14:00:00'),
(1040, 1040, 207, 1, '2026-07-01 14:00:00'),
(1041, 1041, 207, 1, '2026-07-01 14:00:00'),
(1042, 1042, 207, 1, '2026-07-01 14:00:00'),
(1043, 1043, 208, 1, '2026-07-01 14:00:00'),
(1044, 1044, 208, 1, '2026-07-01 14:00:00'),
(1045, 1045, 208, 1, '2026-07-01 14:00:00'),
(1046, 1046, 208, 1, '2026-07-01 14:00:00'),
(1047, 1047, 208, 1, '2026-07-01 14:00:00'),
(1048, 1048, 208, 1, '2026-07-01 14:00:00'),
(1049, 1049, 209, 1, '2026-07-01 14:00:00'),
(1050, 1050, 209, 1, '2026-07-01 14:00:00'),
(1051, 1051, 209, 1, '2026-07-01 14:00:00'),
(1052, 1052, 209, 1, '2026-07-01 14:00:00'),
(1053, 1053, 209, 1, '2026-07-01 14:00:00'),
(1054, 1054, 209, 1, '2026-07-01 14:00:00'),
(1055, 1055, 210, 1, '2026-07-01 14:00:00'),
(1056, 1056, 210, 1, '2026-07-01 14:00:00'),
(1057, 1057, 210, 1, '2026-07-01 14:00:00'),
(1058, 1058, 210, 1, '2026-07-01 14:00:00'),
(1059, 1059, 210, 1, '2026-07-01 14:00:00'),
(1060, 1060, 210, 1, '2026-07-01 14:00:00'),
(1061, 1061, 211, 1, '2026-07-01 14:00:00'),
(1062, 1062, 211, 1, '2026-07-01 14:00:00'),
(1063, 1063, 211, 1, '2026-07-01 14:00:00'),
(1064, 1064, 211, 1, '2026-07-01 14:00:00'),
(1065, 1065, 211, 1, '2026-07-01 14:00:00'),
(1066, 1066, 211, 1, '2026-07-01 14:00:00'),
(1067, 1067, 212, 1, '2026-07-01 14:00:00'),
(1068, 1068, 212, 1, '2026-07-01 14:00:00'),
(1069, 1069, 212, 1, '2026-07-01 14:00:00'),
(1070, 1070, 212, 1, '2026-07-01 14:00:00'),
(1071, 1071, 212, 1, '2026-07-01 14:00:00'),
(1072, 1072, 212, 1, '2026-07-01 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(120) NOT NULL,
  `duration_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `publish_status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `unit_id`, `code`, `duration_seconds`, `publish_status`, `is_active`, `created_at`, `updated_at`) VALUES
(2001, 1001, 'V-201-1-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2002, 1001, 'V-201-1-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2003, 1002, 'V-201-2-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2004, 1002, 'V-201-2-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2005, 1003, 'V-201-3-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2006, 1003, 'V-201-3-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2007, 1004, 'V-202-1-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2008, 1004, 'V-202-1-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2009, 1005, 'V-202-2-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2010, 1005, 'V-202-2-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2011, 1006, 'V-202-3-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2012, 1006, 'V-202-3-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2013, 1007, 'V-203-1-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2014, 1007, 'V-203-1-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2015, 1008, 'V-203-2-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2016, 1008, 'V-203-2-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2017, 1009, 'V-203-3-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2018, 1009, 'V-203-3-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2019, 1010, 'V-204-1-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2020, 1010, 'V-204-1-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2021, 1011, 'V-204-2-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2022, 1011, 'V-204-2-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2023, 1012, 'V-204-3-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2024, 1012, 'V-204-3-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2025, 1013, 'V-205-1-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2026, 1013, 'V-205-1-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2027, 1014, 'V-205-2-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2028, 1014, 'V-205-2-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2029, 1015, 'V-205-3-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2030, 1015, 'V-205-3-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2031, 1016, 'V-206-1-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2032, 1016, 'V-206-1-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2033, 1017, 'V-206-2-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2034, 1017, 'V-206-2-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2035, 1018, 'V-206-3-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2036, 1018, 'V-206-3-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2037, 1019, 'V-207-1-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2038, 1019, 'V-207-1-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2039, 1020, 'V-207-2-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2040, 1020, 'V-207-2-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2041, 1021, 'V-207-3-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2042, 1021, 'V-207-3-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2043, 1022, 'V-208-1-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2044, 1022, 'V-208-1-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2045, 1023, 'V-208-2-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2046, 1023, 'V-208-2-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2047, 1024, 'V-208-3-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2048, 1024, 'V-208-3-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2049, 1025, 'V-209-1-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2050, 1025, 'V-209-1-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2051, 1026, 'V-209-2-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2052, 1026, 'V-209-2-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2053, 1027, 'V-209-3-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2054, 1027, 'V-209-3-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2055, 1028, 'V-210-1-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2056, 1028, 'V-210-1-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2057, 1029, 'V-210-2-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2058, 1029, 'V-210-2-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2059, 1030, 'V-210-3-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2060, 1030, 'V-210-3-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2061, 1031, 'V-211-1-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2062, 1031, 'V-211-1-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2063, 1032, 'V-211-2-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2064, 1032, 'V-211-2-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2065, 1033, 'V-211-3-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2066, 1033, 'V-211-3-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2067, 1034, 'V-212-1-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2068, 1034, 'V-212-1-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2069, 1035, 'V-212-2-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2070, 1035, 'V-212-2-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2071, 1036, 'V-212-3-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2072, 1036, 'V-212-3-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2073, 1037, 'V-213-1-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2074, 1037, 'V-213-1-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2075, 1038, 'V-213-2-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2076, 1038, 'V-213-2-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2077, 1039, 'V-213-3-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2078, 1039, 'V-213-3-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2079, 1040, 'V-214-1-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2080, 1040, 'V-214-1-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2081, 1041, 'V-214-2-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2082, 1041, 'V-214-2-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2083, 1042, 'V-214-3-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2084, 1042, 'V-214-3-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2085, 1043, 'V-215-1-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2086, 1043, 'V-215-1-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2087, 1044, 'V-215-2-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2088, 1044, 'V-215-2-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2089, 1045, 'V-215-3-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2090, 1045, 'V-215-3-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2091, 1046, 'V-216-1-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2092, 1046, 'V-216-1-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2093, 1047, 'V-216-2-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2094, 1047, 'V-216-2-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2095, 1048, 'V-216-3-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2096, 1048, 'V-216-3-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2097, 1049, 'V-217-1-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2098, 1049, 'V-217-1-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2099, 1050, 'V-217-2-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2100, 1050, 'V-217-2-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2101, 1051, 'V-217-3-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2102, 1051, 'V-217-3-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2103, 1052, 'V-218-1-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2104, 1052, 'V-218-1-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2105, 1053, 'V-218-2-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2106, 1053, 'V-218-2-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2107, 1054, 'V-218-3-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2108, 1054, 'V-218-3-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2109, 1055, 'V-219-1-1', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2110, 1055, 'V-219-1-2', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2111, 1056, 'V-219-2-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2112, 1056, 'V-219-2-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2113, 1057, 'V-219-3-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2114, 1057, 'V-219-3-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2115, 1058, 'V-220-1-1', 1920, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2116, 1058, 'V-220-1-2', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2117, 1059, 'V-220-2-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2118, 1059, 'V-220-2-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2119, 1060, 'V-220-3-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2120, 1060, 'V-220-3-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2121, 1061, 'V-221-1-1', 2100, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2122, 1061, 'V-221-1-2', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2123, 1062, 'V-221-2-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2124, 1062, 'V-221-2-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2125, 1063, 'V-221-3-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2126, 1063, 'V-221-3-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2127, 1064, 'V-222-1-1', 2280, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2128, 1064, 'V-222-1-2', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2129, 1065, 'V-222-2-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2130, 1065, 'V-222-2-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2131, 1066, 'V-222-3-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2132, 1066, 'V-222-3-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2133, 1067, 'V-223-1-1', 2460, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2134, 1067, 'V-223-1-2', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2135, 1068, 'V-223-2-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2136, 1068, 'V-223-2-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2137, 1069, 'V-223-3-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2138, 1069, 'V-223-3-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2139, 1070, 'V-224-1-1', 1200, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2140, 1070, 'V-224-1-2', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2141, 1071, 'V-224-2-1', 1380, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2142, 1071, 'V-224-2-2', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2143, 1072, 'V-224-3-1', 1560, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(2144, 1072, 'V-224-3-2', 1740, 'published', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `videos_translations`
--

CREATE TABLE `videos_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `videos_translations`
--

INSERT INTO `videos_translations` (`id`, `video_id`, `language_id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(5001, 2001, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5002, 2001, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5003, 2002, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5004, 2002, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5005, 2003, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5006, 2003, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5007, 2004, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5008, 2004, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5009, 2005, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5010, 2005, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5011, 2006, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5012, 2006, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5013, 2007, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5014, 2007, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5015, 2008, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5016, 2008, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5017, 2009, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5018, 2009, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5019, 2010, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5020, 2010, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5021, 2011, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5022, 2011, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5023, 2012, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة العربية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5024, 2012, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Arabic Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5025, 2013, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5026, 2013, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5027, 2014, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5028, 2014, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5029, 2015, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5030, 2015, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5031, 2016, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5032, 2016, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5033, 2017, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5034, 2017, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5035, 2018, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5036, 2018, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5037, 2019, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5038, 2019, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5039, 2020, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5040, 2020, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5041, 2021, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5042, 2021, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5043, 2022, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5044, 2022, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5045, 2023, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5046, 2023, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5047, 2024, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الإنجليزية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5048, 2024, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the English Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5049, 2025, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5050, 2025, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5051, 2026, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5052, 2026, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5053, 2027, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5054, 2027, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5055, 2028, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5056, 2028, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5057, 2029, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5058, 2029, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5059, 2030, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5060, 2030, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5061, 2031, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5062, 2031, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5063, 2032, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5064, 2032, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5065, 2033, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5066, 2033, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5067, 2034, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5068, 2034, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5069, 2035, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5070, 2035, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5071, 2036, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الرياضيات.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5072, 2036, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Mathematics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5073, 2037, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5074, 2037, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5075, 2038, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5076, 2038, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5077, 2039, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5078, 2039, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5079, 2040, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5080, 2040, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5081, 2041, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5082, 2041, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5083, 2042, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5084, 2042, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5085, 2043, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5086, 2043, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5087, 2044, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5088, 2044, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5089, 2045, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5090, 2045, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5091, 2046, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5092, 2046, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5093, 2047, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5094, 2047, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5095, 2048, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفيزياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5096, 2048, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Physics unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5097, 2049, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5098, 2049, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5099, 2050, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5100, 2050, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5101, 2051, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5102, 2051, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5103, 2052, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5104, 2052, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5105, 2053, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5106, 2053, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5107, 2054, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5108, 2054, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5109, 2055, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5110, 2055, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5111, 2056, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5112, 2056, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5113, 2057, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5114, 2057, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5115, 2058, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5116, 2058, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5117, 2059, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5118, 2059, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5119, 2060, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الكيمياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5120, 2060, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Chemistry unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5121, 2061, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5122, 2061, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5123, 2062, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5124, 2062, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5125, 2063, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5126, 2063, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5127, 2064, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5128, 2064, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5129, 2065, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5130, 2065, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5131, 2066, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5132, 2066, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5133, 2067, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5134, 2067, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5135, 2068, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5136, 2068, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5137, 2069, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5138, 2069, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5139, 2070, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5140, 2070, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5141, 2071, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5142, 2071, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5143, 2072, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الأحياء.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5144, 2072, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Biology unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5145, 2073, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5146, 2073, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5147, 2074, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5148, 2074, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5149, 2075, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5150, 2075, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5151, 2076, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5152, 2076, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5153, 2077, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5154, 2077, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5155, 2078, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5156, 2078, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5157, 2079, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5158, 2079, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5159, 2080, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5160, 2080, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5161, 2081, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5162, 2081, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5163, 2082, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5164, 2082, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5165, 2083, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5166, 2083, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5167, 2084, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة التاريخ.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5168, 2084, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the History unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5169, 2085, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5170, 2085, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5171, 2086, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5172, 2086, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5173, 2087, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5174, 2087, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5175, 2088, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5176, 2088, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5177, 2089, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5178, 2089, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5179, 2090, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5180, 2090, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5181, 2091, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5182, 2091, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5183, 2092, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5184, 2092, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5185, 2093, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5186, 2093, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5187, 2094, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5188, 2094, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5189, 2095, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5190, 2095, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5191, 2096, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الجغرافيا.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5192, 2096, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Geography unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5193, 2097, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5194, 2097, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5195, 2098, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5196, 2098, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5197, 2099, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5198, 2099, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5199, 2100, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5200, 2100, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5201, 2101, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5202, 2101, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5203, 2102, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5204, 2102, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5205, 2103, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5206, 2103, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5207, 2104, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5208, 2104, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5209, 2105, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5210, 2105, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5211, 2106, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5212, 2106, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5213, 2107, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5214, 2107, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5215, 2108, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة الفلسفة والمنطق.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5216, 2108, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Philosophy & Logic unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5217, 2109, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5218, 2109, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5219, 2110, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5220, 2110, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5221, 2111, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5222, 2111, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5223, 2112, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5224, 2112, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5225, 2113, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5226, 2113, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5227, 2114, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5228, 2114, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5229, 2115, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5230, 2115, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5231, 2116, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5232, 2116, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5233, 2117, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5234, 2117, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5235, 2118, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5236, 2118, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5237, 2119, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5238, 2119, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5239, 2120, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة علوم الحاسب.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5240, 2120, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Computer Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5241, 2121, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5242, 2121, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5243, 2122, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5244, 2122, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5245, 2123, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5246, 2123, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5247, 2124, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5248, 2124, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5249, 2125, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5250, 2125, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5251, 2126, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5252, 2126, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5253, 2127, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5254, 2127, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5255, 2128, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5256, 2128, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5257, 2129, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5258, 2129, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5259, 2130, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5260, 2130, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5261, 2131, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5262, 2131, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5263, 2132, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة اللغة الفرنسية.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5264, 2132, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the French Language unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5265, 2133, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5266, 2133, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5267, 2134, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5268, 2134, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5269, 2135, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5270, 2135, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5271, 2136, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5272, 2136, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5273, 2137, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5274, 2137, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5275, 2138, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5276, 2138, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5277, 2139, 1, 'الدرس 1: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5278, 2139, 2, 'Lesson 1: Foundations and Core Concepts', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5279, 2140, 1, 'الدرس 2: الأساسيات وبناء المفاهيم', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5280, 2140, 2, 'Lesson 2: Foundations and Core Concepts', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5281, 2141, 1, 'الدرس 1: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5282, 2141, 2, 'Lesson 1: Application and Problem Solving', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5283, 2142, 1, 'الدرس 2: التطبيق وحل المسائل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5284, 2142, 2, 'Lesson 2: Application and Problem Solving', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5285, 2143, 1, 'الدرس 1: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5286, 2143, 2, 'Lesson 1: Revision and Comprehensive Practice', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5287, 2144, 1, 'الدرس 2: المراجعة والاختبار الشامل', 'شرح فيديو تطبيقي ضمن وحدة العلوم المتكاملة.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00'),
(5288, 2144, 2, 'Lesson 2: Revision and Comprehensive Practice', 'Practical video lesson in the Integrated Science unit.', 1, '2026-07-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `video_teachers`
--

CREATE TABLE `video_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `video_teachers`
--

INSERT INTO `video_teachers` (`id`, `video_id`, `teacher_id`, `is_active`, `created_at`) VALUES
(1001, 2001, 201, 1, '2026-07-01 14:00:00'),
(1002, 2002, 201, 1, '2026-07-01 14:00:00'),
(1003, 2003, 201, 1, '2026-07-01 14:00:00'),
(1004, 2004, 201, 1, '2026-07-01 14:00:00'),
(1005, 2005, 201, 1, '2026-07-01 14:00:00'),
(1006, 2006, 201, 1, '2026-07-01 14:00:00'),
(1007, 2007, 201, 1, '2026-07-01 14:00:00'),
(1008, 2008, 201, 1, '2026-07-01 14:00:00'),
(1009, 2009, 201, 1, '2026-07-01 14:00:00'),
(1010, 2010, 201, 1, '2026-07-01 14:00:00'),
(1011, 2011, 201, 1, '2026-07-01 14:00:00'),
(1012, 2012, 201, 1, '2026-07-01 14:00:00'),
(1013, 2013, 202, 1, '2026-07-01 14:00:00'),
(1014, 2014, 202, 1, '2026-07-01 14:00:00'),
(1015, 2015, 202, 1, '2026-07-01 14:00:00'),
(1016, 2016, 202, 1, '2026-07-01 14:00:00'),
(1017, 2017, 202, 1, '2026-07-01 14:00:00'),
(1018, 2018, 202, 1, '2026-07-01 14:00:00'),
(1019, 2019, 202, 1, '2026-07-01 14:00:00'),
(1020, 2020, 202, 1, '2026-07-01 14:00:00'),
(1021, 2021, 202, 1, '2026-07-01 14:00:00'),
(1022, 2022, 202, 1, '2026-07-01 14:00:00'),
(1023, 2023, 202, 1, '2026-07-01 14:00:00'),
(1024, 2024, 202, 1, '2026-07-01 14:00:00'),
(1025, 2025, 203, 1, '2026-07-01 14:00:00'),
(1026, 2026, 203, 1, '2026-07-01 14:00:00'),
(1027, 2027, 203, 1, '2026-07-01 14:00:00'),
(1028, 2028, 203, 1, '2026-07-01 14:00:00'),
(1029, 2029, 203, 1, '2026-07-01 14:00:00'),
(1030, 2030, 203, 1, '2026-07-01 14:00:00'),
(1031, 2031, 203, 1, '2026-07-01 14:00:00'),
(1032, 2032, 203, 1, '2026-07-01 14:00:00'),
(1033, 2033, 203, 1, '2026-07-01 14:00:00'),
(1034, 2034, 203, 1, '2026-07-01 14:00:00'),
(1035, 2035, 203, 1, '2026-07-01 14:00:00'),
(1036, 2036, 203, 1, '2026-07-01 14:00:00'),
(1037, 2037, 204, 1, '2026-07-01 14:00:00'),
(1038, 2038, 204, 1, '2026-07-01 14:00:00'),
(1039, 2039, 204, 1, '2026-07-01 14:00:00'),
(1040, 2040, 204, 1, '2026-07-01 14:00:00'),
(1041, 2041, 204, 1, '2026-07-01 14:00:00'),
(1042, 2042, 204, 1, '2026-07-01 14:00:00'),
(1043, 2043, 204, 1, '2026-07-01 14:00:00'),
(1044, 2044, 204, 1, '2026-07-01 14:00:00'),
(1045, 2045, 204, 1, '2026-07-01 14:00:00'),
(1046, 2046, 204, 1, '2026-07-01 14:00:00'),
(1047, 2047, 204, 1, '2026-07-01 14:00:00'),
(1048, 2048, 204, 1, '2026-07-01 14:00:00'),
(1049, 2049, 205, 1, '2026-07-01 14:00:00'),
(1050, 2050, 205, 1, '2026-07-01 14:00:00'),
(1051, 2051, 205, 1, '2026-07-01 14:00:00'),
(1052, 2052, 205, 1, '2026-07-01 14:00:00'),
(1053, 2053, 205, 1, '2026-07-01 14:00:00'),
(1054, 2054, 205, 1, '2026-07-01 14:00:00'),
(1055, 2055, 205, 1, '2026-07-01 14:00:00'),
(1056, 2056, 205, 1, '2026-07-01 14:00:00'),
(1057, 2057, 205, 1, '2026-07-01 14:00:00'),
(1058, 2058, 205, 1, '2026-07-01 14:00:00'),
(1059, 2059, 205, 1, '2026-07-01 14:00:00'),
(1060, 2060, 205, 1, '2026-07-01 14:00:00'),
(1061, 2061, 206, 1, '2026-07-01 14:00:00'),
(1062, 2062, 206, 1, '2026-07-01 14:00:00'),
(1063, 2063, 206, 1, '2026-07-01 14:00:00'),
(1064, 2064, 206, 1, '2026-07-01 14:00:00'),
(1065, 2065, 206, 1, '2026-07-01 14:00:00'),
(1066, 2066, 206, 1, '2026-07-01 14:00:00'),
(1067, 2067, 206, 1, '2026-07-01 14:00:00'),
(1068, 2068, 206, 1, '2026-07-01 14:00:00'),
(1069, 2069, 206, 1, '2026-07-01 14:00:00'),
(1070, 2070, 206, 1, '2026-07-01 14:00:00'),
(1071, 2071, 206, 1, '2026-07-01 14:00:00'),
(1072, 2072, 206, 1, '2026-07-01 14:00:00'),
(1073, 2073, 207, 1, '2026-07-01 14:00:00'),
(1074, 2074, 207, 1, '2026-07-01 14:00:00'),
(1075, 2075, 207, 1, '2026-07-01 14:00:00'),
(1076, 2076, 207, 1, '2026-07-01 14:00:00'),
(1077, 2077, 207, 1, '2026-07-01 14:00:00'),
(1078, 2078, 207, 1, '2026-07-01 14:00:00'),
(1079, 2079, 207, 1, '2026-07-01 14:00:00'),
(1080, 2080, 207, 1, '2026-07-01 14:00:00'),
(1081, 2081, 207, 1, '2026-07-01 14:00:00'),
(1082, 2082, 207, 1, '2026-07-01 14:00:00'),
(1083, 2083, 207, 1, '2026-07-01 14:00:00'),
(1084, 2084, 207, 1, '2026-07-01 14:00:00'),
(1085, 2085, 208, 1, '2026-07-01 14:00:00'),
(1086, 2086, 208, 1, '2026-07-01 14:00:00'),
(1087, 2087, 208, 1, '2026-07-01 14:00:00'),
(1088, 2088, 208, 1, '2026-07-01 14:00:00'),
(1089, 2089, 208, 1, '2026-07-01 14:00:00'),
(1090, 2090, 208, 1, '2026-07-01 14:00:00'),
(1091, 2091, 208, 1, '2026-07-01 14:00:00'),
(1092, 2092, 208, 1, '2026-07-01 14:00:00'),
(1093, 2093, 208, 1, '2026-07-01 14:00:00'),
(1094, 2094, 208, 1, '2026-07-01 14:00:00'),
(1095, 2095, 208, 1, '2026-07-01 14:00:00'),
(1096, 2096, 208, 1, '2026-07-01 14:00:00'),
(1097, 2097, 209, 1, '2026-07-01 14:00:00'),
(1098, 2098, 209, 1, '2026-07-01 14:00:00'),
(1099, 2099, 209, 1, '2026-07-01 14:00:00'),
(1100, 2100, 209, 1, '2026-07-01 14:00:00'),
(1101, 2101, 209, 1, '2026-07-01 14:00:00'),
(1102, 2102, 209, 1, '2026-07-01 14:00:00'),
(1103, 2103, 209, 1, '2026-07-01 14:00:00'),
(1104, 2104, 209, 1, '2026-07-01 14:00:00'),
(1105, 2105, 209, 1, '2026-07-01 14:00:00'),
(1106, 2106, 209, 1, '2026-07-01 14:00:00'),
(1107, 2107, 209, 1, '2026-07-01 14:00:00'),
(1108, 2108, 209, 1, '2026-07-01 14:00:00'),
(1109, 2109, 210, 1, '2026-07-01 14:00:00'),
(1110, 2110, 210, 1, '2026-07-01 14:00:00'),
(1111, 2111, 210, 1, '2026-07-01 14:00:00'),
(1112, 2112, 210, 1, '2026-07-01 14:00:00'),
(1113, 2113, 210, 1, '2026-07-01 14:00:00'),
(1114, 2114, 210, 1, '2026-07-01 14:00:00'),
(1115, 2115, 210, 1, '2026-07-01 14:00:00'),
(1116, 2116, 210, 1, '2026-07-01 14:00:00'),
(1117, 2117, 210, 1, '2026-07-01 14:00:00'),
(1118, 2118, 210, 1, '2026-07-01 14:00:00'),
(1119, 2119, 210, 1, '2026-07-01 14:00:00'),
(1120, 2120, 210, 1, '2026-07-01 14:00:00'),
(1121, 2121, 211, 1, '2026-07-01 14:00:00'),
(1122, 2122, 211, 1, '2026-07-01 14:00:00'),
(1123, 2123, 211, 1, '2026-07-01 14:00:00'),
(1124, 2124, 211, 1, '2026-07-01 14:00:00'),
(1125, 2125, 211, 1, '2026-07-01 14:00:00'),
(1126, 2126, 211, 1, '2026-07-01 14:00:00'),
(1127, 2127, 211, 1, '2026-07-01 14:00:00'),
(1128, 2128, 211, 1, '2026-07-01 14:00:00'),
(1129, 2129, 211, 1, '2026-07-01 14:00:00'),
(1130, 2130, 211, 1, '2026-07-01 14:00:00'),
(1131, 2131, 211, 1, '2026-07-01 14:00:00'),
(1132, 2132, 211, 1, '2026-07-01 14:00:00'),
(1133, 2133, 212, 1, '2026-07-01 14:00:00'),
(1134, 2134, 212, 1, '2026-07-01 14:00:00'),
(1135, 2135, 212, 1, '2026-07-01 14:00:00'),
(1136, 2136, 212, 1, '2026-07-01 14:00:00'),
(1137, 2137, 212, 1, '2026-07-01 14:00:00'),
(1138, 2138, 212, 1, '2026-07-01 14:00:00'),
(1139, 2139, 212, 1, '2026-07-01 14:00:00'),
(1140, 2140, 212, 1, '2026-07-01 14:00:00'),
(1141, 2141, 212, 1, '2026-07-01 14:00:00'),
(1142, 2142, 212, 1, '2026-07-01 14:00:00'),
(1143, 2143, 212, 1, '2026-07-01 14:00:00'),
(1144, 2144, 212, 1, '2026-07-01 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `written_model_answers`
--

CREATE TABLE `written_model_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `written_model_answers`
--

INSERT INTO `written_model_answers` (`id`, `question_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1001, 5005, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1002, 5010, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1003, 5015, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1004, 5020, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1005, 5025, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1006, 5030, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1007, 5035, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1008, 5040, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1009, 5045, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1010, 5050, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1011, 5055, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1012, 5060, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1013, 5065, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1014, 5070, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1015, 5075, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1016, 5080, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1017, 5085, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1018, 5090, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1019, 5095, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1020, 5100, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1021, 5105, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1022, 5110, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1023, 5115, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(1024, 5120, 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `written_model_answers_translations`
--

CREATE TABLE `written_model_answers_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `written_model_answer_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` smallint(5) UNSIGNED NOT NULL,
  `model_answer` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `written_model_answers_translations`
--

INSERT INTO `written_model_answers_translations` (`id`, `written_model_answer_id`, `language_id`, `model_answer`, `is_active`, `created_at`, `updated_at`) VALUES
(13001, 1001, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13002, 1001, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13003, 1002, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13004, 1002, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13005, 1003, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13006, 1003, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13007, 1004, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13008, 1004, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13009, 1005, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13010, 1005, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13011, 1006, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13012, 1006, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13013, 1007, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13014, 1007, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13015, 1008, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13016, 1008, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13017, 1009, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13018, 1009, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13019, 1010, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13020, 1010, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13021, 1011, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13022, 1011, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13023, 1012, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13024, 1012, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13025, 1013, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13026, 1013, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13027, 1014, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13028, 1014, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13029, 1015, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13030, 1015, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13031, 1016, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13032, 1016, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13033, 1017, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13034, 1017, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13035, 1018, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13036, 1018, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13037, 1019, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13038, 1019, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13039, 1020, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13040, 1020, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13041, 1021, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13042, 1021, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13043, 1022, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13044, 1022, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13045, 1023, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13046, 1023, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13047, 1024, 1, 'إجابة نموذجية تتضمن تعريف المفهوم، شرح العلاقة الأساسية، ثم مثالًا تطبيقيًا واضحًا.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00'),
(13048, 1024, 2, 'A model answer defines the concept, explains the core relationship, and gives a clear applied example.', 1, '2026-08-01 14:00:00', '2026-08-10 16:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_academic_years_code` (`code`);

--
-- Indexes for table `academic_years_translations`
--
ALTER TABLE `academic_years_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_academic_years_translations` (`academic_year_id`,`language_id`),
  ADD KEY `idx_academic_years_translations_language_id` (`language_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admins_email` (`email`),
  ADD KEY `fk_admins_role` (`role_id`);

--
-- Indexes for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_advertisements_user` (`user_type`,`user_id`),
  ADD KEY `idx_advertisements_position` (`position`),
  ADD KEY `idx_advertisements_status` (`status`);

--
-- Indexes for table `advertisements_translations`
--
ALTER TABLE `advertisements_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_advertisements_translations` (`advertisement_id`,`language_id`),
  ADD KEY `idx_advertisements_translations_language_id` (`language_id`);

--
-- Indexes for table `affiliates`
--
ALTER TABLE `affiliates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_affiliates_code` (`affiliate_code`),
  ADD KEY `idx_affiliates_student_id` (`student_id`),
  ADD KEY `idx_affiliates_teacher_id` (`teacher_id`);

--
-- Indexes for table `affiliate_commissions`
--
ALTER TABLE `affiliate_commissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_affiliate_commissions_affiliate_id` (`affiliate_id`),
  ADD KEY `idx_affiliate_commissions_status` (`status`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_banners_is_active` (`is_active`),
  ADD KEY `idx_banners_type` (`type`);

--
-- Indexes for table `banner_views`
--
ALTER TABLE `banner_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_banner_views_banner_id` (`banner_id`),
  ADD KEY `idx_banner_views_user_id` (`user_id`),
  ADD KEY `idx_banner_views_user_type` (`user_type`);

--
-- Indexes for table `book_teachers`
--
ALTER TABLE `book_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_book_teachers` (`book_id`,`teacher_id`),
  ADD KEY `idx_bt_book` (`book_id`),
  ADD KEY `idx_bt_teacher` (`teacher_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_cert_academic_year` (`academic_year_id`),
  ADD KEY `idx_cert_subject` (`subject_id`),
  ADD KEY `idx_cert_course` (`course_id`),
  ADD KEY `idx_cert_type` (`certificate_type`),
  ADD KEY `idx_cert_code` (`code`);

--
-- Indexes for table `certificates_translations`
--
ALTER TABLE `certificates_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_certificates_lang` (`certificate_id`,`language_id`),
  ADD KEY `idx_cert_trans_certificate` (`certificate_id`);

--
-- Indexes for table `certificate_teachers`
--
ALTER TABLE `certificate_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_certificate_teachers` (`certificate_id`,`teacher_id`),
  ADD KEY `idx_ct_cert` (`certificate_id`),
  ADD KEY `idx_ct_teacher` (`teacher_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cities_code` (`code`);

--
-- Indexes for table `cities_translations`
--
ALTER TABLE `cities_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cities_translations` (`city_id`,`language_id`),
  ADD KEY `idx_cities_translations_language_id` (`language_id`);

--
-- Indexes for table `class_sessions`
--
ALTER TABLE `class_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_csn_teacher` (`teacher_id`),
  ADD KEY `idx_csn_subject` (`subject_id`),
  ADD KEY `idx_csn_course` (`course_id`),
  ADD KEY `idx_csn_day` (`day_of_week`);

--
-- Indexes for table `code_redemptions_log`
--
ALTER TABLE `code_redemptions_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crl_code` (`subscription_code_id`),
  ADD KEY `idx_crl_student` (`student_id`),
  ADD KEY `idx_crl_sub` (`student_subscription_id`);

--
-- Indexes for table `consultation_slots`
--
ALTER TABLE `consultation_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cs_teacher` (`teacher_id`),
  ADD KEY `idx_cs_student` (`student_id`),
  ADD KEY `idx_cs_date` (`slot_date`),
  ADD KEY `idx_cs_status` (`status`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_courses_slug` (`slug`),
  ADD KEY `idx_courses_category_id` (`category_id`),
  ADD KEY `idx_courses_academic_year_id` (`academic_year_id`),
  ADD KEY `idx_courses_teacher_id` (`teacher_id`);

--
-- Indexes for table `courses_translations`
--
ALTER TABLE `courses_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_courses_translations` (`course_id`,`language_id`),
  ADD KEY `idx_courses_translations_language_id` (`language_id`);

--
-- Indexes for table `course_books`
--
ALTER TABLE `course_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cb_subject` (`subject_id`),
  ADD KEY `idx_cb_course` (`course_id`),
  ADD KEY `idx_cb_unit` (`unit_id`);

--
-- Indexes for table `course_books_translations`
--
ALTER TABLE `course_books_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_book_lang` (`book_id`,`language_id`),
  ADD KEY `idx_cbt_book` (`book_id`);

--
-- Indexes for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_categories_code` (`code`);

--
-- Indexes for table `course_categories_translations`
--
ALTER TABLE `course_categories_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_categories_translations` (`course_category_id`,`language_id`),
  ADD KEY `idx_course_categories_translations_language_id` (`language_id`);

--
-- Indexes for table `course_exams`
--
ALTER TABLE `course_exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ce_subject` (`subject_id`),
  ADD KEY `idx_ce_course` (`course_id`),
  ADD KEY `idx_ce_unit` (`unit_id`);

--
-- Indexes for table `course_exams_translations`
--
ALTER TABLE `course_exams_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_lang` (`exam_id`,`language_id`),
  ADD KEY `idx_cet_exam` (`exam_id`);

--
-- Indexes for table `course_ratings`
--
ALTER TABLE `course_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_course_rating` (`student_id`,`course_id`),
  ADD KEY `idx_course_rating` (`course_id`),
  ADD KEY `idx_student_course_rating` (`student_id`);

--
-- Indexes for table `course_teachers`
--
ALTER TABLE `course_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_teachers` (`course_id`,`teacher_id`),
  ADD KEY `idx_ct_course` (`course_id`),
  ADD KEY `idx_ct_teacher` (`teacher_id`);

--
-- Indexes for table `course_videos`
--
ALTER TABLE `course_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cv_subject` (`subject_id`),
  ADD KEY `idx_cv_course` (`course_id`),
  ADD KEY `idx_cv_unit` (`unit_id`);

--
-- Indexes for table `course_videos_translations`
--
ALTER TABLE `course_videos_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_video_lang` (`video_id`,`language_id`),
  ADD KEY `idx_cvt_video` (`video_id`);

--
-- Indexes for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_verification_lookup` (`email`,`code`,`user_type`);

--
-- Indexes for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_attempt_student` (`exam_id`,`student_id`),
  ADD KEY `idx_ea_exam` (`exam_id`),
  ADD KEY `idx_ea_student` (`student_id`),
  ADD KEY `idx_ea_status` (`status`);

--
-- Indexes for table `exam_attempt_answers`
--
ALTER TABLE `exam_attempt_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `idx_eaa_attempt` (`attempt_id`),
  ADD KEY `idx_eaa_question` (`question_id`);

--
-- Indexes for table `exam_teachers`
--
ALTER TABLE `exam_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_teachers` (`exam_id`,`teacher_id`),
  ADD KEY `idx_et_exam` (`exam_id`),
  ADD KEY `idx_et_teacher` (`teacher_id`);

--
-- Indexes for table `homework`
--
ALTER TABLE `homework`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hw_teacher` (`teacher_id`),
  ADD KEY `idx_hw_subject` (`subject_id`);

--
-- Indexes for table `homework_submissions`
--
ALTER TABLE `homework_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_hw_student` (`homework_id`,`student_id`),
  ADD KEY `idx_hws_homework` (`homework_id`),
  ADD KEY `idx_hws_student` (`student_id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_languages_code` (`code`),
  ADD KEY `idx_languages_sort_order` (`sort_order`);

--
-- Indexes for table `languages_translations`
--
ALTER TABLE `languages_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_languages_translations` (`translated_language_id`,`language_id`),
  ADD KEY `idx_languages_translations_language_id` (`language_id`);

--
-- Indexes for table `mcq_options`
--
ALTER TABLE `mcq_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mcq_options_question_id` (`question_id`);

--
-- Indexes for table `mcq_options_translations`
--
ALTER TABLE `mcq_options_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mcq_options_translations` (`mcq_option_id`,`language_id`),
  ADD KEY `idx_mcq_options_translations_language_id` (`language_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_parents_email` (`email`),
  ADD KEY `idx_parents_city_id` (`city_id`);

--
-- Indexes for table `parent_students`
--
ALTER TABLE `parent_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_parent_students` (`parent_id`,`student_id`),
  ADD KEY `idx_parent_students_student_id` (`student_id`);

--
-- Indexes for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_lookup` (`email`,`code`,`user_type`,`is_used`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pt_student` (`student_id`),
  ADD KEY `idx_pt_status` (`status`),
  ADD KEY `idx_pt_ref` (`easykash_ref`),
  ADD KEY `idx_pt_item` (`item_type`,`item_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_code` (`code`);

--
-- Indexes for table `permissions_translations`
--
ALTER TABLE `permissions_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_translations` (`permission_id`,`language_id`),
  ADD KEY `idx_permissions_translations_language` (`language_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_exam_id` (`exam_id`),
  ADD KEY `idx_questions_course_id` (`course_id`),
  ADD KEY `idx_questions_unit_id` (`unit_id`),
  ADD KEY `idx_questions_type` (`question_type`);

--
-- Indexes for table `questions_translations`
--
ALTER TABLE `questions_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_questions_translations` (`question_id`,`language_id`),
  ADD KEY `idx_questions_translations_language_id` (`language_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_resources_code` (`code`),
  ADD KEY `idx_resources_course_id` (`course_id`),
  ADD KEY `idx_resources_unit_id` (`unit_id`),
  ADD KEY `idx_resources_type` (`resource_type`);

--
-- Indexes for table `resources_translations`
--
ALTER TABLE `resources_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_resources_translations` (`resource_id`,`language_id`),
  ADD KEY `idx_resources_translations_language_id` (`language_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_code` (`code`);

--
-- Indexes for table `roles_translations`
--
ALTER TABLE `roles_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_translations` (`role_id`,`language_id`),
  ADD KEY `idx_roles_translations_language` (`language_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_permissions` (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_schools_code` (`code`),
  ADD KEY `idx_schools_school_type_id` (`school_type_id`),
  ADD KEY `idx_schools_city_id` (`city_id`);

--
-- Indexes for table `schools_translations`
--
ALTER TABLE `schools_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_schools_translations` (`school_id`,`language_id`),
  ADD KEY `idx_schools_translations_language_id` (`language_id`);

--
-- Indexes for table `school_types`
--
ALTER TABLE `school_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_school_types_code` (`code`);

--
-- Indexes for table `school_types_translations`
--
ALTER TABLE `school_types_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_school_types_translations` (`school_type_id`,`language_id`),
  ADD KEY `idx_school_types_translations_language_id` (`language_id`);

--
-- Indexes for table `session_attendance`
--
ALTER TABLE `session_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sess_att` (`session_id`,`student_id`,`session_date`),
  ADD KEY `idx_sa_session` (`session_id`),
  ADD KEY `idx_sa_student` (`student_id`),
  ADD KEY `idx_sa_date` (`session_date`);

--
-- Indexes for table `session_files`
--
ALTER TABLE `session_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_files_session_id` (`session_id`),
  ADD KEY `idx_session_files_file_type` (`file_type`),
  ADD KEY `idx_session_files_uploader` (`uploaded_by_type`,`uploaded_by_id`);

--
-- Indexes for table `session_file_submissions`
--
ALTER TABLE `session_file_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_submission_student_file` (`session_file_id`,`student_id`),
  ADD KEY `idx_submissions_student` (`student_id`),
  ADD KEY `idx_submissions_file` (`session_file_id`),
  ADD KEY `idx_submissions_status` (`status`);

--
-- Indexes for table `session_reschedule_requests`
--
ALTER TABLE `session_reschedule_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_srr_session` (`session_id`),
  ADD KEY `idx_srr_teacher` (`teacher_id`),
  ADD KEY `idx_srr_status` (`status`),
  ADD KEY `idx_srr_requested_by` (`requested_by_type`,`requested_by_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key_unique` (`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_students_email` (`email`),
  ADD KEY `idx_students_school_id` (`school_id`),
  ADD KEY `idx_students_city_id` (`city_id`);

--
-- Indexes for table `student_book_purchase_requests`
--
ALTER TABLE `student_book_purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `student_book_subscriptions`
--
ALTER TABLE `student_book_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `student_certificates`
--
ALTER TABLE `student_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD KEY `idx_sc_cert` (`certificate_id`),
  ADD KEY `idx_sc_student` (`student_id`),
  ADD KEY `idx_sc_course` (`course_id`),
  ADD KEY `idx_sc_exam` (`exam_id`);

--
-- Indexes for table `student_question_answers`
--
ALTER TABLE `student_question_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sqa_student` (`student_id`),
  ADD KEY `idx_sqa_question` (`question_id`),
  ADD KEY `idx_sqa_unit` (`unit_id`),
  ADD KEY `idx_sqa_course` (`course_id`),
  ADD KEY `idx_sqa_student_question` (`student_id`,`question_id`);

--
-- Indexes for table `student_reports`
--
ALTER TABLE `student_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sr_teacher` (`teacher_id`),
  ADD KEY `idx_sr_student` (`student_id`),
  ADD KEY `idx_sr_admin` (`admin_id`),
  ADD KEY `idx_sr_subject` (`subject_id`),
  ADD KEY `idx_sr_report_type` (`report_type`),
  ADD KEY `idx_sr_date` (`report_date`);

--
-- Indexes for table `student_subscriptions`
--
ALTER TABLE `student_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ss_student` (`student_id`),
  ADD KEY `idx_ss_code` (`subscription_code_id`),
  ADD KEY `idx_ss_active` (`is_active`),
  ADD KEY `idx_ss_end_date` (`end_date`);

--
-- Indexes for table `student_subscription_books`
--
ALTER TABLE `student_subscription_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_subscription_id` (`student_subscription_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `student_subscription_courses`
--
ALTER TABLE `student_subscription_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ss_crs` (`student_subscription_id`,`course_id`),
  ADD KEY `idx_ssc_sub` (`student_subscription_id`),
  ADD KEY `idx_ssc_crs` (`course_id`);

--
-- Indexes for table `student_subscription_subjects`
--
ALTER TABLE `student_subscription_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ss_subj` (`student_subscription_id`,`subject_id`),
  ADD KEY `idx_sss_sub` (`student_subscription_id`),
  ADD KEY `idx_sss_subj` (`subject_id`);

--
-- Indexes for table `student_subscription_teachers`
--
ALTER TABLE `student_subscription_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ss_teach` (`student_subscription_id`,`teacher_id`),
  ADD KEY `idx_sst_sub` (`student_subscription_id`),
  ADD KEY `idx_sst_teach` (`teacher_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subjects_code` (`code`);

--
-- Indexes for table `subjects_translations`
--
ALTER TABLE `subjects_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subjects_translations` (`subject_id`,`language_id`),
  ADD KEY `idx_subjects_translations_language_id` (`language_id`);

--
-- Indexes for table `subject_academic_years`
--
ALTER TABLE `subject_academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subject_academic_year` (`subject_id`,`academic_year_id`),
  ADD KEY `idx_say_subject` (`subject_id`),
  ADD KEY `idx_say_academic_year` (`academic_year_id`);

--
-- Indexes for table `subscription_codes`
--
ALTER TABLE `subscription_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_sc_code` (`code`),
  ADD KEY `idx_sc_active` (`is_active`),
  ADD KEY `idx_sc_valid_to` (`valid_to`);

--
-- Indexes for table `subscription_code_courses`
--
ALTER TABLE `subscription_code_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sc_crs` (`subscription_code_id`,`course_id`),
  ADD KEY `idx_scc_code` (`subscription_code_id`),
  ADD KEY `idx_scc_crs` (`course_id`);

--
-- Indexes for table `subscription_code_subjects`
--
ALTER TABLE `subscription_code_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sc_subj` (`subscription_code_id`,`subject_id`),
  ADD KEY `idx_scs_code` (`subscription_code_id`),
  ADD KEY `idx_scs_subj` (`subject_id`);

--
-- Indexes for table `subscription_code_teachers`
--
ALTER TABLE `subscription_code_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sc_teach` (`subscription_code_id`,`teacher_id`),
  ADD KEY `idx_sct_code` (`subscription_code_id`),
  ADD KEY `idx_sct_teach` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teachers_email` (`email`),
  ADD KEY `idx_teachers_subject_id` (`subject_id`),
  ADD KEY `idx_teachers_city_id` (`city_id`);

--
-- Indexes for table `teacher_ratings`
--
ALTER TABLE `teacher_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_teacher_rating` (`student_id`,`teacher_id`),
  ADD KEY `idx_teacher_rating` (`teacher_id`),
  ADD KEY `idx_student_teacher_rating` (`student_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_subjects` (`teacher_id`,`subject_id`),
  ADD KEY `idx_teacher_subjects_subject` (`subject_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_transactions_code` (`transaction_code`),
  ADD UNIQUE KEY `uq_transactions_provider_reference` (`provider_reference`),
  ADD KEY `idx_transactions_student_id` (`student_id`),
  ADD KEY `idx_transactions_parent_id` (`parent_id`),
  ADD KEY `idx_transactions_teacher_id` (`teacher_id`),
  ADD KEY `idx_transactions_related_id` (`related_transaction_id`),
  ADD KEY `idx_transactions_status` (`status`),
  ADD KEY `idx_transactions_type` (`transaction_type`),
  ADD KEY `idx_transactions_created_at` (`created_at`);

--
-- Indexes for table `transactions_translations`
--
ALTER TABLE `transactions_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_transactions_translations` (`transaction_id`,`language_id`),
  ADD KEY `idx_transactions_translations_language_id` (`language_id`);

--
-- Indexes for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_items_transaction_id` (`transaction_id`),
  ADD KEY `idx_transaction_items_reference` (`item_type`,`item_id`);

--
-- Indexes for table `transaction_items_translations`
--
ALTER TABLE `transaction_items_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_transaction_items_translations` (`transaction_item_id`,`language_id`),
  ADD KEY `idx_transaction_items_translations_language_id` (`language_id`);

--
-- Indexes for table `true_false_answers`
--
ALTER TABLE `true_false_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_true_false_answers_question_id` (`question_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_units_course_code` (`course_id`,`code`),
  ADD KEY `idx_units_course_id` (`course_id`);

--
-- Indexes for table `units_translations`
--
ALTER TABLE `units_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_units_translations` (`unit_id`,`language_id`),
  ADD KEY `idx_units_translations_language_id` (`language_id`);

--
-- Indexes for table `unit_teachers`
--
ALTER TABLE `unit_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_unit_teachers` (`unit_id`,`teacher_id`),
  ADD KEY `idx_ut_unit` (`unit_id`),
  ADD KEY `idx_ut_teacher` (`teacher_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_videos_unit_code` (`unit_id`,`code`),
  ADD KEY `idx_videos_unit_id` (`unit_id`),
  ADD KEY `idx_videos_publish_status` (`publish_status`);

--
-- Indexes for table `videos_translations`
--
ALTER TABLE `videos_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_videos_translations` (`video_id`,`language_id`),
  ADD KEY `idx_videos_translations_language_id` (`language_id`);

--
-- Indexes for table `video_teachers`
--
ALTER TABLE `video_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_video_teachers` (`video_id`,`teacher_id`),
  ADD KEY `idx_vt_video` (`video_id`),
  ADD KEY `idx_vt_teacher` (`teacher_id`);

--
-- Indexes for table `written_model_answers`
--
ALTER TABLE `written_model_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_written_model_answers_question_id` (`question_id`);

--
-- Indexes for table `written_model_answers_translations`
--
ALTER TABLE `written_model_answers_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_written_model_answers_translations` (`written_model_answer_id`,`language_id`),
  ADD KEY `idx_written_model_answers_translations_language_id` (`language_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `academic_years_translations`
--
ALTER TABLE `academic_years_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `advertisements`
--
ALTER TABLE `advertisements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `advertisements_translations`
--
ALTER TABLE `advertisements_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1013;

--
-- AUTO_INCREMENT for table `affiliates`
--
ALTER TABLE `affiliates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `affiliate_commissions`
--
ALTER TABLE `affiliate_commissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `banner_views`
--
ALTER TABLE `banner_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1121;

--
-- AUTO_INCREMENT for table `book_teachers`
--
ALTER TABLE `book_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1025;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `certificates_translations`
--
ALTER TABLE `certificates_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1025;

--
-- AUTO_INCREMENT for table `certificate_teachers`
--
ALTER TABLE `certificate_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1013;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cities_translations`
--
ALTER TABLE `cities_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `class_sessions`
--
ALTER TABLE `class_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `code_redemptions_log`
--
ALTER TABLE `code_redemptions_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1062;

--
-- AUTO_INCREMENT for table `consultation_slots`
--
ALTER TABLE `consultation_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT for table `courses_translations`
--
ALTER TABLE `courses_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1065;

--
-- AUTO_INCREMENT for table `course_books`
--
ALTER TABLE `course_books`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=432;

--
-- AUTO_INCREMENT for table `course_books_translations`
--
ALTER TABLE `course_books_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9063;

--
-- AUTO_INCREMENT for table `course_categories`
--
ALTER TABLE `course_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `course_categories_translations`
--
ALTER TABLE `course_categories_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `course_exams`
--
ALTER TABLE `course_exams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=625;

--
-- AUTO_INCREMENT for table `course_exams_translations`
--
ALTER TABLE `course_exams_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11049;

--
-- AUTO_INCREMENT for table `course_ratings`
--
ALTER TABLE `course_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1068;

--
-- AUTO_INCREMENT for table `course_teachers`
--
ALTER TABLE `course_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=526;

--
-- AUTO_INCREMENT for table `course_videos`
--
ALTER TABLE `course_videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3145;

--
-- AUTO_INCREMENT for table `course_videos_translations`
--
ALTER TABLE `course_videos_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7289;

--
-- AUTO_INCREMENT for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7063;

--
-- AUTO_INCREMENT for table `exam_attempt_answers`
--
ALTER TABLE `exam_attempt_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15311;

--
-- AUTO_INCREMENT for table `exam_teachers`
--
ALTER TABLE `exam_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1025;

--
-- AUTO_INCREMENT for table `homework`
--
ALTER TABLE `homework`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `homework_submissions`
--
ALTER TABLE `homework_submissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `languages_translations`
--
ALTER TABLE `languages_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mcq_options`
--
ALTER TABLE `mcq_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10289;

--
-- AUTO_INCREMENT for table `mcq_options_translations`
--
ALTER TABLE `mcq_options_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20577;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=321;

--
-- AUTO_INCREMENT for table `parent_students`
--
ALTER TABLE `parent_students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1061;

--
-- AUTO_INCREMENT for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `permissions_translations`
--
ALTER TABLE `permissions_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5121;

--
-- AUTO_INCREMENT for table `questions_translations`
--
ALTER TABLE `questions_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12241;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=549;

--
-- AUTO_INCREMENT for table `resources_translations`
--
ALTER TABLE `resources_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10097;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles_translations`
--
ALTER TABLE `roles_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `schools_translations`
--
ALTER TABLE `schools_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `school_types`
--
ALTER TABLE `school_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `school_types_translations`
--
ALTER TABLE `school_types_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `session_attendance`
--
ALTER TABLE `session_attendance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1195;

--
-- AUTO_INCREMENT for table `session_files`
--
ALTER TABLE `session_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `session_file_submissions`
--
ALTER TABLE `session_file_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `session_reschedule_requests`
--
ALTER TABLE `session_reschedule_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4343;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1061;

--
-- AUTO_INCREMENT for table `student_book_purchase_requests`
--
ALTER TABLE `student_book_purchase_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `student_book_subscriptions`
--
ALTER TABLE `student_book_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_certificates`
--
ALTER TABLE `student_certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `student_question_answers`
--
ALTER TABLE `student_question_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20121;

--
-- AUTO_INCREMENT for table `student_reports`
--
ALTER TABLE `student_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `student_subscriptions`
--
ALTER TABLE `student_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1067;

--
-- AUTO_INCREMENT for table `student_subscription_books`
--
ALTER TABLE `student_subscription_books`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_subscription_courses`
--
ALTER TABLE `student_subscription_courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1063;

--
-- AUTO_INCREMENT for table `student_subscription_subjects`
--
ALTER TABLE `student_subscription_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1040;

--
-- AUTO_INCREMENT for table `student_subscription_teachers`
--
ALTER TABLE `student_subscription_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1038;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `subjects_translations`
--
ALTER TABLE `subjects_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `subject_academic_years`
--
ALTER TABLE `subject_academic_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `subscription_codes`
--
ALTER TABLE `subscription_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `subscription_code_courses`
--
ALTER TABLE `subscription_code_courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1025;

--
-- AUTO_INCREMENT for table `subscription_code_subjects`
--
ALTER TABLE `subscription_code_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1013;

--
-- AUTO_INCREMENT for table `subscription_code_teachers`
--
ALTER TABLE `subscription_code_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1013;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

--
-- AUTO_INCREMENT for table `teacher_ratings`
--
ALTER TABLE `teacher_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1061;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=512;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9061;

--
-- AUTO_INCREMENT for table `transactions_translations`
--
ALTER TABLE `transactions_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14121;

--
-- AUTO_INCREMENT for table `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10061;

--
-- AUTO_INCREMENT for table `transaction_items_translations`
--
ALTER TABLE `transaction_items_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15121;

--
-- AUTO_INCREMENT for table `true_false_answers`
--
ALTER TABLE `true_false_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1025;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1073;

--
-- AUTO_INCREMENT for table `units_translations`
--
ALTER TABLE `units_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3145;

--
-- AUTO_INCREMENT for table `unit_teachers`
--
ALTER TABLE `unit_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1073;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2145;

--
-- AUTO_INCREMENT for table `videos_translations`
--
ALTER TABLE `videos_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5289;

--
-- AUTO_INCREMENT for table `video_teachers`
--
ALTER TABLE `video_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1145;

--
-- AUTO_INCREMENT for table `written_model_answers`
--
ALTER TABLE `written_model_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1025;

--
-- AUTO_INCREMENT for table `written_model_answers_translations`
--
ALTER TABLE `written_model_answers_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13049;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_years_translations`
--
ALTER TABLE `academic_years_translations`
  ADD CONSTRAINT `fk_academic_years_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_academic_years_translations_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `advertisements_translations`
--
ALTER TABLE `advertisements_translations`
  ADD CONSTRAINT `fk_advertisements_translations_advertisement` FOREIGN KEY (`advertisement_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_advertisements_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `affiliates`
--
ALTER TABLE `affiliates`
  ADD CONSTRAINT `fk_affiliates_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_affiliates_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `affiliate_commissions`
--
ALTER TABLE `affiliate_commissions`
  ADD CONSTRAINT `fk_affiliate_commissions_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `banner_views`
--
ALTER TABLE `banner_views`
  ADD CONSTRAINT `fk_banner_views_banner` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cities_translations`
--
ALTER TABLE `cities_translations`
  ADD CONSTRAINT `fk_cities_translations_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cities_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_courses_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `courses_translations`
--
ALTER TABLE `courses_translations`
  ADD CONSTRAINT `fk_courses_translations_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_courses_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_categories_translations`
--
ALTER TABLE `course_categories_translations`
  ADD CONSTRAINT `fk_course_categories_translations_category` FOREIGN KEY (`course_category_id`) REFERENCES `course_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_course_categories_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_teachers`
--
ALTER TABLE `course_teachers`
  ADD CONSTRAINT `fk_ct_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ct_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD CONSTRAINT `fk_exam_attempts_exam` FOREIGN KEY (`exam_id`) REFERENCES `course_exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_attempts_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_attempt_answers`
--
ALTER TABLE `exam_attempt_answers`
  ADD CONSTRAINT `fk_exam_attempt_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_attempt_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `languages_translations`
--
ALTER TABLE `languages_translations`
  ADD CONSTRAINT `fk_languages_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_languages_translations_translated_language` FOREIGN KEY (`translated_language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mcq_options`
--
ALTER TABLE `mcq_options`
  ADD CONSTRAINT `fk_mcq_options_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mcq_options_translations`
--
ALTER TABLE `mcq_options_translations`
  ADD CONSTRAINT `fk_mcq_options_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mcq_options_translations_option` FOREIGN KEY (`mcq_option_id`) REFERENCES `mcq_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `fk_parents_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `parent_students`
--
ALTER TABLE `parent_students`
  ADD CONSTRAINT `fk_parent_students_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_parent_students_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `permissions_translations`
--
ALTER TABLE `permissions_translations`
  ADD CONSTRAINT `fk_permissions_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_permissions_translations_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_questions_course_exam` FOREIGN KEY (`exam_id`) REFERENCES `course_exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_questions_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `questions_translations`
--
ALTER TABLE `questions_translations`
  ADD CONSTRAINT `fk_questions_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_questions_translations_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `fk_resources_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resources_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `resources_translations`
--
ALTER TABLE `resources_translations`
  ADD CONSTRAINT `fk_resources_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resources_translations_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `roles_translations`
--
ALTER TABLE `roles_translations`
  ADD CONSTRAINT `fk_roles_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_roles_translations_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `schools`
--
ALTER TABLE `schools`
  ADD CONSTRAINT `fk_schools_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schools_school_type` FOREIGN KEY (`school_type_id`) REFERENCES `school_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `schools_translations`
--
ALTER TABLE `schools_translations`
  ADD CONSTRAINT `fk_schools_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schools_translations_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `school_types_translations`
--
ALTER TABLE `school_types_translations`
  ADD CONSTRAINT `fk_school_types_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_school_types_translations_school_type` FOREIGN KEY (`school_type_id`) REFERENCES `school_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_students_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `subjects_translations`
--
ALTER TABLE `subjects_translations`
  ADD CONSTRAINT `fk_subjects_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subjects_translations_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subject_academic_years`
--
ALTER TABLE `subject_academic_years`
  ADD CONSTRAINT `fk_say_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_say_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teachers_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_teachers_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `fk_teacher_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_teacher_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_related` FOREIGN KEY (`related_transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transactions_translations`
--
ALTER TABLE `transactions_translations`
  ADD CONSTRAINT `fk_transactions_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_translations_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `fk_transaction_items_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaction_items_translations`
--
ALTER TABLE `transaction_items_translations`
  ADD CONSTRAINT `fk_transaction_items_translations_item` FOREIGN KEY (`transaction_item_id`) REFERENCES `transaction_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_items_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `true_false_answers`
--
ALTER TABLE `true_false_answers`
  ADD CONSTRAINT `fk_true_false_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `fk_units_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `units_translations`
--
ALTER TABLE `units_translations`
  ADD CONSTRAINT `fk_units_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_units_translations_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `fk_videos_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `videos_translations`
--
ALTER TABLE `videos_translations`
  ADD CONSTRAINT `fk_videos_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_videos_translations_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `written_model_answers`
--
ALTER TABLE `written_model_answers`
  ADD CONSTRAINT `fk_written_model_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `written_model_answers_translations`
--
ALTER TABLE `written_model_answers_translations`
  ADD CONSTRAINT `fk_written_model_answers_translations_answer` FOREIGN KEY (`written_model_answer_id`) REFERENCES `written_model_answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_written_model_answers_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
