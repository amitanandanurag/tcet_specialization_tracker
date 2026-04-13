-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2026 at 12:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tcet_st`
--

-- --------------------------------------------------------

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
  `class_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `department_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'amit@tcetmumbai.in', 'Amit@1234', 1, 1, '2026-04-13 09:12:17');

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

-- --------------------------------------------------------

--
-- Table structure for table `st_menu_master`
--

CREATE TABLE `st_menu_master` (
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Honours Degree (NPTEL)', 7.00, 0, 4, 8, 1),
(2, 'Minor Degree', 0.00, 1, NULL, NULL, 0),
(3, 'Honours with Research', 7.50, 0, 7, 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `st_student_details`
--

CREATE TABLE `st_student_details` (
  `student_details_id` int(11) NOT NULL,
  `student_name` varchar(200) NOT NULL,
  `department_id` int(11) NOT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `mother_name` varchar(200) DEFAULT NULL,
  `email_id` varchar(200) NOT NULL,
  `contact_no` varchar(15) DEFAULT NULL,
  `alternate_contact_no` varchar(15) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `cgpa_id` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `st_student_master`
--

CREATE TABLE `st_student_master` (
  `student_id` int(11) NOT NULL,
  `student_details_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `specialization_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL
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
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_user_master`
--

INSERT INTO `st_user_master` (`user_id`, `user_name`, `email_id`, `phone_number`, `department_id`, `role_id`) VALUES
(1, 'Anurag Mishra', 'amit@tcetmumbai.in', '8080590516', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `st_audit_log`
--
ALTER TABLE `st_audit_log`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `st_cgpa_master`
--
ALTER TABLE `st_cgpa_master`
  ADD PRIMARY KEY (`cgpa_id`);

--
-- Indexes for table `st_class_master`
--
ALTER TABLE `st_class_master`
  ADD PRIMARY KEY (`class_id`);

--
-- Indexes for table `st_credit_ledger`
--
ALTER TABLE `st_credit_ledger`
  ADD PRIMARY KEY (`credit_id`);

--
-- Indexes for table `st_department_master`
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
-- Indexes for table `st_student_details`
--
ALTER TABLE `st_student_details`
  ADD PRIMARY KEY (`student_details_id`);

--
-- Indexes for table `st_student_master`
--
ALTER TABLE `st_student_master`
  ADD PRIMARY KEY (`student_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `st_audit_log`
--
ALTER TABLE `st_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_cgpa_master`
--
ALTER TABLE `st_cgpa_master`
  MODIFY `cgpa_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_class_master`
--
ALTER TABLE `st_class_master`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_credit_ledger`
--
ALTER TABLE `st_credit_ledger`
  MODIFY `credit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_department_master`
--
ALTER TABLE `st_department_master`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `st_mentor_student_mapping`
--
ALTER TABLE `st_mentor_student_mapping`
  MODIFY `mapping_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_menu_allocation_master`
--
ALTER TABLE `st_menu_allocation_master`
  MODIFY `menu_allocation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_menu_master`
--
ALTER TABLE `st_menu_master`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `st_student_details`
--
ALTER TABLE `st_student_details`
  MODIFY `student_details_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_student_master`
--
ALTER TABLE `st_student_master`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_sub_menu_master`
--
ALTER TABLE `st_sub_menu_master`
  MODIFY `sub_menu_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_user_log_master`
--
ALTER TABLE `st_user_log_master`
  MODIFY `user_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_user_master`
--
ALTER TABLE `st_user_master`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
