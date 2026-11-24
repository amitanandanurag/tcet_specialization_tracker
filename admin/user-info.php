<?php include "header/header.php"; ?>
<?php
$typeParam = strtolower(trim($_GET['type'] ?? ''));
$roleParam = strtolower(trim($_GET['role'] ?? ''));

$roleMap = array(
  'all' => array('id' => 0, 'label' => 'All Users'),
  'superadmin' => array('id' => 1, 'label' => 'Super Admin'),
  'admin' => array('id' => 2, 'label' => 'Admin'),
  'coordinator' => array('id' => 3, 'label' => 'Coordinator / HOD'),
  'mentor' => array('id' => 4, 'label' => 'Mentor')
);

if ($typeParam === 'users' || $roleParam === 'all') {
  $roleKey = 'all';
} elseif (isset($roleMap[$roleParam])) {
  $roleKey = $roleParam;
} else {
  $roleKey = 'all';
}

$roleId = $roleMap[$roleKey]['id'];
$roleLabel = $roleMap[$roleKey]['label'];
?>

<!-- Scoped Institutional Design System Styles for User Directory -->
<style>
/* Scoped Wrapper */
.erp-userdir-wrap {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #263445;
  background-color: #F5F7FA;
  padding-bottom: 30px;
  margin: -15px -15px 0 -15px;
}

/* Page Header Panel */
.erp-header-panel {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 24px;
  background: #FFFFFF;
  border-bottom: 1px solid #E1E7EF;
  margin-bottom: 20px;
}
.erp-header-title-block {
  max-width: 65%;
}
.erp-breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #68778A;
  margin-bottom: 6px;
}
.erp-breadcrumb-nav a {
  color: #68778A;
  text-decoration: none;
  transition: color 0.15s;
}
.erp-breadcrumb-nav a:hover {
  color: #243B64;
}
.erp-breadcrumb-nav .sep {
  color: #CBD5E1;
}
.erp-breadcrumb-nav .current {
  color: #243B64;
  font-weight: 600;
}
.erp-main-title {
  font-size: 20px;
  font-weight: 700;
  color: #263445;
  margin: 0 0 4px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.erp-main-title i {
  color: #243B64;
  font-size: 19px;
}
.erp-main-subtitle {
  font-size: 13px;
  color: #68778A;
  margin: 0;
  line-height: 1.4;
}
.erp-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Institutional Button System */
.btn-erp-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background-color: #243B64 !important;
  color: #FFFFFF !important;
  border: 1px solid #1E3152 !important;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  text-decoration: none !important;
  transition: all 0.15s ease;
  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
  cursor: pointer;
}
.btn-erp-primary:hover {
  background-color: #1A2B49 !important;
  border-color: #15233B !important;
  color: #FFFFFF !important;
}

/* User Directory Container */
.erp-userdir-container {
  padding: 0 24px;
}

/* Segmented Role Navigation Bar */
.erp-filter-segment-bar {
  background: #FFFFFF;
  border: 1px solid #E1E7EF;
  border-radius: 8px;
  padding: 12px 18px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
}
.erp-filter-label {
  font-size: 12px;
  font-weight: 700;
  color: #68778A;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.erp-filter-segments {
  display: inline-flex;
  background: #F1F5F9;
  padding: 4px;
  border-radius: 6px;
  gap: 2px;
  flex-wrap: wrap;
}
.erp-segment-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 500;
  color: #475467;
  text-decoration: none !important;
  transition: all 0.15s ease;
  cursor: pointer;
  border: none;
  background: transparent;
}
.erp-segment-btn:hover {
  color: #243B64;
  background-color: rgba(255, 255, 255, 0.6);
}
.erp-segment-btn.active {
  background-color: #243B64 !important;
  color: #FFFFFF !important;
  font-weight: 600;
  box-shadow: 0 1px 3px rgba(16, 24, 40, 0.1);
}

/* Panel Card Container */
.erp-panel-card {
  background: #FFFFFF;
  border: 1px solid #E1E7EF;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
  margin-bottom: 20px;
  overflow: hidden;
}
.erp-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #E1E7EF;
  background: #FFFFFF;
}
.erp-panel-title {
  font-size: 15px;
  font-weight: 700;
  color: #263445;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.erp-panel-title i {
  color: #243B64;
  font-size: 15px;
}
.erp-panel-body {
  padding: 20px;
}

/* Subtle Badges & Status Pills */
.erp-role-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  font-size: 11px;
  font-weight: 600;
  border-radius: 12px;
  line-height: 1.2;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.role-pill-superadmin {
  background-color: #F8F9FC;
  color: #243B64;
  border: 1px solid #D0D5DD;
}
.role-pill-admin {
  background-color: #EFF8FF;
  color: #175CD3;
  border: 1px solid #B2DDFF;
}
.role-pill-coordinator {
  background-color: #FFFAEB;
  color: #B54708;
  border: 1px solid #FEDF89;
}
.role-pill-mentor {
  background-color: #ECFDF3;
  color: #027A48;
  border: 1px solid #A6F4C5;
}
.erp-protected-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 600;
  border-radius: 4px;
  background-color: #F1F5F9;
  color: #64748B;
  border: 1px solid #CBD5E1;
}

/* Action Button */
.btn-erp-table-action {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 4px;
  background-color: #FFFFFF;
  cursor: pointer;
  text-decoration: none !important;
  transition: all 0.15s ease;
}
.btn-erp-action-danger {
  color: #B42318;
  border: 1px solid #FECDCA;
}
.btn-erp-action-danger:hover {
  background-color: #FEF3F2;
  border-color: #F97066;
  color: #912018;
}

/* DataTables Institutional Enhancements */
#userRoleTable_wrapper {
  padding: 16px 20px;
}
#userRoleTable_wrapper .dataTables_length,
#userRoleTable_wrapper .dataTables_filter {
  margin-bottom: 16px;
}
#userRoleTable_wrapper .dataTables_length label,
#userRoleTable_wrapper .dataTables_filter label {
  font-size: 13px;
  font-weight: 500;
  color: #68778A;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
#userRoleTable_wrapper .dataTables_length select,
#userRoleTable_wrapper .dataTables_filter input {
  border: 1px solid #D0D5DD;
  border-radius: 6px;
  padding: 6px 10px;
  font-size: 13px;
  color: #263445;
  background-color: #FFFFFF;
  outline: none;
  transition: border-color 0.15s;
}
#userRoleTable_wrapper .dataTables_length select:focus,
#userRoleTable_wrapper .dataTables_filter input:focus {
  border-color: #315A91;
  box-shadow: 0 0 0 3px rgba(49, 90, 145, 0.15);
}
#userRoleTable_wrapper .dataTables_info {
  font-size: 13px;
  color: #68778A;
  padding-top: 14px;
}
#userRoleTable_wrapper .dataTables_paginate {
  padding-top: 12px;
}
#userRoleTable_wrapper .dataTables_paginate .paginate_button {
  border-radius: 4px !important;
  border: 1px solid #E1E7EF !important;
  background: #FFFFFF !important;
  color: #263445 !important;
  font-size: 12px !important;
  font-weight: 500 !important;
  padding: 4px 10px !important;
  margin: 0 2px !important;
  transition: all 0.15s ease !important;
}
#userRoleTable_wrapper .dataTables_paginate .paginate_button:hover {
  background: #F8FAFC !important;
  border-color: #CBD5E1 !important;
  color: #243B64 !important;
}
#userRoleTable_wrapper .dataTables_paginate .paginate_button.current,
#userRoleTable_wrapper .dataTables_paginate .paginate_button.current:hover {
  background: #243B64 !important;
  border-color: #1E3152 !important;
  color: #FFFFFF !important;
  font-weight: 600 !important;
}

/* Data Table Columns Styling */
#userRoleTable {
  border-collapse: collapse !important;
  width: 100% !important;
  margin-top: 0 !important;
  margin-bottom: 0 !important;
  border: 1px solid #E1E7EF;
}
#userRoleTable thead th {
  background-color: #243B64 !important;
  color: #FFFFFF !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.4px !important;
  padding: 12px 14px !important;
  border-bottom: none !important;
  border-top: none !important;
}
#userRoleTable thead th.sorting:after,
#userRoleTable thead th.sorting_asc:after,
#userRoleTable thead th.sorting_desc:after {
  color: rgba(255, 255, 255, 0.7) !important;
}
#userRoleTable tbody td {
  padding: 12px 14px !important;
  font-size: 13px !important;
  color: #263445 !important;
  border-top: 1px solid #E1E7EF !important;
  border-bottom: none !important;
  vertical-align: middle !important;
}
#userRoleTable tbody tr:hover {
  background-color: #F8FAFC !important;
}

/* Responsive adjustment */
@media (max-width: 768px) {
  .erp-header-panel {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .erp-header-title-block {
    max-width: 100%;
  }
  .erp-filter-segment-bar {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

<div class="content-wrapper" style="padding: 0; background-color: #F5F7FA;">
  <div class="erp-userdir-wrap">
    <!-- Institutional Page Header -->
    <div class="erp-header-panel">
      <div class="erp-header-title-block">
        <div class="erp-breadcrumb-nav">
          <a href="index.php"><i class="fa fa-home"></i> Home</a>
          <span class="sep">/</span>
          <span class="current">User Directory</span>
        </div>
        <h1 class="erp-main-title"><i class="fa fa-users"></i> User Directory</h1>
        <p class="erp-main-subtitle">Manage administrative and academic user accounts.</p>
      </div>
      <div class="erp-header-actions">
        <a href="user_register.php?role=<?php echo urlencode($roleKey === 'all' ? 'admin' : $roleKey); ?>" class="btn-erp-primary">
          <i class="fa fa-user-plus"></i> Register User
        </a>
      </div>
    </div>

    <div class="erp-userdir-container">
      <!-- Role Filters (Segmented Navigation) -->
      <div class="erp-filter-segment-bar">
        <div class="erp-filter-label">
          <i class="fa fa-filter text-navy"></i> Role Filter:
        </div>
        <div class="erp-filter-segments">
          <a href="user-info.php?role=all" class="erp-segment-btn <?php echo $roleKey === 'all' ? 'active' : ''; ?>">
            <i class="fa fa-th-list"></i> All Users
          </a>
          <a href="user-info.php?role=superadmin" class="erp-segment-btn <?php echo $roleKey === 'superadmin' ? 'active' : ''; ?>">
            <i class="fa fa-shield"></i> Super Admin
          </a>
          <a href="user-info.php?role=admin" class="erp-segment-btn <?php echo $roleKey === 'admin' ? 'active' : ''; ?>">
            <i class="fa fa-user-circle"></i> Admin
          </a>
          <a href="user-info.php?role=coordinator" class="erp-segment-btn <?php echo $roleKey === 'coordinator' ? 'active' : ''; ?>">
            <i class="fa fa-user-tie"></i> Coordinator / HOD
          </a>
          <a href="user-info.php?role=mentor" class="erp-segment-btn <?php echo $roleKey === 'mentor' ? 'active' : ''; ?>">
            <i class="fa fa-users"></i> Mentor
          </a>
        </div>
      </div>

      <!-- Directory Card with Data Table -->
      <div class="erp-panel-card">
        <div class="erp-panel-header">
          <h3 class="erp-panel-title">
            <i class="fa fa-address-book-o"></i> Directory Accounts &bull; <?php echo htmlspecialchars($roleLabel); ?>
          </h3>
          <span style="font-size: 12px; color: #68778A;">Server-side paginated &amp; searchable</span>
        </div>
        <div class="erp-panel-body" style="padding: 0;">
          <div class="table-responsive" style="overflow-x: auto;">
            <table id="userRoleTable" class="table text-left" width="100%">
              <thead>
                <tr>
                  <th style="width: 60px; text-align: center;">Sr. No</th>
                  <th style="text-align: left;">Name</th>
                  <th style="text-align: left;">Email</th>
                  <th style="text-align: left;">Phone</th>
                  <th style="text-align: left;">Department</th>
                  <th style="text-align: center;">Role</th>
                  <th style="width: 100px; text-align: center;">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  var roleId = <?php echo intval($roleId); ?>;

  if ($.fn.dataTable) {
    $.fn.dataTable.ext.errMode = 'console';
  }

  $('#userRoleTable').DataTable({
    processing: true,
    serverSide: true,
    pageLength: 15,
    order: [[0, 'asc']],
    ajax: {
      url: 'user_info_ajax.php',
      type: 'POST',
      data: { role_id: roleId },
      error: function(xhr, err, thrown) {
        console.error('DataTables error:', err, thrown, xhr.responseText);
      }
    },
    columnDefs: [
      { orderable: false, targets: [6] },
      { className: 'text-center', targets: [0, 5, 6] },
      { className: 'text-left', targets: [1, 2, 3, 4] }
    ],
    language: {
      search: "_INPUT_",
      searchPlaceholder: "Search users...",
      lengthMenu: "Show _MENU_ entries",
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      paginate: {
        previous: "<i class='fa fa-angle-left'></i>",
        next: "<i class='fa fa-angle-right'></i>"
      }
    }
  });
});

function deleteUser(userId, roleKey) {
  if (!confirm('Are you sure you want to remove this user record?')) {
    return;
  }

  $.ajax({
    url: 'user_delete.php',
    type: 'POST',
    dataType: 'json',
    data: { user_id: userId },
    success: function(resp) {
      if (resp.success) {
        window.location.href = 'user-info.php?role=' + encodeURIComponent(roleKey);
      } else {
        alert(resp.message || 'Delete operation failed.');
      }
    },
    error: function() {
      alert('An error occurred while deleting the user account.');
    }
  });
}
</script>
<?php include "header/footer.php"; ?>
