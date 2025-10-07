// Staff Model - Extends User model for staff-specific functionality
class Staff extends User {
    constructor(id, username, password, role, name, phone = '', email = '', hireDate = new Date()) {
        super(id, username, password, role, name, phone, email);
        this.hireDate = hireDate;
        this.totalSales = 0;
        this.salesCount = 0;
        this.lastLogin = null;
        this.performance = {
            monthlySales: 0,
            monthlyTarget: 5000,
            customerRating: 0,
            completedTasks: 0
        };
    }

    // Static method to get all staff members
    static getAll() {
        const users = User.getAll();
        return users.filter(user => user.role === 'staff' || user.role === 'admin')
                   .map(user => Object.assign(new Staff(), user));
    }

    // Static method to get staff members only (excluding admins)
    static getStaffOnly() {
        const users = User.getAll();
        return users.filter(user => user.role === 'staff')
                   .map(user => Object.assign(new Staff(), user));
    }

    // Static method to get admins only
    static getAdmins() {
        const users = User.getAll();
        return users.filter(user => user.role === 'admin')
                   .map(user => Object.assign(new Staff(), user));
    }

    // Static method to create new staff member
    static create(staffData) {
        const userData = {
            ...staffData,
            role: staffData.role || 'staff'
        };
        return User.create(userData);
    }

    // Static method to get staff performance data
    static getPerformanceData() {
        const staff = Staff.getAll();
        const sales = Sale.getAll();
        
        return staff.map(member => {
            const memberSales = sales.filter(sale => sale.staffId === member.id);
            const totalSales = memberSales.reduce((sum, sale) => sum + sale.total, 0);
            const salesCount = memberSales.length;
            
            // Calculate this month's sales
            const thisMonth = Sale.getThisMonthSales().filter(sale => sale.staffId === member.id);
            const monthlySales = thisMonth.reduce((sum, sale) => sum + sale.total, 0);
            
            return {
                ...member,
                totalSales,
                salesCount,
                performance: {
                    ...member.performance,
                    monthlySales
                }
            };
        });
    }

    // Static method to get top performing staff
    static getTopPerformers(limit = 5) {
        const performanceData = Staff.getPerformanceData();
        return performanceData
            .sort((a, b) => b.performance.monthlySales - a.performance.monthlySales)
            .slice(0, limit);
    }

    // Static method to update staff performance
    static updatePerformance(staffId, performanceData) {
        const users = User.getAll();
        const userIndex = users.findIndex(user => user.id === staffId);
        
        if (userIndex !== -1) {
            users[userIndex].performance = {
                ...users[userIndex].performance,
                ...performanceData
            };
            User.saveAll(users);
            return users[userIndex];
        }
        return null;
    }

    // Static method to record staff login
    static recordLogin(staffId) {
        return User.update(staffId, { lastLogin: new Date() });
    }

    // Static method to get staff activity summary
    static getActivitySummary() {
        const staff = Staff.getAll();
        const today = new Date();
        const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        
        return {
            totalStaff: staff.length,
            activeToday: staff.filter(member => 
                member.lastLogin && new Date(member.lastLogin) >= startOfDay
            ).length,
            onlineNow: staff.filter(member => 
                member.lastLogin && 
                (new Date() - new Date(member.lastLogin)) < 30 * 60 * 1000 // 30 minutes
            ).length
        };
    }

    // Instance method to get formatted hire date
    getFormattedHireDate() {
        return new Date(this.hireDate).toLocaleDateString();
    }

    // Instance method to calculate years of service
    getYearsOfService() {
        const now = new Date();
        const hire = new Date(this.hireDate);
        return Math.floor((now - hire) / (365.25 * 24 * 60 * 60 * 1000));
    }

    // Instance method to get performance rating
    getPerformanceRating() {
        const { monthlySales, monthlyTarget } = this.performance;
        const percentage = (monthlySales / monthlyTarget) * 100;
        
        if (percentage >= 120) return 'Excellent';
        if (percentage >= 100) return 'Good';
        if (percentage >= 80) return 'Average';
        if (percentage >= 60) return 'Below Average';
        return 'Poor';
    }

    // Instance method to get performance percentage
    getPerformancePercentage() {
        const { monthlySales, monthlyTarget } = this.performance;
        return Math.round((monthlySales / monthlyTarget) * 100);
    }

    // Instance method to check if staff member is active today
    isActiveToday() {
        if (!this.lastLogin) return false;
        
        const today = new Date();
        const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        return new Date(this.lastLogin) >= startOfDay;
    }

    // Instance method to check if staff member is currently online
    isOnline() {
        if (!this.lastLogin) return false;
        
        const now = new Date();
        const lastLogin = new Date(this.lastLogin);
        return (now - lastLogin) < 30 * 60 * 1000; // 30 minutes
    }

    // Instance method to get status
    getStatus() {
        if (!this.isActive) return 'Inactive';
        if (this.isOnline()) return 'Online';
        if (this.isActiveToday()) return 'Active Today';
        return 'Offline';
    }

    // Instance method to get formatted total sales
    getFormattedTotalSales() {
        return `$${this.totalSales.toFixed(2)}`;
    }

    // Instance method to get formatted monthly sales
    getFormattedMonthlySales() {
        return `$${this.performance.monthlySales.toFixed(2)}`;
    }

    // Instance method to get sales this month
    getSalesThisMonth() {
        const thisMonthSales = Sale.getThisMonthSales().filter(sale => sale.staffId === this.id);
        return {
            count: thisMonthSales.length,
            amount: thisMonthSales.reduce((sum, sale) => sum + sale.total, 0)
        };
    }

    // Instance method to get recent sales
    getRecentSales(limit = 10) {
        const allSales = Sale.getAll();
        return allSales
            .filter(sale => sale.staffId === this.id)
            .sort((a, b) => new Date(b.saleDate) - new Date(a.saleDate))
            .slice(0, limit);
    }
}
