-- Seed sample banners for testing (uses the real `banners` schema from migration 009:
-- title_ar, title_en, image_ar, image_en, link, type, is_active - no target_type/sort_order/
-- views_count/clicks_count columns exist, those were a bug in the student-facing endpoint, fixed
-- separately in app/user/student/lookups/banners.php).
INSERT INTO `banners` (`title_ar`, `title_en`, `image_ar`, `image_en`, `link`, `type`, `is_active`) VALUES
('خصم 20% على كورسات الرياضيات', '20% off Math courses', 'https://cdn.najeh.test/banners/math-sale-ar.jpg', 'https://cdn.najeh.test/banners/math-sale-en.jpg', 'https://najeh.test/courses?subject=math', 'image', 1),
('سجل الآن في كورسات الفيزياء', 'Enroll now in Physics courses', 'https://cdn.najeh.test/banners/physics-ar.jpg', 'https://cdn.najeh.test/banners/physics-en.jpg', 'https://najeh.test/courses?subject=physics', 'image', 1),
('تعرف على منصة ناجح', 'Discover Najeh Platform', 'https://cdn.najeh.test/banners/intro-ar.jpg', 'https://cdn.najeh.test/banners/intro-en.jpg', 'https://najeh.test/about', 'video', 1),
('اشترك بكود وابدأ التعلم', 'Redeem a code and start learning', 'https://cdn.najeh.test/banners/redeem-ar.jpg', 'https://cdn.najeh.test/banners/redeem-en.jpg', 'https://najeh.test/redeem', 'image', 1),
('احجز استشارتك المجانية', 'Book your free consultation', 'https://cdn.najeh.test/banners/consult-ar.jpg', 'https://cdn.najeh.test/banners/consult-en.jpg', 'https://najeh.test/consultations', 'image', 1);
