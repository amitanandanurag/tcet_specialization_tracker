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
  $editResult = mysqli_query($conn, "SELECT subject_id, department_id, semester_id, specialization_id, subject_name, description FROM st_specialization_subject_master WHERE subject_id = {$editId} LIMIT 1");
  if ($editResult) {
    $editSubject = mysqli_fetch_assoc($editResult);
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
    $subjectName = trim($_POST['subject_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($action === 'save' && $departmentId > 0 && $semesterId > 0 && $specializationId > 0 && $subjectName !== '') {
        if ($subjectId > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE st_specialization_subject_master SET department_id = ?, semester_id = ?, specialization_id = ?, subject_name = ?, description = ? WHERE subject_id = ?");
            mysqli_stmt_bind_param($stmt, 'iiissi', $departmentId, $semesterId, $specializationId, $subjectName, $description, $subjectId);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO st_specialization_subject_master (department_id, semester_id, specialization_id, subject_name, description, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            mysqli_stmt_bind_param($stmt, 'iiiss', $departmentId, $semesterId, $specializationId, $subjectName, $description);
        }
        if ($stmt && mysqli_stmt_execute($stmt)) {
            $message = 'Specialization subject saved successfully.';
        } else {
            $message = 'Unable to save subject. It may already exist for this department, semester, and specialization.';
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

$departments = $db_handle->runQuery("SELECT department_id, department_name FROM st_department_master ORDER BY department_name") ?? array();
$semesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id") ?? array();
$specializations = $db_handle->runQuery("SELECT specialization_id, specialization_name FROM st_specialization_master ORDER BY specialization_name") ?? array();
$subjects = $db_handle->runQuery("SELECT s.subject_id, s.subject_name, s.description, s.is_active, d.department_name, sem.semester_name, sp.specialization_name FROM st_specialization_subject_master s LEFT JOIN st_department_master d ON d.department_id = s.department_id LEFT JOIN st_semester_master sem ON sem.semester_id = s.semester_id LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id ORDER BY d.department_name, sem.semester_id, s.subject_name") ?? array();
?>

<div class="content-wrapper">
  <!-- Institutional ERP Page Header -->
  <div class="erp-page-header">
    <div>
      <h1 class="erp-page-title">Specialization Subjects</h1>
      <p class="erp-page-subtitle">Course catalog, departmental alignment, and academic curriculum mapping</p>
    </div>
    <div class="erp-page-actions">
      <a href="mentor_assignment.php" class="btn btn-erp-primary"><i class="fa fa-users"></i> Mentor Allocation</a>
      <a href="mentor_subject.php" class="btn btn-erp-secondary"><i class="fa fa-link"></i> Mentor Subject Map</a>
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
          <h3 class="erp-card-title"><i class="fa fa-pencil text-primary" style="margin-right: 6px;"></i> <?php echo $editSubject ? 'Edit Specialization Subject' : 'Add New Specialization Subject'; ?></h3>
          <p class="erp-card-subtitle">Define curriculum courses linked to department tracks and academic terms</p>
        </div>
      </div>
      <form method="post">
        <?php echo DBController::getCsrfInputField(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="subject_id" value="<?php echo (int) ($editSubject['subject_id'] ?? 0); ?>">
        <div class="erp-card-body" style="padding: 16px;">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Department <span class="text-danger">*</span></label>
                <select name="department_id" class="form-control input-sm" required>
                  <option value="">-- Select Department --</option>
                  <?php foreach ($departments as $item): ?>
                    <option value="<?php echo (int) $item['department_id']; ?>" <?php echo ((int) ($editSubject['department_id'] ?? 0) === (int) $item['department_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['department_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Semester <span class="text-danger">*</span></label>
                <select name="semester_id" class="form-control input-sm" required>
                  <option value="">-- Select Semester --</option>
                  <?php foreach ($semesters as $item): ?>
                    <option value="<?php echo (int) $item['semester_id']; ?>" <?php echo ((int) ($editSubject['semester_id'] ?? 0) === (int) $item['semester_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($item['semester_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
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
            <div class="col-md-4">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Subject Name <span class="text-danger">*</span></label>
                <input name="subject_name" class="form-control input-sm" required maxlength="255" placeholder="e.g. Advanced Web Development" value="<?php echo htmlspecialchars($editSubject['subject_name'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="form-group" style="margin-top: 5px; margin-bottom: 0;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Description (Optional)</label>
            <textarea name="description" class="form-control input-sm" rows="2" maxlength="1000" placeholder="Optional curriculum summary or course prerequisites..."><?php echo htmlspecialchars($editSubject['description'] ?? ''); ?></textarea>
          </div>
        </div>
        <div class="erp-card-footer" style="padding: 12px 16px;">
          <button class="btn btn-erp-primary" type="submit">
            <i class="fa fa-save"></i> <?php echo $editSubject ? 'Update Subject' : 'Save Subject'; ?>
          </button>
          <?php if ($editSubject): ?>
            <a class="btn btn-erp-secondary" href="specialization_subject_manage.php" style="margin-left: 6px;">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Configured Subjects Data Grid -->
    <div class="erp-card">
      <div class="erp-card-header">
        <div>
          <h3 class="erp-card-title"><i class="fa fa-list text-primary" style="margin-right: 6px;"></i> Configured Specialization Courses</h3>
          <p class="erp-card-subtitle">Active and archived subject tracks across all academic departments</p>
        </div>
        <div class="pull-right">
          <span class="erp-badge erp-badge-secondary" style="font-size: 12px; padding: 4px 10px;">Total: <?php echo count($subjects); ?> courses</span>
        </div>
      </div>
      <div class="erp-card-body table-responsive" style="padding: 0;">
        <table class="erp-table">
          <thead>
            <tr>
              <th style="width: 45px;" class="col-center">#</th>
              <th>Subject Name</th>
              <th>Department</th>
              <th style="width: 110px;">Semester</th>
              <th>Specialization Track</th>
              <th style="width: 90px;" class="col-center">Status</th>
              <th style="width: 130px;" class="col-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subjects as $idx => $item): 
              $isActive = (int) $item['is_active'] === 1;
              $statusBadge = $isActive ? 'erp-badge-success' : 'erp-badge-secondary';
              $statusText = $isActive ? 'Active' : 'Inactive';
            ?>
              <tr>
                <td class="col-center text-muted"><?php echo $idx + 1; ?></td>
                <td><strong style="color: #0f172a;"><?php echo htmlspecialchars($item['subject_name']); ?></strong></td>
                <td><span class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($item['department_name'] ?? 'N/A'); ?></span></td>
                <td><span class="erp-badge erp-badge-secondary"><?php echo htmlspecialchars($item['semester_name'] ?? 'N/A'); ?></span></td>
                <td><span style="font-weight: 500;"><?php echo htmlspecialchars($item['specialization_name'] ?? 'N/A'); ?></span></td>
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
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include "header/footer.php"; ?>
