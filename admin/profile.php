<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
include "header/header.php";

$profileAlertType = '';
$profileAlertMessage = '';

$currentUserId = intval($userid ?? 0);
$currentRoleId = intval($usertype ?? 0);

$defaultProfilePhoto = 'dist/img/user2-160x160.jpg';
$profilePhotoStorageDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_photos';
$profilePhotoWebDir = 'uploads/profile_photos';

function getUserProfilePhotoWebPath($userId, $storageDir, $webDir, $defaultPath)
{
  $userId = intval($userId);
  if ($userId <= 0 || !is_dir($storageDir)) {
    return $defaultPath;
  }

  $matches = glob($storageDir . DIRECTORY_SEPARATOR . 'user_' . $userId . '.*');
  if (!$matches || count($matches) === 0) {
    return $defaultPath;
  }

  return $webDir . '/' . basename($matches[0]);
}

$profilePhotoWebPath = getUserProfilePhotoWebPath($currentUserId, $profilePhotoStorageDir, $profilePhotoWebDir, $defaultProfilePhoto);

if ($currentUserId <= 0 || $currentRoleId <= 0) {
  $profileAlertType = 'danger';
  $profileAlertMessage = 'Unable to load profile. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $currentUserId > 0 && $currentRoleId > 0) {
  if (!DBController::validateCsrfToken()) {
    $profileAlertType = 'danger';
    $profileAlertMessage = 'Security validation failed. Please refresh and try again.';
  } else {
    $profileName = trim((string) ($_POST['profile_name'] ?? ''));
  $profileEmail = trim((string) ($_POST['profile_email'] ?? ''));
  $profilePhone = trim((string) ($_POST['profile_phone'] ?? ''));
  $hasPhotoUpload = isset($_FILES['profile_photo']) && is_array($_FILES['profile_photo']) && intval($_FILES['profile_photo']['error'] ?? 4) !== 4;
  $uploadedPhotoTmpPath = '';
  $uploadedPhotoExt = '';

  if ($profileName === '') {
    $profileAlertType = 'warning';
    $profileAlertMessage = 'Name is required.';
  } elseif ($profileEmail !== '' && !filter_var($profileEmail, FILTER_VALIDATE_EMAIL)) {
    $profileAlertType = 'warning';
    $profileAlertMessage = 'Please enter a valid email address.';
  } elseif ($hasPhotoUpload) {
    $photoErrorCode = intval($_FILES['profile_photo']['error'] ?? 1);
    if ($photoErrorCode !== 0) {
      $profileAlertType = 'warning';
      $profileAlertMessage = 'Unable to upload photo. Please try again.';
    } else {
      $uploadedPhotoTmpPath = (string) ($_FILES['profile_photo']['tmp_name'] ?? '');
      $uploadedPhotoSize = intval($_FILES['profile_photo']['size'] ?? 0);
      $uploadedPhotoName = (string) ($_FILES['profile_photo']['name'] ?? '');
      $uploadedPhotoExt = strtolower(pathinfo($uploadedPhotoName, PATHINFO_EXTENSION));
      $allowedPhotoExt = array('jpg', 'jpeg', 'png', 'webp');

      if ($uploadedPhotoTmpPath === '' || !is_uploaded_file($uploadedPhotoTmpPath)) {
        $profileAlertType = 'warning';
        $profileAlertMessage = 'Invalid photo upload request.';
      } elseif (!in_array($uploadedPhotoExt, $allowedPhotoExt, true)) {
        $profileAlertType = 'warning';
        $profileAlertMessage = 'Profile photo must be JPG, PNG, or WEBP format.';
      } elseif ($uploadedPhotoSize <= 0 || $uploadedPhotoSize > 2 * 1024 * 1024) {
        $profileAlertType = 'warning';
        $profileAlertMessage = 'Profile photo size must be less than 2 MB.';
      }
    }
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

      if ($hasPhotoUpload && $uploadedPhotoTmpPath !== '' && $uploadedPhotoExt !== '') {
        if (!is_dir($profilePhotoStorageDir)) {
          @mkdir($profilePhotoStorageDir, 0777, true);
        }

        if (is_dir($profilePhotoStorageDir)) {
          $existingPhotos = glob($profilePhotoStorageDir . DIRECTORY_SEPARATOR . 'user_' . intval($currentUserId) . '.*');
          if ($existingPhotos) {
            foreach ($existingPhotos as $existingPhotoPath) {
              @unlink($existingPhotoPath);
            }
          }

          $targetPhotoFilename = 'user_' . intval($currentUserId) . '.' . $uploadedPhotoExt;
          $targetPhotoPath = $profilePhotoStorageDir . DIRECTORY_SEPARATOR . $targetPhotoFilename;
          if (move_uploaded_file($uploadedPhotoTmpPath, $targetPhotoPath)) {
            $profilePhotoWebPath = $profilePhotoWebDir . '/' . $targetPhotoFilename;
          } else {
            $profileAlertType = 'warning';
            $profileAlertMessage = 'Profile updated, but photo upload failed.';
          }
        } else {
          $profileAlertType = 'warning';
          $profileAlertMessage = 'Profile updated, but photo storage is not available.';
        }
      }
    } else {
      mysqli_rollback($db_handle->conn);
      $profileAlertType = 'danger';
      $profileAlertMessage = 'Unable to update profile right now.';
    }
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

  .profile-avatar-wrap {
    width: 86px;
    margin: 0 auto;
    position: relative;
  }

  .profile-avatar-edit-tag {
    position: absolute;
    right: 0px;
    bottom: 0px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--erp-primary, #423cbc);
    color: #fff;
    border: 2px solid #ffffff;
    font-size: 11px;
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

  .profile-photo-upload-card {
    border: 1px dashed var(--erp-border-dark, #cbd5e1);
    border-radius: var(--erp-radius-sm, 4px);
    padding: 12px;
    background: #f8fafc;
  }

  .profile-photo-preview {
    width: 60px;
    height: 60px;
    border-radius: var(--erp-radius-sm, 4px);
    object-fit: cover;
    border: 1px solid var(--erp-border, #e2e8f0);
    background: #fff;
  }

  .profile-photo-upload-card .help-block {
    margin: 4px 0 0;
    color: var(--erp-text-muted, #64748b);
    font-size: 11px;
  }

  .profile-file-input {
    border-radius: var(--erp-radius-sm, 4px);
    border: 1px solid var(--erp-border-dark, #cbd5e1);
    background: #fff;
    padding: 5px 8px;
    width: 100%;
    font-size: 12px;
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
            <div class="profile-avatar-wrap">
              <img class="profile-avatar" src="<?php echo htmlspecialchars($profilePhotoWebPath); ?>" alt="User profile picture">
              <span class="profile-avatar-edit-tag"><i class="fa fa-camera"></i></span>
            </div>
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

          <form class="form-horizontal" method="POST" enctype="multipart/form-data">
            <?php echo DBController::getCsrfInputField(); ?>
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

              <div class="form-group">
                <label class="col-sm-3 control-label">Profile Photo</label>
                <div class="col-sm-9">
                  <div class="profile-photo-upload-card">
                    <div class="row">
                      <div class="col-xs-3" style="text-align:center;">
                        <img src="<?php echo htmlspecialchars($profilePhotoWebPath); ?>" id="profilePhotoPreview" class="profile-photo-preview" alt="Profile photo preview">
                      </div>
                      <div class="col-xs-9">
                        <input type="file" class="profile-file-input" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <p class="help-block">Upload JPG, PNG, or WEBP image (max 2MB).</p>
                      </div>
                    </div>
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
      </div>
    </div>
  </section>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var fileInput = document.getElementById('profile_photo');
    var preview = document.getElementById('profilePhotoPreview');

    if (!fileInput || !preview) {
      return;
    }

    fileInput.addEventListener('change', function () {
      var selectedFile = this.files && this.files.length > 0 ? this.files[0] : null;
      if (!selectedFile) {
        return;
      }

      var reader = new FileReader();
      reader.onload = function (event) {
        if (event && event.target && event.target.result) {
          preview.src = event.target.result;
        }
      };
      reader.readAsDataURL(selectedFile);
    });
  });
</script>

<?php include "header/footer.php"; ?>
