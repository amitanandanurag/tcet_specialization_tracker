-- Migration: Separate Specialization Track from Specialization Name
-- Date: 2026-10-12
-- Safe, idempotent migration preserving all existing data and relationships

-- 1. Add is_track and track_code to st_specialization_master if not exists
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_specialization_master' AND COLUMN_NAME = 'is_track');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `st_specialization_master` ADD COLUMN `is_track` TINYINT(1) NOT NULL DEFAULT 0 AFTER `specialization_name`, ADD COLUMN `track_code` VARCHAR(50) NULL AFTER `is_track`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mark existing tracks
UPDATE `st_specialization_master` SET `is_track` = 1, `track_code` = 'honours' WHERE `specialization_id` = 1;
UPDATE `st_specialization_master` SET `is_track` = 1, `track_code` = 'honours_research' WHERE `specialization_id` = 2;
UPDATE `st_specialization_master` SET `is_track` = 1, `track_code` = 'minor' WHERE `specialization_id` = 3;
UPDATE `st_specialization_master` SET `is_track` = 1, `track_code` = 'minor_multidisciplinary' WHERE `specialization_id` = 4;
UPDATE `st_specialization_master` SET `is_track` = 0 WHERE `specialization_id` >= 5;

-- 2. Add specialization_track_id to st_student_master if not exists
SET @col_exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_student_master' AND COLUMN_NAME = 'specialization_track_id');
SET @sql2 = IF(@col_exists2 = 0, 'ALTER TABLE `st_student_master` ADD COLUMN `specialization_track_id` INT(11) NULL DEFAULT NULL AFTER `department_id`', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Backfill legacy students where specialization_id was used as track
UPDATE `st_student_master` 
SET `specialization_track_id` = `specialization_id` 
WHERE `specialization_id` IN (1, 2, 3, 4) 
  AND (`specialization_track_id` IS NULL OR `specialization_track_id` = 0);

-- 3. Add specialization_track_id to st_student_semester_history if not exists
SET @col_exists3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_student_semester_history' AND COLUMN_NAME = 'specialization_track_id');
SET @sql3 = IF(@col_exists3 = 0, 'ALTER TABLE `st_student_semester_history` ADD COLUMN `specialization_track_id` INT(11) NULL DEFAULT NULL AFTER `department_id`', 'SELECT 1');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- Backfill legacy history records
UPDATE `st_student_semester_history` 
SET `specialization_track_id` = `specialization_id` 
WHERE `specialization_id` IN (1, 2, 3, 4) 
  AND (`specialization_track_id` IS NULL OR `specialization_track_id` = 0);
