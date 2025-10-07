<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

// Total products
$query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_products'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total sales amount
$query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_sales'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total customers
$query = "SELECT COUNT(*) as total FROM customers";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_customers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Low stock products
$query = "SELECT COUNT(*) as total FROM products WHERE stock <= min_stock AND is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['low_stock'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Today's sales
$query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_sales'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// This month's sales
$query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['month_sales'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent sales (last 10)
$query = "SELECT s.receipt_number, s.total_amount, s.sale_date, c.name as customer_name, u.name as staff_name
          FROM sales s
          LEFT JOIN customers c ON s.customer_id = c.id
          LEFT JOIN users u ON s.staff_id = u.id
          ORDER BY s.sale_date DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();

$recent_sales = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent_sales[] = [
        'receipt_number' => $row['receipt_number'],
        'total_amount' => (float)$row['total_amount'],
        'sale_date' => $row['sale_date'],
        'customer_name' => $row['customer_name'] ?? 'Walk-in Customer',
        'staff_name' => $row['staff_name']
    ];
}

// Low stock products details
$query = "SELECT code, brand, model, stock, min_stock 
          FROM products 
          WHERE stock <= min_stock AND is_active = 1 
          ORDER BY stock ASC";
$stmt = $db->prepare($query);
$stmt->execute();

$low_stock_products = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $low_stock_products[] = [
        'code' => $row['code'],
        'name' => $row['brand'] . ' ' . $row['model'],
        'stock' => (int)$row['stock'],
        'min_stock' => (int)$row['min_stock']
    ];
}

// Top selling products (this month)
$query = "SELECT p.code, p.brand, p.model, SUM(si.quantity) as total_sold, SUM(si.total_price) as revenue
          FROM sale_items si
          JOIN products p ON si.product_id = p.id
          JOIN sales s ON si.sale_id = s.id
          WHERE MONTH(s.sale_date) = MONTH(CURDATE()) AND YEAR(s.sale_date) = YEAR(CURDATE())
          GROUP BY p.id
          ORDER BY total_sold DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();

$top_products = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $top_products[] = [
        'code' => $row['code'],
        'name' => $row['brand'] . ' ' . $row['model'],
        'total_sold' => (int)$row['total_sold'],
        'revenue' => (float)$row['revenue']
    ];
}

echo json_encode([
    'success' => true,
    'data' => [
        'stats' => $stats,
        'recent_sales' => $recent_sales,
        'low_stock_products' => $low_stock_products,
        'top_products' => $top_products
    ]
]);
?>
