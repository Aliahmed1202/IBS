// Reports Controller - Manages reports and analytics (Admin only)
class ReportsController {
    constructor() {
        this.currentPeriod = 'month';
        this.reportData = null;
        this.init();
    }

    // Initialize reports controller
    init() {
        this.bindEvents();
    }

    // Bind reports events
    bindEvents() {
        // Report period change
        document.getElementById('report-period')?.addEventListener('change', (e) => {
            this.currentPeriod = e.target.value;
            this.generateReport();
        });

        // Generate report button
        document.getElementById('generate-report')?.addEventListener('click', () => {
            this.generateReport();
        });
    }

    // Load reports section
    loadReports() {
        try {
            this.generateReport();
        } catch (error) {
            console.error('Error loading reports:', error);
            this.showError('Failed to load reports data');
        }
    }

    // Generate report based on selected period
    generateReport() {
        const reportData = this.getReportData(this.currentPeriod);
        this.reportData = reportData;
        
        this.updateTopProducts(reportData.topProducts);
        this.showReportSummary(reportData);
    }

    // Get report data for specified period
    getReportData(period) {
        let sales, startDate, endDate;
        const now = new Date();

        switch (period) {
            case 'today':
                sales = Sale.getTodaySales();
                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                endDate = now;
                break;
            case 'week':
                const weekStart = new Date(now.setDate(now.getDate() - now.getDay()));
                const weekEnd = new Date(now.setDate(weekStart.getDate() + 6));
                sales = Sale.getByDateRange(weekStart, weekEnd);
                startDate = weekStart;
                endDate = weekEnd;
                break;
            case 'month':
                sales = Sale.getThisMonthSales();
                startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                break;
            case 'year':
                sales = Sale.getThisYearSales();
                startDate = new Date(now.getFullYear(), 0, 1);
                endDate = new Date(now.getFullYear(), 11, 31);
                break;
            default:
                sales = Sale.getAll();
                startDate = null;
                endDate = null;
        }

        const totalAmount = Sale.getTotalSalesAmount(sales);
        const totalCount = sales.length;
        const topProducts = Sale.getTopSellingProducts(10);
        const customerAnalytics = this.getCustomerAnalytics(sales);
        const staffPerformance = this.getStaffPerformance(sales);

        return {
            period,
            startDate,
            endDate,
            sales,
            totalAmount,
            totalCount,
            topProducts,
            customerAnalytics,
            staffPerformance,
            averageOrderValue: totalCount > 0 ? totalAmount / totalCount : 0
        };
    }

    // Get customer analytics for the period
    getCustomerAnalytics(sales) {
        const customerSales = {};
        
        sales.forEach(sale => {
            if (!customerSales[sale.customerId]) {
                customerSales[sale.customerId] = {
                    customerId: sale.customerId,
                    totalAmount: 0,
                    orderCount: 0
                };
            }
            customerSales[sale.customerId].totalAmount += sale.total;
            customerSales[sale.customerId].orderCount += 1;
        });

        const topCustomers = Object.values(customerSales)
            .sort((a, b) => b.totalAmount - a.totalAmount)
            .slice(0, 10)
            .map(cs => {
                const customer = Customer.findById(cs.customerId);
                return {
                    ...cs,
                    customerName: customer?.name || 'Unknown'
                };
            });

        return {
            totalCustomers: Object.keys(customerSales).length,
            topCustomers,
            newCustomers: this.getNewCustomersCount()
        };
    }

    // Get staff performance for the period
    getStaffPerformance(sales) {
        const staffSales = {};
        
        sales.forEach(sale => {
            if (!staffSales[sale.staffId]) {
                staffSales[sale.staffId] = {
                    staffId: sale.staffId,
                    totalAmount: 0,
                    salesCount: 0
                };
            }
            staffSales[sale.staffId].totalAmount += sale.total;
            staffSales[sale.staffId].salesCount += 1;
        });

        return Object.values(staffSales)
            .sort((a, b) => b.totalAmount - a.totalAmount)
            .map(ss => {
                const staff = User.findById(ss.staffId);
                return {
                    ...ss,
                    staffName: staff?.getDisplayName() || 'Unknown'
                };
            });
    }

    // Get new customers count
    getNewCustomersCount() {
        const customers = Customer.getAll();
        const now = new Date();
        let startDate;

        switch (this.currentPeriod) {
            case 'today':
                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                break;
            case 'week':
                startDate = new Date(now.setDate(now.getDate() - 7));
                break;
            case 'month':
                startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                break;
            case 'year':
                startDate = new Date(now.getFullYear(), 0, 1);
                break;
            default:
                return customers.length;
        }

        return customers.filter(customer => 
            new Date(customer.createdAt) >= startDate
        ).length;
    }

    // Update top products display
    updateTopProducts(topProducts) {
        const container = document.getElementById('top-products');
        if (!container) return;

        if (topProducts.length === 0) {
            container.innerHTML = '<p class="no-data">No data available</p>';
            return;
        }

        container.innerHTML = topProducts.map((item, index) => {
            const product = Product.findById(item.productId);
            return `
                <div class="top-product-item">
                    <div class="product-rank">#${index + 1}</div>
                    <div class="product-info">
                        <strong>${product?.getFullName() || 'Unknown Product'}</strong>
                        <div class="product-stats">
                            <span>Sold: ${item.totalQuantity}</span>
                            <span>Revenue: ${utils.formatCurrency(item.totalAmount)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Show report summary
    showReportSummary(data) {
        const summaryContainer = document.querySelector('#reports .section-header');
        if (!summaryContainer) return;

        // Remove existing summary
        const existingSummary = summaryContainer.querySelector('.report-summary');
        if (existingSummary) {
            existingSummary.remove();
        }

        // Create new summary
        const summaryDiv = document.createElement('div');
        summaryDiv.className = 'report-summary';
        summaryDiv.innerHTML = `
            <div class="summary-stats">
                <span class="stat">Sales: ${data.totalCount}</span>
                <span class="stat">Revenue: ${utils.formatCurrency(data.totalAmount)}</span>
                <span class="stat">Avg Order: ${utils.formatCurrency(data.averageOrderValue)}</span>
                <span class="stat">Customers: ${data.customerAnalytics.totalCustomers}</span>
            </div>
        `;

        summaryContainer.appendChild(summaryDiv);
    }

    // Export report data
    exportReport() {
        if (!this.reportData) {
            this.showError('No report data to export');
            return;
        }

        const exportData = {
            period: this.reportData.period,
            generatedAt: new Date().toISOString(),
            summary: {
                totalSales: this.reportData.totalCount,
                totalRevenue: this.reportData.totalAmount,
                averageOrderValue: this.reportData.averageOrderValue
            },
            topProducts: this.reportData.topProducts,
            customerAnalytics: this.reportData.customerAnalytics,
            staffPerformance: this.reportData.staffPerformance
        };

        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `sales-report-${this.reportData.period}-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Show error message
    showError(message) {
        window.app?.showMessage(message, 'error');
    }

    // Show success message
    showSuccess(message) {
        window.app?.showMessage(message, 'success');
    }
}
