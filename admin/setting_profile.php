<?php header("Location: profile.php"); exit; ?>
<?php
$profileAlertType = '';
$profileAlertMessage = '';
$passwordAlertType = '';
$passwordAlertMessage = '';

$currentUserId = intval($userid ?? 0);
$currentRoleId = intval($usertype ?? 0);

if ($currentUserId <= 0 || $currentRoleId <= 0) {
  $profileAlertType = 'danger';
  $profileAlertMessage = 'Unable to load profile. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $currentUserId > 0 && $currentRoleId > 0) {
  $profileName = trim((string) ($_POST['profile_name'] ?? ''));
  $profileEmail = trim((string) ($_POST['profile_email'] ?? ''));
  $profilePhone = trim((string) ($_POST['profile_phone'] ?? ''));

  if ($profileName === '') {
    $profileAlertType = 'warning';
    $profileAlertMessage = 'Name is required.';
  } elseif ($profileEmail !== '' && !filter_var($profileEmail, FILTER_VALIDATE_EMAIL)) {
    $profileAlertType = 'warning';
    $profileAlertMessage = 'Please enter a valid email address.';
  } else {
    mysqli_begin_transaction($db_handle->conn);
    $ok = true;

      $loginUpdateSql = "UPDATE st_login SET username = ? WHERE user_id = ?";
    $loginStmt = mysqli_prepare($db_handle->conn, $loginUpdateSql);
    if ($loginStmt) {
        mysqli_stmt_bind_param($loginStmt, 'si', $profileName, $currentUserId);
      $ok = $ok && mysqli_stmt_execute($loginStmt);
      mysqli_stmt_close($loginStmt);
    } else {
      $ok = false;
    }

    if ($ok) {
        $profileCheckSql = "SELECT user_id FROM st_user_master WHERE user_id = ? LIMIT 1";
      $profileCheckStmt = mysqli_prepare($db_handle->conn, $profileCheckSql);
      if ($profileCheckStmt) {
          mysqli_stmt_bind_param($profileCheckStmt, 'i', $currentUserId);
        mysqli_stmt_execute($profileCheckStmt);
        $profileCheckResult = mysqli_stmt_get_result($profileCheckStmt);
        $profileExists = ($profileCheckResult && mysqli_num_rows($profileCheckResult) > 0);
        mysqli_stmt_close($profileCheckStmt);

        if ($profileExists) {
            $profileUpdateSql = "UPDATE st_user_master SET user_name = ?, email_id = ?, phone_number = ? WHERE user_id = ?";
          $profileUpdateStmt = mysqli_prepare($db_handle->conn, $profileUpdateSql);
          if ($profileUpdateStmt) {
              mysqli_stmt_bind_param($profileUpdateStmt, 'sssi', $profileName, $profileEmail, $profilePhone, $currentUserId);
            $ok = $ok && mysqli_stmt_execute($profileUpdateStmt);
            mysqli_stmt_close($profileUpdateStmt);
          } else {
            $ok = false;
          }
        } else {
          $profileInsertSql = "INSERT INTO st_user_master (user_id, user_name, email_id, phone_number, department_id, role_id, student_id) VALUES (?, ?, ?, ?, 0, ?, 0)";
          $profileInsertStmt = mysqli_prepare($db_handle->conn, $profileInsertSql);
          if ($profileInsertStmt) {
            mysqli_stmt_bind_param($profileInsertStmt, 'isssi', $currentUserId, $profileName, $profileEmail, $profilePhone, $currentRoleId);
            $ok = $ok && mysqli_stmt_execute($profileInsertStmt);
            mysqli_stmt_close($profileInsertStmt);
          } else {
            $ok = false;
          }
        }
      } else {
        $ok = false;
      }
    }

    if ($ok) {
      mysqli_commit($db_handle->conn);
      $profileAlertType = 'success';
      $profileAlertMessage = 'Profile updated successfully.';
      $username = $profileName;
      $name = $profileName;
    } else {
      mysqli_rollback($db_handle->conn);
      $profileAlertType = 'danger';
      $profileAlertMessage = 'Unable to update profile right now.';
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password']) && $currentUserId > 0 && $currentRoleId > 0) {
  $currentPassword = trim((string) ($_POST['current_password'] ?? ''));
  $newPassword = trim((string) ($_POST['new_password'] ?? ''));
  $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));

  if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    $passwordAlertType = 'warning';
    $passwordAlertMessage = 'All password fields are required.';
  } elseif ($newPassword !== $confirmPassword) {
    $passwordAlertType = 'warning';
    $passwordAlertMessage = 'New password and confirm password must match.';
  } else {
      $currentPasswordSql = "SELECT password FROM st_login WHERE user_id = ? LIMIT 1";
    $currentPasswordStmt = mysqli_prepare($db_handle->conn, $currentPasswordSql);
    if ($currentPasswordStmt) {
        mysqli_stmt_bind_param($currentPasswordStmt, 'i', $currentUserId);
      mysqli_stmt_execute($currentPasswordStmt);
      $currentPasswordResult = mysqli_stmt_get_result($currentPasswordStmt);
      $currentPasswordRow = $currentPasswordResult ? mysqli_fetch_assoc($currentPasswordResult) : null;
      mysqli_stmt_close($currentPasswordStmt);

      if (!$currentPasswordRow || (string) ($currentPasswordRow['password'] ?? '') !== $currentPassword) {
        $passwordAlertType = 'danger';
        $passwordAlertMessage = 'Current password is incorrect.';
      } else {
          $updatePasswordSql = "UPDATE st_login SET password = ? WHERE user_id = ?";
        $updatePasswordStmt = mysqli_prepare($db_handle->conn, $updatePasswordSql);
        if ($updatePasswordStmt) {
            mysqli_stmt_bind_param($updatePasswordStmt, 'si', $newPassword, $currentUserId);
          if (mysqli_stmt_execute($updatePasswordStmt)) {
            $passwordAlertType = 'success';
            $passwordAlertMessage = 'Password updated successfully.';
          } else {
            $passwordAlertType = 'danger';
            $passwordAlertMessage = 'Unable to update password right now.';
          }
          mysqli_stmt_close($updatePasswordStmt);
        } else {
          $passwordAlertType = 'danger';
          $passwordAlertMessage = 'Unable to prepare password update.';
        }
      }
    } else {
      $passwordAlertType = 'danger';
      $passwordAlertMessage = 'Unable to verify current password.';
    }
  }
}

$profileData = array(
  'username' => (string) ($username ?? ''),
  'role_name' => (string) ($role_name ?? ''),
  'email_id' => '',
  'phone_number' => '',
  'department_name' => ''
);

if ($currentUserId > 0 && $currentRoleId > 0) {
    $profileSql = "SELECT l.username, r.role_name, u.user_name, u.email_id, u.phone_number, d.department_name
                   FROM st_login l
                   LEFT JOIN st_user_master u ON u.user_id = l.user_id
                   LEFT JOIN st_role_master r ON r.role_id = u.role_id
                   LEFT JOIN st_department_master d ON d.department_id = u.department_id
                   WHERE l.user_id = ?
                   LIMIT 1";
  $profileStmt = mysqli_prepare($db_handle->conn, $profileSql);

  if ($profileStmt) {
      mysqli_stmt_bind_param($profileStmt, 'i', $currentUserId);
    mysqli_stmt_execute($profileStmt);
    $profileResult = mysqli_stmt_get_result($profileStmt);
    if ($profileResult && ($row = mysqli_fetch_assoc($profileResult))) {
      $displayName = trim((string) ($row['user_name'] ?? ''));
      if ($displayName === '') {
        $displayName = (string) ($row['username'] ?? '');
      }
      $profileData['username'] = $displayName;
      $profileData['role_name'] = (string) ($row['role_name'] ?? $profileData['role_name']);
      $profileData['email_id'] = (string) ($row['email_id'] ?? '');
      $profileData['phone_number'] = (string) ($row['phone_number'] ?? '');
      $profileData['department_name'] = (string) ($row['department_name'] ?? '');
    }
    mysqli_stmt_close($profileStmt);
  }
}
?>

<style>
  .profile-summary-card {
    border: 1px solid var(--erp-border, #e2e8f0);
    border-radius: var(--erp-radius-md, 6px);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    background: #ffffff;
  }

  .profile-summary-head {
    height: 60px;
    background: var(--erp-primary, #423cbc);
  }

  .profile-summary-body {
    margin-top: -36px;
    padding: 0 18px 18px;
    text-align: center;
  }

  .profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    object-fit: cover;
  }

  .profile-display-name {
    margin: 10px 0 4px;
    font-size: 20px;
    font-weight: 700;
    color: var(--erp-text-main, #0f172a);
  }

  .profile-role-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 12px;
    background: var(--erp-primary-light, #eef2ff);
    color: var(--erp-primary, #423cbc);
    border: 1px solid var(--erp-primary-border, #c7d2fe);
    font-weight: 600;
    font-size: 11px;
    margin-bottom: 14px;
    text-transform: uppercase;
  }

  .profile-meta-box {
    border: 1px solid var(--erp-border, #e2e8f0);
    border-radius: var(--erp-radius-sm, 4px);
    overflow: hidden;
    text-align: left;
  }

  .profile-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 12px;
    border-bottom: 1px solid var(--erp-border, #e2e8f0);
    background: #fff;
    font-size: 12px;
  }

  .profile-meta-row:last-child {
    border-bottom: 0;
  }

  .profile-meta-label {
    color: var(--erp-text-secondary, #475569);
    font-weight: 600;
  }

  .profile-meta-value {
    color: var(--erp-text-main, #0f172a);
    font-weight: 500;
    text-align: right;
  }

  .profile-online-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    font-weight: 600;
    font-size: 11px;
  }

  .profile-form-card {
    border: 1px solid var(--erp-border, #e2e8f0);
    border-radius: var(--erp-radius-md, 6px);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    background: #ffffff;
  }

  .profile-form-card .box-header {
    background: #ffffff;
    border-bottom: 1px solid var(--erp-border, #e2e8f0);
    padding: 12px 16px;
  }

  .profile-form-card .box-title {
    color: var(--erp-text-main, #0f172a);
    font-size: 14px;
    font-weight: 700;
  }

  .profile-form-card .box-body {
    padding: 16px;
    background: #fff;
  }

  .profile-form-card .control-label {
    color: var(--erp-text-secondary, #475569);
    font-size: 12px;
    font-weight: 600;
  }

  .profile-input-group .input-group-addon {
    border-radius: var(--erp-radius-sm, 4px) 0 0 var(--erp-radius-sm, 4px);
    border: 1px solid var(--erp-border-dark, #cbd5e1);
    border-right: 0;
    background: #f8fafc;
    color: var(--erp-text-muted, #64748b);
    min-width: 38px;
    text-align: center;
  }

  .profile-input-group .form-control {
    height: 36px;
    border-radius: 0 var(--erp-radius-sm, 4px) var(--erp-radius-sm, 4px) 0;
    border: 1px solid var(--erp-border-dark, #cbd5e1);
    box-shadow: none;
  }

  .profile-input-group .form-control:focus {
    border-color: var(--erp-primary, #423cbc);
    box-shadow: 0 0 0 3px rgba(66, 60, 188, 0.12);
  }

  .profile-form-card .box-footer {
    border-top: 1px solid var(--erp-border, #e2e8f0);
    background: #f8fafc;
    padding: 12px 16px;
  }

  .profile-form-card .profile-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid var(--erp-primary-hover, #352fa1);
    border-radius: var(--erp-radius-sm, 4px);
    padding: 6px 14px;
    background: var(--erp-primary, #423cbc) !important;
    color: #fff !important;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(66, 60, 188, 0.2);
  }

  .profile-form-card .profile-save-btn:hover,
  .profile-form-card .profile-save-btn:focus {
    background: var(--erp-primary-hover, #352fa1) !important;
    color: #fff !important;
  }
</style>

<div class="content-wrapper">
  <section class="content-header">
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Profile</li>
    </ol>
  </section>

  <section class="content" style="margin-top: 20px;">
    <div id="profile"></div>
    <div class="row">
      <div class="col-md-4">
        <div class="box profile-summary-card">
          <div class="profile-summary-head"></div>
          <div class="box-body box-profile text-center profile-summary-body">
            <img class="profile-avatar" src="dist/img/user2-160x160.jpg" alt="User profile picture">
            <h3 class="profile-display-name text-center"><?php echo htmlspecialchars($profileData['username']); ?></h3>
            <div class="profile-role-pill"><?php echo htmlspecialchars($profileData['role_name']); ?></div>

            <div class="profile-meta-box">
              <div class="profile-meta-row">
                <span class="profile-meta-label">User ID</span>
                <span class="profile-meta-value"><?php echo intval($currentUserId); ?></span>
              </div>
              <div class="profile-meta-row">
                <span class="profile-meta-label">Department</span>
                <span class="profile-meta-value"><?php echo htmlspecialchars($profileData['department_name'] !== '' ? $profileData['department_name'] : 'N/A'); ?></span>
              </div>
              <div class="profile-meta-row">
                <span class="profile-meta-label">Status</span>
                <span class="profile-meta-value"><span class="profile-online-pill">Online</span></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="box profile-form-card">
          <div class="box-header with-border">
            <h3 class="box-title">Profile Details</h3>
          </div>

          <?php if ($profileAlertMessage !== '') { ?>
            <div class="box-body" style="padding-bottom:0;">
              <div class="alert alert-<?php echo htmlspecialchars($profileAlertType); ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php echo htmlspecialchars($profileAlertMessage); ?>
              </div>
            </div>
          <?php } ?>

          <form class="form-horizontal" method="POST">
            <div class="box-body">
              <div class="form-group">
                <label class="col-sm-3 control-label">Name</label>
                <div class="col-sm-9">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-user"></i></span>
                    <input type="text" class="form-control" name="profile_name" value="<?php echo htmlspecialchars($profileData['username']); ?>" required>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Role</label>
                <div class="col-sm-9">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-shield"></i></span>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($profileData['role_name']); ?>" readonly>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Email</label>
                <div class="col-sm-9">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                    <input type="email" class="form-control" name="profile_email" value="<?php echo htmlspecialchars($profileData['email_id']); ?>" placeholder="Enter email">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Phone</label>
                <div class="col-sm-9">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                    <input type="text" class="form-control" name="profile_phone" value="<?php echo htmlspecialchars($profileData['phone_number']); ?>" placeholder="Enter phone number">
                  </div>
                </div>
              </div>
            </div>
            <div class="box-footer">
              <input type="hidden" name="update_profile" value="1">
              <button type="submit" class="pull-right profile-save-btn"><i class="fa fa-check"></i> Update Profile</button>
            </div>
          </form>
        </div>

        <div id="password" style="margin-top: 20px;"></div>
        <div class="box profile-form-card">
          <div class="box-header with-border">
            <h3 class="box-title">Update Password</h3>
          </div>

          <?php if ($passwordAlertMessage !== '') { ?>
            <div class="box-body" style="padding-bottom:0;">
              <div class="alert alert-<?php echo htmlspecialchars($passwordAlertType); ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php echo htmlspecialchars($passwordAlertMessage); ?>
              </div>
            </div>
          <?php } ?>

          <form class="form-horizontal" method="POST">
            <div class="box-body">
              <div class="form-group">
                <label class="col-sm-3 control-label">Current Password</label>
                <div class="col-sm-9">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                    <input type="password" class="form-control" name="current_password" placeholder="Enter current password" required>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">New Password</label>
                <div class="col-sm-9">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                    <input type="password" class="form-control" name="new_password" placeholder="Enter new password" required>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Confirm Password</label>
                <div class="col-sm-9">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-check"></i></span>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Re-enter new password" required>
                  </div>
                </div>
              </div>
            </div>
            <div class="box-footer">
              <input type="hidden" name="update_password" value="1">
              <button type="submit" class="pull-right profile-save-btn"><i class="fa fa-refresh"></i> Update Password</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include "header/footer.php"; ?>
