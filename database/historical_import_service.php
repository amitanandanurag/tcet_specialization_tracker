<?php
/**
 * TCET Specialization Tracker — Historical Actual Student Master Import Service
 * Enterprise idempotent import handler with transaction safety, duplicate detection,
 * mandatory field enforcement, and audit ledger tracking.
 */

if (!defined('APP_SECURITY_CHECK') && !class_exists('DBController')) {
    require_once __DIR__ . '/db_connect.php';
}

class HistoricalImportService {
    private mysqli $conn;

    public function __construct(?mysqli $conn = null) {
        if ($conn instanceof mysqli) {
            $this->conn = $conn;
        } else {
            $db = new DBController();
            $this->conn = $db->conn;
        }
    }

    /**
     * Pre-flight analysis of candidates across the historical archive.
     * Evaluates completeness, duplicates, and existing student matches without altering data.
     */
    public function analyzeCandidates(array $filters = []): array {
        $where = " WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['department_id'])) {
            $where .= " AND hc.cur_department_id = ?";
            $params[] = intval($filters['department_id']);
            $types .= "i";
        }
        if (!empty($filters['class_id'])) {
            $where .= " AND hc.class_id = ?";
            $params[] = intval($filters['class_id']);
            $types .= "i";
        }
        if (!empty($filters['academic_approval_status'])) {
            $where .= " AND hc.academic_approval_status = ?";
            $params[] = trim($filters['academic_approval_status']);
            $types .= "s";
        }

        $sql = "SELECT 
            hc.candidate_id,
            hc.staging_id,
            hc.cur_erp_id,
            hc.cur_student_name,
            hc.cur_department_id,
            hc.class_id,
            hc.cur_division_id,
            hc.semester_id,
            hc.academic_year_id,
            hc.cur_roll_no,
            hc.cur_cgpa,
            hc.cur_track_id,
            hc.cur_specialization_id,
            hc.cur_spec_subject_id,
            hc.academic_approval_status,
            hc.eligibility_status,
            hc.import_status,
            hc.promoted_student_id,
            sm.student_id AS existing_student_id,
            sm.fname AS existing_student_name
        FROM st_historical_student_candidates hc
        LEFT JOIN st_student_master sm ON (
            hc.cur_erp_id IS NOT NULL 
            AND TRIM(hc.cur_erp_id) != '' 
            AND sm.registration_no = hc.cur_erp_id
        )
        $where
        ORDER BY hc.candidate_id ASC";

        if (!empty($params)) {
            $stmt = mysqli_prepare($this->conn, $sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = mysqli_query($this->conn, $sql);
        }

        $analysis = [
            'total_candidates'           => 0,
            'already_imported'           => 0,
            'ready_for_import'           => 0,
            'missing_mandatory_count'    => 0,
            'duplicate_erp_count'        => 0,
            'existing_student_overlap'   => 0,
            'status_breakdown'           => ['HOLD' => 0, 'PENDING' => 0, 'CONDITIONAL' => 0, 'APPROVED' => 0, 'REJECTED' => 0],
            'missing_reasons'            => [
                'missing_erp'            => 0,
                'missing_division'       => 0,
                'missing_class'          => 0,
                'missing_name'           => 0
            ],
            'duplicate_erps'             => [],
            'overlap_records'            => [],
            'eligible_candidate_ids'     => []
        ];

        // Track seen ERP IDs in this pass to flag intra-archive duplicates
        $erpCounts = [];
        $rows = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $analysis['total_candidates']++;
            $st = strtoupper($row['academic_approval_status'] ?? 'PENDING');
            if (isset($analysis['status_breakdown'][$st])) {
                $analysis['status_breakdown'][$st]++;
            }

            $erp = trim((string)($row['cur_erp_id'] ?? ''));
            if ($erp !== '') {
                $erpCounts[$erp] = ($erpCounts[$erp] ?? 0) + 1;
            }
            $rows[] = $row;
        }

        // Second pass: Categorize each candidate
        foreach ($rows as $row) {
            $cid = intval($row['candidate_id']);
            $erp = trim((string)($row['cur_erp_id'] ?? ''));
            $name = trim((string)($row['cur_student_name'] ?? ''));
            $classId = intval($row['class_id'] ?? 0);
            $divId = intval($row['cur_division_id'] ?? 0);
            $isImported = ($row['import_status'] === 'IMPORTED' || !empty($row['promoted_student_id']));
            $hasExistingStudent = !empty($row['existing_student_id']);

            if ($isImported) {
                $analysis['already_imported']++;
                continue;
            }

            if ($hasExistingStudent) {
                $analysis['existing_student_overlap']++;
                $analysis['overlap_records'][] = [
                    'candidate_id'  => $cid,
                    'staging_id'    => $row['staging_id'],
                    'erp_id'        => $erp,
                    'candidate_name'=> $name,
                    'existing_id'   => $row['existing_student_id'],
                    'existing_name' => $row['existing_student_name']
                ];
                continue;
            }

            // Check missing mandatory fields
            $hasMissing = false;
            if ($erp === '') {
                $analysis['missing_reasons']['missing_erp']++;
                $hasMissing = true;
            }
            if ($divId <= 0) {
                $analysis['missing_reasons']['missing_division']++;
                $hasMissing = true;
            }
            if ($classId <= 0) {
                $analysis['missing_reasons']['missing_class']++;
                $hasMissing = true;
            }
            if ($name === '') {
                $analysis['missing_reasons']['missing_name']++;
                $hasMissing = true;
            }

            if ($hasMissing) {
                $analysis['missing_mandatory_count']++;
                continue;
            }

            // Check duplicate ERP in archive
            if (!empty($erpCounts[$erp]) && $erpCounts[$erp] > 1) {
                $analysis['duplicate_erp_count']++;
                $analysis['duplicate_erps'][$erp][] = [
                    'candidate_id' => $cid,
                    'staging_id'   => $row['staging_id'],
                    'name'         => $name
                ];
                continue;
            }

            // Clean candidate ready for import
            $analysis['ready_for_import']++;
            $analysis['eligible_candidate_ids'][] = $cid;
        }

        return $analysis;
    }

    /**
     * Executes idempotent import of specified candidate IDs into st_student_master.
     * Guaranteed transaction atomicity with full rollback on unhandled error.
     */
    public function importCandidates(array $candidate_ids, int $userId = 1, string $username = 'Super Admin'): array {
        if (empty($candidate_ids)) {
            return [
                'success'         => false,
                'message'         => 'No historical candidate IDs provided for import.',
                'imported_count'  => 0,
                'skipped_count'   => 0,
                'duplicate_count' => 0,
                'details'         => []
            ];
        }

        // Sanitize IDs
        $cleanIds = [];
        foreach ($candidate_ids as $id) {
            $val = intval($id);
            if ($val > 0) $cleanIds[$val] = $val;
        }
        $cleanIds = array_values($cleanIds);

        if (empty($cleanIds)) {
            return [
                'success'         => false,
                'message'         => 'No valid positive candidate IDs found in request.',
                'imported_count'  => 0,
                'skipped_count'   => 0,
                'duplicate_count' => 0,
                'details'         => []
            ];
        }

        $idListStr = implode(',', $cleanIds);
        $fetchSql = "SELECT * FROM st_historical_student_candidates WHERE candidate_id IN ($idListStr) ORDER BY candidate_id ASC";
        $fetchRes = mysqli_query($this->conn, $fetchSql);
        if (!$fetchRes) {
            return [
                'success' => false,
                'message' => 'Failed to query historical candidates: ' . mysqli_error($this->conn),
                'imported_count' => 0,
                'skipped_count'  => 0,
                'duplicate_count'=> 0
            ];
        }

        $candidates = [];
        while ($r = mysqli_fetch_assoc($fetchRes)) {
            $candidates[] = $r;
        }

        // Prepared statements for insertion and updates
        $insertStudentSql = "INSERT INTO st_student_master (
            registration_no, class_id, division_id, department_id, current_semester_id,
            academic_year_id, roll_no, specialization_track_id, specialization_id,
            specialization_subject_id, minor_course_id, minor_subject_id, cgpa,
            fname, mark_list, status, m_sem1, m_sem2, m_sem3, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, '', '', '', NOW())";

        $insertHistorySql = "INSERT INTO st_student_semester_history (
            student_id, academic_year_id, class_id, semester_id, department_id,
            specialization_track_id, division_id, roll_no, specialization_id,
            specialization_subject_id, minor_course_id, minor_subject_id,
            cgpa, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())";

        $updateCandidateSql = "UPDATE st_historical_student_candidates 
            SET import_status = 'IMPORTED', 
                promoted_student_id = ?, 
                promoted_at = NOW() 
            WHERE candidate_id = ?";

        $insertAuditSql = "INSERT INTO st_historical_candidate_audit (
            candidate_id, action_type, field_name, old_value, new_value,
            approving_authority, remarks, performed_by_user_id, performed_by_username, performed_at
        ) VALUES (?, 'STATUS_CHANGE', 'import_status', ?, 'IMPORTED', 'Super Admin', ?, ?, ?, NOW())";

        $checkExistingStudentStmt = mysqli_prepare($this->conn, "SELECT student_id, fname FROM st_student_master WHERE registration_no = ? LIMIT 1");
        $stmtStudent = mysqli_prepare($this->conn, $insertStudentSql);
        $stmtHist = mysqli_prepare($this->conn, $insertHistorySql);
        $stmtUpdateCand = mysqli_prepare($this->conn, $updateCandidateSql);
        $stmtAudit = mysqli_prepare($this->conn, $insertAuditSql);

        if (!$stmtStudent || !$stmtHist || !$stmtUpdateCand || !$stmtAudit || !$checkExistingStudentStmt) {
            return [
                'success' => false,
                'message' => 'Failed preparing database statements: ' . mysqli_error($this->conn)
            ];
        }

        mysqli_begin_transaction($this->conn);

        $imported = [];
        $skipped = [];
        $duplicates = [];
        $batchErpSet = [];

        try {
            foreach ($candidates as $cand) {
                $cid = intval($cand['candidate_id']);
                $stagingId = $cand['staging_id'];
                $erp = trim((string)($cand['cur_erp_id'] ?? ''));
                $name = trim((string)($cand['cur_student_name'] ?? ''));
                $classId = intval($cand['class_id'] ?? 0);
                $divId = intval($cand['cur_division_id'] ?? 0);
                $deptId = !empty($cand['cur_department_id']) ? intval($cand['cur_department_id']) : null;
                $semId = !empty($cand['semester_id']) ? intval($cand['semester_id']) : null;
                $ayId = !empty($cand['academic_year_id']) ? intval($cand['academic_year_id']) : null;
                $rollNo = trim((string)($cand['cur_roll_no'] ?? ''));
                $trackId = !empty($cand['cur_track_id']) ? intval($cand['cur_track_id']) : null;
                $specId = !empty($cand['cur_specialization_id']) ? intval($cand['cur_specialization_id']) : null;
                $specSubId = !empty($cand['cur_spec_subject_id']) ? intval($cand['cur_spec_subject_id']) : null;
                $minorCourseId = !empty($cand['cur_minor_course_id']) ? intval($cand['cur_minor_course_id']) : null;
                $minorSubId = !empty($cand['cur_minor_subject_id']) ? intval($cand['cur_minor_subject_id']) : null;
                $cgpa = ($cand['cur_cgpa'] !== null && $cand['cur_cgpa'] !== '') ? floatval($cand['cur_cgpa']) : null;
                $appStatus = $cand['academic_approval_status'] ?? 'PENDING';
                $oldImportStatus = $cand['import_status'] ?? 'NOT_IMPORTED';

                // 1. Idempotency check: Already imported?
                if ($oldImportStatus === 'IMPORTED' || !empty($cand['promoted_student_id'])) {
                    $skipped[] = [
                        'candidate_id' => $cid,
                        'staging_id'   => $stagingId,
                        'erp_id'       => $erp,
                        'reason'       => 'ALREADY_IMPORTED',
                        'existing_id'  => $cand['promoted_student_id']
                    ];
                    continue;
                }

                // 2. Mandatory Fields Validation
                if ($erp === '' || $name === '' || $classId <= 0 || $divId <= 0) {
                    $missingItems = [];
                    if ($erp === '') $missingItems[] = 'ERP ID';
                    if ($name === '') $missingItems[] = 'Student Name';
                    if ($classId <= 0) $missingItems[] = 'Class';
                    if ($divId <= 0) $missingItems[] = 'Division';

                    $skipped[] = [
                        'candidate_id' => $cid,
                        'staging_id'   => $stagingId,
                        'erp_id'       => $erp ?: '(Blank)',
                        'reason'       => 'MISSING_MANDATORY_FIELDS (' . implode(', ', $missingItems) . ')'
                    ];
                    continue;
                }

                // 3. Duplicate ERP in current batch
                if (isset($batchErpSet[$erp])) {
                    $duplicates[] = [
                        'candidate_id' => $cid,
                        'staging_id'   => $stagingId,
                        'erp_id'       => $erp,
                        'reason'       => 'DUPLICATE_ERP_IN_BATCH'
                    ];
                    continue;
                }

                // 4. Overlap with existing student in st_student_master
                mysqli_stmt_bind_param($checkExistingStudentStmt, 's', $erp);
                mysqli_stmt_execute($checkExistingStudentStmt);
                $existRes = mysqli_stmt_get_result($checkExistingStudentStmt);
                if ($existRow = mysqli_fetch_assoc($existRes)) {
                    $skipped[] = [
                        'candidate_id' => $cid,
                        'staging_id'   => $stagingId,
                        'erp_id'       => $erp,
                        'reason'       => 'MATCHES_EXISTING_STUDENT_MASTER',
                        'existing_id'  => $existRow['student_id'],
                        'student_name' => $existRow['fname']
                    ];
                    continue;
                }

                // 5. Insert into st_student_master
                $markListRef = "HISTORICAL_IMPORTED:cand_{$cid}:{$stagingId}";
                $stmtStudent->execute([
                    $erp, $classId, $divId, $deptId, $semId,
                    $ayId, $rollNo, $trackId, $specId,
                    $specSubId, $minorCourseId, $minorSubId, $cgpa,
                    $name, $markListRef
                ]);

                $newStudentId = intval(mysqli_insert_id($this->conn));
                if ($newStudentId <= 0) {
                    throw new RuntimeException("Failed generating new student_id for candidate {$stagingId}");
                }

                // 6. Insert semester progression history
                $stmtHist->execute([
                    $newStudentId, $ayId, $classId, $semId, $deptId,
                    $trackId, $divId, $rollNo, $specId,
                    $specSubId, $minorCourseId, $minorSubId, $cgpa
                ]);

                // 7. Academic enrollment distinction
                // Only create active st_enrollment row if status was explicitly APPROVED
                if ($appStatus === 'APPROVED' && !empty($specId)) {
                    require_once __DIR__ . '/enrollment_service.php';
                    $enrollService = new EnrollmentService($this->conn);
                    $enrollRes = $enrollService->createEnrollment([
                        'student_id'                => $newStudentId,
                        'department_id'             => $deptId ?: 1,
                        'academic_year_id'          => $ayId ?: 1,
                        'semester_id'               => $semId ?: 4,
                        'class_id'                  => $classId,
                        'division_id'               => $divId,
                        'specialization_track_id'   => $trackId,
                        'specialization_id'         => $specId,
                        'specialization_subject_id' => $specSubId,
                        'minor_course_id'           => $minorCourseId,
                        'minor_subject_id'          => $minorSubId,
                        'status'                    => 'Active',
                        'created_by'                => $userId
                    ]);
                    if (!$enrollRes['success']) {
                        throw new RuntimeException("EnrollmentService failure for {$stagingId}: " . $enrollRes['error']);
                    }
                }

                // 8. Update archive reference
                $stmtUpdateCand->execute([$newStudentId, $cid]);

                // 9. Append audit ledger entry
                $auditRemark = "Idempotent actual student master record created. New Student ID: {$newStudentId}. Status: {$appStatus}.";
                $stmtAudit->execute([$cid, $oldImportStatus, $auditRemark, $userId, $username]);

                $batchErpSet[$erp] = $newStudentId;
                $imported[] = [
                    'candidate_id'   => $cid,
                    'staging_id'     => $stagingId,
                    'student_id'     => $newStudentId,
                    'erp_id'         => $erp,
                    'student_name'   => $name,
                    'approval_status'=> $appStatus
                ];
            }

            mysqli_commit($this->conn);

            return [
                'success'         => true,
                'message'         => sprintf("Import completed: %d candidates imported, %d skipped, %d duplicate ERPs skipped.", count($imported), count($skipped), count($duplicates)),
                'imported_count'  => count($imported),
                'skipped_count'   => count($skipped),
                'duplicate_count' => count($duplicates),
                'imported'        => $imported,
                'skipped'         => $skipped,
                'duplicates'      => $duplicates
            ];

        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            error_log("[HISTORICAL_IMPORT_ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'success'         => false,
                'message'         => 'Import transaction aborted and rolled back: ' . $e->getMessage(),
                'imported_count'  => 0,
                'skipped_count'   => count($skipped),
                'duplicate_count' => count($duplicates),
                'error'           => $e->getMessage()
            ];
        }
    }
}
