# TCET ERP — Final Open Issues & Architectural Recommendations

**Project:** TCET Specialization Tracker (TCET ERP)  
**Location:** `C:\xampp\htdocs\st`  
**Execution Timestamp:** October 17, 2026 (Local Time)  
**Environment:** PHP 8.2.12 / MariaDB 10.4.32 (XAMPP Port 3306)  
**Release Gate Verdict:** **PASS WITH OPEN ISSUES** (Zero Blocking Issues)  

---

## 1. Overview & Current Status

All Critical and High-Priority requirements for Role-Based Access Control, Data Reconciliation, Sidebar Restructuring, and Student Self-Service have been verified, remediated, and tested with a 100% test pass rate across all 14 suites (428 / 428 assertions passing).

The items documented below represent verified functional workflows, non-blocking technical debt, legacy data observations, and architectural recommendations for future development cycles. None of these items block local release or staging verification.

---

## 2. Assessment of Key Workflows

### 2.1 Credit Ledger Engine
- **Status:** **VERIFIED**
- **Component:** `admin/student_monitor_ajax.php`, `admin/offline_marks_student_ajax.php`
- **Evidence:** The credit ledger correctly queries `st_credit_ledger` (and falls back to `st_minorsubject.credits` when ledger entries are not yet recorded) to display credits earned per semester.
- **Long-Term Recommendation:** Implement an automated batch job to populate `st_credit_ledger` upon formal end-of-semester exam finalization.

### 2.2 Certificate Verification Workflow
- **Status:** **VERIFIED**
- **Component:** `admin/nptel_certificate.php`
- **Evidence:** Supports student certificate upload (PDF/JPG/PNG max 2MB) with course name, exam score, and completion date. Verification status ('Pending', 'Approved', 'Rejected') is tracked and displayed to students with direct link to view the uploaded file.
- **Long-Term Recommendation:** Add email alerts to department coordinators when new certificates are pending review.

### 2.3 In-App Notifications
- **Status:** **OPEN (Non-Blocking Enhancement)**
- **Component:** `admin/header/header.php`
- **Evidence:** The top navigation bar contains a notification bell icon and counter badge. In the current AdminLTE template, this uses client-side markup and is not yet wired to a live backend WebSocket or SSE daemon.
- **Long-Term Recommendation:** Implement a lightweight Server-Sent Events (SSE) or polling endpoint querying `st_audit_log` or a dedicated `st_notification` table for real-time alerts.

---

## 3. Inventory of Remaining Non-Blocking Issues

### Issue 1: Missing Schema Defaults for `m_sem1`, `m_sem2`, `m_sem3`
- **Severity:** Low (Architectural Resilience)
- **Component:** `st_student_master`
- **Description:** Columns `m_sem1`, `m_sem2`, and `m_sem3` are declared as `text NOT NULL` without a `DEFAULT` clause. In the application code (`admin/student_process.php` and `admin/student.php`), these fields are populated with `'[]'`. However, external manual SQL queries or older test scripts that omit these columns trigger MySQL strict-mode errors.
- **Safe Recommendation:** Execute a schema migration:
  ```sql
  ALTER TABLE st_student_master 
      MODIFY m_sem1 TEXT NOT NULL DEFAULT '[]', 
      MODIFY m_sem2 TEXT NOT NULL DEFAULT '[]', 
      MODIFY m_sem3 TEXT NOT NULL DEFAULT '[]';
  ```

### Issue 2: Overloaded `status` Column Semantics (587 Legacy Registrations)
- **Severity:** Medium (Data Governance)
- **Component:** `st_student_master.status`
- **Description:** In the legacy database `tcet_st`, `status` is used both for student enrollment status (Active `1` vs Inactive `0`) and application approval (Approved `1` vs Pending `0`). There are 587 historical student records with `status = 0`.
- **Mitigation Implemented:** Dashboard KPI cards now transparently present `64 Enrolled (651 Registered)` rather than an unexplained single count.
- **Administrative Action:** Institution admission coordinators should conduct a formal administrative review to approve, graduate, or archive historical inactive cohorts. Do NOT bulk-modify records without institutional sign-off.

### Issue 3: Localhost SMTP Mail Transport
- **Severity:** Low (Environment)
- **Component:** `login/forgot_password.php`
- **Description:** Password reset tokens, SHA-256 hashing, expiration rules, and CSRF protection are fully functional. However, standard local XAMPP environments do not have an active external SMTP relay configured, meaning local `@mail()` calls do not deliver external emails.
- **Safe Recommendation:** Configure PHPMailer or institutional SMTP relay (`smtp.tcetmumbai.in` / TLS Port 587) in a protected environment config before deploying to production.

### Issue 4: Legacy Roll Number Duplicates
- **Severity:** Low (Data Quality)
- **Component:** `st_student_master.roll_no`
- **Description:** Historical data imports created duplicate or empty roll numbers in a few older cohorts.
- **Impact:** Does not affect primary key (`student_id`) or user authentication.
- **Safe Recommendation:** Department coordinators should review and deduplicate roll numbers during annual cohort promotions.

---

## 4. Summary & Release Gate Recommendation

| Category | Status | Notes |
|---|---|---|
| **Codebase Syntax & Linting** | **PASS** | 140 / 140 PHP files clean (`php -l`) |
| **Regression Test Runner** | **PASS** | 428 / 428 Assertions passed across 14 suites (Exit code 0) |
| **Role-Based Access Control** | **PASS** | 110 / 110 Unit & Live HTTP security assertions passed |
| **Database Isolation & Safety** | **PASS** | `tcet_st` strictly read-only (651 students); `tcet_st_test` isolated |
| **Sidebar & Master Consolidation** | **PASS** | Submenus 51-53 consolidated; Super Admin privileged routes protected |
| **Student Self-Service Portal** | **PASS** | 11 completeness fields; CSRF contact update; sequential progression |
| **Remaining Open Items** | **PASS WITH OPEN ISSUES** | All 4 documented items are non-blocking enhancements/recommendations |

**Final Decision:** **PASS WITH OPEN ISSUES**  
**Action:** All local tests and documentation are complete. Leave working tree uncommitted for user review.
