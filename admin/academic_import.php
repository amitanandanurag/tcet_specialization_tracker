<?php
/**
 * TCET ERP - Specialization & Course Data Importer
 * 
 * Imports and synchronizes:
 * - 87 Course records
 * - 21 Specialization areas
 * - 15 Academic programs
 * - Honors and Minor eligibility mappings
 * - Academic years, semesters, course codes, study years, and source notes
 * 
 * Features:
 * - Idempotent execution (safe to rerun multiple times)
 * - Prepared statements for all write operations
 * - Atomic database transaction with automatic rollback on error
 * - Full audit logging
 * - Dual execution mode: Web (Admin UI) and CLI (Headless migration runner)
 */

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "../database/db_connect.php";
    $db_handle = new DBController();

    // Check authorization: Super Admin (1) or Admin (2)
    $userType = (int)($_SESSION['user_type'] ?? $_SESSION['role_id'] ?? 0);
    if (!in_array($userType, [1, 2], true)) {
        header("Location: index.php");
        exit("Access Denied");
    }
} else {
    require_once __DIR__ . "/../database/db_connect.php";
    $db_handle = new DBController();
}

/**
 * Returns the definitive list of 21 Specializations with their metadata
 */
function getSourceSpecializations() {
    return [
        1 => [
            'id' => 5,
            'name' => 'Artificial Intelligence',
            'program' => 'B.E. (Computer Engineering)',
            'dept_code' => 'CE',
            'honors' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        2 => [
            'id' => 6,
            'name' => 'Data Science',
            'program' => 'B.E. (Computer Engineering)',
            'dept_code' => 'CE',
            'honors' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        3 => [
            'id' => 7,
            'name' => 'Blockchain',
            'program' => 'B.E. (Information Technology)',
            'dept_code' => 'IT',
            'honors' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        4 => [
            'id' => 8,
            'name' => 'Internet of Things (IoT)',
            'program' => 'B.E. (Electronics & Communication Engineering)',
            'dept_code' => 'EXTC',
            'honors' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        5 => [
            'id' => 9,
            'name' => 'Cyber Security',
            'program' => 'B.E. (Electronics & Communication Engineering)',
            'dept_code' => 'EXTC',
            'honors' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        6 => [
            'id' => 10,
            'name' => 'Robotics',
            'program' => 'B.E. (Electronics and Computer Science)',
            'dept_code' => 'ECS',
            'honors' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        7 => [
            'id' => 11,
            'name' => 'Green Technology',
            'program' => 'B.E. (Civil Engineering)',
            'dept_code' => 'CIVIL',
            'honors' => 'Civil Engineering',
            'minor' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        8 => [
            'id' => 12,
            'name' => 'Infrastructure Engineering',
            'program' => 'B.E. (Civil Engineering)',
            'dept_code' => 'CIVIL',
            'honors' => 'Civil Engineering',
            'minor' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        9 => [
            'id' => 13,
            'name' => 'Energy Engineering',
            'program' => 'B.E. (Mechanical Engineering)',
            'dept_code' => 'MECH',
            'honors' => 'Mechanical Engineering / Mechanical & Mechatronics',
            'minor' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        10 => [
            'id' => 14,
            'name' => '3-D Printing',
            'program' => 'B.E. (Mechanical Engineering)',
            'dept_code' => 'MECH',
            'honors' => 'Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering',
            'minor' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        11 => [
            'id' => 15,
            'name' => 'Computational Biology',
            'program' => 'B.Tech. (Artificial Intelligence and Data Science)',
            'dept_code' => 'AIDS',
            'honors' => 'B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        12 => [
            'id' => 16,
            'name' => 'Artificial Intelligence and Machine Learning — Sector Specific',
            'program' => 'B.Tech. (Artificial Intelligence and Machine Learning)',
            'dept_code' => 'AIML',
            'honors' => 'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        13 => [
            'id' => 17,
            'name' => 'Industrial IoT and Robotics',
            'program' => 'B.E. (Internet of Things)',
            'dept_code' => 'IOT',
            'honors' => 'B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        14 => [
            'id' => 18,
            'name' => 'Electric Vehicle Technology',
            'program' => 'B.E. (Mechanical and Mechatronics Engineering)',
            'dept_code' => 'MME',
            'honors' => 'Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science',
            'minor' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        15 => [
            'id' => 19,
            'name' => 'Advanced Cyber Security and Quantum Cryptography',
            'program' => 'Computer Science and Engineering (Cybersecurity)',
            'dept_code' => 'CSE-CS',
            'honors' => 'Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)',
            'minor' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        16 => [
            'id' => 20,
            'name' => 'Advanced Web Development',
            'program' => 'Information Technology',
            'dept_code' => 'IT',
            'honors' => 'All UG Programs',
            'minor' => '—',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        17 => [
            'id' => 21,
            'name' => 'VLSI Design & Technology',
            'program' => 'Electronics & Computer Science Engineering',
            'dept_code' => 'ECS',
            'honors' => 'All UG Programs',
            'minor' => '—',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        18 => [
            'id' => 22,
            'name' => 'Universal Human Values (UHV)',
            'program' => 'All Branch',
            'dept_code' => 'All Branch',
            'honors' => 'All UG Programs',
            'minor' => '—',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        19 => [
            'id' => 23,
            'name' => 'Indian Knowledge System',
            'program' => 'All Branch',
            'dept_code' => 'All Branch',
            'honors' => 'All UG Programs',
            'minor' => '—',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        20 => [
            'id' => 24,
            'name' => 'Innovation, Entrepreneurial & Venture Development',
            'program' => 'All Branch',
            'dept_code' => 'All Branch',
            'honors' => 'All UG Programs',
            'minor' => '—',
            'sem_from' => 4,
            'sem_to' => 7
        ],
        21 => [
            'id' => 25,
            'name' => 'Business Development, Marketing and Finance',
            'program' => 'MBA',
            'dept_code' => 'MBA',
            'honors' => 'All UG Programs',
            'minor' => '—',
            'sem_from' => 1,
            'sem_to' => 4
        ]
    ];
}

/**
 * Returns the definitive list of 87 Courses extracted from the PDF
 */
function getSourceCourses() {
    return [
        // 1-4: Artificial Intelligence
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Artificial Intelligence', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4011', 'course_name' => 'Essential Mathematics for ML', 'page' => 11, 'notes' => ''],
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Artificial Intelligence', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL-5011', 'course_name' => 'Machine Learning', 'page' => 14, 'notes' => ''],
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Artificial Intelligence', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL-6011', 'course_name' => 'Deep Learning', 'page' => 18, 'notes' => ''],
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Artificial Intelligence', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL-7011', 'course_name' => 'Reinforcement Learning', 'page' => 22, 'notes' => ''],

        // 5-9: Data Science
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Data Science', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4012-1', 'course_name' => 'Probability and Statistics', 'page' => 26, 'notes' => ''],
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Data Science', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4012-2', 'course_name' => 'Introduction to Database Systems', 'page' => 28, 'notes' => ''],
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Data Science', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL-5012', 'course_name' => 'Machine Learning', 'page' => 31, 'notes' => ''],
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Data Science', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL-6012', 'course_name' => 'Deep Learning', 'page' => 35, 'notes' => ''],
        ['program' => 'B.E. (Computer Engineering)', 'specialization' => 'Data Science', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL-7012', 'course_name' => 'Learning Analytics Tools', 'page' => 39, 'notes' => ''],

        // 10-13: Blockchain
        ['program' => 'B.E. (Information Technology)', 'specialization' => 'Blockchain', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4013', 'course_name' => 'Blockchain Evolution & Cryptocurrencies', 'page' => 43, 'notes' => ''],
        ['program' => 'B.E. (Information Technology)', 'specialization' => 'Blockchain', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL-5013', 'course_name' => 'Transactions in Blockchain and Smart Contracts', 'page' => 46, 'notes' => ''],
        ['program' => 'B.E. (Information Technology)', 'specialization' => 'Blockchain', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL-6013', 'course_name' => 'Blockchain and Ethereum', 'page' => 49, 'notes' => ''],
        ['program' => 'B.E. (Information Technology)', 'specialization' => 'Blockchain', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.Tech (Internet of Things); B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL-7013', 'course_name' => 'Blockchain and Hyperledger Fabric', 'page' => 52, 'notes' => ''],

        // 14-17: Internet of Things (IoT)
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Internet of Things (IoT)', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4014', 'course_name' => 'Introduction To Internet of Things', 'page' => 56, 'notes' => ''],
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Internet of Things (IoT)', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL-5014', 'course_name' => 'Embedded System Design With ARM', 'page' => 58, 'notes' => ''],
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Internet of Things (IoT)', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL-6014', 'course_name' => 'Programming, Data Structures And Algorithms Using Python', 'page' => 61, 'notes' => ''],
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Internet of Things (IoT)', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL-7014', 'course_name' => 'Introduction to Industry 4.0 and Industrial Internet of Things', 'page' => 64, 'notes' => ''],

        // 18-21: Cyber Security
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Cyber Security', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4015', 'course_name' => 'Cyber Security & Privacy', 'page' => 68, 'notes' => ''],
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Cyber Security', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL-5015', 'course_name' => 'Cryptography And Network Security', 'page' => 70, 'notes' => ''],
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Cyber Security', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL-6015', 'course_name' => 'Privacy And Security in Online Social Media', 'page' => 73, 'notes' => ''],
        ['program' => 'B.E. (Electronics & Communication Engineering)', 'specialization' => 'Cyber Security', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL-7015', 'course_name' => 'Information Security and Forensics', 'page' => 76, 'notes' => ''],

        // 22-25: Robotics
        ['program' => 'B.E. (Electronics and Computer Science)', 'specialization' => 'Robotics', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4016', 'course_name' => 'R Programming', 'page' => 80, 'notes' => ''],
        ['program' => 'B.E. (Electronics and Computer Science)', 'specialization' => 'Robotics', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL-5016', 'course_name' => 'Introduction to Robotics', 'page' => 82, 'notes' => ''],
        ['program' => 'B.E. (Electronics and Computer Science)', 'specialization' => 'Robotics', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL-6016', 'course_name' => 'Robotics and Control : Theory and Practice', 'page' => 85, 'notes' => ''],
        ['program' => 'B.E. (Electronics and Computer Science)', 'specialization' => 'Robotics', 'honors_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Internet of Things); B.E (Mechanical & Mechatronics)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL-7016', 'course_name' => 'Robotics: Basics And Selected Advanced Concepts', 'page' => 88, 'notes' => ''],

        // 26-29: Green Technology
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Green Technology', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL-4017', 'course_name' => 'Strategies for Sustainable Design', 'page' => 92, 'notes' => 'Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.'],
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Green Technology', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL-5017', 'course_name' => 'Municipal Solid Waste Management', 'page' => 95, 'notes' => 'Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.'],
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Green Technology', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL-6017', 'course_name' => 'Environmental Quality Monitoring & Analysis', 'page' => 98, 'notes' => 'Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.'],
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Green Technology', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL-7017', 'course_name' => 'Wastewater Treatment and Recycling', 'page' => 100, 'notes' => 'Green Technology scheme page has a blank specialization-name field; course syllabus pages identify it as Green Technology.'],

        // 30-33: Infrastructure Engineering
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Infrastructure Engineering', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL4018', 'course_name' => 'Remote Sensing Essentials', 'page' => 104, 'notes' => ''],
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Infrastructure Engineering', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL5018', 'course_name' => 'Pavement Materials', 'page' => 107, 'notes' => ''],
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Infrastructure Engineering', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL6018', 'course_name' => 'Analysis and Design of Bituminous Pavements', 'page' => 110, 'notes' => ''],
        ['program' => 'B.E. (Civil Engineering)', 'specialization' => 'Infrastructure Engineering', 'honors_to' => 'Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); Mechanical Engineering; B.E (Mechanical & Mechatronics); B.Tech (Internet of Things)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL7018', 'course_name' => 'Urban Transport System Planning', 'page' => 113, 'notes' => ''],

        // 34-37: Energy Engineering
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => 'Energy Engineering', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL4019', 'course_name' => 'Thermodynamics', 'page' => 119, 'notes' => ''],
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => 'Energy Engineering', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL5019', 'course_name' => 'Applied Thermodynamics', 'page' => 123, 'notes' => ''],
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => 'Energy Engineering', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL6019', 'course_name' => 'Power Plant Engineering', 'page' => 126, 'notes' => ''],
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => 'Energy Engineering', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL7019', 'course_name' => 'Non-Conventional Energy Resources', 'page' => 129, 'notes' => ''],

        // 38-41: 3-D Printing
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => '3-D Printing', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)', 'academic_year' => '2024-25', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40110', 'course_name' => 'Design For Additive Manufacturing', 'page' => 132, 'notes' => ''],
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => '3-D Printing', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)', 'academic_year' => '2024-25', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50110', 'course_name' => 'Additive Manufacturing Processes', 'page' => 132, 'notes' => ''],
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => '3-D Printing', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)', 'academic_year' => '2024-25', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60110', 'course_name' => 'Materials, Energy Sources And Bonding Mechanisms', 'page' => 132, 'notes' => ''],
        ['program' => 'B.E. (Mechanical Engineering)', 'specialization' => '3-D Printing', 'honors_to' => 'Mechanical Engineering / Mechanical & Mechatronics / Civil Engineering', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; Electronics & Computer Science; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things)', 'academic_year' => '2024-25', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70110', 'course_name' => 'Rapid Tooling And Industrial Applications', 'page' => 132, 'notes' => ''],

        // 42-45: Computational Biology
        ['program' => 'B.Tech. (Artificial Intelligence and Data Science)', 'specialization' => 'Computational Biology', 'honors_to' => 'B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'S.T (IV)', 'semester' => 'IV', 'course_code' => 'MDL40111', 'course_name' => 'Bio Informatics: Algorithms and Applications', 'page' => 150, 'notes' => ''],
        ['program' => 'B.Tech. (Artificial Intelligence and Data Science)', 'specialization' => 'Computational Biology', 'honors_to' => 'B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.T (V)', 'semester' => 'V', 'course_code' => 'MDL50111', 'course_name' => 'Next Generation Sequencing Technologies : Data Analysis and Applications', 'page' => 152, 'notes' => ''],
        ['program' => 'B.Tech. (Artificial Intelligence and Data Science)', 'specialization' => 'Computational Biology', 'honors_to' => 'B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.T (VI)', 'semester' => 'VI', 'course_code' => 'MDL60111', 'course_name' => 'Computational Systems Biology', 'page' => 155, 'notes' => ''],
        ['program' => 'B.Tech. (Artificial Intelligence and Data Science)', 'specialization' => 'Computational Biology', 'honors_to' => 'B.Tech (Artificial Intelligence & Data Science) / B.Tech (Artificial Intelligence & Machine Learning) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'B.T (VII)', 'semester' => 'VII', 'course_code' => 'MDL70111', 'course_name' => 'Algorithms for protein modelling and engineering', 'page' => 158, 'notes' => ''],

        // 46-51: Artificial Intelligence and Machine Learning — Sector Specific
        ['program' => 'B.Tech. (Artificial Intelligence and Machine Learning)', 'specialization' => 'Artificial Intelligence and Machine Learning — Sector Specific', 'honors_to' => 'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'S.T (IV)', 'semester' => 'IV', 'course_code' => 'MDL40112', 'course_name' => 'Python for Machine Learning & Data Science', 'page' => 163, 'notes' => 'Sector-specific specialization'],
        ['program' => 'B.Tech. (Artificial Intelligence and Machine Learning)', 'specialization' => 'Artificial Intelligence and Machine Learning — Sector Specific', 'honors_to' => 'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.T (V)', 'semester' => 'V', 'course_code' => 'MDL50112', 'course_name' => 'AI Developer', 'page' => 166, 'notes' => 'Sector-specific specialization'],
        ['program' => 'B.Tech. (Artificial Intelligence and Machine Learning)', 'specialization' => 'Artificial Intelligence and Machine Learning — Sector Specific', 'honors_to' => 'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.T (VI)', 'semester' => 'VI', 'course_code' => 'MDL60112_1', 'course_name' => 'Machine Learning and Reinforcement Learning in Finance', 'page' => 189, 'notes' => 'Advanced course-I; Fintech sector'],
        ['program' => 'B.Tech. (Artificial Intelligence and Machine Learning)', 'specialization' => 'Artificial Intelligence and Machine Learning — Sector Specific', 'honors_to' => 'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.T (VI)', 'semester' => 'VI', 'course_code' => 'MDL60112_2', 'course_name' => 'Game Design and Development with Unity', 'page' => 170, 'notes' => 'Advanced course-I; Education, Entertainment and Animation'],
        ['program' => 'B.Tech. (Artificial Intelligence and Machine Learning)', 'specialization' => 'Artificial Intelligence and Machine Learning — Sector Specific', 'honors_to' => 'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'B.T (VII)', 'semester' => 'VII', 'course_code' => 'MDL70112_1', 'course_name' => 'Blockchain in Financial Services', 'page' => 193, 'notes' => 'Advanced course-II; Fintech sector'],
        ['program' => 'B.Tech. (Artificial Intelligence and Machine Learning)', 'specialization' => 'Artificial Intelligence and Machine Learning — Sector Specific', 'honors_to' => 'B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.E (Computer Science & Engineering) (Cyber Security)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'B.T (VII)', 'semester' => 'VII', 'course_code' => 'MDL70112_2', 'course_name' => 'AI for Scientific Research', 'page' => 174, 'notes' => 'Advanced course-II; Education, Entertainment and Animation'],

        // 52-55: Industrial IoT and Robotics
        ['program' => 'B.E. (Internet of Things)', 'specialization' => 'Industrial IoT and Robotics', 'honors_to' => 'B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40113', 'course_name' => 'Sensing, Actuation and Interfacing Systems for Industrial IoT', 'page' => 199, 'notes' => 'PDF VII-course header contains a semester label'],
        ['program' => 'B.E. (Internet of Things)', 'specialization' => 'Industrial IoT and Robotics', 'honors_to' => 'B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50113', 'course_name' => 'Industrial Automation and Control', 'page' => 202, 'notes' => 'PDF VII-course header contains a semester label'],
        ['program' => 'B.E. (Internet of Things)', 'specialization' => 'Industrial IoT and Robotics', 'honors_to' => 'B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60113', 'course_name' => 'Industrial Robotics for Implementation', 'page' => 206, 'notes' => 'PDF VII-course header contains a semester label'],
        ['program' => 'B.E. (Internet of Things)', 'specialization' => 'Industrial IoT and Robotics', 'honors_to' => 'B.Tech (Internet of Things) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2024-25', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70113', 'course_name' => 'Cognitive robotics & AI', 'page' => 209, 'notes' => 'PDF VII-course header contains a semester label'],

        // 56-59: Electric Vehicle Technology
        ['program' => 'B.E. (Mechanical and Mechatronics Engineering)', 'specialization' => 'Electric Vehicle Technology', 'honors_to' => 'Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40114', 'course_name' => 'Introduction to Hybrid and Electric Vehicles', 'page' => 214, 'notes' => ''],
        ['program' => 'B.E. (Mechanical and Mechatronics Engineering)', 'specialization' => 'Electric Vehicle Technology', 'honors_to' => 'Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50114', 'course_name' => 'Electric vehicles and Renewable Energy', 'page' => 217, 'notes' => ''],
        ['program' => 'B.E. (Mechanical and Mechatronics Engineering)', 'specialization' => 'Electric Vehicle Technology', 'honors_to' => 'Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60114', 'course_name' => 'Fundamentals of Electric vehicles: Technology & Economics', 'page' => 219, 'notes' => ''],
        ['program' => 'B.E. (Mechanical and Mechatronics Engineering)', 'specialization' => 'Electric Vehicle Technology', 'honors_to' => 'Mechanical & Mechatronics / Mechanical Engineering / Electronics & Computer Science', 'minor_to' => 'Computer Engineering; Information Technology; Electronics & Telecommunication; B.Tech (Artificial Intelligence & Data Science); B.Tech (Artificial Intelligence & Machine Learning); B.E (Computer Science & Engineering) (Cyber Security); B.Tech (Internet of Things); Civil Engineering', 'academic_year' => '2024-25', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70114', 'course_name' => 'EV for Academia & Vehicle Dynamics', 'page' => 223, 'notes' => ''],

        // 60-63: Advanced Cyber Security and Quantum Cryptography
        ['program' => 'Computer Science and Engineering (Cybersecurity)', 'specialization' => 'Advanced Cyber Security and Quantum Cryptography', 'honors_to' => 'Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40115', 'course_name' => 'Cyber Security and Privacy', 'page' => 227, 'notes' => ''],
        ['program' => 'Computer Science and Engineering (Cybersecurity)', 'specialization' => 'Advanced Cyber Security and Quantum Cryptography', 'honors_to' => 'Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50115', 'course_name' => 'Introduction to Information Security I, IIT Madras', 'page' => 229, 'notes' => ''],
        ['program' => 'Computer Science and Engineering (Cybersecurity)', 'specialization' => 'Advanced Cyber Security and Quantum Cryptography', 'honors_to' => 'Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60115', 'course_name' => 'Privacy and Security in Online Social Media, IIIT Hyderabad', 'page' => 231, 'notes' => ''],
        ['program' => 'Computer Science and Engineering (Cybersecurity)', 'specialization' => 'Advanced Cyber Security and Quantum Cryptography', 'honors_to' => 'Computer Science & Engineering (Cyber Security) / Computer Engineering / Information Technology / Electronics & Telecommunication / Electronics & Computer Science / B.Tech (Artificial Intelligence & Machine Learning) / B.Tech (Artificial Intelligence & Data Science) / B.Tech (Internet of Things)', 'minor_to' => 'Mechanical Engineering; Civil Engineering; B.E (Mechanical & Mechatronics)', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70115', 'course_name' => 'Quantum Algorithms and Cryptography', 'page' => 233, 'notes' => ''],

        // 64-67: Advanced Web Development
        ['program' => 'Information Technology', 'specialization' => 'Advanced Web Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40116', 'course_name' => 'Foundation of web technology', 'page' => 237, 'notes' => ''],
        ['program' => 'Information Technology', 'specialization' => 'Advanced Web Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50116', 'course_name' => 'Advance Internet Technology', 'page' => 240, 'notes' => ''],
        ['program' => 'Information Technology', 'specialization' => 'Advanced Web Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60116', 'course_name' => 'Web X.0', 'page' => 243, 'notes' => ''],
        ['program' => 'Information Technology', 'specialization' => 'Advanced Web Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70116', 'course_name' => 'Full Stack Web development', 'page' => 246, 'notes' => ''],

        // 68-71: VLSI Design & Technology
        ['program' => 'Electronics & Computer Science Engineering', 'specialization' => 'VLSI Design & Technology', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40117', 'course_name' => 'Digital Electronic Circuits', 'page' => 250, 'notes' => ''],
        ['program' => 'Electronics & Computer Science Engineering', 'specialization' => 'VLSI Design & Technology', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50117', 'course_name' => 'System Design Through VERILOG and Fundamentals of electronic device fabrication', 'page' => 253, 'notes' => ''],
        ['program' => 'Electronics & Computer Science Engineering', 'specialization' => 'VLSI Design & Technology', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60117', 'course_name' => 'Digital IC Design', 'page' => 256, 'notes' => ''],
        ['program' => 'Electronics & Computer Science Engineering', 'specialization' => 'VLSI Design & Technology', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70117', 'course_name' => 'Electronic Systems Design: Hands-on Circuits and PCB Design with CAD Software', 'page' => 258, 'notes' => ''],

        // 72-75: Universal Human Values (UHV)
        ['program' => 'All Branch', 'specialization' => 'Universal Human Values (UHV)', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40118', 'course_name' => 'UHV-III Understanding Human Being, Nature and Existence Comprehensively', 'page' => 261, 'notes' => "The PDF's p.260 scheme heading says 'Advanced; UHV is identified from course syllabus pages."],
        ['program' => 'All Branch', 'specialization' => 'Universal Human Values (UHV)', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50118', 'course_name' => 'UHV IV: Vision for Humane Society', 'page' => 264, 'notes' => "The PDF's p.260 scheme heading says 'Advanced; UHV is identified from course syllabus pages."],
        ['program' => 'All Branch', 'specialization' => 'Universal Human Values (UHV)', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60118', 'course_name' => 'UHV V: Human Psychology', 'page' => 267, 'notes' => "The PDF's p.260 scheme heading says 'Advanced; UHV is identified from course syllabus pages."],
        ['program' => 'All Branch', 'specialization' => 'Universal Human Values (UHV)', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70118', 'course_name' => 'UHV VI: Holistic Human Health', 'page' => 270, 'notes' => "The PDF's p.260 scheme heading says 'Advanced; UHV is identified from course syllabus pages."],

        // 76-79: Indian Knowledge System
        ['program' => 'All Branch', 'specialization' => 'Indian Knowledge System', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40119', 'course_name' => 'Introduction to Indian Knowledge System', 'page' => 274, 'notes' => ''],
        ['program' => 'All Branch', 'specialization' => 'Indian Knowledge System', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50119', 'course_name' => 'Vedic Mathematics', 'page' => 277, 'notes' => ''],
        ['program' => 'All Branch', 'specialization' => 'Indian Knowledge System', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60119', 'course_name' => 'Indian Education System', 'page' => 280, 'notes' => ''],
        ['program' => 'All Branch', 'specialization' => 'Indian Knowledge System', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70119', 'course_name' => 'Indian Science and Technology', 'page' => 283, 'notes' => ''],

        // 80-83: Innovation, Entrepreneurial & Venture Development
        ['program' => 'All Branch', 'specialization' => 'Innovation, Entrepreneurial & Venture Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'S.E (IV)', 'semester' => 'IV', 'course_code' => 'MDL40120', 'course_name' => 'Innovation and Start up policy', 'page' => 287, 'notes' => ''],
        ['program' => 'All Branch', 'specialization' => 'Innovation, Entrepreneurial & Venture Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (V)', 'semester' => 'V', 'course_code' => 'MDL50120', 'course_name' => 'Innovation, Business Models and Entrepreneurship', 'page' => 290, 'notes' => ''],
        ['program' => 'All Branch', 'specialization' => 'Innovation, Entrepreneurial & Venture Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'T.E (VI)', 'semester' => 'VI', 'course_code' => 'MDL60120', 'course_name' => 'Introduction to Marketing Management', 'page' => 293, 'notes' => ''],
        ['program' => 'All Branch', 'specialization' => 'Innovation, Entrepreneurial & Venture Development', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => 'B.E (VII)', 'semester' => 'VII', 'course_code' => 'MDL70120', 'course_name' => 'Project Management', 'page' => 296, 'notes' => ''],

        // 84-87: Business Development, Marketing and Finance
        ['program' => 'MBA', 'specialization' => 'Business Development, Marketing and Finance', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => '1st Year (I)', 'semester' => 'I', 'course_code' => 'MDL01121', 'course_name' => 'Business Marketing - Technology Focus', 'page' => 300, 'notes' => ''],
        ['program' => 'MBA', 'specialization' => 'Business Development, Marketing and Finance', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => '1st Year (II)', 'semester' => 'II', 'course_code' => 'MDL02121', 'course_name' => 'Entrepreneurship Development', 'page' => 303, 'notes' => ''],
        ['program' => 'MBA', 'specialization' => 'Business Development, Marketing and Finance', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => '2nd Year (III)', 'semester' => 'III', 'course_code' => 'MDL03121', 'course_name' => 'Digital Marketing', 'page' => 306, 'notes' => ''],
        ['program' => 'MBA', 'specialization' => 'Business Development, Marketing and Finance', 'honors_to' => 'All UG Programs', 'minor_to' => '—', 'academic_year' => '2023-24', 'year_sem' => '2nd Year (IV)', 'semester' => 'IV', 'course_code' => 'MDL04121', 'course_name' => 'Corporate Finance', 'page' => 309, 'notes' => '']
    ];
}

/**
 * Execute the complete data import inside an atomic transaction
 */
function executeSpecializationCourseImport($db_handle, $userId = 1) {
    $conn = $db_handle->conn;
    if (!($conn instanceof mysqli)) {
        return ['success' => false, 'error' => 'Database connection unavailable'];
    }

    // 1. Ensure schema migration is in place
    $db_handle->ensureSpecializationCourseSchema();

    // 2. Start transaction
    mysqli_begin_transaction($conn);

    $stats = [
        'source_courses_extracted' => 87,
        'unique_specializations' => 21,
        'unique_programs' => 15,
        'departments_updated' => 0,
        'departments_inserted' => 0,
        'academic_years_synced' => 0,
        'specializations_inserted' => 0,
        'specializations_updated' => 0,
        'courses_inserted' => 0,
        'courses_updated' => 0,
        'courses_matched' => 0,
        'duplicate_records_avoided' => 0,
        'unresolved_conflicts' => 0,
        'failed_records' => 0
    ];

    try {
        // --- STEP 1: DEPARTMENTS & ACADEMIC PROGRAMS ---
        $deptProgramDefinitions = [
            1 => ['code' => 'MCA', 'program' => 'Master of Computer Applications'],
            2 => ['code' => 'AIML', 'program' => 'B.Tech. (Artificial Intelligence and Machine Learning)'],
            3 => ['code' => 'CE', 'program' => 'B.E. (Computer Engineering)'],
            4 => ['code' => 'IT', 'program' => 'B.E. (Information Technology)'],
            5 => ['code' => 'EXTC', 'program' => 'B.E. (Electronics & Communication Engineering)'],
            6 => ['code' => 'ECS', 'program' => 'B.E. (Electronics and Computer Science)'],
            7 => ['code' => 'MECH', 'program' => 'B.E. (Mechanical Engineering)'],
            8 => ['code' => 'CIVIL', 'program' => 'B.E. (Civil Engineering)'],
            9 => ['code' => 'CSE-CS', 'program' => 'Computer Science and Engineering (Cybersecurity)'],
            10 => ['code' => 'MME', 'program' => 'B.E. (Mechanical and Mechatronics Engineering)'],
            11 => ['code' => 'BCA', 'program' => 'Bachelor of Computer Applications'],
            12 => ['code' => 'AIDS', 'program' => 'B.Tech. (Artificial Intelligence and Data Science)'],
            13 => ['code' => 'IOT', 'program' => 'B.E. (Internet of Things)'],
            14 => ['code' => 'MBA', 'program' => 'MBA'],
            15 => ['code' => 'All Branch', 'program' => 'All Branch']
        ];

        foreach ($deptProgramDefinitions as $dId => $meta) {
            $code = $meta['code'];
            $prog = $meta['program'];

            $checkStmt = mysqli_prepare($conn, "SELECT department_id FROM st_department_master WHERE department_id = ?");
            mysqli_stmt_bind_param($checkStmt, 'i', $dId);
            mysqli_stmt_execute($checkStmt);
            $checkRes = mysqli_stmt_get_result($checkStmt);

            if ($checkRes && mysqli_num_rows($checkRes) > 0) {
                // Update program name
                $upStmt = mysqli_prepare($conn, "UPDATE st_department_master SET program_name = ? WHERE department_id = ?");
                mysqli_stmt_bind_param($upStmt, 'si', $prog, $dId);
                mysqli_stmt_execute($upStmt);
                mysqli_stmt_close($upStmt);
                $stats['departments_updated']++;
            } else {
                // Insert new department record (e.g. MBA, All Branch)
                $insStmt = mysqli_prepare($conn, "INSERT INTO st_department_master (department_id, department_name, program_name) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($insStmt, 'iss', $dId, $code, $prog);
                mysqli_stmt_execute($insStmt);
                mysqli_stmt_close($insStmt);
                $stats['departments_inserted']++;
            }
            mysqli_stmt_close($checkStmt);
        }

        // Program Name to Department ID lookup map
        $programToDeptId = [
            'B.E. (Computer Engineering)' => 3,
            'B.E. (Information Technology)' => 4,
            'Information Technology' => 4,
            'B.E. (Electronics & Communication Engineering)' => 5,
            'B.E. (Electronics and Computer Science)' => 6,
            'Electronics & Computer Science Engineering' => 6,
            'B.E. (Civil Engineering)' => 8,
            'B.E. (Mechanical Engineering)' => 7,
            'B.Tech. (Artificial Intelligence and Data Science)' => 12,
            'B.Tech. (Artificial Intelligence and Machine Learning)' => 2,
            'B.E. (Internet of Things)' => 13,
            'B.E. (Mechanical and Mechatronics Engineering)' => 10,
            'Computer Science and Engineering (Cybersecurity)' => 9,
            'MBA' => 14,
            'All Branch' => 15
        ];

        // --- STEP 2: ACADEMIC YEARS ---
        $academicYears = ['2023-24', '2024-25'];
        $ayIdMap = [];
        // First get existing sessions
        $ayRes = mysqli_query($conn, "SELECT session_id, session_name FROM st_session_master");
        if ($ayRes) {
            while ($row = mysqli_fetch_assoc($ayRes)) {
                $trimmed = trim($row['session_name']);
                $ayIdMap[$trimmed] = (int)$row['session_id'];
                // Normalize 2025 -2026 to 2025-26
                $clean = str_replace(' ', '', $trimmed);
                $ayIdMap[$clean] = (int)$row['session_id'];
            }
        }

        foreach ($academicYears as $ayName) {
            if (!isset($ayIdMap[$ayName])) {
                $insAy = mysqli_prepare($conn, "INSERT INTO st_session_master (session_name) VALUES (?)");
                mysqli_stmt_bind_param($insAy, 's', $ayName);
                mysqli_stmt_execute($insAy);
                $newAyId = mysqli_insert_id($conn);
                mysqli_stmt_close($insAy);
                $ayIdMap[$ayName] = $newAyId;
                $stats['academic_years_synced']++;
            }
        }

        // --- STEP 3: SEMESTERS ---
        $romanToSemId = [
            'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4,
            'V' => 5, 'VI' => 6, 'VII' => 7, 'VIII' => 8
        ];

        // --- STEP 4: SPECIALIZATIONS (21 tracks) ---
        $sourceSpecs = getSourceSpecializations();
        $specNameToId = [];

        foreach ($sourceSpecs as $sMeta) {
            $specId = (int)$sMeta['id'];
            $specName = $sMeta['name'];
            $offeringProg = $sMeta['program'];
            $deptId = $programToDeptId[$offeringProg] ?? null;
            $honors = $sMeta['honors'];
            $minor = $sMeta['minor'];
            $semFrom = (int)$sMeta['sem_from'];
            $semTo = (int)$sMeta['sem_to'];

            $specNameToId[$specName] = $specId;

            // Check if specialization exists by specialization_id or name
            $spCheck = mysqli_prepare($conn, "SELECT specialization_id FROM st_specialization_master WHERE specialization_id = ? OR LOWER(specialization_name) = LOWER(?)");
            mysqli_stmt_bind_param($spCheck, 'is', $specId, $specName);
            mysqli_stmt_execute($spCheck);
            $spRes = mysqli_stmt_get_result($spCheck);

            if ($spRes && mysqli_num_rows($spRes) > 0) {
                $existingSp = mysqli_fetch_assoc($spRes);
                $actualSpecId = (int)$existingSp['specialization_id'];
                $specNameToId[$specName] = $actualSpecId;

                $upSp = mysqli_prepare($conn, "UPDATE st_specialization_master 
                    SET specialization_name = ?, offering_program = ?, department_id = ?, honors_eligibility = ?, minor_eligibility = ?, sem_from = ?, sem_to = ?
                    WHERE specialization_id = ?");
                mysqli_stmt_bind_param($upSp, 'ssissiii', $specName, $offeringProg, $deptId, $honors, $minor, $semFrom, $semTo, $actualSpecId);
                mysqli_stmt_execute($upSp);
                mysqli_stmt_close($upSp);
                $stats['specializations_updated']++;
            } else {
                $insSp = mysqli_prepare($conn, "INSERT INTO st_specialization_master 
                    (specialization_id, specialization_name, min_cgpa, kt_allowed, sem_from, sem_to, is_exclusive, offering_program, department_id, honors_eligibility, minor_eligibility) 
                    VALUES (?, ?, 0.00, 0, ?, ?, 0, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($insSp, 'isiisiss', $specId, $specName, $semFrom, $semTo, $offeringProg, $deptId, $honors, $minor);
                mysqli_stmt_execute($insSp);
                mysqli_stmt_close($insSp);
                $stats['specializations_inserted']++;
            }
            mysqli_stmt_close($spCheck);
        }

        // --- STEP 5: 87 COURSES IMPORT ---
        $sourceCourses = getSourceCourses();

        foreach ($sourceCourses as $c) {
            $code = trim($c['course_code']);
            $name = trim($c['course_name']);
            $prog = trim($c['program']);
            $specName = trim($c['specialization']);
            $deptId = $programToDeptId[$prog] ?? null;
            $specId = $specNameToId[$specName] ?? null;
            $semId = $romanToSemId[trim($c['semester'])] ?? null;
            $ayName = trim($c['academic_year']);
            $ayId = $ayIdMap[$ayName] ?? null;
            $yearSem = trim($c['year_sem']);
            $page = (string)$c['page'];
            $notes = trim($c['notes']);
            $honorsElig = trim($c['honors_to']);
            $minorElig = trim($c['minor_to']);
            $subjType = ($honorsElig !== '—' && $minorElig !== '—') ? 'Honors / Minor' : (($honorsElig !== '—') ? 'Honors' : 'Minor');

            if (!$deptId || !$specId || !$semId) {
                throw new Exception("Missing foreign key mapping for course $code ($name)");
            }

            // Match by course_code (idempotent matching)
            $cCheck = mysqli_prepare($conn, "SELECT subject_id FROM st_specialization_subject_master WHERE subject_code = ? LIMIT 1");
            mysqli_stmt_bind_param($cCheck, 's', $code);
            mysqli_stmt_execute($cCheck);
            $cRes = mysqli_stmt_get_result($cCheck);

            if ($cRes && mysqli_num_rows($cRes) > 0) {
                // Update existing record
                $existingCourse = mysqli_fetch_assoc($cRes);
                $subjectId = (int)$existingCourse['subject_id'];

                $upC = mysqli_prepare($conn, "UPDATE st_specialization_subject_master 
                    SET subject_name = ?, specialization_id = ?, department_id = ?, semester_id = ?, academic_year_id = ?,
                        subject_type = ?, is_active = 1, program_name = ?, year_of_study = ?, source_page = ?,
                        source_notes = ?, honors_eligibility = ?, minor_eligibility = ?
                    WHERE subject_id = ?");
                mysqli_stmt_bind_param($upC, 'siiiisssssssi', 
                    $name, $specId, $deptId, $semId, $ayId,
                    $subjType, $prog, $yearSem, $page,
                    $notes, $honorsElig, $minorElig, $subjectId
                );
                mysqli_stmt_execute($upC);
                mysqli_stmt_close($upC);

                $stats['courses_matched']++;
                $stats['courses_updated']++;
                $stats['duplicate_records_avoided']++;
            } else {
                // Insert new course record
                $insC = mysqli_prepare($conn, "INSERT INTO st_specialization_subject_master 
                    (subject_code, subject_name, specialization_id, department_id, semester_id, academic_year_id,
                     subject_type, is_active, program_name, year_of_study, source_page,
                     source_notes, honors_eligibility, minor_eligibility) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($insC, 'ssiiiisssssss', 
                    $code, $name, $specId, $deptId, $semId, $ayId,
                    $subjType, $prog, $yearSem, $page,
                    $notes, $honorsElig, $minorElig
                );
                mysqli_stmt_execute($insC);
                mysqli_stmt_close($insC);

                $stats['courses_inserted']++;
            }
            mysqli_stmt_close($cCheck);
        }

        // Commit transaction
        mysqli_commit($conn);

        // Audit log
        $auditDesc = "TCET ERP Specialization and Course Data Import completed successfully. " .
                     "Inserted: {$stats['courses_inserted']}, Matched/Updated: {$stats['courses_updated']}, " .
                     "Specializations: {$stats['unique_specializations']}, Programs: {$stats['unique_programs']}.";
        $db_handle->writeAuditLog($userId, 'DATA_IMPORT_SPECIALIZATION_COURSES', 'st_specialization_subject_master', null, $auditDesc);

        return ['success' => true, 'stats' => $stats];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'error' => $e->getMessage(), 'stats' => $stats];
    }
}

// -------------------------------------------------------------
// EXECUTION: CLI MODE
// -------------------------------------------------------------
if ($isCli) {
    echo "=======================================================\n";
    echo "TCET ERP SPECIALIZATION & COURSE DATA IMPORTER (CLI)\n";
    echo "=======================================================\n\n";

    $result = executeSpecializationCourseImport($db_handle, 1);

    if ($result['success']) {
        echo ">>> IMPORT COMPLETED SUCCESSFULLY!\n\n";
        echo "Statistics & Audit Summary:\n";
        echo "-------------------------------------------------------\n";
        foreach ($result['stats'] as $k => $v) {
            echo sprintf("  %-32s: %s\n", ucwords(str_replace('_', ' ', $k)), $v);
        }
        echo "-------------------------------------------------------\n";
        exit(0);
    } else {
        echo ">>> IMPORT FAILED: " . $result['error'] . "\n";
        exit(1);
    }
}

// -------------------------------------------------------------
// EXECUTION: WEB ADMIN UI
// -------------------------------------------------------------
$message = '';
$messageType = '';
$importResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_import') {
    if (!DBController::validateCsrfToken()) {
        $message = "Security token validation failed. Please try again.";
        $messageType = "danger";
    } else {
        $userId = intval($_SESSION['user_id'] ?? 1);
        $result = executeSpecializationCourseImport($db_handle, $userId);
        if ($result['success']) {
            $message = "Specialization and Course Data successfully imported into MySQL database!";
            $messageType = "success";
            $importResults = $result['stats'];
        } else {
            $message = "Import encountered an error: " . htmlspecialchars($result['error']);
            $messageType = "danger";
        }
    }
}

// Current database state metrics
$totalCourseCount = $db_handle->numRows("SELECT subject_id FROM st_specialization_subject_master WHERE subject_code IS NOT NULL AND subject_code != ''");
$totalSpecCount = $db_handle->numRows("SELECT specialization_id FROM st_specialization_master WHERE specialization_id >= 5");
$totalDeptCount = $db_handle->numRows("SELECT department_id FROM st_department_master");

include "header/header.php";
?>

<div class="content-wrapper">
  <div class="erp-page-header">
    <div>
      <h1 class="erp-page-title"><i class="fa fa-database text-primary"></i> Specialization & Course Data Import</h1>
      <p class="erp-page-subtitle">Batch synchronization of 87 curriculum courses, 21 specializations, and honors/minor mappings</p>
    </div>
    <div class="erp-page-actions">
      <a href="specialization_subject_manage.php" class="btn btn-erp-primary"><i class="fa fa-book"></i> Subject Management</a>
      <a href="class_crud_new.php?tab=specialization-list" class="btn btn-erp-secondary"><i class="fa fa-cogs"></i> Specialization Master</a>
    </div>
  </div>

  <section class="content" style="padding-top: 0;">
    <?php if ($message !== ''): ?>
      <div class="alert alert-<?php echo $messageType; ?> alert-dismissable" style="border-radius: 4px; margin-bottom: 20px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h4><i class="fa <?php echo ($messageType === 'success') ? 'fa-check' : 'fa-exclamation-triangle'; ?>"></i> Notification</h4>
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <!-- Summary Metrics Card -->
    <div class="row" style="margin-bottom: 20px;">
      <div class="col-md-3">
        <div class="info-box">
          <span class="info-box-icon bg-aqua"><i class="fa fa-book"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Imported Courses</span>
            <span class="info-box-number"><?php echo $totalCourseCount; ?> / 87</span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="info-box">
          <span class="info-box-icon bg-green"><i class="fa fa-graduation-cap"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Specializations</span>
            <span class="info-box-number"><?php echo $totalSpecCount; ?> / 21</span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="info-box">
          <span class="info-box-icon bg-yellow"><i class="fa fa-university"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Academic Programs</span>
            <span class="info-box-number">15</span>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="info-box">
          <span class="info-box-icon bg-purple"><i class="fa fa-shield"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Integrity Mode</span>
            <span class="info-box-number">Idempotent</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Execution Card -->
    <div class="erp-card" style="margin-bottom: 25px;">
      <div class="erp-card-header">
        <h3 class="erp-card-title"><i class="fa fa-play text-primary"></i> Execute Curriculum Data Synchronization</h3>
        <p class="erp-card-subtitle">Synchronize verified course datasets from Specialization_Course_Data directly into MySQL</p>
      </div>
      <div class="erp-card-body" style="padding: 20px;">
        <p>This utility imports and updates the official 87 specialization courses across all 21 tracks. The operation runs inside a protected MySQL transaction, ensuring that student records, marks, and progression history remain completely untouched.</p>
        
        <form method="post" onsubmit="return confirm('Execute idempotent course catalog import?');">
          <?php echo DBController::getCsrfInputField(); ?>
          <input type="hidden" name="action" value="run_import">
          <button type="submit" class="btn btn-erp-primary btn-lg">
            <i class="fa fa-refresh"></i> Run Course & Specialization Import
          </button>
        </form>
      </div>
    </div>

    <?php if ($importResults): ?>
      <div class="erp-card" style="margin-bottom: 25px;">
        <div class="erp-card-header">
          <h3 class="erp-card-title text-success"><i class="fa fa-check-circle"></i> Import Execution Report</h3>
        </div>
        <div class="erp-card-body" style="padding: 20px;">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Metric / Operation</th>
                <th>Count / Result</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($importResults as $k => $v): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $k))); ?></strong></td>
                  <td><?php echo htmlspecialchars((string)$v); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php include "header/footer.php"; ?>
