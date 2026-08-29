-- Development-only reset. Do not run against production.
USE `tcet_st`;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE st_mentor_student_mapping;
TRUNCATE TABLE st_mentor_subject_mapping;
TRUNCATE TABLE st_coordinator_mentor;
TRUNCATE TABLE st_coordinator;
TRUNCATE TABLE st_student_semester_history;
TRUNCATE TABLE st_enrollment;
TRUNCATE TABLE st_credit_ledger;
TRUNCATE TABLE st_offline_marks_entry;
TRUNCATE TABLE st_nptel_records;
TRUNCATE TABLE st_research_records;
TRUNCATE TABLE st_eligibility_log;
TRUNCATE TABLE st_nptel_cancellations;
TRUNCATE TABLE st_audit_log;
TRUNCATE TABLE st_login;
TRUNCATE TABLE st_user_master;
TRUNCATE TABLE st_student_master;

ALTER TABLE st_student_master AUTO_INCREMENT = 1;
ALTER TABLE st_student_semester_history AUTO_INCREMENT = 1;

INSERT INTO st_specialization_subject_master
    (subject_id, subject_name, specialization_id, department_id, semester_id, description, is_active)
VALUES
    (24, 'Artificial Intelligence', 1, 3, 3, 'CE Semester 3 test subject', 1),
    (25, 'Machine Learning', 1, 3, 4, 'CE Semester 4 test subject', 1),
    (26, 'Data Analytics', 1, 4, 3, 'IT Semester 3 test subject', 1),
    (27, 'DevOps', 1, 4, 4, 'IT Semester 4 test subject', 1),
    (28, 'Python for Data Science', 1, 12, 3, 'AIDS Semester 3 test subject', 1),
    (29, 'Deep Learning', 1, 12, 4, 'AIDS Semester 4 test subject', 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), is_active = 1;

INSERT INTO st_student_master
    (student_id, registration_no, class_id, division_id, grad_year, roll_no, department_id,
     specialization_id, specialization_subject_id, cgpa, fname, mobile, email, mark_list,
     status, m_sem1, m_sem2, m_sem3, academic_year_id, current_semester_id)
VALUES
    (9001, 'TEST-CE-001', 5, 1, 2028, 'T01', 3, 1,
     (SELECT subject_id FROM st_specialization_subject_master WHERE subject_name = 'Artificial Intelligence' AND department_id = 3 AND semester_id = 3 LIMIT 1),
     8.10, 'Test Student One', '9000000001', 'test.student1@example.com', NULL, 1, '[]', '[]', '[]', 1, 3),
    (9002, 'TEST-CE-002', 5, 1, 2028, 'T02', 3, 1,
     (SELECT subject_id FROM st_specialization_subject_master WHERE subject_name = 'Machine Learning' AND department_id = 3 AND semester_id = 4 LIMIT 1),
     8.40, 'Test Student Two', '9000000002', 'test.student2@example.com', NULL, 1, '[]', '[]', '[]', 1, 4),
    (9003, 'TEST-IT-001', 5, 1, 2028, 'T03', 4, 1,
     (SELECT subject_id FROM st_specialization_subject_master WHERE subject_name = 'Data Analytics' AND department_id = 4 AND semester_id = 3 LIMIT 1),
     7.80, 'Test Student Three', '9000000003', 'test.student3@example.com', NULL, 1, '[]', '[]', '[]', 1, 3),
    (9004, 'TEST-IT-002', 5, 1, 2028, 'T04', 4, 1,
     (SELECT subject_id FROM st_specialization_subject_master WHERE subject_name = 'DevOps' AND department_id = 4 AND semester_id = 4 LIMIT 1),
     8.00, 'Test Student Four', '9000000004', 'test.student4@example.com', NULL, 1, '[]', '[]', '[]', 1, 4),
    (9005, 'TEST-AIDS-001', 5, 1, 2028, 'T05', 12, 1,
     (SELECT subject_id FROM st_specialization_subject_master WHERE subject_name = 'Python for Data Science' AND department_id = 12 AND semester_id = 3 LIMIT 1),
     8.70, 'Test Student Five', '9000000005', 'test.student5@example.com', NULL, 1, '[]', '[]', '[]', 1, 3);

INSERT INTO st_student_semester_history
    (student_id, academic_year_id, class_id, semester_id, division_id, specialization_id,
     specialization_subject_id, cgpa, mentor_id, progress_percent, status)
SELECT student_id, academic_year_id, class_id, current_semester_id, division_id, specialization_id,
       specialization_subject_id, cgpa,
    CASE student_id WHEN 9001 THEN 11001 WHEN 9002 THEN 11002 WHEN 9003 THEN 11003 WHEN 9004 THEN 11004 WHEN 9005 THEN 11005 END,
       CASE student_id WHEN 9001 THEN 85.00 WHEN 9002 THEN 20.00 WHEN 9003 THEN 60.00 WHEN 9004 THEN 0.00 WHEN 9005 THEN 45.00 END,
       'Active'
FROM st_student_master WHERE student_id BETWEEN 9001 AND 9005;

-- Test accounts. Password for every account below is: Test@1234
INSERT INTO st_user_master (user_id, user_name, email_id, phone_number, department_id, role_id, student_id, is_first_login)
VALUES
    (10001, 'Test Student One', 'student1@test.local', '9000000001', 3, 5, 9001, 0),
    (10002, 'Test Student Two', 'student2@test.local', '9000000002', 3, 5, 9002, 0),
    (10003, 'Test Student Three', 'student3@test.local', '9000000003', 4, 5, 9003, 0),
    (10004, 'Test Student Four', 'student4@test.local', '9000000004', 4, 5, 9004, 0),
    (10005, 'Test Student Five', 'student5@test.local', '9000000005', 12, 5, 9005, 0),
    (11001, 'Test Mentor One', 'mentor1@test.local', '9100000001', 3, 4, 0, 0),
    (11002, 'Test Mentor Two', 'mentor2@test.local', '9100000002', 3, 4, 0, 0),
    (11003, 'Test Mentor Three', 'mentor3@test.local', '9100000003', 4, 4, 0, 0),
    (11004, 'Test Mentor Four', 'mentor4@test.local', '9100000004', 4, 4, 0, 0),
    (11005, 'Test Mentor Five', 'mentor5@test.local', '9100000005', 12, 4, 0, 0),
    (12001, 'Test Coordinator One', 'coordinator1@test.local', '9200000001', 3, 3, 0, 0),
    (12002, 'Test Coordinator Two', 'coordinator2@test.local', '9200000002', 3, 3, 0, 0),
    (12003, 'Test Coordinator Three', 'coordinator3@test.local', '9200000003', 4, 3, 0, 0),
    (12004, 'Test Coordinator Four', 'coordinator4@test.local', '9200000004', 4, 3, 0, 0),
    (12005, 'Test Coordinator Five', 'coordinator5@test.local', '9200000005', 12, 3, 0, 0),
    (13001, 'Test Admin One', 'admin1@test.local', '9300000001', 1, 2, 0, 0),
    (13002, 'Test Admin Two', 'admin2@test.local', '9300000002', 1, 2, 0, 0),
    (13003, 'Test Admin Three', 'admin3@test.local', '9300000003', 1, 2, 0, 0),
    (14001, 'Test Super Admin', 'superadmin@test.local', '9400000001', 1, 1, 0, 0);

INSERT INTO st_login (username, password, user_id)
VALUES
    ('student1@test.local', 'Test@1234', 10001),
    ('student2@test.local', 'Test@1234', 10002),
    ('student3@test.local', 'Test@1234', 10003),
    ('student4@test.local', 'Test@1234', 10004),
    ('student5@test.local', 'Test@1234', 10005),
    ('mentor1@test.local', 'Test@1234', 11001),
    ('mentor2@test.local', 'Test@1234', 11002),
    ('mentor3@test.local', 'Test@1234', 11003),
    ('mentor4@test.local', 'Test@1234', 11004),
    ('mentor5@test.local', 'Test@1234', 11005),
    ('coordinator1@test.local', 'Test@1234', 12001),
    ('coordinator2@test.local', 'Test@1234', 12002),
    ('coordinator3@test.local', 'Test@1234', 12003),
    ('coordinator4@test.local', 'Test@1234', 12004),
    ('coordinator5@test.local', 'Test@1234', 12005),
    ('admin1@test.local', 'Test@1234', 13001),
    ('admin2@test.local', 'Test@1234', 13002),
    ('admin3@test.local', 'Test@1234', 13003),
    ('superadmin@test.local', 'Test@1234', 14001);

INSERT INTO st_coordinator (coordinator_id, login_id)
SELECT 1, login_id FROM st_login WHERE user_id = 12001
UNION ALL SELECT 2, login_id FROM st_login WHERE user_id = 12002
UNION ALL SELECT 3, login_id FROM st_login WHERE user_id = 12003
UNION ALL SELECT 4, login_id FROM st_login WHERE user_id = 12004
UNION ALL SELECT 5, login_id FROM st_login WHERE user_id = 12005;

INSERT INTO st_coordinator_mentor (coordinator_id, mentor_id)
VALUES
    (12001, 11001), (12001, 11002),
    (12002, 11003), (12002, 11004),
    (12003, 11005), (12004, 11001), (12005, 11003);

INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id, academic_year_id)
VALUES
    (11001, 9001, 3, 1),
    (11002, 9002, 4, 1),
    (11003, 9003, 3, 1),
    (11004, 9004, 4, 1),
    (11005, 9005, 3, 1);

INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id)
SELECT 11001, subject_id FROM st_specialization_subject_master WHERE subject_name = 'Artificial Intelligence' AND department_id = 3 AND semester_id = 3 LIMIT 1;
INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id)
SELECT 11002, subject_id FROM st_specialization_subject_master WHERE subject_name = 'Machine Learning' AND department_id = 3 AND semester_id = 4 LIMIT 1;
INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id)
SELECT 11003, subject_id FROM st_specialization_subject_master WHERE subject_name = 'Data Analytics' AND department_id = 4 AND semester_id = 3 LIMIT 1;
INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id)
SELECT 11004, subject_id FROM st_specialization_subject_master WHERE subject_name = 'DevOps' AND department_id = 4 AND semester_id = 4 LIMIT 1;
INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id)
SELECT 11005, subject_id FROM st_specialization_subject_master WHERE subject_name = 'Python for Data Science' AND department_id = 12 AND semester_id = 3 LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
