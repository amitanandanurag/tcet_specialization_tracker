<?php
/**
 * Escapes leading formula characters to prevent CSV Injection in spreadsheet tools
 */
if (!function_exists('sanitizeCsvRow')) {
    function sanitizeCsvRow(array $row): array {
        return array_map(function($cell) {
            if (is_string($cell) && strlen($cell) > 0) {
                $firstChar = $cell[0];
                if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
                    return "'" . $cell;
                }
            }
            return $cell;
        }, $row);
    }
}

if (defined('TCET_UNIT_TEST') && TCET_UNIT_TEST === true && (!defined('TCET_EXPORT_TEST') || TCET_EXPORT_TEST !== true)) {
    return;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_session']) || empty($_SESSION['user_session'])) {
    http_response_code(401);
    die("Unauthorized: Please log in to export data.");
}

require_once __DIR__ . "/../database/db_connect.php";
if (!isset($conn) || !($conn instanceof mysqli)) {
    $db = new DBController();
    $conn = $db->conn;
} else {
    $db = new DBController();
    $db->conn = $conn;
}

$userId = intval($_SESSION['user_id'] ?? 0);
$roleId = intval($_SESSION['user_type'] ?? $_SESSION['role_id'] ?? 0);

// Only Super Admin (1), Admin (2), and Coordinator (3) can export bulk data
if (!in_array($roleId, [1, 2, 3])) {
    http_response_code(403);
    die("Access Denied: Your role does not have permission to export datasets.");
}

// Fetch user's department for scoping
$userDeptId = 0;
$uRes = mysqli_query($db->conn, "SELECT department_id FROM st_user_master WHERE user_id = $userId LIMIT 1");
if ($uRes && $uRow = mysqli_fetch_assoc($uRes)) {
    $userDeptId = intval($uRow['department_id'] ?? 0);
}

$type = $_GET['type'] ?? 'students';
$deptFilter = intval($_GET['department_id'] ?? ($roleId === 3 ? $userDeptId : 0));
$semFilter = intval($_GET['semester_id'] ?? 0);
$specFilter = intval($_GET['specialization_id'] ?? 0);
$mentorFilter = intval($_GET['mentor_id'] ?? 0);

// Department isolation for Coordinators
if ($roleId === 3 && $userDeptId > 0) {
    $deptFilter = $userDeptId;
}

// Strict Super Admin gate BEFORE sending CSV headers for historical archive export
if ($type === 'historical_students') {
    $archScope = array();
    if (!$db->requireHistoricalArchiveSuperAdmin($archScope)) {
        if (method_exists($db, 'writeAuditLog')) {
            $db->writeAuditLog($userId, 'UNAUTHORIZED_ACCESS_ATTEMPT', 'st_historical_student_candidates', null, "Unauthorized historical archive export attempt by role_id={$roleId}");
        }
        http_response_code(403);
        die("Access Denied: Only Super Admin can export the historical student archive.");
    }
}

$filename = "tcet_export_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $type) . "_" . date("Ymd_His") . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
// Output UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

/**
 * Escapes leading formula characters to prevent CSV Injection in spreadsheet tools
 */
if (!function_exists('sanitizeCsvRow')) {
    function sanitizeCsvRow(array $row): array {
        return array_map(function($cell) {
            if (is_string($cell) && strlen($cell) > 0) {
                $firstChar = $cell[0];
                if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
                    return "'" . $cell;
                }
            }
            return $cell;
        }, $row);
    }
}

if ($type === 'students') {
    fputcsv($output, [
        'Student ID', 'Roll No', 'Registration / ERP ID', 'Full Name', 
        'Department', 'Class', 'Division', 'Current Semester', 
        'Specialization Track', 'Specialization Subject', 'Assigned Mentor', 
        'Minor Course', 'Minor Subject', 'CGPA', 'Email', 'Mobile', 'Status'
    ]);
    
    $where = [];
    if ($deptFilter > 0) $where[] = "s.department_id = $deptFilter";
    if ($semFilter > 0) $where[] = "s.current_semester_id = $semFilter";
    if ($specFilter > 0) $where[] = "s.specialization_id = $specFilter";
    if ($mentorFilter > 0) {
        $where[] = "(msm.mentor_id = $mentorFilter OR EXISTS (
            SELECT 1 FROM st_mentor_subject_mapping msub
            WHERE msub.mentor_id = $mentorFilter
              AND msub.subject_id = COALESCE(NULLIF(s.specialization_subject_id, 0), h_current.specialization_subject_id)
        ))";
    }
    $whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
    
    $sql = "SELECT 
                s.student_id, s.roll_no, s.registration_no, s.fname,
                d.department_name, c.class_name, sec.sections as division_name, sem.semester_name,
                sp.specialization_name, sps.subject_name,
                u.user_name as mentor_name,
                mc.course_name as minor_course_name, ms.subject_name as minor_subject_name,
                s.cgpa, s.email, s.mobile,
                IF(s.status = 1, 'Active', 'Inactive') as status_label
            FROM st_student_master s
            LEFT JOIN st_department_master d ON s.department_id = d.department_id
            LEFT JOIN st_class_master c ON s.class_id = c.class_id
            LEFT JOIN st_section_master sec ON s.division_id = sec.id
            LEFT JOIN st_semester_master sem ON s.current_semester_id = sem.semester_id
            LEFT JOIN st_specialization_master sp ON s.specialization_id = sp.specialization_id
            LEFT JOIN st_specialization_subject_master sps ON s.specialization_subject_id = sps.subject_id
            LEFT JOIN st_mentor_student_mapping msm ON (msm.student_id = s.student_id AND msm.semester_id = s.current_semester_id)
            LEFT JOIN st_user_master u ON msm.mentor_id = u.user_id
            LEFT JOIN st_minorcourse mc ON s.minor_course_id = mc.course_id
            LEFT JOIN st_minorsubject ms ON s.minor_subject_id = ms.subject_id
            $whereSql
            ORDER BY s.department_id ASC, s.roll_no ASC, s.student_id ASC";
            
    $res = mysqli_query($db->conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, sanitizeCsvRow([
                $row['student_id'],
                $row['roll_no'] ?? '',
                $row['registration_no'] ?? '',
                $row['fname'] ?? '',
                $row['department_name'] ?? '',
                $row['class_name'] ?? '',
                $row['division_name'] ?? '',
                $row['semester_name'] ?? '',
                $row['specialization_name'] ?? 'None',
                $row['subject_name'] ?? 'None',
                $row['mentor_name'] ?? 'Unassigned',
                $row['minor_course_name'] ?? 'None',
                $row['minor_subject_name'] ?? 'None',
                $row['cgpa'] ?? '',
                $row['email'] ?? '',
                $row['mobile'] ?? '',
                $row['status_label']
            ]));
        }
    }
} elseif ($type === 'audit_logs') {
    if ($roleId !== 1) {
        fputcsv($output, ['Access Denied: Only Super Admin can export audit trail.']);
    } else {
        fputcsv($output, ['Audit ID', 'User ID', 'Username', 'Action Type', 'Affected Table', 'Record ID', 'Description', 'IP Address', 'Timestamp']);
        $sql = "SELECT audit_id, user_id, username, action_type, affected_table, affected_record, description, ip_address, performed_at 
                FROM st_audit_log 
                ORDER BY audit_id DESC 
                LIMIT 5000";
        $res = mysqli_query($db->conn, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                fputcsv($output, sanitizeCsvRow([
                    $row['audit_id'], $row['user_id'], $row['username'],
                    $row['action_type'], $row['affected_table'], $row['affected_record'],
                    $row['description'], $row['ip_address'], $row['performed_at']
                ]));
            }
        }
    }
} elseif ($type === 'historical_students') {
    fputcsv($output, [
        'Candidate ID', 'Staging ID', 'Import Batch', 'Academic Year', 'Semester', 'Class',
        'Orig ERP Raw', 'Orig ERP Standardized', 'Orig Student Name', 'Orig Excel Name', 'Orig Notice Name',
        'Orig Dept Code', 'Orig Division Canonical', 'Orig Roll No', 'Orig CGPA Raw', 'Orig CGPA Numeric',
        'Orig Track Category', 'Orig Specialization/Minor', 'Orig Course Code', 'Orig Mentor Raw',
        'Source Doc Primary', 'Source Doc Secondary', 'Approval Tier',
        'Curated ERP ID', 'Curated Student Name', 'Curated Department', 'Curated Division', 'Curated Roll No',
        'Curated CGPA', 'Curated Specialization', 'Curated Minor Course', 'Curated Course Code', 'Curated Course Name',
        'Curated Mentor', 'Eligibility Status', 'Academic Approval Status', 'Import Status',
        'Approving Authority', 'Approval Date', 'Supporting Source Reference', 'Approval Remarks',
        'Blocking Reasons', 'Linked Conflicts'
    ]);

    $histFilters = array(
        'academic_year_id' => $_GET['academic_year_id'] ?? '',
        'semester_id' => $_GET['semester_id'] ?? '',
        'department_id' => $_GET['department_id'] ?? '',
        'class_id' => $_GET['class_id'] ?? '',
        'division' => $_GET['division'] ?? '',
        'erp_id' => $_GET['erp_id'] ?? '',
        'student_name' => $_GET['student_name'] ?? '',
        'specialization' => $_GET['specialization'] ?? '',
        'course' => $_GET['course'] ?? '',
        'eligibility_status' => $_GET['eligibility_status'] ?? '',
        'academic_approval_status' => $_GET['academic_approval_status'] ?? '',
        'import_status' => $_GET['import_status'] ?? '',
        'approval_tier' => $_GET['approval_tier'] ?? ''
    );
    $histSearch = $_GET['search'] ?? '';
    $histParams = array();
    $histTypes = '';
    $histWhere = $db->buildHistoricalArchiveWhere($histFilters, $histSearch, $histParams, $histTypes);

    $histSql = "SELECT
                    c.candidate_id, c.staging_id, b.import_batch_ref, c.academic_year_label,
                    IFNULL(sem.semester_name, c.semester_id) AS semester_name,
                    IFNULL(cl.class_name, c.class_id) AS class_name,
                    c.orig_erp_raw, c.orig_erp_standardized, c.orig_student_name_canonical,
                    c.orig_student_name_excel, c.orig_student_name_notice, c.orig_department_code,
                    c.orig_division_canonical, c.orig_roll_no, c.orig_cgpa_raw, c.orig_cgpa_numeric,
                    c.orig_honors_minor_category, c.orig_specialization_or_minor_name,
                    c.orig_mapped_course_code, c.orig_mentor_raw,
                    c.source_document_primary, c.source_document_secondary, c.approval_tier,
                    c.cur_erp_id, c.cur_student_name,
                    IFNULL(d.department_name, IFNULL(c.cur_department_code, '')) AS cur_dept_name,
                    IFNULL(dv.division_name, IFNULL(c.cur_division_label, '')) AS cur_div_name,
                    c.cur_roll_no, c.cur_cgpa,
                    IFNULL(sp.specialization_name, '') AS cur_spec_name,
                    IFNULL(mc.course_name, '') AS cur_minor_name,
                    c.cur_course_code, c.cur_course_name, c.cur_mentor_name,
                    c.eligibility_status, c.academic_approval_status, c.import_status,
                    c.approving_authority, c.approval_date, c.supporting_source_reference, c.approval_remarks,
                    c.blocking_reasons, c.related_conflict_ids
                FROM st_historical_student_candidates c
                INNER JOIN st_historical_import_batches b ON b.batch_id = c.batch_id
                LEFT JOIN st_semester_master sem ON sem.semester_id = c.semester_id
                LEFT JOIN st_class_master cl ON cl.class_id = c.class_id
                LEFT JOIN st_department_master d ON d.department_id = c.cur_department_id
                LEFT JOIN st_division_master dv ON dv.division_id = c.cur_division_id
                LEFT JOIN st_specialization_master sp ON sp.specialization_id = c.cur_specialization_id
                LEFT JOIN st_minorcourse mc ON mc.course_id = c.cur_minor_course_id
                WHERE {$histWhere}
                ORDER BY c.candidate_id ASC";

    $histRows = $db->runPreparedSelect($histSql, $histTypes, $histParams);
    foreach ($histRows as $hr) {
        fputcsv($output, sanitizeCsvRow([
            $hr['candidate_id'], $hr['staging_id'], $hr['import_batch_ref'],
            $hr['academic_year_label'], $hr['semester_name'], $hr['class_name'],
            $hr['orig_erp_raw'] ?? '', $hr['orig_erp_standardized'] ?? '',
            $hr['orig_student_name_canonical'] ?? '', $hr['orig_student_name_excel'] ?? '',
            $hr['orig_student_name_notice'] ?? '', $hr['orig_department_code'] ?? '',
            $hr['orig_division_canonical'] ?? '', $hr['orig_roll_no'] ?? '',
            $hr['orig_cgpa_raw'] ?? '', $hr['orig_cgpa_numeric'] ?? '',
            $hr['orig_honors_minor_category'] ?? '', $hr['orig_specialization_or_minor_name'] ?? '',
            $hr['orig_mapped_course_code'] ?? '', $hr['orig_mentor_raw'] ?? '',
            $hr['source_document_primary'] ?? '', $hr['source_document_secondary'] ?? '',
            $hr['approval_tier'],
            $hr['cur_erp_id'] ?? '', $hr['cur_student_name'] ?? '', $hr['cur_dept_name'],
            $hr['cur_div_name'], $hr['cur_roll_no'] ?? '', $hr['cur_cgpa'] ?? '',
            $hr['cur_spec_name'], $hr['cur_minor_name'],
            $hr['cur_course_code'] ?? '', $hr['cur_course_name'] ?? '', $hr['cur_mentor_name'] ?? '',
            $hr['eligibility_status'], $hr['academic_approval_status'], $hr['import_status'],
            $hr['approving_authority'] ?? '', $hr['approval_date'] ?? '',
            $hr['supporting_source_reference'] ?? '', $hr['approval_remarks'] ?? '',
            $hr['blocking_reasons'] ?? '', $hr['related_conflict_ids'] ?? ''
        ]));
    }
} else {
    fputcsv($output, ['Error', 'Unsupported export dataset type.']);
}

fclose($output);

if (method_exists($db, 'writeAuditLog')) {
    $db->writeAuditLog($userId, 'DATA_EXPORT', null, null, "Exported dataset type: {$type}, department: {$deptFilter}");
}
if (!defined('TCET_UNIT_TEST') || TCET_UNIT_TEST !== true) {
    exit;
}
