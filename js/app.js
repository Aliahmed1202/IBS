// Main Application Controller - MVC Architecture
class MobileShopApp {
    constructor() {
        this.currentUser = null;
        this.currentSection = 'dashboard';
        this.controllers = {};
        this.views = {};
        
        this.init();
    }

    // Initialize the application
    init() {
        this.initializeControllers();
        this.initializeViews();
        this.bindEvents();
        this.checkAuthStatus();
    }

    // Initialize all controllers
    initializeControllers() {
        this.controllers.auth = new AuthController();
        this.controllers.dashboard = new DashboardController();
        this.controllers.inventory = new InventoryController();
        this.controllers.sales = new SalesController();
        this.controllers.customer = new CustomerController();
        this.controllers.reports = new ReportsController();
        this.controllers.staff = new StaffController();
    }

    // Initialize all views
    initializeViews() {
        this.views.login = new LoginView();
        this.views.dashboard = new DashboardView();
        this.views.inventory = new InventoryView();
        this.views.sales = new SalesView();
        this.views.customer = new CustomerView();
        this.views.reports = new ReportsView();
        this.views.staff = new StaffView();
    }

    // Bind global events
    bindEvents() {
        // Navigation events
        document.addEventListener('click', (e) => {
            if (e.target.closest('.nav-item')) {
                const navItem = e.target.closest('.nav-item');
                const section = navItem.dataset.section;
                if (section) {
                    this.navigateToSection(section);
                }
            }
        });

        // Logout event
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            this.logout();
        });

        // Login form event
        document.getElementById('login-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        // Window events
        window.addEventListener('beforeunload', () => {
            this.saveAppState();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }

    // Handle login
    async handleLogin() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;

        if (!username || !password || !role) {
            this.showMessage('Please fill in all fields', 'error');
            return;
        }

        const user = User.authenticate(username, password, role);
        
        if (user) {
            this.currentUser = user;
            Staff.recordLogin(user.id);
            this.showApp();
            this.updateUserInterface();
            this.navigateToSection('dashboard');
            this.showMessage(`Welcome back, ${user.getDisplayName()}!`, 'success');
        } else {
            this.showMessage('Invalid credentials. Please try again.', 'error');
        }
    }

    // Logout user
    logout() {
        this.currentUser = null;
        this.hideApp();
        this.clearForms();
        sessionStorage.removeItem('currentUser');
        this.showMessage('You have been logged out successfully.', 'info');
    }

    // Check authentication status
    checkAuthStatus() {
        // Ensure default users exist
        User.getAll();
        
        const savedUser = sessionStorage.getItem('currentUser');
        if (savedUser) {
            this.currentUser = JSON.parse(savedUser);
            this.showApp();
            this.updateUserInterface();
            this.navigateToSection('dashboard');
        } else {
            this.hideApp();
        }
    }

    // Show main application
    showApp() {
        document.getElementById('login-container').style.display = 'none';
        document.getElementById('app-container').style.display = 'block';
        
        if (this.currentUser) {
            sessionStorage.setItem('currentUser', JSON.stringify(this.currentUser));
        }
    }

    // Hide main application
    hideApp() {
        document.getElementById('login-container').style.display = 'flex';
        document.getElementById('app-container').style.display = 'none';
    }

    // Update user interface based on role
    updateUserInterface() {
        if (!this.currentUser) return;

        // Update welcome message
        const welcomeMsg = document.getElementById('welcome-message');
        const userRole = document.getElementById('user-role');
        
        if (welcomeMsg) {
            welcomeMsg.textContent = `Welcome, ${this.currentUser.getDisplayName()}`;
        }
        
        if (userRole) {
            userRole.textContent = this.currentUser.role.charAt(0).toUpperCase() + this.currentUser.role.slice(1);
        }

        // Show/hide navigation items based on role
        this.updateNavigation();
        
        // Update current date
        this.updateCurrentDate();
    }

    // Update navigation based on user role
    updateNavigation() {
        const navItems = document.querySelectorAll('.nav-item[data-roles]');
        
        navItems.forEach(item => {
            const allowedRoles = item.dataset.roles.split(',');
            if (allowedRoles.includes(this.currentUser.role)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Navigate to section
    navigateToSection(section) {
        // Check permissions
        if (!this.hasPermissionForSection(section)) {
            this.showMessage('You do not have permission to access this section.', 'error');
            return;
        }

        // Hide all sections
        document.querySelectorAll('.content-section').forEach(s => {
            s.classList.remove('active');
        });

        // Remove active class from nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        // Show selected section
        const sectionElement = document.getElementById(section);
        const navItem = document.querySelector(`[data-section="${section}"]`);
        
        if (sectionElement) {
            sectionElement.classList.add('active');
        }
        
        if (navItem) {
            navItem.classList.add('active');
        }

        // Load section data
        this.loadSectionData(section);
        
        this.currentSection = section;
    }

    // Check if user has permission for section
    hasPermissionForSection(section) {
        if (!this.currentUser) return false;

        const sectionPermissions = {
            'dashboard': ['view_dashboard'],
            'inventory': ['manage_inventory'],
            'sales': ['manage_sales'],
            'customers': ['manage_customers'],
            'reports': ['view_reports'],
            'staff-management': ['manage_staff']
        };

        const requiredPermissions = sectionPermissions[section] || [];
        return requiredPermissions.every(permission => 
            this.currentUser.hasPermission(permission)
        );
    }

    // Load section data
    loadSectionData(section) {
        switch (section) {
            case 'dashboard':
                this.controllers.dashboard.loadDashboard();
                break;
            case 'inventory':
                this.controllers.inventory.loadInventory();
                break;
            case 'sales':
                this.controllers.sales.loadSales();
                break;
            case 'customers':
                this.controllers.customer.loadCustomers();
                break;
            case 'reports':
                this.controllers.reports.loadReports();
                break;
            case 'staff-management':
                this.controllers.staff.loadStaff();
                break;
        }
    }

    // Update current date
    updateCurrentDate() {
        const dateElement = document.getElementById('current-date');
        if (dateElement) {
            const now = new Date();
            dateElement.textContent = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }

    // Handle keyboard shortcuts
    handleKeyboardShortcuts(e) {
        if (!this.currentUser) return;

        // Ctrl/Cmd + shortcuts
        if (e.ctrlKey || e.metaKey) {
            switch (e.key) {
                case '1':
                    e.preventDefault();
                    this.navigateToSection('dashboard');
                    break;
                case '2':
                    e.preventDefault();
                    if (this.hasPermissionForSection('inventory')) {
                        this.navigateToSection('inventory');
                    }
                    break;
                case '3':
                    e.preventDefault();
                    if (this.hasPermissionForSection('sales')) {
                        this.navigateToSection('sales');
                    }
                    break;
                case '4':
                    e.preventDefault();
                    if (this.hasPermissionForSection('customers')) {
                        this.navigateToSection('customers');
                    }
                    break;
                case 'l':
                    e.preventDefault();
                    this.logout();
                    break;
            }
        }

        // Escape key to close modals
        if (e.key === 'Escape') {
            this.closeAllModals();
        }
    }

    // Close all modals
    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }

    // Clear all forms
    clearForms() {
        document.querySelectorAll('form').forEach(form => {
            form.reset();
        });
    }

    // Show message to user
    showMessage(message, type = 'info') {
        // Remove existing messages
        document.querySelectorAll('.message').forEach(msg => msg.remove());

        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.innerHTML = `
            <i class="fas fa-${this.getMessageIcon(type)}"></i>
            <span>${message}</span>
        `;

        // Add to page
        const container = document.querySelector('.main-content') || document.body;
        container.insertBefore(messageDiv, container.firstChild);

        // Auto remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    // Get icon for message type
    getMessageIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'info': 'info-circle',
            'warning': 'exclamation-triangle'
        };
        return icons[type] || 'info-circle';
    }

    // Save application state
    saveAppState() {
        if (this.currentUser) {
            sessionStorage.setItem('currentUser', JSON.stringify(this.currentUser));
            sessionStorage.setItem('currentSection', this.currentSection);
        }
    }

    // Get current user
    getCurrentUser() {
        return this.currentUser;
    }

    // Get current section
    getCurrentSection() {
        return this.currentSection;
    }

    // Refresh current section
    refreshCurrentSection() {
        this.loadSectionData(this.currentSection);
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new MobileShopApp();
});

// Global utility functions
window.utils = {
    formatCurrency: (amount) => `$${parseFloat(amount).toFixed(2)}`,
    formatDate: (date) => new Date(date).toLocaleDateString(),
    formatDateTime: (date) => new Date(date).toLocaleString(),
    generateId: () => Date.now() + Math.random().toString(36).substr(2, 9),
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};
