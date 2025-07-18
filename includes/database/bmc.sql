-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 12:39 PM
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
  `salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `principal`
--

INSERT INTO `principal` (`id`, `principal_image`, `school_id`, `principal_name`, `email`, `password`, `phone`, `principal_dob`, `gender`, `blood_group`, `address`, `qualification`, `salary`) VALUES
(1, NULL, 1, 'Mr. Rajiv Mehta', 'rajiv.mehta@school.com', '', '9876543211', '1975-04-15', 'Male', 'O+', 'Ring Road, Surat', 'M.Ed', 75000.00),
(2, NULL, 2, 'Mrs. Sneha Patel', 'sneha.patel@school.com', '', '9876543212', '1980-06-20', 'Female', 'A+', 'Vesu, Surat', 'M.A. B.Ed', 72000.00),
(3, NULL, 3, 'Dr. Anil Shah', 'anil.shah@school.com', '', '9876543213', '1968-11-30', 'Male', 'B+', 'Adajan, Surat', 'Ph.D (Education)', 85000.00),
(4, NULL, 4, 'Ms. Nita Desai', 'nita.desai@school.com', '', '9876543214', '1985-09-10', 'Female', 'AB+', 'Piplod, Surat', 'M.Sc B.Ed', 70000.00),
(5, NULL, 5, 'Mx. Karan Yadav', 'karan.yadav@school.com', '', '9876543215', '1990-02-25', 'Others', 'O-', 'City Light, Surat', 'M.A. M.Ed', 68000.00);

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `id` int(11) NOT NULL,
  `school_name` varchar(100) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school`
--

INSERT INTO `school` (`id`, `school_name`, `email`, `phone`, `address`) VALUES
(1, 'Sunrise Public School', 'sunrise@school.com', '9876543210', 'Ring Road, Surat'),
(2, 'Green Valley High School', 'greenvalley@school.com', '9823456789', 'Vesu, Surat'),
(3, 'Silver Oak International', 'silveroak@school.com', '9812345678', 'Adajan, Surat'),
(4, 'Little Star English Medium School', 'littlestar@school.com', '9898989898', 'Piplod, Surat'),
(5, 'Divine Child School', 'divinechild@school.com', '9797979797', 'City Light, Surat');

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
(1, '', 'fenil', '74', '2', 'fenil@gmail.com', '$2y$10$CXWzpU7y5UCLmiuegRegw.j7GELyUv16QQCFsUldbImOLaNYttPDi', '2025', 3, '2005-08-17', 'female', 'b+', 'canalr road', 'rupesh', '9874562162', 'falguni', '8987453259'),
(2, '../../uploads/students/687a2026b84c5.jpg', 'harsh shah', '106', '8', 'harsh@gmail.com', '$2y$10$aPcvMgOkAPNqJ31BCg2Dsu4tomQUtxtcpEOkmzz.Gk3HKDonMn2iS', '2024', 3, '2005-01-26', 'male', 'ab+', 'shantibhavan', 'heman bhai', '874632158', 'sunita', '753685124'),
(4, '../../uploads/students/687a21940d1b2.jpg', 'meet', '81', '7', 'meet@gmail.com', '$2y$10$qn4Vk/7w8qZ4R5SDOW5a/uSgcZbcMlvXQZcijCal4fF9sr2ez8xcK', '2023', 3, '2005-09-04', 'male', 'b+', 'mota varachaa', 'girishbhai', '9999999999', 'vanita', '88888888'),
(5, '../../uploads/students/687a228bc2425.jpg', 'devam', '69', '5', 'devam@gmail.com', '$2y$10$4cLjSLbL5XzUYe21oR.z.uc0ObTYCQfYZfzx6WGhEDI5uRg4RjeMy', '2023', 3, '2005-03-11', 'female', 'b+', 'LP savani', 'mukesh', '7412589630', 'harshna', '852369741'),
(6, '../../uploads/students/687a2391c3340.png', 'swayam', '109', '9', 'swayam@gmail.com', '$2y$10$An.ypKg7HqSFA7Dto1W4P.loF2FQJs/jt6/kNsfgNq8ovhXop3.jS', '2025-2026', 3, '2005-12-07', 'male', 'b+', 'pal', 'sanket', '9327874000', 'grishma', '7878097797');

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
(1, 'bmc', 'ravishankar@gmail.com', 'rs123'),
(3, 'student', 'fenil@gmail.com', '$2y$10$CXWzpU7y5UCLmiuegRegw.j7GELyUv16QQCFsUldbImOLaNYttPDi'),
(5, 'student', 'harsh@gmail.com', '$2y$10$aPcvMgOkAPNqJ31BCg2Dsu4tomQUtxtcpEOkmzz.Gk3HKDonMn2iS'),
(8, 'student', 'meet@gmail.com', '$2y$10$qn4Vk/7w8qZ4R5SDOW5a/uSgcZbcMlvXQZcijCal4fF9sr2ez8xcK'),
(9, 'student', 'devam@gmail.com', '$2y$10$4cLjSLbL5XzUYe21oR.z.uc0ObTYCQfYZfzx6WGhEDI5uRg4RjeMy'),
(10, 'student', 'swayam@gmail.com', '$2y$10$An.ypKg7HqSFA7Dto1W4P.loF2FQJs/jt6/kNsfgNq8ovhXop3.jS');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `principal`
--
ALTER TABLE `principal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
