<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');

require_once "../database/db_connect.php";
$db_handle = new DBController();

$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : (isset($_GET['class_id']) ? intval($_GET['class_id']) : 0);
$department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : (isset($_GET['department_id']) ? intval($_GET['department_id']) : 0);
$track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : (isset($_POST['specialization_track_id']) ? intval($_POST['specialization_track_id']) : (isset($_GET['track_id']) ? intval($_GET['track_id']) : 0));

// Check Class restriction (FY / First Year not eligible)
if ($class_id > 0) {
    $class_stmt = mysqli_prepare($db_handle->conn, "SELECT class_name FROM st_class_master WHERE class_id = ? LIMIT 1");
    if ($class_stmt) {
        mysqli_stmt_bind_param($class_stmt, "i", $class_id);
        mysqli_stmt_execute($class_stmt);
        $c_res = mysqli_stmt_get_result($class_stmt);
        if ($c_res && ($c_data = mysqli_fetch_assoc($c_res))) {
            $class_name = strtoupper($c_data['class_name'] ?? '');
            if ($class_name === 'FY' || $class_name === 'FE' || stripos($class_name, 'FIRST YEAR') !== false) {
                echo json_encode([
                    'success' => true,
                    'warning' => true,
                    'message' => 'Specialization is not offered for First Year (FY) students.',
                    'data' => []
                ]);
                mysqli_stmt_close($class_stmt);
                exit;
            }
        }
        mysqli_stmt_close($class_stmt);
    }
}

// If Minor Multidisciplinary track (ID 4)
if ($track_id === 4) {
    echo json_encode([
        'success' => true,
        'warning' => false,
        'is_multidisciplinary' => true,
        'data' => [
            ['specialization_id' => 4, 'specialization_name' => 'Minor Multidisciplinary']
        ]
    ]);
    exit;
}

// If both department_id and track_id are available, apply official eligibility mappings
if ($department_id > 0 && $track_id > 0) {
    $specializations = $db_handle->getEligibleSpecializations($department_id, $track_id, $class_id);
    echo json_encode([
        'success' => true,
        'warning' => false,
        'data' => $specializations
    ]);
    exit;
}

// Fallback: If only track_id is selected but department not yet selected
if ($track_id > 0 && $department_id <= 0) {
    echo json_encode([
        'success' => true,
        'warning' => false,
        'message' => 'Please select a department to see eligible specializations.',
        'data' => []
    ]);
    exit;
}

// Fallback: If no track selected yet
echo json_encode([
    'success' => true,
    'warning' => false,
    'data' => []
]);
exit;