-- Migration: Semester-Wise, Subject-Wise Mentor Allocation Schema Enhancement
-- Date: 2026-10-16
-- Target: st_mentor_subject_mapping
-- Target Database: tcet_st_test (isolated test database)

ALTER TABLE `st_mentor_subject_mapping`
  ADD COLUMN IF NOT EXISTS `department_id` INT(11) DEFAULT NULL AFTER `mentor_id`,
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `semester_id`;

UPDATE `st_mentor_subject_mapping` msm
JOIN `st_specialization_subject_master` ssm ON ssm.subject_id = msm.subject_id
SET msm.department_id = ssm.department_id
WHERE msm.department_id IS NULL OR msm.department_id = 0;

UPDATE `st_mentor_subject_mapping` msm
JOIN `st_user_master` u ON u.user_id = msm.mentor_id
SET msm.department_id = u.department_id
WHERE msm.department_id IS NULL OR msm.department_id = 0;

-- Drop old unique key if present and add composite key including mentor_id
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'st_mentor_subject_mapping' AND index_name = 'unique_period_subject_mentor');
SET @sqlstmt := IF(@exist > 0, 'ALTER TABLE `st_mentor_subject_mapping` DROP INDEX `unique_period_subject_mentor`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist2 := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'st_mentor_subject_mapping' AND index_name = 'uq_mentor_period_subject');
SET @sqlstmt2 := IF(@exist2 = 0, 'ALTER TABLE `st_mentor_subject_mapping` ADD UNIQUE KEY `uq_mentor_period_subject` (`mentor_id`, `subject_id`, `semester_id`, `academic_year_id`)', 'SELECT 1');
PREPARE stmt2 FROM @sqlstmt2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @exist3 := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'st_mentor_subject_mapping' AND index_name = 'idx_alloc_lookup');
SET @sqlstmt3 := IF(@exist3 = 0, 'ALTER TABLE `st_mentor_subject_mapping` ADD KEY `idx_alloc_lookup` (`subject_id`, `semester_id`, `academic_year_id`, `is_active`)', 'SELECT 1');
PREPARE stmt3 FROM @sqlstmt3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

SET @exist4 := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'st_mentor_subject_mapping' AND index_name = 'unique_period_subject_mentor');
SET @sqlstmt4 := IF(@exist4 = 0, 'ALTER TABLE `st_mentor_subject_mapping` ADD UNIQUE KEY `unique_period_subject_mentor` (`mentor_id`, `subject_id`, `semester_id`, `academic_year_id`)', 'SELECT 1');
PREPARE stmt4 FROM @sqlstmt4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

