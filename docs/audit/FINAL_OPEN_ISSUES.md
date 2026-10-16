# TCET ERP Final Open Issues & Recommendations Report
**Document ID:** FINAL-OPEN-ISSUES-2026-10-16  
**Execution Timestamp:** 2026-10-16 22:38:00 IST  
**Environment:** PHP 8.2.12 / MariaDB 10.4.32  
**Target Codebase:** `C:\xampp\htdocs\st`  

---

## 1. Overview of Open Findings

All Critical (P1, P2, P3) and High-Priority (P4, P5, P10) security vulnerabilities, dashboard data defects, and Specialization Subject UI layout defects have been verified and remediated.

The remaining open items below represent non-blocking architectural enhancements, technical debt, and environment configurations recommended for subsequent planned releases.

---

## 2. Open Issues Inventory

### Issue 1: Overloaded `status` Column Semantics
- **Severity:** Medium
- **Component:** Database Schema (`st_student_master`)
- **Description:** In the legacy schema, `st_student_master.status` is used both as a student account lifecycle flag (`status = 1` for Active, `status = 0` for Deactivated) and as an application review status (`status = 1` for Approved, `status = 0` for Pending Review).
- **Impact:** 587 legacy rows imported during system inception carry `status = 0`, causing them to appear as "Pending" in the Specialization Application Tracking table while being "Inactive" in general rosters.
- **Safe Recommendation:** In a future planned migration, introduce an explicit column `application_status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING'` on specialization registration tables, separating application workflow from student account activation. Do not execute a bulk conversion on legacy data without academic committee sign-off.

### Issue 2: End-to-End SMTP Mail Delivery on Localhost
- **Severity:** Low (Environment)
- **Component:** [login/forgot_password.php](file:///c:/xampp/htdocs/st/login/forgot_password.php)
- **Description:** The password reset token lifecycle, SHA-256 hashing, rate limiting, and generic response are fully functional. However, standard XAMPP localhost does not have an active SMTP transport configured, meaning `@mail()` does not deliver real external emails in local testing.
- **Impact:** Real emails cannot be received in localhost development without configuring `sendmail.ini` / PHPMailer SMTP credentials.
- **Safe Recommendation:** Configure PHPMailer or institutional SMTP relay credentials (`smtp.tcetmumbai.in` / TLS Port 587) in a secure environment configuration file before production deployment.

### Issue 3: Offline Credit Ledger Caching for Transcripts
- **Severity:** Low (Enhancement)
- **Component:** Academic Progression Engine
- **Description:** Student credits are currently calculated dynamically on every request from `st_student_semester_history` and `st_minorsubject.credits`.
- **Impact:** As historical semesters and student volumes scale into thousands of records, repeated dynamic credit summation may increase query latency on bulk report exports.
- **Safe Recommendation:** Implement a cached `st_student_credit_ledger` summary table updated atomically upon semester finalization.

### Issue 4: Real-Time Push Notification Engine
- **Severity:** Medium (Feature)
- **Component:** Header Notifications
- **Description:** The header navigation displays a notification bell icon and counter badge. Notifications are currently loaded via polling rather than a persistent WebSocket / Server-Sent Events daemon.
- **Impact:** Minor delay in real-time alert updates for students.
- **Safe Recommendation:** Implement a lightweight SSE (Server-Sent Events) endpoint for real-time mentor/coordinator action alerts.

### Issue 5: Legacy Roll Number Duplicates in Reference Data
- **Severity:** Low (Data Hygiene)
- **Component:** Database Data Quality (`tcet_st`)
- **Description:** Diagnostic audit identified 74 distinct roll numbers with duplicates and 2 rows with null/blank roll numbers originating from historical CSV imports.
- **Impact:** Non-unique roll numbers do not affect primary key (`student_id`) operations or login security, but may cause ambiguity in legacy departmental printouts.
- **Safe Recommendation:** Run a registrar-reviewed deduplication pass during semester transition.

---

## 3. Summary of Overall Application Status

| Category | Status | Notes |
|---|---|---|
| **Master Data UI & Specialization Subject** | **PASS** | 2-column grid, bounded 1050px card, no overflow across 5 viewports |
| **Dashboard Metrics Accuracy** | **PASS** | Reconciled with mathematical invariants holding 100% |
| **Batch 1 Security (P1, P2, P3)** | **PASS** | Password resets, NPTEL cancellation, admission overwrite verified |
| **Batch 2 Security (P4, P5, P10)** | **PASS** | Status handling, password hashing, mentor RBAC verified |
| **Regression Test Suite** | **PASS** | 300 / 300 tests passed against isolated `tcet_st_test` |
| **Production Safety Gate** | **PASS** | `tcet_st` untouched, zero commits/pushes, full backups verified |
| **Overall Release Verdict** | **PASS WITH DOCUMENTED OPEN ENHANCEMENTS** | Ready for stakeholder review and deployment authorization |
