-- Migration: 20261016_nptel_cancellations.sql
-- Description: Create st_nptel_cancellations table for NPTEL fail workflow and cancellation tracking

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
