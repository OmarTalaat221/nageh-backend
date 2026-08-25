<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';

if (!function_exists('studentListLanguages')) {
    function studentListLanguages($con) {
        $sql = "SELECT id, code, name, is_default, is_active FROM languages WHERE is_active = 1 ORDER BY is_default DESC, id ASC";
        $res = mysqli_query($con, $sql);

        $languages = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['id'] = (int)$row['id'];
                $row['is_default'] = (int)$row['is_default'];
                $row['is_active'] = (int)$row['is_active'];
                $languages[] = $row;
            }
        }

        return [
            'status' => 'success',
            'data' => $languages
        ];
    }
}
