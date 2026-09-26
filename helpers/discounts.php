<?php
require_once __DIR__ . '/entities.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/upload.php';

/**
 * Discount codes for course and package subscriptions.
 *
 * A discount code only ever reduces a price - it grants nothing. That is the whole difference
 * from `subscription_codes`, which open access outright, and the reason the two never share a
 * table or a code path.
 *
 * Validation and application are separate on purpose: a student checking a code from the course
 * page is a read and must not consume a use, while an admin granting a subscription with the code
 * is the moment it is actually spent.
 */

if (!function_exists('get_discount_code_details')) {
    function get_discount_code_details($con, int $codeId): ?array {
        $stmt = mysqli_prepare($con, "SELECT * FROM discount_codes WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $codeId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) return null;
        return discount_format_row($con, $row);
    }
}

if (!function_exists('discount_format_row')) {
    function discount_format_row($con, array $row): array {
        $row['id'] = (int)$row['id'];
        $row['discount_value'] = (float)$row['discount_value'];
        $row['max_discount_amount'] = $row['max_discount_amount'] !== null ? (float)$row['max_discount_amount'] : null;
        $row['max_uses'] = $row['max_uses'] !== null ? (int)$row['max_uses'] : null;
        $row['used_count'] = (int)$row['used_count'];
        $row['once_per_student'] = (int)$row['once_per_student'];
        $row['is_active'] = (int)$row['is_active'];
        $row['created_by_id'] = $row['created_by_id'] ? (int)$row['created_by_id'] : null;
        $row['remaining_uses'] = $row['max_uses'] !== null
            ? max(0, $row['max_uses'] - $row['used_count'])
            : null;

        $row['course_ids'] = [];
        $row['courses'] = [];
        $linkStmt = mysqli_prepare($con, "SELECT course_id FROM discount_code_courses WHERE discount_code_id = ?");
        mysqli_stmt_bind_param($linkStmt, "i", $row['id']);
        mysqli_stmt_execute($linkStmt);
        $linkRes = mysqli_stmt_get_result($linkStmt);
        while ($link = mysqli_fetch_assoc($linkRes)) {
            $courseId = (int)$link['course_id'];
            $row['course_ids'][] = $courseId;
            $details = get_course_details($con, $courseId);
            if ($details) {
                $row['courses'][] = $details;
            }
        }

        return format_response_images($row);
    }
}

if (!function_exists('discount_sync_courses')) {
    function discount_sync_courses($con, int $codeId, $courseIds): void {
        $del = mysqli_prepare($con, "DELETE FROM discount_code_courses WHERE discount_code_id = ?");
        mysqli_stmt_bind_param($del, "i", $codeId);
        mysqli_stmt_execute($del);

        if (!is_array($courseIds)) {
            $courseIds = $courseIds !== null && $courseIds !== '' ? [$courseIds] : [];
        }
        $unique = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
        if (!$unique) return;

        $ins = mysqli_prepare($con, "INSERT IGNORE INTO discount_code_courses (discount_code_id, course_id) VALUES (?, ?)");
        foreach ($unique as $courseId) {
            mysqli_stmt_bind_param($ins, "ii", $codeId, $courseId);
            mysqli_stmt_execute($ins);
        }
    }
}

if (!function_exists('discount_compute_amount')) {
    function discount_compute_amount(array $code, float $amount): array {
        if ($amount <= 0) {
            return ['discount' => 0.0, 'final' => 0.0];
        }

        if ($code['discount_type'] === 'fixed') {
            $discount = (float)$code['discount_value'];
        } else {
            $discount = $amount * ((float)$code['discount_value'] / 100);
            if ($code['max_discount_amount'] !== null && $code['max_discount_amount'] > 0) {
                $discount = min($discount, (float)$code['max_discount_amount']);
            }
        }

        // A discount can never exceed the price or turn it negative.
        $discount = max(0.0, min($discount, $amount));

        return [
            'discount' => round($discount, 2),
            'final' => round($amount - $discount, 2),
        ];
    }
}

/**
 * Checks a code against a student and a set of courses and returns what it would take off.
 * Read-only: nothing is consumed, so a student may check as often as they like.
 */
if (!function_exists('discount_validate_code')) {
    function discount_validate_code($con, string $rawCode, ?int $studentId, array $courseIds): array {
        $codeStr = strtoupper(trim($rawCode));
        if ($codeStr === '') {
            return ['valid' => false, 'message' => 'كود الخصم مطلوب'];
        }

        $stmt = mysqli_prepare($con, "SELECT * FROM discount_codes WHERE UPPER(code) = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $codeStr);
        mysqli_stmt_execute($stmt);
        $raw = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$raw) {
            return ['valid' => false, 'message' => 'كود الخصم غير صحيح'];
        }

        $code = discount_format_row($con, $raw);

        if (!$code['is_active']) {
            return ['valid' => false, 'message' => 'كود الخصم غير مفعّل'];
        }
        if (!empty($code['valid_from']) && strtotime($code['valid_from']) > time()) {
            return ['valid' => false, 'message' => 'كود الخصم لم يبدأ بعد'];
        }
        if (!empty($code['valid_to']) && strtotime($code['valid_to']) < time()) {
            return ['valid' => false, 'message' => 'انتهت صلاحية كود الخصم'];
        }
        if ($code['max_uses'] !== null && $code['used_count'] >= $code['max_uses']) {
            return ['valid' => false, 'message' => 'تم استهلاك كود الخصم بالكامل'];
        }

        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds))));

        if ($code['applies_to'] === 'selected') {
            if (!$courseIds) {
                return ['valid' => false, 'message' => 'هذا الكود يعمل على كورسات محددة، اختر الكورس أولًا'];
            }
            $allowed = array_intersect($courseIds, $code['course_ids']);
            if (!$allowed) {
                return ['valid' => false, 'message' => 'كود الخصم لا ينطبق على هذا الكورس'];
            }
            $courseIds = array_values($allowed);
        }

        if ($studentId && $code['once_per_student']) {
            $usedStmt = mysqli_prepare($con, "SELECT id FROM discount_code_redemptions WHERE discount_code_id = ? AND student_id = ? LIMIT 1");
            mysqli_stmt_bind_param($usedStmt, "ii", $code['id'], $studentId);
            mysqli_stmt_execute($usedStmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($usedStmt))) {
                return ['valid' => false, 'message' => 'هذا الطالب استخدم الكود من قبل'];
            }
        }

        $original = 0.0;
        $items = [];
        foreach ($courseIds as $courseId) {
            $course = get_course_details($con, $courseId);
            if (!$course) continue;
            $price = (float)($course['price'] ?? 0);
            $original += $price;
            $items[] = [
                'course_id' => $courseId,
                'name' => $course['name'] ?? ('#' . $courseId),
                'price' => $price,
            ];
        }

        $computed = discount_compute_amount($code, $original);

        return [
            'valid' => true,
            'message' => 'كود الخصم صالح',
            'code' => [
                'id' => $code['id'],
                'code' => $code['code'],
                'title' => $code['title'],
                'discount_type' => $code['discount_type'],
                'discount_value' => $code['discount_value'],
                'max_discount_amount' => $code['max_discount_amount'],
                'applies_to' => $code['applies_to'],
                'remaining_uses' => $code['remaining_uses'],
                'valid_to' => $code['valid_to'],
            ],
            'items' => $items,
            'original_amount' => round($original, 2),
            'discount_amount' => $computed['discount'],
            'final_amount' => $computed['final'],
            'currency' => 'USD',
        ];
    }
}

/**
 * Spends the code: writes the redemption row and bumps used_count. Call this only where the
 * discount really takes effect, never from a price preview.
 */
if (!function_exists('discount_apply_code')) {
    function discount_apply_code($con, string $rawCode, int $studentId, array $courseIds, string $byType, ?int $byId, ?int $subscriptionId = null): array {
        $check = discount_validate_code($con, $rawCode, $studentId, $courseIds);
        if (empty($check['valid'])) {
            return $check;
        }

        $codeId = (int)$check['code']['id'];
        $primaryCourseId = $check['items'][0]['course_id'] ?? null;

        $ins = mysqli_prepare($con, "
            INSERT INTO discount_code_redemptions
                (discount_code_id, student_id, course_id, student_subscription_id, original_amount, discount_amount, final_amount, applied_by_type, applied_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $ins, "iiiidddsi",
            $codeId, $studentId, $primaryCourseId, $subscriptionId,
            $check['original_amount'], $check['discount_amount'], $check['final_amount'],
            $byType, $byId
        );
        if (!mysqli_stmt_execute($ins)) {
            return ['valid' => false, 'message' => 'تعذر تسجيل استخدام كود الخصم: ' . mysqli_error($con)];
        }

        mysqli_query($con, "UPDATE discount_codes SET used_count = used_count + 1 WHERE id = {$codeId}");

        $check['redemption_id'] = mysqli_insert_id($con);
        return $check;
    }
}
