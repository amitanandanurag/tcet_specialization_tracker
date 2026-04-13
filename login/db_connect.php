<?php

/* Database connection start */


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sainathcollege";

// $servername = "dignityitsolution.ipagemysql.com.";
// $username = "dignitysms";
// $password = "dignity@sms";
// $dbname = "dignitysms";

$conn = mysqli_connect($servername, $username, $password, $dbname) or die("Connection failed: " . mysqli_connect_error());
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
if ($conn) {
  echo "string";
}
?>