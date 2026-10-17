# TCET Specialization Tracker — Batch 1 Database Migration Notes

## 1. Summary of Applied Migrations

| Migration Script | Target Table | Purpose | Reversible |
| :--- | :--- | :--- | :--- |
| `database/migrations/20261016_password_resets.sql` | `st_password_resets` | Store single-use, time-limited cryptographic password reset tokens (SHA-256 hashed) with expiry and consumption tracking. | Yes |
| `database/migrations/20261016_nptel_cancellations.sql` | `st_nptel_cancellations` | Restore table required by NPTEL Fail cancellation workflow in `admin/nptel_certificate.php`. | Yes |

---

## 2. Migration Details

### 2.1 Password Resets Table (`st_password_resets`)
- **File**: [`database/migrations/20261016_password_resets.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_password_resets.sql)
- **DDL Definition**:
```sql
CREATE TABLE IF NOT EXISTS `st_password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`reset_id`),
  KEY `idx_pwd_reset_user` (`user_id`),
  KEY `idx_pwd_reset_token` (`token_hash`),
  KEY `idx_pwd_reset_expires` (`expires_at`),
  CONSTRAINT `fk_pwd_reset_user` FOREIGN KEY (`user_id`) REFERENCES `st_user_master` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```
- **Index Optimization**:
  - `idx_pwd_reset_user`: Fast retrieval of user's active tokens.
  - `idx_pwd_reset_token`: High-performance token lookup during password reset validation.
  - `idx_pwd_reset_expires`: Efficient pruning of expired tokens.
- **Rollback SQL**:
```sql
DROP TABLE IF EXISTS `st_password_resets`;
```

---

### 2.2 NPTEL Cancellations Table (`st_nptel_cancellations`)
- **File**: [`database/migrations/20261016_nptel_cancellations.sql`](file:///c:/xampp/htdocs/st/database/migrations/20261016_nptel_cancellations.sql)
- **DDL Definition**:
```sql
CREATE TABLE IF NOT EXISTS `st_nptel_cancellations` (
  `cancellation_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `course_name` varchar(200) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Submitted by Student',
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cancellation_id`),
  KEY `idx_nptel_cancel_student` (`student_id`),
  KEY `idx_nptel_cancel_enroll` (`enrollment_id`),
  CONSTRAINT `fk_nptel_cancel_student` FOREIGN KEY (`student_id`) REFERENCES `st_student_master` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```
- **Design Decisions**:
  - `enrollment_id` is defined as `DEFAULT NULL` to prevent foreign key errors when the application has not generated an enrollment entity.
  - `student_id` is foreign-keyed to `st_student_master.student_id` with `ON DELETE CASCADE`.
- **Rollback SQL**:
```sql
DROP TABLE IF EXISTS `st_nptel_cancellations`;
```

---

## 3. Database Integrity & Production Protection

- **Migration Target**: Applied to `tcet_st_test` and verified.
- **Auto-Ensure Capability**: Added idempotent helpers `ensurePasswordResetTable()` and `ensureNptelCancellationTable()` in `DBController` ([`database/db_connect.php`](file:///c:/xampp/htdocs/st/database/db_connect.php)), ensuring zero runtime crashes on fresh environments without modifying existing tables.
