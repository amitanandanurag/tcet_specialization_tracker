<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');

// Session authentication check
if (empty($_SESSION['user_id']) || intval($_SESSION['role_id'] ?? 0) > 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

include_once("../database/db_connect.php");
$db_handle = new DBController();

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'get_rejected_students') {
        $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : 0;
        
        // Query inactive/unallocated students from st_student_master
        $query = "SELECT 
            s.fname,
            s.registration_no,
            d.department_name,
            CASE 
                WHEN s.cgpa < 2.0 THEN 'Low CGPA'
                WHEN s.email IS NULL OR s.email = '' THEN 'Incomplete documents'
                WHEN s.status = 1 THEN 'Deactivated / Inactive'
                ELSE 'Prerequisite not met'
            END as rejection_reason,
            COALESCE(NULLIF(s.mobile, ''), 'N/A') as mobile
        FROM st_student_master s
        LEFT JOIN st_department_master d ON s.department_id = d.department_id
        WHERE s.status = 1";
        
        if ($spec_id > 0) {
            $query .= " AND s.specialization_id = " . $spec_id;
        }
        
        $query .= " ORDER BY s.student_id DESC LIMIT 50";
        
        $result = mysqli_query($db_handle->conn, $query);
        $data = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = [
                    'fname' => $row['fname'] ?? '',
                    'lname' => '',
                    'registration_no' => $row['registration_no'] ?? '',
                    'department_name' => $row['department_name'] ?: 'Unknown',
                    'rejection_reason' => $row['rejection_reason'] ?? 'Prerequisite not met',
                    'mobile' => $row['mobile'] ?? 'N/A'
                ];
            }
        }
        
        echo json_encode($data);
        exit;
    }
}
echo json_encode([]);
exit;
?>