# TCET Specialization Tracker — Dashboard UAT Plan & Execution Results

**Date:** 2026-10-16  
**Test Environment:** `http://localhost/st/` | **Browser Automation:** Headless Chromium Subagent  
**Database:** `tcet_st_test` / `tcet_st` | **PHP Version:** 8.2  

---

## 1. UAT Execution Overview

User Acceptance Testing was performed against the live local application using browser automation subagents, inspecting the DOM, capturing KPI values, verifying network calls, asserting visual rendering, and testing responsive behavior across multiple viewports.

---

## 2. Test Case Results Matrix

| Test ID | Test Scenario | Preconditions | Expected Outcome | Actual Outcome | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **UAT-01** | **Dashboard Loads** | User authenticated as Admin (`amitanand`) | Dashboard loads at `admin/index.php` without PHP notices or JS errors | HTTP 200 OK, DOM fully rendered, 0 console errors, 0 failed network requests | **PASS** |
| **UAT-02** | **Student Count Accuracy** | Active students in database | Total Students card displays active students (`status = 1`) | Total Students displays 58 (active DB students), matching independent query | **PASS** |
| **UAT-03** | **Application Status Table (4 Rows)** | 25 specializations in master table | Table displays exactly 4 specialization tracks by default | Exactly 4 rows displayed: Honours, Honours w/ Research, Minor, Minor Multi | **PASS** |
| **UAT-04** | **Status Accuracy & Invariants** | Application records present | Total Applied = Approved + Pending for each track | Row 1 (Honours): 633 = 46 + 587. Rows 2-4: 2=2+0, 2=2+0, 4=4+0. Invariant holds 100% | **PASS** |
| **UAT-05** | **Specialization Chart** | Chart.js initialized | Canvas renders doughnut chart with full color palette | `#specializationChart` canvas renders smoothly with 25 distinct palette colors | **PASS** |
| **UAT-06** | **Responsive Layout (Desktop & Mobile)** | Viewports: 1920×1080, 1366×768, 375×812 | No unwanted horizontal page blowout, clean wrapping | Verified across all 3 viewports: cards stack cleanly, table responsive, 0 overflow | **PASS** |
| **UAT-07** | **RBAC & Scoped Queries** | Session authentication active | Department filtering preserves all tracks in LEFT JOIN | Non-admin accounts restricted; department-scoped query retains all tracks | **PASS** |
| **UAT-08** | **Regression Verification** | 11 test suites registered | All regression tests pass | 300 / 300 tests passed across all 11 suites | **PASS** |

---

## 3. Scenario Details & Evidence

### UAT-01: Dashboard Load & KPI Cards
- **Preconditions:** Authenticated as user `amitanand` via `http://localhost/st/login/index.php`.
- **Observed KPI Card Values:**
  - **Total Students:** `58` (active enrolled students)
  - **Total Users:** `12` (system user accounts)
  - **Total Branches:** `15` (academic departments)
  - **Mentors:** `2` (active faculty mentors)
- **Console Errors:** None (`[]`).
- **Network Errors:** None (`[]`).

### UAT-03 & UAT-04: Application Status Tracking Table
- **Row Count:** Exactly 4 rows in `<tbody>`.
- **Row 1:**
  - Name: **Honours Degree**
  - Total Applied: `633`
  - Approved: `46`
  - Pending: `587`
  - Rejected: `0`
  - Invariant: $46 + 587 = 633$ (Satisfied)
- **Row 2:**
  - Name: **Honours with Research**
  - Total Applied: `2`
  - Approved: `2`
  - Pending: `0`
  - Rejected: `0`
  - Invariant: $2 + 0 = 2$ (Satisfied)
- **Row 3:**
  - Name: **Minor Degree**
  - Total Applied: `2`
  - Approved: `2`
  - Pending: `0`
  - Rejected: `0`
  - Invariant: $2 + 0 = 2$ (Satisfied)
- **Row 4:**
  - Name: **Minor Multidisciplinary**
  - Total Applied: `4`
  - Approved: `4`
  - Pending: `0`
  - Rejected: `0`
  - Invariant: $4 + 0 = 4$ (Satisfied)

### UAT-06: Responsive Viewports
1. **Desktop 1920×1080:**
   - 4 KPI cards aligned in single 4-column row.
   - Specialization Overview doughnut chart (left, 5 cols) and Application Status table (right, 7 cols) side-by-side.
   - Visual Evidence: `login_page_1792167907611.png`, `dashboard_1920x1080_1792167926800.png`.
2. **Laptop 1366×768:**
   - Elements wrap cleanly without overlapping text or truncated badges.
   - Zero horizontal scrollbar on body.
   - Visual Evidence: `dashboard_1366x768_1792167931074.png`.
3. **Mobile 375×812:**
   - KPI cards collapse into vertical sequence.
   - Table wrapped in `.table-responsive` scrolls horizontally within table container without distorting outer layout.
   - Sidebar toggles via AdminLTE hamburger button.
   - Visual Evidence: `dashboard_mobile_375x812_1792167934884.png`.

---

## 4. Defect and Resolution Summary

No remaining defects or blockers exist. All 8 UAT scenarios passed completely.
