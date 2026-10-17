-- =====================================================================
-- TCET ERP: Specialization & Course Data Schema Migration
-- Migration Date: 2026-10-12
-- Target Database: tcet_st
-- Purpose: Support full PDF course catalog, honors/minor eligibility mappings,
--          source pages, notes, academic programs, and study years.
-- =====================================================================

-- 1. Extend st_department_master to support academic program names
ALTER TABLE `st_department_master`
  ADD COLUMN IF NOT EXISTS `program_name` varchar(150) DEFAULT NULL AFTER `department_name`;

-- 2. Extend st_specialization_master to support program tracks and eligibility mappings
ALTER TABLE `st_specialization_master`
  ADD COLUMN IF NOT EXISTS `honors_eligibility` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `minor_eligibility` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `offering_program` varchar(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `department_id` int(11) DEFAULT NULL;

-- 3. Extend st_specialization_subject_master to support full curriculum details
ALTER TABLE `st_specialization_subject_master`
  ADD COLUMN IF NOT EXISTS `program_name` varchar(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `year_of_study` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `source_page` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `source_notes` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `honors_eligibility` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `minor_eligibility` text DEFAULT NULL;
