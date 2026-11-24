# TCET Specialization Tracker — Canonical Documentation Suite

**Application:** TCET Specialization Tracker (TCET_ST)  
**Version:** 3.2.0 (Production Consolidated)  
**Runtime Database:** `tcet_st` (MariaDB 10.4.32)  
**Environment:** PHP 8.3 / Apache (XAMPP Environment)  

---

## 1. Documentation Index

The `docs/` directory contains the complete canonical technical, operational, and architectural documentation for the TCET Specialization Tracker:

| Document | File Path | Scope & Description |
|---|---|---|
| **System Architecture** | [`SYSTEM_ARCHITECTURE.md`](SYSTEM_ARCHITECTURE.md) | Component architecture, MVC structure, request pipelines, and DataTables AJAX workflows. |
| **Database Documentation** | [`DATABASE_DOCUMENTATION.md`](DATABASE_DOCUMENTATION.md) | MariaDB `tcet_st` schema, canonical tables, immutability triggers, and performance indices. |
| **User Guide** | [`USER_GUIDE.md`](USER_GUIDE.md) | Operational instructions for faculty mentors, department coordinators, and students. |
| **Administrator Guide** | [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) | Administrative workflows, user management, candidate archive governance, and institutional rules. |
| **Deployment Guide** | [`DEPLOYMENT_GUIDE.md`](DEPLOYMENT_GUIDE.md) | Production server setup, Apache VirtualHost, PHP 8.3 configuration, and backup runbooks. |
| **Testing Guide** | [`TESTING_GUIDE.md`](TESTING_GUIDE.md) | Automated regression test suites, database integrity verification, and browser UAT runbooks. |
| **Security Guide** | [`SECURITY_GUIDE.md`](SECURITY_GUIDE.md) | Multi-tiered RBAC, session isolation, CSRF/XSS defenses, and trigger-based audit immutability. |
| **Changelog** | [`CHANGELOG.md`](CHANGELOG.md) | Complete version release notes from Phase 0 to Final Production Release. |
| **Institutional Audit Evidence** | [`audit/`](audit/) | Original college source documents (`.docx`, `.xlsx`, `.pdf`) preserved for regulatory audits. |

---

## 2. Institutional Compliance & Integrity Notice

The TCET Specialization Tracker operates under strict academic council rules:
- **Single Source of Truth:** `tcet_st` is the only active runtime database.
- **Historical Candidate Archive:** 878 historical candidates are maintained in `st_historical_student_candidates` with zero automatic promotions (`APPROVED: 0`).
- **Audit Immutability:** Changes to `st_historical_candidate_audit` are permanently protected against updates and deletions by MariaDB triggers (`trg_hist_audit_no_update`, `trg_hist_audit_no_delete`).
- **Zero Public Exposure:** Direct HTTP downloads of database dumps and configurations are strictly forbidden via `.htaccess`.
