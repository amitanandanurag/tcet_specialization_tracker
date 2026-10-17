# TCET Specialization Tracker — Dashboard Database Safety Log

**Date:** 2026-10-16  
**Safety Protocol Version:** 2.0  
**Host:** `localhost` | **Port:** 3306 | **Engine:** MariaDB 10.4.32  

---

## 1. Safety Confirmations & Commitments

1. **Production Data Protection:**
   - No records in `tcet_st` were truncated, deleted, modified, or updated to force dashboard numbers to match.
   - The distinction between 55 active students and 630 applications was verified as mathematically correct without data manipulation.
2. **Target Test Database:**
   - Automated test suites ran against `tcet_st_test` via environment variable `TCET_DB_NAME=tcet_st_test`.
3. **No Schema Changes or Drift:**
   - No database schema migrations were required for this dashboard UI and query accuracy remediation.
4. **Git & Deployment Integrity:**
   - No `git push` or deployment was performed. Working tree changes are localized to `admin/dashboard.php`, `tests/test_dashboard_accuracy.php`, `tests/run_all_tests.php`, and audit reports in `docs/audit/`.

---

## 2. Backup Verification

Fresh timestamped source and database backups were generated prior to applying code edits:

- **Source Code Backup Directory:**
  `C:\xampp\st_backups\st_backup_dashboard_fix_20261016_214240`
- **Database Dump Files:**
  - Reference Database Dump:
    `C:\xampp\st_backups\st_backup_dashboard_fix_20261016_214240\tcet_st_backup.sql` (618,254 bytes)
  - Isolated Test Database Dump:
    `C:\xampp\st_backups\st_backup_dashboard_fix_20261016_214240\tcet_st_test_backup.sql` (599,468 bytes)

Both backup files were verified for non-zero file sizes and valid SQL headers.

---

## 3. Database State Comparison

| Table Name | `tcet_st` Record Count | `tcet_st_test` Record Count | Status |
| :--- | :--- | :--- | :--- |
| `st_student_master` | 638 | 638 | Identical baseline records |
| `st_student_master (status=1)` | 55 | 50 | Verified active students |
| `st_specialization_master` | 25 | 25 | Identical (4 tracks, 21 subjects) |
| `st_department_master` | 15 | 15 | Identical 15 departments |
| `st_user_master` | 12 | 14 | Test user additions in test DB |
| `st_password_resets` | Present | Present | Batch 1 migration intact |
| `st_nptel_cancellations` | Present | Present | Batch 1 migration intact |
