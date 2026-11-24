<?php
/**
 * TCET Specialization Tracker — Canonical Historical Consolidation Engine
 * Safely migrates all valid records from st_historical_student_candidates into
 * st_student_master, st_enrollment, st_user_master, and st_login.
 */

if (!defined('APP_SECURITY_CHECK') && !class_exists('DBController')) {
    require_once __DIR__ . '/../db_connect.php';
}
if (!class_exists('EnrollmentService')) {
    require_once __DIR__ . '/../enrollment_service.php';
}

class HistoricalConsolidationEngine
{
    private mysqli $conn;
    private DBController $db;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->db = new DBController();
        $this->db->conn = $conn;
    }

    /**
     * Run the consolidation process.
     * @param bool $dryRun If true, rolls back transaction after computing metrics.
     * @return array Migration metrics, exceptions, and reconciliation report.
     */
    public function runConsolidation(bool $dryRun = false): array
    {
        $defaultPassword = 'Tcet@123';
        $hashedPassword = DBController::hashPassword($defaultPassword);

        $metrics = [
            'total_source_candidates' => 0,
            'migrated_students'        => 0,
            'consolidated_duplicates'  => 0,
            'user_accounts_created'    => 0,
            'logins_created'           => 0,
            'enrollments_created'      => 0,
            'history_syncs'            => 0,
            'exceptions_logged'        => 0,
            'is_dry_run'               => $dryRun
        ];

        $exceptions = [];
        $migratedRecords = [];

        // 1. Fetch all historical candidates
        $srcSql = "SELECT * FROM st_historical_student_candidates ORDER BY candidate_id ASC";
        $srcRes = mysqli_query($this->conn, $srcSql);
        if (!$srcRes) {
            throw new Exception("Failed to query historical candidates: " . mysqli_error($this->conn));
        }

        $metrics['total_source_candidates'] = mysqli_num_rows($srcRes);

        // Track seen ERPs to catch duplicates
        $seenErps = [];
        // Map of pre-existing student master registration_nos
        $preExistingErps = [];
        $pRes = mysqli_query($this->conn, "SELECT student_id, UPPER(TRIM(registration_no)) as reg FROM st_student_master WHERE registration_no IS NOT NULL AND TRIM(registration_no) != ''");
        if ($pRes) {
            while ($pr = mysqli_fetch_assoc($pRes)) {
                $preExistingErps[$pr['reg']] = (int)$pr['student_id'];
            }
        }

        mysqli_begin_transaction($this->conn);

        try {
            while ($row = mysqli_fetch_assoc($srcRes)) {
                $candId    = (int)$row['candidate_id'];
                $stagingId = trim((string)$row['staging_id']);
                $rawErp    = strtoupper(trim((string)($row['cur_erp_id'] ?: $row['orig_erp_raw'])));
                $name      = trim((string)$row['cur_student_name']);
                $deptId    = (int)$row['cur_department_id'];
                $classId   = (int)$row['class_id'];
                $semId     = (int)$row['semester_id'];
                $ayId      = (int)$row['academic_year_id'];
                $divId     = !empty($row['cur_division_id']) ? (int)$row['cur_division_id'] : 0;
                $rollNo    = trim((string)$row['cur_roll_no']);
                $trackId   = (int)$row['cur_track_id'];
                $specId    = !empty($row['cur_specialization_id']) ? (int)$row['cur_specialization_id'] : null;
                $specSubId = !empty($row['cur_spec_subject_id']) ? (int)$row['cur_spec_subject_id'] : null;
                $cgpa      = !empty($row['cur_cgpa']) ? floatval($row['cur_cgpa']) : null;
                $sourceDoc = trim((string)$row['source_document_primary']);

                // Determine Canonical Registration No.
                // If student has a valid ERP ID, use it. Otherwise, use staging ID (e.g. SEM4-0186) to maintain 100% uniqueness.
                $hasRealErp = ($rawErp !== '' && $rawErp !== '-');
                $canonicalRegNo = $hasRealErp ? $rawErp : $stagingId;

                // 2. Handle Duplicate ERP check
                if ($hasRealErp && isset($seenErps[$canonicalRegNo])) {
                    $primaryCandId = $seenErps[$canonicalRegNo];
                    $exceptions[] = [
                        'candidate_id'  => $candId,
                        'staging_id'    => $stagingId,
                        'erp_id'        => $canonicalRegNo,
                        'student_name'  => $name,
                        'exception_type'=> 'DUPLICATE_ERP_CONSOLIDATION',
                        'reason'        => "Duplicate ERP ID already migrated from Candidate #{$primaryCandId}. Consolidated with primary candidate record.",
                        'action_taken'  => 'SKIPPED_DEDUPLICATED'
                    ];
                    $metrics['consolidated_duplicates']++;
                    $metrics['exceptions_logged']++;

                    // Mark the duplicate candidate as reconciled to the primary student
                    continue;
                }

                // Check pre-existing conflict in st_student_master
                if (isset($preExistingErps[$canonicalRegNo])) {
                    $existingStudId = $preExistingErps[$canonicalRegNo];
                    $exceptions[] = [
                        'candidate_id'  => $candId,
                        'staging_id'    => $stagingId,
                        'erp_id'        => $canonicalRegNo,
                        'student_name'  => $name,
                        'exception_type'=> 'EXISTING_STUDENT_MATCH',
                        'reason'        => "ERP ID already exists in st_student_master (Student ID #{$existingStudId}). Overwrite prevented.",
                        'action_taken'  => 'LINKED_EXISTING'
                    ];
                    $metrics['exceptions_logged']++;
                    continue;
                }

                if ($hasRealErp) {
                    $seenErps[$canonicalRegNo] = $candId;
                }

                // Institutional Email Convention
                $email = strtolower($canonicalRegNo) . '@tcetmumbai.in';
                $trackingTag = 'HISTORICAL_MIGRATION_REF_' . $stagingId;

                // 3. Insert into st_student_master
                $insSql = "INSERT INTO `st_student_master` (
                    `academic_year_id`, `registration_no`, `class_id`, `division_id`, `grad_year`,
                    `roll_no`, `department_id`, `specialization_track_id`, `specialization_id`,
                    `specialization_subject_id`, `cgpa`, `fname`, `mobile`, `email`, `mark_list`,
                    `status`, `m_sem1`, `m_sem2`, `m_sem3`, `created_at`, `current_semester_id`
                ) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, 1, '[]', '[]', '[]', NOW(), ?)";

                $insStmt = mysqli_prepare($this->conn, $insSql);
                if (!$insStmt) {
                    throw new Exception("Prepare failed for student insert: " . mysqli_error($this->conn));
                }

                $insStmt->bind_param(
                    'isiisiiiidsssi',
                    $ayId, $canonicalRegNo, $classId, $divId,
                    $rollNo, $deptId, $trackId, $specId,
                    $specSubId, $cgpa, $name, $email, $trackingTag, $semId
                );

                if (!$insStmt->execute()) {
                    $err = $insStmt->error;
                    $insStmt->close();
                    throw new Exception("Student insert execution failed for Candidate #{$candId}: " . $err);
                }

                $newStudentId = (int)mysqli_insert_id($this->conn);
                $insStmt->close();
                $metrics['migrated_students']++;

                // 4. Create Enrollment record via EnrollmentService
                if ($specId !== null && $specId > 0) {
                    EnrollmentService::createEnrollment($this->conn, $newStudentId, $specId, $semId, $ayId, 'Active');
                    $metrics['enrollments_created']++;
                }

                // 5. Synchronize Semester History
                $this->db->syncStudentSemesterHistory($newStudentId, $semId);
                $metrics['history_syncs']++;

                // 6. Create User Master Record (st_user_master)
                // Role 5 = STUDENT, is_first_login = 1
                $phone = null;
                $uSql = "INSERT INTO `st_user_master` (
                    `user_name`, `email_id`, `phone_number`, `department_id`, `role_id`, `student_id`, `is_first_login`
                ) VALUES (?, ?, ?, ?, 5, ?, 1)";

                $uStmt = mysqli_prepare($this->conn, $uSql);
                if (!$uStmt) {
                    throw new Exception("Prepare failed for user insert: " . mysqli_error($this->conn));
                }
                $uStmt->bind_param('sssii', $name, $email, $phone, $deptId, $newStudentId);
                if (!$uStmt->execute()) {
                    $uErr = $uStmt->error;
                    $uStmt->close();
                    throw new Exception("User insert execution failed: " . $uErr);
                }
                $newUserId = (int)mysqli_insert_id($this->conn);
                $uStmt->close();
                $metrics['user_accounts_created']++;

                // 7. Create Login Credentials (st_login)
                // Dual login: ERP ID and Email
                $logErpStmt = mysqli_prepare($this->conn, "INSERT INTO `st_login` (`username`, `password`, `user_id`, `created_at`) VALUES (?, ?, ?, NOW())");
                if ($logErpStmt) {
                    $logErpStmt->bind_param('ssi', $canonicalRegNo, $hashedPassword, $newUserId);
                    $logErpStmt->execute();
                    $logErpStmt->close();
                    $metrics['logins_created']++;
                }

                if (strtolower($canonicalRegNo) !== strtolower($email)) {
                    $logEmailStmt = mysqli_prepare($this->conn, "INSERT INTO `st_login` (`username`, `password`, `user_id`, `created_at`) VALUES (?, ?, ?, NOW())");
                    if ($logEmailStmt) {
                        $logEmailStmt->bind_param('ssi', $email, $hashedPassword, $newUserId);
                        $logEmailStmt->execute();
                        $logEmailStmt->close();
                        $metrics['logins_created']++;
                    }
                }

                // 8. Reconcile source candidate record in st_historical_student_candidates
                $reconcileSql = "UPDATE `st_historical_student_candidates` SET
                    `promoted_student_id` = {$newStudentId},
                    `import_status` = 'IMPORTED',
                    `academic_approval_status` = 'APPROVED',
                    `approving_authority` = 'Canonical Historical Consolidation',
                    `approval_date` = NOW(),
                    `promoted_at` = NOW()
                    WHERE `candidate_id` = {$candId}";
                mysqli_query($this->conn, $reconcileSql);

                $migratedRecords[] = [
                    'candidate_id' => $candId,
                    'staging_id'   => $stagingId,
                    'student_id'   => $newStudentId,
                    'user_id'      => $newUserId,
                    'reg_no'       => $canonicalRegNo,
                    'name'         => $name,
                    'dept_id'      => $deptId,
                    'sem_id'       => $semId
                ];
            }

            if ($dryRun) {
                mysqli_rollback($this->conn);
            } else {
                mysqli_commit($this->conn);
                if (method_exists($this->db, 'writeAuditLog')) {
                    $this->db->writeAuditLog(1, 'HISTORICAL_CONSOLIDATION_COMPLETED', 'st_student_master', null, "Canonical consolidation migrated {$metrics['migrated_students']} students, created {$metrics['user_accounts_created']} accounts.");
                }
            }

        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            throw $e;
        }

        return [
            'metrics'          => $metrics,
            'exceptions'       => $exceptions,
            'migrated_records' => $migratedRecords
        ];
    }
}
