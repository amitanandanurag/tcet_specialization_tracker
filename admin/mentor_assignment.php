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

// Backend Authorization: Super Admin (1), Admin (2), Coordinator/HOD (3), Mentor (4)
if (!in_array($loginRole, [1, 2, 3, 4], true)) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json', true, 403);
        echo json_encode(['success' => false, 'message' => 'Access Denied: You do not have permission to view mentor assignments.']);
        exit();
    }
    echo "<script>alert('Access Denied'); window.location.href='index.php';</script>";
    exit();
}

// Handle AJAX Mentor Assignment Update (Privileged: Super Admin 1, Admin 2, HOD 3)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_mentor') {
    header('Content-Type: application/json');

    if ($loginRole === 4) {
        echo json_encode(['success' => false, 'message' => 'Mentors are not authorized to reassign mentor allocations.']);
        exit();
    }

    if (!DBController::validateCsrfToken()) {
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
        exit();
    }

    $subjectId = intval($_POST['subject_id'] ?? 0);
    $newMentorId = intval($_POST['new_mentor_id'] ?? 0);
    $semesterId = intval($_POST['semester_id'] ?? 0);
    $academicYearId = intval($_POST['academic_year_id'] ?? 1);

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

    $updated = $db_handle->assignSubjectMentor($subjectId, $newMentorId, $loginUserId, $semesterId, $academicYearId);

    if ($updated) {
        $semClause = $semesterId > 0 ? "AND s.current_semester_id = $semesterId" : "";
        $countRes = mysqli_query($db_handle->conn, "
            SELECT COUNT(DISTINCT s.student_id) AS total
            FROM st_student_master s
            LEFT JOIN st_student_semester_history h ON h.student_id = s.student_id AND h.semester_id = s.current_semester_id
            WHERE COALESCE(NULLIF(h.specialization_subject_id, 0), s.specialization_subject_id) = $subjectId
            $semClause
        ");
        $affectedCount = 0;
        if ($countRes && ($cRow = mysqli_fetch_assoc($countRes))) {
            $affectedCount = intval($cRow['total']);
        }

        $scopeText = $semesterId > 0 ? "for Semester {$semesterId}" : "across all semesters";
        echo json_encode([
            'success' => true,
            'message' => "Mentor for '{$subjectName}' ({$scopeText}) updated to '{$newMentorName}'. {$affectedCount} enrolled students now dynamically resolve this mentor.",
            'subject_id' => $subjectId,
            'new_mentor_id' => $newMentorId,
            'new_mentor_name' => $newMentorName,
            'semester_id' => $semesterId,
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

    $extraStudentCond = "";
    if ($loginRole === 3) {
        $extraStudentCond = " AND s.department_id = " . intval($loginDepartment);
    } elseif ($loginRole === 4) {
        $extraStudentCond = " AND (
            EXISTS (SELECT 1 FROM st_mentor_student_mapping msm2 WHERE msm2.student_id = s.student_id AND msm2.mentor_id = $loginUserId)
            OR EXISTS (SELECT 1 FROM st_mentor_subject_mapping msub WHERE msub.mentor_id = $loginUserId AND msub.subject_id = $subjectId)
        )";
    }

    $studentsSql = "
        SELECT s.student_id, s.registration_no, s.roll_no, s.fname, s.email, s.mobile, s.cgpa,
               COALESCE(d.department_name, 'N/A') AS department_name,
               COALESCE(sec.sections, 'A') AS division_name,
               COALESCE(sem.semester_name, CONCAT('Semester ', s.current_semester_id)) AS semester_name
        FROM st_student_master s
        LEFT JOIN st_department_master d ON d.department_id = s.department_id
        LEFT JOIN st_section_master sec ON sec.id = s.division_id
        LEFT JOIN st_semester_master sem ON sem.semester_id = s.current_semester_id
        LEFT JOIN st_student_semester_history h ON h.student_id = s.student_id AND h.semester_id = s.current_semester_id
        WHERE COALESCE(NULLIF(h.specialization_subject_id, 0), s.specialization_subject_id) = $subjectId
        $extraStudentCond
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
$departments = $db_handle->runQuery("SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC") ?? [];
$semesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC") ?? [];

// Role-specific scope condition on subjects list
$subjectScopeCond = "";
if ($loginRole === 4) {
    $subjectScopeCond = " AND msm.mentor_id = " . intval($loginUserId);
} elseif ($loginRole === 3) {
    $subjectScopeCond = " AND (ssm.department_id = " . intval($loginDepartment) . " OR sm_links.department_id = " . intval($loginDepartment) . ") ";
}

// Fetch all subjects with resolved mentor and student count
$subjectsSql = "
    SELECT ssm.subject_id,
           ssm.subject_name,
           ssm.is_active,
           COALESCE(ay.session_name, '2026-27') AS academic_year,
           COALESCE(GROUP_CONCAT(DISTINCT d.department_name ORDER BY d.department_name SEPARATOR ', '), 'All Departments') AS departments,
           COALESCE(GROUP_CONCAT(DISTINCT sem.semester_name ORDER BY sem.semester_id SEPARATOR ', '), 'Multiple') AS semesters,
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
    WHERE ssm.subject_id > 0 $subjectScopeCond
    GROUP BY ssm.subject_id, ssm.subject_name
    ORDER BY ssm.subject_name ASC
";
$subjectAssignments = $db_handle->runQuery($subjectsSql) ?? [];

// Calculate summary statistics
$totalSubjects = count($subjectAssignments);
$totalEnrolled = 0;
$dummyMentorCount = 0;
$facultyMentorCount = 0;
$unassignedCount = 0;

foreach ($subjectAssignments as $sub) {
    $totalEnrolled += intval($sub['student_count']);
    if (empty($sub['current_mentor_id']) || $sub['current_mentor_name'] === 'Not Assigned') {
        $unassignedCount++;
    } elseif (!empty($sub['is_dummy_mentor'])) {
        $dummyMentorCount++;
    } else {
        $facultyMentorCount++;
    }
}

require "header/header.php";
?>

<style>
    /* Compact Professional Mentor Assignments Styling */
    .erp-assignment-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }
    .erp-assignment-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 86px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .erp-assignment-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
    }
    .erp-card-metric-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .erp-card-metric-val {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .erp-card-metric-sub {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }
    .assignment-filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 14px 16px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    .assignment-filter-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1.2fr 1.2fr auto;
        gap: 12px;
        align-items: flex-end;
    }
    .filter-field label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 4px;
        letter-spacing: 0.3px;
    }
    .filter-field input, .filter-field select {
        height: 36px;
        border-radius: 4px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        width: 100%;
        padding: 6px 10px;
    }
    .filter-field input:focus, .filter-field select:focus {
        border-color: var(--erp-primary, #423cbc);
        outline: none;
        box-shadow: 0 0 0 3px rgba(66, 60, 188, 0.12);
    }
    .filter-search-wrap {
        position: relative;
    }
    .filter-search-wrap i {
        position: absolute;
        left: 10px;
        top: 11px;
        color: #94a3b8;
        font-size: 13px;
    }
    .filter-search-wrap input {
        padding-left: 32px;
    }
    .table-container-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 6px 6px;
    }
    .table-container-responsive table {
        width: 100%;
        min-width: 900px;
        margin-bottom: 0;
    }
    @media (max-width: 991px) {
        .assignment-filter-grid {
            grid-template-columns: 1fr 1fr;
        }
        .filter-field-search {
            grid-column: span 2;
        }
    }
    @media (max-width: 576px) {
        .assignment-filter-grid {
            grid-template-columns: 1fr;
        }
        .filter-field-search {
            grid-column: span 1;
        }
    }
</style>

<div class="content-wrapper">
    <!-- Institutional ERP Page Header -->
    <div class="erp-page-header">
        <div>
            <h1 class="erp-page-title">Mentor Assignments</h1>
            <p class="erp-page-subtitle">Manage subject-wise faculty mentor allocation and dynamic student coverage</p>
        </div>
        <div class="erp-page-actions">
            <a href="mentor_subject.php" class="btn btn-erp-secondary"><i class="fa fa-link"></i> Mentor Subject Mapping</a>
            <a href="specialization_subject_manage.php" class="btn btn-erp-secondary"><i class="fa fa-book"></i> Subject Directory</a>
        </div>
    </div>

    <!-- Main Content Area -->
    <section class="content" style="padding-top: 0;">
        <!-- Compact Institutional Summary Metric Cards -->
        <div class="erp-assignment-summary">
            <div class="erp-assignment-card" style="border-left: 3px solid var(--erp-primary, #423cbc);">
                <div class="erp-card-metric-title">
                    <span>Total Subjects</span>
                    <i class="fa fa-book text-primary"></i>
                </div>
                <div class="erp-card-metric-val"><?= $totalSubjects ?></div>
                <div class="erp-card-metric-sub">Active Courses</div>
            </div>
            <div class="erp-assignment-card" style="border-left: 3px solid #0284c7;">
                <div class="erp-card-metric-title">
                    <span>Enrolled Students</span>
                    <i class="fa fa-users text-info"></i>
                </div>
                <div class="erp-card-metric-val"><?= $totalEnrolled ?></div>
                <div class="erp-card-metric-sub">Across All Semesters</div>
            </div>
            <div class="erp-assignment-card" style="border-left: 3px solid #16a34a;">
                <div class="erp-card-metric-title">
                    <span>Faculty Mentors</span>
                    <i class="fa fa-check-circle text-success"></i>
                </div>
                <div class="erp-card-metric-val" style="color: #16a34a;"><?= $facultyMentorCount ?></div>
                <div class="erp-card-metric-sub">Allocated</div>
            </div>
            <div class="erp-assignment-card" style="border-left: 3px solid #7c3aed;">
                <div class="erp-card-metric-title">
                    <span>Placeholder Mentors</span>
                    <i class="fa fa-user-secret" style="color: #7c3aed;"></i>
                </div>
                <div class="erp-card-metric-val" style="color: #7c3aed;"><?= $dummyMentorCount ?></div>
                <div class="erp-card-metric-sub">Temporary</div>
            </div>
            <div class="erp-assignment-card" style="border-left: 3px solid <?= ($unassignedCount > 0 ? '#dc2626' : '#94a3b8') ?>;">
                <div class="erp-card-metric-title">
                    <span style="<?= ($unassignedCount > 0 ? 'color: #dc2626;' : '') ?>">Unassigned</span>
                    <i class="fa fa-exclamation-triangle" style="<?= ($unassignedCount > 0 ? 'color: #dc2626;' : 'color: #94a3b8;') ?>"></i>
                </div>
                <div class="erp-card-metric-val" style="<?= ($unassignedCount > 0 ? 'color: #dc2626;' : 'color: #64748b;') ?>"><?= $unassignedCount ?></div>
                <div class="erp-card-metric-sub"><?= ($unassignedCount > 0 ? 'Needs Attention' : 'All Covered') ?></div>
            </div>
        </div>

        <!-- ERP Filter & Search Toolbar (Compact Grid) -->
        <div class="assignment-filter-card">
            <div class="assignment-filter-grid">
                <div class="filter-field filter-field-search">
                    <label>Search Directory</label>
                    <div class="filter-search-wrap">
                        <i class="fa fa-search"></i>
                        <input type="text" id="subjectLiveSearch" placeholder="Search subject name, mentor, or department...">
                    </div>
                </div>

                <div class="filter-field">
                    <label>Semester</label>
                    <select id="semFilterSelect" class="form-control">
                        <option value="all">All Semesters</option>
                        <option value="3">Semester III</option>
                        <option value="4">Semester IV</option>
                        <option value="5">Semester V</option>
                        <option value="6">Semester VI</option>
                        <option value="7">Semester VII</option>
                        <option value="8">Semester VIII</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Department</label>
                    <select id="deptFilterSelect" class="form-control">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= strtolower(htmlspecialchars($d['department_name'])) ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Mentor Status</label>
                    <select id="mentorStatusFilter" class="form-control">
                        <option value="all">All Mentors</option>
                        <option value="faculty">Faculty Mentors</option>
                        <option value="placeholder">Placeholder Mentors</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>

                <div class="filter-field" style="text-align: right;">
                    <button type="button" class="btn btn-default" id="btnResetFilters" title="Reset all filters" style="height: 36px; padding: 0 16px; font-weight: 600;">
                        <i class="fa fa-undo"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Master Assignments Table Card -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div>
                    <h3 class="erp-card-title"><i class="fa fa-user-circle-o text-primary" style="margin-right: 6px;"></i> Subject Mentor Allocation Matrix</h3>
                    <p class="erp-card-subtitle">Dynamic student linkage propagates mentor assignment across the entire ERP</p>
                </div>
                <div class="pull-right">
                    <span id="matchingCountText" class="erp-badge erp-badge-secondary" style="font-size: 12px; padding: 5px 10px;">Showing <?= $totalSubjects ?> subjects</span>
                </div>
            </div>
            <div class="erp-card-body table-container-responsive" style="padding: 0;">
                <table id="mentorAssignmentsTable" class="erp-table table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;">#</th>
                            <th style="min-width: 240px;">Specialization Subject</th>
                            <th style="min-width: 160px;">Department(s)</th>
                            <th style="width: 110px; text-align: center;">Semester</th>
                            <th style="width: 90px; text-align: center;">Session</th>
                            <th style="width: 110px; text-align: center;">Enrolled</th>
                            <th style="min-width: 180px;">Assigned Mentor</th>
                            <th style="width: 90px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectAssignments as $idx => $sub): 
                            $isDummy = !empty($sub['is_dummy_mentor']);
                            $isUnassigned = empty($sub['current_mentor_id']) || $sub['current_mentor_name'] === 'Not Assigned';
                            
                            $mentorStatusType = 'faculty';
                            if ($isUnassigned) {
                                $mentorStatusType = 'unassigned';
                            } elseif ($isDummy) {
                                $mentorStatusType = 'placeholder';
                            }
                        ?>
                            <tr data-subject-id="<?= $sub['subject_id'] ?>" 
                                data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                                data-departments="<?= strtolower(htmlspecialchars($sub['departments'])) ?>"
                                data-semester-ids="<?= htmlspecialchars($sub['semester_ids']) ?>"
                                data-mentor-status="<?= $mentorStatusType ?>"
                                data-mentor-name="<?= htmlspecialchars($sub['current_mentor_name']) ?>"
                                data-mentor-id="<?= intval($sub['current_mentor_id']) ?>">
                                <td class="col-center text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($sub['subject_name']) ?></div>
                                </td>
                                <td>
                                    <span class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($sub['departments']) ?></span>
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-secondary"><?= htmlspecialchars($sub['semesters']) ?></span>
                                </td>
                                <td>
                                    <span class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($sub['academic_year']) ?></span>
                                </td>
                                <td class="col-center">
                                    <button type="button" class="btn btn-erp-secondary btn-xs btn-view-students" 
                                            data-subject-id="<?= $sub['subject_id'] ?>" 
                                            data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                                            title="View student roster">
                                        <i class="fa fa-users text-primary"></i> <strong><?= intval($sub['student_count']) ?></strong>
                                    </button>
                                </td>
                                <td class="mentor-col">
                                    <?php if ($isUnassigned): ?>
                                        <span class="erp-badge erp-badge-danger">
                                            <i class="fa fa-exclamation-circle"></i> Unassigned
                                        </span>
                                    <?php elseif ($isDummy): ?>
                                        <span class="erp-badge erp-badge-purple">
                                            <i class="fa fa-user-o"></i> <?= htmlspecialchars($sub['current_mentor_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="erp-badge erp-badge-success">
                                            <i class="fa fa-user"></i> <?= htmlspecialchars($sub['current_mentor_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-center">
                                    <?php if ($loginRole !== 4): ?>
                                    <button type="button" class="btn btn-erp-primary btn-xs btn-change-mentor" 
                                            data-subject-id="<?= $sub['subject_id'] ?>" 
                                            data-subject-name="<?= htmlspecialchars($sub['subject_name']) ?>"
                                            data-current-mentor-id="<?= intval($sub['current_mentor_id']) ?>"
                                            data-current-mentor-name="<?= htmlspecialchars($sub['current_mentor_name']) ?>">
                                        <i class="fa fa-pencil"></i> Assign
                                    </button>
                                    <?php else: ?>
                                    <span class="text-muted" style="font-size: 11px;"><i class="fa fa-check text-success"></i> My Assignment</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="erp-card-footer text-muted" style="font-size: 12px; padding: 12px 18px;">
                <i class="fa fa-info-circle text-primary"></i> Subject mentor reassignments dynamically propagate across all enrolled student records, reports, and academic progression ledgers.
            </div>
        </div>
    </section>
</div>

<!-- CHANGE MENTOR DIALOG MODAL -->
<div class="modal fade" id="changeMentorModal" tabindex="-1" role="dialog" aria-labelledby="changeMentorModalLabel">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
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
                        <div id="modal_subject_name_display" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; border-radius: 3px; font-weight: 600; color: #1e293b;"></div>
                    </div>

                    <div class="form-group">
                        <label>Current Assigned Mentor</label>
                        <div id="modal_current_mentor_display" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; border-radius: 3px; color: #334155;">
                            <i class="fa fa-user text-muted"></i> <span id="modal_current_mentor_text" style="font-weight: 500;"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Target Academic Period</label>
                        <select name="semester_id" id="modal_semester_scope" class="form-control">
                            <option value="0">All Semesters (Global Subject Default)</option>
                            <option value="3">Semester III (SEM III)</option>
                            <option value="4">Semester IV (SEM IV)</option>
                            <option value="5">Semester V (SEM V)</option>
                            <option value="6">Semester VI (SEM VI)</option>
                            <option value="7">Semester VII (SEM VII)</option>
                            <option value="8">Semester VIII (SEM VIII)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select New Mentor <span class="text-danger">*</span></label>
                        <select name="new_mentor_id" id="modal_new_mentor_select" class="form-control" required>
                            <option value="">-- Select Faculty or Placeholder Mentor --</option>
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
                                        <?= htmlspecialchars($m['mentor_name']) ?> (Placeholder)
                                    </option>
                                <?php endif; endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveMentorChange" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ENROLLED STUDENTS ROSTER MODAL -->
<div class="modal fade" id="enrolledStudentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-graduation-cap"></i> Enrolled Students &mdash; <span id="enrolledSubjectTitle" style="font-weight: 500;"></span>
                </h4>
            </div>
            <div class="modal-body">
                <div id="enrolledStudentsLoading" class="text-center" style="padding: 25px;">
                    <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="text-muted" style="margin-top: 8px;">Loading roster data...</p>
                </div>
                <div id="enrolledStudentsContent" style="display: none;" class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="col-center">#</th>
                                <th>Student Name</th>
                                <th>ERP ID / Reg No</th>
                                <th>Department</th>
                                <th>Division</th>
                                <th>Roll No</th>
                                <th>Semester</th>
                                <th class="col-num">CGPA</th>
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
        $('#modal_semester_scope').val('0');
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
                    var mentorHtml = '';
                    
                    if (isDummy) {
                        mentorHtml = '<span class="erp-badge erp-badge-purple"><i class="fa fa-user-o"></i> ' + $('<div>').text(response.new_mentor_name).html() + '</span>';
                    } else {
                        mentorHtml = '<span class="erp-badge erp-badge-success"><i class="fa fa-user"></i> ' + $('<div>').text(response.new_mentor_name).html() + '</span>';
                    }

                    row.find('.mentor-col').html(mentorHtml);

                    row.find('.btn-change-mentor')
                       .data('current-mentor-id', response.new_mentor_id)
                       .data('current-mentor-name', response.new_mentor_name);

                    row.data('mentor-name', response.new_mentor_name);
                    row.data('mentor-id', response.new_mentor_id);
                    row.data('mentor-status', isDummy ? 'placeholder' : 'faculty');

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
                            '<td class="col-center text-muted">' + (i + 1) + '</td>' +
                            '<td><div class="student-cell-name">' + $('<div>').text(s.fname).html() + '</div><div class="student-cell-reg">' + $('<div>').text(s.registration_no).html() + '</div></td>' +
                            '<td>' + $('<div>').text(s.department_name).html() + '</td>' +
                            '<td>' + $('<div>').text(s.division_name).html() + '</td>' +
                            '<td><span class="text-mono">' + $('<div>').text(s.roll_no).html() + '</span></td>' +
                            '<td>' + $('<div>').text(s.semester_name).html() + '</td>' +
                            '<td class="col-num font-weight-bold">' + (s.cgpa || 'N/A') + '</td>' +
                            '<td><a href="mailto:' + encodeURIComponent(s.email) + '">' + $('<div>').text(s.email).html() + '</a></td>' +
                        '</tr>';
                    });
                    $('#enrolledStudentsTableBody').html(rowsHtml);
                } else {
                    $('#enrolledStudentsTableBody').html('<tr><td colspan="8" class="text-center text-muted" style="padding: 20px;">No students currently enrolled in this specialization subject.</td></tr>');
                }
            },
            error: function() {
                $('#enrolledStudentsLoading').hide();
                $('#enrolledStudentsContent').show();
                $('#enrolledStudentsTableBody').html('<tr><td colspan="8" class="text-center text-danger" style="padding: 20px;">Error retrieving enrolled student list.</td></tr>');
            }
        });
    });

    // Multi-Facet Filtering Engine
    $('#semFilterSelect, #deptFilterSelect, #mentorStatusFilter').on('change', function() {
        filterTable();
    });

    $('#subjectLiveSearch').on('keyup', function() {
        filterTable();
    });

    $('#btnResetFilters').on('click', function() {
        $('#subjectLiveSearch').val('');
        $('#semFilterSelect').val('all');
        $('#deptFilterSelect').val('all');
        $('#mentorStatusFilter').val('all');
        filterTable();
    });

    function filterTable() {
        var query = ($('#subjectLiveSearch').val() || '').toLowerCase().trim();
        var selectedSem = $('#semFilterSelect').val();
        var selectedDept = $('#deptFilterSelect').val();
        var selectedStatus = $('#mentorStatusFilter').val();

        var visibleCount = 0;

        $('#mentorAssignmentsTable tbody tr').each(function() {
            var row = $(this);
            var subjectName = (row.data('subject-name') || '').toLowerCase();
            var mentorName = (row.data('mentor-name') || '').toLowerCase();
            var departments = (row.data('departments') || '').toLowerCase();
            var semIds = (row.data('semester-ids') || '').toString().split(',');
            var mentorStatus = (row.data('mentor-status') || '').toLowerCase();

            var matchesSearch = !query || subjectName.indexOf(query) !== -1 || mentorName.indexOf(query) !== -1 || departments.indexOf(query) !== -1;
            var matchesSem = selectedSem === 'all' || semIds.indexOf(selectedSem.toString()) !== -1;
            var matchesDept = selectedDept === 'all' || departments.indexOf(selectedDept) !== -1;
            var matchesStatus = selectedStatus === 'all' || mentorStatus === selectedStatus;

            if (matchesSearch && matchesSem && matchesDept && matchesStatus) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });

        $('#matchingCountText').text('Showing ' + visibleCount + ' subjects');
    }
});
</script>

<?php require "header/footer.php"; ?>
