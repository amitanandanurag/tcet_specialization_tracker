<?php
require "header/header.php";

$student = null;
$linkedStudentId = 0;

if ((int) ($usertype ?? 0) !== 5) {
    header("location: index.php");
    exit;
}

if (!empty($userid)) {
    $userCheckSql = "SELECT student_id FROM st_user_master WHERE user_id = ? AND student_id > 0 LIMIT 1";
    $userCheckStmt = mysqli_prepare($db_handle->conn, $userCheckSql);

    if ($userCheckStmt) {
        mysqli_stmt_bind_param($userCheckStmt, 'i', $userid);
        mysqli_stmt_execute($userCheckStmt);
        $userCheckResult = mysqli_stmt_get_result($userCheckStmt);
        if ($userCheckResult && ($userRow = mysqli_fetch_assoc($userCheckResult))) {
            $linkedStudentId = intval($userRow['student_id']);
        }
        mysqli_stmt_close($userCheckStmt);
    }
}

if ($linkedStudentId > 0) {
    $studentSql = "SELECT s.student_id, s.academic_year_id, s.registration_no, s.roll_no, s.grad_year,
                          s.cgpa, s.fname, s.mobile, s.email, s.status, s.created_at,
                          c.class_name, sec.sections AS division_name, d.department_name,
                          sp.specialization_name, sub.subject_name, current_history_subject.subject_name AS current_history_subject_name, ay.session_name
                                  , sem.semester_name AS current_semester_name
                                  , (SELECT um.user_name FROM st_mentor_student_mapping mm
                                      INNER JOIN st_user_master um ON um.user_id = mm.mentor_id
                                      WHERE mm.student_id = s.student_id AND mm.semester_id = s.current_semester_id
                                      ORDER BY mm.mapping_id DESC LIMIT 1) AS current_mentor_name
                                  , (SELECT h.progress_percent FROM st_student_semester_history h
                                      WHERE h.student_id = s.student_id AND h.semester_id = s.current_semester_id
                                      LIMIT 1) AS current_progress
                   FROM st_student_master s
                   LEFT JOIN st_class_master c ON c.class_id = s.class_id
                   LEFT JOIN st_section_master sec ON sec.id = s.division_id
                   LEFT JOIN st_department_master d ON d.department_id = s.department_id
                   LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id
                   LEFT JOIN st_student_semester_history current_history ON current_history.student_id = s.student_id AND current_history.semester_id = s.current_semester_id
                   LEFT JOIN st_specialization_subject_master sub ON sub.subject_id = s.specialization_subject_id
                   LEFT JOIN st_specialization_subject_master current_history_subject ON current_history_subject.subject_id = current_history.specialization_subject_id
                   LEFT JOIN st_session_master ay ON ay.session_id = s.academic_year_id
                   LEFT JOIN st_semester_master sem ON sem.semester_id = s.current_semester_id
                   WHERE s.student_id = ?
                   LIMIT 1";

    $studentStmt = mysqli_prepare($db_handle->conn, $studentSql);
    if ($studentStmt) {
        mysqli_stmt_bind_param($studentStmt, 'i', $linkedStudentId);
        mysqli_stmt_execute($studentStmt);
        $studentResult = mysqli_stmt_get_result($studentStmt);
        if ($studentResult) {
            $student = mysqli_fetch_assoc($studentResult);
        }
        mysqli_stmt_close($studentStmt);
    }
}
$statusText = 'Pending';
$statusClass = 'label-warning';
if ($student && (int) $student['status'] === 1) {
    $statusText = 'Approved';
    $statusClass = 'label-success';
}


$isNptelPassed = false;
if ($student) {
    $studentId = intval($student['student_id']);
    // Check st_offline_marks_entry
    $offCheck = mysqli_query($db_handle->conn, "SELECT 1 FROM st_offline_marks_entry WHERE student_id = $studentId AND nptel_status = 'Pass' LIMIT 1");
    if ($offCheck && mysqli_num_rows($offCheck) > 0) {
        $isNptelPassed = true;
    } else {
        // Check st_nptel_records
        $nptelCheck = mysqli_query($db_handle->conn, "SELECT 1 FROM st_nptel_records WHERE student_id = $studentId AND (pass_fail = 'Pass' OR score >= 40) LIMIT 1");
        if ($nptelCheck && mysqli_num_rows($nptelCheck) > 0) {
            $isNptelPassed = true;
        }
    }
}

function student_dashboard_value($value)
{
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? htmlspecialchars($value) : 'N/A';
}
?>

<style>
    .student-hero {
        background: #ffffff;
        border-top: 3px solid #3c8dbc;
        padding: 22px;
        margin-bottom: 20px;
    }

    .student-hero h2 {
        margin: 0 0 6px;
        font-weight: 600;
        color: #2f3b45;
    }

    .student-info-list {
        margin: 0;
    }

    .student-info-list dt {
        color: #6b7785;
        font-weight: 600;
    }

    .student-info-list dd {
        margin-bottom: 14px;
        color: #2f3b45;
    }

    .student-stat {
        min-height: 108px;
    }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-dashboard"></i> Student Dashboard</h1>
        <ol class="breadcrumb">
            <li><a href="student_dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Student Dashboard</li>
        </ol>
    </section>

    <section class="content">
        <?php if (!$student): ?>
            <div class="alert alert-warning" style="padding: 15px;">
                <i class="fa fa-info-circle"></i> 
                <strong>No profile found.</strong> Please complete your admission form to see your dashboard.
                <br>
                <a href="student_admission.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">
                    <i class="fa fa-arrow-right"></i> Go to Admission Form
                </a>
            </div>
        <?php else: ?>
            <div class="callout callout-info" style="margin-bottom: 20px; background-color: #00c0ef !important; color: #fff !important;">
                <h4><i class="fa fa-certificate"></i> NPTEL Actions</h4>
                <p>Select your NPTEL result status: If you passed, upload your certificate. If you failed, submit the cancellation form.</p>
                <a href="nptel_certificate.php" class="btn btn-default btn-sm" style="margin-top: 5px; color: #333; font-weight: bold;">
                    <i class="fa fa-arrow-circle-right"></i> Go to NPTEL Actions
                </a>
            </div>
            <div class="box student-hero" style="position: relative;">
                <a href="student_admission.php?edit=1" class="btn btn-primary pull-right" style="margin-top: 10px;"><i class="fa fa-edit"></i> Edit Profile / Promote Semester</a>
                <h2>Welcome, <?php echo student_dashboard_value($student['fname']); ?></h2>
                <p class="text-muted">
                    Registration No: <strong><?php echo student_dashboard_value($student['registration_no']); ?></strong>
                    &nbsp; | &nbsp;
                    Status: <span class="label <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </p>
            </div>

            <div class="row">
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-aqua student-stat">
                        <div class="inner">
                            <h3><?php echo student_dashboard_value($student['cgpa']); ?></h3>
                            <p>CGPA</p>
                        </div>
                        <div class="icon"><i class="fa fa-line-chart"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-green student-stat">
                        <div class="inner">
                            <h3><?php echo student_dashboard_value($student['class_name']); ?></h3>
                            <p>Class</p>
                        </div>
                        <div class="icon"><i class="fa fa-graduation-cap"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-yellow student-stat">
                        <div class="inner">
                            <h3><?php echo student_dashboard_value($student['division_name']); ?></h3>
                            <p>Division</p>
                        </div>
                        <div class="icon"><i class="fa fa-users"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-red student-stat">
                        <div class="inner">
                            <h3><?php echo student_dashboard_value($student['roll_no']); ?></h3>
                            <p>Roll No</p>
                        </div>
                        <div class="icon"><i class="fa fa-id-card"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-user"></i> My Profile</h3>
                        </div>
                        <div class="box-body">
                            <dl class="row student-info-list">
                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8"><?php echo student_dashboard_value($student['fname']); ?></dd>
                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8"><?php echo student_dashboard_value($student['email']); ?></dd>
                                <dt class="col-sm-4">Mobile</dt>
                                <dd class="col-sm-8"><?php echo student_dashboard_value($student['mobile']); ?></dd>
                                <dt class="col-sm-4">Academic Year</dt>
                                <dd class="col-sm-8"><?php echo student_dashboard_value($student['session_name'] ?? $student['academic_year_id']); ?></dd>
                                <dt class="col-sm-4">Graduation Year</dt>
                                <dd class="col-sm-8"><?php echo student_dashboard_value($student['grad_year']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-book"></i> Academic Details</h3>
                        </div>
                        <div class="box-body">
                            <dl class="row student-info-list">
                                <dt class="col-sm-5">Department</dt>
                                <dd class="col-sm-7"><?php echo student_dashboard_value($student['department_name']); ?></dd>
                                <dt class="col-sm-5">Current Semester</dt>
                                <dd class="col-sm-7"><?php echo student_dashboard_value($student['current_semester_name'] ?? $student['current_semester_id']); ?></dd>
                                <dt class="col-sm-5">Specialization</dt>
                                <dd class="col-sm-7"><?php echo student_dashboard_value($student['specialization_name']); ?></dd>
                                <dt class="col-sm-5">Subject</dt>
                                <dd class="col-sm-7"><?php echo student_dashboard_value($student['subject_name'] ?: ($student['current_history_subject_name'] ?? '')); ?></dd>
                                <dt class="col-sm-5">Current Mentor</dt>
                                <dd class="col-sm-7"><?php echo student_dashboard_value($student['current_mentor_name']); ?></dd>
                                <dt class="col-sm-5">Current Progress</dt>
                                <dd class="col-sm-7"><?php echo $student['current_progress'] !== null ? student_dashboard_value($student['current_progress']) . '%' : 'N/A'; ?></dd>
                                <dt class="col-sm-5">Applied On</dt>
                                <dd class="col-sm-7"><?php echo student_dashboard_value($student['created_at']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEMESTER REGISTRATION & COURSE HISTORY -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-history"></i> Semester & Specialization History</h3>
                        </div>
                        <div class="box-body table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #f4f4f4;">
                                        <th>Semester</th>
                                        <th>Academic Year</th>
                                        <th>Class</th>
                                        <th>Division</th>
                                        <th>Specialization</th>
                                        <th>Course / Subject</th>
                                        <th>Mentor</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>CGPA</th>
                                        <th>Research Components</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $histSql = "SELECT
                                        sh.semester_id,
                                        sh.mentor_id,
                                        sh.progress_percent,
                                        sh.status,
                                        IFNULL(mentor.user_name, '') AS mentor_name,
                                        sh.cgpa,
                                        sh.research_core_vii,
                                        sh.research_core_viii,
                                        IFNULL(cl.class_name, 'N/A') AS class_name,
                                        IFNULL(sec.sections, 'N/A') AS section_name,
                                        IFNULL(sp.specialization_name, 'N/A') AS specialization_name,
                                        IFNULL(ssb.subject_name, '') AS specialization_subject_name,
                                        IFNULL(mc.course_name, '') AS minor_course_name,
                                        IFNULL(ms.subject_name, '') AS minor_subject_name,
                                        IFNULL(sess.session_name, 'N/A') AS academic_year_name,
                                        IFNULL(sem.semester_name, 'N/A') AS semester_name,
                                        IFNULL(rsi.subject_name, '') AS research_component_i_name,
                                        IFNULL(rsii.subject_name, '') AS research_component_ii_name
                                    FROM st_student_semester_history sh
                                    LEFT JOIN st_class_master cl ON cl.class_id = sh.class_id
                                    LEFT JOIN st_section_master sec ON sec.id = sh.division_id
                                    LEFT JOIN st_specialization_master sp ON sp.specialization_id = sh.specialization_id
                                    LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sh.specialization_subject_id
                                    LEFT JOIN st_minorcourse mc ON mc.course_id = sh.minor_course_id
                                    LEFT JOIN st_minorsubject ms ON ms.subject_id = sh.minor_subject_id
                                    LEFT JOIN st_session_master sess ON sess.session_id = sh.academic_year_id
                                    LEFT JOIN st_semester_master sem ON sem.semester_id = sh.semester_id
                                    LEFT JOIN st_specialization_subject_master rsi ON rsi.subject_id = sh.research_component_i_id
                                    LEFT JOIN st_specialization_subject_master rsii ON rsii.subject_id = sh.research_component_ii_id
                                    LEFT JOIN st_user_master mentor ON mentor.user_id = sh.mentor_id
                                    WHERE sh.student_id = $studentId
                                    ORDER BY sem.semester_name ASC, sh.semester_id ASC";
                                    
                                    $histRes = mysqli_query($db_handle->conn, $histSql);
                                    if ($histRes && mysqli_num_rows($histRes) > 0) {
                                        while ($hrow = mysqli_fetch_assoc($histRes)) {
                                            $h_spec = strtolower($hrow['specialization_name']);
                                            $h_is_minor = strpos($h_spec, 'minor multidisciplinary') !== false;
                                            $h_is_research = strpos($h_spec, 'research') !== false;
                                            
                                            // Format Course/Subject details
                                            $course_details = 'N/A';
                                            if ($h_is_minor) {
                                                $course_details = "<strong>Course:</strong> " . htmlspecialchars($hrow['minor_course_name'] ?: 'N/A') . "<br><strong>Subject:</strong> " . htmlspecialchars($hrow['minor_subject_name'] ?: 'N/A');
                                            } else if (!empty($hrow['specialization_subject_name'])) {
                                                $course_details = htmlspecialchars($hrow['specialization_subject_name']);
                                            }
                                            
                                            // Format Research Details
                                            $research_details = 'N/A';
                                            if ($h_is_research) {
                                                $research_details = "<strong>Comp I (OE II):</strong> " . htmlspecialchars($hrow['research_component_i_name'] ?: 'N/A') . "<br>"
                                                                  . "<strong>Core VII:</strong> " . htmlspecialchars($hrow['research_core_vii'] ?: 'N/A') . "<br>"
                                                                  . "<strong>Comp II (OE III):</strong> " . htmlspecialchars($hrow['research_component_ii_name'] ?: 'N/A') . "<br>"
                                                                  . "<strong>Core VIII:</strong> " . htmlspecialchars($hrow['research_core_viii'] ?: 'N/A');
                                            }
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($hrow['semester_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($hrow['academic_year_name']); ?></td>
                                            <td><?php echo htmlspecialchars($hrow['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($hrow['section_name']); ?></td>
                                            <td><?php echo htmlspecialchars($hrow['specialization_name']); ?></td>
                                            <td><?php echo $course_details; ?></td>
                                            <td><?php echo htmlspecialchars($hrow['mentor_name'] ?: 'Not assigned'); ?></td>
                                            <td><?php echo $hrow['progress_percent'] !== null ? htmlspecialchars($hrow['progress_percent']) . '%' : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($hrow['status']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($hrow['cgpa'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo $research_details; ?></td>
                                        </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                        <tr>
                                            <td colspan="11" class="text-center text-muted">No historical semester registrations recorded.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include "header/footer.php"; ?>
