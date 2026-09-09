<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/admin_notifications.php';

if (!function_exists('ensure_consultation_slots_type_column')) {
    function ensure_consultation_slots_type_column($con): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        $check = @mysqli_query($con, "SHOW COLUMNS FROM consultation_slots LIKE 'type'");
        if ($check && mysqli_num_rows($check) === 0) {
            @mysqli_query($con, "ALTER TABLE consultation_slots ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'educational' AFTER `subject_id`");
        }
    }
}

if (!function_exists('get_consultation_slot_details')) {
    function get_consultation_slot_details($con, int $slotId) {
        ensure_consultation_slots_type_column($con);
        $stmt = mysqli_prepare($con, "SELECT id, teacher_id, subject_id, type, meeting_type, slot_date, slot_time, notes, is_booked, student_id, booked_at, status, is_active, created_at FROM consultation_slots WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "i", $slotId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if (!$res) return null;
        $row = mysqli_fetch_assoc($res);
        if (!$row) return null;

        $row['id'] = (int)$row['id'];
        $row['teacher_id'] = (int)$row['teacher_id'];
        $row['subject_id'] = $row['subject_id'] ? (int)$row['subject_id'] : null;

        $rawType = strtolower(trim($row['type'] ?? 'educational'));
        if (in_array($rawType, ['psychological', 'psych', 'نفسية', 'نفسيه'])) {
            $row['type'] = 'psychological';
            $row['type_label'] = 'استشارة نفسية';
            $row['type_ar'] = 'نفسية';
        } else {
            $row['type'] = 'educational';
            $row['type_label'] = 'استشارة تربوية';
            $row['type_ar'] = 'تربوية';
        }

        $row['student_id'] = $row['student_id'] ? (int)$row['student_id'] : null;
        $row['is_booked'] = (int)$row['is_booked'];
        $row['is_active'] = (int)$row['is_active'];
        $row['status'] = $row['status'] ?: ($row['is_booked'] ? 'pending' : 'available');

        $row['date'] = $row['slot_date'];
        $row['session_date'] = $row['slot_date'];

        $row['subject'] = $row['subject_id'] ? get_subject_details($con, $row['subject_id']) : null;
        $row['teacher'] = get_teacher_details($con, $row['teacher_id']);
        $row['student'] = $row['student_id'] ? get_student_details($con, $row['student_id']) : null;

        return format_response_images($row);
    }
}

if (!function_exists('apply_consultation_type_filter')) {
    function apply_consultation_type_filter(&$where, &$params, &$types, $prefix = '') {
        $p = $prefix ? $prefix . '.' : '';
        $rawType = strtolower(trim($_GET['type'] ?? $_GET['consultation_type'] ?? ''));
        if (!empty($rawType)) {
            if (in_array($rawType, ['psychological', 'psych', 'نفسية', 'نفسيه'])) {
                $where[] = "{$p}type = 'psychological'";
            } else if (in_array($rawType, ['educational', 'pedagogical', 'تربوية', 'تربويه'])) {
                $where[] = "{$p}type = 'educational'";
            } else {
                $where[] = "{$p}type = ?";
                $params[] = $rawType;
                $types .= "s";
            }
        }
    }
}

if (!function_exists('apply_consultation_date_filters')) {
    function apply_consultation_date_filters(&$where, &$params, &$types, $prefix = '') {
        $p = $prefix ? $prefix . '.' : '';
        $exactDate = trim($_GET['date'] ?? $_GET['slot_date'] ?? '');
        $fromDate = trim($_GET['from_date'] ?? $_GET['start_date'] ?? '');
        $toDate = trim($_GET['to_date'] ?? $_GET['end_date'] ?? '');
        $dayOfWeek = trim($_GET['day'] ?? $_GET['day_of_week'] ?? '');

        if (!empty($exactDate) && strtotime($exactDate) !== false) {
            $where[] = "{$p}slot_date = ?";
            $params[] = date('Y-m-d', strtotime($exactDate));
            $types .= "s";
        } else {
            if (!empty($fromDate) && strtotime($fromDate) !== false) {
                $where[] = "{$p}slot_date >= ?";
                $params[] = date('Y-m-d', strtotime($fromDate));
                $types .= "s";
            }
            if (!empty($toDate) && strtotime($toDate) !== false) {
                $where[] = "{$p}slot_date <= ?";
                $params[] = date('Y-m-d', strtotime($toDate));
                $types .= "s";
            }
        }

        if (!empty($dayOfWeek)) {
            $allowedDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            if (in_array($dayOfWeek, $allowedDays, true)) {
                $where[] = "DAYNAME({$p}slot_date) = ?";
                $params[] = $dayOfWeek;
                $types .= "s";
            }
        }
    }
}

if (!function_exists('teacherListConsultationSlots')) {
    function teacherListConsultationSlots($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = trim($_GET['status'] ?? '');

        $where = ["teacher_id = ?"];
        $params = [$teacherId];
        $types = "i";

        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }

        apply_consultation_date_filters($where, $params, $types);
        apply_consultation_type_filter($where, $params, $types);

        $whereClause = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) AS total FROM consultation_slots {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT id FROM consultation_slots {$whereClause} ORDER BY slot_date ASC, slot_time ASC LIMIT ? OFFSET ?";
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

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('teacherListBookedConsultations')) {
    function teacherListBookedConsultations($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = trim($_GET['status'] ?? '');

        $where = ["teacher_id = ?", "student_id IS NOT NULL"];
        $params = [$teacherId];
        $types = "i";

        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }

        apply_consultation_date_filters($where, $params, $types);
        apply_consultation_type_filter($where, $params, $types);

        $whereClause = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) AS total FROM consultation_slots {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT id FROM consultation_slots {$whereClause} ORDER BY booked_at DESC, slot_date ASC LIMIT ? OFFSET ?";
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

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('teacherCreateConsultationSlot')) {
    function teacherCreateConsultationSlot($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();

        ensure_consultation_slots_type_column($con);

        $rawType = strtolower(trim($body['type'] ?? $body['consultation_type'] ?? $_GET['type'] ?? 'educational'));
        if (in_array($rawType, ['psychological', 'psych', 'نفسية', 'نفسيه'])) {
            $type = 'psychological';
        } else {
            $type = 'educational';
        }

        $meetingType = strtolower(trim($body['meeting_type'] ?? 'video_call'));
        if (!in_array($meetingType, ['offline', 'video_call', 'chat', 'online'])) {
            return ['status' => 'error', 'message' => trans('invalid_meeting_type')];
        }
        $subjectId = isset($body['subject_id']) && $body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        $slotDate = !empty($body['slot_date']) ? date('Y-m-d', strtotime($body['slot_date'])) : null;
        $slotTime = !empty($body['slot_time']) ? date('H:i:s', strtotime($body['slot_time'])) : null;
        $notes = trim($body['notes'] ?? '');

        if (empty($slotDate) || empty($slotTime)) {
            return ['status' => 'error', 'message' => trans('slot_date_time_required')];
        }

        $stmt = mysqli_prepare($con, "INSERT INTO consultation_slots (teacher_id, subject_id, type, meeting_type, slot_date, slot_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')");
        mysqli_stmt_bind_param($stmt, "iisssss", $teacherId, $subjectId, $type, $meetingType, $slotDate, $slotTime, $notes);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to create slot: ' . mysqli_error($con)];
        }

        $newSlotId = mysqli_insert_id($con);
        notify_of_consultation_slot_created($con, $teacherId, $subjectId, $slotDate, $slotTime);

        return ['status' => 'success', 'message' => trans('slot_added_success'), 'data' => get_consultation_slot_details($con, $newSlotId)];
    }
}

if (!function_exists('teacherCreateRecurringConsultationSlots')) {
    function teacherCreateRecurringConsultationSlots($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();

        ensure_consultation_slots_type_column($con);

        $allowedDays = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $rawDays = $body['days'] ?? [];
        if (!is_array($rawDays)) {
            $rawDays = [$rawDays];
        }
        $days = [];
        foreach ($rawDays as $rawDay) {
            $normalized = ucfirst(strtolower(trim((string)$rawDay)));
            if (in_array($normalized, $allowedDays, true) && !in_array($normalized, $days, true)) {
                $days[] = $normalized;
            }
        }
        if (empty($days)) {
            return ['status' => 'error', 'message' => trans('at_least_one_day_required')];
        }

        $rawType = strtolower(trim($body['type'] ?? $body['consultation_type'] ?? 'educational'));
        if (in_array($rawType, ['psychological', 'psych', 'نفسية', 'نفسيه'])) {
            $type = 'psychological';
        } else {
            $type = 'educational';
        }

        $meetingType = strtolower(trim($body['meeting_type'] ?? 'video_call'));
        if (!in_array($meetingType, ['offline', 'video_call', 'chat', 'online'])) {
            return ['status' => 'error', 'message' => trans('invalid_meeting_type')];
        }

        $subjectId = isset($body['subject_id']) && $body['subject_id'] > 0 ? (int)$body['subject_id'] : null;
        $slotTime = !empty($body['slot_time']) ? date('H:i:s', strtotime($body['slot_time'])) : null;
        $notes = trim($body['notes'] ?? '');

        if (empty($slotTime)) {
            return ['status' => 'error', 'message' => trans('slot_time_required')];
        }

        $endDateStr = !empty($body['end_date']) ? date('Y-m-d', strtotime($body['end_date'])) : null;
        if (empty($endDateStr)) {
            return ['status' => 'error', 'message' => trans('end_date_required')];
        }

        $today = get_request_now();
        $todayStr = $today->format('Y-m-d');
        if ($endDateStr < $todayStr) {
            return ['status' => 'error', 'message' => trans('end_date_must_be_in_future')];
        }

        $maxEndDateStr = (clone $today)->modify('+180 days')->format('Y-m-d');
        if ($endDateStr > $maxEndDateStr) {
            return ['status' => 'error', 'message' => trans('end_date_too_far')];
        }

        // For every selected weekday, walk from today (or that weekday's next occurrence)
        // in 7-day steps up to end_date inclusive — same day-name resolution approach as
        // get_session_calculated_date() for class_sessions, just repeated until the end date
        // instead of stopping at the first occurrence.
        $datesToCreate = [];
        foreach ($days as $dayName) {
            $cursor = clone $today;
            if (strcasecmp($today->format('l'), $dayName) !== 0) {
                $cursor->modify("next {$dayName}");
            }

            while ($cursor->format('Y-m-d') <= $endDateStr) {
                $datesToCreate[] = $cursor->format('Y-m-d');
                $cursor = clone $cursor;
                $cursor->modify('+7 days');
            }
        }

        $datesToCreate = array_values(array_unique($datesToCreate));
        sort($datesToCreate);

        if (empty($datesToCreate)) {
            return ['status' => 'error', 'message' => trans('no_dates_in_range')];
        }

        if (count($datesToCreate) > 200) {
            return ['status' => 'error', 'message' => trans('too_many_slots_requested')];
        }

        // Skip dates where this exact teacher+date+time slot already exists, so running
        // this twice (or with overlapping ranges) never creates duplicates.
        $existingDates = [];
        $placeholders = implode(',', array_fill(0, count($datesToCreate), '?'));
        $existingStmt = mysqli_prepare($con, "SELECT slot_date FROM consultation_slots WHERE teacher_id = ? AND slot_time = ? AND slot_date IN ({$placeholders})");
        if ($existingStmt) {
            $bindTypes = 'is' . str_repeat('s', count($datesToCreate));
            $bindParams = array_merge([$teacherId, $slotTime], $datesToCreate);
            mysqli_stmt_bind_param($existingStmt, $bindTypes, ...$bindParams);
            mysqli_stmt_execute($existingStmt);
            $existingRes = mysqli_stmt_get_result($existingStmt);
            while ($existingRow = mysqli_fetch_assoc($existingRes)) {
                $existingDates[] = $existingRow['slot_date'];
            }
        }

        $newDates = array_values(array_diff($datesToCreate, $existingDates));

        $createdSlots = [];
        $insertStmt = mysqli_prepare($con, "INSERT INTO consultation_slots (teacher_id, subject_id, type, meeting_type, slot_date, slot_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')");
        foreach ($newDates as $slotDate) {
            mysqli_stmt_bind_param($insertStmt, "iisssss", $teacherId, $subjectId, $type, $meetingType, $slotDate, $slotTime, $notes);
            if (mysqli_stmt_execute($insertStmt)) {
                $createdSlots[] = ['id' => mysqli_insert_id($con), 'slot_date' => $slotDate, 'slot_time' => $slotTime];
            }
        }

        if (!empty($createdSlots)) {
            // One summary notification for the whole batch instead of one per generated
            // date, so this doesn't spam admins/students with dozens of near-identical alerts.
            notify_of_consultation_slot_created($con, $teacherId, $subjectId, $createdSlots[0]['slot_date'], $slotTime);
        }

        return [
            'status' => 'success',
            'message' => trans('recurring_slots_created_success'),
            'data' => [
                'created_count' => count($createdSlots),
                'skipped_count' => count($existingDates),
                'slots' => $createdSlots,
            ],
        ];
    }
}

if (!function_exists('teacherRespondToConsultationBooking')) {
    function teacherRespondToConsultationBooking($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();

        $slotId = (int)($_GET['id'] ?? $body['slot_id'] ?? $body['id'] ?? 0);
        $action = strtolower(trim($body['action'] ?? $body['status'] ?? ''));

        if ($slotId <= 0) {
            return ['status' => 'error', 'message' => trans('slot_id_required')];
        }

        $checkStmt = mysqli_prepare($con, "SELECT id, is_booked, student_id FROM consultation_slots WHERE id = ? AND teacher_id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "ii", $slotId, $teacherId);
        mysqli_stmt_execute($checkStmt);
        $slot = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));

        if (!$slot || !$slot['student_id']) {
            return ['status' => 'error', 'message' => trans('consultation_not_found_or_not_booked')];
        }

        if (in_array($action, ['approve', 'approved', 'accept', 'accepted'])) {
            $stmt = mysqli_prepare($con, "UPDATE consultation_slots SET status = 'approved' WHERE id = ? AND teacher_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $slotId, $teacherId);
            mysqli_stmt_execute($stmt);
            $msg = trans('consultation_approved_success');
        } elseif (in_array($action, ['reject', 'rejected', 'decline', 'declined', 'cancel', 'cancelled'])) {
            $stmt = mysqli_prepare($con, "UPDATE consultation_slots SET is_booked = 0, student_id = NULL, booked_at = NULL, status = 'available' WHERE id = ? AND teacher_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $slotId, $teacherId);
            mysqli_stmt_execute($stmt);
            $msg = trans('consultation_cancelled_restored');
        } else {
            return ['status' => 'error', 'message' => trans('invalid_action_approve_reject')];
        }

        return [
            'status' => 'success',
            'message' => $msg,
            'data' => get_consultation_slot_details($con, $slotId)
        ];
    }
}

if (!function_exists('teacherCancelConsultationSlot')) {
    function teacherCancelConsultationSlot($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $slotId = (int)($_GET['id'] ?? $body['slot_id'] ?? $body['id'] ?? 0);
        if ($slotId <= 0) return ['status' => 'error', 'message' => trans('slot_id_required')];

        $stmt = mysqli_prepare($con, "UPDATE consultation_slots SET is_booked = 0, student_id = NULL, booked_at = NULL, status = 'available' WHERE id = ? AND teacher_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $slotId, $teacherId);
        mysqli_stmt_execute($stmt);

        return [
            'status' => 'success',
            'message' => trans('consultation_reset_available'),
            'data' => get_consultation_slot_details($con, $slotId)
        ];
    }
}

if (!function_exists('teacherDeleteConsultationSlot')) {
    function teacherDeleteConsultationSlot($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? 0);
        $body = read_json_body();
        $slotId = (int)($_GET['id'] ?? $body['slot_id'] ?? $body['id'] ?? 0);
        if ($slotId <= 0) return ['status' => 'error', 'message' => trans('slot_id_required')];

        $stmt = mysqli_prepare($con, "DELETE FROM consultation_slots WHERE id = ? AND teacher_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $slotId, $teacherId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to delete slot'];
        }

        return ['status' => 'success', 'message' => trans('consultation_deleted_success')];
    }
}

if (!function_exists('studentListAvailableConsultationTeachers')) {
    function studentListAvailableConsultationTeachers($con, $studentData = null) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $subjectId = (int)($_GET['subject_id'] ?? 0);

        $where = ["cs.is_booked = 0", "cs.is_active = 1", "cs.slot_date >= CURDATE()", "(cs.status = 'available' OR cs.status IS NULL)"];
        $params = [];
        $types = "";

        if ($subjectId > 0) {
            $where[] = "cs.subject_id = ?";
            $params[] = $subjectId;
            $types .= "i";
        }
        if (!empty($search)) {
            $pattern = '%' . $search . '%';
            $where[] = "(t.name LIKE ? OR t.specialization LIKE ?)";
            $params[] = $pattern;
            $params[] = $pattern;
            $types .= "ss";
        }

        apply_consultation_date_filters($where, $params, $types, 'cs');
        apply_consultation_type_filter($where, $params, $types, 'cs');

        $whereClause = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(DISTINCT cs.teacher_id) AS total FROM consultation_slots cs LEFT JOIN teachers t ON cs.teacher_id = t.id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT DISTINCT cs.teacher_id FROM consultation_slots cs LEFT JOIN teachers t ON cs.teacher_id = t.id {$whereClause} ORDER BY cs.teacher_id ASC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($con, $sql);
        $bindTypes = $types . "ii";
        $bindParams = array_merge($params, [$limit, $offset]);
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $teacherId = (int)$row['teacher_id'];
            $teacher = get_teacher_details($con, $teacherId);
            if ($teacher) {
                $cntStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM consultation_slots WHERE teacher_id = ? AND is_booked = 0 AND is_active = 1 AND slot_date >= CURDATE() AND (status = 'available' OR status IS NULL)");
                mysqli_stmt_bind_param($cntStmt, "i", $teacherId);
                mysqli_stmt_execute($cntStmt);
                $teacher['available_slots_count'] = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cntStmt))['total'] ?? 0);

                $subjRes = mysqli_query($con, "SELECT DISTINCT subject_id FROM consultation_slots WHERE teacher_id = {$teacherId} AND is_booked = 0 AND is_active = 1 AND slot_date >= CURDATE() AND subject_id IS NOT NULL");
                $subjects = [];
                if ($subjRes) {
                    while ($sRow = mysqli_fetch_assoc($subjRes)) {
                        $sDetails = get_subject_details($con, (int)$sRow['subject_id']);
                        if ($sDetails) $subjects[] = $sDetails;
                    }
                }
                $teacher['subjects'] = $subjects;

                $items[] = format_response_images($teacher);
            }
        }

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('studentListAvailableConsultationSlots')) {
    function studentListAvailableConsultationSlots($con, $studentData = null) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        $meetingType = strtolower(trim($_GET['meeting_type'] ?? ''));
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $where = ["cs.is_booked = 0", "cs.is_active = 1", "cs.slot_date >= CURDATE()", "(cs.status = 'available' OR cs.status IS NULL)"];
        $params = [];
        $types = "";

        if ($teacherId > 0) { $where[] = "cs.teacher_id = ?"; $params[] = $teacherId; $types .= "i"; }
        if ($subjectId > 0) { $where[] = "cs.subject_id = ?"; $params[] = $subjectId; $types .= "i"; }
        if (!empty($meetingType)) { $where[] = "cs.meeting_type = ?"; $params[] = $meetingType; $types .= "s"; }
        if (!empty($search)) {
            $pattern = '%' . $search . '%';
            $where[] = "(t.name LIKE ? OR cs.notes LIKE ?)";
            $params[] = $pattern;
            $params[] = $pattern;
            $types .= "ss";
        }

        apply_consultation_date_filters($where, $params, $types, 'cs');
        apply_consultation_type_filter($where, $params, $types, 'cs');

        $whereClause = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) AS total FROM consultation_slots cs LEFT JOIN teachers t ON cs.teacher_id = t.id {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT cs.id FROM consultation_slots cs LEFT JOIN teachers t ON cs.teacher_id = t.id {$whereClause} ORDER BY cs.slot_date ASC, cs.slot_time ASC LIMIT ? OFFSET ?";
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

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}

if (!function_exists('studentBookConsultationSlot')) {
    function studentBookConsultationSlot($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $slotId = (int)($body['slot_id'] ?? 0);
        if ($slotId <= 0) return ['status' => 'error', 'message' => trans('slot_id_required')];

        $stmt = mysqli_prepare($con, "UPDATE consultation_slots SET is_booked = 1, student_id = ?, booked_at = NOW(), status = 'pending' WHERE id = ? AND (is_booked = 0 OR status = 'available') AND is_active = 1");
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $slotId);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return ['status' => 'error', 'message' => trans('slot_no_longer_available')];
        }

        $slotDetails = get_consultation_slot_details($con, $slotId);

        return [
            'status' => 'success',
            'message' => trans('slot_booked_awaiting_approval'),
            'data' => [
                'is_booked' => true,
                'status' => 'pending',
                'booking' => $slotDetails
            ]
        ];
    }
}

if (!function_exists('studentCancelConsultationBooking')) {
    function studentCancelConsultationBooking($con, $studentData) {
        $studentId = (int)($studentData['id'] ?? 0);
        $body = read_json_body();
        $slotId = (int)($_GET['id'] ?? $body['slot_id'] ?? $body['id'] ?? 0);
        if ($slotId <= 0) return ['status' => 'error', 'message' => trans('slot_id_required')];

        $stmt = mysqli_prepare($con, "UPDATE consultation_slots SET is_booked = 0, student_id = NULL, booked_at = NULL, status = 'available' WHERE id = ? AND student_id = ? AND is_booked = 1");
        mysqli_stmt_bind_param($stmt, "ii", $slotId, $studentId);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            return ['status' => 'error', 'message' => trans('booking_not_found_or_cancelled')];
        }

        return [
            'status' => 'success',
            'message' => trans('consultation_cancelled_success'),
            'data' => get_consultation_slot_details($con, $slotId)
        ];
    }
}

if (!function_exists('studentListMyBookedConsultations')) {
    function studentListMyBookedConsultations($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($studentId <= 0) {
            $studentId = (int)($_GET['student_id'] ?? 0);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        if ($studentId <= 0) {
            return build_paginated_response([], $page, $limit, 0);
        }

        $status = trim($_GET['status'] ?? '');

        $where = ["student_id = ?"];
        $params = [$studentId];
        $types = "i";

        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }

        apply_consultation_date_filters($where, $params, $types);
        apply_consultation_type_filter($where, $params, $types);

        $whereClause = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) AS total FROM consultation_slots {$whereClause}";
        $countStmt = mysqli_prepare($con, $countSql);
        if (!empty($types)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);

        $sql = "SELECT id FROM consultation_slots {$whereClause} ORDER BY booked_at DESC, slot_date ASC LIMIT ? OFFSET ?";
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

        return build_paginated_response($items, $page, $limit, $totalRecords);
    }
}
