# CHANGELOG — TCET SPECIALIZATION TRACKER
**Application:** TCET Specialization Tracker (TCET_ST)  
**Repository:** `C:\xampp\htdocs\st`  
**Current Release:** v3.2.0 (Production Release)  

---

## [v3.2.0] — 2026-11-23 (Production Release & Cleanup)
### Added
- **Standardized Documentation Suite:** Published 9 authoritative documents in `docs/` (`README`, `SYSTEM_ARCHITECTURE`, `DATABASE_DOCUMENTATION`, `USER_GUIDE`, `ADMIN_GUIDE`, `DEPLOYMENT_GUIDE`, `TESTING_GUIDE`, `SECURITY_GUIDE`, `CHANGELOG`).
- **Controlled Documentation Archive:** Established `docs/archive/` preserving historical phase, batch, and early UAT reports.
- **Repository Optimization:** Removed redundant rehearsal releases and obsolete scratch diagnostic scripts, significantly reducing repository footprint.

### Changed
- **Client-Facing Candidate Modal Redesign:** Overhauled historical student inspection view with responsive layout, humanized status pills, and complete omission of internal audit artifacts.
- **Restricted Audit View Isolation:** Restricted engineering audit trail to `section=restricted_audit` gated by SuperAdmin role permissions.

### Fixed
- **Student Info AJAX Zero-Record Bug:** Corrected query joins and status filtering in `admin/student_info_ajax.php` to restore full active student directory rendering.
- **DataTables Responsive Overflow:** Corrected horizontal table overflow across mobile, tablet, and desktop viewports.

---

## [v3.1.0] — 2026-11-22 (Data Consolidation & Architecture Unification)
### Changed
- **Single Database Unification:** Transitioned the application from multi-database staging dependencies to the single authoritative production database `tcet_st`.
- **Authoritative Data Cleanup:** Conducted evidence-based deletion of 29 dummy test fixtures while preserving all 878 genuine historical candidates.

### Added
- **Audit Immutability Triggers:** Implemented `trg_hist_audit_no_update` and `trg_hist_audit_no_delete` on `st_historical_candidate_audit` to block tampering.
- **Post-Cleanup Verification:** Verified 26 authoritative audit logs (IDs 5–30) and strict zero unverified student promotions (`APPROVED: 0`).

---

## [v2.2.0] — 2026-11-15 (Concurrency & Hardening)
### Added
- Transactional enrollment locking (`SELECT ... FOR UPDATE`) in `database/enrollment_service.php`.
- Concurrency test suites verifying atomic registration under load.
- CSRF validation across all student update and status change endpoints.

---

## [v2.1.0] — 2026-11-08 (Historical Archive & Data Ingestion)
### Added
- Ingestion pipeline for 878 historical candidate records across 6 academic source documents.
- Candidate status taxonomy: `HOLD` (573), `PENDING` (283), `CONDITIONAL` (22), `APPROVED` (0), `REJECTED` (0).
- Historical student directory interface (`admin/historical_students.php`).

---

## [v1.0.0] — 2026-10-25 (Initial Release)
### Added
- Core student master directory (`st_student_master`) and specialization tracking (`st_enrollment`).
- Role-based user authentication and dashboard analytics.
- Export services for student marklists and mentor allocations.
