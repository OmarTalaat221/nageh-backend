<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/notifications.php';
require_once __DIR__ . '/../../../../helpers/points.php';

if (!function_exists('ensure_affiliate_commissions_extended_columns')) {
    function ensure_affiliate_commissions_extended_columns($con): void {
        static $done = false;
        if ($done || !$con) return;
        $done = true;

        $chk1 = @mysqli_query($con, "SHOW COLUMNS FROM affiliate_commissions LIKE 'referred_student_id'");
        if ($chk1 && mysqli_num_rows($chk1) === 0) {
            @mysqli_query($con, "ALTER TABLE affiliate_commissions ADD COLUMN `referred_student_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `affiliate_id`");
        }
        $chk2 = @mysqli_query($con, "SHOW COLUMNS FROM affiliate_commissions LIKE 'item_type'");
        if ($chk2 && mysqli_num_rows($chk2) === 0) {
            @mysqli_query($con, "ALTER TABLE affiliate_commissions ADD COLUMN `item_type` enum('course','book','subscription_code','other') DEFAULT 'subscription_code' AFTER `referred_student_id`");
        } else {
            @mysqli_query($con, "ALTER TABLE affiliate_commissions MODIFY COLUMN `item_type` enum('course','book','subscription_code','other') DEFAULT 'subscription_code'");
        }
        $chk3 = @mysqli_query($con, "SHOW COLUMNS FROM affiliate_commissions LIKE 'item_id'");
        if ($chk3 && mysqli_num_rows($chk3) === 0) {
            @mysqli_query($con, "ALTER TABLE affiliate_commissions ADD COLUMN `item_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `item_type`");
        }
        $chk4 = @mysqli_query($con, "SHOW COLUMNS FROM affiliate_commissions LIKE 'purchase_amount'");
        if ($chk4 && mysqli_num_rows($chk4) === 0) {
            @mysqli_query($con, "ALTER TABLE affiliate_commissions ADD COLUMN `purchase_amount` decimal(10,2) DEFAULT 0.00 AFTER `item_id`");
        }
        $chk5 = @mysqli_query($con, "SHOW COLUMNS FROM affiliate_commissions LIKE 'notes'");
        if ($chk5 && mysqli_num_rows($chk5) === 0) {
            @mysqli_query($con, "ALTER TABLE affiliate_commissions ADD COLUMN `notes` text DEFAULT NULL AFTER `status`");
        }
        $chk6 = @mysqli_query($con, "SHOW COLUMNS FROM affiliate_commissions LIKE 'approved_by_admin_id'");
        if ($chk6 && mysqli_num_rows($chk6) === 0) {
            @mysqli_query($con, "ALTER TABLE affiliate_commissions ADD COLUMN `approved_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `notes`");
        }

        $chkS1 = @mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'affiliate_code'");
        if ($chkS1 && mysqli_num_rows($chkS1) === 0) {
            @mysqli_query($con, "ALTER TABLE students ADD COLUMN `affiliate_code` varchar(50) DEFAULT NULL");
        }
        $chkS2 = @mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'points_balance'");
        if ($chkS2 && mysqli_num_rows($chkS2) === 0) {
            @mysqli_query($con, "ALTER TABLE students ADD COLUMN `points_balance` decimal(10,2) DEFAULT 0.00");
        }
        $chkS3 = @mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'academic_year_id'");
        if ($chkS3 && mysqli_num_rows($chkS3) === 0) {
            @mysqli_query($con, "ALTER TABLE students ADD COLUMN `academic_year_id` bigint(20) UNSIGNED DEFAULT NULL");
        }
        $chkS4 = @mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'referred_by_student_id'");
        if ($chkS4 && mysqli_num_rows($chkS4) === 0) {
            @mysqli_query($con, "ALTER TABLE students ADD COLUMN `referred_by_student_id` bigint(20) UNSIGNED DEFAULT NULL");
        }
    }
}

if (!function_exists('format_affiliate_item_details')) {
    function format_affiliate_item_details($con, string $itemType, int $itemId): array {
        $itemTitle = '';
        $codeDetails = null;
        $includedCourses = [];

        if (($itemType === 'subscription_code' || $itemType === 'other') && $itemId > 0) {
            $codeStmt = mysqli_prepare($con, "SELECT id, code, title, price FROM subscription_codes WHERE id = ? LIMIT 1");
            if ($codeStmt) {
                mysqli_stmt_bind_param($codeStmt, "i", $itemId);
                mysqli_stmt_execute($codeStmt);
                $codeRes = mysqli_fetch_assoc(mysqli_stmt_get_result($codeStmt));
                if ($codeRes) {
                    $codeDetails = [
                        'id' => (int)$codeRes['id'],
                        'code' => $codeRes['code'],
                        'title' => $codeRes['title'],
                        'price' => (float)$codeRes['price']
                    ];
                    $itemTitle = 'كود اشتراك: ' . (!empty($codeRes['title']) ? $codeRes['title'] : $codeRes['code']);
                }
            }

            // Get included courses for this subscription code (linked via courses, subjects, or teachers)
            $queryCourses = "SELECT DISTINCT c.id, 
                                COALESCE(
                                    (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = c.id AND ct.language_id = 1 LIMIT 1),
                                    (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = c.id LIMIT 1),
                                    c.slug,
                                    CONCAT('كورس #', c.id)
                                ) AS name, 
                                c.price 
                             FROM courses c 
                             WHERE c.id IN (SELECT course_id FROM subscription_code_courses WHERE subscription_code_id = ?)
                                OR c.subject_id IN (SELECT subject_id FROM subscription_code_subjects WHERE subscription_code_id = ?)
                                OR c.teacher_id IN (SELECT teacher_id FROM subscription_code_teachers WHERE subscription_code_id = ?)
                                OR c.id IN (SELECT ct.course_id FROM course_teachers ct JOIN subscription_code_teachers sct ON ct.teacher_id = sct.teacher_id WHERE sct.subscription_code_id = ?)";
            $sccStmt = mysqli_prepare($con, $queryCourses);
            if ($sccStmt) {
                mysqli_stmt_bind_param($sccStmt, "iiii", $itemId, $itemId, $itemId, $itemId);
                mysqli_stmt_execute($sccStmt);
                $sccRes = mysqli_stmt_get_result($sccStmt);
                while ($cRow = mysqli_fetch_assoc($sccRes)) {
                    $includedCourses[] = [
                        'id' => (int)$cRow['id'],
                        'name' => $cRow['name'],
                        'price' => (float)$cRow['price']
                    ];
                }
            }

            if (empty($itemTitle)) {
                $itemTitle = 'كود اشتراك #' . $itemId;
            }
        } elseif ($itemType === 'course' && $itemId > 0) {
            $cStmt = mysqli_prepare($con, "SELECT ct.name FROM courses c LEFT JOIN courses_translations ct ON c.id = ct.course_id AND ct.language_id = 1 WHERE c.id = ? LIMIT 1");
            if ($cStmt) {
                mysqli_stmt_bind_param($cStmt, "i", $itemId);
                mysqli_stmt_execute($cStmt);
                $cRes = mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt));
                $itemTitle = $cRes['name'] ?? ('كورس #' . $itemId);
            }
        } elseif ($itemType === 'book' && $itemId > 0) {
            $bStmt = mysqli_prepare($con, "SELECT title FROM course_books WHERE id = ? LIMIT 1");
            if ($bStmt) {
                mysqli_stmt_bind_param($bStmt, "i", $itemId);
                mysqli_stmt_execute($bStmt);
                $bRes = mysqli_fetch_assoc(mysqli_stmt_get_result($bStmt));
                $itemTitle = $bRes['title'] ?? ('كتاب #' . $itemId);
            }
        }

        return [
            'item_title' => $itemTitle,
            'code_details' => $codeDetails,
            'included_courses' => $includedCourses
        ];
    }
}

if (!function_exists('generateStudentAffiliateCode')) {
    function generateStudentAffiliateCode($con, int $studentId): string {
        if ($studentId <= 0) return '';

        $code = '';
        $stmt = mysqli_prepare($con, "SELECT affiliate_code FROM students WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $studentId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            if (!empty($row['affiliate_code'])) {
                $code = $row['affiliate_code'];
            }
        }

        if (empty($code)) {
            $code = "NAJEH-STU-" . $studentId;

            // Ensure uniqueness
            $checkStmt = mysqli_prepare($con, "SELECT id FROM students WHERE affiliate_code = ? LIMIT 1");
            if ($checkStmt) {
                mysqli_stmt_bind_param($checkStmt, "s", $code);
                mysqli_stmt_execute($checkStmt);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
                    $code = "NAJEH-STU-" . $studentId . "-" . rand(100, 999);
                }
            }

            // Update student record
            $upd = mysqli_prepare($con, "UPDATE students SET affiliate_code = ? WHERE id = ?");
            if ($upd) {
                mysqli_stmt_bind_param($upd, "si", $code, $studentId);
                mysqli_stmt_execute($upd);
            }
        }

        // Ensure affiliate record ALWAYS exists in affiliates table
        $affStmt = mysqli_prepare($con, "SELECT id FROM affiliates WHERE student_id = ? LIMIT 1");
        if ($affStmt) {
            mysqli_stmt_bind_param($affStmt, "i", $studentId);
            mysqli_stmt_execute($affStmt);
            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($affStmt))) {
                $insAff = mysqli_prepare($con, "INSERT INTO affiliates (student_id, affiliate_code, commission_type, commission_value, is_active) VALUES (?, ?, 'fixed', 0.00, 1)");
                if ($insAff) {
                    mysqli_stmt_bind_param($insAff, "is", $studentId, $code);
                    mysqli_stmt_execute($insAff);
                }
            }
        }

        return $code;
    }
}

if (!function_exists('studentGetAffiliateInfo')) {
    function studentGetAffiliateInfo($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        if ($stId <= 0) {
            return [
                'status' => 'success',
                'data' => [
                    'affiliate_code' => null,
                    'share_link' => null,
                    'points_balance' => 0.00,
                    'total_points_earned' => 0.00,
                    'total_commissions_count' => 0,
                    'referred_students_count' => 0,
                    'referred_students' => [],
                    'commissions' => []
                ]
            ];
        }

        // Get student info
        $stStmt = mysqli_prepare($con, "SELECT id, name, email, affiliate_code, points_balance FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stStmt, "i", $stId);
        mysqli_stmt_execute($stStmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));

        if (!$student) {
            return ['status' => 'error', 'message' => trans('profile_not_found') ?? 'Student profile not found'];
        }

        $affiliateCode = $student['affiliate_code'];
        if (empty($affiliateCode)) {
            $affiliateCode = generateStudentAffiliateCode($con, $stId);
        } else {
            // Guarantee affiliate record exists
            generateStudentAffiliateCode($con, $stId);
        }

        $shareLink = "https://najeh-app.com/register?ref=" . urlencode($affiliateCode);

        // Get list of referred students
        $referredStudents = [];
        $refStmt = mysqli_prepare($con, "SELECT id, name, email, image, created_at FROM students WHERE referred_by_student_id = ? ORDER BY id DESC");
        if ($refStmt) {
            mysqli_stmt_bind_param($refStmt, "i", $stId);
            mysqli_stmt_execute($refStmt);
            $res = mysqli_stmt_get_result($refStmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $referredStudents[] = format_response_images($row);
            }
        }

        // Get affiliate commissions / rewards
        $commissions = [];
        $commStmt = mysqli_prepare($con, "SELECT ac.id, ac.item_type, ac.item_id, ac.purchase_amount, ac.amount AS reward_amount, ac.status, ac.notes, ac.created_at, ac.updated_at, st.name AS referred_student_name, st.email AS referred_student_email
            FROM affiliate_commissions ac
            JOIN affiliates a ON ac.affiliate_id = a.id
            LEFT JOIN students st ON ac.referred_student_id = st.id
            WHERE a.student_id = ?
            ORDER BY ac.id DESC");
        if ($commStmt) {
            mysqli_stmt_bind_param($commStmt, "i", $stId);
            mysqli_stmt_execute($commStmt);
            $res = mysqli_stmt_get_result($commStmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['item_id'] = (int)$row['item_id'];
                $row['purchase_amount'] = (float)$row['purchase_amount'];
                $row['reward_amount'] = (float)$row['reward_amount'];

                $formattedDetails = format_affiliate_item_details($con, $row['item_type'], $row['item_id']);
                $row['item_title'] = $formattedDetails['item_title'];
                $row['code_details'] = $formattedDetails['code_details'];
                $row['included_courses'] = $formattedDetails['included_courses'];

                $commissions[] = $row;
            }
        }

        return [
            'status' => 'success',
            'data' => [
                'affiliate_code' => $affiliateCode,
                'share_link' => $shareLink,
                'points_balance' => (float)$student['points_balance'],
                // What the balance is actually worth, and where it can be spent. Without this the
                // page said "80 points" and nothing else, so a student had to start a purchase
                // before discovering their points meant anything at all.
                'points_value' => student_points_value_summary($con, (float)$student['points_balance'], (int)$student['id']),
                'total_referred_students' => count($referredStudents),
                'referred_students' => $referredStudents,
                'affiliate_requests' => $commissions
            ]
        ];
    }
}

if (!function_exists('createPendingAffiliateCommission')) {
    function createPendingAffiliateCommission($con, int $purchasingStudentId, string $itemType, int $itemId, float $price): bool {
        if ($purchasingStudentId <= 0 || $price < 0) return false;

        ensure_affiliate_commissions_extended_columns($con);

        $itemType = strtolower(trim($itemType));
        if (!in_array($itemType, ['course', 'book', 'subscription_code', 'other'], true)) {
            $itemType = 'other';
        }

        // Check if purchasing student was referred by another student
        $stStmt = mysqli_prepare($con, "SELECT referred_by_student_id FROM students WHERE id = ? LIMIT 1");
        if (!$stStmt) return false;
        mysqli_stmt_bind_param($stStmt, "i", $purchasingStudentId);
        mysqli_stmt_execute($stStmt);
        $stRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));
        $referrerStudentId = (int)($stRow['referred_by_student_id'] ?? 0);

        if ($referrerStudentId <= 0 || $referrerStudentId === $purchasingStudentId) {
            return false;
        }

        // Ensure referrer student has an affiliate code and affiliate row
        $affCode = generateStudentAffiliateCode($con, $referrerStudentId);

        $affiliateId = 0;
        $affStmt = mysqli_prepare($con, "SELECT id FROM affiliates WHERE student_id = ? LIMIT 1");
        if ($affStmt) {
            mysqli_stmt_bind_param($affStmt, "i", $referrerStudentId);
            mysqli_stmt_execute($affStmt);
            $affRow = mysqli_fetch_assoc(mysqli_stmt_get_result($affStmt));
            if ($affRow) {
                $affiliateId = (int)$affRow['id'];
            }
        }

        if ($affiliateId <= 0) {
            $insAff = mysqli_prepare($con, "INSERT INTO affiliates (student_id, affiliate_code, commission_type, commission_value, is_active) VALUES (?, ?, 'fixed', 0.00, 1)");
            if ($insAff) {
                mysqli_stmt_bind_param($insAff, "is", $referrerStudentId, $affCode);
                if (mysqli_stmt_execute($insAff)) {
                    $affiliateId = mysqli_insert_id($con);
                }
            }
        }

        if ($affiliateId <= 0) return false;

        // Avoid duplicate pending commission for the exact same item purchase if already pending
        $chkStmt = mysqli_prepare($con, "SELECT id FROM affiliate_commissions WHERE affiliate_id = ? AND referred_student_id = ? AND item_type = ? AND item_id = ? AND status = 'pending' LIMIT 1");
        if ($chkStmt) {
            mysqli_stmt_bind_param($chkStmt, "iisi", $affiliateId, $purchasingStudentId, $itemType, $itemId);
            mysqli_stmt_execute($chkStmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($chkStmt))) {
                return true; // Already created
            }
        }

        // Create pending commission request for admin review
        $now = date('Y-m-d H:i:s');
        $insStmt = mysqli_prepare($con, "INSERT INTO affiliate_commissions (affiliate_id, referred_student_id, item_type, item_id, purchase_amount, amount, commission_date, status, is_active) VALUES (?, ?, ?, ?, ?, 0.00, ?, 'pending', 1)");
        if ($insStmt) {
            mysqli_stmt_bind_param($insStmt, "iisids", $affiliateId, $purchasingStudentId, $itemType, $itemId, $price, $now);
            return mysqli_stmt_execute($insStmt);
        }

        return false;
    }
}
