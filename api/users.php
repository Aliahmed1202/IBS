<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all users/staff
        $query = "SELECT id, username, name, role, phone, email, is_active, created_at 
                  FROM users 
                  ORDER BY role, name";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'name' => $row['name'],
                'role' => $row['role'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'is_active' => (bool)$row['is_active'],
                'status' => $row['is_active'] ? 'Active' : 'Inactive',
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
        break;
        
    case 'POST':
        // Add new user (Admin only)
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->username) && !empty($data->password) && !empty($data->name) && !empty($data->role)) {
            $query = "INSERT INTO users (username, password, name, role, phone, email, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, 1)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->username);
            $stmt->bindParam(2, $data->password);
            $stmt->bindParam(3, $data->name);
            $stmt->bindParam(4, $data->role);
            $stmt->bindParam(5, $data->phone ?? '');
            $stmt->bindParam(6, $data->email ?? '');
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add user'
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
        // Update user details
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id)) {
            try {
                // Check if password is being updated
                if (isset($data->password) && !empty($data->password)) {
                    // Update all user fields including password
                    $query = "UPDATE users SET name = ?, role = ?, phone = ?, email = ?, is_active = ?, password = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    $stmt->bindParam(1, $data->name);
                    $stmt->bindParam(2, $data->role);
                    $stmt->bindParam(3, $data->phone);
                    $stmt->bindParam(4, $data->email);
                    $stmt->bindParam(5, $data->is_active);
                    $stmt->bindParam(6, $data->password);
                    $stmt->bindParam(7, $data->id);
                } else {
                    // Update all user fields except password
                    $query = "UPDATE users SET name = ?, role = ?, phone = ?, email = ?, is_active = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    $stmt->bindParam(1, $data->name);
                    $stmt->bindParam(2, $data->role);
                    $stmt->bindParam(3, $data->phone);
                    $stmt->bindParam(4, $data->email);
                    $stmt->bindParam(5, $data->is_active);
                    $stmt->bindParam(6, $data->id);
                }
                
                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'User updated successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update user'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
        }
        break;
}
?>
