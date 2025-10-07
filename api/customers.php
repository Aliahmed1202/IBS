<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
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
                'total_purchases' => (float)$row['total_purchases'],
                'customer_tier' => $row['customer_tier'],
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $customers
        ]);
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
}
?>
