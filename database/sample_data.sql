-- ATTIA Mobile Shop Sample Data
-- Insert sample data for testing and development

USE attia_mobile_shop;

-- Insert Categories
('Smartphones', 'Mobile phones and smartphones'),
('Accessories', 'Phone accessories like cases, chargers, etc.'),
('Tablets', 'Tablet devices'),
('Smart Watches', 'Wearable smart devices');

-- Insert users (hashed passwords for phpMyAdmin access)
INSERT INTO users (username, password, role, name, phone, email, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', '+1-555-0001', 'admin@attia.com', 1),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Ahmed Hassan', '+1-555-0002', 'ahmed@attia.com', 1),
('staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Sarah Johnson', '+1-555-0003', 'sarah@attia.com', 1),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Mike Wilson', '+1-555-0004', 'mike@attia.com', 1);

-- Insert Suppliers
INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
{{ ... }}
('Samsung Electronics', 'Kim Lee', '+1-800-SAM-ELEC', 'wholesale@samsung.com', '456 Innovation Ave, Seoul, South Korea'),
('Google Store', 'Sarah Johnson', '+1-800-GOO-STOR', 'business@google.com', '789 Mountain View, CA'),
('Huawei Global', 'Li Wei', '+86-400-HUA-WEI', 'sales@huawei.com', '321 Shenzhen Tech Park, China');

-- Insert Products
INSERT INTO products (code, brand, model, price, stock, min_stock, category_id, description) VALUES
-- Apple Products
('APL001', 'Apple', 'iPhone 15 Pro', 999.99, 25, 5, 1, 'Latest iPhone with A17 Pro chip, titanium design'),
('APL002', 'Apple', 'iPhone 15', 799.99, 30, 5, 1, 'iPhone 15 with Dynamic Island and USB-C'),
('APL003', 'Apple', 'iPhone 14', 699.99, 20, 5, 1, 'iPhone 14 with A15 Bionic chip'),
('APL004', 'Apple', 'iPhone 13', 599.99, 15, 5, 1, 'iPhone 13 with dual camera system'),
('APL005', 'Apple', 'iPad Air', 599.99, 12, 3, 3, 'iPad Air with M1 chip'),

-- Samsung Products
('SAM001', 'Samsung', 'Galaxy S24 Ultra', 1199.99, 18, 5, 1, 'Premium Galaxy with S Pen and 200MP camera'),
('SAM002', 'Samsung', 'Galaxy S24', 899.99, 25, 5, 1, 'Galaxy S24 with AI features'),
('SAM003', 'Samsung', 'Galaxy A54', 399.99, 35, 10, 1, 'Mid-range Galaxy with great camera'),
('SAM004', 'Samsung', 'Galaxy Tab S9', 799.99, 8, 3, 3, 'Premium Android tablet'),
('SAM005', 'Samsung', 'Galaxy Watch 6', 329.99, 15, 5, 4, 'Latest Galaxy smartwatch'),

-- Google Products
('GOO001', 'Google', 'Pixel 8 Pro', 999.99, 12, 5, 1, 'Google Pixel with advanced AI photography'),
('GOO002', 'Google', 'Pixel 8', 699.99, 18, 5, 1, 'Google Pixel with pure Android experience'),
('GOO003', 'Google', 'Pixel 7a', 499.99, 22, 5, 1, 'Affordable Pixel with flagship features'),

-- Huawei Products
('HUA001', 'Huawei', 'P60 Pro', 899.99, 8, 5, 1, 'Huawei flagship with Leica cameras'),
('HUA002', 'Huawei', 'Mate 60', 799.99, 10, 5, 1, 'Huawei Mate series with advanced features'),
('HUA003', 'Huawei', 'Watch GT 4', 249.99, 12, 5, 4, 'Huawei smartwatch with long battery life'),

-- Accessories
('ACC001', 'Generic', 'USB-C Cable', 19.99, 50, 20, 2, 'High-quality USB-C charging cable'),
('ACC002', 'Generic', 'Wireless Charger', 39.99, 25, 10, 2, '15W wireless charging pad'),
('ACC003', 'Generic', 'Phone Case Clear', 14.99, 100, 30, 2, 'Clear protective phone case'),
('ACC004', 'Generic', 'Screen Protector', 9.99, 80, 25, 2, 'Tempered glass screen protector'),
('ACC005', 'Generic', 'Power Bank 10000mAh', 29.99, 30, 10, 2, 'Portable power bank with fast charging');

-- Insert Customers
INSERT INTO customers (name, phone, email, address, total_purchases, customer_tier) VALUES
('Ahmed Mohammed', '+1-555-1001', 'ahmed.m@email.com', '123 Main St, Downtown', 2450.50, 'VIP'),
('Sarah Johnson', '+1-555-1002', 'sarah.j@email.com', '456 Oak Ave, Midtown', 1299.99, 'VIP'),
('Mohammed Ali', '+1-555-1003', 'mohammed.a@email.com', '789 Pine St, Uptown', 899.99, 'Regular'),
('Fatima Hassan', '+1-555-1004', 'fatima.h@email.com', '321 Elm St, Westside', 1599.98, 'VIP'),
('Omar Khalil', '+1-555-1005', 'omar.k@email.com', '654 Maple Ave, Eastside', 399.99, 'Regular'),
('Layla Ahmad', '+1-555-1006', 'layla.a@email.com', '987 Cedar St, Northside', 799.99, 'Regular'),
('Yusuf Ibrahim', '+1-555-1007', 'yusuf.i@email.com', '147 Birch Ave, Southside', 2199.97, 'Premium'),
('Aisha Farouk', '+1-555-1008', 'aisha.f@email.com', '258 Willow St, Central', 699.99, 'Regular'),
('Hassan Nasser', '+1-555-1009', 'hassan.n@email.com', '369 Spruce Ave, Harbor', 1499.98, 'VIP'),
('Nora Salim', '+1-555-1010', 'nora.s@email.com', '741 Fir St, Heights', 999.99, 'Regular');

-- Insert Sales (Recent transactions)
INSERT INTO sales (receipt_number, customer_id, staff_id, subtotal, tax_amount, total_amount, payment_method, sale_date) VALUES
('RCP-2025-001', 1, 2, 999.99, 100.00, 1099.99, 'card', '2025-01-07 10:30:00'),
('RCP-2025-002', 2, 3, 1199.99, 120.00, 1319.99, 'card', '2025-01-07 11:15:00'),
('RCP-2025-003', 3, 2, 399.99, 40.00, 439.99, 'cash', '2025-01-07 12:45:00'),
('RCP-2025-004', 4, 3, 799.99, 80.00, 879.99, 'card', '2025-01-07 14:20:00'),
('RCP-2025-005', 5, 2, 699.99, 70.00, 769.99, 'mobile', '2025-01-07 15:10:00'),
('RCP-2025-006', 6, 3, 329.99, 33.00, 362.99, 'cash', '2025-01-06 16:30:00'),
('RCP-2025-007', 7, 2, 1999.98, 200.00, 2199.98, 'card', '2025-01-06 17:45:00'),
('RCP-2025-008', 8, 3, 499.99, 50.00, 549.99, 'card', '2025-01-06 09:20:00'),
('RCP-2025-009', 9, 2, 899.99, 90.00, 989.99, 'cash', '2025-01-05 11:30:00'),
('RCP-2025-010', 10, 3, 599.99, 60.00, 659.99, 'mobile', '2025-01-05 13:15:00');

-- Insert Sale Items
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES
-- Sale 1: iPhone 15 Pro
(1, 1, 1, 999.99, 999.99),

-- Sale 2: Galaxy S24 Ultra
(2, 6, 1, 1199.99, 1199.99),

-- Sale 3: Galaxy A54
(3, 8, 1, 399.99, 399.99),

-- Sale 4: iPhone 15
(4, 2, 1, 799.99, 799.99),

-- Sale 5: Pixel 8
(5, 12, 1, 699.99, 699.99),

-- Sale 6: Galaxy Watch 6
(6, 10, 1, 329.99, 329.99),

-- Sale 7: iPhone 15 Pro + iPhone 14 (multiple items)
(7, 1, 1, 999.99, 999.99),
(7, 3, 1, 699.99, 699.99),
(7, 17, 2, 19.99, 39.98),
(7, 19, 2, 14.99, 29.98),
(7, 20, 2, 9.99, 19.98),

-- Sale 8: Pixel 7a
(8, 13, 1, 499.99, 499.99),

-- Sale 9: P60 Pro
(9, 14, 1, 899.99, 899.99),

-- Sale 10: iPad Air
(10, 5, 1, 599.99, 599.99);

-- Insert Stock Movements (Track inventory changes from sales)
INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, created_by, notes) VALUES
-- Stock movements for recent sales
(1, 'out', 2, 'sale', 1, 2, 'Sale to Ahmed Mohammed'),
(1, 'out', 1, 'sale', 7, 2, 'Sale to Yusuf Ibrahim'),
(6, 'out', 1, 'sale', 2, 3, 'Sale to Sarah Johnson'),
(8, 'out', 1, 'sale', 3, 2, 'Sale to Mohammed Ali'),
(2, 'out', 1, 'sale', 4, 3, 'Sale to Fatima Hassan'),
(12, 'out', 1, 'sale', 5, 2, 'Sale to Omar Khalil'),
(10, 'out', 1, 'sale', 6, 3, 'Sale to Layla Ahmad'),
(3, 'out', 1, 'sale', 7, 2, 'Sale to Yusuf Ibrahim'),
(13, 'out', 1, 'sale', 8, 3, 'Sale to Aisha Farouk'),
(14, 'out', 1, 'sale', 9, 2, 'Sale to Hassan Nasser'),
(5, 'out', 1, 'sale', 10, 3, 'Sale to Nora Salim'),

-- Stock adjustments and restocks
(1, 'in', 50, 'purchase', NULL, 1, 'Initial stock - iPhone 15 Pro'),
(2, 'in', 40, 'purchase', NULL, 1, 'Initial stock - iPhone 15'),
(6, 'in', 30, 'purchase', NULL, 1, 'Initial stock - Galaxy S24 Ultra'),
(8, 'in', 50, 'purchase', NULL, 1, 'Initial stock - Galaxy A54'),
(17, 'in', 100, 'purchase', NULL, 1, 'Initial stock - USB-C Cables'),
(19, 'in', 150, 'purchase', NULL, 1, 'Initial stock - Phone Cases'),
(20, 'in', 120, 'purchase', NULL, 1, 'Initial stock - Screen Protectors');
