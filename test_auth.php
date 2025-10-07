<?php
// Test Authentication Script
header('Content-Type: text/html; charset=UTF-8');
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>ATTIA Authentication Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .test-form { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>";

echo "<h1>🔐 ATTIA Authentication Test</h1>";

if (!$db) {
    echo "<div class='error'>❌ Database connection failed!</div>";
    exit;
}

echo "<div class='success'>✅ Database connected successfully</div>";

// Show all users in database
echo "<h2>👥 Users in Database</h2>";
$users_query = "SELECT id, username, password, role, name, is_active FROM users";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();

if ($users_stmt->rowCount() > 0) {
    echo "<table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Password</th>
            <th>Role</th>
            <th>Name</th>
            <th>Active</th>
        </tr>";
    
    while ($user = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
        $active = $user['is_active'] ? '✅' : '❌';
        echo "<tr>
            <td>{$user['id']}</td>
            <td><strong>{$user['username']}</strong></td>
            <td><code>{$user['password']}</code></td>
            <td>{$user['role']}</td>
            <td>{$user['name']}</td>
            <td>$active</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No users found in database!</div>";
    echo "<p><strong>Solution:</strong> Import sample_data_plain.sql</p>";
    echo "<pre>mysql -u root -p attia_mobile_shop < database/sample_data_plain.sql</pre>";
}

// Test authentication
if ($_POST) {
    echo "<h2>🧪 Authentication Test Result</h2>";
    
    $test_username = $_POST['username'];
    $test_password = $_POST['password'];
    $test_role = $_POST['role'];
    
    echo "<div class='info'>Testing: $test_username / $test_password / $test_role</div>";
    
    // Same query as auth.php
    $auth_query = "SELECT id, username, password, role, name, phone, email FROM users WHERE username = ? AND role = ? AND is_active = 1";
    $auth_stmt = $db->prepare($auth_query);
    $auth_stmt->bindParam(1, $test_username);
    $auth_stmt->bindParam(2, $test_role);
    $auth_stmt->execute();
    
    if ($auth_stmt->rowCount() > 0) {
        $auth_row = $auth_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='success'>✅ User found in database</div>";
        echo "<p><strong>Database password:</strong> <code>{$auth_row['password']}</code></p>";
        echo "<p><strong>Entered password:</strong> <code>$test_password</code></p>";
        
        if ($auth_row['password'] === $test_password) {
            echo "<div class='success'>✅ Password matches! Authentication should work.</div>";
            echo "<pre>" . json_encode([
                "success" => true,
                "user" => [
                    "id" => $auth_row['id'],
                    "username" => $auth_row['username'],
                    "role" => $auth_row['role'],
                    "name" => $auth_row['name']
                ]
            ], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<div class='error'>❌ Password does not match!</div>";
        }
    } else {
        echo "<div class='error'>❌ User not found or inactive</div>";
    }
}

// Test form
echo "<div class='test-form'>
    <h2>🧪 Test Login</h2>
    <form method='POST'>
        <p>
            <label>Username:</label><br>
            <input type='text' name='username' value='staff1' style='width: 200px; padding: 5px;'>
        </p>
        <p>
            <label>Password:</label><br>
            <input type='text' name='password' value='staff123' style='width: 200px; padding: 5px;'>
        </p>
        <p>
            <label>Role:</label><br>
            <select name='role' style='width: 210px; padding: 5px;'>
                <option value='staff' selected>staff</option>
                <option value='admin'>admin</option>
            </select>
        </p>
        <button type='submit' style='padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px;'>Test Authentication</button>
    </form>
</div>";

// API test
echo "<h2>🔗 API Test</h2>";
echo "<p>Test the API directly:</p>";
echo "<pre>
POST /ATTIA/api/auth.php
Content-Type: application/json

{
    \"username\": \"staff1\",
    \"password\": \"staff123\",
    \"role\": \"staff\"
}
</pre>";

echo "<p><a href='api/auth.php' target='_blank' style='color: #007bff;'>Open auth.php directly</a> (will show error - needs POST data)</p>";

echo "</body></html>";
?>
