<?php

include "../database/db_connect.php";
$db_handel = new DBController();

if (isset($_POST['save'])) {
    $academic = $_POST['academic'] ?? '';
    $registration_no = $_POST['registration_no'] ?? '';
    $join_date = $_POST['join_date'] ?? '';
    $class_id = $_POST['class'] ?? '';
    $division_id = $_POST['batch'] ?? '';
    $batch_id = $_POST['batch_id'] ?? '';
    $roll_no = $_POST['roll_no'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $specialization_id = $_POST['specialization_id'] ?? '';
    $specialization_subject_id = $_POST['unaided_subject'] ?? '';
    $cgpa = $_POST['cgpa'] ?? '';
    $fname = $_POST['fname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $nationality = $_POST['nation'] ?? '';
    $apaar_id = $_POST['appar'] ?? '';
    $uan = $_POST['uan'] ?? '';
    $pan = $_POST['pan'] ?? '';
    $permanent = $_POST['permanent'] ?? '';
    $present = $_POST['present'] ?? '';
    $city = $_POST['city'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $country = $_POST['country'] ?? '';
    $state = $_POST['state'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $email = $_POST['email'] ?? '';

    $photo = $_FILES['photo']['name'] ?? '';
    $photo_tmp = $_FILES['photo']['tmp_name'] ?? '';
    $mark_list = $_FILES['mark-list']['name'] ?? '';
    $mark_tmp = $_FILES['mark-list']['tmp_name'] ?? '';
    $birth_certificate = $_FILES['bc']['name'] ?? '';
    $bc_tmp = $_FILES['bc']['tmp_name'] ?? '';
    $transfer_certificate = $_FILES['tc']['name'] ?? '';
    $tc_tmp = $_FILES['tc']['tmp_name'] ?? '';
    $caste_certificate = $_FILES['cc']['name'] ?? '';
    $cc_tmp = $_FILES['cc']['tmp_name'] ?? '';
    $migration_certificate = $_FILES['migration']['name'] ?? '';
    $migration_tmp = $_FILES['migration']['tmp_name'] ?? '';
    $affidavit = $_FILES['affidavit']['name'] ?? '';
    $affidavit_tmp = $_FILES['affidavit']['tmp_name'] ?? '';

    $folder = "../manage/pdf/";
    if (!empty($photo_tmp) && !empty($photo)) move_uploaded_file($photo_tmp, $folder.$photo);
    if (!empty($mark_tmp) && !empty($mark_list)) move_uploaded_file($mark_tmp, $folder.$mark_list);
    if (!empty($bc_tmp) && !empty($birth_certificate)) move_uploaded_file($bc_tmp, $folder.$birth_certificate);
    if (!empty($tc_tmp) && !empty($transfer_certificate)) move_uploaded_file($tc_tmp, $folder.$transfer_certificate);
    if (!empty($cc_tmp) && !empty($caste_certificate)) move_uploaded_file($cc_tmp, $folder.$caste_certificate);
    if (!empty($migration_tmp) && !empty($migration_certificate)) move_uploaded_file($migration_tmp, $folder.$migration_certificate);
    if (!empty($affidavit_tmp) && !empty($affidavit)) move_uploaded_file($affidavit_tmp, $folder.$affidavit);

    $cgpaValue = (!empty($cgpa) && is_numeric($cgpa)) ? "'".mysqli_real_escape_string($db_handel->conn, $cgpa)."'" : "NULL";

    $createTableSql = "CREATE TABLE IF NOT EXISTS `st_student_master` (
      `student_id` int(11) NOT NULL AUTO_INCREMENT,
      `academic_year` varchar(100) NOT NULL,
      `registration_no` varchar(200) NOT NULL,
      `joining_date` varchar(100) NOT NULL,
      `class_id` int(11) NOT NULL,
      `division_id` int(11) NOT NULL,
      `batch_id` int(11) DEFAULT NULL,
      `roll_no` varchar(50) DEFAULT NULL,
      `department_id` int(11) DEFAULT NULL,
      `specialization_id` int(11) DEFAULT NULL,
      `specialization_subject_id` int(11) DEFAULT NULL,
      `cgpa` decimal(4,2) DEFAULT NULL,
      `fname` varchar(100) NOT NULL,
      `mname` varchar(100) DEFAULT NULL,
      `lname` varchar(100) DEFAULT NULL,
      `dob` varchar(100) DEFAULT NULL,
      `gender` varchar(50) DEFAULT NULL,
      `nationality` varchar(100) DEFAULT NULL,
      `apaar_id` varchar(100) DEFAULT NULL,
      `uan` varchar(100) DEFAULT NULL,
      `pan` varchar(100) DEFAULT NULL,
      `permanent_address` text DEFAULT NULL,
      `present_address` text DEFAULT NULL,
      `city` varchar(100) DEFAULT NULL,
      `pincode` varchar(20) DEFAULT NULL,
      `country` varchar(100) DEFAULT NULL,
      `state` varchar(100) DEFAULT NULL,
      `phone` varchar(20) DEFAULT NULL,
      `mobile` varchar(20) DEFAULT NULL,
      `email` varchar(150) DEFAULT NULL,
      `photo` varchar(255) DEFAULT NULL,
      `mark_list` varchar(255) DEFAULT NULL,
      `birth_certificate` varchar(255) DEFAULT NULL,
      `transfer_certificate` varchar(255) DEFAULT NULL,
      `caste_certificate` varchar(255) DEFAULT NULL,
      `migration_certificate` varchar(255) DEFAULT NULL,
      `affidavit` varchar(255) DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`student_id`),
      UNIQUE KEY `registration_no` (`registration_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    mysqli_query($db_handel->conn, $createTableSql);

    $sql = "INSERT INTO `st_student_master`(
      `academic_year`, `registration_no`, `joining_date`, `class_id`, `division_id`, `batch_id`, `roll_no`,
      `department_id`, `specialization_id`, `specialization_subject_id`, `cgpa`, `fname`, `mname`, `lname`, `dob`,
      `gender`, `nationality`, `apaar_id`, `uan`, `pan`, `permanent_address`, `present_address`, `city`, `pincode`,
      `country`, `state`, `phone`, `mobile`, `email`, `photo`, `mark_list`, `birth_certificate`,
      `transfer_certificate`, `caste_certificate`, `migration_certificate`, `affidavit`
    ) VALUES (
      '".mysqli_real_escape_string($db_handel->conn, $academic)."',
      '".mysqli_real_escape_string($db_handel->conn, $registration_no)."',
      '".mysqli_real_escape_string($db_handel->conn, $join_date)."',
      '".mysqli_real_escape_string($db_handel->conn, $class_id)."',
      '".mysqli_real_escape_string($db_handel->conn, $division_id)."',
      '".mysqli_real_escape_string($db_handel->conn, $batch_id)."',
      '".mysqli_real_escape_string($db_handel->conn, $roll_no)."',
      '".mysqli_real_escape_string($db_handel->conn, $department_id)."',
      '".mysqli_real_escape_string($db_handel->conn, $specialization_id)."',
      '".mysqli_real_escape_string($db_handel->conn, $specialization_subject_id)."',
      $cgpaValue,
      '".mysqli_real_escape_string($db_handel->conn, $fname)."',
      '".mysqli_real_escape_string($db_handel->conn, $mname)."',
      '".mysqli_real_escape_string($db_handel->conn, $lname)."',
      '".mysqli_real_escape_string($db_handel->conn, $dob)."',
      '".mysqli_real_escape_string($db_handel->conn, $gender)."',
      '".mysqli_real_escape_string($db_handel->conn, $nationality)."',
      '".mysqli_real_escape_string($db_handel->conn, $apaar_id)."',
      '".mysqli_real_escape_string($db_handel->conn, $uan)."',
      '".mysqli_real_escape_string($db_handel->conn, $pan)."',
      '".mysqli_real_escape_string($db_handel->conn, $permanent)."',
      '".mysqli_real_escape_string($db_handel->conn, $present)."',
      '".mysqli_real_escape_string($db_handel->conn, $city)."',
      '".mysqli_real_escape_string($db_handel->conn, $pincode)."',
      '".mysqli_real_escape_string($db_handel->conn, $country)."',
      '".mysqli_real_escape_string($db_handel->conn, $state)."',
      '".mysqli_real_escape_string($db_handel->conn, $phone)."',
      '".mysqli_real_escape_string($db_handel->conn, $mobile)."',
      '".mysqli_real_escape_string($db_handel->conn, $email)."',
      '".mysqli_real_escape_string($db_handel->conn, $photo)."',
      '".mysqli_real_escape_string($db_handel->conn, $mark_list)."',
      '".mysqli_real_escape_string($db_handel->conn, $birth_certificate)."',
      '".mysqli_real_escape_string($db_handel->conn, $transfer_certificate)."',
      '".mysqli_real_escape_string($db_handel->conn, $caste_certificate)."',
      '".mysqli_real_escape_string($db_handel->conn, $migration_certificate)."',
      '".mysqli_real_escape_string($db_handel->conn, $affidavit)."'
    )";

    $result = mysqli_query($db_handel->conn, $sql);
    if ($result === TRUE) {
        echo '<script type="text/javascript">alert("Student admission saved successfully.");</script>';
        echo "<script>window.open('student_admission.php','_self')</script>";
    } else {
        echo("Error description: " . mysqli_error($db_handel->conn));
    }
}

?>
