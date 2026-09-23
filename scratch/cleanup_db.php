
<?php
$server = @mysqli_connect('localhost', 'root', '');
if (!$server) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}

mysqli_query($server, "SET FOREIGN_KEY_CHECKS = 0");

$res = mysqli_query($server, "SHOW TABLES FROM `tcet_st`");
if ($res) {
    while ($r = mysqli_fetch_row($res)) {
        $table = $r[0];
        $dropRes = mysqli_query($server, "DROP TABLE IF EXISTS `tcet_st`.`$table`");
        if ($dropRes) {
            echo "Dropped $table\n";
        } else {
            echo "Failed to drop $table: " . mysqli_error($server) . "\n";
        }
    }
} else {
    echo "SHOW TABLES failed: " . mysqli_error($server) . "\n";
}

// Now attempt drop database again
$dropDb = mysqli_query($server, "DROP DATABASE IF EXISTS `tcet_st`");
if ($dropDb) {
    echo "Successfully dropped database tcet_st!\n";
} else {
    echo "Database drop failed: " . mysqli_error($server) . "\n";
}
