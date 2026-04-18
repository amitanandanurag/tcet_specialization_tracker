-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 12:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

--
-- Database: `tcet_st`
--

-- --------------------------------------------------------

--
-- Table structure for table `class_master`
--

CREATE TABLE `class_master` (
  `id` int(11) NOT NULL,
  `class` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



--
-- Table structure for table `st_audit_log`
--

CREATE TABLE `st_audit_log` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `affected_table` varchar(100) DEFAULT NULL,
  `affected_record` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_batch_master`
--

CREATE TABLE `st_batch_master` (
  `batch_id` int(11) NOT NULL,
  `batch_name` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_batch_master`
--

INSERT INTO `st_batch_master` (`batch_id`, `batch_name`, `date`) VALUES
(1, '2027', '2026-04-16 09:07:59'),
(2, '2028', '2026-04-16 09:08:15'),
(3, '2029', '2026-04-16 09:08:15');

-- --------------------------------------------------------

--
-- Table structure for table `st_cgpa_master`
--

CREATE TABLE `st_cgpa_master` (
  `cgpa_id` int(11) NOT NULL,
  `cgpa_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_class_master`
--

CREATE TABLE `st_class_master` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_class_master`
--

INSERT INTO `st_class_master` (`class_id`, `class_name`, `date`) VALUES
(1, 'FY', '2026-04-16 08:46:58'),
(2, 'SY', '2026-04-16 08:46:58'),
(3, 'TY', '2026-04-16 08:46:58'),
(4, 'FE', '2026-04-16 08:46:58'),
(5, 'SE', '2026-04-16 08:46:58'),
(6, 'TE', '2026-04-16 08:46:58'),
(7, 'BE', '2026-04-16 08:46:58');

-- --------------------------------------------------------

--
-- Table structure for table `st_credit_ledger`
--

CREATE TABLE `st_credit_ledger` (
  `credit_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `credits_earned` decimal(4,2) NOT NULL DEFAULT 0.00,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_department_master`
--

CREATE TABLE `st_department_master` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(150) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_department_master`
--

INSERT INTO `st_department_master` (`department_id`, `department_name`, `date`) VALUES
(1, 'Computer Application', '2026-04-16 09:31:31'),
(2, 'AIML', '2026-04-16 09:31:31'),
(3, 'Computer', '2026-04-16 09:31:31'),
(4, 'Mechanical', '2026-04-16 09:31:31');

-- --------------------------------------------------------

--
-- Table structure for table `st_division_master`
--

CREATE TABLE `st_division_master` (
  `division_id` int(11) NOT NULL,
  `division_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_eligibility_log`
--

CREATE TABLE `st_eligibility_log` (
  `eligibility_log_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `cgpa_at_check` decimal(4,2) DEFAULT NULL,
  `kt_count_at_check` int(11) NOT NULL DEFAULT 0,
  `outcome` enum('Eligible','Ineligible') NOT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_enrollment`
--

CREATE TABLE `st_enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `status` enum('Active','Suspended','Completed','Dropped') NOT NULL DEFAULT 'Active',
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_login`
--

CREATE TABLE `st_login` (
  `login_id` int(11) NOT NULL,
  `username` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_login`
--

INSERT INTO `st_login` (`login_id`, `username`, `password`, `role_id`, `user_id`, `created_at`) VALUES
(1, 'amit@tcetmumbai.in', 'Amit@1234', 2, 1, '2026-04-13 09:12:17'),
(2, 'anurag@gmail.com', 'Admin@123', 2, 2, '2026-04-14 13:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `st_mentor_student_mapping`
--

CREATE TABLE `st_mentor_student_mapping` (
  `mapping_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_menu_allocation_master`
--

CREATE TABLE `st_menu_allocation_master` (
  `menu_allocation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `sub_menu_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_menu_allocation_master`
--

INSERT INTO `st_menu_allocation_master` (`menu_allocation_id`, `user_id`, `role_id`, `menu_id`, `sub_menu_id`) VALUES
(1, 0, 1, 1, NULL),
(2, 0, 1, 1, 1),
(3, 0, 1, 1, 2),
(4, 0, 1, 1, 3),
(5, 0, 1, 1, 4),
(6, 0, 1, 1, 5),
(7, 0, 1, 2, NULL),
(8, 0, 1, 2, 6),
(9, 0, 2, 1, NULL),
(10, 0, 2, 1, 1),
(11, 0, 2, 1, 2),
(12, 0, 2, 1, 3),
(13, 0, 2, 1, 4),
(14, 0, 2, 1, 5),
(15, 0, 2, 2, NULL),
(16, 0, 2, 2, 6),
(17, 0, 3, 1, NULL),
(18, 0, 3, 1, 2),
(19, 0, 3, 1, 5),
(20, 0, 3, 2, NULL),
(21, 0, 3, 2, 6),
(22, 0, 4, 1, NULL),
(23, 0, 4, 1, 2),
(24, 0, 4, 1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `st_menu_master`
--

CREATE TABLE `st_menu_master` (
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_menu_master`
--

INSERT INTO `st_menu_master` (`menu_id`, `menu_name`) VALUES
(1, 'Students'),
(2, 'Settings');

-- --------------------------------------------------------

--
-- Table structure for table `st_minor_certificates`
--

CREATE TABLE `st_minor_certificates` (
  `certificate_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `issuing_institution` varchar(200) NOT NULL,
  `completion_date` date DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `verification_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_nptel_records`
--

CREATE TABLE `st_nptel_records` (
  `nptel_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `pass_fail` enum('Pass','Fail','Pending') NOT NULL DEFAULT 'Pending',
  `offline_exam_flag` tinyint(1) NOT NULL DEFAULT 0,
  `offline_exam_score` decimal(5,2) DEFAULT NULL,
  `offline_exam_date` date DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_research_records`
--

CREATE TABLE `st_research_records` (
  `research_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `milestone_title` varchar(200) NOT NULL,
  `milestone_status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_role_master`
--

CREATE TABLE `st_role_master` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_role_master`
--

INSERT INTO `st_role_master` (`role_id`, `role_name`) VALUES
(1, 'Super Admin'),
(2, 'Admin'),
(3, 'Coordinator / HOD'),
(4, 'Mentor'),
(5, 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `st_section_master`
--

CREATE TABLE `st_section_master` (
  `id` int(11) NOT NULL,
  `sections` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `st_section_master`
--

INSERT INTO `st_section_master` (`id`, `sections`, `date`) VALUES
(1, 'A', '2022-04-27 01:03:40'),
(2, 'B', '2022-04-27 01:04:04'),
(3, 'C', '2022-04-27 01:04:18'),
(4, 'D', '2022-04-27 01:04:26'),
(5, 'E', '2022-04-27 01:04:33'),
(6, 'F', '2022-04-27 01:04:42');

-- --------------------------------------------------------

--
-- Table structure for table `st_semester_master`
--

CREATE TABLE `st_semester_master` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_semester_master`
--

INSERT INTO `st_semester_master` (`semester_id`, `semester_name`) VALUES
(1, 'Semester 1'),
(2, 'Semester 2'),
(3, 'Semester 3'),
(4, 'Semester 4'),
(5, 'Semester 5'),
(6, 'Semester 6'),
(7, 'Semester 7'),
(8, 'Semester 8');

-- --------------------------------------------------------

--
-- Table structure for table `st_session_master`
--

CREATE TABLE `st_session_master` (
  `session_id` int(11) NOT NULL,
  `session_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_specialization_master`
--

CREATE TABLE `st_specialization_master` (
  `specialization_id` int(11) NOT NULL,
  `specialization_name` varchar(150) NOT NULL,
  `min_cgpa` decimal(4,2) NOT NULL DEFAULT 0.00,
  `kt_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `sem_from` int(11) DEFAULT NULL,
  `sem_to` int(11) DEFAULT NULL,
  `is_exclusive` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_specialization_master`
--

INSERT INTO `st_specialization_master` (`specialization_id`, `specialization_name`, `min_cgpa`, `kt_allowed`, `sem_from`, `sem_to`, `is_exclusive`) VALUES
(1, 'Honours Degree', 7.00, 0, 4, 8, 1),
(2, 'Minor Degree', 0.00, 1, NULL, NULL, 0),
(3, 'Honours with Research', 7.50, 0, 7, 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `st_specialization_subject_master`
--

CREATE TABLE `st_specialization_subject_master` (
  `subject_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `subject_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_specialization_subject_master`
--

INSERT INTO `st_specialization_subject_master` (`subject_id`, `specialization_id`, `subject_name`) VALUES
(1, 1, 'Cloud Computing'),
(2, 2, 'Software Testing');

-- --------------------------------------------------------

--
-- Table structure for table `st_student_master`
--

CREATE TABLE `st_student_master` (
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(100) NOT NULL,
  `registration_no` varchar(200) NOT NULL,
  `joining_date` varchar(100) NOT NULL,
  `class_id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `specialization_id` int(11) DEFAULT NULL,
  `specialization_subject_id` int(11) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `dob` varchar(100) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `apaar_id` varchar(100) DEFAULT NULL,
  `uan` varchar(100) DEFAULT NULL,
  `pan` varchar(100) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `mark_list` varchar(255) DEFAULT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL,
  `transfer_certificate` varchar(255) DEFAULT NULL,
  `caste_certificate` varchar(255) DEFAULT NULL,
  `migration_certificate` varchar(255) DEFAULT NULL,
  `affidavit` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_sub_menu_master`
--

CREATE TABLE `st_sub_menu_master` (
  `sub_menu_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `sub_menu_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_sub_menu_master`
--

INSERT INTO `st_sub_menu_master` (`sub_menu_id`, `menu_id`, `sub_menu_name`) VALUES
(1, 1, 'Register Students'),
(2, 1, 'List of Students'),
(3, 1, 'Left Students'),
(4, 1, 'Previous Students'),
(5, 1, 'Concise Details'),
(6, 2, 'Manage Class');

-- --------------------------------------------------------

--
-- Table structure for table `st_menu_seed_updates`
--

ALTER TABLE `st_sub_menu_master`
  ADD COLUMN IF NOT EXISTS `sort_order` int(11) NOT NULL DEFAULT 0 AFTER `menu_id`;

UPDATE `st_sub_menu_master`
SET `sort_order` = `sub_menu_id`
WHERE `sort_order` = 0;

INSERT INTO `st_menu_master` (`menu_name`)
SELECT 'Admin' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `st_menu_master` WHERE LOWER(`menu_name`) = 'admin');

INSERT INTO `st_menu_master` (`menu_name`)
SELECT 'Coordinator' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `st_menu_master` WHERE LOWER(`menu_name`) = 'coordinator');

INSERT INTO `st_menu_master` (`menu_name`)
SELECT 'Mentor' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `st_menu_master` WHERE LOWER(`menu_name`) = 'mentor');

INSERT INTO `st_sub_menu_master` (`menu_id`, `sub_menu_name`, `sort_order`)
SELECT `m`.`menu_id`, 'Register Admin', 7
FROM `st_menu_master` `m`
WHERE LOWER(`m`.`menu_name`) = 'admin'
AND NOT EXISTS (
  SELECT 1 FROM `st_sub_menu_master` `sm`
  WHERE `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) = 'register admin'
);

INSERT INTO `st_sub_menu_master` (`menu_id`, `sub_menu_name`, `sort_order`)
SELECT `m`.`menu_id`, 'Admin Info', 8
FROM `st_menu_master` `m`
WHERE LOWER(`m`.`menu_name`) = 'admin'
AND NOT EXISTS (
  SELECT 1 FROM `st_sub_menu_master` `sm`
  WHERE `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) = 'admin info'
);

INSERT INTO `st_sub_menu_master` (`menu_id`, `sub_menu_name`, `sort_order`)
SELECT `m`.`menu_id`, 'Register Coordinator', 9
FROM `st_menu_master` `m`
WHERE LOWER(`m`.`menu_name`) = 'coordinator'
AND NOT EXISTS (
  SELECT 1 FROM `st_sub_menu_master` `sm`
  WHERE `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) = 'register coordinator'
);

INSERT INTO `st_sub_menu_master` (`menu_id`, `sub_menu_name`, `sort_order`)
SELECT `m`.`menu_id`, 'Coordinator Info', 10
FROM `st_menu_master` `m`
WHERE LOWER(`m`.`menu_name`) = 'coordinator'
AND NOT EXISTS (
  SELECT 1 FROM `st_sub_menu_master` `sm`
  WHERE `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) = 'coordinator info'
);

INSERT INTO `st_sub_menu_master` (`menu_id`, `sub_menu_name`, `sort_order`)
SELECT `m`.`menu_id`, 'Register Mentor', 11
FROM `st_menu_master` `m`
WHERE LOWER(`m`.`menu_name`) = 'mentor'
AND NOT EXISTS (
  SELECT 1 FROM `st_sub_menu_master` `sm`
  WHERE `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) = 'register mentor'
);

INSERT INTO `st_sub_menu_master` (`menu_id`, `sub_menu_name`, `sort_order`)
SELECT `m`.`menu_id`, 'Mentor Info', 12
FROM `st_menu_master` `m`
WHERE LOWER(`m`.`menu_name`) = 'mentor'
AND NOT EXISTS (
  SELECT 1 FROM `st_sub_menu_master` `sm`
  WHERE `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) = 'mentor info'
);

INSERT INTO `st_sub_menu_master` (`menu_id`, `sub_menu_name`, `sort_order`)
SELECT `m`.`menu_id`, 'Manage Section', 13
FROM `st_menu_master` `m`
WHERE LOWER(`m`.`menu_name`) = 'settings'
AND NOT EXISTS (
  SELECT 1 FROM `st_sub_menu_master` `sm`
  WHERE `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) = 'manage section'
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, NULL
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'admin'
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` IS NULL
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, `sm`.`sub_menu_id`
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'admin'
JOIN `st_sub_menu_master` `sm` ON `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) IN ('register admin', 'admin info')
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` = `sm`.`sub_menu_id`
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, NULL
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'coordinator'
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` IS NULL
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, `sm`.`sub_menu_id`
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'coordinator'
JOIN `st_sub_menu_master` `sm` ON `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) IN ('register coordinator', 'coordinator info')
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` = `sm`.`sub_menu_id`
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, NULL
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'mentor'
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` IS NULL
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, `sm`.`sub_menu_id`
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'mentor'
JOIN `st_sub_menu_master` `sm` ON `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) IN ('register mentor', 'mentor info')
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` = `sm`.`sub_menu_id`
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, NULL
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'settings'
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` IS NULL
);

INSERT INTO `st_menu_allocation_master` (`user_id`, `role_id`, `menu_id`, `sub_menu_id`)
SELECT 0, `r`.`role_id`, `m`.`menu_id`, `sm`.`sub_menu_id`
FROM (SELECT 1 AS `role_id` UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) `r`
JOIN `st_menu_master` `m` ON LOWER(`m`.`menu_name`) = 'settings'
JOIN `st_sub_menu_master` `sm` ON `sm`.`menu_id` = `m`.`menu_id` AND LOWER(`sm`.`sub_menu_name`) IN ('manage class', 'manage section')
WHERE NOT EXISTS (
  SELECT 1 FROM `st_menu_allocation_master` `a`
  WHERE `a`.`user_id` = 0 AND `a`.`role_id` = `r`.`role_id` AND `a`.`menu_id` = `m`.`menu_id` AND `a`.`sub_menu_id` = `sm`.`sub_menu_id`
);

-- --------------------------------------------------------

--
-- Table structure for table `st_user_log_master`
--

CREATE TABLE `st_user_log_master` (
  `user_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_user_master`
--

CREATE TABLE `st_user_master` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(150) NOT NULL,
  `email_id` varchar(200) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_user_master`
--

INSERT INTO `st_user_master` (`user_id`, `user_name`, `email_id`, `phone_number`, `department_id`, `role_id`, `student_id`) VALUES
(1, 'Anurag Mishra', 'amit@tcetmumbai.in', '8080590516', 1, 1, 0),
(2, 'Anurag', 'anurag@gmail.com', '8080590516', 1, 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `unaided_sub`
--

CREATE TABLE `unaided_sub` (
  `id` int(11) NOT NULL,
  `sub` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unaided_sub`
--

INSERT INTO `unaided_sub` (`id`, `sub`) VALUES
(1, 'IT'),
(2, 'CS'),
(3, 'ELECTRONICS');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class_master`
--
ALTER TABLE `class_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dsms_student_master`
--
ALTER TABLE `dsms_student_master`
  ADD PRIMARY KEY (`std_id`);

--
-- Indexes for table `session_master`
--
ALTER TABLE `session_master`
  ADD PRIMARY KEY (`session_id`);

--
-- Indexes for table `st_audit_log`
--
ALTER TABLE `st_audit_log`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `st_batch_master`
--
ALTER TABLE `st_batch_master`
  ADD PRIMARY KEY (`batch_id`);

--
-- Indexes for table `st_cgpa_master`
--
ALTER TABLE `st_cgpa_master`
  ADD PRIMARY KEY (`cgpa_id`);

--
ALTER TABLE `st_class_master`
  ADD PRIMARY KEY (`class_id`);

--
-- Indexes for table `st_credit_ledger`
--
ALTER TABLE `st_credit_ledger`
  ADD PRIMARY KEY (`credit_id`);

--
ALTER TABLE `st_department_master`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `st_division_master`
--
ALTER TABLE `st_division_master`
  ADD PRIMARY KEY (`division_id`);

--
-- Indexes for table `st_eligibility_log`
--
ALTER TABLE `st_eligibility_log`
  ADD PRIMARY KEY (`eligibility_log_id`);

--
-- Indexes for table `st_enrollment`
--
ALTER TABLE `st_enrollment`
  ADD PRIMARY KEY (`enrollment_id`);

--
-- Indexes for table `st_login`
--
ALTER TABLE `st_login`
  ADD PRIMARY KEY (`login_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `st_mentor_student_mapping`
--
ALTER TABLE `st_mentor_student_mapping`
  ADD PRIMARY KEY (`mapping_id`);

--
-- Indexes for table `st_menu_allocation_master`
--
ALTER TABLE `st_menu_allocation_master`
  ADD PRIMARY KEY (`menu_allocation_id`);

--
-- Indexes for table `st_menu_master`
--
ALTER TABLE `st_menu_master`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `st_minor_certificates`
--
ALTER TABLE `st_minor_certificates`
  ADD PRIMARY KEY (`certificate_id`);

--
-- Indexes for table `st_nptel_records`
--
ALTER TABLE `st_nptel_records`
  ADD PRIMARY KEY (`nptel_id`);

--
-- Indexes for table `st_research_records`
--
ALTER TABLE `st_research_records`
  ADD PRIMARY KEY (`research_id`);

--
-- Indexes for table `st_role_master`
--
ALTER TABLE `st_role_master`
  ADD PRIMARY KEY (`role_id`);

--
ALTER TABLE `st_section_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `st_semester_master`
--
ALTER TABLE `st_semester_master`
  ADD PRIMARY KEY (`semester_id`);

--
-- Indexes for table `st_session_master`
--
ALTER TABLE `st_session_master`
  ADD PRIMARY KEY (`session_id`);

--
-- Indexes for table `st_specialization_master`
--
ALTER TABLE `st_specialization_master`
  ADD PRIMARY KEY (`specialization_id`);

--
-- Indexes for table `st_specialization_subject_master`
--
ALTER TABLE `st_specialization_subject_master`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `st_student_master`
--
ALTER TABLE `st_student_master`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `registration_no` (`registration_no`);

--
-- Indexes for table `st_sub_menu_master`
--
ALTER TABLE `st_sub_menu_master`
  ADD PRIMARY KEY (`sub_menu_id`);

--
-- Indexes for table `st_user_log_master`
--
ALTER TABLE `st_user_log_master`
  ADD PRIMARY KEY (`user_log_id`);

--
-- Indexes for table `st_user_master`
--
ALTER TABLE `st_user_master`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email_id` (`email_id`);

--
-- Indexes for table `unaided_sub`
--
ALTER TABLE `unaided_sub`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `class_master`
--
ALTER TABLE `class_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `dsms_student_master`
--
ALTER TABLE `dsms_student_master`
  MODIFY `std_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session_master`
--
ALTER TABLE `session_master`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `st_audit_log`
--
ALTER TABLE `st_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_batch_master`
--
ALTER TABLE `st_batch_master`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `st_cgpa_master`
--
ALTER TABLE `st_cgpa_master`
  MODIFY `cgpa_id` int(11) NOT NULL AUTO_INCREMENT;

--
ALTER TABLE `st_class_master`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `st_department_master`
--
ALTER TABLE `st_department_master`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `st_section_master`
--
ALTER TABLE `st_section_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `st_credit_ledger`
--
ALTER TABLE `st_credit_ledger`
  MODIFY `credit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_department_master`
--
ALTER TABLE `st_department_master`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `st_division_master`
--
ALTER TABLE `st_division_master`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_eligibility_log`
--
ALTER TABLE `st_eligibility_log`
  MODIFY `eligibility_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_enrollment`
--
ALTER TABLE `st_enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_login`
--
ALTER TABLE `st_login`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `st_mentor_student_mapping`
--
ALTER TABLE `st_mentor_student_mapping`
  MODIFY `mapping_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_menu_allocation_master`
--
ALTER TABLE `st_menu_allocation_master`
  MODIFY `menu_allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `st_menu_master`
--
ALTER TABLE `st_menu_master`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `st_minor_certificates`
--
ALTER TABLE `st_minor_certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_nptel_records`
--
ALTER TABLE `st_nptel_records`
  MODIFY `nptel_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_research_records`
--
ALTER TABLE `st_research_records`
  MODIFY `research_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_role_master`
--
ALTER TABLE `st_role_master`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `st_semester_master`
--
ALTER TABLE `st_semester_master`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `st_session_master`
--
ALTER TABLE `st_session_master`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_specialization_master`
--
ALTER TABLE `st_specialization_master`
  MODIFY `specialization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `st_specialization_subject_master`
--
ALTER TABLE `st_specialization_subject_master`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `st_student_master`
--
ALTER TABLE `st_student_master`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_sub_menu_master`
--
ALTER TABLE `st_sub_menu_master`
  MODIFY `sub_menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `st_user_log_master`
--
ALTER TABLE `st_user_log_master`
  MODIFY `user_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_user_master`
--
ALTER TABLE `st_user_master`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `unaided_sub`
--
ALTER TABLE `unaided_sub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
