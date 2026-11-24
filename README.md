# TCET Specialization Tracker (TCET_ST)

**Version:** 3.2.0 (Production Release)  
**Institution:** Thakur College of Engineering and Technology (TCET), Mumbai  
**Runtime Database:** `tcet_st` (MariaDB 10.4.32)  
**Environment:** PHP 8.3 / Apache (XAMPP Environment)  

---

## 1. Overview

The **TCET Specialization Tracker** is an academic management application designed to track student specialization selections, honors/minor eligibility, mentor allocations, and historical candidate reconciliations.

The system enforces role-based access control (Admin, Coordinator, Mentor, Student) and protects audit trails via database-level immutability triggers.

---

## 2. Project Structure

```
st/
├── admin/                  # Administrative controllers, views, AJAX handlers, and assets
│   ├── dashboard.php       # Admin analytics and metrics overview
│   ├── student-info.php    # Active enrolled student directory
│   ├── student_info_ajax.php # Server-side DataTables AJAX processor
│   ├── student_view.php    # Student detail modal view
│   ├── historical_students.php # Historical candidate repository (878 records)
│   ├── historical_students_ajax.php # Historical archive DataTables AJAX processor
│   ├── historical_student_view.php # Client-Facing & Restricted Audit candidate modal
│   ├── historical_student_action.php # Candidate review & status action controller
│   └── export_service.php  # CSV/Excel export service
├── database/               # Database connection and persistence
│   ├── backup/             # Full verified SQL backups (.sql)
│   ├── import_review/      # Authoritative reconciliation registers (.csv)
│   ├── migrations/         # Canonical schema specifications
│   ├── db_connect.php      # Authoritative database connection pool (tcet_st)
│   └── enrollment_service.php # Transactional enrollment engine
├── docs/                   # Essential operational documentation
│   ├── ADMIN_GUIDE.md      # Administrator procedures and candidate governance
│   ├── CHANGELOG.md        # Release version history
│   ├── DATABASE_DOCUMENTATION.md # MariaDB schema, tables, triggers, and indices
│   ├── DEPLOYMENT_GUIDE.md  # Production server setup and configuration
│   ├── SECURITY_GUIDE.md   # Security architecture, RBAC, and defenses
│   ├── SYSTEM_ARCHITECTURE.md # MVC structure and request lifecycles
│   ├── TESTING_GUIDE.md    # Automated test execution runbook
│   └── USER_GUIDE.md       # Faculty and mentor operational guide
├── login/                  # Authentication forms and session initiation
├── tests/                  # Automated regression and verification suites
├── index.php               # Root entry point & router
└── .htaccess               # Apache directory security & protection rules
```

---

## 3. Quick Start (XAMPP Setup)

1. **Place Codebase:** Deploy to `C:\xampp\htdocs\st`.
2. **Start Services:** Start Apache and MySQL via the XAMPP Control Panel.
3. **Database Setup:** Ensure MariaDB contains `tcet_st`. To restore from the verified backup:
   ```cmd
   mysql -u root -p tcet_st < database\backup\tcet_st_final_backup_20261123_001718.sql
   ```
4. **Configuration:** Check connection in `database/db_connect.php`:
   ```php
   $db = "tcet_st";
   ```
5. **Access Application:**
   - Web URL: `http://localhost/st/login/`

---

## 4. Running Regression Tests

Execute the automated test suites via PHP CLI:

```cmd
# Run database integrity and historical candidate verification:
php tests/verify_db_integrity.php

# Run full HTTP endpoint and clean application tests:
php tests/test_clean_app.php

# Run complete 18-suite master regression:
php tests/run_all_tests.php

# Run headless browser UI & security test (Puppeteer):
node tests/test_redesign_and_security.js
```
