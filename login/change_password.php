<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

$message = "";
$success = false;

if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $user_id = $_SESSION['user_id'];

    $result = $db_handle->query("SELECT password FROM st_login WHERE user_id='$user_id'");
    $row = $result->fetch_assoc();

    if ($current != $row['password']) {
        $message = "Current password is incorrect!";
    } elseif ($new != $confirm) {
        $message = "Passwords do not match!";
    } else {

        $db_handle->query("UPDATE st_login SET password='$new' WHERE user_id='$user_id'");
        $db_handle->query("UPDATE st_user_master SET is_first_login=0 WHERE user_id='$user_id'");

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Change Password</title>

<style>
body {
    background: #0b1f2e;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    font-family: sans-serif;
}

.card {
    background: #132f4c;
    padding: 30px;
    border-radius: 10px;
    width: 350px;
    text-align: center;
}

h2 {
    color: #fff;
}

input {
    width: 100%;
    height: 45px;
    margin: 10px 0;
    padding: 10px;
    border-radius: 5px;
    border: none;
}

button {
    width: 100%;
    height: 45px;
    background: #0ea5e9;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}

button:hover {
    background: #0284c7;
}

.msg {
    color: #ff6b6b;
    margin-bottom: 10px;
}

.success {
    color: #4ade80;
    margin-bottom: 10px;
}
</style>

<?php if ($success) { ?>
<script>
    alert("Password changed successfully!");
    window.location.href = "index.php";
</script>
<?php } ?>

</head>

<body>

<div class="card">

<h2>Change Password</h2>

<?php if (!empty($message)) { ?>
<div class="msg"><?php echo $message; ?></div>
<?php } ?>

<?php if ($success) { ?>
<div class="success">Password updated successfully. Redirecting...</div>
<?php } ?>

<form method="POST">

<input type="password" name="current_password" placeholder="CURRENT PASSWORD" required>
<input type="password" name="new_password" placeholder="NEW PASSWORD" required>
<input type="password" name="confirm_password" placeholder="CONFIRM PASSWORD" required>

<button type="submit" name="change_password">UPDATE PASSWORD</button>

</form>

</div>

</body>
</html>