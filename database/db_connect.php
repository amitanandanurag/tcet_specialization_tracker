<?php
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

    function __construct()
    {
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
    $result = mysqli_query($this->conn, $query);

    if (!$result) {
        die("Query Failed: " . mysqli_error($this->conn));
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
    
    // Find mentors mapped to this subject, order by their student count in this semester (ascending)
    $sql = "SELECT msm.mentor_id, COUNT(ms.mapping_id) as student_count 
            FROM st_mentor_subject_mapping msm
            LEFT JOIN st_mentor_student_mapping ms ON ms.mentor_id = msm.mentor_id AND ms.semester_id = ?
            WHERE msm.subject_id = ?
            GROUP BY msm.mentor_id
            ORDER BY student_count ASC, msm.mentor_id ASC
            LIMIT 1";
            
    $stmt = mysqli_prepare($this->conn, $sql);
    if (!$stmt) {
      return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $semesterId, $subjectId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
      $mentorId = intval($row['mentor_id']);
      mysqli_stmt_close($stmt);
      
      // Fetch student's academic_year_id
      $ayId = 1;
      $ayRes = mysqli_query($this->conn, "SELECT academic_year_id FROM st_student_master WHERE student_id = $studentId");
      if ($ayRes && $ayRow = mysqli_fetch_assoc($ayRes)) {
          $ayId = intval($ayRow['academic_year_id'] ?? 1);
      }

      // Delete existing mapping for this student in this academic year
      $deleteSql = "DELETE FROM st_mentor_student_mapping WHERE student_id = ? AND academic_year_id = ?";
      $delStmt = mysqli_prepare($this->conn, $deleteSql);
      if ($delStmt) {
        mysqli_stmt_bind_param($delStmt, "ii", $studentId, $ayId);
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
          if (method_exists($this, 'writeAuditLog')) {
            $this->writeAuditLog($studentId, 'AUTO_MENTOR_ALLOCATION', 'st_mentor_student_mapping', null, "Automatically assigned mentor ID {$mentorId} due to specialization subject selection");
          }
          return true;
        }
      }
    } else {
      mysqli_stmt_close($stmt);
    }
    
    return false;
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
    $sql = "SELECT academic_year_id, class_id, division_id, specialization_id, specialization_subject_id,
                   minor_course_id, minor_subject_id, cgpa, research_component_i_id, research_core_vii,
                   research_component_ii_id, research_core_viii
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
      
      $ayId = $row['academic_year_id'] !== null ? intval($row['academic_year_id']) : null;
      $classId = $row['class_id'] !== null ? intval($row['class_id']) : null;
      $divId = $row['division_id'] !== null ? intval($row['division_id']) : null;
      $specId = $row['specialization_id'] !== null ? intval($row['specialization_id']) : null;
      $specSubId = $row['specialization_subject_id'] !== null ? intval($row['specialization_subject_id']) : null;
      $minorCourseId = $row['minor_course_id'] !== null ? intval($row['minor_course_id']) : null;
      $minorSubId = $row['minor_subject_id'] !== null ? intval($row['minor_subject_id']) : null;
      $cgpa = $row['cgpa'] !== null ? floatval($row['cgpa']) : null;
      $resComp1 = $row['research_component_i_id'] !== null ? intval($row['research_component_i_id']) : null;
      $resCore7 = $row['research_core_vii'] !== null ? (string)$row['research_core_vii'] : null;
      $resComp2 = $row['research_component_ii_id'] !== null ? intval($row['research_component_ii_id']) : null;
      $resCore8 = $row['research_core_viii'] !== null ? (string)$row['research_core_viii'] : null;
      
      // UPSERT into st_student_semester_history
      $upsertSql = "INSERT INTO st_student_semester_history (
                      student_id, academic_year_id, class_id, semester_id, division_id,
                      specialization_id, specialization_subject_id, minor_course_id, minor_subject_id,
                      cgpa, research_component_i_id, research_core_vii, research_component_ii_id, research_core_viii
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      academic_year_id = VALUES(academic_year_id),
                      class_id = VALUES(class_id),
                      division_id = VALUES(division_id),
                      specialization_id = VALUES(specialization_id),
                      specialization_subject_id = VALUES(specialization_subject_id),
                      minor_course_id = VALUES(minor_course_id),
                      minor_subject_id = VALUES(minor_subject_id),
                      cgpa = VALUES(cgpa),
                      research_component_i_id = VALUES(research_component_i_id),
                      research_core_vii = VALUES(research_core_vii),
                      research_component_ii_id = VALUES(research_component_ii_id),
                      research_core_viii = VALUES(research_core_viii)";
                      
      $upStmt = mysqli_prepare($this->conn, $upsertSql);
      if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "iiiiiiiiidisis",
          $studentId, $ayId, $classId, $semesterId, $divId,
          $specId, $specSubId, $minorCourseId, $minorSubId,
          $cgpa, $resComp1, $resCore7, $resComp2, $resCore8
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

}
?>
