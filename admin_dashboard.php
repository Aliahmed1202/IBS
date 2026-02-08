<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Barcode generation functions
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

$staffMembers = [];
try {
    // Debug: Check database connection
    error_log("Database connection status: " . ($db ? "Connected" : "Not connected"));
    
    // Test query to check if table exists and has data
    $testQuery = "SELECT COUNT(*) as total FROM users";
    $testStmt = $db->prepare($testQuery);
    $testStmt->execute();
    $totalUsers = $testStmt->fetch(PDO::FETCH_ASSOC)['total'];
    error_log("Total users in database: " . $totalUsers);
    
    $query = "SELECT id, username, name, role, is_active, created_at 
              FROM users 
              ORDER BY role, name";
    error_log("SQL Query: " . $query);
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    error_log("Execute result: " . ($result ? "Success" : "Failed"));
    
    $rowCount = $stmt->rowCount();
    error_log("Row count: " . $rowCount);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $staffMembers[] = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'name' => $row['name'],
            'role' => $row['role'],
            'phone' => '', // Phone column doesn't exist in database
            'email' => '', // Email column doesn't exist in database
            'is_active' => (bool) $row['is_active'],
            'status' => $row['is_active'] ? 'Active' : 'Inactive',
            'created_at' => $row['created_at']
        ];
    }
    
    // Debug: Log loaded data
    error_log("Staff members loaded: " . count($staffMembers) . " items");
    if (count($staffMembers) > 0) {
        error_log("First staff member: " . print_r($staffMembers[0], true));
    } else {
        error_log("No staff members found in database");
    }
} catch (Exception $e) {
    error_log("Error loading staff members: " . $e->getMessage());
    $staffMembers = [];
}

if (isset($_GET['logout'])) {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    if (session_destroy()) {
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Redirect to login page
        header('Location: index.php');
        exit;
    } else {
        // If session destruction fails, try alternative method
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['name']);
        header('Location: index.php');
        exit;
    }
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_product'])) {
        // Validate required fields and values
        $purchase_price = floatval($_POST['purchase_price']);
        $min_selling_price = floatval($_POST['min_selling_price']);
        $suggested_price = floatval($_POST['suggested_price']);
        $stock = intval($_POST['stock']);

        if ($purchase_price <= 0) {
            $error = "Purchase price must be greater than 0.";
        } elseif ($min_selling_price <= 0) {
            $error = "Minimum selling price must be greater than 0.";
        } elseif ($suggested_price <= 0) {
            $error = "Suggested selling price must be greater than 0.";
        } elseif ($min_selling_price < $purchase_price) {
            $error = "Minimum selling price cannot be less than purchase price.";
        } elseif ($suggested_price < $min_selling_price) {
            $error = "Suggested selling price cannot be less than minimum selling price.";
        } elseif ($stock <= 0) {
            $error = "Stock quantity must be greater than 0.";
        } elseif (empty($_POST['brand']) || empty($_POST['model'])) {
            $error = "Brand and Model are required fields.";
        } else {
            // Generate automatic product code
            $codeQuery = "SELECT MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) as max_num FROM products WHERE code LIKE 'IBS%'";
            $codeStmt = $db->prepare($codeQuery);
            $codeStmt->execute();
            $result = $codeStmt->fetch(PDO::FETCH_ASSOC);

            $nextNumber = ($result['max_num'] ?? 0) + 1;
            $generatedCode = 'IBS' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Generate EAN-13 barcode
            $barcode = generateEAN13Barcode($generatedCode);

            // Handle optional min_stock and category_id
            $minStock = !empty($_POST['min_stock']) ? (int)$_POST['min_stock'] : null;
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

            $query = "INSERT INTO products (code, barcode, brand, model, purchase_price, min_selling_price, suggested_price, stock, min_stock, category_id, description) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            if ($stmt->execute([$generatedCode, $barcode, $_POST['brand'], $_POST['model'], $purchase_price, $min_selling_price, $suggested_price, $stock, $minStock, $categoryId, $_POST['description']])) {
                // Redirect to prevent form resubmission
                header('Location: admin_dashboard.php?success=' . urlencode("Product added successfully! Product Code: $generatedCode, Barcode: $barcode"));
                exit;
            } else {
                $error = "Failed to add product. Please try again.";
            }
        }
    }

    if (isset($_POST['add_user'])) {
        $query = "INSERT INTO users (username, password, role, name, phone, email, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$_POST['username'], $_POST['password'], $_POST['role'], $_POST['name'], $_POST['phone'], $_POST['email']])) {
            // Redirect to prevent form resubmission
            header('Location: admin_dashboard.php?success=' . urlencode("User added successfully!"));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title data-translate="navigation.dashboard">Admin Dashboard - IBS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/translations.js"></script>
</head>

<body id="body-lang">
    <!-- Language Toggle Button -->
    <button class="language-toggle" id="languageToggle" onclick="toggleLanguage()" title="Toggle Language">
        <i class="fas fa-language"></i>
        <span class="lang-text">EN</span>
    </button>
    
    <div class="header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="assets/css/logo.jpeg" alt="IBS Store Logo" style="width: 40px; height: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" />
            <h1 data-translate="navigation.dashboard">🛠️ IBS Admin Dashboard</h1>
        </div>
        <div>
            <span data-translate="navigation.welcome">Welcome</span>, <?php echo $_SESSION['name']; ?>
            <a href="?logout=1" 
               style="color: white; margin-left: 15px; text-decoration: none; padding: 8px 15px; border-radius: 6px; transition: all 0.3s; display: inline-block; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); font-weight: 500; position: relative; z-index: 1000;" 
               data-translate="navigation.logout" 
               onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(-2px)';" 
               onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)';">
                🚪 Logout
            </a>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="showTab('receipt')" data-translate="sales.receipt">🧾 Receipt</button>
        <button class="nav-tab" onclick="showTab('products')" data-translate="inventory.addProduct">📦 Add Product</button>
        <button class="nav-tab" onclick="showTab('inventory')" data-translate="navigation.inventory">📋 Inventory</button>
        <button class="nav-tab" onclick="showTab('sales')" data-translate="navigation.sales">💰 Sales</button>
        <button class="nav-tab" onclick="showTab('reports')" data-translate="navigation.reports">📊 Reports</button>
        <button class="nav-tab" onclick="showTab('staff')" data-translate="navigation.staff">👥 Staff</button>
        <button class="nav-tab" onclick="showTab('income')" data-translate="navigation.income">💰 Income</button>
        <button class="nav-tab" onclick="showTab('payment')" data-translate="navigation.payment">💸 Payment</button>
    </div>

    <div class="content">
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Receipt Tab -->
        <div id="receipt" class="tab-content active">
            <div class="section">
                <h2 data-translate="sales.createReceipt">🧾 Create Receipt</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <h3 data-translate="sales.customerInfo">Customer Information</h3>
                        <div class="form-group">
                            <label data-translate="sales.customerName">Customer Name:</label>
                            <input type="text" id="customer-name" placeholder="Enter customer name" data-translate-placeholder="sales.customerNamePlaceholder">
                        </div>
                        <div class="form-group">
                            <label data-translate="sales.customerPhone">Customer Phone:</label>
                            <input type="tel" id="customer-phone" placeholder="Enter phone number" data-translate-placeholder="sales.customerPhonePlaceholder">
                        </div>
                        <div class="form-group">
                            <label data-translate="sales.selectProduct">Select Product:</label>
                            <select id="product-select" onchange="updateSellingPrice()">
                                <option value="" data-translate="sales.chooseProduct">Choose a product...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label data-translate="sales.sellingPrice">Selling Price:</label>
                            <input type="number" id="selling-price" step="0.01" min="0" readonly>
                        </div>
                        <div class="form-group">
                            <label data-translate="sales.quantity">Quantity:</label>
                            <input type="number" id="quantity" min="1" value="1" data-translate-placeholder="sales.quantityPlaceholder">
                        </div>
                        <button class="btn" onclick="addToReceipt()" id="add-product-btn" disabled data-translate="sales.addToReceipt">Add to Receipt</button>
                    </div>
                    <div>
                        <h3 data-translate="sales.receiptItems">Receipt Items</h3>
                        <div id="receipt-items" style="border: 1px solid #ddd; padding: 15px; min-height: 200px;">
                            <div style="text-align: center; color: #666;" data-translate="sales.noItemsAdded">No items added yet</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; margin-top: 15px;">
                            <div style="font-weight: bold; font-size: 1.2em;" data-translate="sales.total">Total: <span id="total"> 0.00 EGP</span>
                            </div>
                        </div>
                        <button class="btn btn-success" onclick="completeReceipt()" id="complete-btn" disabled data-translate="sales.completeSale">Complete
                            Sale</button>
                        <button class="btn btn-danger" onclick="clearReceipt()" data-translate="sales.clearReceipt">Clear</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Product Tab -->
        <div id="products" class="tab-content">
            <div class="section">
                <h2 data-translate="inventory.addProduct">📦 Add New Product</h2>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <div class="form-group">
                                <label data-translate="inventory.productCode">Product Code:</label>
                                <div
                                    style="padding: 10px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 5px; color: #666; font-weight: bold;">
                                    Will be auto-generated
                                </div>
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.autoGenerated">Product code will be automatically created
                                    when you save</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.brand"><span style="color: red;">*</span> Brand:</label>
                                <input type="text" name="brand" required
                                    style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.model"><span style="color: red;">*</span> Model:</label>
                                <input type="text" name="model" required
                                    style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.purchasePrice"><span style="color: red;">*</span> Purchase Price (Cost):</label>
                                <input type="number" name="purchase_price" step="0.01" min="0.01" required
                                    placeholder="What you pay to buy this product"
                                    style="width: 70%; max-width: 70%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.purchasePriceHelp">The cost price you pay to suppliers</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.minPrice"><span style="color: red;">*</span> Minimum Selling Price:</label>
                                <input type="number" name="min_selling_price" step="0.01" min="0.01" required
                                    placeholder="Lowest price allowed for sale"
                                    style="width: 70%; max-width: 70%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.minPriceHelp">Staff cannot sell below this price</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.suggestedPrice"><span style="color: red;">*</span> Suggested Selling Price:</label>
                                <input type="number" name="suggested_price" step="0.01" min="0.01" required
                                    placeholder="Recommended selling price"
                                    style="width: 70%; max-width: 70%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.suggestedPriceHelp">Default price shown in receipts (can be
                                    changed)</small>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label data-translate="inventory.stockQuantity"><span style="color: red;">*</span> Stock Quantity:</label>
                                <input type="number" name="stock" min="1" required
                                    placeholder="Enter stock quantity (must be greater than 0)"
                                    style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;"></small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.serialNumber">Serial Number:</label>
                                <input type="text" name="serial_number" 
                                    placeholder="Enter serial number (optional)"
                                    style="width: 70%; max-width: 70%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.serialHelp">Unique serial number for tracking</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.color">Color:</label>
                                <input type="text" name="color" 
                                    placeholder="Enter product color"
                                    style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.colorHelp">Product color (e.g., Black, White, Blue)</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.supplier">Supplier:</label>
                                <select name="supplier_id" id="supplier-select"
                                    style="width: 70%; max-width: 70%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                    <option value="" data-translate="inventory.selectSupplier">Select Supplier</option>
                                </select>
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.supplierHelp">Choose supplier for this product</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.hasImei">Has IMEI:</label>
                                <select name="has_imei" id="has-imei-select"
                                    style="width: 30%; max-width: 30%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                    <option value="0" data-translate="common.no">No</option>
                                    <option value="1" data-translate="common.yes">Yes</option>
                                </select>
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.imeiHelp">Does this product have IMEI?</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.minStock">Minimum Stock:</label>
                                <input type="number" name="min_stock" placeholder="Leave empty for no minimum stock"
                                    style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.minStockHelp">Optional: Set minimum stock level for alerts</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.category">Category:</label>
                                <select name="category_id" id="category-select"
                                    style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                    <option value="" data-translate="inventory.selectCategory">Select Category</option>
                                    <option value="1" data-translate="inventory.phones">Phones</option>
                                    <option value="2" data-translate="inventory.airpods">AirPods</option>
                                    <option value="3" data-translate="inventory.watch">Watch</option>
                                    <option value="4" data-translate="inventory.accessories">Accessories</option>
                                    <option value="5" data-translate="inventory.tablets">Tablets</option>
                                </select>
                                <small style="color: #666; font-size: 12px;" data-translate="inventory.categoryHelp">Choose product category</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="inventory.description">Description:</label>
                                <textarea name="description" rows="3"
                                    style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="btn" data-translate="inventory.addProduct">Add Product</button>
                </form>
            </div>
        </div>

        <!-- Inventory Tab -->
        <div id="inventory" class="tab-content">
            <div class="section">
                <h2 data-translate="inventory.title">📋 Inventory Management</h2>
                <div id="inventory-stats" class="stats-grid"></div>

                <!-- Search Bar -->
                <div class="form-group" style="margin: 20px 0;">
                    <label data-translate="inventory.search">🔍 Search Products:</label>
                    <input type="text" id="inventory-search"
                        placeholder="Search by code, brand, model, or description..."
                        style="width: 100%; max-width: 500px; font-size: 16px; padding: 12px;">
                    <div id="search-results-count" style="margin-top: 5px; color: #666; font-size: 14px;"></div>
                </div>

                <table id="inventory-table">
                    <thead>
                        <tr>
                            <th data-translate="inventory.code">Code</th>
                            <th data-translate="inventory.barcode">Barcode</th>
                            <th data-translate="inventory.product">Product</th>
                            <th data-translate="inventory.category">Category</th>
                            <th data-translate="inventory.color">Color</th>
                            <th data-translate="inventory.serial">Serial/IMEI</th>
                            <th data-translate="inventory.supplier">Supplier</th>
                            <th data-translate="inventory.purchasePrice">Purchase Price</th>
                            <th data-translate="inventory.minPrice">Min Price</th>
                            <th data-translate="inventory.suggestedPrice">Suggested Price</th>
                            <th data-translate="inventory.stock">Stock</th>
                            <th data-translate="inventory.status">Status</th>
                            <th data-translate="inventory.stockDetails">Stock Details</th>
                            <th data-translate="inventory.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-tbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Sales Tab -->
        <div id="sales" class="tab-content">
            <div class="section">
                <h2>💰 Sales Management</h2>
                <div id="sales-stats" class="stats-grid"></div>

                <!-- Search Bar -->
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <button onclick="startBarcodeScanner()" class="btn btn-primary" style="padding: 12px 20px; background: #28a745;">
                        📷 Scan Barcode
                    </button>
                    <div style="flex: 1; max-width: 400px;">
                        <input type="text" id="salesSearchInput" placeholder="🔍 Search by Receipt Number " data-translate-placeholder="sales.searchReceipt"
                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;"
                            onkeyup="filterSales(this.value)"
                            onkeypress="if(event.key==='Enter') filterSales(this.value)">
                    </div>
                    <button onclick="clearSalesSearch()" class="btn btn-secondary"
                        style="padding: 12px 20px;">Clear</button>
                </div>

                <!-- Search Results Count -->
                <div id="sales-search-results-count" style="margin-bottom: 15px; color: #666; font-size: 14px;">
                    Showing all sales
                </div>

                <table>
                    <thead>
                        <tr>
                            <th data-translate="sales.receiptNumber">Receipt #</th>
                            <th data-translate="sales.date">Date</th>
                            <th data-translate="sales.staff">Staff</th>
                            <th data-translate="sales.customer">Customer</th>
                            <th data-translate="sales.totalAmount">Total Amount</th>
                            <th data-translate="sales.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sales-tbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reports" class="tab-content">
            <div class="section">
                <h2 data-translate="reports.title">📊 Reports</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3 id="today-sales">EGP 0</h3>
                        <p data-translate="reports.todaySales">Today's Sales</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="today-profit">EGP 0</h3>
                        <p data-translate="reports.todayProfit">Today's Profit</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="month-sales">EGP 0</h3>
                        <p data-translate="reports.monthSales">This Month Sales</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="month-profit">EGP 0</h3>
                        <p data-translate="reports.monthProfit">This Month Profit</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-products">0</h3>
                        <p data-translate="reports.totalProducts">Total Products</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="low-stock">0</h3>
                        <p data-translate="reports.lowStock">Low Stock Items</p>
                    </div>
                </div>

                <!-- Print Reports Section -->
                <div style="margin-top: 30px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 20px; font-size: 1.3em; border-left: 4px solid #0056b3; padding-left: 15px;"
                        data-translate="reports.printReports">🖨️ Print Reports</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">

                        <!-- Today's Sales Report -->
                        <div style="background: linear-gradient(135deg, #0056b3 0%, #007bff 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0, 86, 179, 0.3); transition: transform 0.3s ease;"
                            onmouseover="this.style.transform='translateY(-5px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div
                                    style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                                    <span style="font-size: 24px;">📅</span>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.2em;">Today's Sales</h4>
                                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Detailed sales report
                                        for today</p>
                                </div>
                            </div>
                            <button onclick="printTodaysSalesReport()"
                                style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                🖨️ Print Today's Sales
                            </button>
                        </div>

                        <!-- This Month's Sales Report -->
                        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3); transition: transform 0.3s ease;"
                            onmouseover="this.style.transform='translateY(-5px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div
                                    style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                                    <span style="font-size: 24px;">📊</span>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.2em;">This Month's Sales</h4>
                                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Complete monthly sales
                                        report</p>
                                </div>
                            </div>
                            <button onclick="printMonthSalesReport()"
                                style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                🖨️ Print Monthly Report
                            </button>
                        </div>

                        <!-- Total Products Report -->
                        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3); transition: transform 0.3s ease;"
                            onmouseover="this.style.transform='translateY(-5px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div
                                    style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                                    <span style="font-size: 24px;">📦</span>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.2em;">Total Products</h4>
                                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Complete inventory list
                                    </p>
                                </div>
                            </div>
                            <button onclick="printProductsReport()"
                                style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                🖨️ Print Products List
                            </button>
                        </div>

                        <!-- Low Stock Items Report -->
                        <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3); transition: transform 0.3s ease;"
                            onmouseover="this.style.transform='translateY(-5px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div
                                    style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                                    <span style="font-size: 24px;">⚠️</span>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.2em;">Low Stock Items</h4>
                                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Items requiring
                                        restocking</p>
                                </div>
                            </div>
                            <button onclick="printLowStockReport()"
                                style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                🖨️ Print Low Stock Alert
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Management Tab -->
        <div id="staff" class="tab-content">
            <div class="section">
                <h2 data-translate="staff.title">👥 Staff Management</h2>
                <h3 data-translate="staff.addStaff">Add New Staff</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <div class="form-group">
                                <label data-translate="staff.username">Username:</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group">
                                <label data-translate="staff.password">Password:</label>
                                <input type="text" name="password" required>
                            </div>
                            <div class="form-group">
                                <label data-translate="staff.role">Role:</label>
                                <select name="role" required>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label data-translate="staff.name">Full Name:</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label data-translate="staff.phone">Phone:</label>
                                <input type="text" name="phone">
                            </div>
                            <div class="form-group">
                                <label data-translate="staff.email">Email:</label>
                                <input type="email" name="email">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn" data-translate="staff.addStaff">Add Staff</button>
                </form>

                <h3 style="margin-top: 30px;" data-translate="staff.currentStaff">Current Staff</h3>

                <!-- Staff Search Bar -->
                <div class="form-group" style="margin: 20px 0;">
                    <label data-translate="staff.searchStaff">🔍 Search Staff:</label>
                    <input type="text" id="staff-search" placeholder="Search by username, name, role, or phone..."
                        style="width: 100%; max-width: 500px; font-size: 16px; padding: 12px;">
                    <div id="staff-search-results-count" style="margin-top: 5px; color: #666; font-size: 14px;"></div>
                </div>

                <table id="staff-table">
                    <thead>
                        <tr>
                            <th data-translate="staff.username">Username</th>
                            <th data-translate="staff.name">Name</th>
                            <th data-translate="staff.role">Role</th>
                            <th data-translate="staff.phone">Phone</th>
                            <th data-translate="staff.status">Status</th>
                            <th data-translate="staff.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staff-tbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Income Tab -->
        <div id="income" class="tab-content">
            <div class="section">
                <h2 data-translate="income.title">💰 Income Management</h2>

                <!-- Add Income Entry Form -->
                <div style="margin-bottom: 30px;">
                    <h3 data-translate="income.addNewIncome">Add New Income Entry</h3>
                    <form id="addIncomeForm">
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                            <div class="form-group">
                                <label data-translate="income.price">Price: <span style="color: red;">*</span></label>
                                <input type="number" id="income-price" step="0.01" min="0.01" required
                                    placeholder="Enter income amount"
                                    style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="income.amountGreaterThanZero">Amount must be greater than 0</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="income.description">Description: <span style="color: red;">*</span></label>
                                <textarea id="income-description" rows="2" required
                                    placeholder="Describe the source of income..."
                                    style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                                <small style="color: #666; font-size: 12px;" data-translate="income.provideIncomeDetails">Provide details about this income entry</small>
                            </div>
                        </div>
                        <button type="submit" class="btn" style="margin-top: 10px;" data-translate="income.addIncomeEntry">Add Income Entry</button>
                    </form>
                </div>

                <!-- Income Entries List -->
                <div>
                    <h3 data-translate="income.incomeEntries">Income Entries</h3>
                    <div id="income-stats" class="stats-grid" style="margin-bottom: 20px;"></div>
                    <table id="income-table">
                        <thead>
                            <tr>
                                <th data-translate="income.date">Date</th>
                                <th data-translate="income.description">Description</th>
                                <th data-translate="income.amount">Amount</th>
                                <th data-translate="income.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="income-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Tab -->
        <div id="payment" class="tab-content">
            <div class="section">
                <h2 data-translate="payment.title">💸 Payment Management</h2>

                <!-- Add Payment Entry Form -->
                <div style="margin-bottom: 30px;">
                    <h3 data-translate="payment.addNewPayment">Add New Payment Entry</h3>
                    <form id="addPaymentForm">
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                            <div class="form-group">
                                <label data-translate="payment.amount">Amount: <span style="color: red;">*</span></label>
                                <input type="number" id="payment-price" step="0.01" min="0.01" required
                                    placeholder="Enter payment amount"
                                    style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <small style="color: #666; font-size: 12px;" data-translate="payment.amountGreaterThanZero">Amount must be greater than 0</small>
                            </div>
                            <div class="form-group">
                                <label data-translate="payment.description">Description: <span style="color: red;">*</span></label>
                                <textarea id="payment-description" rows="2" required
                                    placeholder="Describe the payment/expense..."
                                    style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                                <small style="color: #666; font-size: 12px;" data-translate="payment.providePaymentDetails">Provide details about this payment entry</small>
                            </div>
                        </div>
                        <button type="submit" class="btn" style="margin-top: 10px;" data-translate="payment.addPaymentEntry">Add Payment Entry</button>
                    </form>
                </div>

                <!-- Payment Entries List -->
                <div>
                    <h3 data-translate="payment.paymentEntries">Payment Entries</h3>
                    <div id="payment-stats" class="stats-grid" style="margin-bottom: 20px;"></div>
                    <table id="payment-table">
                        <thead>
                            <tr>
                                <th data-translate="payment.date">Date</th>
                                <th data-translate="payment.description">Description</th>
                                <th data-translate="payment.amount">Amount</th>
                                <th data-translate="payment.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="payment-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal"
        style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
        <div
            style="background-color: white; margin: 2% auto; padding: 40px; border-radius: 15px; width: 90%; max-width: 700px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); min-height: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #333; font-size: 1.8em;">✏️ Edit User Information</h2>
                <button onclick="closeEditModal()"
                    style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: background 0.3s;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='none'">&times;</button>
            </div>

            <form id="editUserForm">
                <input type="hidden" id="editUserId">

                <!-- Account Information Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        🔐 Account Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" id="editUsername" readonly
                                style="background: #f5f5f5; cursor: not-allowed;">
                            <small style="color: #666; font-size: 12px;">Username cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label>New Password:</label>
                            <input type="password" id="editPassword" placeholder="Leave blank to keep current password"
                                minlength="4">
                            <small style="color: #666; font-size: 12px;">Minimum 4 characters (optional)</small>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        👤 Personal Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Full Name: <span style="color: red;">*</span></label>
                            <input type="text" id="editName" required>
                        </div>

                        <div class="form-group">
                            <label>Role: <span style="color: red;">*</span></label>
                            <select id="editRole" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                        </div>
                    </div>

                    <!-- Account Status Section -->
                    <div style="margin-bottom: 25px;">
                        <h3
                            style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                            🔐 Account Status</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Status: <span style="color: red;">*</span></label>
                                <select id="editStatus" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                        <button type="button" onclick="closeEditModal()" class="btn btn-secondary"
                            style="padding: 12px 25px; font-size: 16px;">Cancel</button>
                        <button type="submit" class="btn" style="padding: 12px 25px; font-size: 16px;">💾 Save
                            Changes</button>
                    </div>
                </form>
                <div
                    style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary"
                        style="padding: 12px 25px; font-size: 16px;">Cancel</button>
                    <button type="submit" class="btn" style="padding: 12px 25px; font-size: 16px;">💾 Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal"
        style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
        <div
            style="background-color: white; margin: 2% auto; padding: 40px; border-radius: 15px; width: 90%; max-width: 700px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); min-height: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #333; font-size: 1.8em;">📦 Edit Product Information</h2>
                <button onclick="closeEditProductModal()"
                    style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: background 0.3s;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='none'">&times;</button>
            </div>

            <form id="editProductForm">
                <input type="hidden" id="editProductId">

                <!-- Product Code Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        🏷️ Product Code</h3>
                    <div class="form-group">
                        <label>Product Code:</label>
                        <input type="text" id="editProductCode" readonly
                            style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; background: #f5f5f5; cursor: not-allowed;">
                        <small style="color: #666; font-size: 12px;">Product code cannot be changed</small>

                <!-- Categorization & Status Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        🏷️ Categorization & Status</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Category ID:</label>
                            <input type="number" id="editProductCategoryId" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                            <small style="color: #666; font-size: 12px;">Numeric category identifier</small>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select id="editProductIsActive"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / span 2;">
                            <label>Image URL:</label>
                            <input type="text" id="editProductImageUrl"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                    </div>
                </div>
                
                <!-- Pricing & Stock Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #28a745; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #28a745; padding-left: 10px;">
                        💰 Pricing & Stock</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Brand:</label>
                            <input type="text" id="editProductBrand" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Model:</label>
                            <input type="text" id="editProductModel" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Price:</label>
                            <input type="number" id="editProductPrice" step="0.01" min="0.01" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity:</label>
                            <input type="number" id="editProductStock" min="0" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Minimum Stock:</label>
                            <input type="number" id="editProductMinStock" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Purchase Price:</label>
                            <input type="number" id="editProductPurchasePrice" step="0.01" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Minimum Selling Price:</label>
                            <input type="number" id="editProductMinSellingPrice" step="0.01" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div style="margin-bottom: 30px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        📝 Additional Information</h3>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea id="editProductDescription" rows="3"
                            style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div
                    style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <button type="button" onclick="closeEditProductModal()" class="btn btn-secondary"
                        style="padding: 12px 25px; font-size: 16px;">Cancel</button>
                    <button type="submit" class="btn" style="padding: 12px 25px; font-size: 16px;">💾 Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Receipt Details Modal -->
    <div id="receiptDetailsModal"
        style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
        <div
            style="background-color: white; margin: 2% auto; padding: 40px; border-radius: 15px; width: 90%; max-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); min-height: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #333; font-size: 1.8em;" data-translate="sales.receiptDetails">🧾 Receipt Details</h2>
                <button onclick="closeReceiptDetailsModal()"
                    style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: background 0.3s;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='none'">&times;</button>
            </div>

            <!-- Receipt Header Information -->
            <div style="margin-bottom: 25px;">
                <h3
                    style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;"
                    data-translate="sales.receiptInformation">📋 Receipt Information</h3>
                <div
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <div>
                        <strong data-translate="sales.receiptNumberLabel">Receipt Number:</strong> <span id="detailReceiptNumber"></span><br>
                        <strong data-translate="sales.date">Date:</strong> <span id="detailDate"></span><br>
                        <strong data-translate="sales.staff">Staff:</strong> <span id="detailStaff"></span>
                    </div>
                    <div>
                        <strong data-translate="sales.customer">Customer:</strong> <span id="detailCustomer"></span><br>
                        <strong data-translate="sales.paymentMethod">Payment Method:</strong> <span id="detailPaymentMethod"></span><br>
                        <strong data-translate="sales.totalAmount">Total Amount:</strong> <span id="detailTotalAmount"
                            style="color: #28a745; font-weight: bold;"></span>
                    </div>
                </div>
            </div>

            <!-- Items List -->
            <div style="margin-bottom: 25px;">
                <h3
                    style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                    🛍️ Items Purchased</h3>
                <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding: 12px; text-align: left;">Product</th>
                                <th style="padding: 12px; text-align: center;">Quantity</th>
                                <th style="padding: 12px; text-align: right;">Unit Price</th>
                                <th style="padding: 12px; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody id="receiptItemsTable">
                            <!-- Items will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Receipt Summary -->
            <div style="margin-bottom: 30px;">
                <h3
                    style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;"
                    data-translate="sales.paymentSummary">💰 Payment Summary</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2em;">
                        <span data-translate="sales.total">Total:</span>
                        <span id="detailGrandTotal" style="color: #28a745;"></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                <button type="button" onclick="closeReceiptDetailsModal()" class="btn btn-secondary"
                    style="padding: 12px 25px; font-size: 16px;" data-translate="sales.close">Close</button>
                <button type="button" onclick="printReceiptFromModal()" class="btn btn-success"
                    style="padding: 12px 25px; font-size: 16px;" data-translate="sales.printReceipt">🖨️ Print Receipt</button>
            </div>
        </div>
    </div>

    <!-- Additional CSS for better modal styling -->
    <style>
        @media (max-width: 768px) {
            #editUserModal>div {
                margin: 5% auto !important;
                width: 95% !important;
                padding: 20px !important;
            }

            #editUserModal div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }

            #editUserModal h2 {
                font-size: 1.4em !important;
            }

            #editUserModal h3 {
                font-size: 1.1em !important;
            }

            #editUserModal .form-group input,
            #editUserModal .form-group select {
                max-width: 100% !important;
            }
        }

        #editUserModal>div {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Improve form field styling */
        #editUserModal .form-group {
            margin-bottom: 15px;
        }

        #editUserModal .form-group input,
        #editUserModal .form-group select {
            width: 100%;
            max-width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        #editUserModal .form-group input:focus,
        #editUserModal .form-group select:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }

        #editUserModal .form-group label {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
    </style>

    <script>
        let currentReceipt = { items: [], subtotal: 0, tax: 0, total: 0 };
        let products = [];
        let allInventoryProducts = []; // Store all products for search filtering
        let allStaffMembers = <?php echo json_encode($staffMembers); ?>; // Store all staff for search filtering
        let allSuppliers = []; // Store all suppliers for dropdown
        document.addEventListener('DOMContentLoaded', function () {
            // Check if we should show inventory tab (coming from stock_items page)
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab === 'inventory') {
                // Clear the stored value
                localStorage.removeItem('activeTab');
                // Show inventory tab after a short delay to ensure page is loaded
                setTimeout(() => {
                    showTab('inventory');
                }, 100);
            }
            
            loadSuppliers();
            loadProducts();
            loadInventory();
            loadSales();
            loadStaff();
            loadReports();
            loadIncome();
            loadPayment();

            // Check if product was just added and refresh inventory
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success')) {
                // Clear any search filters
                const searchInput = document.getElementById('inventory-search');
                if (searchInput) {
                    searchInput.value = '';
                }
                // Refresh inventory after a short delay to ensure database is updated
                setTimeout(function () {
                    loadInventory();
                    // Switch to inventory tab if not already there
                    const inventoryTab = document.querySelector('.nav-tab[onclick*="inventory"]');
                    if (inventoryTab) {
                        inventoryTab.click();
                    }
                }, 500);
            }

            // Add search functionality
            const searchInput = document.getElementById('inventory-search');
            searchInput.addEventListener('input', function () {
                filterInventory(this.value);
            });

            // Add staff search functionality
            const staffSearchInput = document.getElementById('staff-search');
            staffSearchInput.addEventListener('input', function () {
                filterStaff(this.value);
            });

            // Handle edit form submission
            const editForm = document.getElementById('editUserForm');
            if (editForm) {
                editForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const userId = document.getElementById('editUserId').value;
                    const password = document.getElementById('editPassword').value;

                    const userData = {
                        id: parseInt(userId),
                        name: document.getElementById('editName').value,
                        role: document.getElementById('editRole').value,
                        is_active: parseInt(document.getElementById('editStatus').value)
                    };

                    // Only include password if it's not empty
                    if (password.trim()) {
                        if (password.length < 4) {
                            alert('Password must be at least 4 characters long');
                            return;
                        }
                        userData.password = password;
                    }

                    try {
                        const response = await fetch('api/users.php', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(userData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            const message = password.trim() ?
                                'User updated successfully! Password has been changed.' :
                                'User updated successfully!';
                            alert(message);
                            closeEditModal();

                            // Update the local data
                            const userIndex = allStaffMembers.findIndex(member => member.id === parseInt(userId));
                            if (userIndex !== -1) {
                                allStaffMembers[userIndex] = {
                                    ...allStaffMembers[userIndex],
                                    ...userData,
                                    status: userData.is_active ? 'Active' : 'Inactive'
                                };
                                displayStaff(allStaffMembers);

                                // Update search results count
                                const resultsCountDiv = document.getElementById('staff-search-results-count');
                                if (resultsCountDiv) {
                                    resultsCountDiv.textContent = `Showing all ${allStaffMembers.length} staff members`;
                                    resultsCountDiv.style.color = '#666';
                                }
                            }
                        } else {
                            alert('Failed to update user: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error updating user:', error);
                        alert('Error updating user. Please try again.');
                    }
                });
            }

            // Handle edit product form submission
            const editProductForm = document.getElementById('editProductForm');
            if (editProductForm) {
                editProductForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const productId = document.getElementById('editProductId').value;
                    const price = parseFloat(document.getElementById('editProductPrice').value);
                    const stock = parseInt(document.getElementById('editProductStock').value);
                    const minStock = parseInt(document.getElementById('editProductMinStock').value || '0');
                    const categoryId = parseInt(document.getElementById('editProductCategoryId').value || '0');
                    const isActive = parseInt(document.getElementById('editProductIsActive').value || '1');
                    const imageUrl = document.getElementById('editProductImageUrl').value || '';
                    const purchasePrice = parseFloat(document.getElementById('editProductPurchasePrice').value || '0');
                    const minSellingPrice = parseFloat(document.getElementById('editProductMinSellingPrice').value || '0');

                    // Validate input
                    if (price <= 0) {
                        alert('Price must be greater than 0');
                        return;
                    }

                    if (stock < 0) {
                        alert('Stock quantity cannot be negative');
                        return;
                    }
                    if (minStock < 0) {
                        alert('Minimum stock cannot be negative');
                        return;
                    }
                    if (categoryId < 0) {
                        alert('Category ID cannot be negative');
                        return;
                    }
                    if (!isNaN(purchasePrice) && purchasePrice < 0) {
                        alert('Purchase price cannot be negative');
                        return;
                    }
                    if (!isNaN(minSellingPrice) && minSellingPrice < 0) {
                        alert('Minimum selling price cannot be negative');
                        return;
                    }
                    if (!isNaN(purchasePrice) && !isNaN(minSellingPrice) && minSellingPrice > 0 && purchasePrice > 0 && minSellingPrice < purchasePrice) {
                        alert('Minimum selling price cannot be less than purchase price');
                        return;
                    }
                    if (!isNaN(minSellingPrice) && minSellingPrice > 0 && price < minSellingPrice) {
                        alert('Suggested price cannot be less than minimum selling price');
                        return;
                    }

                    const productData = {
                        id: parseInt(productId),
                        brand: document.getElementById('editProductBrand').value,
                        model: document.getElementById('editProductModel').value,
                        price: price,
                        stock: stock,
                        min_stock: minStock,
                        category_id: categoryId,
                        is_active: isActive,
                        image_url: imageUrl,
                        purchase_price: isNaN(purchasePrice) ? undefined : purchasePrice,
                        min_selling_price: isNaN(minSellingPrice) ? undefined : minSellingPrice,
                        description: document.getElementById('editProductDescription').value
                    };

                    try {
                        const response = await fetch('api/products.php', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(productData)
                        });

                        const result = await response.json();
                        console.log('API Response:', result);
                        console.log('Response status:', response.status);

                        if (result.success) {
                            alert('Product updated successfully!');
                            closeEditProductModal();

                            // Update the local data
                            const productIndex = allInventoryProducts.findIndex(p => p.id === parseInt(productId));
                            if (productIndex !== -1) {
                                allInventoryProducts[productIndex] = {
                                    ...allInventoryProducts[productIndex],
                                    ...productData
                                };
                                displayInventory(allInventoryProducts);
                                displayInventoryStats(allInventoryProducts);

                                // Update search results count if needed
                                const resultsCountDiv = document.getElementById('search-results-count');
                                if (resultsCountDiv) {
                                    resultsCountDiv.textContent = `Showing all ${allInventoryProducts.length} products`;
                                    resultsCountDiv.style.color = '#666';
                                }
                            }

                            // Refresh products for POS system
                            loadProducts();
                        } else {
                            alert('Failed to update product: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error updating product:', error);
                        console.error('Full error details:', {
                            message: error.message,
                            stack: error.stack,
                            productData: productData
                        });
                        alert('Error updating product: ' + error.message + '. Check console for details.');
                    }
                });
            }

            // Handle add income form submission
            const addIncomeForm = document.getElementById('addIncomeForm');
            if (addIncomeForm) {
                addIncomeForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const price = parseFloat(document.getElementById('income-price').value);
                    const description = document.getElementById('income-description').value.trim();

                    // Validate input
                    if (price <= 0) {
                        alert('Price must be greater than 0');
                        return;
                    }

                    if (!description) {
                        alert('Description is required');
                        return;
                    }

                    const incomeData = {
                        price: price,
                        description: description
                    };

                    try {
                        const response = await fetch('api/income.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(incomeData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('Income entry added successfully!');
                            addIncomeForm.reset();
                            loadIncome(); // Refresh the income list
                        } else {
                            alert('Failed to add income entry: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error adding income entry:', error);
                        alert('Error adding income entry. Please try again.');
                    }
                });
            }

            // Handle add payment form submission
            const addPaymentForm = document.getElementById('addPaymentForm');
            if (addPaymentForm) {
                addPaymentForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const price = parseFloat(document.getElementById('payment-price').value);
                    const description = document.getElementById('payment-description').value.trim();

                    // Validate input
                    if (price <= 0) {
                        alert('Amount must be greater than 0');
                        return;
                    }

                    if (!description) {
                        alert('Description is required');
                        return;
                    }

                    const paymentData = {
                        price: price,
                        description: description
                    };

                    try {
                        const response = await fetch('api/payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(paymentData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('Payment entry added successfully!');
                            addPaymentForm.reset();
                            loadPayment(); // Refresh the payment list
                            loadReports(); // Refresh profit calculations
                        } else {
                            alert('Failed to add payment entry: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error adding payment entry:', error);
                        alert('Error adding payment entry. Please try again.');
                    }
                });
            }
        });

        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        async function loadProducts() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();
                if (result.success) {
                    products = result.data;
                    allProducts = result.data.filter(p => p.stock > 0); // Only products with stock for search
                    populateProductSelect();
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Load suppliers for dropdown
        async function loadSuppliers() {
            try {
                const response = await fetch('api/suppliers.php');
                const result = await response.json();
                if (result.success) {
                    allSuppliers = result.suppliers;
                    populateSupplierSelect();
                }
            } catch (error) {
                console.error('Error loading suppliers:', error);
            }
        }

        function populateSupplierSelect() {
            const select = document.getElementById('supplier-select');
            if (select) {
                select.innerHTML = '<option value="">Select Supplier</option>';
                allSuppliers.forEach(supplier => {
                    select.innerHTML += `<option value="${supplier.id}">${supplier.name}</option>`;
                });
            }
        }

        function populateProductSelect() {
            const select = document.getElementById('product-select');
            select.innerHTML = '<option value="">Choose a product...</option>';
            products.filter(p => p.stock > 0).forEach(product => {
                select.innerHTML += `<option value="${product.id}" data-price="${product.price}" data-stock="${product.stock}" data-code="${product.code}" data-brand="${product.brand}" data-model="${product.model}">${product.brand} ${product.model} - ${product.price} EGP (Stock: ${product.stock})</option>`;
            });
        }

        // Global variables for product search
        let selectedProduct = null;
        let allProducts = [];

        function searchProducts(searchTerm) {
            const resultsDiv = document.getElementById('product-search-results');

            if (!searchTerm.trim()) {
                resultsDiv.style.display = 'none';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredProducts = allProducts.filter(product => {
                return product.code.toLowerCase().includes(searchLower) ||
                    product.brand.toLowerCase().includes(searchLower) ||
                    product.model.toLowerCase().includes(searchLower) ||
                    `${product.brand} ${product.model}`.toLowerCase().includes(searchLower);
            });

            if (filteredProducts.length === 0) {
                resultsDiv.innerHTML = '<div style="padding: 10px; color: #666; text-align: center;">No products found</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            resultsDiv.innerHTML = filteredProducts.slice(0, 10).map(product => `
                <div onclick="selectProduct(${product.id})" 
                     style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; hover:background: #f0f0f0;"
                     onmouseover="this.style.background='#f0f0f0'" 
                     onmouseout="this.style.background='white'">
                    <div style="font-weight: bold;">${product.brand} ${product.model}</div>
                    <div style="font-size: 12px; color: #666;">${product.code} - ${product.price.toFixed(2)} EGP (Stock: ${product.stock})</div>
                </div>
            `).join('');

            resultsDiv.style.display = 'block';
        }

        function selectProduct(productId) {
            selectedProduct = allProducts.find(p => p.id === productId);
            if (selectedProduct) {
                document.getElementById('selected-product').innerHTML = `
                    <div style="color: #333;">
                        <strong>${selectedProduct.brand} ${selectedProduct.model}</strong><br>
                        <small>Code: ${selectedProduct.code} | Min: ${selectedProduct.min_selling_price.toFixed(2)} EGP | Suggested: ${selectedProduct.suggested_price.toFixed(2)} EGP | Stock: ${selectedProduct.stock}</small>
                    </div>
                `;

                // Set the suggested price in the price input
                document.getElementById('selling-price').value = selectedProduct.suggested_price.toFixed(2);
                document.getElementById('selling-price').min = selectedProduct.min_selling_price;

                document.getElementById('add-product-btn').disabled = false;
                document.getElementById('product-search-results').style.display = 'none';
                document.getElementById('product-search').value = `${selectedProduct.brand} ${selectedProduct.model}`;
            }
        }

        function selectFirstProduct() {
            const resultsDiv = document.getElementById('product-search-results');
            const firstResult = resultsDiv.querySelector('div[onclick]');
            if (firstResult) {
                firstResult.click();
            }
        }

        function addToReceipt() {
            const quantityInput = document.getElementById('quantity');
            const sellingPriceInput = document.getElementById('selling-price');

            if (!selectedProduct || !quantityInput.value || !sellingPriceInput.value) {
                alert('Please select a product, enter selling price and quantity');
                return;
            }

            const quantity = parseInt(quantityInput.value);
            const sellingPrice = parseFloat(sellingPriceInput.value);

            if (sellingPrice < selectedProduct.min_selling_price) {
                alert(`Selling price cannot be less than minimum price: ${selectedProduct.min_selling_price.toFixed(2)} EGP `);
                return;
            }

            if (quantity > selectedProduct.stock) {
                alert(`Only ${selectedProduct.stock} items available`);
                return;
            }

            const existingItem = currentReceipt.items.find(item => item.productId === selectedProduct.id);

            if (existingItem) {
                if (existingItem.quantity + quantity <= selectedProduct.stock) {
                    existingItem.quantity += quantity;
                    existingItem.price = sellingPrice; // Update price to current selling price
                    existingItem.total = existingItem.quantity * existingItem.price;
                } else {
                    alert('Cannot exceed available stock');
                    return;
                }
            } else {
                currentReceipt.items.push({
                    productId: selectedProduct.id,
                    code: selectedProduct.code,
                    name: `${selectedProduct.brand} ${selectedProduct.model}`,
                    price: sellingPrice,
                    quantity: quantity,
                    total: sellingPrice * quantity
                });
            }

            updateReceiptDisplay();
            quantityInput.value = 1;

            // Clear selection
            selectedProduct = null;
            document.getElementById('selected-product').innerHTML = '<div style="color: #666;">No product selected</div>';
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search').value = '';
            document.getElementById('selling-price').value = '';
        }

        function updateReceiptDisplay() {
            const itemsDiv = document.getElementById('receipt-items');

            if (currentReceipt.items.length === 0) {
                itemsDiv.innerHTML = '<div style="text-align: center; color: #666;">No items added yet</div>';
            } else {
                itemsDiv.innerHTML = currentReceipt.items.map(item => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #eee;">
                        <div><strong>${item.name}</strong><br><small>${item.code}</small></div>
                        <div>${item.quantity} × ${item.price.toFixed(2)} EGP = ${item.total.toFixed(2)} EGP </div>
                        <button onclick="removeFromReceipt(${item.productId})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px;">×</button>
                    </div>
                `).join('');
            }

            currentReceipt.total = currentReceipt.items.reduce((sum, item) => sum + item.total, 0);

            document.getElementById('total').textContent = currentReceipt.total.toFixed(2);
            document.getElementById('complete-btn').disabled = currentReceipt.items.length === 0;
        }

        function removeFromReceipt(productId) {
            currentReceipt.items = currentReceipt.items.filter(item => item.productId !== productId);
            updateReceiptDisplay();
        }

        function clearReceipt() {
            currentReceipt = { items: [], total: 0 };
            selectedProduct = null;
            updateReceiptDisplay();
            document.getElementById('customer-name').value = '';
            document.getElementById('customer-phone').value = '';
            document.getElementById('product-search').value = '';
            document.getElementById('selected-product').innerHTML = '<div style="color: #666;">No product selected</div>';
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search-results').style.display = 'none';
            document.getElementById('selling-price').value = '';
        }

        async function completeReceipt() {
            const customerName = document.getElementById('customer-name').value.trim();
            if (!customerName || currentReceipt.items.length === 0) {
                alert('Please enter customer name and add items');
                return;
            }

            const completeBtn = document.getElementById('complete-btn');
            completeBtn.innerHTML = 'Processing...';
            completeBtn.disabled = true;

            try {
                let customerId = null;
                const customerPhone = document.getElementById('customer-phone').value.trim();

                // Try to add customer, but don't fail if it doesn't work
                if (customerPhone) {
                    try {
                        const customerResponse = await fetch('api/customers.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ name: customerName, phone: customerPhone })
                        });
                        const customerResult = await customerResponse.json();
                        if (customerResult.success) customerId = customerResult.customer_id;
                    } catch (customerError) {
                        console.warn('Customer creation failed, continuing without customer ID:', customerError);
                        // Continue with sale even if customer creation fails
                    }
                }

                const saleData = {
                    customer_id: customerId,
                    staff_id: <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>,
                    subtotal: currentReceipt.total,
                    tax_amount: 0,
                    total_amount: currentReceipt.total,
                    payment_method: 'cash',
                    staff_name: <?php echo json_encode(isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin'); ?>,
                    staff_username: <?php echo json_encode(isset($_SESSION['username']) ? $_SESSION['username'] : 'admin'); ?>,
                    items: currentReceipt.items.map(item => ({
                        product_id: item.productId,
                        quantity: item.quantity,
                        unit_price: item.price,
                        total_price: item.total
                    }))
                };

                console.log('Sending sale data:', saleData);

                const saleResponse = await fetch('api/sales.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });

                console.log('Sale response status:', saleResponse.status);

                if (!saleResponse.ok) {
                    let errorText = '';
                    try {
                        errorText = await saleResponse.text();
                        // Try to parse as JSON
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch {
                            alert('Server error: ' + saleResponse.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + saleResponse.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await saleResponse.text();
                console.log('Raw response:', responseText);

                let saleResult;
                try {
                    saleResult = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', responseText);
                    alert('Error: Invalid JSON response from server. Check console for details.');
                    return;
                }
                console.log('Sale result:', saleResult);

                if (saleResult.success) {
                    alert('Sale completed! Receipt #' + saleResult.receipt_number);
                    clearReceipt();
                    loadProducts();
                    loadInventory();
                    loadSales();
                    loadReports();
                } else {
                    alert('Failed to complete sale: ' + saleResult.message);
                }
            } catch (error) {
                console.error('Error completing sale:', error);
                alert('Error completing sale: ' + error.message);
            } finally {
                completeBtn.innerHTML = 'Complete Sale';
                completeBtn.disabled = false;
            }
        }

        async function loadInventory() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();
                if (result.success) {
                    allInventoryProducts = result.data; // Store all products for filtering

                    // Clear search filter to show all products
                    const searchInput = document.getElementById('inventory-search');
                    if (searchInput) {
                        searchInput.value = '';
                    }

                    displayInventory(result.data);
                    displayInventoryStats(result.data);

                    // Initialize search results count
                    const resultsCountDiv = document.getElementById('search-results-count');
                    if (resultsCountDiv) {
                        resultsCountDiv.textContent = `Showing all ${result.data.length} products`;
                        resultsCountDiv.style.color = '#666';
                    }
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
            }
        }

        function displayInventory(products) {
            const tbody = document.getElementById('inventory-tbody');
            tbody.innerHTML = products.map(product => {
                const categoryName = product.category_name || 'Uncategorized';
                const serialImei = product.has_imei ? 
                    (product.imei || 'No IMEI') : 
                    (product.serial_number || 'No Serial');
                const supplierName = product.supplier_name || 'Unknown';
                const color = product.color || 'N/A';
                const barcode = product.barcode || '<span style="color: #999;">No barcode</span>';
                
                return `
                <tr>
                    <td>${product.code}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-family: monospace; font-weight: bold; color: ${product.barcode ? '#000' : '#999'};">${barcode}</span>
                        </div>
                    </td>
                    <td>${product.brand} ${product.model}</td>
                    <td><span style="background: var(--primary-blue); color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">${categoryName}</span></td>
                    <td><span style="background: ${color.toLowerCase() === 'black' ? '#333' : color.toLowerCase() === 'white' ? '#f0f0f0' : color}; color: ${color.toLowerCase() === 'black' || color.toLowerCase() === 'blue' ? 'white' : 'black'}; padding: 2px 8px; border-radius: 12px; font-size: 12px;">${color}</span></td>
                    <td>${serialImei}</td>
                    <td>${supplierName}</td>
                    <td>${(product.purchase_price || 0).toFixed(2)} EGP </td>
                    <td>${(product.min_selling_price || 0).toFixed(2)} EGP </td>
                    <td>${(product.suggested_price || product.price || 0).toFixed(2)} EGP </td>
                    <td>${product.stock}</td>
                    <td><span class="${product.stock === 0 ? 'stock-out' : (product.min_stock !== null && product.stock <= product.min_stock ? 'stock-low' : 'stock-ok')}">${product.stock === 0 ? '🔴 Out of Stock' : (product.min_stock !== null && product.stock <= product.min_stock ? '⚠️ Low Stock' : '✅ In Stock')}</span></td>
                    <td>
                        <a href="stock_items.php?product_id=${product.id}" class="btn btn-info" style="padding: 5px 10px; font-size: 12px; margin-right: 5px; text-decoration: none; display: inline-block;">📋 View Items</a>
                    </td>
                    <td>
                        <button class="btn btn-primary" onclick="editProduct(${product.id})" style="margin-right: 5px;">Edit</button>
                        <button class="btn btn-success" onclick="printProductLabel(${product.id})" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">🏷️ Label</button>
                        ${product.is_active ?
                    `<button class="btn btn-warning" onclick="toggleProductStatus(${product.id}, false)" style="padding: 5px 10px; font-size: 12px; margin-right: 5px; background: #ff9800; color: white; border: none;">🚫 Deactivate</button>` :
                    `<button class="btn btn-info" onclick="toggleProductStatus(${product.id}, true)" style="padding: 5px 10px; font-size: 12px; margin-right: 5px; background: #2196F3; color: white; border: none;">✅ Activate</button>`
                }
                    </td>
                </tr>
            `;
            }).join('');
        }

        function displayInventoryStats(products) {
            const totalProducts = products.length;
            const lowStock = products.filter(p => p.min_stock !== null && p.stock > 0 && p.stock <= p.min_stock).length;
            const outOfStock = products.filter(p => p.stock === 0).length;
            const totalValue = products.reduce((sum, p) => sum + ((p.purchase_price || 0) * p.stock), 0);

            document.getElementById('inventory-stats').innerHTML = `
                <div class="stat-card"><h3>${totalProducts}</h3><p>Total Products</p></div>
                <div class="stat-card"><h3>${lowStock}</h3><p>Low Stock</p></div>
                <div class="stat-card"><h3>${outOfStock}</h3><p>Out of Stock</p></div>
                <div class="stat-card"><h3>${totalValue.toFixed(0)} EGP </h3><p>Inventory Value</p></div>
            `;
        }

        function filterInventory(searchTerm) {
            const resultsCountDiv = document.getElementById('search-results-count');

            if (!searchTerm.trim()) {
                // If search is empty, show all products
                displayInventory(allInventoryProducts);
                displayInventoryStats(allInventoryProducts);
                resultsCountDiv.textContent = `Showing all ${allInventoryProducts.length} products`;
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredProducts = allInventoryProducts.filter(product => {
                return (
                    product.code.toLowerCase().includes(searchLower) ||
                    product.brand.toLowerCase().includes(searchLower) ||
                    product.model.toLowerCase().includes(searchLower) ||
                    (product.description && product.description.toLowerCase().includes(searchLower)) ||
                    (product.category && product.category.toLowerCase().includes(searchLower))
                );
            });

            displayInventory(filteredProducts);
            displayInventoryStats(filteredProducts);

            // Update results count
            if (filteredProducts.length === 0) {
                resultsCountDiv.textContent = 'No products found matching your search';
                resultsCountDiv.style.color = '#dc3545';
            } else {
                resultsCountDiv.textContent = `Found ${filteredProducts.length} product${filteredProducts.length === 1 ? '' : 's'} matching "${searchTerm}"`;
                resultsCountDiv.style.color = '#28a745';
            }
        }

        // Global variable to store all sales data
        let allSalesData = [];

        async function loadSales() {
            try {
                const response = await fetch('api/sales.php');
                const result = await response.json();
                if (result.success) {
                    allSalesData = result.data; // Store all sales data for filtering
                    displaySales(result.data);
                    displaySalesStats(result.data);

                    // Initialize search results count
                    const resultsCountDiv = document.getElementById('sales-search-results-count');
                    if (resultsCountDiv) {
                        resultsCountDiv.textContent = `Showing all ${result.data.length} sales`;
                        resultsCountDiv.style.color = '#666';
                    }
                }
            } catch (error) {
                console.error('Error loading sales:', error);
            }
        }

        function displaySales(sales) {
            const tbody = document.getElementById('sales-tbody');
            tbody.innerHTML = sales.slice(0, 10).map(sale => `
                <tr>
                    <td>${sale.receipt_number}</td>
                    <td>${new Date(sale.sale_date).toLocaleDateString()}</td>
                    <td>${sale.staff_name}</td>
                    <td>${sale.customer_name}</td>
                    <td>${sale.total_amount.toFixed(2)} EGP </td>
                    <td>
                        <button class="btn btn-info" onclick="viewReceiptDetails(${sale.id})" style="margin-right: 5px; padding: 5px 10px; font-size: 12px;">👁️ View Details</button>
                        <button class="btn btn-success" onclick="printReceipt(${sale.id})" style="padding: 5px 10px; font-size: 12px;">🖨️ Print</button>
                    </td>
                </tr>
            `).join('');
        }

        function filterSales(searchTerm) {
            const resultsCountDiv = document.getElementById('sales-search-results-count');

            if (!searchTerm.trim()) {
                // If search is empty, show all sales
                displaySales(allSalesData);
                displaySalesStats(allSalesData);
                resultsCountDiv.textContent = `Showing all ${allSalesData.length} sales`;
                resultsCountDiv.style.color = '#666';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredSales = allSalesData.filter(sale => {
                return sale.receipt_number.toLowerCase().includes(searchLower);
            });

            displaySales(filteredSales);
            displaySalesStats(filteredSales);

            // Update results count
            if (filteredSales.length === 0) {
                resultsCountDiv.textContent = 'No receipts found matching your search';
                resultsCountDiv.style.color = '#dc3545';
            } else {
                resultsCountDiv.textContent = `Found ${filteredSales.length} receipt${filteredSales.length === 1 ? '' : 's'} matching "${searchTerm}"`;
                resultsCountDiv.style.color = '#28a745';
            }
        }

        function clearSalesSearch() {
            const searchInput = document.getElementById('salesSearchInput');
            const resultsCountDiv = document.getElementById('sales-search-results-count');

            searchInput.value = '';
            displaySales(allSalesData);
            displaySalesStats(allSalesData);
            resultsCountDiv.textContent = `Showing all ${allSalesData.length} sales`;
            resultsCountDiv.style.color = '#666';
        }

        async function displaySalesStats(sales) {
            const today = new Date().toDateString();
            const todaySales = sales.filter(s => new Date(s.sale_date).toDateString() === today);
            const thisMonth = new Date().getMonth();
            const monthSales = sales.filter(s => new Date(s.sale_date).getMonth() === thisMonth);

            // Calculate profit by getting product costs
            const todayProfit = await calculateProfit(todaySales, true);
            const monthProfit = await calculateProfit(monthSales, false);

            // Calculate total income (revenue) from sales
            const todaySalesIncome = todaySales.reduce((sum, sale) => sum + sale.total_amount, 0);
            const monthSalesIncome = monthSales.reduce((sum, sale) => sum + sale.total_amount, 0);

            // Fetch and calculate additional income and payments
            let todayIncomeEntries = 0;
            let monthIncomeEntries = 0;
            let todayPaymentEntries = 0;
            let monthPaymentEntries = 0;

            try {
                // Fetch income entries
                const incomeResponse = await fetch('api/income.php');
                const incomeResult = await incomeResponse.json();
                if (incomeResult.success) {
                    const todayIncomeData = incomeResult.data.filter(entry => new Date(entry.entry_date).toDateString() === today);
                    const monthIncomeData = incomeResult.data.filter(entry => new Date(entry.entry_date).getMonth() === thisMonth);

                    todayIncomeEntries = todayIncomeData.reduce((sum, entry) => sum + entry.price, 0);
                    monthIncomeEntries = monthIncomeData.reduce((sum, entry) => sum + entry.price, 0);
                }

                // Fetch payment entries
                const paymentResponse = await fetch('api/payment.php');
                const paymentResult = await paymentResponse.json();
                if (paymentResult.success) {
                    const todayPaymentData = paymentResult.data.filter(entry => new Date(entry.entry_date).toDateString() === today);
                    const monthPaymentData = paymentResult.data.filter(entry => new Date(entry.entry_date).getMonth() === thisMonth);

                    todayPaymentEntries = todayPaymentData.reduce((sum, entry) => sum + entry.price, 0);
                    monthPaymentEntries = monthPaymentData.reduce((sum, entry) => sum + entry.price, 0);
                }
            } catch (error) {
                console.error('Error fetching income/payment data:', error);
            }

            // Calculate adjusted income (sales + income entries - payment entries)
            const todayAdjustedIncome = todaySalesIncome + todayIncomeEntries - todayPaymentEntries;
            const monthAdjustedIncome = monthSalesIncome + monthIncomeEntries - monthPaymentEntries;

            document.getElementById('sales-stats').innerHTML = `
                <div class="stat-card"><h3>${todaySales.length}</h3><p>Today's Orders</p></div>
                <div class="stat-card"><h3>${todayProfit.toFixed(0)} EGP </h3><p>Today's Profit</p></div>
                <div class="stat-card"><h3>${monthSales.length}</h3><p>This Month Orders</p></div>
                <div class="stat-card"><h3>${monthProfit.toFixed(0)} EGP </h3><p>Month Profit</p></div>
                <div class="stat-card total-income-card"><h3>${todayAdjustedIncome.toFixed(2)} EGP</h3><p>💰 Today's Total Income</p></div>
                <div class="stat-card monthly-income-card"><h3>${monthAdjustedIncome.toFixed(2)} EGP</h3><p>📅 This Month's Total Income</p></div>
            `;
        }

        async function calculateProfit(sales, isToday = false) {
            let totalProfit = 0;

            // Get all products to access purchase prices
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();

                if (result.success) {
                    const products = result.data;
                    const productMap = {};

                    // Create a map for quick product lookup
                    products.forEach(product => {
                        productMap[product.id] = product;
                    });

                    // Calculate profit for each sale
                    for (const sale of sales) {
                        if (sale.items) {
                            for (const item of sale.items) {
                                const product = productMap[item.product_id];
                                if (product) {
                                    const purchasePrice = product.purchase_price || 0;
                                    const sellingPrice = item.unit_price || 0;
                                    const quantity = item.quantity || 0;

                                    const itemProfit = (sellingPrice - purchasePrice) * quantity;
                                    totalProfit += itemProfit;
                                }
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error calculating sales profit:', error);
                // Fallback to total sales if profit calculation fails
                totalProfit = sales.reduce((sum, s) => sum + s.total_amount, 0);
            }

            // Add income entries to total profit
            try {
                const incomeResponse = await fetch('api/income.php');
                const incomeResult = await incomeResponse.json();

                if (incomeResult.success) {
                    let incomeToAdd = incomeResult.data;

                    // Filter income entries by the same time period as sales
                    if (isToday) {
                        const today = new Date().toDateString();
                        incomeToAdd = incomeResult.data.filter(entry =>
                            new Date(entry.entry_date).toDateString() === today
                        );
                    } else {
                        const thisMonth = new Date().getMonth();
                        incomeToAdd = incomeResult.data.filter(entry =>
                            new Date(entry.entry_date).getMonth() === thisMonth
                        );
                    }

                    const totalIncome = incomeToAdd.reduce((sum, entry) => sum + entry.price, 0);
                    totalProfit += totalIncome;
                }
            } catch (incomeError) {
                console.error('Error calculating income profit:', incomeError);
                // Continue with sales profit only if income calculation fails
            }

            // Subtract payment entries from total profit
            try {
                const paymentResponse = await fetch('api/payment.php');
                const paymentResult = await paymentResponse.json();

                if (paymentResult.success) {
                    let paymentsToSubtract = paymentResult.data;

                    // Filter payment entries by the same time period as sales
                    if (isToday) {
                        const today = new Date().toDateString();
                        paymentsToSubtract = paymentResult.data.filter(entry =>
                            new Date(entry.entry_date).toDateString() === today
                        );
                    } else {
                        const thisMonth = new Date().getMonth();
                        paymentsToSubtract = paymentResult.data.filter(entry =>
                            new Date(entry.entry_date).getMonth() === thisMonth
                        );
                    }

                    const totalPayments = paymentsToSubtract.reduce((sum, entry) => sum + entry.price, 0);
                    totalProfit -= totalPayments;
                }
            } catch (paymentError) {
                console.error('Error calculating payment deductions:', paymentError);
                // Continue with current profit if payment calculation fails
            }

            return totalProfit;
        }

        async function loadStaff() {
            try {
                // Use PHP-loaded data directly
                if (allStaffMembers && allStaffMembers.length > 0) {
                    console.log('Loading staff from PHP data:', allStaffMembers);
                    
                    // Add small delay to ensure DOM is ready
                    setTimeout(() => {
                        displayStaff(allStaffMembers);
                        
                        // Initialize staff search results count
                        const resultsCountDiv = document.getElementById('staff-search-results-count');
                        if (resultsCountDiv) {
                            resultsCountDiv.textContent = `Showing all ${allStaffMembers.length} staff members`;
                            resultsCountDiv.style.color = '#666';
                        }
                    }, 100);
                    
                    return;
                }

                // If no PHP data, show message instead of calling API
                console.log('No staff data available from PHP');
                document.getElementById('staff-tbody').innerHTML = '<tr><td colspan="5">No staff data available</td></tr>';
                
            } catch (error) {
                console.error('Error loading staff:', error);
                document.getElementById('staff-tbody').innerHTML = `<tr><td colspan="5">Error loading staff: ${error.message}</td></tr>`;
            }
        }

        function displayStaff(staff) {
            console.log('displayStaff called with data:', staff);
            console.log('staff-tbody element:', document.getElementById('staff-tbody'));
            
            const tbody = document.getElementById('staff-tbody');
            if (!tbody) {
                console.error('staff-tbody element not found!');
                return;
            }
            
            tbody.innerHTML = staff.map(member => `
                <tr>
                    <td>${member.username}</td>
                    <td>${member.name}</td>
                    <td><span style="color: ${member.role === 'admin' ? '#0056b3' : '#28a745'};">${member.role.charAt(0).toUpperCase() + member.role.slice(1)}</span></td>
                    <td>${member.phone || 'N/A'}</td>
                    <td><span class="${member.is_active ? 'status-active' : 'status-inactive'}">${member.status}</span></td>
                    <td>
                        <button class="btn btn-sm" onclick="editUser(${member.id})" style="padding: 5px 10px; font-size: 12px;">
                            ✏️ Edit
                        </button>
                    </td>
                </tr>
            `).join('');
            
            console.log('Table HTML updated');
        }

        function filterStaff(searchTerm) {
            const resultsCountDiv = document.getElementById('staff-search-results-count');

            if (!searchTerm.trim()) {
                // If search is empty, show all staff
                displayStaff(allStaffMembers);
                resultsCountDiv.textContent = `Showing all ${allStaffMembers.length} staff members`;
                resultsCountDiv.style.color = '#666';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredStaff = allStaffMembers.filter(member => {
                return (
                    member.username.toLowerCase().includes(searchLower) ||
                    member.name.toLowerCase().includes(searchLower) ||
                    member.role.toLowerCase().includes(searchLower) ||
                    (member.phone && member.phone.toLowerCase().includes(searchLower)) ||
                    (member.email && member.email.toLowerCase().includes(searchLower))
                );
            });

            displayStaff(filteredStaff);

            // Update results count
            if (filteredStaff.length === 0) {
                resultsCountDiv.textContent = 'No staff members found matching your search';
                resultsCountDiv.style.color = '#dc3545';
            } else {
                resultsCountDiv.textContent = `Found ${filteredStaff.length} staff member${filteredStaff.length === 1 ? '' : 's'} matching "${searchTerm}"`;
                resultsCountDiv.style.color = '#28a745';
            }
        }

        function editUser(userId) {
            const user = allStaffMembers.find(member => member.id === userId);
            if (!user) {
                alert('User not found');
                return;
            }

            // Populate the edit form
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editPassword').value = ''; // Always start with empty password
            document.getElementById('editName').value = user.name;
            document.getElementById('editRole').value = user.role;
            document.getElementById('editPhone').value = user.phone || '';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editStatus').value = user.is_active ? '1' : '0';

            // Show the modal
            document.getElementById('editUserModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const userModal = document.getElementById('editUserModal');
            const productModal = document.getElementById('editProductModal');
            const receiptModal = document.getElementById('receiptDetailsModal');

            if (event.target === userModal) {
                closeEditModal();
            }
            if (event.target === productModal) {
                closeEditProductModal();
            }
            if (event.target === receiptModal) {
                closeReceiptDetailsModal();
            }
        }

        function editProduct(productId) {
            console.log('editProduct called with productId:', productId);
            console.log('allInventoryProducts available:', allInventoryProducts.length);
            
            const product = allInventoryProducts.find(p => p.id === productId);
            console.log('Found product:', product);
            
            if (!product) {
                alert('Product not found');
                return;
            }

            // Populate the edit form
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editProductCode').value = product.code;
            document.getElementById('editProductBrand').value = product.brand;
            document.getElementById('editProductModel').value = product.model;
            document.getElementById('editProductPrice').value = product.price;
            document.getElementById('editProductStock').value = product.stock;
            document.getElementById('editProductDescription').value = product.description || '';
            document.getElementById('editProductMinStock').value = (product.min_stock || '');
            document.getElementById('editProductCategoryId').value = (product.category_id || 0);
            if (document.getElementById('editProductIsActive')) {
                document.getElementById('editProductIsActive').value = (product.is_active ? '1' : '0');
            }
            document.getElementById('editProductImageUrl').value = product.image_url || '';
            if (typeof product.purchase_price !== 'undefined') {
                document.getElementById('editProductPurchasePrice').value = product.purchase_price;
            }
            if (typeof product.min_selling_price !== 'undefined') {
                document.getElementById('editProductMinSellingPrice').value = product.min_selling_price;
            }

            // Show the modal
            console.log('Showing edit modal...');
            document.getElementById('editProductModal').style.display = 'block';
        }

        function closeEditProductModal() {
            console.log('closeEditProductModal called');
            const modal = document.getElementById('editProductModal');
            console.log('Modal element:', modal);
            if (modal) {
                modal.style.display = 'none';
                console.log('Modal hidden');
            } else {
                console.log('Modal not found!');
            }
        }

        // Toggle Product Status (Activate/Deactivate)
        async function toggleProductStatus(productId, activate) {
            const product = allInventoryProducts.find(p => p.id === productId);
            if (!product) {
                alert('Product not found');
                return;
            }

            const action = activate ? 'activate' : 'deactivate';
            const confirmMessage = `Are you sure you want to ${action} "${product.brand} ${product.model}" (${product.code})?`;
            if (!confirm(confirmMessage)) {
                return;
            }

            try {
                const response = await fetch('api/products.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: productId,
                        is_active: activate ? 1 : 0
                    })
                });

                console.log('Toggle status response:', response.status);

                if (!response.ok) {
                    let errorText = '';
                    try {
                        errorText = await response.text();
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch {
                            alert('Server error: ' + response.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + response.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Error: Invalid JSON response from server.');
                    return;
                }

                if (result.success) {
                    alert(`Product ${activate ? 'activated' : 'deactivated'} successfully!`);

                    // Update local data
                    const productIndex = allInventoryProducts.findIndex(p => p.id === productId);
                    if (productIndex !== -1) {
                        allInventoryProducts[productIndex].is_active = activate;
                    }

                    // Refresh inventory display
                    loadInventory();
                    loadProducts(); // Refresh products for POS system
                } else {
                    alert('Failed to ' + action + ' product: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error toggling product status:', error);
                alert('Error: ' + error.message);
            }
        }

        // Receipt Details Functions
        async function viewReceiptDetails(saleId) {
            try {
                const response = await fetch(`api/sales.php?id=${saleId}`);
                const result = await response.json();

                if (result.success) {
                    const sale = result.data;

                    // Populate receipt information
                    document.getElementById('detailReceiptNumber').textContent = sale.receipt_number;
                    document.getElementById('detailDate').textContent = new Date(sale.sale_date).toLocaleDateString();
                    document.getElementById('detailStaff').textContent = sale.staff_name;
                    document.getElementById('detailCustomer').textContent = sale.customer_name || 'Walk-in Customer';
                    document.getElementById('detailPaymentMethod').textContent = sale.payment_method || 'Cash';
                    document.getElementById('detailTotalAmount').textContent = sale.total_amount.toFixed(2) + ' EGP';

                    // Populate payment summary  
                    document.getElementById('detailFinalTotal').textContent = sale.total_amount.toFixed(2) + ' EGP';

                    // Populate items table
                    const itemsTable = document.getElementById('receiptItemsTable');
                    itemsTable.innerHTML = sale.items.map(item => `
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 12px;">
                                <strong>${item.product_brand} ${item.product_model}</strong><br>
                                <small style="color: #666;">${item.product_code}</small>
                            </td>
                            <td style="padding: 12px; text-align: center;">${item.quantity}</td>
                            <td style="padding: 12px; text-align: right;">${item.unit_price.toFixed(2)} EGP </td>
                            <td style="padding: 12px; text-align: right; font-weight: bold;">${item.total_price.toFixed(2)} EGP </td>
                        </tr>
                    `).join('');

                    // Store sale data for printing
                    window.currentReceiptData = sale;

                    // Show the modal
                    document.getElementById('receiptDetailsModal').style.display = 'block';
                } else {
                    alert('Failed to load receipt details: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading receipt details:', error);
                alert('Error loading receipt details. Please try again.');
            }
        }

        function closeReceiptDetailsModal() {
            document.getElementById('receiptDetailsModal').style.display = 'none';
        }

        function printReceiptFromModal() {
            if (window.currentReceiptData) {
                printReceipt(window.currentReceiptData.id);
            }
        }

        async function printReceipt(saleId) {
            try {
                // Get receipt data if not already loaded
                let receiptData = window.currentReceiptData;
                if (!receiptData || receiptData.id !== saleId) {
                    const response = await fetch(`api/sales.php?id=${saleId}`);
                    const result = await response.json();
                    if (result.success) {
                        receiptData = result.data;
                    } else {
                        alert('Failed to load receipt data for printing');
                        return;
                    }
                }

                // Create printable receipt
                const printWindow = window.open('', '_blank');
                const printContent = generatePrintableReceipt(receiptData);

                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
                printWindow.close();

            } catch (error) {
                console.error('Error printing receipt:', error);
                alert('Error printing receipt. Please try again.');
            }
        }

        function generatePrintableReceipt(receiptData) {
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Receipt - ${receiptData.receipt_number}</title>
                    <style>
                        body { font-family: 'Courier New', monospace; width: 300px; margin: 0 auto; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                        .company-name { font-size: 18px; font-weight: bold; }
                        .receipt-info { margin-bottom: 15px; }
                        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                        .items-table th, .items-table td { text-align: left; padding: 5px 0; }
                        .items-table th { border-bottom: 1px solid #000; }
                        .total-section { border-top: 2px solid #000; padding-top: 10px; }
                        .total-line { display: flex; justify-content: space-between; margin-bottom: 5px; }
                        .final-total { font-weight: bold; font-size: 16px; border-top: 1px solid #000; padding-top: 5px; }
                        .footer { text-align: center; margin-top: 20px; border-top: 1px solid #000; padding-top: 10px; }
                        @media print { body { width: auto; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">IBS MOBILE SHOP</div>
                        <div>Mobile & Electronics Store</div>
                    </div>
                    
                    <div class="receipt-info">
                        <div><strong>Receipt #:</strong> ${receiptData.receipt_number}</div>
                        <div><strong>Date:</strong> ${new Date(receiptData.sale_date).toLocaleString()}</div>
                        <div><strong>Staff:</strong> ${receiptData.staff_name}</div>
                        <div><strong>Customer:</strong> ${receiptData.customer_name || 'Walk-in Customer'}</div>
                    </div>
                    
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${receiptData.items.map(item => `
                                <tr>
                                    <td>${item.product_brand} ${item.product_model}</td>
                                    <td>${item.quantity}</td>
                                    <td>${item.unit_price.toFixed(2)} EGP </td>
                                    <td>${item.total_price.toFixed(2)} EGP </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div class="total-section">
                        <div class="total-line final-total">
                            <span>TOTAL:</span>
                            <span>${receiptData.total_amount.toFixed(2)} EGP </span>
                        </div>
                    </div>
                    
                    <div class="footer">
                        <div>Thank you for your business!</div>
                        <div>Visit us again soon</div>
                    </div>
                </body>
                </html>
            `;
        }

        async function loadReports() {
            // Load report data and update the reports tab
            try {
                const [salesResponse, productsResponse] = await Promise.all([
                    fetch('api/sales.php'),
                    fetch('api/products.php')
                ]);

                const salesResult = await salesResponse.json();
                const productsResult = await productsResponse.json();

                if (salesResult.success && productsResult.success) {
                    const sales = salesResult.data;
                    const products = productsResult.data;

                    const today = new Date().toDateString();
                    const todaySales = sales.filter(s => new Date(s.sale_date).toDateString() === today);
                    const thisMonth = new Date().getMonth();
                    const monthSales = sales.filter(s => new Date(s.sale_date).getMonth() === thisMonth);

                    // Calculate profit instead of just sales totals
                    const todayProfit = await calculateProfit(todaySales);
                    const monthProfit = await calculateProfit(monthSales);
                    const lowStock = products.filter(p => p.min_stock !== null && p.stock <= p.min_stock).length;

                    document.getElementById('today-sales').textContent = ` ${todayProfit.toFixed(0)} EGP`;
                    document.getElementById('month-sales').textContent = ` ${monthProfit.toFixed(0)} EGP`;
                    document.getElementById('total-products').textContent = products.length;
                    document.getElementById('low-stock').textContent = lowStock;
                }
            } catch (error) {
                console.error('Error loading reports:', error);
            }
        }

        // Print Report Functions
        async function printTodaysSalesReport() {
            try {
                const response = await fetch('api/sales.php');
                const result = await response.json();

                if (result.success) {
                    const today = new Date().toDateString();
                    const todaySales = result.data.filter(s => new Date(s.sale_date).toDateString() === today);

                    const printContent = generateSalesReportHTML(todaySales, "Today's Sales Report", new Date().toLocaleDateString());
                    openPrintWindow(printContent, "Today's Sales Report");
                }
            } catch (error) {
                console.error('Error generating today\'s sales report:', error);
                alert('Error generating report. Please try again.');
            }
        }

        async function printMonthSalesReport() {
            try {
                const response = await fetch('api/sales.php');
                const result = await response.json();

                if (result.success) {
                    const thisMonth = new Date().getMonth();
                    const thisYear = new Date().getFullYear();
                    const monthSales = result.data.filter(s => {
                        const saleDate = new Date(s.sale_date);
                        return saleDate.getMonth() === thisMonth && saleDate.getFullYear() === thisYear;
                    });

                    const monthName = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                    const printContent = generateSalesReportHTML(monthSales, `${monthName} Sales Report`, monthName);
                    openPrintWindow(printContent, "Monthly Sales Report");
                }
            } catch (error) {
                console.error('Error generating monthly sales report:', error);
                alert('Error generating report. Please try again.');
            }
        }

        async function printProductsReport() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();

                if (result.success) {
                    const printContent = generateProductsReportHTML(result.data);
                    openPrintWindow(printContent, "Products Inventory Report");
                }
            } catch (error) {
                console.error('Error generating products report:', error);
                alert('Error generating report. Please try again.');
            }
        }

        async function printLowStockReport() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();

                if (result.success) {
                    const lowStockItems = result.data.filter(p => p.stock <= p.min_stock);
                    const printContent = generateLowStockReportHTML(lowStockItems);
                    openPrintWindow(printContent, "Low Stock Alert Report");
                }
            } catch (error) {
                console.error('Error generating low stock report:', error);
                alert('Error generating report. Please try again.');
            }
        }

        function generateSalesReportHTML(sales, title, period) {
            const totalAmount = sales.reduce((sum, sale) => sum + sale.total_amount, 0);
            const totalTransactions = sales.length;

            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0056b3; padding-bottom: 20px; }
                        .company-name { font-size: 24px; font-weight: bold; color: #0056b3; margin-bottom: 5px; }
                        .report-title { font-size: 20px; color: #333; margin-bottom: 5px; }
                        .report-period { font-size: 16px; color: #666; }
                        .summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
                        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
                        .summary-item { text-align: center; }
                        .summary-value { font-size: 24px; font-weight: bold; color: #0056b3; }
                        .summary-label { color: #666; margin-top: 5px; }
                        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        .table th { background: #0056b3; color: white; font-weight: bold; }
                        .table tr:nth-child(even) { background: #f8f9fa; }
                        .total-row { font-weight: bold; background: #e3f2fd !important; }
                        .footer { margin-top: 40px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                        @media print { body { margin: 0; } .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">IBS MOBILE SHOP</div>
                        <div class="report-title">${title}</div>
                        <div class="report-period">Period: ${period}</div>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="summary-value">${totalTransactions}</div>
                                <div class="summary-label">Total Transactions</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">$${totalAmount.toFixed(2)}</div>
                                <div class="summary-label">Total Sales Amount</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">$${totalTransactions > 0 ? (totalAmount / totalTransactions).toFixed(2) : '0.00'}</div>
                                <div class="summary-label">Average Transaction</div>
                            </div>
                        </div>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date & Time</th>
                                <th>Staff</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sales.map(sale => `
                                <tr>
                                    <td>${sale.receipt_number}</td>
                                    <td>${new Date(sale.sale_date).toLocaleString()}</td>
                                    <td>${sale.staff_name}</td>
                                    <td>${sale.customer_name || 'Walk-in Customer'}</td>
                                    <td>${sale.items ? sale.items.length : 0}</td>
                                    <td>$${sale.total_amount.toFixed(2)}</td>
                                </tr>
                            `).join('')}
                            <tr class="total-row">
                                <td colspan="5"><strong>TOTAL</strong></td>
                                <td><strong>$${totalAmount.toFixed(2)}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <div>Report generated on ${new Date().toLocaleString()}</div>
                        <div>IBS Mobile Shop - Sales Management System</div>
                    </div>
                </body>
                </html>
            `;
        }

        function generateProductsReportHTML(products) {
            const totalValue = products.reduce((sum, product) => sum + ((product.purchase_price || product.price || 0) * product.stock), 0);
            const totalItems = products.reduce((sum, product) => sum + product.stock, 0);

            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Products Inventory Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #4facfe; padding-bottom: 20px; }
                        .company-name { font-size: 24px; font-weight: bold; color: #4facfe; margin-bottom: 5px; }
                        .report-title { font-size: 20px; color: #333; margin-bottom: 5px; }
                        .summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
                        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
                        .summary-item { text-align: center; }
                        .summary-value { font-size: 24px; font-weight: bold; color: #4facfe; }
                        .summary-label { color: #666; margin-top: 5px; }
                        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .table th, .table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
                        .table th { background: #4facfe; color: white; font-weight: bold; }
                        .table tr:nth-child(even) { background: #f8f9fa; }
                        .low-stock { background: #ffebee !important; color: #c62828; }
                        .footer { margin-top: 40px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">IBS MOBILE SHOP</div>
                        <div class="report-title">Complete Products Inventory Report</div>
                        <div class="report-period">Generated on ${new Date().toLocaleDateString()}</div>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="summary-value">${products.length}</div>
                                <div class="summary-label">Total Products</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">${totalItems}</div>
                                <div class="summary-label">Total Items in Stock</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">$${totalValue.toFixed(2)}</div>
                                <div class="summary-label">Total Inventory Value</div>
                            </div>
                        </div>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Min Stock</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${products.map(product => `
                                <tr ${product.min_stock !== null && product.stock <= product.min_stock ? 'class="low-stock"' : ''}>
                                    <td>${product.code}</td>
                                    <td>${product.brand}</td>
                                    <td>${product.model}</td>
                                    <td>$${product.price.toFixed(2)}</td>
                                    <td>${product.stock}</td>
                                    <td>${product.min_stock || 'N/A'}</td>
                                    <td>$${((product.purchase_price || product.price || 0) * product.stock).toFixed(2)}</td>
                                    <td>${product.min_stock !== null && product.stock <= product.min_stock ? '⚠️ Low Stock' : '✅ In Stock'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <div>Report generated on ${new Date().toLocaleString()}</div>
                        <div>IBS Mobile Shop - Inventory Management System</div>
                    </div>
                </body>
                </html>
            `;
        }

        function generateLowStockReportHTML(lowStockItems) {
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Low Stock Alert Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #fa709a; padding-bottom: 20px; }
                        .company-name { font-size: 24px; font-weight: bold; color: #fa709a; margin-bottom: 5px; }
                        .report-title { font-size: 20px; color: #333; margin-bottom: 5px; }
                        .alert-badge { background: #ffebee; color: #c62828; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: inline-block; margin-top: 10px; }
                        .summary { background: #fff3e0; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid #ff9800; }
                        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        .table th { background: #fa709a; color: white; font-weight: bold; }
                        .table tr:nth-child(even) { background: #ffebee; }
                        .urgent { background: #ffcdd2 !important; font-weight: bold; }
                        .footer { margin-top: 40px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">IBS MOBILE SHOP</div>
                        <div class="report-title">⚠️ Low Stock Alert Report</div>
                        <div class="alert-badge">${lowStockItems.length} Items Require Attention</div>
                    </div>
                    
                    <div class="summary">
                        <h3 style="margin-top: 0; color: #e65100;">⚠️ URGENT: Items Below Minimum Stock Level</h3>
                        <p>The following items are at or below their minimum stock levels and require immediate restocking to avoid stockouts.</p>
                        <p><strong>Total Items Requiring Attention: ${lowStockItems.length}</strong></p>
                    </div>
                    
                    ${lowStockItems.length > 0 ? `
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Code</th>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>Min Stock</th>
                                    <th>Shortage</th>
                                    <th>Unit Price</th>
                                    <th>Reorder Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${lowStockItems.map(product => {
                const shortage = Math.max(0, product.min_stock - product.stock);
                const reorderQty = product.min_stock + 10; // Suggest reordering to min + buffer
                const reorderValue = reorderQty * product.price;
                const isUrgent = product.stock === 0;

                return `
                                        <tr ${isUrgent ? 'class="urgent"' : ''}>
                                            <td>${isUrgent ? '🔴 OUT OF STOCK' : '🟡 LOW STOCK'}</td>
                                            <td>${product.code}</td>
                                            <td>${product.brand} ${product.model}</td>
                                            <td>${product.stock}</td>
                                            <td>${product.min_stock}</td>
                                            <td>${shortage}</td>
                                            <td>$${product.price.toFixed(2)}</td>
                                            <td>$${reorderValue.toFixed(2)} (${reorderQty} units)</td>
                                        </tr>
                                    `;
            }).join('')}
                            </tbody>
                        </table>
                    ` : `
                        <div style="text-align: center; padding: 40px; background: #e8f5e8; border-radius: 8px; color: #2e7d32;">
                            <h3>✅ All Products Are Adequately Stocked</h3>
                            <p>No items are currently below their minimum stock levels.</p>
                        </div>
                    `}
                    
                    <div class="footer">
                        <div>Report generated on ${new Date().toLocaleString()}</div>
                        <div>IBS Mobile Shop - Stock Management System</div>
                        <div style="margin-top: 10px; color: #e65100; font-weight: bold;">⚠️ Please review and restock items marked as urgent</div>
                    </div>
                </body>
                </html>
            `;
        }

        function openPrintWindow(content, title) {
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.write(content);
            printWindow.document.close();
            printWindow.focus();

            // Auto-print after a short delay to ensure content is loaded
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        // Print Product Label Function
        function printProductLabel(productId) {
            const product = allInventoryProducts.find(p => p.id === productId);
            if (!product) {
                alert('Product not found');
                return;
            }

            const labelContent = generateProductLabelHTML(product);
            const printWindow = window.open('', '_blank', 'width=400,height=300');
            printWindow.document.write(labelContent);
            printWindow.document.close();
            printWindow.focus();

            // Auto-print after a short delay
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        function generateProductLabelHTML(product) {
            const barcode = product.barcode || 'No Barcode';
            const barcodeImage = product.barcode ? 
                `<img src="https://barcode.tec-it.com/barcode.ashx?data=${product.barcode}&code=Code128&multiplebarcodes=false&translate-esc=false&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff&qunit=Mm&quiet=0" 
                     alt="Barcode" style="width: 200px; height: 60px; margin: 8px 0;" />` :
                `<div style="font-family: 'Courier New', monospace; font-size: 18px; font-weight: bold; margin: 8px 0; letter-spacing: 2px;">${product.code}</div>`;
            
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Product Label - ${product.code}</title>
                    <style>
                        @page {
                            size: 4in 3in;
                            margin: 0.1in;
                        }

                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 15px;
                            width: 3.8in;
                            height: 2.8in;
                            border: 2px solid #000;
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            text-align: center;
                        }

                        .company-name {
                            font-size: 16px;
                            font-weight: bold;
                            color: #333;
                            margin-bottom: 10px;
                            border-bottom: 1px solid #ccc;
                            padding-bottom: 6px;
                            width: 100%;
                        }

                        .product-code {
                            font-size: 20px;
                            font-weight: bold;
                            color: #000;
                            margin-bottom: 6px;
                            letter-spacing: 1px;
                        }

                        .product-name {
                            font-size: 18px;
                            font-weight: bold;
                            color: #333;
                            margin-bottom: 6px;
                            line-height: 1.2;
                        }

                        .price {
                            font-size: 16px;
                            font-weight: bold;
                            color: #28a745;
                            margin-bottom: 8px;
                        }

                        .barcode-container {
                            margin: 10px 0;
                            padding: 8px;
                            border: 1px dashed #ccc;
                            background: #f9f9f9;
                            border-radius: 4px;
                        }

                        .barcode-text {
                            font-family: 'Courier New', monospace;
                            font-size: 14px;
                            font-weight: bold;
                            color: #000;
                            margin-top: 4px;
                            letter-spacing: 1px;
                        }

                        .no-barcode {
                            color: #999;
                            font-style: italic;
                        }

                        @media print {
                            body {
                                width: auto;
                                height: auto;
                            }
                            
                            .barcode-container {
                                background: white;
                                border-color: #000;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="company-name">IBS MOBILE SHOP</div>

                    <div class="product-code">${product.code}</div>

                    <div class="product-name">${product.brand} ${product.model}</div>

                    <div class="price">${product.suggested_price || product.price || 0} EGP</div>

                    <div class="barcode-container">
                        ${barcodeImage}
                        <div class="barcode-text ${!product.barcode ? 'no-barcode' : ''}">${barcode}</div>
                    </div>
                </body>
                </html>
            `;
        }

        // Income Management Functions
        let allIncomeEntries = [];

        async function loadIncome() {
            try {
                const response = await fetch('api/income.php');
                const result = await response.json();
                if (result.success) {
                    allIncomeEntries = result.data;
                    displayIncome(result.data);
                    displayIncomeStats(result.data);
                }
            } catch (error) {
                console.error('Error loading income entries:', error);
            }
        }

        function displayIncome(incomeEntries) {
            const tbody = document.getElementById('income-tbody');
            tbody.innerHTML = incomeEntries.map(entry => `
                <tr>
                    <td>${new Date(entry.entry_date).toLocaleDateString()}</td>
                    <td>${entry.price.toFixed(2)} EGP</td>
                    <td>${entry.description}</td>
                    <td>${entry.created_by_name}</td>
                    <td>
                        <button class="btn btn-sm" onclick="editIncomeEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px; margin-right: 5px;">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteIncomeEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px;">
                            🗑️ Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function displayIncomeStats(incomeEntries) {
            const totalIncome = incomeEntries.reduce((sum, entry) => sum + entry.price, 0);
            const thisMonthIncome = incomeEntries.filter(entry => {
                const entryDate = new Date(entry.entry_date);
                const now = new Date();
                return entryDate.getMonth() === now.getMonth() && entryDate.getFullYear() === now.getFullYear();
            }).reduce((sum, entry) => sum + entry.price, 0);

            document.getElementById('income-stats').innerHTML = `
                <div class="stat-card">
                    <h3>${incomeEntries.length}</h3>
                    <p>Total Entries</p>
                </div>
                <div class="stat-card">
                    <h3>${totalIncome.toFixed(0)} EGP</h3>
                    <p>Total Income</p>
                </div>
                <div class="stat-card">
                    <h3>${thisMonthIncome.toFixed(0)} EGP</h3>
                    <p>This Month</p>
                </div>
            `;
        }

        async function deleteIncomeEntry(entryId) {
            if (!confirm('Are you sure you want to delete this income entry?')) {
                return;
            }

            try {
                const response = await fetch(`api/income.php?id=${entryId}`, {
                    method: 'DELETE'
                });

                console.log('Delete income response status:', response.status);

                if (!response.ok) {
                    let errorText = '';
                    try {
                        errorText = await response.text();
                        // Try to parse as JSON
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch {
                            alert('Server error: ' + response.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + response.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await response.text();
                console.log('Raw delete income response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', responseText);
                    alert('Error: Invalid JSON response from server. Check console for details.');
                    return;
                }

                if (result.success) {
                    alert('Income entry deleted successfully!');
                    loadIncome(); // Refresh the list
                    loadReports(); // Refresh profit calculations
                } else {
                    alert('Failed to delete income entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error deleting income entry:', error);
                alert('Error deleting income entry: ' + error.message);
            }
        }

        function editIncomeEntry(entryId) {
            const entry = allIncomeEntries.find(e => e.id === entryId);
            if (!entry) {
                alert('Income entry not found');
                return;
            }

            // For now, use a simple prompt. In a real application, you'd want a modal
            const newPrice = prompt('Enter new price:', entry.price);
            const newDescription = prompt('Enter new description:', entry.description);

            if (newPrice === null || newDescription === null) {
                return; // User cancelled
            }

            const price = parseFloat(newPrice);
            const description = newDescription.trim();

            if (isNaN(price) || price <= 0) {
                alert('Please enter a valid price greater than 0');
                return;
            }

            if (!description) {
                alert('Description cannot be empty');
                return;
            }

            // Update the entry
            updateIncomeEntry(entryId, price, description);
        }

        async function updateIncomeEntry(entryId, price, description) {
            try {
                const response = await fetch('api/income.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: entryId,
                        price: price,
                        description: description
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Income entry updated successfully!');
                    loadIncome(); // Refresh the list
                } else {
                    alert('Failed to update income entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating income entry:', error);
                alert('Error updating income entry. Please try again.');
            }
        }

        // Payment Management Functions
        let allPaymentEntries = [];

        async function loadPayment() {
            try {
                const response = await fetch('api/payment.php');
                const result = await response.json();
                if (result.success) {
                    allPaymentEntries = result.data;
                    displayPayment(result.data);
                    displayPaymentStats(result.data);
                }
            } catch (error) {
                console.error('Error loading payment entries:', error);
            }
        }

        function displayPayment(paymentEntries) {
            const tbody = document.getElementById('payment-tbody');
            tbody.innerHTML = paymentEntries.map(entry => `
                <tr>
                    <td>${new Date(entry.entry_date).toLocaleDateString()}</td>
                    <td>${entry.price.toFixed(2)} EGP</td>
                    <td>${entry.description}</td>
                    <td>${entry.created_by_name}</td>
                    <td>
                        <button class="btn btn-sm" onclick="editPaymentEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px; margin-right: 5px;">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deletePaymentEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px;">
                            🗑️ Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function displayPaymentStats(paymentEntries) {
            const totalPayments = paymentEntries.reduce((sum, entry) => sum + entry.price, 0);
            const thisMonthPayments = paymentEntries.filter(entry => {
                const entryDate = new Date(entry.entry_date);
                const now = new Date();
                return entryDate.getMonth() === now.getMonth() && entryDate.getFullYear() === now.getFullYear();
            }).reduce((sum, entry) => sum + entry.price, 0);

            document.getElementById('payment-stats').innerHTML = `
                <div class="stat-card">
                    <h3>${paymentEntries.length}</h3>
                    <p>Total Entries</p>
                </div>
                <div class="stat-card">
                    <h3>${totalPayments.toFixed(0)} EGP</h3>
                    <p>Total Payments</p>
                </div>
                <div class="stat-card">
                    <h3>${thisMonthPayments.toFixed(0)} EGP</h3>
                    <p>This Month</p>
                </div>
            `;
        }

        async function deletePaymentEntry(entryId) {
            if (!confirm('Are you sure you want to delete this payment entry?')) {
                return;
            }

            try {
                const response = await fetch(`api/payment.php?id=${entryId}`, {
                    method: 'DELETE'
                });

                console.log('Delete payment response status:', response.status);

                if (!response.ok) {
                    let errorText = '';
                    try {
                        errorText = await response.text();
                        // Try to parse as JSON
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch {
                            alert('Server error: ' + response.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + response.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await response.text();
                console.log('Raw delete payment response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', responseText);
                    alert('Error: Invalid JSON response from server. Check console for details.');
                    return;
                }

                if (result.success) {
                    alert('Payment entry deleted successfully!');
                    loadPayment(); // Refresh the list
                    loadReports(); // Refresh profit calculations
                } else {
                    alert('Failed to delete payment entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error deleting payment entry:', error);
                alert('Error deleting payment entry: ' + error.message);
            }
        }

        function editPaymentEntry(entryId) {
            const entry = allPaymentEntries.find(e => e.id === entryId);
            if (!entry) {
                alert('Payment entry not found');
                return;
            }

            // For now, use a simple prompt. In a real application, you'd want a modal
            const newPrice = prompt('Enter new amount:', entry.price);
            const newDescription = prompt('Enter new description:', entry.description);

            if (newPrice === null || newDescription === null) {
                return; // User cancelled
            }

            const price = parseFloat(newPrice);
            const description = newDescription.trim();

            if (isNaN(price) || price <= 0) {
                alert('Please enter a valid amount greater than 0');
                return;
            }

            if (!description) {
                alert('Description cannot be empty');
                return;
            }

            // Update the entry
            updatePaymentEntry(entryId, price, description);
        }

        async function updatePaymentEntry(entryId, price, description) {
            try {
                const response = await fetch('api/payment.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: entryId,
                        price: price,
                        description: description
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Payment entry updated successfully!');
                    loadPayment(); // Refresh the list
                    loadReports(); // Refresh profit calculations
                } else {
                    alert('Failed to update payment entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating payment entry:', error);
                alert('Error updating payment entry. Please try again.');
            }
        }

        // IMEI validation and form handling
        document.addEventListener('DOMContentLoaded', function() {
            // IMEI field validation
            const imeiField = document.getElementById('imei-field');
            const hasImeiSelect = document.getElementById('has-imei-select');
            
            if (hasImeiSelect && imeiField) {
                hasImeiSelect.addEventListener('change', function() {
                    if (this.value === '1') {
                        imeiField.required = true;
                        imeiField.placeholder = 'IMEI is required (15 digits)';
                    } else {
                        imeiField.required = false;
                        imeiField.placeholder = 'Enter IMEI number (for mobile devices)';
                        imeiField.value = '';
                    }
                });
                
                // View stock items for a product
        async function viewStockItems(productId) {
            console.log('Viewing stock items for product:', productId);
            try {
                const response = await fetch(`api/stock_items.php?product_id=${productId}`);
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('API result:', result);
                
                if (result.success) {
                    displayStockItemsModal(result.data);
                } else {
                    alert('Failed to load stock items: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading stock items:', error);
                alert('Error loading stock items: ' + error.message);
            }
        }
        
        function displayStockItemsModal(stockItems) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: white;
                padding: 20px;
                border-radius: 10px;
                max-width: 800px;
                max-height: 80vh;
                overflow-y: auto;
                width: 90%;
            `;
            
            const availableItems = stockItems.filter(item => item.status === 'available');
            const soldItems = stockItems.filter(item => item.status === 'sold');
            const reservedItems = stockItems.filter(item => item.status === 'reserved');
            
            modalContent.innerHTML = `
                <h2>Stock Items Management</h2>
                <div style="margin-bottom: 20px;">
                    <span style="background: #4CAF50; color: white; padding: 5px 10px; border-radius: 5px; margin-right: 10px;">Available: ${availableItems.length}</span>
                    <span style="background: #2196F3; color: white; padding: 5px 10px; border-radius: 5px; margin-right: 10px;">Sold: ${soldItems.length}</span>
                    <span style="background: #FF9800; color: white; padding: 5px 10px; border-radius: 5px; margin-right: 10px;">Reserved: ${reservedItems.length}</span>
                    <span style="background: #9E9E9E; color: white; padding: 5px 10px; border-radius: 5px;">Total: ${stockItems.length}</span>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Serial Number</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">IMEI</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Status</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Added Date</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${stockItems.map(item => `
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd;">${item.serial_number}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${item.imei || 'N/A'}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <span style="
                                        background: ${item.status === 'available' ? '#4CAF50' : item.status === 'sold' ? '#2196F3' : item.status === 'reserved' ? '#FF9800' : '#F44336'};
                                        color: white;
                                        padding: 3px 8px;
                                        border-radius: 3px;
                                        font-size: 12px;
                                    ">${item.status.toUpperCase()}</span>
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${new Date(item.created_at).toLocaleDateString()}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    ${item.status === 'available' ? `
                                        <button onclick="markAsSold(${item.id})" style="background: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 3px; margin-right: 5px; cursor: pointer;">Mark Sold</button>
                                        <button onclick="deleteStockItem(${item.id})" style="background: #F44336; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Delete</button>
                                    ` : '-'}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <div style="margin-top: 20px; text-align: right;">
                    <button onclick="closeStockItemsModal()" style="background: #666; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Close</button>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        async function markAsSold(stockItemId) {
            if (!confirm('Mark this item as sold?')) return;
            
            try {
                const response = await fetch('api/stock_items.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: stockItemId,
                        status: 'sold'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Item marked as sold successfully');
                    location.reload(); // Reload to update the inventory
                } else {
                    alert('Failed to mark item as sold: ' + result.message);
                }
            } catch (error) {
                console.error('Error marking item as sold:', error);
                alert('Error marking item as sold');
            }
        }
        
        async function deleteStockItem(stockItemId) {
            if (!confirm('Delete this stock item? This action cannot be undone.')) return;
            
            try {
                const response = await fetch(`api/stock_items.php?id=${stockItemId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Stock item deleted successfully');
                    location.reload(); // Reload to update the inventory
                } else {
                    alert('Failed to delete stock item: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting stock item:', error);
                alert('Error deleting stock item');
            }
        }
        
        function closeStockItemsModal() {
            // Find and remove the stock items modal
            const modals = document.querySelectorAll('div[style*="position: fixed"]');
            modals.forEach(modal => {
                if (modal.style.background && modal.style.background.includes('rgba(0,0,0,0.5)')) {
                    modal.remove();
                }
            });
        }
        
        // IMEI format validation
                imeiField.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, ''); // Remove non-digits
                    if (value.length > 15) {
                        value = value.substring(0, 15);
                    }
                    this.value = value;
                });
            }
            
            // Form submission handling
            const addProductForm = document.querySelector('form[method="POST"]');
            if (addProductForm) {
                addProductForm.addEventListener('submit', function(e) {
                    const hasImei = document.getElementById('has-imei-select').value;
                    const imeiField = document.getElementById('imei-field');
                    
                    if (hasImei === '1' && (!imeiField.value || imeiField.value.length !== 15)) {
                        e.preventDefault();
                        alert('IMEI is required and must be 15 digits when "Has IMEI" is set to Yes');
                        imeiField.focus();
                        return false;
                    }
                });
            }
        });

        // Barcode Generation Function
        async function generateBarcode(productId) {
            if (!confirm('Generate barcode for this product?')) return;
            
            try {
                const response = await fetch('api/barcode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Barcode generated successfully: ' + result.barcode);
                    loadInventory(); // Refresh inventory to show the new barcode
                } else {
                    alert('Failed to generate barcode: ' + result.message);
                }
            } catch (error) {
                console.error('Error generating barcode:', error);
                alert('Error generating barcode');
            }
        }

        // Barcode Scanner Function
        function startBarcodeScanner() {
            const input = document.createElement('input');
            input.style.position = 'fixed';
            input.style.top = '0';
            input.style.left = '0';
            input.style.width = '100%';
            input.style.height = '100%';
            input.style.zIndex = '10000';
            input.style.background = 'rgba(0,0,0,0.8)';
            input.style.color = 'white';
            input.style.fontSize = '24px';
            input.style.textAlign = 'center';
            input.style.padding = '20px';
            input.placeholder = 'Scan barcode or type barcode number...';
            
            document.body.appendChild(input);
            input.focus();
            
            input.addEventListener('keypress', async function(e) {
                if (e.key === 'Enter') {
                    const barcode = input.value.trim();
                    document.body.removeChild(input);
                    
                    if (barcode) {
                        await searchProductByBarcode(barcode);
                    }
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.body.removeChild(input);
                }
            });
        }

        async function searchProductByBarcode(barcode) {
            try {
                const response = await fetch(`api/barcode.php?barcode=${encodeURIComponent(barcode)}`);
                const result = await response.json();
                
                if (result.success) {
                    const product = result.data;
                    alert(`Product Found!\n\nCode: ${product.code}\nProduct: ${product.brand} ${product.model}\nStock: ${product.stock}\nPrice: ${product.suggested_price} EGP`);
                    
                    // Add to receipt if in sales tab
                    if (document.getElementById('receipt').classList.contains('active')) {
                        addToReceiptByBarcode(product);
                    }
                } else {
                    alert('Product not found for barcode: ' + barcode);
                }
            } catch (error) {
                console.error('Error searching by barcode:', error);
                alert('Error searching for product');
            }
        }

        function addToReceiptByBarcode(product) {
            // Add product to receipt
            const existingItem = currentReceipt.items.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                currentReceipt.items.push({
                    id: product.id,
                    code: product.code,
                    name: `${product.brand} ${product.model}`,
                    price: product.suggested_price,
                    quantity: 1
                });
            }
            
            updateReceiptDisplay();
        }

        // Barcode generation function (for PHP form submission)
        function generateEAN13Barcode(productCode) {
            // This function is for reference - actual generation happens in PHP
            // Extract numeric part from product code
            const numeric = productCode.replace(/[^0-9]/g, '');
            
            // Pad to 12 digits (EAN-13 without checksum)
            let padded = numeric.padStart(12, '0').substring(0, 12);
            
            // Calculate checksum
            let sum = 0;
            for (let i = 0; i < 12; i++) {
                const digit = parseInt(padded[i]);
                sum += (i % 2 === 0) ? digit : digit * 3;
            }
            const checksum = (10 - (sum % 10)) % 10;
            
            return padded + checksum;
        }

    </script>
    
    <script>
        // Language toggle function using the new translation system
        function toggleLanguage() {
            if (typeof langManager !== 'undefined') {
                langManager.toggleLanguage();
            }
        }
        
        // Handle logout function
        function handleLogout(event) {
            console.log('Logout clicked');
            event.preventDefault();
            
            // Show confirmation
            if (confirm('Are you sure you want to logout?')) {
                console.log('Proceeding with logout');
                window.location.href = '?logout=1';
            } else {
                console.log('Logout cancelled');
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