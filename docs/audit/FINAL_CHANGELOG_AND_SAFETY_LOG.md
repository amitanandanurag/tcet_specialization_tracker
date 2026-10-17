# TCET ERP Final Changelog & Safety Verification Log
**Document ID:** FINAL-CHANGELOG-AND-SAFETY-LOG-2026-10-16  
**Execution Timestamp:** 2026-10-16 22:38:00 IST  
**Environment:** PHP 8.2.12 / MariaDB 10.4.32  
**Target Codebase:** `C:\xampp\htdocs\st`  

---

## 1. Complete File Modification Inventory

| File Path | Nature of Change | Architectural Rationale |
|---|---|---|
| [admin/class_crud_new.php](file:///c:/xampp/htdocs/st/admin/class_crud_new.php) | Layout & Grid Refactor | Changed custom field column classes to `col-xs-12 col-sm-6 col-md-6` (2 columns on $\ge 768\text{px}$, 1 column on mobile); added `.erp-form-card`, `.erp-table-card`, live search filtering, and tab auto-scrolling synchronization. |
| [admin/css/erp-theme.css](file:///c:/xampp/htdocs/st/admin/css/erp-theme.css) | Design System & Overflow Rules | Enforced `box-sizing: border-box !important`, `width: 100% !important`, `max-width: 100% !important` on all controls; added flexbox `.erp-form-grid`; suppressed `:before/:after` table pseudo-elements on `.nav-tabs.erp-master-tabs`; added smooth horizontal scroller for 11 master tabs; bounded `.modal-dialog` and `.modal-body`. |
| [database/db_connect.php](file:///c:/xampp/htdocs/st/database/db_connect.php) | DB Connection Sanitization | Added `trim()` to `$dbName` parameter and `getenv('TCET_DB_NAME')` to prevent trailing whitespace from breaking CLI MariaDB connection strings. |
| [tests/run_all_tests.php](file:///c:/xampp/htdocs/st/tests/run_all_tests.php) | Test Isolation Enforcement | Explicitly set `TCET_DB_NAME=tcet_st_test` and `TCET_TEST_DB=tcet_st_test` for all 11 test subprocesses to guarantee reference database `tcet_st` remains untouched. |
| [login/forgot_password.php](file:///c:/xampp/htdocs/st/login/forgot_password.php) | Security Hardening (P1) | Secure SHA-256 token hashing, 15-minute expiry, `FOR UPDATE` transaction concurrency lock, rate limiting (5 req/15 min), account enumeration protection, bcrypt password hashing. |
| [admin/nptel_certificate.php](file:///c:/xampp/htdocs/st/admin/nptel_certificate.php) | Security Hardening (P2) | Integrated cancellation workflow with `st_nptel_cancellations` table, prepared statements, role authorization, and atomic rollback. |
| [admin/student_process.php](file:///c:/xampp/htdocs/st/admin/student_process.php) | Bug Remediation (P3) | Fixed admin user overwrite defect by scoping self-link student updates strictly to `role_id === 5` (Student). |
| [login/student_register.php](file:///c:/xampp/htdocs/st/login/student_register.php) | Security Hardening (P5) | Replaced legacy plaintext password insertion with `DBController::hashPassword()`. |
| [admin/header/side_menu.php](file:///c:/xampp/htdocs/st/admin/header/side_menu.php) | Navigation Restriction (P10) | Removed unauthorized Subject Management navigation allocations for faculty mentors. |
| [admin/dashboard.php](file:///c:/xampp/htdocs/st/admin/dashboard.php) | Metric Accuracy | Standardized KPI counts for active students (`status = 1`), user totals from `st_user_master`, department scoping, and 4 degree track filtering. |

---

## 2. Safety Invariants & Verification Checklist

- [x] **Reference Database Read-Only:** Verified `tcet_st` was accessed via SELECT queries only during diagnostic audits. Record count remained exactly 651 throughout testing.
- [x] **Isolated Test Database:** All automated test executions and mock student insertions targeted `tcet_st_test`.
- [x] **No Unapproved Migrations:** No migration was executed against `tcet_st`.
- [x] **No Git Push or Remote Deployment:** Branch `ashutosh` remains local and uncommitted. Zero commits or pushes to GitHub.
- [x] **No Data Destruction:** Zero student records truncated, deleted, or bulk-updated to artificially match dashboard figures.
- [x] **No Plaintext Passwords or Secret Leaks:** Verified all audit reports contain sanitized aggregates only; no passwords, tokens, or PII exposed.
- [x] **Preserved Core Architecture:** Procedural PHP 8.2, MariaDB, AdminLTE, and Bootstrap 3 architecture preserved without framework changes.

---

## 3. Backup Manifest & Recovery Instructions

**Backup Directory:** `C:\xampp\st_backups\st_final_verification_20261016_223120`

### Artifacts:
1. `code/` — Complete application codebase snapshot.
2. `tcet_st.sql` — Logical dump of reference database `tcet_st` (647,290 bytes).
3. `tcet_st_test.sql` — Logical dump of test database `tcet_st_test` (604,224 bytes).

### Restoration Procedure:
To restore source code:
```powershell
robocopy "C:\xampp\st_backups\st_final_verification_20261016_223120\code" "C:\xampp\htdocs\st" /E
```
To restore test database:
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root tcet_st_test < "C:\xampp\st_backups\st_final_verification_20261016_223120\tcet_st_test.sql"
```
