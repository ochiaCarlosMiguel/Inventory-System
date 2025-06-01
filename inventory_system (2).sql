-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2024 at 03:37 AM
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
-- Database: `inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(31, 16, 'Sale Update', '{\"Sale ID\":17,\"Changes\":{\"quantity\":{\"old\":2,\"new\":\"2\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-11-30 00:00:00\",\"new\":\"2024-11-30\"}}}', '2024-11-30 12:30:10'),
(32, 16, 'Sale Update', '{\"Sale ID\":16,\"Changes\":{\"quantity\":{\"old\":1,\"new\":\"2\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-11-30 10:40:36\",\"new\":\"2024-11-30\"}}}', '2024-11-30 12:31:41'),
(33, 21, 'Sale Update', '{\"Sale ID\":6,\"Changes\":{\"quantity\":{\"old\":1,\"new\":\"2\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-11-30 09:47:14\",\"new\":\"2024-11-30\"}}}', '2024-11-30 12:52:11'),
(34, 21, 'Sale Update', '{\"Sale ID\":17,\"Changes\":{\"quantity\":{\"old\":2,\"new\":\"4\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-11-30 00:00:00\",\"new\":\"2024-11-30\"}}}', '2024-11-30 12:52:48'),
(35, 16, 'User Update', 'Updated user ID 16: Username changed from \'CarlosOchia\' to \'CarlosBodonia\', Role changed from \'admin\' to \'Admin\'', '2024-12-02 04:02:10'),
(36, 16, 'Sale Deleted', '{\"Sale ID\":\"6\",\"Product\":\"Batman\",\"Details\":{\"quantity\":\"2\",\"price\":\"\\u20b1250.00\",\"total\":\"\\u20b1500.00\",\"date\":\"2024-11-30 00:00\"}}', '2024-12-02 12:14:36'),
(37, 16, 'Sale Deleted', '{\"Sale ID\":\"16\",\"Product\":\"Batman\",\"Details\":{\"quantity\":\"2\",\"price\":\"\\u20b1250.00\",\"total\":\"\\u20b1500.00\",\"date\":\"2024-11-30 00:00\"}}', '2024-12-02 12:16:46'),
(38, 16, 'Sale Deleted', '{\"Sale ID\":\"17\",\"Product\":\"Batman\",\"Details\":{\"quantity\":\"4\",\"price\":\"\\u20b1250.00\",\"total\":\"\\u20b11,000.00\",\"date\":\"2024-11-30 00:00\"}}', '2024-12-02 12:18:00'),
(39, 16, 'Sale Update', '{\"Sale ID\":18,\"Changes\":{\"quantity\":{\"old\":2,\"new\":\"10\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-11-30 00:00:00\",\"new\":\"2024-12-02\"}}}', '2024-12-02 12:23:07'),
(40, 16, 'Sale Deleted', '{\"Sale ID\":\"18\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:40:14'),
(41, 16, 'Sale Deleted', '{\"Sale ID\":\"18\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:40:22'),
(42, 16, 'SALE_COMPLETED', 'Sale ID: 25, Customer ID: CTM_XICUY, Total: ₱475.00', '2024-12-02 13:42:13'),
(43, 16, 'Sale Deleted', '{\"Sale ID\":\"25\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:42:40'),
(44, 16, 'SALE_COMPLETED', 'Sale ID: 26, Customer ID: CTM_6PITQ, Total: ₱225.00', '2024-12-02 13:43:02'),
(45, 16, 'Sale Deleted', '{\"Sale ID\":\"26\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:43:07'),
(46, 16, 'SALE_COMPLETED', 'Sale ID: 27, Customer ID: CTM_AHYAT, Total: ₱1,000.00', '2024-12-02 13:44:31'),
(47, 16, 'Sale Deleted', '{\"Sale ID\":\"27\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:44:37'),
(48, 16, 'SALE_COMPLETED', 'Sale ID: 28, Customer ID: CTM_HKROA, Total: ₱250.00', '2024-12-02 13:48:00'),
(49, 16, 'Sale Deleted', '{\"Sale ID\":\"28\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:48:16'),
(50, 16, 'SALE_COMPLETED', 'Sale ID: 29, Customer ID: CTM_ZI829, Total: ₱725.00', '2024-12-02 13:48:31'),
(51, 16, 'Sale Deleted', '{\"Sale ID\":\"29\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:48:35'),
(52, 16, 'SALE_COMPLETED', 'Sale ID: 30, Customer ID: CTM_383NO, Total: ₱250.00', '2024-12-02 13:49:04'),
(53, 16, 'SALE_COMPLETED', 'Sale ID: 31, Customer ID: CTM_L8PM4, Total: ₱250.00', '2024-12-02 13:49:15'),
(54, 16, 'Sale Deleted', '{\"Sale ID\":\"31\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:49:20'),
(55, 16, 'SALE_COMPLETED', 'Sale ID: 32, Customer ID: CTM_W28MQ, Total: ₱750.00', '2024-12-02 13:52:19'),
(56, 16, 'Sale Deleted', '{\"Sale ID\":\"32\",\"Product\":null,\"Details\":{\"quantity\":null,\"price\":null,\"total\":null,\"date\":null}}', '2024-12-02 13:52:27'),
(57, 16, 'SALE_COMPLETED', 'Sale ID: 33, Customer ID: CTM_4P6KB, Total: ₱750.00', '2024-12-02 13:55:11'),
(58, 16, 'SALE_COMPLETED', 'Sale ID: 34, Customer ID: CTM_R12ST, Total: ₱5,000.00', '2024-12-02 13:58:11'),
(59, 16, 'SALE_COMPLETED', 'Sale ID: 35, Customer ID: CTM_75UK8, Total: ₱250.00', '2024-12-02 14:02:03'),
(60, 16, 'SALE_COMPLETED', 'Sale ID: 36, Customer ID: CTM_GU43M, Total: ₱250.00', '2024-12-02 14:02:20'),
(61, 21, 'Sale Update', '{\"Sale ID\":36,\"Product\":\"Naruto\",\"Changes\":{\"quantity\":{\"old\":1,\"new\":\"2\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-12-02 22:02:20\",\"new\":\"2024-12-02\"}}}', '2024-12-02 14:28:46'),
(62, 21, 'Sale Update', '{\"Sale ID\":36,\"Changes\":{\"quantity\":{\"old\":2,\"new\":\"4\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-12-02 00:00:00\",\"new\":\"2024-12-02\"}}}', '2024-12-02 15:01:08'),
(63, 21, 'SALE_COMPLETED', 'Sale ID: 37, Customer ID: CTM_42C6M, Total: ₱1,550.00', '2024-12-03 00:30:37'),
(64, 21, 'Sale Deleted', '{\"Sale ID\":\"37\",\"Product\":\"Sakuragi Hanamichi\",\"Details\":{\"quantity\":\"1\",\"price\":\"250.00\",\"total\":\"250.00\"}}', '2024-12-03 00:35:43'),
(65, 16, 'Sale Deleted', 'null', '2024-12-03 00:38:02'),
(66, 16, 'Sale Deleted', 'null', '2024-12-03 00:38:08'),
(67, 16, 'Sale Deleted', 'null', '2024-12-03 00:38:30'),
(68, 16, 'Sale Deleted', 'null', '2024-12-03 00:40:20'),
(69, 16, 'SALE_COMPLETED', 'Sale ID: 38, Customer ID: CTM_HKUD2, Total: ₱1,400.00', '2024-12-03 00:40:35'),
(70, 16, 'Sale Deleted', 'null', '2024-12-03 00:40:49'),
(71, 16, 'Sale Deleted', 'null', '2024-12-03 00:46:01'),
(72, 21, 'Sale Deleted', '{\"Sale ID\":\"38\",\"Product\":\"Sakuragi Hanamichi\",\"Details\":{\"quantity\":\"1\",\"price\":\"250.00\",\"total\":\"250.00\"}}', '2024-12-03 00:46:22'),
(73, 16, 'SALE_COMPLETED', 'Sale ID: 39, Customer ID: CTM_2SUFZ, Total: ₱1,250.00', '2024-12-03 00:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `created_at`, `updated_at`) VALUES
(6, 'Bobble Head', '2024-11-28 11:49:41', '2024-11-28 11:49:41'),
(31, 'Slamdunk Scaled Figure', '2024-11-29 13:20:40', '2024-11-29 13:20:40'),
(32, 'Strawhat', '2024-11-29 13:20:47', '2024-11-29 13:20:47'),
(33, 'Small Chibi Figure', '2024-11-29 13:20:55', '2024-11-29 13:20:55'),
(34, 'Slayer', '2024-11-29 13:20:59', '2024-11-29 13:20:59'),
(35, 'Sonny Angel', '2024-11-29 14:37:30', '2024-11-29 14:37:30');

-- --------------------------------------------------------

--
-- Table structure for table `edit_history`
--

CREATE TABLE `edit_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `changes` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `edit_history`
--

INSERT INTO `edit_history` (`id`, `user_id`, `action`, `item_type`, `item_id`, `changes`, `timestamp`) VALUES
(33, 1, 'edit', 'product', 16, '{\"category_id\":{\"old\":6,\"new\":\"6\"},\"quantity\":{\"old\":0,\"new\":\"5\"}}', '2024-11-30 11:38:58'),
(34, 16, 'Update', 'group', 16, '{\"group_name\":{\"old\":\"Assistant Staff\",\"new\":\"Admin Assistant\"},\"group_level\":{\"old\":2,\"new\":\"1\"},\"status\":{\"old\":\"inactive\",\"new\":\"active\"}}', '2024-12-02 04:02:38'),
(35, 1, 'edit', 'category', 36, '{\"category_name\":{\"old\":\"Sample Category\",\"new\":\"Temporary Category\"}}', '2024-12-02 04:03:18'),
(36, 16, '', 'profile', 16, '{\"name\":{\"old\":\"Carlos Miguel B. Ochia\",\"new\":\"Carlos Miguel Bodonia Ochia\"}}', '2024-12-02 04:03:33'),
(37, 16, 'delete', 'product', 62, '{\"product_title\":\"Sample Product\",\"action\":\"Product Deleted\",\"deleted_at\":\"2024-12-02 13:07:24\"}', '2024-12-02 12:07:24'),
(38, 16, 'delete', 'product', 63, '{\"product_title\":\"Sample Product\",\"action\":\"Product Deleted\",\"deleted_at\":\"2024-12-02 14:22:01\"}', '2024-12-02 13:22:01'),
(39, 16, 'delete', 'product', 64, '{\"product_title\":\"Sample Product\",\"action\":\"Product Deleted\",\"deleted_at\":\"2024-12-02 14:22:03\"}', '2024-12-02 13:22:03'),
(40, 16, 'delete', 'product', 65, '{\"product_title\":\"Sample Product\",\"action\":\"Product Deleted\",\"deleted_at\":\"2024-12-02 14:22:06\"}', '2024-12-02 13:22:06'),
(41, 16, 'delete', 'product', 38, '{\"product_title\":\"Rukawa Kaede\",\"action\":\"Product Deleted\",\"deleted_at\":\"2024-12-02 14:22:42\"}', '2024-12-02 13:22:42'),
(42, 16, 'delete', 'product', 67, '{\"product_title\":\"Sample Product\",\"action\":\"Product Deleted\",\"deleted_at\":\"2024-12-02 14:23:12\"}', '2024-12-02 13:23:12'),
(43, 16, 'delete', 'product', 66, '{\"product_title\":\"Sample Product\",\"action\":\"Product Deleted\",\"deleted_at\":\"2024-12-02 14:23:15\"}', '2024-12-02 13:23:15');

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `action` enum('login','logout') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`id`, `user_id`, `username`, `name`, `user_role`, `action`, `timestamp`) VALUES
(87, 20, 'Eduard', 'Eduard Hawa', 'staff', 'login', '2024-11-30 11:32:58'),
(88, 16, 'CarlosOchia', 'Carlos Miguel B. Ochia', 'admin', 'login', '2024-11-30 11:33:15'),
(89, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-11-30 11:34:09'),
(90, 16, 'CarlosOchia', 'Carlos Miguel B. Ochia', 'admin', 'login', '2024-11-30 11:34:52'),
(91, 20, 'Eduard', 'Eduard Hawa', 'staff', 'login', '2024-11-30 12:32:01'),
(92, 20, 'Eduard', 'Eduard Hawa', 'staff', 'login', '2024-11-30 12:39:46'),
(93, 16, 'CarlosOchia', 'Carlos Miguel B. Ochia', 'admin', 'login', '2024-11-30 12:42:50'),
(94, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-11-30 12:42:59'),
(95, 16, 'CarlosOchia', 'Carlos Miguel B. Ochia', 'admin', 'login', '2024-11-30 12:52:55'),
(96, 16, 'CarlosOchia', 'Carlos Miguel B. Ochia', 'admin', 'login', '2024-11-30 18:20:05'),
(97, 16, 'CarlosOchia', 'Carlos Miguel B. Ochia', 'admin', 'login', '2024-12-02 04:01:41'),
(98, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:18:41'),
(99, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 04:22:48'),
(100, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 04:24:48'),
(101, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:30:33'),
(102, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 04:42:00'),
(103, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:48:12'),
(104, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:49:35'),
(105, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 04:52:00'),
(106, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:52:50'),
(107, 21, 'Alex', 'Alex Sapon', 'staff', 'logout', '2024-12-02 04:54:15'),
(108, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:54:35'),
(109, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 04:54:54'),
(110, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:56:57'),
(111, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 04:59:16'),
(112, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 05:00:04'),
(113, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 05:17:37'),
(114, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 07:34:59'),
(115, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 10:37:41'),
(116, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 11:03:21'),
(117, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 11:05:35'),
(118, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 11:28:09'),
(119, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 11:29:25'),
(120, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 11:30:57'),
(121, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 11:37:38'),
(122, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 13:26:21'),
(123, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 13:31:06'),
(124, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 13:34:06'),
(125, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 13:35:17'),
(126, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 13:35:56'),
(127, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 13:38:16'),
(128, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:06:12'),
(129, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:06:48'),
(130, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:20:18'),
(131, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:25:32'),
(132, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:26:30'),
(133, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:27:14'),
(134, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:27:20'),
(135, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:27:27'),
(136, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 14:28:57'),
(137, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:29:15'),
(138, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 14:40:19'),
(139, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:40:48'),
(140, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:48:03'),
(141, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:48:35'),
(142, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:48:41'),
(143, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 14:51:46'),
(144, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:52:20'),
(145, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 14:52:35'),
(146, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 14:54:54'),
(147, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 14:55:03'),
(148, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 15:00:07'),
(149, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 15:01:14'),
(150, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 15:03:35'),
(151, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 15:04:36'),
(152, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 15:05:45'),
(153, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 15:06:19'),
(154, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 15:07:07'),
(155, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 23:49:00'),
(156, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 23:52:54'),
(157, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-02 23:53:11'),
(158, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 23:53:59'),
(159, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-02 23:55:47'),
(160, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 00:07:32'),
(161, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 00:11:04'),
(162, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:12:40'),
(163, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 00:17:47'),
(164, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:18:03'),
(165, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 00:24:44'),
(166, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:24:53'),
(167, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 00:30:24'),
(168, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:34:22'),
(169, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 00:35:40'),
(170, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:35:50'),
(171, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:44:05'),
(172, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 00:46:20'),
(173, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:46:30'),
(174, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 00:55:10'),
(175, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 02:08:54'),
(176, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 02:20:28'),
(177, 21, 'Alex', 'Alex Sapon', 'staff', 'login', '2024-12-03 02:28:20'),
(178, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-03 02:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_title` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `buying_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_title`, `category_id`, `image_path`, `quantity`, `buying_price`, `selling_price`, `created_at`, `updated_at`) VALUES
(1, 'Batman', 6, 'product_6749ccc6910173.50650861.jpg', 0, 200.00, 250.00, '2024-11-29 14:16:38', '2024-11-30 02:43:45'),
(2, 'Doctor Strange', 6, 'product_6749ccf425bde6.63472882.png', 0, 200.00, 250.00, '2024-11-29 14:17:24', '2024-11-30 01:49:28'),
(3, 'Cubone', 6, 'product_6749cd30835264.23797053.png', 0, 200.00, 250.00, '2024-11-29 14:18:24', '2024-11-30 04:06:59'),
(4, 'Yuji Itadori', 6, 'product_6749cd68273722.28163877.png', 0, 200.00, 250.00, '2024-11-29 14:19:20', '2024-11-30 02:01:43'),
(5, 'Zenitsu Agatsuma', 6, 'product_6749cd8fe49676.81599445.png', 0, 200.00, 250.00, '2024-11-29 14:19:59', '2024-11-30 02:01:43'),
(6, 'Kugisaki Nobara', 6, 'product_6749cddf3d2e94.06908898.png', 0, 200.00, 250.00, '2024-11-29 14:21:19', '2024-11-30 02:35:28'),
(7, 'Yor Forger', 6, 'product_6749ce101f7e69.69166016.png', 0, 200.00, 250.00, '2024-11-29 14:22:08', '2024-11-30 06:22:37'),
(8, 'Inosuke Hashibira', 6, 'product_6749ce3ebced92.19793466.png', 0, 200.00, 250.00, '2024-11-29 14:22:54', '2024-11-30 04:09:25'),
(9, 'Rukawa Kaede', 6, 'product_6749ce734ddb96.53157239.png', 0, 200.00, 250.00, '2024-11-29 14:23:47', '2024-11-30 06:22:37'),
(10, 'One Piece', 6, 'product_6749ce97ecb3d1.79853123.png', 0, 200.00, 250.00, '2024-11-29 14:24:23', '2024-11-30 08:02:59'),
(11, 'Tony Tony Chopper', 6, 'product_6749cec5918f20.77024356.png', 0, 200.00, 250.00, '2024-11-29 14:25:09', '2024-11-30 11:19:24'),
(12, 'Transformer', 6, 'product_6749cef9f24650.06649079.png', 0, 200.00, 250.00, '2024-11-29 14:26:01', '2024-12-02 13:48:00'),
(13, 'Killua Zoldyck', 6, 'product_6749cf22468749.00793731.png', 0, 200.00, 250.00, '2024-11-29 14:26:42', '2024-12-03 00:52:01'),
(14, 'Super Man', 6, 'product_6749cf491fbf59.40844194.png', 0, 200.00, 250.00, '2024-11-29 14:27:21', '2024-12-02 13:49:15'),
(15, 'Vinsmoke Sanji', 6, 'product_6749cf6bcca310.07524972.png', 0, 200.00, 250.00, '2024-11-29 14:27:55', '2024-11-30 02:27:01'),
(16, 'Satoru Gojo', 6, 'product_6749cf9abf65f7.19951415.png', 2, 200.00, 250.00, '2024-11-29 14:28:42', '2024-12-03 00:52:01'),
(17, 'Sabo', 6, 'product_6749cfd2739d41.41829184.png', 0, 200.00, 250.00, '2024-11-29 14:29:38', '2024-12-02 13:52:19'),
(18, 'Sakuragi Hanamichi', 6, 'product_6749d0057d8a28.94761878.png', 0, 200.00, 250.00, '2024-11-29 14:30:29', '2024-12-03 00:52:01'),
(19, 'NBA', 6, 'product_6749d02a8d0326.82697952.png', 0, 200.00, 250.00, '2024-11-29 14:31:06', '2024-12-03 00:40:35'),
(20, 'Michael Jordan', 6, 'product_6749d04cd70cc4.32034813.png', 1, 200.00, 250.00, '2024-11-29 14:31:40', '2024-12-03 00:33:09'),
(21, 'Boston Kyrie Irving', 6, 'product_6749d06d9d5403.58235461.png', 0, 200.00, 250.00, '2024-11-29 14:32:13', '2024-12-03 00:52:01'),
(22, 'Dwayne Wade ', 6, 'product_6749d0a0640b70.85571305.png', 0, 200.00, 250.00, '2024-11-29 14:33:04', '2024-12-03 00:52:01'),
(23, 'Rockets N\'Faly Dante', 6, 'product_6749d0c656d467.71402392.png', 0, 200.00, 250.00, '2024-11-29 14:33:42', '2024-12-03 00:40:35'),
(24, 'Kevin Durant', 6, 'product_6749d1007a02f1.16704633.png', 1, 200.00, 250.00, '2024-11-29 14:34:40', '2024-11-29 15:13:19'),
(25, 'Brooklyn Kyrie Irving', 6, 'product_6749d12e531810.06743846.png', 0, 200.00, 250.00, '2024-11-29 14:35:26', '2024-12-03 00:40:35'),
(26, 'Sonny Angel Yellow', 35, 'product_6749d1ff3d5384.03167044.png', 12, 130.00, 150.00, '2024-11-29 14:38:55', '2024-11-29 15:13:19'),
(27, 'Sonny Angel Green', 35, 'product_6749d2a7412672.36692733.png', 12, 130.00, 150.00, '2024-11-29 14:41:43', '2024-12-03 00:40:49'),
(28, 'Sonny Angel Pastel Green', 35, 'product_6749d2edae1da0.66276360.png', 12, 130.00, 150.00, '2024-11-29 14:42:53', '2024-12-03 00:38:08'),
(29, 'Zenitsu Agatsuma', 6, 'product_6749d3528ea809.12493405.png', 1, 200.00, 250.00, '2024-11-29 14:44:34', '2024-12-03 00:38:30'),
(30, 'Nezuko Kamado', 6, 'product_6749d37dbe4b06.22807939.png', 1, 200.00, 250.00, '2024-11-29 14:45:17', '2024-11-29 15:13:19'),
(31, 'Inosuke Hashibira', 6, 'product_6749d3a4019969.22987440.png', 1, 200.00, 250.00, '2024-11-29 14:45:56', '2024-11-29 15:13:19'),
(32, 'Zenitsu Agatsuma', 6, 'product_6749d3c7964911.90951545.png', 1, 200.00, 250.00, '2024-11-29 14:46:31', '2024-11-29 15:13:19'),
(33, 'Nezuko Kamado', 6, 'product_6749d4027adc67.86425085.png', 1, 200.00, 250.00, '2024-11-29 14:47:30', '2024-11-29 15:13:19'),
(34, 'Ryota Miyagi', 31, 'product_6749d4471a8798.35653158.png', 1, 1400.00, 1500.00, '2024-11-29 14:48:39', '2024-12-02 14:59:15'),
(35, 'Inosuke Hashibira', 6, 'product_6749d4738b5712.36343270.png', 1, 200.00, 250.00, '2024-11-29 14:49:23', '2024-11-29 15:13:19'),
(36, 'Strawhat', 32, 'product_6749d4b21e57f6.77832675.png', 10, 200.00, 250.00, '2024-11-29 14:50:26', '2024-12-02 14:58:43'),
(37, 'Sakuragi Hanamichi', 31, 'product_6749d4dcb96645.17652966.png', 1, 1400.00, 1500.00, '2024-11-29 14:51:08', '2024-11-29 15:13:19'),
(39, 'Slam Dunk', 31, 'product_6749d57aee7020.12343426.png', 1, 1400.00, 1500.00, '2024-11-29 14:53:46', '2024-12-02 14:54:57'),
(40, 'Takenori Akagi', 31, 'product_6749d5ae73b977.71948871.png', 1, 1400.00, 1500.00, '2024-11-29 14:54:38', '2024-11-29 15:13:19'),
(41, 'Iron Man', 6, 'product_6749d5c6b5c5a8.65643039.png', 1, 200.00, 250.00, '2024-11-29 14:55:02', '2024-12-02 14:52:23'),
(42, 'j-hope BTS', 6, 'product_6749d5eb36ac57.38170675.png', 1, 200.00, 250.00, '2024-11-29 14:55:39', '2024-11-29 15:13:19'),
(43, 'Jerry', 6, 'product_6749d605be84a2.74567754.png', 1, 200.00, 250.00, '2024-11-29 14:56:05', '2024-12-02 14:51:02'),
(44, 'Jimin BTS', 6, 'product_6749d61dad92e7.69627029.png', 1, 200.00, 250.00, '2024-11-29 14:56:29', '2024-12-02 13:56:40'),
(45, 'Joker', 6, 'product_6749d631788ad8.85005782.png', 1, 200.00, 250.00, '2024-11-29 14:56:49', '2024-12-02 14:01:21'),
(46, 'Jungkook', 6, 'product_6749d64c72cb93.95160840.png', 1, 200.00, 250.00, '2024-11-29 14:57:16', '2024-12-02 13:55:26'),
(47, 'Kuromi', 6, 'product_6749d6687fc155.20090267.png', 1, 200.00, 250.00, '2024-11-29 14:57:44', '2024-12-02 14:01:19'),
(48, 'Monkey D. Luffy', 6, 'product_6749d687385c52.73493561.png', 1, 200.00, 250.00, '2024-11-29 14:58:15', '2024-12-02 13:55:19'),
(49, 'Luigi', 6, 'product_6749d6a30ec619.25506915.png', 1, 200.00, 250.00, '2024-11-29 14:58:43', '2024-12-02 14:00:03'),
(50, 'Mario', 6, 'product_6749d6c1c0e2f7.87391948.png', 1, 200.00, 250.00, '2024-11-29 14:59:13', '2024-11-29 15:13:19'),
(51, 'Naruto', 6, 'product_6749d6e1a034e7.38220441.png', 4, 200.00, 250.00, '2024-11-29 14:59:45', '2024-12-03 00:40:20'),
(52, 'Pikachu', 6, 'product_6749d6f84e4b13.85293055.png', 1, 200.00, 250.00, '2024-11-29 15:00:08', '2024-12-02 14:50:59'),
(53, 'RM BTS', 6, 'product_6749d71942f4a8.13791184.png', 0, 200.00, 250.00, '2024-11-29 15:00:41', '2024-12-02 13:52:19'),
(54, 'Ronald Mcdonald', 6, 'product_6749d736502fc1.78450366.png', 0, 200.00, 250.00, '2024-11-29 15:01:10', '2024-12-02 13:44:31'),
(55, 'Sonic', 6, 'product_6749d74ae12fd5.93127031.png', 0, 200.00, 250.00, '2024-11-29 15:01:30', '2024-12-02 13:44:31'),
(56, 'Spider-Man', 6, 'product_6749d763f2eb03.74908139.png', 0, 200.00, 250.00, '2024-11-29 15:01:55', '2024-12-02 13:48:31'),
(57, 'Squirtle', 6, 'product_6749d77f8235a3.68025455.png', 0, 200.00, 250.00, '2024-11-29 15:02:23', '2024-12-02 13:44:31'),
(58, 'Stitch', 6, 'product_6749d7955cb6e9.47097305.png', 0, 200.00, 250.00, '2024-11-29 15:02:45', '2024-12-02 13:48:31'),
(59, 'V', 6, 'product_6749d7ac757742.14387807.png', 0, 200.00, 250.00, '2024-11-29 15:03:08', '2024-12-02 13:44:31'),
(68, 'Sample Product', 6, 'artmoreshop.jpg', 97, 50.00, 75.00, '2024-12-02 06:23:34', '2024-12-02 13:48:31'),
(69, 'Sample Product', 31, 'artmoreshop.jpg', 47, 100.00, 150.00, '2024-12-02 06:23:34', '2024-12-02 13:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `total_amount`, `sale_date`) VALUES
(38, 'CTM_HKUD2', 250.00, '2024-12-03 08:40:35'),
(39, 'CTM_2SUFZ', 1250.00, '2024-12-03 08:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `price`, `total`) VALUES
(71, 38, 19, 1, 250.00, 250.00),
(72, 39, 13, 1, 250.00, 250.00),
(73, 39, 16, 1, 250.00, 250.00),
(74, 39, 18, 1, 250.00, 250.00),
(75, 39, 22, 1, 250.00, 250.00),
(76, 39, 21, 1, 250.00, 250.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','','') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(1) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`, `updated_at`, `status`, `profile_image`, `deleted_at`) VALUES
(16, 'Carlos Miguel Bodonia Ochia', 'CarlosBodonia', '$2y$10$BZbMEahBoJNXfJIXPiuZhu5uNlgcX7yfhOPH3zyEBA7tA2XZNMAfO', 'admin', '2024-11-29 06:57:12', '2024-12-02 04:03:33', 1, '1732888824_images (2).jpg', NULL),
(20, 'Eduard Hawa', 'Eduard', '$2y$10$JxdGvlLdDk7PJDWvzfPh8ucxbi.7tpt48bzaWFc3WQ0Jz.E706DTG', 'staff', '2024-11-30 01:12:24', '2024-11-30 01:12:24', 1, '674ac8e84188b.jpg', NULL),
(21, 'Alex Sapon', 'Alex', '$2y$10$j8w1XdF0c9FyIrGeeeMzbuAMK03YT8AytURD5p7mLZtoe.ldDKmaO', 'staff', '2024-11-30 04:33:54', '2024-11-30 04:33:54', 1, '674af8225be2a.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_level` tinyint(4) NOT NULL,
  `status` enum('active','deactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`id`, `group_name`, `group_level`, `status`, `created_at`, `updated_at`) VALUES
(13, 'Admin', 1, 'active', '2024-11-29 11:03:24', '2024-11-29 13:56:12'),
(15, 'Staff', 2, 'active', '2024-11-29 13:56:22', '2024-11-29 13:56:22'),
(16, 'Admin Assistant', 1, 'active', '2024-11-29 13:56:38', '2024-12-02 04:02:38'),
(18, 'Sample', 2, 'active', '2024-12-02 07:18:52', '2024-12-02 07:18:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_ibfk_1` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `edit_history`
--
ALTER TABLE `edit_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_type` (`item_type`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `edit_history`
--
ALTER TABLE `edit_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
