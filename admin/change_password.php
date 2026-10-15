<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
include "header/header.php";

$passwordAlertType = '';
$passwordAlertMessage = '';

$currentUserId = intval($userid ?? 0);
$currentRoleId = intval($usertype ?? 0);
$closeRoute = ($currentRoleId === 5) ? 'student_dashboard.php' : 'index.php';

if ($currentUserId <= 0 || $currentRoleId <= 0) {
  $passwordAlertType = 'danger';
  $passwordAlertMessage = 'Unable to load password form. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password']) && $currentUserId > 0 && $currentRoleId > 0) {
  if (!DBController::validateCsrfToken()) {
    $passwordAlertType = 'danger';
    $passwordAlertMessage = 'Invalid security token (CSRF). Please refresh and try again.';
  } else {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
      $passwordAlertType = 'warning';
      $passwordAlertMessage = 'All password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
      $passwordAlertType = 'warning';
      $passwordAlertMessage = 'New password and confirm password must match.';
    } elseif (strlen($newPassword) < 6) {
      $passwordAlertType = 'warning';
      $passwordAlertMessage = 'New password must be at least 6 characters long.';
    } else {
        $loginSql = "SELECT password FROM st_login WHERE user_id = ? LIMIT 1";
      $loginStmt = mysqli_prepare($db_handle->conn, $loginSql);

      if ($loginStmt) {
        mysqli_stmt_bind_param($loginStmt, 'i', $currentUserId);
        mysqli_stmt_execute($loginStmt);
        $loginResult = mysqli_stmt_get_result($loginStmt);
        $loginRow = $loginResult ? mysqli_fetch_assoc($loginResult) : null;
        mysqli_stmt_close($loginStmt);

        $storedPassword = (string) ($loginRow['password'] ?? '');
        if (!DBController::verifyPassword($currentPassword, $storedPassword)) {
          $passwordAlertType = 'danger';
          $passwordAlertMessage = 'Current password is incorrect.';
        } else {
          $hashedNewPassword = DBController::hashPassword($newPassword);
          $updateSql = "UPDATE st_login SET password = ? WHERE user_id = ?";
          $updateStmt = mysqli_prepare($db_handle->conn, $updateSql);
          if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, 'si', $hashedNewPassword, $currentUserId);
            if (mysqli_stmt_execute($updateStmt)) {
              // Mark first login as completed
              $updFirst = mysqli_prepare($db_handle->conn, "UPDATE st_user_master SET is_first_login = 0 WHERE user_id = ?");
              if ($updFirst) {
                mysqli_stmt_bind_param($updFirst, 'i', $currentUserId);
                mysqli_stmt_execute($updFirst);
                mysqli_stmt_close($updFirst);
              }

              if (method_exists($db_handle, 'writeAuditLog')) {
                $db_handle->writeAuditLog($currentUserId, 'PASSWORD_CHANGE', 'st_login', $currentUserId, "User updated their password");
              }

              $passwordAlertType = 'success';
              $passwordAlertMessage = 'Password updated successfully.';
            } else {
              $passwordAlertType = 'danger';
              $passwordAlertMessage = 'Unable to update password right now.';
            }
            mysqli_stmt_close($updateStmt);
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
}

$passwordData = array(
  'username' => (string) ($username ?? ''),
  'role_name' => (string) ($role_name ?? '')
);

if ($currentUserId > 0 && $currentRoleId > 0) {
    $profileSql = "SELECT l.username, r.role_name
                   FROM st_login l
                   LEFT JOIN st_user_master u ON u.user_id = l.user_id
                   LEFT JOIN st_role_master r ON r.role_id = u.role_id
                   WHERE l.user_id = ?
                   LIMIT 1";
  $profileStmt = mysqli_prepare($db_handle->conn, $profileSql);

  if ($profileStmt) {
      mysqli_stmt_bind_param($profileStmt, 'i', $currentUserId);
    mysqli_stmt_execute($profileStmt);
    $profileResult = mysqli_stmt_get_result($profileStmt);
    if ($profileResult && ($row = mysqli_fetch_assoc($profileResult))) {
      $passwordData['username'] = (string) ($row['username'] ?? $passwordData['username']);
      $passwordData['role_name'] = (string) ($row['role_name'] ?? $passwordData['role_name']);
    }
    mysqli_stmt_close($profileStmt);
  }
}
?>

<style>
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
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .profile-form-card .box-title {
    color: var(--erp-text-main, #0f172a);
    font-size: 14px;
    font-weight: 700;
  }

  .profile-form-card .profile-close-icon {
    width: 28px;
    height: 28px;
    border-radius: var(--erp-radius-sm, 4px);
    color: var(--erp-text-muted, #64748b);
    background: #ffffff;
    border: 1px solid var(--erp-border, #e2e8f0);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.15s ease;
  }

  .profile-form-card .profile-close-icon:hover,
  .profile-form-card .profile-close-icon:focus {
    color: var(--erp-text-main, #0f172a);
    background: #f1f5f9;
    text-decoration: none;
  }

  .profile-form-card .box-body {
    padding: 16px;
    background: #fff;
  }

  .profile-form-card .control-label {
    color: var(--erp-text-secondary, #475569);
    font-size: 12px;
    font-weight: 600;
    padding-top: 8px;
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

  .profile-input-group .input-group-btn .btn {
    height: 36px;
    border: 1px solid var(--erp-border-dark, #cbd5e1);
    border-left: 0;
    border-radius: 0 var(--erp-radius-sm, 4px) var(--erp-radius-sm, 4px) 0;
    background: #f8fafc;
    color: var(--erp-text-muted, #64748b);
  }

  .profile-input-group .input-group-btn .btn:focus {
    outline: none;
    box-shadow: none;
  }

  .profile-input-group .form-control {
    height: 36px;
    border-radius: 0;
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
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    position: relative;
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

  .profile-form-card .profile-close-btn {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--erp-border-dark, #cbd5e1);
    border-radius: var(--erp-radius-sm, 4px);
    padding: 6px 12px;
    background: #ffffff;
    color: var(--erp-text-secondary, #475569);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
  }

  .profile-form-card .profile-close-btn:hover,
  .profile-form-card .profile-close-btn:focus {
    background: #f8fafc;
    color: var(--erp-text-main, #0f172a);
    text-decoration: none;
  }
</style>

<div class="content-wrapper">
  <section class="content-header">
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Update Password</li>
    </ol>
  </section>

  <section class="content" style="margin-top: 20px;">
    <div class="row">
      <div class="col-md-3"></div>
      <div class="col-md-6">
        <div class="box profile-form-card">
          <div class="box-header with-border">
            <h3 class="box-title">Update Password</h3>
            <a href="<?php echo htmlspecialchars($closeRoute); ?>" class="profile-close-icon" aria-label="Close update password">
              <i class="fa fa-times"></i>
            </a>
          </div>

          <div class="box-body" style="padding-bottom: 0;">
            <p style="margin-bottom: 10px; color: #374151; font-weight: 600;">User: <?php echo htmlspecialchars($passwordData['username']); ?></p>
            <p style="margin-bottom: 0; color: #6b7280;">Role: <?php echo htmlspecialchars($passwordData['role_name']); ?></p>
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
            <?php echo DBController::getCsrfInputField(); ?>
            <div class="box-body">
              <div class="form-group">
                <label class="col-sm-4 control-label">Current Password</label>
                <div class="col-sm-8">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                    <input type="password" class="form-control password-toggle-field" id="current_password" name="current_password" placeholder="Enter current password" required>
                    <span class="input-group-btn">
                      <button type="button" class="btn btn-default toggle-password" data-target="current_password" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-4 control-label">New Password</label>
                <div class="col-sm-8">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                    <input type="password" class="form-control password-toggle-field" id="new_password" name="new_password" placeholder="Enter new password" required>
                    <span class="input-group-btn">
                      <button type="button" class="btn btn-default toggle-password" data-target="new_password" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-4 control-label">Confirm Password</label>
                <div class="col-sm-8">
                  <div class="input-group profile-input-group">
                    <span class="input-group-addon"><i class="fa fa-check"></i></span>
                    <input type="password" class="form-control password-toggle-field" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                    <span class="input-group-btn">
                      <button type="button" class="btn btn-default toggle-password" data-target="confirm_password" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="box-footer">
              <input type="hidden" name="update_password" value="1">
              <a href="<?php echo htmlspecialchars($closeRoute); ?>" class="profile-close-btn"><i class="fa fa-times"></i> Close</a>
              <button type="submit" class="profile-save-btn"><i class="fa fa-refresh"></i> Update Password</button>
            </div>
          </form>
        </div>
      </div>
      <div class="col-md-3"></div>
    </div>
  </section>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var toggleButtons = document.querySelectorAll('.toggle-password');
    for (var i = 0; i < toggleButtons.length; i++) {
      toggleButtons[i].addEventListener('click', function () {
        var targetId = this.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) {
          return;
        }

        var icon = this.querySelector('i');
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        if (icon) {
          icon.className = isPassword ? 'fa fa-eye-slash' : 'fa fa-eye';
        }
        this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      });
    }
  });
</script>

<?php include "header/footer.php"; ?>
