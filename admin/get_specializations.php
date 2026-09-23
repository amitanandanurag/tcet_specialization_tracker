<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');

require_once "../database/db_connect.php";
$db_handle = new DBController();

if (isset($_POST['class_id'])) {
    $class_id = intval($_POST['class_id']);
    
    // Get class name using prepared statement
    $class_name = '';
    $stmt = mysqli_prepare($db_handle->conn, "SELECT class_name FROM st_class_master WHERE class_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $class_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && ($class_data = mysqli_fetch_assoc($res))) {
            $class_name = $class_data['class_name'] ?? '';
        }
        mysqli_stmt_close($stmt);
    }
    
    $response = [];
    
    // Check for FY (First Year)
    if (stripos($class_name, 'FY') !== false || stripos($class_name, 'First Year') !== false) {
        $response['warning'] = true;
        $response['data'] = [];
        echo json_encode($response);
        exit;
    }
    
    // Check for SY (Second Year)
    if (stripos($class_name, 'SY') !== false || stripos($class_name, 'Second Year') !== false) {
        $result = $db_handle->conn->query("SELECT specialization_id, specialization_name FROM st_specialization_master WHERE specialization_name LIKE '%Honours%' OR specialization_name LIKE '%Minor%' ORDER BY specialization_name");
    } else {
        // For other classes, show all specializations
        $result = $db_handle->conn->query("SELECT specialization_id, specialization_name FROM st_specialization_master ORDER BY specialization_name");
    }
    
    $specializations = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $specializations[] = $row;
        }
    }
    
    $response['warning'] = false;
    $response['data'] = $specializations;
    
    echo json_encode($response);
} else {
    echo json_encode(['warning' => false, 'data' => []]);
}
?>