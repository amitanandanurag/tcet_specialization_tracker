# TCET ERP Dashboard Data Reconciliation Report
**Document ID:** FINAL-DATA-RECONCILIATION-2026-10-16  
**Execution Timestamp:** 2026-10-16 22:38:00 IST  
**Environment:** PHP 8.2.12 / MariaDB 10.4.32  
**Target Databases:** `tcet_st` (Reference / Read-Only), `tcet_st_test` (Isolated Test Database)  

---

## 1. Executive Summary: Resolving Metric Discrepancies

Prior audit notes recorded apparent conflicts across dashboard reports:
- Reference database baseline report: **55 active students**
- Isolated test database report: **50 active students**
- Subsequent browser UAT report: **58 active students**
- Current reference database count: **64 active students**
- Honours Degree application totals: **630** (43 approved + 587 pending) $\rightarrow$ **633** (46 approved + 587 pending) $\rightarrow$ **639** (52 approved + 587 pending)

An evidence-based investigation of student creation timestamps (`created_at`) and SQL query execution traces has definitively uncovered the root cause:

### The Proven Cause: Test Runner Subprocess Database Default
1. Several automated test suites (`test_academic_progression.php`, `test_honours_with_research_eligibility.php`, `test_final_independent_verification.php`, `test_progression_consistency.php`, etc.) instantiate `$db = new DBController();` without parameters.
2. In `database/db_connect.php`, when `new DBController()` is instantiated without arguments and without `getenv('TCET_DB_NAME')`, it falls back to the default production/reference database `tcet_st`.
3. In those test scripts, tests create mock students (e.g., `fname = 'Progression Test Student'`, `registration_no = 'TEST_PROG_...'`, `status = 1`, `specialization_id = 1`) without teardown cleanup.
4. Each automated test suite execution without explicit environment variable isolation inserted test rows directly into `tcet_st`:
   - Baseline seed: **43** approved Honours students + 587 pending + 12 other track active = **55 active students**.
   - After UAT automated run: 3 test students inserted $\rightarrow$ **46** approved Honours ($46 + 587 = 633$ applications) + 12 other active = **58 active students**.
   - After additional test iterations: 6 more test students inserted $\rightarrow$ **52** approved Honours ($52 + 587 = 639$ applications) + 12 other active = **64 active students**.
5. In `tcet_st_test`, the student count remained at **50 active students** ($37 \text{ Honours approved} + 2 \text{ Research} + 2 \text{ Minor} + 4 \text{ Multi} + 5 \text{ Unassigned} = \mathbf{50}$).

### The Remediation Implemented
1. `database/db_connect.php` was updated to sanitize and trim `getenv('TCET_DB_NAME')`.
2. `tests/run_all_tests.php` was updated to explicitly set and export `set "TCET_DB_NAME=tcet_st_test" && set "TCET_TEST_DB=tcet_st_test"` for all 11 test subprocesses.
3. Subsequent test suite runs confirm zero inserts or mutations into `tcet_st`. `tcet_st` remains 100% read-only.
4. Per safety rule #9, no student records were deleted or modified in `tcet_st` to force artificial numbers.

---

## 2. Independent Read-Only Database Audits

### A. Reference Database (`tcet_st`)
*Query verified:* `SELECT DATABASE();` $\rightarrow$ `tcet_st`

| Metric / Table | Count | Breakdown / Details |
|---|---|---|
| **Total Students (`st_student_master`)** | **651** | All historical records |
| **Active Students (`status = 1`)** | **64** | Approved / actively enrolled students |
| **Inactive / Pending (`status = 0`)** | **587** | Legacy unverified / pending applications |
| **Null / Blank Roll Numbers** | **2** | Data quality backlog from legacy import |
| **Duplicate Roll Numbers** | **74** | Multiple legacy rows sharing temporary roll codes |
| **Active Students With No Specialization** | **4** | Students with `specialization_id IS NULL` |
| **Total System Users (`st_user_master`)** | **12** | 1 Super Admin, 1 Admin, 1 Coordinator, 2 Mentors, 7 Students |
| **Active Faculty Mentors** | **2** | Users with `role_id = 4` in `st_user_master` |
| **Academic Departments** | **15** | Departments registered in `st_department_master` |

#### Specialization Track Breakdown (`tcet_st`):
| Specialization ID | Track Name | Status 1 (Approved) | Status 0 (Pending) | Total Applied |
|---|---|---|---|---|
| 1 | Honours Degree | 52 | 587 | **639** |
| 2 | Honours with Research | 2 | 0 | **2** |
| 3 | Minor Degree | 2 | 0 | **2** |
| 4 | Minor Multidisciplinary | 4 | 0 | **4** |
| *NULL* | *Unassigned Active Students* | 4 | 0 | **4** |
| **TOTAL** | | **64** | **587** | **651** |

*Invariant Check:* $52 + 587 = 639$ (Honours Total). Active students total: $52 + 2 + 2 + 4 + 4 = 64$. **Invariant holds 100%.**

---

### B. Isolated Test Database (`tcet_st_test`)
*Query verified:* `SELECT DATABASE();` $\rightarrow$ `tcet_st_test`

| Metric / Table | Count | Breakdown / Details |
|---|---|---|
| **Total Students (`st_student_master`)** | **638** | All test database records |
| **Active Students (`status = 1`)** | **50** | Enrolled test students |
| **Inactive / Pending (`status = 0`)** | **588** | Pending test records |
| **Active Students With No Specialization** | **5** | Test students with `specialization_id IS NULL` |
| **Total System Users (`st_user_master`)** | **14** | 1 Super Admin, 1 Admin, 1 Coordinator, 3 Mentors, 8 Students |
| **Active Faculty Mentors** | **3** | Users with `role_id = 4` |
| **Academic Departments** | **15** | Identical structure to reference DB |

#### Specialization Track Breakdown (`tcet_st_test`):
| Specialization ID | Track Name | Status 1 (Approved) | Status 0 (Pending) | Total Applied |
|---|---|---|---|---|
| 1 | Honours Degree | 37 | 588 | **625** |
| 2 | Honours with Research | 2 | 0 | **2** |
| 3 | Minor Degree | 2 | 0 | **2** |
| 4 | Minor Multidisciplinary | 4 | 0 | **4** |
| *NULL* | *Unassigned Active Students* | 5 | 0 | **5** |
| **TOTAL** | | **50** | **588** | **638** |

*Invariant Check:* $37 + 588 = 625$ (Honours Total). Active students total: $37 + 2 + 2 + 4 + 5 = 50$. **Invariant holds 100%.**

---

## 3. Dashboard KPI Semantics & Query Definitions

| Dashboard Metric | Business Meaning | Source Table & Filter | Exact SQL Expression |
|---|---|---|---|
| **Total Students** | Active, verified students currently enrolled in the college | `st_student_master` where `status = 1` (scoped by department for coordinator/HOD) | `SELECT COUNT(*) as total FROM st_student_master WHERE status = 1` |
| **Total Users** | System authentication and administrative accounts | `st_user_master` | `SELECT COUNT(*) as total FROM st_user_master` |
| **Branches** | Institutional engineering departments | `st_department_master` | `SELECT COUNT(*) as total FROM st_department_master` |
| **Mentors** | Assigned academic faculty mentors | `st_user_master` where `role_id IN (mentor_role_ids)` | `SELECT COUNT(*) as total FROM st_user_master WHERE role_id IN (4)` |
| **Application Status Tracking: Total Applied** | Cumulative submitted applications per specialization track | `st_specialization_master` LEFT JOIN `st_student_master` | `COUNT(s.student_id)` grouped by `sm.specialization_id` |
| **Application Status Tracking: Approved** | Applications approved and activated | `st_specialization_master` LEFT JOIN `st_student_master` | `SUM(CASE WHEN s.status = 1 THEN 1 ELSE 0 END)` |
| **Application Status Tracking: Pending** | Applications awaiting mentor/coordinator review or legacy pending submissions | `st_specialization_master` LEFT JOIN `st_student_master` | `SUM(CASE WHEN s.status = 0 THEN 1 ELSE 0 END)` |

### Status Modeling Note & Future Recommendation
In the legacy schema, `st_student_master.status` serves a dual purpose:
- For registered student accounts: `status = 1` (Active), `status = 0` (Deactivated).
- For specialization applications: `status = 1` (Approved), `status = 0` (Pending Review).

This dual-use column creates semantic ambiguity between a deactivated student and a pending application.  
**Recommendation for future release:** Introduce an explicit `application_status` enum (`'PENDING'`, `'APPROVED'`, `'REJECTED'`) on specialization mapping tables rather than overloading the binary `status` column.
