-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 09, 2026 at 12:12 AM
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
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `location`, `phone`, `email`, `address`, `is_active`, `created_at`) VALUES
(1, 'Main Branch', '123 Main Street, City, Country', '+0123456789', 'main@ibs.com', 'Primary business location', 1, '2026-02-08 13:50:00');

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
  `branch_id` int DEFAULT NULL,
  `total_purchases` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `branch_id`, `total_purchases`, `created_at`) VALUES
(1, 'Ahmed Hassan', '+966-50-111-2222', 'ahmed@email.com', 'Riyadh, Saudi Arabia', 1, 0.00, '2026-01-24 00:14:16'),
(2, 'Sarah Johnson', '+966-55-333-4444', 'sarah@email.com', 'Jeddah, Saudi Arabia', 1, 0.00, '2026-01-24 00:14:16'),
(3, 'Mohammed Ali', '+966-58-555-6666', 'mohammed@email.com', 'Dammam, Saudi Arabia', 1, 0.00, '2026-01-24 00:14:16');

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
-- Table structure for table `payment_splits`
--

DROP TABLE IF EXISTS `payment_splits`;
CREATE TABLE IF NOT EXISTS `payment_splits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `installment_details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_splits_sale_id` (`sale_id`),
  KEY `idx_payment_splits_method` (`payment_method`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_splits`
--

INSERT INTO `payment_splits` (`id`, `sale_id`, `payment_method`, `amount`, `reference_number`, `installment_details`, `created_at`) VALUES
(1, 4, 'Cash', 800.00, NULL, NULL, '2026-02-09 00:12:24'),
(2, 4, 'Visa', 799.00, '****-****-****-1234', NULL, '2026-02-09 00:12:24'),
(3, 5, 'Instapay', 1000.00, 'INST-20260209-001', NULL, '2026-02-09 00:12:24'),
(4, 5, 'Installment', 2999.00, '6 months - 0% interest', NULL, '2026-02-09 00:12:24'),
(5, 6, 'Cash', 500.00, NULL, NULL, '2026-02-09 00:12:24'),
(6, 6, 'Visa', 1899.00, '****-****-****-5678', NULL, '2026-02-09 00:12:24');

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
  `branch_id` int DEFAULT NULL,
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

INSERT INTO `products` (`id`, `code`, `barcode`, `brand`, `model`, `suggested_price`, `purchase_price`, `min_selling_price`, `stock`, `min_stock`, `category`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`, `category_id`, `branch_id`, `serial_number`, `imei`, `color`, `supplier_id`, `has_imei`) VALUES
(1, 'IBS001', NULL, 'Apple', 'iPhone 15 Pro', 3999.00, 2799.00, 3199.00, 25, NULL, NULL, 'Latest iPhone with A17 Pro chip', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 1, 1, 'SN001234567890', '354409123456789', 'Space Gray', 1, 1),
(2, 'IBS002', NULL, 'Apple', 'iPhone 15', 3199.00, 2239.00, 2559.00, 30, NULL, NULL, 'iPhone 15 with Dynamic Island', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 1, 1, 'SN002345678901', '354409234567890', 'Blue', 2, 1),
(3, 'IBS003', NULL, 'Samsung', 'Galaxy S24 Ultra', 4799.00, 3359.00, 3839.00, 18, NULL, NULL, 'Premium Galaxy with S Pen', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 1, 1, 'SN003456789012', '358940123456789', 'Black', 3, 1),
(4, 'IBS004', NULL, 'Apple', 'iPad Air', 2399.00, 1679.00, 1919.00, 12, NULL, NULL, 'iPad Air with M1 chip', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 5, 1, 'SN004567890123', NULL, 'Space Gray', 2, 0),
(5, 'IBS005', NULL, 'Samsung', 'Galaxy Watch 6', 1319.00, 923.00, 1055.00, 15, NULL, NULL, 'Latest Galaxy smartwatch', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 3, 1, 'SN005123456789', NULL, 'Black', 1, 0),
(6, 'IBS006', NULL, 'iphone ', '17 pro', 99999999.99, 99999999.99, 99999999.99, 2, 2, NULL, '', '', 1, '2026-01-24 00:15:54', '2026-02-08 15:51:44', 1, 1, NULL, NULL, NULL, NULL, 0);

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
  `is_split_payment` tinyint(1) DEFAULT '0',
  `total_paid` decimal(10,2) GENERATED ALWAYS AS (`total_amount`) STORED,
  `sale_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `receipt_number`, `customer_id`, `staff_id`, `total_amount`, `payment_method`, `is_split_payment`, `sale_date`, `created_at`) VALUES
(1, 'IBS-20260124-001', 1, 1, 3999.00, 'Cash', 0, '2026-01-24 00:14:16', '2026-01-24 00:14:16'),
(2, 'IBS-20260124-002', 2, 2, 2799.00, 'Visa', 0, '2026-01-24 00:14:16', '2026-01-24 00:14:16'),
(3, 'IBS-20260124-003', 3, 1, 4799.00, 'Cash', 0, '2026-01-24 00:14:16', '2026-01-24 00:14:16'),
(4, 'IBS-20260209-004', 1, 1, 1599.00, 'Instapay', 1, '2026-02-09 08:30:00', '2026-02-09 08:30:00'),
(5, 'IBS-20260209-005', 2, 2, 3999.00, 'Installment', 1, '2026-02-09 09:15:00', '2026-02-09 09:15:00'),
(6, 'IBS-20260209-006', 3, 1, 2399.00, 'Visa', 1, '2026-02-09 10:00:00', '2026-02-09 10:00:00');

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 1, 3999.00, 3999.00),
(2, 2, 2, 1, 2799.00, 2799.00),
(3, 3, 3, 1, 4799.00, 4799.00),
(4, 4, 5, 1, 1599.00, 1599.00),
(5, 5, 1, 1, 3999.00, 3999.00),
(6, 6, 4, 1, 2399.00, 2399.00);

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
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(17, 6, 'SN1769794845040w0z69qykk0', NULL, 'available', '2026-01-30 17:40:45', '2026-01-30 17:40:45', NULL),
(18, 5, 'SN1769942218433u3zwwuz2p0', NULL, 'available', '2026-02-01 10:36:58', '2026-02-01 10:36:58', NULL),
(19, 5, 'SN17699422184334oq6vp9x91', NULL, 'available', '2026-02-01 10:36:58', '2026-02-01 10:36:58', NULL),
(20, 5, 'SN1769942218433v0ehm85122', NULL, 'available', '2026-02-01 10:36:58', '2026-02-01 10:36:58', NULL),
(21, 5, 'SN1769942218433clkwncydp3', NULL, 'available', '2026-02-01 10:36:58', '2026-02-01 10:36:58', NULL),
(22, 5, 'SN1769942218433zimypzcg44', NULL, 'available', '2026-02-01 10:36:58', '2026-02-01 10:36:58', NULL),
(25, 7, 'SN176994226326250w1ky42w2', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(26, 7, 'SN1769942263262vy0wgzxfo3', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(27, 7, 'SN17699422632620hbw6moa04', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(28, 7, 'SN1769942263262swe6u808t5', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(29, 7, 'SN1769942263262hzno8kb3x6', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(30, 7, 'SN17699422632620u8vqwfc77', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(31, 7, 'SN17699422632625ut00ogk88', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(32, 7, 'SN1769942263262hw20ihini9', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(33, 7, 'SN17699422632623e3x4zzv010', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(34, 7, 'SN17699422632621nlh82h4z11', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(35, 7, 'SN1769942263262h44dpkm9c12', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(36, 7, 'SN1769942263262kj8ojq7hh13', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(37, 7, 'SN1769942263262xzfn5ufxt14', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(38, 7, 'SN1769942263262j5opksqgd15', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(39, 7, 'SN17699422632624mv2t8w8f16', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(40, 7, 'SN17699422632621a0kq3nu317', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(41, 7, 'SN1769942263262y3vx0klij18', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(42, 7, 'SN1769942263262uuks585jd19', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(43, 7, 'SN1769942263262r9czq6zqv20', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(44, 7, 'SN1769942263262tzzyh7hyi21', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(45, 7, 'SN17699422632627y2pe7ar322', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(46, 7, 'SN1769942263262o1x8zlqzr23', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(47, 7, 'SN1769942263262l7uqm7ql824', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(48, 7, 'SN17699422632628wiiks0iw25', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(49, 7, 'SN1769942263262iu8x1hu7p26', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(50, 7, 'SN1769942263262ys8h9nrp327', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(51, 7, 'SN176994226326284u7vtrwz28', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(52, 7, 'SN1769942263262jlcp2dgq229', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(53, 7, 'SN1769942263262k4ilzur4130', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(54, 7, 'SN1769942263262hh5cya1au31', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(55, 7, 'SN1769942263262ksv4ph52032', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(56, 7, 'SN1769942263262jn1yl4zev33', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(57, 7, 'SN1769942263262kicq5h2w134', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(58, 7, 'SN1769942263262kzlv078ff35', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(59, 7, 'SN1769942263262uch5k2cno36', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(60, 7, 'SN17699422632621akpmi77b37', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(61, 7, 'SN1769942263262z5y8xi5ds38', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(62, 7, 'SN1769942263262pw9gkh2ci39', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(63, 7, 'SN1769942263262toobddxnt40', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(64, 7, 'SN17699422632625ahc8olae41', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(65, 7, 'SN1769942263262hxtwpfmw542', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(67, 7, 'SN1769942263262jyu53zkda44', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(71, 7, 'SN17699422632623hyhmrjes48', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(72, 7, 'SN1769942263262vret7rdsm49', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(73, 7, 'SN1769942263262sxhxl0gji50', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(74, 7, 'SN17699422632623mc21t5zd51', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(75, 7, 'SN1769942263262a9nsxqokf52', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(77, 7, 'SN1769942263262kjln97u9r54', NULL, 'available', '2026-02-01 10:37:43', '2026-02-01 10:37:43', NULL),
(78, 8, 'SN1770303007703tnyyxx9bm0', NULL, 'available', '2026-02-05 14:50:07', '2026-02-05 14:50:07', NULL),
(80, 8, 'SN17703039251671zzt3t3pq0', NULL, 'available', '2026-02-05 15:05:25', '2026-02-05 15:05:25', NULL);

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
  `role` enum('owner','admin','staff') NOT NULL DEFAULT 'staff',
  `branch_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `branch_id`, `is_active`, `created_at`) VALUES
(1, 'owner', 'owner123', 'System Owner', 'owner', 1, 1, '2026-01-23 22:14:16'),
(2, 'admin', 'admin123', 'System Administrator', 'admin', 1, 1, '2026-01-23 22:14:16'),
(3, 'staff1', 'staff123', 'Ahmed Hassan', 'staff', 1, 1, '2026-01-23 22:14:16'),
(4, 'staff2', 'staff123', 'Sarah Johnson', 'staff', 1, 1, '2026-01-23 22:14:16');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payment_splits`
--
ALTER TABLE `payment_splits`
  ADD CONSTRAINT `fk_payment_splits_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_items`
--
ALTER TABLE `stock_items`
  ADD CONSTRAINT `fk_stock_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
