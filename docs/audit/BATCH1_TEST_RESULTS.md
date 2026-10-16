# TCET Specialization Tracker — Batch 1 Test Results

**Date**: 2026-10-16  
**Test Runner**: `tests/run_all_tests.php`  
**Syntax Linter**: `tests/lint_all.php`  
**PHP Version**: 8.2.12 (CLI)  
**Database**: `tcet_st_test`  

---

## 1. Executive Summary

| Test Phase | Tests Run | Passed | Failed | Status |
| :--- | :---: | :---: | :---: | :---: |
| **PHP Syntax Linter** | 136 files | 136 | 0 | **PASS** |
| **Existing Baseline Suites** | 8 suites (240 tests) | 240 | 0 | **PASS** |
| **Targeted Batch 1 Suite** | 1 suite (20 tests) | 20 | 0 | **PASS** |
| **Aggregate Master Runner** | **9 suites (260 tests)** | **260** | **0** | **PASS** |

---

## 2. Targeted Batch 1 Test Execution Details

Test Suite: [`tests/test_batch1_critical_fixes.php`](file:///c:/xampp/htdocs/st/tests/test_batch1_critical_fixes.php)

### Section 1: P1 Password Reset Security Tests
| Test ID | Description | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| `P1-T1` | `st_password_resets` table existence check | Table exists in database | Table exists | **PASS** |
| `P1-T2` | Schema inspection for columns | Columns `token_hash`, `expires_at`, `consumed_at` present | Verified present | **PASS** |
| `P1-T3` | Cryptographic token generation | 64 hex chars with SHA-256 hash | 64 chars generated | **PASS** |
| `P1-T4` | Token insertion with 15-minute expiry | Inserted with future timestamp | Inserted, ID returned | **PASS** |
| `P1-T5` | Token consumption upon update | `consumed_at` timestamp set | Consumed successfully | **PASS** |
| `P1-T6` | Token reuse prevention | Attempt to consume already consumed token returns 0 rows | Blocked (0 rows) | **PASS** |
| `P1-T7` | Bcrypt password hashing verification | Password hashed with `$2y$` bcrypt and verifies correctly | Verified with `verifyPassword` | **PASS** |

### Section 2: P2 NPTEL Cancellation Workflow Tests
| Test ID | Description | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| `P2-T1` | `st_nptel_cancellations` table existence check | Table exists in database | Table exists | **PASS** |
| `P2-T2` | Schema inspection for columns | Columns `student_id`, `course_name`, `reason`, `status` present | Verified present | **PASS** |
| `P2-T3` | Atomic transaction insertion | Insert succeeds within transaction | Inserted, ID returned | **PASS** |
| `P2-T4` | Student cancellation retrieval | Accurate retrieval of submitted cancellation details | Matches course and reason | **PASS** |
| `P2-T5` | Student isolation check | Student B cannot view Student A's cancellations | 0 records returned for Student B | **PASS** |

### Section 3: P3 Student Admission Overwrite Bug Tests
| Test ID | Description | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| `P3-T1` | Admin user initial state check | User ID 1 has `student_id = 0` | `student_id` is 0 | **PASS** |
| `P3-T2` | First student admission (Student A) | Student A record created | Record created | **PASS** |
| `P3-T3` | Admin account protection after Student A | User ID 1 `student_id` remains 0 | `student_id` remains 0 | **PASS** |
| `P3-T4` | Second student admission (Student B) | Student B created with distinct ID | Distinct ID generated | **PASS** |
| `P3-T5` | Student A preservation check | Student A record unchanged after Student B creation | Registration No and Name intact | **PASS** |
| `P3-T6` | Student B independence check | Student B has its own independent record | Verified distinct | **PASS** |
| `P3-T7` | Admin account protection after Student B | User ID 1 `student_id` remains 0 | `student_id` remains 0 | **PASS** |
| `P3-T8` | Duplicate admission prevention | Duplicate registration numbers blocked | Uniqueness confirmed | **PASS** |

---

## 3. Aggregate Master Suite Comparison

```
Baseline Run (Pre-fix):
  Total Suites: 8
  Passed: 240
  Failed: 0

Batch 1 Post-Fix Run:
  Total Suites: 9
  Passed: 260
  Failed: 0
  Net Gain: +20 Passing Automated Regression Checks
```
