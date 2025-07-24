-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2025 at 10:55 AM
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
(1, 3, 6, 4, '11', '2025-07-24', 'Present', NULL),
(2, 11, 6, 4, '11', '2025-07-24', 'Absent', NULL);

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
(1, 'JAY', 'jay@gmail.com', '5674298791', 'male', '2005-11-03', 'AB-', '0', 3, 'BA', 'Account', 'Hindi', 500000.00, 'Nursery,Junior,1', '5', 'Evening', 0, NULL, 'schooladmin', '2025-07-22 11:51:18');

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
(3, NULL, 'devam parekh', '9', '11', 'devam@gmail.com', '$2y$10$vl/hHLMF3ar5GEc6pQJfVexTt3vKCXoAGF/9HcDtgGGDsfKHoXHQu', '2024-2025', 4, '2025-07-11', 'male', 'b+', 'canal road', 'mukesh', '9874522589', 'sunita', '753685124'),
(11, '../../pages/student/uploads/student_6881e50218b1f5.49660569.jpg', 'harsh shah', '10', '11', 'harsh@gmail.com', '$2y$10$819fe414Y7sRnMLMDPtoDOnJCkFLE8BNRJaB1zFnh9LIxeLQHHaSa', '2024-2025', 4, '2025-07-08', 'male', 'ab+', 'navyug', 'hemant', '9898440096', 'Sita', '9924976503');

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
(10, 'schooladmin', 'fenil@gmail.com', '$2y$10$EaSZM1Mq/otD2L1wHMoZdefcPjkOWeXPjePcvdj5WLY/6Lx5DxrJ6'),
(11, 'student', 'harsh@gmail.com', '$2y$10$819fe414Y7sRnMLMDPtoDOnJCkFLE8BNRJaB1zFnh9LIxeLQHHaSa');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `principal`
--
ALTER TABLE `principal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `school_id` (`school_id`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `standard_subjects`
--
ALTER TABLE `standard_subjects`
  MODIFY `std_subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `principal`
--
ALTER TABLE `principal`
  ADD CONSTRAINT `fk_principal_user_id` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `principal_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
