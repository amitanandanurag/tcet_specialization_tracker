<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dataDir = "C:/xampp/mysql/data/tcet_st";
$backupDir = "C:/xampp/mysql/data/tcet_st_backup_" . time();

echo "Step 1: Connecting to MySQL server...\n";
$server = @mysqli_connect('localhost', 'root', '');
if (!$server) {
    die("Failed to connect to MySQL server: " . mysqli_connect_error() . "\n");
}

echo "Step 2: Backing up orphaned ibd files to $backupDir...\n";
if (is_dir($dataDir)) {
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    $files = scandir($dataDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        copy("$dataDir/$f", "$backupDir/$f");
        unlink("$dataDir/$f");
    }
    @rmdir($dataDir);
    echo "Files moved/backed up successfully.\n";
}

echo "Step 3: Dropping & Creating tcet_st database...\n";
mysqli_query($server, "DROP DATABASE IF EXISTS `tcet_st`");
if (!mysqli_query($server, "CREATE DATABASE `tcet_st` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Failed to create database tcet_st: " . mysqli_error($server) . "\n");
}
echo "Database tcet_st created cleanly!\n";

mysqli_select_db($server, 'tcet_st');
mysqli_query($server, "SET FOREIGN_KEY_CHECKS = 0");

echo "Step 4: Importing complete_database.sql...\n";
$sqlFile = __DIR__ . "/../database/complete_database.sql";
$sqlContent = file_get_contents($sqlFile);

// Execute multi_query
if (mysqli_multi_query($server, $sqlContent)) {
    do {
        if ($res = mysqli_store_result($server)) {
            mysqli_free_result($res);
        }
    } while (mysqli_more_results($server) && mysqli_next_result($server));
    echo "complete_database.sql imported successfully!\n";
} else {
    echo "Error importing complete_database.sql: " . mysqli_error($server) . "\n";
}

echo "Step 5: Applying migration 20260827_semester_history.sql if exists...\n";
$migFile = __DIR__ . "/../database/migrations/20260827_semester_history.sql";
if (file_exists($migFile)) {
    $migContent = file_get_contents($migFile);
    if (mysqli_multi_query($server, $migContent)) {
        do {
            if ($res = mysqli_store_result($server)) {
                mysqli_free_result($res);
            }
        } while (mysqli_more_results($server) && mysqli_next_result($server));
        echo "Migration 20260827_semester_history.sql applied!\n";
    } else {
        echo "Migration notice: " . mysqli_error($server) . "\n";
    }
}

echo "Step 6: Verifying table health...\n";
$res = mysqli_query($server, "SHOW TABLES FROM `tcet_st`");
$tables = [];
while ($r = mysqli_fetch_row($res)) {
    $tables[] = $r[0];
}

$working = 0;
$broken = 0;
foreach ($tables as $t) {
    $q = mysqli_query($server, "SELECT COUNT(*) as c FROM `tcet_st`.`$t`");
    if ($q) {
        $row = mysqli_fetch_assoc($q);
        $working++;
        echo " - Table `$t`: OK ({$row['c']} rows)\n";
    } else {
        $broken++;
        echo " - Table `$t`: BROKEN (" . mysqli_error($server) . ")\n";
    }
}

echo "\nSummary: $working tables healthy, $broken tables broken.\n";
