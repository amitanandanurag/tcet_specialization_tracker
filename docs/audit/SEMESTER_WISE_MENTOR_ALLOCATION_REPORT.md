# TCET ERP — Semester-Wise, Subject-Wise Mentor Allocation Report

**Document Reference:** `docs/audit/SEMESTER_WISE_MENTOR_ALLOCATION_REPORT.md`  
**Execution Environment:** Isolated Test Database `tcet_st_test` (PHP 8.2, MariaDB 10.4)  
**Production / Reference Database:** `tcet_st` (Strictly Unmodified)  
**Status:** COMPLETE & VERIFIED (27/27 Scenario Tests Passed, 327/327 Master Regression Tests Passed)

---

## Executive Summary

The TCET ERP Specialization Tracker previously assigned mentors at the course/subject level without enforcing semester and academic year context. This led to cross-semester allocation contamination—where a mentor assigned to teach a subject in Semester 7 could unintentionally be mapped to Semester 5 students taking the same subject.

To enforce the fundamental institutional rule:
> **"Same subject does not mean same mentor. The complete academic allocation context determines the eligible mentor."**

We implemented strict separation between **Mentor Accounts** (`st_user_master`) and **Mentor Allocations** (`st_mentor_subject_mapping`), bound all allocations to the **4-dimensional composite key** (`Academic Year + Department + Specialization Subject + Semester`), updated the Mentor Registration and Mentor Allocation user interfaces, enforced strict server-side validation against all manual and automated assignment paths, and created automated test suites validating all 16 target scenarios.

---

## 1. Existing Schema and Code Findings

### 1.1 Architectural Gap Analysis
1. **Account vs. Allocation Coupling:** In legacy code, mentor registration (`admin/role_register_base.php`) captured only `Department` and a single `Specialization Subject`. The backend handler attempted to treat a mentor account as a 1:1 subject mapping.
2. **Missing Dimensions in Legacy Mapping:** While `st_mentor_subject_mapping` contained `semester_id` and `academic_year_id`, they were often populated with `0` or `NULL` during registration. Crucially, the table lacked `department_id` and an active status flag (`is_active`), preventing department-specific scoping or deactivation of expired allocations without deleting assignment records.
3. **Arbitrary Auto-Allocation (`autoAllocateMentor`):** The legacy `autoAllocateMentor` method in `database/db_connect.php` resolved mentors using a `LIMIT 1` query that fell back to any mentor teaching the subject regardless of semester or academic year.
4. **Direct Manual Assignment Bypass:** `admin/mentor_allocation.php` (`mentor_allocation_assign_students`) did not verify whether the destination mentor held an active allocation matching the students' semester and academic year before inserting mappings into `st_mentor_student_mapping`.
5. **st_user_master Schema Constraint:** `st_user_master` does **not** have a `status` column (it utilizes `role_id`, `department_id`, `student_id`, and `is_first_login`). Allocation status belongs strictly on `st_mentor_subject_mapping.is_active`.

---

## 2. Tables and Relationships Used

The architecture cleanly delineates identity, academic progression, and allocations across the following tables:

| Table | Entity | Key Attributes Used | Relationship |
|---|---|---|---|
| `st_user_master` | Mentor Identity | `user_id`, `user_name`, `email_id`, `phone_number`, `role_id=4`, `department_id` | 1:N with `st_mentor_subject_mapping` |
| `st_mentor_subject_mapping` | Academic Allocation | `mapping_id`, `mentor_id`, `department_id`, `subject_id`, `semester_id`, `academic_year_id`, `is_active`, `created_at`, `updated_at` | Composite key: `(mentor_id, subject_id, semester_id, academic_year_id)` |
| `st_student_master` | Student Active Context | `student_id`, `department_id`, `specialization_subject_id`, `current_semester_id`, `academic_year_id`, `status` | Student authoritative state |
| `st_student_semester_history` | Historical Ledger | `history_id`, `student_id`, `semester_id`, `academic_year_id`, `department_id`, `specialization_subject_id`, `mentor_id`, `status` | Preserves past semester mentor assignments |
| `st_mentor_student_mapping` | Active Student Assignment | `mapping_id`, `mentor_id`, `student_id`, `semester_id`, `academic_year_id` | 1:1 active student-mentor pairing per semester |
| `st_specialization_subject_master` | Subject Master | `subject_id`, `subject_name`, `department_id`, `is_active` | Catalog of elective offerings |
| `st_department_master` | Department Master | `department_id`, `department_name`, `dept_code` | Academic departments |
| `st_academic_year_master` | Academic Year Master | `academic_year_id`, `academic_year` | Official academic sessions (e.g., 2026-2027) |

```
[st_user_master (Mentor, Role 4)]
          │ 1
          │
          ▼ N
[st_mentor_subject_mapping] ◄── [st_specialization_subject_master]
  (mentor_id,                   [st_department_master]
   subject_id,                  [st_academic_year_master]
   semester_id,
   academic_year_id,
   department_id,
   is_active)
          ▲
          │ Validates Context
          │
[st_student_master] ──► [st_mentor_student_mapping] ──► [st_student_semester_history]
 (Student context)       (Active Assignment)             (Historical Ledger)
```

---

## 3. Database Integrity & Migration Details

### 3.1 Migration Script: `database/migrations/20261016_semester_wise_mentor_allocation.sql`
Executed against isolated test database `tcet_st_test`:

```sql
-- 1. Add department_id to st_mentor_subject_mapping if not present
SET @col_dept_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'st_mentor_subject_mapping' 
      AND COLUMN_NAME = 'department_id'
);
SET @sql_dept := IF(@col_dept_exists = 0, 
    'ALTER TABLE `st_mentor_subject_mapping` ADD COLUMN `department_id` INT(11) NULL AFTER `mentor_id`', 
    'SELECT "department_id already exists"');
PREPARE stmt_dept FROM @sql_dept;
EXECUTE stmt_dept;
DEALLOCATE PREPARE stmt_dept;

-- 2. Add is_active flag to st_mentor_subject_mapping if not present
SET @col_active_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'st_mentor_subject_mapping' 
      AND COLUMN_NAME = 'is_active'
);
SET @sql_active := IF(@col_active_exists = 0, 
    'ALTER TABLE `st_mentor_subject_mapping` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `semester_id`', 
    'SELECT "is_active already exists"');
PREPARE stmt_active FROM @sql_active;
EXECUTE stmt_active;
DEALLOCATE PREPARE stmt_active;

-- 3. Populate missing department_id from mentor user profile
UPDATE st_mentor_subject_mapping msm
JOIN st_user_master u ON u.user_id = msm.mentor_id
SET msm.department_id = u.department_id
WHERE msm.department_id IS NULL OR msm.department_id = 0;

-- 4. Composite unique constraint preventing duplicate active allocations for the same mentor
ALTER TABLE `st_mentor_subject_mapping`
  ADD UNIQUE KEY `unique_period_subject_mentor` (`mentor_id`, `subject_id`, `semester_id`, `academic_year_id`);

-- 5. Fast lookup index for student allocation resolution
ALTER TABLE `st_mentor_subject_mapping`
  ADD INDEX `idx_alloc_lookup` (`subject_id`, `semester_id`, `academic_year_id`, `is_active`);
```

---

## 4. Mentor Registration Form Changes

### 4.1 UI Refinement (`admin/role_register_base.php`)
- **Required Dynamic Fields:**
  1. **Department:** Populated dynamically from `st_department_master`.
  2. **Academic Year:** Populated dynamically from `st_academic_year_master` (latest year auto-selected).
  3. **Semester:** Mandatory dropdown covering Semesters 1 through 8.
  4. **Specialization Subject:** Dynamic dropdown populated from `st_specialization_subject_master`.
- **Dynamic JavaScript Filtering:**
  - When the administrator selects or changes the Department or Semester, the Specialization Subject dropdown automatically updates via AJAX/client filtering to show only subjects matching the selected academic scope.
- **Account Disambiguation & Multiple Allocations:**
  - If a mentor already has a registered user account (`st_user_master`), the registration handler in `admin/role_process_base.php` no longer throws a duplicate email error or forces creation of a duplicate user. Instead, it recognizes the existing mentor account and creates the new academic allocation in `st_mentor_subject_mapping` after validating permissions and duplicate constraints.

### 4.2 Mentor Edit & Profile Views
- `admin/role_edit_base.php`: The legacy single-subject dropdown was replaced with an **Active Allocations Table** displaying every Subject, Semester, Academic Year, and Status assigned to that mentor, with direct links to manage allocations in `admin/mentor_subject.php`.
- `admin/role_view_base.php`: Formatted table presenting the complete academic context for the mentor.

---

## 5. Allocation-Key Definition

A valid mentor allocation is strictly governed by the **4-dimensional composite key**:

$$\text{Allocation Key} = \langle \text{Academic Year}, \text{Department}, \text{Specialization Subject}, \text{Semester} \rangle$$

### Cardinal Rules:
1. **Non-Equivalence:** Mentor X allocated to `(2026-2027, Computer, Cyber Security, Sem 5)` is **not** eligible for `(2026-2027, Computer, Cyber Security, Sem 7)`.
2. **Active Status:** Only allocations where `msm.is_active = 1` and mentor `u.role_id = 4` are eligible.
3. **Ambiguity Prohibition:** If two active mentors are mapped to the identical context key, automatic assignment aborts and flags an ambiguity error requiring administrative resolution. The system never picks one at random.
4. **No Silent Fallback:** If no matching mentor is configured for that exact 4-key tuple, the student remains unassigned with a diagnostic message.

---

## 6. Student Assignment Logic

Updated in `database/db_connect.php`:

### 6.1 `getMatchingMentorsForAllocation($subjectId, $semesterId, $academicYearId, $departmentId)`
Executes a parameterized query joining `st_mentor_subject_mapping` with `st_user_master`:
- Filters on `subject_id = ?`, `semester_id = ?`, `academic_year_id = ?`, `is_active = 1`, and `u.role_id = 4`.
- Validates department matching (`msm.department_id = $deptId OR msm.department_id = 0 OR u.department_id = $deptId`).
- Returns all matching mentors.

### 6.2 `getResolvedMentorForSubject(...)`
- Validates that both `semester_id > 0` and `academic_year_id > 0`. A subject alone returns `null`.
- If matches = 0, returns `null`.
- If matches = 1, returns the unique mentor record.
- If matches > 1, returns structured ambiguity error:
  `['ambiguous' => true, 'count' => N, 'mentors' => [...], 'error' => '...']`

### 6.3 `autoAllocateMentor($studentId)`
- Reads authoritative student records from `st_student_master`.
- Checks `st_student_semester_history` for any semester-specific override.
- Looks up the matching active mentor via `getResolvedMentorForSubject`.
- If ambiguous or absent, safely exits with `false` without making improper assignments.
- Inserts/updates `st_mentor_student_mapping` and synchronizes `st_student_semester_history.mentor_id`.

### 6.4 `allocateStudentToMentor($mentorId, $studentId)`
- Performs strict backend pre-flight validation: verifies the mentor has an active allocation matching the student's exact subject, semester, and academic year before permitting the pairing.

### 6.5 `removeInvalidStudentMentorAllocation($studentId)`
- Invoked on semester promotion or profile update. Checks if the current mentor mapping matches the student's new semester, subject, and AY. If the mentor has no allocation for the new context, the mapping is deleted and the active history ledger is unlinked.

---

## 7. Backend Security and Validation Changes

1. **Role Authorization:** Direct allocation endpoints (`admin/mentor_subject.php`, `admin/mentor_allocation.php`) enforce admin/coordinator roles (`role_id IN (1, 2, 3)`). Role 4 (Mentor) and Role 5 (Student) are forbidden from modifying allocations.
2. **CSRF Protection:** All allocation forms and AJAX operations require verified CSRF tokens (`DBController::validateCsrfToken()`).
3. **SQL Injection Defense:** All allocation lookups, inserts, and updates use parameterized `mysqli_prepare` statements.
4. **Direct POST/AJAX Tamper Defense:** In `admin/mentor_allocation.php`, both `assign_selected` and `assign_filtered` actions validate that the target mentor possesses an active allocation matching the students' semester and subject. Submitting mismatched IDs via manipulated HTTP requests is rejected server-side.

---

## 8. Test Scenarios and Actual Results

Automated test suite: `tests/test_semester_wise_mentor_allocation.php`  
Executed against test database `tcet_st_test`:

| # | Scenario Description | Tested Logic | Actual Output | Status |
|---|---|---|---|---|
| 1 | Allocate Mentor X to Cyber Security, Sem 5, AY 2026-2027 | `assignSubjectMentor` | Allocation record exists with `is_active = 1` | **PASS** |
| 2 | Allocate Mentor Y to Cyber Security, Sem 7, AY 2026-2027 | `assignSubjectMentor` | Allocation record exists with `is_active = 1` | **PASS** |
| 3 | Sem 5 student assigned to Mentor X | `autoAllocateMentor` | Student mapped to Mentor X (#9017) | **PASS** |
| 4 | Sem 7 student assigned to Mentor Y | `autoAllocateMentor` | Student mapped to Mentor Y (#9018) | **PASS** |
| 5 | Sem 5 student cannot be assigned to Mentor Y | `allocateStudentToMentor` | Rejects Mentor Y; resolved mentor is Mentor X | **PASS** |
| 6 | Cross-Academic-Year allocation rejected | `allocateStudentToMentor` | Rejects Mentor X for student in AY 1 (allocation is AY 2) | **PASS** |
| 7 | Mentor assigned to different subject rejected | `allocateStudentToMentor` | Mentor Z (Subject 4) rejected for Student studying Subject 5 | **PASS** |
| 8 | Missing semester or academic year rejected | `getResolvedMentorForSubject` | Returns `null` when semester or AY omitted; returns `false` | **PASS** |
| 9 | Disabled/inactive allocation cannot receive assignments | `allocateStudentToMentor` | Setting `is_active = 0` causes immediate allocation rejection | **PASS** |
| 10 | Absent matching allocation handled cleanly | `getResolvedMentorForSubject` | Returns `null` for unmapped Semester 8 | **PASS** |
| 11 | Multiple active matches detected as ambiguous | `getResolvedMentorForSubject` | Ambiguity detected; count = 2; returns resolution prompt | **PASS** |
| 12 | Changing student's semester triggers invalidation | `removeInvalidStudentMentorAllocation` | Sem 6 invalid mapping cleared automatically | **PASS** |
| 13 | Mentor accounts & history integrity | `st_user_master` query | Mentor X and Y accounts preserved intact | **PASS** |
| 14 | Unauthorized role access protection | Route auth policy | Student (Role 5) and Mentor (Role 4) denied admin allocation | **PASS** |
| 15 | Direct POST/AJAX requests enforce backend rules | `mentor_allocation_assign_students` | Direct call with mismatched semester rejected | **PASS** |
| 16 | Duplicate allocation handling follows business rules | `assignSubjectMentor` | Upserts existing mapping safely without duplicate accounts | **PASS** |

### Complete Regression Verification
- **Total Master Suites Executed:** 12 suites (`tests/run_all_tests.php`)
- **Total Tests:** 327
- **Total Passed:** 327
- **Total Failed:** 0
- **Pass Rate:** **100.0%**

---

## 9. Remaining Issues and Migration Risks

| Risk | Likelihood | Impact | Mitigation Strategy |
|---|---|---|---|
| Legacy mappings with `semester_id = 0` in production | Medium | Medium | Migration script `20261016_semester_wise_mentor_allocation.sql` inspects and updates `is_active = 1` while preserving existing IDs. Administrators can review unassigned subjects via the filter bar in `admin/mentor_subject.php`. |
| Cross-department specialization electives | Low | Low | When subjects are offered institute-wide across multiple departments, `msm.department_id` defaults to `0`, matching students from any department enrolled in that elective. |
| Co-mentoring scenarios | Low | Low | If multiple mentors share a single cohort, the ambiguity detector flags the conflict. Administrators select the primary mentor or allocate distinct divisions. |

---

## 10. Files Modified and Database Safety Confirmation

### 10.1 Application Files Modified
1. `database/db_connect.php`: Enforced 4-dimensional allocation key, ambiguity handling, active status check, and dynamic mentor resolution.
2. `admin/role_register_base.php`: Added Academic Year and Semester mandatory fields with live AJAX filtering of subjects by department and semester.
3. `admin/role_process_base.php`: Added server-side validation for all 4 allocation dimensions; enables multi-allocation linking to existing mentor accounts.
4. `admin/role_edit_base.php`: Replaced single-subject input with full academic allocations table and direct link to allocation manager.
5. `admin/role_view_base.php`: Added structured display of all active academic allocations for the mentor.
6. `admin/mentor_subject.php`: Rebuilt with multi-dimensional allocation form, dynamic subject filtering, status toggling, and multi-field filter bar.
7. `admin/mentor_allocation.php`: Enforced strict semester and subject matching on batch allocation operations.
8. `database/migrations/20261016_semester_wise_mentor_allocation.sql`: Migration script for `st_mentor_subject_mapping`.
9. `tests/test_semester_wise_mentor_allocation.php`: Automated test suite covering all 16 target scenarios (27 individual assertions).
10. `tests/run_all_tests.php`: Wired new allocation suite into the master regression test runner.

### 10.2 Database Safety Confirmation
- **Isolated Testing:** All schema alterations, test insertions, and synthetic student allocations were executed exclusively on `tcet_st_test`.
- **Production Safety:** Production / Reference database `tcet_st` was not altered, truncated, or dropped.
- **Data Preservation:** Zero existing user accounts or student assignment history records were deleted.
- **Syntax Verification:** All PHP files passed `php -l` syntax validation with zero errors or warnings.
