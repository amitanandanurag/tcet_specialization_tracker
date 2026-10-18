<?php
require "header/header.php";

if (!in_array((int) ($usertype ?? 0), array(1, 2, 3), true)) {
    header("location: index.php");
    exit;
}

$message = '';
$messageType = 'success';
$conn = $db_handle->conn;
$editSubject = null;

if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editStmt = mysqli_prepare($conn, "SELECT subject_id, department_id, semester_id, specialization_id, subject_code, subject_name, academic_year_id, year_of_study, description, honors_eligibility, minor_eligibility, source_page, source_notes FROM st_specialization_subject_master WHERE subject_id = ? LIMIT 1");
    if ($editStmt) {
        mysqli_stmt_bind_param($editStmt, 'i', $editId);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        if ($editResult) {
            $editSubject = mysqli_fetch_assoc($editResult);
        }
        mysqli_stmt_close($editStmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!DBController::validateCsrfToken()) {
        $message = 'Security validation failed (invalid token). Please try again.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        $subjectId = intval($_POST['subject_id'] ?? 0);
        $departmentId = intval($_POST['department_id'] ?? 0);
        $semesterId = intval($_POST['semester_id'] ?? 0);
        $specializationId = intval($_POST['specialization_id'] ?? 0);
        $academicYearId = intval($_POST['academic_year_id'] ?? 0);
        $subjectCode = trim($_POST['subject_code'] ?? '');
        $subjectName = trim($_POST['subject_name'] ?? '');
        $yearOfStudy = trim($_POST['year_of_study'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $honorsElig = trim($_POST['honors_eligibility'] ?? '');
        $minorElig = trim($_POST['minor_eligibility'] ?? '');

        if ($action === 'save' && $departmentId > 0 && $semesterId > 0 && $specializationId > 0 && $subjectName !== '') {
            $ayIdVal = ($academicYearId > 0) ? $academicYearId : null;

            if ($subjectId > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE st_specialization_subject_master 
                    SET department_id = ?, semester_id = ?, specialization_id = ?, subject_code = ?, subject_name = ?, academic_year_id = ?, year_of_study = ?, description = ?, honors_eligibility = ?, minor_eligibility = ? 
                    WHERE subject_id = ?");
                mysqli_stmt_bind_param($stmt, 'iiississssi', $departmentId, $semesterId, $specializationId, $subjectCode, $subjectName, $ayIdVal, $yearOfStudy, $description, $honorsElig, $minorElig, $subjectId);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO st_specialization_subject_master 
                    (department_id, semester_id, specialization_id, subject_code, subject_name, academic_year_id, year_of_study, description, honors_eligibility, minor_eligibility, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                mysqli_stmt_bind_param($stmt, 'iiississss', $departmentId, $semesterId, $specializationId, $subjectCode, $subjectName, $ayIdVal, $yearOfStudy, $description, $honorsElig, $minorElig);
            }
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $message = 'Specialization subject saved successfully.';
                if ($subjectId === 0) {
                    $editSubject = null;
                }
            } else {
                $message = 'Unable to save subject. It may already exist or a database constraint was violated.';
                $messageType = 'danger';
            }
            if ($stmt) mysqli_stmt_close($stmt);
        } elseif ($action === 'toggle' && $subjectId > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE st_specialization_subject_master SET is_active = IF(is_active = 1, 0, 1) WHERE subject_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $subjectId);
            $message = ($stmt && mysqli_stmt_execute($stmt)) ? 'Subject status updated.' : 'Unable to update subject status.';
            $messageType = $message === 'Subject status updated.' ? 'success' : 'danger';
            if ($stmt) mysqli_stmt_close($stmt);
        } else {
            $message = 'Department, semester, specialization, and subject name are required.';
            $messageType = 'danger';
        }
    }
}

// Master lists for dropdowns
$departments = $db_handle->runQuery("SELECT department_id, department_name, COALESCE(program_name, department_name) as program_label FROM st_department_master ORDER BY department_name") ?? array();
$semesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id") ?? array();
$specializations = $db_handle->runQuery("SELECT specialization_id, specialization_name FROM st_specialization_master ORDER BY specialization_name") ?? array();
$academicYears = $db_handle->runQuery("SELECT session_id, session_name FROM st_session_master ORDER BY session_id DESC") ?? array();

// Search & Filter parameters
$filterDept = intval($_GET['dept'] ?? 0);
$filterSpec = intval($_GET['spec'] ?? 0);
$filterSem = intval($_GET['sem'] ?? 0);
$filterSearch = trim($_GET['q'] ?? '');

$whereClauses = [];
$params = [];
$paramTypes = '';

if ($filterDept > 0) {
    $whereClauses[] = "s.department_id = ?";
    $params[] = $filterDept;
    $paramTypes .= 'i';
}
if ($filterSpec > 0) {
    $whereClauses[] = "s.specialization_id = ?";
    $params[] = $filterSpec;
    $paramTypes .= 'i';
}
if ($filterSem > 0) {
    $whereClauses[] = "s.semester_id = ?";
    $params[] = $filterSem;
    $paramTypes .= 'i';
}
if ($filterSearch !== '') {
    $whereClauses[] = "(s.subject_code LIKE ? OR s.subject_name LIKE ? OR s.program_name LIKE ?)";
    $likeSearch = "%" . $filterSearch . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $paramTypes .= 'sss';
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$baseQuery = "SELECT s.subject_id, s.subject_code, s.subject_name, s.description, s.is_active, s.year_of_study,
                     s.honors_eligibility, s.minor_eligibility, s.source_page, s.source_notes,
                     d.department_name, COALESCE(s.program_name, d.program_name, d.department_name) AS program_name,
                     sem.semester_name, sp.specialization_name, sess.session_name
              FROM st_specialization_subject_master s 
              LEFT JOIN st_department_master d ON d.department_id = s.department_id 
              LEFT JOIN st_semester_master sem ON sem.semester_id = s.semester_id 
              LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id 
              LEFT JOIN st_session_master sess ON sess.session_id = s.academic_year_id
              $whereSql
              ORDER BY d.department_name, sem.semester_id, s.subject_code, s.subject_name";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $baseQuery);
    mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $subjects = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $subjects[] = $r;
        }
    }
    mysqli_stmt_close($stmt);
} else {
    $subjects = $db_handle->runQuery($baseQuery) ?? array();
}
?>

<style>
  /* Layout & Section Containers */
  .curriculum-content-area {
    padding: 14px 18px 24px;
    background-color: var(--erp-bg, #f8fafc);
  }

  /* Academic Page Header */
  .curriculum-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding-bottom: 14px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
  }
  .curriculum-header-left h1 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.01em;
  }
  .curriculum-header-left p {
    margin: 3px 0 0;
    font-size: 12px;
    color: #64748b;
  }
  .curriculum-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .btn-erp-compact {
    height: 32px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
    transition: all 0.15s ease;
    cursor: pointer;
    line-height: 30px;
  }
  .btn-erp-primary-action {
    background-color: var(--erp-primary, #423cbc);
    color: #ffffff !important;
    border: 1px solid var(--erp-primary-hover, #352fa1);
  }
  .btn-erp-primary-action:hover {
    background-color: var(--erp-primary-hover, #352fa1);
    color: #ffffff !important;
  }
  .btn-erp-neutral-action {
    background-color: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
  }
  .btn-erp-neutral-action:hover {
    background-color: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
  }

  /* Academic Card Panel */
  .curriculum-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    margin-bottom: 16px;
    overflow: hidden;
  }
  .curriculum-card-header {
    padding: 12px 18px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }
  .curriculum-card-title {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .curriculum-card-subtitle {
    margin: 2px 0 0;
    font-size: 11px;
    color: #64748b;
  }
  .curriculum-card-body {
    padding: 16px 18px;
  }
  .curriculum-card-footer {
    padding: 10px 18px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* Form Elements Grid */
  .curriculum-form-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 12px 14px;
  }
  .curriculum-form-group {
    margin-bottom: 0;
  }
  .col-span-2 { grid-column: span 2; }
  .col-span-3 { grid-column: span 3; }
  .col-span-4 { grid-column: span 4; }
  .col-span-5 { grid-column: span 5; }
  .col-span-6 { grid-column: span 6; }
  .col-span-12 { grid-column: span 12; }

  @media (max-width: 992px) {
    .col-span-2, .col-span-3, .col-span-4, .col-span-5 {
      grid-column: span 6;
    }
    .col-span-6, .col-span-12 {
      grid-column: span 12;
    }
  }
  @media (max-width: 576px) {
    .col-span-2, .col-span-3, .col-span-4, .col-span-5, .col-span-6, .col-span-12 {
      grid-column: span 12;
    }
  }

  .curriculum-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 4px;
  }
  .curriculum-label .req-star {
    color: #dc2626;
    margin-left: 2px;
  }
  .curriculum-input, .curriculum-select {
    width: 100%;
    height: 34px;
    padding: 5px 10px;
    font-size: 13px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    outline: none;
  }
  .curriculum-input:focus, .curriculum-select:focus, .curriculum-textarea:focus {
    border-color: var(--erp-primary, #423cbc);
    box-shadow: 0 0 0 2px rgba(66, 60, 188, 0.12);
  }
  .curriculum-textarea {
    width: 100%;
    padding: 7px 10px;
    font-size: 13px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    outline: none;
    resize: vertical;
    min-height: 48px;
  }

  /* Filter Toolbar */
  .curriculum-filter-row {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
  }
  .filter-col-spec {
    flex: 1 1 220px;
  }
  .filter-col-dept {
    flex: 1 1 240px;
  }
  .filter-col-sem {
    flex: 0 1 130px;
  }
  .filter-col-search {
    flex: 1 1 200px;
  }
  .filter-col-action {
    display: flex;
    gap: 6px;
    align-items: center;
  }
  .btn-filter-submit {
    height: 34px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    background: var(--erp-primary, #423cbc);
    color: #ffffff;
    border: 1px solid var(--erp-primary-hover, #352fa1);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .btn-filter-submit:hover {
    background: var(--erp-primary-hover, #352fa1);
    color: #ffffff;
  }
  .btn-filter-reset {
    height: 34px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    text-decoration: none !important;
    transition: all 0.15s ease;
  }
  .btn-filter-reset:hover {
    background: #f1f5f9;
    color: #0f172a;
  }

  /* Curriculum Table */
  .curriculum-table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .curriculum-table {
    width: 100%;
    min-width: 980px;
    border-collapse: collapse;
    font-size: 13px;
  }
  .curriculum-table th {
    background-color: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 9px 12px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
  }
  .curriculum-table td {
    padding: 9px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #1e293b;
  }
  .curriculum-table tbody tr:hover {
    background-color: #f8fafc;
  }
  .curriculum-table .col-center {
    text-align: center;
  }
  .course-code-badge {
    font-family: var(--erp-font-mono, monospace);
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #334155;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
  }
  .course-name-title {
    font-weight: 600;
    color: #0f172a;
    display: block;
    line-height: 1.35;
  }
  .course-notes-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
    display: block;
  }
  .spec-track-label {
    font-weight: 500;
    color: #312e81;
  }
  .dept-program-label {
    font-size: 12px;
    color: #475569;
    line-height: 1.3;
  }
  .semester-pill {
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 2px 8px;
    border-radius: 12px;
    display: inline-block;
    white-space: nowrap;
  }
  .status-pill {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }
  .status-pill.status-active {
    background-color: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }
  .status-pill.status-inactive {
    background-color: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
  }
  .status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
  }
  .status-pill.status-active .status-dot {
    background-color: #16a34a;
  }
  .status-pill.status-inactive .status-dot {
    background-color: #94a3b8;
  }
  .eligibility-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    line-height: 1.25;
    margin-bottom: 2px;
  }
  .eligibility-tag .tag-badge {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 1px 4px;
    border-radius: 3px;
    letter-spacing: 0.02em;
  }
  .eligibility-tag.honors .tag-badge {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
  }
  .eligibility-tag.minor .tag-badge {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
  }
  .btn-table-action {
    height: 26px;
    padding: 0 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 3px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    text-decoration: none !important;
    transition: all 0.15s ease;
    cursor: pointer;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
  }
  .btn-table-action:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
  }
  .btn-table-action.btn-toggle-deactivate:hover {
    color: #dc2626;
    border-color: #fca5a5;
    background: #fef2f2;
  }
  .btn-table-action.btn-toggle-activate:hover {
    color: #16a34a;
    border-color: #86efac;
    background: #f0fdf4;
  }
</style>

<div class="content-wrapper">
  <div class="curriculum-content-area">
    <!-- Academic Page Header -->
    <div class="curriculum-header">
      <div class="curriculum-header-left">
        <h1>
          <i class="fa fa-book text-primary" style="color: var(--erp-primary, #423cbc); font-size: 16px;"></i> Specialization &amp; Curriculum Management
        </h1>
        <p>Course catalog, honors/minor degree alignments, and academic track curriculum</p>
      </div>
      <div class="curriculum-header-actions">
        <?php if (in_array((int)$usertype, [1, 2], true)): ?>
          <a href="academic_import.php" class="btn-erp-compact btn-erp-primary-action"><i class="fa fa-refresh"></i> Import / Sync PDF Data</a>
        <?php endif; ?>
        <a href="class_crud_new.php?tab=specialization-list" class="btn-erp-compact btn-erp-neutral-action"><i class="fa fa-cogs"></i> Specializations Master</a>
        <a href="mentor_assignment.php" class="btn-erp-compact btn-erp-neutral-action"><i class="fa fa-users"></i> Mentor Allocation</a>
      </div>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-<?php echo $messageType; ?> alert-dismissable" style="border-radius: 4px; margin-bottom: 16px; padding: 10px 14px; font-size: 13px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- Add/Edit Subject Form Card -->
    <div class="curriculum-card">
      <div class="curriculum-card-header">
        <div>
          <h3 class="curriculum-card-title">
            <i class="fa fa-pencil text-primary" style="color: var(--erp-primary, #423cbc); font-size: 12px;"></i>
            <?php echo $editSubject ? 'Edit Course / Subject Record' : 'Add New Specialization Course'; ?>
          </h3>
          <p class="curriculum-card-subtitle">Define curriculum courses linked to department tracks, semester terms, and eligibility rules</p>
        </div>
      </div>
      <form method="post">
        <?php echo DBController::getCsrfInputField(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="subject_id" value="<?php echo (int) ($editSubject['subject_id'] ?? 0); ?>">
        <div class="curriculum-card-body">
          <div class="curriculum-form-grid">
            <!-- Row 1: Course Identity & Department Scope -->
            <div class="col-span-3">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Course Code</label>
                <input name="subject_code" class="curriculum-input" maxlength="50" placeholder="e.g. MDL-4011" value="<?php echo htmlspecialchars($editSubject['subject_code'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-span-5">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Course Name <span class="req-star">*</span></label>
                <input name="subject_name" class="curriculum-input" required maxlength="255" placeholder="e.g. Essential Mathematics for ML" value="<?php echo htmlspecialchars($editSubject['subject_name'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-span-4">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Department / Program <span class="req-star">*</span></label>
                <select name="department_id" class="curriculum-select" required>
                  <option value="">-- Select Department --</option>
                  <?php foreach ($departments as $item): ?>
                    <option value="<?php echo (int) $item['department_id']; ?>" <?php echo ((int) ($editSubject['department_id'] ?? 0) === (int) $item['department_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['department_name'] . ' (' . $item['program_label'] . ')'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Row 2: Academic Track & Term Classification -->
            <div class="col-span-4">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Specialization Track <span class="req-star">*</span></label>
                <select name="specialization_id" class="curriculum-select" required>
                  <option value="">-- Select Specialization --</option>
                  <?php foreach ($specializations as $item): ?>
                    <option value="<?php echo (int) $item['specialization_id']; ?>" <?php echo ((int) ($editSubject['specialization_id'] ?? 0) === (int) $item['specialization_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['specialization_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-span-3">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Semester <span class="req-star">*</span></label>
                <select name="semester_id" class="curriculum-select" required>
                  <option value="">-- Select Term --</option>
                  <?php foreach ($semesters as $item): ?>
                    <option value="<?php echo (int) $item['semester_id']; ?>" <?php echo ((int) ($editSubject['semester_id'] ?? 0) === (int) $item['semester_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['semester_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-span-3">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Academic Year</label>
                <select name="academic_year_id" class="curriculum-select">
                  <option value="">-- Select Year --</option>
                  <?php foreach ($academicYears as $ay): ?>
                    <option value="<?php echo (int) $ay['session_id']; ?>" <?php echo ((int) ($editSubject['academic_year_id'] ?? 0) === (int) $ay['session_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($ay['session_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-span-2">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Year of Study</label>
                <input name="year_of_study" class="curriculum-input" maxlength="50" placeholder="e.g. S.E (IV)" value="<?php echo htmlspecialchars($editSubject['year_of_study'] ?? ''); ?>">
              </div>
            </div>

            <!-- Row 3: Degree Eligibility Rules -->
            <div class="col-span-6">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Offered as Honors To</label>
                <input name="honors_eligibility" class="curriculum-input" placeholder="Programs eligible for Honors" value="<?php echo htmlspecialchars($editSubject['honors_eligibility'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-span-6">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Offered as Minor To</label>
                <input name="minor_eligibility" class="curriculum-input" placeholder="Programs eligible for Minor" value="<?php echo htmlspecialchars($editSubject['minor_eligibility'] ?? ''); ?>">
              </div>
            </div>

            <!-- Row 4: Description / Notes -->
            <div class="col-span-12">
              <div class="curriculum-form-group">
                <label class="curriculum-label">Description / Notes</label>
                <textarea name="description" class="curriculum-textarea" rows="2" maxlength="1000" placeholder="Curriculum summary or source syllabus notes..."><?php echo htmlspecialchars($editSubject['description'] ?? ''); ?></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="curriculum-card-footer">
          <button class="btn-erp-compact btn-erp-primary-action" type="submit">
            <i class="fa fa-save"></i> <?php echo $editSubject ? 'Update Course Record' : 'Save Course'; ?>
          </button>
          <?php if ($editSubject): ?>
            <a class="btn-erp-compact btn-erp-neutral-action" href="specialization_subject_manage.php">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="curriculum-card">
      <div class="curriculum-card-body" style="padding: 12px 18px;">
        <form method="get" class="curriculum-filter-row">
          <div class="filter-col-spec">
            <label class="curriculum-label">Specialization</label>
            <select name="spec" class="curriculum-select" onchange="this.form.submit()">
              <option value="">All Specializations</option>
              <?php foreach ($specializations as $item): ?>
                <option value="<?php echo (int) $item['specialization_id']; ?>" <?php echo ($filterSpec === (int)$item['specialization_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($item['specialization_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-col-dept">
            <label class="curriculum-label">Department / Program</label>
            <select name="dept" class="curriculum-select" onchange="this.form.submit()">
              <option value="">All Departments</option>
              <?php foreach ($departments as $item): ?>
                <option value="<?php echo (int) $item['department_id']; ?>" <?php echo ($filterDept === (int)$item['department_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($item['department_name'] . ' - ' . $item['program_label']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-col-sem">
            <label class="curriculum-label">Semester</label>
            <select name="sem" class="curriculum-select" onchange="this.form.submit()">
              <option value="">All Terms</option>
              <?php foreach ($semesters as $item): ?>
                <option value="<?php echo (int) $item['semester_id']; ?>" <?php echo ($filterSem === (int)$item['semester_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($item['semester_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-col-search">
            <label class="curriculum-label">Search Code / Name</label>
            <input type="text" name="q" class="curriculum-input" placeholder="e.g. MDL-4011 or Machine Learning" value="<?php echo htmlspecialchars($filterSearch); ?>">
          </div>
          <div class="filter-col-action">
            <button type="submit" class="btn-filter-submit" title="Apply filters">
              <i class="fa fa-filter"></i> Filter
            </button>
            <?php if ($filterDept > 0 || $filterSpec > 0 || $filterSem > 0 || $filterSearch !== ''): ?>
              <a href="specialization_subject_manage.php" class="btn-filter-reset" title="Reset all filters">
                <i class="fa fa-times"></i> Reset
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Configured Specialization Courses Grid -->
    <div class="curriculum-card">
      <div class="curriculum-card-header">
        <div>
          <h3 class="curriculum-card-title">
            <i class="fa fa-list text-primary" style="color: var(--erp-primary, #423cbc); font-size: 13px;"></i> Configured Specialization Courses
          </h3>
          <p class="curriculum-card-subtitle">Active and archived subject tracks across all academic departments and curricula</p>
        </div>
        <div>
          <span style="font-size: 11px; font-weight: 600; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 4px;">
            Showing <?php echo count($subjects); ?> courses
          </span>
        </div>
      </div>
      <div class="curriculum-table-responsive">
        <table class="curriculum-table">
          <thead>
            <tr>
              <th style="width: 45px;" class="col-center">#</th>
              <th style="width: 105px;">Code</th>
              <th>Course Name</th>
              <th style="width: 170px;">Specialization Track</th>
              <th style="width: 180px;">Program / Department</th>
              <th style="width: 85px;" class="col-center">Semester</th>
              <th style="width: 80px;" class="col-center">A.Y.</th>
              <th style="width: 190px;">Honors / Minor Eligibility</th>
              <th style="width: 85px;" class="col-center">Status</th>
              <th style="width: 110px;" class="col-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($subjects)): ?>
              <tr>
                <td colspan="10" class="col-center text-muted" style="padding: 32px 16px;">
                  <i class="fa fa-info-circle" style="margin-right: 4px;"></i> No courses found matching the selected filter criteria.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($subjects as $idx => $item):
                $isActive = (int) $item['is_active'] === 1;
                $statusClass = $isActive ? 'status-active' : 'status-inactive';
                $statusText = $isActive ? 'Active' : 'Inactive';
                $honors = trim($item['honors_eligibility'] ?? '');
                $minor = trim($item['minor_eligibility'] ?? '');
              ?>
                <tr>
                  <td class="col-center text-muted" style="font-size: 12px;"><?php echo $idx + 1; ?></td>
                  <td>
                    <?php if (!empty($item['subject_code'])): ?>
                      <span class="course-code-badge"><?php echo htmlspecialchars($item['subject_code']); ?></span>
                    <?php else: ?>
                      <span class="text-muted" style="font-size: 12px;">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="course-name-title"><?php echo htmlspecialchars($item['subject_name']); ?></span>
                    <?php if (!empty($item['source_notes'])): ?>
                      <span class="course-notes-sub"><i class="fa fa-info-circle" style="font-size: 10px;"></i> <?php echo htmlspecialchars($item['source_notes']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="spec-track-label"><?php echo htmlspecialchars($item['specialization_name'] ?? 'N/A'); ?></span>
                  </td>
                  <td>
                    <span class="dept-program-label"><?php echo htmlspecialchars($item['program_name'] ?? $item['department_name'] ?? 'N/A'); ?></span>
                  </td>
                  <td class="col-center">
                    <span class="semester-pill"><?php echo htmlspecialchars($item['semester_name'] ?? 'N/A'); ?></span>
                  </td>
                  <td class="col-center">
                    <span style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($item['session_name'] ?? '—'); ?></span>
                  </td>
                  <td>
                    <?php if ($honors !== '' && $honors !== '—'): ?>
                      <div class="eligibility-tag honors">
                        <span class="tag-badge">Honors</span>
                        <span class="text-muted" title="<?php echo htmlspecialchars($honors); ?>" style="font-size: 11px;">
                          <?php echo htmlspecialchars(mb_strimwidth($honors, 0, 30, '...')); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                    <?php if ($minor !== '' && $minor !== '—'): ?>
                      <div class="eligibility-tag minor">
                        <span class="tag-badge">Minor</span>
                        <span class="text-muted" title="<?php echo htmlspecialchars($minor); ?>" style="font-size: 11px;">
                          <?php echo htmlspecialchars(mb_strimwidth($minor, 0, 30, '...')); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                    <?php if (($honors === '' || $honors === '—') && ($minor === '' || $minor === '—')): ?>
                      <span class="text-muted" style="font-size: 12px;">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="col-center">
                    <span class="status-pill <?php echo $statusClass; ?>">
                      <span class="status-dot"></span> <?php echo $statusText; ?>
                    </span>
                  </td>
                  <td class="col-center">
                    <div style="display: inline-flex; align-items: center; gap: 4px;">
                      <a class="btn-table-action" href="specialization_subject_manage.php?edit=<?php echo (int) $item['subject_id']; ?>" title="Edit course record">
                        <i class="fa fa-pencil" style="font-size: 10px;"></i> Edit
                      </a>
                      <form style="display:inline; margin:0;" method="post">
                        <?php echo DBController::getCsrfInputField(); ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="subject_id" value="<?php echo (int) $item['subject_id']; ?>">
                        <button class="btn-table-action <?php echo $isActive ? 'btn-toggle-deactivate' : 'btn-toggle-activate'; ?>" type="submit" title="<?php echo $isActive ? 'Deactivate course' : 'Activate course'; ?>">
                          <i class="fa <?php echo $isActive ? 'fa-ban text-danger' : 'fa-check text-success'; ?>" style="font-size: 11px;"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include "header/footer.php"; ?>
