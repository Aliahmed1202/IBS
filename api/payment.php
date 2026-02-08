<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all payment entries
        try {
            $query = "SELECT pe.*, u.name as created_by_name
                     FROM payment_entries pe
                     LEFT JOIN users u ON pe.created_by = u.id
                     ORDER BY pe.entry_date DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();

            $payment_entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $payment_entries[] = [
                    'id' => (int) $row['id'],
                    'price' => (float) ($row['price'] / 100),
                    'description' => $row['description'],
                    'entry_date' => $row['entry_date'],
                    'created_by' => (int) $row['created_by'],
                    'created_by_name' => $row['created_by_name'],
                    'created_at' => $row['created_at']
                ];
            }

            echo json_encode(['success' => true, 'data' => $payment_entries]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching payment entries: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Add new payment entry
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['price']) || !isset($data['description'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Price and description are required']);
            exit;
        }

        $price = intval(round(floatval($data['price']) * 100));
        $description = trim($data['description']);

        if ($price <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
            exit;
        }

        if (empty($description)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Description cannot be empty']);
            exit;
        }

        try {
            $query = "INSERT INTO payment_entries (price, description, created_by) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$price, $description, $_SESSION['user_id']]);

            if ($result) {
                $newId = $db->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment entry added successfully',
                    'payment_id' => $newId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add payment entry']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error adding payment entry: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Update payment entry
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['id']) || !isset($data['price']) || !isset($data['description'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID, price and description are required']);
            exit;
        }

        $id = intval($data['id']);
        $price = intval(round(floatval($data['price']) * 100));
        $description = trim($data['description']);

        if ($price <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
            exit;
        }

        if (empty($description)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Description cannot be empty']);
            exit;
        }

        try {
            $query = "UPDATE payment_entries SET price = ?, description = ? WHERE id = ? AND created_by = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$price, $description, $id, $_SESSION['user_id']]);

            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Payment entry updated successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Payment entry not found or not authorized to edit']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating payment entry: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Delete payment entry
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Payment entry ID is required']);
            exit;
        }

        $id = intval($_GET['id']);

        try {
            // Admin can delete any entry, regular users can only delete their own
            $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

            if ($isAdmin) {
                $query = "DELETE FROM payment_entries WHERE id = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([$id]);
            } else {
                $query = "DELETE FROM payment_entries WHERE id = ? AND created_by = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([$id, $_SESSION['user_id']]);
            }

            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Payment entry deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Payment entry not found or not authorized to delete']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error deleting payment entry: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>