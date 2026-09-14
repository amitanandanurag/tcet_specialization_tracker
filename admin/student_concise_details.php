<?php include "header/header.php"; ?>
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>

<!-- DataTables + Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">

<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<style>
  .wrapper2 {
    border-radius: var(--erp-radius-md, 6px);
    overflow: hidden;
  }

  .page-header-box {
    background: #ffffff;
    padding: 14px 18px;
    border-radius: var(--erp-radius-sm, 4px);
    margin-bottom: 16px;
    border: 1px solid var(--erp-border, #e2e8f0);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  }

  .page-header-box h1 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--erp-text-main, #0f172a);
  }

  select.form-control {
    border-radius: var(--erp-radius-sm, 4px);
    border: 1px solid var(--erp-border-dark, #cbd5e1);
    height: 36px;
    font-size: 13px;
  }

  #resetFilters {
    background: #ffffff;
    color: var(--erp-text-secondary, #475569);
    border: 1px solid var(--erp-border-dark, #cbd5e1);
    padding: 7px 14px;
    border-radius: var(--erp-radius-sm, 4px);
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: 0.15s;
  }

  #resetFilters:hover {
    background: #f8fafc;
    color: var(--erp-text-main, #0f172a);
  }

  #exportData {
    background: var(--erp-primary, #423cbc);
    color: #fff;
    border: 1px solid var(--erp-primary-hover, #352fa1);
    padding: 7px 14px;
    border-radius: var(--erp-radius-sm, 4px);
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: 0.15s;
  }

  #exportData:hover {
    background: var(--erp-primary-hover, #352fa1);
  }

  .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--erp-primary, #423cbc) !important;
    color: #fff !important;
    border-color: var(--erp-primary, #423cbc) !important;
  }

  .skeleton {
    height: 15px;
    background: #f1f5f9;
    margin: 8px 0;
    border-radius: 4px;
  }
</style>
<div class="content-wrapper">
  <section class="content">
 <div class="row">
      <div class="col-md-12">

        <div class="page-header-box">
          <h1>STUDENT CONCISE DETAILS</h1>
        </div>

    <div id="loadingSkeleton" style="display:none;">
      <div class="skeleton"></div>
      <div class="skeleton"></div>
      <div class="skeleton"></div>
    </div>

    <div class="wrapper2 box box-primary">

      <!-- FILTER ROW -->
      <table class="table table-bordered">
        <thead>
          <tr>

            <th>
              <select class="form-control" id="select_class">
                <option value="" hidden>Class</option>
                <?php
                $result = $db_handle->query("SELECT * FROM st_class_master");
                while ($row = $result->fetch_assoc()) {
                  echo '<option value="' . $row['class_id'] . '">' . $row['class_name'] . '</option>';
                }
                ?>
              </select>
            </th>

            <th>
              <select class="form-control" id="select_section">
                <option value="" hidden>Division</option>
                <?php
                $result = $db_handle->query("SELECT * FROM st_section_master");
                while ($row = $result->fetch_assoc()) {
                  echo '<option value="' . $row['id'] . '">' . $row['sections'] . '</option>';
                }
                ?>
              </select>
            </th>

            <th>
              <select class="form-control" id="select_specialization">
                <option value="" hidden>Specialization</option>
                <?php
                $result = $db_handle->query("SELECT * FROM st_specialization_master");
                while ($row = $result->fetch_assoc()) {
                  echo '<option value="' . $row['specialization_id'] . '">' . $row['specialization_name'] . '</option>';
                }
                ?>
              </select>
            </th>

            <th>
              <select class="form-control" id="select_department">
                <option value="" hidden>Department</option>
                <?php
                $result = $db_handle->query("SELECT * FROM st_department_master");
                while ($row = $result->fetch_assoc()) {
                  echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
                }
                ?>
              </select>
            </th>

            <th>
              <select class="form-control" id="select_specialization_subject">
                <option value="" hidden>Specialization Subject</option>
                <?php
                $result = $db_handle->query("SELECT * FROM st_specialization_subject_master");
                while ($row = $result->fetch_assoc()) {
                  echo '<option value="' . $row['subject_id'] . '">' . $row['subject_name'] . '</option>';
                }
                ?>
              </select>
            </th>

            <th>
              <button id="exportData" class="btn">Export data</button>
              <button id="resetFilters" class="btn">Reset</button>
            </th>

          </tr>
        </thead>
      </table>

      <!-- DATA TABLE -->
      <table id="myTable" class="table table-bordered">
        <thead>
          <tr>
            <th>SR NO</th>
            <th>Class</th>
            <th>Division</th>
            <th>Department</th>
            <th>Specialization</th>
            <th>Specialization Subject</th>
            <th>Student Count</th>
          </tr>
        </thead>
      </table>

    </div>
  </section>
</div>

<script>
  $(document).ready(function () {


    fetch_data();

    function fetch_data() {

      $('#myTable').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        searching: false,

        pageLength: 10,
        lengthMenu: [[10, 25, 100], [10, 25, 100]],

        //   FIXED (ADD 'l' FOR DROPDOWN)
        dom: 'lBfrtip',

        buttons: [
          { extend: 'excelHtml5', text: 'Excel' },
          { extend: 'pdfHtml5', text: 'PDF' },
          { extend: 'csvHtml5', text: 'CSV' }
        ],

        ajax: {
          url: "student_concise_details_ajax.php",
          type: "POST",
          data: function (d) {
            d.select_class = $('#select_class').val();
            d.select_section = $('#select_section').val();
            d.select_department = $('#select_department').val();
            d.select_specialization = $('#select_specialization').val();
            d.select_specialization_subject = $('#select_specialization_subject').val();
          }
        }
      });
    }

    //   FILTER CHANGE (NO DUPLICATE CALLS)
    $('#select_class, #select_section, #select_department, #select_specialization, #select_specialization_subject')
      .on('change', function () {
        $('#myTable').DataTable().destroy();
        fetch_data();
      });

    //   RESET (FIXED - RELOAD TABLE ALSO)
    $('#resetFilters').click(function () {
      $('#select_class, #select_section, #select_department, #select_specialization, #select_specialization_subject').val('');
      $('#myTable').DataTable().destroy();
      fetch_data();
    });

    //   LOADING HANDLER (ATTACH AFTER INIT)
    $('#myTable').on('preXhr.dt', function () {
      $('#loadingSkeleton').show();
    });

    $('#myTable').on('xhr.dt', function () {
      $('#loadingSkeleton').hide();
    });

    //   EXPORT BUTTON
    $('#exportData').click(function () {

      let params = {
        export: true,
        select_class: $('#select_class').val(),
        select_section: $('#select_section').val(),
        select_department: $('#select_department').val(),
        select_specialization: $('#select_specialization').val(),
        select_specialization_subject: $('#select_specialization_subject').val()
      };

      $('#exportData').text('Exporting...');

      $.ajax({
        url: "student_concise_details_ajax.php",
        type: "POST",
        data: params,

        success: function (response) {

          let json = JSON.parse(response);
          let csv = '';

          // HEADER
          csv += "SR NO,Class,Division,Department,Specialization,Subject,Student Count\n";

          // DATA
          json.data.forEach(row => {
            csv += row.join(",") + "\n";
          });

          // DOWNLOAD
          let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
          let link = document.createElement("a");

          link.href = URL.createObjectURL(blob);
          link.download = "student_data.csv";
          link.click();

          $('#exportData').text('Export data');
        },

        error: function () {
          alert("Export failed!");
          $('#exportData').text('Export data');
        }
      });

    });

  });
</script>

<?php include "header/footer.php"; ?>