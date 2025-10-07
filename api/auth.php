<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password) && !empty($data->role)) {
    
    $query = "SELECT id, username, password, role, name, phone, email FROM users WHERE username = ? AND role = ? AND is_active = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->username);
    $stmt->bindParam(2, $data->role);
    $stmt->execute();
    
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Simple password check - plain text for phpMyAdmin access
        // Password is stored as plain text in database for easy management
        
        // Log authentication attempt
        error_log("Authentication attempt: " . $data->username . " as " . $data->role);
        error_log("Database password: " . $row['password']);
        error_log("Entered password: " . $data->password);
        
        // Direct password comparison with database stored password
        if ($row['password'] === $data->password) {
            error_log("Password match successful for: " . $data->username);
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "user" => [
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "role" => $row['role'],
                    "name" => $row['name'],
                    "phone" => $row['phone'],
                    "email" => $row['email']
                ]
            ]);
        } else {
            error_log("Password mismatch for: " . $data->username);
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "Invalid credentials - password mismatch"
            ]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "User not found or inactive"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Incomplete data"
    ]);
}
?>
