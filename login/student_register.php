<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

$message = "";

if (isset($_POST['register'])) {

    $username = mysqli_real_escape_string($db_handle->conn, $_POST['username']);
    $email = mysqli_real_escape_string($db_handle->conn, $_POST['email']);
    $phone = mysqli_real_escape_string($db_handle->conn, $_POST['phone']);
    $department = mysqli_real_escape_string($db_handle->conn, $_POST['department']);

    $role_id = 5;
    $student_id = 0;
    $password = "Tcet@1234";

    if (strpos($email, "@tcetmumbai.in") === false) {
        $message = "Use institute email only!";
    } else {

        $check = $db_handle->query("SELECT * FROM st_user_master WHERE email_id='$email'");

        if ($check && mysqli_num_rows($check) > 0) {
            $message = "Email already exists!";
        } else {

            $sql1 = "INSERT INTO st_user_master 
            (user_name, email_id, phone_number, department_id, role_id, student_id)
            VALUES ('$username','$email','$phone','$department','$role_id','$student_id')";

            if ($db_handle->query($sql1)) {

                $user_id = mysqli_insert_id($db_handle->conn);

                $sql2 = "INSERT INTO st_login(username,password,user_id)
                         VALUES('$email','$password','$user_id')";

                if ($db_handle->query($sql2)) {

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['role_id'] = $role_id;

                    echo "<script>
                        alert('Registration Successful!');
                        window.parent.closePopup();
                        window.parent.location.href = 'index.php';
                    </script>";
                    exit();

                } else {
                    $message = "Login Insert Error";
                }

            } else {
                $message = "User Insert Error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>STUDENT REGISTRATION</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin-top: 50px;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: transparent;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            background: #0b1f2e;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.6);
        }

        .card h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 1px;
            color: #ffffff;
            text-transform: uppercase;
        }

        .input-group {
            position: relative;
            width: 100%;
            margin-bottom: 15px;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            height: 48px;
            padding: 10px 10px 10px 40px;
            border-radius: 6px;
            border: none;
            background: #1c2f3f;
            color: #fff;
            display: block;
        }

        .input-group input::placeholder {
            color: #aaa;
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            box-shadow: 0 0 6px rgba(30, 144, 255, 0.5);
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .btn-register {
            width: 100%;
            height: 48px;
            background: #0ea5e9;
            color: #fff;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-register:hover {
            background: #0284c7;
        }

        .msg {
            text-align: center;
            margin-bottom: 10px;
            color: #ff6b6b;
            font-size: 14px;
        }
    </style>

    <script>
        window.onload = function () {
            const msg = document.getElementById("msgBox");

            if (msg && msg.innerText.trim() !== "") {
                setTimeout(() => {
                    msg.style.transition = "opacity 0.5s ease";
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

        <h2>STUDENT REGISTRATION</h2>

        <?php if (!empty($message)) { ?>
            <div id="msgBox" class="msg">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="POST">

            <div class="input-group">
                <i class="fa fa-user"></i>
                <input type="text" name="username" placeholder="ENTER USERNAME" required>
            </div>

            <div class="input-group">
                <i class="fa fa-envelope"></i>
                <input type="email" name="email" placeholder="INSTITUTE EMAIL" required>
            </div>

            <div class="input-group">
                <i class="fa fa-phone"></i>
                <input type="text" name="phone" placeholder="PHONE NUMBER" required>
            </div>

            <div class="input-group">
                <i class="fa fa-building"></i>
                <select name="department" required>
                    <option value="">SELECT DEPARTMENT</option>

                    <?php
                    $dept = $db_handle->query("SELECT department_id, department_name FROM st_department_master");
                    while ($row = $dept->fetch_assoc()) {
                        echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="register" class="btn-register">
                REGISTER
            </button>
            

        </form>

    </div>

</body>

</html>