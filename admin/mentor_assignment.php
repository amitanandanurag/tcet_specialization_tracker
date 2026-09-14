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

<style>
/* Modern Premium Theme Enhancements */
.st-dashboard-header {
    background: linear-gradient(135deg, #423cbc 0%, #302b8e 100%);
    padding: 24px 30px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 10px 25px rgba(66, 60, 188, 0.2);
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.st-dashboard-title h2 {
    margin: 0 0 6px 0;
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.5px;
    color: #ffffff;
}

.st-dashboard-title p {
    margin: 0;
    font-size: 14px;
    opacity: 0.85;
}

.st-stat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    border: 1px solid #edf2f7;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}

.st-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.st-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    float: right;
}

.st-stat-value {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    margin: 4px 0 0 0;
    line-height: 1.2;
}

.st-stat-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.st-filter-tabs {
    display: flex;
    gap: 8px;
    background: #f1f5f9;
    padding: 6px;
    border-radius: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.st-tab-btn {
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.st-tab-btn:hover {
    color: #334155;
    background: rgba(255, 255, 255, 0.6);
}

.st-tab-btn.active {
    background: #ffffff;
    color: #423cbc;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.st-table-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid #edf2f7;
    overflow: hidden;
}

.st-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 16px 14px;
    border-bottom: 2px solid #e2e8f0;
}

.st-table tbody td {
    padding: 16px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.st-table tbody tr:hover {
    background-color: #f8fafc;
}

.badge-dept {
    background: #e0e7ff;
    color: #4338ca;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    margin: 2px;
}

.badge-sem {
    background: #dbeafe;
    color: #1d4ed8;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-mentor-faculty {
    background: #dcfce7;
    color: #15803d;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #bbf7d0;
}

.badge-mentor-dummy {
    background: #fef3c7;
    color: #b45309;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #fde68a;
}

.btn-action-change {
    background: linear-gradient(135deg, #423cbc 0%, #302b8e 100%);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 7px 16px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(66, 60, 188, 0.25);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-action-change:hover {
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(66, 60, 188, 0.35);
}

.btn-roster-view {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 5px 12px;
    font-weight: 700;
    font-size: 12px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn-roster-view:hover {
    background: #423cbc;
    color: #ffffff;
    border-color: #423cbc;
}
</style>

<div class="content-wrapper" style="min-height: 880px; padding: 20px 25px; background: #f8fafc;">
    <!-- Top Header Banner -->
    <div class="st-dashboard-header">
        <div class="st-dashboard-title">
            <h2><i class="fa fa-users" style="margin-right: 10px;"></i> Mentor Assignments & Specializations</h2>
            <p>Academic Year 2026-27 &bull; Real-time dynamic mentor resolution for all departmental students</p>
        </div>
        <div>
            <span style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 30px; font-weight: 600; font-size: 13px;">
                <i class="fa fa-calendar"></i> AY 2026-27 Active
            </span>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="st-stat-card">
                <div class="st-stat-icon" style="background: #e0e7ff; color: #4338ca;"><i class="fa fa-book"></i></div>
                <p class="st-stat-label">Specialization Subjects</p>
                <h3 class="st-stat-value"><?= $totalSubjects ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="st-stat-card">
                <div class="st-stat-icon" style="background: #dcfce7; color: #16a34a;"><i class="fa fa-graduation-cap"></i></div>
                <p class="st-stat-label">Enrolled Students</p>
                <h3 class="st-stat-value"><?= $totalEnrolled ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="st-stat-card">
                <div class="st-stat-icon" style="background: #f3e8ff; color: #9333ea;"><i class="fa fa-user-circle"></i></div>
                <p class="st-stat-label">Faculty Mentors</p>
                <h3 class="st-stat-value"><?= $facultyMentorCount ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="st-stat-card">
                <div class="st-stat-icon" style="background: #fef3c7; color: #d97706;"><i class="fa fa-tags"></i></div>
                <p class="st-stat-label">Alphabet Dummy Mentors</p>
                <h3 class="st-stat-value"><?= $dummyMentorCount ?></h3>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-8 col-sm-12">
            <div class="st-filter-tabs">
                <button type="button" class="st-tab-btn active" data-sem-filter="all">All Semesters (<?= $totalSubjects ?>)</button>
                <button type="button" class="st-tab-btn" data-sem-filter="3">Semester III</button>
                <button type="button" class="st-tab-btn" data-sem-filter="5">Semester V</button>
                <button type="button" class="st-tab-btn" data-sem-filter="7">Semester VII</button>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 text-right">
            <div class="input-group" style="width: 100%;">
                <span class="input-group-addon" style="background: #ffffff; border-radius: 8px 0 0 8px; border-right: none;"><i class="fa fa-search text-muted"></i></span>
                <input type="text" id="subjectLiveSearch" class="form-control" placeholder="Search subjects, mentors, or departments..." style="border-radius: 0 8px 8px 0; border-left: none; height: 40px; box-shadow: none;">
            </div>
        </div>
    </div>

    <!-- Main Assignments Table Card -->
    <div class="st-table-container">
        <div class="table-responsive">
            <table id="mentorAssignmentsTable" class="table st-table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Specialization Subject</th>
                        <th>Applicable Department(s)</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                        <th style="text-align: center;">Students Enrolled</th>
                        <th>Assigned Mentor</th>
                        <th style="text-align: center; width: 160px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjectAssignments as $idx => $sub): 
                        $isDummy = !empty($sub['is_dummy_mentor']);
                        $mentorBadgeClass = $isDummy ? 'badge-mentor-dummy' : 'badge-mentor-faculty';
                        $mentorType = $isDummy ? 'dummy' : 'faculty';
                    ?>
                        <tr data-subject-id="<?= $sub['subject_id'] ?>" 
                            data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                            data-ay="<?= htmlspecialchars($sub['academic_year']) ?>"
                            data-semester-ids="<?= htmlspecialchars($sub['semester_ids']) ?>"
                            data-mentor-type="<?= $mentorType ?>"
                            data-mentor-name="<?= htmlspecialchars($sub['current_mentor_name']) ?>"
                            data-mentor-id="<?= intval($sub['current_mentor_id']) ?>">
                            <td style="color: #94a3b8; font-weight: 700;"><?= $idx + 1 ?></td>
                            <td>
                                <span style="font-weight: 700; color: #1e293b; font-size: 15px;"><?= htmlspecialchars($sub['subject_name']) ?></span>
                            </td>
                            <td>
                                <?php 
                                $depts = explode(', ', $sub['departments']);
                                foreach ($depts as $d): ?>
                                    <span class="badge-dept"><?= htmlspecialchars($d) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <span class="badge-sem"><?= htmlspecialchars($sub['semesters']) ?></span>
                            </td>
                            <td style="color: #64748b; font-weight: 500;">
                                <?= htmlspecialchars($sub['academic_year']) ?>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-roster-view btn-view-students" 
                                        data-subject-id="<?= $sub['subject_id'] ?>" 
                                        data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>">
                                    <i class="fa fa-users" style="margin-right: 4px;"></i> <?= intval($sub['student_count']) ?> Students
                                </button>
                            </td>
                            <td class="mentor-col">
                                <span class="<?= $mentorBadgeClass ?>">
                                    <i class="fa <?= $isDummy ? 'fa-tag' : 'fa-check-circle' ?>"></i>
                                    <span class="mentor-name-text"><?= htmlspecialchars($sub['current_mentor_name']) ?></span>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-action-change btn-change-mentor" 
                                        data-subject-id="<?= $sub['subject_id'] ?>"
                                        data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                                        data-current-mentor-id="<?= intval($sub['current_mentor_id']) ?>"
                                        data-current-mentor-name="<?= htmlspecialchars($sub['current_mentor_name']) ?>">
                                    <i class="fa fa-pencil"></i> Change Mentor
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CHANGE MENTOR MODAL -->
<div class="modal fade" id="changeMentorModal" tabindex="-1" role="dialog" aria-labelledby="changeMentorModalLabel">
    <div class="modal-dialog" role="document" style="max-width: 520px; margin-top: 80px;">
        <div class="modal-content" style="border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #423cbc, #302b8e); color: #ffffff; padding: 20px 24px; border-bottom: none;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9; font-size: 26px;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="changeMentorModalLabel" style="font-weight: 700; font-size: 18px;">
                    <i class="fa fa-user-plus" style="margin-right: 8px;"></i> Change Subject Mentor
                </h4>
            </div>
            <form id="changeMentorForm">
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="action" value="change_mentor">
                    <input type="hidden" name="subject_id" id="modal_subject_id">

                    <div id="modalAlert" style="display: none;"></div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="font-weight: 600; color: #475569; font-size: 13px;">Specialization Subject</label>
                        <div id="modal_subject_name_display" style="padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 700; color: #1e293b; font-size: 15px;"></div>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="font-weight: 600; color: #475569; font-size: 13px;">Current Assigned Mentor</label>
                        <div id="modal_current_mentor_display" style="padding: 12px 16px; background: #fffaf0; border: 1px solid #feebc8; border-radius: 8px; color: #b45309; font-weight: 600; font-size: 14px;">
                            <i class="fa fa-user" style="margin-right: 6px;"></i> <span id="modal_current_mentor_text"></span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-weight: 600; color: #475569; font-size: 13px;">Select New Faculty / Mentor <span style="color: #ef4444;">*</span></label>
                        <select name="new_mentor_id" id="modal_new_mentor_select" class="form-control" style="width: 100%; border-radius: 8px; height: 42px;" required>
                            <option value="">-- Choose Faculty or Dummy Mentor --</option>
                            <optgroup label="👨‍🏫 Actual Faculty Members">
                                <?php foreach ($allMentors as $m): if (empty($m['is_dummy'])): ?>
                                    <option value="<?= $m['mentor_id'] ?>">
                                        <?= htmlspecialchars($m['mentor_name']) ?> (<?= htmlspecialchars($m['department_name']) ?> &bull; <?= htmlspecialchars($m['email_id']) ?>)
                                    </option>
                                <?php endif; endforeach; ?>
                            </optgroup>
                            <optgroup label="🔤 Alphabet Dummy Mentors">
                                <?php foreach ($allMentors as $m): if (!empty($m['is_dummy'])): ?>
                                    <option value="<?= $m['mentor_id'] ?>">
                                        <?= htmlspecialchars($m['mentor_name']) ?> (<?= htmlspecialchars($m['email_id']) ?>)
                                    </option>
                                <?php endif; endforeach; ?>
                            </optgroup>
                        </select>
                        <small style="color: #64748b; margin-top: 6px; display: block;">
                            <i class="fa fa-info-circle text-primary"></i> Updating this mentor will automatically update all students taking this subject.
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 24px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Cancel</button>
                    <button type="submit" id="btnSaveMentorChange" class="btn btn-primary" style="background: #423cbc; border-color: #423cbc; border-radius: 8px; font-weight: 600; padding: 8px 22px;">
                        <i class="fa fa-save"></i> Save Mentor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ENROLLED STUDENTS LIST MODAL -->
<div class="modal fade" id="enrolledStudentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="margin-top: 60px;">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #334155); color: #ffffff; padding: 18px 24px;">
                <button type="button" class="close" data-dismiss="modal" style="color: #fff; opacity: 0.85;">&times;</button>
                <h4 class="modal-title" style="font-weight: 700;">
                    <i class="fa fa-graduation-cap text-info"></i> Enrolled Students in <span id="enrolledSubjectTitle" style="color: #38bdf8;"></span>
                </h4>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div id="enrolledStudentsLoading" style="text-align: center; padding: 30px;">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color: #423cbc;"></i>
                    <p style="margin-top: 10px; color: #64748b;">Loading student roster...</p>
                </div>
                <div id="enrolledStudentsContent" style="display: none;" class="table-responsive">
                    <table class="table table-hover table-striped" style="font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; color: #475569;">
                                <th>#</th>
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
            <div class="modal-footer" style="background: #f8fafc; padding: 14px 24px;">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Close</button>
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
                        '<div class="alert alert-success" style="border-radius: 8px; padding: 10px 14px; margin-bottom: 15px;">' +
                        '<i class="fa fa-check-circle"></i> ' + response.message +
                        '</div>'
                    ).fadeIn();

                    // Update row in table immediately
                    var row = $('tr[data-subject-id="' + response.subject_id + '"]');
                    var isDummy = response.new_mentor_name.indexOf('Mentor ') === 0;
                    var badgeClass = isDummy ? 'badge-mentor-dummy' : 'badge-mentor-faculty';
                    var icon = isDummy ? 'fa-tag' : 'fa-check-circle';

                    row.find('.mentor-col').html(
                        '<span class="' + badgeClass + '">' +
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
                        '<div class="alert alert-danger" style="border-radius: 8px; padding: 10px 14px; margin-bottom: 15px;">' +
                        '<i class="fa fa-exclamation-triangle"></i> ' + errorMsg +
                        '</div>'
                    ).fadeIn();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalBtnHtml);
                var err = xhr.status === 403 ? 'Access Denied: You do not have permission to change mentor assignments.' : 'Server error processing request.';
                $('#modalAlert').html(
                    '<div class="alert alert-danger" style="border-radius: 8px; padding: 10px 14px; margin-bottom: 15px;">' +
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
                            '<td style="color: #94a3b8; font-weight: 600;">' + (i + 1) + '</td>' +
                            '<td><strong style="color: #1e293b;">' + $('<div>').text(s.fname).html() + '</strong></td>' +
                            '<td><code style="background: #f1f5f9; color: #475569;">' + $('<div>').text(s.registration_no).html() + '</code></td>' +
                            '<td><span class="badge-dept">' + $('<div>').text(s.department_name).html() + '</span></td>' +
                            '<td>' + $('<div>').text(s.division_name).html() + '</td>' +
                            '<td>' + $('<div>').text(s.roll_no).html() + '</td>' +
                            '<td><span class="badge-sem">' + $('<div>').text(s.semester_name).html() + '</span></td>' +
                            '<td><span style="background: #dbeafe; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-weight: 700;">' + $('<div>').text(s.cgpa || 'N/A').html() + '</span></td>' +
                            '<td><a href="mailto:' + encodeURIComponent(s.email) + '" style="color: #423cbc;">' + $('<div>').text(s.email).html() + '</a></td>' +
                        '</tr>';
                    });
                    $('#enrolledStudentsTableBody').html(rowsHtml);
                } else {
                    $('#enrolledStudentsTableBody').html('<tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 25px;">No students currently enrolled in this specialization subject.</td></tr>');
                }
            },
            error: function() {
                $('#enrolledStudentsLoading').hide();
                $('#enrolledStudentsContent').show();
                $('#enrolledStudentsTableBody').html('<tr><td colspan="9" style="text-align: center; color: #ef4444; padding: 25px;">Error retrieving enrolled student list.</td></tr>');
            }
        });
    });

    // Semester Tabs Filter
    var activeSemFilter = 'all';
    $('.st-tab-btn').on('click', function() {
        $('.st-tab-btn').removeClass('active');
        $(this).addClass('active');
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
