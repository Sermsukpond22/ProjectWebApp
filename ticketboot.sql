-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2024 at 10:22 AM
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
-- Database: `ticketboot`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permissions` enum('add','delete','update') NOT NULL DEFAULT 'update'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booths`
--

CREATE TABLE `booths` (
  `booth_id` int(11) NOT NULL,
  `booth_name` varchar(100) NOT NULL,
  `booth_size` varchar(50) DEFAULT NULL,
  `booth_status` enum('available','under_review','booked') NOT NULL DEFAULT 'available',
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booths`
--

INSERT INTO `booths` (`booth_id`, `booth_name`, `booth_size`, `booth_status`, `price`, `image_url`, `zone_id`) VALUES
(6, 'Booth A', '10x10', 'booked', 1500.00, 'http://example.com/image1.jpg', 2),
(8, 'Booth A', '10x10', '', 1500.00, 'http://example.com/image1.jpg', 2),
(9, 'Booth A6', '10x10', '', 1500.00, 'http://example.com/image1.jpg', 2),
(10, 'Booth A8', '10x10', '', 1500.00, 'http://example.com/image1.jpg', 2);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `start_date`, `end_date`) VALUES
(1, 'Updated Music Festival 2024', '2024-09-18', '2024-09-25');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booth_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `status` enum('available','approve','under_review','reserve') NOT NULL,
  `payment_status` enum('unpaid','pending','paid','failed') NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `booth_id`, `reservation_date`, `status`, `payment_status`, `payment_proof`) VALUES
(1, 3, 6, '2024-09-15', 'available', 'pending', 'http://example.com/proof.jpg'),
(3, 1, 6, '2024-09-15', 'approve', 'paid', 'http://example.com/proof.jpg'),
(5, 1, 6, '2024-09-16', 'available', 'pending', 'http://example.com/proof.jpg sddsd'),
(6, 1, 9, '2024-09-25', 'under_review', 'pending', 'http://example.com/proof.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('guest','member','admin') NOT NULL DEFAULT 'guest'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fname`, `lname`, `phone`, `email`, `password`, `role`) VALUES
(1, 'Pond', 'Sermsuk', '0623813389', 'sermsuk.b@gmail.com', 'pond_2210', 'guest'),
(2, ' pond', 'boonserm', '0623813384', 'sermsuk@gmail.com', 'pond2210', 'member'),
(3, 'John', 'Doe', '0812345678', 'john.doe@example.com', '$2y$10$4qimZCmtGjeiTG2dZseuA.bMc/NYy739NePw8MZKTZG22lmrUl5c6', 'member'),
(4, 'Boonsaen', 'Sermsuk', '0812345554', 'boonsean@example.com', '$2y$10$2Wee2MVO1TIhofHB/jHituR0mAWgLNsd6rbT3kuM3C9wKeo18iM.u', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `zone_id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `zone_info` text DEFAULT NULL,
  `number_of_booths` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zones`
--

INSERT INTO `zones` (`zone_id`, `zone_name`, `zone_info`, `number_of_booths`) VALUES
(2, 'Zone B', 'Information about Zone B', 10),
(4, 'Zone A', 'Information about Zone A', 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `booths`
--
ALTER TABLE `booths`
  ADD PRIMARY KEY (`booth_id`),
  ADD KEY `fk_zone` (`zone_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booth_id` (`booth_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`zone_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booths`
--
ALTER TABLE `booths`
  MODIFY `booth_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `zones`
--
ALTER TABLE `zones`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `booths`
--
ALTER TABLE `booths`
  ADD CONSTRAINT `booths_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`zone_id`),
  ADD CONSTRAINT `fk_zone` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`zone_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`booth_id`) REFERENCES `booths` (`booth_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
