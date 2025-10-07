<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all sales with details
        $query = "SELECT s.*, c.name as customer_name, u.name as staff_name
                  FROM sales s
                  LEFT JOIN customers c ON s.customer_id = c.id
                  LEFT JOIN users u ON s.staff_id = u.id
                  ORDER BY s.sale_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $sales = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get sale items
            $items_query = "SELECT si.*, p.brand, p.model, p.code
                           FROM sale_items si
                           JOIN products p ON si.product_id = p.id
                           WHERE si.sale_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->bindParam(1, $row['id']);
            $items_stmt->execute();
            
            $items = [];
            while ($item_row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = [
                    'product_id' => (int)$item_row['product_id'],
                    'code' => $item_row['code'],
                    'name' => $item_row['brand'] . ' ' . $item_row['model'],
                    'quantity' => (int)$item_row['quantity'],
                    'unit_price' => (float)$item_row['unit_price'],
                    'total_price' => (float)$item_row['total_price']
                ];
            }
            
            $sales[] = [
                'id' => (int)$row['id'],
                'receipt_number' => $row['receipt_number'],
                'customer_name' => $row['customer_name'] ?? 'Walk-in Customer',
                'staff_name' => $row['staff_name'],
                'subtotal' => (float)$row['subtotal'],
                'tax_amount' => (float)$row['tax_amount'],
                'total_amount' => (float)$row['total_amount'],
                'payment_method' => $row['payment_method'],
                'sale_date' => $row['sale_date'],
                'items' => $items
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $sales
        ]);
        break;
        
    case 'POST':
        // Create new sale
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->items) && !empty($data->staff_id)) {
            try {
                $db->beginTransaction();
                
                // Generate receipt number
                $receipt_number = 'RCP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Insert sale record with staff tracking
                $sale_query = "INSERT INTO sales (receipt_number, customer_id, staff_id, subtotal, tax_amount, total_amount, payment_method, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $sale_stmt = $db->prepare($sale_query);
                $notes = "Sale by: " . ($data->staff_name ?? 'Staff') . " (" . ($data->staff_username ?? 'unknown') . ")";
                $sale_stmt->bindParam(1, $receipt_number);
                $sale_stmt->bindParam(2, $data->customer_id ?? null);
                $sale_stmt->bindParam(3, $data->staff_id);
                $sale_stmt->bindParam(4, $data->subtotal);
                $sale_stmt->bindParam(5, $data->tax_amount);
                $sale_stmt->bindParam(6, $data->total_amount);
                $sale_stmt->bindParam(7, $data->payment_method ?? 'cash');
                $sale_stmt->bindParam(8, $notes);
                
                // Log the sale creation
                error_log("Creating sale: Receipt $receipt_number by staff ID " . $data->staff_id . " (" . ($data->staff_name ?? 'Unknown') . ")");
                
                $sale_stmt->execute();
                $sale_id = $db->lastInsertId();
                
                // Insert sale items and update stock
                foreach ($data->items as $item) {
                    // Insert sale item
                    $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) 
                                  VALUES (?, ?, ?, ?, ?)";
                    $item_stmt = $db->prepare($item_query);
                    $item_stmt->bindParam(1, $sale_id);
                    $item_stmt->bindParam(2, $item->product_id);
                    $item_stmt->bindParam(3, $item->quantity);
                    $item_stmt->bindParam(4, $item->unit_price);
                    $item_stmt->bindParam(5, $item->total_price);
                    $item_stmt->execute();
                    
                    // Update product stock
                    $stock_query = "UPDATE products SET stock = stock - ? WHERE id = ?";
                    $stock_stmt = $db->prepare($stock_query);
                    $stock_stmt->bindParam(1, $item->quantity);
                    $stock_stmt->bindParam(2, $item->product_id);
                    $stock_stmt->execute();
                    
                    // Insert stock movement record
                    $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, created_by) 
                                      VALUES (?, 'out', ?, 'sale', ?, ?)";
                    $movement_stmt = $db->prepare($movement_query);
                    $movement_stmt->bindParam(1, $item->product_id);
                    $movement_stmt->bindParam(2, $item->quantity);
                    $movement_stmt->bindParam(3, $sale_id);
                    $movement_stmt->bindParam(4, $data->staff_id);
                    $movement_stmt->execute();
                }
                
                // Update customer total purchases if customer exists
                if (!empty($data->customer_id)) {
                    $customer_query = "UPDATE customers SET total_purchases = total_purchases + ? WHERE id = ?";
                    $customer_stmt = $db->prepare($customer_query);
                    $customer_stmt->bindParam(1, $data->total_amount);
                    $customer_stmt->bindParam(2, $data->customer_id);
                    $customer_stmt->execute();
                }
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sale completed successfully',
                    'sale_id' => (int)$sale_id,
                    'receipt_number' => $receipt_number
                ]);
                
            } catch (Exception $e) {
                $db->rollback();
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to complete sale: ' . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required data'
            ]);
        }
        break;
}
?>
