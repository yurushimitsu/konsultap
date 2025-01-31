-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 10, 2025 at 03:46 AM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u801109301_konsultap`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_type` varchar(100) NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `medication` varchar(1000) NOT NULL,
  `created_at` date NOT NULL DEFAULT current_timestamp(),
  `is_unread` int(11) DEFAULT 1,
  `is_unreadusers` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `IdNumber` varchar(255) DEFAULT NULL,
  `doctor` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `consult_done` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cancel_appointments`
--

CREATE TABLE `cancel_appointments` (
  `id` int(11) NOT NULL,
  `IdNumber` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `appointment_date_cancelled` date DEFAULT NULL,
  `appointment_time_cancel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `fullName` varchar(50) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `Gender` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Height` varchar(50) NOT NULL,
  `Weight` varchar(50) NOT NULL,
  `Phone` varchar(50) NOT NULL,
  `ContactPerson` varchar(50) NOT NULL,
  `dateofbirth` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `role` enum('faculty') NOT NULL DEFAULT 'faculty',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `disabled_at` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `itc`
--

CREATE TABLE `itc` (
  `id` int(11) NOT NULL,
  `fullName` varchar(50) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `Gender` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Height` varchar(50) NOT NULL,
  `Weight` varchar(50) NOT NULL,
  `Phone` varchar(50) NOT NULL,
  `ContactPerson` varchar(50) NOT NULL,
  `dateofbirth` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `role` enum('itc') NOT NULL DEFAULT 'itc',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `disabled_at` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_history_records`
--

CREATE TABLE `medical_history_records` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `date_added` date NOT NULL,
  `medpracNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medprac`
--

CREATE TABLE `medprac` (
  `id` int(11) NOT NULL,
  `fullName` varchar(50) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `Gender` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Height` varchar(50) NOT NULL,
  `Weight` varchar(50) NOT NULL,
  `Phone` varchar(50) NOT NULL,
  `ContactPerson` varchar(50) NOT NULL,
  `dateofbirth` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `role` enum('medical practitioner') NOT NULL DEFAULT 'medical practitioner',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `disabled_at` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `message` varchar(100) NOT NULL,
  `is_unread` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_forgotpassword`
--

CREATE TABLE `notification_forgotpassword` (
  `id` int(11) NOT NULL,
  `IdNumber` varchar(55) NOT NULL,
  `fullName` varchar(55) NOT NULL,
  `is_unread` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_records`
--

CREATE TABLE `prescription_records` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `date_added` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procedure_history_records`
--

CREATE TABLE `procedure_history_records` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `date_added` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `fullName` varchar(50) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `GradeLevel` varchar(50) NOT NULL,
  `Gender` varchar(10) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Height` varchar(10) NOT NULL,
  `Weight` varchar(10) NOT NULL,
  `Phone` varchar(11) NOT NULL,
  `ContactPerson` varchar(50) NOT NULL,
  `dateofbirth` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `role` enum('student') NOT NULL DEFAULT 'student',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `disabled_at` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videoconference`
--

CREATE TABLE `videoconference` (
  `id` int(11) NOT NULL,
  `appointments_id` int(11) NOT NULL,
  `videoconference_id` varchar(255) NOT NULL,
  `idNumber_user1` varchar(50) NOT NULL,
  `idNumber_user2` varchar(50) NOT NULL,
  `fullName_user1` varchar(255) NOT NULL,
  `fullName_user2` varchar(255) NOT NULL,
  `active_participants` int(11) NOT NULL,
  `user1_joined` tinyint(4) DEFAULT 0,
  `consult_done` int(11) NOT NULL,
  `call_duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cancel_appointments`
--
ALTER TABLE `cancel_appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `itc`
--
ALTER TABLE `itc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medical_history_records`
--
ALTER TABLE `medical_history_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medprac`
--
ALTER TABLE `medprac`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_forgotpassword`
--
ALTER TABLE `notification_forgotpassword`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prescription_records`
--
ALTER TABLE `prescription_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `procedure_history_records`
--
ALTER TABLE `procedure_history_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `videoconference`
--
ALTER TABLE `videoconference`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cancel_appointments`
--
ALTER TABLE `cancel_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `itc`
--
ALTER TABLE `itc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_history_records`
--
ALTER TABLE `medical_history_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medprac`
--
ALTER TABLE `medprac`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_forgotpassword`
--
ALTER TABLE `notification_forgotpassword`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_records`
--
ALTER TABLE `prescription_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procedure_history_records`
--
ALTER TABLE `procedure_history_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `videoconference`
--
ALTER TABLE `videoconference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
