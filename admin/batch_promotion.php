<?php
require "header/header.php";

$db_handle = new DBController();

// Authorization: Only Super Admin (1), Admin (2), and Coordinator (3)
if (!in_array($usertype, [1, 2, 3])) {
    echo "<div class='content-wrapper'><div class='alert alert-danger' style='margin: 20px;'>Access Denied.</div></div>";
    require "header/footer.php";
    exit;
}

$userDeptId = 0;
$uRes = mysqli_query($db_handle->conn, "SELECT department_id FROM st_user_master WHERE user_id = $userid LIMIT 1");
if ($uRes && $uRow = mysqli_fetch_assoc($uRes)) {
    $userDeptId = intval($uRow['department_id'] ?? 0);
}

$successMessage = '';
$errorMessage = '';

// Handle Batch Promotion Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_promotion'])) {
    if (!DBController::validateCsrfToken()) {
        $errorMessage = "Security validation failed (CSRF token invalid). Please refresh and try again.";
    } else {
        $targetSemesterId = intval($_POST['target_semester_id'] ?? 0);
        $targetAcademicYearId = intval($_POST['target_academic_year_id'] ?? 0);
        $selectedStudents = $_POST['selected_students'] ?? [];
        $newRollNumbers = $_POST['new_roll_no'] ?? [];

        if ($targetSemesterId <= 0 || empty($selectedStudents)) {
            $errorMessage = "Please select at least one student and specify the target semester.";
        } else {
            $promotedCount = 0;
            $failedCount = 0;

            mysqli_begin_transaction($db_handle->conn);
            try {
                foreach ($selectedStudents as $sId) {
                    $sId = intval($sId);
                    if ($sId <= 0) continue;

                    $newRoll = isset($newRollNumbers[$sId]) ? trim((string)$newRollNumbers[$sId]) : null;
                    
                    $promoted = $db_handle->promoteStudentSemester($sId, $targetSemesterId, $newRoll, null, $targetAcademicYearId > 0 ? $targetAcademicYearId : null);
                    if ($promoted) {
                        $promotedCount++;
                    } else {
                        $failedCount++;
                    }
                }

                mysqli_commit($db_handle->conn);
                $successMessage = "Successfully promoted {$promotedCount} student(s) to Semester {$targetSemesterId}!";
                if ($failedCount > 0) {
                    $errorMessage = "Notice: {$failedCount} student(s) could not be promoted.";
                }
            } catch (Exception $e) {
                mysqli_rollback($db_handle->conn);
                $errorMessage = "Transaction error during batch promotion: " . $e->getMessage();
            }
        }
    }
}

$filterDept = intval($_GET['department_id'] ?? ($usertype === 3 ? $userDeptId : 0));
$filterClass = intval($_GET['class_id'] ?? 0);
$filterDiv = intval($_GET['division_id'] ?? 0);
$filterSem = intval($_GET['current_sem_id'] ?? 5);

// Department isolation for Coordinators
if ($usertype === 3 && $userDeptId > 0) {
    $filterDept = $userDeptId;
}

// Fetch Students matching filter criteria
$students = [];
if ($filterDept > 0 || $usertype === 1 || $usertype === 2) {
    $where = ["s.status = 1"];
    if ($filterDept > 0) $where[] = "s.department_id = $filterDept";
    if ($filterClass > 0) $where[] = "s.class_id = $filterClass";
    if ($filterDiv > 0) $where[] = "s.division_id = $filterDiv";
    if ($filterSem > 0) $where[] = "s.current_semester_id = $filterSem";

    $whereSql = implode(' AND ', $where);
    $q = "SELECT s.student_id, s.roll_no, s.registration_no, s.fname, s.cgpa,
                 d.department_name, c.class_name, divm.division_name, sem.semester_name,
                 sp.specialization_name, sps.subject_name
          FROM st_student_master s
          LEFT JOIN st_department_master d ON s.department_id = d.department_id
          LEFT JOIN st_class_master c ON s.class_id = c.class_id
          LEFT JOIN st_division_master divm ON s.division_id = divm.division_id
          LEFT JOIN st_semester_master sem ON s.current_semester_id = sem.semester_id
          LEFT JOIN st_specialization_master sp ON s.specialization_id = sp.specialization_id
          LEFT JOIN st_specialization_subject_master sps ON s.specialization_subject_id = sps.subject_id
          WHERE $whereSql
          ORDER BY s.roll_no ASC, s.fname ASC";
          
    $res = mysqli_query($db_handle->conn, $q);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $students[] = $row;
        }
    }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="erp-page-header">
      <div class="erp-page-title-wrap">
        <h1 class="erp-page-title"><i class="fa fa-graduation-cap"></i> Batch Semester Promotion</h1>
        <div class="erp-breadcrumb">
          <a href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
          <span class="sep">&rsaquo;</span>
          <a href="student_monitor.php">Student Monitor</a>
          <span class="sep">&rsaquo;</span>
          <span class="active-item">Cohort Promotion</span>
        </div>
      </div>
    </div>
  </section>

  <section class="content" style="padding-top: 0;">
    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success alert-dismissible" style="border-radius: 4px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
      <div class="alert alert-danger alert-dismissible" style="border-radius: 4px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="erp-filter-card">
      <form method="GET" action="batch_promotion.php" style="margin: 0;">
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
          <?php if ($usertype !== 3): ?>
          <div style="flex: 1; min-width: 160px;">
            <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">Department</label>
            <select name="department_id" class="form-control" style="height: 34px; font-size: 12px;">
              <option value="0">All Departments</option>
              <?php
              $deptQ = mysqli_query($db_handle->conn, "SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC");
              while ($d = mysqli_fetch_assoc($deptQ)) {
                $sel = ($filterDept == $d['department_id']) ? 'selected' : '';
                echo "<option value='{$d['department_id']}' $sel>" . htmlspecialchars($d['department_name']) . "</option>";
              }
              ?>
            </select>
          </div>
          <?php endif; ?>

          <div style="flex: 1; min-width: 140px;">
            <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">Current Semester</label>
            <select name="current_sem_id" class="form-control" style="height: 34px; font-size: 12px;">
              <?php
              $semQ = mysqli_query($db_handle->conn, "SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC");
              while ($sm = mysqli_fetch_assoc($semQ)) {
                $sel = ($filterSem == $sm['semester_id']) ? 'selected' : '';
                echo "<option value='{$sm['semester_id']}' $sel>" . htmlspecialchars($sm['semester_name']) . "</option>";
              }
              ?>
            </select>
          </div>

          <div style="flex: 1; min-width: 140px;">
            <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">Division</label>
            <select name="division_id" class="form-control" style="height: 34px; font-size: 12px;">
              <option value="0">All Divisions</option>
              <?php
              $divQ = mysqli_query($db_handle->conn, "SELECT division_id, division_name FROM st_division_master ORDER BY division_name ASC");
              while ($dv = mysqli_fetch_assoc($divQ)) {
                $sel = ($filterDiv == $dv['division_id']) ? 'selected' : '';
                echo "<option value='{$dv['division_id']}' $sel>" . htmlspecialchars($dv['division_name']) . "</option>";
              }
              ?>
            </select>
          </div>

          <div>
            <button type="submit" class="btn-erp-primary" style="height: 34px;">
              <i class="fa fa-filter"></i> Load Cohort
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Student Selection & Promotion Form -->
    <form method="POST" action="batch_promotion.php" onsubmit="return confirmPromotion();">
      <?php echo DBController::getCsrfInputField(); ?>
      
      <div class="erp-card">
        <div class="erp-card-header">
          <div class="erp-card-title-group">
            <h3 class="erp-card-title">Eligible Students for Promotion</h3>
            <span class="erp-count-badge"><?php echo count($students); ?> Found</span>
          </div>

          <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
              <label style="font-size: 12px; font-weight: 600; margin: 0; color: #334155;">Target Semester:</label>
              <select name="target_semester_id" class="form-control" required style="width: 150px; height: 32px; font-size: 12px; display: inline-block;">
                <option value="">Select Target Sem</option>
                <?php
                $targetSemOptions = mysqli_query($db_handle->conn, "SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC");
                while ($tso = mysqli_fetch_assoc($targetSemOptions)) {
                  $isDefault = ($filterSem > 0 && $tso['semester_id'] == ($filterSem + 1)) ? 'selected' : '';
                  echo "<option value='{$tso['semester_id']}' $isDefault>" . htmlspecialchars($tso['semester_name']) . "</option>";
                }
                ?>
              </select>
            </div>

            <button type="submit" name="execute_promotion" class="btn-erp-primary" style="height: 32px;" <?php echo empty($students) ? 'disabled' : ''; ?>>
              <i class="fa fa-arrow-circle-right"></i> Advance Selected Cohort
            </button>
          </div>
        </div>

        <div style="overflow-x: auto;">
          <table class="erp-table">
            <thead>
              <tr>
                <th style="width: 40px; text-align: center;">
                  <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this);">
                </th>
                <th>Student</th>
                <th>Roll No</th>
                <th>Department</th>
                <th>Class / Div</th>
                <th>Current Track & Subject</th>
                <th>CGPA</th>
                <th>New Roll No (Optional)</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($students)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; padding: 30px; color: #94a3b8;">
                    <i class="fa fa-users" style="font-size: 28px; margin-bottom: 8px; display: block;"></i>
                    No active students found matching the selected criteria.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $s): ?>
                  <tr>
                    <td style="text-align: center;">
                      <input type="checkbox" name="selected_students[]" value="<?php echo intval($s['student_id']); ?>" class="student-checkbox">
                    </td>
                    <td>
                      <div class="student-cell">
                        <span class="student-cell-name"><?php echo htmlspecialchars($s['fname']); ?></span>
                        <span class="student-cell-reg"><?php echo htmlspecialchars($s['registration_no'] ?? 'N/A'); ?></span>
                      </div>
                    </td>
                    <td class="text-mono" style="font-weight: 600; color: #1e293b;">
                      <?php echo htmlspecialchars($s['roll_no'] ?? '-'); ?>
                    </td>
                    <td><?php echo htmlspecialchars($s['department_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars(($s['class_name'] ?? '') . ' / ' . ($s['division_name'] ?? '')); ?></td>
                    <td>
                      <div style="font-size: 12px; font-weight: 600; color: var(--erp-primary);">
                        <?php echo htmlspecialchars($s['specialization_name'] ?? 'None'); ?>
                      </div>
                      <div style="font-size: 11px; color: #64748b;">
                        <?php echo htmlspecialchars($s['subject_name'] ?? 'None'); ?>
                      </div>
                    </td>
                    <td class="col-num" style="font-weight: 600;">
                      <?php echo !empty($s['cgpa']) ? number_format((float)$s['cgpa'], 2) : '-'; ?>
                    </td>
                    <td style="width: 150px;">
                      <input type="text" name="new_roll_no[<?php echo intval($s['student_id']); ?>]" class="form-control" placeholder="Optional" style="height: 30px; font-size: 12px; font-family: monospace;">
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </form>
  </section>
</div>

<script>
function toggleSelectAll(master) {
  var checkboxes = document.querySelectorAll('.student-checkbox');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].checked = master.checked;
  }
}

function confirmPromotion() {
  var checked = document.querySelectorAll('.student-checkbox:checked');
  if (checked.length === 0) {
    alert('Please select at least one student to promote.');
    return false;
  }
  var targetSemSelect = document.querySelector('select[name="target_semester_id"]');
  var targetSemText = targetSemSelect.options[targetSemSelect.selectedIndex].text;
  return confirm('Are you sure you want to promote ' + checked.length + ' student(s) to ' + targetSemText + '?\n\nTheir current semester records will be safely archived in the semester history ledger.');
}
</script>

<?php require "header/footer.php"; ?>
