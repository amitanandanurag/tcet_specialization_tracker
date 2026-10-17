-- Migration: Remove Invalid Mentor Subject Management Menu Allocation
-- Date: 2026-10-16
-- Issue: P10 - Fix Mentor Subject Management Navigation Allocation
-- Target Database: tcet_st_test (isolated test database)

DELETE FROM st_menu_allocation_master 
WHERE role_id = 4 
  AND sub_menu_id IN (
    SELECT sub_menu_id FROM st_sub_menu_master 
    WHERE sub_menu_route LIKE '%specialization_subject_manage.php%'
  );
