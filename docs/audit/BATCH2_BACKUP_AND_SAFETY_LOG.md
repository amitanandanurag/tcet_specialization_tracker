# TCET Specialization Tracker — Batch 2 Backup and Safety Log

## 1. Baseline Verification

- **Repository Root**: `c:\xampp\htdocs\st`
- **Active Git Branch**: `ashutosh`
- **Working Tree State at Start**: Clean working tree with Batch 1 changes preserved.
- **Git Push / Deployment**: Strictly forbidden and not performed.
- **PHP Version**: PHP 8.2 (CLI)

---

## 2. Backup Inventory

Prior to initiating any file or database modifications for Batch 2, verified full backups were established outside of `htdocs`:

### 2.1 Filesystem Snapshot
- **Destination Path**: `C:\xampp\st_backups\st_backup_batch2_20261016_173801`
- **Timestamp**: `2026-10-16 17:38:01`
- **Source**: `c:\xampp\htdocs\st`
- **Contents**: Complete code snapshot preserving all configurations, vendor assets, and migrations.

### 2.2 Database Snapshot
- **Destination File**: `C:\xampp\st_backups\st_backup_batch2_20261016_173801\tcet_st_backup_batch2_20261016_173801.sql`
- **Database Dumped**: `tcet_st`
- **File Size**: 298,803 bytes
- **Verification**: SQL structure validated; contains complete schema and data tables (`st_student_master`, `st_login`, `st_user_master`, `st_menu_allocation_master`, `st_nptel_cancellations`, `st_password_resets`, etc.).

---

## 3. Database Safety Measures

1. **Isolation of Operations**:
   - Every database modification and test fixture was executed exclusively against `tcet_st_test`.
   - Before executing migration `20261016_remove_mentor_subject_manage_allocation.sql`, verified `SELECT DATABASE() = 'tcet_st_test'`.
2. **Environment Variable Enforcement**:
   - `TCET_DB_NAME=tcet_st_test` was passed during test suite runs to prevent tests from modifying `tcet_st`.
3. **Synthetic Data Hygiene**:
   - Test suites remove all created mock students, mentor mappings, and login credentials at the conclusion of test runs (`CLEAN-T1` verification passed).
4. **No Blanket Database Conversions**:
   - In accordance with safety requirements, legacy student rows in `st_student_master` were not modified en masse.
   - Legitimate historical data remains intact.

---

## 4. Verification Checkpoints

| Checkpoint | Command Executed | Result |
| :--- | :--- | :---: |
| Baseline Syntax Lint | `C:\xampp\php\php.exe tests\lint_all.php` | 136 / 136 files passed |
| Baseline Master Tests | `C:\xampp\php\php.exe tests\run_all_tests.php` | 260 / 260 passed |
| Post-Remediation Lint | `C:\xampp\php\php.exe tests\lint_all.php` | 137 / 137 files passed |
| Post-Remediation Tests | `C:\xampp\php\php.exe tests\run_all_tests.php` | 280 / 280 passed |
