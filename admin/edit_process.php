<?php
session_start();
header('Content-Type: application/json');
require "../database/db_connect.php";
$db_handle = new DBController();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['student_id']);
    $table_name = $_POST['table_name'];
    
    // Validate table name to prevent SQL injection
    if ($table_name !== 'st_student_master') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid table name']);
        exit;
    }
    
    // Get all form data
    $academic_year = mysqli_real_escape_string($db_handle->conn, $_POST['academic'] ?? '');
    $class_id = intval($_POST['class'] ?? 0);
    $division_id = intval($_POST['batch'] ?? 0);
    $batch_id = intval($_POST['batch_id'] ?? 0);
    $roll_no = mysqli_real_escape_string($db_handle->conn, $_POST['roll_no'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0);
    $specialization_id = intval($_POST['specialization_id'] ?? 0);
    $specialization_subject_id = intval($_POST['unaided_subject'] ?? 0);
    $cgpa = !empty($_POST['cgpa']) ? floatval($_POST['cgpa']) : NULL;
    $joining_date = mysqli_real_escape_string($db_handle->conn, $_POST['join_date'] ?? '');
    
    $fname = mysqli_real_escape_string($db_handle->conn, $_POST['fname'] ?? '');
    $mname = mysqli_real_escape_string($db_handle->conn, $_POST['mname'] ?? '');
    $lname = mysqli_real_escape_string($db_handle->conn, $_POST['lname'] ?? '');
    $dob = mysqli_real_escape_string($db_handle->conn, $_POST['dob'] ?? '');
    $gender = mysqli_real_escape_string($db_handle->conn, $_POST['gender'] ?? '');
    $nationality = mysqli_real_escape_string($db_handle->conn, $_POST['nation'] ?? 'INDIAN');
    $appar = mysqli_real_escape_string($db_handle->conn, $_POST['appar'] ?? '');
    $uan = mysqli_real_escape_string($db_handle->conn, $_POST['uan'] ?? '');
    $pan = mysqli_real_escape_string($db_handle->conn, $_POST['pan'] ?? '');
    
    $permanent_address = mysqli_real_escape_string($db_handle->conn, $_POST['permanent'] ?? '');
    $present_address = mysqli_real_escape_string($db_handle->conn, $_POST['present'] ?? '');
    $city = mysqli_real_escape_string($db_handle->conn, $_POST['city'] ?? '');
    $pincode = mysqli_real_escape_string($db_handle->conn, $_POST['pincode'] ?? '');
    $country = mysqli_real_escape_string($db_handle->conn, $_POST['country'] ?? 'India');
    $state = mysqli_real_escape_string($db_handle->conn, $_POST['state'] ?? 'Maharashtra');
    $phone = mysqli_real_escape_string($db_handle->conn, $_POST['phone'] ?? '');
    $mobile = mysqli_real_escape_string($db_handle->conn, $_POST['mobile'] ?? '');
    $email = mysqli_real_escape_string($db_handle->conn, $_POST['email'] ?? '');
    
    // Handle photo upload
    $photo = '';
    if (!empty($_FILES['photo']['name'])) {
        $filename = basename($_FILES['photo']['name']);
        $target_dir = "student_photo/";
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo = $filename;
        }
    }
    
    // Build UPDATE query
    $update_sql = "UPDATE $table_name SET 
        academic_year = '$academic_year',
        class_id = $class_id,
        division_id = $division_id,
        batch_id = $batch_id,
        roll_no = '$roll_no',
        department_id = $department_id,
        specialization_id = $specialization_id,
        specialization_subject_id = $specialization_subject_id,
        cgpa = " . ($cgpa !== NULL ? $cgpa : "NULL") . ",
        joining_date = '$joining_date',
        fname = '$fname',
        mname = '$mname',
        lname = '$lname',
        dob = '$dob',
        gender = '$gender',
        nationality = '$nationality',
        appar = '$appar',
        uan = '$uan',
        pan = '$pan',
        permanent_address = '$permanent_address',
        present_address = '$present_address',
        city = '$city',
        pincode = '$pincode',
        country = '$country',
        state = '$state',
        phone = '$phone',
        mobile = '$mobile',
        email = '$email'";
    
    if (!empty($photo)) {
        $update_sql .= ", photo = '$photo'";
    }
    
    $update_sql .= " WHERE student_id = $student_id";
    
    if ($db_handle->conn->query($update_sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Student record updated successfully']);
        // Redirect after 2 seconds
        header("Refresh: 2; url=student-info.php");
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating record: ' . $db_handle->conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
