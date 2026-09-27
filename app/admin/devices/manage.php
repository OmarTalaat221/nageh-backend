<?php
require_once __DIR__ . '/../../../helpers/devices.php';

/**
 * Admin control over which devices an account may sign in from.
 *
 * The two things support actually needs when a student calls: see what is currently holding the
 * slots, and free them. Everything else here exists to answer "why can't I log in?" without
 * guessing - the device rows keep their last IP, user agent and last-seen time, and revoked rows
 * are kept rather than deleted so a repeat offender is visible as a repeat offender.
 */

if (!function_exists('adminListStudentDevices')) {
    function adminListStudentDevices($con) {
        $studentId = (int)($_GET['student_id'] ?? $_GET['id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'student_id is required'];
        }

        $stmt = mysqli_prepare($con, "SELECT id, name, email, max_devices FROM students WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $studentId);
        mysqli_stmt_execute($stmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$student) {
            return ['status' => 'error', 'message' => 'Student not found'];
        }

        $settings = device_settings($con);
        $devices = device_list($con, 'student', $studentId);
        $active = array_values(array_filter($devices, fn($d) => $d['is_active'] === 1));

        return [
            'status' => 'success',
            'data' => [
                'student' => [
                    'id' => (int)$student['id'],
                    'name' => $student['name'],
                    'email' => $student['email'],
                ],
                'devices' => $devices,
                'active_count' => count($active),
                // What actually applies to this student, and whether it came from their own
                // override or the platform default - support needs to see which knob to turn.
                'effective_limit' => device_limit_for_student($con, $studentId),
                'student_max_devices' => $student['max_devices'] !== null ? (int)$student['max_devices'] : null,
                'global_max_devices' => $settings['max_devices'],
                'enforcement_enabled' => $settings['enabled'],
            ],
        ];
    }
}

/**
 * Frees every slot at once. This is the button support presses; the student signs in again on
 * whichever device they are actually holding, and any session still open elsewhere is ended on
 * its next request.
 */
if (!function_exists('adminResetStudentDevices')) {
    function adminResetStudentDevices($con, $adminData = null) {
        $body = read_json_body();
        $studentId = (int)($body['student_id'] ?? $body['id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'student_id is required'];
        }

        $adminId = (int)($adminData['id'] ?? 0) ?: null;
        $revoked = device_revoke($con, 'student', $studentId, null, $adminId);

        return [
            'status' => 'success',
            'message' => $revoked > 0
                ? "تم إلغاء تفعيل {$revoked} جهاز. الطالب يقدر يسجل دخول من جهاز جديد دلوقتي."
                : 'لا توجد أجهزة مفعّلة لهذا الطالب.',
            'data' => ['revoked_count' => $revoked],
        ];
    }
}

if (!function_exists('adminRevokeStudentDevice')) {
    function adminRevokeStudentDevice($con, $adminData = null) {
        $body = read_json_body();
        $studentId = (int)($body['student_id'] ?? 0);
        $deviceId = trim((string)($body['device_id'] ?? ''));

        if ($studentId <= 0 || $deviceId === '') {
            return ['status' => 'error', 'message' => 'student_id and device_id are required'];
        }

        $adminId = (int)($adminData['id'] ?? 0) ?: null;
        $revoked = device_revoke($con, 'student', $studentId, $deviceId, $adminId);

        if ($revoked === 0) {
            return ['status' => 'error', 'message' => 'الجهاز ده مش مفعّل أصلًا لهذا الطالب'];
        }

        return ['status' => 'success', 'message' => 'تم إلغاء تفعيل الجهاز'];
    }
}

/**
 * Raises or clears one student's ceiling. Sending null goes back to the platform default, which
 * is the right answer far more often than baking a number onto the student forever.
 */
if (!function_exists('adminSetStudentDeviceLimit')) {
    function adminSetStudentDeviceLimit($con) {
        $body = read_json_body();
        $studentId = (int)($body['student_id'] ?? $body['id'] ?? 0);
        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => 'student_id is required'];
        }

        $raw = $body['max_devices'] ?? null;
        $maxDevices = ($raw === null || $raw === '') ? null : (int)$raw;
        if ($maxDevices !== null && $maxDevices < 1) {
            return ['status' => 'error', 'message' => 'max_devices must be 1 or more, or null to use the platform default'];
        }

        $stmt = mysqli_prepare($con, "UPDATE students SET max_devices = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $maxDevices, $studentId);
        if (!mysqli_stmt_execute($stmt)) {
            return ['status' => 'error', 'message' => 'Failed to update the device limit: ' . mysqli_error($con)];
        }

        return [
            'status' => 'success',
            'message' => $maxDevices === null
                ? 'رجع الطالب للحد الافتراضي للمنصة'
                : "تم تحديد الحد الأقصى للطالب بـ {$maxDevices} جهاز",
            'data' => ['effective_limit' => device_limit_for_student($con, $studentId)],
        ];
    }
}
