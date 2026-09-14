<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once "../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_SESSION['user_session'])) {
    header("location: ../index.php");
    exit();
}

$loginUserId = intval($_SESSION['user_id'] ?? 0);
$userData = $db_handle->runQuery("SELECT role_id, department_id, user_name FROM st_user_master WHERE user_id='$loginUserId'");
$loginRole = intval($userData[0]['role_id'] ?? 0);
$loginDepartment = intval($userData[0]['department_id'] ?? 0);
$loginUserName = $userData[0]['user_name'] ?? 'Administrator';

// Backend Authorization: Super Admin (1), Admin (2), Coordinator/HOD (3)
if (!in_array($loginRole, [1, 2, 3], true)) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json', true, 403);
        echo json_encode(['success' => false, 'message' => 'Access Denied: You do not have permission to manage mentor assignments.']);
        exit();
    }
    echo "<script>alert('Access Denied'); window.location.href='index.php';</script>";
    exit();
}

// Handle AJAX Mentor Assignment Update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_mentor') {
    header('Content-Type: application/json');
    $subjectId = intval($_POST['subject_id'] ?? 0);
    $newMentorId = intval($_POST['new_mentor_id'] ?? 0);

    if ($subjectId <= 0 || $newMentorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid subject and mentor.']);
        exit();
    }

    // Verify Subject Exists
    $subRes = mysqli_query($db_handle->conn, "SELECT subject_name FROM st_specialization_subject_master WHERE subject_id = $subjectId LIMIT 1");
    if (!$subRes || mysqli_num_rows($subRes) === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected subject does not exist.']);
        exit();
    }
    $subRow = mysqli_fetch_assoc($subRes);
    $subjectName = $subRow['subject_name'];

    // Verify Mentor Exists
    $mRes = mysqli_query($db_handle->conn, "SELECT user_id, COALESCE(NULLIF(TRIM(user_name), ''), email_id) AS mentor_name, email_id FROM st_user_master WHERE user_id = $newMentorId AND role_id = 4 LIMIT 1");
    if (!$mRes || mysqli_num_rows($mRes) === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected mentor is invalid.']);
        exit();
    }
    $mRow = mysqli_fetch_assoc($mRes);
    $newMentorName = $mRow['mentor_name'];

    $updated = $db_handle->assignSubjectMentor($subjectId, $newMentorId, $loginUserId);

    if ($updated) {
        // Count affected students
        $countRes = mysqli_query($db_handle->conn, "
            SELECT COUNT(DISTINCT s.student_id) AS total
            FROM st_student_master s
            LEFT JOIN st_student_semester_history h ON h.student_id = s.student_id AND h.semester_id = s.current_semester_id
            WHERE COALESCE(NULLIF(h.specialization_subject_id, 0), s.specialization_subject_id) = $subjectId
        ");
        $affectedCount = 0;
        if ($countRes && ($cRow = mysqli_fetch_assoc($countRes))) {
            $affectedCount = intval($cRow['total']);
        }

        echo json_encode([
            'success' => true,
            'message' => "Mentor for '{$subjectName}' updated to '{$newMentorName}'. {$affectedCount} enrolled students now dynamically resolve this mentor.",
            'subject_id' => $subjectId,
            'new_mentor_id' => $newMentorId,
            'new_mentor_name' => $newMentorName,
            'affected_students' => $affectedCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating mentor assignment.']);
    }
    exit();
}

// Handle AJAX Enrolled Students List modal query
if (isset($_GET['action']) && $_GET['action'] === 'get_enrolled_students') {
    header('Content-Type: application/json');
    $subjectId = intval($_GET['subject_id'] ?? 0);
    $studentsSql = "
        SELECT s.student_id, s.registration_no, s.roll_no, s.fname, s.email, s.mobile, s.cgpa,
               COALESCE(d.department_name, 'N/A') AS department_name,
               COALESCE(sec.sections, 'A') AS division_name,
               COALESCE(sem.semester_name, CONCAT('Semester ', s.current_semester_id)) AS semester_name,
               IF((SELECT 1 FROM st_nptel_records n WHERE n.student_id = s.student_id LIMIT 1), 1, 0) AS is_nptel
        FROM st_student_master s
        LEFT JOIN st_department_master d ON d.department_id = s.department_id
        LEFT JOIN st_section_master sec ON sec.id = s.division_id
        LEFT JOIN st_semester_master sem ON sem.semester_id = s.current_semester_id
        LEFT JOIN st_student_semester_history h ON h.student_id = s.student_id AND h.semester_id = s.current_semester_id
        WHERE COALESCE(NULLIF(h.specialization_subject_id, 0), s.specialization_subject_id) = $subjectId
        ORDER BY s.fname ASC
    ";
    $students = $db_handle->runQuery($studentsSql) ?? [];
    echo json_encode(['success' => true, 'students' => $students]);
    exit();
}

// Fetch all available mentors for the modal dropdown
$mentorsSql = "
    SELECT u.user_id AS mentor_id,
           COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
           u.email_id,
           COALESCE(d.department_name, 'General') AS department_name,
           IF(u.user_name LIKE 'Mentor %', 1, 0) AS is_dummy
    FROM st_user_master u
    LEFT JOIN st_login l ON l.user_id = u.user_id
    LEFT JOIN st_department_master d ON d.department_id = u.department_id
    WHERE u.role_id = 4
    ORDER BY is_dummy ASC, mentor_name ASC
";
$allMentors = $db_handle->runQuery($mentorsSql) ?? [];

// Fetch distinct filter options
$academicYears = $db_handle->runQuery("SELECT session_id, session_name FROM st_session_master ORDER BY session_id DESC") ?? [];
$departments = $db_handle->runQuery("SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC") ?? [];
$semesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC") ?? [];

// Fetch all subjects with resolved mentor and student count
$subjectsSql = "
    SELECT ssm.subject_id,
           ssm.subject_name,
           ssm.is_active,
           COALESCE(ay.session_name, '2026 -2027') AS academic_year,
           COALESCE(GROUP_CONCAT(DISTINCT d.department_name ORDER BY d.department_name SEPARATOR ', '), 'All Departments') AS departments,
           COALESCE(GROUP_CONCAT(DISTINCT sem.semester_name ORDER BY sem.semester_id SEPARATOR ', '), 'Multiple Semesters') AS semesters,
           COALESCE(GROUP_CONCAT(DISTINCT sem.semester_id ORDER BY sem.semester_id SEPARATOR ','), '') AS semester_ids,
           COALESCE(NULLIF(TRIM(um.user_name), ''), um.email_id, 'Not Assigned') AS current_mentor_name,
           msm.mentor_id AS current_mentor_id,
           IF(um.user_name LIKE 'Mentor %', 1, 0) AS is_dummy_mentor,
           (
               SELECT COUNT(DISTINCT s.student_id)
               FROM st_student_master s
               LEFT JOIN st_student_semester_history h ON h.student_id = s.student_id AND h.semester_id = s.current_semester_id
               WHERE COALESCE(NULLIF(h.specialization_subject_id, 0), s.specialization_subject_id) = ssm.subject_id
           ) AS student_count
    FROM st_specialization_subject_master ssm
    LEFT JOIN st_mentor_subject_mapping msm ON msm.subject_id = ssm.subject_id
    LEFT JOIN st_user_master um ON um.user_id = msm.mentor_id
    LEFT JOIN st_session_master ay ON ay.session_id = ssm.academic_year_id
    LEFT JOIN st_student_master sm_links ON sm_links.specialization_subject_id = ssm.subject_id
    LEFT JOIN st_department_master d ON (d.department_id = ssm.department_id OR d.department_id = sm_links.department_id)
    LEFT JOIN st_semester_master sem ON (sem.semester_id = ssm.semester_id OR sem.semester_id = sm_links.current_semester_id)
    WHERE ssm.subject_id > 0
    GROUP BY ssm.subject_id, ssm.subject_name
    ORDER BY ssm.subject_name ASC
";
$subjectAssignments = $db_handle->runQuery($subjectsSql) ?? [];

// Calculate summary stats
$totalSubjects = count($subjectAssignments);
$totalEnrolled = 0;
$dummyMentorCount = 0;
$facultyMentorCount = 0;
foreach ($subjectAssignments as $sub) {
    $totalEnrolled += intval($sub['student_count']);
    if (!empty($sub['current_mentor_id'])) {
        if (!empty($sub['is_dummy_mentor'])) {
            $dummyMentorCount++;
        } else {
            $facultyMentorCount++;
        }
    }
}

require "header/header.php";
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            <i class="fa fa-users text-primary"></i> Mentor Assignments
            <small>Specialization Subject & Faculty Mentor Management</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="specialization_subject_manage.php">Coordinator</a></li>
            <li class="active">Mentor Assignments</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Info Boxes -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fa fa-book"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Subjects</span>
                        <span class="info-box-number"><?= $totalSubjects ?></span>
                        <span class="progress-description text-muted">Specialization courses</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fa fa-graduation-cap"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Enrolled Students</span>
                        <span class="info-box-number"><?= $totalEnrolled ?></span>
                        <span class="progress-description text-muted">Across all departments</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-purple"><i class="fa fa-user"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Faculty Mentors</span>
                        <span class="info-box-number"><?= $facultyMentorCount ?></span>
                        <span class="progress-description text-muted">Assigned staff</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fa fa-tag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">System Mentors</span>
                        <span class="info-box-number"><?= $dummyMentorCount ?></span>
                        <span class="progress-description text-muted">Deterministic placeholders</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar Box -->
        <div class="box box-default">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-7 col-sm-12">
                        <div class="btn-group" role="group" id="semFilterButtonGroup">
                            <button type="button" class="btn btn-default active sem-filter-btn" data-sem-filter="all">All Semesters (<?= $totalSubjects ?>)</button>
                            <button type="button" class="btn btn-default sem-filter-btn" data-sem-filter="3">Semester III</button>
                            <button type="button" class="btn btn-default sem-filter-btn" data-sem-filter="5">Semester V</button>
                            <button type="button" class="btn btn-default sem-filter-btn" data-sem-filter="7">Semester VII</button>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-12">
                        <div class="input-group">
                            <input type="text" id="subjectLiveSearch" class="form-control" placeholder="Search by subject, mentor, or department...">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Assignments Table Card -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list"></i> Subject-Wise Mentor Allocation Matrix</h3>
                <div class="box-tools pull-right">
                    <span class="label label-primary">AY 2026-27</span>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table id="mentorAssignmentsTable" class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr class="bg-gray-light">
                            <th style="width: 50px; text-align: center;">#</th>
                            <th>Specialization Subject</th>
                            <th>Department(s)</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th style="text-align: center;">Enrolled Students</th>
                            <th>Assigned Mentor</th>
                            <th style="text-align: center; width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectAssignments as $idx => $sub): 
                            $isDummy = !empty($sub['is_dummy_mentor']);
                            $mentorLabelClass = $isDummy ? 'label-warning' : 'label-success';
                            $mentorType = $isDummy ? 'dummy' : 'faculty';
                        ?>
                            <tr data-subject-id="<?= $sub['subject_id'] ?>" 
                                data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                                data-ay="<?= htmlspecialchars($sub['academic_year']) ?>"
                                data-semester-ids="<?= htmlspecialchars($sub['semester_ids']) ?>"
                                data-mentor-type="<?= $mentorType ?>"
                                data-mentor-name="<?= htmlspecialchars($sub['current_mentor_name']) ?>"
                                data-mentor-id="<?= intval($sub['current_mentor_id']) ?>">
                                <td style="text-align: center;"><?= $idx + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($sub['subject_name']) ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $depts = explode(', ', $sub['departments']);
                                    foreach ($depts as $d): ?>
                                        <span class="label label-default" style="display:inline-block; margin: 1px;"><?= htmlspecialchars($d) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <span class="label label-info"><?= htmlspecialchars($sub['semesters']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($sub['academic_year']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-default btn-xs btn-view-students" 
                                            data-subject-id="<?= $sub['subject_id'] ?>" 
                                            data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                                            title="View enrolled students roster">
                                        <i class="fa fa-users text-primary"></i> <strong><?= intval($sub['student_count']) ?></strong> Students
                                    </button>
                                </td>
                                <td class="mentor-col">
                                    <span class="label <?= $mentorLabelClass ?>" style="font-size: 12px; padding: 4px 8px;">
                                        <i class="fa <?= $isDummy ? 'fa-tag' : 'fa-check' ?>"></i>
                                        <span class="mentor-name-text"><?= htmlspecialchars($sub['current_mentor_name']) ?></span>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-primary btn-xs btn-change-mentor" 
                                            data-subject-id="<?= $sub['subject_id'] ?>" 
                                            data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                                            data-current-mentor-id="<?= intval($sub['current_mentor_id']) ?>"
                                            data-current-mentor-name="<?= htmlspecialchars($sub['current_mentor_name']) ?>">
                                        <i class="fa fa-edit"></i> Change Mentor
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="box-footer clearfix text-muted" style="font-size: 12px;">
                <i class="fa fa-info-circle text-primary"></i> Reassigning a mentor here dynamically propagates across all enrolled student records, reports, and dashboards.
            </div>
        </div>
    </section>
</div>

<!-- CHANGE MENTOR MODAL -->
<div class="modal fade" id="changeMentorModal" tabindex="-1" role="dialog" aria-labelledby="changeMentorModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="changeMentorModalLabel">
                    <i class="fa fa-edit"></i> Change Specialization Mentor
                </h4>
            </div>
            <form id="changeMentorForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_mentor">
                    <input type="hidden" name="subject_id" id="modal_subject_id">

                    <div id="modalAlert" style="display: none;"></div>

                    <div class="form-group">
                        <label>Specialization Subject</label>
                        <div id="modal_subject_name_display" class="well well-sm" style="margin-bottom: 0; font-weight: bold;"></div>
                    </div>

                    <div class="form-group">
                        <label>Current Assigned Mentor</label>
                        <div id="modal_current_mentor_display" class="well well-sm text-yellow" style="margin-bottom: 0; font-weight: bold;">
                            <i class="fa fa-user"></i> <span id="modal_current_mentor_text"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Select New Mentor <span class="text-danger">*</span></label>
                        <select name="new_mentor_id" id="modal_new_mentor_select" class="form-control" required>
                            <option value="">-- Select Faculty or Dummy Mentor --</option>
                            <optgroup label="Faculty Members">
                                <?php foreach ($allMentors as $m): if (empty($m['is_dummy'])): ?>
                                    <option value="<?= $m['mentor_id'] ?>">
                                        <?= htmlspecialchars($m['mentor_name']) ?> (<?= htmlspecialchars($m['department_name']) ?> &bull; <?= htmlspecialchars($m['email_id']) ?>)
                                    </option>
                                <?php endif; endforeach; ?>
                            </optgroup>
                            <optgroup label="System Placeholder Mentors">
                                <?php foreach ($allMentors as $m): if (!empty($m['is_dummy'])): ?>
                                    <option value="<?= $m['mentor_id'] ?>">
                                        <?= htmlspecialchars($m['mentor_name']) ?> (<?= htmlspecialchars($m['email_id']) ?>)
                                    </option>
                                <?php endif; endforeach; ?>
                            </optgroup>
                        </select>
                        <p class="help-block"><i class="fa fa-info-circle text-primary"></i> All enrolled students taking this subject will immediately resolve to the selected mentor.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveMentorChange" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Mentor Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ENROLLED STUDENTS LIST MODAL -->
<div class="modal fade" id="enrolledStudentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-graduation-cap"></i> Enrolled Students &mdash; <span id="enrolledSubjectTitle"></span>
                </h4>
            </div>
            <div class="modal-body">
                <div id="enrolledStudentsLoading" class="text-center" style="padding: 30px;">
                    <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="text-muted" style="margin-top: 10px;">Loading student roster...</p>
                </div>
                <div id="enrolledStudentsContent" style="display: none;" class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr class="bg-gray-light">
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Student Name</th>
                                <th>ERP ID / Reg No</th>
                                <th>Dept</th>
                                <th>Div</th>
                                <th>Roll No</th>
                                <th>Semester</th>
                                <th>CGPA</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody id="enrolledStudentsTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Open Change Mentor Modal
    $('.btn-change-mentor').on('click', function() {
        var subjectId = $(this).data('subject-id');
        var subjectName = $(this).data('subject-name');
        var currentMentorId = $(this).data('current-mentor-id');
        var currentMentorName = $(this).data('current-mentor-name');

        $('#modal_subject_id').val(subjectId);
        $('#modal_subject_name_display').text(subjectName);
        $('#modal_current_mentor_text').text(currentMentorName);
        $('#modal_new_mentor_select').val(currentMentorId);
        $('#modalAlert').hide().empty();

        $('#changeMentorModal').modal('show');
    });

    // Handle AJAX Mentor Change Submission
    $('#changeMentorForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = $('#btnSaveMentorChange');
        var originalBtnHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: 'mentor_assignment.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalBtnHtml);
                if (response && response.success) {
                    $('#modalAlert').html(
                        '<div class="alert alert-success alert-dismissable">' +
                        '<i class="fa fa-check-circle"></i> ' + response.message +
                        '</div>'
                    ).fadeIn();

                    // Update row in table immediately
                    var row = $('tr[data-subject-id="' + response.subject_id + '"]');
                    var isDummy = response.new_mentor_name.indexOf('Mentor ') === 0;
                    var labelClass = isDummy ? 'label-warning' : 'label-success';
                    var icon = isDummy ? 'fa-tag' : 'fa-check';

                    row.find('.mentor-col').html(
                        '<span class="label ' + labelClass + '" style="font-size: 12px; padding: 4px 8px;">' +
                        '<i class="fa ' + icon + '"></i> ' +
                        '<span class="mentor-name-text">' + $('<div>').text(response.new_mentor_name).html() + '</span>' +
                        '</span>'
                    );

                    row.find('.btn-change-mentor')
                       .data('current-mentor-id', response.new_mentor_id)
                       .data('current-mentor-name', response.new_mentor_name);

                    row.data('mentor-name', response.new_mentor_name);
                    row.data('mentor-id', response.new_mentor_id);
                    row.data('mentor-type', isDummy ? 'dummy' : 'faculty');

                    setTimeout(function() {
                        $('#changeMentorModal').modal('hide');
                    }, 1000);
                } else {
                    var errorMsg = response && response.message ? response.message : 'Unable to update mentor assignment.';
                    $('#modalAlert').html(
                        '<div class="alert alert-danger alert-dismissable">' +
                        '<i class="fa fa-exclamation-triangle"></i> ' + errorMsg +
                        '</div>'
                    ).fadeIn();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalBtnHtml);
                var err = xhr.status === 403 ? 'Access Denied: You do not have permission to change mentor assignments.' : 'Server error processing request.';
                $('#modalAlert').html(
                    '<div class="alert alert-danger alert-dismissable">' +
                    '<i class="fa fa-exclamation-triangle"></i> ' + err +
                    '</div>'
                ).fadeIn();
            }
        });
    });

    // View Enrolled Students Modal
    $('.btn-view-students').on('click', function() {
        var subjectId = $(this).data('subject-id');
        var subjectName = $(this).data('subject-name');

        $('#enrolledSubjectTitle').text(subjectName);
        $('#enrolledStudentsLoading').show();
        $('#enrolledStudentsContent').hide();
        $('#enrolledStudentsTableBody').empty();
        $('#enrolledStudentsModal').modal('show');

        $.ajax({
            url: 'mentor_assignment.php?action=get_enrolled_students&subject_id=' + subjectId,
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                $('#enrolledStudentsLoading').hide();
                $('#enrolledStudentsContent').show();
                if (resp && resp.students && resp.students.length > 0) {
                    var rowsHtml = '';
                    $.each(resp.students, function(i, s) {
                        rowsHtml += '<tr>' +
                            '<td style="text-align: center;">' + (i + 1) + '</td>' +
                            '<td><strong>' + $('<div>').text(s.fname).html() + '</strong></td>' +
                            '<td><code>' + $('<div>').text(s.registration_no).html() + '</code></td>' +
                            '<td><span class="label label-default">' + $('<div>').text(s.department_name).html() + '</span></td>' +
                            '<td>' + $('<div>').text(s.division_name).html() + '</td>' +
                            '<td>' + $('<div>').text(s.roll_no).html() + '</td>' +
                            '<td><span class="label label-info">' + $('<div>').text(s.semester_name).html() + '</span></td>' +
                            '<td><span class="badge bg-blue">' + $('<div>').text(s.cgpa || 'N/A').html() + '</span></td>' +
                            '<td><a href="mailto:' + encodeURIComponent(s.email) + '">' + $('<div>').text(s.email).html() + '</a></td>' +
                        '</tr>';
                    });
                    $('#enrolledStudentsTableBody').html(rowsHtml);
                } else {
                    $('#enrolledStudentsTableBody').html('<tr><td colspan="9" class="text-center text-muted" style="padding: 25px;">No students currently enrolled in this specialization subject.</td></tr>');
                }
            },
            error: function() {
                $('#enrolledStudentsLoading').hide();
                $('#enrolledStudentsContent').show();
                $('#enrolledStudentsTableBody').html('<tr><td colspan="9" class="text-center text-danger" style="padding: 25px;">Error retrieving enrolled student list.</td></tr>');
            }
        });
    });

    // Semester Filter Button Group
    var activeSemFilter = 'all';
    $('.sem-filter-btn').on('click', function() {
        $('.sem-filter-btn').removeClass('active btn-primary').addClass('btn-default');
        $(this).removeClass('btn-default').addClass('active btn-primary');
        activeSemFilter = $(this).data('sem-filter');
        filterTable();
    });

    // Live Search Filter
    $('#subjectLiveSearch').on('keyup', function() {
        filterTable();
    });

    function filterTable() {
        var query = ($('#subjectLiveSearch').val() || '').toLowerCase().trim();

        $('#mentorAssignmentsTable tbody tr').each(function() {
            var row = $(this);
            var subjectName = (row.data('subject-name') || '').toLowerCase();
            var mentorName = (row.data('mentor-name') || '').toLowerCase();
            var semIds = (row.data('semester-ids') || '').toString().split(',');

            var matchesSearch = !query || subjectName.indexOf(query) !== -1 || mentorName.indexOf(query) !== -1;
            var matchesSem = activeSemFilter === 'all' || semIds.indexOf(activeSemFilter.toString()) !== -1;

            if (matchesSearch && matchesSem) {
                row.show();
            } else {
                row.hide();
            }
        });
    }
});
</script>

<?php require "header/footer.php"; ?>
