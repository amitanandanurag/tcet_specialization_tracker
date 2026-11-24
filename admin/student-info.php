<?php
require_once __DIR__ . "/../database/db_connect.php";
if (class_exists('DBController')) {
    DBController::startSecureSession();
} elseif (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$db_handle = new DBController();

include "header/header.php";
$sessionRoleId = intval($_SESSION['user_type'] ?? $_SESSION['role_id'] ?? 0);
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="erp-page-header">
      <div class="erp-page-title-wrap">
        <h1 class="erp-page-title">Enrolled Students</h1>
        <div class="erp-page-subtitle">Manage student enrollment and academic records</div>
        <div class="erp-breadcrumb">
          <a href="index.php"><i class="fa fa-home"></i> Home</a>
          <span class="sep">/</span>
          <span class="active-item">Students</span>
        </div>
      </div>
      <div class="erp-page-actions">
        <?php if ($sessionRoleId == 1): ?>
        <a href="student_bulk_import.php" class="btn-erp-secondary" style="margin-right: 8px;">
          <i class="fa fa-file-excel-o"></i> Bulk Student Upload
        </a>
        <?php endif; ?>
        <a href="student_admission.php" class="btn-erp-primary">
          <i class="fa fa-user-plus"></i> Enroll Student
        </a>
      </div>
    </div>
  </section>

  <section class="content" style="padding: 0 20px 20px 20px;">
    <!-- Compact ERP Filter Toolbar -->
    <div class="erp-filter-card">
      <div class="erp-filter-top-row">
        <div class="erp-search-hero">
          <i class="fa fa-search"></i>
          <input type="text" id="customSearch" placeholder="Search by student name, roll no., ERP ID, email..." autocomplete="off">
        </div>
        <div class="erp-filter-actions">
          <button type="button" id="btnResetFilters" class="btn-erp-secondary" title="Reset all filters">
            <i class="fa fa-refresh"></i> Reset Filters
          </button>
          <a href="export_service.php?type=students" id="btnExportCsv" class="btn-erp-secondary" style="color: #15803d; border-color: #bbf7d0;" title="Download complete student dataset as CSV">
            <i class="fa fa-download"></i> Download CSV
          </a>
          <button type="button" onclick="bulkDelete()" class="btn-erp-danger" title="Delete selected students">
            <i class="fa fa-trash"></i> Bulk Delete
          </button>
        </div>
      </div>

      <div class="erp-filter-bottom-row">
        <div class="erp-filter-select-item">
          <label for="select_session">Academic Year</label>
          <select id="select_session" name="select_session">
            <option value="">All Academic Years</option>
            <?php
            $result = $db_handle->query("SELECT * FROM `st_session_master`");
            while ($row = $result->fetch_assoc()) {
              echo '<option value="' . $row['session_id'] . '">' . htmlspecialchars($row['session_name']) . '</option>';
            }
            ?>
          </select>
        </div>

        <div class="erp-filter-select-item">
          <label for="select_semester">Semester</label>
          <select id="select_semester" name="select_semester">
            <option value="">All Semesters</option>
            <?php
            $semester_result = $db_handle->query("SELECT semester_id, semester_name FROM `st_semester_master` ORDER BY semester_id");
            while ($row = $semester_result->fetch_assoc()) {
              echo "<option value='{$row['semester_id']}'>" . htmlspecialchars($row['semester_name']) . "</option>";
            }
            ?>
          </select>
        </div>

        <div class="erp-filter-select-item">
          <label for="select_department">Department</label>
          <select id="select_department" name="select_department">
            <option value="">All Departments</option>
            <?php
            $dept_result = $db_handle->query("SELECT department_id, department_name FROM `st_department_master` ORDER BY department_name");
            while ($row = $dept_result->fetch_assoc()) {
              echo "<option value='{$row['department_id']}'>" . htmlspecialchars($row['department_name']) . "</option>";
            }
            ?>
          </select>
        </div>

        <div class="erp-filter-select-item">
          <label for="select_class">Class</label>
          <select id="select_class" name="select_class">
            <option value="">All Classes</option>
            <?php
            $result = $db_handle->query("SELECT class_id AS id, class_name AS class FROM `st_class_master`");
            while ($row = $result->fetch_assoc()) {
              echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['class']) . '</option>';
            }
            ?>
          </select>
        </div>

        <div class="erp-filter-select-item">
          <label for="select_section">Division</label>
          <select id="select_section" name="select_section">
            <option value="">All Divisions</option>
            <?php
            $result = $db_handle->query("SELECT * FROM `st_section_master`");
            while ($row = $result->fetch_assoc()) {
              echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['sections']) . '</option>';
            }
            ?>
          </select>
        </div>

        <div class="erp-filter-select-item">
          <label for="select_batch">Batch</label>
          <select id="select_batch" name="select_batch">
            <option value="">All Batches</option>
            <?php
            $batch_result = $db_handle->query("SELECT batch_id, batch_name FROM `st_batch_master` ORDER BY batch_name DESC");
            while ($row = $batch_result->fetch_assoc()) {
              echo "<option value='{$row['batch_name']}'>" . htmlspecialchars($row['batch_name']) . "</option>";
            }
            ?>
          </select>
        </div>

        <div class="erp-filter-select-item">
          <label for="select_specialization">Specialization</label>
          <select id="select_specialization" name="select_specialization">
            <option value="">All Specializations</option>
            <?php
            $spec_result = $db_handle->query("SELECT specialization_id, specialization_name FROM `st_specialization_master` ORDER BY specialization_name");
            while ($row = $spec_result->fetch_assoc()) {
              echo "<option value='{$row['specialization_id']}'>" . htmlspecialchars($row['specialization_name']) . "</option>";
            }
            ?>
          </select>
        </div>

        <div class="erp-filter-select-item">
          <label for="select_mentor">Assigned Mentor</label>
          <select id="select_mentor" name="select_mentor">
            <option value="">All Mentors</option>
            <?php
            $mentor_result = $db_handle->query("SELECT user_id, user_name FROM `st_user_master` WHERE role_id = 4 ORDER BY user_name");
            while ($row = $mentor_result->fetch_assoc()) {
              echo "<option value='{$row['user_id']}'>" . htmlspecialchars($row['user_name']) . "</option>";
            }
            ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Main Data Grid Card -->
    <div class="erp-card">
      <div class="erp-card-header">
        <div class="erp-card-title-group">
          <h2 class="erp-card-title">Student Directory</h2>
          <span class="erp-count-badge" id="studentCountBadge"><i class="fa fa-spinner fa-spin"></i> Loading...</span>
        </div>
      </div>
      <div class="table-responsive" style="overflow-x: auto;">
        <table id="myTable" class="erp-table table table-hover" width="100%">
          <thead>
            <tr>
              <th style="width: 32px; text-align: center;" data-orderable="false"><input type="checkbox" id="select_all_header" style="cursor: pointer;"></th>
              <th style="width: 70px;">Roll No</th>
              <th style="min-width: 170px;">Student Name & ERP ID</th>
              <th style="width: 55px; text-align: center;">Class</th>
              <th style="width: 45px; text-align: center;">Div</th>
              <th style="width: 90px;">Academic Year</th>
              <th style="width: 75px;">Semester</th>
              <th style="width: 75px;">Department</th>
              <th style="min-width: 170px;">Specialization & Subject</th>
              <th style="width: 60px; text-align: right;">CGPA</th>
              <th style="min-width: 130px;">Contact</th>
              <th style="width: 95px; text-align: center;" data-orderable="false">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </section>
</div>

<style>
/* ==========================================================================
   TCET_ST — Student Profile & Academic Progression Modal Architecture
   Enforces a strict 3-part layout: Fixed Header, Scrollable Body, Fixed Footer
   ========================================================================== */
body.modal-open {
  overflow: hidden !important;
}

#view.modal, #edit.modal {
  overflow: hidden !important;
  padding-right: 0 !important;
}

#view .modal-dialog, #edit .modal-dialog {
  max-width: 980px !important;
  width: 92% !important;
  height: 90vh !important;
  max-height: 90vh !important;
  margin: 30px auto !important;
  display: flex !important;
  flex-direction: column !important;
}

#view .modal-content, #edit .modal-content {
  height: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #cbd5e1;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  background: #ffffff;
}

#view .modal-header, #edit .modal-header {
  flex: 0 0 auto;
  background: #4338ca !important;
  color: #ffffff !important;
  padding: 14px 22px !important;
  border-bottom: 1px solid #3730a3 !important;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 10;
}

#view .modal-header .modal-title, #edit .modal-header .modal-title {
  font-size: 15px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #ffffff !important;
  margin: 0;
  display: flex;
  align-items: center;
}

#view .modal-header .close, #edit .modal-header .close {
  color: #ffffff !important;
  opacity: 0.85;
  font-size: 24px;
  margin-top: 0;
  text-shadow: none;
  background: transparent;
  border: none;
  cursor: pointer;
  line-height: 1;
}

#view .modal-header .close:hover, #edit .modal-header .close:hover {
  opacity: 1;
}

#view .modal-body, #edit .modal-body {
  flex: 1 1 auto;
  min-height: 0;
  max-height: none !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
  background-color: #f8fafc;
  padding: 18px 20px 30px 20px !important;
}

#view .modal-footer, #edit .modal-footer {
  flex: 0 0 auto;
  background: #ffffff !important;
  border-top: 1px solid #e2e8f0 !important;
  padding: 12px 22px !important;
  margin: 0 !important;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
  z-index: 10;
}

@media (max-width: 768px) {
  #view .modal-dialog, #edit .modal-dialog {
    width: 96% !important;
    height: 94vh !important;
    max-height: 94vh !important;
    margin: 15px auto !important;
  }
}

@media (max-width: 576px) {
  #view .modal-dialog, #edit .modal-dialog {
    width: 98% !important;
    height: 96vh !important;
    max-height: 96vh !important;
    margin: 10px auto !important;
  }
  #view .modal-header, #edit .modal-header {
    padding: 12px 14px !important;
  }
  #view .modal-body, #edit .modal-body {
    padding: 12px 12px 24px 12px !important;
  }
  #view .modal-footer, #edit .modal-footer {
    padding: 10px 14px !important;
  }
}
</style>

<!-- View Modal -->
<div id="view" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">
          <i class="fa fa-user" style="margin-right: 6px;"></i> Student Profile & Academic Progression
        </h4>
      </div>
      <div class="modal-body">
        <div id="modal-loader" style="display: none; text-align: center; padding: 24px;">
          <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
          <p style="margin-top: 8px; color: #64748b; font-size: 12px;">Loading student record...</p>
        </div>
        <div id="dynamic-content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-erp-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">
          <i class="fa fa-pencil" style="margin-right: 6px;"></i> Edit Student Information
        </h4>
      </div>
      <div class="modal-body">
        <div id="edit-modal-loader" style="display: none; text-align: center; padding: 24px;">
          <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
          <p style="margin-top: 8px; color: #64748b; font-size: 12px;">Loading student editor...</p>
        </div>
        <div id="edit-dynamic-content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-erp-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>

<script>
  var dataTable;

  // Single Delete
  function delete_user(id, table) {
    Swal.fire({
      title: "Remove Student?",
      text: "Student will be moved to the left students archive.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#b91c1c",
      cancelButtonColor: "#64748b",
      confirmButtonText: "Yes, remove",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'student_delete.php',
          type: "POST",
          data: { id: id, table: table },
          dataType: "json",
          success: function(data) {
            if (data.status === 'success') {
              Swal.fire('Updated', 'Student record removed successfully.', 'success')
                .then(() => {
                  dataTable.ajax.reload();
                });
            } else {
              Swal.fire('Error', data.message || 'Could not delete student.', 'error');
            }
          },
          error: function() {
            Swal.fire('Error', 'Server connection error.', 'error');
          }
        });
      }
    });
  }

  // Bulk Delete
  function bulkDelete() {
    var selectedIds = [];
    $('.activeStudentRow:checked').each(function() {
      selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
      Swal.fire('No Active Selection', 'Please select at least one active student record. Historical archive records cannot be deleted.', 'info');
      return;
    }

    Swal.fire({
      title: "Delete " + selectedIds.length + " Student(s)?",
      text: "This action will archive all selected active student records.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#b91c1c",
      cancelButtonColor: "#64748b",
      confirmButtonText: "Yes, delete selected",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'student_bulk_delete.php',
          type: "POST",
          data: { ids: selectedIds, table: 'st_student_master' },
          dataType: "json",
          success: function(data) {
            if (data.status === 'success') {
              Swal.fire('Deleted', data.message, 'success')
                .then(() => {
                  dataTable.ajax.reload();
                });
            } else {
              Swal.fire('Error', data.message || 'Problem deleting students.', 'error');
            }
          },
          error: function() {
            Swal.fire('Error', 'Server communication failure.', 'error');
          }
        });
      }
    });
  }

  $(document).ready(function() {
    // Initialize DataTable
    if ($.fn.dataTable.isDataTable('#myTable')) {
      $('#myTable').DataTable().destroy();
    }

    dataTable = $('#myTable').DataTable({
      "processing": true,
      "serverSide": true,
      "ajax": {
        "url": "student_info_ajax.php",
        "type": "POST",
        "data": function(d) {
          d.select_class = $('#select_class').val();
          d.select_section = $('#select_section').val();
          d.select_session = $('#select_session').val();
          d.select_batch = $('#select_batch').val();
          d.select_semester = $('#select_semester').val();
          d.select_department = $('#select_department').val();
          d.select_specialization = $('#select_specialization').val();
          d.select_mentor = $('#select_mentor').val();
        }
      },
      "lengthMenu": [
        [15, 25, 50, 100, 500],
        [15, 25, 50, 100, 500]
      ],
      "pageLength": 15,
      "autoWidth": false,
      "scrollX": false,
      "dom": "<'row'<'col-sm-12'tr>><'erp-table-footer'<'erp-pagination-info'i><'erp-pagination-controls'p>>",
      "columnDefs": [
        { "orderable": false, "targets": [0, 11] },
        { "className": "text-center", "targets": [0, 3, 4, 11] },
        { "className": "text-right", "targets": [9] }
      ],
      "language": {
        "processing": "<span style='color: #423cbc; font-size: 13px; font-weight: 600;'><i class='fa fa-spinner fa-spin'></i> Loading records...</span>",
        "emptyTable": "<div style='padding: 32px 16px; text-align: center; color: #64748b;'><i class='fa fa-users' style='font-size: 32px; color: #cbd5e1; margin-bottom: 12px; display: block;'></i><strong style='font-size: 14px; color: #334155;'>No Students Found</strong><br><span style='font-size: 12px; color: #64748b;'>To register a student, click <a href=\"student_admission.php\" style=\"color: #4338ca; font-weight: 600;\">Enroll Student</a> or use <a href=\"student_bulk_import.php\" style=\"color: #4338ca; font-weight: 600;\">Bulk Student Upload</a>.</span></div>",
        "zeroRecords": "<div style='padding: 24px; text-align: center; color: #64748b;'><strong>No matching students found</strong><br><span style='font-size: 12px;'>Try changing your search or filter parameters.</span></div>",
        "info": "Showing _START_ to _END_ of _TOTAL_ students",
        "infoEmpty": "Showing 0 to 0 of 0 students",
        "paginate": {
          "previous": '<i class="fa fa-angle-left"></i> Previous',
          "next": 'Next <i class="fa fa-angle-right"></i>'
        }
      },
      "drawCallback": function(settings) {
        var total = settings.json ? settings.json.recordsFiltered : 0;
        $('#studentCountBadge').text(total.toLocaleString() + ' students');
        $('#select_all_header').prop('checked', false);
      }
    });

    // Link prominent search input with DataTables
    var searchTimer;
    $('#customSearch').on('keyup input', function() {
      clearTimeout(searchTimer);
      var val = $(this).val();
      searchTimer = setTimeout(function() {
        dataTable.search(val).draw();
      }, 250);
    });

    // Reset Filters
    $('#btnResetFilters').click(function() {
      $('#customSearch').val('');
      $('#select_class').val('');
      $('#select_section').val('');
      $('#select_session').val('');
      $('#select_batch').val('');
      $('#select_semester').val('');
      $('#select_department').val('');
      $('#select_specialization').val('');
      $('#select_mentor').val('');
      updateExportUrl();
      dataTable.search('').draw();
    });

    // Filter changes
    function updateExportUrl() {
      var dept = $('#select_department').val() || '';
      var sem = $('#select_semester').val() || '';
      var spec = $('#select_specialization').val() || '';
      var mentor = $('#select_mentor').val() || '';
      var url = 'export_service.php?type=students';
      if (dept) url += '&department_id=' + encodeURIComponent(dept);
      if (sem) url += '&semester_id=' + encodeURIComponent(sem);
      if (spec) url += '&specialization_id=' + encodeURIComponent(spec);
      if (mentor) url += '&mentor_id=' + encodeURIComponent(mentor);
      $('#btnExportCsv').attr('href', url);
    }

    $('#select_class, #select_section, #select_session, #select_batch, #select_semester, #select_department, #select_specialization, #select_mentor').change(function() {
      updateExportUrl();
      dataTable.ajax.reload();
    });

    // Select All (Target only enabled active student checkboxes)
    $(document).on('click', '#select_all_header', function() {
      var isChecked = $(this).is(':checked');
      $('.activeStudentRow:not(:disabled)').prop('checked', isChecked);
    });
  });

  // View and Edit modals
  $(document).ready(function() {
    $(document).on('click', '.student_view', function(e) {
      e.preventDefault();
      var uid = $(this).data('id');
      $('#dynamic-content').empty();
      $('#modal-loader').show();
      $('#view').modal('show');
      $.ajax({
        url: 'student_view.php',
        type: 'POST',
        data: 'id=' + uid,
        dataType: 'html'
      }).done(function(data) {
        $('#modal-loader').hide();
        $('#dynamic-content').html(data);
      }).fail(function() {
        $('#modal-loader').hide();
        $('#dynamic-content').html('<div class="alert alert-danger" style="margin: 10px;">Failed to load student record. Please try again.</div>');
      });
    });

    $(document).on('click', '.student_edit', function(e) {
      e.preventDefault();
      var uid = $(this).data('id');
      $('#edit-dynamic-content').empty();
      $('#edit-modal-loader').show();
      $('#edit').modal('show');
      $.ajax({
        url: 'student-edit.php',
        type: 'POST',
        data: 'id=' + uid,
        dataType: 'html'
      }).done(function(data) {
        $('#edit-modal-loader').hide();
        $('#edit-dynamic-content').html(data);
      }).fail(function() {
        $('#edit-modal-loader').hide();
        $('#edit-dynamic-content').html('<div class="alert alert-danger" style="margin: 10px;">Failed to load edit form. Please try again.</div>');
      });
    });
  });

  // Excel Export
  function fnExcelReport() {
    var table = document.getElementById("myTable");
    var excludeCols = [0, 11];
    var tableHTML = "<table border='1' style='border-collapse:collapse; font-family: sans-serif;'>";

    for (var i = 0; i < table.rows.length; i++) {
      tableHTML += "<tr>";
      var row = table.rows[i];
      for (var j = 0; j < row.cells.length; j++) {
        if (excludeCols.includes(j)) continue;
        var cell = row.cells[j];
        var tag = (i === 0) ? "th" : "td";
        var cellText = cell.innerText.replace(/\s+/g, ' ').trim();
        var bg = (i === 0) ? "background-color: #f1f5f9;" : "";
        tableHTML += `<${tag} style="padding:6px 10px;text-align:left;vertical-align:middle;${bg}">${cellText}</${tag}>`;
      }
      tableHTML += "</tr>";
    }
    tableHTML += "</table>";

    var blob = new Blob(['\ufeff', tableHTML], {
      type: 'application/vnd.ms-excel;charset=utf-8;'
    });
    var url = URL.createObjectURL(blob);
    var link = document.createElement("a");
    link.href = url;
    link.download = "TCET_Students_Export_" + (new Date().toISOString().slice(0, 10)) + ".xls";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
</script>

<?php include "header/footer.php"; ?>