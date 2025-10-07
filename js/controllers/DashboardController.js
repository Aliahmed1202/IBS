// Dashboard Controller - Manages dashboard data and interactions
class DashboardController {
    constructor() {
        this.refreshInterval = null;
        this.autoRefreshEnabled = true;
        this.refreshRate = 30000; // 30 seconds
        this.init();
    }

    // Initialize dashboard controller
    init() {
        this.bindEvents();
    }

    // Bind dashboard events
    bindEvents() {
        // Auto-refresh toggle
        document.addEventListener('click', (e) => {
            if (e.target.id === 'toggle-auto-refresh') {
                this.toggleAutoRefresh();
            }
        });

        // Manual refresh button
        document.addEventListener('click', (e) => {
            if (e.target.id === 'refresh-dashboard') {
                this.loadDashboard();
            }
        });

        // Listen for data updates from other controllers
        document.addEventListener('dataUpdated', (e) => {
            if (this.isCurrentSection('dashboard')) {
                this.loadDashboard();
            }
        });
    }

    // Load dashboard data
    loadDashboard() {
        try {
            this.updateStatistics();
            this.updateRecentActivities();
            this.updateCurrentDate();
            this.startAutoRefresh();
        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Failed to load dashboard data');
        }
    }

    // Update dashboard statistics
    updateStatistics() {
        const stats = this.calculateStatistics();
        
        // Update stat cards
        this.updateStatCard('total-products', stats.totalProducts);
        this.updateStatCard('total-sales', utils.formatCurrency(stats.totalSales));
        this.updateStatCard('total-customers', stats.totalCustomers);
        this.updateStatCard('low-stock', stats.lowStockItems);

        // Add trend indicators
        this.updateTrendIndicators(stats);
    }

    // Calculate dashboard statistics
    calculateStatistics() {
        const products = Product.getAll();
        const customers = Customer.getAll();
        const salesStats = Sale.getStatistics();
        const lowStockProducts = Product.getLowStock();

        return {
            totalProducts: products.length,
            totalSales: salesStats.total.amount,
            totalCustomers: customers.length,
            lowStockItems: lowStockProducts.length,
            todaySales: salesStats.today.amount,
            monthSales: salesStats.thisMonth.amount,
            salesCount: salesStats.total.count,
            trends: this.calculateTrends()
        };
    }

    // Calculate trends for statistics
    calculateTrends() {
        const now = new Date();
        const yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000);
        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());

        const todaySales = Sale.getTodaySales();
        const yesterdaySales = Sale.getByDateRange(
            new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate()),
            new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 23, 59, 59)
        );

        const thisMonthSales = Sale.getThisMonthSales();
        const lastMonthSales = Sale.getByDateRange(
            new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1),
            new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0, 23, 59, 59)
        );

        return {
            dailySalesTrend: this.calculatePercentageChange(
                Sale.getTotalSalesAmount(yesterdaySales),
                Sale.getTotalSalesAmount(todaySales)
            ),
            monthlySalesTrend: this.calculatePercentageChange(
                Sale.getTotalSalesAmount(lastMonthSales),
                Sale.getTotalSalesAmount(thisMonthSales)
            )
        };
    }

    // Calculate percentage change
    calculatePercentageChange(oldValue, newValue) {
        if (oldValue === 0) return newValue > 0 ? 100 : 0;
        return ((newValue - oldValue) / oldValue) * 100;
    }

    // Update individual stat card
    updateStatCard(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Animate number change
            this.animateNumber(element, value);
        }
    }

    // Animate number changes
    animateNumber(element, targetValue) {
        const currentValue = parseFloat(element.textContent.replace(/[^0-9.-]+/g, '')) || 0;
        const isMonetary = element.textContent.includes('$');
        
        if (currentValue === targetValue) return;

        const duration = 1000; // 1 second
        const steps = 30;
        const increment = (targetValue - currentValue) / steps;
        let current = currentValue;
        let step = 0;

        const timer = setInterval(() => {
            step++;
            current += increment;
            
            if (step >= steps) {
                current = targetValue;
                clearInterval(timer);
            }

            if (isMonetary) {
                element.textContent = utils.formatCurrency(current);
            } else {
                element.textContent = Math.round(current);
            }
        }, duration / steps);
    }

    // Update trend indicators
    updateTrendIndicators(stats) {
        // Add trend arrows and percentages to stat cards
        const trends = stats.trends;
        
        this.addTrendIndicator('total-sales', trends.monthlySalesTrend);
        this.addTrendIndicator('low-stock', -trends.dailySalesTrend); // Inverse for low stock
    }

    // Add trend indicator to stat card
    addTrendIndicator(cardId, trendPercentage) {
        const card = document.querySelector(`#${cardId}`).closest('.stat-card');
        if (!card) return;

        // Remove existing trend indicator
        const existingTrend = card.querySelector('.trend-indicator');
        if (existingTrend) {
            existingTrend.remove();
        }

        // Create new trend indicator
        const trendDiv = document.createElement('div');
        trendDiv.className = 'trend-indicator';
        
        const isPositive = trendPercentage >= 0;
        const arrow = isPositive ? '↗' : '↘';
        const color = isPositive ? '#22c55e' : '#ef4444';
        
        trendDiv.innerHTML = `
            <span style="color: ${color}; font-size: 0.8rem;">
                ${arrow} ${Math.abs(trendPercentage).toFixed(1)}%
            </span>
        `;
        
        card.querySelector('.stat-info').appendChild(trendDiv);
    }

    // Update recent activities
    updateRecentActivities() {
        const activities = this.getRecentActivities();
        const activityList = document.getElementById('activity-list');
        
        if (!activityList) return;

        if (activities.length === 0) {
            activityList.innerHTML = '<p class="no-data">No recent activities</p>';
            return;
        }

        activityList.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-info">
                    <div class="activity-text">${activity.text}</div>
                    <div class="activity-time">${activity.time}</div>
                </div>
                <div class="activity-icon">
                    <i class="fas fa-${activity.icon}"></i>
                </div>
            </div>
        `).join('');
    }

    // Get recent activities
    getRecentActivities() {
        const activities = [];
        
        // Recent sales
        const recentSales = Sale.getAll()
            .sort((a, b) => new Date(b.saleDate) - new Date(a.saleDate))
            .slice(0, 5);

        recentSales.forEach(sale => {
            const customer = Customer.findById(sale.customerId);
            const product = Product.findById(sale.productId);
            
            activities.push({
                text: `Sale: ${customer?.name || 'Unknown'} purchased ${product?.getFullName() || 'Unknown Product'}`,
                time: this.getRelativeTime(sale.saleDate),
                icon: 'shopping-cart',
                type: 'sale'
            });
        });

        // Low stock alerts
        const lowStockProducts = Product.getLowStock(5);
        lowStockProducts.slice(0, 3).forEach(product => {
            activities.push({
                text: `Low Stock Alert: ${product.getFullName()} (${product.stock} remaining)`,
                time: 'Now',
                icon: 'exclamation-triangle',
                type: 'alert'
            });
        });

        // New customers
        const recentCustomers = Customer.getRecentCustomers(3);
        recentCustomers.forEach(customer => {
            activities.push({
                text: `New Customer: ${customer.name} registered`,
                time: this.getRelativeTime(customer.createdAt),
                icon: 'user-plus',
                type: 'customer'
            });
        });

        // Sort by time and return top 10
        return activities
            .sort((a, b) => {
                if (a.time === 'Now') return -1;
                if (b.time === 'Now') return 1;
                return 0;
            })
            .slice(0, 10);
    }

    // Get relative time string
    getRelativeTime(date) {
        const now = new Date();
        const past = new Date(date);
        const diffMs = now - past;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} min ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return past.toLocaleDateString();
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

    // Start auto refresh
    startAutoRefresh() {
        if (this.autoRefreshEnabled && !this.refreshInterval) {
            this.refreshInterval = setInterval(() => {
                if (this.isCurrentSection('dashboard')) {
                    this.updateStatistics();
                    this.updateRecentActivities();
                }
            }, this.refreshRate);
        }
    }

    // Stop auto refresh
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    // Toggle auto refresh
    toggleAutoRefresh() {
        this.autoRefreshEnabled = !this.autoRefreshEnabled;
        
        if (this.autoRefreshEnabled) {
            this.startAutoRefresh();
        } else {
            this.stopAutoRefresh();
        }
        
        // Update UI indicator
        this.updateAutoRefreshIndicator();
    }

    // Update auto refresh indicator
    updateAutoRefreshIndicator() {
        const indicator = document.getElementById('auto-refresh-indicator');
        if (indicator) {
            indicator.textContent = this.autoRefreshEnabled ? 'Auto-refresh: ON' : 'Auto-refresh: OFF';
            indicator.className = this.autoRefreshEnabled ? 'status-active' : 'status-inactive';
        }
    }

    // Check if current section is dashboard
    isCurrentSection(section) {
        return window.app && window.app.getCurrentSection() === section;
    }

    // Show error message
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'message message-error';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        
        const dashboard = document.getElementById('dashboard');
        if (dashboard) {
            dashboard.insertBefore(errorDiv, dashboard.firstChild);
            setTimeout(() => errorDiv.remove(), 5000);
        }
    }

    // Export dashboard data
    exportDashboardData() {
        const stats = this.calculateStatistics();
        const activities = this.getRecentActivities();
        
        const data = {
            statistics: stats,
            activities: activities,
            exportDate: new Date().toISOString(),
            exportedBy: window.app?.getCurrentUser()?.getDisplayName() || 'Unknown'
        };
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `dashboard-export-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Cleanup when leaving dashboard
    cleanup() {
        this.stopAutoRefresh();
    }
}
