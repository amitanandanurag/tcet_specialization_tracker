# TCET Specialization Tracker — Batch 1 Backup and Safety Log

**Date/Time**: 2026-10-16 17:20:27 IST  
**Environment**: Local Development (XAMPP / Apache / MariaDB)  
**Host**: localhost  
**Active Working Database**: `tcet_st`  
**Isolated Test Database**: `tcet_st_test`  

---

## 1. Safety Checklist Verification

| Safety Rule | Status | Evidence / Verification |
| :--- | :---: | :--- |
| **Project Root Confirmed** | VERIFIED | `C:\xampp\htdocs\st` |
| **Git Status & Working Tree** | VERIFIED | Clean working tree on branch `ashutosh`, tracking `origin/ashutosh`. No uncommitted user changes existed before starting. |
| **Project Files Backup Created** | VERIFIED | Stored outside web-accessible directory: `C:\xampp\st_backups\st_backup_20261016_172027` |
| **Database Backup Created** | VERIFIED | Full UTF-8 dump: `C:\xampp\st_backups\st_backup_20261016_172027\tcet_st_backup_utf8.sql` (590,500 bytes) |
| **Backup Integrity & Readability** | VERIFIED | Verified readable with MariaDB header and table schemas. |
| **Isolated Test DB Created** | VERIFIED | `tcet_st_test` created and seeded from verified backup. |
| **Database Isolation Guard** | VERIFIED | `tcet_st_test` and `tcet_st` confirmed distinct. All migrations and test executions targeted `tcet_st_test`. |
| **Production Protection** | VERIFIED | No live student records modified. No production publishing or Git push performed. |

---

## 2. Backup Metadata and File Locations

### 2.1 File System Backup
- **Source Directory**: `c:\xampp\htdocs\st`
- **Destination Path**: `C:\xampp\st_backups\st_backup_20261016_172027`
- **Exclusions**: `.git` directory preserved in working directory only.
- **Verification Command**:
  ```powershell
  Get-Item "C:\xampp\st_backups\st_backup_20261016_172027"
  ```

### 2.2 Database Backup
- **Source Database**: `tcet_st`
- **Dump Tool**: `C:\xampp\mysql\bin\mysqldump.exe`
- **Options Used**: `--routines --triggers --events --result-file=...`
- **Backup File Path**: `C:\xampp\st_backups\st_backup_20261016_172027\tcet_st_backup_utf8.sql`
- **Size**: 590,500 bytes
- **Table Count**: 31 tables

---

## 3. Test Database Isolation

- **Test Database Name**: `tcet_st_test`
- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_general_ci`
- **Creation Command**:
  ```sql
  DROP DATABASE IF EXISTS tcet_st_test;
  CREATE DATABASE tcet_st_test CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
  ```
- **Seeding Source**: `C:\xampp\st_backups\st_backup_20261016_172027\tcet_st_backup_utf8.sql`
- **Verification**: Database controller parameterized to connect via `new DBController('tcet_st_test')` or environment variable `TCET_TEST_DB=tcet_st_test`.
