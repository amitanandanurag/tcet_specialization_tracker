# TCET Specialization Tracker — Dashboard UI Fix Report

**Date:** 2026-10-16  
**Module:** Administrative Dashboard (`admin/index.php`, `admin/dashboard.php`)  
**PHP Version:** 8.2 | **Stack:** PHP, MySQL/MariaDB, AdminLTE, Bootstrap 3, jQuery, Chart.js  

---

## 1. Overview of Changes

This report documents the architectural, query, and UI fixes implemented in `admin/dashboard.php` to resolve dashboard data accuracy, limit Application Status Tracking to the primary specialization tracks, correct role-scoped joins, and enhance frontend styling.

---

## 2. Issues & Root Cause Analysis

### Issue 1: Application Status Tracking Displayed All 25 Specializations
- **File:** `admin/dashboard.php`
- **Root Cause:** The table iterated over the entire `$spec_data` associative array populated from `st_specialization_master`. While rows 1–4 represent Degree Tracks (Honours, Honours w/ Research, Minor, Minor Multidisciplinary), rows 5–25 represent specific course subjects with zero direct student enrollments.
- **Resolution:** Limited the default table display to the first four configured tracks using `$table_spec_data = array_slice($spec_data, 0, 4, true);`.

### Issue 2: Doughnut Chart Color Starvation
- **File:** `admin/dashboard.php` (Chart.js initialization)
- **Root Cause:** The dataset configuration hardcoded an array of only 3 hex colors: `['#00c0ef', '#f39c12', '#00a65a']`. When more than 3 specializations were plotted, subsequent slices defaulted to missing or neutral colors.
- **Resolution:** Added a curated 25-color AdminLTE-compatible palette (`$palette` and `$hover_palette`) generated dynamically in PHP and rendered into the `specializationChart` dataset.

### Issue 3: Role-Scoped Join Filter Bug (Coordinator / HOD View)
- **File:** `admin/dashboard.php` (lines 225–241)
- **Root Cause:** When `$is_scoped_user && $logged_dept_id` was active, the script appended `WHERE s.department_id = ... OR s.student_id IS NULL`. This filtering in the `WHERE` clause dropped any specialization that had students enrolled in other departments if the logged-in department had zero students.
- **Resolution:** Moved the department filter into the `LEFT JOIN ... ON` clause (`AND s.department_id = ...`), ensuring all 25 specializations remain present with count 0 for the scoped department.

### Issue 4: Redundant Unbuffered Full-Table Queries
- **File:** `admin/dashboard.php` (lines 425–446)
- **Root Cause:** The script executed `SELECT * FROM st_student_master WHERE status = 1` and `SELECT * FROM st_user_master` simply to call `mysqli_num_rows()`, creating unnecessary memory consumption.
- **Resolution:** Consolidated student counting into a single role-aware query (`SELECT COUNT(*) as total`) during Section 2 card preparation and assigned `$rowcount = $total_students;`.

---

## 3. Code Modifications Summary

### Modified File: `admin/dashboard.php`

#### A. Consolidated Student Query (Lines 182–196)
```diff
- $student_count_sql = "SELECT COUNT(*) as total FROM st_student_master WHERE status = 1";
- if ($is_scoped_user && $logged_dept_id) {
-     $student_count_sql .= " AND department_id = " . intval($logged_dept_id);
- }
- $students_query = $student_count_sql;
- $students_result = mysqli_query($db_handle->conn, $students_query);
- $total_students = $students_result ? mysqli_fetch_assoc($students_result)['total'] : 0;
+ if ($usertype == 4) {
+     // Mentor - only allocated active students
+     $student_count_sql = "SELECT COUNT(s.student_id) as total
+             FROM st_student_master s
+             INNER JOIN st_mentor_student_mapping m ON s.student_id = m.student_id
+             WHERE s.status = 1 AND m.mentor_id = " . intval($logged_user_id);
+ } else {
+     $student_count_sql = "SELECT COUNT(*) as total FROM st_student_master WHERE status = 1";
+     if ($is_scoped_user && $logged_dept_id) {
+         $student_count_sql .= " AND department_id = " . intval($logged_dept_id);
+     }
+ }
+ $students_result = mysqli_query($db_handle->conn, $student_count_sql);
+ $total_students = ($students_result && $row = mysqli_fetch_assoc($students_result)) ? (int)$row['total'] : 0;
+ $rowcount = $total_students;
```

#### B. Specialization Query and Color Generation (Lines 224–285)
```diff
+ $dept_join_cond = "";
+ if ($is_scoped_user && $logged_dept_id) {
+     $dept_join_cond = " AND s.department_id = " . intval($logged_dept_id);
+ }
+
  $spec_query = "SELECT 
      sm.specialization_id,
      sm.specialization_name,
      COUNT(s.student_id) as total,
      SUM(CASE WHEN s.status = 1 THEN 1 ELSE 0 END) as approved,
      SUM(CASE WHEN s.status = 0 THEN 1 ELSE 0 END) as pending,
      0 as rejected
  FROM st_specialization_master sm
- LEFT JOIN st_student_master s ON sm.specialization_id = s.specialization_id";
- if ($is_scoped_user && $logged_dept_id) {
-     $spec_query .= " WHERE s.department_id = " . intval($logged_dept_id) . " OR s.student_id IS NULL";
- }
- $spec_query .= " GROUP BY sm.specialization_id, sm.specialization_name ORDER BY sm.specialization_id";
+ LEFT JOIN st_student_master s ON sm.specialization_id = s.specialization_id" . $dept_join_cond . "
+ GROUP BY sm.specialization_id, sm.specialization_name
+ ORDER BY sm.specialization_id ASC";
```

#### C. Limit Table Display to First 4 Tracks (Lines 682–720)
```diff
- <?php foreach ($spec_data as $id => $sd): ?>
+ <?php 
+ // Display exactly the first four configured specializations by default
+ $table_spec_data = array_slice($spec_data, 0, 4, true);
+ foreach ($table_spec_data as $id => $sd): 
+ ?>
```

#### D. Dynamic Doughnut Chart Color Palette (Lines 1140–1160)
```diff
  datasets: [{
      data: [<?php echo implode(',', $spec_totals); ?>],
-     backgroundColor: ['#00c0ef', '#f39c12', '#00a65a'],
-     hoverBackgroundColor: ['#00acd6', '#e08e0b', '#008d4c']
+     backgroundColor: [<?php echo implode(',', $spec_colors); ?>],
+     hoverBackgroundColor: [<?php echo implode(',', $spec_hover_colors); ?>]
  }]
```

---

## 4. UI & Responsive Validation

1. **Table Layout:**
   - Explicit column width percentages added (`36%` track name, `16%` numeric columns).
   - Wrapped in Bootstrap `.table-responsive` container to prevent page blowout.
   - Headers styled with AdminLTE `.bg-gray` and clear semantic badges.

2. **Cross-Resolution Performance:**
   - 1920×1080: Perfect horizontal alignment of 4 top KPI cards and side-by-side Chart/Table layout.
   - 1366×768: Clean layout with zero horizontal overflow.
   - 375×812 (Mobile): KPI cards wrap gracefully to single column, table scrolls within its container, navigation drawer collapses cleanly.
