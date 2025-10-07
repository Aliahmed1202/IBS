// MVC Architecture Implementation with Database Integration

// API Configuration
const API_BASE = 'api/';

// Simple Data Models (M in MVC)
const AppData = {
    products: [],
    customers: [],
    sales: [],
    dashboardStats: {},
    currentUser: null,
    currentReceipt: {
        items: [],
        customer: '',
        phone: '',
        customer_id: null,
        subtotal: 0,
        tax: 0,
        total: 0
    }
};

// Make AppData globally accessible
window.AppData = AppData;

// API Helper Functions
const API = {
    async request(endpoint, options = {}) {
        try {
            const response = await fetch(API_BASE + endpoint, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    },

    async login(username, password, role) {
        return await this.request('auth.php', {
            method: 'POST',
            body: JSON.stringify({ username, password, role })
        });
    },

    async getProducts() {
        return await this.request('products.php');
    },

    async getCustomers() {
        return await this.request('customers.php');
    },

    async getSales() {
        return await this.request('sales.php');
    },

    async getDashboardStats() {
        return await this.request('dashboard.php');
    },

    async createSale(saleData) {
        return await this.request('sales.php', {
            method: 'POST',
            body: JSON.stringify(saleData)
        });
    },

    async addCustomer(customerData) {
        return await this.request('customers.php', {
            method: 'POST',
            body: JSON.stringify(customerData)
        });
    }
};

// Simple Controller Functions (C in MVC)
const AppController = {
    // Authentication
    async login(username, password, role) {
        try {
            const response = await API.login(username, password, role);
            
            if (response.success) {
                AppData.currentUser = response.user;
                console.log('✅ Database login successful for:', AppData.currentUser.name);
                this.showApp();
                this.updateUserInfo();
                await this.loadInitialData();
                this.showSection('dashboard');
                return true;
            }
            return false;
        } catch (error) {
            console.error('❌ Database login failed:', error);
            return false;
        }
    },

    // Load initial data from database
    async loadInitialData() {
        console.log('📊 Loading data from database...');
        
        try {
            // Load products - REQUIRED for all users
            const productsResponse = await API.getProducts();
            if (productsResponse.success) {
                AppData.products = productsResponse.data;
                console.log(`✅ Loaded ${AppData.products.length} products from database`);
            } else {
                console.error('❌ Failed to load products from database');
                throw new Error('Products data is required');
            }

            // Load customers (admin only)
            if (AppData.currentUser.role === 'admin') {
                const customersResponse = await API.getCustomers();
                if (customersResponse.success) {
                    AppData.customers = customersResponse.data;
                    console.log(`✅ Loaded ${AppData.customers.length} customers from database`);
                } else {
                    console.error('❌ Failed to load customers from database');
                    AppData.customers = [];
                }

                const salesResponse = await API.getSales();
                if (salesResponse.success) {
                    AppData.sales = salesResponse.data;
                    console.log(`✅ Loaded ${AppData.sales.length} sales from database`);
                } else {
                    console.error('❌ Failed to load sales from database');
                    AppData.sales = [];
                }
            }

            // Load dashboard stats - REQUIRED
            const statsResponse = await API.getDashboardStats();
            if (statsResponse.success) {
                AppData.dashboardStats = statsResponse.data;
                console.log('✅ Loaded dashboard statistics from database');
            } else {
                console.error('❌ Failed to load dashboard stats from database');
                throw new Error('Dashboard stats are required');
            }
            
            console.log('🎉 All data loaded successfully from database');
        } catch (error) {
            console.error('💥 Critical error loading data from database:', error);
            alert('Failed to load data from database. Please check your database connection and try again.');
            this.logout();
        }
    },

    logout: function() {
        AppData.currentUser = null;
        this.hideApp();
    },

    // View Management
    showApp: function() {
        document.getElementById('login-container').style.display = 'none';
        document.getElementById('app-container').style.display = 'block';
    },

    hideApp: function() {
        document.getElementById('login-container').style.display = 'flex';
        document.getElementById('app-container').style.display = 'none';
    },

    showSection: function(sectionId) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        
        // Show selected section
        document.getElementById(sectionId).classList.add('active');
        document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
        
        // Load section data
        this.loadSectionData(sectionId);
    },

    updateUserInfo: function() {
        if (AppData.currentUser) {
            document.getElementById('welcome-message').textContent = `Welcome, ${AppData.currentUser.name}`;
            document.getElementById('user-role').textContent = AppData.currentUser.role.charAt(0).toUpperCase() + AppData.currentUser.role.slice(1);
            
            // Show/hide navigation based on role
            document.querySelectorAll('.nav-item[data-roles]').forEach(item => {
                const allowedRoles = item.dataset.roles.split(',');
                item.style.display = allowedRoles.includes(AppData.currentUser.role) ? 'flex' : 'none';
            });
        }
    },

    loadSectionData: function(sectionId) {
        switch(sectionId) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'inventory':
                this.loadInventory();
                break;
            case 'sales':
                this.loadSales();
                // If staff, automatically show receipt builder
                if (AppData.currentUser.role === 'staff') {
                    this.showReceiptBuilder();
                }
                break;
            case 'customers':
                this.loadCustomers();
                break;
        }
    },

    // Dashboard
    loadDashboard: function() {
        const stats = AppData.dashboardStats.stats || {};
        
        if (AppData.currentUser.role === 'staff') {
            // Staff sees only essential info
            document.getElementById('total-products').textContent = stats.total_products || 0;
            
            // Hide icons and restricted cards for staff
            document.querySelectorAll('.stat-card .stat-icon').forEach(icon => {
                icon.style.display = 'none';
            });
            
            // Hide sales and customers cards for staff
            const statCards = document.querySelectorAll('.stat-card');
            if (statCards[1]) statCards[1].style.display = 'none'; // Sales card
            if (statCards[2]) statCards[2].style.display = 'none'; // Customers card
        } else {
            // Admin sees full dashboard with real data
            document.getElementById('total-products').textContent = stats.total_products || 0;
            document.getElementById('total-sales').textContent = `$${(stats.total_sales || 0).toFixed(2)}`;
            document.getElementById('total-customers').textContent = stats.total_customers || 0;
            
            // Show all cards and icons for admin
            document.querySelectorAll('.stat-card .stat-icon').forEach(icon => {
                icon.style.display = 'flex';
            });
            document.querySelectorAll('.stat-card').forEach(card => {
                card.style.display = 'block';
            });
        }
        
        // Update current date
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    },

    // Inventory
    loadInventory: function() {
        const tbody = document.getElementById('products-tbody');
        tbody.innerHTML = AppData.products.map(product => `
            <tr>
                <td><img src="https://via.placeholder.com/50x50?text=${product.brand}" class="product-image"></td>
                <td><strong>${product.code}</strong></td>
                <td>${product.brand}</td>
                <td>${product.model}</td>
                <td>$${product.price.toFixed(2)}</td>
                <td>${product.stock}</td>
                <td><span class="status-badge ${product.stock > 10 ? 'status-in-stock' : 'status-low-stock'}">${product.stock > 10 ? 'In Stock' : 'Low Stock'}</span></td>
                <td>
                    ${AppData.currentUser.role === 'staff' ? 
                        `<button class="btn btn-primary btn-sm" onclick="ReceiptController.addToReceipt(${product.id})">
                            <i class="fas fa-plus"></i> Add to Receipt
                        </button>` :
                        `<button class="btn btn-secondary btn-sm" onclick="AppController.editProduct(${product.id})">
                            <i class="fas fa-edit"></i>
                        </button>`
                    }
                </td>
            </tr>
        `).join('');
    },

    // Sales
    loadSales: function() {
        const tbody = document.getElementById('sales-tbody');
        if (!tbody) return;
        
        tbody.innerHTML = AppData.sales.map(sale => {
            const itemsText = sale.items.map(item => item.name).join(', ');
            return `
                <tr>
                    <td>${sale.receipt_number}</td>
                    <td>${new Date(sale.sale_date).toLocaleDateString()}</td>
                    <td>${sale.customer_name}</td>
                    <td>${itemsText}</td>
                    <td>${sale.items.reduce((sum, item) => sum + item.quantity, 0)}</td>
                    <td>$${sale.total_amount.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="AppController.viewSaleDetails(${sale.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Update sales summary with real data
        const stats = AppData.dashboardStats.stats || {};
        const todaySalesEl = document.getElementById('today-sales');
        const monthSalesEl = document.getElementById('month-sales');
        const allSalesEl = document.getElementById('all-sales');
        
        if (todaySalesEl) todaySalesEl.textContent = `$${(stats.today_sales || 0).toFixed(2)}`;
        if (monthSalesEl) monthSalesEl.textContent = `$${(stats.month_sales || 0).toFixed(2)}`;
        if (allSalesEl) allSalesEl.textContent = `$${(stats.total_sales || 0).toFixed(2)}`;
    },

    // Customers
    loadCustomers: function() {
        const tbody = document.getElementById('customers-tbody');
        if (!tbody) return;
        
        tbody.innerHTML = AppData.customers.map(customer => `
            <tr>
                <td>
                    <div class="customer-info">
                        <strong>${customer.name}</strong>
                        <div class="customer-tier">${customer.customer_tier}</div>
                    </div>
                </td>
                <td>${customer.phone || 'N/A'}</td>
                <td>${customer.email || 'N/A'}</td>
                <td>$${customer.total_purchases.toFixed(2)}</td>
                <td>${new Date(customer.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="AppController.editCustomer(${customer.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },

    viewSaleDetails: function(saleId) {
        const sale = AppData.sales.find(s => s.id === saleId);
        if (sale) {
            alert(`Sale Details:\nReceipt: ${sale.receipt_number}\nCustomer: ${sale.customer_name}\nTotal: $${sale.total_amount.toFixed(2)}\nItems: ${sale.items.length}`);
        }
    },

    editCustomer: function(customerId) {
        alert(`Edit customer ${customerId} - Feature coming soon!`);
    },

    editProduct: function(id) {
        alert(`Edit product ${id} - Feature coming soon!`);
    }
};

// Initialize App (V in MVC - View initialization)
document.addEventListener('DOMContentLoaded', function() {
    // Login form handler
    document.getElementById('login-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
        submitBtn.disabled = true;
        
        try {
            const success = await AppController.login(username, password, role);
            
            if (success) {
                document.querySelector('.message')?.remove();
            } else {
                // Show error message
                const existingMsg = document.querySelector('.message');
                if (existingMsg) existingMsg.remove();
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'message message-error';
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Invalid credentials</span>';
                document.getElementById('login-form').insertBefore(errorMsg, document.getElementById('login-form').firstChild);
            }
        } catch (error) {
            console.error('Login error:', error);
            const errorMsg = document.createElement('div');
            errorMsg.className = 'message message-error';
            errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Connection error. Please try again.</span>';
            document.getElementById('login-form').insertBefore(errorMsg, document.getElementById('login-form').firstChild);
        } finally {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    // Navigation handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.nav-item')) {
            const section = e.target.closest('.nav-item').dataset.section;
            if (section) AppController.showSection(section);
        }
    });

    // Logout handler
    document.getElementById('logout-btn').addEventListener('click', function() {
        AppController.logout();
    });

    // Database connection status check
    console.log('🔗 Application initialized - Database connection required for all operations');
});
