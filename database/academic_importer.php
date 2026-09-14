<?php

if (!class_exists('DBController')) {
    require_once __DIR__ . '/db_connect.php';
}

class AcademicImporter
{
    private $db;
    private $conn;

    // Standard department mapping
    private $deptAliases = [
        'IT' => 4,
        'COMP' => 3,
        'COMP.' => 3,
        'CE' => 3,
        'COMPUTER' => 3,
        'CSE' => 9,
        'CSE CS' => 9,
        'CSE-CS' => 9,
        'CSE_CS' => 9,
        'IOT' => 13,
        'E&TC' => 5,
        'EXTC' => 5,
        'ETC' => 5,
        'MECHANICAL' => 7,
        'MECH' => 7,
        'MME' => 10,
        'AI&DS' => 12,
        'AIDS' => 12,
        'AI&ML' => 2,
        'AIML' => 2,
        'CIVIL' => 8,
        'ECS' => 6,
        'E&CS' => 6,
        'MCA' => 1,
        'BCA' => 11
    ];

    public function __construct($db = null)
    {
        if ($db instanceof DBController) {
            $this->db = $db;
            $this->conn = $db->conn;
        } else {
            $this->db = new DBController();
            $this->conn = $this->db->conn;
        }
    }

    /**
     * Resolve department ID from various string representations
     */
    public function resolveDepartmentId($deptStr)
    {
        $raw = trim((string)$deptStr);
        if ($raw === '') return null;

        $clean = strtoupper(preg_replace('/[^a-zA-Z0-9&]/', '', $raw));
        if (isset($this->deptAliases[$clean])) {
            return $this->deptAliases[$clean];
        }
        $upper = strtoupper($raw);
        if (isset($this->deptAliases[$upper])) {
            return $this->deptAliases[$upper];
        }

        if (strpos($clean, 'AIDS') !== false || strpos($clean, 'AI&DS') !== false) return 12;
        if (strpos($clean, 'AIML') !== false || strpos($clean, 'AI&ML') !== false) return 2;
        if (strpos($clean, 'CSE') !== false) return 9;
        if (strpos($clean, 'COMP') !== false || strpos($clean, 'CE') !== false) return 3;
        if (strpos($clean, 'EXTC') !== false || strpos($clean, 'E&TC') !== false || strpos($clean, 'ETC') !== false) return 5;
        if (strpos($clean, 'MECH') !== false) return 7;
        if (strpos($clean, 'MME') !== false) return 10;
        if (strpos($clean, 'IOT') !== false) return 13;
        if (strpos($clean, 'ECS') !== false || strpos($clean, 'E&CS') !== false) return 6;
        if (strpos($clean, 'CIVIL') !== false) return 8;
        if (strpos($clean, 'IT') !== false) return 4;

        return null;
    }

    /**
     * Resolve division ID (1=A, 2=B, 3=C, 4=D, 5=E, 6=F)
     */
    public function resolveDivisionId($divStr)
    {
        $raw = strtoupper(trim((string)$divStr));
        if ($raw === '') return 1;

        if (preg_match('/\b([A-F])\b/', $raw, $m)) {
            return ord($m[1]) - ord('A') + 1;
        }
        if (preg_match('/([A-F])$/', $raw, $m)) {
            return ord($m[1]) - ord('A') + 1;
        }
        return 1;
    }

    /**
     * Resolve class ID from semester
     */
    public function resolveClassIdForSemester($semesterId)
    {
        switch (intval($semesterId)) {
            case 1:
            case 2:
                return 4; // FE
            case 3:
            case 4:
                return 5; // SE
            case 5:
            case 6:
                return 6; // TE
            case 7:
            case 8:
                return 7; // BE
            default:
                return 5;
        }
    }

    /**
     * Normalize boolean Yes/No values
     */
    public function normalizeBoolean($val)
    {
        $str = strtolower(trim((string)$val));
        if (in_array($str, ['yes', 'y', '1', 'true', 'passed', 'pass'], true)) {
            return true;
        }
        if (in_array($str, ['no', 'n', '0', 'false', 'failed', 'fail'], true)) {
            return false;
        }
        return null;
    }

    /**
     * Normalize Subject Name for consistent display and matching
     */
    public function normalizeSubjectName($subjectRaw)
    {
        $clean = trim(preg_replace('/\s+/', ' ', (string)$subjectRaw));
        if (stripos($clean, 'Quantum') !== false) {
            return 'Quantum Technology';
        }
        if (stripos($clean, 'Space') !== false) {
            return 'Space Technology';
        }
        if (preg_match('/COMP\s*-\s*Artificial\s*Intelligence/i', $clean) || stripos($clean, 'Artificial Intelligence') !== false) {
            return 'COMP - Artificial Intelligence';
        }
        if (preg_match('/COMP\s*-\s*Data\s*Science/i', $clean)) {
            return 'COMP - Data Science';
        }
        if (stripos($clean, 'Data Science') !== false) {
            return 'Data Science';
        }
        if (stripos($clean, 'Cyber Security') !== false) {
            return 'Cyber Security';
        }
        return $clean;
    }

    /**
     * Generate deterministic dummy mentor name from index (0 -> Mentor A, 25 -> Mentor Z, 26 -> Mentor AA)
     */
    public static function getDummyMentorName($index)
    {
        $letters = '';
        $n = intval($index);
        while ($n >= 0) {
            $rem = $n % 26;
            $letters = chr(65 + $rem) . $letters;
            $n = intval(floor($n / 26)) - 1;
        }
        return 'Mentor ' . $letters;
    }

    /**
     * Normalize name string for matching
     */
    private function normalizeNameString($name)
    {
        return strtolower(trim(preg_replace('/[^a-z]/', '', (string)$name)));
    }

    /**
     * Name similarity check for deduplication
     */
    private function namesMatch($n1, $n2)
    {
        $c1 = $this->normalizeNameString($n1);
        $c2 = $this->normalizeNameString($n2);
        if ($c1 === $c2 && !empty($c1)) return true;
        if (empty($c1) || empty($c2)) return false;

        $words1 = array_filter(explode(' ', strtolower(trim(preg_replace('/[^a-z\s]/', '', $n1)))));
        $words2 = array_filter(explode(' ', strtolower(trim(preg_replace('/[^a-z\s]/', '', $n2)))));
        $first1 = reset($words1);
        $last1 = end($words1);
        $first2 = reset($words2);
        $last2 = end($words2);

        if ($first1 === $first2 && $last1 === $last2 && strlen($first1) >= 3) {
            return true;
        }
        if ((strpos($c1, $c2) !== false || strpos($c2, $c1) !== false) && min(strlen($c1), strlen($c2)) >= 8) {
            return true;
        }
        return false;
    }

    /**
     * Parse raw text data from antigravity_academic_data_2026_27.txt
     */
    public function parseAcademicText($rawText)
    {
        $sections = explode('================================================================================', $rawText);
        $records = [];
        $sourceCounts = [];
        $currentWorkbook = '';

        foreach ($sections as $section) {
            $section = trim($section);
            if (empty($section)) continue;

            if (preg_match('/WORKBOOK:\s*(.+)/i', $section, $wbM)) {
                $currentWorkbook = trim($wbM[1]);
            }

            if (preg_match('/SHEET:\s*(.+)/i', $section, $sheetM)) {
                $sheetName = trim($sheetM[1]);
                $lines = explode("\n", $section);
                $inData = false;

                foreach ($lines as $line) {
                    $line = trim($line, "\r");
                    if (empty(trim($line))) continue;

                    if (preg_match('/^DATA_ROWS:/', $line)) {
                        $inData = true;
                        continue;
                    }
                    if (preg_match('/^WORKBOOK:/', $line) || preg_match('/^SHEET:/', $line) || preg_match('/^---+/', $line) || preg_match('/^HEADER_ROW/', $line)) {
                        continue;
                    }
                    if (preg_match('/^IMPLEMENTATION METADATA/i', $line) || preg_match('/^SEMESTER_MAPPING/i', $line) || preg_match('/^MENTOR_RULES/i', $line)) {
                        break;
                    }

                    if ($inData) {
                        if (preg_match('/(Name of a student|Name of student|Official Email Address|Registration done on Nptel)/i', $line)) {
                            continue;
                        }

                        $cols = array_map('trim', explode("\t", $line));
                        $sourceCounts[$sheetName] = ($sourceCounts[$sheetName] ?? 0) + 1;

                        $rec = [
                            'workbook' => $currentWorkbook,
                            'source_sheet' => $sheetName,
                            'academic_year' => '2026-27',
                            'academic_year_id' => 2,
                            'is_nptel' => false,
                            'raw_cols' => $cols
                        ];

                        if ($sheetName === 'SEM V') {
                            $rec['semester'] = 'V';
                            $rec['semester_id'] = 5;
                            if (count($cols) >= 6 && is_numeric($cols[0])) {
                                $rec['sr_no'] = $cols[0];
                                $rec['name'] = $cols[1];
                                $rec['class_div'] = $cols[2];
                                $rec['division_id'] = $this->resolveDivisionId($cols[2]);
                                $rec['roll_no'] = $cols[3];
                                $rec['dept_str'] = $cols[4];
                                $rec['subject_raw'] = $cols[5];
                                $rec['remark'] = trim(($cols[6] ?? '') . ' ' . ($cols[7] ?? ''));
                            } else {
                                $rec['name'] = $cols[1] ?? $cols[0];
                                $rec['erp_or_code'] = $cols[2] ?? $cols[1];
                                $rec['subject_raw'] = $cols[3] ?? $cols[2];
                                if (preg_match('/24-(.+?)([A-F0-9]+)-28/i', $rec['erp_or_code'], $codeMatch)) {
                                    $rec['dept_str'] = $codeMatch[1];
                                    $rec['roll_no'] = $codeMatch[2];
                                    $rec['division_id'] = $this->resolveDivisionId($codeMatch[2]);
                                }
                            }
                        } elseif ($sheetName === 'SEM VII NPTEL STUDENTS') {
                            $rec['semester'] = 'VII';
                            $rec['semester_id'] = 7;
                            $rec['is_nptel'] = true;
                            $rec['sr_no'] = $cols[0] ?? '';
                            $rec['name'] = $cols[1] ?? '';
                            $rec['division_str'] = $cols[2] ?? '';
                            $rec['division_id'] = $this->resolveDivisionId($cols[2] ?? '');
                            $rec['roll_no'] = $cols[3] ?? '';
                            $rec['dept_str'] = $cols[4] ?? '';
                            $rec['subject_raw'] = $cols[5] ?? '';
                        } elseif ($sheetName === 'SEM VII') {
                            $rec['semester'] = 'VII';
                            $rec['semester_id'] = 7;
                            if (is_numeric($cols[0])) {
                                $rec['sr_no'] = $cols[0];
                                $rec['name'] = $cols[1] ?? '';
                                $rec['division_str'] = $cols[2] ?? '';
                                $rec['division_id'] = $this->resolveDivisionId($cols[2] ?? '');
                                $rec['roll_no'] = $cols[3] ?? '';
                                $rec['dept_str'] = $cols[4] ?? '';
                                $rec['subject_raw'] = $cols[5] ?? '';
                                $rec['remark'] = trim(($cols[6] ?? '') . ' ' . ($cols[7] ?? ''));
                            } else {
                                $rec['name'] = $cols[1] ?? $cols[0];
                                $rec['division_str'] = $cols[2] ?? $cols[1];
                                $rec['division_id'] = $this->resolveDivisionId($cols[2] ?? '');
                                $rec['roll_no'] = $cols[3] ?? '';
                                $rec['dept_str'] = $cols[4] ?? '';
                                $rec['subject_raw'] = $cols[5] ?? '';
                            }
                        } elseif ($sheetName === 'Quantum Technology and Applicat') {
                            $rec['semester'] = 'III';
                            $rec['semester_id'] = 3;
                            $rec['subject_raw'] = 'Quantum Technology';
                            $rec['name'] = $cols[0] ?? '';
                            $rec['division_str'] = $cols[1] ?? '';
                            $rec['division_id'] = $this->resolveDivisionId($cols[1] ?? '');
                            $rec['roll_no'] = $cols[2] ?? '';
                            $rec['dept_str'] = $cols[3] ?? '';
                            $rec['erp_or_email'] = $cols[4] ?? '';
                            $rec['cgpa_raw'] = $cols[5] ?? '';
                            $rec['remark'] = $cols[6] ?? '';
                        } elseif ($sheetName === 'Quantum Tech final count AY 26-') {
                            $rec['semester'] = 'III';
                            $rec['semester_id'] = 3;
                            $rec['subject_raw'] = 'Quantum Technology';
                            $rec['name'] = $cols[0] ?? '';
                            $rec['class_div'] = $cols[1] ?? '';
                            $rec['roll_no'] = $cols[2] ?? '';
                            $rec['nptel_reg'] = $cols[3] ?? '';
                            $rec['col5'] = $cols[4] ?? '';
                            $rec['cgpa_raw'] = $cols[5] ?? '';
                            if (preg_match('/^([A-Za-z\s&]+)[-\s]+([A-Za-z0-9]+)$/', $rec['class_div'], $cdM)) {
                                $rec['dept_str'] = trim($cdM[1]);
                                $rec['division_str'] = trim($cdM[2]);
                                $rec['division_id'] = $this->resolveDivisionId($cdM[2]);
                            } else {
                                $rec['dept_str'] = $rec['class_div'];
                                $rec['division_id'] = $this->resolveDivisionId($rec['class_div']);
                            }
                            if (strtolower(trim($rec['nptel_reg'] ?? '')) === 'yes') {
                                $rec['is_nptel'] = true;
                            }
                        } elseif ($sheetName === 'Space Technology AY 26-27 sem I') {
                            $rec['semester'] = 'III';
                            $rec['semester_id'] = 3;
                            $rec['subject_raw'] = 'Space Technology';
                            $rec['sr_no'] = $cols[0] ?? '';
                            $rec['name'] = $cols[1] ?? '';
                            $rec['roll_no'] = $cols[2] ?? '';
                            $rec['dept_str'] = $cols[3] ?? '';
                            $rec['division_str'] = $cols[4] ?? '';
                            $rec['division_id'] = $this->resolveDivisionId($cols[4] ?? '');
                            $rec['erp_or_email'] = $cols[5] ?? '';
                            $rec['year_str'] = $cols[6] ?? '';
                            $rec['cgpa_sem1'] = $cols[7] ?? '';
                            $rec['cgpa_sem2'] = $cols[8] ?? '';
                            $rec['mobile'] = $cols[9] ?? '';
                        }

                        $rec['dept_id'] = $this->resolveDepartmentId($rec['dept_str'] ?? '');
                        $rec['normalized_subject'] = $this->normalizeSubjectName($rec['subject_raw'] ?? '');

                        // Extract clean ERP & email
                        $erp = null;
                        $email = null;
                        if (!empty($rec['erp_or_email'])) {
                            if (preg_match('/^([S0-9]+)@/i', $rec['erp_or_email'], $m)) {
                                $erp = $m[1];
                                $email = strtolower($rec['erp_or_email']);
                            } elseif (preg_match('/^[S0-9]+$/i', $rec['erp_or_email'])) {
                                $erp = $rec['erp_or_email'];
                                $email = strtolower($rec['erp_or_email']) . '@tcetmumbai.in';
                            } elseif (strpos($rec['erp_or_email'], '@') !== false) {
                                $email = strtolower($rec['erp_or_email']);
                            }
                        } elseif (!empty($rec['erp_or_code'])) {
                            $erp = $rec['erp_or_code'];
                        }

                        $rec['erp_id'] = $erp;
                        $rec['email'] = $email;

                        // Parse numeric CGPA
                        $cgpaVal = null;
                        if (!empty($rec['cgpa_raw'])) {
                            if (preg_match('/([0-9]+\.[0-9]+|[0-9]+)/', $rec['cgpa_raw'], $cgpaM)) {
                                $cgpaVal = floatval($cgpaM[1]);
                            }
                        } elseif (!empty($rec['cgpa_sem1'])) {
                            if (preg_match('/([0-9]+\.[0-9]+|[0-9]+)/', $rec['cgpa_sem1'], $cgpaM)) {
                                $cgpaVal = floatval($cgpaM[1]);
                            }
                        }
                        $rec['parsed_cgpa'] = $cgpaVal;

                        $records[] = $rec;
                    }
                }
            }
        }

        return [
            'records' => $records,
            'source_counts' => $sourceCounts
        ];
    }

    /**
     * Deduplicate parsed records into unique students
     */
    public function deduplicateRecords(array $records)
    {
        $uniqueStudents = [];
        $duplicates = [];

        foreach ($records as $r) {
            $name = trim(preg_replace('/\s+/', ' ', $r['name']));
            $roll = trim($r['roll_no'] ?? '');
            $dept = $r['dept_id'] ?? 0;
            $sem = $r['semester_id'] ?? 0;
            $div = $r['division_id'] ?? 1;
            $erp = $r['erp_id'] ?? '';
            $email = $r['email'] ?? '';

            $matchedKey = null;
            $matchReason = '';

            // Priority 1: ERP ID
            if (!empty($erp)) {
                foreach ($uniqueStudents as $key => $u) {
                    if (!empty($u['erp_id']) && strcasecmp($u['erp_id'], $erp) === 0) {
                        $matchedKey = $key;
                        $matchReason = "Priority 1 (ERP ID: $erp)";
                        break;
                    }
                }
            }

            // Priority 3: Email
            if (!$matchedKey && !empty($email)) {
                foreach ($uniqueStudents as $key => $u) {
                    if (!empty($u['email']) && strcasecmp($u['email'], $email) === 0) {
                        $matchedKey = $key;
                        $matchReason = "Priority 3 (Email: $email)";
                        break;
                    }
                }
            }

            // Priority 4: Roll + Dept + Division + Semester AND Name similarity
            if (!$matchedKey && !empty($roll) && $dept > 0 && $sem > 0) {
                foreach ($uniqueStudents as $key => $u) {
                    if ($u['roll_no'] === $roll && $u['dept_id'] === $dept && $u['semester_id'] === $sem && $u['division_id'] === $div) {
                        if ($this->namesMatch($u['name'], $name)) {
                            $matchedKey = $key;
                            $matchReason = "Priority 4 (Roll: $roll, Dept: $dept, Div: $div, Sem: $sem)";
                            break;
                        }
                    }
                }
            }

            // Priority 5: Name + Roll + Dept + Semester
            if (!$matchedKey && !empty($name) && !empty($roll) && $dept > 0 && $sem > 0) {
                foreach ($uniqueStudents as $key => $u) {
                    if ($u['roll_no'] === $roll && $u['dept_id'] === $dept && $u['semester_id'] === $sem) {
                        if ($this->namesMatch($u['name'], $name)) {
                            $matchedKey = $key;
                            $matchReason = "Priority 5 (Name+Roll+Dept+Sem: '$name' Roll: $roll)";
                            break;
                        }
                    }
                }
            }

            if ($matchedKey) {
                $duplicates[] = [
                    'original' => $uniqueStudents[$matchedKey],
                    'duplicate' => $r,
                    'reason' => $matchReason
                ];

                // Enrich missing attributes
                if (empty($uniqueStudents[$matchedKey]['erp_id']) && !empty($erp)) {
                    $uniqueStudents[$matchedKey]['erp_id'] = $erp;
                }
                if (empty($uniqueStudents[$matchedKey]['email']) && !empty($email)) {
                    $uniqueStudents[$matchedKey]['email'] = $email;
                }
                if (empty($uniqueStudents[$matchedKey]['mobile']) && !empty($r['mobile'])) {
                    $uniqueStudents[$matchedKey]['mobile'] = $r['mobile'];
                }
                if (!empty($r['is_nptel'])) {
                    $uniqueStudents[$matchedKey]['is_nptel'] = true;
                }
                if (!empty($r['parsed_cgpa']) && empty($uniqueStudents[$matchedKey]['parsed_cgpa'])) {
                    $uniqueStudents[$matchedKey]['parsed_cgpa'] = $r['parsed_cgpa'];
                }
            } else {
                $k = "STU_" . (count($uniqueStudents) + 1);
                $uniqueStudents[$k] = $r;
                $uniqueStudents[$k]['id_key'] = $k;
            }
        }

        return [
            'unique_students' => array_values($uniqueStudents),
            'duplicates' => $duplicates
        ];
    }

    /**
     * Generate Pre-Import Preview Metrics
     */
    public function getImportPreview($rawText)
    {
        $parsed = $this->parseAcademicText($rawText);
        $deduped = $this->deduplicateRecords($parsed['records']);

        // Collect distinct subjects
        $distinctSubjects = [];
        foreach ($deduped['unique_students'] as $s) {
            $subName = $s['normalized_subject'] ?? 'Specialization';
            $distinctSubjects[$subName] = ($distinctSubjects[$subName] ?? 0) + 1;
        }
        ksort($distinctSubjects);

        // Check existing subjects in DB
        $existingSubjects = [];
        $res = mysqli_query($this->conn, "SELECT subject_id, subject_name FROM st_specialization_subject_master");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $existingSubjects[strtolower(trim($row['subject_name']))] = intval($row['subject_id']);
            }
        }

        $newSubjectsCount = 0;
        $existingSubjectsCount = 0;
        foreach (array_keys($distinctSubjects) as $sub) {
            if (isset($existingSubjects[strtolower(trim($sub))])) {
                $existingSubjectsCount++;
            } else {
                $newSubjectsCount++;
            }
        }

        // Count existing vs new students in DB
        $newStudentsCount = 0;
        $existingStudentsCount = 0;
        foreach ($deduped['unique_students'] as $s) {
            $erp = mysqli_real_escape_string($this->conn, $s['erp_id'] ?? '');
            $email = mysqli_real_escape_string($this->conn, $s['email'] ?? '');
            $roll = mysqli_real_escape_string($this->conn, $s['roll_no'] ?? '');
            $dept = intval($s['dept_id'] ?? 0);
            $sem = intval($s['semester_id'] ?? 0);

            $exists = false;
            if (!empty($erp)) {
                $chk = mysqli_query($this->conn, "SELECT student_id FROM st_student_master WHERE registration_no = '$erp' LIMIT 1");
                if ($chk && mysqli_num_rows($chk) > 0) $exists = true;
            }
            if (!$exists && !empty($email)) {
                $chk = mysqli_query($this->conn, "SELECT student_id FROM st_student_master WHERE email = '$email' LIMIT 1");
                if ($chk && mysqli_num_rows($chk) > 0) $exists = true;
            }
            if (!$exists && !empty($roll) && $dept > 0 && $sem > 0) {
                $chk = mysqli_query($this->conn, "SELECT student_id FROM st_student_master WHERE roll_no = '$roll' AND department_id = $dept AND current_semester_id = $sem LIMIT 1");
                if ($chk && mysqli_num_rows($chk) > 0) $exists = true;
            }

            if ($exists) {
                $existingStudentsCount++;
            } else {
                $newStudentsCount++;
            }
        }

        // Semester distribution
        $semBreakdown = [];
        foreach ($deduped['unique_students'] as $s) {
            $sem = 'Semester ' . ($s['semester_id'] ?? 'Unknown');
            $semBreakdown[$sem] = ($semBreakdown[$sem] ?? 0) + 1;
        }

        return [
            'academic_year' => '2026-27',
            'total_source_rows' => count($parsed['records']),
            'sources' => $parsed['source_counts'],
            'students' => [
                'total_unique' => count($deduped['unique_students']),
                'new' => $newStudentsCount,
                'existing_updated' => $existingStudentsCount,
                'duplicates_merged' => count($deduped['duplicates']),
                'invalid' => 0
            ],
            'subjects' => [
                'distinct_subjects' => $distinctSubjects,
                'new' => $newSubjectsCount,
                'existing' => $existingSubjectsCount
            ],
            'semesters' => $semBreakdown,
            'dummy_mentors_needed' => count($distinctSubjects)
        ];
    }

    /**
     * Execute Idempotent Academic Data Import
     */
    public function executeImport($rawText, $executedByUserId = 1)
    {
        $parsed = $this->parseAcademicText($rawText);
        $deduped = $this->deduplicateRecords($parsed['records']);

        mysqli_begin_transaction($this->conn);

        try {
            // Step 1: Ensure Academic Year 2026-27 exists
            $ayId = 2; // session_id = 2
            $ayCheck = mysqli_query($this->conn, "SELECT session_id FROM st_session_master WHERE session_id = 2 OR session_name LIKE '%2026%2027%' LIMIT 1");
            if ($ayCheck && ($ayRow = mysqli_fetch_assoc($ayCheck))) {
                $ayId = intval($ayRow['session_id']);
            } else {
                mysqli_query($this->conn, "INSERT INTO st_session_master (session_id, session_name) VALUES (2, '2026 -2027')");
                $ayId = 2;
            }

            // Step 2: Extract and Sort Distinct Subjects Deterministically
            $distinctSubjectNames = [];
            foreach ($deduped['unique_students'] as $s) {
                $subName = $s['normalized_subject'] ?? 'Specialization';
                $distinctSubjectNames[$subName] = true;
            }
            $subjectList = array_keys($distinctSubjectNames);
            sort($subjectList, SORT_STRING | SORT_FLAG_CASE);

            // Step 3: Resolve / Create Subjects and Assign Deterministic Dummy Mentors
            $subjectMap = []; // Name -> subject_id
            $dummyMentorsCreated = 0;
            $newSubjectsCreated = 0;

            foreach ($subjectList as $idx => $subjName) {
                $cleanSubName = mysqli_real_escape_string($this->conn, $subjName);

                // Check if subject exists in st_specialization_subject_master
                $subQuery = mysqli_query($this->conn, "SELECT subject_id, subject_name, department_id, semester_id FROM st_specialization_subject_master WHERE subject_name = '$cleanSubName' LIMIT 1");
                $subjectId = 0;
                if ($subQuery && ($subRow = mysqli_fetch_assoc($subQuery))) {
                    $subjectId = intval($subRow['subject_id']);
                } else {
                    // Create subject
                    $insSub = "INSERT INTO st_specialization_subject_master (subject_name, specialization_id, academic_year_id, is_active, description) 
                               VALUES ('$cleanSubName', 1, $ayId, 1, 'Academic Year 2026-27 Specialization Subject')";
                    mysqli_query($this->conn, $insSub);
                    $subjectId = mysqli_insert_id($this->conn);
                    $newSubjectsCreated++;
                }

                $subjectMap[$subjName] = $subjectId;

                // Step 4: Subject-to-Mentor Assignment (Preserve existing actual/custom mentors!)
                $existingMapQuery = mysqli_query($this->conn, "SELECT mapping_id, mentor_id FROM st_mentor_subject_mapping WHERE subject_id = $subjectId LIMIT 1");
                if ($existingMapQuery && mysqli_num_rows($existingMapQuery) > 0) {
                    // EXISTING MENTOR ASSIGNMENT FOUND -> PRESERVE IT!
                    // Do nothing; never overwrite an existing mentor on re-import.
                } else {
                    // Generate deterministic dummy alphabet mentor (Mentor A, Mentor B, ...)
                    $dummyMentorName = self::getDummyMentorName($idx);
                    $dummyEmail = 'mentor.' . strtolower(str_replace(' ', '', substr($dummyMentorName, 7))) . '@tcetmumbai.in';
                    $cleanMentorName = mysqli_real_escape_string($this->conn, $dummyMentorName);
                    $cleanEmail = mysqli_real_escape_string($this->conn, $dummyEmail);

                    // Check if dummy mentor user exists
                    $mentorUserQuery = mysqli_query($this->conn, "SELECT user_id FROM st_user_master WHERE user_name = '$cleanMentorName' AND role_id = 4 LIMIT 1");
                    $mentorUserId = 0;
                    if ($mentorUserQuery && ($mRow = mysqli_fetch_assoc($mentorUserQuery))) {
                        $mentorUserId = intval($mRow['user_id']);
                    } else {
                        // Insert dummy mentor in st_user_master
                        $insMentor = "INSERT INTO st_user_master (user_name, email_id, department_id, role_id, student_id, is_first_login)
                                      VALUES ('$cleanMentorName', '$cleanEmail', 1, 4, 0, 0)";
                        mysqli_query($this->conn, $insMentor);
                        $mentorUserId = mysqli_insert_id($this->conn);

                        // Insert in st_login
                        $insLogin = "INSERT INTO st_login (username, password, user_id) VALUES ('$cleanEmail', '123456', $mentorUserId)";
                        mysqli_query($this->conn, $insLogin);
                        $dummyMentorsCreated++;
                    }

                    // Create subject -> mentor mapping
                    $insMapping = "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id) VALUES ($mentorUserId, $subjectId)
                                   ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id)";
                    mysqli_query($this->conn, $insMapping);
                }
            }

            // Step 5: Import / Update Students
            $studentsCreated = 0;
            $studentsUpdated = 0;

            foreach ($deduped['unique_students'] as $idx => $s) {
                $name = mysqli_real_escape_string($this->conn, trim($s['name']));
                $roll = mysqli_real_escape_string($this->conn, trim($s['roll_no'] ?? ''));
                $deptId = intval($s['dept_id'] ?? 3);
                $divId = intval($s['division_id'] ?? 1);
                $semId = intval($s['semester_id'] ?? 3);
                $classId = $this->resolveClassIdForSemester($semId);
                $subjName = $s['normalized_subject'] ?? 'Specialization';
                $subjectId = intval($subjectMap[$subjName] ?? 0);
                $cgpa = $s['parsed_cgpa'] !== null ? floatval($s['parsed_cgpa']) : 8.00;
                $mobile = mysqli_real_escape_string($this->conn, $s['mobile'] ?? '');

                // Generate or use ERP / Registration No
                $erp = trim((string)($s['erp_id'] ?? ''));
                if ($erp === '') {
                    $deptCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $s['dept_str'] ?? 'ST'));
                    $erp = '26-' . $deptCode . '-D' . $divId . '-R' . ($roll !== '' ? $roll : 'U' . ($idx + 1));
                }
                $cleanErp = mysqli_real_escape_string($this->conn, $erp);

                // Generate or use Email
                $email = trim((string)($s['email'] ?? ''));
                if ($email === '' || strpos($email, '@') === false) {
                    $cleanNameForEmail = strtolower(preg_replace('/[^a-z0-9]/', '.', $s['name']));
                    $email = $cleanNameForEmail . ($roll !== '' ? '.' . $roll : '') . '@tcetmumbai.in';
                }
                $cleanEmail = mysqli_real_escape_string($this->conn, $email);

                // Check if student already exists in database
                $whereClauses = [];
                if (!empty($cleanErp)) {
                    $whereClauses[] = "registration_no = '$cleanErp'";
                }
                if (!empty($cleanEmail)) {
                    $whereClauses[] = "email = '$cleanEmail'";
                }
                if (!empty($roll) && $deptId > 0 && $semId > 0) {
                    $whereClauses[] = "(roll_no = '$roll' AND department_id = $deptId AND division_id = $divId AND current_semester_id = $semId)";
                }

                $whereSql = implode(' OR ', $whereClauses);
                $existingStudentQuery = !empty($whereSql) ? mysqli_query($this->conn, "SELECT student_id FROM st_student_master WHERE $whereSql LIMIT 1") : false;

                $studentId = 0;
                if ($existingStudentQuery && ($stRow = mysqli_fetch_assoc($existingStudentQuery))) {
                    $studentId = intval($stRow['student_id']);
                    // Update existing student record idempotently
                    $upStudent = "UPDATE st_student_master 
                                  SET fname = '$name', roll_no = '$roll', department_id = $deptId, division_id = $divId,
                                      class_id = $classId, specialization_id = 1, specialization_subject_id = $subjectId,
                                      cgpa = $cgpa, academic_year_id = $ayId, current_semester_id = $semId,
                                      mobile = IF('$mobile' != '', '$mobile', mobile)
                                  WHERE student_id = $studentId";
                    mysqli_query($this->conn, $upStudent);
                    $studentsUpdated++;
                } else {
                    // Insert new student record
                    $insStudent = "INSERT INTO st_student_master 
                                   (registration_no, class_id, division_id, grad_year, roll_no, department_id,
                                    specialization_id, specialization_subject_id, cgpa, fname, mobile, email,
                                    status, m_sem1, m_sem2, m_sem3, academic_year_id, current_semester_id)
                                   VALUES 
                                   ('$cleanErp', $classId, $divId, 2028, '$roll', $deptId,
                                    1, $subjectId, $cgpa, '$name', '$mobile', '$cleanEmail',
                                    1, '[]', '[]', '[]', $ayId, $semId)";
                    mysqli_query($this->conn, $insStudent);
                    $studentId = mysqli_insert_id($this->conn);
                    $studentsCreated++;

                    // Create user_master & login for student
                    $insUser = "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id, is_first_login)
                                VALUES ('$name', '$cleanEmail', '$mobile', $deptId, 5, $studentId, 1)";
                    mysqli_query($this->conn, $insUser);
                    $userId = mysqli_insert_id($this->conn);

                    $insLogin = "INSERT INTO st_login (username, password, user_id) VALUES ('$cleanEmail', '123456', $userId)";
                    mysqli_query($this->conn, $insLogin);
                }

                // Sync Student Semester History
                $histQuery = "INSERT INTO st_student_semester_history 
                              (student_id, academic_year_id, class_id, semester_id, division_id, specialization_id, specialization_subject_id, cgpa, status)
                              VALUES ($studentId, $ayId, $classId, $semId, $divId, 1, $subjectId, $cgpa, 'Active')
                              ON DUPLICATE KEY UPDATE 
                                academic_year_id = $ayId, class_id = $classId, division_id = $divId,
                                specialization_id = 1, specialization_subject_id = $subjectId, cgpa = $cgpa";
                mysqli_query($this->conn, $histQuery);

                // Handle NPTEL status if present
                if (!empty($s['is_nptel'])) {
                    $nptelQuery = "INSERT INTO st_nptel_records (student_id, semester_id, course_name, pass_fail)
                                   VALUES ($studentId, $semId, '$cleanSubName', 'Pass')
                                   ON DUPLICATE KEY UPDATE pass_fail = 'Pass'";
                    mysqli_query($this->conn, $nptelQuery);
                }
            }

            // Write Audit Log
            if (method_exists($this->db, 'writeAuditLog')) {
                $this->db->writeAuditLog(
                    $executedByUserId,
                    'ACADEMIC_DATA_IMPORTED',
                    'st_student_master',
                    null,
                    "Imported AY 2026-27 academic data: {$studentsCreated} new students, {$studentsUpdated} updated, {$newSubjectsCreated} subjects, {$dummyMentorsCreated} dummy mentors created."
                );
            }

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'academic_year' => '2026-27',
                'total_source_rows' => count($parsed['records']),
                'unique_students_processed' => count($deduped['unique_students']),
                'students_created' => $studentsCreated,
                'students_updated' => $studentsUpdated,
                'duplicates_merged' => count($deduped['duplicates']),
                'distinct_subjects_count' => count($subjectList),
                'new_subjects_created' => $newSubjectsCreated,
                'dummy_mentors_created' => $dummyMentorsCreated,
                'subjects' => $subjectList
            ];
        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
