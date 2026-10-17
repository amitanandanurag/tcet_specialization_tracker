# TCET ERP — Sidebar Restructuring & Master Menu Consolidation Report

**Project:** TCET Specialization Tracker (TCET ERP)  
**Location:** `C:\xampp\htdocs\st`  
**Files Modified:** `admin/header/side_menu.php`, `admin/header/header.php`, `admin/class_crud_new.php`  
**Date:** October 17, 2026  
**Status:** IMPLEMENTED, CONSOLIDATED & VERIFIED  

---

## 1. Executive Summary

In compliance with Phase D of the Master Requirements, the sidebar navigation and menu allocation architecture have been restructured:
1. **Privileged Settings Reservation:** Privileged system management submenus—**Master Data**, **Audit Log**, and **Side Menu Allocation**—are strictly reserved for **Super Admin (Role 1)**.
2. **Settings Isolation for Non-Super Admin Roles:** For Roles 2 (Admin), 3 (HOD), 4 (Mentor), and 5 (Student), privileged configuration submenus are stripped. Their Settings menu exposes only personal self-service actions (**Profile** and **Update Password**).
3. **Consolidation of Master Configuration:** Standalone submenus **Manage Section** (SubID 51), **Menu Master** (SubID 52), and **Sub Menu Master** (SubID 53) have been consolidated into the unified Master interface (`class_crud_new.php`). They no longer appear as redundant standalone sidebar items.
4. **Backend Route Guard Enforcement:** Menu filtering is enforced not only at the UI display layer, but also at the backend route guard (`checkUserRouteAuthorization`) to deny direct URL access to unprivileged roles.

---

## 2. Consolidation of Master Configuration

### 2.1 The Legacy Duplication Issue
Previously, the Settings menu in `st_sub_menu_master` contained four separate entries that pointed to the same underlying functionality:
- `Masters` (`class_crud_new.php#section-list`) — SubMenu ID 13
- `Manage Section` (`class_crud_new.php?tab=section-list`) — SubMenu ID 51
- `Menu Master` (`class_crud_new.php?tab=menu-list`) — SubMenu ID 52
- `Sub Menu Master` (`class_crud_new.php?tab=sub-menu-list`) — SubMenu ID 53

This caused severe menu bloat in the sidebar, presenting multiple redundant links to different tabs of the same master management page.

### 2.2 The Consolidation Solution
1. **Preservation of Core Data:** SubMenu IDs 51, 52, and 53 and their route targets remain intact in `st_sub_menu_master` to ensure no database integrity loss or foreign-key breakage.
2. **Allocation Table Cleanup:** Allocations of SubIDs 51, 52, and 53 were safely removed from `st_menu_allocation_master`:
   ```sql
   DELETE FROM st_menu_allocation_master WHERE sub_menu_id IN (51, 52, 53);
   ```
3. **Dynamic Sidebar Filter:** In `admin/header/side_menu.php`, SubIDs 51, 52, and 53 are explicitly excluded during tree construction:
   ```php
   if (in_array($subId, array(51, 52, 53), true)) {
       continue; // Consolidated into single Masters entry
   }
   ```
4. **Tab Integration in `class_crud_new.php`:** The single **Masters** link opens `admin/class_crud_new.php`, which houses tabbed panels for:
   - Sections & Classes (`#section-list`)
   - Departments & Branches (`#dept-list`)
   - Academic Years (`#ay-list`)
   - Menus & Submenus (`#menu-list` and `#sub-menu-list`)
   - Specializations & Subjects (`#spec-list`)

---

## 3. Super Admin-Only Settings Reservation

### 3.1 Allocations Pruning
Privileged submenus were audited and restricted:
- **Masters (SubMenu ID 13):** `class_crud_new.php`
- **Side Menu Allocation (SubMenu ID 25):** `allocation_master.php`
- **Audit Log (SubMenu ID 49):** `audit_log.php`

The seeding logic in `admin/header/side_menu.php` strictly strips these items from any non-Super Admin role:
```php
mysqli_query($db_handle->conn, "DELETE FROM st_menu_allocation_master WHERE sub_menu_id IN (13, 25, 49) AND role_id <> 1");
```

### 3.2 Dynamic Route Guard in `admin/header/header.php`
If an Admin (Role 2) or any other user attempts to bypass the sidebar and request `allocation_master.php`, `audit_log.php`, or `class_crud_new.php` directly:
```php
$superAdminOnly = array(
    'class_crud_new.php', 'class_edit_new.php', 'class_edit_new_ajax.php',
    'class_manage.php', 'class_manage_new.php', 'audit_log.php', 'allocation_master.php'
);
if (in_array($scriptName, $superAdminOnly, true)) {
    return ($roleId === 1);
}
```
HTTP 403 Forbidden is immediately served.

---

## 4. Role-Specific Sidebar Navigation Structure

### Role 1: Super Admin
- **Dashboard** (`index.php`)
- **Students**
  - Enroll (`student_admission.php`)
  - List of Students (`student-info.php`)
  - Concise Details (`student_concise_details.php`)
  - Monitor (`student_monitor.php`)
  - NPTEL Pass or Fail (`nptel_certificate.php`)
- **Admin / Staff Management**
  - Register Admin (`admin_register.php`)
  - Admin Info (`admin_info.php`)
- **Coordinator**
  - Register Coordinator (`coordinator_register.php`)
  - Coordinator Info (`coordinator_info.php`)
  - Coordinator Allocation (`coordinator_allocation.php`)
  - Mentor Subject (`mentor_subject.php`)
  - Subject Management (`specialization_subject_manage.php`)
- **Mentor**
  - Register Mentor (`mentor_register.php`)
  - Mentor Info (`mentor_info.php`)
  - Mentor Allocation (`mentor_allocation.php`)
  - Mentor Assignments (`mentor_assignment.php`)
  - Offline Marks Entry (`offline_marks_entry.php`)
- **Settings**
  - Masters (`class_crud_new.php#section-list`)
  - Profile (`profile.php`)
  - Update Password (`change_password.php`)
  - Side Menu Allocation (`allocation_master.php`)
  - Audit Log (`audit_log.php`)

### Role 2: Admin
- **Dashboard** (`index.php`)
- **Students** (Enroll, List of Students, Concise Details, Monitor, NPTEL)
- **Coordinator Management** (Register, Info, Allocation, Mentor Subject, Subject Management)
- **Mentor Management** (Register, Info, Allocation, Assignments, Offline Marks)
- **Settings**
  - Profile (`profile.php`)
  - Update Password (`change_password.php`)
  *(Privileged Masters, Side Menu Allocation, and Audit Log strictly excluded)*

### Role 3: Coordinator / HOD
- **Dashboard** (`index.php`)
- **Department Students** (Enroll, List of Students, Concise Details, NPTEL)
- **Department Coordinators & Subjects** (Mentor Subject, Subject Management)
- **Department Mentors** (Mentor Allocation, Mentor Assignments, Offline Marks Entry)
- **Settings** (Profile, Update Password)

### Role 4: Mentor
- **Dashboard** (`index.php`)
- **Students** (List of Students — filtered to assigned students only)
- **Mentor**
  - Mentor Assignments (`mentor_assignment.php` — read-only self-view)
  - Offline Marks Entry (`offline_marks_entry.php` — scoped to assigned students)
- **Settings** (Profile, Update Password)

### Role 5: Student
- **Dashboard** (`student_dashboard.php`)
- **My Admission / Enrollment** (`student_admission_view.php?id=SELF`)
- **Certifications** (NPTEL Pass or Fail)
- **Settings** (Profile, Update Password)

---

## 5. Verification Evidence

Automated test suite `tests/test_role_based_access_control.php` verified:
- `[PASS] Redundant submenus 51, 52, 53 removed from st_menu_allocation_master (Count = 0)`
- `[PASS] Privileged submenus (13, 25, 49) NOT allocated to any non-Super Admin role (Count = 0)`
- `[PASS] Super Admin (Role 1) has all 3 privileged submenus allocated (5)`
- `[PASS] Super Admin (1) ALLOWED: class_crud_new.php`
- `[PASS] Admin (2) BLOCKED from privileged: class_crud_new.php`
- `[PASS] Admin (2) BLOCKED from privileged: audit_log.php`
- `[PASS] Admin (2) BLOCKED from privileged: allocation_master.php`
- `[PASS] HOD (3), Mentor (4), Student (5) BLOCKED from privileged routes`

The sidebar and menu hierarchy are completely secure, uncluttered, and strictly role-aligned.
