# TCET ERP — Data Accuracy Reconciliation Report

**Project:** TCET Specialization Tracker (TCET ERP)  
**Location:** `C:\xampp\htdocs\st`  
**Database Evaluated:** `tcet_st` (Production Reference, Read-Only) & `tcet_st_test` (Isolated Test Database)  
**Date:** October 17, 2026  
**Status:** FULLY RECONCILED & MATHEMATICALLY VERIFIED  

---

## 1. Discrepancy Investigation & Root Causes

The supplied audit screenshots highlighted apparent discrepancies across the system:
1. **Total Students (Dashboard: 64 vs Student Directory: 651 vs Branch Info: 46)**
2. **Total Users (Dashboard: 12)**
3. **Total Mentors (Dashboard: 2)**
4. **Branch Information (1 branch and 1 HOD)**
5. **Honours Degree Applications (639 Total: 52 Approved, 587 Pending)**

### Root Cause Analysis

| Screenshot Observation | Underlying Database State & Discovery | Root Cause Identified |
|---|---|---|
| **Dashboard: 64 Students** | `SELECT COUNT(*) FROM st_student_master WHERE status = 1` yields exactly **64** (in production `tcet_st`). | Dashboard intentionally filtered on `status = 1` (Active/Approved Enrolled students only). |
| **Directory: 651 Students** | `SELECT COUNT(*) FROM st_student_master` yields exactly **651** (in production `tcet_st`). | Directory queried all registered students regardless of status (64 active + 587 pending = 651 total). |
| **Branch Info: 46 Students** | `SELECT COUNT(*) FROM st_student_master WHERE department_id = 1` yields **46** (in production `tcet_st`). | Branch Information was filtered to Department 1 (MCA), which contains 46 total registered students (40 active, 6 pending). |
| **Dashboard: 12 Users** | `SELECT COUNT(*) FROM st_user_master` yields **12** in production `tcet_st`. | Dashboard KPI accurately counted distinct user accounts in `st_user_master`. |
| **Dashboard: 2 Mentors** | `SELECT COUNT(*) FROM st_user_master WHERE role_id = 4` yields **2** in production `tcet_st`. | Dashboard correctly counted distinct mentor user accounts, rather than mentor-subject allocation rows. |
| **Branch Info: 1 HOD / 1 Branch** | Query in `admin/branch_info.php` had `role_id = 2` (Admin) instead of `role_id = 3` (HOD/Coordinator). | **Coding Bug in Legacy Query:** `total_hods` was querying `role_id = 2` which counts Admins, not HODs. Corrected to `role_id = 3`. |
| **Applications: 639 Total, 52 Approved, 587 Pending** | `SELECT COUNT(*) FROM st_student_master WHERE specialization_id = 1` yields **639** (52 active `status = 1`, 587 pending `status = 0`). | Application counts reconcile 100% mathematically: $52 + 587 = 639$. |
| **Dashboard Department Status** | `admin/dashboard.php` previously used `MOD(d.department_id, 3)` to fabricate status strings ("Completed", "Pending", "In Progress"). | **Fabricated Status Bug:** Replaced with authoritative check: `CASE WHEN COUNT(u.user_id) > 0 THEN 'Assigned' ELSE 'Vacant' END`. |

---

## 2. Authoritative Metrics Definitions

Every dashboard and institutional KPI now follows an authoritative, role-scoped definition:

### 1. Total Students
- **Business Definition:** All registered student candidates within the institution.
- **Display Standard:** The primary metric card displays Active Enrolled students (`status = 1`) with the total registered count clearly noted in parentheses (e.g., `64 Enrolled (651 Registered)`).
- **Source Table:** `st_student_master s`
- **Authoritative SQL:**
  ```sql
  SELECT 
      COUNT(DISTINCT s.student_id) AS total_registered,
      COUNT(DISTINCT CASE WHEN s.status = 1 THEN s.student_id END) AS active_enrolled,
      COUNT(DISTINCT CASE WHEN s.status = 0 THEN s.student_id END) AS pending_admissions
  FROM st_student_master s;
  ```

### 2. Total Users
- **Business Definition:** Unique user accounts registered in the ERP.
- **Source Table:** `st_user_master u`
- **Authoritative SQL:**
  ```sql
  SELECT COUNT(DISTINCT u.user_id) AS total_users FROM st_user_master u;
  ```

### 3. Total Mentors
- **Business Definition:** Unique staff members assigned to the Mentor role (`role_id = 4`).
- **Source Table:** `st_user_master u`
- **Authoritative SQL:**
  ```sql
  SELECT COUNT(DISTINCT u.user_id) AS total_mentors 
  FROM st_user_master u 
  WHERE u.role_id = 4;
  ```

### 4. Total Branches
- **Business Definition:** Active academic departments (excluding meta-department 15 'All Branch').
- **Source Table:** `st_department_master`
- **Authoritative SQL:**
  ```sql
  SELECT COUNT(DISTINCT department_id) AS total_branches 
  FROM st_department_master 
  WHERE department_id != 15;
  ```

### 5. Application Status Distribution
- **Business Definition:** Student applications per specialization, partitioned into Approved (`status = 1`), Pending (`status = 0`), and Other/Rejected (`status NOT IN (0, 1)` or `NULL`).
- **Source Table:** `st_student_master s`
- **Authoritative SQL:**
  ```sql
  SELECT 
      sp.specialization_name,
      COUNT(s.student_id) AS total_applications,
      COUNT(CASE WHEN s.status = 1 THEN 1 END) AS approved_count,
      COUNT(CASE WHEN s.status = 0 THEN 1 END) AS pending_count,
      COUNT(CASE WHEN s.status NOT IN (0, 1) OR s.status IS NULL THEN 1 END) AS other_count
  FROM st_specialization_master sp
  LEFT JOIN st_student_master s ON s.specialization_id = sp.specialization_id
  GROUP BY sp.specialization_id, sp.specialization_name;
  ```

---

## 3. Before-and-After Reconciliation Evidence

| Metric | Before Fix (Screenshot State) | After Fix (Authoritative Engine) | Reconciliation Explanation |
|---|---|---|---|
| **Dashboard Student KPI** | Displayed `64` without context, causing confusion against Directory `651`. | Displays `64 Enrolled (651 Registered)` in prod; `61 Enrolled (649 Registered)` in test DB. | Transparently explains the distinction between approved active students and total registered admissions. |
| **Department Status in Table** | Fabricated via `MOD(d.department_id, 3)` producing arbitrary "Pending" / "Completed". | Derived from real database relationships: `Assigned` if HOD exists, `Vacant` if unassigned. | Eliminates hardcoded modulo logic and mirrors authoritative staffing state. |
| **Branch Info HOD Count** | Counted `role_id = 2` (Admin) instead of Coordinators/HODs (`role_id = 3`). | Queries `role_id = 3` (`COORDINATOR / HOD`). | Accurately counts HODs rather than institutional Admins. |
| **Branch Info Student Count** | Displayed only 46 without breakdown. | Displays active enrolled (`total_students`) alongside registered total (`total_registered_students`). | Harmonized with institution-wide student definitions. |
| **Application Reconciliation** | Potential for unhandled null/unknown statuses. | Explicitly groups into Approved, Pending, and dynamic Other/Rejected. | Total applications ($639$) equals Approved ($52$) + Pending ($587$) + Other ($0$). Perfect zero-drift mathematical reconciliation. |

---

## 4. Test Verification Evidence

Automated tests in `tests/test_role_based_access_control.php` Section 3 validated all metrics:
- `[PASS] Metrics returns both active and registered student totals`
- `[PASS] Active enrolled students (61) <= Total registered (649)`
- `[PASS] Total users (14) matches distinct user count (14)`
- `[PASS] Total mentors (3) counts distinct mentor users (3), not join rows`
- `[PASS] Total branches (14) matches valid academic departments (14)`
- `[PASS] Application stats reconcile exactly: Total (636) = Approved (48) + Pending (588) + Other (0)`
- `[PASS] Branch Information counts Coordinators/HODs (role_id = 3), count = 0`

No records were modified or fabricated to force numbers to match. All values derive dynamically and deterministically from the database state.
