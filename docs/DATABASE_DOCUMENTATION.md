# DATABASE DOCUMENTATION — TCET_ST
**Database Name:** `tcet_st`  
**Engine:** MariaDB 10.4.32-MariaDB / InnoDB  
**Collation:** `utf8mb4_unicode_ci`  
**Authoritative Status:** Locked Production Application Database  

---

## 1. Schema Overview

The `tcet_st` database serves as the single source of truth for the TCET Specialization Tracker. It is partitioned logically into:
1. **Core Active Academic Tables:** Active student enrollment, semester performance, and specializations.
2. **Historical Candidate Repository:** Pre-admission and historical records pending college council regularization.
3. **Audit & Compliance Tables:** Append-only transaction and status change logs.
4. **Security & Configuration Tables:** User accounts, roles, departments, and academic batches.

---

## 2. Table Catalog & Specifications

### 2.1 Active Student Tables

#### `st_student_master`
Stores primary profile records for all currently enrolled students.
| Column | Type | Nullable | Description |
|---|---|---|---|
| `student_id` | `INT(11) AUTO_INCREMENT` | NO | Primary Key |
| `erp_id` | `VARCHAR(50)` | NO | Unique College ERP Identification Number |
| `first_name` | `VARCHAR(100)` | NO | Student First Name |
| `last_name` | `VARCHAR(100)` | NO | Student Surname |
| `branch` | `VARCHAR(50)` | NO | Academic Branch (e.g., Computer Engineering) |
| `admission_year` | `INT(4)` | NO | Year of Admission |
| `current_semester` | `INT(2)` | NO | Current Academic Semester (e.g., 4, 5, 6) |
| `status` | `ENUM('ACTIVE','INACTIVE')` | NO | Enrollment Status |

#### `st_enrollment`
Links students to approved specialization programs and cohorts.
| Column | Type | Nullable | Description |
|---|---|---|---|
| `enrollment_id` | `INT(11) AUTO_INCREMENT` | NO | Primary Key |
| `student_id` | `INT(11)` | NO | FK &rarr; `st_student_master.student_id` |
| `specialization_id` | `INT(11)` | NO | FK &rarr; `st_specialization.specialization_id` |
| `enrollment_date` | `DATE` | NO | Date of Specialization Enrollment |
| `status` | `VARCHAR(20)` | NO | Status (`ENROLLED`, `COMPLETED`, `DROPPED`) |

#### `st_student_semester_history`
Tracks semester-wise SGPA, CGPA, credits earned, and backlog records.
| Column | Type | Nullable | Description |
|---|---|---|---|
| `history_id` | `INT(11) AUTO_INCREMENT` | NO | Primary Key |
| `student_id` | `INT(11)` | NO | FK &rarr; `st_student_master.student_id` |
| `semester` | `INT(2)` | NO | Semester Number |
| `sgpa` | `DECIMAL(4,2)` | YES | Semester Grade Point Average |
| `cgpa` | `DECIMAL(4,2)` | YES | Cumulative Grade Point Average |
| `academic_year` | `VARCHAR(20)` | NO | Academic Year (e.g., 2024-25) |

---

### 2.2 Historical Archive Tables

#### `st_historical_candidates`
Authoritative archive of 878 historical candidate records awaiting institutional regularization.
| Column | Type | Nullable | Description |
|---|---|---|---|
| `candidate_id` | `INT(11) AUTO_INCREMENT` | NO | Primary Key |
| `temp_id` | `VARCHAR(50)` | YES | Legacy/Temporary Identifier |
| `erp_id` | `VARCHAR(50)` | YES | Matched ERP ID (if verified) |
| `student_name` | `VARCHAR(150)` | NO | Full Candidate Name |
| `branch` | `VARCHAR(50)` | NO | Engineering Branch |
| `specialization_name` | `VARCHAR(150)` | NO | Declared Specialization Track |
| `source_file` | `VARCHAR(100)` | NO | Source Excel/Notice document |
| `approval_status` | `ENUM(...)` | NO | Current Status: `HOLD`, `PENDING`, `CONDITIONAL`, `APPROVED`, `REJECTED` |
| `match_confidence` | `VARCHAR(20)` | YES | Match Confidence Grade (`HIGH`, `MEDIUM`, `LOW`, `MANUAL`) |
| `discrepancy_flags` | `TEXT` | YES | Documented data anomalies or mismatches |
| `created_at` | `DATETIME` | NO | Record ingestion timestamp |

**Current Status Breakdown (Total: 878):**
- `HOLD`: **573** (Unregularized pending external institutional review)
- `PENDING`: **283** (Under internal department review)
- `CONDITIONAL`: **22** (Provisional clearance pending documentation)
- `APPROVED`: **0** (Strictly zero unverified promotions)
- `REJECTED`: **0**

#### `st_historical_candidate_audit`
Immutable audit register tracking every transition, verification step, and administrative note.
| Column | Type | Nullable | Description |
|---|---|---|---|
| `hist_audit_id` | `INT(11) AUTO_INCREMENT` | NO | Primary Key (Active range: IDs 5–30, Total 26 rows) |
| `candidate_id` | `INT(11)` | NO | FK &rarr; `st_historical_candidates.candidate_id` |
| `old_status` | `VARCHAR(50)` | YES | Previous status |
| `new_status` | `VARCHAR(50)` | NO | Updated status |
| `changed_by` | `VARCHAR(100)` | NO | Actor username / system process |
| `change_reason` | `TEXT` | YES | Formal justification or Council resolution |
| `changed_at` | `DATETIME` | NO | Precise change timestamp |

---

## 3. Database Triggers & Immutability Defenses

To enforce absolute immutability on audit records, MariaDB triggers intercept and reject any unauthorized DML:

```sql
DELIMITER $$

-- Prevent modifications to existing audit logs
CREATE TRIGGER trg_hist_audit_no_update
BEFORE UPDATE ON st_historical_candidate_audit
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'SECURITY VIOLATION: st_historical_candidate_audit is immutable. UPDATE operations are forbidden.';
END$$

-- Prevent deletion of audit entries
CREATE TRIGGER trg_hist_audit_no_delete
BEFORE DELETE ON st_historical_candidate_audit
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'SECURITY VIOLATION: st_historical_candidate_audit is immutable. DELETE operations are forbidden.';
END$$

DELIMITER ;
```

---

## 4. Primary Indexes & Performance Tuning

- `st_student_master(erp_id)` — Unique B-tree index for instantaneous lookup.
- `st_enrollment(student_id, specialization_id)` — Composite index optimizing student-track joins.
- `st_historical_candidates(approval_status, branch)` — Composite index backing DataTables server-side filtering.
- `st_historical_candidate_audit(candidate_id, changed_at)` — Index accelerating modal timeline queries.
