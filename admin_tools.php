<?php
// Admin Tools for ATTIA Mobile Shop
// Simple interface to manage users and view database data

header('Content-Type: text/html; charset=UTF-8');
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>ATTIA Admin Tools</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { padding: 8px 15px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
        .form-group { margin: 10px 0; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 200px; padding: 5px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>";

echo "<h1>🛠️ ATTIA Admin Tools</h1>";

if (!$db) {
    echo "<div class='error'>❌ Database connection failed!</div>";
    exit;
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        
        $insert_query = "INSERT INTO users (username, password, role, name, phone, email, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt->execute([$username, $password, $role, $name, $phone, $email])) {
            echo "<div class='success'>✅ User '$username' added successfully!</div>";
        } else {
            echo "<div class='error'>❌ Failed to add user!</div>";
        }
    }
    
    if (isset($_POST['update_password'])) {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$new_password, $user_id])) {
            echo "<div class='success'>✅ Password updated successfully!</div>";
        } else {
            echo "<div class='error'>❌ Failed to update password!</div>";
        }
    }
}

// Display current users
echo "<div class='section'>
    <h2>👥 Current Users (Plain Text Passwords)</h2>";

$users_query = "SELECT * FROM users ORDER BY role, username";
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
            <th>Phone</th>
            <th>Email</th>
            <th>Status</th>
            <th>Created</th>
        </tr>";
    
    while ($user = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $user['is_active'] ? '✅ Active' : '❌ Inactive';
        echo "<tr>
            <td>{$user['id']}</td>
            <td><strong>{$user['username']}</strong></td>
            <td><code>{$user['password']}</code></td>
            <td>{$user['role']}</td>
            <td>{$user['name']}</td>
            <td>{$user['phone']}</td>
            <td>{$user['email']}</td>
            <td>$status</td>
            <td>{$user['created_at']}</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found.</p>";
}

echo "</div>";

// Add new user form
echo "<div class='section'>
    <h2>➕ Add New User</h2>
    <form method='POST'>
        <div class='form-group'>
            <label>Username:</label>
            <input type='text' name='username' required>
        </div>
        <div class='form-group'>
            <label>Password (Plain Text):</label>
            <input type='text' name='password' required>
        </div>
        <div class='form-group'>
            <label>Role:</label>
            <select name='role' required>
                <option value=''>Select Role</option>
                <option value='staff'>Staff</option>
                <option value='admin'>Admin</option>
            </select>
        </div>
        <div class='form-group'>
            <label>Full Name:</label>
            <input type='text' name='name' required>
        </div>
        <div class='form-group'>
            <label>Phone:</label>
            <input type='text' name='phone'>
        </div>
        <div class='form-group'>
            <label>Email:</label>
            <input type='email' name='email'>
        </div>
        <button type='submit' name='add_user' class='btn'>Add User</button>
    </form>
</div>";

// Update password form
echo "<div class='section'>
    <h2>🔑 Update User Password</h2>
    <form method='POST'>
        <div class='form-group'>
            <label>Select User:</label>
            <select name='user_id' required>";

$users_stmt->execute();
while ($user = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<option value='{$user['id']}'>{$user['username']} ({$user['role']})</option>";
}

echo "    </select>
        </div>
        <div class='form-group'>
            <label>New Password (Plain Text):</label>
            <input type='text' name='new_password' required>
        </div>
        <button type='submit' name='update_password' class='btn'>Update Password</button>
    </form>
</div>";

// Database info
echo "<div class='section'>
    <h2>📊 Database Information</h2>
    <p><strong>Database:</strong> attia_mobile_shop</p>
    <p><strong>Password Storage:</strong> Plain text (for phpMyAdmin access)</p>
    <p><strong>phpMyAdmin Access:</strong> You can directly edit users table</p>
    
    <h3>Quick Actions:</h3>
    <a href='setup_verification.php' class='btn'>🔍 Verify Setup</a>
    <a href='test_db.php' class='btn'>🧪 Test Database</a>
    <a href='index.html' class='btn'>🚀 Launch App</a>
</div>";

// Recent sales
echo "<div class='section'>
    <h2>💰 Recent Sales</h2>";

$sales_query = "SELECT s.*, u.name as staff_name, c.name as customer_name 
                FROM sales s 
                LEFT JOIN users u ON s.staff_id = u.id 
                LEFT JOIN customers c ON s.customer_id = c.id 
                ORDER BY s.created_at DESC LIMIT 5";
$sales_stmt = $db->prepare($sales_query);
$sales_stmt->execute();

if ($sales_stmt->rowCount() > 0) {
    echo "<table>
        <tr>
            <th>Receipt #</th>
            <th>Staff</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Date</th>
        </tr>";
    
    while ($sale = $sales_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
            <td>{$sale['receipt_number']}</td>
            <td>{$sale['staff_name']}</td>
            <td>" . ($sale['customer_name'] ?? 'Walk-in') . "</td>
            <td>$" . number_format($sale['total_amount'], 2) . "</td>
            <td>{$sale['created_at']}</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No sales found.</p>";
}

echo "</div>";

echo "</body></html>";
?>
