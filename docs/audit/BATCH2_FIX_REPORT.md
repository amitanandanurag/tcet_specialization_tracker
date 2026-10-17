# TCET Specialization Tracker — Batch 2 Remediation Report

## 1. Executive Summary

This document details the engineering root cause, comprehensive remediation, before-and-after behavior, modified source files, and verification evidence for **Batch 2 Remediation**:
- **P4**: Inconsistent student status handling across dashboards, deletions, mentor queries, and listings.
- **P5**: Plaintext password storage in student registration (`login/student_register.php`).
- **P10**: Invalid Subject Management navigation allocation for mentors (`st_menu_allocation_master`).

All remediations were implemented adhering strictly to security best practices, preserving all working business logic, and executing migrations exclusively against the isolated test database `tcet_st_test`.

---

## 2. Issue P4: Inconsistent Student Status Handling

- **Severity**: **HIGH**
- **Affected Files**:
  - [`admin/student_delete.php`](file:///c:/xampp/htdocs/st/admin/student_delete.php)
  - [`admin/dashboard.php`](file:///c:/xampp/htdocs/st/admin/dashboard.php)
  - [`admin/branch_info.php`](file:///c:/xampp/htdocs/st/admin/branch_info.php)
  - [`admin/mentor_allocation.php`](file:///c:/xampp/htdocs/st/admin/mentor_allocation.php)
  - [`admin/student_concise_details_ajax.php`](file:///c:/xampp/htdocs/st/admin/student_concise_details_ajax.php)
  - [`admin/student_info_ajax.php`](file:///c:/xampp/htdocs/st/admin/student_info_ajax.php)
  - [`admin/student_dashboard.php`](file:///c:/xampp/htdocs/st/admin/student_dashboard.php)

### 2.1 Root Cause Analysis
An architectural split existed across different modules of the codebase regarding the semantic meaning of `st_student_master.status`:
1. **Modern Standard (`status = 1` means Active/Approved/Enrolled)**:
   - `admin/student_process.php` (L355: `$status = 1;` on student admission).
   - `login/student_register.php` (inserts with `status = 1`).
   - `admin/student_dashboard.php` (L95: `if ((int)$student['status'] === 1)` displays `'Approved / Active'`; otherwise `'Pending Approval'`).
   - `admin/export_service.php` (L85: `IF(s.status = 1, 'Active', 'Inactive')`).
   - `admin/student_monitor_ajax.php` (L257: `intval($student['status']) === 1 ? 'Active' : 'Inactive'`).
   - `admin/list.php` (L35: `WHERE s.status = 1` for active students; L161: `WHERE s.status = 0` for rejected/inactive).
   - `admin/student_info_ajax.php` (L72 & L193: `AND sm.status = 1` for mentors).
2. **Legacy Inversion (`status = '0'` assumed Active)**:
   - `admin/student_delete.php` (L33: executed `UPDATE st_student_master SET status = '1'` when performing soft deletion, claiming `"Student moved to inactive list"`). This caused deactivated students to actually become active in all modern reporting modules!
   - `admin/dashboard.php` (L182, 263, 320, 403: queried `status = '0'` for total students, while line 221 simultaneously counted `status = 1` as approved, causing internal self-contradiction).
   - `admin/branch_info.php` (L18: queried `status = 0` for student count).
   - `admin/mentor_allocation.php` (L45: hardcoded `WHERE sm.status = '0'`).
   - `admin/student_concise_details_ajax.php` (L50: hardcoded `WHERE sm.status = '0'`).

### 2.2 Implemented Remediation
1. **Standardized Definition**: Established a unified application-wide standard:
   - **`1` = ACTIVE / ENROLLED / APPROVED**: Student has valid academic standing and is actively enrolled.
   - **`0` = INACTIVE / DEACTIVATED / PENDING**: Student is deactivated, de-enrolled, soft-deleted, or pending review.
2. **Deactivation & Reactivation (`admin/student_delete.php`)**:
   - Corrected soft deletion to set `status = 0` (Inactive) using parameterized prepared statements.
   - Added explicit reactivation workflow (`delete_type = 'reactivate'` / `'activate'`) setting `status = 1` (Active).
   - Emits structured audit log entries (`STUDENT_DEACTIVATED`, `STUDENT_REACTIVATED`).
3. **Dashboard & Branch Alignment (`admin/dashboard.php`, `admin/branch_info.php`)**:
   - Updated metric card queries, recent activity queries, and branch distribution joins to filter on `status = 1`.
   - Removed contradictory status semantics.
4. **Mentor Allocation & Concise Details**:
   - Updated `mentor_allocation.php` to default to active students (`status = 1`) while supporting dynamic status filtering.
   - Updated `student_concise_details_ajax.php` to filter on active students (`status = 1`).
5. **Preservation of Legacy Records**:
   - No blanket updates were executed on legacy records.
   - Legacy records imported without registration numbers remain preserved as inactive/pending review without guessing or corrupting historical records.

---

## 3. Issue P5: Secure Student Registration Passwords

- **Severity**: **CRITICAL**
- **Affected File**: [`login/student_register.php`](file:///c:/xampp/htdocs/st/login/student_register.php)
- **Supporting Classes**: [`database/db_connect.php`](file:///c:/xampp/htdocs/st/database/db_connect.php)

### 3.1 Root Cause Analysis
1. `student_register.php` assigned a shared default password `Tcet@1234` to all newly registered students.
2. The password was inserted directly into `st_login.password` in **plaintext** (`INSERT INTO st_login ... VALUES(..., '$password', ...)`), violating credential storage standards.
3. The registration form lacked CSRF protection, leaving the endpoint susceptible to cross-site request forgery.
4. Queries were executed via raw string concatenation rather than prepared statements.
5. Plaintext credentials were stored in `$_SESSION['registered_password']`, output directly in the HTML DOM (`#credText`), and exposed to the parent window via `postMessage`.

### 3.2 Implemented Remediation
1. **Never Store Plaintext**:
   - Integrated `DBController::hashPassword($plainPassword)` utilizing PHP `password_hash()` with `PASSWORD_BCRYPT` (cost factor 10).
   - Ensured `st_login.password` stores only verified cryptographic hashes.
2. **User-Created Password Workflow**:
   - Added `password` (minimum 8 characters) and `confirm_password` inputs to the registration form with both client-side JavaScript and strict backend PHP validations.
   - For backwards-compatible or automated registration calls where password is not submitted, a secure random temporary credential (`Tcet@` . bin2hex(random_bytes(3))) is generated and flagged with `is_first_login = 1` in `st_user_master`, enforcing immediate password reset upon first sign-in.
3. **CSRF Protection**:
   - Added CSRF token generation via `DBController::getCsrfInputField()`.
   - Validates CSRF token on submission via `DBController::validateCsrfToken()`, rejecting missing or invalid tokens with a 403-safe error response.
4. **Duplicate Account Prevention**:
   - Added duplicate verification on both `st_user_master.email_id` and `st_login.username` using parameterized prepared statements.
   - Returns a safe, user-friendly error message (`"An account with this email already exists!"`) without disclosing internal system states.
5. **Elimination of Credential Exposure**:
   - Removed password transmission via `postMessage` and session DOM leakage when the user provides their password.
   - Registration success screen confirms account creation securely.
   - Parent login window receives only the username field for convenience.
6. **Prepared Statements & Atomic Transactions**:
   - Converted student insertion, user master insertion, and login credential insertion to parameterized prepared statements (`mysqli_prepare`, `mysqli_stmt_bind_param`) enclosed within an atomic transaction with rollback on failure.

---

## 4. Issue P10: Invalid Subject Management Navigation for Mentors

- **Severity**: **MEDIUM**
- **Affected Files**:
  - [`admin/header/side_menu.php`](file:///c:/xampp/htdocs/st/admin/header/side_menu.php)
  - [`admin/specialization_subject_manage.php`](file:///c:/xampp/htdocs/st/admin/specialization_subject_manage.php)
  - [`database/migrations/20261016_remove_mentor_subject_manage_allocation.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_remove_mentor_subject_manage_allocation.sql)

### 4.1 Root Cause Analysis
1. In `admin/header/side_menu.php`, function `sidebar_seed_subject_management_menu()` contained:
   ```php
   foreach (array(1, 2, 3, 4) as $roleId) { ... }
   ```
   This automatically seeded menu allocation row `menu_allocation_id = 137` allocating `sub_menu_id = 59` (`specialization_subject_manage.php`) to `role_id = 4` (Mentor).
2. Consequently, mentors saw "Subject Management" in their Coordinator dropdown sidebar menu.
3. However, `admin/specialization_subject_manage.php` strictly enforced:
   ```php
   if (!in_array((int) ($usertype ?? 0), array(1, 2, 3), true)) {
       header("location: index.php");
       exit;
   }
   ```
   When a mentor clicked the navigation link, they were immediately ejected back to `index.php`.
4. Role 4 (Mentor) is an academic guidance role; mentors must not create, edit, or delete curriculum specialization subjects.

### 4.2 Implemented Remediation
1. **Database Migration (`20261016_remove_mentor_subject_manage_allocation.sql`)**:
   - Deleted allocation records for `role_id = 4` linked to `specialization_subject_manage.php`.
   - Executed and verified strictly on `tcet_st_test`.
2. **Sidebar Seeder Correction (`admin/header/side_menu.php`)**:
   - Restricted allocation seeding to `array(1, 2, 3)` (Super Admin, Admin/HOD, Coordinator).
   - Added self-healing cleanup removing any rogue role 4 allocations:
     ```php
     mysqli_query($db_handle->conn, "DELETE FROM st_menu_allocation_master WHERE role_id = 4 AND sub_menu_id = {$subMenuId}");
     ```
3. **Route Authorization & Access Verification**:
   - Centralized RBAC route guard `checkUserRouteAuthorization()` returns `false` for mentors on `specialization_subject_manage.php`.
   - Authorized roles 1, 2, and 3 retain full access.

---

## 5. Summary of Modified Files

| File | Changes Made |
| :--- | :--- |
| [`admin/student_delete.php`](file:///c:/xampp/htdocs/st/admin/student_delete.php) | Fixed soft delete to set `status = 0`, added reactivation branch setting `status = 1`, added robust `__DIR__` include path, and logged audit events. |
| [`admin/dashboard.php`](file:///c:/xampp/htdocs/st/admin/dashboard.php) | Standardized student count metrics, recent student activity, and branch queries to active status (`status = 1`). |
| [`admin/branch_info.php`](file:///c:/xampp/htdocs/st/admin/branch_info.php) | Updated total students subquery to count active students (`status = 1`). |
| [`admin/mentor_allocation.php`](file:///c:/xampp/htdocs/st/admin/mentor_allocation.php) | Updated student where-builder to default to active students (`status = 1`) with optional filter support. |
| [`admin/student_concise_details_ajax.php`](file:///c:/xampp/htdocs/st/admin/student_concise_details_ajax.php) | Standardized query to active students (`sm.status = 1`). |
| [`login/student_register.php`](file:///c:/xampp/htdocs/st/login/student_register.php) | Replaced plaintext password with bcrypt hashing, added user password/confirm fields, added CSRF input & backend validation, prepared statements, and safe success screen. |
| [`admin/header/side_menu.php`](file:///c:/xampp/htdocs/st/admin/header/side_menu.php) | Restricted Subject Management menu seeding to roles 1, 2, 3, removing invalid mentor allocation. |
| [`database/migrations/20261016_remove_mentor_subject_manage_allocation.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_remove_mentor_subject_manage_allocation.sql) | Migration deleting role 4 Subject Management allocation from `st_menu_allocation_master`. |
| [`tests/test_batch2_remediation.php`](file:///c:/xampp/htdocs/st/tests/test_batch2_remediation.php) | Targeted 20-test suite validating P4, P5, and P10. |
| [`tests/run_all_tests.php`](file:///c:/xampp/htdocs/st/tests/run_all_tests.php) | Registered Batch 2 Remediation Suite in master regression runner. |
