<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['product_id'])) {
            // Get stock items for a specific product
            $productId = $_GET['product_id'];
            $query = "SELECT si.*, p.brand, p.model, p.color, p.has_imei
                      FROM stock_items si
                      JOIN products p ON si.product_id = p.id
                      WHERE si.product_id = ?
                      ORDER BY si.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$productId]);
            
            $stockItems = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stockItems[] = [
                    'id' => (int) $row['id'],
                    'product_id' => (int) $row['product_id'],
                    'serial_number' => $row['serial_number'],
                    'imei' => $row['imei'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'sale_id' => $row['sale_id'] ? (int) $row['sale_id'] : null,
                    'brand' => $row['brand'],
                    'model' => $row['model'],
                    'color' => $row['color'],
                    'has_imei' => (int) $row['has_imei']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stockItems
            ]);
        } else {
            // Get all stock items with product info
            $query = "SELECT si.*, p.brand, p.model, p.code, p.color, p.has_imei, c.name as category_name
                      FROM stock_items si
                      JOIN products p ON si.product_id = p.id
                      LEFT JOIN categories c ON p.category_id = c.id
                      ORDER BY si.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $stockItems = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stockItems[] = [
                    'id' => (int) $row['id'],
                    'product_id' => (int) $row['product_id'],
                    'product_code' => $row['code'],
                    'product_name' => $row['brand'] . ' ' . $row['model'],
                    'category_name' => $row['category_name'] ?? 'Uncategorized',
                    'serial_number' => $row['serial_number'],
                    'imei' => $row['imei'],
                    'status' => $row['status'],
                    'color' => $row['color'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'sale_id' => $row['sale_id'] ? (int) $row['sale_id'] : null,
                    'has_imei' => (int) $row['has_imei']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stockItems
            ]);
        }
        break;
        
    case 'POST':
        // Add new stock items for a product
        $data = json_decode(file_get_contents('php://input'));
        
        if (!isset($data->product_id) || !isset($data->items)) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID and items are required'
            ]);
            exit;
        }
        
        $productId = (int) $data->product_id;
        $items = $data->items;
        
        // First, get product info to check if it has IMEI
        $productQuery = "SELECT has_imei FROM products WHERE id = ?";
        $productStmt = $db->prepare($productQuery);
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
            exit;
        }
        
        $hasImei = (int) $product['has_imei'];
        $successCount = 0;
        $errors = [];
        
        foreach ($items as $item) {
            // Validate required fields
            if (empty($item->serial_number)) {
                $errors[] = "Serial number is required for all items";
                continue;
            }
            
            // Check if IMEI is required for this product
            if ($hasImei && empty($item->imei)) {
                $errors[] = "IMEI is required for this product";
                continue;
            }
            
            // Check if serial number already exists
            $checkQuery = "SELECT id FROM stock_items WHERE serial_number = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$item->serial_number]);
            
            if ($checkStmt->fetch()) {
                $errors[] = "Serial number {$item->serial_number} already exists";
                continue;
            }
            
            // Insert stock item
            $insertQuery = "INSERT INTO stock_items (product_id, serial_number, imei, status) VALUES (?, ?, ?, 'available')";
            $insertStmt = $db->prepare($insertQuery);
            
            $params = [$productId, $item->serial_number];
            if ($hasImei) {
                $params[] = $item->imei;
            } else {
                $params[] = null;
            }
            
            if ($insertStmt->execute($params)) {
                $successCount++;
            } else {
                $errors[] = "Failed to add item with serial number {$item->serial_number}";
            }
        }
        
        // Update product stock count
        $updateStockQuery = "UPDATE products SET stock = (SELECT COUNT(*) FROM stock_items WHERE product_id = ? AND status = 'available') WHERE id = ?";
        $updateStockStmt = $db->prepare($updateStockQuery);
        $updateStockStmt->execute([$productId, $productId]);
        
        echo json_encode([
            'success' => $successCount > 0,
            'message' => $successCount > 0 ? "Successfully added $successCount items" : "No items were added",
            'success_count' => $successCount,
            'errors' => $errors
        ]);
        break;
        
    case 'PUT':
        // Update stock item status (e.g., mark as sold)
        $data = json_decode(file_get_contents('php://input'));
        
        if (!isset($data->id) || !isset($data->status)) {
            echo json_encode([
                'success' => false,
                'message' => 'Item ID and status are required'
            ]);
            exit;
        }
        
        $itemId = (int) $data->id;
        $status = $data->status;
        $saleId = isset($data->sale_id) ? (int) $data->sale_id : null;
        
        // Validate status
        $validStatuses = ['available', 'sold', 'reserved', 'damaged'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
            ]);
            exit;
        }
        
        // Get product_id before updating
        $getProductQuery = "SELECT product_id FROM stock_items WHERE id = ?";
        $getProductStmt = $db->prepare($getProductQuery);
        $getProductStmt->execute([$itemId]);
        $stockItem = $getProductStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stockItem) {
            echo json_encode([
                'success' => false,
                'message' => 'Stock item not found'
            ]);
            exit;
        }
        
        $productId = (int) $stockItem['product_id'];
        
        // Update stock item
        $updateQuery = "UPDATE stock_items SET status = ?, sale_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$status, $saleId, $itemId])) {
            // Update product stock count
            $updateStockQuery = "UPDATE products SET stock = (SELECT COUNT(*) FROM stock_items WHERE product_id = ? AND status = 'available') WHERE id = ?";
            $updateStockStmt = $db->prepare($updateStockQuery);
            $updateStockStmt->execute([$productId, $productId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Stock item updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update stock item'
            ]);
        }
        break;
        
    case 'DELETE':
        // Delete a stock item
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Item ID is required'
            ]);
            exit;
        }
        
        $itemId = (int) $_GET['id'];
        
        // Get product_id before deleting
        $getProductQuery = "SELECT product_id FROM stock_items WHERE id = ?";
        $getProductStmt = $db->prepare($getProductQuery);
        $getProductStmt->execute([$itemId]);
        $stockItem = $getProductStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stockItem) {
            echo json_encode([
                'success' => false,
                'message' => 'Stock item not found'
            ]);
            exit;
        }
        
        $productId = (int) $stockItem['product_id'];
        
        // Delete stock item
        $deleteQuery = "DELETE FROM stock_items WHERE id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        
        if ($deleteStmt->execute([$itemId])) {
            // Update product stock count
            $updateStockQuery = "UPDATE products SET stock = (SELECT COUNT(*) FROM stock_items WHERE product_id = ? AND status = 'available') WHERE id = ?";
            $updateStockStmt = $db->prepare($updateStockQuery);
            $updateStockStmt->execute([$productId, $productId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Stock item deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete stock item'
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
