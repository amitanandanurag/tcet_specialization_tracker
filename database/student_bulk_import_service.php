<?php
/**
 * TCET Specialization Tracker — Student Bulk Import Service
 * Enterprise-grade Excel / CSV ingestion pipeline.
 * Features:
 * - PhpSpreadsheet parsing with formula injection protection
 * - Fast in-memory master dictionary caching
 * - Four-category row validation: Valid, Duplicate, Missing Info, Invalid
 * - Transactional insertion into st_student_master
 * - Automatic enrollment via EnrollmentService
 * - Semester progression history synchronization
 * - Automatic dual student login provisioning (st_user_master & st_login)
 * - Reconciliation with historical candidates
 * - Idempotency & Conflict Safety
 */

if (!defined('APP_SECURITY_CHECK') && !class_exists('DBController')) {
    require_once __DIR__ . '/db_connect.php';
}
if (!class_exists('EnrollmentService')) {
    require_once __DIR__ . '/enrollment_service.php';
}
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StudentBulkImportService
{
    private mysqli $conn;
    private DBController $db;

    // In-memory lookup caches
    private array $departments = [];
    private array $academicYears = [];
    private array $classes = [];
    private array $divisions = [];
    private array $semesters = [];
    private array $tracks = [];
    private array $specializations = [];
    private array $existingErpIds = [];
    private array $existingEmails = [];
    private array $existingUsernames = [];

    public function __construct(?mysqli $conn = null)
    {
        if ($conn instanceof mysqli) {
            $this->conn = $conn;
            $this->db = new DBController();
            $this->db->conn = $conn;
        } else {
            $this->db = new DBController();
            $this->conn = $this->db->conn;
        }
        $this->loadMasterDictionaries();
    }

    /**
     * Load all academic master lookup dictionaries into fast in-memory structures.
     */
    public function loadMasterDictionaries(): void
    {
        // 1. Departments: map department_name and aliases to department_id
        $this->departments = [];
        $dRes = mysqli_query($this->conn, "SELECT department_id, department_name FROM st_department_master");
        if ($dRes) {
            while ($d = mysqli_fetch_assoc($dRes)) {
                $id = (int)$d['department_id'];
                $name = strtoupper(trim((string)$d['department_name']));
                $this->departments[$name] = $id;
                $this->departments[strval($id)] = $id;
            }
        }
        // Aliases
        $this->departments['COMP'] = $this->departments['CE'] ?? 3;
        $this->departments['COMPUTER'] = $this->departments['CE'] ?? 3;
        $this->departments['COMPUTER ENGINEERING'] = $this->departments['CE'] ?? 3;
        $this->departments['INFORMATION TECHNOLOGY'] = $this->departments['IT'] ?? 4;

        // 2. Academic Years: map session_name to session_id
        $this->academicYears = [];
        $yRes = mysqli_query($this->conn, "SELECT session_id, session_name FROM st_session_master");
        if ($yRes) {
            while ($y = mysqli_fetch_assoc($yRes)) {
                $id = (int)$y['session_id'];
                $sName = trim((string)$y['session_name']);
                $normalized = strtolower(preg_replace('/\s+/', '', $sName));
                $this->academicYears[$normalized] = $id;
                $this->academicYears[strval($id)] = $id;
            }
        }

        // 3. Classes: map class_name to class_id
        $this->classes = [];
        $cRes = mysqli_query($this->conn, "SELECT class_id, class_name FROM st_class_master");
        if ($cRes) {
            while ($c = mysqli_fetch_assoc($cRes)) {
                $id = (int)$c['class_id'];
                $cName = strtoupper(trim((string)$c['class_name']));
                $this->classes[$cName] = $id;
                $this->classes[strval($id)] = $id;
            }
        }

        // 4. Divisions: map division_name to division_id
        $this->divisions = [];
        $divRes = mysqli_query($this->conn, "SELECT division_id, division_name FROM st_division_master");
        if ($divRes) {
            while ($div = mysqli_fetch_assoc($divRes)) {
                $id = (int)$div['division_id'];
                $dName = strtoupper(trim((string)$div['division_name']));
                $this->divisions[$dName] = $id;
                $this->divisions[strval($id)] = $id;
            }
        }

        // 5. Semesters: 1-8
        $this->semesters = [];
        $sRes = mysqli_query($this->conn, "SELECT semester_id, semester_name FROM st_semester_master");
        if ($sRes) {
            while ($s = mysqli_fetch_assoc($sRes)) {
                $id = (int)$s['semester_id'];
                $this->semesters[strval($id)] = $id;
                $this->semesters['SEMESTER ' . $id] = $id;
                $this->semesters['SEM ' . $id] = $id;
            }
        }

        // 6. Specialization Tracks & Domains
        $this->tracks = [
            'HONOURS DEGREE' => 1,
            'HONOURS' => 1,
            'TRACK 1' => 1,
            'HONOURS WITH RESEARCH' => 2,
            'HONOURS RESEARCH' => 2,
            'TRACK 2' => 2,
            'MINOR DEGREE' => 3,
            'MINOR' => 3,
            'TRACK 3' => 3,
            'MINOR MULTIDISCIPLINARY' => 4,
            'TRACK 4' => 4,
        ];

        $this->specializations = [];
        $spRes = mysqli_query($this->conn, "SELECT specialization_id, specialization_name FROM st_specialization_master WHERE is_track = 0");
        if ($spRes) {
            while ($sp = mysqli_fetch_assoc($spRes)) {
                $id = (int)$sp['specialization_id'];
                $sName = strtoupper(trim((string)$sp['specialization_name']));
                $this->specializations[$sName] = $id;
                $this->specializations[strval($id)] = $id;
                // Simplified aliases
                $cleanName = preg_replace('/[^A-Z0-9]/', '', $sName);
                $this->specializations[$cleanName] = $id;
            }
        }

        // 7. Existing ERP IDs in st_student_master
        $this->existingErpIds = [];
        $erpRes = mysqli_query($this->conn, "SELECT student_id, UPPER(TRIM(registration_no)) as erp FROM st_student_master WHERE registration_no IS NOT NULL AND TRIM(registration_no) != ''");
        if ($erpRes) {
            while ($erpRow = mysqli_fetch_assoc($erpRes)) {
                $this->existingErpIds[$erpRow['erp']] = (int)$erpRow['student_id'];
            }
        }

        // 8. Existing Emails & Usernames
        $this->existingEmails = [];
        $this->existingUsernames = [];
        $uRes = mysqli_query($this->conn, "SELECT user_id, LOWER(TRIM(email_id)) as email, UPPER(TRIM(user_name)) as uname FROM st_user_master");
        if ($uRes) {
            while ($u = mysqli_fetch_assoc($uRes)) {
                if (!empty($u['email'])) $this->existingEmails[$u['email']] = (int)$u['user_id'];
                if (!empty($u['uname'])) $this->existingUsernames[$u['uname']] = (int)$u['user_id'];
            }
        }
        $lRes = mysqli_query($this->conn, "SELECT user_id, LOWER(TRIM(username)) as l_user FROM st_login");
        if ($lRes) {
            while ($l = mysqli_fetch_assoc($lRes)) {
                if (!empty($l['l_user'])) {
                    $this->existingUsernames[strtoupper($l['l_user'])] = (int)$l['user_id'];
                }
            }
        }
    }

    /**
     * Safely parse an uploaded file (.xlsx, .xls, .csv) into an array of associative rows.
     * Includes formula injection protection.
     */
    public function parseUploadedFile(string $filePath, string $originalFileName): array
    {
        $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            throw new InvalidArgumentException("Unsupported file format: .$ext. Only .xlsx, .xls, and .csv are permitted.");
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("Uploaded file could not be read from disk.");
        }

        // PhpSpreadsheet reading with read-data-only to disable formula execution
        $reader = IOFactory::createReaderForFile($filePath);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, false, false);

        if (empty($rawRows) || count($rawRows) < 2) {
            throw new RuntimeException("Uploaded spreadsheet is empty or has no data rows beneath the header.");
        }

        // Header mapping (row 0)
        $rawHeaders = array_map(function($h) {
            return strtolower(trim((string)$h));
        }, $rawRows[0]);

        $columnMap = [
            'erp_registration_id' => -1,
            'roll_number'         => -1,
            'student_full_name'   => -1,
            'email_address'       => -1,
            'mobile_number'       => -1,
            'department_code'     => -1,
            'academic_year'       => -1,
            'class'               => -1,
            'semester'            => -1,
            'division'            => -1,
            'specialization_track'=> -1,
            'specialization_name' => -1,
            'cgpa'                => -1,
            'graduation_year'     => -1,
            'faculty_mentor'      => -1,
            'status'              => -1
        ];

        foreach ($rawHeaders as $idx => $hdr) {
            $cleanHdr = preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', $hdr));
            if (strpos($cleanHdr, 'erp') !== false || strpos($cleanHdr, 'registration') !== false) {
                $columnMap['erp_registration_id'] = $idx;
            } elseif (strpos($cleanHdr, 'roll') !== false) {
                $columnMap['roll_number'] = $idx;
            } elseif (strpos($cleanHdr, 'name') !== false || strpos($cleanHdr, 'student') !== false) {
                if ($columnMap['student_full_name'] === -1) $columnMap['student_full_name'] = $idx;
            } elseif (strpos($cleanHdr, 'email') !== false) {
                $columnMap['email_address'] = $idx;
            } elseif (strpos($cleanHdr, 'mobile') !== false || strpos($cleanHdr, 'phone') !== false || strpos($cleanHdr, 'contact') !== false) {
                $columnMap['mobile_number'] = $idx;
            } elseif (strpos($cleanHdr, 'dept') !== false || strpos($cleanHdr, 'branch') !== false) {
                $columnMap['department_code'] = $idx;
            } elseif (strpos($cleanHdr, 'academic') !== false || strpos($cleanHdr, 'session') !== false || strpos($cleanHdr, 'year') !== false) {
                if (strpos($cleanHdr, 'grad') === false && $columnMap['academic_year'] === -1) {
                    $columnMap['academic_year'] = $idx;
                }
            } elseif ($cleanHdr === 'class' || strpos($cleanHdr, 'class') !== false) {
                $columnMap['class'] = $idx;
            } elseif (strpos($cleanHdr, 'sem') !== false) {
                $columnMap['semester'] = $idx;
            } elseif (strpos($cleanHdr, 'div') !== false || strpos($cleanHdr, 'sec') !== false || strpos($cleanHdr, 'batch') !== false) {
                $columnMap['division'] = $idx;
            } elseif (strpos($cleanHdr, 'track') !== false || strpos($cleanHdr, 'category') !== false) {
                $columnMap['specialization_track'] = $idx;
            } elseif (strpos($cleanHdr, 'specialization') !== false || strpos($cleanHdr, 'minor') !== false || strpos($cleanHdr, 'spec') !== false) {
                if ($columnMap['specialization_name'] === -1) $columnMap['specialization_name'] = $idx;
            } elseif (strpos($cleanHdr, 'cgpa') !== false) {
                $columnMap['cgpa'] = $idx;
            } elseif (strpos($cleanHdr, 'grad') !== false) {
                $columnMap['graduation_year'] = $idx;
            } elseif (strpos($cleanHdr, 'mentor') !== false) {
                $columnMap['faculty_mentor'] = $idx;
            } elseif ($cleanHdr === 'status') {
                $columnMap['status'] = $idx;
            }
        }

        // Fallback positional indexing if headers are standard order
        if ($columnMap['erp_registration_id'] === -1 && isset($rawHeaders[0])) $columnMap['erp_registration_id'] = 0;
        if ($columnMap['student_full_name'] === -1 && isset($rawHeaders[2])) $columnMap['student_full_name'] = 2;
        if ($columnMap['email_address'] === -1 && isset($rawHeaders[3])) $columnMap['email_address'] = 3;
        if ($columnMap['department_code'] === -1 && isset($rawHeaders[5])) $columnMap['department_code'] = 5;

        $parsedRows = [];
        $totalDataRows = count($rawRows);

        for ($r = 1; $r < $totalDataRows; $r++) {
            $row = $rawRows[$r];
            // Check if entire row is empty
            $isEmpty = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    $isEmpty = false;
                    break;
                }
            }
            if ($isEmpty) continue;

            $record = [
                'excel_row_num'       => $r + 1,
                'erp_registration_id' => $this->sanitizeCellValue($row[$columnMap['erp_registration_id']] ?? ''),
                'roll_number'         => $this->sanitizeCellValue($row[$columnMap['roll_number']] ?? ''),
                'student_full_name'   => $this->sanitizeCellValue($row[$columnMap['student_full_name']] ?? ''),
                'email_address'       => $this->sanitizeCellValue($row[$columnMap['email_address']] ?? ''),
                'mobile_number'       => $this->sanitizeCellValue($row[$columnMap['mobile_number']] ?? ''),
                'department_code'     => $this->sanitizeCellValue($row[$columnMap['department_code']] ?? ''),
                'academic_year'       => $this->sanitizeCellValue($row[$columnMap['academic_year']] ?? ''),
                'class'               => $this->sanitizeCellValue($row[$columnMap['class']] ?? ''),
                'semester'            => $this->sanitizeCellValue($row[$columnMap['semester']] ?? ''),
                'division'            => $this->sanitizeCellValue($row[$columnMap['division']] ?? ''),
                'specialization_track'=> $this->sanitizeCellValue($row[$columnMap['specialization_track']] ?? ''),
                'specialization_name' => $this->sanitizeCellValue($row[$columnMap['specialization_name']] ?? ''),
                'cgpa'                => $this->sanitizeCellValue($row[$columnMap['cgpa']] ?? ''),
                'graduation_year'     => $this->sanitizeCellValue($row[$columnMap['graduation_year']] ?? ''),
                'faculty_mentor'      => $this->sanitizeCellValue($row[$columnMap['faculty_mentor']] ?? ''),
                'status'              => $this->sanitizeCellValue($row[$columnMap['status']] ?? 'Active')
            ];

            $parsedRows[] = $record;
        }

        return $parsedRows;
    }

    /**
     * Sanitize cell value to prevent CSV / Excel formula injection.
     */
    private function sanitizeCellValue($val): string
    {
        $str = trim((string)$val);
        if ($str === '') return '';
        // If string starts with formula trigger characters, neutralize it
        if (in_array($str[0], ['=', '+', '-', '@'], true)) {
            $str = "'" . $str;
        }
        return $str;
    }

    /**
     * Validate an array of parsed rows into 4 distinct categories:
     * - valid
     * - duplicate
     * - missing_info
     * - invalid_data
     */
    public function validateBatch(array $rows): array
    {
        $results = [
            'valid'        => [],
            'duplicate'    => [],
            'missing_info' => [],
            'invalid_data' => [],
            'summary'      => [
                'total_rows'        => count($rows),
                'valid_count'       => 0,
                'duplicate_count'   => 0,
                'missing_count'     => 0,
                'invalid_count'     => 0
            ]
        ];

        $seenErpsInFile = [];

        foreach ($rows as $row) {
            $rowNum = $row['excel_row_num'];
            $erp = strtoupper(trim($row['erp_registration_id']));
            $name = trim($row['student_full_name']);
            $email = strtolower(trim($row['email_address']));
            $deptRaw = strtoupper(trim($row['department_code']));
            $ayRaw = strtolower(preg_replace('/\s+/', '', trim($row['academic_year'])));
            $classRaw = strtoupper(trim($row['class']));
            $semRaw = trim($row['semester']);
            $divRaw = strtoupper(trim($row['division']));
            $trackRaw = strtoupper(trim($row['specialization_track']));
            $specRaw = strtoupper(trim($row['specialization_name']));
            $cgpaRaw = trim($row['cgpa']);

            $missingFields = [];
            $invalidReasons = [];

            // 1. Mandatory Fields Check
            if ($erp === '') $missingFields[] = 'ERP_Registration_ID';
            if ($name === '') $missingFields[] = 'Student_Full_Name';
            if ($deptRaw === '') $missingFields[] = 'Department_Code';
            if ($ayRaw === '') $missingFields[] = 'Academic_Year';
            if ($classRaw === '') $missingFields[] = 'Class';
            if ($semRaw === '') $missingFields[] = 'Semester';
            if ($divRaw === '') $missingFields[] = 'Division';
            if ($trackRaw === '') $missingFields[] = 'Specialization_Track';

            if (!empty($missingFields)) {
                $row['reasons'] = ['Missing required fields: ' . implode(', ', $missingFields)];
                $results['missing_info'][] = $row;
                $results['summary']['missing_count']++;
                continue;
            }

            // 2. Duplicate Check within the file
            if (isset($seenErpsInFile[$erp])) {
                $row['reasons'] = ["Duplicate ERP ID '$erp' within the uploaded file (First seen on Row {$seenErpsInFile[$erp]})"];
                $results['duplicate'][] = $row;
                $results['summary']['duplicate_count']++;
                continue;
            }
            $seenErpsInFile[$erp] = $rowNum;

            // 3. Existing Student Conflict Check in authoritative Database
            if (isset($this->existingErpIds[$erp])) {
                $existingId = $this->existingErpIds[$erp];
                $row['reasons'] = ["ERP ID '$erp' already exists in the student database (Student ID #{$existingId}). Overwrite is strictly prevented."];
                $results['duplicate'][] = $row;
                $results['summary']['duplicate_count']++;
                continue;
            }

            // 4. Data Validity Lookups
            // Department
            $deptId = $this->departments[$deptRaw] ?? null;
            if (!$deptId) {
                $invalidReasons[] = "Department '$deptRaw' is not recognized in institutional department master.";
            }

            // Academic Year
            $ayId = $this->academicYears[$ayRaw] ?? null;
            if (!$ayId) {
                // Try fallback to active academic year (ID 1) if not matched exactly
                $ayId = 1;
            }

            // Class
            $classId = $this->classes[$classRaw] ?? null;
            if (!$classId) {
                $invalidReasons[] = "Class '$classRaw' is invalid. Accepted values: SE, TE, BE.";
            } elseif ($classRaw === 'FY' || $classRaw === 'FE') {
                $invalidReasons[] = "Specialization is not offered for First Year ($classRaw) students.";
            }

            // Semester
            $semNum = intval(preg_replace('/[^0-9]/', '', $semRaw));
            if ($semNum < 1 || $semNum > 8) {
                $invalidReasons[] = "Semester '$semRaw' must be a numeric semester between 1 and 8.";
            }

            // Division
            $divId = $this->divisions[$divRaw] ?? null;
            if (!$divId) {
                $invalidReasons[] = "Division '$divRaw' is invalid. Accepted divisions: A, B, C, D, E, F.";
            }

            // Specialization Track
            $trackId = $this->tracks[$trackRaw] ?? null;
            if (!$trackId) {
                $invalidReasons[] = "Specialization Track '$trackRaw' is invalid. Accepted: Honours Degree, Honours with Research, Minor Degree, Minor Multidisciplinary.";
            }

            // Specialization Domain (Tracks 1, 2, 3)
            $specId = null;
            if ($trackId === 4) {
                $specId = 4; // Minor Multidisciplinary
            } elseif ($specRaw !== '') {
                $cleanSpec = preg_replace('/[^A-Z0-9]/', '', $specRaw);
                $specId = $this->specializations[$specRaw] ?? ($this->specializations[$cleanSpec] ?? null);
                if (!$specId) {
                    $invalidReasons[] = "Specialization '$specRaw' is not found in master records.";
                }
            } elseif (in_array($trackId, [1, 2, 3], true)) {
                $invalidReasons[] = "Specialization Name is required for Honours or Minor tracks.";
            }

            // CGPA Validation
            $cgpaVal = null;
            if ($cgpaRaw !== '') {
                if (!is_numeric($cgpaRaw)) {
                    $invalidReasons[] = "CGPA '$cgpaRaw' must be a numeric value.";
                } else {
                    $cgpaVal = floatval($cgpaRaw);
                    if ($cgpaVal < 0 || $cgpaVal > 10.0) {
                        $invalidReasons[] = "CGPA '$cgpaRaw' must be between 0.00 and 10.00.";
                    } elseif ($trackId === 1 && $cgpaVal < 7.00) {
                        $invalidReasons[] = "CGPA ($cgpaVal) is below minimum institutional threshold (7.00) for Honours Degree.";
                    } elseif ($trackId === 2 && $cgpaVal < 7.50) {
                        $invalidReasons[] = "CGPA ($cgpaVal) is below minimum institutional threshold (7.50) for Honours with Research.";
                    }
                }
            }

            // Email Format
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidReasons[] = "Email '$email' is not a valid email address.";
            }

            // Mobile Format (if provided)
            $mobileRaw = preg_replace('/[^0-9]/', '', trim($row['mobile_number']));
            if ($mobileRaw !== '' && strlen($mobileRaw) !== 10) {
                $invalidReasons[] = "Mobile number must be exactly 10 digits if provided.";
            }

            // Categorize into Invalid or Valid
            if (!empty($invalidReasons)) {
                $row['reasons'] = $invalidReasons;
                $results['invalid_data'][] = $row;
                $results['summary']['invalid_count']++;
            } else {
                // Resolved IDs attached for fast execution
                $row['resolved_dept_id']    = $deptId;
                $row['resolved_ay_id']      = $ayId;
                $row['resolved_class_id']   = $classId;
                $row['resolved_sem_id']     = $semNum;
                $row['resolved_div_id']     = $divId;
                $row['resolved_track_id']   = $trackId;
                $row['resolved_spec_id']    = $specId;
                $row['resolved_cgpa']       = $cgpaVal;
                $row['resolved_mobile']     = $mobileRaw;
                $row['resolved_email']      = ($email !== '') ? $email : (strtolower($erp) . '@tcetmumbai.in');

                $results['valid'][] = $row;
                $results['summary']['valid_count']++;
            }
        }

        return $results;
    }

    /**
     * Atomically execute insertion of validated student records into authoritative tables.
     * Provisions student master, enrollment, semester progression, and login credentials.
     */
    public function executeImport(array $validRows, int $adminUserId, array $options = []): array
    {
        $batchId = time() . '_' . bin2hex(random_bytes(2));
        $defaultPassword = $options['default_password'] ?? 'Tcet@123';
        $hashedDefaultPassword = DBController::hashPassword($defaultPassword);

        $report = [
            'batch_id'         => $batchId,
            'total_attempted'  => count($validRows),
            'students_created' => 0,
            'logins_created'   => 0,
            'reconciled_hist'  => 0,
            'errors'           => [],
            'imported_students'=> []
        ];

        if (empty($validRows)) {
            return $report;
        }

        foreach ($validRows as $row) {
            $erp = strtoupper(trim($row['erp_registration_id']));
            $name = trim($row['student_full_name']);
            $email = strtolower(trim($row['resolved_email']));
            $mobile = $row['resolved_mobile'];
            $rollNo = trim($row['roll_number']);
            $deptId = (int)$row['resolved_dept_id'];
            $ayId = (int)$row['resolved_ay_id'];
            $classId = (int)$row['resolved_class_id'];
            $semId = (int)$row['resolved_sem_id'];
            $divId = (int)$row['resolved_div_id'];
            $trackId = (int)$row['resolved_track_id'];
            $specId = !empty($row['resolved_spec_id']) ? (int)$row['resolved_spec_id'] : null;
            $cgpa = $row['resolved_cgpa'];
            $gradYear = !empty($row['graduation_year']) ? intval($row['graduation_year']) : null;
            $rowNum = $row['excel_row_num'];

            mysqli_begin_transaction($this->conn);

            try {
                // Secondary Lock Check: Ensure ERP ID was not inserted concurrently
                $lockCheck = mysqli_query($this->conn, "SELECT student_id FROM st_student_master WHERE registration_no = '" . mysqli_real_escape_string($this->conn, $erp) . "' LIMIT 1 FOR UPDATE");
                if ($lockCheck && mysqli_num_rows($lockCheck) > 0) {
                    throw new Exception("ERP ID '$erp' already exists in the database. Duplicate prevented.");
                }

                // 1. Insert into st_student_master
                $insSql = "INSERT INTO `st_student_master` (
                    `academic_year_id`, `registration_no`, `class_id`, `division_id`, `grad_year`,
                    `roll_no`, `department_id`, `specialization_track_id`, `specialization_id`,
                    `cgpa`, `fname`, `mobile`, `email`, `mark_list`, `status`,
                    `m_sem1`, `m_sem2`, `m_sem3`, `created_at`, `current_semester_id`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, '[]', '[]', '[]', NOW(), ?)";

                $insStmt = mysqli_prepare($this->conn, $insSql);
                if (!$insStmt) {
                    throw new Exception("Failed to prepare student insert: " . mysqli_error($this->conn));
                }

                $markListTag = 'EXCEL_MASS_IMPORT_BATCH_' . $batchId;
                $insStmt->bind_param(
                    'isiissiiidssssi',
                    $ayId, $erp, $classId, $divId, $gradYear,
                    $rollNo, $deptId, $trackId, $specId,
                    $cgpa, $name, $mobile, $email, $markListTag, $semId
                );

                if (!$insStmt->execute()) {
                    $err = $insStmt->error;
                    $insStmt->close();
                    throw new Exception("Student insertion failed: " . $err);
                }

                $studentId = (int)mysqli_insert_id($this->conn);
                $insStmt->close();

                // 2. Synchronize Semester History
                $this->db->syncStudentSemesterHistory($studentId, $semId);

                // 3. Create Enrollment record via EnrollmentService
                if ($specId !== null && $specId > 0) {
                    EnrollmentService::createEnrollment($this->conn, $studentId, $specId, $semId, $ayId, 'Active');
                }

                // 4. Create User Master Record (st_user_master)
                // Role 5 = Student; is_first_login = 1 (forces password change on first login)
                $userSql = "INSERT INTO `st_user_master` (
                    `user_name`, `email_id`, `phone_number`, `department_id`, `role_id`, `student_id`, `is_first_login`
                ) VALUES (?, ?, ?, ?, 5, ?, 1)";

                $userStmt = mysqli_prepare($this->conn, $userSql);
                if (!$userStmt) {
                    throw new Exception("Failed to prepare user insert: " . mysqli_error($this->conn));
                }
                $userStmt->bind_param('sssii', $name, $email, $mobile, $deptId, $studentId);
                if (!$userStmt->execute()) {
                    $uErr = $userStmt->error;
                    $userStmt->close();
                    throw new Exception("User master insertion failed: " . $uErr);
                }
                $userId = (int)mysqli_insert_id($this->conn);
                $userStmt->close();

                // 5. Create Dual Login Credentials in st_login (ERP ID & Email)
                // Primary login: ERP Registration No.
                $logErpSql = "INSERT INTO `st_login` (`username`, `password`, `user_id`, `created_at`) VALUES (?, ?, ?, NOW())";
                $logErpStmt = mysqli_prepare($this->conn, $logErpSql);
                if ($logErpStmt) {
                    $logErpStmt->bind_param('ssi', $erp, $hashedDefaultPassword, $userId);
                    $logErpStmt->execute();
                    $logErpStmt->close();
                    $report['logins_created']++;
                }

                // Secondary login: Email (if distinct from ERP ID)
                if (strtolower($erp) !== strtolower($email)) {
                    $logEmailStmt = mysqli_prepare($this->conn, "INSERT INTO `st_login` (`username`, `password`, `user_id`, `created_at`) VALUES (?, ?, ?, NOW())");
                    if ($logEmailStmt) {
                        $logEmailStmt->bind_param('ssi', $email, $hashedDefaultPassword, $userId);
                        $logEmailStmt->execute();
                        $logEmailStmt->close();
                        $report['logins_created']++;
                    }
                }

                // 6. Historical Candidate Reconciliation (if corresponding candidate exists)
                $escErp = mysqli_real_escape_string($this->conn, $erp);
                $reconcileSql = "UPDATE `st_historical_student_candidates` SET
                    `promoted_student_id` = {$studentId},
                    `import_status` = 'IMPORTED',
                    `academic_approval_status` = 'APPROVED',
                    `approving_authority` = 'Super Admin (Excel Mass Upload)',
                    `approval_date` = NOW(),
                    `promoted_at` = NOW()
                    WHERE (`cur_erp_id` = '{$escErp}' OR `orig_erp_raw` = '{$escErp}')
                      AND `import_status` != 'IMPORTED'";
                mysqli_query($this->conn, $reconcileSql);
                if (mysqli_affected_rows($this->conn) > 0) {
                    $report['reconciled_hist']++;
                }

                // 7. Commit Transaction
                mysqli_commit($this->conn);

                // Add to internal lookup cache to prevent intra-batch duplicates
                $this->existingErpIds[$erp] = $studentId;

                // Institutional Audit Log
                if (method_exists($this->db, 'writeAuditLog')) {
                    $this->db->writeAuditLog($adminUserId, 'STUDENT_MASS_IMPORTED', 'st_student_master', $studentId, "Student {$name} ({$erp}) imported via Excel batch {$batchId}");
                }

                $report['students_created']++;
                $report['imported_students'][] = [
                    'row'        => $rowNum,
                    'erp'        => $erp,
                    'name'       => $name,
                    'email'      => $email,
                    'student_id' => $studentId,
                    'user_id'    => $userId,
                    'status'     => 'SUCCESS'
                ];

            } catch (Throwable $e) {
                mysqli_rollback($this->conn);
                $report['errors'][] = [
                    'row'    => $rowNum,
                    'erp'    => $erp,
                    'name'   => $name,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $report;
    }
}
