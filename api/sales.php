<?php
// Suppress error display to prevent HTML output in JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

// Register error handler to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Debug logging
error_log("Sales API called with method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request headers: " . json_encode(getallheaders()));

try {
    include_once '../config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization error: ' . $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Add test endpoint
        if (isset($_GET['test'])) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Sales API is working',
                'timestamp' => date('Y-m-d H:i:s'),
                'database_connected' => $db ? true : false
            ]);
            break;
        }
        // Check if requesting specific sale by ID
        if (isset($_GET['id'])) {
            $sale_id = (int) $_GET['id'];

            // Get specific sale with details
            $query = "SELECT s.*, c.name as customer_name, u.name as staff_name
                      FROM sales s
                      LEFT JOIN customers c ON s.customer_id = c.id
                      LEFT JOIN users u ON s.staff_id = u.id
                      WHERE s.id = ?";

            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $sale_id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Get sale items
                $items_query = "SELECT si.*, p.brand as product_brand, p.model as product_model, p.code as product_code
                               FROM sale_items si
                               JOIN products p ON si.product_id = p.id
                               WHERE si.sale_id = ?";
                $items_stmt = $db->prepare($items_query);
                $items_stmt->bindParam(1, $sale_id);
                $items_stmt->execute();

                $items = [];
                while ($item_row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $items[] = [
                        'product_id' => (int) $item_row['product_id'],
                        'product_code' => $item_row['product_code'],
                        'product_brand' => $item_row['product_brand'],
                        'product_model' => $item_row['product_model'],
                        'quantity' => (int) $item_row['quantity'],
                        'unit_price' => (float) ($item_row['unit_price'] / 100),
                        'total_price' => (float) ($item_row['total_price'] / 100)
                    ];
                }

                $sale = [
                    'id' => (int) $row['id'],
                    'receipt_number' => $row['receipt_number'],
                    'customer_name' => $row['customer_name'] ?? 'Walk-in Customer',
                    'staff_name' => $row['staff_name'],
                    'subtotal' => (float) ($row['subtotal'] / 100),
                    'tax_amount' => (float) ($row['tax_amount'] / 100),
                    'total_amount' => (float) ($row['total_amount'] / 100),
                    'payment_method' => $row['payment_method'],
                    'sale_date' => $row['sale_date'],
                    'items' => $items
                ];

                ob_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $sale
                ]);
            } else {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Sale not found'
                ]);
            }
        } else {
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
                        'product_id' => (int) $item_row['product_id'],
                        'code' => $item_row['code'],
                        'name' => $item_row['brand'] . ' ' . $item_row['model'],
                        'quantity' => (int) $item_row['quantity'],
                        'unit_price' => (float) ($item_row['unit_price'] / 100),
                        'total_price' => (float) ($item_row['total_price'] / 100)
                    ];
                }

                $sales[] = [
                    'id' => (int) $row['id'],
                    'receipt_number' => $row['receipt_number'],
                    'customer_name' => $row['customer_name'] ?? 'Walk-in Customer',
                    'staff_name' => $row['staff_name'],
                    'subtotal' => (float) ($row['subtotal'] / 100),
                    'tax_amount' => (float) ($row['tax_amount'] / 100),
                    'total_amount' => (float) ($row['total_amount'] / 100),
                    'payment_method' => $row['payment_method'],
                    'sale_date' => $row['sale_date'],
                    'items' => $items
                ];
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'data' => $sales
            ]);
        }
        break;

    case 'POST':
        // Create new sale
        try {
            $input = file_get_contents("php://input");
            if (empty($input)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'No data received'
                ]);
                break;
            }

            $data = json_decode($input);

            if (json_last_error() !== JSON_ERROR_NONE) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid JSON data: ' . json_last_error_msg()
                ]);
                break;
            }

            if (!$data) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to decode JSON data'
                ]);
                break;
            }

            // Debug logging
            error_log("Sales API received data: " . json_encode($data));
            error_log("Staff ID: " . ($data->staff_id ?? 'NULL'));
            error_log("Items count: " . (is_array($data->items) ? count($data->items) : 'NOT ARRAY'));

            // Validate required fields
            if (empty($data->items) || !is_array($data->items) || count($data->items) === 0) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'No items in sale'
                ]);
                break;
            }

            if (empty($data->staff_id) || $data->staff_id === null) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Staff ID is required',
                    'received_staff_id' => $data->staff_id ?? 'null'
                ]);
                break;
            }

            // Validate staff_id is numeric
            if (!is_numeric($data->staff_id)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid staff ID format',
                    'received_staff_id' => $data->staff_id
                ]);
                break;
            }

            if (!empty($data->items) && !empty($data->staff_id) && $data->staff_id !== null) {
                try {
                    $db->beginTransaction();

                    // Generate receipt number
                    $receipt_number = 'RCP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                    // Insert sale record with staff tracking
                    $sale_query = "INSERT INTO sales (receipt_number, customer_id, staff_id, subtotal, tax_amount, total_amount, payment_method, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $sale_stmt = $db->prepare($sale_query);
                    $notes = "Sale by: " . ($data->staff_name ?? 'Staff') . " (" . ($data->staff_username ?? 'unknown') . ")";
                    $customer_id = $data->customer_id ?? null;
                    $payment_method = $data->payment_method ?? 'cash';

                    $subtotal_cents = intval(round($data->subtotal * 100));
                    $tax_cents = intval(round($data->tax_amount * 100));
                    $total_cents = intval(round($data->total_amount * 100));
                    $staff_id = (int) $data->staff_id;

                    $sale_stmt->bindParam(1, $receipt_number);
                    $sale_stmt->bindParam(2, $customer_id);
                    $sale_stmt->bindParam(3, $staff_id);
                    $sale_stmt->bindParam(4, $subtotal_cents);
                    $sale_stmt->bindParam(5, $tax_cents);
                    $sale_stmt->bindParam(6, $total_cents);
                    $sale_stmt->bindParam(7, $payment_method);
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
                        $unit_price_cents = intval(round($item->unit_price * 100));
                        $total_price_cents = intval(round($item->total_price * 100));
                        $product_id = (int) $item->product_id;
                        $quantity = (int) $item->quantity;

                        $item_stmt->bindParam(1, $sale_id);
                        $item_stmt->bindParam(2, $product_id);
                        $item_stmt->bindParam(3, $quantity);
                        $item_stmt->bindParam(4, $unit_price_cents);
                        $item_stmt->bindParam(5, $total_price_cents);
                        $item_stmt->execute();

                        // Update product stock
                        $stock_query = "UPDATE products SET stock = stock - ? WHERE id = ?";
                        $stock_stmt = $db->prepare($stock_query);
                        $stock_stmt->bindParam(1, $item->quantity);
                        $stock_stmt->bindParam(2, $item->product_id);
                        $stock_stmt->execute();

                        // Insert stock movement record (if table exists)
                        try {
                            $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, created_by) 
                                          VALUES (?, 'out', ?, 'sale', ?, ?)";
                            $movement_stmt = $db->prepare($movement_query);
                            $movement_stmt->bindParam(1, $item->product_id);
                            $movement_stmt->bindParam(2, $item->quantity);
                            $movement_stmt->bindParam(3, $sale_id);
                            $movement_stmt->bindParam(4, $data->staff_id);
                            $movement_stmt->execute();
                        } catch (Exception $e) {
                            // Stock movements table doesn't exist, continue without it
                            error_log("Stock movements table not found, skipping: " . $e->getMessage());
                        }
                    }

                    // Update customer total purchases if customer exists
                    if (!empty($data->customer_id)) {
                        $customer_query = "UPDATE customers SET total_purchases = total_purchases + ? WHERE id = ?";
                        $customer_stmt = $db->prepare($customer_query);
                        $customer_total_cents = intval(round($data->total_amount * 100));
                        $customer_id_val = (int) $data->customer_id;
                        $customer_stmt->bindParam(1, $customer_total_cents);
                        $customer_stmt->bindParam(2, $customer_id_val);
                        $customer_stmt->execute();
                    }

                    $db->commit();

                    // Clean any output buffer before sending JSON
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Sale completed successfully',
                        'sale_id' => (int) $sale_id,
                        'receipt_number' => $receipt_number
                    ]);

                } catch (Exception $e) {
                    $db->rollback();
                    error_log("Sale creation failed: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    // Clean any output buffer before sending JSON
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to complete sale: ' . $e->getMessage(),
                        'error_details' => $e->getFile() . ':' . $e->getLine()
                    ]);
                }
            } else {
                $missing = [];
                if (empty($data->items))
                    $missing[] = 'items';
                if (empty($data->staff_id))
                    $missing[] = 'staff_id';

                // Clean any output buffer before sending JSON
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required data: ' . implode(', ', $missing),
                    'received_data' => [
                        'has_items' => !empty($data->items),
                        'items_count' => is_array($data->items) ? count($data->items) : 0,
                        'staff_id' => $data->staff_id ?? 'null',
                        'staff_id_type' => gettype($data->staff_id ?? null)
                    ]
                ]);
            }
        } catch (Throwable $e) {
            ob_clean();
            http_response_code(500);
            $errorMsg = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorTrace = $e->getTraceAsString();

            error_log("Sales API POST error: " . $errorMsg);
            error_log("Error in file: " . $errorFile . " on line " . $errorLine);
            error_log("Stack trace: " . $errorTrace);

            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $errorMsg,
                'error_details' => $errorFile . ':' . $errorLine,
                'error_type' => get_class($e)
            ]);
        } catch (Exception $e) {
            ob_clean();
            http_response_code(500);
            error_log("Sales API POST exception: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error_details' => $e->getFile() . ':' . $e->getLine()
            ]);
        }
        break;
    default:
        ob_clean();
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}

// End output buffering
ob_end_flush();
?>