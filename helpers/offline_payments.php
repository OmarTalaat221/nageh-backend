<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/payments_grant.php';
require_once __DIR__ . '/discounts.php';
require_once __DIR__ . '/admin_notifications.php';

/**
 * Offline payments: the student sends the money themselves, and someone here confirms it.
 *
 * There is no gateway on this platform. Until now that meant a student transferred the money and
 * then tried to reach a human on WhatsApp with a screenshot - nothing recorded who had paid, what
 * they had paid for, or whether anyone had looked at it yet. This gives that a queue.
 *
 * The rule the whole file is built around: filing a request grants nothing. Access is opened only
 * when a reviewer approves, and the amount the student typed is never trusted as proof of
 * anything - it is shown next to the price so a person can compare them.
 */

if (!function_exists('offline_payment_methods')) {
    function offline_payment_methods(): array {
        return ['instapay', 'vodafone_cash', 'etihad_cash', 'orange_cash', 'we_pay', 'bank', 'other'];
    }
}

// =================================================================================================
// The accounts students transfer to
// =================================================================================================

if (!function_exists('payment_account_format')) {
    function payment_account_format($con, array $row, bool $forStudent = false): array {
        $isEnglish = get_request_lang($con) === 'en';

        $out = [
            'id' => (int)$row['id'],
            'method' => $row['method'],
            'label' => $isEnglish ? ($row['label_en'] ?: $row['label_ar']) : ($row['label_ar'] ?: $row['label_en']),
            'account_name' => $row['account_name'],
            'account_number' => $row['account_number'],
            'instructions' => $isEnglish
                ? ($row['instructions_en'] ?: $row['instructions_ar'])
                : ($row['instructions_ar'] ?: $row['instructions_en']),
        ];

        // The admin screen edits both languages, so it needs the raw columns as well.
        if (!$forStudent) {
            $out += [
                'label_ar' => $row['label_ar'],
                'label_en' => $row['label_en'],
                'instructions_ar' => $row['instructions_ar'],
                'instructions_en' => $row['instructions_en'],
                'is_active' => (int)$row['is_active'],
                'sort_order' => (int)$row['sort_order'],
                'created_at' => $row['created_at'],
            ];
        }

        return $out;
    }
}

if (!function_exists('payment_accounts_list')) {
    function payment_accounts_list($con, bool $activeOnly = false): array {
        $sql = "SELECT * FROM payment_accounts";
        if ($activeOnly) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY sort_order ASC, id ASC";

        $res = @mysqli_query($con, $sql);
        if (!$res) return [];

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = payment_account_format($con, $row, $activeOnly);
        }
        return $rows;
    }
}

if (!function_exists('payment_account_find')) {
    function payment_account_find($con, int $id): ?array {
        $stmt = mysqli_prepare($con, "SELECT * FROM payment_accounts WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }
}

// =================================================================================================
// Pricing a request
// =================================================================================================

/**
 * What this student should be paying for this item right now, including a discount code they are
 * already holding.
 *
 * The discount is only previewed here - it is not spent. A code is consumed when access is
 * actually granted, so a request that is never approved costs the student nothing.
 */
if (!function_exists('offline_payment_quote')) {
    function offline_payment_quote($con, int $studentId, string $itemType, int $itemId, string $code = ''): array {
        $item = payments_resolve_purchasable_item($con, $itemType, $itemId);
        if (!$item) {
            return ['ok' => false, 'message' => app_offline_text($con, 'العنصر ده مش موجود.', 'That item does not exist.')];
        }
        if ((int)$item['is_active'] !== 1) {
            return ['ok' => false, 'message' => app_offline_text($con, 'العنصر ده مش متاح حاليًا.', 'That item is not available right now.')];
        }

        $price = (float)$item['price'];
        if ($price <= 0) {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'العنصر ده مالوش سعر، مفيش حاجة تتدفع.',
                'This item has no price, so there is nothing to pay.'
            )];
        }

        $discountAmount = 0.00;
        $discountCodeId = null;
        $discountMessage = '';

        // Discount codes only ever reduce a course price - a book is not something they apply to.
        $code = trim($code);
        if ($code !== '' && $itemType === 'course') {
            // It reads the price off the course itself, so the figure a reviewer later compares
            // against is the one the discount engine actually used.
            $check = discount_validate_code($con, $code, $studentId, [$itemId]);
            if (!empty($check['valid'])) {
                $discountAmount = (float)($check['discount_amount'] ?? 0);
                $discountCodeId = (int)($check['code']['id'] ?? 0) ?: null;
                $price = (float)($check['original_amount'] ?? $price);
            } else {
                $discountMessage = (string)($check['message'] ?? '');
            }
        }

        $final = max(0.0, round($price - $discountAmount, 2));

        return [
            'ok' => true,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'original_amount' => round($price, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_code_id' => $discountCodeId,
            'discount_message' => $discountMessage,
            'amount_due' => $final,
        ];
    }
}

if (!function_exists('app_offline_text')) {
    function app_offline_text($con, string $ar, string $en): string {
        return get_request_lang($con) === 'en' ? $en : $ar;
    }
}

// =================================================================================================
// Reading requests
// =================================================================================================

if (!function_exists('offline_payment_format')) {
    function offline_payment_format($con, array $row, bool $forStudent = false): array {
        $out = [
            'id' => (int)$row['id'],
            'student_id' => (int)$row['student_id'],
            'item_type' => $row['item_type'],
            'item_id' => (int)$row['item_id'],
            'item_name' => $row['item_name'] ?? null,
            'original_amount' => (float)$row['original_amount'],
            'discount_amount' => (float)$row['discount_amount'],
            'amount' => (float)$row['amount'],
            'currency' => $row['currency'],
            'sender_name' => $row['sender_name'],
            'sender_reference' => $row['sender_reference'],
            'transferred_at' => $row['transferred_at'],
            'receipt_image' => $row['receipt_image'],
            'student_note' => $row['student_note'],
            'status' => $row['status'],
            'review_note' => $row['review_note'],
            'reviewed_at' => $row['reviewed_at'],
            'created_at' => $row['created_at'],
            'payment_account_id' => $row['payment_account_id'] ? (int)$row['payment_account_id'] : null,
            'payment_account_label' => $row['payment_account_label'] ?? null,
        ];

        // A student has no business knowing which staff member looked at their receipt.
        if (!$forStudent) {
            $out += [
                'student_name' => $row['student_name'] ?? null,
                'student_email' => $row['student_email'] ?? null,
                'student_phone' => $row['student_phone'] ?? null,
                'reviewed_by_type' => $row['reviewed_by_type'],
                'reviewed_by_id' => $row['reviewed_by_id'] ? (int)$row['reviewed_by_id'] : null,
                'granted_ref_id' => $row['granted_ref_id'] ? (int)$row['granted_ref_id'] : null,
            ];
        }

        return format_response_images($out);
    }
}

/**
 * The shared SELECT. `item_name` is resolved per row type so a reviewer sees what was bought
 * rather than an id, and the amounts come back beside it so the comparison is on one line.
 */
if (!function_exists('offline_payment_base_query')) {
    function offline_payment_base_query($con): string {
        $langInfo = get_language_info($con);
        $langId = (int)($langInfo['id'] ?? 1);

        return "
            SELECT r.*,
                   s.name AS student_name, s.email AS student_email, s.phone AS student_phone,
                   pa.label_ar AS payment_account_label,
                   CASE r.item_type
                       WHEN 'course' THEN COALESCE(
                           (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = r.item_id AND ct.language_id = {$langId} LIMIT 1),
                           (SELECT ct.name FROM courses_translations ct WHERE ct.course_id = r.item_id LIMIT 1))
                       ELSE COALESCE(
                           (SELECT bt.title FROM course_books_translations bt WHERE bt.book_id = r.item_id AND bt.language_id = {$langId} LIMIT 1),
                           (SELECT bt.title FROM course_books_translations bt WHERE bt.book_id = r.item_id LIMIT 1))
                   END AS item_name
            FROM offline_payment_requests r
            LEFT JOIN students s ON s.id = r.student_id
            LEFT JOIN payment_accounts pa ON pa.id = r.payment_account_id
        ";
    }
}

// =================================================================================================
// Approving and rejecting
// =================================================================================================

/**
 * Approves a request: opens the access, spends the discount code if one was quoted, and records
 * who did it.
 *
 * Re-checks the status inside the UPDATE rather than trusting the row it just read. Two reviewers
 * with the same queue open is not a hypothetical, and without that check both would grant the
 * same access and the student would end up with two subscriptions to one course.
 */
if (!function_exists('offline_payment_approve')) {
    function offline_payment_approve($con, array $request, string $reviewerType, int $reviewerId, string $note = ''): array {
        $id = (int)$request['id'];

        $claim = mysqli_prepare($con, "UPDATE offline_payment_requests
            SET status = 'approved', reviewed_by_type = ?, reviewed_by_id = ?, reviewed_at = NOW(), review_note = ?
            WHERE id = ? AND status = 'pending'");
        if (!$claim) {
            return ['ok' => false, 'message' => app_offline_text($con, 'تعذر تنفيذ العملية.', 'The operation could not be completed.')];
        }
        mysqli_stmt_bind_param($claim, "sisi", $reviewerType, $reviewerId, $note, $id);
        mysqli_stmt_execute($claim);

        if (mysqli_stmt_affected_rows($claim) === 0) {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'الطلب ده اتراجع عليه بالفعل.',
                'This request has already been reviewed.'
            )];
        }

        $studentId = (int)$request['student_id'];
        $itemType = $request['item_type'];
        $itemId = (int)$request['item_id'];

        // Already owning it is not a failure - the money was still received, and the request
        // should close as approved rather than trapping a student between two states.
        if (payments_student_already_has_access($con, $studentId, $itemType, $itemId)) {
            return [
                'ok' => true,
                'already_had_access' => true,
                'message' => app_offline_text(
                    $con,
                    'الطلب اتقبل. الطالب كان معاه الوصول بالفعل.',
                    'Approved. The student already had access.'
                ),
            ];
        }

        $grantedId = payments_grant_access($con, $studentId, $itemType, $itemId, (float)$request['amount']);
        if ($grantedId === null) {
            // Put it back rather than leaving a request marked approved that granted nothing.
            mysqli_query($con, "UPDATE offline_payment_requests SET status = 'pending', reviewed_by_type = NULL, reviewed_by_id = NULL, reviewed_at = NULL WHERE id = {$id}");
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'تعذر فتح الوصول للطالب، والطلب رجع لحالته.',
                'Access could not be opened for the student, so the request was put back.'
            )];
        }

        $upd = mysqli_prepare($con, "UPDATE offline_payment_requests SET granted_ref_id = ? WHERE id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, "ii", $grantedId, $id);
            mysqli_stmt_execute($upd);
        }

        // The code is spent here, not when the request was filed: a request that is never
        // approved must not cost the student one of their uses.
        $codeId = $request['discount_code_id'] ? (int)$request['discount_code_id'] : 0;
        if ($codeId > 0 && $itemType === 'course' && function_exists('discount_apply_code')) {
            $codeRow = get_discount_code_details($con, $codeId);
            if ($codeRow) {
                discount_apply_code(
                    $con,
                    (string)$codeRow['code'],
                    $studentId,
                    [$itemId],
                    $reviewerType,
                    $reviewerId,
                    $grantedId
                );
            }
        }

        return [
            'ok' => true,
            'granted_ref_id' => $grantedId,
            'message' => app_offline_text($con, 'تم قبول الطلب وفتح الوصول للطالب.', 'Approved, and access has been opened for the student.'),
        ];
    }
}

if (!function_exists('offline_payment_reject')) {
    function offline_payment_reject($con, array $request, string $reviewerType, int $reviewerId, string $note): array {
        $note = trim($note);
        if ($note === '') {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'اكتب سبب الرفض — الطالب هيقراه.',
                'A reason is required - the student is shown it.'
            )];
        }

        $id = (int)$request['id'];
        $stmt = mysqli_prepare($con, "UPDATE offline_payment_requests
            SET status = 'rejected', reviewed_by_type = ?, reviewed_by_id = ?, reviewed_at = NOW(), review_note = ?
            WHERE id = ? AND status = 'pending'");
        if (!$stmt) {
            return ['ok' => false, 'message' => app_offline_text($con, 'تعذر تنفيذ العملية.', 'The operation could not be completed.')];
        }
        mysqli_stmt_bind_param($stmt, "sisi", $reviewerType, $reviewerId, $note, $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return ['ok' => false, 'message' => app_offline_text(
                $con,
                'الطلب ده اتراجع عليه بالفعل.',
                'This request has already been reviewed.'
            )];
        }

        return ['ok' => true, 'message' => app_offline_text($con, 'تم رفض الطلب.', 'The request was rejected.')];
    }
}

/**
 * The course ids a teacher may review payments for - the same ownership test the discount codes
 * use, so a teacher never sees money for work that is not theirs.
 */
if (!function_exists('offline_payment_teacher_course_ids')) {
    function offline_payment_teacher_course_ids($con, int $teacherId): array {
        return discount_teacher_course_ids($con, $teacherId);
    }
}
