-- =====================================================
-- IBS - Inventory Management System Complete Database
-- =====================================================
-- This file contains the complete database structure and data
-- Created: 2026-02-14
-- Version: 2.0 (Consolidated)
-- =====================================================

-- Database setup
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Drop existing tables if they exist (for fresh installation)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS payment_splits;
DROP TABLE IF EXISTS stock_items;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS income_entries;
DROP TABLE IF EXISTS payment_entries;
DROP TABLE IF EXISTS income;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS branches;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- TABLE CREATION
-- =====================================================

-- Create branches table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create users table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create customers table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create suppliers table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create brands table
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL UNIQUE,
  `description` text,
  `logo_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_brands_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create products table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create stock_items table for individual item tracking (IMEI, serial numbers)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create sales table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create sale_items table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create income table
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
  KEY `idx_income_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create income_entries table
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

-- Create payments table (expenses)
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
  KEY `idx_payment_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create payment_entries table
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

-- Create payment_splits table
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

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
-- =====================================================

ALTER TABLE `users` ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
ALTER TABLE `products` ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
ALTER TABLE `products` ADD CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;
ALTER TABLE `products` ADD CONSTRAINT `fk_products_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
ALTER TABLE `products` ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;
ALTER TABLE `stock_items` ADD CONSTRAINT `fk_stock_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
ALTER TABLE `stock_items` ADD CONSTRAINT `fk_stock_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `sales` ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;
ALTER TABLE `sales` ADD CONSTRAINT `fk_sales_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
ALTER TABLE `sales` ADD CONSTRAINT `fk_sales_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT;
ALTER TABLE `sale_items` ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;
ALTER TABLE `sale_items` ADD CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;
ALTER TABLE `income` ADD CONSTRAINT `fk_income_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
ALTER TABLE `income` ADD CONSTRAINT `fk_income_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
ALTER TABLE `payments` ADD CONSTRAINT `fk_payment_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
ALTER TABLE `payments` ADD CONSTRAINT `fk_payment_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
ALTER TABLE `payment_splits` ADD CONSTRAINT `fk_payment_splits_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

-- =====================================================
-- TRIGGERS FOR STOCK MANAGEMENT
-- =====================================================

DELIMITER //

CREATE TRIGGER update_stock_on_sale 
AFTER INSERT ON sale_items
FOR EACH ROW
BEGIN
    UPDATE products 
    SET stock = stock - NEW.quantity 
    WHERE id = NEW.product_id;
END//

CREATE TRIGGER restore_stock_on_sale_delete
AFTER DELETE ON sale_items
FOR EACH ROW
BEGIN
    UPDATE products 
    SET stock = stock + OLD.quantity 
    WHERE id = OLD.product_id;
END//

CREATE TRIGGER update_stock_on_sale_item_update
AFTER UPDATE ON sale_items
FOR EACH ROW
BEGIN
    IF NEW.quantity != OLD.quantity THEN
        UPDATE products 
        SET stock = stock + (OLD.quantity - NEW.quantity) 
        WHERE id = NEW.product_id;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Drop existing views if they exist
DROP VIEW IF EXISTS low_stock_alerts;
DROP VIEW IF EXISTS sales_summary;
DROP VIEW IF EXISTS financial_summary;

-- Create view for low stock alerts
CREATE VIEW low_stock_alerts AS
SELECT 
    p.id,
    p.code,
    p.brand,
    p.model,
    p.stock,
    p.min_stock,
    c.name as category_name,
    b.name as branch_name,
    (p.stock <= p.min_stock) as is_low_stock
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN branches b ON p.branch_id = b.id
WHERE p.is_active = TRUE
ORDER BY (p.stock / p.min_stock) ASC;

-- Create view for sales summary
CREATE VIEW sales_summary AS
SELECT 
    DATE(s.sale_date) as sale_date,
    COUNT(s.id) as total_sales,
    COALESCE(SUM(s.total_amount), 0) as total_revenue,
    AVG(s.total_amount) as avg_sale_amount,
    b.name as branch_name
FROM sales s
LEFT JOIN branches b ON s.branch_id = b.id
GROUP BY DATE(s.sale_date), b.name
ORDER BY sale_date DESC;

-- Create view for financial summary
CREATE VIEW financial_summary AS
SELECT 
    date,
    SUM(total_income) as total_income,
    SUM(total_expenses) as total_expenses,
    SUM(total_income) - SUM(total_expenses) as net_profit,
    branch_name
FROM (
    -- Income records
    SELECT 
        DATE(i.entry_date) as date,
        COALESCE(i.price, i.amount) as total_income,
        0 as total_expenses,
        COALESCE(b.name, 'Unknown') as branch_name
    FROM income i
    LEFT JOIN branches b ON i.branch_id = b.id
    
    UNION ALL
    
    -- Payment (expense) records
    SELECT 
        DATE(p.entry_date) as date,
        0 as total_income,
        COALESCE(p.price, p.amount) as total_expenses,
        COALESCE(b.name, 'Unknown') as branch_name
    FROM payments p
    LEFT JOIN branches b ON p.branch_id = b.id
) combined_data
GROUP BY date, branch_name
ORDER BY date DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Drop existing procedures if they exist
DROP PROCEDURE IF EXISTS GetProductStock;
DROP PROCEDURE IF EXISTS GetSalesByPeriod;
DROP PROCEDURE IF EXISTS GetTopProducts;

DELIMITER //

CREATE PROCEDURE GetProductStock(IN product_id INT)
BEGIN
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
END//

CREATE PROCEDURE GetSalesByPeriod(IN start_date DATE, IN end_date DATE)
BEGIN
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
END//

CREATE PROCEDURE GetTopProducts(IN limit_count INT)
BEGIN
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
END//

DELIMITER ;

-- =====================================================
-- FUNCTIONS
-- =====================================================

-- Drop existing functions if they exist
DROP FUNCTION IF EXISTS CalculateStockValue;
DROP FUNCTION IF EXISTS GetTotalStockValue;

DELIMITER //

CREATE FUNCTION CalculateStockValue(product_id INT) 
RETURNS DECIMAL(10,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE stock_value DECIMAL(10,2);
    SELECT stock * purchase_price INTO stock_value
    FROM products
    WHERE id = product_id;
    RETURN stock_value;
END//

CREATE FUNCTION GetTotalStockValue() 
RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_value DECIMAL(15,2);
    SELECT SUM(stock * purchase_price) INTO total_value
    FROM products
    WHERE is_active = TRUE;
    RETURN total_value;
END//

DELIMITER ;

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert branches
INSERT INTO `branches` (`id`, `name`, `location`, `phone`, `email`, `address`, `is_active`, `created_at`) VALUES
(1, 'Main Branch', '123 Main Street, City, Country', '+0123456789', 'main@ibs.com', 'Primary business location', 1, '2026-02-08 13:50:00');

-- Insert categories
INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Phones', 'Mobile phones and smartphones', 1, '2026-01-24 00:14:15'),
(2, 'AirPods', 'Apple wireless earbuds and headphones', 1, '2026-01-24 00:14:15'),
(3, 'Watch', 'Smart watches and wearable devices', 1, '2026-01-24 00:14:15'),
(4, 'Accessories', 'Phone accessories like cases, chargers, cables, etc.', 1, '2026-01-24 00:14:15'),
(5, 'Tablets', 'Tablet devices', 1, '2026-01-24 00:14:15');

-- Insert brands
INSERT INTO `brands` (`id`, `name`, `description`, `website`, `contact_email`, `is_active`, `created_at`) VALUES
(1, 'Apple', 'Apple Inc. - Technology company designing consumer electronics', 'https://www.apple.com', 'support@apple.com', 1, '2026-01-24 00:14:15'),
(2, 'Samsung', 'Samsung Electronics - Global technology company', 'https://www.samsung.com', 'support@samsung.com', 1, '2026-01-24 00:14:15'),
(3, 'Xiaomi', 'Xiaomi Corporation - Chinese electronics company', 'https://www.mi.com', 'support@xiaomi.com', 1, '2026-01-24 00:14:15'),
(4, 'OnePlus', 'OnePlus Technology - Smartphone manufacturer', 'https://www.oneplus.com', 'support@oneplus.com', 1, '2026-01-24 00:14:15'),
(5, 'Huawei', 'Huawei Technologies - Chinese multinational technology company', 'https://www.huawei.com', 'support@huawei.com', 1, '2026-01-24 00:14:15');

-- Insert suppliers
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `is_active`, `created_at`) VALUES
(1, 'Global Tech Supplies', 'Ahmed Mohamed', '+966-50-123-4567', 'ahmed@globaltech.sa', 'Riyadh, Saudi Arabia', 1, '2026-01-24 00:14:16'),
(2, 'Mobile Parts Warehouse', 'Salem Abdullah', '+966-55-987-6543', 'salem@mobileparts.sa', 'Jeddah, Saudi Arabia', 1, '2026-01-24 00:14:16'),
(3, 'Electronics Import Co', 'Khalid Omar', '+966-58-456-7890', 'khalid@electronics.sa', 'Dammam, Saudi Arabia', 1, '2026-01-24 00:14:16');

-- Insert users
INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `phone`, `role`, `branch_id`, `is_active`, `created_at`) VALUES
(1, 'owner', 'owner123', 'System Owner', 'owner@ibs.com', '+1234567890', 'owner', 1, 1, '2026-01-23 22:14:16'),
(2, 'admin', 'admin123', 'System Administrator', 'admin@ibs.com', '+1234567891', 'admin', 1, 1, '2026-01-23 22:14:16'),
(3, 'staff1', 'staff123', 'Ahmed Hassan', 'ahmed@email.com', '+966-50-111-2222', 'staff', 1, 1, '2026-01-23 22:14:16'),
(4, 'staff2', 'staff123', 'Sarah Johnson', 'sarah@email.com', '+966-55-333-4444', 'staff', 1, 1, '2026-01-23 22:14:16');

-- Insert customers
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `branch_id`, `total_purchases`, `created_at`) VALUES
(1, 'Ahmed Hassan', '+966-50-111-2222', 'ahmed@email.com', 'Riyadh, Saudi Arabia', 1, 0.00, '2026-01-24 00:14:16'),
(2, 'Sarah Johnson', '+966-55-333-4444', 'sarah@email.com', 'Jeddah, Saudi Arabia', 1, 0.00, '2026-01-24 00:14:16'),
(3, 'Mohammed Ali', '+966-58-555-6666', 'mohammed@email.com', 'Dammam, Saudi Arabia', 1, 0.00, '2026-01-24 00:14:16');

-- Insert products
INSERT INTO `products` (`id`, `code`, `barcode`, `brand`, `model`, `suggested_price`, `purchase_price`, `min_selling_price`, `stock`, `min_stock`, `category`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`, `category_id`, `branch_id`, `serial_number`, `imei`, `color`, `supplier_id`, `brand_id`, `has_imei`) VALUES
(1, 'IBS001', NULL, 'Apple', 'iPhone 15 Pro', 3999.00, 2799.00, 3199.00, 25, 5, NULL, 'Latest iPhone with A17 Pro chip', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 1, 1, 'SN001234567890', '354409123456789', 'Space Gray', 1, 1, 1),
(2, 'IBS002', NULL, 'Apple', 'iPhone 15', 3199.00, 2239.00, 2559.00, 30, 5, NULL, 'iPhone 15 with Dynamic Island', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 1, 1, 'SN002345678901', '354409234567890', 'Blue', 2, 1, 1),
(3, 'IBS003', NULL, 'Samsung', 'Galaxy S24 Ultra', 4799.00, 3359.00, 3839.00, 18, 5, NULL, 'Premium Galaxy with S Pen', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 1, 1, 'SN003456789012', '358940123456789', 'Black', 3, 2, 1),
(4, 'IBS004', NULL, 'Apple', 'iPad Air', 2399.00, 1679.00, 1919.00, 12, 3, NULL, 'iPad Air with M1 chip', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 5, 1, 'SN004567890123', NULL, 'Space Gray', 2, 1, 0),
(5, 'IBS005', NULL, 'Samsung', 'Galaxy Watch 6', 1319.00, 923.00, 1055.00, 15, 3, NULL, 'Latest Galaxy smartwatch', NULL, 1, '2026-01-24 00:14:16', '2026-02-08 15:51:44', 3, 1, 'SN005123456789', NULL, 'Black', 1, 2, 0);

-- Insert sample stock items
INSERT INTO `stock_items` (`id`, `product_id`, `serial_number`, `imei`, `item_identifier`, `item_type`, `status`, `created_at`, `updated_at`, `sale_id`) VALUES
(1, 1, 'SN001234567890', '354409123456789', '354409123456789', 'imei', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(2, 1, 'SN001234567891', '354409123456790', '354409123456790', 'imei', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(3, 2, 'SN002345678901', '354409234567890', '354409234567890', 'imei', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL),
(4, 3, 'SN003456789012', '358940123456789', '358940123456789', 'imei', 'available', '2026-01-24 00:14:16', '2026-01-24 00:14:16', NULL);

-- Insert sample sales
INSERT INTO `sales` (`id`, `receipt_number`, `customer_id`, `staff_id`, `total_amount`, `payment_method`, `is_split_payment`, `sale_date`, `created_at`) VALUES
(1, 'IBS-20260124-001', 1, 1, 3999.00, 'Cash', 0, '2026-01-24 00:14:16', '2026-01-24 00:14:16'),
(2, 'IBS-20260124-002', 2, 2, 2799.00, 'Visa', 0, '2026-01-24 00:14:16', '2026-01-24 00:14:16'),
(3, 'IBS-20260124-003', 3, 1, 4799.00, 'Cash', 0, '2026-01-24 00:14:16', '2026-01-24 00:14:16');

-- Insert sample sale items
INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 1, 1, 3999.00, 3999.00, '2026-01-24 00:14:16'),
(2, 2, 2, 1, 2799.00, 2799.00, '2026-01-24 00:14:16'),
(3, 3, 3, 1, 4799.00, 4799.00, '2026-01-24 00:14:16');

-- Insert sample income
INSERT INTO `income` (`id`, `amount`, `description`, `price`, `date`, `entry_date`, `created_at`) VALUES
(1, 5000.00, 'Initial investment', 5000.00, '2026-01-01', '2026-01-01', '2026-01-22 23:19:00'),
(2, 3000.00, 'Additional capital', 3000.00, '2026-01-15', '2026-01-15', '2026-01-22 23:19:00'),
(3, 2000.00, 'Loan received', 2000.00, '2026-01-20', '2026-01-20', '2026-01-22 23:19:00');

-- Insert sample payments (expenses)
INSERT INTO `payments` (`id`, `amount`, `description`, `price`, `date`, `entry_date`, `created_at`) VALUES
(1, 1500.00, 'Shop rent', 1500.00, '2026-01-05', '2026-01-05', '2026-01-22 23:19:00'),
(2, 800.00, 'Electricity bill', 800.00, '2026-01-10', '2026-01-10', '2026-01-22 23:19:00'),
(3, 1200.00, 'Staff salaries', 1200.00, '2026-01-15', '2026-01-15', '2026-01-22 23:19:00');

-- =====================================================
-- SETUP COMPLETION MESSAGE
-- =====================================================

SELECT 'IBS Complete Database created successfully!' as message,
       NOW() as created_at,
       (SELECT COUNT(*) FROM branches) as branches_created,
       (SELECT COUNT(*) FROM users) as users_created,
       (SELECT COUNT(*) FROM products) as products_created,
       (SELECT COUNT(*) FROM sales) as sales_created;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
