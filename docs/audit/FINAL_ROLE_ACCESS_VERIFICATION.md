# TCET ERP — Final Role-Based Access Control Verification

**Project:** TCET Specialization Tracker (TCET ERP)  
**Location:** `C:\xampp\htdocs\st`  
**Test Harnesses:**  
- `tests/test_role_based_access_control.php` (81 Matrix Unit/Integration Assertions)  
- `tests/test_browser_role_sessions.php` (29 Live Apache HTTP Assertions)  
**Target Environment:** Isolated Test DB `tcet_st_test`  
**Date:** October 17, 2026  
**Result:** 110 / 110 TESTS PASSED (100% AUTHORIZATION INTEGRITY)  

---

## 1. Role Capabilities & Security Boundary Matrix

The server-side authorization architecture validates all 10 matrix permissions across all 5 roles:

| Action / Permission | Super Admin (1) | Admin (2) | HOD (3) | Mentor (4) | Student (5) | Verification Method | Status |
|---|---|---|---|---|---|---|---|
| **View Institution-Wide Operational Data** | ALLOW | ALLOW | DENY | DENY | DENY | Unit matrix & HTTP GET | **PASS** |
| **View Assigned Department Students** | ALLOW | ALLOW | ALLOW | If assigned | Own record only | `canAccessStudent()` & SQL scoping | **PASS** |
| **View Students Assigned to Mentor** | ALLOW | ALLOW | Within dept | Own assignments | DENY | `st_mentor_student_mapping` check | **PASS** |
| **View Other Departments' Data** | ALLOW | ALLOW | DENY | DENY | DENY | `canAccessDepartment()` check | **PASS** |
| **View Other Mentors' Students** | ALLOW | ALLOW | Within dept | DENY | DENY | Assignment query check | **PASS** |
| **Access Master Configuration (`class_crud_new.php`)** | ALLOW | DENY | DENY | DENY | DENY | `checkUserRouteAuthorization()` HTTP 403 | **PASS** |
| **Access Audit Log (`audit_log.php`)** | ALLOW | DENY | DENY | DENY | DENY | `checkUserRouteAuthorization()` HTTP 403 | **PASS** |
| **Access Side Menu Allocation (`allocation_master.php`)** | ALLOW | DENY | DENY | DENY | DENY | `checkUserRouteAuthorization()` HTTP 403 | **PASS** |
| **Update Own Permitted Profile** | ALLOW | ALLOW | ALLOW | ALLOW | ALLOW | Session-bound ID + CSRF | **PASS** |
| **Change Protected Roles or Scopes** | Authorized | DENY | DENY | DENY | DENY | Backend handler restriction | **PASS** |

---

## 2. Live HTTP Session Verification Across Real Web Endpoints

Automated HTTP verification suite `tests/test_browser_role_sessions.php` tested live session requests against Apache at `http://127.0.0.1/st/`:

### 2.1 Unauthenticated Guest Requests
- `GET admin/dashboard.php` -> HTTP 302 Redirect to `login/login.php`
- `GET admin/student_dashboard.php` -> HTTP 302 Redirect to `login/login.php`
- `GET admin/class_crud_new.php` -> HTTP 302 Redirect to `login/login.php`

### 2.2 Super Admin (Role 1: `anuragmishra`)
- Login redirect -> `dashboard.php` (HTTP 302)
- `GET admin/dashboard.php` -> HTTP 200 OK
- `GET admin/class_crud_new.php` -> HTTP 200 OK
- `GET admin/audit_log.php` -> HTTP 200 OK
- `GET admin/allocation_master.php` -> HTTP 200 OK

### 2.3 Admin (Role 2: `amitanand`)
- Login redirect -> `dashboard.php` (HTTP 302)
- `GET admin/dashboard.php` -> HTTP 200 OK
- `GET admin/student-info.php` -> HTTP 200 OK
- `GET admin/branch_info.php` -> HTTP 200 OK
- `GET admin/class_crud_new.php` -> **HTTP 403 Forbidden**
- `GET admin/audit_log.php` -> **HTTP 403 Forbidden**
- `GET admin/allocation_master.php` -> **HTTP 403 Forbidden**

### 2.4 Coordinator / HOD (Role 3: `Alok Kumar`, Department 2)
- Login redirect -> `dashboard.php` (HTTP 302)
- `GET admin/dashboard.php` -> HTTP 200 OK
- `GET admin/student_view.php?id=459` (Student in Department 2) -> HTTP 200 OK
- `GET admin/student_view.php?id=1` (Student in Department 1) -> **HTTP 403 Forbidden**
- `GET admin/class_crud_new.php` -> **HTTP 403 Forbidden**

### 2.5 Mentor (Role 4: `Ashok Mishra`)
- Login redirect -> `dashboard.php` (HTTP 302)
- `GET admin/mentor_assignment.php` -> HTTP 200 OK (Read-only self-view)
- `POST admin/mentor_assignment.php` (`action=change_mentor`) -> Blocked / Error (Role 4 cannot reallocate)
- `GET admin/class_crud_new.php` -> **HTTP 403 Forbidden**
- `GET admin/admin_register.php` -> **HTTP 403 Forbidden**
- `GET admin/coordinator_register.php` -> **HTTP 403 Forbidden**

### 2.6 Student (Role 5: `Atrim Yasha`, Student ID 638)
- Login redirect -> `student_dashboard.php` (HTTP 302)
- `GET admin/student_dashboard.php` -> HTTP 200 OK
- `GET admin/profile.php` -> HTTP 200 OK
- `GET admin/admin_info.php` -> **HTTP 403 Forbidden**
- `GET admin/mentor_info.php` -> **HTTP 403 Forbidden**
- `GET admin/student-info.php` -> **HTTP 403 Forbidden**
- `GET admin/class_crud_new.php` -> **HTTP 403 Forbidden**
- Direct URL Tampering `GET admin/student_admission_view.php?id=999` -> **HTTP 403 Forbidden**
