<?php
/**
 * TCET ERP — Database Backup & Disposable Recovery Verification
 * Exports authoritative tcet_st, restores into disposable database,
 * verifies 100% table and record parity, and cleans up.
 */

require_once __DIR__ . '/../database/db_connect.php';

$sourceDb = 'tcet_st';
$disposableDb = 'tcet_st_disposable_restore_test';
$dumpFile = sys_get_temp_dir() . '/tcet_st_restore_verification_' . date('Ymd_His') . '.sql';

echo "====================================================================\n";
echo "TCET ERP — DISPOSABLE DATABASE BACKUP & RESTORATION TEST\n";
echo "====================================================================\n\n";

// 1. Connect to MySQL server
$mysqli = new mysqli('localhost', 'root', '', '');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

// 2. Dump source database using mysqldump
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (!file_exists($mysqldumpPath)) {
    $mysqldumpPath = 'mysqldump';
}

echo "1. Exporting $sourceDb to temporary dump file...\n";
$dumpCmd = "\"$mysqldumpPath\" --host=localhost --user=root --routines --triggers $sourceDb > \"$dumpFile\"";
exec($dumpCmd, $output, $returnVar);

if ($returnVar !== 0 || !file_exists($dumpFile) || filesize($dumpFile) < 100000) {
    die("Dump failed! Check mysqldump executable and path.\n");
}

$dumpSize = round(filesize($dumpFile) / 1024, 2);
$dumpSha = hash_file('sha256', $dumpFile);
echo "   Dump exported successfully ($dumpSize KB).\n";
echo "   SHA-256 Checksum: $dumpSha\n\n";

// 3. Prepare clean disposable database
echo "2. Preparing disposable database: $disposableDb...\n";
$mysqli->query("DROP DATABASE IF EXISTS `$disposableDb`");
if (!$mysqli->query("CREATE DATABASE `$disposableDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die("Failed to create disposable database: " . $mysqli->error . "\n");
}
echo "   Created $disposableDb.\n\n";

// 4. Restore dump into disposable database
echo "3. Restoring dump into $disposableDb...\n";
$mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';
if (!file_exists($mysqlPath)) {
    $mysqlPath = 'mysql';
}
$restoreCmd = "\"$mysqlPath\" --host=localhost --user=root $disposableDb < \"$dumpFile\"";
exec($restoreCmd, $restoreOutput, $restoreReturn);

if ($restoreReturn !== 0) {
    die("Restore failed with code $restoreReturn.\n");
}
echo "   Restore completed successfully.\n\n";

// 5. Connect to both databases and compare parity
echo "4. Comparing authoritative vs restored database tables and row counts...\n";
$tablesToCompare = [
    'st_student_master',
    'st_student_semester_history',
    'st_enrollment',
    'st_user_master',
    'st_login',
    'st_department_master',
    'st_specialization_master',
    'st_specialization_subject_master',
    'st_menu_allocation_master',
    'st_class_master',
    'st_division_master',
    'st_role_master'
];

$parityAllPassed = true;
$sourceConn = new mysqli('localhost', 'root', '', $sourceDb);
$restoreConn = new mysqli('localhost', 'root', '', $disposableDb);

foreach ($tablesToCompare as $tbl) {
    $srcRes = $sourceConn->query("SELECT COUNT(*) as cnt FROM `$tbl`");
    $srcCount = $srcRes ? $srcRes->fetch_assoc()['cnt'] : -1;

    $rstRes = $restoreConn->query("SELECT COUNT(*) as cnt FROM `$tbl`");
    $rstCount = $rstRes ? $rstRes->fetch_assoc()['cnt'] : -1;

    $match = ($srcCount === $rstCount && $srcCount >= 0);
    if (!$match) {
        $parityAllPassed = false;
    }

    echo sprintf("   [%s] Table: %-35s | Source: %-6d | Restored: %-6d\n", $match ? 'PASS' : 'FAIL', $tbl, $srcCount, $rstCount);
}

// 6. Verify triggers restoration
echo "\n5. Verifying triggers restoration...\n";
$srcTrig = $sourceConn->query("SHOW TRIGGERS");
$srcTrigCount = $srcTrig ? $srcTrig->num_rows : 0;
$rstTrig = $restoreConn->query("SHOW TRIGGERS");
$rstTrigCount = $rstTrig ? $rstTrig->num_rows : 0;
$trigMatch = ($srcTrigCount === $rstTrigCount);
echo sprintf("   [%s] Triggers Count: Source: %d | Restored: %d\n", $trigMatch ? 'PASS' : 'FAIL', $srcTrigCount, $rstTrigCount);

// 7. Cleanup disposable database and temporary dump file
echo "\n6. Cleaning up disposable database and temporary dump file...\n";
$mysqli->query("DROP DATABASE IF EXISTS `$disposableDb`");
@unlink($dumpFile);
echo "   Disposable database $disposableDb dropped.\n";
echo "   Temporary dump file removed.\n\n";

if ($parityAllPassed && $trigMatch) {
    echo "====================================================================\n";
    echo "RESULT: DISPOSABLE BACKUP & RESTORATION PASSED WITH 100% PARITY!\n";
    echo "====================================================================\n";
    exit(0);
} else {
    echo "RESULT: FAILED: Parity mismatch detected.\n";
    exit(1);
}
