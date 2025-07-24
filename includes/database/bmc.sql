-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2025 at 03:06 PM
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
-- Database: `bmc`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `standard` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `teacher_id`, `school_id`, `standard`, `subject`, `title`, `description`, `file_path`, `original_filename`, `due_date`, `created_at`) VALUES
(3, 6, 4, '11', 'maths', 'maths', 'chbjdcj', '/BMC-SMS/pages/assignments/uploads/assign_688223fef08ce9.86748149_INTERNSHIP REGISTRATION FORM JAY (4).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4).pdf', '2025-08-17', '2025-07-24 12:15:58');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_submissions`
--

INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `student_id`, `file_path`, `original_filename`, `status`, `submitted_at`) VALUES
(1, 3, 3, '/BMC-SMS/pages/assignments/submit/sub_688226f95aae52.40718307_PROJECT college.pdf', 'PROJECT college.pdf', 'Submitted', '2025-07-24 12:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `std` varchar(10) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL,
  `remark` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `teacher_id`, `school_id`, `std`, `attendance_date`, `status`, `remark`) VALUES
(1, 3, 6, 4, '11', '2025-07-24', 'Present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deleted_principals`
--

CREATE TABLE `deleted_principals` (
  `id` int(11) NOT NULL,
  `principal_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `batch` enum('Morning','Evening') DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `deleted_by_role` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_principals`
--

INSERT INTO `deleted_principals` (`id`, `principal_name`, `email`, `phone`, `dob`, `gender`, `blood_group`, `address`, `qualification`, `salary`, `batch`, `school_id`, `deleted_by_role`, `deleted_at`) VALUES
(1, 'HARSH', 'harsh@gmail.com', '5674231689', '2005-02-06', 'male', 'B-', 'Adajan', 'B.C.A', 500000.00, '', 3, 'schooladmin', '2025-07-22 11:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_schools`
--

CREATE TABLE `deleted_schools` (
  `id` int(11) NOT NULL,
  `school_logo` varchar(255) DEFAULT NULL,
  `school_name` varchar(100) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `school_opening` date DEFAULT NULL,
  `school_type` enum('Government','Private') DEFAULT NULL,
  `education_board` set('CBSE','State','IGCSE') DEFAULT NULL,
  `school_medium` set('English','Hindi','Regional Language') DEFAULT NULL,
  `school_category` set('Pre-Primary','Primary','Upper Primary','Secondary','Higher Secondary') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `deleted_by_role` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_schools`
--

INSERT INTO `deleted_schools` (`id`, `school_logo`, `school_name`, `email`, `phone`, `school_opening`, `school_type`, `education_board`, `school_medium`, `school_category`, `address`, `deleted_by_role`, `deleted_at`) VALUES
(6, NULL, 'LP SAVANI CANAL ROAD', 'lpsavani@gmail.com', '5478931254', '1999-03-11', 'Private', 'State', 'Hindi', '', 'Adajan', 'bmc', '2025-07-24 10:06:38');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_students`
--

CREATE TABLE `deleted_students` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rollno` varchar(20) DEFAULT NULL,
  `std` varchar(10) DEFAULT NULL,
  `academic_year` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_phone` varchar(15) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_phone` varchar(15) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `deleted_by_role` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_students`
--

INSERT INTO `deleted_students` (`id`, `student_name`, `email`, `rollno`, `std`, `academic_year`, `dob`, `gender`, `blood_group`, `address`, `father_name`, `father_phone`, `mother_name`, `mother_phone`, `school_id`, `deleted_by_role`, `deleted_at`) VALUES
(1, 'Rahul Patel', 'rahul@gmail.com', '1', '5th', '2024-2025', '2005-02-02', 'male', 'AB+', 'surat', 'harsh', '6565548720', 'hemina', '6523012304', 3, 'schooladmin', '2025-07-22 11:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_teachers`
--

CREATE TABLE `deleted_teachers` (
  `id` int(11) NOT NULL,
  `teacher_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `language_known` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `std` set('Nursery','Junior','Senior','1','2','3','4','5','6','7','8','9','10','11','12') DEFAULT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `batch` enum('Morning','Evening') DEFAULT NULL,
  `class_teacher` tinyint(1) DEFAULT NULL,
  `class_teacher_std` varchar(10) DEFAULT NULL,
  `deleted_by_role` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_teachers`
--

INSERT INTO `deleted_teachers` (`id`, `teacher_name`, `email`, `phone`, `gender`, `dob`, `blood_group`, `address`, `school_id`, `qualification`, `subject`, `language_known`, `salary`, `std`, `experience`, `batch`, `class_teacher`, `class_teacher_std`, `deleted_by_role`, `deleted_at`) VALUES
(1, 'JAY', 'jay@gmail.com', '5674298791', 'male', '2005-11-03', 'AB-', '0', 3, 'BA', 'Account', 'Hindi', 500000.00, 'Nursery,Junior,1', '5', 'Evening', 0, NULL, 'schooladmin', '2025-07-22 11:51:18'),
(12, 'ram', 'ram@gmail.com', '5545875655', 'male', '2005-03-11', 'AB+', 'surat', 4, 'MA', 'English', 'English', 100000.00, '5,6', '5', 'Morning', 0, NULL, 'schooladmin', '2025-07-24 09:34:16');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `applied_on` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `teacher_id`, `from_date`, `to_date`, `reason`, `status`, `applied_on`) VALUES
(1, 6, '2025-07-30', '2025-08-10', 'My friend\'s marriage', 'Approved', '2025-07-23 17:40:03'),
(2, 6, '2025-07-31', '2025-08-20', 'swayam marriage', 'Approved', '2025-07-23 17:55:10');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `target_standard` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `school_id`, `target_standard`, `title`, `content`, `file_path`, `original_filename`, `created_at`) VALUES
(3, 6, 4, '11', 'Fee', 'BLAW BLAW', '/BMC-SMS/pages/teacher/uploads/note_6882136a28ca99.45092353_INTERNSHIP REGISTRATION FORM JAY.pdf', 'INTERNSHIP REGISTRATION FORM JAY.pdf', '2025-07-24 11:05:14');

-- --------------------------------------------------------

--
-- Table structure for table `principal`
--

CREATE TABLE `principal` (
  `id` int(11) NOT NULL,
  `principal_image` varchar(255) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `principal_name` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `principal_dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Others') NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `address` text DEFAULT NULL,
  `qualification` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `batch` enum('Morning','Evening') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `principal`
--

INSERT INTO `principal` (`id`, `principal_image`, `school_id`, `principal_name`, `email`, `password`, `phone`, `principal_dob`, `gender`, `blood_group`, `address`, `qualification`, `salary`, `batch`) VALUES
(10, NULL, 4, 'Fenil Pastagia', 'fenil@gmail.com', '$2y$10$EaSZM1Mq/otD2L1wHMoZdefcPjkOWeXPjePcvdj5WLY/6Lx5DxrJ6', '9924976503', '1980-08-17', 'Male', 'B+', 'Adajan', 'M.A. M.Ed', 90000.00, 'Morning');

-- --------------------------------------------------------

--
-- Table structure for table `principal_timings`
--

CREATE TABLE `principal_timings` (
  `timing_id` int(11) NOT NULL,
  `principal_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `opens_at` time DEFAULT NULL,
  `closes_at` time DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `principal_timings`
--

INSERT INTO `principal_timings` (`timing_id`, `principal_id`, `day_of_week`, `opens_at`, `closes_at`, `is_closed`) VALUES
(1, 10, 'Monday', '06:00:00', '20:00:00', 0),
(2, 10, 'Tuesday', '10:00:00', '20:00:00', 0),
(3, 10, 'Wednesday', '10:00:00', '20:00:00', 0),
(4, 10, 'Thursday', '10:00:00', '20:00:00', 0),
(5, 10, 'Friday', '10:00:00', '20:00:00', 0),
(6, 10, 'Saturday', '10:00:00', '20:00:00', 0),
(7, 10, 'Sunday', '10:00:00', '20:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `id` int(11) NOT NULL,
  `school_logo` varchar(255) DEFAULT NULL,
  `school_name` varchar(100) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `school_opening` date DEFAULT NULL,
  `school_type` enum('Government','Private') DEFAULT NULL,
  `education_board` set('CBSE','State','IGCSE') DEFAULT NULL,
  `school_medium` set('English','Hindi','Regional Language') DEFAULT NULL,
  `school_category` set('Pre-Primary','Primary','Upper Primary','Secondary','Higher Secondary') DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school`
--

INSERT INTO `school` (`id`, `school_logo`, `school_name`, `email`, `phone`, `school_opening`, `school_type`, `education_board`, `school_medium`, `school_category`, `address`) VALUES
(4, NULL, 'sanskar bharti vidyalay', 'sbv@gmail.com', '8526548525', '2025-07-06', 'Private', 'CBSE', 'Hindi', '', 'adajan');

-- --------------------------------------------------------

--
-- Table structure for table `standard_subjects`
--

CREATE TABLE `standard_subjects` (
  `std_subject_id` int(11) NOT NULL,
  `standard` varchar(10) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `standard_subjects`
--

INSERT INTO `standard_subjects` (`std_subject_id`, `standard`, `subject_id`) VALUES
(1, '1', 1),
(4, '1', 2),
(6, '1', 3),
(3, '1', 11),
(2, '1', 12),
(5, '1', 16),
(73, '10', 1),
(74, '10', 2),
(77, '10', 3),
(75, '10', 7),
(71, '10', 8),
(72, '10', 10),
(76, '10', 13),
(79, '11', 1),
(80, '11', 2),
(83, '11', 3),
(84, '11', 4),
(78, '11', 10),
(82, '11', 13),
(81, '11', 16),
(93, '12', 1),
(95, '12', 2),
(98, '12', 4),
(92, '12', 10),
(94, '12', 12),
(97, '12', 13),
(96, '12', 16),
(8, '2', 1),
(11, '2', 2),
(10, '2', 11),
(9, '2', 12),
(12, '2', 16),
(7, '2', 17),
(14, '3', 1),
(17, '3', 2),
(19, '3', 3),
(16, '3', 11),
(15, '3', 12),
(18, '3', 16),
(13, '3', 17),
(21, '4', 1),
(24, '4', 2),
(26, '4', 3),
(23, '4', 11),
(22, '4', 12),
(25, '4', 16),
(20, '4', 17),
(28, '5', 1),
(31, '5', 2),
(33, '5', 3),
(34, '5', 4),
(30, '5', 11),
(29, '5', 12),
(32, '5', 16),
(27, '5', 17),
(40, '6', 3),
(41, '6', 4),
(37, '6', 5),
(36, '6', 11),
(35, '6', 12),
(38, '6', 15),
(39, '6', 16),
(43, '7', 1),
(46, '7', 2),
(48, '7', 4),
(45, '7', 5),
(42, '7', 10),
(44, '7', 12),
(47, '7', 13),
(49, '8', 1),
(51, '8', 2),
(55, '8', 4),
(50, '8', 12),
(54, '8', 13),
(52, '8', 15),
(53, '8', 16),
(58, '9', 1),
(63, '9', 4),
(59, '9', 6),
(61, '9', 7),
(56, '9', 8),
(57, '9', 10),
(62, '9', 13),
(60, '9', 16);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` int(11) NOT NULL,
  `student_image` varchar(255) DEFAULT NULL,
  `student_name` varchar(50) DEFAULT NULL,
  `rollno` varchar(10) DEFAULT NULL,
  `std` varchar(4) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `academic_year` varchar(9) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','others') DEFAULT NULL,
  `blood_group` enum('a+','a-','b+','b-','ab+','ab-','o+','o-') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `father_name` varchar(50) DEFAULT NULL,
  `father_phone` varchar(10) DEFAULT NULL,
  `mother_name` varchar(50) DEFAULT NULL,
  `mother_phone` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `student_image`, `student_name`, `rollno`, `std`, `email`, `password`, `academic_year`, `school_id`, `dob`, `gender`, `blood_group`, `address`, `father_name`, `father_phone`, `mother_name`, `mother_phone`) VALUES
(3, NULL, 'devam parekh', '9', '11', 'devam@gmail.com', '$2y$10$vl/hHLMF3ar5GEc6pQJfVexTt3vKCXoAGF/9HcDtgGGDsfKHoXHQu', '2024-2025', 4, '2025-07-11', 'male', 'b+', 'canal road', 'mukesh', '9874522589', 'sunita', '753685124');

-- --------------------------------------------------------

--
-- Table structure for table `student_marks`
--

CREATE TABLE `student_marks` (
  `mark_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `std` varchar(10) NOT NULL,
  `division` varchar(5) NOT NULL,
  `exam_type` varchar(100) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `entry_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `entered_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`) VALUES
(14, 'Art'),
(9, 'Biology'),
(8, 'Chemistry'),
(10, 'Computer Science'),
(17, 'Drawing'),
(1, 'English'),
(6, 'Geography'),
(12, 'Gujarati'),
(11, 'Hindi'),
(5, 'History'),
(2, 'Mathematics'),
(15, 'Music'),
(16, 'Physical Education'),
(7, 'Physics'),
(13, 'Sanskrit'),
(3, 'Science'),
(4, 'Social Studies');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `id` int(11) NOT NULL,
  `teacher_image` varchar(255) DEFAULT NULL,
  `teacher_name` varchar(50) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `school_id` int(11) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Others') NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `language_known` varchar(100) DEFAULT NULL,
  `salary` int(11) DEFAULT NULL,
  `std` set('Nursery','Junior','Senior','1','2','3','4','5','6','7','8','9','10','11','12') DEFAULT NULL,
  `experience` varchar(10) DEFAULT NULL,
  `batch` enum('Morning','Evening') DEFAULT NULL,
  `class_teacher` tinyint(1) DEFAULT 0,
  `class_teacher_std` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`id`, `teacher_image`, `teacher_name`, `phone`, `school_id`, `dob`, `gender`, `blood_group`, `address`, `email`, `password`, `qualification`, `subject`, `language_known`, `salary`, `std`, `experience`, `batch`, `class_teacher`, `class_teacher_std`) VALUES
(6, '../../pages/teacher/uploads/teacher_6880cd02b30464.45441036.jpg', 'meet parekh', '9900990099', 4, '2025-07-01', 'Male', 'B-', 'mota varachaa', 'meet@gmail.com', '$2y$10$sdz4DZ5oaMJNrUA9mld44uiBNIIkAQCPjs2XrrnUcl.Bp6wlzYz1a', 'B.A', 'maths', 'english', 100000, '8,9,10,11,12', '10', 'Evening', 1, '11');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `standard` varchar(50) NOT NULL,
  `class_teacher_id` int(11) NOT NULL,
  `timetable_file` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`id`, `school_id`, `standard`, `class_teacher_id`, `timetable_file`, `original_filename`, `created_at`) VALUES
(1, 4, '11', 6, '/BMC-SMS/pages/teacher/uploads/timetables/tt_6882190a814100.28997107_INTERNSHIP REGISTRATION FORM JAY.pdf', 'INTERNSHIP REGISTRATION FORM JAY.pdf', '2025-07-24 11:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('student','teacher','schooladmin','bmc') NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `email`, `password`) VALUES
(3, 'student', 'devam@gmail.com', '$2y$10$vl/hHLMF3ar5GEc6pQJfVexTt3vKCXoAGF/9HcDtgGGDsfKHoXHQu'),
(6, 'teacher', 'meet@gmail.com', '$2y$10$sdz4DZ5oaMJNrUA9mld44uiBNIIkAQCPjs2XrrnUcl.Bp6wlzYz1a'),
(8, 'bmc', 'swayam@gmail.com', '$2y$10$T74F9Gb05l.StKcZg2sy/ub6PHeH.l3tT3Lv1JwOZzioXJCdEN0zO'),
(10, 'schooladmin', 'fenil@gmail.com', '$2y$10$EaSZM1Mq/otD2L1wHMoZdefcPjkOWeXPjePcvdj5WLY/6Lx5DxrJ6');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `deleted_principals`
--
ALTER TABLE `deleted_principals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_schools`
--
ALTER TABLE `deleted_schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_students`
--
ALTER TABLE `deleted_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_teachers`
--
ALTER TABLE `deleted_teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_leave_teacher_id` (`teacher_id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `principal`
--
ALTER TABLE `principal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `principal_timings`
--
ALTER TABLE `principal_timings`
  ADD PRIMARY KEY (`timing_id`),
  ADD UNIQUE KEY `uq_principal_day` (`principal_id`,`day_of_week`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `standard_subjects`
--
ALTER TABLE `standard_subjects`
  ADD PRIMARY KEY (`std_subject_id`),
  ADD UNIQUE KEY `uq_std_subject` (`standard`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD PRIMARY KEY (`mark_id`),
  ADD UNIQUE KEY `uq_student_exam_subject` (`student_id`,`academic_year`,`exam_type`,`subject_name`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `entered_by_user_id` (`entered_by_user_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_name` (`subject_name`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `class_teacher_id` (`class_teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `deleted_principals`
--
ALTER TABLE `deleted_principals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deleted_students`
--
ALTER TABLE `deleted_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deleted_teachers`
--
ALTER TABLE `deleted_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `principal_timings`
--
ALTER TABLE `principal_timings`
  MODIFY `timing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `standard_subjects`
--
ALTER TABLE `standard_subjects`
  MODIFY `std_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `fk_leave_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `principal`
--
ALTER TABLE `principal`
  ADD CONSTRAINT `fk_principal_user_id` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `principal_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`);

--
-- Constraints for table `principal_timings`
--
ALTER TABLE `principal_timings`
  ADD CONSTRAINT `fk_timing_principal_id` FOREIGN KEY (`principal_id`) REFERENCES `principal` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `standard_subjects`
--
ALTER TABLE `standard_subjects`
  ADD CONSTRAINT `standard_subjects_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `fk_student_user_id` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD CONSTRAINT `student_marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_marks_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_marks_ibfk_3` FOREIGN KEY (`entered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `fk_teacher_user_id` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
