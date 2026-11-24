# TESTING GUIDE — TCET SPECIALIZATION TRACKER
**Application:** TCET Specialization Tracker (TCET_ST)  
**Scope:** Automated Test Suites, Multi-Role Regression, Concurrency, and Browser UAT  
**Version:** 3.2.0  

---

## 1. Testing Framework Overview

The TCET Specialization Tracker employs a multi-layered verification strategy:
1. **Database Integrity & Invariant Suites:** Verifies row counts, status distributions, triggers, and foreign key relations.
2. **API & Controller HTTP Suites:** Multi-role HTTP probes verifying session gating, CSRF tokens, and DataTables AJAX responses.
3. **End-to-End Browser UAT:** Puppeteer headless automation validating DOM rendering, client view modals, mobile responsiveness, and CSS overflow.

---

## 2. Core Automated Test Suites

### 2.1 Database Integrity Post-Redesign Test
- **Script:** `scratch/verify_db_integrity_post_redesign.php`
- **Execution:**
  ```cmd
  php scratch/verify_db_integrity_post_redesign.php
  ```
- **Assertions Verified:**
  - Runtime database is strictly `tcet_st`.
  - Exactly 878 historical candidates exist in `st_historical_candidates`.
  - Approval breakdown matches canonical baseline: `HOLD: 573`, `PENDING: 283`, `CONDITIONAL: 22`, `APPROVED: 0`, `REJECTED: 0`.
  - Exactly 26 audit records in `st_historical_candidate_audit` spanning `hist_audit_id` 5 through 30.
  - Active immutability triggers (`trg_hist_audit_no_update`, `trg_hist_audit_no_delete`) block updates/deletes with error code `45000`.

### 2.2 Clean Application Regression Suite
- **Script:** `scratch/test_clean_app.php`
- **Execution:**
  ```cmd
  php scratch/test_clean_app.php
  ```
- **Assertions Verified:**
  - Login session creation and CSRF validation.
  - `admin/student-info.php` and `admin/student_info_ajax.php` return active student rows (`draw=1`).
  - `admin/historical_students.php` and `historical_students_ajax.php` return 878 records.
  - Candidate inspection endpoint delivers sanitized HTML without fatal errors.
  - Export service produces valid CSV headers.

### 2.3 Headless Browser & UI Redesign Test
- **Script:** `scratch/test_redesign_and_security.js`
- **Execution:**
  ```cmd
  node scratch/test_redesign_and_security.js
  ```
- **Assertions Verified:**
  - Client-facing candidate modal opens and displays no internal audit data.
  - Restricted audit view (`&section=restricted_audit`) loads audit timeline exclusively for authorized sessions.
  - Responsive layout renders correctly at viewports: 1920x1080 (Desktop), 1366x768 (Laptop), 1024x768 (Tablet), 768x1024 (Tablet portrait), 390x844 (Mobile).
  - Zero horizontal overflow (`scrollWidth <= clientWidth`).
  - Zero browser console errors.

---

## 3. Regression Testing Protocol for Releases

Before deploying any future update:
1. Ensure MySQL/Apache services are running.
2. Execute the database integrity suite: confirm 0 failures.
3. Execute the HTTP regression suite: confirm 0 failures.
4. Execute the browser verification suite: confirm 0 failures.
5. Inspect Apache error logs (`C:\xampp\apache\logs\error.log`) to confirm zero PHP warnings or deprecation notices.
