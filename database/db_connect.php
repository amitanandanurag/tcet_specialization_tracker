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

      // A student may have a different mentor in every semester. Never remove
      // mappings belonging to another semester.
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

  public function isSubjectAvailable($subjectId, $departmentId, $semesterId, $specializationId)
  {
    if (!($this->conn instanceof mysqli)) {
      return false;
    }
    $stmt = mysqli_prepare($this->conn, "SELECT subject_id FROM st_specialization_subject_master WHERE subject_id = ? AND department_id = ? AND semester_id = ? AND specialization_id = ? AND is_active = 1 LIMIT 1");
    if (!$stmt) {
      return false;
    }
    $subjectId = intval($subjectId);
    $departmentId = intval($departmentId);
    $semesterId = intval($semesterId);
    $specializationId = intval($specializationId);
    mysqli_stmt_bind_param($stmt, "iiii", $subjectId, $departmentId, $semesterId, $specializationId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $available = $result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);
    return $available;
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
      
      // Read the mentor after allocation when available. This value is a
      // historical snapshot and is not inferred from the student's current row.
      $mentorId = null;
      $mentorStmt = mysqli_prepare($this->conn, "SELECT mentor_id FROM st_mentor_student_mapping WHERE student_id = ? AND semester_id = ? ORDER BY mapping_id DESC LIMIT 1");
      if ($mentorStmt) {
        mysqli_stmt_bind_param($mentorStmt, "ii", $studentId, $semesterId);
        mysqli_stmt_execute($mentorStmt);
        $mentorResult = mysqli_stmt_get_result($mentorStmt);
        if ($mentorResult && ($mentorRow = mysqli_fetch_assoc($mentorResult))) {
          $mentorId = intval($mentorRow['mentor_id']);
        }
        mysqli_stmt_close($mentorStmt);
      }

      // UPSERT only the requested semester. A unique key prevents duplicate
      // active records for one student and semester.
      $upsertSql = "INSERT INTO st_student_semester_history (
                      student_id, academic_year_id, class_id, semester_id, division_id,
                      specialization_id, specialization_subject_id, minor_course_id, minor_subject_id,
                      cgpa, research_component_i_id, research_core_vii, research_component_ii_id, research_core_viii,
                      mentor_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      academic_year_id = IF(status = 'Active', VALUES(academic_year_id), academic_year_id),
                      class_id = IF(status = 'Active', VALUES(class_id), class_id),
                      division_id = IF(status = 'Active', VALUES(division_id), division_id),
                      specialization_id = IF(status = 'Active', VALUES(specialization_id), specialization_id),
                      specialization_subject_id = IF(status = 'Active', VALUES(specialization_subject_id), specialization_subject_id),
                      minor_course_id = IF(status = 'Active', VALUES(minor_course_id), minor_course_id),
                      minor_subject_id = IF(status = 'Active', VALUES(minor_subject_id), minor_subject_id),
                      cgpa = IF(status = 'Active', VALUES(cgpa), cgpa),
                      research_component_i_id = IF(status = 'Active', VALUES(research_component_i_id), research_component_i_id),
                      research_core_vii = IF(status = 'Active', VALUES(research_core_vii), research_core_vii),
                      research_component_ii_id = IF(status = 'Active', VALUES(research_component_ii_id), research_component_ii_id),
                      research_core_viii = IF(status = 'Active', VALUES(research_core_viii), research_core_viii),
                      mentor_id = COALESCE(VALUES(mentor_id), mentor_id)";
                      
      $upStmt = mysqli_prepare($this->conn, $upsertSql);
      if ($upStmt) {
        $bindTypes = "iiiiiiiiidisis" . "i";
        mysqli_stmt_bind_param($upStmt, $bindTypes,
          $studentId, $ayId, $classId, $semesterId, $divId,
          $specId, $specSubId, $minorCourseId, $minorSubId,
          $cgpa, $resComp1, $resCore7, $resComp2, $resCore8, $mentorId
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
    
    // Find all student allocations for this mentor where the active semester has mismatched subject or department
    $sql = "
      SELECT msm.student_id, msm.semester_id
      FROM st_mentor_student_mapping msm
      JOIN st_student_master sm ON sm.student_id = msm.student_id AND sm.current_semester_id = msm.semester_id
      LEFT JOIN st_student_semester_history h_current ON h_current.student_id = sm.student_id AND h_current.semester_id = sm.current_semester_id
      JOIN st_user_master mentor ON mentor.user_id = msm.mentor_id
      LEFT JOIN st_mentor_subject_mapping ms_map ON ms_map.mentor_id = mentor.user_id
      WHERE msm.mentor_id = ?
        AND sm.status = 1
        AND (
          ms_map.subject_id IS NULL 
          OR COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id) IS NULL
          OR COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id) != ms_map.subject_id
          OR sm.department_id != mentor.department_id
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

  public function allocateStudentToMentor($studentId, $mentorId, $semesterId)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0 || intval($mentorId) <= 0 || intval($semesterId) <= 0) {
      return false;
    }
    $studentId = intval($studentId);
    $mentorId = intval($mentorId);
    $semesterId = intval($semesterId);

    // Validate mentor subject and department match student's subject, department and semester
    $validateSql = "
      SELECT 1 
      FROM st_student_master sm
      JOIN st_user_master mentor ON mentor.user_id = ?
      JOIN st_mentor_subject_mapping ms_map ON ms_map.mentor_id = mentor.user_id
      JOIN st_specialization_subject_master sub ON sub.subject_id = ms_map.subject_id
      WHERE sm.student_id = ? 
        AND sm.specialization_subject_id = ms_map.subject_id
        AND sm.department_id = mentor.department_id
        AND sub.semester_id = ?
        AND sm.current_semester_id = ?
    ";
    
    $stmt = mysqli_prepare($this->conn, $validateSql);
    if (!$stmt) {
      return false;
    }
    mysqli_stmt_bind_param($stmt, "iiii", $mentorId, $studentId, $semesterId, $semesterId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $isValid = $result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    if (!$isValid) {
      return false;
    }

    // Get student's academic year id
    $ayId = 1;
    $ayResult = mysqli_query($this->conn, "SELECT academic_year_id FROM st_student_master WHERE student_id = $studentId LIMIT 1");
    if ($ayResult && ($ayRow = mysqli_fetch_assoc($ayResult))) {
      $ayId = max(1, intval($ayRow['academic_year_id']));
    }

    // Check if another mentor is already assigned for this semester
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
        
        // If the existing mentor is compatible, do not override/steal the allocation.
        $compatSql = "
          SELECT 1 
          FROM st_student_master sm
          JOIN st_mentor_subject_mapping ms_map ON ms_map.mentor_id = ?
          WHERE sm.student_id = ? AND sm.specialization_subject_id = ms_map.subject_id
        ";
        $compStmt = mysqli_prepare($this->conn, $compatSql);
        if ($compStmt) {
          mysqli_stmt_bind_param($compStmt, "ii", $existingMentorId, $studentId);
          mysqli_stmt_execute($compStmt);
          $compRes = mysqli_stmt_get_result($compStmt);
          $isCompat = $compRes && mysqli_num_rows($compRes) > 0;
          mysqli_stmt_close($compStmt);
          if ($isCompat) {
            return true; // Already has a compatible mentor, don't change.
          }
        }
      } else {
        mysqli_stmt_close($cStmt);
      }
    }

    // Upsert mapping
    $insertSql = "
      INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id, academic_year_id)
      VALUES (?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id)
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

    // Step 2: Find all eligible students who do not have a compatible active mentor
    $eligibleSql = "
      SELECT sm.student_id, sm.current_semester_id, sm.academic_year_id
      FROM st_student_master sm
      LEFT JOIN st_student_semester_history h_current ON h_current.student_id = sm.student_id AND h_current.semester_id = sm.current_semester_id
      JOIN st_user_master mentor ON mentor.user_id = ?
      JOIN st_mentor_subject_mapping ms_map ON ms_map.mentor_id = mentor.user_id AND ms_map.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id)
      JOIN st_specialization_subject_master sub ON sub.subject_id = ms_map.subject_id
      LEFT JOIN st_mentor_student_mapping msm ON msm.student_id = sm.student_id AND msm.semester_id = sm.current_semester_id
      WHERE sm.status = 1
        AND sm.department_id = mentor.department_id
        AND sub.semester_id = sm.current_semester_id
        AND (
          msm.mentor_id IS NULL
          OR msm.mentor_id NOT IN (
            SELECT mentor.user_id 
            FROM st_user_master mentor
            JOIN st_mentor_subject_mapping ms_map ON ms_map.mentor_id = mentor.user_id
            WHERE ms_map.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id)
              AND mentor.department_id = sm.department_id
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
            ON DUPLICATE KEY UPDATE mentor_id = $mentorId
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

  public function calculateEligibleMentors($studentId)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0) {
      return array();
    }
    $studentId = intval($studentId);
    $sql = "
      SELECT u.user_id AS mentor_id,
             COALESCE(NULLIF(TRIM(u.user_name), ''), u.email_id) AS mentor_name
      FROM st_user_master u
      JOIN st_mentor_subject_mapping msm ON msm.mentor_id = u.user_id
      JOIN st_student_master sm ON sm.student_id = ?
      WHERE u.department_id = sm.department_id
        AND msm.subject_id = sm.specialization_subject_id
        AND u.role_id = 4
    ";
    $stmt = mysqli_prepare($this->conn, $sql);
    $mentors = array();
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "i", $studentId);
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

  public function removeInvalidStudentMentorAllocation($studentId)
  {
    if (!($this->conn instanceof mysqli) || intval($studentId) <= 0) {
      return false;
    }
    $studentId = intval($studentId);
    
    // Find the student's active current semester allocation
    $sql = "
      SELECT msm.semester_id, msm.mentor_id
      FROM st_mentor_student_mapping msm
      JOIN st_student_master sm ON sm.student_id = msm.student_id AND sm.current_semester_id = msm.semester_id
      JOIN st_user_master mentor ON mentor.user_id = msm.mentor_id
      LEFT JOIN st_mentor_subject_mapping ms_map ON ms_map.mentor_id = mentor.user_id
      WHERE msm.student_id = ?
        AND (
          ms_map.subject_id IS NULL 
          OR sm.specialization_subject_id IS NULL
          OR sm.specialization_subject_id != ms_map.subject_id
          OR sm.department_id != mentor.department_id
        )
    ";
    
    $stmt = mysqli_prepare($this->conn, $sql);
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "i", $studentId);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
          $semId = intval($row['semester_id']);
          
          mysqli_query($this->conn, "DELETE FROM st_mentor_student_mapping WHERE student_id = $studentId AND semester_id = $semId");
          mysqli_query($this->conn, "UPDATE st_student_semester_history SET mentor_id = NULL WHERE student_id = $studentId AND semester_id = $semId AND status = 'Active'");
        }
      }
      mysqli_stmt_close($stmt);
    }
    return true;
  }

}
?>
