<?php include "header/header.php"; ?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="erp-page-header">
      <div class="erp-page-title-wrap">
        <h1 class="erp-page-title"><i class="fa fa-user-plus"></i> Register <?php echo htmlspecialchars($roleLabel); ?></h1>
        <div class="erp-breadcrumb">
          <a href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
          <span class="sep">&rsaquo;</span>
          <a href="<?php echo htmlspecialchars($infoFile); ?>"><?php echo htmlspecialchars($roleLabel); ?> Directory</a>
          <span class="sep">&rsaquo;</span>
          <span class="active-item">Registration</span>
        </div>
      </div>
    </div>
  </section>

  <section class="content" style="padding-top: 0;">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="erp-card">
          <div class="erp-card-header">
            <h3 class="erp-card-title">New <?php echo htmlspecialchars($roleLabel); ?> Account Details</h3>
          </div>
          <form method="post" action="<?php echo htmlspecialchars($processFile); ?>" autocomplete="off">
            <?php echo DBController::getCsrfInputField(); ?>
            <div style="padding: 20px;">
              <input type="hidden" name="role_id" value="<?php echo intval($roleId); ?>">

              <div class="form-group">
                <label>Full Name <span style="color:red;">*</span></label>
                <input type="text" name="user_name" class="form-control" placeholder="Enter full name" required>
              </div>

              <div class="form-group">
                <label>Institute Email Address <span style="color:red;">*</span></label>
                <input type="email" name="email_id" class="form-control" placeholder="name@tcetmumbai.in" required>
              </div>

              <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" placeholder="10 digit phone number" maxlength="15">
              </div>

              <div class="form-group">
                <label>Department <span style="color:red;">*</span></label>
                <select name="department_id" id="department_select" class="form-control" required>
                  <option value="">Select Department</option>
                  <?php
                  $deptSql = "SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC";
                  $deptResult = $db_handle->query($deptSql);
                  while ($dept = $deptResult->fetch_assoc()) {
                  ?>
                    <option value="<?php echo intval($dept['department_id']); ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <?php if (intval($roleId) === 4) { ?>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Academic Year <span style="color:red;">*</span></label>
                      <select name="academic_year_id" id="academic_year_select" class="form-control" required>
                        <option value="">Select Academic Year</option>
                        <?php
                        $aySql = "SELECT session_id, session_name FROM st_session_master ORDER BY session_name DESC";
                        $ayResult = $db_handle->query($aySql);
                        while ($ay = $ayResult->fetch_assoc()) {
                          $cleanAy = preg_replace('/\s+/', '', strtolower($ay['session_name']));
                          $isDefaultAy = (strpos($cleanAy, '2026-2027') !== false || intval($ay['session_id']) === 2);
                        ?>
                          <option value="<?php echo intval($ay['session_id']); ?>" <?php echo $isDefaultAy ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ay['session_name']); ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Class <span style="color:red;">*</span></label>
                      <select name="class_id" id="class_select" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php
                        $classSql = "SELECT class_id, class_name FROM st_class_master WHERE class_name NOT IN ('FY', 'FE') ORDER BY class_id ASC";
                        $classResult = $db_handle->query($classSql);
                        while ($cls = $classResult->fetch_assoc()) {
                        ?>
                          <option value="<?php echo intval($cls['class_id']); ?>" data-class-name="<?php echo htmlspecialchars(strtoupper($cls['class_name'])); ?>">
                            <?php echo htmlspecialchars($cls['class_name']); ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Semester <span style="color:red;">*</span></label>
                      <select name="semester_id" id="semester_select" class="form-control" required>
                        <option value="">Select Semester</option>
                        <?php
                        $semSql = "SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC";
                        $semResult = $db_handle->query($semSql);
                        while ($sem = $semResult->fetch_assoc()) {
                        ?>
                          <option value="<?php echo intval($sem['semester_id']); ?>">
                            <?php echo htmlspecialchars($sem['semester_name']); ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Specialization Subject <span style="color:red;">*</span></label>
                  <select name="subject_id" id="subject_select" class="form-control" required disabled>
                    <option value="">Select Department, Class, and Semester first</option>
                    <?php
                    $subSql = "SELECT subject_id, subject_name, department_id, semester_id FROM st_specialization_subject_master WHERE is_active = 1 ORDER BY subject_name ASC";
                    $subResult = $db_handle->query($subSql);
                    while ($sub = $subResult->fetch_assoc()) {
                    ?>
                      <option value="<?php echo intval($sub['subject_id']); ?>"
                              data-department-id="<?php echo intval($sub['department_id'] ?? 0); ?>"
                              data-semester-id="<?php echo intval($sub['semester_id'] ?? 0); ?>">
                        <?php echo htmlspecialchars($sub['subject_name']); ?>
                      </option>
                    <?php } ?>
                  </select>
                  <small class="text-muted" id="subject_filter_hint" style="display:block; margin-top:4px;">Subjects are strictly filtered by selected Department, Class, and Semester.</small>
                </div>
              <?php } ?>
            </div>
            <div style="padding: 12px 20px; background-color: #f8fafc; border-top: 1px solid var(--erp-border); display: flex; align-items: center; gap: 8px;">
              <button type="submit" class="btn-erp-primary"><i class="fa fa-save"></i> Save <?php echo htmlspecialchars($roleLabel); ?></button>
              <button type="reset" class="btn-erp-secondary">Reset</button>
              <a href="<?php echo htmlspecialchars($infoFile); ?>" class="btn-erp-secondary" style="margin-left: auto;">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php if (intval($roleId) === 4) { ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var deptSelect = document.getElementById('department_select');
  var classSelect = document.getElementById('class_select');
  var semSelect = document.getElementById('semester_select');
  var subjSelect = document.getElementById('subject_select');
  var hint = document.getElementById('subject_filter_hint');
  if (!deptSelect || !subjSelect) return;

  var allOptions = Array.from(subjSelect.options).slice(1); // cache all subjects
  var allSemOptions = semSelect ? Array.from(semSelect.options).slice(1) : []; // cache all semesters

  function updateSemestersForClass() {
    if (!semSelect || !classSelect) return;
    var selectedClassText = classSelect.options[classSelect.selectedIndex] ? (classSelect.options[classSelect.selectedIndex].getAttribute('data-class-name') || '').toUpperCase() : '';
    var allowedSems = [];

    if (selectedClassText === 'FY' || selectedClassText === 'FE') {
      allowedSems = [1, 2];
    } else if (selectedClassText === 'SY' || selectedClassText === 'SE') {
      allowedSems = [3, 4];
    } else if (selectedClassText === 'TY' || selectedClassText === 'TE') {
      allowedSems = [5, 6];
    } else if (selectedClassText === 'BE') {
      allowedSems = [7, 8];
    }

    var currentSemVal = parseInt(semSelect.value) || 0;
    semSelect.innerHTML = '<option value="">Select Semester</option>';

    allSemOptions.forEach(function(opt) {
      var sId = parseInt(opt.value) || 0;
      if (allowedSems.length === 0 || allowedSems.indexOf(sId) !== -1) {
        var clone = opt.cloneNode(true);
        if (sId === currentSemVal) clone.selected = true;
        semSelect.appendChild(clone);
      }
    });

    if (allowedSems.length > 0 && allowedSems.indexOf(currentSemVal) === -1) {
      semSelect.value = '';
    }
  }

  function filterSubjects() {
    var selectedDept = parseInt(deptSelect.value) || 0;
    var selectedClass = classSelect ? (parseInt(classSelect.value) || 0) : 0;
    var selectedSem = semSelect ? (parseInt(semSelect.value) || 0) : 0;

    if (selectedDept === 0 || selectedClass === 0 || selectedSem === 0) {
      subjSelect.innerHTML = '<option value="">Select Department, Class, and Semester first</option>';
      subjSelect.disabled = true;
      if (hint) {
        hint.textContent = 'Please select Department, Class, and Semester to display valid subjects.';
        hint.style.color = '#64748b';
      }
      return;
    }

    var matchedCount = 0;
    subjSelect.innerHTML = '<option value="">Select Specialization Subject</option>';

    allOptions.forEach(function(opt) {
      var optDept = parseInt(opt.getAttribute('data-department-id')) || 0;
      var optSem = parseInt(opt.getAttribute('data-semester-id')) || 0;

      var deptMatch = (optDept === selectedDept || optDept === 0);
      var semMatch = (optSem === selectedSem);

      if (deptMatch && semMatch) {
        subjSelect.appendChild(opt.cloneNode(true));
        matchedCount++;
      }
    });

    if (matchedCount > 0) {
      subjSelect.disabled = false;
      if (hint) {
        hint.textContent = 'Found ' + matchedCount + ' subject(s) matching selected Department, Class, and Semester.';
        hint.style.color = '#16a34a';
      }
    } else {
      subjSelect.innerHTML = '<option value="">No subjects match the selected Department, Class, and Semester</option>';
      subjSelect.disabled = true;
      if (hint) {
        hint.textContent = 'No active subjects found for this Department, Class, and Semester.';
        hint.style.color = '#dc2626';
      }
    }
  }

  deptSelect.addEventListener('change', filterSubjects);

  if (classSelect) {
    classSelect.addEventListener('change', function() {
      updateSemestersForClass();
      filterSubjects();
    });
  }

  if (semSelect) {
    semSelect.addEventListener('change', filterSubjects);
  }
});
</script>
<?php } ?>
<?php include "header/footer.php"; ?>
