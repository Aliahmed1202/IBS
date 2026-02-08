-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 31, 2026 at 11:28 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ibs_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Phones', 'Mobile phones and smartphones', 1, '2026-01-24 00:14:15'),
(2, 'AirPods', 'Apple wireless earbuds and headphones', 1, '2026-01-24 00:14:15'),
(3, 'Watch', 'Smart watches and wearable devices', 1, '2026-01-24 00:14:15'),
(4, 'Accessories', 'Phone accessories like cases, chargers, cables, etc.', 1, '2026-01-24 00:14:15'),
(5, 'Tablets', 'Tablet devices', 1, '2026-01-24 00:14:15');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `total_purchases` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `total_purchases`, `created_at`) VALUES
(1, 'Ahmed Hassan', '+966-50-111-2222', 'ahmed@email.com', 'Riyadh, Saudi Arabia', 0.00, '2026-01-24 00:14:16'),
(2, 'Sarah Johnson', '+966-55-333-4444', 'sarah@email.com', 'Jeddah, Saudi Arabia', 0.00, '2026-01-24 00:14:16'),
(3, 'Mohammed Ali', '+966-58-555-6666', 'mohammed@email.com', 'Dammam, Saudi Arabia', 0.00, '2026-01-24 00:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

DROP TABLE IF EXISTS `income`;
CREATE TABLE IF NOT EXISTS `income` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`id`, `amount`, `description`, `date`, `created_at`) VALUES
(1, 5000.00, 'Initial investment', '2026-01-01', '2026-01-22 23:19:00'),
(2, 3000.00, 'Additional capital', '2026-01-15', '2026-01-22 23:19:00'),
(3, 2000.00, 'Loan received', '2026-01-20', '2026-01-22 23:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `income_entries`
--

DROP TABLE IF EXISTS `income_entries`;
CREATE TABLE IF NOT EXISTS `income_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `price` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `entry_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_income_date` (`entry_date`),
  KEY `idx_income_created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `amount`, `description`, `date`, `created_at`) VALUES
(1, 1500.00, 'Shop rent', '2026-01-05', '2026-01-22 23:19:00'),
(2, 800.00, 'Electricity bill', '2026-01-10', '2026-01-22 23:19:00'),
(3, 1200.00, 'Staff salaries', '2026-01-15', '2026-01-22 23:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `payment_entries`
--

DROP TABLE IF EXISTS `payment_entries`;
CREATE TABLE IF NOT EXISTS `payment_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `price` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `entry_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_date` (`entry_date`),
  KEY `idx_payment_created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `barcode` varchar(20) DEFAULT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `suggested_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `purchase_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `min_stock` int DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `imei` varchar(20) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `has_imei` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_products_barcode` (`barcode`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `barcode`, `brand`, `model`, `suggested_price`, `purchase_price`, `min_selling_price`, `stock`, `min_stock`, `category`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`, `category_id`, `serial_number`, `imei`, `color`, `supplier_id`, `has_imei`) VALUES
(1, 'IBS001', NULL, 'Apple', 'iPhone 15 Pro', 3999.00, 2799.00, 3199.00, 25, NULL, NULL, 'Latest iPhone with A17 Pro chip', NULL, 1, '2026-01-24 00:14:16', '2026-01-24 00:14:16', 1, 'SN001234567890', '354409123456789', 'Space Gray', 1, 1),
(2, 'IBS002', NULL, 'Apple', 'iPhone 15', 3199.00, 2239.00, 2559.00, 30, NULL, NULL, 'iPhone 15 with Dynamic Island', NULL, 1, '2026-01-24 00:14:16', '2026-01-24 00:14:16', 1, 'SN002345678901', '354409234567890', 'Blue', 2, 1),
(3, 'IBS003', NULL, 'Samsung', 'Galaxy S24 Ultra', 4799.00, 3359.00, 3839.00, 18, NULL, NULL, 'Premium Galaxy with S Pen', NULL, 1, '2026-01-24 00:14:16', '2026-01-24 00:14:16', 1, 'SN003456789012', '358940123456789', 'Black', 3, 1),
(4, 'IBS004', NULL, 'Apple', 'iPad Air', 2399.00, 1679.00, 1919.00, 12, NULL, NULL, 'iPad Air with M1 chip', NULL, 1, '2026-01-24 00:14:16', '2026-01-24 00:14:16', 5, 'SN004567890123', NULL, 'Space Gray', 2, 0),
(5, 'IBS005', NULL, 'Samsung', 'Galaxy Watch 6', 1319.00, 923.00, 1055.00, 15, NULL, NULL, 'Latest Galaxy smartwatch', NULL, 1, '2026-01-24 00:14:16', '2026-01-24 00:14:16', 3, 'SN005123456789', NULL, 'Black', 1, 0),
(6, 'IBS006', NULL, 'iphone ', '17 pro', 99999999.99, 99999999.99, 99999999.99, 2, 2, NULL, '', '', 1, '2026-01-24 00:15:54', '2026-01-30 17:40:45', 1, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(20) NOT NULL,
  `customer_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `sale_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `receipt_number`, `customer_id`, `staff_id`, `total_amount`, `payment_method`, `sale_date`, `created_at`) VALUES
(1, 'IBS-20260124-001', 1, 1, 3999.00, 'Cash', '2026-01-24 00:14:16', '2026-01-24 00:14:16'),
(2, 'IBS-20260124-002', 2, 2, 2799.00, 'Card', '2026-01-24 00:14:16', '2026-01-24 00:14:16'),
(3, 'IBS-20260124-003', 3, 1, 4799.00, 'Cash', '2026-01-24 00:14:16', '2026-01-24 00:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 1, 3999.00, 3999.00),
(2, 2, 2, 1, 2799.00, 2799.00),
(3, 3, 3, 1, 4799.00, 4799.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_items`
--

DROP TABLE IF EXISTS `stock_items`;
CREATE TABLE IF NOT EXISTS `stock_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `imei` varchar(20) DEFAULT NULL,
  `status` enum('available','sold','reserved','damaged') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sale_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_serial` (`serial_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `fk_stock_items_sale` (`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_items`
--

INSERT INTO `stock_items` (`id`, `product_id`, `serial_number`, `imei`, `status`, `created_at`, `updated_at`, `sale_id`) VALUES
(1, 1, 'SN001234567890', '354409123456789', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(2, 1, 'SN001234567891', '354409123456790', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(3, 1, 'SN001234567892', '354409123456791', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(4, 2, 'SN002345678901', '354409234567890', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(5, 2, 'SN002345678902', '354409234567891', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(6, 2, 'SN002345678903', '354409234567892', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(7, 3, 'SN003456789012', '358940123456789', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(8, 3, 'SN003456789013', '358940123456790', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(9, 3, 'SN003456789014', '358940123456791', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(10, 4, 'SN004567890123', NULL, 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(11, 4, 'SN004567890124', NULL, 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(12, 4, 'SN004567890125', NULL, 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(13, 5, 'SN005123456789', NULL, 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(14, 5, 'SN005123456790', NULL, 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(15, 5, 'SN005123456791', NULL, 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(16, 6, 'SN1769794835406n9ia2s0670', NULL, 'available', '2026-01-30 17:40:35', '2026-01-30 17:40:35', NULL),
(17, 6, 'SN1769794845040w0z69qykk0', NULL, 'available', '2026-01-30 17:40:45', '2026-01-30 17:40:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `is_active`, `created_at`) VALUES
(1, 'Global Tech Supplies', 'Ahmed Mohamed', '+966-50-123-4567', 'ahmed@globaltech.sa', 'Riyadh, Saudi Arabia', 1, '2026-01-24 00:14:16'),
(2, 'Mobile Parts Warehouse', 'Salem Abdullah', '+966-55-987-6543', 'salem@mobileparts.sa', 'Jeddah, Saudi Arabia', 1, '2026-01-24 00:14:16'),
(3, 'Electronics Import Co', 'Khalid Omar', '+966-58-456-7890', 'khalid@electronics.sa', 'Dammam, Saudi Arabia', 1, '2026-01-24 00:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin', 'admin123', 'System Administrator', 'admin', 1, '2026-01-24 00:14:16'),
(2, 'staff1', 'staff123', 'Ahmed Hassan', 'staff', 1, '2026-01-24 00:14:16'),
(3, 'staff2', 'staff123', 'Sarah Johnson', 'staff', 1, '2026-01-24 00:14:16');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `stock_items`
--
ALTER TABLE `stock_items`
  ADD CONSTRAINT `fk_stock_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
