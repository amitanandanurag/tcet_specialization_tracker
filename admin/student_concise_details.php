<?php include "header/header.php"; ?>
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>

<div class="content-wrapper">
  <section class="content">
    <section class="content-header">
      <h1>
        <i class="fa fa-list"></i> STUDENT CONCISE DETAILS
      </h1>
    </section>

    <div class="row">
      <div class="col-md-12">
        <div class="wrapper2 box box-primary">
          <div class="div2 box-header with-border">
            <h3 class="box-title">Filter By</h3>
          </div>

          <table class="table table-bordered">
            <thead>
              <tr>

                <th style="background: linear-gradient(90deg,#95CF0F,#0FCFA9);">
                  <select class="form-control" id="select_class" style="border:2px solid #0FCFA9; border-radius:6px;">
                    <option value="" hidden>Class</option>
                    <?php
                    $result = $db_handle->query("SELECT * FROM st_class_master");
                    while ($row = $result->fetch_assoc()) {
                      ?>
                      <option value="<?php echo $row['class_id']; ?>">
                        <?php echo $row['class_name']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </th>

                <th style="background: linear-gradient(90deg,#95CF0F,#0FCFA9);">
                  <select class="form-control" id="select_section" style="border:2px solid #0FCFA9; border-radius:6px;">
                    <option value="" hidden>Division</option>
                    <?php
                    $result = $db_handle->query("SELECT * FROM st_section_master");
                    while ($row = $result->fetch_assoc()) {
                      ?>
                      <option value="<?php echo $row['id']; ?>"><?php echo $row['sections']; ?></option>
                    <?php } ?>
                  </select>
                </th>

                <th style="background: linear-gradient(90deg,#95CF0F,#0FCFA9);">
                  <select class="form-control" id="select_specialization"
                    style="border:2px solid #0FCFA9; border-radius:6px;">
                    <option value="" hidden>Specialization</option>
                    <?php
                    $result = $db_handle->query("SELECT * FROM st_specialization_master");
                    while ($row = $result->fetch_assoc()) {
                      ?>
                      <option value="<?php echo $row['specialization_id']; ?>">
                        <?php echo $row['specialization_name']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </th>

                <th style="background: linear-gradient(90deg,#95CF0F,#0FCFA9);">
                  <select class="form-control" id="select_department"
                    style="border:2px solid #0FCFA9; border-radius:6px;">
                    <option value="" hidden>Department</option>
                    <?php
                    $result = $db_handle->query("SELECT * FROM st_department_master");
                    while ($row = $result->fetch_assoc()) {
                      ?>
                      <option value="<?php echo $row['department_id']; ?>"><?php echo $row['department_name']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </th>

                <th style="background: linear-gradient(90deg,#95CF0F,#0FCFA9);">
                  <select class="form-control" id="select_specialization_subject"
                    style="border:2px solid #0FCFA9; border-radius:6px;">
                    <option value="" hidden>Specialization Subject</option>
                    <?php
                    $result = $db_handle->query("SELECT * FROM st_specialization_subject_master");
                    while ($row = $result->fetch_assoc()) {
                      ?>
                      <option value="<?php echo $row['subject_id']; ?>">
                        <?php echo $row['subject_name']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </th>
                <th style="background: linear-gradient(90deg,#95CF0F,#0FCFA9);">
                  <button id="applyFilters" class="btn"
                    style="background:linear-gradient(90deg,#4e54c8,#6a5af9); color:white; border:none; margin-right:6px;">
                    Apply Filters
                  </button>

                  <button id="resetFilters" class="btn" style="background:#e74c3c; color:white; border:none;">
                    Reset
                  </button>
                </th>
              </tr>
            </thead>
          </table>

     
          <table id="myTable" class="table table-bordered" style="border:1px solid #4e54c8;">
            <thead>
              <tr>
                <th style="background: linear-gradient(90deg,#0DF387,#0FCFA9);">SR NO</th>
                <th style="background: linear-gradient(90deg,#0DF387,#0FCFA9);">Class</th>
                <th style="background: linear-gradient(90deg,#0DF387,#0FCFA9);">Division</th>
                <th style="background: linear-gradient(90deg,#0DF387,#0FCFA9);">Department</th>
                <th style="background: linear-gradient(90deg,#0DF387,#0FCFA9);">Specialization</th>
                <th style="background: linear-gradient(90deg,#0DF387,#0FCFA9);">Specialization Subject</th>
                <th style="background: linear-gradient(90deg,#0DF387,#0FCFA9);">Student Count</th>
              </tr>
            </thead>
          </table>

        </div>
      </div>
    </div>
</div>

</section>
</div>

<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>

<script>
  $(document).ready(function () {

  fetch_data();

  function fetch_data() {

    $('#myTable').DataTable({
      destroy: true,
      processing: true,
      serverSide: true,
      searching: false,

      pageLength: 3000,
      lengthMenu: [
        [10, 50, 100, 500, 1000, 2000, 3000],
        [10, 50, 100, 500, 1000, 2000, 3000]
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

//apply filters button
  $('#applyFilters').click(function () {

    $(this).text('Applying...').prop('disabled', true);

    $('#myTable').DataTable().destroy();
    fetch_data();

    highlightFilters();

    setTimeout(() => {
      $('#applyFilters').text('Apply Filters').prop('disabled', false);
    }, 500);
  });

//reset filters button
  $('#resetFilters').click(function () {

    $('#select_class').val('');
    $('#select_section').val('');
    $('#select_department').val('');
    $('#select_specialization').val('');
    $('#select_specialization_subject').val('');

    highlightFilters();

    $('#myTable').DataTable().destroy();
    fetch_data();
  });

//highlight filters with value(black border) and non value(green border)
  function highlightFilters() {
    $('#select_class, #select_section, #select_department, #select_specialization, #select_specialization_subject')
      .each(function () {
        if ($(this).val()) {
          $(this).css('border', '2px solid #4e54c8');
        } else {
          $(this).css('border', '2px solid #0FCFA9');
        }
      });
  }

//row highlight on hover
  $('#myTable tbody').on('mouseenter', 'tr', function () {
    $(this).css('background-color', '#e6f7ff');
  }).on('mouseleave', 'tr', function () {
    $(this).css('background-color', '');
  });

});
</script>

<?php include "header/footer.php"; ?>