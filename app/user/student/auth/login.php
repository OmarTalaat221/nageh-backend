<?php
require_once __DIR__ . '/../../../../helpers/entities.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../../helpers/devices.php';
require_once __DIR__ . '/../../../../helpers/app_settings.php';
require_once __DIR__ . '/../../../../helpers/phone.php';
require_once __DIR__ . '/../subscriptions/manage.php';

if (!function_exists('studentLogin')) {
    function studentLogin($con) {
        $body = read_json_body();

        // One box on the screen, so one field here. `email` stays the name the app has always
        // sent; the others are accepted because a client written against a field literally called
        // `email` should not have to keep calling a phone number an email.
        $identifier = trim(
            $body['email']
            ?? $body['phone']
            ?? $body['identifier']
            ?? $body['login']
            ?? ''
        );
        $password = trim($body['password'] ?? '');

        if ($identifier === '' || $password === '') {
            return ['status' => 'error', 'message' => trans('email_password_required')];
        }

        $byPhone = phone_key_column_exists($con) && looks_like_phone($identifier);
        $email = $byPhone ? '' : strtolower($identifier);

        // Rate limiting is keyed on what was typed, normalised, so that trying one password
        // against a number in five different spellings still counts as five attempts.
        $attemptKey = $byPhone ? ('phone:' . phone_key($identifier)) : $email;

        // Before the password is read, so a locked-out attacker learns nothing from how long the
        // response takes or from which error comes back.
        $locked = login_attempt_guard($con, $attemptKey, 'student');
        if ($locked !== null) return $locked;

        $columns = "id, name, email, password, image, phone, age, school_id, city_id, is_email_verified, is_active";

        if ($byPhone) {
            // No LIMIT 1. A number that is on two accounts has to be recognised as ambiguous
            // rather than silently resolved to whichever row the table happens to return first -
            // that would lock the other student out of an account whose password is correct, and
            // tell them their password is wrong.
            $stmt = mysqli_prepare($con, "SELECT {$columns} FROM students WHERE phone_key = ?");
            $bind = phone_key($identifier);
        } else {
            $stmt = mysqli_prepare($con, "SELECT {$columns} FROM students WHERE email = ? LIMIT 1");
            $bind = $email;
        }

        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Database query preparation failed: ' . mysqli_error($con)];
        }

        mysqli_stmt_bind_param($stmt, "s", $bind);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $matches = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $matches[] = $row;
        }

        if (count($matches) > 1) {
            // Said plainly, because the student's password is fine and nothing they can type into
            // this box will work. Counted as a failure too - the number is a real identifier and
            // guessing against it must not be free.
            login_attempt_fail($con, $attemptKey, 'student');
            return [
                'status' => 'error',
                'message' => trans('phone_on_multiple_accounts'),
                'error_code' => 'phone_ambiguous',
            ];
        }

        $student = $matches[0] ?? null;

        // A wrong email counts too. Not counting it would leave the door open to spraying one
        // password across a list of addresses, which is the attack this limit exists to stop.
        if (!$student) {
            login_attempt_fail($con, $attemptKey, 'student');
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        if (!password_verify($password, $student['password'])) {
            login_attempt_fail($con, $attemptKey, 'student');
            return ['status' => 'error', 'message' => trans('invalid_credentials')];
        }

        // The password was right. Everything after this - unverified email, deactivated account,
        // device limit - is a legitimate user meeting a rule, not someone guessing, so the
        // counter is cleared here rather than at the end.
        login_attempt_reset($con, $attemptKey, 'student');

        if ((int)$student['is_email_verified'] !== 1) {
            return [
                'status' => 'error',
                'message' => trans('email_not_verified'),
                'requires_email_verification' => true,
                'email' => $student['email']
            ];
        }

        if ((int)$student['is_active'] !== 1) {
            return ['status' => 'error', 'message' => trans('account_inactive')];
        }

        // Device limiting runs only after the password has been accepted, so a wrong password can
        // never be used to discover how many devices an account has or to fill up its slots.
        $stId = (int)$student['id'];
        $device = device_register_login($con, $stId, $body);

        if (!$device['allowed']) {
            return [
                'status' => 'error',
                'message' => $device['message'],
                'error_code' => $device['code'],
                'max_devices' => $device['max_devices'] ?? null,
                'active_devices' => $device['active_devices'] ?? null,
            ];
        }

        $payload = [
            'id' => $stId,
            'name' => $student['name'],
            'email' => $student['email'],
            'role' => 'student'
        ];

        // Carried in the token so every later request can be checked against the device that was
        // authorised - that is what makes an admin reset take effect immediately.
        if (!empty($device['device_id'])) {
            $payload['did'] = $device['device_id'];
        }

        $tokens = generateStudentTokens($payload);

        $cityId = $student['city_id'] ? (int)$student['city_id'] : null;
        $schoolId = $student['school_id'] ? (int)$student['school_id'] : null;

        unset($student['password']);

        $student['id'] = $stId;
        $student['city_id'] = $cityId;
        $student['school_id'] = $schoolId;
        $student['age'] = $student['age'] ? (int)$student['age'] : null;
        $student['is_email_verified'] = (int)$student['is_email_verified'];
        $student['is_active'] = (int)$student['is_active'];
        $student['city'] = get_city_details($con, $cityId);
        $student['school'] = get_school_details($con, $schoolId);
        $student['parents'] = get_student_parents_details($con, $stId);
        $student['parent_ids'] = array_values(array_map(function($p) { return (int)$p['id']; }, $student['parents']));

        $accessible = get_student_accessible_entities($con, $stId);
        $student['is_subscribed'] = !empty($accessible['course_ids']);

        return [
            'status' => 'success',
            'message' => trans('student_login_success'),
            'data' => [
                'student' => format_response_images($student),
                'tokens' => $tokens
            ]
        ];
    }
}
