<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_session']) || empty($_SESSION['user_session'])) {
    http_response_code(401);
    die("Unauthorized: Please log in to export data.");
}

require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();

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

// Department isolation for Coordinators
if ($roleId === 3 && $userDeptId > 0) {
    $deptFilter = $userDeptId;
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

if ($type === 'students') {
    fputcsv($output, [
        'Student ID', 'Roll No', 'Registration No', 'Full Name', 
        'Department', 'Class', 'Division', 'Current Semester', 
        'Specialization Track', 'Specialization Subject', 'Assigned Mentor', 
        'Minor Course', 'Minor Subject', 'CGPA', 'Email', 'Mobile', 'Status'
    ]);
    
    $where = [];
    if ($deptFilter > 0) $where[] = "s.department_id = $deptFilter";
    if ($semFilter > 0) $where[] = "s.current_semester_id = $semFilter";
    $whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
    
    $sql = "SELECT 
                s.student_id, s.roll_no, s.registration_no, s.fname,
                d.department_name, c.class_name, divm.division_name, sem.semester_name,
                sp.specialization_name, sps.subject_name,
                u.user_name as mentor_name,
                mc.course_name as minor_course_name, ms.subject_name as minor_subject_name,
                s.cgpa, s.email, s.mobile,
                IF(s.status = 1, 'Active', 'Inactive') as status_label
            FROM st_student_master s
            LEFT JOIN st_department_master d ON s.department_id = d.department_id
            LEFT JOIN st_class_master c ON s.class_id = c.class_id
            LEFT JOIN st_division_master divm ON s.division_id = divm.division_id
            LEFT JOIN st_semester_master sem ON s.current_semester_id = sem.semester_id
            LEFT JOIN st_specialization_master sp ON s.specialization_id = sp.specialization_id
            LEFT JOIN st_specialization_subject_master sps ON s.specialization_subject_id = sps.subject_id
            LEFT JOIN st_mentor_student_mapping msm ON (msm.student_id = s.student_id AND msm.semester_id = s.current_semester_id)
            LEFT JOIN st_user_master u ON msm.mentor_id = u.user_id
            LEFT JOIN st_minorcourse mc ON s.minor_course_id = mc.id
            LEFT JOIN st_minorsubject ms ON s.minor_subject_id = ms.id
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
} else {
    fputcsv($output, ['Error', 'Unsupported export dataset type.']);
}

fclose($output);

if (method_exists($db, 'writeAuditLog')) {
    $db->writeAuditLog($userId, 'DATA_EXPORT', null, null, "Exported dataset type: {$type}, department: {$deptFilter}");
}
exit;
