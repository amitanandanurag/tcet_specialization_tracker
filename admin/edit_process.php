<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "../database/db_connect.php";
$database = new DBController();

if (isset($_POST['save'])) {
    $conn = $database->conn;
    
    // Get the student ID from the form
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    
    if ($student_id == 0) {
        echo '<script type="text/javascript">alert("Invalid student ID!");</script>';
        echo "<script>window.open('student-info.php','_self')</script>";
        exit;
    }
    
    // Get form data
    $academic_year_id = mysqli_real_escape_string($conn, $_POST['academic_year_id'] ?? '');
    $registration_no = mysqli_real_escape_string($conn, $_POST['registration_no'] ?? '');
    $class_id = mysqli_real_escape_string($conn, $_POST['class_id'] ?? '');
    $division_id = mysqli_real_escape_string($conn, $_POST['division_id'] ?? '');
    $current_semester_id = mysqli_real_escape_string($conn, $_POST['current_semester_id'] ?? '');
    $fname = mysqli_real_escape_string($conn, $_POST['fname'] ?? '');
    
    // Handle grad_year
    $grad_year = 'NULL';
    if (!empty($_POST['grad_year']) && is_numeric($_POST['grad_year'])) {
        $grad_year = "'" . mysqli_real_escape_string($conn, $_POST['grad_year']) . "'";
    }
    
    // Handle roll_no
    $roll_no = 'NULL';
    if (!empty($_POST['roll_no'])) {
        $roll_no = "'" . mysqli_real_escape_string($conn, $_POST['roll_no']) . "'";
    }
    
    // Handle department_id
    $department_id = 'NULL';
    if (!empty($_POST['department_id']) && is_numeric($_POST['department_id'])) {
        $department_id = "'" . mysqli_real_escape_string($conn, $_POST['department_id']) . "'";
    }
    
    // Handle specialization_id
    $specialization_id = 'NULL';
    if (!empty($_POST['specialization_id']) && is_numeric($_POST['specialization_id'])) {
        $specialization_id = "'" . mysqli_real_escape_string($conn, $_POST['specialization_id']) . "'";
    }
    
    // CRITICAL FIX: Handle specialization_subject_id based on specialization type
    $specialization_subject_id = 'NULL';
    
    // Get specialization name to determine the type
    $spec_check = mysqli_query($conn, "SELECT specialization_name FROM st_specialization_master WHERE specialization_id = " . $specialization_id);
    if ($spec_check && mysqli_num_rows($spec_check) > 0) {
        $spec_row = mysqli_fetch_assoc($spec_check);
        $specialization_name = strtolower($spec_row['specialization_name'] ?? '');
        $is_minor_multidisciplinary = strpos($specialization_name, 'minor multidisciplinary') !== false;
        
        if ($is_minor_multidisciplinary) {
            // For Minor Multidisciplinary: Use minor_subject_id from the form
            if (!empty($_POST['minor_subject_id']) && is_numeric($_POST['minor_subject_id'])) {
                $specialization_subject_id = "'" . mysqli_real_escape_string($conn, $_POST['minor_subject_id']) . "'";
            }
        } else {
            // For other specializations (Honours, etc.): Use specialization_subject_id
            if (!empty($_POST['specialization_subject_id']) && is_numeric($_POST['specialization_subject_id'])) {
                $specialization_subject_id = "'" . mysqli_real_escape_string($conn, $_POST['specialization_subject_id']) . "'";
            }
        }
    }
    
    // Handle CGPA
    $cgpa = 'NULL';
    if (isset($_POST['cgpa']) && $_POST['cgpa'] !== '' && is_numeric($_POST['cgpa'])) {
        $cgpa_val = floatval($_POST['cgpa']);
        $cgpa_val = number_format($cgpa_val, 2);
        $cgpa = "'" . $cgpa_val . "'";
    }
    
    // Handle mobile
    $mobile = 'NULL';
    if (!empty($_POST['mobile'])) {
        $mobile = "'" . mysqli_real_escape_string($conn, $_POST['mobile']) . "'";
    }
    
    // Handle email
    $email = 'NULL';
    if (!empty($_POST['email'])) {
        $email = "'" . mysqli_real_escape_string($conn, $_POST['email']) . "'";
    }
    
    // Handle status
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
    
    // Handle semester marks (text areas)
    $m_sem1 = isset($_POST['m_sem1']) && $_POST['m_sem1'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['m_sem1']) . "'" : "'[]'";
    $m_sem2 = isset($_POST['m_sem2']) && $_POST['m_sem2'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['m_sem2']) . "'" : "'[]'";
    $m_sem3 = isset($_POST['m_sem3']) && $_POST['m_sem3'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['m_sem3']) . "'" : "'[]'";
    
    // Check if registration number exists for OTHER students (excluding current one)
    $check_sql = "SELECT registration_no FROM st_student_master 
                  WHERE registration_no = '$registration_no' 
                  AND student_id != $student_id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo '<script type="text/javascript">alert("Registration number already exists for another student!");</script>';
        echo "<script>window.history.back();</script>";
        exit;
    }
    
    // Build the UPDATE query
    $sql = "UPDATE `st_student_master` SET
        `academic_year_id` = '$academic_year_id',
        `class_id` = '$class_id',
        `division_id` = '$division_id',
        `grad_year` = $grad_year,
        `roll_no` = $roll_no,
        `department_id` = $department_id,
        `specialization_id` = $specialization_id,
        `specialization_subject_id` = $specialization_subject_id,
        `cgpa` = $cgpa,
        `fname` = '$fname',
        `mobile` = $mobile,
        `email` = $email,
        `status` = $status,
        `m_sem1` = $m_sem1,
        `m_sem2` = $m_sem2,
        `m_sem3` = $m_sem3,
        `current_semester_id` = '$current_semester_id'
    WHERE `student_id` = $student_id";
    
    // For debugging - uncomment to see the query
    // echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    // exit;
    
    $result = mysqli_query($conn, $sql);
    
    if ($result === TRUE) {
        // Handle file uploads if needed
        if (!empty($_FILES)) {
            $upload_dir = "uploads/marklists/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $existing_mark_list = [];
            $semester_fields = ['mark-list1', 'mark-list2', 'mark-list3', 'mark-list4', 'mark-list6'];
            
            foreach ($semester_fields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0 && !empty($_FILES[$field]['name'])) {
                    $file_ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                    $safe_reg_no = preg_replace('/[^a-zA-Z0-9]/', '_', $registration_no);
                    $file_name = time() . '_' . $safe_reg_no . '_' . $field . '.' . $file_ext;
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
                        $existing_mark_list[] = $file_name;
                    }
                }
            }
            
            if (!empty($existing_mark_list)) {
                // Get existing files or create new list
                $get_existing = "SELECT mark_list FROM st_student_master WHERE student_id = $student_id";
                $existing_result = mysqli_query($conn, $get_existing);
                $existing_row = mysqli_fetch_assoc($existing_result);
                $existing_files = $existing_row['mark_list'];
                
                $all_files = [];
                if (!empty($existing_files)) {
                    $all_files = explode(',', $existing_files);
                }
                $all_files = array_merge($all_files, $existing_mark_list);
                $mark_list_str = implode(',', array_unique($all_files));
                
                $update_files = "UPDATE `st_student_master` SET `mark_list` = '$mark_list_str' WHERE `student_id` = $student_id";
                mysqli_query($conn, $update_files);
            }
        }
        
        echo '<script type="text/javascript">alert("Student updated successfully!");</script>';
        echo "<script>window.open('student-info.php','_self')</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
        echo "<br><br>SQL Query: <pre>" . htmlspecialchars($sql) . "</pre>";
    }
    exit;
}
?>