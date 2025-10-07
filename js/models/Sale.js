// Sale Model - Manages sales transactions and records
class Sale {
    constructor(id, customerId, productId, quantity, unitPrice, total, staffId) {
        this.id = id;
        this.customerId = customerId;
        this.productId = productId;
        this.quantity = parseInt(quantity);
        this.unitPrice = parseFloat(unitPrice);
        this.total = parseFloat(total);
        this.staffId = staffId;
        this.saleDate = new Date();
        this.status = 'completed';
    }

    // Static method to get all sales from localStorage
    static getAll() {
        const sales = localStorage.getItem('sales');
        return sales ? JSON.parse(sales).map(s => Object.assign(new Sale(), s)) : Sale.getDefaultSales();
    }

    // Static method to get default demo sales
    static getDefaultSales() {
        const defaultSales = [
            new Sale(1, 1, 1, 1, 999.99, 999.99, 1),
            new Sale(2, 2, 2, 1, 899.99, 899.99, 2),
            new Sale(3, 1, 3, 1, 699.99, 699.99, 1),
            new Sale(4, 3, 4, 2, 599.99, 1199.98, 2),
            new Sale(5, 4, 5, 1, 549.99, 549.99, 1)
        ];

        // Set different dates for demo data
        defaultSales[0].saleDate = new Date(Date.now() - 1 * 24 * 60 * 60 * 1000); // 1 day ago
        defaultSales[1].saleDate = new Date(Date.now() - 2 * 24 * 60 * 60 * 1000); // 2 days ago
        defaultSales[2].saleDate = new Date(Date.now() - 3 * 24 * 60 * 60 * 1000); // 3 days ago
        defaultSales[3].saleDate = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000); // 1 week ago
        defaultSales[4].saleDate = new Date(Date.now() - 14 * 24 * 60 * 60 * 1000); // 2 weeks ago

        Sale.saveAll(defaultSales);
        return defaultSales;
    }

    // Static method to save all sales to localStorage
    static saveAll(sales) {
        localStorage.setItem('sales', JSON.stringify(sales));
    }

    // Static method to find sale by ID
    static findById(id) {
        const sales = Sale.getAll();
        return sales.find(sale => sale.id === id);
    }

    // Static method to get sales by customer ID
    static getByCustomerId(customerId) {
        const sales = Sale.getAll();
        return sales.filter(sale => sale.customerId === customerId);
    }

    // Static method to get sales by product ID
    static getByProductId(productId) {
        const sales = Sale.getAll();
        return sales.filter(sale => sale.productId === productId);
    }

    // Static method to get sales by staff ID
    static getByStaffId(staffId) {
        const sales = Sale.getAll();
        return sales.filter(sale => sale.staffId === staffId);
    }

    // Static method to get sales by date range
    static getByDateRange(startDate, endDate) {
        const sales = Sale.getAll();
        return sales.filter(sale => {
            const saleDate = new Date(sale.saleDate);
            return saleDate >= startDate && saleDate <= endDate;
        });
    }

    // Static method to get today's sales
    static getTodaySales() {
        const today = new Date();
        const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        const endOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59);
        return Sale.getByDateRange(startOfDay, endOfDay);
    }

    // Static method to get this month's sales
    static getThisMonthSales() {
        const now = new Date();
        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
        return Sale.getByDateRange(startOfMonth, endOfMonth);
    }

    // Static method to get this year's sales
    static getThisYearSales() {
        const now = new Date();
        const startOfYear = new Date(now.getFullYear(), 0, 1);
        const endOfYear = new Date(now.getFullYear(), 11, 31, 23, 59, 59);
        return Sale.getByDateRange(startOfYear, endOfYear);
    }

    // Static method to create new sale
    static create(saleData) {
        const sales = Sale.getAll();
        const newId = Math.max(...sales.map(s => s.id), 0) + 1;
        
        const newSale = new Sale(
            newId,
            saleData.customerId,
            saleData.productId,
            saleData.quantity,
            saleData.unitPrice,
            saleData.total,
            saleData.staffId
        );

        sales.push(newSale);
        Sale.saveAll(sales);

        // Update product stock
        Product.reduceStock(saleData.productId, saleData.quantity);

        // Update customer purchase info
        Customer.updatePurchaseInfo(saleData.customerId, saleData.total);

        return newSale;
    }

    // Static method to update sale
    static update(id, saleData) {
        const sales = Sale.getAll();
        const saleIndex = sales.findIndex(sale => sale.id === id);
        
        if (saleIndex !== -1) {
            const updatedSale = {
                ...sales[saleIndex],
                ...saleData
            };
            sales[saleIndex] = updatedSale;
            Sale.saveAll(sales);
            return updatedSale;
        }
        return null;
    }

    // Static method to delete sale
    static delete(id) {
        const sales = Sale.getAll();
        const sale = Sale.findById(id);
        
        if (sale) {
            // Restore product stock
            Product.updateStock(sale.productId, sale.quantity);
            
            // Update customer purchase info
            const customer = Customer.findById(sale.customerId);
            if (customer) {
                Customer.update(sale.customerId, {
                    totalPurchases: Math.max(0, customer.totalPurchases - sale.total)
                });
            }
        }

        const filteredSales = sales.filter(sale => sale.id !== id);
        Sale.saveAll(filteredSales);
        return filteredSales.length < sales.length;
    }

    // Static method to calculate total sales amount
    static getTotalSalesAmount(sales = null) {
        const salesData = sales || Sale.getAll();
        return salesData.reduce((total, sale) => total + sale.total, 0);
    }

    // Static method to get sales statistics
    static getStatistics() {
        const allSales = Sale.getAll();
        const todaySales = Sale.getTodaySales();
        const monthSales = Sale.getThisMonthSales();
        const yearSales = Sale.getThisYearSales();

        return {
            total: {
                count: allSales.length,
                amount: Sale.getTotalSalesAmount(allSales)
            },
            today: {
                count: todaySales.length,
                amount: Sale.getTotalSalesAmount(todaySales)
            },
            thisMonth: {
                count: monthSales.length,
                amount: Sale.getTotalSalesAmount(monthSales)
            },
            thisYear: {
                count: yearSales.length,
                amount: Sale.getTotalSalesAmount(yearSales)
            }
        };
    }

    // Static method to get top selling products
    static getTopSellingProducts(limit = 10) {
        const sales = Sale.getAll();
        const productSales = {};

        sales.forEach(sale => {
            if (!productSales[sale.productId]) {
                productSales[sale.productId] = {
                    productId: sale.productId,
                    totalQuantity: 0,
                    totalAmount: 0,
                    salesCount: 0
                };
            }
            productSales[sale.productId].totalQuantity += sale.quantity;
            productSales[sale.productId].totalAmount += sale.total;
            productSales[sale.productId].salesCount += 1;
        });

        return Object.values(productSales)
            .sort((a, b) => b.totalQuantity - a.totalQuantity)
            .slice(0, limit);
    }

    // Instance method to get customer info
    getCustomer() {
        return Customer.findById(this.customerId);
    }

    // Instance method to get product info
    getProduct() {
        return Product.findById(this.productId);
    }

    // Instance method to get staff info
    getStaff() {
        return User.findById(this.staffId);
    }

    // Instance method to get formatted sale date
    getFormattedDate() {
        return new Date(this.saleDate).toLocaleDateString();
    }

    // Instance method to get formatted total
    getFormattedTotal() {
        return `$${this.total.toFixed(2)}`;
    }

    // Instance method to get formatted unit price
    getFormattedUnitPrice() {
        return `$${this.unitPrice.toFixed(2)}`;
    }

    // Instance method to generate sale ID string
    getSaleIdString() {
        return `SALE-${String(this.id).padStart(6, '0')}`;
    }
}
