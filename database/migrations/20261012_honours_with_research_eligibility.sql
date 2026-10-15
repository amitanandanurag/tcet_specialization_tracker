-- Migration: Honours with Research Track Eligibility
-- Date: 2026-10-12
-- Safe, idempotent migration preserving all existing data and relationships

UPDATE `st_specialization_master`
SET `offering_program` = 'BE', `sem_from` = 7, `sem_to` = 8
WHERE `specialization_id` = 2 AND `is_track` = 1;
