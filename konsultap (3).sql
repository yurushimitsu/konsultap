-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2024 at 08:27 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `konsultap`
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
  `status` varchar(50) NOT NULL,
  `IdNumber` varchar(255) DEFAULT NULL,
  `doctor` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `consult_done` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `appointment_date`, `appointment_time`, `appointment_type`, `reason`, `medication`, `created_at`, `is_unread`, `status`, `IdNumber`, `doctor`, `url`, `description`, `consult_done`) VALUES
(1, '2024-05-08', '08:00:00', 'onsite', 'test', 'test', '2024-05-05', 0, 'accept', '2021156233', 'Jeddy Manalili', 'this is the link', '', 1),
(2, '2024-05-09', '00:00:00', 'Leave', 'I am on leave', 'N/A', '2024-05-05', 1, 'event', '202215969', '', '', '', 0),
(3, '2024-05-10', '00:00:00', 'Leave', 'leave', 'N/A', '2024-05-05', 1, 'event', '202215969', '', '', '', 0),
(4, '2024-05-15', '10:00:00', 'online', 'sada', 'sada', '2024-05-05', 1, 'accept', '2021156233', 'Jeddy Manalili', '', '', 0);

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
  `Password` varchar(50) NOT NULL,
  `role` enum('faculty') NOT NULL DEFAULT 'faculty',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `fullName`, `IdNumber`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`, `profile_picture`) VALUES
(1, 'Cyrus cotoner', '202315859', 'Male', 'Cyrus@gmail.com', '167', '66', '92311312', 'Simon Cotoner', 'December 1 1999', '202315859', 'faculty', 'active', NULL);

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
  `Password` varchar(50) NOT NULL,
  `role` enum('itc') NOT NULL DEFAULT 'itc',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itc`
--

INSERT INTO `itc` (`id`, `fullName`, `IdNumber`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`, `profile_picture`) VALUES
(1, 'Lebron James', '202015754', 'Male', 'colinrexler@gmail.com', '150', '80', '9252952', 'Jebron James', 'July 1 1999', '202015754', 'itc', 'active', NULL),
(2, 'King engine', '202015859', 'Female', 'kingengine@gmail.com', '140', '75', '92222222', 'Eko Selasor', 'July 1 2000', '202015859', 'itc', 'active', NULL),
(3, 'Eko Selasor', '21212', 'Male', 'eko@gmail', '150', '80', '2413123', '', 'Jan 1 1999', '21212', 'itc', 'active', NULL),
(4, 'Gabrielle De Jesus', '201872312', 'Male ', 'gabriel@gmail.com', '170', '23', '32323212', '', 'June 8 2001', '201872312', 'itc', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medical_history_records`
--

CREATE TABLE `medical_history_records` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `date_added` date NOT NULL,
  `medpracNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_history_records`
--

INSERT INTO `medical_history_records` (`id`, `description`, `IdNumber`, `date_added`, `medpracNumber`) VALUES
(2, 'hello2', '2021156233', '2024-05-04', '202215969'),
(3, 'hello3', '2021156233', '2024-05-04', '202215969');

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
  `Password` varchar(50) NOT NULL,
  `role` enum('medical practitioner') NOT NULL DEFAULT 'medical practitioner',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medprac`
--

INSERT INTO `medprac` (`id`, `fullName`, `IdNumber`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`, `profile_picture`) VALUES
(1, 'Jeddy Manalili', '202215969', 'Male', 'leonthiago770@gmail.com', '150', '60', '92311313', 'John Manalili', 'July 5 2000', '202215969', 'medical practitioner', 'active', NULL),
(2, 'Cheenah', '202116964', 'Male', 'Sebastian@gmail.com', '170', '75', '9272284516', 'Lila Thiago', 'July 31 2003', '202116964', 'medical practitioner', 'active', NULL);

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

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`id`, `date`, `message`, `is_unread`) VALUES
(1, '2024-05-08', 'Booked an appointment', 1),
(2, '2024-05-15', 'Booked an appointment', 1);

-- --------------------------------------------------------

--
-- Table structure for table `prescription_records`
--

CREATE TABLE `prescription_records` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `date_added` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_records`
--

INSERT INTO `prescription_records` (`id`, `description`, `IdNumber`, `date_added`) VALUES
(1, 'hello', '2021156233', '2024-05-04');

-- --------------------------------------------------------

--
-- Table structure for table `procedure_history_records`
--

CREATE TABLE `procedure_history_records` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `IdNumber` varchar(50) NOT NULL,
  `date_added` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procedure_history_records`
--

INSERT INTO `procedure_history_records` (`id`, `description`, `IdNumber`, `date_added`) VALUES
(1, 'hello', '2021156233', '2024-05-03'),
(3, 'xd', '2021156233', '2024-05-04'),
(4, 's', '2021156233', '2024-05-04'),
(5, 'helo', '2021156233', '2024-05-04'),
(6, 'xd', '2021156233', '2024-05-04'),
(7, 'xd', '2021156233', '2024-05-04');

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
  `Phone` int(20) NOT NULL,
  `ContactPerson` varchar(50) NOT NULL,
  `dateofbirth` varchar(50) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `role` enum('student') NOT NULL DEFAULT 'student',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `fullName`, `IdNumber`, `GradeLevel`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`, `profile_picture`) VALUES
(5, 'Christian Malimit', '2021156233', '10', 'Male', 'christianmalimit@gmail.com', '178', '60', 99922323, 'Ramy Malimit', 'June 7 2002', '2021156233', 'student', 'active', NULL),
(6, 'Ghab Mayoralgo', '202115854', '9', 'Female', 'Ghab@gmail.com', '170', '65', 924231232, 'Lebron Mayoralgo', 'September 8 2002', '202115854', 'student', 'active', NULL),
(9, 'Kobe bryant', '201915754', '8', '', 'kobe@gmail.com', '169', '69', 922323132, 'Mamba bryant', 'December 30 1999', '201915754', 'student', 'active', NULL),
(10, 'Mark Montero', '202132318', '10', 'Male', 'mark@gmail.com', '170', '75', 23231212, '', 'June 8 2001', '202132318', 'student', 'active', NULL),
(12, 'Mark Aguinaldo', '202132319', '10', 'Male', 'mark@gmail.com', '170', '75', 23231212, '', 'June 8 2001', '202132319', 'student', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `itc`
--
ALTER TABLE `itc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_history_records`
--
ALTER TABLE `medical_history_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medprac`
--
ALTER TABLE `medprac`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prescription_records`
--
ALTER TABLE `prescription_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `procedure_history_records`
--
ALTER TABLE `procedure_history_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
