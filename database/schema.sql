-- IBS - Inventory Management System Database Schema
-- Created: 2026-02-10
-- Version: 1.0

-- Drop existing tables if they exist (for fresh installation)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS stock_items;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS payment;
DROP TABLE IF EXISTS income;

-- Create branches table
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('owner', 'admin', 'staff') NOT NULL,
    branch_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- Create customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    branch_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- Create suppliers table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(50),
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    supplier_id INT,
    branch_id INT,
    purchase_price DECIMAL(10,2) NOT NULL,
    min_selling_price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- Create stock_items table for individual item tracking (IMEI, serial numbers)
CREATE TABLE stock_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    item_identifier VARCHAR(100) NOT NULL, -- IMEI, serial number, etc.
    item_type ENUM('imei', 'serial', 'other') DEFAULT 'other',
    status ENUM('available', 'sold', 'reserved', 'damaged') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create sales table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT,
    staff_id INT NOT NULL,
    branch_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT
);

-- Create sale_items table
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Create income table
CREATE TABLE income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    branch_id INT,
    entry_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- Create payment table (expenses)
CREATE TABLE payment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    reference_number VARCHAR(100),
    category_id INT,
    branch_id INT,
    entry_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_branch ON users(branch_id);
CREATE INDEX idx_customers_branch ON customers(branch_id);
CREATE INDEX idx_products_code ON products(code);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_supplier ON products(supplier_id);
CREATE INDEX idx_products_branch ON products(branch_id);
CREATE INDEX idx_stock_items_product ON stock_items(product_id);
CREATE INDEX idx_stock_items_identifier ON stock_items(item_identifier);
CREATE INDEX idx_sales_customer ON sales(customer_id);
CREATE INDEX idx_sales_staff ON sales(staff_id);
CREATE INDEX idx_sales_branch ON sales(branch_id);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_sale_items_product ON sale_items(product_id);
CREATE INDEX idx_income_date ON income(entry_date);
CREATE INDEX idx_income_branch ON income(branch_id);
CREATE INDEX idx_payment_date ON payment(entry_date);
CREATE INDEX idx_payment_branch ON payment(branch_id);

-- Insert default data
INSERT INTO branches (name, location, phone, email) VALUES
('Main Branch', '123 Main St, City', '+1234567890', 'main@ibs.com'),
('Branch 2', '456 Oak Ave, Town', '+1234567891', 'branch2@ibs.com');

INSERT INTO users (username, password, name, email, phone, role, branch_id) VALUES
('owner', 'password', 'System Owner', 'owner@ibs.com', '+1234567890', 'owner', 1),
('admin', 'password', 'System Admin', 'admin@ibs.com', '+1234567891', 'admin', 1),
('staff1', 'password', 'Staff Member 1', 'staff1@ibs.com', '+1234567892', 'staff', 1),
('staff2', 'password', 'Staff Member 2', 'staff2@ibs.com', '+1234567893', 'staff', 2);

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Accessories', 'Phone accessories and peripherals'),
('Services', 'Repair and maintenance services'),
('Other', 'Miscellaneous items');

INSERT INTO suppliers (name, contact_person, phone, email) VALUES
('Tech Supplier', 'John Doe', '+1234567890', 'john@techsupplier.com'),
('Accessory Supplier', 'Jane Smith', '+1234567891', 'jane@accessory.com'),
('Service Provider', 'Bob Johnson', '+1234567892', 'bob@service.com');

-- Sample products
INSERT INTO products (code, barcode, brand, model, description, category_id, supplier_id, branch_id, purchase_price, min_selling_price, stock, min_stock) VALUES
('PHONE001', '1234567890123', 'Apple', 'iPhone 14', 'Latest iPhone model', 1, 1, 1, 800.00, 1000.00, 25, 5),
('PHONE002', '1234567890124', 'Samsung', 'Galaxy S23', 'Latest Samsung model', 1, 1, 1, 700.00, 900.00, 30, 5),
('ACC001', '9876543210987', 'Apple', 'iPhone 14 Case', 'Official iPhone case', 2, 2, 1, 20.00, 30.00, 50, 10);

-- Sample customers
INSERT INTO customers (name, email, phone, address, branch_id) VALUES
('John Customer', 'john@email.com', '+1234567890', '123 Customer St, City', 1),
('Jane Customer', 'jane@email.com', '+1234567891', '456 Customer Ave, Town', 1),
('Bob Customer', 'bob@email.com', '+1234567892', '789 Customer Rd, Village', 2);

-- Sample stock items with IMEI numbers
INSERT INTO stock_items (product_id, item_identifier, item_type, status) VALUES
(1, '3512345678901234', 'imei', 'available'),
(1, '3512345678901235', 'imei', 'available'),
(1, '3512345678901236', 'imei', 'available'),
(2, '9876543210987654', 'imei', 'available'),
(2, '9876543210987655', 'imei', 'available');

-- Sample income entries
INSERT INTO income (description, price, category_id, branch_id, entry_date, notes) VALUES
('Service Revenue', 1500.00, 3, 1, CURDATE(), 'Monthly service revenue'),
('Product Sales', 2500.00, 1, 1, CURDATE(), 'Product sales income');

-- Sample payment entries
INSERT INTO payment (description, amount, payment_method, reference_number, category_id, branch_id, entry_date, notes) VALUES
('Rent Payment', 2000.00, 'Bank Transfer', 'RENT001', 4, 1, CURDATE(), 'Monthly rent'),
('Utilities', 500.00, 'Cash', 'UTIL001', 4, 1, CURDATE(), 'Monthly utilities');

-- Create triggers for stock management
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
    DATE(i.entry_date) as date,
    COALESCE(SUM(i.price), 0) as total_income,
    COALESCE(SUM(p.amount), 0) as total_expenses,
    COALESCE(SUM(i.price), 0) - COALESCE(SUM(p.amount), 0) as net_profit,
    b.name as branch_name
FROM income i
LEFT JOIN payment p ON DATE(i.entry_date) = DATE(p.entry_date) AND i.branch_id = p.branch_id
LEFT JOIN branches b ON i.branch_id = b.id
GROUP BY DATE(i.entry_date), b.name
ORDER BY date DESC;

-- Create stored procedures for common operations
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
        s.sale_number,
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

-- Create functions for calculations
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

-- Create events for auditing
CREATE EVENT audit_log_sales
ON SCHEDULE EVERY 1 HOUR
DO
    INSERT INTO payment (description, amount, payment_method, reference_number, category_id, branch_id, entry_date, notes)
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
    WHERE DATE(sale_date) = DATE(NOW());
END;

-- Final setup completion message
SELECT 'IBS Database Schema created successfully!' as message,
       NOW() as created_at,
       (SELECT COUNT(*) FROM branches) as branches_created,
       (SELECT COUNT(*) FROM users) as users_created,
       (SELECT COUNT(*) FROM products) as products_created;
