<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all customers or specific customer
        if (isset($_GET['id'])) {
            $query = "SELECT * FROM customers WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $_GET['id']);
            $stmt->execute();
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => (int)$customer['id'],
                        'name' => $customer['name'],
                        'phone' => $customer['phone'],
                        'email' => $customer['email'],
                        'address' => $customer['address'],
                        'total_purchases' => (float)($customer['total_purchases'] / 100),
                        'created_at' => $customer['created_at']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Customer not found'
                ]);
            }
        } else {
            // Get all customers
            $query = "SELECT * FROM customers ORDER BY name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $customers = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $customers[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'total_purchases' => (float)($row['total_purchases'] / 100),
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $customers
            ]);
        }
        break;
        
    case 'POST':
        // Add new customer or find existing
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->name)) {
            // Check if customer exists by phone
            if (!empty($data->phone)) {
                $check_query = "SELECT id FROM customers WHERE phone = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(1, $data->phone);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Customer found',
                        'customer_id' => (int)$existing['id']
                    ]);
                    break;
                }
            }
            
            // Create new customer
            $query = "INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->name);
            $stmt->bindParam(2, $data->phone ?? null);
            $stmt->bindParam(3, $data->email ?? null);
            $stmt->bindParam(4, $data->address ?? null);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer added successfully',
                    'customer_id' => (int)$db->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add customer'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Customer name is required'
            ]);
        }
        break;
        
    case 'PUT':
        // Update customer
        $data = json_decode(file_get_contents("php://input"));
        $customerId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$customerId || empty($data->name)) {
            echo json_encode([
                'success' => false,
                'message' => 'Customer ID and name are required'
            ]);
            break;
        }
        
        // Check if customer exists
        $checkQuery = "SELECT id FROM customers WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(1, $customerId);
        $checkStmt->execute();
        
        if (!$checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Customer not found'
            ]);
            break;
        }
        
        $query = "UPDATE customers SET name = ?, phone = ?, email = ?, address = ?, total_purchases = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        $totalPurchases = isset($data->total_purchases) ? (int)round($data->total_purchases * 100) : 0;
        
        $stmt->bindParam(1, $data->name);
        $stmt->bindParam(2, $data->phone);
        $stmt->bindParam(3, $data->email);
        $stmt->bindParam(4, $data->address);
        $stmt->bindParam(5, $totalPurchases);
        $stmt->bindParam(6, $customerId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Customer updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update customer'
            ]);
        }
        break;
        
    case 'DELETE':
        // Delete customer
        $customerId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$customerId) {
            echo json_encode([
                'success' => false,
                'message' => 'Customer ID is required'
            ]);
            break;
        }
        
        // Check if customer has sales
        $checkSalesQuery = "SELECT COUNT(*) as sales_count FROM sales WHERE customer_id = ?";
        $checkSalesStmt = $db->prepare($checkSalesQuery);
        $checkSalesStmt->bindParam(1, $customerId);
        $checkSalesStmt->execute();
        $salesResult = $checkSalesStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($salesResult['sales_count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete customer with sales history. Consider deactivating instead.'
            ]);
            break;
        }
        
        $query = "DELETE FROM customers WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $customerId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete customer'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
?>
