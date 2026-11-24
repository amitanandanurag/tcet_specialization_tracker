<?php
require_once __DIR__ . '/enrollment_service.php';

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
        // Enforce tcet_st in production; allowlist tcet_st_test and tcet_st_disposable_audit_test for isolated test execution via server env or session
        $allowedDbs = array('tcet_st', 'tcet_st_test', 'tcet_st_disposable_audit_test');
        $envDb = getenv('TCET_DB_NAME');
        $envDb = ($envDb !== false) ? trim((string)$envDb) : '';
        $sessionDb = isset($_SESSION['TCET_DB_NAME']) ? trim((string)$_SESSION['TCET_DB_NAME']) : '';

        if ($dbName !== null && trim((string)$dbName) !== '') {
            $candidate = trim((string)$dbName);
            $this->database = in_array($candidate, $allowedDbs, true) ? $candidate : "tcet_st";
        } elseif ($envDb !== '' && in_array($envDb, $allowedDbs, true)) {
            $this->database = $envDb;
        } elseif ($sessionDb !== '' && in_array($sessionDb, $allowedDbs, true)) {
            $this->database = $sessionDb;
        } else {
            $this->database = "tcet_st";
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

        if ($result === true) {
            return true;
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
        if (!$result) {
            return 0;
        }
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

    public static function isHttpsConnection()
    {
        // 1. Direct Web Server HTTPS
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        // 2. Reverse-Proxy HTTPS (Only if originating from a designated trusted proxy)
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $trustedProxies = getenv('TCET_TRUSTED_PROXIES') ?: ($_SERVER['TCET_TRUSTED_PROXIES'] ?? '');
            if (!empty($trustedProxies)) {
                $allowedList = array_map('trim', explode(',', (string)$trustedProxies));
                if (in_array($remoteIp, $allowedList, true) || in_array('*', $allowedList, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function startSecureSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $params = session_get_cookie_params();
                $isSecure = self::isHttpsConnection();
                @session_set_cookie_params([
                    'lifetime' => $params['lifetime'],
                    'path' => $params['path'] ?: '/',
                    'domain' => $params['domain'] ?: '',
                    'secure' => $isSecure,
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
        return password_hash((string)$plainPassword, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($plainPassword, $storedHash)
    {
        if (empty($plainPassword) || empty($storedHash)) {
            return false;
        }
        // 1. Primary check: Verify bcrypt / argon2 hash
        if (password_verify($plainPassword, $storedHash)) {
            return true;
        }
        // 2. Legacy fallback: Constant-time comparison for plaintext accounts pending migration
        if (hash_equals((string)$storedHash, (string)$plainPassword)) {
            return true;
        }
        // 3. Legacy fallback: MD5 comparison for historical accounts
        if (hash_equals((string)$storedHash, md5($plainPassword))) {
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
                   COALESCE(sub.subject_code, '') AS subject_code,
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

  /**
   * Resolves the authoritative RBAC scope for a user.
   */
  public function getUserScope($userId = null, $roleId = null)
  {
    $uid = ($userId !== null) ? intval($userId) : intval($_SESSION['user_id'] ?? 0);
    $rid = ($roleId !== null) ? intval($roleId) : intval($_SESSION['role_id'] ?? $_SESSION['user_type'] ?? 0);

    $deptId = 0;
    $studentId = 0;
    $username = '';

    if ($uid > 0 && ($this->conn instanceof mysqli)) {
      $uRes = mysqli_query($this->conn, "SELECT user_id, user_name, role_id, department_id, student_id FROM st_user_master WHERE user_id = $uid LIMIT 1");
      if ($uRes && ($uRow = mysqli_fetch_assoc($uRes))) {
        if ($roleId === null) {
          $rid = intval($uRow['role_id'] ?? $rid);
        }
        $deptId = intval($uRow['department_id'] ?? 0);
        $studentId = intval($uRow['student_id'] ?? 0);
        $username = $uRow['user_name'] ?? '';
      }
    }

    return array(
      'user_id' => $uid,
      'role_id' => $rid,
      'username' => $username,
      'department_id' => $deptId,
      'student_id' => $studentId,
      'is_super_admin' => ($rid === 1),
      'is_admin' => ($rid === 1 || $rid === 2),
      'is_operational_admin' => ($rid === 2),
      'is_hod' => ($rid === 3),
      'is_mentor' => ($rid === 4),
      'is_student' => ($rid === 5),
      'can_manage_system_config' => ($rid === 1),
      'can_access_audit_log' => ($rid === 1),
      'can_access_menu_allocation' => ($rid === 1),
      'can_access_master_data' => ($rid === 1)
    );
  }

  /**
   * Enforces server-side record-level access control for student profiles.
   */
  public function canAccessStudent($studentId, $userId = null, $roleId = null)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0) {
      return false;
    }
    $studentId = intval($studentId);
    $scope = $this->getUserScope($userId, $roleId);

    // Super Admin and Admin have institution-wide operational access
    if ($scope['is_super_admin'] || $scope['is_operational_admin']) {
      return true;
    }

    // Student can ONLY access their own profile
    if ($scope['is_student']) {
      return ($scope['student_id'] > 0 && $scope['student_id'] === $studentId);
    }

    // Look up student department and active current semester
    $sRes = mysqli_query($this->conn, "SELECT student_id, department_id, current_semester_id, academic_year_id, specialization_subject_id FROM st_student_master WHERE student_id = $studentId LIMIT 1");
    if (!$sRes || !($sRow = mysqli_fetch_assoc($sRes))) {
      return false;
    }

    // HOD can ONLY access students in their authorized department
    if ($scope['is_hod']) {
      return ($scope['department_id'] > 0 && intval($sRow['department_id']) === $scope['department_id']);
    }

    // Mentor can ONLY access students with an active assignment to this mentor
    if ($scope['is_mentor']) {
      $uid = $scope['user_id'];
      $semId = intval($sRow['current_semester_id'] ?? 0);

      $mCheck = mysqli_query($this->conn, "
        SELECT 1 
        FROM st_mentor_student_mapping msm
        JOIN st_mentor_subject_mapping alloc ON alloc.mentor_id = msm.mentor_id 
          AND alloc.semester_id = msm.semester_id 
          AND alloc.academic_year_id = msm.academic_year_id
          AND alloc.is_active = 1
        WHERE msm.student_id = $studentId 
          AND msm.mentor_id = $uid
          AND msm.semester_id = $semId
        LIMIT 1
      ");
      return ($mCheck && mysqli_num_rows($mCheck) > 0);
    }

    return false;
  }

  /**
   * Enforces server-side department scoping.
   */
  public function canAccessDepartment($departmentId, $userId = null, $roleId = null)
  {
    $deptId = intval($departmentId);
    $scope = $this->getUserScope($userId, $roleId);

    if ($scope['is_super_admin'] || $scope['is_operational_admin']) {
      return true;
    }
    if ($scope['is_hod']) {
      return ($scope['department_id'] > 0 && $scope['department_id'] === $deptId);
    }
    return false;
  }

  /**
   * Generates authoritative, role-scoped institutional metrics.
   */
  public function getAuthoritativeMetrics($userId = null, $roleId = null)
  {
    if (!($this->conn instanceof mysqli)) {
      return array();
    }
    $scope = $this->getUserScope($userId, $roleId);

    $whereDept = "";
    $whereUserDept = "";
    if ($scope['is_hod'] && $scope['department_id'] > 0) {
      $d = $scope['department_id'];
      $whereDept = " WHERE s.department_id = $d ";
      $whereUserDept = " WHERE u.department_id = $d ";
    } elseif ($scope['is_mentor']) {
      $uid = $scope['user_id'];
      $whereDept = " INNER JOIN st_mentor_student_mapping msm ON msm.student_id = s.student_id AND msm.mentor_id = $uid WHERE 1=1 ";
      $whereUserDept = " WHERE u.user_id = $uid ";
    }

    // 1. Total Registered Students (and breakdown of active vs pending)
    $sQuery = "SELECT 
                 COUNT(DISTINCT s.student_id) AS total_students,
                 COUNT(DISTINCT CASE WHEN s.status = 1 THEN s.student_id END) AS active_students,
                 COUNT(DISTINCT CASE WHEN s.status = 0 THEN s.student_id END) AS pending_students
               FROM st_student_master s $whereDept";
    $sRes = mysqli_query($this->conn, $sQuery);
    $sRow = $sRes ? mysqli_fetch_assoc($sRes) : array();
    $totalStudents = intval($sRow['total_students'] ?? 0);
    $activeStudents = intval($sRow['active_students'] ?? 0);
    $pendingStudents = intval($sRow['pending_students'] ?? 0);

    // 2. Total Users in st_user_master (Administrative & Academic Staff: Roles 1, 2, 3, 4 matching User Directory)
    $userRoleFilter = " u.role_id IN (1, 2, 3, 4) ";
    $uWhere = empty($whereUserDept) ? " WHERE $userRoleFilter " : " $whereUserDept AND $userRoleFilter ";
    $uQuery = "SELECT COUNT(DISTINCT u.user_id) AS total_users FROM st_user_master u $uWhere";
    $uRes = mysqli_query($this->conn, $uQuery);
    $uRow = $uRes ? mysqli_fetch_assoc($uRes) : array();
    $totalUsers = intval($uRow['total_users'] ?? 0);

    // 3. Total Mentors (role_id = 4)
    $mWhere = ($scope['is_hod'] && $scope['department_id'] > 0)
      ? " WHERE u.role_id = 4 AND u.department_id = {$scope['department_id']} "
      : " WHERE u.role_id = 4 ";
    $mQuery = "SELECT COUNT(DISTINCT u.user_id) AS total_mentors FROM st_user_master u $mWhere";
    $mRes = mysqli_query($this->conn, $mQuery);
    $mRow = $mRes ? mysqli_fetch_assoc($mRes) : array();
    $totalMentors = intval($mRow['total_mentors'] ?? 0);

    // 4. Total Branches / Departments
    $bWhere = ($scope['is_hod'] && $scope['department_id'] > 0)
      ? " WHERE department_id = {$scope['department_id']} "
      : " WHERE department_id != 15 "; // Exclude non-academic 'All Branch' if present
    $bQuery = "SELECT COUNT(DISTINCT department_id) AS total_branches FROM st_department_master $bWhere";
    $bRes = mysqli_query($this->conn, $bQuery);
    $bRow = $bRes ? mysqli_fetch_assoc($bRes) : array();
    $totalBranches = intval($bRow['total_branches'] ?? 0);

    // 5. Total HODs (role_id = 3)
    $hWhere = ($scope['is_hod'] && $scope['department_id'] > 0)
      ? " WHERE u.role_id = 3 AND u.department_id = {$scope['department_id']} "
      : " WHERE u.role_id = 3 ";
    $hQuery = "SELECT COUNT(DISTINCT u.user_id) AS total_hods FROM st_user_master u $hWhere";
    $hRes = mysqli_query($this->conn, $hQuery);
    $hRow = $hRes ? mysqli_fetch_assoc($hRes) : array();
    $totalHods = intval($hRow['total_hods'] ?? 0);

    return array(
      'total_students' => $totalStudents,
      'total_registered_students' => $totalStudents,
      'active_students' => $activeStudents,
      'total_active_students' => $activeStudents,
      'pending_students' => $pendingStudents,
      'total_users' => $totalUsers,
      'total_mentors' => $totalMentors,
      'total_branches' => $totalBranches,
      'total_hods' => $totalHods,
      'scope' => $scope
    );
  }

  // =========================================================================
  // HISTORICAL STUDENT ARCHIVE & APPROVAL HELPERS (SUPER ADMIN ONLY)
  // =========================================================================

  /**
   * Authoritatively verifies whether a user account is an explicitly authorized Department HOD.
   * A generic Coordinator (role_id = 3 registered in st_coordinator or unverified) fails closed.
   */
  public function verifyHodAuthorization($userId, $departmentId)
  {
    $uid = intval($userId);
    $deptId = intval($departmentId);

    if (!($this->conn instanceof mysqli) || $uid <= 0 || $deptId <= 0) {
      return array(
        'is_authorized' => false,
        'is_hod' => false,
        'is_coordinator' => false,
        'reason' => 'Invalid user ID or department ID.'
      );
    }

    // 1. Verify user exists with role_id = 3 in st_user_master
    $uRes = mysqli_query($this->conn, "SELECT user_id, user_name, role_id, department_id FROM st_user_master WHERE user_id = {$uid} LIMIT 1");
    if (!$uRes || !($uRow = mysqli_fetch_assoc($uRes))) {
      return array(
        'is_authorized' => false,
        'is_hod' => false,
        'is_coordinator' => false,
        'reason' => 'User not found in user master.'
      );
    }

    if (intval($uRow['role_id']) !== 3) {
      return array(
        'is_authorized' => false,
        'is_hod' => false,
        'is_coordinator' => false,
        'reason' => 'User role is not Coordinator / HOD (role_id 3).'
      );
    }

    if (intval($uRow['department_id']) !== $deptId) {
      return array(
        'is_authorized' => false,
        'is_hod' => false,
        'is_coordinator' => false,
        'reason' => 'User department mapping does not match requested department.'
      );
    }

    // 2. Check if registered as a Coordinator in st_coordinator
    $isCoordinator = false;
    $cRes = mysqli_query($this->conn, "SELECT 1 FROM st_coordinator c INNER JOIN st_login l ON l.login_id = c.login_id WHERE l.user_id = {$uid} LIMIT 1");
    if ($cRes && mysqli_num_rows($cRes) > 0) {
      $isCoordinator = true;
    }

    // 3. Check for explicit institutional HOD appointment
    // Check A: Table st_department_hod (institutional HOD appointment table)
    $tCheck = mysqli_query($this->conn, "SHOW TABLES LIKE 'st_department_hod'");
    if ($tCheck && mysqli_num_rows($tCheck) > 0) {
      $hRes = mysqli_query($this->conn, "SELECT 1 FROM st_department_hod WHERE department_id = {$deptId} AND user_id = {$uid} AND is_active = 1 LIMIT 1");
      if ($hRes && mysqli_num_rows($hRes) > 0) {
        return array(
          'is_authorized' => true,
          'is_hod' => true,
          'is_coordinator' => $isCoordinator,
          'reason' => 'Verified via st_department_hod institutional appointment registry.'
        );
      }
    }

    // Check B: Explicit user-level menu/approval allocation in st_menu_allocation_master
    $allocRes = mysqli_query($this->conn, "
      SELECT 1 FROM st_menu_allocation_master ma
      INNER JOIN st_sub_menu_master sm ON sm.sub_menu_id = ma.sub_menu_id
      WHERE ma.user_id = {$uid} AND sm.sub_menu_route = 'hod_historical_approvals.php'
      LIMIT 1
    ");
    if ($allocRes && mysqli_num_rows($allocRes) > 0) {
      return array(
        'is_authorized' => true,
        'is_hod' => true,
        'is_coordinator' => $isCoordinator,
        'reason' => 'Verified via explicit user-level menu approval allocation.'
      );
    }

    // Check C: If the user is only a Coordinator or an unverified role_id 3 user, fail closed
    return array(
      'is_authorized' => false,
      'is_hod' => false,
      'is_coordinator' => $isCoordinator,
      'reason' => $isCoordinator
        ? 'Account is designated as a Coordinator. Coordinator accounts lack Head of Department approval authority.'
        : 'Account is an unverified Role 3 user without explicit institutional HOD mapping. Approval access denied (fail-closed).'
    );
  }

  /**
   * Resolves verified session and role permissions for Historical Student Archive.
   * - Super Admin (role_id = 1): Unrestricted institution-wide access (authorized_dept_id = null).
   * - Verified HOD (role_id = 3): Department-isolated access (authorized_dept_id = mapped dept_id).
   * - Coordinator (role_id = 3 without HOD authorization): Fails closed on approval operations.
   * If an HOD does not have a valid mapped department, access is denied (fail-closed).
   */
  public function requireHistoricalArchiveAccess(&$outScope = null, $allowHod = true, $requireApprovalPrivilege = false)
  {
    self::startSecureSession();
    $sessionLoginId = intval($_SESSION['user_login_id'] ?? $_SESSION['user_session'] ?? 0);
    $sessionUserId = intval($_SESSION['user_id'] ?? 0);

    if (!($this->conn instanceof mysqli) || ($sessionLoginId <= 0 && $sessionUserId <= 0)) {
      $outScope = array('user_id' => 0, 'role_id' => 0, 'username' => '', 'department_id' => 0, 'department_name' => '', 'authorized_dept_id' => null, 'is_superadmin' => false, 'is_hod' => false, 'is_coordinator' => false, 'can_approve' => false);
      return false;
    }

    $sql = "SELECT l.login_id, l.username, u.user_id, u.role_id, u.user_name, u.department_id, d.department_name
            FROM st_login l
            INNER JOIN st_user_master u ON u.user_id = l.user_id
            LEFT JOIN st_department_master d ON d.department_id = u.department_id
            WHERE " . ($sessionLoginId > 0 ? "l.login_id = ?" : "u.user_id = ?") . "
            ORDER BY l.login_id DESC LIMIT 1";
    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      $outScope = array('user_id' => $sessionUserId, 'role_id' => 0, 'username' => '', 'department_id' => 0, 'department_name' => '', 'authorized_dept_id' => null, 'is_superadmin' => false, 'is_hod' => false, 'is_coordinator' => false, 'can_approve' => false);
      return false;
    }
    $bindId = ($sessionLoginId > 0) ? $sessionLoginId : $sessionUserId;
    mysqli_stmt_bind_param($stmt, 'i', $bindId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
      $outScope = array('user_id' => $sessionUserId, 'role_id' => 0, 'username' => '', 'department_id' => 0, 'department_name' => '', 'authorized_dept_id' => null, 'is_superadmin' => false, 'is_hod' => false, 'is_coordinator' => false, 'can_approve' => false);
      return false;
    }

    $uid = intval($row['user_id'] ?? 0);
    $rid = intval($row['role_id'] ?? 0);
    $deptId = intval($row['department_id'] ?? 0);
    $uname = trim((string)($row['user_name'] ?: ($row['username'] ?? '')));
    $deptName = trim((string)($row['department_name'] ?? ''));

    $outScope = array(
      'user_id' => $uid,
      'role_id' => $rid,
      'username' => (string)($row['username'] ?? ''),
      'user_name' => $uname,
      'department_id' => $deptId,
      'department_name' => $deptName,
      'is_superadmin' => ($uid > 0 && $rid === 1),
      'is_hod' => false,
      'is_coordinator' => false,
      'can_approve' => ($uid > 0 && $rid === 1),
      'authorized_dept_id' => null,
      'authorization_reason' => ''
    );

    // Super Admin: Unrestricted access across all departments
    if ($uid > 0 && $rid === 1) {
      $outScope['authorized_dept_id'] = null;
      $outScope['can_approve'] = true;
      return true;
    }

    // Role 3: Distinguish between verified HOD and Coordinator
    if ($uid > 0 && $rid === 3) {
      if ($deptId <= 0) {
        $outScope['authorized_dept_id'] = null;
        $outScope['authorization_reason'] = 'User has no assigned department mapping.';
        return false;
      }

      $auth = $this->verifyHodAuthorization($uid, $deptId);
      $outScope['is_hod'] = $auth['is_hod'];
      $outScope['is_coordinator'] = $auth['is_coordinator'];
      $outScope['can_approve'] = $auth['is_authorized'];
      $outScope['authorization_reason'] = $auth['reason'];
      $outScope['authorized_dept_id'] = $deptId;

      if ($requireApprovalPrivilege) {
        return $auth['is_authorized'];
      }

      if ($allowHod) {
        return $auth['is_authorized'];
      }

      return false;
    }

    return false;
  }

  /**
   * Resolves a verified Super Admin (role_id = 1) session against st_login + st_user_master.
   */
  public function requireHistoricalArchiveSuperAdmin(&$outScope = null)
  {
    return $this->requireHistoricalArchiveAccess($outScope, false);
  }

  /**
   * Returns aggregated KPI counts for the Historical Student Archive dashboard.
   * If $departmentId is provided, aggregates strictly for that department.
   */
  public function getHistoricalArchiveStats($departmentId = null)
  {
    $empty = array(
      'total_candidates' => 0,
      'eligible' => 0,
      'conditional' => 0,
      'conditional_academic' => 0,
      'returned_for_correction' => 0,
      'policy_notes' => 0,
      'ineligible' => 0,
      'unresolved' => 0,
      'pending_approval' => 0,
      'conditional_approval' => 0,
      'hold_approval' => 0,
      'approved' => 0,
      'rejected' => 0,
      'not_imported' => 0,
      'ready_for_import' => 0,
      'imported' => 0,
      'excluded' => 0,
      'conflicts_count' => 0,
      'excluded_duplicates_count' => 0
    );
    if (!($this->conn instanceof mysqli)) {
      return $empty;
    }

    $deptId = intval($departmentId);
    $whereClause = ($deptId > 0) ? " WHERE cur_department_id = " . $deptId : "";

    $sql = "SELECT
              COUNT(*) AS total_candidates,
              SUM(CASE WHEN eligibility_status = 'ELIGIBLE' THEN 1 ELSE 0 END) AS eligible,
              SUM(CASE WHEN eligibility_status = 'CONDITIONALLY_ELIGIBLE' THEN 1 ELSE 0 END) AS conditional,
              SUM(CASE WHEN eligibility_status = 'POLICY_NOTE' THEN 1 ELSE 0 END) AS policy_notes,
              SUM(CASE WHEN eligibility_status = 'INELIGIBLE' THEN 1 ELSE 0 END) AS ineligible,
              SUM(CASE WHEN eligibility_status = 'UNRESOLVED' THEN 1 ELSE 0 END) AS unresolved,
              SUM(CASE WHEN academic_approval_status = 'PENDING' THEN 1 ELSE 0 END) AS pending_approval,
              SUM(CASE WHEN academic_approval_status = 'CONDITIONAL' THEN 1 ELSE 0 END) AS conditional_approval,
              SUM(CASE WHEN academic_approval_status = 'CONDITIONAL' AND (approval_remarks IS NULL OR approval_remarks NOT LIKE '%[RETURNED_FOR_CORRECTION]%') THEN 1 ELSE 0 END) AS conditional_academic,
              SUM(CASE WHEN academic_approval_status = 'CONDITIONAL' AND approval_remarks LIKE '%[RETURNED_FOR_CORRECTION]%' THEN 1 ELSE 0 END) AS returned_for_correction,
              SUM(CASE WHEN academic_approval_status = 'HOLD' THEN 1 ELSE 0 END) AS hold_approval,
              SUM(CASE WHEN academic_approval_status = 'APPROVED' THEN 1 ELSE 0 END) AS approved,
              SUM(CASE WHEN academic_approval_status = 'REJECTED' THEN 1 ELSE 0 END) AS rejected,
              SUM(CASE WHEN import_status = 'NOT_IMPORTED' THEN 1 ELSE 0 END) AS not_imported,
              SUM(CASE WHEN import_status = 'READY_FOR_IMPORT' THEN 1 ELSE 0 END) AS ready_for_import,
              SUM(CASE WHEN import_status = 'IMPORTED' THEN 1 ELSE 0 END) AS imported,
              SUM(CASE WHEN import_status = 'EXCLUDED' THEN 1 ELSE 0 END) AS excluded
            FROM st_historical_student_candidates" . $whereClause;
    $res = mysqli_query($this->conn, $sql);
    if ($res && ($row = mysqli_fetch_assoc($res))) {
      foreach ($empty as $k => $v) {
        if (isset($row[$k])) {
          $empty[$k] = intval($row[$k]);
        }
      }
    }

    if ($deptId > 0) {
      $cRes = mysqli_query($this->conn, "SELECT COUNT(DISTINCT r.conflict_register_id) AS cnt FROM st_historical_conflict_register r INNER JOIN st_historical_student_candidates c ON FIND_IN_SET(r.conflict_id_ref, REPLACE(c.related_conflict_ids, ' ', '')) > 0 WHERE c.cur_department_id = {$deptId}");
      if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
        $empty['conflicts_count'] = intval($cRow['cnt'] ?? 0);
      }
      $dRes = mysqli_query($this->conn, "SELECT COUNT(*) AS cnt FROM st_historical_excluded_duplicates d INNER JOIN st_historical_student_candidates c ON c.candidate_id = d.linked_candidate_id WHERE c.cur_department_id = {$deptId}");
      if ($dRes && ($dRow = mysqli_fetch_assoc($dRes))) {
        $empty['excluded_duplicates_count'] = intval($dRow['cnt'] ?? 0);
      }
    } else {
      $cRes = mysqli_query($this->conn, "SELECT COUNT(*) AS cnt FROM st_historical_conflict_register");
      if ($cRes && ($cRow = mysqli_fetch_assoc($cRes))) {
        $empty['conflicts_count'] = intval($cRow['cnt'] ?? 0);
      }
      $dRes = mysqli_query($this->conn, "SELECT COUNT(*) AS cnt FROM st_historical_excluded_duplicates");
      if ($dRes && ($dRow = mysqli_fetch_assoc($dRes))) {
        $empty['excluded_duplicates_count'] = intval($dRow['cnt'] ?? 0);
      }
    }

    return $empty;
  }

  /**
   * Builds shared prepared-statement WHERE SQL & parameter bindings across all 12 archive filters + search.
   * If $enforcedDeptId is set, strictly enforces that department, overriding client filters.
   */
  public function buildHistoricalArchiveWhere(array $filters, $search, array &$params, &$types, $enforcedDeptId = null)
  {
    $clauses = array('1=1');
    $params = array();
    $types = '';

    $ay = intval($filters['academic_year_id'] ?? 0);
    if ($ay > 0) {
      $clauses[] = 'c.academic_year_id = ?';
      $params[] = $ay;
      $types .= 'i';
    }

    $sem = intval($filters['semester_id'] ?? 0);
    if ($sem > 0) {
      $clauses[] = 'c.semester_id = ?';
      $params[] = $sem;
      $types .= 'i';
    }

    $enforced = intval($enforcedDeptId);
    if ($enforced > 0) {
      $clauses[] = 'c.cur_department_id = ?';
      $params[] = $enforced;
      $types .= 'i';
    } else {
      $dept = intval($filters['department_id'] ?? 0);
      if ($dept > 0) {
        $clauses[] = 'c.cur_department_id = ?';
        $params[] = $dept;
        $types .= 'i';
      }
    }

    $cls = intval($filters['class_id'] ?? 0);
    if ($cls > 0) {
      $clauses[] = 'c.class_id = ?';
      $params[] = $cls;
      $types .= 'i';
    }

    $divFilter = trim((string)($filters['division'] ?? ''));
    if ($divFilter === '__MISSING__') {
      $clauses[] = 'c.cur_division_id IS NULL';
    } elseif ($divFilter !== '' && ctype_digit($divFilter)) {
      $clauses[] = 'c.cur_division_id = ?';
      $params[] = intval($divFilter);
      $types .= 'i';
    }

    $erpFilter = trim((string)($filters['erp_id'] ?? ''));
    if ($erpFilter === '__MISSING__') {
      $clauses[] = "(c.cur_erp_id IS NULL OR c.cur_erp_id = '')";
    } elseif ($erpFilter !== '') {
      $likeErp = '%' . $erpFilter . '%';
      $clauses[] = "(IFNULL(c.cur_erp_id, '') LIKE ? OR IFNULL(c.orig_erp_standardized, '') LIKE ? OR IFNULL(c.orig_erp_raw, '') LIKE ?)";
      $params[] = $likeErp;
      $params[] = $likeErp;
      $params[] = $likeErp;
      $types .= 'sss';
    }

    $nameFilter = trim((string)($filters['student_name'] ?? ''));
    if ($nameFilter !== '') {
      $likeName = '%' . $nameFilter . '%';
      $clauses[] = "(c.cur_student_name LIKE ? OR c.orig_student_name_canonical LIKE ? OR IFNULL(c.orig_student_name_excel, '') LIKE ? OR IFNULL(c.orig_student_name_notice, '') LIKE ?)";
      $params[] = $likeName;
      $params[] = $likeName;
      $params[] = $likeName;
      $params[] = $likeName;
      $types .= 'ssss';
    }

    $specFilter = trim((string)($filters['specialization'] ?? ''));
    if ($specFilter === '__UNRESOLVED__') {
      $clauses[] = '(c.cur_specialization_id IS NULL AND c.cur_minor_course_id IS NULL)';
    } elseif (strpos($specFilter, 'MINOR:') === 0) {
      $mcId = intval(substr($specFilter, 6));
      if ($mcId > 0) {
        $clauses[] = 'c.cur_minor_course_id = ?';
        $params[] = $mcId;
        $types .= 'i';
      }
    } elseif (strpos($specFilter, 'SPEC:') === 0) {
      $spId = intval(substr($specFilter, 5));
      if ($spId > 0) {
        $clauses[] = 'c.cur_specialization_id = ?';
        $params[] = $spId;
        $types .= 'i';
      }
    } elseif ($specFilter !== '' && ctype_digit($specFilter)) {
      $clauses[] = 'c.cur_specialization_id = ?';
      $params[] = intval($specFilter);
      $types .= 'i';
    }

    $courseFilter = trim((string)($filters['course'] ?? ''));
    if ($courseFilter === '__UNMAPPED__') {
      $clauses[] = "(c.cur_course_code IS NULL OR c.cur_course_code = '')";
    } elseif ($courseFilter !== '') {
      $likeCourse = '%' . $courseFilter . '%';
      $clauses[] = "(IFNULL(c.cur_course_code, '') LIKE ? OR IFNULL(c.cur_course_name, '') LIKE ? OR IFNULL(c.orig_mapped_course_code, '') LIKE ?)";
      $params[] = $likeCourse;
      $params[] = $likeCourse;
      $params[] = $likeCourse;
      $types .= 'sss';
    }

    $eligAllowed = array('ELIGIBLE', 'CONDITIONALLY_ELIGIBLE', 'POLICY_NOTE', 'INELIGIBLE', 'UNRESOLVED');
    $eligFilter = trim((string)($filters['eligibility_status'] ?? ''));
    if (in_array($eligFilter, $eligAllowed, true)) {
      $clauses[] = 'c.eligibility_status = ?';
      $params[] = $eligFilter;
      $types .= 's';
    }

    $appAllowed = array('PENDING', 'CONDITIONAL', 'HOLD', 'APPROVED', 'REJECTED');
    $appFilter = trim((string)($filters['academic_approval_status'] ?? ''));
    if ($appFilter === 'RETURNED_FOR_CORRECTION') {
      $clauses[] = "(c.academic_approval_status = 'CONDITIONAL' AND c.approval_remarks LIKE '%[RETURNED_FOR_CORRECTION]%')";
    } elseif ($appFilter === 'ACADEMIC_CONDITIONAL') {
      $clauses[] = "(c.academic_approval_status = 'CONDITIONAL' AND (c.approval_remarks IS NULL OR c.approval_remarks NOT LIKE '%[RETURNED_FOR_CORRECTION]%'))";
    } elseif (in_array($appFilter, $appAllowed, true)) {
      $clauses[] = 'c.academic_approval_status = ?';
      $params[] = $appFilter;
      $types .= 's';
    }

    $impAllowed = array('NOT_IMPORTED', 'READY_FOR_IMPORT', 'IMPORTED', 'EXCLUDED');
    $impFilter = trim((string)($filters['import_status'] ?? ''));
    if (in_array($impFilter, $impAllowed, true)) {
      $clauses[] = 'c.import_status = ?';
      $params[] = $impFilter;
      $types .= 's';
    }

    $tierAllowed = array('TIER_1_CLEAN_READY_FOR_SIGN_OFF', 'TIER_2_CONDITIONAL_POLICY_DECISION', 'TIER_3_HOLD_CONFLICT_OR_MISSING_ID');
    $tierFilter = trim((string)($filters['approval_tier'] ?? ''));
    if (in_array($tierFilter, $tierAllowed, true)) {
      $clauses[] = 'c.approval_tier = ?';
      $params[] = $tierFilter;
      $types .= 's';
    }

    $searchClean = trim((string)$search);
    if ($searchClean !== '') {
      $likeSearch = '%' . $searchClean . '%';
      $clauses[] = "(
        c.staging_id LIKE ?
        OR IFNULL(c.cur_erp_id, '') LIKE ?
        OR IFNULL(c.orig_erp_standardized, '') LIKE ?
        OR c.cur_student_name LIKE ?
        OR c.orig_student_name_canonical LIKE ?
        OR IFNULL(c.cur_department_code, '') LIKE ?
        OR IFNULL(c.cur_course_code, '') LIKE ?
        OR IFNULL(c.cur_course_name, '') LIKE ?
        OR IFNULL(c.orig_specialization_or_minor_name, '') LIKE ?
        OR IFNULL(c.blocking_reasons, '') LIKE ?
        OR IFNULL(c.related_conflict_ids, '') LIKE ?
      )";
      for ($i = 0; $i < 11; $i++) {
        $params[] = $likeSearch;
        $types .= 's';
      }
    }

    return implode(' AND ', $clauses);
  }

  /**
   * Executes a prepared SELECT query safely with optional reference-bound parameters.
   */
  public function runPreparedSelect($sql, $types = '', array $params = array())
  {
    if (!($this->conn instanceof mysqli)) {
      return array();
    }
    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      error_log('Prepared select failed: ' . mysqli_error($this->conn));
      return array();
    }
    if ($types !== '' && !empty($params)) {
      $bindArgs = array($types);
      foreach ($params as $idx => $_val) {
        $bindArgs[] = &$params[$idx];
      }
      call_user_func_array(array($stmt, 'bind_param'), $bindArgs);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = array();
    if ($res) {
      while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
      }
    }
    mysqli_stmt_close($stmt);
    return $rows;
  }

  /**
   * Returns conflict register entries (41 seeded entries) with optional search.
   */
  public function getHistoricalConflictRegister($search = '')
  {
    $search = trim((string)$search);
    $sql = "SELECT * FROM st_historical_conflict_register";
    $types = '';
    $params = array();
    if ($search !== '') {
      $like = '%' . $search . '%';
      $sql .= " WHERE resolution_id LIKE ? OR conflict_id_ref LIKE ? OR category LIKE ? OR conflict_title LIKE ? OR IFNULL(evidence_and_impact, '') LIKE ?";
      $types = 'sssss';
      $params = array($like, $like, $like, $like, $like);
    }
    $sql .= " ORDER BY conflict_register_id ASC";
    return $this->runPreparedSelect($sql, $types, $params);
  }

  /**
   * Returns separately stored verbatim Notice duplicates (5 rows) linked to canonical candidates.
   */
  public function getHistoricalExcludedDuplicates()
  {
    $sql = "SELECT d.*, c.staging_id AS linked_staging_id, c.cur_student_name AS linked_student_name,
                   c.academic_approval_status AS linked_approval_status
            FROM st_historical_excluded_duplicates d
            LEFT JOIN st_historical_student_candidates c ON c.candidate_id = d.linked_candidate_id
            ORDER BY d.excluded_dup_id ASC";
    return $this->runPreparedSelect($sql);
  }

  /**
   * Fetches full detail payload for one historical candidate, including conflicts, duplicates, and audit timeline.
   * If $enforcedDeptId is set, verifies department match and returns null if mismatched (strict isolation).
   */
  public function getHistoricalCandidateDetail($candidateId, $enforcedDeptId = null)
  {
    $cid = intval($candidateId);
    if ($cid <= 0) {
      return null;
    }

    $sql = "SELECT c.*,
                   b.import_batch_ref,
                   d.department_name AS cur_department_name,
                   dv.division_name AS cur_division_master_name,
                   sem.semester_name,
                   cl.class_name,
                   tr.specialization_name AS cur_track_name,
                   sp.specialization_name AS cur_specialization_name,
                   mc.course_name AS cur_minor_course_name,
                   ssub.subject_name AS cur_spec_subject_name,
                   ssub.subject_code AS cur_spec_subject_code,
                   msub.subject_name AS cur_minor_subject_name,
                   uapp.user_name AS approved_by_user_name
            FROM st_historical_student_candidates c
            INNER JOIN st_historical_import_batches b ON b.batch_id = c.batch_id
            LEFT JOIN st_department_master d ON d.department_id = c.cur_department_id
            LEFT JOIN st_division_master dv ON dv.division_id = c.cur_division_id
            LEFT JOIN st_semester_master sem ON sem.semester_id = c.semester_id
            LEFT JOIN st_class_master cl ON cl.class_id = c.class_id
            LEFT JOIN st_specialization_master tr ON tr.specialization_id = c.cur_track_id
            LEFT JOIN st_specialization_master sp ON sp.specialization_id = c.cur_specialization_id
            LEFT JOIN st_minorcourse mc ON mc.course_id = c.cur_minor_course_id
            LEFT JOIN st_specialization_subject_master ssub ON ssub.subject_id = c.cur_spec_subject_id
            LEFT JOIN st_minorsubject msub ON msub.subject_id = c.cur_minor_subject_id
            LEFT JOIN st_user_master uapp ON uapp.user_id = c.approved_by_user_id
            WHERE c.candidate_id = ?
            LIMIT 1";
    $rows = $this->runPreparedSelect($sql, 'i', array($cid));
    if (empty($rows)) {
      return null;
    }
    $candidate = $rows[0];

    // Enforce department isolation if an enforced department is passed
    $enforced = intval($enforcedDeptId);
    if ($enforced > 0 && intval($candidate['cur_department_id'] ?? 0) !== $enforced) {
      return null;
    }

    $conflicts = array();
    $relRaw = trim((string)($candidate['related_conflict_ids'] ?? ''));
    if ($relRaw !== '') {
      $confIds = array_values(array_unique(array_filter(array_map('trim', explode(',', $relRaw)))));
      if (!empty($confIds)) {
        $ph = implode(',', array_fill(0, count($confIds), '?'));
        $tStr = str_repeat('s', count($confIds));
        $conflicts = $this->runPreparedSelect(
          "SELECT * FROM st_historical_conflict_register WHERE conflict_id_ref IN ({$ph}) ORDER BY conflict_register_id ASC",
          $tStr,
          $confIds
        );
      }
    }

    $excludedDups = $this->runPreparedSelect(
      "SELECT * FROM st_historical_excluded_duplicates WHERE linked_candidate_id = ? ORDER BY excluded_dup_id ASC",
      'i',
      array($cid)
    );

    $auditTimeline = $this->runPreparedSelect(
      "SELECT * FROM st_historical_candidate_audit WHERE candidate_id = ? ORDER BY hist_audit_id DESC",
      'i',
      array($cid)
    );

    return array(
      'candidate' => $candidate,
      'conflicts' => $conflicts,
      'excluded_duplicates' => $excludedDups,
      'audit_timeline' => $auditTimeline
    );
  }

  /**
   * Performs an authorized Super Admin curated-field correction with full before/after audit logging.
   * Never touches orig_* or source_* columns or active student tables.
   */
  public function correctHistoricalCandidate($candidateId, array $updates, $reason, $userId, $username)
  {
    $cid = intval($candidateId);
    $uid = intval($userId);
    $reasonClean = trim((string)$reason);

    if (!($this->conn instanceof mysqli) || $cid <= 0 || $uid <= 0) {
      return array('ok' => false, 'message' => 'Invalid candidate or user context.');
    }
    if (strlen($reasonClean) < 5) {
      return array('ok' => false, 'message' => 'A mandatory correction reason (at least 5 characters) is required.');
    }

    // Explicitly block any attempt to pass immutable fields
    foreach (array_keys($updates) as $k) {
      if (strpos($k, 'orig_') === 0 || strpos($k, 'source_') === 0 || in_array($k, array('staging_id', 'batch_id', 'reconcile_id', 'student_candidate_key', 'candidate_id', 'promoted_student_id'), true)) {
        return array('ok' => false, 'message' => 'Immutable source fields cannot be modified.');
      }
    }

    $allowlist = array(
      'cur_erp_id', 'cur_student_name', 'cur_department_id', 'cur_division_id',
      'cur_division_label', 'cur_roll_no', 'cur_cgpa', 'cur_track_id',
      'cur_specialization_id', 'cur_minor_course_id', 'cur_spec_subject_id',
      'cur_minor_subject_id', 'cur_course_code', 'cur_course_name',
      'cur_mentor_name', 'eligibility_status'
    );

    $nullableIntFields = array('cur_department_id', 'cur_division_id', 'cur_track_id', 'cur_specialization_id', 'cur_minor_course_id', 'cur_spec_subject_id', 'cur_minor_subject_id');
    $nullableStrFields = array('cur_erp_id', 'cur_division_label', 'cur_roll_no', 'cur_course_code', 'cur_course_name', 'cur_mentor_name');
    $eligAllowed = array('ELIGIBLE', 'CONDITIONALLY_ELIGIBLE', 'POLICY_NOTE', 'INELIGIBLE', 'UNRESOLVED');

    mysqli_begin_transaction($this->conn);
    try {
      $currRows = $this->runPreparedSelect("SELECT * FROM st_historical_student_candidates WHERE candidate_id = ? LIMIT 1 FOR UPDATE", 'i', array($cid));
      if (empty($currRows)) {
        mysqli_rollback($this->conn);
        return array('ok' => false, 'message' => 'Historical candidate record not found.');
      }
      $current = $currRows[0];

      $normalized = array();
      foreach ($allowlist as $field) {
        if (!array_key_exists($field, $updates)) {
          continue;
        }
        $raw = trim((string)$updates[$field]);

        if (in_array($field, $nullableIntFields, true)) {
          if ($raw === '' || $raw === '0') {
            $normalized[$field] = null;
          } elseif (!ctype_digit($raw)) {
            throw new InvalidArgumentException("Invalid integer reference for {$field}.");
          } else {
            $normalized[$field] = intval($raw);
          }
        } elseif ($field === 'cur_cgpa') {
          if ($raw === '') {
            $normalized[$field] = null;
          } elseif (!is_numeric($raw) || floatval($raw) < 0 || floatval($raw) > 10) {
            throw new InvalidArgumentException('Curated CGPA must be a valid decimal between 0.00 and 10.00.');
          } else {
            $normalized[$field] = number_format((float)$raw, 2, '.', '');
          }
        } elseif ($field === 'cur_student_name') {
          if ($raw === '') {
            throw new InvalidArgumentException('Curated student name cannot be empty.');
          }
          $normalized[$field] = mb_substr($raw, 0, 200);
        } elseif ($field === 'eligibility_status') {
          if (!in_array($raw, $eligAllowed, true)) {
            throw new InvalidArgumentException('Invalid eligibility status selection.');
          }
          $normalized[$field] = $raw;
        } elseif (in_array($field, $nullableStrFields, true)) {
          if ($field === 'cur_erp_id' && $raw !== '' && !preg_match('/^[A-Za-z0-9_-]{3,40}$/', $raw)) {
            throw new InvalidArgumentException('Curated ERP ID contains invalid characters.');
          }
          $normalized[$field] = ($raw === '') ? null : mb_substr($raw, 0, 255);
        }
      }

      // Auto-sync cur_department_code if cur_department_id was changed
      if (array_key_exists('cur_department_id', $normalized)) {
        if ($normalized['cur_department_id'] === null) {
          $normalized['cur_department_code'] = null;
        } else {
          $dCheck = $this->runPreparedSelect("SELECT department_name FROM st_department_master WHERE department_id = ? LIMIT 1", 'i', array($normalized['cur_department_id']));
          if (empty($dCheck)) {
            throw new InvalidArgumentException('Selected department does not exist.');
          }
          $normalized['cur_department_code'] = $dCheck[0]['department_name'];
        }
      }

      // Validate division FK and sync division label if unset
      if (array_key_exists('cur_division_id', $normalized) && $normalized['cur_division_id'] !== null) {
        $dvCheck = $this->runPreparedSelect("SELECT division_name FROM st_division_master WHERE division_id = ? LIMIT 1", 'i', array($normalized['cur_division_id']));
        if (empty($dvCheck)) {
          throw new InvalidArgumentException('Selected division does not exist.');
        }
        if (!isset($normalized['cur_division_label']) || $normalized['cur_division_label'] === null) {
          $normalized['cur_division_label'] = $dvCheck[0]['division_name'];
        }
      }

      if (array_key_exists('cur_track_id', $normalized) && $normalized['cur_track_id'] !== null) {
        $tCheck = $this->runPreparedSelect("SELECT specialization_id FROM st_specialization_master WHERE specialization_id = ? LIMIT 1", 'i', array($normalized['cur_track_id']));
        if (empty($tCheck)) {
          throw new InvalidArgumentException('Selected track does not exist.');
        }
      }
      if (array_key_exists('cur_specialization_id', $normalized) && $normalized['cur_specialization_id'] !== null) {
        $sCheck = $this->runPreparedSelect("SELECT specialization_id FROM st_specialization_master WHERE specialization_id = ? LIMIT 1", 'i', array($normalized['cur_specialization_id']));
        if (empty($sCheck)) {
          throw new InvalidArgumentException('Selected specialization does not exist.');
        }
      }
      if (array_key_exists('cur_minor_course_id', $normalized) && $normalized['cur_minor_course_id'] !== null) {
        $mCheck = $this->runPreparedSelect("SELECT course_id FROM st_minorcourse WHERE course_id = ? LIMIT 1", 'i', array($normalized['cur_minor_course_id']));
        if (empty($mCheck)) {
          throw new InvalidArgumentException('Selected minor course does not exist.');
        }
      }

      // Determine only fields that actually changed
      $changes = array();
      foreach ($normalized as $col => $newVal) {
        $oldRaw = $current[$col] ?? null;
        $oldCmp = ($oldRaw === null) ? null : (string)$oldRaw;
        $newCmp = ($newVal === null) ? null : (string)$newVal;
        if ($col === 'cur_cgpa' && $oldCmp !== null && $newCmp !== null && abs((float)$oldCmp - (float)$newCmp) < 0.001) {
          continue;
        }
        if ($oldCmp !== $newCmp) {
          $changes[$col] = array('old' => $oldCmp, 'new' => $newCmp, 'val' => $newVal);
        }
      }

      if (empty($changes)) {
        mysqli_rollback($this->conn);
        return array('ok' => false, 'message' => 'No field values were changed.');
      }

      $setClauses = array();
      $uTypes = '';
      $uParams = array();
      foreach ($changes as $col => $meta) {
        $setClauses[] = "`{$col}` = ?";
        $uParams[] = $meta['val'];
        if (in_array($col, $nullableIntFields, true) && $meta['val'] !== null) {
          $uTypes .= 'i';
        } else {
          $uTypes .= 's';
        }
      }
      $uParams[] = $cid;
      $uTypes .= 'i';

      $updSql = "UPDATE `st_historical_student_candidates` SET " . implode(', ', $setClauses) . " WHERE candidate_id = ?";
      $stmtUpd = mysqli_prepare($this->conn, $updSql);
      if (!$stmtUpd) {
        throw new RuntimeException('Failed preparing candidate update statement.');
      }
      $bindUpd = array($uTypes);
      foreach ($uParams as $idx => $_v) {
        $bindUpd[] = &$uParams[$idx];
      }
      call_user_func_array(array($stmtUpd, 'bind_param'), $bindUpd);
      if (!mysqli_stmt_execute($stmtUpd)) {
        $err = mysqli_stmt_error($stmtUpd);
        mysqli_stmt_close($stmtUpd);
        throw new RuntimeException($err);
      }
      mysqli_stmt_close($stmtUpd);

      // Append field-level entries to immutable audit ledger
      $stmtAudit = mysqli_prepare(
        $this->conn,
        "INSERT INTO `st_historical_candidate_audit`
         (`candidate_id`, `action_type`, `field_name`, `old_value`, `new_value`, `remarks`, `performed_by_user_id`, `performed_by_username`)
         VALUES (?, 'FIELD_CORRECTION', ?, ?, ?, ?, ?, ?)"
      );
      if (!$stmtAudit) {
        throw new RuntimeException('Failed preparing candidate audit statement.');
      }
      $summaryParts = array();
      foreach ($changes as $col => $meta) {
        $oldDisp = ($meta['old'] === null) ? '(NULL)' : $meta['old'];
        $newDisp = ($meta['new'] === null) ? '(NULL)' : $meta['new'];
        mysqli_stmt_bind_param($stmtAudit, 'issssis', $cid, $col, $oldDisp, $newDisp, $reasonClean, $uid, $username);
        if (!mysqli_stmt_execute($stmtAudit)) {
          $err = mysqli_stmt_error($stmtAudit);
          mysqli_stmt_close($stmtAudit);
          throw new RuntimeException($err);
        }
        $summaryParts[] = "{$col}: {$oldDisp} -> {$newDisp}";
      }
      mysqli_stmt_close($stmtAudit);

      $this->writeAuditLog(
        $uid,
        'HISTORICAL_ARCHIVE_CORRECTION',
        'st_historical_student_candidates',
        $cid,
        "Staging {$current['staging_id']} updated (" . implode('; ', $summaryParts) . "). Reason: {$reasonClean}",
        $username
      );

      mysqli_commit($this->conn);
      return array(
        'ok' => true,
        'message' => 'Curated historical record updated and logged in audit history.',
        'changed_fields' => array_keys($changes)
      );
    } catch (Throwable $e) {
      @mysqli_rollback($this->conn);
      return array('ok' => false, 'message' => $e->getMessage());
    }
  }

  /**
   * Records an official Academic Approval Decision with strict supporting-evidence validation.
   * If $enforcedDeptId is provided, strictly validates that all candidate IDs belong to that department.
   * Blocks approval of candidates on Academic HOLD or INELIGIBLE status.
   * Never automatically promotes candidates into st_student_master or st_enrollment.
   */
  public function recordHistoricalApprovalDecision(array $candidateIds, array $payload, $userId, $username, $enforcedDeptId = null, $dryRun = false)
  {
    $uid = intval($userId);
    if (!($this->conn instanceof mysqli) || $uid <= 0) {
      return array('ok' => false, 'message' => 'Unauthorized user session.');
    }

    $cleanIds = array();
    foreach ($candidateIds as $idVal) {
      $idInt = intval($idVal);
      if ($idInt > 0) {
        $cleanIds[$idInt] = $idInt;
      }
    }
    $cleanIds = array_values($cleanIds);
    if (empty($cleanIds)) {
      return array('ok' => false, 'message' => 'Select at least one valid historical candidate.');
    }

    $approvalAllowed = array('PENDING', 'CONDITIONAL', 'HOLD', 'APPROVED', 'REJECTED');
    $rawStatus = trim((string)($payload['academic_approval_status'] ?? ''));
    $authority = trim((string)($payload['approving_authority'] ?? ''));
    $approvalDateRaw = trim((string)($payload['approval_date'] ?? ''));
    $remarks = trim((string)($payload['remarks'] ?? ''));
    $sourceRef = trim((string)($payload['supporting_source_reference'] ?? ''));
    $reqImportStatus = trim((string)($payload['import_status'] ?? ''));

    // Distinguish Return for Correction from Academic Conditional
    $isReturnForCorrection = (
      $rawStatus === 'RETURNED_FOR_CORRECTION' ||
      !empty($payload['is_return_for_correction']) ||
      ($rawStatus === 'CONDITIONAL' && strpos($remarks, '[RETURNED_FOR_CORRECTION]') !== false)
    );

    $newStatus = $isReturnForCorrection ? 'CONDITIONAL' : $rawStatus;

    if (!in_array($newStatus, $approvalAllowed, true)) {
      return array('ok' => false, 'message' => 'Invalid academic approval status selected.');
    }

    if ($isReturnForCorrection && strpos($remarks, '[RETURNED_FOR_CORRECTION]') === false) {
      $remarks = '[RETURNED_FOR_CORRECTION] ' . $remarks;
    }

    // Verify HOD authorization server-side if department scope is enforced
    $enforced = intval($enforcedDeptId);
    if ($enforced > 0) {
      $authCheck = $this->verifyHodAuthorization($uid, $enforced);
      if (!$authCheck['is_authorized']) {
        return array('ok' => false, 'message' => 'Authorization error: ' . $authCheck['reason']);
      }
    }

    // Guard against Authority Spoofing: prevent claiming BoS, Academic Council, Dean, or Principal approval
    if (preg_match('/\b(academic\s+council|board\s+of\s+studies|bos|dean|principal|governing\s+body|university)\b/i', $authority)) {
      return array('ok' => false, 'message' => 'Authority spoofing detected: HOD decisions cannot claim approval on behalf of Academic Council, Board of Studies, Dean, or Principal.');
    }

    if (strlen($authority) < 3) {
      return array('ok' => false, 'message' => 'Approving Authority is mandatory (at least 3 characters).');
    }

    // Server-enforced HOD authority formatting to bind authenticated HOD identity
    if ($enforced > 0) {
      $deptRow = $this->runPreparedSelect("SELECT department_name FROM st_department_master WHERE department_id = ?", 'i', array($enforced));
      $deptName = !empty($deptRow) ? trim((string)$deptRow[0]['department_name']) : ('Dept #' . $enforced);
      if (strpos($authority, 'HOD -') !== 0) {
        $authority = "HOD - {$deptName} ({$username}) [Ref: {$authority}]";
      }
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/', $approvalDateRaw)) {
      return array('ok' => false, 'message' => 'Valid Approval Date (YYYY-MM-DD) is mandatory.');
    }
    $parsedTs = strtotime($approvalDateRaw);
    if ($parsedTs === false) {
      return array('ok' => false, 'message' => 'Invalid Approval Date format.');
    }
    $approvalDateSql = date('Y-m-d H:i:s', $parsedTs);
    if (strlen($remarks) < 5) {
      return array('ok' => false, 'message' => 'Approval remarks (at least 5 characters) are mandatory.');
    }
    if (strlen($sourceRef) < 3) {
      return array('ok' => false, 'message' => 'Supporting source reference (e.g., signed IOM / BoS minute ref) is mandatory.');
    }
    if ($reqImportStatus === 'IMPORTED') {
      return array('ok' => false, 'message' => 'Approval cannot mark candidates as IMPORTED or auto-promote into active students.');
    }

    $isBulk = (count($cleanIds) > 1);
    if ($isReturnForCorrection) {
      $actionType = $isBulk ? 'BULK_RETURN_FOR_CORRECTION' : 'RETURN_FOR_CORRECTION';
    } else {
      $actionType = $isBulk ? 'BULK_APPROVAL_DECISION' : 'APPROVAL_DECISION';
    }

    mysqli_begin_transaction($this->conn);
    try {
      $ph = implode(',', array_fill(0, count($cleanIds), '?'));
      $tStr = str_repeat('i', count($cleanIds));
      $rows = $this->runPreparedSelect(
        "SELECT * FROM st_historical_student_candidates WHERE candidate_id IN ({$ph}) FOR UPDATE",
        $tStr,
        $cleanIds
      );
      if (count($rows) !== count($cleanIds)) {
        throw new InvalidArgumentException('One or more selected historical candidate IDs do not exist.');
      }

      $enforced = intval($enforcedDeptId);
      // Enforce department isolation and prerequisite rules prior to making decisions
      foreach ($rows as $row) {
        if ($enforced > 0 && intval($row['cur_department_id'] ?? 0) !== $enforced) {
          throw new InvalidArgumentException("Security violation: Candidate {$row['staging_id']} ({$row['cur_student_name']}) does not belong to your authorized department.");
        }

        if ($newStatus === 'APPROVED') {
          // Candidates on Academic HOLD or INELIGIBLE cannot be approved via ordinary HOD decision
          if ($row['academic_approval_status'] === 'HOLD' || $row['eligibility_status'] === 'INELIGIBLE') {
            throw new InvalidArgumentException("Cannot approve {$row['staging_id']} ({$row['cur_student_name']}): Candidate is on Academic Hold or Ineligible. Institutional council resolution required.");
          }
          if (trim((string)($row['cur_erp_id'] ?? '')) === '') {
            throw new InvalidArgumentException("Cannot mark {$row['staging_id']} APPROVED: curated ERP ID is missing.");
          }
          if (intval($row['cur_division_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException("Cannot mark {$row['staging_id']} APPROVED: curated Division is missing.");
          }
          if (in_array($row['eligibility_status'], array('INELIGIBLE', 'UNRESOLVED'), true)) {
            throw new InvalidArgumentException("Cannot mark {$row['staging_id']} APPROVED while eligibility is {$row['eligibility_status']}.");
          }
          if (intval($row['cur_specialization_id'] ?? 0) <= 0 && intval($row['cur_minor_course_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException("Cannot mark {$row['staging_id']} APPROVED: curated Specialization/Minor mapping is missing.");
          }
        }
      }

      $stmtUpd = mysqli_prepare(
        $this->conn,
        "UPDATE `st_historical_student_candidates`
         SET academic_approval_status = ?,
             import_status = ?,
             approving_authority = ?,
             approved_by_user_id = ?,
             approval_date = ?,
             approval_remarks = ?,
             supporting_source_reference = ?
         WHERE candidate_id = ?"
      );
      $stmtAudit = mysqli_prepare(
        $this->conn,
        "INSERT INTO `st_historical_candidate_audit`
         (`candidate_id`, `action_type`, `field_name`, `old_value`, `new_value`, `approving_authority`, `remarks`, `supporting_source_reference`, `performed_by_user_id`, `performed_by_username`)
         VALUES (?, ?, 'academic_approval_status', ?, ?, ?, ?, ?, ?, ?)"
      );
      if (!$stmtUpd || !$stmtAudit) {
        throw new RuntimeException('Failed preparing approval decision statements.');
      }

      foreach ($rows as $row) {
        $cid = intval($row['candidate_id']);
        $oldApp = (string)$row['academic_approval_status'];
        $nextImp = (string)$row['import_status'];
        if ($reqImportStatus !== '') {
          if ($reqImportStatus === 'READY_FOR_IMPORT' && $newStatus === 'APPROVED') {
            $nextImp = 'READY_FOR_IMPORT';
          } elseif ($reqImportStatus === 'EXCLUDED' && $newStatus === 'REJECTED') {
            $nextImp = 'EXCLUDED';
          } elseif ($reqImportStatus === 'NOT_IMPORTED') {
            $nextImp = 'NOT_IMPORTED';
          } else {
            throw new InvalidArgumentException("Invalid import_status transition '{$reqImportStatus}' for approval '{$newStatus}'.");
          }
        } elseif ($newStatus === 'REJECTED') {
          $nextImp = 'EXCLUDED';
        } elseif ($newStatus !== 'APPROVED' && $nextImp === 'READY_FOR_IMPORT') {
          $nextImp = 'NOT_IMPORTED';
        }

        mysqli_stmt_bind_param(
          $stmtUpd,
          'sssisssi',
          $newStatus,
          $nextImp,
          $authority,
          $uid,
          $approvalDateSql,
          $remarks,
          $sourceRef,
          $cid
        );
        if (!mysqli_stmt_execute($stmtUpd)) {
          throw new RuntimeException(mysqli_stmt_error($stmtUpd));
        }

        mysqli_stmt_bind_param(
          $stmtAudit,
          'issssssis',
          $cid,
          $actionType,
          $oldApp,
          $newStatus,
          $authority,
          $remarks,
          $sourceRef,
          $uid,
          $username
        );
        if (!mysqli_stmt_execute($stmtAudit)) {
          throw new RuntimeException(mysqli_stmt_error($stmtAudit));
        }
      }

      mysqli_stmt_close($stmtUpd);
      mysqli_stmt_close($stmtAudit);

      if ($dryRun) {
        @mysqli_rollback($this->conn);
        return array(
          'ok' => true,
          'message' => 'Dry run rehearsal successful: all prerequisite checks and validations passed.',
          'updated_count' => count($cleanIds)
        );
      }

      $this->writeAuditLog(
        $uid,
        $isBulk ? 'HISTORICAL_BULK_APPROVAL' : 'HISTORICAL_APPROVAL_DECISION',
        'st_historical_student_candidates',
        count($cleanIds) === 1 ? $cleanIds[0] : null,
        "Recorded {$newStatus} decision for " . count($cleanIds) . " candidate(s); authority={$authority}; date={$approvalDateSql}; ref={$sourceRef}",
        $username
      );

      mysqli_commit($this->conn);
      return array(
        'ok' => true,
        'message' => 'Academic approval decision recorded successfully (no automatic promotion performed).',
        'updated_count' => count($cleanIds)
      );
    } catch (Throwable $e) {
      @mysqli_rollback($this->conn);
      return array('ok' => false, 'message' => $e->getMessage());
    }
  }

  /**
   * Transitions a candidate returned for correction back to PENDING upon rectification.
   * Strictly enforces department isolation.
   */
  public function resubmitCandidateCorrection($candidateId, $userId, $username, $enforcedDeptId = null)
  {
    $uid = intval($userId);
    $cid = intval($candidateId);
    if (!($this->conn instanceof mysqli) || $uid <= 0 || $cid <= 0) {
      return array('ok' => false, 'message' => 'Invalid parameters or user session.');
    }

    $cands = $this->runPreparedSelect("SELECT * FROM st_historical_student_candidates WHERE candidate_id = ?", 'i', array($cid));
    if (empty($cands)) {
      return array('ok' => false, 'message' => 'Candidate record not found.');
    }
    $c = $cands[0];

    $enforced = intval($enforcedDeptId);
    if ($enforced > 0 && intval($c['cur_department_id'] ?? 0) !== $enforced) {
      return array('ok' => false, 'message' => 'Security violation: Candidate belongs to another department.');
    }

    if ($c['academic_approval_status'] !== 'CONDITIONAL' || strpos((string)($c['approval_remarks'] ?? ''), '[RETURNED_FOR_CORRECTION]') === false) {
      return array('ok' => false, 'message' => 'Candidate is not currently in Returned for Correction status.');
    }

    mysqli_begin_transaction($this->conn);
    try {
      $stmtUpd = mysqli_prepare(
        $this->conn,
        "UPDATE st_historical_student_candidates
         SET academic_approval_status = 'PENDING',
             import_status = 'NOT_IMPORTED'
         WHERE candidate_id = ?"
      );
      if (!$stmtUpd) {
        throw new RuntimeException('Failed preparing resubmission statement.');
      }
      mysqli_stmt_bind_param($stmtUpd, 'i', $cid);
      if (!mysqli_stmt_execute($stmtUpd)) {
        throw new RuntimeException(mysqli_stmt_error($stmtUpd));
      }
      mysqli_stmt_close($stmtUpd);

      $stmtAudit = mysqli_prepare(
        $this->conn,
        "INSERT INTO `st_historical_candidate_audit`
         (`candidate_id`, `action_type`, `field_name`, `old_value`, `new_value`, `remarks`, `performed_by_user_id`, `performed_by_username`)
         VALUES (?, 'STATUS_CHANGE', 'academic_approval_status', 'CONDITIONAL', 'PENDING', 'Re-submitted for HOD approval after correction', ?, ?)"
      );
      if (!$stmtAudit) {
        throw new RuntimeException('Failed preparing resubmission audit statement.');
      }
      mysqli_stmt_bind_param($stmtAudit, 'iis', $cid, $uid, $username);
      if (!mysqli_stmt_execute($stmtAudit)) {
        throw new RuntimeException(mysqli_stmt_error($stmtAudit));
      }
      mysqli_stmt_close($stmtAudit);

      $this->writeAuditLog(
        $uid,
        'HISTORICAL_CANDIDATE_RESUBMIT',
        'st_historical_student_candidates',
        $cid,
        "Candidate {$c['staging_id']} re-submitted for HOD approval after correction",
        $username
      );

      mysqli_commit($this->conn);
      return array('ok' => true, 'message' => 'Candidate successfully re-submitted for institutional approval.');
    } catch (Throwable $e) {
      @mysqli_rollback($this->conn);
      return array('ok' => false, 'message' => $e->getMessage());
    }
  }

  /**
   * Generates department-wise approval monitoring metrics for Super Admin.
   */
  public function getSuperAdminApprovalMonitoringSummary()
  {
    if (!($this->conn instanceof mysqli)) {
      return array();
    }
    $sql = "SELECT
              d.department_id,
              d.department_name,
              COUNT(c.candidate_id) AS total_candidates,
              SUM(CASE WHEN c.academic_approval_status = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
              SUM(CASE WHEN c.academic_approval_status = 'APPROVED' THEN 1 ELSE 0 END) AS approved_count,
              SUM(CASE WHEN c.academic_approval_status = 'REJECTED' THEN 1 ELSE 0 END) AS rejected_count,
              SUM(CASE WHEN c.academic_approval_status = 'CONDITIONAL' AND (c.approval_remarks IS NULL OR c.approval_remarks NOT LIKE '%[RETURNED_FOR_CORRECTION]%') THEN 1 ELSE 0 END) AS conditional_academic_count,
              SUM(CASE WHEN c.academic_approval_status = 'CONDITIONAL' AND c.approval_remarks LIKE '%[RETURNED_FOR_CORRECTION]%' THEN 1 ELSE 0 END) AS returned_for_correction_count,
              SUM(CASE WHEN c.academic_approval_status = 'CONDITIONAL' THEN 1 ELSE 0 END) AS conditional_count,
              SUM(CASE WHEN c.academic_approval_status = 'HOLD' THEN 1 ELSE 0 END) AS hold_count,
              SUM(CASE WHEN c.eligibility_status = 'INELIGIBLE' THEN 1 ELSE 0 END) AS ineligible_count,
              MAX(c.approval_date) AS last_decision_date
            FROM st_department_master d
            LEFT JOIN st_historical_student_candidates c ON c.cur_department_id = d.department_id
            GROUP BY d.department_id, d.department_name
            ORDER BY d.department_id ASC";
    return $this->runPreparedSelect($sql);
  }

}
}
?>
