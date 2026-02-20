-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 20, 2026 at 05:40 PM
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

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `GetProductStock`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetProductStock` (IN `product_id` INT)   BEGIN
    SELECT 
        p.id,
        p.code,
        p.brand,
        p.model,
        p.stock,
        p.min_stock,
        c.name as category_name,
        b.name as branch_name,
        CASE 
            WHEN p.stock <= p.min_stock THEN 'Low Stock'
            WHEN p.stock = 0 THEN 'Out of Stock'
            ELSE 'In Stock'
        END as stock_status
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN branches b ON p.branch_id = b.id
    WHERE p.id = product_id;
END$$

DROP PROCEDURE IF EXISTS `GetSalesByPeriod`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSalesByPeriod` (IN `start_date` DATE, IN `end_date` DATE)   BEGIN
    SELECT 
        s.id,
        s.receipt_number,
        c.name as customer_name,
        u.name as staff_name,
        b.name as branch_name,
        s.total_amount,
        s.payment_method,
        s.sale_date,
        COUNT(si.id) as item_count
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.staff_id = u.id
    LEFT JOIN branches b ON s.branch_id = b.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE DATE(s.sale_date) BETWEEN start_date AND end_date
    GROUP BY s.id
    ORDER BY s.sale_date DESC;
END$$

DROP PROCEDURE IF EXISTS `GetTopProducts`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTopProducts` (IN `limit_count` INT)   BEGIN
    SELECT 
        p.id,
        p.code,
        p.brand,
        p.model,
        c.name as category_name,
        COALESCE(SUM(si.quantity), 0) as total_sold,
        COALESCE(SUM(si.total_price), 0) as total_revenue
    FROM products p
    LEFT JOIN sale_items si ON p.id = si.product_id
    LEFT JOIN sales s ON si.sale_id = s.id
    WHERE p.is_active = TRUE
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT limit_count;
END$$

--
-- Functions
--
DROP FUNCTION IF EXISTS `CalculateStockValue`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `CalculateStockValue` (`product_id` INT) RETURNS DECIMAL(10,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE stock_value DECIMAL(10,2);
    SELECT stock * purchase_price INTO stock_value
    FROM products
    WHERE id = product_id;
    RETURN stock_value;
END$$

DROP FUNCTION IF EXISTS `GetTotalStockValue`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `GetTotalStockValue` () RETURNS DECIMAL(15,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE total_value DECIMAL(15,2);
    SELECT SUM(stock * purchase_price) INTO total_value
    FROM products
    WHERE is_active = TRUE;
    RETURN total_value;
END$$

DELIMITER ;

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `location`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Branch', '123 Main Street, City, Country', '+0123456789', 'main@ibs.com', 'Primary business location', 1, '2026-02-08 13:50:00', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `logo_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_brands_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `logo_url`, `website`, `contact_email`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Apple', 'Apple Inc. - Technology company designing consumer electronics', NULL, 'https://www.apple.com', 'support@apple.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(2, 'Samsung', 'Samsung Electronics - Global technology company', NULL, 'https://www.samsung.com', 'support@samsung.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(3, 'Xiaomi', 'Xiaomi Corporation - Chinese electronics company', NULL, 'https://www.mi.com', 'support@xiaomi.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(4, 'OnePlus', 'OnePlus Technology - Smartphone manufacturer', NULL, 'https://www.oneplus.com', 'support@oneplus.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(5, 'Huawei', 'Huawei Technologies - Chinese multinational technology company', NULL, 'https://www.huawei.com', 'support@huawei.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Phones', 'Mobile phones and smartphones', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(2, 'AirPods', 'Apple wireless earbuds and headphones', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(3, 'Watch', 'Smart watches and wearable devices', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(4, 'Accessories', 'Phone accessories like cases, chargers, cables, etc.', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(5, 'Tablets', 'Tablet devices', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45');

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
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customers_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `branch_id`, `total_purchases`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ahmed Hassan', '+966-50-111-2222', 'ahmed@email.com', 'Riyadh, Saudi Arabia', 1, 1000.00, 1, '2026-01-24 00:14:16', '2026-02-14 19:18:19'),
(2, 'Sarah Johnson', '+966-55-333-4444', 'sarah@email.com', 'Jeddah, Saudi Arabia', 1, 0.00, 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(3, 'Mohammed Ali', '+966-58-555-6666', 'mohammed@email.com', 'Dammam, Saudi Arabia', 1, 200.00, 1, '2026-01-24 00:14:16', '2026-02-18 22:04:39');

-- --------------------------------------------------------

--
-- Stand-in structure for view `financial_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `financial_summary`;
CREATE TABLE IF NOT EXISTS `financial_summary` (
`date` date
,`total_income` decimal(32,2)
,`total_expenses` decimal(32,2)
,`net_profit` decimal(33,2)
,`branch_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

DROP TABLE IF EXISTS `income`;
CREATE TABLE IF NOT EXISTS `income` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `category_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `date` date NOT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_income_date` (`entry_date`),
  KEY `idx_income_branch` (`branch_id`),
  KEY `fk_income_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`id`, `amount`, `description`, `price`, `category_id`, `branch_id`, `date`, `entry_date`, `created_at`, `updated_at`) VALUES
(1, 5000.00, 'Initial investment', 5000.00, NULL, NULL, '2026-01-01', '2026-01-01', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(2, 3000.00, 'Additional capital', 3000.00, NULL, NULL, '2026-01-15', '2026-01-15', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(3, 2000.00, 'Loan received', 2000.00, NULL, NULL, '2026-01-20', '2026-01-20', '2026-01-22 23:19:00', '2026-02-13 23:06:45');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `low_stock_alerts`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `low_stock_alerts`;
CREATE TABLE IF NOT EXISTS `low_stock_alerts` (
`id` int
,`code` varchar(20)
,`brand` varchar(50)
,`model` varchar(100)
,`stock` int
,`min_stock` int
,`category_name` varchar(50)
,`branch_name` varchar(100)
,`is_low_stock` int
);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `date` date NOT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_date` (`entry_date`),
  KEY `idx_payment_branch` (`branch_id`),
  KEY `fk_payment_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `amount`, `description`, `price`, `payment_method`, `reference_number`, `category_id`, `branch_id`, `date`, `entry_date`, `created_at`, `updated_at`) VALUES
(1, 1500.00, 'Shop rent', 1500.00, NULL, NULL, NULL, NULL, '2026-01-05', '2026-01-05', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(2, 800.00, 'Electricity bill', 800.00, NULL, NULL, NULL, NULL, '2026-01-10', '2026-01-10', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(3, 1200.00, 'Staff salaries', 1200.00, NULL, NULL, NULL, NULL, '2026-01-15', '2026-01-15', '2026-01-22 23:19:00', '2026-02-13 23:06:45');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `brand_id` int DEFAULT NULL,
  `has_imei` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_products_barcode` (`barcode`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_supplier` (`supplier_id`),
  KEY `idx_products_branch` (`branch_id`),
  KEY `idx_products_brand` (`brand_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `barcode`, `brand`, `model`, `suggested_price`, `purchase_price`, `min_selling_price`, `stock`, `min_stock`, `category`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`, `category_id`, `branch_id`, `serial_number`, `imei`, `color`, `supplier_id`, `brand_id`, `has_imei`) VALUES
(1, 'IBS001', NULL, 'Apple', 'iPhone 15 Pro', 3999.00, 2799.00, 3199.00, 24, 5, NULL, 'Latest iPhone with A17 Pro chip', NULL, 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45', 1, 1, 'SN001234567890', '354409123456789', 'Space Gray', 1, 1, 1),
(2, 'IBS002', NULL, 'Apple', 'iPhone 15', 3199.00, 2239.00, 2559.00, 3, 5, NULL, 'iPhone 15 with Dynamic Island', NULL, 1, '2026-01-24 00:14:16', '2026-02-14 18:23:43', 1, 1, 'SN002345678901', '354409234567890', 'Blue', 2, 1, 1),
(3, 'IBS003', NULL, 'Samsung', 'Galaxy S24 Ultra', 4799.00, 3359.00, 3839.00, 17, 5, NULL, 'Premium Galaxy with S Pen', NULL, 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45', 1, 1, 'SN003456789012', '358940123456789', 'Black', 3, 2, 1),
(4, 'IBS004', NULL, 'Apple', 'iPad Air', 2399.00, 1679.00, 1919.00, 1, 3, NULL, 'iPad Air with M1 chip', NULL, 1, '2026-01-24 00:14:16', '2026-02-14 18:19:36', 5, 1, 'SN004567890123', NULL, 'Space Gray', 2, 1, 0),
(5, 'IBS005', NULL, 'Samsung', 'Galaxy Watch 6', 1319.00, 923.00, 1055.00, 15, 3, NULL, 'Latest Galaxy smartwatch', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 3, 1, 'SN005123456789', NULL, 'Black', 1, 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(20) NOT NULL,
  `sale_number` varchar(50) NOT NULL DEFAULT '',
  `customer_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `is_split_payment` tinyint(1) DEFAULT '0',
  `total_paid` decimal(10,2) GENERATED ALWAYS AS (`total_amount`) STORED,
  `sale_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `idx_sales_customer` (`customer_id`),
  KEY `idx_sales_staff` (`staff_id`),
  KEY `idx_sales_branch` (`branch_id`),
  KEY `idx_sales_date` (`sale_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `receipt_number`, `sale_number`, `customer_id`, `staff_id`, `branch_id`, `total_amount`, `payment_method`, `is_split_payment`, `sale_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'IBS-20260124-001', '', 1, 1, NULL, 3999.00, 'Cash', 0, '2026-01-24 00:14:16', NULL, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(2, 'IBS-20260124-002', '', 2, 2, NULL, 2799.00, 'Visa', 0, '2026-01-24 00:14:16', NULL, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(3, 'IBS-20260124-003', '', 3, 1, NULL, 4799.00, 'Cash', 0, '2026-01-24 00:14:16', NULL, '2026-01-24 00:14:16', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `sales_summary`;
CREATE TABLE IF NOT EXISTS `sales_summary` (
`sale_date` date
,`total_sales` bigint
,`total_revenue` decimal(32,2)
,`avg_sale_amount` decimal(14,6)
,`branch_name` varchar(100)
);

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sale_items_sale` (`sale_id`),
  KEY `idx_sale_items_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 1, 1, 3999.00, 3999.00, '2026-01-24 00:14:16'),
(2, 2, 2, 1, 2799.00, 2799.00, '2026-01-24 00:14:16'),
(3, 3, 3, 1, 4799.00, 4799.00, '2026-01-24 00:14:16');

--
-- Triggers `sale_items`
--
DROP TRIGGER IF EXISTS `restore_stock_on_sale_delete`;
DELIMITER $$
CREATE TRIGGER `restore_stock_on_sale_delete` AFTER DELETE ON `sale_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET stock = stock + OLD.quantity 
    WHERE id = OLD.product_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_stock_on_sale`;
DELIMITER $$
CREATE TRIGGER `update_stock_on_sale` AFTER INSERT ON `sale_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET stock = stock - NEW.quantity 
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_stock_on_sale_item_update`;
DELIMITER $$
CREATE TRIGGER `update_stock_on_sale_item_update` AFTER UPDATE ON `sale_items` FOR EACH ROW BEGIN
    IF NEW.quantity != OLD.quantity THEN
        UPDATE products 
        SET stock = stock + (OLD.quantity - NEW.quantity) 
        WHERE id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;

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
  `item_identifier` varchar(100) NOT NULL DEFAULT '',
  `item_type` enum('imei','serial','other') DEFAULT 'other',
  `status` enum('available','sold','reserved','damaged') DEFAULT 'available',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sale_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_serial` (`serial_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `fk_stock_items_sale` (`sale_id`),
  KEY `idx_stock_items_identifier` (`item_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_items`
--

INSERT INTO `stock_items` (`id`, `product_id`, `serial_number`, `imei`, `item_identifier`, `item_type`, `status`, `notes`, `created_at`, `updated_at`, `sale_id`) VALUES
(1, 1, 'SN001234567890', '354409123456789', '354409123456789', 'imei', 'available', NULL, '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(2, 1, 'SN001234567891', '354409123456790', '354409123456790', 'imei', 'available', NULL, '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(3, 2, 'SN002345678901', '354409234567890', '354409234567890', 'imei', 'available', NULL, '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(4, 3, 'SN003456789012', '358940123456789', '358940123456789', 'imei', 'available', NULL, '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(5, 4, 'SN1771093176262vo83wydgh0', NULL, '', 'other', 'available', NULL, '2026-02-14 18:19:36', '2026-02-14 18:19:36', NULL),
(6, 2, 'SN1771093423056316o8qecp', '5555', '', 'other', 'available', NULL, '2026-02-14 18:23:43', '2026-02-14 18:23:43', NULL),
(7, 2, 'SN1771093423056dld1t6kn4', '8888888888888', '', 'other', 'available', NULL, '2026-02-14 18:23:43', '2026-02-14 18:23:43', NULL);

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Global Tech Supplies', 'Ahmed Mohamed', '+966-50-123-4567', 'ahmed@globaltech.sa', 'Riyadh, Saudi Arabia', 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(2, 'Mobile Parts Warehouse', 'Salem Abdullah', '+966-55-987-6543', 'salem@mobileparts.sa', 'Jeddah, Saudi Arabia', 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(3, 'Electronics Import Co', 'Khalid Omar', '+966-58-456-7890', 'khalid@electronics.sa', 'Dammam, Saudi Arabia', 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45');

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
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('owner','admin','staff') NOT NULL DEFAULT 'staff',
  `branch_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `phone`, `role`, `branch_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'owner', 'owner123', 'System Owner', 'owner@ibs.com', '+1234567890', 'owner', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45'),
(2, 'admin', 'admin123', 'System Administrator', 'admin@ibs.com', '+1234567891', 'admin', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45'),
(3, 'staff1', 'staff123', 'Ahmed Hassan', 'ahmed@email.com', '+966-50-111-2222', 'staff', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45'),
(4, 'staff2', 'staff123', 'Sarah Johnson', 'sarah@email.com', '+966-55-333-4444', 'staff', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Structure for view `financial_summary`
--
DROP TABLE IF EXISTS `financial_summary`;

DROP VIEW IF EXISTS `financial_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `financial_summary`  AS SELECT `combined_data`.`date` AS `date`, sum(`combined_data`.`total_income`) AS `total_income`, sum(`combined_data`.`total_expenses`) AS `total_expenses`, (sum(`combined_data`.`total_income`) - sum(`combined_data`.`total_expenses`)) AS `net_profit`, `combined_data`.`branch_name` AS `branch_name` FROM (select cast(`i`.`entry_date` as date) AS `date`,coalesce(`i`.`price`,`i`.`amount`) AS `total_income`,0 AS `total_expenses`,coalesce(`b`.`name`,'Unknown') AS `branch_name` from (`income` `i` left join `branches` `b` on((`i`.`branch_id` = `b`.`id`))) union all select cast(`p`.`entry_date` as date) AS `date`,0 AS `total_income`,coalesce(`p`.`price`,`p`.`amount`) AS `total_expenses`,coalesce(`b`.`name`,'Unknown') AS `branch_name` from (`payments` `p` left join `branches` `b` on((`p`.`branch_id` = `b`.`id`)))) AS `combined_data` GROUP BY `combined_data`.`date`, `combined_data`.`branch_name` ORDER BY `combined_data`.`date` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `low_stock_alerts`
--
DROP TABLE IF EXISTS `low_stock_alerts`;

DROP VIEW IF EXISTS `low_stock_alerts`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `low_stock_alerts`  AS SELECT `p`.`id` AS `id`, `p`.`code` AS `code`, `p`.`brand` AS `brand`, `p`.`model` AS `model`, `p`.`stock` AS `stock`, `p`.`min_stock` AS `min_stock`, `c`.`name` AS `category_name`, `b`.`name` AS `branch_name`, (`p`.`stock` <= `p`.`min_stock`) AS `is_low_stock` FROM ((`products` `p` left join `categories` `c` on((`p`.`category_id` = `c`.`id`))) left join `branches` `b` on((`p`.`branch_id` = `b`.`id`))) WHERE (`p`.`is_active` = true) ORDER BY (`p`.`stock` / `p`.`min_stock`) ASC ;

-- --------------------------------------------------------

--
-- Structure for view `sales_summary`
--
DROP TABLE IF EXISTS `sales_summary`;

DROP VIEW IF EXISTS `sales_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_summary`  AS SELECT cast(`s`.`sale_date` as date) AS `sale_date`, count(`s`.`id`) AS `total_sales`, coalesce(sum(`s`.`total_amount`),0) AS `total_revenue`, avg(`s`.`total_amount`) AS `avg_sale_amount`, `b`.`name` AS `branch_name` FROM (`sales` `s` left join `branches` `b` on((`s`.`branch_id` = `b`.`id`))) GROUP BY cast(`s`.`sale_date` as date), `b`.`name` ORDER BY `sale_date` DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `income`
--
ALTER TABLE `income`
  ADD CONSTRAINT `fk_income_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_income_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payment_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_splits`
--
ALTER TABLE `payment_splits`
  ADD CONSTRAINT `fk_payment_splits_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_items`
--
ALTER TABLE `stock_items`
  ADD CONSTRAINT `fk_stock_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stock_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

DELIMITER $$
--
-- Events
--
DROP EVENT IF EXISTS `audit_log_sales`$$
CREATE DEFINER=`root`@`localhost` EVENT `audit_log_sales` ON SCHEDULE EVERY 1 HOUR STARTS '2026-02-14 00:48:37' ON COMPLETION NOT PRESERVE ENABLE DO INSERT INTO payment (description, amount, payment_method, reference_number, category_id, branch_id, entry_date, notes)
    SELECT 
        CONCAT('Sales Audit - ', COUNT(*), ' sales'),
        COALESCE(SUM(total_amount), 0),
        'Audit',
        CONCAT('AUDIT-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),
        4,
        1,
        DATE(NOW()),
        'Automated sales audit log'
    FROM sales
    WHERE DATE(sale_date) = DATE(NOW())$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
