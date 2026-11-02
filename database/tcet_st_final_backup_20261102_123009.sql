-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: tcet_st
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `st_audit_log`
--

DROP TABLE IF EXISTS `st_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_audit_log` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `affected_table` varchar(100) DEFAULT NULL,
  `affected_record` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser_user_agent` text DEFAULT NULL,
  `session_duration_seconds` int(11) DEFAULT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_id`),
  KEY `action_type` (`action_type`),
  KEY `user_id` (`user_id`),
  KEY `performed_at` (`performed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_audit_log`
--

LOCK TABLES `st_audit_log` WRITE;
/*!40000 ALTER TABLE `st_audit_log` DISABLE KEYS */;
INSERT INTO `st_audit_log` VALUES (1,1,'LOGIN_SUCCESS','st_login',1,'User ashutosh3276s16@gmail.com logged in successfully','ashutosh3276s16@gmail.com','127.0.0.1','unknown',NULL,NULL,'2026-10-17 18:17:06'),(2,1,'LOGIN_SUCCESS','st_login',2,'User Ashutosh logged in successfully','Ashutosh','127.0.0.1','unknown',NULL,NULL,'2026-10-17 18:17:06'),(3,2,'LOGIN_SUCCESS','st_login',3,'User amitanand.mishra@tcetmumbai.in logged in successfully','amitanand.mishra@tcetmumbai.in','127.0.0.1','unknown',NULL,NULL,'2026-10-17 18:17:06'),(4,1,'LOGIN_SUCCESS','st_login',2,'User Ashutosh logged in successfully','Ashutosh','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',NULL,NULL,'2026-10-17 18:20:31'),(5,1,'LOGIN_SUCCESS','st_login',1,'User ashutosh3276s16@gmail.com logged in successfully','ashutosh3276s16@gmail.com','127.0.0.1','unknown',NULL,NULL,'2026-10-17 18:48:09'),(6,1,'LOGIN_SUCCESS','st_login',2,'User Ashutosh logged in successfully','Ashutosh','127.0.0.1','unknown',NULL,NULL,'2026-10-17 18:48:09'),(7,2,'LOGIN_SUCCESS','st_login',3,'User amitanand.mishra@tcetmumbai.in logged in successfully','amitanand.mishra@tcetmumbai.in','127.0.0.1','unknown',NULL,NULL,'2026-10-17 18:48:09'),(8,2,'LOGIN_SUCCESS','st_login',4,'User Amitanand logged in successfully','Amitanand','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36',NULL,NULL,'2026-10-29 08:23:00'),(9,1,'LOGIN_SUCCESS','st_login',2,'User Ashutosh logged in successfully\nLogout: User \'Ashutosh\' logged out from IP 127.0.0.1. Browser: unknown','Ashutosh','127.0.0.1','unknown',0,'2026-11-02 06:21:48','2026-11-02 06:21:48'),(10,1,'LOGIN_SUCCESS','st_login',2,'User Ashutosh logged in successfully','Ashutosh','127.0.0.1','unknown',NULL,NULL,'2026-11-02 06:33:28'),(11,1,'STUDENT_DEACTIVATED','st_student_master',18,'Deactivated student ID 18',NULL,NULL,NULL,NULL,NULL,'2026-11-02 06:39:53'),(12,1,'STUDENT_REACTIVATED','st_student_master',18,'Reactivated student ID 18',NULL,NULL,NULL,NULL,NULL,'2026-11-02 06:39:53'),(13,1,'LOGIN_SUCCESS','st_login',2,'User Ashutosh logged in successfully\nLogout: User \'Ashutosh\' logged out from IP 127.0.0.1. Browser: unknown','Ashutosh','127.0.0.1','unknown',0,'2026-11-02 06:40:03','2026-11-02 06:40:03'),(14,0,'UNAUTHORIZED_ACCESS_ATTEMPT',NULL,NULL,'User role 0 attempted to access restricted route: smoke_test_all_roles.php',NULL,NULL,NULL,NULL,NULL,'2026-11-02 06:48:38');
/*!40000 ALTER TABLE `st_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_batch_master`
--

DROP TABLE IF EXISTS `st_batch_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_batch_master` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_name` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`batch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_batch_master`
--

LOCK TABLES `st_batch_master` WRITE;
/*!40000 ALTER TABLE `st_batch_master` DISABLE KEYS */;
INSERT INTO `st_batch_master` VALUES (1,'2027','2026-04-16 09:07:59'),(2,'2028','2026-04-16 09:08:15'),(3,'2029','2026-04-16 09:08:15');
/*!40000 ALTER TABLE `st_batch_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_class_master`
--

DROP TABLE IF EXISTS `st_class_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_class_master` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_class_master`
--

LOCK TABLES `st_class_master` WRITE;
/*!40000 ALTER TABLE `st_class_master` DISABLE KEYS */;
INSERT INTO `st_class_master` VALUES (1,'FY','2026-04-16 08:46:58'),(2,'SY','2026-04-16 08:46:58'),(3,'TY','2026-04-16 08:46:58'),(4,'FE','2026-04-16 08:46:58'),(5,'SE','2026-04-16 08:46:58'),(6,'TE','2026-04-16 08:46:58'),(7,'BE','2026-04-16 08:46:58');
/*!40000 ALTER TABLE `st_class_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_coordinator`
--

DROP TABLE IF EXISTS `st_coordinator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_coordinator` (
  `coordinator_id` int(11) NOT NULL AUTO_INCREMENT,
  `login_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`coordinator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_coordinator`
--

LOCK TABLES `st_coordinator` WRITE;
/*!40000 ALTER TABLE `st_coordinator` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_coordinator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_coordinator_mentor`
--

DROP TABLE IF EXISTS `st_coordinator_mentor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_coordinator_mentor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coordinator_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_coordinator_mentor`
--

LOCK TABLES `st_coordinator_mentor` WRITE;
/*!40000 ALTER TABLE `st_coordinator_mentor` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_coordinator_mentor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_credit_ledger`
--

DROP TABLE IF EXISTS `st_credit_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_credit_ledger` (
  `credit_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `credits_earned` decimal(4,2) NOT NULL DEFAULT 0.00,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`credit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_credit_ledger`
--

LOCK TABLES `st_credit_ledger` WRITE;
/*!40000 ALTER TABLE `st_credit_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_credit_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_department_master`
--

DROP TABLE IF EXISTS `st_department_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_department_master` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(150) NOT NULL,
  `program_name` varchar(150) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_department_master`
--

LOCK TABLES `st_department_master` WRITE;
/*!40000 ALTER TABLE `st_department_master` DISABLE KEYS */;
INSERT INTO `st_department_master` VALUES (1,'MCA','Master of Computer Applications','2026-10-12 05:55:58'),(2,'AIML','B.Tech. (Artificial Intelligence and Machine Learning)','2026-10-12 05:55:58'),(3,'CE','B.E. (Computer Engineering)','2026-10-12 05:55:58'),(4,'IT','B.E. (Information Technology)','2026-10-12 05:55:58'),(5,'EXTC','B.E. (Electronics & Communication Engineering)','2026-10-12 05:55:58'),(6,'ECS','B.E. (Electronics and Computer Science)','2026-10-12 05:55:58'),(7,'MECH','B.E. (Mechanical Engineering)','2026-10-12 05:55:58'),(8,'CIVIL','B.E. (Civil Engineering)','2026-10-12 05:55:58'),(9,'CSE-CS','Computer Science and Engineering (Cybersecurity)','2026-10-12 05:55:58'),(10,'MME','B.E. (Mechanical and Mechatronics Engineering)','2026-10-12 05:55:58'),(11,'BCA','Bachelor of Computer Applications','2026-10-12 05:55:58'),(12,'AIDS','B.Tech. (Artificial Intelligence and Data Science)','2026-10-12 05:55:58'),(13,'IOT','B.E. (Internet of Things)','2026-10-12 05:55:58'),(14,'MBA','MBA','2026-10-12 05:55:58'),(15,'All Branch','All Branch','2026-10-12 05:55:58');
/*!40000 ALTER TABLE `st_department_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_division_master`
--

DROP TABLE IF EXISTS `st_division_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_division_master` (
  `division_id` int(11) NOT NULL,
  `division_name` varchar(50) NOT NULL,
  PRIMARY KEY (`division_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_division_master`
--

LOCK TABLES `st_division_master` WRITE;
/*!40000 ALTER TABLE `st_division_master` DISABLE KEYS */;
INSERT INTO `st_division_master` VALUES (1,'A'),(2,'B'),(3,'C'),(4,'D'),(5,'E'),(6,'F');
/*!40000 ALTER TABLE `st_division_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_enrollment`
--

DROP TABLE IF EXISTS `st_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `status` enum('Active','Suspended','Completed','Dropped') NOT NULL DEFAULT 'Active',
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_enrollment`
--

LOCK TABLES `st_enrollment` WRITE;
/*!40000 ALTER TABLE `st_enrollment` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_login`
--

DROP TABLE IF EXISTS `st_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_login` (
  `login_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`login_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_login`
--

LOCK TABLES `st_login` WRITE;
/*!40000 ALTER TABLE `st_login` DISABLE KEYS */;
INSERT INTO `st_login` VALUES (1,'ashutosh3276s16@gmail.com','$2y$10$HKgsW9P5F04UrWlnLwF4gONCXQBugVj7SzHFRHc3Mk22G8vJp.tYy',1,'2026-10-17 18:16:28'),(2,'Ashutosh','$2y$10$SA8uAysuT3T6/w9JNpzveOmvmiNud3AfOuxXPvh9509iBghn4TLpi',1,'2026-10-17 18:16:28'),(3,'amitanand.mishra@tcetmumbai.in','$2y$10$R07Ry0OXcWtwSFuQCzM7cOymesDLMq07UT3R0IlrsSYllI/MG6LaO',2,'2026-10-17 18:16:28'),(4,'Amitanand','$2y$10$xkxDJIxuF2lygm2t/8YIk.UuKIG8zAwpvPDLzffsJyX9k1EL49ISS',2,'2026-10-17 18:16:28');
/*!40000 ALTER TABLE `st_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_mentor_student_mapping`
--

DROP TABLE IF EXISTS `st_mentor_student_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_mentor_student_mapping` (
  `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
  `mentor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `semester_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `uniq_student_semester` (`student_id`,`semester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_mentor_student_mapping`
--

LOCK TABLES `st_mentor_student_mapping` WRITE;
/*!40000 ALTER TABLE `st_mentor_student_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_mentor_student_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_mentor_subject_mapping`
--

DROP TABLE IF EXISTS `st_mentor_subject_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_mentor_subject_mapping` (
  `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
  `mentor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL DEFAULT 1,
  `semester_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `unique_period_subject_mentor` (`subject_id`,`semester_id`,`academic_year_id`),
  KEY `mentor_id` (`mentor_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_mentor_subject_mapping`
--

LOCK TABLES `st_mentor_subject_mapping` WRITE;
/*!40000 ALTER TABLE `st_mentor_subject_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_mentor_subject_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_menu_allocation_master`
--

DROP TABLE IF EXISTS `st_menu_allocation_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_menu_allocation_master` (
  `menu_allocation_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `sub_menu_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`menu_allocation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_menu_allocation_master`
--

LOCK TABLES `st_menu_allocation_master` WRITE;
/*!40000 ALTER TABLE `st_menu_allocation_master` DISABLE KEYS */;
INSERT INTO `st_menu_allocation_master` VALUES (1,0,1,1,NULL),(2,0,1,1,1),(3,0,1,1,2),(4,0,1,1,5),(5,0,1,2,NULL),(6,0,1,3,NULL),(7,0,1,3,7),(8,0,1,3,8),(9,0,1,4,NULL),(10,0,1,4,9),(11,0,1,4,10),(12,0,1,5,NULL),(13,0,1,5,11),(14,0,1,5,12),(15,0,1,2,13),(16,0,1,2,20),(17,0,1,2,21),(18,0,1,2,22),(19,0,1,2,25),(20,0,1,5,13),(21,0,1,5,20),(22,0,1,5,21),(23,0,1,5,25),(25,0,1,4,48),(26,0,1,5,49),(27,0,1,3,50),(45,0,3,1,1),(46,0,3,1,2),(47,0,3,1,5),(50,0,3,4,48),(51,0,3,5,20),(52,0,3,5,21),(53,0,4,1,2),(54,0,4,5,20),(55,0,4,5,21),(56,0,5,5,20),(57,0,5,5,21),(93,0,2,1,1),(94,0,2,1,2),(95,0,2,1,5),(96,0,2,3,9),(97,0,2,3,10),(98,0,2,3,50),(99,0,2,4,11),(100,0,2,4,12),(101,0,2,4,48),(102,0,2,5,20),(103,0,2,5,21),(106,0,1,1,54),(107,0,2,1,NULL),(108,0,2,1,54),(109,0,1,3,55),(110,0,2,3,NULL),(111,0,2,3,55),(112,0,3,3,NULL),(113,0,3,3,55),(114,0,5,5,NULL),(115,0,1,4,11),(116,0,2,4,NULL),(117,0,1,4,12),(118,0,3,4,NULL),(119,0,1,4,56),(120,0,2,4,56),(121,0,3,4,56),(122,0,1,4,57),(123,0,2,4,57),(124,0,3,4,57),(125,0,4,4,NULL),(126,0,4,4,57),(127,0,1,1,58),(128,0,2,1,58),(129,0,3,1,NULL),(130,0,3,1,58),(131,0,5,1,NULL),(132,0,5,1,58),(133,0,5,1,1),(134,0,1,3,59),(135,0,2,3,59),(136,0,3,3,59),(138,0,2,5,NULL),(139,0,3,5,NULL),(140,0,4,5,NULL),(141,0,4,4,56);
/*!40000 ALTER TABLE `st_menu_allocation_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_menu_master`
--

DROP TABLE IF EXISTS `st_menu_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_menu_master` (
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(100) NOT NULL,
  `menu_icon` varchar(100) NOT NULL DEFAULT 'fa fa-folder',
  PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_menu_master`
--

LOCK TABLES `st_menu_master` WRITE;
/*!40000 ALTER TABLE `st_menu_master` DISABLE KEYS */;
INSERT INTO `st_menu_master` VALUES (1,'Students','fa fa-graduation-cap'),(2,'Admin','fa fa-cogs'),(3,'coordinator','fa fa-user-secret'),(4,'mentor','fa fa-users'),(5,'Settings','fa fa-user');
/*!40000 ALTER TABLE `st_menu_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_minor_certificates`
--

DROP TABLE IF EXISTS `st_minor_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`certificate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_minor_certificates`
--

LOCK TABLES `st_minor_certificates` WRITE;
/*!40000 ALTER TABLE `st_minor_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_minor_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_minorcourse`
--

DROP TABLE IF EXISTS `st_minorcourse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_minorcourse` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `course_type` varchar(50) NOT NULL,
  `coordinator` varchar(100) DEFAULT NULL,
  `total_credits` int(11) DEFAULT 18,
  PRIMARY KEY (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_minorcourse`
--

LOCK TABLES `st_minorcourse` WRITE;
/*!40000 ALTER TABLE `st_minorcourse` DISABLE KEYS */;
INSERT INTO `st_minorcourse` VALUES (1,'Performing Arts - Music','Certification',NULL,18),(2,'Performing Arts - Dance','Certification',NULL,18),(3,'Performing Arts - Drama','Certification',NULL,18),(4,'Life Sciences - Science of Energy','MOOCs','Dr. Rajni Bahuguna',18),(5,'Life Sciences - Biotechnology','MOOCs',NULL,18),(6,'Mathematical Computing','MOOCs','Dr. Vivek Bharatiya',18),(7,'Finance Management','MOOCs','Mr. Sudhir Mundra',18),(8,'Life Skills - Health & Nutrition','Institute',NULL,18),(9,'Life Skills - Social & Welfare','MOOCs','Dr. Vinita Gupta',18),(10,'Life Skills - Physical Education (NCC)','Institute','Commandar Vijaypratap Singh',18);
/*!40000 ALTER TABLE `st_minorcourse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_minorsubject`
--

DROP TABLE IF EXISTS `st_minorsubject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_minorsubject` (
  `subject_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `duration` varchar(20) DEFAULT '12 weeks',
  `detail` text DEFAULT NULL,
  `credits` int(11) DEFAULT 3,
  PRIMARY KEY (`subject_id`),
  KEY `course_id` (`course_id`),
  KEY `semester_id` (`semester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_minorsubject`
--

LOCK TABLES `st_minorsubject` WRITE;
/*!40000 ALTER TABLE `st_minorsubject` DISABLE KEYS */;
INSERT INTO `st_minorsubject` VALUES (1,1,3,'Raga studies','12 weeks',NULL,3),(2,1,4,'Study of Tala','12 weeks',NULL,3),(3,1,5,'Hindustani music','12 weeks',NULL,3),(4,1,6,'Folk music','12 weeks',NULL,3),(5,1,7,'Western music','12 weeks',NULL,3),(6,2,3,'History of Dance','12 weeks',NULL,3),(7,2,4,'Indian Culture','12 weeks',NULL,3),(8,2,5,'Techniques of Dance','12 weeks',NULL,3),(9,2,6,'Performance Practice','12 weeks',NULL,3),(10,2,7,'Dance on Camera','12 weeks',NULL,3),(11,3,3,'Indian theatre','12 weeks',NULL,3),(12,3,4,'Basic vocal practice','12 weeks',NULL,3),(13,3,5,'Event management','12 weeks',NULL,3),(14,3,6,'Camera, light, sound','12 weeks',NULL,3),(15,3,7,'Projects on short films','12 weeks',NULL,3),(16,4,3,'Non-Conventional Energy Resources','12 weeks','https://nptel.ac.in/courses/121106014',3),(17,4,4,'Waste to Energy Conversion','12 weeks','https://nptel.ac.in/courses/103107125',3),(18,4,5,'Mass Momentum And Energy Balances In Engineering Analysis','12 weeks','https://nptel.ac.in/courses/105105186',3),(19,4,6,'Renewable Energy Engineering: Solar, Wind and Biomass Energy Systems','12 weeks','https://nptel.ac.in/courses/103103206',3),(20,4,7,'Energy Efficiency, Acoustics and Daylighting in Building','12 weeks','https://nptel.ac.in/courses/105102175',3),(21,4,8,'Energy Resources, Economics and Environment','12 weeks','https://nptel.ac.in/courses/109101171',3),(22,5,3,'Basics of Biology','12 weeks','https://onlinecourses.nptel.ac.in/noc23_bt25',3),(23,5,4,'Structural Biology','12 weeks','https://onlinecourses.nptel.ac.in/noc23_bt23',3),(24,5,5,'Aspects Of Biochemical Engineering','12 weeks','https://onlinecourses.nptel.ac.in/noc23_bt08',3),(25,5,6,'Computational Systems Biology','12 weeks','https://onlinecourses.nptel.ac.in/noc23_bt14',3),(26,5,7,'Bioinformatics: Algorithms and Applications','12 weeks','https://onlinecourses.nptel.ac.in/noc23_bt12',3),(27,5,8,'Material and Energy Balances','12 weeks','https://onlinecourses.nptel.ac.in/noc23_bt16',3),(28,6,3,'Essentials of Data Science with R Software-1: Probability & Statistical Inference','12 weeks','https://archive.nptel.ac.in/courses/111/104/111104146/',3),(29,6,4,'Essentials Of Data Science With R Software-2: Sampling Theory And Linear Regression Analysis','12 weeks','https://archive.nptel.ac.in/courses/111/104/111104147/',3),(30,6,5,'Numerical Linear Algebra','12 weeks','https://archive.nptel.ac.in/courses/111/107/111107106/',3),(31,6,6,'Convex Optimization','12 weeks','https://archive.nptel.ac.in/courses/111/104/111104068/',3),(32,6,7,'Regression Analysis','12 weeks','https://archive.nptel.ac.in/courses/111/105/111105042/',3),(33,6,8,'Applied Multivariate Statistical Modelling','12 weeks','https://archive.nptel.ac.in/courses/111/105/111105091/',3),(34,7,3,'Financial Statement Analysis and Reporting','12 weeks','https://onlinecourses.nptel.ac.in/noc23_mg12',3),(35,7,4,'Financial Accounting','12 weeks','https://onlinecourses.nptel.ac.in/noc23_mg65',3),(36,7,5,'Financial Derivatives & Risk Management','12 weeks','https://onlinecourses.nptel.ac.in/noc23_mg34',3),(37,7,6,'Financial Institutions And Markets','12 weeks','https://onlinecourses.nptel.ac.in/noc23_mg46',3),(38,7,7,'Safety and Risk Analytics','12 weeks','https://onlinecourses.nptel.ac.in/noc23_mg48',3),(39,7,8,'Artificial Intelligence (AI) for Investments','12 weeks','https://onlinecourses.nptel.ac.in/noc23_mg63',3),(40,8,3,'Basic Food Science-I','12 weeks','Basic concept on Food, Nutrition and Nutrients, Carbohydrates, Lipids, Proteins',3),(41,8,4,'Basic Food Science-II','12 weeks','Dietary Fibre, Minerals & Trace Elements, Vitamins, Water, Methods of Cooking and Preventing Nutrient Losses',3),(42,8,5,'Human Nutrition-I','12 weeks','Minimum Nutritional Requirement, Energy in Human Nutrition, Growth & Development from infancy to adulthood, Growth monitoring and promotion',3),(43,8,6,'Human Nutrition-II','12 weeks','Nutrition During Pregnancy, Nutrition during Lactation, Nutrition during Infancy',3),(44,8,7,'Therapeutic Nutrition','12 weeks','Principles of nutrition care, Etiology, Food allergy and food intolerance',3),(45,8,8,'Public Health Nutrition','12 weeks','Introduction to Nutritional deficiency diseases, Causes, Social Health problems, Nutrition for Special conditions, Food Security',3),(46,9,3,'Concept of Society and Social Issues in India - Social Justice & Regional Imbalance','12 weeks','https://onlinecourses.swayam2.ac.in/cec21_hs31',3),(47,9,4,'Woman Empowerment - Gender Justice and Workplace Security','12 weeks','https://onlinecourses.nptel.ac.in/noc20_mg42',3),(48,9,5,'Health, Hygiene and Diseases - Disaster Management','12 weeks','http://ecoursesonline.iasri.res.in/course/view.php?id=187',3),(49,9,6,'Environment Education for Sustainable Development','12 weeks','https://onlinecourses.nptel.ac.in/noc22_hs61/preview',3),(50,9,7,'Youth Empowerment Programs - Education & Awareness Activities','12 weeks','https://onlinecourses.nptel.ac.in/noc23_hs74/preview | https://onlinecourses.swayam2.ac.in/aic23_ge05/preview',3),(51,9,8,'Voluntary Organization (VOs) and Government Organization (GOs)','12 weeks','https://nptel.ac.in/courses/110106141',3),(52,10,1,'NCC Programme I','12 weeks','NCC General, National Integration-1, National Integration-2',3),(53,10,2,'NCC Programme II','12 weeks','Personality Development, Leadership, Communication, Health & Hygiene, Nutrition, Social Service, Community Development',3),(54,10,3,'NCC Programme III','12 weeks','Disaster Management, Adventure, Border & Coastal Area',3),(55,10,4,'NCC Programme IV','12 weeks','Environmental Awareness and Conservation, General Awareness, Armed Forces',3),(56,10,5,'NCC Programme V','12 weeks','Obstacle Training, Defence Entrance Examination & SSB Training, Government New Initiatives, Infantry Weapons',3),(57,10,6,'NCC Programme VI','12 weeks','Communication, Emotional Intelligence, Defence Entrance Exams and SSB Training, Indian Armed Forces, Medical and Paramedical Services of Armed Forces',3);
/*!40000 ALTER TABLE `st_minorsubject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_nptel_cancellations`
--

DROP TABLE IF EXISTS `st_nptel_cancellations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_nptel_cancellations` (
  `cancellation_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `course_name` varchar(200) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Submitted by Student',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cancellation_id`),
  KEY `idx_nptel_cancel_student` (`student_id`),
  KEY `idx_nptel_cancel_enroll` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_nptel_cancellations`
--

LOCK TABLES `st_nptel_cancellations` WRITE;
/*!40000 ALTER TABLE `st_nptel_cancellations` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_nptel_cancellations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_nptel_records`
--

DROP TABLE IF EXISTS `st_nptel_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`nptel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_nptel_records`
--

LOCK TABLES `st_nptel_records` WRITE;
/*!40000 ALTER TABLE `st_nptel_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_nptel_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_offline_marks_entry`
--

DROP TABLE IF EXISTS `st_offline_marks_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `uniq_offline_marks` (`student_id`,`semester_id`,`course_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_offline_marks_entry`
--

LOCK TABLES `st_offline_marks_entry` WRITE;
/*!40000 ALTER TABLE `st_offline_marks_entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_offline_marks_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_password_resets`
--

DROP TABLE IF EXISTS `st_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`reset_id`),
  KEY `idx_pwd_reset_user` (`user_id`),
  KEY `idx_pwd_reset_token` (`token_hash`),
  KEY `idx_pwd_reset_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_password_resets`
--

LOCK TABLES `st_password_resets` WRITE;
/*!40000 ALTER TABLE `st_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_role_master`
--

DROP TABLE IF EXISTS `st_role_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_role_master` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_role_master`
--

LOCK TABLES `st_role_master` WRITE;
/*!40000 ALTER TABLE `st_role_master` DISABLE KEYS */;
INSERT INTO `st_role_master` VALUES (1,'SUPER ADMIN'),(2,'ADMIN'),(3,'COORDINATOR / HOD'),(4,'MENTOR'),(5,'STUDENT');
/*!40000 ALTER TABLE `st_role_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_section_master`
--

DROP TABLE IF EXISTS `st_section_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_section_master` (
  `id` int(11) NOT NULL,
  `sections` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_section_master`
--

LOCK TABLES `st_section_master` WRITE;
/*!40000 ALTER TABLE `st_section_master` DISABLE KEYS */;
INSERT INTO `st_section_master` VALUES (1,'A','2022-04-27 01:03:40'),(2,'B','2022-04-27 01:04:04'),(3,'C','2022-04-27 01:04:18'),(4,'D','2022-04-27 01:04:26'),(5,'E','2022-04-27 01:04:33'),(6,'F','2022-04-27 01:04:42'),(16,'G','2026-04-17 12:57:33'),(18,'H','2026-04-17 13:00:41');
/*!40000 ALTER TABLE `st_section_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_semester`
--

DROP TABLE IF EXISTS `st_semester`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_semester` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_semester`
--

LOCK TABLES `st_semester` WRITE;
/*!40000 ALTER TABLE `st_semester` DISABLE KEYS */;
INSERT INTO `st_semester` VALUES (1,'Semester I'),(2,'Semester II'),(3,'Semester III'),(4,'Semester IV'),(5,'Semester V'),(6,'Semester VI'),(7,'Semester VII'),(8,'Semester VIII');
/*!40000 ALTER TABLE `st_semester` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_semester_master`
--

DROP TABLE IF EXISTS `st_semester_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_semester_master` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(50) NOT NULL,
  PRIMARY KEY (`semester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_semester_master`
--

LOCK TABLES `st_semester_master` WRITE;
/*!40000 ALTER TABLE `st_semester_master` DISABLE KEYS */;
INSERT INTO `st_semester_master` VALUES (1,'Semester 1'),(2,'Semester 2'),(3,'Semester 3'),(4,'Semester 4'),(5,'Semester 5'),(6,'Semester 6'),(7,'Semester 7'),(8,'Semester 8');
/*!40000 ALTER TABLE `st_semester_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_session_master`
--

DROP TABLE IF EXISTS `st_session_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_session_master` (
  `session_id` int(11) NOT NULL,
  `session_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_session_master`
--

LOCK TABLES `st_session_master` WRITE;
/*!40000 ALTER TABLE `st_session_master` DISABLE KEYS */;
INSERT INTO `st_session_master` VALUES (1,'2025 -2026'),(2,'2026 -2027'),(3,'2023-24'),(4,'2024-25');
/*!40000 ALTER TABLE `st_session_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_specialization_master`
--

DROP TABLE IF EXISTS `st_specialization_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_specialization_master` (
  `specialization_id` int(11) NOT NULL,
  `specialization_name` varchar(150) NOT NULL,
  `is_track` tinyint(1) NOT NULL DEFAULT 0,
  `track_code` varchar(50) DEFAULT NULL,
  `min_cgpa` decimal(4,2) NOT NULL DEFAULT 0.00,
  `kt_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `sem_from` int(11) DEFAULT NULL,
  `sem_to` int(11) DEFAULT NULL,
  `is_exclusive` tinyint(1) NOT NULL DEFAULT 0,
  `honors_eligibility` text DEFAULT NULL,
  `minor_eligibility` text DEFAULT NULL,
  `offering_program` varchar(150) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`specialization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_specialization_master`
--

LOCK TABLES `st_specialization_master` WRITE;
/*!40000 ALTER TABLE `st_specialization_master` DISABLE KEYS */;
INSERT INTO `st_specialization_master` VALUES (1,'Honours Degree',1,'honours',7.00,0,4,8,1,NULL,NULL,NULL,NULL),(2,'Honours with Research',1,'honours_research',7.50,0,7,8,1,NULL,NULL,'BE',NULL),(3,'Minor Degree',1,'minor',7.00,1,NULL,NULL,0,NULL,NULL,NULL,NULL),(4,'Minor Multidisciplinary',1,'minor_multidisciplinary',0.00,0,NULL,NULL,0,NULL,NULL,NULL,NULL),(5,'Artificial Intelligence',0,NULL,0.00,0,4,7,0,'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.E. (Computer Engineering)',3),(6,'Data Science',0,NULL,0.00,0,4,7,0,'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.E. (Computer Engineering)',3),(7,'Blockchain',0,NULL,0.00,0,4,7,0,'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.E. (Information Technology)',4),(8,'Internet of Things (IoT)',0,NULL,0.00,0,4,7,0,'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.E. (Electronics & Communication Engineering)',5),(9,'Cyber Security',0,NULL,0.00,0,4,7,0,'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.E. (Electronics & Communication Engineering)',5),(10,'Robotics',0,NULL,0.00,0,4,7,0,'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)','Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)','B.E. (Electronics and Computer Science)',6),(11,'Green Technology',0,NULL,0.00,0,4,7,0,'Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)','B.E. (Civil Engineering)',8),(12,'Infrastructure Engineering',0,NULL,0.00,0,4,7,0,'Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)','B.E. (Civil Engineering)',8),(13,'Energy Engineering',0,NULL,0.00,0,4,7,0,'Mechanical Engineering / Mechanical & Mechatronics','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering','B.E. (Mechanical Engineering)',7),(14,'3-D Printing',0,NULL,0.00,0,4,7,0,'Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)','B.E. (Mechanical Engineering)',7),(15,'Computational Biology',0,NULL,0.00,0,4,7,0,'B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.Tech. (Artificial Intelligence and Data Science)',12),(16,'Artificial Intelligence and Machine Learning — Sector Specific',0,NULL,0.00,0,4,7,0,'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.Tech. (Artificial Intelligence and Machine Learning)',2),(17,'Industrial IoT and Robotics',0,NULL,0.00,0,4,7,0,'B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','B.E. (Internet of Things)',13),(18,'Electric Vehicle Technology',0,NULL,0.00,0,4,7,0,'Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science','Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering','B.E. (Mechanical and Mechatronics Engineering)',10),(19,'Advanced Cyber Security and Quantum Cryptography',0,NULL,0.00,0,4,7,0,'Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)','Computer Science and Engineering (Cybersecurity)',9),(20,'Advanced Web Development',0,NULL,0.00,0,4,7,0,'All UG Programs','—','Information Technology',4),(21,'VLSI Design & Technology',0,NULL,0.00,0,4,7,0,'All UG Programs','—','Electronics & Computer Science Engineering',6),(22,'Universal Human Values (UHV)',0,NULL,0.00,0,4,7,0,'All UG Programs','—','All Branch',15),(23,'Indian Knowledge System',0,NULL,0.00,0,4,7,0,'All UG Programs','—','All Branch',15),(24,'Innovation, Entrepreneurial & Venture Development',0,NULL,0.00,0,4,7,0,'All UG Programs','—','All Branch',15),(25,'Business Development, Marketing and Finance',0,NULL,0.00,0,1,4,0,'All UG Programs','—','MBA',14);
/*!40000 ALTER TABLE `st_specialization_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_specialization_subject_master`
--

DROP TABLE IF EXISTS `st_specialization_subject_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_specialization_subject_master` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(255) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subject_code` varchar(50) DEFAULT NULL,
  `specialization_id` int(10) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `subject_type` varchar(100) DEFAULT NULL,
  `is_open_elective` tinyint(1) NOT NULL DEFAULT 0,
  `is_research_component` tinyint(1) NOT NULL DEFAULT 0,
  `academic_year_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `program_name` varchar(150) DEFAULT NULL,
  `year_of_study` varchar(50) DEFAULT NULL,
  `source_page` varchar(50) DEFAULT NULL,
  `source_notes` text DEFAULT NULL,
  `honors_eligibility` text DEFAULT NULL,
  `minor_eligibility` text DEFAULT NULL,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `uq_subject_department_semester` (`department_id`,`semester_id`,`specialization_id`,`subject_name`),
  KEY `fk_subject_semester` (`semester_id`),
  KEY `fk_subject_specialization` (`specialization_id`),
  CONSTRAINT `fk_subject_department` FOREIGN KEY (`department_id`) REFERENCES `st_department_master` (`department_id`),
  CONSTRAINT `fk_subject_semester` FOREIGN KEY (`semester_id`) REFERENCES `st_semester_master` (`semester_id`),
  CONSTRAINT `fk_subject_specialization` FOREIGN KEY (`specialization_id`) REFERENCES `st_specialization_master` (`specialization_id`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_specialization_subject_master`
--

LOCK TABLES `st_specialization_subject_master` WRITE;
/*!40000 ALTER TABLE `st_specialization_subject_master` DISABLE KEYS */;
INSERT INTO `st_specialization_subject_master` VALUES (1,'Artificial Intelligence and Machine Learning',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'Data Science',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,'Advance Web Development',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,'Advanced Cyber secur4y and Quantum Cryptography',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'Cyber Security',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,'Finance Management',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,'Sector Specific Specialization in Artificial Intelligence',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,'Innovation, Entrepreneurial and Venture Development',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,'Blockchain',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,'Business Development, Marketing and Finance',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'VLSI Design & Technology',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,'Sector Specific Specialization in 13',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,'3D Printing',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,'Internet of Things',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,'Railway Technology',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,'Energy Engineering',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,'Infrastructure Engineering',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,'Green Technology and Sustainability',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(19,'Robotics',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,'Electric Vehicle Technology',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,'Mathematical Computing',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,'Sector Specific Specialization in 2',NULL,1,NULL,1,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,'Artificial Intelligence','Department subject',1,NULL,1,3,3,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,'Machine Learning','Department subject',1,NULL,1,3,4,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(25,'Data Analytics','Department subject',1,NULL,1,4,3,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(26,'DevOps','Department subject',1,NULL,1,4,4,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(27,'Python for Data Science','Department subject',1,NULL,1,12,3,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(28,'Deep Learning','Department subject',1,NULL,1,12,4,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(29,'Essential Mathematics for ML',NULL,1,'MDL-4011',5,3,4,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','S.E (IV)','11','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(30,'Machine Learning',NULL,1,'MDL-5011',5,3,5,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','T.E (V)','14','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(31,'Deep Learning',NULL,1,'MDL-6011',5,3,6,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','T.E (VI)','18','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(32,'Reinforcement Learning',NULL,1,'MDL-7011',5,3,7,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','B.E (VII)','22','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(33,'Probability and Statistics',NULL,1,'MDL-4012-1',6,3,4,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','S.E (IV)','26','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(34,'Introduction to Database Systems',NULL,1,'MDL-4012-2',6,3,4,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','S.E (IV)','28','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(35,'Machine Learning',NULL,1,'MDL-5012',6,3,5,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','T.E (V)','31','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(36,'Deep Learning',NULL,1,'MDL-6012',6,3,6,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','T.E (VI)','35','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(37,'Learning Analytics Tools',NULL,1,'MDL-7012',6,3,7,'Honors / Minor',0,0,0,NULL,'B.E. (Computer Engineering)','B.E (VII)','39','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(38,'Blockchain Evolution & Cryptocurrencies',NULL,1,'MDL-4013',7,4,4,'Honors / Minor',0,0,0,NULL,'B.E. (Information Technology)','S.E (IV)','43','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(39,'Transactions in Blockchain and Smart Contracts',NULL,1,'MDL-5013',7,4,5,'Honors / Minor',0,0,0,NULL,'B.E. (Information Technology)','T.E (V)','46','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(40,'Blockchain and Ethereum',NULL,1,'MDL-6013',7,4,6,'Honors / Minor',0,0,0,NULL,'B.E. (Information Technology)','T.E (VI)','49','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(41,'Blockchain and Hyperledger Fabric',NULL,1,'MDL-7013',7,4,7,'Honors / Minor',0,0,0,NULL,'B.E. (Information Technology)','B.E (VII)','52','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(42,'Introduction To Internet of Things',NULL,1,'MDL-4014',8,5,4,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','S.E (IV)','56','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(43,'Embedded System Design With ARM',NULL,1,'MDL-5014',8,5,5,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','T.E (V)','58','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(44,'Programming, Data Structures And Algorithms Using Python',NULL,1,'MDL-6014',8,5,6,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','T.E (VI)','61','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(45,'Introduction to Industry 4.0 and Industrial Internet of Things',NULL,1,'MDL-7014',8,5,7,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','B.E (VII)','64','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(46,'Cyber Security & Privacy',NULL,1,'MDL-4015',9,5,4,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','S.E (IV)','68','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(47,'Cryptography And Network Security',NULL,1,'MDL-5015',9,5,5,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','T.E (V)','70','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(48,'Privacy And Security in Online Social Media',NULL,1,'MDL-6015',9,5,6,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','T.E (VI)','73','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(49,'Information Security and Forensics',NULL,1,'MDL-7015',9,5,7,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics & Communication Engineering)','B.E (VII)','76','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(50,'R Programming',NULL,1,'MDL-4016',10,6,4,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics and Computer Science)','S.E (IV)','80','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)','Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)'),(51,'Introduction to Robotics',NULL,1,'MDL-5016',10,6,5,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics and Computer Science)','T.E (V)','82','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)','Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)'),(52,'Robotics and Control : Theory and Practice',NULL,1,'MDL-6016',10,6,6,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics and Computer Science)','T.E (VI)','85','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)','Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)'),(53,'Robotics: Basics And Selected Advanced Concepts',NULL,1,'MDL-7016',10,6,7,'Honors / Minor',0,0,0,NULL,'B.E. (Electronics and Computer Science)','B.E (VII)','88','','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)','Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)'),(54,'Strategies for Sustainable Design',NULL,1,'MDL-4017',11,8,4,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','S.E (IV)','92','Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(55,'Municipal Solid Waste Management',NULL,1,'MDL-5017',11,8,5,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','T.E (V)','95','Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(56,'Environmental Quality Monitoring & Analysis',NULL,1,'MDL-6017',11,8,6,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','T.E (VI)','98','Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(57,'Wastewater Treatment and Recycling',NULL,1,'MDL-7017',11,8,7,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','B.E (VII)','100','Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(58,'Remote Sensing Essentials',NULL,1,'MDL4018',12,8,4,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','S.E (IV)','104','','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(59,'Pavement Materials',NULL,1,'MDL5018',12,8,5,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','T.E (V)','107','','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(60,'Analysis and Design of Bituminous Pavements',NULL,1,'MDL6018',12,8,6,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','T.E (VI)','110','','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(61,'Urban Transport System Planning',NULL,1,'MDL7018',12,8,7,'Honors / Minor',0,0,0,NULL,'B.E. (Civil Engineering)','B.E (VII)','113','','Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)'),(62,'Thermodynamics',NULL,1,'MDL4019',13,7,4,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','S.E (IV)','119','','Mechanical Engineering / Mechanical & Mechatronics','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(63,'Applied Thermodynamics',NULL,1,'MDL5019',13,7,5,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','T.E (V)','123','','Mechanical Engineering / Mechanical & Mechatronics','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(64,'Power Plant Engineering',NULL,1,'MDL6019',13,7,6,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','T.E (VI)','126','','Mechanical Engineering / Mechanical & Mechatronics','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(65,'Non-Conventional Energy Resources',NULL,1,'MDL7019',13,7,7,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','B.E (VII)','129','','Mechanical Engineering / Mechanical & Mechatronics','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(66,'Design For Additive Manufacturing',NULL,1,'MDL40110',14,7,4,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','S.E (IV)','132','','Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)'),(67,'Additive Manufacturing Processes',NULL,1,'MDL50110',14,7,5,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','T.E (V)','132','','Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)'),(68,'Materials, Energy Sources And Bonding Mechanisms',NULL,1,'MDL60110',14,7,6,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','T.E (VI)','132','','Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)'),(69,'Rapid Tooling And Industrial Applications',NULL,1,'MDL70110',14,7,7,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical Engineering)','B.E (VII)','132','','Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering','Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)'),(70,'Bio Informatics: Algorithms and Applications',NULL,1,'MDL40111',15,12,4,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Data Science)','S.T (IV)','150','','B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(71,'Next Generation Sequencing Technologies : Data Analysis and Applications',NULL,1,'MDL50111',15,12,5,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Data Science)','T.T (V)','152','','B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(72,'Computational Systems Biology',NULL,1,'MDL60111',15,12,6,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Data Science)','T.T (VI)','155','','B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(73,'Algorithms for protein modelling and engineering',NULL,1,'MDL70111',15,12,7,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Data Science)','B.T (VII)','158','','B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(74,'Python for Machine Learning & Data Science',NULL,1,'MDL40112',16,2,4,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Machine Learning)','S.T (IV)','163','Sector-specific specialization','B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(75,'AI Developer',NULL,1,'MDL50112',16,2,5,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Machine Learning)','T.T (V)','166','Sector-specific specialization','B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(76,'Machine Learning and Reinforcement Learning in Finance',NULL,1,'MDL60112_1',16,2,6,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Machine Learning)','T.T (VI)','189','Advanced course-I; Fintech sector','B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(77,'Game Design and Development with Unity',NULL,1,'MDL60112_2',16,2,6,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Machine Learning)','T.T (VI)','170','Advanced course-I; Education, Entertainment and Animation','B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(78,'Blockchain in Financial Services',NULL,1,'MDL70112_1',16,2,7,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Machine Learning)','B.T (VII)','193','Advanced course-II; Fintech sector','B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(79,'AI for Scientific Research',NULL,1,'MDL70112_2',16,2,7,'Honors / Minor',0,0,0,NULL,'B.Tech. (Artificial Intelligence and Machine Learning)','B.T (VII)','174','Advanced course-II; Education, Entertainment and Animation','B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(80,'Sensing, Actuation and Interfacing Systems for Industrial IoT',NULL,1,'MDL40113',17,13,4,'Honors / Minor',0,0,0,NULL,'B.E. (Internet of Things)','S.E (IV)','199','PDF VII-course header contains a semester label','B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(81,'Industrial Automation and Control',NULL,1,'MDL50113',17,13,5,'Honors / Minor',0,0,0,NULL,'B.E. (Internet of Things)','T.E (V)','202','PDF VII-course header contains a semester label','B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(82,'Industrial Robotics for Implementation',NULL,1,'MDL60113',17,13,6,'Honors / Minor',0,0,0,NULL,'B.E. (Internet of Things)','T.E (VI)','206','PDF VII-course header contains a semester label','B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(83,'Cognitive robotics & AI',NULL,1,'MDL70113',17,13,7,'Honors / Minor',0,0,0,NULL,'B.E. (Internet of Things)','B.E (VII)','209','PDF VII-course header contains a semester label','B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(84,'Introduction to Hybrid and Electric Vehicles',NULL,1,'MDL40114',18,10,4,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical and Mechatronics Engineering)','S.E (IV)','214','','Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science','Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(85,'Electric vehicles and Renewable Energy',NULL,1,'MDL50114',18,10,5,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical and Mechatronics Engineering)','T.E (V)','217','','Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science','Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(86,'Fundamentals of Electric vehicles: Technology & Economics',NULL,1,'MDL60114',18,10,6,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical and Mechatronics Engineering)','T.E (VI)','219','','Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science','Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(87,'EV for Academia & Vehicle Dynamics',NULL,1,'MDL70114',18,10,7,'Honors / Minor',0,0,0,NULL,'B.E. (Mechanical and Mechatronics Engineering)','B.E (VII)','223','','Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science','Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering'),(88,'Cyber Security and Privacy',NULL,1,'MDL40115',19,9,4,'Honors / Minor',0,0,0,NULL,'Computer Science and Engineering (Cybersecurity)','S.E (IV)','227','','Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(89,'Introduction to Information Security I, IIT Madras',NULL,1,'MDL50115',19,9,5,'Honors / Minor',0,0,0,NULL,'Computer Science and Engineering (Cybersecurity)','T.E (V)','229','','Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(90,'Privacy and Security in Online Social Media, IIIT Hyderabad',NULL,1,'MDL60115',19,9,6,'Honors / Minor',0,0,0,NULL,'Computer Science and Engineering (Cybersecurity)','T.E (VI)','231','','Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(91,'Quantum Algorithms and Cryptography',NULL,1,'MDL70115',19,9,7,'Honors / Minor',0,0,0,NULL,'Computer Science and Engineering (Cybersecurity)','B.E (VII)','233','','Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)','Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)'),(92,'Foundation of web technology',NULL,1,'MDL40116',20,4,4,'Honors',0,0,0,NULL,'Information Technology','S.E (IV)','237','','All UG Programs','—'),(93,'Advance Internet Technology',NULL,1,'MDL50116',20,4,5,'Honors',0,0,0,NULL,'Information Technology','T.E (V)','240','','All UG Programs','—'),(94,'Web X.0',NULL,1,'MDL60116',20,4,6,'Honors',0,0,0,NULL,'Information Technology','T.E (VI)','243','','All UG Programs','—'),(95,'Full Stack Web development',NULL,1,'MDL70116',20,4,7,'Honors',0,0,0,NULL,'Information Technology','B.E (VII)','246','','All UG Programs','—'),(96,'Digital Electronic Circuits',NULL,1,'MDL40117',21,6,4,'Honors',0,0,0,NULL,'Electronics & Computer Science Engineering','S.E (IV)','250','','All UG Programs','—'),(97,'System Design Through VERILOG and Fundamentals of electronic device fabrication',NULL,1,'MDL50117',21,6,5,'Honors',0,0,0,NULL,'Electronics & Computer Science Engineering','T.E (V)','253','','All UG Programs','—'),(98,'Digital IC Design',NULL,1,'MDL60117',21,6,6,'Honors',0,0,0,NULL,'Electronics & Computer Science Engineering','T.E (VI)','256','','All UG Programs','—'),(99,'Electronic Systems Design: Hands-on Circuits and PCB Design with CAD Software',NULL,1,'MDL70117',21,6,7,'Honors',0,0,0,NULL,'Electronics & Computer Science Engineering','B.E (VII)','258','','All UG Programs','—'),(100,'UHV-III Understanding Human Being, Nature and Existence Comprehensively',NULL,1,'MDL40118',22,15,4,'Honors',0,0,0,NULL,'All Branch','S.E (IV)','261','The PDF\'s p.260 scheme heading says \'Advanced; UHV is identified from course syllabus pages.','All UG Programs','—'),(101,'UHV IV: Vision for Humane Society',NULL,1,'MDL50118',22,15,5,'Honors',0,0,0,NULL,'All Branch','T.E (V)','264','The PDF\'s p.260 scheme heading says \'Advanced; UHV is identified from course syllabus pages.','All UG Programs','—'),(102,'UHV V: Human Psychology',NULL,1,'MDL60118',22,15,6,'Honors',0,0,0,NULL,'All Branch','T.E (VI)','267','The PDF\'s p.260 scheme heading says \'Advanced; UHV is identified from course syllabus pages.','All UG Programs','—'),(103,'UHV VI: Holistic Human Health',NULL,1,'MDL70118',22,15,7,'Honors',0,0,0,NULL,'All Branch','B.E (VII)','270','The PDF\'s p.260 scheme heading says \'Advanced; UHV is identified from course syllabus pages.','All UG Programs','—'),(104,'Introduction to Indian Knowledge System',NULL,1,'MDL40119',23,15,4,'Honors',0,0,0,NULL,'All Branch','S.E (IV)','274','','All UG Programs','—'),(105,'Vedic Mathematics',NULL,1,'MDL50119',23,15,5,'Honors',0,0,0,NULL,'All Branch','T.E (V)','277','','All UG Programs','—'),(106,'Indian Education System',NULL,1,'MDL60119',23,15,6,'Honors',0,0,0,NULL,'All Branch','T.E (VI)','280','','All UG Programs','—'),(107,'Indian Science and Technology',NULL,1,'MDL70119',23,15,7,'Honors',0,0,0,NULL,'All Branch','B.E (VII)','283','','All UG Programs','—'),(108,'Innovation and Start up policy',NULL,1,'MDL40120',24,15,4,'Honors',0,0,0,NULL,'All Branch','S.E (IV)','287','','All UG Programs','—'),(109,'Innovation, Business Models and Entrepreneurship',NULL,1,'MDL50120',24,15,5,'Honors',0,0,0,NULL,'All Branch','T.E (V)','290','','All UG Programs','—'),(110,'Introduction to Marketing Management',NULL,1,'MDL60120',24,15,6,'Honors',0,0,0,NULL,'All Branch','T.E (VI)','293','','All UG Programs','—'),(111,'Project Management',NULL,1,'MDL70120',24,15,7,'Honors',0,0,0,NULL,'All Branch','B.E (VII)','296','','All UG Programs','—'),(112,'Business Marketing - Technology Focus',NULL,1,'MDL01121',25,14,1,'Honors',0,0,0,NULL,'MBA','1st Year (I)','300','','All UG Programs','—'),(113,'Entrepreneurship Development',NULL,1,'MDL02121',25,14,2,'Honors',0,0,0,NULL,'MBA','1st Year (II)','303','','All UG Programs','—'),(114,'Digital Marketing',NULL,1,'MDL03121',25,14,3,'Honors',0,0,0,NULL,'MBA','2nd Year (III)','306','','All UG Programs','—'),(115,'Corporate Finance',NULL,1,'MDL04121',25,14,4,'Honors',0,0,0,NULL,'MBA','2nd Year (IV)','309','','All UG Programs','—');
/*!40000 ALTER TABLE `st_specialization_subject_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_student_master`
--

DROP TABLE IF EXISTS `st_student_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_student_master` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_no` varchar(200) NOT NULL,
  `class_id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `grad_year` int(11) DEFAULT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `specialization_track_id` int(11) DEFAULT NULL,
  `specialization_id` int(11) DEFAULT NULL,
  `specialization_subject_id` int(11) DEFAULT NULL,
  `minor_course_id` int(11) DEFAULT NULL,
  `minor_subject_id` int(11) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `fname` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mark_list` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `m_sem1` text NOT NULL,
  `m_sem2` text NOT NULL,
  `m_sem3` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year_id` int(11) DEFAULT NULL,
  `current_semester_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_student_master`
--

LOCK TABLES `st_student_master` WRITE;
/*!40000 ALTER TABLE `st_student_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_student_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_student_semester_history`
--

DROP TABLE IF EXISTS `st_student_semester_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_student_semester_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `semester_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `specialization_track_id` int(11) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `specialization_id` int(11) DEFAULT NULL,
  `specialization_subject_id` int(11) DEFAULT NULL,
  `minor_course_id` int(11) DEFAULT NULL,
  `minor_subject_id` int(11) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `research_component_i_id` int(11) DEFAULT NULL,
  `research_core_vii` varchar(255) DEFAULT NULL,
  `research_component_ii_id` int(11) DEFAULT NULL,
  `research_core_viii` varchar(255) DEFAULT NULL,
  `mentor_id` int(11) DEFAULT NULL,
  `progress_percent` decimal(5,2) DEFAULT NULL,
  `status` enum('Active','Completed','Locked') NOT NULL DEFAULT 'Active',
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`history_id`),
  UNIQUE KEY `uq_student_semester_history` (`student_id`,`semester_id`),
  KEY `idx_history_student` (`student_id`),
  KEY `idx_history_semester` (`semester_id`),
  KEY `idx_history_mentor` (`mentor_id`),
  KEY `fk_history_subject` (`specialization_subject_id`),
  CONSTRAINT `fk_history_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `st_user_master` (`user_id`),
  CONSTRAINT `fk_history_semester` FOREIGN KEY (`semester_id`) REFERENCES `st_semester_master` (`semester_id`),
  CONSTRAINT `fk_history_student` FOREIGN KEY (`student_id`) REFERENCES `st_student_master` (`student_id`),
  CONSTRAINT `fk_history_subject` FOREIGN KEY (`specialization_subject_id`) REFERENCES `st_specialization_subject_master` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_student_semester_history`
--

LOCK TABLES `st_student_semester_history` WRITE;
/*!40000 ALTER TABLE `st_student_semester_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `st_student_semester_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_sub_menu_master`
--

DROP TABLE IF EXISTS `st_sub_menu_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_sub_menu_master` (
  `sub_menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `sub_menu_name` varchar(100) NOT NULL,
  `sub_menu_icon` varchar(100) NOT NULL DEFAULT 'fa fa-angle-double-right',
  `sub_menu_route` varchar(255) NOT NULL DEFAULT '#',
  PRIMARY KEY (`sub_menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_sub_menu_master`
--

LOCK TABLES `st_sub_menu_master` WRITE;
/*!40000 ALTER TABLE `st_sub_menu_master` DISABLE KEYS */;
INSERT INTO `st_sub_menu_master` VALUES (1,1,1,'Enroll','fa fa-folder','student_admission.php'),(2,1,2,'List of Students','fa fa-info-circle','student-info.php'),(5,1,3,'Concise Details','fa fa-info-circle','student_concise_details.php'),(7,2,1,'Register Admin','fa fa-plus','admin_register.php'),(8,2,2,'Admin Info','fa fa-info-circle','admin_info.php'),(9,3,1,'Register Coordinator','fa fa-plus','coordinator_register.php'),(10,3,2,'Coordinator Info','fa fa-info-circle','coordinator_info.php'),(11,4,1,'Register Mentor','fa fa-plus','mentor_register.php'),(12,4,2,'Mentor Info','fa fa-info-circle','mentor_info.php'),(13,5,1,'Masters','fa fa-cog','class_crud_new.php#section-list'),(20,5,2,'Profile','fa fa-user','profile.php'),(21,5,3,'Update Password','fa fa-folder','change_password.php'),(25,5,4,'Side Menu Allocation','fa fa-check-square-o','allocation_master.php'),(48,4,3,'Mentor Allocation','fa fa-exchange','mentor_allocation.php'),(49,5,5,'Audit Log','fa fa-history','audit_log.php'),(50,3,3,'Coordinator Allocation','fa fa-exchange','coordinator_allocation.php'),(51,5,6,'Manage Section','fa fa-list-alt','class_crud_new.php?tab=section-list'),(52,5,7,'Menu Master','fa fa-folder-open','class_crud_new.php?tab=menu-list'),(53,5,8,'Sub Menu Master','fa fa-sitemap','class_crud_new.php?tab=sub-menu-list'),(54,1,4,'Monitor','fa fa-desktop','student_monitor.php'),(55,3,4,'Mentor Subject','fa fa-book','mentor_subject.php'),(56,4,4,'Mentor Assignments','fa fa-users','mentor_assignment.php'),(57,4,5,'Offline Marks Entry','fa fa-pencil-square-o','offline_marks_entry.php'),(58,1,5,'NPTEL Pass or Fail','fa fa-certificate','nptel_certificate.php'),(59,3,5,'Subject Management','fa fa-book','specialization_subject_manage.php');
/*!40000 ALTER TABLE `st_sub_menu_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `st_user_master`
--

DROP TABLE IF EXISTS `st_user_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `st_user_master` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(150) NOT NULL,
  `email_id` varchar(200) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `is_first_login` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `st_user_master`
--

LOCK TABLES `st_user_master` WRITE;
/*!40000 ALTER TABLE `st_user_master` DISABLE KEYS */;
INSERT INTO `st_user_master` VALUES (1,'Ashutosh','ashutosh3276s16@gmail.com','9999999999',1,1,0,0),(2,'Amitanand','amitanand.mishra@tcetmumbai.in','8888888888',1,1,0,0);
/*!40000 ALTER TABLE `st_user_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unaided_sub`
--

DROP TABLE IF EXISTS `unaided_sub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unaided_sub` (
  `id` int(11) NOT NULL,
  `sub` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unaided_sub`
--

LOCK TABLES `unaided_sub` WRITE;
/*!40000 ALTER TABLE `unaided_sub` DISABLE KEYS */;
INSERT INTO `unaided_sub` VALUES (1,'IT'),(2,'CS'),(3,'ELECTRONICS');
/*!40000 ALTER TABLE `unaided_sub` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'tcet_st'
--

--
-- Dumping routines for database 'tcet_st'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-02 12:30:10
