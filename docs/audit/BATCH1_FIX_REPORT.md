# TCET Specialization Tracker — Batch 1 Critical Fixes Report

## 1. Overview

This document records the root cause, remediation details, before-and-after behavior, modified files, and verification evidence for Batch 1 Critical Fixes (P1, P2, and P3).

---

## 2. Issue P1: Insecure Password Reset Workflow

- **Severity**: **CRITICAL**
- **Affected File**: [`login/forgot_password.php`](file:///c:/xampp/htdocs/st/login/forgot_password.php)
- **Supporting Schema**: [`database/migrations/20261016_password_resets.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_password_resets.sql)

### 2.1 Root Cause
1. `forgot_password.php` immediately executed `UPDATE st_login SET password='Tcet@1234' WHERE user_id='$user_id'` upon receiving any registered email.
2. The hardcoded universal password `Tcet@1234` was printed directly on the client's screen.
3. No identity verification, secret token, expiry, rate limiting, or CSRF protection existed.
4. Any unauthenticated visitor could hijack any student or administrator account by entering their email address.

### 2.2 Implemented Fix
1. **Removed Universal Password**: Removed hardcoded `Tcet@1234` reset and on-screen password exposure.
2. **Cryptographic Token Lifecycle**:
   - Generates 32-byte cryptographic random token (`bin2hex(random_bytes(32))`).
   - Stores only SHA-256 hash (`hash('sha256', $token)`) in `st_password_resets`.
   - Strictly enforces 15-minute expiration (`DATE_ADD(NOW(), INTERVAL 15 MINUTE)`).
   - Enforces single-use consumption (`consumed_at = NOW()`) with transaction row locking (`FOR UPDATE`) to prevent concurrent reuse.
3. **Account Enumeration Defense**:
   - Returns a universal generic success response for all valid email submissions regardless of whether the account exists in the database.
4. **Rate Limiting & Validation**:
   - Maximum 5 reset requests per 15-minute window per IP address.
   - Enforces `@tcetmumbai.in` email domain restriction.
   - Requires valid CSRF token (`DBController::validateCsrfToken()`).
5. **Secure Password Update**:
   - When user clicks reset link with valid token, renders a secure "Set New Password" form.
   - Enforces minimum 8 characters, uppercase, lowercase, and digits.
   - Hashes password using bcrypt via `DBController::hashPassword()`.
   - Records audit event `PASSWORD_RESET_COMPLETED`.

---

## 3. Issue P2: Missing NPTEL Cancellation Table

- **Severity**: **CRITICAL**
- **Affected File**: [`admin/nptel_certificate.php`](file:///c:/xampp/htdocs/st/admin/nptel_certificate.php)
- **Supporting Schema**: [`database/migrations/20261016_nptel_cancellations.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_nptel_cancellations.sql)
- **Database Helper**: [`database/db_connect.php`](file:///c:/xampp/htdocs/st/database/db_connect.php) (`ensureNptelCancellationTable`)

### 3.1 Root Cause
`admin/nptel_certificate.php` executed `INSERT INTO st_nptel_cancellations` and `SELECT ... FROM st_nptel_cancellations`, but table `st_nptel_cancellations` was never created in `tcet_st.sql` or subsequent migrations. When a student selected "Fail" and submitted cancellation, the application crashed with an unhandled MySQL error (`Table 'tcet_st.st_nptel_cancellations' doesn't exist`).

### 3.2 Implemented Fix
1. **Database Migration**: Created `database/migrations/20261016_nptel_cancellations.sql` containing primary key `cancellation_id`, foreign keys to `st_student_master`, nullable `enrollment_id`, course name, reason, status, and submission timestamp.
2. **Auto-Ensure Helper**: Added `DBController::ensureNptelCancellationTable()` to ensure zero runtime crashes across environments.
3. **Atomic Transaction & Prepared Statements**:
   - Wrapped cancellation insertion and `st_offline_marks_entry` update in an atomic transaction (`mysqli_begin_transaction`).
   - Replaced string concatenation with parameterized prepared statements (`mysqli_prepare`, `mysqli_stmt_bind_param`).
   - If an error occurs, the transaction rolls back cleanly, and safe non-disclosing error messages are displayed.
4. **Student Isolation**: Parameterized cancellation query by student ID (`WHERE student_id = ?`) preventing students from viewing other students' cancellations.

---

## 4. Issue P3: Student Admission Overwrite Bug

- **Severity**: **HIGH**
- **Affected Files**:
  - [`admin/student_process.php`](file:///c:/xampp/htdocs/st/admin/student_process.php)
  - [`admin/student_admission.php`](file:///c:/xampp/htdocs/st/admin/student_admission.php)

### 4.1 Root Cause
1. `student_process.php` ran `UPDATE st_user_master SET student_id = $student_id WHERE user_id = $userid` on every admission. When an administrator or coordinator created a student, their own `student_id` in `st_user_master` was set to the newly created student's ID.
2. On subsequent admissions by the same administrator, lines 310-317 checked `SELECT student_id FROM st_user_master WHERE user_id = $userid` and retrieved the previously admitted student's ID as `$existingStudentId`.
3. Consequently, the script entered the `UPDATE st_student_master` branch instead of `INSERT`, overwriting the first student's data with the new student's data.

### 4.2 Implemented Fix
1. **Decoupled Administrator Identity**:
   - Explicitly retrieved `$userRole` from session.
   - Enforced authentication: if `$userid <= 0`, rejected with 401 Unauthorized.
   - Enforced RBAC: only roles 1, 2, 3, and 5 can submit admission forms.
2. **Role-Aware Existing Student Lookup**:
   - For student self-registration (`$userRole === 5`), looks up `student_id` in `st_user_master` where `role_id = 5`.
   - For administrators and coordinators (`$userRole < 5`), `$existingStudentId` is only set if an explicit `edit_student_id` is passed from an edit form. Normal admissions always set `$existingStudentId = 0`, ensuring a fresh `INSERT`.
3. **Protected Administrator Account**:
   - Updated lines 484-488: `st_user_master.student_id` is ONLY updated if `$userRole === 5`.
   - The logged-in administrator's or coordinator's record is NEVER modified.
   - If admitted by admin and an unlinked student user account matching the student's email exists, it links that student user account instead.
4. **Safe Error Handling**: Leaking raw SQL queries and MySQL errors was replaced with `error_log` and user-friendly error dialogs.
5. **Admission Form Prefill Isolation**: In `student_admission.php`, prefilling from `st_user_master` is strictly restricted to `$usertype === 5`.

---

## 5. Modified Files Summary

| File | Purpose of Modification |
| :--- | :--- |
| [`database/db_connect.php`](file:///c:/xampp/htdocs/st/database/db_connect.php) | Added dynamic database parameter in constructor (`TCET_DB_NAME`); added `ensurePasswordResetTable()` and `ensureNptelCancellationTable()`. |
| [`login/forgot_password.php`](file:///c:/xampp/htdocs/st/login/forgot_password.php) | Completely secured password reset with single-use SHA-256 tokens, 15-minute expiry, rate limiting, generic responses, CSRF, and bcrypt hashing. |
| [`admin/nptel_certificate.php`](file:///c:/xampp/htdocs/st/admin/nptel_certificate.php) | Added cancellation table ensure, atomic transaction rollback, parameterized prepared statements, and student isolation. |
| [`admin/student_process.php`](file:///c:/xampp/htdocs/st/admin/student_process.php) | Decoupled admin from student identity, prevented student ID assignment to admins, prevented sequential admission overwrites, and secured error output. |
| [`admin/student_admission.php`](file:///c:/xampp/htdocs/st/admin/student_admission.php) | Isolated student prefill to role 5 and passed explicit `edit_student_id` only in edit mode. |
| [`database/migrations/20261016_password_resets.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_password_resets.sql) | DDL migration for `st_password_resets` table. |
| [`database/migrations/20261016_nptel_cancellations.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_nptel_cancellations.sql) | DDL migration for `st_nptel_cancellations` table. |
| [`tests/test_batch1_critical_fixes.php`](file:///c:/xampp/htdocs/st/tests/test_batch1_critical_fixes.php) | 20 targeted automated regression tests covering P1, P2, and P3 fixes. |
| [`tests/run_all_tests.php`](file:///c:/xampp/htdocs/st/tests/run_all_tests.php) | Registered Batch 1 suite into master regression test runner. |

---

## 6. Verification and Regression Sign-Off

- **Baseline Tests (Pre-Fix)**: 240 / 240 Passing.
- **Post-Fix Tests**: **260 / 260 Passing (0 Failing)** across 9 test suites.
- **PHP Syntax Check**: **136 / 136 files Passing**.
- **Data Protection**: Verified zero modifications to existing production student records; working tree clean and ready for user review.
