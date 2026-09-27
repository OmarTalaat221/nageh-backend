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
 * Every course a teacher may discount: the ones they are the primary teacher of, the ones they
 * created, and the ones they were added to as an active co-teacher. Same three-way rule the
 * teacher course listings use, kept in one place so a code can never reach further than the
 * courses that teacher can already see.
 */
if (!function_exists('discount_teacher_course_ids')) {
    function discount_teacher_course_ids($con, int $teacherId): array {
        if ($teacherId <= 0) return [];

        $stmt = mysqli_prepare($con, "
            SELECT DISTINCT c.id
            FROM courses c
            WHERE c.teacher_id = ?
               OR (c.created_by_type = 'teacher' AND c.created_by_id = ?)
               OR c.id IN (SELECT course_id FROM course_teachers WHERE teacher_id = ? AND is_active = 1)
        ");
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "iii", $teacherId, $teacherId, $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $ids = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ids[] = (int)$row['id'];
        }
        return $ids;
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

        // A discount only ever exists as a reduction on a price, and only a course or a package
        // carries a price. Without one there is nothing to reduce, so validating must fail here
        // rather than quietly succeed with a zero: a zero used to still be recorded as a real
        // redemption, which burnt a use of the code (and, with once_per_student, burnt it for
        // that student permanently) in exchange for no discount at all. Subject-wide and
        // teacher-wide grants reach this path with no courses, which is exactly how that happened.
        if (!$courseIds) {
            return ['valid' => false, 'message' => 'كود الخصم يحتاج كورس أو باقة - الاشتراك بمادة أو بمدرس ليس له سعر يُخصم منه'];
        }

        if ($code['applies_to'] === 'selected') {
            $allowed = array_intersect($courseIds, $code['course_ids']);
            if (!$allowed) {
                return ['valid' => false, 'message' => 'كود الخصم لا ينطبق على هذا الكورس'];
            }
            $courseIds = array_values($allowed);
        }

        // A teacher's code may only ever touch that teacher's own courses. Without this, a code
        // created by one teacher with applies_to = 'all' would discount the whole platform,
        // including other teachers' work.
        if ($code['created_by_type'] === 'teacher' && $code['created_by_id']) {
            $ownerCourseIds = discount_teacher_course_ids($con, (int)$code['created_by_id']);
            $allowed = array_values(array_intersect($courseIds, $ownerCourseIds));
            if (!$allowed) {
                return ['valid' => false, 'message' => 'كود الخصم ده خاص بكورسات المدرس صاحب الكود فقط'];
            }
            $courseIds = $allowed;
        }

        // Only a discount that was actually given counts against the student. A `pending` row is
        // the student's own claim on this same code - treating it as "already used" would make
        // the admin's grant reject the very claim it is supposed to be consuming.
        if ($studentId && $code['once_per_student']) {
            $usedStmt = mysqli_prepare($con, "SELECT id FROM discount_code_redemptions WHERE discount_code_id = ? AND student_id = ? AND status = 'applied' LIMIT 1");
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
                'created_by_type' => $code['created_by_type'],
                'created_by_id' => $code['created_by_id'],
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
 * A student pinning a code to a course they want. There is no payment gateway, so this is what
 * "using" a code means for a student: the claim is stored as `pending`, the code's quota is NOT
 * touched, and the admin's later grant is what turns it into a real discount.
 *
 * Deliberately idempotent per student+code+course: a student pressing the button twice re-reads
 * their own claim instead of stacking up rows.
 */
if (!function_exists('discount_claim_code')) {
    function discount_claim_code($con, string $rawCode, int $studentId, array $courseIds): array {
        if ($studentId <= 0) {
            return ['valid' => false, 'message' => 'يجب تسجيل الدخول لاستخدام كود الخصم'];
        }

        $check = discount_validate_code($con, $rawCode, $studentId, $courseIds);
        if (empty($check['valid'])) {
            return $check;
        }

        $codeId = (int)$check['code']['id'];
        $primaryCourseId = $check['items'][0]['course_id'] ?? null;

        $existing = mysqli_prepare($con, "
            SELECT id, course_id FROM discount_code_redemptions
            WHERE discount_code_id = ? AND student_id = ? AND status = 'pending'
            ORDER BY id DESC
        ");
        mysqli_stmt_bind_param($existing, "ii", $codeId, $studentId);
        mysqli_stmt_execute($existing);
        $existingRes = mysqli_stmt_get_result($existing);

        $found = null;
        while ($row = mysqli_fetch_assoc($existingRes)) {
            $rowCourseId = $row['course_id'] === null ? null : (int)$row['course_id'];
            if ($rowCourseId === ($primaryCourseId === null ? null : (int)$primaryCourseId)) {
                $found = $row;
                break;
            }
        }

        if ($found) {
            $claimId = (int)$found['id'];
            $upd = mysqli_prepare($con, "
                UPDATE discount_code_redemptions
                SET original_amount = ?, discount_amount = ?, final_amount = ?
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($upd, "dddi", $check['original_amount'], $check['discount_amount'], $check['final_amount'], $claimId);
            mysqli_stmt_execute($upd);
        } else {
            $ins = mysqli_prepare($con, "
                INSERT INTO discount_code_redemptions
                    (discount_code_id, student_id, course_id, original_amount, discount_amount, final_amount, status, applied_by_type, applied_by_id)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', 'student', ?)
            ");
            mysqli_stmt_bind_param(
                $ins, "iiidddi",
                $codeId, $studentId, $primaryCourseId,
                $check['original_amount'], $check['discount_amount'], $check['final_amount'],
                $studentId
            );
            if (!mysqli_stmt_execute($ins)) {
                return ['valid' => false, 'message' => 'تعذر حفظ كود الخصم: ' . mysqli_error($con)];
            }
            $claimId = mysqli_insert_id($con);
        }

        $check['claim_id'] = $claimId;
        $check['status'] = 'pending';
        $check['message'] = 'تم تطبيق كود الخصم على طلبك - سيظهر للإدارة عند تفعيل الاشتراك';
        return $check;
    }
}

/**
 * The claims a student is holding, newest first. `course_ids` narrows it to the claims that would
 * apply to a particular basket - that is how the admin grant screen finds the code a student
 * already picked, so the admin does not have to be told it over WhatsApp and retype it.
 */
if (!function_exists('discount_list_pending_claims')) {
    function discount_list_pending_claims($con, int $studentId, array $courseIds = []): array {
        if ($studentId <= 0) return [];

        $sql = "
            SELECT r.id, r.discount_code_id, r.course_id, r.original_amount, r.discount_amount,
                   r.final_amount, r.created_at, d.code, d.title, d.discount_type, d.discount_value,
                   d.is_active, d.valid_to, d.max_uses, d.used_count
            FROM discount_code_redemptions r
            JOIN discount_codes d ON d.id = r.discount_code_id
            WHERE r.student_id = ? AND r.status = 'pending'
        ";

        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
        if ($courseIds) {
            $sql .= ' AND r.course_id IN (' . implode(',', $courseIds) . ')';
        }
        $sql .= ' ORDER BY r.id DESC';

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['discount_code_id'] = (int)$row['discount_code_id'];
            $row['course_id'] = $row['course_id'] ? (int)$row['course_id'] : null;
            $row['original_amount'] = (float)$row['original_amount'];
            $row['discount_amount'] = (float)$row['discount_amount'];
            $row['final_amount'] = (float)$row['final_amount'];
            $row['is_active'] = (int)$row['is_active'];
            $row['course'] = $row['course_id'] ? get_course_details($con, $row['course_id']) : null;
            // A claim is only worth showing if the code behind it still works - a code that was
            // deactivated or expired after the claim was made must not look redeemable.
            $row['is_still_valid'] = $row['is_active'] === 1
                && (empty($row['valid_to']) || strtotime($row['valid_to']) >= time())
                && ($row['max_uses'] === null || (int)$row['used_count'] < (int)$row['max_uses']);
            $rows[] = $row;
        }

        return format_response_images($rows);
    }
}

if (!function_exists('discount_cancel_claim')) {
    function discount_cancel_claim($con, int $studentId, int $claimId): bool {
        $stmt = mysqli_prepare($con, "DELETE FROM discount_code_redemptions WHERE id = ? AND student_id = ? AND status = 'pending'");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "ii", $claimId, $studentId);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt) > 0;
    }
}

/**
 * Spends the code: writes (or promotes) the redemption row and bumps used_count. Call this only
 * where the discount really takes effect, never from a price preview.
 *
 * If the student had already claimed this same code, that pending row is PROMOTED rather than
 * duplicated - otherwise the student's claim would linger forever next to the real discount.
 */
if (!function_exists('discount_apply_code')) {
    function discount_apply_code($con, string $rawCode, int $studentId, array $courseIds, string $byType, ?int $byId, ?int $subscriptionId = null): array {
        $check = discount_validate_code($con, $rawCode, $studentId, $courseIds);
        if (empty($check['valid'])) {
            return $check;
        }

        $codeId = (int)$check['code']['id'];
        $primaryCourseId = $check['items'][0]['course_id'] ?? null;

        // Prefer the claim the student made for this exact course; fall back to any claim they
        // hold on this code. Picked in PHP so the query stays a plain equality lookup.
        $pending = mysqli_prepare($con, "
            SELECT id, course_id FROM discount_code_redemptions
            WHERE discount_code_id = ? AND student_id = ? AND status = 'pending'
            ORDER BY id DESC
        ");
        mysqli_stmt_bind_param($pending, "ii", $codeId, $studentId);
        mysqli_stmt_execute($pending);
        $pendingRes = mysqli_stmt_get_result($pending);

        $claim = null;
        while ($row = mysqli_fetch_assoc($pendingRes)) {
            if ($claim === null) {
                $claim = $row;
            }
            if ($primaryCourseId !== null && (int)$row['course_id'] === (int)$primaryCourseId) {
                $claim = $row;
                break;
            }
        }

        if ($claim) {
            $claimId = (int)$claim['id'];
            $upd = mysqli_prepare($con, "
                UPDATE discount_code_redemptions
                SET course_id = ?, student_subscription_id = ?, original_amount = ?, discount_amount = ?,
                    final_amount = ?, status = 'applied', applied_by_type = ?, applied_by_id = ?
                WHERE id = ?
            ");
            mysqli_stmt_bind_param(
                $upd, "iidddsii",
                $primaryCourseId, $subscriptionId,
                $check['original_amount'], $check['discount_amount'], $check['final_amount'],
                $byType, $byId, $claimId
            );
            if (!mysqli_stmt_execute($upd)) {
                return ['valid' => false, 'message' => 'تعذر تسجيل استخدام كود الخصم: ' . mysqli_error($con)];
            }
            $check['redemption_id'] = $claimId;
            $check['promoted_from_student_claim'] = true;
        } else {
            $ins = mysqli_prepare($con, "
                INSERT INTO discount_code_redemptions
                    (discount_code_id, student_id, course_id, student_subscription_id, original_amount, discount_amount, final_amount, status, applied_by_type, applied_by_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'applied', ?, ?)
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
            $check['redemption_id'] = mysqli_insert_id($con);
            $check['promoted_from_student_claim'] = false;
        }

        mysqli_query($con, "UPDATE discount_codes SET used_count = used_count + 1 WHERE id = {$codeId}");

        $check['status'] = 'applied';
        return $check;
    }
}
