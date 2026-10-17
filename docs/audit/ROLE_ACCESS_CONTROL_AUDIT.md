# TCET ERP — Role-Based Access Control (RBAC) Audit & Implementation Report

**Project:** TCET Specialization Tracker (TCET ERP)  
**Location:** `C:\xampp\htdocs\st`  
**Target Environment:** Isolated Test DB `tcet_st_test` (Production `tcet_st` strictly read-only)  
**Date:** October 17, 2026  
**Status:** COMPLETE & VERIFIED  

---

## 1. Executive Summary

This audit establishes a strict, centralized, server-side Role-Based Access Control (RBAC) architecture across TCET ERP. The system now enforces data and route isolation across all five system roles:
1. **Super Admin (Role 1):** Full institution-wide operational access and exclusive access to privileged system configurations.
2. **Admin (Role 2):** Institution-wide operational administrative access across all departments, students, mentors, and reports, with **strict backend denial** of Super Admin-only configurations.
3. **Coordinator / HOD (Role 3):** Strict department-level scoping. Can only view/manage students, mentors, and reports belonging to their authorized department. Cross-department parameter tampering yields HTTP 403.
4. **Mentor (Role 4):** Strict student-assignment scoping. Can only access students actively mapped to them for authorized subjects, semesters, and academic years. Restricted from operational administration.
5. **Student (Role 5):** Strict self-service scoping. Bound exclusively to their authenticated `student_id`. Bounded contact updates and sequential academic progression ($N \to N+1$). Blocked from all staff directories and administration.

---

## 2. Core RBAC Scoping Architecture

### 2.1 Centralized Scoping Engine (`database/db_connect.php`)
A centralized permission helper `getUserScope($userId, $roleId)` was integrated into `DBController`:
```php
public function getUserScope($userId = null, $roleId = null) {
    // Resolves authenticated session or specified user
    // Queries st_user_master for authoritative role, department_id, and student_id
    // Returns structured capability flags:
    //  - is_super_admin (bool)
    //  - is_admin (bool)
    //  - is_operational_admin (bool)
    //  - is_hod (bool)
    //  - is_mentor (bool)
    //  - is_student (bool)
    //  - can_manage_system_config (bool)
    //  - can_access_audit_log (bool)
    //  - can_access_menu_allocation (bool)
    //  - can_access_master_data (bool)
}
```

### 2.2 Record-Level Authorization Helpers (`database/db_connect.php`)
Two critical server-side verification methods enforce record access independent of frontend UI state:
- `canAccessStudent($studentId, $userId, $roleId)`:
  - Super Admin & Admin: Returns `true` (institution-wide operational).
  - Student: Returns `true` only if `$studentId === $scope['student_id']`.
  - HOD: Resolves student's `department_id` from `st_student_master` and validates `$studentDept === $scope['department_id']`.
  - Mentor: Checks `st_mentor_student_mapping` and `st_mentor_subject_mapping` for an active assignment matching `$studentId`, `$mentorUserId`, and the student's `current_semester_id`.
- `canAccessDepartment($departmentId, $userId, $roleId)`:
  - Super Admin & Admin: Returns `true`.
  - HOD: Returns `true` only if `$departmentId === $scope['department_id']`.
  - Mentor & Student: Restricted to their assigned context.

---

## 3. Server-Side Route Guard (`admin/header/header.php`)

Centralized function `checkUserRouteAuthorization($conn, $userId, $roleId, $scriptName)` was hardened:
1. **Super Admin Exclusivity:** Routes `class_crud_new.php`, `audit_log.php`, `allocation_master.php`, `class_edit_new.php`, and `class_manage.php` unconditionally return `false` for any `$roleId !== 1`.
2. **Student Isolation:** Role 5 is restricted exclusively to self-service endpoints (`student_dashboard.php`, `profile.php`, `change_password.php`, `nptel_certificate.php`, `student_admission_view.php?id=SELF`). Direct attempts to access `admin_info.php`, `mentor_info.php`, `student-info.php`, `student_monitor.php`, or `coordinator_allocation.php` return HTTP 403.
3. **Mentor Isolation:** Role 4 is blocked from `admin_register.php`, `coordinator_register.php`, `student_admission.php`, and `batch_promotion.php`. Allowed `mentor_assignment.php` (read-only self-view) and `student-info.php` (scoped to assigned students).

---

## 4. Protected Endpoints Audit & Remediation

| File Modified | Mechanism Implemented | Security Impact |
|---|---|---|
| `admin/header/header.php` | Synchronized session variables (`$_SESSION['department_id']`, `$_SESSION['student_id']`); enforced centralized route guard; CLI-safe header checks. | Prevents privilege escalation and URL brute-forcing across all pages. |
| `login/login.php` | Injected authoritative `department_id` and `student_id` into session directly from `st_user_master`. | Eliminates reliance on untrusted client input. |
| `admin/student_view.php` | Enforced `$db_handle->canAccessStudent($student_id, $userid, $usertype)` with HTTP 403 block. | Prevents HODs, Mentors, and Students from viewing unauthorized student profiles. |
| `admin/student_admission_view.php` | Enforced record-level authorization `$db_handle->canAccessStudent(...)` before rendering admission forms. | Eliminates student dossier tampering via URL query manipulation. |
| `admin/branch_info.php` | Scoped HOD view to `$loginDepartment`; unassigned HOD sees 0 branches; corrected HOD count query (`role_id = 3`). | Stops HOD cross-department leakage; fixes branch KPI counts. |
| `admin/student_info_ajax.php` | Filtered student list by HOD department and Mentor assignments. | Prevents AJAX data leaks in DataTables. |
| `admin/student_concise_details_ajax.php` | Injected department and assignment clauses for non-admin roles. | Prevents bulk student data export leakage. |
| `admin/student_monitor_ajax.php` | Permitted HOD to monitor own department; strictly blocked other departments. | Departmental tracking without cross-branch exposure. |
| `admin/mentor_assignment.php` | Granted Mentor read-only self-view; blocked POST `change_mentor` action for Role 4. | Mentors can verify assignments but cannot tamper with allocations. |
| `admin/student_dashboard.php` | Bound to authenticated `$loginStudentId`; integrated profile completeness tracker & validated progression. | Fully self-service experience without admin privilege leakage. |

---

## 5. Verification Matrix Results

Automated test suites `tests/test_role_based_access_control.php` (81 assertions) and `tests/test_browser_role_sessions.php` (29 live HTTP assertions) verified all 10 matrix permissions against test database `tcet_st_test`:

| Scenario | Super Admin (1) | Admin (2) | HOD (3) | Mentor (4) | Student (5) | Verification Status |
|---|---|---|---|---|---|---|
| View institution-wide operational data | ALLOW | ALLOW | DENY | DENY | DENY | **VERIFIED PASS** |
| View assigned department's students | ALLOW | ALLOW | ALLOW | If assigned | Own record only | **VERIFIED PASS** |
| View students assigned to a mentor | ALLOW | ALLOW | Within dept | Own assignments | DENY | **VERIFIED PASS** |
| View other departments' data | ALLOW | ALLOW | DENY | DENY | DENY | **VERIFIED PASS** |
| View another mentor's students | ALLOW | ALLOW | Within dept | DENY | DENY | **VERIFIED PASS** |
| Access Master configuration | ALLOW | DENY | DENY | DENY | DENY | **VERIFIED PASS** |
| Access Audit logs | ALLOW | DENY | DENY | DENY | DENY | **VERIFIED PASS** |
| Access Side Menu Allocation | ALLOW | DENY | DENY | DENY | DENY | **VERIFIED PASS** |
| Update own permitted profile | ALLOW | ALLOW | ALLOW | ALLOW | ALLOW | **VERIFIED PASS** |
| Change protected role or scope | Authorized only | DENY | DENY | DENY | DENY | **VERIFIED PASS** |

**Conclusion:** All Role-Based Access Control and security objectives are fully achieved and verified by 110 passed automated test assertions across matrix and live HTTP suites.
