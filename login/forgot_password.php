<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

$message = "";
$messageType = ""; // "success" or "error"

if (isset($_POST['reset_btn'])) {

    $email = mysqli_real_escape_string($db_handle->conn, $_POST['email']);

    if (strpos($email, "@tcetmumbai.in") === false) {
        $message = "🚫 Use institute email only!";
        $messageType = "error";
    } else {

        $check = $db_handle->query("SELECT user_id FROM st_user_master WHERE email_id='$email'");

        if ($check && mysqli_num_rows($check) > 0) {

            $row = mysqli_fetch_assoc($check);
            $user_id = $row['user_id'];

            $new_password = "Tcet@1234";

            $update = $db_handle->query("UPDATE st_login SET password='$new_password' WHERE user_id='$user_id'");

            if ($update) {
                $message = "✓ Password reset successful! Your new password is: <strong>Tcet@1234</strong>";
                $messageType = "success";
            } else {
                $message = "❌ Error resetting password!";
                $messageType = "error";
            }

        } else {
            $message = "❌ Email not found!";
            $messageType = "error";
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
        window.onload = function () {
            const msg = document.getElementById("msgBox");

            if (msg && msg.innerText.trim() !== "") {
                const successMessage = "Password reset successful!";
                if (msg.innerText.includes(successMessage)) {
                    setTimeout(() => {
                        try {
                            parent.closeForgotPasswordPopup();
                        } catch(e) {
                            parent.location.reload();
                        }
                    }, 2500);
                } else {
                    setTimeout(() => {
                        msg.style.transition = "opacity 0.5s";
                        msg.style.opacity = "0";
                        setTimeout(() => {
                            msg.style.display = "none";
                        }, 500);
                    }, 4000);
                }
            }
        };

        function closeModal() {
            try {
                parent.closeForgotPasswordPopup();
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
                <h2 class="erp-modal-title">Forgot Password</h2>
                <p class="erp-modal-subtitle">Reset your TCET ERP account password</p>
            </div>
        </div>

        <div class="erp-modal-body">
            <div class="helper-text">
                <i class="fa fa-info-circle" style="color: var(--erp-primary); margin-right: 4px;"></i>
                Enter your registered institute email. The temporary password will be reset to <strong>Tcet@1234</strong>.
            </div>

            <?php if (!empty($message)) { ?>
                <div id="msgBox" class="<?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php } ?>

            <form method="POST" autocomplete="off">
                <div class="erp-form-group">
                    <label>Institute Email</label>
                    <div class="erp-input-group">
                        <span class="erp-input-icon"><i class="fa fa-envelope"></i></span>
                        <input type="email" name="email" class="erp-input-control" placeholder="name@tcetmumbai.in" required>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <button type="submit" name="reset_btn" class="btn-erp-reset-submit">
                        <i class="fa fa-key"></i> Reset Password
                    </button>
                    <a href="#" class="btn-erp-back" onclick="closeModal(); return false;">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>