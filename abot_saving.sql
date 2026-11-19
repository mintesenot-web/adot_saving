-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2026 at 11:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `abot_saving`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `former_savings`
--

CREATE TABLE `former_savings` (
  `former_id` int(11) NOT NULL,
  `member_name` varchar(150) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `saving_date` date NOT NULL,
  `remark` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `gallery_id` int(11) NOT NULL,
  `photo_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `upload_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `icare_savings`
--

CREATE TABLE `icare_savings` (
  `icare_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `saving_date` date NOT NULL,
  `remark` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `photo` varchar(255) NOT NULL,
  `members_id` int(11) NOT NULL,
  `full_name` varchar(50) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `sex` enum('M','F') NOT NULL,
  `loan_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`photo`, `members_id`, `full_name`, `phone`, `sex`, `loan_amount`) VALUES
('1794817943_9394.JPG', 1, 'Mintesenot Serani', '0961284452', 'M', 0.00),
('1794818062_5302.JPG', 2, 'Abel Dereje', '0908755709', 'M', 0.00),
('1794818171_7677.JPG', 3, 'Semegn Gezahegn', '0958285944', 'F', 0.00),
('1794818288_5582.JPG', 4, 'Aman Kidane', '0970802854', 'M', 0.00),
('1794818647_7766.JPG', 5, 'Feyene Bogale', '0910657480', 'F', 0.00),
('1794818916_6457.JPG', 6, 'Helina Mengestu', '0933962577', 'F', 0.00),
('1794821944_5881.JPG', 7, 'Mekedes Werku', '0922826334', 'F', 0.00),
('1794822046_1441.JPG', 8, 'Hana Girma', '0951057813', 'F', 0.00),
('1794822130_9712.JPG', 9, 'Yeabsera Jemal', '0952438778', 'M', 0.00),
('1794822198_8692.JPG', 10, 'Helina Ayalew', '0986710733', 'F', 0.00),
('1794822407_5602.png', 11, 'Firezer Husen', '0963385960', 'M', 0.00),
('1794822495_2562.png', 12, 'Esubalew Gemeda', '0941293531', 'M', 0.00),
('1794822576_5419.png', 13, 'Fuad Jemal', '0932199687', 'M', 0.00),
('1794822653_1582.JPG', 14, 'Abreham Birhanu', '0973098539', 'M', 0.00),
('1794822767_3027.png', 15, 'Eliyas Chernet', '0920291728', 'M', 0.00),
('1794822948_6699.JPG', 16, 'Bireket F/Amilak', '0900000000', 'M', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `savings`
--

CREATE TABLE `savings` (
  `saving_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `saving_date` date NOT NULL,
  `round_month` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','guest') NOT NULL,
  `member_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `member_id`) VALUES
(1, 'mintesinot.s', '36e17ba1aaaffab0a59379d96f5cea26', 'guest', 1),
(2, 'abel.d', 'dab806f3438b9531e77afc6236a31e8c', 'guest', 2),
(3, 'semegn.g', '3c4bd057d68dc90589720bd6e141a434', 'guest', 3),
(4, 'aman.k', '7b666598f500bd2ff31d2a924bdec353', 'guest', 4),
(5, 'feyene.b', '2410444b950d53077ab20525c88e40a6', 'guest', 5),
(6, 'helina.m', '41cdc4daeafe6829353153a7e0af63b2', 'guest', 6),
(7, 'mekedes.w', 'f804e5a4feb78d5bab8115a01f13c888', 'guest', 7),
(8, 'hana.g', 'e353508ed01947f48b7d5981c35c593b', 'guest', 8),
(9, 'yeabsera.j', 'f42945d18eab43d9dd279d9fc346211b', 'guest', 9),
(10, 'helina.a', '16166d973126b9aba23e3d21d42f601d', 'guest', 10),
(11, 'firezer.h', 'b559860798a3aeb8f41c9333f6ecc24d', 'guest', 11),
(12, 'esubalew.g', '5c4343293e76fde8dabde32f79d0442c', 'guest', 12),
(13, 'fuad.j', 'dd3054d3cae87c3d778d4b526194e078', 'guest', 13),
(14, 'abreham.b', 'a42c00742aad929ff58c0b203efcd1f5', 'guest', 14),
(15, 'eliyas.c', '87aa03388a935f8a4a7d43721f0db671', 'guest', 15),
(16, 'bireket.a', 'dad672ec638635ccfb0aa73c06b9738b', 'guest', 16),
(17, 'guest_visitor', '0a2c0dc210845c41ef6a163f0fe07da8', 'guest', NULL),
(18, 'admin', 'ce5dec5f7d5cda41bd625bd6a23bb9aa', 'owner', NULL),
(19, 'visitor', '9f12d754602873cd6116ec9d0ecb7ecb', 'guest', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `former_savings`
--
ALTER TABLE `former_savings`
  ADD PRIMARY KEY (`former_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`gallery_id`);

--
-- Indexes for table `icare_savings`
--
ALTER TABLE `icare_savings`
  ADD PRIMARY KEY (`icare_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`members_id`);

--
-- Indexes for table `savings`
--
ALTER TABLE `savings`
  ADD PRIMARY KEY (`saving_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `former_savings`
--
ALTER TABLE `former_savings`
  MODIFY `former_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `gallery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `icare_savings`
--
ALTER TABLE `icare_savings`
  MODIFY `icare_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `members_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `savings`
--
ALTER TABLE `savings`
  MODIFY `saving_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
