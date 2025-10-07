// Receipt Management System
// Handles all receipt creation and management functionality

const ReceiptController = {
    // Receipt Management (Staff Functions)
    showReceiptBuilder: function() {
        document.getElementById('receipt-builder').style.display = 'block';
        this.clearReceipt();
        
        // Scroll to receipt builder
        document.getElementById('receipt-builder').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
        
        // Add visual indicator
        const receiptBuilder = document.getElementById('receipt-builder');
        receiptBuilder.style.border = '2px solid #007bff';
        receiptBuilder.style.boxShadow = '0 0 10px rgba(0,123,255,0.3)';
    },

    cancelReceipt: function() {
        document.getElementById('receipt-builder').style.display = 'none';
        this.clearReceipt();
    },

    clearReceipt: function() {
        if (typeof AppData !== 'undefined') {
            AppData.currentReceipt = {
                items: [],
                customer: '',
                phone: '',
                customer_id: null,
                subtotal: 0,
                tax: 0,
                total: 0
            };
            this.updateReceiptDisplay();
        } else {
            console.error('AppData is not defined');
        }
    },

    addToReceipt: function(productId) {
        console.log('Adding product to receipt:', productId);
        
        if (typeof AppData === 'undefined') {
            console.error('AppData is not defined');
            alert('System error: AppData not available');
            return;
        }
        
        if (!AppData.products || AppData.products.length === 0) {
            console.error('No products available');
            alert('No products loaded. Please refresh the page.');
            return;
        }
        
        const product = AppData.products.find(p => p.id === productId);
        if (!product) {
            console.error('Product not found:', productId);
            alert('Product not found!');
            return;
        }

        if (product.stock <= 0) {
            alert('Product is out of stock!');
            return;
        }

        // Check if product already in receipt
        const existingItem = AppData.currentReceipt.items.find(item => item.productId === productId);
        
        if (existingItem) {
            if (existingItem.quantity < product.stock) {
                existingItem.quantity++;
                existingItem.total = existingItem.quantity * existingItem.price;
            } else {
                alert('Cannot add more items than available in stock!');
                return;
            }
        } else {
            AppData.currentReceipt.items.push({
                productId: productId,
                code: product.code,
                name: `${product.brand} ${product.model}`,
                price: product.price,
                quantity: 1,
                total: product.price
            });
        }

        this.updateReceiptDisplay();
        this.showReceiptBuilder(); // Show receipt builder if not already visible
        
        // Show success message with better UX
        const button = document.querySelector(`button[onclick="ReceiptController.addToReceipt(${productId})"]`);
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Added!';
            button.style.backgroundColor = '#28a745';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.backgroundColor = '';
            }, 1500);
        }
        
        // Show notification
        this.showNotification(`${product.brand} ${product.model} added to receipt!`, 'success');
    },

    removeFromReceipt: function(productId) {
        if (typeof AppData !== 'undefined') {
            AppData.currentReceipt.items = AppData.currentReceipt.items.filter(item => item.productId !== productId);
            this.updateReceiptDisplay();
        }
    },

    updateReceiptDisplay: function() {
        const itemsList = document.getElementById('receipt-items-list');
        
        if (!itemsList) {
            console.error('Receipt items list element not found');
            return;
        }
        
        if (!AppData.currentReceipt.items || AppData.currentReceipt.items.length === 0) {
            itemsList.innerHTML = '<p class="no-items">No items added yet. Go to Inventory to add products.</p>';
        } else {
            itemsList.innerHTML = AppData.currentReceipt.items.map(item => `
                <div class="receipt-item">
                    <div class="item-info">
                        <strong>${item.name}</strong>
                        <div class="item-code">Code: ${item.code}</div>
                    </div>
                    <div class="item-quantity">
                        <button class="qty-btn" onclick="ReceiptController.changeQuantity(${item.productId}, -1)">-</button>
                        <span class="qty-display">${item.quantity}</span>
                        <button class="qty-btn" onclick="ReceiptController.changeQuantity(${item.productId}, 1)">+</button>
                    </div>
                    <div class="item-price">$${item.price.toFixed(2)}</div>
                    <div class="item-total">$${item.total.toFixed(2)}</div>
                    <button class="btn-remove" onclick="ReceiptController.removeFromReceipt(${item.productId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }

        // Calculate totals
        const subtotal = AppData.currentReceipt.items.reduce((sum, item) => sum + item.total, 0);
        const tax = subtotal * 0.1;
        const total = subtotal + tax;

        // Update receipt totals
        AppData.currentReceipt.subtotal = subtotal;
        AppData.currentReceipt.tax = tax;
        AppData.currentReceipt.total = total;

        // Update display
        const subtotalEl = document.getElementById('receipt-subtotal');
        const taxEl = document.getElementById('receipt-tax');
        const totalEl = document.getElementById('receipt-total');
        
        if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
        if (taxEl) taxEl.textContent = `$${tax.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;

        // Enable/disable complete button
        const completeBtn = document.getElementById('complete-receipt-btn');
        if (completeBtn) {
            completeBtn.disabled = AppData.currentReceipt.items.length === 0;
        }
    },

    changeQuantity: function(productId, change) {
        const item = AppData.currentReceipt.items.find(item => item.productId === productId);
        const product = AppData.products.find(p => p.id === productId);
        
        if (!item || !product) return;

        const newQuantity = item.quantity + change;
        if (newQuantity <= 0) {
            this.removeFromReceipt(productId);
        } else if (newQuantity <= product.stock) {
            item.quantity = newQuantity;
            item.total = item.quantity * item.price;
            this.updateReceiptDisplay();
        } else {
            alert('Cannot exceed available stock!');
        }
    },

    async completeReceipt() {
        const customerName = document.getElementById('receipt-customer').value.trim();
        const customerPhone = document.getElementById('receipt-phone').value.trim();

        if (!customerName) {
            alert('Please enter customer name');
            return;
        }

        if (AppData.currentReceipt.items.length === 0) {
            alert('Please add items to the receipt');
            return;
        }

        try {
            // Show loading state
            const completeBtn = document.getElementById('complete-receipt-btn');
            const originalText = completeBtn.innerHTML;
            completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            completeBtn.disabled = true;

            // Add or find customer
            let customerId = null;
            if (customerPhone) {
                const customerResponse = await API.addCustomer({
                    name: customerName,
                    phone: customerPhone
                });
                
                if (customerResponse.success) {
                    customerId = customerResponse.customer_id;
                }
            }

            // Prepare sale data with staff information
            const saleData = {
                customer_id: customerId,
                staff_id: AppData.currentUser.id,
                subtotal: AppData.currentReceipt.subtotal,
                tax_amount: AppData.currentReceipt.tax,
                total_amount: AppData.currentReceipt.total,
                payment_method: 'cash',
                staff_name: AppData.currentUser.name,
                staff_username: AppData.currentUser.username,
                items: AppData.currentReceipt.items.map(item => ({
                    product_id: item.productId,
                    quantity: item.quantity,
                    unit_price: item.price,
                    total_price: item.total
                }))
            };

            console.log('💾 Saving sale to database:', {
                staff: AppData.currentUser.name,
                customer: customerName,
                total: AppData.currentReceipt.total,
                items: saleData.items.length
            });

            // Create sale
            const saleResponse = await API.createSale(saleData);
            
            if (saleResponse.success) {
                // Create receipt object for printing
                const newSale = {
                    id: saleResponse.sale_id,
                    receipt_number: saleResponse.receipt_number,
                    customer: customerName,
                    phone: customerPhone,
                    items: [...AppData.currentReceipt.items],
                    subtotal: AppData.currentReceipt.subtotal,
                    tax: AppData.currentReceipt.tax,
                    total: AppData.currentReceipt.total,
                    date: new Date(),
                    staff: AppData.currentUser.name
                };

                // Update local product stock
                AppData.currentReceipt.items.forEach(item => {
                    const product = AppData.products.find(p => p.id === item.productId);
                    if (product) {
                        product.stock -= item.quantity;
                    }
                });

                // Show success message and print receipt
                this.printReceipt(newSale);
                this.cancelReceipt();
                
                // Refresh data
                await AppController.loadInitialData();
                
                // Refresh inventory if currently viewing it
                if (document.getElementById('inventory').classList.contains('active')) {
                    AppController.loadInventory();
                }

                alert('Sale completed successfully!');
            } else {
                alert('Failed to complete sale: ' + saleResponse.message);
            }
        } catch (error) {
            console.error('Error completing sale:', error);
            alert('Error completing sale. Please try again.');
        } finally {
            // Restore button state
            const completeBtn = document.getElementById('complete-receipt-btn');
            if (completeBtn) {
                completeBtn.innerHTML = '<i class="fas fa-check"></i> Complete Sale';
                completeBtn.disabled = false;
            }
        }
    },

    printReceipt: function(sale) {
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
                    <h2>ATTIA Mobile Shop</h2>
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
                            <span>${item.quantity} x $${item.price.toFixed(2)} = $${item.total.toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
                
                <div class="totals">
                    <div class="total-line">
                        <span>Subtotal:</span>
                        <span>$${sale.subtotal.toFixed(2)}</span>
                    </div>
                    <div class="total-line">
                        <span>Tax (10%):</span>
                        <span>$${sale.tax.toFixed(2)}</span>
                    </div>
                    <div class="total-line final-total">
                        <span>Total:</span>
                        <span>$${sale.total.toFixed(2)}</span>
                    </div>
                </div>
                
                <div class="footer" style="text-align: center; margin-top: 20px;">
                    <p>Thank you for your business!</p>
                </div>
            </body>
            </html>
        `);
        receiptWindow.document.close();
        receiptWindow.print();
    },

    // Notification system
    showNotification: function(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(n => n.remove());

        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
};

// Make ReceiptController globally accessible
window.ReceiptController = ReceiptController;

// Extend AppController with receipt functions
if (typeof AppController !== 'undefined') {
    Object.assign(AppController, ReceiptController);
}
