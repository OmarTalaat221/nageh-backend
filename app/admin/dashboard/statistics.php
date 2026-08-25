<?php
require_once __DIR__ . '/../../../helpers/language.php';

if (!function_exists('adminDashboardStatistics')) {
    function adminDashboardStatistics($con) {
        $adminsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM admins");
        $admins = mysqli_fetch_assoc($adminsRes);

        $teachersRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS pending FROM teachers");
        $teachers = mysqli_fetch_assoc($teachersRes);

        $studentsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN is_email_verified = 1 THEN 1 ELSE 0 END) AS verified FROM students");
        $students = mysqli_fetch_assoc($studentsRes);

        $parentsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM parents");
        $parents = mysqli_fetch_assoc($parentsRes);

        $schoolsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM schools");
        $schools = mysqli_fetch_assoc($schoolsRes);

        $subjectsRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM subjects");
        $subjects = mysqli_fetch_assoc($subjectsRes);

        $citiesRes = mysqli_query($con, "SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active FROM cities");
        $cities = mysqli_fetch_assoc($citiesRes);

        return [
            'status' => 'success',
            'data' => [
                'statistics' => [
                    'admins' => [
                        'total' => (int)($admins['total'] ?? 0),
                        'active' => (int)($admins['active'] ?? 0)
                    ],
                    'teachers' => [
                        'total' => (int)($teachers['total'] ?? 0),
                        'active' => (int)($teachers['active'] ?? 0),
                        'pending' => (int)($teachers['pending'] ?? 0)
                    ],
                    'students' => [
                        'total' => (int)($students['total'] ?? 0),
                        'active' => (int)($students['active'] ?? 0),
                        'email_verified' => (int)($students['verified'] ?? 0)
                    ],
                    'parents' => [
                        'total' => (int)($parents['total'] ?? 0),
                        'active' => (int)($parents['active'] ?? 0)
                    ],
                    'schools' => [
                        'total' => (int)($schools['total'] ?? 0),
                        'active' => (int)($schools['active'] ?? 0)
                    ],
                    'subjects' => [
                        'total' => (int)($subjects['total'] ?? 0),
                        'active' => (int)($subjects['active'] ?? 0)
                    ],
                    'cities' => [
                        'total' => (int)($cities['total'] ?? 0),
                        'active' => (int)($cities['active'] ?? 0)
                    ]
                ]
            ]
        ];
    }
}
