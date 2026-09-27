<?php
require_once __DIR__ . '/../../../../helpers/devices.php';

/**
 * The student's own view of where their account is signed in.
 *
 * Deliberately read-only: a student cannot free their own slot, because being able to would make
 * the limit self-service and the shared-password problem would simply come back. Freeing a slot
 * goes through support, which is the point of the whole feature.
 */
if (!function_exists('studentListMyDevices')) {
    function studentListMyDevices($con, $studentData = null) {
        $studentId = 0;
        if (is_array($studentData)) {
            $studentId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $studentId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }

        if ($studentId <= 0) {
            return ['status' => 'error', 'message' => trans('unauthorized')];
        }

        $currentDeviceId = (string)($studentData['did'] ?? '');
        $devices = device_list($con, 'student', $studentId, true);

        foreach ($devices as &$device) {
            // So the UI can label one of them "هذا الجهاز" instead of showing a list of
            // indistinguishable rows.
            $device['is_current'] = $currentDeviceId !== '' && $device['device_id'] === $currentDeviceId;
            unset($device['user_agent'], $device['revoked_by_admin_id'], $device['revoked_at']);
        }
        unset($device);

        return [
            'status' => 'success',
            'data' => [
                'devices' => $devices,
                'active_count' => count($devices),
                'max_devices' => device_limit_for_student($con, $studentId),
            ],
        ];
    }
}
