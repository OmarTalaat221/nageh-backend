<?php
/**
 * Every permission the dashboard has, in one place.
 *
 * Until now a permission existed because somebody wrote an INSERT in a migration. That meant a new
 * section of the dashboard needed a page, a sidebar entry, a migration, a translation insert, and
 * a trip to phpMyAdmin - and if any of those was skipped the result was silent. It is how
 * Packages and Certificates ended up with no checkbox at all, how two permissions for screens that
 * do not exist ended up on the assignment form, and how `view_dashboard` came to be labelled
 * "إدارة المسؤولين / Manage Admins", which is a different permission's name entirely.
 *
 * Now the list lives here, beside the routes that enforce it, and `permissions_sync()` makes the
 * table match. Adding a section is one line here and one line in the sidebar.
 *
 * WHAT THIS IS NOT: the frontend cannot own this list. A permission is real only because a route
 * checks it - `requirePermission($con, $adminData, 'manage_packages')` - so a code invented on the
 * client would render a checkbox that grants nothing, which is worse than a missing one. The
 * sidebar names the permission it needs; this file decides whether that permission exists.
 */

if (!function_exists('permissions_catalogue')) {

    /**
     * code => [ar, en, module]
     *
     * `module` is the permission's own slug, which the list is ordered by. The Arabic name must match the
     * sidebar label word for word: a checkbox named something the sidebar does not say cannot be
     * mapped back to a screen by whoever is ticking it.
     */
    function permissions_catalogue(): array {
        return [
            // ---- overview -------------------------------------------------------------------
            'view_dashboard' => ['عرض لوحة التحكم والإحصائيات', 'View Dashboard & Statistics', 'dashboard'],

            // ---- people ---------------------------------------------------------------------
            'manage_students' => ['إدارة الطلاب', 'Manage Students', 'students'],
            'manage_teachers' => ['إدارة المدرسين', 'Manage Teachers', 'teachers'],
            'manage_parents'  => ['إدارة أولياء الأمور', 'Manage Parents & Student Linkage', 'parents'],
            'manage_admins'   => ['إدارة المشرفين والصلاحيات', 'Manage Admins & Permissions', 'admins'],
            // No sidebar row of its own - the Admins screen calls /admin/roles - but it must stay
            // grantable or that screen cannot be delegated.
            'manage_roles'    => ['إدارة الأدوار', 'Manage Roles', 'roles'],

            // ---- academic -------------------------------------------------------------------
            'manage_academic_years'    => ['إدارة الصفوف الدراسية', 'Manage Grades (Academic Years)', 'academic_years'],
            'manage_subjects'          => ['إدارة المواد الدراسية', 'Manage Subjects', 'subjects'],
            'manage_course_categories' => ['إدارة تصنيفات الكورسات', 'Manage Course Categories', 'course_categories'],
            'manage_courses'           => ['إدارة الكورسات', 'Manage Courses', 'courses'],
            'manage_packages'          => ['إدارة الباقات', 'Manage Packages', 'packages'],
            'manage_exams'             => ['إدارة الامتحانات', 'Manage Exams', 'exams'],
            'manage_certificates'      => ['إدارة الشهادات', 'Manage Certificates', 'certificates'],
            'manage_books'             => ['إدارة الكتب وطلبات الشراء', 'Manage Books & Purchase Requests', 'books'],

            // ---- money ----------------------------------------------------------------------
            'view_sales'           => ['المبيعات', 'Sales', 'sales'],
            'manage_subscriptions' => ['إدارة اشتراكات الطلاب وأكواد الاشتراك', 'Manage Subscriptions & Codes', 'subscriptions'],
            'manage_discounts'     => ['إدارة أكواد الخصم', 'Manage Discount Codes', 'discounts'],
            'manage_payments'      => ['إدارة المدفوعات اليدوية', 'Manage Offline Payments', 'payments'],
            'manage_pricing'       => ['إدارة الأسعار والعملات', 'Manage Pricing & Currencies', 'pricing'],
            'manage_wallets'       => ['إدارة محافظ المدرسين والتظلمات', 'Manage Teacher Wallets & Appeals', 'wallets'],

            // ---- operations -----------------------------------------------------------------
            'manage_sessions'      => ['إدارة الحصص المباشرة والمواعيد', 'Manage Live Sessions & Availability', 'sessions'],
            'manage_consultations' => ['إدارة الاستشارات', 'Manage Consultations', 'consultations'],
            'manage_attendance'    => ['إدارة الحضور والاستثناءات', 'Manage Attendance & Exceptions', 'attendance'],
            // Renamed from "إدارة إعدادات الموارد البشرية" / "Manage HR Settings" - that named a
            // subject (human resources) this screen has nothing to do with. The sidebar's own
            // label for /settings is "إعدادات الحضور والرواتب"; the checkbox now says the same thing.
            'manage_hr'            => ['إدارة إعدادات الحضور والرواتب', 'Manage Attendance & Salary Settings', 'hr'],

            // ---- communication ----------------------------------------------------------------
            'manage_reports'       => ['إدارة التقارير', 'Manage Reports', 'reports'],
            'manage_notifications' => ['إدارة الإشعارات', 'Manage Notifications', 'notifications'],
            // Deliberately narrower than "manage": every admin already reads their OWN notification
            // inbox regardless of this permission - it gates only the broadcast/history screen
            // (/najeh/admin/notifications, the send+history route), never /notifications/inbox.
            'manage_chat'          => ['إدارة المحادثات', 'Manage Chat', 'chat'],
            // Chat has no backend route of its own - the dashboard talks to Firebase directly, so
            // nothing here has ever mediated who could open it. /admin/chat/access and
            // /admin/chat/monitor/access exist solely to give these two codes something real to
            // check, matching the module's own rule that a permission must be enforced by a route.
            // NOTE the residual gap this does not close: Firebase's rules are not scoped per
            // permission, so this stops the dashboard UI path, not a client that talks to Firebase
            // directly with valid credentials. Real closure needs Firebase custom claims + rules.
            'monitor_chat'         => ['مراقبة المحادثات', 'Monitor Chat', 'chat_monitor'],

            // ---- platform settings ----------------------------------------------------------
            'manage_cities'    => ['إدارة المناطق', 'Manage Regions', 'cities'],
            'manage_languages' => ['إدارة اللغات', 'Manage Languages', 'languages'],
            'manage_banners'   => ['إدارة البانرات والإحصائيات', 'Manage Banners & Analytics', 'banners'],
            'manage_settings'  => ['إدارة إعدادات التطبيق', 'Manage Application Settings', 'app_settings'],
        ];
    }
}

if (!function_exists('permissions_sync')) {

    /**
     * Make the `permissions` table match the catalogue above.
     *
     * Additive and reversible by design:
     *   - a code in the catalogue is inserted, or has its name and module corrected;
     *   - a code NOT in the catalogue is hidden, never deleted. Deleting would cascade into
     *     `admin_permissions` and silently change what existing admins can do, which is the kind
     *     of change nobody notices until somebody cannot open a page weeks later;
     *   - `admin_permissions` is never touched. A new permission starts granted to nobody, which
     *     is the only safe default - a section that appears should not open itself to every admin
     *     who happens to hold a nearby permission. A super admin sees it regardless, because
     *     hasPermission() answers true for them before it looks anything up.
     *
     * Runs only when the catalogue has actually changed, compared by hash, so the usual request
     * does one cheap SELECT and stops.
     */
    function permissions_sync($con, bool $force = false): array {
        $catalogue = permissions_catalogue();
        $hash = md5(json_encode($catalogue, JSON_UNESCAPED_UNICODE));

        $stored = null;
        $res = @mysqli_query($con, "SELECT setting_value FROM site_settings WHERE setting_key = 'permissions_catalogue_hash' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $stored = $row['setting_value'];
        }

        if (!$force && $stored === $hash) {
            return ['synced' => false, 'added' => [], 'updated' => [], 'hidden' => []];
        }

        $added = [];
        $updated = [];

        foreach ($catalogue as $code => [$ar, $en, $module]) {
            $existing = mysqli_fetch_assoc(mysqli_query(
                $con,
                "SELECT id FROM permissions WHERE code = '" . mysqli_real_escape_string($con, $code) . "' LIMIT 1"
            ));

            $stmt = mysqli_prepare($con, "
                INSERT INTO permissions (code, name, module, description)
                VALUES (?, ?, ?, '')
                ON DUPLICATE KEY UPDATE name = VALUES(name), module = VALUES(module)
            ");

            if (!$stmt) continue;

            mysqli_stmt_bind_param($stmt, "sss", $code, $en, $module);
            mysqli_stmt_execute($stmt);

            $permId = $existing ? (int)$existing['id'] : mysqli_insert_id($con);
            if ($permId <= 0) continue;

            $existing ? $updated[] = $code : $added[] = $code;

            // is_visible only exists after migration 082. Everything else here works without it.
            @mysqli_query($con, "UPDATE permissions SET is_visible = 1 WHERE id = {$permId}");

            foreach ([1 => $ar, 2 => $en] as $langId => $name) {
                $tStmt = mysqli_prepare($con, "
                    INSERT INTO permissions_translations (permission_id, language_id, name)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE name = VALUES(name)
                ");

                if (!$tStmt) continue;

                mysqli_stmt_bind_param($tStmt, "iis", $permId, $langId, $name);

                if (!mysqli_stmt_execute($tStmt)) {
                    // No unique key on (permission_id, language_id) on some installs, so the
                    // upsert cannot fire. Fall back to an explicit update-or-insert.
                    $has = mysqli_fetch_assoc(mysqli_query($con, "
                        SELECT id FROM permissions_translations
                        WHERE permission_id = {$permId} AND language_id = {$langId} LIMIT 1
                    "));
                    $safe = mysqli_real_escape_string($con, $name);
                    mysqli_query($con, $has
                        ? "UPDATE permissions_translations SET name = '{$safe}' WHERE id = " . (int)$has['id']
                        : "INSERT INTO permissions_translations (permission_id, language_id, name) VALUES ({$permId}, {$langId}, '{$safe}')");
                }
            }
        }

        // Anything the catalogue no longer names.
        $hidden = [];
        $codes = array_map(fn($c) => "'" . mysqli_real_escape_string($con, $c) . "'", array_keys($catalogue));
        $list = implode(',', $codes);
        $res = @mysqli_query($con, "SELECT code FROM permissions WHERE code NOT IN ({$list}) AND is_visible = 1");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) $hidden[] = $row['code'];
            @mysqli_query($con, "UPDATE permissions SET is_visible = 0 WHERE code NOT IN ({$list})");
        }

        $safeHash = mysqli_real_escape_string($con, $hash);
        @mysqli_query($con, "
            INSERT INTO site_settings (setting_key, setting_value)
            VALUES ('permissions_catalogue_hash', '{$safeHash}')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        return ['synced' => true, 'added' => $added, 'updated' => $updated, 'hidden' => $hidden];
    }
}
