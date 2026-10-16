# TCET ERP UI/UX User Acceptance Testing (UAT) Results
**Document ID:** UI-UAT-RESULTS-2026-10-16  
**Project:** TCET Specialization Tracker / TCET ERP  
**Location:** `C:\xampp\htdocs\st`  
**Target Page:** `Master Data → Specialization Subject` (`http://localhost/st/admin/class_crud_new.php?tab=specialization_subject-list`)  
**Tested Viewports:** 1920×1080, 1366×768, 1024×768, 768×1024, 390×844  
**Date:** 2026-10-16  
**Testing Mechanism:** Real Headless Browser Automation (Puppeteer / Chrome Subagent)  

---

## 1. Summary of UAT Execution

| Test Case | Description | Viewport | Result | Evidence Screenshot |
|---|---|---|---|---|
| **TC-UAT-01** | Desktop Full HD Form Grid & Centering | 1920 × 1080 | **PASS** | `uat_specialization_subject_1920x1080.png` |
| **TC-UAT-02** | Standard Laptop Form & Table Alignment | 1366 × 768 | **PASS** | `uat_specialization_subject_1366x768.png` |
| **TC-UAT-03** | Tablet Landscape Responsive Layout | 1024 × 768 | **PASS** | `uat_specialization_subject_1024x768.png` |
| **TC-UAT-04** | Tablet Portrait Vertical Stacking & Scrollable Tabs | 768 × 1024 | **PASS** | `uat_specialization_subject_768x1024.png` |
| **TC-UAT-05** | Mobile Viewport Single-Column Form & Overflow Safety | 390 × 844 | **PASS** | `uat_specialization_subject_390x844.png` |
| **TC-UAT-06** | Interactive Live Search Filter on Listing Table | 1366 × 768 | **PASS** | `uat_search_filter_active.png` |
| **TC-UAT-07** | Master Data Tab Switching (Class, Section, Dept, etc.) | 1366 × 768 | **PASS** | `master_class_tab_1792168845903.png` |
| **TC-UAT-08** | Required Field Asterisk & Form Label Positioning | 1366 × 768 | **PASS** | Verified across all forms |
| **TC-UAT-09** | Role-Based Authorization & 403 Redirection | All | **PASS** | `access_denied_1792168239284.png` |

---

## 2. Detailed Viewport Analysis & Verification

### TC-UAT-01: 1920 × 1080 (Desktop Full HD)
* **Observed Layout:** The `.erp-form-card` container occupies a centered max-width of `1050px` with balanced lateral margins.
* **Form Grid:** Fields (*Subject Code, Subject Name, Department, Semester, Specialization*) arrange cleanly into 2 columns (`col-md-6`).
* **Visual Evaluation:** No stretching across the 1920px screen. Content feels cohesive and matches modern enterprise standards.
* **Status:** **PASS**

### TC-UAT-02: 1366 × 768 (Standard Laptop)
* **Observed Layout:** Header, breadcrumbs, tabs, and form card maintain proportional padding.
* **Input Elements:** Heights are uniformly `38px`. Dropdown select chevrons align with text input baselines.
* **Status:** **PASS**

### TC-UAT-03: 1024 × 768 (Tablet Landscape / Small Laptop)
* **Observed Layout:** Two-column grid remains intact. Sidebar and main wrapper adjust without overlap.
* **Tab Navigation:** All 11 tabs remain accessible via smooth horizontal track.
* **Status:** **PASS**

### TC-UAT-04: 768 × 1024 (Tablet Portrait)
* **Observed Layout:** Form controls transition into responsive stacking where column width narrows below comfort threshold.
* **No Page Blowout:** Horizontal page-level scrollbar does **not** appear; only the `.erp-tabs-wrapper` scrolls horizontally.
* **Status:** **PASS**

### TC-UAT-05: 390 × 844 (Mobile Viewport)
* **Observed Layout:** Complete single-column vertical stacking (`col-sm-12`).
* **Touch Usability:** Form buttons span full width or comfortable touch sizes (>44px height). Table renders inside an isolated `.table-responsive` wrapper with smooth lateral swipe.
* **Status:** **PASS**

### TC-UAT-06: Interactive Table Search Filter
* **Action:** Typed "Honours" into `#erpLiveSearch`.
* **Behavior:** Table rows dynamically filtered in real time to match the search string without full page reload.
* **Status:** **PASS**

---

## 3. RBAC & Security Verification
* Non-super-admin user (`amitanand`, Role 2) attempting to access `class_crud_new.php` was correctly denied and redirected.
* Super-admin (`anuragmishra`, Role 1) accessed the page with full CRUD authority.
* CSRF protections, session checks, and query security remain 100% operational.
