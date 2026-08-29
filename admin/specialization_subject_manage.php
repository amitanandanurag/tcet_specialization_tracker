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
            $message = 'Subject saved successfully.';
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

$departments = $db_handle->runQuery("SELECT department_id, department_name FROM st_department_master ORDER BY department_name") ?? array();
$semesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id") ?? array();
$specializations = $db_handle->runQuery("SELECT specialization_id, specialization_name FROM st_specialization_master ORDER BY specialization_name") ?? array();
$subjects = $db_handle->runQuery("SELECT s.subject_id, s.subject_name, s.description, s.is_active, d.department_name, sem.semester_name, sp.specialization_name FROM st_specialization_subject_master s LEFT JOIN st_department_master d ON d.department_id = s.department_id LEFT JOIN st_semester_master sem ON sem.semester_id = s.semester_id LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id ORDER BY d.department_name, sem.semester_id, s.subject_name") ?? array();
?>
<div class="content-wrapper">
  <section class="content-header"><h1><i class="fa fa-book"></i> Specialization Subjects</h1></section>
  <section class="content">
    <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Add Subject</h3></div>
      <form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="subject_id" value="<?php echo (int) ($editSubject['subject_id'] ?? 0); ?>"><div class="box-body">
        <div class="row">
          <div class="col-md-3"><label>Department</label><select name="department_id" class="form-control" required><option value="">Select Department</option><?php foreach ($departments as $item): ?><option value="<?php echo (int) $item['department_id']; ?>" <?php echo ((int) ($editSubject['department_id'] ?? 0) === (int) $item['department_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($item['department_name']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-2"><label>Semester</label><select name="semester_id" class="form-control" required><option value="">Select Semester</option><?php foreach ($semesters as $item): ?><option value="<?php echo (int) $item['semester_id']; ?>" <?php echo ((int) ($editSubject['semester_id'] ?? 0) === (int) $item['semester_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($item['semester_name']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-3"><label>Specialization</label><select name="specialization_id" class="form-control" required><option value="">Select Specialization</option><?php foreach ($specializations as $item): ?><option value="<?php echo (int) $item['specialization_id']; ?>" <?php echo ((int) ($editSubject['specialization_id'] ?? 0) === (int) $item['specialization_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($item['specialization_name']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label>Subject</label><input name="subject_name" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars($editSubject['subject_name'] ?? ''); ?>"></div>
        </div>
        <div class="form-group" style="margin-top:15px"><label>Description</label><textarea name="description" class="form-control" maxlength="1000"><?php echo htmlspecialchars($editSubject['description'] ?? ''); ?></textarea></div>
      </div><div class="box-footer"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> <?php echo $editSubject ? 'Update Subject' : 'Save Subject'; ?></button><?php if ($editSubject): ?> <a class="btn btn-default" href="specialization_subject_manage.php">Cancel</a><?php endif; ?></div></form>
    </div>
    <div class="box box-default"><div class="box-header with-border"><h3 class="box-title">Configured Subjects</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Department</th><th>Semester</th><th>Specialization</th><th>Subject</th><th>Status</th><th>Action</th></tr></thead><tbody>
      <?php foreach ($subjects as $item): ?><tr><td><?php echo htmlspecialchars($item['department_name'] ?? ''); ?></td><td><?php echo htmlspecialchars($item['semester_name'] ?? ''); ?></td><td><?php echo htmlspecialchars($item['specialization_name'] ?? ''); ?></td><td><?php echo htmlspecialchars($item['subject_name']); ?></td><td><?php echo ((int) $item['is_active'] === 1) ? 'Active' : 'Inactive'; ?></td><td><a class="btn btn-xs btn-primary" href="specialization_subject_manage.php?edit=<?php echo (int) $item['subject_id']; ?>">Edit</a> <form style="display:inline" method="post"><input type="hidden" name="action" value="toggle"><input type="hidden" name="subject_id" value="<?php echo (int) $item['subject_id']; ?>"><button class="btn btn-xs <?php echo ((int) $item['is_active'] === 1) ? 'btn-warning' : 'btn-success'; ?>" type="submit"><?php echo ((int) $item['is_active'] === 1) ? 'Deactivate' : 'Activate'; ?></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </section>
</div>
<?php include "header/footer.php"; ?>
