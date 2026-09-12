<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
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

        if ($teacherId > 0) {
            $where[] = "cs.teacher_id = ?";
            $params[] = $teacherId;
            $types .= "i";
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

        if (array_key_exists('teacher_id', $body) && (int)$body['teacher_id'] > 0) {
            $updateFields[] = "teacher_id = ?";
            $params[] = (int)$body['teacher_id'];
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
    function adminCreateRecurringConsultationSlots($con) {
        $body = read_json_body();
        $teacherId = (int)($body['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('teacher_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $teacherId);
        mysqli_stmt_execute($checkStmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            return ['status' => 'error', 'message' => trans('teacher_not_found')];
        }

        return create_recurring_consultation_slots_core($con, $teacherId, $body);
    }
}
