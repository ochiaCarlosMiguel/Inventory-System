-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 05, 2024 at 09:28 AM
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
-- Database: `u178537786_inventory`
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
(85, 16, 'SALE_COMPLETED', 'Sale ID: 46, Customer ID: CTM_TUMID, Total: ₱250.00', '2024-12-04 14:02:36'),
(86, 16, 'Sale Deleted', 'null', '2024-12-04 14:02:50'),
(87, 16, 'SALE_COMPLETED', 'Sale ID: 47, Customer ID: CTM_C193F, Total: ₱150.00', '2024-12-04 14:05:39'),
(88, 16, 'Sale Deleted', 'null', '2024-12-04 14:47:06'),
(89, 16, 'SALE_COMPLETED', 'Sale ID: 48, Customer ID: CTM_QCDKD, Total: ₱750.00', '2024-12-04 14:59:00'),
(90, 16, 'SALE_COMPLETED', 'Sale ID: 49, Customer ID: CTM_QS412, Total: ₱1,500.00', '2024-12-04 15:02:36'),
(91, 16, 'Sale Deleted', 'null', '2024-12-04 15:03:53'),
(92, 16, 'Sale Deleted', 'null', '2024-12-04 15:03:56'),
(93, 16, 'User Update', 'Updated user ID 22: Role changed from \'admin\' to \'Staff\'', '2024-12-04 15:05:39'),
(94, 16, 'SALE_COMPLETED', 'Sale ID: 50, Customer ID: CTM_JOWQH, Total: ₱500.00', '2024-12-04 15:13:40'),
(95, 21, 'SALE_COMPLETED', 'Sale ID: 51, Customer ID: CTM_GLHD0, Total: ₱500.00', '2024-12-04 15:15:11'),
(96, 21, 'Sale Update', '{\"Sale ID\":51,\"Changes\":{\"quantity\":{\"old\":1,\"new\":\"5\"},\"price\":{\"old\":\"250.00\",\"new\":\"250.00\"},\"sale_date\":{\"old\":\"2024-12-04 23:15:11\",\"new\":\"2024-12-04\"}}}', '2024-12-04 15:15:44'),
(97, 16, 'User Update', 'Updated user ID 22: Role changed from \'staff\' to \'manager\'', '2024-12-04 15:18:15'),
(98, 16, 'Sale Deleted', 'null', '2024-12-05 03:38:02'),
(99, 16, 'Category Delete', 'Deleted category \'\' (ID: #CAT007) with 0 associated products', '2024-12-05 07:47:02'),
(100, 16, 'Category Edit', 'Updated category name from \'Carlos Bodonias\' to \'Carlos Bodonia\'', '2024-12-05 07:59:00'),
(101, 16, 'SALE_COMPLETED', 'Sale ID: 53, Customer ID: CTM_3NNIM, Total: ₱750.00', '2024-12-05 08:04:52'),
(102, 21, 'SALE_COMPLETED', 'Sale ID: 54, Customer ID: CTM_8ZD6E, Total: ₱750.00', '2024-12-05 08:08:59'),
(103, 16, 'Category Edit', 'Updated category name from \'Carlos Bodonia\' to \'Carlos Bodoniass\'', '2024-12-05 09:17:52'),
(104, 16, 'Category Delete', 'Deleted category \'\' (ID: #CAT007) with 0 associated products', '2024-12-05 09:18:08'),
(105, 16, 'SALE_COMPLETED', 'Sale ID: 55, Customer ID: CTM_Y1ASL, Total: ₱500.00', '2024-12-05 09:18:43'),
(106, 16, 'User Update', 'Updated user ID 22: Role changed from \'\' to \'Staff\'', '2024-12-05 09:22:10'),
(107, 16, 'User Update', 'Updated user ID 22: Role changed from \'staff\' to \'Staff\'', '2024-12-05 09:22:33'),
(108, 16, 'User Update', 'Updated user ID 23: Role changed from \'admin\' to \'Staff\'', '2024-12-05 09:22:38');

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
(1, 'Bobble Head', '2024-11-28 11:49:41', '2024-12-04 10:42:28'),
(2, 'Slamdunk Scaled Figure', '2024-11-29 13:20:40', '2024-12-04 10:43:00'),
(3, 'Strawhat', '2024-11-29 13:20:47', '2024-12-04 10:42:28'),
(4, 'Small Chibi Figure', '2024-11-29 13:20:55', '2024-12-04 10:42:28'),
(5, 'Slayer', '2024-11-29 13:20:59', '2024-12-04 10:42:28'),
(6, 'Sonny Angels', '2024-11-29 14:37:30', '2024-12-04 14:53:59'),
(7, 'Tom', '2024-12-05 08:01:12', '2024-12-05 09:18:08');

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
(261, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'logout', '2024-12-05 09:26:37'),
(262, 16, 'CarlosBodonia', 'Carlos Miguel Bodonia Ochia', 'admin', 'login', '2024-12-05 09:27:34');

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
(1, 'Batman', 1, 'product_6749ccc6910173.50650861.jpg', 0, 230.00, 250.00, '2024-11-29 14:16:38', '2024-12-05 08:04:52'),
(2, 'Doctor Strange', 1, 'product_6749ccf425bde6.63472882.png', 0, 230.00, 250.00, '2024-11-29 14:17:24', '2024-12-05 08:04:52'),
(3, 'Cubone', 1, 'product_6749cd30835264.23797053.png', 0, 230.00, 250.00, '2024-11-29 14:18:24', '2024-12-04 15:15:11'),
(4, 'Yuji Itadori', 1, 'product_6749cd68273722.28163877.png', 0, 230.00, 250.00, '2024-11-29 14:19:20', '2024-12-05 08:04:52'),
(5, 'Zenitsu Agatsuma', 1, 'product_6749cd8fe49676.81599445.png', 0, 230.00, 250.00, '2024-11-29 14:19:59', '2024-12-04 14:59:00'),
(6, 'Kugisaki Nobara', 1, 'product_6749cddf3d2e94.06908898.png', 0, 230.00, 250.00, '2024-11-29 14:21:19', '2024-12-05 09:18:43'),
(7, 'Yor Forger', 1, 'product_6749ce101f7e69.69166016.png', 0, 230.00, 250.00, '2024-11-29 14:22:08', '2024-12-05 09:18:43'),
(8, 'Inosuke Hashibira', 1, 'product_6749ce3ebced92.19793466.png', 0, 230.00, 250.00, '2024-11-29 14:22:54', '2024-12-05 08:08:59'),
(9, 'Rukawa Kaede', 1, 'product_6749ce734ddb96.53157239.png', 0, 230.00, 250.00, '2024-11-29 14:23:47', '2024-12-04 15:02:36'),
(10, 'One Piece', 1, 'product_6749ce97ecb3d1.79853123.png', 2, 230.00, 250.00, '2024-11-29 14:24:23', '2024-12-04 12:18:48'),
(11, 'Tony Tony Chopper', 1, 'product_6749cec5918f20.77024356.png', 1, 230.00, 250.00, '2024-11-29 14:25:09', '2024-12-04 12:18:59'),
(12, 'Transformer', 1, 'product_6749cef9f24650.06649079.png', 1, 230.00, 250.00, '2024-11-29 14:26:01', '2024-12-04 12:20:16'),
(13, 'Killua Zoldyck', 1, 'product_6749cf22468749.00793731.png', 2, 230.00, 250.00, '2024-11-29 14:26:42', '2024-12-04 12:20:26'),
(14, 'Super Man', 1, 'product_6749cf491fbf59.40844194.png', 1, 230.00, 250.00, '2024-11-29 14:27:21', '2024-12-04 15:02:36'),
(15, 'Vinsmoke Sanji', 1, 'product_6749cf6bcca310.07524972.png', 1, 230.00, 250.00, '2024-11-29 14:27:55', '2024-12-04 12:21:22'),
(16, 'Satoru Gojo', 1, 'product_6749cf9abf65f7.19951415.png', 2, 230.00, 250.00, '2024-11-29 14:28:42', '2024-12-04 12:21:57'),
(17, 'Sabo', 1, 'product_6749cfd2739d41.41829184.png', 0, 230.00, 250.00, '2024-11-29 14:29:38', '2024-12-04 15:02:36'),
(18, 'Sakuragi Hanamichi', 1, 'product_6749d0057d8a28.94761878.png', 1, 230.00, 250.00, '2024-11-29 14:30:29', '2024-12-04 12:22:23'),
(19, 'NBA', 1, 'product_6749d02a8d0326.82697952.png', 1, 230.00, 250.00, '2024-11-29 14:31:06', '2024-12-04 12:22:48'),
(20, 'Michael Jordan', 1, 'product_6749d04cd70cc4.32034813.png', 0, 230.00, 250.00, '2024-11-29 14:31:40', '2024-12-04 15:02:36'),
(21, 'Boston Kyrie Irving', 1, 'product_6749d06d9d5403.58235461.png', 1, 230.00, 250.00, '2024-11-29 14:32:13', '2024-12-04 12:23:10'),
(22, 'Dwayne Wade', 1, 'product_6749d0a0640b70.85571305.png', 1, 230.00, 250.00, '2024-11-29 14:33:04', '2024-12-04 12:23:21'),
(23, 'Rockets N\'Faly Dante', 1, 'product_6749d0c656d467.71402392.png', 1, 230.00, 250.00, '2024-11-29 14:33:42', '2024-12-04 12:23:33'),
(24, 'Kevin Durant', 1, 'product_6749d1007a02f1.16704633.png', 1, 230.00, 250.00, '2024-11-29 14:34:40', '2024-12-04 12:23:45'),
(25, 'Brooklyn Kyrie Irving', 1, 'product_6749d12e531810.06743846.png', 1, 230.00, 250.00, '2024-11-29 14:35:26', '2024-12-04 12:23:55'),
(26, 'Sonny Angel Yellow', 6, 'product_6749d1ff3d5384.03167044.png', 12, 130.00, 150.00, '2024-11-29 14:38:55', '2024-12-04 14:47:06'),
(27, 'Sonny Angel Green', 6, 'product_6749d2a7412672.36692733.png', 12, 130.00, 150.00, '2024-11-29 14:41:43', '2024-12-03 00:40:49'),
(28, 'Sonny Angel Pastel Green', 6, 'product_6749d2edae1da0.66276360.png', 12, 130.00, 150.00, '2024-11-29 14:42:53', '2024-12-03 00:38:08'),
(29, 'Zenitsu Agatsuma', 1, 'product_6749d3528ea809.12493405.png', 1, 230.00, 250.00, '2024-11-29 14:44:34', '2024-12-04 12:21:41'),
(30, 'Nezuko Kamado', 1, 'product_6749d37dbe4b06.22807939.png', 0, 230.00, 250.00, '2024-11-29 14:45:17', '2024-12-04 15:13:40'),
(31, 'Inosuke Hashibira', 1, 'product_6749d3a4019969.22987440.png', 1, 230.00, 250.00, '2024-11-29 14:45:56', '2024-12-04 12:24:25'),
(32, 'Zenitsu Agatsuma', 1, 'product_6749d3c7964911.90951545.png', 1, 230.00, 250.00, '2024-11-29 14:46:31', '2024-12-04 12:24:39'),
(33, 'Nezuko Kamado', 1, 'product_6749d4027adc67.86425085.png', 0, 230.00, 250.00, '2024-11-29 14:47:30', '2024-12-04 15:13:40'),
(34, 'Ryota Miyagi', 2, 'product_6749d4471a8798.35653158.png', 1, 1450.00, 1500.00, '2024-11-29 14:48:39', '2024-12-04 12:32:16'),
(35, 'Inosuke Hashibira', 1, 'product_6749d4738b5712.36343270.png', 1, 230.00, 250.00, '2024-11-29 14:49:23', '2024-12-04 12:25:39'),
(36, 'Strawhat', 3, 'product_6749d4b21e57f6.77832675.png', 10, 230.00, 250.00, '2024-11-29 14:50:26', '2024-12-04 12:25:54'),
(37, 'Sakuragi Hanamichi', 2, 'product_6749d4dcb96645.17652966.png', 1, 1450.00, 1500.00, '2024-11-29 14:51:08', '2024-12-04 12:32:32'),
(39, 'Slam Dunk', 2, 'product_6749d57aee7020.12343426.png', 1, 1450.00, 1500.00, '2024-11-29 14:53:46', '2024-12-04 12:32:43'),
(40, 'Takenori Akagi', 2, 'product_6749d5ae73b977.71948871.png', 1, 1450.00, 1500.00, '2024-11-29 14:54:38', '2024-12-04 12:32:57'),
(41, 'Iron Man', 1, 'product_6749d5c6b5c5a8.65643039.png', 1, 230.00, 250.00, '2024-11-29 14:55:02', '2024-12-04 12:26:10'),
(42, 'j-hope BTS', 1, 'product_6749d5eb36ac57.38170675.png', 1, 230.00, 250.00, '2024-11-29 14:55:39', '2024-12-04 12:26:23'),
(43, 'Jerry', 1, 'product_6749d605be84a2.74567754.png', 1, 230.00, 250.00, '2024-11-29 14:56:05', '2024-12-04 12:29:44'),
(44, 'Jimin BTS', 1, 'product_6749d61dad92e7.69627029.png', 1, 230.00, 250.00, '2024-11-29 14:56:29', '2024-12-04 12:30:32'),
(45, 'Joker', 1, 'product_6749d631788ad8.85005782.png', 1, 230.00, 250.00, '2024-11-29 14:56:49', '2024-12-04 12:30:15'),
(46, 'Jungkook', 1, 'product_6749d64c72cb93.95160840.png', 1, 230.00, 250.00, '2024-11-29 14:57:16', '2024-12-04 12:29:28'),
(47, 'Kuromi', 1, 'product_6749d6687fc155.20090267.png', 1, 230.00, 250.00, '2024-11-29 14:57:44', '2024-12-04 12:29:13'),
(48, 'Monkey D. Luffy', 1, 'product_6749d687385c52.73493561.png', 1, 230.00, 250.00, '2024-11-29 14:58:15', '2024-12-04 12:28:52'),
(49, 'Luigi', 1, 'product_6749d6a30ec619.25506915.png', 1, 230.00, 250.00, '2024-11-29 14:58:43', '2024-12-04 12:28:38'),
(50, 'Mario', 1, 'product_6749d6c1c0e2f7.87391948.png', 1, 230.00, 250.00, '2024-11-29 14:59:13', '2024-12-04 12:28:26'),
(51, 'Naruto', 1, 'product_6749d6e1a034e7.38220441.png', 4, 230.00, 250.00, '2024-11-29 14:59:45', '2024-12-04 14:02:50'),
(52, 'Pikachu', 1, 'product_6749d6f84e4b13.85293055.png', 1, 230.00, 250.00, '2024-11-29 15:00:08', '2024-12-04 12:28:05'),
(53, 'RM BTS', 1, 'product_6749d71942f4a8.13791184.png', 1, 230.00, 250.00, '2024-11-29 15:00:41', '2024-12-04 12:27:53'),
(54, 'Ronald Mcdonald', 1, 'product_6749d736502fc1.78450366.png', 2, 230.00, 250.00, '2024-11-29 15:01:10', '2024-12-04 12:27:43'),
(55, 'Sonic', 1, 'product_6749d74ae12fd5.93127031.png', 1, 230.00, 250.00, '2024-11-29 15:01:30', '2024-12-04 12:27:33'),
(56, 'Spider-Man', 1, 'product_6749d763f2eb03.74908139.png', 1, 230.00, 250.00, '2024-11-29 15:01:55', '2024-12-04 12:27:23'),
(57, 'Squirtle', 1, 'product_6749d77f8235a3.68025455.png', 1, 230.00, 250.00, '2024-11-29 15:02:23', '2024-12-04 12:26:55'),
(58, 'Stitch', 1, 'product_6749d7955cb6e9.47097305.png', 1, 230.00, 250.00, '2024-11-29 15:02:45', '2024-12-04 12:26:43'),
(59, 'V', 1, 'product_6749d7ac757742.14387807.png', 1, 230.00, 250.00, '2024-11-29 15:03:08', '2024-12-04 12:26:33');

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
(48, 'CTM_QCDKD', 750.00, '2024-12-04 22:59:00'),
(49, 'CTM_QS412', 1000.00, '2024-12-04 23:02:36'),
(50, 'CTM_JOWQH', 500.00, '2024-12-04 23:13:40'),
(51, 'CTM_GLHD0', 1250.00, '2024-12-04 00:00:00'),
(53, 'CTM_3NNIM', 750.00, '2024-12-05 16:04:52'),
(54, 'CTM_8ZD6E', 750.00, '2024-12-05 16:08:59'),
(55, 'CTM_Y1ASL', 500.00, '2024-12-05 17:18:43');

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
(90, 48, 1, 1, 250.00, 250.00),
(91, 48, 5, 2, 250.00, 500.00),
(94, 49, 9, 1, 250.00, 250.00),
(95, 49, 14, 1, 250.00, 250.00),
(96, 49, 17, 1, 250.00, 250.00),
(97, 49, 20, 1, 250.00, 250.00),
(98, 50, 30, 1, 250.00, 250.00),
(99, 50, 33, 1, 250.00, 250.00),
(100, 51, 1, 5, 250.00, 1250.00),
(101, 51, 3, 5, 250.00, 1250.00),
(103, 53, 1, 1, 250.00, 250.00),
(104, 53, 2, 1, 250.00, 250.00),
(105, 53, 4, 1, 250.00, 250.00),
(106, 54, 6, 1, 250.00, 250.00),
(107, 54, 7, 1, 250.00, 250.00),
(108, 54, 8, 1, 250.00, 250.00),
(109, 55, 6, 1, 250.00, 250.00),
(110, 55, 7, 1, 250.00, 250.00);

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
(16, 'Carlos Miguel Bodonia Ochia', 'CarlosBodonia', '$2y$10$BZbMEahBoJNXfJIXPiuZhu5uNlgcX7yfhOPH3zyEBA7tA2XZNMAfO', 'admin', '2024-11-29 06:57:12', '2024-12-05 07:49:09', 1, '1732888824_images (2).jpg', NULL),
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
(15, 'Staff', 2, 'active', '2024-11-29 13:56:22', '2024-12-05 09:22:02');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `edit_history`
--
ALTER TABLE `edit_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=263;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
