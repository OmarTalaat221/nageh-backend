<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/consultation_notifications.php';
require_once __DIR__ . '/../../teacher/consultations/manage.php';

if (!function_exists('adminListConsultations')) {
    function adminListConsultations($con) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        $studentId = (int)($_GET['student_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        $status = strtolower(trim($_GET['status'] ?? ''));
        $meetingType = strtolower(trim($_GET['meeting_type'] ?? ''));
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = ["1=1"];
        $params = [];
        $types = "";

        // teacher_id=0 sent explicitly asks for the platform owner's own slots; the parameter
        // left out still means "any teacher", as it always did.
        if ($teacherId > 0) {
            $where[] = "cs.teacher_id = ?";
            $params[] = $teacherId;
            $types .= "i";
        } elseif (isset($_GET['teacher_id']) && trim((string)$_GET['teacher_id']) !== '') {
            $where[] = "cs.teacher_id IS NULL";
        }
        if ($studentId > 0) {
            $where[] = "cs.student_id = ?";
            $params[] = $studentId;
            $types .= "i";
        }
        if ($subjectId > 0) {
            $where[] = "cs.subject_id = ?";
            $params[] = $subjectId;
            $types .= "i";
        }
        if (!empty($status)) {
            $where[] = "cs.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        if (!empty($meetingType)) {
            $where[] = "cs.meeting_type = ?";
            $params[] = $meetingType;
            $types .= "s";
        }
        if (!empty($search)) {
            $pattern = '%' . $search . '%';
            $where[] = "(t.name LIKE ? OR s.name LIKE ? OR cs.notes LIKE ?)";
            $params[] = $pattern;
            $params[] = $pattern;
            $params[] = $pattern;
            $types .= "sss";
        }

        apply_consultation_date_filters($where, $params, $types, 'cs');
        apply_consultation_type_filter($where, $params, $types, 'cs');

        $whereClause = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) AS total FROM consultation_slots cs LEFT JOIN teachers t ON cs.teacher_id = t.id LEFT JOIN students s ON cs.student_id = s.id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT cs.id FROM consultation_slots cs LEFT JOIN teachers t ON cs.teacher_id = t.id LEFT JOIN students s ON cs.student_id = s.id {$whereClause} ORDER BY cs.slot_date DESC, cs.slot_time DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        $bindTypes = $types . "ii";
        $bindParams = array_merge($params, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = get_consultation_slot_details($con, (int)$row['id']);
        }

        $statisticsRes = mysqli_query($con, "SELECT COUNT(*) AS total_slots, SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_slots, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_slots, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_slots FROM consultation_slots");
        $statRow = mysqli_fetch_assoc($statisticsRes) ?: [];

        return build_admin_paginated_response($items, $page, $limit, $totalRecords, [
            'total_slots' => (int)($statRow['total_slots'] ?? 0),
            'available_slots' => (int)($statRow['available_slots'] ?? 0),
            'pending_slots' => (int)($statRow['pending_slots'] ?? 0),
            'approved_slots' => (int)($statRow['approved_slots'] ?? 0)
        ]);
    }
}

if (!function_exists('adminUpdateConsultationSlot')) {
    function adminUpdateConsultationSlot($con) {
        $body = read_json_body();
        $slotId = (int)($_GET['id'] ?? $body['slot_id'] ?? $body['id'] ?? 0);

        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => trans('slot_id_required')];
        }

        ensure_consultation_slots_type_column($con);

        $checkStmt = mysqli_prepare($con, "SELECT id FROM consultation_slots WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $slotId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('consultation_slot_not_found')];
        }

        $updateFields = [];
        $params = [];
        $types = "";

        // Moving a slot to the platform owner is teacher_id = 0, which is stored as no teacher
        // at all; any other value still has to be a real teacher.
        if (array_key_exists('teacher_id', $body) && (int)$body['teacher_id'] >= 0) {
            $updateFields[] = "teacher_id = ?";
            $params[] = (int)$body['teacher_id'] > 0 ? (int)$body['teacher_id'] : null;
            $types .= "i";
        }
        if (array_key_exists('subject_id', $body)) {
            $updateFields[] = "subject_id = ?";
            $params[] = $body['subject_id'] ? (int)$body['subject_id'] : null;
            $types .= "i";
        }
        if (array_key_exists('type', $body) || array_key_exists('consultation_type', $body)) {
            $rawType = strtolower(trim($body['type'] ?? $body['consultation_type'] ?? ''));
            if (in_array($rawType, ['psychological', 'psych', 'نفسية', 'نفسيه'])) {
                $typeVal = 'psychological';
            } else {
                $typeVal = 'educational';
            }
            $updateFields[] = "type = ?";
            $params[] = $typeVal;
            $types .= "s";
        }
        if (array_key_exists('student_id', $body)) {
            $studentVal = $body['student_id'] ? (int)$body['student_id'] : null;
            $updateFields[] = "student_id = ?";
            $params[] = $studentVal;
            $types .= "i";
            if ($studentVal === null) {
                $updateFields[] = "is_booked = 0";
                $updateFields[] = "booked_at = NULL";
            } else {
                $updateFields[] = "is_booked = 1";
            }
        }
        if (array_key_exists('meeting_type', $body)) {
            $mType = strtolower(trim($body['meeting_type']));
            if (in_array($mType, ['offline', 'video_call', 'chat', 'online'])) {
                $updateFields[] = "meeting_type = ?";
                $params[] = $mType;
                $types .= "s";
            }
        }
        if (!empty($body['slot_date'])) {
            $updateFields[] = "slot_date = ?";
            $params[] = date('Y-m-d', strtotime($body['slot_date']));
            $types .= "s";
        }
        if (!empty($body['slot_time'])) {
            $updateFields[] = "slot_time = ?";
            $params[] = date('H:i:s', strtotime($body['slot_time']));
            $types .= "s";
        }
        if (array_key_exists('notes', $body)) {
            $updateFields[] = "notes = ?";
            $params[] = trim($body['notes']);
            $types .= "s";
        }
        if (!empty($body['status'])) {
            $st = strtolower(trim($body['status']));
            $updateFields[] = "status = ?";
            $params[] = $st;
            $types .= "s";
        }
        if (array_key_exists('is_active', $body)) {
            $updateFields[] = "is_active = ?";
            $params[] = (int)$body['is_active'];
            $types .= "i";
        }

        if (!empty($updateFields)) {
            $sql = "UPDATE consultation_slots SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $params[] = $slotId;
            $types .= "i";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
        }

        return [
            'status' => 'success',
            'message' => trans('consultation_updated_success'),
            'data' => get_consultation_slot_details($con, $slotId)
        ];
    }
}

if (!function_exists('adminDeleteConsultationSlot')) {
    function adminDeleteConsultationSlot($con) {
        $body = read_json_body();
        $slotId = (int)($_GET['id'] ?? $body['slot_id'] ?? $body['id'] ?? 0);
        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => trans('slot_id_required')];
        }

        $stmt = mysqli_prepare($con, "DELETE FROM consultation_slots WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $slotId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete consultation slot'];
        }

        return ['status' => 'success', 'message' => trans('consultation_deleted_success')];
    }
}

if (!function_exists('adminCreateRecurringConsultationSlots')) {
    function adminCreateRecurringConsultationSlots($con, $adminData = null) {
        $body = read_json_body();
        // 0 means the platform owner, who gives consultations himself and has no row in
        // `teachers`. Any other value still has to be a teacher that exists.
        $teacherId = (int)($body['teacher_id'] ?? 0);
        if ($teacherId > 0) {
            $checkStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($checkStmt, "i", $teacherId);
            mysqli_stmt_execute($checkStmt);
            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
                return ['status' => 'error', 'message' => trans('teacher_not_found')];
            }
        }

        // Optional: create the run already booked for one student. Without this the admin had to
        // create the slots, then find each generated date in the table and book it separately -
        // which for a weekly slot over a term is a dozen trips through the same dialog.
        $studentId = isset($body['student_id']) && (int)$body['student_id'] > 0 ? (int)$body['student_id'] : 0;
        if ($studentId > 0) {
            $student = mysqli_fetch_assoc(mysqli_query($con, "SELECT id, is_active FROM students WHERE id = {$studentId} LIMIT 1"));
            if (!$student) {
                return ['status' => 'error', 'message' => 'Student not found'];
            }
            if ((int)$student['is_active'] !== 1) {
                return ['status' => 'error', 'message' => 'This student account is not active'];
            }
        }

        $result = create_recurring_consultation_slots_core($con, $teacherId, $body);

        if ($studentId <= 0 || ($result['status'] ?? '') !== 'success') {
            return $result;
        }

        // Only the dates this call actually created are booked. Dates that already existed are
        // skipped by the core as duplicates, and they may well belong to somebody else.
        $created = $result['data']['slots'] ?? [];
        $ids = array_values(array_filter(array_map(fn($s) => (int)($s['id'] ?? 0), $created)));
        if (empty($ids)) {
            return $result;
        }

        $idList = implode(',', $ids);
        mysqli_query($con, "UPDATE consultation_slots SET is_booked = 1, student_id = {$studentId}, booked_at = NOW(), status = 'approved' WHERE id IN ({$idList})");

        $dates = array_map(fn($s) => (string)($s['slot_date'] ?? ''), $created);
        sort($dates);

        $adminId = is_array($adminData) ? (int)($adminData['id'] ?? 0) : 0;
        notify_consultation_series_booked($con, $studentId, $teacherId, count($ids), reset($dates) ?: '', end($dates) ?: '', $adminId ?: null);

        $result['data']['booked_for_student_id'] = $studentId;
        $result['data']['booked_count'] = count($ids);
        $result['message'] = trans('slot_booked_for_student');

        return $result;
    }
}

if (!function_exists('adminBookConsultationForStudent')) {
    /**
     * The admin books a slot on a student's behalf.
     *
     * Until now the only way to do this was to edit the slot and set its student, which left
     * `booked_at` empty, left the status on whatever it was, and told nobody. Booking is its own
     * action because it is not an edit: it involves two people who both need to hear about it.
     *
     * The booking is created already approved. An admin booking that still waited on the teacher's
     * approval would not be a booking, it would be a request - and the admin is the one place on
     * the platform that does not need to ask.
     */
    function adminBookConsultationForStudent($con, $adminData = null) {
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? $body['id'] ?? $_GET['id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);

        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => trans('slot_id_required')];
        }
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'student_id is required'];
        }

        $slot = mysqli_fetch_assoc(mysqli_query($con, "SELECT id, teacher_id, student_id, is_booked, is_active, slot_date, slot_time FROM consultation_slots WHERE id = {$slotId} LIMIT 1"));
        if (!$slot) {
            return ['status' => 'error', 'message' => trans('consultation_not_found_or_not_booked')];
        }
        if ((int)$slot['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('slot_no_longer_available')];
        }
        // Already held by somebody else. Re-booking the same student onto the same slot is allowed
        // and simply refreshes it, so a double click is not an error.
        if ((int)$slot['is_booked'] === 1 && (int)$slot['student_id'] > 0 && (int)$slot['student_id'] !== $studentId) {
            return ['status' => 'error', 'message' => trans('slot_no_longer_available')];
        }

        $student = mysqli_fetch_assoc(mysqli_query($con, "SELECT id, is_active FROM students WHERE id = {$studentId} LIMIT 1"));
        if (!$student) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }
        if ((int)$student['is_active'] !== 1) {
            return ['status' => 'error', 'message' => 'This student account is not active'];
        }

        $stmt = mysqli_prepare($con, "UPDATE consultation_slots SET is_booked = 1, student_id = ?, booked_at = NOW(), status = 'approved' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $slotId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to book this consultation'];
        }

        $adminId = is_array($adminData) ? (int)($adminData['id'] ?? 0) : 0;
        notify_consultation_booked($con, $slotId, $studentId, (int)$slot['teacher_id'], 'admin', $adminId ?: null);

        return [
            'status' => 'success',
            'message' => trans('slot_booked_for_student'),
            'data' => get_consultation_slot_details($con, $slotId)
        ];
    }
}
