<?php
// Suppress error display to prevent HTML output in JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit();
}

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
        // Get all products with supplier, category, and stock information
        $query = "SELECT p.*, s.name as supplier_name, c.name as category_name,
                  (SELECT COUNT(*) FROM stock_items WHERE product_id = p.id AND status = 'available') as available_stock,
                  (SELECT COUNT(*) FROM stock_items WHERE product_id = p.id) as total_stock
                  FROM products p 
                  LEFT JOIN suppliers s ON p.supplier_id = s.id 
                  LEFT JOIN categories c ON p.category_id = c.id
                  ORDER BY p.brand, p.model";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = [
                'id' => (int) $row['id'],
                'code' => $row['code'],
                'barcode' => $row['barcode'],
                'brand' => $row['brand'],
                'model' => $row['model'],
                'purchase_price' => (float) ($row['purchase_price'] ?? $row['price'] ?? 0),
                'min_selling_price' => (float) ($row['min_selling_price'] ?? $row['price'] ?? 0),
                'suggested_price' => (float) ($row['suggested_price'] ?? $row['price'] ?? 0),
                'price' => (float) ($row['suggested_price'] ?? $row['price'] ?? 0), // For backward compatibility
                'stock' => (int) $row['stock'],
                'min_stock' => $row['min_stock'] ? (int) $row['min_stock'] : null,
                'category_id' => (int) ($row['category_id'] ?? 0),
                'image_url' => $row['image_url'] ?? '',
                'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : true,
                'description' => $row['description'] ?? '',
                'serial_number' => $row['serial_number'] ?? '',
                'imei' => $row['imei'] ?? '',
                'color' => $row['color'] ?? '',
                'supplier_id' => $row['supplier_id'] ? (int) $row['supplier_id'] : null,
                'supplier_name' => $row['supplier_name'] ?? 'Unknown',
                'category_name' => $row['category_name'] ?? 'Uncategorized',
                'available_stock' => (int) $row['available_stock'],
                'total_stock' => (int) $row['total_stock'],
                'has_imei' => (int) ($row['has_imei'] ?? 0),
                'created_at' => $row['created_at'] ?? null
            ];
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
        break;

    case 'POST':
        // Add new product (Admin only)
        $data = json_decode(file_get_contents("php://input"));

        // Validate required fields and values
        $purchase_price = intval(round(floatval($data->purchase_price ?? 0) * 100));
        $min_selling_price = intval(round(floatval($data->min_selling_price ?? 0) * 100));
        $suggested_price = intval(round(floatval($data->suggested_price ?? 0) * 100));
        $stock = intval($data->stock ?? 0);

        if ((empty($data->brand) && empty($data->brand_id)) || empty($data->model)) {
            echo json_encode([
                'success' => false,
                'message' => 'Brand and Model are required fields'
            ]);
        } elseif ($purchase_price <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Purchase price must be greater than 0'
            ]);
        } elseif ($min_selling_price <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Minimum selling price must be greater than 0'
            ]);
        } elseif ($suggested_price <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Suggested selling price must be greater than 0'
            ]);
        } elseif ($min_selling_price < $purchase_price) {
            echo json_encode([
                'success' => false,
                'message' => 'Minimum selling price cannot be less than purchase price'
            ]);
        } elseif ($suggested_price < $min_selling_price) {
            echo json_encode([
                'success' => false,
                'message' => 'Suggested selling price cannot be less than minimum selling price'
            ]);
        } elseif ($stock <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Stock quantity must be greater than 0'
            ]);
        } else {
            // Handle brand data - get brand name if brand_id is provided
            $brandName = null;
            $brandId = null;
            
            if (!empty($data->brand_id) && is_numeric($data->brand_id)) {
                // Get brand name from brands table
                $brandQuery = "SELECT name FROM brands WHERE id = ? AND is_active = 1";
                $brandStmt = $db->prepare($brandQuery);
                $brandStmt->bindParam(1, $data->brand_id);
                $brandStmt->execute();
                $brandResult = $brandStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($brandResult) {
                    $brandName = $brandResult['name'];
                    $brandId = (int)$data->brand_id;
                }
            } elseif (!empty($data->brand)) {
                // Handle legacy brand text input
                $brandName = $data->brand;
                
                // Try to find existing brand by name
                $findBrandQuery = "SELECT id FROM brands WHERE name = ? AND is_active = 1";
                $findBrandStmt = $db->prepare($findBrandQuery);
                $findBrandStmt->bindParam(1, $brandName);
                $findBrandStmt->execute();
                $findResult = $findBrandStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($findResult) {
                    $brandId = (int)$findResult['id'];
                } else {
                    // Create new brand if it doesn't exist
                    $insertBrandQuery = "INSERT INTO brands (name) VALUES (?)";
                    $insertBrandStmt = $db->prepare($insertBrandQuery);
                    $insertBrandStmt->bindParam(1, $brandName);
                    if ($insertBrandStmt->execute()) {
                        $brandId = (int)$db->lastInsertId();
                    }
                }
            }
            
            if (empty($brandName)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Brand is required'
                ]);
                break;
            }

            // Generate automatic product code
            $codeQuery = "SELECT MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) as max_num FROM products WHERE code LIKE 'IBS%'";
            $codeStmt = $db->prepare($codeQuery);
            $codeStmt->execute();
            $result = $codeStmt->fetch(PDO::FETCH_ASSOC);

            $nextNumber = ($result['max_num'] ?? 0) + 1;
            $generatedCode = 'IBS' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Generate EAN-13 barcode
            $barcode = generateEAN13Barcode($generatedCode);

            // Force min_stock and category_id to 0
            $minStock = 0;
            $categoryId = 0;

            $query = "INSERT INTO products (code, barcode, brand, model, purchase_price, min_selling_price, suggested_price, stock, min_stock, category_id, description, serial_number, imei, color, supplier_id, has_imei, brand_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $generatedCode);
            $stmt->bindParam(2, $barcode);
            $stmt->bindParam(3, $brandName);
            $stmt->bindParam(4, $data->model);
            $stmt->bindParam(5, $purchase_price);
            $stmt->bindParam(6, $min_selling_price);
            $stmt->bindParam(7, $suggested_price);
            $stmt->bindParam(8, $stock);
            $stmt->bindParam(9, $minStock);
            $stmt->bindParam(10, $categoryId);
            $description = $data->description ?? '';
            $stmt->bindParam(11, $description);
            $serialNumber = $data->serial_number ?? null;
            $stmt->bindParam(12, $serialNumber);
            $imei = $data->imei ?? null;
            $stmt->bindParam(13, $imei);
            $color = $data->color ?? null;
            $stmt->bindParam(14, $color);
            $supplierId = $data->supplier_id ?? null;
            $stmt->bindParam(15, $supplierId);
            $hasImei = isset($data->has_imei) ? (int)$data->has_imei : 0;
            $stmt->bindParam(16, $hasImei);
            $stmt->bindParam(17, $brandId);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => "Product added successfully! Code: $generatedCode, Barcode: $barcode",
                    'code' => $generatedCode,
                    'barcode' => $barcode,
                    'id' => $db->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add product'
                ]);
            }
        }
        break;

    case 'PUT':
        // Update product details or stock
        $input = file_get_contents("php://input");
        $data = json_decode($input);

        if (!empty($data->id)) {
            // Check if this is a stock-only update (for sales) or full product update
            if (isset($data->brand) && isset($data->model) && isset($data->price)) {
                // Full product update - use suggested_price for backward compatibility
                $price = intval(round(floatval($data->price) * 100));
                $stock = intval($data->stock);
                $minStock = isset($data->min_stock) ? intval($data->min_stock) : null;
                $categoryId = isset($data->category_id) ? intval($data->category_id) : null;
                $imageUrl = isset($data->image_url) ? $data->image_url : null;
                $isActive = isset($data->is_active) ? intval($data->is_active) : null;

                // Validate input
                if (empty($data->brand) || empty($data->model)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Brand and Model are required fields'
                    ]);
                    break;
                }

                if ($price <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Price must be greater than 0'
                    ]);
                    break;
                }

                if ($stock < 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Stock quantity cannot be negative'
                    ]);
                    break;
                }

                // Build dynamic update to avoid overwriting when optional fields are omitted
                // Map UI 'price' to DB 'suggested_price' (dump schema has no 'price' column)
                $fields = [
                    'brand' => $data->brand,
                    'model' => $data->model,
                    'suggested_price' => $price,
                    'stock' => $stock,
                    'description' => ($data->description ?? '')
                ];
                if ($minStock !== null) {
                    $fields['min_stock'] = $minStock;
                }
                if ($categoryId !== null) {
                    $fields['category_id'] = $categoryId;
                }
                if ($imageUrl !== null) {
                    $fields['image_url'] = $imageUrl;
                }
                if ($isActive !== null) {
                    $fields['is_active'] = $isActive;
                }
                if (isset($data->purchase_price) && $data->purchase_price !== '') {
                    $fields['purchase_price'] = intval(round(floatval($data->purchase_price) * 100));
                }
                if (isset($data->min_selling_price) && $data->min_selling_price !== '') {
                    $fields['min_selling_price'] = intval(round(floatval($data->min_selling_price) * 100));
                }
                // Add new fields
                if (isset($data->serial_number)) {
                    $fields['serial_number'] = $data->serial_number;
                }
                if (isset($data->imei)) {
                    $fields['imei'] = $data->imei;
                }
                if (isset($data->color)) {
                    $fields['color'] = $data->color;
                }
                if (isset($data->supplier_id)) {
                    $fields['supplier_id'] = $data->supplier_id;
                }
                if (isset($data->has_imei)) {
                    $fields['has_imei'] = (int)$data->has_imei;
                }

                $setClauses = [];
                $params = [];
                foreach ($fields as $column => $value) {
                    $setClauses[] = "$column = ?";
                    $params[] = $value;
                }
                $params[] = $data->id;

                $query = "UPDATE products SET " . implode(', ', $setClauses) . " WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute($params)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product updated successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update product'
                    ]);
                }
            } elseif (isset($data->stock)) {
                // Stock-only update (for sales)
                $query = "UPDATE products SET stock = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stockValue = intval($data->stock);
                $productId = intval($data->id);
                $stmt->bindParam(1, $stockValue);
                $stmt->bindParam(2, $productId);

                if ($stmt->execute()) {
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Stock updated successfully'
                    ]);
                } else {
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update stock'
                    ]);
                }
            } elseif (isset($data->is_active) && !empty($data->id)) {
                // Status-only update (activate/deactivate)
                $isActive = intval($data->is_active);
                $productId = intval($data->id);
                $query = "UPDATE products SET is_active = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $isActive);
                $stmt->bindParam(2, $productId);

                if ($stmt->execute()) {
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product ' . ($isActive ? 'activated' : 'deactivated') . ' successfully'
                    ]);
                } else {
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update product status'
                    ]);
                }
            } else {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields for update'
                ]);
            }

        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
        }
        break;

    case 'DELETE':
        // Delete product (Admin only)
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

            if (!$data || empty($data->id)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Product ID is required'
                ]);
                break;
            }

            $productId = intval($data->id);

            if ($productId <= 0) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid product ID'
                ]);
                break;
            }

            // Check if product exists and has no sales
            $checkQuery = "SELECT COUNT(*) as sale_count FROM sale_items WHERE product_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(1, $productId);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($result['sale_count'] > 0) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete product that has been sold. Please use the "Deactivate" button instead to hide it from the inventory.'
                ]);
                break;
            }

            // Delete the product
            $deleteQuery = "DELETE FROM products WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(1, $productId);

            if ($deleteStmt->execute()) {
                if ($deleteStmt->rowCount() > 0) {
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product deleted successfully'
                    ]);
                } else {
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Product not found'
                    ]);
                }
            } else {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete product'
                ]);
            }
        } catch (Throwable $e) {
            ob_clean();
            http_response_code(500);
            error_log("Delete product error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting product: ' . $e->getMessage(),
                'error_details' => $e->getFile() . ':' . $e->getLine()
            ]);
        } catch (Exception $e) {
            ob_clean();
            http_response_code(500);
            error_log("Delete product exception: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting product: ' . $e->getMessage()
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

// Barcode generation function
function generateEAN13Barcode($productCode) {
    // Extract numeric part from product code
    $numeric = preg_replace('/[^0-9]/', '', $productCode);
    
    // Pad to 12 digits (EAN-13 without checksum)
    if (strlen($numeric) < 12) {
        $numeric = str_pad($numeric, 12, '0', STR_PAD_LEFT);
    } elseif (strlen($numeric) > 12) {
        $numeric = substr($numeric, 0, 12);
    }
    
    // Calculate checksum
    $checksum = calculateEAN13Checksum($numeric);
    
    return $numeric . $checksum;
}

function calculateEAN13Checksum($digits) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int) $digits[$i];
        if ($i % 2 == 0) {
            $sum += $digit;
        } else {
            $sum += $digit * 3;
        }
    }
    $checksum = (10 - ($sum % 10)) % 10;
    return $checksum;
}

// End output buffering
ob_end_flush();
?>