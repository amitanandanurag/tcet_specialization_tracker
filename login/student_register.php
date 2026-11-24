<?php
session_start();
require "../database/db_connect.php";

$db_handle = new DBController();

$message = "";

function getNextId(mysqli $conn, string $table, string $column): int
{
    $result = mysqli_query($conn, "SELECT COALESCE(MAX($column), 0) + 1 AS next_id FROM $table");
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        return (int) ($row['next_id'] ?? 1);
    }

    return 1;
}

// ================= REGISTER LOGIC =================
if (isset($_POST['register']) || (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')) {

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
           || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    // 1. CSRF VALIDATION
    if (!DBController::validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = "Invalid or missing security token (CSRF). Please refresh the page and try again.";
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => $message]);
            exit();
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = intval($_POST['department'] ?? 0);
        $passwordInput = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // 2. INPUT VALIDATIONS
        if ($username === '' || $email === '' || $phone === '' || $department <= 0 || $passwordInput === '') {
            $message = "All fields are required.";
        }
        elseif (!preg_match("/^[A-Za-z0-9_ ]{3,30}$/", $username)) {
            $message = "Username must be 3-30 characters (letters, numbers, space, underscore).";
        }
        elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
            $message = "Phone number must contain exactly 10 digits.";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/^[a-zA-Z0-9._%+-]+@tcetmumbai\.in$/i", $email)) {
            $message = "Use institute email only (name@tcetmumbai.in)!";
        }
        elseif ($passwordInput !== '' && strlen($passwordInput) < 8) {
            $message = "Password must be at least 8 characters long.";
        }
        elseif ($passwordInput !== '' && $confirmPassword !== '' && $passwordInput !== $confirmPassword) {
            $message = "Password and confirm password do not match.";
        }
        else {
            // Verify Department Exists in st_department_master
            $deptValid = false;
            $chkDeptStmt = mysqli_prepare($db_handle->conn, "SELECT department_id FROM st_department_master WHERE department_id = ? LIMIT 1");
            if ($chkDeptStmt) {
                mysqli_stmt_bind_param($chkDeptStmt, 'i', $department);
                mysqli_stmt_execute($chkDeptStmt);
                mysqli_stmt_store_result($chkDeptStmt);
                if (mysqli_stmt_num_rows($chkDeptStmt) > 0) {
                    $deptValid = true;
                }
                mysqli_stmt_close($chkDeptStmt);
            }

            if (!$deptValid) {
                $message = "Please select a valid department.";
            } else {
                // 3. DUPLICATE ACCOUNT CHECKS (EMAIL, USERNAME, PHONE)
                $emailExists = false;
                $userExists = false;
                $phoneExists = false;

                // Check email in st_user_master
                $chkUserEmail = mysqli_prepare($db_handle->conn, "SELECT 1 FROM st_user_master WHERE email_id = ? LIMIT 1");
                if ($chkUserEmail) {
                    mysqli_stmt_bind_param($chkUserEmail, 's', $email);
                    mysqli_stmt_execute($chkUserEmail);
                    mysqli_stmt_store_result($chkUserEmail);
                    if (mysqli_stmt_num_rows($chkUserEmail) > 0) {
                        $emailExists = true;
                    }
                    mysqli_stmt_close($chkUserEmail);
                }

                // Check email in st_login
                if (!$emailExists) {
                    $chkLoginEmail = mysqli_prepare($db_handle->conn, "SELECT 1 FROM st_login WHERE username = ? LIMIT 1");
                    if ($chkLoginEmail) {
                        mysqli_stmt_bind_param($chkLoginEmail, 's', $email);
                        mysqli_stmt_execute($chkLoginEmail);
                        mysqli_stmt_store_result($chkLoginEmail);
                        if (mysqli_stmt_num_rows($chkLoginEmail) > 0) {
                            $emailExists = true;
                        }
                        mysqli_stmt_close($chkLoginEmail);
                    }
                }

                // Check username in st_user_master
                $chkUserName = mysqli_prepare($db_handle->conn, "SELECT 1 FROM st_user_master WHERE user_name = ? LIMIT 1");
                if ($chkUserName) {
                    mysqli_stmt_bind_param($chkUserName, 's', $username);
                    mysqli_stmt_execute($chkUserName);
                    mysqli_stmt_store_result($chkUserName);
                    if (mysqli_stmt_num_rows($chkUserName) > 0) {
                        $userExists = true;
                    }
                    mysqli_stmt_close($chkUserName);
                }

                // Check username in st_login
                if (!$userExists) {
                    $chkLoginUser = mysqli_prepare($db_handle->conn, "SELECT 1 FROM st_login WHERE username = ? LIMIT 1");
                    if ($chkLoginUser) {
                        mysqli_stmt_bind_param($chkLoginUser, 's', $username);
                        mysqli_stmt_execute($chkLoginUser);
                        mysqli_stmt_store_result($chkLoginUser);
                        if (mysqli_stmt_num_rows($chkLoginUser) > 0) {
                            $userExists = true;
                        }
                        mysqli_stmt_close($chkLoginUser);
                    }
                }

                // Check phone in st_user_master
                $chkPhoneStmt = mysqli_prepare($db_handle->conn, "SELECT 1 FROM st_user_master WHERE phone_number = ? LIMIT 1");
                if ($chkPhoneStmt) {
                    mysqli_stmt_bind_param($chkPhoneStmt, 's', $phone);
                    mysqli_stmt_execute($chkPhoneStmt);
                    mysqli_stmt_store_result($chkPhoneStmt);
                    if (mysqli_stmt_num_rows($chkPhoneStmt) > 0) {
                        $phoneExists = true;
                    }
                    mysqli_stmt_close($chkPhoneStmt);
                }

                if ($emailExists) {
                    $message = "Institute email is already registered.";
                } elseif ($userExists) {
                    $message = "Username already exists.";
                } elseif ($phoneExists) {
                    $message = "Phone number is already registered with another account.";
                } else {
                    // 4. PREPARE HASHED PASSWORD & ROLE
                    $userProvidedPassword = ($passwordInput !== '');
                    $plainPassword = $userProvidedPassword ? $passwordInput : ("Tcet@" . bin2hex(random_bytes(3)));
                    $hashedPassword = DBController::hashPassword($plainPassword);
                    $role_id = 5; // Strict Student Role (Privilege escalation impossible)
                    $isFirstLogin = $userProvidedPassword ? 0 : 1;

                    mysqli_begin_transaction($db_handle->conn);

                    try {
                        // Insert minimal student master record using native AUTO_INCREMENT
                        $sqlStudent = "INSERT INTO st_student_master (
                            registration_no, class_id, division_id, grad_year, roll_no, department_id,
                            fname, mobile, email, mark_list, status, m_sem1, m_sem2, m_sem3
                        ) VALUES (
                            '', 0, 0, NULL, '', ?, ?, ?, ?, '', 1, '', '', ''
                        )";

                        $stmtStudent = mysqli_prepare($db_handle->conn, $sqlStudent);
                        if (!$stmtStudent) {
                            throw new Exception("Student statement preparation failed: " . mysqli_error($db_handle->conn));
                        }
                        mysqli_stmt_bind_param($stmtStudent, 'isss', $department, $username, $phone, $email);
                        if (!mysqli_stmt_execute($stmtStudent)) {
                            $err = mysqli_stmt_error($stmtStudent);
                            mysqli_stmt_close($stmtStudent);
                            throw new Exception("Student record insert failed: " . $err);
                        }
                        $student_id = mysqli_insert_id($db_handle->conn);
                        mysqli_stmt_close($stmtStudent);

                        // Insert user record with linked student_id using native AUTO_INCREMENT
                        $sqlUser = "INSERT INTO st_user_master 
                            (user_name, email_id, phone_number, department_id, role_id, student_id, is_first_login)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmtUser = mysqli_prepare($db_handle->conn, $sqlUser);
                        if (!$stmtUser) {
                            throw new Exception("User statement preparation failed: " . mysqli_error($db_handle->conn));
                        }
                        mysqli_stmt_bind_param($stmtUser, 'sssiiii',
                            $username, $email, $phone, $department, $role_id, $student_id, $isFirstLogin
                        );
                        if (!mysqli_stmt_execute($stmtUser)) {
                            $err = mysqli_stmt_error($stmtUser);
                            mysqli_stmt_close($stmtUser);
                            throw new Exception("User record insert failed: " . $err);
                        }
                        $user_id = mysqli_insert_id($db_handle->conn);
                        mysqli_stmt_close($stmtUser);

                        // Insert login with email using native AUTO_INCREMENT
                        $sqlLoginEmail = "INSERT INTO st_login (username, password, user_id) VALUES (?, ?, ?)";
                        $stmtLoginEmail = mysqli_prepare($db_handle->conn, $sqlLoginEmail);
                        if (!$stmtLoginEmail) {
                            throw new Exception("Login statement preparation failed: " . mysqli_error($db_handle->conn));
                        }
                        mysqli_stmt_bind_param($stmtLoginEmail, 'ssi', $email, $hashedPassword, $user_id);
                        if (!mysqli_stmt_execute($stmtLoginEmail)) {
                            $err = mysqli_stmt_error($stmtLoginEmail);
                            mysqli_stmt_close($stmtLoginEmail);
                            throw new Exception("Login email record insert failed: " . $err);
                        }
                        mysqli_stmt_close($stmtLoginEmail);

                        // Insert login with username (if distinct from email) to allow sign-in by username
                        if (strtolower($username) !== strtolower($email)) {
                            $stmtLoginUser = mysqli_prepare($db_handle->conn, "INSERT INTO st_login (username, password, user_id) VALUES (?, ?, ?)");
                            if ($stmtLoginUser) {
                                mysqli_stmt_bind_param($stmtLoginUser, 'ssi', $username, $hashedPassword, $user_id);
                                if (!mysqli_stmt_execute($stmtLoginUser)) {
                                    $err = mysqli_stmt_error($stmtLoginUser);
                                    mysqli_stmt_close($stmtLoginUser);
                                    throw new Exception("Login username record insert failed: " . $err);
                                }
                                mysqli_stmt_close($stmtLoginUser);
                            }
                        }

                        mysqli_commit($db_handle->conn);

                        if (method_exists($db_handle, 'writeAuditLog')) {
                            $db_handle->writeAuditLog($user_id, 'STUDENT_REGISTERED', 'st_user_master', $user_id, "Student {$email} registered successfully", $username);
                        }

                        if ($isAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                'status' => 'success',
                                'message' => 'Registration submitted successfully.',
                                'username' => $email
                            ]);
                            exit();
                        }

                        // Store safe feedback for standard form submission
                        $_SESSION['registration_success'] = true;
                        $_SESSION['registered_username'] = $email;
                        if (!$userProvidedPassword) {
                            $_SESSION['temp_password'] = $plainPassword;
                        }

                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();

                    } catch (Throwable $e) {
                        mysqli_rollback($db_handle->conn);
                        $refId = 'REG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                        error_log("[$refId] Portal registration error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
                        $message = "Registration could not be completed due to a temporary server error. (Ref: {$refId})";
                    }
                }
            }
        }

        if ($isAjax && !empty($message)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $message]);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Registration - TCET ERP</title>

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
    position: relative;
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
    margin-top: 3px;
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

select.erp-input-control {
    cursor: pointer;
}

.btn-erp-register-submit {
    width: 100%;
    height: 38px;
    margin-top: 6px;
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

.btn-erp-register-submit:hover,
.btn-erp-register-submit:focus {
    background-color: var(--erp-primary-hover);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(66, 60, 188, 0.3);
}

#msgBox {
    color: var(--erp-danger-text);
    background-color: var(--erp-danger-bg);
    border: 1px solid var(--erp-danger-border);
    padding: 10px 12px;
    border-radius: var(--erp-radius);
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 14px;
    text-align: center;
}

#credentialsBox {
    background-color: var(--erp-success-bg);
    border: 1px solid var(--erp-success-border);
    padding: 20px 18px;
    border-radius: var(--erp-radius);
    color: var(--erp-text-main);
    text-align: center;
}

.credentials-title {
    color: var(--erp-success-text);
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

#credText {
    background: #ffffff;
    padding: 14px;
    border-radius: var(--erp-radius);
    margin: 12px 0;
    border: 1px solid var(--erp-border);
    font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 13px;
    line-height: 1.6;
    color: #065f46;
    text-align: left;
}

#credText strong {
    color: #047857;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    display: inline-block;
    width: 80px;
}

.btn-copy {
    width: 100%;
    height: 36px;
    border-radius: var(--erp-radius);
    background-color: #ffffff;
    color: var(--erp-success-text);
    border: 1px solid var(--erp-success-border);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-copy:hover {
    background-color: #ecfdf5;
}

.btn-proceed {
    width: 100%;
    height: 36px;
    margin-top: 10px;
    border-radius: var(--erp-radius);
    background-color: var(--erp-primary);
    color: #ffffff;
    border: 1px solid var(--erp-primary-hover);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-proceed:hover {
    background-color: var(--erp-primary-hover);
}
</style>

<script>
function copyCredentials() {
    const text = document.getElementById("credText").innerText;
    navigator.clipboard.writeText(text);
    alert("Credentials copied to clipboard!");
}

function proceedToEnrollment() {
    if (window.parent && window.parent !== window) {
        if (typeof window.parent.closePopup === 'function') {
            window.parent.closePopup();
            setTimeout(function() {
                alert("Registration successful! You can now log in with your credentials.");
            }, 300);
        }
    }
}

function showRegisterError(message) {
    var msgBox = document.getElementById('msgBox');
    if (!msgBox) {
        msgBox = document.createElement('div');
        msgBox.id = 'msgBox';
        var form = document.querySelector('.erp-modal-body form');
        if (form) {
            form.parentNode.insertBefore(msgBox, form);
        }
    }

    msgBox.textContent = message;
    msgBox.style.display = 'block';
}

function validateStudentRegisterForm(event) {
    var username = document.querySelector('input[name="username"]');
    var email = document.querySelector('input[name="email"]');
    var phone = document.querySelector('input[name="phone"]');
    var department = document.querySelector('select[name="department"]');
    var password = document.querySelector('input[name="password"]');
    var confirmPassword = document.querySelector('input[name="confirm_password"]');

    var usernameValue = username ? username.value.trim() : '';
    var emailValue = email ? email.value.trim() : '';
    var phoneValue = phone ? phone.value.trim() : '';
    var departmentValue = department ? department.value.trim() : '';
    var passwordValue = password ? password.value : '';
    var confirmPasswordValue = confirmPassword ? confirmPassword.value : '';

    var emailPattern = /^[a-zA-Z0-9._%+-]+@tcetmumbai\.in$/i;
    var phonePattern = /^[0-9]{10}$/;
    var usernamePattern = /^[A-Za-z0-9_ ]{3,30}$/;

    if (!usernameValue) {
        event.preventDefault();
        showRegisterError('All fields are required.');
        return false;
    }

    if (!usernamePattern.test(usernameValue)) {
        event.preventDefault();
        showRegisterError('Username must be 3-30 characters (letters, numbers, space, underscore).');
        return false;
    }

    if (!emailValue) {
        event.preventDefault();
        showRegisterError('All fields are required.');
        return false;
    }

    if (!emailPattern.test(emailValue)) {
        event.preventDefault();
        showRegisterError('Use institute email only (name@tcetmumbai.in)!');
        return false;
    }

    if (!phoneValue) {
        event.preventDefault();
        showRegisterError('All fields are required.');
        return false;
    }

    if (!phonePattern.test(phoneValue)) {
        event.preventDefault();
        showRegisterError('Phone number must contain exactly 10 digits.');
        return false;
    }

    if (!departmentValue || parseInt(departmentValue, 10) <= 0) {
        event.preventDefault();
        showRegisterError('Please select a valid department.');
        return false;
    }

    if (!passwordValue) {
        event.preventDefault();
        showRegisterError('All fields are required.');
        return false;
    }

    if (passwordValue.length < 8) {
        event.preventDefault();
        showRegisterError('Password must be at least 8 characters long.');
        return false;
    }

    if (passwordValue !== confirmPasswordValue) {
        event.preventDefault();
        showRegisterError('Password and confirm password do not match.');
        return false;
    }

    return true;
}

window.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.erp-modal-body form');
    if (form) {
        form.addEventListener('submit', validateStudentRegisterForm);
    }
});
</script>

<?php if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) { ?>
<script>
window.addEventListener('load', function () {
    if (window.parent && window.parent !== window) {
        try {
            var parentDocument = window.parent.document;
            var usernameField = parentDocument.getElementById('username');
            if (usernameField) {
                usernameField.value = <?php echo json_encode($_SESSION['registered_username']); ?>;
                var passwordField = parentDocument.getElementById('password');
                if (passwordField) {
                    passwordField.focus();
                } else {
                    usernameField.focus();
                }
            }
        } catch (error) {
            window.parent.postMessage({
                type: 'student-registration-success',
                username: <?php echo json_encode($_SESSION['registered_username']); ?>
            }, window.location.origin);
        }
    }
});
</script>
<?php } ?>

</head>
<body>

<div class="erp-modal-container">
    <div class="erp-modal-header">
        <h2 class="erp-modal-title">Portal Registration</h2>
        <p class="erp-modal-subtitle">Create your TCET ERP account</p>
    </div>

    <div class="erp-modal-body">
        <?php if (!empty($message)) { ?>
        <div id="msgBox"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>

        <?php if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) { ?>
        <div id="credentialsBox">
            <div class="credentials-title"><i class="fa fa-check-circle"></i> Registration Successful</div>
            <p style="color: var(--erp-text-secondary); margin: 6px 0 12px; font-size: 12px;">Your student account has been created successfully.</p>
            <div id="credText">
                <div><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['registered_username']); ?></div>
                <?php if (!empty($_SESSION['temp_password'])) { ?>
                <div style="margin-top: 6px;"><strong>Temporary Password:</strong> <?php echo htmlspecialchars($_SESSION['temp_password']); ?></div>
                <p style="margin-top: 8px; font-size: 11px; color: #b45309;"><i class="fa fa-info-circle"></i> Please change your password upon your first sign in.</p>
                <button type="button" class="btn-copy" style="margin-top: 8px;" onclick="copyCredentials()"><i class="fa fa-copy"></i> Copy Credentials</button>
                <?php } else { ?>
                <div style="margin-top: 6px; font-size: 12px; color: #15803d;"><i class="fa fa-shield-alt"></i> Password securely encrypted. Sign in with your password.</div>
                <?php } ?>
            </div>
            <button type="button" class="btn-proceed" onclick="proceedToEnrollment()">Proceed to Sign In <i class="fa fa-arrow-right"></i></button>
        </div>
        <?php
        unset($_SESSION['registration_success']);
        unset($_SESSION['registered_username']);
        unset($_SESSION['temp_password']);
        ?>
        <?php } else { ?>
        <form method="POST" autocomplete="off">
            <?php echo DBController::getCsrfInputField(); ?>

            <div class="erp-form-group">
                <label>Username</label>
                <div class="erp-input-group">
                    <span class="erp-input-icon"><i class="fa fa-user"></i></span>
                    <input type="text" name="username" class="erp-input-control" placeholder="Enter username" required autocomplete="off">
                </div>
            </div>

            <div class="erp-form-group">
                <label>Institute Email</label>
                <div class="erp-input-group">
                    <span class="erp-input-icon"><i class="fa fa-envelope"></i></span>
                    <input type="email" name="email" class="erp-input-control" placeholder="name@tcetmumbai.in" required autocomplete="email">
                </div>
            </div>

            <div class="erp-form-group">
                <label>Phone Number</label>
                <div class="erp-input-group">
                    <span class="erp-input-icon"><i class="fa fa-phone"></i></span>
                    <input type="text" name="phone" class="erp-input-control" placeholder="10 digit phone number" required maxlength="10" inputmode="numeric" autocomplete="tel">
                </div>
            </div>

            <div class="erp-form-group">
                <label>Department</label>
                <div class="erp-input-group">
                    <span class="erp-input-icon"><i class="fa fa-building"></i></span>
                    <select name="department" class="erp-input-control" required>
                        <option value="">Select department</option>
                        <?php
                        $dept = $db_handle->query("SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC");
                        while ($row = $dept->fetch_assoc()) {
                            echo '<option value="'.intval($row['department_id']).'">'.htmlspecialchars($row['department_name']).'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="erp-form-group">
                <label>Password</label>
                <div class="erp-input-group">
                    <span class="erp-input-icon"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" class="erp-input-control" placeholder="Create password (minimum 8 characters)" required autocomplete="new-password">
                </div>
            </div>

            <div class="erp-form-group">
                <label>Confirm Password</label>
                <div class="erp-input-group">
                    <span class="erp-input-icon"><i class="fa fa-lock"></i></span>
                    <input type="password" name="confirm_password" class="erp-input-control" placeholder="Re-enter password" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" name="register" class="btn-erp-register-submit">
                <i class="fa fa-user-plus"></i> Register
            </button>
        </form>
        <?php } ?>
    </div>
</div>

</body>
</html>