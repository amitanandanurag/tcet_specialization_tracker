# TCET ERP Final Security & Workflow Recheck Report
**Document ID:** FINAL-SECURITY-RECHECK-2026-10-16  
**Execution Timestamp:** 2026-10-16 22:38:00 IST  
**Environment:** PHP 8.2.12 / MariaDB 10.4.32  
**Target Codebase:** `C:\xampp\htdocs\st`  

---

## 1. Security Recheck by Domain

### D1. Insecure Password Reset (P1 Remediation)
*Target File:* [login/forgot_password.php](file:///c:/xampp/htdocs/st/login/forgot_password.php)
- **Token Generation:** Uses cryptographically secure `bin2hex(random_bytes(32))`. **PASS**
- **Token Storage:** Only SHA-256 hashes (`$tokenHash = hash('sha256', $plainToken);`) are stored in `st_password_resets`. Raw tokens are never persisted. **PASS**
- **Token Expiry & Replay Prevention:** Enforces 15-minute validity (`expires_at > NOW()`). Upon redemption, `consumed_at = NOW()` is written immediately inside a transaction with `FOR UPDATE` row locking to prevent race conditions. **PASS**
- **Account Enumeration Protection:** Form displays a universal generic confirmation: *"If the provided email is registered in our system, password reset instructions have been sent."* whether the user exists or not. **PASS**
- **Rate Limiting & CSRF:** Limits requests to a maximum of 5 requests per IP address per 15 minutes. CSRF token validated on submission. **PASS**
- **Password Storage:** Submissions are validated for complexity ($\ge 8$ chars, uppercase, lowercase, number) and hashed using `DBController::hashPassword()` (`PASSWORD_BCRYPT`). **PASS**
- **Email Delivery Status:** Local development environment does not have a live SMTP mail server configured. `mail()` call succeeds locally or fails silently. **Status: BLOCKED (End-to-End SMTP delivery not configured on localhost). Core token workflow verified.**

---

### D2. NPTEL Certificate Cancellation Workflow (P2 Remediation)
*Target Files:* [admin/nptel_certificate.php](file:///c:/xampp/htdocs/st/admin/nptel_certificate.php), [database/migrations/20261016_nptel_cancellations.sql](file:///c:/xampp/htdocs/st/database/migrations/20261016_nptel_cancellations.sql)
- **Schema Presence:** Table `st_nptel_cancellations` verified in test database with proper foreign keys (`student_id`, `cancelled_by`, `reason`, `cancelled_at`). **PASS**
- **Prepared Statements & Transactions:** All cancellation inserts and status updates use `mysqli_prepare()` and `mysqli_begin_transaction()`. **PASS**
- **Student Authorization Isolation:** Students (`role_id = 5`) are strictly restricted to cancelling their own certificate requests (`WHERE student_id = logged_student_id`). Administrative roles can cancel on behalf of students with an audit reason. **PASS**
- **Atomic Rollback:** Failure during certificate status modification automatically triggers `mysqli_rollback()`. **PASS**

---

### D3. Student Admission & Registration Overwrite Bug (P3 & P5 Remediation)
*Target Files:* [admin/student_process.php](file:///c:/xampp/htdocs/st/admin/student_process.php), [login/student_register.php](file:///c:/xampp/htdocs/st/login/student_register.php)
- **Admin Overwrite Bug (P3):** Previously, an admin creating a student record could overwrite their own linked student profile if `$user_id` was shared. Now, `student_process.php` restricts self-link updates strictly to `role_id === 5` (Student). For admins and coordinators, an update is executed only if an explicit `edit_student_id` is passed. **PASS**
- **Registration Number Uniqueness:** Verified server-side check prevents duplicate registration numbers on both insert and edit. **PASS**
- **Plaintext Password Elimination (P5):** In [student_register.php](file:///c:/xampp/htdocs/st/login/student_register.php), student passwords are now always hashed with `DBController::hashPassword()` before insertion into `st_user_master` and `st_login`. No plaintext passwords appear in HTML, logs, sessions, or URLs. **PASS**

---

### D4. Student Status & Role Access Control (P4 & P10 Remediation)
*Target Files:* [admin/header/side_menu.php](file:///c:/xampp/htdocs/st/admin/header/side_menu.php), [admin/header/header.php](file:///c:/xampp/htdocs/st/admin/header/header.php), [admin/student_delete.php](file:///c:/xampp/htdocs/st/admin/student_delete.php)
- **Status Consistency (P4):** Deactivation (`status = 0`) and reactivation (`status = 1`) update both `st_student_master` and linked `st_user_master` accounts consistently. Legacy records are not modified in bulk. **PASS**
- **Mentor Access Restriction (P10):** Mentors (`role_id = 4`) previously had invalid allocations to Subject Management. Allocation removed via migration. Route-level guard in [header.php](file:///c:/xampp/htdocs/st/admin/header/header.php) (`checkUserRouteAuthorization()`) blocks mentors from directly requesting `class_crud_new.php` or `class_manage.php` via URL (returns HTTP 403 Access Denied). **PASS**

---

### D5. Credit Ledger & Remaining Audit Findings
Review of unresolved audit items:
1. **Credit Ledger Calculation:** Academic credits are computed dynamically via `st_student_semester_history` and `st_minorsubject.credits`. A dedicated offline calculation job or ledger caching table is recommended for institutional transcript generation in a future release. **Status: OPEN (Low Severity / Enhancement)**
2. **In-App Notifications:** Real-time bell notification counter exists in header; push notification daemon is not implemented. **Status: OPEN (Medium Severity / Feature)**
3. **Certificate Verification Workflow:** NPTEL certificate upload and marks verification workflow is functional; external NPTEL API automated verification is not present (manual verification by coordinator). **Status: OPEN (Low Severity / Feature)**
4. **Enrollment Table Usage:** Specialization mappings are tracked in `st_student_master` and `st_student_semester_history`. The legacy `st_enrollment` table contains orphan historical rows. Migration recommended for Phase 3. **Status: OPEN (Medium Severity / Technical Debt)**
