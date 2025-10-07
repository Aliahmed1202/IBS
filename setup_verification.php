<?php
// Database Setup Verification Script
// This script verifies that the database is properly set up with all required data

header('Content-Type: text/html; charset=UTF-8');
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>ATTIA Database Setup Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>🏪 ATTIA Mobile Shop - Database Setup Verification</h1>";

if (!$db) {
    echo "<div class='section error'>
        <h2>❌ Database Connection Failed</h2>
        <p>Please check your database configuration in config/database.php</p>
    </div>";
    exit;
}

echo "<div class='section success'>
    <h2>✅ Database Connection Successful</h2>
</div>";

// Check required tables
$required_tables = ['users', 'products', 'customers', 'sales', 'sale_items', 'categories', 'stock_movements'];
$missing_tables = [];

echo "<div class='section'>
    <h2>📋 Table Verification</h2>
    <table>
        <tr><th>Table</th><th>Status</th><th>Records</th><th>Sample Data</th></tr>";

foreach ($required_tables as $table) {
    $query = "SHOW TABLES LIKE '$table'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Count records
        $count_query = "SELECT COUNT(*) as count FROM $table";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get sample data
        $sample_query = "SELECT * FROM $table LIMIT 1";
        $sample_stmt = $db->prepare($sample_query);
        $sample_stmt->execute();
        $sample = $sample_stmt->fetch(PDO::FETCH_ASSOC);
        
        $status_class = $count > 0 ? 'success' : 'warning';
        $sample_info = $sample ? array_keys($sample)[0] . ': ' . array_values($sample)[0] : 'No data';
        
        echo "<tr class='$status_class'>
            <td>$table</td>
            <td>✅ Exists</td>
            <td>$count</td>
            <td>$sample_info</td>
        </tr>";
    } else {
        $missing_tables[] = $table;
        echo "<tr class='error'>
            <td>$table</td>
            <td>❌ Missing</td>
            <td>-</td>
            <td>-</td>
        </tr>";
    }
}

echo "</table></div>";

if (!empty($missing_tables)) {
    echo "<div class='section error'>
        <h2>❌ Missing Tables</h2>
        <p>The following tables are missing: " . implode(', ', $missing_tables) . "</p>
        <p><strong>Action Required:</strong> Import database/schema.sql</p>
    </div>";
}

// Check users for authentication
echo "<div class='section'>
    <h2>👥 User Authentication Data</h2>";

$users_query = "SELECT id, username, role, name, is_active FROM users WHERE is_active = 1";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();

if ($users_stmt->rowCount() > 0) {
    echo "<table>
        <tr><th>ID</th><th>Username</th><th>Role</th><th>Name</th><th>Status</th></tr>";
    
    while ($user = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
            <td>{$user['id']}</td>
            <td>{$user['username']}</td>
            <td>{$user['role']}</td>
            <td>{$user['name']}</td>
            <td>" . ($user['is_active'] ? '✅ Active' : '❌ Inactive') . "</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No active users found. Import sample_data.sql</p>";
}

echo "</div>";

// Check products for inventory
echo "<div class='section'>
    <h2>📱 Product Inventory Data</h2>";

$products_query = "SELECT id, code, brand, model, price, stock FROM products WHERE is_active = 1 LIMIT 5";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();

if ($products_stmt->rowCount() > 0) {
    echo "<table>
        <tr><th>ID</th><th>Code</th><th>Brand</th><th>Model</th><th>Price</th><th>Stock</th></tr>";
    
    while ($product = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
            <td>{$product['id']}</td>
            <td>{$product['code']}</td>
            <td>{$product['brand']}</td>
            <td>{$product['model']}</td>
            <td>$" . number_format($product['price'], 2) . "</td>
            <td>{$product['stock']}</td>
        </tr>";
    }
    echo "</table>";
    
    $total_products_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $total_stmt = $db->prepare($total_products_query);
    $total_stmt->execute();
    $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total active products: <strong>$total</strong></p>";
} else {
    echo "<p class='error'>❌ No products found. Import sample_data.sql</p>";
}

echo "</div>";

// API Endpoints Test
echo "<div class='section'>
    <h2>🔗 API Endpoints</h2>
    <p>Test the following API endpoints:</p>
    <ul>
        <li><a href='api/products.php' target='_blank'>Products API</a></li>
        <li><a href='api/customers.php' target='_blank'>Customers API</a></li>
        <li><a href='api/sales.php' target='_blank'>Sales API</a></li>
        <li><a href='api/dashboard.php' target='_blank'>Dashboard API</a></li>
    </ul>
</div>";

// Setup Instructions
echo "<div class='section'>
    <h2>🚀 Next Steps</h2>";

if (empty($missing_tables) && $users_stmt->rowCount() > 0 && $products_stmt->rowCount() > 0) {
    echo "<p class='success'>✅ <strong>Database is properly set up!</strong></p>
        <p>You can now use the application:</p>
        <ul>
            <li><a href='index.html'>Launch ATTIA Mobile Shop</a></li>
            <li>Login with database credentials (staff1/staff123 or admin/admin123)</li>
            <li>All data will be loaded from the database</li>
        </ul>";
} else {
    echo "<p class='error'>❌ <strong>Database setup incomplete</strong></p>
        <p>Required actions:</p>
        <ol>
            <li>Import database schema: <code>mysql -u root -p attia_mobile_shop < database/schema.sql</code></li>
            <li>Import sample data: <code>mysql -u root -p attia_mobile_shop < database/sample_data.sql</code></li>
            <li>Refresh this page to verify setup</li>
        </ol>";
}

echo "</div>";

echo "</body></html>";
?>
