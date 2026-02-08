<?php
// Barcode generation and management API
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
        if (isset($_GET['barcode'])) {
            // Get product by barcode
            $barcode = $_GET['barcode'];
            $query = "SELECT p.*, s.name as supplier_name, c.name as category_name
                      FROM products p 
                      LEFT JOIN suppliers s ON p.supplier_id = s.id 
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.barcode = ? AND p.is_active = 1";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$barcode]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => (int) $product['id'],
                        'code' => $product['code'],
                        'barcode' => $product['barcode'],
                        'brand' => $product['brand'],
                        'model' => $product['model'],
                        'purchase_price' => (float) $product['purchase_price'],
                        'min_selling_price' => (float) $product['min_selling_price'],
                        'suggested_price' => (float) $product['suggested_price'],
                        'stock' => (int) $product['stock'],
                        'color' => $product['color'],
                        'supplier_name' => $product['supplier_name'] ?? 'Unknown',
                        'category_name' => $product['category_name'] ?? 'Uncategorized'
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product not found for this barcode'
                ]);
            }
        } else {
            // Get all products with barcodes
            $query = "SELECT id, code, barcode, brand, model, stock FROM products WHERE barcode IS NOT NULL AND barcode != '' ORDER BY code";
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
                    'stock' => (int) $row['stock']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
        }
        break;
        
    case 'POST':
        // Generate barcode for a product
        $data = json_decode(file_get_contents('php://input'));
        
        if (!isset($data->product_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
            exit;
        }
        
        $productId = (int) $data->product_id;
        
        // Get product info
        $query = "SELECT code FROM products WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
            exit;
        }
        
        // Generate EAN-13 barcode (13 digits)
        $barcode = generateEAN13Barcode($product['code']);
        
        // Update product with barcode
        $updateQuery = "UPDATE products SET barcode = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$barcode, $productId])) {
            echo json_encode([
                'success' => true,
                'message' => 'Barcode generated successfully',
                'barcode' => $barcode
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate barcode'
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
?>
