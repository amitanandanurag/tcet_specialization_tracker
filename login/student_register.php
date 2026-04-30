<?php
session_start();
require "../database/db_connect.php";

$db_handle = new DBController();

$message = "";

// ================= REGISTER LOGIC =================
if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = $_POST['department'];

    // VALIDATIONS
    if (!preg_match("/^[A-Za-z_ ]{3,20}$/", $username)) {
        $message = "Username must be 3-20 characters (letters, space, underscore)";
    }
    elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $message = "Phone must be 10 digits";
    }
    elseif (strpos($email, "@tcetmumbai.in") === false) {
        $message = "Use institute email only!";
    }
    else {

        $username = mysqli_real_escape_string($db_handle->conn, $username);
        $email = mysqli_real_escape_string($db_handle->conn, $email);
        $phone = mysqli_real_escape_string($db_handle->conn, $phone);
        $department = mysqli_real_escape_string($db_handle->conn, $department);

        $password = "Tcet@1234";
        $role_id = 5;

        $check = $db_handle->query("SELECT * FROM st_user_master WHERE email_id='$email'");

        if ($check && mysqli_num_rows($check) > 0) {
            $message = "Email already exists!";
        } else {

            $sql1 = "INSERT INTO st_user_master 
            (user_name, email_id, phone_number, department_id, role_id, student_id)
            VALUES ('$username','$email','$phone','$department','$role_id','0')";

            if ($db_handle->query($sql1)) {

                $user_id = mysqli_insert_id($db_handle->conn);

                $sql2 = "INSERT INTO st_login(username,password,role_id,user_id)
                         VALUES('$email','$password','$role_id','$user_id')";

                if ($db_handle->query($sql2)) {

                    // ✅ STORE IN SESSION (SHOW ONCE)
                    $_SESSION['show_credentials'] = true;
                    $_SESSION['registered_username'] = $email;
                    $_SESSION['registered_password'] = $password;

                    header("Location: " . $_SERVER['PHP_SELF']);
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
<title>Student Registration</title>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* RESET */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* BACKGROUND */
body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

    font-family: 'Segoe UI', sans-serif;

    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
}

/* CARD (main form container) */
.card {
    background: #162733;
    padding: 35px 30px;

    width: 100%;
    max-width: 380px;

    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.6);
}

/* TITLE */
h2 {
    color: #fff;
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
}

/* INPUT GROUP */
.input-group {
    position: relative;
    width: 100%;
    margin-bottom: 16px;
}

.input-group i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
    font-size: 14px;
}


.input-group input,
.input-group select {
    width: 100%;
    height: 48px;

    padding: 10px 12px 10px 40px;

    border-radius: 8px;
    border: none;

    background: #e9eef3;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);

    outline: none;
}


button {
    width: 100%;
    height: 48px;

    margin-top: 10px;

    background: #0ea5e9;
    color: #fff;

    border: none;
    border-radius: 8px;

    font-weight: 600;
    font-size: 15px;

    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #0284c7;
}

#msgBox {
    color: #ff6b6b;
    text-align: center;
    margin-bottom: 12px;
}

#credentialsBox {
    background: #1c2f3f;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;

    color: #fff;
    text-align: center;
}


.overlay {
    position: fixed;
    top: 0;
    left: 0;

    width: 100%;
    height: 100%;

    display: flex;
    justify-content: center;
    align-items: center;

    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(6px);

    z-index: 1000;
}

.popup {
    width: 420px;
    max-width: 90%;

    padding: 30px;

    border-radius: 14px;
    background: #162733;

    box-shadow: 0px 20px 50px rgba(0,0,0,0.6);

    position: relative;
}

.popup span {
    position: absolute;
    top: 12px;
    right: 15px;

    font-size: 20px;
    color: #ff4d6d;

    cursor: pointer;
}

.popup h2 {
    color: #fff;
    margin-bottom: 20px;
}

.popup button {
    background: #0ea5e9;
}

.popup button:hover {
    background: #0284c7;
}

@media (max-width: 480px) {

    .card,
    .popup {
        padding: 25px 20px;
    }

    h2 {
        font-size: 18px;
    }
}
</style>

<script>
function copyCredentials() {
    const text = document.getElementById("credText").innerText;
    navigator.clipboard.writeText(text);

    alert("Copied!");

    document.getElementById("credentialsBox").style.display = "none";
}
</script>

</head>

<body>

<div class="card">

<h2>STUDENT REGISTRATION</h2>

<!-- MESSAGE -->
<?php if (!empty($message)) { ?>
<div id="msgBox"><?php echo $message; ?></div>
<?php } ?>

<!-- CREDENTIAL BOX (SHOW ONCE) -->
<?php if (isset($_SESSION['show_credentials']) && $_SESSION['show_credentials']) { ?>
<div id="credentialsBox">

    <p style="color:#4ade80;"><b>Registration Successful</b></p>

    <div id="credText">
        Username: <?php echo $_SESSION['registered_username']; ?><br>
        Password: <?php echo $_SESSION['registered_password']; ?>
    </div>

    <button onclick="copyCredentials()">Copy Credentials</button>

</div>

<?php
unset($_SESSION['show_credentials']);
unset($_SESSION['registered_username']);
unset($_SESSION['registered_password']);
?>
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
            echo '<option value="'.$row['department_id'].'">'.$row['department_name'].'</option>';
        }
        ?>
    </select>
</div>

<button type="submit" name="register">REGISTER</button>

</form>

</div>

</body>
</html>