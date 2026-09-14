<?php
require "header/header.php";

$studentId = 0;
$semesterId = 0;

if ((int)($usertype ?? 0) === 5) {
    if (!empty($userid)) {
        $userCheckSql = "SELECT sm.student_id, sm.current_semester_id 
                         FROM st_student_master sm
                         JOIN st_user_master um ON um.student_id = sm.student_id 
                         WHERE um.user_id = ? LIMIT 1";
        $userCheckStmt = mysqli_prepare($db_handle->conn, $userCheckSql);
        if ($userCheckStmt) {
            mysqli_stmt_bind_param($userCheckStmt, 'i', $userid);
            mysqli_stmt_execute($userCheckStmt);
            $userCheckResult = mysqli_stmt_get_result($userCheckStmt);
            if ($userCheckResult && ($userRow = mysqli_fetch_assoc($userCheckResult))) {
                $studentId = intval($userRow['student_id']);
                $semesterId = intval($userRow['current_semester_id']);
            }
            mysqli_stmt_close($userCheckStmt);
        }
    }
} else {
    $studentId = intval($_GET['student_id'] ?? $_GET['id'] ?? 0);
    if ($studentId > 0) {
        $userCheckSql = "SELECT current_semester_id FROM st_student_master WHERE student_id = ? LIMIT 1";
        $userCheckStmt = mysqli_prepare($db_handle->conn, $userCheckSql);
        if ($userCheckStmt) {
            mysqli_stmt_bind_param($userCheckStmt, 'i', $studentId);
            mysqli_stmt_execute($userCheckStmt);
            $userCheckResult = mysqli_stmt_get_result($userCheckStmt);
            if ($userCheckResult && ($userRow = mysqli_fetch_assoc($userCheckResult))) {
                $semesterId = intval($userRow['current_semester_id']);
            }
            mysqli_stmt_close($userCheckStmt);
        }
    }
}

if ($studentId <= 0) {
    if ((int)($usertype ?? 0) === 5) {
        echo "<div class='content-wrapper'><section class='content'><div class='alert alert-warning'><i class='fa fa-info-circle'></i> Student profile not found. Please complete your admission form first.</div></section></div>";
        include "header/footer.php";
        exit;
    } else {
        ?>
        <div class="content-wrapper">
            <section class="content-header">
                <h1><i class="fa fa-certificate"></i> NPTEL Status Form</h1>
            </section>
            <section class="content">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Select Student to Manage NPTEL Status</h3>
                    </div>
                    <div class="box-body">
                        <form method="GET" action="">
                            <div class="form-group">
                                <label>Select Student</label>
                                <select class="form-control select" name="student_id" required style="width: 100%;" onchange="this.form.submit()">
                                    <option value="">Select Student</option>
                                    <?php
                                    $studentsResult = mysqli_query($db_handle->conn, "SELECT student_id, registration_no, fname FROM st_student_master ORDER BY fname ASC");
                                    while ($sRow = mysqli_fetch_assoc($studentsResult)) {
                                        echo "<option value='{$sRow['student_id']}'>{$sRow['fname']} ({$sRow['registration_no']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
        <?php
        include "header/footer.php";
        exit;
    }
}

// Fetch all available subjects to select NPTEL course name
$allSubjects = array();
$subRes = mysqli_query($db_handle->conn, "SELECT DISTINCT subject_name FROM st_specialization_subject_master ORDER BY subject_name");
if ($subRes) {
    while ($row = mysqli_fetch_assoc($subRes)) {
        $allSubjects[] = $row['subject_name'];
    }
}

$successMsg = '';
$errorMsg = '';

// Handle NPTEL result and course submission by student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultStatus = $_POST['result_status'] ?? 'Pass';
    $courseName = trim($_POST['course_name'] ?? '');
    if ($courseName === 'Other' && !empty($_POST['custom_course_name'])) {
        $courseName = trim($_POST['custom_course_name']);
    }
    
    if ($courseName === '') {
        $errorMsg = 'Please select or enter your NPTEL course name.';
    } else {
        // Find or create st_offline_marks_entry
        $checkSql = "SELECT entry_id FROM st_offline_marks_entry WHERE student_id = ? AND semester_id = ? AND course_name = ? LIMIT 1";
        $checkStmt = mysqli_prepare($db_handle->conn, $checkSql);
        $existingEntryId = 0;
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, 'iis', $studentId, $semesterId, $courseName);
            mysqli_stmt_execute($checkStmt);
            $checkRes = mysqli_stmt_get_result($checkStmt);
            if ($checkRes && ($checkRow = mysqli_fetch_assoc($checkRes))) {
                $existingEntryId = intval($checkRow['entry_id']);
            }
            mysqli_stmt_close($checkStmt);
        }

        if ($resultStatus === 'Pass') {
            $completionDate = trim($_POST['completion_date'] ?? '');
            
            if ($completionDate === '') {
                $errorMsg = 'Please enter the completion date.';
            } elseif (!isset($_FILES['certificate_file']) || $_FILES['certificate_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'Please select a valid certificate file to upload.';
            } else {
                $file = $_FILES['certificate_file'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                $allowedExts = array('pdf', 'png', 'jpg', 'jpeg');
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Secure type check using finfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowedMimes = array('application/pdf', 'image/png', 'image/jpeg', 'image/pjpeg');
                
                if ($file['size'] > $maxSize) {
                    $errorMsg = 'File size exceeds 2MB limit.';
                } elseif (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
                    $errorMsg = 'Only PDF, PNG, and JPG files are allowed.';
                } else {
                    $uploadDir = "uploads/certificates/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $secName = "nptel_" . $studentId . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                    $destPath = $uploadDir . $secName;
                    
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $enrollmentId = 0;
                        $enRes = mysqli_query($db_handle->conn, "SELECT enrollment_id FROM st_enrollment WHERE student_id = $studentId ORDER BY enrollment_id DESC LIMIT 1");
                        if ($enRes && $enRow = mysqli_fetch_assoc($enRes)) {
                            $enrollmentId = intval($enRow['enrollment_id']);
                        }
                        
                        // Insert certificate details
                        $insertSql = "INSERT INTO st_minor_certificates (student_id, enrollment_id, course_name, issuing_institution, completion_date, file_path, verification_status) 
                                      VALUES (?, ?, ?, 'NPTEL', ?, ?, 'Pending')";
                        $stmt = mysqli_prepare($db_handle->conn, $insertSql);
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "iisss", $studentId, $enrollmentId, $courseName, $completionDate, $destPath);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }

                        // Write to st_offline_marks_entry
                        if ($existingEntryId > 0) {
                            mysqli_query($db_handle->conn, "UPDATE st_offline_marks_entry SET nptel_status = 'Pass' WHERE entry_id = $existingEntryId");
                        } else {
                            mysqli_query($db_handle->conn, "INSERT INTO st_offline_marks_entry (student_id, semester_id, course_name, nptel_status) VALUES ($studentId, $semesterId, '" . mysqli_real_escape_string($db_handle->conn, $courseName) . "', 'Pass')");
                        }
                        
                        $successMsg = 'NPTEL Pass status recorded and certificate uploaded successfully.';
                    } else {
                        $errorMsg = 'Error moving uploaded file. Please check directory permissions.';
                    }
                }
            }
        } else {
            // Student selected Fail: process cancellation
            $reason = trim($_POST['reason'] ?? '');
            $confirm = $_POST['confirm_cancellation'] ?? '';
            
            if ($reason === '') {
                $errorMsg = 'Please specify the reason for cancellation.';
            } elseif ($confirm !== '1') {
                $errorMsg = 'You must confirm the cancellation declaration.';
            } else {
                $enrollmentId = 0;
                $enRes = mysqli_query($db_handle->conn, "SELECT enrollment_id FROM st_enrollment WHERE student_id = $studentId ORDER BY enrollment_id DESC LIMIT 1");
                if ($enRes && $enRow = mysqli_fetch_assoc($enRes)) {
                    $enrollmentId = intval($enRow['enrollment_id']);
                }
                
                // Save cancellation details
                $insertSql = "INSERT INTO st_nptel_cancellations (student_id, enrollment_id, course_name, reason, status) 
                              VALUES (?, ?, ?, ?, 'Submitted by Student')";
                $stmt = mysqli_prepare($db_handle->conn, $insertSql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "iiss", $studentId, $enrollmentId, $courseName, $reason);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                // Write to st_offline_marks_entry
                if ($existingEntryId > 0) {
                    mysqli_query($db_handle->conn, "UPDATE st_offline_marks_entry SET nptel_status = 'Fail', remarks = '" . mysqli_real_escape_string($db_handle->conn, $reason) . "' WHERE entry_id = $existingEntryId");
                } else {
                    mysqli_query($db_handle->conn, "INSERT INTO st_offline_marks_entry (student_id, semester_id, course_name, nptel_status, remarks) VALUES ($studentId, $semesterId, '" . mysqli_real_escape_string($db_handle->conn, $courseName) . "', 'Fail', '" . mysqli_real_escape_string($db_handle->conn, $reason) . "')");
                }

                $successMsg = 'NPTEL Fail status and cancellation form submitted successfully.';
            }
        }
    }
}

// Fetch uploaded certificates
$certs = array();
$certRes = mysqli_query($db_handle->conn, "SELECT * FROM st_minor_certificates WHERE student_id = $studentId ORDER BY submitted_at DESC");
if ($certRes) {
    while ($row = mysqli_fetch_assoc($certRes)) {
        $certs[] = $row;
    }
}

// Fetch submitted cancellations
$cancellations = array();
$cancelRes = mysqli_query($db_handle->conn, "SELECT * FROM st_nptel_cancellations WHERE student_id = $studentId ORDER BY submitted_at DESC");
if ($cancelRes) {
    while ($row = mysqli_fetch_assoc($cancelRes)) {
        $cancellations[] = $row;
    }
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-certificate"></i> NPTEL Status Form</h1>
        <ol class="breadcrumb">
            <li><a href="student_dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">NPTEL Status</li>
        </ol>
    </section>

    <section class="content">
        <?php if ($successMsg !== ''): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-check"></i> <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg !== ''): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-ban"></i> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-edit"></i> Submit NPTEL Course Result</h3>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="box-body">
                            <!-- NPTEL Course Dropdown -->
                            <div class="form-group">
                                <label for="course_name">Select NPTEL Course <span style="color: red;">*</span></label>
                                <select class="form-control" name="course_name" id="course_name" required>
                                    <option value="">Select NPTEL Course</option>
                                    <?php foreach ($allSubjects as $cname): ?>
                                        <option value="<?php echo htmlspecialchars($cname); ?>"><?php echo htmlspecialchars($cname); ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">Other (Type below)</option>
                                </select>
                            </div>

                            <!-- Custom Course Text Field (shown if 'Other' selected) -->
                            <div class="form-group" id="custom_course_wrapper" style="display: none;">
                                <label for="custom_course_name">Type Course Name <span style="color: red;">*</span></label>
                                <input type="text" class="form-control" name="custom_course_name" id="custom_course_name" placeholder="Enter NPTEL Course Name">
                            </div>

                            <!-- Result Status Dropdown -->
                            <div class="form-group">
                                <label for="result_status">NPTEL Exam Result <span style="color: red;">*</span></label>
                                <select class="form-control" name="result_status" id="result_status" required>
                                    <option value="Pass">Pass (Upload Certificate)</option>
                                    <option value="Fail">Fail (Fill Cancellation Form)</option>
                                </select>
                            </div>

                            <!-- PASS BLOCK -->
                            <div id="pass_section">
                                <div class="form-group">
                                    <label for="completion_date">Completion Date <span style="color: red;">*</span></label>
                                    <input type="date" class="form-control" name="completion_date" id="completion_date">
                                </div>
                                
                                <div class="form-group">
                                    <label for="certificate_file">Upload Certificate (PDF, PNG, JPG - max 2MB) <span style="color: red;">*</span></label>
                                    <input type="file" name="certificate_file" id="certificate_file" accept=".pdf,.png,.jpg,.jpeg">
                                </div>
                            </div>

                            <!-- FAIL BLOCK -->
                            <div id="fail_section" style="display: none;">
                                <div class="form-group">
                                    <label for="reason">Reason for Cancellation / Failure <span style="color: red;">*</span></label>
                                    <textarea class="form-control" name="reason" id="reason" rows="4" placeholder="Enter reason for NPTEL cancellation..."></textarea>
                                </div>

                                <div class="checkbox">
                                    <label style="font-weight: bold; color: #d9534f;">
                                        <input type="checkbox" name="confirm_cancellation" value="1">
                                        I request cancellation of credit transfer for this NPTEL course and opt for College Examination.
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn-erp-primary"><i class="fa fa-save"></i> Submit Result</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Dashboard Records view -->
            <div class="col-md-6">
                <!-- Certificates Table -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> My Uploaded Certificates</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Completion Date</th>
                                    <th>Status</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($certs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No certificates uploaded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($certs as $cert): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cert['course_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($cert['completion_date'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($cert['verification_status'] === 'Approved'): ?>
                                                    <span class="label label-success">Approved</span>
                                                <?php elseif ($cert['verification_status'] === 'Rejected'): ?>
                                                    <span class="label label-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="label label-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($cert['file_path']); ?>" target="_blank" class="btn btn-xs btn-default">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cancellations Table -->
                <div class="box box-danger" style="margin-top: 20px;">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-warning"></i> My NPTEL Cancellations</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cancellations)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No cancellation forms submitted yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cancellations as $c): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($c['course_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($c['reason'] ?? '-'); ?></td>
                                            <td><span class="label label-danger"><?php echo htmlspecialchars($c['status'] ?? '-'); ?></span></td>
                                            <td><?php echo date('d-m-Y', strtotime($c['submitted_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
$(document).ready(function() {
    function toggleSections() {
        var status = $('#result_status').val();
        if (status === 'Pass') {
            $('#pass_section').show();
            $('#fail_section').hide();
            $('#completion_date').attr('required', 'required');
            $('#certificate_file').attr('required', 'required');
            $('#reason').removeAttr('required');
            $('input[name="confirm_cancellation"]').removeAttr('required');
        } else {
            $('#pass_section').hide();
            $('#fail_section').show();
            $('#completion_date').removeAttr('required');
            $('#certificate_file').removeAttr('required');
            $('#reason').attr('required', 'required');
            $('input[name="confirm_cancellation"]').attr('required', 'required');
        }
    }

    function toggleCustomCourse() {
        var val = $('#course_name').val();
        if (val === 'Other') {
            $('#custom_course_wrapper').show();
            $('#custom_course_name').attr('required', 'required');
        } else {
            $('#custom_course_wrapper').hide();
            $('#custom_course_name').removeAttr('required');
        }
    }

    $('#result_status').change(toggleSections);
    $('#course_name').change(toggleCustomCourse);

    // Run on load
    toggleSections();
    toggleCustomCourse();
});
</script>

<?php include "header/footer.php"; ?>
