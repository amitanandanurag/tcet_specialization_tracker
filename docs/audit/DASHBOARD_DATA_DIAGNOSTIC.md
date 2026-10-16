# TCET Specialization Tracker — Dashboard Data Diagnostic & Reconciliation Report

**Date:** 2026-10-16  
**System:** TCET Specialization Tracker / TCET ERP (`C:\xampp\htdocs\st`)  
**PHP Version:** 8.2 | **Database Engine:** MariaDB 10.4.32  
**Target Test Database:** `tcet_st_test` | **Reference Database:** `tcet_st`  

---

## 1. Executive Summary

This diagnostic report details the data architecture, source tables, SQL queries, business logic, and mathematical reconciliation for the metrics displayed on the TCET ERP Administrative Dashboard (`admin/index.php` / `admin/dashboard.php`).

The dashboard previously displayed:
- **Total Students:** 55
- **Total Users:** 12
- **Total Branches:** 15
- **Mentors:** 2
- **Application Status Tracking Table:** Displayed all 25 specializations (21 of which were courses/domains with 0 applications)
- **Honours Degree Applications:** Total Applied = 630, Approved = 43, Pending = 587

---

## 2. Source Tables and Entity Definitions

| Metric Card / UI Element | Source Table | Key Columns | Entity Definition & Business Meaning |
| :--- | :--- | :--- | :--- |
| **Total Students** | `st_student_master` | `student_id`, `status`, `department_id` | **Active enrolled students** (`status = 1`). Excludes non-students, inactive students (`status = 0`), and pending registrations. |
| **Total Users** | `st_user_master` | `user_id`, `role_id`, `department_id` | **Staff and administrative system accounts** (Super Admin, Admin, Coordinator/HOD, Mentor). |
| **Total Branches** | `st_department_master` | `department_id`, `department_name` | **Academic departments / engineering branches** configured in the institution. |
| **Mentors** | `st_user_master` | `user_id`, `role_id = 4`, `department_id` | **Faculty allocated as student mentors** (`role_id = 4`). |
| **Application Status Tracking** | `st_specialization_master` LEFT JOIN `st_student_master` | `sm.specialization_id`, `sm.specialization_name`, `s.status` | **Specialization track applications** submitted by students. Tracks: 1=Honours, 2=Honours w/ Research, 3=Minor, 4=Minor Multidisciplinary. |
| **Specialization Overview Chart** | `st_specialization_master` LEFT JOIN `st_student_master` | `sm.specialization_id`, `COUNT(s.student_id)` | **Institution-wide distribution** of students across all specialization offerings. |

---

## 3. Investigation & Reconciliation: 55 Total Students vs 630 Honours Applications

### A. The Core Question
Why does the dashboard report **55 Total Students** while Honours Degree reports **630 Total Applied**?

### B. Findings & Evidence
1. **Different Business Entities:**
   - The **"Total Students" KPI card** measures **Active Enrolled Students** at TCET:
     ```sql
     SELECT COUNT(*) as total FROM st_student_master WHERE status = 1;
     ```
     In the reference database `tcet_st`, this yields exactly **55 active students** (50 in `tcet_st_test`).
   
   - The **"Application Status Tracking"** table measures **Submitted Applications** per specialization track:
     ```sql
     SELECT 
         sm.specialization_id, sm.specialization_name,
         COUNT(s.student_id) as total,
         SUM(CASE WHEN s.status = 1 THEN 1 ELSE 0 END) as approved,
         SUM(CASE WHEN s.status = 0 THEN 1 ELSE 0 END) as pending,
         0 as rejected
     FROM st_specialization_master sm
     LEFT JOIN st_student_master s ON sm.specialization_id = s.specialization_id
     GROUP BY sm.specialization_id, sm.specialization_name;
     ```

2. **The Status Breakdown:**
   For Honours Degree (`specialization_id = 1`):
   - **Approved (`status = 1`):** 43
   - **Pending (`status = 0`):** 587
   - **Total Applied:** 43 + 587 = **630**

3. **Mathematical Invariant Holds Perfectly:**
   $$\text{Total Applied} = \text{Approved} + \text{Pending}$$
   $$630 = 43 + 587$$

4. **Reconciling Approved Applications with Total Students:**
   Across all 4 specialization tracks:
   - Honours Degree: **43** approved
   - Honours with Research: **2** approved
   - Minor Degree: **2** approved
   - Minor Multidisciplinary: **4** approved
   - Active Students with no specialization assigned (`specialization_id IS NULL`): **4**
   $$\text{Total Active Students} = 43 + 2 + 2 + 4 + 4 = \mathbf{55}$$

5. **Conclusion:**
   The 55 students vs 630 applications difference is **not a database corruption bug or join multiplication**. It represents the valid distinction between **Active Enrolled Students** (55 approved students) and **Total Historical Applications** (630 applicants, of which 587 are pending validation/legacy submissions).

---

## 4. Query Analysis & Defect Identification

### Defect 1: Displaying 25 Specializations instead of the 4 Main Tracks
- **Cause:** `st_specialization_master` contains 25 records: IDs 1–4 are Degree Tracks (`is_track = 1`), while IDs 5–25 are individual subject areas (`is_track = 0`).
- **Impact:** The Application Status Tracking table rendered 21 zero-count course rows, cluttering the table.
- **Fix:** Restrict default table rendering to the first 4 configured tracks (`array_slice($spec_data, 0, 4, true)`).

### Defect 2: Role-Scoped LEFT JOIN Filter Bug
- **Original Code:**
  ```sql
  WHERE s.department_id = [dept_id] OR s.student_id IS NULL
  ```
- **Impact:** If another department had students enrolled in a track where the logged-in department had none, `s.student_id` was NOT NULL, causing the `WHERE` condition to evaluate to FALSE and completely dropping that specialization row from the HOD dashboard.
- **Fix:** Moved department filtering into the `ON` clause:
  ```sql
  LEFT JOIN st_student_master s ON sm.specialization_id = s.specialization_id AND s.department_id = [dept_id]
  ```

### Defect 3: Doughnut Chart Color Truncation
- **Original Code:** Hardcoded only 3 colors (`['#00c0ef', '#f39c12', '#00a65a']`) for up to 25 specialization items.
- **Impact:** Specializations beyond index 2 lacked color definitions in Chart.js.
- **Fix:** Implemented a full 25-color curated AdminLTE-compatible palette bound dynamically via PHP.

### Defect 4: Redundant Duplicate Full-Table Scan
- **Original Code:** Executed `SELECT * FROM st_student_master WHERE status = 1` followed by `mysqli_num_rows($result)` at line 412, despite `SELECT COUNT(*)` already being executed at line 187.
- **Fix:** Consolidated into a single role-aware `COUNT(*)` query at Section 2.
