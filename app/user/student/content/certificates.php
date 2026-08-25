<?php
require_once __DIR__ . '/../../../../helpers/functions.php';
require_once __DIR__ . '/../../../../helpers/language.php';
require_once __DIR__ . '/../../../../helpers/upload.php';
require_once __DIR__ . '/../../../content/helpers.php';
require_once __DIR__ . '/../subscriptions/manage.php';

if (!function_exists('studentGetMyCertificates')) {
    function studentGetMyCertificates($con, $studentData = null) {
        $stId = 0;
        if (is_array($studentData)) {
            $stId = (int)($studentData['id'] ?? $studentData['student_id'] ?? 0);
        } elseif (is_object($studentData)) {
            $stId = (int)($studentData->id ?? $studentData->student_id ?? 0);
        }
        if ($stId <= 0) {
            $stId = (int)($_GET['student_id'] ?? 0);
        }

        $academicYearId = null;
        $issuedCerts = [];

        if ($stId > 0) {
            $chkAy = @mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'academic_year_id'");
            if ($chkAy && mysqli_num_rows($chkAy) === 0) {
                @mysqli_query($con, "ALTER TABLE students ADD COLUMN `academic_year_id` bigint(20) UNSIGNED DEFAULT NULL");
            }
            $stStmt = mysqli_prepare($con, "SELECT academic_year_id FROM students WHERE id = ? LIMIT 1");
            if ($stStmt) {
                mysqli_stmt_bind_param($stStmt, "i", $stId);
                mysqli_stmt_execute($stStmt);
                $stRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stStmt));
                $academicYearId = $stRow['academic_year_id'] ? (int)$stRow['academic_year_id'] : null;
            }

            $issuedRes = mysqli_query($con, "
                SELECT sc.id AS student_certificate_id, sc.certificate_code, sc.issue_date, sc.grade_obtained, sc.pdf_url,
                       c.id AS certificate_id, c.academic_year_id, c.score, c.certificate_type, c.code, c.image,
                       COALESCE(ct.name, c.code) AS name, ct.description
                FROM student_certificates sc
                JOIN certificates c ON sc.certificate_id = c.id
                LEFT JOIN certificates_translations ct ON c.id = ct.certificate_id AND ct.language_id = 1
                WHERE sc.student_id = {$stId}
                ORDER BY sc.id DESC
            ");
            if ($issuedRes) {
                while ($row = mysqli_fetch_assoc($issuedRes)) {
                    $row['student_certificate_id'] = (int)$row['student_certificate_id'];
                    $row['certificate_id'] = (int)$row['certificate_id'];
                    $row['academic_year_id'] = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
                    $row['score'] = (float)$row['score'];
                    $row['grade_obtained'] = $row['grade_obtained'] ? (float)$row['grade_obtained'] : null;
                    $row['is_issued'] = true;
                    $issuedCerts[] = format_response_images($row);
                }
            }
        }

        $ayClause = "c.academic_year_id IS NULL";
        if ($academicYearId !== null) {
            $ayClause = "(c.academic_year_id IS NULL OR c.academic_year_id = {$academicYearId})";
        }

        $availableCerts = [];
        $availRes = mysqli_query($con, "
            SELECT c.id, c.academic_year_id, c.score, c.certificate_type, c.code, c.image, c.is_active, c.created_at,
                   COALESCE(ct.name, c.code) AS name, ct.description
            FROM certificates c
            LEFT JOIN certificates_translations ct ON c.id = ct.certificate_id AND ct.language_id = 1
            WHERE c.is_active = 1 AND {$ayClause}
            ORDER BY c.id DESC
        ");
        if ($availRes) {
            while ($row = mysqli_fetch_assoc($availRes)) {
                $row['id'] = (int)$row['id'];
                $row['academic_year_id'] = $row['academic_year_id'] ? (int)$row['academic_year_id'] : null;
                $row['score'] = (float)$row['score'];
                $row['is_active'] = (int)$row['is_active'];
                $row['is_issued'] = false;
                $availableCerts[] = format_response_images($row);
            }
        }

        return [
            'status' => 'success',
            'data' => [
                'issued_certificates' => $issuedCerts,
                'available_certificates' => $availableCerts
            ]
        ];
    }
}
