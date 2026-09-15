<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';
require_once __DIR__ . '/../../../helpers/entities.php';
require_once __DIR__ . '/../../../helpers/hr_policy.php';
require_once __DIR__ . '/../../content/helpers.php';
require_once __DIR__ . '/../auth/profile.php';
require_once __DIR__ . '/../sessions/manage.php';
require_once __DIR__ . '/../courses/manage.php';
require_once __DIR__ . '/../consultations/manage.php';

if (!function_exists('teacherHome')) {
    /**
     * Full teacher dashboard "home" screen: profile summary, real KPIs, today's schedule,
     * everything currently waiting on the teacher's action (grading, reschedule requests,
     * consultation bookings, their own pending exception/appeal requests), wallet snapshot,
     * a preview of their courses, upcoming consultations, and recent notifications.
     * Every number here is computed live from the DB — no placeholder/demo values.
     */
    function teacherHome($con, $teacherData) {
        $teacherId = (int)($teacherData['id'] ?? $teacherData['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, image, specialization, biography, description, rating, is_active, is_attendance_suspended FROM teachers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $teacher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$teacher) {
            return ['status' => 'error', 'message' => trans('teacher_not_found')];
        }

        $teacher['id'] = (int)$teacher['id'];
        $teacher['rating'] = (float)$teacher['rating'];
        $teacher['is_active'] = (int)$teacher['is_active'];
        $teacher['is_attendance_suspended'] = (int)$teacher['is_attendance_suspended'];
        $teacher['bio'] = !empty($teacher['biography']) ? $teacher['biography'] : (!empty($teacher['description']) ? $teacher['description'] : '');
        unset($teacher['biography'], $teacher['description']);
        $teacher['suspension_reason'] = $teacher['is_attendance_suspended'] === 1 ? trans('teacher_suspension_reason') : null;

        $reviewsRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM teacher_ratings WHERE teacher_id = {$teacherId} AND is_approved = 1");
        $teacher['reviews_count'] = (int)(mysqli_fetch_assoc($reviewsRes)['total'] ?? 0);

        $teacher['subjects'] = get_teacher_subjects_details($con, $teacherId);
        $teacher = format_response_images($teacher);

        // ---- Wallet (Section 10: attendance/salary system) --------------------------------
        $walletRes = @mysqli_query($con, "SELECT wallet_balance FROM teachers WHERE id = {$teacherId} LIMIT 1");
        $walletBalance = (float)(mysqli_fetch_assoc($walletRes)['wallet_balance'] ?? 0);
        $payRate = get_teacher_pay_rate($con, $teacherId);

        // ---- Real KPIs (same accurate "who is this teacher's student" logic used elsewhere,
        // no demo fallback numbers) ---------------------------------------------------------
        $totalStudents = 0;
        $stRes = @mysqli_query($con, "
            SELECT COUNT(DISTINCT ss.student_id) AS total
            FROM student_subscriptions ss
            LEFT JOIN student_subscription_teachers sst ON ss.id = sst.student_subscription_id
            LEFT JOIN student_subscription_courses ssc ON ss.id = ssc.student_subscription_id
            LEFT JOIN courses c ON ssc.course_id = c.id
            LEFT JOIN student_subscription_subjects sss ON ss.id = sss.student_subscription_id
            LEFT JOIN teacher_subjects ts ON sss.subject_id = ts.subject_id
            WHERE ss.is_active = 1
              AND (
                sst.teacher_id = {$teacherId}
                OR c.teacher_id = {$teacherId}
                OR (c.created_by_type = 'teacher' AND c.created_by_id = {$teacherId})
                OR ts.teacher_id = {$teacherId}
              )
        ");
        if ($stRes) $totalStudents = (int)(mysqli_fetch_assoc($stRes)['total'] ?? 0);

        // Same ownership rule adminListCourses() uses for its `teacher_id` filter — kept as a
        // plain COUNT here (teacherListMyCourses is used further down for the actual list).
        $coursesCountRes = @mysqli_query($con, "
            SELECT COUNT(DISTINCT c.id) AS total FROM courses c
            LEFT JOIN course_teachers ct ON c.id = ct.course_id
            WHERE c.is_active = 1 AND (
                c.teacher_id = {$teacherId}
                OR (ct.teacher_id = {$teacherId} AND ct.is_active = 1)
                OR (c.created_by_type = 'teacher' AND c.created_by_id = {$teacherId})
            )
        ");
        $totalCourses = (int)(mysqli_fetch_assoc($coursesCountRes)['total'] ?? 0);

        $activeSessionsRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM class_sessions WHERE teacher_id = {$teacherId} AND is_active = 1");
        $totalActiveSessions = (int)(mysqli_fetch_assoc($activeSessionsRes)['total'] ?? 0);

        $unreadNotifRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM notification_recipients WHERE user_type = 'teacher' AND user_id = {$teacherId} AND is_read = 0");
        $unreadNotificationsCount = (int)(mysqli_fetch_assoc($unreadNotifRes)['total'] ?? 0);

        // ---- Today's schedule (reuses the exact same session-detail builder the teacher's own
        // schedule screen uses, so status/is_available_now/meeting_link/students_count are
        // guaranteed to match) ----------------------------------------------------------------
        $now = get_request_now();
        $today = $now->format('l');
        $todaySessions = [];
        $todaySessRes = @mysqli_query($con, "SELECT id FROM class_sessions WHERE teacher_id = {$teacherId} AND is_active = 1 AND day_of_week = '{$today}' ORDER BY start_time ASC");
        if ($todaySessRes) {
            while ($row = mysqli_fetch_assoc($todaySessRes)) {
                $details = get_class_session_details($con, (int)$row['id']);
                if ($details) $todaySessions[] = $details;
            }
        }

        // ---- Pending actions: everything currently waiting on this teacher --------------------
        $pendingHomeworkGrading = 0;
        $hwRes = @mysqli_query($con, "
            SELECT COUNT(*) AS total FROM homework_submissions hs
            JOIN homework h ON hs.homework_id = h.id
            WHERE h.teacher_id = {$teacherId} AND hs.status = 'submitted'
        ");
        if ($hwRes) $pendingHomeworkGrading = (int)(mysqli_fetch_assoc($hwRes)['total'] ?? 0);

        // Mirrors teacher_owns_exam()'s ownership rule (app/teacher/exams/attempts.php) exactly —
        // keep the two in sync if that rule ever changes.
        $pendingExamGrading = 0;
        $examRes = @mysqli_query($con, "
            SELECT COUNT(*) AS total FROM exam_attempts ea
            JOIN course_exams ce ON ea.exam_id = ce.id
            WHERE ea.status = 'submitted' AND (
                (ce.created_by_type = 'teacher' AND ce.created_by_id = {$teacherId})
                OR ce.id IN (SELECT exam_id FROM exam_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR ce.course_id IN (SELECT id FROM courses WHERE teacher_id = {$teacherId} OR (created_by_type = 'teacher' AND created_by_id = {$teacherId}))
                OR ce.course_id IN (SELECT course_id FROM course_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR ce.unit_id IN (SELECT id FROM units WHERE (created_by_type = 'teacher' AND created_by_id = {$teacherId}))
                OR ce.unit_id IN (SELECT unit_id FROM unit_teachers WHERE teacher_id = {$teacherId} AND is_active = 1)
                OR ce.subject_id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id = {$teacherId})
                OR (ce.created_by_type = 'admin' AND NOT EXISTS (SELECT 1 FROM exam_teachers et_chk WHERE et_chk.exam_id = ce.id AND et_chk.is_active = 1))
            )
        ");
        if ($examRes) $pendingExamGrading = (int)(mysqli_fetch_assoc($examRes)['total'] ?? 0);

        $incomingRescheduleRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM session_reschedule_requests WHERE teacher_id = {$teacherId} AND requested_by_type = 'student' AND status = 'pending'");
        $incomingRescheduleCount = (int)(mysqli_fetch_assoc($incomingRescheduleRes)['total'] ?? 0);

        $myExceptionRequestsRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM attendance_exception_requests WHERE requested_by_type = 'teacher' AND requested_by_id = {$teacherId} AND status = 'pending'");
        $myExceptionRequestsPending = (int)(mysqli_fetch_assoc($myExceptionRequestsRes)['total'] ?? 0);

        $myAppealsRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM teacher_deduction_appeals WHERE teacher_id = {$teacherId} AND status = 'pending'");
        $myAppealsPending = (int)(mysqli_fetch_assoc($myAppealsRes)['total'] ?? 0);

        $consultationRequestsRes = @mysqli_query($con, "SELECT COUNT(*) AS total FROM consultation_slots WHERE teacher_id = {$teacherId} AND is_booked = 1 AND status = 'pending'");
        $consultationRequestsPending = (int)(mysqli_fetch_assoc($consultationRequestsRes)['total'] ?? 0);

        $pendingActions = [
            'pending_homework_grading_count' => $pendingHomeworkGrading,
            'pending_exam_grading_count' => $pendingExamGrading,
            'incoming_reschedule_requests_count' => $incomingRescheduleCount,
            'my_attendance_exception_requests_pending_count' => $myExceptionRequestsPending,
            'my_deduction_appeals_pending_count' => $myAppealsPending,
            'consultation_requests_pending_count' => $consultationRequestsPending,
        ];
        $pendingActionsTotal = array_sum($pendingActions);
        $pendingActions['total_count'] = $pendingActionsTotal;

        // ---- My courses preview (reuses the real, battle-tested course listing/ownership logic
        // instead of re-deriving it — same source of truth as the teacher's own courses screen) ---
        $savedGet = $_GET;
        $_GET = ['page' => 1, 'limit' => 6];
        $myCoursesResponse = teacherListMyCourses($con, $teacherData);
        $_GET = $savedGet;
        $myCourses = is_array($myCoursesResponse['data'] ?? null) ? $myCoursesResponse['data'] : [];
        foreach ($myCourses as &$course) {
            $cId = (int)($course['id'] ?? 0);
            $sCountRes = @mysqli_query($con, "SELECT COUNT(DISTINCT ss.student_id) AS total FROM student_subscription_courses ssc JOIN student_subscriptions ss ON ssc.student_subscription_id = ss.id WHERE ssc.course_id = {$cId} AND ss.is_active = 1");
            $course['students_count'] = (int)(mysqli_fetch_assoc($sCountRes)['total'] ?? 0);
        }
        unset($course);

        // ---- Upcoming consultations (approved + booked, from today onward) --------------------
        $todayDate = $now->format('Y-m-d');
        $upcomingConsultations = [];
        $consultRes = @mysqli_query($con, "
            SELECT id FROM consultation_slots
            WHERE teacher_id = {$teacherId} AND is_booked = 1 AND status = 'approved' AND slot_date >= '{$todayDate}'
            ORDER BY slot_date ASC, slot_time ASC
            LIMIT 5
        ");
        if ($consultRes) {
            while ($row = mysqli_fetch_assoc($consultRes)) {
                $slot = get_consultation_slot_details($con, (int)$row['id']);
                if ($slot) $upcomingConsultations[] = $slot;
            }
        }

        // ---- Recent notifications preview ------------------------------------------------------
        $recentNotifications = [];
        $notifRes = @mysqli_query($con, "
            SELECT nr.id AS recipient_id, nr.is_read, n.sender_type, n.title, n.body, n.data, n.created_at
            FROM notification_recipients nr
            JOIN notifications n ON nr.notification_id = n.id
            WHERE nr.user_type = 'teacher' AND nr.user_id = {$teacherId}
            ORDER BY nr.id DESC
            LIMIT 5
        ");
        if ($notifRes) {
            while ($row = mysqli_fetch_assoc($notifRes)) {
                $row['id'] = (int)$row['recipient_id'];
                unset($row['recipient_id']);
                $row['is_read'] = (int)$row['is_read'];
                $row['data'] = !empty($row['data']) ? json_decode($row['data'], true) : null;
                $recentNotifications[] = $row;
            }
        }

        return [
            'status' => 'success',
            'message' => 'Teacher home data fetched successfully',
            'data' => [
                'greeting' => 'أهلاً بك',
                'teacher' => $teacher,
                'kpis' => [
                    'total_students' => $totalStudents,
                    'total_courses' => $totalCourses,
                    'total_subjects' => count($teacher['subjects']),
                    'total_active_sessions' => $totalActiveSessions,
                    'wallet_balance' => $walletBalance,
                    'unread_notifications_count' => $unreadNotificationsCount,
                    'pending_actions_count' => $pendingActionsTotal,
                ],
                'wallet' => [
                    'wallet_balance' => $walletBalance,
                    'pay_rate' => $payRate,
                ],
                'today' => [
                    'date' => $todayDate,
                    'day_of_week' => $today,
                    'sessions_count' => count($todaySessions),
                    'sessions' => $todaySessions,
                ],
                'pending_actions' => $pendingActions,
                'my_courses' => $myCourses,
                'upcoming_consultations' => $upcomingConsultations,
                'recent_notifications' => $recentNotifications,
            ],
        ];
    }
}
