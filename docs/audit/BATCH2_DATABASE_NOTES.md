# TCET Specialization Tracker — Batch 2 Database Notes

## 1. Safety and Isolation Policy

1. **Isolation Mandate**: All test executions, data insertions, and schema modifications were restricted strictly to the isolated database `tcet_st_test`.
2. **Production Protection**: The baseline working database `tcet_st` was not altered, truncated, or subjected to test fixtures.
3. **Environment Dynamic Switch**: Integration scripts and test suites specify `TCET_DB_NAME=tcet_st_test` to prevent inadvertent operations on production data.

---

## 2. Issue P4: Student Status Representation

### 2.1 Schema Definition
Table: `st_student_master`
Column: `status tinyint(1) NOT NULL DEFAULT 1`

### 2.2 Status Field Value Semantics
| Value | Standard Representation | Workflow Implication |
| :---: | :--- | :--- |
| `1` | **Active / Enrolled / Approved** | Included in active student counts, mentor allocation lists, student dashboards, NPTEL progression, and batch promotions. |
| `0` | **Inactive / Deactivated / Pending** | Excluded from active mentor student lists, marked as Pending/Inactive in dashboards and exports, eligible for reactivation by administrators. |

### 2.3 Legacy Record Classification
- In the initial seed dataset, 587 legacy student rows were imported with `status = 0`.
  - 429 records contain registration numbers and academic details.
  - 158 records have blank/null registration numbers.
- **Decision & Safety Constraint**: In accordance with the audit guidelines, no blanket `UPDATE st_student_master SET status = 1` was executed. Blanket updates would destroy legitimate deactivation history and improperly activate unverified placeholder records.
- All modern admission and registration workflows correctly insert active students with `status = 1`.
- `admin/student_delete.php` now correctly updates `status = 0` for deactivations and `status = 1` for reactivations.

---

## 3. Issue P5: Password Storage Schema

### 3.1 Table: `st_login`
- Column: `password varchar(255)`
- All new registrations in `login/student_register.php` insert standard 60-character bcrypt hashes generated via:
  ```php
  DBController::hashPassword($plainPassword); // Uses PASSWORD_BCRYPT, cost: 10
  ```
- No plaintext credentials are stored in `st_login`.

### 3.2 Table: `st_user_master`
- Column: `is_first_login tinyint(1)`
- If a student registers with their own chosen password, `is_first_login` is set to `0`.
- If an automated or fallback temporary password is used, `is_first_login` is set to `1`, which triggers the forced change-password redirect upon login via `login/login.php` -> `admin/change_password.php`.

---

## 4. Issue P10: Menu Allocation Migration

### 4.1 Migration File
`database/migrations/20261016_remove_mentor_subject_manage_allocation.sql`

```sql
-- Migration: Remove Invalid Mentor Subject Management Menu Allocation
-- Date: 2026-10-16
-- Issue: P10 - Fix Mentor Subject Management Navigation Allocation
-- Target Database: tcet_st_test (isolated test database)

DELETE FROM st_menu_allocation_master 
WHERE role_id = 4 
  AND sub_menu_id IN (
    SELECT sub_menu_id FROM st_sub_menu_master 
    WHERE sub_menu_route LIKE '%specialization_subject_manage.php%'
  );
```

### 4.2 Allocation State Before and After Migration
- **Before**:
  - `menu_allocation_id = 134`, `role_id = 1` (Super Admin) -> `specialization_subject_manage.php`
  - `menu_allocation_id = 135`, `role_id = 2` (Admin/HOD) -> `specialization_subject_manage.php`
  - `menu_allocation_id = 136`, `role_id = 3` (Coordinator) -> `specialization_subject_manage.php`
  - `menu_allocation_id = 137`, `role_id = 4` (Mentor) -> `specialization_subject_manage.php` *(INVALID)*
- **After**:
  - Allocation `137` removed.
  - Roles 1, 2, and 3 allocations preserved (`134`, `135`, `136`).
  - No orphaned menu records or broken foreign references.
