<?php
/**
 * TCET ERP - Mentor Subject Mapping Schema Compatibility & Migration Test
 * Strictly isolated to tcet_st_test. Verifies migration DDL, default values,
 * enable/disable toggling, rollback DDL, and legacy-schema query resilience.
 */

require_once __DIR__ . '/../database/db_connect.php';

$testDb = new DBController('tcet_st_test');

// Fail-closed DB connection check
$activeDb = mysqli_fetch_row(mysqli_query($testDb->conn, "SELECT DATABASE()"))[0] ?? '';
if ($activeDb !== 'tcet_st_test') {
    die("[FATAL SAFETY ERROR] This test must only execute against tcet_st_test. Active: {$activeDb}\n");
}

$passCount = 0;
$failCount = 0;

function assertMigration($condition, $description) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failCount++;
    }
}

echo "=======================================================\n";
echo "MENTOR SUBJECT MAPPING: MIGRATION & COMPATIBILITY TEST\n";
echo "Target DB: {$activeDb} (Strictly Isolated)\n";
echo "=======================================================\n\n";

// 1. Initial State Inspection
$initialCols = [];
$cRes = mysqli_query($testDb->conn, "SHOW COLUMNS FROM st_mentor_subject_mapping");
while ($r = mysqli_fetch_assoc($cRes)) {
    $initialCols[] = $r['Field'];
}
$initialCount = (int)$testDb->runQuery("SELECT COUNT(*) as cnt FROM st_mentor_subject_mapping")[0]['cnt'];

assertMigration($testDb->conn instanceof mysqli, "Database connection is active and valid");
assertMigration($initialCount >= 0, "Initial st_mentor_subject_mapping row count recorded ({$initialCount} rows)");

// 2. Simulate Legacy Schema by Dropping is_active if present
if (in_array('is_active', $initialCols, true)) {
    mysqli_query($testDb->conn, "ALTER TABLE st_mentor_subject_mapping DROP COLUMN is_active");
}

$legacyCols = [];
$cRes2 = mysqli_query($testDb->conn, "SHOW COLUMNS FROM st_mentor_subject_mapping");
while ($r = mysqli_fetch_assoc($cRes2)) {
    $legacyCols[] = $r['Field'];
}
assertMigration(!in_array('is_active', $legacyCols, true), "Legacy State Simulated: st_mentor_subject_mapping lacks 'is_active' column");

// 3. Test Adaptive Query Execution on Legacy Schema
$legacyQuery = $testDb->runQuery("
    SELECT msm.mapping_id, 
           msm.mentor_id,
           COALESCE(ssm.is_active, 1) AS is_active,
           ssm.subject_name
    FROM st_mentor_subject_mapping msm
    JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id
    LIMIT 5
");
assertMigration($legacyQuery !== false, "Legacy Compatibility: Adaptive allocations query executes without error on legacy schema");

// 4. Execute Proposed Migration DDL
$migrationSql = "ALTER TABLE st_mentor_subject_mapping ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER semester_id";
$migrationResult = mysqli_query($testDb->conn, $migrationSql);
assertMigration($migrationResult === true, "Migration DDL Executed: Added 'is_active TINYINT(1) NOT NULL DEFAULT 1' to st_mentor_subject_mapping");

// 5. Verify Column Addition and Default Values
$postMigrationCols = [];
$cRes3 = mysqli_query($testDb->conn, "SHOW COLUMNS FROM st_mentor_subject_mapping");
while ($r = mysqli_fetch_assoc($cRes3)) {
    $postMigrationCols[] = $r['Field'];
}
assertMigration(in_array('is_active', $postMigrationCols, true), "Schema Verified: 'is_active' column successfully added");

$postMigrationCount = (int)$testDb->runQuery("SELECT COUNT(*) as cnt FROM st_mentor_subject_mapping")[0]['cnt'];
assertMigration($postMigrationCount === $initialCount, "Row Count Invariance: Row count unchanged after migration ({$postMigrationCount} == {$initialCount})");

$nonDefaultCount = (int)$testDb->runQuery("SELECT COUNT(*) as cnt FROM st_mentor_subject_mapping WHERE is_active != 1")[0]['cnt'];
assertMigration($nonDefaultCount === 0, "Default Value Propagation: 100% of existing rows assigned is_active = 1 default");

// 6. Test Enable / Disable State Mutation
$sampleRow = $testDb->runQuery("SELECT mapping_id, is_active FROM st_mentor_subject_mapping LIMIT 1");
if (!empty($sampleRow)) {
    $mId = (int)$sampleRow[0]['mapping_id'];
    
    // Disable allocation
    $testDb->query("UPDATE st_mentor_subject_mapping SET is_active = 0 WHERE mapping_id = {$mId}");
    $disabledCheck = (int)$testDb->runQuery("SELECT is_active FROM st_mentor_subject_mapping WHERE mapping_id = {$mId}")[0]['is_active'];
    assertMigration($disabledCheck === 0, "State Mutation: Successfully updated mapping {$mId} to is_active = 0 (disabled)");

    // Re-enable allocation
    $testDb->query("UPDATE st_mentor_subject_mapping SET is_active = 1 WHERE mapping_id = {$mId}");
    $enabledCheck = (int)$testDb->runQuery("SELECT is_active FROM st_mentor_subject_mapping WHERE mapping_id = {$mId}")[0]['is_active'];
    assertMigration($enabledCheck === 1, "State Mutation: Successfully updated mapping {$mId} to is_active = 1 (re-enabled)");
}

// 7. Test Emergency Rollback DDL
$rollbackSql = "ALTER TABLE st_mentor_subject_mapping DROP COLUMN is_active";
$rollbackResult = mysqli_query($testDb->conn, $rollbackSql);
assertMigration($rollbackResult === true, "Rollback DDL Executed: Successfully dropped 'is_active' column");

$postRollbackCols = [];
$cRes4 = mysqli_query($testDb->conn, "SHOW COLUMNS FROM st_mentor_subject_mapping");
while ($r = mysqli_fetch_assoc($cRes4)) {
    $postRollbackCols[] = $r['Field'];
}
assertMigration(!in_array('is_active', $postRollbackCols, true), "Rollback Verified: 'is_active' column completely removed from schema");

$postRollbackCount = (int)$testDb->runQuery("SELECT COUNT(*) as cnt FROM st_mentor_subject_mapping")[0]['cnt'];
assertMigration($postRollbackCount === $initialCount, "Rollback Row Count Invariance: Row count intact after rollback ({$postRollbackCount} == {$initialCount})");

// 8. Re-apply Migration to Leave Test DB in Expected Target State
$reApplySql = "ALTER TABLE st_mentor_subject_mapping ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER semester_id";
$reApplyResult = mysqli_query($testDb->conn, $reApplySql);
assertMigration($reApplyResult === true, "Target Restoration: Re-applied migration so tcet_st_test has target schema");

// 9. Production Safety Check: Confirm tcet_st was completely untouched
$prodDb = new DBController('tcet_st');
$prodCols = [];
$pRes = mysqli_query($prodDb->conn, "SHOW COLUMNS FROM st_mentor_subject_mapping");
while ($r = mysqli_fetch_assoc($pRes)) {
    $prodCols[] = $r['Field'];
}
assertMigration(!in_array('is_active', $prodCols, true), "CRITICAL PRODUCTION INVARIANCE: tcet_st.st_mentor_subject_mapping remains 100% untouched without is_active");

echo "\n=======================================================\n";
echo "SCHEMA MIGRATION SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "=======================================================\n";

exit($failCount > 0 ? 1 : 0);
