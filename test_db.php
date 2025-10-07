<?php
// Database Connection Test
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "✅ Database connection successful!<br>";
    
    // Test if tables exist
    $tables = ['users', 'products', 'customers', 'sales', 'sale_items'];
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
            
            // Count records
            $count_query = "SELECT COUNT(*) as count FROM $table";
            $count_stmt = $db->prepare($count_query);
            $count_stmt->execute();
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "&nbsp;&nbsp;&nbsp;📊 Records: $count<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    echo "<br><strong>🔗 API Endpoints:</strong><br>";
    echo "• <a href='api/auth.php' target='_blank'>Authentication API</a><br>";
    echo "• <a href='api/products.php' target='_blank'>Products API</a><br>";
    echo "• <a href='api/customers.php' target='_blank'>Customers API</a><br>";
    echo "• <a href='api/sales.php' target='_blank'>Sales API</a><br>";
    echo "• <a href='api/dashboard.php' target='_blank'>Dashboard API</a><br>";
    
} else {
    echo "❌ Database connection failed!";
}
?>
