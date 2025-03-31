-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 21, 2025 at 03:52 AM
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
-- Database: `u171783535_ysabelles`
--

-- --------------------------------------------------------

--
-- Table structure for table `bond`
--

CREATE TABLE `bond` (
  `bondID` int(255) NOT NULL,
  `transactionID` int(255) NOT NULL,
  `employeeID` int(255) DEFAULT NULL,
  `dateBond` date NOT NULL,
  `depositBond` int(255) DEFAULT NULL,
  `releaseBond` int(255) DEFAULT NULL,
  `bondCurrentBalance` int(11) DEFAULT NULL,
  `noteBond` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bond`
--

INSERT INTO `bond` (`bondID`, `transactionID`, `employeeID`, `dateBond`, `depositBond`, `releaseBond`, `bondCurrentBalance`, `noteBond`) VALUES
(45, 2, 1, '2025-03-05', 6000, NULL, 6000, ''),
(46, 4, 1, '2025-03-07', 500, NULL, 500, ''),
(47, 5, 1, '2025-03-07', 100, NULL, 100, ''),
(48, 4, 1, '2025-03-07', NULL, 500, 0, ''),
(49, 6, 1, '2025-03-11', 200, NULL, 200, ''),
(50, 6, 1, '2025-03-11', NULL, 200, 0, ''),
(51, 7, 8, '2025-03-12', 500, NULL, 500, ''),
(52, 7, 8, '2025-03-12', NULL, 500, 0, ''),
(55, 9, 8, '2025-03-13', 500, NULL, 500, ''),
(60, 3, 8, '2025-03-13', 2000, NULL, 2000, ''),
(62, 16, 8, '2025-03-13', 500, NULL, 500, ''),
(63, 16, 8, '2025-03-13', NULL, 500, 0, ''),
(64, 18, 8, '2025-03-13', 500, NULL, 500, ''),
(65, 19, 8, '2025-03-13', 500, NULL, 500, ''),
(66, 19, 8, '2025-03-13', NULL, 500, 0, ''),
(67, 20, 8, '2025-03-13', 500, NULL, 500, ''),
(68, 20, 8, '2025-03-13', NULL, 500, 0, ''),
(72, 28, 8, '2025-03-14', 2000, NULL, 2000, ''),
(80, 30, 1, '2025-03-14', 5000, NULL, 5000, ''),
(81, 30, 1, '2025-03-15', 5000, NULL, 5000, ''),
(82, 30, 1, '2025-03-17', NULL, 2000, 3000, ''),
(83, 35, 1, '2025-03-17', 2000, NULL, 2000, ''),
(85, 28, 1, '2025-03-17', NULL, 2000, 0, ''),
(86, 30, 1, '2025-03-17', NULL, 2000, 1000, ''),
(87, 30, 1, '2025-03-17', NULL, 1000, 0, ''),
(88, 35, 1, '2025-03-17', NULL, 1000, 1000, ''),
(89, 35, 1, '2025-03-17', NULL, 1000, 0, ''),
(90, 37, 1, '2025-03-18', 2000, NULL, 2000, ''),
(91, 38, 1, '2025-03-19', 1000, NULL, 1000, '');

-- --------------------------------------------------------

--
-- Table structure for table `branch_requests`
--

CREATE TABLE `branch_requests` (
  `requestID` int(11) NOT NULL,
  `sourceBranch` varchar(255) NOT NULL,
  `destinationBranch` varchar(255) NOT NULL,
  `products` text NOT NULL,
  `notes` text DEFAULT NULL,
  `requiredDate` date NOT NULL,
  `dateRequested` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('PENDING','APPROVED','DECLINED','COMPLETED','CANCELED') NOT NULL DEFAULT 'PENDING',
  `requestedBy` int(11) NOT NULL,
  `respondedBy` int(11) DEFAULT NULL,
  `completedBy` int(11) DEFAULT NULL,
  `completedDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branch_requests`
--

INSERT INTO `branch_requests` (`requestID`, `sourceBranch`, `destinationBranch`, `products`, `notes`, `requiredDate`, `dateRequested`, `status`, `requestedBy`, `respondedBy`, `completedBy`, `completedDate`) VALUES
(1, 'DUMAGUETE CITY', 'BACOLOD CITY', '[{\"id\":62,\"name\":\"David\",\"code\":\"dv001\",\"price\":\"4800.00\"}]', '', '2025-03-20', '2025-03-18 03:45:23', 'APPROVED', 2, 8, 8, '2025-03-18 03:47:01'),
(2, 'DUMAGUETE CITY', 'BACOLOD CITY', '[{\"id\":105,\"name\":\"JACK\",\"code\":\"JA009\",\"price\":\"2400.00\"}]', '', '2025-03-21', '2025-03-18 03:49:31', 'APPROVED', 2, 8, 8, '2025-03-18 03:50:23'),
(4, 'DUMAGUETE CITY', 'BACOLOD CITY', '[{\"id\":6,\"name\":\"JESSICA\",\"code\":\"mymtmjt\",\"price\":\"3000.00\"},{\"id\":147,\"name\":\"MARVIN\",\"code\":\"123fdsfsre\",\"price\":\"32100000.00\"},{\"id\":126,\"name\":\"Stefan\",\"code\":\"STF1\",\"price\":\"2355.00\"}]', '', '2025-03-21', '2025-03-18 04:02:55', 'APPROVED', 2, 8, NULL, NULL),
(5, 'DUMAGUETE CITY', 'BACOLOD CITY', '[{\"id\":145,\"name\":\"Variation Product\",\"code\":\"sfdsfds\",\"price\":\"1232312.00\"}]', '', '2025-03-19', '2025-03-18 05:05:44', 'APPROVED', 2, 8, NULL, NULL),
(6, 'BACOLOD CITY', 'DUMAGUETE CITY', '[{\"id\":147,\"name\":\"MARVIN\",\"code\":\"123fdsfsre\",\"price\":\"32100000.00\"},{\"id\":28,\"name\":\"MILLARO\",\"code\":null,\"price\":\"7100.00\"}]', 'Needed by March 20!', '2025-03-20', '2025-03-19 05:34:12', 'APPROVED', 8, 2, NULL, NULL),
(7, 'BACOLOD CITY', 'SAN CARLOS CITY', '[{\"id\":37,\"name\":\"Aljur\",\"code\":\"al004\",\"price\":\"5000.00\"}]', '', '2025-03-20', '2025-03-19 05:51:35', 'CANCELED', 8, 8, NULL, NULL),
(8, 'DUMAGUETE CITY', 'BACOLOD CITY', '[{\"id\":105,\"name\":\"JACK\",\"code\":\"JA009\",\"price\":\"2400.00\"}]', '', '2025-03-28', '2025-03-20 06:28:04', 'APPROVED', 2, 8, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_fingerprint`
--

CREATE TABLE `device_fingerprint` (
  `deviceID` int(255) NOT NULL,
  `fingerprint_hash` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `screen_resolution` varchar(50) DEFAULT NULL,
  `os_info` varchar(255) DEFAULT NULL,
  `browser_info` varchar(255) DEFAULT NULL,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employeeID` int(11) NOT NULL,
  `nameEmployee` varchar(255) NOT NULL,
  `usernameEmployee` varchar(50) NOT NULL,
  `passwordEmployee` varchar(255) NOT NULL,
  `dateStart` date NOT NULL,
  `dateEnd` date DEFAULT NULL,
  `employedEmployee` tinyint(1) DEFAULT 1,
  `locationEmployee` enum('BACOLOD CITY','DUMAGUETE CITY','ILOILO CITY','SAN CARLOS CITY','CEBU CITY') NOT NULL,
  `positionEmployee` enum('ADMIN','CASHIER','COMPUTER','INVENTORY','SALES','SUPERADMIN') NOT NULL,
  `createdAt` timestamp NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lastLogin` datetime DEFAULT NULL,
  `session_token` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employeeID`, `nameEmployee`, `usernameEmployee`, `passwordEmployee`, `dateStart`, `dateEnd`, `employedEmployee`, `locationEmployee`, `positionEmployee`, `createdAt`, `updatedAt`, `lastLogin`, `session_token`) VALUES
(1, 'Test', 'test', '$2y$10$bUrrCy1CPdpF76tzN6XME.g7elCTkr6Pzx334NLb2xCMVGCXvRDZq', '2025-02-12', NULL, 1, 'BACOLOD CITY', 'SUPERADMIN', '2025-03-05 03:49:09', '2025-03-21 03:27:57', '2025-03-21 11:27:27', ''),
(2, 'duma', 'duma', '$2y$10$EiKvbHb/utcdW9TkHqt6y.u4YdXqV828C8dt3nlgfQE1z3RRoQC.e', '2025-03-05', '2029-05-25', 1, 'DUMAGUETE CITY', 'ADMIN', '2025-03-05 03:50:00', '2025-03-21 03:28:36', '2025-03-21 11:28:03', ''),
(3, 'sales1', 'sales1', '$2y$10$NvDvTpAl0a9ShHg4ygRrSO3bkeRF5fTRE7aoeqsHGuk/LRV.gWZDi', '2025-03-05', NULL, 1, 'BACOLOD CITY', 'SALES', '2025-03-05 07:08:17', '2025-03-06 06:46:30', NULL, ''),
(4, 'cebu', 'cebu', '$2y$10$ApXJI1VpNT5/.P6p6etBzuA1AhcpnEt3C9bZjRWJQaBP5niDigQ3G', '2025-03-06', '2027-12-15', 1, 'CEBU CITY', 'ADMIN', '2025-03-06 07:11:34', '2025-03-06 07:11:34', NULL, ''),
(5, 'cashier', 'cashier', '$2y$10$Whj.Kt/MbC1p9gtN8FTj2OfGQ6UvebAuOLAZI4xqCBQjO/0YSOsTC', '2025-03-07', NULL, 1, 'BACOLOD CITY', 'CASHIER', '2025-03-07 07:34:56', '2025-03-21 03:30:35', '2025-03-21 11:28:43', ''),
(6, 'inventory', 'inven', '$2y$10$/4o9twW/Yyw90U/XEivP.uY3SOMOdyedx1jp0fG5MI0qmhCC5V0yi', '2025-03-07', NULL, 1, 'BACOLOD CITY', 'INVENTORY', '2025-03-07 07:50:35', '2025-03-07 07:50:35', NULL, ''),
(7, 'computer', 'com', '8dec8c89c4b8b4a9e49029bf334104b0:f7c18295c3b8e40f767a7f8727b99b3e6827b6a3175222f8e05fe6228f194e9342b986891485b4faa57f265e739bf0fc84dbb0afdeb7ed7ab7dab91e34e99c55', '2025-03-10', NULL, 1, 'BACOLOD CITY', 'COMPUTER', '2025-03-11 06:57:31', '2025-03-20 03:07:51', NULL, ''),
(8, 'sadmin', 'sadmin', '$2y$10$KtXBDoDzZWJJTYja.cLvs.pzFfuTmhzMRw7PkG6mCGRCPckkfsuRm', '2025-03-12', NULL, 1, 'BACOLOD CITY', 'SUPERADMIN', '2025-03-12 03:07:04', '2025-03-21 03:24:07', '2025-03-21 11:24:07', '533ce29e83e529f6492405b678a3cb5621eb4cd0f15d85757879138cf4fe0c58'),
(11, 'rtest', 'rtest', '9828709ffbbd5790d819d5661490a083:483ce1f799a57f211c913d6b713b78b4a2bf3bfc3673c5fcb905eb2ba792ba6e03cfe2ed575b7aee67f0ca1df55302caec890ee23315be5fc5e2e71e85ee3f5c', '2025-03-20', '0000-00-00', 1, 'BACOLOD CITY', 'SUPERADMIN', '2025-03-20 02:08:14', '2025-03-20 04:04:39', '2025-03-20 04:04:39', ''),
(14, 'refreshtest', 'refresh2', '$2y$10$ajGz3mssQzooLp4a7CnrP.KT4zt0wT48.PcXkE3982zu2ufTZePie', '2025-03-20', NULL, 1, 'BACOLOD CITY', 'SUPERADMIN', '2025-03-20 05:27:24', '2025-03-21 02:47:54', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `entourage`
--

CREATE TABLE `entourage` (
  `entourageID` int(255) NOT NULL,
  `productID` int(255) DEFAULT NULL,
  `nameEntourage` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `entourage`
--

INSERT INTO `entourage` (`entourageID`, `productID`, `nameEntourage`) VALUES
(34, NULL, 'Lirio'),
(36, NULL, 'Felipe Entourage'),
(37, NULL, 'Aula');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `paymentID` int(255) NOT NULL,
  `transactionID` int(255) NOT NULL,
  `employeeID` int(255) DEFAULT NULL,
  `datePayment` date NOT NULL,
  `kindPayment` varchar(255) NOT NULL,
  `amountPayment` int(255) NOT NULL,
  `paymentCurrentBalance` int(255) DEFAULT NULL,
  `notePayment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`paymentID`, `transactionID`, `employeeID`, `datePayment`, `kindPayment`, `amountPayment`, `paymentCurrentBalance`, `notePayment`) VALUES
(3, 2, 1, '2025-03-05', 'CASH', 3200, NULL, ''),
(4, 2, 1, '2025-03-05', 'CASH', 14600, NULL, ''),
(5, 3, 1, '2025-03-06', 'CASH', 200, NULL, ''),
(14, 4, 1, '2025-03-07', 'CASH', 100, 2900, ''),
(15, 4, 1, '2025-03-07', 'CASH', 900, 2000, ''),
(16, 4, 1, '2025-03-07', 'CASH', 1000, 1000, ''),
(17, 4, 1, '2025-03-07', 'GCASH/E-WALLETS', 1000, 0, 'GCASH RECIEPT: 1231255572723'),
(18, 5, 1, '2025-03-07', 'CASH', 1900, 0, ''),
(19, 6, 1, '2025-03-11', 'CASH', 2000, 5000, ''),
(20, 6, 1, '2025-03-11', 'CASH', 5000, 0, ''),
(21, 7, 8, '2025-03-12', 'DEBIT', 8500, 0, ''),
(25, 9, 8, '2025-03-13', 'CASH', 1, 12, ''),
(27, 9, 8, '2025-03-13', 'CASH', 1, 12, ''),
(28, 9, 8, '2025-03-13', 'CASH', 12, 0, ''),
(29, 10, 8, '2025-03-13', 'CASH', 2000, 0, ''),
(30, 11, 8, '2025-03-13', 'CASH', 1200, 3600, ''),
(31, 11, 8, '2025-03-13', 'CASH', 3600, 0, ''),
(32, 8, 8, '2025-03-13', 'CASH', 5000, 0, ''),
(34, 3, 8, '2025-03-13', 'CASH', 200, 6600, ''),
(39, 3, 8, '2025-03-13', 'CASH', 6600, 0, ''),
(41, 12, 8, '2025-03-13', 'CASH', 500, 4000, ''),
(45, 12, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(46, 13, 8, '2025-03-13', 'CASH', 400, 4000, ''),
(47, 13, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(48, 14, 8, '2025-03-13', 'CASH', 100, 4000, ''),
(49, 14, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(52, 16, 8, '2025-03-13', 'CASH', 100, 4000, ''),
(53, 16, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(54, 17, 8, '2025-03-13', 'CASH', 200, 4000, ''),
(55, 17, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(56, 18, 8, '2025-03-13', 'CASH', 200, 4000, ''),
(57, 18, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(58, 19, 8, '2025-03-13', 'CASH', 200, 4000, ''),
(59, 19, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(62, 20, 8, '2025-03-13', 'CASH', 200, 4000, ''),
(63, 20, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(64, 21, 8, '2025-03-13', 'CASH', 200, 4000, ''),
(65, 21, 8, '2025-03-13', 'CASH', 4000, 0, ''),
(66, 23, 8, '2025-03-13', 'CASH', 2000, 3000, ''),
(67, 23, 8, '2025-03-13', 'CASH', 3000, 0, ''),
(68, 24, 8, '2025-03-13', 'CASH', 10000, 0, ''),
(69, 25, 8, '2025-03-13', 'CASH', 4400, 0, ''),
(72, 28, 8, '2025-03-14', 'CASH', 1000, 13000, ''),
(74, 28, 8, '2025-03-14', 'CASH', 1000, 12000, ''),
(86, 28, 8, '2025-03-14', 'CASH', 1000, 11000, ''),
(88, 28, 8, '2025-03-14', 'CASH', 11000, 0, ''),
(97, 30, 8, '2025-03-14', 'CASH', 499, 4500, ''),
(123, 30, 1, '2025-03-14', 'CASH', 100, 4400, ''),
(126, 30, 1, '2025-03-14', 'CASH', 100, 4100, ''),
(127, 30, 1, '2025-03-14', 'CASH', 100, 4000, ''),
(128, 30, 1, '2025-03-14', 'CASH', 100, 3900, ''),
(129, 30, 1, '2025-03-14', 'CASH', 100, 3800, ''),
(130, 30, 1, '2025-03-14', 'CASH', 100, 3700, ''),
(132, 31, 1, '2025-03-14', 'CASH', 2000, 7000, ''),
(134, 31, 1, '2025-03-14', 'CASH', 1000, 6000, ''),
(135, 31, 1, '2025-03-14', 'CASH', 6000, 0, ''),
(137, 30, 1, '2025-03-14', 'CASH', 3700, 0, ''),
(139, 30, 1, '2025-03-14', 'CASH', 4200, 0, ''),
(140, 30, 1, '2025-03-14', 'CASH', 4200, 0, ''),
(148, 32, 1, '2025-03-14', 'CASH', 1000, 11000, ''),
(149, 32, 1, '2025-03-14', 'CASH', 11000, 0, ''),
(153, 30, 1, '2025-03-15', 'CASH', 4200, 0, ''),
(154, 32, 1, '2025-03-17', 'CASH', 11000, 0, ''),
(155, 32, 1, '2025-03-17', 'CASH', 11000, 0, ''),
(156, 33, 1, '2025-03-17', 'CASH', 6000, 1000, ''),
(157, 33, 1, '2025-03-17', 'CASH', 1000, 0, ''),
(158, 34, 1, '2025-03-17', 'CASH', 2000, 13000, ''),
(159, 34, 1, '2025-03-17', 'CASH', 13000, 0, ''),
(160, 35, 1, '2025-03-17', 'CASH', 1000, 4000, ''),
(161, 35, 1, '2025-03-17', 'CASH', 4000, 0, ''),
(162, 36, 1, '2025-03-17', 'CASH', 300, 2000, ''),
(163, 36, 1, '2025-03-17', 'CASH', 1000, 1000, ''),
(164, 36, 1, '2025-03-17', 'CASH', 100, 900, ''),
(165, 37, 1, '2025-03-18', 'CASH', 1000, 7000, ''),
(166, 37, 1, '2025-03-18', 'CASH', 7000, 0, ''),
(167, 36, 1, '2025-03-18', 'CASH', 300, 600, ''),
(168, 38, 1, '2025-03-18', 'CASH', 200, 35, ''),
(169, 38, 1, '2025-03-19', 'CASH', 35, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `picture`
--

CREATE TABLE `picture` (
  `pictureID` int(11) NOT NULL,
  `productID` int(11) DEFAULT NULL,
  `entourageID` int(11) DEFAULT NULL,
  `pictureLocation` varchar(255) NOT NULL,
  `dateAdded` timestamp NOT NULL DEFAULT current_timestamp(),
  `isPrimary` tinyint(1) NOT NULL DEFAULT 0,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `fileType` varchar(10) DEFAULT NULL,
  `fileSize` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `picture`
--

INSERT INTO `picture` (`pictureID`, `productID`, `entourageID`, `pictureLocation`, `dateAdded`, `isPrimary`, `isActive`, `fileType`, `fileSize`) VALUES
(5, 24, NULL, 'pictures/pic_67c80e71629a29143090.jpeg', '2025-03-05 08:42:25', 0, 1, NULL, NULL),
(6, 25, NULL, 'pictures/pic_67c80ef0c10501926570.jpeg', '2025-03-05 08:44:32', 0, 1, NULL, NULL),
(7, 26, NULL, 'pictures/pic_67c810c1b18733466873.jpeg', '2025-03-05 08:52:17', 0, 1, NULL, NULL),
(8, NULL, 36, 'pictures/pic_67c92fba7d49b7750671.jpg', '2025-03-06 05:17:01', 0, 1, NULL, NULL),
(9, NULL, 36, 'pictures/pic_67c92fba9a51e3614269.jpg', '2025-03-06 05:17:01', 0, 1, NULL, NULL),
(11, 30, NULL, 'pictures/pic_67c93019b855c2897538.jpg', '2025-03-06 05:18:36', 0, 1, NULL, NULL),
(12, 31, NULL, 'pictures/pic_67c9303c70ba91783125.jpg', '2025-03-06 05:19:11', 0, 1, NULL, NULL),
(17, 29, NULL, 'pictures/product_29_67c9322dc4ddd.jpg', '2025-03-06 05:27:28', 0, 1, NULL, NULL),
(18, 29, NULL, 'pictures/product_29_67c9322e010d2.jpg', '2025-03-06 05:27:28', 0, 1, NULL, NULL),
(19, 29, NULL, 'pictures/product_29_67c9322e33016.jpg', '2025-03-06 05:27:28', 0, 1, NULL, NULL),
(20, 29, NULL, 'pictures/product_29_67c9322e4ebbb.jpg', '2025-03-06 05:27:29', 0, 1, NULL, NULL),
(22, NULL, NULL, 'pictures/pic_67c95115d14a33575724.jpg', '2025-03-06 07:39:01', 0, 1, NULL, NULL),
(30, 5, NULL, 'pictures/product_5_67c964ae1dcce.jpeg', '2025-03-06 09:02:37', 0, 1, NULL, NULL),
(31, 6, NULL, 'pictures/product_6_67c964ba0a043.jpeg', '2025-03-06 09:02:49', 0, 1, NULL, NULL),
(32, 1, NULL, 'pictures/product_1_67ce54ef6106c.jpg', '2025-03-10 02:56:45', 0, 1, NULL, NULL),
(33, 34, NULL, 'pictures/pic_67cfc2762b5a14551013.jpg', '2025-03-11 04:56:27', 0, 1, NULL, NULL),
(34, 35, NULL, 'pictures/pic_67cfc2c09ed348594080.jpg', '2025-03-11 04:57:42', 0, 1, NULL, NULL),
(35, 36, NULL, 'pictures/pic_67cfc2ee4e9637141199.jpg', '2025-03-11 04:58:28', 0, 1, NULL, NULL),
(36, 37, NULL, 'pictures/pic_67cfc311782eb2129899.jpg', '2025-03-11 04:59:03', 0, 1, NULL, NULL),
(37, 38, NULL, 'pictures/pic_67cfc385a3c533506164.jpg', '2025-03-11 05:00:59', 0, 1, NULL, NULL),
(38, 39, NULL, 'pictures/pic_67cfc3ad492761711287.jpg', '2025-03-11 05:01:39', 0, 1, NULL, NULL),
(39, 40, NULL, 'pictures/pic_67cfc3d507de09215029.jpg', '2025-03-11 05:02:18', 0, 1, NULL, NULL),
(40, 41, NULL, 'pictures/pic_67cfc4146cc4d7243979.jpg', '2025-03-11 05:03:22', 0, 1, NULL, NULL),
(46, 48, NULL, 'pictures/pic_67cfd686674732891165.jpg', '2025-03-11 06:22:04', 0, 1, NULL, NULL),
(47, 50, NULL, 'pictures/pic_67cfd79c4c7816684384.jpg', '2025-03-11 06:26:42', 0, 1, NULL, NULL),
(48, 51, NULL, 'pictures/pic_67cfd97ae4e384562822.jpg', '2025-03-11 06:34:40', 0, 1, NULL, NULL),
(49, 52, NULL, 'pictures/pic_67cfda598040f6719382.jpg', '2025-03-11 06:38:23', 0, 1, NULL, NULL),
(50, 53, NULL, 'pictures/pic_67cfda846db829141565.jpg', '2025-03-11 06:39:06', 0, 1, NULL, NULL),
(51, 54, NULL, 'pictures/pic_67cfdcc6948d55293221.jpg', '2025-03-11 06:48:44', 0, 1, NULL, NULL),
(52, 59, NULL, 'pictures/pic_67cfe2368a2f88135341.jpg', '2025-03-11 07:11:56', 0, 1, NULL, NULL),
(53, 61, NULL, 'pictures/pic_67cfe30c090487826514.jpg', '2025-03-11 07:15:29', 0, 1, NULL, NULL),
(54, 62, NULL, 'pictures/pic_67cfe30c090487826514.jpg', '2025-03-11 07:15:30', 0, 1, NULL, NULL),
(55, 63, NULL, 'pictures/pic_67cfe73e118522396515.jpeg', '2025-03-11 07:33:23', 0, 1, NULL, NULL),
(56, 64, NULL, 'pictures/pic_67cfe73e118522396515.jpeg', '2025-03-11 07:33:24', 0, 1, NULL, NULL),
(57, 66, NULL, 'pictures/pic_67cff2ceda1fa3152362.jpg', '2025-03-11 08:22:44', 0, 1, NULL, NULL),
(58, 68, NULL, 'pictures/pic_67cff79d5404a9844996.jpeg', '2025-03-11 08:43:15', 0, 1, NULL, NULL),
(59, 69, NULL, 'pictures/pic_67cff79d5404a9844996.jpeg', '2025-03-11 08:43:15', 0, 1, NULL, NULL),
(60, 70, NULL, 'pictures/pic_67cff79d5404a9844996.jpeg', '2025-03-11 08:43:15', 0, 1, NULL, NULL),
(61, 71, NULL, 'pictures/pic_67cff79d5404a9844996.jpeg', '2025-03-11 08:43:16', 0, 1, NULL, NULL),
(62, 72, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:34', 0, 1, NULL, NULL),
(63, 73, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:35', 0, 1, NULL, NULL),
(64, 74, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:35', 0, 1, NULL, NULL),
(65, 75, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:36', 0, 1, NULL, NULL),
(66, 76, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:36', 0, 1, NULL, NULL),
(67, 77, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:37', 0, 1, NULL, NULL),
(68, 78, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:37', 0, 1, NULL, NULL),
(69, 79, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:38', 0, 1, NULL, NULL),
(70, 80, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:38', 0, 1, NULL, NULL),
(71, 81, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:39', 0, 1, NULL, NULL),
(72, 82, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:39', 0, 1, NULL, NULL),
(73, 83, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:40', 0, 1, NULL, NULL),
(74, 84, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:40', 0, 1, NULL, NULL),
(75, 85, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:41', 0, 1, NULL, NULL),
(76, 86, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:41', 0, 1, NULL, NULL),
(77, 87, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:42', 0, 1, NULL, NULL),
(78, 88, NULL, 'pictures/pic_67d0e6835ffff5468502.jpeg', '2025-03-12 01:42:42', 0, 1, NULL, NULL),
(85, 97, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:20', 0, 1, NULL, NULL),
(86, 98, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:21', 0, 1, NULL, NULL),
(87, 99, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:21', 0, 1, NULL, NULL),
(88, 100, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:22', 0, 1, NULL, NULL),
(89, 101, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:22', 0, 1, NULL, NULL),
(90, 102, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:22', 0, 1, NULL, NULL),
(91, 103, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:23', 0, 1, NULL, NULL),
(92, 104, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:23', 0, 1, NULL, NULL),
(93, 105, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:24', 0, 1, NULL, NULL),
(94, 106, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:24', 0, 1, NULL, NULL),
(95, 107, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:25', 0, 1, NULL, NULL),
(96, 108, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:25', 0, 1, NULL, NULL),
(97, 109, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:26', 0, 1, NULL, NULL),
(98, 110, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:27', 0, 1, NULL, NULL),
(99, 111, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:27', 0, 1, NULL, NULL),
(100, 112, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:28', 0, 1, NULL, NULL),
(101, 113, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:28', 0, 1, NULL, NULL),
(102, 114, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:29', 0, 1, NULL, NULL),
(103, 115, NULL, 'pictures/pic_67d0edb952d5f2831221.jpeg', '2025-03-12 02:13:29', 0, 1, NULL, NULL),
(104, 116, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:01', 0, 1, NULL, NULL),
(105, 117, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:02', 0, 1, NULL, NULL),
(106, 118, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:02', 0, 1, NULL, NULL),
(107, 119, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:02', 0, 1, NULL, NULL),
(108, 120, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:03', 0, 1, NULL, NULL),
(109, 121, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:03', 0, 1, NULL, NULL),
(110, 122, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:03', 0, 1, NULL, NULL),
(111, 123, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:04', 0, 1, NULL, NULL),
(112, 124, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:04', 0, 1, NULL, NULL),
(113, 125, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-12 08:55:04', 0, 1, NULL, NULL),
(114, 126, NULL, 'pictures/pic_67d3927e7bee26531968.jpg', '2025-03-14 02:20:47', 0, 1, NULL, NULL),
(115, 127, NULL, 'pictures/pic_67d3929dded0a5678057.jpg', '2025-03-14 02:21:18', 0, 1, NULL, NULL),
(116, 128, NULL, 'pictures/pic_67d392d7df3831818943.jpg', '2025-03-14 02:22:16', 0, 1, NULL, NULL),
(117, 147, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-14 07:29:03', 0, 1, NULL, NULL),
(118, 148, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-14 07:30:57', 0, 1, NULL, NULL),
(119, 149, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-14 07:35:16', 0, 1, NULL, NULL),
(120, 150, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-14 07:36:19', 0, 1, NULL, NULL),
(121, 151, NULL, 'pictures/pic_67d14bde6cc6c9533377.jpg', '2025-03-14 07:37:48', 0, 1, NULL, NULL),
(122, 152, NULL, 'pictures/pic_67d779cb0769e2873582.jpg', '2025-03-17 01:24:27', 0, 1, NULL, NULL),
(123, 153, NULL, 'pictures/pic_67d77f2501a406345463.jpg', '2025-03-17 01:47:17', 0, 1, NULL, NULL),
(124, 154, NULL, 'pictures/pic_67d790dc6921f9035622.jpeg', '2025-03-17 03:02:52', 0, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `productID` int(11) NOT NULL,
  `entourageID` int(11) DEFAULT NULL,
  `categoryID` int(11) DEFAULT NULL,
  `locationProduct` enum('BACOLOD CITY','DUMAGUETE CITY','ILOILO CITY','SAN CARLOS CITY','CEBU CITY') NOT NULL DEFAULT 'BACOLOD CITY',
  `typeProduct` varchar(10) DEFAULT NULL,
  `nameProduct` varchar(100) DEFAULT NULL,
  `bustProduct` varchar(20) DEFAULT NULL,
  `waistProduct` varchar(20) DEFAULT NULL,
  `lengthProduct` varchar(20) DEFAULT NULL,
  `sizeProduct` varchar(20) DEFAULT NULL,
  `colorProduct` varchar(50) DEFAULT NULL,
  `descProduct` text DEFAULT NULL,
  `priceProduct` decimal(10,2) NOT NULL,
  `priceSold` decimal(10,2) DEFAULT NULL,
  `genderProduct` enum('MENSWEAR','WOMENSWEAR','BOYS','GIRLS','WEDDING','ACCESSORIES') NOT NULL,
  `damageProduct` tinyint(1) NOT NULL DEFAULT 0,
  `returnedProduct` tinyint(1) NOT NULL DEFAULT 0,
  `useProduct` tinyint(1) NOT NULL DEFAULT 0,
  `soldProduct` tinyint(1) NOT NULL DEFAULT 0,
  `codeProduct` varchar(50) DEFAULT NULL,
  `counterProduct` int(11) DEFAULT 0,
  `isNew` tinyint(1) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`productID`, `entourageID`, `categoryID`, `locationProduct`, `typeProduct`, `nameProduct`, `bustProduct`, `waistProduct`, `lengthProduct`, `sizeProduct`, `colorProduct`, `descProduct`, `priceProduct`, `priceSold`, `genderProduct`, `damageProduct`, `returnedProduct`, `useProduct`, `soldProduct`, `codeProduct`, `counterProduct`, `isNew`, `createdAt`, `updatedAt`) VALUES
(1, NULL, 32, 'BACOLOD CITY', 'BG', 'REN', '38', '36', '44', '', 'WHITE', '', 5660.00, NULL, 'WEDDING', 0, 0, 0, 0, 'test2', 1, 0, '2025-03-05 06:28:12', '2025-03-19 02:44:02'),
(5, NULL, 32, 'BACOLOD CITY', 'BG', 'LIEZEL TABE FRANCAA', '34', '32', '40', '', 'WHITE', '', 8000.00, NULL, 'WEDDING', 0, 0, 0, 0, '1', 0, 0, '2025-03-05 07:50:36', '2025-03-19 02:44:19'),
(6, NULL, 32, 'DUMAGUETE CITY', 'BG', 'JESSICA', '38', '32', '44', '', 'WHITE', '', 3000.00, NULL, 'WEDDING', 0, 0, 0, 0, 'mymtmjt', 0, 0, '2025-03-05 07:52:11', '2025-03-19 02:44:24'),
(24, NULL, 32, 'DUMAGUETE CITY', 'BG', 'DONA PIDOR', '38', '36', '42', '', 'WHITE', NULL, 5255.00, NULL, 'WOMENSWEAR', 0, 0, 0, 0, NULL, 0, 0, '2025-03-05 08:42:25', '2025-03-11 02:30:48'),
(25, NULL, 32, 'BACOLOD CITY', 'BG', 'JANICE', '34', '30', '48', '', 'WHITE', NULL, 5899.00, NULL, 'WOMENSWEAR', 0, 0, 0, 0, NULL, 0, 0, '2025-03-05 08:44:32', '2025-03-05 08:44:32'),
(26, NULL, 29, 'DUMAGUETE CITY', 'S', 'MARTIN', NULL, NULL, NULL, '46', 'GRAY', 'tear in the left sleeve', 3200.00, NULL, 'MENSWEAR', 1, 0, 0, 0, 'code2', 1, 0, '2025-03-05 08:52:17', '2025-03-19 02:44:09'),
(27, NULL, 30, 'BACOLOD CITY', 'BRM', 'NEW PRODUCT', NULL, NULL, NULL, '', 'White', 'blabla', 6200.00, NULL, '', 0, 0, 0, 0, '4124', 0, 0, '2025-03-05 09:05:28', '2025-03-06 08:31:55'),
(28, NULL, 29, 'DUMAGUETE CITY', 'S', 'MILLARO', NULL, NULL, NULL, '', 'Red', '', 7100.00, NULL, '', 0, 0, 0, 0, NULL, 0, 0, '2025-03-06 05:04:36', '2025-03-07 02:54:39'),
(29, 36, 44, 'DUMAGUETE CITY', 'BM', 'Felipe BM2', '30', '30', '30', '', 'gold', '', 2000.00, NULL, '', 0, 0, 0, 0, '123', 0, 0, '2025-03-06 05:17:49', '2025-03-11 02:49:16'),
(30, 36, 44, 'DUMAGUETE CITY', 'BM', 'Felipe BM', '30', '30', '30', '', 'gold', 'tear sleeve', 2000.00, NULL, 'WEDDING', 1, 0, 0, 0, '123321', 1, NULL, '2025-03-06 05:18:36', '2025-03-11 02:49:11'),
(31, 36, 28, 'BACOLOD CITY', 'MD', 'Felipe MD', '40', '40', '40', '', 'gold', NULL, 2000.00, NULL, 'WEDDING', 0, 0, 0, 0, '12345', 1, NULL, '2025-03-06 05:19:11', '2025-03-11 05:16:42'),
(33, NULL, 29, 'BACOLOD CITY', 'S', 'MILLARO SUIT', NULL, NULL, NULL, '23', '', '', 6000.00, NULL, '', 0, 0, 0, 0, NULL, 0, 1, '2025-03-11 02:39:58', '2025-03-11 03:43:22'),
(34, NULL, 29, 'BACOLOD CITY', 'S', 'BLACK WITH BLACK SHINY LAPEL SUIT (ALJUR).	', NULL, NULL, NULL, '52', 'Black', NULL, 5000.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'al001', 0, NULL, '2025-03-11 04:56:27', '2025-03-11 04:56:27'),
(35, NULL, 29, 'BACOLOD CITY', 'S', 'Aljur', NULL, NULL, NULL, '52', 'Black', 'BLACK WITH BLACK SHINY LAPEL SUIT (ALJUR).	', 5000.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'al002', 0, NULL, '2025-03-11 04:57:42', '2025-03-11 04:57:42'),
(36, NULL, 29, 'SAN CARLOS CITY', 'S', 'Aljur', NULL, NULL, NULL, '54', 'Black', 'BLACK WITH BLACK SHINY LAPEL SUIT (ALJUR).	', 5000.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'al003', 0, NULL, '2025-03-11 04:58:27', '2025-03-11 04:58:27'),
(37, NULL, 29, 'SAN CARLOS CITY', 'S', 'Aljur', NULL, NULL, NULL, '56', 'Black', 'BLACK WITH BLACK SHINY LAPEL SUIT (ALJUR).	', 5000.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'al004', 0, NULL, '2025-03-11 04:59:03', '2025-03-11 04:59:03'),
(38, NULL, 29, 'BACOLOD CITY', 'S', 'Arden', NULL, NULL, NULL, '44', 'Royal Blue', NULL, 5000.00, 5000.00, 'MENSWEAR', 0, 1, 0, 1, 'ar004', 0, NULL, '2025-03-11 05:00:59', '2025-03-14 07:05:06'),
(39, NULL, 29, 'BACOLOD CITY', 'S', 'Arden', NULL, NULL, NULL, '46', 'Royal Blue', NULL, 5000.00, 5000.00, 'MENSWEAR', 0, 0, 0, 0, 'ar005', 1, NULL, '2025-03-11 05:01:38', '2025-03-17 05:41:12'),
(40, NULL, 29, 'BACOLOD CITY', 'S', 'Arden', NULL, NULL, NULL, '46', 'Royal Blue', NULL, 5000.00, 5000.00, 'MENSWEAR', 0, 1, 0, 1, 'ar006', 0, NULL, '2025-03-11 05:02:18', '2025-03-14 07:05:51'),
(41, NULL, 29, 'BACOLOD CITY', 'S', 'Arden', NULL, NULL, NULL, '50', 'Royal Blue', NULL, 5000.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'ar016', 0, NULL, '2025-03-11 05:03:22', '2025-03-11 05:03:22'),
(48, NULL, 29, 'BACOLOD CITY', 'S', 'RED', NULL, NULL, NULL, NULL, 'White', NULL, 0.00, NULL, 'MENSWEAR', 0, 0, 0, 0, NULL, 0, NULL, '2025-03-11 06:22:03', '2025-03-11 06:22:03'),
(49, NULL, 34, 'BACOLOD CITY', 'SB', 'ds', NULL, NULL, NULL, NULL, '434', NULL, 0.00, NULL, 'BOYS', 0, 0, 0, 0, NULL, 0, NULL, '2025-03-11 06:23:40', '2025-03-11 06:23:40'),
(50, NULL, 29, 'BACOLOD CITY', 'S', 'dsds', NULL, NULL, NULL, NULL, 'sdsdsds', NULL, 0.00, NULL, 'MENSWEAR', 0, 0, 0, 0, NULL, 0, NULL, '2025-03-11 06:26:41', '2025-03-11 06:26:41'),
(51, NULL, 29, 'BACOLOD CITY', 'S', 'dsddsd', NULL, NULL, NULL, NULL, 'White', NULL, 0.00, NULL, 'MENSWEAR', 0, 0, 0, 0, NULL, 0, NULL, '2025-03-11 06:34:39', '2025-03-11 06:34:39'),
(52, NULL, 29, 'BACOLOD CITY', 'S', 'Hendrix', NULL, NULL, NULL, '48', 'Off White With Black', 'OFFWHITE WITH BLACK LAPEL SUIT (HENDRIX).	', 4000.00, 8000.00, 'MENSWEAR', 0, 1, 0, 1, 'hd001', 0, NULL, '2025-03-11 06:38:23', '2025-03-17 04:34:14'),
(53, NULL, 29, 'BACOLOD CITY', 'S', 'Hendrix', NULL, NULL, NULL, '48', 'Off White With Black', NULL, 4000.00, 8000.00, 'MENSWEAR', 0, 1, 0, 1, 'hd002', 0, NULL, '2025-03-11 06:39:06', '2025-03-17 04:34:29'),
(54, NULL, 26, 'BACOLOD CITY', 'MOH', 'try', NULL, NULL, NULL, NULL, 'sdsds', NULL, 0.00, NULL, 'WEDDING', 0, 0, 0, 0, NULL, 0, NULL, '2025-03-11 06:48:43', '2025-03-11 06:48:43'),
(59, NULL, 29, 'BACOLOD CITY', 'S', 'Ford', NULL, NULL, NULL, '48', 'NAVY BLUE STRIPE', 'NAVY BLUE STRIPE SUIT (FORD).	', 4500.00, 4500.00, 'MENSWEAR', 0, 1, 0, 1, 'fr001', 0, NULL, '2025-03-11 07:11:56', '2025-03-13 05:53:15'),
(60, NULL, 29, 'DUMAGUETE CITY', 'S', 'Ford', NULL, NULL, NULL, '46', 'NAVY BLUE STRIPE', 'NAVY BLUE STRIPE SUIT (FORD).	', 4200.00, 4200.00, 'MENSWEAR', 0, 0, 0, 0, 'fr002', 1, NULL, '2025-03-11 07:11:56', '2025-03-17 04:08:21'),
(61, NULL, 29, 'DUMAGUETE CITY', 'S', 'David', NULL, NULL, NULL, '44', 'White Black', 'WHITE WITH BLACK DESIGN SUIT (DAVID).	', 5000.00, 10000.00, 'MENSWEAR', 0, 1, 0, 1, 'dv002', 0, NULL, '2025-03-11 07:15:29', '2025-03-17 03:51:22'),
(62, NULL, 29, 'BACOLOD CITY', 'S', 'David', NULL, NULL, NULL, '46', 'White Black', 'WHITE WITH BLACK DESIGN SUIT (DAVID).	', 4800.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'dv001', 0, NULL, '2025-03-11 07:15:30', '2025-03-11 07:15:30'),
(63, NULL, 30, 'BACOLOD CITY', 'BRM', 'Aljur', NULL, NULL, NULL, '12', 'sdsdsds', NULL, 12.00, NULL, 'MENSWEAR', 0, 0, 0, 0, '321', 0, NULL, '2025-03-11 07:33:23', '2025-03-11 07:33:23'),
(64, NULL, 30, 'BACOLOD CITY', 'BRM', 'Aljur', NULL, NULL, NULL, '13', 'sdsdsds', NULL, 13.00, NULL, 'MENSWEAR', 0, 0, 0, 0, '322', 0, NULL, '2025-03-11 07:33:24', '2025-03-11 07:33:24'),
(66, NULL, 29, 'BACOLOD CITY', 'S', 'calf', NULL, NULL, NULL, '', '123123123', NULL, 0.00, NULL, 'MENSWEAR', 0, 0, 0, 0, NULL, 0, NULL, '2025-03-11 08:22:44', '2025-03-11 08:22:44'),
(68, NULL, 29, 'BACOLOD CITY', 'S', 'espanto', NULL, NULL, NULL, '12', 'djsdkjsdk', NULL, 321.00, NULL, 'MENSWEAR', 0, 0, 0, 0, '32323232', 0, NULL, '2025-03-11 08:43:15', '2025-03-11 08:43:15'),
(69, NULL, 29, 'DUMAGUETE CITY', 'S', 'espanto', NULL, NULL, NULL, '12', 'djsdkjsdk', NULL, 321.00, NULL, 'MENSWEAR', 0, 0, 0, 0, '323222', 0, NULL, '2025-03-11 08:43:15', '2025-03-18 03:57:33'),
(70, NULL, 29, 'BACOLOD CITY', 'S', 'espanto', NULL, NULL, NULL, '13', 'djsdkjsdk', NULL, 312.00, NULL, 'MENSWEAR', 0, 0, 0, 0, '32232321', 0, NULL, '2025-03-11 08:43:15', '2025-03-11 08:43:15'),
(71, NULL, 29, 'BACOLOD CITY', 'S', 'espanto', NULL, NULL, NULL, '13', 'djsdkjsdk', NULL, 312.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'sdse2312', 0, NULL, '2025-03-11 08:43:16', '2025-03-11 08:43:16'),
(72, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '66', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI001', 0, NULL, '2025-03-12 01:42:34', '2025-03-12 01:42:34'),
(73, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '66', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI002', 0, NULL, '2025-03-12 01:42:35', '2025-03-12 01:42:35'),
(74, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '68', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2200.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI003', 0, NULL, '2025-03-12 01:42:35', '2025-03-12 01:42:35'),
(75, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '68', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2200.00, 2200.00, 'BOYS', 0, 1, 0, 1, 'JI004', 0, NULL, '2025-03-12 01:42:36', '2025-03-13 05:58:28'),
(76, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '68', 'MATTE BROWN, BLACK COLLAR, VELVET ', NULL, 2200.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI005', 1, NULL, '2025-03-12 01:42:36', '2025-03-13 06:05:51'),
(77, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '68', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2200.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI006', 0, NULL, '2025-03-12 01:42:36', '2025-03-12 01:42:36'),
(78, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '70', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI007', 0, NULL, '2025-03-12 01:42:37', '2025-03-12 01:42:37'),
(79, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '70', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI008', 0, NULL, '2025-03-12 01:42:37', '2025-03-12 01:42:37'),
(80, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '70', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI009', 0, NULL, '2025-03-12 01:42:38', '2025-03-12 01:42:38'),
(81, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '70', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI10', 0, NULL, '2025-03-12 01:42:38', '2025-03-12 01:42:38'),
(82, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '72', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2600.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI11', 0, NULL, '2025-03-12 01:42:39', '2025-03-12 01:42:39'),
(83, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '72', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2600.00, 5400.00, 'BOYS', 0, 1, 0, 1, 'JI12', 0, NULL, '2025-03-12 01:42:39', '2025-03-17 04:21:21'),
(84, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '72', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2600.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI13', 1, NULL, '2025-03-12 01:42:40', '2025-03-17 04:21:45'),
(85, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '72', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2600.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI14', 0, NULL, '2025-03-12 01:42:40', '2025-03-12 01:42:40'),
(86, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '74', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2800.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI15', 0, NULL, '2025-03-12 01:42:41', '2025-03-12 01:42:41'),
(87, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '74', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2800.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI16', 0, NULL, '2025-03-12 01:42:41', '2025-03-12 01:42:41'),
(88, NULL, 34, 'BACOLOD CITY', 'SB', 'Jairus', NULL, NULL, NULL, '74', 'MATTE BROWN, BLACK COLLAR, VELVET ', 'SMALL MATTE BROWN WITH BLACK COLLAR VELVET SUIT (JAIRUS)	', 2800.00, NULL, 'BOYS', 0, 0, 0, 0, 'JI17', 0, NULL, '2025-03-12 01:42:42', '2025-03-12 01:42:42'),
(97, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '66', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA001', 0, NULL, '2025-03-12 02:13:20', '2025-03-12 02:13:20'),
(98, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '66', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA002', 0, NULL, '2025-03-12 02:13:21', '2025-03-12 02:13:21'),
(99, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '66', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA003', 0, NULL, '2025-03-12 02:13:21', '2025-03-12 02:13:21'),
(100, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '66', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'broken', 2000.00, NULL, 'BOYS', 1, 0, 0, 0, 'JA004', 2, NULL, '2025-03-12 02:13:21', '2025-03-13 03:02:09'),
(101, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '68', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2200.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA005', 0, NULL, '2025-03-12 02:13:22', '2025-03-12 02:13:22'),
(102, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '68', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2200.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA006', 0, NULL, '2025-03-12 02:13:22', '2025-03-12 02:13:22'),
(103, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '68', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', NULL, 2200.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA007', 5, NULL, '2025-03-12 02:13:23', '2025-03-17 04:09:00'),
(104, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '68', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', NULL, 2200.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA008', 5, NULL, '2025-03-12 02:13:23', '2025-03-17 04:08:53'),
(105, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '70', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA009', 0, NULL, '2025-03-12 02:13:24', '2025-03-12 02:13:24'),
(106, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '70', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA010', 0, NULL, '2025-03-12 02:13:24', '2025-03-12 02:13:24'),
(107, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '70', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA011', 0, NULL, '2025-03-12 02:13:24', '2025-03-12 02:13:24'),
(108, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '70', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA012', 0, NULL, '2025-03-12 02:13:25', '2025-03-12 02:13:25'),
(109, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '72', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2600.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA013', 0, NULL, '2025-03-12 02:13:26', '2025-03-12 02:13:26'),
(110, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '72', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2600.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA014', 0, NULL, '2025-03-12 02:13:26', '2025-03-12 02:13:26'),
(111, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '72', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2600.00, 5000.00, 'BOYS', 0, 0, 0, 0, 'JA015', 0, NULL, '2025-03-12 02:13:27', '2025-03-12 08:34:58'),
(112, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '74', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2800.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA016', 0, NULL, '2025-03-12 02:13:28', '2025-03-12 02:13:28'),
(113, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '74', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2800.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA017', 0, NULL, '2025-03-12 02:13:28', '2025-03-12 02:13:28'),
(114, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '74', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', 'SMALL ROYAL BLUE WITH BLACK COLLAR VELVET SUIT (JACK)	', 2800.00, 5800.00, 'BOYS', 0, 1, 0, 1, 'JA018', 0, NULL, '2025-03-12 02:13:29', '2025-03-12 07:49:02'),
(115, NULL, 34, 'BACOLOD CITY', 'SB', 'JACK', NULL, NULL, NULL, '74', 'ROYAL BLUE WITH BLACK COLLAR VELVET SUIT', NULL, 2800.00, NULL, 'BOYS', 0, 0, 0, 0, 'JA019', 1, NULL, '2025-03-12 02:13:29', '2025-03-12 08:04:18'),
(116, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '15', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'C1', 0, NULL, '2025-03-12 08:55:01', '2025-03-12 08:55:01'),
(117, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '15', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'C2', 0, NULL, '2025-03-12 08:55:02', '2025-03-12 08:55:02'),
(118, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '15', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'C3', 0, NULL, '2025-03-12 08:55:02', '2025-03-12 08:55:02'),
(119, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '15', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'C4', 0, NULL, '2025-03-12 08:55:02', '2025-03-12 08:55:02'),
(120, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '15', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2000.00, NULL, 'BOYS', 0, 0, 0, 0, 'C5', 0, NULL, '2025-03-12 08:55:03', '2025-03-12 08:55:03'),
(121, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '17', 'MIDNIGHT BLUE ', NULL, 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'C6', 1, NULL, '2025-03-12 08:55:03', '2025-03-13 03:08:23'),
(122, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '17', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'C7', 0, NULL, '2025-03-12 08:55:03', '2025-03-12 08:55:03'),
(123, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '17', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'C8', 0, NULL, '2025-03-12 08:55:03', '2025-03-12 08:55:03'),
(124, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '17', 'MIDNIGHT BLUE ', NULL, 2400.00, NULL, 'BOYS', 0, 0, 0, 0, 'C9', 1, NULL, '2025-03-12 08:55:04', '2025-03-13 03:08:08'),
(125, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '17', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 2400.00, 9000.00, 'BOYS', 0, 1, 0, 1, 'C10', 0, NULL, '2025-03-12 08:55:04', '2025-03-17 03:51:59'),
(126, NULL, 29, 'DUMAGUETE CITY', 'S', 'Stefan', NULL, NULL, NULL, '25', 'burgundy', NULL, 2355.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'STF1', 0, NULL, '2025-03-14 02:20:47', '2025-03-18 04:06:07'),
(127, NULL, 29, 'BACOLOD CITY', 'S', 'Markie', NULL, NULL, NULL, '26', 'Maroon', NULL, 2455.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'MRK1', 1, NULL, '2025-03-14 02:21:18', '2025-03-17 04:14:36'),
(128, NULL, 29, 'BACOLOD CITY', 'S', 'Casper', NULL, NULL, NULL, '32', 'Grey', NULL, 2544.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'CSPR1', 1, NULL, '2025-03-14 02:22:16', '2025-03-17 04:15:36'),
(129, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, 'new variation', 'N/A', NULL, 12331.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, '123312312312312', 0, NULL, '2025-03-14 06:47:37', '2025-03-14 06:47:37'),
(132, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, '9999', 'N/A', NULL, 99999999.99, NULL, 'ACCESSORIES', 0, 0, 0, 0, '312asdlkj', 0, NULL, '2025-03-14 06:54:29', '2025-03-14 06:54:29'),
(133, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, 'gonna be big', 'N/A', NULL, 123.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, '3212221131', 0, NULL, '2025-03-14 06:55:51', '2025-03-14 06:55:51'),
(134, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, '312312', 'N/A', NULL, 99999999.99, NULL, 'ACCESSORIES', 0, 0, 0, 0, '31231231212312312312312312', 0, NULL, '2025-03-14 06:57:24', '2025-03-14 06:57:24'),
(136, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, '123', 'N/A', NULL, 99999999.99, NULL, 'ACCESSORIES', 0, 0, 0, 0, 'sdsfs233423', 0, NULL, '2025-03-14 06:58:58', '2025-03-14 06:58:58'),
(140, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, '432423423', 'N/A', NULL, 42342342.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, '42342342', 0, NULL, '2025-03-14 07:00:48', '2025-03-14 07:00:48'),
(141, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, 'newprod', 'N/A', NULL, 123321.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, 'dsffsdfdsfdsfds', 0, NULL, '2025-03-14 07:02:08', '2025-03-14 07:02:08'),
(142, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, 'qwe123', 'N/A', NULL, 321.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, '123312312dfssdsfsd', 0, NULL, '2025-03-14 07:02:54', '2025-03-14 07:02:54'),
(143, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, 'size size1', 'N/A', NULL, 12312312.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, '21324\';\';jh', 0, NULL, '2025-03-14 07:03:39', '2025-03-14 07:03:39'),
(144, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, 'newpeorduct pleas we', 'N/A', NULL, 123321.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, 'dsadsdas', 0, NULL, '2025-03-14 07:07:17', '2025-03-14 07:07:17'),
(145, NULL, NULL, 'BACOLOD CITY', 'VAR', 'Variation Product', NULL, NULL, NULL, 'merge try if ggagana', 'N/A', NULL, 1232312.00, NULL, 'ACCESSORIES', 0, 0, 0, 0, 'sfdsfds', 0, NULL, '2025-03-14 07:14:26', '2025-03-14 07:14:26'),
(146, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, 'copy existing data t', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 321123.00, NULL, 'BOYS', 0, 0, 0, 0, '321123312123dssd', 0, NULL, '2025-03-14 07:26:01', '2025-03-14 07:26:01'),
(147, NULL, 34, 'DUMAGUETE CITY', 'SB', 'MARVIN', NULL, NULL, NULL, 'copy also image try', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 32100000.00, NULL, 'BOYS', 0, 0, 0, 0, '123fdsfsre', 0, NULL, '2025-03-14 07:29:02', '2025-03-18 04:06:03'),
(148, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, 'abcdefghijklmnop', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 12312312.00, NULL, 'BOYS', 0, 0, 0, 0, '1231', 0, NULL, '2025-03-14 07:30:56', '2025-03-14 07:30:56'),
(149, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '1aaq', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 13.00, 12000.00, 'BOYS', 0, 1, 0, 1, '31//.', 0, NULL, '2025-03-14 07:35:15', '2025-03-17 02:53:46'),
(150, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '1sizesmall', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 123.00, NULL, 'BOYS', 0, 0, 0, 0, '--00', 0, NULL, '2025-03-14 07:36:19', '2025-03-14 07:36:19'),
(151, NULL, 34, 'BACOLOD CITY', 'SB', 'MARVIN', NULL, NULL, NULL, '12', 'MIDNIGHT BLUE ', 'MIDNIGHT BLUE SMALL COAT (MARVIN)', 321.00, NULL, 'BOYS', 0, 0, 0, 0, '129983', 0, NULL, '2025-03-14 07:37:48', '2025-03-14 07:37:48'),
(152, NULL, 26, 'BACOLOD CITY', 'MOH', 'TEST', '10', '10', '10', '', 'Black', NULL, 2500.00, NULL, 'WEDDING', 0, 0, 0, 0, '278349812', 0, NULL, '2025-03-17 01:24:27', '2025-03-17 01:24:27'),
(153, NULL, 41, 'BACOLOD CITY', 'BRG', 'AWD', NULL, NULL, NULL, '', 'AWD', 'AWD', 3500.00, NULL, 'WEDDING', 0, 0, 0, 0, 'GAHGDABJHDVGADHGJAWDGHhJA', 0, NULL, '2025-03-17 01:47:17', '2025-03-17 01:47:17'),
(154, NULL, 29, 'BACOLOD CITY', 'S', 'TEST23333', NULL, NULL, NULL, '49', 'AWDAWD', NULL, 2300.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'AWDAWDAWDAWDAWDADAWDAWDADAWDAWD', 0, NULL, '2025-03-17 03:02:52', '2025-03-17 03:02:52'),
(155, NULL, 29, 'BACOLOD CITY', 'S', 'TEST2MULTIPLE', NULL, NULL, NULL, '1', 'AWDAWD', NULL, 200.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'AWDAWD', 0, NULL, '2025-03-17 03:03:51', '2025-03-17 03:03:51'),
(156, NULL, 29, 'BACOLOD CITY', 'S', 'TEST2MULTIPLE', NULL, NULL, NULL, '2', 'AWDAWD', NULL, 235.00, NULL, 'MENSWEAR', 0, 0, 0, 0, 'AWDAWDASDAS', 0, NULL, '2025-03-17 03:03:51', '2025-03-17 03:03:51'),
(157, NULL, 45, 'BACOLOD CITY', 'BRB', 'FIRSTUSEBARONG', NULL, NULL, NULL, NULL, NULL, NULL, 8000.00, NULL, 'BOYS', 0, 0, 0, 0, NULL, 0, 1, '2025-03-17 06:12:19', '2025-03-17 06:12:19');

-- --------------------------------------------------------

--
-- Table structure for table `productcategory`
--

CREATE TABLE `productcategory` (
  `categoryID` int(255) NOT NULL,
  `productCategory` varchar(255) NOT NULL,
  `categoryCode` varchar(255) NOT NULL,
  `genderCategory` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productcategory`
--

INSERT INTO `productcategory` (`categoryID`, `productCategory`, `categoryCode`, `genderCategory`) VALUES
(26, 'Maid of Honor', 'MOH', 'WEDDING'),
(27, 'Flower Girl', 'FG', 'GIRLS'),
(28, 'Mothers Dress', 'MD', 'WEDDING'),
(29, 'Suit', 'S', 'MENSWEAR'),
(30, 'Barong for Men', 'BRM', 'MENSWEAR'),
(31, 'Long Gown', 'LG', 'WOMENSWEAR'),
(32, 'Ball Gown', 'BG', 'WOMENSWEAR'),
(33, 'Barong for Women', 'BRW', 'WOMENSWEAR'),
(34, 'Suit for Boys', 'SB', 'BOYS'),
(35, 'Vests', 'VST', 'MENSWEAR'),
(36, 'Long Sleeves', 'LGS', 'MENSWEAR'),
(37, 'Long Sleeve', 'LGSW', 'WOMENSWEAR'),
(38, 'Necktie', 'NCK', 'ACCESSORIES'),
(41, 'Bridal Gown', 'BRG', 'WEDDING'),
(42, 'Entourage', 'EN', 'WEDDING'),
(44, 'Brides Maid', 'BM', 'WEDDING'),
(45, 'Boy\'s Barong', 'BRB', 'BOYS'),
(48, 'Pants for Boys', 'PB', 'BOYS');

-- --------------------------------------------------------

--
-- Table structure for table `productstyle`
--

CREATE TABLE `productstyle` (
  `styleID` int(11) NOT NULL,
  `productID` int(255) DEFAULT NULL,
  `nameStyle` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_history`
--

CREATE TABLE `product_history` (
  `historyID` int(255) NOT NULL,
  `productID` int(255) DEFAULT NULL,
  `transactionID` int(255) DEFAULT NULL,
  `employeeID` int(255) DEFAULT NULL,
  `action_type` enum('RELEASE','RETURN','SOLD') DEFAULT NULL,
  `action_date` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `damage_status` tinyint(1) DEFAULT NULL,
  `damage_description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_history`
--

INSERT INTO `product_history` (`historyID`, `productID`, `transactionID`, `employeeID`, `action_type`, `action_date`, `damage_status`, `damage_description`) VALUES
(36, 1, 2, 1, 'RELEASE', '2025-03-05 17:16:14.416370', NULL, NULL),
(37, 1, 2, 1, 'RETURN', '2025-03-05 17:16:50.005078', 0, NULL),
(38, 26, 4, 1, 'RELEASE', '2025-03-07 10:47:20.210461', NULL, NULL),
(39, 26, 4, 1, 'RETURN', '2025-03-07 10:48:55.192420', 1, 'tear in the left sleeve'),
(40, 31, 5, 1, 'RELEASE', '2025-03-11 10:33:13.932316', NULL, NULL),
(41, 30, 6, 1, 'RELEASE', '2025-03-11 10:44:29.448124', NULL, NULL),
(42, 30, 6, 1, 'RETURN', '2025-03-11 10:45:30.510752', 1, 'tear sleeve'),
(43, 31, 5, 1, 'RELEASE', '2025-03-11 13:05:08.755576', NULL, NULL),
(44, 31, 5, 1, 'RETURN', '2025-03-11 13:16:42.383355', 0, NULL),
(45, 114, 7, 8, 'SOLD', '2025-03-12 15:49:03.059169', NULL, NULL),
(46, 115, 7, 8, 'RELEASE', '2025-03-12 15:49:35.755172', NULL, NULL),
(47, 115, 7, 8, 'RETURN', '2025-03-12 16:04:18.258275', 0, NULL),
(48, 100, 10, 8, 'RELEASE', '2025-03-13 10:55:28.330479', NULL, NULL),
(50, 100, 10, 8, 'RETURN', '2025-03-13 11:02:09.448407', 1, 'broken'),
(51, 121, 11, 8, 'RELEASE', '2025-03-13 11:07:42.749923', NULL, NULL),
(52, 124, 11, 8, 'RELEASE', '2025-03-13 11:07:59.619642', NULL, NULL),
(53, 124, 11, 8, 'RETURN', '2025-03-13 11:08:08.410587', 0, NULL),
(54, 121, 11, 8, 'RETURN', '2025-03-13 11:08:23.470617', 0, NULL),
(55, 59, 12, 8, 'SOLD', '2025-03-13 13:53:15.149704', NULL, NULL),
(56, 75, 13, 8, 'SOLD', '2025-03-13 13:58:28.676785', NULL, NULL),
(57, 76, 13, 8, 'RELEASE', '2025-03-13 14:00:58.496538', NULL, NULL),
(58, 76, 13, 8, 'RETURN', '2025-03-13 14:05:51.399737', 0, NULL),
(59, 104, 14, 8, 'RELEASE', '2025-03-13 14:09:33.778220', NULL, NULL),
(60, 103, 14, 8, 'RELEASE', '2025-03-13 14:09:40.650733', NULL, NULL),
(62, 103, 14, 8, 'RETURN', '2025-03-13 14:14:15.609043', 0, NULL),
(67, 104, 14, 8, 'RETURN', '2025-03-13 14:58:20.403811', 0, NULL),
(68, 103, 16, 8, 'RELEASE', '2025-03-13 15:01:41.153899', NULL, NULL),
(70, 104, 16, 8, 'RELEASE', '2025-03-13 15:07:42.063166', NULL, NULL),
(71, 103, 16, 8, 'RETURN', '2025-03-13 15:08:42.068124', 0, NULL),
(72, 104, 16, 8, 'RETURN', '2025-03-13 15:10:11.451248', 0, NULL),
(73, 103, 17, 8, 'RELEASE', '2025-03-13 15:27:20.138159', NULL, NULL),
(75, 104, 17, 8, 'RELEASE', '2025-03-13 15:31:09.081636', NULL, NULL),
(76, 103, 17, 8, 'RETURN', '2025-03-13 15:39:06.694337', 0, NULL),
(77, 104, 17, 8, 'RETURN', '2025-03-13 15:39:13.014644', 0, NULL),
(78, 103, 18, 8, 'RELEASE', '2025-03-13 15:41:15.245693', NULL, NULL),
(79, 104, 18, 8, 'RELEASE', '2025-03-13 15:41:19.899314', NULL, NULL),
(80, 103, 18, 8, 'RETURN', '2025-03-13 15:41:27.428792', 0, NULL),
(81, 104, 18, 8, 'RETURN', '2025-03-13 15:41:33.004020', 0, NULL),
(82, 103, 19, 8, 'RELEASE', '2025-03-13 15:43:09.262790', NULL, NULL),
(83, 104, 19, 8, 'RELEASE', '2025-03-13 15:43:16.625411', NULL, NULL),
(84, 103, 19, 8, 'RETURN', '2025-03-13 15:43:28.293620', 0, NULL),
(85, 104, 19, 8, 'RETURN', '2025-03-13 15:43:33.810927', 0, NULL),
(86, 103, 20, 8, 'RELEASE', '2025-03-13 16:39:59.806025', NULL, NULL),
(87, 104, 20, 8, 'RELEASE', '2025-03-13 16:40:08.925517', NULL, NULL),
(88, 103, 20, 8, 'RETURN', '2025-03-13 16:46:55.018873', 0, NULL),
(89, 104, 20, 8, 'RETURN', '2025-03-13 16:47:02.627753', 0, NULL),
(90, 103, 21, 8, 'RELEASE', '2025-03-13 16:48:47.059416', NULL, NULL),
(91, 104, 21, 8, 'RELEASE', '2025-03-13 16:48:51.455467', NULL, NULL),
(92, 103, 21, 8, 'RETURN', '2025-03-13 16:48:59.910101', 0, NULL),
(93, 104, 21, 8, 'RETURN', '2025-03-13 16:49:05.019907', 0, NULL),
(95, 40, 23, 8, 'SOLD', '2025-03-13 17:21:25.109633', NULL, NULL),
(97, 38, 24, 8, 'SOLD', '2025-03-13 17:30:05.671582', NULL, NULL),
(98, 39, 24, 8, 'SOLD', '2025-03-13 17:30:11.892391', NULL, NULL),
(109, 149, 32, 8, 'SOLD', '2025-03-17 10:53:46.307886', NULL, NULL),
(110, 127, 30, 8, 'RELEASE', '2025-03-17 10:56:15.807168', NULL, NULL),
(111, 128, 30, 8, 'RELEASE', '2025-03-17 10:56:39.744422', NULL, NULL),
(123, 61, 28, 8, 'SOLD', '2025-03-17 11:51:23.022455', NULL, NULL),
(124, 60, 28, 8, 'RELEASE', '2025-03-17 11:51:38.437372', NULL, NULL),
(125, 125, 31, 8, 'SOLD', '2025-03-17 11:51:59.586412', NULL, NULL),
(126, 104, 25, 8, 'RELEASE', '2025-03-17 11:53:13.545598', NULL, NULL),
(127, 103, 25, 8, 'RELEASE', '2025-03-17 11:53:25.223455', NULL, NULL),
(128, 60, 28, 8, 'RETURN', '2025-03-17 12:08:21.693843', 0, NULL),
(129, 104, 25, 8, 'RETURN', '2025-03-17 12:08:53.119302', 0, NULL),
(130, 103, 25, 8, 'RETURN', '2025-03-17 12:09:00.873156', 0, NULL),
(131, 127, 30, 8, 'RETURN', '2025-03-17 12:14:37.000298', 0, NULL),
(132, 128, 30, 8, 'RETURN', '2025-03-17 12:15:36.323667', 0, NULL),
(133, 84, 33, 8, 'RELEASE', '2025-03-17 12:21:14.497218', NULL, NULL),
(134, 83, 33, 8, 'SOLD', '2025-03-17 12:21:21.278974', NULL, NULL),
(135, 84, 33, 8, 'RETURN', '2025-03-17 12:21:45.179867', 0, NULL),
(136, 52, 34, 8, 'SOLD', '2025-03-17 12:34:15.091829', NULL, NULL),
(137, 53, 34, 8, 'SOLD', '2025-03-17 12:34:29.884420', NULL, NULL),
(138, 39, 35, 8, 'RELEASE', '2025-03-17 13:40:57.268038', NULL, NULL),
(139, 39, 35, 8, 'RETURN', '2025-03-17 13:41:12.218636', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_size_variations`
--

CREATE TABLE `product_size_variations` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `nameProduct` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_size_variations`
--

INSERT INTO `product_size_variations` (`id`, `group_id`, `product_id`, `created_at`, `nameProduct`) VALUES
(1, 1, 68, '2025-03-11 08:43:15', NULL),
(2, 1, 69, '2025-03-11 08:43:15', NULL),
(3, 1, 70, '2025-03-11 08:43:15', NULL),
(4, 1, 71, '2025-03-11 08:43:16', NULL),
(5, 2, 72, '2025-03-12 01:42:34', NULL),
(6, 2, 73, '2025-03-12 01:42:35', NULL),
(7, 2, 74, '2025-03-12 01:42:35', NULL),
(8, 2, 75, '2025-03-12 01:42:36', NULL),
(9, 2, 76, '2025-03-12 01:42:36', NULL),
(10, 2, 77, '2025-03-12 01:42:37', NULL),
(11, 2, 78, '2025-03-12 01:42:37', NULL),
(12, 2, 79, '2025-03-12 01:42:37', NULL),
(13, 2, 80, '2025-03-12 01:42:38', NULL),
(14, 2, 81, '2025-03-12 01:42:38', NULL),
(15, 2, 82, '2025-03-12 01:42:39', NULL),
(16, 2, 83, '2025-03-12 01:42:39', NULL),
(17, 2, 84, '2025-03-12 01:42:40', NULL),
(18, 2, 85, '2025-03-12 01:42:40', NULL),
(19, 2, 86, '2025-03-12 01:42:41', NULL),
(20, 2, 87, '2025-03-12 01:42:41', NULL),
(21, 2, 88, '2025-03-12 01:42:42', NULL),
(26, 3, 97, '2025-03-12 02:13:20', NULL),
(27, 3, 98, '2025-03-12 02:13:21', NULL),
(28, 3, 99, '2025-03-12 02:13:21', NULL),
(29, 3, 100, '2025-03-12 02:13:22', NULL),
(30, 3, 101, '2025-03-12 02:13:22', NULL),
(31, 3, 102, '2025-03-12 02:13:22', NULL),
(32, 3, 103, '2025-03-12 02:13:23', NULL),
(33, 3, 104, '2025-03-12 02:13:23', NULL),
(34, 3, 105, '2025-03-12 02:13:24', NULL),
(35, 3, 106, '2025-03-12 02:13:24', NULL),
(36, 3, 107, '2025-03-12 02:13:25', NULL),
(37, 3, 108, '2025-03-12 02:13:25', NULL),
(38, 3, 109, '2025-03-12 02:13:26', NULL),
(39, 3, 110, '2025-03-12 02:13:27', NULL),
(40, 3, 111, '2025-03-12 02:13:27', NULL),
(41, 3, 112, '2025-03-12 02:13:28', NULL),
(42, 3, 113, '2025-03-12 02:13:28', NULL),
(43, 3, 114, '2025-03-12 02:13:29', NULL),
(44, 3, 115, '2025-03-12 02:13:29', NULL),
(45, 4, 116, '2025-03-12 08:55:01', NULL),
(46, 4, 117, '2025-03-12 08:55:02', NULL),
(47, 4, 118, '2025-03-12 08:55:02', NULL),
(48, 4, 119, '2025-03-12 08:55:02', NULL),
(49, 4, 120, '2025-03-12 08:55:03', NULL),
(50, 4, 121, '2025-03-12 08:55:03', NULL),
(51, 4, 122, '2025-03-12 08:55:03', NULL),
(52, 4, 123, '2025-03-12 08:55:04', NULL),
(53, 4, 124, '2025-03-12 08:55:04', NULL),
(54, 4, 125, '2025-03-12 08:55:04', NULL),
(55, 3, 129, '2025-03-14 06:47:37', NULL),
(56, 3, 132, '2025-03-14 06:54:29', NULL),
(57, 3, 133, '2025-03-14 06:55:51', NULL),
(58, 4, 134, '2025-03-14 06:57:25', NULL),
(59, 4, 136, '2025-03-14 06:58:58', NULL),
(60, 4, 140, '2025-03-14 07:00:49', NULL),
(61, 3, 141, '2025-03-14 07:02:08', NULL),
(62, 3, 142, '2025-03-14 07:02:54', NULL),
(63, 0, 143, '2025-03-14 07:03:39', NULL),
(64, 4, 144, '2025-03-14 07:07:18', NULL),
(65, 4, 145, '2025-03-14 07:14:26', NULL),
(66, 4, 146, '2025-03-14 07:26:01', NULL),
(67, 4, 147, '2025-03-14 07:29:02', NULL),
(68, 4, 148, '2025-03-14 07:30:57', NULL),
(69, 4, 149, '2025-03-14 07:35:15', NULL),
(70, 4, 150, '2025-03-14 07:36:19', NULL),
(71, 4, 151, '2025-03-14 07:37:48', NULL),
(72, 5, 155, '2025-03-17 03:03:51', NULL),
(73, 5, 156, '2025-03-17 03:03:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_size_variations_backup`
--

CREATE TABLE `product_size_variations_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `product_id` int(11) NOT NULL,
  `size` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_size_variations_backup`
--

INSERT INTO `product_size_variations_backup` (`id`, `product_id`, `size`, `price`, `quantity`, `created_at`) VALUES
(15, 48, '12', 3232.00, 2, '2025-03-11 06:22:03'),
(16, 48, '32', 234.00, 3, '2025-03-11 06:22:03'),
(17, 49, '1', 2312332.00, 1, '2025-03-11 06:23:40'),
(18, 49, '2', 32.00, 2, '2025-03-11 06:23:40'),
(19, 50, '32', 12312312.00, 2, '2025-03-11 06:26:41'),
(20, 50, '322', 12332312.00, 3, '2025-03-11 06:26:41'),
(21, 51, '12', 32.00, 2, '2025-03-11 06:34:40'),
(22, 51, '23', 12.00, 2, '2025-03-11 06:34:40'),
(23, 54, '11', 23.00, 2, '2025-03-11 06:48:43'),
(24, 54, '12', 232.00, 2, '2025-03-11 06:48:44');

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `purchaseID` int(255) NOT NULL,
  `productID` int(255) NOT NULL,
  `transactionID` int(255) NOT NULL,
  `dateCreated` timestamp NOT NULL,
  `soldPProduct` tinyint(1) DEFAULT NULL,
  `packagePurchase` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`purchaseID`, `productID`, `transactionID`, `dateCreated`, `soldPProduct`, `packagePurchase`) VALUES
(143, 1, 2, '2025-03-05 09:05:27', NULL, 0),
(144, 5, 2, '2025-03-05 09:05:27', NULL, 0),
(145, 27, 2, '2025-03-05 09:05:28', NULL, 0),
(146, 28, 3, '2025-03-06 13:04:36', NULL, 0),
(147, 26, 4, '2025-03-07 09:46:21', NULL, 0),
(148, 31, 5, '2025-03-07 10:32:57', NULL, 0),
(149, 30, 6, '2025-03-11 10:39:59', NULL, 0),
(150, 33, 6, '2025-03-11 10:39:59', NULL, 0),
(151, 115, 7, '2025-03-12 14:16:49', NULL, 0),
(152, 114, 7, '2025-03-12 14:16:49', 1, 0),
(153, 111, 8, '2025-03-12 16:35:00', 1, 0),
(154, 64, 9, '2025-03-12 17:04:06', NULL, 0),
(155, 100, 10, '2025-03-13 10:49:30', NULL, 0),
(156, 121, 11, '2025-03-13 11:05:53', NULL, 0),
(157, 124, 11, '2025-03-13 11:05:53', NULL, 0),
(158, 59, 12, '2025-03-13 13:33:12', 1, 0),
(159, 75, 13, '2025-03-13 13:54:23', 1, 0),
(160, 76, 13, '2025-03-13 13:54:23', NULL, 0),
(161, 103, 14, '2025-03-13 14:08:37', NULL, 0),
(162, 104, 14, '2025-03-13 14:08:37', NULL, 0),
(165, 103, 16, '2025-03-13 15:00:03', NULL, 0),
(166, 104, 16, '2025-03-13 15:00:03', NULL, 0),
(167, 103, 17, '2025-03-13 15:12:17', NULL, 0),
(168, 104, 17, '2025-03-13 15:12:17', NULL, 0),
(169, 103, 18, '2025-03-13 15:40:05', NULL, 0),
(170, 104, 18, '2025-03-13 15:40:05', NULL, 0),
(171, 103, 19, '2025-03-13 15:42:36', NULL, 0),
(172, 104, 19, '2025-03-13 15:42:36', NULL, 0),
(173, 103, 20, '2025-03-13 15:56:33', NULL, 0),
(174, 104, 20, '2025-03-13 15:56:33', NULL, 0),
(175, 103, 21, '2025-03-13 16:48:03', NULL, 0),
(176, 104, 21, '2025-03-13 16:48:03', NULL, 0),
(178, 40, 23, '2025-03-13 17:11:02', 1, 0),
(179, 39, 24, '2025-03-13 17:23:51', 1, 0),
(180, 38, 24, '2025-03-13 17:23:51', 1, 0),
(181, 103, 25, '2025-03-13 17:31:40', NULL, 0),
(182, 104, 25, '2025-03-13 17:31:40', NULL, 0),
(187, 61, 28, '2025-03-14 10:01:16', 1, 0),
(188, 60, 28, '2025-03-14 10:01:16', NULL, 0),
(190, 127, 30, '2025-03-14 11:40:36', NULL, 0),
(191, 128, 30, '2025-03-14 11:40:36', NULL, 0),
(192, 125, 31, '2025-03-14 17:07:20', 1, 0),
(193, 149, 32, '2025-03-14 17:49:56', 1, 0),
(194, 83, 33, '2025-03-17 12:18:35', 1, 0),
(195, 84, 33, '2025-03-17 12:18:35', NULL, 0),
(196, 53, 34, '2025-03-17 12:24:46', 1, 0),
(197, 52, 34, '2025-03-17 12:24:46', 1, 0),
(198, 39, 35, '2025-03-17 13:33:00', NULL, 0),
(199, 154, 36, '2025-03-17 13:35:41', NULL, 0),
(200, 157, 37, '2025-03-17 14:12:18', NULL, 0),
(201, 156, 38, '2025-03-18 13:24:30', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `transactionID` int(11) NOT NULL,
  `employeeID` int(11) NOT NULL,
  `purchaseID` int(255) DEFAULT NULL,
  `dateTransaction` date NOT NULL,
  `locationTransaction` enum('BACOLOD CITY','DUMAGUETE CITY','ILOILO CITY','SAN CARLOS CITY','CEBU CITY') NOT NULL,
  `clientName` varchar(255) NOT NULL,
  `clientAddress` varchar(255) NOT NULL,
  `clientContact` varchar(20) NOT NULL,
  `datePickUp` date NOT NULL,
  `dateReturn` date NOT NULL,
  `chargeTransaction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `depositTransaction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balanceTransaction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bondTransaction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bondStatus` int(255) NOT NULL DEFAULT 0,
  `discountTransaction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bondBalance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `modeTransaction` varchar(50) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`transactionID`, `employeeID`, `purchaseID`, `dateTransaction`, `locationTransaction`, `clientName`, `clientAddress`, `clientContact`, `datePickUp`, `dateReturn`, `chargeTransaction`, `depositTransaction`, `balanceTransaction`, `bondTransaction`, `bondStatus`, `discountTransaction`, `bondBalance`, `modeTransaction`, `createdAt`, `updatedAt`) VALUES
(2, 3, 143, '2025-03-05', 'BACOLOD CITY', 'Bernard Millaro', 'Home', '98765443212', '2025-03-05', '2025-03-12', 19800.00, 0.00, 0.00, 6000.00, 2, 2000.00, 6000.00, '', '2025-03-05 09:05:27', '2025-03-20 04:22:16'),
(3, 3, 146, '2025-03-06', 'BACOLOD CITY', 'Test Millaro', 'Home', '12323', '2025-03-31', '2025-04-04', 7100.00, 0.00, 0.00, 2000.00, 1, 100.00, 2000.00, '', '2025-03-06 05:04:35', '2025-03-13 05:30:59'),
(4, 3, 147, '2025-03-07', 'BACOLOD CITY', 'Test 2 Millaro', 'House', '12512652', '2025-04-17', '2025-04-24', 3200.00, 0.00, 0.00, 500.00, 2, 200.00, 0.00, '', '2025-03-07 01:46:20', '2025-03-07 02:50:33'),
(5, 3, 148, '2025-03-07', 'BACOLOD CITY', 'test', 'test', 'test', '2025-05-29', '2025-06-12', 2000.00, 0.00, 0.00, 100.00, 1, 100.00, 100.00, '', '2025-03-07 02:32:56', '2025-03-07 02:43:50'),
(6, 3, 149, '2025-03-11', 'BACOLOD CITY', 'Anton', 'House', '1234', '2025-05-09', '2025-05-23', 8000.00, 0.00, 0.00, 200.00, 2, 1000.00, 0.00, '', '2025-03-11 02:39:57', '2025-03-11 02:46:10'),
(7, 3, 151, '2025-03-12', 'BACOLOD CITY', 'Sold Test', 'Road House', '1234', '2025-03-26', '2025-03-31', 8600.00, 0.00, 0.00, 500.00, 2, 100.00, 0.00, '', '2025-03-12 06:16:47', '2025-03-12 08:05:02'),
(8, 3, 153, '2025-03-12', 'BACOLOD CITY', 'SOLD TEST 2', 'HOUSE', '123', '2025-04-07', '2025-04-14', 5000.00, 0.00, 0.00, 0.00, 1, 0.00, 0.00, '', '2025-03-12 08:34:58', '2025-03-13 04:34:49'),
(9, 3, 154, '2025-03-12', 'BACOLOD CITY', 'jam', 'jamvillage', '123', '2025-03-12', '2025-03-15', 13.00, 0.00, 0.00, 500.00, 1, 0.00, 500.00, '', '2025-03-12 09:04:06', '2025-03-13 02:22:05'),
(10, 3, 155, '2025-03-13', 'BACOLOD CITY', 'zero bond rent test', 'address', '123', '2025-06-13', '2025-06-16', 2000.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-13 02:49:28', '2025-03-13 03:02:09'),
(11, 3, 156, '2025-03-13', 'BACOLOD CITY', 'zero bond rent test 2 products', 'address', '123', '2025-05-13', '2025-05-16', 4800.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-13 03:05:51', '2025-03-13 03:08:23'),
(12, 3, 158, '2025-03-13', 'BACOLOD CITY', 'buy2', 'buy2', '123', '2025-04-01', '2025-04-03', 4500.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-13 05:33:10', '2025-03-13 05:53:15'),
(13, 3, 159, '2025-03-13', 'BACOLOD CITY', 'buy3', 'buyandrent1', '1', '2025-03-14', '2025-03-17', 4400.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-13 05:54:21', '2025-03-13 06:06:51'),
(14, 3, 161, '2025-03-13', 'BACOLOD CITY', '2 rent', '2 rent', '2', '2025-03-24', '2025-03-25', 4400.00, 0.00, 0.00, 0.00, 2, 300.00, 0.00, '', '2025-03-13 06:08:35', '2025-03-13 06:58:20'),
(16, 3, 165, '2025-03-13', 'BACOLOD CITY', '2 rent 2', '2 rent 2', '2', '2025-03-27', '2025-03-28', 4400.00, 0.00, 0.00, 500.00, 2, 300.00, 0.00, '', '2025-03-13 07:00:01', '2025-03-13 07:10:33'),
(17, 3, 167, '2025-03-13', 'BACOLOD CITY', '2 rent no bond again', '2 rent no bond again', '2', '2025-03-26', '2025-03-31', 4400.00, 0.00, 0.00, 0.00, 2, 200.00, 0.00, '', '2025-03-13 07:12:16', '2025-03-13 07:39:13'),
(18, 3, 169, '2025-03-13', 'BACOLOD CITY', '2 rent no bond again 2', '2 rent no bond again 2', '2', '2025-03-28', '2025-03-31', 4400.00, 0.00, 0.00, 0.00, 2, 200.00, 0.00, '', '2025-03-13 07:40:03', '2025-03-13 07:41:33'),
(19, 3, 171, '2025-03-13', 'BACOLOD CITY', '2rwb', '2rwb', '2', '2025-04-01', '2025-04-02', 4400.00, 0.00, 0.00, 500.00, 2, 200.00, 0.00, '', '2025-03-13 07:42:34', '2025-03-15 12:48:48'),
(20, 3, 173, '2025-03-13', 'BACOLOD CITY', '2rwb2', '2rwb2', '2', '2025-04-14', '2025-04-15', 4400.00, 0.00, 0.00, 500.00, 2, 200.00, 0.00, '', '2025-03-13 07:56:31', '2025-03-15 12:48:44'),
(21, 3, 175, '2025-03-13', 'BACOLOD CITY', '2rnb3', '2r0b2', '3', '2025-04-29', '2025-04-30', 4400.00, 0.00, 0.00, 0.00, 2, 200.00, 0.00, '', '2025-03-13 08:48:01', '2025-03-15 12:48:29'),
(23, 3, 178, '2025-03-13', 'BACOLOD CITY', 'buy4', 'buy4', '4', '2025-04-24', '2025-04-25', 5000.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-13 09:11:00', '2025-03-13 09:21:25'),
(24, 3, 179, '2025-03-13', 'BACOLOD CITY', 'buy5', 'buy5', '5', '2025-04-23', '2025-04-24', 10000.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-13 09:23:49', '2025-03-13 09:30:12'),
(25, 3, 181, '2025-03-13', 'BACOLOD CITY', '2rnb2', '2rnb4', '4', '2025-04-14', '2025-04-21', 4400.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-13 09:31:38', '2025-03-17 04:09:00'),
(28, 3, 187, '2025-03-14', 'BACOLOD CITY', '1b1rwb', '1b1rb', '1', '2025-05-27', '2025-05-29', 14200.00, 0.00, 0.00, 2000.00, 2, 200.00, 0.00, '', '2025-03-14 02:01:14', '2025-03-17 05:33:37'),
(30, 3, 190, '2025-03-14', 'BACOLOD CITY', 'Test 3 Millaro', 'Test 3 Millaro', '3', '2025-03-15', '2025-03-16', 4999.00, 0.00, 0.00, 5000.00, 2, 500.00, 0.00, '', '2025-03-14 03:40:34', '2025-03-17 05:33:52'),
(31, 3, 192, '2025-03-14', 'BACOLOD CITY', 'BUY7', 'BUY7', '7', '2025-06-25', '2025-06-26', 9000.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-14 09:07:18', '2025-03-17 03:51:59'),
(32, 3, 193, '2025-03-14', 'BACOLOD CITY', 'buy12', 'buy12', '12', '2025-03-29', '2025-03-30', 12000.00, 0.00, 0.00, 0.00, 2, 0.00, 0.00, '', '2025-03-14 09:49:54', '2025-03-17 02:53:46'),
(33, 3, 194, '2025-03-17', 'BACOLOD CITY', '1b1rnr', '1b1rnr', '091651065', '2025-04-03', '2025-04-08', 8000.00, 0.00, 0.00, 0.00, 2, 1000.00, 0.00, '', '2025-03-17 04:18:35', '2025-03-17 04:21:45'),
(34, 3, 196, '2025-03-17', 'BACOLOD CITY', '2bnrnb', '2bnrnb', '2', '2025-03-29', '2025-03-31', 16000.00, 0.00, 0.00, 0.00, 2, 1000.00, 0.00, '', '2025-03-17 04:24:46', '2025-03-17 04:34:30'),
(35, 3, 198, '2025-03-17', 'BACOLOD CITY', '1r', '1 rent', '12345', '2025-03-28', '2025-03-29', 5000.00, 0.00, 0.00, 2000.00, 2, 0.00, 0.00, '', '2025-03-17 05:33:00', '2025-03-17 07:28:59'),
(36, 3, 199, '2025-03-17', 'BACOLOD CITY', 'JAMORA', 'JAMORA', 'JAMORA', '2025-03-17', '2025-03-29', 2300.00, 0.00, 600.00, 500000.00, 0, 0.00, 0.00, '', '2025-03-17 05:35:42', '2025-03-18 02:03:04'),
(37, 3, 200, '2025-03-17', 'BACOLOD CITY', 'firstuse3', 'firstuse3', '0987654321', '2025-06-06', '2025-06-27', 8000.00, 0.00, 0.00, 2000.00, 1, 0.00, 2000.00, '', '2025-03-17 06:12:18', '2025-03-18 02:01:06'),
(38, 3, 201, '2025-03-18', 'BACOLOD CITY', 'AWDAWD', 'AWDAWD', 'AWDADW', '2025-03-14', '2025-03-24', 235.00, 0.00, 0.00, 1000.00, 1, 0.00, 1000.00, '', '2025-03-18 05:24:31', '2025-03-20 04:09:16');

-- --------------------------------------------------------

--
-- Table structure for table `transmittal`
--

CREATE TABLE `transmittal` (
  `transmittalID` int(255) NOT NULL,
  `productID` int(255) NOT NULL,
  `employeeID` int(255) NOT NULL,
  `dateTransmittal` datetime NOT NULL DEFAULT current_timestamp(),
  `fromLocation` varchar(255) NOT NULL,
  `toLocation` varchar(255) NOT NULL,
  `statusTransmittal` enum('PENDING','IN_TRANSIT','DELIVERED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `noteTransmittal` varchar(255) DEFAULT NULL,
  `dateDelivered` datetime DEFAULT NULL,
  `receivedBy` int(255) DEFAULT NULL,
  `requestID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transmittal`
--

INSERT INTO `transmittal` (`transmittalID`, `productID`, `employeeID`, `dateTransmittal`, `fromLocation`, `toLocation`, `statusTransmittal`, `noteTransmittal`, `dateDelivered`, `receivedBy`, `requestID`) VALUES
(16, 6, 1, '2025-03-05 17:20:28', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-05 09:20:57', 2, NULL),
(17, 26, 1, '2025-03-07 10:53:33', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', 'blablabla', '2025-03-07 02:54:32', 2, NULL),
(18, 28, 1, '2025-03-07 10:53:33', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', 'blablabla', '2025-03-07 02:54:38', 2, NULL),
(21, 26, 2, '2025-03-07 11:04:32', 'DUMAGUETE CITY', 'BACOLOD CITY', 'DELIVERED', 'back to you!\n', '2025-03-07 11:04:49', 1, NULL),
(22, 6, 2, '2025-03-10 13:31:38', 'DUMAGUETE CITY', 'BACOLOD CITY', 'DELIVERED', '', '2025-03-10 13:38:33', 1, NULL),
(23, 1, 2, '2025-03-10 13:37:01', 'DUMAGUETE CITY', 'BACOLOD CITY', 'DELIVERED', '', '2025-03-10 14:12:35', 1, NULL),
(24, 26, 1, '2025-03-10 14:17:18', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-10 14:17:35', 2, NULL),
(25, 24, 1, '2025-03-11 10:30:10', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-11 10:30:48', 2, NULL),
(26, 30, 1, '2025-03-11 10:48:11', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-11 10:49:11', 2, NULL),
(27, 29, 1, '2025-03-11 10:48:11', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-11 10:49:16', 2, NULL),
(28, 60, 1, '2025-03-11 15:41:08', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-11 17:44:58', 2, NULL),
(29, 61, 1, '2025-03-11 15:41:08', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-12 09:58:26', 2, NULL),
(30, 69, 1, '2025-03-11 17:12:54', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', '', '2025-03-18 11:57:33', 2, NULL),
(31, 68, 1, '2025-03-11 17:12:54', 'BACOLOD CITY', 'DUMAGUETE CITY', 'PENDING', '', NULL, NULL, NULL),
(32, 69, 1, '2025-03-11 17:12:55', 'BACOLOD CITY', 'DUMAGUETE CITY', 'CANCELLED', '', NULL, NULL, NULL),
(33, 68, 1, '2025-03-11 17:12:55', 'BACOLOD CITY', 'DUMAGUETE CITY', 'CANCELLED', '', NULL, NULL, NULL),
(34, 6, 8, '2025-03-18 04:03:10', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', 'Auto-generated from Branch Request #4', '2025-03-18 12:05:58', 2, 4),
(35, 147, 8, '2025-03-18 04:03:10', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', 'Auto-generated from Branch Request #4', '2025-03-18 12:06:03', 2, 4),
(36, 126, 8, '2025-03-18 04:03:10', 'BACOLOD CITY', 'DUMAGUETE CITY', 'DELIVERED', 'Auto-generated from Branch Request #4', '2025-03-18 12:06:07', 2, 4),
(37, 145, 8, '2025-03-18 05:08:14', 'BACOLOD CITY', 'DUMAGUETE CITY', 'PENDING', 'Auto-generated from Branch Request #5', NULL, NULL, 5),
(38, 147, 2, '2025-03-19 06:05:05', 'DUMAGUETE CITY', 'BACOLOD CITY', 'PENDING', 'Auto-generated from Branch Request #6', NULL, NULL, 6),
(39, 28, 2, '2025-03-19 06:05:05', 'DUMAGUETE CITY', 'BACOLOD CITY', 'PENDING', 'Auto-generated from Branch Request #6', NULL, NULL, 6),
(40, 105, 8, '2025-03-20 06:28:25', 'BACOLOD CITY', 'DUMAGUETE CITY', 'PENDING', 'Auto-generated from Branch Request #8', NULL, NULL, 8);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bond`
--
ALTER TABLE `bond`
  ADD PRIMARY KEY (`bondID`),
  ADD KEY `idx_bond_transaction` (`transactionID`),
  ADD KEY `idx_bond_employee` (`employeeID`);

--
-- Indexes for table `branch_requests`
--
ALTER TABLE `branch_requests`
  ADD PRIMARY KEY (`requestID`),
  ADD KEY `idx_source_branch` (`sourceBranch`),
  ADD KEY `idx_destination_branch` (`destinationBranch`),
  ADD KEY `idx_requested_by` (`requestedBy`);

--
-- Indexes for table `device_fingerprint`
--
ALTER TABLE `device_fingerprint`
  ADD PRIMARY KEY (`deviceID`),
  ADD UNIQUE KEY `fingerprint_hash` (`fingerprint_hash`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employeeID`),
  ADD UNIQUE KEY `usernameEmployee` (`usernameEmployee`);

--
-- Indexes for table `entourage`
--
ALTER TABLE `entourage`
  ADD PRIMARY KEY (`entourageID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`paymentID`),
  ADD KEY `idx_payment_transaction` (`transactionID`),
  ADD KEY `idx_payment_employee` (`employeeID`);

--
-- Indexes for table `picture`
--
ALTER TABLE `picture`
  ADD PRIMARY KEY (`pictureID`),
  ADD KEY `idx_picture_product` (`productID`,`dateAdded`),
  ADD KEY `idx_picture_entourage` (`entourageID`,`dateAdded`),
  ADD KEY `idx_picture_primary` (`productID`,`isPrimary`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`productID`),
  ADD UNIQUE KEY `codeProduct` (`codeProduct`),
  ADD KEY `idx_category` (`categoryID`),
  ADD KEY `idx_location_type` (`locationProduct`,`typeProduct`),
  ADD KEY `idx_code` (`codeProduct`),
  ADD KEY `fk_product_entourage` (`entourageID`);

--
-- Indexes for table `productcategory`
--
ALTER TABLE `productcategory`
  ADD PRIMARY KEY (`categoryID`);

--
-- Indexes for table `productstyle`
--
ALTER TABLE `productstyle`
  ADD PRIMARY KEY (`styleID`);

--
-- Indexes for table `product_history`
--
ALTER TABLE `product_history`
  ADD PRIMARY KEY (`historyID`),
  ADD KEY `idx_history_transaction` (`transactionID`),
  ADD KEY `idx_history_product` (`productID`),
  ADD KEY `idx_history_employee` (`employeeID`);

--
-- Indexes for table `product_size_variations`
--
ALTER TABLE `product_size_variations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`purchaseID`),
  ADD KEY `idx_purchase_product` (`productID`),
  ADD KEY `idx_purchase_transaction` (`transactionID`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`transactionID`),
  ADD KEY `idx_employee` (`employeeID`),
  ADD KEY `idx_dates` (`datePickUp`,`dateReturn`),
  ADD KEY `idx_location` (`locationTransaction`),
  ADD KEY `fk_transaction_purchase` (`purchaseID`);

--
-- Indexes for table `transmittal`
--
ALTER TABLE `transmittal`
  ADD PRIMARY KEY (`transmittalID`),
  ADD KEY `idx_transmittal_product` (`productID`),
  ADD KEY `idx_transmittal_employee` (`employeeID`),
  ADD KEY `idx_transmittal_request` (`requestID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bond`
--
ALTER TABLE `bond`
  MODIFY `bondID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `branch_requests`
--
ALTER TABLE `branch_requests`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `device_fingerprint`
--
ALTER TABLE `device_fingerprint`
  MODIFY `deviceID` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `employeeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `entourage`
--
ALTER TABLE `entourage`
  MODIFY `entourageID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `paymentID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `picture`
--
ALTER TABLE `picture`
  MODIFY `pictureID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `productID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `productcategory`
--
ALTER TABLE `productcategory`
  MODIFY `categoryID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `productstyle`
--
ALTER TABLE `productstyle`
  MODIFY `styleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_history`
--
ALTER TABLE `product_history`
  MODIFY `historyID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `product_size_variations`
--
ALTER TABLE `product_size_variations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `purchaseID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `transactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `transmittal`
--
ALTER TABLE `transmittal`
  MODIFY `transmittalID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bond`
--
ALTER TABLE `bond`
  ADD CONSTRAINT `fk_bond_employee` FOREIGN KEY (`employeeID`) REFERENCES `employee` (`employeeID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bond_transaction` FOREIGN KEY (`transactionID`) REFERENCES `transaction` (`transactionID`) ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_employee` FOREIGN KEY (`employeeID`) REFERENCES `employee` (`employeeID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_transaction` FOREIGN KEY (`transactionID`) REFERENCES `transaction` (`transactionID`) ON UPDATE CASCADE;

--
-- Constraints for table `picture`
--
ALTER TABLE `picture`
  ADD CONSTRAINT `fk_picture_entourage` FOREIGN KEY (`entourageID`) REFERENCES `entourage` (`entourageID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_picture_product` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`) ON DELETE SET NULL;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`categoryID`) REFERENCES `productcategory` (`categoryID`),
  ADD CONSTRAINT `fk_product_entourage` FOREIGN KEY (`entourageID`) REFERENCES `entourage` (`entourageID`);

--
-- Constraints for table `product_history`
--
ALTER TABLE `product_history`
  ADD CONSTRAINT `fk_history_employee` FOREIGN KEY (`employeeID`) REFERENCES `employee` (`employeeID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_product` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_transaction` FOREIGN KEY (`transactionID`) REFERENCES `transaction` (`transactionID`) ON UPDATE CASCADE;

--
-- Constraints for table `product_size_variations`
--
ALTER TABLE `product_size_variations`
  ADD CONSTRAINT `product_size_variations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `fk_purchase_product` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_transaction` FOREIGN KEY (`transactionID`) REFERENCES `transaction` (`transactionID`) ON UPDATE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `fk_transaction_employee` FOREIGN KEY (`employeeID`) REFERENCES `employee` (`employeeID`),
  ADD CONSTRAINT `fk_transaction_purchase` FOREIGN KEY (`purchaseID`) REFERENCES `purchase` (`purchaseID`) ON DELETE SET NULL;

--
-- Constraints for table `transmittal`
--
ALTER TABLE `transmittal`
  ADD CONSTRAINT `fk_transmittal_employee` FOREIGN KEY (`employeeID`) REFERENCES `employee` (`employeeID`),
  ADD CONSTRAINT `fk_transmittal_product` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
