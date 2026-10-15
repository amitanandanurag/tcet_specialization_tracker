<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require "../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_SESSION['user_session'])) {
  header("location: ../index.php");
  exit();
}

$loginUserId = intval($_SESSION['user_id'] ?? 0);

$userData = $db_handle->runQuery("
    SELECT role_id, department_id
    FROM st_user_master
    WHERE user_id='$loginUserId'
");

$loginRole = intval($userData[0]['role_id'] ?? 0);
$loginDepartment = intval($userData[0]['department_id'] ?? 0);

function mentor_allocation_fetch_mentors($db_handle)
{
  $sql = "SELECT
            u.user_id AS mentor_id,
            COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,l.username,
            COALESCE(d.department_name, '') AS department_name,
            COUNT(msm.mapping_id) AS assigned_students
          FROM st_user_master u
          LEFT JOIN st_login l ON l.user_id = u.user_id
          LEFT JOIN st_department_master d ON d.department_id = u.department_id
          LEFT JOIN st_mentor_student_mapping msm ON msm.mentor_id = u.user_id
          WHERE u.role_id = 4
          GROUP BY u.user_id, mentor_name, l.username, d.department_name
          ORDER BY mentor_name ASC";

  return $db_handle->runQuery($sql) ?? array();
}

function mentor_allocation_build_student_where($conn, $filters, $loginRole, $loginDepartment)
{
  $where = " WHERE sm.status = '0' ";

   if ($loginRole == 3) {          // change 3 to your Department role id
        $where .= " AND sm.department_id = '".intval($loginDepartment)."' ";
    }

  if (!empty($filters['class_id'])) {
    $classId = mysqli_real_escape_string($conn, $filters['class_id']);
    $where .= " AND sm.class_id = '{$classId}' ";
  }

  if (!empty($filters['section_id'])) {
    $sectionId = mysqli_real_escape_string($conn, $filters['section_id']);
    $where .= " AND sm.division_id = '{$sectionId}' ";
  }

  if (!empty($filters['session'])) {
    $session = mysqli_real_escape_string($conn, $filters['session']);
    $where .= " AND sm.academic_year_id = '{$session}' ";
  }


  if (!empty($filters['mentor_id'])) {
    $mentorId = intval($filters['mentor_id']);
    if ($mentorId > 0) {
      $where .= " AND msm.mentor_id = {$mentorId} ";
    }
  }

  if (!empty($filters['assignment_status'])) {
    if ($filters['assignment_status'] === 'assigned') {
      $where .= " AND msm.mentor_id IS NOT NULL ";
    } elseif ($filters['assignment_status'] === 'unassigned') {
      $where .= " AND msm.mentor_id IS NULL ";
    }
  }

  if (!empty($filters['search'])) {
    $search = mysqli_real_escape_string($conn, $filters['search']);
    $where .= " AND (
      sm.registration_no LIKE '%{$search}%'
      OR sm.fname LIKE '%{$search}%'
      OR sm.roll_no LIKE '%{$search}%'
      OR cl.class_name LIKE '%{$search}%'
      OR sec.sections LIKE '%{$search}%'
      OR dep.department_name LIKE '%{$search}%'
      OR COALESCE(NULLIF(TRIM(mu.user_name), ''), ml.username) LIKE '%{$search}%'
      OR sm.academic_year_id LIKE '%{$search}%'
    ) ";
  }

  return $where;
}

function mentor_allocation_fetch_student_ids($db_handle, $filters)
{
  global $loginRole, $loginDepartment;
  $where = mentor_allocation_build_student_where($db_handle->conn, $filters,$loginRole,$loginDepartment);
  
  $sql = "SELECT sm.student_id
          FROM st_student_master sm
          LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
          LEFT JOIN st_section_master sec ON sec.id = sm.division_id
          LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
          LEFT JOIN st_mentor_student_mapping msm ON msm.student_id = sm.student_id AND msm.academic_year_id = sm.academic_year_id
          LEFT JOIN st_login ml ON ml.user_id = msm.mentor_id
          LEFT JOIN st_user_master mu ON mu.user_id = msm.mentor_id AND mu.role_id = 4
          {$where}
          ORDER BY sm.fname ASC, sm.student_id ASC";

  $rows = $db_handle->runQuery($sql) ?? array();
  $ids = array();

  foreach ($rows as $row) {
    $studentId = intval($row['student_id'] ?? 0);
    if ($studentId > 0) {
      $ids[] = $studentId;
    }
  }

  return $ids;
}

function mentor_allocation_assign_students($db_handle, $mentorId, $studentIds, $semesterId = '')
{
  $mentorId = intval($mentorId);
  $cleanIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));

  if ($mentorId <= 0 || empty($cleanIds)) {
    return false;
  }

  mysqli_begin_transaction($db_handle->conn);

  try {
    foreach ($cleanIds as $studentId) {
      $studentRes = mysqli_query($db_handle->conn, "SELECT current_semester_id, academic_year_id FROM st_student_master WHERE student_id = {$studentId}");
      $semId = 1;
      $ayId = 1;
      if ($studentRes && $studentRow = mysqli_fetch_assoc($studentRes)) {
        $semId = intval($studentRow['current_semester_id'] ?? 1);
        $ayId = intval($studentRow['academic_year_id'] ?? 1);
      }
      
      if ($semesterId !== '') {
        $semId = intval($semesterId);
      }

      if (!mysqli_query($db_handle->conn, "DELETE FROM st_mentor_student_mapping WHERE student_id = {$studentId} AND academic_year_id = {$ayId}")) {
        throw new Exception('Unable to clear existing mentor mapping.');
      }

      $insertSql = "INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id, academic_year_id) VALUES ({$mentorId}, {$studentId}, {$semId}, {$ayId})";
      if (!mysqli_query($db_handle->conn, $insertSql)) {
        throw new Exception('Unable to save mentor mapping.');
      }
    }

    mysqli_commit($db_handle->conn);
    return true;
  } catch (Throwable $e) {
    mysqli_rollback($db_handle->conn);
    return false;
  }
}

if (isset($_GET['action']) && $_GET['action'] === 'load_students') {
  header('Content-Type: application/json');

  $requestData = $_REQUEST;
  $filters = array(
    'class_id' => $_POST['class_id'] ?? '',
    'section_id' => $_POST['section_id'] ?? '',
    'session' => $_POST['session'] ?? '',
    'mentor_id' => $_POST['mentor_filter'] ?? '',
    'assignment_status' => $_POST['assignment_status'] ?? '',
    'semester_id' => $_POST['semester_id'] ?? '',
    'search' => $requestData['search']['value'] ?? ''
  );

  $where = mentor_allocation_build_student_where($db_handle->conn, $filters,$loginRole,$loginDepartment);
  
  $baseSql = "FROM st_student_master sm
              LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
              LEFT JOIN st_section_master sec ON sec.id = sm.division_id
              LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
              LEFT JOIN st_mentor_student_mapping msm ON msm.student_id = sm.student_id AND msm.academic_year_id = sm.academic_year_id
              LEFT JOIN st_login ml ON ml.user_id = msm.mentor_id
              LEFT JOIN st_user_master mu ON mu.user_id = msm.mentor_id AND mu.role_id = 4
              {$where}";

  $totalRows = $db_handle->runQuery("SELECT COUNT(*) AS total FROM st_user_master WHERE role_id = 4") ?? array();
  $filteredRows = $db_handle->runQuery("SELECT COUNT(*) AS total {$baseSql}") ?? array();
  $totalData = intval($totalRows[0]['total'] ?? 0);
  $totalFiltered = intval($filteredRows[0]['total'] ?? 0);

  $columns = array(
    0 => 'sm.student_id',
    1 => 'sm.registration_no',
    2 => 'sm.fname',
    3 => 'cl.class_name',
    4 => 'sec.sections',
    5 => 'dep.department_name',
    6 => 'sm.academic_year_id',
    7 => 'mentor_name'
  );

  $orderColumnIndex = isset($requestData['order'][0]['column']) ? intval($requestData['order'][0]['column']) : 2;
  $orderColumn = $columns[$orderColumnIndex] ?? 'sm.fname';
  $orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
  $start = isset($requestData['start']) ? intval($requestData['start']) : 0;
  $length = isset($requestData['length']) ? intval($requestData['length']) : 15;

  $sql = "SELECT
            sm.student_id,
            sm.registration_no,
            sm.fname,
            sm.roll_no,
            COALESCE(cl.class_name, '') AS class_name,
            COALESCE(sec.sections, '') AS section_name,
            COALESCE(dep.department_name, '') AS department_name,
            COALESCE(sm.academic_year_id, '') AS academic_year_id,
            COALESCE(NULLIF(TRIM(mu.user_name), ''), ml.username, '') AS mentor_name
          {$baseSql}
          ORDER BY {$orderColumn} {$orderDir}
          LIMIT {$start}, {$length}";

  $rows = $db_handle->runQuery($sql) ?? array();
  $data = array();
  $srNo = $start + 1;

  foreach ($rows as $row) {
    $studentId = intval($row['student_id'] ?? 0);
    $data[] = array(
      "<input type='checkbox' class='student-checkbox' value='{$studentId}'>",
      $srNo++,
      htmlspecialchars($row['registration_no'] ?? ''),
      htmlspecialchars($row['fname'] ?? ''),
      htmlspecialchars($row['class_name'] ?? ''),
      htmlspecialchars($row['section_name'] ?? ''),
      htmlspecialchars($row['department_name'] ?? ''),
      htmlspecialchars($row['academic_year_id'] ?? ''),
      htmlspecialchars($row['mentor_name'] !== '' ? $row['mentor_name'] : 'Unassigned')
    );
  }

  echo json_encode(array(
    'draw' => intval($requestData['draw'] ?? 0),
    'recordsTotal' => $totalData,
    'recordsFiltered' => $totalFiltered,
    'data' => $data
  ));
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_selected') {
  header('Content-Type: application/json');

  if (!DBController::validateCsrfToken()) {
    echo json_encode(array('success' => false, 'message' => 'Security token expired. Please refresh the page.'));
    exit();
  }

  $mentorId = intval($_POST['mentor_id'] ?? 0);
  $studentIds = $_POST['student_ids'] ?? array();
  $semesterId = $_POST['semester_id'] ?? '';
  $success = mentor_allocation_assign_students($db_handle, $mentorId, is_array($studentIds) ? $studentIds : array(), $semesterId);

  if ($success) {
    if (method_exists($db_handle, 'writeAuditLog')) {
      $db_handle->writeAuditLog($_SESSION['user_session'] ?? 0, 'MENTOR_ALLOCATION_UPDATED', 'st_mentor_student_mapping', null, 'Assigned selected students to mentor ID ' . $mentorId . ' for Semester ID ' . $semesterId);
    }
    echo json_encode(array('success' => true, 'message' => 'Selected students allocated successfully.'));
  } else {
    echo json_encode(array('success' => false, 'message' => 'Unable to allocate selected students.'));
  }
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_filtered') {
  header('Content-Type: application/json');

  if (!DBController::validateCsrfToken()) {
    echo json_encode(array('success' => false, 'message' => 'Security token expired. Please refresh the page.'));
    exit();
  }

  $mentorId = intval($_POST['mentor_id'] ?? 0);
  $filters = array(
    'class_id' => $_POST['class_id'] ?? '',
    'section_id' => $_POST['section_id'] ?? '',
    'session' => $_POST['session'] ?? '',
    'mentor_id' => $_POST['mentor_filter'] ?? '',
    'assignment_status' => $_POST['assignment_status'] ?? '',
    'semester_id' => $_POST['semester_id'] ?? '',
    'search' => $_POST['search_value'] ?? ''
  );

  $studentIds = mentor_allocation_fetch_student_ids($db_handle, $filters);
  if (empty($studentIds)) {
    echo json_encode(array('success' => false, 'message' => 'No students found for the selected filters.'));
    exit();
  }

  $success = mentor_allocation_assign_students($db_handle, $mentorId, $studentIds, $filters['semester_id']);

  if ($success) {
    if (method_exists($db_handle, 'writeAuditLog')) {
      $db_handle->writeAuditLog($_SESSION['user_session'] ?? 0, 'MENTOR_ALLOCATION_UPDATED', 'st_mentor_student_mapping', null, 'Assigned filtered students to mentor ID ' . $mentorId . ' for Semester ID ' . $filters['semester_id'] . '. Total students: ' . count($studentIds));
    }
    echo json_encode(array('success' => true, 'message' => 'Filtered students allocated successfully.', 'count' => count($studentIds)));
  } else {
    echo json_encode(array('success' => false, 'message' => 'Unable to allocate filtered students.'));
  }
  exit();
}

$mentors = mentor_allocation_fetch_mentors($db_handle);
$classRows = $db_handle->runQuery("SELECT class_id, class_name FROM st_class_master ORDER BY class_name ASC") ?? array();
$sectionRows = $db_handle->runQuery("SELECT id, sections FROM st_section_master ORDER BY sections ASC") ?? array();
$sessionRows = $db_handle->runQuery("SELECT DISTINCT academic_year_id FROM st_student_master WHERE academic_year_id IS NOT NULL AND academic_year_id != '' ORDER BY academic_year_id DESC") ?? array();

include "header/header.php";
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1><i class="fa fa-users"></i> Mentor Allocation</h1>
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Mentor Allocation</li>
    </ol>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="erp-card">
          <div class="erp-card-header">
            <h3 class="erp-card-title"><i class="fa fa-users"></i> Assign One Mentor To Every Student</h3>
          </div>
          <div class="erp-card-body">
            <div class="row" style="margin-bottom: 15px;">
              <div class="col-md-3">
                <label style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Mentor</label>
                <select class="form-control" id="mentor_id">
                  <option value="">Select Mentor</option>
                  <?php foreach ($mentors as $mentor) { ?>
                    <option value="<?php echo (int) $mentor['mentor_id']; ?>">
                      <?php echo htmlspecialchars(($mentor['mentor_name'] ?? '') . ($mentor['department_name'] ? ' - ' . $mentor['department_name'] : '')); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-2">
                <label style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Class</label>
                <select class="form-control" id="class_id">
                  <option value="">All Classes</option>
                  <?php foreach ($classRows as $classRow) { ?>
                    <option value="<?php echo (int) $classRow['class_id']; ?>">
                      <?php echo htmlspecialchars($classRow['class_name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-2">
                <label style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Division</label>
                <select class="form-control" id="section_id">
                  <option value="">All Divisions</option>
                  <?php foreach ($sectionRows as $sectionRow) { ?>
                    <option value="<?php echo (int) $sectionRow['id']; ?>">
                      <?php echo htmlspecialchars($sectionRow['sections']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-2">
                <label style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Session</label>
                <select class="form-control" id="session">
                  <option value="">All Sessions</option>
                  <?php foreach ($sessionRows as $sessionRow) { ?>
                    <option value="<?php echo htmlspecialchars($sessionRow['academic_year_id']); ?>">
                      <?php echo htmlspecialchars($sessionRow['academic_year_id']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-3">
                <label style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Current Status</label>
                <select class="form-control" id="assignment_status">
                  <option value="">All Students</option>
                  <option value="assigned">Assigned</option>
                  <option value="unassigned">Unassigned</option>
                </select>
              </div>
            </div>

            <div class="row" style="margin-bottom: 20px;">
              <div class="col-md-3">
                <label style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Current Mentor Filter</label>
                <select class="form-control" id="mentor_filter">
                  <option value="">All Mentors</option>
                  <?php foreach ($mentors as $mentor) { ?>
                    <option value="<?php echo (int) $mentor['mentor_id']; ?>">
                      <?php echo htmlspecialchars($mentor['mentor_name'] ?? ''); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-3">
                <label style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Allocation Semester</label>
                <select class="form-control" id="semester_id">
                  <option value="">Use Current Semester</option>
                  <?php
                  $semesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC") ?? [];
                  foreach ($semesters as $sem) {
                  ?>
                    <option value="<?php echo intval($sem['semester_id']); ?>">
                      <?php echo htmlspecialchars($sem['semester_name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-6" style="padding-top: 24px; display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn-erp-primary" id="apply_filters"><i class="fa fa-filter"></i> Apply Filters</button>
                <button type="button" class="btn-erp-secondary" id="reset_filters"><i class="fa fa-refresh"></i> Reset</button>
                <button type="button" class="btn-erp-primary" style="background:#16a34a; border-color:#16a34a;" id="assign_selected_btn"><i class="fa fa-check-square-o"></i> Assign Selected</button>
                <button type="button" class="btn-erp-primary" style="background:#d97706; border-color:#d97706;" id="assign_filtered_btn"><i class="fa fa-random"></i> Assign All Filtered</button>
              </div>
            </div>

            <div class="table-responsive" style="border: 1px solid var(--erp-border); border-radius: 4px;">
              <table id="mentorAllocationTable" class="erp-table" width="100%">
                <thead>
                  <tr>
                    <th style="width: 40px;"><input type="checkbox" id="select_all_students"></th>
                    <th style="width: 50px;">Sr. No</th>
                    <th>Reg. No</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Session</th>
                    <th>Current Mentor</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
<script>
  $(document).ready(function () {
    var mentorTable = $('#mentorAllocationTable').DataTable({
      processing: true,
      serverSide: true,
      pageLength: 15,
      order: [[3, 'asc']],
      ajax: {
        url: 'mentor_allocation.php?action=load_students',
        type: 'POST',
        data: function (d) {
          d.class_id = $('#class_id').val();
          d.section_id = $('#section_id').val();
          d.session = $('#session').val();
          d.mentor_filter = $('#mentor_filter').val();
          d.assignment_status = $('#assignment_status').val();
          d.semester_id = $('#semester_id').val();
        }
      },
      columnDefs: [
        { orderable: false, targets: [0] }
      ]
    });

    function selectedStudentIds() {
      var ids = [];
      $('.student-checkbox:checked').each(function () {
        ids.push($(this).val());
      });
      return ids;
    }

    function ensureMentorSelected() {
      if (!$('#mentor_id').val()) {
        alert('Please select a mentor first.');
        return false;
      }
      return true;
    }

    $('#apply_filters').on('click', function () {
      mentorTable.ajax.reload();
    });

    $('#reset_filters').on('click', function () {
      $('#class_id, #section_id, #session, #mentor_filter, #assignment_status, #mentor_id, #semester_id').val('');
      $('div.dataTables_filter input').val('');
      mentorTable.search('').draw();
    });

    $('#select_all_students').on('change', function () {
      $('.student-checkbox').prop('checked', this.checked);
    });

    $(document).on('change', '.student-checkbox', function () {
      if (!this.checked) {
        $('#select_all_students').prop('checked', false);
      }
    });

    $('#assign_selected_btn').on('click', function () {
      if (!ensureMentorSelected()) {
        return;
      }

      var studentIds = selectedStudentIds();
      if (!studentIds.length) {
        alert('Please select at least one student.');
        return;
      }

      $.ajax({
        url: 'mentor_allocation.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'assign_selected',
          mentor_id: $('#mentor_id').val(),
          student_ids: studentIds,
          semester_id: $('#semester_id').val()
        },
        success: function (resp) {
          alert((resp && resp.message) ? resp.message : 'Assignment completed.');
          $('#select_all_students').prop('checked', false);
          mentorTable.ajax.reload(null, false);
        },
        error: function () {
          alert('Unable to assign selected students right now.');
        }
      });
    });

    $('#assign_filtered_btn').on('click', function () {
      if (!ensureMentorSelected()) {
        return;
      }

      if (!confirm('Assign the selected mentor to all students matching the current filters?')) {
        return;
      }

      $.ajax({
        url: 'mentor_allocation.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'assign_filtered',
          mentor_id: $('#mentor_id').val(),
          class_id: $('#class_id').val(),
          section_id: $('#section_id').val(),
          session: $('#session').val(),
          mentor_filter: $('#mentor_filter').val(),
          assignment_status: $('#assignment_status').val(),
          semester_id: $('#semester_id').val(),
          search_value: $('div.dataTables_filter input').val()
        },
        success: function (resp) {
          alert((resp && resp.message) ? resp.message : 'Filtered allocation completed.');
          $('#select_all_students').prop('checked', false);
          mentorTable.ajax.reload(null, false);
        },
        error: function () {
          alert('Unable to assign filtered students right now.');
        }
      });
    });
  });
</script>
<?php include "header/footer.php"; ?>