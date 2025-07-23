-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2025 at 10:07 AM
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
  `school_std` set('Pre-Primary','Primary (1-5)','Upper Primary (6-8)','Secondary (9-10)','Higher Secondary (11-12)') DEFAULT NULL,
  `address` text DEFAULT NULL
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
  `salary` decimal(10,2) DEFAULT NULL,
  `std` set('Nursery','Junior','Senior','1','2','3','4','5','6','7','8','9','10','11','12') DEFAULT NULL,
  `experience` varchar(10) DEFAULT NULL,
  `batch` enum('Morning','Evening') DEFAULT NULL,
  `class_teacher` tinyint(1) DEFAULT 0,
  `class_teacher_std` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'schooladmin', 'fenil@gmail.com', '$2y$10$d3NpW61HsPhfQMhrnQyz0uzEJbkMXRJrQZkPC6pnfls5JA/Ck0bKe'),
(2, 'teacher', 'swayam@gmail.com', '$2y$10$s18f7OGGbOoMEB1i4eqFSuI5r07Zry8HfpshQvXi9GWR122mK81.y'),
(3, 'student', 'meet@gmail.com', '$2y$10$Az8jVXsuxHYWC6EfnPTKy.dLTS.YENi5B5bCgMhpNLsKzvC1S9Ahu'),
(4, 'student', 'ram@gmail.com', '$2y$10$JBzHDObjqBb/tBd86d1V1.N4WnAORx8Y6EVSJFZVKf0bR0Fioq3BW');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`,`password`),
  ADD KEY `school_id` (`school_id`);

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
-- AUTO_INCREMENT for table `principal`
--
ALTER TABLE `principal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `principal`
--
ALTER TABLE `principal`
  ADD CONSTRAINT `principal_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `school` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
