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

<div class="content-wrapper">
  <!-- Institutional ERP Page Header -->
  <div class="erp-page-header">
    <div>
      <h1 class="erp-page-title"><i class="fa fa-book text-primary" style="margin-right: 6px;"></i> Specialization & Curriculum Management</h1>
      <p class="erp-page-subtitle">Course catalog, honors/minor degree alignments, and academic track curriculum</p>
    </div>
    <div class="erp-page-actions">
      <?php if (in_array((int)$usertype, [1, 2], true)): ?>
        <a href="academic_import.php" class="btn btn-erp-primary"><i class="fa fa-refresh"></i> Import / Sync PDF Data</a>
      <?php endif; ?>
      <a href="class_crud_new.php?tab=specialization-list" class="btn btn-erp-secondary"><i class="fa fa-cogs"></i> Specializations Master</a>
      <a href="mentor_assignment.php" class="btn btn-erp-secondary"><i class="fa fa-users"></i> Mentor Allocation</a>
    </div>
  </div>

  <section class="content" style="padding-top: 0;">
    <?php if ($message !== ''): ?>
      <div class="alert alert-<?php echo $messageType; ?> alert-dismissable" style="border-radius: 4px; margin-bottom: 16px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- Add/Edit Subject ERP Card -->
    <div class="erp-card" style="margin-bottom: 20px;">
      <div class="erp-card-header">
        <div>
          <h3 class="erp-card-title"><i class="fa fa-pencil text-primary" style="margin-right: 6px;"></i> <?php echo $editSubject ? 'Edit Course / Subject Record' : 'Add New Specialization Course'; ?></h3>
          <p class="erp-card-subtitle">Define curriculum courses linked to department tracks, semester terms, and eligibility rules</p>
        </div>
      </div>
      <form method="post">
        <?php echo DBController::getCsrfInputField(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="subject_id" value="<?php echo (int) ($editSubject['subject_id'] ?? 0); ?>">
        <div class="erp-card-body" style="padding: 16px;">
          <div class="row">
            <div class="col-md-2">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Course Code</label>
                <input name="subject_code" class="form-control input-sm" maxlength="50" placeholder="e.g. MDL-4011" value="<?php echo htmlspecialchars($editSubject['subject_code'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Course Name <span class="text-danger">*</span></label>
                <input name="subject_name" class="form-control input-sm" required maxlength="255" placeholder="e.g. Essential Mathematics for ML" value="<?php echo htmlspecialchars($editSubject['subject_name'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Specialization Track <span class="text-danger">*</span></label>
                <select name="specialization_id" class="form-control input-sm" required>
                  <option value="">-- Select Specialization --</option>
                  <?php foreach ($specializations as $item): ?>
                    <option value="<?php echo (int) $item['specialization_id']; ?>" <?php echo ((int) ($editSubject['specialization_id'] ?? 0) === (int) $item['specialization_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['specialization_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Department / Program <span class="text-danger">*</span></label>
                <select name="department_id" class="form-control input-sm" required>
                  <option value="">-- Select Department --</option>
                  <?php foreach ($departments as $item): ?>
                    <option value="<?php echo (int) $item['department_id']; ?>" <?php echo ((int) ($editSubject['department_id'] ?? 0) === (int) $item['department_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['department_name'] . ' (' . $item['program_label'] . ')'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-2">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Semester <span class="text-danger">*</span></label>
                <select name="semester_id" class="form-control input-sm" required>
                  <option value="">-- Select Term --</option>
                  <?php foreach ($semesters as $item): ?>
                    <option value="<?php echo (int) $item['semester_id']; ?>" <?php echo ((int) ($editSubject['semester_id'] ?? 0) === (int) $item['semester_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['semester_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Academic Year</label>
                <select name="academic_year_id" class="form-control input-sm">
                  <option value="">-- Select Year --</option>
                  <?php foreach ($academicYears as $ay): ?>
                    <option value="<?php echo (int) $ay['session_id']; ?>" <?php echo ((int) ($editSubject['academic_year_id'] ?? 0) === (int) $ay['session_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($ay['session_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Year of Study</label>
                <input name="year_of_study" class="form-control input-sm" maxlength="50" placeholder="e.g. S.E (IV)" value="<?php echo htmlspecialchars($editSubject['year_of_study'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Offered as Honors To</label>
                <input name="honors_eligibility" class="form-control input-sm" placeholder="Programs eligible for Honors" value="<?php echo htmlspecialchars($editSubject['honors_eligibility'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Offered as Minor To</label>
                <input name="minor_eligibility" class="form-control input-sm" placeholder="Programs eligible for Minor" value="<?php echo htmlspecialchars($editSubject['minor_eligibility'] ?? ''); ?>">
              </div>
            </div>
          </div>

          <div class="form-group" style="margin-top: 5px; margin-bottom: 0;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Description / Notes</label>
            <textarea name="description" class="form-control input-sm" rows="2" maxlength="1000" placeholder="Curriculum summary or source syllabus notes..."><?php echo htmlspecialchars($editSubject['description'] ?? ''); ?></textarea>
          </div>
        </div>
        <div class="erp-card-footer" style="padding: 12px 16px;">
          <button class="btn btn-erp-primary" type="submit">
            <i class="fa fa-save"></i> <?php echo $editSubject ? 'Update Course Record' : 'Save Course'; ?>
          </button>
          <?php if ($editSubject): ?>
            <a class="btn btn-erp-secondary" href="specialization_subject_manage.php" style="margin-left: 6px;">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="erp-card" style="margin-bottom: 20px;">
      <div class="erp-card-body" style="padding: 14px 16px;">
        <form method="get" class="row">
          <div class="col-md-3">
            <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Specialization</label>
            <select name="spec" class="form-control input-sm" onchange="this.form.submit()">
              <option value="">All Specializations</option>
              <?php foreach ($specializations as $item): ?>
                <option value="<?php echo (int) $item['specialization_id']; ?>" <?php echo ($filterSpec === (int)$item['specialization_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($item['specialization_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Department / Program</label>
            <select name="dept" class="form-control input-sm" onchange="this.form.submit()">
              <option value="">All Departments</option>
              <?php foreach ($departments as $item): ?>
                <option value="<?php echo (int) $item['department_id']; ?>" <?php echo ($filterDept === (int)$item['department_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($item['department_name'] . ' - ' . $item['program_label']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Semester</label>
            <select name="sem" class="form-control input-sm" onchange="this.form.submit()">
              <option value="">All Terms</option>
              <?php foreach ($semesters as $item): ?>
                <option value="<?php echo (int) $item['semester_id']; ?>" <?php echo ($filterSem === (int)$item['semester_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($item['semester_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Search Code / Name</label>
            <input type="text" name="q" class="form-control input-sm" placeholder="e.g. MDL-4011 or Machine Learning" value="<?php echo htmlspecialchars($filterSearch); ?>">
          </div>
          <div class="col-md-1" style="padding-top: 19px;">
            <button type="submit" class="btn btn-erp-primary btn-sm btn-block"><i class="fa fa-filter"></i></button>
          </div>
        </form>
      </div>
    </div>

    <!-- Configured Subjects Data Grid -->
    <div class="erp-card">
      <div class="erp-card-header">
        <div>
          <h3 class="erp-card-title"><i class="fa fa-list text-primary" style="margin-right: 6px;"></i> Configured Specialization Courses</h3>
          <p class="erp-card-subtitle">Active and archived subject tracks across all academic departments and curricula</p>
        </div>
        <div class="pull-right">
          <span class="erp-badge erp-badge-secondary" style="font-size: 12px; padding: 4px 10px;">Showing <?php echo count($subjects); ?> courses</span>
        </div>
      </div>
      <div class="erp-card-body table-responsive" style="padding: 0;">
        <table class="erp-table">
          <thead>
            <tr>
              <th style="width: 40px;" class="col-center">#</th>
              <th style="width: 100px;">Code</th>
              <th>Course Name</th>
              <th>Specialization Track</th>
              <th>Program / Department</th>
              <th style="width: 80px;" class="col-center">Semester</th>
              <th style="width: 80px;" class="col-center">A.Y.</th>
              <th style="width: 180px;">Honors / Minor Eligibility</th>
              <th style="width: 80px;" class="col-center">Status</th>
              <th style="width: 110px;" class="col-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($subjects)): ?>
              <tr>
                <td colspan="10" class="col-center text-muted" style="padding: 25px;">No courses found matching the selected filter criteria.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($subjects as $idx => $item): 
                $isActive = (int) $item['is_active'] === 1;
                $statusBadge = $isActive ? 'erp-badge-success' : 'erp-badge-secondary';
                $statusText = $isActive ? 'Active' : 'Inactive';
                $honors = trim($item['honors_eligibility'] ?? '');
                $minor = trim($item['minor_eligibility'] ?? '');
              ?>
                <tr>
                  <td class="col-center text-muted"><?php echo $idx + 1; ?></td>
                  <td>
                    <?php if (!empty($item['subject_code'])): ?>
                      <code style="font-size: 11px; background: #f1f5f9; color: #1e293b; padding: 2px 5px; border-radius: 3px; font-weight: 600;">
                        <?php echo htmlspecialchars($item['subject_code']); ?>
                      </code>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong style="color: #0f172a;"><?php echo htmlspecialchars($item['subject_name']); ?></strong>
                    <?php if (!empty($item['source_notes'])): ?>
                      <br><small class="text-muted"><i class="fa fa-info-circle"></i> <?php echo htmlspecialchars($item['source_notes']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="font-weight: 500; color: #1e3a8a;"><?php echo htmlspecialchars($item['specialization_name'] ?? 'N/A'); ?></span>
                  </td>
                  <td>
                    <span style="font-size: 12px; color: #475569;"><?php echo htmlspecialchars($item['program_name'] ?? $item['department_name'] ?? 'N/A'); ?></span>
                  </td>
                  <td class="col-center">
                    <span class="erp-badge erp-badge-secondary"><?php echo htmlspecialchars($item['semester_name'] ?? 'N/A'); ?></span>
                  </td>
                  <td class="col-center">
                    <span style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($item['session_name'] ?? '—'); ?></span>
                  </td>
                  <td>
                    <?php if ($honors !== '' && $honors !== '—'): ?>
                      <div style="font-size: 10px; line-height: 1.3; margin-bottom: 3px;">
                        <span class="label label-info" style="font-size: 9px;">Honors</span> 
                        <span class="text-muted" title="<?php echo htmlspecialchars($honors); ?>">
                          <?php echo htmlspecialchars(mb_strimwidth($honors, 0, 35, '...')); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                    <?php if ($minor !== '' && $minor !== '—'): ?>
                      <div style="font-size: 10px; line-height: 1.3;">
                        <span class="label label-warning" style="font-size: 9px;">Minor</span> 
                        <span class="text-muted" title="<?php echo htmlspecialchars($minor); ?>">
                          <?php echo htmlspecialchars(mb_strimwidth($minor, 0, 35, '...')); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                    <?php if (($honors === '' || $honors === '—') && ($minor === '' || $minor === '—')): ?>
                      <span class="text-muted" style="font-size: 11px;">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="col-center"><span class="erp-badge <?php echo $statusBadge; ?>"><?php echo $statusText; ?></span></td>
                  <td class="col-center">
                    <a class="btn btn-erp-secondary btn-xs" href="specialization_subject_manage.php?edit=<?php echo (int) $item['subject_id']; ?>">
                      <i class="fa fa-pencil"></i> Edit
                    </a>
                    <form style="display:inline" method="post">
                      <?php echo DBController::getCsrfInputField(); ?>
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="subject_id" value="<?php echo (int) $item['subject_id']; ?>">
                      <button class="btn btn-erp-secondary btn-xs" type="submit" style="margin-left: 3px;" title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                        <i class="fa <?php echo $isActive ? 'fa-ban text-danger' : 'fa-check text-success'; ?>"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include "header/footer.php"; ?>
