<?php
session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: index.php');
    exit;
}

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="navigation.dashboard">Staff Dashboard - IBS Mobile Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/translations.js"></script>
</head>

<body id="body-lang">
    <!-- Language Toggle Button -->
    <button class="language-toggle" id="languageToggle" onclick="toggleLanguage()" title="Toggle Language" style="position: fixed; top: 20px; right: 20px; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 12px; padding: 10px 16px; display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500; color: rgba(255, 255, 255, 0.95); z-index: 9999; min-width: 60px; justify-content: center;">
        <i class="fas fa-language"></i>
        <span class="lang-text">EN</span>
    </button>
    
    <div class="header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="assets/css/logo.jpeg" alt="IBS Store Logo" style="width: 40px; height: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" />
            <h1 data-translate="navigation.dashboard">📱 IBS Staff Dashboard</h1>
        </div>
        <div class="user-info">
            <span data-translate="navigation.welcome">Welcome</span>, <?php echo $_SESSION['name']; ?>
            <a href="?logout=1" class="logout-btn" data-translate="navigation.logout">Logout</a>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="showTab('receipt')" data-translate="sales.receipt">🧾 Receipt</button>
        <button class="nav-tab" onclick="showTab('inventory')" data-translate="navigation.inventory">📦 Inventory</button>
    </div>

    <div class="content">
        <!-- Receipt Tab -->
        <div id="receipt" class="tab-content active">
            <div class="section">
                <h2>🧾 Create Receipt</h2>
                <div class="receipt-builder">
                    <div>
                        <h3>Customer Information</h3>
                        <div class="form-group">
                            <label>Customer Name:</label>
                            <input type="text" id="customer-name" placeholder="Enter customer name">
                        </div>
                        <div class="form-group">
                            <label>Customer Phone:</label>
                            <input type="text" id="customer-phone" placeholder="Enter phone number">
                        </div>

                        <h3>Add Products</h3>
                        <div class="form-group">
                            <label>Search Product:</label>
                            <input type="text" id="product-search"
                                placeholder="🔍 Search by product code, brand, or model..."
                                style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;"
                                onkeyup="searchProducts(this.value)"
                                onkeypress="if(event.key==='Enter') selectFirstProduct()">
                            <div id="product-search-results"
                                style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-top: none; display: none; background: white; position: relative; z-index: 100;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Selected Product:</label>
                            <div id="selected-product"
                                style="padding: 10px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; min-height: 40px; color: #666;">
                                No product selected
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Selling Price: <span style="color: red;">*</span></label>
                            <input type="number" id="selling-price" step="0.01" min="0.01"
                                placeholder="Enter selling price"
                                style="width: 70%; max-width: 70%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                            <small style="color: #666; font-size: 12px;">Must be at least the minimum selling
                                price</small>
                        </div>
                        <div class="form-group">
                            <label>Quantity:</label>
                            <input type="number" id="quantity" min="1" value="1">
                        </div>
                        <button class="btn" onclick="addToReceipt()" id="add-product-btn" disabled>Add to
                            Receipt</button>
                    </div>

                    <div>
                        <h3>Receipt Items</h3>
                        <div class="receipt-items" id="receipt-items">
                            <div class="no-data">No items added yet</div>
                        </div>

                        <div class="receipt-totals">
                            <div class="total-final">Total: <span id="total">0.00 EGP</span></div>
                        </div>

                        <button class="btn btn-success" onclick="completeReceipt()" id="complete-btn" disabled>Complete
                            Sale</button>
                        <button class="btn btn-danger" onclick="clearReceipt()">Clear Receipt</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Tab -->
        <div id="inventory" class="tab-content">
            <div class="section">
                <h2>📦 Inventory View</h2>
                <div id="inventory-stats" class="stats-grid"></div>

                <!-- Search Bar -->
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <div style="flex: 1; max-width: 400px;">
                        <input type="text" id="inventorySearchInput" placeholder="🔍 Search by Code, Brand, Model..."
                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;"
                            onkeyup="filterInventory(this.value)"
                            onkeypress="if(event.key==='Enter') filterInventory(this.value)">
                    </div>
                    <button onclick="clearInventorySearch()" class="btn btn-secondary"
                        style="padding: 12px 20px;">Clear</button>
                </div>

                <!-- Search Results Count -->
                <div id="inventory-search-results-count" style="margin-bottom: 15px; color: #666; font-size: 14px;">
                    Showing all products
                </div>

                <table id="inventory-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Product</th>
                            <th>Min Price</th>
                            <th>Suggested Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-tbody">
                        <tr>
                            <td colspan="6" class="no-data">Loading inventory...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentReceipt = { items: [], total: 0 };
        let products = [];
        let selectedProduct = null;
        let allProducts = [];
        let allInventoryData = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            loadProducts();
            loadInventory();
        });

        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Load products
        async function loadProducts() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();

                if (result.success) {
                    products = result.data;
                    allProducts = result.data.filter(p => p.stock > 0);
                    populateProductSelect();
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Populate product select (keeping for compatibility)
        function populateProductSelect() {
            // This function is kept for compatibility but not used with search
        }

        // Product search functions
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
                     style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;"
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

        // Add to receipt
        function addToReceipt() {
            const quantityInput = document.getElementById('quantity');
            const sellingPriceInput = document.getElementById('selling-price');

            if (!selectedProduct) {
                alert('Please select a product');
                return;
            }

            const quantity = parseInt(quantityInput.value);
            const sellingPrice = parseFloat(sellingPriceInput.value);

            if (!quantity || quantity <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            if (!sellingPrice || sellingPrice <= 0) {
                alert('Please enter a valid selling price');
                return;
            }

            if (sellingPrice < selectedProduct.min_selling_price) {
                alert(`Selling price cannot be less than minimum price: ${selectedProduct.min_selling_price.toFixed(2)} EGP `);
                return;
            }

            if (quantity > selectedProduct.stock) {
                alert(`Only ${selectedProduct.stock} items available in stock`);
                return;
            }

            // Check if item exists
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

        // Update receipt display
        function updateReceiptDisplay() {
            const itemsDiv = document.getElementById('receipt-items');

            if (currentReceipt.items.length === 0) {
                itemsDiv.innerHTML = '<div class="no-data">No items added yet</div>';
            } else {
                itemsDiv.innerHTML = currentReceipt.items.map(item => `
                    <div class="receipt-item">
                        <div>
                            <strong>${item.name}</strong><br>
                            <small>Code: ${item.code}</small>
                        </div>
                        <div style="text-align: right;">
                            <div>${item.quantity} × ${item.price.toFixed(2)} EGP </div>
                            <div><strong>${item.total.toFixed(2)} EGP </strong></div>
                        </div>
                        <button class="btn btn-danger" onclick="removeFromReceipt(${item.productId})" style="padding: 5px 10px;">×</button>
                    </div>
                `).join('');
            }

            // Calculate totals
            currentReceipt.total = currentReceipt.items.reduce((sum, item) => sum + item.total, 0);

            // Update display
            document.getElementById('total').textContent = `$${currentReceipt.total.toFixed(2)}`;

            // Enable/disable complete button
            document.getElementById('complete-btn').disabled = currentReceipt.items.length === 0;
        }

        // Remove from receipt
        function removeFromReceipt(productId) {
            currentReceipt.items = currentReceipt.items.filter(item => item.productId !== productId);
            updateReceiptDisplay();
        }

        // Clear receipt
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

        // Complete receipt
        async function completeReceipt() {
            const customerName = document.getElementById('customer-name').value.trim();

            if (!customerName) {
                alert('Please enter customer name');
                return;
            }

            // Check if user is logged in
            const staffId = <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>;
            if (!staffId) {
                alert('You are not logged in. Please refresh the page and login again.');
                return;
            }

            if (currentReceipt.items.length === 0) {
                alert('Please add items to receipt');
                return;
            }

            const completeBtn = document.getElementById('complete-btn');
            completeBtn.innerHTML = 'Processing...';
            completeBtn.disabled = true;

            try {
                // Try to add customer, but don't fail if it doesn't work
                let customerId = null;
                const customerPhone = document.getElementById('customer-phone').value.trim();

                if (customerPhone) {
                    try {
                        const customerResponse = await fetch('api/customers.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ name: customerName, phone: customerPhone })
                        });

                        const customerResult = await customerResponse.json();
                        if (customerResult.success) {
                            customerId = customerResult.customer_id;
                        }
                    } catch (customerError) {
                        console.warn('Customer creation failed, continuing without customer ID:', customerError);
                        // Continue with sale even if customer creation fails
                    }
                }

                // Create sale
                const saleData = {
                    customer_id: customerId,
                    staff_id: <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>,
                    subtotal: currentReceipt.total,
                    tax_amount: 0,
                    total_amount: currentReceipt.total,
                    payment_method: 'cash',
                    staff_name: <?php echo json_encode(isset($_SESSION['name']) ? $_SESSION['name'] : 'Staff'); ?>,
                    staff_username: <?php echo json_encode(isset($_SESSION['username']) ? $_SESSION['username'] : 'staff'); ?>,
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
                    printReceipt({
                        receipt_number: saleResult.receipt_number,
                        customer: customerName,
                        phone: customerPhone,
                        items: currentReceipt.items,
                        total: currentReceipt.total,
                        date: new Date(),
                        staff: <?php echo json_encode(isset($_SESSION['name']) ? $_SESSION['name'] : 'Staff'); ?>
                    });

                    alert('Sale completed successfully! Receipt #' + saleResult.receipt_number);
                    clearReceipt();
                    loadProducts(); // Refresh products to update stock
                    loadInventory(); // Refresh inventory
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

        // Print receipt
        function printReceipt(sale) {
            const receiptWindow = window.open('', '_blank', 'width=400,height=600');
            receiptWindow.document.write(`
                <html>
                <head>
                    <title>Receipt - ${sale.receipt_number}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .receipt-info { margin: 15px 0; }
                        .items { margin: 20px 0; }
                        .item { display: flex; justify-content: space-between; margin: 5px 0; }
                        .totals { border-top: 2px solid #000; padding-top: 10px; margin-top: 20px; }
                        .total-line { display: flex; justify-content: space-between; margin: 5px 0; }
                        .final-total { font-weight: bold; font-size: 1.2em; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>IBS Mobile Shop</h2>
                        <p>Receipt #${sale.receipt_number}</p>
                    </div>
                    
                    <div class="receipt-info">
                        <p><strong>Date:</strong> ${sale.date.toLocaleDateString()}</p>
                        <p><strong>Staff:</strong> ${sale.staff}</p>
                        <p><strong>Customer:</strong> ${sale.customer}</p>
                        ${sale.phone ? `<p><strong>Phone:</strong> ${sale.phone}</p>` : ''}
                    </div>
                    
                    <div class="items">
                        <h3>Items:</h3>
                        ${sale.items.map(item => `
                            <div class="item">
                                <span>${item.name} (${item.code})</span>
                                <span>${item.quantity} x ${item.price.toFixed(2)} EGP = ${item.total.toFixed(2)} EGP </span>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="totals">
                        <div class="total-line final-total">
                            <span>Total:</span>
                            <span>${sale.total.toFixed(2)} EGP </span>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p>Thank you for your business!</p>
                    </div>
                </body>
                </html>
            `);
            receiptWindow.document.close();
            receiptWindow.print();
        }

        // Load inventory
        async function loadInventory() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();

                if (result.success) {
                    allInventoryData = result.data; // Store all inventory data for filtering
                    displayInventory(result.data);
                    displayInventoryStats(result.data);

                    // Initialize search results count
                    const resultsCountDiv = document.getElementById('inventory-search-results-count');
                    if (resultsCountDiv) {
                        resultsCountDiv.textContent = `Showing all ${result.data.length} products`;
                        resultsCountDiv.style.color = '#666';
                    }
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
            }
        }

        // Display inventory
        function displayInventory(products) {
            const tbody = document.getElementById('inventory-tbody');

            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-data">No products found</td></tr>';
                return;
            }

            tbody.innerHTML = products.map(product => `
                <tr>
                    <td>${product.code}</td>
                    <td>${product.brand} ${product.model}</td>
                    <td>${(product.min_selling_price || 0).toFixed(2)} EGP </td>
                    <td>${(product.suggested_price || product.price || 0).toFixed(2)} EGP </td>
                    <td>${product.stock}</td>
                    <td>
                        <span class="${product.stock <= product.min_stock ? 'stock-low' : 'stock-ok'}">
                            ${product.stock <= product.min_stock ? '⚠️ Low Stock' : '✅ In Stock'}
                        </span>
                    </td>
                </tr>
            `).join('');
        }

        // Display inventory stats
        function displayInventoryStats(products) {
            const totalProducts = products.length;
            const lowStock = products.filter(p => p.stock <= p.min_stock).length;
            const outOfStock = products.filter(p => p.stock === 0).length;
            const totalValue = products.reduce((sum, p) => sum + (p.price * p.stock), 0);

            document.getElementById('inventory-stats').innerHTML = `
                <div class="stat-card">
                    <h3>${totalProducts}</h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-card">
                    <h3>${lowStock}</h3>
                    <p>Low Stock Items</p>
                </div>
                <div class="stat-card">
                    <h3>${outOfStock}</h3>
                    <p>Out of Stock</p>
                </div>
                <div class="stat-card">
                    <h3> 0 EGP </h3>
                    <p>Inventory Value</p>
                </div>
            `;
        }

        // Inventory search functions
        function filterInventory(searchTerm) {
            const resultsCountDiv = document.getElementById('inventory-search-results-count');

            if (!searchTerm.trim()) {
                // If search is empty, show all products
                displayInventory(allInventoryData);
                displayInventoryStats(allInventoryData);
                resultsCountDiv.textContent = `Showing all ${allInventoryData.length} products`;
                resultsCountDiv.style.color = '#666';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredProducts = allInventoryData.filter(product => {
                return product.code.toLowerCase().includes(searchLower) ||
                    product.brand.toLowerCase().includes(searchLower) ||
                    product.model.toLowerCase().includes(searchLower) ||
                    (product.description && product.description.toLowerCase().includes(searchLower));
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

        function clearInventorySearch() {
            const searchInput = document.getElementById('inventorySearchInput');
            const resultsCountDiv = document.getElementById('inventory-search-results-count');

            searchInput.value = '';
            displayInventory(allInventoryData);
            displayInventoryStats(allInventoryData);
            resultsCountDiv.textContent = `Showing all ${allInventoryData.length} products`;
            resultsCountDiv.style.color = '#666';
        }
    </script>
    
    <script>
        // Language toggle function using the translation system
        function toggleLanguage() {
            langManager.toggleLanguage();
        }
        
        // Apply initial language when page loads
        document.addEventListener('DOMContentLoaded', function() {
            langManager.init();
        });
    </script>
</body>

</html>