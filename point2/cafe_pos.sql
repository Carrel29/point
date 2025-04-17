-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 13, 2025 at 06:05 AM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cafe_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `archived` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `archived`) VALUES
(1, 'coffee', 0),
(2, 'breakfast', 0),
(3, 'addons', 0),
(4, 'milktea', 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `type` enum('cash','online') NOT NULL,
  `payment_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `code`, `type`, `payment_image`, `is_active`, `created_at`, `archived`, `updated_at`) VALUES
(1, 'Cash', 'CASH', 'cash', NULL, 1, '2025-04-11 15:24:30', 0, '2025-04-12 05:35:22'),
(2, 'GCash', 'GCASH', 'online', 'images/2025/04/67fb540b33aaf.png', 1, '2025-04-11 15:24:30', 0, '2025-04-13 06:04:59'),
(3, 'PayMaya', 'MAYA', 'online', 'images/2025/04/67fb5414c99a6.png', 1, '2025-04-12 01:38:11', 0, '2025-04-13 06:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `price_medium` decimal(10,2) DEFAULT NULL,
  `price_large` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `archived` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `code`, `price_medium`, `price_large`, `price`, `category_id`, `archived`) VALUES
(1, 'Americano Coffee', 'AC', 70.00, 80.00, NULL, 1, 0),
(2, 'Vanilla Latte', 'VL', 80.00, 90.00, NULL, 1, 0),
(3, 'Café Latte', 'CL', 80.00, 90.00, NULL, 1, 0),
(4, 'Caramel Macchiato', 'CM', 90.00, 100.00, NULL, 1, 0),
(5, 'Cappuccino', 'CP', 80.00, 90.00, NULL, 1, 0),
(6, 'Matcha Espresso', 'ME', 100.00, 110.00, NULL, 1, 0),
(7, 'Classic Breakfast', 'CB', NULL, NULL, 120.00, 2, 0),
(8, 'Pancakes with Syrup', 'PS', NULL, NULL, 150.00, 2, 0),
(9, 'Eggs Benedict', 'EB', NULL, NULL, 130.00, 2, 0),
(10, 'Avocado Toast', 'AT', NULL, NULL, 110.00, 2, 0),
(11, 'Extra Shot of Espresso', 'ES', NULL, NULL, 20.00, 3, 0),
(12, 'Whipped Cream', 'WC', NULL, NULL, 10.00, 3, 0),
(13, 'Caramel Drizzle', 'CD', NULL, NULL, 15.00, 3, 0),
(14, 'Chocolate Syrup', 'CS', NULL, NULL, 15.00, 3, 0),
(15, 'Mocha Latte', 'ML', 80.00, 100.00, NULL, 1, 0),
(16, 'Wintermelon', 'WM', 39.00, 49.00, NULL, 4, 0);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_date` datetime NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `cash_received` decimal(10,2) DEFAULT NULL,
  `cash_change` decimal(10,2) DEFAULT NULL,
  `reference_number` varchar(4) DEFAULT NULL,
  `cart_items` text NOT NULL,
  `username` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_date`, `payment_method`, `total_amount`, `cash_received`, `cash_change`, `reference_number`, `cart_items`, `username`, `created_at`) VALUES
(1, '2025-04-12 17:58:21', 'online', 90.00, NULL, NULL, '1234', '[{\"name\":\"Caf\\u00e9 Latte\",\"price\":\"90\",\"quantity\":\"1\"}]', 'Admin', '2025-04-12 09:58:21'),
(2, '2025-04-12 22:00:38', 'online', 80.00, NULL, NULL, '0629', '[{\"name\":\"Caf\\u00e9 Latte\",\"price\":\"80\",\"quantity\":\"1\"}]', 'Admin', '2025-04-12 14:00:38'),
(3, '2025-04-13 03:58:18', 'cash', 39.00, 50.00, 11.00, NULL, '[{\"name\":\"Wintermelon\",\"price\":\"39\",\"quantity\":\"1\"}]', 'Admin', '2025-04-12 19:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `is_admin`, `created_at`) VALUES
(2, 'Carrel29', '$2y$10$Msh1YcsImn5dT3k/Vd2JDe7wFpEFSlpKQWKKOkdVeX/M/hRJIM31m', 1, '2025-04-10 06:00:52'),
(3, 'Admin', '$2y$10$aSQUZSs05ymzbbjQtV/1Peg1X3BqQtxnUVO/F1NuLVfbzDHruqHtC', 1, '2025-04-10 06:00:52'),
(4, 'Cashier', '$2y$10$Zl5RYCPXMEBbDka3ItlFneMU4QmnbrBObwTMIP8qBm6PmrqyIgxjG', 0, '2025-04-10 06:00:52');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
