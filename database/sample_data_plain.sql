-- ATTIA Mobile Shop Sample Data (Plain Text Passwords for phpMyAdmin Access)
-- Insert sample data for testing and development

USE attia_mobile_shop;

-- Insert Categories
INSERT INTO categories (name, description) VALUES
('Smartphones', 'Mobile phones and smartphones'),
('Accessories', 'Phone accessories like cases, chargers, etc.'),
('Tablets', 'Tablet devices'),
('Smart Watches', 'Wearable smart devices');

-- Insert users (plain text passwords for phpMyAdmin access)
INSERT INTO users (username, password, role, name, phone, email, is_active) VALUES
('admin', 'admin123', 'admin', 'System Administrator', '+1-555-0001', 'admin@attia.com', 1),
('staff1', 'staff123', 'staff', 'Ahmed Hassan', '+1-555-0002', 'ahmed@attia.com', 1),
('staff2', 'staff123', 'staff', 'Sarah Johnson', '+1-555-0003', 'sarah@attia.com', 1),
('manager1', 'admin123', 'admin', 'Mike Wilson', '+1-555-0004', 'mike@attia.com', 1);

-- Insert Suppliers
INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('Apple Inc.', 'John Smith', '+1-800-APL-CARE', 'wholesale@apple.com', '123 Apple Park Way, Cupertino, CA'),
('Samsung Electronics', 'Kim Lee', '+1-800-SAM-ELEC', 'wholesale@samsung.com', '456 Innovation Ave, Seoul, South Korea'),
('Google Store', 'Sarah Johnson', '+1-800-GOO-STOR', 'business@google.com', '789 Mountain View, CA'),
('Huawei Global', 'Li Wei', '+86-400-HUA-WEI', 'sales@huawei.com', '321 Shenzhen Tech Park, China');

-- Insert Products
INSERT INTO products (code, brand, model, price, stock, min_stock, category_id, description) VALUES
-- Apple Products
('APL001', 'Apple', 'iPhone 15 Pro', 999.99, 25, 5, 1, 'Latest iPhone with A17 Pro chip, titanium design'),
('APL002', 'Apple', 'iPhone 15', 799.99, 30, 5, 1, 'iPhone 15 with Dynamic Island and USB-C'),
('APL003', 'Apple', 'iPhone 14', 699.99, 20, 5, 1, 'iPhone 14 with A15 Bionic chip'),
('APL004', 'Apple', 'iPhone 13', 599.99, 15, 5, 1, 'iPhone 13 with A15 Bionic chip'),
('APL005', 'Apple', 'iPad Pro 12.9"', 1099.99, 10, 3, 3, 'iPad Pro with M2 chip and Liquid Retina XDR display'),
('APL006', 'Apple', 'iPad Air', 599.99, 12, 3, 3, 'iPad Air with M1 chip'),
('APL007', 'Apple', 'Apple Watch Series 9', 399.99, 18, 5, 4, 'Latest Apple Watch with S9 chip'),

-- Samsung Products
('SAM001', 'Samsung', 'Galaxy S24 Ultra', 1199.99, 20, 5, 1, 'Premium Samsung flagship with S Pen'),
('SAM002', 'Samsung', 'Galaxy S24', 799.99, 25, 5, 1, 'Samsung Galaxy S24 with AI features'),
('SAM003', 'Samsung', 'Galaxy S23', 699.99, 18, 5, 1, 'Samsung Galaxy S23 with Snapdragon 8 Gen 2'),
('SAM004', 'Samsung', 'Galaxy A54', 449.99, 30, 8, 1, 'Mid-range Samsung with great camera'),
('SAM005', 'Samsung', 'Galaxy Tab S9', 799.99, 8, 3, 3, 'Premium Samsung tablet with S Pen'),
('SAM006', 'Samsung', 'Galaxy Watch 6', 329.99, 15, 5, 4, 'Samsung smartwatch with health tracking'),

-- Google Products
('GOO001', 'Google', 'Pixel 8 Pro', 999.99, 15, 5, 1, 'Google Pixel with advanced AI photography'),
('GOO002', 'Google', 'Pixel 8', 699.99, 20, 5, 1, 'Google Pixel with pure Android experience'),
('GOO003', 'Google', 'Pixel 7a', 499.99, 25, 8, 1, 'Affordable Google Pixel with great camera'),
('GOO004', 'Google', 'Pixel Tablet', 499.99, 6, 2, 3, 'Google tablet with smart home hub features'),

-- Huawei Products
('HUA001', 'Huawei', 'P60 Pro', 899.99, 12, 3, 1, 'Huawei flagship with advanced camera system'),
('HUA002', 'Huawei', 'Mate 50 Pro', 799.99, 10, 3, 1, 'Huawei Mate series with satellite communication'),
('HUA003', 'Huawei', 'Nova 11', 549.99, 18, 5, 1, 'Stylish Huawei phone with portrait photography'),
('HUA004', 'Huawei', 'MatePad Pro', 649.99, 8, 2, 3, 'Professional Huawei tablet for productivity'),

-- Accessories
('ACC001', 'Various', 'Phone Case Universal', 19.99, 100, 20, 2, 'Protective phone case for various models'),
('ACC002', 'Various', 'Wireless Charger', 39.99, 50, 10, 2, 'Fast wireless charging pad'),
('ACC003', 'Various', 'USB-C Cable', 14.99, 80, 15, 2, 'High-quality USB-C charging cable'),
('ACC004', 'Various', 'Screen Protector', 9.99, 120, 25, 2, 'Tempered glass screen protector'),
('ACC005', 'Various', 'Power Bank 10000mAh', 49.99, 35, 8, 2, 'Portable power bank with fast charging');

-- Insert Customers
INSERT INTO customers (name, phone, email, address, customer_tier, total_purchases) VALUES
('John Smith', '+1-555-0101', 'john.smith@email.com', '123 Main St, New York, NY', 'Gold', 2500.00),
('Sarah Johnson', '+1-555-0102', 'sarah.j@email.com', '456 Oak Ave, Los Angeles, CA', 'Silver', 1200.00),
('Mike Wilson', '+1-555-0103', 'mike.wilson@email.com', '789 Pine St, Chicago, IL', 'Bronze', 800.00),
('Emily Davis', '+1-555-0104', 'emily.davis@email.com', '321 Elm St, Houston, TX', 'Gold', 3200.00),
('David Brown', '+1-555-0105', 'david.brown@email.com', '654 Maple Dr, Phoenix, AZ', 'Silver', 1500.00),
('Lisa Garcia', '+1-555-0106', 'lisa.garcia@email.com', '987 Cedar Ln, Philadelphia, PA', 'Bronze', 600.00),
('James Miller', '+1-555-0107', 'james.miller@email.com', '147 Birch Rd, San Antonio, TX', 'Gold', 2800.00),
('Maria Rodriguez', '+1-555-0108', 'maria.r@email.com', '258 Spruce St, San Diego, CA', 'Silver', 1100.00),
('Robert Taylor', '+1-555-0109', 'robert.taylor@email.com', '369 Willow Way, Dallas, TX', 'Bronze', 750.00),
('Jennifer Anderson', '+1-555-0110', 'jennifer.a@email.com', '741 Poplar Ave, San Jose, CA', 'Gold', 3500.00);

-- Insert Sample Sales
INSERT INTO sales (receipt_number, customer_id, staff_id, subtotal, tax_amount, total_amount, payment_method, notes) VALUES
('RCP-2024-0001', 1, 2, 999.99, 100.00, 1099.99, 'credit_card', 'Sale by: Ahmed Hassan (staff1)'),
('RCP-2024-0002', 2, 2, 799.99, 80.00, 879.99, 'cash', 'Sale by: Ahmed Hassan (staff1)'),
('RCP-2024-0003', 3, 3, 449.99, 45.00, 494.99, 'credit_card', 'Sale by: Sarah Johnson (staff2)'),
('RCP-2024-0004', 4, 2, 1199.99, 120.00, 1319.99, 'credit_card', 'Sale by: Ahmed Hassan (staff1)'),
('RCP-2024-0005', 5, 3, 699.99, 70.00, 769.99, 'cash', 'Sale by: Sarah Johnson (staff2)');

-- Insert Sale Items
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES
-- Sale 1: iPhone 15 Pro
(1, 1, 1, 999.99, 999.99),
-- Sale 2: iPhone 15
(2, 2, 1, 799.99, 799.99),
-- Sale 3: Galaxy A54
(3, 10, 1, 449.99, 449.99),
-- Sale 4: Galaxy S24 Ultra
(4, 8, 1, 1199.99, 1199.99),
-- Sale 5: Galaxy S23
(5, 10, 1, 699.99, 699.99);

-- Insert Stock Movements
INSERT INTO stock_movements (product_id, movement_type, quantity, reference_id, reference_type, notes, created_by) VALUES
-- Initial stock entries
(1, 'in', 30, NULL, 'initial', 'Initial stock - iPhone 15 Pro', 1),
(2, 'in', 35, NULL, 'initial', 'Initial stock - iPhone 15', 1),
(8, 'in', 25, NULL, 'initial', 'Initial stock - Galaxy S24 Ultra', 1),
(10, 'in', 35, NULL, 'initial', 'Initial stock - Galaxy A54', 1),
-- Sales movements
(1, 'out', 1, 1, 'sale', 'Sold via receipt RCP-2024-0001', 2),
(2, 'out', 1, 2, 'sale', 'Sold via receipt RCP-2024-0002', 2),
(10, 'out', 1, 3, 'sale', 'Sold via receipt RCP-2024-0003', 3),
(8, 'out', 1, 4, 'sale', 'Sold via receipt RCP-2024-0004', 2),
(10, 'out', 1, 5, 'sale', 'Sold via receipt RCP-2024-0005', 3);
