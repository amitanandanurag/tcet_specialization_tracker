<?php
$server = @mysqli_connect('localhost', 'root', '');
if (!$server) {
    die("Could not connect to MySQL server: " . mysqli_connect_error() . "\n");
}

echo "Dropping database tcet_st if exists...\n";
if (mysqli_query($server, "DROP DATABASE IF EXISTS `tcet_st`")) {
    echo "Database dropped successfully.\n";
} else {
    echo "Drop failed: " . mysqli_error($server) . "\n";
}

echo "Creating database tcet_st...\n";
if (mysqli_query($server, "CREATE DATABASE `tcet_st` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    echo "Database created successfully.\n";
} else {
    echo "Create failed: " . mysqli_error($server) . "\n";
}
