<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all products
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.is_active = 1 
                  ORDER BY p.brand, p.model";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'brand' => $row['brand'],
                'model' => $row['model'],
                'price' => (float)$row['price'],
                'stock' => (int)$row['stock'],
                'min_stock' => (int)$row['min_stock'],
                'category' => $row['category_name'],
                'description' => $row['description'],
                'image_url' => $row['image_url'],
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
        break;
        
    case 'POST':
        // Add new product (Admin only)
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->code) && !empty($data->brand) && !empty($data->model) && !empty($data->price)) {
            $query = "INSERT INTO products (code, brand, model, price, stock, min_stock, category_id, description) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->code);
            $stmt->bindParam(2, $data->brand);
            $stmt->bindParam(3, $data->model);
            $stmt->bindParam(4, $data->price);
            $stmt->bindParam(5, $data->stock ?? 0);
            $stmt->bindParam(6, $data->min_stock ?? 5);
            $stmt->bindParam(7, $data->category_id ?? null);
            $stmt->bindParam(8, $data->description ?? '');
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add product'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
        }
        break;
        
    case 'PUT':
        // Update product stock (for sales)
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id) && isset($data->stock)) {
            $query = "UPDATE products SET stock = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->stock);
            $stmt->bindParam(2, $data->id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Stock updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update stock'
                ]);
            }
        }
        break;
}
?>
