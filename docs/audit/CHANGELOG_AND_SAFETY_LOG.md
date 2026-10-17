# TCET ERP — Changelog and Safety Log

**Project:** TCET Specialization Tracker (TCET ERP)  
**Location:** `C:\xampp\htdocs\st`  
**Date:** October 17, 2026  
**Safety Status:** ZERO PRODUCTION WRITES — TEST ISOLATION 100% PRESERVED  

---

## 1. Safety Measures & Pre-Change Baseline

Prior to implementing any modifications, all mandatory safety rules were strictly enforced:

### 1.1 Git Working Tree & Branch Verification
- **Branch:** `ashutosh`
- **Latest Commit:** `9c31837`
- **Remote Verification:** Verified tracking against origin repository.
- **Git Push Prohibition:** No remote push or deployment was triggered.

### 1.2 Full External Backup
A full dated directory mirror was created outside the active working tree before modifying any files:
- **Backup Directory:** `C:\xampp\htdocs\st_backup_20261017_phase2`
- **Method:** Robocopy external mirror preserving all timestamps, permissions, and directory trees.
- **Integrity:** Verified complete byte-level parity with baseline.

### 1.3 Database Isolation & Read-Only Guarantee
- **Production Database (`tcet_st`):** Maintained strictly **READ-ONLY**. Zero inserts, updates, deletes, or truncations occurred in `tcet_st`. Verified student count remained constant at 651 throughout the entire engagement.
- **Isolated Test Database (`tcet_st_test`):** All testing, menu seeding, and test mutations were strictly directed to `tcet_st_test` via environment variables `TCET_DB_NAME=tcet_st_test` and `TCET_TEST_DB=tcet_st_test`.

---

## 2. Chronological Log of Source Code Modifications

| # | File Modified | Nature of Change | Purpose / Rationale |
|---|---|---|---|
| 1 | `database/db_connect.php` | Added `getUserScope()`, `canAccessStudent()`, `canAccessDepartment()`, and `getAuthoritativeMetrics()`; updated `runQuery()` to support boolean true returns from non-SELECT queries. | Establishes authoritative centralized permission and metric engine; eliminates SQL errors during audit logging. |
| 2 | `login/login.php` | Updated authentication query to fetch and store `department_id` and `student_id` in `$_SESSION`. | Ensures session variables reflect authoritative user attributes immediately upon login. |
| 3 | `admin/header/header.php` | Synchronized user session variables (`$loginDepartment`, `$loginStudentId`); hardened `checkUserRouteAuthorization` for Super Admin-only routes (`class_crud_new.php`, `audit_log.php`, `allocation_master.php`); added CLI-safe header redirect guards. | Closes direct URL security holes; prevents privilege escalation; enables automated CLI test runner execution. |
| 4 | `admin/header/side_menu.php` | Seed functions strip submenus 51, 52, 53 and remove privileged submenus (13, 25, 49) from non-Super Admin roles; dynamic menu tree filters redundant submenus and pruned empty categories. | Delivers clean, uncluttered, role-tailored sidebar navigation across all five roles. |
| 5 | `admin/dashboard.php` | Replaced fabricated `MOD(d.department_id, 3)` status with authoritative assignment check; wired KPI cards to `getAuthoritativeMetrics()`; added registered count breakdown. | Fixes data discrepancy and transparency on institution dashboard. |
| 6 | `admin/branch_info.php` | Fixed bug in HOD count query (`role_id = 3` instead of `2`); added `total_registered_students`; enforced HOD department scoping. | Corrects branch statistics and eliminates cross-branch data exposure for HODs. |
| 7 | `admin/student_view.php` | Enforced `$db_handle->canAccessStudent($student_id, $userid, $usertype)` returning HTTP 403 on denial; added safe `session_start` check. | Prevents unauthorized viewing of student dossiers via URL parameter tampering. |
| 8 | `admin/student_admission_view.php` | Enforced record-level authorization `$db_handle->canAccessStudent(...)` before rendering admission records. | Stops unauthorized student admission form inspection. |
| 9 | `admin/student_info_ajax.php` | Injected HOD department filter and Mentor assignment filter into server-side query. | Stops AJAX data leakage in student directory DataTables. |
| 10 | `admin/student_concise_details_ajax.php` | Enforced department scoping for HODs and assignment scoping for Mentors. | Restricts concise student report generation to permitted boundaries. |
| 11 | `admin/student_monitor_ajax.php` | Allowed HOD to monitor own department; strictly blocked cross-department monitoring. | Balances departmental oversight with strict institutional privacy. |
| 12 | `admin/mentor_assignment.php` | Allowed Role 4 (Mentor) read-only access to their own student assignments; blocked POST `change_mentor` mutation for Role 4. | Enables mentor self-verification while preventing unauthorized allocation tampering. |
| 13 | `admin/student_dashboard.php` | Integrated Profile Completeness tracker (11 fields); added CSRF-protected contact self-update; added validated sequential semester progression ($N \to N+1$). | Delivers comprehensive student self-service portal with zero administrative privilege leaks. |
| 14 | `tests/test_role_based_access_control.php` | Created comprehensive 81-assertion test suite validating all 10 matrix permissions, data accuracy reconciliation, and self-service workflows. | Automated, repeatable verification proof for all deliverables. |
| 15 | `tests/test_browser_role_sessions.php` | Created 29-assertion HTTP session verification suite simulating Super Admin, Admin, HOD, Mentor, and Student requests against local Apache server (`http://127.0.0.1/st/`). | Proves route authorization, CSRF protection, and session scoping over live HTTP. |
| 16 | `tests/test_academic_progression.php`, `tests/test_final_independent_verification.php`, `tests/test_student_registration_specialization_mapping.php`, `tests/test_honours_with_research_eligibility.php`, `tests/test_batch1_critical_fixes.php`, `tests/test_batch2_remediation.php`, `tests/test_semester_wise_mentor_allocation.php` | Remediated mock student inserts to explicitly include `m_sem1, m_sem2, m_sem3` as `'[]'` (matching production code); enforced `SELECT DATABASE() === 'tcet_st_test'` isolation guard; removed fallback to production db. | Resolves all 23 legacy test fixture failures caused by MySQL strict mode missing default value error. |
| 17 | `tests/run_all_tests.php` | Updated master runner to execute 14 suites (428 total assertions). | Unifies regression pipeline; achieves 428 / 428 passing assertions (100% success rate). |

---

## 3. Database Modifications

- **Production Database (`tcet_st`):** ZERO schema changes, ZERO data mutations. Student count remains strictly 651 throughout all verification.
- **Test Database (`tcet_st_test`):** 
  - Submenus 51, 52, 53 removed from `st_menu_allocation_master` (consolidated into Master ID 13).
  - Privileged submenus 13, 25, 49 removed from non-Super Admin roles in `st_menu_allocation_master`.
  - Added test audit logs and temporary test student fixtures during verification (cleaned up automatically post-test).
  - Test database isolation is strictly verified via programmatic `SELECT DATABASE()` queries in PHP before any operations occur.

---

## 4. Rollback Plan

If reversion of these changes is ever required:
1. **Source Code Rollback:**
   ```bash
   git checkout -- admin/ database/ login/ tests/
   ```
   Or restore directly from external mirror:
   `C:\xampp\htdocs\st_backup_20261017_phase2`
2. **Database State:**
   Production database `tcet_st` was never modified, so no database rollback is needed.
