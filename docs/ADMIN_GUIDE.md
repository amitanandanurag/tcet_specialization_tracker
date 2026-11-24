# ADMINISTRATOR GUIDE — TCET SPECIALIZATION TRACKER
**Audience:** Institute Administrators, Academic Registrars, Examination Cell Heads  
**Application:** TCET Specialization Tracker (TCET_ST)  
**Runtime Database:** `tcet_st`  
**Version:** 3.2.0  

---

## 1. Administrative Overview

The TCET Specialization Tracker grants institute administrators end-to-end control over student specialization cohorts, historical reconciliations, institutional audits, and data governance.

---

## 2. Managing the Historical Candidate Archive

Access the repository via **Historical Students Archive** (`admin/historical_students.php`).

### 2.1 Candidate Registry & Metrics
The archive contains 878 legacy candidate records ingested from historical notices and Excel rosters.
- **Current Regularization Breakdown:**
  - `HOLD`: 573 candidates awaiting formal departmental clearance.
  - `PENDING`: 283 candidates undergoing document verification.
  - `CONDITIONAL`: 22 candidates provisionally cleared.
  - `APPROVED`: 0 candidates (Strict rule: zero automatic promotions to active status without College Council resolution).

### 2.2 Client-Facing vs. Curated Inspection
- When presenting to stakeholders, accreditation teams, or faculty, click the **Inspect** button next to any candidate.
- The **Client-Facing View** displays clean academic credentials, declared specialization tracks, and reconciliation status without internal technical markers.

### 2.3 Accessing the Restricted Audit Trail
- To review technical compliance, system changes, or audit records:
  - System engineers and Super Administrators append `&section=restricted_audit` to the modal request URL.
  - The modal then renders the complete historical audit log from `st_historical_candidate_audit` (IDs 5–30).
  - This interface is strictly protected by session verification and blocked to standard users.

---

## 3. Data Governance & Institutional Promotion Rules

1. **Strict Zero-Promotion Protocol:**
   - Under no circumstances should an administrator manually promote historical candidates to `st_student_master` or assign them active ERP IDs without signed approval from the TCET Academic Council.
2. **Audit Immutability:**
   - Any status change recorded in `st_historical_candidate_audit` is permanently sealed by database triggers (`trg_hist_audit_no_update`, `trg_hist_audit_no_delete`).
   - Attempts to alter or delete audit records via SQL or web interface will be blocked with an SQLSTATE 45000 exception.

---

## 4. User Role Management

Administrators can grant or revoke role privileges via the administrative settings:
- **SuperAdmin / Admin:** Full configuration, archive access, restricted audit review, export rights.
- **Faculty Coordinator:** Directory viewing, branch filtering, export access.
- **Mentor:** Read-only access to assigned student cohorts.
- **Student:** View own profile and enrolled specialization course progress.
