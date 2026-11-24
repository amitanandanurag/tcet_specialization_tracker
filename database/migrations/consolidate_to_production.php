<?php
/**
 * TCET_ST — Authoritative Production Database Consolidation Engine
 *
 * Consolidates all verified historical candidates (878), conflict registers (41),
 * excluded duplicates (5), import batches (2), audit history (24), active students (13),
 * and essential system mappings from tcet_st_test into the designated target database.
 *
 * Usage:
 *   php database/migrations/consolidate_to_production.php --dry-run --target=tcet_st_dryrun_verify
 *   php database/migrations/consolidate_to_production.php --apply --target=tcet_st_dryrun_verify
 *   php database/migrations/consolidate_to_production.php --apply --target=tcet_st
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

$options = getopt('', ['dry-run', 'apply', 'target::', 'source::', 'help']);
if (isset($options['help'])) {
    echo "Usage: php consolidate_to_production.php [--dry-run|--apply] [--target=tcet_st|tcet_st_dryrun_verify] [--source=tcet_st_test]\n";
    exit(0);
}

$isApply = isset($options['apply']);
$isDryRun = !$isApply || isset($options['dry-run']);
$targetDb = trim((string)($options['target'] ?? ''));
$sourceDb = trim((string)($options['source'] ?? 'tcet_st_test'));

$allowedTargets = ['tcet_st_dryrun_verify', 'tcet_st'];
if (!in_array($targetDb, $allowedTargets, true)) {
    fwrite(STDERR, "FATAL: Invalid target database '{$targetDb}'. Must be one of: " . implode(', ', $allowedTargets) . "\n");
    exit(1);
}

echo "====================================================================\n";
echo "   TCET_ST DATABASE CONSOLIDATION ENGINE\n";
echo "====================================================================\n";
echo "Source Database: {$sourceDb}\n";
echo "Target Database: {$targetDb}\n";
echo "Execution Mode:  " . ($isDryRun ? "DRY-RUN (Simulation, Rollback)" : "APPLY (Commit to Database)") . "\n";
echo "Timestamp:       " . date('Y-m-d H:i:s') . "\n";
echo "====================================================================\n\n";

$mysqli = new mysqli('127.0.0.1', 'root', '', '');
if ($mysqli->connect_error) {
    fwrite(STDERR, "FATAL: Database connection failed: " . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

// 1. Verify source database readiness
$srcCheck = $mysqli->query("SELECT COUNT(*) FROM `{$sourceDb}`.st_historical_student_candidates");
if (!$srcCheck) {
    fwrite(STDERR, "FATAL: Source database {$sourceDb} does not have st_historical_student_candidates table.\n");
    exit(1);
}
$srcCandidates = intval($srcCheck->fetch_row()[0]);
if ($srcCandidates !== 878) {
    fwrite(STDERR, "FATAL: Source database {$sourceDb} contains {$srcCandidates} candidates, expected exactly 878.\n");
    exit(1);
}
echo "[✓] Source database {$sourceDb} verified: 878 historical candidates present.\n";

// 2. Begin transaction on target
$mysqli->select_db($targetDb);
$mysqli->begin_transaction();

try {
    // 2.1 Ensure st_schema_version exists and ensure column alignments
    $schemaVerSql = "CREATE TABLE IF NOT EXISTS `{$targetDb}`.`st_schema_version` (
      `version_id` int(11) NOT NULL AUTO_INCREMENT,
      `version_tag` varchar(64) NOT NULL,
      `migration_name` varchar(150) NOT NULL,
      `checksum` varchar(64) NOT NULL,
      `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `applied_by` varchar(100) NOT NULL DEFAULT 'consolidate_to_production.php',
      `execution_time_ms` int(11) DEFAULT NULL,
      `status` enum('APPLIED','ROLLED_BACK','FAILED') NOT NULL DEFAULT 'APPLIED',
      PRIMARY KEY (`version_id`),
      UNIQUE KEY `uq_schema_version_tag` (`version_tag`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $mysqli->query($schemaVerSql);

    // Schema alignment: ensure department_id and is_active exist on st_mentor_subject_mapping
    $mysqli->query("ALTER TABLE `{$targetDb}`.`st_mentor_subject_mapping` 
        ADD COLUMN IF NOT EXISTS `department_id` int(11) NOT NULL DEFAULT 0 AFTER `mentor_id`,
        ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `semester_id`");

    // 2.2 Migrate st_historical_import_batches
    echo "\n--- Migrating st_historical_import_batches ---\n";
    $batchRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_historical_import_batches ORDER BY batch_id ASC");
    $batchesCopied = 0;
    while ($row = $batchRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_historical_import_batches (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE import_batch_ref=VALUES(import_batch_ref)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert batch: " . $mysqli->error);
        }
        $batchesCopied++;
    }
    echo "Batches migrated/verified: {$batchesCopied}\n";

    // 2.3 Migrate st_historical_student_candidates
    echo "\n--- Migrating st_historical_student_candidates (878 canonical records) ---\n";
    $candRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_historical_student_candidates ORDER BY candidate_id ASC");
    $candsCopied = 0;
    while ($row = $candRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_historical_student_candidates (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE cur_student_name=VALUES(cur_student_name), cur_erp_id=VALUES(cur_erp_id)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert candidate {$row['candidate_id']}: " . $mysqli->error);
        }
        $candsCopied++;
    }
    echo "Candidates migrated/verified: {$candsCopied}\n";

    // 2.4 Migrate st_historical_conflict_register (41 records)
    echo "\n--- Migrating st_historical_conflict_register (41 records) ---\n";
    $confRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_historical_conflict_register ORDER BY conflict_register_id ASC");
    $confCopied = 0;
    while ($row = $confRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_historical_conflict_register (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE resolution_id=VALUES(resolution_id)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert conflict {$row['conflict_register_id']}: " . $mysqli->error);
        }
        $confCopied++;
    }
    echo "Conflicts migrated/verified: {$confCopied}\n";

    // 2.5 Migrate st_historical_excluded_duplicates (5 records)
    echo "\n--- Migrating st_historical_excluded_duplicates (5 records) ---\n";
    $dupRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_historical_excluded_duplicates ORDER BY excluded_dup_id ASC");
    $dupCopied = 0;
    while ($row = $dupRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_historical_excluded_duplicates (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE reconcile_id=VALUES(reconcile_id)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert duplicate {$row['excluded_dup_id']}: " . $mysqli->error);
        }
        $dupCopied++;
    }
    echo "Excluded duplicates migrated/verified: {$dupCopied}\n";

    // 2.6 Migrate st_historical_candidate_audit (24 records)
    echo "\n--- Migrating st_historical_candidate_audit (24 records) ---\n";
    $audRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_historical_candidate_audit ORDER BY hist_audit_id ASC");
    $audCopied = 0;
    while ($row = $audRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT IGNORE INTO `{$targetDb}`.st_historical_candidate_audit (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert audit {$row['hist_audit_id']}: " . $mysqli->error);
        }
        $audCopied++;
    }
    echo "Audit logs migrated/verified: {$audCopied}\n";

    // 2.7 Integrate User Accounts and Role Logins (Must precede mentor/coord mappings due to FK)
    echo "\n--- Integrating User Accounts and Role Logins ---\n";
    $userRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_user_master WHERE user_id > 2 ORDER BY user_id ASC");
    $usersAdded = 0;
    while ($row = $userRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_user_master (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE user_name=VALUES(user_name), role_id=VALUES(role_id)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to integrate user {$row['user_id']}: " . $mysqli->error);
        }
        $usersAdded++;
    }
    echo "Additional role users integrated: {$usersAdded}\n";

    // Add logins for UAT / test roles
    $loginRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_login ORDER BY login_id ASC");
    $loginsAdded = 0;
    while ($row = $loginRes->fetch_assoc()) {
        $uName = $mysqli->real_escape_string($row['username']);
        $chk = $mysqli->query("SELECT login_id FROM `{$targetDb}`.st_login WHERE username = '{$uName}'");
        if ($chk && $chk->num_rows > 0) {
            continue; // Already exists
        }
        $pwd = $mysqli->real_escape_string($row['password']);
        $uId = intval($row['user_id']);
        $sql = "INSERT INTO `{$targetDb}`.st_login (username, password, user_id, created_at)
                VALUES ('{$uName}', '{$pwd}', {$uId}, NOW())";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to integrate login {$uName}: " . $mysqli->error);
        }
        $loginsAdded++;
    }
    echo "Logins added/verified: {$loginsAdded}\n";

    // 2.8 Migrate st_student_master (13 active enrolled students)
    echo "\n--- Migrating st_student_master (13 active enrolled students) ---\n";
    $stuRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_student_master ORDER BY student_id ASC");
    $stuCopied = 0;
    while ($row = $stuRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_student_master (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE fname=VALUES(fname), registration_no=VALUES(registration_no)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert student {$row['student_id']}: " . $mysqli->error);
        }
        $stuCopied++;
    }
    echo "Active students migrated/verified: {$stuCopied}\n";

    // 2.9 Migrate st_student_semester_history (1 record)
    echo "\n--- Migrating st_student_semester_history ---\n";
    $histRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_student_semester_history ORDER BY history_id ASC");
    $histCopied = 0;
    while ($row = $histRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_student_semester_history (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE semester_id=VALUES(semester_id)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert semester history {$row['history_id']}: " . $mysqli->error);
        }
        $histCopied++;
    }
    echo "Semester history records migrated: {$histCopied}\n";

    // 2.10 Migrate st_coordinator (1 record)
    echo "\n--- Migrating st_coordinator ---\n";
    $coordRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_coordinator ORDER BY coordinator_id ASC");
    $coordCopied = 0;
    while ($row = $coordRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_coordinator (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE login_id=VALUES(login_id)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert coordinator {$row['coordinator_id']}: " . $mysqli->error);
        }
        $coordCopied++;
    }
    echo "Coordinators migrated/verified: {$coordCopied}\n";

    // 2.11 Migrate st_mentor_subject_mapping (6 records)
    echo "\n--- Migrating st_mentor_subject_mapping ---\n";
    $mapRes = $mysqli->query("SELECT * FROM `{$sourceDb}`.st_mentor_subject_mapping ORDER BY mapping_id ASC");
    $mapCopied = 0;
    while ($row = $mapRes->fetch_assoc()) {
        $cols = array_keys($row);
        $escapedCols = array_map(fn($c) => "`$c`", $cols);
        $escapedVals = array_map(function($v) use ($mysqli) {
            return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO `{$targetDb}`.st_mentor_subject_mapping (" . implode(',', $escapedCols) . ") 
                VALUES (" . implode(',', $escapedVals) . ")
                ON DUPLICATE KEY UPDATE mentor_id=VALUES(mentor_id), subject_id=VALUES(subject_id)";
        if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to insert mentor mapping {$row['mapping_id']}: " . $mysqli->error);
        }
        $mapCopied++;
    }
    echo "Mentor subject mappings migrated/verified: {$mapCopied}\n";

    // 2.12 Record consolidation version tag in st_schema_version
    $verSql = "INSERT INTO `{$targetDb}`.st_schema_version 
               (version_tag, migration_name, checksum, applied_by, status)
               VALUES ('CONSOLIDATION_V1.0', 'consolidate_to_production.php', 
                       '5ebb57829938f6d887b18865c812ea22a2f58808dec082eaea29044696d3fbd3', 
                       'consolidate_to_production.php', 'APPLIED')
               ON DUPLICATE KEY UPDATE status='APPLIED'";
    $mysqli->query($verSql);

    // 3. Reconcile Target Database Post-Consolidation
    echo "\n====================================================================\n";
    echo "   POST-CONSOLIDATION DATA RECONCILIATION AUDIT\n";
    echo "====================================================================\n";

    $tCands = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_historical_student_candidates")->fetch_row()[0]);
    $tSem4 = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_historical_student_candidates WHERE semester_id = 4")->fetch_row()[0]);
    $tSem6 = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_historical_student_candidates WHERE semester_id = 6")->fetch_row()[0]);
    $tMissingErp = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_historical_student_candidates WHERE cur_erp_id IS NULL OR cur_erp_id = ''")->fetch_row()[0]);
    $tMissingCgpa = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_historical_student_candidates WHERE cur_cgpa IS NULL")->fetch_row()[0]);
    $tConflicts = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_historical_conflict_register")->fetch_row()[0]);
    $tDuplicates = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_historical_excluded_duplicates")->fetch_row()[0]);
    $tStudents = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_student_master")->fetch_row()[0]);
    $tStudentsActive = intval($mysqli->query("SELECT COUNT(*) FROM `{$targetDb}`.st_student_master WHERE status = 1")->fetch_row()[0]);

    echo sprintf("Total Historical Candidates: %d (Expected: 878) -> %s\n", $tCands, $tCands === 878 ? 'PASS' : 'FAIL');
    echo sprintf(" - SEM IV Candidates:        %d (Expected: 600) -> %s\n", $tSem4, $tSem4 === 600 ? 'PASS' : 'FAIL');
    echo sprintf(" - SEM VI Candidates:        %d (Expected: 278) -> %s\n", $tSem6, $tSem6 === 278 ? 'PASS' : 'FAIL');
    echo sprintf(" - Missing ERP Candidates:   %d (Expected: 438) -> %s\n", $tMissingErp, $tMissingErp === 438 ? 'PASS' : 'FAIL');
    echo sprintf(" - Missing CGPA Candidates:  %d (Expected: 311) -> %s\n", $tMissingCgpa, $tMissingCgpa === 311 ? 'PASS' : 'FAIL');
    echo sprintf("Academic Conflict Register:  %d (Expected: 41)  -> %s\n", $tConflicts, $tConflicts === 41 ? 'PASS' : 'FAIL');
    echo sprintf("Excluded Duplicate Register: %d (Expected: 5)   -> %s\n", $tDuplicates, $tDuplicates === 5 ? 'PASS' : 'FAIL');
    echo sprintf("Active Student Master Rows:  %d (Expected: 13)  -> %s\n", $tStudents, $tStudents === 13 ? 'PASS' : 'FAIL');
    echo sprintf("Active Enrolled (status = 1):%d (Expected: 13)  -> %s\n", $tStudentsActive, $tStudentsActive === 13 ? 'PASS' : 'FAIL');

    if ($tCands !== 878 || $tSem4 !== 600 || $tSem6 !== 278 || $tConflicts !== 41 || $tDuplicates !== 5 || $tStudents !== 13) {
        throw new RuntimeException("Reconciliation counts did not match expected baseline!");
    }

    if ($isDryRun) {
        $mysqli->rollback();
        echo "\n[✓] DRY-RUN SUCCESSFUL: All tables verified and transaction rolled back safely.\n";
        exit(0);
    } else {
        $mysqli->commit();
        echo "\n[✓] CONSOLIDATION APPLIED: Transaction committed successfully to {$targetDb}!\n";
        exit(0);
    }

} catch (Exception $e) {
    $mysqli->rollback();
    fwrite(STDERR, "\nFATAL CONSOLIDATION ERROR: " . $e->getMessage() . "\nTransaction rolled back safely.\n");
    exit(1);
}
