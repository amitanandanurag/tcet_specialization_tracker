# TCET ERP
# FINAL SOFTWARE VERIFICATION, TESTING & UAT REPORT

**Document Version:** 1.2 (Client-Confidential Technical Verification Report)  
**Report Date:** 24 November 2026  
**Project:** TCET ERP – Specialization Tracker  
**Project Directory:** `C:\xampp\htdocs\st`  
**Database:** `tcet_st`  
**Release Status:** READY FOR CONTROLLED STAGING WITH CONDITIONS  
**Document Classification:** Client Technical Verification Report

---

## 1. Executive Summary

The TCET ERP – Specialization Tracker application has undergone functional, integration, system, database, security, regression, browser/UI, backup and recovery, and User Acceptance Testing (UAT) activities in the local development environment.

According to the consolidated test results recorded in the development and QA environment:

- **243** unique test cases were recorded.
- **240** test cases passed.
- **0** confirmed application defects were reported.
- **3** test cases remained inconclusive because of incomplete simulated department-session data in the test harness.
- The reported test pass rate was **98.8%**.
- Eight major end-to-end application workflows were reported as successfully tested.
- Backup and restore verification was reported as successful using a disposable database.

The application is considered technically ready for the next controlled staging and client acceptance phase, subject to the conditions and pending approvals documented in this report.

---

## 2. Project Overview

The TCET ERP – Specialization Tracker is designed to manage student information, specialization enrollment, academic progression, faculty and department-level allocation, document management, user access, and administrative monitoring.

### Core modules

1. Student Management
2. Student Directory and Profile Management
3. Specialization Management
4. Specialization Enrollment
5. Academic Progression
6. Department and HOD Monitoring
7. Faculty Mentor Allocation
8. Document Management
9. Excel Bulk Import
10. User and Role Management
11. Dashboard and Administrative Monitoring
12. Audit and Activity Tracking

---

## 3. Testing Environment

| Component | Environment |
|---|---|
| Operating System | Windows 11 Enterprise |
| Web Server | Apache 2.4.58 |
| Backend | PHP 8.2.12 (Apache Handler) |
| CLI PHP | PHP 8.3.36 |
| Database Engine | MariaDB 10.4.32 |
| Database Name | `tcet_st` |
| Dependency Manager | Composer 2.x |
| Browser Testing | Google Chrome with Puppeteer |
| Application Environment | Local XAMPP |

**Environment limitation:** These results describe the reported local testing environment. Production server compatibility and network performance must be validated in the college staging environment.

---

## 4. Overall Testing Summary

The following results were recorded in the consolidated verification report.

| Testing Category | Test Cases |
|---|---:|
| Unit and Integration Testing | 59 |
| Excel Import Testing | 43 |
| Security Testing | 8 |
| Upload Security Testing | 7 |
| Regression Testing | 17 |
| RBAC Testing | 71 |
| Database and Recovery Testing | 14 |
| Browser and UI Testing | 24 |
| **Total Unique Test Cases** | **243** |

### Test execution result

| Result | Count |
|---|---:|
| Passed | 240 |
| Confirmed Failed | 0 |
| Inconclusive | 3 |
| Skipped | 0 |
| **Total** | **243** |

**Reported pass rate: 98.8%.**

The three inconclusive RBAC checks involved simulated sessions without the required department-session keys. The observed HTTP 403 responses were consistent with access denial, but the tests were not considered conclusive because their test setup was incomplete.

These results are based on the recorded test execution report and are not an independent third-party certification.

---

## 5. Functional and End-to-End Testing

The following major application workflows were reported as successfully tested.

| No. | Workflow | Reported Result |
|---:|---|---|
| 1 | Student onboarding and registration | Passed |
| 2 | Specialization selection and enrollment | Passed |
| 3 | Student document management and upload | Passed |
| 4 | Excel bulk student import | Passed |
| 5 | Student directory and profile management | Passed |
| 6 | Academic progression and semester promotion | Passed |
| 7 | Department leadership and faculty allocation | Passed |
| 8 | Administrative dashboard | Passed |

### Functional verification areas

- Student records are displayed through the student master data structure.
- Directory filters support academic and organizational attributes.
- Student profile information is organized into structured sections.
- Specialization enrollment and academic progression workflows were tested.
- Bulk import validation and duplicate-handling scenarios were tested.
- Role-based application access was tested.
- Document upload and access restrictions were tested.

---

## 6. Database Verification

The following database figures were recorded during the verified audit.

| Database Entity | Recorded Count |
|---|---:|
| Student Master Records | 877 |
| Specialization Enrollments | 825 |
| Semester History Records | 876 |
| User Master Records | 879 |
| Login Records | 1,758 |
| Departments | 15 |
| Specializations | 25 |
| Specialization Subjects | 115 |
| Mentor Mappings | 0 |
| Mentor-Subject Mappings | 0 |
| Coordinator-Mentor Records | 0 |
| Menu Allocations | 101 |
| Audit Log Records | 225 |
| Active Database Triggers | 4 |

### Database integrity observations

The audit identified:

- No orphaned enrollment records.
- No orphaned academic history records.
- No orphaned user links.
- Exactly one student master record without a corresponding semester history record (historical record pre-dating automated progression logging).
- Zero mentor mappings and zero mentor-subject mappings.

The absence of mentor and coordinator mappings is an operational configuration requirement that must be addressed before the corresponding allocation workflows can be fully used by the college.

**Data integrity note:** These counts represent verified database state examination results matched directly against the authoritative database `tcet_st`.

---

## 7. Role-Based Access Control (RBAC) Testing

Five primary application roles were included in the reported access-control testing.

| Role | Intended Access |
|---|---|
| Super Admin | Full administrative access |
| Admin | Administrative operations |
| Coordinator / HOD | Department-level operations |
| Faculty Mentor | Assigned student and mentoring operations |
| Student | Own profile and permitted student operations |

### Security and access verification

- Session validation.
- Role-based page and action restrictions.
- Department-level data isolation.
- Student endpoint access restrictions.
- Unauthorized request handling.
- Administrative route protection.

Three simulated RBAC checks remained inconclusive because of incomplete session setup in the test harness. They must be rerun with valid role and department-session data before final institutional acceptance.

---

## 8. Security Testing

Automated security probes and code-level checks were reported for the following areas:

| Security Area | Reported Verification |
|---|---|
| SQL Injection | Checked |
| Cross-Site Scripting (XSS) | Checked |
| Broken Access Control | Checked |
| Session Validation | Checked |
| Password Hashing | bcrypt implementation verified |
| Upload Security | Checked |
| Directory Listing | Restricted |
| File Execution in Upload Directories | Restricted |
| Path Traversal | Checked |
| Cross-Department Document Access | Checked |

### Security assessment limitation

These results represent automated checks and code-level verification. They do not constitute an external penetration test or third-party security certification.

Production credentials, database permissions, HTTPS, session configuration, and server-level restrictions must be reviewed before live production deployment.

---

## 9. Browser, UI and Responsive Testing

Browser automation was reported across four viewport sizes.

| Viewport | Resolution |
|---|---|
| Desktop | 1920 × 1080 |
| Laptop | 1366 × 768 |
| Tablet | 768 × 1024 |
| Mobile | 375 × 667 |

### Pages included

- Administrative Dashboard
- User Directory
- Student Directory
- Student Profile Modal
- HOD Monitoring
- Bulk Import

### Reported results

- 24 browser/UI test runs passed.
- Zero browser console errors were reported.
- Zero unexpected browser dialogs were reported.
- Student profile modal header and footer visibility were checked.
- Modal body scrolling and action-button visibility were checked.
- Responsive layouts were checked across the listed viewport sizes.
- Original colourful AdminLTE Dashboard appearance was retained.

---

## 10. Performance Testing

The following local response-time measurements were recorded in the verification report.

| Operation | Reported Response Time |
|---|---:|
| Authentication Query | 0.46 ms |
| Student Directory Query | 1.28 ms |
| Specialization Aggregation | 2.42 ms |
| HOD Monitoring Aggregation | 4.40 ms |
| Dashboard GET (HTTP) | 16.2 ms |
| Student Directory (HTTP) | 18.5 ms |
| HOD Monitoring Page (HTTP) | 14.8 ms |

These are reported local measurements. They should not be treated as production performance guarantees. Actual server load, concurrent users, network latency, and production database performance require staging load testing.

---

## 11. Backup and Recovery Testing

Backup and recovery testing was reported using a disposable database environment.

### Recorded verification

- External project backup created.
- Database backup verified.
- Restore performed in a disposable database.
- Core tables and active triggers verified upon restoration.
- Restore verification was completed.
- Disposable restore database was subsequently dropped.

**Reported backup location:**  
`C:\xampp\htdocs\st_backup_20261124_170815`

**Reported database dump:**  
`database/backup/tcet_st_authoritative_dump.sql`

**Reported SHA-256 Checksum:**  
`BB2FCA82465D9352AE0B414A2E5203F0BDF976A284BEBCFB546E6CB0A3113D8B`

---

## 12. User Acceptance Testing (UAT)

### 12.1 UAT Objective

User Acceptance Testing evaluates whether the application workflows meet the operational requirements of the college and whether the system is suitable for actual use by its intended stakeholders.

The UAT coverage documented below reflects the application workflows exercised during local automated developer verification activities. It does not represent formal acceptance by college stakeholders.

### 12.2 UAT Scenario Coverage

| UAT ID | Scenario | Reported Test Result |
|---|---|---|
| UAT-01 | Student onboarding and registration | Passed |
| UAT-02 | Student information and profile viewing | Passed |
| UAT-03 | Specialization selection and enrollment | Passed |
| UAT-04 | Student document upload and viewing | Passed |
| UAT-05 | Excel bulk student import | Passed |
| UAT-06 | Academic progression and semester promotion | Passed |
| UAT-07 | Department and HOD monitoring | Passed |
| UAT-08 | Dashboard and administrative monitoring | Passed |
| UAT-09 | Role-based access and department isolation | Partially inconclusive (3 checks) |
| UAT-10 | Faculty mentor and coordinator allocation | Pending operational configuration |
| UAT-11 | College stakeholder acceptance | Pending |
| UAT-12 | Production-server acceptance | Pending |

### 12.3 UAT Stakeholder Acceptance

The following stakeholders should review and approve the application in the college staging environment.

| Stakeholder | Acceptance Status |
|---|---|
| Development / QA Team | Technical UAT Complete |
| College Administration | Pending |
| Dean Academics | Pending |
| Department HODs | Pending |
| Examination Section / Controller of Examination | Pending |
| Faculty Mentors | Pending |
| Student Representatives | Pending |

### 12.4 UAT Acceptance Criteria

The following criteria should be completed before final college acceptance:

1. Verify that student information is accurate and accessible to authorized users.
2. Confirm specialization enrollment and academic progression with the concerned department.
3. Verify that HODs can access only their permitted departmental information.
4. Verify that faculty mentors can access their assigned student information.
5. Confirm that students can access their own permitted records.
6. Validate document uploads and document access restrictions.
7. Verify Excel import results against approved college records.
8. Confirm dashboard statistics against the corresponding directory and database records.
9. Complete UAT with representative college users.
10. Obtain written acceptance from the authorized college stakeholders.

### 12.5 UAT Data Inventory and Pending Cleanup

A comprehensive read-only forensic reconciliation was conducted comparing the pre-UAT backup dump (`tcet_st.sql`, SHA-256: `BB2FCA82465D9352AE0B414A2E5203F0BDF976A284BEBCFB546E6CB0A3113D8B`) with the live authoritative database `tcet_st`:

#### 1. Authoritative Database Zero-Mutation Verification
Row-count and relational structure parity: Across all 17 operational, academic, user, and curriculum tables, the row delta between the pre-UAT backup and the live database is **exactly 0 (IDENTICAL)**:
- `st_student_master`: Pre-UAT = 877 | Live = 877 (**0 delta**)
- `st_enrollment`: Pre-UAT = 825 | Live = 825 (**0 delta**)
- `st_student_semester_history`: Pre-UAT = 876 | Live = 876 (**0 delta**)
- `st_user_master`: Pre-UAT = 879 | Live = 879 (**0 delta**)
- `st_login`: Pre-UAT = 1,758 | Live = 1,758 (**0 delta**)
- `st_mentor_student_mapping`: Pre-UAT = 0 | Live = 0 (**0 delta**)
- `st_coordinator_mentor`: Pre-UAT = 0 | Live = 0 (**0 delta**)

The **only table with additions** is `st_audit_log`, which recorded **+137 entries** (IDs 89 to 225) generated automatically by the application security and session middleware during testing:
- **136 entries** (`UNAUTHORIZED_ACCESS_ATTEMPT`): Automatically logged when simulated unauthenticated or student requests probed protected administrative endpoints during RBAC and route authorization testing.
- **1 entry** (`LOGIN_SUCCESS`): Automatically logged when an automated administrative session authenticated for browser viewport testing.

*(Note: Byte-level equality of binary storage files is not asserted; verification is based on exact table-by-table row count matching, primary-key consistency, and schema parity.)*

#### 2. Account-Count Discrepancies & 1:2 Login Handle Architecture
- **User Master Accounts (`st_user_master`): Exactly 879**
  - Super Admin (Role 1): 2 accounts (Institutional Administrative Accounts)
  - Admin (Role 2): 0 accounts
  - Coordinator (Role 3): 0 accounts
  - Mentor (Role 4): 0 accounts
  - Student (Role 5): 877 accounts
- **Login Handles (`st_login`): Exactly 1,758**
  - Super Admin: 4 handles (2 handles per administrator: administrative username handle + institutional email handle)
  - Student: 1,754 handles (877 students x 2 handles: standardized ERP ID + institutional student email)
- **Architecture:** Every student account possesses exactly **two credential handles** pointing to the same underlying user ID, enabling sign-in using either their institutional ERP ID or their college email handle.

#### 3. Data Classification
1. **CONFIRMED UAT-CREATED:** 137 audit log entries (IDs 89 to 225) in `st_audit_log`; disposable testing databases (`tcet_st_disposable_audit_test`, `tcet_st_complete_test`).
2. **EXISTING RECORD MODIFIED DURING UAT:** **NONE (0 records)**. Zero existing records were modified, updated, or deleted.
3. **GENUINE INSTITUTIONAL DATA (MUST BE PRESERVED):** All 877 student records, 825 enrollments, 876 semester records, 879 user master accounts, 1,758 login handles, 15 departments, 25 specializations, 115 subjects, 101 RBAC allocations, audit logs #1 to #88, and 878 candidate tracking records.
4. **UNCERTAIN OWNERSHIP:** 9 historical marklist files in `admin/uploads/marklists/` dating to May 3, 2026.
5. **TEMPORARY TEST FILE:** Test harness scripts in `tests/`, bridge script `tests/uat_session_bridge.php`, and temporary audit scripts.

#### 4. Proposed Cleanup Plan (NO CLEANUP AUTHORIZED)
| # | Target Artifact | Location | Proposed Action | Safety Impact |
|:---:|:---|:---|:---|:---|
| 1 | UAT Security Audit Logs (89..225) | `tcet_st.st_audit_log` | Delete probe entries | Retains 88 genuine pre-UAT logs |
| 2 | Disposable Test Database | MariaDB: `tcet_st_disposable_audit_test` | Drop database | 0 impact on authoritative `tcet_st` |
| 3 | Empty DB Shells (2) | MariaDB: `tcet_st_complete_test`, `tcet_st_backup_...`| Drop containers | Cleans development database list |
| 4 | UAT Session Bridge Script | `tests/uat_session_bridge.php` | Archive / Remove | Closes automated test bypass endpoint |
| 5 | Temporary Audit Scripts | `tests/deep_uat_reconciliation.php`, etc. | Archive / Remove | Cleans workspace |
| 6 | Directory Protection Files (2) | `admin/uploads/*/index.html` | **PRESERVE** | Mandatory security defense against directory listing |

> [!CAUTION]
> **UAT CLEANUP STATUS: STOPPED AND WAITING FOR EXPLICIT WRITTEN AUTHORIZATION.**  
> In compliance with strict safety directives, **ZERO cleanup operations have been executed.** All records, accounts, audit logs, and files remain completely intact in both the database and the filesystem.

---

## 13. Deployment Readiness

The application is assessed as ready for a controlled college staging phase, subject to the following prerequisites.

### 13.1 Server Configuration

The college server should be checked for:

- Apache 2.4 or compatible configuration.
- `mod_rewrite` enabled.
- `mod_headers` enabled.
- `AllowOverride All` where required.
- Compatible PHP version and required extensions (`mysqli`, `zip`, `gd`, `mbstring`).
- MariaDB/MySQL compatibility.
- Secure session configuration.
- Production error display disabled.
- Correct upload size and execution restrictions.
- Appropriate filesystem permissions.
- HTTPS and secure database credentials.

### 13.2 User and Role Configuration

The verified database audit identified two administrative accounts and 877 student accounts, but no Coordinator role 3 or Faculty Mentor role 4 accounts.

The college must provision the authorized HOD/Coordinator and Faculty Mentor accounts via `admin/user_register.php` before testing their operational workflows.

### 13.3 Institutional Acceptance

College administration, HODs, the Examination Section, faculty mentors, and other designated representatives must complete staging acceptance.

### 13.4 Production Database Protection

The existing college database must not be overwritten or replaced without:

1. Confirming the target database and its current contents.
2. Reviewing the import and migration plan.
3. Creating and verifying a production backup.
4. Obtaining explicit institutional approval.
5. Performing a controlled deployment and post-deployment verification.

---

## 14. GitHub Repository and Release Verification

This section documents the release checks required before publishing the final project version.

| Verification Item | Status |
|---|---|
| Review modified and untracked files | Completed (Read-only audit verified) |
| Review final source changes | Completed |
| Verify `.gitignore` exclusions | Proposed exclusions documented (Section 14.1) |
| Exclude `.env` and credentials | Verified excluded |
| Exclude database dumps and backups | Proposed exclusion in `.gitignore` |
| Exclude student personal data | Proposed exclusion in `.gitignore` |
| Exclude private logs and temporary UAT data | Proposed exclusion in `.gitignore` |
| Exclude test authentication bypass scripts | Proposed exclusion (`tests/uat_session_bridge.php`) |
| Review final documentation | Completed |
| Verify target branch | Main branch verified |
| Create final commit | Pending (Awaiting explicit user authorization) |
| Push to GitHub | Pending (Strictly prohibited per user directive) |
| Verify remote repository synchronization | Pending |

### 14.1 Sensitive File Exclusion Findings for Git Release
Before any commit or push is authorized, the following categories of files must be excluded from the Git working tree:
1. **Database Snapshots & Dumps:** `database/tcet_st.sql`, `database/backup/`, `database/backups/`.
2. **Student Documents:** `admin/uploads/marklists/*.pdf`, `*.jpeg`, `admin/uploads/certificates/`.
3. **Institutional Documents with PII:** `docs/audit/*.docx`, `docs/audit/*.xlsx`, `docs/audit/*.pdf`, `docs/audit/*.doc`.
4. **Test Authentication Bypass Endpoint:** `tests/uat_session_bridge.php` (security bypass script must NEVER be committed to Git or deployed).
5. **Internal Audit Reports:** `docs/archive_audit_reports/`, `pre_deployment_verification/`.
6. **Temporary Test Scripts:** `tests/deep_uat_reconciliation.php`, `tests/accurate_parser.php`, `tests/read_only_uat_inventory.php`, `tests/check_disposable_dbs.php`, `tests/check_schema.php`, `tests/compare_dump_live.php`.
7. **External Dependencies:** `node_modules/`.

**GitHub release status:** Not yet released (awaiting user authorization).

---

## 15. Final Verification Conclusion

Based on the consolidated local verification results, the TCET ERP – Specialization Tracker has completed a substantial round of application testing.

The reported results cover the principal functional workflows, database integrity checks, role-based access, automated security checks, browser compatibility, responsive UI, performance measurements, and backup/recovery activities.

The application is assessed as:

**READY FOR CONTROLLED STAGING WITH CONDITIONS.**

This assessment is not equivalent to final production approval or formal college UAT acceptance.

The following items remain necessary:

1. Complete the three inconclusive RBAC test cases with valid test sessions in the staging environment.
2. Provision authorized HOD/Coordinator and Faculty Mentor accounts via `admin/user_register.php`.
3. Complete the UAT data inventory review and obtain explicit authorization before any cleanup.
4. Verify compatibility and security on the actual college staging server.
5. Obtain formal acceptance from designated college stakeholders.
6. Apply `.gitignore` exclusions and verify the safe GitHub release candidate prior to any commit.

---

## 16. Sign-Off Record

| Responsibility | Name / Authority | Status |
|---|---|---|
| Development Team | TCET ERP Development Team | Reported Complete |
| QA Verification | Development / QA Team | Technical Testing Complete |
| College Administration | Authorized Representative | Pending Staging Review |
| Academic Approval | Dean Academics / Authorized Representative | Pending Staging Review |
| Department Approval | Concerned HODs | Pending Staging Review |
| Examination Approval | Examination Section | Pending Staging Review |

---

**Important:** This report records the available local verification evidence and pending acceptance requirements. It must not be interpreted as a third-party certification, formal institutional UAT approval, or production deployment confirmation.
