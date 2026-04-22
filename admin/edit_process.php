<?php
include "header/header.php";

// Database connection (adjust as needed)
// $db_handle->conn should already be available

if (isset($_POST['save'])) {

    // Sanitize and collect form data
    $academic_year = mysqli_real_escape_string($db_handle->conn, $_POST['academic']);
    $registration_no = mysqli_real_escape_string($db_handle->conn, $_POST['registration_no']);
    $roll_no = mysqli_real_escape_string($db_handle->conn, $_POST['roll_no']);
    $class_id = mysqli_real_escape_string($db_handle->conn, $_POST['class']);
    $division_id = mysqli_real_escape_string($db_handle->conn, $_POST['batch']);
    $grad_year = mysqli_real_escape_string($db_handle->conn, $_POST['batch_id']);
    $department_id = mysqli_real_escape_string($db_handle->conn, $_POST['department_id']);
    $specialization_id = mysqli_real_escape_string($db_handle->conn, $_POST['specialization_id']);
    $specialisation_subject_id = isset($_POST['unaided_subject']) ? mysqli_real_escape_string($db_handle->conn, $_POST['unaided_subject']) : NULL;
    $cgpa = isset($_POST['cgpa']) && $_POST['cgpa'] != '' ? mysqli_real_escape_string($db_handle->conn, $_POST['cgpa']) : NULL;
    $fname = mysqli_real_escape_string($db_handle->conn, $_POST['fname']);
    $mobile = isset($_POST['mobile']) ? mysqli_real_escape_string($db_handle->conn, $_POST['mobile']) : NULL;
    $email = isset($_POST['email']) ? mysqli_real_escape_string($db_handle->conn, $_POST['email']) : NULL;

    // Handle file uploads for mark_list
    $mark_list_files = [];
    $upload_dir = "uploads/marklists/";

    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Check for semester mark lists
    $semester_fields = ['mark-list1', 'mark-list2', 'mark-list4', 'mark-list6'];
    foreach ($semester_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $file_name = time() . '_' . $registration_no . '_' . $field . '_' . basename($_FILES[$field]['name']);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
                $mark_list_files[] = $file_name;
            }
        }
    }

    // Store mark_list as comma-separated string or JSON
    $mark_list = !empty($mark_list_files) ? implode(',', $mark_list_files) : NULL;

    // Handle semester marks (m_sem1, m_sem2, m_sem3) - you may want to store JSON data
    // For now, we'll store placeholder values or you can collect from additional form fields
    $m_sem1 = '[]'; // Store as JSON if you have marks data
    $m_sem2 = '[]';
    $m_sem3 = '[]';

    // Status default is 1 (active)
    $status = 1;

    // Check if registration number already exists
    $check_sql = "SELECT registration_no FROM st_student_master WHERE registration_no = '$registration_no'";
    $check_result = mysqli_query($db_handle->conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Registration number already exists!');</script>";
    } else {
        // Insert into database
        $sql = "INSERT INTO st_student_master (
                    academic_year, 
                    registration_no, 
                    class_id, 
                    division_id, 
                    grad_year, 
                    roll_no, 
                    department_id, 
                    specialization_id, 
                    specialisation_subject_id, 
                    cgpa, 
                    fname, 
                    mobile, 
                    email, 
                    mark_list, 
                    status,
                    m_sem1,
                    m_sem2,
                    m_sem3,
                    created_at
                ) VALUES (
                    '$academic_year',
                    '$registration_no',
                    '$class_id',
                    '$division_id',
                    '$grad_year',
                    '$roll_no',
                    '$department_id',
                    '$specialization_id',
                    " . ($specialisation_subject_id ? "'$specialisation_subject_id'" : "NULL") . ",
                    " . ($cgpa ? "'$cgpa'" : "NULL") . ",
                    '$fname',
                    " . ($mobile ? "'$mobile'" : "NULL") . ",
                    " . ($email ? "'$email'" : "NULL") . ",
                    " . ($mark_list ? "'$mark_list'" : "NULL") . ",
                    '$status',
                    '$m_sem1',
                    '$m_sem2',
                    '$m_sem3',
                    NOW()
                )";

        if (mysqli_query($db_handle->conn, $sql)) {
            $student_id = mysqli_insert_id($db_handle->conn);
            echo "<script>alert('Student registered successfully! Student ID: " . $student_id . "'); window.location.href='student_list.php';</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($db_handle->conn) . "');</script>";
        }
    }
}
?>

<!-- Your existing HTML form goes here -->