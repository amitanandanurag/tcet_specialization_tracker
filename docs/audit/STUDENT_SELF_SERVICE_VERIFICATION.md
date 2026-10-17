# TCET ERP — Student Self-Service Verification Report

**Project:** TCET Specialization Tracker (TCET ERP)  
**Location:** `C:\xampp\htdocs\st`  
**Files Modified:** `admin/student_dashboard.php`, `admin/header/header.php`, `database/db_connect.php`  
**Date:** October 17, 2026  
**Status:** IMPLEMENTED, BOUNDED & VERIFIED  

---

## 1. Executive Summary

In adherence to Phase A (Section 7) of the Master Requirements, the student self-service portal was audited, hardened, and expanded in `admin/student_dashboard.php`:
1. **Profile Completeness Tracker:** Calculates student profile health based on 11 legitimate ERP fields, rendering a responsive progress bar, status badge, and dynamic list of missing fields.
2. **Bounded Profile Self-Update:** Students can directly update personal contact details (Email and Mobile Number) with strict CSRF protection and audit trail logging. Protected institutional fields (Student ID, Roll No, Registration No, Department, Specialization, CGPA) cannot be modified by the student.
3. **Validated Sequential Semester Progression:** Implements academic progression rules preventing arbitrary skipping ($N \to N+2$) or backward transitions ($N \to N-1$). Progressions from Semester $N \to N+1$ are validated, audited, and bound to the authenticated student session.

---

## 2. Profile Completeness Tracker Implementation

### 2.1 The 11 Legitimate ERP Fields
An audit of `st_student_master` identified 11 essential fields required for academic tracking, avoiding unnecessary or intrusive personal data:

| # | Field Name | Description | Category |
|---|---|---|---|
| 1 | `fname` | Student Full Name | Identity |
| 2 | `email` | Institutional / Personal Email | Contact |
| 3 | `mobile` | Primary Mobile Number | Contact |
| 4 | `roll_no` | Academic Roll Number | Academic |
| 5 | `registration_no` | Institution Registration ID | Academic |
| 6 | `department_id` | Enrolled Academic Department | Institutional |
| 7 | `current_semester_id`| Active Semester | Academic |
| 8 | `specialization_id` | Honours / Minor Specialization Track | Curriculum |
| 9 | `cgpa` | Cumulative Grade Point Average | Academic |
| 10 | `grad_year` | Expected Graduation Year | Cohort |
| 11 | `division_id` | Class Division | Administration |

### 2.2 Calculation Logic (`admin/student_dashboard.php`)
```php
$completedFields = 0;
$missingFields = [];
foreach ($requiredProfileFields as $field => $label) {
    if (!empty($studentData[$field])) {
        $completedFields++;
    } else {
        $missingFields[] = $label;
    }
}
$profileCompletenessPct = (int) round(($completedFields / count($requiredProfileFields)) * 100);
```

### 2.3 UI Feedback States
- **100% Complete:** Displays a green badge `<span class="badge bg-green">Profile 100% Complete</span>` with an alert confirming full verification readiness.
- **< 100% Complete:** Displays a striped progress bar with percentage and an actionable warning box enumerating the exact missing fields:
  ```html
  <div class="alert alert-warning">
      <strong><i class="fa fa-info-circle"></i> Complete Your Profile:</strong>
      Missing information: CGPA, Graduation Year, Division.
  </div>
  ```

---

## 3. Bounded Contact Self-Update Workflow

### 3.1 Security & Protection Model
Students must not alter institutional records (such as their Roll No, Registration No, CGPA, or Department), but must be able to maintain current contact info.
- **Allowed Fields:** `email`, `mobile`
- **Protected Fields:** `student_id`, `roll_no`, `registration_no`, `department_id`, `specialization_id`, `cgpa`, `status`
- **Identity Binding:** The update handler resolves `$loginStudentId` exclusively from the authenticated session (`$_SESSION['student_id']`), ignoring any client-supplied ID parameter.
- **CSRF Token:** Enforces `$_SESSION['csrf_token'] === $_POST['csrf_token']`.

### 3.2 Audit Trail Recording
Upon successful contact update, an audit record is logged in `st_audit_log`:
```sql
INSERT INTO st_audit_log (user_id, action_type, affected_table, affected_record, description, username, performed_at)
VALUES ($userid, 'STUDENT_CONTACT_UPDATE', 'st_student_master', $studentId, 'Student updated contact details (email/mobile)', '$username', NOW());
```

---

## 4. Validated Sequential Semester Progression Workflow

### 4.1 Business Rules for Semester Progression
1. **Session-Bound Identity:** Only the authenticated student can initiate progression for their own record.
2. **Sequential Requirement ($N \to N+1$):**
   - If current semester is $3$, only semester $4$ is accepted.
   - Semester skipping ($3 \to 5$) is rejected with message: `"Semester progression must be sequential (3 to 4). Skipping semesters is not allowed."`
   - Backward movement ($4 \to 3$) is rejected with message: `"Backward semester progression is not permitted without administrative authorization."`
   - Redundant movement ($3 \to 3$) is rejected.
3. **Boundaries:** Semesters are bounded between $1$ and $8$. Attempting progression past $8$ is rejected.
4. **Audit Logging:** Every progression event is logged in `st_audit_log` with the transition details (`Semester X -> Y`).

---

## 5. Verification Test Evidence

Automated regression suite `tests/test_role_based_access_control.php` Section 5 validated all student self-service capabilities:

```
>>> Section 5: Testing Student Self-Service Workflows...
  [PASS] Full student profile calculates to 100% complete (11/11 fields)
  [PASS] Partial student profile calculates to 73% complete (8/11 fields)
  [PASS] Progression Sem 3 -> 4 is valid (Progression valid)
  [PASS] Progression Sem 3 -> 5 correctly blocked: Semester progression must be sequential (3 to 4). Skipping semesters is not allowed.
  [PASS] Progression Sem 4 -> 3 correctly blocked: Backward semester progression is not permitted without administrative authorization
  [PASS] Progression Sem 3 -> 3 correctly blocked: Target semester is same as current semester
  [PASS] Progression Sem 8 -> 9 correctly blocked: Target semester exceeds maximum permitted undergraduate semester (8)
```

Direct URL tampering tests confirmed that students cannot access other students' profiles or administrative endpoints.
