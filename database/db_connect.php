<?php
if (!class_exists('DBController')) {
class DBController
{
    /*public $host = "localhost";
    public $user = "tcet_st";
    public $password = "Tcet@1378";
    public $database = " tcet_st";*/

    public $host = "localhost";
    public $user = "root";
    public $password = "";
    public $database = "tcet_st"; 
      
   public $conn;
  public $last_error = '';

    function __construct($dbName = null)
    {
        if ($dbName !== null && trim((string)$dbName) !== '') {
            $this->database = trim((string)$dbName);
        } elseif (getenv('TCET_DB_NAME')) {
            $this->database = trim((string)getenv('TCET_DB_NAME'));
        }
        $this->conn = $this->connectDB();
    }

    function query($query)
    {
    $result = mysqli_query($this->conn,$query);
    return $result;
    }

    function connectDB()
    {
      mysqli_report(MYSQLI_REPORT_OFF);
      $conn = @mysqli_connect($this->host,$this->user,$this->password,$this->database);
       if (!$conn) {
        $this->last_error = 'Unable to connect with database';
        return false;
      }


      return $conn;
    }

    public function runQuery($query) {
        if (!$this->conn) {
            $this->last_error = 'Database connection is not available';
            return false;
        }
        $result = mysqli_query($this->conn, $query);

        if (!$result) {
            $this->last_error = mysqli_error($this->conn);
            error_log("Database runQuery error: " . $this->last_error);
            return false;
        }

        $resultset = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $resultset[] = $row;
        }
        return $resultset;
    }


    function numRows($query)
    {
        $result = mysqli_query($this->conn,$query);
        $rowcount = mysqli_num_rows($result);
        return $rowcount;
    }

    function executeUpdate($query)
    {
        $result = mysqli_query($this->conn,$query);
        return $result;
    }

  function readData($query)
    {
          $result = mysqli_query($this->conn,$query);
         while($row=mysqli_fetch_assoc($result))
       {
            $resultset[] = $row;
           }
          if(!empty($resultset))
            return $resultset;
    }

  function executeInsert($query)
    {
      $result = mysqli_query($this->conn,$query);
      $insert_id = mysqli_insert_id($this->conn);
        return $insert_id;
    }

    function cleanData($data)
    {
          $data = mysqli_real_escape_string($this->conn,strip_tags($data));
          return $data;
    }

    public static function startSecureSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $params = session_get_cookie_params();
                @session_set_cookie_params([
                    'lifetime' => $params['lifetime'],
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            @session_start();
        }
    }

    public static function generateCsrfToken()
    {
        self::startSecureSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getCsrfToken()
    {
        return self::generateCsrfToken();
    }

    public static function getCsrfInputField()
    {
        $token = htmlspecialchars(self::generateCsrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function validateCsrfToken($token = null)
    {
        self::startSecureSession();
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, (string)$token);
    }

    public static function hashPassword($plainPassword)
    {
        return password_hash((string)$plainPassword, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    public static function verifyPassword($plainPassword, $storedHash)
    {
        if (empty($plainPassword) || empty($storedHash)) {
            return false;
        }
        // Check bcrypt
        if (password_verify($plainPassword, $storedHash)) {
            return true;
        }
        // Fallback for legacy plaintext
        if ($storedHash === $plainPassword) {
            return true;
        }
        // Fallback for legacy md5
        if ($storedHash === md5($plainPassword)) {
            return true;
        }
        return false;
    }

  private function auditLogColumnExists($columnName)
  {
    $safeColumn = mysqli_real_escape_string($this->conn, $columnName);
    $result = mysqli_query($this->conn, "SHOW COLUMNS FROM st_audit_log LIKE '$safeColumn'");
    $exists = ($result && mysqli_num_rows($result) > 0);

    if ($result) {
      mysqli_free_result($result);
    }

    return $exists;
  }

  public function columnExists($tableName, $columnName)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }
    $safeTable = mysqli_real_escape_string($this->conn, $tableName);
    $safeColumn = mysqli_real_escape_string($this->conn, $columnName);
    $result = mysqli_query($this->conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    $exists = ($result && mysqli_num_rows($result) > 0);
    if ($result) {
      mysqli_free_result($result);
    }
    return $exists;
  }

  public function ensureSpecializationCourseSchema()
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }

    $schemaUpdates = array(
      'st_department_master' => array(
        'program_name' => "ALTER TABLE st_department_master ADD COLUMN program_name varchar(150) DEFAULT NULL AFTER department_name"
      ),
      'st_specialization_master' => array(
        'honors_eligibility' => "ALTER TABLE st_specialization_master ADD COLUMN honors_eligibility text DEFAULT NULL",
        'minor_eligibility' => "ALTER TABLE st_specialization_master ADD COLUMN minor_eligibility text DEFAULT NULL",
        'offering_program' => "ALTER TABLE st_specialization_master ADD COLUMN offering_program varchar(150) DEFAULT NULL",
        'department_id' => "ALTER TABLE st_specialization_master ADD COLUMN department_id int(11) DEFAULT NULL"
      ),
      'st_specialization_subject_master' => array(
        'program_name' => "ALTER TABLE st_specialization_subject_master ADD COLUMN program_name varchar(150) DEFAULT NULL",
        'year_of_study' => "ALTER TABLE st_specialization_subject_master ADD COLUMN year_of_study varchar(50) DEFAULT NULL",
        'source_page' => "ALTER TABLE st_specialization_subject_master ADD COLUMN source_page varchar(50) DEFAULT NULL",
        'source_notes' => "ALTER TABLE st_specialization_subject_master ADD COLUMN source_notes text DEFAULT NULL",
        'honors_eligibility' => "ALTER TABLE st_specialization_subject_master ADD COLUMN honors_eligibility text DEFAULT NULL",
        'minor_eligibility' => "ALTER TABLE st_specialization_subject_master ADD COLUMN minor_eligibility text DEFAULT NULL"
      )
    );

    foreach ($schemaUpdates as $table => $cols) {
      foreach ($cols as $colName => $sql) {
        if (!$this->columnExists($table, $colName)) {
          mysqli_query($this->conn, $sql);
        }
      }
    }

    return true;
  }

  public function ensureAuditLogTable()
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }

    $createSql = "CREATE TABLE IF NOT EXISTS st_audit_log (
      audit_id int(11) NOT NULL AUTO_INCREMENT,
      user_id int(11) NOT NULL DEFAULT 0,
      action_type varchar(100) NOT NULL,
      affected_table varchar(100) DEFAULT NULL,
      affected_record int(11) DEFAULT NULL,
      description text DEFAULT NULL,
      username varchar(200) DEFAULT NULL,
      ip_address varchar(45) DEFAULT NULL,
      browser_user_agent text DEFAULT NULL,
      session_duration_seconds int(11) DEFAULT NULL,
      logout_at timestamp NULL DEFAULT NULL,
      performed_at timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (audit_id),
      KEY action_type (action_type),
      KEY user_id (user_id),
      KEY performed_at (performed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!mysqli_query($this->conn, $createSql)) {
      return false;
    }

    $columns = array(
      'username' => "ALTER TABLE st_audit_log ADD COLUMN username varchar(200) DEFAULT NULL AFTER description",
      'ip_address' => "ALTER TABLE st_audit_log ADD COLUMN ip_address varchar(45) DEFAULT NULL AFTER username",
      'browser_user_agent' => "ALTER TABLE st_audit_log ADD COLUMN browser_user_agent text DEFAULT NULL AFTER ip_address",
      'session_duration_seconds' => "ALTER TABLE st_audit_log ADD COLUMN session_duration_seconds int(11) DEFAULT NULL AFTER browser_user_agent",
      'logout_at' => "ALTER TABLE st_audit_log ADD COLUMN logout_at timestamp NULL DEFAULT NULL AFTER session_duration_seconds"
    );

    foreach ($columns as $column => $alterSql) {
      if (!$this->auditLogColumnExists($column)) {
        mysqli_query($this->conn, $alterSql);
      }
    }

    return true;
  }

  public function ensurePasswordResetTable()
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }

    $createSql = "CREATE TABLE IF NOT EXISTS st_password_resets (
      reset_id int(11) NOT NULL AUTO_INCREMENT,
      user_id int(11) NOT NULL,
      email varchar(150) NOT NULL,
      token_hash varchar(64) NOT NULL,
      expires_at datetime NOT NULL,
      consumed_at datetime DEFAULT NULL,
      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      ip_address varchar(45) DEFAULT NULL,
      PRIMARY KEY (reset_id),
      KEY idx_pwd_reset_user (user_id),
      KEY idx_pwd_reset_token (token_hash),
      KEY idx_pwd_reset_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return (mysqli_query($this->conn, $createSql) !== false);
  }

  public function ensureNptelCancellationTable()
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }

    $createSql = "CREATE TABLE IF NOT EXISTS st_nptel_cancellations (
      cancellation_id int(11) NOT NULL AUTO_INCREMENT,
      student_id int(11) NOT NULL,
      enrollment_id int(11) DEFAULT NULL,
      course_name varchar(200) NOT NULL,
      reason text NOT NULL,
      status varchar(50) NOT NULL DEFAULT 'Submitted by Student',
      submitted_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (cancellation_id),
      KEY idx_nptel_cancel_student (student_id),
      KEY idx_nptel_cancel_enroll (enrollment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return (mysqli_query($this->conn, $createSql) !== false);
  }


 public function writeAuditLog($userId, $actionType, $affectedTable = null, $affectedRecord = null, $description = null, $username = null, $ipAddress = null, $userAgent = null, $sessionDurationSeconds = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }

    if (!$this->ensureAuditLogTable()) {
      return false;
    }

    $sql = "INSERT INTO st_audit_log (user_id, action_type, affected_table, affected_record, description, username, ip_address, browser_user_agent, session_duration_seconds) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($this->conn, $sql);

    if (!$stmt) {
      return false;
    }

    $safeUserId = intval($userId);
    $safeActionType = (string) $actionType;
    $safeAffectedTable = $affectedTable !== null ? (string) $affectedTable : null;
    $safeAffectedRecord = $affectedRecord !== null ? intval($affectedRecord) : null;
    $safeDescription = $description !== null ? (string) $description : null;
    $safeUsername = $username !== null ? (string) $username : null;
    $safeIpAddress = $ipAddress !== null ? (string) $ipAddress : null;
    $safeUserAgent = $userAgent !== null ? (string) $userAgent : null;
    $safeSessionDuration = $sessionDurationSeconds !== null ? intval($sessionDurationSeconds) : null;

    mysqli_stmt_bind_param($stmt, "ississssi", $safeUserId, $safeActionType, $safeAffectedTable, $safeAffectedRecord, $safeDescription, $safeUsername, $safeIpAddress, $safeUserAgent, $safeSessionDuration);
    $result = mysqli_stmt_execute($stmt);
    $insertId = $result ? mysqli_insert_id($this->conn) : false;
    mysqli_stmt_close($stmt);

    return $insertId;
  }

  public function completeAuditSession($auditId, $userId, $description = null, $sessionDurationSeconds = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }

    if (!$this->ensureAuditLogTable()) {
      return false;
    }

    $sql = "UPDATE st_audit_log
            SET logout_at = NOW(),
                session_duration_seconds = ?,
                description = CONCAT(COALESCE(description, ''), ?)
            WHERE audit_id = ?
              AND user_id = ?
              AND action_type = 'LOGIN_SUCCESS'
            LIMIT 1";
    $stmt = mysqli_prepare($this->conn, $sql);

    if (!$stmt) {
      return false;
    }

    $safeDuration = $sessionDurationSeconds !== null ? intval($sessionDurationSeconds) : null;
    $safeDescription = $description !== null ? "\n" . (string) $description : '';
    $safeAuditId = intval($auditId);
    $safeUserId = intval($userId);

    mysqli_stmt_bind_param($stmt, "isii", $safeDuration, $safeDescription, $safeAuditId, $safeUserId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
  }

  public function writeLogoutLog($userId, $loginId = null, $username = null, $ipAddress = null, $userAgent = null, $sessionDurationSeconds = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }

    $sql = "INSERT INTO st_user_logout_log (user_id, login_id, username, ip_address, browser_user_agent, session_duration_seconds) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($this->conn, $sql);

    if (!$stmt) {
      return false;
    }

    $safeUserId = intval($userId);
    $safeLoginId = $loginId !== null ? intval($loginId) : null;
    $safeUsername = $username !== null ? (string) $username : null;
    $safeIpAddress = $ipAddress !== null ? (string) $ipAddress : null;
    $safeUserAgent = $userAgent !== null ? (string) $userAgent : null;
    $safeSessionDuration = $sessionDurationSeconds !== null ? intval($sessionDurationSeconds) : null;

    mysqli_stmt_bind_param($stmt, "iisssi", $safeUserId, $safeLoginId, $safeUsername, $safeIpAddress, $safeUserAgent, $safeSessionDuration);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
  }

 public function loginPage($myusername, $mypassword)
  {
    $mypassword = md5($mypassword);

    $sql = "SELECT * FROM (SELECT * FROM user_master_activate UNION SELECT * FROM user_master ) AS U WHERE U.user_name = ? and U.password = ? AND U.flag = '1' AND U.status = '1'";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ss", $myusername, $mypassword);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return 1;
    } else {
      return 0;
    }

  } 

  public function autoAllocateMentor($studentId, $subjectId, $semesterId)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }
    
    $studentId = intval($studentId);
    $subjectId = intval($subjectId);
    $semesterId = intval($semesterId);
    
    if ($studentId <= 0 || $subjectId <= 0 || $semesterId <= 0) {
      return false;
    }

    // Fetch student's academic_year_id and department_id
    $ayId = intval($academicYearId ?? 0);
    $deptId = 0;
    $sRes = mysqli_query($this->conn, "SELECT academic_year_id, department_id FROM st_student_master WHERE student_id = $studentId LIMIT 1");
    if ($sRes && ($sRow = mysqli_fetch_assoc($sRes))) {
      if ($ayId <= 0) {
        $ayId = intval($sRow['academic_year_id'] ?? 1);
      }
      $deptId = intval($sRow['department_id'] ?? 0);
    }
    if ($ayId <= 0) {
      $ayId = 1;
    }
    
    // Find active mentors strictly mapped to this subject, semester, and academic year
    $sql = "SELECT msm.mentor_id, COUNT(ms.mapping_id) as student_count 
            FROM st_mentor_subject_mapping msm
            JOIN st_user_master u ON u.user_id = msm.mentor_id AND u.role_id = 4
            LEFT JOIN st_mentor_student_mapping ms ON ms.mentor_id = msm.mentor_id AND ms.semester_id = ? AND ms.academic_year_id = ?
            WHERE msm.subject_id = ?
              AND msm.semester_id = ?
              AND msm.academic_year_id = ?
              AND msm.is_active = 1
              AND (msm.department_id IS NULL OR msm.department_id = 0 OR msm.department_id = ? OR u.department_id = ?)
            GROUP BY msm.mentor_id
            ORDER BY student_count ASC, msm.mentor_id ASC";
            
    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      return false;
    }
    
    mysqli_stmt_bind_param($stmt, "iiiiiii", $semesterId, $ayId, $subjectId, $semesterId, $ayId, $deptId, $deptId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $matches = [];
    if ($result) {
      while ($r = mysqli_fetch_assoc($result)) {
        $matches[] = $r;
      }
    }
    mysqli_stmt_close($stmt);

    if (empty($matches)) {
      // Rule: Never silently fall back to a mentor from another semester or academic year!
      return false;
    }

    $mentorId = intval($matches[0]['mentor_id']);

    // A student may have a different mentor in every semester. Never remove mappings belonging to another semester.
    $deleteSql = "DELETE FROM st_mentor_student_mapping WHERE student_id = ? AND semester_id = ?";
    $delStmt = mysqli_prepare($this->conn, $deleteSql);
    if ($delStmt) {
      mysqli_stmt_bind_param($delStmt, "ii", $studentId, $semesterId);
      mysqli_stmt_execute($delStmt);
      mysqli_stmt_close($delStmt);
    }
    
    // Insert new mapping with academic_year_id
    $insertSql = "INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id, academic_year_id) VALUES (?, ?, ?, ?)";
    $insStmt = mysqli_prepare($this->conn, $insertSql);
    if ($insStmt) {
      mysqli_stmt_bind_param($insStmt, "iiii", $mentorId, $studentId, $semesterId, $ayId);
      $res = mysqli_stmt_execute($insStmt);
      mysqli_stmt_close($insStmt);
      
      if ($res) {
        $upHist = mysqli_prepare($this->conn, "UPDATE st_student_semester_history SET mentor_id = ? WHERE student_id = ? AND semester_id = ? AND status = 'Active'");
        if ($upHist) {
          mysqli_stmt_bind_param($upHist, "iii", $mentorId, $studentId, $semesterId);
          mysqli_stmt_execute($upHist);
          mysqli_stmt_close($upHist);
        }
        if (method_exists($this, 'writeAuditLog')) {
          $this->writeAuditLog($studentId, 'AUTO_MENTOR_ALLOCATION', 'st_mentor_student_mapping', null, "Automatically assigned mentor ID {$mentorId} due to specialization subject selection");
        }
        return true;
      }
    }
    
    return false;
  }

  public function matchesEligibilityString($deptRow, $eligibilityText)
  {
    if (empty($eligibilityText) || trim($eligibilityText) === '—' || trim($eligibilityText) === '-') {
      return false;
    }
    
    $trimmed = trim($eligibilityText);
    if (strcasecmp($trimmed, 'All UG Programs') === 0 || strcasecmp($trimmed, 'All UG') === 0 || strcasecmp($trimmed, 'All Branch') === 0) {
      return true;
    }

    $deptCode = strtoupper(trim($deptRow['department_name'] ?? ''));
    $progName = trim($deptRow['program_name'] ?? '');

    $norm = function($str) {
      $str = strtolower($str);
      $str = str_replace(['&', 'and'], 'and', $str);
      return preg_replace('/[^a-z0-9]/', '', $str);
    };

    $normProg = $norm($progName);

    $codeMap = [
      'CE' => ['computer engineering', 'be computer engineering'],
      'IT' => ['information technology', 'be information technology'],
      'EXTC' => ['electronics & telecommunication', 'electronics and telecommunication', 'electronics & communication', 'extc'],
      'ECS' => ['electronics & computer science', 'electronics and computer science', 'ecs'],
      'MECH' => ['mechanical engineering', 'be mechanical engineering', 'mech'],
      'CIVIL' => ['civil engineering', 'be civil engineering', 'civil'],
      'CSE-CS' => ['computer science & engineering (cyber security)', 'computer science and engineering (cybersecurity)', 'computer science & engineering (cybersecurity)', 'cyber security', 'cse-cs'],
      'MME' => ['mechanical & mechatronics', 'mechanical and mechatronics', 'mechanical & mechatronics engineering', 'mme'],
      'AIDS' => ['artificial intelligence & data science', 'artificial intelligence and data science', 'aids'],
      'AIML' => ['artificial intelligence & machine learning', 'artificial intelligence and machine learning', 'aiml'],
      'IOT' => ['internet of things', 'btech internet of things', 'be internet of things', 'iot'],
      'MBA' => ['mba', 'master of business administration'],
      'MCA' => ['mca', 'master of computer applications'],
      'BCA' => ['bca', 'bachelor of computer applications'],
    ];

    $parts = preg_split('/[;\/]+/', $eligibilityText);
    foreach ($parts as $part) {
      $part = trim($part);
      if ($part === '') continue;

      $normPart = $norm($part);
      if ($normProg !== '' && (strpos($normProg, $normPart) !== false || strpos($normPart, $normProg) !== false)) {
        return true;
      }

      if (isset($codeMap[$deptCode])) {
        foreach ($codeMap[$deptCode] as $alias) {
          $normAlias = $norm($alias);
          if (strpos($normPart, $normAlias) !== false || strpos($normAlias, $normPart) !== false) {
            return true;
          }
        }
      }
    }

    return false;
  }

  public function isDepartmentEligibleForSpecialization($departmentId, $specializationId, $trackId = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }
    $departmentId = intval($departmentId);
    $specializationId = intval($specializationId);
    if ($departmentId <= 0 || $specializationId <= 0) {
      return false;
    }

    $stmt = mysqli_prepare($this->conn, "SELECT specialization_id, specialization_name, is_track, track_code, honors_eligibility, minor_eligibility FROM st_specialization_master WHERE specialization_id = ? LIMIT 1");
    if (!$stmt) {
      return false;
    }
    mysqli_stmt_bind_param($stmt, "i", $specializationId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $specRow = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$specRow) {
      return false;
    }

    if (!empty($specRow['is_track'])) {
      return true;
    }

    $dStmt = mysqli_prepare($this->conn, "SELECT department_name, program_name FROM st_department_master WHERE department_id = ? LIMIT 1");
    if (!$dStmt) {
      return false;
    }
    mysqli_stmt_bind_param($dStmt, "i", $departmentId);
    mysqli_stmt_execute($dStmt);
    $dRes = mysqli_stmt_get_result($dStmt);
    $deptRow = ($dRes && mysqli_num_rows($dRes) > 0) ? mysqli_fetch_assoc($dRes) : null;
    mysqli_stmt_close($dStmt);

    if (!$deptRow) {
      return false;
    }

    $trackId = intval($trackId);
    if ($trackId === 1 || $trackId === 2) {
      if ($trackId === 2 && $this->isNonBeDepartment($departmentId)) {
        return false;
      }
      return $this->matchesEligibilityString($deptRow, $specRow['honors_eligibility']);
    }
    if ($trackId === 3) {
      return $this->matchesEligibilityString($deptRow, $specRow['minor_eligibility']);
    }
    if ($trackId === 4) {
      return true;
    }

    return $this->matchesEligibilityString($deptRow, $specRow['honors_eligibility']) ||
           $this->matchesEligibilityString($deptRow, $specRow['minor_eligibility']);
  }

  public function getClassNameById($classId)
  {
    if (!($this->conn instanceof mysqli)) {
      return '';
    }
    $classId = intval($classId);
    if ($classId <= 0) {
      return '';
    }
    $stmt = mysqli_prepare($this->conn, "SELECT class_name FROM st_class_master WHERE class_id = ? LIMIT 1");
    if (!$stmt) {
      return '';
    }
    mysqli_stmt_bind_param($stmt, "i", $classId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return strtoupper(trim($row['class_name'] ?? ''));
  }

  public function isNonBeDepartment($departmentId)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }
    $departmentId = intval($departmentId);
    if ($departmentId <= 0) {
      return false;
    }
    $stmt = mysqli_prepare($this->conn, "SELECT department_name, program_name FROM st_department_master WHERE department_id = ? LIMIT 1");
    if (!$stmt) {
      return false;
    }
    mysqli_stmt_bind_param($stmt, "i", $departmentId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$row) {
      return false;
    }
    $dName = strtoupper(trim($row['department_name'] ?? ''));
    $pName = strtoupper(trim($row['program_name'] ?? ''));

    if ($dName === 'BCA' || $dName === 'MCA' || $dName === 'MBA') {
      return true;
    }
    if (strpos($pName, 'BACHELOR OF COMPUTER APPLICATIONS') !== false ||
        strpos($pName, 'MASTER OF COMPUTER APPLICATIONS') !== false ||
        strpos($pName, 'MBA') !== false) {
      return true;
    }
    return false;
  }

  public function isTrackEligible($trackId, $classId = null, $semesterId = null, $departmentId = null)
  {
    $trackId = intval($trackId);
    if ($trackId <= 0) {
      return false;
    }

    // Check First Year (FY/FE) restriction for all specializations/tracks
    if ($classId !== null && intval($classId) > 0) {
      $cName = $this->getClassNameById($classId);
      if ($cName === 'FY' || $cName === 'FE' || stripos($cName, 'FIRST YEAR') !== false) {
        return false;
      }
    }

    if ($trackId !== 2) {
      // Tracks 1 (Honours Degree), 3 (Minor Degree), 4 (Minor Multidisciplinary)
      return true;
    }

    // Track 2: Honours with Research
    // Official rule: Available ONLY to BE students in Semester 7 and Semester 8.
    // 1. Program / Class validation
    if ($classId === null || intval($classId) <= 0) {
      return false;
    }
    $cName = $this->getClassNameById($classId);
    if ($cName !== 'BE') {
      return false;
    }

    // 2. Semester validation
    if ($semesterId === null || intval($semesterId) <= 0) {
      return false;
    }
    $sem = intval($semesterId);
    if ($sem !== 7 && $sem !== 8) {
      return false;
    }

    // 3. Department / Program validation: non-BE programs (e.g. BCA, MCA, MBA) are ineligible
    if ($departmentId !== null && intval($departmentId) > 0) {
      if ($this->isNonBeDepartment($departmentId)) {
        return false;
      }
    }

    return true;
  }

  public function getEligibleTracks($classId = null, $semesterId = null, $departmentId = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return [];
    }

    // FY students are not offered specializations
    if ($classId !== null && intval($classId) > 0) {
      $cName = $this->getClassNameById($classId);
      if ($cName === 'FY' || $cName === 'FE' || stripos($cName, 'FIRST YEAR') !== false) {
        return [];
      }
    }

    $res = mysqli_query($this->conn, "SELECT specialization_id, specialization_name, track_code, sem_from, sem_to, offering_program FROM st_specialization_master WHERE is_track = 1 ORDER BY specialization_id ASC");
    if (!$res) {
      return [];
    }

    $tracks = [];
    while ($row = mysqli_fetch_assoc($res)) {
      $tId = intval($row['specialization_id']);
      if ($this->isTrackEligible($tId, $classId, $semesterId, $departmentId)) {
        $tracks[] = [
          'specialization_id' => $tId,
          'specialization_name' => $row['specialization_name'],
          'track_code' => $row['track_code'] ?? ''
        ];
      }
    }

    return $tracks;
  }

  public function getEligibleSpecializations($departmentId, $trackId, $classId = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return [];
    }
    $departmentId = intval($departmentId);
    $trackId = intval($trackId);

    if ($classId !== null) {
      $classId = intval($classId);
      $cStmt = mysqli_prepare($this->conn, "SELECT class_name FROM st_class_master WHERE class_id = ? LIMIT 1");
      if ($cStmt) {
        mysqli_stmt_bind_param($cStmt, "i", $classId);
        mysqli_stmt_execute($cStmt);
        $cRes = mysqli_stmt_get_result($cStmt);
        if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
          $cName = strtoupper($cRow['class_name'] ?? '');
          if ($cName === 'FY' || $cName === 'FE' || stripos($cName, 'FIRST YEAR') !== false) {
            mysqli_stmt_close($cStmt);
            return [];
          }
        }
        mysqli_stmt_close($cStmt);
      }
    }

    if ($trackId === 2) {
      if ($this->isNonBeDepartment($departmentId)) {
        return [];
      }
      if ($classId !== null) {
        $cName = $this->getClassNameById($classId);
        if ($cName !== 'BE') {
          return [];
        }
      }
    }

    if ($trackId === 4) {
      return [
        ['specialization_id' => 4, 'specialization_name' => 'Minor Multidisciplinary']
      ];
    }

    $res = mysqli_query($this->conn, "SELECT specialization_id, specialization_name, honors_eligibility, minor_eligibility FROM st_specialization_master WHERE is_track = 0 ORDER BY specialization_id ASC");
    if (!$res) {
      return [];
    }

    $dStmt = mysqli_prepare($this->conn, "SELECT department_name, program_name FROM st_department_master WHERE department_id = ? LIMIT 1");
    if (!$dStmt) {
      return [];
    }
    mysqli_stmt_bind_param($dStmt, "i", $departmentId);
    mysqli_stmt_execute($dStmt);
    $dRes = mysqli_stmt_get_result($dStmt);
    $deptRow = ($dRes && mysqli_num_rows($dRes) > 0) ? mysqli_fetch_assoc($dRes) : null;
    mysqli_stmt_close($dStmt);

    if (!$deptRow) {
      return [];
    }

    $eligible = [];
    while ($row = mysqli_fetch_assoc($res)) {
      $isOk = false;
      if ($trackId === 1 || $trackId === 2) {
        $isOk = $this->matchesEligibilityString($deptRow, $row['honors_eligibility']);
      } elseif ($trackId === 3) {
        $isOk = $this->matchesEligibilityString($deptRow, $row['minor_eligibility']);
      } else {
        $isOk = $this->matchesEligibilityString($deptRow, $row['honors_eligibility']) ||
                $this->matchesEligibilityString($deptRow, $row['minor_eligibility']);
      }

      if ($isOk) {
        $eligible[] = [
          'specialization_id' => intval($row['specialization_id']),
          'specialization_name' => $row['specialization_name']
        ];
      }
    }

    return $eligible;
  }

  public function getEligibleSubjects($specializationId, $semesterId, $departmentId = null, $trackId = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return [];
    }
    $specializationId = intval($specializationId);
    $semesterId = intval($semesterId);
    $departmentId = intval($departmentId);
    $trackId = intval($trackId);

    if ($specializationId <= 0 || $semesterId <= 0) {
      return [];
    }

    if ($departmentId > 0 && !$this->isDepartmentEligibleForSpecialization($departmentId, $specializationId, $trackId)) {
      return [];
    }

    $stmt = mysqli_prepare($this->conn, "SELECT subject_id, subject_code, subject_name 
                                         FROM st_specialization_subject_master 
                                         WHERE specialization_id = ? AND semester_id = ? AND is_active = 1 
                                         ORDER BY subject_code, subject_name");
    if (!$stmt) {
      return [];
    }
    mysqli_stmt_bind_param($stmt, "ii", $specializationId, $semesterId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $subjects = [];
    if ($res) {
      while ($row = mysqli_fetch_assoc($res)) {
        $code = trim($row['subject_code'] ?? '');
        $name = trim($row['subject_name'] ?? '');
        $display = !empty($code) ? ($code . ' — ' . $name) : $name;
        $subjects[] = [
          'subject_id' => intval($row['subject_id']),
          'subject_code' => $code,
          'subject_name' => $display,
          'raw_name' => $name
        ];
      }
    }
    mysqli_stmt_close($stmt);
    return $subjects;
  }

  public function isSubjectAvailable($subjectId, $departmentId, $semesterId, $specializationId, $trackId = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }
    $subjectId = intval($subjectId);
    $departmentId = intval($departmentId);
    $semesterId = intval($semesterId);
    $specializationId = intval($specializationId);

    if ($subjectId <= 0 || $semesterId <= 0 || $specializationId <= 0) {
      return false;
    }

    $stmt = mysqli_prepare($this->conn, "SELECT subject_id, department_id FROM st_specialization_subject_master WHERE subject_id = ? AND semester_id = ? AND specialization_id = ? AND is_active = 1 LIMIT 1");
    if (!$stmt) {
      return false;
    }
    mysqli_stmt_bind_param($stmt, "iii", $subjectId, $semesterId, $specializationId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
      $stmtLegacy = mysqli_prepare($this->conn, "SELECT subject_id FROM st_specialization_subject_master WHERE subject_id = ? AND department_id = ? AND semester_id = ? AND specialization_id = ? AND is_active = 1 LIMIT 1");
      if ($stmtLegacy) {
        mysqli_stmt_bind_param($stmtLegacy, "iiii", $subjectId, $departmentId, $semesterId, $specializationId);
        mysqli_stmt_execute($stmtLegacy);
        $resLegacy = mysqli_stmt_get_result($stmtLegacy);
        $legacyOk = ($resLegacy && mysqli_num_rows($resLegacy) > 0);
        mysqli_stmt_close($stmtLegacy);
        if ($legacyOk) {
          return true;
        }
      }
      return false;
    }

    if ($departmentId > 0) {
      if (!$this->isDepartmentEligibleForSpecialization($departmentId, $specializationId, $trackId)) {
        return false;
      }
    }

    return true;
  }

  public function syncStudentSemesterHistory($studentId, $semesterId)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }
    
    $studentId = intval($studentId);
    $semesterId = intval($semesterId);
    
    if ($studentId <= 0 || $semesterId <= 0) {
      return false;
    }
    
    // Fetch current snapshot from st_student_master
    $sql = "SELECT academic_year_id, class_id, department_id, division_id, roll_no, specialization_id, specialization_subject_id,
                   minor_course_id, minor_subject_id, cgpa, specialization_track_id
            FROM st_student_master
            WHERE student_id = ?
            LIMIT 1";
            
    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
      mysqli_stmt_close($stmt);
      
      $ayId = ($row['academic_year_id'] !== null && intval($row['academic_year_id']) > 0) ? intval($row['academic_year_id']) : 2;
      $classId = ($row['class_id'] !== null && intval($row['class_id']) > 0) ? intval($row['class_id']) : 1;
      $deptId = ($row['department_id'] !== null && intval($row['department_id']) > 0) ? intval($row['department_id']) : 1;
      $divId = ($row['division_id'] !== null && intval($row['division_id']) > 0) ? intval($row['division_id']) : 1;
      $rollNo = $row['roll_no'] !== null ? trim((string)$row['roll_no']) : '';
      $specId = ($row['specialization_id'] !== null && intval($row['specialization_id']) > 0) ? intval($row['specialization_id']) : null;
      $specSubId = ($row['specialization_subject_id'] !== null && intval($row['specialization_subject_id']) > 0) ? intval($row['specialization_subject_id']) : null;
      $minorCourseId = ($row['minor_course_id'] !== null && intval($row['minor_course_id']) > 0) ? intval($row['minor_course_id']) : null;
      $minorSubId = ($row['minor_subject_id'] !== null && intval($row['minor_subject_id']) > 0) ? intval($row['minor_subject_id']) : null;
      $cgpa = $row['cgpa'] !== null ? floatval($row['cgpa']) : null;
      $trackId = ($row['specialization_track_id'] !== null && intval($row['specialization_track_id']) > 0) ? intval($row['specialization_track_id']) : null;
      
      // Dynamic period-aware mentor resolution
      $mentorId = null;
      if ($specSubId !== null && $specSubId > 0) {
        $resolvedMentor = $this->getResolvedMentorForSubject($specSubId, $semesterId, $ayId);
        if ($resolvedMentor && intval($resolvedMentor['mentor_id']) > 0) {
          $mentorId = intval($resolvedMentor['mentor_id']);
        }
      }

      // UPSERT only the requested semester.
      $upsertSql = "INSERT INTO st_student_semester_history (
                      student_id, academic_year_id, class_id, semester_id, department_id, division_id, roll_no,
                      specialization_id, specialization_subject_id, minor_course_id, minor_subject_id,
                      cgpa, mentor_id, specialization_track_id, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
                    ON DUPLICATE KEY UPDATE
                      academic_year_id = IF(status = 'Active', VALUES(academic_year_id), academic_year_id),
                      class_id = IF(status = 'Active', VALUES(class_id), class_id),
                      department_id = IF(status = 'Active', VALUES(department_id), department_id),
                      division_id = IF(status = 'Active', VALUES(division_id), division_id),
                      roll_no = IF(status = 'Active', VALUES(roll_no), roll_no),
                      specialization_id = IF(status = 'Active', VALUES(specialization_id), specialization_id),
                      specialization_subject_id = IF(status = 'Active', VALUES(specialization_subject_id), specialization_subject_id),
                      minor_course_id = IF(status = 'Active', VALUES(minor_course_id), minor_course_id),
                      minor_subject_id = IF(status = 'Active', VALUES(minor_subject_id), minor_subject_id),
                      cgpa = IF(status = 'Active', VALUES(cgpa), cgpa),
                      mentor_id = COALESCE(VALUES(mentor_id), mentor_id),
                      specialization_track_id = IF(status = 'Active', VALUES(specialization_track_id), specialization_track_id)";
                      
      $upStmt = mysqli_prepare($this->conn, $upsertSql);
      if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "iiiiiisiiiidii",
          $studentId, $ayId, $classId, $semesterId, $deptId, $divId, $rollNo,
          $specId, $specSubId, $minorCourseId, $minorSubId,
          $cgpa, $mentorId, $trackId
        );
        $res = mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);
        return $res;
      }
    } else {
      mysqli_stmt_close($stmt);
    }
    return false;

  }

  public function finalizeStudentSemester($studentId, $semesterId)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0 || intval($semesterId) <= 0) {
      return false;
    }

    $stmt = mysqli_prepare($this->conn,
      "UPDATE st_student_semester_history
       SET status = 'Completed', finalized_at = COALESCE(finalized_at, CURRENT_TIMESTAMP)
       WHERE student_id = ? AND semester_id = ? AND status = 'Active'");
    if (!$stmt) {
      return false;
    }
    $studentId = intval($studentId);
    $semesterId = intval($semesterId);
    mysqli_stmt_bind_param($stmt, "ii", $studentId, $semesterId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
  }

  public function removeInvalidMentorAllocation($mentorId)
  {
    if (!($this->conn instanceof mysqli) || intval($mentorId) <= 0) {
      return 0;
    }
    $mentorId = intval($mentorId);
    
    // Find all student allocations for this mentor where no active allocation matches student's exact subject, current semester, academic year, and department
    $sql = "
      SELECT msm.student_id, msm.semester_id
      FROM st_mentor_student_mapping msm
      JOIN st_student_master sm ON sm.student_id = msm.student_id AND sm.current_semester_id = msm.semester_id
      LEFT JOIN st_student_semester_history h_current ON h_current.student_id = sm.student_id AND h_current.semester_id = sm.current_semester_id
      JOIN st_user_master mentor ON mentor.user_id = msm.mentor_id
      WHERE msm.mentor_id = ?
        AND sm.status = 1
        AND NOT EXISTS (
          SELECT 1 FROM st_mentor_subject_mapping alloc
          WHERE alloc.mentor_id = mentor.user_id
            AND alloc.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id, 0)
            AND alloc.semester_id = sm.current_semester_id
            AND alloc.academic_year_id = sm.academic_year_id
            AND alloc.is_active = 1
            AND (alloc.department_id IS NULL OR alloc.department_id = 0 OR alloc.department_id = sm.department_id OR mentor.department_id = sm.department_id)
        )
    ";
    
    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      return 0;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $mentorId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $removedCount = 0;
    if ($result) {
      while ($row = mysqli_fetch_assoc($result)) {
        $sId = intval($row['student_id']);
        $semId = intval($row['semester_id']);
        
        // Remove mapping
        $delQuery = "DELETE FROM st_mentor_student_mapping WHERE student_id = $sId AND semester_id = $semId";
        if (mysqli_query($this->conn, $delQuery)) {
          // Clear active semester history record's mentor_id
          $upQuery = "UPDATE st_student_semester_history SET mentor_id = NULL WHERE student_id = $sId AND semester_id = $semId AND status = 'Active'";
          mysqli_query($this->conn, $upQuery);
          $removedCount++;
        }
      }
    }
    mysqli_stmt_close($stmt);
    return $removedCount;
  }

  public function allocateStudentToMentor($studentId, $mentorId, $semesterId, $academicYearId = null)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0 || intval($mentorId) <= 0 || intval($semesterId) <= 0) {
      return false;
    }
    $studentId = intval($studentId);
    $mentorId = intval($mentorId);
    $semesterId = intval($semesterId);

    // Fetch student's academic context
    $sRes = mysqli_query($this->conn, "SELECT student_id, department_id, specialization_subject_id, academic_year_id, current_semester_id FROM st_student_master WHERE student_id = $studentId LIMIT 1");
    if (!$sRes || !($student = mysqli_fetch_assoc($sRes))) {
      return false;
    }

    $ayId = ($academicYearId !== null && intval($academicYearId) > 0) ? intval($academicYearId) : intval($student['academic_year_id'] ?? 1);
    if ($ayId <= 0) {
      $ayId = 1;
    }
    $deptId = intval($student['department_id'] ?? 0);
    $subjId = intval($student['specialization_subject_id'] ?? 0);

    // Check student semester history for subject if set for this semester
    $hRes = mysqli_query($this->conn, "SELECT specialization_subject_id, department_id, academic_year_id FROM st_student_semester_history WHERE student_id = $studentId AND semester_id = $semesterId ORDER BY history_id DESC LIMIT 1");
    if ($hRes && ($hRow = mysqli_fetch_assoc($hRes))) {
      if (intval($hRow['specialization_subject_id']) > 0) {
        $subjId = intval($hRow['specialization_subject_id']);
      }
      if (intval($hRow['department_id']) > 0) {
        $deptId = intval($hRow['department_id']);
      }
      if ($academicYearId === null && intval($hRow['academic_year_id']) > 0) {
        $ayId = intval($hRow['academic_year_id']);
      }
    }

    if ($subjId <= 0) {
      return false;
    }

    // Validate mentor exists, is active (status=1), and has an active allocation matching this exact 4-key context:
    // (subject_id, semester_id, academic_year_id, department_id, is_active=1)
    $validateSql = "
      SELECT 1 
      FROM st_user_master mentor
      JOIN st_mentor_subject_mapping ms_map ON ms_map.mentor_id = mentor.user_id
      WHERE mentor.user_id = ?
        AND mentor.role_id = 4
        AND ms_map.subject_id = ?
        AND ms_map.semester_id = ?
        AND ms_map.academic_year_id = ?
        AND ms_map.is_active = 1
        AND (ms_map.department_id IS NULL OR ms_map.department_id = 0 OR ms_map.department_id = ? OR mentor.department_id = ?)
      LIMIT 1
    ";
    
    $stmt = mysqli_prepare($this->conn, $validateSql);
    if (!$stmt) {
      return false;
    }
    mysqli_stmt_bind_param($stmt, "iiiiii", $mentorId, $subjId, $semesterId, $ayId, $deptId, $deptId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $isValid = $result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    if (!$isValid) {
      return false;
    }

    // Check if mentor is already assigned
    $checkSql = "SELECT mentor_id FROM st_mentor_student_mapping WHERE student_id = ? AND semester_id = ? LIMIT 1";
    $cStmt = mysqli_prepare($this->conn, $checkSql);
    if ($cStmt) {
      mysqli_stmt_bind_param($cStmt, "ii", $studentId, $semesterId);
      mysqli_stmt_execute($cStmt);
      $cRes = mysqli_stmt_get_result($cStmt);
      if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
        $existingMentorId = intval($cRow['mentor_id']);
        mysqli_stmt_close($cStmt);
        if ($existingMentorId === $mentorId) {
          return true;
        }
      } else {
        mysqli_stmt_close($cStmt);
      }
    }

    // Upsert mapping in st_mentor_student_mapping
    $insertSql = "
      INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id, academic_year_id)
      VALUES (?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id), academic_year_id = VALUES(academic_year_id)
    ";
    $insStmt = mysqli_prepare($this->conn, $insertSql);
    if ($insStmt) {
      mysqli_stmt_bind_param($insStmt, "iiii", $mentorId, $studentId, $semesterId, $ayId);
      $ok = mysqli_stmt_execute($insStmt);
      mysqli_stmt_close($insStmt);
      
      if ($ok) {
        // Update history
        $upHistory = "
          UPDATE st_student_semester_history 
          SET mentor_id = ? 
          WHERE student_id = ? AND semester_id = ? AND status = 'Active'
        ";
        $histStmt = mysqli_prepare($this->conn, $upHistory);
        if ($histStmt) {
          mysqli_stmt_bind_param($histStmt, "iii", $mentorId, $studentId, $semesterId);
          mysqli_stmt_execute($histStmt);
          mysqli_stmt_close($histStmt);
        }
        return true;
      }
    }
    return false;
  }

  public function recalculateMentorStudents($mentorId)
  {
    if (!($this->conn instanceof mysqli) || intval($mentorId) <= 0) {
      return array('removed' => 0, 'allocated' => 0, 'current' => 0);
    }
    $mentorId = intval($mentorId);

    // Step 1: Remove invalid active allocations
    $removedCount = $this->removeInvalidMentorAllocation($mentorId);

    // Step 2: Find all eligible students who match any active allocation of this mentor
    // and currently do not have a valid active mentor matching their exact academic context
    $eligibleSql = "
      SELECT sm.student_id, sm.current_semester_id, sm.academic_year_id
      FROM st_student_master sm
      LEFT JOIN st_student_semester_history h_current ON h_current.student_id = sm.student_id AND h_current.semester_id = sm.current_semester_id
      JOIN st_user_master mentor ON mentor.user_id = ? AND mentor.role_id = 4
      JOIN st_mentor_subject_mapping alloc ON alloc.mentor_id = mentor.user_id 
        AND alloc.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id)
        AND alloc.semester_id = sm.current_semester_id
        AND alloc.academic_year_id = sm.academic_year_id
        AND alloc.is_active = 1
        AND (alloc.department_id IS NULL OR alloc.department_id = 0 OR alloc.department_id = sm.department_id OR mentor.department_id = sm.department_id)
      LEFT JOIN st_mentor_student_mapping msm ON msm.student_id = sm.student_id AND msm.semester_id = sm.current_semester_id
      WHERE sm.status = 1
        AND (
          msm.mentor_id IS NULL
          OR NOT EXISTS (
            SELECT 1 FROM st_mentor_subject_mapping valid_alloc
            JOIN st_user_master cur_m ON cur_m.user_id = valid_alloc.mentor_id AND cur_m.role_id = 4
            WHERE valid_alloc.mentor_id = msm.mentor_id
              AND valid_alloc.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id)
              AND valid_alloc.semester_id = sm.current_semester_id
              AND valid_alloc.academic_year_id = sm.academic_year_id
              AND valid_alloc.is_active = 1
          )
        )
    ";

    $stmt = mysqli_prepare($this->conn, $eligibleSql);
    $allocatedCount = 0;
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "i", $mentorId);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
          $sId = intval($row['student_id']);
          $semId = intval($row['current_semester_id']);
          $ayId = max(1, intval($row['academic_year_id']));
          
          $insertSql = "
            INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id, academic_year_id)
            VALUES ($mentorId, $sId, $semId, $ayId)
            ON DUPLICATE KEY UPDATE mentor_id = $mentorId, academic_year_id = $ayId
          ";
          if (mysqli_query($this->conn, $insertSql)) {
            $upHistory = "
              UPDATE st_student_semester_history 
              SET mentor_id = $mentorId 
              WHERE student_id = $sId AND semester_id = $semId AND status = 'Active'
            ";
            mysqli_query($this->conn, $upHistory);
            $allocatedCount++;
          }
        }
      }
      mysqli_stmt_close($stmt);
    }

    // Step 3: Count current active allocations for this mentor
    $currentCount = 0;
    $countSql = "
      SELECT COUNT(*) AS total 
      FROM st_mentor_student_mapping msm
      JOIN st_student_master sm ON sm.student_id = msm.student_id AND sm.current_semester_id = msm.semester_id
      WHERE msm.mentor_id = ? AND sm.status = 1
    ";
    $cStmt = mysqli_prepare($this->conn, $countSql);
    if ($cStmt) {
      mysqli_stmt_bind_param($cStmt, "i", $mentorId);
      mysqli_stmt_execute($cStmt);
      $cRes = mysqli_stmt_get_result($cStmt);
      if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
        $currentCount = intval($cRow['total']);
      }
      mysqli_stmt_close($cStmt);
    }

    return array(
      'removed' => $removedCount,
      'allocated' => $allocatedCount,
      'current' => $currentCount
    );
  }

  public function getMatchingMentorsForAllocation($subjectId, $semesterId, $academicYearId, $departmentId = null)
  {
    if (!($this->conn instanceof mysqli) || intval($subjectId) <= 0 || intval($semesterId) <= 0 || intval($academicYearId) <= 0) {
      return array();
    }
    $subjectId = intval($subjectId);
    $semesterId = intval($semesterId);
    $academicYearId = intval($academicYearId);
    $deptId = ($departmentId !== null) ? intval($departmentId) : 0;

    $sql = "
      SELECT u.user_id AS mentor_id,
             COALESCE(NULLIF(TRIM(u.user_name), ''), u.email_id) AS mentor_name,
             u.email_id,
             u.phone_number,
             u.department_id,
             d.department_name,
             msm.mapping_id,
             msm.subject_id,
             msm.semester_id,
             msm.academic_year_id,
             msm.is_active
      FROM st_mentor_subject_mapping msm
      JOIN st_user_master u ON u.user_id = msm.mentor_id
      LEFT JOIN st_department_master d ON d.department_id = u.department_id
      WHERE msm.subject_id = ?
        AND msm.semester_id = ?
        AND msm.academic_year_id = ?
        AND msm.is_active = 1
        AND u.role_id = 4
        " . ($deptId > 0 ? "AND (msm.department_id IS NULL OR msm.department_id = 0 OR msm.department_id = $deptId OR u.department_id = $deptId)" : "") . "
      ORDER BY u.user_name ASC
    ";

    $stmt = mysqli_prepare($this->conn, $sql);
    $mentors = array();
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "iii", $subjectId, $semesterId, $academicYearId);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
          $mentors[] = $row;
        }
      }
      mysqli_stmt_close($stmt);
    }
    return $mentors;
  }

  public function calculateEligibleMentors($studentId, $semesterId = null, $academicYearId = null)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0) {
      return array();
    }
    $studentId = intval($studentId);

    $sRes = mysqli_query($this->conn, "SELECT student_id, department_id, specialization_subject_id, current_semester_id, academic_year_id FROM st_student_master WHERE student_id = $studentId LIMIT 1");
    if (!$sRes || !($student = mysqli_fetch_assoc($sRes))) {
      return array();
    }

    $semId = ($semesterId !== null && intval($semesterId) > 0) ? intval($semesterId) : intval($student['current_semester_id']);
    $ayId = ($academicYearId !== null && intval($academicYearId) > 0) ? intval($academicYearId) : intval($student['academic_year_id'] ?? 1);
    $deptId = intval($student['department_id']);
    $subjId = intval($student['specialization_subject_id']);

    // Check semester history
    $hRes = mysqli_query($this->conn, "SELECT specialization_subject_id, department_id, academic_year_id FROM st_student_semester_history WHERE student_id = $studentId AND semester_id = $semId ORDER BY history_id DESC LIMIT 1");
    if ($hRes && ($hRow = mysqli_fetch_assoc($hRes))) {
      if (intval($hRow['specialization_subject_id']) > 0) {
        $subjId = intval($hRow['specialization_subject_id']);
      }
      if (intval($hRow['department_id']) > 0) {
        $deptId = intval($hRow['department_id']);
      }
      if ($academicYearId === null && intval($hRow['academic_year_id']) > 0) {
        $ayId = intval($hRow['academic_year_id']);
      }
    }

    if ($subjId <= 0 || $semId <= 0 || $ayId <= 0) {
      return array();
    }

    return $this->getMatchingMentorsForAllocation($subjId, $semId, $ayId, $deptId);
  }

  public function removeInvalidStudentMentorAllocation($studentId)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0) {
      return false;
    }
    $studentId = intval($studentId);
    
    // Find active current semester mapping for this student
    $sql = "
      SELECT msm.semester_id, msm.mentor_id, sm.current_semester_id, sm.academic_year_id,
             COALESCE(NULLIF(sm.specialization_subject_id, 0), h.specialization_subject_id, 0) AS subject_id,
             sm.department_id
      FROM st_mentor_student_mapping msm
      JOIN st_student_master sm ON sm.student_id = msm.student_id AND sm.current_semester_id = msm.semester_id
      LEFT JOIN st_student_semester_history h ON h.student_id = sm.student_id AND h.semester_id = sm.current_semester_id
      WHERE msm.student_id = ?
    ";
    
    $stmt = mysqli_prepare($this->conn, $sql);
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "i", $studentId);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
          $semId = intval($row['semester_id']);
          $mentorId = intval($row['mentor_id']);
          $subjId = intval($row['subject_id']);
          $ayId = intval($row['academic_year_id']);
          $deptId = intval($row['department_id']);
          
          // Verify if mentor has an active allocation matching this exact 4-key context
          $validCheck = mysqli_query($this->conn, "
            SELECT 1 
            FROM st_mentor_subject_mapping alloc
            JOIN st_user_master mentor ON mentor.user_id = alloc.mentor_id AND mentor.role_id = 4
            WHERE alloc.mentor_id = $mentorId
              AND alloc.subject_id = $subjId
              AND alloc.semester_id = $semId
              AND alloc.academic_year_id = $ayId
              AND alloc.is_active = 1
              AND (alloc.department_id IS NULL OR alloc.department_id = 0 OR alloc.department_id = $deptId OR mentor.department_id = $deptId)
            LIMIT 1
          ");
          
          if (!$validCheck || mysqli_num_rows($validCheck) === 0) {
            // Mapping is invalid: remove student mapping and clear mentor from active history
            mysqli_query($this->conn, "DELETE FROM st_mentor_student_mapping WHERE student_id = $studentId AND semester_id = $semId");
            mysqli_query($this->conn, "UPDATE st_student_semester_history SET mentor_id = NULL WHERE student_id = $studentId AND semester_id = $semId AND status = 'Active'");
          }
        }
      }
      mysqli_stmt_close($stmt);
    }
    return true;
  }

  public function getResolvedMentorForSubject($subjectId, $semesterId = null, $academicYearId = null, $departmentId = null)
  {
    if (!($this->conn instanceof mysqli) || intval($subjectId) <= 0) {
      return null;
    }
    $subjectId = intval($subjectId);
    $semId = ($semesterId !== null) ? intval($semesterId) : 0;
    $ayId = ($academicYearId !== null) ? intval($academicYearId) : 0;

    // A subject alone must not determine a mentor. Both semester and academic year are mandatory dimensions.
    if ($semId <= 0 || $ayId <= 0) {
      return null;
    }

    $matches = $this->getMatchingMentorsForAllocation($subjectId, $semId, $ayId, $departmentId);
    if (empty($matches)) {
      return null;
    }

    if (count($matches) > 1) {
      // Ambiguity detected: multiple active mentors match the exact context key
      return array(
        'ambiguous' => true,
        'count' => count($matches),
        'mentors' => $matches,
        'error' => "Multiple active mentors match this allocation (Academic Year: {$ayId}, Semester: {$semId}, Subject: {$subjectId}). Please resolve the ambiguity."
      );
    }

    return $matches[0];
  }

  public function getResolvedMentorForStudent($studentId, $semesterId = null, $academicYearId = null)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0) {
      return null;
    }
    $studentId = intval($studentId);

    // Look up student details
    $sRes = mysqli_query($this->conn, "SELECT student_id, department_id, specialization_subject_id, current_semester_id, academic_year_id FROM st_student_master WHERE student_id = $studentId LIMIT 1");
    if (!$sRes || !($sRow = mysqli_fetch_assoc($sRes))) {
      return null;
    }

    $semId = ($semesterId !== null && intval($semesterId) > 0) ? intval($semesterId) : intval($sRow['current_semester_id']);
    $ayId = ($academicYearId !== null && intval($academicYearId) > 0) ? intval($academicYearId) : intval($sRow['academic_year_id'] ?? 1);
    $deptId = intval($sRow['department_id']);
    $subId = intval($sRow['specialization_subject_id']);

    // Check historical ledger for the specified semester
    $hRes = mysqli_query($this->conn, "SELECT specialization_subject_id, department_id, academic_year_id, mentor_id FROM st_student_semester_history WHERE student_id = $studentId AND semester_id = $semId ORDER BY history_id DESC LIMIT 1");
    if ($hRes && ($hRow = mysqli_fetch_assoc($hRes))) {
      if (intval($hRow['specialization_subject_id']) > 0) {
        $subId = intval($hRow['specialization_subject_id']);
      }
      if (intval($hRow['department_id']) > 0) {
        $deptId = intval($hRow['department_id']);
      }
      if ($academicYearId === null && intval($hRow['academic_year_id']) > 0) {
        $ayId = intval($hRow['academic_year_id']);
      }
    }

    if ($subId <= 0 || $semId <= 0 || $ayId <= 0) {
      return null;
    }

    $resolved = $this->getResolvedMentorForSubject($subId, $semId, $ayId, $deptId);
    if ($resolved && empty($resolved['ambiguous'])) {
      $resolved['subject_id'] = $subId;
      $subNameRes = mysqli_query($this->conn, "SELECT subject_name FROM st_specialization_subject_master WHERE subject_id = $subId LIMIT 1");
      $resolved['subject_name'] = ($subNameRes && ($snRow = mysqli_fetch_assoc($subNameRes))) ? $snRow['subject_name'] : '';
      return $resolved;
    }

    return null;
  }

  public function assignSubjectMentor($subjectId, $mentorId, $changedByUserId = 1, $semesterId = 0, $academicYearId = 0, $departmentId = null, $isActive = 1)
  {
    if (!($this->conn instanceof mysqli) || intval($subjectId) <= 0 || intval($mentorId) <= 0) {
      return false;
    }
    $subjectId = intval($subjectId);
    $mentorId = intval($mentorId);
    $semesterId = intval($semesterId);
    $academicYearId = intval($academicYearId);
    $isActive = intval($isActive) ? 1 : 0;

    if ($departmentId === null || intval($departmentId) <= 0) {
      // Lookup subject department
      $subDepRes = mysqli_query($this->conn, "SELECT department_id FROM st_specialization_subject_master WHERE subject_id = $subjectId LIMIT 1");
      if ($subDepRes && ($sdRow = mysqli_fetch_assoc($subDepRes))) {
        $departmentId = intval($sdRow['department_id']);
      }
      if (!$departmentId) {
        $departmentId = 0;
      }
    }
    $deptId = intval($departmentId);

    // Get previous mentor details for audit
    $prevMentor = $this->getResolvedMentorForSubject($subjectId, $semesterId, $academicYearId, $deptId);
    $prevMentorName = ($prevMentor && empty($prevMentor['ambiguous'])) ? $prevMentor['mentor_name'] : 'None';

    // Get subject name
    $subName = 'Unknown Subject';
    $subRes = mysqli_query($this->conn, "SELECT subject_name FROM st_specialization_subject_master WHERE subject_id = $subjectId LIMIT 1");
    if ($subRes && ($sRow = mysqli_fetch_assoc($subRes))) {
      $subName = $sRow['subject_name'];
    }

    // Get new mentor name
    $newMentorName = 'Unknown Mentor';
    $mRes = mysqli_query($this->conn, "SELECT COALESCE(NULLIF(TRIM(user_name), ''), email_id) AS mentor_name FROM st_user_master WHERE user_id = $mentorId LIMIT 1");
    if ($mRes && ($mRow = mysqli_fetch_assoc($mRes))) {
      $newMentorName = $mRow['mentor_name'];
    }

    // Upsert mapping in st_mentor_subject_mapping
    $sql = "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id, department_id, academic_year_id, semester_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE department_id = VALUES(department_id), is_active = VALUES(is_active), updated_at = CURRENT_TIMESTAMP";
    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      return false;
    }
    mysqli_stmt_bind_param($stmt, "iiiiii", $mentorId, $subjectId, $deptId, $academicYearId, $semesterId, $isActive);
    $res = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($res) {
      // Sync active history table if present
      $whereSem = ($semesterId > 0) ? " AND h.semester_id = $semesterId" : "";
      $whereAY = ($academicYearId > 0) ? " AND h.academic_year_id = $academicYearId" : "";
      mysqli_query($this->conn, "
        UPDATE st_student_semester_history h
        JOIN st_student_master s ON s.student_id = h.student_id
        SET h.mentor_id = $mentorId
        WHERE COALESCE(NULLIF(h.specialization_subject_id, 0), s.specialization_subject_id) = $subjectId
          AND h.status = 'Active'
          $whereSem
          $whereAY
      ");

      // Write audit log
      if (method_exists($this, 'writeAuditLog')) {
        $this->writeAuditLog(
          $changedByUserId,
          'MENTOR_ASSIGNMENT_CHANGED',
          'st_mentor_subject_mapping',
          $subjectId,
          "Subject '{$subName}' (Sem: {$semesterId}, AY: {$academicYearId}) mentor set to '{$newMentorName}' (Active: {$isActive})"
        );
      }
      return true;
    }
    return false;
  }

  /**
   * Complete Academic History retrieval for a student
   */
  public function getStudentAcademicHistory($studentId)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0) {
      return [];
    }
    $studentId = intval($studentId);

    $sql = "SELECT h.history_id,
                   h.student_id,
                   h.academic_year_id,
                   h.class_id,
                   h.semester_id,
                   h.division_id,
                   h.roll_no,
                   h.department_id,
                   h.specialization_id,
                   h.specialization_subject_id,
                   h.minor_course_id,
                   h.minor_subject_id,
                   h.cgpa,
                   h.progress_percent,
                   h.status AS history_status,
                   h.finalized_at,
                   h.created_at AS enrolled_at,
                   COALESCE(sem.semester_name, CONCAT('Semester ', h.semester_id)) AS semester_name,
                   COALESCE(ay.session_name, 'N/A') AS academic_year_name,
                   COALESCE(c.class_name, 'N/A') AS class_name,
                   COALESCE(sec.sections, 'N/A') AS division_name,
                   COALESCE(d.department_name, 'N/A') AS department_name,
                   COALESCE(sp.specialization_name, 'N/A') AS specialization_name,
                   COALESCE(sub.subject_name, 'N/A') AS subject_name,
                   COALESCE(mc.course_name, 'N/A') AS minor_course_name,
                   COALESCE(ms.subject_name, 'N/A') AS minor_subject_name
            FROM st_student_semester_history h
            LEFT JOIN st_semester_master sem ON sem.semester_id = h.semester_id
            LEFT JOIN st_session_master ay ON ay.session_id = h.academic_year_id
            LEFT JOIN st_class_master c ON c.class_id = h.class_id
            LEFT JOIN st_section_master sec ON sec.id = h.division_id
            LEFT JOIN st_department_master d ON d.department_id = COALESCE(NULLIF(h.department_id, 0), (SELECT s.department_id FROM st_student_master s WHERE s.student_id = h.student_id))
            LEFT JOIN st_specialization_master sp ON sp.specialization_id = h.specialization_id
            LEFT JOIN st_specialization_subject_master sub ON sub.subject_id = h.specialization_subject_id
            LEFT JOIN st_minorcourse mc ON mc.course_id = h.minor_course_id
            LEFT JOIN st_minorsubject ms ON ms.subject_id = h.minor_subject_id
            WHERE h.student_id = ?
            ORDER BY h.semester_id ASC, h.academic_year_id ASC, h.history_id ASC";

    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      return [];
    }
    mysqli_stmt_bind_param($stmt, "i", $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $history = [];

    if ($result) {
      while ($row = mysqli_fetch_assoc($result)) {
        $subId = intval($row['specialization_subject_id']);
        $semId = intval($row['semester_id']);
        $ayId = intval($row['academic_year_id']);

        // Resolve mentor for this specific academic period
        $mentor = ($subId > 0) ? $this->getResolvedMentorForSubject($subId, $semId, $ayId) : null;
        $row['mentor_name'] = $mentor ? $mentor['mentor_name'] : 'Not Assigned';
        $row['mentor_email'] = $mentor ? $mentor['email_id'] : '';
        $row['mentor_id'] = $mentor ? intval($mentor['mentor_id']) : 0;

        $history[] = $row;
      }
    }
    mysqli_stmt_close($stmt);
    return $history;
  }

  /**
   * Non-destructive Semester Progression / Student Promotion
   */
  public function promoteStudentSemester(
    $studentId,
    $newSemesterId,
    $newAcademicYearId = null,
    $newClassId = null,
    $newDivisionId = null,
    $newRollNo = null,
    $newDepartmentId = null,
    $newSpecializationId = null,
    $newSubjectId = null,
    $newCgpa = null,
    $promotedByUserId = 1
  ) {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0 || intval($newSemesterId) <= 0) {
      return false;
    }
    $studentId = intval($studentId);
    $newSemesterId = intval($newSemesterId);

    // Fetch existing student details
    $sRes = mysqli_query($this->conn, "SELECT student_id, fname, registration_no, department_id, class_id, division_id, roll_no, specialization_id, specialization_subject_id, cgpa, current_semester_id, academic_year_id FROM st_student_master WHERE student_id = $studentId LIMIT 1");
    if (!$sRes || mysqli_num_rows($sRes) === 0) {
      return false;
    }
    $sRow = mysqli_fetch_assoc($sRes);
    $oldSemesterId = intval($sRow['current_semester_id'] ?? 0);
    $ayId = ($newAcademicYearId !== null && intval($newAcademicYearId) > 0) ? intval($newAcademicYearId) : intval($sRow['academic_year_id'] ?? 2);
    $classId = ($newClassId !== null && intval($newClassId) > 0) ? intval($newClassId) : intval($sRow['class_id'] ?? 1);
    $deptId = ($newDepartmentId !== null && intval($newDepartmentId) > 0) ? intval($newDepartmentId) : intval($sRow['department_id'] ?? 1);
    $divId = ($newDivisionId !== null && intval($newDivisionId) > 0) ? intval($newDivisionId) : intval($sRow['division_id'] ?? 1);
    $rollNo = ($newRollNo !== null && trim((string)$newRollNo) !== '') ? trim((string)$newRollNo) : ($sRow['roll_no'] ?? '');
    $specId = ($newSpecializationId !== null && intval($newSpecializationId) > 0) ? intval($newSpecializationId) : intval($sRow['specialization_id'] ?? 0);
    $subId = ($newSubjectId !== null && intval($newSubjectId) > 0) ? intval($newSubjectId) : intval($sRow['specialization_subject_id'] ?? 0);
    $cgpaVal = ($newCgpa !== null && floatval($newCgpa) > 0) ? floatval($newCgpa) : floatval($sRow['cgpa'] ?? 0);

    mysqli_begin_transaction($this->conn);
    try {
      // 1. Mark previous active semesters as Completed
      mysqli_query($this->conn, "
        UPDATE st_student_semester_history
        SET status = 'Completed', finalized_at = COALESCE(finalized_at, CURRENT_TIMESTAMP)
        WHERE student_id = $studentId AND semester_id < $newSemesterId AND status = 'Active'
      ");

      // 2. Resolve mentor for new subject in new semester
      $newMentorId = null;
      if ($subId > 0) {
        $mentorInfo = $this->getResolvedMentorForSubject($subId, $newSemesterId, $ayId);
        if ($mentorInfo && intval($mentorInfo['mentor_id']) > 0) {
          $newMentorId = intval($mentorInfo['mentor_id']);
        }
      }

      // 3. Upsert record for new semester in st_student_semester_history
      $histCheck = mysqli_query($this->conn, "
        SELECT history_id FROM st_student_semester_history
        WHERE student_id = $studentId AND semester_id = $newSemesterId
        LIMIT 1
      ");

      if ($histCheck && ($hRow = mysqli_fetch_assoc($histCheck))) {
        $hId = intval($hRow['history_id']);
        $updHistSql = "UPDATE st_student_semester_history
                       SET academic_year_id = ?, class_id = ?, specialization_id = ?, specialization_subject_id = ?, division_id = ?, roll_no = ?, cgpa = ?, department_id = ?, mentor_id = ?, status = 'Active', updated_at = CURRENT_TIMESTAMP
                       WHERE history_id = ?";
        $stmtH = mysqli_prepare($this->conn, $updHistSql);
        if ($stmtH) {
          mysqli_stmt_bind_param($stmtH, "iiiiisdiii", $ayId, $classId, $specId, $subId, $divId, $rollNo, $cgpaVal, $deptId, $newMentorId, $hId);
          mysqli_stmt_execute($stmtH);
          mysqli_stmt_close($stmtH);
        }
      } else {
        $insHistSql = "INSERT INTO st_student_semester_history
                       (student_id, academic_year_id, class_id, semester_id, department_id, division_id, roll_no, specialization_id, specialization_subject_id, cgpa, mentor_id, status, progress_percent)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 0.00)";
        $stmtH = mysqli_prepare($this->conn, $insHistSql);
        if ($stmtH) {
          mysqli_stmt_bind_param($stmtH, "iiiiiisiidi", $studentId, $ayId, $classId, $newSemesterId, $deptId, $divId, $rollNo, $specId, $subId, $cgpaVal, $newMentorId);
          mysqli_stmt_execute($stmtH);
          mysqli_stmt_close($stmtH);
        }
      }

      // 4. Update current active state on st_student_master
      $updStudentSql = "UPDATE st_student_master
                        SET current_semester_id = ?, academic_year_id = ?, class_id = ?, specialization_id = ?, specialization_subject_id = ?, division_id = ?, department_id = ?, roll_no = ?, cgpa = ?
                        WHERE student_id = ?";
      $stmtS = mysqli_prepare($this->conn, $updStudentSql);
      if ($stmtS) {
        mysqli_stmt_bind_param($stmtS, "iiiiiiisdi", $newSemesterId, $ayId, $classId, $specId, $subId, $divId, $deptId, $rollNo, $cgpaVal, $studentId);
        mysqli_stmt_execute($stmtS);
        mysqli_stmt_close($stmtS);
      }

      // 5. Audit Log
      $this->writeAuditLog(
        $promotedByUserId,
        'STUDENT_PROMOTED_SEMESTER',
        'st_student_master',
        $studentId,
        "Student '{$sRow['fname']}' ({$sRow['registration_no']}) promoted from Semester {$oldSemesterId} to Semester {$newSemesterId} (AY: {$ayId})"
      );

      mysqli_commit($this->conn);
      return true;
    } catch (Throwable $e) {
      mysqli_rollback($this->conn);
      return false;
    }
  }

}
}
?>
