<?php
session_start();
include_once 'config/database.php';

// Handle login
if ($_POST && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM users WHERE username = ? AND password = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $password]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
        } else {
            header('Location: staff_dashboard.php');
        }
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en" id="html-lang">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBS Mobile Shop - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/translations.js"></script>
</head>
<body class="login-body" id="body-lang">
    <!-- Language Toggle Button -->
    <button class="language-toggle" id="languageToggle" onclick="toggleLanguage()" title="Toggle Language">
        <i class="fas fa-language"></i>
        <span class="lang-text">EN</span>
    </button>
    
    <div class="login-container">
        <div class="logo">
            <img src="assets/css/logo.jpeg" alt="IBS Store Logo" style="width: 120px; height: auto; margin-bottom: 20px; border-radius: 12px; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);" />
            <p data-translate="login.title">Mobile Shop Management</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="console.log('Form submitting...'); return true;">
            <div class="form-group">
                <label for="username" data-translate="login.username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password" data-translate="login.password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" name="login" class="login-btn" onclick="console.log('Login button clicked');">
                🔐 <span data-translate="login.login">Login</span>
            </button>
        </form>
    </div>
    
    <script>
        // Language toggle function using the new translation system
        function toggleLanguage() {
            if (typeof langManager !== 'undefined') {
                langManager.toggleLanguage();
            }
        }
        
        // Apply initial language when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof langManager !== 'undefined') {
                langManager.init();
            }
        });
    </script>
</body>
</html>
