<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_REQUEST['id'])) {
    echo "<div class='alert alert-danger'>No student ID provided.</div>";
    exit;
}

$student_id = intval($_REQUEST['id']);

$sql = "SELECT
    sm.student_id,
    sm.photo,
    sm.mobile,
    sm.registration_no,
    sm.academic_year,
    sm.roll_no,
    sm.fname,
    sm.mname,
    sm.lname,
    sm.dob,
    sm.gender,
    sm.joining_date,
    sm.permanent_address,
    sm.email,
    sm.city,
    sm.phone,
    sm.pincode,
    sm.country,
    sm.state,
    sm.present_address,
    sm.nationality,
    IFNULL(sm.apaar_id, '') AS appar,
    sm.uan,
    sm.pan,
    sm.cgpa,
    IFNULL(cl.class_name, '') AS class_name,
    IFNULL(sec.sections, '') AS sections,
    IFNULL(dep.department_name, '') AS department_name,
    IFNULL(sp.specialization_name, '') AS specialization_name,
    IFNULL(ssb.subject_name, '') AS subject_name
FROM st_student_master sm
LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
LEFT JOIN st_section_master sec ON sec.id = sm.division_id
LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
WHERE sm.student_id = $student_id";

$result = $db_handle->query($sql);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
}

$photo = !empty($row['photo']) ? $row['photo'] : 'student.JPG';
?>
<style>
.view-field { margin-bottom: 15px; }
.view-label { font-weight: bold; color: #333; margin-bottom: 5px; }
.view-value { color: #666; padding: 8px; background-color: #f9f9f9; border-radius: 3px; }
.view-photo { text-align: center; margin-bottom: 20px; }
.view-photo img { max-width: 120px; border-radius: 50%; border: 2px solid #ddd; }
</style>

<div class="view-photo">
    <img src="student_photo/<?php echo htmlspecialchars($photo); ?>" alt="Student Photo">
</div>

<div class="row">
    <div class="col-md-6">
        <div class="view-field"><div class="view-label">Registration Number:</div><div class="view-value"><?php echo htmlspecialchars($row['registration_no'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">First Name:</div><div class="view-value"><?php echo htmlspecialchars($row['fname'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Middle Name:</div><div class="view-value"><?php echo htmlspecialchars($row['mname'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Last Name:</div><div class="view-value"><?php echo htmlspecialchars($row['lname'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Date of Birth:</div><div class="view-value"><?php echo htmlspecialchars($row['dob'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Gender:</div><div class="view-value"><?php echo htmlspecialchars($row['gender'] ?? ''); ?></div></div>
    </div>
    <div class="col-md-6">
        <div class="view-field"><div class="view-label">Academic Year:</div><div class="view-value"><?php echo htmlspecialchars($row['academic_year'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Class:</div><div class="view-value"><?php echo htmlspecialchars($row['class_name'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Section:</div><div class="view-value"><?php echo htmlspecialchars($row['sections'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Roll No:</div><div class="view-value"><?php echo htmlspecialchars($row['roll_no'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Joining Date:</div><div class="view-value"><?php echo htmlspecialchars($row['joining_date'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Department:</div><div class="view-value"><?php echo htmlspecialchars($row['department_name'] ?? ''); ?></div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="view-field"><div class="view-label">Specialization:</div><div class="view-value"><?php echo htmlspecialchars($row['specialization_name'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Specialization Subject:</div><div class="view-value"><?php echo htmlspecialchars($row['subject_name'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">CGPA:</div><div class="view-value"><?php echo htmlspecialchars($row['cgpa'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Nationality:</div><div class="view-value"><?php echo htmlspecialchars($row['nationality'] ?? ''); ?></div></div>
    </div>
    <div class="col-md-6">
        <div class="view-field"><div class="view-label">Mobile:</div><div class="view-value"><?php echo htmlspecialchars($row['mobile'] ?? 'N/A'); ?></div></div>
        <div class="view-field"><div class="view-label">Email:</div><div class="view-value"><?php echo htmlspecialchars($row['email'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">City:</div><div class="view-value"><?php echo htmlspecialchars($row['city'] ?? ''); ?></div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="view-field"><div class="view-label">Permanent Address:</div><div class="view-value"><?php echo htmlspecialchars($row['permanent_address'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Present Address:</div><div class="view-value"><?php echo htmlspecialchars($row['present_address'] ?? ''); ?></div></div>
    </div>
    <div class="col-md-6">
        <div class="view-field"><div class="view-label">Pincode:</div><div class="view-value"><?php echo htmlspecialchars($row['pincode'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">State:</div><div class="view-value"><?php echo htmlspecialchars($row['state'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">Country:</div><div class="view-value"><?php echo htmlspecialchars($row['country'] ?? ''); ?></div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="view-field"><div class="view-label">APAAR ID:</div><div class="view-value"><?php echo htmlspecialchars($row['appar'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">UAN:</div><div class="view-value"><?php echo htmlspecialchars($row['uan'] ?? ''); ?></div></div>
        <div class="view-field"><div class="view-label">PAN:</div><div class="view-value"><?php echo htmlspecialchars($row['pan'] ?? ''); ?></div></div>
    </div>
</div>
