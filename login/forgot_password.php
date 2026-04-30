<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

$message = "";

if (isset($_POST['reset_btn'])) {

    $email = mysqli_real_escape_string($db_handle->conn, $_POST['email']);

    if (strpos($email, "@tcetmumbai.in") === false) {
        $message = "Use institute email only!";
    } else {

        $check = $db_handle->query("SELECT user_id FROM st_user_master WHERE email_id='$email'");

        if ($check && mysqli_num_rows($check) > 0) {

            $row = mysqli_fetch_assoc($check);
            $user_id = $row['user_id'];

            $new_password = "Tcet@1234";

            $update = $db_handle->query("UPDATE st_login SET password='$new_password' WHERE user_id='$user_id'");

            if ($update) {
                $message = "Password reset successful! Default password: Tcet@1234";
            } else {
                $message = "Error resetting password!";
            }

        } else {
            $message = "Email not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #2c5364, #203a43, #0f2027);

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            background: #0b1f2e;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.6);
        }

        .card h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .form-control {
            height: 45px;
            border-radius: 6px;
            border: none;
            background: #1c2f3f;
            color: #fff;
            margin-bottom: 15px;
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .btn-reset {
            width: 100%;
            height: 45px;
            background: #0ea5e9;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
        }

        .btn-reset:hover {
            background: #0284c7;
        }

        .msg {
            text-align: center;
            margin-bottom: 10px;
            color: #ff6b6b;
        }
    </style>

    <script>
        window.onload = function () {
            const msg = document.getElementById("msgBox");

            if (msg && msg.innerText.trim() !== "") {
                setTimeout(() => {
                    msg.style.transition = "opacity 0.5s";
                    msg.style.opacity = "0";

                    setTimeout(() => {
                        msg.style.display = "none";
                    }, 500);
                }, 3000);
            }
        };
    </script>
</head>

<body>

<div class="card">

    <h2>Forgot Password</h2>

    <?php if (!empty($message)) { ?>
        <div id="msgBox" class="msg"><?php echo $message; ?></div>
    <?php } ?>

    <form method="POST">

        <input type="email" name="email" class="form-control" placeholder="Enter Institute Email" required>

        <button type="submit" name="reset_btn" class="btn-reset">
            Reset Password
        </button>

    </form>

    <div style="text-align:center; margin-top:10px;">
        <a href="index.php" style="color:#fff;">Back to Login</a>
    </div>

</div>

</body>
</html>