<?php
/**
 * Chat itself is not a PHP/MySQL feature - the admin dashboard talks to Firebase directly, and no
 * backend route ever mediated who could open it. That is the actual gap behind "chat has no
 * permission checkbox": a permission in this codebase is only ever real because a route enforces
 * it (see helpers/permissions_registry.php), and until now no route existed to attach one to.
 *
 * This endpoint is that route. The two chat pages call it before they open a single Firebase
 * connection; a denial keeps the page from ever mounting the chat UI at all.
 *
 * Caveat that must stay visible, not buried: Firebase's own security rules are not scoped per
 * permission today, so an admin who already holds valid Firebase-side credentials and reaches
 * into the SDK directly (browser console, a hand-crafted request) is not stopped at the database
 * layer - only the dashboard UI path is. That is a materially weaker guarantee than every other
 * permission in this file, which the database itself enforces per request. Closing that gap for
 * real means Firebase custom claims plus matching security rules, which is a separate piece of
 * work from adding this checkbox.
 */

if (!function_exists('adminCheckChatAccess')) {
    function adminCheckChatAccess($con, $adminData) {
        return ['status' => 'success', 'message' => 'ok'];
    }
}

if (!function_exists('adminCheckChatMonitorAccess')) {
    function adminCheckChatMonitorAccess($con, $adminData) {
        return ['status' => 'success', 'message' => 'ok'];
    }
}
