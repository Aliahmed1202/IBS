// Inventory Controller - Manages product inventory operations
class InventoryController {
    constructor() {
        this.currentProducts = [];
        this.filteredProducts = [];
        this.searchQuery = '';
        this.selectedBrand = '';
        this.sortBy = 'name';
        this.sortOrder = 'asc';
        this.init();
    }

    // Initialize inventory controller
    init() {
        this.bindEvents();
    }

    // Bind inventory events
    bindEvents() {
        // Add product button
        document.getElementById('add-product-btn')?.addEventListener('click', () => {
            this.showProductModal();
        });

        // Product form submission
        document.getElementById('product-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleProductSubmit();
        });

        // Modal close events
        document.getElementById('close-product-modal')?.addEventListener('click', () => {
            this.hideProductModal();
        });

        document.getElementById('cancel-product')?.addEventListener('click', () => {
            this.hideProductModal();
        });

        // Search functionality
        document.getElementById('search-products')?.addEventListener('input', 
            utils.debounce((e) => {
                this.searchQuery = e.target.value;
                this.filterProducts();
            }, 300)
        );

        // Brand filter
        document.getElementById('filter-brand')?.addEventListener('change', (e) => {
            this.selectedBrand = e.target.value;
            this.filterProducts();
        });

        // Table sorting
        document.addEventListener('click', (e) => {
            if (e.target.closest('th[data-sort]')) {
                const sortField = e.target.closest('th').dataset.sort;
                this.handleSort(sortField);
            }
        });

        // Product actions (edit, delete)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-edit-product')) {
                const productId = parseInt(e.target.closest('.btn-edit-product').dataset.productId);
                this.editProduct(productId);
            }
            
            if (e.target.closest('.btn-delete-product')) {
                const productId = parseInt(e.target.closest('.btn-delete-product').dataset.productId);
                this.deleteProduct(productId);
            }
        });

        // Modal click outside to close
        document.getElementById('product-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'product-modal') {
                this.hideProductModal();
            }
        });
    }

    // Load inventory data
    loadInventory() {
        try {
            this.currentProducts = Product.getAll();
            this.updateBrandFilter();
            this.filterProducts();
            this.updateInventoryStats();
        } catch (error) {
            console.error('Error loading inventory:', error);
            this.showError('Failed to load inventory data');
        }
    }

    // Update brand filter dropdown
    updateBrandFilter() {
        const brandFilter = document.getElementById('filter-brand');
        if (!brandFilter) return;

        const brands = Product.getBrands();
        
        brandFilter.innerHTML = '<option value="">All Brands</option>';
        brands.forEach(brand => {
            brandFilter.innerHTML += `<option value="${brand}">${brand}</option>`;
        });
        
        brandFilter.value = this.selectedBrand;
    }

    // Filter products based on search and brand
    filterProducts() {
        let filtered = [...this.currentProducts];

        // Apply search filter
        if (this.searchQuery) {
            filtered = Product.search(this.searchQuery);
        }

        // Apply brand filter
        if (this.selectedBrand) {
            filtered = filtered.filter(product => product.brand === this.selectedBrand);
        }

        // Apply sorting
        filtered = this.sortProducts(filtered);

        this.filteredProducts = filtered;
        this.renderProductsTable();
    }

    // Sort products
    sortProducts(products) {
        return products.sort((a, b) => {
            let aValue, bValue;

            switch (this.sortBy) {
                case 'name':
                    aValue = a.getFullName().toLowerCase();
                    bValue = b.getFullName().toLowerCase();
                    break;
                case 'brand':
                    aValue = a.brand.toLowerCase();
                    bValue = b.brand.toLowerCase();
                    break;
                case 'price':
                    aValue = a.price;
                    bValue = b.price;
                    break;
                case 'stock':
                    aValue = a.stock;
                    bValue = b.stock;
                    break;
                default:
                    return 0;
            }

            if (aValue < bValue) return this.sortOrder === 'asc' ? -1 : 1;
            if (aValue > bValue) return this.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });
    }

    // Handle table sorting
    handleSort(field) {
        if (this.sortBy === field) {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortBy = field;
            this.sortOrder = 'asc';
        }

        this.filterProducts();
        this.updateSortIndicators();
    }

    // Update sort indicators in table headers
    updateSortIndicators() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
            if (th.dataset.sort === this.sortBy) {
                th.classList.add(`sort-${this.sortOrder}`);
            }
        });
    }

    // Render products table
    renderProductsTable() {
        const tbody = document.getElementById('products-tbody');
        if (!tbody) return;

        if (this.filteredProducts.length === 0) {
            tbody.innerHTML = `
                <tr class="no-data-row">
                    <td colspan="7">No products found</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredProducts.map(product => `
            <tr>
                <td>
                    <img src="${product.image || 'https://via.placeholder.com/50x50?text=No+Image'}" 
                         alt="${product.getFullName()}" 
                         class="product-image"
                         onerror="this.src='https://via.placeholder.com/50x50?text=No+Image'">
                </td>
                <td>${product.brand}</td>
                <td>${product.model}</td>
                <td>${product.getFormattedPrice()}</td>
                <td>${product.stock}</td>
                <td>
                    <span class="status-badge status-${product.getStockStatus()}">
                        ${product.getStockStatusText()}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-secondary btn-sm btn-edit-product" 
                                data-product-id="${product.id}" title="Edit Product">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete-product" 
                                data-product-id="${product.id}" title="Delete Product">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // Update inventory statistics
    updateInventoryStats() {
        const totalProducts = this.currentProducts.length;
        const lowStockProducts = Product.getLowStock().length;
        const outOfStockProducts = this.currentProducts.filter(p => p.stock === 0).length;
        const totalValue = this.currentProducts.reduce((sum, p) => sum + (p.price * p.stock), 0);

        // Update dashboard if visible
        if (window.app?.getCurrentSection() === 'dashboard') {
            document.getElementById('total-products').textContent = totalProducts;
            document.getElementById('low-stock').textContent = lowStockProducts;
        }

        // Show inventory summary in inventory section
        this.showInventorySummary({
            totalProducts,
            lowStockProducts,
            outOfStockProducts,
            totalValue
        });
    }

    // Show inventory summary
    showInventorySummary(stats) {
        const summaryContainer = document.querySelector('#inventory .section-header');
        if (!summaryContainer) return;

        // Remove existing summary
        const existingSummary = summaryContainer.querySelector('.inventory-summary');
        if (existingSummary) {
            existingSummary.remove();
        }

        // Create new summary
        const summaryDiv = document.createElement('div');
        summaryDiv.className = 'inventory-summary';
        summaryDiv.innerHTML = `
            <div class="summary-stats">
                <span class="stat">Total: ${stats.totalProducts}</span>
                <span class="stat warning">Low Stock: ${stats.lowStockProducts}</span>
                <span class="stat danger">Out of Stock: ${stats.outOfStockProducts}</span>
                <span class="stat">Value: ${utils.formatCurrency(stats.totalValue)}</span>
            </div>
        `;

        summaryContainer.appendChild(summaryDiv);
    }

    // Show product modal
    showProductModal(product = null) {
        const modal = document.getElementById('product-modal');
        const title = document.getElementById('product-modal-title');
        const form = document.getElementById('product-form');

        if (product) {
            title.textContent = 'Edit Product';
            this.fillProductForm(product);
            form.dataset.productId = product.id;
        } else {
            title.textContent = 'Add New Product';
            form.reset();
            delete form.dataset.productId;
        }

        modal.style.display = 'block';
        document.getElementById('product-brand').focus();
    }

    // Hide product modal
    hideProductModal() {
        const modal = document.getElementById('product-modal');
        modal.style.display = 'none';
        document.getElementById('product-form').reset();
    }

    // Fill product form with data
    fillProductForm(product) {
        document.getElementById('product-brand').value = product.brand;
        document.getElementById('product-model').value = product.model;
        document.getElementById('product-price').value = product.price;
        document.getElementById('product-stock').value = product.stock;
        document.getElementById('product-image').value = product.image || '';
        document.getElementById('product-description').value = product.description || '';
    }

    // Handle product form submission
    handleProductSubmit() {
        const form = document.getElementById('product-form');
        const formData = new FormData(form);
        
        const productData = {
            brand: formData.get('product-brand')?.trim(),
            model: formData.get('product-model')?.trim(),
            price: parseFloat(formData.get('product-price')),
            stock: parseInt(formData.get('product-stock')),
            image: formData.get('product-image')?.trim(),
            description: formData.get('product-description')?.trim()
        };

        // Validate data
        if (!this.validateProductData(productData)) {
            return;
        }

        try {
            if (form.dataset.productId) {
                // Update existing product
                const productId = parseInt(form.dataset.productId);
                Product.update(productId, productData);
                this.showSuccess('Product updated successfully');
            } else {
                // Create new product
                Product.create(productData);
                this.showSuccess('Product added successfully');
            }

            this.hideProductModal();
            this.loadInventory();
            
            // Trigger data update event
            this.triggerDataUpdate();
            
        } catch (error) {
            console.error('Error saving product:', error);
            this.showError('Failed to save product');
        }
    }

    // Validate product data
    validateProductData(data) {
        const errors = [];

        if (!data.brand) errors.push('Brand is required');
        if (!data.model) errors.push('Model is required');
        if (!data.price || data.price <= 0) errors.push('Valid price is required');
        if (!data.stock || data.stock < 0) errors.push('Valid stock quantity is required');

        if (errors.length > 0) {
            this.showError(errors.join(', '));
            return false;
        }

        return true;
    }

    // Edit product
    editProduct(productId) {
        const product = Product.findById(productId);
        if (product) {
            this.showProductModal(product);
        } else {
            this.showError('Product not found');
        }
    }

    // Delete product
    deleteProduct(productId) {
        const product = Product.findById(productId);
        if (!product) {
            this.showError('Product not found');
            return;
        }

        if (confirm(`Are you sure you want to delete ${product.getFullName()}?`)) {
            try {
                Product.delete(productId);
                this.showSuccess('Product deleted successfully');
                this.loadInventory();
                this.triggerDataUpdate();
            } catch (error) {
                console.error('Error deleting product:', error);
                this.showError('Failed to delete product');
            }
        }
    }

    // Bulk operations
    bulkUpdateStock(updates) {
        try {
            updates.forEach(update => {
                Product.updateStock(update.productId, update.quantity);
            });
            
            this.showSuccess(`Updated stock for ${updates.length} products`);
            this.loadInventory();
            this.triggerDataUpdate();
        } catch (error) {
            console.error('Error updating stock:', error);
            this.showError('Failed to update stock');
        }
    }

    // Export inventory data
    exportInventory() {
        const data = {
            products: this.currentProducts,
            exportDate: new Date().toISOString(),
            exportedBy: window.app?.getCurrentUser()?.getDisplayName() || 'Unknown'
        };

        const csv = this.convertToCSV(this.currentProducts);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `inventory-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Convert products to CSV
    convertToCSV(products) {
        const headers = ['ID', 'Brand', 'Model', 'Price', 'Stock', 'Status', 'Description'];
        const rows = products.map(product => [
            product.id,
            product.brand,
            product.model,
            product.price,
            product.stock,
            product.getStockStatusText(),
            product.description || ''
        ]);

        return [headers, ...rows]
            .map(row => row.map(field => `"${field}"`).join(','))
            .join('\n');
    }

    // Trigger data update event
    triggerDataUpdate() {
        document.dispatchEvent(new CustomEvent('dataUpdated', {
            detail: { type: 'inventory' }
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
