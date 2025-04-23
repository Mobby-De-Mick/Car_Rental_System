-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2025 at 07:00 AM
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
-- Database: `car_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `license_no` varchar(20) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `ctype` enum('Compact','Medium','Large','SUV','Van','Truck') NOT NULL,
  `is_available` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `license_no`, `model`, `year`, `ctype`, `is_available`, `created_at`) VALUES
(1, 'KCD 120D', 'Toyota Harrier', 2021, 'Medium', '', '2025-03-29 18:08:38'),
(2, 'KDR 224R', 'Mazda', 2022, 'Compact', '', '2025-03-29 18:22:11'),
(4, 'KDS 560T', 'Mazda', 2021, 'Compact', 'available', '2025-03-29 20:12:03'),
(7, 'KCS 780R', 'Toyota Camry', 2022, 'Medium', 'available', '2025-03-30 11:51:37'),
(8, 'KBE 230Y', 'Subaru Legacy', 2014, 'Medium', '', '2025-03-30 11:53:27'),
(9, 'KDR 870R', 'Ford Taurus', 2023, 'Large', '', '2025-03-30 11:54:33'),
(10, 'KDU 567C', 'Toyota RAV4', 2022, 'SUV', '', '2025-03-30 12:04:44'),
(11, 'KDE 892R', 'Jeep Grand Cherokee', 2023, 'SUV', 'available', '2025-03-30 12:05:44'),
(12, 'KBR 464S', 'Subaru Outback', 2012, 'SUV', 'available', '2025-03-30 12:06:51'),
(13, 'KCP 239N', 'Toyota Sienna', 2021, 'Van', 'available', '2025-03-30 12:07:44'),
(14, 'KCR 892G', 'Mercedes-Benz Sprinter', 2020, 'Van', '', '2025-03-30 12:09:02'),
(15, 'KCB 349D', 'Ford F-150', 2022, 'Truck', 'available', '2025-03-30 12:09:44'),
(16, 'KCM 832P', 'Nissan Maxima', 2019, 'Large', '', '2025-03-30 12:10:48'),
(17, 'KDE 642T', 'Ford Taurus', 2023, 'Large', 'available', '2025-03-30 12:11:55'),
(18, 'KBR 372V', 'Nissan Quest', 2016, 'Van', 'available', '2025-03-30 12:13:37'),
(20, 'KCS 957W', 'Nissan Titan', 2021, 'Truck', 'available', '2025-03-30 12:15:39'),
(21, 'KCP 389T', 'Toyota Tacoma', 2020, 'Truck', '', '2025-03-30 12:16:31'),
(22, 'KCY 245G', 'Nissan Altima', 2019, 'Medium', 'available', '2025-03-30 12:30:24'),
(23, 'KCW 740Y', 'Honda Civic', 2021, 'Compact', 'available', '2025-04-10 08:55:44'),
(26, 'KCF 467R', 'Toyota Prado', 2019, 'SUV', '', '2025-04-10 09:16:18'),
(27, 'KBS 783F', 'Corolla', 2017, 'Compact', '', '2025-04-10 09:33:29'),
(28, 'KCG 892L', 'Lexus Lx', 2020, 'SUV', 'available', '2025-04-10 09:40:39'),
(31, 'KCN 289B', 'Kia Picanto', 2016, 'Compact', 'available', '2025-04-10 10:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payid` int(11) NOT NULL,
  `Rid` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `receipt_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`Payid`, `Rid`, `amount`, `payment_date`, `payment_method`, `payment_status`, `receipt_no`, `notes`) VALUES
(1, 1, 25.00, '2025-04-14 19:01:52', 'cash', 'pending', NULL, 'Payment for return charges');

-- --------------------------------------------------------

--
-- Table structure for table `rental_rates`
--

CREATE TABLE `rental_rates` (
  `id` int(11) NOT NULL,
  `Ctype` enum('Compact','Medium','Large','SUV','Van','Truck') NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `weekly_rate` decimal(10,2) NOT NULL,
  `monthly_rate` decimal(10,2) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_rates`
--

INSERT INTO `rental_rates` (`id`, `Ctype`, `daily_rate`, `weekly_rate`, `monthly_rate`, `updated_at`) VALUES
(1, 'Compact', 30.00, 150.00, 500.00, '2025-03-29 18:12:52'),
(2, 'Medium', 40.00, 200.00, 650.00, '2025-03-29 18:12:52'),
(3, 'Large', 50.00, 250.00, 800.00, '2025-03-29 18:12:52'),
(4, 'SUV', 60.00, 300.00, 950.00, '2025-03-29 18:12:52'),
(5, 'Van', 70.00, 350.00, 1100.00, '2025-03-29 18:12:52'),
(6, 'Truck', 80.00, 400.00, 1250.00, '2025-03-29 18:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `renting`
--

CREATE TABLE `renting` (
  `Rid` int(11) NOT NULL,
  `booking_ref` varchar(12) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `license_no` varchar(20) NOT NULL,
  `Sdate` date NOT NULL,
  `Nodays` int(11) NOT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `return_processed_date` datetime DEFAULT NULL,
  `Noweeks` int(11) DEFAULT 0,
  `total_price` decimal(10,2) NOT NULL,
  `additional_charges` decimal(10,2) DEFAULT 0.00,
  `damage_report` text DEFAULT NULL,
  `fuel_level_on_return` enum('full','3/4','1/2','1/4','empty') DEFAULT NULL,
  `damage_severity` enum('none','minor','moderate','major','severe') DEFAULT NULL,
  `Rtype` enum('Daily','Weekly','Monthly') NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Ctype` enum('Compact','Medium','Large','SUV','Van','Truck') NOT NULL,
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `amount_paid` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renting`
--

INSERT INTO `renting` (`Rid`, `booking_ref`, `user_id`, `license_no`, `Sdate`, `Nodays`, `actual_return_date`, `return_processed_date`, `Noweeks`, `total_price`, `additional_charges`, `damage_report`, `fuel_level_on_return`, `damage_severity`, `Rtype`, `payment_method`, `status`, `created_at`, `updated_at`, `Ctype`, `payment_status`, `amount_paid`) VALUES
(1, NULL, 18, 'KCS 780R', '0000-00-00', 1, '2025-04-14 00:00:00', '2025-04-14 19:01:52', 0, 40.00, 44385082.23, '', '3/4', 'none', 'Daily', '0', 'completed', '2025-04-14 15:14:33', '2025-04-14 16:01:52', 'Medium', 'partial', 25.00);

-- --------------------------------------------------------

--
-- Table structure for table `return_car`
--

CREATE TABLE `return_car` (
  `id` int(11) NOT NULL,
  `fuel_level` int(11) NOT NULL,
  `damage_severity` int(11) NOT NULL,
  `damage_description` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `return_date` datetime DEFAULT current_timestamp(),
  `rental_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `age` int(11) NOT NULL,
  `mobile` varchar(10) NOT NULL,
  `dlno` varchar(20) NOT NULL,
  `insno` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `verification_code`, `created_at`, `last_login`, `fname`, `lname`, `age`, `mobile`, `dlno`, `insno`) VALUES
(2, 'vin@gmail.com', '202cb962ac59075b964b07152d234b70', '', '2025-03-29 20:34:45', NULL, '', '', 0, '', '', ''),
(3, 'okuku12@gmail.com', '$2y$10$P6j4mVKpLagCG2PPXjvvIOhzdOAy/jlEYWdjzvd1dy45nTy1.sJnK', '', '2025-04-01 13:11:44', NULL, '', '', 0, '', '', ''),
(4, 'mbugua@gmail.com', '$2y$10$e2GU9BfnF/8/J8EBeibiTOwXfkhTeFiSb4v/ziNWrSjiZ9of4.IAC', '', '2025-04-02 07:24:02', NULL, '', '', 0, '', '', ''),
(5, 'reagannesimiyu@gmail.com', '$2y$10$qA9yve4R2J3SweyOb8jL1uWQGMA5L2f/xWIELPdC2VQp.lBWKR8E2', '', '2025-04-02 07:29:35', NULL, '', '', 0, '', '', ''),
(6, 'barasavin@gmail.com', '$2y$10$U68Gq2CKRZqm48/t0yZNs.9LHNvSbKg7bQbFxMCd3WYSL5aNdJ8lS', '', '2025-04-02 07:46:46', NULL, '', '', 0, '', '', ''),
(7, 'john@gmail.com', '$2y$10$sTlBJr8ugfi/sOLtIZsS8eiHs2pLqoBk958794S/5o.FN4r0hXAKO', '', '2025-04-02 07:54:19', NULL, '', '', 0, '', '', ''),
(8, 'victor12@gmail.com', '$2y$10$Tl0W8NHVszTwR0hwNGXJDO6NnC.XwkwKjA.UNtqIl4iLgoZzqHOIW', NULL, '2025-04-07 14:06:30', NULL, '', '', 0, '', '', ''),
(9, 'clinton1@gmail.com', '$2y$10$/nPexteJZyNIMmj23/EDyeOfmbodSdHK7Bw0Q7HrEwlU1Xr5F7eUu', NULL, '2025-04-10 07:16:20', NULL, '', '', 0, '', '', ''),
(10, 'nyamasenge12@gmail.com', '$2y$10$PHKpX.z/wv8jTezEXpkMzurweHgOBSG9WDLPtTM3zMWzFlr5ML062', NULL, '2025-04-10 10:57:01', NULL, '', '', 0, '', '', ''),
(11, 'kennedy12@gmail.com', '$2y$10$lNhtiZ3/zahwiDPzdURHtOlxY1/YiG5MkVkhXBbzn.3F8LOIe9V5O', NULL, '2025-04-10 11:15:11', NULL, '', '', 0, '', '', ''),
(12, 'ochieng12@gmail.com', '$2y$10$NFUe8LHe18YT3Ag.OD8MWuibvha4AauSAeQtCcXSw5CjRU0L3F0kS', NULL, '2025-04-10 11:21:04', NULL, '', '', 0, '', '', ''),
(13, 'reagann@gmail.com', '$2y$10$F2pp.iU/6yU.1HSSv1aI5Ob/iZJotzvExXWRByqQC7CNhv4G3KUXm', NULL, '2025-04-11 06:42:22', NULL, '', '', 0, '', '', ''),
(14, 'edward12@gmail.com', '$2y$10$5e/BReWLDtqZETK7sHWxjek3q33wPeYDy.ngYyhHdXCE3jPsrJ1YK', NULL, '2025-04-11 09:45:05', NULL, '', '', 0, '', '', ''),
(15, 'anne12@gmail.com', '$2y$10$IAWp7/WyQclhgf/kT4jygePtEHiWFVAOB7v9uf2QrjpbkUm4UeYFG', NULL, '2025-04-12 16:14:52', NULL, '', '', 0, '', '', ''),
(16, 'vin12@gmail.com', '$2y$10$IG8Jp/3SM1s0O.62XbO3ZuhsoxfBJc7qVi9OfYmyfRiMXFJO3026C', NULL, '2025-04-13 20:04:01', NULL, '', '', 0, '', '', ''),
(18, 'yohana12@gmail.com', '$2y$10$gyvgmq2iPmTk1pUD43wDt.SBgL5W5uwWz06cF66De2IRncb2pqCJq', NULL, '2025-04-14 13:22:27', '2025-04-18 07:59:19', '', '', 0, '', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_no` (`license_no`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payid`),
  ADD KEY `fk_payment_rid` (`Rid`);

--
-- Indexes for table `rental_rates`
--
ALTER TABLE `rental_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Ctype` (`Ctype`);

--
-- Indexes for table `renting`
--
ALTER TABLE `renting`
  ADD PRIMARY KEY (`Rid`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `return_car`
--
ALTER TABLE `return_car`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rental_rates`
--
ALTER TABLE `rental_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `renting`
--
ALTER TABLE `renting`
  MODIFY `Rid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `return_car`
--
ALTER TABLE `return_car`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_rid` FOREIGN KEY (`Rid`) REFERENCES `renting` (`Rid`) ON DELETE CASCADE;

--
-- Constraints for table `renting`
--
ALTER TABLE `renting`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `renting_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
