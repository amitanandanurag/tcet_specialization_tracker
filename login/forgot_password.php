<?php
require_once __DIR__ . "/../database/db_connect.php";
DBController::startSecureSession();

$db_handle = new DBController();
$db_handle->ensurePasswordResetTable();

$message = "";
$messageType = ""; // "success" or "error"

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$mode = 'request'; // 'request' or 'reset'
$tokenParam = trim($_GET['token'] ?? '');
$activeResetRecord = null;

// Validate token if provided
if ($tokenParam !== '') {
    $mode = 'reset';
    $tokenHash = hash('sha256', $tokenParam);

    $stmt = mysqli_prepare($db_handle->conn, "SELECT reset_id, user_id, email, expires_at FROM st_password_resets WHERE token_hash = ? AND consumed_at IS NULL AND expires_at > NOW() LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $tokenHash);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $activeResetRecord = $row;
        }
        mysqli_stmt_close($stmt);
    }

    if (!$activeResetRecord) {
        $message = "This password reset link is invalid or has expired. Please request a new one.";
        $messageType = "error";
        $mode = 'invalid_token';
    }
}

// -------------------------------------------------------------
// POST Handler: Request Reset Link
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset_btn'])) {
    if (!DBController::validateCsrfToken()) {
        $message = "Security validation failed. Please refresh the page and try again.";
        $messageType = "error";
    } else {
        $email = trim($_POST['email'] ?? '');

        // Basic rate limiting: max 5 requests per IP in last 15 minutes
        $rateStmt = mysqli_prepare($db_handle->conn, "SELECT COUNT(*) as cnt FROM st_password_resets WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $requestCount = 0;
        if ($rateStmt) {
            mysqli_stmt_bind_param($rateStmt, "s", $clientIp);
            mysqli_stmt_execute($rateStmt);
            $rRes = mysqli_stmt_get_result($rateStmt);
            if ($rRes && ($rRow = mysqli_fetch_assoc($rRes))) {
                $requestCount = intval($rRow['cnt']);
            }
            mysqli_stmt_close($rateStmt);
        }

        if ($requestCount >= 5) {
            $message = "Too many reset attempts from this network. Please wait a few minutes before trying again.";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strpos(strtolower($email), "@tcetmumbai.in") === false) {
            $message = "Please enter a valid TCET institute email address (@tcetmumbai.in).";
            $messageType = "error";
        } else {
            // Lookup user using prepared statement
            $userStmt = mysqli_prepare($db_handle->conn, "SELECT u.user_id, u.user_name FROM st_user_master u INNER JOIN st_login l ON l.user_id = u.user_id WHERE u.email_id = ? LIMIT 1");
            $foundUserId = 0;
            $foundUserName = '';
            if ($userStmt) {
                mysqli_stmt_bind_param($userStmt, "s", $email);
                mysqli_stmt_execute($userStmt);
                $uRes = mysqli_stmt_get_result($userStmt);
                if ($uRes && ($uRow = mysqli_fetch_assoc($uRes))) {
                    $foundUserId = intval($uRow['user_id']);
                    $foundUserName = $uRow['user_name'] ?? '';
                }
                mysqli_stmt_close($userStmt);
            }

            if ($foundUserId > 0) {
                // Generate cryptographically secure token
                $plainToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $plainToken);

                // Invalidate any previous unconsumed tokens for this user
                $invStmt = mysqli_prepare($db_handle->conn, "UPDATE st_password_resets SET consumed_at = NOW() WHERE user_id = ? AND consumed_at IS NULL");
                if ($invStmt) {
                    mysqli_stmt_bind_param($invStmt, "i", $foundUserId);
                    mysqli_stmt_execute($invStmt);
                    mysqli_stmt_close($invStmt);
                }

                // Insert token with 15-minute expiry
                $insStmt = mysqli_prepare($db_handle->conn, "INSERT INTO st_password_resets (user_id, email, token_hash, expires_at, ip_address) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), ?)");
                if ($insStmt) {
                    mysqli_stmt_bind_param($insStmt, "isss", $foundUserId, $email, $tokenHash, $clientIp);
                    mysqli_stmt_execute($insStmt);
                    mysqli_stmt_close($insStmt);
                }

                // Build reset link
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $resetUrl = "{$scheme}://{$host}/st/login/forgot_password.php?token={$plainToken}";

                // Send email notification if mail transport is configured
                $mailHeaders = "MIME-Version: 1.0\r\n";
                $mailHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
                $mailHeaders .= "From: TCET ERP System <no-reply@tcetmumbai.in>\r\n";
                $mailSubject = "TCET ERP - Password Reset Request";
                $mailBody = "<html><body>"
                    . "<p>Hello " . htmlspecialchars($foundUserName) . ",</p>"
                    . "<p>A password reset request was received for your TCET Specialization Tracker account.</p>"
                    . "<p>To reset your password, please click the secure link below (valid for 15 minutes):</p>"
                    . "<p><a href=\"" . htmlspecialchars($resetUrl) . "\">" . htmlspecialchars($resetUrl) . "</a></p>"
                    . "<p>If you did not request this, please ignore this email. Your password will remain unchanged.</p>"
                    . "</body></html>";

                @mail($email, $mailSubject, $mailBody, $mailHeaders);

                if (method_exists($db_handle, 'writeAuditLog')) {
                    $db_handle->writeAuditLog($foundUserId, 'PASSWORD_RESET_REQUESTED', 'st_password_resets', null, "Password reset requested for {$email}", $email, $clientIp);
                }
            }

            // Universal generic message: do not reveal whether account exists
            $message = "If the provided email is registered in our system, password reset instructions have been sent. Please check your inbox and spam folder.";
            $messageType = "success";
        }
    }
}

// -------------------------------------------------------------
// POST Handler: Submit New Password
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_new_password_btn'])) {
    if (!DBController::validateCsrfToken()) {
        $message = "Security validation failed. Please refresh the page and try again.";
        $messageType = "error";
    } elseif (!$activeResetRecord) {
        $message = "This password reset link is invalid or has expired.";
        $messageType = "error";
        $mode = 'invalid_token';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $message = "Password must be at least 8 characters long.";
            $messageType = "error";
        } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $message = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
            $messageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Passwords do not match. Please re-enter.";
            $messageType = "error";
        } else {
            mysqli_begin_transaction($db_handle->conn);
            try {
                $targetUserId = intval($activeResetRecord['user_id']);
                $resetId = intval($activeResetRecord['reset_id']);

                // Ensure token is still active and not consumed (concurrency check)
                $lockStmt = mysqli_prepare($db_handle->conn, "SELECT reset_id FROM st_password_resets WHERE reset_id = ? AND consumed_at IS NULL AND expires_at > NOW() FOR UPDATE");
                mysqli_stmt_bind_param($lockStmt, "i", $resetId);
                mysqli_stmt_execute($lockStmt);
                $lockRes = mysqli_stmt_get_result($lockStmt);

                if (!$lockRes || mysqli_num_rows($lockRes) === 0) {
                    mysqli_rollback($db_handle->conn);
                    $message = "This reset link has already been used or expired.";
                    $messageType = "error";
                    $mode = 'invalid_token';
                } else {
                    mysqli_stmt_close($lockStmt);

                    // Hash password using secure bcrypt helper
                    $hashedPassword = DBController::hashPassword($newPassword);

                    // Update login password
                    $updLogin = mysqli_prepare($db_handle->conn, "UPDATE st_login SET password = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($updLogin, "si", $hashedPassword, $targetUserId);
                    mysqli_stmt_execute($updLogin);
                    mysqli_stmt_close($updLogin);

                    // Mark token as consumed
                    $markConsumed = mysqli_prepare($db_handle->conn, "UPDATE st_password_resets SET consumed_at = NOW() WHERE reset_id = ?");
                    mysqli_stmt_bind_param($markConsumed, "i", $resetId);
                    mysqli_stmt_execute($markConsumed);
                    mysqli_stmt_close($markConsumed);

                    // Clear first login flag if present
                    if ($db_handle->columnExists('st_user_master', 'is_first_login')) {
                        $clearFirst = mysqli_prepare($db_handle->conn, "UPDATE st_user_master SET is_first_login = 0 WHERE user_id = ?");
                        if ($clearFirst) {
                            mysqli_stmt_bind_param($clearFirst, "i", $targetUserId);
                            mysqli_stmt_execute($clearFirst);
                            mysqli_stmt_close($clearFirst);
                        }
                    }

                    mysqli_commit($db_handle->conn);

                    if (method_exists($db_handle, 'writeAuditLog')) {
                        $db_handle->writeAuditLog($targetUserId, 'PASSWORD_RESET_COMPLETED', 'st_login', $targetUserId, "Password reset completed successfully", $activeResetRecord['email'], $clientIp);
                    }

                    $message = "Password reset successful! You can now log in with your new password.";
                    $messageType = "success";
                    $mode = 'completed';
                }
            } catch (Throwable $e) {
                mysqli_rollback($db_handle->conn);
                $message = "An error occurred while resetting your password. Please try again.";
                $messageType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TCET ERP</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --erp-primary: #423cbc;
            --erp-primary-hover: #352fa1;
            --erp-border: #e2e8f0;
            --erp-border-input: #cbd5e1;
            --erp-text-main: #0f172a;
            --erp-text-body: #334155;
            --erp-text-secondary: #475569;
            --erp-text-muted: #64748b;
            --erp-success-bg: #f0fdf4;
            --erp-success-border: #bbf7d0;
            --erp-success-text: #15803d;
            --erp-danger-bg: #fef2f2;
            --erp-danger-border: #fecaca;
            --erp-danger-text: #b91c1c;
            --erp-radius: 4px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #ffffff;
            color: var(--erp-text-body);
            font-size: 13px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .erp-modal-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            background: #ffffff;
        }

        .erp-modal-header {
            padding: 14px 20px;
            background: #ffffff;
            border-bottom: 1px solid var(--erp-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .erp-header-logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
            border-radius: var(--erp-radius);
            border: 1px solid var(--erp-border);
            padding: 2px;
            background: #ffffff;
        }

        .erp-modal-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--erp-text-main);
            margin: 0;
            letter-spacing: -0.01em;
        }

        .erp-modal-subtitle {
            font-size: 12px;
            color: var(--erp-text-muted);
            margin-top: 2px;
            margin-bottom: 0;
        }

        .erp-modal-body {
            padding: 18px 20px 20px;
        }

        .erp-form-group {
            margin-bottom: 14px;
        }

        .erp-form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--erp-text-secondary);
            margin-bottom: 4px;
        }

        .erp-input-group {
            display: flex;
            align-items: stretch;
            width: 100%;
            border: 1px solid var(--erp-border-input);
            border-radius: var(--erp-radius);
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .erp-input-group:focus-within {
            border-color: var(--erp-primary);
            box-shadow: 0 0 0 3px rgba(66, 60, 188, 0.12);
        }

        .erp-input-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            background: #f8fafc;
            border-right: 1px solid var(--erp-border);
            color: var(--erp-text-muted);
            font-size: 13px;
            flex-shrink: 0;
        }

        .erp-input-control {
            width: 100%;
            height: 36px;
            padding: 6px 12px;
            font-size: 13px;
            color: var(--erp-text-main);
            background: transparent;
            border: none;
            outline: none;
        }

        .erp-input-control::placeholder {
            color: #94a3b8;
            font-size: 12px;
        }

        .helper-text {
            color: var(--erp-text-muted);
            font-size: 12px;
            margin-bottom: 14px;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: var(--erp-radius);
            border: 1px solid var(--erp-border);
        }

        .btn-erp-reset-submit {
            width: 100%;
            height: 38px;
            background-color: var(--erp-primary);
            color: #ffffff;
            border: 1px solid var(--erp-primary-hover);
            border-radius: var(--erp-radius);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(66, 60, 188, 0.2);
        }

        .btn-erp-reset-submit:hover,
        .btn-erp-reset-submit:focus {
            background-color: var(--erp-primary-hover);
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(66, 60, 188, 0.3);
        }

        .btn-erp-back {
            display: block;
            width: 100%;
            height: 36px;
            line-height: 34px;
            text-align: center;
            background-color: #ffffff;
            color: var(--erp-text-secondary);
            border: 1px solid var(--erp-border-input);
            border-radius: var(--erp-radius);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 8px;
            transition: all 0.15s ease;
        }

        .btn-erp-back:hover {
            background-color: #f8fafc;
            color: var(--erp-text-main);
            text-decoration: none;
        }

        #msgBox {
            padding: 10px 12px;
            border-radius: var(--erp-radius);
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 14px;
            text-align: center;
        }

        #msgBox.success {
            color: var(--erp-success-text);
            background-color: var(--erp-success-bg);
            border: 1px solid var(--erp-success-border);
        }

        #msgBox.error {
            color: var(--erp-danger-text);
            background-color: var(--erp-danger-bg);
            border: 1px solid var(--erp-danger-border);
        }
    </style>

    <script>
        function closeModal() {
            try {
                if (window.parent && window.parent.closeForgotPasswordPopup) {
                    window.parent.closeForgotPasswordPopup();
                } else {
                    window.location.href = "index.php";
                }
            } catch(e) {
                window.location.href = "index.php";
            }
        }
    </script>
</head>

<body>
    <div class="erp-modal-container">
        <div class="erp-modal-header">
            <img src="images/tcet_logo.png" alt="TCET Mumbai Logo" class="erp-header-logo">
            <div>
                <h2 class="erp-modal-title">
                    <?php echo ($mode === 'reset') ? 'Create New Password' : 'Forgot Password'; ?>
                </h2>
                <p class="erp-modal-subtitle">
                    <?php echo ($mode === 'reset') ? 'Enter and confirm your new secure password' : 'Reset your TCET ERP account password securely'; ?>
                </p>
            </div>
        </div>

        <div class="erp-modal-body">
            <?php if (!empty($message)) { ?>
                <div id="msgBox" class="<?php echo htmlspecialchars($messageType); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <?php if ($mode === 'request') { ?>
                <div class="helper-text">
                    <i class="fa fa-info-circle" style="color: var(--erp-primary); margin-right: 4px;"></i>
                    Enter your registered institute email address. A time-limited, single-use password reset link will be sent to your inbox.
                </div>

                <form method="POST" autocomplete="off">
                    <?php echo DBController::getCsrfInputField(); ?>
                    <div class="erp-form-group">
                        <label>Institute Email</label>
                        <div class="erp-input-group">
                            <span class="erp-input-icon"><i class="fa fa-envelope"></i></span>
                            <input type="email" name="email" class="erp-input-control" placeholder="username@tcetmumbai.in" required>
                        </div>
                    </div>

                    <div style="margin-top: 16px;">
                        <button type="submit" name="request_reset_btn" class="btn-erp-reset-submit">
                            <i class="fa fa-paper-plane"></i> Send Reset Link
                        </button>
                        <a href="#" class="btn-erp-back" onclick="closeModal(); return false;">Back to Login</a>
                    </div>
                </form>

            <?php } elseif ($mode === 'reset') { ?>
                <div class="helper-text">
                    <i class="fa fa-shield-alt" style="color: var(--erp-primary); margin-right: 4px;"></i>
                    Password must be at least 8 characters long and contain uppercase, lowercase, and numbers.
                </div>

                <form method="POST" autocomplete="off">
                    <?php echo DBController::getCsrfInputField(); ?>
                    <div class="erp-form-group">
                        <label>New Password</label>
                        <div class="erp-input-group">
                            <span class="erp-input-icon"><i class="fa fa-lock"></i></span>
                            <input type="password" name="new_password" class="erp-input-control" placeholder="••••••••" required minlength="8">
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <label>Confirm New Password</label>
                        <div class="erp-input-group">
                            <span class="erp-input-icon"><i class="fa fa-check-circle"></i></span>
                            <input type="password" name="confirm_password" class="erp-input-control" placeholder="••••••••" required minlength="8">
                        </div>
                    </div>

                    <div style="margin-top: 16px;">
                        <button type="submit" name="set_new_password_btn" class="btn-erp-reset-submit">
                            <i class="fa fa-key"></i> Update Password
                        </button>
                        <a href="forgot_password.php" class="btn-erp-back">Cancel / Request New Link</a>
                    </div>
                </form>

            <?php } else { ?>
                <div style="margin-top: 16px;">
                    <a href="forgot_password.php" class="btn-erp-reset-submit">
                        <i class="fa fa-redo"></i> Request New Reset Link
                    </a>
                    <a href="#" class="btn-erp-back" onclick="closeModal(); return false;">Back to Login</a>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>