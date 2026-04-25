<?php
include "header/header.php";

if (!isset($_POST['save'])) {
    echo "<script>alert('Invalid request.'); window.location.href='student-info.php';</script>";
    exit;
}

$student_id = intval($_POST['student_id'] ?? 0);
if ($student_id <= 0) {
    echo "<script>alert('Invalid student selected.'); window.location.href='student-info.php';</script>";
    exit;
}

$conn = $db_handle->conn;

$existingResult = mysqli_query($conn, "SELECT photo, mark_list FROM st_student_master WHERE student_id = {$student_id} LIMIT 1");
$existingRow = $existingResult ? mysqli_fetch_assoc($existingResult) : null;

if (!$existingRow) {
    echo "<script>alert('Student record not found.'); window.location.href='student-info.php';</script>";
    exit;
}

$academic_year = mysqli_real_escape_string($conn, $_POST['academic'] ?? '');
$registration_no = mysqli_real_escape_string($conn, $_POST['registration_no'] ?? '');
$join_date = mysqli_real_escape_string($conn, $_POST['join_date'] ?? '');
$class_id = intval($_POST['class'] ?? 0);
$division_id = intval($_POST['batch'] ?? 0);
$grad_year = !empty($_POST['batch_id']) && is_numeric($_POST['batch_id']) ? intval($_POST['batch_id']) : null;
$roll_no = mysqli_real_escape_string($conn, $_POST['roll_no'] ?? '');
$department_id = !empty($_POST['department_id']) && is_numeric($_POST['department_id']) ? intval($_POST['department_id']) : null;
$specialization_id = !empty($_POST['specialization_id']) && is_numeric($_POST['specialization_id']) ? intval($_POST['specialization_id']) : null;
$specialization_subject_id = !empty($_POST['unaided_subject']) && is_numeric($_POST['unaided_subject']) ? intval($_POST['unaided_subject']) : null;
$cgpa = ($_POST['cgpa'] ?? '') !== '' && is_numeric($_POST['cgpa']) ? number_format((float) $_POST['cgpa'], 2, '.', '') : null;
$fname = mysqli_real_escape_string($conn, $_POST['fname'] ?? '');
$mname = mysqli_real_escape_string($conn, $_POST['mname'] ?? '');
$lname = mysqli_real_escape_string($conn, $_POST['lname'] ?? '');
$dob = mysqli_real_escape_string($conn, $_POST['dob'] ?? '');
$gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
$nationality = mysqli_real_escape_string($conn, $_POST['nation'] ?? 'INDIAN');
$apaar_id = mysqli_real_escape_string($conn, $_POST['appar'] ?? '');
$uan = mysqli_real_escape_string($conn, $_POST['uan'] ?? '');
$pan = mysqli_real_escape_string($conn, $_POST['pan'] ?? '');
$permanent_address = mysqli_real_escape_string($conn, $_POST['permanent'] ?? '');
$present_address = mysqli_real_escape_string($conn, $_POST['present'] ?? '');
$city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
$pincode = mysqli_real_escape_string($conn, $_POST['pincode'] ?? '');
$country = mysqli_real_escape_string($conn, $_POST['country'] ?? 'India');
$state = mysqli_real_escape_string($conn, $_POST['state'] ?? 'Maharashtra');
$phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
$mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');

$photo = $existingRow['photo'] ?? '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0 && !empty($_FILES['photo']['name'])) {
    $photoDir = "student_photo/";
    if (!file_exists($photoDir)) {
        mkdir($photoDir, 0777, true);
    }

    $photoExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $photoName = time() . '_student_' . $student_id . '.' . $photoExt;
    $photoPath = $photoDir . $photoName;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
        $photo = $photoName;
    }
}

$markList = $existingRow['mark_list'] ?? '';
if (isset($_FILES['mark-list']) && $_FILES['mark-list']['error'] === 0 && !empty($_FILES['mark-list']['name'])) {
    $uploadDir = "uploads/marklists/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExt = strtolower(pathinfo($_FILES['mark-list']['name'], PATHINFO_EXTENSION));
    $safeRegNo = preg_replace('/[^a-zA-Z0-9]/', '_', $registration_no);
    $markFileName = time() . '_' . $safeRegNo . '_marklist.' . $fileExt;
    $targetPath = $uploadDir . $markFileName;

    if (move_uploaded_file($_FILES['mark-list']['tmp_name'], $targetPath)) {
        $markList = $markFileName;
    }
}

$duplicateSql = "SELECT student_id FROM st_student_master WHERE registration_no = '{$registration_no}' AND student_id != {$student_id} LIMIT 1";
$duplicateResult = mysqli_query($conn, $duplicateSql);
if ($duplicateResult && mysqli_num_rows($duplicateResult) > 0) {
    echo "<script>alert('Registration number already exists for another student.'); window.history.back();</script>";
    exit;
}

$updateSql = "UPDATE st_student_master SET
    academic_year = '{$academic_year}',
    registration_no = '{$registration_no}',
    joining_date = " . ($join_date !== '' ? "'{$join_date}'" : "NULL") . ",
    class_id = " . ($class_id > 0 ? $class_id : "NULL") . ",
    division_id = " . ($division_id > 0 ? $division_id : "NULL") . ",
    grad_year = " . ($grad_year !== null ? $grad_year : "NULL") . ",
    roll_no = " . ($roll_no !== '' ? "'{$roll_no}'" : "NULL") . ",
    department_id = " . ($department_id !== null ? $department_id : "NULL") . ",
    specialization_id = " . ($specialization_id !== null ? $specialization_id : "NULL") . ",
    specialization_subject_id = " . ($specialization_subject_id !== null ? $specialization_subject_id : "NULL") . ",
    cgpa = " . ($cgpa !== null ? "'{$cgpa}'" : "NULL") . ",
    fname = '{$fname}',
    mname = " . ($mname !== '' ? "'{$mname}'" : "NULL") . ",
    lname = " . ($lname !== '' ? "'{$lname}'" : "NULL") . ",
    dob = " . ($dob !== '' ? "'{$dob}'" : "NULL") . ",
    gender = " . ($gender !== '' ? "'{$gender}'" : "NULL") . ",
    nationality = " . ($nationality !== '' ? "'{$nationality}'" : "NULL") . ",
    apaar_id = " . ($apaar_id !== '' ? "'{$apaar_id}'" : "NULL") . ",
    uan = " . ($uan !== '' ? "'{$uan}'" : "NULL") . ",
    pan = " . ($pan !== '' ? "'{$pan}'" : "NULL") . ",
    permanent_address = " . ($permanent_address !== '' ? "'{$permanent_address}'" : "NULL") . ",
    present_address = " . ($present_address !== '' ? "'{$present_address}'" : "NULL") . ",
    city = " . ($city !== '' ? "'{$city}'" : "NULL") . ",
    pincode = " . ($pincode !== '' ? "'{$pincode}'" : "NULL") . ",
    country = " . ($country !== '' ? "'{$country}'" : "NULL") . ",
    state = " . ($state !== '' ? "'{$state}'" : "NULL") . ",
    phone = " . ($phone !== '' ? "'{$phone}'" : "NULL") . ",
    mobile = " . ($mobile !== '' ? "'{$mobile}'" : "NULL") . ",
    email = " . ($email !== '' ? "'{$email}'" : "NULL") . ",
    photo = " . ($photo !== '' ? "'{$photo}'" : "NULL") . ",
    mark_list = " . ($markList !== '' ? "'{$markList}'" : "NULL") . "
WHERE student_id = {$student_id}";

$updateResult = mysqli_query($conn, $updateSql);

if ($updateResult) {
    echo "<script>alert('Student details updated successfully.'); window.location.href='student-info.php';</script>";
    exit;
}

echo "<script>alert('Error while updating student: " . mysqli_real_escape_string($conn, mysqli_error($conn)) . "'); window.history.back();</script>";
?>
