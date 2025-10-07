// Sales Controller - Manages sales transactions and operations
class SalesController {
    constructor() {
        this.currentSales = [];
        this.filteredSales = [];
        this.currentSale = null;
        this.init();
    }

    // Initialize sales controller
    init() {
        this.bindEvents();
    }

    // Bind sales events
    bindEvents() {
        // New sale button
        document.getElementById('new-sale-btn')?.addEventListener('click', () => {
            this.showSaleModal();
        });

        // Sale form submission
        document.getElementById('sale-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSaleSubmit();
        });

        // Modal close events
        document.getElementById('close-sale-modal')?.addEventListener('click', () => {
            this.hideSaleModal();
        });

        document.getElementById('cancel-sale')?.addEventListener('click', () => {
            this.hideSaleModal();
        });

        // Product selection change
        document.getElementById('sale-product')?.addEventListener('change', (e) => {
            this.handleProductSelection(e.target.value);
        });

        // Quantity change
        document.getElementById('sale-quantity')?.addEventListener('input', (e) => {
            this.calculateTotal();
        });

        // Sale actions (view, delete)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-view-sale')) {
                const saleId = parseInt(e.target.closest('.btn-view-sale').dataset.saleId);
                this.viewSale(saleId);
            }
            
            if (e.target.closest('.btn-delete-sale')) {
                const saleId = parseInt(e.target.closest('.btn-delete-sale').dataset.saleId);
                this.deleteSale(saleId);
            }
        });

        // Modal click outside to close
        document.getElementById('sale-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'sale-modal') {
                this.hideSaleModal();
            }
        });
    }

    // Load sales data
    loadSales() {
        try {
            this.currentSales = Sale.getAll().sort((a, b) => 
                new Date(b.saleDate) - new Date(a.saleDate)
            );
            this.filteredSales = [...this.currentSales];
            this.renderSalesTable();
            this.updateSalesSummary();
            this.populateCustomerDropdown();
            this.populateProductDropdown();
        } catch (error) {
            console.error('Error loading sales:', error);
            this.showError('Failed to load sales data');
        }
    }

    // Render sales table
    renderSalesTable() {
        const tbody = document.getElementById('sales-tbody');
        if (!tbody) return;

        if (this.filteredSales.length === 0) {
            tbody.innerHTML = `
                <tr class="no-data-row">
                    <td colspan="7">No sales recorded</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredSales.map(sale => {
            const customer = Customer.findById(sale.customerId);
            const product = Product.findById(sale.productId);
            
            return `
                <tr>
                    <td>${sale.getSaleIdString()}</td>
                    <td>${sale.getFormattedDate()}</td>
                    <td>${customer?.name || 'Unknown Customer'}</td>
                    <td>${product?.getFullName() || 'Unknown Product'}</td>
                    <td>${sale.quantity}</td>
                    <td>${sale.getFormattedTotal()}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-secondary btn-sm btn-view-sale" 
                                    data-sale-id="${sale.id}" title="View Sale">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-danger btn-sm btn-delete-sale" 
                                    data-sale-id="${sale.id}" title="Delete Sale">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Update sales summary
    updateSalesSummary() {
        const stats = Sale.getStatistics();
        
        document.getElementById('today-sales').textContent = utils.formatCurrency(stats.today.amount);
        document.getElementById('month-sales').textContent = utils.formatCurrency(stats.thisMonth.amount);
        document.getElementById('all-sales').textContent = utils.formatCurrency(stats.total.amount);
    }

    // Populate customer dropdown
    populateCustomerDropdown() {
        const customerSelect = document.getElementById('sale-customer');
        if (!customerSelect) return;

        const customers = Customer.getAll().sort((a, b) => a.name.localeCompare(b.name));
        
        customerSelect.innerHTML = '<option value="">Select Customer</option>';
        customers.forEach(customer => {
            customerSelect.innerHTML += `
                <option value="${customer.id}">${customer.name} (${customer.phone})</option>
            `;
        });
    }

    // Populate product dropdown
    populateProductDropdown() {
        const productSelect = document.getElementById('sale-product');
        if (!productSelect) return;

        const products = Product.getAll()
            .filter(product => product.stock > 0)
            .sort((a, b) => a.getFullName().localeCompare(b.getFullName()));
        
        productSelect.innerHTML = '<option value="">Select Product</option>';
        products.forEach(product => {
            productSelect.innerHTML += `
                <option value="${product.id}" data-price="${product.price}" data-stock="${product.stock}">
                    ${product.getFullName()} - ${product.getFormattedPrice()} (Stock: ${product.stock})
                </option>
            `;
        });
    }

    // Handle product selection
    handleProductSelection(productId) {
        const productSelect = document.getElementById('sale-product');
        const quantityInput = document.getElementById('sale-quantity');
        
        if (!productId) {
            quantityInput.max = '';
            quantityInput.value = '';
            this.calculateTotal();
            return;
        }

        const selectedOption = productSelect.querySelector(`option[value="${productId}"]`);
        if (selectedOption) {
            const maxStock = parseInt(selectedOption.dataset.stock);
            quantityInput.max = maxStock;
            quantityInput.value = 1;
            this.calculateTotal();
        }
    }

    // Calculate sale total
    calculateTotal() {
        const productSelect = document.getElementById('sale-product');
        const quantityInput = document.getElementById('sale-quantity');
        const totalInput = document.getElementById('sale-total');
        
        const selectedOption = productSelect.querySelector(`option[value="${productSelect.value}"]`);
        
        if (selectedOption && quantityInput.value) {
            const price = parseFloat(selectedOption.dataset.price);
            const quantity = parseInt(quantityInput.value);
            const total = price * quantity;
            
            totalInput.value = total.toFixed(2);
        } else {
            totalInput.value = '';
        }
    }

    // Show sale modal
    showSaleModal() {
        const modal = document.getElementById('sale-modal');
        const form = document.getElementById('sale-form');
        
        form.reset();
        this.populateCustomerDropdown();
        this.populateProductDropdown();
        
        modal.style.display = 'block';
        document.getElementById('sale-customer').focus();
    }

    // Hide sale modal
    hideSaleModal() {
        const modal = document.getElementById('sale-modal');
        modal.style.display = 'none';
        document.getElementById('sale-form').reset();
    }

    // Handle sale form submission
    handleSaleSubmit() {
        const form = document.getElementById('sale-form');
        const formData = new FormData(form);
        
        const saleData = {
            customerId: parseInt(formData.get('sale-customer')),
            productId: parseInt(formData.get('sale-product')),
            quantity: parseInt(formData.get('sale-quantity')),
            total: parseFloat(formData.get('sale-total')),
            staffId: window.app?.getCurrentUser()?.id
        };

        // Get product info for unit price
        const product = Product.findById(saleData.productId);
        if (product) {
            saleData.unitPrice = product.price;
        }

        // Validate data
        if (!this.validateSaleData(saleData)) {
            return;
        }

        try {
            Sale.create(saleData);
            this.showSuccess('Sale completed successfully');
            this.hideSaleModal();
            this.loadSales();
            this.triggerDataUpdate();
            
            // Show sale receipt option
            this.showReceiptOption(saleData);
            
        } catch (error) {
            console.error('Error creating sale:', error);
            this.showError('Failed to complete sale');
        }
    }

    // Validate sale data
    validateSaleData(data) {
        const errors = [];

        if (!data.customerId) errors.push('Customer is required');
        if (!data.productId) errors.push('Product is required');
        if (!data.quantity || data.quantity <= 0) errors.push('Valid quantity is required');
        if (!data.total || data.total <= 0) errors.push('Valid total is required');

        // Check product availability
        const product = Product.findById(data.productId);
        if (product && !product.isAvailable(data.quantity)) {
            errors.push(`Only ${product.stock} units available`);
        }

        if (errors.length > 0) {
            this.showError(errors.join(', '));
            return false;
        }

        return true;
    }

    // View sale details
    viewSale(saleId) {
        const sale = Sale.findById(saleId);
        if (!sale) {
            this.showError('Sale not found');
            return;
        }

        const customer = Customer.findById(sale.customerId);
        const product = Product.findById(sale.productId);
        const staff = User.findById(sale.staffId);

        const saleDetails = `
            <div class="sale-details">
                <h3>Sale Details - ${sale.getSaleIdString()}</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <strong>Date:</strong> ${sale.getFormattedDate()}
                    </div>
                    <div class="detail-item">
                        <strong>Customer:</strong> ${customer?.name || 'Unknown'}
                    </div>
                    <div class="detail-item">
                        <strong>Phone:</strong> ${customer?.phone || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Product:</strong> ${product?.getFullName() || 'Unknown'}
                    </div>
                    <div class="detail-item">
                        <strong>Unit Price:</strong> ${sale.getFormattedUnitPrice()}
                    </div>
                    <div class="detail-item">
                        <strong>Quantity:</strong> ${sale.quantity}
                    </div>
                    <div class="detail-item">
                        <strong>Total:</strong> ${sale.getFormattedTotal()}
                    </div>
                    <div class="detail-item">
                        <strong>Staff:</strong> ${staff?.getDisplayName() || 'Unknown'}
                    </div>
                </div>
                <div class="detail-actions">
                    <button class="btn btn-primary" onclick="salesController.printReceipt(${saleId})">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>
        `;

        this.showModal('Sale Details', saleDetails);
    }

    // Delete sale
    deleteSale(saleId) {
        const sale = Sale.findById(saleId);
        if (!sale) {
            this.showError('Sale not found');
            return;
        }

        if (confirm(`Are you sure you want to delete sale ${sale.getSaleIdString()}?`)) {
            try {
                Sale.delete(saleId);
                this.showSuccess('Sale deleted successfully');
                this.loadSales();
                this.triggerDataUpdate();
            } catch (error) {
                console.error('Error deleting sale:', error);
                this.showError('Failed to delete sale');
            }
        }
    }

    // Show receipt option
    showReceiptOption(saleData) {
        const customer = Customer.findById(saleData.customerId);
        const product = Product.findById(saleData.productId);
        
        const receiptOption = confirm(
            `Sale completed successfully!\n\nCustomer: ${customer?.name}\nProduct: ${product?.getFullName()}\nTotal: ${utils.formatCurrency(saleData.total)}\n\nWould you like to print a receipt?`
        );
        
        if (receiptOption) {
            this.printLastReceipt();
        }
    }

    // Print receipt
    printReceipt(saleId) {
        const sale = Sale.findById(saleId);
        if (!sale) {
            this.showError('Sale not found');
            return;
        }

        const customer = Customer.findById(sale.customerId);
        const product = Product.findById(sale.productId);
        const staff = User.findById(sale.staffId);

        const receiptContent = `
            <div class="receipt">
                <div class="receipt-header">
                    <h2>Mobile Shop Pro</h2>
                    <p>Sales Receipt</p>
                </div>
                
                <div class="receipt-info">
                    <p><strong>Receipt #:</strong> ${sale.getSaleIdString()}</p>
                    <p><strong>Date:</strong> ${sale.getFormattedDate()}</p>
                    <p><strong>Staff:</strong> ${staff?.getDisplayName() || 'Unknown'}</p>
                </div>
                
                <div class="receipt-customer">
                    <h4>Customer Information</h4>
                    <p><strong>Name:</strong> ${customer?.name || 'Unknown'}</p>
                    <p><strong>Phone:</strong> ${customer?.phone || 'N/A'}</p>
                </div>
                
                <div class="receipt-items">
                    <h4>Items Purchased</h4>
                    <table>
                        <tr>
                            <td>${product?.getFullName() || 'Unknown Product'}</td>
                            <td>${sale.quantity} x ${sale.getFormattedUnitPrice()}</td>
                            <td>${sale.getFormattedTotal()}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="receipt-total">
                    <h3>Total: ${sale.getFormattedTotal()}</h3>
                </div>
                
                <div class="receipt-footer">
                    <p>Thank you for your business!</p>
                    <p>Visit us again soon</p>
                </div>
            </div>
        `;

        this.printContent(receiptContent);
    }

    // Print last receipt
    printLastReceipt() {
        const lastSale = this.currentSales[0];
        if (lastSale) {
            this.printReceipt(lastSale.id);
        }
    }

    // Print content
    printContent(content) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Receipt</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .receipt { max-width: 400px; margin: 0 auto; }
                        .receipt-header { text-align: center; margin-bottom: 20px; }
                        .receipt-header h2 { margin: 0; }
                        .receipt-info, .receipt-customer, .receipt-items { margin-bottom: 15px; }
                        .receipt-items table { width: 100%; border-collapse: collapse; }
                        .receipt-items td { padding: 5px; border-bottom: 1px solid #ddd; }
                        .receipt-total { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 2px solid #333; }
                        .receipt-footer { text-align: center; margin-top: 20px; font-size: 0.9em; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    // Show modal
    showModal(title, content) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('custom-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'custom-modal';
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="custom-modal-title"></h3>
                        <span class="close" id="close-custom-modal">&times;</span>
                    </div>
                    <div class="modal-body" id="custom-modal-body"></div>
                </div>
            `;
            document.body.appendChild(modal);

            // Bind close event
            document.getElementById('close-custom-modal').addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        document.getElementById('custom-modal-title').textContent = title;
        document.getElementById('custom-modal-body').innerHTML = content;
        modal.style.display = 'block';
    }

    // Get sales report data
    getSalesReportData(period = 'month') {
        let sales;
        
        switch (period) {
            case 'today':
                sales = Sale.getTodaySales();
                break;
            case 'month':
                sales = Sale.getThisMonthSales();
                break;
            case 'year':
                sales = Sale.getThisYearSales();
                break;
            default:
                sales = Sale.getAll();
        }

        return {
            sales: sales,
            totalAmount: Sale.getTotalSalesAmount(sales),
            totalCount: sales.length,
            topProducts: Sale.getTopSellingProducts(5)
        };
    }

    // Trigger data update event
    triggerDataUpdate() {
        document.dispatchEvent(new CustomEvent('dataUpdated', {
            detail: { type: 'sales' }
        }));
    }

    // Show success message
    showSuccess(message) {
        window.app?.showMessage(message, 'success');
    }

    // Show error message
    showError(message) {
        window.app?.showMessage(message, 'error');
    }
}

// Make controller globally accessible for receipt printing
window.salesController = new SalesController();
