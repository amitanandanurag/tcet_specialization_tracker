-- Semester history migration (safe and non-destructive).
-- Run once against tcet_st after taking a normal database backup.

ALTER TABLE st_specialization_subject_master
  MODIFY COLUMN subject_name VARCHAR(255) NOT NULL,
  ADD COLUMN IF NOT EXISTS subject_code VARCHAR(50) DEFAULT NULL AFTER subject_name,
  ADD COLUMN IF NOT EXISTS department_id INT(11) DEFAULT NULL AFTER specialization_id,
  ADD COLUMN IF NOT EXISTS semester_id INT(11) DEFAULT NULL AFTER department_id,
  ADD COLUMN IF NOT EXISTS description VARCHAR(1000) DEFAULT NULL AFTER subject_name,
  ADD COLUMN IF NOT EXISTS subject_type VARCHAR(100) DEFAULT NULL AFTER semester_id,
  ADD COLUMN IF NOT EXISTS is_open_elective TINYINT(1) NOT NULL DEFAULT 0 AFTER subject_type,
  ADD COLUMN IF NOT EXISTS is_research_component TINYINT(1) NOT NULL DEFAULT 0 AFTER is_open_elective,
  ADD COLUMN IF NOT EXISTS academic_year_id INT(11) DEFAULT NULL AFTER is_research_component,
  ADD COLUMN IF NOT EXISTS batch_id INT(11) DEFAULT NULL AFTER academic_year_id,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description;

ALTER TABLE st_specialization_subject_master
  ADD UNIQUE KEY IF NOT EXISTS uq_subject_department_semester (department_id, semester_id, specialization_id, subject_name);

-- Existing rows remain valid historical master records. They are left without
-- a department until an administrator assigns one in the management screen.

ALTER TABLE st_mentor_student_mapping
  ADD COLUMN IF NOT EXISTS academic_year_id INT(11) NOT NULL DEFAULT 1 AFTER semester_id,
  DROP INDEX IF EXISTS uniq_student_academic_year,
  ADD UNIQUE KEY IF NOT EXISTS uniq_student_semester (student_id, semester_id);

CREATE TABLE IF NOT EXISTS st_student_semester_history (
    history_id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    academic_year_id INT(11) DEFAULT NULL,
    class_id INT(11) DEFAULT NULL,
    semester_id INT(11) NOT NULL,
    division_id INT(11) DEFAULT NULL,
    specialization_id INT(11) DEFAULT NULL,
    specialization_subject_id INT(11) DEFAULT NULL,
    minor_course_id INT(11) DEFAULT NULL,
    minor_subject_id INT(11) DEFAULT NULL,
    cgpa DECIMAL(4,2) DEFAULT NULL,
    research_component_i_id INT(11) DEFAULT NULL,
    research_core_vii VARCHAR(255) DEFAULT NULL,
    research_component_ii_id INT(11) DEFAULT NULL,
    research_core_viii VARCHAR(255) DEFAULT NULL,
    mentor_id INT(11) DEFAULT NULL,
    progress_percent DECIMAL(5,2) DEFAULT NULL,
    status ENUM('Active','Completed','Locked') NOT NULL DEFAULT 'Active',
    finalized_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (history_id),
    UNIQUE KEY uq_student_semester_history (student_id, semester_id),
    KEY idx_history_student (student_id),
    KEY idx_history_semester (semester_id),
    KEY idx_history_mentor (mentor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Older deployments already have this table without mentor/progress state.
-- These additive clauses make the migration safe for both new and existing
-- installations; no existing column or row is replaced.
ALTER TABLE st_student_semester_history
  ADD COLUMN IF NOT EXISTS mentor_id INT(11) DEFAULT NULL AFTER research_core_viii,
  ADD COLUMN IF NOT EXISTS progress_percent DECIMAL(5,2) DEFAULT NULL AFTER mentor_id,
  ADD COLUMN IF NOT EXISTS status ENUM('Active','Completed','Locked') NOT NULL DEFAULT 'Active' AFTER progress_percent,
  ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL DEFAULT NULL AFTER status;

-- Backfill the current legacy snapshot only. This never updates or deletes
-- st_student_master and does not invent unavailable prior-semester values.
INSERT INTO st_student_semester_history (
    student_id, academic_year_id, class_id, semester_id, division_id,
    specialization_id, specialization_subject_id, minor_course_id, minor_subject_id,
    cgpa, research_component_i_id, research_core_vii,
    research_component_ii_id, research_core_viii, status
)
SELECT s.student_id, s.academic_year_id, s.class_id, s.current_semester_id, s.division_id,
       s.specialization_id, s.specialization_subject_id, s.minor_course_id, s.minor_subject_id,
       s.cgpa, NULL, NULL, NULL, NULL, 'Active'
FROM st_student_master s
WHERE s.current_semester_id IS NOT NULL
  AND s.current_semester_id > 0
  AND NOT EXISTS (
      SELECT 1 FROM st_student_semester_history h
      WHERE h.student_id = s.student_id AND h.semester_id = s.current_semester_id
  );

-- Capture currently known mentor assignments without disturbing them.
UPDATE st_student_semester_history h
JOIN st_mentor_student_mapping msm
  ON msm.student_id = h.student_id AND msm.semester_id = h.semester_id
SET h.mentor_id = msm.mentor_id
WHERE h.mentor_id IS NULL;
