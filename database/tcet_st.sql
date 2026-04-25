-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 11:12 AM
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

--
-- Dumping data for table `st_audit_log`
--

INSERT INTO `st_audit_log` (`audit_id`, `user_id`, `action_type`, `affected_table`, `affected_record`, `description`, `performed_at`) VALUES
(1, 1, 'LOGIN_SUCCESS', 'st_login', 1, 'User \'amit@tcetmumbai.in\' logged in successfully with role 1 from IP ::1. Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 13:40:46'),
(2, 1, 'LOGIN_SUCCESS', 'st_login', 1, 'User \'amit@tcetmumbai.in\' logged in successfully with role 1 from IP ::1. Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 13:41:49'),
(3, 0, 'LOGIN_FAILED', 'st_login', NULL, 'Login failed for username \'superadmin@tcetmumbai.in\' from IP ::1. Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 08:56:44'),
(4, 1, 'LOGIN_SUCCESS', 'st_login', 1, 'User \'superadmin@tcetmumbai.in\' logged in successfully with role 1 from IP ::1. Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 08:58:11'),
(5, 1, 'LOGIN_SUCCESS', 'st_login', 1, 'User \'superadmin@tcetmumbai.in\' logged in successfully with role 1 from IP ::1. Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 09:03:30');

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
(1, 'MCA', '2026-04-22 10:19:07'),
(2, 'AIML', '2026-04-16 09:31:31'),
(3, 'CE', '2026-04-22 10:16:32'),
(4, 'IT', '2026-04-22 10:16:38'),
(5, 'EXTC', '2026-04-22 10:16:45'),
(6, 'ECS', '2026-04-22 10:18:15'),
(7, 'MECH', '2026-04-22 10:18:15'),
(8, 'CIVIL', '2026-04-22 10:18:15'),
(9, 'CSE-CS', '2026-04-22 10:18:15'),
(10, 'MME', '2026-04-22 10:18:15'),
(11, 'BCA', '2026-04-22 10:19:55'),
(12, 'AIDS', '2026-04-22 10:19:55'),
(13, 'IOT', '2026-04-22 10:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `st_division_master`
--

CREATE TABLE `st_division_master` (
  `division_id` int(11) NOT NULL,
  `division_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_division_master`
--

INSERT INTO `st_division_master` (`division_id`, `division_name`) VALUES
(1, 'A'),
(2, 'B'),
(3, 'C'),
(4, 'D'),
(5, 'E'),
(6, 'F');

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
(1, 'superadmin@tcetmumbai.in', 'Amit@1234', 1, 1, '2026-04-23 09:44:44'),
(2, 'admin@tcetmumbai.in', 'Amit@1234', 2, 2, '2026-04-23 09:45:37'),
(3, 'coordinator@tcetmumbai.in', 'Amit@1234', 3, 3, '2026-04-23 09:48:36'),
(4, 'mentor@tcetmumbai.in', 'Amit@1234', 4, 4, '2026-04-23 09:48:36'),
(5, 'student@tcetmumbai.in', 'Amit@1234', 5, 5, '2026-04-23 09:50:00');

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
(6, 0, 1, 1, 5),
(7, 0, 1, 2, NULL),
(17, 0, 3, 1, NULL),
(18, 0, 3, 1, 2),
(19, 0, 3, 1, 5),
(20, 0, 3, 2, NULL),
(22, 0, 4, 1, NULL),
(23, 0, 4, 1, 2),
(24, 0, 4, 1, 5),
(25, 0, 1, 3, NULL),
(27, 0, 3, 3, NULL),
(28, 0, 4, 3, NULL),
(32, 0, 1, 3, 7),
(34, 0, 3, 3, 7),
(35, 0, 4, 3, 7),
(36, 0, 1, 3, 8),
(38, 0, 3, 3, 8),
(39, 0, 4, 3, 8),
(47, 0, 1, 4, NULL),
(49, 0, 3, 4, NULL),
(50, 0, 4, 4, NULL),
(54, 0, 1, 4, 9),
(56, 0, 3, 4, 9),
(57, 0, 4, 4, 9),
(58, 0, 1, 4, 10),
(60, 0, 3, 4, 10),
(61, 0, 4, 4, 10),
(69, 0, 1, 5, NULL),
(71, 0, 3, 5, NULL),
(72, 0, 4, 5, NULL),
(76, 0, 1, 5, 11),
(78, 0, 3, 5, 11),
(79, 0, 4, 5, 11),
(80, 0, 1, 5, 12),
(82, 0, 3, 5, 12),
(83, 0, 4, 5, 12),
(91, 0, 4, 2, NULL),
(93, 0, 1, 2, 13),
(95, 0, 3, 2, 13),
(96, 0, 4, 2, 13),
(147, 0, 1, 2, 20),
(148, 0, 1, 2, 21),
(149, 0, 1, 2, 22),
(150, 0, 1, 2, 23),
(151, 0, 1, 2, 24),
(152, 0, 1, 2, 25),
(153, 0, 1, 5, 13),
(154, 0, 1, 5, 20),
(155, 0, 1, 5, 21),
(156, 0, 1, 5, 23),
(157, 0, 1, 5, 24),
(158, 0, 1, 5, 25),
(159, 0, 1, 5, 46),
(224, 0, 2, 1, 1),
(225, 0, 2, 1, NULL),
(226, 0, 2, 1, 2),
(227, 0, 2, 2, 7),
(228, 0, 2, 2, NULL),
(229, 0, 2, 2, 8),
(230, 0, 2, 3, 9),
(231, 0, 2, 3, NULL),
(232, 0, 2, 3, 10),
(233, 0, 2, 4, 11),
(234, 0, 2, 4, NULL),
(235, 0, 2, 4, 12),
(236, 0, 2, 5, 13),
(237, 0, 2, 5, NULL),
(238, 0, 2, 5, 20),
(239, 0, 2, 5, 21),
(240, 0, 1, 5, 47),
(241, 0, 1, 4, 48);

-- --------------------------------------------------------

--
-- Table structure for table `st_menu_master`
--

CREATE TABLE `st_menu_master` (
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(100) NOT NULL,
  `menu_icon` varchar(100) NOT NULL DEFAULT 'fa fa-folder'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_menu_master`
--

INSERT INTO `st_menu_master` (`menu_id`, `menu_name`, `menu_icon`) VALUES
(1, 'Students', 'fa fa-graduation-cap'),
(2, 'Admin', 'fa fa-cogs'),
(3, 'coordinator', 'fa fa-user-secret'),
(4, 'mentor', 'fa fa-users'),
(5, 'Settings', 'fa fa-user');

-- --------------------------------------------------------

--
-- Table structure for table `st_minorcourse`
--

CREATE TABLE `st_minorcourse` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `course_type` varchar(50) NOT NULL,
  `coordinator` varchar(100) DEFAULT NULL,
  `total_credits` int(11) DEFAULT 18
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_minorcourse`
--

INSERT INTO `st_minorcourse` (`course_id`, `course_name`, `course_type`, `coordinator`, `total_credits`) VALUES
(1, 'Performing Arts - Music', 'Certification', NULL, 18),
(2, 'Performing Arts - Dance', 'Certification', NULL, 18),
(3, 'Performing Arts - Drama', 'Certification', NULL, 18),
(4, 'Life Sciences - Science of Energy', 'MOOCs', 'Dr. Rajni Bahuguna', 18),
(5, 'Life Sciences - Biotechnology', 'MOOCs', NULL, 18),
(6, 'Mathematical Computing', 'MOOCs', 'Dr. Vivek Bharatiya', 18),
(7, 'Finance Management', 'MOOCs', 'Mr. Sudhir Mundra', 18),
(8, 'Life Skills - Health & Nutrition', 'Institute', NULL, 18),
(9, 'Life Skills - Social & Welfare', 'MOOCs', 'Dr. Vinita Gupta', 18),
(10, 'Life Skills - Physical Education (NCC)', 'Institute', 'Commandar Vijaypratap Singh', 18);

-- --------------------------------------------------------

--
-- Table structure for table `st_minorsubject`
--

CREATE TABLE `st_minorsubject` (
  `subject_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `duration` varchar(20) DEFAULT '12 weeks',
  `detail` text DEFAULT NULL,
  `credits` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_minorsubject`
--

INSERT INTO `st_minorsubject` (`subject_id`, `course_id`, `semester_id`, `subject_name`, `duration`, `detail`, `credits`) VALUES
(1, 1, 3, 'Raga studies', '12 weeks', NULL, 3),
(2, 1, 4, 'Study of Tala', '12 weeks', NULL, 3),
(3, 1, 5, 'Hindustani music', '12 weeks', NULL, 3),
(4, 1, 6, 'Folk music', '12 weeks', NULL, 3),
(5, 1, 7, 'Western music', '12 weeks', NULL, 3),
(6, 2, 3, 'History of Dance', '12 weeks', NULL, 3),
(7, 2, 4, 'Indian Culture', '12 weeks', NULL, 3),
(8, 2, 5, 'Techniques of Dance', '12 weeks', NULL, 3),
(9, 2, 6, 'Performance Practice', '12 weeks', NULL, 3),
(10, 2, 7, 'Dance on Camera', '12 weeks', NULL, 3),
(11, 3, 3, 'Indian theatre', '12 weeks', NULL, 3),
(12, 3, 4, 'Basic vocal practice', '12 weeks', NULL, 3),
(13, 3, 5, 'Event management', '12 weeks', NULL, 3),
(14, 3, 6, 'Camera, light, sound', '12 weeks', NULL, 3),
(15, 3, 7, 'Projects on short films', '12 weeks', NULL, 3),
(16, 4, 3, 'Non-Conventional Energy Resources', '12 weeks', 'https://nptel.ac.in/courses/121106014', 3),
(17, 4, 4, 'Waste to Energy Conversion', '12 weeks', 'https://nptel.ac.in/courses/103107125', 3),
(18, 4, 5, 'Mass Momentum And Energy Balances In Engineering Analysis', '12 weeks', 'https://nptel.ac.in/courses/105105186', 3),
(19, 4, 6, 'Renewable Energy Engineering: Solar, Wind and Biomass Energy Systems', '12 weeks', 'https://nptel.ac.in/courses/103103206', 3),
(20, 4, 7, 'Energy Efficiency, Acoustics and Daylighting in Building', '12 weeks', 'https://nptel.ac.in/courses/105102175', 3),
(21, 4, 8, 'Energy Resources, Economics and Environment', '12 weeks', 'https://nptel.ac.in/courses/109101171', 3),
(22, 5, 3, 'Basics of Biology', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_bt25', 3),
(23, 5, 4, 'Structural Biology', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_bt23', 3),
(24, 5, 5, 'Aspects Of Biochemical Engineering', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_bt08', 3),
(25, 5, 6, 'Computational Systems Biology', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_bt14', 3),
(26, 5, 7, 'Bioinformatics: Algorithms and Applications', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_bt12', 3),
(27, 5, 8, 'Material and Energy Balances', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_bt16', 3),
(28, 6, 3, 'Essentials of Data Science with R Software-1: Probability & Statistical Inference', '12 weeks', 'https://archive.nptel.ac.in/courses/111/104/111104146/', 3),
(29, 6, 4, 'Essentials Of Data Science With R Software-2: Sampling Theory And Linear Regression Analysis', '12 weeks', 'https://archive.nptel.ac.in/courses/111/104/111104147/', 3),
(30, 6, 5, 'Numerical Linear Algebra', '12 weeks', 'https://archive.nptel.ac.in/courses/111/107/111107106/', 3),
(31, 6, 6, 'Convex Optimization', '12 weeks', 'https://archive.nptel.ac.in/courses/111/104/111104068/', 3),
(32, 6, 7, 'Regression Analysis', '12 weeks', 'https://archive.nptel.ac.in/courses/111/105/111105042/', 3),
(33, 6, 8, 'Applied Multivariate Statistical Modelling', '12 weeks', 'https://archive.nptel.ac.in/courses/111/105/111105091/', 3),
(34, 7, 3, 'Financial Statement Analysis and Reporting', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_mg12', 3),
(35, 7, 4, 'Financial Accounting', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_mg65', 3),
(36, 7, 5, 'Financial Derivatives & Risk Management', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_mg34', 3),
(37, 7, 6, 'Financial Institutions And Markets', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_mg46', 3),
(38, 7, 7, 'Safety and Risk Analytics', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_mg48', 3),
(39, 7, 8, 'Artificial Intelligence (AI) for Investments', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_mg63', 3),
(40, 8, 3, 'Basic Food Science-I', '12 weeks', 'Basic concept on Food, Nutrition and Nutrients, Carbohydrates, Lipids, Proteins', 3),
(41, 8, 4, 'Basic Food Science-II', '12 weeks', 'Dietary Fibre, Minerals & Trace Elements, Vitamins, Water, Methods of Cooking and Preventing Nutrient Losses', 3),
(42, 8, 5, 'Human Nutrition-I', '12 weeks', 'Minimum Nutritional Requirement, Energy in Human Nutrition, Growth & Development from infancy to adulthood, Growth monitoring and promotion', 3),
(43, 8, 6, 'Human Nutrition-II', '12 weeks', 'Nutrition During Pregnancy, Nutrition during Lactation, Nutrition during Infancy', 3),
(44, 8, 7, 'Therapeutic Nutrition', '12 weeks', 'Principles of nutrition care, Etiology, Food allergy and food intolerance', 3),
(45, 8, 8, 'Public Health Nutrition', '12 weeks', 'Introduction to Nutritional deficiency diseases, Causes, Social Health problems, Nutrition for Special conditions, Food Security', 3),
(46, 9, 3, 'Concept of Society and Social Issues in India - Social Justice & Regional Imbalance', '12 weeks', 'https://onlinecourses.swayam2.ac.in/cec21_hs31', 3),
(47, 9, 4, 'Woman Empowerment - Gender Justice and Workplace Security', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc20_mg42', 3),
(48, 9, 5, 'Health, Hygiene and Diseases - Disaster Management', '12 weeks', 'http://ecoursesonline.iasri.res.in/course/view.php?id=187', 3),
(49, 9, 6, 'Environment Education for Sustainable Development', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc22_hs61/preview', 3),
(50, 9, 7, 'Youth Empowerment Programs - Education & Awareness Activities', '12 weeks', 'https://onlinecourses.nptel.ac.in/noc23_hs74/preview | https://onlinecourses.swayam2.ac.in/aic23_ge05/preview', 3),
(51, 9, 8, 'Voluntary Organization (VOs) and Government Organization (GOs)', '12 weeks', 'https://nptel.ac.in/courses/110106141', 3),
(52, 10, 1, 'NCC Programme I', '12 weeks', 'NCC General, National Integration-1, National Integration-2', 3),
(53, 10, 2, 'NCC Programme II', '12 weeks', 'Personality Development, Leadership, Communication, Health & Hygiene, Nutrition, Social Service, Community Development', 3),
(54, 10, 3, 'NCC Programme III', '12 weeks', 'Disaster Management, Adventure, Border & Coastal Area', 3),
(55, 10, 4, 'NCC Programme IV', '12 weeks', 'Environmental Awareness and Conservation, General Awareness, Armed Forces', 3),
(56, 10, 5, 'NCC Programme V', '12 weeks', 'Obstacle Training, Defence Entrance Examination & SSB Training, Government New Initiatives, Infantry Weapons', 3),
(57, 10, 6, 'NCC Programme VI', '12 weeks', 'Communication, Emotional Intelligence, Defence Entrance Exams and SSB Training, Indian Armed Forces, Medical and Paramedical Services of Armed Forces', 3);

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
-- Table structure for table `st_offline_marks_entry`
--

CREATE TABLE `st_offline_marks_entry` (
  `entry_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `nptel_status` enum('Pass','Fail') NOT NULL,
  `nptel_exam_score` decimal(5,2) DEFAULT NULL,
  `nptel_assignment_raw` decimal(5,2) DEFAULT NULL,
  `nptel_assignment_converted` decimal(5,2) DEFAULT NULL,
  `ise1_marks` decimal(5,2) DEFAULT NULL,
  `ise2_marks` decimal(5,2) DEFAULT NULL,
  `ese_written_marks` decimal(5,2) DEFAULT NULL,
  `college_total_score` decimal(6,2) DEFAULT NULL,
  `final_score` decimal(6,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(1, 'SUPER ADMIN'),
(2, 'ADMIN'),
(3, 'COORDINATOR / HOD'),
(4, 'MENTOR'),
(5, 'STUDENT');

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
(6, 'F', '2022-04-27 01:04:42'),
(16, 'G', '2026-04-17 12:57:33'),
(18, 'H', '2026-04-17 13:00:41');

-- --------------------------------------------------------

--
-- Table structure for table `st_semester`
--

CREATE TABLE `st_semester` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_semester`
--

INSERT INTO `st_semester` (`semester_id`, `semester_name`) VALUES
(1, 'Semester I'),
(2, 'Semester II'),
(3, 'Semester III'),
(4, 'Semester IV'),
(5, 'Semester V'),
(6, 'Semester VI'),
(7, 'Semester VII'),
(8, 'Semester VIII');

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

--
-- Dumping data for table `st_session_master`
--

INSERT INTO `st_session_master` (`session_id`, `session_name`) VALUES
(1, '2025 -2026'),
(2, '2026 -2027');

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
(2, 'Honours with Research', 7.50, 0, 7, 8, 1),
(3, 'Minor Degree', 7.00, 1, NULL, NULL, 0),
(4, 'Minor Multidisciplinary', 0.00, 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `st_specialization_subject_master`
--

CREATE TABLE `st_specialization_subject_master` (
  `subject_id` int(11) NOT NULL,
  `subject_name` text NOT NULL,
  `specialization_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_specialization_subject_master`
--

INSERT INTO `st_specialization_subject_master` (`subject_id`, `subject_name`, `specialization_id`) VALUES
(1, 'Artificial Intelligence and Machine Learning', 1),
(2, 'Data Science', 1),
(3, 'Advance Web Development', 1),
(4, 'Advanced Cyber secur4y and Quantum Cryptography', 1),
(5, 'Cyber Security', 1),
(6, 'Finance Management', 1),
(7, 'Sector Specific Specialization in Artificial Intelligence', 1),
(8, 'Innovation, Entrepreneurial and Venture Development', 1),
(9, 'Blockchain', 1),
(10, 'Business Development, Marketing and Finance', 1),
(11, 'VLSI Design & Technology', 1),
(12, 'Sector Specific Specialization in 13', 1),
(13, '3D Printing', 1),
(14, 'Internet of Things', 1),
(15, 'Railway Technology', 1),
(16, 'Energy Engineering', 1),
(17, 'Infrastructure Engineering', 1),
(18, 'Green Technology and Sustainability', 1),
(19, 'Robotics', 1),
(20, 'Electric Vehicle Technology', 1),
(21, 'Mathematical Computing', 1),
(22, 'Sector Specific Specialization in 2', 1);

-- --------------------------------------------------------

--
-- Table structure for table `st_student_master`
--

CREATE TABLE `st_student_master` (
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(100) NOT NULL,
  `registration_no` varchar(200) NOT NULL,
  `class_id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `grad_year` int(11) DEFAULT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `specialization_id` int(11) DEFAULT NULL,
  `specialization_subject_id` int(11) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `fname` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mark_list` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `m_sem1` text NOT NULL,
  `m_sem2` text NOT NULL,
  `m_sem3` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_student_master`
--

INSERT INTO `st_student_master` (`student_id`, `academic_year`, `registration_no`, `class_id`, `division_id`, `grad_year`, `roll_no`, `department_id`, `specialization_id`, `specialization_subject_id`, `cgpa`, `fname`, `mobile`, `email`, `mark_list`, `status`, `m_sem1`, `m_sem2`, `m_sem3`, `created_at`) VALUES
(1, '2025-26', 'S1032241061', 5, 1, 0, '34', 3, 1, 1, 9.37, 'Shailesh Gajengi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(2, '2025-26', '1032241038', 5, 1, 0, '12', 3, 1, 1, 6.70, 'Jaibir Chahal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(3, '2025-26', 'S1032241030', 5, 1, 0, '3', 3, 1, 1, 8.95, 'Priyanshukumar Arya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(4, '2025-26', '1032241054', 5, 1, 0, '27', 3, 1, 1, 9.86, 'Krishna Santosh Dube', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(5, '2025-26', '1032241055', 5, 1, 0, '28', 3, 1, 1, 7.30, 'Aryan Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(6, '2025-26', 'S1032241056', 5, 1, 0, '29', 3, 1, 1, 9.91, 'Dibyansh Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(7, '2025-26', '24-COMPA51-28', 5, 1, 0, '51', 3, 1, 1, 9.38, 'Yashkumar Motilal Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(8, '2025-26', '1032241032', 5, 1, 0, '5', 3, 1, 1, 9.32, 'Simran Bendke', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(9, '2025-26', 'S1032241029', 5, 1, 0, '2', 3, 1, 1, 9.77, 'Kavish Ahuja', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(10, '2025-26', 'S1032241043', 5, 1, 0, '16', 3, 1, 1, 9.86, 'Hr4ik Chauhan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(11, '2025-26', '24-COMPA45-28', 5, 1, 0, '45', 3, 1, 1, 8.00, 'Mansi Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(12, '2025-26', 'S1032241059', 5, 1, 0, '32', 3, 1, 1, 9.09, 'Purva Dinkar Gade', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(13, '2025-26', 'S1032241051', 5, 1, 0, '24', 3, 1, 1, 9.86, 'Sanjana Dhopte', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(14, '2025-26', 'S1032241060', 5, 1, 0, '33', 3, 1, 1, 6.75, 'Ishan Ranj4 Gadecha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(15, '2025-26', 'S1032241048', 5, 1, 0, '21', 3, 1, 1, 9.31, 'Mohammad Shoeb Md Farookh Choudhary', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(16, '2025-26', 'S1032241068', 5, 1, 0, '41', 3, 1, 1, 9.38, 'Aman Rajkumar Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(17, '2025-26', 'S1032241085', 5, 1, 0, '58', 3, 1, 1, 6.90, 'Rishabh Jaiswal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(18, '2025-26', 'S1032241071', 5, 1, 0, '44', 3, 1, 1, 9.73, 'Himanshu Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(19, '2025-26', 'S1032241089', 5, 1, 0, '62', 3, 1, 1, 9.66, 'Prince Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(20, '2025-26', 'S1032241074', 5, 1, 0, '47', 3, 1, 1, 7.20, 'Pratham Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(21, '2025-26', 'S1032250200', 5, 1, 0, '66', 3, 1, 1, 9.95, 'Ananya Dhote', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(22, '2025-26', 'S1032241075', 5, 1, 0, '48', 3, 1, 1, 9.31, 'Sarthak Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(23, '2025-26', 'S1032250204', 5, 1, 0, '69', 3, 1, 1, 8.23, 'Sagar Sanjay Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(24, '2025-26', 'S1032241044', 5, 1, 0, '17', 3, 1, 1, 9.75, 'Sanju Chauhan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(25, '2025-26', 'S1032241070', 5, 1, 0, '43', 3, 1, 1, 9.10, 'Disha Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(26, '2025-26', 'S1032241065', 5, 1, 0, '38', 3, 1, 1, 9.55, 'Pr4ha Goradia', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(27, '2025-26', 'S1032241047', 5, 1, 0, '20', 3, 1, 1, 8.30, 'Krish Choudhary', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(28, '2025-26', 's1032241084', 5, 1, 0, '57', 3, 1, 1, 9.34, 'Krish Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(29, '2025-26', '1032241028', 5, 1, 0, '1', 3, 1, 1, 8.01, 'Tiya Agarwal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(30, '2025-26', 'S1032241077', 5, 1, 0, '50', 3, 1, 1, 9.14, 'Sum4 Santosh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(31, '2025-26', 'S1032241041', 5, 1, 0, '14', 3, 1, 1, 9.86, 'Dipendra Kumar Chaturvedi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(32, '2025-26', 'S1032241090', 5, 1, 0, '63', 3, 1, 1, 9.05, 'Rishu Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(33, '2025-26', 'S1032241037', 5, 1, 0, '10', 3, 1, 1, 8.70, 'Hredey Chaand', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(34, '2025-26', 'S1032241064', 5, 1, 0, '37', 3, 1, 1, 8.18, 'Jay Bipinbhai Gohil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(35, '2025-26', '1032241093', 5, 2, 0, '2', 3, 1, 1, 7.08, 'Daksh Preetam Jodhavat', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(36, '2025-26', 'S1032241128', 5, 2, 0, '37', 3, 1, 1, 8.10, 'Sakshi Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(37, '2025-26', 'S1032241101', 5, 2, 0, '10', 3, 1, 1, 8.00, 'Shreya Kesharwani', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(38, '2025-26', 'S1032241154', 5, 2, 0, '63', 3, 1, 1, 8.82, 'Dhruva Ramesh Panmand', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(39, '2025-26', '1032241146', 5, 2, 0, '55', 3, 1, 1, 8.73, 'Prince Shyamdhar Pal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(40, '2025-26', 'S1032241125', 5, 2, 0, '34', 3, 1, 1, 9.44, 'Anish Ajaykumar Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(41, '2025-26', 'S1032241122', 5, 2, 0, '31', 3, 1, 1, 9.14, 'Priya Amar Manna', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(42, '2025-26', 'S1032241127', 5, 2, 0, '36', 3, 1, 1, 9.12, 'Ashish Sanjay Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(43, '2025-26', 'S1032241124', 5, 2, 0, '33', 3, 1, 1, 8.91, 'Adarsh Vinod Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(44, '2025-26', 'S1032241136', 5, 2, 0, '45', 3, 1, 1, 8.50, 'Yash Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(45, '2025-26', 'S1032241143', 5, 2, 0, '52', 3, 1, 1, 8.00, 'Asma Qureshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(46, '2025-26', 'S1032241132', 5, 2, 0, '41', 3, 1, 1, 8.50, 'Harsh Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(47, '2025-26', 'S1032241150', 5, 2, 0, '59', 3, 1, 1, 10.00, 'Anshika Ashish Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(48, '2025-26', 'S1032241141', 5, 2, 0, '50', 3, 1, 1, 9.60, 'Areeza Mukadam', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(49, '2025-26', 'S1032241140', 5, 2, 0, '49', 3, 1, 1, 7.23, 'Krish Morya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(50, '2025-26', 'S1032241111', 5, 2, 0, '20', 3, 1, 1, 9.84, 'Devanshu Kumawat', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(51, '2025-26', 'S1032241153', 5, 2, 0, '62', 3, 1, 1, 8.68, 'Esha Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(52, '2025-26', 'S1032250208', 5, 2, 0, '67', 3, 1, 1, 8.50, 'Rishabh Trivedi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(53, '2025-26', 'S1032250222', 5, 2, 0, '69', 3, 1, 1, 9.68, 'Jogeshkumar Kantilal Mali', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(54, '2025-26', 'S1032241108', 5, 2, 0, '17', 3, 1, 1, 8.30, 'Yash Khatri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(55, '2025-26', 'S1032250210', 5, 2, 0, '64', 3, 1, 1, 9.41, 'Parth Akshay Dave', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(56, '2025-26', 'S1032250211', 5, 2, 0, '66', 3, 1, 1, 9.45, 'Aryan Navghare', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(57, '2025-26', 'S1032241104', 5, 2, 0, '13', 3, 1, 1, 8.48, 'Inzamamul haque Hamid ali Khan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(58, '2025-26', 'S1032241149', 5, 2, 0, '58', 3, 1, 1, 9.95, 'Akish Anil Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(59, '2025-26', 'S1032241135', 5, 2, 0, '44', 3, 1, 1, 9.69, 'Sagar Mr4unjay Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(60, '2025-26', 'S1032250207', 5, 2, 0, '65', 3, 1, 1, 9.36, 'SAZIA SALIM KAROL', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(61, '2025-26', '1032241109', 5, 2, 0, '18', 3, 1, 1, 7.50, 'Durvakshi Killedar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(62, '2025-26', 'S1032241142', 5, 2, 0, '51', 3, 1, 1, 8.90, 'Divya nagaraju Dumpeti', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(63, '2025-26', 'S1032241163', 5, 3, 0, '9', 3, 1, 1, 8.27, 'Akshat Prajapati', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(64, '2025-26', 'S1032241213', 5, 3, 0, '59', 3, 1, 1, 8.22, 'Ved Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(65, '2025-26', 'S1032241179', 5, 3, 0, '25', 3, 1, 1, 8.87, 'Saish Raut', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(66, '2025-26', 'S1032241217', 5, 3, 0, '63', 3, 1, 1, 9.27, 'Ad4ya Sunilkumar Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(67, '2025-26', 'S1032241214', 5, 3, 0, '60', 3, 1, 1, 9.68, 'Arsh Siddiqui', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(68, '2025-26', 'S1032241170', 5, 3, 0, '16', 3, 1, 1, 8.71, 'Ad4ya Raj Prasad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(69, '2025-26', 'S1032250214', 5, 3, 0, '67', 3, 1, 1, 9.73, 'Shaikh Mohd Fahad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(70, '2025-26', 'S1032241162', 5, 3, 0, '8', 3, 1, 1, 8.74, 'Radhe Patel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(71, '2025-26', 'S1032241191', 5, 3, 0, '37', 3, 1, 1, 8.57, 'Rajvi Shah', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(72, '2025-26', 'S1032241171', 5, 3, 0, '17', 3, 1, 1, 9.00, 'Dhananjay Prasad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(73, '2025-26', 'S1032241188', 5, 3, 0, '34', 3, 1, 1, 8.67, 'Aneesh Sawant', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(74, '2025-26', 'S1032241193', 5, 3, 0, '39', 3, 1, 1, 9.05, 'Mohammad Istiyaq Ahmed Shakil shaikh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(75, '2025-26', 'S1032241181', 5, 3, 0, '27', 3, 1, 1, 7.00, 'Priyanshu reddy', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(76, '2025-26', '1032241192', 5, 3, 0, '38', 3, 1, 1, 7.32, 'Shaikh mohammad adnan mohd irfan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(77, '2025-26', 'S1032241195', 5, 3, 0, '41', 3, 1, 1, 8.59, 'Sohail Najir Shaikh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(78, '2025-26', 'S1032241182', 5, 3, 0, '28', 3, 1, 1, 8.49, 'MohammedSaad LaiqHasan Rizvi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(79, '2025-26', 'S1032241166', 5, 3, 0, '12', 3, 1, 1, 9.60, 'Tanish Sandip Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(80, '2025-26', 'S1032241161', 5, 3, 0, '7', 3, 1, 1, 8.00, 'Manas Patel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(81, '2025-26', 'S1032241183', 5, 3, 0, '29', 3, 1, 1, 7.68, 'Shruti Sable', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(82, '2025-26', 'S1032241185', 5, 3, 0, '31', 3, 1, 1, 9.40, 'JAYAD4YA SALOI', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(83, '2025-26', 'S1032241187', 5, 3, 0, '33', 3, 1, 1, 9.60, 'Vivek Kumar R4lal Saw', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(84, '2025-26', 'S1032241176', 5, 3, 0, '22', 3, 1, 1, 7.68, 'Mayank Rai', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(85, '2025-26', 'S1032241186', 5, 3, 0, '32', 3, 1, 1, 9.42, 'Anish Sasmal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(86, '2025-26', 'S1032250212', 5, 3, 0, '68', 3, 1, 1, 10.00, 'Am4 Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(87, '2025-26', 'S1032250215', 5, 3, 0, '65', 3, 1, 1, 9.45, 'Md Irfanul Hamidul Haque', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(88, '2025-26', 'S1032241207', 5, 3, 0, '53', 3, 1, 1, 9.53, 'Harsh4a Shirsat', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(89, '2025-26', 'S1032241211', 5, 3, 0, '57', 3, 1, 1, 9.86, 'Shivang Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(90, '2025-26', 'S1032241190', 5, 3, 0, '36', 3, 1, 1, 8.71, 'Prasham Shah', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(91, '2025-26', 'S1032241215', 5, 3, 0, '61', 3, 1, 1, 7.77, 'Aarav Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(92, '2025-26', 'S1032241189', 5, 3, 0, '35', 3, 1, 1, 8.70, 'Devashree Sawant', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(93, '2025-26', '1032250213', 5, 3, 0, '64', 3, 1, 1, 9.32, 'Abhay R. Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(94, '2025-26', '1032241197', 5, 3, 0, '43', 3, 1, 1, 9.10, 'Mahimna Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(95, '2025-26', 'S1032241200', 5, 3, 0, '46', 3, 1, 1, 8.95, 'Ad4ya Mukhlal Shaw', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(96, '2025-26', 'S1032241175', 5, 3, 0, '21', 3, 1, 1, 9.63, 'Ayush Rajesh Rai', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(97, '2025-26', '1032241169', 5, 3, 0, '15', 3, 1, 1, 9.08, 'Sum4kumar Sanjay Kumar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(98, '2025-26', 'S1032241204', 5, 3, 0, '50', 3, 1, 1, 10.00, 'Shishir p Shetty', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(99, '2025-26', 'S1032241196', 5, 3, 0, '42', 3, 1, 1, 10.00, 'Atul Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(100, '2025-26', 'S1032241194', 5, 3, 0, '40', 3, 1, 1, 7.50, 'Saahil Khalid Shaikh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(101, '2025-26', '1032241234', 5, 4, 0, '17', 3, 1, 1, 7.86, 'Udaypratap Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(102, '2025-26', 'S1032241232', 5, 4, 0, '15', 3, 1, 1, 8.99, 'Rudrapratap Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(103, '2025-26', 'S1032241249', 5, 4, 0, '32', 3, 1, 1, 9.62, 'Akshay Upadhyay', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(104, '2025-26', 'S1032241222', 5, 4, 0, '5', 3, 1, 1, 9.50, 'Ashm4 singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(105, '2025-26', 'S1032241221', 5, 4, 0, '4', 3, 1, 1, 8.82, 'Aryan Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(106, '2025-26', 'S1032250217', 5, 4, 0, '70', 3, 1, 1, 9.55, 'Swapnil Santosh Shinde', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(107, '2025-26', 'S1032241244', 5, 4, 0, '27', 3, 1, 1, 10.00, 'Nik4a Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(108, '2025-26', 'S1032241256', 5, 4, 0, '39', 3, 1, 1, 9.18, 'Pratik Verma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(109, '2025-26', '1032241255', 5, 4, 0, '38', 3, 1, 1, 0.00, 'Aryan verma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(110, '2025-26', '1032241269', 5, 4, 0, '52', 3, 1, 1, 7.30, 'Aryan Worah', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(111, '2025-26', 'S1032250223', 5, 4, 0, '68', 3, 1, 1, 0.00, 'Sm4 Suhas Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(112, '2025-26', 'S1032241275', 5, 4, 0, '58', 3, 1, 1, 0.00, 'Nikhil Mahendrakumar Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(113, '2025-26', 'S1032241267', 5, 4, 0, '50', 3, 1, 1, 0.00, 'Ved Amar Wade', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(114, '2025-26', 'S1032241260', 5, 4, 0, '43', 3, 1, 1, 8.57, 'Rahul Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(115, '2025-26', 'S1032241273', 5, 4, 0, '56', 3, 1, 1, 0.00, 'Ayush Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(116, '2025-26', 'S1032241219', 5, 4, 0, '2', 3, 1, 1, 8.40, 'Singh Aman Kumar Ravindra Kumar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(117, '2025-26', 'S1032241094', 5, 2, 0, '3', 3, 1, 1, 8.54, 'Swarraj Joshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(118, '2025-26', '1032241252', 5, 4, 0, '35', 3, 1, 1, 9.68, 'Bhavika Shriram Vasule', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(119, '2025-26', 'S1032241277', 5, 4, 0, '60', 3, 1, 1, 9.65, 'Om Shivbalak Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(120, '2025-26', '1032241279', 5, 4, 0, '62', 3, 1, 1, 7.24, 'Vikas yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(121, '2025-26', 'S1032241266', 5, 4, 0, '49', 3, 1, 1, 9.16, 'Yagna vyas', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(122, '2025-26', 'S1032241253', 5, 4, 0, '36', 3, 1, 1, 8.12, 'Akash Verma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(123, '2025-26', 'S1032241040', 5, 1, 0, '13', 3, 1, 1, 7.40, 'Anagh Chaturvedi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(124, '2025-26', 'S1032241069', 5, 1, 0, '42', 3, 1, 1, 9.82, 'Bhavna Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(125, '2025-26', 'S1032250205', 5, 1, 0, '68', 3, 1, 1, 9.41, 'Darshana Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(126, '2025-26', 'S1032241067', 5, 1, 0, '40', 3, 1, 2, 9.24, 'Adarsh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(127, '2025-26', 'S1032241033', 5, 1, 0, '6', 3, 1, 2, 7.41, 'Arnav Bhanage', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(128, '2025-26', '1032241040', 5, 1, 0, '13', 3, 1, 2, 7.40, 'Anagh Deepak Chaturvedi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(129, '2025-26', '1032241053', 5, 1, 0, '26', 3, 1, 2, 6.60, 'Chah4 doshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(130, '2025-26', '1032241052', 5, 1, 0, '25', 3, 1, 2, 6.45, 'KRISH', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(131, '2025-26', 'S1032241039', 5, 1, 0, '12', 3, 1, 2, 6.50, 'Ad4ya Chamlagain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(132, '2025-26', '1032241050', 5, 1, 0, '23', 3, 1, 2, 8.41, 'Mishti Dhiman', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(133, '2025-26', 'S1032241086', 5, 1, 0, '59', 3, 1, 2, 8.62, 'Lovekush Jaiswar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(134, '2025-26', 'S1032250202', 5, 1, 0, '70', 3, 1, 2, 9.64, 'Aad4i Pawar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(135, '2025-26', '1032241049', 5, 1, 0, '22', 3, 1, 2, 8.44, 'Samiha Nadeem Dadarkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(136, '2025-26', '1032241083', 5, 1, 0, '56', 3, 1, 2, 8.40, 'Jeel jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(137, '2025-26', 'S1032241066', 5, 1, 0, '39', 3, 1, 2, 7.50, 'Aarna Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(138, '2025-26', 'S1032241120', 5, 2, 0, '29', 3, 1, 2, 9.88, 'Raj Mahesh Mane', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(139, '2025-26', 'S1032241097', 5, 2, 0, '6', 3, 1, 2, 10.00, 'Ananya Kalia', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(140, '2025-26', 'S1032241164', 5, 3, 0, '10', 3, 1, 2, 9.75, 'Manasvi Viraj Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(141, '2025-26', 'S1032241168', 5, 3, 0, '14', 3, 1, 2, 8.31, 'Srushti Pawar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(142, '2025-26', '1032241158', 5, 3, 0, '4', 3, 1, 2, 8.57, 'Veera pashine', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(143, '2025-26', 'S1032241203', 5, 3, 0, '49', 3, 1, 2, 8.05, 'Nandini Krishna Shetty', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(144, '2025-26', 'S1032241180', 5, 3, 0, '26', 3, 1, 2, 6.51, 'Soham Rawte', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(145, '2025-26', '1032241157', 5, 3, 0, '3', 3, 1, 2, 8.27, 'Shravani Parsekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(146, '2025-26', 'S1032241173', 5, 3, 0, '19', 3, 1, 2, 7.64, 'Mohammad Faiz qadri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(147, '2025-26', 'S1032241209', 5, 3, 0, '55', 3, 1, 2, 6.40, 'Ad4ya Shrivastava', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(148, '2025-26', 'S1032241233', 5, 4, 0, '16', 3, 1, 2, 8.59, 'Sakshi Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(149, '2025-26', 'MU0341120240224830', 5, 4, 0, '9', 3, 1, 2, 8.18, 'Mahek singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(150, '2025-26', '1032241231', 5, 4, 0, '14', 3, 1, 2, 7.70, 'Rish4 Udai Pratap Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(151, '2025-26', 'S1032250220', 5, 4, 0, '67', 3, 1, 2, 8.00, 'Mohammad Shees', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(152, '2025-26', '1032250218', 5, 4, 0, '69', 3, 1, 2, 9.23, 'Shirin Mohammed Hussain Shaikh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(153, '2025-26', '1032241263', 5, 4, 0, '46', 3, 1, 2, 9.54, 'SHIVAM VISHWAKARMA', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(154, '2025-26', 'S1032241276', 5, 4, 0, '59', 3, 1, 2, 7.53, 'Nilesh Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(155, '2025-26', 'S1032241261', 5, 4, 0, '44', 3, 1, 2, 8.16, 'Sahil Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(156, '2025-26', 'S1032241258', 5, 4, 0, '41', 3, 1, 2, 7.40, 'Alok shyam Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(157, '2025-26', 'S1032241036', 5, 1, 0, '9', 3, 1, 3, 9.09, 'Harshaal Shankar Boyeni', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(158, '2025-26', '1032241114', 5, 2, 0, '23', 3, 1, 3, 9.32, 'Nainika Kunder', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(159, '2025-26', '1032241251', 5, 4, 0, '34', 3, 1, 3, 8.03, 'Shreyas Dipankar Vartak', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(160, '2025-26', 'S1032241259', 5, 4, 0, '42', 3, 1, 3, 0.00, 'Hr4hik Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(161, '2025-26', 'S1032241042', 5, 1, 0, '15', 3, 1, 4, 9.18, 'Namrata Chaubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(162, '2025-26', 'S1032241237', 5, 4, 0, '20', 3, 1, 4, 0.00, 'Divyajyot Sinha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(163, '2025-26', 'S1032241063', 5, 1, 0, '36', 3, 1, 10, 6.30, 'Pariket Girase', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(164, '2025-26', '1032241148', 5, 2, 0, '57', 3, 1, 10, 8.27, 'Kashish Panchal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(165, '2025-26', '1032241110', 5, 2, 0, '19', 3, 1, 10, 7.73, 'H4anshu Kothari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(166, '2025-26', '1032241134', 5, 2, 0, '43', 3, 1, 10, 9.27, 'Prathamesh Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(167, '2025-26', '1032241704', 5, 2, 0, '46', 3, 1, 10, 8.50, 'Puj4ha Pedapolu', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(168, '2025-26', '1032241095', 5, 2, 0, '4', 3, 1, 10, 8.00, 'Manasvi Kadambala', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(169, '2025-26', 'S1032241138', 5, 2, 0, '47', 3, 1, 10, 0.00, 'Jay Miyani', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(170, '2025-26', 'S1032241096', 5, 2, 0, '5', 3, 1, 10, 8.32, 'Jay Kakadiya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(171, '2025-26', 'S1032241099', 5, 2, 0, '8', 3, 1, 10, 9.50, 'Riya Kasat', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(172, '2025-26', 'S1032241100', 5, 2, 0, '9', 3, 1, 10, 8.20, 'Pradnyesh kawate', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(173, '2025-26', 'S1032241167', 5, 3, 0, '13', 3, 1, 10, 7.86, 'Ved Manojkumar Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(174, '2025-26', '1032241106', 5, 2, 0, '15', 3, 1, 5, 9.58, 'Ad4i Khandge', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(175, '2025-26', 'S1032241155', 5, 3, 0, '1', 3, 1, 5, 7.20, 'Kunal Parmar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(176, '2025-26', 'S1032241206', 5, 3, 0, '52', 3, 1, 5, 8.07, 'Sarvesh Shinde', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(177, '2025-26', '1032241224', 5, 4, 0, '7', 3, 1, 6, 0.00, 'Krish Sushil Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(178, '2025-26', 'S1032241205', 5, 3, 0, '51', 3, 1, 6, 9.92, 'Zidane Z Shikalgar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(179, '2025-26', 'S1032241159', 5, 3, 0, '5', 3, 1, 6, 9.68, 'Aayan Patel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(180, '2025-26', 'S1032250205', 5, 1, 0, '68', 3, 1, 7, 9.41, 'Darshana Rajesh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(181, '2025-26', 'S1032241235', 5, 4, 0, '18', 3, 1, 8, 9.10, 'Vickykumar Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(182, '2025-26', 'S1032291225', 5, 3, 0, '8', 3, 1, 1, 9.18, 'Kushgra Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(183, '2025-26', 'S1032240670', 5, 1, 0, '8', 4, 1, 3, 8.60, 'Pranav Bhavsar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(184, '2025-26', 'S1032240682', 5, 1, 0, '20', 4, 1, 3, 8.50, 'Taniya Dhawan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(185, '2025-26', 'S1032240713', 5, 1, 0, '51', 4, 1, 4, 8.20, 'Ravishankar Chandrakant Kanaki', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(186, '2025-26', '', 5, 3, 0, '29', 4, 1, 4, 8.05, 'Pradnya Mukesh Sonawane', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(187, '2025-26', '', 5, 3, 0, '15', 4, 1, 4, 9.57, 'Pratha Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(188, '2025-26', '1032240817', 5, 3, 0, '28', 4, 1, 4, 9.54, 'Mihir Sonawane', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(189, '2025-26', 'S1032240663', 5, 1, 0, '1', 4, 1, 1, 9.75, 'Abidi Mohammed Saqlain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(190, '2025-26', '1032250173', 5, 1, 0, '69', 4, 1, 1, 9.77, 'Falak Afraz Shaikh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(191, '2025-26', '1032240712', 5, 1, 0, '50', 4, 1, 1, 10.00, 'Suhani Subodh Kambli', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(192, '2025-26', '1032240686', 5, 1, 0, '24', 4, 1, 1, 9.23, 'Vidhidevi Rajkumar Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(193, '2025-26', '1032240676', 5, 1, 0, '14', 4, 1, 1, 9.55, 'MAHIKA CHAURASIYA', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(194, '2025-26', '1032240680', 5, 1, 0, '18', 4, 1, 1, 9.73, 'Anand Dangi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(195, '2025-26', '1032240714', 5, 1, 0, '52', 4, 1, 1, 9.63, 'Ananya Harish Kanchan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(196, '2025-26', '1032240706', 5, 1, 0, '44', 4, 1, 1, 9.92, 'Jainam Pankaj Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(197, '2025-26', 'S1032240675', 5, 1, 0, '13', 4, 1, 1, 9.68, 'Diksha Chaurasiya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(198, '2025-26', 'S1032240688', 5, 1, 0, '26', 4, 1, 1, 9.85, 'Aarya Shantaram Gaikwad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(199, '2025-26', '1032250171', 5, 1, 0, '65', 4, 1, 1, 9.64, 'Isha Ganesh Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(200, '2025-26', '1032240693', 5, 1, 0, '31', 4, 1, 1, 7.32, 'Bhumika Vinod Gothankar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(201, '2025-26', '1032240689', 5, 1, 0, '27', 4, 1, 1, 7.62, 'Sneha Gajera', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(202, '2025-26', 'S1032240684', 5, 1, 0, '22', 4, 1, 1, 8.88, 'Kalp Doshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(203, '2025-26', '1032240697', 5, 1, 0, '35', 4, 1, 1, 8.89, 'Gautam Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(204, '2025-26', 'S1032240678', 5, 1, 0, '16', 4, 1, 1, 8.10, 'Mihir Chettiar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(205, '2025-26', 'S1032240698', 5, 1, 0, '36', 4, 1, 1, 7.22, 'Lavi sanjaykumar Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(206, '2025-26', '1032240690', 5, 1, 0, '28', 4, 1, 1, 7.88, 'Mihir Govind Gaonkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(207, '2025-26', 'S1032240752', 5, 2, 0, '26', 4, 1, 1, 8.86, 'Shivam Madan Oz', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(208, '2025-26', 'S1032240756', 5, 2, 0, '30', 4, 1, 1, 9.73, 'Anurag Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(209, '2025-26', 'S1032240742', 5, 2, 0, '16', 4, 1, 1, 9.95, 'Jennica Paresh Mistry', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(210, '2025-26', 'S1032240750', 5, 2, 0, '24', 4, 1, 1, 9.73, 'Bhagyashri Pravin Nere', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(211, '2025-26', '1032240781', 5, 2, 0, '55', 4, 1, 1, 7.63, 'Chhahat Samat', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(212, '2025-26', 'S1032240729', 5, 2, 0, '3', 4, 1, 1, 9.95, 'Shaheen Rajmohammad Madeena', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(213, '2025-26', 'S1032240769', 5, 2, 0, '43', 4, 1, 1, 7.69, 'Sakshi Prajapati', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(214, '2025-26', 'S1032240728', 5, 2, 0, '2', 4, 1, 1, 7.60, 'Swar Lokre', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(215, '2025-26', '1032240749', 5, 2, 0, '23', 4, 1, 1, 9.45, 'Raunak Nayak', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(216, '2025-26', 'S1032240770', 5, 2, 0, '44', 4, 1, 1, 8.50, 'Shabdansh Prajapati', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(217, '2025-26', 'S1032240779', 5, 2, 0, '53', 4, 1, 1, 8.05, 'Anannya Salvi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(218, '2025-26', 'S1032240778', 5, 2, 0, '52', 4, 1, 1, 8.70, 'Khushi Sah', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(219, '2025-26', 'S1032240754', 5, 2, 0, '28', 4, 1, 1, 9.86, 'Aniket Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(220, '2025-26', 'S1032240738', 5, 2, 0, '12', 4, 1, 1, 9.59, 'Himanshu Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(221, '2025-26', 'S1032240777', 5, 2, 0, '51', 4, 1, 1, 9.30, 'Ad4ya Sah', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(222, '2025-26', '1032240780', 5, 2, 0, '54', 4, 1, 1, 7.30, 'Simran Samanta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(223, '2025-26', 'S1032240776', 5, 2, 0, '50', 4, 1, 1, 8.91, 'Shashank Dhananjay Roy', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(224, '2025-26', 'S1032240784', 5, 2, 0, '58', 4, 1, 1, 8.73, 'Shaikh Foziyabano. F', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(225, '2025-26', 'S1032240727', 5, 2, 0, '1', 4, 1, 1, 9.22, 'Ameya Kulkarni', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(226, '2025-26', 'S1032240783', 5, 2, 0, '57', 4, 1, 1, 8.77, 'Dev Shah', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(227, '2025-26', 'S1032240744', 5, 2, 0, '18', 4, 1, 1, 8.80, 'Sanket More', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(228, '2025-26', 'S1032240745', 5, 2, 0, '19', 4, 1, 1, 7.50, 'Ayush Mahadev Mote', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(229, '2025-26', 'S1032240774', 5, 2, 0, '48', 4, 1, 1, 7.58, 'Riya S Rajpurkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(230, '2025-26', 'S1032240788', 5, 2, 0, '62', 4, 1, 1, 9.20, 'Shivam Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(231, '2025-26', '1032240828', 5, 3, 0, '39', 4, 1, 1, 9.95, 'Arwa Vasaiwala', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(232, '2025-26', '1032240821', 5, 3, 0, '32', 4, 1, 1, 10.00, 'Khushboo Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(233, '2025-26', '1032240811', 5, 3, 0, '22', 4, 1, 1, 9.75, 'Rishika Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(234, '2025-26', '1032240829', 5, 3, 0, '40', 4, 1, 1, 9.52, 'Aayush Vengurlekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(235, '2025-26', 'S1032240823', 5, 3, 0, '34', 4, 1, 1, 8.68, 'Omkar Deenanath Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(236, '2025-26', 'S1032250184', 5, 3, 0, '65', 4, 1, 1, 9.36, 'Fuzail Aqdas Abdul Qawi Khan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(237, '2025-26', '1032240820', 5, 3, 0, '31', 4, 1, 1, 8.90, 'Devansh Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(238, '2025-26', 'S1032240834', 5, 3, 0, '45', 4, 1, 1, 8.87, 'Gautam Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(239, '2025-26', '1032240824', 5, 3, 0, '35', 4, 1, 1, 8.40, 'Prasham Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(240, '2025-26', '1032240813', 5, 3, 0, '24', 4, 1, 1, 8.17, 'Saksham Rajesh Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(241, '2025-26', 'S1032240796', 5, 3, 0, '7', 4, 1, 1, 9.00, 'Janhavi Shrotriya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(242, '2025-26', '1032240837', 5, 3, 0, '48', 4, 1, 1, 8.80, 'Shaili Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(243, '2025-26', 'S1032240807', 5, 3, 0, '18', 4, 1, 1, 8.68, 'Dev singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(244, '2025-26', '1032240800', 5, 3, 0, '11', 4, 1, 1, 9.59, 'Harsh Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(245, '2025-26', 'S1032240799', 5, 3, 0, '10', 4, 1, 1, 9.43, 'Girik Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(246, '2025-26', '1032250183', 5, 3, 0, '68', 4, 1, 1, 9.70, 'Aman Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(247, '2025-26', '1032240831', 5, 3, 0, '42', 4, 1, 1, 8.80, 'Ansh Viramgama', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(248, '2025-26', '1032240825', 5, 3, 0, '36', 4, 1, 1, 8.70, 'Disha Upwanshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(249, '2025-26', '1032240815', 5, 3, 0, '26', 4, 1, 1, 8.62, 'Shiva Sunil Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(250, '2025-26', 'S1032240801', 5, 3, 0, '12', 4, 1, 1, 8.40, 'Jatin Vinod Shekla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(251, '2025-26', '1032240692', 5, 1, 0, '30', 4, 1, 9, 8.50, 'Sum4 Ghavri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(252, '2025-26', '1032250174', 5, 1, 0, '70', 4, 1, 9, 8.50, 'Jhalak Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(253, '2025-26', 'S1032240683', 5, 1, 0, '21', 4, 1, 10, 9.23, 'Tanishka Dombe', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(254, '2025-26', 'S1032240717', 5, 1, 0, '55', 4, 1, 10, 8.18, 'Muskan kapri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(255, '2025-26', 'S1032240718', 5, 1, 0, '56', 4, 1, 10, 7.14, 'Yasoub kaunain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(256, '2025-26', 'S1032240664', 5, 1, 0, '2', 4, 1, 10, 8.41, 'Harsh Agarwal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(257, '2025-26', 'S1032240789', 5, 2, 0, '63', 4, 1, 10, 8.61, 'Shubh Wade', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(258, '2025-26', 'S1032240746', 5, 2, 0, '20', 4, 1, 10, 9.05, 'Shreyash Mahesh Mule', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(259, '2025-26', 'S1032240787', 5, 2, 0, '61', 4, 1, 10, 7.91, 'Pratik Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(260, '2025-26', '1032240771', 5, 2, 0, '45', 4, 1, 10, 8.55, 'Durgesh Rajesh Prasad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(261, '2025-26', '1032240762', 5, 2, 0, '36', 4, 1, 10, 8.30, 'Rajas Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(262, '2025-26', '1032230566', 5, 1, 0, '60', 4, 1, 5, 8.02, 'Aryan Kadam', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(263, '2025-26', 'S1032240701', 5, 1, 0, '39', 4, 1, 5, 9.36, 'Utsavi Rahul Gurjar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(264, '2025-26', '1032240671', 5, 1, 0, '9', 4, 1, 5, 9.27, 'Ayush Bind', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(265, '2025-26', 'S1032240763', 5, 2, 0, '37', 4, 1, 5, 9.68, 'Swapnil Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(266, '2025-26', 'S1032240761', 5, 2, 0, '35', 4, 1, 5, 9.17, 'Manasvi Patel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(267, '2025-26', '1032240782', 5, 2, 0, '56', 4, 1, 5, 9.45, 'Vedant Sawant', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(268, '2025-26', '1032240768', 5, 2, 0, '42', 4, 1, 5, 7.70, 'Parvesh prajapati', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(269, '2025-26', 'S1032240685', 5, 1, 0, '23', 4, 1, 2, 7.82, 'Arnav Doshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(270, '2025-26', 'S1032240677', 5, 1, 0, '15', 4, 1, 2, 9.76, 'Yash Chavan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(271, '2025-26', '1032250175', 5, 1, 0, '68', 4, 1, 2, 8.68, 'Thakur Abhinav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(272, '2025-26', '1032250170', 5, 1, 0, '67', 4, 1, 2, 9.86, 'Jidnyesh Badgujar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(273, '2025-26', 'S1032240772', 5, 2, 0, '46', 4, 1, 2, 8.95, 'Deepu Pushkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(274, '2025-26', 'S1032240743', 5, 2, 0, '17', 4, 1, 2, 9.61, 'Riddhi More', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(275, '2025-26', 'S1032240736', 5, 2, 0, '10', 4, 1, 2, 9.49, 'Anushka Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(276, '2025-26', 'S1032240733', 5, 2, 0, '7', 4, 1, 2, 9.31, 'Sujoy Ma4y', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(277, '2025-26', 'S1032240775', 5, 2, 0, '49', 4, 1, 2, 9.50, 'Vishwa Rajendra Rajput', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(278, '2025-26', 'S1032250180', 5, 2, 0, '67', 4, 1, 2, 9.23, 'Ananya Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(279, '2025-26', 'S1032250179', 5, 2, 0, '68', 4, 1, 2, 9.18, 'Eshika Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(280, '2025-26', '1032250186', 5, 3, 0, '69', 4, 1, 2, 9.86, 'Urvi Shinde', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(281, '2025-26', 'S1032250182', 5, 3, 0, '66', 4, 1, 2, 9.82, 'Pranjal Mahendra Gawand', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(282, '2025-26', '1032240826', 5, 3, 0, '37', 4, 1, 2, 9.77, 'Manini Utekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(283, '2025-26', '1032240797', 5, 3, 0, '8', 4, 1, 2, 9.47, 'Ad4i Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(284, '2025-26', 'S1032250187', 5, 3, 0, '67', 4, 1, 2, 9.50, 'Shwet Alok Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(285, '2025-26', '1032250185', 5, 3, 0, '70', 4, 1, 2, 9.91, 'Soham Dipen Ramjiyanj', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(286, '2025-26', 'S1032240839', 5, 3, 0, '50', 4, 1, 2, 9.05, 'Saeesha Wade', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(287, '2025-26', 'S1032240819', 5, 3, 0, '30', 4, 1, 2, 9.23, 'Khushi Madan Thakur', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(288, '2025-26', 'S1032240847', 5, 3, 0, '58', 4, 1, 2, 7.32, 'Riddhi yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(289, '2025-26', 'S1032240816', 5, 3, 0, '27', 4, 1, 2, 9.82, 'Shvet Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(290, '2025-26', '1032240845', 5, 3, 0, '56', 4, 1, 2, 7.28, 'Arjun Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(291, '2025-26', '1032240838', 5, 3, 0, '49', 4, 1, 2, 8.90, 'Srishti Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(292, '2025-26', 'S1032240850', 5, 3, 0, '61', 4, 1, 2, 8.64, 'Varsha Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(293, '2025-26', 'S1032240840', 5, 3, 0, '51', 4, 1, 2, 9.68, 'Shubham Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(294, '2025-26', 'S1032240849', 5, 3, 0, '60', 4, 1, 2, 7.45, 'Shivam yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(295, '2025-26', '1032240711', 5, 1, 0, '49', 4, 1, 13, 7.00, 'Vin4 Kadam', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(296, '2025-26', '1032240710', 5, 1, 0, '48', 4, 1, 13, 7.00, 'Vedant Kadam', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(297, '2025-26', 'S1032240539', 5, 1, 0, '3', 5, 1, 11, 9.85, 'Dristi Dilip Agrawal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(298, '2025-26', 'S1032240540', 5, 1, 0, '4', 5, 1, 2, 9.89, 'Shashank Mayur Barot', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(299, '2025-26', 'S1032240544', 5, 1, 0, '8', 5, 1, 1, 9.63, 'Priyanka Ramchandra Chavan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(300, '2025-26', 'S1032240546', 5, 1, 0, '10', 5, 1, 1, 9.70, 'Tanmaya Deshpande', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(301, '2025-26', 'S1032240548', 5, 1, 0, '12', 5, 1, 12, 9.37, 'Pratham Divkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(302, '2025-26', 'S1032240549', 5, 1, 0, '13', 5, 1, 2, 6.05, 'Krishna Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(303, '2025-26', 'S1032240553', 5, 1, 0, '17', 5, 1, 1, 9.29, 'Vaibhav Vimlendu Dwivedi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(304, '2025-26', 'S1032240554', 5, 1, 0, '18', 5, 1, 1, 9.16, 'Sneha Gautam', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(305, '2025-26', 'S1032240556', 5, 1, 0, '20', 5, 1, 2, 7.67, 'Ad4ya Sanjay Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(306, '2025-26', 'S1032240557', 5, 1, 0, '21', 5, 1, 2, 9.86, 'Darshana Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(307, '2025-26', 'S1032240559', 5, 1, 0, '23', 5, 1, 2, 8.63, 'Prachi gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(308, '2025-26', 'S1032240561', 5, 1, 0, '25', 5, 1, 2, 9.83, 'Shreya Rajkumar Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(309, '2025-26', 'S1032240562', 5, 1, 0, '26', 5, 1, 1, 8.96, 'Gupta Suraj Vimlesh Kumar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(310, '2025-26', 'S1032240565', 5, 1, 0, '29', 5, 1, 1, 8.60, 'Mohanish Jagushte', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(311, '2025-26', 'S1032240567', 5, 1, 0, '31', 5, 1, 1, 8.98, 'Krrish Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(312, '2025-26', 'S1032240570', 5, 1, 0, '34', 5, 1, 1, 9.58, 'Akshat Jangid', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(313, '2025-26', 'S1032240571', 5, 1, 0, '35', 5, 1, 1, 8.76, 'Aman Prakash Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(314, '2025-26', 'S1032240574', 5, 1, 0, '38', 5, 1, 2, 8.58, 'Vansh Joshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(315, '2025-26', 'S1032240576', 5, 1, 0, '40', 5, 1, 1, 7.00, 'mayuri janak kadam', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(316, '2025-26', 'S1032240580', 5, 1, 0, '44', 5, 1, 2, 7.14, 'Chetna keshari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(317, '2025-26', 'S1032240581', 5, 1, 0, '45', 5, 1, 2, 9.12, 'Ank4 Sunil Kumar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(318, '2025-26', 'S1032240584', 5, 1, 0, '48', 5, 1, 14, 9.86, 'Tirth Ashok Kushwaha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(319, '2025-26', 'S1032240585', 5, 1, 0, '49', 5, 1, 2, 9.55, 'vivek kushwaha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(320, '2025-26', 'S1032240586', 5, 1, 0, '50', 5, 1, 2, 7.90, 'Ma4hili Gujar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(321, '2025-26', 'S1032240587', 5, 1, 0, '51', 5, 1, 2, 9.91, 'Tanmay Harish Malkapurkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(322, '2025-26', 'S1032240588', 5, 1, 0, '52', 5, 1, 14, 9.60, 'Annirudha Narayana Mardi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(323, '2025-26', 'S1032240590', 5, 1, 0, '54', 5, 1, 1, 7.00, 'Aayush Mehta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(324, '2025-26', 'S1032240592', 5, 1, 0, '56', 5, 1, 2, 6.74, 'Aayush KrishnaKumar Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(325, '2025-26', 'S1032240594', 5, 1, 0, '58', 5, 1, 11, 6.70, 'Atharva mourya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(326, '2025-26', 'S1032250160', 5, 1, 0, '65', 5, 1, 5, 7.45, 'Upadhyay R4esh Umesh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(327, '2025-26', 'S1032250158', 5, 1, 0, '66', 5, 1, 5, 6.50, 'Shaikh Mukeem Nabi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(328, '2025-26', 'S1032250156', 5, 1, 0, '67', 5, 1, 5, 8.00, 'Avaneesh Gawde', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(329, '2025-26', 'S1032250157', 5, 1, 0, '68', 5, 1, 1, 9.32, 'Akshata Thaksen Patekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(330, '2025-26', 'S1032250159', 5, 1, 0, '69', 5, 1, 1, 8.09, 'Riya Prakash Sawant', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(331, '2025-26', 'S1032250162', 5, 1, 0, '70', 5, 1, 5, 0.00, 'Riya Negi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(332, '2025-26', 'S1032250161', 5, 1, 0, '71', 5, 1, 1, 8.64, 'Vedant  Kulkarni', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(333, '2025-26', 'S1032250155', 5, 1, 0, '72', 5, 1, 1, 0.00, 'Utkarsh Vivek Chaubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(334, '2025-26', 'S1032240582', 5, 1, 0, '46', 5, 1, 15, 8.43, 'Reejeeshraj Kumar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(335, '2025-26', 'S1032240601', 5, 2, 0, '2', 5, 1, 1, 9.80, 'Nidhi S4aram Pal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(336, '2025-26', 'S1032240605', 5, 2, 0, '6', 5, 1, 11, 7.50, 'Rudram Panchal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(337, '2025-26', 'S1032240608', 5, 2, 0, '9', 5, 1, 11, 9.47, 'Vaibhav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(338, '2025-26', 'S1032240611', 5, 2, 0, '12', 5, 1, 11, 9.44, 'Aniket Mahesh Parte', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(339, '2025-26', 'S1032240613', 5, 2, 0, '14', 5, 1, 11, 9.08, 'Jay Jayesh Patel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(340, '2025-26', 'S1032240615', 5, 2, 0, '16', 5, 1, 5, 9.64, 'Niha Satish Poojary', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(341, '2025-26', 'S1032240621', 5, 2, 0, '22', 5, 1, 11, 8.08, 'Omkar Ramavadh Prasad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(342, '2025-26', 'S1032240622', 5, 2, 0, '23', 5, 1, 11, 9.57, 'Sahil Santosh Rai', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(343, '2025-26', 'S1032240623', 5, 2, 0, '24', 5, 1, 1, 9.32, 'Aachal Rasekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(344, '2025-26', 'S1032240624', 5, 2, 0, '25', 5, 1, 12, 7.73, 'Atharv Nayan Ratate', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(345, '2025-26', 'S1032240628', 5, 2, 0, '29', 5, 1, 5, 8.53, 'Tiya Sattabhayya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(346, '2025-26', 'S1032240630', 5, 2, 0, '31', 5, 1, 3, 8.05, 'Mohammad Hassan Shaikh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(347, '2025-26', 'S1032240634', 5, 2, 0, '35', 5, 1, 2, 6.00, 'Yug sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(348, '2025-26', 'S1032240635', 5, 2, 0, '36', 5, 1, 1, 8.50, 'Shishir Shetty', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(349, '2025-26', 'S1032240636', 5, 2, 0, '37', 5, 1, 1, 7.76, 'Dhruv shinde', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(350, '2025-26', 'S1032240644', 5, 2, 0, '45', 5, 1, 1, 6.00, 'Pranjal Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(351, '2025-26', 'S1032240651', 5, 2, 0, '52', 5, 1, 2, 6.66, 'Atharva Telkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(352, '2025-26', 'S1032240654', 5, 2, 0, '55', 5, 1, 1, 8.01, 'Saurabh Sushil Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(353, '2025-26', 'S1032240657', 5, 2, 0, '58', 5, 1, 11, 10.00, 'Anuj Upadhyay', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(354, '2025-26', 'S1032240658', 5, 2, 0, '59', 5, 1, 14, 8.20, 'Kaavya Upadhyay', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(355, '2025-26', 'S1032250169', 5, 2, 0, '67', 5, 1, 7, 0.00, 'Om Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(356, '2025-26', 'S1032250166', 5, 2, 0, '68', 5, 1, 14, 0.00, 'Gautam Thakur', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(357, '2025-26', 'S1032250163', 5, 2, 0, '69', 5, 1, 10, 7.91, 'Shivraj Umakant Khandare', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(358, '2025-26', 'S1032250165', 5, 2, 0, '70', 5, 1, 5, 8.68, 'Pratidnya Mohan wakshe', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(359, '2025-26', 'S1032250168', 5, 2, 0, '71', 5, 1, 10, 0.00, 'Ad4ya Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(360, '2025-26', 'S1032250167', 5, 2, 0, '72', 5, 1, 10, 0.00, 'Yadav Abhijeet Rajesh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(361, '2025-26', 'S1032250164', 5, 2, 0, '73', 5, 1, 5, 7.68, 'Ganesh Lagad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(362, '2025-26', 'S1032240318', 5, 0, 0, '3', 6, 1, 4, 7.60, 'Vignesh Bordikar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(363, '2025-26', 'S1032240329', 5, 0, 0, '14', 6, 1, 4, 9.30, 'Tanisha Giri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(364, '2025-26', 'S1032240376', 5, 0, 0, '61', 6, 1, 4, 9.23, 'Abishta Veludandi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(365, '2025-26', 'S1032240332', 5, 0, 0, '17', 6, 1, 11, 9.27, 'Aarya Gujar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(366, '2025-26', 'S1032240350', 5, 0, 0, '35', 6, 1, 11, 9.30, 'Aad4i Pawar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(367, '2025-26', 'S1032240357', 5, 0, 0, '42', 6, 1, 11, 9.62, 'Falakkhatoon Shaikh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(368, '2025-26', 'S1032240319', 5, 0, 0, '4', 6, 1, 9, 8.00, 'Ad4ya Chaudhari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(369, '2025-26', 'S1032240323', 5, 0, 0, '8', 6, 1, 2, 8.63, 'Parthsarthi Choudhary', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(370, '2025-26', 'S1032240331', 5, 0, 0, '16', 6, 1, 2, 8.33, 'Rajshree Gouda', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(371, '2025-26', 'S1032240334', 5, 0, 0, '19', 6, 1, 2, 9.64, 'Tanvi Prakash Jabare', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(372, '2025-26', 'S1032250131', 5, 0, 0, '65', 6, 1, 2, 8.41, 'Sakshi Bari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(373, '2025-26', 'S1032250134', 5, 0, 0, '66', 6, 1, 2, 8.18, 'Divyesh Dhananjay Desale', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(374, '2025-26', 'S1032250132', 5, 0, 0, '67', 6, 1, 2, 8.09, 'Yasir Irshad Khan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(375, '2025-26', 'S1032250133', 5, 0, 0, '68', 6, 1, 2, 8.55, 'Arya Prasad Raverkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(376, '2025-26', 'S1032250129', 5, 0, 0, '69', 6, 1, 2, 7.68, 'Sadhvi Ravipratap Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(377, '2025-26', '', 5, 0, 0, '70', 6, 1, 2, 0.00, 'Bhoomi Shrivastav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(378, '2025-26', '', 5, 0, 0, '14', 7, 1, 1, 0.00, 'Roh4 Santosh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(379, '2025-26', '', 5, 0, 0, '20', 7, 1, 1, 0.00, 'Santosh kamble', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(380, '2025-26', '', 5, 0, 0, '24', 7, 1, 4, 0.00, 'Anshul Malviya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(381, '2025-26', '', 5, 0, 0, '40', 7, 1, 16, 0.00, 'Rozario Aaron Bonny', '', '', '', 0, '', '', '', '0000-00-00 00:00:00');
INSERT INTO `st_student_master` (`student_id`, `academic_year`, `registration_no`, `class_id`, `division_id`, `grad_year`, `roll_no`, `department_id`, `specialization_id`, `specialization_subject_id`, `cgpa`, `fname`, `mobile`, `email`, `mark_list`, `status`, `m_sem1`, `m_sem2`, `m_sem3`, `created_at`) VALUES
(382, '2025-26', '', 5, 0, 0, '50', 7, 1, 1, 0.00, 'Kav4a Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(383, '2025-26', '', 5, 0, 0, '52', 7, 1, 1, 0.00, 'Shivansh Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(384, '2025-26', '', 5, 0, 0, '55', 7, 1, 2, 0.00, 'Vansh Timori', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(385, '2025-26', '', 5, 0, 0, '57', 7, 1, 1, 0.00, 'Khushi Unadkat', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(386, '2025-26', '', 5, 0, 0, '58', 7, 1, 16, 0.00, 'Upadhyay Aman Awadheshchandra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(387, '2025-26', '', 5, 0, 0, '59', 7, 1, 1, 0.00, 'Shikha Verma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(388, '2025-26', '', 5, 0, 0, '62', 7, 1, 1, 0.00, 'Ayush kamlesh yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(389, '2025-26', '', 5, 0, 0, '63', 7, 1, 1, 0.00, 'Yadav J4endra Subhashchandra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(390, '2025-26', '', 5, 0, 0, '67', 7, 1, 1, 0.00, 'Varuna Santosh Karande', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(391, '2025-26', '', 5, 0, 0, '6', 7, 1, 13, 0.00, 'Kunal Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(392, '2025-26', 'S1032240173', 5, 0, 0, '5', 8, 1, 17, 9.79, 'Saiesh Deshpande', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(393, '2025-26', 'S1032240189', 5, 0, 0, '21', 8, 1, 17, 8.19, 'Om Koltharkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(394, '2025-26', 'S1032240190', 5, 0, 0, '22', 8, 1, 17, 9.24, 'Samihan Dipak Kulkarni', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(395, '2025-26', 'S1032240194', 5, 0, 0, '26', 8, 1, 18, 8.60, 'Manthan Mutha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(396, '2025-26', 'S1032240196', 5, 0, 0, '28', 8, 1, 17, 7.36, 'Taran Ramakant Nevalkar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(397, '2025-26', 'S1032240204', 5, 0, 0, '36', 8, 1, 18, 8.35, 'Anas Qureshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(398, '2025-26', 'S1032240220', 5, 0, 0, '54', 8, 1, 17, 9.40, 'Bhavik Ramsagar Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(399, '2025-26', 'S1032240232', 5, 0, 0, '64', 8, 1, 17, 7.60, 'Spandan Yeole', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(400, '2025-26', 'S1032250128', 5, 0, 0, '66', 8, 1, 17, 8.55, 'Prathamesh Vasudev Bhagade', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(401, '2025-26', 'S1032250125', 5, 0, 0, '67', 8, 1, 17, 8.14, 'Ganesh Ishwar Birajdar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(402, '2025-26', 'S1032250124', 5, 0, 0, '68', 8, 1, 18, 7.73, 'Bhagyashree Jethwa', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(403, '2025-26', 'S1032240254', 5, 0, 0, '3', 9, 1, 1, 7.91, 'Kushagra Agarwal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(404, '2025-26', 'S1032240255', 5, 0, 0, '4', 9, 1, 1, 9.82, 'Kushal Laxmikant Borse', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(405, '2025-26', 'S1032240259', 5, 0, 0, '8', 9, 1, 1, 8.52, 'Parth Santosh Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(406, '2025-26', 'S1032240260', 5, 0, 0, '9', 9, 1, 1, 8.90, 'Shivam Dubey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(407, '2025-26', 'S1032240261', 5, 0, 0, '10', 9, 1, 2, 7.90, 'Rahul Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(408, '2025-26', 'S1032240262', 5, 0, 0, '11', 9, 1, 1, 10.00, 'Shreeya Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(409, '2025-26', '1032240263S', 5, 0, 0, '12', 9, 1, 1, 9.93, 'Vrishti Nilesh Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(410, '2025-26', '1032240265', 5, 0, 0, '14', 9, 1, 4, 8.73, 'Samartha Anil Jarad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(411, '2025-26', 'S1032240267', 5, 0, 0, '16', 9, 1, 4, 8.20, 'Charvi Joshi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(412, '2025-26', 'S1032240270', 5, 0, 0, '19', 9, 1, 1, 9.44, 'Sahil Kumbhar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(413, '2025-26', 'S1032240271', 5, 0, 0, '20', 9, 1, 1, 8.23, 'Tanishka Vijay Jaiswal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(414, '2025-26', 'S1032240272', 5, 0, 0, '21', 9, 1, 4, 7.81, 'Anjali Mergu', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(415, '2025-26', 'S1032240273', 5, 0, 0, '22', 9, 1, 1, 8.09, 'Devyansh Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(416, '2025-26', 'S1032240276', 5, 0, 0, '25', 9, 1, 10, 7.53, 'Tushar Mohta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(417, '2025-26', 'S1032240278', 5, 0, 0, '27', 9, 1, 10, 7.50, 'Shiva Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(418, '2025-26', 'S1032240281', 5, 0, 0, '30', 9, 1, 1, 9.45, 'Mugdha Patel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(419, '2025-26', 'S1032240282', 5, 0, 0, '31', 9, 1, 10, 7.10, 'Yash Patel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(420, '2025-26', '1032240283', 5, 0, 0, '32', 9, 1, 4, 8.26, 'Kishan Pathak', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(421, '2025-26', '1032240284', 5, 0, 0, '33', 9, 1, 5, 8.90, 'Shreya Nilesh Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(422, '2025-26', 'S1032240285', 5, 0, 0, '34', 9, 1, 4, 8.59, 'Ananya Sandeep Pillai', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(423, '2025-26', 'S1032240289', 5, 0, 0, '38', 9, 1, 4, 9.77, 'Saee Sawant', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(424, '2025-26', '1032240290', 5, 0, 0, '39', 9, 1, 4, 9.68, 'Shruti Vilas Sawant', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(425, '2025-26', 'S1032240291', 5, 0, 0, '40', 9, 1, 4, 8.36, 'Ash4a J4endra Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(426, '2025-26', 'S1032240293', 5, 0, 0, '42', 9, 1, 1, 7.12, 'Atharva Shelar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(427, '2025-26', '103224095', 5, 0, 0, '44', 9, 1, 1, 8.50, 'Prastuth Suresh Shetty', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(428, '2025-26', '1032240297', 5, 0, 0, '46', 9, 1, 4, 8.60, 'Ananya Shirke', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(429, '2025-26', 'S1032240298', 5, 0, 0, '47', 9, 1, 10, 7.91, 'Aryan Virendra Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(430, '2025-26', 'S1032240299', 5, 0, 0, '48', 9, 1, 2, 9.85, 'Soham J4endra Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(431, '2025-26', 'S1032240303', 5, 0, 0, '52', 9, 1, 1, 7.50, 'Nikhil Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(432, '2025-26', 'S1032240314', 5, 0, 0, '63', 9, 1, 4, 9.91, 'Pooja Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(433, '2025-26', 'S1032240563', 5, 0, 0, '65', 9, 1, 1, 8.90, 'Harshil Hemani', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(434, '2025-26', '1032250150', 5, 0, 0, '67', 9, 1, 4, 8.86, 'Yamini Yogesh Gaikar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(435, '2025-26', '1032250153', 5, 0, 0, '68', 9, 1, 4, 9.36, 'Seema Kushwaha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(436, '2025-26', 'S1032550152', 5, 0, 0, '69', 9, 1, 1, 8.36, 'Durgesh Mahindrakar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(437, '2025-26', 'S1032250151', 5, 0, 0, '72', 9, 1, 1, 8.36, 'Roh4', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(438, '2025-26', 'S1032250149', 5, 0, 0, '70', 9, 1, 10, 0.00, 'Arch4a Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(439, '2025-26', 'S1032240413', 5, 0, 0, '2', 10, 1, 1, 8.20, 'Yadnyesh Rajesh Andhari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(440, '2025-26', 'S1032240414', 5, 0, 0, '3', 10, 1, 13, 7.02, 'Rayaan Ansari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(441, '2025-26', 'MU0341120240218951', 5, 0, 0, '6', 10, 1, 1, 9.20, 'Vedantika Danavale', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(442, '2025-26', '24-MME-28', 5, 0, 0, '14', 10, 1, 1, 8.50, 'Saurabh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(443, '2025-26', 'S1032240426', 5, 0, 0, '15', 10, 1, 19, 8.05, 'Arnav Hake', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(444, '2025-26', '1032240429', 5, 0, 0, '18', 10, 1, 20, 7.73, 'Anup Sanjay Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(445, '2025-26', '1032240430', 5, 0, 0, '19', 10, 1, 20, 7.30, 'Parshuram Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(446, '2025-26', '1032240437', 5, 0, 0, '26', 10, 1, 5, 7.30, 'Masoom Monil Hathi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(447, '2025-26', '24-MME28-28', 5, 0, 0, '28', 10, 1, 19, 8.40, 'Yukta Mayekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(448, '2025-26', '24-MME29-28', 5, 0, 0, '29', 10, 1, 1, 8.75, 'Sia Mehta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(449, '2025-26', 'S1032240443', 5, 0, 0, '32', 10, 1, 1, 7.82, 'Manushree Naik', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(450, '2025-26', '1032240444', 5, 0, 0, '33', 10, 1, 19, 8.89, 'Jim4 oza', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(451, '2025-26', 'S1032240447', 5, 0, 0, '36', 10, 1, 19, 9.32, 'Harsh Parasnis', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(452, '2025-26', '123860042', 5, 0, 0, '38', 10, 1, 19, 8.14, 'Gautam patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(453, '2025-26', '1032240450', 5, 0, 0, '39', 10, 1, 5, 8.59, 'Rushikesh Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(454, '2025-26', '', 5, 0, 0, '40', 10, 1, 19, 7.38, 'Vedant Pawar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(455, '2025-26', 'S1032240452', 5, 0, 0, '41', 10, 1, 1, 8.40, 'Suraj Prajapati', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(456, '2025-26', 'S1032240456', 5, 0, 0, '45', 10, 1, 4, 7.28, 'Maniya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(457, '2025-26', 'S1032240471', 5, 0, 0, '60', 10, 1, 11, 9.38, 'Shubham Shivshankar Varma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(458, '2025-26', '1032240472', 5, 0, 0, '61', 10, 1, 19, 7.80, 'Kisan Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(459, '2025-26', '', 5, 1, 0, '38', 2, 1, 7, 9.73, 'Saumya Santosh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(460, '2025-26', '', 5, 1, 0, '55', 2, 1, 5, 8.50, 'Ankush Saroj jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(461, '2025-26', '', 5, 1, 0, '39', 2, 1, 3, 8.84, 'Sejal Mahesh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(462, '2025-26', '', 5, 1, 0, '47', 2, 1, 7, 9.82, 'Hardik Kamlesh Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(463, '2025-26', '', 5, 1, 0, '6', 2, 1, 7, 9.82, 'Bala Sudalaimuthu', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(464, '2025-26', '', 5, 1, 0, '59', 2, 1, 7, 9.73, 'Siddharth Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(465, '2025-26', '', 5, 1, 0, '50', 2, 1, 7, 9.86, 'Nirek Jaiswal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(466, '2025-26', '', 5, 1, 0, '51', 2, 1, 7, 8.64, 'Piyush Shivkumar Jaiswal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(467, '2025-26', '', 5, 1, 0, '36', 2, 1, 7, 9.45, 'Hemant Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(468, '2025-26', '', 5, 1, 0, '61', 2, 1, 7, 9.91, 'Anand Maruti Kalambe', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(469, '2025-26', '', 5, 1, 0, '58', 2, 1, 7, 9.14, 'Shekharkumar Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(470, '2025-26', '', 5, 1, 0, '57', 2, 1, 7, 7.68, 'Mantu Satish Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(471, '2025-26', '', 5, 1, 0, '21', 2, 1, 5, 7.25, 'Anurag Deb', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(472, '2025-26', '', 5, 1, 0, '53', 2, 1, 4, 8.77, 'Ganesh Raju Jani', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(473, '2025-26', '', 5, 1, 0, '3', 2, 1, 7, 9.70, 'Sahil Ambekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(474, '2025-26', '', 5, 1, 0, '2', 2, 1, 5, 7.86, 'Krish Ambani', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(475, '2025-26', '', 5, 1, 0, '34', 2, 1, 7, 9.32, 'Bhoomi Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(476, '2025-26', '', 5, 1, 0, '30', 2, 1, 7, 9.09, 'Gaurav Giri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(477, '2025-26', '', 5, 1, 0, '17', 2, 1, 7, 7.97, 'Ranjeet Singh Chauhan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(478, '2025-26', '', 5, 1, 0, '43', 2, 1, 10, 8.34, 'Swati Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(479, '2025-26', '', 5, 1, 0, '40', 2, 1, 3, 8.33, 'Shweta gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(480, '2025-26', '', 5, 2, 0, '2', 2, 1, 4, 9.57, 'Ridhi Karn', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(481, '2025-26', '', 5, 2, 0, '5', 2, 1, 7, 7.95, 'Khan Mohd Usaid Asad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(482, '2025-26', '', 5, 2, 0, '14', 2, 1, 5, 9.14, 'Alok Sharad Mahadik', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(483, '2025-26', '', 5, 2, 0, '17', 2, 1, 5, 8.77, 'Anuj Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(484, '2025-26', '', 5, 2, 0, '20', 2, 1, 7, 8.77, 'Varun Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(485, '2025-26', '', 5, 2, 0, '62', 2, 1, 7, 8.49, 'M4eshkumar Deeparam Puroh4', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(486, '2025-26', '', 5, 2, 0, '16', 2, 1, 7, 8.35, 'Prakash Mandal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(487, '2025-26', '', 5, 2, 0, '1', 2, 1, 5, 8.36, 'Yuvraj Kanojiya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(488, '2025-26', '', 5, 2, 0, '68', 2, 1, 7, 9.27, 'Arya Anil Pawar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(489, '2025-26', '', 5, 2, 0, '6', 2, 1, 7, 8.41, 'Sarvadnya Madhav Kharde', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(490, '2025-26', '', 5, 2, 0, '66', 2, 1, 7, 9.18, 'Harshini Mishal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(491, '2025-26', '', 5, 2, 0, '33', 2, 1, 5, 7.50, 'Prajwal Nangarepatil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(492, '2025-26', '', 5, 2, 0, '42', 2, 1, 7, 8.00, 'Aparna Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(493, '2025-26', '', 5, 2, 0, '35', 2, 1, 5, 9.09, 'Nipun Raj', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(494, '2025-26', '', 5, 2, 0, '27', 2, 1, 5, 7.64, 'Suryakant Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(495, '2025-26', '', 5, 2, 0, '25', 2, 1, 5, 9.42, 'Satyam Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(496, '2025-26', '', 5, 2, 0, '44', 2, 1, 7, 9.00, 'Sum4 Panigrahi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(497, '2025-26', '', 5, 2, 0, '7', 2, 1, 5, 8.82, 'Yashavi Jayprakash Kharwar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(498, '2025-26', '', 5, 2, 0, '4', 2, 1, 5, 8.03, 'Khan Arsalaan Mohammed Moomshad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(499, '2025-26', '', 5, 2, 0, '31', 2, 1, 5, 7.60, 'Arman Rahiman Mulani', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(500, '2025-26', '', 5, 2, 0, '53', 2, 1, 7, 7.93, 'Dikshant Pednekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(501, '2025-26', '', 5, 2, 0, '51', 2, 1, 3, 8.77, 'Soham Milind Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(502, '2025-26', '', 5, 2, 0, '69', 2, 1, 7, 9.64, 'Abhij4h Mahesh Shetty', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(503, '2025-26', '', 5, 2, 0, '65', 2, 1, 7, 9.32, 'Aaryan Santosh Chavan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(504, '2025-26', '', 5, 2, 0, '61', 2, 1, 7, 7.97, 'Pooja Puri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(505, '2025-26', '', 5, 2, 0, '15', 2, 1, 5, 8.42, 'Karthik Santosh Mahajan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(506, '2025-26', '', 5, 2, 0, '52', 2, 1, 7, 7.68, 'Tushar Patil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(507, '2025-26', '', 5, 2, 0, '9', 2, 1, 10, 8.30, 'Piyush Kishnani', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(508, '2025-26', '', 5, 2, 0, '55', 2, 1, 5, 9.23, 'Piyush Neeraj Prajapati', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(509, '2025-26', '', 5, 2, 0, '36', 2, 1, 5, 8.14, 'Aakash Nishad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(510, '2025-26', '', 5, 2, 0, '40', 2, 1, 7, 9.29, 'Ad4ya Vijayshankar Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(511, '2025-26', '', 5, 2, 0, '32', 2, 1, 5, 7.82, 'Swayam naik', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(512, '2025-26', '', 5, 2, 0, '45', 2, 1, 5, 8.63, 'Henil', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(513, '2025-26', '', 5, 2, 0, '21', 2, 1, 14, 7.50, 'Atharva Mhapsekar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(514, '2025-26', '', 5, 2, 0, '18', 2, 1, 14, 7.58, 'Harsh Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(515, '2025-26', '', 5, 2, 0, '19', 2, 1, 14, 7.50, 'N4in Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(516, '2025-26', '', 5, 3, 0, '63', 2, 1, 8, 8.47, 'Yash Manojkumar Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(517, '2025-26', '', 5, 3, 0, '34', 2, 1, 7, 8.09, 'Shivam Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(518, '2025-26', '', 5, 3, 0, '28', 2, 1, 7, 8.98, 'Ansh Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(519, '2025-26', '', 5, 3, 0, '62', 2, 1, 7, 8.80, 'Vishal Arvind Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(520, '2025-26', '', 5, 3, 0, '59', 2, 1, 10, 9.24, 'Riya Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(521, '2025-26', '', 5, 3, 0, '53', 2, 1, 10, 8.59, 'Pranav Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(522, '2025-26', '', 5, 3, 0, '27', 2, 1, 7, 9.36, 'Amar Ajay Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(523, '2025-26', '', 5, 3, 0, '29', 2, 1, 7, 8.55, 'Bhavesh sona singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(524, '2025-26', '', 5, 3, 0, '31', 2, 1, 19, 9.20, 'Priya Prashant Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(525, '2025-26', '', 5, 3, 0, '54', 2, 1, 7, 9.66, 'Sanch4a Sanjay Warkad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(526, '2025-26', '', 5, 3, 0, '32', 2, 1, 7, 9.20, 'Pushkar Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(527, '2025-26', '', 5, 3, 0, '26', 2, 1, 7, 8.91, 'Ad4ya Manoj Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(528, '2025-26', '', 5, 3, 0, '58', 2, 1, 7, 9.61, 'Pulk4 Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(529, '2025-26', '', 5, 3, 0, '56', 2, 1, 7, 8.10, 'Yadav Ashu Subash', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(530, '2025-26', '', 5, 3, 0, '47', 2, 1, 7, 7.40, 'Varma saurabh santosh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(531, '2025-26', '', 5, 3, 0, '4', 2, 1, 7, 9.12, 'Am4 Kr. Sahu', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(532, '2025-26', '', 5, 3, 0, '45', 2, 1, 7, 8.20, 'Shivam Upadhyay', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(533, '2025-26', '', 5, 3, 0, '30', 2, 1, 7, 8.50, 'Prakash singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(534, '2025-26', '', 5, 3, 0, '39', 2, 1, 8, 9.91, 'Diya Tailor', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(535, '2025-26', '', 5, 3, 0, '41', 2, 1, 7, 7.00, 'Atharva Tandel', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(536, '2025-26', '', 5, 3, 0, '43', 2, 1, 7, 9.23, 'Vedika Thorat', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(537, '2025-26', '', 5, 3, 0, '21', 2, 1, 8, 9.68, 'Nidhi Dilipkumar Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(538, '2025-26', '', 5, 3, 0, '1', 2, 1, 8, 8.95, 'Rudransh Puthan', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(539, '2025-26', '', 5, 3, 0, '35', 2, 1, 7, 9.73, 'Shruti Shailendra Kumar Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(540, '2025-26', '', 5, 3, 0, '42', 2, 1, 4, 8.77, 'Pratham Thakur', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(541, '2025-26', '', 5, 3, 0, '52', 2, 1, 7, 8.02, 'Abhishek Vishwakarma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(542, '2025-26', '', 5, 3, 0, '46', 2, 1, 7, 9.59, 'Parag Valam', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(543, '2025-26', '', 5, 3, 0, '60', 2, 1, 7, 9.59, 'Ruchi Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(544, '2025-26', '', 5, 3, 0, '25', 12, 1, 3, 8.70, 'Jiya Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(545, '2025-26', '', 5, 3, 0, '32', 12, 1, 3, 9.63, 'Samiksha Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(546, '2025-26', '', 5, 3, 0, '18', 12, 1, 3, 9.47, 'Shweta Sanjay Shukla', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(547, '2025-26', '', 5, 3, 0, '51', 12, 1, 3, 9.39, 'Jagdish Wagh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(548, '2025-26', '', 5, 3, 0, '21', 12, 1, 3, 9.34, 'Arp4a Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(549, '2025-26', '', 5, 3, 0, '53', 12, 1, 4, 7.62, 'Abhishek Surendra yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(550, '2025-26', '', 5, 1, 0, '23', 12, 1, 4, 9.21, 'Heer Dave', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(551, '2025-26', '', 5, 1, 0, '67', 12, 1, 4, 8.32, 'Sakshi Maurya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(552, '2025-26', '', 5, 1, 0, '49', 12, 1, 4, 9.98, 'Laksh4a Hingar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(553, '2025-26', '', 5, 1, 0, '19', 12, 1, 4, 9.24, 'Ramkumar Chaurasiya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(554, '2025-26', '', 5, 1, 0, '50', 12, 1, 4, 9.18, 'Anushka Mangesh Jadhav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(555, '2025-26', '', 5, 3, 0, '11', 12, 1, 4, 9.33, 'Om Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(556, '2025-26', '', 5, 3, 0, '45', 12, 1, 10, 9.20, 'Ved Sunil Nalavade', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(557, '2025-26', '', 5, 1, 0, '8', 12, 1, 10, 8.26, 'Ashr4 Bang', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(558, '2025-26', '', 5, 1, 0, '44', 12, 1, 10, 8.00, 'Gaurav Rajesh Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(559, '2025-26', '', 5, 1, 0, '38', 12, 1, 5, 7.87, 'Gaurav Manoj Thakur', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(560, '2025-26', '', 5, 3, 0, '62', 12, 1, 5, 9.36, 'Sudhirkumar Maganlal Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(561, '2025-26', '', 5, 1, 0, '11', 12, 1, 5, 9.64, 'Sanskar Sanjay Bhoir', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(562, '2025-26', '', 5, 1, 0, '52', 12, 1, 5, 9.72, 'Soham Nishant Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(563, '2025-26', '', 5, 1, 0, '55', 12, 1, 5, 8.95, 'N4in Nandlal Jaiswal', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(564, '2025-26', '', 5, 1, 0, '51', 12, 1, 5, 9.30, 'Bhavik Jain', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(565, '2025-26', '', 5, 1, 0, '48', 12, 1, 5, 7.92, 'Satyam Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(566, '2025-26', '', 5, 1, 0, '60', 12, 1, 5, 9.03, 'Gyaneshwar Jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(567, '2025-26', '', 5, 1, 0, '5', 12, 1, 5, 7.45, 'Omkar Auti', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(568, '2025-26', '', 5, 2, 0, '52', 12, 1, 5, 8.14, 'Prateek Prajapati', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(569, '2025-26', '', 5, 1, 0, '56', 12, 1, 5, 8.86, 'Ayush Jaju', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(570, '2025-26', '', 5, 3, 0, '34', 12, 1, 6, 7.63, 'Sushil singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(571, '2025-26', '', 5, 1, 0, '14', 12, 1, 21, 9.80, 'Vedant Bist', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(572, '2025-26', '', 5, 3, 0, '63', 12, 1, 7, 8.00, 'Ujala Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(573, '2025-26', '', 5, 1, 0, '1', 12, 1, 7, 8.96, 'Abdul Samad', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(574, '2025-26', '', 5, 2, 0, '28', 12, 1, 22, 8.47, 'Adh4hya Haridas Nair', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(575, '2025-26', '', 5, 3, 0, '42', 12, 1, 7, 8.80, 'Shreyash Tiwari', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(576, '2025-26', '', 5, 3, 0, '43', 12, 1, 7, 9.00, 'Gangotrinath Tripathi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(577, '2025-26', '', 5, 3, 0, '6', 12, 1, 22, 7.50, 'Anoop Sanjay Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(578, '2025-26', '', 5, 0, 0, '2', 13, 1, 1, 9.26, 'Aryan Prashant Bhalerao', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(579, '2025-26', '', 5, 0, 0, '5', 13, 1, 1, 7.20, 'Tushar Dix4', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(580, '2025-26', '', 5, 0, 0, '7', 13, 1, 1, 7.15, 'Kanhayya Kailas Gupta', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(581, '2025-26', '', 5, 0, 0, '17', 13, 1, 1, 9.37, 'Anuraag nair', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(582, '2025-26', '', 5, 0, 0, '18', 13, 1, 1, 8.20, 'Smr4i Pandey', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(583, '2025-26', '', 5, 0, 0, '28', 13, 1, 1, 8.59, 'Zabal Thakar', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(584, '2025-26', '', 5, 0, 0, '30', 13, 1, 1, 7.48, 'Saurabh Tripathi', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(585, '2025-26', '', 5, 0, 0, '34', 13, 1, 1, 9.18, 'Sheetal Bhendigeri', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(586, '2025-26', '', 5, 0, 0, '9', 13, 1, 2, 7.81, 'Ramkrishna jha', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(587, '2025-26', '', 5, 0, 0, '11', 13, 1, 2, 7.55, 'Niraj kanojiya', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(588, '2025-26', '', 5, 0, 0, '14', 13, 1, 2, 8.42, 'Daksh Am4 Khut', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(589, '2025-26', '', 5, 0, 0, '20', 13, 1, 2, 8.80, 'Rajpuroh4 Sanjaysing Lunkaran', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(590, '2025-26', '', 5, 0, 0, '22', 13, 1, 2, 8.38, 'Tejas Ramesh Sharma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(591, '2025-26', '', 5, 0, 0, '23', 13, 1, 2, 8.36, 'Shreya Santosh Shrivastav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(592, '2025-26', '', 5, 0, 0, '32', 13, 1, 2, 7.30, 'Shivam Yadav', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(593, '2025-26', '', 5, 0, 0, '24', 13, 1, 3, 8.07, 'Deep Singh', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(594, '2025-26', '', 5, 0, 0, '12', 13, 1, 10, 8.91, 'Payal Kawale', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(595, '2025-26', '', 5, 0, 0, '10', 13, 1, 5, 8.91, 'Anand kalirana', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(596, '2025-26', '', 5, 0, 0, '16', 13, 1, 5, 7.23, 'Shreya Mishra', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(597, '2025-26', '', 5, 0, 0, '31', 13, 1, 5, 8.04, 'Yatin varma', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(598, '2025-26', '', 5, 0, 0, '1', 13, 1, 19, 7.60, 'Girik Arora', '', '', '', 0, '', '', '', '0000-00-00 00:00:00'),
(599, '2026 - 2027', 'B1032243044', 2, 5, 1, '33', 1, 3, 18, NULL, 'Why AmI', '8997232773', 'why@tcetmumbai.in', NULL, 1, '[]', '[]', '[]', '2026-04-23 09:33:22'),
(600, '2026 - 2027', '9324877888', 3, 4, 2, '65', 4, 4, NULL, NULL, 'Mohan Yadav', '9302738289', 'rex@tcetmumbai.in', NULL, 1, '[]', '[]', '[]', '2026-04-23 09:55:22'),
(601, '2026 - 2027', '8988878889', 3, 4, 1, '67', 2, 4, NULL, NULL, 'Maini', '2382398938', 'maini@tcetmumbai.in', NULL, 1, '[]', '[]', '[]', '2026-04-24 09:28:45'),
(602, '2026 - 2027', '785877899', 2, 4, 1, '66', NULL, 4, NULL, NULL, 'Rishi Raj', '3434334433', 'rtsrt@tcetmumbai.in', NULL, 1, '[]', '[]', '[]', '2026-04-24 10:58:13'),
(603, '2026 - 2027', '83299383289', 2, 4, 2, '22', 1, 4, NULL, NULL, 'Rish Dubey', '2199210921', 'r@tcetmumbai.in', NULL, 1, '[]', '[]', '[]', '2026-04-24 11:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `st_student_master_old`
--

CREATE TABLE `st_student_master_old` (
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

--
-- Dumping data for table `st_student_master_old`
--

INSERT INTO `st_student_master_old` (`student_id`, `academic_year`, `registration_no`, `joining_date`, `class_id`, `division_id`, `batch_id`, `roll_no`, `department_id`, `specialization_id`, `specialization_subject_id`, `cgpa`, `fname`, `mname`, `lname`, `dob`, `gender`, `nationality`, `apaar_id`, `uan`, `pan`, `permanent_address`, `present_address`, `city`, `pincode`, `country`, `state`, `phone`, `mobile`, `email`, `photo`, `mark_list`, `birth_certificate`, `transfer_certificate`, `caste_certificate`, `migration_certificate`, `affidavit`, `status`, `created_at`) VALUES
(1, '2026 - 2027', '55', '2004-01-02', 2, 1, 1, '51', 1, 1, 1, 8.00, '', '', '', '', 'Male', 'INDIAN', '', '', '', '                ', '                ', '', '', 'India', 'Maharashtra', '', '', '', '', '', '', '', '', '', '', 0, '2026-04-16 13:44:07'),
(2, '2026 - 2027', '555', '2026-04-23', 2, 2, 1, '21', 1, 1, 1, 8.00, 'Ashutosh', '', 'Pandey', '', 'Male', 'INDIAN', '', '', '', 'A/102 Krishna Vihar Apt, Opp Firebigade Office, Vasai East', '                ', 'Vasai', '401209', 'India', 'Maharashtra', '9702420582', '', 'ashutosh3276s16@gmail.com', '', '', '', '', '', '', '', 1, '2026-04-17 06:27:11'),
(3, '2026 - 2027', '', '', 0, 0, 0, '', 0, 2, 0, NULL, '', '', '', '', 'Male', 'INDIAN', '', '', '', '                ', '                ', '', '', 'India', 'Maharashtra', '', '', '', '', '', '', '', '', '', '', 1, '2026-04-17 06:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `st_sub_menu_master`
--

CREATE TABLE `st_sub_menu_master` (
  `sub_menu_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `sub_menu_name` varchar(100) NOT NULL,
  `sub_menu_icon` varchar(100) NOT NULL DEFAULT 'fa fa-angle-double-right',
  `sub_menu_route` varchar(255) NOT NULL DEFAULT '#'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `st_sub_menu_master`
--

INSERT INTO `st_sub_menu_master` (`sub_menu_id`, `menu_id`, `sort_order`, `sub_menu_name`, `sub_menu_icon`, `sub_menu_route`) VALUES
(1, 1, 1, 'Register Students', 'fa fa-plus', 'student_admission.php'),
(2, 1, 2, 'List of Students', 'fa fa-info-circle', 'student-info.php'),
(5, 1, 3, 'Concise Details', 'fa fa-info-circle', 'student_concise_details.php'),
(7, 2, 1, 'Register Admin', 'fa fa-plus', 'admin_register.php'),
(8, 2, 2, 'Admin Info', 'fa fa-info-circle', 'admin_info.php'),
(9, 3, 1, 'Register Coordinator', 'fa fa-plus', 'coordinator_register.php'),
(10, 3, 2, 'Coordinator Info', 'fa fa-info-circle', 'coordinator_info.php'),
(11, 4, 1, 'Register Mentor', 'fa fa-plus', 'mentor_register.php'),
(12, 4, 2, 'Mentor Info', 'fa fa-info-circle', 'mentor_info.php'),
(13, 5, 1, 'Masters', 'fa fa-cog', 'class_crud_new.php#section-list'),
(20, 5, 2, 'Profile', 'fa fa-user', 'profile.php'),
(21, 5, 3, 'Update Password', 'fa fa-folder', 'change_password.php'),
(23, 5, 5, 'Menu', 'fa fa-folder', 'class_crud_new.php?tab=menu-list'),
(24, 5, 6, 'Sub Menu', 'fa fa-folder', 'class_crud_new.php?tab=sub-menu-list'),
(25, 5, 7, 'Side Menu Allocation', 'fa fa-check-square-o', 'allocation_master.php'),
(46, 5, 4, 'Manage Section', 'fa fa-list-alt', 'class_crud_new.php?tab=section-list'),
(47, 5, 8, 'Offline Marks Entry', 'fa fa-pencil-square-o', 'offline_marks_entry.php'),
(48, 4, 3, 'Mentor Allocation', 'fa fa-exchange', 'mentor_allocation.php');

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
(2, 'Amit Kumar', 'anurag@tcetmumbai.in', '8080590516', 1, 2, 0),
(3, 'Ashutosh Pandey', 'asdf@tcetmumbai.in', '234', 2, 2, 0),
(4, 'Ashutosh', '1032251400@tcetmumbai.in', '9702420582', 1, 4, 0);

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
-- Indexes for table `st_minorcourse`
--
ALTER TABLE `st_minorcourse`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `st_minorsubject`
--
ALTER TABLE `st_minorsubject`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `semester_id` (`semester_id`);

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
-- Indexes for table `st_offline_marks_entry`
--
ALTER TABLE `st_offline_marks_entry`
  ADD PRIMARY KEY (`entry_id`),
  ADD UNIQUE KEY `uniq_offline_marks` (`student_id`,`semester_id`,`course_name`);

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
-- Indexes for table `st_section_master`
--
ALTER TABLE `st_section_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `st_semester`
--
ALTER TABLE `st_semester`
  ADD PRIMARY KEY (`semester_id`);

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
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `st_student_master_old`
--
ALTER TABLE `st_student_master_old`
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
-- AUTO_INCREMENT for table `st_audit_log`
--
ALTER TABLE `st_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `st_class_master`
--
ALTER TABLE `st_class_master`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `st_credit_ledger`
--
ALTER TABLE `st_credit_ledger`
  MODIFY `credit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_department_master`
--
ALTER TABLE `st_department_master`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `st_division_master`
--
ALTER TABLE `st_division_master`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `st_mentor_student_mapping`
--
ALTER TABLE `st_mentor_student_mapping`
  MODIFY `mapping_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_menu_allocation_master`
--
ALTER TABLE `st_menu_allocation_master`
  MODIFY `menu_allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `st_menu_master`
--
ALTER TABLE `st_menu_master`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `st_minorsubject`
--
ALTER TABLE `st_minorsubject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

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
-- AUTO_INCREMENT for table `st_offline_marks_entry`
--
ALTER TABLE `st_offline_marks_entry`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `st_section_master`
--
ALTER TABLE `st_section_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `st_semester_master`
--
ALTER TABLE `st_semester_master`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `st_session_master`
--
ALTER TABLE `st_session_master`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `st_specialization_master`
--
ALTER TABLE `st_specialization_master`
  MODIFY `specialization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `st_specialization_subject_master`
--
ALTER TABLE `st_specialization_subject_master`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `st_student_master`
--
ALTER TABLE `st_student_master`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=604;

--
-- AUTO_INCREMENT for table `st_student_master_old`
--
ALTER TABLE `st_student_master_old`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `st_sub_menu_master`
--
ALTER TABLE `st_sub_menu_master`
  MODIFY `sub_menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `st_user_log_master`
--
ALTER TABLE `st_user_log_master`
  MODIFY `user_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `st_user_master`
--
ALTER TABLE `st_user_master`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `unaided_sub`
--
ALTER TABLE `unaided_sub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `st_minorsubject`
--
ALTER TABLE `st_minorsubject`
  ADD CONSTRAINT `st_minorsubject_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `st_minorcourse` (`course_id`),
  ADD CONSTRAINT `st_minorsubject_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `st_semester` (`semester_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
