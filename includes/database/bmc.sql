-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2025 at 11:06 AM
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
(3, 6, 4, '11', 'maths', 'maths', 'chbjdcj', '/BMC-SMS/pages/assignments/uploads/assign_688223fef08ce9.86748149_INTERNSHIP REGISTRATION FORM JAY (4).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4).pdf', '2025-08-17', '2025-07-24 12:15:58'),
(4, 6, 4, '8', 'maths', 'vfvf', 'dfvfdv', '/BMC-SMS/pages/assignments/uploads/assign_688364726c8816.85613585_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf', '2025-02-02', '2025-07-25 11:03:14'),
(5, 6, 4, '11', 'maths', 'cjbdcn', 'cm d cm', '/BMC-SMS/pages/assignments/uploads/assign_6883672a4665c9.17531706_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf', '2025-08-17', '2025-07-25 11:14:50'),
(6, 6, 4, '11', 'maths', 'test', 'testing', '/BMC-SMS/pages/assignments/uploads/assign_6888bc6286f051.53392889_💻 Case Study.pdf', '💻 Case Study.pdf', '2025-08-01', '2025-07-29 12:19:46'),
(7, 6, 4, '11', 'maths', 'hyy', 'fgbdb', NULL, NULL, '2025-08-17', '2025-07-29 12:43:57'),
(8, 6, 4, '11', 'maths', 'fsdgfasf', 'dasfaffffffffff', NULL, NULL, '2025-08-05', '2025-07-31 09:28:40'),
(9, 6, 4, '11', 'maths', 'sggtwgt', 'fwswefwfwew', NULL, NULL, '2025-08-09', '2025-07-31 09:29:44'),
(10, 6, 4, '11', 'maths', 'fenil', 'fenillllll', NULL, NULL, '2025-08-01', '2025-07-31 09:37:21'),
(11, 6, 4, '11', 'maths', 'sff', 'dadadwefwfe', NULL, NULL, '2025-08-02', '2025-07-31 09:48:34'),
(12, 6, 4, '11', 'maths', 'ddasdsfd', 'asfasf', NULL, NULL, '2025-02-03', '2025-07-31 10:05:12'),
(13, 6, 4, '11', 'maths', 'bjb', 'csv', NULL, NULL, '2025-12-12', '2025-07-31 10:09:34'),
(14, 6, 4, '11', 'maths', 'ssff', 'asdsaf', NULL, NULL, '2026-01-30', '2025-07-31 10:13:51'),
(15, 6, 4, '11', 'maths', 'qwfdadfdd', 'dasfs', NULL, NULL, '2025-08-01', '2025-07-31 10:15:45'),
(16, 6, 4, '11', 'maths', 'asfffascsa', 'csaf', NULL, NULL, '2025-08-01', '2025-07-31 12:11:36');

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
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `evaluated_at` timestamp NULL DEFAULT NULL,
  `rejection_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_submissions`
--

INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `student_id`, `file_path`, `original_filename`, `status`, `rejection_reason`, `submitted_at`, `evaluated_at`, `rejection_count`) VALUES
(1, 3, 3, '/BMC-SMS/pages/assignments/submit/sub_688226f95aae52.40718307_PROJECT college.pdf', 'PROJECT college.pdf', 'Submitted', NULL, '2025-07-24 12:28:41', NULL, 0),
(2, 5, 3, '/BMC-SMS/pages/assignments/submit/sub_68836749e5cb40.87594266_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4) (1) (1).pdf', 'Accepted', NULL, '2025-07-25 11:15:21', '2025-07-31 10:14:36', 0),
(3, 6, 3, '/BMC-SMS/pages/assignments/submit/sub_6888bcfe175da4.78458659_💻 Case Study.pdf', '💻 Case Study.pdf', 'Accepted', NULL, '2025-07-29 12:22:22', '2025-07-29 12:23:24', 0),
(4, 7, 3, '/BMC-SMS/pages/assignments/submit/sub_6888c2612d4318.65333657_💻 Case Study.pdf', '💻 Case Study.pdf', 'Accepted', NULL, '2025-07-29 12:45:21', '2025-07-29 12:45:30', 0),
(5, 8, 3, '/BMC-SMS/pages/assignments/submit/sub_688b375a579f91.40456980_view_attendence.txt', 'view_attendence.txt', 'Accepted', NULL, '2025-07-31 09:28:58', '2025-07-31 09:29:16', 0),
(6, 10, 3, '/BMC-SMS/pages/assignments/submit/sub_688b39dd2fcb88.08535015_teacher_attendance (1).sql', 'teacher_attendance (1).sql', 'Accepted', NULL, '2025-07-31 09:39:41', '2025-07-31 09:39:50', 0),
(7, 9, 3, '/BMC-SMS/pages/assignments/submit/sub_688b3b5ad38145.29582346_FINAL1[1].pdf', 'FINAL1[1].pdf', 'Accepted', NULL, '2025-07-31 09:46:02', '2025-07-31 09:46:17', 1),
(8, 11, 3, '/BMC-SMS/pages/assignments/submit/sub_688b3c2ebdebf2.71939847_view_attendence.txt', 'view_attendence.txt', 'Accepted', NULL, '2025-07-31 09:49:34', '2025-07-31 09:49:52', 1),
(9, 12, 3, '/BMC-SMS/pages/assignments/submit/sub_688b4002f0cf99.90497967_teacher_attendance (1).sql', 'teacher_attendance (1).sql', 'Accepted', NULL, '2025-07-31 10:05:54', '2025-07-31 10:06:17', 0),
(10, 13, 3, '/BMC-SMS/pages/assignments/submit/sub_688b40f3842e15.47944399_teacher_attendance (1).sql', 'teacher_attendance (1).sql', 'Accepted', NULL, '2025-07-31 10:09:55', '2025-07-31 10:10:07', 0),
(11, 14, 3, '/BMC-SMS/pages/assignments/submit/sub_688b41f0aa2947.76524506_teacher_attendance (1).sql', 'teacher_attendance (1).sql', 'Accepted', NULL, '2025-07-31 10:14:08', '2025-07-31 10:14:46', 0),
(12, 15, 3, '/BMC-SMS/pages/assignments/submit/sub_688b4262730564.75332767_teacher_attendance (1).sql', 'teacher_attendance (1).sql', 'Accepted', NULL, '2025-07-31 10:16:02', '2025-07-31 10:16:19', 0),
(13, 16, 3, '/BMC-SMS/pages/assignments/submit/sub_688b5dca6fab85.99248405_edit.php', 'edit.php', 'Accepted', NULL, '2025-07-31 12:12:58', '2025-07-31 12:20:58', 0);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `standard` varchar(10) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `period_number` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `teacher_id`, `school_id`, `standard`, `subject`, `period_number`, `attendance_date`, `status`) VALUES
(1, 3, 6, 4, '11', '0', 2, '2025-07-28', 'Absent');

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
(1, 'Rahul Patel', 'rahul@gmail.com', '1', '5th', '2024-2025', '2005-02-02', 'male', 'AB+', 'surat', 'harsh', '6565548720', 'hemina', '6523012304', 3, 'schooladmin', '2025-07-22 11:51:18'),
(13, 'vansh', 'vansh@gmail.com', '15', '12', '2024-2025', '2011-03-11', 'female', 'B+', 'surat', 'girishbhai', '5565615555', 'Sita Patel', '5454454455', 4, 'teacher', '2025-07-24 15:42:54'),
(16, 'mihir', 'mihir@gmail.com', '15', '11', '2024-2025', '2005-08-17', 'male', 'B-', 'nutan', 'janak', '5746895214', 'harshita', '6352417898', 4, 'schooladmin', '2025-07-30 08:06:28');

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
(12, 'ram', 'ram@gmail.com', '5545875655', 'male', '2005-03-11', 'AB+', 'surat', 4, 'MA', 'English', 'English', 100000.00, '5,6', '5', 'Morning', 0, NULL, 'schooladmin', '2025-07-24 09:34:16'),
(14, 'Hemant', 'hemant@gmail.com', '5674231495', 'male', '2000-03-11', 'AB+', 'Surat', 4, 'MA', 'account', 'English', 150000.00, '11,12', '5', 'Morning', 1, '12', 'schooladmin', '2025-07-25 08:19:17'),
(17, 'Yug gandhi', 'yug@gmail.com', '5874693214', 'male', '2005-03-11', 'B-', 'surat', 4, 'MA', 'maths', 'English', 250000.00, '7,9', '5', 'Morning', 1, '7', 'schooladmin', '2025-07-30 08:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `exam_timetables`
--

CREATE TABLE `exam_timetables` (
  `id` int(11) NOT NULL,
  `principal_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_timetables`
--

INSERT INTO `exam_timetables` (`id`, `principal_id`, `school_id`, `title`, `description`, `file_path`, `original_filename`, `created_at`) VALUES
(1, 10, 4, 'Term 1 Exam Timetable', 'time table for term 1', '/BMC-SMS/uploads/timetables/examtt_688b5a0eec5341.29191278_INTERNSHIP REGISTRATION FORM Sujal.pdf', 'INTERNSHIP REGISTRATION FORM Sujal.pdf', '2025-07-31 11:57:02'),
(2, 10, 4, 'Term 2 Exam Timetable', 'dasf', '/BMC-SMS/uploads/timetables/examtt_688b5f8df01541.57695077_INTERNSHIP REGISTRATION FORM JAY (1).pdf', 'INTERNSHIP REGISTRATION FORM JAY (1).pdf', '2025-07-31 12:20:30');

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
  `leave_type` varchar(20) NOT NULL DEFAULT 'Full Day',
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `applied_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `teacher_id`, `from_date`, `to_date`, `reason`, `leave_type`, `status`, `applied_on`, `rejection_reason`) VALUES
(1, 6, '2025-07-30', '2025-08-10', 'My friend\'s marriage', 'Full Day', 'Approved', '2025-07-23 17:40:03', NULL),
(2, 6, '2025-07-31', '2025-08-20', 'swayam marriage', 'Full Day', 'Approved', '2025-07-23 17:55:10', NULL),
(3, 6, '2025-07-26', '2025-07-30', 'Marriage', 'Full Day', 'Rejected', '2025-07-25 07:45:29', 'Because you don\'t deserve'),
(4, 6, '2025-07-25', '2025-07-25', 'fgdgvbdfvc', 'Full Day', 'Approved', '2025-07-25 11:04:58', NULL),
(5, 6, '2025-08-01', '2025-08-17', 'I\'m Sick', 'Full Day', 'Rejected', '2025-07-28 08:31:40', 'You\'re telling lie'),
(6, 6, '2025-07-28', '2025-08-01', 'dcnjdkjcdckdcdk', 'Full Day', 'Rejected', '2025-07-28 09:17:10', 'njcdmcdcd cdcdcdc dm.cdc dcd mcd c mdc md c dc d mcmdm c,d c d c,d mcdm ,cm,dc m,dmcdm,cv m,dmdm,clkm dlv,dfkjnhvnjkmvhfjkm,l.poio-'),
(7, 6, '2025-07-29', '2025-07-29', 'want to go for shopping', 'First Half', 'Rejected', '2025-07-29 09:44:55', 'do shoping after school hours'),
(8, 6, '2025-07-29', '2025-07-29', 'i am sick', 'Second Half', 'Approved', '2025-07-29 09:48:16', NULL),
(9, 6, '2025-07-29', '2025-07-29', 'personal reason\r\n', 'First Half', 'Approved', '2025-07-29 11:03:55', NULL),
(10, 6, '2025-07-29', '2025-07-29', 'personal reason\r\n', 'First Half', 'Approved', '2025-07-29 11:08:03', NULL),
(11, 6, '2025-07-29', '2025-07-29', 'medical emegency', 'Second Half', 'Rejected', '2025-07-29 11:08:42', 'cant'),
(12, 6, '2025-07-09', '2025-07-09', 'i want leave', 'Second Half', 'Rejected', '2025-07-29 11:31:47', 'no you can\'t'),
(13, 6, '2025-07-29', '2025-07-29', 'leave', 'First Half', 'Approved', '2025-07-29 12:35:04', NULL),
(14, 6, '2025-08-01', '2025-08-03', 'oooooooo', 'Full Day', 'Approved', '2025-07-31 11:09:39', NULL),
(15, 6, '2025-08-01', '2025-08-05', 'bghugg', 'Full Day', 'Approved', '2025-07-31 11:20:57', NULL),
(16, 6, '2025-08-01', '2025-08-05', 'bghugg', 'Full Day', 'Rejected', '2025-07-31 11:22:24', 'jnjknjnj'),
(17, 6, '2025-08-08', '2025-08-09', 'bhugv nk nn n', 'Full Day', 'Approved', '2025-07-31 11:23:31', NULL),
(18, 6, '2025-08-08', '2025-08-09', 'bhugv nk nn n', 'Full Day', 'Approved', '2025-07-31 11:24:31', NULL),
(19, 6, '2025-08-01', '2025-08-05', 'csdff', 'Full Day', 'Approved', '2025-07-31 12:11:58', NULL);

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
(3, 6, 4, '11', 'Fee', 'BLAW BLAW', '/BMC-SMS/pages/teacher/uploads/note_6882136a28ca99.45092353_INTERNSHIP REGISTRATION FORM JAY.pdf', 'INTERNSHIP REGISTRATION FORM JAY.pdf', '2025-07-24 11:05:14'),
(4, 6, 4, '11', 'Hello test notification', 'this is test notification for educational purposes only', '/BMC-SMS/pages/teacher/uploads/note_688756ecdc5275.27306642_research sign paper.pdf', 'research sign paper.pdf', '2025-07-28 10:54:36'),
(5, 6, 4, '11', 'safafdevammmmmmmmmmmm', 'devammmmmmmmmmmmmm', NULL, NULL, '2025-07-31 09:02:53'),
(6, 6, 4, '11', 'njisijfj', 'happpp', '/BMC-SMS/pages/teacher/uploads/note_688b317e718586.38465640_view_attendence.txt', 'view_attendence.txt', '2025-07-31 09:03:58'),
(7, 6, 4, '11', 'csfxasc', 'cddddddddddddddddddddddddddddddddddd', NULL, NULL, '2025-07-31 12:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `notice`
--

CREATE TABLE `notice` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notice`
--

INSERT INTO `notice` (`id`, `user_id`, `title`, `content`, `file_path`, `original_filename`, `created_at`) VALUES
(1, 8, 'Internship', 'Complete Internship', '/BMC-SMS/pages/bmc/uploads/notice_68834a91915150.91659686_INTERNSHIP REGISTRATION FORM JAY (4) (1).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4) (1).pdf', '2025-07-25 09:12:49'),
(2, 8, 'Day 6 Test', 'waoaz', '/BMC-SMS/pages/bmc/uploads/notice_688362673aef16.92057394_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4) (1) (1) (1).pdf', '2025-07-25 10:54:31'),
(3, 8, 'Devam ', 'parekh', '/BMC-SMS/pages/bmc/uploads/notice_6887332e030931.00260915_UI-UX_Fenil_74.pdf', 'UI-UX_Fenil_74.pdf', '2025-07-28 08:22:06'),
(4, 8, 'Harsh', 'Shah', '/BMC-SMS/pages/bmc/uploads/notice_6887347bbbd760.91586774_UI-UX_Fenil_74.pdf', 'UI-UX_Fenil_74.pdf', '2025-07-28 08:27:39'),
(5, 8, 'aafafasdf', 'zcasfasfasf', NULL, NULL, '2025-07-31 09:53:31'),
(6, 8, 'efewf', 'cdwdwffffffffffffffffff', NULL, NULL, '2025-07-31 11:01:54'),
(7, 8, 'wefwsee', 'seeeeeeeeee', NULL, NULL, '2025-07-31 11:08:15'),
(8, 8, 'fweff', 'wffw', NULL, NULL, '2025-07-31 12:21:46');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`, `type`) VALUES
(1, 10, 'New notice from BMC: Devam ', '/pages/principal/view_notice.php', 1, '2025-07-28 08:22:06', 'new_notice'),
(2, 10, 'New notice from BMC: Harsh', '/pages/principal/view_notice.php', 1, '2025-07-28 08:27:39', 'new_notice'),
(3, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-28 08:31:40', 'leave_request'),
(4, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-28 09:17:10', 'leave_request'),
(5, 6, 'Your leave application has been Rejected.', '/pages/teacher/teacher_leave_history.php', 1, '2025-07-28 09:18:01', 'leave_status'),
(6, 3, 'New notes posted: Hello test notification...', '/pages/student/view_notes.php', 1, '2025-07-28 10:54:36', 'new_notes'),
(7, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-29 09:44:56', 'leave_request'),
(8, 6, 'Your leave application has been Rejected.', '/pages/teacher/teacher_leave_history.php', 1, '2025-07-29 09:45:50', 'leave_status'),
(9, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-29 09:48:16', 'leave_request'),
(10, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_history.php', 1, '2025-07-29 09:48:41', 'leave_status'),
(11, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-29 11:03:55', 'leave_request'),
(12, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-29 11:08:03', 'leave_request'),
(13, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-29 11:08:42', 'leave_request'),
(14, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_history.php', 1, '2025-07-29 11:09:26', 'leave_status'),
(15, 6, 'Your leave application has been Rejected.', '/pages/teacher/teacher_leave_history.php', 1, '2025-07-29 11:09:32', 'leave_status'),
(16, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_history.php', 1, '2025-07-29 11:09:33', 'leave_status'),
(17, 6, 'New notice from Principal: Email testing...', '/pages/teacher/view_notice.php', 1, '2025-07-29 11:25:01', 'school_notice'),
(18, 3, 'New notice from Principal: Email testing...', '/pages/student/view_notice.php', 1, '2025-07-29 11:25:09', 'school_notice'),
(19, 15, 'New notice from Principal: Email testing...', '/pages/student/view_notice.php', 0, '2025-07-29 11:25:16', 'school_notice'),
(20, 3, 'New notice from Principal: testing...', '/pages/student/view_notice.php', 1, '2025-07-29 11:28:28', 'school_notice'),
(21, 15, 'New notice from Principal: testing...', '/pages/student/view_notice.php', 0, '2025-07-29 11:28:34', 'school_notice'),
(22, 6, 'New notice from Principal: sending to both teacher and students...', '/pages/teacher/view_notice.php', 1, '2025-07-29 11:29:33', 'school_notice'),
(23, 3, 'New notice from Principal: sending to both teacher and students...', '/pages/student/view_notice.php', 1, '2025-07-29 11:29:39', 'school_notice'),
(24, 15, 'New notice from Principal: sending to both teacher and students...', '/pages/student/view_notice.php', 0, '2025-07-29 11:29:44', 'school_notice'),
(25, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-29 11:31:47', 'leave_request'),
(26, 6, 'Your leave application has been Rejected.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-29 11:32:47', 'leave_status'),
(27, 3, 'New Assignment: test...', '/pages/assignments/view_assignments.php', 1, '2025-07-29 12:19:46', 'new_assignment'),
(28, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-29 12:35:04', 'leave_request'),
(29, 3, 'New Assignment: hyy...', '/pages/assignments/view_assignments.php', 1, '2025-07-29 12:43:57', 'new_assignment'),
(30, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-30 08:11:43', 'leave_status'),
(31, 3, 'New notes posted: safafdevammmmmmmmmmmm...', '/pages/student/view_notes.php', 1, '2025-07-31 09:02:53', 'new_notes'),
(32, 3, 'New notes posted: njisijfj...', '/pages/student/view_notes.php', 1, '2025-07-31 09:03:58', 'new_notes'),
(33, 3, 'New Assignment: fsdgfasf...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 09:28:40', 'new_assignment'),
(34, 3, 'New Assignment: sggtwgt...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 09:29:44', 'new_assignment'),
(35, 3, 'New Assignment: fenil...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 09:37:21', 'new_assignment'),
(36, 3, 'New Assignment: sff...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 09:48:34', 'new_assignment'),
(37, 6, 'devam parekh has submitted an assignment.', '/BMC-SMS/pages/assignments/view_submissions.php?id=11', 1, '2025-07-31 09:48:55', 'assignment_submission'),
(38, 6, 'devam parekh has submitted an assignment.', '/BMC-SMS/pages/assignments/view_submissions.php?id=11', 1, '2025-07-31 09:49:34', 'assignment_submission'),
(39, 10, 'New notice from BMC: aafafasdf', '/pages/principal/view_notice.php', 1, '2025-07-31 09:53:31', 'new_notice'),
(40, 6, 'New notice from Principal: ffdefw...', '/pages/teacher/view_notice.php', 1, '2025-07-31 09:54:19', 'school_notice'),
(41, 3, 'New notice from Principal: ffdefw...', '/pages/student/view_notice.php', 1, '2025-07-31 09:54:24', 'school_notice'),
(42, 15, 'New notice from Principal: ffdefw...', '/pages/student/view_notice.php', 0, '2025-07-31 09:54:28', 'school_notice'),
(43, 6, 'New notice from Principal: fweff...', '/pages/teacher/view_notice.php', 1, '2025-07-31 10:01:37', 'school_notice'),
(44, 3, 'New notice from Principal: fweff...', '/pages/student/view_notice.php', 1, '2025-07-31 10:01:41', 'school_notice'),
(45, 15, 'New notice from Principal: fweff...', '/pages/student/view_notice.php', 0, '2025-07-31 10:01:48', 'school_notice'),
(46, 3, 'New Assignment: ddasdsfd...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 10:05:12', 'new_assignment'),
(47, 6, 'devam parekh has submitted an assignment.', '/BMC-SMS/pages/assignments/view_submissions.php?id=12', 1, '2025-07-31 10:05:55', 'assignment_submission'),
(48, 3, 'New Assignment: bjb...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 10:09:34', 'new_assignment'),
(49, 6, 'devam parekh has submitted an assignment.', '/BMC-SMS/pages/assignments/view_submissions.php?id=13', 1, '2025-07-31 10:09:55', 'assignment_submission'),
(50, 3, 'New Assignment: ssff...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 10:13:51', 'new_assignment'),
(51, 6, 'devam parekh has submitted an assignment.', '/BMC-SMS/pages/assignments/view_submissions.php?id=14', 1, '2025-07-31 10:14:08', 'assignment_submission'),
(52, 3, 'New Assignment: qwfdadfdd...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 10:15:45', 'new_assignment'),
(53, 6, 'devam parekh has submitted an assignment.', '/BMC-SMS/pages/assignments/view_submissions.php?id=15', 1, '2025-07-31 10:16:02', 'assignment_submission'),
(54, 10, 'New notice from BMC: efewf', '/pages/principal/view_notice.php', 1, '2025-07-31 11:01:54', 'new_notice'),
(55, 10, 'New notice from BMC: wefwsee', '/pages/principal/view_notice.php', 1, '2025-07-31 11:08:15', 'new_notice'),
(56, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-31 11:09:39', 'leave_request'),
(57, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-31 11:12:56', 'leave_status'),
(58, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-31 11:20:57', 'leave_request'),
(59, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-31 11:21:59', 'leave_status'),
(60, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-31 11:22:24', 'leave_request'),
(61, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-31 11:23:31', 'leave_request'),
(62, 6, 'Your leave application has been Rejected.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-31 11:24:03', 'leave_status'),
(63, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-31 11:24:17', 'leave_status'),
(64, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-31 11:24:31', 'leave_request'),
(65, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-31 11:25:23', 'leave_status'),
(66, 8, 'New Notice from Fenil Pastagia', '/pages/bmc/view_principal_notices.php', 1, '2025-07-31 11:39:41', 'principal_notice'),
(67, 6, 'New Exam Timetable: Term 1 Exam Timetable', '/pages/teacher/view_exam_timetable.php', 1, '2025-07-31 11:57:03', 'exam_timetable'),
(68, 3, 'New Exam Timetable: Term 1 Exam Timetable', '/pages/student/view_exam_timetable.php', 1, '2025-07-31 11:57:03', 'exam_timetable'),
(69, 15, 'New Exam Timetable: Term 1 Exam Timetable', '/pages/student/view_exam_timetable.php', 0, '2025-07-31 11:57:03', 'exam_timetable'),
(70, 3, 'New Assignment: asfffascsa...', '/pages/assignments/view_assignments.php', 1, '2025-07-31 12:11:37', 'new_assignment'),
(71, 10, 'New leave request from meet parekh', '/pages/principal/principal_leave_requests.php', 1, '2025-07-31 12:11:58', 'leave_request'),
(72, 3, 'New notes posted: csfxasc...', '/pages/student/view_notes.php', 1, '2025-07-31 12:12:31', 'new_notes'),
(73, 6, 'devam parekh has submitted an assignment.', '/BMC-SMS/pages/assignments/view_submissions.php?id=16', 1, '2025-07-31 12:12:58', 'assignment_submission'),
(74, 6, 'Your leave application has been Approved.', '/pages/teacher/teacher_leave_management.php', 1, '2025-07-31 12:15:49', 'leave_status'),
(75, 6, 'New notice from Principal: csdff...', '/pages/teacher/view_notice.php', 1, '2025-07-31 12:16:25', 'school_notice'),
(76, 3, 'New notice from Principal: csdff...', '/pages/student/view_notice.php', 1, '2025-07-31 12:16:30', 'school_notice'),
(77, 15, 'New notice from Principal: csdff...', '/pages/student/view_notice.php', 0, '2025-07-31 12:16:35', 'school_notice'),
(78, 8, 'New Notice from Fenil Pastagia', '/pages/bmc/view_principal_notices.php', 1, '2025-07-31 12:16:52', 'principal_notice'),
(79, 6, 'New Exam Timetable: Term 2 Exam Timetable', '/pages/teacher/view_exam_timetable.php', 1, '2025-07-31 12:20:30', 'exam_timetable'),
(80, 3, 'New Exam Timetable: Term 2 Exam Timetable', '/pages/student/view_exam_timetable.php', 1, '2025-07-31 12:20:30', 'exam_timetable'),
(81, 15, 'New Exam Timetable: Term 2 Exam Timetable', '/pages/student/view_exam_timetable.php', 0, '2025-07-31 12:20:30', 'exam_timetable'),
(82, 10, 'New notice from BMC: fweff', '/pages/principal/view_notice.php', 1, '2025-07-31 12:21:46', 'new_notice');

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
  `dob` date DEFAULT NULL,
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

INSERT INTO `principal` (`id`, `principal_image`, `school_id`, `principal_name`, `email`, `password`, `phone`, `dob`, `gender`, `blood_group`, `address`, `qualification`, `salary`, `batch`) VALUES
(10, '', 4, 'Fenil Pastagia', '17fenill@gmail.com', '$2y$10$EaSZM1Mq/otD2L1wHMoZdefcPjkOWeXPjePcvdj5WLY/6Lx5DxrJ6', '9924976503', '0000-00-00', 'Female', 'B+', 'canal road', 'M.A. M.Ed', 90000.00, 'Morning');

-- --------------------------------------------------------

--
-- Table structure for table `principal_attendance`
--

CREATE TABLE `principal_attendance` (
  `id` int(11) NOT NULL,
  `principal_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL,
  `login_latitude` decimal(10,8) DEFAULT NULL,
  `login_longitude` decimal(11,8) DEFAULT NULL,
  `login_time` time NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `principal_attendance`
--

INSERT INTO `principal_attendance` (`id`, `principal_id`, `school_id`, `attendance_date`, `status`, `login_latitude`, `login_longitude`, `login_time`, `updated_at`) VALUES
(1, 10, 4, '2025-07-30', 'Absent', 21.21014980, 72.77075840, '23:47:25', '2025-07-30 18:17:25'),
(12, 10, 4, '2025-07-31', 'Absent', NULL, NULL, '17:44:31', '2025-07-31 12:14:31'),
(20, 10, 4, '2025-08-01', 'Absent', 21.18458692, 72.77910595, '14:15:32', '2025-08-01 08:45:32');

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
-- Table structure for table `principal_to_bmc_notices`
--

CREATE TABLE `principal_to_bmc_notices` (
  `id` int(11) NOT NULL,
  `principal_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `principal_to_bmc_notices`
--

INSERT INTO `principal_to_bmc_notices` (`id`, `principal_id`, `school_id`, `title`, `content`, `file_path`, `original_filename`, `created_at`) VALUES
(1, 10, 4, 'mioweojfkj', 'jwndjkngfbn', NULL, NULL, '2025-07-31 11:39:41'),
(2, 10, 4, 'njhshdjgsjd', ' sdhjbfdhbfdbwfbwfbw gwvshjvwehjfhsjbfhjsbhewb', NULL, NULL, '2025-07-31 12:16:52');

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
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `passing_percentage` decimal(5,2) NOT NULL DEFAULT 33.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school`
--

INSERT INTO `school` (`id`, `school_logo`, `school_name`, `email`, `phone`, `school_opening`, `school_type`, `education_board`, `school_medium`, `school_category`, `address`, `latitude`, `longitude`, `passing_percentage`) VALUES
(4, NULL, 'sanskar bharti vidyalay', 'sbv@gmail.com', '8526548525', '2025-07-06', 'Private', 'CBSE', 'Hindi', '', 'adajan', 21.21060270, 72.76795460, 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `school_notices_content`
--

CREATE TABLE `school_notices_content` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_notices_content`
--

INSERT INTO `school_notices_content` (`id`, `user_id`, `school_id`, `title`, `content`, `file_path`, `original_filename`, `created_at`) VALUES
(2, 10, 4, 'Internship', 'Do Work', '/BMC-SMS/pages/principal/uploads/notice_688352064e9079.52076292_INTERNSHIP REGISTRATION FORM JAY (4) (1) (1).pdf', 'INTERNSHIP REGISTRATION FORM JAY (4) (1) (1).pdf', '2025-07-25 09:44:38'),
(3, 10, 4, 'Complete work', 'HII', '/BMC-SMS/pages/principal/uploads/notice_6883539e857812.52522225_INTERNSHIP REGISTRATION FORM JAY (5).pdf', 'INTERNSHIP REGISTRATION FORM JAY (5).pdf', '2025-07-25 09:51:26'),
(4, 10, 4, 'Email testing', 'this notice is being sent to test email feature', '/BMC-SMS/pages/principal/uploads/notice_6888af8d830663.02075899_💻 Case Study.pdf', '💻 Case Study.pdf', '2025-07-29 11:25:01'),
(5, 10, 4, 'testing', 'hello', '/BMC-SMS/pages/principal/uploads/notice_6888b05c2e9f11.53130874_💻 Case Study.pdf', '💻 Case Study.pdf', '2025-07-29 11:28:28'),
(6, 10, 4, 'sending to both teacher and students', 'testing', NULL, NULL, '2025-07-29 11:29:33'),
(7, 10, 4, 'ffdefw', 'dewwwwwwww', NULL, NULL, '2025-07-31 09:54:19'),
(8, 10, 4, 'fweff', 'casfafsf', NULL, NULL, '2025-07-31 10:01:37'),
(9, 10, 4, 'csdff', 'readdddd', NULL, NULL, '2025-07-31 12:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `school_notice_recipients`
--

CREATE TABLE `school_notice_recipients` (
  `id` int(11) NOT NULL,
  `notice_id` int(11) NOT NULL,
  `recipient_type` enum('teacher','standard') NOT NULL,
  `recipient_identifier` varchar(50) NOT NULL COMMENT 'Stores teacher ID or standard number'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_notice_recipients`
--

INSERT INTO `school_notice_recipients` (`id`, `notice_id`, `recipient_type`, `recipient_identifier`) VALUES
(1, 2, 'teacher', '6'),
(2, 2, 'standard', '11'),
(3, 3, 'teacher', '6'),
(4, 4, 'teacher', '6'),
(5, 4, 'standard', '10'),
(6, 4, 'standard', '11'),
(7, 5, 'standard', '10'),
(8, 5, 'standard', '11'),
(9, 6, 'teacher', '6'),
(10, 6, 'standard', '10'),
(11, 6, 'standard', '11'),
(12, 7, 'teacher', '6'),
(13, 7, 'standard', '10'),
(14, 7, 'standard', '11'),
(15, 8, 'teacher', '6'),
(16, 8, 'standard', '10'),
(17, 8, 'standard', '11'),
(18, 9, 'teacher', '6'),
(19, 9, 'standard', '10'),
(20, 9, 'standard', '11');

-- --------------------------------------------------------

--
-- Table structure for table `school_timetable`
--

CREATE TABLE `school_timetable` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `standard` varchar(10) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `period_number` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_timetable`
--

INSERT INTO `school_timetable` (`id`, `school_id`, `standard`, `day_of_week`, `period_number`, `subject_name`, `teacher_id`, `start_time`, `end_time`) VALUES
(1, 4, '11', 'Monday', 1, 'Computer Science', 6, '08:00:00', '09:00:00'),
(2, 4, '11', 'Monday', 2, '0', 6, '09:00:00', '10:00:00'),
(3, 4, '11', 'Tuesday', 1, 'English', 6, '18:00:00', '19:00:00'),
(4, 4, '11', 'Tuesday', 2, '0', 6, '09:00:00', '10:00:00'),
(5, 4, '11', 'Wednesday', 1, 'Mathematics', 6, '08:00:00', '09:00:00'),
(6, 4, '11', 'Wednesday', 2, '0', 6, '09:00:00', '10:00:00'),
(7, 4, '11', 'Thursday', 1, 'Physical Education', 6, '08:09:00', '09:00:00'),
(8, 4, '11', 'Thursday', 2, '0', 6, '09:00:00', '10:00:00'),
(9, 4, '11', 'Friday', 1, 'Sanskrit', 6, '08:00:00', '09:00:00'),
(10, 4, '11', 'Friday', 2, '0', 6, '09:00:00', '10:00:00'),
(11, 4, '11', 'Saturday', 1, 'Science', 6, '08:00:00', '09:00:00'),
(12, 4, '11', 'Saturday', 2, '0', 6, '09:00:00', '10:00:00'),
(62, 4, '11', 'Monday', 3, 'Science', 6, '18:46:00', '00:00:00'),
(64, 4, '11', 'Tuesday', 3, 'Sanskrit', 6, '00:00:00', '00:00:00'),
(66, 4, '11', 'Wednesday', 3, 'Social Studies', 6, '00:00:00', '00:00:00'),
(68, 4, '11', 'Thursday', 3, 'Physical Education', 6, '00:00:00', '00:00:00'),
(70, 4, '11', 'Friday', 3, 'English', 6, '00:00:00', '00:00:00'),
(72, 4, '11', 'Saturday', 3, 'Mathematics', 6, '00:00:00', '00:00:00');

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
(100, '1', 1),
(103, '1', 2),
(105, '1', 3),
(99, '1', 9),
(102, '1', 11),
(101, '1', 12),
(104, '1', 16),
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
(3, NULL, 'devam parekh', '9', '11', 'devamparekh1200@gmail.com', '$2y$10$vl/hHLMF3ar5GEc6pQJfVexTt3vKCXoAGF/9HcDtgGGDsfKHoXHQu', '2024-2025', 4, '2025-07-11', 'male', 'b+', 'canal road', 'mukesh', '9874522589', 'sunita', '753685124'),
(15, '../../pages/student/uploads/student_6888af4a9563a3.55306099.jpg', 'harsh shah', '26', '10', 'shh.260105@gmail.com', '$2y$10$nj4MFVjg.rCq6AmmAOX3jewd9VDTeNZCvWoeE138bfbUQaFAZmtY2', '2025-2026', 4, '2005-01-26', 'male', 'ab+', 'navyug', 'hemant shah', '8520321456', 'sunita shah', '6547852365');

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

--
-- Dumping data for table `student_marks`
--

INSERT INTO `student_marks` (`mark_id`, `student_id`, `school_id`, `academic_year`, `std`, `division`, `exam_type`, `subject_name`, `marks_obtained`, `total_marks`, `entry_date`, `entered_by_user_id`) VALUES
(85, 3, 4, '2025-2026', '11', '', 'term_1', 'Computer Science', 90.00, 100.00, '2025-07-24 13:08:59', 6),
(86, 3, 4, '2025-2026', '11', '', 'term_1', 'English', 80.00, 100.00, '2025-07-24 13:08:59', 6),
(87, 3, 4, '2025-2026', '11', '', 'term_1', 'Mathematics', 80.00, 100.00, '2025-07-24 13:08:59', 6),
(88, 3, 4, '2025-2026', '11', '', 'term_1', 'Physical Education', 80.00, 100.00, '2025-07-24 13:08:59', 6),
(89, 3, 4, '2025-2026', '11', '', 'term_1', 'Sanskrit', 70.00, 100.00, '2025-07-24 13:08:59', 6),
(90, 3, 4, '2025-2026', '11', '', 'term_1', 'Science', 80.00, 100.00, '2025-07-24 13:08:59', 6),
(91, 3, 4, '2025-2026', '11', '', 'term_1', 'Social Studies', 80.00, 100.00, '2025-07-24 13:08:59', 6),
(92, 3, 4, '2025-2026', '11', '', 'final_exam', 'Computer Science', 75.00, 100.00, '2025-07-25 11:01:54', 6),
(93, 3, 4, '2025-2026', '11', '', 'final_exam', 'English', 85.00, 100.00, '2025-07-25 11:01:54', 6),
(94, 3, 4, '2025-2026', '11', '', 'final_exam', 'Mathematics', 85.00, 100.00, '2025-07-25 11:01:54', 6),
(95, 3, 4, '2025-2026', '11', '', 'final_exam', 'Physical Education', 85.00, 100.00, '2025-07-25 11:01:54', 6),
(96, 3, 4, '2025-2026', '11', '', 'final_exam', 'Sanskrit', 99.00, 100.00, '2025-07-25 11:01:54', 6),
(97, 3, 4, '2025-2026', '11', '', 'final_exam', 'Science', 99.00, 100.00, '2025-07-25 11:01:54', 6),
(98, 3, 4, '2025-2026', '11', '', 'final_exam', 'Social Studies', 99.00, 100.00, '2025-07-25 11:01:54', 6),
(99, 3, 4, '2025-2026', '11', '', 'term_2', 'Computer Science', 50.00, 100.00, '2025-07-31 08:55:32', 6),
(100, 3, 4, '2025-2026', '11', '', 'term_2', 'English', 60.00, 100.00, '2025-07-31 08:55:32', 6),
(101, 3, 4, '2025-2026', '11', '', 'term_2', 'Mathematics', 99.00, 100.00, '2025-07-31 08:55:32', 6),
(102, 3, 4, '2025-2026', '11', '', 'term_2', 'Physical Education', 99.00, 100.00, '2025-07-31 08:55:32', 6),
(103, 3, 4, '2025-2026', '11', '', 'term_2', 'Sanskrit', 99.00, 100.00, '2025-07-31 08:55:32', 6),
(104, 3, 4, '2025-2026', '11', '', 'term_2', 'Science', 99.00, 100.00, '2025-07-31 08:55:32', 6),
(105, 3, 4, '2025-2026', '11', '', 'term_2', 'Social Studies', 99.00, 100.00, '2025-07-31 08:55:32', 6);

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
(6, '../../pages/teacher/uploads/teacher_6880cd02b30464.45441036.jpg', 'meet parekh', '9900990099', 4, '2025-07-01', 'Male', 'B-', 'mota varachaa', 'otherswayam@gmail.com', '$2y$10$sdz4DZ5oaMJNrUA9mld44uiBNIIkAQCPjs2XrrnUcl.Bp6wlzYz1a', 'B.A', 'maths', 'english', 100000, '8,9,10,11,12', '10', 'Evening', 1, '11');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `attendance_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `marked_by_user_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`attendance_id`, `teacher_id`, `school_id`, `attendance_date`, `status`, `remark`, `marked_by_user_id`, `updated_at`) VALUES
(1, 6, 4, '2025-07-28', 'Leave', NULL, 10, '2025-07-28 08:53:46'),
(2, 15, 4, '2025-07-28', 'Absent', NULL, 10, '2025-07-28 08:53:30'),
(3, 16, 4, '2025-07-28', 'Present', NULL, 10, '2025-07-28 09:09:23'),
(4, 17, 4, '2025-07-28', 'Absent', NULL, 10, '2025-07-28 09:15:12'),
(6, 19, 4, '2025-07-28', 'Absent', NULL, 10, '2025-07-28 09:22:16'),
(7, 20, 4, '2025-07-28', 'Present', NULL, 10, '2025-07-28 09:26:16'),
(8, 15, 4, '2025-07-27', 'Absent', NULL, 10, '2025-07-28 09:35:08'),
(9, 16, 4, '2025-07-27', 'Present', NULL, 10, '2025-07-28 09:35:08'),
(10, 19, 4, '2025-07-27', 'Present', NULL, 10, '2025-07-28 09:35:08'),
(11, 6, 4, '2025-07-27', 'Present', NULL, 10, '2025-07-28 09:35:08'),
(12, 17, 4, '2025-07-27', 'Present', NULL, 10, '2025-07-28 09:35:08'),
(13, 20, 4, '2025-07-27', 'Present', NULL, 10, '2025-07-28 09:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_timings`
--

CREATE TABLE `teacher_timings` (
  `timing_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `opens_at` time DEFAULT NULL,
  `closes_at` time DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_timings`
--

INSERT INTO `teacher_timings` (`timing_id`, `teacher_id`, `day_of_week`, `opens_at`, `closes_at`, `is_closed`) VALUES
(0, 6, 'Monday', '10:00:00', '18:00:00', 0),
(0, 6, 'Tuesday', '10:00:00', '18:00:00', 0),
(0, 6, 'Wednesday', '10:00:00', '18:00:00', 0),
(0, 6, 'Thursday', '10:00:00', '18:00:00', 0),
(0, 6, 'Friday', '10:00:00', '18:00:00', 0),
(0, 6, 'Saturday', NULL, NULL, 1),
(0, 6, 'Sunday', NULL, NULL, 1),
(0, 17, 'Monday', '10:00:00', '18:00:00', 0),
(0, 17, 'Tuesday', '10:00:00', '18:00:00', 0),
(0, 17, 'Wednesday', '10:00:00', '18:00:00', 0),
(0, 17, 'Thursday', '10:00:00', '18:00:00', 0),
(0, 17, 'Friday', '10:00:00', '18:00:00', 0),
(0, 17, 'Saturday', NULL, NULL, 1),
(0, 17, 'Sunday', NULL, NULL, 1);

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
  `password` varchar(255) NOT NULL,
  `account_status` enum('active','suspended') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `email`, `password`, `account_status`) VALUES
(3, 'student', 'devamparekh1200@gmail.com', '$2y$10$vl/hHLMF3ar5GEc6pQJfVexTt3vKCXoAGF/9HcDtgGGDsfKHoXHQu', 'active'),
(6, 'teacher', 'otherswayam@gmail.com', '$2y$10$sdz4DZ5oaMJNrUA9mld44uiBNIIkAQCPjs2XrrnUcl.Bp6wlzYz1a', 'active'),
(8, 'bmc', 'shahswayam7125@gmail.com', '$2y$10$T74F9Gb05l.StKcZg2sy/ub6PHeH.l3tT3Lv1JwOZzioXJCdEN0zO', 'active'),
(10, 'schooladmin', '17fenill@gmail.com', '$2y$10$EaSZM1Mq/otD2L1wHMoZdefcPjkOWeXPjePcvdj5WLY/6Lx5DxrJ6', 'active'),
(15, 'student', 'shh.260105@gmail.com', '$2y$10$nj4MFVjg.rCq6AmmAOX3jewd9VDTeNZCvWoeE138bfbUQaFAZmtY2', 'active');

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_lecture_attendance` (`student_id`,`attendance_date`,`period_number`);

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
-- Indexes for table `exam_timetables`
--
ALTER TABLE `exam_timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `principal_id` (`principal_id`);

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
-- Indexes for table `notice`
--
ALTER TABLE `notice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
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
-- Indexes for table `principal_attendance`
--
ALTER TABLE `principal_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_principal_attendance` (`principal_id`,`attendance_date`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `principal_timings`
--
ALTER TABLE `principal_timings`
  ADD PRIMARY KEY (`timing_id`),
  ADD UNIQUE KEY `uq_principal_day` (`principal_id`,`day_of_week`);

--
-- Indexes for table `principal_to_bmc_notices`
--
ALTER TABLE `principal_to_bmc_notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `principal_id` (`principal_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `school_notices_content`
--
ALTER TABLE `school_notices_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `school_notice_recipients`
--
ALTER TABLE `school_notice_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notice_id` (`notice_id`);

--
-- Indexes for table `school_timetable`
--
ALTER TABLE `school_timetable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lecture_slot` (`school_id`,`standard`,`day_of_week`,`period_number`);

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
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `uq_teacher_attendance_date` (`teacher_id`,`attendance_date`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `marked_by_user_id` (`marked_by_user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deleted_principals`
--
ALTER TABLE `deleted_principals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deleted_students`
--
ALTER TABLE `deleted_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `deleted_teachers`
--
ALTER TABLE `deleted_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `exam_timetables`
--
ALTER TABLE `exam_timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notice`
--
ALTER TABLE `notice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `principal_attendance`
--
ALTER TABLE `principal_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `principal_timings`
--
ALTER TABLE `principal_timings`
  MODIFY `timing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `principal_to_bmc_notices`
--
ALTER TABLE `principal_to_bmc_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `school_notices_content`
--
ALTER TABLE `school_notices_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `school_notice_recipients`
--
ALTER TABLE `school_notice_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `school_timetable`
--
ALTER TABLE `school_timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `standard_subjects`
--
ALTER TABLE `standard_subjects`
  MODIFY `std_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
-- Constraints for table `exam_timetables`
--
ALTER TABLE `exam_timetables`
  ADD CONSTRAINT `fk_ett_principal` FOREIGN KEY (`principal_id`) REFERENCES `principal` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ett_school` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `notice`
--
ALTER TABLE `notice`
  ADD CONSTRAINT `notice_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `principal`
--
ALTER TABLE `principal`
  ADD CONSTRAINT `fk_principal_user_id` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `principal_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`);

--
-- Constraints for table `principal_attendance`
--
ALTER TABLE `principal_attendance`
  ADD CONSTRAINT `principal_attendance_ibfk_1` FOREIGN KEY (`principal_id`) REFERENCES `principal` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `principal_attendance_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `principal_timings`
--
ALTER TABLE `principal_timings`
  ADD CONSTRAINT `fk_timing_principal_id` FOREIGN KEY (`principal_id`) REFERENCES `principal` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `principal_to_bmc_notices`
--
ALTER TABLE `principal_to_bmc_notices`
  ADD CONSTRAINT `fk_pbn_principal` FOREIGN KEY (`principal_id`) REFERENCES `principal` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pbn_school` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_notices_content`
--
ALTER TABLE `school_notices_content`
  ADD CONSTRAINT `school_notices_content_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `school_notices_content_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_notice_recipients`
--
ALTER TABLE `school_notice_recipients`
  ADD CONSTRAINT `fk_notice_recipients_notice_id` FOREIGN KEY (`notice_id`) REFERENCES `school_notices_content` (`id`) ON DELETE CASCADE;

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
